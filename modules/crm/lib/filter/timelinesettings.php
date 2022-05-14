<?php
namespace Bitrix\Crm\Filter;
class TimelineSettings extends \Bitrix\Main\Filter\Settings
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Undefined;
	}
}
