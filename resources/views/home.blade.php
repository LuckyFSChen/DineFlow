@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-brand-soft/20 text-brand-dark">
    <section class="relative isolate overflow-hidden border-b border-brand-soft/60 bg-brand-dark text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.22),_transparent_30%),linear-gradient(135deg,_rgba(90,30,14,0.98),_rgba(236,144,87,0.92))]"></div>
        <div class="absolute -left-16 top-20 h-44 w-44 rounded-full bg-brand-accent/20 blur-3xl"></div>
        <div class="absolute -right-10 bottom-0 h-56 w-56 rounded-full bg-brand-highlight/10 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-6 py-16 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div>
                <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-sm font-semibold tracking-[0.2em] text-brand-highlight">
                    DineFlow QR Ordering
                </span>

                <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">
                    讓店家點餐流程更快速
                    <br>
                    也更一致直覺
                </h1>

                <p class="mt-5 max-w-2xl text-lg leading-8 text-white/75">
                    掃描 QR Code 後，顧客可直接進入店家專屬點餐頁，從瀏覽菜單、加入購物車到送出訂單，
                    全部都在一套清楚一致的體驗裡完成。
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="#store-list" class="inline-flex items-center rounded-2xl bg-brand-highlight px-5 py-3 text-sm font-semibold text-brand-dark shadow-lg shadow-brand-highlight/30 transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        查看店家
                    </a>
                    <a href="#how-it-works" class="inline-flex items-center rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                        了解流程
                    </a>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-white/10 p-8 shadow-[0_30px_80px_rgba(0,0,0,0.28)] backdrop-blur">
                <div class="mb-6">
                    <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-highlight/80">
                        Product Feel
                    </div>
                    <h2 class="mt-3 text-2xl font-bold text-white">
                        從掃碼到結帳，維持同一套風格
                    </h2>
                    <p class="mt-3 text-white/70">
                        不只是能點餐，而是讓整體前台體驗看起來更完整。
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <div class="font-semibold text-white">掃碼後立即進入菜單</div>
                        <div class="mt-1 text-sm text-white/70">快速查看分類、價格、商品圖片與購物車狀態。</div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <div class="font-semibold text-white">一致的點餐流程</div>
                        <div class="mt-1 text-sm text-white/70">店家首頁、菜單、購物車與成功頁都使用一致的暖色系視覺。</div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <div class="font-semibold text-white">完成後立即看到訂單</div>
                        <div class="mt-1 text-sm text-white/70">送出訂單後，顧客可以立刻看到清楚的訂單摘要與編號。</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="store-list" class="py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-8 text-center">
                <h2 class="text-3xl font-bold tracking-tight text-brand-dark">可點餐店家</h2>
                <p class="mt-3 text-brand-primary/75">
                    選擇一間店家，立即進入專屬點餐頁。
                </p>
            </div>

            <div class="mx-auto mb-10 max-w-3xl rounded-[1.75rem] border border-brand-soft/60 bg-white p-4 shadow-[0_18px_40px_rgba(90,30,14,0.08)]">
                <form method="GET" action="{{ route('home') }}" class="flex flex-col gap-3 md:flex-row">
                    <input
                        type="text"
                        name="keyword"
                        value="{{ $keyword ?? '' }}"
                        placeholder="搜尋店家名稱、地址或電話"
                        class="w-full rounded-2xl border border-brand-soft/70 px-4 py-3 text-brand-dark placeholder:text-brand-primary/40 focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40"
                    >
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark"
                    >
                        搜尋
                    </button>
                </form>
            </div>

            @if($stores->count())
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($stores as $store)
                        <div class="group flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_60px_rgba(90,30,14,0.16)]">
                            <div class="relative h-48 w-full overflow-hidden">
                                <img
                                    src="{{ $store->banner_image ? asset('storage/' . $store->banner_image) : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80' }}"
                                    alt="{{ $store->name }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/85 via-brand-dark/25 to-transparent"></div>
                                <div class="absolute left-4 top-4">
                                    @if($store->isOrderingAvailable())
                                        <span class="inline-flex rounded-full bg-brand-highlight px-3 py-1 text-xs font-semibold text-brand-dark shadow">
                                            可點餐
                                        </span>
                                    @elseif($store->is_active)
                                        <span class="inline-flex rounded-full bg-brand-soft px-3 py-1 text-xs font-semibold text-brand-dark shadow">
                                            營業中
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-brand-dark shadow">
                                            未開放
                                        </span>
                                    @endif
                                </div>
                                <div class="absolute bottom-4 left-4 right-4 text-white">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-highlight/80">Store</p>
                                    <h3 class="mt-2 text-2xl font-bold">{{ $store->name }}</h3>
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col p-6">
                                <p class="text-sm leading-6 text-brand-primary/75">
                                    {{ $store->description ? \Illuminate\Support\Str::limit($store->description, 100) : '歡迎使用 DineFlow 進入店家專屬點餐體驗。' }}
                                </p>

                                <div class="mt-5 space-y-2 text-sm text-brand-primary/70">
                                    <div>營業時段 {{ $store->businessHoursLabel() }}</div>
                                    @if(!empty($store->address))
                                        <div>地址 {{ $store->address }}</div>
                                    @endif
                                    @if(!empty($store->phone))
                                        <div>電話 {{ $store->phone }}</div>
                                    @endif
                                </div>

                                <div class="mt-auto pt-6">
                                    @if($store->is_active)
                                        <a href="{{ route('stores.enter', ['store' => $store]) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
                                            進入店家
                                        </a>
                                    @else
                                        <button disabled class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-2xl bg-slate-200 px-4 py-3 text-sm font-semibold text-slate-500">
                                            尚未開放
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $stores->links() }}
                </div>
            @else
                <div class="rounded-[2rem] border border-brand-soft/60 bg-white px-6 py-16 text-center shadow-[0_20px_40px_rgba(90,30,14,0.08)]">
                    <h3 class="text-xl font-bold text-brand-dark">找不到符合條件的店家</h3>
                    <p class="mt-3 text-brand-primary/75">
                        可以換個關鍵字，或先看看首頁推薦的店家。
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('home') }}" class="inline-flex items-center rounded-2xl border border-brand-soft/70 bg-white px-5 py-3 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/20">
                            查看全部店家
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section id="how-it-works" class="border-t border-brand-soft/60 bg-white py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-10 text-center">
                <h2 class="text-3xl font-bold tracking-tight text-brand-dark">點餐流程</h2>
                <p class="mt-3 text-brand-primary/75">三個步驟就能完成從掃碼到送單的整體體驗。</p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-[1.75rem] border border-brand-soft/60 bg-brand-soft/18 p-8 text-center">
                    <div class="text-3xl font-bold text-brand-primary">01</div>
                    <h3 class="mt-4 text-xl font-bold text-brand-dark">掃描 QR Code</h3>
                    <p class="mt-3 text-sm leading-6 text-brand-primary/75">顧客掃碼後直接進入店家專屬點餐頁，無需下載 App。</p>
                </div>
                <div class="rounded-[1.75rem] border border-brand-soft/60 bg-brand-soft/18 p-8 text-center">
                    <div class="text-3xl font-bold text-brand-primary">02</div>
                    <h3 class="mt-4 text-xl font-bold text-brand-dark">瀏覽菜單並加入購物車</h3>
                    <p class="mt-3 text-sm leading-6 text-brand-primary/75">透過分類、商品圖片與數量控制，快速完成點餐。</p>
                </div>
                <div class="rounded-[1.75rem] border border-brand-soft/60 bg-brand-soft/18 p-8 text-center">
                    <div class="text-3xl font-bold text-brand-primary">03</div>
                    <h3 class="mt-4 text-xl font-bold text-brand-dark">送出訂單</h3>
                    <p class="mt-3 text-sm leading-6 text-brand-primary/75">系統立即產生訂單摘要與編號，前後台都能快速對單。</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="border-t border-brand-soft/60 bg-brand-dark">
        <div class="mx-auto max-w-7xl px-6 py-8 text-center text-white/80 lg:px-8">
            <div class="text-base font-semibold text-white">DineFlow</div>
            <div class="mt-2 text-sm">QR Code 點餐體驗平台</div>
        </div>
    </footer>
</div>
@endsection
