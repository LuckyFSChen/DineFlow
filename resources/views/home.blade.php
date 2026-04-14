@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-brand-soft/20 text-brand-dark">
    <section class="relative isolate overflow-hidden border-b border-brand-soft/60 bg-brand-dark text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.22),_transparent_30%),linear-gradient(135deg,_rgba(90,30,14,0.98),_rgba(236,144,87,0.92))]"></div>
        <div class="absolute -left-16 top-20 h-44 w-44 rounded-full bg-brand-accent/20 blur-3xl"></div>
        <div class="absolute -right-10 bottom-0 h-56 w-56 rounded-full bg-brand-highlight/10 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-6 py-16 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-base font-semibold tracking-[0.2em] text-brand-highlight">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 3h5v5H3V3zm1.5 1.5v2h2v-2h-2zM12 3h5v5h-5V3zm1.5 1.5v2h2v-2h-2zM3 12h5v5H3v-5zm1.5 1.5v2h2v-2h-2zM11.5 12a.5.5 0 01.5-.5h1v-1a.5.5 0 011 0v1h1a.5.5 0 010 1h-1v1a.5.5 0 01-1 0v-1h-1a.5.5 0 01-.5-.5zm3 3a.5.5 0 01.5-.5h1a.5.5 0 010 1h-1a.5.5 0 01-.5-.5z" clip-rule="evenodd"/>
                    </svg>
                    DineFlow QR Ordering
                </span>

                <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">
                    讓店家點餐流程更快速
                    <br>
                    也更一致直覺
                </h1>

                <p class="mt-5 max-w-2xl text-xl leading-9 text-white/75">
                    掃描 QR Code 後，顧客可直接進入店家專屬點餐頁，從瀏覽菜單、加入購物車到送出訂單，
                    全部都在一套清楚一致的體驗裡完成。
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="#store-list" class="inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark shadow-lg shadow-brand-highlight/30 transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4.03 4.86 8.84 5.07 9.04a1.3 1.3 0 001.86 0c.2-.2 5.07-5.01 5.07-9.04a6 6 0 00-6-6zm0 8a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                        查看店家
                    </a>
                    <a href="#how-it-works" class="inline-flex items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.5a.75.75 0 10-1.5 0v3.25c0 .2.08.39.22.53l2 2a.75.75 0 101.06-1.06l-1.78-1.78V6.5z" clip-rule="evenodd"/>
                        </svg>
                        了解流程
                    </a>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-white/10 p-8 shadow-[0_30px_80px_rgba(0,0,0,0.28)] backdrop-blur">
                <div class="mb-6">
                    <div class="text-base font-semibold uppercase tracking-[0.2em] text-brand-highlight/80">
                        Product Feel
                    </div>
                    <h2 class="mt-3 text-2xl font-bold text-white">
                        從掃碼到結帳，維持同一套風格
                    </h2>
                    <p class="mt-3 text-lg leading-8 text-white/70">
                        不只是能點餐，而是讓整體前台體驗看起來更完整。
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-highlight/20 text-brand-highlight">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 3h6v6H3V3zm1.5 1.5v3h3v-3h-3zM11 3h6v6h-6V3zm1.5 1.5v3h3v-3h-3zM3 11h6v6H3v-6zm1.5 1.5v3h3v-3h-3zM12 11a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1v-1a1 1 0 10-2 0v1h-1z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <div>
                                <div class="text-lg font-semibold text-white">掃碼後立即進入菜單</div>
                                <div class="mt-1 text-base leading-7 text-white/70">快速查看分類、價格、商品圖片與購物車狀態。</div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-highlight/20 text-brand-highlight">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4 3a1 1 0 00-1 1v9a4 4 0 004 4h8a1 1 0 100-2H7a2 2 0 01-2-2V4a1 1 0 00-1-1zm4 1a1 1 0 011-1h7a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V4z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <div>
                                <div class="text-lg font-semibold text-white">一致的點餐流程</div>
                                <div class="mt-1 text-base leading-7 text-white/70">店家首頁、菜單、購物車與成功頁都使用一致的暖色系視覺。</div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-highlight/20 text-brand-highlight">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V7.4a2 2 0 00-.59-1.41l-2.4-2.4A2 2 0 0013.6 3H4zm3.3 4.2a1 1 0 011.4 0l1.1 1.1 2.5-2.5a1 1 0 111.4 1.4l-3.2 3.2a1 1 0 01-1.4 0L7.3 7.6a1 1 0 010-1.4zM6 12a1 1 0 100 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <div>
                                <div class="text-lg font-semibold text-white">完成後立即看到訂單</div>
                                <div class="mt-1 text-base leading-7 text-white/70">送出訂單後，顧客可以立刻看到清楚的訂單摘要與編號。</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="store-list" class="py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-8 text-center">
                <h2 class="inline-flex items-center gap-2 text-3xl font-bold tracking-tight text-brand-dark">
                    <svg class="h-7 w-7 text-brand-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4.03 4.86 8.84 5.07 9.04a1.3 1.3 0 001.86 0c.2-.2 5.07-5.01 5.07-9.04a6 6 0 00-6-6zm0 8a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    可點餐店家
                </h2>
                <p class="mt-3 text-lg text-brand-primary/75">
                    選擇一間店家，立即進入專屬點餐頁。
                </p>
            </div>

            <div class="mx-auto mb-10 max-w-3xl rounded-[1.75rem] border border-brand-soft/80 bg-white p-4 shadow-[0_18px_40px_rgba(90,30,14,0.08)]">
                <form method="GET" action="{{ route('home') }}" class="flex flex-row gap-3">
                    <input
                        type="text"
                        name="keyword"
                        value="{{ $keyword ?? '' }}"
                        placeholder="搜尋店家名稱、地址或電話"
                        class="min-w-0 flex-1 rounded-2xl border border-brand-soft/70 px-4 py-3 text-lg text-brand-dark placeholder:text-brand-primary/80 focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40"
                    >
                    <button
                        type="submit"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-brand-primary px-5 py-3 text-base font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 014.46 8.72l3.16 3.16a1 1 0 01-1.41 1.41l-3.16-3.16A5.5 5.5 0 118.5 3zm0 2a3.5 3.5 0 100 7 3.5 3.5 0 000-7z" clip-rule="evenodd"/>
                        </svg>
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
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-highlight px-3 py-1 text-xs font-semibold text-brand-dark shadow">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.3 10.7a1 1 0 011.4 0l.8.8 2.8-2.8a1 1 0 111.4 1.4l-3.5 3.5a1 1 0 01-1.4 0l-1.5-1.5a1 1 0 010-1.4z" clip-rule="evenodd"/>
                                            </svg>
                                            可點餐
                                        </span>
                                    @elseif($store->is_active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-soft px-3 py-1 text-xs font-semibold text-brand-dark shadow">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <circle cx="10" cy="10" r="4"/>
                                            </svg>
                                            營業中
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-brand-dark shadow">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4.3 4.3a1 1 0 011.4 0L10 8.6l4.3-4.3a1 1 0 111.4 1.4L11.4 10l4.3 4.3a1 1 0 01-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 01-1.4-1.4L8.6 10 4.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd"/>
                                            </svg>
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
                                <p class="text-base leading-7 text-brand-primary/75">
                                    {{ $store->description ? \Illuminate\Support\Str::limit($store->description, 100) : '歡迎使用 DineFlow 進入店家專屬點餐體驗。' }}
                                </p>

                                <div class="mt-5 space-y-2 text-base text-brand-primary/70">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-brand-primary/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.5a.75.75 0 10-1.5 0v3.25c0 .2.08.39.22.53l2 2a.75.75 0 101.06-1.06l-1.78-1.78V6.5z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>營業時段 {{ $store->businessHoursLabel() }}</span>
                                    </div>
                                    @if(!empty($store->address))
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-brand-primary/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4.03 4.86 8.84 5.07 9.04a1.3 1.3 0 001.86 0c.2-.2 5.07-5.01 5.07-9.04a6 6 0 00-6-6zm0 8a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>地址 {{ $store->address }}</span>
                                        </div>
                                    @endif
                                    @if(!empty($store->phone))
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-brand-primary/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M2.4 2.9a1 1 0 011.1-.3l2.2.7a1 1 0 01.7 1v2a1 1 0 01-.3.7l-1 1a12.5 12.5 0 005 5l1-1a1 1 0 01.7-.3h2a1 1 0 011 .7l.7 2.2a1 1 0 01-.3 1.1l-1.4 1.1a2 2 0 01-1.8.3A16.3 16.3 0 012.9 7.1a2 2 0 01.3-1.8L4.3 3.9z"/>
                                            </svg>
                                            <span>電話 {{ $store->phone }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-auto pt-6">
                                    @if($store->is_active)
                                        <a href="{{ route('stores.enter', ['store' => $store]) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-primary px-4 py-3 text-base font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L13.59 11H4a1 1 0 110-2h9.59l-3.3-3.3a1 1 0 010-1.4z" clip-rule="evenodd"/>
                                            </svg>
                                            進入店家
                                        </a>
                                    @else
                                        <button disabled class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-2xl bg-slate-200 px-4 py-3 text-base font-semibold text-slate-500">
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
                    <div class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand-soft/70 text-brand-primary">
                        <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4.03 4.86 8.84 5.07 9.04a1.3 1.3 0 001.86 0c.2-.2 5.07-5.01 5.07-9.04a6 6 0 00-6-6zm0 8a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-brand-dark">找不到符合條件的店家</h3>
                    <p class="mt-3 text-lg text-brand-primary/75">
                        可以換個關鍵字，或先看看首頁推薦的店家。
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('home') }}" class="inline-flex items-center rounded-2xl border border-brand-soft/70 bg-white px-5 py-3 text-base font-semibold text-brand-primary transition hover:bg-brand-soft/20">
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
                <h2 class="inline-flex items-center gap-2 text-3xl font-bold tracking-tight text-brand-dark">
                    <svg class="h-7 w-7 text-brand-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h2a1 1 0 010 2H5v10h1a1 1 0 110 2H4a1 1 0 01-1-1V4zm4 1a1 1 0 011-1h8a1 1 0 011 1v2.5a1 1 0 11-2 0V6H9v2.5a1 1 0 11-2 0V5zm0 7a1 1 0 011-1h6a1 1 0 110 2H8a1 1 0 01-1-1zm0 4a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                    點餐流程
                </h2>
                <p class="mt-3 text-lg text-brand-primary/75">三個步驟就能完成從掃碼到送單的整體體驗。</p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-[1.75rem] border border-brand-soft/60 bg-brand-soft/18 p-8 text-center">
                    <div class="text-3xl font-bold text-brand-primary">01</div>
                    <div class="mx-auto mt-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-brand-primary shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 3h6v6H3V3zm1.5 1.5v3h3v-3h-3zM11 3h6v6h-6V3zm1.5 1.5v3h3v-3h-3zM3 11h6v6H3v-6zm1.5 1.5v3h3v-3h-3zM12 11a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1v-1a1 1 0 10-2 0v1h-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-bold text-brand-dark">掃描 QR Code</h3>
                    <p class="mt-3 text-base leading-7 text-brand-primary/75">顧客掃碼後直接進入店家專屬點餐頁，無需下載 App。</p>
                </div>
                <div class="rounded-[1.75rem] border border-brand-soft/60 bg-brand-soft/18 p-8 text-center">
                    <div class="text-3xl font-bold text-brand-primary">02</div>
                    <div class="mx-auto mt-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-brand-primary shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M2 4a1 1 0 011-1h8a1 1 0 110 2H4v9h9v-7a1 1 0 112 0v8a1 1 0 01-1 1H3a1 1 0 01-1-1V4zm11.3-.7a1 1 0 011.4 0l3 3a1 1 0 01-1.4 1.4L15 6.4V14a1 1 0 11-2 0V6.4l-1.3 1.3a1 1 0 01-1.4-1.4l3-3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-bold text-brand-dark">瀏覽菜單並加入購物車</h3>
                    <p class="mt-3 text-base leading-7 text-brand-primary/75">透過分類、商品圖片與數量控制，快速完成點餐。</p>
                </div>
                <div class="rounded-[1.75rem] border border-brand-soft/60 bg-brand-soft/18 p-8 text-center">
                    <div class="text-3xl font-bold text-brand-primary">03</div>
                    <div class="mx-auto mt-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-brand-primary shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M2.6 9.2a1 1 0 01.42-1.75l13-4a1 1 0 011.25 1.25l-4 13a1 1 0 01-1.75.42l-2.6-3.45a1 1 0 01-.18-.52l-.18-2.12-2.12-.18a1 1 0 01-.52-.18L2.6 9.2zm3.48.16l3.43.3a1 1 0 01.9.9l.3 3.43 2.59-8.41-8.41 2.59z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-bold text-brand-dark">送出訂單</h3>
                    <p class="mt-3 text-base leading-7 text-brand-primary/75">系統立即產生訂單摘要與編號，前後台都能快速對單。</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="border-t border-brand-soft/60 bg-brand-dark">
        <div class="mx-auto max-w-7xl px-6 py-8 text-center text-white/80 lg:px-8">
            <div class="text-base font-semibold text-white">DineFlow</div>
            <div class="mt-2 text-base">QR Code 點餐體驗平台</div>
        </div>
    </footer>
</div>
@endsection
