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
        return app(\App\Services\QuanHeNguoiDung::class)->person($account, auth('tai_khoan')->id());
    }
    /**
     * Show the form for creating a new resource.
     */
    public function index(Request $request)
    {
        $id = $request->user('tai_khoan')->idTaiKhoan;
        $blocked = app(\App\Services\QuanHeNguoiDung::class)->blockedIds($id);
        $friendIds = BanBe::where('TrangThai', 'Dang_La_Ban')
            ->where(fn ($query) => $query->where('idTaiKhoan1', $id)->orWhere('idTaiKhoan2', $id))
            ->get()->map(fn ($friend) => $friend->idTaiKhoan1 === $id ? $friend->idTaiKhoan2 : $friend->idTaiKhoan1);
        $sentIds = GuiKetBan::where('idGuiKetBan', $id)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->pluck('idDuocKetBan');
        $received = GuiKetBan::with('nguoiGui.thongTinCaNhan')->where('idDuocKetBan', $id)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->latest('NgayTao')->get();

        return response()->json(['data' => [
            'friend_count' => $friendIds->count(),
            'friends' => TaiKhoan::whereIn('idTaiKhoan', $friendIds)->whereNotIn('idTaiKhoan', $blocked)->with('thongTinCaNhan')->get()->map(fn ($u) => $this->accountData($u)),
            'requests' => $received->map(fn ($invite) => ['idKetBan' => $invite->idKetBan, 'nguoi_gui' => $this->accountData($invite->nguoiGui)]),
            'suggestions' => TaiKhoan::with('thongTinCaNhan')->where('idTaiKhoan', '!=', $id)->where('TrangThaiTaiKhoan', 'Hoat_Dong')
                ->whereNotIn('idTaiKhoan', $blocked)->whereNotIn('idTaiKhoan', $received->pluck('idGuiKetBan'))->whereNotIn('idTaiKhoan', $friendIds)->whereNotIn('idTaiKhoan', $sentIds)->limit(8)->get()->map(fn ($account) => $this->accountData($account)),
        ]]);
    }

    public function search(Request $request)
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:1', 'max:100']]);
        $id = $request->user('tai_khoan')->idTaiKhoan;
        $relations = app(\App\Services\QuanHeNguoiDung::class);
        $words = preg_split('/\s+/u', trim($data['q']));
        $accounts = TaiKhoan::with(['thongTinCaNhan', 'avatarHienTai'])->where('idTaiKhoan', '!=', $id)
            ->whereNotIn('idTaiKhoan', $relations->blockedIds($id))->where('TrangThaiTaiKhoan', 'Hoat_Dong');
        foreach ($words as $word) {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word).'%';
            $accounts->where(fn ($q) => $q->where('TaiKhoan', 'like', $term)->orWhereHas('thongTinCaNhan', fn ($p) => $p->where('Ho', 'like', $term)->orWhere('Ten', 'like', $term)));
        }
        return response()->json(['data' => $accounts->orderBy('TaiKhoan')->limit(20)->get()->map(fn ($u) => $relations->person($u, $id))]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate(['idTaiKhoan' => ['required', 'integer', 'exists:tai_khoans,idTaiKhoan']]);
        $invite = app(\App\Services\QuanHeNguoiDung::class)->request($request->user('tai_khoan')->idTaiKhoan, (int) $data['idTaiKhoan']);
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
        $data = $request->validate(['action' => ['required', Rule::in(['chap_nhan', 'tu_choi'])]]);
        app(\App\Services\QuanHeNguoiDung::class)->respond($request->user('tai_khoan')->idTaiKhoan, $guiKetBan, $data['action']);
        return response()->json(['message' => 'Đã cập nhật lời mời.']);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GuiKetBan $guiKetBan)
    {
        //
    }
}
