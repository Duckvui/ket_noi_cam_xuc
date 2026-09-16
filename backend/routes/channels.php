<?php

use App\Models\Pet;
use App\Models\TaiKhoan;
use App\Policies\PetPolicy;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('cam-xuc.{id}', function (TaiKhoan $user, $id) {
    // No emotion or visibility is broadcast; authorized API supplies current data.
    return TaiKhoan::whereKey($id)->exists();
}, ['guards' => ['tai_khoan']]);

Broadcast::channel('pet.{id}', function (TaiKhoan $user, $id) {
    $pet = Pet::find($id);

    return $pet && (new PetPolicy)->view($user, $pet);
}, ['guards' => ['tai_khoan']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
