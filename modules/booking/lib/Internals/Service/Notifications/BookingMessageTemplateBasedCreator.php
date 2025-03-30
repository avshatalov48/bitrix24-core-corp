<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Notifications;

use Bitrix\Booking\Entity\Message\MessageTemplateBased;
use Bitrix\Booking\Internals\Integration\Notifications\TemplateRepository;

class BookingMessageTemplateBasedCreator extends BookingMessageCreator
{
	protected MessageTemplateBased $message;

	public function __construct(MessageTemplateBased $message)
	{
		$this->message = $message;

		parent::__construct();
	}

	protected function createInfoMessage(): MessageTemplateBased|null
	{
		$resource = $this->getResource();
		if (!$resource)
		{
			return null;
		}

		return $this->createMessage(
			NotificationType::Info,
			$this->getTemplateCode(
				NotificationType::Info,
				NotificationTemplateType::from($resource->getTemplateTypeInfo())
			)
		);
	}

	protected function createConfirmationMessage(): MessageTemplateBased|null
	{
		$resource = $this->getResource();
		if (!$resource)
		{
			return null;
		}

		return $this->createMessage(
			NotificationType::Confirmation,
			$this->getTemplateCode(
				NotificationType::Confirmation,
				NotificationTemplateType::from($resource->getTemplateTypeConfirmation())
			)
		);
	}

	protected function createRemindMessage(): MessageTemplateBased|null
	{
		$resource = $this->getResource();
		if (!$resource)
		{
			return null;
		}

		return $this->createMessage(
			NotificationType::Reminder,
			$this->getTemplateCode(
				NotificationType::Reminder,
				NotificationTemplateType::from($resource->getTemplateTypeReminder())
			)
		);
	}

	protected function createFeedbackMessage(): MessageTemplateBased|null
	{
		$resource = $this->getResource();
		if (!$resource)
		{
			return null;
		}

		return $this->createMessage(
			NotificationType::Feedback,
			$this->getTemplateCode(
				NotificationType::Feedback,
				NotificationTemplateType::from($resource->getTemplateTypeFeedback())
			)
		);
	}

	protected function createDelayedMessage(): MessageTemplateBased|null
	{
		$resource = $this->getResource();
		if (!$resource)
		{
			return null;
		}

		return $this->createMessage(
			NotificationType::Delayed,
			$this->getTemplateCode(
				NotificationType::Delayed,
				NotificationTemplateType::from($resource->getTemplateTypeDelayed())
			)
		);
	}

	private function createMessage(
		NotificationType $notificationType,
		string $templateCode,
		array $placeholders = []
	): MessageTemplateBased
	{
		return $this->message
			->setTemplateCode($templateCode)
			->setPlaceholders(
				array_merge(
					$this->getDefaultPlaceholders(),
					$placeholders,
				)
			)
		;
	}

	private function getDefaultPlaceholders(): array
	{
		return [
			'DATE_FROM' => $this->getDateFrom(),
			'DATE_TO' => $this->getDateTo(),
			'DATE_TIME_FROM' => $this->getDateTimeFrom(),
			'DATE_TIME_TO' => $this->getDateTimeTo(),
			'RESOURCE_TYPE_NAME' => $this->getResourceTypeName(),
			'RESOURCE_NAME' => $this->getResourceName(),
			'CLIENT_NAME' => $this->getClientName(),
			'MANAGER_NAME' => $this->getManagerName(),
			'COMPANY_NAME' => $this->getCompanyName(),
			'CONFIRMATION_LINK' => $this->getConfirmationLink(),
			'DELAYED_CONFIRMATION_LINK' => $this->getDelayedConfirmationLink(),
			'FEEDBACK_LINK' => $this->getFeedbackLink(),
			//@todo needs to be removed after we fix issue with edna template
			'SOME_TEXT' => ' ',
		];
	}

	private function getTemplateCode(
		NotificationType $notificationType,
		NotificationTemplateType $templateType
	): string
	{
		return TemplateRepository::getTemplateCode($notificationType, $templateType);
	}
}
