<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use Bitrix\UI\Util;

final class AhaMomentCounterLightTime extends Base
{
	private const PORTAL_CREATION_DATE_THRESHOLD = '2024-03-01';

	public const OPTION_NAME = 'aha-moment-counter-lightdate';

	protected function canShow(): bool
	{
		return !$this->isUserSeenTour()
			&& $this->isPortalCreationDateBeforeFeatureRelease()
			&& $this->isUserHasUncompletedAction();
	}

	protected function getSteps(): array
	{
		Loader::includeModule('ui');
		return [
			[
				'id' => 'step1',
				'target' => '#counter_panel_container',
				'title' =>  Loc::getMessage('CRM_TOUR_AHA_LIGHT_COUNTER_TIME_TITLE'),
				'text' =>  Loc::getMessage(
					'CRM_TOUR_AHA_LIGHT_COUNTER_TIME_BODY',
					['#HELPDESK_URL#' => Util::getArticleUrlByCode('17359558')]
				),
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

	private function isPortalCreationDateBeforeFeatureRelease(): bool
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
		$releaseDate = new Date(self::PORTAL_CREATION_DATE_THRESHOLD, 'Y-m-d');

		return $portalDate < $releaseDate;
	}

	private function isUserHasUncompletedAction(): bool
	{
		$userId = Container::getInstance()->getContext()->getUserId();

		$row = EntityCountableActivityTable::query()
			->addSelect('ID')
			->where('ENTITY_ASSIGNED_BY_ID', '=', $userId)
			->setLimit(1)
			->setCacheTtl(60)
			->fetch();

		return $row !== false;
	}
}