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
                              <p>Models are located at /models/ folder<br><a href="https://mv-framework.com/general-model-principles" target="_blank">Read more about models</a></p>
                              <h2>Routes</h2>
                              <p>Routes are listed in /config/routes.php file<br>
                              <a href="https://mv-framework.com/general-template-principles" target="_blank">Read more about routing</a></p>
                              <h2>Views</h2>
                              <p>Views (templates) are located at /views/ folder<br>
                              <a href="https://mv-framework.com/creating-new-template" target="_blank">Read more about views</a></p>'
            ],
            [
                'name' => '404 not found',
                'url' => 'e404',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 0,
                'order' => 4,
                'content' => '<p>The requested page was not found.</p>'
            ],
            [
                'name' => 'Documentation and support',
                'url' => 'documentation',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 2,
                'content' => '<h2>Documentation and code samples</h2>
                              <p><a href="https://mv-framework.com" target="_blank">https://mv-framework.com</a></p>
                              <h2>Questions and support</h2>
                              <p><a href="https://mv-framework.com/questions" target="_blank">https://mv-framework.com/questions</a></p>
                              <h2>Feedback form</h2>
                              <p><a href="https://mv-framework.com/feedback" target="_blank">https://mv-framework.com/feedback</a></p>'
            ],
            [
                'name' => 'Form',
                'url' => 'form',
                'parent' => -1,
                'active' => 1,
                'in_menu' => 1,
                'order' => 3,
                'content' => '<p>Example of a form for sending an email message or adding a record into a database.</p>'
            ]
        ]
    ]
];