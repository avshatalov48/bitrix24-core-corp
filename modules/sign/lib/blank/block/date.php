<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blank\Section;

use function FormatDate;

class Date extends Dummy
{
	/**
	 * Returns block's manifest.
	 * @return array
	 */
	public static function getManifest(): array
	{
		return [
			'code' => 'date',
			'thirdParty' => false,
			'section' => Section::GENERAL,
			'title' => Loc::getMessage('SIGN_CORE_BLOCK_DATE_TITLE'),
			'hint' => Loc::getMessage('SIGN_CORE_BLOCK_DATE_HINT'),
		];
	}

	/**
	 * Returns block form's manifest.
	 * @return array
	 */
	public static function getDefaultData(): array
	{
		return [
			'text' => FormatDate('SHORT')
		];
	}
}
