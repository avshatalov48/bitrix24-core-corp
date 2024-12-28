<?php
namespace Bitrix\BIConnector\Services;

use Bitrix\BIConnector\LimitManager;
use Bitrix\BIConnector\Manager;
use Bitrix\BIConnector\Service;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

class MicrosoftPowerBI extends Service
{
	protected static $serviceId = 'pbi';
	public static $dateFormats = [
		'datetime_format' => '%Y-%m-%d %H:%i:%s',
		'datetime_format_php' => 'Y-m-d H:i:s',
		'date_format' => '%Y-%m-%d',
		'date_format_php' => 'Y-m-d',
	];

	/**
	 * @inheritDoc
	 */
	public static function validateDashboardUrl(\Bitrix\Main\Event $event)
	{
		$url = $event->getParameters()[0];
		$uri = new \Bitrix\Main\Web\Uri($url);
		$isUrlOk =
			$uri->getScheme() === 'https'
			&& $uri->getHost() === 'app.powerbi.com'
			&& (
				$uri->getPath() === '/view'
				|| $uri->getPath() === '/reportEmbed'
			)
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

		$fields = [];
		foreach ($schema as $fieldInfo)
		{
			$fields[] = $fieldInfo['ID'];
		}

		$count = 0;
		$size = 0;
		$countColumns = count($fields);
		$firstLinePrinted = false;

		$connector->sendAnalytic();

		$queryResult = $connector->query($parameters, $limit, static::$dateFormats);
		foreach ($queryResult as $row)
		{
			if (!$firstLinePrinted)
			{
				$output = "[\n" . Json::encode($fields, JSON_UNESCAPED_UNICODE) . "\n";
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

				$output = ',' . Json::encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) . "\n";
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
				$output = "[\n" . Json::encode($fields, JSON_UNESCAPED_UNICODE) . "\n";
				echo $output;
				$size += strlen($output);
			}

			$output = ']';
			echo $output;
			$size += strlen($output);
		}

		$manager->endQuery($logId, $count, $size, $limitManager->fixLimit($count));

		$databaseConnection->unlock('biconnector_data');

		return $result;
	}
}
