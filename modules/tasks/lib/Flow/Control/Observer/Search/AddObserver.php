<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Search;

use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Observer\AddObserverInterface;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Search\FullTextSearch;

final class AddObserver implements AddObserverInterface
{
	public function update(AddCommand $command, FlowEntity $flowEntity): void
	{
		$flow = new Flow($flowEntity->collectValues());

		(new FullTextSearch())->index($flow);
	}
}