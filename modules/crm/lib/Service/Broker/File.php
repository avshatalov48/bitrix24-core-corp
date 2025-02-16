<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Service\Broker;
use CFile;

/**
 * @method array|null getById(int $id)
 * @method array[] getBunchByIds(array $ids)
 */
class File extends Broker
{
	private bool $isRequiredSrc = false;

	public function setRequiredSrc(bool $value): self
	{
		$this->isRequiredSrc = $value;

		return $this;
	}

	protected function loadEntry(int $id): ?array
	{
		$files = CFile::GetList(
			[],
			[
				'ID' => $id,
			]
		);
		$file = $files->Fetch();

		if ($this->isNeedAddSrcToFile($file))
		{
			$file['SRC'] = CFile::GetFileSRC($file);

			return $file;
		}

		if (is_array($file))
		{
			return $file;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	protected function loadEntries(array $ids): array
	{
		$files = CFile::GetList(
			[],
			[
				'@ID' => $ids,
			]
		);

		$entries = [];
		while($file = $files->Fetch())
		{
			$entries[$file['ID']] = $file;

			if ($this->isNeedAddSrcToFile($file))
			{
				$entries[$file['ID']]['SRC'] = CFile::GetFileSRC($file);
			}
		}

		return $entries;
	}

	private function isNeedAddSrcToFile(mixed $file): bool
	{
		return ($this->isRequiredSrc && is_array($file) && empty($file['SRC']));
	}
}
