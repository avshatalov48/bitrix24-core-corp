<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Notifications;

use Bitrix\Booking\Entity\Message\MessageBodyBased;

class BookingMessageBodyBasedCreator extends BookingMessageCreator
{
	protected MessageBodyBased $message;

	public function __construct(MessageBodyBased $message)
	{
		$this->message = $message;

		parent::__construct();
	}

	protected function createInfoMessage(): MessageBodyBased|null
	{
		//@todo
		$messageTemplate = "
			Hello #CLIENT_NAME#,
			
			Info message template
			
			Resource Type Name: #RESOURCE_TYPE_NAME#
			Resource Name: #RESOURCE_NAME#
			DateFrom: #DATE_FROM#
			DateTimeFrom: #DATE_TIME_FROM#
			DateTo: #DATE_TO#
			DateTimeTo: #DATE_TIME_TO#
			ConfirmationLink: #CONFIRMATION_LINK#
			DelayedConfirmationLink: #DELAYED_CONFIRMATION_LINK#
			FeedbackLink: #FEEDBACK_LINK#
			
			#MANAGER_NAME#
			#COMPANY_NAME#
		";

		$messageBody = $this->replaceDefaultVariables($messageTemplate);

		return $this->message->setMessageBody($messageBody);
	}

	protected function createConfirmationMessage(): MessageBodyBased|null
	{
		$messageTemplate = "
			Hello #CLIENT_NAME#,
			
			Confirmation message template
			
			Resource Type Name: #RESOURCE_TYPE_NAME#
			Resource Name: #RESOURCE_NAME#
			DateFrom: #DATE_FROM#
			DateTimeFrom: #DATE_TIME_FROM#
			DateTo: #DATE_TO#
			DateTimeTo: #DATE_TIME_TO#
			ConfirmationLink: #CONFIRMATION_LINK#
			DelayedConfirmationLink: #DELAYED_CONFIRMATION_LINK#
			FeedbackLink: #FEEDBACK_LINK#
			
			#MANAGER_NAME#
			#COMPANY_NAME#
		";

		$messageBody = $this->replaceDefaultVariables($messageTemplate);

		return $this->message->setMessageBody($messageBody);
	}

	protected function createRemindMessage(): MessageBodyBased|null
	{
		$messageTemplate = "
			Hello #CLIENT_NAME#,
			
			Remind message template
			
			Resource Type Name: #RESOURCE_TYPE_NAME#
			Resource Name: #RESOURCE_NAME#
			DateFrom: #DATE_FROM#
			DateTimeFrom: #DATE_TIME_FROM#
			DateTo: #DATE_TO#
			DateTimeTo: #DATE_TIME_TO#
			ConfirmationLink: #CONFIRMATION_LINK#
			DelayedConfirmationLink: #DELAYED_CONFIRMATION_LINK#
			FeedbackLink: #FEEDBACK_LINK#
			
			#MANAGER_NAME#
			#COMPANY_NAME#
		";

		$messageBody = $this->replaceDefaultVariables($messageTemplate);

		return $this->message->setMessageBody($messageBody);
	}

	protected function createFeedbackMessage(): MessageBodyBased|null
	{
		$messageTemplate = "
			Hello #CLIENT_NAME#,
			
			Feedback message template
			
			Resource Type Name: #RESOURCE_TYPE_NAME#
			Resource Name: #RESOURCE_NAME#
			DateFrom: #DATE_FROM#
			DateTimeFrom: #DATE_TIME_FROM#
			DateTo: #DATE_TO#
			DateTimeTo: #DATE_TIME_TO#
			ConfirmationLink: #CONFIRMATION_LINK#
			DelayedConfirmationLink: #DELAYED_CONFIRMATION_LINK#
			FeedbackLink: #FEEDBACK_LINK#
			
			#MANAGER_NAME#
			#COMPANY_NAME#
		";

		$messageBody = $this->replaceDefaultVariables($messageTemplate);

		return $this->message->setMessageBody($messageBody);
	}

	protected function createDelayedMessage(): MessageBodyBased|null
	{
		$messageTemplate = "
			Hello #CLIENT_NAME#,
			
			Delayed message template
			
			Resource Type Name: #RESOURCE_TYPE_NAME#
			Resource Name: #RESOURCE_NAME#
			DateFrom: #DATE_FROM#
			DateTimeFrom: #DATE_TIME_FROM#
			DateTo: #DATE_TO#
			DateTimeTo: #DATE_TIME_TO#
			ConfirmationLink: #CONFIRMATION_LINK#
			DelayedConfirmationLink: #DELAYED_CONFIRMATION_LINK#
			FeedbackLink: #FEEDBACK_LINK#
			
			#MANAGER_NAME#
			#COMPANY_NAME#
		";

		$messageBody = $this->replaceDefaultVariables($messageTemplate);

		return $this->message->setMessageBody($messageBody);
	}

	private function replaceDefaultVariables(string $messageTemplate): string
	{
		$map = [
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
		];

		return str_replace(
			array_map(static fn($item) => '#' . $item . '#', array_keys($map)),
			array_values($map),
			$messageTemplate
		);
	}
}
