<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmDedupeSettingsComponentAjaxController extends Main\Engine\Controller
{

	public function saveConfigurationAction($guid, array $config): void
	{
		if (Main\Loader::includeModule('crm'))
		{
			$dedupeConfig = new \Bitrix\Crm\Integrity\DedupeConfig();
			$dedupeConfig->save((string)$guid, $config);
		}
	}
}