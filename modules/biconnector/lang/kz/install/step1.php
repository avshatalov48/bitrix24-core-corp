<?php
$MESS["BICONNECTOR_CONNECTION_NOTE"] = "
<p>bitrix/.settings.php файлында модульмен үйлесімді дерекқор қосылымдары табылған жоқ.</p>
<p>Әдепкі қосылым пайдаланылатын болады, бұл өнімділік тұрғысынан тиімді болмауы мүмкін.<p>
<p>Оңтайлы баптау үшін bitrix/.settings.php: файлын редакциялаңыз:</p>
<ul>
<li>'connections' кілтінен 'default' кілтін жаңа атпен кілтке көшіріңіз (мысалы: 'biconnector');
<li>'className' кілт мәнін '\Bitrix\BIConnector\DB\MysqliConnection' ауыстырыңыз;
<li>Қажет болса, 'include_after_connected' қосылымын баптау үшін файл қосылымын қосыңыз.
</ul>
<p>Нәтиже келесідей болуы мүмкін:</p>
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
<p>after_connect_bi.php файлының шамамен мазмұны:</p>
<pre>
\$this->queryExecute(\"SET NAMES 'cp1251'\");
\$this->queryExecute(\"SET NAMES 'cp1251'\");
</pre>
<p>Қосылымды аяқтап баптау үшін '\$this' пайдалануға назар аударыңыз.</p>
<p>Маңызды! Өте мұқият болыңыз, өйткені қате өңдеу сайттың толық жұмыс істемеуіне әкелуі мүмкін.<p>
<p>Оны ssh консолі арқылы немесе sftp көмегімен орындаған жөн.<p>
";
$MESS["BICONNECTOR_INSTALL"] = "BI connector модулін орнату.";
