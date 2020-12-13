<? if($display_time): ?>
<? //Inspired by: https://medium.com/@jmperezperez/displaying-page-load-metrics-on-your-site-2e13f63164eb ?>

<script>
window.addEventListener('load', () =>
{
  setTimeout(() =>
  {
    const timing = window.performance && performance.timing
    const round2 = num => Math.round(num * 100) / 100

    if (timing)
    {
        const elm  = document.querySelector('#timing')
        const time = round2((timing.loadEventEnd - timing.navigationStart) / 1000)
        elm.innerHTML = time;
    }

  }, 0)
})
</script>
<? endif; ?>

<span class="inline">
<a href="https://www.joomlatools.com" title="Site by Joomlatools.com" class="inline">
<svg class="w-<?= $icon_size ?> h-<?= $icon_size ?>" viewBox="0 0 32 30.3303"><style>.jt-blue{fill:var(--jt-blue);}.jt-black{fill:var(--jt-black);}</style><g><path class="jt-black" d="M30.3747,14.26A12.1484,12.1484,0,0,0,13.7805,9.8135,2.3155,2.3155,0,1,0,16.096,13.824a7.5168,7.5168,0,0,1,7.5165,13.02,2.3158,2.3158,0,0,0,2.3158,4.0111A12.154,12.154,0,0,0,30.3747,14.26Z" transform="translate(0 -0.8348)"/><path class="jt-black" d="M24.2974,18.7688a2.3157,2.3157,0,0,0-4.631.0366v.0374a7.5181,7.5181,0,1,1-15.0347.047l0-.03A2.3155,2.3155,0,0,0,.0012,18.832a12.1491,12.1491,0,1,0,24.2974.1332C24.2983,18.8994,24.298,18.8343,24.2974,18.7688Z" transform="translate(0 -0.8348)"/></g><path id="accent" class="jt-blue" d="M24.5575,4.5516a2.3153,2.3153,0,0,0-1.1263-1.9888A12.1492,12.1492,0,1,0,11.2275,23.5715a2.3153,2.3153,0,0,0,2.2658-4.0385A7.518,7.518,0,1,1,21.0512,6.5362a2.3165,2.3165,0,0,0,3.5063-1.9846Z" transform="translate(0 -0.8348)"/></svg>
</a>
<? if($display_time): ?>
<span class="text-gray-500 dark:text-gray-900 text-xs">Loaded in <span id="timing"></span>s</span>
<? endif; ?>
</span>