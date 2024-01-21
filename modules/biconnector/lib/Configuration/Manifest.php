<?php

namespace Bitrix\BiConnector\Configuration;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Manifest
{
	public const MANIFEST_CODE_POWERBI = 'bi_powerbi';
	public const MANIFEST_CODE_DATA_STUDIO = 'bi_data_studio';
	public const MANIFEST_CODE_SUPERSET = 'bi_superset';

	/**
	 * Returns rest applications description.
	 *
	 * @return array
	 */
	public static function list()
	{
		$manifestList = [];

		$manifestList[] = [
			'CODE' => self::MANIFEST_CODE_POWERBI,
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
			'CODE' => self::MANIFEST_CODE_DATA_STUDIO,
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

		$manifestList[] = [
			'CODE' => self::MANIFEST_CODE_SUPERSET,
			'VERSION' => 1,
			'ACTIVE' => 'N',
			'PLACEMENT' => [],
			'USES' => [
				'bi',
			],
			'TITLE' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_APACHE_SUPERSET_TITLE'),
			'DESCRIPTION' => '',
			'DISABLE_CLEAR_FULL' => 'Y',
			'DISABLE_NEED_START_BTN' => 'Y',
			'COLOR' => '#ff799c',
			'ICON' => '/bitrix/images/crm/configuration/vertical-crm-icon.svg',
			'IMPORT_TITLE_PAGE' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_APACHE_SUPERSET_IMPORT_TITLE_PAGE'),
			'IMPORT_TITLE_BLOCK' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_APACHE_SUPERSET_IMPORT_TITLE_BLOCK'),
			'IMPORT_DESCRIPTION_UPLOAD' => '',
			'IMPORT_DESCRIPTION_START' => '',
			'INSTALL_STEP' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_APACHE_SUPERSET_IMPORT_INSTALL_STEP'),
			'IMPORT_FINISH_DESCRIPTION' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_MANIFEST_APACHE_SUPERSET_IMPORT_FINISH_DESCRIPTION'),
			'IMPORT_INSTALL_FINISH_TEXT' => '',
			'EXPORT_TITLE_PAGE' => '',
			'EXPORT_TITLE_BLOCK' => '',
			'EXPORT_ACTION_DESCRIPTION' => '',
			'ACCESS' => [
				'MODULE_ID' => 'biconnector',
				'CALLBACK' => [
					static::class,
					'onCheckAccessSuperset',
				],
			],
		];

		return $manifestList;
	}

	/**
	 * Checks access to export and import superset dashboards.
	 * @param string $type Export or import.
	 * @param array $manifest Manifest data.
	 * @return array
	 */
	public static function onCheckAccessSuperset(string $type, array $manifest): array
	{
		return [
			'result' => true,
		];
	}
}
