<?php


namespace Bitrix\Crm\Service\Broker;


use Bitrix\Crm\Service\Broker;

class File extends Broker
{
	protected function loadEntry(int $id): ?array
	{
		$files = \CFile::GetList(
			[],
			[
				'ID' => $id,
			]
		);
		$file = $files->Fetch();

		return (is_array($file) ? $file : null);
	}

	/**
	 * @inheritDoc
	 */
	protected function loadEntries(array $ids): array
	{
		$files = \CFile::GetList(
			[],
			[
				'@ID' => $ids,
			]
		);
		$entries = [];
		while($file = $files->Fetch())
		{
			$entries[$file['ID']] = $file;
		}

		return $entries;
	}
}
