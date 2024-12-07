<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blank\Section;
use Bitrix\Sign\Document;

class Stamp extends MyStamp
{
	/**
	 * Returns block's manifest.
	 * @return array
	 */
	public static function getManifest(): array
	{
		return [
			'code' => 'stamp',
			'thirdParty' => true,
			'section' => Section::PARTNER,
			'title' => Loc::getMessage('SIGN_CORE_BLOCK_STAMP_TITLE'),
			'hint' => Loc::getMessage('SIGN_CORE_BLOCK_STAMP_HINT'),
		];
	}
}
