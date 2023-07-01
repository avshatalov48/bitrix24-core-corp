<?php
$MESS["BICONNECTOR_CONNECTION_NOTE"] = "
<p>No database connections compatible with this module were found in the file bitrix/.settings.php.</p>
<p>The default connection will be used instead which may affect performance.<p>
<p>You will have to edit the file bitrix/.settings.php to achieve optimum performance.</p>
<ul>
<li>Under the 'connections' key, copy the 'default' key to a new key (for example: 'biconnector').
<li>Set the value of the 'className' key to '\Bitrix\BIConnector\DB\MysqliConnection'.
<li>If required, include a file to provide additional configuration in 'include_after_connected'.
</ul>
<p>Your result may look like this:</p>
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
<p>This is how the additional configuration file after_connect_bi.php may look like:</p>
<pre>
\$this->queryExecute(\"SET NAMES 'utf8'\");
\$this->queryExecute(\"SET sql_mode=''\");
</pre>
<p>Note the use of '\$this' to fine-tune the database connection.</p>
<p>Pay every bit of attention when editing this file. Even a small error may leave your site totally unresponsive.<p>
<p>We recommend that you edit this file using the SSH console or over the SFTP.<p>";
$MESS["BICONNECTOR_INSTALL"] = "BI connector module installation";
