<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Search;

use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Search\FullTextSearch;

final class UpdateObserver implements UpdateObserverInterface
{
	public function update(UpdateCommand $command, FlowEntity $flowEntity, FlowEntity $flowEntityBeforeUpdate): void
	{
		$flow = new Flow($flowEntity->collectValues());

		(new FullTextSearch())->index($flow);
	}
}