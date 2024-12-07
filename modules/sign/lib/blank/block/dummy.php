<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sign\Blank\Block;
use Bitrix\Sign\Document;

Loc::loadMessages(__FILE__);

abstract class Dummy
{
	/**
	 * Returns block's manifest.
	 * @return array
	 */
	abstract public static function getManifest(): array;

	/**
	 * Returns block form's manifest.
	 * @return array
	 */
	public static function getDefaultData(): array
	{
		return [""];
	}

	/**
	 * Optionally transforms data before giving out.
	 * @param array $data Data to set.
	 * @param Document|null $document Document instance, iw we're within document context.
	 * @return array
	 */
	public static function getData(array $data, ?Document $document): array
	{
		return $data;
	}

	/**
	 * Returns true, if block is empty (no data).
	 * @param array $data Block's data.
	 * @return bool
	 */
	public static function isEmpty(array $data): bool
	{
		return
			($data['data']['text'] ?? '') === '' &&
			($data['data']['src'] ?? '') === '';
	}

	/**
	 * Calls when block success added or updated on blank.
	 * @param Block $block Block instance.
	 * @return void
	 */
	public static function wasUpdatedOnBlank(Block $block): void
	{
	}

	/**
	 * Must return false, if block data is not correct for saving.
	 * @param Block $block Block instance.
	 * @return bool
	 */
	public static function checkBeforeSave(Block $block): Result
	{
		return new Result();
	}
	
	/**
	 * @param array $data
	 * @param Document|null $document
	 * @return array
	 */
	public static function getViewData(array $data, ?Document $document): array
	{
		return static::getData($data, $document);
	}
}
