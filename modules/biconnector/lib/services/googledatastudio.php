<?php
namespace Bitrix\BIConnector\Services;

use Bitrix\BIConnector\LimitManager;
use Bitrix\BIConnector\Manager;
use Bitrix\BIConnector\Service;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

class GoogleDataStudio extends Service
{
	protected static $serviceId = 'gds';
	public static $dateFormats = [
		'datetime_format' => '%Y%m%d%H%i%s',
		'datetime_format_php' => 'YmdHis',
		'date_format' => '%Y%m%d',
		'date_format_php' => 'Ymd',
	];

	public const URL_CREATE = 'https://datastudio.google.com/datasources/create';
	public const OPTION_DEPLOYMENT_ID = 'gds_deployment_id';

	/**
	 * @inheritDoc
	 */
	public static function validateDashboardUrl(\Bitrix\Main\Event $event)
	{
		$url = $event->getParameters()[0];
		$uri = new \Bitrix\Main\Web\Uri($url);
		$isUrlOk =
			$uri->getScheme() === 'https'
			&& (
				$uri->getHost() === 'datastudio.google.com'
				|| $uri->getHost() === 'lookerstudio.google.com'
			)
			&& strpos($uri->getPath(), '/embed/reporting/') === 0
		;
		$result = new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, ($isUrlOk ? 1 : 0));
		return $result;
	}

	/**
	 * @param string $tableName
	 * @param array $parameters
	 * @return Result
	 */
	public function printQuery(
		string $tableName,
		array $parameters,
		string $requestMethod,
		string $requestUri,
		int $limit,
		LimitManager $limitManager
	): Result
	{
		$result = new Result();
		$connector = $this->getDataSourceConnector($tableName);
		if (!$connector)
		{
			$result->addError(new Error('NO_TABLE'));

			return $result;
		}

		$manager = Manager::getInstance();
		$data = $connector->getFormattedData($parameters, static::$dateFormats);

		$databaseConnection = $manager->getDatabaseConnection();
		$databaseConnection->lock('biconnector_data', -1);

		$getIdFunction = function ($x)
		{
			return $x['ID'];
		};

		$schema = $data['schema'] ?? [];

		$logId = $manager->startQuery(
			$tableName
			,implode(', ', array_map($getIdFunction, $schema))
			,Json::encode($data['where'], JSON_UNESCAPED_UNICODE)
			,Json::encode($parameters, JSON_UNESCAPED_UNICODE)
			,$requestMethod
			,preg_replace('/(?:^|\\?|&)token=(.+?)(?:$|&)/', 'token=hide-the-key-from-the-log', $requestUri)
		);

		foreach ($schema as $i => $tableInfo)
		{
			unset($schema[$i]['GROUP_KEY']);
			unset($schema[$i]['GROUP_CONCAT']);
			unset($schema[$i]['GROUP_COUNT']);
			unset($schema[$i]['IS_PRIMARY']);
			if (!$tableInfo['AGGREGATION_TYPE'])
			{
				unset($schema[$i]['AGGREGATION_TYPE']);
			}
			if (!$tableInfo['DESCRIPTION'])
			{
				unset($schema[$i]['DESCRIPTION']);
			}
			if (!$tableInfo['NAME'])
			{
				unset($schema[$i]['NAME']);
			}
		}

		$comma = '';

		$count = 0;
		$size = 0;
		$countColumns = count($schema);
		$firstLinePrinted = false;

		$connector->sendAnalytic();

		$queryResult = $connector->query($parameters, $limit, static::$dateFormats);
		foreach ($queryResult as $row)
		{
			if (!$firstLinePrinted)
			{
				$output = '{"schema":' . Json::encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ',"rows":[' . "\n";
				echo $output;
				$size += strlen($output);
				$firstLinePrinted = true;
			}

			if (is_array($row))
			{
				$rowCount = count($row);
				if ($countColumns < $rowCount)
				{
					$row = array_slice($row, 0, $countColumns);
				}
				elseif ($countColumns > $rowCount)
				{
					$row += array_fill($rowCount, $countColumns - $rowCount, null);
				}

				$output = $comma . '{"values":' . Json::encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) . '}' . "\n";
				$comma = ',';
				echo $output;
				$size += strlen($output);

				$count++;
			}
		}

		$queryReturnResult = $queryResult->getReturn();
		if ($queryReturnResult instanceof Result && !$queryReturnResult->isSuccess())
		{
			$result->addErrors($queryReturnResult->getErrors());
		}
		else
		{
			if (!$firstLinePrinted)
			{
				$output = '{"schema":' . Json::encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ',"rows":[' . "\n";
				echo $output;
				$size += strlen($output);
			}

			$output = ']' . ($data['filtersApplied'] ? ',"filtersApplied":true' : '') . '}';
			echo $output;
			$size += strlen($output);
		}

		$manager->endQuery($logId, $count, $size, $limitManager->fixLimit($count));

		$databaseConnection->unlock('biconnector_data');

		return $result;
	}
}
