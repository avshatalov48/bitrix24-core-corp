<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;


class Address extends Crm\Volume\Base
{
	/** @var array */
	protected static $entityList = array(
		Crm\AddressTable::class,
	);

	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_ADDRESS_TITLE');
	}
}

