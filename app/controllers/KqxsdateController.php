<?php

namespace App\Controllers;

use App\Models\LotteryResults;
use App\Models\Provinces;
use App\Models\PredictionArticles;
use App\Library\KqxsHelper;

/**
 * KqxsdateController
 * Xử lý hiển thị kết quả xổ số theo ngày được chọn từ calendar
 */
class KqxsdateController extends ControllerBase
{
    /**
     * Hiển thị kết quả xổ số của ngày được chọn (cả 3 miền)
     */
    public function indexAction()
    {
        $day = $this->dispatcher->getParam('day');
        $month = $this->dispatcher->getParam('month');
        $year = $this->dispatcher->getParam('year');
        
        // Validate date
        if (!$this->isValidDate($day, $month, $year)) {
            return $this->response->redirect('/')->send();
        }
        
        $dateString = sprintf('%s-%s-%s', $year, $month, $day);
        $date = \DateTime::createFromFormat('Y-m-d', $dateString);
        
        if (!$date) {
            return $this->response->redirect('/')->send();
        }
        
        // Check if date is in the future
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        if ($date > $today) {
            return $this->response->redirect('/')->send();
        }
        
        // Fetch results for all regions
        $xsmnResults = $this->getResultsByDateAndRegion($dateString, 'XSMN');
        $xsmbResults = $this->getResultsByDateAndRegion($dateString, 'XSMB');
        $xsmtResults = $this->getResultsByDateAndRegion($dateString, 'XSMT');
        
        // Fetch prediction articles for each region (6 latest articles per region)
        $xsmnPredictions = $this->getPredictionArticlesByRegion('XSMN', 6);
        $xsmbPredictions = $this->getPredictionArticlesByRegion('XSMB', 6);
        $xsmtPredictions = $this->getPredictionArticlesByRegion('XSMT', 6);
        
        // Format date for display
        $formattedDate = $date->format('d/m/Y');
        $dayOfWeek = $this->getDayOfWeekVietnamese($date->format('N'));
        
        // Pass to view
        $this->view->setVars([
            'date' => $formattedDate,
            'dayOfWeek' => $dayOfWeek,
            'dateObject' => $date,
            'xsmnResults' => $xsmnResults,
            'xsmbResults' => $xsmbResults,
            'xsmtResults' => $xsmtResults,
            'hasXsmn' => !empty($xsmnResults),
            'hasXsmb' => !empty($xsmbResults),
            'hasXsmt' => !empty($xsmtResults),
            'xsmnPredictions' => $xsmnPredictions,
            'xsmbPredictions' => $xsmbPredictions,
            'xsmtPredictions' => $xsmtPredictions,
        ]);
        
        // SEO
        $pageTitle = "Kết quả xổ số ngày {$formattedDate} - KQXS 3 miền {$formattedDate}";
        $this->view->setVar('pageTitle', $pageTitle);
        $this->view->setVar('metaDescription', "Xem kết quả xổ số ngày {$formattedDate} cả 3 miền Bắc, Trung, Nam. KQXS {$dayOfWeek} ngày {$formattedDate} nhanh và chính xác nhất.");
        
        $this->view->pick('kqxsdate/index');
    }
    
    /**
     * Validate date parameters
     */
    private function isValidDate($day, $month, $year)
    {
        if (!$day || !$month || !$year) {
            return false;
        }
        
        $day = (int)$day;
        $month = (int)$month;
        $year = (int)$year;
        
        return checkdate($month, $day, $year);
    }
    
    /**
     * Get results by date and region
     */
    private function getResultsByDateAndRegion($dateString, $region)
    {
        $results = LotteryResults::find([
            'conditions' => 'draw_date = :date: AND draw_type = :type:',
            'bind' => [
                'date' => $dateString,
                'type' => $region
            ],
            'order' => 'id ASC'
        ]);
        
        if (!$results || count($results) === 0) {
            return [];
        }
        
        $formatted = [];
        foreach ($results as $result) {
            // Get province info
            $province = Provinces::findFirst([
                'conditions' => 'id = :id:',
                'bind' => ['id' => $result->province_id]
            ]);
            
            $item = [
                'id' => $result->id,
                'province' => $province ? $province->name : '',
                'province_code' => $province ? $province->code : '',
                'draw_date' => $result->draw_date,
                'draw_type' => $result->draw_type,
            ];
            
            // Add prizes
            if ($region === 'XSMB') {
                // XSMB has different structure
                $item['special_prize'] = KqxsHelper::toArray($result->special_prize);
                $item['first_prize'] = KqxsHelper::toArray($result->first_prize);
                $item['second_prize'] = KqxsHelper::toArray($result->second_prize);
                $item['third_prize'] = KqxsHelper::toArray($result->third_prize);
                $item['fourth_prize'] = KqxsHelper::toArray($result->fourth_prize);
                $item['fifth_prize'] = KqxsHelper::toArray($result->fifth_prize);
                $item['sixth_prize'] = KqxsHelper::toArray($result->sixth_prize);
                $item['seventh_prize'] = KqxsHelper::toArray($result->seventh_prize);
                $item['lv'] = KqxsHelper::toArray($result->lv);
            } else {
                // XSMN and XSMT use same field names as XSMB but different mapping
                // eighth_prize = G8, seventh_prize = G7, ... special_prize = ĐB
                $item['g8'] = KqxsHelper::toArray($result->eighth_prize);
                $item['g7'] = KqxsHelper::toArray($result->seventh_prize);
                $item['g6'] = KqxsHelper::toArray($result->sixth_prize);
                $item['g5'] = KqxsHelper::toArray($result->fifth_prize);
                $item['g4'] = KqxsHelper::toArray($result->fourth_prize);
                $item['g3'] = KqxsHelper::toArray($result->third_prize);
                $item['g2'] = KqxsHelper::toArray($result->second_prize);
                $item['g1'] = [$result->first_prize]; // G1 is single value
                $item['db'] = [$result->special_prize]; // ĐB is single value
            }
            
            $formatted[] = $item;
        }
        
        return $formatted;
    }
    
    /**
     * Get Vietnamese day of week name
     */
    private function getDayOfWeekVietnamese($dayNumber)
    {
        $days = [
            1 => 'thứ 2',
            2 => 'thứ 3',
            3 => 'thứ 4',
            4 => 'thứ 5',
            5 => 'thứ 6',
            6 => 'thứ 7',
            7 => 'chủ nhật'
        ];
        
        return $days[$dayNumber] ?? '';
    }
    
    /**
     * Get prediction articles by region
     */
    private function getPredictionArticlesByRegion($region, $limit = 6)
    {
        $articles = PredictionArticles::find([
            'conditions' => 'region = :region: AND province_id IS NULL',
            'bind' => ['region' => $region],
            'order' => 'prediction_date DESC, created_at DESC',
            'limit' => $limit
        ]);
        
        if (!$articles || count($articles) === 0) {
            return [];
        }
        
        $formatted = [];
        foreach ($articles as $article) {
            $predictionDate = $article->prediction_date ? 
                \DateTime::createFromFormat('Y-m-d', $article->prediction_date) : null;
            
            $daysAgo = 0;
            if ($predictionDate) {
                $now = new \DateTime();
                $interval = $now->diff($predictionDate);
                $daysAgo = (int)$interval->format('%a');
            }
            
            $formatted[] = [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'prediction_date' => $predictionDate ? $predictionDate->format('d/m/Y') : '',
                'days_ago' => $daysAgo,
                'content' => $article->content,
            ];
        }
        
        return $formatted;
    }
}

