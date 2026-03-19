<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('keyword', ''));

        $stores = Store::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('address', 'like', "%{$keyword}%");
                });
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('home', compact('stores', 'keyword'));
    }
}