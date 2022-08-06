<?php
define('NOT_CHECK_PERMISSIONS', true);
define('NO_KEEP_STATISTIC', true);
define('BX_SECURITY_SESSION_VIRTUAL', true);
define('SKIP_DISK_QUOTA_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

/** @var CUser $USER */
if (!$USER->IsAuthorized())
{
	@session_destroy();
}

@set_time_limit(0);
while(ob_end_clean());
header('Content-Type:application/json; charset=UTF-8');

$inputJSON = file_get_contents('php://input');
$input = $inputJSON ? Bitrix\Main\Web\Json::decode($inputJSON) : [];

if (\Bitrix\Main\Loader::includeModule('biconnector'))
{
	if (isset($input['key']))
	{
		$accessKey = substr($input['key'], 0 ,32);
		$languageCode = substr($input['key'], 32, 2);
		$input['key'] = 'hide-the-key-from-the-log';
	}
	else
	{
		$accessKey = substr($_GET['token'], 0 ,32);
		$languageCode = substr($_GET['token'], 32, 2);
	}

	$manager = Bitrix\BIConnector\Manager::getInstance();
	$service = $manager->createService('gds');
	$service->setLanguage($languageCode);

	if (!$manager->checkAccessKey($accessKey))
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'WRONG_KEY']);
	}
	elseif (\Bitrix\Main\Loader::includeModule('bitrix24') && !\Bitrix\Bitrix24\Feature::isFeatureEnabled('biconnector'))
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'DISABLED']);
	}
	elseif (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimit())
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'LIMIT_EXCEEDED']);
	}
	elseif (isset($_GET['show_tables']))
	{
		$tableList = $service->getTableList();
		echo Bitrix\Main\Web\Json::encode($tableList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}
	elseif (!$service->getTableFields($_GET['table']))
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'NO_TABLE']);
	}
	elseif (isset($_GET['desc']))
	{
		$tableFields = $service->getTableFields($_GET['table']);
		if (isset($_GET['pp']))
		{
			ob_start();
			\Bitrix\BIConnector\PrettyPrinter::printRowsArray($tableFields);
			$c = ob_get_clean();
			echo \Bitrix\Main\Text\Encoding::convertEncoding($c, SITE_CHARSET, 'UTF-8');
		}
		else
		{
			echo Bitrix\Main\Web\Json::encode(array_values($tableFields), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}
	}
	elseif (isset($_GET['explain']))
	{
		$result = $service->getData($_GET['table'], $input);
		$connection = $manager->getDatabaseConnection();

		try
		{
			$res = $connection->query('explain ' . $result['sql']);
		}
		catch (\Bitrix\Main\DB\SqlQueryException $e)
		{
			$res = $e->getMessage();
		}

		echo $result['sql'] . "\n";
		if (is_object($res))
		{
			\Bitrix\BIConnector\PrettyPrinter::printQueryResult($res);
		}
		else
		{
			echo $e;
		}
	}
	elseif (isset($_GET['data']))
	{
		$result = $service->getData($_GET['table'], $input);

		$fields = [];
		foreach ($result['schema'] as $fieldInfo)
		{
			$fields[] = $fieldInfo['ID'];
		}

		$connection = $manager->getDatabaseConnection();
		$connection->lock('biconnector_data', -1);
		$comma = '';

		$logId = $manager->startQuery(
			$_GET['table']
			,'*'
			,\Bitrix\Main\Web\Json::encode($result['where'], JSON_UNESCAPED_UNICODE)
			,\Bitrix\Main\Web\Json::encode($input, JSON_UNESCAPED_UNICODE)
			,$_SERVER['REQUEST_METHOD']
			,preg_replace('/(?:^|\\?|&)token=(.+?)(?:$|&)/', 'token=hide-the-key-from-the-log', $_SERVER['REQUEST_URI'])
		);

		$stmt = $connection->getResource()->prepare($result['sql']);
		if (is_object($stmt))
		{
			$stmt->execute();

			$primary = null;
			$concat_fields = [];
			$i = 0;
			foreach ($result['schema'] as $fieldInfo)
			{
				if (isset($fieldInfo['IS_PRIMARY']) && $fieldInfo['IS_PRIMARY'] === 'Y')
				{
					$primary = $i;
				}
				if (isset($fieldInfo['CONCAT_GROUP_BY']))
				{
					$concat_fields[$i] = $fieldInfo['CONCAT_GROUP_BY'];
				}

				$i++;
			}

			$row_dat = [];
			$row_ref = [];
			foreach ($result['schema'] as $fieldInfo)
			{
				$row_dat[] = '';
				$row_ref[] = &$row_dat[count($row_dat) - 1];
			}
			call_user_func_array([$stmt, 'bind_result'], $row_ref);

			$count = 0;
			echo '{"schema":' . \Bitrix\Main\Web\Json::encode($result['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ',"rows":[' . "\n";
			$output_row = false;
			while ($stmt->fetch())
			{
				$row = [];
				foreach ($row_dat as $v)
				{
					$row[] = $v; //dereference
				}

				foreach ($result['onAfterFetch'] as $i => $callback)
				{
					$row[$i] = $callback($row[$i], $service::$dateFormats);
				}

				if (isset($primary) && $concat_fields)
				{
					if (!$output_row)
					{
						$output_row = $row;
						foreach ($concat_fields as $i => $delimiter)
						{
							$output_row[$i] = $row[$i] ? [$row[$i] => 1] : [];
						}
					}
					elseif ($row[$primary] === $output_row[$primary])
					{
						foreach ($concat_fields as $i => $delimiter)
						{
							if ($row[$i])
							{
								$output_row[$i][$row[$i]] = 1;
							}
						}
					}
					else
					{
						foreach ($concat_fields as $i => $delimiter)
						{
							$output_row[$i] = implode($delimiter, array_keys($output_row[$i]));
						}
						echo $comma . '{"values":' . Bitrix\Main\Web\Json::encode($output_row, JSON_UNESCAPED_UNICODE) . '}' . "\n";
						$comma = ',';
						$count++;

						$output_row = $row;
						foreach ($concat_fields as $i => $delimiter)
						{
							$output_row[$i] = $row[$i] ? [$row[$i] => 1] : [];
						}
					}
				}
				else
				{
					echo $comma . '{"values":' . Bitrix\Main\Web\Json::encode($row, JSON_UNESCAPED_UNICODE) . '}' . "\n";
					$comma = ',';
					$count++;
				}
			}

			if ($output_row)
			{
				foreach ($concat_fields as $i => $delimiter)
				{
					$output_row[$i] = implode($delimiter, array_keys($output_row[$i]));
				}
				echo $comma . '{"values":' . Bitrix\Main\Web\Json::encode($output_row, JSON_UNESCAPED_UNICODE) . '}' . "\n";
				$count++;
			}

			echo ']' . ($result['filtersApplied'] ? ',"filtersApplied":true' : '') . '}';
			$manager->endQuery($logId, $count);
			\Bitrix\BIConnector\LimitManager::getInstance()->fixLimit($count);
		}
		else
		{
			echo Bitrix\Main\Web\Json::encode([
				'error' => 'SQL_ERROR',
				'errno' => $connection->getResource()->errno,
				'errstr' => $connection->getResource()->error,
			]);
		}
		$connection->unlock('biconnector_data');
	}
	else
	{
		echo Bitrix\Main\Web\Json::encode(['error' => 'UKNOWN_COMMAND']);
	}
}
else
{
	echo Bitrix\Main\Web\Json::encode(['error' => 'NO_MODULE']);
}

echo "\n";

\Bitrix\Main\Application::getInstance()->terminate();
