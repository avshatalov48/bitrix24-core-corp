<?php

namespace Bitrix\BIConnector\ExternalSource\Source;

use Bitrix\BIConnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\Internal\EO_ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\BIConnector\ExternalSource\SourceManager;
use Bitrix\BIConnector\Superset\Logger\Logger;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

class Source1C extends Base
{
	private EO_ExternalSource | null $source;
	/** @var ExternalDatasetField[] */
	private array $datasetFields = [];

	private string | null $host = null;
	private string | null $username = null;
	private string | null $password = null;
	private int $requestTimeout = 300;

	private const API_VERSION = 1;

	private const CHECK_CONNECTION_ENDPOINT = '/hs/bitrixAnalytics/checkConnection';
	private const TABLE_LIST_ENDPOINT = '/hs/bitrixAnalytics/listMetadata';
	private const TABLE_DESCRIPTION_ENDPOINT = '/hs/bitrixAnalytics/getMetadata';
	private const DATA_ENDPOINT = '/hs/bitrixAnalytics/getData';

	public function __construct(?int $sourceId)
	{
		parent::__construct($sourceId);

		$source = ExternalSourceTable::getList([
			'filter' => ['=ID' => $sourceId],
		])->fetchObject();

		$this->source = $source;
	}

	public function connect(ExternalSourceSettingsCollection $settings): Result
	{
		$this->host = $settings->getValueByCode('host');
		$this->username = $settings->getValueByCode('username');
		$this->password = $settings->getValueByCode('password');
		$this->requestTimeout = 5;

		$connectResult = $this->get(self::CHECK_CONNECTION_ENDPOINT);
		if ($connectResult->getData()['statusCode'] === 404)
		{
			$result = new Result();
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_1C_CONNECTION_ERROR_CONNECTION_NOT_FOUND')));

			return $result;
		}

		return $connectResult;
	}

	/**
	 * @param string|null $searchString Search query.
	 *
	 * @return Result Result of loading list. Data is array with tables.
	 *
	 * ID - table code like "Catalog#BankAccounts" <br>
	 * TITLE - readable name of table like "(Dictionary) Bank Accounts" <br>
	 * DESCRIPTION - same readable name of table <br>
	 * DATASET_NAME - transliterated table name to save as dataset<br>
	 */
	public function getEntityList(?string $searchString = null): Result
	{
		$result = new Result();
		$queryResult = $this->post(self::TABLE_LIST_ENDPOINT, [
			'searchString' => $searchString,
		]);
		$tableList = $this->decode($queryResult->getData()['answer']);
		if (!$tableList)
		{
			$result->setData([]);

			return $result;
		}

		$formatted = [];
		foreach ($tableList as $table)
		{
			$formatted[] = [
				'ID' => $table['code'],
				'TITLE' => $table['name'],
				'DESCRIPTION' => $table['name'],
				'DATASET_NAME' => \CUtil::translit($table['name'], 'ru'),
			];
		}

		$result->setData($formatted);

		return $result;
	}

	/**
	 * @param string $entityName Table code with # - like Catalog#BankAccounts.
	 *
	 * @return array
	 */
	public function getDescription(string $entityName): array
	{
		$result = $this->post(self::TABLE_DESCRIPTION_ENDPOINT, ['code' => $entityName]);
		$tableData = $this->decode($result->getData()['answer']);
		if (!$tableData)
		{
			return [];
		}

		$columns = [];
		foreach ($tableData['columns'] as $column)
		{
			$type = $this->mapType($column['type']);
			if ($type)
			{
				$columns[] = [
					'NAME' => $column['name'],
					'EXTERNAL_CODE' => $column['name'],
					'TYPE' => $type,
				];
			}
		}

		return $columns;
	}

	/**
	 * @param string $tableName Table code with # - like Catalog#BankAccounts.
	 * @param array $query Array of query params - select, filter, limit.
	 *
	 * @return string Answer with data in trino format.
	 */
	public function getData(string $tableName, array $query): string
	{
		if (!$this->source->getActive())
		{
			throw new SystemException(Loc::getMessage('BICONNECTOR_1C_SOURCE_NOT_ACTIVE'));
		}

		$selectFields = $query['select'];
		if (!$selectFields)
		{
			$selectFields = array_map(static fn($field) => $field->getName(), $this->datasetFields);
		}

		$params = [
			'code' => $tableName,
			'select' => $selectFields,
			'columnNames' => $query['columnNames'],
			'filters' => $query['filter'] ?? [],
		];

		if (isset($query['limit']))
		{
			$params['limit'] = (int)$query['limit'];
		}

		$result = $this->requestData($params);

		return $result;
	}

	/**
	 * @param string $entityName Table code with # - like Catalog#BankAccounts.
	 * @param int $n Amount of rows.
	 * @param array $fields Field names mapping - [DatasetFieldName => ExternalFieldName]
	 *
	 * @return array
	 */
	public function getFirstNData(string $entityName, int $n, array $fields = []): array
	{
		$fieldsForCacheKey = implode(',', array_keys($fields));
		$cacheKey = "biconnector_1c_preview_data_{$entityName}_{$n}_{$this->source->getId()}_{$fieldsForCacheKey}";
		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();

		if ($cacheManager->read(3600, $cacheKey))
		{
			return $cacheManager->get($cacheKey);
		}

		$queryParams = [
			'select' => array_keys($fields),
			'columnNames' => $fields,
			'limit' => $n,
		];
		$data = $this->getData($entityName, $queryParams);
		$result = $this->decode($data);
		if (!$result)
		{
			return [];
		}

		$cacheManager->set($cacheKey, $result);

		return $result;
	}

	public function initDatasetFields(string $datasetName): void
	{
		$dataset = ExternalDatasetTable::getList(['filter' => ['=NAME' => $datasetName], 'limit' => 1])->fetchObject();
		if (!$dataset)
		{
			return;
		}

		$datasetFields = ExternalDatasetFieldTable::getList([
			'select' => ['*'],
			'filter' => [
				'=DATASET_ID' => $dataset->getId(),
				'=VISIBLE' => 'Y',
			]
		])->fetchCollection();

		$result = [];
		foreach ($datasetFields as $field)
		{
			$result[$field->getName()] = $field;
		}

		$this->datasetFields = $result;
	}

	private function getHttpClient(): HttpClient
	{
		$client = new HttpClient();

		$settings = null;
		if ($this->source)
		{
			$settings = SourceManager::getSourceSettings($this->source);
		}
		$username = $this->username ?? $settings?->getValueByCode('username');
		$password = $this->password ?? $settings?->getValueByCode('password');
		if ($username && $password)
		{
			$client->setAuthorization($username, $password);
		}
		$client->setTimeout($this->requestTimeout);
		if (\Bitrix\Main\Config\Option::get('biconnector', 'allow_local_connections', 'N') !== 'Y')
		{
			$client->setPrivateIp(false);
		}

		return $client;
	}

	private function getHost(): string
	{
		if ($this->host)
		{
			return $this->host;
		}

		$settings = null;
		if ($this->source)
		{
			$settings = SourceManager::getSourceSettings($this->source);
		}
		$host = $settings?->getValueByCode('host') ?? '';
		$this->host = $host;

		return $this->host;
	}

	private function get(string $requestedUrl, array $queryParams = []): Result
	{
		$encodedUrl = Uri::urnEncode($this->getHost() . $requestedUrl);
		$url = new Uri($encodedUrl);
		$queryParams['apiVersion'] = self::API_VERSION;
		$url->addParams($queryParams);

		$client = $this->getHttpClient();
		$answer = $client->get($url);

		$responseResult = $this->processResponse($answer, $client);
		$this->processResponseErrors($responseResult);

		return $responseResult;
	}

	private function post(string $requestedUrl, array $queryParams = []): Result
	{
		$encodedUrl = Uri::urnEncode($this->getHost() . $requestedUrl);
		$url = new Uri($encodedUrl);
		$queryParams['apiVersion'] = self::API_VERSION;

		$client = $this->getHttpClient();
		$answer = $client->post($url, json_encode($queryParams));

		$responseResult = $this->processResponse($answer, $client);
		$this->processResponseErrors($responseResult);

		return $responseResult;
	}

	private function requestData(array $queryParams = []): string
	{
		$encodedUrl = Uri::urnEncode($this->getHost() . self::DATA_ENDPOINT);
		$url = new Uri($encodedUrl);
		$queryParams['apiVersion'] = self::API_VERSION;
		$client = $this->getHttpClient();
		$answer = $client->post($url, json_encode($queryParams));

		$responseResult = $this->processResponse($answer, $client);
		$this->processResponseErrors($responseResult);
		if (!$responseResult->isSuccess())
		{
			throw new \Bitrix\Main\SystemException($responseResult->getErrorMessages()[0]);
		}

		return $answer;
	}

	private function processResponse($answer, HttpClient $client): Result
	{
		$result = new Result();
		$result->setData([
			'requestedUrl' => Uri::urnDecode($client->getEffectiveUrl()),
			'statusCode' => $client->getStatus(),
		]);

		if ($client->getStatus() === 401)
		{
			$errorData = $this->decode($answer);
			if ($errorData)
			{
				if (isset($errorData['odata.error']))
				{
					$result->addError(new Error($errorData['odata.error']['message']['value']));

					return $result;
				}
			}
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_1C_CONNECTION_ERROR_PASSWORD')));

			return $result;
		}

		if ($client->getStatus() === 0)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_1C_CONNECTION_ERROR_WRONG_HOST')));

			return $result;
		}

		$result->setData([
			...$result->getData(),
			'answer' => $answer,
		]);
		if ($client->getStatus() === 200)
		{
			$result->setData([
				...$result->getData(),
			]);

			return $result;
		}

		// Other statuses and errors
		if (isset($responseData['odata.error']))
		{
			$result->addError(new Error($responseData['odata.error']['message']['value']));

			return $result;
		}

		if ($client->getStatus() >= 500)
		{
			$result->addError(new Error($answer));

			return $result;
		}

		$result->addError(new Error(Loc::getMessage('BICONNECTOR_1C_CONNECTION_ERROR_CANT_CONNECT')));

		return $result;
	}

	private function processResponseErrors(Result $queryResult): void
	{
		if (!$queryResult->isSuccess())
		{
			Logger::logErrors($queryResult->getErrors(), [
				'connectionType' => '1C',
				'connectionId' => $this->source?->getId() ?? 0,
				'requestedUrl' => $queryResult->getData()['requestedUrl'],
				'answer' => $queryResult->getData()['answer'] ?? 'No answer',
			]);
		}
	}

	/**
	 * @param string $type Type from 1C.
	 *
	 * @return string|null Type supported by trino, null if type is unsupported.
	 * @see \Bitrix\BIConnector\DataSourceConnector\ApacheSupersetFieldDto::mapType
	 */
	private function mapType(string $type): ?string
	{
		return match ($type)
		{
			'string', 'boolean' => 'STRING',
			'int' => 'INT',
			'double' => 'DOUBLE',
			'datetime' => 'DATETIME',
			'date' => 'DATE',
			default => null,
		};
	}

	private function decode($data): ?array
	{
		try
		{
			return Json::decode($data);
		}
		catch (ArgumentException $e)
		{
			return null;
		}
	}
}
