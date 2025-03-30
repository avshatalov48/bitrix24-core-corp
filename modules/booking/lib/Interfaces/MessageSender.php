<?php

declare(strict_types=1);

namespace Bitrix\Booking\Interfaces;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Message\MessageBodyBased;
use Bitrix\Booking\Entity\Message\MessageStatus;
use Bitrix\Booking\Entity\Message\MessageTemplateBased;

use Bitrix\Main\Result;

interface MessageSender
{
	public function getModuleId(): string;

	public function getCode(): string;

	public function createMessage(): MessageTemplateBased|MessageBodyBased;

	public function getMessageStatus(int $messageId): MessageStatus;

	public function send(Booking $booking, $message): Result;

	public function canUse(): bool;
}
