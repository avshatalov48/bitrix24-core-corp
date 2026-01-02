<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\WaitListItem\WaitListItem;
use Bitrix\Booking\Entity\WaitListItem\WaitListItemCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\WaitListItemRepositoryInterface;
use Bitrix\Booking\Internals\Service\ClientService;
use Bitrix\Booking\Internals\Service\ExternalDataService;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Trait\ClientTrait;
use Bitrix\Booking\Provider\Trait\ExternalDataTrait;

class WaitListItemProvider
{
	use ClientTrait;
	use ExternalDataTrait;

	public function __construct(
		private WaitListItemRepositoryInterface|null $waitListItemRepository = null,
		private ClientService|null $clientService = null,
		private ExternalDataService|null $externalDataService = null,
	)
	{
		$this->waitListItemRepository = $waitListItemRepository ?? Container::getWaitListItemRepository();
		$this->clientService = $clientService ?? Container::getClientService();
		$this->externalDataService = $externalDataService ?? Container::getExternalDataService();
	}

	public function getList(GridParams $gridParams, int $userId): WaitListItemCollection
	{
		return $this->waitListItemRepository->getList(
			limit: $gridParams->limit,
			offset: $gridParams->offset,
			filter: $gridParams->getFilter(),
			sort: $gridParams->getSort(),
			select: $gridParams->getSelect(),
			userId: $userId,
		);
	}

	public function getById(int $id, int $userId, bool $withRelations = true): WaitListItem|null
	{
		$waitListItem = $this->waitListItemRepository->getById($id, $userId);

		if (!$waitListItem)
		{
			return null;
		}

		if ($withRelations)
		{
			$this->withExternalData(new WaitListItemCollection($waitListItem));
			$this->clientService->loadClientData($waitListItem->getClientCollection());
		}

		return $waitListItem;
	}

	protected function getExternalDataService(): ExternalDataService
	{
		return $this->externalDataService;
	}
}
