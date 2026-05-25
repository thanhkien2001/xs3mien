<?php

namespace App\Library;

use App\Models\Provinces;

class ArchiveHelper
{
    /**
     * Mapping từ URL alias sang DB code
     * Các tỉnh có code trong DB khác với alias trong URL
     */
    private static function getDbCodeFromAlias(string $alias): string
    {
        $aliasLower = strtolower($alias);
        $mapping = [
            'xsqn' => 'xsqnm',  // Quảng Nam: URL dùng xsqn, DB dùng XSQNM
        ];
        
        return $mapping[$aliasLower] ?? $aliasLower;
    }

    /**
     * Handle 404 not found response
     */
    public static function notFound($response, $view)
    {
        $response->setStatusCode(404, 'Not Found');
        
        // Set SEO variables for 404 page
        $view->setVar('noindex', true);
        $view->setVar('canonical_url', 'https://soicau247.com/');
        
        $view->pick('errors/404');
        return;
    }

    /**
     * Find province by name slug
     */
    public static function findProvinceByNameSlug(string $slug): ?Provinces
    {
        $all = Provinces::find(['order' => 'id ASC']);
        foreach ($all as $p) {
            $s = KqxsHelper::slugify((string)$p->name);
            if ($s === $slug) return $p;
        }
        return null;
    }

    /**
     * Find province by alias (code)
     */
    public static function findProvinceByAlias(string $alias): ?Provinces
    {
        $alias = strtolower($alias);
        // Chuyển đổi alias URL sang DB code nếu có mapping
        $dbCode = self::getDbCodeFromAlias($alias);
        
        return Provinces::findFirst([
            'conditions' => 'LOWER(code) = :alias:',
            'bind' => ['alias' => $dbCode],
            'order' => 'id ASC'
        ]);
    }

    /**
     * Find province by both name slug and alias together to avoid ambiguity
     */
    public static function findProvinceByNameSlugAndAlias(string $nameSlug, string $alias): ?Provinces
    {
        $all = Provinces::find(['order' => 'id ASC']);
        $aliasLower = strtolower($alias);
        // Chuyển đổi alias URL sang DB code nếu có mapping
        $dbCode = self::getDbCodeFromAlias($alias);
        
        foreach ($all as $p) {
            $provinceNameSlug = KqxsHelper::slugify((string)$p->name);
            $provinceCodeLower = strtolower((string)$p->code);
            
            // Match both name slug AND DB code
            if ($provinceNameSlug === $nameSlug && $provinceCodeLower === $dbCode) {
                return $p;
            }
        }
        
        return null;
    }

    /**
     * Get canonical URL for a province based on header mapping
     * These are the ONLY valid URLs - anything else should return 404
     * @param Provinces|object|array $province
     */
    public static function getCanonicalProvinceUrl($province): ?string
    {
        // Handle both Provinces object and array/object from cache
        if (is_array($province)) {
            $name = $province['name'] ?? null;
        } elseif (is_object($province)) {
            $name = is_a($province, 'App\Models\Provinces') 
                ? (string)$province->name 
                : ($province->name ?? null);
        } else {
            return null;
        }
        
        if (!$name) {
            return null;
        }
        
        // Mapping từ header.phtml - đây là các URL duy nhất được phép
        $canonicalUrls = [
            // Miền Nam
            'An Giang' => '/xo-so-an-giang-xsag.html',
            'Bạc Liêu' => '/xo-so-bac-lieu-xsblieu.html',
            'Bến Tre' => '/xo-so-ben-tre-xsbtr.html',
            'Bình Dương' => '/xo-so-binh-duong-xsbduong.html',
            'Bình Phước' => '/xo-so-binh-phuoc-xsbp.html',
            'Bình Thuận' => '/xo-so-binh-thuan-xsbthuan.html',
            'Cà Mau' => '/xo-so-ca-mau-xscm.html',
            'Cần Thơ' => '/xo-so-can-tho-xsct.html',
            'Đà Lạt' => '/xo-so-da-lat-xsdl.html',
            'Đồng Nai' => '/xo-so-dong-nai-xsdn.html',
            'Đồng Tháp' => '/xo-so-dong-thap-xsdthap.html',
            'Hậu Giang' => '/xo-so-hau-giang-xshg.html',
            'TP. Hồ Chí Minh' => '/xo-so-ho-chi-minh-xshcm.html',
            'Hồ Chí Minh' => '/xo-so-ho-chi-minh-xshcm.html',
            'Kiên Giang' => '/xo-so-kien-giang-xskg.html',
            'Long An' => '/xo-so-long-an-xsla.html',
            'Sóc Trăng' => '/xo-so-soc-trang-xsst.html',
            'Tây Ninh' => '/xo-so-tay-ninh-xstn.html',
            'Tiền Giang' => '/xo-so-tien-giang-xstg.html',
            'Trà Vinh' => '/xo-so-tra-vinh-xstv.html',
            'Vĩnh Long' => '/xo-so-vinh-long-xsvl.html',
            'Vũng Tàu' => '/xo-so-vung-tau-xsvt.html',
            
            // Miền Trung
            'Bình Định' => '/xo-so-binh-dinh-xsbdinh.html',
            'Đà Nẵng' => '/xo-so-da-nang-xsdna.html',
            'Đắk Lắk' => '/xo-so-dak-lak-xsdlk.html',
            'Đắk Nông' => '/xo-so-dak-nong-xsdnong.html',
            'Gia Lai' => '/xo-so-gia-lai-xsgl.html',
            'Huế' => '/xo-so-hue-xstth.html',
            'Thừa Thiên Huế' => '/xo-so-hue-xstth.html',
            'Khánh Hòa' => '/xo-so-khanh-hoa-xskhoa.html',
            'Kon Tum' => '/xo-so-kon-tum-xskontum.html',
            'Ninh Thuận' => '/xo-so-ninh-thuan-xsnt.html',
            'Phú Yên' => '/xo-so-phu-yen-xspy.html',
            'Quảng Bình' => '/xo-so-quang-binh-xsqb.html',
            'Quảng Nam' => '/xo-so-quang-nam-xsqn.html',
            'Quảng Ngãi' => '/xo-so-quang-ngai-xsqng.html',
            'Quảng Trị' => '/xo-so-quang-tri-xsqtri.html',
        ];
        
        // Return canonical URL if exists, otherwise return null (will trigger 404)
        return $canonicalUrls[$name] ?? null;
    }

    /**
     * Get today href based on region
     */
    public static function getTodayHref($region): string
    {
        return match ($region) {
            'XSMN' => '/xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html',
            'XSMT' => '/xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html',
            default => '/xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html',
        };
    }

    /**
     * Make pagination URL
     */
    public static function makePageUrl(string $baseUri, int $page): string
    {
        // Tách base URL và query string hiện tại
        $parsed = parse_url($baseUri);
        $path = $parsed['path'] ?? '';
        $query = $parsed['query'] ?? '';
        
        // Parse query string hiện tại
        parse_str($query, $params);
        
        // Cập nhật page parameter
        $params['page'] = $page;
        
        // Tạo URL mới
        $newQuery = http_build_query($params);
        return $path . ($newQuery ? '?' . $newQuery : '');
    }

    /**
     * Build prizes array from lottery result
     */
    public static function buildPrizesArray($res): array
    {
        return [
            1 => [$res->special_prize],
            2 => [$res->first_prize],
            3 => KqxsHelper::toArray($res->second_prize),
            4 => KqxsHelper::toArray($res->third_prize),
            5 => KqxsHelper::toArray($res->fourth_prize),
            6 => KqxsHelper::toArray($res->fifth_prize),
            7 => KqxsHelper::toArray($res->sixth_prize),
            8 => KqxsHelper::toArray($res->seventh_prize),
            9 => KqxsHelper::toArray($res->eighth_prize),
        ];
    }

    /**
     * Build flat prizes structure
     */
    public static function buildFlatPrizes($res, array $prizes): array
    {
        return [
            'special_prize' => (string)$res->special_prize,
            'first_prize'   => (string)$res->first_prize,
            'second_prize'  => $prizes[3],
            'third_prize'   => $prizes[4],
            'fourth_prize'  => $prizes[5],
            'fifth_prize'   => $prizes[6],
            'sixth_prize'   => $prizes[7],
            'seventh_prize' => $prizes[8],
            'eighth_prize'  => $prizes[9],
        ];
    }

    /**
     * Determine smart cache lifetime based on data
     */
    public static function getSmartCacheLifetime($data, $dateField = null): int
    {
        if (empty($data)) {
            return 86000;
        }

        $today = date('Y-m-d');
        $hasToday = false;

        foreach ($data as $item) {
            $dateValue = $dateField ? $item[$dateField] : $item;
            $dateStr = is_object($dateValue) ? $dateValue->format('Y-m-d') : $dateValue;

            if ($dateStr == $today) {
                $hasToday = true;
                break;
            }
        }

        if ($hasToday) {
            $now = new \DateTime();
            $drawTime = new \DateTime('18:00'); 

            if ($now > $drawTime) {
                return 86000; 
            } else {
                return 86000;
            }
        } else {
            return 86000;
        }
    }
}
