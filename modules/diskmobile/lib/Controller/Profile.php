<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Disk\Controller\DataProviders\ChildrenDataProvider;
use Bitrix\Disk;
use Bitrix\Disk\Controller\Types;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\UI\PageNavigation;

class Profile extends Folder
{
	protected function getChildren(
		int $id,
		array $order = [],
		bool $showRights = false,
		array $context = [],
		string $search = null,
		?array $searchContext = null,
		PageNavigation $pageNavigation = null,
	): ?Response\DataType\Page
	{
		$folder = Disk\Folder::loadById($id);
		$currentUser = $this->getCurrentUser();
		$childrenDataProvider = new ChildrenDataProvider();
		$searchScope = (string)$search !== '' ? 'subfolders' : 'currentFolder';
		$result = $childrenDataProvider->getChildren(
			$folder,
			$currentUser,
			$search,
			$searchScope,
			$showRights,
			$context,
			$order,
			$pageNavigation
		);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$data = $result->getData();

		return new Response\DataType\Page('children', $data['children'], $data['total']);
	}

	protected function getRights(int $id): ?array
	{
		$folder = Disk\Folder::loadById($id);
		$currentUser = $this->getCurrentUser();
		$securityContext = $folder->getStorage()?->getSecurityContext($currentUser);

		return [
			'canAdd' => $securityContext->canAdd($id),
		];
	}
}
