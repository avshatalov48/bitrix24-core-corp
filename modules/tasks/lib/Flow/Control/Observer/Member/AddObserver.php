<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Member;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotAddedException;
use Bitrix\Tasks\Flow\Control\Mapper\CreatorsCommandMapper;
use Bitrix\Tasks\Flow\Control\Observer\AddObserverInterface;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;

class AddObserver implements AddObserverInterface
{
	use FlowMemberTrait;

	protected CreatorsCommandMapper $mapper;
	protected AddCommand $command;
	protected FlowEntity $flowEntity;

	public function __construct()
	{
		$this->mapper = new CreatorsCommandMapper();
	}

	/**
	 * @throws SqlQueryException
	 * @throws FlowNotAddedException
	 */
	public function update(AddCommand $command, FlowEntity $flowEntity): void
	{
		$this->command = $command;
		$this->flowEntity = $flowEntity;

		$this->cleanUp($flowEntity->getId());

		$members = $this->getMembers()->setFlowId($flowEntity->getId());

		if ($members->isEmpty())
		{
			throw new FlowNotAddedException('Empty flow members list');
		}

		$members
			->makeUnique()
			->insertIgnore();
	}
}