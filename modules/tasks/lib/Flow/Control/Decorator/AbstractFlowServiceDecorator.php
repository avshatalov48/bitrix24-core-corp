<?php

namespace Bitrix\Tasks\Flow\Control\Decorator;

use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\DeleteCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Flow;

abstract class AbstractFlowServiceDecorator extends FlowService
{
	public function __construct(
		protected FlowService $source
	)
	{
		$this->init();
	}

	public function add(AddCommand $command): Flow
	{
		return $this->source->add($command);
	}

	public function update(UpdateCommand $command): Flow
	{
		return $this->source->update($command);
	}

	public function delete(DeleteCommand $command): bool
	{
		return $this->source->delete($command);
	}

	protected function init(): void
	{

	}
}