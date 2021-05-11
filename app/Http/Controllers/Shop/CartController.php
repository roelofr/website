<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CartAddRequest;
use App\Http\Requests\Shop\CartUpdateRequest;
use App\Models\Shop\ProductVariant;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Darryldecode\Cart\ItemCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index(): Response
    {
        return ResponseFacade::view('shop.cart', [
            'cartItems' => Cart::getContent()->sortBy('metadata.sort-key'),
        ]);
    }

    public function add(CartAddRequest $request): RedirectResponse
    {
        // Find variant
        $variant = ProductVariant::query()
            ->with([
                'product',
                'product.variants',
            ])->findOrFail($request->variant);

        $product = $variant->product;
        $isSingleVariant = $product->variants->count() === 1;

        // De-duplicate
        $matchedItem = null;
        foreach (Cart::getContent() as $item) {
            assert($item instanceof ItemCollection);

            $itemModel = $item->model;
            if (! $itemModel || !$itemModel->is($variant)) {
                continue;
            }

            $matchedItem = $itemModel;
        }

        // If an item is matched, simply increase the count
        if ($matchedItem) {
            Cart::update($matchedItem->id, [
                'quantity' => $request->quantity,
            ]);

            flash(__(':Product was already in your cart, so we\'ve added :count more.', [
                'product' => $matchedItem->name,
                'quantity' => $request->quantity,
            ]));

            return ResponseFacade::redirectToRoute('shop.cart');
        }

        // Make a nice name
        $cartItemName = $isSingleVariant ? $product->name : "{$product->name} {$variant->name}";

        Cart::add([
            'id' => (string) Str::random(16),
            'name' => $cartItemName,
            'price' => $variant->price,
            'quantity' => $request->quantity,
            'associatedModel' => $variant,
            'metadata' => [
                // For some reason IDs are a reflection of the order
                'sort-key' => "{$product->id}_{$variant->id}",
            ],
        ]);

        flash(__(':Product was added to your cart.', [
            'product' => $cartItemName,
        ]));

        return ResponseFacade::redirectToRoute('shop.cart');
    }

    public function update(CartUpdateRequest $request): RedirectResponse
    {
        // Get the posted data
        $valid = $request->validated();
        $cartId = $valid['id'];
        $quantity = (int) $valid['quantity'];

        // Find the node
        $entry = Cart::getContent()->firstWhere('id', $cartId);

        // Fail if missing
        if (!$entry) {
            flash(__('The given product could not be found in your cart'));

            return ResponseFacade::redirectToRoute('shop.cart');
        }

        // Remove if quantity is zero, or lower
        if ($quantity <= 0) {
            Cart::remove($entry->id);

            flash(__(':Product was removed from your cart', [
                'product' => $entry->name,
            ]));

            return ResponseFacade::redirectToRoute('shop.cart');
        }

        // Update quantity but treat it as an absolute
        Cart::update($entry->id, [
            'quantity' => [
                'relative' => false,
                'value' => $quantity,
            ],
        ]);

        flash(__(':Product has been updated', [
            'product' => $entry->name,
        ]));

        return ResponseFacade::redirectToRoute('shop.cart');
    }
}
