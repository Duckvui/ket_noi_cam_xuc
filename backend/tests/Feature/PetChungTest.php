<?php

namespace Tests\Feature;

use App\Models\BanBe;
use App\Models\CamXuc;
use App\Models\Pet;
use App\Models\TaiKhoan;
use App\Services\QuyDoiDiemPet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetChungTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $name): TaiKhoan
    {
        return TaiKhoan::create(['TaiKhoan' => $name, 'MatKhau' => 'test', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
    }

    private function pair(): array
    {
        $a = $this->account('a');
        $b = $this->account('b');
        BanBe::create(['idTaiKhoan1' => $a->idTaiKhoan, 'idTaiKhoan2' => $b->idTaiKhoan]);

        return [$a, $b];
    }

    private function invite(TaiKhoan $a, TaiKhoan $b): int
    {
        return $this->actingAs($a, 'tai_khoan')->postJson('/api/loi-moi-nuoi-pet', ['idTaiKhoan' => $b->idTaiKhoan, 'TenPet' => 'Mochi', 'LoaiPet' => 'meo'])->assertCreated()->json('data.idLoiMoiPet');
    }

    private function pet(TaiKhoan $a, TaiKhoan $b): int
    {
        $invite = $this->invite($a, $b);

        return $this->actingAs($b, 'tai_khoan')->patchJson('/api/loi-moi-nuoi-pet/'.$invite, ['action' => 'chap_nhan'])->assertOk()->json('data.idPet');
    }

    public function test_invitation_requires_friend_and_recipient_and_creates_exactly_two_members(): void
    {
        $this->getJson('/api/pet-chung')->assertUnauthorized();
        [$a, $b] = $this->pair();
        $c = $this->account('c');
        $this->actingAs($a, 'tai_khoan')->postJson('/api/loi-moi-nuoi-pet', ['idTaiKhoan' => $a->idTaiKhoan, 'TenPet' => 'Pet', 'LoaiPet' => 'meo'])->assertUnprocessable();
        $this->postJson('/api/loi-moi-nuoi-pet', ['idTaiKhoan' => $c->idTaiKhoan, 'TenPet' => 'Pet', 'LoaiPet' => 'meo'])->assertForbidden();
        $invite = $this->invite($a, $b);
        $this->assertDatabaseCount('pets', 0);
        $this->actingAs($b, 'tai_khoan')->postJson('/api/loi-moi-nuoi-pet', ['idTaiKhoan' => $a->idTaiKhoan, 'TenPet' => 'Pet', 'LoaiPet' => 'gau'])->assertUnprocessable();
        $this->actingAs($a, 'tai_khoan')->patchJson('/api/loi-moi-nuoi-pet/'.$invite, ['action' => 'chap_nhan'])->assertNotFound();
        $this->actingAs($c, 'tai_khoan')->patchJson('/api/loi-moi-nuoi-pet/'.$invite, ['action' => 'chap_nhan'])->assertNotFound();
        $this->actingAs($b, 'tai_khoan')->patchJson('/api/loi-moi-nuoi-pet/'.$invite, ['action' => 'chap_nhan'])->assertOk()->assertJsonCount(2, 'data.thanh_vien');
        $this->patchJson('/api/loi-moi-nuoi-pet/'.$invite, ['action' => 'chap_nhan'])->assertUnprocessable();
        $this->assertDatabaseCount('pets', 1);
        $this->assertDatabaseHas('pets', ['idTaiKhoan' => $a->idTaiKhoan, 'idTaiKhoan2' => $b->idTaiKhoan, 'DiemCamXuc' => 50]);
    }

    public function test_rejection_and_lost_friendship_do_not_create_pet(): void
    {
        [$a, $b] = $this->pair();
        $invite = $this->invite($a, $b);
        $this->actingAs($b, 'tai_khoan')->patchJson('/api/loi-moi-nuoi-pet/'.$invite, ['action' => 'tu_choi'])->assertOk();
        $this->assertDatabaseHas('loi_moi_nuoi_pets', ['idLoiMoiPet' => $invite, 'TrangThai' => 'Da_Tu_Choi']);
        $invite = $this->invite($a, $b);
        BanBe::query()->update(['TrangThai' => 'Da_Huy']);
        $this->actingAs($b, 'tai_khoan')->patchJson('/api/loi-moi-nuoi-pet/'.$invite, ['action' => 'chap_nhan'])->assertForbidden();
        $this->assertDatabaseCount('pets', 0);
    }

    public function test_outsider_cannot_view_edit_interact_or_authorize_private_channel(): void
    {
        [$a, $b] = $this->pair();
        $id = $this->pet($a, $b);
        $c = $this->account('c');
        $this->actingAs($c, 'tai_khoan')->getJson('/api/pet-chung')->assertJsonCount(0, 'data');
        $this->getJson('/api/pet-chung/'.$id)->assertNotFound();
        $this->patchJson('/api/pet-chung/'.$id, ['TenPet' => 'Hacked', 'LoaiPet' => 'gau'])->assertNotFound();
        $this->postJson('/api/pet-chung/'.$id.'/tuong-tac', ['MaHanhDong' => 'om'])->assertNotFound();
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');
        $this->postJson('/api/realtime/auth', ['socket_id' => '1.2', 'channel_name' => 'private-pet.'.$id])->assertForbidden();
        $this->actingAs($a, 'tai_khoan')->postJson('/api/realtime/auth', ['socket_id' => '1.2', 'channel_name' => 'private-pet.'.$id])->assertOk();
        $this->assertDatabaseHas('pets', ['idPet' => $id, 'TenPet' => 'Mochi', 'DiemCamXuc' => 50]);
    }

    public function test_emotion_and_interaction_preserve_partner_state_and_enforce_cooldown_and_bounds(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$a, $b] = $this->pair();
        $id = $this->pet($a, $b);
        $mood = CamXuc::where('TenCamXuc', 'Buon')->firstOrFail();
        $this->actingAs($a, 'tai_khoan')->putJson('/api/cam-xuc-ca-nhan/me', ['idCamXuc' => $mood->idCamXuc, 'CheDoHienThi' => 'Ban_Be', 'DiemCamXuc' => 100])->assertOk();
        $this->assertDatabaseHas('pets', ['idPet' => $id, 'DiemCamXuc' => 40, 'TrangThaiPet' => 'buon']);
        $this->assertDatabaseCount('lich_su_cam_xuc_pets', 1);
        $this->assertDatabaseMissing('trang_thai_cam_xucs', ['idTaiKhoan' => $b->idTaiKhoan]);
        $this->actingAs($b, 'tai_khoan')->postJson('/api/pet-chung/'.$id.'/tuong-tac', ['MaHanhDong' => 'om', 'DiemCamXuc' => 100])->assertOk()->assertJsonPath('data.DiemCamXuc', 48);
        $this->postJson('/api/pet-chung/'.$id.'/tuong-tac', ['MaHanhDong' => 'om'])->assertTooManyRequests();
        $this->assertDatabaseCount('tuong_tac_pets', 1);
        $this->getJson('/api/pet-chung/'.$id)->assertJsonCount(2, 'data.lich_su');
        $this->travel(10)->seconds();
        Pet::whereKey($id)->update(['DiemCamXuc' => 99]);
        $this->postJson('/api/pet-chung/'.$id.'/tuong-tac', ['MaHanhDong' => 'choi_cung'])->assertOk()->assertJsonPath('data.DiemCamXuc', 100);
        $this->assertDatabaseHas('tuong_tac_pets', ['idPet' => $id, 'DiemThayDoi' => 1, 'DiemSau' => 100]);
        Pet::whereKey($id)->update(['DiemCamXuc' => 2]);
        $this->actingAs($a, 'tai_khoan')->putJson('/api/cam-xuc-ca-nhan/me', ['idCamXuc' => $mood->idCamXuc, 'CheDoHienThi' => 'Ban_Be'])->assertOk();
        $this->assertDatabaseHas('pets', ['idPet' => $id, 'DiemCamXuc' => 0]);
        $this->assertDatabaseHas('lich_su_cam_xuc_pets', ['idPet' => $id, 'DiemThayDoi' => -2, 'DiemSau' => 0]);
    }

    public function test_score_thresholds(): void
    {
        $service = app(QuyDoiDiemPet::class);
        foreach ([0 => 'rat_buon', 20 => 'rat_buon', 21 => 'buon', 40 => 'buon', 41 => 'binh_thuong', 60 => 'binh_thuong', 61 => 'vui', 80 => 'vui', 81 => 'rat_vui', 100 => 'rat_vui'] as $score => $state) {
            $this->assertSame($state, $service->trangThai($score));
        }
    }
}
