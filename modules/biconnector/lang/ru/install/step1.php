<?
$MESS["BICONNECTOR_INSTALL"] = "Установка модуля BI connector.";
$MESS["BICONNECTOR_CONNECTION_NOTE"] = "
<p>Не найдено совместимых с модулем подключений к базе данных в файле bitrix/.settings.php.</p>
<p>Будет использоваться подключение по умолчанию, что может быть не эффективно с точки зрения производительности.<p>
<p>Для оптимальной настройки отредактируйте файл bitrix/.settings.php:</p>
<ul>
<li>Из ключа 'connections' скопируйте ключ 'default' в ключ с новым именем (например: 'biconnector');
<li>Замените значение ключа 'className' на '\Bitrix\BIConnector\DB\MysqliConnection';
<li>При необходимости добавьте подключение файла для донастройки подключения 'include_after_connected'.
</ul>
<p>Результат может выглядеть примерно так:</p>
<pre>
  'connections' =>
  array (
    'value' =>
    array (
      'default' =>
      array (
        'className' => '\Bitrix\Main\DB\MysqliConnection',
        'host' => 'localhost',
        'database' => 'sitemanager',
        'login' => 'user',
        'password' => 'password',
        'options' => 2,
        'include_after_connected' => \$_SERVER['DOCUMENT_ROOT'] . '/bitrix/' . 'php_interface/after_connect.php',
      ),
      'biconnector' =>
      array (
        'className' => '\Bitrix\BIConnector\DB\MysqliConnection',
        'host' => 'localhost',
        'database' => 'sitemanager',
        'login' => 'user',
        'password' => 'password',
        'options' => 2,
        'include_after_connected' => \$_SERVER['DOCUMENT_ROOT'] . '/bitrix/' . 'php_interface/after_connect_bi.php',
      ),
    ),
    'readonly' => true,
  ),
</pre>
<p>Примерное содержимое файла after_connect_bi.php:</p>
<pre>
\$this->queryExecute(\"SET NAMES 'cp1251'\");
\$this->queryExecute(\"SET sql_mode=''\");
</pre>
<p>Обратите внимание на использование '\$this' для донастройки подключения.</p>
<p>Важно! Будьте очень внимательны, т.к. неверная правка может привести к полной неработоспособности сайта.<p>
<p>Желательно выполнять её или через ssh консоль или используя sftp.<p>
";
