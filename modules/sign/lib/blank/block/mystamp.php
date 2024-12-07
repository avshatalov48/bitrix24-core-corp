<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blank\Section;
use Bitrix\Sign\Document;

class MyStamp extends Dummy
{
	/**
	 * Returns block's manifest.
	 * @return array
	 */
	public static function getManifest(): array
	{
		return [
			'code' => 'mystamp',
			'section' => Section::INITIATOR,
			'title' => Loc::getMessage('SIGN_CORE_BLOCK_MYSTAMP_TITLE'),
			'hint' => Loc::getMessage('SIGN_CORE_BLOCK_MYSTAMP_HINT'),
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

		if ($document && $member)
		{
			$file = $member->getStampFile();
			if ($file)
			{
				$data['data'] = ['fileId' => $file->getId()];
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
		return ($data['data']['fileId'] ?? '') === '';
	}
	
	/**
	 * @param array $data
	 * @param Document|null $document
	 * @return array
	 */
	public static function getViewData(array $data, ?Document $document): array
	{
		$member = $document ? $document->getMemberByPart($data['part']) : null;
		$data['data'] = [];
		
		if ($document && $member)
		{
			$file = $member->getStampFile();
			if ($file)
			{
				$data['data'] = ['base64' => $file->getBase64Content()];
			}
		}
		
		return $data;
	}
}
