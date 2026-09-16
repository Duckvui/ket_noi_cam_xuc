<?php

namespace App\Services;

use App\Events\TinNhanDaGui;
use App\Models\CuocTroChuyen;
use App\Models\TaiKhoan;
use App\Models\ThanhVienCuocTroChuyen;
use App\Models\TinNhan;
use Illuminate\Support\Facades\DB;

class DichVuChat
{
    public function members(int $conversation, int $user): array
    {
        abort_unless(CuocTroChuyen::whereKey($conversation)->where('TrangThai', 'Dang_Hoat_Dong')->exists(), 404);
        $ids = ThanhVienCuocTroChuyen::where('idCuocTroChuyen', $conversation)->where('TrangThai', 'Dang_Tham_Gia')->pluck('idTaiKhoan')->map(fn ($id) => (int) $id)->all();
        abort_unless(in_array($user, $ids, true), 403);
        return $ids;
    }

    public function start(TaiKhoan $user, int $other): CuocTroChuyen
    {
        return DB::transaction(function () use ($user, $other) {
            $relations = app(QuanHeNguoiDung::class);
            $relations->lockPair($user->idTaiKhoan, $other);
            $relations->ensureAllowed($user->idTaiKhoan, $other);
            $conversation = CuocTroChuyen::where('LoaiCuocTroChuyen', 'Ca_Nhan')->where('TrangThai', 'Dang_Hoat_Dong')
                ->whereHas('thanhViens', fn ($q) => $q->where('idTaiKhoan', $user->idTaiKhoan)->where('TrangThai', 'Dang_Tham_Gia'))
                ->whereHas('thanhViens', fn ($q) => $q->where('idTaiKhoan', $other)->where('TrangThai', 'Dang_Tham_Gia'))
                ->whereHas('thanhViens', fn ($q) => $q->where('TrangThai', 'Dang_Tham_Gia'), '=', 2)->orderBy('idCuocTroChuyen')->first();
            if ($conversation) return $conversation;
            $conversation = CuocTroChuyen::create(['LoaiCuocTroChuyen' => 'Ca_Nhan', 'NgayTao' => now(), 'NgayCapNhat' => now(), 'TrangThai' => 'Dang_Hoat_Dong']);
            foreach ([$user->idTaiKhoan, $other] as $id) {
                ThanhVienCuocTroChuyen::create(['idCuocTroChuyen' => $conversation->idCuocTroChuyen, 'idTaiKhoan' => $id, 'NgayThamGia' => now(), 'TrangThai' => 'Dang_Tham_Gia']);
            }
            return $conversation;
        }, 3);
    }

    public function send(TaiKhoan $user, int $conversation, ?string $content, ?string $path = null, ?int $story = null): TinNhan
    {
        return DB::transaction(function () use ($user, $conversation, $content, $path, $story) {
            abort_unless($user->TrangThaiTaiKhoan === 'Hoat_Dong', 403);
            $ids = $this->members($conversation, $user->idTaiKhoan);
            TaiKhoan::whereIn('idTaiKhoan', $ids)->orderBy('idTaiKhoan')->lockForUpdate()->get();
            foreach ($ids as $id) if ($id !== $user->idTaiKhoan) app(QuanHeNguoiDung::class)->ensureAllowed($user->idTaiKhoan, $id);
            $content = trim($content ?? '');
            abort_if($content === '' && ! $path, 422, 'Nhập nội dung hoặc chọn ảnh.');
            $message = TinNhan::create(['idCuocTroChuyen' => $conversation, 'idNguoiGui' => $user->idTaiKhoan,
                'NoiDung' => $content ?: null, 'DuongDanTep' => $path, 'idTin' => $story,
                'LoaiTinNhan' => $path ? 'Anh' : 'Van_Ban', 'ThoiGianGui' => now(), 'TrangThaiTinNhan' => 'Da_Gui']);
            CuocTroChuyen::whereKey($conversation)->update(['NgayCapNhat' => now()]);
            if (count($ids) === 2 && CuocTroChuyen::whereKey($conversation)->where('LoaiCuocTroChuyen', 'Ca_Nhan')->exists()) {
                $other = array_values(array_diff($ids, [$user->idTaiKhoan]))[0];
                app(PetStreakService::class)->fromChat($user, $other);
            }
            DB::afterCommit(function () use ($conversation, $ids): void {
                try {
                    TinNhanDaGui::dispatch($conversation, $ids);
                } catch (\Throwable $error) {
                    // A websocket/queue outage must not turn a persisted message into a failed send.
                    report($error);
                }
            });
            return $message;
        }, 3);
    }

    public function data(TinNhan $message): array
    {
        return ['id' => $message->idTinNhan, 'content' => $message->NoiDung, 'sent_at' => $message->ThoiGianGui,
            'sender_id' => $message->idNguoiGui, 'idTin' => $message->idTin, 'type' => $message->LoaiTinNhan,
            'image_url' => $message->DuongDanTep ? route('chat.media', $message->idTinNhan, false) : null];
    }
}
