<!DOCTYPE html>
<html lang="en"><head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport"
          content="height=device-height, width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <title>Wrong Transaction</title>
    <!-- Google font -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,700|Passion+One:900" rel="stylesheet">

    <style media="all" type="text/css">
        * {
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }

        body {
            padding: 0;
            margin: 0;
        }

        #pnp {
            position: relative;
            height: 100vh;
            margin: 0 auto;
            max-width: 600px;
            max-height: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 2em;
        }

        .container {
            position: relative;
            width: 100%;
            padding-left: 190px;
            line-height: 1.4;
        }

        .container .item--face {
            position: absolute;
            left: 30%;
            top: 0;
            width: 40%;
        }
        .container .item--text {
            position: absolute;
            left: 40%;
            top: 0;
            width: 60%;
        }

        .container .item--face h1 {
            font-family: 'Passion One', cursive;
            color: {$face_color};
            font-size: 150px;
            width: 150px;
            letter-spacing: 15.5px;
            margin: 0px;
            font-weight: 900;
            -webkit-transform: translate(-50%, -40%);
            -ms-transform: translate(-50%, -40%);
            transform: translate(-50%, -40%);
        }

        .container .item--text .text {
            -webkit-transform: translate(0, -25%);
            -ms-transform: translate(0, -25%);
            transform: translate(0, -25%);
        }

        .container h2 {
            font-family: 'Raleway', sans-serif;
            color: #292929;
            font-size: 28px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            margin-top: 0;
        }

        .container p {
            font-family: 'Raleway', sans-serif;
            font-size: 14px;
            font-weight: 400;
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }
    </style>
</head>
<body>
<div id="pnp">
    <div class="logo">
        <img src="{$logo}">
    </div>
    <div class="container">
        <div class="item--face">
            <h1>:(</h1>
        </div>
        <div class="item--text">
            <div class="text">
                <h2>{l s='Something went wrong!' mod='paylands'}</h2>
                <p>{l s='We apologize the transaction was not success, please contact to store\'s administrator to get more details' mod='paylands'}</p>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    setTimeout(function(){
        window.location.href = '{$url_redirect|escape:'htmlall':'UTF-8'}';
    }, 5000)

</script>
</body></html>
