<?php

use Bitrix\BIConnector;
use Bitrix\Bitrix24;
use Bitrix\Main;
use Bitrix\Main\Web\Json;

define('NOT_CHECK_PERMISSIONS', true);
define('NO_KEEP_STATISTIC', true);
define('BX_SECURITY_SESSION_VIRTUAL', true);
define('SKIP_DISK_QUOTA_CHECK', true);
define('CACHED_b_file', false);
define('BX_PUBLIC_TOOLS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

/** @var CUser $USER */
if (!$USER->IsAuthorized())
{
	@session_destroy();
}

@set_time_limit(0);
while (ob_get_length() !== false)
{
	ob_end_clean();
}
header('Content-Type:application/json; charset=UTF-8');

$inputJSON = file_get_contents('php://input');
$input = $inputJSON ? Json::decode($inputJSON) : [];

if (!Main\Loader::includeModule('biconnector'))
{
	echo Json::encode(['error' => 'NO_MODULE']);
	echo "\n";
	Main\Application::getInstance()->terminate();
}

if (isset($_GET['superset_verify']))
{
	if (BIConnector\Superset\Config\ConfigContainer::getConfigContainer()->isPortalIdVerified())
	{
		echo Json::encode(['error' => 'ALREADY_VERIFIED']);
		echo "\n";
		Main\Application::getInstance()->terminate();
	}

	if (!isset($input['message']))
	{
		echo Json::encode(['error' => 'NO_MESSAGE']);
		echo "\n";
		Main\Application::getInstance()->terminate();
	}

	$message = $input['message'];
	$secretManager = BIConnector\Superset\Config\SecretManager::getManager();
	$encryptResult = $secretManager->encryptMessage($message);

	$result = [];
	if ($encryptResult->isSuccess())
	{
		$result = $encryptResult->getData();
	}
	else
	{
		$result = ['error' => $encryptResult->getErrors()[0]->getCode()];
	}

	echo Json::encode($result);
	echo "\n";
	Main\Application::getInstance()->terminate();
}

$supersetKey = $input['superset_key'] ?? '';
unset($input['superset_key']);

$biKey = $input['key'] ?? '';
unset($input['key']);
$languageCode = substr($biKey, 32, 2);
$biKey = substr($biKey, 0, 32);

$lockFileName = CTempFile::GetAbsoluteRoot() . '/' . md5($biKey) . '-bi.lock';
$lockFile = fopen($lockFileName, 'w');
$isLocked = $lockFile ? flock($lockFile, LOCK_EX) : false;

$manager = BIConnector\Manager::getInstance();

$service = $manager->createService('bi-ctr');
$service->setLanguage($languageCode);

$limitManager = BIConnector\LimitManager::getInstance();
if ($supersetKey)
{
	$limitManager->setSupersetKey($supersetKey);
}
elseif (
	!Main\Loader::includeModule('bitrix24')
	&& $biKey === BIConnector\Superset\KeyManager::getAccessKey()
)
{
	$limitManager->setIsSuperset();
}

$tableName = isset($_GET['table']) ? (string)$_GET['table'] : null;

if (!$manager->checkAccessKey($biKey))
{
	echo Json::encode(['error' => 'WRONG_KEY']);
}
elseif (!$limitManager->isSuperset())
{
	echo Json::encode(['error' => 'NOT_SUPERSET']);
}
elseif (
	Main\Loader::includeModule('bitrix24')
	&& !Bitrix24\Feature::isFeatureEnabled('biconnector')
)
{
	echo Json::encode(['error' => 'DISABLED']);
}
elseif (isset($_GET['show_tables']))
{
	$tableList = $service->getTableList();
	echo Json::encode($tableList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
elseif (!$limitManager->checkLimit())
{
	echo Json::encode(['error' => 'LIMIT_EXCEEDED']);
}
elseif (empty($tableName) || !$service->getTableFields($tableName))
{
	echo Json::encode(['error' => 'NO_TABLE']);
}
elseif (isset($_GET['desc']))
{
	$tableFields = $service->getTableFields($tableName);
	echo Json::encode(array_values($tableFields), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
elseif (isset($_GET['explain']))
{
	$limit = (int)($_GET['limit'] ?? 0);
	if ($limit > 0)
	{
		$input['limit'] = $limit;
	}
	$result = $service->getData($tableName, $input);
	if (isset($result['error']))
	{
		echo Json::encode($result);
	}
	elseif (!empty($result['sql']))
	{
		echo $result['sql'] . "\n";

		$connection = $manager->getDatabaseConnection();
		try
		{
			$res = $connection->query('explain ' . $result['sql']);
			if (is_object($res))
			{
				\Bitrix\BIConnector\PrettyPrinter::printQueryResult($res);
			}
		}
		catch (\Bitrix\Main\DB\SqlQueryException $e)
		{
			echo $e->getMessage();
		}
	}
	else
	{
		echo 'Not SQL dataset';
	}
}
elseif ($service->getTableFields($tableName))
{
	$limit = (int)($_GET['limit'] ?? 0);
	if ($limit > 0)
	{
		$input['limit'] = $limit;
	}
	$result = $service->getData($tableName, $input);

	if (isset($result['error']))
	{
		echo Bitrix\Main\Web\Json::encode($result);
	}
	else
	{
		$resultQuery = $service->printQuery(
			$tableName,
			$input,
			$_SERVER['REQUEST_METHOD'],
			$_SERVER['REQUEST_URI'],
			$limit,
			$limitManager
		);

		if (!$resultQuery->isSuccess())
		{
			foreach ($resultQuery->getErrorCollection() as $error)
			{
				$outputError = ['error' => $error->getMessage()];
				if (!empty($error->getCustomData()['description']))
				{
					$outputError['errstr'] = $error->getCustomData()['description'];
				}

				echo Json::encode($outputError);
			}
		}
	}
}
else
{
	echo Json::encode(['error' => 'NO_TABLE']);
}

if ($isLocked)
{
	flock($lockFile, LOCK_UN);
}
if ($lockFile)
{
	fclose($lockFile);
	unlink($lockFileName);
}

\Bitrix\BIConnector\MemoryCache::expunge();

echo "\n";

\Bitrix\Main\Application::getInstance()->terminate();
