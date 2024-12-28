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
	if (isset($_GET['superset_verify']))
	{
		if (\Bitrix\BIConnector\Superset\Config\ConfigContainer::getConfigContainer()->isPortalIdVerified())
		{
			echo Bitrix\Main\Web\Json::encode(['error' => 'ALREADY_VERIFIED']);
			echo "\n";

			\Bitrix\Main\Application::getInstance()->terminate();
		}

		if (!isset($input['message']))
		{
			echo Bitrix\Main\Web\Json::encode(['error' => 'NO_MESSAGE']);
			echo "\n";

			\Bitrix\Main\Application::getInstance()->terminate();
		}

		$message = $input['message'];
		$secretManager = Bitrix\BIConnector\Superset\Config\SecretManager::getManager();
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

		echo Bitrix\Main\Web\Json::encode($result);
		echo "\n";

		\Bitrix\Main\Application::getInstance()->terminate();
	}

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

	$serviceCode = 'gds';
	$service = $manager->createService($serviceCode);
	$service->setLanguage($languageCode);
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
	elseif (isset($_GET['show_tables']))
	{
		$tableList = $service->getTableList();
		echo Bitrix\Main\Web\Json::encode($tableList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
		else
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
	}
	elseif (isset($_GET['data']))
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
			$fields = [];
			foreach ($result['schema'] as $fieldInfo)
			{
				$fields[] = $fieldInfo['ID'];
			}

			$connection = $manager->getDatabaseConnection();
			$connection->lock('biconnector_data', -1);
			$comma = '';

			$getIdFunction = function ($x)
			{
				return $x['ID'];
			};

			$logId = $manager->startQuery(
				$tableName
				,implode(', ', array_map($getIdFunction, $result['schema']))
				,\Bitrix\Main\Web\Json::encode($result['where'], JSON_UNESCAPED_UNICODE)
				,\Bitrix\Main\Web\Json::encode($input, JSON_UNESCAPED_UNICODE)
				,$_SERVER['REQUEST_METHOD']
				,preg_replace('/(?:^|\\?|&)token=(.+?)(?:$|&)/', 'token=hide-the-key-from-the-log', $_SERVER['REQUEST_URI'])
			);

			$res = $connection->biQuery($result['sql']);
			if ($res)
			{
				$extraCount = count($result['shadowFields']);
				$selectFields = array_merge(array_values($result['schema']) , array_values($result['shadowFields']));

				$primary = [];
				foreach ($selectFields as $i => $fieldInfo)
				{
					if (isset($fieldInfo['IS_PRIMARY']) && $fieldInfo['IS_PRIMARY'] === 'Y')
					{
						$primary[] = $i;
					}
				}

				$group_fields = [];
				foreach ($selectFields as $i => $fieldInfo)
				{
					if (isset($fieldInfo['GROUP_CONCAT']))
					{
						foreach ($selectFields as $j => $keyInfo)
						{
							if ($keyInfo['ID'] == $fieldInfo['GROUP_KEY'])
							{
								$group_fields[$i] = [
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
								$group_fields[$i] = [
									'unique_id' => $j,
									'state' => new Bitrix\BIConnector\Aggregate\CountState($fieldInfo['GROUP_COUNT'] === 'DISTINCT'),
								];
								break;
							}
						}
					}
				}

				//Cleanup
				foreach ($result['schema'] as $i => $tableInfo)
				{
					unset($result['schema'][$i]['GROUP_KEY']);
					unset($result['schema'][$i]['GROUP_CONCAT']);
					unset($result['schema'][$i]['GROUP_COUNT']);
					unset($result['schema'][$i]['IS_PRIMARY']);
					if (!$tableInfo['AGGREGATION_TYPE'])
					{
						unset($result['schema'][$i]['AGGREGATION_TYPE']);
					}
					if (!$tableInfo['DESCRIPTION'])
					{
						unset($result['schema'][$i]['DESCRIPTION']);
					}
					if (!$tableInfo['NAME'])
					{
						unset($result['schema'][$i]['NAME']);
					}
				}

				$out = '{"schema":' . \Bitrix\Main\Web\Json::encode($result['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ',"rows":[' . "\n";
				echo $out;
				$count = 0;
				$size = strlen($out);

				$prevPrimaryKey = '';
				$output_row = false;
				while ($row = $res->fetch())
				{
					if ($limit && $count === $limit)
					{
						/** It`s Avoiding "Commands out of sync" error. The issue is 0205035 */
						continue;
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

					if ($primary && $group_fields)
					{
						if (!$output_row)
						{
							$output_row = $row;
							foreach ($group_fields as $i => $groupInfo)
							{
								$group_id = $row[$groupInfo['unique_id']];
								$output_row[$i] = clone $groupInfo['state'];
								$output_row[$i]->updateState($group_id, $row[$i]);
							}
						}
						elseif ($primaryKey === $prevPrimaryKey)
						{
							foreach ($group_fields as $i => $groupInfo)
							{
								$group_id = $row[$groupInfo['unique_id']];
								$output_row[$i]->updateState($group_id, $row[$i]);
							}
						}
						else
						{
							foreach ($group_fields as $i => $groupInfo)
							{
								$output_row[$i] = $output_row[$i]->output();
							}

							if ($extraCount)
							{
								array_splice($output_row, -$extraCount);
							}

							$out = $comma . '{"values":' . Bitrix\Main\Web\Json::encode($output_row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) . '}' . "\n";
							echo $out;
							$count++;
							$size += strlen($out);

							$comma = ',';

							$output_row = $row;
							foreach ($group_fields as $i => $groupInfo)
							{
								$group_id = $row[$groupInfo['unique_id']];
								$output_row[$i] = clone $groupInfo['state'];
								$output_row[$i]->updateState($group_id, $row[$i]);
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

						$out = $comma . '{"values":' . Bitrix\Main\Web\Json::encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) . '}' . "\n";
						echo $out;
						$count++;
						$size += strlen($out);

						$comma = ',';
					}
				}

				if ($output_row && !($limit && $count === $limit))
				{
					foreach ($group_fields as $i => $groupInfo)
					{
						$output_row[$i] = $output_row[$i]->output();
					}

					if ($extraCount)
					{
						array_splice($output_row, -$extraCount);
					}

					$out = $comma . '{"values":' . Bitrix\Main\Web\Json::encode($output_row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) . '}' . "\n";
					echo $out;
					$count++;
					$size += strlen($out);
				}

				$out = ']' . ($result['filtersApplied'] ? ',"filtersApplied":true' : '') . '}';
				echo $out;
				$size += strlen($out);

				$isOverLimit = $limitManager->fixLimit($count);

				$manager->endQuery($logId, $count, $size, $isOverLimit);
			}
			else
			{
				echo Bitrix\Main\Web\Json::encode([
					'error' => 'SQL_ERROR',
					'errstr' => $connection->getErrorMessage(),
				]);
			}
			$connection->unlock('biconnector_data');
		}
	}
	else
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'UKNOWN_COMMAND']);
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
