<?php

namespace Tests\Feature;

use App\Models\Avatar;
use App\Models\BanBe;
use App\Models\CamXuc;
use App\Models\TaiKhoan;
use App\Models\TrangThaiCamXuc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvatarCamXucTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $name): TaiKhoan
    {
        return TaiKhoan::create(['TaiKhoan' => $name, 'MatKhau' => 'test', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
    }

    public function test_requires_login(): void
    {
        $this->getJson('/api/avatar-animation')->assertUnauthorized();
        $this->getJson('/api/cam-xuc-ca-nhan?ids[]=1')->assertUnauthorized();
        $this->putJson('/api/avatar-animation/me', [])->assertUnauthorized();
        $this->putJson('/api/cam-xuc-ca-nhan/me', [])->assertUnauthorized();
    }

    public function test_avatar_persists_without_overwriting_photos_or_other_account(): void
    {
        $owner = $this->account('owner');
        $other = $this->account('other');
        Avatar::create(['idTaiKhoan' => $owner->idTaiKhoan, 'AnhAvatar' => 'old.jpg', 'AnhBia' => 'cover.jpg']);
        $this->actingAs($owner, 'tai_khoan')->putJson('/api/avatar-animation/me', ['MaAvatarAnimation' => 'meo', 'idTaiKhoan' => $other->idTaiKhoan])
            ->assertOk()->assertJsonPath('data.ma_avatar', 'meo');
        $this->getJson('/api/cam-xuc-ca-nhan?ids[]='.$owner->idTaiKhoan)->assertJsonPath('data.0.ma_avatar', 'meo');
        $this->assertDatabaseHas('avatars', ['idTaiKhoan' => $owner->idTaiKhoan, 'AnhAvatar' => 'old.jpg', 'AnhBia' => 'cover.jpg', 'MaAvatarAnimation' => 'meo']);
        $this->assertDatabaseMissing('avatars', ['idTaiKhoan' => $other->idTaiKhoan]);
        $this->putJson('/api/avatar-animation/me', ['MaAvatarAnimation' => null])->assertOk()->assertJsonPath('data.ma_avatar', null);
    }

    public function test_emotion_history_and_privacy_are_enforced(): void
    {
        $this->travelTo(now()->startOfSecond());
        $owner = $this->account('owner');
        $friend = $this->account('friend');
        $other = $this->account('other');
        BanBe::create(['idTaiKhoan1' => $friend->idTaiKhoan, 'idTaiKhoan2' => $owner->idTaiKhoan]);
        $vui = CamXuc::where('TenCamXuc', 'Vui')->firstOrFail()->idCamXuc;
        $buon = CamXuc::where('TenCamXuc', 'Buon')->firstOrFail()->idCamXuc;
        $url = '/api/cam-xuc-ca-nhan?ids[]='.$owner->idTaiKhoan;
        $this->actingAs($owner, 'tai_khoan')->putJson('/api/cam-xuc-ca-nhan/me', ['idCamXuc' => $vui, 'CheDoHienThi' => 'Ban_Be', 'idTaiKhoan' => $other->idTaiKhoan])
            ->assertOk()->assertJsonPath('data.cam_xuc.ma', 'vui')->assertJsonPath('data.cam_xuc.thoi_gian', now()->toISOString());
        $this->actingAs($friend, 'tai_khoan')->getJson($url)->assertJsonPath('data.0.cam_xuc.ma', 'vui');
        $this->actingAs($other, 'tai_khoan')->getJson($url)->assertJsonPath('data.0.cam_xuc', null);
        $this->actingAs($owner, 'tai_khoan')->putJson('/api/cam-xuc-ca-nhan/me', ['idCamXuc' => $buon, 'CheDoHienThi' => 'Chi_Minh_Toi'])->assertOk();
        $this->actingAs($friend, 'tai_khoan')->getJson($url)->assertJsonPath('data.0.cam_xuc', null);
        $this->assertSame(1, TrangThaiCamXuc::where('idTaiKhoan', $owner->idTaiKhoan)->where('TrangThai', 'Hien_Tai')->count());
        $this->assertDatabaseHas('trang_thai_cam_xucs', ['idTaiKhoan' => $owner->idTaiKhoan, 'idCamXuc' => $vui, 'TrangThai' => 'Da_Cu']);
        $this->assertDatabaseMissing('trang_thai_cam_xucs', ['idTaiKhoan' => $other->idTaiKhoan]);
    }

    public function test_rejects_invalid_and_inactive_choices_and_locked_account(): void
    {
        $owner = $this->account('owner');
        $this->actingAs($owner, 'tai_khoan');
        $this->getJson('/api/avatar-animation')->assertJsonCount(4, 'data');
        $this->getJson('/api/cam-xuc-ca-nhan/danh-muc')->assertJsonCount(6, 'data');
        $this->putJson('/api/avatar-animation/me', ['MaAvatarAnimation' => 'https://evil.test/a.json'])->assertUnprocessable();
        $this->putJson('/api/cam-xuc-ca-nhan/me', [])->assertUnprocessable();
        $mood = CamXuc::where('TenCamXuc', 'Vui')->firstOrFail();
        $mood->update(['TrangThai' => 'Ngung_Su_Dung']);
        $this->putJson('/api/cam-xuc-ca-nhan/me', ['idCamXuc' => $mood->idCamXuc, 'CheDoHienThi' => 'Ban_Be'])->assertUnprocessable();
        $owner->update(['TrangThaiTaiKhoan' => 'Bi_Khoa']);
        $this->putJson('/api/avatar-animation/me', ['MaAvatarAnimation' => 'gau'])->assertForbidden();
        $this->assertDatabaseCount('trang_thai_cam_xucs', 0);
    }
}
