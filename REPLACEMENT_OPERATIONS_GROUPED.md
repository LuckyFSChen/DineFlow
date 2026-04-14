# Blade 中文本地化替換操作清單

## 文檔 1: resources/views/admin/products/index.blade.php

### 📌 第一組：頁面標題與導航
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "page_header",
  "replacements": [
    {
      "key": "admin.products_title",
      "original": "商品管理中心",
      "newString": "{{ __('admin.products_title') }}",
      "context": "H1 頁面標題"
    },
    {
      "key": "admin.products_subtitle",
      "original": "店家：{{ $store->name }}，依分類管理商品，使用彈窗快速編輯。",
      "newString": "{{ __('admin.products_subtitle', ['store' => $store->name]) }}",
      "context": "頁面副標題"
    },
    {
      "key": "admin.products_back_to_stores",
      "original": "回店家列表",
      "newString": "{{ __('admin.products_back_to_stores') }}",
      "context": "返回按鈕"
    }
  ]
}
```

### 📌 第二組：統計卡片
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "stats_cards",
  "replacements": [
    {
      "key": "admin.products_total_count",
      "original": "總商品數",
      "newString": "{{ __('admin.products_total_count') }}",
      "context": "統計卡片標籤"
    },
    {
      "key": "admin.products_active_status",
      "original": "可販售",
      "newString": "{{ __('admin.products_active_status') }}",
      "context": "統計卡片 - 可販售"
    },
    {
      "key": "admin.products_with_options",
      "original": "有選配",
      "newString": "{{ __('admin.products_with_options') }}",
      "context": "統計卡片 - 選配"
    }
  ]
}
```

### 📌 第三組：分類按鈕群組
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "category_buttons",
  "replacements": [
    {
      "key": "admin.products_add_category",
      "original": "新增分類",
      "newString": "{{ __('admin.products_add_category') }}",
      "context": "分類新增按鈕"
    },
    {
      "key": "admin.products_edit_category",
      "original": "編輯分類",
      "newString": "{{ __('admin.products_edit_category') }}",
      "context": "分類編輯按鈕"
    },
    {
      "key": "admin.products_disable_category",
      "original": "停用分類",
      "newString": "{{ __('admin.products_disable_category') }}",
      "context": "分類停用按鈕"
    },
    {
      "key": "admin.products_delete_category",
      "original": "刪除分類",
      "newString": "{{ __('admin.products_delete_category') }}",
      "context": "分類刪除按鈕"
    }
  ]
}
```

### 📌 第四組：商品卡片狀態與操作
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "product_card_status",
  "replacements": [
    {
      "key": "admin.products_sort",
      "original": "排序",
      "newString": "{{ __('admin.products_sort') }}",
      "context": "商品排序標籤（多處出現，統一替換）"
    },
    {
      "key": "admin.products_drag_handle",
      "original": "拖曳排序",
      "newString": "{{ __('admin.products_drag_handle') }}",
      "context": "拖曳排序按鈕"
    },
    {
      "key": "admin.products_status_available",
      "original": "可販售",
      "newString": "{{ __('admin.products_status_available') }}",
      "context": "商品狀態 - 可販售"
    },
    {
      "key": "admin.products_status_sold_out",
      "original": "售完",
      "newString": "{{ __('admin.products_status_sold_out') }}",
      "context": "商品狀態 - 售完"
    },
    {
      "key": "admin.products_status_inactive",
      "original": "下架",
      "newString": "{{ __('admin.products_status_inactive') }}",
      "context": "商品狀態 - 下架"
    }
  ]
}
```

### 📌 第五組：商品描述與選配提示
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "product_description",
  "replacements": [
    {
      "key": "admin.products_no_description",
      "original": "尚未填寫描述",
      "newString": "{{ __('admin.products_no_description') }}",
      "context": "商品描述空值提示"
    },
    {
      "key": "admin.products_has_options",
      "original": "已設定選配",
      "newString": "{{ __('admin.products_has_options') }}",
      "context": "已設定選配標籤"
    },
    {
      "key": "admin.products_no_options",
      "original": "無選配",
      "newString": "{{ __('admin.products_no_options') }}",
      "context": "無選配標籤"
    }
  ]
}
```

### 📌 第六組：商品卡片操作按鈕
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "product_card_buttons",
  "replacements": [
    {
      "key": "admin.products_edit",
      "original": "編輯",
      "newString": "{{ __('admin.products_edit') }}",
      "context": "商品卡片編輯按鈕"
    },
    {
      "key": "admin.products_delete",
      "original": "刪除",
      "newString": "{{ __('admin.products_delete') }}",
      "context": "商品卡片刪除按鈕"
    }
  ]
}
```

### 📌 第七組：空狀態提示
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "empty_states",
  "replacements": [
    {
      "key": "admin.products_empty_category",
      "original": "這個分類還沒有商品，點右上角「新增商品」快速建立。",
      "newString": "{{ __('admin.products_empty_category') }}",
      "context": "分類為空提示"
    },
    {
      "key": "admin.products_no_categories",
      "original": "目前沒有可用分類，請先建立分類再新增商品。",
      "newString": "{{ __('admin.products_no_categories') }}",
      "context": "沒有分類時提示"
    }
  ]
}
```

### 📌 第八組：已停用分類區塊
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "inactive_categories",
  "replacements": [
    {
      "key": "admin.products_inactive_title",
      "original": "已停用分類",
      "newString": "{{ __('admin.products_inactive_title') }}",
      "context": "已停用分類標題"
    },
    {
      "key": "admin.products_inactive_desc",
      "original": "需要恢復時可直接重新啟用，不會刪除原本分類與商品關聯。",
      "newString": "{{ __('admin.products_inactive_desc') }}",
      "context": "已停用分類描述"
    },
    {
      "key": "admin.products_count_unit",
      "original": "筆",
      "newString": "{{ __('admin.products_count_unit') }}",
      "context": "計數單位"
    },
    {
      "key": "admin.products_items",
      "original": "項商品",
      "newString": "{{ __('admin.products_items') }}",
      "context": "項商品計數"
    },
    {
      "key": "admin.products_enable_category",
      "original": "重新啟用",
      "newString": "{{ __('admin.products_enable_category') }}",
      "context": "分類啟用按鈕"
    }
  ]
}
```

### 📌 第九組：商品模態標題
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "product_modal_header",
  "replacements": [
    {
      "key": "admin.products_modal_product_title",
      "original": "新增商品",
      "newString": "{{ __('admin.products_modal_product_title') }}",
      "context": "商品模態標題"
    },
    {
      "key": "admin.products_modal_subtitle",
      "original": "使用彈窗快速維護商品資料",
      "newString": "{{ __('admin.products_modal_subtitle') }}",
      "context": "模態副標題"
    }
  ]
}
```

### 📌 第十組：商品模態表單標籤
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "product_modal_form",
  "replacements": [
    {
      "key": "admin.products_form_name",
      "original": "商品名稱",
      "newString": "{{ __('admin.products_form_name') }}",
      "context": "模態表單 - 商品名稱標籤"
    },
    {
      "key": "admin.products_form_category",
      "original": "分類",
      "newString": "{{ __('admin.products_form_category') }}",
      "context": "模態表單 - 分類標籤"
    },
    {
      "key": "admin.products_form_price",
      "original": "價格 (NT$)",
      "newString": "{{ __('admin.products_form_price') }}",
      "context": "模態表單 - 價格標籤"
    },
    {
      "key": "admin.products_form_sort",
      "original": "排序",
      "newString": "{{ __('admin.products_form_sort') }}",
      "context": "模態表單 - 排序標籤（第二次）"
    },
    {
      "key": "admin.products_form_image",
      "original": "圖片 URL",
      "newString": "{{ __('admin.products_form_image') }}",
      "context": "模態表單 - 圖片標籤"
    },
    {
      "key": "admin.products_form_description",
      "original": "描述",
      "newString": "{{ __('admin.products_form_description') }}",
      "context": "模態表單 - 描述標籤"
    }
  ]
}
```

### 📌 第十一組：選配功能按鈕
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "option_buttons",
  "replacements": [
    {
      "key": "admin.products_template_steak",
      "original": "套用牛排範本",
      "newString": "{{ __('admin.products_template_steak') }}",
      "context": "牛排範本按鈕"
    },
    {
      "key": "admin.products_template_combo",
      "original": "套用套餐範本",
      "newString": "{{ __('admin.products_template_combo') }}",
      "context": "套餐範本按鈕"
    },
    {
      "key": "admin.products_clear",
      "original": "清空",
      "newString": "{{ __('admin.products_clear') }}",
      "context": "清空按鈕"
    },
    {
      "key": "admin.products_add_group",
      "original": "新增群組",
      "newString": "{{ __('admin.products_add_group') }}",
      "context": "新增群組按鈕"
    }
  ]
}
```

### 📌 第十二組：選配提示
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "option_hint",
  "replacements": [
    {
      "key": "admin.products_option_edit_hint",
      "original": "選配樹狀編輯：先建立群組，再新增群組內選項。",
      "newString": "{{ __('admin.products_option_edit_hint') }}",
      "context": "選配編輯提示"
    }
  ]
}
```

### 📌 第十三組：商品模態複選框
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "product_modal_checkboxes",
  "replacements": [
    {
      "key": "admin.products_checkbox_active",
      "original": "上架",
      "newString": "{{ __('admin.products_checkbox_active') }}",
      "context": "上架複選框標籤"
    },
    {
      "key": "admin.products_checkbox_sold_out",
      "original": "售完",
      "newString": "{{ __('admin.products_checkbox_sold_out') }}",
      "context": "售完複選框標籤"
    }
  ]
}
```

### 📌 第十四組：商品模態按鈕
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "product_modal_buttons",
  "replacements": [
    {
      "key": "admin.products_save_product",
      "original": "儲存商品",
      "newString": "{{ __('admin.products_save_product') }}",
      "context": "商品儲存按鈕"
    },
    {
      "key": "admin.products_cancel",
      "original": "取消",
      "newString": "{{ __('admin.products_cancel') }}",
      "context": "模態取消按鈕"
    }
  ]
}
```

### 📌 第十五組：分類模態
```json
{
  "file": "resources/views/admin/products/index.blade.php",
  "group": "category_modal",
  "replacements": [
    {
      "key": "admin.products_modal_category_title",
      "original": "新增分類",
      "newString": "{{ __('admin.products_modal_category_title') }}",
      "context": "分類模態標題"
    },
    {
      "key": "admin.products_modal_category_subtitle",
      "original": "建立或修改商品分類",
      "newString": "{{ __('admin.products_modal_category_subtitle') }}",
      "context": "分類模態副標題"
    },
    {
      "key": "admin.products_form_category_name",
      "original": "分類名稱",
      "newString": "{{ __('admin.products_form_category_name') }}",
      "context": "分類名稱標籤"
    },
    {
      "key": "admin.products_save_category",
      "original": "儲存分類",
      "newString": "{{ __('admin.products_save_category') }}",
      "context": "分類儲存按鈕"
    }
  ]
}
```

---

## 文檔 2: resources/views/admin/products/_form.blade.php

### 📌 第十六組：基本資訊章節
```json
{
  "file": "resources/views/admin/products/_form.blade.php",
  "group": "basic_section",
  "replacements": [
    {
      "key": "admin.products_form_suggestion",
      "original": "建議流程：\\n        1. 先填商品基本資訊。\\n        2. 設定銷售狀態。\\n        3. 最後用下方「選配設定」加上必選與加購項目。",
      "newString": "{{ __('admin.products_form_suggestion') }}",
      "context": "表單建議提示"
    },
    {
      "key": "admin.products_form_basic_title",
      "original": "基本資訊",
      "newString": "{{ __('admin.products_form_basic_title') }}",
      "context": "基本資訊章節標題"
    },
    {
      "key": "admin.products_form_basic_desc",
      "original": "顧客第一眼會看到的名稱、分類、價格與描述。",
      "newString": "{{ __('admin.products_form_basic_desc') }}",
      "context": "基本資訊章節描述"
    }
  ]
}
```

### 📌 第十七組：表單基本標籤（重複）
```json
{
  "file": "resources/views/admin/products/_form.blade.php",
  "group": "form_labels",
  "replacements": [
    {
      "key": "admin.products_form_image_optional",
      "original": "圖片 URL (可留空)",
      "newString": "{{ __('admin.products_form_image_optional') }}",
      "context": "_form.blade.php - 圖片標籤（與 index 略有不同）"
    }
  ]
}
```

### 📌 第十八組：選配設定章節
```json
{
  "file": "resources/views/admin/products/_form.blade.php",
  "group": "options_section",
  "replacements": [
    {
      "key": "admin.products_form_options_title",
      "original": "選配設定",
      "newString": "{{ __('admin.products_form_options_title') }}",
      "context": "選配設定章節標題"
    },
    {
      "key": "admin.products_form_options_desc",
      "original": "可建立必選、單選、多選與加價項目。",
      "newString": "{{ __('admin.products_form_options_desc') }}",
      "context": "選配設定描述"
    },
    {
      "key": "admin.products_form_group_list",
      "original": "群組清單",
      "newString": "{{ __('admin.products_form_group_list') }}",
      "context": "群組清單標籤"
    },
    {
      "key": "admin.products_form_options_hint",
      "original": "提示：\\n            群組可設為單選或多選，必選群組會在前台要求顧客一定要選。\\n            多選群組可設定最多可選幾項。",
      "newString": "{{ __('admin.products_form_options_hint') }}",
      "context": "選配設定提示"
    }
  ]
}
```

### 📌 第十九組：銷售狀態章節
```json
{
  "file": "resources/views/admin/products/_form.blade.php",
  "group": "status_section",
  "replacements": [
    {
      "key": "admin.products_form_status_title",
      "original": "銷售狀態",
      "newString": "{{ __('admin.products_form_status_title') }}",
      "context": "銷售狀態章節標題"
    },
    {
      "key": "admin.products_form_status_desc",
      "original": "控制是否上架、或暫時標記售完。",
      "newString": "{{ __('admin.products_form_status_desc') }}",
      "context": "銷售狀態描述"
    }
  ]
}
```

### 📌 第二十組：表單底部按鈕
```json
{
  "file": "resources/views/admin/products/_form.blade.php",
  "group": "form_buttons",
  "replacements": [
    {
      "key": "admin.products_submit",
      "original": "儲存",
      "newString": "{{ __('admin.products_submit') }}",
      "context": "_form.blade.php - 儲存按鈕"
    },
    {
      "key": "admin.products_back_to_list",
      "original": "返回商品列表",
      "newString": "{{ __('admin.products_back_to_list') }}",
      "context": "返回列表按鈕"
    }
  ]
}
```

---

## 📊 統計摘要

| 項目 | 數量 |
|------|------|
| **總分組數** | 20 |
| **index.blade.php 分組** | 15 |
| **_form.blade.php 分組** | 5 |
| **總替換對數** | 60+ |
| **翻譯鍵命名空間** | admin.products_* |

## ✅ 執行指引

1. **按分組順序執行**：從第一組開始，按順序執行到第二十組
2. **每組獨立執行**：每個分組可獨立執行 multi_replace_string_in_file 操作
3. **檢查上下文**：oldString 包含前後 2-3 行上下文，確保準確匹配
4. **保留函數呼叫**：route()、csrf_token() 等函數保持不變
5. **JavaScript 不替換**：<script> 標籤內的中文保留原樣

## 🔑 翻譯鍵對應表

```php
// admin.products_* translation keys to be added to lang files:
admin.products_title => '商品管理中心'
admin.products_subtitle => '店家：{store}，依分類管理商品，使用彈窗快速編輯。'
admin.products_back_to_stores => '回店家列表'
admin.products_add_category => '新增分類'
admin.products_add_product => '新增商品'
admin.products_total_count => '總商品數'
admin.products_active_status => '可販售'
admin.products_with_options => '有選配'
// ... etc
```
