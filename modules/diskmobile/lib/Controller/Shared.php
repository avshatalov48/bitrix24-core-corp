<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Disk\BaseObject;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Response\DataType\Page;

class Shared extends BaseFileList
{
	public function configureActions(): array
	{
		return [
			'getChildren' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/**
	 * @see \Bitrix\Disk\Controller\CommonStorage::getChildrenAction()
	 * @restMethod diskmobile.Shared.get
	 */
	public function getChildrenAction(string $search = null, array $extra = []): ?array
	{
		/** @var array|null $page */
		$page = $this->forward(
			\Bitrix\Disk\Controller\CommonStorage::class,
			'getChildren',
		);

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		$response = [
			'items' => [],
			'users' => [],
			'storages' => [],
		];

		if (count($page->getItems()) > 0)
		{
			$response['items'] = array_map(fn(array $item): array => $item, $page->getItems());
			$response = $this->withUsers($response);
			$response = $this->withRealStorageIds($response);
			$response = $this->withStorages($response);
		}

		return $response;
	}
}
