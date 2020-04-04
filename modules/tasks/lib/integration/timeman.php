<?php
namespace Bitrix\Tasks\Integration;

abstract class Timeman extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'timeman';

	public static function canUse()
	{
		return \isModuleInstalled('intranet')
			   && (!\CModule::IncludeModule('extranet') || !\CExtranet::IsExtranetSite());
	}
}