<?php

namespace Bitrix\BIConnector\Rest;

use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestConnectorTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestTable;
use Bitrix\BIConnector\ExternalSource\SourceSettingType;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Error;

if (!Loader::includeModule('rest'))
{
	return;
}

class Connector extends Base
{
	public const SCOPE = 'biconnector';
	const ALLOWED_SELECT = ['ID', 'TITLE', 'DESCRIPTION', 'DATE_CREATE', 'LOGO', 'URL_CHECK', 'URL_TABLE_LIST', 'URL_TABLE_DESCRIPTION', 'URL_DATA', 'SETTINGS', 'SORT',];

	public static function OnRestServiceBuildDescription()
	{
		return [
			self::SCOPE => [
				'biconnector.connector.add' => [
					'callback' => [__CLASS__, 'add'],
					'options' => [],
				],
				'biconnector.connector.update' => [
					'callback' => [__CLASS__, 'update'],
					'options' => [],
				],
				'biconnector.connector.delete' => [
					'callback' => [__CLASS__, 'delete'],
					'options' => [],
				],
				'biconnector.connector.fields' => [
					'callback' => [__CLASS__, 'fields'],
					'options' => [],
				],
				'biconnector.connector.get' => [
					'callback' => [__CLASS__, 'get'],
					'options' => [],
				],
				'biconnector.connector.list' => [
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
				'title' => 'logo',
				'type' => 'string',
				'isRequired' => true,
				'isReadOnly' => false,
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
				'title' => 'sort',
				'type' => 'integer',
				'isRequired' => false,
				'isReadOnly' => false,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'urlCheck',
				'type' => 'string',
				'isRequired' => true,
				'isReadOnly' => false,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'urlData',
				'type' => 'string',
				'isRequired' => true,
				'isReadOnly' => false,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'urlTableList',
				'type' => 'string',
				'isRequired' => true,
				'isReadOnly' => false,
				'isImmutable' => false,
				'isMultiple' => false,
			],
			[
				'title' => 'urlTableDescription',
				'type' => 'string',
				'isRequired' => true,
				'isReadOnly' => false,
				'isImmutable' => false,
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
			[
				'title' => 'dateCreate',
				'type' => 'datetime',
				'isRequired' => true,
				'isReadOnly' => true,
				'isImmutable' => false,
				'isMultiple' => false,
			],
		];
	}

	protected static function addEntity(array $params): Result
	{
		$result = new Result();
		$converter = new Converter(
			Converter::KEYS
			| Converter::RECURSIVE
			| Converter::TO_SNAKE
			| Converter::TO_UPPER
		);
		$fields = $converter->process($params['fields']);
		$fields['APP_ID'] = $params['appId'];

		$checkSettingsResult = self::checkAndPrepareSettings($fields['SETTINGS']);
		if (!$checkSettingsResult->isSuccess())
		{
			$result->addErrors($checkSettingsResult->getErrors());

			return $result;
		}

		$fields['SETTINGS'] = $checkSettingsResult->getData()['settings'];
		$addResult = ExternalSourceRestConnectorTable::add($fields);

		if (!$addResult->isSuccess())
		{
			$result->addErrors($addResult->getErrors());

			return $result;
		}

		$result->setData(['id' => $addResult->getId()]);

		return $result;
	}

	protected static function updateEntity(array $params): Result
	{
		$result = new Result();
		$converter = new Converter(
			Converter::KEYS
			| Converter::RECURSIVE
			| Converter::TO_SNAKE
			| Converter::TO_UPPER
		);
		$fields = $converter->process($params['fields']);
		$id = (int)$params['id'];

		$connector = ExternalSourceRestConnectorTable::getById($id)->fetchObject();
		if (!$connector || $connector->getAppId() !== $params['appId'])
		{
			$result->addError(new Error('Connector was not found.', 'CONNECTOR_NOT_FOUND'));

			return $result;
		}

		if (isset($fields['SETTINGS']))
		{
			$checkSettingsResult = self::checkAndPrepareSettings($fields['SETTINGS']);
			if (!$checkSettingsResult->isSuccess())
			{
				$result->addErrors($checkSettingsResult->getErrors());

				return $result;
			}

			$fields['SETTINGS'] = $checkSettingsResult->getData()['settings'];
		}

		foreach ($fields as $key => $value)
		{
			$connector->set($key, $value);
		}

		$updateResult = $connector->save();
		if (!$updateResult->isSuccess())
		{
			$result->addErrors($updateResult->getErrors());

			return $result;
		}

		return $result;
	}

	protected static function deleteEntity(array $params): Result
	{
		$result = new Result();
		$id = (int)$params['id'];

		$connector = ExternalSourceRestConnectorTable::getById($id)->fetchObject();
		if (!$connector || $connector->getAppId() !== $params['appId'])
		{
			$result->addError(new Error('Connector was not found.', 'CONNECTOR_NOT_FOUND'));

			return $result;
		}

		$sourceConnectorRelation = ExternalSourceRestTable::getList([
			'select' => ['CONNECTOR_ID'],
			'filter' => [
				'CONNECTOR_ID' => $id,
			],
			'limit' => 1,
		])->fetchCollection();

		if (!$sourceConnectorRelation->isEmpty())
		{
			$result->addError(new Error('Connector cannot be removed. Remove the connections related to the connector first.', 'CONNECTOR_DELETE_RESTRICTED'));

			return $result;
		}

		$deleteResult = $connector->delete();

		if (!$deleteResult->isSuccess())
		{
			$result->addErrors($deleteResult->getErrors());

			return $result;
		}

		return $result;
	}

	protected static function getEntity(array $params): Result
	{
		$result = new Result();
		$id = (int)$params['id'];

		$connector = ExternalSourceRestConnectorTable::getById($id)->fetchObject();
		if (!$connector || $connector->getAppId() !== $params['appId'])
		{
			$result->addError(new Error('Connector was not found.', 'CONNECTOR_NOT_FOUND'));

			return $result;
		}
		$connectorArray = $connector->collectValues();

		if (isset($connectorArray['SETTINGS']))
		{
			$connectorArray['SETTINGS'] = Json::decode($connectorArray['SETTINGS']);
		}

		$connectorArray = array_map('self::formatDateTime', $connectorArray);
		unset($connectorArray['APP_ID']);
		$connectorArray = Converter::toJson()->process($connectorArray);
		$result->setData($connectorArray);

		return $result;
	}

	private static function checkAndPrepareSettings(array $settings): Result
	{
		$result = new Result();

		$resultSettings = [];
		foreach ($settings as $setting)
		{
			if (!isset($setting['TYPE'], $setting['NAME'], $setting['CODE']))
			{
				$result->addError(new Error('Settings must include "type", "name" and "code" fields.', 'VALIDATION_SETTINGS_MISSING_REQUIRED_FIELDS'));
				continue;
			}

			if (mb_strlen($setting['NAME']) > 512)
			{
				$result->addError(new Error('Parameter "name" must be less than 512 characters.', 'VALIDATION_SETTINGS_NAME_TOO_LONG'));
				continue;
			}

			if (mb_strlen($setting['CODE']) > 512)
			{
				$result->addError(new Error('Parameter "code" must be less than 512 characters.', 'VALIDATION_SETTINGS_CODE_TOO_LONG'));
				continue;
			}

			if (SourceSettingType::tryFrom($setting['TYPE']) === null)
			{
				$result->addError(new Error('Parameter "type" is not correct.', 'VALIDATION_SETTINGS_INVALID_TYPE'));
				continue;
			}

			$resultSettings[] = [
				'name' => $setting['NAME'],
				'code' => $setting['CODE'],
				'type' => $setting['TYPE'],
			];
		}

		if ($result->isSuccess())
		{
			$result->setData(['settings' => Json::encode($resultSettings)]);
		}

		return $result;
	}

	protected static function getList(array $params): Result
	{
		$result = new Result();

		$select = $params['select'];
		$filter = $params['filter'];
		$order = $params['order'];
		$offset = $params['offset'];
		$limit = self::ORM_LIMIT;

		$connectorsCollection = ExternalSourceRestConnectorTable::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => $order,
			'offset' => $offset,
			'limit' => $limit,
		])->fetchCollection();

		$connectorsArray = array_values($connectorsCollection->collectValues());
		if (in_array('SETTINGS', $select, true) || in_array('*', $select, true))
		{
			$connectorsArray = array_map(function ($connector) {
				$connector['SETTINGS'] = Json::decode($connector['SETTINGS']);
				return $connector;
			}, $connectorsArray);
		}

		if (
			in_array('*', $select, true)
			|| in_array('DATE_CREATE', $select, true)
		)
		{
			$connectorsArray = array_map(static function($value) {
				return array_map('self::formatDateTime', $value);
			}, $connectorsArray);
		}
		$connectorsArray = Converter::toJson()->process($connectorsArray);
		$result->setData($connectorsArray);

		return $result;
	}

	protected static function prepareSelect(array $select): array
	{
		if ($select === ['*'])
		{
			$select = self::ALLOWED_SELECT;
		}

		return $select;
	}

	protected static function prepareFilter(array $filters, string $appId): array
	{
		$filters['APP_ID'] = $appId;
		return array_filter($filters, static function ($value) {
			return $value !== [];
		});
	}

	protected static function prepareOrder(array $order): array
	{
		return $order;
	}
}
