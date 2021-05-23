<?php

namespace Bitrix\Crm\Entity\Index;

use Bitrix\Main\Entity;

class ContactTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_contact_index';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('CONTACT_ID', ['primary' => true]),
			new Entity\StringField('SEARCH_CONTENT')
		];
	}
}
