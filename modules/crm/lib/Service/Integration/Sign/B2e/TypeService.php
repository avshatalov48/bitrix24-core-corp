<?php

namespace Bitrix\Crm\Service\Integration\Sign\B2e;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;

final class TypeService
{
	/**
	 *  @return list<array{
	 *     ID: int,
	 *	   ENTITY_TYPE_ID: int,
	 *     IS_DEFAULT: bool,
	 *     IS_SYSTEM: bool,
	 *     CODE: string,
	 *     CREATED_DATE: DateTime,
	 *     NAME: string,
	 *     SORT: int,
	 *     SETTINGS: string,
	 *  }>
	 */
	public function getCategories(): array
	{
		return Container::getInstance()
			->getSignB2eTypeService()
			->getCategories()
		;
	}

	/**
	 * @return list<string>
	 */
	public function getCategoryCodesForMenu(): array
	{
		return [
			\Bitrix\Crm\Service\Sign\B2e\TypeService::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE,
			\Bitrix\Crm\Service\Sign\B2e\TypeService::SIGN_B2E_ITEM_CATEGORY_CODE,
		];
	}
}
