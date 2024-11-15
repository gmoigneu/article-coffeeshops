<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index()
    {
        return ShopResource::collection(Shop::all());
    }

    public function show(Shop $shop)
    {
        return new ShopResource($shop);
    }
}
