<?php

namespace App\Library;

use App\Models\Provinces;

class ScheduleUrlHelper
{
    public static function buildSlots(\DateTime $date): array
    {
        $dow = (int)$date->format('N'); // 1..7 (1 = Monday, 7 = Sunday)
        $slots = [];
        $regionRoot = [
            'XSMN' => '/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html',
            'XSMT' => '/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html',
            'XSMB' => '/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html',
        ];
        $slots[] = [
            'time_label' => "16h15'",
            'region'     => 'XSMN',
            'items'      => self::buildRegionItems('XSMN', $dow, $regionRoot['XSMN']),
            'image'      => '/img/tructiep/ttxsmn.png'
        ];
        $slots[] = [
            'time_label' => "17h15'",
            'region'     => 'XSMT',
            'items'      => self::buildRegionItems('XSMT', $dow, $regionRoot['XSMT']),
            'image'      => '/img/tructiep/ttxsmt.png'
        ];
        $itemsMB   = [['title' => 'XSMB', 'url' => $regionRoot['XSMB']]];
        $vietlottSchedules = [
            'Power 6/55' => ['days' => [2, 4, 6], 'url' => '/ket-qua-xoso-power-6-55-vietlott.html'],
            'Mega 6/45'  => ['days' => [3, 5, 7], 'url' => '/ket-qua-xoso-mega-6-45-vietlott.html'],
            'Max 3D'     => ['days' => [1, 3, 5], 'url' => '/ket-qua-xoso-max-3d-vietlott.html'],
            'Max 3D Pro' => ['days' => [2, 4, 6], 'url' => '/ket-qua-xoso-max-3d-pro-vietlott.html'],
            'Keno'       => ['days' => [1, 2, 3, 4, 5, 6, 7], 'url' => '/ket-qua-xoso-keno-vietlott.html'],
        ];

        foreach ($vietlottSchedules as $game => $schedule) {
            if (in_array($dow, $schedule['days'])) {
                $itemsMB[] = ['title' => $game, 'url' => $schedule['url']];
            }
        }
        $slots[] = [
            'time_label' => "18h15'",
            'region'     => 'XSMB',
            'items'      => $itemsMB,
            'image'      => '/img/tructiep/ttxsmb.png'
        ];

        return $slots;
    }
    public static function buildSlots1(\DateTime $date): array
    {
        $dow = (int)$date->format('N'); // 1..7 (1 = Monday, 7 = Sunday)
        $slots1 = [];


        // Cấu hình link trang miền (dùng đúng pattern bạn đang xài)
        $regionRoot = [
            'XSMN' => '/truc-tiep-ket-qua-xo-so-mien-nam-ttxsmn-xsmn.html',
            'XSMT' => '/truc-tiep-ket-qua-xo-so-mien-trung-ttxsmt-xsmt.html',
            'XSMB' => '/truc-tiep-ket-qua-xo-so-mien-bac-ttxsmb-xsmb.html',
        ];

        // Slot: XSMN – 16h15
        $slots1[] = [
            'time_label' => "16h15'",
            'region'     => 'XSMN',
            'items'      => self::buildRegionItems('XSMN', $dow, $regionRoot['XSMN']),
            'image'      => '/img/tructiep/ttxsmn.png'
        ];

        // Slot: XSMT – 17h15
        $slots1[] = [
            'time_label' => "17h15'",
            'region'     => 'XSMT',
            'items'      => self::buildRegionItems('XSMT', $dow, $regionRoot['XSMT']),
            'image'      => '/img/tructiep/ttxsmt.png'
        ];

        $slots1[] = [
            'time_label' => "18h15'",
            'region'     => 'XSMB',
            'items'      => self::buildRegionItems('XSMB', $dow, $regionRoot['XSMB']),
            'image'      => '/img/tructiep/ttxsmb.png'
        ];

        return $slots1;
    }

    /**
     * Tạo danh sách link cho một miền ở một thứ cụ thể:
     *  - item đầu tiên là link trang miền
     *  - tiếp theo là các đài mở thưởng ngày đó theo DB (provinces.draw_days)
     */
    private static function buildRegionItems(string $region, int $dow, string $regionUrl): array
    {
        $items = [
            ['title' => $region, 'url' => $regionUrl],
        ];

        $provinces = Provinces::find([
            'conditions' => 'region = :r: AND FIND_IN_SET(:d:, draw_days)',
            'bind'       => ['r' => $region, 'd' => (string)$dow],
            'order'      => 'id ASC',
        ]);

        foreach ($provinces as $p) {
            [$title, $url] = self::provinceLink($p->name, $p->code);
            $items[] = ['title' => $title, 'url' => $url];
        }

        return $items;
    }

    /**
     * Map URL "đặc thù" theo code đài. Sử dụng canonical URLs từ header mapping
     *   /ket-qua-xo-so-{slug-ten-tinh}-{code-thuong}.html
     */
    public static function provinceLink(string $name, string $code): array
    {
        // Sử dụng mapping canonical URLs từ header để đảm bảo nhất quán
        $canonicalUrls = [
            // Miền Nam
            'An Giang' => '/ket-qua-xo-so-an-giang-xsag.html',
            'Bạc Liêu' => '/ket-qua-xo-so-bac-lieu-xsblieu.html',
            'Bến Tre' => '/ket-qua-xo-so-ben-tre-xsbtr.html',
            'Bình Dương' => '/ket-qua-xo-so-binh-duong-xsbduong.html',
            'Bình Phước' => '/ket-qua-xo-so-binh-phuoc-xsbp.html',
            'Bình Thuận' => '/ket-qua-xo-so-binh-thuan-xsbthuan.html',
            'Cà Mau' => '/ket-qua-xo-so-ca-mau-xscm.html',
            'Cần Thơ' => '/ket-qua-xo-so-can-tho-xsct.html',
            'Đà Lạt' => '/ket-qua-xo-so-da-lat-xsdl.html',
            'Đồng Nai' => '/ket-qua-xo-so-dong-nai-xsdn.html',
            'Đồng Tháp' => '/ket-qua-xo-so-dong-thap-xsdthap.html',
            'Hậu Giang' => '/ket-qua-xo-so-hau-giang-xshg.html',
            'TP. Hồ Chí Minh' => '/ket-qua-xo-so-ho-chi-minh-xshcm.html',
            'Hồ Chí Minh' => '/ket-qua-xo-so-ho-chi-minh-xshcm.html',
            'Kiên Giang' => '/ket-qua-xo-so-kien-giang-xskg.html',
            'Long An' => '/ket-qua-xo-so-long-an-xsla.html',
            'Sóc Trăng' => '/ket-qua-xo-so-soc-trang-xsst.html',
            'Tây Ninh' => '/ket-qua-xo-so-tay-ninh-xstn.html',
            'Tiền Giang' => '/ket-qua-xo-so-tien-giang-xstg.html',
            'Trà Vinh' => '/ket-qua-xo-so-tra-vinh-xstv.html',
            'Vĩnh Long' => '/ket-qua-xo-so-vinh-long-xsvl.html',
            'Vũng Tàu' => '/ket-qua-xo-so-vung-tau-xsvt.html',
            
            // Miền Trung
            'Bình Định' => '/ket-qua-xo-so-binh-dinh-xsbdinh.html',
            'Đà Nẵng' => '/ket-qua-xo-so-da-nang-xsdna.html',
            'Đắk Lắk' => '/ket-qua-xo-so-dak-lak-xsdlk.html',
            'Đắk Nông' => '/ket-qua-xo-so-dak-nong-xsdnong.html',
            'Gia Lai' => '/ket-qua-xo-so-gia-lai-xsgl.html',
            'Huế' => '/ket-qua-xo-so-hue-xstth.html',
            'Thừa Thiên Huế' => '/ket-qua-xo-so-hue-xstth.html',
            'Khánh Hòa' => '/ket-qua-xo-so-khanh-hoa-xskhoa.html',
            'Kon Tum' => '/ket-qua-xo-so-kon-tum-xskontum.html',
            'Ninh Thuận' => '/ket-qua-xo-so-ninh-thuan-xsnt.html',
            'Phú Yên' => '/ket-qua-xo-so-phu-yen-xspy.html',
            'Quảng Bình' => '/ket-qua-xo-so-quang-binh-xsqb.html',
            'Quảng Nam' => '/ket-qua-xo-so-quang-nam-xsqn.html',
            'Quảng Ngãi' => '/ket-qua-xo-so-quang-ngai-xsqng.html',
            'Quảng Trị' => '/ket-qua-xo-so-quang-tri-xsqtri.html',
        ];

        // Sử dụng canonical URL nếu có
        if (isset($canonicalUrls[$name])) {
            return [$name, $canonicalUrls[$name]];
        }

        // Fallback chung (cho các tỉnh không có trong danh sách)
        $slug = KqxsHelper::slugify($name);
        $alias = strtolower(KqxsHelper::slugify($code));
        $url = sprintf('/ket-qua-xo-so-%s-%s.html', $slug, $alias);
        return [$name, $url];
    }
}