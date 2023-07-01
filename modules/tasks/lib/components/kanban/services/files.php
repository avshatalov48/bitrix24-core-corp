<?php

namespace Bitrix\Tasks\Components\Kanban\Services;

use Bitrix\Main\Loader;
use \Bitrix\Tasks\Integration\Disk\Connector\Task as ConnectorTask;

class Files
{
	private array $previewSize = [
		'width' => 1000,
		'height' => 1000,
	];

	public function __construct(array $previewSize = [])
	{
		if (isset($previewSize['width'], $previewSize['height']))
		{
			$this->previewSize = $previewSize;
		}
	}

	/**
	 * Fill data-array with task files.
	 * @param array $items Task items.
	 * @return array
	 */
	public function getFiles(array $items): array
	{
		if (empty($items))
		{
			return $items;
		}

		if (Loader::includeModule('disk'))
		{
			// get counts
			$cnt = ConnectorTask::getFilesCount(array_keys($items));
			foreach ($cnt as $taskId => $c)
			{
				$items[$taskId]['data']['count_files'] = $c;
			}
			// get covers
			$covers = ConnectorTask::getCover(
				array_keys($items),
				$this->previewSize['width'],
				$this->previewSize['height']
			);
			foreach ($covers as $taskId => $cover)
			{
				$items[$taskId]['data']['background'] = $cover;
			}
		}

		return $items;
	}
}