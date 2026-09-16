<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\TuongTacPet;
use App\Policies\PetPolicy;
use App\Services\DichVuPet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TuongTacPetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(Request $request, Pet $pet, DichVuPet $service)
    {
        abort_unless((new PetPolicy)->update($request->user('tai_khoan'), $pet), 404);
        $data = $request->validate(['MaHanhDong' => ['required', Rule::in(array_keys(config('pet.hanh_dong')))]]);
        $service->tuongTac($request->user('tai_khoan'), $pet, $data['MaHanhDong']);

        return response()->json(['data' => $service->duLieu($pet->fresh())]);
    }

    /**
     * Display the specified resource.
     */
    public function show(TuongTacPet $tuongTacPet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TuongTacPet $tuongTacPet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TuongTacPet $tuongTacPet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TuongTacPet $tuongTacPet)
    {
        //
    }
}
