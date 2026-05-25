<?php

$router = $di->getRouter();

// Define your routes here
// Detail pages cho cả Giải mã giấc mơ và Bi kíp (đặt trước notFound để catch các URL detail)
// Route pattern này sẽ match các URL như /mo-thay-xxx.html, /soi-cau-pascal-la-gi-d3124.html, etc.
// Controller sẽ kiểm tra file view có tồn tại trong cả giacmo và bikip, nếu không thì trả về 404

// Soi cầu bạch thủ XSMB
$router->add(
    '/soi-cau-bach-thu.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'index'
    ]
);

// Soi cầu nhiều nháy XSMB
$router->add(
    '/soi-cau-nhieu-nhay.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'nhieuNhay'
    ]
);

// Soi cầu lật liên tục XSMB
$router->add(
    '/soi-cau-lat-lien-tuc.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'latLienTuc'
    ]
);

$router->add(
    '/{slug:[a-z0-9\-]+}.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'GiacMo',
        'action' => 'detail',
        'slug' => 1
    ]
)->setName('article_detail');



// Router cho Win2888 với ngày cụ thể - PHẢI ĐẶT TRƯỚC route catch-all
$router->add(
    "/soi-cau-xsmb-win2888-asia-{date:[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}}.html",
    [
        "controller" => "Soicau",
        "action" => "soiCauWin2888ByDate",
        "date" => 1
    ]
);

// Router cho dự đoán số đề với ngày cụ thể - PHẢI ĐẶT TRƯỚC route catch-all
$router->add(
    "/du-doan-so-de-hom-nay-{date:[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}}.html",
    [
        "controller" => "Soicau",
        "action" => "duDoanSoDeByDate",
        "date" => 1
    ]
);

// Soi cầu cho 1 tỉnh cụ thể
$router->add(
    '/soi-cau-{province:[a-z0-9]+}.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'province',
        'province' => 1
    ]
);
// Route cho trang Soi cầu XSMB tổng hợp - MUST be FIRST to avoid conflict with pattern routes
$router->add(
    '/soi-cau-xsmb.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'soiCauXsmb'
    ]
)->setName('soi_cau_xsmb');

$router->add(
    '/bach-thu-de-xsmb-hom-nay.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'BachThuDe',
        'action' => 'bachThuDeXsmbHomNay'
    ]
)->setName('bach_thu_de_xsmb_hom_nay');

$router->add(
    '/soi-cau-song-thu-de.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'BachThuDe',
        'action' => 'soiCauSongThuDe'
    ]
)->setName('soi_cau_song_thu_de');


$router->add(
    '/soi-cau-666-xsmb.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'SoicauRight',
        'action' => 'soiCau666Xsmb'
    ]
)->setName('soi_cau_666_xsmb');

$router->add(
    '/soi-cau-7777-soi-cau-chuan-nhat.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'SoicauRight',
        'action' => 'soiCau7777'
    ]
)->setName('soi_cau_7777');

$router->add('/xs-minh-ngoc-hom-nay.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'SoicauRight',
    'action' => 'xsMinhNgocHomNay'
])->setName('xs_minh_ngoc_hom_nay');

$router->add('/soi-cau-tot-soi-cau-xo-so-3-mien.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'SoicauRight',
    'action' => 'soiCauTot'
])->setName('soi_cau_tot');

$router->add('/soi-cau-viet-soi-cau-lo-viet.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'SoicauRight',
    'action' => 'soiCauViet'
])->setName('soi_cau_viet');

$router->add('/soi-cau-366-mb.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'SoicauRight',
    'action' => 'soiCau366Mb'
])->setName('soi_cau_366_mb');

$router->add('/soi-cau-xsmt.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'SoicauNew',
    'action' => 'soiCauXsmt'
])->setName('soi_cau_xsmt');

$router->add('/soi-cau-xsmn.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'SoicauNew',
    'action' => 'soiCauXsmn'
])->setName('soi_cau_xsmn');


$router->add(
    '/soi-cau-lo-chinh-xac-nhat-mien-bac.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'soiCauLoChinhXac'
    ]
)->setName('soi_cau_lo_chinh_xac');

$router->add(
    '/soi-cau-lo-de-chuan-xsmb.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'SoicauRight',
        'action' => 'soiCauLoDeChuanXsmb'
    ]
)->setName('soi_cau_lo_de_chuan_xsmb');

$router->add(
    '/bac-nho-lo-de-xsmb.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'bacNhoLoDe'
    ]
)->setName('bac_nho_lo_de');

$router->add(
    '/dan-de-10-so-nuoi-khung-5-ngay.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'dande',
        'action' => 'danDe10SoNuoiKhung5Ngay'
    ]
);

$router->add(
    '/nuoi-dan-de-20-so-khung-3-ngay.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'dande',
        'action' => 'nuoiDanDe20SoKhung3Ngay'
    ]
);

$router->add(
    '/dan-de-36-so-khung-3-ngay.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'dande',
        'action' => 'danDe36SoKhung3Ngay'
    ]
);

$router->add(
    '/nuoi-lo-khung-xsmb.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'dande',
        'action' => 'nuoiLoKhungXsmb'
    ]
);

$router->add(
    '/lo-top.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'dande',
        'action' => 'loTop'
    ]
);

$router->add(
    '/soi-cau-xsmb-mien-phi-ngay-hom-nay.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'dande',
        'action' => 'soiCauXsmbMienPhiNgayHomNay'
    ]
);
// Routes for Giải mã giấc mơ - MUST be FIRST
$router->add(
    "/giai-ma-giac-mo-lo-de-y-nghia-nhung-giac-mo-va-chot-so-c111",
    [
        'namespace' => 'App\Controllers',
        "controller" => "GiacMo",
        "action" => "index",
    ]
);

$router->add(
    "/giai-ma-giac-mo-lo-de-y-nghia-nhung-giac-mo-va-chot-so-c111.html",
    [
        'namespace' => 'App\Controllers',
        "controller" => "GiacMo",
        "action" => "index",
    ]
);

$router->add(
    "/giai-ma-giac-mo-lo-de-y-nghia-nhung-giac-mo-va-chot-so-c111/page/{page:[0-9]+}.html",
    [
        'namespace' => 'App\Controllers',
        "controller" => "GiacMo",
        "action" => "loadMorePage",
        "page" => 1
    ]
);

// Routes for Bi kíp về phương pháp soi cầu lô đề chuẩn
$router->add(
    "/bi-kip-ve-phuong-phap-soi-cau-lo-de-chuan-c110",
    [
        'namespace' => 'App\Controllers',
        "controller" => "Bikip",
        "action" => "index",
    ]
);

$router->add(
    "/bi-kip-ve-phuong-phap-soi-cau-lo-de-chuan-c110.html",
    [
        'namespace' => 'App\Controllers',
        "controller" => "Bikip",
        "action" => "index",
    ]
);

$router->add(
    "/bi-kip-ve-phuong-phap-soi-cau-lo-de-chuan-c110/page/{page:[0-9]+}.html",
    [
        'namespace' => 'App\Controllers',
        "controller" => "Bikip",
        "action" => "loadMorePage",
        "page" => 1
    ]
);

// Routes for category pages - MUST be FIRST to ensure they match before pattern routes
$router->add(
    '/du-doan-xsmb-c59.html',
    [
        'controller' => 'dudoan',
        'action' => 'categoryXsmb'
    ]
)->setName('category_xsmb');

$router->add(
    '/du-doan-xsmb-c59/page/{page:[0-9]+}.html',
    [
        'controller' => 'dudoan',
        'action' => 'categoryXsmbPage',
        'page' => 1
    ]
)->setName('category_xsmb_page');

$router->add(
    '/du-doan-xsmn-c61.html',
    [
        'controller' => 'dudoan',
        'action' => 'categoryXsmn'
    ]
)->setName('category_xsmn');

$router->add(
    '/du-doan-xsmn-c61/page/{page:[0-9]+}.html',
    [
        'controller' => 'dudoan',
        'action' => 'categoryXsmnPage',
        'page' => 1
    ]
)->setName('category_xsmn_page');

$router->add(
    '/du-doan-xsmt-c60.html',
    [
        'controller' => 'dudoan',
        'action' => 'categoryXsmt'
    ]
)->setName('category_xsmt');

$router->add(
    '/du-doan-xsmt-c60/page/{page:[0-9]+}.html',
    [
        'controller' => 'dudoan',
        'action' => 'categoryXsmtPage',
        'page' => 1
    ]
)->setName('category_xsmt_page');

// Sổ kết quả 30 ngày - Miền Bắc
$router->add(
    '/thong-ke-xsmb-30-ngay-xo-so-mien-bac-30-ngay.html',
    [
        'controller' => 'sokequa',
        'action' => 'soKetQuaXsmb30Ngay'
    ]
)->setName('so_ket_qua_xsmb_30_ngay');

// Sổ kết quả 60 ngày - Miền Bắc
$router->add(
    '/thong-ke-xsmb-60-ngay-xo-so-mien-bac-60-ngay.html',
    [
        'controller' => 'sokequa',
        'action' => 'soKetQuaXsmb60Ngay'
    ]
)->setName('so_ket_qua_xsmb_60_ngay');

// Sổ kết quả 90 ngày - Miền Bắc
$router->add(
    '/thong-ke-xsmb-90-ngay-xo-so-mien-bac-90-ngay.html',
    [
        'controller' => 'sokequa',
        'action' => 'soKetQuaXsmb90Ngay'
    ]
)->setName('so_ket_qua_xsmb_90_ngay');

// Sổ kết quả 100 ngày - Miền Bắc
$router->add(
    '/thong-ke-xsmb-100-ngay-xo-so-mien-bac-100-ngay.html',
    [
        'controller' => 'sokequa',
        'action' => 'soKetQuaXsmb100Ngay'
    ]
)->setName('so_ket_qua_xsmb_100_ngay');

// Sổ kết quả 30 ngày - Miền Nam
$router->add(
    '/thong-ke-xsmn-30-ngay-xo-so-mien-nam-30-ngay.html',
    [
        'controller' => 'sokequa',
        'action' => 'soKetQuaXsmn30Ngay'
    ]
)->setName('so_ket_qua_xsmn_30_ngay');

// Sổ kết quả 30 ngày - Miền Trung
// Sổ kết quả 10 ngày - Miền Bắc
$router->add(
    '/thong-ke-xsmb-10-ngay-xo-so-mien-bac-10-ngay.html',
    [
        'controller' => 'sokequa',
        'action' => 'soKetQuaXsmb10Ngay'
    ]
)->setName('so_ket_qua_xsmb_10_ngay');

$router->add(
    '/soi-cau-kubet-mien-bac.html',
    [
        'controller' => 'soicau',
        'action' => 'soiCauKubet'
    ]
)->setName('soi_cau_kubet');



$router->add(
    '/thong-ke-xsmt-30-ngay-xo-so-mien-trung-30-ngay.html',
    [
        'controller' => 'sokequa',
        'action' => 'soKetQuaXsmt30Ngay'
    ]
)->setName('so_ket_qua_xsmt_30_ngay');

$router->add(
    "/ket-qua-xoso-power-6-55-vietlott-{date}",
    [
        "controller" => "vietlott655",
        "action" => "index655",
        "date" => 1
    ]
);

$router->add(
    "/ket-qua-xoso-power-6-55-vietlott-{date}.html",
    [
        "controller" => "vietlott655",
        "action" => "index655",
        "date" => 1
    ]
);
$router->add(
    "/ket-qua-xoso-power-6-55-vietlott.html",
    [
        "controller" => "vietlott655",
        "action" => "index655",
    ]
);

// API routes for Vietlott655
$router->add(
    "/vietlott655/byDate",
    [
        "controller" => "vietlott655",
        "action" => "byDate"
    ]
);

$router->add(
    "/vietlott655/byDate.html",
    [
        "controller" => "vietlott655",
        "action" => "byDate"
    ]
);

$router->add(
    "/vietlott655/results",
    [
        "controller" => "vietlott655",
        "action" => "results"
    ]
);

$router->add(
    "/vietlott655/results.html",
    [
        "controller" => "vietlott655",
        "action" => "results"
    ]
);

$router->add(
    "/ket-qua-xoso-mega-6-45-vietlott-{date}",
    [
        "controller" => "vietlott645",
        "action" => "index645",
        "date" => 1
    ]
);

$router->add(
    "/ket-qua-xoso-mega-6-45-vietlott-{date}.html",
    [
        "controller" => "vietlott645",
        "action" => "index645",
        "date" => 1
    ]
);
$router->add(
    "/ket-qua-xoso-mega-6-45-vietlott.html",
    [
        "controller" => "vietlott645",
        "action" => "index645",
    ]
);


// API routes for Vietlott645
$router->add(
    "/vietlott645/byDate",
    [
        "controller" => "vietlott645",
        "action" => "byDate"
    ]
);

$router->add(
    "/vietlott645/byDate.html",
    [
        "controller" => "vietlott645",
        "action" => "byDate"
    ]
);

$router->add(
    "/vietlott645/results",
    [
        "controller" => "vietlott645",
        "action" => "results"
    ]
);

$router->add(
    "/vietlott645/results.html",
    [
        "controller" => "vietlott645",
        "action" => "results"
    ]
);
$router->add(
    "/ket-qua-xoso-keno-vietlott-{date}",
    [
        "controller" => "keno",
        "action" => "kenoindex",
        "date" => 1
    ]
);

$router->add(
    "/ket-qua-xoso-keno-vietlott-{date}.html",
    [
        "controller" => "keno",
        "action" => "kenoindex",
        "date" => 1
    ]
);

$router->add(
    "/ket-qua-xoso-keno-vietlott.html",
    [
        "controller" => "keno",
        "action" => "kenoindex",
    ]
);
$router->add(
    "/ket-qua-xoso-max-3d-vietlott-{date}",
    [
        "controller" => "vietlottmax",
        "action" => "index3dmax",
        "date" => 1
    ]
);

$router->add(
    "/ket-qua-xoso-max-3d-vietlott-{date}.html",
    [
        "controller" => "vietlottmax",
        "action" => "index3dmax",
        "date" => 1
    ]
);

$router->add(
    "/ket-qua-xoso-max-3d-vietlott.html",
    [
        "controller" => "vietlottmax",
        "action" => "index3dmax",
    ]
);

$router->add(
    "/ket-qua-xoso-max-3d-pro-vietlott-{date}",
    [
        "controller" => "vietlottmaxpro",
        "action" => "index3dmaxpro",
        "date" => 1
    ]
);

$router->add(
    "/ket-qua-xoso-max-3d-pro-vietlott-{date}.html",
    [
        "controller" => "vietlottmaxpro",
        "action" => "index3dmaxpro",
        "date" => 1
    ]
);

$router->add(
    "/ket-qua-xoso-max-3d-pro-vietlott.html",
    [
        "controller" => "vietlottmaxpro",
        "action" => "index3dmaxpro",
    ]
);

$router->add(
    "/quay-thu-xo-so-power-6-55-hom-nay",
    [
        "controller" => "quaythu",
        "action" => "quaythuPower655",
    ]
);

$router->add(
    "/quay-thu-xo-so-power-6-55-hom-nay.html",
    [
        "controller" => "quaythu",
        "action" => "quaythuPower655",
    ]
);
$router->add(
    "/quay-thu-xo-so-mega-6-45-hom-nay",
    [
        "controller" => "quaythu",
        "action" => "quaythuMega645",
    ]
);

$router->add(
    "/quay-thu-xo-so-mega-6-45-hom-nay.html",
    [
        "controller" => "quaythu",
        "action" => "quaythuMega645",
    ]
);
$router->add(
    "/quay-thu-xsmb-quay-thu-xo-so-mien-bac-hom-nay",
    [
        "controller" => "quaythu",
        "action" => "quaythumb",
    ]
);

$router->add(
    "/quay-thu-xsmb.html",
    [
        "controller" => "quaythu",
        "action" => "quaythumb",
    ]
);
$router->add(
    "/quay-thu-xo-so",
    [
        "controller" => "quaythu",
        "action" => "quaythumb",
    ]
);

$router->add(
    "/quay-thu-xo-so.html",
    [
        "controller" => "quaythu",
        "action" => "quaythuxs",
    ]
);
$router->add(
    "/quay-thu-xsmt",
    [
        "controller" => "quaythu",
        "action" => "quaythumt",
    ]
);

$router->add(
    "/quay-thu-xsmt.html",
    [
        "controller" => "quaythu",
        "action" => "quaythumt",
    ]
);
$router->add(
    "/quay-thu-xsmn",
    [
        "controller" => "quaythu",
        "action" => "quaythumn",
    ]
);

$router->add(
    "/quay-thu-xsmn.html",
    [
        "controller" => "quaythu",
        "action" => "quaythumn",
    ]
);

// Quay thử theo tỉnh - XSMN
$router->add('/quay-thu-xsag.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsag'
]);
$router->add('/quay-thu-xsbl.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsbl'
]);
$router->add('/quay-thu-xsbth.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsbth'
]);
$router->add('/quay-thu-xsbt.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsbt'
]);
$router->add('/quay-thu-xsbd.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsbd'
]);
$router->add('/quay-thu-xsbp.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsbp'
]);
$router->add('/quay-thu-xscm.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xscm'
]);
$router->add('/quay-thu-xsct.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsct'
]);
$router->add('/quay-thu-xsdl.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsdl'
]);
$router->add('/quay-thu-xsdn.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsdn'
]);
$router->add('/quay-thu-xsdt.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsdt'
]);
$router->add('/quay-thu-xshg.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xshg'
]);
$router->add('/quay-thu-xshcm.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xshcm'
]);
$router->add('/quay-thu-xskg.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xskg'
]);
$router->add('/quay-thu-xsla.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsla'
]);
$router->add('/quay-thu-xsst.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsst'
]);
$router->add('/quay-thu-xstn.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xstn'
]);
$router->add('/quay-thu-xstg.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xstg'
]);
$router->add('/quay-thu-xstv.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xstv'
]);
$router->add('/quay-thu-xsvl.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsvl'
]);
$router->add('/quay-thu-xsvt.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsvt'
]);

// Quay thử theo tỉnh - XSMT
$router->add('/quay-thu-xsbdinh.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsbdinh'
]);
$router->add('/quay-thu-xsgl.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsgl'
]);
$router->add('/quay-thu-xskh.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xskh'
]);
$router->add('/quay-thu-xsktum.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsktum'
]);
$router->add('/quay-thu-xsnt.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsnt'
]);
$router->add('/quay-thu-xspy.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xspy'
]);
$router->add('/quay-thu-xsqb.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsqb'
]);
$router->add('/quay-thu-xsqn.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsqn'
]);
$router->add('/quay-thu-xsqt.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsqt'
]);
// $router->add('/quay-thu-xsqtri.html', [
//     'controller' => 'quaythu',
//     'action' => 'quaythuProvince',
//     'province' => 'xsqtri'
// ]);
$router->add('/quay-thu-xstth.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xstth'
]);
$router->add('/quay-thu-xsdno.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsdno'
]);
$router->add('/quay-thu-xsqng.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsqng'
]);
$router->add('/quay-thu-xsdng.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsdng'
]);
$router->add('/quay-thu-xsdlk.html', [
    'controller' => 'quaythu',
    'action' => 'quaythuProvince',
    'province' => 'xsdlk'
]);

// $router->add('/du-doan-xsmb', [
//     'controller' => 'dudoan',
//     'action' => 'xsmbdudoan'
// ]);

// $router->add('/du-doan-xsmb-c59.html', [
//     'controller' => 'dudoan',
//     'action' => 'xsmbdudoan'
// ]);
$router->add('/lo-xien-xsmb.html', [
    'controller' => 'thongkemb',
    'action' => 'loXienXsmb'
]);
// $router->add('/du-doan-xsmn', [
//     'controller' => 'dudoan',
//     'action' => 'xsmndudoan'
// ]);

// $router->add('/du-doan-xsmn-c61.html', [
//     'controller' => 'dudoan',
//     'action' => 'xsmndudoan'
// ]);

// Routes for province-specific predictions - unified action
$router->add('/du-doan-{province:[a-z\-]+}-{day:thu-[0-9]|chu-nhat}.html', [
    'controller' => 'dudoan',
    'action' => 'provinceDudoan',
    'province' => 1,
    'day' => 2
]);
// $router->add('/du-doan-xsmt', [
//     'controller' => 'dudoan',
//     'action' => 'xsmtdudoan'
// ]);

// $router->add('/du-doan-xsmt-c60.html', [
//     'controller' => 'dudoan',
//     'action' => 'xsmtdudoan'
// ]);
$router->add('/dudoan-load-more', [
    'controller' => 'dudoan',
    'action' => 'loadMore'
]);

$router->add('/dudoan-load-more.html', [
    'controller' => 'dudoan',
    'action' => 'loadMore'
]);
$router->addPost('/dudoan/loadMoreSoiCau', [
    'controller' => 'dudoan',
    'action' => 'loadMoreSoiCau'
]);

$router->addPost('/dudoan/loadMoreSoiCau.html', [
    'controller' => 'dudoan',
    'action' => 'loadMoreSoiCau'
]);

$router->add(
    '/du-doan-xo-so-soi-cau',
    [
        'controller' => 'dudoan',
        'action' => 'dudoansoicau'
    ]
);

$router->add(
    '/du-doan-xo-so-soi-cau.html',
    [
        'controller' => 'dudoan',
        'action' => 'dudoansoicau'
    ]
);

$router->add(
    "/y-nghia-cac-con-so-tu-00-den-99-trong-lo-de",
    [
        "controller" => "index",
        "action" => "ynghia",
    ]
);

$router->add(
    "/y-nghia-cac-con-so-tu-00-den-99-trong-lo-de.html",
    [
        "controller" => "index",
        "action" => "ynghia",
    ]
);
// $router->add(
//     "/xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb",
//     [
//         "controller" => "ketquaxsmb",
//         "action"     => "listxsmb",
//     ]
// );

// $router->add(
//     "/xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html",
//     [
//         "controller" => "ketquaxsmb",
//         "action"     => "listxsmb",
//     ]
// );
// $router->add(
//     "/xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn",
//     [
//         "controller" => "ketquaxsmn",
//         "action"     => "listxsmn",
//     ]
// );

// $router->add(
//     "/xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html",
//     [
//         "controller" => "ketquaxsmn",
//         "action"     => "listxsmn",
//     ]
// );
// $router->add(
//     "/xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt",
//     [
//         "controller" => "ketquaxsmt",
//         "action"     => "listxsmt",
//     ]
// );

// $router->add(
//     "/xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html",
//     [
//         "controller" => "ketquaxsmt",
//         "action"     => "listxsmt",
//     ]
// );

$router->add(
    "/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb",
    [
        "controller" => "ketquaxsmb",
        "action" => "index",
    ]
);

$router->add(
    "/ket-qua-xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb.html",
    [
        "controller" => "ketquaxsmb",
        "action" => "index",
    ]
);

$router->add(
    "/xo-so-mien-bac-xsmb.html",
    [
        "controller" => "ketquaxsmb",
        "action" => "index",
    ]
);

$router->add(
    "/xo-so-mien-bac-xsmb",
    [
        "controller" => "ketquaxsmb",
        "action" => "index",
    ]
);

// Route for load more AJAX
$router->add(
    '/xo-so-mien-bac-xsmb/page/:params',
    [
        'controller' => 'ketquaxsmb',
        'action' => 'page',
        'pageNumber' => 1,
    ]
);

// Router ngắn: /xsmb-29-12-2025.html
$router->add(
    '/xsmb-([0-9]{1,2})-([0-9]{1,2})-([0-9]{4}).html',
    [
        'controller' => 'ketquaxs',
        'action' => 'byDateShort',
        'd' => 1,  // Day
        'm' => 2,  // Month
        'y' => 3,  // Year
    ]
);

$router->add(
    '/xsmb-([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})',
    [
        'controller' => 'ketquaxs',
        'action' => 'byDateShort',
        'd' => 1,  // Day
        'm' => 2,  // Month
        'y' => 3,  // Year
    ]
);
// $router->add(
//     '/xsmb-(thu-2|thu-3|thu-4|thu-5|thu-6|thu-7|chu-nhat)',
//     [
//         'controller' => 'ketquaxsmb',
//         'action'     => 'weekday',
//         'slug'       => 1, 
//     ]
// );

$router->add(
    '/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn',
    ['controller' => 'ketquaxsmn', 'action' => 'index']
);

$router->add(
    '/ket-qua-xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn.html',
    ['controller' => 'ketquaxsmn', 'action' => 'index']
);

$router->add(
    '/xo-so-mien-nam-xsmn.html',
    ['controller' => 'ketquaxsmn', 'action' => 'index']
);

$router->add(
    '/xo-so-mien-nam-xsmn',
    ['controller' => 'ketquaxsmn', 'action' => 'index']
);

// XSMN theo ngày: xsmn-20-7-ket-qua-xo-so-mien-nam-ngay-20-7-2025.html
$router->add(
    '/xsmn-([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})',
    [
        'controller' => 'ketquaxs',
        'action' => 'byDateShort',
        'd' => 1,  // Day
        'm' => 2,  // Month
        'y' => 3,  // Year
    ]
);

$router->add(
    '/xsmn-([0-9]{1,2})-([0-9]{1,2})-([0-9]{4}).html',
    [
        'controller' => 'ketquaxs',
        'action' => 'byDateShort',
        'd' => 1,  // Day
        'm' => 2,  // Month
        'y' => 3,  // Year
    ]
);

// XSMN theo thứ: xsmn-thu-2, xsmn-thu-3, ..., xsmn-chu-nhat
// $router->add(
//     '/xsmn-(thu-2|thu-3|thu-4|thu-5|thu-6|thu-7|chu-nhat)',
//     ['controller' => 'ketquaxsmn', 'action' => 'weekday', 'slug' => 1]
// );


$router->add(
    '/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt',
    ['controller' => 'ketquaxsmt', 'action' => 'index']
);

$router->add(
    '/ket-qua-xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html',
    ['controller' => 'ketquaxsmt', 'action' => 'index']
);

$router->add(
    '/xo-so-mien-trung-xsmt.html',
    ['controller' => 'ketquaxsmt', 'action' => 'index']
);

$router->add(
    '/xo-so-mien-trung-xsmt',
    ['controller' => 'ketquaxsmt', 'action' => 'index']
);

// XSMT theo ngày: xsmt-20-7-ket-qua-xo-so-mien-trung-ngay-20-7-2025.html
$router->add(
    '/xsmt-([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})',
    [
        'controller' => 'ketquaxs',
        'action' => 'byDateShort',
        'd' => 1,  // Day
        'm' => 2,  // Month
        'y' => 3,  // Year
    ]
);

$router->add(
    '/xsmt-([0-9]{1,2})-([0-9]{1,2})-([0-9]{4}).html',
    [
        'controller' => 'ketquaxs',
        'action' => 'byDateShort',
        'd' => 1,  // Day
        'm' => 2,  // Month
        'y' => 3,  // Year
    ]
);

// XSMT theo thứ: xsmt-thu-2, xsmt-thu-3, ..., xsmt-chu-nhat
// $router->add(
//     '/xsmt-(thu-2|thu-3|thu-4|thu-5|thu-6|thu-7|chu-nhat)',
//     ['controller' => 'ketquaxsmt', 'action' => 'weekday', 'slug' => 1]
// );

// XSMT theo thứ (archive 5 kỳ gần nhất + pagination)
$router->add(
    '/xsmt-(thu-2|thu-3|thu-4|thu-5|thu-6|thu-7|chu-nhat)-ket-qua-xo-so-mien-trung(?:\.html)?',
    [
        'controller' => 'archive',
        'action' => 'regionweekday',
        'slug' => 1,
        'region' => 'XSMT',
    ]
);
// XSMN
$router->add(
    '/xsmn-(thu-2|thu-3|thu-4|thu-5|thu-6|thu-7|chu-nhat)-ket-qua-xo-so-mien-nam(?:\.html)?',
    [
        'controller' => 'archive',
        'action' => 'regionWeekday',
        'slug' => 1,
        'region' => 'XSMN',
    ]
);
$router->add(
    '/xsmb-(thu-2|thu-3|thu-4|thu-5|thu-6|thu-7|chu-nhat)-ket-qua-xo-so-mien-bac(?:\.html)?',
    [
        'controller' => 'archive',
        'action' => 'regionWeekday',
        'slug' => 1,
        'region' => 'XSMB',
    ]
);

// // Tỉnh (archive 5 kỳ gần nhất + pagination).
// // Ví dụ: /ket-qua-xo-so-binh-dinh-xsbdinh
// $router->add(
//     '/ket-qua-xo-so-([a-z0-9-]+)-([a-z0-9-]+)',
//     [
//         'controller' => 'archive',
//         'action'     => 'province',
//         'nameSlug'   => 1,   // binh-dinh
//         'alias'      => 2,   // xsbdinh (chỉ để "đẹp" URL, không dùng lookup)
//     ]
// );

// $router->add(
//     '/ket-qua-xo-so-([a-z0-9-]+)-([a-z0-9-]+).html',
//     [
//         'controller' => 'archive',
//         'action'     => 'province',
//         'nameSlug'   => 1,   // binh-dinh
//         'alias'      => 2,   // xsbdinh (chỉ để "đẹp" URL, không dùng lookup)
//     ]
// );


$router->add(
    '/xo-so-([a-z0-9-]+)-([a-z0-9-]+)',
    [
        'controller' => 'archive',
        'action' => 'province',
        'nameSlug' => 1,   // binh-dinh
        'alias' => 2,   // xsbdinh (chỉ để "đẹp" URL, không dùng lookup)
    ]
);

$router->add(
    '/xo-so-([a-z0-9-]+)-([a-z0-9-]+).html',
    [
        'controller' => 'archive',
        'action' => 'province',
        'nameSlug' => 1,   // binh-dinh
        'alias' => 2,   // xsbdinh (chỉ để "đẹp" URL, không dùng lookup)
    ]
);

$router->add(
    '/bach-thu-de-xsmb-hom-nay.html',
    [
        'controller' => 'soicau',
        'action' => 'bachThuDe',
    ]
);

$router->add(
    '/soi-cau-wap-du-doan-xsmb-wap-hom-nay-chinh-xac-nhat.html',
    [
        'controller' => 'soicau',
        'action' => 'soiCauWap',
    ]
);

$router->add(
    '/soi-cau-3-mien-du-doan-xo-so-3-mien.html',
    [
        'controller' => 'soicau',
        'action' => 'soiCau3Mien',
    ]
);

$router->add(
    '/soi-cau-du-doan-xsmb-chinh-xac-100.html',
    [
        'controller' => 'soicau',
        'action' => 'soiCauDuDoan',
    ]
);


$router->add(
    "/truc-tiep-ket-qua-xo-so-mien-nam-ttxsmn-xsmn",
    [
        "controller" => "lottery",
        "action" => "live",
    ]
);

$router->add(
    "/truc-tiep-ket-qua-xo-so-mien-nam-ttxsmn-xsmn.html",
    [
        "controller" => "lottery",
        "action" => "live",
    ]
);
$router->add(
    "/truc-tiep-ket-qua-xo-so-mien-bac-ttxsmb-xsmb",
    [
        "controller" => "lottery",
        "action" => "livemb",
    ]
);

$router->add(
    "/truc-tiep-ket-qua-xo-so-mien-bac-ttxsmb-xsmb.html",
    [
        "controller" => "lottery",
        "action" => "livemb",
    ]
);
$router->add(
    "/truc-tiep-ket-qua-xo-so-mien-trung-ttxsmt-xsmt",
    [
        "controller" => "lottery",
        "action" => "livemt",
    ]
);

$router->add(
    "/truc-tiep-ket-qua-xo-so-mien-trung-ttxsmt-xsmt.html",
    [
        "controller" => "lottery",
        "action" => "livemt",
    ]
);

// ===== LOTTERY LIVE API ROUTES =====
// CSRF token endpoint
$router->addPost(
    "/ws-auth/csrf",
    [
        "controller" => "lottery",
        "action" => "csrf",
    ]
);

$router->addPost(
    "/ws-auth/csrf.html",
    [
        "controller" => "lottery",
        "action" => "csrf",
    ]
);

// CSRF token endpoint for JavaScript files
$router->addPost(
    "/lottery/csrf",
    [
        "controller" => "lottery",
        "action" => "csrf",
    ]
);

$router->addPost(
    "/lottery/csrf.html",
    [
        "controller" => "lottery",
        "action" => "csrf",
    ]
);

// Thêm GET route để tránh 404 (optional)
$router->addGet(
    "/lottery/csrf.html",
    [
        "controller" => "lottery",
        "action" => "csrf",
    ]
);

// Generate token endpoint
$router->addPost(
    "/ws-auth/generateToken",
    [
        "controller" => "lottery",
        "action" => "generateToken",
    ]
);

$router->addPost(
    "/ws-auth/generateToken.html",
    [
        "controller" => "lottery",
        "action" => "generateToken",
    ]
);

// Proxy generate token endpoint
$router->addPost(
    "/lottery/proxyGenerateToken",
    [
        "controller" => "lottery",
        "action" => "generateToken",
    ]
);

$router->addPost(
    "/lottery/proxyGenerateToken.html",
    [
        "controller" => "lottery",
        "action" => "generateToken",
    ]
);



$router->add(
    "/rong-bach-kim.html",
    [
        "controller" => "soicau",
        "action" => "rongBachKim",
    ]
);


$router->add(
    "/soi-cau-24h-du-doan-xo-so-24h-mien-bac.html",
    [
        "controller" => "soicau",
        "action" => "soiCau24h",
    ]
);

$router->add(
    "/soi-cau-mien-phi-888.html",
    [
        "controller" => "soicau",
        "action" => "soiCau888",
    ]
);

// $router->add(
//     "/soi-cau-366-mb.html",
//     [
//         "controller" => "Soicau",
//         "action" => "soiCau366",
//     ]
// );

$router->add(
    "/soi-cau-3-cang-mien-bac.html",
    [
        "controller" => "Soicau",
        "action" => "soiCau3Cang",
    ]
);

$router->add(
    "/soi-cau-xsmb-vip-hom-nay.html",
    [
        "controller" => "SoiCauVip",
        "action" => "index",
    ]
);

$router->add(
    "/soi-cau-xo-so.html",
    [
        "controller" => "SoiCauVip",
        "action" => "soiCauXoSo",
    ]
);

$router->add(
    "/soi-cau-xsmb-win2888-asia.html",
    [
        "controller" => "Soicau",
        "action" => "soiCauWin2888",
    ]
);



$router->add(
    "/thong-ke-xo-so-power-6-55",
    [
        "controller" => "Statistics665",
        "action" => "statisticspower655",
        "date" => 1
    ]
);

$router->add(
    "/thong-ke-xo-so-power-6-55.html",
    [
        "controller" => "Statistics665",
        "action" => "statisticspower655",
        "date" => 1
    ]
);
$router->add(
    "/thong-ke-xo-so-mega-6-45",
    [
        "controller" => "Statistics645",
        "action" => "statisticspower645",
        "date" => 1
    ]
);

$router->add(
    "/thong-ke-xo-so-mega-6-45.html",
    [
        "controller" => "Statistics645",
        "action" => "statisticspower645",
        "date" => 1
    ]
);
$router->add(
    "/thong-ke-xo-so-mien-bac-tk-xsmb",
    [
        "controller" => "Thongkemb",
        "action" => "thongkexsmb",
    ]
);

$router->add(
    "/thong-ke-xo-so-mien-bac-tk-xsmb.html",
    [
        "controller" => "Thongkemb",
        "action" => "thongkexsmb",
    ]
);
$router->add(
    "/lo-gan-xsmb",
    [
        "controller" => "Thongkemb",
        "action" => "loganxsmb",
    ]
);

$router->add(
    "/lo-gan-xsmb.html",
    [
        "controller" => "Thongkemb",
        "action" => "loganxsmb",
    ]
);
$router->add(
    "/dac-biet-xsmb",
    [
        "controller" => "Thongkemb",
        "action" => "dacbietxsmb",
    ]
);

$router->add(
    "/dac-biet-xsmb.html",
    [
        "controller" => "Thongkemb",
        "action" => "dacbietxsmb",
    ]
);

$router->add(
    "/dac-biet-tuan-xsmb.html",
    [
        "controller" => "Thongkemb",
        "action" => "dacBietXsmbTuan",
    ]
);

$router->add(
    "/dac-biet-thang-xsmb.html",
    [
        "controller" => "Thongkemb",
        "action" => "dacBietXsmbThang",
    ]
);


$router->add(
    "/dau-duoi-xsmb",
    [
        "controller" => "Thongkemb",
        "action" => "dauduoixsmb",
    ]
);

$router->add(
    "/dau-duoi-xsmb.html",
    [
        "controller" => "Thongkemb",
        "action" => "dauduoixsmb",
    ]
);
$router->add(
    "/thong-ke-tan-suat-mien-bac",
    [
        "controller" => "Thongkemb",
        "action" => "tansuatxsmb",
    ]
);

$router->add(
    "/thong-ke-tan-suat-mien-bac.html",
    [
        "controller" => "Thongkemb",
        "action" => "tansuatxsmb",
    ]
);

$router->add(
    "/tan-suat-lo-to-xsmb.html",
    [
        "controller" => "Thongkemb",
        "action" => "tanSuatLoToXsmb",
    ]
);

$router->add(
    "/lo-kep-xsmb.html",
    [
        "controller" => "Thongkemb",
        "action" => "loKepXsmb",
    ]
);

$router->add(
    "/thong-ke-theo-tong-xsmb.html",
    [
        "controller" => "Thongkemb",
        "action" => "thongKeTheoTongXsmb",
    ]
);


// Routes for province-specific statistics (slug-based URLs)
// Đặt sau route cụ thể để tránh conflict

// Routes for Miền Nam provinces
$router->add('/thong-ke-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}', [
    'controller' => 'Thongkemn',
    'action' => 'thongKeXsmn',
    'slug' => 1
]);
$router->add('/thong-ke-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}.html', [
    'controller' => 'Thongkemn',
    'action' => 'thongKeXsmn',
    'slug' => 1
]);

$router->add('/thong-ke-lo-gan-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}', [
    'controller' => 'Thongkemn',
    'action' => 'loGanXsmn',
    'slug' => 1
]);
$router->add('/thong-ke-lo-gan-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}.html', [
    'controller' => 'Thongkemn',
    'action' => 'loGanXsmn',
    'slug' => 1
]);

$router->add('/lo-gan-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}', [
    'controller' => 'Thongkemn',
    'action' => 'loGanXsmn',
    'slug' => 1
]);
$router->add('/lo-gan-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}.html', [
    'controller' => 'Thongkemn',
    'action' => 'loGanXsmn',
    'slug' => 1
]);

$router->add('/thong-ke-dac-biet-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}', [
    'controller' => 'Thongkemn',
    'action' => 'dacBietXsmn',
    'slug' => 1
]);
$router->add('/thong-ke-dac-biet-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}.html', [
    'controller' => 'Thongkemn',
    'action' => 'dacBietXsmn',
    'slug' => 1
]);

$router->add('/thong-ke-dau-duoi-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}', [
    'controller' => 'Thongkemn',
    'action' => 'dauDuoiXsmn',
    'slug' => 1
]);
$router->add('/thong-ke-dau-duoi-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}.html', [
    'controller' => 'Thongkemn',
    'action' => 'dauDuoiXsmn',
    'slug' => 1
]);

$router->add('/thong-ke-tan-suat-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}', [
    'controller' => 'Thongkemn',
    'action' => 'tanSuatXsmn',
    'slug' => 1
]);
$router->add('/thong-ke-tan-suat-{slug:(xshcm|xsvt|xsla|xsbd|xstv|xsdl|xsbp|xshg|xskg|xstg|xstn|xsdt|xsag|xsbt|xsbth|xsdn|xsbl|xsct|xsst|xscm)}.html', [
    'controller' => 'Thongkemn',
    'action' => 'tanSuatXsmn',
    'slug' => 1
]);

// Routes for Miền Trung provinces
$router->add('/thong-ke-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}', [
    'controller' => 'Thongkemt',
    'action' => 'thongKeXsmt',
    'slug' => 1
]);
$router->add('/thong-ke-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}.html', [
    'controller' => 'Thongkemt',
    'action' => 'thongKeXsmt',
    'slug' => 1
]);

$router->add('/thong-ke-lo-gan-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}', [
    'controller' => 'Thongkemt',
    'action' => 'loganxsmt',
    'slug' => 1
]);
$router->add('/thong-ke-lo-gan-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}.html', [
    'controller' => 'Thongkemt',
    'action' => 'loganxsmt',
    'slug' => 1
]);

$router->add('/lo-gan-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}', [
    'controller' => 'Thongkemt',
    'action' => 'loganxsmt',
    'slug' => 1
]);
$router->add('/lo-gan-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}.html', [
    'controller' => 'Thongkemt',
    'action' => 'loganxsmt',
    'slug' => 1
]);

$router->add('/thong-ke-dac-biet-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}', [
    'controller' => 'Thongkemt',
    'action' => 'dacBietXsmt',
    'slug' => 1
]);
$router->add('/thong-ke-dac-biet-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}.html', [
    'controller' => 'Thongkemt',
    'action' => 'dacBietXsmt',
    'slug' => 1
]);

$router->add('/thong-ke-dau-duoi-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}', [
    'controller' => 'Thongkemt',
    'action' => 'dauDuoiXsmt',
    'slug' => 1
]);
$router->add('/thong-ke-dau-duoi-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}.html', [
    'controller' => 'Thongkemt',
    'action' => 'dauDuoiXsmt',
    'slug' => 1
]);

$router->add('/thong-ke-tan-suat-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}', [
    'controller' => 'Thongkemt',
    'action' => 'tanSuatXsmt',
    'slug' => 1
]);
$router->add('/thong-ke-tan-suat-{slug:(xsdng|xsbdinh|xstth|xsdlk|xsqng|xsqt|xsqb|xsqn|xsdno|xsgl|xsnt|xspy|xskh|xs-kontum)}.html', [
    'controller' => 'Thongkemt',
    'action' => 'tanSuatXsmt',
    'slug' => 1
]);

$router->add(
    "/thong-ke-xo-so-mien-nam-tk-xsmn",
    [
        "controller" => "Thongkemn",
        "action" => "thongkexsmn",
    ]
);

$router->add(
    "/thong-ke-xo-so-mien-nam-tk-xsmn.html",
    [
        "controller" => "Thongkemn",
        "action" => "thongkexsmn",
    ]
);
$router->add(
    "/thong-ke-lo-gan-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn",
    [
        "controller" => "Thongkemn",
        "action" => "loganxsmn",
    ]
);

$router->add(
    "/thong-ke-lo-gan-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html",
    [
        "controller" => "Thongkemn",
        "action" => "loganxsmn",
    ]
);
$router->add(
    "/thong-ke-dac-biet-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn",
    [
        "controller" => "Thongkemn",
        "action" => "dacbietxsmn",
    ]
);

$router->add(
    "/thong-ke-dac-biet-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html",
    [
        "controller" => "Thongkemn",
        "action" => "dacbietxsmn",
    ]
);
$router->add(
    "/thong-ke-dau-duoi-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn",
    [
        "controller" => "Thongkemn",
        "action" => "dauduoixsmn",
    ]
);

$router->add(
    "/thong-ke-dau-duoi-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html",
    [
        "controller" => "Thongkemn",
        "action" => "dauduoixsmn",
    ]
);
$router->add(
    "/thong-ke-tan-suat-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn",
    [
        "controller" => "Thongkemn",
        "action" => "tansuatxsmn",
    ]
);

$router->add(
    "/thong-ke-tan-suat-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html",
    [
        "controller" => "Thongkemn",
        "action" => "tansuatxsmn",
    ]
);

$router->add(
    "/thong-ke-xo-so-mien-trung-tk-xsmt",
    [
        "controller" => "Thongkemt",
        "action" => "thongkexsmt",
    ]
);

$router->add(
    "/thong-ke-xo-so-mien-trung-tk-xsmt.html",
    [
        "controller" => "Thongkemt",
        "action" => "thongkexsmt",
    ]
);
$router->add(
    "/thong-ke-lo-gan-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt",
    [
        "controller" => "Thongkemt",
        "action" => "loganxsmt",
    ]
);

$router->add(
    "/thong-ke-lo-gan-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html",
    [
        "controller" => "Thongkemt",
        "action" => "loganxsmt",
    ]
);
$router->add(
    "/thong-ke-dac-biet-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt",
    [
        "controller" => "Thongkemt",
        "action" => "dacbietxsmt",
    ]
);

$router->add(
    "/thong-ke-dac-biet-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html",
    [
        "controller" => "Thongkemt",
        "action" => "dacbietxsmt",
    ]
);
$router->add(
    "/thong-ke-dau-duoi-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt",
    [
        "controller" => "Thongkemt",
        "action" => "dauduoixsmt",
    ]
);

$router->add(
    "/thong-ke-dau-duoi-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html",
    [
        "controller" => "Thongkemt",
        "action" => "dauduoixsmt",
    ]
);
$router->add(
    "/thong-ke-tan-suat-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt",
    [
        "controller" => "Thongkemt",
        "action" => "tansuatxsmt",
    ]
);

$router->add(
    "/thong-ke-tan-suat-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html",
    [
        "controller" => "Thongkemt",
        "action" => "tansuatxsmt",
    ]
);






$router->addPost(
    "/get-token",
    [
        "controller" => "lottery",
        "action" => "generateToken",
    ]
);

$router->addPost(
    "/get-token.html",
    [
        "controller" => "lottery",
        "action" => "generateToken",
    ]
);

$router->addPost('/set-cookie', [
    'controller' => 'index',
    'action' => 'setCookie'
]);
$router->addPost('/set-cookie.html', [
    'controller' => 'index',
    'action' => 'setCookie'
]);


$router->addGet('/get-token-value', [
    'controller' => 'index',
    'action' => 'getTokenValue'
]);
$router->addGet('/get-token-value.html', [
    'controller' => 'index',
    'action' => 'getTokenValue'
]);

// ===== SEO ROUTES =====
// Thêm route cho trang chủ
$router->add('/', [
    'controller' => 'index',
    'action' => 'index'
]);

// Thêm route cho sitemap
$router->add('/sitemap.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'index'
]);

// Thêm route cho sitemap index (beautiful display)
$router->add('/sitemap-index', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'sitemapIndex'
]);

// Thêm route cho các sitemap con
$router->add('/sitemap-ket-qua-xo-so.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'ketQuaXoSo'
]);

$router->add('/sitemap-du-doan-xo-so.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'duDoanXoSo'
]);

$router->add('/sitemap-vietlott.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'vietlott'
]);

$router->add('/sitemap-quay-thu-xo-so.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'quayThuXoSo'
]);

$router->add('/sitemap-thong-ke-ket-qua-xo-so.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'thongKeKetQuaXoSo'
]);

$router->add('/tin-tuc.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'news'
]);

// Monthly sitemap routes
$router->add('/sitemap-du-doan-ngay-{year:[0-9]{4}}-{month:[0-9]{2}}.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'monthlyDuDoan',
    'year' => 1,
    'month' => 2
]);

$router->add('/sitemap-kqxs-ngay-{year:[0-9]{4}}-{month:[0-9]{2}}.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'monthlyKetQua',
    'year' => 1,
    'month' => 2
]);

// Route for date-specific kqxs results (calendar functionality)
$router->add('/kqxs-ngay-{day:[0-9]{2}}-{month:[0-9]{2}}-{year:[0-9]{4}}.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'Kqxsdate',
    'action' => 'index',
    'day' => 1,
    'month' => 2,
    'year' => 3
]);

$router->add('/sitemap-vietlott-ngay-{year:[0-9]{4}}-{month:[0-9]{2}}.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'monthlyVietlott',
    'year' => 1,
    'month' => 2
]);

// Thêm route cho sitemap hình ảnh
$router->add('/sitemap-images.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'images'
]);

// Thêm route cho sitemap tin tức
$router->add('/sitemap-news.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'news'
]);

// Thêm route cho robots.txt
$router->add('/robots.txt', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'robots'
]);
$router->add('/sitemap-dudoan-ket-qua-xo-so.xml', [
    'namespace' => 'App\Controllers',
    'controller' => 'Sitemap',
    'action' => 'predictions'
]);

// ===== VIETLOTT LIVE ROUTES =====
// Live pages
$router->add(
    "/truc-tiep-ket-qua-xo-so-mega-6-45-vietlott",
    [
        "controller" => "VietlottLive",
        "action" => "mega645",
    ]
);

$router->add(
    "/truc-tiep-ket-qua-xo-so-mega-6-45-vietlott.html",
    [
        "controller" => "VietlottLive",
        "action" => "mega645",
    ]
);
$router->add(
    "/truc-tiep-ket-qua-xo-so-power-6-55-vietlott",
    [
        "controller" => "VietlottLive",
        "action" => "power655",
    ]
);

$router->add(
    "/truc-tiep-ket-qua-xo-so-power-6-55-vietlott.html",
    [
        "controller" => "VietlottLive",
        "action" => "power655",
    ]
);
$router->add(
    "/truc-tiep-ket-qua-xo-so-max-3d-vietlott",
    [
        "controller" => "VietlottLive",
        "action" => "max3d",
    ]
);

$router->add(
    "/truc-tiep-ket-qua-xo-so-max-3d-vietlott.html",
    [
        "controller" => "VietlottLive",
        "action" => "max3d",
    ]
);
$router->add(
    "/truc-tiep-ket-qua-xo-so-max-3d-pro-vietlott",
    [
        "controller" => "VietlottLive",
        "action" => "max3dpro",
    ]
);

$router->add(
    "/truc-tiep-ket-qua-xo-so-max-3d-pro-vietlott.html",
    [
        "controller" => "VietlottLive",
        "action" => "max3dpro",
    ]
);

// API endpoints
$router->add(
    "/vietlott-live/check-mega645",
    [
        "controller" => "VietlottLive",
        "action" => "checkMega645",
    ]
);
$router->add(
    "/vietlott-live/check-mega645.html",
    [
        "controller" => "VietlottLive",
        "action" => "checkMega645",
    ]
);
$router->add(
    "/vietlott-live/check-power655",
    [
        "controller" => "VietlottLive",
        "action" => "checkPower655",
    ]
);
$router->add(
    "/vietlott-live/check-power655.html",
    [
        "controller" => "VietlottLive",
        "action" => "checkPower655",
    ]
);
$router->add(
    "/vietlott-live/check-max3d",
    [
        "controller" => "VietlottLive",
        "action" => "checkMax3d",
    ]
);
$router->add(
    "/vietlott-live/check-max3d.html",
    [
        "controller" => "VietlottLive",
        "action" => "checkMax3d",
    ]
);
$router->add(
    "/vietlott-live/check-max3dpro",
    [
        "controller" => "VietlottLive",
        "action" => "checkMax3dpro",
    ]
);
$router->add(
    "/vietlott-live/check-max3dpro.html",
    [
        "controller" => "VietlottLive",
        "action" => "checkMax3dpro",
    ]
);
$router->add(
    "/vietlott-live/get-api-keys",
    [
        "controller" => "VietlottLive",
        "action" => "getApiKeys",
    ]
);
$router->add(
    "/vietlott-live/get-api-keys.html",
    [
        "controller" => "VietlottLive",
        "action" => "getApiKeys",
    ]
);

// ===== ABOUT ROUTES =====
// About page
$router->add('/gioi-thieu', [
    'controller' => 'About',
    'action' => 'index'
]);

$router->add('/gioi-thieu.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'About',
    'action' => 'index'
]);

// Terms of service page
$router->add('/dieu-khoan-su-dung', [
    'controller' => 'About',
    'action' => 'terms'
]);

$router->add('/dieu-khoan-su-dung.html', [
    'controller' => 'About',
    'action' => 'terms'
]);

// Contact page
$router->add('/lien-he', [
    'controller' => 'About',
    'action' => 'contact'
]);

$router->add('/lien-he.html', [
    'controller' => 'About',
    'action' => 'contact'
]);

// Privacy policy page
$router->add('/chinh-sach-bao-mat', [
    'controller' => 'About',
    'action' => 'privacy'
]);

$router->add('/chinh-sach-bao-mat.html', [
    'controller' => 'About',
    'action' => 'privacy'
]);

// ===== ERROR HANDLING =====
// Error routes
$router->add('/errors/notFound', [
    'namespace' => 'App\Controllers',
    'controller' => 'Errors',
    'action' => 'notFound'
]);

$router->add('/errors/notFound.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'Errors',
    'action' => 'notFound'
]);

$router->add('/errors/serverError', [
    'namespace' => 'App\Controllers',
    'controller' => 'Errors',
    'action' => 'serverError'
]);

$router->add('/errors/serverError.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'Errors',
    'action' => 'serverError'
]);

$router->add('/errors/unauthorized', [
    'namespace' => 'App\Controllers',
    'controller' => 'Errors',
    'action' => 'unauthorized'
]);

$router->add('/errors/unauthorized.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'Errors',
    'action' => 'unauthorized'
]);

$router->add('/errors/forbidden', [
    'namespace' => 'App\Controllers',
    'controller' => 'Errors',
    'action' => 'forbidden'
]);

$router->add('/errors/forbidden.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'Errors',
    'action' => 'forbidden'
]);

// ===== REDIS API =====
$router->add('/api/redis/clear-cache', [
    'namespace' => 'App\Controllers',
    'controller' => 'Redis',
    'action' => 'clearCache'
]);
$router->add('/api/redis/clear-cache.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'Redis',
    'action' => 'clearCache'
]);
$router->add('/api/redis/clear-cache-pattern', [
    'namespace' => 'App\Controllers',
    'controller' => 'Redis',
    'action' => 'clearCacheByPattern'
]);
$router->add('/api/redis/clear-cache-pattern.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'Redis',
    'action' => 'clearCacheByPattern'
]);
$router->add('/api/redis/clear-mega645-cache', [
    'namespace' => 'App\Controllers',
    'controller' => 'Redis',
    'action' => 'clearMega645Cache'
]);
$router->add('/api/redis/clear-mega645-cache.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'Redis',
    'action' => 'clearMega645Cache'
]);
$router->add('/api/redis/info', [
    'namespace' => 'App\Controllers',
    'controller' => 'Redis',
    'action' => 'info'
]);
$router->add('/api/redis/info.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'Redis',
    'action' => 'info'
]);



// Thêm route mặc định để xử lý 404
$router->notFound([
    'namespace' => 'App\Controllers',
    'controller' => 'Errors',
    'action' => 'notFound'
]);



// Admin Authentication Routes
$router->add('/admin/login.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'AdminAuth',
    'action' => 'login'
]);
$router->add('/admin/login', [
    'namespace' => 'App\Controllers',
    'controller' => 'AdminAuth',
    'action' => 'login'
]);

$router->add('/admin/logout.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'AdminAuth',
    'action' => 'logout'
]);

$router->add('/admin/check.html', [
    'namespace' => 'App\Controllers',
    'controller' => 'AdminAuth',
    'action' => 'check'
]);

// Admin SEO Fields Routes
$router->add('/admin/seo-fields', [
    'namespace' => 'App\Controllers',
    'controller' => 'AdminSeoFields',
    'action' => 'index'
]);


$router->add('/admin/seo-fields/edit/{pageType:[a-z_]+}/{pageSubtype:[a-z0-9_]+}', [
    'namespace' => 'App\Controllers',
    'controller' => 'AdminSeoFields',
    'action' => 'edit',
    'pageType' => 1,
    'pageSubtype' => 2
]);

$router->add('/admin/seo-fields/preview', [
    'namespace' => 'App\Controllers',
    'controller' => 'AdminSeoFields',
    'action' => 'preview'
]);

$router->add('/admin/seo-fields/init', [
    'namespace' => 'App\Controllers',
    'controller' => 'AdminSeoFields',
    'action' => 'init'
]);

$router->add('/admin/seo-fields/preview', [
    'namespace' => 'App\Controllers',
    'controller' => 'AdminSeoFields',
    'action' => 'preview'
]);


// Thêm route mới cho URL chỉ với slug - chỉ match các slug prediction articles
// Must be BEFORE province routes to avoid conflicts
// Exclude category URLs (-c59, -c61, -c60)
$router->add(
    '/du-doan-{slug:[a-z0-9\-]+}',
    [
        'controller' => 'dudoan',
        'action' => 'detailBySlug'
    ]
);

$router->add(
    '/du-doan-{slug:[a-z0-9\-]+}.html',
    [
        'controller' => 'dudoan',
        'action' => 'detailBySlug'
    ]
);

// Routes for short province format (new format) - must be AFTER slug routes
$router->add('/du-doan-{province:[a-z0-9]+}.html', [
    'controller' => 'dudoan',
    'action' => 'provinceDudoan',
    'province' => 1
]);

// Router cho dự đoán số đề với ngày cụ thể - PHẢI ĐẶT TRƯỚC route slug để tránh conflict
$router->add(
    "/du-doan-so-de-hom-nay-{date:[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}}.html",
    [
        "controller" => "Soicau",
        "action" => "duDoanSoDeByDate",
        "date" => 1
    ]
);

// ===== SOI CẦU ROUTES =====
// Soi cầu bạch thủ XSMB
$router->add(
    '/soi-cau-bach-thu',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'index'
    ]
);

$router->add(
    '/soi-cau-bach-thu.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'index'
    ]
);

// API endpoint for AJAX requests
$router->addPost(
    '/soicau/getData',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'getData'
    ]
);

$router->addPost(
    '/soicau/getData.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'getData'
    ]
);

// Soi cầu nhiều nháy XSMB
$router->add(
    '/soi-cau-nhieu-nhay',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'nhieuNhay'
    ]
);

$router->add(
    '/soi-cau-nhieu-nhay.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'nhieuNhay'
    ]
);

// Soi cầu lật liên tục XSMB
$router->add(
    '/soi-cau-lat-lien-tuc',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'latLienTuc'
    ]
);

$router->add(
    '/soi-cau-lat-lien-tuc.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'latLienTuc'
    ]
);

// Soi cầu Miền Nam
$router->add(
    '/soi-cau-mien-nam',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'mienNam'
    ]
);

$router->add(
    '/soi-cau-mien-nam.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'mienNam'
    ]
);

// Soi cầu Miền Trung
$router->add(
    '/soi-cau-mien-trung',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'mienTrung'
    ]
);

$router->add(
    '/soi-cau-mien-trung.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'soicau',
        'action' => 'mienTrung'
    ]
);

$router->add(
    '/du-doan-xsmn-wap.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'dudoan',
        'action' => 'duDoanXsmnWap'
    ]
)->setName('du_doan_xsmn_wap');

$router->add(
    '/tao-dan-de.html',
    [
        'namespace' => 'App\Controllers',
        'controller' => 'dande',
        'action' => 'taoDanDe'
    ]
)->setName('tao_dan_de');

// ===== URL NGẮN CHO XSMB / XSMN / XSMT - Cập nhật namespace để chắc chắn match =====
$router->add('/xo-so-mien-nam-xsmn.html',   ['namespace' => 'App\Controllers', 'controller' => 'ketquaxsmn', 'action' => 'shortXsmn']);
$router->add('/xo-so-mien-nam-xsmn',         ['namespace' => 'App\Controllers', 'controller' => 'ketquaxsmn', 'action' => 'shortXsmn']);
$router->add('/xo-so-mien-bac-xsmb.html',   ['namespace' => 'App\Controllers', 'controller' => 'ketquaxsmb', 'action' => 'shortXsmb']);
$router->add('/xo-so-mien-bac-xsmb',         ['namespace' => 'App\Controllers', 'controller' => 'ketquaxsmb', 'action' => 'shortXsmb']);
$router->add('/xo-so-mien-trung-xsmt.html', ['namespace' => 'App\Controllers', 'controller' => 'ketquaxsmt', 'action' => 'shortXsmt']);
$router->add('/xo-so-mien-trung-xsmt',       ['namespace' => 'App\Controllers', 'controller' => 'ketquaxsmt', 'action' => 'shortXsmt']);
