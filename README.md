# google_scraping

BrightDataのプロキシサービスを利用したGoogle検索順位スクレイピングクラス

## 概要

このクラスは、指定したキーワードとURLに対して、Google検索での順位を取得します。BrightDataのプロキシサービスを利用することで、安定したスクレイピングを実現しています。

## 必要要件

- PHP 7.0以上
- BrightDataのアカウント（プロキシサービス）
- Curlモジュール

## インストール

1. ファイルをプロジェクトに配置
2. BrightDataの認証情報を設定

```php
private static $brightDataUsername = '{BrightDataのユーザーネーム}';
private static $brightDataPassword = '{BrightDataのパスワード}';
private static $brightDataEndpoint = '{BrightDataのエンドポイント}';
```

## 基本的な使用方法

```php
// クラスのインスタンス化
$GoogleScraper = new GoogleScraper();

// 検索順位の取得
$rank = $GoogleScraper->getGoogleRank(
    "検索キーワード",           // 検索したいキーワード
    "https://example.com",     // 順位を確認したいURL
    0                          // URLタイプ（0: ドメインのみ, 1: ドメイン+パス, 2: ドメイン+パス+クエリ）
);

// 結果の確認
if ($rank !== null) {
    echo "検索順位: {$rank}位\n";
} else {
    echo "300位以内に表示されていません\n";
}
```

## URLタイプについて

- `0`: ドメインのみマッチ（例：`example.com`）
- `1`: ドメイン+パスまでマッチ（例：`example.com/path`）
- `2`: クエリパラメータまで完全マッチ（例：`example.com/path?param=value`）

## エラーハンドリング

```php
try {
    $rank = $GoogleScraper->getGoogleRank("キーワード", "https://example.com");
    if ($rank !== null) {
        echo "検索順位: {$rank}位\n";
    } else {
        echo "300位以内に表示されていません\n";
    }
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
```

## 注意事項

- BrightDataの利用料金が発生します
- Google検索のレート制限に注意してください
- 大量のリクエストは避けてください
- URLタイプは検索目的に応じて適切に選択してください

## ライセンス

MITライセンス
