<?php

namespace App\Library;

class SchemaHelper
{
    private static $baseUrl = '';
    private static $siteName = 'Soi Cầu 247 - Dự đoán xổ số 3 miền - Kết quả xổ số hôm nay';
    private static $siteDescription = 'Soi Cầu 247 cung cấp dự đoán xổ số 3 miền, thống kê lô gan, giải đặc biệt chính xác nhất. Cập nhật kết quả xổ số nhanh chóng hàng ngày.';
    
    /**
     * Get base URL dynamically
     */
    private static function getBaseUrl()
    {
        if (!empty(self::$baseUrl)) {
            return self::$baseUrl;
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = (($_SERVER['HTTPS'] ?? 'off') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'http') === 'https') ? 'https' : 'http';
            self::$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        } else {
            self::$baseUrl = 'https://soicau247.com';
        }

        return self::$baseUrl;
    }
    
    /**
     * Generate Website Schema
     */
    public static function getWebsiteSchema()
    {
        return [
            "@context" => "https://schema.org",
            "@type" => "WebSite",
            "name" => self::$siteName,
            "alternateName" => "Soi Cầu 247",
            "url" => self::getBaseUrl(),
            "description" => self::$siteDescription,
            "inLanguage" => "vi",
            "publisher" => self::getOrganizationSchema(),
            "creator" => [
                "@type" => "Organization",
                "name" => "Soi Cầu 247",
                "url" => self::getBaseUrl()
            ],
            "license" => "https://creativecommons.org/licenses/by-nc-sa/4.0/",
            "potentialAction" => [
                "@type" => "SearchAction",
                "target" => [
                    "@type" => "EntryPoint",
                    "urlTemplate" => self::$baseUrl . "/search?q={search_term_string}"
                ],
                "query-input" => "required name=search_term_string"
            ],
            // "mainEntity" => [
            //     "@type" => "ItemList",
            //     "name" => "Kết quả xổ số 3 miền",
            //     "itemListElement" => [
            //         [
            //             "@type" => "ListItem",
            //             "position" => 1,
            //             "name" => "Xổ số Miền Bắc",
            //             "url" => self::$baseUrl . "/xsmb-xo-so-kien-thiet-mien-bac-sxmb-kqxsmb"
            //         ],
            //         [
            //             "@type" => "ListItem",
            //             "position" => 2,
            //             "name" => "Xổ số Miền Nam",
            //             "url" => self::$baseUrl . "/xsmn-xo-so-kien-thiet-mien-nam-sxmn-kqxsmn"
            //         ],
            //         [
            //             "@type" => "ListItem",
            //             "position" => 3,
            //             "name" => "Xổ số Miền Trung",
            //             "url" => self::$baseUrl . "/xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt"
            //         ]
            //     ]
            // ]
        ];
    }
    
    /**
     * Generate Organization Schema
     */
    public static function getOrganizationSchema()
    {
        return [
            "@type" => "Organization",
            "name" => "Soi Cầu 247",
            "alternateName" => "Soi Cầu 247",
            "url" => self::$baseUrl,
            "logo" => [
                "@type" => "ImageObject",
                "url" => self::getBaseUrl() . "/media/website/logo.png",
                "width" => 200,
                "height" => 60
            ],
            "description" => self::$siteDescription,
            "foundingDate" => "2020-01-01",
            "knowsLanguage" => "vi",
            "areaServed" => [
                "@type" => "Country",
                "name" => "Vietnam"
            ],
            "sameAs" => [
            ],
            "contactPoint" => [
                "@type" => "ContactPoint",
                "contactType" => "customer service",
                "availableLanguage" => "Vietnamese",
                "areaServed" => "Vietnam"
            ]
        ];
    }
    
    /**
     * Generate WebPage Schema
     */
    public static function getWebPageSchema($pageType, $title, $description, $url = null, $breadcrumbs = [])
    {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "WebPage",
            "name" => $title,
            "description" => $description,
            "url" => $url ?: self::$baseUrl,
            "inLanguage" => "vi",
            "isPartOf" => [
                "@type" => "WebSite",
                "name" => self::$siteName,
                "url" => self::getBaseUrl()
            ],
            "publisher" => self::getOrganizationSchema(),
            "creator" => [
                "@type" => "Organization",
                "name" => "Soi Cầu 247",
                "url" => self::getBaseUrl()
            ],
            "license" => "https://creativecommons.org/licenses/by-nc-sa/4.0/",
            "dateModified" => date('c'),
            "datePublished" => date('c')
        ];
        
        // Add breadcrumbs if provided
        if (!empty($breadcrumbs)) {
            $schema["breadcrumb"] = [
                "@type" => "BreadcrumbList",
                "itemListElement" => array_map(function($item, $index) use ($breadcrumbs) {
                    $itemData = [
                        "@type" => "ListItem",
                        "position" => $index + 1,
                        "name" => $item['name']
                    ];
                    
                    // Nếu có URL, thêm item với @type và @id
                    if (!empty($item['url'])) {
                        $itemData["item"] = [
                            "@type" => "Thing",
                            "@id" => $item['url']
                        ];
                    }
                    
                    return $itemData;
                }, $breadcrumbs, array_keys($breadcrumbs))
            ];
        }
        
        return $schema;
    }
    
    /**
     * Generate Lottery Results Schema
     */
    public static function getLotteryResultsSchema($region, $date, $results, $provinces = [])
    {
        $regionNames = [
            'xsmb' => 'Xổ số Miền Bắc',
            'xsmn' => 'Xổ số Miền Nam', 
            'xsmt' => 'Xổ số Miền Trung'
        ];
        
        $regionName = $regionNames[$region] ?? 'Xổ số';
        
        return [
            "@context" => "https://schema.org",
            "@type" => "Event",
            "name" => "Kết quả " . $regionName . " ngày " . $date,
            "description" => "Kết quả xổ số " . $regionName . " ngày " . $date . " - Cập nhật nhanh chóng và chính xác",
            "startDate" => $date . "T18:00:00+07:00",
            "endDate" => $date . "T18:30:00+07:00",
            "eventStatus" => "https://schema.org/EventScheduled",
            "eventAttendanceMode" => "https://schema.org/OnlineEventAttendanceMode",
            "location" => [
                "@type" => "Place",
                "name" => $regionName,
                "address" => [
                    "@type" => "PostalAddress",
                    "addressCountry" => "VN"
                ]
            ],
            "organizer" => self::getOrganizationSchema(),
            "offers" => [
                "@type" => "Offer",
                "price" => "0",
                "priceCurrency" => "VND",
                "availability" => "https://schema.org/InStock"
            ],
            "about" => [
                "@type" => "Thing",
                "name" => "Xổ số kiến thiết",
                "description" => "Kết quả soi cầu 247 " . $regionName
            ]
        ];
    }
    
    /**
     * Generate Article Schema for predictions
     */
    public static function getArticleSchema($title, $description, $content, $author = "soicau247.com", $publishDate = null)
    {
        return [
            "@context" => "https://schema.org",
            "@type" => "Article",
            "headline" => $title,
            "description" => $description,
            "articleBody" => $content,
            "author" => [
                "@type" => "Organization",
                "name" => $author,
                "url" => self::$baseUrl
            ],
            "creator" => [
                "@type" => "Organization",
                "name" => $author,
                "url" => self::$baseUrl
            ],
            "publisher" => self::getOrganizationSchema(),
            "license" => "https://creativecommons.org/licenses/by-nc-sa/4.0/",
            "datePublished" => $publishDate ?: date('c'),
            "dateModified" => date('c'),
            "inLanguage" => "vi",
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => self::getBaseUrl()
            ],
            "about" => [
                "@type" => "Thing",
                "name" => "Dự đoán xổ số",
                "description" => "Phân tích và dự đoán kết quả xổ số"
            ]
        ];
    }
    
    /**
     * Generate FAQ Schema
     */
    public static function getFAQSchema($faqs)
    {
        return [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => array_map(function($faq) {
                return [
                    "@type" => "Question",
                    "name" => $faq['question'],
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => $faq['answer']
                    ]
                ];
            }, $faqs)
        ];
    }
    
    /**
     * Generate BreadcrumbList Schema
     */
    public static function getBreadcrumbSchema($breadcrumbs)
    {
        return [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => array_map(function($item, $index) {
                $itemData = [
                    "@type" => "ListItem",
                    "position" => $index + 1,
                    "name" => $item['name']
                ];
                
                // Nếu có URL, thêm item với @type và @id
                if (!empty($item['url'])) {
                    $itemData["item"] = [
                        "@type" => "Thing",
                        "@id" => $item['url']
                    ];
                }
                
                return $itemData;
            }, $breadcrumbs, array_keys($breadcrumbs))
        ];
    }
    
    /**
     * Generate LocalBusiness Schema for lottery centers
     */
    public static function getLocalBusinessSchema($province, $region)
    {
        $regionNames = [
            'xsmb' => 'Miền Bắc',
            'xsmn' => 'Miền Nam',
            'xsmt' => 'Miền Trung'
        ];
        
        return [
            "@context" => "https://schema.org",
            "@type" => "LocalBusiness",
            "name" => "Xổ số " . $province,
            "description" => "Kết quả xổ số " . $province . " - " . $regionNames[$region],
            "url" => self::$baseUrl . "/ket-qua-xo-so-" . strtolower(str_replace(' ', '-', $province)),
            "address" => [
                "@type" => "PostalAddress",
                "addressLocality" => $province,
                "addressCountry" => "VN"
            ],
            "areaServed" => [
                "@type" => "City",
                "name" => $province
            ],
            "serviceType" => "Xổ số kiến thiết",
            "openingHours" => "Mo-Su 18:00-18:30"
        ];
    }
    
    /**
     * Generate SoftwareApplication Schema for lottery tools
     */
    public static function getSoftwareApplicationSchema($appName, $description, $url)
    {
        return [
            "@context" => "https://schema.org",
            "@type" => "SoftwareApplication",
            "name" => $appName,
            "description" => $description,
            "url" => $url,
            "applicationCategory" => "GameApplication",
            "operatingSystem" => "Web Browser",
            "offers" => [
                "@type" => "Offer",
                "price" => "0",
                "priceCurrency" => "VND"
            ],
            "publisher" => self::getOrganizationSchema(),
            "inLanguage" => "vi"
        ];
    }
    
    /**
     * Generate JSON-LD string
     */
    public static function generateJsonLd($schema)
    {
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    /**
     * Generate multiple schemas as JSON-LD
     */
    public static function generateMultipleJsonLd($schemas)
    {
        $output = '';
        foreach ($schemas as $schema) {
            $output .= self::generateJsonLd($schema) . "\n";
        }
        return $output;
    }
    
    /**
     * Get schema for specific page type
     */
    public static function getPageSchema($pageType, $data = [])
    {
        switch ($pageType) {
            case 'homepage':
                return [
                    self::getWebsiteSchema(),
                    // self::getOrganizationSchema()
                ];
                
            case 'lottery_results':
                return [
                    self::getWebPageSchema(
                        'lottery_results',
                        $data['title'] ?? 'Kết quả xổ số',
                        $data['description'] ?? 'Kết quả xổ số mới nhất',
                        $data['url'] ?? null,
                        $data['breadcrumbs'] ?? []
                    ),
                    self::getLotteryResultsSchema(
                        $data['region'] ?? 'xsmb',
                        $data['date'] ?? date('d/m/Y'),
                        $data['results'] ?? [],
                        $data['provinces'] ?? []
                    )
                ];
                
            case 'prediction':
                return [
                    self::getWebPageSchema(
                        'prediction',
                        $data['title'] ?? 'Dự đoán xổ số',
                        $data['description'] ?? 'Dự đoán kết quả xổ số',
                        $data['url'] ?? null,
                        $data['breadcrumbs'] ?? []
                    ),
                    self::getArticleSchema(
                        $data['title'] ?? 'Dự đoán xổ số',
                        $data['description'] ?? 'Dự đoán kết quả xổ số',
                        $data['content'] ?? '',
                        $data['author'] ?? 'soicau247.com',
                        $data['publishDate'] ?? null
                    )
                ];
                
            case 'statistics':
                return [
                    // self::getWebPageSchema(
                    //     'statistics',
                    //     $data['title'] ?? 'Thống kê xổ số',
                    //     $data['description'] ?? 'Thống kê kết quả xổ số',
                    //     $data['url'] ?? null,
                    //     $data['breadcrumbs'] ?? []
                    // )
                ];
                
            default:
                return [
                    self::getWebPageSchema(
                        'webpage',
                        $data['title'] ?? self::$siteName,
                        $data['description'] ?? self::$siteDescription,
                        $data['url'] ?? null,
                        $data['breadcrumbs'] ?? []
                    )
                ];
        }
    }
}
