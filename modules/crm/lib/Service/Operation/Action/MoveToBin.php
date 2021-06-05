<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\Recycling\DynamicController;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

class MoveToBin extends Action
{
	protected
		$item;

	public function __construct(Item $item)
	{
		$this->item = $item;
		parent::__construct();
	}

	public function process(Item $item): Result
	{
		$result = new Result();

		DynamicController::getInstance($item->getEntityTypeId())->moveToBin(
			$item->getId(),
			['FIELDS' => $item->getCompatibleData()]
		);

		return $result;
	}
}