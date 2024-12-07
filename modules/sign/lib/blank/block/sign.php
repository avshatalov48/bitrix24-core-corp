<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blank\Section;
use Bitrix\Sign\Document;

class Sign extends Dummy
{
	/**
	 * Returns block's manifest.
	 * @return array
	 */
	public static function getManifest(): array
	{
		return [
			'code' => 'sign',
			'thirdParty' => true,
			'section' => Section::PARTNER,
			'title' => Loc::getMessage('SIGN_CORE_BLOCK_SIGN_TITLE'),
			'hint' => Loc::getMessage('SIGN_CORE_BLOCK_SIGN_BLOCK_HINT'),
		];
	}

	/**
	 * Optionally transforms data before giving out.
	 * @param array $data Data to set.
	 * @param Document|null $document Document instance, if we're within document context.
	 * @return array
	 */
	public static function getData(array $data, ?Document $document): array
	{
		$member = $document ? $document->getMemberByPart($data['part']) : null;
		$data['data'] = [];

		if ($member)
		{
			$file = $member->getSignatureFile();
			if ($file)
			{
				$data['data'] = ['base64' => $file->getBase64Content()];
			}
		}

		return $data;
	}

	/**
	 * Returns true, if block is empty (no data).
	 * @param array $data Block's data.
	 * @return bool
	 */
	public static function isEmpty(array $data): bool
	{
		return ($data['data']['base64'] ?? '') === '';
	}
}
