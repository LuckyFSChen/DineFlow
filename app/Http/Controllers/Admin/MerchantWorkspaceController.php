<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsAllBoardsPageData;
use App\Http\Controllers\Concerns\BuildsMerchantOrderPageData;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MerchantWorkspaceController extends Controller
{
    use BuildsAllBoardsPageData;
    use BuildsMerchantOrderPageData;

    public function index(Request $request, Store $store): View
    {
        $this->authorize('update', $store);

        $orderViewData = array_merge($this->merchantOrderPageViewData($store), [
            'embedded' => true,
            'workspace' => true,
        ]);
        $boardViewData = array_merge($this->allBoardsPageViewData($request, $store), [
            'embedded' => true,
            'workspace' => true,
        ]);

        return view('admin.workspace.index', [
            'store' => $store,
            'availableStores' => $boardViewData['availableStores'] ?? collect(),
            'initialTab' => $request->query('tab') === 'boards' ? 'boards' : 'orders',
            'ordersPanelHtml' => view('admin.orders.create', $orderViewData)->renderSections()['content'] ?? '',
            'boardsPanelHtml' => view('admin.boards.index', $boardViewData)->renderSections()['content'] ?? '',
        ]);
    }
}
