<?php
// エラーログの設定
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

// メモリ制限の緩和
ini_set('memory_limit', '256M');

// タイムアウトの設定
set_time_limit(300);

// MySeoクラスの読み込み
require_once 'GoogleScraper.php';

// HTMLヘッダー
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google検索順位チェッカー</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.6; 
            background-color: #f5f5f5; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px; 
            background-color: #fff; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        hr { 
            border: 1px solid #ddd; 
            margin: 15px 0; 
        }
        a { 
            color: #1a0dab; 
            text-decoration: none; 
        }
        a:hover { 
            text-decoration: underline; 
        }
        .error { 
            color: #dc3545; 
            background-color: #f8d7da; 
            padding: 10px; 
            border-radius: 4px; 
            margin: 10px 0; 
        }
        .success { 
            color: #28a745; 
            background-color: #d4edda; 
            padding: 10px; 
            border-radius: 4px; 
            margin: 10px 0; 
        }
        .info { 
            background-color: #f8f9fa; 
            padding: 10px; 
            border-radius: 4px; 
            margin: 10px 0; 
        }
        .url-type-selector { 
            margin: 15px 0; 
        }
        .url-type-selector label { 
            display: block; 
            margin-bottom: 5px; 
        }
        .url-type-selector select { 
            width: 100%; 
            padding: 8px; 
            border-radius: 4px; 
            border: 1px solid #ddd; 
        }
        .form-group { 
            margin: 15px 0; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
        }
        .form-control { 
            width: 100%; 
            padding: 8px; 
            border-radius: 4px; 
            border: 1px solid #ddd; 
        }
        .btn { 
            padding: 8px 16px; 
            border-radius: 4px; 
            border: none; 
            cursor: pointer; 
        }
        .btn-primary { 
            background-color: #007bff; 
            color: white; 
        }
        .btn-primary:hover { 
            background-color: #0056b3; 
        }
        .result-box {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Google検索順位チェッカー</h1>
    <p class="info">検索キーワードと対象URLを入力して、Google検索での順位を確認します。<br>
    最大300件（3ページ分）まで検索します。</p>

    <?php
    // GETパラメータの処理
    $typeSh = isset($_GET['typeSh']) ? (int)$_GET['typeSh'] : 0;
    if ($typeSh < 0 || $typeSh > 2) {
        $typeSh = 0;
    }

    // 検索クエリと対象URLの取得
    $query = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    $targetUrl = isset($_GET['url']) ? trim($_GET['url']) : '';

    // デフォルト値の設定
    if (empty($query)) {
        $query = "laravel vue.js チュートリアル";
    }
    if (empty($targetUrl)) {
        $targetUrl = "https://www.hypertextcandy.com/vue-laravel-tutorial-introduction";
    }
    ?>

    <form method="get" action="">
        <div class="url-type-selector">
            <label for="typeSh">URLタイプの選択:</label>
            <select name="typeSh" id="typeSh">
                <option value="0"<?php echo $typeSh === 0 ? ' selected' : ''; ?>>ドメインのみ</option>
                <option value="1"<?php echo $typeSh === 1 ? ' selected' : ''; ?>>ドメイン + パス</option>
                <option value="2"<?php echo $typeSh === 2 ? ' selected' : ''; ?>>ドメイン + パス + クエリ</option>
            </select>
        </div>
        <div class="form-group">
            <label for="keyword">検索クエリ:</label>
            <input type="text" name="keyword" id="keyword" value="<?php echo htmlspecialchars($query); ?>" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="url">対象URL:</label>
            <input type="url" name="url" id="url" value="<?php echo htmlspecialchars($targetUrl); ?>" class="form-control" required>
        </div>
        <input type="submit" value="検索実行" class="btn btn-primary">
    </form>

    <?php
    if (isset($_GET['keyword']) && isset($_GET['url'])) {
        echo "<hr>";
        echo "<div class='result-box'>";
        echo "<h2>検索結果</h2>";
        echo "<p>検索クエリ: " . htmlspecialchars($query) . "</p>";
        echo "<p>対象URL: <a href='" . htmlspecialchars($targetUrl) . "' target='_blank'>" . htmlspecialchars($targetUrl) . "</a></p>";
        echo "<p>URLタイプ: " . MySeo::getUrlTypeDescription($typeSh) . "</p>";
        echo "<hr>";

        try {
            $startTime = microtime(true);
            $MySeo = new MySeo();
            $rank = $MySeo->getGoogleRank($query, $targetUrl, $typeSh);
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            if ($rank !== null) {
                echo "<div class='success'>";
                echo "✨ 検索順位: {$rank}位<br>";
                echo "⏱️ 実行時間: {$executionTime}秒";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "❌ 300位以内に順位が見つかりませんでした<br>";
                echo "⏱️ 実行時間: {$executionTime}秒";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "❌ エラーが発生しました:<br>";
            echo htmlspecialchars($e->getMessage());
            echo "</div>";
        }
        echo "</div>";
    }
    ?>
</div>
</body>
</html> 