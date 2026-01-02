<?php

namespace Bitrix\BIConnector\Rest;

use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestConnector;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestConnectorTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\BIConnector\ExternalSource\Source\Rest;
use Bitrix\BIConnector\ExternalSource\SourceManager;
use Bitrix\BIConnector\ExternalSource\Type;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

if (!Loader::includeModule('rest'))
{
	return;
}

class Source extends Base
{
	public const SCOPE = 'biconnector';

	public static function OnRestServiceBuildDescription()
	{
		return [
			self::SCOPE => [
				'biconnector.source.add' => [
					'callback' => [__CLASS__, 'add'],
					'options' => [],
				],
				'biconnector.source.update' => [
					'callback' => [__CLASS__, 'update'],
					'options' => [],
				],
				'biconnector.source.delete' => [
					'callback' => [__CLASS__, 'delete'],
					'options' => [],
				],
				'biconnector.source.fields' => [
					'callback' => [__CLASS__, 'fields'],
					'options' => [],
				],
				'biconnector.source.get' => [
					'callback' => [__CLASS__, 'get'],
					'options' => [],
				],
				'biconnector.source.list' => [
					'callback' => [__CLASS__, 'list'],
					'options' => [],
				],
			]
		];
	}

	protected static function getFields(): array
	{
		return [
			[
				'title' => 'id',
				'type' => 'integer',
				'isRequired' => true,
				'isReadOnly' => true,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'title',
				'type' => 'string',
				'isRequired' => true,
				'isReadOnly' => false,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'type',
				'type' => 'string',
				'isRequired' => true,
				'isReadOnly' => true,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'code',
				'type' => 'string',
				'isRequired' => true,
				'isReadOnly' => true,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'description',
				'type' => 'string',
				'isRequired' => false,
				'isReadOnly' => false,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'active',
				'type' => 'boolean',
				'isRequired' => false,
				'isReadOnly' => false,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'dateCreate',
				'type' => 'datetime',
				'isRequired' => true,
				'isReadOnly' => true,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'dateUpdate',
				'type' => 'datetime',
				'isRequired' => true,
				'isReadOnly' => true,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'createdById',
				'type' => 'integer',
				'isRequired' => true,
				'isReadOnly' => true,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'updatedById',
				'type' => 'integer',
				'isRequired' => true,
				'isReadOnly' => true,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'connectorId',
				'type' => 'integer',
				'isRequired' => true,
				'isReadOnly' => false,
				'isImmutable' => true,
				'isMultiple' => false,
			],
			[
				'title' => 'settings',
				'type' => 'array',
				'isRequired' => true,
				'isReadOnly' => false,
				'isImmutable' => false,
				'isMultiple' => true,
			],
		];
	}

	protected static function addEntity(array $params): Result
	{
		$result = new Result();
		$converter = new Converter(
			Converter::KEYS
			| Converter::TO_SNAKE
		);
		$fields = $converter->process($params['fields']);

		$connectorId = (int)$fields['connector_id'];
		$connector = ExternalSourceRestConnectorTable::getById($connectorId)->fetchObject();
		if (!$connector || $connector->getAppId() !== $params['appId'])
		{
			$result->addError(new Error('Connector was not found.', 'CONNECTOR_NOT_FOUND'));

			return $result;
		}
		$fields['code'] = Type::Rest->value . '_' . $fields['connector_id'];
		$fields['type'] = Type::Rest->value;

		if (!self::checkConnectionBeforeAdd($connector, $fields['settings']))
		{
			$result->addError(new Error('Cannot create connection.', 'SOURCE_CREATE_CONNECTION_ERROR'));

			return $result;
		}

		$addConnectionResult = SourceManager::addConnection($fields);
		if (!$addConnectionResult->isSuccess())
		{
			$result->addErrors($addConnectionResult->getErrors());

			return $result;
		}

		$sourceId = $addConnectionResult->getData()['connection']['id'];
		$result->setData(['id' => $sourceId]);

		return $result;
	}

	protected static function updateEntity(array $params): Result
	{
		$result = new Result();
		$converter = new Converter(
			Converter::KEYS
			| Converter::TO_SNAKE
		);
		$fields = $converter->process($params['fields']);
		$fields['type'] = Type::Rest->value;
		$sourceId = (int)$params['id'];

		$source = ExternalSourceTable::getById($sourceId)->fetchObject();
		if (
			!$source
			|| $source->getType() !== Type::Rest->value
			|| !self::checkPermissionAppBySourceId($params['appId'], $sourceId)
		)
		{
			$result->addError(new Error('Source was not found.', 'SOURCE_NOT_FOUND'));

			return $result;
		}

		if (!self::checkConnectionBeforeUpdate($sourceId, $fields['settings'] ?? []))
		{
			$result->addError(new Error('Cannot update connection.', 'SOURCE_UPDATE_CONNECTION_ERROR'));

			return $result;
		}

		$updateConnectionResult = SourceManager::updateConnection($sourceId, $fields);

		if (!$updateConnectionResult->isSuccess())
		{
			$result->addErrors($updateConnectionResult->getErrors());

			return $result;
		}

		return $result;
	}

	protected static function deleteEntity(array $params): Result
	{
		$result = new Result();
		$id = (int)$params['id'];

		$source = ExternalSourceTable::getById($id)->fetchObject();
		if (
			!$source
			|| $source->getType() !== Type::Rest->value
			|| !self::checkPermissionAppBySourceId($params['appId'], $id)
		)
		{
			$result->addError(new Error('Source was not found.', 'SOURCE_NOT_FOUND'));

			return $result;
		}
		return SourceManager::deleteSource($id);
	}

	protected static function getEntity(array $params): Result
	{
		$result = new Result();
		$sourceId = (int)$params['id'];

		$source = ExternalSourceTable::getById($sourceId)->fetchObject();
		if (
			!$source
			|| $source->getType() !== Type::Rest->value
			|| !self::checkPermissionAppBySourceId($params['appId'], $sourceId)
		)
		{
			$result->addError(new Error('Source was not found.', 'SOURCE_NOT_FOUND'));

			return $result;
		}

		$connection = $source->collectValues();
		$connection = array_map('self::formatDateTime', $connection);

		$sourceConnectorRelation = ExternalSourceRestTable::getList([
			'select' => ['CONNECTOR_ID'],
			'filter' => [
				'SOURCE_ID' => $source->getId(),
			],
			'limit' => 1,
		])->fetchObject();

		if (!$sourceConnectorRelation)
		{
			$result->addError(new Error('Connector was not found.', 'CONNECTOR_NOT_FOUND'));

			return $result;
		}
		$connectorId = $sourceConnectorRelation->getConnectorId();

		$settings = ExternalSourceSettingsTable::getList([
			'select' => ['CODE',  'NAME', 'TYPE', 'VALUE'],
			'filter' => ['SOURCE_ID' => $sourceId],
		])->fetchCollection()->collectValues();

		$settings = array_values($settings);

		$resultArray = [
			'connection' => $connection,
			'connectorId' => $connectorId,
			'settings' => $settings,
		];

		$resultArray = Converter::toJson()->process($resultArray);
		$result->setData($resultArray);

		return $result;
	}

	protected static function getList(array $params): Result
	{
		$result = new Result();

		[$selectSource, $selectSetting] = self::getSelectForSourceAndSettings($params['select']);
		$filter = $params['filter'];
		$order = $params['order'];
		$offset = $params['offset'];
		$limit = self::ORM_LIMIT;

		$sourceCollection = ExternalSourceRestTable::getList([
			'select' => $selectSource,
			'filter' => $filter,
			'order' => $order,
			'offset' => $offset,
			'limit' => $limit,
		])->fetchCollection();

		if ($selectSetting && $sourceIdList = $sourceCollection->getSourceCollection()->getIdList())
		{
			$settingsCollection = ExternalSourceSettingsTable::getList([
				'select' => $selectSetting,
				'filter' => [
					'@SOURCE_ID' => $sourceIdList,
				],
			])->fetchCollection();

			$settingsArray = [];
			foreach ($settingsCollection as $setting)
			{
				$settingsArray[$setting->getSourceId()][] = [
					'code' => $setting->getCode(),
					'name' => $setting->getName(),
					'type' => $setting->getType(),
					'value' => $setting->getValue(),
				];
			}
		}

		$resultArray = [];
		foreach ($sourceCollection as $rawData)
		{
			$item = $rawData->getSource()->collectValues();
			if (in_array('CONNECTOR_ID', $selectSource, true))
			{
				$item['CONNECTOR_ID'] = $rawData->getConnectorId();
			}
			if ($selectSetting)
			{
				$item['SETTINGS'] = $settingsArray[$rawData->getSource()->getId()];
			}
			$resultArray[] = $item;
		}

		if (
			in_array('SOURCE', $selectSource, true)
			|| in_array('DATE_CREATE', $selectSource, true)
			|| in_array('DATE_UPDATE', $selectSource, true)
		)
		{
			$resultArray = array_map(static function($value) {
				return array_map('self::formatDateTime', $value);
			}, $resultArray);
		}

		$resultArray = Converter::toJson()->process($resultArray);
		$result->setData($resultArray);

		return $result;
	}

	private static function checkConnectionBeforeAdd(
		ExternalSourceRestConnector $connector,
		array $settings
	): bool
	{
		$source = new Rest(0);
		$source->setConnector($connector);

		$settingsCollection = ExternalSourceSettingsTable::createCollection();
		foreach ($settings as $code => $value)
		{
			$settingItem = ExternalSourceSettingsTable::createObject();
			$settingItem
				->setCode($code)
				->setValue($value)
			;
			$settingsCollection->add($settingItem);
		}

		if (\Bitrix\Main\Config\Option::get('biconnector', 'biconnector_use_fake_check_test') === 'Y')
		{
			return self::fakeCheckForTest($settingsCollection);
		}

		return $source->connect($settingsCollection)->isSuccess();
	}

	private static function checkConnectionBeforeUpdate(int $sourceId, array $settings): bool
	{
		$source = new Rest($sourceId);

		$settingsCollection = ExternalSourceSettingsTable::getList([
			'filter' => ['=SOURCE_ID' => $sourceId],
		])->fetchCollection();
		foreach ($settings as $code => $value)
		{
			$settingItem = $settingsCollection->getEntityByCode($code);
			if (is_null($settingItem))
			{
				$settingItem = ExternalSourceSettingsTable::createObject();
				$settingItem
					->setCode($code)
					->setValue($value)
				;
				$settingsCollection->add($settingItem);
			}
			else
			{
				$settingItem->setValue($value);
			}
		}

		if (\Bitrix\Main\Config\Option::get('biconnector', 'biconnector_use_fake_check_test', 'N') === 'Y')
		{
			return self::fakeCheckForTest($settingsCollection);
		}

		return $source->connect($settingsCollection)->isSuccess();
	}

	protected static function prepareSelect(array $select): array
	{
		if ($select === ['*'])
		{
			return $select;
		}
		$resultSelect = [];

		foreach ($select as $item)
		{
			$newItem = match ($item){
				'CONNECTOR_ID' => 'CONNECTOR_ID',
				'SETTINGS' => 'SETTINGS',
				default => 'SOURCE.' . $item,
			};
			$resultSelect[] = $newItem;
		}
		$resultSelect = array_filter($resultSelect);

		return $resultSelect;
	}

	protected static function prepareFilter(array $filters, string $appId): array
	{
		$resultFilter = [
			'SOURCE.TYPE' => Type::Rest->value,
			'CONNECTOR.APP_ID' => $appId,
		];
		$pattern = '/^([' . self::ALLOWED_PREFIX_ORM_FILTER . ']*)([A-Z]+)/';

		if ($filters === [])
		{
			return $resultFilter;
		}

		foreach ($filters as $filter => $value)
		{
			$truncatedFilter = ltrim($filter, self::ALLOWED_PREFIX_ORM_FILTER);
			$newFilter = match ($truncatedFilter) {
				'CONNECTOR_ID' => $filter,
				'SETTINGS' => null,
				default => preg_replace($pattern, '$1SOURCE.$2', $filter),
			};

			if (is_null($newFilter) || $value === [])
			{
				continue;
			}
			$resultFilter[$newFilter] = $value;
		}

		return $resultFilter;
	}

	protected static function prepareOrder(array $order): array
	{
		$resultOrder = [];
		if ($order === [])
		{
			return [];
		}

		foreach ($order as $field => $value)
		{
			$newField = match ($field) {
				'CONNECTOR_ID' => $field,
				'SETTINGS' => null,
				default => 'SOURCE.' . $field,
			};
			if (is_null($newField))
			{
				continue;
			}
			$resultOrder[$newField] = $value;
		}

		return $resultOrder;
	}

	private static function getSelectForSourceAndSettings(array $select): array
	{
		$selectSource = ['CONNECTOR_ID', 'SOURCE'];
		$selectSetting = ['SOURCE_ID', 'CODE', 'NAME', 'TYPE', 'VALUE'];
		if ($select === ['*'])
		{
			return [$selectSource, $selectSetting];
		}

		$index = array_search('SETTINGS', $select, true);
		if ($index !== false)
		{
			unset($select[$index]);
		}
		else
		{
			$selectSetting = [];
		}

		$selectSource = $select;

		return [$selectSource, $selectSetting];
	}

	/**
	 * @param $settingsCollection
	 * @return bool
	 */
	protected static function fakeCheckForTest($settingsCollection): bool
	{
		foreach ($settingsCollection as $settingItem)
		{
			if ($settingItem->getCode() === 'token' && $settingItem->getValue() === 'beliberda')
			{
				return true;
			}
		}
		return false;
	}
}
