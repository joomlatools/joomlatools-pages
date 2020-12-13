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


        <link rel="preconnect" href="https://unpkg.com/"  />
        <ktml:style src="https://unpkg.com/reveal.js@4.0.2/dist/reveal.css" rel="preload" as="style"/>
        <ktml:style src="https://unpkg.com/reveal.js@4.0.2/plugin/highlight/monokai.css" rel="preload" as="style"/>

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
        <script src="https://unpkg.com/reveal.js@4.0.2/dist/reveal.js"></script>
        <script src="https://unpkg.com/reveal.js@4.0.2/plugin/notes/notes.js"></script>
        <script src="https://unpkg.com/reveal.js@4.0.2/plugin/highlight/highlight.js"></script>
        <script>
            // See https://revealjs.com/config/ for a full list of configuration options
            Reveal.initialize({
                center: true,
                history: true,
                plugins: [ RevealHighlight, RevealNotes ]
            });
        </script>

    </body>
</html>