@extends('layouts.app')

@section('title', __('home.full_intro_title') . ' | ' . config('app.name', 'DineFlow'))
@section('meta_description', __('home.full_intro_desc'))
@section('canonical', route('product.intro'))
@section('meta_robots', 'index,follow,max-image-preview:large')
@section('meta_image', asset('images/product-intro/productManagement.png'))

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => __('home.full_intro_title'),
    'description' => __('home.full_intro_desc'),
    'url' => route('product.intro'),
    'inLanguage' => str_replace('_', '-', app()->getLocale()),
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => config('app.name', 'DineFlow'),
        'url' => url('/'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
@php
    $heroSnapshots = [
        [
            'title' => __('home.full_intro_flow_step_1_title'),
            'image' => asset('images/product-intro/productManagement.png'),
        ],
        [
            'title' => __('home.full_intro_flow_step_4_title'),
            'image' => asset('images/product-intro/billboard.png'),
        ],
        [
            'title' => __('home.full_intro_flow_step_5_title'),
            'image' => asset('images/product-intro/ticket.png'),
        ],
    ];

    $kpiCards = [
        [
            'number' => '01',
            'title' => __('home.full_intro_kpi_1_title'),
            'desc' => __('home.full_intro_kpi_1_desc'),
        ],
        [
            'number' => '02',
            'title' => __('home.full_intro_kpi_2_title'),
            'desc' => __('home.full_intro_kpi_2_desc'),
        ],
        [
            'number' => '03',
            'title' => __('home.full_intro_kpi_3_title'),
            'desc' => __('home.full_intro_kpi_3_desc'),
        ],
    ];

    $flowSteps = [
        [
            'number' => 1,
            'title' => __('home.full_intro_flow_step_1_title'),
            'desc' => __('home.full_intro_flow_step_1_desc'),
            'image' => asset('images/product-intro/productManagement.png'),
            'badge' => 'bg-brand-primary text-white',
        ],
        [
            'number' => 2,
            'title' => __('home.full_intro_flow_step_2_title'),
            'desc' => __('home.full_intro_flow_step_2_desc'),
            'image' => asset('images/product-intro/qrcode.png'),
            'badge' => 'bg-brand-primary text-white',
        ],
        [
            'number' => 3,
            'title' => __('home.full_intro_flow_step_3_title'),
            'desc' => __('home.full_intro_flow_step_3_desc'),
            'image' => asset('images/product-intro/menu.png'),
            'badge' => 'bg-brand-primary text-white',
        ],
        [
            'number' => 4,
            'title' => __('home.full_intro_flow_step_4_title'),
            'desc' => __('home.full_intro_flow_step_4_desc'),
            'image' => asset('images/product-intro/billboard.png'),
            'badge' => 'bg-brand-primary text-white',
        ],
        [
            'number' => 5,
            'title' => __('home.full_intro_flow_step_5_title'),
            'desc' => __('home.full_intro_flow_step_5_desc'),
            'image' => asset('images/product-intro/ticket.png'),
            'badge' => 'bg-brand-highlight text-brand-dark',
        ],
        [
            'number' => 6,
            'title' => __('home.full_intro_flow_step_6_title'),
            'desc' => __('home.full_intro_flow_step_6_desc'),
            'image' => asset('images/product-intro/financial.png'),
            'badge' => 'bg-brand-primary text-white',
        ],
    ];

    $featureCards = [
        [
            'title' => __('home.full_intro_feature_1_title'),
            'desc' => __('home.full_intro_feature_1_desc'),
        ],
        [
            'title' => __('home.full_intro_feature_2_title'),
            'desc' => __('home.full_intro_feature_2_desc'),
        ],
        [
            'title' => __('home.full_intro_feature_3_title'),
            'desc' => __('home.full_intro_feature_3_desc'),
        ],
        [
            'title' => __('home.full_intro_feature_4_title'),
            'desc' => __('home.full_intro_feature_4_desc'),
        ],
    ];

    $simulatorCopy = app()->getLocale() === 'en'
        ? [
            'section_badge' => 'Interactive Demo',
            'section_title' => 'Watch the customer and merchant flow move together',
            'section_desc' => 'Click through the ordering journey from scan to pickup, or let it autoplay to show how both screens stay in sync.',
            'progress_label' => 'Demo Stages',
            'detail_label' => 'Current Focus',
            'customer_side' => 'Customer side',
            'merchant_side' => 'Merchant side',
            'hint_title' => 'How to use it',
            'hints' => [
                'Click any stage on the left to jump to that moment in the journey.',
                'Switch between split view, customer-only, or merchant-only focus.',
                'Buttons inside each screen also push the scenario forward.',
            ],
            'views' => [
                'split' => 'Split View',
                'customer' => 'Customer Only',
                'merchant' => 'Merchant Only',
            ],
            'auto' => 'Autoplay',
            'pause' => 'Pause',
            'reset' => 'Restart',
            'stages' => [
                [
                    'label' => 'Scan In',
                    'title' => 'A guest enters the dedicated table session',
                    'summary' => 'Scanning the QR code automatically loads the store, table, and ordering mode.',
                    'customer' => 'The customer skips app download and starts directly from the table or takeout link.',
                    'merchant' => 'The store has already prepared the QR entry point and menu context in advance.',
                ],
                [
                    'label' => 'Build Cart',
                    'title' => 'Browse the menu and customize the meal',
                    'summary' => 'Items, add-ons, and notes can be selected quickly while the cart updates instantly.',
                    'customer' => 'The guest compares categories, chooses a signature meal, and toggles dine-in or takeout.',
                    'merchant' => 'No one needs to manually repeat the menu at the counter before the order is sent.',
                ],
                [
                    'label' => 'Send Order',
                    'title' => 'The order reaches the board immediately',
                    'summary' => 'Once submitted, the order appears in the merchant queue without verbal handoff.',
                    'customer' => 'The customer gets confirmation, an order number, and a clear next step right away.',
                    'merchant' => 'Kitchen and cashier can see a fresh order card the moment the guest checks out.',
                ],
                [
                    'label' => 'Prepare',
                    'title' => 'Preparation status is shared in real time',
                    'summary' => 'The merchant moves the order into production and the guest sees the progress update.',
                    'customer' => 'The order page changes from submitted to preparing, including an estimated wait time.',
                    'merchant' => 'Front and back of house stay aligned on one board instead of passing status verbally.',
                ],
                [
                    'label' => 'Pickup',
                    'title' => 'Pickup notification is pushed to the customer',
                    'summary' => 'Marking the order ready instantly tells the guest where and when to collect it.',
                    'customer' => 'The guest sees a ready notice and can head to the counter with confidence.',
                    'merchant' => 'The board clears the order into the ready lane and the service cycle is complete.',
                ],
            ],
            'customer' => [
                'label' => 'Customer Experience',
                'headline' => 'Table-side ordering screen',
                'store' => 'DineFlow Bistro',
                'table' => 'Table A12',
                'mode' => 'Dine-in',
                'alt_mode' => 'Takeout',
                'mode_toggle' => 'Toggle order mode',
                'badge' => 'No app download',
                'categories' => ['Signature', 'Set Meal', 'Tea'],
                'cart_label' => 'Cart Summary',
                'total_label' => 'Total',
                'empty_title' => 'Start from the QR entry point',
                'empty_desc' => 'The table QR pulls store and seating context in automatically.',
                'status_scan' => 'Scan the QR and the system enters the correct store flow immediately.',
                'status_add' => 'The cart now has the featured meal. Notes and add-ons are ready to use.',
                'status_submit' => 'One more tap sends the order straight into the store queue.',
                'status_prepare' => 'The order has been accepted and the kitchen is already preparing it.',
                'status_ready' => 'Pickup is ready. The page can show a clear handoff message to the guest.',
                'toast_sent' => 'Order DF-2048 submitted',
                'toast_prepare' => 'Preparing now, about 8 minutes',
                'toast_ready' => 'Table A12 order is ready for pickup',
                'action_scan' => 'Simulate QR scan',
                'action_add' => 'Add the featured meal',
                'action_submit' => 'Send order',
                'action_track' => 'Check status update',
                'action_notice' => 'See pickup notice',
                'action_restart' => 'Replay from start',
                'items' => [
                    [
                        'name' => 'Charred Chicken Rice Bowl',
                        'desc' => 'Best-seller with seasonal vegetables and warm grain rice.',
                        'price' => 285,
                        'tag' => 'Popular',
                    ],
                    [
                        'name' => 'Truffle Egg Sandwich',
                        'desc' => 'Soft bread, creamy eggs, and a strong brunch profile.',
                        'price' => 220,
                        'tag' => 'Brunch',
                    ],
                    [
                        'name' => 'Yuzu Sparkling Tea',
                        'desc' => 'Citrus finish for guests who want a lighter pairing.',
                        'price' => 160,
                        'tag' => 'Drink',
                    ],
                ],
                'addon' => [
                    'name' => 'Osmanthus Oolong',
                    'meta' => 'Less ice / half sugar',
                    'price' => 45,
                ],
                'currency' => 'NT$',
            ],
            'merchant' => [
                'label' => 'Merchant Console',
                'headline' => 'Live order board',
                'badge' => 'Receiving in real time',
                'columns' => [
                    ['key' => 'new', 'label' => 'New Orders', 'empty' => 'Waiting for new orders'],
                    ['key' => 'preparing', 'label' => 'Preparing', 'empty' => 'Kitchen queue is clear'],
                    ['key' => 'ready', 'label' => 'Ready', 'empty' => 'Ready-for-pickup lane is empty'],
                ],
                'metrics' => [
                    ['label' => 'Orders today', 'value' => '128'],
                    ['label' => 'Avg. prep', 'value' => '7.5 min'],
                    ['label' => 'Repeat rate', 'value' => '32%'],
                ],
                'status_new' => 'Awaiting action',
                'status_preparing' => 'Preparing',
                'status_ready' => 'Ready for pickup',
                'action_waiting' => 'Waiting for guest order',
                'action_accept' => 'Start preparing',
                'action_ready' => 'Mark ready',
                'action_complete' => 'Finish demo round',
                'notes' => [
                    'The board is idle, but the QR entry point is already active for customers.',
                    'Guests are choosing items, so the team can stay focused on service instead of manual order taking.',
                    'A fresh order has entered the queue and can move straight into preparation.',
                    'The order is in production, and the customer can see the same progress right away.',
                    'Pickup is ready and the board has completed this service cycle.',
                ],
            ],
        ]
        : [
            'section_badge' => '互動模擬',
            'section_title' => '商家與消費者，兩個畫面同步模擬',
            'section_desc' => '用可點擊的方式重現 QR 點餐從掃碼、選餐、送單到取餐提醒，並可切換單一角色或雙視角一起觀看。',
            'progress_label' => '模擬階段',
            'detail_label' => '當前亮點',
            'customer_side' => '消費者端',
            'merchant_side' => '商家端',
            'hint_title' => '怎麼操作',
            'hints' => [
                '點左側任一階段，可以直接跳到該情境。',
                '可切換雙視角、只看消費者，或只看商家畫面。',
                '畫面內的按鈕也能直接推進流程，兩端狀態會同步更新。',
            ],
            'views' => [
                'split' => '雙視角',
                'customer' => '只看消費者',
                'merchant' => '只看商家',
            ],
            'auto' => '自動播放',
            'pause' => '暫停播放',
            'reset' => '重新播放',
            'stages' => [
                [
                    'label' => '掃碼入桌',
                    'title' => '顧客掃碼後進入專屬桌次',
                    'summary' => '掃描桌邊 QR 後，系統會自動帶入店家、桌號與點餐模式。',
                    'customer' => '消費者不必下載 App，直接進入桌邊或外帶專屬點餐入口。',
                    'merchant' => '店家預先佈署好 QR 與菜單情境，現場不用再口頭引導一遍。',
                ],
                [
                    'label' => '選餐加購',
                    'title' => '快速選餐並客製需求',
                    'summary' => '顧客瀏覽熱門餐點、切換內用外帶，購物車與備註即時更新。',
                    'customer' => '客人能快速比較品項、加入主餐與飲品，也能先完成加料與甜度偏好。',
                    'merchant' => '櫃台不需要先人工介紹整張菜單，能把人力留給出餐與接待。',
                ],
                [
                    'label' => '送單入列',
                    'title' => '訂單送出後立即進入店家看板',
                    'summary' => '一送單，商家看板就會收到新單，不再依賴人工轉述。',
                    'customer' => '消費者立刻看到訂單編號與下一步提示，知道自己已成功送單。',
                    'merchant' => '前台、廚房與收銀能同時看到新訂單，降低漏單與口頭確認成本。',
                ],
                [
                    'label' => '製作追蹤',
                    'title' => '製作進度雙邊同步更新',
                    'summary' => '店家開始製作後，顧客端也同步看到製作中與預估時間。',
                    'customer' => '顧客不用反覆詢問櫃台，畫面就能直接顯示餐點製作中的狀態。',
                    'merchant' => '前後場透過同一張訂單卡協作，現場節奏更穩定也更清楚。',
                ],
                [
                    'label' => '叫號取餐',
                    'title' => '完成出餐並推送取餐提醒',
                    'summary' => '店家一標記可取餐，消費者畫面就會收到提醒與取餐指示。',
                    'customer' => '顧客知道該何時前往櫃台，不必反覆查看或等待口頭叫號。',
                    'merchant' => '看板將訂單移到可取餐欄位，整個服務循環完整收斂。',
                ],
            ],
            'customer' => [
                'label' => '消費者畫面',
                'headline' => '桌邊點餐模擬',
                'store' => 'DineFlow Bistro',
                'table' => 'A12 桌',
                'mode' => '內用',
                'alt_mode' => '外帶',
                'mode_toggle' => '切換點餐模式',
                'badge' => '免下載 App',
                'categories' => ['招牌主餐', '套餐加購', '茶飲'],
                'cart_label' => '購物車摘要',
                'total_label' => '合計',
                'empty_title' => '先從桌邊 QR 進入點餐',
                'empty_desc' => '掃碼後會直接帶入桌號與店家情境，不需要重複輸入。',
                'status_scan' => '掃描 QR 後，系統會直接進入對應門市與桌次的點餐流程。',
                'status_add' => '主餐已加入購物車，加料、備註與飲品都能一起處理。',
                'status_submit' => '再按一次送出訂單，店家後台就會立即收到新單。',
                'status_prepare' => '訂單已成立，廚房與櫃台正在同步處理這張訂單。',
                'status_ready' => '餐點已完成，頁面會直接提示顧客前往櫃台取餐。',
                'toast_sent' => '訂單 DF-2048 已送出',
                'toast_prepare' => '餐點製作中，預估 8 分鐘',
                'toast_ready' => 'A12 餐點可前往櫃台取餐',
                'action_scan' => '模擬掃碼進入',
                'action_add' => '加入招牌套餐',
                'action_submit' => '送出訂單',
                'action_track' => '查看出餐進度',
                'action_notice' => '查看取餐提醒',
                'action_restart' => '重新體驗流程',
                'items' => [
                    [
                        'name' => '炙燒雞腿溫沙拉飯',
                        'desc' => '人氣主餐，附季節時蔬與溫熱穀飯，適合內用主推。',
                        'price' => 285,
                        'tag' => '熱銷',
                    ],
                    [
                        'name' => '松露滑蛋可頌堡',
                        'desc' => '早午餐常點款，口感柔軟、出餐速度快。',
                        'price' => 220,
                        'tag' => '早午餐',
                    ],
                    [
                        'name' => '柚香氣泡冷泡茶',
                        'desc' => '清爽搭配系飲品，適合做套餐加購。',
                        'price' => 160,
                        'tag' => '飲品',
                    ],
                ],
                'addon' => [
                    'name' => '桂花烏龍',
                    'meta' => '少冰 / 半糖',
                    'price' => 45,
                ],
                'currency' => 'NT$',
            ],
            'merchant' => [
                'label' => '商家畫面',
                'headline' => '即時接單看板',
                'badge' => '即時接單中',
                'columns' => [
                    ['key' => 'new', 'label' => '新訂單', 'empty' => '等待新單進入'],
                    ['key' => 'preparing', 'label' => '製作中', 'empty' => '目前廚房節奏平穩'],
                    ['key' => 'ready', 'label' => '可取餐', 'empty' => '尚未有待取餐訂單'],
                ],
                'metrics' => [
                    ['label' => '今日訂單', 'value' => '128'],
                    ['label' => '平均出餐', 'value' => '7.5 分'],
                    ['label' => '回訪率', 'value' => '32%'],
                ],
                'status_new' => '待接單',
                'status_preparing' => '製作中',
                'status_ready' => '可取餐',
                'action_waiting' => '等待顧客送單',
                'action_accept' => '開始製作',
                'action_ready' => '標記可取餐',
                'action_complete' => '完成一輪示範',
                'notes' => [
                    '系統待命中，但桌邊 QR 與點餐入口已經準備完成。',
                    '顧客正在選餐，現場人員不用先人工抄單或逐項介紹。',
                    '新訂單已進入看板，可直接分派給廚房開始處理。',
                    '訂單進入製作中，顧客端也會同步看到目前進度。',
                    '餐點已可取餐，這輪服務流程已經順利完成。',
                ],
            ],
        ];
@endphp
<style>
    .intro-reveal {
        opacity: 0;
        transform: translateY(30px) scale(0.98);
        transition:
            opacity 700ms ease,
            transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
        transition-delay: var(--delay, 0ms);
    }

    .intro-reveal.is-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .intro-hero-orb {
        animation: introFloat 16s ease-in-out infinite;
    }

    .intro-hero-orb--slow {
        animation-duration: 22s;
        animation-delay: -5s;
    }

    .intro-hero-strip {
        position: relative;
        overflow: hidden;
    }

    .intro-hero-strip::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(130deg, rgba(255, 255, 255, 0.22), transparent 45%, rgba(255, 255, 255, 0.1));
        pointer-events: none;
    }

    .intro-kpi-card {
        transition:
            transform 260ms ease,
            box-shadow 260ms ease,
            border-color 260ms ease;
    }

    .intro-kpi-card:hover {
        transform: translateY(-6px);
        border-color: rgba(236, 144, 87, 0.45);
        box-shadow: 0 26px 52px rgba(90, 30, 14, 0.14);
    }

    .intro-flow-card {
        position: relative;
        overflow: hidden;
        transition:
            transform 280ms ease,
            box-shadow 280ms ease,
            border-color 280ms ease;
    }

    .intro-flow-card::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: radial-gradient(circle at top right, rgba(246, 174, 45, 0.18), transparent 36%);
        opacity: 0;
        transition: opacity 280ms ease;
    }

    .intro-flow-card:hover {
        transform: translateY(-4px);
        border-color: rgba(236, 144, 87, 0.4);
        box-shadow: 0 30px 56px rgba(90, 30, 14, 0.14);
    }

    .intro-flow-card:hover::before {
        opacity: 1;
    }

    .intro-flow-image-wrap {
        position: relative;
        overflow: hidden;
    }

    .intro-flow-image-wrap::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(160deg, rgba(255, 255, 255, 0.22), transparent 48%);
        pointer-events: none;
    }

    .intro-flow-image {
        transition: transform 800ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .intro-flow-card:hover .intro-flow-image {
        transform: scale(1.04);
    }

    .intro-feature-card {
        transition:
            transform 260ms ease,
            box-shadow 260ms ease;
    }

    .intro-feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 22px 48px rgba(90, 30, 14, 0.12);
    }

    .intro-cta-pulse {
        animation: introPulse 2.9s ease-in-out infinite;
    }

    .intro-simulator-shell {
        position: relative;
        overflow: hidden;
        border-radius: 2rem;
        border: 1px solid rgba(236, 144, 87, 0.18);
        background:
            radial-gradient(circle at top left, rgba(246, 208, 90, 0.18), transparent 26%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 247, 240, 0.96));
        box-shadow: 0 30px 80px rgba(90, 30, 14, 0.1);
    }

    .intro-simulator-shell::before,
    .intro-simulator-shell::after {
        content: '';
        position: absolute;
        border-radius: 999px;
        pointer-events: none;
        filter: blur(14px);
        opacity: 0.8;
    }

    .intro-simulator-shell::before {
        top: -3rem;
        right: -2rem;
        width: 14rem;
        height: 14rem;
        background: rgba(236, 144, 87, 0.12);
    }

    .intro-simulator-shell::after {
        left: -4rem;
        bottom: -4rem;
        width: 16rem;
        height: 16rem;
        background: rgba(246, 208, 90, 0.16);
    }

    .intro-simulator-grid {
        position: relative;
        isolation: isolate;
    }

    .intro-simulator-panel {
        position: relative;
        overflow: hidden;
        border-radius: 1.85rem;
        border: 1px solid rgba(236, 144, 87, 0.18);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(255, 249, 244, 0.9));
        box-shadow: 0 20px 48px rgba(90, 30, 14, 0.08);
    }

    .intro-simulator-panel::after {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(125deg, rgba(255, 255, 255, 0.16), transparent 34%),
            linear-gradient(180deg, transparent, rgba(90, 30, 14, 0.03));
        pointer-events: none;
    }

    .intro-view-toggle {
        transition:
            transform 220ms ease,
            background-color 220ms ease,
            color 220ms ease,
            border-color 220ms ease,
            box-shadow 220ms ease;
    }

    .intro-view-toggle:hover {
        transform: translateY(-1px);
    }

    .intro-stage-card {
        position: relative;
        transition:
            transform 240ms ease,
            border-color 240ms ease,
            background-color 240ms ease,
            box-shadow 240ms ease;
    }

    .intro-stage-card:hover {
        transform: translateY(-2px);
        border-color: rgba(236, 144, 87, 0.32);
    }

    .intro-stage-card.is-active {
        border-color: rgba(236, 144, 87, 0.42);
        background: linear-gradient(180deg, rgba(236, 144, 87, 0.12), rgba(255, 255, 255, 0.95));
        box-shadow: 0 18px 32px rgba(90, 30, 14, 0.08);
    }

    .intro-stage-card.is-complete .intro-stage-index {
        background: #5a1e0e;
        color: #ffffff;
    }

    .intro-stage-card.is-active .intro-stage-index {
        background: #f6d05a;
        color: #5a1e0e;
        box-shadow: 0 0 0 0.35rem rgba(246, 208, 90, 0.22);
    }

    .intro-stage-index {
        transition:
            background-color 240ms ease,
            color 240ms ease,
            box-shadow 240ms ease;
    }

    .intro-phone-frame {
        position: relative;
        max-width: 24rem;
        margin-inline: auto;
        padding: 0.8rem;
        border-radius: 2.3rem;
        background:
            linear-gradient(155deg, rgba(22, 23, 29, 0.98), rgba(64, 46, 37, 0.98)),
            linear-gradient(180deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.02));
        box-shadow:
            0 32px 72px rgba(26, 12, 9, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .intro-phone-frame::before {
        content: '';
        position: absolute;
        top: 0.62rem;
        left: 50%;
        width: 35%;
        height: 1.2rem;
        transform: translateX(-50%);
        border-radius: 999px;
        background: rgba(5, 6, 8, 0.9);
        z-index: 2;
    }

    .intro-phone-screen {
        position: relative;
        overflow: hidden;
        min-height: 33rem;
        border-radius: 1.7rem;
        background:
            radial-gradient(circle at top, rgba(246, 208, 90, 0.24), transparent 24%),
            linear-gradient(180deg, #fff8f1, #ffffff 38%, #fff7ef);
    }

    .intro-phone-screen::after {
        content: '';
        position: absolute;
        inset: 0;
        border: 1px solid rgba(255, 255, 255, 0.78);
        border-radius: inherit;
        pointer-events: none;
    }

    .intro-console-frame {
        position: relative;
        overflow: hidden;
        border-radius: 1.8rem;
        background:
            radial-gradient(circle at top right, rgba(246, 208, 90, 0.18), transparent 24%),
            linear-gradient(180deg, rgba(34, 21, 17, 0.98), rgba(68, 36, 21, 0.98));
        box-shadow: 0 28px 70px rgba(26, 12, 9, 0.28);
    }

    .intro-console-frame::after {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
            linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px);
        background-size: 2.8rem 2.8rem;
        mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.9), transparent 90%);
        pointer-events: none;
        opacity: 0.36;
    }

    .intro-menu-card {
        transition:
            transform 220ms ease,
            border-color 220ms ease,
            box-shadow 220ms ease,
            background-color 220ms ease;
    }

    .intro-menu-card:hover {
        transform: translateY(-2px);
        border-color: rgba(236, 144, 87, 0.32);
        box-shadow: 0 16px 26px rgba(90, 30, 14, 0.08);
    }

    .intro-menu-card.is-active {
        border-color: rgba(236, 144, 87, 0.42);
        background: rgba(236, 144, 87, 0.08);
    }

    .intro-live-dot {
        animation: introBlink 2.1s ease-in-out infinite;
    }

    .intro-order-card {
        animation: introCardFloat 4s ease-in-out infinite;
        transition:
            transform 260ms ease,
            box-shadow 260ms ease,
            border-color 260ms ease;
    }

    .intro-order-card.is-hot {
        box-shadow: 0 20px 42px rgba(246, 208, 90, 0.14);
        border-color: rgba(246, 208, 90, 0.28);
    }

    .intro-status-pill {
        backdrop-filter: blur(10px);
    }

    .intro-metric-bar {
        position: relative;
        overflow: hidden;
    }

    .intro-metric-bar::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.28), transparent);
        transform: translateX(-100%);
        animation: introSweep 3.8s ease-in-out infinite;
    }

    .intro-toast {
        animation: introToastRise 480ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .intro-device-switch {
        transition:
            transform 220ms ease,
            opacity 220ms ease;
    }

    .intro-customer-shell {
        background: #fff7ed;
    }

    .intro-customer-header {
        position: sticky;
        top: 0;
        z-index: 2;
        border-bottom: 1px solid #fed7aa;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(10px);
    }

    .intro-customer-scene {
        min-height: 23rem;
    }

    .intro-customer-menu-layout {
        display: grid;
        grid-template-columns: 4.85rem minmax(0, 1fr);
        gap: 0.8rem;
        align-items: start;
    }

    .intro-customer-sidebar {
        position: sticky;
        top: 0.35rem;
        align-self: start;
    }

    .intro-customer-category {
        width: 100%;
        border: 1px solid #fed7aa;
        background: rgba(255, 255, 255, 0.92);
        color: #9a3412;
        box-shadow: 0 10px 24px rgba(251, 146, 60, 0.08);
        transition:
            transform 220ms ease,
            border-color 220ms ease,
            background-color 220ms ease,
            box-shadow 220ms ease;
    }

    .intro-customer-category:hover {
        transform: translateY(-1px);
        border-color: #fb923c;
    }

    .intro-customer-category.is-active {
        border-color: #f97316;
        background: linear-gradient(180deg, #fff7ed, #fed7aa);
        box-shadow: 0 18px 34px rgba(251, 146, 60, 0.16);
    }

    .intro-customer-category-index {
        color: #f97316;
    }

    .intro-customer-cart-item {
        border: 1px solid #fed7aa;
        background: linear-gradient(180deg, rgba(255, 247, 237, 0.92), rgba(255, 255, 255, 0.96));
    }

    .intro-customer-product {
        border: 1px solid #fed7aa;
        background: #ffffff;
        box-shadow: 0 10px 26px rgba(251, 146, 60, 0.08);
        transition:
            transform 220ms ease,
            border-color 220ms ease,
            box-shadow 220ms ease;
    }

    .intro-customer-product:hover {
        transform: translateY(-2px);
        border-color: #fb923c;
        box-shadow: 0 18px 36px rgba(251, 146, 60, 0.12);
    }

    .intro-customer-product.is-active {
        border-color: #f97316;
        box-shadow: 0 0 0 1px rgba(249, 115, 22, 0.14), 0 22px 40px rgba(251, 146, 60, 0.16);
    }

    .intro-customer-product-meta {
        min-width: 0;
    }

    .intro-customer-product-action {
        margin-top: 1rem;
        border-top: 1px solid #ffedd5;
        padding-top: 0.9rem;
    }

    .intro-customer-product-button {
        width: 100%;
    }

    .intro-customer-bottom {
        position: sticky;
        bottom: 0;
        z-index: 3;
        border-top: 1px solid #fed7aa;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(10px);
    }

    .intro-customer-success {
        border: 1px solid #bbf7d0;
        background: #ffffff;
        box-shadow: 0 16px 34px rgba(34, 197, 94, 0.08);
    }

    .intro-customer-status-card {
        border: 1px solid #fdba74;
        background: #fff7ed;
    }

    .intro-customer-status-card.is-ready {
        border-color: #86efac;
        background: #f0fdf4;
    }

    .intro-customer-qty {
        border: 1px solid #fed7aa;
        background: #ffffff;
        color: #ea580c;
    }

    .intro-board-shell {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(2, 6, 23, 0.98));
        color: #ffffff;
    }

    .intro-board-toolbar {
        border: 1px solid rgba(71, 85, 105, 0.68);
        background: rgba(15, 23, 42, 0.82);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .intro-board-filter {
        border: 1px solid rgba(71, 85, 105, 0.78);
        color: #94a3b8;
        transition:
            background-color 220ms ease,
            color 220ms ease,
            border-color 220ms ease;
    }

    .intro-board-filter.is-active {
        color: #ffffff;
    }

    .intro-board-card {
        border: 1px solid rgba(71, 85, 105, 0.62);
        background: rgba(15, 23, 42, 0.74);
        box-shadow: 0 20px 48px rgba(2, 6, 23, 0.34);
    }

    .intro-board-card.is-pending {
        border-color: rgba(59, 130, 246, 0.5);
    }

    .intro-board-card.is-preparing {
        border-color: rgba(96, 165, 250, 0.42);
    }

    .intro-board-card.is-ready {
        border-color: rgba(16, 185, 129, 0.48);
    }

    .intro-board-item {
        border: 1px solid rgba(71, 85, 105, 0.58);
        background: rgba(15, 23, 42, 0.46);
        transition:
            border-color 220ms ease,
            background-color 220ms ease;
    }

    .intro-board-item.is-complete {
        border-color: rgba(52, 211, 153, 0.42);
        background: rgba(6, 78, 59, 0.24);
    }

    .intro-board-empty {
        border: 1px dashed rgba(71, 85, 105, 0.72);
        background: rgba(15, 23, 42, 0.44);
    }

    .intro-board-action {
        transition:
            transform 220ms ease,
            background-color 220ms ease,
            opacity 220ms ease;
    }

    .intro-board-action:hover {
        transform: translateY(-1px);
    }

    .intro-board-action:disabled {
        transform: none;
    }

    @keyframes introFloat {
        0%, 100% {
            transform: translate3d(0, 0, 0) scale(1);
        }
        50% {
            transform: translate3d(0, -14px, 0) scale(1.04);
        }
    }

    @keyframes introPulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.03);
        }
    }

    @keyframes introBlink {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.45;
            transform: scale(0.84);
        }
    }

    @keyframes introCardFloat {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-3px);
        }
    }

    @keyframes introSweep {
        0% {
            transform: translateX(-100%);
        }
        100% {
            transform: translateX(135%);
        }
    }

    @keyframes introToastRise {
        0% {
            opacity: 0;
            transform: translateY(10px) scale(0.96);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .intro-reveal,
        .intro-kpi-card,
        .intro-flow-card,
        .intro-flow-image,
        .intro-feature-card,
        .intro-hero-orb,
        .intro-cta-pulse,
        .intro-live-dot,
        .intro-order-card,
        .intro-metric-bar::after,
        .intro-toast {
            animation: none;
            transition: none;
            transform: none;
            opacity: 1;
        }
    }
</style>
<div class="min-h-screen bg-brand-soft/20 text-brand-dark">
    <section class="relative isolate overflow-hidden border-b border-brand-soft/70 bg-brand-dark text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.2),_transparent_36%),linear-gradient(130deg,_rgba(90,30,14,0.98),_rgba(236,144,87,0.9))]"></div>
        <div class="intro-hero-orb absolute -left-12 top-20 h-52 w-52 rounded-full bg-brand-highlight/20 blur-3xl"></div>
        <div class="intro-hero-orb intro-hero-orb--slow absolute -right-10 bottom-6 h-48 w-48 rounded-full bg-brand-soft/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-[92rem] px-6 py-16 lg:px-8 lg:py-24 2xl:max-w-[98rem]">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1.08fr)_minmax(30rem,0.92fr)] lg:items-center">
                <div class="lg:pr-4">
                    <span class="intro-reveal inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-sm font-semibold tracking-[0.2em] text-brand-highlight" data-reveal>
                        {{ __('home.full_intro_badge') }}
                    </span>
                    <h1 class="intro-reveal mt-6 max-w-5xl text-4xl font-bold tracking-tight sm:text-5xl xl:text-[3.55rem] xl:leading-[1.08]" data-reveal style="--delay: 80ms;">
                        {{ __('home.full_intro_title') }}
                    </h1>
                    <p class="intro-reveal mt-5 max-w-3xl text-lg leading-8 text-white/80 sm:text-xl sm:leading-9" data-reveal style="--delay: 140ms;">
                        {{ __('home.full_intro_desc') }}
                    </p>

                    <div class="intro-reveal mt-8 flex flex-wrap gap-4" data-reveal style="--delay: 220ms;">
                        @auth
                            <a href="{{ auth()->user()?->role === 'customer' ? route('join.merchant.register') : route('dashboard') }}" class="intro-cta-pulse inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                                {{ __('home.full_intro_join_button') }}
                            </a>
                        @else
                            <a href="{{ route('register', ['account_type' => 'merchant']) }}" class="intro-cta-pulse inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                                {{ __('home.full_intro_join_button') }}
                            </a>
                        @endauth
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                            {{ __('home.full_intro_back_home') }}
                        </a>
                    </div>

                    <div class="mt-10 grid max-w-5xl gap-4 md:grid-cols-3">
                        @foreach ($heroSnapshots as $snapshot)
                            <div class="intro-reveal intro-hero-strip overflow-hidden rounded-2xl border border-white/15 bg-white/10 p-2 backdrop-blur" data-reveal style="--delay: {{ 280 + ($loop->index * 70) }}ms;">
                                <div class="overflow-hidden rounded-xl border border-white/10 bg-black/20">
                                    <img
                                        src="{{ $snapshot['image'] }}"
                                        alt="{{ $snapshot['title'] }}"
                                        loading="lazy"
                                        class="h-32 w-full object-cover object-top"
                                    >
                                </div>
                                <p class="mt-2 text-xs font-semibold text-white/80">{{ $snapshot['title'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="intro-reveal" data-reveal style="--delay: 180ms;">
                    <div class="rounded-[2rem] border border-white/15 bg-white/10 p-5 shadow-[0_28px_80px_rgba(0,0,0,0.26)] backdrop-blur sm:p-6 xl:p-7">
                        <div class="max-w-xl">
                            <div class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-highlight/80">
                                {{ __('home.badge_qr_ordering') }}
                            </div>
                            <h2 class="mt-3 text-2xl font-bold text-white sm:text-[2rem]">
                                {{ __('home.full_intro_flow_title') }}
                            </h2>
                            <p class="mt-3 text-base leading-7 text-white/75">
                                {{ __('home.full_intro_flow_desc') }}
                            </p>
                        </div>

                        <div class="mt-6 flex justify-center">
                            <x-marketing-video
                                class="mx-auto w-full max-w-[22rem] sm:max-w-[24rem]"
                                :src="asset('video/dineflow_short_video_cta.mp4')"
                                :badge="__('home.full_intro_badge')"
                            />
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                            @foreach ($kpiCards as $kpi)
                                <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-highlight/80">{{ $kpi['number'] }}</div>
                                    <div class="mt-2 text-sm font-semibold leading-6 text-white">{{ $kpi['title'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-brand-soft/60 bg-[linear-gradient(180deg,_rgba(255,248,243,0.96),_rgba(255,255,255,1))] py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="intro-reveal mb-10 text-center" data-reveal>
                <span class="inline-flex items-center gap-2 rounded-full border border-brand-soft/70 bg-white px-4 py-1.5 text-sm font-semibold tracking-[0.18em] text-brand-primary/75">
                    {{ $simulatorCopy['section_badge'] }}
                </span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ $simulatorCopy['section_title'] }}</h2>
                <p class="mx-auto mt-3 max-w-3xl text-lg leading-8 text-brand-primary/75">{{ $simulatorCopy['section_desc'] }}</p>
            </div>

            <div
                x-data="introSimulation(@js($simulatorCopy))"
                x-init="init()"
                class="intro-reveal intro-simulator-shell px-5 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8"
                data-reveal
                style="--delay: 50ms;"
            >
                <div class="intro-simulator-grid grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
                    <div class="space-y-5 xl:sticky xl:top-24 xl:self-start">
                        <div class="intro-simulator-panel p-4 sm:p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ $simulatorCopy['progress_label'] }}</div>
                                    <div class="mt-2 text-sm text-brand-primary/70">{{ $simulatorCopy['section_badge'] }}</div>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-brand-soft/70 bg-white px-3 py-1 text-sm font-semibold text-brand-dark">
                                    <span x-text="step + 1"></span>/{{ count($simulatorCopy['stages']) }}
                                </span>
                            </div>

                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-brand-soft/70">
                                <div
                                    class="h-full rounded-full bg-[linear-gradient(90deg,_rgba(90,30,14,1),_rgba(246,208,90,1))] transition-all duration-700 ease-out"
                                    :style="`width: ${progressWidth()}%`"
                                ></div>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach ($simulatorCopy['stages'] as $stage)
                                    <button
                                        type="button"
                                        @click="setStep({{ $loop->index }})"
                                        class="intro-stage-card w-full rounded-[1.4rem] border border-brand-soft/70 bg-white/85 p-4 text-left"
                                        :class="{ 'is-active': step === {{ $loop->index }}, 'is-complete': step > {{ $loop->index }} }"
                                    >
                                        <div class="flex gap-3">
                                            <span class="intro-stage-index inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-soft/70 text-sm font-bold text-brand-primary">
                                                {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                            <div class="min-w-0">
                                                <div class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-brand-primary/55">{{ $stage['label'] }}</div>
                                                <div class="mt-1 text-sm font-semibold leading-6 text-brand-dark">{{ $stage['title'] }}</div>
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="intro-simulator-panel p-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ $simulatorCopy['detail_label'] }}</div>

                            @foreach ($simulatorCopy['stages'] as $stage)
                                <div x-cloak x-show="step === {{ $loop->index }}" x-transition.opacity.duration.250ms>
                                    <h3 class="mt-3 text-xl font-bold text-brand-dark">{{ $stage['title'] }}</h3>
                                    <p class="mt-2 text-sm leading-7 text-brand-primary/75">{{ $stage['summary'] }}</p>

                                    <div class="mt-4 space-y-3">
                                        <div class="rounded-2xl border border-brand-soft/60 bg-brand-soft/18 p-4">
                                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ $simulatorCopy['customer_side'] }}</div>
                                            <p class="mt-2 text-sm leading-6 text-brand-primary/80">{{ $stage['customer'] }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-brand-soft/60 bg-white p-4">
                                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ $simulatorCopy['merchant_side'] }}</div>
                                            <p class="mt-2 text-sm leading-6 text-brand-primary/80">{{ $stage['merchant'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <div class="mt-5 border-t border-brand-soft/60 pt-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ $simulatorCopy['hint_title'] }}</div>
                                <div class="mt-3 space-y-2.5">
                                    @foreach ($simulatorCopy['hints'] as $hint)
                                        <div class="flex gap-2.5 text-sm leading-6 text-brand-primary/75">
                                            <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-highlight"></span>
                                            <span>{{ $hint }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="intro-simulator-panel p-4 sm:p-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($simulatorCopy['views'] as $viewKey => $viewLabel)
                                        <button
                                            type="button"
                                            @click="setView('{{ $viewKey }}')"
                                            class="intro-view-toggle inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold"
                                            :class="view === '{{ $viewKey }}'
                                                ? 'border-brand-dark bg-brand-dark text-white shadow-[0_14px_28px_rgba(90,30,14,0.18)]'
                                                : 'border-brand-soft/80 bg-white text-brand-primary/80'"
                                        >
                                            {{ $viewLabel }}
                                        </button>
                                    @endforeach
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        @click="toggleAutoplay()"
                                        class="intro-view-toggle inline-flex items-center gap-2 rounded-full border border-brand-soft/80 bg-white px-4 py-2 text-sm font-semibold text-brand-primary/80"
                                    >
                                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-brand-highlight" :class="autoplay ? 'intro-live-dot' : ''"></span>
                                        <span x-text="autoplay ? copy.pause : copy.auto"></span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="reset()"
                                        class="intro-view-toggle inline-flex items-center gap-2 rounded-full border border-brand-soft/80 bg-white px-4 py-2 text-sm font-semibold text-brand-primary/80"
                                    >
                                        {{ $simulatorCopy['reset'] }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5" :class="view === 'split' ? 'xl:grid-cols-2' : 'grid-cols-1'">
                            <div
                                x-cloak
                                x-show="view !== 'merchant'"
                                x-transition.opacity.duration.350ms
                                class="intro-device-switch intro-simulator-panel p-4 sm:p-5"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ $simulatorCopy['customer']['label'] }}</div>
                                        <h3 class="mt-1 text-xl font-bold text-brand-dark">{{ $simulatorCopy['customer']['headline'] }}</h3>
                                    </div>
                                    <span class="inline-flex items-center gap-2 rounded-full border border-brand-soft/70 bg-white px-3 py-1 text-xs font-semibold text-brand-primary/75">
                                        <span class="intro-live-dot inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                        {{ $simulatorCopy['customer']['badge'] }}
                                    </span>
                                </div>

                                <div class="mt-5 intro-phone-frame">
                                    <div class="intro-phone-screen intro-customer-shell">
                                        <div class="intro-customer-header px-4 pb-4 pt-6 sm:px-5">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-medium text-orange-500">DineFlow</p>
                                                    <h4 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">{{ $simulatorCopy['customer']['store'] }}</h4>
                                                    <p class="mt-1 text-sm text-gray-500">{{ __('customer.table_no') }} {{ $simulatorCopy['customer']['table'] }}</p>
                                                </div>
                                                <div class="flex flex-col items-end gap-2">
                                                    <button
                                                        type="button"
                                                        @click="customerTopAction()"
                                                        class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-600 transition hover:bg-orange-100"
                                                    >
                                                        <span x-text="customerTopButtonLabel()"></span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="toggleMode()"
                                                        class="text-xs font-semibold text-orange-600 transition hover:text-orange-700"
                                                    >
                                                        <span x-text="modeLabel()"></span> / {{ $simulatorCopy['customer']['mode_toggle'] }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-4 rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm text-gray-600" x-text="customerBannerText()"></div>
                                        </div>

                                        <section x-cloak x-show="customerScene() === 'menu'" x-transition.opacity.duration.250ms class="intro-customer-scene px-4 py-5 sm:px-5">
                                            <div class="intro-customer-menu-layout">
                                                <aside class="intro-customer-sidebar">
                                                    <div class="flex flex-col gap-2">
                                                        @foreach ($simulatorCopy['customer']['categories'] as $category)
                                                            <button
                                                                type="button"
                                                                @click="selectItem({{ $loop->index }})"
                                                                class="intro-customer-category rounded-[1.35rem] px-3 py-3 text-left"
                                                                :class="{ 'is-active': selectedItem === {{ $loop->index }} }"
                                                            >
                                                                <div class="intro-customer-category-index text-[11px] font-bold tracking-[0.18em]">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                                                                <div class="mt-1 text-xs font-semibold leading-5">{{ $category }}</div>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </aside>

                                                <div class="space-y-4">
                                                    @foreach ($simulatorCopy['customer']['items'] as $item)
                                                        <button
                                                            type="button"
                                                            @click="selectItem({{ $loop->index }})"
                                                            class="intro-customer-product w-full rounded-3xl p-4 text-left"
                                                            :class="{ 'is-active': selectedItem === {{ $loop->index }} }"
                                                        >
                                                            <div class="intro-customer-product-meta">
                                                                <div class="flex flex-wrap items-center gap-2">
                                                                    <h5 class="text-base font-semibold leading-7 text-gray-900">{{ $item['name'] }}</h5>
                                                                    <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-600">{{ $item['tag'] }}</span>
                                                                </div>
                                                                <p class="mt-1 text-sm leading-6 text-gray-500">{{ $item['desc'] }}</p>

                                                                <div class="mt-4 flex items-center gap-2">
                                                                    <span class="text-xl font-bold text-orange-600">{{ $simulatorCopy['customer']['currency'] }} {{ $item['price'] }}</span>
                                                                </div>
                                                            </div>

                                                            <div class="intro-customer-product-action">
                                                                <span class="intro-customer-product-button inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600">
                                                                    {{ __('customer.add_to_cart') }}
                                                                </span>
                                                            </div>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </section>

                                        <section x-cloak x-show="customerScene() === 'cart'" x-transition.opacity.duration.250ms class="intro-customer-scene px-4 py-5 sm:px-5">
                                            <div class="space-y-4">
                                                <div class="rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                                                    <div class="mb-5 flex items-center justify-between">
                                                        <h5 class="text-lg font-bold text-gray-900">{{ __('customer.selected_items') }}</h5>
                                                        <span class="text-sm text-gray-400" x-text="customerItemCountLabel()"></span>
                                                    </div>

                                                    <div class="space-y-3">
                                                        <div class="intro-customer-cart-item rounded-2xl px-4 py-4">
                                                            <div class="flex items-center justify-between gap-4">
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="text-base font-semibold text-gray-900" x-text="currentItemName()"></div>
                                                                    <p class="mt-1 text-sm text-gray-500">{{ __('customer.unit_price') }} <span x-text="formatCurrency(currentItemPrice())"></span></p>
                                                                </div>

                                                                <div class="text-right">
                                                                    <div class="flex items-center justify-end gap-2">
                                                                        <span class="intro-customer-qty inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold">-</span>
                                                                        <span class="min-w-8 text-sm font-semibold text-gray-600">1</span>
                                                                        <span class="intro-customer-qty inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold">+</span>
                                                                    </div>
                                                                    <p class="mt-2 text-base font-bold text-orange-600" x-text="formatCurrency(currentItemPrice())"></p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="intro-customer-cart-item rounded-2xl px-4 py-4">
                                                            <div class="flex items-center justify-between gap-4">
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="flex flex-wrap items-center gap-2">
                                                                        <div class="text-base font-semibold text-gray-900">{{ $simulatorCopy['customer']['addon']['name'] }}</div>
                                                                        <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-600">{{ app()->getLocale() === 'en' ? 'Recommended' : '加購推薦' }}</span>
                                                                    </div>
                                                                    <p class="mt-1 text-sm text-gray-500">{{ $simulatorCopy['customer']['addon']['meta'] }}</p>
                                                                </div>

                                                                <div class="text-right">
                                                                    <p class="text-sm font-semibold text-gray-500">x 1</p>
                                                                    <p class="mt-2 text-base font-bold text-orange-600">{{ $simulatorCopy['customer']['currency'] }} {{ $simulatorCopy['customer']['addon']['price'] }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                                                    <div class="space-y-3">
                                                        <div class="flex items-center justify-between text-sm text-gray-500">
                                                            <span>{{ __('customer.order_total') }}</span>
                                                            <span class="text-lg font-bold text-orange-600" x-text="cartTotal()"></span>
                                                        </div>
                                                        <div class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm text-gray-600">
                                                            {{ __('customer.submit_order_hint') }}
                                                        </div>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="customerAction()"
                                                        class="mt-5 inline-flex h-12 w-full items-center justify-center rounded-2xl bg-orange-500 px-5 text-base font-semibold text-white shadow-sm transition hover:bg-orange-600 active:scale-[0.99]"
                                                    >
                                                        <span x-text="customerActionLabel()"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </section>

                                        <section x-cloak x-show="customerScene() === 'success'" x-transition.opacity.duration.250ms class="intro-customer-scene px-4 py-5 sm:px-5">
                                            <div class="intro-customer-success rounded-3xl p-5 shadow-sm">
                                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                    <div>
                                                        <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-2xl text-green-700">
                                                            &#10003;
                                                        </div>
                                                        <h5 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('customer.order_success_title') }}</h5>
                                                        <p class="mt-2 text-sm leading-6 text-gray-500" x-text="customerToast()"></p>
                                                    </div>

                                                    <div class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-left sm:min-w-[180px]">
                                                        <p class="text-xs font-medium uppercase tracking-wide text-orange-500">{{ __('customer.order_no') }}</p>
                                                        <p class="mt-1 text-base font-bold tracking-tight text-gray-900">DF-2048</p>
                                                    </div>
                                                </div>

                                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                                                        <p class="text-sm text-gray-500">{{ __('customer.order_status') }}</p>
                                                        <p class="mt-1 font-semibold text-orange-600" x-text="customerSuccessStateLabel()"></p>
                                                    </div>
                                                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                                                        <p class="text-sm text-gray-500">{{ __('customer.estimated_ready_time') }}</p>
                                                        <p class="mt-1 font-semibold text-gray-900" x-text="customerEtaLabel()"></p>
                                                    </div>
                                                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                                                        <p class="text-sm text-gray-500">{{ __('customer.order_amount') }}</p>
                                                        <p class="mt-1 font-semibold text-gray-900" x-text="cartTotal()"></p>
                                                    </div>
                                                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                                                        <p class="text-sm text-gray-500">{{ __('customer.table_no') }}</p>
                                                        <p class="mt-1 font-semibold text-gray-900">{{ $simulatorCopy['customer']['table'] }} / <span x-text="modeLabel()"></span></p>
                                                    </div>
                                                </div>

                                                <div class="mt-4 rounded-2xl px-4 py-4" :class="step >= 4 ? 'intro-customer-status-card is-ready' : 'intro-customer-status-card'">
                                                    <p class="text-sm font-semibold" :class="step >= 4 ? 'text-emerald-700' : 'text-orange-700'">{{ __('customer.order_info_section') }}</p>
                                                    <p class="mt-1 text-sm leading-6" :class="step >= 4 ? 'text-emerald-800' : 'text-gray-700'" x-text="customerStatus()"></p>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="customerAction()"
                                                    class="mt-5 inline-flex h-12 w-full items-center justify-center rounded-2xl bg-orange-500 px-5 text-base font-semibold text-white shadow-sm transition hover:bg-orange-600 active:scale-[0.99]"
                                                >
                                                    <span x-text="customerActionLabel()"></span>
                                                </button>
                                            </div>
                                        </section>

                                        <div x-cloak x-show="customerScene() === 'menu'" class="intro-customer-bottom px-4 py-4 sm:px-5">
                                            <div class="flex items-center justify-between gap-3 rounded-2xl bg-gray-900 px-4 py-3 text-white shadow-lg">
                                                <div>
                                                    <p class="text-xs text-gray-300">{{ __('customer.add_to_cart') }}</p>
                                                    <p class="text-sm font-semibold" x-text="currentItemName()"></p>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="selectItem(selectedItem)"
                                                    class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-semibold text-white transition hover:bg-orange-600"
                                                >
                                                    <span x-text="customerFooterButtonLabel()"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                x-cloak
                                x-show="view !== 'customer'"
                                x-transition.opacity.duration.350ms
                                class="intro-device-switch intro-simulator-panel p-4 sm:p-5"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ $simulatorCopy['merchant']['label'] }}</div>
                                        <h3 class="mt-1 text-xl font-bold text-brand-dark">{{ $simulatorCopy['merchant']['headline'] }}</h3>
                                    </div>
                                    <span class="inline-flex items-center gap-2 rounded-full border border-brand-soft/70 bg-white px-3 py-1 text-xs font-semibold text-brand-primary/75">
                                        <span class="intro-live-dot inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                        {{ $simulatorCopy['merchant']['badge'] }}
                                    </span>
                                </div>

                                <div class="mt-5 intro-console-frame intro-board-shell p-4 text-white sm:p-5">
                                    <div class="intro-board-toolbar rounded-2xl p-4">
                                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                            <div class="flex items-center gap-4">
                                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-300">
                                                    {{ __('admin.board_back_to_stores') }}
                                                </span>
                                                <div>
                                                    <div class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-300">{{ $simulatorCopy['merchant']['label'] }}</div>
                                                    <h4 class="mt-1 text-xl font-bold text-white">{{ $simulatorCopy['customer']['store'] }}</h4>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                                                <div class="flex rounded-lg border border-slate-700 overflow-hidden text-xs font-semibold">
                                                    <span class="px-3 py-1.5 bg-indigo-600 text-white">{{ $simulatorCopy['customer']['store'] }}</span>
                                                </div>

                                                <div class="flex rounded-lg border border-slate-700 overflow-hidden text-xs font-semibold">
                                                    <span class="intro-board-filter px-3 py-1.5" :class="boardFilterKey() === 'all' ? 'is-active bg-indigo-600' : 'hover:bg-slate-700'">{{ __('admin.board_filter_all') }}</span>
                                                    <span class="intro-board-filter px-3 py-1.5" :class="boardFilterKey() === 'cashier' ? 'is-active bg-amber-500' : 'hover:bg-slate-700'">{{ __('admin.board_label_cashier') }}</span>
                                                    <span class="intro-board-filter px-3 py-1.5" :class="boardFilterKey() === 'kitchen' ? 'is-active bg-blue-600' : 'hover:bg-slate-700'">{{ __('admin.board_label_kitchen') }}</span>
                                                </div>

                                                <div class="flex items-center gap-1.5 rounded-full border border-emerald-700 bg-emerald-900/50 px-3 py-1 text-xs text-emerald-400">
                                                    <span class="relative flex h-2 w-2">
                                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                                    </span>
                                                    {{ __('admin.board_live_updating') }}
                                                </div>

                                                <span class="rounded-full bg-indigo-600 px-3 py-0.5 text-xs font-bold" x-text="boardCountLabel()"></span>
                                            </div>
                                        </div>

                                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                            @foreach ($simulatorCopy['merchant']['metrics'] as $metric)
                                                <div class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2">
                                                    <p class="text-[11px] text-slate-400">{{ $metric['label'] }}</p>
                                                    <p class="text-lg font-bold text-white">{{ $metric['value'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-300">
                                            <span class="rounded-full border border-slate-700 bg-slate-800 px-3 py-1">{{ __('admin.board_last_updated') }}: <span x-text="boardLastUpdatedText()"></span></span>
                                            <span class="rounded-full border border-indigo-600/60 bg-indigo-950/40 px-3 py-1 font-semibold text-indigo-200">{{ __('admin.board_next_refresh') }}10s</span>
                                        </div>
                                    </div>

                                    <div x-cloak x-show="step < 2" class="intro-board-empty mt-4 flex flex-col items-center justify-center rounded-2xl px-6 py-20 text-center text-slate-400">
                                        <div class="text-lg font-semibold text-slate-200">{{ __('admin.board_empty_pending') }}</div>
                                        <p class="mt-2 max-w-md text-sm leading-6 text-slate-400" x-text="merchantStatus()"></p>
                                    </div>

                                    <div x-cloak x-show="step >= 2" x-transition.opacity.duration.250ms class="mt-4">
                                        <div class="intro-board-card rounded-2xl overflow-hidden" :class="boardCardToneClass()">
                                            <div class="flex items-start justify-between gap-3 border-b border-slate-700/60 px-4 pb-3 pt-4">
                                                <div>
                                                    <span class="font-mono text-lg font-bold text-white">#DF-2048</span>
                                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                                        <span class="text-slate-500" x-text="boardRelativeTime()"></span>
                                                        <span class="rounded bg-rose-500/15 px-1.5 py-0.5 text-[10px] font-semibold text-rose-200" x-text="boardWaitLabel()"></span>
                                                        <span class="rounded border border-sky-500/40 bg-sky-900/40 px-1.5 py-0.5 text-[10px] font-semibold text-sky-300">Locale {{ str_replace('_', '-', app()->getLocale()) }}</span>
                                                    </div>
                                                    <div class="mt-1 text-xs text-slate-400">DineFlow Guest</div>
                                                </div>

                                                <div class="flex flex-col items-end gap-1.5">
                                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="boardChannelClass()" x-text="boardChannelLabel()"></span>
                                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="boardStatusClass()" x-text="boardStatusLabel()"></span>
                                                    <span class="rounded bg-slate-700 px-2.5 py-1 text-sm font-semibold text-slate-200">{{ __('admin.board_table_no') }} {{ $simulatorCopy['customer']['table'] }}</span>
                                                </div>
                                            </div>

                                            <div class="px-4 py-3 space-y-2">
                                                <div class="mb-1 text-[11px] font-semibold text-slate-300">
                                                    {{ __('admin.board_item_progress') }} <span x-text="boardCompletedCount()"></span>/2
                                                </div>

                                                <div class="intro-board-item rounded-lg px-3 py-3" :class="{ 'is-complete': boardItemComplete(0) }">
                                                    <div class="flex items-start gap-2">
                                                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md border text-xs font-bold" :class="boardItemComplete(0) ? 'border-emerald-300 bg-emerald-500 text-white' : 'border-slate-500 bg-slate-800 text-slate-300'">
                                                            <span x-text="boardItemComplete(0) ? 'OK' : '1'"></span>
                                                        </span>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-start gap-2">
                                                                <span class="min-w-0 flex-1 break-words text-sm font-semibold" :class="boardItemComplete(0) ? 'text-emerald-200 line-through' : 'text-white'" x-text="currentItemName()"></span>
                                                                <span class="ml-auto shrink-0 text-right text-xs font-semibold" :class="boardItemComplete(0) ? 'text-emerald-300' : 'text-slate-300'">x 1</span>
                                                            </div>
                                                            <div class="mt-1 text-xs text-slate-400" x-text="modeLabel()"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="intro-board-item rounded-lg px-3 py-3" :class="{ 'is-complete': boardItemComplete(1) }">
                                                    <div class="flex items-start gap-2">
                                                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md border text-xs font-bold" :class="boardItemComplete(1) ? 'border-emerald-300 bg-emerald-500 text-white' : 'border-slate-500 bg-slate-800 text-slate-300'">
                                                            <span x-text="boardItemComplete(1) ? 'OK' : '2'"></span>
                                                        </span>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-start gap-2">
                                                                <span class="min-w-0 flex-1 break-words text-sm font-semibold" :class="boardItemComplete(1) ? 'text-emerald-200 line-through' : 'text-white'">{{ $simulatorCopy['customer']['addon']['name'] }}</span>
                                                                <span class="ml-auto shrink-0 text-right text-xs font-semibold" :class="boardItemComplete(1) ? 'text-emerald-300' : 'text-slate-300'">x 1</span>
                                                            </div>
                                                            <div class="mt-1 rounded-md border border-yellow-700/40 bg-yellow-900/20 px-2 py-1 text-xs text-yellow-300">{{ __('admin.board_item_note_label') }} {{ $simulatorCopy['customer']['addon']['meta'] }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mx-4 mb-3 rounded-lg border border-slate-700/70 bg-slate-800/70 px-3 py-2 text-xs text-slate-200 space-y-1.5">
                                                <div class="flex items-center justify-between">
                                                    <span>{{ __('admin.board_order_subtotal') }}</span>
                                                    <span class="tabular-nums" x-text="cartTotal()"></span>
                                                </div>
                                                <div class="flex items-center justify-between border-t border-slate-700 pt-1.5 text-sm font-semibold text-white">
                                                    <span>{{ __('admin.board_order_total') }}</span>
                                                    <span class="tabular-nums" x-text="cartTotal()"></span>
                                                </div>
                                            </div>

                                            <div class="mx-4 mb-3 rounded-lg border border-yellow-700/30 bg-yellow-900/30 px-3 py-2 text-xs text-yellow-300">
                                                <span class="font-semibold">{{ __('admin.board_note_label') }}</span>
                                                {{ $simulatorCopy['customer']['addon']['meta'] }}
                                            </div>

                                            <div class="border-t border-slate-700/50 px-4 py-3">
                                                <div class="flex justify-end">
                                                    <button
                                                        type="button"
                                                        @click="merchantAction()"
                                                        :disabled="merchantActionDisabled()"
                                                        class="intro-board-action inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-45 sm:w-auto"
                                                        :class="step === 2 ? 'bg-blue-600 hover:bg-blue-500' : (step === 3 ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-700 hover:bg-slate-600')"
                                                    >
                                                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-white/80" :class="merchantActionDisabled() ? '' : 'intro-live-dot'"></span>
                                                        <span x-text="boardPrimaryActionLabel()"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="hidden mt-5 intro-console-frame p-4 text-white sm:p-5">
                                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-highlight/80">{{ $simulatorCopy['merchant']['label'] }}</div>
                                            <h4 class="mt-2 text-2xl font-bold">{{ $simulatorCopy['customer']['store'] }}</h4>
                                            <p class="mt-1 text-sm text-white/65">{{ $simulatorCopy['merchant']['badge'] }}</p>
                                        </div>
                                        <div class="rounded-full border border-white/12 bg-white/8 px-4 py-2 text-sm font-semibold text-white/80">
                                            <span x-text="modeLabel()"></span> · {{ $simulatorCopy['customer']['table'] }}
                                        </div>
                                    </div>

                                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                        @foreach ($simulatorCopy['merchant']['metrics'] as $metric)
                                            <div class="rounded-2xl border border-white/12 bg-white/[0.06] p-4">
                                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/55">{{ $metric['label'] }}</div>
                                                <div class="mt-2 text-xl font-bold text-white">{{ $metric['value'] }}</div>
                                                <div class="intro-metric-bar mt-3 h-1.5 rounded-full bg-white/10">
                                                    <div
                                                        class="h-full rounded-full bg-[linear-gradient(90deg,_rgba(246,208,90,1),_rgba(255,255,255,0.92))]"
                                                        style="width: {{ [72, 58, 44][$loop->index] ?? 60 }}%;"
                                                    ></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-6 grid gap-4 xl:grid-cols-3">
                                        @foreach ($simulatorCopy['merchant']['columns'] as $column)
                                            <div class="rounded-[1.45rem] border border-white/10 bg-white/[0.06] p-4">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="text-sm font-semibold text-white">{{ $column['label'] }}</div>
                                                    <span class="intro-status-pill inline-flex items-center justify-center rounded-full border border-white/10 bg-white/10 px-2.5 py-1 text-xs font-semibold text-white/70" x-text="columnCount('{{ $column['key'] }}')"></span>
                                                </div>

                                                <div class="mt-4 min-h-[12rem]">
                                                    <div
                                                        x-cloak
                                                        x-show="orderColumn() === '{{ $column['key'] }}'"
                                                        x-transition.opacity.scale.duration.350ms
                                                        class="intro-order-card rounded-[1.35rem] border border-white/12 bg-[#fff6ec] p-4 text-brand-dark"
                                                        :class="{ 'is-hot': step >= 2 }"
                                                    >
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="inline-flex rounded-full bg-brand-dark px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white">DF-2048</span>
                                                            <span class="text-xs font-semibold text-brand-primary/70">{{ $simulatorCopy['customer']['table'] }}</span>
                                                        </div>

                                                        <h5 class="mt-4 text-lg font-bold" x-text="currentItemName()"></h5>
                                                        <p class="mt-2 text-sm leading-6 text-brand-primary/75">
                                                            <span x-text="modeLabel()"></span> · {{ $simulatorCopy['customer']['addon']['name'] }} · {{ $simulatorCopy['customer']['addon']['meta'] }}
                                                        </p>

                                                        <div class="mt-4 flex flex-wrap gap-2">
                                                            <span class="inline-flex rounded-full bg-brand-highlight/20 px-3 py-1 text-xs font-semibold text-brand-dark" x-text="orderStatusLabel()"></span>
                                                            <span class="inline-flex rounded-full bg-brand-soft/70 px-3 py-1 text-xs font-semibold text-brand-primary/80" x-text="modeLabel()"></span>
                                                        </div>

                                                        <div class="mt-4 flex items-center justify-between text-sm font-semibold text-brand-primary/75">
                                                            <span>{{ $simulatorCopy['customer']['addon']['meta'] }}</span>
                                                            <span x-text="cartTotal()"></span>
                                                        </div>
                                                    </div>

                                                    <div
                                                        x-show="orderColumn() !== '{{ $column['key'] }}'"
                                                        class="flex min-h-[12rem] items-center justify-center rounded-[1.35rem] border border-dashed border-white/10 px-4 text-center text-sm leading-6 text-white/42"
                                                    >
                                                        {{ $column['empty'] }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="rounded-[1.2rem] border border-white/10 bg-white/[0.08] px-4 py-3 text-sm leading-6 text-white/75" x-text="merchantStatus()"></div>

                                        <button
                                            type="button"
                                            @click="merchantAction()"
                                            :disabled="merchantActionDisabled()"
                                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-brand-highlight px-4 py-3 text-sm font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft disabled:cursor-not-allowed disabled:opacity-45 disabled:hover:translate-y-0"
                                        >
                                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-brand-dark/80" :class="merchantActionDisabled() ? '' : 'intro-live-dot'"></span>
                                            <span x-text="merchantActionLabel()"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-14">
        <div class="mx-auto grid max-w-7xl gap-6 px-6 md:grid-cols-3 lg:px-8">
            @foreach ($kpiCards as $kpi)
                <div class="intro-reveal intro-kpi-card rounded-[1.6rem] border border-brand-soft/70 bg-white p-6 shadow-[0_16px_36px_rgba(90,30,14,0.08)]" data-reveal style="--delay: {{ 40 + ($loop->index * 90) }}ms;">
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/70">{{ $kpi['number'] }}</div>
                    <h2 class="mt-3 text-xl font-bold text-brand-dark">{{ $kpi['title'] }}</h2>
                    <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ $kpi['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="border-t border-brand-soft/60 bg-white py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="intro-reveal mb-10 text-center" data-reveal>
                <h2 class="text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('home.full_intro_flow_title') }}</h2>
                <p class="mt-3 text-lg text-brand-primary/75">{{ __('home.full_intro_flow_desc') }}</p>
            </div>

            <div class="relative space-y-6 lg:space-y-7">
                <div class="pointer-events-none absolute left-5 top-6 hidden h-[calc(100%-3.5rem)] w-[2px] bg-gradient-to-b from-brand-primary/35 via-brand-highlight/70 to-transparent lg:block"></div>
                @foreach ($flowSteps as $step)
                    <div class="intro-reveal intro-flow-card rounded-[1.7rem] border border-brand-soft/60 bg-brand-soft/20 p-6 sm:p-7" data-reveal style="--delay: {{ 40 + ($loop->index * 70) }}ms;">
                        <div class="grid gap-5 lg:grid-cols-[1fr_420px] lg:items-center">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start {{ $loop->even ? 'lg:order-2' : '' }}">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-bold {{ $step['badge'] }}">{{ $step['number'] }}</span>
                                <div>
                                    <h3 class="text-xl font-bold text-brand-dark">{{ $step['title'] }}</h3>
                                    <p class="mt-2 text-base leading-7 text-brand-primary/80">{{ $step['desc'] }}</p>
                                </div>
                            </div>
                            <div class="intro-flow-image-wrap overflow-hidden rounded-2xl border border-brand-soft/70 bg-white/80 {{ $loop->even ? 'lg:order-1' : '' }}">
                                <img
                                    src="{{ $step['image'] }}"
                                    alt="{{ $step['title'] }}"
                                    loading="lazy"
                                    class="intro-flow-image h-full w-full object-cover object-top"
                                >
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-brand-soft/60 bg-brand-soft/16 py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="intro-reveal mb-8 text-center" data-reveal>
                <h2 class="text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('home.full_intro_feature_title') }}</h2>
                <p class="mt-3 text-lg text-brand-primary/75">{{ __('home.full_intro_feature_desc') }}</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                @foreach ($featureCards as $feature)
                    <div class="intro-reveal intro-feature-card rounded-[1.6rem] border border-brand-soft/60 bg-white p-6 shadow-[0_14px_34px_rgba(90,30,14,0.06)]" data-reveal style="--delay: {{ 50 + ($loop->index * 80) }}ms;">
                        <h3 class="text-xl font-bold text-brand-dark">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @include('partials.product-intro-pricing-contact', ['plansByTier' => $plansByTier ?? collect()])

    <section class="border-t border-brand-soft/60 bg-brand-dark py-16 text-white">
        <div class="intro-reveal mx-auto max-w-5xl px-6 text-center lg:px-8" data-reveal>
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('home.full_intro_join_title') }}</h2>
            <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-white/80">{{ __('home.full_intro_join_desc') }}</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                @auth
                    <a href="{{ auth()->user()?->role === 'customer' ? route('join.merchant.register') : route('dashboard') }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        {{ __('home.full_intro_join_button') }}
                    </a>
                @else
                    <a href="{{ route('register', ['account_type' => 'merchant']) }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        {{ __('home.full_intro_join_button') }}
                    </a>
                @endauth
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                    {{ __('home.full_intro_back_home') }}
                </a>
            </div>
        </div>
    </section>
</div>
<script>
    (function () {
        const revealNodes = document.querySelectorAll('[data-reveal]');
        if (revealNodes.length) {
            if (!('IntersectionObserver' in window)) {
                revealNodes.forEach((node) => node.classList.add('is-visible'));
            } else {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    });
                }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });

                revealNodes.forEach((node) => observer.observe(node));
            }
        }

        const registerIntroSimulation = () => {
            if (!window.Alpine || window.Alpine.__dineflowIntroSimulationRegistered) {
                return;
            }

            window.Alpine.__dineflowIntroSimulationRegistered = true;

            window.Alpine.data('introSimulation', (copy) => ({
                copy,
                view: 'split',
                step: 0,
                autoplay: false,
                timer: null,
                selectedItem: 0,
                mode: 'dinein',

                init() {
                    this.syncState();

                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                            this.stopAutoplay();
                        }
                    });
                },

                maxStep() {
                    return this.copy.stages.length - 1;
                },

                progressWidth() {
                    return ((this.step + 1) / this.copy.stages.length) * 100;
                },

                setView(nextView) {
                    this.view = nextView;
                },

                setStep(nextStep) {
                    this.step = Math.max(0, Math.min(nextStep, this.maxStep()));
                    this.syncState();
                },

                syncState() {
                    if (!this.copy.customer.items[this.selectedItem]) {
                        this.selectedItem = 0;
                    }
                },

                startAutoplay() {
                    this.stopAutoplay();
                    this.autoplay = true;

                    this.timer = window.setInterval(() => {
                        if (this.step >= this.maxStep()) {
                            this.setStep(0);
                            return;
                        }

                        this.setStep(this.step + 1);
                    }, 2600);
                },

                stopAutoplay() {
                    this.autoplay = false;

                    if (this.timer) {
                        window.clearInterval(this.timer);
                        this.timer = null;
                    }
                },

                toggleAutoplay() {
                    if (this.autoplay) {
                        this.stopAutoplay();
                        return;
                    }

                    this.startAutoplay();
                },

                reset() {
                    const shouldResume = this.autoplay;
                    this.stopAutoplay();
                    this.selectedItem = 0;
                    this.mode = 'dinein';
                    this.setStep(0);

                    if (shouldResume) {
                        this.startAutoplay();
                    }
                },

                selectItem(index) {
                    this.selectedItem = index;

                    if (this.step === 0) {
                        this.setStep(1);
                    }
                },

                toggleMode() {
                    this.mode = this.mode === 'dinein' ? 'takeout' : 'dinein';
                },

                modeLabel() {
                    return this.mode === 'dinein'
                        ? this.copy.customer.mode
                        : this.copy.customer.alt_mode;
                },

                currentItem() {
                    return this.copy.customer.items[this.selectedItem] || this.copy.customer.items[0];
                },

                currentItemName() {
                    return this.currentItem().name;
                },

                currentItemPrice() {
                    return Number(this.currentItem().price || 0);
                },

                customerScene() {
                    if (this.step === 0) {
                        return 'menu';
                    }

                    if (this.step === 1) {
                        return 'cart';
                    }

                    return 'success';
                },

                formatCurrency(value) {
                    return `${this.copy.customer.currency}${Number(value || 0)}`;
                },

                cartTotalValue() {
                    if (this.step === 0) {
                        return 0;
                    }

                    let total = this.currentItemPrice();

                    total += Number(this.copy.customer.addon.price || 0);

                    return total;
                },

                cartTotal() {
                    return this.formatCurrency(this.cartTotalValue());
                },

                cartTitle() {
                    if (this.step === 0) {
                        return this.copy.customer.empty_title;
                    }

                    return `${this.currentItemName()} + ${this.copy.customer.addon.name}`;
                },

                customerStatus() {
                    if (this.step === 0) {
                        return this.copy.customer.status_scan;
                    }

                    if (this.step === 1) {
                        return this.copy.customer.status_add;
                    }

                    if (this.step === 2) {
                        return this.copy.customer.status_submit;
                    }

                    if (this.step === 3) {
                        return this.copy.customer.status_prepare;
                    }

                    return this.copy.customer.status_ready;
                },

                customerToast() {
                    if (this.step === 2) {
                        return this.copy.customer.toast_sent;
                    }

                    if (this.step === 3) {
                        return this.copy.customer.toast_prepare;
                    }

                    return this.copy.customer.toast_ready;
                },

                customerActionLabel() {
                    if (this.step === 0) {
                        return this.copy.customer.action_scan;
                    }

                    if (this.step === 1) {
                        return this.copy.customer.action_submit;
                    }

                    if (this.step === 2) {
                        return this.copy.customer.action_track;
                    }

                    if (this.step === 3) {
                        return this.copy.customer.action_notice;
                    }

                    return this.copy.customer.action_restart;
                },

                customerTopButtonLabel() {
                    return this.step >= 1
                        ? @js(__('customer.back_to_menu'))
                        : @js(__('customer.add_to_cart'));
                },

                customerTopAction() {
                    if (this.step === 0) {
                        this.selectItem(this.selectedItem);
                        return;
                    }

                    this.setStep(0);
                },

                customerBannerText() {
                    if (this.step === 0) {
                        return @js(__('customer.select_instruction'));
                    }

                    if (this.step === 1) {
                        return @js(__('customer.submit_order_hint'));
                    }

                    return this.customerStatus();
                },

                customerItemCount() {
                    if (this.step >= 1) {
                        return 2;
                    }

                    return 0;
                },

                customerItemCountLabel() {
                    return `${this.customerItemCount()}${@js(__('customer.items'))}`;
                },

                customerSuccessStateLabel() {
                    if (this.step === 2) {
                        return @js(__('merchant.order_status_pending'));
                    }

                    if (this.step === 3) {
                        return @js(__('merchant.order_status_preparing'));
                    }

                    return @js(__('merchant.order_status_ready_for_pickup'));
                },

                customerEtaLabel() {
                    if (this.step === 2) {
                        return @js(__('customer.estimated_prep_time_only', ['minutes' => 12]));
                    }

                    if (this.step === 3) {
                        return @js(__('customer.estimated_prep_time_only', ['minutes' => 8]));
                    }

                    return @js(__('merchant.order_status_ready_for_pickup'));
                },

                customerFooterButtonLabel() {
                    if (this.step === 0) {
                        return this.copy.customer.action_add;
                    }

                    return this.customerActionLabel();
                },

                customerAction() {
                    if (this.step >= this.maxStep()) {
                        this.reset();
                        return;
                    }

                    this.setStep(this.step + 1);
                },

                orderColumn() {
                    if (this.step < 2) {
                        return null;
                    }

                    if (this.step === 2) {
                        return 'new';
                    }

                    if (this.step === 3) {
                        return 'preparing';
                    }

                    return 'ready';
                },

                columnCount(columnKey) {
                    return this.orderColumn() === columnKey ? 1 : 0;
                },

                orderStatusLabel() {
                    if (this.step === 2) {
                        return this.copy.merchant.status_new;
                    }

                    if (this.step === 3) {
                        return this.copy.merchant.status_preparing;
                    }

                    return this.copy.merchant.status_ready;
                },

                merchantStatus() {
                    return this.copy.merchant.notes[this.step] || this.copy.merchant.notes[this.copy.merchant.notes.length - 1];
                },

                boardFilterKey() {
                    if (this.step < 2) {
                        return 'all';
                    }

                    if (this.step === 2) {
                        return 'cashier';
                    }

                    return 'kitchen';
                },

                boardVisibleCount() {
                    return this.step >= 2 ? 1 : 0;
                },

                boardCountLabel() {
                    return `${this.boardVisibleCount()} ${@js(__('admin.board_order_unit'))}`;
                },

                boardLastUpdatedText() {
                    if (this.step < 2) {
                        return @js(__('admin.board_not_updated_yet'));
                    }

                    if (this.step === 2) {
                        return '14:32:18';
                    }

                    if (this.step === 3) {
                        return '14:33:04';
                    }

                    return '14:34:11';
                },

                boardCardToneClass() {
                    if (this.step === 2) {
                        return 'is-pending';
                    }

                    if (this.step === 3) {
                        return 'is-preparing';
                    }

                    return 'is-ready';
                },

                boardRelativeTime() {
                    if (this.step === 2) {
                        return '45s';
                    }

                    if (this.step === 3) {
                        return '4m';
                    }

                    return '8m';
                },

                boardWaitLabel() {
                    if (this.step === 2) {
                        return `${@js(__('admin.board_waiting_prefix'))}1m`;
                    }

                    if (this.step === 3) {
                        return `${@js(__('admin.board_waiting_prefix'))}6m`;
                    }

                    return `${@js(__('admin.board_waiting_prefix'))}10m`;
                },

                boardChannelLabel() {
                    if (this.step === 2) {
                        return @js(__('admin.board_label_cashier'));
                    }

                    return @js(__('admin.board_label_kitchen'));
                },

                boardChannelClass() {
                    if (this.step === 2) {
                        return 'bg-amber-500/20 text-amber-300';
                    }

                    return 'bg-blue-500/20 text-blue-300';
                },

                boardStatusLabel() {
                    if (this.step === 2) {
                        return @js(__('admin.board_status_pending'));
                    }

                    if (this.step === 3) {
                        return @js(__('admin.board_status_preparing'));
                    }

                    return @js(__('merchant.order_status_completed'));
                },

                boardStatusClass() {
                    if (this.step === 2) {
                        return 'bg-amber-500/20 text-amber-200';
                    }

                    if (this.step === 3) {
                        return 'bg-blue-500/20 text-blue-200';
                    }

                    return 'bg-emerald-500/20 text-emerald-200';
                },

                boardCompletedCount() {
                    if (this.step === 2) {
                        return 0;
                    }

                    if (this.step === 3) {
                        return 1;
                    }

                    return 2;
                },

                boardItemComplete(index) {
                    if (this.step === 3) {
                        return index === 0;
                    }

                    return this.step >= 4;
                },

                boardPrimaryActionLabel() {
                    if (this.step === 2) {
                        return @js(__('admin.board_action_accept_postpay'));
                    }

                    if (this.step === 3) {
                        return @js(__('admin.board_action_mark_completed'));
                    }

                    return this.copy.reset;
                },

                merchantActionDisabled() {
                    return this.step < 2;
                },

                merchantActionLabel() {
                    if (this.step < 2) {
                        return this.copy.merchant.action_waiting;
                    }

                    if (this.step === 2) {
                        return this.copy.merchant.action_accept;
                    }

                    if (this.step === 3) {
                        return this.copy.merchant.action_ready;
                    }

                    return this.copy.merchant.action_complete;
                },

                merchantAction() {
                    if (this.step < 2) {
                        return;
                    }

                    if (this.step >= this.maxStep()) {
                        this.reset();
                        return;
                    }

                    this.setStep(this.step + 1);
                },
            }));
        };

        if (window.Alpine) {
            registerIntroSimulation();
        } else {
            document.addEventListener('alpine:init', registerIntroSimulation, { once: true });
        }
    })();
</script>
@endsection
