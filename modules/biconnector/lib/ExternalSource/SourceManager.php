<?php

namespace Bitrix\BIConnector\ExternalSource;

use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;

class SourceManager
{
	/**
	 * Keys - connection type - 1c, mysql, pgsql etc.
	 * Values - array with fields required to connect to database.
	 *
	 * @return array[]
	 */
	public static function getFieldsConfig(): array
	{
		$result = [];
		if (self::is1cConnectionsAvailable())
		{
			$result['1c'] = [
				[
					'name' => Loc::getMessage('EXTERNAL_CONNECTION_FIELD_HOST'),
					'type' => ExternalSource\SourceSettingType::String->value,
					'code' => 'host',
					'placeholder' => 'http://localhost_23740259475',
				],
				[
					'name' => Loc::getMessage('EXTERNAL_CONNECTION_FIELD_USERNAME'),
					'type' => ExternalSource\SourceSettingType::String->value,
					'code' => 'username',
					'placeholder' => 'user@mail.com',
				],
				[
					'name' => Loc::getMessage('EXTERNAL_CONNECTION_FIELD_PASSWORD'),
					'type' => ExternalSource\SourceSettingType::String->value,
					'code' => 'password',
					'placeholder' => Loc::getMessage('EXTERNAL_CONNECTION_FIELD_PASSWORD'),
				],
			];
		}

		if (Loader::includeModule('rest'))
		{
			$restConnectors = ExternalSource\Internal\ExternalSourceRestConnectorTable::getList([
				'select' => ['ID', 'SETTINGS'],
			]);

			while ($restConnector = $restConnectors->fetchObject())
			{
				try {
					$settings = Json::decode($restConnector['SETTINGS']);
					if (is_array($settings))
					{
						$sourceCode = $restConnector->getCode();
						$result[$sourceCode] = [];
						foreach ($settings as $setting)
						{
							$result[$sourceCode][] = [
								'name' => $setting['name'],
								'type' => $setting['type'],
								'code' => $setting['code'],
								'placeholder' => Loc::getMessage('EXTERNAL_CONNECTION_FIELD_REST_EXTERNAL_PARAM_PLACEHOLDER'),
							];
						}
					}
				}
				catch (\Exception)
				{
				}
			}
		}

		return $result;
	}

	/**
	 * @return array[] List of databases to show in selector on create connection slider.
	 */
	public static function getSupportedDatabases(): array
	{
		$result = [];
		if (self::is1cConnectionsAvailable())
		{
			$result[] = [
				'code' => ExternalSource\Type::Source1C->value,
				'type' => ExternalSource\Type::Source1C->value,
				'name' => '1C',
			];
		}

		if (Loader::includeModule('rest'))
		{
			$restSources = ExternalSource\Internal\ExternalSourceRestConnectorTable::getList([
				'select' => ['TITLE', 'ID'],
			]);
			while ($restSource = $restSources->fetchObject())
			{
				$result[] = [
					'code' => $restSource->getCode(),
					'type' => ExternalSource\Type::Rest->value,
					'name' => $restSource->getTitle(),
				];
			}
		}

		return $result;
	}

	public static function is1cConnectionsAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return in_array($region, ['ru', 'by', 'kz']);
	}

	public static function isExternalConnectionsAvailable(): bool
	{
		return count(self::getSupportedDatabases()) > 0;
	}

	public static function addConnection(array $data): Result
	{
		$result = new Result();

		$checkResult = self::prepareBeforeAdd($data);

		if (!$checkResult->isSuccess())
		{
			$result->addErrors($checkResult->getErrors());

			return $result;
		}

		$checkedData = $checkResult->getData();

		/** @var ExternalSource\Type $type */
		$type = $checkedData['type'];
		$userId = (int)CurrentUser::get()->getId();

		$db = Application::getInstance()->getConnection();
		try
		{
			$db->startTransaction();

			$source = ExternalSourceTable::createObject();
			$source
				->setDateCreate(new DateTime())
				->setCreatedById($userId)
				->setType($type->value)
				->setCode($data['code'])
				->setActive('Y')
				->setTitle($data['title'])
				->setDateUpdate(new DateTime())
				->setUpdatedById($userId)
			;

			if (isset($data['description']))
			{
				$source->setDescription($data['description']);
			}

			$saveResult = $source->save();
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
				$db->rollbackTransaction();

				return $result;
			}

			$saveSettingsResult = self::saveConnectionSettings($source, $data);
			if (!$saveSettingsResult->isSuccess())
			{
				$result->addErrors($saveSettingsResult->getErrors());
				$db->rollbackTransaction();

				return $result;
			}

			if ($type === Type::Rest)
			{
				$connectorId = (int)str_replace('rest_', '', $data['code']);
				$addConnectorToSourceResult = ExternalSourceRestTable::add([
					'SOURCE_ID' => $source->getId(),
					'CONNECTOR_ID' => $connectorId,
				]);

				if (!$addConnectorToSourceResult->isSuccess())
				{
					$result->addErrors($addConnectorToSourceResult->getErrors());
					$db->rollbackTransaction();

					return $result;
				}

				$avatar = ExternalSourceRestTable::getList([
					'select' => ['CONNECTOR.LOGO'],
					'filter' => [
						'CONNECTOR_ID' => $connectorId,
					],
					'limit' => 1
				])->fetchObject()->getConnector()->getLogo();
			}

			$connection = [
				'id' => $source->getId(),
				'name' => htmlspecialcharsbx($source->getTitle()),
				'type' => $source->getType(),
			];

			if ($type === Type::Rest)
			{
				$connection['avatar'] = $avatar;
			}

			$db->commitTransaction();

			$result->setData([
				'connection' => $connection,
			]);

			return $result;
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage()));
			$db->rollbackTransaction();

			return $result;
		}
	}

	private static function prepareBeforeAdd(array $data): Result
	{
		$result = new Result();

		if (empty($data['title'] ?? null))
		{
			$result->addError(new Error(Loc::getMessage('EXTERNAL_CONNECTION_ERROR_FIELDS_INCOMPLETE')));

			return $result;
		}

		$type = ExternalSource\Type::tryFrom($data['type']);
		if (!$type)
		{
			$result->addError(
				new Error(Loc::getMessage('EXTERNAL_CONNECTION_ERROR_UNKNOWN_TYPE', [
					'#CONNECTION_TYPE#' => htmlspecialcharsbx($data['type']),
				])),
			);

			return $result;
		}

		$result->setData([
			'type' => $type,
		]);

		return $result;
	}

	public static function updateConnection(int $sourceId, array $data): Result
	{
		$result = new Result();

		$checkResult = self::prepareBeforeUpdate($data);
		if (!$checkResult->isSuccess())
		{
			$result->addErrors($checkResult->getErrors());

			return $result;
		}

		$db = Application::getInstance()->getConnection();
		try
		{
			$db->startTransaction();
			$source = ExternalSourceTable::getById($sourceId)->fetchObject();
			if (!$source)
			{
				$result->addError(new Error(Loc::getMessage('EXTERNAL_CONNECTION_ERROR_NOT_FOUND')));

				return $result;
			}
			$userId = (int)CurrentUser::get()->getId();

			if (!isset($data['title']))
			{
				$data['title'] = $source->getTitle();
			}

			$source
				->setTitle($data['title'])
				->setDateUpdate(new DateTime())
				->setUpdatedById($userId)
			;

			if (isset($data['description']))
			{
				$source->setDescription($data['description']);
			}

			if ($source->getType() === Type::Rest->value)
			{
				$connectorId = ExternalSourceRestTable::getList([
					'filter' => [
						'SOURCE_ID' => $sourceId,
					],
					'limit' => 1,
				])->fetchObject()->getConnectorId();

				$data['connector_id'] = $connectorId;
			}

			$saveSettingsResult = self::saveConnectionSettings($source, $data);
			if (!$saveSettingsResult->isSuccess())
			{
				$result->addErrors($saveSettingsResult->getErrors());
				$db->rollbackTransaction();

				return $result;
			}

			$saveResult = $source->save();
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
				$db->rollbackTransaction();

				return $result;
			}

			$db->commitTransaction();

			$result->setData([
				'connection' => [
					'id' => $source->getId(),
					'name' => htmlspecialcharsbx($source->getTitle()),
					'type' => $source->getType(),
				],
			]);

			return $result;
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage()));
			$db->rollbackTransaction();

			return $result;
		}
	}

	private static function prepareBeforeUpdate(array $data): Result
	{
		$result = new Result();

		if (empty($data['title'] ?? null))
		{
			$result->addError(new Error(Loc::getMessage('EXTERNAL_CONNECTION_ERROR_FIELDS_INCOMPLETE')));

			return $result;
		}

		return $result;
	}

	protected static function saveConnectionSettings(ExternalSource\Internal\ExternalSource $source, array $data): Result
	{
		$result = new Result();

		$checkResult = self::prepareConnectionSettings($source, $data);
		if (!$checkResult->isSuccess())
		{
			$result->addErrors($checkResult->getErrors());

			return $result;
		}

		$settings = $checkResult->getData()['settings'];
		$existSettings = ExternalSourceSettingsTable::getList([
			'filter' => ['=SOURCE_ID' => $source->getId()],
		])->fetchCollection();

		if ($source->getType() === Type::Rest->value)
		{
			$deleteSettingsResult = self::removeUnusedSettings($source->getId(), $existSettings);
			if (!$deleteSettingsResult->isSuccess())
			{
				$result->addErrors($deleteSettingsResult->getErrors());

				return $result;
			}
		}

		foreach ($settings as $settingData)
		{
			$settingItem = $existSettings->getEntityByCode($settingData['code']);
			if (is_null($settingItem))
			{
				$settingItem = ExternalSourceSettingsTable::createObject();
				$settingItem
					->setCode($settingData['code'])
					->setValue($settingData['value'])
					->setName($settingData['name'])
					->setType($settingData['type'])
					->setSourceId($source->getId())
				;
			}
			else
			{
				$settingItem->setValue($settingData['value']);
			}

			$saveSettingResult = $settingItem->save();
			if (!$saveSettingResult->isSuccess())
			{
				$result->addErrors($saveSettingResult->getErrors());

				return $result;
			}
		}

		return $result;
	}

	protected static function prepareConnectionSettings(ExternalSource\Internal\ExternalSource $source, array $data): Result
	{
		$result = new Result();
		$settings = [];
		if ($source->getType() !== Type::Rest->value)
		{
			$configKey = $source->getType();
		}
		else
		{
			$configKey = $data['code'] ?? $source->getType() . '_' . $data['connector_id'];
			if (!empty($data['settings']))
			{
				$data = $data['settings'];
			}
		}

		$requiredFields = self::getFieldsConfig()[$configKey] ?? [];
		foreach ($requiredFields as $requiredField)
		{
			if (isset($data[$requiredField['code']]))
			{
				$settings[] = [
					'code' => $requiredField['code'],
					'name' => $requiredField['name'],
					'value' => trim($data[$requiredField['code']]),
					'type' => $requiredField['type'],
				];
			}
			elseif ($source->getType() !== Type::Rest->value)
			{
				$result->addError(new Error(Loc::getMessage('EXTERNAL_CONNECTION_ERROR_FIELDS_INCOMPLETE')));

				return $result;
			}
		}

		$result->setData([
			'settings' => $settings,
		]);

		return $result;
	}

	public static function getSourceSettings(ExternalSource\Internal\ExternalSource $source): ExternalSourceSettingsCollection
	{
		return ExternalSourceSettingsTable::getList([
			'filter' => [
				'=SOURCE_ID' => $source->getId(),
			],
		])
			->fetchCollection()
		;
	}

	public static function deleteSource(int $id): Result
	{
		$result = new Result();
		$source = ExternalSourceTable::getById($id)->fetchObject();
		if (!$source)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SOURCE_ERROR_NOT_FOUND')));

			return $result;
		}

		$db = Application::getInstance()->getConnection();
		try
		{
			$db->startTransaction();

			if ($source->getType() === Type::Rest->value)
			{
				$sourceConnectorRelation = ExternalSourceRestTable::getList([
					'select' => ['ID'],
					'filter' => [
						'SOURCE_ID' => $source->getId(),
					],
					'limit' => 1,
				])->fetchObject();

				if ($sourceConnectorRelation)
				{
					$deleteResult = $sourceConnectorRelation->delete();
					if (!$deleteResult->isSuccess())
					{
						$result->addErrors($deleteResult->getErrors());
						$db->rollbackTransaction();

						return $result;
					}
				}
			}

			$deleteResult = $source->delete();
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
				$db->rollbackTransaction();

				return $result;
			}

			$db->commitTransaction();

		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage()));
			$db->rollbackTransaction();

			return $result;
		}

		return $result;
	}

	private static function removeUnusedSettings(
		int $sourceId,
		Internal\ExternalSourceSettingsCollection $existSettings,
	): Result
	{
		$result = new Result();

		$connector = ExternalSourceRestTable::getList([
			'select' => ['CONNECTOR.SETTINGS'],
			'filter' => ['=SOURCE_ID' => $sourceId],
			'limit' => 1,
		])->fetchObject();

		if (!$connector) //in this case it is an add method, and we don't have a connector yet
		{
			return $result;
		}

		$connectorSettings = Json::decode($connector->getConnector()->getSettings());
		$settingsCodeList = array_column($connectorSettings, 'code');
		foreach ($existSettings as $setting)
		{
			if (!in_array($setting->getCode(), $settingsCodeList, true))
			{
				$deleteSettingsResult = $setting->delete();
				if (!$deleteSettingsResult->isSuccess())
				{
					$result->addErrors($deleteSettingsResult->getErrors());

					return $result;
				}
			}
		}

		return $result;
	}
}
