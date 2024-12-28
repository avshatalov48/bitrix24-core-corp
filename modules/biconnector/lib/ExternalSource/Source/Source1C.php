<?php

namespace Bitrix\BIConnector\ExternalSource\Source;

use Bitrix\BIConnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\Internal\EO_ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
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
	private int $requestTimeout = 60;

	private const CHECK_CONNECTION_ENDPOINT = '/hs/bitrixAnalytics/checkConnection';
	private const ACTIVATE_TABLE_ENDPOINT = '/hs/bitrixAnalytics/activateMetadata';
	private const TABLE_LIST_ENDPOINT = '/hs/bitrixAnalytics/listMetadata';
	private const TABLE_DESCRIPTION_ENDPOINT = '/odata/standard.odata/$metadata';
	private const DATA_ENDPOINT = '/odata/standard.odata/';

	public function __construct(?int $sourceId)
	{
		parent::__construct($sourceId);

		$source = ExternalSourceTable::getList([
			'filter' => ['=ID' => $sourceId],
		])->fetchObject();

		$this->source = $source;
	}

	public function connect(string $host, string $username, string $password): Result
	{
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
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
	 * @return array Array with tables.
	 *
	 * ID - table code like "Catalog#BankAccounts" <br>
	 * TITLE - readable name of table like "(Dictionary) Bank Accounts" <br>
	 * DESCRIPTION - table name like "Catalog_BankAccounts" <br>
	 */
	public function getEntityList(?string $searchString = null): array
	{
		$result = $this->post(self::TABLE_LIST_ENDPOINT, [
			'searchString' => $searchString,
		]);

		$tableList = $result->getData()['data'] ?? [];
		if (!$tableList)
		{
			return [];
		}

		$result = [];
		foreach ($tableList as $table)
		{
			$result[] = [
				'ID' => $table['code'],
				'TITLE' => $table['description'],
				'DESCRIPTION' => $table['name'],
				'DATASET_NAME' => \CUtil::translit($table['description'], 'ru'),
			];
		}

		return $result;
	}

	/**
	 * @param string $tableCode Table code with # - like Catalog#BankAccounts.
	 *
	 * @return Result
	 */
	public function activateEntity(string $tableCode): Result
	{
		$result = $this->post(self::ACTIVATE_TABLE_ENDPOINT, [
			'code' => $tableCode,
		]);

		$answer = $result->getData()['data'];
		if (!is_array($answer) || ($answer['result'] ?? '') !== 'success')
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_1C_CONNECTION_ERROR_ACTIVATE_ENTITY')));
		}

		return $result;
	}

	/**
	 * @param string $entityName Table name with _ - like Catalog_BankAccounts.
	 *
	 * @return array
	 */
	public function getDescription(string $entityName): array
	{
		$result = $this->get(self::TABLE_DESCRIPTION_ENDPOINT);
		$tableData = $result->getData()['answer'];

		$xmlParser = new \CDataXML();
		$xmlParser->loadString($tableData);
		$tree = $xmlParser->getTree();
		if (!$tree)
		{
			return [];
		}

		$tables = $tree->elementsByName('EntityType');
		$columns = [];

		foreach ($tables as $table)
		{
			if ($entityName === $table->getAttribute('Name'))
			{
				$properties = $table->elementsByName('Property');
				foreach ($properties as $property)
				{
					$type = $this->mapType($property->getAttribute('Type'));
					if ($type)
					{
						$columns[] = [
							'ID' => $property->getAttribute('Name'),
							'NAME' => $property->getAttribute('Name'),
							'TYPE' => $type,
						];
					}
				}
			}
		}

		return $columns;
	}

	/**
	 * @param string $tableName Table name with _ - like Catalog_BankAccounts.
	 * @param array $query Array of query params - select, filter, limit.
	 *
	 * @return array Elements are arrays like [fieldName => fieldValue].
	 */
	public function getData(string $tableName, array $query): array
	{
		if (!$this->source->getActive())
		{
			throw new SystemException(Loc::getMessage('BICONNECTOR_1C_SOURCE_NOT_ACTIVE'));
		}

		$params = [];

		$selectFields = array_column($query['select'] ?? [], 'NAME');
		$selectString = $this->prepareSelectString($selectFields);

		$params['$format'] = 'json';
		$params['$select'] = $selectString;

		$filterString = $this->prepareFilterString($query['filter'] ?? []);
		if ($filterString)
		{
			$params['$filter'] = $filterString;
		}

		if ($query['limit'] ?? 10)
		{
			$params['$top'] = (int)$query['limit'];
		}

		$getResult = $this->get(self::DATA_ENDPOINT . $tableName, $params);

		if (!$getResult->isSuccess())
		{
			if (($getResult->getData()['statusCode'] ?? 0) === 404)
			{
				throw new SystemException(Loc::getMessage('BICONNECTOR_1C_SOURCE_404_ERROR'));
			}

			throw new SystemException(implode($getResult->getErrorMessages()));
		}

		$data = $getResult->getData()['data'] ?? [];
		if (!$data)
		{
			return [];
		}

		return $data['value'];
	}

	/**
	 * @param string $entityName Table name with _ - like Catalog_BankAccounts.
	 * @param int $n Amount of rows.
	 *
	 * @return array
	 */
	public function getFirstNData(string $entityName, int $n): array
	{
		$cacheKey = "biconnector_1c_preview_data_{$entityName}_{$n}_{$this->source->getId()}";
		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();

		if ($cacheManager->read(3600, $cacheKey))
		{
			return $cacheManager->get($cacheKey);
		}

		$data = $this->getData($entityName, ['limit' => $n]);

		$cacheManager->set($cacheKey, $data);

		return $data;
	}

	/**
	 * @param string[] $selectFields
	 *
	 * @return string
	 */
	private function prepareSelectString(array $selectFields): string
	{
		if (
			empty($selectFields)
			|| count($selectFields) === count($this->datasetFields)
		)
		{
			return '*';
		}

		$result = [];
		foreach ($selectFields as $selectField)
		{
			foreach ($this->datasetFields as $datasetField)
			{
				if ($datasetField->getName() === $selectField)
				{
					$result[] = $datasetField->getExternalCode();
				}
			}
		}

		return implode(',', $result);
	}

	private function prepareFilterString(array $filter = []): string
	{
		if (empty($filter))
		{
			return '';
		}

		$filterConditions = [];
		foreach ($filter as $key => $topFilter)
		{
			if (is_array($topFilter))
			{
				$logic = $topFilter['LOGIC'];
				unset($topFilter['LOGIC']);
				foreach ($topFilter as $subFilter)
				{
					$fieldConditions = [];
					foreach ($subFilter as $code => $value)
					{
						$fieldCondition = '';
						$negate = false;
						if (str_starts_with($code, '!'))
						{
							$negate = true;
						}

						if (str_starts_with($code, '='))
						{
							$code = substr($code, 1);
							$datasetField = $this->datasetFields[$code];

							if (is_array($value))
							{
								$valueConditions = [];
								foreach ($value as $valueItem)
								{
									if (str_ends_with($datasetField->getExternalCode(), '_Key'))
									{
										$valueConditions[] = "{$datasetField->getExternalCode()} eq guid'{$valueItem}'";
									}
									else
									{
										$valueConditions[] = "{$datasetField->getExternalCode()} eq '{$valueItem}'";
									}
								}
								$fieldCondition .= '(' . implode(' or ', $valueConditions) . ')';
							}
							elseif ($value === false)
							{
								// TODO Null operator
								// $fieldCondition .= $datasetField->getName() . ' is null';
								// $fieldCondition .=  "isof({$datasetField->getName()}, 'Null')";
							}
						}
						$fieldConditions[] = $fieldCondition;
					}
					$filterConditions[] = implode(" {$logic} ", $fieldConditions);
				}
			}
			elseif (
				str_starts_with($key, '>=')
				|| str_starts_with($key, '<=')
			)
			{
				$code = substr($key, 2);
				$operator = null;
				if (str_starts_with($key, '>='))
				{
					$operator = 'gt';
				}
				elseif (str_starts_with($key, '<='))
				{
					$operator = 'lt';
				}

				if (!$operator)
				{
					continue;
				}
				$value = $topFilter;
				$dateValue = (new DateTime($value));
				if ($dateValue > new DateTime('31.12.3999'))
				{
					// 1c doesn't allow years after 3999 in filters
					$dateValue = new DateTime('31.12.3999 23:59:59');
				}
				$value = $dateValue->format('Y-m-d\TH:i:s');
				$datasetField = $this->datasetFields[$code];
				if (
					$datasetField->getEnumType() === FieldType::DateTime
					|| $datasetField->getEnumType() === FieldType::Date
				)
				{
					$filterConditions[] = "({$datasetField->getExternalCode()} {$operator} datetime'{$value}')";
				}
			}
		}

		$result = implode(' and ', $filterConditions);

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
		$username = $this->username ?? $this->source?->getSettings()->getValueByCode('username');
		$password = $this->password ?? $this->source?->getSettings()->getValueByCode('password');
		if ($username && $password)
		{
			$client->setAuthorization($username, $password);
		}
		$client->setTimeout($this->requestTimeout);

		return $client;
	}

	private function getHost(): string
	{
		if ($this->host)
		{
			return $this->host;
		}

		$host = $this->source?->getSettings()->getValueByCode('host') ?? '';
		$this->host = $host;

		return $this->host;
	}

	private function get(string $requestedUrl, array $queryParams = []): Result
	{
		$encodedUrl = Uri::urnEncode($this->getHost() . $requestedUrl);
		$url = new Uri($encodedUrl);
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

		$client = $this->getHttpClient();
		$answer = $client->post($url, json_encode($queryParams));

		$responseResult = $this->processResponse($answer, $client);
		$this->processResponseErrors($responseResult);

		return $responseResult;
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

		$responseData = $this->decode($answer);
		if ($client->getStatus() === 200)
		{
			$result->setData([
				...$result->getData(),
				'data' => $responseData,
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
			'Edm.String', 'Edm.Guid' => 'STRING',
			'Edm.Int16', 'Edm.Int32', 'Edm.Int64' => 'INT',
			'Edm.Double' => 'DOUBLE',
			'Edm.DateTime' => 'DATETIME',
			'Edm.Boolean' => 'BOOLEAN',
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
