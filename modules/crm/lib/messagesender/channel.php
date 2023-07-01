<?php

namespace Bitrix\Crm\MessageSender;

use Bitrix\Crm\MessageSender\Channel\Correspondents;
use Bitrix\Crm\MessageSender\Channel\ErrorCode;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

final class Channel
{
	/** @var string|ICanSendMessage */
	private string $sender;
	private array $channelInfo;
	/** @var Correspondents\From[] */
	private array $fromList = [];
	/** @var Correspondents\To[] */
	private array $toList = [];
	private int $userId;

	/**
	 * @param string $sender
	 * @param array{
	 *     id: string,
	 *     isDefault: bool,
	 *     name: string,
	 *     shortName: string,
	 * } $channelInfo
	 * @param Correspondents\From[] $fromList
	 * @param Correspondents\To[] $toList
	 * @param int $userId
	 * @throws ArgumentTypeException
	 */
	public function __construct(string $sender, array $channelInfo, array $fromList, array $toList, int $userId)
	{
		if (!is_a($sender, ICanSendMessage::class, true))
		{
			throw new ArgumentTypeException('sender', ICanSendMessage::class);
		}
		$this->sender = $sender;

		$this->channelInfo = $channelInfo;

		foreach ($fromList as $from)
		{
			if (!($from instanceof Correspondents\From))
			{
				throw new ArgumentTypeException('from', Correspondents\From::class);
			}
			$this->fromList[] = $from;
		}

		foreach ($toList as $to)
		{
			if (!($to instanceof Correspondents\To))
			{
				throw new ArgumentTypeException('to', Correspondents\To::class);
			}
			$this->toList[] = $to;
		}

		$this->userId = $userId;
	}

	/**
	 * @return string|ICanSendMessage
	 */
	public function getSender(): string
	{
		return $this->sender;
	}

	public function getId(): string
	{
		return (string)($this->channelInfo['id'] ?? '');
	}

	public function canSendMessage(): bool
	{
		return $this->checkChannel()->isSuccess() && $this->checkCommunications()->isSuccess();
	}

	public function checkChannel(): Result
	{
		return $this->sender::canSendMessageViaChannel($this);
	}

	public function checkCommunications(): Result
	{
		$result = new Result();

		if (empty($this->toList))
		{
			$result->addError(ErrorCode::getNoReceiversError());
		}

		return $result;
	}

	/**
	 * @return Correspondents\From[]
	 */
	public function getFromList(): array
	{
		return $this->fromList;
	}

	/**
	 * @return Correspondents\To[]
	 */
	public function getToList(): array
	{
		return $this->toList;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function isDefault(): bool
	{
		if (isset($this->channelInfo['isDefault']) && is_bool($this->channelInfo['isDefault']))
		{
			return $this->channelInfo['isDefault'];
		}

		return false;
	}

	public function getName(): string
	{
		if (isset($this->channelInfo['name']) && is_string($this->channelInfo['name']))
		{
			return $this->channelInfo['name'];
		}

		return '';
	}

	public function getShortName(): string
	{
		if (isset($this->channelInfo['shortName']) && is_string($this->channelInfo['shortName']))
		{
			return $this->channelInfo['shortName'];
		}

		return '';
	}
}
