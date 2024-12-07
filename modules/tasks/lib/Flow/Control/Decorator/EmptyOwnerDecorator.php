<?php

namespace Bitrix\Tasks\Flow\Control\Decorator;

use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Flow;

class EmptyOwnerDecorator extends AbstractFlowServiceDecorator
{
	public function add(AddCommand $command): Flow
	{
		if (!$command->hasValidOwnerId() && $command->hasValidCreatorId())
		{
			$command->ownerId = $command->creatorId;
		}

		return parent::add($command);
	}

	public function update(UpdateCommand $command): Flow
	{
		if (!$command->hasValidOwnerId() && $command->hasValidCreatorId())
		{
			$command->ownerId = $command->creatorId;
		}

		return parent::update($command);
	}
}