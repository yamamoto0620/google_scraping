<?php
/**
 * GoogleScraper - Google検索順位チェッカークラス
 * 
 * 使用例：
 * 
 * // クラスのインスタンス化
 * $GoogleScraper = new GoogleScraper();
 * 
 * // 基本的な使用方法
 * $rank = $GoogleScraper->getGoogleRank(
 *     "検索キーワード",           // 検索したいキーワード
 *     "https://example.com",     // 順位を確認したいURL
 *     0                          // URLタイプ（0: ドメインのみ, 1: ドメイン+パス, 2: ドメイン+パス+クエリ）
 * );
 * 
 * // エラーハンドリング付きの使用例
 * try {
 *     $rank = $GoogleScraer->getGoogleRank("キーワード", "https://example.com");
 *     if ($rank !== null) {
 *         echo "検索順位: {$rank}位\n";
 *     } else {
 *         echo "300位以内に表示されていません\n";
 *     }
 * } catch (Exception $e) {
 *     echo "エラー: " . $e->getMessage() . "\n";
 * }
 * 
 * // URLタイプの説明を取得
 * $typeDescription = MySeo::getUrlTypeDescription(0); // "ドメインのみ" を返す
 */
class GoogleScraper {

    // BrightData設定
    private static $brightDataUsername = '{briteDataのユーザネーム}';
    private static $brightDataPassword = '{brightDataのパスワード}';
    private static $brightDataEndpoint = '{brigthDataのエンドポイント}';

    // User-Agent設定
    private static $userAgents = [
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Edge/121.0.0.0"
    ];

    // 設定値
    private static $maxRetries = 5;
    private static $minWaitTime = 10;
    private static $maxWaitTime = 30;
    private static $maxPages = 3;

    /**
     * Google検索順位を取得
     * @param string $query 検索クエリ
     * @param string $targetUrl 対象URL
     * @param int $typeSh URLタイプ（0: ドメインのみ, 1: ドメイン+パス, 2: ドメイン+パス+クエリ）
     * @return int|null 検索順位（見つからない場合はnull）
     */
    public function getGoogleRank($query, $targetUrl, $typeSh = 0) {
        try {
            $rank = null;
            for ($page = 0; $page < self::$maxPages; $page++) {
                $searchResults = $this->fetchBrightDataResults($query, $page);
                
                if ($searchResults === false) {
                    throw new Exception("検索結果の取得に失敗しました");
                }

                $rank = $this->findRankInResults($searchResults, $targetUrl, $page * 100, $typeSh);
                if ($rank !== null) {
                    return $rank;
                }

                if ($page < self::$maxPages - 1) {
                    $waitTime = $this->getRandomWaitTime();
                    sleep($waitTime);
                }
            }
            
            return $rank;

        } catch (Exception $e) {
            throw new Exception("検索順位の取得に失敗しました: " . $e->getMessage());
        }
    }

    /**
     * BrightDataを使用して検索結果を取得
     */
    private function fetchBrightDataResults($query, $page) {
        $retryCount = 0;
        
        while ($retryCount < self::$maxRetries) {
            try {
                $searchUrl = 'https://www.google.co.jp/search?' . http_build_query([
                    'q' => $query,
                    'start' => $page * 100,
                    'hl' => 'ja',
                    'lr' => 'lang_ja',
                    'num' => 100,
                    'gl' => 'jp',
                    'uule' => 'w+CAIQICIFSmFwYW4',
                    'filter' => '0'
                ]);
                
                $ch = curl_init();
                $proxyUrl = "http://" . self::$brightDataUsername . ":" . self::$brightDataPassword . "@" . self::$brightDataEndpoint;
                
                curl_setopt_array($ch, [
                    CURLOPT_URL => $searchUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_PROXY => $proxyUrl,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_CONNECTTIMEOUT => 30,
                    CURLOPT_HTTPHEADER => [
                        'User-Agent: ' . $this->getRandomUserAgent(),
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language: ja,en-US;q=0.7,en;q=0.3',
                        'Accept-Encoding: identity',
                        'Connection: keep-alive',
                        'Upgrade-Insecure-Requests: 1',
                        'Cache-Control: max-age=0',
                        'Sec-Fetch-Dest: document',
                        'Sec-Fetch-Mode: navigate',
                        'Sec-Fetch-Site: none',
                        'Sec-Fetch-User: ?1'
                    ],
                    CURLOPT_ENCODING => 'identity',
                    CURLOPT_VERBOSE => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 5,
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_TCP_KEEPALIVE => 1,
                    CURLOPT_TCP_KEEPIDLE => 120
                ]);

                $response = curl_exec($ch);
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $headerSize);
                $body = substr($response, $headerSize);

                if (curl_errno($ch)) {
                    throw new Exception("CURLエラー: " . curl_error($ch));
                }

                curl_close($ch);

                if (empty($body)) {
                    throw new Exception("レスポンスが空です");
                }

                return $this->parseSearchResults($body);

            } catch (Exception $e) {
                $retryCount++;
                if ($retryCount >= self::$maxRetries) {
                    throw new Exception("検索結果の取得に失敗しました（{$retryCount}回リトライ）: " . $e->getMessage());
                }
                
                $waitTime = $this->getRandomWaitTime();
                sleep($waitTime);
            }
        }
    }

    /**
     * 検索結果をパース
     */
    private function parseSearchResults($body) {
        $patterns = [
            '/<div[^>]*class="[^"]*(?:g|MjjYud|NUnG9d|ZINbbc|xpdopen)[^"]*"[^>]*>.*?<\/div>/s',
            '/<div[^>]*class="[^"]*(?:VwiC3b|DKV0Md|LC20lb)[^"]*"[^>]*>.*?<\/div>/s',
            '/<div[^>]*class="[^"]*(?:tF2Cxc|yuRUbf)[^"]*"[^>]*>.*?<\/div>/s'
        ];

        $matches = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $body, $patternMatches)) {
                $matches = array_merge($matches, $patternMatches[0]);
            }
        }

        if (empty($matches)) {
            throw new Exception("検索結果のパターンが見つかりません");
        }

        $linkPatterns = [
            '/<a[^>]*href="([^"]+)"[^>]*>/i',
            '/<a[^>]*data-href="([^"]+)"[^>]*>/i',
            '/<a[^>]*data-url="([^"]+)"[^>]*>/i',
            '/<a[^>]*data-ved="[^"]*"[^>]*href="([^"]+)"[^>]*>/i'
        ];

        $links = [];
        foreach ($matches as $match) {
            foreach ($linkPatterns as $pattern) {
                if (preg_match($pattern, $match, $linkMatches)) {
                    $link = $linkMatches[1];
                    $link = html_entity_decode($link, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (!preg_match('/^\/search\?|^\/url\?|^\/preferences\?|^javascript:|^#/', $link)) {
                        if (filter_var($link, FILTER_VALIDATE_URL)) {
                            $links[] = $link;
                        }
                    }
                }
            }
        }

        if (empty($links)) {
            throw new Exception("有効なリンクが見つかりません");
        }

        return ['organic_results' => array_map(function($link) {
            return ['link' => $link];
        }, $links)];
    }

    /**
     * 検索結果から順位を特定
     */
    private function findRankInResults($results, $targetUrl, $start, $typeSh = 0) {
        if (!isset($results['organic_results'])) {
            return null;
        }

        foreach ($results['organic_results'] as $i => $result) {
            if (!isset($result['link'])) {
                continue;
            }

            $href = $result['link'];
            $normalizedResult = $this->normalizeUrl($href, $typeSh);
            $normalizedTarget = $this->normalizeUrl($targetUrl, $typeSh);
            
            if (strpos($normalizedResult, $normalizedTarget) !== false || 
                strpos($normalizedTarget, $normalizedResult) !== false) {
                return $start + $i + 1;
            }
        }

        return null;
    }

    /**
     * URLを正規化
     */
    private function normalizeUrl($url, $typeSh = 0) {
        if (empty($url) || !is_string($url)) {
            return '';
        }

        $url = preg_replace('#^https?://#', '', $url);
        $parsedUrl = parse_url($url);
        
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return strtolower(trim($url));
        }

        $domain = preg_replace('/^www\./', '', $parsedUrl['host']);
        
        switch ($typeSh) {
            case 0: // ドメインのみ
                return strtolower($domain);
            case 1: // ドメイン + パス
                $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
                return strtolower($domain . $path);
            case 2: // ドメイン + パス + クエリ
                $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
                $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
                return strtolower($domain . $path . $query);
            default:
                return strtolower($domain);
        }
    }

    /**
     * ランダムなUser-Agentを取得
     */
    private function getRandomUserAgent() {
        return self::$userAgents[array_rand(self::$userAgents)];
    }

    /**
     * ランダムな待機時間を取得
     */
    private function getRandomWaitTime() {
        $mean = (self::$minWaitTime + self::$maxWaitTime) / 2;
        $std = (self::$maxWaitTime - self::$minWaitTime) / 4;
        
        do {
            $waitTime = (int)round($this->gaussianRandom($mean, $std));
        } while ($waitTime < self::$minWaitTime || $waitTime > self::$maxWaitTime);
        
        return $waitTime;
    }

    /**
     * ガウス分布に従う乱数を生成
     */
    private function gaussianRandom($mean, $std) {
        $x = mt_rand() / mt_getrandmax();
        $y = mt_rand() / mt_getrandmax();
        $z = sqrt(-2 * log($x)) * cos(2 * M_PI * $y);
        return $z * $std + $mean;
    }

    /**
     * URLタイプの説明を取得
     * @param int $typeSh URLタイプ
     * @return string URLタイプの説明
     */
    public static function getUrlTypeDescription($typeSh) {
        switch ($typeSh) {
            case 0:
                return "ドメインのみ";
            case 1:
                return "ドメイン + パス";
            case 2:
                return "ドメイン + パス + クエリ";
            default:
                return "ドメインのみ（デフォルト）";
        }
    }
}
?>