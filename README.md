# DineFlow

> DineFlow 是一套為餐飲品牌打造的 QR 點餐與營運平台，將品牌官網、門店後台、現場看板、會員經營與訂閱商業模式整合在同一個 Laravel 專案中。

## 產品概念

餐飲品牌往往不是只缺一個點餐頁，而是缺一套能把「曝光、接單、出餐、收款、回購經營」串起來的系統。

DineFlow 目前聚焦在以下幾件事：

- 讓顧客可以快速完成內用與外帶點餐
- 讓店家能管理多門店、商品、桌位與現場流程
- 讓商家具備會員、優惠、報表與訂閱化能力
- 讓整個系統同時具備 B2C 展示面與 B2B 營運面

## 目前亮點

- 內用 QR 點餐與外帶點餐雙流程
- 商家門店後台，支援商品分類、品項排序、桌位與 QR 管理
- 廚房看板、收銀看板、綜合 boards
- 會員點數、優惠券、回購經營模組
- 商家訂閱方案、7 天試用、方案升級補差額
- 營收、成本、毛利、客單價、品項趨勢等營運報表
- 公開首頁、產品介紹頁、價格洽詢、門店列表與評論展示
- 多語系支援：`zh_TW`、`zh_CN`、`en`、`vi`
- Laravel Reverb 即時同步，支援內用購物車更新

## 專案現況

DineFlow 已經不是單純的 prototype，而是一個具備完整資料表、seed 資料、角色權限、背景工作與測試的可運行應用。

目前已完成的重點包含：

- 顧客端點餐與購物車流程
- 商家端門店與商品管理
- 現場看板流程
- 會員與優惠
- 訂閱方案與功能開關
- 營運報表
- 發票中心 UI、資料模型與背景工作流程

目前仍需注意：

- `ECPay` 已用於商家訂閱付款流程
- 電子發票中心目前仍使用模擬 gateway，尚未接入正式發票服務商

## 核心模組

### 顧客端

- 內用入口：`/s/{store:slug}/t/{table:qr_token}/menu`
- 外帶入口：`/s/{store:slug}/takeout/menu`
- 購物車、優惠券檢查、結帳、訂單成功頁、訂單歷史、再次下單
- 門店評論與公開展示頁

### 商家端

- 門店管理與多店配額控制
- 商品分類、品項、選項群組、售完狀態管理
- 桌位與 QR 管理
- 廚房 / 收銀 / 綜合 boards
- 會員、點數、優惠券管理
- 發票中心
- 財務報表

### 平台端

- 訂閱方案管理
- 商家訂閱狀態管理
- 全站功能開關
- 管理員與商家角色權限控制

## 技術棧

- `PHP 8.2+`
- `Laravel 12`
- `Blade`
- `Tailwind CSS`
- `Alpine.js`
- `Vite`
- `Laravel Reverb`
- `SQLite`（本機預設）
- `PHPUnit`

## 快速啟動

### 需求

- `PHP 8.2+`
- `Composer`
- `Node.js 18+`
- `npm`

### 安裝

1. 安裝後端套件

```bash
composer install
```

2. 建立環境檔

macOS / Linux:

```bash
cp .env.example .env
```

PowerShell:

```powershell
Copy-Item .env.example .env
```

3. 產生應用程式金鑰

```bash
php artisan key:generate
```

4. 建立資料表並載入 demo 資料

```bash
php artisan migrate:fresh --seed
```

5. 建立 storage 連結

```bash
php artisan storage:link
```

6. 安裝前端套件

```bash
npm install
```

7. 啟動開發環境

```bash
composer run dev
```

`composer run dev` 會同時啟動：

- `php artisan serve`
- `php artisan queue:listen`
- `php artisan pail`
- `npm run dev`
- `php artisan reverb:start`

如果只想先跑一次基本安裝，也可以使用：

```bash
composer run setup
```

## Demo 資料

執行 `php artisan migrate:fresh --seed` 後，系統會建立：

- 10 間 demo 門店
- 商品、分類、桌位、QR 與歷史訂單資料
- 訂閱方案
- 報表展示資料
- 發票中心示範資料

### 預設帳號

| 角色 | 帳號 | 密碼 |
| --- | --- | --- |
| Admin | `admin@dineflow.local` | `password` |
| Merchant | `merchant.basic@dineflow.local` | `password` |
| Merchant | `merchant.growth@dineflow.local` | `password` |
| Merchant | `merchant.pro@dineflow.local` | `password` |
| Merchant | `merchant.plus@dineflow.local` | `password` |
| Customer | `customer01@dineflow.local` ~ `customer24@dineflow.local` | `password` |

## 重要環境變數

### 基本

- `APP_URL`
- `APP_LOCALE`
- `APP_TIMEZONE`
- `DB_*`

### 即時功能

- `BROADCAST_CONNECTION=reverb`
- `REVERB_*`
- `VITE_REVERB_*`

### 郵件

- `MAIL_*`
- `MERCHANT_REGISTER_NOTIFY_EMAIL`
- `MS_GRAPH_*`
- `GRAPH_USER_SCOPES`

### 訂閱付款

- `ECPAY_MERCHANT_ID`
- `ECPAY_HASH_KEY`
- `ECPAY_HASH_IV`
- `ECPAY_CHECKOUT_ACTION`

### 地點與發票

- `GOOGLE_PLACES_*`
- `INVOICE_*`

## 常用指令

```bash
# 執行測試
php artisan test

# 清除快取
php artisan optimize:clear

# 查看路由
php artisan route:list

# 建立管理員
php artisan admin:create admin@example.com --name="Site Admin"

# 為指定門店灌測試菜單
php artisan stores:fake-menu seed-store-01 --replace

# 為指定門店產生測試訂單
php artisan stores:fake-orders seed-store-01 --count=30 --days=14

# 發送測試郵件
php artisan mail:test you@example.com
php artisan mail:test you@example.com --graph
```

## 部署提醒

- 正式環境請確保 `queue worker`、`reverb server` 與 `scheduler` 正常運作
- 上傳圖片前需先執行 `php artisan storage:link`
- 專案提供 `scripts/ubuntu-deploy.sh` 作為簡易部署腳本
- 若要正式上線電子發票，請先替換目前的模擬 invoice gateway

## 專案結構

- `app/Http/Controllers/Admin`
  - 商家後台、boards、門店與商品管理
- `app/Http/Controllers/Customer`
  - 內用與外帶點餐、訂單、評論
- `app/Http/Controllers/Merchant`
  - 訂閱、報表、會員與發票中心
- `app/Models`
  - 核心商業模型
- `app/Jobs`
  - 發票相關背景工作
- `database/migrations`
  - 資料結構
- `database/seeders`
  - demo 資料
- `resources/views`
  - 前後台 Blade 畫面
- `lang`
  - 多語系檔案

## License

本專案採用 `MIT` 授權。
