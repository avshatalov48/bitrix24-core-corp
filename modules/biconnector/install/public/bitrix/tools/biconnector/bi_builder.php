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
elseif (!$service->getTableFields($_GET['table']))
{
	echo Json::encode(['error' => 'NO_TABLE']);
}
elseif (isset($_GET['desc']))
{
	$tableFields = $service->getTableFields($_GET['table']);
	echo Json::encode(array_values($tableFields), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
elseif ($tableFields = $service->getTableFields($_GET['table']))
{
	$limit = (int)($_GET['limit'] ?? 0);
	$result = $service->getData($_GET['table'], $input);
	if (isset($result['error']))
	{
		echo Json::encode($result);
	}
	else
	{
		$connection = $manager->getDatabaseConnection();
		$connection->lock('biconnector_data', -1);

		$logId = $manager->startQuery(
			$_GET['table'],
			implode(', ', array_map(static fn($item) => $item['ID'], $result['schema'])),
			Json::encode($result['where'], JSON_UNESCAPED_UNICODE),
			Json::encode($input, JSON_UNESCAPED_UNICODE),
			$_SERVER['REQUEST_METHOD'],
			$_SERVER['REQUEST_URI'],
		);

		$queryResult = $connection->biQuery($result['sql']);
		if ($queryResult)
		{
			$extraCount = count($result['shadowFields']);
			$selectFields = array_merge(array_values($result['schema']), array_values($result['shadowFields']));

			$primary = [];
			foreach ($selectFields as $i => $fieldInfo)
			{
				if (isset($fieldInfo['IS_PRIMARY']) && $fieldInfo['IS_PRIMARY'] === 'Y')
				{
					$primary[] = $i;
				}
			}

			$groupFields = [];
			foreach ($selectFields as $i => $fieldInfo)
			{
				if (isset($fieldInfo['GROUP_CONCAT']))
				{
					foreach ($selectFields as $j => $keyInfo)
					{
						if ($keyInfo['ID'] == $fieldInfo['GROUP_KEY'])
						{
							$groupFields[$i] = [
								'unique_id' => $j,
								'state' => new Bitrix\BIConnector\Aggregate\ConcatState($fieldInfo['GROUP_CONCAT']),
							];
							break;
						}
					}
				}
				elseif (isset($fieldInfo['GROUP_COUNT']))
				{
					foreach ($selectFields as $j => $keyInfo)
					{
						if ($keyInfo['ID'] == $fieldInfo['GROUP_KEY'])
						{
							$groupFields[$i] = [
								'unique_id' => $j,
								'state' => new Bitrix\BIConnector\Aggregate\CountState($fieldInfo['GROUP_COUNT'] === 'DISTINCT'),
							];
							break;
						}
					}
				}
			}

			$fields = [];
			foreach ($result['schema'] as $fieldInfo)
			{
				$fields[] = $fieldInfo['ID'];
			}

			$out = "[\n" . Json::encode($fields, JSON_UNESCAPED_UNICODE) . "\n";
			echo $out;
			$count = 0;
			$size = strlen($out);

			$prevPrimaryKey = '';
			$outputRow = false;
			while ($row = $queryResult->fetch())
			{
				if ($limit && $count === $limit)
				{
					continue; //Avoid "Commands out of sync" error.
				}

				foreach ($result['onAfterFetch'] as $i => $callback)
				{
					$row[$i] = $callback($row[$i], $service::$dateFormats);
				}

				$primaryKey = '';
				foreach ($primary as $primaryIndex)
				{
					if ($primaryKey)
					{
						$primaryKey .= '-';
					}
					$primaryKey .= $row[$primaryIndex];
				}

				if ($primary && $groupFields)
				{
					if (!$outputRow)
					{
						$outputRow = $row;
						foreach ($groupFields as $i => $groupInfo)
						{
							$groupId = $row[$groupInfo['unique_id']];
							$outputRow[$i] = clone $groupInfo['state'];
							$outputRow[$i]->updateState($groupId, $row[$i]);
						}
					}
					elseif ($primaryKey === $prevPrimaryKey)
					{
						foreach ($groupFields as $i => $groupInfo)
						{
							$groupId = $row[$groupInfo['unique_id']];
							$outputRow[$i]->updateState($groupId, $row[$i]);
						}
					}
					else
					{
						foreach ($groupFields as $i => $groupInfo)
						{
							$outputRow[$i] = $outputRow[$i]->output();
						}
						if ($extraCount)
						{
							array_splice($outputRow, -$extraCount);
						}

						$out = ',' . Json::encode($outputRow, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) . "\n";
						echo $out;
						$count++;
						$size += strlen($out);

						$outputRow = $row;
						foreach ($groupFields as $i => $groupInfo)
						{
							$groupId = $row[$groupInfo['unique_id']];
							$outputRow[$i] = clone $groupInfo['state'];
							$outputRow[$i]->updateState($groupId, $row[$i]);
						}
					}
					$prevPrimaryKey = $primaryKey;
				}
				else
				{
					if ($extraCount)
					{
						array_splice($row, -$extraCount);
					}

					$out = ',' . Json::encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) . "\n";
					echo $out;
					$count++;
					$size += strlen($out);
				}
			}

			if ($outputRow && !($limit && $count === $limit))
			{
				foreach ($groupFields as $i => $groupInfo)
				{
					$outputRow[$i] = $outputRow[$i]->output();
				}

				if ($extraCount)
				{
					array_splice($outputRow, -$extraCount);
				}

				$out = ',' . Json::encode($outputRow, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) . "\n";
				echo $out;
				$count++;
				$size += strlen($out);
			}

			$out = ']';
			echo $out;
			$size += strlen($out);

			$isOverLimit = $limitManager->fixLimit($count);

			$manager->endQuery($logId, $count, $size, $isOverLimit);
		}
		else
		{
			echo Json::encode([
				'error' => 'SQL_ERROR',
				'errstr' => $connection->getErrorMessage(),
			]);
		}
		$connection->unlock('biconnector_data');
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
