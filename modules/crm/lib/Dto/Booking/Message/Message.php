<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking\Message;

use Bitrix\Main\Type\Contract\Arrayable;

class Message implements Arrayable
{
	public function __construct(
		public readonly MessageTypeEnum $type,
		public readonly MessageStatusEnum $status,
		public readonly int $timestamp = 0,
	)
	{
	}

	public static function mapFromArray(array $params): self
	{
		return new self(
			MessageTypeEnum::from($params['type']),
			MessageStatusEnum::from($params['status']),
			$params['timestamp'] ?? 0,
		);
	}

	public function toArray(): array
	{
		return [
			'type' => $this->type->value,
			'status' => $this->status->value,
			'timestamp' => $this->timestamp,
		];
	}

	/**
	 * Support special log message in timeline.
	 *
	 * @return bool
	 */
	public function isSupported(): bool
	{
		return
			(
				$this->type === MessageTypeEnum::Info
				&& $this->status === MessageStatusEnum::Sent
			)
			|| (
				$this->type === MessageTypeEnum::Confirmation
				&& in_array($this->status, [MessageStatusEnum::Sent, MessageStatusEnum::Read], true)
			)
			|| (
				$this->type === MessageTypeEnum::Reminder
				&& $this->status === MessageStatusEnum::Sent
			)
			|| (
				$this->type === MessageTypeEnum::Delayed
				&& in_array($this->status, [MessageStatusEnum::Sent, MessageStatusEnum::Read], true)
			);
	}

	/**
	 * Should be transferred to timeline scheduled card.
	 *
	 * @return bool
	 */
	public function isMeaning(): bool
	{
		return in_array(
			$this->type,
			[MessageTypeEnum::Confirmation, MessageTypeEnum::Delayed],
			true
		);
	}
}
