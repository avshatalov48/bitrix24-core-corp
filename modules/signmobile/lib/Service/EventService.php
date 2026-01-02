<?php

namespace Bitrix\SignMobile\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Mobile;
use Bitrix\Sign;
use Bitrix\Main;
use Bitrix\SignMobile\Type\NotificationType;
use Bitrix\SignMobile\Item\Notification;
use Bitrix\SignMobile\Service;
use Bitrix\SignMobile\Repository\NotificationQueueRepository;
use Bitrix\SignMobile\Repository\NotificationRepository;

class EventService
{
	private const EVENT_NAME_FOUND_DOCUMENT_FOR_SIGNING = 'SIGN_MOBILE_FOUND_DOCUMENT_FOR_SIGNING';
	private const EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION = 'SIGN_MOBILE_REQUEST_FOR_SIGN_CONFIRMATION';
	private const INCLUDE_REQUIRED_MODULES_ERROR_TEXT = 'Modules must be installed: mobile, sign';

	private NotificationRepository $notificationRepository;
	private NotificationQueueRepository $notificationQueueRepository;
	private Sign\Service\MobileService $mobileService;

	public function __construct(
		?NotificationRepository $notificationRepository = null,
		?NotificationQueueRepository $notificationQueueRepository = null,
		?Sign\Service\MobileService $mobileService = null,
	)
	{
		$this->notificationRepository = $notificationRepository ?? Service\Container::instance()->getNotificationRepository();
		$this->notificationQueueRepository = $notificationQueueRepository ?? Service\Container::instance()->getNotificationQueueRepository();
		$this->mobileService = $mobileService ?? Sign\Service\Container::instance()->getMobileService();
	}

	private function includeRequiredModules(): bool
	{
		return Loader::includeModule('mobile') && Loader::includeModule('sign');
	}

	private function getPriorityLinkNotification(int $userId, Main\Type\DateTime $searchFromDate = null): ?Sign\Item\Mobile\Link
	{
		$notification = $this->notificationQueueRepository->getOne($userId, NotificationType::PUSH_RESPONSE_SIGNING, $searchFromDate);

		if (is_null($notification))
		{
			$this->notificationQueueRepository->deleteOlderThan($userId, $searchFromDate);
			return null;
		}

		$linkResult = $this->mobileService->getLinkForSigning($notification->getSignMemberId());

		if ($linkResult->isSuccess() && ($link = $linkResult->getLink()) && $link->canBeConfirmed())
		{
			return $link;
		}

		if ($this->notificationQueueRepository->delete($notification)->isSuccess())
		{
			return $this->getPriorityLinkNotification($userId, $searchFromDate);
		}

		return null;
	}

	public function sendPriorityDocumentNotificationToSign(int $userId, ?Main\Type\DateTime $notificationCreationDate = null, ?Main\Type\DateTime $searchNotificationsStartingFromDate = null): Main\Result
	{
		$result = new Main\Result();

		if (!$this->includeRequiredModules())
		{
			$result->addError(new Main\Error(self::INCLUDE_REQUIRED_MODULES_ERROR_TEXT));

			return $result;
		}

		if (is_null($notificationCreationDate))
		{
			$notificationCreationDate = Main\Type\DateTime::createFromTimestamp(time());
		}

		if (is_null($searchNotificationsStartingFromDate))
		{
			$searchNotificationsStartingFromDate = (new DateTime())->add('-1D');
		}

		$lastNotificationResponse = $this->notificationRepository->getOne($userId, NotificationType::PUSH_RESPONSE_SIGNING);

		if (!is_null($lastNotificationResponse))
		{
			/*
				The issues check is performed so that the error does not fall into the log.
				Here we delete the line if there is one.
			 */
			$this->notificationQueueRepository->delete($lastNotificationResponse)->isSuccess();
		}

		$priorityNotificationLink = $this->getPriorityLinkNotification($userId, $searchNotificationsStartingFromDate);

		$typeNotification = NotificationType::PUSH_RESPONSE_SIGNING;

		if (is_null($priorityNotificationLink))
		{
			$linkResult = $this->mobileService
				->setDarkMode(false)
				->getNextSigningIfExists($userId)
			;

			if ($linkResult->isSuccess() && $link = $linkResult->getLink())
			{
				$priorityNotificationLink = $link;
			}
			else
			{
				return $result;
			}

			$typeNotification = NotificationType::PUSH_FOUND_FOR_SIGNING;
		}

		$priorityNotification = new Notification(
			$typeNotification,
			$userId,
			$priorityNotificationLink->memberId,
			dateCreate: $notificationCreationDate,
		);

		$url = $priorityNotificationLink->url;

		$notification = new Notification(
			$priorityNotification->getType(),
			$priorityNotification->getUserId(),
			$priorityNotification->getSignMemberId(),
			$notificationCreationDate,
		);

		if (!is_null($url))
		{
			$notificationHasBeenAdded = true;

			if ($this->notificationRepository->insertIgnore($notification) === 0)
			{
				$notificationHasBeenAdded = false;

				$foundNotification = $this->notificationRepository->getOne($notification->getUserId(), $notification->getType());

				if (!is_null($foundNotification))
				{
					if ($foundNotification->getSignMemberId() !== $notification->getSignMemberId())
					{
						$notification->id = $foundNotification->getId();
						$updateResult = $this->notificationRepository->update($notification);
						$notificationHasBeenAdded = $updateResult->isSuccess();
					}
				}
			}

			if($notificationHasBeenAdded)
			{
				$type = match ($priorityNotification->getType()) {
					NotificationType::PUSH_FOUND_FOR_SIGNING => self::EVENT_NAME_FOUND_DOCUMENT_FOR_SIGNING,
					NotificationType::PUSH_RESPONSE_SIGNING => self::EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION,
				};

				$title = Main\Localization\Loc::getMessage('SIGN_MOBILE_SERVICE_EVENT_TITLE');
				$body = Main\Localization\Loc::getMessage('SIGN_MOBILE_SERVICE_EVENT_REQUEST_BODY');
				$payload = [
					'memberId' => $notification->getSignMemberId(),
					'role' => $priorityNotificationLink->getRole(),
					'isGoskey' => $priorityNotificationLink->isGoskey(),
					'isExternal' => $priorityNotificationLink->isExternal(),
					'initiatedByType' => $priorityNotificationLink->getInitiatedByType(),
					'document' => [
						'url' => $url,
						'title' => $priorityNotificationLink->documentTitle,
					],
				];

				$applicationMessage = new Mobile\Push\Message($type, $title, payload: $payload);
				$deviceMessage = new Mobile\Push\Message($type, $title, $body, $payload);
				Mobile\Push\Sender::sendContextMessage($userId, $applicationMessage, $deviceMessage);
			}
		}

		return $result;
	}

	public function sendSignConfirmation(int $userId, Sign\Item\Mobile\Link $link, ?Main\Type\DateTime $notificationCreationDate = null): Main\Result
	{
		$result = new Main\Result();

		if (!$this->includeRequiredModules())
		{
			$result->addError(new Main\Error(self::INCLUDE_REQUIRED_MODULES_ERROR_TEXT));

			return $result;
		}

		$this->notificationQueueRepository->add(
			new Notification(
				NotificationType::PUSH_RESPONSE_SIGNING,
				$userId,
				$link->memberId,
				dateCreate: $notificationCreationDate ?? Main\Type\DateTime::createFromTimestamp(time())
			)
		);

		$title = Main\Localization\Loc::getMessage('SIGN_MOBILE_SERVICE_EVENT_TITLE');
		$body = Main\Localization\Loc::getMessage('SIGN_MOBILE_SERVICE_EVENT_CONFIRM_BODY');

		$payload = [
			'forcedBannerOpening' => true,
			'memberId' => $link->memberId,
			'initiatedByType' => $link->getInitiatedByType(),
			'document' => [
				'role' => $link->getRole(),
				'url' => $link->url,
				'title' => $link->documentTitle
			]
		];

		$applicationMessage = new Mobile\Push\Message(self::EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION, $title, payload: $payload);
		$deviceMessage = new Mobile\Push\Message(self::EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION, $title, $body, $payload);

		return Mobile\Push\Sender::sendContextMessage($userId, $applicationMessage, $deviceMessage);
	}
}
