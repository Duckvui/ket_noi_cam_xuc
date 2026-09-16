<?php

namespace Tests\Feature;

use App\Events\TinNhanDaGui;
use App\Models\BaiViet;
use App\Models\CamXuc;
use App\Models\Pet;
use App\Models\TaiKhoan;
use App\Models\Tin;
use App\Models\TinNhan;
use App\Services\PetStreakService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KetNoiChatStreakTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $name): TaiKhoan
    {
        return TaiKhoan::create(['TaiKhoan' => $name, 'MatKhau' => 'test', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
    }

    private function relation(TaiKhoan $actor, TaiKhoan $other, string $action)
    {
        return $this->actingAs($actor, 'tai_khoan')->postJson("/api/nguoi-dung/{$other->idTaiKhoan}/quan-he", ['action' => $action]);
    }

    private function conversation(TaiKhoan $a, TaiKhoan $b): int
    {
        return $this->actingAs($a, 'tai_khoan')->postJson('/api/cuoc-tro-chuyens', ['recipient_id' => $b->idTaiKhoan])->assertOk()->json('data.id');
    }

    public function test_search_profile_and_all_friend_states(): void
    {
        $a = $this->account('an');
        $b = $this->account('Khanhvan123');
        $b->thongTinCaNhan()->create(['Ho' => 'Khánh', 'Ten' => 'Vân']);
        $this->actingAs($a, 'tai_khoan')->getJson('/api/nguoi-dung/tim-kiem?q='.urlencode('Khánh Vân'))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.ten_hien_thi', 'Khánh Vân');
        $this->getJson('/api/nguoi-dung/tim-kiem?q=an')->assertJsonCount(1, 'data');
        $this->getJson('/api/nguoi-dung/'.$b->idTaiKhoan)->assertJsonPath('data.status', 'co_the_ket_ban');
        $this->relation($a, $a, 'request')->assertUnprocessable();
        $this->relation($a, $b, 'request')->assertOk()->assertJsonPath('data.status', 'da_gui');
        $this->relation($a, $b, 'request')->assertOk();
        $this->assertDatabaseCount('gui_ket_bans', 1);
        $this->relation($a, $b, 'cancel')->assertOk()->assertJsonPath('data.status', 'co_the_ket_ban');
        $this->relation($a, $b, 'request')->assertOk();
        $this->relation($b, $a, 'reject')->assertOk();
        $this->relation($a, $b, 'request')->assertOk();
        $this->actingAs($b, 'tai_khoan')->getJson('/api/nguoi-dung/'.$a->idTaiKhoan)->assertJsonPath('data.status.0', 'loi_moi_den');
        $this->relation($b, $a, 'accept')->assertOk()->assertJsonPath('data.la_ban', true);
        $this->assertDatabaseCount('ban_bes', 1);
        $this->relation($b, $a, 'follow')->assertOk()->assertJsonPath('data.dang_theo_doi', true);
        $this->relation($b, $a, 'unfollow')->assertOk()->assertJsonPath('data.dang_theo_doi', false);
        $this->relation($a, $b, 'unfriend')->assertOk()->assertJsonPath('data.la_ban', false);
        $this->relation($a, $a, 'follow')->assertUnprocessable();
    }

    public function test_image_chat_is_persistent_private_and_conversation_is_reused(): void
    {
        Storage::fake('local');
        Event::fake([TinNhanDaGui::class]);
        $a = $this->account('a'); $b = $this->account('b'); $c = $this->account('c');
        $id = $this->conversation($a, $b);
        $this->assertSame($id, $this->conversation($b, $a));
        $this->assertDatabaseCount('cuoc_tro_chuyens', 1);
        $url = "/api/cuoc-tro-chuyens/$id/tin-nhans";
        $this->actingAs($a, 'tai_khoan')->postJson($url, ['content' => 'Xin chào'])->assertCreated();
        $image = $this->postJson($url, ['image' => UploadedFile::fake()->image('photo.png')])->assertCreated()->json('data');
        $this->postJson($url, ['content' => 'Ảnh của tôi', 'image' => UploadedFile::fake()->image('photo.jpg')])->assertCreated()->assertJsonPath('data.content', 'Ảnh của tôi');
        $this->postJson($url, ['content' => '   '])->assertUnprocessable();
        $this->postJson($url, ['image' => UploadedFile::fake()->image('large.png')->size(5121)])->assertUnprocessable();
        $this->postJson($url, ['image' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertUnprocessable();
        Storage::disk('local')->assertExists(TinNhan::find($image['id'])->DuongDanTep);
        $this->actingAs($b, 'tai_khoan')->getJson($url)->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.1.image_url', $image['image_url']);
        $this->get($image['image_url'])->assertOk();
        $this->actingAs($c, 'tai_khoan')->get($image['image_url'])->assertForbidden();
        $this->getJson($url)->assertForbidden();
        $this->postJson($url, ['content' => 'fake'])->assertForbidden();
        Event::assertDispatched(TinNhanDaGui::class, fn ($e) => $e->conversationId === $id && count($e->memberIds) === 2);
    }

    public function test_block_prevents_new_messages_requests_follow_and_content_but_keeps_history(): void
    {
        $a = $this->account('a'); $b = $this->account('b');
        $id = $this->conversation($a, $b);
        $this->postJson("/api/cuoc-tro-chuyens/$id/tin-nhans", ['content' => 'Old message'])->assertCreated();
        $this->relation($a, $b, 'request'); $this->relation($b, $a, 'accept'); $this->relation($a, $b, 'follow');
        $post = BaiViet::create(['idTaiKhoan' => $b->idTaiKhoan, 'NoiDung' => 'Public', 'CheDoHienThi' => 'Cong_Khai', 'TrangThaiBaiViet' => 'Binh_Thuong']);
        $story = Tin::create(['idTaiKhoan' => $b->idTaiKhoan, 'LoaiTin' => 'VAN_BAN', 'NoiDung' => 'Story', 'ThoiGianHetHan' => now()->addDay()]);
        $this->relation($a, $b, 'block')->assertOk()->assertJsonPath('data.da_chan', true)->assertJsonPath('data.la_ban', false)->assertJsonPath('data.dang_theo_doi', false)->assertJsonCount(0, 'data.posts');
        $this->getJson('/api/bai-viets')->assertJsonCount(0, 'data');
        $this->postJson('/api/bai-viets/'.$post->idBaiViet.'/cam-xuc', ['type' => 'Thich'])->assertNotFound();
        $this->postJson('/api/tins/'.$story->idTin.'/tra-loi', ['NoiDung' => 'Blocked reply'])->assertNotFound();
        $this->getJson('/api/nguoi-dung/tim-kiem?q=b')->assertJsonCount(0, 'data');
        foreach ([[$a, $b], [$b, $a]] as [$actor, $target]) {
            $this->relation($actor, $target, 'request')->assertForbidden();
            $this->relation($actor, $target, 'follow')->assertForbidden();
            $this->postJson("/api/cuoc-tro-chuyens/$id/tin-nhans", ['content' => 'Blocked'])->assertForbidden();
        }
        $this->getJson("/api/cuoc-tro-chuyens/$id/tin-nhans")->assertJsonCount(1, 'data');
        $this->relation($a, $b, 'unblock')->assertOk()->assertJsonPath('data.bi_chan', false);
        $this->postJson("/api/cuoc-tro-chuyens/$id/tin-nhans", ['content' => 'Allowed'])->assertCreated();
    }

    public function test_streak_uses_chat_and_interactions_once_per_day_and_survives_gap(): void
    {
        $this->travelTo(Carbon::parse('2026-09-16 12:00:00', 'Asia/Ho_Chi_Minh'));
        $a = $this->account('a'); $b = $this->account('b');
        $pet = Pet::create(['idTaiKhoan' => $a->idTaiKhoan, 'idTaiKhoan2' => $b->idTaiKhoan, 'TenPet' => 'Mochi', 'LoaiPet' => 'meo', 'DiemCamXuc' => 50]);
        $id = $this->conversation($a, $b);
        $url = "/api/cuoc-tro-chuyens/$id/tin-nhans";
        $this->postJson($url, ['content' => 'Day one'])->assertCreated();
        $this->actingAs($b, 'tai_khoan')->postJson($url, ['content' => 'Same day'])->assertCreated();
        $this->assertSame(1, $pet->fresh()->current_streak);
        $this->postJson("/api/pet-chung/{$pet->idPet}/tuong-tac", ['MaHanhDong' => 'om'])->assertOk()->assertJsonPath('data.current_streak', 1);
        $this->travel(1)->days();
        $this->postJson($url, ['content' => 'Day two'])->assertCreated();
        $this->assertSame(2, $pet->fresh()->current_streak);
        $this->travel(1)->days();
        $this->postJson($url, ['content' => 'Day three'])->assertCreated();
        $this->assertSame(3, $pet->fresh()->current_streak);
        $this->travel(2)->days();
        $this->getJson('/api/pet-chung/'.$pet->idPet)->assertOk()->assertJsonPath('data.current_streak', 0)->assertJsonCount(1, 'data.lich_su');
        $this->postJson($url, ['content' => 'Restart'])->assertCreated();
        $this->assertSame(1, $pet->fresh()->current_streak);
        $this->assertSame(3, $pet->fresh()->longest_streak);
        $this->assertDatabaseCount('pets', 1);
        $this->assertDatabaseCount('tuong_tac_pets', 1);
    }

    public function test_streak_boundary_is_local_midnight_and_pet_emotion_is_shared(): void
    {
        $this->travelTo(Carbon::parse('2026-09-16 16:59:00', 'UTC'));
        $a = $this->account('a'); $b = $this->account('b');
        $pet = Pet::create(['idTaiKhoan' => $a->idTaiKhoan, 'idTaiKhoan2' => $b->idTaiKhoan, 'TenPet' => 'Mochi', 'LoaiPet' => 'meo', 'DiemCamXuc' => 50]);
        $service = app(PetStreakService::class);
        $service->recordActivity($pet, $a);
        $this->travel(2)->minutes();
        $service->recordActivity($pet, $b);
        $this->assertSame(2, $pet->fresh()->current_streak);
        $mood = CamXuc::where('TenCamXuc', 'Buon')->firstOrFail();
        $this->actingAs($a, 'tai_khoan')->putJson('/api/cam-xuc-ca-nhan/me', ['idCamXuc' => $mood->idCamXuc, 'CheDoHienThi' => 'Ban_Be'])->assertOk();
        $this->actingAs($b, 'tai_khoan')->getJson('/api/pet-chung/'.$pet->idPet)->assertJsonPath('data.Animation', 'buon');
        $this->postJson('/api/pet-chung/'.$pet->idPet.'/tuong-tac', ['MaHanhDong' => 'an_ui'])->assertOk()->assertJsonPath('data.Animation', 'binh_thuong');
        $this->assertDatabaseHas('tuong_tac_pets', ['CamXucTruoc' => 'Buon', 'CamXucSau' => 'binh_thuong']);
    }
}
