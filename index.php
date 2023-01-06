<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>入力</title>
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
<p>
    チェックしたいURLを複数入力（1URLあたり3秒～かかります）
</p>
<form action="check.php" method="post">
    <textarea name="url_from" cols="30" rows="10"></textarea>
    <button>送信</button>
</form>
</body>
</html>
