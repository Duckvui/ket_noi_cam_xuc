<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Policies\PetPolicy;
use App\Services\DichVuPet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, DichVuPet $service)
    {
        return response()->json(['data' => $service->cuaToi($request->user('tai_khoan'))->with(['taiKhoan.thongTinCaNhan', 'taiKhoan2.thongTinCaNhan', 'camXucHienTai'])->get()->map(fn ($pet) => $service->duLieu($pet))]);
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Pet $pet, DichVuPet $service)
    {
        abort_unless((new PetPolicy)->view($request->user('tai_khoan'), $pet), 404);

        return response()->json(['data' => [...$service->duLieu($pet), 'lich_su' => $service->lichSu($pet)]]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pet $pet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pet $pet, DichVuPet $service)
    {
        abort_unless((new PetPolicy)->update($request->user('tai_khoan'), $pet), 404);
        $data = $request->validate(['TenPet' => ['required', 'string', 'max:50'], 'LoaiPet' => ['required', Rule::in(array_keys(config('cam_xuc.avatars')))]]);
        $service->doiPet($request->user('tai_khoan'), $pet, $data);

        return response()->json(['data' => $service->duLieu($pet->fresh())]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pet $pet)
    {
        //
    }
}
