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
				'crm_form',
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

		$manifestList[] = [
			'DISABLE_CLEAR_FULL' => 'Y',
			'CODE' => 'crm_form',
			'VERSION' => 1,
			'ACTIVE' => 'N',
			'PLACEMENT' => [
				'crm',
				'crm_lead',
				'crm_deal',
				'crm_contact',
				'crm_company',
				'crm_settings'
			],
			'USES' => [
				'crm_fields',
				'crm_form',
			],
			'TITLE' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_TITLE_CRM_FORM"),
			'DESCRIPTION' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_DESCRIPTION_CRM_FORM"),
			'COLOR' => '#00b4ac',
			'ICON' => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgLTIxIDQxIDU0Ij48cGF0aCBmaWxsPSIjRkZGIiBkPSJNMjQuOTA2IDE1LjA3OWMwIC41NDItLjQ1OC45ODUtMS4wMTguOTg1aC04LjE4MWMtLjU2IDAtMS4wMTktLjQ0My0xLjAxOS0uOTg1di0uMDVjMC0uNTQzLjQ1OS0uOTg2IDEuMDE5LS45ODZoOC4xOGMuNTYxIDAgMS4wMi40NDMgMS4wMi45ODZ2LjA1em0tMS43NzggNC4zOTNjMCAuNTQyLS40NTguOTg1LTEuMDE4Ljk4NWgtNi40MDNjLS41NiAwLTEuMDItLjQ0My0xLjAyLS45ODV2LS4wNTFjMC0uNTQyLjQ2LS45ODUgMS4wMi0uOTg1aDYuNDAzYy41NiAwIDEuMDE4LjQ0MyAxLjAxOC45ODV2LjA1MXptMCA0LjM5MmMwIC41NDMtLjQ1OC45ODUtMS4wMTguOTg1aC02LjQwM2MtLjU2IDAtMS4wMi0uNDQyLTEuMDItLjk4NXYtLjA1YzAtLjU0Mi40Ni0uOTg1IDEuMDItLjk4NWg2LjQwM2MuNTYgMCAxLjAxOC40NDMgMS4wMTguOTg1di4wNXpNMjguODg2IDExSDEzLjExNEMxMi41IDExIDEyIDExLjQ4NSAxMiAxMi4wNzd2MTYuODQ2YzAgLjU5Mi41MDEgMS4wNzcgMS4xMTQgMS4wNzdoMTUuNzcyQzI5LjUgMzAgMzAgMjkuNTE1IDMwIDI4LjkyM1YxMi4wNzdjMC0uNTkyLS41MDEtMS4wNzctMS4xMTQtMS4wNzd6Ii8+PC9zdmc+',
			'EXPORT_TITLE_PAGE' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_PAGE_TITLE_CRM_FORM"),
			'EXPORT_TITLE_BLOCK' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_BLOCK_TITLE_CRM_FORM"),
			'EXPORT_ACTION_DESCRIPTION' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_ACTION_DESCRIPTION_CRM_FORM"),
		];

		$manifestList[] = [
			'DISABLE_CLEAR_FULL' => 'Y',
			'CODE' => 'crm_smart_robots',
			'VERSION' => 1,
			'ACTIVE' => 'Y',
			'PLACEMENT' => [
				'crm_smart_robots',
			],
			'USES' => [
				'bizproc_script',
			],
			'TITLE' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_TITLE_CRM_SMART_ROBOTS"),
			'COLOR' => '#ff799c',
			'ICON' => '/bitrix/images/crm/configuration/vertical-crm-icon.svg',
			'EXPORT_TITLE_PAGE' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_PAGE_TITLE_CRM_SMART_ROBOTS"),
			'EXPORT_TITLE_BLOCK' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_BLOCK_TITLE_CRM_SMART_ROBOTS"),
			'IMPORT_TITLE_PAGE' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_PAGE_TITLE_IMPORT_CRM_SMART_ROBOTS"),
			'IMPORT_TITLE_BLOCK' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_BLOCK_TITLE_IMPORT_CRM_SMART_ROBOTS"),
			'IMPORT_DESCRIPTION_UPLOAD' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_IMPORT_DESCRIPTION_UPLOAD_CRM_SMART_ROBOTS"),
			'IMPORT_DESCRIPTION_START' => ' ',
			'IMPORT_INSTALL_FINISH_TEXT' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_IMPORT_INSTALL_FINISH_TEXT_CRM_SMART_ROBOTS"),
			'EXPORT_ACTION_DESCRIPTION' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_ACTION_DESCRIPTION_CRM_SMART_ROBOTS"),
		];

		return $manifestList;
	}
}