<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class NavFeature
{
    public const SETTING_KEY = 'nav_features';
    public const PLACEMENT_LINKS = 'links';
    public const PLACEMENT_DROPDOWN = 'dropdown';

    public const SUBSCRIPTION = 'subscription';
    public const FINANCIAL_REPORT = 'financial_report';
    public const ORDER_HISTORY = 'order_history';
    public const INVOICE_CENTER = 'invoice_center';
    public const LOYALTY = 'loyalty';
    public const STORE_BACKEND = 'store_backend';
    public const BOARDS = 'boards';

    private const CACHE_KEY = 'system-settings.nav-features';

    public static function definitions(): array
    {
        return [
            self::SUBSCRIPTION => [
                'label_key' => 'nav.subscription',
                'description_key' => 'admin.nav_feature_subscription_desc',
            ],
            self::FINANCIAL_REPORT => [
                'label_key' => 'nav.financial_report',
                'description_key' => 'admin.nav_feature_financial_report_desc',
            ],
            self::ORDER_HISTORY => [
                'label_key' => 'nav.order_history',
                'description_key' => 'admin.nav_feature_order_history_desc',
            ],
            self::INVOICE_CENTER => [
                'label_key' => 'nav.invoice_center',
                'description_key' => 'admin.nav_feature_invoice_center_desc',
            ],
            self::LOYALTY => [
                'label_key' => 'nav.loyalty',
                'description_key' => 'admin.nav_feature_loyalty_desc',
            ],
            self::STORE_BACKEND => [
                'label_key' => 'nav.store_backend',
                'description_key' => 'admin.nav_feature_store_backend_desc',
            ],
            self::BOARDS => [
                'label_key' => 'admin.board_all_title',
                'description_key' => 'admin.nav_feature_boards_desc',
            ],
        ];
    }

    public static function defaults(): array
    {
        $defaults = [];

        foreach (array_keys(self::definitions()) as $key) {
            $defaults[$key] = true;
        }

        return $defaults;
    }

    public static function defaultLayouts(): array
    {
        return [
            self::SUBSCRIPTION => ['placement' => self::PLACEMENT_DROPDOWN, 'order' => 70],
            self::FINANCIAL_REPORT => ['placement' => self::PLACEMENT_LINKS, 'order' => 30],
            self::ORDER_HISTORY => ['placement' => self::PLACEMENT_DROPDOWN, 'order' => 20],
            self::INVOICE_CENTER => ['placement' => self::PLACEMENT_DROPDOWN, 'order' => 50],
            self::LOYALTY => ['placement' => self::PLACEMENT_DROPDOWN, 'order' => 40],
            self::STORE_BACKEND => ['placement' => self::PLACEMENT_DROPDOWN, 'order' => 10],
            self::BOARDS => ['placement' => self::PLACEMENT_LINKS, 'order' => 60],
        ];
    }

    public static function placements(): array
    {
        return [self::PLACEMENT_LINKS, self::PLACEMENT_DROPDOWN];
    }

    public static function configurations(): array
    {
        $defaults = self::defaults();
        $defaultLayouts = self::defaultLayouts();

        if (! Schema::hasTable('system_settings')) {
            $resolved = [];

            foreach ($defaults as $key => $enabled) {
                $resolved[$key] = [
                    'enabled' => (bool) $enabled,
                    'placement' => self::normalizePlacement($defaultLayouts[$key]['placement'] ?? self::PLACEMENT_DROPDOWN),
                    'order' => self::normalizeOrder($defaultLayouts[$key]['order'] ?? 999),
                ];
            }

            return $resolved;
        }

        $stored = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $setting = SystemSetting::query()
                ->where('key', self::SETTING_KEY)
                ->value('value');

            return is_array($setting) ? $setting : [];
        });

        $resolved = [];
        foreach ($defaults as $key => $defaultEnabled) {
            $storedValue = $stored[$key] ?? null;

            if (is_array($storedValue)) {
                $enabled = (bool) ($storedValue['enabled'] ?? $defaultEnabled);
                $placement = self::normalizePlacement($storedValue['placement'] ?? ($defaultLayouts[$key]['placement'] ?? null));
                $order = self::normalizeOrder($storedValue['order'] ?? ($defaultLayouts[$key]['order'] ?? null));
            } else {
                $enabled = (bool) ($storedValue ?? $defaultEnabled);
                $placement = self::normalizePlacement($defaultLayouts[$key]['placement'] ?? null);
                $order = self::normalizeOrder($defaultLayouts[$key]['order'] ?? null);
            }

            $resolved[$key] = [
                'enabled' => $enabled,
                'placement' => $placement,
                'order' => $order,
            ];
        }

        return $resolved;
    }

    public static function all(): array
    {
        $resolved = [];
        foreach (self::configurations() as $key => $configuration) {
            $resolved[$key] = (bool) ($configuration['enabled'] ?? false);
        }

        return $resolved;
    }

    public static function enabled(string $feature): bool
    {
        return (bool) (self::all()[$feature] ?? false);
    }

    public static function labelKey(string $feature): string
    {
        return self::definitions()[$feature]['label_key'] ?? $feature;
    }

    public static function update(array $values, array $placements = [], array $orders = []): void
    {
        $configurations = self::configurations();
        $defaultLayouts = self::defaultLayouts();
        $payload = [];

        foreach (array_keys(self::definitions()) as $key) {
            $payload[$key] = [
                'enabled' => (bool) ($values[$key] ?? false),
                'placement' => self::normalizePlacement(
                    $placements[$key]
                        ?? ($configurations[$key]['placement'] ?? null)
                        ?? ($defaultLayouts[$key]['placement'] ?? null)
                ),
                'order' => self::normalizeOrder(
                    $orders[$key]
                        ?? ($configurations[$key]['order'] ?? null)
                        ?? ($defaultLayouts[$key]['order'] ?? null)
                ),
            ];
        }

        SystemSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => $payload],
        );

        Cache::forget(self::CACHE_KEY);
    }

    private static function normalizePlacement(mixed $placement): string
    {
        $normalized = is_string($placement) ? trim(strtolower($placement)) : '';

        return in_array($normalized, self::placements(), true)
            ? $normalized
            : self::PLACEMENT_DROPDOWN;
    }

    private static function normalizeOrder(mixed $order): int
    {
        $resolved = (int) $order;

        if ($resolved < 1) {
            return 1;
        }

        if ($resolved > 999) {
            return 999;
        }

        return $resolved;
    }
}
