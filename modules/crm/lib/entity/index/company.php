<?php

namespace Bitrix\Crm\Entity\Index;

use Bitrix\Main\Entity;

class CompanyTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_company_index';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('COMPANY_ID', ['primary' => true]),
			new Entity\StringField('SEARCH_CONTENT')
		];
	}
}
