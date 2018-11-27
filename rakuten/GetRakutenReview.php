<?php
require_once '../vendor/autoload.php';
require_once '../phpquery/phpQuery-onefile.php';
 
class RakutenApi{
    // 商品価格ナビ製品検索API(version:2017-04-26)
    public function rakutenProductSearch($genreId, $page){
        $client = new RakutenRws_Client();
        $client->setApplicationId('1039648843158400527'); //ディベロッパーID

        $response = $client->execute('ProductSearch', array(
            'genreId' => $genreId, // ここにレビューを得たいジャンルのジャンルIDをセット
            'sort' => '-seller',// 売上順
            'page' => $page // 取得ページ(1-100)
        ));
    // 正しいレスポンスが帰って来たか    
    if ($response->isOk()) {
        return $response;
    } else {
        echo 'Error:'.$response->getMessage();
    }
    }
}

$pages = 1; // 取得するページ数(商品数は30*$pages)
$genreId = $argv[1]; // 取得したいジャンルID
$count = 2; // 取得するレビュー一覧のページ番号
$count_max = 10; // 取得するレビュー一覧のページ数最大(2-100))
$f = fopen(date("YmdHis").".csv", "w"); // 現在時刻が名前のファイル作成

for($page = 1; $page <= $pages; $page++ ){
    $response = RakutenApi::rakutenProductSearch($genreId, $page);
    // 製品毎のレビューを取得
    foreach($response as $product_key => $product){
        // レビュー一覧のURL
        $url = $product['reviewUrlPC'];
        // コンテンツの取得
        $code = file_get_contents($url);
        $doc = phpQuery::newDocumentHTML( $code, 'UTF-8' );
    
        echo 'Now Scraping... '. $url. "\n";
        foreach($doc->find(".rpsRevListLeft") as $key => $arr){
            $item_arr = array();
            $item_arr["productId"] = $product['productId'];
            $item_arr["txtPoint"] = pq($arr)->find(".txtPoint")->text();
            $item_arr["revTitle"] = pq($arr)->find(".revTitle")->text();
            $item_arr["revTxt"] = pq($arr)->find(".revTxt")->text();
            fputcsv($f, $item_arr);
        }
        
        // 次のページへのリンク
        preg_match_all('(https?://product.rakuten.co.jp/product/-/[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+/review/'.$count.'/[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+)',$doc->find("a"), $matches);
        while(isset($matches[0][0]) && $count <= $count_max){
            $url = $matches[0][0];
            $code = file_get_contents($url);
            $doc = phpQuery::newDocumentHTML( $code, 'UTF-8' );

            echo 'Now Scraping... '. $url. "\n";
            foreach($doc->find(".rpsRevListLeft") as $key => $arr){
                $item_arr = array();
                $item_arr["productId"] = $product['productId'];
                $item_arr["txtPoint"] = pq($arr)->find(".txtPoint")->text();
                $item_arr["revTitle"] = pq($arr)->find(".revTitle")->text();
                $item_arr["revTxt"] = pq($arr)->find(".revTxt")->text();
                fputcsv($f, $item_arr);
            }
            $count++;
            preg_match_all('(https?://product.rakuten.co.jp/product/-/[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+/review/'.$count.'/[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+)',$doc->find("a"), $matches);
        }
    }
}

fclose($f);

