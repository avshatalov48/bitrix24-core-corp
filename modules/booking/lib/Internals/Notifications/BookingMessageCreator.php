<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Notifications;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Integration\Booking\ProviderManager;
use Bitrix\Booking\Integration\Booking\Message\Message;
use Bitrix\Booking\Integration\Booking\Message\MessageSender;
use Bitrix\Booking\Integration\Crm\MyCompany;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Feature\BookingConfirmLink;
use Bitrix\Booking\Internals\NotificationType;
use DateTime;
use DateTimeImmutable;
use Bitrix\Main\Context;

abstract class BookingMessageCreator
{
	protected ProviderManager $providerManager;

	protected MessageSender $messageSender;
	protected Booking $booking;
	protected Context\Culture|null $culture = null;

	/**
	 * @param MessageSender $messageSender
	 */
	public function __construct(MessageSender $messageSender)
	{
		$this->messageSender = $messageSender;
		$this->providerManager = Container::getProviderManager();
		$this->culture = Context::getCurrent()->getCulture();
	}

	public function setBooking(Booking $booking): static
	{
		$this->booking = $booking;

		return $this;
	}

	public function createMessageOfType(NotificationType $notificationType): Message|null
	{
		if ($notificationType === NotificationType::Info)
		{
			return $this->createInfoMessage();
		}
		elseif ($notificationType === NotificationType::Confirmation)
		{
			return $this->createConfirmationMessage();
		}
		elseif ($notificationType === NotificationType::Reminder)
		{
			return $this->createRemindMessage();
		}
		elseif ($notificationType === NotificationType::Delayed)
		{
			return $this->createDelayedMessage();
		}
		elseif ($notificationType === NotificationType::Feedback)
		{
			return $this->createFeedbackMessage();
		}

		return null;
	}

	abstract protected function createInfoMessage(): Message|null;

	abstract protected function createConfirmationMessage(): Message|null;

	abstract protected function createRemindMessage(): Message|null;

	abstract protected function createFeedbackMessage(): Message|null;

	abstract protected function createDelayedMessage(): Message|null;

	protected function getClientName(): string
	{
		$clientProvider = $this->providerManager::getProviderByBooking($this->booking)?->getClientProvider();
		if (!$clientProvider)
		{
			return '';
		}

		$primaryClient = $this->booking->getPrimaryClient();
		if (!$primaryClient)
		{
			return '';
		}

		return $clientProvider->getClientName($primaryClient);
	}

	protected function getResource(): Resource|null
	{
		return $this->booking->getPrimaryResource();
	}

	protected function getResourceName(): string
	{
		$resource = $this->getResource();
		if (!$resource)
		{
			return '';
		}

		return $resource->getName() ?? '';
	}

	protected function getResourceTypeName(): string
	{
		$resource = $this->getResource();
		if (!$resource)
		{
			return '';
		}

		$resourceType = $resource->getType();
		if (!$resourceType)
		{
			return '';
		}

		return $resourceType->getName() ?? '';
	}

	protected function getManagerName(): string
	{
		$managerId = $this->booking->getCreatedBy();
		if (!$managerId)
		{
			return '';
		}

		$user = \CUser::getById($managerId)->fetch();
		if (!$user)
		{
			return '';
		}

		return $user['NAME'] ?? '';
	}

	protected function getDateFrom(): string
	{
		$dateFrom = $this->booking->getDatePeriod()?->getDateFrom();
		if (!$dateFrom)
		{
			return '';
		}

		return $this->getDayMonthFormattedDateTime($dateFrom);
	}

	protected function getDateTo(): string
	{
		$dateTo = $this->booking->getDatePeriod()?->getDateTo();
		if (!$dateTo)
		{
			return '';
		}

		return $this->getDayMonthFormattedDateTime($dateTo);
	}

	protected function getDateTimeFrom(): string
	{
		$dateFrom = $this->booking->getDatePeriod()?->getDateFrom();
		if (!$dateFrom)
		{
			return '';
		}

		return implode(
			' ',
			[
				$this->getShortTimeFormattedDateTime($dateFrom),
				$this->getDayMonthFormattedDateTime($dateFrom),
			]
		);
	}

	protected function getDateTimeTo(): string
	{
		$dateTo = $this->booking->getDatePeriod()?->getDateTo();
		if (!$dateTo)
		{
			return '';
		}

		return implode(
			' ',
			[
				$this->getShortTimeFormattedDateTime($dateTo),
				$this->getDayMonthFormattedDateTime($dateTo),
			]
		);
	}

	protected function getCompanyName(): string
	{
		$myCrmCompanyName = MyCompany::getName();
		if ($myCrmCompanyName)
		{
			return $myCrmCompanyName;
		}

		/**
		 * We need to keep a space here so that to match EDNA templates containing company name variable
		 */

		return ' ';
	}

	protected function getConfirmationLink(): string
	{
		return (new BookingConfirmLink())->getLink($this->booking);
	}

	protected function getDelayedConfirmationLink(): string
	{
		return $this->getConfirmationLink();
	}

	protected function getFeedbackLink(): string
	{
		//@todo
		return '';
	}

	private function getCultureFormat(string $formatCode): string
	{
		if (!$this->culture)
		{
			return '';
		}

		$format = $this->culture->get($formatCode);

		return $format ?? '';
	}

	private function getDayMonthFormattedDateTime(DateTimeImmutable $dateTime): string
	{
		return $this->formatDateTime(
			$dateTime,
			$this->getCultureFormat('DAY_MONTH_FORMAT')
		);
	}

	private function getShortTimeFormattedDateTime(DateTimeImmutable $dateTime): string
	{
		return $this->formatDateTime(
			$dateTime,
			$this->getCultureFormat('SHORT_TIME_FORMAT')
		);
	}

	private function formatDateTime(DateTimeImmutable $dateTime, string $format): string
	{
		$userTimezoneOffset = $dateTime->getTimezone()->getOffset(new DateTime());
		$serverTimezoneOffset = (new DateTime())->getTimezone()->getOffset(new DateTime());

		return FormatDate(
			$format,
			$dateTime->getTimestamp() + ($userTimezoneOffset - $serverTimezoneOffset)
		);
	}
}
