<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Model\File;

/**
 * @mixin Configurable
 */
trait FileListPreparer
{
	public function prepareFiles(array $files): array
	{
		$result = [];
		foreach ($files as $file)
		{
			$result[] = new File(
				$file['ID'],
				(int)$file['FILE_ID'],
				trim((string)$file['NAME']),
				(int)$file['SIZE'],
				(string)($file['VIEW_URL'] ?? ''),
				isset($file['PREVIEW_URL']) ? (string)$file['PREVIEW_URL'] : null
			);
		}

		return $result;
	}
}
