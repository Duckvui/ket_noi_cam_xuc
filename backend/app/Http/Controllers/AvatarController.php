<?php

namespace App\Http\Controllers;

use App\Models\Avatar;
use App\Policies\CamXucPolicy;
use App\Services\DichVuCamXuc;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AvatarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(['data' => collect(config('cam_xuc.avatars'))->map(fn ($ten, $ma) => ['ma' => $ma, 'ten' => $ten])->values()]);
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
        $data = $request->validate(['MaAvatarAnimation' => ['present', 'nullable', Rule::in(array_keys(config('cam_xuc.avatars')))]]);
        $service->doiAvatar($owner, $data['MaAvatarAnimation']);

        return response()->json(['data' => $service->hienThi($owner->fresh(), $owner)]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Avatar $avatar)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Avatar $avatar)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Avatar $avatar)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Avatar $avatar)
    {
        //
    }
}
