<?php

namespace Bitrix\Sign\Controllers\V1\Document;

use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Operation\SaveBlankPreviewPage;
use Bitrix\Sign\Service;
use Bitrix\Sign\Item;
use Bitrix\Sign\Attribute;

use Bitrix\Main;
use Bitrix\Sign\Type\Access\AccessibleItemType;

class Pages extends \Bitrix\Sign\Engine\Controller
{
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_READ,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_READ,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
		)
	)]
	public function listAction(string $uid): array
	{
		$document = Service\Container::instance()->getDocumentRepository()->getByUid($uid);
		if (!$document)
		{
			$this->addError(new Main\Error('Document not found'));

			return [];
		}

		if ($document->blankId === null)
		{
			$this->addError(new Main\Error('Document item field `blankId` is empty'));

			return [];
		}

		$blank = Service\Container::instance()
			->getBlankRepository()
			->getById($document->blankId)
		;
		if ($blank === null)
		{
			$this->addError(new Main\Error('Blank with id = ' . $document->blankId . ' not found'));

			return [];
		}

		$listRequest = new Item\Api\Document\Page\ListRequest($uid);
		$apiPages = Service\Container::instance()->getApiDocumentPageService();
		$listResponse = $apiPages->getList($listRequest);
		if (!$listResponse->isSuccess())
		{
			$this->addErrors($listResponse->getErrors());
		}

		$pages = [];
		foreach ($listResponse->pages->toArray() AS $key => $page)
		{
			if ($key === 0)
			{
				(new SaveBlankPreviewPage(
					$blank,
					$page->url,
					Storage::instance()->getApiEndpoint()
				))->launch();
			}
			$pages[] = ['url' => $page->url];
		}

		return [
			'ready' => $listResponse->ready,
			'pages' => $pages
		];
	}
}
