<?php

namespace Bitrix\Tasks\Flow\Control\Observer;

use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;

interface DeleteObserverInterface
{
	public function update(FlowEntity $flowEntity): void;
}