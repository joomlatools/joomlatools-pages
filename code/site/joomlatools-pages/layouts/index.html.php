<!DOCTYPE html>
<html xmlns:og="http://opengraphprotocol.org/schema/" class="no-js" lang="<?= language() ?>" dir="<?= direction() ?>" vocab="http://schema.org/">
<head>
    <meta charset="utf-8"/>
    <base href="<?= url(); ?>" />

    <ktml:title>
    <ktml:meta>
    <ktml:link>
    <ktml:style>
    <ktml:script>

    <title><?= title() ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="mobile-web-app-capable  " content="yes"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="apple-mobile-web-app-title" content="<?= config()->site->name ?>"/>
        
    <link href="theme://images/logo/favicon.ico" rel="shortcut icon" type="image/ico" />
    <link href="theme://images/logo/favicon.png" rel="icon" type="image/png" />
    <link href="theme://images/logo/xtouch-icon.png" rel="apple-touch-icon" />
    <link href="theme://images/logo/manifest.json" rel="manifest" />
    <link href="theme://images/logo/safari-pinned-tab.svg" rel="mask-icon" />

    <meta name="msapplication-TileColor" content="<?= config()->site->main_color ?>b"/>
    <meta name="msapplication-config" content="theme://images/browserconfig.xml"/>
    <meta name="theme-color" content="<?= config()->site->main_color ?>"/>
    <meta name="google-site-verification" content="<?= config()->ga_code ?>" />

    <link href="<?= config()->site->logo ?>" rel="preload" as="image" />
    <ktml:style src="theme://css/output.css" rel="preload" as="style"/>
    <ktml:style src="theme://css/custom.css" rel="preload" as="style"/>
    <ktml:script src="media://com_pages/js/prefetcher-v1.0.0.min.js" defer="defer" />

    <? if (config()->ga_code): ?>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= config()->ga_code ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '<?= config()->ga_code ?>');
    </script>
    <? endif; ?>
</head>

<body class="<?= config()->site->body_class ?>">

<script>function hasClass(e,t){return e.className.match(new RegExp("(\\s|^)"+t+"(\\s|$)"))}var el=document.documentElement;var cl="no-js";if(hasClass(el,cl)){var reg=new RegExp("(\\s|^)"+cl+"(\\s|$)");el.className=el.className.replace(reg,"js ")}</script>

<ktml:content>

<? if(debug()): ?>
   <?= import('/partials/debug/grid.html'); ?>
<? endif; ?>

</body>
</html>