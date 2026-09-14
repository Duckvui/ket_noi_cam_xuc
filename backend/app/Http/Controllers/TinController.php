<?php

namespace App\Http\Controllers;

use App\Models\BanBe;
use App\Models\Tin;
use App\Models\LuotXemTin;
use App\Models\TuongTacTin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TinController extends Controller
{
    private function visible(Request $request): Builder
    {
        $id = $request->user('tai_khoan')->idTaiKhoan;
        $friends = BanBe::where('TrangThai', 'Dang_La_Ban')
            ->where(fn ($q) => $q->where('idTaiKhoan1', $id)->orWhere('idTaiKhoan2', $id))
            ->get()->map(fn ($friend) => $friend->idTaiKhoan1 == $id ? $friend->idTaiKhoan2 : $friend->idTaiKhoan1);

        return Tin::where('TrangThai', 'Binh_Thuong')->where('ThoiGianHetHan', '>', now())
            ->where(fn ($q) => $q->where('idTaiKhoan', $id)->orWhere('CheDoHienThi', 'Cong_Khai')
                ->orWhere(fn ($q) => $q->where('CheDoHienThi', 'Ban_Be')->whereIn('idTaiKhoan', $friends)));
    }

    private function data(Tin $tin, ?int $viewerId = null): array
    {
        $tin->loadMissing('taiKhoan.thongTinCaNhan');
        $profile = $tin->taiKhoan->thongTinCaNhan;

        return [
            'idTin' => $tin->idTin,
            'NoiDung' => $tin->NoiDung,
            'LoaiTin' => $tin->LoaiTin,
            'CheDoHienThi' => $tin->CheDoHienThi,
            'ThoiGianDang' => $tin->ThoiGianDang,
            'ThoiGianHetHan' => $tin->ThoiGianHetHan,
            'media_url' => $tin->DuongDanMedia ? route('tins.media', $tin->idTin, false) : null,
            'ten_hien_thi' => trim(($profile?->Ho ?? '').' '.($profile?->Ten ?? '')) ?: $tin->taiKhoan->TaiKhoan,
            'is_owner' => $viewerId === $tin->idTaiKhoan,
            'views_count' => $tin->luotXems()->count(),
            'reactions_count' => $tin->tuongTacs()->count(),
            'my_reaction' => $viewerId ? $tin->tuongTacs()->where('idTaiKhoan', $viewerId)->value('LoaiTuongTac') : null,
            'viewers' => $viewerId === $tin->idTaiKhoan ? $tin->luotXems()->with('taiKhoan.thongTinCaNhan')->latest('ThoiGianXem')->get()->map(fn ($view) => [
                'id' => $view->idTaiKhoan,
                'name' => trim(($view->taiKhoan->thongTinCaNhan?->Ho ?? '').' '.($view->taiKhoan->thongTinCaNhan?->Ten ?? '')) ?: $view->taiKhoan->TaiKhoan,
            ]) : [],
        ];
    }

    public function index(Request $request)
    {
        return response()->json(['data' => $this->visible($request)->with('taiKhoan.thongTinCaNhan')
            ->orderByDesc('ThoiGianDang')->orderByDesc('idTin')->limit(100)->get()->map(fn ($tin) => $this->data($tin, $request->user('tai_khoan')->idTaiKhoan))]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user('tai_khoan')->TrangThaiTaiKhoan === 'Hoat_Dong', 403);
        $data = $request->validate([
            'LoaiTin' => ['required', Rule::in(['ANH', 'VIDEO', 'VAN_BAN'])],
            'NoiDung' => ['required_if:LoaiTin,VAN_BAN', 'nullable', 'string', 'max:2000'],
            'CheDoHienThi' => ['required', Rule::in(['Cong_Khai', 'Ban_Be', 'Chi_Minh_Toi'])],
            'media' => $request->input('LoaiTin') === 'VAN_BAN' ? ['prohibited'] : [
                'required', 'file',
                $request->input('LoaiTin') === 'ANH' ? 'mimes:jpg,jpeg,png,webp,gif' : 'mimes:mp4,webm',
                $request->input('LoaiTin') === 'ANH' ? 'max:10240' : 'max:51200',
            ],
        ], [
            'NoiDung.required_if' => 'Vui lòng nhập nội dung tin.',
            'media.required' => 'Vui lòng chọn ảnh hoặc video.',
            'media.mimes' => 'Định dạng tệp không phù hợp với loại tin.',
            'media.max' => 'Ảnh tối đa 10 MB, video tối đa 50 MB.',
        ]);
        $path = null;
        try {
            if ($request->hasFile('media')) {
                $path = $request->file('media')->store('tins', 'local');
                if (! $path) {
                    throw new \RuntimeException('Không thể lưu tệp tin.');
                }
            }
            $tin = Tin::create([
                'idTaiKhoan' => $request->user('tai_khoan')->idTaiKhoan,
                'NoiDung' => $data['NoiDung'] ?? null,
                'LoaiTin' => $data['LoaiTin'],
                'CheDoHienThi' => $data['CheDoHienThi'],
                'DuongDanMedia' => $path,
                'ThoiGianDang' => now(),
                'ThoiGianHetHan' => now()->addDay(),
                'TrangThai' => 'Binh_Thuong',
            ]);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return response()->json(['message' => 'Đăng tin thành công.', 'data' => $this->data($tin, $request->user('tai_khoan')->idTaiKhoan)], 201);
    }

    public function media(Request $request, int $tin)
    {
        $story = $this->visible($request)->findOrFail($tin);
        abort_unless($story->DuongDanMedia && Storage::disk('local')->exists($story->DuongDanMedia), 404);

        return response()->file(Storage::disk('local')->path($story->DuongDanMedia), ['Cache-Control' => 'private, no-store']);
    }

    public function view(Request $request, int $tin)
    {
        $story = $this->visible($request)->findOrFail($tin);
        $viewer = $request->user('tai_khoan');
        if ($story->idTaiKhoan !== $viewer->idTaiKhoan) {
            LuotXemTin::updateOrCreate(['idTin' => $story->idTin, 'idTaiKhoan' => $viewer->idTaiKhoan], ['ThoiGianXem' => now()]);
        }
        return response()->json(['data' => $this->data($story, $viewer->idTaiKhoan)]);
    }

    public function react(Request $request, int $tin)
    {
        $story = $this->visible($request)->findOrFail($tin);
        $data = $request->validate(['type' => ['required', Rule::in(['Thich', 'Yeu_Thich', 'Haha', 'Wow', 'Buon', 'Tuc_Gian'])]]);
        $viewer = $request->user('tai_khoan');
        $reaction = TuongTacTin::where('idTin', $story->idTin)->where('idTaiKhoan', $viewer->idTaiKhoan)->first();
        if ($reaction?->LoaiTuongTac === $data['type']) $reaction->delete();
        else TuongTacTin::updateOrCreate(['idTin' => $story->idTin, 'idTaiKhoan' => $viewer->idTaiKhoan], ['LoaiTuongTac' => $data['type'], 'ThoiGianTuongTac' => now()]);
        return response()->json(['data' => $this->data($story, $viewer->idTaiKhoan)]);
    }
}
