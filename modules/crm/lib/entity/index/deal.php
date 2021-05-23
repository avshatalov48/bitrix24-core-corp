<?php

namespace Bitrix\Crm\Entity\Index;

use Bitrix\Main\Entity;

class DealTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_deal_index';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('DEAL_ID', ['primary' => true]),
			new Entity\StringField('SEARCH_CONTENT')
		];
	}
}
