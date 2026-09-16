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

        return ['id' => $account->idTaiKhoan, 'name' => trim(($account->thongTinCaNhan?->Ho ?? '').' '.($account->thongTinCaNhan?->Ten ?? '')) ?: $account->TaiKhoan, 'avatar' => $account->avatarHienTai?->AnhAvatar];
    }

    private function message(TinNhan $message): array
    {
        return ['id' => $message->idTinNhan, 'content' => $message->NoiDung, 'sent_at' => $message->ThoiGianGui, 'sender_id' => $message->idNguoiGui, 'idTin' => $message->idTin];
    }

    public function index(Request $request)
    {
        $me = $request->user('tai_khoan');
        $ids = ThanhVienCuocTroChuyen::where('idTaiKhoan', $me->idTaiKhoan)->where('TrangThai', 'Dang_Tham_Gia')->pluck('idCuocTroChuyen');
        $items = CuocTroChuyen::whereIn('idCuocTroChuyen', $ids)->where('TrangThai', 'Dang_Hoat_Dong')->with(['thanhViens.taiKhoan.thongTinCaNhan', 'tinNhans' => fn ($q) => $q->latest('ThoiGianGui')->limit(1)])->get()
            ->map(function (CuocTroChuyen $conversation) use ($me) {
                $other = $conversation->thanhViens->first(fn ($member) => $member->idTaiKhoan !== $me->idTaiKhoan)?->taiKhoan;
                $last = $conversation->tinNhans->first();

                return ['id' => $conversation->idCuocTroChuyen, 'person' => $other ? $this->person($other) : ['id' => $me->idTaiKhoan, 'name' => 'Nhóm chat'], 'last_message' => $last?->NoiDung ?: ($last?->DuongDanTep ? '📷 Hình ảnh' : null), 'updated_at' => $last?->ThoiGianGui ?? $conversation->NgayCapNhat];
            })->sortByDesc('updated_at')->values();

        return response()->json(['data' => $items]);
    }

    public function start(Request $request)
    {
        $data = $request->validate(['recipient_id' => ['required', 'integer', 'exists:tai_khoans,idTaiKhoan']]);
        $conversation = app(\App\Services\DichVuChat::class)->start($request->user('tai_khoan'), (int) $data['recipient_id']);
        return response()->json(['data' => ['id' => $conversation->idCuocTroChuyen]]);
    }
    public function messages(Request $request, int $conversation)
    {
        $service = app(\App\Services\DichVuChat::class);
        $service->members($conversation, $request->user('tai_khoan')->idTaiKhoan);
        $data = $request->validate(['before' => ['nullable', 'integer', 'min:1']]);
        $query = TinNhan::where('idCuocTroChuyen', $conversation)->whereNull('NgayXoa');
        if (!empty($data['before'])) $query->where('idTinNhan', '<', $data['before']);
        $messages = $query->orderByDesc('idTinNhan')->limit(100)->get()->reverse()->values();
        return response()->json(['data' => $messages->map(fn ($message) => $service->data($message)), 'has_more' => $messages->count() === 100]);
    }

    public function send(Request $request, int $conversation)
    {
        $service = app(\App\Services\DichVuChat::class);
        $service->members($conversation, $request->user('tai_khoan')->idTaiKhoan);
        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:2000', 'required_without:image'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:content'],
        ], ['content.required_without' => 'Nhập nội dung hoặc chọn ảnh.', 'image.required_without' => 'Nhập nội dung hoặc chọn ảnh.', 'image.max' => 'Ảnh tối đa 5 MB.', 'image.mimes' => 'Chỉ hỗ trợ JPG, PNG, WebP.']);
        $path = null;
        try {
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('chat', 'local');
                if (!$path) throw new \RuntimeException('Không lưu được ảnh.');
            }
            $message = $service->send($request->user('tai_khoan'), $conversation, $data['content'] ?? null, $path);
        } catch (\Throwable $error) {
            if ($path && ! TinNhan::where('DuongDanTep', $path)->exists()) \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
            throw $error;
        }
        return response()->json(['data' => $service->data($message)], 201);
    }

    public function media(Request $request, TinNhan $tinNhan)
    {
        app(\App\Services\DichVuChat::class)->members($tinNhan->idCuocTroChuyen, $request->user('tai_khoan')->idTaiKhoan);
        abort_if($tinNhan->NgayXoa || !$tinNhan->DuongDanTep, 404);
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($disk->exists($tinNhan->DuongDanTep), 404);
        return response()->file($disk->path($tinNhan->DuongDanTep), ['Cache-Control' => 'private, no-store']);
    }
}
