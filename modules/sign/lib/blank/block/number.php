<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blank\Section;
use Bitrix\Sign\Document;

class Number extends Dummy
{
	/**
	 * Returns block's manifest.
	 * @return array
	 */
	public static function getManifest(): array
	{
		return [
			'code' => 'number',
			'thirdParty' => false,
			'section' => Section::GENERAL,
			'title' => Loc::getMessage('SIGN_CORE_BLOCK_NUMBER_TITLE'),
			'hint' => Loc::getMessage('SIGN_CORE_BLOCK_NUMBER_HINT'),
		];
	}

	/**
	 * Returns block form's manifest.
	 * @return array
	 */
	public static function getDefaultData(): array
	{
		return [
		];
	}

	/**
	 * Optionally transforms data before giving out.
	 * @param array $data Data to set.
	 * @param Document|null $document Document instance, iw we're within document context.
	 * @return array
	 */
	public static function getData(array $data, ?Document $document): array
	{
		if ($document)
		{
			$data['data'] = ['text' => $document->getEntityNumber()];
		}

		return $data;
	}
}
