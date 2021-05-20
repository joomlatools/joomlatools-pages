---
layout: default
route: blog/[:slug]
collection:
    extend: blog
metadata:
    'og:type': article
visible: false
---

<?= partial('articles/single.html', [
    'article' => collection(),
]); ?>