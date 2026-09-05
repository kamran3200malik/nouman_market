<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FavoritesController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Favorite::where('user_id', $user->id)
            ->with(['product.categoryRelation']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $favorites = $query->latest()->paginate(12)->withQueryString();

        return Inertia::render('Customer/Wishlist', [
            'favorites' => $favorites,
            'filters' => [
                'search' => $request->search ?? '',
            ],
        ]);
    }

    public function toggle(Product $product)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $existing = Favorite::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', "'{$product->name}' removed from your wishlist.");
        }

        Favorite::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return back()->with('success', "'{$product->name}' added to your wishlist.");
    }

    public function destroy(Product $product)
    {
        $favorite = Favorite::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
        }

        return back()->with('success', 'Product removed from wishlist.');
    }
}
