<?php

namespace Bitrix\Sign\Service\Api\Document;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service;
use Bitrix\Main;

class PageService
{
	private Service\ApiService $api;

	public function __construct(
		Service\ApiService $api,
	)
	{
		$this->api = $api;
	}

	public function getList(Item\Api\Document\Page\ListRequest $request): Item\Api\Document\Page\ListResponse
	{
		$result = new Main\Result();
		if ($request->documentUid === '')
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->get(
				"v1/document.page.list/$request->documentUid/",
			);
		}

		$data = $result->getData();

		/*  @todo validate data */
		$pages = new Item\Api\Property\Response\Page\List\PageCollection();
		foreach(($data['pages']) ?? [] as $page)
		{
			$pages->addItem(new Item\Api\Property\Response\Page\List\Page((string)($page['url'] ?? '')));
		}
		$response = new Item\Api\Document\Page\ListResponse(
			(bool) ($data['ready'] ?? false),
			$pages
		);

		$response->addErrors($result->getErrors());

		return $response;
	}
}