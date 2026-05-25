<?php

namespace App\Library;

/**
 * SEO Render Helper - Tối ưu render SEO cho tất cả các view
 */
class SeoRenderHelper
{
    /**
     * Render SEO meta tags tối ưu (inline để tăng tốc)
     */
    public static function renderSeoMeta($seoData = []): string
    {
        $title = $seoData['seo_title'] ?? 'Soi Cầu 247 - Dự Đoán Xổ Số Chính Xác 3 Miền';
        $description = $seoData['seo_description'] ?? 'Soi cầu 247 - Dự đoán xổ số chính xác 3 miền XSMB, XSMN, XSMT - Cập nhật nhanh chóng và chính xác';
        $keywords = $seoData['seo_keywords'] ?? 'xổ số, kết quả xổ số, xsmb, xsmn, xsmt, vietlott, power 6/55, mega 6/45';
        $ogTitle = $seoData['og_title'] ?? $title;
        $ogDescription = $seoData['og_description'] ?? $description;
        $canonicalUrl = $seoData['canonical_url'] ?? '';
        
        return "
        <title>{$title}</title>
        <meta name=\"description\" content=\"{$description}\">
        <meta name=\"keywords\" content=\"{$keywords}\">
        <meta property=\"og:title\" content=\"{$ogTitle}\">
        <meta property=\"og:description\" content=\"{$ogDescription}\">
        <meta property=\"og:type\" content=\"website\">
        <meta property=\"og:url\" content=\"{$canonicalUrl}\">
        <meta property=\"og:image\" content=\"/img/logo-soicau247.png\">
        <meta property=\"og:site_name\" content=\"Soi Cầu 247\">
        <meta property=\"og:locale\" content=\"vi_VN\">
        <meta name=\"twitter:card\" content=\"summary_large_image\">
        <meta name=\"twitter:title\" content=\"{$ogTitle}\">
        <meta name=\"twitter:description\" content=\"{$ogDescription}\">
        <meta name=\"twitter:image\" content=\"/img/logo-soicau247.png\">
        <meta name=\"author\" content=\"Soi Cầu 247\">
        <meta name=\"robots\" content=\"index, follow\">
        <meta name=\"googlebot\" content=\"index, follow\">
        <link rel=\"canonical\" href=\"{$canonicalUrl}\">";
    }
    
    /**
     * Render structured data tối ưu (pre-encoded JSON)
     */
    public static function renderStructuredData($seoData = []): string
    {
        $output = '';
        
        // Main structured data
        if (isset($seoData['structured_data_json'])) {
            $output .= "<script type=\"application/ld+json\">{$seoData['structured_data_json']}</script>";
        }
        
        // Organization structured data
        if (isset($seoData['organization_structured_data'])) {
            $output .= "<script type=\"application/ld+json\">{$seoData['organization_structured_data']}</script>";
        }
        
        return $output;
    }
    
    /**
     * Render critical CSS inline
     */
    public static function renderCriticalCss(): string
    {
        return "
        <style>
            body{font-family:Arial,sans-serif;margin:0;padding:0;background:#f5f5f5}
            .container{max-width:1200px;margin:0 auto;padding:0 15px}
            .header{background:#fff;box-shadow:0 2px 4px rgba(0,0,0,0.1);padding:10px 0}
            .lottery-results{background:#fff;border-radius:8px;padding:20px;margin:10px 0}
            .loading{text-align:center;padding:20px;color:#666}
            .seo-optimized{display:none}
        </style>";
    }
    
    /**
     * Render critical JavaScript inline
     */
    public static function renderCriticalJs(): string
    {
        return "
        <script>
            document.addEventListener('DOMContentLoaded',function(){
                const menuToggle=document.querySelector('.menu-toggle');
                if(menuToggle){
                    menuToggle.addEventListener('click',function(){
                        document.body.classList.toggle('menu-open');
                    });
                }
                if(typeof lozad!=='undefined'){
                    const observer=lozad('.lozad',{loaded:function(el){el.classList.add('loaded');}});
                    observer.observe();
                }
            });
        </script>";
    }
    
    /**
     * Render complete SEO head section (tối ưu nhất)
     */
    public static function renderSeoHead($seoData = []): string
    {
        return self::renderSeoMeta($seoData) . 
               self::renderCriticalCss() . 
               self::renderStructuredData($seoData);
    }
}
