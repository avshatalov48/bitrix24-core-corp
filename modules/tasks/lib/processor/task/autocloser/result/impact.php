<?
/**
 * todo: Impact class is TEMPORAL, it should be replaced with (or at least inherited from) \Bitrix\Tasks\Item\Task when ready
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task\AutoCloser\Result;

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User;

final class Impact extends \Bitrix\Tasks\Processor\Task\Result\Impact
{
	/**
	 * @param array $data
	 */
	public function setDataUpdated(array $data)
	{
		$this->getDataPristine();

		if(array_key_exists('STATUS', $data))
		{
			$this->data['STATUS'] = $data['STATUS'];
		}

		//_print_r('close '.$this->getId().' '.$this->data['TITLE']);
	}

	public function getUpdatedData()
	{
		return array('STATUS' => $this->data['STATUS']);
	}

	public function getDataPristine()
	{
		if (!count($this->dataPristine))
		{
			$this->dataPristine = array(
				'STATUS' => $this->data['STATUS'],
			);
		}

		return $this->dataPristine;
	}

	public function save()
	{
		$result = new Result();

		// check if changed
		if($this->data['STATUS'] == $this->dataPristine['STATUS'] || $this->data['STATUS'] != 5)
		{
			return $result;
		}

		try
		{
			$prevUserId = User::getOccurAsId();
			User::setOccurAsId($this->userId);

			// todo: get rid of CTaskItem, use \Bitrix\Tasks\Item\Task when ready
			$t = new \CTaskItem($this->getId(), $this->userId);
			$t->update($this->getUpdatedData(), array(
				'THROTTLE_MESSAGES' => true,
				'AUTO_CLOSE' => false,
			));

			if($prevUserId)
			{
				User::setOccurAsId($prevUserId);
			}
		}
		catch(\TasksException $e)
		{
			$result->addException($e, Loc::getMessage('TASKS_WORKER_TASK_IMPACT_SAVE_ERROR'));
		}

		return $result;
	}

	public static function getBaseMixin()
	{
		return array(
			'select' => array(
				'STATUS',
				'AUTO_CLOSE',

				// task fields for php rights checking
				'RESPONSIBLE_ID',
				'CREATED_BY',
				'GROUP_ID',
				'STATUS',
			),
		);
	}
}