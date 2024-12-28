<?php

namespace Bitrix\ImOpenLines\V2\Status;

enum Status: int
	implements \JsonSerializable
{
	// New dialog opens.
	case NEW = 0;

	// The operator sent the dialog to the queue.
	case SKIP = 5;

	// The operator took the dialogue to work.
	case ANSWER = 10;

	// The client is waiting for the operator's response.
	case CLIENT = 20;

	// The client is waiting for the operator's answer (new question after answer).
	case CLIENT_AFTER_OPERATOR = 25;

	// The operator responded to the client.
	case OPERATOR = 40;

	// The dialogue in the mode of closing (pending the vote or after auto-answer).
	case WAIT_CLIENT = 50;

	// The conversation has ended.
	case CLOSE = 60;

	// Spam / forced termination.
	case SPAM = 65;

	// Duplicate session. The session is considered closed.
	case DUPLICATE = 69;

	// Closed without sending special messages and notifications.
	case SILENTLY_CLOSE = 75;


	public static function getMap(): array
	{
		$map = [];

		foreach (self::cases() as $status)
		{
			$statusGroup = $status->getStatusGroup()->name;

			$map[$statusGroup][$status->name] = $status->value;
		}

		return $map;
	}

	/**
	 * @return StatusGroup (method returns corresponding StatusGroup Enum)
	 */
	public function getStatusGroup(): StatusGroup
	{
		return match($this)
		{
			self::NEW, self::SKIP => StatusGroup::NEW,
			self::ANSWER, self::CLIENT, self::CLIENT_AFTER_OPERATOR => StatusGroup::WORK,
			self::OPERATOR, self::WAIT_CLIENT, self::CLOSE, self::SPAM, self::DUPLICATE, self::SILENTLY_CLOSE => StatusGroup::ANSWERED,
		};
	}

	public function jsonSerialize(): array
	{
		return [
			'STRING_CODE' => $this->name,
			'NUMERICAL_CODE' => $this->value,
			'STATUS_GROUP' => $this->getStatusGroup(),
		];
	}
}