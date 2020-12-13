<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8"/>
        <base href="<?= url(); ?>" />

        <ktml:title>
        <ktml:meta>
        <ktml:link>
        <ktml:style>

        <title><?= title() ?></title>

        <ktml:style src="theme://css/reveal.css" rel="preload" as="style"/>
        <link rel="stylesheet" href="theme://js/reveal/plugin/highlight/monokai.css">
        <ktml:style src="theme://css/fonts.css" media="print" onload="this.media='all'; this.onload=null;" />
        <ktml:style src="theme://css/output.min.css" rel="preload" as="style" />
        
        <style type="text/css" media="screen">
            .slides section.has-dark-background,
            .slides section.has-dark-background h3 {
                color: #fff;
            }
            .slides section.has-light-background,
            .slides section.has-light-background h3 {
                color: #222;
            }
        </style>
    </head>

    <body>

        <ktml:content>
        
        <ktml:script>
        <script src="theme://js/reveal/reveal.js"></script>
        <script src="theme://js/reveal/plugin/notes/notes.js"></script>
        <script src="theme://js/reveal/plugin/highlight/highlight.js"></script>
        <script>
            // See https://github.com/hakimel/reveal.js#configuration for a full list of configuration options
            Reveal.initialize({
                center: true,
                history: true,
                plugins: [ RevealHighlight, RevealNotes ]
            });
        </script>

    </body>
</html>