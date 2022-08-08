---
@route: /blog/[:slug]
@layout: /default
@collection:
    extend: /blog

metadata:
    'og:type': article
state:
    visible: false
---

<?= partial('/articles/single.html', [
    'article' => collection(),
]); ?>