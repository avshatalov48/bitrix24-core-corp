<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blank\Section;

class Text extends Dummy
{
	/**
	 * Returns block's manifest.
	 * @return array
	 */
	public static function getManifest(): array
	{
		return [
			'code' => 'text',
			'thirdParty' => false,
			'section' => Section::GENERAL,
			'title' => Loc::getMessage('SIGN_CORE_BLOCK_TEXT_TITLE'),
			'hint' => Loc::getMessage('SIGN_CORE_BLOCK_TEXT_HINT'),
		];
	}

	/**
	 * Returns block form's manifest.
	 * @return array
	 */
	public static function getDefaultData(): array
	{
		return [
			'text' => ''
		];
	}

	/**
	 * Returns true, if block is empty (no data).
	 * @param array $data Block's data.
	 * @return bool
	 */
	public static function isEmpty(array $data): bool
	{
		return false;// text block allowed to be empty
	}
}
