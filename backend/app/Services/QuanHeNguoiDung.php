<?php

namespace App\Services;

use App\Models\BanBe;
use App\Models\GuiKetBan;
use App\Models\TaiKhoan;
use App\Models\TheoDoi;
use Illuminate\Support\Facades\DB;

class QuanHeNguoiDung
{
    // All pair mutations acquire the same account locks, in ascending order.
    public function lockPair(int $a, int $b): void
    {
        abort_if($a === $b, 422, 'Không thể thực hiện với chính mình.');
        $accounts = TaiKhoan::whereIn('idTaiKhoan', [$a, $b])->orderBy('idTaiKhoan')->lockForUpdate()->get();
        abort_unless($accounts->count() === 2 && $accounts->every(fn ($u) => $u->TrangThaiTaiKhoan === 'Hoat_Dong'), 403, 'Tài khoản không hoạt động.');
    }

    public function blockedIds(int $id): array
    {
        return DB::table('chan_nguoi_dungs')->where('idNguoiChan', $id)->orWhere('idBiChan', $id)->get()
            ->map(fn ($r) => (int) ($r->idNguoiChan == $id ? $r->idBiChan : $r->idNguoiChan))->unique()->values()->all();
    }

    public function blocked(int $a, int $b): bool
    {
        return DB::table('chan_nguoi_dungs')->where(fn ($q) => $q->where('idNguoiChan', $a)->where('idBiChan', $b))
            ->orWhere(fn ($q) => $q->where('idNguoiChan', $b)->where('idBiChan', $a))->exists();
    }

    public function ensureAllowed(int $a, int $b): void
    {
        abort_if($this->blocked($a, $b), 403, 'Không thể tương tác vì có quan hệ chặn.');
    }

    public function friends(int $a, int $b)
    {
        return BanBe::where(fn ($q) => $q->where(fn ($q) => $q->where('idTaiKhoan1', $a)->where('idTaiKhoan2', $b))
            ->orWhere(fn ($q) => $q->where('idTaiKhoan1', $b)->where('idTaiKhoan2', $a)));
    }

    public function status(int $a, int $b): array
    {
        $sent = GuiKetBan::where('idGuiKetBan', $a)->where('idDuocKetBan', $b)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->first();
        $received = GuiKetBan::where('idGuiKetBan', $b)->where('idDuocKetBan', $a)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->first();
        $friend = $this->friends($a, $b)->where('TrangThai', 'Dang_La_Ban')->exists();
        $blocked = $this->blocked($a, $b);
        return [
            'status' => $blocked ? 'bi_chan' : ($friend ? 'ban_be' : ($received ? ['loi_moi_den', $received->idKetBan] : ($sent ? 'da_gui' : 'co_the_ket_ban'))),
            'la_ban' => $friend, 'loi_moi_id' => $received?->idKetBan ?? $sent?->idKetBan,
            'dang_theo_doi' => TheoDoi::where('idTaiKhoan', $a)->where('idTaiKhoanTheoDoi', $b)->where('TrangThai', true)->exists(),
            'da_chan' => DB::table('chan_nguoi_dungs')->where('idNguoiChan', $a)->where('idBiChan', $b)->exists(),
            'bi_chan' => $blocked, 'is_self' => $a === $b,
        ];
    }

    public function person(TaiKhoan $account, int $viewer): array
    {
        $account->loadMissing('thongTinCaNhan', 'avatarHienTai');
        return ['id' => $account->idTaiKhoan, 'tai_khoan' => $account->TaiKhoan,
            'ten_hien_thi' => trim(($account->thongTinCaNhan?->Ho ?? '').' '.($account->thongTinCaNhan?->Ten ?? '')) ?: $account->TaiKhoan,
            'avatar' => $account->avatarHienTai?->AnhAvatar, ...$this->status($viewer, $account->idTaiKhoan)];
    }

    public function request(int $a, int $b): GuiKetBan
    {
        return DB::transaction(function () use ($a, $b) {
            $this->lockPair($a, $b);
            $this->ensureAllowed($a, $b);
            abort_if($this->friends($a, $b)->where('TrangThai', 'Dang_La_Ban')->exists(), 422, 'Hai người đã là bạn bè.');
            abort_if(GuiKetBan::where('idGuiKetBan', $b)->where('idDuocKetBan', $a)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->exists(), 422, 'Người này đã gửi lời mời cho bạn.');
            return GuiKetBan::updateOrCreate(['idGuiKetBan' => $a, 'idDuocKetBan' => $b], ['TrangThaiLoiMoi' => 'Cho_Xac_Nhan', 'NgayTao' => now()]);
        }, 3);
    }

    public function respond(int $user, GuiKetBan $invite, string $action): void
    {
        DB::transaction(function () use ($user, $invite, $action) {
            $this->lockPair($invite->idGuiKetBan, $invite->idDuocKetBan);
            $invite->refresh();
            abort_unless($invite->idDuocKetBan === $user && $invite->TrangThaiLoiMoi === 'Cho_Xac_Nhan', 403);
            $this->ensureAllowed($user, $invite->idGuiKetBan);
            $invite->update(['TrangThaiLoiMoi' => $action === 'chap_nhan' ? 'Da_Chap_Nhan' : 'Da_Tu_Choi']);
            if ($action === 'chap_nhan') {
                $old = $this->friends($user, $invite->idGuiKetBan)->first();
                if ($old) $old->update(['TrangThai' => 'Dang_La_Ban']);
                else BanBe::create(['idTaiKhoan1' => min($user, $invite->idGuiKetBan), 'idTaiKhoan2' => max($user, $invite->idGuiKetBan), 'TrangThai' => 'Dang_La_Ban', 'NgayTao' => now()]);
            }
        }, 3);
    }

    public function change(int $a, int $b, string $action): void
    {
        DB::transaction(function () use ($a, $b, $action) {
            $this->lockPair($a, $b);
            if ($action === 'block') {
                DB::table('chan_nguoi_dungs')->updateOrInsert(['idNguoiChan' => $a, 'idBiChan' => $b], ['NgayTao' => now()]);
                $this->friends($a, $b)->update(['TrangThai' => 'Da_Huy']);
                TheoDoi::where(fn ($q) => $q->where('idTaiKhoan', $a)->where('idTaiKhoanTheoDoi', $b))->orWhere(fn ($q) => $q->where('idTaiKhoan', $b)->where('idTaiKhoanTheoDoi', $a))->update(['TrangThai' => false]);
                GuiKetBan::where(fn ($q) => $q->where('idGuiKetBan', $a)->where('idDuocKetBan', $b))->orWhere(fn ($q) => $q->where('idGuiKetBan', $b)->where('idDuocKetBan', $a))->update(['TrangThaiLoiMoi' => 'Da_Tu_Choi']);
            } elseif ($action === 'unblock') {
                DB::table('chan_nguoi_dungs')->where('idNguoiChan', $a)->where('idBiChan', $b)->delete();
            } elseif ($action === 'unfriend') {
                $this->friends($a, $b)->update(['TrangThai' => 'Da_Huy']);
            } elseif ($action === 'cancel') {
                GuiKetBan::where('idGuiKetBan', $a)->where('idDuocKetBan', $b)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->update(['TrangThaiLoiMoi' => 'Da_Tu_Choi']);
            } else {
                $this->ensureAllowed($a, $b);
                TheoDoi::updateOrCreate(['idTaiKhoan' => $a, 'idTaiKhoanTheoDoi' => $b], ['TrangThai' => $action === 'follow', 'NgayTao' => now()]);
            }
        }, 3);
    }
}
