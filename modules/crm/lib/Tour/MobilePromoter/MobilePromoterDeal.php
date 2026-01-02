<?php

namespace Bitrix\Crm\Tour\MobilePromoter;

use Bitrix\Main\Localization\Loc;

class MobilePromoterDeal extends MobilePromoter
{
	protected const OPTION_NAME_SHOW_COUNT = 'mobile_promoter_deal_show_count';

	protected function canShow(): bool
	{
		if (!parent::canShow())
		{
			return false;
		}

		return $this->isEntityTypeUsed(\CCrmOwnerType::Deal);
	}

	protected function getTitle(): string
	{
		return Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_DEAL_TITLE');
	}

	protected function getContent(): string
	{
		return
			'<ul class="ui-mobile-promoter__popup-list">'
			. '<li class="ui-mobile-promoter__popup-list-item">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_DEAL_LIST_ITEM_1') . '</li>'
			. '<li class="ui-mobile-promoter__popup-list-item">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_DEAL_LIST_ITEM_2') . '</li>'
			. '<li class="ui-mobile-promoter__popup-list-item">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_DEAL_LIST_ITEM_3') . '</li>'
			. '</ul>'
			. '<div class="ui-mobile-promoter__popup-desc">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_DEAL_DESC') . '</div>'
			. '<div class="ui-mobile-promoter__popup-info">' . Loc::getMessage('CRM_TOUR_MOBILE_PROMOTER_DEAL_INFO') . '</div>';
	}

	protected function getAnalytics(): array
	{
		return [
			'c_section' => 'deal_section',
		];
	}
}
