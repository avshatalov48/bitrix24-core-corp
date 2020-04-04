<?php
namespace Bitrix\Timeman\Helper;

class ConfigurationHelper
{
	public static function getIblockStructureId()
	{
		return \Bitrix\Main\Config\Option::get('intranet', 'iblock_structure', false);
	}
}