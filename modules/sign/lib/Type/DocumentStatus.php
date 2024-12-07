<?php

namespace Bitrix\Sign\Type;

use Bitrix\Sign\Item\Document;

final class DocumentStatus
{
	public const NEW = 'new';
	public const UPLOADED = 'uploaded';
	public const READY = 'ready';
	public const STOPPED = 'stopped';
	public const SIGNING = 'signing';
	public const DONE = 'done';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::NEW,
			self::UPLOADED,
			self::READY,
			self::STOPPED,
			self::SIGNING,
			self::DONE,
		];
	}

	/**
	 * @return array<self::*>
	 */
	public static function getEnding(): array
	{
		return [
			self::DONE,
			self::SIGNING,
			self::STOPPED,
		];
	}

	/**
	 * @return list<self::*>
	 */
	public static function getFinalStatuses(): array
	{
		return [
			self::DONE,
			self::STOPPED,
		];
	}

	public static function isFinalByDocument(Document $document): bool
	{
		return in_array($document->status, self::getFinalStatuses(), true);
	}
}
