<?
namespace Bitrix\Tasks\Copy;

use Bitrix\Main\Copy\Copyable;
use Bitrix\Main\Copy\ContainerCollection;
use Bitrix\Main\Result;
use Bitrix\Tasks\Copy\Implement\TaskCheckList;

class CheckList implements Copyable
{
	private $checkList;
	private $executiveUserId;

	/**
	 * @var Result
	 */
	private $result;

	public function __construct(TaskCheckList $checkList, $executiveUserId)
	{
		$this->checkList = $checkList;
		$this->executiveUserId = $executiveUserId;

		$this->result = new Result();
	}

	/**
	 * Copies checklists.
	 *
	 * @param ContainerCollection $containerCollection The object with data to copy.
	 * @return Result
	 */
	public function copy(ContainerCollection $containerCollection)
	{
		foreach ($containerCollection as $container)
		{
			$checkListItems = $this->checkList->getCheckListItemsByEntityId($container->getEntityId());

			if ($this->checkList->hasErrors())
			{
				$this->result->addErrors($this->checkList->getErrors());
			}
			else
			{
				$this->checkList->add($container->getCopiedEntityId(), $this->executiveUserId, $checkListItems);
			}
		}

		return $this->result;
	}
}