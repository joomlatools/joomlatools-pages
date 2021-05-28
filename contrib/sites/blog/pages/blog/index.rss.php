---
@collection:
    extend: /blog
    state:
        limit: 10

title: The blog
summary:  Description for the blog
visible: false
---

<?= import('com:pages.collection.newsfeed.rss'); ?>