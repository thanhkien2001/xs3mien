<?php

namespace App\Library;

/**
 * Helper class để quản lý lịch quay xổ số và mapping URL soi cầu
 */
class SoiCauScheduleHelper
{
    /**
     * Lịch quay xổ số Miền Nam theo thứ (1 = Thứ 2, 7 = Chủ Nhật)
     */
    private static function getMienNamSchedule(): array
    {
        return [
            1 => ['TP. Hồ Chí Minh', 'Đồng Tháp', 'Cà Mau'], // Thứ 2
            2 => ['Bến Tre', 'Vũng Tàu', 'Bạc Liêu'], // Thứ 3
            3 => ['Đồng Nai', 'Cần Thơ', 'Sóc Trăng'], // Thứ 4
            4 => ['Tây Ninh', 'An Giang', 'Bình Thuận'], // Thứ 5
            5 => ['Vĩnh Long', 'Bình Dương', 'Trà Vinh'], // Thứ 6
            6 => ['TP. Hồ Chí Minh', 'Long An', 'Bình Phước', 'Hậu Giang'], // Thứ 7
            7 => ['Tiền Giang', 'Kiên Giang', 'Đà Lạt'], // Chủ Nhật
        ];
    }

    /**
     * Lịch quay xổ số Miền Trung theo thứ (1 = Thứ 2, 7 = Chủ Nhật)
     */
    private static function getMienTrungSchedule(): array
    {
        return [
            1 => ['Thừa Thiên Huế', 'Phú Yên'], // Thứ 2
            2 => ['Đắk Lắk', 'Quảng Nam'], // Thứ 3
            3 => ['Đà Nẵng', 'Khánh Hòa'], // Thứ 4
            4 => ['Bình Định', 'Quảng Trị', 'Quảng Bình'], // Thứ 5
            5 => ['Gia Lai', 'Ninh Thuận'], // Thứ 6
            6 => ['Đà Nẵng', 'Quảng Ngãi', 'Đắk Nông'], // Thứ 7
            7 => ['Khánh Hòa', 'Kon Tum', 'Thừa Thiên Huế'], // Chủ Nhật
        ];
    }

    /**
     * Mapping tên tỉnh sang URL soi cầu
     */
    private static function getSoiCauUrlMap(): array
    {
        return [
            // Miền Nam
            'An Giang' => '/soi-cau-xsag.html',
            'Bạc Liêu' => '/soi-cau-xsbl.html',
            'Bến Tre' => '/soi-cau-xsbtr.html',
            'Bình Dương' => '/soi-cau-xsbd.html',
            'Bình Phước' => '/soi-cau-xsbp.html',
            'Bình Thuận' => '/soi-cau-xsbth.html',
            'Cà Mau' => '/soi-cau-xscm.html',
            'Cần Thơ' => '/soi-cau-xsct.html',
            'Đà Lạt' => '/soi-cau-xsdl.html',
            'Đồng Nai' => '/soi-cau-xsdn.html',
            'Đồng Tháp' => '/soi-cau-xsdt.html',
            'Hậu Giang' => '/soi-cau-xshg.html',
            'TP. Hồ Chí Minh' => '/soi-cau-xshcm.html',
            'Hồ Chí Minh' => '/soi-cau-xshcm.html',
            'Kiên Giang' => '/soi-cau-xskg.html',
            'Long An' => '/soi-cau-xsla.html',
            'Sóc Trăng' => '/soi-cau-xsst.html',
            'Tây Ninh' => '/soi-cau-xstn.html',
            'Tiền Giang' => '/soi-cau-xstg.html',
            'Trà Vinh' => '/soi-cau-xstv.html',
            'Vĩnh Long' => '/soi-cau-xsvl.html',
            'Vũng Tàu' => '/soi-cau-xsvt.html',
            
            // Miền Trung
            'Bình Định' => '/soi-cau-xsbdi.html',
            'Đà Nẵng' => '/soi-cau-xsdna.html',
            'Đắk Lắk' => '/soi-cau-xsdlk.html',
            'Đắk Nông' => '/soi-cau-xsdno.html',
            'Gia Lai' => '/soi-cau-xsgl.html',
            'Khánh Hòa' => '/soi-cau-xskh.html',
            'Kon Tum' => '/soi-cau-xskt.html',
            'Ninh Thuận' => '/soi-cau-xsnt.html',
            'Phú Yên' => '/soi-cau-xspy.html',
            'Quảng Bình' => '/soi-cau-xsqb.html',
            'Quảng Nam' => '/soi-cau-xsqna.html',
            'Quảng Ngãi' => '/soi-cau-xsqng.html',
            'Quảng Trị' => '/soi-cau-xsqt.html',
            'Thừa Thiên Huế' => '/soi-cau-xstth.html',
            'Huế' => '/soi-cau-xstth.html',
        ];
    }

    /**
     * Lấy danh sách tỉnh quay xổ số theo miền và thứ
     * 
     * @param string $region 'XSMN' hoặc 'XSMT'
     * @param int $dayOfWeek 1-7 (1 = Thứ 2, 7 = Chủ Nhật)
     * @return array Danh sách tên tỉnh
     */
    public static function getProvincesByDay(string $region, int $dayOfWeek): array
    {
        if ($region === 'XSMN') {
            $schedule = self::getMienNamSchedule();
        } elseif ($region === 'XSMT') {
            $schedule = self::getMienTrungSchedule();
        } else {
            return [];
        }

        return $schedule[$dayOfWeek] ?? [];
    }

    /**
     * Map tên tỉnh sang URL soi cầu
     * 
     * @param string $provinceName Tên tỉnh
     * @return string URL soi cầu
     */
    public static function getSoiCauUrl(string $provinceName): string
    {
        $urlMap = self::getSoiCauUrlMap();
        return $urlMap[$provinceName] ?? '#';
    }

    /**
     * Map province code (slug) sang tên tỉnh và region
     * 
     * @param string $provinceCode Mã tỉnh (ví dụ: 'xsct', 'xsag')
     * @return array|null ['name' => 'Tên tỉnh', 'region' => 'XSMN'|'XSMT'] hoặc null nếu không tìm thấy
     */
    public static function getProvinceInfoByCode(string $provinceCode): ?array
    {
        $code = strtolower($provinceCode);
        
        // Mapping từ code sang tên tỉnh và region
        $xsmnMap = [
            'xsag' => ['name' => 'An Giang', 'region' => 'XSMN'],
            'xsbl' => ['name' => 'Bạc Liêu', 'region' => 'XSMN'],
            'xsblieu' => ['name' => 'Bạc Liêu', 'region' => 'XSMN'],
            'xsbtr' => ['name' => 'Bến Tre', 'region' => 'XSMN'],
            'xsbd' => ['name' => 'Bình Dương', 'region' => 'XSMN'],
            'xsbduong' => ['name' => 'Bình Dương', 'region' => 'XSMN'],
            'xsbp' => ['name' => 'Bình Phước', 'region' => 'XSMN'],
            'xsbth' => ['name' => 'Bình Thuận', 'region' => 'XSMN'],
            'xsbthuan' => ['name' => 'Bình Thuận', 'region' => 'XSMN'],
            'xscm' => ['name' => 'Cà Mau', 'region' => 'XSMN'],
            'xsct' => ['name' => 'Cần Thơ', 'region' => 'XSMN'],
            'xsdl' => ['name' => 'Đà Lạt', 'region' => 'XSMN'],
            'xsdn' => ['name' => 'Đồng Nai', 'region' => 'XSMN'],
            'xsdt' => ['name' => 'Đồng Tháp', 'region' => 'XSMN'],
            'xsdthap' => ['name' => 'Đồng Tháp', 'region' => 'XSMN'],
            'xshg' => ['name' => 'Hậu Giang', 'region' => 'XSMN'],
            'xshcm' => ['name' => 'TP. Hồ Chí Minh', 'region' => 'XSMN'],
            'xskg' => ['name' => 'Kiên Giang', 'region' => 'XSMN'],
            'xsla' => ['name' => 'Long An', 'region' => 'XSMN'],
            'xsst' => ['name' => 'Sóc Trăng', 'region' => 'XSMN'],
            'xstn' => ['name' => 'Tây Ninh', 'region' => 'XSMN'],
            'xstg' => ['name' => 'Tiền Giang', 'region' => 'XSMN'],
            'xstv' => ['name' => 'Trà Vinh', 'region' => 'XSMN'],
            'xsvl' => ['name' => 'Vĩnh Long', 'region' => 'XSMN'],
            'xsvt' => ['name' => 'Vũng Tàu', 'region' => 'XSMN'],
        ];
        
        $xsmtMap = [
            'xsbdi' => ['name' => 'Bình Định', 'region' => 'XSMT'],
            'xsbd' => ['name' => 'Bình Định', 'region' => 'XSMT'],
            'xsdna' => ['name' => 'Đà Nẵng', 'region' => 'XSMT'],
            'xsdng' => ['name' => 'Đà Nẵng', 'region' => 'XSMT'],
            'xsdlk' => ['name' => 'Đắk Lắk', 'region' => 'XSMT'],
            'xsdno' => ['name' => 'Đắk Nông', 'region' => 'XSMT'],
            'xsdnong' => ['name' => 'Đắk Nông', 'region' => 'XSMT'],
            'xsgl' => ['name' => 'Gia Lai', 'region' => 'XSMT'],
            'xskh' => ['name' => 'Khánh Hòa', 'region' => 'XSMT'],
            'xskt' => ['name' => 'Kon Tum', 'region' => 'XSMT'],
            'xsnt' => ['name' => 'Ninh Thuận', 'region' => 'XSMT'],
            'xspy' => ['name' => 'Phú Yên', 'region' => 'XSMT'],
            'xsqb' => ['name' => 'Quảng Bình', 'region' => 'XSMT'],
            'xsqna' => ['name' => 'Quảng Nam', 'region' => 'XSMT'],
            'xsqn' => ['name' => 'Quảng Nam', 'region' => 'XSMT'],
            'xsqng' => ['name' => 'Quảng Ngãi', 'region' => 'XSMT'],
            'xsqt' => ['name' => 'Quảng Trị', 'region' => 'XSMT'],
            'xstth' => ['name' => 'Thừa Thiên Huế', 'region' => 'XSMT'],
            'xsh' => ['name' => 'Thừa Thiên Huế', 'region' => 'XSMT'],
        ];
        
        $allMap = array_merge($xsmnMap, $xsmtMap);
        
        return $allMap[$code] ?? null;
    }

    /**
     * Map tên tỉnh sang URL xo-so (canonical URL từ ArchiveHelper)
     * 
     * @param string $provinceName Tên tỉnh
     * @return string URL xo-so
     */
    public static function getXoSoUrl(string $provinceName): string
    {
        // Sử dụng mapping từ ArchiveHelper::getCanonicalProvinceUrl
        $urlMap = [
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
            'Khánh Hòa' => '/xo-so-khanh-hoa-xskhoa.html',
            'Kon Tum' => '/xo-so-kon-tum-xskontum.html',
            'Ninh Thuận' => '/xo-so-ninh-thuan-xsnt.html',
            'Phú Yên' => '/xo-so-phu-yen-xspy.html',
            'Quảng Bình' => '/xo-so-quang-binh-xsqb.html',
            'Quảng Nam' => '/xo-so-quang-nam-xsqna.html',
            'Quảng Ngãi' => '/xo-so-quang-ngai-xsqng.html',
            'Quảng Trị' => '/xo-so-quang-tri-xsqtri.html',
            'Thừa Thiên Huế' => '/xo-so-hue-xstth.html',
            'Huế' => '/xo-so-hue-xstth.html',
        ];
        
        return $urlMap[$provinceName] ?? '#';
    }

    /**
     * Lấy mã tỉnh uppercase từ province code (ví dụ: xsct -> XSCT)
     * 
     * @param string $provinceCode Mã tỉnh (ví dụ: 'xsct')
     * @return string Mã tỉnh uppercase (ví dụ: 'XSCT')
     */
    public static function getProvinceCodeUppercase(string $provinceCode): string
    {
        return strtoupper($provinceCode);
    }

    /**
     * Lấy danh sách tỉnh quay xổ số hôm nay và hôm qua
     * 
     * @param string $region 'XSMN' hoặc 'XSMT'
     * @return array ['today' => [...], 'yesterday' => [...], 'todayDate' => 'dd-mm-yyyy', 'yesterdayDate' => 'dd-mm-yyyy']
     */
    public static function getTodayAndYesterdayProvinces(string $region): array
    {
        $today = new \DateTime();
        $yesterday = clone $today;
        $yesterday->modify('-1 day');

        $todayDow = (int)$today->format('N'); // 1-7
        $yesterdayDow = (int)$yesterday->format('N'); // 1-7

        $todayProvinces = self::getProvincesByDay($region, $todayDow);
        $yesterdayProvinces = self::getProvincesByDay($region, $yesterdayDow);

        // Format danh sách tỉnh với URL và title
        $formatProvinces = function(array $provinces) {
            $result = [];
            foreach ($provinces as $province) {
                $result[] = [
                    'name' => $province,
                    'url' => self::getSoiCauUrl($province),
                    'title' => 'Soi cầu ' . $province
                ];
            }
            return $result;
        };

        return [
            'today' => $formatProvinces($todayProvinces),
            'yesterday' => $formatProvinces($yesterdayProvinces),
            'todayDate' => $today->format('d-m-Y'),
            'yesterdayDate' => $yesterday->format('d-m-Y'),
        ];
    }
}

