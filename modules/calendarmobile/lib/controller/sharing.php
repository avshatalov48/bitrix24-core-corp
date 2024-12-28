<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\Calendar\Controller\SharingGroupAjax;
use Bitrix\Calendar\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Calendar\Integration\Bitrix24Manager;
use Bitrix\Calendar\Sharing\SharingGroup;
use Bitrix\CalendarMobile\Dto;
use Bitrix\CalendarMobile\Integration\IM\ChatService;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;

class Sharing extends Controller
{
	private int $userId;

	protected function init(): void
	{
		parent::init();

		$this->userId = \CCalendar::GetUserId();
	}

	public function enableAction(): ?Dto\Sharing
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing($this->userId);

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
		$sharing = new \Bitrix\Calendar\Sharing\Sharing($this->userId);
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
			'userInfo' => $sharing->getUserInfo(),
			'settings' => $this->getSettings($sharing->getLinkInfo()),
		]);
	}

	public function disableUserLinkAction(?string $hash): bool
	{
		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return false;
		}

		$sharing = new \Bitrix\Calendar\Sharing\Sharing($this->userId);
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
		$sharing = new \Bitrix\Calendar\Sharing\Sharing($this->userId);
		$activeLinkShortUrl = $sharing->getActiveLinkShortUrl();

		return Dto\Sharing::make([
			'isEnabled' => !empty($activeLinkShortUrl),
			'isRestriction' => $this->isRestriction(),
			'isPromo' => $this->isPromo(),
			'shortUrl' => $activeLinkShortUrl,
			'userInfo' => $sharing->getUserInfo(),
			'settings' => $this->getSettings($sharing->getLinkInfo()),
		]);
	}

	public function getPublicUserLinkAction(): ?array
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing($this->userId);
		$activeLinkShortUrl = $sharing->getActiveLinkShortUrl();

		if(empty($activeLinkShortUrl))
		{
			$this->addError(new Error('Sharing is disabled', 100040));
			return null;
		}

		return [
			'shortUrl'=> $activeLinkShortUrl,
		];
	}

	public function generateUserJointSharingLinkAction(array $memberIds): ?array
	{
		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return null;
		}

		$result = (new \Bitrix\Calendar\Sharing\Sharing($this->userId))->generateUserJointLink($memberIds);
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

		return [
			'userLinks' => (new \Bitrix\Calendar\Sharing\Sharing($this->userId))->getAllUserLinkInfo(),
			'pathToUser' => Option::get('intranet', 'path_user', '/company/personal/user/#USER_ID#/', '-'),
		];
	}

	public function increaseFrequentUseAction(?string $hash): bool
	{
		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return false;
		}

		$sharing = new \Bitrix\Calendar\Sharing\Sharing($this->userId);
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

	/**
	 * @param array $memberIds
	 * @param int $groupId
	 * @param string $dialogId
	 *
	 * @return array|null
	 * @throws LoaderException
	 * @throws SystemException
	 */
	public function generateGroupJointSharingLinkAction(array $memberIds, int $groupId, string $dialogId = ''): ?array
	{
		if (!empty($dialogId) && !Loader::includeModule('im'))
		{
			$this->addError(new Error('Module im not installed'));

			return null;
		}

		$sharing = new SharingGroup($groupId, $this->userId);
		if (!$sharing->isEnabled())
		{
			$sharing->enable();
		}

		$result = $this->forward(
			SharingGroupAjax::class,
			'generateJointSharingLink',
			[
				'memberIds' => $memberIds,
				'groupId' => $groupId,
			],
		);

		if ($result === null)
		{
			return null;
		}

		if (!empty($dialogId) && !empty($result['url']))
		{
			$messageResult = (new ChatService($this->userId))->sendMessage($dialogId, $result['url']);

			if (!$messageResult->isSuccess())
			{
				$this->addError($messageResult->getError());
			}
		}

		return $result;
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
		$sharing = new \Bitrix\Calendar\Sharing\Sharing($this->userId);
		$sharing->setSortJointLinksByFrequentUse($sortByFrequentUse === 'Y');
	}
}
