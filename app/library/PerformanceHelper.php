<?php

namespace App\Library;

use App\Library\SeoHelper;

/**
 * Performance Helper - Tối ưu performance cho SEO và caching
 */
class PerformanceHelper
{
    /**
     * Generate and cache SEO data for any controller (OPTIMIZED)
     */
    public static function generateCachedSeoData(string $type, ?string $region = null, ?string $date = null, array $additionalData = [], $cache = null): array
    {
        // Tạo cache key dựa trên type và parameters
        $cacheKey = self::buildSeoCacheKey($type, $region, $date, $additionalData);
        
        // Kiểm tra cache trước (nếu có cache service)
        if ($cache !== null) {
            $cachedData = $cache->get($cacheKey);
            if ($cachedData !== null) {
                return $cachedData;
            }
        }
        
        // Generate SEO data
        $seoData = [];
        
        switch ($type) {
            case 'homepage':
                $seoData = SeoHelper::generateHomepageMeta();
                // $seoData['structured_data'] = SeoHelper::generateHomepageStructuredData(
                //     $additionalData['kqxsByRegion'] ?? [],
                //     $additionalData['megaResult'] ?? null,
                //     $additionalData['powerResult'] ?? null
                // );
                break;
                
            case 'lottery_result':
                $seoData = SeoHelper::generateLotteryResultMeta($region, $date, $additionalData['provinceName'] ?? null);
                if (!empty($additionalData['results'])) {
                    $seoData['structured_data'] = SeoHelper::generateLotteryResultStructuredData(
                        $region, $date, $additionalData['results'], $additionalData['provinceName'] ?? null
                    );
                }
                break;
                
            case 'prediction':
                $seoData = SeoHelper::generatePredictionMeta(
                    $region, $date, $additionalData['title'], $additionalData['provinceName'] ?? null
                );
                $seoData['structured_data'] = SeoHelper::generatePredictionStructuredData(
                    $additionalData['title'],
                    $additionalData['description'] ?? $seoData['seo_description'],
                    $region, $date, $additionalData['publishedDate'] ?? null
                );
                break;
                
            case 'prediction_listing':
                $seoData = SeoHelper::generatePredictionListingMeta($region, $additionalData['predictions'] ?? []);
                $seoData['structured_data'] = SeoHelper::generatePredictionListingStructuredData(
                    $region, $additionalData['predictions'] ?? []
                );
                break;
                
            case 'statistics':
                $seoData = SeoHelper::generateStatisticsMeta($region, $additionalData['type'] ?? 'thongke');
                // $seoData['structured_data'] = SeoHelper::generateStatisticsStructuredData(
                //     $region, $additionalData['type'] ?? 'thongke', $additionalData['data'] ?? []
                // );
                break;
                
            case 'vietlott':
                $seoData = SeoHelper::generateVietlottMeta($region, $date);
                // $seoData['structured_data'] = SeoHelper::generateVietlottStructuredData($region, $additionalData['latestResult'] ?? null);
                break;
                
            case 'archive':
                $seoData = SeoHelper::generateArchiveMeta(
                    $region, 
                    $additionalData['weekdaySlug'] ?? null, 
                    $additionalData['province'] ?? null, 
                    $additionalData['page'] ?? 1
                );
                $seoData['structured_data'] = SeoHelper::generateArchiveStructuredData(
                    $region, 
                    $additionalData['weekdaySlug'] ?? null, 
                    $additionalData['province'] ?? null, 
                    $additionalData['sections'] ?? []
                );
                break;
                
            case 'quaythu':
                $seoData = SeoHelper::generateQuaythuMeta($region, $additionalData['regions'] ?? []);
                $seoData['structured_data'] = SeoHelper::generateQuaythuStructuredData($region);
                break;
                
            case 'keno':
                $seoData = SeoHelper::generateKenoMeta(
                    $additionalData['latestResult'] ?? null, 
                    $additionalData['nextDrawTime'] ?? null,
                    $date
                );
                // $seoData['structured_data'] = SeoHelper::generateKenoStructuredData(
                //     $additionalData['latestResult'] ?? null, 
                //     $additionalData['statistics'] ?? []
                // );
                break;
            case 'custom':
                $seoData = SeoHelper::generateCustomPageMeta($region, $additionalData);
                break;
        }
        
        // Add organization structured data (cached)
        if($type != 'vietlott' && $type != 'homepage' && $type != 'keno'){
            $seoData['organization_structured_data'] = SeoHelper::generateOrganizationStructuredDataJson();
        }
        
        // Pre-encode JSON để tránh encoding trong view (OPTIMIZED)
        if (isset($seoData['structured_data'])) {
            $seoData['structured_data_json'] = json_encode($seoData['structured_data'], JSON_UNESCAPED_UNICODE);
            unset($seoData['structured_data']); // Remove original để tiết kiệm memory
        }
        
        // Pre-encode organization JSON (OPTIMIZED)
        if (isset($seoData['organization_structured_data'])) {
            if (is_array($seoData['organization_structured_data'])) {
                $seoData['organization_structured_data'] = json_encode($seoData['organization_structured_data'], JSON_UNESCAPED_UNICODE);
            }
        }
        
        // Cache với thời gian phù hợp (nếu có cache service)
        if ($cache !== null) {
            $cacheLifetime = self::getCacheLifetime($type, $date);
            $cache->set($cacheKey, $seoData, $cacheLifetime);
        }
        
        return $seoData;
    }
    
    /**
     * Build cache key for SEO data
     */
    private static function buildSeoCacheKey(string $type, ?string $region = null, ?string $date = null, array $additionalData = []): string
    {
        $keyParts = ['seo', $type];
        
        if ($region) $keyParts[] = $region;
        if ($date) $keyParts[] = str_replace('-', '', $date);
        
        // Add hash of additional data
        if (!empty($additionalData)) {
            $keyParts[] = substr(md5(serialize($additionalData)), 0, 8);
        }
        
        return implode('_', $keyParts);
    }
    
    /**
     * Get appropriate cache lifetime based on type and date
     */
    private static function getCacheLifetime(string $type, ?string $date = null): int
    {
        switch ($type) {
            case 'homepage':
                return 3600; // 1 hour
            case 'lottery_result':
                // Cache longer for past dates
                if ($date && strtotime($date) < strtotime('today')) {
                    return 86400; // 24 hours for past dates
                }
                return 300; // 5 minutes for today
            case 'prediction':
                return 1800; // 30 minutes
            case 'statistics':
                return 3600; // 1 hour
            case 'vietlott':
                return 7200; // 2 hours
            case 'archive':
                return 7200; // 2 hours
            case 'quaythu':
                return 3600; // 1 hour
            case 'keno':
                return 1800; // 30 minutes (frequent updates)
            default:
                return 1800; // 30 minutes
        }
    }
}
