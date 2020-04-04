<?
/**
 * todo: Impact class is TEMPORAL, it should be replaced with (or at least inherited from) \Bitrix\Tasks\Item\Task when ready
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task\Result;

use Bitrix\Main\Localization\Loc;

use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Assert;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

abstract class Impact implements \ArrayAccess
{
	protected $data = array();
	protected $dataPristine = array();
	protected $head = false;
	protected $userId = 0;

	/**
	 * Impact constructor.
	 * @param int|mixed[] $data
	 * @param int $userId
	 */
	public function __construct($data, $userId = 0)
	{
		$this->setUserId($userId);

		$baseMixin = static::getBaseMixin();

		$allowed = array_merge(array(
			'ID',
		), $baseMixin['select']);

		if(is_array($data))
		{
			$data = array_intersect_key($data, array_flip($allowed));
		}
		elseif(is_numeric($data))
		{
			$data = intval($data);
			if($data)
			{
				$data = TaskTable::getList(array(
					'filter' => array('=ID' => $data),
					'select' => $allowed,
				))->fetch();
				if(!$data)
				{
					$data = array();
				}
			}
			else
			{
				$data = array();
			}
		}

		$this->data = $data;
	}

	public function getId()
	{
		return intval($this->data['ID']);
	}

	public function getFieldValueTitle()
	{
		return $this->data['TITLE'];
	}

	public function setAsHead()
	{
		return $this->head = true;
	}

	public function isHead()
	{
		return $this->head;
	}

	public function getDataPristine()
	{
		if (!count($this->dataPristine))
		{
			// todo
		}

		return $this->dataPristine;
	}

	/**
	 * @param array $data
	 */
	public function setDataUpdated(array $data)
	{
		$this->getDataPristine();

		// todo
	}

	public function setUserId($userId)
	{
		$this->userId = static::defineUserId($userId);
	}

	private static function defineUserId($userId)
	{
		if(!$userId)
		{
			$userId = \Bitrix\Tasks\Util\User::getId();
		}

		$userId = Assert::expectIntegerNonNegative($userId, '$userId', 'Was unable to identify effective user id');

		return $userId;
	}

	public function dump()
	{
		return '';
	}

	public function getUpdatedData()
	{
		return array();
	}

	public function exportUpdatedData()
	{
		$data = $this->getUpdatedData();
		$data['ID'] = $this->getId();

		return $data;
	}

	public function save()
	{
		$result = new Result();

		try
		{
			$prevUserId = User::getOccurAsId();
			User::setOccurAsId($this->userId);

			// todo: get rid of CTaskItem, use \Bitrix\Tasks\Item\Task when ready
			$t = new \CTaskItem($this->getId(), $this->userId);
			$t->update($this->getUpdatedData(), array(
				'THROTTLE_MESSAGES' => true
				// todo: disable workers here
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
				'RESPONSIBLE_ID',
				'CREATED_BY',
				'GROUP_ID',
			),
		);
	}

	public function offsetExists($offset)
	{
		return array_intersect_key($offset, $this->data);
	}

	public function offsetGet($offset)
	{
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		throw new NotImplementedException();
	}

	public function offsetUnset($offset)
	{
		throw new NotImplementedException();
	}
}