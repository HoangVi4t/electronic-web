<?php

namespace App\Http\Livewire;

use App\Http\Middleware\AuthAdmin;
use Livewire\Component;
use Cart;
use Illuminate\Support\Facades\Auth;

class CartComponent extends Component
{
    public function increaseQuantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1;
        Cart::instance('cart')->update($rowId, $qty);
        $this->emitTo('cart-count-component','refreshComponent');
        return $this->redirect('cart');
    }

    public function decreaseQuantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1;
        Cart::instance('cart')->update($rowId, $qty);
        $this->emitTo('cart-count-component','refreshComponent');
        return $this->redirect('cart');
    }

    public function destroy($rowId){
        Cart::instance('cart')->remove($rowId);
        $this->emitTo('cart-count-component','refreshComponent');
        session()->flash('success_message','Item has been removed');
        return $this->redirect('cart');

    }

    public function destroyAll(){
        Cart::instance('cart')->destroy();
        $this->emitTo('cart-count-component','refreshComponent');

        return $this->redirect('cart');

    }

    public function switchToSaveForLater($rowId){
        $item = Cart::instance('cart')->get($rowId);
        Cart::instance('cart')->remove($rowId);
        Cart::instance('saveForLater')->add($item->id,$item->name,1,$item->price)->associate('App\Models\Product');
        $this->emitTo('cart-count-component','refreshComponent');
        session()->flash('success_message','Item has been saved for later');
        return $this->redirect('cart');


    }

    public function moveToCart($rowId){
        $item = Cart::instance('saveForLater')->get($rowId);
        Cart::instance('saveForLater')->remove($rowId);
        Cart::instance('cart')->add($item->id,$item->name,1,$item->price)->associate('App\Models\Product');
        $this->emitTo('cart-count-component','refreshComponent');
        session()->flash('success_message','Item has been moved to cart');
        return $this->redirect('cart');

    }

    public function deleteFromSaveForLater($rowId){
        Cart::instance('savedForLater')->remove($rowId);
        session()->flash('success_message','Item has been removed from save for later');

    }

    public function checkout(){
        if(Auth::check())
        {
            return redirect()->route('checkout');
        } else{
            return redirect()->route('login');
        }
    }

    public function setAmountForCheckout()
    {
        if(!Cart::instance('cart')->count()>0)
        {
            session()->forget('checkout');
            return;
        }
        if(session()->has('coupon'))
        {
            session()->put('checkout',[
               'discount' => $this->discount,
               'subtotal' => $this->subtotalAfterDiscount,
               'tax' => $this->taxAfterDiscount,
               'total' => $this->totalAfterDiscount
            ]);
        }
        else
        {
            session()->put('checkout',[
                'discount' => 0,
                'subtotal' => Cart::instance('cart')->subtotal(),
                'tax'=>Cart::instance('cart')->tax(),
                'total'=>Cart::instance('cart')->total()
            ]);
        }
    }

    public function render()
    {
        $this->setAmountForCheckout();
        return view('livewire.cart-component')->layout('layouts.base');
    }
}
