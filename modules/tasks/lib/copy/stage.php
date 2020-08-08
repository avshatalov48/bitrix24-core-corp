<?php
namespace Bitrix\Tasks\Copy;

use Bitrix\Main\Copy\ContainerCollection;
use Bitrix\Main\Copy\Copyable;
use Bitrix\Main\Result;
use Bitrix\Tasks\Copy\Implement\Stage as Implementer;

class Stage implements Copyable
{
	private $implementer;
	private $implementerName;
	private $targetGroupId;

	private $result;

	public function __construct(Implementer $implementer, $targetGroupId = 0)
	{
		$this->implementer = $implementer;
		$this->implementerName = get_class($implementer);

		$this->targetGroupId = (int) $targetGroupId;

		$this->result = new Result();
	}

	/**
	 * Copies task stages.
	 *
	 * @param ContainerCollection $containerCollection
	 * @return Result
	 */
	public function copy(ContainerCollection $containerCollection)
	{
		$result = [$this->implementerName => []];

		foreach ($containerCollection as $container)
		{
			$taskId = $container->getEntityId();
			$copiedTaskId = $container->getCopiedEntityId();

			$stageIds = $this->implementer->getStageIds($taskId);
			$result[$this->implementerName] += $this->implementer->addStages($copiedTaskId, $stageIds);

			$this->implementer->updateTaskStageId($container, $taskId, $copiedTaskId);
		}

		$this->result->setData($result);

		return $this->result;
	}
}