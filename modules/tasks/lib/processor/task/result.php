<?
/**
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task;

use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Processor\Task\Result\Impact;

/**
 * Class Result
 * @package Bitrix\Tasks\Processor\Task
 */
final class Result extends \Bitrix\Tasks\Util\Result
{
	public function setData($data)
	{
		parent::setData(Collection::isA($data) ? $data : new Collection($data));
	}

	public function getImpactById($id)
	{
		return $this->data[$id];
	}

	/**
	 * Apply task changes
	 * @param mixed[] $conditions Optional filter by impact collection
	 *
	 * @return Result
	 */
	public function save($conditions = array())
	{
		$result = new Result();

		if(!$this->isSuccess())
		{
			$result->addError('INCORRECT_OPERATION', 'Incorrect operation');
		}
		else
		{
			/** @var Collection $data */
			$data = $this->getData();
			if($conditions)
			{
				$data = $data->find($conditions);
			}

			if(count($data))
			{
				Item\Task::enterBatchState();
				/** @var Impact $impact */
				foreach($data as $impact)
				{
					$result->adoptErrors(
						$impact->save()
					);
				}
				Item\Task::leaveBatchState();
			}
		}

		return $result;
	}

	public function exportData()
	{
		/** @var Impact[] $data */
		$data = $this->getData();

		$result = array();
		foreach($data as $impact)
		{
			$result[intval($impact->getId())] = $impact->exportUpdatedData();
		}

		return $result;
	}

	protected function dumpData()
	{
		$data = $this->getData();

		$out = '';
		/** @var Impact $impact */
		foreach($data as $impact)
		{
			$out .= $impact->dump().PHP_EOL;
		}

		return $out;
	}
}