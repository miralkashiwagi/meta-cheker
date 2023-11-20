<!doctype html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>meta情報チェックツール：入力画面</title>
<style>
    textarea{
        width: 100%;
        max-width: 600px;
        height: 400px;
        display: block;
    }
    button{
        scale: 2;
        transform-origin: 0 0;
    }
</style>
</head>
<body>
<form action="check.php" method="post">
    <label for="url_form">
        チェックしたいURLを複数入力（1URLあたり3秒～かかります）
    </label>
    <textarea id="url_form" name="url_from" cols="30" rows="10"></textarea>
    <button>送信</button>
</form>
</body>
</html>
