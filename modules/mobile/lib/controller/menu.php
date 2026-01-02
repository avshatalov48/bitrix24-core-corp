<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;

use Bitrix\Main\Loader;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Menu\MenuList;
use Bitrix\Mobile\Menu\Service\MenuListCache;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Mobile\Provider\UserRepository;

class Menu extends Controller
{
	public function configureActions()
	{
		return [
			'getMenu' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getMenuAction(bool $forceRefresh = false): array
	{
		if (!Loader::includeModule('mobileapp'))
		{
			return [];
		}
		$userId = (int)$this->getCurrentUser()?->getId();
		if (!$userId)
		{
			return [];
		}

		$user = $this->getUserData($userId);
		if (!$user)
		{
			return [];
		}

		global $USER;

		$context = new Context([
			'userId' => $userId,
			'extranet' => $user->isExtranet,
			'isCollaber' => $user->isCollaber,
		]);
		$cache = new MenuListCache($context->userId);

		$supportBotId = $this->getSupportBotId();

		return [
			'user' => $user,
			'menuList' => (new MenuList($context, $cache))->build($forceRefresh),
			'currentShift' => $this->getCurrentShift($context, $userId),
			'workTime' => $this->getWorkTime(),
			'company' => $this->getCompany($context),
			'license' => $this->getLicense($context),
			'helpdeskUrl' => Loader::includeModule('ui') ? \Bitrix\UI\Util::getHelpdeskUrl(true) : null,
			'supportBotId' => $supportBotId,

			'canEditProfile' => $USER->CanDoOperation('edit_own_profile'),
			'canUseTimeMan' => $this->canUseTimeMan($context, $userId),
			'canUseCheckIn' => $this->canUseCheckIn($context),
			'canUseSupport' => $supportBotId > 0,
			'canInvite' => $this->canInvite(),
			'canUseTelephony' => \Bitrix\Main\Loader::includeModule('voximplant') && \Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls(),
			'shouldShowWhatsNew' => \Bitrix\Mobile\Config\Feature::isEnabled(\Bitrix\Mobile\Feature\WhatsNewFeature::class),
		];
	}

	private function getUserData(int $userId): ?\Bitrix\Mobile\Provider\CommonUserDto
	{
		$userData = \CUser::getById($userId)->Fetch();
		if (!$userData || !is_array($userData))
		{
			return null;
		}

		$user = [
			'ID' => $userId,
			'LOGIN' => $userData['LOGIN'],
			'NAME' => $userData['NAME'],
			'LAST_NAME' => $userData['LAST_NAME'],
			'SECOND_NAME' => $userData['SECOND_NAME'],
			'EMAIL' => $userData['EMAIL'] ?? '',
			'WORK_PHONE' => $userData['WORK_PHONE'],
			'WORK_POSITION' => $userData['WORK_POSITION'],
			'PERSONAL_MOBILE' => $userData['PERSONAL_MOBILE'],
			'PERSONAL_PHONE' => $userData['PERSONAL_PHONE'],
			'PERSONAL_GENDER' => $userData['PERSONAL_GENDER'],
			'PERSONAL_PHOTO' => $userData['PERSONAL_PHOTO'],
		];

		return UserRepository::createUserDto($user);
	}

	private function getCurrentShift(Context $context, int $userId): ?\Bitrix\StaffTrack\Model\Shift
	{
		if (
			$context->isCollaber || $context->extranet
			|| !Loader::includeModule('stafftrack')
			||  !\Bitrix\StaffTrack\Feature::isCheckInEnabled()
		)
		{
			return null;
		}

		$offset = \CTimeZone::GetOffset();
		$date = \Bitrix\Main\Type\Date::createFromTimestamp(time() + $offset);
		$dateFormatted = $date->format('d.m.Y');

		return \Bitrix\StaffTrack\Provider\ShiftProvider::getInstance($userId)->findByDate($dateFormatted);
	}

	private function canUseCheckIn(Context $context): bool
	{
		return Loader::includeModule('stafftrack')
			&& \Bitrix\StaffTrack\Feature::isCheckInEnabled()
			&& !$context->isCollaber
			&& !$context->extranet;
	}

	private function getSupportBotId(): int
	{
		if (!\Bitrix\Mobile\Config\Feature::isEnabled(\Bitrix\Mobile\Feature\SupportFeature::class))
		{
			return 0;
		}

		return  (int)(new \Bitrix\Mobile\Provider\SupportProvider())->getBotId();
	}

	private function canInvite(): bool
	{
		return (
			Loader::includeModule('intranet')
			&& Loader::includeModule('intranetmobile')
			&& \Bitrix\Intranet\Invitation::canCurrentUserInvite());
	}

	private function canUseTimeMan(Context $context, int $userId): bool
	{
		return !$context->extranet && Loader::includeModule('timeman') && \CTimeMan::CanUse($userId);
	}

	private function getCompany(Context $context): ?array
	{
		if (
			!Loader::includeModule('intranet')
			|| !Loader::includeModule('intranetmobile')
			|| $context->extranet
			|| $context->isCollaber
		)
		{
			return null;
		}

		$users = \Bitrix\IntranetMobile\Provider\UserProvider::getActiveUsersByLimit(4);
		$totalUsersCount = \Bitrix\IntranetMobile\Provider\UserProvider::getUsersCountWithLimit(104);

		return [
			'users' => $users,
			'totalUsersCount' => $totalUsersCount,
			'canInvite' => true,
		];
	}

	private function getLicense(Context $context): ?array
	{
		if (
			!Loader::includeModule('bitrix24')
			|| $context->extranet
			|| $context->isCollaber
		)
		{
			return null;
		}

		$isFreeLicense = \CBitrix24::isFreeLicense();
		$isDemoLicense = \CBitrix24::IsDemoLicense();
		$isAutoPay = \CBitrix24::IsLicensePaid() && \CBitrix24::isAutoPayLicense();
		$isUnlimited = \CBitrix24::isLicenseDateUnlimited();

		$licenseTill = (int)Option::get('main', '~controller_group_till', 0);
		$currentDate = (new DateTime)->getTimestamp();
		$daysLeft = $licenseTill > 0 ? (int)ceil(($licenseTill - $currentDate) / 86400) : 0;

		$tillDate = $licenseTill > 0 ? $licenseTill : null;

		$isExpired = !$isFreeLicense && !$isUnlimited && ($isAutoPay ? $daysLeft < 0 : $daysLeft <= 0);

		$defaultAlmostExpired = !$isFreeLicense && !$isUnlimited && $daysLeft >= 0;
		$portalZone = \CBitrix24::getPortalZone();
		$isCIS = in_array($portalZone, ['ru', 'by', 'kz'], true);

		$lastPaySystem = \Bitrix\Bitrix24\License::getCurrent()->getPurchaseHistory()->getLastPaySystem();

		$isAlmostExpired = false;
		if ($isCIS || $isDemoLicense)
		{
			$isAlmostExpired = $defaultAlmostExpired && !$isAutoPay && $daysLeft < 14;
		}
		elseif ($lastPaySystem === \Bitrix\Bitrix24\License\Orders\PaySystem::DIOCAL_OTHER && $isAutoPay)
		{
			$isAlmostExpired = $defaultAlmostExpired && $daysLeft < 2;
		}
		else
		{
			$isAlmostExpired = $defaultAlmostExpired && !$isAutoPay && $daysLeft < 2;
		}

		return [
			'licenseName' => \CBitrix24::getLicenseName(),
			'tillDate' => $tillDate,
			'isDemo' => $isDemoLicense,
			'isLicenseExpired' => $isExpired,
			'isLicenseAlmostExpired' => $isAlmostExpired,
			'type' => \CBitrix24::getLicenseType(),
			'isFreeLicense' => $isFreeLicense,
			'isEnterprise' => \CBitrix24::getLicenseFamily() === 'ent',
			'isDemoTrialAvailable' => \Bitrix\Bitrix24\License::getCurrent()->getDemo()->isAvailable(),
		];
	}

	private function getWorkTime()
	{
		if (!\CBXFeatures::IsFeatureEnabled('timeman')
			|| !\CModule::IncludeModule('timeman')
			|| !\CTimeMan::CanUse()
		)
		{
			return [];
		}

		if (abs(\CTimeZone::GetOffset()) > BX_TIMEMAN_WRONG_DATE_CHECK)
		{
			return ['ERROR' => 'WRONG_DATE'];
		}

		$runtimeInfo = \CTimeMan::GetRuntimeInfo(false);

		if (empty($runtimeInfo) || !is_array($runtimeInfo))
		{
			return [];
		}

		return $runtimeInfo;
	}
}
