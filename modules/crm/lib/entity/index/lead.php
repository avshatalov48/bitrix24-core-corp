<?php

namespace Bitrix\Crm\Entity\Index;

use Bitrix\Main\Entity;

class LeadTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_lead_index';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('LEAD_ID', ['primary' => true]),
			new Entity\StringField('SEARCH_CONTENT')
		];
	}
}
