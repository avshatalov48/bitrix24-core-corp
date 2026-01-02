<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Booking;

use Bitrix\Crm\Dto\Booking\Booking\BookingStatusEnum;
use Bitrix\Crm\Dto\Booking\Message\Message;
use Bitrix\Crm\Dto\Booking\Message\MessageStatusEnum;
use Bitrix\Crm\Dto\Booking\Message\MessageTypeEnum;
use Bitrix\Crm\Dto\Booking\TagPhraseEnum;
use Bitrix\Crm\Dto\Booking\TagType;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

class TagMapper
{
	public static function mapFromMessage(Message $message): Tag|null
	{
		$tagType = null;
		$tagPhrase = null;

		if (
			$message->type === MessageTypeEnum::Info
			&& $message->status === MessageStatusEnum::Sent
		) {
			$tagType = TagType::Success;
			$tagPhrase = TagPhraseEnum::Sent;
		}
		elseif ($message->type === MessageTypeEnum::Confirmation)
		{
			if ($message->status === MessageStatusEnum::Sent)
			{
				$tagType = TagType::Secondary;
				$tagPhrase = TagPhraseEnum::SentNotRead;
			}
			elseif ($message->status === MessageStatusEnum::Read)
			{
				$tagType = TagType::Warning;
				$tagPhrase = TagPhraseEnum::OpenedNotConfirmed;
			}
		}
		elseif (
			$message->type === MessageTypeEnum::Reminder
			&& $message->status === MessageStatusEnum::Sent
		)
		{
			$tagType = TagType::Success;
			$tagPhrase = TagPhraseEnum::Sent;
		}
		elseif ($message->type === MessageTypeEnum::Delayed)
		{
			if ($message->status === MessageStatusEnum::Sent)
			{
				$tagType = TagType::Secondary;
				$tagPhrase = TagPhraseEnum::SentNotRead;
			}
			elseif ($message->status === MessageStatusEnum::Read)
			{
				$tagType = TagType::Failure;
				$tagPhrase = TagPhraseEnum::OpenedNotConfirmed;
			}
		}

		if (!$tagType || !$tagPhrase)
		{
			return null;
		}

		return self::getTag($tagPhrase, $tagType);
	}

	public static function mapFromMessageAndStatus(
		Message $message = null,
		BookingStatusEnum $status = null,
		int $statusUpdated = 0,
	): Tag|null
	{
		if (
			$message
			&& $message->timestamp >= $statusUpdated
			&& (!$status || $status === BookingStatusEnum::DelayedCounterActivated)
		)
		{
			return self::mapFromMessage($message);
		}

		if (!$status)
		{
			return null;
		}

		$tagType = match($status)
		{
			BookingStatusEnum::ComingSoon => TagType::Primary,
			BookingStatusEnum::CanceledByClient => TagType::Failure,
			default => TagType::Success,
		};

		$tagPhrase = match ($status)
		{
			BookingStatusEnum::ComingSoon => TagPhraseEnum::ComingSoon,
			BookingStatusEnum::ConfirmedByClient => TagPhraseEnum::ConfirmedByClient,
			BookingStatusEnum::ConfirmedByManager => TagPhraseEnum::ConfirmedByManager,
			BookingStatusEnum::CanceledByClient => TagPhraseEnum::CanceledByClient,
			default => null,
		};

		if (!$tagPhrase)
		{
			return null;
		}

		return self::getTag($tagPhrase, $tagType);
	}

	private static function getTag(TagPhraseEnum $tagPhrase, TagType $tagType): Tag|null
	{
		$phraseMessage = match ($tagPhrase)
		{
			TagPhraseEnum::Sent => 'CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_SENT',
			TagPhraseEnum::SentNotRead => 'CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_SENT_NOT_READ',
			TagPhraseEnum::OpenedNotConfirmed => 'CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_OPENED_NOT_CONFIRMED',
			TagPhraseEnum::ConfirmedByClient => 'CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_OPENED_CONFIRMED_BY_CLIENT',
			TagPhraseEnum::ConfirmedByManager => 'CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_OPENED_CONFIRMED_BY_MANAGER',
			TagPhraseEnum::ComingSoon => 'CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_COMING_SOON',
			TagPhraseEnum::CanceledByClient => 'CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_CANCELLED_BY_CLIENT',
		};

		return new Tag(
			title: Loc::getMessage($phraseMessage) ?? '',
			type: $tagType->value,
		);
	}
}
