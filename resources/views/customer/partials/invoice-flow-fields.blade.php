@php
    $invoiceFlow = old('invoice_flow', $rememberedCustomerInfo['invoice_flow'] ?? 'none');
    $invoiceMobileBarcode = old('invoice_mobile_barcode', $rememberedCustomerInfo['invoice_mobile_barcode'] ?? '');
    $invoiceMemberCarrierCode = old('invoice_member_carrier_code', $rememberedCustomerInfo['invoice_member_carrier_code'] ?? '');
    $invoiceDonationCode = old('invoice_donation_code', $rememberedCustomerInfo['invoice_donation_code'] ?? '');
    $invoiceCompanyTaxId = old('invoice_company_tax_id', $rememberedCustomerInfo['invoice_company_tax_id'] ?? '');
    $invoiceCompanyName = old('invoice_company_name', $rememberedCustomerInfo['invoice_company_name'] ?? '');
@endphp

<div class="rounded-2xl border border-brand-soft/60 bg-brand-soft/10 p-4" data-invoice-flow-box>
    <p class="text-sm font-semibold text-brand-dark">發票開立方式</p>
    <p class="mt-1 text-xs text-brand-primary/70">支援手機條碼、會員載具、愛心碼捐贈、公司統編。</p>

    <div class="mt-3">
        <label for="invoice_flow" class="mb-2 block text-sm font-medium text-brand-dark">發票流向</label>
        <select
            id="invoice_flow"
            name="invoice_flow"
            class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft"
            data-invoice-flow-select
        >
            <option value="none" @selected($invoiceFlow === 'none')>暫不開立</option>
            <option value="mobile_barcode" @selected($invoiceFlow === 'mobile_barcode')>個人雲端發票：手機條碼</option>
            <option value="member_carrier" @selected($invoiceFlow === 'member_carrier')>個人雲端發票：會員載具</option>
            <option value="donation_code" @selected($invoiceFlow === 'donation_code')>捐贈發票：愛心碼</option>
            <option value="company_tax_id" @selected($invoiceFlow === 'company_tax_id')>公司報帳：統編</option>
        </select>
    </div>

    <div class="mt-3 space-y-3" data-invoice-field="mobile_barcode">
        <label for="invoice_mobile_barcode" class="mb-2 block text-sm font-medium text-brand-dark">手機條碼</label>
        <input
            id="invoice_mobile_barcode"
            type="text"
            name="invoice_mobile_barcode"
            value="{{ $invoiceMobileBarcode }}"
            placeholder="例如 /ABCD123"
            class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm uppercase text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft"
        >
        <p class="text-xs text-brand-primary/70">需為 / 開頭共 8 碼。</p>
    </div>

    <div class="mt-3 space-y-3" data-invoice-field="member_carrier">
        <label for="invoice_member_carrier_code" class="mb-2 block text-sm font-medium text-brand-dark">會員載具識別碼</label>
        <input
            id="invoice_member_carrier_code"
            type="text"
            name="invoice_member_carrier_code"
            value="{{ $invoiceMemberCarrierCode }}"
            placeholder="例如 DFMEMBER001"
            class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm uppercase text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft"
        >
        <p class="text-xs text-brand-primary/70">首次填寫後，系統會嘗試綁定到此店會員資料。</p>
    </div>

    <div class="mt-3 space-y-3" data-invoice-field="donation_code">
        <label for="invoice_donation_code" class="mb-2 block text-sm font-medium text-brand-dark">愛心碼</label>
        <input
            id="invoice_donation_code"
            type="text"
            name="invoice_donation_code"
            value="{{ $invoiceDonationCode }}"
            inputmode="numeric"
            maxlength="7"
            placeholder="例如 16888"
            class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft"
        >
    </div>

    <div class="mt-3 space-y-3" data-invoice-field="company_tax_id">
        <label for="invoice_company_tax_id" class="mb-2 block text-sm font-medium text-brand-dark">公司統一編號</label>
        <input
            id="invoice_company_tax_id"
            type="text"
            name="invoice_company_tax_id"
            value="{{ $invoiceCompanyTaxId }}"
            inputmode="numeric"
            maxlength="8"
            placeholder="8 位數統編"
            class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft"
        >

        <label for="invoice_company_name" class="mb-2 block text-sm font-medium text-brand-dark">公司抬頭（選填）</label>
        <input
            id="invoice_company_name"
            type="text"
            name="invoice_company_name"
            value="{{ $invoiceCompanyName }}"
            placeholder="例如 鼎流餐飲股份有限公司"
            class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft"
        >
    </div>
</div>

<script>
(() => {
    const box = document.querySelector('[data-invoice-flow-box]');
    if (!box) {
        return;
    }

    const select = box.querySelector('[data-invoice-flow-select]');
    if (!select) {
        return;
    }

    const fieldBlocks = Array.from(box.querySelectorAll('[data-invoice-field]'));

    const syncVisibility = () => {
        const flow = String(select.value || 'none');

        fieldBlocks.forEach((block) => {
            const targetFlow = block.getAttribute('data-invoice-field');
            const isVisible = targetFlow === flow;
            block.classList.toggle('hidden', !isVisible);

            block.querySelectorAll('input').forEach((input) => {
                if (!isVisible) {
                    return;
                }

                if (flow === 'mobile_barcode' && input.name === 'invoice_mobile_barcode') {
                    input.setAttribute('required', 'required');
                } else if (flow === 'member_carrier' && input.name === 'invoice_member_carrier_code') {
                    input.setAttribute('required', 'required');
                } else if (flow === 'donation_code' && input.name === 'invoice_donation_code') {
                    input.setAttribute('required', 'required');
                } else if (flow === 'company_tax_id' && input.name === 'invoice_company_tax_id') {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                }
            });
        });
    };

    select.addEventListener('change', syncVisibility);
    syncVisibility();
})();
</script>

