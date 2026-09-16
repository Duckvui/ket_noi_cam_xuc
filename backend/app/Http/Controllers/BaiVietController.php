<?php

namespace App\Http\Controllers;

use App\Events\BinhLuanDaTao;
use App\Models\BaiViet;
use App\Models\BanBe;
use App\Models\BinhLuan;
use App\Models\TuongTacBaiViet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BaiVietController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private function visible(Request $request): Builder
    {
        $id = $request->user('tai_khoan')->idTaiKhoan;
        $friends = BanBe::where('TrangThai', 'Dang_La_Ban')
            ->where(fn ($query) => $query->where('idTaiKhoan1', $id)->orWhere('idTaiKhoan2', $id))
            ->get()->map(fn ($friend) => $friend->idTaiKhoan1 === $id ? $friend->idTaiKhoan2 : $friend->idTaiKhoan1);

        return BaiViet::where('TrangThaiBaiViet', 'Binh_Thuong')
            ->where(fn ($query) => $query->where('idTaiKhoan', $id)
                ->orWhere('CheDoHienThi', 'Cong_Khai')
                ->orWhere(fn ($friendQuery) => $friendQuery->where('CheDoHienThi', 'Ban_Be')->whereIn('idTaiKhoan', $friends)));
    }

    /**
     * Show the form for creating a new resource.
     */
    private function data(BaiViet $baiViet): array
    {
        $baiViet->loadMissing('taiKhoan.thongTinCaNhan', 'camXuc');
        $profile = $baiViet->taiKhoan->thongTinCaNhan;

        return [
            'id' => $baiViet->idBaiViet,
            'idTaiKhoan' => $baiViet->idTaiKhoan,
            'content' => $baiViet->NoiDung,
            'image_url' => $baiViet->HinhAnh ? route('bai-viets.media', $baiViet->idBaiViet, false) : null,
            'visibility' => $baiViet->CheDoHienThi,
            'published_at' => $baiViet->NgayDang,
            'name' => trim(($profile?->Ho ?? '').' '.($profile?->Ten ?? '')) ?: $baiViet->taiKhoan->TaiKhoan,
            'mood' => $baiViet->camXuc?->TenCamXuc,
            'mood_icon' => $baiViet->camXuc?->BieuTuong,
            'likes' => $baiViet->tuong_tacs_count,
            'comments' => $baiViet->binh_luans_count,
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_unless($request->user('tai_khoan')->TrangThaiTaiKhoan === 'Hoat_Dong', 403);
        $data = $request->validate([
            'NoiDung' => ['nullable', 'string', 'max:5000', 'required_without:photo'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240', 'required_without:NoiDung'],
            'CheDoHienThi' => ['required', Rule::in(['Cong_Khai', 'Ban_Be', 'Chi_Minh_Toi'])],
            'idCamXuc' => ['nullable', 'integer', Rule::exists('cam_xucs', 'idCamXuc')->where('TrangThai', 'Dang_Su_Dung')],
        ], [
            'NoiDung.required_without' => 'Hãy nhập nội dung hoặc chọn một ảnh để đăng.',
            'photo.required_without' => 'Hãy nhập nội dung hoặc chọn một ảnh để đăng.',
            'photo.mimes' => 'Chỉ hỗ trợ ảnh JPG, PNG, WebP hoặc GIF.',
            'photo.max' => 'Ảnh tối đa 10 MB.',
        ]);

        $path = null;
        try {
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('posts', 'local');
            }
            $post = BaiViet::create([
                'idTaiKhoan' => $request->user('tai_khoan')->idTaiKhoan,
                'NoiDung' => trim($data['NoiDung'] ?? '') ?: null,
                'HinhAnh' => $path,
                'idCamXuc' => $data['idCamXuc'] ?? null,
                'CheDoHienThi' => $data['CheDoHienThi'],
                'NgayDang' => now(),
                'NgayCapNhat' => now(),
                'TrangThaiBaiViet' => 'Binh_Thuong',
            ]);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return response()->json(['message' => 'Đăng bài thành công.', 'data' => $this->data($post->loadCount(['tuongTacs', 'binhLuans']))], 201);
    }

    public function index(Request $request)
    {
        return response()->json(['data' => $this->visible($request)->with(['taiKhoan.thongTinCaNhan', 'camXuc'])
            ->withCount(['tuongTacs', 'binhLuans'])->orderByDesc('NgayDang')->orderByDesc('idBaiViet')->limit(100)
            ->get()->map(fn (BaiViet $post) => $this->data($post))]);
    }

    public function media(Request $request, int $baiViet)
    {
        $post = $this->visible($request)->findOrFail($baiViet);
        abort_unless($post->HinhAnh && Storage::disk('local')->exists($post->HinhAnh), 404);

        return response()->file(Storage::disk('local')->path($post->HinhAnh), ['Cache-Control' => 'private, no-store']);
    }

    public function react(Request $request, int $baiViet)
    {
        $post = $this->visible($request)->findOrFail($baiViet);
        $data = $request->validate(['type' => ['required', Rule::in(['Thich', 'Yeu_Thich', 'Haha', 'Buon', 'Tuc_Gian'])]]);
        $reaction = TuongTacBaiViet::where('idBaiViet', $post->idBaiViet)->where('idTaiKhoan', $request->user('tai_khoan')->idTaiKhoan)->first();
        if ($reaction?->LoaiTuongTac === $data['type']) {
            $reaction->delete();
        } else {
            TuongTacBaiViet::updateOrCreate(['idBaiViet' => $post->idBaiViet, 'idTaiKhoan' => $request->user('tai_khoan')->idTaiKhoan], ['LoaiTuongTac' => $data['type'], 'ThoiGianTuongTac' => now()]);
        }

        return response()->json(['data' => ['likes' => $post->tuongTacs()->count(), 'reaction' => $reaction?->LoaiTuongTac === $data['type'] ? null : $data['type']]]);
    }

    public function comments(Request $request, int $baiViet)
    {
        $post = $this->visible($request)->findOrFail($baiViet);
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'content' => ['required', 'string', 'max:2000'],
                'parent_id' => ['nullable', 'integer'],
            ]);

            if (isset($data['parent_id'])) {
                abort_unless(BinhLuan::where('idBinhLuan', $data['parent_id'])->where('idBaiViet', $post->idBaiViet)->exists(), 422, 'Bình luận gốc không hợp lệ.');
            }

            $comment = BinhLuan::create([
                'idBaiViet' => $post->idBaiViet,
                'idTaiKhoan' => $request->user('tai_khoan')->idTaiKhoan,
                'NoiDung' => trim($data['content']),
                'ThoiGianBinhLuan' => now(),
                'idBinhLuanCha' => $data['parent_id'] ?? null,
                'TrangThai' => 'Binh_Thuong',
            ]);
            $payload = $this->commentData($comment->load('taiKhoan.thongTinCaNhan'));
            BinhLuanDaTao::dispatch($post->idBaiViet, $payload);

            return response()->json(['message' => 'Đã đăng bình luận.', 'data' => $payload], 201);
        }

        return response()->json(['data' => $post->binhLuans()->with('taiKhoan.thongTinCaNhan')
            ->where('TrangThai', 'Binh_Thuong')->oldest('ThoiGianBinhLuan')->oldest('idBinhLuan')->get()
            ->map(fn (BinhLuan $comment) => $this->commentData($comment))]);
    }

    private function commentData(BinhLuan $comment): array
    {
        return [
            'id' => $comment->idBinhLuan,
            'idTaiKhoan' => $comment->idTaiKhoan,
            'parent_id' => $comment->idBinhLuanCha,
            'content' => $comment->NoiDung,
            'created_at' => $comment->ThoiGianBinhLuan?->toISOString(),
            'name' => trim(($comment->taiKhoan->thongTinCaNhan?->Ho ?? '').' '.($comment->taiKhoan->thongTinCaNhan?->Ten ?? '')) ?: $comment->taiKhoan->TaiKhoan,
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(BaiViet $baiViet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BaiViet $baiViet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Cập nhật bài viết.
     */
    public function update(Request $request, BaiViet $baiViet)
    {
        $user = $request->user('tai_khoan');

        abort_unless(
            $user && $user->TrangThaiTaiKhoan === 'Hoat_Dong',
            403,
            'Tài khoản không có quyền thực hiện thao tác này.'
        );

        abort_unless(
            (int) $baiViet->idTaiKhoan === (int) $user->idTaiKhoan,
            403,
            'Bạn không có quyền sửa bài viết này.'
        );

        abort_unless(
            $baiViet->TrangThaiBaiViet === 'Binh_Thuong',
            404,
            'Bài viết không tồn tại hoặc đã bị xóa.'
        );

        $data = $request->validate([
            'content' => [
                'required',
                'string',
                'max:5000',
            ],
            'visibility' => [
                'sometimes',
                Rule::in([
                    'Cong_Khai',
                    'Ban_Be',
                    'Chi_Minh_Toi',
                ]),
            ],
            'mood_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('cam_xucs', 'idCamXuc')
                    ->where('TrangThai', 'Dang_Su_Dung'),
            ],
        ], [
            'content.required' => 'Nội dung bài viết không được để trống.',
            'content.string' => 'Nội dung bài viết không hợp lệ.',
            'content.max' => 'Nội dung bài viết tối đa 5000 ký tự.',
            'visibility.in' => 'Chế độ hiển thị không hợp lệ.',
            'mood_id.exists' => 'Cảm xúc không tồn tại hoặc đã ngừng sử dụng.',
        ]);

        $baiViet->NoiDung = trim($data['content']);

        if (array_key_exists('visibility', $data)) {
            $baiViet->CheDoHienThi = $data['visibility'];
        }

        if (array_key_exists('mood_id', $data)) {
            $baiViet->idCamXuc = $data['mood_id'];
        }

        $baiViet->NgayCapNhat = now();
        $baiViet->save();

        $baiViet->load([
            'taiKhoan.thongTinCaNhan',
            'camXuc',
        ]);

        $baiViet->loadCount([
            'tuongTacs',
            'binhLuans',
        ]);

        return response()->json([
            'message' => 'Cập nhật bài viết thành công.',
            'data' => $this->data($baiViet),
        ]);
    }

    /**
     * Xóa bài viết.
     */
    public function destroy(Request $request, BaiViet $baiViet)
    {
        $user = $request->user('tai_khoan');

        abort_unless(
            $user && $user->TrangThaiTaiKhoan === 'Hoat_Dong',
            403,
            'Tài khoản không có quyền thực hiện thao tác này.'
        );

        abort_unless(
            (int) $baiViet->idTaiKhoan === (int) $user->idTaiKhoan,
            403,
            'Bạn không có quyền xóa bài viết này.'
        );

        abort_unless(
            $baiViet->TrangThaiBaiViet === 'Binh_Thuong',
            404,
            'Bài viết không tồn tại hoặc đã bị xóa.'
        );

        /*
         * Xóa mềm bằng cách đổi trạng thái.
         * Ảnh vẫn được giữ lại, có thể khôi phục bài viết sau này.
         */
        $baiViet->update([
            'TrangThaiBaiViet' => 'Da_Xoa',
            'NgayCapNhat' => now(),
        ]);

        return response()->json([
            'message' => 'Xóa bài viết thành công.',
            'data' => [
                'id' => $baiViet->idBaiViet,
                'idTaiKhoan' => $baiViet->idTaiKhoan,
            ],
        ]);
    }
}
