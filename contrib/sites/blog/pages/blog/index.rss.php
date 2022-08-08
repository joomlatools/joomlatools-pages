---
@collection:
    extend: /blog
    state:
        limit: 10

title: The blog
summary:  Description for the blog
state:
    visible: false
---

<?= import('com:pages.collection.newsfeed.rss'); ?>