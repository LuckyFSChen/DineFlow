<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->get('keyword', ''));

        $query = Store::query()
            ->where('is_active', 1)
            ->where('takeout_qr_enabled', 1);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('address', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $stores = $query
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('home', compact('stores', 'keyword'));
    }
}
