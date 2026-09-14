<?php

namespace App\Http\Controllers;

use App\Models\BanBe;
use App\Models\GuiKetBan;
use App\Models\TaiKhoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GuiKetBanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private function accountData(TaiKhoan $account): array
    {
        $account->loadMissing('thongTinCaNhan');
        $profile = $account->thongTinCaNhan;

        return [
            'id' => $account->idTaiKhoan,
            'ten_hien_thi' => trim(($profile?->Ho ?? '').' '.($profile?->Ten ?? '')) ?: $account->TaiKhoan,
            'tai_khoan' => $account->TaiKhoan,
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function index(Request $request)
    {
        $id = $request->user('tai_khoan')->idTaiKhoan;
        $friendIds = BanBe::where('TrangThai', 'Dang_La_Ban')
            ->where(fn ($query) => $query->where('idTaiKhoan1', $id)->orWhere('idTaiKhoan2', $id))
            ->get()->map(fn ($friend) => $friend->idTaiKhoan1 === $id ? $friend->idTaiKhoan2 : $friend->idTaiKhoan1);
        $sentIds = GuiKetBan::where('idGuiKetBan', $id)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->pluck('idDuocKetBan');
        $received = GuiKetBan::with('nguoiGui.thongTinCaNhan')->where('idDuocKetBan', $id)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->latest('NgayTao')->get();

        return response()->json(['data' => [
            'friend_count' => $friendIds->count(),
            'requests' => $received->map(fn ($invite) => ['idKetBan' => $invite->idKetBan, 'nguoi_gui' => $this->accountData($invite->nguoiGui)]),
            'suggestions' => TaiKhoan::with('thongTinCaNhan')->where('idTaiKhoan', '!=', $id)->where('TrangThaiTaiKhoan', 'Hoat_Dong')
                ->whereNotIn('idTaiKhoan', $friendIds)->whereNotIn('idTaiKhoan', $sentIds)->limit(8)->get()->map(fn ($account) => $this->accountData($account)),
        ]]);
    }

    public function search(Request $request)
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $id = $request->user('tai_khoan')->idTaiKhoan;
        $term = trim($data['q']);
        $accounts = TaiKhoan::with('thongTinCaNhan')->where('idTaiKhoan', '!=', $id)->where('TrangThaiTaiKhoan', 'Hoat_Dong')
            ->where(fn ($query) => $query->where('TaiKhoan', 'like', "%{$term}%")->orWhereHas('thongTinCaNhan', fn ($profile) => $profile->where('Ho', 'like', "%{$term}%")->orWhere('Ten', 'like', "%{$term}%")))
            ->limit(10)->get();

        return response()->json(['data' => $accounts->map(function (TaiKhoan $account) use ($id) {
            $status = 'co_the_ket_ban';
            if (BanBe::where('TrangThai', 'Dang_La_Ban')->where(fn ($query) => $query->where('idTaiKhoan1', $id)->where('idTaiKhoan2', $account->idTaiKhoan)->orWhere('idTaiKhoan1', $account->idTaiKhoan)->where('idTaiKhoan2', $id))->exists()) $status = 'ban_be';
            elseif (GuiKetBan::where('idGuiKetBan', $id)->where('idDuocKetBan', $account->idTaiKhoan)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->exists()) $status = 'da_gui';
            elseif ($invite = GuiKetBan::where('idGuiKetBan', $account->idTaiKhoan)->where('idDuocKetBan', $id)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->first()) $status = ['loi_moi_den', $invite->idKetBan];

            return [...$this->accountData($account), 'status' => $status];
        })]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate(['idTaiKhoan' => ['required', 'integer', Rule::exists('tai_khoans', 'idTaiKhoan')]]);
        $id = $request->user('tai_khoan')->idTaiKhoan;
        abort_if($id === (int) $data['idTaiKhoan'], 422, 'Bạn không thể gửi lời mời cho chính mình.');
        $target = (int) $data['idTaiKhoan'];
        $isFriend = BanBe::where('TrangThai', 'Dang_La_Ban')->where(fn ($query) => $query->where('idTaiKhoan1', $id)->where('idTaiKhoan2', $target)->orWhere('idTaiKhoan1', $target)->where('idTaiKhoan2', $id))->exists();
        abort_if($isFriend, 422, 'Hai người đã là bạn bè.');
        $reverse = GuiKetBan::where('idGuiKetBan', $target)->where('idDuocKetBan', $id)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->exists();
        abort_if($reverse, 422, 'Người này đã gửi lời mời cho bạn. Hãy chấp nhận lời mời đó.');
        $invite = GuiKetBan::updateOrCreate(['idGuiKetBan' => $id, 'idDuocKetBan' => $target], ['TrangThaiLoiMoi' => 'Cho_Xac_Nhan', 'NgayTao' => now()]);

        return response()->json(['message' => 'Đã gửi lời mời kết bạn.', 'data' => ['idKetBan' => $invite->idKetBan]], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(GuiKetBan $guiKetBan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GuiKetBan $guiKetBan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GuiKetBan $guiKetBan)
    {
        abort_unless($guiKetBan->idDuocKetBan === $request->user('tai_khoan')->idTaiKhoan && $guiKetBan->TrangThaiLoiMoi === 'Cho_Xac_Nhan', 403);
        $data = $request->validate(['action' => ['required', Rule::in(['chap_nhan', 'tu_choi'])]]);
        DB::transaction(function () use ($data, $guiKetBan): void {
            $guiKetBan->update(['TrangThaiLoiMoi' => $data['action'] === 'chap_nhan' ? 'Da_Chap_Nhan' : 'Da_Tu_Choi']);
            if ($data['action'] === 'chap_nhan') {
                $first = min($guiKetBan->idGuiKetBan, $guiKetBan->idDuocKetBan);
                $second = max($guiKetBan->idGuiKetBan, $guiKetBan->idDuocKetBan);
                BanBe::updateOrCreate(['idTaiKhoan1' => $first, 'idTaiKhoan2' => $second], ['TrangThai' => 'Dang_La_Ban', 'NgayTao' => now()]);
            }
        });

        return response()->json(['message' => $data['action'] === 'chap_nhan' ? 'Đã trở thành bạn bè.' : 'Đã từ chối lời mời.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GuiKetBan $guiKetBan)
    {
        //
    }
}
