<?php

namespace Bitrix\BiConnector\Configuration;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Manifest
{
	public static function list()
	{
		$manifestList = [];

		$manifestList[] = [
			'CODE' => 'bi_powerbi',
			'VERSION' => 1,
			'ACTIVE' => 'N',
			'PLACEMENT' => [],
			'USES' => [
				'bi',
			],
			'TITLE' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_POWER_BI_TITLE'),
			'DESCRIPTION' => '',
			'COLOR' => '#ff799c',
			'DISABLE_CLEAR_FULL' => 'Y',
			'DISABLE_NEED_START_BTN' => 'Y',
			'ICON' => '/bitrix/images/crm/configuration/vertical-crm-icon.svg',
			'IMPORT_TITLE_PAGE' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_POWER_BI_IMPORT_TITLE_PAGE'),
			'IMPORT_TITLE_BLOCK' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_POWER_BI_IMPORT_TITLE_BLOCK'),
			'IMPORT_DESCRIPTION_UPLOAD' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_POWER_BI_IMPORT_DESCRIPTION_UPLOAD'),
			'IMPORT_INSTALL_FINISH_TEXT' => '',
			'IMPORT_DESCRIPTION_START' => '',
			'IMPORT_FINISH_DESCRIPTION' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_POWER_BI_IMPORT_FINISH_DESCRIPTION'),
			'EXPORT_TITLE_PAGE' => '',
			'EXPORT_TITLE_BLOCK' => '',
			'EXPORT_ACTION_DESCRIPTION' => '',
		];

		$manifestList[] = [
			'CODE' => 'bi_data_studio',
			'VERSION' => 1,
			'ACTIVE' => 'N',
			'PLACEMENT' => [],
			'USES' => [
				'bi',
			],
			'TITLE' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_DATA_STUDIO_TITLE'),
			'DESCRIPTION' => '',
			'DISABLE_CLEAR_FULL' => 'Y',
			'DISABLE_NEED_START_BTN' => 'Y',
			'COLOR' => '#ff799c',
			'ICON' => '/bitrix/images/crm/configuration/vertical-crm-icon.svg',
			'IMPORT_TITLE_PAGE' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_DATA_STUDIO_IMPORT_TITLE_PAGE'),
			'IMPORT_TITLE_BLOCK' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_DATA_STUDIO_IMPORT_TITLE_BLOCK'),
			'IMPORT_DESCRIPTION_UPLOAD' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_DATA_STUDIO_IMPORT_DESCRIPTION_UPLOAD'),
			'IMPORT_DESCRIPTION_START' => '',
			'IMPORT_FINISH_DESCRIPTION' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_DATA_STUDIO_IMPORT_FINISH_DESCRIPTION'),
			'IMPORT_INSTALL_FINISH_TEXT' => '',
			'EXPORT_TITLE_PAGE' => '',
			'EXPORT_TITLE_BLOCK' => '',
			'EXPORT_ACTION_DESCRIPTION' => '',
		];

		return $manifestList;
	}
}
