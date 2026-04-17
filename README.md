# DineFlow

DineFlow 是一個餐廳點餐與商家管理系統，支援內用與外帶點餐、商家訂閱制、綠界金流、店家/商品後台管理。

目前專案以 Laravel 12 + Blade + Tailwind 為主，後台重點頁面已採用 Popup + AJAX 操作。

## 核心功能

### 1) 角色與權限

- `admin` / `merchant` / `customer` 三種角色。
- 商家後台路由受 `auth + verified + role + merchant.subscription` 保護。
- `merchant` 只能管理自己的店家與商品；`admin` 可管理所有店家。

### 2) 訂閱與方案

- 訂閱方案表：天數、價格、店家上限。
- 商家可在方案頁訂閱，付款會導向綠界 AIO 金流頁。
- 綠界付款通知會同步訂閱狀態、到期日與付款紀錄。
- 付款稽核資料寫入 `subscription_payments`。

### 3) 商家後台

- 店家管理：
	- 店家列表搜尋、狀態顯示、橫幅顯示。
	- 新增/編輯使用 Popup + AJAX。
	- 圖片支援拖曳上傳與預覽。
- 商品管理中心：
	- 依分類群組顯示商品。
	- 新增/編輯使用 Popup + AJAX。
	- 選配使用樹狀 UI（群組/選項）編輯，系統送出 JSON。
	- 商品排序支援拖曳（目前為拖曳放開才套用排序）。

### 4) 顧客點餐

- 內用：`/s/{store:slug}/t/{table:qr_token}/menu`
- 外帶：`/s/{store:slug}/takeout/menu`
- 支援商品選配、購物車、結帳、成功頁、訂單狀態追蹤。
- 客戶資料可勾選記住，並可清除已記住資料。
- 客戶電話會正規化為 `09xx-xxx-xxx`。

## 技術棧

- PHP 8.2+
- Laravel 12
- Blade + Tailwind CSS + Alpine.js + Vite
- QR Code (`simplesoftwareio/simple-qrcode`)

## 環境需求

- PHP 8.2+
- Composer
- Node.js 18+
- npm
- 資料庫：
	- 預設可用 SQLite（`.env.example`）
	- 或自行改為 MySQL

## 快速啟動

1. 安裝 PHP 依賴

```bash
composer install
```

2. 建立環境檔與金鑰

```bash
cp .env.example .env
php artisan key:generate
```

3. 建立資料表與測試資料

```bash
php artisan migrate:fresh --seed
```

4. 建立 storage 連結（橫幅圖片需要）

```bash
php artisan storage:link
```

5. 安裝前端依賴並啟動

```bash
npm install
composer run dev
```

若只要建置前端靜態資源：

```bash
npm run build
```

## 測試帳號（Seeder）

執行 `php artisan migrate:fresh --seed` 後可使用：

- Admin
	- Email: `admin@dineflow.local`
	- Password: `password`
- Merchant
	- Email: `merchant@dineflow.local`
	- Password: `password`
- Customer
	- Email: `customer@dineflow.local`
	- Password: `password`

## 綠界設定

請在 `.env` 設定：

```env
ECPAY_MERCHANT_ID=
ECPAY_HASH_KEY=
ECPAY_HASH_IV=
ECPAY_CHECKOUT_ACTION=https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5
```

付款通知路由：

- `POST /ecpay/subscription/notify`

## 常用指令

```bash
# 執行測試
php artisan test

# 清除快取
php artisan optimize:clear

# 查看路由
php artisan route:list
```

## Ubuntu 一鍵部署腳本

已提供腳本 [scripts/ubuntu-deploy.sh](scripts/ubuntu-deploy.sh)，會依序執行：

1. `php artisan migrate`
2. `npm run build`
3. `php artisan optimize`
4. `php artisan queue:restart`

使用方式：

```bash
chmod +x scripts/ubuntu-deploy.sh
./scripts/ubuntu-deploy.sh
```

## Production：重設 storage 連結

如果 production 上傳圖檔偶發讀不到，常見原因是 `public/storage` 仍指到舊版 release 路徑。可在每次部署後執行以下指令重建連結。

```bash
cd /var/www/dineflow/current

# 1) 清掉舊連結（不存在就忽略）
php artisan storage:unlink || true
rm -rf public/storage

# 2) 重新建立到目前 release 的連結
php artisan storage:link --relative

# 3) 清快取
php artisan optimize:clear
```

檢查是否正確：

```bash
ls -l public | grep storage
```

應看到 `public/storage -> ../storage/app/public` 這種相對連結。

另外請確認：

- Web root 指向 `.../current/public`（不要指到專案根目錄）。
- `storage` 與 `bootstrap/cache` 對 web user 可寫入。

## Production：重建訂閱方案（只跑方案 seed）

如果只要在 production 更新訂閱方案資料，請不要執行整包 `db:seed`，避免覆蓋 demo 資料。建議只跑方案 seeder：

```bash
cd /var/www/dineflow/current
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\SubscriptionPlanSeeder --force
php artisan optimize:clear
```

或使用專案內建 composer script：

```bash
composer run prod:refresh-subscription-plans
```

## 主要路由摘要

- 商家後台
	- `admin/stores`（店家管理）
	- `admin/stores/{store}/products`（商品管理）
	- `admin/stores/{store}/products/reorder`（商品排序 API）
- 商家訂閱
	- `merchant/subscription`
	- `ecpay/subscription/notify`（綠界付款通知）
- 顧客點餐
	- 內用：`s/{store:slug}/t/{table:qr_token}/menu`
	- 外帶：`s/{store:slug}/takeout/menu`

## 專案結構（重點）

- `app/Http/Controllers/Admin`：店家/商品後台控制器
- `app/Http/Controllers/Customer`：點餐與購物車流程
- `app/Http/Controllers/Merchant`：訂閱流程
- `resources/views/admin`：後台頁面
- `resources/views/customer`：前台點餐頁面
- `database/migrations`：資料表與欄位演進
- `database/seeders`：測試資料與 demo 帳號

## 注意事項

- `Store` 路由綁定鍵為 `slug`，不要用數字 id 組後台編輯路由。
- 商家若無有效訂閱或超出店家額度，後台建立店家會被限制。
- 商品管理目前分類資料由既有分類表提供，分類進階管理可再擴充。

## 授權

此專案基於 Laravel 生態建置，原始框架授權為 MIT。
