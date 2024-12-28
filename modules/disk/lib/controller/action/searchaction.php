<?php

namespace Bitrix\Disk\Controller\Action;

use Bitrix\Disk;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Search\StorageFileFinder;
use Bitrix\Main\Search;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Uri;

class SearchAction extends Search\SearchAction
{
	public function provideData($searchQuery, array $options = null, PageNavigation $pageNavigation = null)
	{
		if (!$this->getCurrentUser())
		{
			return [];
		}

		$fileFinder = new StorageFileFinder($this->getCurrentUser()->getId());
		$objectIds = $fileFinder->findIdsByText($searchQuery);

		$result = [];
		if (!$objectIds)
		{
			return $result;
		}

		$parameters = ['filter' => ['@ID' => $objectIds]];
		foreach (Disk\BaseObject::getModelList($parameters) as $object)
		{
			$type = $object instanceof Disk\File? 'file' : 'folder';
			$resultItem = new Search\ResultItem($object->getName(), $this->buildUriForSearchPresentation($object), $object->getId());
			$resultItem->setType($type);

			if ($object instanceof Disk\File)
			{
				$resultItem->addLink('download', Driver::getInstance()->getUrlManager()->getUrlForDownloadFile($object));
			}

			$result[] = $resultItem;
		}

		return $result;
	}

	protected function buildUriForSearchPresentation(Disk\BaseObject $object)
	{
		$urlManager = Driver::getInstance()->getUrlManager();

		if ($object instanceof Disk\File)
		{
			return new Uri(
				$urlManager->getUrlFocusController(
					'openFileDetail',
					[
						'fileId' => $object->getId(),
						'inSidePanel' => 1,
					]
				)
			);
		}
		return new Uri(
			$urlManager->getUrlFocusController(
				'openFolderList',
				[
					'folderId' => $object->getId(),
				]
			)
		);
	}
}