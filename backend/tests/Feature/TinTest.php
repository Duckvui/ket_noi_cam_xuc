<?php

namespace Tests\Feature;

use App\Models\BanBe;
use App\Models\CuocTroChuyen;
use App\Models\TaiKhoan;
use App\Models\Tin;
use App\Models\TinNhan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TinTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $name): TaiKhoan
    {
        return TaiKhoan::create(['TaiKhoan' => $name, 'MatKhau' => 'test', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
    }

    public function test_feed_returns_owner_identity_avatar_and_chronological_stories_without_truncation(): void
    {
        $owner = $this->account('owner');
        $other = $this->account('other');
        $owner->thongTinCaNhan()->create(['Ho' => 'Nguyễn', 'Ten' => 'An']);
        $owner->avatars()->create(['AnhAvatar' => '/avatar-old.jpg']);
        $owner->avatars()->create(['AnhAvatar' => '/avatar-current.jpg']);
        for ($i = 0; $i < 102; $i++) {
            Tin::create([
                'idTaiKhoan' => $i % 2 ? $other->idTaiKhoan : $owner->idTaiKhoan,
                'LoaiTin' => 'VAN_BAN', 'NoiDung' => "Tin $i", 'CheDoHienThi' => 'Cong_Khai',
                'ThoiGianDang' => now()->subMinutes(102 - $i), 'ThoiGianHetHan' => now()->addDay(),
            ]);
        }
        $response = $this->actingAs($owner, 'tai_khoan')->getJson('/api/tins')->assertOk()->assertJsonCount(102, 'data');
        $response->assertJsonPath('data.0.idTaiKhoan', $owner->idTaiKhoan)
            ->assertJsonPath('data.0.ten_hien_thi', 'Nguyễn An')
            ->assertJsonPath('data.0.avatar', '/avatar-current.jpg')
            ->assertJsonPath('data.0.is_owner', true)
            ->assertJsonPath('data.1.is_owner', false)
            ->assertJsonPath('data.0.NoiDung', 'Tin 0')
            ->assertJsonPath('data.101.NoiDung', 'Tin 101');
    }

    public function test_text_background_is_persisted_validated_and_defaults_for_existing_stories(): void
    {
        $owner = $this->account('owner');
        $this->actingAs($owner, 'tai_khoan');
        $data = ['LoaiTin' => 'VAN_BAN', 'NoiDung' => 'Nền xanh', 'CheDoHienThi' => 'Cong_Khai'];
        $this->postJson('/api/tins', $data + ['MauNen' => 'blue'])->assertCreated()->assertJsonPath('data.MauNen', 'blue');
        $this->assertDatabaseHas('tins', ['MauNen' => 'blue', 'idTaiKhoan' => $owner->idTaiKhoan]);
        $this->postJson('/api/tins', $data + ['MauNen' => 'invalid'])->assertUnprocessable();
        $this->postJson('/api/tins', $data)->assertCreated()->assertJsonPath('data.MauNen', 'purple');
        Tin::query()->update(['MauNen' => null]);
        $this->getJson('/api/tins')->assertJsonPath('data.0.MauNen', 'purple');
    }

    public function test_login_required_and_text_story_uses_session_owner_and_expires(): void
    {
        $this->postJson('/api/tins', [])->assertUnauthorized();
        $user = $this->account('owner');
        $this->actingAs($user, 'tai_khoan');
        $response = $this->postJson('/api/tins', ['LoaiTin' => 'VAN_BAN', 'NoiDung' => 'Xin chào', 'CheDoHienThi' => 'Cong_Khai', 'idTaiKhoan' => 999]);
        $response->assertCreated()->assertJsonPath('data.NoiDung', 'Xin chào');
        $story = Tin::firstOrFail();
        $this->assertEquals($user->idTaiKhoan, $story->idTaiKhoan);
        $this->assertEquals(24, $story->ThoiGianDang->diffInHours($story->ThoiGianHetHan));
        $this->travel(25)->hours();
        $this->getJson('/api/tins')->assertJsonCount(0, 'data');
    }

    public function test_validation_rejects_missing_content_wrong_media_and_invalid_type(): void
    {
        Storage::fake('local');
        $this->actingAs($this->account('owner'), 'tai_khoan');
        $base = ['CheDoHienThi' => 'Cong_Khai'];
        $this->postJson('/api/tins', $base + ['LoaiTin' => 'VAN_BAN', 'NoiDung' => '   '])->assertUnprocessable();
        $this->postJson('/api/tins', $base + ['LoaiTin' => 'ANH'])->assertUnprocessable();
        $this->postJson('/api/tins', $base + ['LoaiTin' => 'OTHER'])->assertUnprocessable();
        $this->postJson('/api/tins', $base + ['LoaiTin' => 'VIDEO', 'media' => UploadedFile::fake()->image('photo.jpg')])->assertUnprocessable();
        $this->postJson('/api/tins', $base + ['LoaiTin' => 'ANH', 'media' => UploadedFile::fake()->image('large.jpg')->size(10241)])->assertUnprocessable();
        $this->assertDatabaseCount('tins', 0);
    }

    public function test_image_video_upload_and_media_privacy(): void
    {
        Storage::fake('local');
        $owner = $this->account('owner');
        $other = $this->account('other');
        $this->actingAs($owner, 'tai_khoan');
        foreach (['ANH' => UploadedFile::fake()->image('photo.jpg'), 'VIDEO' => UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4')] as $type => $file) {
            $response = $this->postJson('/api/tins', ['LoaiTin' => $type, 'media' => $file, 'CheDoHienThi' => 'Chi_Minh_Toi'])->assertCreated();
            $story = Tin::findOrFail($response->json('data.idTin'));
            Storage::disk('local')->assertExists($story->DuongDanMedia);
            $url = $response->json('data.media_url');
            $this->get($url)->assertOk();
            $this->actingAs($other, 'tai_khoan')->get($url)->assertNotFound();
            $this->actingAs($owner, 'tai_khoan');
        }
    }

    public function test_feed_respects_friendship_visibility_and_hidden_status(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        foreach (['Cong_Khai', 'Ban_Be', 'Chi_Minh_Toi'] as $visibility) {
            Tin::create(['idTaiKhoan' => $owner->idTaiKhoan, 'LoaiTin' => 'VAN_BAN', 'NoiDung' => 'Hi', 'CheDoHienThi' => $visibility, 'ThoiGianHetHan' => now()->addDay()]);
        }
        $this->actingAs($viewer, 'tai_khoan')->getJson('/api/tins')->assertJsonCount(1, 'data');
        BanBe::create(['idTaiKhoan1' => $viewer->idTaiKhoan, 'idTaiKhoan2' => $owner->idTaiKhoan, 'TrangThai' => 'Dang_La_Ban']);
        $this->getJson('/api/tins')->assertJsonCount(2, 'data');
        Tin::where('CheDoHienThi', 'Cong_Khai')->update(['TrangThai' => 'Da_An']);
        $this->getJson('/api/tins')->assertJsonCount(1, 'data');
    }

    public function test_reply_to_story_creates_a_personal_conversation_and_message(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $story = Tin::create([
            'idTaiKhoan' => $owner->idTaiKhoan,
            'LoaiTin' => 'VAN_BAN',
            'NoiDung' => 'Tin cua chu so huu',
            'CheDoHienThi' => 'Cong_Khai',
            'ThoiGianHetHan' => now()->addDay(),
        ]);

        $response = $this->actingAs($viewer, 'tai_khoan')
            ->postJson("/api/tins/{$story->idTin}/tra-loi", ['NoiDung' => 'Xin chao'])
            ->assertCreated()
            ->assertJsonPath('data.tinNhan.content', 'Xin chao')
            ->assertJsonPath('data.tinNhan.idTin', $story->idTin);

        $conversationId = $response->json('data.idCuocTroChuyen');
        $this->assertDatabaseCount('cuoc_tro_chuyens', 1);
        $this->assertDatabaseCount('thanh_vien_cuoc_tro_chuyens', 2);
        $this->assertDatabaseHas('tin_nhans', [
            'idCuocTroChuyen' => $conversationId,
            'idNguoiGui' => $viewer->idTaiKhoan,
            'idTin' => $story->idTin,
            'NoiDung' => 'Xin chao',
        ]);
        $this->assertTrue(CuocTroChuyen::findOrFail($conversationId)->tinNhans()->exists());
        $this->assertSame($story->idTin, TinNhan::firstOrFail()->idTin);
    }
}
