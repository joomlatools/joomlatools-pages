---
layout: default
route: blog/[:slug]
collection:
    extend: blog
metadata:
    'og:type': article
visible: false
---

<?= import('/partials/articles/single.html', [
    'article' => collection(),
]); ?>