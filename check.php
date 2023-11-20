<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>meta情報チェックツール：結果画面</title>
    <style>
        table {
            table-layout: fixed;
            font-size: small;
        }

        th, td {
            border: solid 1px #999;
        }
		img{
			max-width: 100%;
			width: 200px;
			height: auto;
		}
        .headings{
            margin: 0;
            font-size: 11px;
            border-bottom: solid 1px #ccc;
        }
        strong.ng{
            color: red;
        }
        small{
            font-size: 11px;
            display: block;
            font-weight: normal;
        }
    </style>
</head>
<body>
<table>
    <colgroup>
        <col style="width:20%;">
        <col style="width:20%;">
        <col style="width:30%;">
        <col style="width:5%;">
        <col style="width:25%;">
    </colgroup>
    <tr>
        <th>
            URL
        </th>
        <th>
            タイトル
        </th>
		<th>
			description
		</th>
		<th>
			og image
		</th>
        <th>
            headings
            <small>display:noneの要素も表示されます</small>
        </th>
    </tr>
    <?php
    //タイムアウトしないようにする
    set_time_limit(0);


    //URL一覧をPOSTのデータから生成する
    $urls = [];
    if (isset($_POST['url_from'])) {
        $str = $_POST['url_from'];
        $str = str_replace("\r\n", "\n", $str);
        $str = str_replace("\r", "\n", $str);
        $urls = explode("\n", $str);
    }


    // 待ち時間の定義（整数秒）
    $interval = 3;

    function getAltText($node) {
        $altText = "";
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'img') {
                $altText .= $child->getAttribute('alt') . ' ';
            }

            // If the child has children of its own, recursively collect their alt text
            if ($child instanceof DOMElement && $child->hasChildNodes()) {
                $altText .= getAltText($child);
            }
        }
        return $altText;
    }

    // URLを順番に処理
    foreach ($urls as $url) {
        //処理ごとに指定秒数まつ
        sleep($interval);

        // 出力バッファの内容を送信する
        @ob_flush();
        @flush();

        // （1回目）cURLセッションを初期化
        $ch = curl_init($url);

        // （1回目）cURLオプションを設定
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //FOLLOWLOCATION すると 最終的なリダイレクト結果まで一気に行くのでそれはしない
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // （1回目）HTTPリクエストを実行
        $response = curl_exec($ch);

        // （1回目）ステータスコードを取得
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // （1回目）cURLセッションを終了
        curl_close($ch);

        // （1回目）ページタイトルを取得
        $dom = new DOMDocument();
        @$dom->loadHTML($response);
        $title = $dom->getElementsByTagName('title')->item(0)->nodeValue;


        $metas = $dom->getElementsByTagName('meta');

        // og titleを取得
        $ogTitle = null;
        foreach ($metas as $meta) {
            if ($meta->getAttribute('property') == 'og:title') {
                $ogTitle = $meta->getAttribute('content');
                break;
            }
        }

        // meta descriptionを取得
	    $metaDescription = null;
	    foreach ($metas as $meta) {
		    if ($meta->getAttribute('name') == 'description') {
			    $metaDescription = $meta->getAttribute('content');
			    break;
		    }
	    }

        // og descriptionを取得
        $ogDescription = null;
        foreach ($metas as $meta) {
            if ($meta->getAttribute('property') == 'og:description') {
                $ogDescription = $meta->getAttribute('content');
                break;
            }
        }

        // og imageのURLを取得
	    $ogImageUrl = null;
	    foreach ($metas as $meta) {
		    if ($meta->getAttribute('property') == 'og:image') {
			    $ogImageUrl = $meta->getAttribute('content');
			    break;
		    }
	    }


        // 見出しを取得するためにDOMXpathを使う
        $xpath = new DOMXpath($dom);
        $headings = $xpath->query("//*/h1 | //*/h2 | //*/h3 | //*/h4 |//*/h5 | //*/h6");

        //子孫要素のaltを取得するために準備した可変変数（URLごとに0にもどす）
        $levels=[1,2,3,4,5,6];
        foreach ($levels as $level){
            ${$level . "count"} = 0;
        }
        //見出しレベル警告の判定用（URLごとにnullにもどす）
        $prev = null;

        ?>
        <tr>
            <td>
                <?php echo $url ?>
            </td>
            <td>
                <div>
                    <?php echo $title ?>
                </div>
                <?php
                if ($title != $ogTitle) {
                    echo "<div style='margin-top: 1em;'>（og:title）<br>" . $ogTitle . "</div>";
                }
                ?>
            </td>
            <td>
                <div>
                    <?php echo $metaDescription ?>
                </div>
                <?php
                if ($metaDescription != $ogDescription) {
                    echo "<div style='margin-top: 1em;'>（og:description）<br>" . $ogDescription . "</div>";
                }
                ?>
            </td>
			<td>
				<div>
					<img src="<?php echo $ogImageUrl ?>" alt=""/>
				</div>
			</td>
            <td>
                <?php
                foreach ($headings as $item) {
                    $tag = $item->tagName;
                    $level = (int) str_replace('h','',$tag);
                    $indent = $level -1;
                    $text = $item->nodeValue;
                    $alert = "";

                    //子孫要素のaltを取得
                    ${$level . "count"}++;
                    $altText = getAltText($item); // <=== getAltText function is used here

                    if(!empty($altText)){
                        $text = $text . "（以下altテキスト）" . $altText;
                    }


                    //見出しレベルによって警告表示する
                    if($prev){
                        // Resetting the counter here
                        if($level !== $prev){
                            ${$prev . "count"} = 0;
                        }

                        //前の見出しより1大きい、もしくは前の見出しと同じか小さいときはOK
                        //それ以外だとNG
                        if($level !== $prev +1 && $level > $prev){
                            $alert = "ng";
                        }
                    }else{
                        //1つ目の見出しなのにh1でない
                        if($level !== 1){
                            $alert = "ng";
                        }
                    }


                    echo "<p style='margin-left: ".$indent."em' class='headings heading-".$level."'><strong class='".$alert."'>".$tag. "</strong> - " .$text."</p>";


                    $prev = $level;
                }?>
                <?php //echo $httpCode ?>

            </td>
        </tr>
    <?php } ?>
</table>
</body>
</html>
