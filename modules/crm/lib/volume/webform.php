<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;


class Webform extends Crm\Volume\Base
{
	/** @var array */
	protected static $entityList = array(
		Crm\WebForm\Internals\FormTable::class,
		Crm\WebForm\Internals\FormCounterTable::class,
		Crm\WebForm\Internals\FieldTable::class,
		Crm\WebForm\Internals\FieldDependenceTable::class,
		Crm\WebForm\Internals\PresetFieldTable::class,
		Crm\WebForm\Internals\QueueTable::class,
		Crm\WebForm\Internals\ResultTable::class,
		Crm\WebForm\Internals\ResultEntityTable::class,
		Crm\WebForm\Internals\FormStartEditTable::class,
		Crm\WebForm\Internals\FormCounterDailyTable::class,
	);

	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_WEBFORM_TITLE');
	}
}
