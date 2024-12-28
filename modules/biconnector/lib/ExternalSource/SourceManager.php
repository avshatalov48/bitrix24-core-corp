<?php

namespace Bitrix\BIConnector\ExternalSource;

use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

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
					'type' => ExternalSourceSettingsTable::SETTING_TYPE_STRING,
					'code' => 'host',
					'placeholder' => 'http://localhost_23740259475',
				],
				[
					'name' => Loc::getMessage('EXTERNAL_CONNECTION_FIELD_USERNAME'),
					'type' => ExternalSourceSettingsTable::SETTING_TYPE_STRING,
					'code' => 'username',
					'placeholder' => 'user@mail.com',
				],
				[
					'name' => Loc::getMessage('EXTERNAL_CONNECTION_FIELD_PASSWORD'),
					'type' => ExternalSourceSettingsTable::SETTING_TYPE_STRING,
					'code' => 'password',
					'placeholder' => Loc::getMessage('EXTERNAL_CONNECTION_FIELD_PASSWORD'),
				],
			];
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
				'name' => '1C',
			];
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
				->setCode($type->value)
				->setActive('Y')
				->setTitle($data['title'])
				->setDateUpdate(new DateTime())
				->setUpdatedById($userId)
			;

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
			$source = ExternalSourceTable::getList([
				'filter' => ['ID' => $sourceId],
				'limit' => 1,
			])
				->fetchObject()
			;
			$userId = (int)CurrentUser::get()->getId();

			$source
				->setTitle($data['title'])
				->setDateUpdate(new DateTime())
				->setUpdatedById($userId)
			;

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

	private static function saveConnectionSettings(ExternalSource\Internal\ExternalSource $source, array $data): Result
	{
		$result = new Result();

		$source->removeAllSettings();

		$checkResult = self::prepareConnectionSettings($source, $data);
		if (!$checkResult->isSuccess())
		{
			$result->addErrors($checkResult->getErrors());

			return $result;
		}

		$settings = $checkResult->getData()['settings'];

		foreach ($settings as $settingData)
		{
			$settingItem = ExternalSourceSettingsTable::createObject();
			$settingItem
				->setCode($settingData['code'])
				->setValue($settingData['value'])
				->setName($settingData['name'])
				->setType($settingData['type'])
				->setSourceId($source->getId())
			;
			$saveSettingResult = $settingItem->save();

			if (!$saveSettingResult->isSuccess())
			{
				$result->addErrors($saveSettingResult->getErrors());

				return $result;
			}
		}

		return $result;
	}

	private static function prepareConnectionSettings(ExternalSource\Internal\ExternalSource $source, array $data): Result
	{
		$result = new Result();
		$settings = [];
		$requiredFields = self::getFieldsConfig()[$source->getType()] ?? [];
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
			else
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
}
