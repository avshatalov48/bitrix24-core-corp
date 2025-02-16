<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\CounterRepositoryInterface;
use Bitrix\Main\Engine\CurrentUser;

class Counters extends BaseController
{
	private CounterRepositoryInterface $counterRepository;

	public function __construct()
	{
		parent::__construct();
		$this->counterRepository = Container::getCounterRepository();
	}

	public function getAction(): array
	{
		$userId = (int)CurrentUser::get()->getId();

		return $this->counterRepository->getList($userId);
	}
}
