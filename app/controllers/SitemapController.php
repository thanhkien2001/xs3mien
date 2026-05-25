<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Models\LotteryResults;
use App\Models\News;
use App\Models\Provinces;
use App\Models\PredictionArticles;
use Exception;

class SitemapController extends Controller
{
    public function indexAction()
    {
        // Redirect to sitemap index instead of single sitemap
        return $this->dispatcher->forward([
            'action' => 'sitemapIndex'
        ]);
    }

    public function sitemapIndexAction()
    {
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
        $now = date('c');

        // Define all sub-sitemaps
        $sitemaps = [
            [
                'url' => $baseUrl . '/sitemap-ket-qua-xo-so.xml',
                'lastmod' => $now
            ],
            [
                'url' => $baseUrl . '/sitemap-du-doan-xo-so.xml',
                'lastmod' => $now
            ],
            [
                'url' => $baseUrl . '/sitemap-vietlott.xml',
                'lastmod' => $now
            ],
            [
                'url' => $baseUrl . '/sitemap-quay-thu-xo-so.xml',
                'lastmod' => $now
            ],
            [
                'url' => $baseUrl . '/sitemap-thong-ke-ket-qua-xo-so.xml',
                'lastmod' => $now
            ],
            [
                'url' => $baseUrl . '/tin-tuc.xml',
                'lastmod' => $now
            ]
        ];

        // Đọc các file sitemap đã lưu để hiển thị
        $savedFiles = $this->getSavedSitemapFiles();

        foreach ($savedFiles as $file) {
            $filename = $file['filename'];

            // Parse filename để lấy loại và tháng/năm
            if (preg_match('/^ket-qua-(\d{4})-(\d{2})$/', $filename, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $sitemaps[] = [
                    'url' => $baseUrl . "/sitemap-kqxs-ngay-{$year}-{$month}.xml",
                    'lastmod' => date('c', strtotime($file['generated_at']))
                ];
            } elseif (preg_match('/^vietlott-(\d{4})-(\d{2})$/', $filename, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $sitemaps[] = [
                    'url' => $baseUrl . "/sitemap-vietlott-ngay-{$year}-{$month}.xml",
                    'lastmod' => date('c', strtotime($file['generated_at']))
                ];
            } elseif (preg_match('/^du-doan-(\d{4})-(\d{2})$/', $filename, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $sitemaps[] = [
                    'url' => $baseUrl . "/sitemap-du-doan-ngay-{$year}-{$month}.xml",
                    'lastmod' => date('c', strtotime($file['generated_at']))
                ];
            }
        }

        $xml = $this->buildSitemapIndex($sitemaps);
        return $this->respondXml($xml);
    }

    public function ketQuaXoSoAction()
    {
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
        $now = date('c');

        // Generate URLs for this sitemap
        $urls = [];

        // Trang chủ
        $urls[] = [
            'url' => $baseUrl . '/',
            'lastmod' => $now,
            'priority' => '1.0',
            'changefreq' => 'daily'
        ];

        // Trực tiếp
        $urls[] = [
            'url' => $baseUrl . '/truc-tiep-ket-qua-xo-so-mien-bac-ttxsmb-xsmb.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/truc-tiep-ket-qua-xo-so-mien-nam-ttxsmn-xsmn.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/truc-tiep-ket-qua-xo-so-mien-trung-ttxsmt-xsmt.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];

        // Kết quả xổ số 3 miền
        $urls[] = [
            'url' => $baseUrl . '/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];

        // List pages
        // $urls[] = [
        //     'url' => $baseUrl . '/xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html',
        //     'lastmod' => $now,
        //     'priority' => '0.9',
        //     'changefreq' => 'daily'
        // ];
        // $urls[] = [
        //     'url' => $baseUrl . '/xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html',
        //     'lastmod' => $now,
        //     'priority' => '0.9',
        //     'changefreq' => 'daily'
        // ];
        // $urls[] = [
        //     'url' => $baseUrl . '/xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html',
        //     'lastmod' => $now,
        //     'priority' => '0.9',
        //     'changefreq' => 'daily'
        // ];

        // Theo thứ
        $days = ['thu-2', 'thu-3', 'thu-4', 'thu-5', 'thu-6', 'thu-7', 'chu-nhat'];
        foreach ($days as $day) {
            $urls[] = [
                'url' => $baseUrl . "/xsmb-{$day}-ket-qua-xo-so-mien-bac.html",
                'lastmod' => $now,
                'priority' => '0.8',
                'changefreq' => 'weekly'
            ];
            $urls[] = [
                'url' => $baseUrl . "/xsmn-{$day}-ket-qua-xo-so-mien-nam.html",
                'lastmod' => $now,
                'priority' => '0.8',
                'changefreq' => 'weekly'
            ];
            $urls[] = [
                'url' => $baseUrl . "/xsmt-{$day}-ket-qua-xo-so-mien-trung.html",
                'lastmod' => $now,
                'priority' => '0.8',
                'changefreq' => 'weekly'
            ];
        }

        // Tỉnh miền Nam
        $xsmnProvinces = [
            'an-giang-xsag',
            'bac-lieu-xsblieu',
            'ben-tre-xsbtr',
            'binh-duong-xsbduong',
            'binh-phuoc-xsbp',
            'binh-thuan-xsbthuan',
            'ca-mau-xscm',
            'can-tho-xsct',
            'da-lat-xsdl',
            'dong-nai-xsdn',
            'dong-thap-xsdthap',
            'hau-giang-xshg',
            'ho-chi-minh-xshcm',
            'kien-giang-xskg',
            'long-an-xsla',
            'soc-trang-xsst',
            'tay-ninh-xstn',
            'tien-giang-xstg',
            'tra-vinh-xstv',
            'vinh-long-xsvl',
            'vung-tau-xsvt'
        ];

        foreach ($xsmnProvinces as $province) {
            $urls[] = [
                'url' => $baseUrl . "/ket-qua-xo-so-{$province}.html",
                'lastmod' => $now,
                'priority' => '0.7',
                'changefreq' => 'weekly'
            ];
        }

        // Tỉnh miền Trung
        $xsmtProvinces = [
            'binh-dinh-xsbdinh',
            'da-nang-xsdna',
            'dak-lak-xsdlk',
            'dak-nong-xsdnong',
            'gia-lai-xsgl',
            'hue-xstth',
            'khanh-hoa-xskhoa',
            'kon-tum-xskontum',
            'ninh-thuan-xsnt',
            'phu-yen-xspy',
            'quang-binh-xsqb',
            'quang-nam-xsqn',
            'quang-ngai-xsqng',
            'quang-tri-xsqtri'
        ];

        foreach ($xsmtProvinces as $province) {
            $urls[] = [
                'url' => $baseUrl . "/ket-qua-xo-so-{$province}.html",
                'lastmod' => $now,
                'priority' => '0.7',
                'changefreq' => 'weekly'
            ];
        }

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function duDoanXoSoAction()
    {
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
        $now = date('c');

        // Generate URLs for this sitemap
        $urls = [];

        // Dự đoán chính
        $urls[] = [
            'url' => $baseUrl . '/du-doan-xsmb-c59.html',
            'lastmod' => $now,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/du-doan-xsmn-c61.html',
            'lastmod' => $now,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/du-doan-xsmt-c60.html',
            'lastmod' => $now,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/du-doan-xo-so-soi-cau.html',
            'lastmod' => $now,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];

        // Dự đoán tỉnh XSMN
        $xsmnCodes = ['xsag', 'xsblieu', 'xsbtr', 'xsbd', 'xsbp', 'xsbthuan', 'xscm', 'xsct', 'xsdl', 'xsdn', 'xsdthap', 'xshg', 'xshcm', 'xskg', 'xsla', 'xsst', 'xstn', 'xstg', 'xstv', 'xsvl', 'xsvt'];
        foreach ($xsmnCodes as $code) {
            $urls[] = [
                'url' => $baseUrl . "/du-doan-{$code}.html",
                'lastmod' => $now,
                'priority' => '0.7',
                'changefreq' => 'daily'
            ];
        }

        // Dự đoán tỉnh XSMT
        $xsmtCodes = ['xsh', 'xspy', 'xsdlk', 'xsqn', 'xsdng', 'xskh', 'xsbd', 'xsqb', 'xsqt', 'xsgl', 'xsnt', 'xsdnong', 'xsqng', 'xskt'];
        foreach ($xsmtCodes as $code) {
            $urls[] = [
                'url' => $baseUrl . "/du-doan-{$code}.html",
                'lastmod' => $now,
                'priority' => '0.7',
                'changefreq' => 'daily'
            ];
        }

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function vietlottAction()
    {
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
        $now = date('c');

        // Generate URLs for this sitemap
        $urls = [];

        // Vietlott kết quả
        $urls[] = [
            'url' => $baseUrl . '/ket-qua-xoso-power-6-55-vietlott.html',
            'lastmod' => $now,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/ket-qua-xoso-mega-6-45-vietlott.html',
            'lastmod' => $now,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/ket-qua-xoso-keno-vietlott.html',
            'lastmod' => $now,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/ket-qua-xoso-max-3d-vietlott.html',
            'lastmod' => $now,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/ket-qua-xoso-max-3d-pro-vietlott.html',
            'lastmod' => $now,
            'priority' => '0.8',
            'changefreq' => 'daily'
        ];

        // Trực tiếp Vietlott
        $urls[] = [
            'url' => $baseUrl . '/truc-tiep-ket-qua-xo-so-mega-6-45-vietlott.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/truc-tiep-ket-qua-xo-so-power-6-55-vietlott.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/truc-tiep-ket-qua-xo-so-max-3d-vietlott.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];
        $urls[] = [
            'url' => $baseUrl . '/truc-tiep-ket-qua-xo-so-max-3d-pro-vietlott.html',
            'lastmod' => $now,
            'priority' => '0.9',
            'changefreq' => 'daily'
        ];

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function quayThuXoSoAction()
    {
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
        $now = date('c');

        // Generate URLs for this sitemap
        $urls = [];

        // Quay thử
        $urls[] = [
            'url' => $baseUrl . '/quay-thu-xsmb.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/quay-thu-xsmn.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/quay-thu-xsmt.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/quay-thu-xo-so.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/quay-thu-xo-so-power-6-55-hom-nay.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/quay-thu-xo-so-mega-6-45-hom-nay.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];

        // Quay thử theo tỉnh XSMN
        $xsmnProvinces = [
            'xsag',
            'xsbl',
            'xsbt',
            'xsbd',
            'xsbth',
            'xsbp',
            'xscm',
            'xsct',
            'xsdl',
            'xsdn',
            'xsdt',
            'xshg',
            'xshcm',
            'xskg',
            'xsla',
            'xsst',
            'xstn',
            'xstg',
            'xstv',
            'xsvl',
            'xsvt'
        ];

        foreach ($xsmnProvinces as $province) {
            $urls[] = [
                'url' => $baseUrl . "/quay-thu-{$province}.html",
                'lastmod' => $now,
                'priority' => '0.5',
                'changefreq' => 'weekly'
            ];
        }

        // Quay thử theo tỉnh XSMT
        $xsmtProvinces = [
            'xsbdinh',
            'xsgl',
            'xskh',
            'xsktum',
            'xsnt',
            'xspy',
            'xsqb',
            'xsqn',
            'xsqtri',
            'xstth',
            'xsdno',
            'xsqng',
            'xsdng',
            'xsdlk'
        ];

        foreach ($xsmtProvinces as $province) {
            $urls[] = [
                'url' => $baseUrl . "/quay-thu-{$province}.html",
                'lastmod' => $now,
                'priority' => '0.5',
                'changefreq' => 'weekly'
            ];
        }

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function thongKeKetQuaXoSoAction()
    {
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
        $now = date('c');

        // Generate URLs for this sitemap
        $urls = [];

        // Thống kê
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-xo-so-mien-bac-tk-xsmb.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-xo-so-mien-nam-tk-xsmn.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-xo-so-mien-trung-tk-xsmt.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-xo-so-power-6-55.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-xo-so-mega-6-45.html',
            'lastmod' => $now,
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];

        // Thống kê chi tiết XSMB
        $urls[] = [
            'url' => $baseUrl . '/lo-gan-xsmb.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/dac-biet-xsmb.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/tan-suat-lo-to-xsmb.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];

        // Thống kê chi tiết XSMN
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-lo-gan-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-dac-biet-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-dau-duoi-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-tan-suat-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];

        // Thống kê chi tiết XSMT
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-lo-gan-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-dac-biet-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-dau-duoi-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];
        $urls[] = [
            'url' => $baseUrl . '/thong-ke-tan-suat-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html',
            'lastmod' => $now,
            'priority' => '0.5',
            'changefreq' => 'weekly'
        ];

        // URLs tỉnh Miền Nam (slug-based)
        $mnProvinces = [
            'xshcm' => 'Hồ Chí Minh',
            'xsvt' => 'Vũng Tàu',
            'xsla' => 'Long An',
            'xsbd' => 'Bình Dương',
            'xstv' => 'Tây Ninh',
            'xsdl' => 'Đồng Nai',
            'xsbp' => 'Bình Phước',
            'xshg' => 'Hậu Giang',
            'xskg' => 'Kiên Giang',
            'xstg' => 'Tiền Giang',
            'xsdt' => 'Đồng Tháp',
            'xsag' => 'An Giang',
            'xsbt' => 'Bến Tre',
            'xsbth' => 'Bạc Liêu',
            'xsct' => 'Cà Mau',
            'xsst' => 'Sóc Trăng'
        ];

        foreach ($mnProvinces as $slug => $name) {
            // Thống kê tổng hợp
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];

            // Lô gan
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-lo-gan-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];

            // Đặc biệt
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-dac-biet-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];

            // Đầu đuôi
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-dau-duoi-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];

            // Tần suất
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-tan-suat-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];
        }

        // URLs tỉnh Miền Trung (slug-based)
        $mtProvinces = [
            'xsdng' => 'Đà Nẵng',
            'xsbdinh' => 'Bình Định',
            'xstth' => 'Thừa Thiên Huế',
            'xsdlk' => 'Đắk Lắk',
            'xsqng' => 'Quảng Ngãi',
            'xsqt' => 'Quảng Trị',
            'xsqb' => 'Quảng Bình',
            'xsqn' => 'Quảng Nam',
            'xsdno' => 'Đắk Nông',
            'xsgl' => 'Gia Lai',
            'xsnt' => 'Ninh Thuận',
            'xspy' => 'Phú Yên',
            'xskh' => 'Khánh Hòa',
            'xs-kontum' => 'Kon Tum'
        ];

        foreach ($mtProvinces as $slug => $name) {
            // Thống kê tổng hợp
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];

            // Lô gan
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-lo-gan-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];

            // Đặc biệt
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-dac-biet-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];

            // Đầu đuôi
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-dau-duoi-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];

            // Tần suất
            $urls[] = [
                'url' => $baseUrl . '/thong-ke-tan-suat-' . $slug . '.html',
                'lastmod' => $now,
                'priority' => '0.4',
                'changefreq' => 'weekly'
            ];
        }

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function monthlyDuDoanAction()
    {
        $year = $this->dispatcher->getParam('year');
        $month = $this->dispatcher->getParam('month');
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];

        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);

        // Thử đọc URLs từ file trước (file đã merge bao gồm cả tỉnh)
        $urls = $this->loadUrlsFromFile("du-doan-{$year}-{$monthStr}");

        // Nếu không có file, tạo mới và lưu
        if ($urls === null) {
            $urls = [];

            // Generate URLs for each day of the month
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            // Lịch quay theo thứ trong tuần
            $schedule = [
                // Thứ 2 (Monday)
                1 => [
                    'xsmn' => ['ca-mau', 'tp-ho-chi-minh', 'dong-thap'],
                    'xsmt' => ['thua-thien-hue', 'phu-yen']
                ],
                // Thứ 3 (Tuesday)
                2 => [
                    'xsmn' => ['bac-lieu', 'ben-tre', 'vung-tau'],
                    'xsmt' => ['dak-lak', 'quang-nam']
                ],
                // Thứ 4 (Wednesday)
                3 => [
                    'xsmn' => ['can-tho', 'soc-trang', 'dong-nai'],
                    'xsmt' => ['da-nang', 'khanh-hoa']
                ],
                // Thứ 5 (Thursday)
                4 => [
                    'xsmn' => ['an-giang', 'binh-thuan', 'tay-ninh'],
                    'xsmt' => ['binh-dinh', 'quang-binh', 'quang-tri']
                ],
                // Thứ 6 (Friday)
                5 => [
                    'xsmn' => ['binh-duong', 'tra-vinh', 'vinh-long'],
                    'xsmt' => ['gia-lai', 'ninh-thuan']
                ],
                // Thứ 7 (Saturday)
                6 => [
                    'xsmn' => ['binh-phuoc', 'hau-giang', 'ho-chi-minh', 'long-an'],
                    'xsmt' => ['da-nang', 'dak-nong', 'quang-ngai']
                ],
                // Chủ nhật (Sunday)
                0 => [
                    'xsmn' => ['kien-giang', 'tien-giang', 'da-lat'],
                    'xsmt' => ['khanh-hoa', 'kon-tum', 'thua-thien-hue']
                ]
            ];

            // Chỉ tạo URLs từ ngày 28/9/2025 trở đi (ngày bắt đầu có dữ liệu)
            $startDate = mktime(0, 0, 0, 9, 28, 2025); // 28/9/2025

            // Tính ngày hiện tại và logic tạo URLs
            $currentHour = date('H');
            $currentDay = date('j');
            $currentMonth = date('n');
            $currentYear = date('Y');

            // Tính ngày bắt đầu cho tháng này
            $monthStartDay = 1;
            if ($year < 2025 || ($year == 2025 && $month < 9)) {
                // Nếu tháng này trước 28/9/2025, không tạo URLs
                $monthStartDay = $daysInMonth + 1; // Không tạo URLs nào
            } elseif ($year == 2025 && $month == 9) {
                // Nếu là tháng 9/2025, bắt đầu từ ngày 28
                $monthStartDay = 28;
            }

            // Tính ngày cuối cùng dựa trên logic thời gian
            $endDay = $daysInMonth;

            // Nếu là tháng hiện tại
            if ($year == $currentYear && $month == $currentMonth) {
                $monthStartDay = max($monthStartDay, $currentDay);

                // Nếu sau 7 giờ tối, tạo thêm ngày mai
                if ($currentHour >= 19) {
                    $endDay = min($currentDay + 1, $daysInMonth);
                } else {
                    // Trước 7 giờ tối, chỉ tạo ngày hôm nay
                    $endDay = $currentDay;
                }
            }

            for ($day = $monthStartDay; $day <= $endDay; $day++) {
                $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                $currentDate = mktime(0, 0, 0, $month, $day, $year);

                $lastmod = date('c', $currentDate);

                // Lấy thứ trong tuần (0=Chủ nhật, 1=Thứ 2, ..., 6=Thứ 7)
                $dayOfWeek = date('w', $currentDate);

                // Tạo URLs theo lịch quay
                if (isset($schedule[$dayOfWeek])) {
                    // URLs chung cho từng miền
                    $urls[] = [
                        'url' => $baseUrl . "/du-doan-xsmb-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-bac-{$dayStr}-{$monthStr}-{$year}.html",
                        'lastmod' => $lastmod,
                        'priority' => '0.8',
                        'changefreq' => 'daily'
                    ];

                    $urls[] = [
                        'url' => $baseUrl . "/du-doan-xsmn-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-nam-{$dayStr}-{$monthStr}-{$year}.html",
                        'lastmod' => $lastmod,
                        'priority' => '0.8',
                        'changefreq' => 'daily'
                    ];

                    $urls[] = [
                        'url' => $baseUrl . "/du-doan-xsmt-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-mien-trung-{$dayStr}-{$monthStr}-{$year}.html",
                        'lastmod' => $lastmod,
                        'priority' => '0.8',
                        'changefreq' => 'daily'
                    ];

                    // URLs cho từng tỉnh Miền Nam
                    if (isset($schedule[$dayOfWeek]['xsmn'])) {
                        foreach ($schedule[$dayOfWeek]['xsmn'] as $province) {
                            // Mapping tên tỉnh đúng
                            $provinceNames = [
                                'ca-mau' => 'ca-mau',
                                'tp-ho-chi-minh' => 'tp-ho-chi-minh',
                                'dong-thap' => 'dong-thap',
                                'bac-lieu' => 'bac-lieu',
                                'ben-tre' => 'ben-tre',
                                'vung-tau' => 'vung-tau',
                                'can-tho' => 'can-tho',
                                'soc-trang' => 'soc-trang',
                                'dong-nai' => 'dong-nai',
                                'an-giang' => 'an-giang',
                                'binh-thuan' => 'binh-thuan',
                                'tay-ninh' => 'tay-ninh',
                                'binh-duong' => 'binh-duong',
                                'tra-vinh' => 'tra-vinh',
                                'vinh-long' => 'vinh-long',
                                'binh-phuoc' => 'binh-phuoc',
                                'hau-giang' => 'hau-giang',
                                'long-an' => 'long-an',
                                'kien-giang' => 'kien-giang',
                                'tien-giang' => 'tien-giang',
                                'da-lat' => 'da-lat'
                            ];

                            $provinceName = $provinceNames[$province] ?? $province;
                            $urls[] = [
                                'url' => $baseUrl . "/du-doan-xsmn-{$province}-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-{$provinceName}-{$dayStr}-{$monthStr}-{$year}.html",
                                'lastmod' => $lastmod,
                                'priority' => '0.7',
                                'changefreq' => 'daily'
                            ];
                        }
                    }

                    // URLs cho từng tỉnh Miền Trung
                    if (isset($schedule[$dayOfWeek]['xsmt'])) {
                        foreach ($schedule[$dayOfWeek]['xsmt'] as $province) {
                            // Mapping tên tỉnh đúng cho Miền Trung
                            $provinceNames = [
                                'thua-thien-hue' => 'thua-thien-hue',
                                'phu-yen' => 'phu-yen',
                                'dak-lak' => 'dak-lak',
                                'quang-nam' => 'quang-nam',
                                'da-nang' => 'da-nang',
                                'khanh-hoa' => 'khanh-hoa',
                                'binh-dinh' => 'binh-dinh',
                                'quang-binh' => 'quang-binh',
                                'quang-tri' => 'quang-tri',
                                'gia-lai' => 'gia-lai',
                                'ninh-thuan' => 'ninh-thuan',
                                'dak-nong' => 'dak-nong',
                                'quang-ngai' => 'quang-ngai',
                                'kon-tum' => 'kon-tum'
                            ];

                            $provinceName = $provinceNames[$province] ?? $province;
                            $urls[] = [
                                'url' => $baseUrl . "/du-doan-xsmt-{$province}-{$dayStr}-{$monthStr}-{$year}-soi-cau-xo-so-{$provinceName}-{$dayStr}-{$monthStr}-{$year}.html",
                                'lastmod' => $lastmod,
                                'priority' => '0.7',
                                'changefreq' => 'daily'
                            ];
                        }
                    }
                }
            }

            // Lưu URLs vào file để persistent
            $this->saveUrlsToFile($urls, "du-doan-{$year}-{$monthStr}");
        }

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function monthlyKetQuaAction()
    {
        $year = $this->dispatcher->getParam('year');
        $month = $this->dispatcher->getParam('month');
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];

        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);

        // Thử đọc URLs từ file trước
        $urls = $this->loadUrlsFromFile("ket-qua-{$year}-{$monthStr}");

        // Nếu không có file, tạo mới và lưu
        if ($urls === null) {
            $urls = [];

            // Generate URLs for each day of the month
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
                $lastmod = date('c', mktime(0, 0, 0, $month, $day, $year));

                // Kết quả theo ngày
                $urls[] = [
                    'url' => $baseUrl . "/xsmb-{$dayStr}-{$monthStr}-ket-qua-xo-so-mien-bac-ngay-{$dayStr}-{$monthStr}-{$year}.html",
                    'lastmod' => $lastmod,
                    'priority' => '0.8',
                    'changefreq' => 'daily'
                ];

                $urls[] = [
                    'url' => $baseUrl . "/xsmn-{$dayStr}-{$monthStr}-ket-qua-xo-so-mien-nam-ngay-{$dayStr}-{$monthStr}-{$year}.html",
                    'lastmod' => $lastmod,
                    'priority' => '0.8',
                    'changefreq' => 'daily'
                ];

                $urls[] = [
                    'url' => $baseUrl . "/xsmt-{$dayStr}-{$monthStr}-ket-qua-xo-so-mien-trung-ngay-{$dayStr}-{$monthStr}-{$year}.html",
                    'lastmod' => $lastmod,
                    'priority' => '0.8',
                    'changefreq' => 'daily'
                ];
            }

            // Lưu URLs vào file để persistent
            $this->saveUrlsToFile($urls, "ket-qua-{$year}-{$monthStr}");
        }

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function monthlyVietlottAction()
    {
        $year = $this->dispatcher->getParam('year');
        $month = $this->dispatcher->getParam('month');
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];

        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);

        // Thử đọc URLs từ file trước
        $urls = $this->loadUrlsFromFile("vietlott-{$year}-{$monthStr}");

        // Nếu không có file, tạo mới và lưu
        if ($urls === null) {
            $urls = [];

            // Generate URLs for each day of the month
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            // Tạo tất cả ngày trong tháng để lưu persistent
            $monthStartDay = 1;
            $endDay = $daysInMonth;

            for ($day = $monthStartDay; $day <= $endDay; $day++) {
                $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                $lastmod = date('c', mktime(0, 0, 0, $month, $day, $year));

                // Lấy thứ trong tuần (0=Chủ nhật, 1=Thứ 2, ..., 6=Thứ 7)
                $dayOfWeek = date('w', mktime(0, 0, 0, $month, $day, $year));

                // Vietlott theo lịch quay thực tế
                // Thứ 3, 5, 7: Power 655 + Max3D Pro
                if (in_array($dayOfWeek, [2, 4, 6])) { // Thứ 3, 5, 7
                    $urls[] = [
                        'url' => $baseUrl . "/ket-qua-xoso-power-6-55-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
                        'lastmod' => $lastmod,
                        'priority' => '0.8',
                        'changefreq' => 'daily'
                    ];

                    $urls[] = [
                        'url' => $baseUrl . "/ket-qua-xoso-max-3d-pro-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
                        'lastmod' => $lastmod,
                        'priority' => '0.8',
                        'changefreq' => 'daily'
                    ];
                }

                // Thứ 2, 4, 6: Max 3D
                if (in_array($dayOfWeek, [1, 3, 5])) { // Thứ 2, 4, 6
                    $urls[] = [
                        'url' => $baseUrl . "/ket-qua-xoso-max-3d-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
                        'lastmod' => $lastmod,
                        'priority' => '0.8',
                        'changefreq' => 'daily'
                    ];
                }

                // Thứ 4, 6, CN: Mega 645
                if (in_array($dayOfWeek, [3, 5, 0])) { // Thứ 4, 6, CN
                    $urls[] = [
                        'url' => $baseUrl . "/ket-qua-xoso-mega-6-45-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
                        'lastmod' => $lastmod,
                        'priority' => '0.8',
                        'changefreq' => 'daily'
                    ];
                }

                // Keno quay hàng ngày
                $urls[] = [
                    'url' => $baseUrl . "/ket-qua-xoso-keno-vietlott-{$year}-{$monthStr}-{$dayStr}.html",
                    'lastmod' => $lastmod,
                    'priority' => '0.8',
                    'changefreq' => 'daily'
                ];
            }

            // Lưu URLs vào file để persistent
            $this->saveUrlsToFile($urls, "vietlott-{$year}-{$monthStr}");
        }

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function imagesAction()
    {
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
        $now = date('c');

        // Generate URLs for this sitemap
        $urls = [];

        // Add main logo
        $urls[] = [
            'url' => $baseUrl . '/',
            'lastmod' => $now,
            'priority' => '1.0',
            'changefreq' => 'monthly'
        ];

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function newsAction()
    {
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];

        // Generate URLs for this sitemap
        $urls = [];

        // TODO: Add actual news articles from database
        // Example:
        // $news = News::find(['order' => 'created_at DESC', 'limit' => 1000]);
        // foreach ($news as $article) {
        //     $urls[] = [
        //         'url' => $baseUrl . '/' . $article->slug,
        //         'lastmod' => date('c', strtotime($article->updated_at)),
        //         'priority' => '0.7',
        //         'changefreq' => 'weekly'
        //     ];
        // }

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function predictionsAction()
    {
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];

        // Generate URLs for this sitemap
        $urls = [];

        // Lấy tất cả bài viết dự đoán từ database
        $predictions = PredictionArticles::find([
            'order' => 'prediction_date DESC, id DESC',
            'limit' => 1000 // Giới hạn 1000 bài viết mới nhất
        ]);

        foreach ($predictions as $prediction) {
            $region = strtolower($prediction->region);
            $url = $baseUrl . '/' . $prediction->slug;

            // Tạo lastmod từ ngày tạo bài viết
            $lastmod = date('c', strtotime($prediction->created_at));

            $urls[] = [
                'url' => $url,
                'lastmod' => $lastmod,
                'priority' => '0.8',
                'changefreq' => 'daily'
            ];
        }

        $xml = $this->buildUrlset($urls);
        return $this->respondXml($xml);
    }

    public function robotsAction()
    {
        $this->response->setContentType('text/plain');
        $this->response->setHeader('Content-Type', 'text/plain; charset=utf-8');

        $robotsContent = file_get_contents(BASE_PATH . '/public/robots.txt');
        $this->response->setContent($robotsContent);
        return $this->response;
    }

    private function generateUrlElement($url, $priority, $changefreq)
    {
        return "  <url>\n" .
            "    <loc>{$url}</loc>\n" .
            "    <lastmod>" . date('Y-m-d') . "</lastmod>\n" .
            "    <changefreq>{$changefreq}</changefreq>\n" .
            "    <priority>{$priority}</priority>\n" .
            "  </url>\n";
    }

    /**
     * Lưu URLs vào file để persistent
     */
    private function saveUrlsToFile($urls, $filename)
    {
        try {
            // Tạo thư mục cache nếu chưa có
            $cacheDir = __DIR__ . '/../../cache/sitemaps/';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            // Tạo file path
            $filePath = $cacheDir . $filename . '.json';

            // Chuẩn bị dữ liệu để lưu
            $data = [
                'generated_at' => date('Y-m-d H:i:s'),
                'total_urls' => count($urls),
                'urls' => $urls
            ];

            // Lưu vào file JSON
            $result = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($result !== false) {
                error_log("✅ Đã lưu {$data['total_urls']} URLs vào file: {$filePath}");
            } else {
                error_log("❌ Lỗi khi lưu file: {$filePath}");
            }
        } catch (Exception $e) {
            error_log("❌ Exception khi lưu file sitemap: " . $e->getMessage());
        }
    }

    /**
     * Đọc URLs từ file đã lưu
     */
    private function loadUrlsFromFile($filename)
    {
        try {
            $cacheDir = __DIR__ . '/../../cache/sitemaps/';
            $filePath = $cacheDir . $filename . '.json';

            if (!file_exists($filePath)) {
                return null;
            }

            $content = file_get_contents($filePath);
            $data = json_decode($content, true);

            if ($data && isset($data['urls'])) {
                error_log("✅ Đã đọc {$data['total_urls']} URLs từ file: {$filePath}");
                return $data['urls'];
            }

            return null;
        } catch (Exception $e) {
            error_log("❌ Exception khi đọc file sitemap: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy danh sách các file sitemap đã lưu
     */
    private function getSavedSitemapFiles()
    {
        try {
            $cacheDir = __DIR__ . '/../../cache/sitemaps/';

            if (!is_dir($cacheDir)) {
                return [];
            }

            $files = glob($cacheDir . '*.json');
            $sitemaps = [];

            foreach ($files as $file) {
                $filename = basename($file, '.json');
                $content = file_get_contents($file);
                $data = json_decode($content, true);

                if ($data && isset($data['generated_at'])) {
                    $sitemaps[] = [
                        'filename' => $filename,
                        'generated_at' => $data['generated_at'],
                        'total_urls' => $data['total_urls'] ?? 0,
                        'file_path' => $file
                    ];
                }
            }

            // Sắp xếp theo thời gian tạo (mới nhất trước)
            usort($sitemaps, function ($a, $b) {
                return strtotime($b['generated_at']) - strtotime($a['generated_at']);
            });

            return $sitemaps;
        } catch (Exception $e) {
            error_log("❌ Exception khi lấy danh sách file sitemap: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trả về XML response với Content-Type đúng
     */
    private function respondXml(string $xml): \Phalcon\Http\ResponseInterface
    {
        $this->view->disable();
        $this->response->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->response->setContent($xml);
        return $this->response;
    }

    /**
     * Tạo XML header với XSL stylesheet
     */
    private function xmlHeaderWithXsl(string $xslPath = '/sitemaps/sitemap.xsl'): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<?xml-stylesheet type="text/xsl" href="' . $xslPath . '"?>' . "\n";
    }

    /**
     * Build sitemap index XML
     */
    private function buildSitemapIndex(array $sitemaps): string
    {
        $xml = $this->xmlHeaderWithXsl();
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $sm) {
            $loc = htmlspecialchars($sm['url'], ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $lastmod = htmlspecialchars($sm['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $xml .= "  <sitemap>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * Build urlset XML
     */
    private function buildUrlset(array $urls): string
    {
        $xml = $this->xmlHeaderWithXsl();
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $u) {
            $loc = htmlspecialchars($u['url'], ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $lastmod = htmlspecialchars($u['lastmod'] ?? date('c'), ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $changefreq = htmlspecialchars($u['changefreq'] ?? 'weekly', ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $priority = htmlspecialchars($u['priority'] ?? '0.5', ENT_XML1 | ENT_COMPAT, 'UTF-8');

            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
            $xml .= "    <priority>{$priority}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
