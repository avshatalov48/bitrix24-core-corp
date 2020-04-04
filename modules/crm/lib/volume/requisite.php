<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;


class Requisite extends Crm\Volume\Base
{
	/** @var array */
	protected static $entityList = array(
		Crm\RequisiteTable::class,
		Crm\Requisite\LinkTable::class,
	);

	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_REQUISITE_TITLE');
	}

	/**
	 * Returns table list corresponding to indicator.
	 * @return string[]
	 */
	public function getTableList()
	{
		$tableNames = parent::getTableList();

		$tableNames[] = Crm\EntityRequisite::CONFIG_TABLE_NAME;

		return $tableNames;
	}
}

