<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Controllers;

use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Deadline\Command\SkipDeadlineNotificationCommand;
use Bitrix\Tasks\Deadline\Controllers\Dto\NotificationDto;
use Bitrix\Tasks\Deadline\Entity\DeadlineUserOption;
use Bitrix\Tasks\Deadline\SkipNotificationPeriod;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Rest\Controllers\Trait\ErrorResponseTrait;
use InvalidArgumentException;

class Notification extends Controller
{
	use ErrorResponseTrait;

	/**
	 * @throws BinderArgumentException
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				NotificationDto::class,
				'notificationData',
				function($className, $notificationData): ?NotificationDto {
					$dto = NotificationDto::createFromArray($notificationData);

					$period = SkipNotificationPeriod::tryFrom($notificationData['skipPeriod'] ?? '');
					if ($period === null)
					{
						$this->buildErrorResponse('skipPeriod: unexpected value');

						return null;
					}

					$dto->setSkipPeriod($period);

					return $dto;
				}
			),
		];
	}

	/**
	 * @restMethod tasks.deadline.Notification.skip
	 */
	public function skipAction(NotificationDto $notificationData): bool
	{
		try
		{
			$notificationData->validate();

			$userId = (int)CurrentUser::get()->getId();

			$entity = new DeadlineUserOption(
				userId: $userId,
				skipNotificationPeriod: $notificationData->skipPeriod,
			);

			$command = new SkipDeadlineNotificationCommand($entity);

			$command->run();

			return true;
		}
		catch (InvalidCommandException|InvalidArgumentException $e)
		{
			$this->buildErrorResponse($e->getMessage());

			return false;
		}
	}
}
