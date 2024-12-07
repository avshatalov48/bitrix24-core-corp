<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Search;

use Bitrix\Tasks\Flow\Control\Observer\DeleteObserverInterface;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Search\FullTextSearch;

final class DeleteObserver implements DeleteObserverInterface
{
	public function update(FlowEntity $flowEntity): void
	{
		(new FullTextSearch())->removeIndex($flowEntity->getId());
	}
}