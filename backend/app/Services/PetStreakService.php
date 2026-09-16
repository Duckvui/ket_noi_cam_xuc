<?php

namespace App\Services;

use App\Events\PetDaThayDoi;
use App\Models\Pet;
use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;

class PetStreakService
{
    public function current(Pet $pet): int
    {
        $yesterday = now(config('pet.timezone'))->subDay()->toDateString();
        return $pet->last_activity_date && $pet->last_activity_date->toDateString() >= $yesterday ? (int) $pet->current_streak : 0;
    }

    public function recordActivity(Pet $pet, TaiKhoan $user): void
    {
        DB::transaction(function () use ($pet, $user) {
            $pet = Pet::whereKey($pet->idPet)->lockForUpdate()->firstOrFail();
            abort_unless($pet->idTaiKhoan2 && in_array($user->idTaiKhoan, [$pet->idTaiKhoan, $pet->idTaiKhoan2], true), 403);
            $today = now(config('pet.timezone'))->toDateString();
            $last = $pet->last_activity_date?->toDateString();
            if ($last && $last >= $today) return;
            $streak = $last === now(config('pet.timezone'))->subDay()->toDateString() ? $pet->current_streak + 1 : 1;
            $pet->forceFill(['current_streak' => $streak, 'longest_streak' => max($streak, $pet->longest_streak), 'last_activity_date' => $today, 'NgayCapNhat' => now()])->save();
            PetDaThayDoi::dispatch($pet->idPet);
        }, 3);
    }

    public function fromChat(TaiKhoan $user, int $other): void
    {
        $pet = Pet::where('idTaiKhoan', min($user->idTaiKhoan, $other))->where('idTaiKhoan2', max($user->idTaiKhoan, $other))->first();
        if ($pet) $this->recordActivity($pet, $user);
    }
}
