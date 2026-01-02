<?php

namespace Bitrix\BIConnector\Rest;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestTable;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use CRestServer;
use IRestService;

abstract class Base extends IRestService
{
	protected const ALLOWED_PREFIX_ORM_FILTER = '<>=!@%';
	protected const ORM_LIMIT = 50;
	protected const ACCESS_ERROR = [
		'error' => 'ACCESS_DENIED',
		'error_description' => "Access denied.",
	];

	abstract protected static function getFields(): array;
	abstract protected static function addEntity(array $params): Result;
	abstract protected static function updateEntity(array $params): Result;
	abstract protected static function deleteEntity(array $params): Result;
	abstract protected static function getEntity(array $params): Result;
	abstract protected static function getList(array $params): Result;
	abstract protected static function prepareSelect(array $select): array;
	abstract protected static function prepareFilter(array $filters, string $appId): array;
	abstract protected static function prepareOrder(array $order): array;

	public static function add(array $params, $n, CRestServer $server): array
	{
		Loc::setCurrentLang('en');
		if (!self::checkPermission())
		{
			return ['error' => self::ACCESS_ERROR];
		}

		$checkAppIdResult = self::checkAndPrepareAppId($server);
		if (!$checkAppIdResult->isSuccess())
		{
			return ['error' => self::ACCESS_ERROR];
		}
		$params['appId'] = $checkAppIdResult->getData()['appId'];
		$validateResult = static::validateParamsForAdd($params);

		if (!$validateResult->isSuccess())
		{
			$error = $validateResult->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		$result = static::addEntity($params);
		if (!$result->isSuccess())
		{
			$error = $result->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		return ['id' => $result->getData()['id']];
	}

	public static function update(array $params, $n, CRestServer $server): array|bool
	{
		Loc::setCurrentLang('en');
		if (!self::checkPermission())
		{
			return ['error' => self::ACCESS_ERROR];
		}

		$checkAppIdResult = self::checkAndPrepareAppId($server);
		if (!$checkAppIdResult->isSuccess())
		{
			return ['error' => self::ACCESS_ERROR];
		}
		$params['appId'] = $checkAppIdResult->getData()['appId'];
		$validateResult = self::validateParamsForUpdate($params);

		if (!$validateResult->isSuccess())
		{
			$error = $validateResult->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		$result = static::updateEntity($params);
		if (!$result->isSuccess())
		{
			$error = $result->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		return true;
	}

	public static function delete(array $params, $n, CRestServer $server): array|bool
	{
		Loc::setCurrentLang('en');
		if (!self::checkPermission())
		{
			return ['error' => self::ACCESS_ERROR];
		}

		$checkAppIdResult = self::checkAndPrepareAppId($server);
		if (!$checkAppIdResult->isSuccess())
		{
			return ['error' => self::ACCESS_ERROR];
		}
		$params['appId'] = $checkAppIdResult->getData()['appId'];
		$validateResult = self::validateParamsId($params);

		if (!$validateResult->isSuccess())
		{
			$error = $validateResult->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		$result = static::deleteEntity($params);
		if (!$result->isSuccess())
		{
			$error = $result->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		return true;
	}

	public static function fields(array $params, $n, CRestServer $server): array
	{
		if (!self::checkPermission())
		{
			return ['error' => self::ACCESS_ERROR];
		}

		return ['fields' => static::getFields()];
	}

	public static function get(array $params, $n, CRestServer $server): array
	{
		Loc::setCurrentLang('en');
		if (!self::checkPermission())
		{
			return ['error' => self::ACCESS_ERROR];
		}

		$checkAppIdResult = self::checkAndPrepareAppId($server);
		if (!$checkAppIdResult->isSuccess())
		{
			return ['error' => self::ACCESS_ERROR];
		}
		$params['appId'] = $checkAppIdResult->getData()['appId'];
		$validateResult = static::validateParamsId($params);

		if (!$validateResult->isSuccess())
		{
			$error = $validateResult->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		$result = static::getEntity($params);
		if (!$result->isSuccess())
		{
			$error = $result->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		return ['item' => $result->getData()];
	}

	public static function list(array $params, $n, CRestServer $server): array
	{
		Loc::setCurrentLang('en');
		if (!self::checkPermission())
		{
			return ['error' => self::ACCESS_ERROR];
		}

		$checkAppIdResult = self::checkAndPrepareAppId($server);
		if (!$checkAppIdResult->isSuccess())
		{
			return ['error' => self::ACCESS_ERROR];
		}
		$params['appId'] = $checkAppIdResult->getData()['appId'];
		$validateResult = static::validateParamsForList($params);

		if (!$validateResult->isSuccess())
		{
			$error = $validateResult->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		$params = static::prepareOrmParams($params);

		$result = static::getList($params);
		if (!$result->isSuccess())
		{
			$error = $result->getErrors()[0];

			return ['error' => [
				'error' => $error->getCode(),
				'error_description' => $error->getMessage(),
			]];
		}

		return $result->getData();
	}

	private static function validateParamsForAdd(array $params): Result
	{
		$result = new Result();
		$fields = $params['fields'] ?? [];
		$requiredFields = static::getFields();

		$validateFieldsResult = self::validateFields($fields, $requiredFields, true);
		if (!$validateFieldsResult->isSuccess())
		{
			return $validateFieldsResult;
		}

		return $result;
	}

	private static function validateParamsForUpdate(array $params): Result
	{
		$result = new Result();
		$fields = $params['fields'] ?? [];
		$requiredFields = static::getFields();

		$validateIdResult = self::validateParamsId($params);
		if (!$validateIdResult->isSuccess())
		{
			return $validateIdResult;
		}

		$validateFieldsResult = self::validateFields($fields, $requiredFields, false);
		if (!$validateFieldsResult->isSuccess())
		{
			return $validateFieldsResult;
		}

		return $result;
	}

	protected static function validateParamsId(array $params): Result
	{
		$result = new Result();

		if (!array_key_exists('id', $params))
		{
			$result->addError(new Error('ID is missing.', 'VALIDATION_ID_NOT_PROVIDED'));

			return $result;
		}

		if (!is_numeric($params['id']) || (int)$params['id'] <= 0)
		{
			$result->addError(new Error('ID has to be a positive integer.', 'VALIDATION_INVALID_ID_FORMAT'));
		}

		return $result;
	}

	/**
	 * @param array $fields
	 * @param array $requiredFields
	 * @param bool $strictCheck
	 * @return Result
	 */
	private static function validateFields(
		mixed $fields,
		array $requiredFields,
		bool $strictCheck,
	): Result
	{
		$result = new Result();

		if (empty($fields) || !is_array($fields))
		{
			$result->addError(new Error('Fields not provided.', 'VALIDATION_FIELDS_NOT_PROVIDED'));

			return $result;
		}

		$actualFieldsTitles = array_keys($fields);
		$requiredFieldsTitles = array_column($requiredFields, 'title');
		$unknownParams = array_diff($actualFieldsTitles, $requiredFieldsTitles);
		if ($unknownParams !== [])
		{
			$errorMess = 'Unknown parameters: ' . implode(', ', $unknownParams);
			$result->addError(new Error($errorMess, 'VALIDATION_UNKNOWN_PARAMETERS'));
		}

		foreach ($requiredFields as $field)
		{
			$title = $field['title'];
			$type = $field['type'];
			$isRequired = $field['isRequired'];
			$isReadOnly = $field['isReadOnly'];
			$isImmutable = $field['isImmutable'];

			if (!array_key_exists($title, $fields))
			{
				if ($strictCheck && $isRequired && !$isReadOnly)
				{
					$errorMess = "Field \"{$title}\" is required.";
					$result->addError(new Error($errorMess, 'VALIDATION_REQUIRED_FIELD_MISSING'));
				}
				continue;
			}

			if ($isReadOnly)
			{
				$errorMess = "Field \"{$title}\" is read only.";
				$result->addError(new Error($errorMess, 'VALIDATION_READ_ONLY_FIELD'));
				continue;
			}

			if (!$strictCheck && $isImmutable)
			{
				$errorMess = "Field \"{$title}\" is immutable.";
				$result->addError(new Error($errorMess, 'VALIDATION_IMMUTABLE_FIELD'));
				continue;
			}

			$value = $fields[$title];

			if (
				($type === 'integer' && !is_numeric($value))
				|| ($type === 'string' && !is_string($value))
				|| ($type === 'array' && !is_array($value))
				|| ($type === 'boolean' && !is_bool($value))
			)
			{
				$errorMess = "Field \"{$title}\" must be of type {$type}.";
				$result->addError(new Error($errorMess, 'VALIDATION_INVALID_FIELD_TYPE'));
			}
		}

		return $result;
	}

	protected static function formatDateTime($value): mixed
	{
		if ($value instanceof DateTime)
		{
			return $value->format('Y-m-d H:i:s');
		}

		if ($value instanceof Date)
		{
			return $value->format('Y-m-d');
		}

		return $value;
	}

	private static function validateParamsForList(array $params): Result
	{
		$result = new Result();
		$allowedColumns = array_column(static::getFields(), 'title');

		if (isset($params['select']))
		{
			if (!is_array($params['select']))
			{
				$result->addError(new Error('Parameter "select" must be array.', 'VALIDATION_SELECT_TYPE'));

				return $result;
			}
			$allowedSelect = $allowedColumns;
			$allowedSelect[] = '*';
			$select = $params['select'];
			foreach ($select as $field)
			{
				if (!in_array($field, $allowedSelect, true))
				{
					$errorMess = "Field \"{$field}\" is not allowed in the \"select\".";
					$result->addError(new Error($errorMess, 'VALIDATION_FIELD_NOT_ALLOWED_IN_SELECT'));
				}
			}
		}

		if (isset($params['filter']))
		{
			if (!is_array($params['filter']))
			{
				$result->addError(new Error('Parameter "filter" must be array.', 'VALIDATION_FILTER_TYPE'));

				return $result;
			}
			$filter = $params['filter'];
			$validateFilterResult = self::validateFilter($filter, $allowedColumns);
			if (!$validateFilterResult->isSuccess())
			{
				$result->addErrors($validateFilterResult->getErrors());
			}
		}

		if (isset($params['order']))
		{
			if (!is_array($params['order']))
			{
				$result->addError(new Error('Parameter "order" must be array.', 'VALIDATION_ORDER_TYPE'));

				return $result;
			}
			$order = $params['order'];
			foreach ($order as $field => $value)
			{
				if (!in_array($field, $allowedColumns, true))
				{
					$errorMess = "Field \"{$field}\" is not allowed in the \"order\".";
					$result->addError(new Error($errorMess, 'VALIDATION_FIELD_NOT_ALLOWED_IN_ORDER'));
				}
			}
		}

		return $result;
	}

	private static function validateFilter(array $filter, array $allowedColumns): Result
	{
		$result = new Result();

		foreach ($filter as $key => $value)
		{
			if ($key === 'logic')
			{
				if (!in_array(strtoupper($value), ['AND', 'OR']))
				{
					$result->addError(new Error(
						'Field "logic" must be either "AND" or "OR".',
						'VALIDATION_INVALID_FILTER_LOGIC'
					));
				}
				continue;
			}

			if (is_array($value))
			{
				$validateFilterRecursionResult = self::validateFilter($value, $allowedColumns);
				if (!$validateFilterRecursionResult->isSuccess())
				{
					$result->addErrors($validateFilterRecursionResult->getErrors());
				}
				continue;
			}

			$field = ltrim($key, self::ALLOWED_PREFIX_ORM_FILTER);

			if (
				!(is_numeric($value) && is_numeric($field))
				&& !in_array($field, $allowedColumns, true)
			)
			{
				$errorMess = "Field \"{$field}\" is not allowed in the \"filter\".";
				$result->addError(new Error($errorMess, 'VALIDATION_FIELD_NOT_ALLOWED_IN_FILTER'));
			}
		}

		return $result;
	}

	private static function prepareOrmParams($params)
	{
		$converterSelect = new Converter(
			Converter::VALUES
			| Converter::TO_SNAKE
			| Converter::TO_UPPER
			| Converter::RECURSIVE
		);
		$converterFilter = new Converter(
			Converter::KEYS
			| Converter::TO_SNAKE
			| Converter::TO_UPPER
			| Converter::RECURSIVE
		);
		$converterOrder = $converterFilter;

		if (!isset($params['select']) || $params['select'] === [])
		{
			$params['select'] = ['*'];
		}
		$params['select'] = static::prepareSelect($converterSelect->process($params['select']));

		if (!isset($params['filter']))
		{
			$params['filter'] = [];
		}
		$params['filter'] = static::prepareFilter($converterFilter->process($params['filter']), $params['appId']);

		if (!isset($params['order']))
		{
			$params['order'] = [];
		}
		$params['order'] = static::prepareOrder($converterOrder->process($params['order']));

		if (!isset($params['page']) || !is_numeric($params['page']) || (int)$params['page'] <= 1)
		{
			$params['offset'] = 0;
		}
		else
		{
			$params['offset'] = ((int)$params['page'] - 1) * self::ORM_LIMIT;
		}

		return $params;
	}

	protected static function checkPermission(): bool
	{
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS))
		{
			return false;
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG))
		{
			return false;
		}

		return true;
	}

	protected static function checkAndPrepareAppId(CRestServer $server): Result
	{
		$result = new Result();
		$appId = $server->getClientId();

		if (\Bitrix\Main\Config\Option::get('biconnector', 'biconnector_use_webhooks_rest_source', 'N') === 'Y')
		{
			$appId = $appId ?? 'test_app_id'; //stub for test
		}

		if (empty($appId))
		{
			$result->addError(new Error('Webhook calls are not permitted for this operation'));

			return $result;
		}

		$result->setData(['appId' => $appId]);

		return $result;
	}

	protected static function checkPermissionAppBySourceId(string $appId, int $sourceId): bool
	{
		$connector = ExternalSourceRestTable::getList([
			'select' => ['CONNECTOR.APP_ID'],
			'filter' => [
				'SOURCE_ID' => $sourceId,
				'CONNECTOR.APP_ID' => $appId,
			],
			'limit' => 1,
		])
			->fetchObject()
		;

		return !is_null($connector);
	}

	protected static function checkPermissionAppByDatasetId(string $appId, int $datasetId): bool
	{
		$sourceId = ExternalSourceDatasetRelationTable::getList([
			'select' => ['SOURCE_ID'],
			'filter' => [
				'DATASET_ID' => $datasetId,
			],
			'limit' => 1,
		])
			->fetchObject()
			->getSourceId()
		;

		return self::checkPermissionAppBySourceId($appId, $sourceId);
	}
}
