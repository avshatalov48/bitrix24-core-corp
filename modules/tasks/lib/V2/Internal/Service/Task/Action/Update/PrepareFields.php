<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareChangedBy;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareCreator;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareDatePlan;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareDates;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareDependencies;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareDescription;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareFlags;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareFlow;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareGroup;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareId;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareMark;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareMembers;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareOutlook;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareParents;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PreparePriority;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareStage;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareStatus;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareTags;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareTitle;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PreparePipeline;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ApplicationErrorTrait;

class PrepareFields
{
	use ConfigTrait;
	use ApplicationErrorTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		try
		{
			$fields = $this->prepareFields($fields, $fullTaskData);
		}
		catch (TaskFieldValidateException $e)
		{
			$message = $e->getMessage();

			$this->setApplicationError($message);

			throw new TaskUpdateException($e->getMessage());
		}

		return $fields;
	}

	private function prepareFields(array $fields, array $fullTaskData): array
	{
		$pipeline = new PreparePipeline($this->config, [
			PrepareFlow::class,
			PrepareGroup::class,
			PrepareStage::class,
			PrepareCreator::class,
			PrepareTitle::class,
			PrepareDescription::class,
			PrepareStatus::class,
			PreparePriority::class,
			PrepareMark::class,
			PrepareFlags::class,
			PrepareParents::class,
			PrepareMembers::class,
			PrepareDependencies::class,
			PrepareOutlook::class,
			PrepareTags::class,
			PrepareChangedBy::class,
			PrepareDates::class,
			PrepareId::class,
			PrepareDatePlan::class,
		]);

		return $pipeline($fields, $fullTaskData);
	}
}