<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */
?>

---
# Sentry.io integration
#
# Supports:
#
# - Error Tracking
# - Performance Tracing
#
# For more info see: https://docs.sentry.io/platforms/javascript/

dsn:
version:
release:
tunnel:
tags: []
environment:
tracesSampleRate: 1.0
---

<?
$dsn = $dsn ?? getenv('SENTRY_DSN');
$env = $environment ?? getenv('SENTRY_ENVIRONMENT');
$rel = $release ?? getenv('SENTRY_RELEASE') ?: $this->getObject('com:pages.version')->getVersion();

if(!empty($version)) {
    $version =  '@'.$version;
}

?>

<? if (!empty($dsn)): ?>
<script src="https://unpkg.com/@sentry/tracing<?= $version ?>/build/bundle.tracing.min.js" crossorigin="anonymous" ></script>
<script>
Sentry.init({
    dsn: "<?= $dsn ?>",
    debug: <?= debug() ? 'true' : 'false' ?>,
    tunnel: <?= !empty($tunnel) ? '"'.$tunnel.'"' : 'null' ?>,
    release: <?= !empty($rel) ? '"'.$rel.'"' : 'null' ?>,
    environment: <?= !empty($env) ? '"'.$env.'"' : 'null' ?>,
    tracesSampleRate: <?= $tracesSampleRate ?? 1.0 ?>,
    integrations: [new Sentry.Integrations.BrowserTracing()],
    initialScope: scope => {
        scope.setTags(<?= json($tags ?? []) ?>);
        return scope;
    },
});
</script>
<? endif; ?>