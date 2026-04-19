<?php

namespace App\Support;

class InvoiceFlow
{
    public const NONE = 'none';
    public const MOBILE_BARCODE = 'mobile_barcode';
    public const MEMBER_CARRIER = 'member_carrier';
    public const DONATION = 'donation_code';
    public const COMPANY_TAX_ID = 'company_tax_id';

    public static function options(): array
    {
        return [
            self::NONE => '不開立',
            self::MOBILE_BARCODE => '手機條碼',
            self::MEMBER_CARRIER => '會員載具',
            self::DONATION => '捐贈愛心碼',
            self::COMPANY_TAX_ID => '公司統編',
        ];
    }

    public static function validationRules(bool $flowRequired = false): array
    {
        $flowRule = $flowRequired ? 'required' : 'nullable';

        return [
            'invoice_flow' => [$flowRule, 'string', 'in:' . implode(',', array_keys(self::options()))],
            'invoice_mobile_barcode' => ['nullable', 'string', 'max:64'],
            'invoice_member_carrier_code' => ['nullable', 'string', 'max:64'],
            'invoice_donation_code' => ['nullable', 'string', 'max:16'],
            'invoice_company_tax_id' => ['nullable', 'string', 'max:16'],
            'invoice_company_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function normalize(array $validated): array
    {
        $flow = trim((string) ($validated['invoice_flow'] ?? self::NONE));
        if (! array_key_exists($flow, self::options())) {
            $flow = self::NONE;
        }

        $mobileBarcode = strtoupper(trim((string) ($validated['invoice_mobile_barcode'] ?? '')));
        $memberCarrierCode = strtoupper(trim((string) ($validated['invoice_member_carrier_code'] ?? '')));
        $donationCode = preg_replace('/\D+/', '', (string) ($validated['invoice_donation_code'] ?? ''));
        $companyTaxId = preg_replace('/\D+/', '', (string) ($validated['invoice_company_tax_id'] ?? ''));
        $companyName = trim((string) ($validated['invoice_company_name'] ?? ''));

        $mobileBarcode = $mobileBarcode !== '' ? $mobileBarcode : null;
        $memberCarrierCode = $memberCarrierCode !== '' ? $memberCarrierCode : null;
        $donationCode = is_string($donationCode) && $donationCode !== '' ? $donationCode : null;
        $companyTaxId = is_string($companyTaxId) && $companyTaxId !== '' ? $companyTaxId : null;
        $companyName = $companyName !== '' ? $companyName : null;

        return match ($flow) {
            self::MOBILE_BARCODE => [
                'invoice_flow' => $flow,
                'invoice_mobile_barcode' => $mobileBarcode,
                'invoice_member_carrier_code' => null,
                'invoice_donation_code' => null,
                'invoice_company_tax_id' => null,
                'invoice_company_name' => null,
            ],
            self::MEMBER_CARRIER => [
                'invoice_flow' => $flow,
                'invoice_mobile_barcode' => null,
                'invoice_member_carrier_code' => $memberCarrierCode,
                'invoice_donation_code' => null,
                'invoice_company_tax_id' => null,
                'invoice_company_name' => null,
            ],
            self::DONATION => [
                'invoice_flow' => $flow,
                'invoice_mobile_barcode' => null,
                'invoice_member_carrier_code' => null,
                'invoice_donation_code' => $donationCode,
                'invoice_company_tax_id' => null,
                'invoice_company_name' => null,
            ],
            self::COMPANY_TAX_ID => [
                'invoice_flow' => $flow,
                'invoice_mobile_barcode' => null,
                'invoice_member_carrier_code' => null,
                'invoice_donation_code' => null,
                'invoice_company_tax_id' => $companyTaxId,
                'invoice_company_name' => $companyName,
            ],
            default => [
                'invoice_flow' => self::NONE,
                'invoice_mobile_barcode' => null,
                'invoice_member_carrier_code' => null,
                'invoice_donation_code' => null,
                'invoice_company_tax_id' => null,
                'invoice_company_name' => null,
            ],
        };
    }

    public static function validateFlowPayload(array $payload): array
    {
        $errors = [];
        $flow = (string) ($payload['invoice_flow'] ?? self::NONE);

        if ($flow === self::MOBILE_BARCODE) {
            $barcode = (string) ($payload['invoice_mobile_barcode'] ?? '');
            if (! preg_match('/^\/[A-Z0-9.+\-]{7}$/', $barcode)) {
                $errors['invoice_mobile_barcode'] = '手機條碼格式不正確，應為 / 開頭共 8 碼。';
            }
        }

        if ($flow === self::MEMBER_CARRIER) {
            $carrier = (string) ($payload['invoice_member_carrier_code'] ?? '');
            if (strlen($carrier) < 4) {
                $errors['invoice_member_carrier_code'] = '請輸入有效的會員載具識別碼。';
            }
        }

        if ($flow === self::DONATION) {
            $donationCode = (string) ($payload['invoice_donation_code'] ?? '');
            if (! preg_match('/^\d{3,7}$/', $donationCode)) {
                $errors['invoice_donation_code'] = '愛心碼須為 3 到 7 位數字。';
            }
        }

        if ($flow === self::COMPANY_TAX_ID) {
            $taxId = (string) ($payload['invoice_company_tax_id'] ?? '');
            if (! self::isTaiwanTaxId($taxId)) {
                $errors['invoice_company_tax_id'] = '統編格式或檢核碼不正確。';
            }
        }

        return $errors;
    }

    public static function isTaiwanTaxId(string $taxId): bool
    {
        if (! preg_match('/^\d{8}$/', $taxId)) {
            return false;
        }

        $digits = array_map('intval', str_split($taxId));
        $weights = [1, 2, 1, 2, 1, 2, 4, 1];
        $sum = 0;

        foreach ($digits as $index => $digit) {
            $value = $digit * $weights[$index];
            $sum += intdiv($value, 10) + ($value % 10);
        }

        if ($sum % 10 === 0) {
            return true;
        }

        return $digits[6] === 7 && ($sum + 1) % 10 === 0;
    }
}

