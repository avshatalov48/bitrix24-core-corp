<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Template;

use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Psr\Container\NotFoundExceptionInterface;

class UpdateObserver implements UpdateObserverInterface
{
	use UpdatePermissionTrait;

	protected UpdateCommand $command;
	protected FlowEntity $flowEntityBeforeUpdate;

	/**
	 * @throws InvalidCommandException
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	public function update(UpdateCommand $command, FlowEntity $flowEntity, FlowEntity $flowEntityBeforeUpdate): void
	{
		$this->command = $command;
		$this->flowEntityBeforeUpdate = $flowEntityBeforeUpdate;

		if ($this->hasNoTemplate())
		{
			return;
		}

		if ($this->hasNoCreators())
		{
			return;
		}


		if ($this->isCreatorsChanged() || $this->isTemplateChanged())
		{
			$this->updatePermission($command);
		}
	}

	protected function hasNoTemplate(): bool
	{
		return $this->command->templateId <= 0;
	}

	protected function hasNoCreators(): bool
	{
		return !is_array($this->command->taskCreators);
	}


	protected function isCreatorsChanged(): bool
	{
		$creatorsBefore = $this->flowEntityBeforeUpdate->getMembers()?->getTaskCreators()->getAccessCodeList() ?? [];
		$creatorsAfter = $this->command->taskCreators;

		return
			!empty(array_diff($creatorsBefore, $creatorsAfter))
			|| !empty(array_diff($creatorsAfter, $creatorsBefore));
	}

	protected function isTemplateChanged(): bool
	{
		return $this->command->templateId !== $this->flowEntityBeforeUpdate->getTemplateId();
	}
}