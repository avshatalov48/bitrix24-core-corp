<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller;

use Bitrix\Booking\Provider\ClientTypeProvider;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;

class ClientType extends Controller
{
	private const ENTITY_ID = 'CLIENT_TYPE';
	private ClientTypeProvider $clientTypeProvider;

	public function init(): void
	{
		$this->clientTypeProvider = new ClientTypeProvider();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.ClientType.list
	 */
	public function listAction(): Page
	{
		$clientTypeCollection =
			$this
				->clientTypeProvider
				->getList(new GridParams())
		;

		return new Page(
			id: self::ENTITY_ID,
			items: $this->convertToRestFields($clientTypeCollection),
			totalCount: 0,
		);
	}
}
