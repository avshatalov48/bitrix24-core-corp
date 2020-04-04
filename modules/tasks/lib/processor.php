<?
/**
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks;

use Bitrix\Main\ArgumentException;

use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Processor\Task\Result\Impact;
use Bitrix\Tasks\Processor\Task\Result;

abstract class Processor
{
	protected $userId = 0;
	protected $affected = array();

	protected static $instances = array();

	/**
	 * @param int $userId
	 * @return static
	 * @throws ArgumentException
	 */
	public static function getInstance($userId = 0)
	{
		$userId = intval($userId);
		if(!$userId)
		{
			$userId = User::getId();
		}

		if(!$userId)
		{
			throw new ArgumentException('User ID not defined');
		}

		if(!array_key_exists($userId, static::$instances))
		{
			static::$instances[$userId] = new static($userId);
		}

		return static::$instances[$userId];
	}

	public function __construct($userId)
	{
		$this->userId = $userId;
		$this->reset();
	}

	public function getUserId()
	{
		return $this->userId;
	}

	public function isDebugEnabled()
	{
		return false;
	}

	/**
	 * Process affected item
	 *
	 * @param $id int task
	 * @param mixed[] changed fields
	 * @param mixed[] $settings
	 * @return \Bitrix\Tasks\Processor\Task\Result
	 */
	public function processEntity($id, $data = array(), array $settings = array())
	{
		$this->reset();
		$result = new Result();

		// do smth for $id

		$result->setData($this->affected);

		return $result;
	}

	protected function reset()
	{
	}

	/**
	 * @param Impact $impact
	 */
	public function addImpact($impact)
	{
		$taskId = $impact->getId();

		if(array_key_exists($taskId, $this->affected))
		{
			return;
		}

		$this->affected[$taskId] = $impact;
	}

	/**
	 * @param $taskId
	 * @return Impact
	 */
	public function getImpactById($taskId)
	{
		return $this->affected[$taskId];
	}

	public function hasImpact($taskId)
	{
		return array_key_exists($taskId, $this->affected);
	}

	/**
	 * Returns task data that were changed
	 */
	public function getChanges()
	{
		$result = array();
		/** @var Impact $impact */
		foreach($this->affected as $impact)
		{
			$result[$impact->getId()] = $impact->exportUpdatedData();
		}

		return $result;
	}
}