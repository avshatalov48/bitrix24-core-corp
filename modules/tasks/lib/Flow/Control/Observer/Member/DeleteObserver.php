<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Member;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Tasks\Flow\Control\Observer\DeleteObserverInterface;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;

class DeleteObserver implements DeleteObserverInterface
{
	use FlowMemberTrait;
	/**
	 * @throws SqlQueryException
	 */
	public function update(FlowEntity $flowEntity): void
	{
		$this->cleanUp($flowEntity->getId());
	}
}