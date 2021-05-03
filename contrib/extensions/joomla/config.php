<?php
return [
    'page.registry' => [
        'collections' => [
            'articles' => ['model' => 'ext:joomla.model.articles'],
            'menus'    => ['model' => 'ext:joomla.model.menus']
        ]
    ],
    'event.subscriber.factory' => [
        'subscribers' => [
            'ext:joomla.event.subscriber.pagedecorator',
        ]
    ],
];