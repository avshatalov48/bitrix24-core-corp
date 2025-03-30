<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Ui\EntitySelector;

use Bitrix\Booking\Access\ResourceAction;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Provider;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class ResourceProvider extends BaseProvider
{
	private int $userId;
	private Provider\ResourceProvider $resourceProvider;
	private BaseAccessController $resourceAccessController;

	private int $maxCount = 20;

	public static function getRecentIds(): array
	{
		if (!Loader::includeModule('ui'))
		{
			return [];
		}

		$dialog = new Dialog([
			'entities' => [
				[
					'id' => EntityId::Resource->value,
					'dynamicLoad' => true,
				],
			],
		]);

		$dialog->load();

		return array_map(
			static fn($item) => $item->getId(),
			$dialog->getRecentItems()->getAll(),
		);
	}

	public function __construct()
	{
		parent::__construct();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->resourceProvider = new Provider\ResourceProvider();
		$this->resourceAccessController = Container::getResourceAccessController();
	}

	public function isAvailable(): bool
	{
		return $this->canRead();
	}

	public function getItems(array $ids): array
	{
		if (!$this->canRead())
		{
			return [];
		}

		$resources = $this->resourceProvider->getList(
			gridParams: new Provider\Params\GridParams(
				filter: new Provider\Params\Resource\ResourceFilter([
					'ID' => $ids,
					'MODULE_ID' => 'booking',
				]),
			),
			userId: $this->userId,
		);

		return $this->prepareDialogItems($resources);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$items = $this->getResourceItems($searchQuery);

		$dialog->addItems($items);
	}

	public function fillDialog(Dialog $dialog): void
	{
		$dialog->addRecentItems($this->getResourceItems());
	}

	private function getResourceItems(?SearchQuery $searchQuery = null): array
	{
		$filter = [
			'MODULE_ID' => 'booking',
		];

		if ($searchQuery)
		{
			$filter['SEARCH_QUERY'] = $searchQuery->getQuery();
		}

		$resources = $this->resourceProvider->getList(
			gridParams: new Provider\Params\GridParams(
				limit: $this->maxCount,
				filter: new Provider\Params\Resource\ResourceFilter($filter),
			),
			userId: $this->userId,
		);

		return $this->prepareDialogItems($resources);
	}

	private function prepareDialogItems(ResourceCollection $resources): array
	{
		return array_map(
			static function (Resource $resource)
			{
				return new Item([
					'id' => $resource->getId(),
					'entityId' => EntityId::Resource->value,
					'title' => $resource->getName(),
					'subtitle' => $resource->getType()->getName(),
				]);
			},
			$resources->getCollectionItems(),
		);
	}

	private function canRead(): bool
	{
		return $this->resourceAccessController::can(
			userId: $this->userId,
			action: ResourceAction::Read,
		);
	}
}
