<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;

class ActivityViewMode extends Base
{
	private const OLD_PORTAL_DATE = '14.10.2022';
	private const HELPDESK_CODE = '16440640';

	protected const OPTION_NAME = 'activity-view-mode';
	protected const ICON_PATH = '/bitrix/images/crm/whats_new/activity_view_mode/';

	/**
	 * @inheritdoc
	 */
	protected function canShow(): bool
	{
		return
			Crm::isUniversalActivityScenarioEnabled()
			&& !$this->isUserSeenTour()
			&& $this->isOldPortal()
			&& $this->isEnableEntityUncompletedActivity()
			&& $this->isEnableEntityLastActivityFields()
			&& $this->seenPolarStarSlider()
		;
	}

	private function isOldPortal(): bool
	{
		$user = UserTable::getRow([
			'select' => ['DATE_REGISTER'],
			'filter' => ['ID' => 1],
			'cache'=> [
				'ttl' => 86400,
			],
		]);

		if (!$user)
		{
			return false;
		}

		$portalDate = Date::createFromTimestamp($user['DATE_REGISTER']->getTimestamp());
		$releaseDate = new Date(self::OLD_PORTAL_DATE, 'd.m.Y');

		return ($portalDate < $releaseDate);
	}

	private function isEnableEntityUncompletedActivity(): bool
	{
		return (Option::get('crm', 'enable_entity_uncompleted_act', 'Y') === 'Y');
	}

	private function isEnableEntityLastActivityFields(): bool
	{
		return (Option::get('crm', 'enable_last_activity_for_deal', 'Y') === 'Y');
	}

	protected function getSlides(): array
	{
		return [
			[
				'title' => Loc::getMessage('CRM_TOUR_AVM_TITLE'),
				'innerTitle' => Loc::getMessage('CRM_TOUR_AVM_STEP_BODY_TITLE'),
				'innerImage' => self::ICON_PATH . 'icon.svg',
				'innerDescription' => Loc::getMessage('CRM_TOUR_AVM_STEP_BODY_TEXT'),
				'buttons' => [
					[
						'text' => Loc::getMessage('CRM_TOUR_AVM_STEP_BUTTON_1'),
						'className' => 'ui-btn-primary',
						'onClickClose' => true,
					],
					[
						'text' => Loc::getMessage('CRM_TOUR_AVM_STEP_BUTTON_2'),
						'className' => 'ui-btn-secondary',
						'helpDeskCode' => self::HELPDESK_CODE,
					],
				],
			],
		];
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'step1',
				'target' => '#ui-nav-panel-item-activity',
				'title' => Loc::getMessage('CRM_TOUR_AVM_STEP_1_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_AVM_STEP_1_TEXT'),
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'steps' => [
				'popup' => [
					'width' => 380,
				],
			],
		];
	}

	private function seenPolarStarSlider(): bool
	{
		$zone = $this->getZone();
		if (in_array($zone, ['ua', 'ur']))
		{
			return true;
		}

		$maxDateTime = '03.12.2022 00:00';
		$time = \DateTime::createFromFormat(
			'd.m.Y H:i',
			$maxDateTime,
			new \DateTimeZone('Europe/Moscow')
		);

		if (time() > $time->getTimestamp())
		{
			return true;
		}

		if (Loader::includeModule('extranet') && \CExtranet::isExtranetSite())
		{
			return true;
		}

		if (\CUserOptions::getOption('intranet', 'release_polar_star:deactivated') === 'Y')
		{
			return true;
		}

		return false;
	}

	private function getZone(): ?string
	{
		if (Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::getPortalZone();
		}

		return null;
	}
}
