<?php

namespace Bitrix\Booking\Internals\Integration\Ui\EntitySelector;

use Bitrix\Booking\Access\ResourceTypeAction;
use Bitrix\Booking\Entity\ResourceType\ResourceType;
use Bitrix\Booking\Entity\ResourceType\ResourceTypeCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Provider;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/booking/lib/Integration/Ui/EntitySelector/ResourceTypeProvider.php');

class ResourceTypeProvider extends BaseProvider
{
	private Provider\ResourceTypeProvider $resourceTypeProvider;
	private BaseAccessController $resourceTypeAccessController;

	private int $maxCount = 20;

	public function __construct()
	{
		parent::__construct();

		$this->resourceTypeProvider = new Provider\ResourceTypeProvider();
		$this->resourceTypeAccessController = Container::getResourceTypeAccessController();
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

		$resourceTypes = $this->resourceTypeProvider->getList(
			new Provider\Params\GridParams(
				filter: new Provider\Params\ResourceType\ResourceTypeFilter([
					'ID' => $ids,
					'MODULE_ID' => 'booking',
				]),
			),
			userId: (int)CurrentUser::get()->getId(),
		);

		return $this->prepareDialogItems($resourceTypes);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$items = $this->getResourceTypeItems($this->maxCount, $searchQuery);

		$dialog->addItems($items);
	}

	public function fillDialog(Dialog $dialog): void
	{
		$dialog->addRecentItems($this->getResourceTypeItems($this->maxCount));
	}

	private function getResourceTypeItems(int $limit, ?SearchQuery $searchQuery = null): array
	{
		$filter = [
			'MODULE_ID' => 'booking',
		];

		if ($searchQuery)
		{
			$filter['SEARCH_QUERY'] = $searchQuery->getQuery();
		}

		$resourceTypes = $this->resourceTypeProvider->getList(
			new Provider\Params\GridParams(
				limit: $limit,
				filter: new Provider\Params\ResourceType\ResourceTypeFilter($filter),
			),
			userId: (int)CurrentUser::get()->getId(),
		);

		return $this->prepareDialogItems($resourceTypes);
	}

	private function canRead(): bool
	{
		return $this->resourceTypeAccessController::can(
			userId: (int)CurrentUser::get()->getId(),
			action: ResourceTypeAction::Read,
		);
	}

	private function getNavigation(int $maxCount): PageNavigation
	{
		$navigation = new PageNavigation('booking-resource-type-provider');

		$navigation->setCurrentPage(1);
		$navigation->setPageSize($maxCount);

		return $navigation;
	}

	private function prepareDialogItems(ResourceTypeCollection $resourceTypes): array
	{
		return array_map(
			static function (ResourceType $resourceType)
			{
				return new Item([
					'id' => $resourceType->getId(),
					'entityId' => EntityId::ResourceType->value,
					'title' => $resourceType->getName(),
					'supertitle' => Loc::getMessage('BOOKING_RESOURCE_TYPE_PROVIDER_SUPER_TITLE'),
					'avatar' => '/bitrix/js/booking/images/entity-selector/resource-type.svg',
				]);
			},
			$resourceTypes->getCollectionItems()
		);
	}
}
