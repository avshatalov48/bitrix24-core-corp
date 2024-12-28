<?php

namespace Bitrix\Sign\Integration\Main\UISelector;

use Bitrix\Main\GroupTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SiteTable;
use Throwable;

final class SignGroupEntityProvider extends \Bitrix\Main\UI\Selector\EntityBase
{
	public const ENTITY_TYPE = 'SIGNGROUP';
	public const ENTITY_CODE = 'signGroup';

	private const GROUP_ACCESS_CODE_PREFIX = 'G';

	public function getData(): array
	{
		$employeeGroupCode = $this->getGroupId('EMPLOYEES_' . $this->getSiteId());
		$directionGroupCode = $this->getGroupId('DIRECTION');

		$items = [];
		if ($employeeGroupCode)
		{
			$items[self::GROUP_ACCESS_CODE_PREFIX . $employeeGroupCode] = [
				"id" => self::GROUP_ACCESS_CODE_PREFIX . $employeeGroupCode,
				"entityId" => 0,
				"name" => Loc::getMessage('SIGN_INTEGRATION_MAIN_UISELECTOR_SIGNGROUPENTITYPROVIDER_EMPLOYEE_NAME'),
				"desc" => Loc::getMessage('SIGN_INTEGRATION_MAIN_UISELECTOR_SIGNGROUPENTITYPROVIDER_EMPLOYEE_DESCRIPTION'),
			];
		}
		if ($directionGroupCode)
		{
			$items[self::GROUP_ACCESS_CODE_PREFIX . $directionGroupCode] = [
				"id" => self::GROUP_ACCESS_CODE_PREFIX . $directionGroupCode,
				"entityId" => 0,
				"name" => Loc::getMessage('SIGN_INTEGRATION_MAIN_UISELECTOR_SIGNGROUPENTITYPROVIDER_DIRECTION_NAME'),
				"desc" => Loc::getMessage('SIGN_INTEGRATION_MAIN_UISELECTOR_SIGNGROUPENTITYPROVIDER_DIRECTION_DESCRIPTION'),
			];
		}

		return [
			'ITEMS' => $items,
			'ITEMS_LAST' => [],
			'ADDITIONAL_INFO' => [],
		];
	}

	/**
	 * @return list<array{id: string, name: string, sort: int}>
	 */
	public function getTabList(): array
	{
		return [
			[
				'id' => self::ENTITY_CODE,
				'name' => Loc::getMessage('SIGN_INTEGRATION_MAIN_UISELECTOR_SIGNGROUPENTITYPROVIDER_TAB_NAME'),
				'sort' => 50,
			],
		];
	}

	/**
	 * @param string $filterValue
	 *
	 * @return int|null
	 * @see \Bitrix\Crm\Integration\Sign\Access::getNeededGroupId copied here
	 */
	private function getGroupId(string $filterValue): ?int
	{
		try
		{
			$employeeGroupId = GroupTable::getList(
					[
						'select' => ['ID'],
						'filter' => ['=STRING_ID' => $filterValue],
					]
				)
				->fetch()
			;
		}
		catch (Throwable $e)
		{
			return null;
		}

		return $employeeGroupId ? (int)$employeeGroupId['ID'] : null;
	}

	/**
	 * @return string|null
	 * @see \Bitrix\Crm\Integration\Sign\Access::getSiteId copied here
	 */
	private function getSiteId(): ?string
	{
		try
		{
			/** @todo Use SiteTable::getDefaultSiteId() */
			$site = SiteTable::getList(
					[
						'select' => ['LID', 'LANGUAGE_ID'],
						'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y'],
						'cache' => ['ttl' => 86400],
					]
				)
				->fetch()
			;
		}
		catch (Throwable $e)
		{
			return null;
		}

		return $site ? $site['LID'] : null;
	}
}