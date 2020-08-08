<?php
namespace Bitrix\Crm\Integration\Rest\Configuration;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Manifest
{
	/**
	 * @return array
	 */
	public static function getList()
	{
		$manifestList = [];
		$manifestList[] = [
			'CODE' => 'vertical_crm',
			'VERSION' => 1,
			'ACTIVE' => 'Y',
			'PLACEMENT' => [
				'crm',
				'crm_lead',
				'crm_deal',
				'crm_contact',
				'crm_company',
				'crm_settings'
			],
			'USES' => [
				'app',
				'crm',
				'bizproc_crm',
				'intranet_setting'
			],
			'TITLE' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_TITLE_VERTICAL_CRM"),
			'DESCRIPTION' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_DESCRIPTION_VERTICAL_CRM"),
			'COLOR' => '#ff799c',
			'ICON' => '/bitrix/images/crm/configuration/vertical-crm-icon.svg',
			'EXPORT_TITLE_PAGE' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_PAGE_TITLE_VERTICAL_CRM"),
			'EXPORT_TITLE_BLOCK' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_BLOCK_TITLE_VERTICAL_CRM"),
			'EXPORT_ACTION_DESCRIPTION' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_ACTION_DESCRIPTION_VERTICAL_CRM"),
		];

		return $manifestList;
	}
}