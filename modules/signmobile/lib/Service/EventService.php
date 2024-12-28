<?php

namespace Bitrix\SignMobile\Service;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Mobile\Push\Message;
use Bitrix\Mobile\Push\Sender;
use Bitrix\Sign\Item\Mobile\Link;
use Bitrix\Sign;
use Bitrix\Main;
use Bitrix\SignMobile\Type\NotificationType;
use Bitrix\SignMobile\Item\Notification;
use Bitrix\SignMobile\Service;

const EVENT_NAME_FOUND_DOCUMENT_FOR_SIGNING = 'SIGN_MOBILE_FOUND_DOCUMENT_FOR_SIGNING';
const EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION = 'SIGN_MOBILE_REQUEST_FOR_SIGN_CONFIRMATION';

class EventService
{
	private static function includeRequiredModules(): bool
	{
		return Loader::includeModule('mobile') && Loader::includeModule('sign');
	}

	public static function checkDocumentsSentForSigning(): void
	{
		if (!self::includeRequiredModules())
		{
			return;
		}

		Main\Application::getInstance()->addBackgroundJob(function ()
		{
			$service = Sign\Service\Container::instance()->getMobileService();
			$currentUserId = (int)CurrentUser::get()->getId();
			$currentTime = DateTime::createFromTimestamp(time());

			$notificationRepository = Service\Container::instance()->getNotificationRepository();
			$notificationPriorityQueueRepository = Service\Container::instance()->getNotificationPriorityQueueRepository();

			$lastNotificationResponse = $notificationRepository->getByType(NotificationType::PUSH_RESPONSE_SIGNING);

			if (!is_null($lastNotificationResponse))
			{
				/*
					The issues check is performed so that the error does not fall into the log.
					Here we delete the line if there is one.
				 */
				$notificationPriorityQueueRepository->deleteModelRow($lastNotificationResponse)->isSuccess();
			}

			$priorityNotificationLink = $notificationPriorityQueueRepository->getPriorityLinkNotification($currentUserId);

			$typeNotification = NotificationType::PUSH_RESPONSE_SIGNING;

			if (is_null($priorityNotificationLink))
			{
				$linkResult = $service
					->setDarkMode(false)
					->getNextSigningIfExists($currentUserId)
				;

				if ($linkResult->isSuccess() && $link = $linkResult->getLink())
				{
					$priorityNotificationLink = $link;
				}
				else
				{
					return;
				}

				$typeNotification = NotificationType::PUSH_FOUND_FOR_SIGNING;
			}

			$priorityNotification = new Notification(
				$typeNotification,
				$currentUserId,
				$priorityNotificationLink->memberId,
				dateCreate: $currentTime,
			);

			$url = $priorityNotificationLink->url;

			$notification = new Notification(
				$priorityNotification->getType(),
				$priorityNotification->getUserId(),
				$priorityNotification->getSignMemberId(),
				DateTime::createFromTimestamp(time())
			);

			if (!is_null($url) && $notificationRepository->insertIfDifferent($notification))
			{
				$type = match ($priorityNotification->getType()) {
					NotificationType::PUSH_FOUND_FOR_SIGNING => EVENT_NAME_FOUND_DOCUMENT_FOR_SIGNING,
					NotificationType::PUSH_RESPONSE_SIGNING => EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION,
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

				$applicationMessage = new Message($type, $title, payload: $payload);
				$deviceMessage = new Message($type, $title, $body, $payload);
				Sender::sendContextMessage($currentUserId, $applicationMessage, $deviceMessage);
			}
		});
	}

	public static function sendSignConfirmation(int $userId, Link $link): Result
	{
		if (!self::includeRequiredModules())
		{
			return (new Result())->addError(new Error('mobile load error'));
		}

		$notificationPriorityQueueRepository = Service\Container::instance()->getNotificationPriorityQueueRepository();

		$notificationPriorityQueueRepository->insertIfDifferent(
			new Notification(
				NotificationType::PUSH_RESPONSE_SIGNING,
				$userId,
				$link->memberId,
				dateCreate: DateTime::createFromTimestamp(time())
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

		$applicationMessage = new Message(EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION, $title, payload: $payload);
		$deviceMessage = new Message(EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION, $title, $body, $payload);
		return Sender::sendContextMessage($userId, $applicationMessage, $deviceMessage);
	}
}
