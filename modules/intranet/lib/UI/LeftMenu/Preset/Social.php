<?php
namespace Bitrix\Intranet\UI\LeftMenu\Preset;

class Social extends PresetAbstract
{
	const CODE = 'social';

	const STRUCTURE = [
		'shown' => [
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
		]
	];

	public function getName(): string
	{
		return 'Social';
	}

	public function getStructure(): array
	{
		static $structure;
		if ($structure)
		{
			return $structure;
		}

		$structure = static::STRUCTURE;
		$found = false;
		$filler = function(&$item, $key) use (&$filler, &$found) {
			if ($key === 'menu_marketplace_group')
			{
				$found = false;
				foreach ($this->getMarketPlaceStructure() as $application)
				{
					$item[] = $application;
				}
			}
			else if (is_array($item) && $found === false)
			{
				array_walk($item, $filler);
			}
		};
		array_walk($structure, $filler);
		return $structure;
	}

	public static function isAvailable(): bool
	{
		return true;
	}

	private function getMarketPlaceStructure(): array
	{
		$adminOption = unserialize(\COption::GetOptionString('intranet', 'left_menu_items_marketplace_' . $this->getSiteId())
			, ['allowed_classes' => false]
		);
		$adminOption = is_array($adminOption) ? $adminOption : [];
		$ids = [];
		array_map(function ($item) use (&$ids) {
			$ids[] = $item['ID'];
		}, $adminOption);
		return $ids;
	}
}