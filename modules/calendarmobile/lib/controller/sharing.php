<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\Calendar\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Calendar\Integration\Bitrix24Manager;
use Bitrix\CalendarMobile\Dto;
use Bitrix\Main\Config\Option;
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

		return Dto\Sharing::make([
			'isEnabled' => true,
			'isRestriction' => $this->isRestriction(),
			'isPromo' => $this->isPromo(),
			'shortUrl' => \Bitrix\Calendar\Sharing\Helper::getShortUrl($sharing->getActiveLinkUrl()),
			'userInfo' => $sharing->getUserInfo(),
			'settings' => $sharing->getLinkSettings(),
			'options' => $sharing->getOptions(),
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

		return Dto\Sharing::make([
			'isEnabled' => false,
			'isRestriction' => $this->isRestriction(),
			'isPromo' => $this->isPromo(),
			'settings' => $this->getSettings($sharing->getLinkInfo()),
		]);
	}

	public function disableUserLinkAction(?string $hash): bool
	{
		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return false;
		}

		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetUserId());
		$result = $sharing->deactivateUserLink($hash);
		if (!$result->isSuccess())
		{
			$this->addErrors($this->getErrors());

			return false;
		}

		return true;
	}

	public function isEnabledAction(): ?Dto\Sharing
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetCurUserId());
		$activeLinkShortUrl = $sharing->getActiveLinkShortUrl();

		return Dto\Sharing::make([
			'isEnabled' => !empty($activeLinkShortUrl),
			'isRestriction' => $this->isRestriction(),
			'isPromo' => $this->isPromo(),
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

	public function generateUserJointSharingLinkAction(array $memberIds): ?array
	{
		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return null;
		}

		$userId = \CCalendar::GetCurUserId();
		$result = (new \Bitrix\Calendar\Sharing\Sharing($userId))->generateUserJointLink($memberIds);
		if (!$result->isSuccess())
		{
			$this->addErrors($this->getErrors());

			return null;
		}

		return $result->getData();
	}

	public function getAllUserLinkAction(): ?array
	{
		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return null;
		}

		$userId = \CCalendar::GetCurUserId();

		return [
			'userLinks' => (new \Bitrix\Calendar\Sharing\Sharing($userId))->getAllUserLinkInfo(),
			'pathToUser' => Option::get('intranet', 'path_user', '/company/personal/user/#USER_ID#/', '-'),
		];
	}

	public function increaseFrequentUseAction(?string $hash): bool
	{
		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return false;
		}

		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetUserId());
		$result = $sharing->increaseFrequentUse($hash);
		if (!$result->isSuccess())
		{
			$this->addErrors($this->getErrors());

			return false;
		}

		return true;
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

		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetCurUserId());

		return Dto\Sharing::make([
			'isEnabled' => true,
			'shortUrl' => $linkInfo['url'],
			'isRestriction' => false,
			'isPromo' => false,
			'settings' => $this->getSettings($linkInfo),
			'userInfo' => $sharing->getUserInfo(),
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
		return !Bitrix24Manager::isFeatureEnabled(FeatureDictionary::CALENDAR_SHARING);
	}

	public function isPromo(): bool
	{
		return Bitrix24Manager::isPromoFeatureEnabled(FeatureDictionary::CALENDAR_SHARING);
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

	public function setSortJointLinksByFrequentUseAction(string $sortByFrequentUse): void
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetUserId());
		$sharing->setSortJointLinksByFrequentUse($sortByFrequentUse === 'Y');
	}
}
