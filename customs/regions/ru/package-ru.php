<?php
return [
    'hello' => 'Добро пожаловать!',

    'database' => [
        'Pages' => [
            [
                'name' => 'Добро пожаловать',
                'url' => 'index',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 1,
                'content' => '<h2>Модели</h2>
                              <p>Файлы моделей расположены в папке /models/<br>
                              <a href="https://mv-framework.ru/obshchie-printcipy-modeley" target="_blank">Подробнее о моделях</a></p>
                              <h2>Маршрутизация</h2><p>Файл с маршрутами /config/routes.php<br>
                              <a href="https://mv-framework.ru/obshie-principy-shablonov" target="_blank">Подробнее о маршрутизации</a></p>
                              <h2>Шаблоны</h2>
                              <p>Файлы шаблонов расположены в папке /views/<br>
                              <a href="https://mv-framework.ru/sozdanie-novogo-shablona" target="_blank">Подробнее о шаблонах</a></p>'
            ],
            [
                'name' => 'Страница не найдена',
                'url' => 'e404',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 0,
                'order' => 4,
                'content' => '<p>Запрошенная страница не найдена</p>'
            ],
            [
                'name' => 'Документация и поддержка',
                'url' => 'documentation',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 2,
                'content' => '<h2>Документация и примеры кода</h2>
                              <p><a href="https://mv-framework.ru" target="_blank">https://mv-framework.ru</a></p>
                              <h2>Вопросы и поддержка</h2>
                              <p><a href="https://mv-framework.ru/questions" target="_blank">https://mv-framework.ru/questions</a></p>
                              <h2>Обратная связь</h2>
                              <p><a href="https://mv-framework.ru/feedback" target="_blank">https://mv-framework.ru/feedback</a></p>'
            ],
            [
                'name' => 'Форма',
                'url' => 'form',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 3,
                'content' => '<p>Пример формы для отправки email сообщения или занесения записи в базу данных.</p>'
            ]
        ]
    ]
];