<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\Calendar\Controller\SyncAjax;
use Bitrix\Calendar\Core\Role\Helper;
use Bitrix\Calendar\Core\Role\User;
use Bitrix\Calendar\Integration\Pull\PushCommand;
use Bitrix\Calendar\Util;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;

class Sync extends Controller
{
    public function getSyncInfoAction(): array
    {
		$userId = \CCalendar::GetCurUserId();

		if (!$userId)
		{
			$this->addError(new Error('User not found'));

			return [];
		}

		$calculateTimestamp = \CCalendarSync::getTimestampWithUserOffset($userId);

		$syncInfo = \CCalendarSync::getNewSyncItemsInfo($userId, $calculateTimestamp);

		return $this->prepareSyncInfoResult($syncInfo);
    }

	public function getSectionsForProviderAction(int $connectionId, string $type): array
	{
		$sectionType = [];

		switch ($type)
		{
			case \Bitrix\Calendar\Sync\Icloud\Helper::ACCOUNT_TYPE:
			case \Bitrix\Calendar\Sync\Office365\Helper::ACCOUNT_TYPE:
				$sectionType = [$type];
				break;
			case \Bitrix\Calendar\Sync\Google\Helper::CONNECTION_NAME:
				$sectionType = \Bitrix\Calendar\Sync\Google\Dictionary::ACCESS_ROLE_TO_EXTERNAL_TYPE;
				break;
		}

		if (!empty($sectionType))
		{
			return [
				'sections' => \CCalendarSect::getAllSectionsForVendor($connectionId, $sectionType)
			];
		}

		return [];
	}

	public function deactivateConnectionAction(int $connectionId): bool
	{
		return \CCalendarSync::deactivateConnection($connectionId);
	}

	public function changeSectionStatusAction(?int $sectionId, $status): bool
	{
		$sectionsStatus = [];
		$userId = \CCalendar::GetCurUserId();
		$status = $status === 'true';

		if ($userId && (int)$sectionId)
		{
			$sectionsStatus[$sectionId] = $status;

			\CCalendarSync::SetSectionStatus($userId, $sectionsStatus);

			return true;
		}

		return false;
	}


	public function createGoogleConnectionAction(): array
	{
		$response = [
			'status' => 'error',
			'message' => 'Could not finish sync.',
		];

		if (!Loader::includeModule('dav'))
		{
			$this->addError(new Error('Module dav is required'));

			return $response;
		}

		if (!\CCalendar::isGoogleApiEnabled())
		{
			$this->addError(new Error('Api not enabled'));

			return $response;
		}

		$this->setPushCalendarState(false);

		$result = (new \Bitrix\Calendar\Sync\Google\StartSynchronizationManager(\CCalendar::GetCurUserId()))->synchronize();

		if (!empty($result['status']) && $result['status'] === 'success')
		{
			$this->sendPushConnectionSuccess('google');
		}

		$this->setPushCalendarState(true);

		return $result;
	}

	public function createOffice365ConnectionAction(): array
	{
		if (!Loader::includeModule('dav'))
		{
			return [
				'status' => 'error',
				'message' => 'Module dav is required',
			];
		}

		if (!Loader::includeModule('socialservices'))
		{
			return [
				'status' => 'error',
				'message' => 'Module socialservices is required',
			];
		}

		$owner = Helper::getRole(\CCalendar::GetUserId(), User::TYPE);

		$this->setPushCalendarState(false);

		$result = (new \Bitrix\Calendar\Sync\Office365\StartSyncController($owner))->synchronize();

		if (!empty($result['status']) && $result['status'] === 'success')
		{
			$this->sendPushConnectionSuccess('office365');
		}

		$this->setPushCalendarState(true);

		return $result;
	}

	public function createIcloudConnectionAction(string $appleId, string $appPassword): array
	{
		$appleId = trim($appleId);
		$appPassword = trim($appPassword);

		if (!Loader::includeModule('dav'))
		{
			$this->addError(new Error('Module dav is required'));

			return [
				'status' => 'error',
				'message' => 'Module dav is required',
			];
		}

		if (!preg_match("/[a-z]{4}-[a-z]{4}-[a-z]{4}-[a-z]{4}/", $appPassword))
		{
			$this->addError(new Error('Incorrect app password'));

			return [
				'status' => 'incorrect_app_pass',
				'message' => 'Incorrect app password'
			];
		}

		$connectionId = (new \Bitrix\Calendar\Sync\Icloud\VendorSyncManager())->initConnection($appleId, $appPassword);
		if (!$connectionId)
		{
			$this->addError(new Error('Connection not found'));

			return [
				'status' => 'error',
				'message' => 'Connection not found',
			];
		}

		return [
			'status' => 'success',
			'connectionId' => $connectionId
		];
	}

	public function syncIcloudConnectionAction(int $connectionId): array
	{
		if (!Loader::includeModule('dav'))
		{
			$this->addError(new Error('Module dav is required'));

			return [
				'status' => 'error',
				'message' => 'Module dav is required',
			];
		}

		$this->setPushCalendarState(false);

		$result = (new \Bitrix\Calendar\Sync\Icloud\VendorSyncManager())->syncIcloudConnection($connectionId);

		if ($result['status'] === 'error' && $result['message'])
		{
			$this->addError(new Error($result['message']));
		}
		else
		{
			$this->sendPushConnectionSuccess('icloud');
		}

		$this->setPushCalendarState(true);

		return $result;
	}

	public function clearSuccessfulConnectionNotifierAction(string $type): void
	{
		\Bitrix\Calendar\Sync\Managers\NotificationManager::clearFinishedSyncNotificationAgent(
			\CCalendar::GetUserId(),
			$type
		);
	}

	public function updateConnectionsAction(): bool
	{
		$userId = \CCalendar::GetCurUserId();

		if (!$userId)
		{
			return false;
		}

		return \CCalendarSync::UpdateUserConnections();
	}


	public function getConnectionLinkAction(string $type): array
	{
		$result = [];

		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			$this->addError(new Error('Access denied', 403));

			return $result;
		}

		$connectionPath = '/bitrix/services/main/ajax.php';
		$userId = \CCalendar::GetCurUserId();
		$siteId = \CSite::GetDefSite();

		$hash = \CUser::GetHitAuthHash($connectionPath, $userId, $siteId);
		if (!$hash)
		{
			$hash = \CUser::AddHitAuthHash($connectionPath, $userId, $siteId, 7200);
		}

		if (!$hash || empty($type))
		{
			$this->addError(new Error('Error while trying to receive link', 404));

			return $result;
		}

		$result['connectionLink'] = UrlManager::getInstance()->createByController(
			new SyncAjax(),
			'handleMobileAuth',
			[
				'serviceName' => $type,
				'hitHash' => $hash,
			],
			true
		);

		return $result;
	}

	private function prepareSyncInfoResult($syncInfo): array
	{
		$defaultSyncData = static function($name){
			return [
				'type' => $name,
				'active' => false,
				'connected' => false,
			];
		};

		$result = [
			'google' => !empty($syncInfo['google']) ? $syncInfo['google'] : $defaultSyncData('google'),
			'office365' => !empty($syncInfo['office365']) ? $syncInfo['office365'] : $defaultSyncData('office365'),
			'icloud' => !empty($syncInfo['icloud']) ? $syncInfo['icloud'] : $defaultSyncData('icloud'),
		];

		return [
			'syncInfo' => $result,
		];
	}

	private function sendPushConnectionSuccess(string $vendorName): void
	{
		Util::addPullEvent(
			PushCommand::HandleSuccessfulConnection,
			\CCalendar::GetCurUserId(),
			[
				'vendorName' => $vendorName,
			]
		);
	}

	private function setPushCalendarState(bool $state): void
	{
		\CCalendarEvent::$sendPush = $state;
		\CCalendarSect::$sendPush = $state;
	}
}
