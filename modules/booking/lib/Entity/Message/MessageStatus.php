<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Message;

use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/booking/lib/Integration/Booking/Message/MessageStatus.php');

class MessageStatus
{
	public const CODE_SENDING = 'sending';
	public const CODE_SENT = 'sent';
	public const CODE_DELIVERED = 'delivered';
	public const CODE_READ = 'read';
	public const CODE_FAILED = 'failed';

	public const SEMANTIC_SUCCESS = 'success';
	public const SEMANTIC_FAILURE = 'failure';

	private string $code;

	public function __construct(string $code)
	{
		if (!isset(self::getCodes()[$code]))
		{
			throw new InvalidArgumentException('Unexpected code - ' . $code);
		}

		$this->code = $code;
	}

	public static function sending(): self
	{
		return new self(self::CODE_SENDING);
	}

	public static function sent(): self
	{
		return new self(self::CODE_SENT);
	}

	public static function delivered(): self
	{
		return new self(self::CODE_DELIVERED);
	}

	public static function read(): self
	{
		return new self(self::CODE_READ);
	}

	public static function failed(): self
	{
		return new self(self::CODE_FAILED);
	}

	public function getName(): string
	{
		return self::getCodes()[$this->code]['NAME'];
	}

	public function getSemantic(): string
	{
		return self::getCodes()[$this->code]['SEMANTIC'];
	}

	private static function getCodes(): array
	{
		static $codes;

		if (empty($codes))
		{
			$codes = [
				self::CODE_SENDING => [
					'NAME' => Loc::getMessage('MESSAGE_STATUS_SENDING'),
					'SEMANTIC' => self::SEMANTIC_SUCCESS,
				],
				self::CODE_SENT => [
					'NAME' => Loc::getMessage('MESSAGE_STATUS_SENT'),
					'SEMANTIC' => self::SEMANTIC_SUCCESS,
				],
				self::CODE_DELIVERED => [
					'NAME' => Loc::getMessage('MESSAGE_STATUS_DELIVERED'),
					'SEMANTIC' => self::SEMANTIC_SUCCESS,
				],
				self::CODE_READ => [
					'NAME' => Loc::getMessage('MESSAGE_STATUS_READ'),
					'SEMANTIC' => self::SEMANTIC_SUCCESS,
				],
				self::CODE_FAILED => [
					'NAME' => Loc::getMessage('MESSAGE_STATUS_FAILED'),
					'SEMANTIC' => self::SEMANTIC_FAILURE,
				],
			];
		}

		return $codes;
	}
}
