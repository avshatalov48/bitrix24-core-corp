<?php

namespace Bitrix\Crm\UserField\Types;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use CUserTypeManager;
use Bitrix\Main\UserField\Types\StringType;
use Bitrix\Main\Loader;
use CCrmStatus;

Loc::loadMessages(__FILE__);

/**
 * Class StatusType
 * @package Bitrix\Crm\UserField\Types
 */
class StatusType extends StringType
{
	public const
		USER_TYPE_ID = 'crm_status',
		RENDER_COMPONENT = 'bitrix:crm.field.status';

	public static function getDescription(): array
	{
		return [
			'DESCRIPTION' => Loc::getMessage('USER_TYPE_CRM_STATUS_DESCRIPTION'),
			'BASE_TYPE' => CUserTypeManager::BASE_TYPE_STRING,
		];
	}

	/**
	 * @param array $userField
	 * @return array
	 */
	public static function prepareSettings(array $userField): array
	{
		Loader::includeModule('crm');

		$entityTypes = CCrmStatus::GetEntityTypes();
		$entityType = $userField['SETTINGS']['ENTITY_TYPE'];
		//fool-proof
		if(is_array($entityType))
		{
			$entityType = ($entityType['ID'] ?? '');
		}

		return [
			'ENTITY_TYPE' => (
			isset($entityTypes[$entityType]) ? $entityType : array_shift($entityTypes)
			)
		];
	}


	/**
	 * @param array $userField
	 * @param array|string $value
	 * @return array
	 */
	public static function checkFields(array $userField, $value): array
	{
		return [];
	}

	public static function onSearchIndex(array $userField): ?string
	{
		if(is_array($userField['VALUE']))
		{
			$result = implode("\r\n", $userField['VALUE']);
		}
		else
		{
			$result = $userField['VALUE'];
		}
		return $result;
	}

	public static function getStatusList(array &$userField, array $additionalParameters = []): void
	{
		$results = (static::getList($userField))->arResult;

		$fields = [
			null => Loc::getMessage('MAIN_NO')
		];

		foreach ($results as $result){
			$fields[$result['ID']] = $result['VALUE'];
		}
		$userField['USER_TYPE']['FIELDS'] = $fields;
	}

	/**
	 * @param array $userField
	 * @return bool|\CDBResult
	 */
	public static function getList(array $userField)
	{
		$result = false;

		if(Loader::includeModule('crm'))
		{
			$list = [];
			$entityType = $userField['SETTINGS']['ENTITY_TYPE'];

			//fool-proof
			if(is_array($entityType))
			{
				$entityType = ($entityType['ID'] ?? '');
			}
			$statuses = CCrmStatus::GetStatus($entityType);

			foreach($statuses as $status)
			{
				$list[] = [
					'ID' => $status['STATUS_ID'],
					'VALUE' => $status['NAME']
				];
			}

			$result = new \CDBResult();
			$result->InitFromArray($list);
		}

		return $result;
	}

	public static function renderEdit(array $userField, ?array $additionalParameters = []): string
	{
		self::getStatusList($userField);
		return parent::renderEdit($userField, $additionalParameters);
	}

	public static function renderView(array $userField, ?array $additionalParameters = []): string
	{
		self::getStatusList($userField);
		return parent::renderView($userField, $additionalParameters);
	}

	/**
	 * @array $userField
	 * @param $userField
	 * @return string
	 */
	public static function getEmptyCaption(array $userField): string
	{
		return (
		$userField['SETTINGS']['CAPTION_NO_VALUE'] != '' ?
			HtmlFilter::encode($userField['SETTINGS']['CAPTION_NO_VALUE']) :
			Loc::getMessage('USER_TYPE_CRM_STATUS_NO_VALUE')
		);
	}
}