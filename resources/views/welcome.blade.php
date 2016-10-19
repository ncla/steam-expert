<html>
<head>
    <title>STEAM.EXPERT</title>

    <script type="application/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.3/jquery.min.js"></script>
    <script type="application/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mb.YTPlayer/3.0.0/jquery.mb.YTPlayer.min.js"></script>
    <link href='//fonts.googleapis.com/css?family=Lato:100' rel='stylesheet' type='text/css'>

    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            color: #B0BEC5;
            display: table;
            font-weight: 100;
            font-family: 'Lato';
            background: black;
        }

        .container {
            text-align: center;
            display: table-cell;
            vertical-align: middle;
        }

        .content {
            text-align: center;
            display: inline-block;
        }

        .title {
            font-size: 96px;
            margin-bottom: 40px;
        }

        .quote {
            font-size: 24px;
        }
    </style>
</head>
<body>
<div class="player" data-property="{videoURL:'https://youtu.be/Hm2DjfFLE-M', mute:false, loop:false}">></div>
<div class="container">
    <div class="content">
        <div class="title">STEAMEXPERT</div>
        <div class="quote"></div>
    </div>
</div>
</body>
<script type="application/javascript">
    var videos = [
        {videoURL: 'IPBDxK0eqk4', mute: {{ (int) app()->environment('dev') }}, loop: false, stopMovieOnBlur: false},
        {videoURL: 'STiFPbPrblY', mute: {{ (int) app()->environment('dev') }}, loop: false, stopMovieOnBlur: false}
    ];
    $(function() {
        $(".player").YTPlaylist(videos, false);
    });
</script>
</html>
