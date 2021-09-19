---
@route:
    - /blog.json
    - /blog.csv
@collection:
    extend: /blog
    state:
        limit: 0
    format: [csv]

visible: false
---