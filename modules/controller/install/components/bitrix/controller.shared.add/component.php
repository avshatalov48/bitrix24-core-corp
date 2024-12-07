<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @var CDatabase $DB */
/** @var CUser $USER */
/** @var CMain $APPLICATION */

//Module
if (!CModule::IncludeModule('controller'))
{
	ShowError(GetMessage('CSA_MODULE_NOT_INSTALLED'));
	return;
}

if (!function_exists('mycopy'))
{
	function mycopy($source, $target, $arExc = [], $arParams = [])
	{
		if (is_dir($source))
		{
			@mkdir($target, octdec($arParams['DIR_PERMISSIONS']));
			@chmod($target, octdec($arParams['DIR_PERMISSIONS']));
			$d = opendir($source);

			while (false !== ($entry = readdir($d)))
			{
				if ($entry == '.' || $entry == '..')
				{
					continue;
				}
				if (in_array($entry, $arExc, true) || in_array($source . '/' . $entry, $arExc, true))
				{
					continue;
				}
				mycopy($source . '/' . $entry, $target . '/' . $entry, $arExc, $arParams);
			}
			closedir($d);
		}
		else
		{
			copy($source, $target);
			@chmod($target, octdec($arParams['FILE_PERMISSIONS']));
		}
	}
}

if (!function_exists('__ConvPathParam'))
{
	function __ConvPathParam($path)
	{
		if (mb_substr($path, 0, 1) === '/' || mb_substr($path, 1, 2) === ':\\')
		{
			$path_vhosts = $path;
		}
		else
		{
			$root = rtrim($_SERVER['DOCUMENT_ROOT'], '\\/');
			$path_vhosts = Rel2Abs($root . '/', $path);
		}
		return $path_vhosts;
	}
}

$URL_SUBDOMAIN = $arParams['URL_SUBDOMAIN'];

$arResult = [];
$arError = [];

if ($_REQUEST['domain_name'] <> '')
{
	set_time_limit(180);
	$site_id = mb_strtolower(mb_substr(preg_replace('/[^a-z0-9]/', '', $_REQUEST['domain_name']), 0, 10));
	$site_url = 'http://' . $site_id . '.' . $URL_SUBDOMAIN . '/';

	if (preg_match('#[^a-z1-9.-]#iu', $_REQUEST['domain_name']))
	{
		$arError[] = GetMessage('CSA_ERROR_DOMAIN_NAME');
	}

	if (!preg_match('#^0[0-7]{3}$#u', $arParams['DIR_PERMISSIONS']))
	{
		$arError[] = GetMessage('CSA_ERROR_DIR_PERMISSIONS');
	}

	if (!preg_match('#^0[0-7]{3}$#u', $arParams['FILE_PERMISSIONS']))
	{
		$arError[] = GetMessage('CSA_ERROR_FILE_PERMISSIONS');
	}

	if (!preg_match('#^\d+M$#iu', $arParams['MEMORY_LIMIT']))
	{
		$arError[] = GetMessage('CSA_ERROR_MEMORY_LIMIT');
	}

	if (!count($arError))
	{
		//check for URL with trailing / and without it
		$db_members = CControllerMember::GetList([], ['=URL' => [$site_url, mb_substr($site_url, 0, -1)]]);
		if ($ar_member = $db_members->Fetch())
		{
			$arError[] = GetMessage('CSA_ERROR_NAME_EXISTS');
		}
	}

	//Create database
	$db_name = 'db_' . str_replace('.', '_', $site_id);
	$mysql_path = __ConvPathParam($arParams['MYSQL_PATH']);
	$mysql_db_path = __ConvPathParam($arParams['MYSQL_DB_PATH']);

	if (!is_file($mysql_path) || !is_executable($mysql_path))
	{
		$arError[] = GetMessage('CSA_ERROR_BAD_MYSQL_PATH');
	}

	if (!is_file($mysql_db_path) || !is_readable($mysql_db_path))
	{
		$arError[] = GetMessage('CSA_ERROR_DB_FILEDUMP');
	}

	$conn = false;
	$helper = false;
	if (!$arError)
	{
		if ($p = mb_strpos($DB->DBHost, ':'))
		{
			$mysql_port = '-P' . mb_substr($DB->DBHost, $p + 1);
			$mysql_host = mb_substr($DB->DBHost, 0, $p);
		}
		else
		{
			$mysql_port = '';
			$mysql_host = $DB->DBHost;
		}

		$config = [
			'host' => $DB->DBHost,
			'login' => $arParams['MYSQL_USER'],
			'password' => $arParams['MYSQL_PASSWORD'],
			'database' => $DB->DBName,
		];
		$conn = new \Bitrix\Main\DB\MysqliConnection($config);
		try
		{
			$conn->connect();
		}
		catch (\Bitrix\Main\DB\ConnectionException $e)
		{
			$arError[] = GetMessage('CSA_ERROR_DB_CONNECT', ['#ERROR#' => (string)$e]);
		}

		if (!$arError)
		{
			$helper = $conn->getSqlHelper();
			try
			{
				$conn->query('CREATE DATABASE /*!32312 IF NOT EXISTS*/ ' . $helper->quote($db_name) . ' /*!40100 DEFAULT CHARACTER SET utf8 */');
			}
			catch (\Bitrix\Main\DB\SqlException $e)
			{
				$arError[] = GetMessage('CSA_ERROR_DB_CREATE', ['#DB_NAME#' => $db_name, '#ERROR#' => (string)$e]);
			}

			if (!$arError)
			{
				$cmd = $mysql_path
					. ' -h ' . escapeshellarg($mysql_host)
					. ' -u ' . escapeshellarg($arParams['MYSQL_USER'])
					. ' ' . $mysql_port
					. ' --password="' . $arParams['MYSQL_PASSWORD'] . '" '
					. escapeshellarg($db_name)
					. ' < ' . $mysql_db_path;
				$result = exec($cmd, $ar);
				if ($result || $ar)
				{
					$arError[] = GetMessage('CSA_ERROR_DB_IMPORT', ['#DB_NAME#' => $db_name]);
				}
			}
		}
	}

	$ID = false;
	//Put site under controller
	if ($conn && !$arError && ($arParams['REGISTER_IMMEDIATE'] == 'N'))
	{
		$ar_member = [
			'URL' => $site_url,
			'SHARED_KERNEL' => 'Y',
			'DISCONNECTED' => 'I',
			'NAME' => mb_substr(mb_substr($site_url, 0, -1), mb_strlen('http://')), // w/o http://
			'ACTIVE' => 'Y',
		];
		if (!CControllerMember::Add($ar_member))
		{
			$arError[] = GetMessage('CSA_ERROR_ADD_SITE');
		}
	}

	if ($conn && !$arError && ($arParams['REGISTER_IMMEDIATE'] != 'N'))
	{
		$ar_member = [
			'MEMBER_ID' => 'm' . \Bitrix\Main\Security\Random::getString(31),
			'SECRET_ID' => 'm' . \Bitrix\Main\Security\Random::getString(31),
			'NAME' => mb_substr($site_url, mb_strlen('http://')), // w/o http://
			'URL' => $site_url,
			'CONTROLLER_GROUP_ID' => COption::GetOptionInt('controller', 'default_group', 1),
			'ACTIVE' => 'Y',
			'SHARED_KERNEL' => 'Y',
			'DISCONNECTED' => 'N',
			'~DATE_CREATE' => CDatabase::CurrentTimeFunction(),
		];

		$dbrCheck = $DB->Query('SELECT \'x\' FROM b_controller_member WHERE MEMBER_ID = \'' . $DB->ForSql($ar_member['MEMBER_ID'], 32) . '\'');
		while ($dbrCheck->Fetch())
		{
			$ar_member['MEMBER_ID'] = 'm' . \Bitrix\Main\Security\Random::getString(31);
			$dbrCheck = $DB->Query('SELECT \'x\' FROM b_controller_member WHERE MEMBER_ID = \'' . $DB->ForSql($ar_member['MEMBER_ID'], 32) . '\'');
		}

		if ($ID = CControllerMember::Add($ar_member))
		{
			$arControllerLog = [
				'NAME' => 'REGISTRATION',
				'CONTROLLER_MEMBER_ID' => $ID,
				'DESCRIPTION' => GetMessage('CSA_LOG_ADD_CLIENT'),
				'STATUS' => 'Y',
			];
			CControllerLog::Add($arControllerLog);
			$conn->selectDatabase($db_name);
			$queries = [
				"delete from b_option where NAME='update_site'",
				"insert into b_option (SITE_ID, MODULE_ID, NAME, VALUE, DESCRIPTION) values (
					NULL,
					'main',
					'update_site',
					'www.1c-bitrix.ru',
					NULL
				)",
				"delete from b_option where NAME='controller_member'",
				"insert into b_option (SITE_ID, MODULE_ID, NAME, VALUE, DESCRIPTION) values (
					NULL,
					'main',
					'controller_member',
					'Y',
					NULL
				)",
				"delete from b_option where NAME='controller_member_id'",
				"insert into b_option (SITE_ID, MODULE_ID, NAME, VALUE, DESCRIPTION) values (
					NULL,
					'main',
					'controller_member_id',
					'" . $helper->forSql($ar_member['MEMBER_ID'], 32) . "',
					NULL
				)",
				"delete from b_option where NAME='controller_member_secret_id'",
				"insert into b_option (SITE_ID, MODULE_ID, NAME, VALUE, DESCRIPTION) values (
					NULL,
					'main',
					'controller_member_secret_id',
					'" . $helper->forSql($ar_member['SECRET_ID'], 32) . "',
					NULL
				)",
				"delete from b_option where NAME='controller_url'",
				"insert into b_option (SITE_ID, MODULE_ID, NAME, VALUE, DESCRIPTION) values (
					NULL,
					'main',
					'controller_url',
					'" . $helper->forSql($arParams['CONTROLLER_URL']) . "',
					NULL
				)",
				"delete from b_option where NAME='~controller_disconnect_command'",
				"insert into b_option (SITE_ID, MODULE_ID, NAME, VALUE, DESCRIPTION) values (
					NULL,
					'main',
					'~controller_disconnect_command',
					'
					CControllerClient::RestoreAll();
					COption::SetOptionString(\"main\", \"controller_member\", \"N\");
					COption::SetOptionString(\"main\", \"controller_member_id\", \"\");
					COption::SetOptionString(\"main\", \"controller_url\", \"\");
					UnRegisterModuleDependences(\"main\", \"OnUserLoginExternal\", \"main\", \"CControllerClient\", \"OnExternalLogin\", 1);
					UnRegisterModuleDependences(\"main\", \"OnExternalAuthList\", \"main\", \"CControllerClient\", \"OnExternalAuthList\");
					',
					NULL
				)",
				"insert into b_module_to_module (SORT, FROM_MODULE_ID, MESSAGE_ID, TO_MODULE_ID, TO_CLASS, TO_METHOD, TO_PATH) values (
					1,
					'main',
					'OnUserLoginExternal',
					'main',
					'CControllerClient',
					'OnExternalLogin',
					NULL
				)",
				"insert into b_module_to_module (SORT, FROM_MODULE_ID, MESSAGE_ID, TO_MODULE_ID, TO_CLASS, TO_METHOD, TO_PATH) values (
					100,
					'main',
					'OnExternalAuthList',
					'main',
					'CControllerClient',
					'OnExternalAuthList',
					NULL
				)",
			];
			foreach ($queries as $sql)
			{
				try
				{
					$conn->query($sql);
				}
				catch (\Bitrix\Main\DB\SqlException $e)
				{
					$arError[] = GetMessage('CSA_ERROR_ADD_SITE2', ['#ERROR#' => (string)$e]);
					break;
				}
			}

			if (!$arError)
			{
				// vars $login, $password, $email - read from component params
				$adm_login = '';
				if (isset($arParams['ADMIN_LOGIN']))
				{
					$adm_login = trim($arParams['LOGIN']);
				}
				$adm_email = '';
				if (isset($arParams['ADMIN_EMAIL']))
				{
					$adm_email = trim($arParams['ADMIN_EMAIL']);
				}
				$adm_password = '';
				if (isset($arParams['ADMIN_PASSWORD']))
				{
					$adm_password = trim($arParams['ADMIN_PASSWORD']);
				}
				if (mb_strlen($adm_login) && mb_strlen($adm_email) && mb_strlen($adm_password))
				{
					$result = $conn->query("
						UPDATE b_user
						SET
							LOGIN='" . $helper->forSql($adm_login) . "',
							PASSWORD='" . $helper->forSql($adm_password) . "',
							EMAIL='" . $helper->forSql($adm_email) . "'
						WHERE ID=1
					");
				}
			}
		}
		else
		{
			$arError[] = GetMessage('CSA_ERROR_ADD_SITE');
		}
	}

	$path_vhosts = str_replace('//', '/', __ConvPathParam($arParams['PATH_VHOST']) . '/');
	$path_to = str_replace('//', '/', $path_vhosts . $site_id . '/');
	if (!count($arError))
	{
		//directory creation
		CheckDirPath($path_to);
		@chmod($path_to, octdec($arParams['DIR_PERMISSIONS']));
		if (!is_dir($path_to) || !is_writable($path_to))
		{
			$arError[] = GetMessage('CSA_ERROR_CREATE_DIR');
		}
	}

	$path_public = str_replace('//', '/', __ConvPathParam($arParams['PATH_PUBLIC']) . '/');
	if (!count($arError))
	{
		//copy files
		mycopy($path_public, $path_to, [
			'.db',
			'managed_cache',
			'stack_cache',
			'cache',
			$path_public . 'bitrix'
		], $arParams);
	}

	$path_kernel = $_SERVER['DOCUMENT_ROOT'] . COption::GetOptionString('controller', 'shared_kernel_path', '/bitrix/clients');
	if (!count($arError))
	{
		//create kernel link
		$result = @symlink($path_kernel, $path_to . 'bitrix');
	}

	if (!count($arError))
	{
		// adding dbconn.php
		$filename = str_replace('//', '/', $path_to . 'bitrix_personal/php_interface/dbconn.php');
		$str = '<?' . 'php
$DBType = "mysql";
$DBHost = "' . EscapePHPString($DB->DBHost) . '";
$DBLogin = "' . EscapePHPString($arParams['MYSQL_USER']) . '";
$DBPassword = "' . EscapePHPString($arParams['MYSQL_PASSWORD']) . '";
$DBName = "' . EscapePHPString($db_name) . '";
$DBDebug = true;
$DBDebugToFile = false;
@set_time_limit(60);

define("CACHED_b_file", 3600);
define("CACHED_b_file_bucket_size", 10);

define("BX_FILE_PERMISSIONS", ' . preg_replace('/\D/', '', $arParams['FILE_PERMISSIONS']) . ');
define("BX_DIR_PERMISSIONS", ' . preg_replace('/\D/', '', $arParams['DIR_PERMISSIONS']) . ');
@umask(~BX_DIR_PERMISSIONS);
@ini_set("memory_limit", "' . EscapePHPString($arParams['MEMORY_LIMIT']) . '");
?>
';
		$f = fopen($filename, 'wb');
		if ($f)
		{
			$result = fwrite($f, $str);
			fclose($f);
		}
		else
		{
			$result = 0;
		}
		if ($result !== mb_strlen($str))
		{
			$arError[] = GetMessage('CSA_ERROR_FILE_WRITE', ['#FILE#' => $filename]);
		}
	}

	$apache_root = str_replace('//', '/', __ConvPathParam($arParams['APACHE_ROOT']) . '/');
	if (!is_dir($apache_root) || !is_writable($apache_root))
	{
		$arError[] = GetMessage('CSA_ERROR_NOT_FOUND_APACHE_VHOST_DIR');
	}

	$apache_template = false;
	if (!count($arError))
	{
		$filename = '';
		if ($this->initComponentTemplate())
		{
			$template = &$this->getTemplate();
			if ($template)
			{
				$folderPath = $template->GetFolder();
				if ($folderPath <> '')
				{
					$filename = $_SERVER['DOCUMENT_ROOT'] . $folderPath . '/apache.conf.php';
					if (file_exists($filename))
					{
						$f = fopen($filename, 'rb');
						if ($f)
						{
							$apache_template = fread($f, filesize($filename));
							fclose($f);
							if (mb_strlen($apache_template) != filesize($filename))
							{
								$apache_template = false;
							}
						}
					}
				}
			}
		}
		if (!$apache_template)
		{
			$arError[] = GetMessage('CSA_ERROR_APACHE_TEMPLATE_NOT_FOUND', ['#FILE#' => $filename]);
		}
	}


	if (!count($arError))
	{
		//add apache virtual server
		$filename = str_replace('//', '/', $apache_root . '/999-' . $site_id . '.conf');
		$str = str_replace(
			['#SITE_ID#', '#URL_SUBDOMAIN#', '#DOCUMENT_ROOT#'],
			[$site_id, $arParams['URL_SUBDOMAIN'], $path_to],
			$apache_template
		);

		$f = fopen($filename, 'wb');
		if ($f)
		{
			$result = fwrite($f, $str);
			fclose($f);
		}
		else
		{
			$result = 0;
		}
		if ($result !== mb_strlen($str))
		{
			$arError[] = GetMessage('CSA_ERROR_WRITE_APACHE_CONFIG');
		}
	}

	$nginx_template = false;
	if (!count($arError))
	{
		$filename = '';
		if ($this->initComponentTemplate())
		{
			$template = &$this->getTemplate();
			if ($template)
			{
				$folderPath = $template->GetFolder();
				if ($folderPath <> '')
				{
					$filename = $_SERVER['DOCUMENT_ROOT'] . $folderPath . '/nginx.conf.php';
					if (file_exists($filename))
					{
						$f = fopen($filename, 'rb');
						if ($f)
						{
							$nginx_template = fread($f, filesize($filename));
							fclose($f);
							if (mb_strlen($nginx_template) != filesize($filename))
							{
								$nginx_template = false;
							}
						}
					}
				}
			}
		}
		if (!$nginx_template)
		{
			$arError[] = GetMessage('CSA_ERROR_NGINX_TEMPLATE_NOT_FOUND', ['#FILE#' => $filename]);
		}
	}


	$nginx_root = str_replace('//', '/', __ConvPathParam($arParams['NGINX_ROOT']) . '/');
	if (!is_dir($nginx_root) || !is_writable($nginx_root))
	{
		$arError[] = GetMessage('CSA_ERROR_NOT_FOUND_NGINX_VHOST_DIR');
	}

	if (!count($arError))
	{
		//add nginx virtual server
		$filename = str_replace('//', '/', $nginx_root . '/999-' . $site_id . '.conf');
		$str = str_replace(
			['#SITE_ID#', '#URL_SUBDOMAIN#', '#DOCUMENT_ROOT#'],
			[$site_id, $arParams['URL_SUBDOMAIN'], $path_to],
			$nginx_template
		);

		$f = fopen($filename, 'wb');
		if ($f)
		{
			$result = fwrite($f, $str);
			fclose($f);
		}
		else
		{
			$result = 0;
		}
		if ($result !== mb_strlen($str))
		{
			$arError[] = GetMessage('CSA_ERROR_WRITE_NGINX_CONFIG');
		}
	}

	if (!count($arError))
	{
		// restart apache
		$f = fopen($arParams['RELOAD_FILE'], 'wb');
		fwrite($f, 'do it!');
		fclose($f);

		do
		{
			sleep(3);
		}
		while (file_exists($arParams['RELOAD_FILE']));

		if ($ID)
		{
			CControllerMember::SetGroupSettings($ID);
		}

		LocalRedirect($site_url);
		$arResult['NEW_URL'] = $site_id . '.' . $URL_SUBDOMAIN;
	}
}

$arResult['ERROR_MESSAGE'] = implode('<br>', $arError);

//Set Title
$arParams['SET_TITLE'] = ($arParams['SET_TITLE'] == 'N' ? 'N' : 'Y' );
if ($arParams['SET_TITLE'] == 'Y')
{
	$APPLICATION->SetTitle(GetMessage('CSA_TITLE'));
}

$this->includeComponentTemplate();
