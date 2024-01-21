<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\SalesCenter\Component\ReceivePaymentHelper;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\MobileManager;

final class TerminalResponsible extends Controller
{
	public function getUserMobileInfoAction(int $userId): ?array
	{
		if (!$this->checkUserViewPermissions($userId))
		{
			$this->addError(new Error('Not enough permissions'));

			return null;
		}

		$isMobileInstalled = MobileManager::getInstance()->isEnabled() && MobileManager::getInstance()->isMobileInstalledForUser($userId);
		if ($isMobileInstalled)
		{
			return [
				'isMobileInstalled' => true,
			];
		}

		$userPhoneNumbers = $this->getUserPhoneNumbers($userId);

		return [
			'isMobileInstalled' => false,
			'phones' => array_filter(array_values($userPhoneNumbers)),
		];
	}

	public function refreshDataForSendingLinkAction(int $userId): ?array
	{
		if (!$this->checkUserViewPermissions($userId))
		{
			$this->addError(new Error('Not enough permissions'));

			return null;
		}

		$senders = ReceivePaymentHelper::getSendersData();

		$userPhoneNumbers = $this->getUserPhoneNumbers($userId);

		return [
			'senders' => $senders,
			'phones' => array_filter(array_values($userPhoneNumbers)),
		];
	}

	public function sendLinkToMobileAppAction(int $userId, string $phone, string $senderId, array $entity): ?array
	{
		if (!$this->checkUserViewPermissions($userId))
		{
			$this->addError(new Error('Not enough permissions'));

			return null;
		}

		$userPhones = UserTable::getRow([
			'select' => [
				'PERSONAL_MOBILE',
				'WORK_PHONE',
				'PERSONAL_PHONE',
			],
			'filter' => [
				'=ID' => $userId,
			]
		]);

		if (!$userPhones)
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_TERMINAL_RESPONSIBLE_NOT_FOUND')));

			return [
				'isSent' => false,
			];
		}

		if (!in_array($phone, [$userPhones['PERSONAL_MOBILE'], $userPhones['WORK_PHONE'], $userPhones['PERSONAL_PHONE']], true))
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_TERMINAL_RESPONSIBLE_PHONE_NOT_SPECIFIED')));

			return [
				'isSent' => false,
			];
		}

		$entityIdentifier = new ItemIdentifier($entity['entityTypeId'], $entity['entityId']);

		$isSent = CrmManager::getInstance()->sendApplicationLinkBySms($phone, $senderId, $userId, $entityIdentifier);

		return [
			'isSent' => $isSent,
		];
	}

	private function checkUserViewPermissions(int $targetUserId): bool
	{
		if (CurrentUser::get()->getId() === $targetUserId)
		{
			return true;
		}

		if (Loader::includeModule('socialnetwork'))
		{
			return \CSocNetUser::canProfileView(CurrentUser::get()->getId(), $targetUserId);
		}

		return false;
	}

	private function getUserPhoneNumbers(int $userId): array
	{
		$userPhones = UserTable::getRow([
			'select' => [
				'PERSONAL_MOBILE',
				'WORK_PHONE',
				'PERSONAL_PHONE'
			],
			'filter' => [
				'=ID' => $userId,
			]
		]);

		return is_null($userPhones) ? [] : $userPhones;
	}
}
