<?php

namespace Bitrix\Tasks\Flow\Control\Observer;

use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;

interface AddObserverInterface
{
	public function update(AddCommand $command, FlowEntity $flowEntity): void;
}