<div class="space-y-6">
    <div class="filament-tabs flex overflow-x-auto items-center p-1 space-x-1 rtl:space-x-reverse bg-gray-500/5 rounded-lg">
        {{-- Tab Semua Produk --}}
        <button
            wire:click="filterProductsByCategory()"
            @class([
                'flex items-center h-8 px-5 font-medium rounded-lg whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-inset',
                'bg-white text-primary-600 shadow' => $selectedCategoryId === null,
                'hover:text-gray-800 hover:bg-white focus:text-primary-600' => $selectedCategoryId !== null,
            ])
        >
            Semua Produk
        </button>

        @foreach($categories as $category)
        <button
            wire:click="filterProductsByCategory({{ $category->id }})"
            @class([
                'flex items-center h-8 px-5 font-medium rounded-lg whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-inset',
                'bg-white text-primary-600 shadow' => $selectedCategoryId === $category->id,
                'hover:text-gray-800 hover:bg-white focus:text-primary-600' => $selectedCategoryId !== $category->id,
            ])
        >
            {{ $category->name }}
        </button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($filteredProducts as $product)
        <div class="bg-white p-4 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="aspect-w-3 aspect-h-2">
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="object-cover rounded">
                @else
                    <div class="w-full h-full bg-gray-200 rounded flex items-center justify-center">
                        <span class="text-gray-400">No Image</span>
                    </div>
                @endif
            </div>
            <div class="mt-4">
                <h3 class="text-lg font-medium text-gray-900">{{ $product->name }}</h3>
                <p class="mt-1 text-sm text-gray-500">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                <div class="mt-2 text-sm text-gray-500">Stok: {{ $product->stock }}</div>
                <button
                    wire:click="addToCart({{ $product->id }})"
                    @class([
                        'mt-4 w-full px-4 py-2 rounded-lg',
                        'bg-primary-600 text-white hover:bg-primary-700' => $product->stock > 0,
                        'bg-gray-300 cursor-not-allowed' => $product->stock <= 0,
                    ])
                    @disabled($product->stock <= 0)
                >
                    {{ $product->stock > 0 ? 'Tambah ke Keranjang' : 'Stok Habis' }}
                </button>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12">
            <div class="text-gray-500">Tidak ada produk tersedia</div>
        </div>
        @endforelse
    </div>
</div>