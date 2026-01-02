<?php

namespace Bitrix\BIConnector\ExternalSource\Source;

use Bitrix\BIConnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRest;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestConnector;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection;
use Bitrix\BIConnector\Services;
use Bitrix\BIConnector\Superset\Logger\Logger;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

class Rest extends Base
{
	private ExternalSourceRest | null $source;
	private int $requestTimeout = 250;

	private ?ExternalSourceRestConnector $connector = null;

	public function __construct(?int $sourceId)
	{
		parent::__construct($sourceId);

		$source = ExternalSourceRestTable::getList([
				'filter' => ['=SOURCE_ID' => $sourceId],
			])
			->fetchObject()
		;

		$source?->fillSource();
		$source?->fillConnector();

		if ($source)
		{
			$this->connector = $source->getConnector();
		}

		$this->source = $source;
	}

	private static function checkTableList(array $tableList): bool
	{
		foreach ($tableList as $table)
		{
			if (!is_array($table))
			{
				return false;
			}

			if (empty($table['code']) || empty($table['title']))
			{
				return false;
			}
		}

		return true;
	}

	private static function checkTableDescription(array $columns): bool
	{
		$requiredColumnFields = ['code', 'name', 'type'];

		if (empty($columns))
		{
			return false;
		}

		foreach ($columns as $column)
		{
			if (!is_array($column))
			{
				return false;
			}

			foreach ($requiredColumnFields as $field)
			{
				if (!array_key_exists($field, $column) || empty($column[$field]))
				{
					return false;
				}
			}
		}

		return true;
	}

	public function connect(ExternalSourceSettingsCollection $settings = null): Result
	{
		$result = new Result();

		if (!$this->connector?->getUrlCheck())
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_REST_CONNECTION_ERROR_EMPTY_CHECK_URL')));

			return $result;
		}

		$queryParams = [];
		if ($settings)
		{
			foreach ($settings as $setting)
			{
				$queryParams[$setting->getCode()] = $setting->getValue();
			}
		}

		try {
			$this->query(
				$this->connector?->getUrlCheck(),
				[
					'connection' => $queryParams,
				]
			);
		}
		catch (SystemException $e)
		{
			$result->addError(new Error($e->getMessage()));
		}

		return $result;
	}

	/**
	 * @param string|null $searchString Search query.
	 *
	 * @return Result Result of loading list/ Data is array with tables.
	 *
	 * ID - table code like "bank_accounts" <br>
	 * DATASET_NAME - same table code <br>
	 * TITLE - readable name of table like "(Dictionary) Bank Accounts" <br>
	 * DESCRIPTION - table name "bank_accounts" <br>
	 */
	public function getEntityList(?string $searchString = null): Result
	{
		$result = new Result();
		if (!$this->source->getConnector()?->getUrlTableList())
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_REST_CONNECTION_ERROR_EMPTY_DATA_URL')));

			return $result;
		}

		$query = $this->fillConnectionSettings([
			'searchString' => $searchString,
		]);

		try {
			$resultQuery = $this->query(
				$this->source->getConnector()?->getUrlTableList(),
				$query
			);

			$tableList = $this->decode($resultQuery);
			if ($tableList === null || !self::checkTableList($tableList))
			{
				$result->addError(new Error(Loc::getMessage('BICONNECTOR_REST_EMPTY_TABLE_LIST_ERROR')));

				return $result;
			}
		}
		catch (SystemException)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_REST_CONNECTION_ERROR_404_ERROR')));

			return $result;
		}

		$resultData = [];
		if (!empty($tableList))
		{
			foreach ($tableList as $table)
			{
				$resultData[] = [
					'ID' => $table['id'] ?? $table['code'],
					'TITLE' => $table['title'],
					'DESCRIPTION' => $table['code'],
					'DATASET_NAME' => $table['code'],
				];
			}
		}

		$result->setData($resultData);

		return $result;
	}

	/**
	 * @param string $entityName Table name with _ - like Catalog_BankAccounts.
	 *
	 * @return array
	 */
	public function getDescription(string $entityName): array
	{
		if (!$this->source->getConnector()?->getUrlTableDescription())
		{
			return [];
		}

		$query = $this->fillConnectionSettings([
			'table' => $entityName,
		]);

		try {
			$result = $this->query(
				$this->source->getConnector()?->getUrlTableDescription(),
				$query
			);

			$columns = $this->decode($result);
		}
		catch (SystemException)
		{
			throw new SystemException(Loc::getMessage('BICONNECTOR_REST_CONNECTION_TABLE_DESCRIPTION_404_ERROR'));
		}

		if (!$columns || !is_array($columns) || !self::checkTableDescription($columns))
		{
			throw new SystemException(Loc::getMessage('BICONNECTOR_REST_EMPTY_TABLE_DESCRIPTION_ERROR'));
		}

		$result = [];
		foreach ($columns as $column)
		{
			$result[] = [
				'CODE' => $column['code'],
				'NAME' => $column['name'] ?? $column['code'],
				'TYPE' => $this->mapType($column['type'] ?? Services\ApacheSuperset::TYPE_STRING),
			];
		}

		return $result;
	}

	/**
	 * @param string $tableName Table name with _ - like Catalog_BankAccounts.
	 * @param array $query Array of query params - select, filter, limit.
	 *
	 * @return string.
	 */
	public function getData(string $tableName, array $query): string
	{
		if (!$this->source->getSource()?->getActive())
		{
			throw new SystemException(Loc::getMessage('BICONNECTOR_REST_CONNECTION_NOT_ACTIVE'));
		}

		if (!$this->source->getConnector()?->getUrlData())
		{
			throw new SystemException(Loc::getMessage('BICONNECTOR_REST_CONNECTION_ERROR_EMPTY_DATA_URL'));
		}

		$query = array_intersect_key($query, array_flip(['limit', 'filter', 'select']));
		$query = $this->fillConnectionSettings($query);
		$query['table'] = $tableName;

		return $this->query($this->source->getConnector()?->getUrlData(), $query);
	}

	/**
	 * @param string $entityName Table name with _ - like Catalog_BankAccounts.
	 * @param int $n Amount of rows.
	 *
	 * @return array
	 */
	public function getFirstNData(string $entityName, int $n, array $fields = []): array
	{
		$fieldsForCacheKey = implode(',', array_keys($fields));
		$cacheKey = "biconnector_rest_preview_data_{$entityName}_{$n}_{$this->source->getSourceId()}_{$fieldsForCacheKey}";
		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();

		if ($cacheManager->read(3600, $cacheKey))
		{
			return $cacheManager->get($cacheKey);
		}

		$data = [];
		try {
			$result = $this->getData($entityName, ['limit' => $n]);
			$rows = $this->decode($result);
			if ($rows !== null)
			{
				$columns = array_shift($rows);

				$rows = array_slice($rows, 0, $n);
				foreach ($rows as $row)
				{
					$data[] = array_combine($columns, $row);
				}
			}
		}
		catch (SystemException)
		{
		}

		$cacheManager->set($cacheKey, $data);

		return $data;
	}

	/**
	 * @param ExternalSourceRestConnector $connector
	 *
	 * @return $this
	 */
	public function setConnector(ExternalSourceRestConnector $connector): self
	{
		$this->connector = $connector;

		return $this;
	}

	/**
	 * @param string $requestedUrl
	 * @param array $queryParams
	 *
	 * @return string
	 *
	 * @throws SystemException
	 */
	private function query(string $requestedUrl, array $queryParams = []): string
	{
		$url = new Uri($requestedUrl);

		$client = new HttpClient();
		$client->setTimeout($this->requestTimeout);
		if (\Bitrix\Main\Config\Option::get('biconnector', 'allow_local_connections', 'N') !== 'Y')
		{
			$client->setPrivateIp(false);
		}

		$answer = $client->post($url, $queryParams);
		$responseResult = $this->processResponse($answer, $client);
		$this->processResponseErrors($responseResult);

		if (!$responseResult->isSuccess())
		{
			throw new SystemException($responseResult->getError()->getMessage());
		}

		return $answer;
	}

	/**
	 * @param $answer
	 *
	 * @param HttpClient $client
	 * @return Result
	 */
	private function processResponse($answer, HttpClient $client): Result
	{
		$result = new Result();
		$effectiveUrl = $client->getEffectiveUrl();
		$result->setData([
			'answer' => $answer,
			'requestedUrl' => is_string($effectiveUrl) ? $effectiveUrl : $effectiveUrl->getUri(),
		]);

		if (!$answer)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_REST_CONNECTION_ERROR_CANT_CONNECT')));

			return $result;
		}

		if ($client->getStatus() !== 200)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_REST_CONNECTION_ERROR_404_ERROR')));

			return $result;
		}

		return $result;
	}

	/**
	 * @param Result $queryResult
	 *
	 * @return void
	 */
	private function processResponseErrors(Result $queryResult): void
	{
		if (!$queryResult->isSuccess())
		{
			Logger::logErrors($queryResult->getErrors(), [
				'connectionType' => 'rest',
				'connectionId' => $this->source?->getSource()?->getId() ?? 0,
				'requestedUrl' => $queryResult->getData()['requestedUrl'],
				'answer' => $queryResult->getData()['answer'] ?? 'No answer',
			]);
		}
	}

	/**
	 * @param string $type.
	 *
	 * @return string.
	 * @see \Bitrix\BIConnector\DataSourceConnector\ApacheSupersetFieldDto::mapType
	 */
	private function mapType(string $type): string
	{
		$mapType = FieldType::tryFrom(mb_strtolower($type));

		if ($mapType)
		{
			return $mapType->value;
		}

		return FieldType::String->value;
	}

	private function decode($data): ?array
	{
		try
		{
			$decoded = Json::decode($data);
			if (is_array($decoded))
			{
				return $decoded;
			}
		}
		catch (ArgumentException $e)
		{
		}

		return null;
	}

	private function fillConnectionSettings(array $queryFields): array
	{
		$settingFields = [];
		$settings = \Bitrix\BIConnector\ExternalSource\SourceManager::getSourceSettings($this->source->getSource());
		foreach ($settings as $setting)
		{
			$settingFields[$setting->getCode()] = $setting->getValue();
		}

		$queryFields['connection'] = $settingFields;

		return $queryFields;
	}
}
