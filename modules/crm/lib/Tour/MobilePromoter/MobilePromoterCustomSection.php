<?php

namespace Bitrix\Crm\Tour\MobilePromoter;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Localization\Loc;

class MobilePromoterCustomSection extends MobilePromoter
{
	protected const OPTION_NAME_SHOW_COUNT = 'mobile_promoter_dynamic_show_count';
	private ?int $customSectionId = null;

	public function setCustomSection(int $customSectionId)
	{
		$customSectionId = $customSectionId > 0 ? $customSectionId : null;
		$this->customSectionId = $customSectionId;

		return $this;
	}

	protected function getIntent(): string
	{
		if ($this->customSectionId === null)
		{
			return parent::getIntent();
		}

		return 'preset_custom_section_id' . $this->customSectionId;
	}

	protected function canShow(): bool
	{
		if (!parent::canShow())
		{
			return false;
		}

		$cache = Cache::createInstance();
		if ($cache->initCache(
			self::CACHE_TTL,
			'crm.tour.mobile-promoter.dynamicTypes',
			self::CACHE_DIR
		))
		{
			$dynamicTypes = $cache->getVars();
		}
		else
		{
			$dynamicTypes = TypeTable::getList([
				'select' => ['ID', 'ENTITY_TYPE_ID', 'CUSTOM_SECTION_ID'],
				'filter' => ['>=CUSTOM_SECTION_ID' => 0],
				'limit' => 2,
			])->fetchAll();

			$cache->startDataCache();
			$cache->endDataCache($dynamicTypes);
		}

		if (count($dynamicTypes) > 1)
		{
			return true;
		}

		if (count($dynamicTypes) === 1)
		{
			return $this->isEntityTypeUsed($dynamicTypes[0]['ENTITY_TYPE_ID']);
		}

		return false;
	}

	protected function getTitle(): string
	{
		return Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_CUSTOM_SECTION_TITLE');
	}

	protected function getContent(): string
	{
		return
			'<ul class="ui-mobile-promoter__popup-list">'
			. '<li class="ui-mobile-promoter__popup-list-item">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_CUSTOM_SECTION_LIST_ITEM_1') . '</li>'
			. '<li class="ui-mobile-promoter__popup-list-item">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_CUSTOM_SECTION_LIST_ITEM_2') . '</li>'
			. '<li class="ui-mobile-promoter__popup-list-item">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_CUSTOM_SECTION_LIST_ITEM_3') . '</li>'
			. '</ul>'
			. '<div class="ui-mobile-promoter__popup-desc">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_CUSTOM_SECTION_DESC') . '</div>'
			. '<div class="ui-mobile-promoter__popup-info">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_CUSTOM_SECTION_INFO') . '</div>';
	}

	protected function getAnalytics(): array
	{
		return [
			'c_section' => 'custom_section',
		];
	}
}
