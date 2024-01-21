<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\CalendarMobile\Dto;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;

Loader::requireModule('calendar');

class Sharing extends Controller
{
	public function enableAction(): ?Dto\Sharing
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetCurUserId());

		$result = $sharing->enable();

		$errors = [];
		if (!$result->isSuccess())
		{
			foreach ($result->getErrors() as $error)
			{
				$errors[] = $error->getCode();
			}

			if (in_array(\Bitrix\Calendar\Sharing\Sharing::ERROR_CODE_100010, $errors, true) === false)
			{
				$this->addErrors($result->getErrors());
				return null;
			}
		}

		return new Dto\Sharing([
			'isEnabled' => true,
			'shortUrl' => \Bitrix\Calendar\Sharing\Helper::getShortUrl($sharing->getActiveLinkUrl()),
			'isRestriction' => $this->isRestriction(),
			'settings' => $this->getSettings($sharing->getLinkInfo()),
		]);
	}

	public function disableAction(): ?Dto\Sharing
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetCurUserId());
		$result = $sharing->disable();

		$errors = [];
		if (!$result->isSuccess())
		{
			foreach ($result->getErrors() as $error)
			{
				$errors[] = $error->getCode();
			}

			if (in_array(\Bitrix\Calendar\Sharing\Sharing::ERROR_CODE_100020, $errors, true) === false)
			{
				$this->addErrors($result->getErrors());
				return null;
			}
		}

		return new Dto\Sharing([
			'isEnabled' => false,
			'isRestriction' => $this->isRestriction(),
			'settings' => $this->getSettings($sharing->getLinkInfo()),
		]);
	}

	public function isEnabledAction(): ?Dto\Sharing
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetCurUserId());
		$activeLinkShortUrl = $sharing->getActiveLinkShortUrl();

		return new Dto\Sharing([
			'isEnabled' => !empty($activeLinkShortUrl),
			'isRestriction' => $this->isRestriction(),
			'shortUrl' => $activeLinkShortUrl,
			'settings' => $this->getSettings($sharing->getLinkInfo()),
		]);
	}

	public function getPublicUserLinkAction(): ?array
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetCurUserId());
		$activeLinkShortUrl = $sharing->getActiveLinkShortUrl();

		if(empty($activeLinkShortUrl))
		{
			$this->addError(new Error('Sharing is disabled', 100040));
			return null;
		}

		return [
			'shortUrl'=> $activeLinkShortUrl
		];
	}
	
	public function saveLinkRuleAction(string $linkHash, array $ruleArray): array
	{
		$result = [];
		
		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			$this->addError(new Error('Access denied'));
			
			return $result;
		}
		
		$saveResult = \Bitrix\Calendar\Sharing\Link\Rule\Helper::getInstance()->saveLinkRule($linkHash, $ruleArray);
		
		if (!$saveResult)
		{
			$this->addError(new Error('Error while trying to save rule'));
		}
		
		return $result;
	}

	public function initCrmAction(int $entityTypeId, int $entityId): Dto\Sharing|array
	{
		$result = [];

		if (!Loader::includeModule('crm'))
		{
			$this->addError(new Error('Crm module not found'));

			return $result;
		}

		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions($entityTypeId, $entityId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return $result;
		}

		$entity = \Bitrix\Crm\Service\Container::getInstance()->getEntityBroker($entityTypeId)?->getById($entityId);
		if (!$entity)
		{
			$this->addError(new Error('Entity not found'));

			return $result;
		}

		$ownerId = $entity->getAssignedById();

		$linkInfo = $this->getLinkInfoCrm($entityId, $ownerId);

		return new Dto\Sharing([
			'isEnabled' => true,
			'shortUrl' => $linkInfo['url'],
			'isRestriction' => false,
			'settings' => $this->getSettings($linkInfo),
 		]);
	}

	private function getLinkInfoCrm(int $entityId, int $ownerId): array
	{
		$linkFactory = new \Bitrix\Calendar\Sharing\Link\Factory();
		$linkMapper = new \Bitrix\Calendar\Sharing\Link\Rule\Mapper();

		/** @var \Bitrix\Calendar\Sharing\Link\CrmDealLink $crmDealLink */
		$crmDealLink = $linkFactory->getCrmDealLink($entityId, $ownerId);
		if ($crmDealLink === null)
		{
			$crmDealLink = $linkFactory->createCrmDealLink($ownerId, $entityId);
		}

		$shortUrl = \Bitrix\Calendar\Sharing\Helper::getShortUrl($crmDealLink->getUrl());

		return [
			'url' => $shortUrl,
			'hash' => $crmDealLink->getHash(),
			'rule' => $linkMapper->convertToArray($crmDealLink->getSharingRule()),
		];
	}

	public function isRestriction(): bool
	{
		return !\Bitrix\Calendar\Integration\Bitrix24Manager::isFeatureEnabled('calendar_sharing');
	}

	private function getSettings(array $linkInfo): array
	{
		$settings = [];

		if (!empty($linkInfo))
		{
			$calendarSettings = \CCalendar::GetSettings();
			$settings = [
				'weekStart' => \CCalendar::GetWeekStart(),
				'workTimeStart' => $calendarSettings['work_time_start'],
				'workTimeEnd' => $calendarSettings['work_time_end'],
				'weekHolidays' => $calendarSettings['week_holidays'],
				'rule' => [
					'hash' => $linkInfo['hash'],
					'slotSize' => $linkInfo['rule']['slotSize'],
					'ranges' => $linkInfo['rule']['ranges'],
				],
			];
		}

		return $settings;
	}
}