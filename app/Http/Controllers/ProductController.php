<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'integer', 'exists:ms_kategori,id_kategori'],
        ]);

        $products = Product::query()
            ->with('category')
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nama_produk', 'like', '%'.$search.'%')
                        ->orWhereHas('category', function ($query) use ($search): void {
                            $query->where('nama_kategori', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($filters['kategori'] ?? null, function ($query, $categoryId): void {
                $query->where('id_kategori', $categoryId);
            })
            ->orderBy('nama_produk')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->orderBy('nama_kategori')->get();

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $filters['q'] ?? '',
            'selectedCategory' => $filters['kategori'] ?? '',
            'productCount' => $products->total(),
            'categoryCount' => $categories->count(),
            'totalStock' => (int) Product::query()->sum('stok'),
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product,
            'categories' => Category::query()->orderBy('nama_kategori')->get(),
            'editing' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);
        $image = $this->storeImage($request) ?? ($data['gambar_url'] ?? null);
        unset($data['gambar_url']);

        $nextId = (int) Product::query()->max('id_produk') + 1;
        if ($nextId > 2147483647) {
            throw new RuntimeException('ID produk telah mencapai batas maksimum database.');
        }

        Product::query()->create([
            ...$data,
            'id_produk' => $nextId,
            'gambar' => $image,
        ]);

        return redirect()->route('products.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        $product->load('category');

        return view('products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('nama_kategori')->get(),
            'editing' => true,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request);
        $oldImage = $product->gambar;
        $uploadedImage = $this->storeImage($request);
        $providedImageUrl = filled($data['gambar_url'] ?? null);
        $image = $uploadedImage ?? ($providedImageUrl ? $data['gambar_url'] : null);
        unset($data['gambar_url']);

        $product->fill($data);
        if ($image !== null) {
            $product->gambar = $image;
        } elseif ($request->boolean('hapus_gambar')) {
            $product->gambar = null;
        }
        $product->save();

        if (($image !== null || $request->boolean('hapus_gambar')) && $oldImage !== $product->gambar) {
            $this->deleteStoredImage($oldImage);
        }

        return redirect()->route('products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        foreach ([
            ['table' => 'tr_detail_pesanan', 'column' => 'id_produk'],
            ['table' => 'tr_stok', 'column' => 'ms_produk_id_produk'],
        ] as $reference) {
            if (
                Schema::hasTable($reference['table'])
                && DB::table($reference['table'])->where($reference['column'], $product->getKey())->exists()
            ) {
                return back()->with('error', 'Produk memiliki riwayat transaksi atau stok sehingga tidak dapat dihapus.');
            }
        }

        $image = $product->gambar;
        $product->delete();
        $this->deleteStoredImage($image);

        return redirect()->route('products.index')->with('status', 'Produk berhasil dihapus.');
    }

    /**
     * @return array{nama_produk: string, id_kategori: int|null, harga: int, gambar_url: string|null}
     */
    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'nama_produk' => ['required', 'string', 'max:30'],
            'id_kategori' => ['nullable', 'integer', Rule::exists('ms_kategori', 'id_kategori')],
            'harga' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'gambar_url' => ['nullable', 'url:http,https', 'max:100'],
            'gambar' => ['nullable', 'image', 'max:2048'],
        ], [
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'nama_produk.max' => 'Nama produk maksimal 30 karakter.',
            'id_kategori.exists' => 'Kategori yang dipilih tidak tersedia.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.integer' => 'Harga harus berupa angka bulat.',
            'harga.min' => 'Harga tidak boleh kurang dari nol.',
            'gambar_url.url' => 'Masukkan URL gambar yang valid dan diawali http atau https.',
            'gambar_url.max' => 'URL gambar maksimal 100 karakter.',
            'gambar.image' => 'File gambar harus berupa gambar yang valid.',
            'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
        ]);
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('gambar')) {
            return null;
        }

        $path = $request->file('gambar')->store('products', 'public');
        if ($path === false || Str::length($path) > 100) {
            throw new RuntimeException('Gambar produk gagal disimpan. Silakan coba lagi.');
        }

        return $path;
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && ! Str::startsWith($path, ['http://', 'https://'])) {
            if (! Storage::disk('public')->delete($path)) {
                logger()->warning('Failed to delete a product image from public storage.', ['path' => $path]);
            }
        }
    }
}
