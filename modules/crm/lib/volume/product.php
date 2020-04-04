<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;


class Product extends Crm\Volume\Base
{
	/** @var array */
	protected static $entityList = array(
		Crm\ProductTable::class,
		Crm\ProductRowTable::class,
	);

	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_PRODUCT_TITLE');
	}

	/**
	 * Returns table list corresponding to indicator.
	 * @return string[]
	 */
	public function getTableList()
	{
		$tableNames = parent::getTableList();

		$tableNames[] = \CCrmProductRow::CONFIG_TABLE_NAME;

		return $tableNames;
	}
}

