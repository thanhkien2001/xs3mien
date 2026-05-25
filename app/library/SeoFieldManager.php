<?php

namespace App\Library;

use Phalcon\Annotations\Adapter\Memory;
use Phalcon\Annotations\Annotation;

class SeoFieldManager
{
    private $annotations;
    private $seoFields = [];
    private $configFile;
    
    public function __construct()
    {
        try {
            error_log("DEBUG: SeoFieldManager::__construct() - Starting");
            $this->annotations = new Memory();
            $this->configFile = __DIR__ . '/../../annotations/seo_fields.json';
            error_log("DEBUG: SeoFieldManager constructor - configFile: " . $this->configFile);
            error_log("DEBUG: SeoFieldManager constructor - file exists: " . (file_exists($this->configFile) ? 'YES' : 'NO'));
            
            $this->loadSeoFields();
            error_log("DEBUG: SeoFieldManager constructor - loaded " . count($this->seoFields) . " page types");
            
            foreach (array_keys($this->seoFields) as $pageType) {
                $subtypes = array_keys($this->seoFields[$pageType]);
                error_log("DEBUG: SeoFieldManager constructor - {$pageType}: " . implode(', ', $subtypes));
            }
            
        } catch (\Exception $e) {
            error_log("ERROR: SeoFieldManager::__construct() - Exception: " . $e->getMessage());
            error_log("ERROR: Stack trace: " . $e->getTraceAsString());
            throw $e;
        } catch (\Error $e) {
            error_log("FATAL: SeoFieldManager::__construct() - Error: " . $e->getMessage());
            error_log("FATAL: Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Load SEO fields from file or default
     */
    private function loadSeoFields()
    {
        // Đọc từ file JSON nếu có
        if (file_exists($this->configFile)) {
            $jsonData = file_get_contents($this->configFile);
            $decodedData = json_decode($jsonData, true);
            if ($decodedData !== null && !empty($decodedData)) {
                $this->seoFields = $decodedData;
                return; // Đã load thành công từ file
            }
        }
        
        // Nếu file không tồn tại hoặc rỗng, dùng default
        error_log("DEBUG: Loading default SEO fields data");
        $this->seoFields = [
            'vietlott_live' => [
                'mega645' => [
                    'title' => [
                        'type' => 'text',
                        'default' => 'Trực tiếp kết quả xổ số Mega 6/45 Vietlott hôm nay - Live Mega 6/45',
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'default' => 'Xem trực tiếp kết quả xổ số Mega 6/45 Vietlott hôm nay. Cập nhật live kết quả Mega 6/45 mới nhất, chính xác và nhanh nhất.',
                        'required' => true
                    ],
                    'canonical_template' => [
                        'type' => 'text',
                        'default' => '/truc-tiep-ket-qua-xo-so-mega-6-45-vietlott.html',
                        'required' => true
                    ]
                ],
                'power655' => [
                    'title' => [
                        'type' => 'text',
                        'default' => 'Trực tiếp kết quả xổ số Power 6/55 Vietlott hôm nay - Live Power 6/55',
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'default' => 'Xem trực tiếp kết quả xổ số Power 6/55 Vietlott hôm nay. Cập nhật live kết quả Power 6/55 mới nhất, chính xác và nhanh nhất.',
                        'required' => true
                    ],
                    'canonical_template' => [
                        'type' => 'text',
                        'default' => '/truc-tiep-ket-qua-xo-so-power-6-55-vietlott.html',
                        'required' => true
                    ]
                ],
                'max3d' => [
                    'title' => [
                        'type' => 'text',
                        'default' => 'Trực tiếp kết quả xổ số Max 3D Vietlott hôm nay - Live Max 3D',
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'default' => 'Xem trực tiếp kết quả xổ số Max 3D Vietlott hôm nay. Cập nhật live kết quả Max 3D mới nhất, chính xác và nhanh nhất.',
                        'required' => true
                    ],
                    'canonical_template' => [
                        'type' => 'text',
                        'default' => '/truc-tiep-ket-qua-xo-so-max-3d-vietlott.html',
                        'required' => true
                    ]
                ],
                'max3dpro' => [
                    'title' => [
                        'type' => 'text',
                        'default' => 'Trực tiếp kết quả xổ số Max 3D Pro Vietlott hôm nay - Live Max 3D Pro',
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'default' => 'Xem trực tiếp kết quả xổ số Max 3D Pro Vietlott hôm nay. Cập nhật live kết quả Max 3D Pro mới nhất, chính xác và nhanh nhất.',
                        'required' => true
                    ],
                    'canonical_template' => [
                        'type' => 'text',
                        'default' => '/truc-tiep-ket-qua-xo-so-max-3d-pro-vietlott.html',
                        'required' => true
                    ]
                ]
            ],
            'vietlott_meta' => [
                'Mega645' => [
                    'title_template' => [
                        'type' => 'text',
                        'default' => 'Kết quả Vietlott {typeName} {dateStr} - Soi Cầu 247',
                        'required' => true
                    ],
                    'description_template' => [
                        'type' => 'textarea',
                        'default' => 'Kết quả xổ số Vietlott {typeName} {dateStr}. Cập nhật nhanh chóng, chính xác. Thống kê, phân tích số may mắn.',
                        'required' => true
                    ],
                    'canonical_template' => [
                        'type' => 'text',
                        'default' => '/ket-qua-xoso-{typeName_slug}-vietlott.html',
                        'required' => true
                    ]
                ],
                'Power655' => [
                    'title_template' => [
                        'type' => 'text',
                        'default' => 'Kết quả Vietlott {typeName} {dateStr} - Soi Cầu 247',
                        'required' => true
                    ],
                    'description_template' => [
                        'type' => 'textarea',
                        'default' => 'Kết quả xổ số Vietlott {typeName} {dateStr}. Cập nhật nhanh chóng, chính xác. Thống kê, phân tích số may mắn.',
                        'required' => true
                    ],
                    'canonical_template' => [
                        'type' => 'text',
                        'default' => '/ket-qua-xoso-{typeName_slug}-vietlott.html',
                        'required' => true
                    ]
                ]
            ],
            'fallback_seo' => [
                'xsmb' => [
                    'title' => [
                        'type' => 'text',
                        'default' => 'XSMB - Kết Quả Xổ Số Miền Bắc Hôm Nay - SXMB - KQXSMB',
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'default' => 'Kết quả xổ số Miền Bắc hôm nay - XSMB - SXMB - KQXSMB. Cập nhật kết quả xổ số kiến thiết Miền Bắc mới nhất, chính xác và nhanh nhất.',
                        'required' => true
                    ],
                    'canonical_template' => [
                        'type' => 'text',
                        'default' => '/ket-qua-xo-so-mien-bac-xsmb.html',
                        'required' => true
                    ]
                ]
            ],
            'archive' => [
                'province' => [
                    'title_template' => [
                        'type' => 'text',
                        'default' => 'Lịch sử kết quả xổ số {provinceName} - {regionName}',
                        'required' => true
                    ],
                    'description_template' => [
                        'type' => 'textarea',
                        'default' => 'Xem lịch sử kết quả xổ số {provinceName} {regionName} qua các kỳ quay. Dữ liệu chính xác, cập nhật liên tục.',
                        'required' => true
                    ],
                    'keywords_template' => [
                        'type' => 'text',
                        'default' => 'lịch sử kết quả xổ số, {provinceName}, {regionName}, xsmb, xsmn, xsmt, kết quả xổ số cũ',
                        'required' => true
                    ]
                ],
                'weekday' => [
                    'title_template' => [
                        'type' => 'text',
                        'default' => 'Kết quả xổ số {regionName} {weekdayText} - Lịch sử kết quả',
                        'required' => true
                    ],
                    'description_template' => [
                        'type' => 'textarea',
                        'default' => 'Xem lịch sử kết quả xổ số {regionName} các ngày {weekdayText}. Dữ liệu chính xác, cập nhật liên tục.',
                        'required' => true
                    ],
                    'keywords_template' => [
                        'type' => 'text',
                        'default' => 'lịch sử kết quả xổ số, {regionName}, xsmb, xsmn, xsmt, kết quả xổ số cũ',
                        'required' => true
                    ]
                ]
            ]
        ];
        
        // Lưu default vào file nếu chưa có hoặc file rỗng
        if (!file_exists($this->configFile) || (file_exists($this->configFile) && filesize($this->configFile) < 10)) {
            error_log("DEBUG: Saving default data to file");
            $this->saveToFile();
        }
    }
    
    /**
     * Get SEO field value
     */
    public function getField($pageType, $pageSubtype, $fieldName, $variables = [])
    {
        if (!isset($this->seoFields[$pageType][$pageSubtype][$fieldName])) {
            return null;
        }
        
        $field = $this->seoFields[$pageType][$pageSubtype][$fieldName];
        $value = $field['default'];
        
        // Replace variables in template
        if (!empty($variables)) {
            foreach ($variables as $key => $val) {
                if (is_scalar($val)) {
                    $value = str_replace('{' . $key . '}', (string)$val, $value);
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Get all fields for a page type/subtype
     */
    public function getFields($pageType, $pageSubtype = null)
    {
        if ($pageSubtype === null) {
            return $this->seoFields[$pageType] ?? [];
        }
        
        return $this->seoFields[$pageType][$pageSubtype] ?? [];
    }
    
    /**
     * Set field value (for runtime modification)
     */
    public function setField($pageType, $pageSubtype, $fieldName, $value)
    {
        if (!isset($this->seoFields[$pageType])) {
            $this->seoFields[$pageType] = [];
        }
        
        if (!isset($this->seoFields[$pageType][$pageSubtype])) {
            $this->seoFields[$pageType][$pageSubtype] = [];
        }
        
        if (!isset($this->seoFields[$pageType][$pageSubtype][$fieldName])) {
            $this->seoFields[$pageType][$pageSubtype][$fieldName] = [
                'type' => 'text',
                'default' => '',
                'required' => false
            ];
        }
        
        $this->seoFields[$pageType][$pageSubtype][$fieldName]['default'] = $value;
        
        // Lưu vào file JSON
        return $this->saveToFile();
    }
    
    /**
     * Save fields to JSON file
     */
    private function saveToFile()
    {
        $jsonData = json_encode($this->seoFields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Log debug info
        error_log("DEBUG: Saving to file: " . $this->configFile);
        error_log("DEBUG: JSON data length: " . strlen($jsonData));
        error_log("DEBUG: Directory exists: " . (is_dir(dirname($this->configFile)) ? 'Yes' : 'No'));
        error_log("DEBUG: Directory writable: " . (is_writable(dirname($this->configFile)) ? 'Yes' : 'No'));
        
        // Tạo thư mục nếu chưa có
        $dir = dirname($this->configFile);
        if (!is_dir($dir)) {
            error_log("DEBUG: Creating directory: " . $dir);
            mkdir($dir, 0755, true);
        }
        
        $result = file_put_contents($this->configFile, $jsonData);
        
        // Log kết quả
        if ($result === false) {
            error_log("DEBUG: FAILED to write file");
            error_log("Cannot write to SEO fields file: " . $this->configFile);
        } else {
            error_log("DEBUG: SUCCESS - wrote " . $result . " bytes");
            error_log("SEO fields saved successfully to: " . $this->configFile);
        }
        
        return $result !== false;
    }
    
    
    /**
     * Get field configuration for admin interface
     */
    public function getFieldConfig($pageType, $pageSubtype, $fieldName)
    {
        return $this->seoFields[$pageType][$pageSubtype][$fieldName] ?? null;
    }
    
    /**
     * Get all page types
     */
    public function getPageTypes()
    {
        return array_keys($this->seoFields);
    }
    
    /**
     * Generate SEO data with custom field values
     */
    public function generateSeoData(string $pageType, string $pageSubtype, array $variables = [], array $customFields = []): array
    {
        $fields = $this->getFields($pageType, $pageSubtype);
        
        // Override with custom field values if provided
        if (!empty($customFields)) {
            foreach ($customFields as $fieldName => $customField) {
                if (isset($fields[$fieldName])) {
                    $fields[$fieldName]['default'] = $customField['default'];
                }
            }
        }
        
        // Kiểm tra xem có dữ liệu tỉnh không
        $hasProvinceData = !empty($variables['provinceCode']) && !empty($variables['provinceName']);
        
        $result = [];
        foreach ($fields as $fieldName => $fieldConfig) {
            // Bỏ qua các field _province_template vì sẽ xử lý riêng
            if (strpos($fieldName, '_province_template') !== false) {
                continue;
            }
            
            // Nếu có province data, ưu tiên dùng template cho province
            if ($hasProvinceData && strpos($fieldName, '_template') !== false) {
                // title_template -> title_province_template
                $baseFieldName = str_replace('_template', '', $fieldName);
                $provinceFieldName = $baseFieldName . '_province_template';
                
                if (isset($fields[$provinceFieldName])) {
                    $template = $fields[$provinceFieldName]['default'];
                } else {
                    $template = $fieldConfig['default'];
                }
            } else {
                $template = $fieldConfig['default'];
            }
            
            // Replace variables in template
            foreach ($variables as $variable => $value) {
                if (!is_scalar($value)) {
                    continue;
                }
                $template = str_replace('{' . $variable . '}', (string)$value, $template);
            }
            
            // Keep original field names (title_template, description_template, keywords_template)
            $result[$fieldName] = $template;
        }
        
        return $result;
    }
    
    /**
     * Force create default file (for testing)
     */
    public function createDefaultFile()
    {
        if (empty($this->seoFields)) {
            $this->loadSeoFields();
        }
        $this->saveToFile();
    }
    
    /**
     * Get subtypes for a page type
     */
    public function getPageSubtypes($pageType)
    {
        return array_keys($this->seoFields[$pageType] ?? []);
    }
}
