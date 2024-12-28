<?php
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
$input = $inputJSON ? Bitrix\Main\Web\Json::decode($inputJSON) : [];

if (\Bitrix\Main\Loader::includeModule('biconnector'))
{
	$supersetKey = $input['superset_key'] ?? '';
	unset($input['superset_key']);

	if (isset($input['key']))
	{
		$accessKey = substr($input['key'], 0 ,32);
		$languageCode = substr($input['key'], 32, 2);
		$input['key'] = 'hide-the-key-from-the-log';
	}
	else
	{
		$token = $_GET['token'] ?? '';
		$accessKey = substr($token, 0 ,32);
		$languageCode = substr($token, 32, 2);
	}

	$lockFileName = CTempFile::GetAbsoluteRoot() . '/' . md5($accessKey) . '-bi.lock';
	$lockFile = fopen($lockFileName, 'w');
	$isLocked = $lockFile ? flock($lockFile, LOCK_EX) : false;

	$manager = Bitrix\BIConnector\Manager::getInstance();

	$consumer = 'pbi';
	if (isset($_GET['consumer']) && in_array($_GET['consumer'], ['datalens'], true))
	{
		$consumer = $_GET['consumer'];
	}
	$service = $manager->createService($consumer);
	$service->setLanguage($languageCode);

	$limitManager = \Bitrix\BIConnector\LimitManager::getInstance();
	if ($supersetKey)
	{
		$limitManager->setSupersetKey($supersetKey);
	}
	else if (
		!\Bitrix\Main\Loader::includeModule('bitrix24')
		&& $accessKey === \Bitrix\BIConnector\Superset\KeyManager::getAccessKey()
	)
	{
		$limitManager->setIsSuperset();
	}

	$tableName = isset($_GET['table']) ? (string)$_GET['table'] : null;

	if (!$manager->checkAccessKey($accessKey))
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'WRONG_KEY']);
	}
	elseif (\Bitrix\Main\Loader::includeModule('bitrix24') && !\Bitrix\Bitrix24\Feature::isFeatureEnabled('biconnector'))
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'DISABLED']);
	}
	elseif (!$limitManager->checkLimit())
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'LIMIT_EXCEEDED']);
	}
	elseif (empty($tableName) || !$service->getTableFields($tableName))
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'NO_TABLE']);
	}
	elseif (isset($_GET['desc']))
	{
		$tableFields = $service->getTableFields($tableName);
		if (isset($_GET['pp']))
		{
			ob_start();
			\Bitrix\BIConnector\PrettyPrinter::printRowsArray($tableFields);
			$c = ob_get_clean();
			echo $c;
		}
		else
		{
			echo Bitrix\Main\Web\Json::encode(array_values($tableFields), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}
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
			echo Bitrix\Main\Web\Json::encode($result);
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
	elseif ($tableFields = $service->getTableFields($tableName))
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

					echo Bitrix\Main\Web\Json::encode($outputError);
				}
			}
		}
	}
	else
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'NO_TABLE']);
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
}
else
{
	echo Bitrix\Main\Web\Json::encode(['error' => 'NO_MODULE']);
}

echo "\n";

\Bitrix\Main\Application::getInstance()->terminate();
