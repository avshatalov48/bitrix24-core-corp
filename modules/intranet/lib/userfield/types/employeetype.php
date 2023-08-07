<?php

namespace Bitrix\Intranet\UserField\Types;

use Bitrix\Main\UserField\Types\BaseType;
use Bitrix\Main\Localization\Loc;
use CUserTypeManager;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

/**
 * Class EmployeeType
 * @package Bitrix\Intranet\UserField\Types
 */
class EmployeeType extends BaseType
{
	public const
		USER_TYPE_ID = 'employee',
		RENDER_COMPONENT = 'bitrix:intranet.field.employee';

	public static function getDescription(): array
	{
		return [
			'DESCRIPTION' => Loc::getMessage('INTRANET_PROPERTY_TITLE_MSGVER_1'),
			'BASE_TYPE' => CUserTypeManager::BASE_TYPE_ENUM,
		];
	}

	/**
	 * @return string
	 */
	public static function getDbColumnType(): string
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		return $helper->getColumnTypeByField(new \Bitrix\Main\ORM\Fields\IntegerField('x'));
	}

	/**
	 * @param array $userField
	 * @return array
	 */
	public static function prepareSettings(array $userField): array
	{
		return [];
	}

	/**
	 * @param array $userField
	 * @param string|array $value
	 * @return array
	 */
	public static function checkFields(array $userField, $value): array
	{
		return [];
	}

	/**
	 * @param array|bool $userField
	 * @param array|null $additionalParameters
	 * @param $varsFromForm
	 * @return string
	 */
	public static function getSettingsHtml($userField, ?array $additionalParameters, $varsFromForm): string
	{
		return '';
	}

	/**
	 * @param array $userField
	 * @return string|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function onSearchIndex(array $userField): ?string
	{
		$res = null;

		if(is_array($userField['VALUE']))
		{
			$values = $userField['VALUE'];
		}
		else
		{
			$values = [$userField['VALUE']];
		}

		$isSearchModuleIncluded = Loader::includeModule('search');

		$values = array_filter($values, 'intval');

		if(count($values))
		{
			foreach($values as $value)
			{
				$users = \CUser::GetList('', '', ['ID' => $value]);

				while($user = $users->Fetch())
				{
					if($isSearchModuleIncluded)
					{
						$res .= \CSearch::KillTags(
								\CUser::FormatName(\CSite::GetNameFormat(), $user)
							) . "\r\n";
					}
					else
					{
						$res .= strip_tags(
								\CUser::FormatName(\CSite::GetNameFormat(), $user)
							) . "\r\n";
					}
				}
			}
		}

		return $res;
	}

	/**
	 * @param array $userField
	 * @param $value
	 * @return string|null
	 */
	public static function onBeforeSave(array $userField, $value)
	{
		return $value;
	}
}