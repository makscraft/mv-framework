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
                              <a href="https://mv-framework.ru/predustanovlennye-modeli" target="_blank">Подробнее о моделях</a></p>
                              <h2>Шаблоны</h2>
                              <p>Файлы шаблонов расположены в папке /views/<br>
                              <a href="https://mv-framework.ru/sozdanie-novogo-shablona" target="_blank">Подробнее о шаблонах</a></p>
                              <h2>Маршрутизация</h2><p>Файл с маршрутами /config/routes.php<br>
                              <a href="https://mv-framework.ru/obshie-principy-shablonov" target="_blank">Подробнее о маршрутизации</a></p>'
            ],
            [
                'name' => 'Страница не найдена',
                'url' => 'e404',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 0,
                'order' => 5,
                'content' => '<p>Запрошенная страница не найдена на сайте</p>'
            ],
            [
                'name' => 'О проекте',
                'url' => 'about',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 2,
                'content' => '<p>Основная идея MV framework - упростить и ускорить создание сайтов и веб-приложений при 
                              помощи встроенного CMF, позволяющего управлять контентом через панель администратора.</p>
                              <ul><li>полностью объектно-ориентированный подход</li>
                              <li>автозагрузка классов моделей и плагинов</li>
                              <li>абстракция базы данных</li>
                              <li>возможность использования разных СУБД (MySQL и SQLite)</li>
                              <li>применение популярных PHP паттернов (Sigleton, Active Record)</li>
                              <li>обновляемое ядро и административный интерфейс (обратная совместимость)</li></ul>
                              <p>Административная панель автоматически создает интерфейс для управления моделями.
                              Все активные модели имеют свой раздел в административной панеле, через который можно создавать, редактировать и удалять записи.</p>'
            ],
            [
                'name' => 'Документация и поддержка',
                'url' => 'documentation',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 3,
                'content' => '<p>Документация и примеры кода <a href="https://mv-framework.ru" target="_blank">https://mv-framework.ru</a></p>
                              <p><a href="https://mv-framework.ru" target="_blank"></a>Вопросы и ответы <a href="https://mv-framework.ru/questions" target="_blank">https://mv-framework.ru/questions/</a></p><p><a href="https://mv-framework.ru/questions" target="_blank"></a>Обратная связь <a href="https://mv-framework.ru/feedback" target="_blank">https://mv-framework.ru/feedback/</a></p>'
            ],
            [
                'name' => 'Форма',
                'url' => 'form',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 4,
                'content' => '<p>Пример формы для отправки email сообщения или занесения записи в базу данных.</p>'
            ]
        ]
    ]
];