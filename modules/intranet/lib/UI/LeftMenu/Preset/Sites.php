<?php
namespace Bitrix\Intranet\UI\LeftMenu\Preset;

use Bitrix\Main\Localization\Loc;

class Sites extends Social
{
	const CODE = 'sites';

	const STRUCTURE = [
		'shown' => [
			'menu_sites',
			'menu_shop',
			'menu_crm_favorite',
			'menu_booking',
			'menu_crm_store',
			'menu_marketing',
			'menu_tasks',
			'menu_teamwork' => [
				'menu_live_feed',
				'menu_im_messenger',
				'menu_im_collab',
				'menu_calendar',
				'menu_documents',
				'menu_boards',
				'menu_files',
				'menu_external_mail',
				'menu_all_groups',
				'menu_all_spaces',
			],
			'menu_bi_constructor',
			'menu_company',
			'menu_bizproc_sect',
			'menu_automation',
			'menu_marketplace_group' => [
				'menu_marketplace_sect',
				'menu_devops_sect',
			],
		],
		'hidden' => [
			'menu_timeman_sect',
			'menu_rpa',
			"menu_contact_center",
			"menu_crm_tracking",
			"menu_analytics",
			"menu-sale-center",
			"menu_openlines",
			"menu_telephony",
			"menu_ai",
			"menu_onec_sect",
			"menu_tariff",
			"menu_updates",
			'menu_knowledge',
			'menu_conference',
			'menu_configs_sect',
		]
	];

	public function getName(): string
	{
		return Loc::getMessage('MENU_PRESET_SITES_TITLE');
	}
}