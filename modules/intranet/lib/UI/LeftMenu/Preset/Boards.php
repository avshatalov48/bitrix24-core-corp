<?php

namespace Bitrix\Intranet\UI\LeftMenu\Preset;

class Boards extends Social
{
	const CODE = 'boards';

	const STRUCTURE = [
		'shown' => [
			'menu_teamwork' => [
				'menu_boards',
				'menu_im_messenger',
				'menu_live_feed',
				'menu_im_collab',
				'menu_calendar',
				'menu_documents',
				'menu_files',
				'menu_external_mail',
				'menu_all_groups',
				'menu_all_spaces',
			],
			'menu_tasks',
			'menu_crm_favorite',
			'menu_booking',
			'menu_crm_store',
			'menu_marketing',
			'menu_sites',
			'menu_shop',
			'menu_sign_b2e',
			'menu_sign',
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
		],
	];

	public function getStructure(): array
	{
		$structure = parent::getStructure();

		$structure['shown']['menu_teamwork'] = [
			'menu_boards',
			'menu_im_messenger',
			'menu_live_feed',
			'menu_im_collab',
			'menu_calendar',
			'menu_documents',
			'menu_files',
			'menu_external_mail',
			'menu_all_groups',
			'menu_all_spaces',
		];

		return $structure;
	}
}
