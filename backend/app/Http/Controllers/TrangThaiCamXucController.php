<?php

namespace App\Http\Controllers;

use App\Models\TaiKhoan;
use App\Models\TrangThaiCamXuc;
use App\Policies\CamXucPolicy;
use App\Services\DichVuCamXuc;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrangThaiCamXucController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, DichVuCamXuc $service)
    {
        $data = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['required', 'integer', 'min:1']]);
        $owners = TaiKhoan::with(['avatarHienTai', 'camXucHienTai.camXuc'])->whereIn('idTaiKhoan', $data['ids'])->get();

        return response()->json(['data' => $owners->map(fn ($owner) => $service->hienThi($owner, $request->user('tai_khoan')))]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, DichVuCamXuc $service)
    {
        $owner = $request->user('tai_khoan');
        abort_unless((new CamXucPolicy)->update($owner, $owner), 403);
        $data = $request->validate([
            'idCamXuc' => ['required', 'integer', Rule::exists('cam_xucs', 'idCamXuc')->where('TrangThai', 'Dang_Su_Dung')->whereIn('TenCamXuc', array_keys(config('cam_xuc.cam_xucs')))],
            'CheDoHienThi' => ['required', Rule::in(['Ban_Be', 'Chi_Minh_Toi'])],
        ]);
        $service->capNhat($owner, $data);

        return response()->json(['data' => $service->hienThi($owner->fresh(), $owner)]);
    }

    /**
     * Display the specified resource.
     */
    public function show(TrangThaiCamXuc $trangThaiCamXuc)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TrangThaiCamXuc $trangThaiCamXuc)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrangThaiCamXuc $trangThaiCamXuc)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrangThaiCamXuc $trangThaiCamXuc)
    {
        //
    }
}
