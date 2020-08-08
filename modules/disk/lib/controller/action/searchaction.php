<?php

namespace Bitrix\Disk\Controller\Action;

use Bitrix\Disk;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Search\Reindex;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main\Search;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Uri;

class SearchAction extends Search\SearchAction
{
	protected function listIdsBySearch($searchQuery)
	{
		$filter = [
			'=DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			'STORAGE.USE_INTERNAL_RIGHTS' => true,
			'=STORAGE.MODULE_ID' => Driver::INTERNAL_MODULE_ID,
			'@STORAGE.ENTITY_TYPE' => [
				Disk\ProxyType\User::className(),
				Disk\ProxyType\Group::className(),
				Disk\ProxyType\Common::className(),
			],
		];

		$fulltextContent = Disk\Search\FullTextBuilder::create()
			->addText($searchQuery)
			->getSearchValue()
		;

		if (!Search\Content::canUseFulltextSearch($fulltextContent))
		{
			return [];
		}

		if (Reindex\HeadIndex::isReady())
		{
			$filter["*HEAD_INDEX.SEARCH_INDEX"] = $fulltextContent;
		}
		elseif (Reindex\BaseObjectIndex::isReady())
		{
			$filter["*SEARCH_INDEX"] = $fulltextContent;
		}
		else
		{
			return [];
		}

		$securityContext = new Disk\Security\DiskSecurityContext($this->getCurrentUser()->getId());
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck(
			$securityContext,
			[
				'select' => ['ID'],
				'filter' => $filter,
				'limit' => 30,
				'order' => [
					'UPDATE_TIME' => 'DESC',
				],
			],
			['ID', 'CREATED_BY']
		);

		$objectIds = [];
		foreach (ObjectTable::getList($parameters) as $row)
		{
			$objectIds[] = $row['ID'];
		}

		return $objectIds;
	}

	public function provideData($searchQuery, array $options = null, PageNavigation $pageNavigation = null)
	{
		$objectIds = $this->listIdsBySearch($searchQuery);

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