<?php

namespace Bitrix\Tasks\Flow\Control\Observer;

use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;

interface UpdateObserverInterface
{
	public function update(UpdateCommand $command, FlowEntity $flowEntity, FlowEntity $flowEntityBeforeUpdate): void;
}