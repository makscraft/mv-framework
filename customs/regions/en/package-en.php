<?php
return [
    'hello' => 'Happy coding!',

    'database' => [
        'Pages' => [
            [
                'name' => 'Welcome',
                'url' => 'index',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 1,
                'content' => '<h2>Models</h2>
                              <p>Models are located at /models/ folder<br><a href="https://mv-framework.com/predefined-models" target="_blank">Read more about models</a></p>
                              <h2>Views</h2>
                              <p>Views (templates) are located at /views/ folder<br>
                              <a href="https://mv-framework.com/creating-new-template" target="_blank">Read more about views</a></p>
                              <h2>Routes</h2><p>Routes are listed in /config/routes.php file<br>
                              <a href="https://mv-framework.com/general-template-principles" target="_blank">Read more about routing</a></p>'
            ],
            [
                'name' => '404 not found',
                'url' => 'e404',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 0,
                'order' => 5,
                'content' => '<p>The requested page was not found.</p>'
            ],
            [
                'name' => 'About',
                'url' => 'about',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 2,
                'content' => '<p>Main idea of MV is to provide a simplified and faster way to create websites 
                              and web applications with the help of built-in CMF that allows to manage content with 
                              Admin Panel.</p><ul>
                              <li>Totally object-oriented approach</li>
                              <li>Autoloading of classes of models and plugins</li>
                              <li>Abstraction of database</li>
                              <li>MySQL and SQLite databases support</li>
                              <li>Use of popular PHP patterns (Sigleton, Active Record)</li>
                              <li>Updatable core and admin interface (reverse compatibility)</li></ul>
                              <p>Automatically created the interface for content management. 
                              All active models have their own section in Admin Panel where it is possible to create, edit and delete records.</p>'
            ],
            [
                'name' => 'Documentation and support',
                'url' => 'documentation',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 3,
                'content' => '<p>Documentation and code samples <a href="https://mv-framework.com" target="_blank">https://mv-framework.com</a></p>
                              <p>Feedback form <a href="https://mv-framework.com/feedback" target="_blank">https://mv-framework.com/feedback</a></p>'
            ],
            [
                'name' => 'Form',
                'url' => 'form',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 4,
                'content' => '<p>Example of a form for sending an email message or adding a record into a database.</p>'
            ]
        ]
    ]
];