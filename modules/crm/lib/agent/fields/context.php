<?php

namespace Bitrix\Crm\Agent\Fields;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\FieldContext\Repository;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;

class Context extends AgentBase
{
	public static function run(): void
	{
		$list = Container::getInstance()->getDynamicTypeDataClass()::getList()->fetchAll();
		foreach($list as $item)
		{
			$type = $item['ID'];
			TypeTable::createItemFieldsContextTable($type);
		}

		Option::delete('crm', ['name' => Repository::TABLES_CREATED_OPTION_NAME]);
	}
}
