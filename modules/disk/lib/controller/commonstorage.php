<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Controller\DataProviders\ChildrenDataProvider;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Engine\Response;

final class CommonStorage extends Engine\Controller
{
	public function getChildrenAction(
		CurrentUser $currentUser,
		string $search = null,
		string $searchScope = 'currentFolder',
		bool $showRights = false,
		array $context = [],
		array $order = [],
		PageNavigation $pageNavigation = null
	):  ?Response\DataType\Page
	{
		$siteId = Application::getInstance()->getContext()->getSite();
		$commonStorageId = 'shared_files_' . $siteId;
		$storage = Disk\Driver::getInstance()->getStorageByCommonId($commonStorageId);

		if (!$storage)
		{
			$this->addError(new Error("Could not find common storage. Site: {$siteId}"));

			return null;
		}

		$childrenDataProvider = new ChildrenDataProvider();
		$result = $childrenDataProvider->getChildren(
			$storage->getRootObject(), $currentUser, $search, $searchScope, $showRights, $context, $order, $pageNavigation
		);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$data = $result->getData();

		return new Response\DataType\Page('children', $data['children'], $data['total']);
	}
}