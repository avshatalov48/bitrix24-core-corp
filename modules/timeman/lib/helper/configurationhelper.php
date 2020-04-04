<?php
namespace Bitrix\Timeman\Helper;

use COption;

class ConfigurationHelper
{
	public static function getInstance()
	{
		return new static();
	}

	public function getIblockStructureId()
	{
		return \Bitrix\Main\Config\Option::get('intranet', 'iblock_structure', false);
	}

	public function getIsAllowedToEditDay()
	{
		return COption::getOptionString('timeman', 'workday_can_edit_current', 'Y') === 'Y';
	}

	public function getIsAllowedToReopenDay()
	{
		return COption::getOptionString('timeman', 'workday_close_undo', 'Y') === 'Y';
	}
}