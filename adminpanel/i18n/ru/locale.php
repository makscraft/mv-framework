<? 
//Russian PHP regional translations and standarts for MV framework
$regionalData = array(

	'caption' => 'Русский',

	'date_format' => 'dd.mm.yyyy',

	'plural_rules' => array(

		'one' => '/^(1|[2-9]+1|[1-9][0-9]*[^1]+1)$/',
		'few' => '/^([2-4]|[2-9]+[2-4]|[1-9][0-9]*[^1]+[2-4])$/',
		'many' => '/^(0|[5-9]|1[0-9]|20|[2-9]+[5-9]|[0-9]+0)$/'
	),
	
	'decimal_mark' => ',',
	
	'month' => array('Январь','Февраль','Март','Апрель','Май','Июнь','Июль',
					 'Август','Сентябрь','Октябрь','Ноябрь','Декабрь'),
	
	'month_case' => array('Января','Февраля','Марта','Апреля','Мая','Июня','Июля',
					 	  'Августа','Сентября','Октября','Ноября','Декабря'),
		
	'week_days' => array('Понедельник','Вторник','Среда','Четверг','Пятница','Суббота','Воскресенье'),
	
	'translation' => array(
		
		'mv' => 'MV framework',
		'welcome' => 'Добро пожаловать в административную панель MV',
		'index-users-icon' => 'Управление доступом к административной панели',
		'index-garbage-icon' => 'Восстановление удаленных данных',
		'index-history-icon' => 'История операций администраторов',
		'index-file-manager-icon' => 'Работа с загруженными файлами и папками',
		'admin-panel-skin' => 'Тема оформления',
		'language' => 'Язык',
		'fast-search' => 'Быстрый поиск',
	    'search-in-all-modules' => 'Поиск по всем модулям',
		'yes' => 'да',
		'no' => 'нет',
		'size-kb' => 'КБ',
		'size-mb' => 'МБ',
		'day' => 'День',
		'month' => 'Месяц',
		'year' => 'Год',
		'hours' => 'Часы',
		'minutes' => 'Минуты',
		'seconds' => 'Секунды',
		'date-from' => 'с',
		'date-to' => 'по',		
		'number-from' => 'от',
		'number-to' => 'до',
		'login-action' => 'Войти',
		'create' => 'Создать',
		'update' => 'Обновить',
		'delete' => 'Удалить',
		'add' => 'Добавить',
		'edit' => 'Редактировать',
		'apply-filters' => 'Фильтровать',
		'reset' => 'Сбросить',
		'restore' => 'Восстановить',
		'cancel' => 'Отмена',
		'read' => 'Просмотреть',
		'apply' => 'Применить',
		'copy' => 'Копировать',
		'paste' => 'Вставить',
		'rename' => 'Переименовать',
		'cut' => 'Вырезать',
		'rollback' => 'Откатить',
		'switch-on' => 'Включить',
		'switch-off' => 'Отключить',
		'find' => 'Найти',
		'search' => 'Поиск',
		'show' => 'Показать',
		'hide' => 'Скрыть',
		'back' => 'Назад',
	    'back-to-module' => 'Назад к модулю',
		'create-and-continue' => 'Сохранить и создать новую запись',
		'update-and-continue' => 'Сохранить и продолжить редактирование',
		'create-and-edit' => 'Сохранить и начать редактирование',
		'save' => 'Сохранить',
		'quick-edit' => 'Быстрое редактирование',
		'empty-recylce-bin' => 'Очистить корзину',
		'filters' => 'Фильтры',
		'manage-filters' => 'Управление фильтрами',
		'file-manager' => 'Файловый менеджер',
		'files-top-menu' => 'Файлы',
		'file-params' => 'Параметры файла',
		'delete-checked' => 'Удалить отмеченные',
		'last-change' => 'Последнее изменение',
		'upload-file' => 'Загрузить файл',
		'upload-image' => 'Загрузить изображение',
		'multiple-upload' => 'Мультизагрузка',
		'upload-many-images' => 'Загрузить несколько изображений',
		'stop-upload' => 'Остановить загрузку',
		'about-flash-player' => 'Для загрузки изображений необходимо наличие в браузере Adobe Flash Player',
		'create-folder' => 'Создать папку',
		'upload' => 'Загрузить',
		'upper' => 'Выше',
		'add-edit-comment' => 'Добавить / редактировать комментарий',
		'move-left' => 'Переместить влево',
		'move-right' => 'Переместить вправо',
		'move-first' => 'Переместить в начало',
		'move-last' => 'Переместить в конец',
		'move-up' => 'Переместить вверх',
		'move-down' => 'Переместить вниз',
		'move-selected' => 'Выбрать',
		'select-value' => 'Выберите значение',
		'move-not-selected' => 'Убрать из выбранных',
		'view-download' => 'Скачать / просмотреть',
		'search-by-name' => 'Искать по названию',
		'versions-history' => 'История изменений',
		'versions-limit' => 'Количество хранимых версий',
		'versions-disabled' => 'Сохранение истории изменений отключено.',
		'versions-history-new' => 'После создания новой записи здесь будет отображаться история изменений.',
		'simple-module' => 'Простой модуль',
		'root-catalog' => 'Корневой раздел',
		'empty-list' => 'Список пуст',
		'done-create' => 'Новая запись была успешно добавлена.',
		'done-update' => 'Изменения были успешно сохранены.',
		'done-delete' => 'Удаление было успешно произведено.',
		'done-restore' => 'Восстановление было успешно произведено.',
		'done-operation' => 'Операция была успешно произведена.',
		'created-now-edit' => 'Новая запись была создана и готова для редактирования.',
		'upload-file-error' => 'Файл не был загружен. Ошибка передачи данных либо файл имеет слишком большой размер.',
		'no-images' => 'Изображения отсутствуют',
		'no-image' => 'Изображение отсутствует',
		'pager-limit' => 'Записей на странице',
		'page' => 'Страница',
		'date' => 'Дата',
		'create-record' => 'Создание новой записи',
		'update-record' => 'Редактирование записи',
		'with-selected' => 'С отмеченными',
		'change-param' => 'Изменить параметр',
		'add-param' => 'Добавить параметр',
		'remove-param' => 'Удалить параметр',		
		'version-set-back' => 'Сделать данную версию текущей',
		'versions-no-yet' => 'На данный момент предыдущие версии записи отсутствуют.',
		'not-defined' => 'Не задан',
		'has-value' => 'Есть (заполнено)',
		'has-no-value' => 'Нет (не заполнено)',
		'all-catalogs' => 'Все разделы',
		'in-all-catalogs' => 'Во всех разделах',
		'name' => 'Название',
		'garbage' => 'Корзина',
		'module' => 'Модуль',
		'date' => 'Дата',
		'creating' => 'Создание',
		'editing' => 'Редактирование',
		'deleting' => 'Удаление',
		'restoring' => 'Восстановление',
		'record' => 'Запись',
		'user' => 'Администратор',
		'operation' => 'Операция',
		'operations' => 'Операции',
		'users-operations' => 'История операций администраторов',
		'display-fields' => 'Отображаемые поля',
		'my-last-actions' => 'Мои последние операции',
		'other-users-actions' => 'Операции других администраторов',
		'send-user-info' => 'Отправить администратору информацию об учетной записи',
		'user-data' => 'Данные администратора',
		'no-changes' => 'Без изменений',
		'results-found' => 'Найдено результатов',
		'logs' => 'История',
		'modules' => 'Модули',
		'my-settings' => 'Мои настройки',
		'to-site' => 'Сайт',
		'exit' => 'Выход',
		'filtration-applied' => 'Применена фильтрация',
		'version-loaded' => 'Загружена версия ',
		'size' => 'Размер',
		'width' => 'Ширина',
		'height' => 'Высота',
		'selected' => 'Выбранные',
		'not-selected' => 'Не выбранные',
		'see-all' => 'Посмотреть все',
		'users' => 'Администраторы',
		'name-person' => 'Имя',
		'active' => 'Активен',
		'child-records' => 'Дочерние разделы',
		'email' => 'E-mail',
		'date-registered' => 'Дата регистрации',
		'date-last-visit' => 'Дата последнего посещения',
		'password' => 'Пароль',
		'password-repeat' => 'Повтор пароля',
		'new-password' => 'Новый пароль',
		'users-rights' => 'Права администратора',
		'root-rights' => 'Данный администратор обладает полными правами на все модули.',
		'login' => 'Логин',
		'authorization' => 'Авторизация',
		'fogot-password' => 'Забыли пароль?',
		'password-restore' => 'Восстановление пароля',
		'remember-me' => 'Запомнить меня на этом компьютере',
		'caution' => 'Предупреждение',
		'to-authorization-page' => 'На страницу авторизации',
		'get-ready' => 'Подготовка к работе',
		'captcha' => 'Код безопасности',
		'complete-login' => 'Введите логин',
		'complete-password' => 'Введите пароль',
		'complete-captcha' => 'Введите код безопасности',
		'complete-email' => 'Введите e-mail адрес',
		'wrong-captcha' => 'Код безопасности введен не верно',
		'login-failed' => 'Неверный логин или пароль',
		'not-user-email' => 'Аадминистратор с данным e-mail адресом не найден',
		'password-confirmed' => 'Новый пароль был успешно подтвержден',
		'password-not-confirmed' => 'Пароль не был подтвержден по причине длительного времени ожидания, либо он уже был подтвержден ранее',
		'bad-extetsion' => 'Операция не была произведена. Недопустимое расширение у файла.',
		'forbidden-directory' => 'Данная директория запрещена для просмотра.',
	
		'warning-development-mode' => 'Сайт работает в режиме отладки. Необходимо поставить значение "production" для опции "APP_ENV" в файле .env',
		'warning-root-password' => 'Необходимо сменить исходный пароль пользователя root.',
		'warning-logs-folder' => 'Необходимо установить права на запись в папку log.',
		'warning-userfiles-folder' => 'Необходимо установить права на запись в папку userfiles и все вложенные папки.',
		'warning-dangerous-code' => 'Потенциально вредоносный код в файле:',
	
		'error-failed' => "Операция не была произведена.",
		'error-wrong-token' => "Неверный проверочный ключ. Попробуйте еще раз.",
		'error-wrong-ajax-token' => "Неверный проверочный ключ. Перезагрузите страницу.",
		'error-undefined-value' => "Поле '{field}' имеет недопустимое значение.",
		'error-must-match' => "Значения полей '{field_1}' и '{field_2}' должны совпадать.",
		'error-required' => "Поле '{field}' обязательно для заполнения.",
		'error-required-bool' => "Необходимо отметить поле '{field}'.",
		'error-required-enum' => "Необходимо выбрать значение поля '{field}'.",
		'error-required-file' => "Необходимо выбрать файл для поля '{field}'.",
		'error-required-image' => "Необходимо выбрать изображение для поля '{field}'.",
		'error-required-multi-images' => "Необходимо выбрать одно или несколько изображений для поля '{field}'.",
		'error-email-format' => "Неверный формат поля '{field}'. Введите e-mail адрес в формате name@domain.zone",
		'error-not-int' => "Поле '{field}' должно содержать целое число.",
		'error-not-float' => "Поле '{field}' должно содержать вещественное число.",
		'error-date-format' => "Дата в поле '{field}' должна быть в формате '{date_format}'.",
		'error-date-values' => "Для поля '{field}' необходимо выбрать день, месяц и год.",
		'error-date-time-format' => "Дата и время в поле '{field}' должны быть в формате '{date_time_format}'.",
		'error-date-time-values' => "Для поля '{field}' необходимо выбрать день, месяц, год, часы и минуты.",
		'error-short-password' => "Поле '{field}' должно содержать не менее чем {min_length} [symbol].",
		'error-regexp' => "Поле '{field}' не соответствует нужному формату.",
		'error-length' => "Длина поля '{field}' должна составлять {length} [symbol].",
		'error-min-length' => "Минимальная длина поля '{field}' должна составлять {min_length} [symbol].",
		'error-max-length' => "Максимальная длина поля '{field}' должна составлять {max_length} [symbol].",
		'error-unique-value' => "Введенное значение поля '{field}' уже используется в другой записи и не является уникальным.",
		'error-unique-restore' => "Значение поля '{field}' в восстанавливаемой записи уже используется в другой записи и не является уникальным.",
		'error-zero-forbidden' => "Значение поля '{field}' не может быть нулевым.",
		'error-not-positive' => "Значение поля '{field}' должно быть положительным числом.",
		'error-letters-required' => "Поле '{field}' должно содержать буквы.",
		'error-digits-required' => "Поле '{field}' должно содержать цифры.",
		'error-phone-format' => "Значение поля '{field}' может содержать только цифры, а также знаки (,),-, и +.",
		'error-redirect-format' => "Значение поля '{field}' должно содержать URL адрес в формате 'http://example.com'.",
		'error-url-format' => "Значение поля '{field}' может содержать только латинские буквы, цифры и символ '-'. Буквы обязательно должны присутствовать.",
		'no-delete-parent' => 'Удаление не было произведено. Данная запись имеет дочерние записи, которые должны быть удалены или перенесены заранее.',
		'no-delete-model' => "Удаление не было произведено. Данная запись имеет дочерние записи в модуле '{module}', которые должны быть удалены или перенесены заранее.",
	    'no-delete-root' => "Главный администратор не может быть уделен.",
	    'user-account-created' => 'для вас была создана учетная запись в административной панеле.',
		'user-account-updated' => 'ваша учетная запись в административной панеле была обновлена.',
		'number-records' => '{number} [records]',
		'number-for-records' => '{number} [for-record]',
		'found-records' => '[found] {number} [records]',
		'no-records-found' => 'Записей не найдено',
		'affected-records' => '{ids_number} [records]',
		'number-files' => '{number} [files]',
		'error-ie' => 'Вы используете устаревшую версию браузера Internet Explorer. Для продолжения работы вам необходимо обновить браузер до версии 8+ или воспользоваться другим браузером.',
		'error-js' => 'В вашем браузере отключен JavaScript. Для продолжения работы вам необходимо включить JavaScript в настройках браузера или воспользоваться другим браузером.',
		'error-occured' => 'Произошла ошибка. Работа приостановлена.',
		'change-password' => 'вы запросили восстановление пароля для доступа в административную панель.',
		'change-password-sent' => 'Инструкции для восстановления пароля были высланы на указанный email адрес.',
		'change-password-ok' => 'Новый пароль был выслан на указанный e-mail адрес и его необходимо подтвердить в течение {number} [in-hour].',
		'confirm-time' => 'Новый пароль необходимо подтвердить в течение {number} [in-hour], для этого перейдите по ссылке ниже.',
		'error-no-rights' => 'У вас недостаточно прав для осуществления данной операции либо отсутствует доступ к данному модулю.',
		'error-params-needed' => 'Недостаточно параметров для отображения страницы.',
		'error-wrong-record' => 'Запрошенная запись не найдена.',
		'error-page-not-found' => 'Запрошенная страница не найдена.',
		'folder-exists' => 'Папка с данным названием уже существует.',
		'file-exists' => 'Файл с данным названием уже существует.',
		'folder-not-created' => 'Папка не была создана. Возможно название папки содержит недопустимые символы.',
		'folder-created' => 'Новая папка была успешно создана.',
		'file-uploaded' => 'Файл был успешно загружен.',
		'not-deleted' => 'Удаление не было произведено.',
		'bad-folder-name' => "Название папки сожержит недопустимые символы. Разрешены только латинские буквы, цифры, а также символы '-' и '_'.",
		'bad-file-name' => "Название файла содержет недопустимые символы. Разрешены только латинские буквы, цифры, а также символы '.', '-' и '_'.",
		
		'passwords-must-match' => 'Пароли должны совпадать.',
		'wrong-images-type' => 'Загрузка изображений данного формата запрещена. Разрешенные форматы: {formats}.',
		'wrong-filemanager-type' => "Загрузка файлов данного типа запрещена.",
		'wrong-files-type' => "Загрузка файлов данного типа для поля '{field}' запрещена.",
		'wrong-file-type' => "Загрузка файлов данного типа для поля '{field}' запрещена. Разрешенные форматы: {formats}.",
		'too-heavy-file' => 'Загружаемый файл имеет слишком большой размер. Максимальный размер составляет {weight}.',
		'too-heavy-image' => "Изображение, загружаемое в поле '{field}', имеет слишком большой размер. Максимальный размер составляет {weight}.",
		'too-large-image' => "Изображение, загружаемое в поле '{field}', имеет слишком большие размеры. Максимальные размеры составляют {size} пикселей.",
		'too-heavy-image-editor' => "Загружаемое изображение, имеет слишком большой размер. Максимальный размер составляет {weight}.",
		'too-large-image-editor' => "Загружаемое изображение, имеет слишком большие размеры. Максимальные размеры составляют {size} пикселей.",
	
		'symbol' => array('one' => 'символ', 'few' => 'символа', 'many' => 'символов', 'other' => 'символов'),	
		'field-signs' => 'Поле должно содержать {number} [sign]',
		'sign' => array('one' => 'знак', 'few' => 'знака', 'many' => 'знаков', 'other' => 'знаков'),
		'for-record' => array('one' => 'записи', 'other' => 'записей'),
		'records' => array('one' => 'запись', 'few' => 'записи', 'many' => 'записей', 'other' => 'записей'),
		'in-hour' => array('one' => 'часа', 'other' => 'часов'),
		'files' => array('one' => 'файл', 'few' => 'файла', 'other' => 'файлов'),
	    'found' => array('one' => 'найдена', 'few' => 'найдено', 'other' => 'найдено'),
		'comments' => array('one' => 'комментарий', 'few' => 'комментария', 'many' => 'комментариев', 'other' => 'комментариев'),
	
		'export-csv' => 'Экспорт CSV',
		'import-csv' => 'Импорт CSV',
		'choose-fields-export-csv' => 'Выберите поля для экспорта и поставьте их в нужной последовательности.',
		'column-separator' => 'Разделитель колонок',
		'comma' => 'Запятая',
		'semicolon' => 'Точка с запятой',
		'tabulation' => 'Табуляция',
		'file-encoding' => 'Кодировка файла',
		'first-line-headers' => 'Заголовки в первой строке',
		'download-file' => 'Скачать файл',
		'choose-fields-import-csv' => 'Выберите поля для импорта в порядке следования колонок в csv файле.',
		'file-csv' => 'Файл формата csv',
		'update-order' => 'Порядок загрузки',
		'update-and-create' => 'Обновлять и создавать записи',
	    'update-only' => 'Только обновлять записи',
		'create-only' => 'Только создавать записи',
		'error-not-all-params' => 'Недостаточно параметров для обновления.',	
		'error-wrong-csv-file' => 'Загружен неверный файл для обновления.',
		'update-was-sucessfull' => 'Загрузка была успешно произведена.',
		'update-was-failed' => 'Обновление не был произведено.',
		'created-records' => 'Создано записей',
		'updated-records' => 'Обновлено записей',
		'declined-strings' => 'Номера отклоненных строк',
		'declined-ids' => 'Номера отклоненных id',
		'maximum-files-one-time' => 'Максимум {number} файлов за раз',
			
		'delete-one' => "Удалить запись '{name}'?",
		'delete-many' => "Удалить {number_records}?",
		'delete-one-finally' => "Удалить запись '{name}' без возможности восстановления?",
		'delete-many-finally' => "Удалить {number_records} без возможности восстановления?",
		'restore-one' => "Восстановить запись '{name}'?",
		'restore-many' => "Восстановить {number_records}?",
		'update-many-bool' => "Задать параметр '{caption}' равным '{value}' для {number_records}?",
		'update-many-enum' => "Изменить параметр '{caption}' для {number_records}?",
		'update-many-m2m-add' => "Добавить параметр '{caption}' для {number_records}?",
		'update-many-m2m-remove' => "Удалить параметр '{caption}' для {number_records}?",
		'sort-by-column' => "Для изменения порядка элементов таблица должна быть отсортированна по данному полю.",
		'all-parents-filter' => "Для изменения порядка элементов необходимо снять фильтр по полю '{field}'.",
		'parent-filter-needed' => "Для изменения порядка элементов необходимо задать фильтр по полю '{field}'.",
		'error-data-transfer' => "Ошибка передачи данных.",
		'no-rights' => "У вас недостаточно прав для осуществления данной операции.",
		'delete-files' => "Удалить {number_files}?",
		'delete-file' => "Удалить файл '{name}'?",
		'delete-folder' => "Удалить папку '{name}'?",
		'rename-file' => "Переименовать файл '{name}?'",
		'rename-folder' => "Переименовать папку '{name}?'",
		'add-image-comment' => "Добавить / редактировать комментарий к изображениию",
		'not-uploaded-files' => "Не загруженные файлы",
		'select-fields' => "Необходимо выбрать нужные поля.",
		'select-csv-file' => "Необходимо выбрать файл в формате csv.",
		'quick-edit-limit' => "При текущей конфигурации столбцов таблицы лимит записей на странице не должен превышать {number}.",
		'choose-skin' => "Выберите тему оформления административной панели."
	)
);
?>