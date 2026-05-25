<?php

namespace App\Library;
// ========== 1. CacheHelper - Quản lý cache keys và strategies ==========
class CacheHelper
{
    /**
     * Generate standardized cache key for different types
     */
    public static function generateKey(string $type, array $params = []): string
    {
        switch ($type) {
            // case 'region_weekday':
            //     // Format: REGION_WEEKDAY_pPAGE (e.g., "XSMT_thu-2_p1")
            //     return sprintf('%s_%s_p%d', 
            //         $params['region'], 
            //         $params['weekday_slug'], 
            //         $params['page']
            //     );

            // case 'province':
            //     // Format: REGION_PROVINCE_pPAGE (e.g., "XSMT_an-giang_p1")
            //     return sprintf('%s_%s_p%d', 
            //         $params['region'], 
            //         $params['province_slug'], 
            //         $params['page']
            //     );

            // case 'kqxsmb_date':
            //     // Format: XSMB_DATE (e.g., "XSMB_2025-01-15")
            //     return sprintf('XSMB_%s', $params['date']);

            // case 'kqxsmb_weekday':
            //     // Format: XSMB_WEEKDAY_DATE (e.g., "XSMB_thu-2_2025-01-15")
            //     return sprintf('XSMB_%s_%s', 
            //         $params['weekday_slug'], 
            //         $params['date']
            //     );

            // case 'predictions_box':
            //     // Format: PRED_BOX_DATE (e.g., "PRED_BOX_2025-01-15")
            //     return sprintf('PRED_BOX_%s', $params['date']);

            // case 'prediction_single':
            //     // Format: PRED_REGION_DATE (e.g., "PRED_XSMN_2025-01-15")
            //     return sprintf('PRED_%s_%s', 
            //         $params['region'], 
            //         $params['date']
            //     );
            case 'XSMT_full_page':
                // Format: XSMB_FULL_PAGE_DATE (e.g., "XSMB_FULL_PAGE_2025-01-15")
                return sprintf('XSMT_FULL_PAGE_%s', $params['date']);
            case 'XSMN_full_page':
                // Format: XSMB_FULL_PAGE_DATE (e.g., "XSMB_FULL_PAGE_2025-01-15")
                return sprintf('XSMN_FULL_PAGE_%s', $params['date']);
            case 'XSMB_full_page':
                // Format: XSMB_FULL_PAGE_DATE (e.g., "XSMB_FULL_PAGE_2025-01-15")
                return sprintf('XSMB_FULL_PAGE_%s', $params['date']);
                
            default:
                return implode('_', array_values($params));
        }
    }

    /**
     * Get smart cache lifetime based on data freshness
     */
    public static function getSmartLifetime($data, ?string $dateField = null): int
    {
        if (empty($data)) {
            return 86000; // 1 hour for empty data
        }

        // If data is array of objects with date field
        if ($dateField && is_array($data)) {
            $dates = [];
            foreach ($data as $item) {
                if (is_object($item) && property_exists($item, $dateField)) {
                    $dates[] = $item->{$dateField};
                } elseif (is_array($item) && isset($item[$dateField])) {
                    $dates[] = $item[$dateField];
                }
            }
            return self::calculateLifetimeFromDates($dates);
        }

        // If data is array of DateTime objects or date strings
        if (is_array($data)) {
            $dates = array_map(function($item) {
                if ($item instanceof \DateTime) {
                    return $item;
                } elseif (is_string($item)) {
                    return new \DateTime($item);
                }
                return null;
            }, $data);
            
            $dates = array_filter($dates);
            return self::calculateLifetimeFromDates($dates);
        }

        // Default cache time
        return 86000; // 1 hour
    }

    private static function calculateLifetimeFromDates(array $dates): int
    {
        if (empty($dates)) {
            return 86000;
        }

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $now = new \DateTime('now', $tz);
        $today = new \DateTime('today', $tz);

        $latestDate = null;
        foreach ($dates as $date) {
            if ($date instanceof \DateTime) {
                if (!$latestDate || $date > $latestDate) {
                    $latestDate = $date;
                }
            }
        }

        if (!$latestDate) {
            return 86000;
        }

        // If latest date is today or future
        if ($latestDate >= $today) {
            // Short cache for current/future data
            return 86000; // 5 minutes
        }

        // If latest date is yesterday
        $yesterday = (clone $today)->modify('-1 day');
        if ($latestDate >= $yesterday) {
            return 86000; // 15 minutes
        }

        // For older data, longer cache
        return 86000; // 2 hours
    }

}
