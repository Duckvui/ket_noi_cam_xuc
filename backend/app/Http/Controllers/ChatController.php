<?php

namespace App\Http\Controllers;

use App\Models\CuocTroChuyen;
use App\Models\TaiKhoan;
use App\Models\ThanhVienCuocTroChuyen;
use App\Models\TinNhan;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    private function member(int $conversationId, int $accountId): void
    {
        abort_unless(ThanhVienCuocTroChuyen::where('idCuocTroChuyen', $conversationId)->where('idTaiKhoan', $accountId)->where('TrangThai', 'Dang_Tham_Gia')->exists(), 403);
    }

    private function person(TaiKhoan $account): array
    {
        $account->loadMissing('thongTinCaNhan');
        return ['id' => $account->idTaiKhoan, 'name' => trim(($account->thongTinCaNhan?->Ho ?? '').' '.($account->thongTinCaNhan?->Ten ?? '')) ?: $account->TaiKhoan];
    }

    private function message(TinNhan $message): array
    {
        return ['id' => $message->idTinNhan, 'content' => $message->NoiDung, 'sent_at' => $message->ThoiGianGui, 'sender_id' => $message->idNguoiGui];
    }

    public function index(Request $request)
    {
        $me = $request->user('tai_khoan');
        $ids = ThanhVienCuocTroChuyen::where('idTaiKhoan', $me->idTaiKhoan)->where('TrangThai', 'Dang_Tham_Gia')->pluck('idCuocTroChuyen');
        $items = CuocTroChuyen::whereIn('idCuocTroChuyen', $ids)->where('TrangThai', 'Dang_Hoat_Dong')->with(['thanhViens.taiKhoan.thongTinCaNhan', 'tinNhans' => fn ($q) => $q->latest('ThoiGianGui')->limit(1)])->get()
            ->map(function (CuocTroChuyen $conversation) use ($me) {
                $other = $conversation->thanhViens->first(fn ($member) => $member->idTaiKhoan !== $me->idTaiKhoan)?->taiKhoan;
                $last = $conversation->tinNhans->first();
                return ['id' => $conversation->idCuocTroChuyen, 'person' => $other ? $this->person($other) : ['id' => $me->idTaiKhoan, 'name' => 'Nhóm chat'], 'last_message' => $last?->NoiDung, 'updated_at' => $last?->ThoiGianGui ?? $conversation->NgayCapNhat];
            })->sortByDesc('updated_at')->values();
        return response()->json(['data' => $items]);
    }

    public function start(Request $request)
    {
        $data = $request->validate(['recipient_id' => ['required', 'integer', 'exists:tai_khoans,idTaiKhoan']]);
        $me = $request->user('tai_khoan');
        abort_if($me->idTaiKhoan === (int) $data['recipient_id'], 422, 'Không thể tự nhắn tin cho chính mình.');
        $candidateIds = ThanhVienCuocTroChuyen::where('idTaiKhoan', $me->idTaiKhoan)->where('TrangThai', 'Dang_Tham_Gia')->pluck('idCuocTroChuyen');
        $conversation = CuocTroChuyen::where('LoaiCuocTroChuyen', 'Ca_Nhan')->whereIn('idCuocTroChuyen', $candidateIds)
            ->whereHas('thanhViens', fn ($q) => $q->where('idTaiKhoan', $data['recipient_id'])->where('TrangThai', 'Dang_Tham_Gia'))
            ->whereHas('thanhViens', fn ($q) => $q->where('TrangThai', 'Dang_Tham_Gia'), '=', 2)->first();
        if (! $conversation) {
            $conversation = CuocTroChuyen::create(['LoaiCuocTroChuyen' => 'Ca_Nhan', 'NgayTao' => now(), 'NgayCapNhat' => now(), 'TrangThai' => 'Dang_Hoat_Dong']);
            foreach ([$me->idTaiKhoan, $data['recipient_id']] as $accountId) ThanhVienCuocTroChuyen::create(['idCuocTroChuyen' => $conversation->idCuocTroChuyen, 'idTaiKhoan' => $accountId, 'NgayThamGia' => now(), 'TrangThai' => 'Dang_Tham_Gia']);
        }
        return response()->json(['data' => ['id' => $conversation->idCuocTroChuyen]]);
    }

    public function messages(Request $request, int $conversation)
    {
        $this->member($conversation, $request->user('tai_khoan')->idTaiKhoan);
        return response()->json(['data' => TinNhan::where('idCuocTroChuyen', $conversation)->whereNull('NgayXoa')->oldest('ThoiGianGui')->limit(100)->get()->map(fn ($message) => $this->message($message))]);
    }

    public function send(Request $request, int $conversation)
    {
        $me = $request->user('tai_khoan'); $this->member($conversation, $me->idTaiKhoan);
        $data = $request->validate(['content' => ['required', 'string', 'max:2000']]);
        $message = TinNhan::create(['idCuocTroChuyen' => $conversation, 'idNguoiGui' => $me->idTaiKhoan, 'NoiDung' => trim($data['content']), 'LoaiTinNhan' => 'Van_Ban', 'ThoiGianGui' => now(), 'TrangThaiTinNhan' => 'Da_Gui']);
        CuocTroChuyen::whereKey($conversation)->update(['NgayCapNhat' => now()]);
        return response()->json(['data' => $this->message($message)], 201);
    }
}
