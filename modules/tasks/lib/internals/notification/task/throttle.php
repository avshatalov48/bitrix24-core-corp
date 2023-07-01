<?
/**
 * Class MessageThrottleTable
 *
 * @package Bitrix\Tasks
 *
 * This class "decorates" CTaskNotification::sendUpdateMessage() calls. Actions are grouped, so only the last notification
 * will have a complete set of changes for each user, and after that, may be sent. This is useful when user is
 * updating one or several tasks rapidly (for example, drags plan dates in the Gantt chart my mouse)
 *
 * todo: leave entity alone, move methods to somewhere like lib/util/notification/throttle
 **/

namespace Bitrix\Tasks\Internals\Notification\Task;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class ThrottleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Throttle_Query query()
 * @method static EO_Throttle_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Throttle_Result getById($id)
 * @method static EO_Throttle_Result getList(array $parameters = [])
 * @method static EO_Throttle_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection wakeUpCollection($rows)
 */
class ThrottleTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_msg_throttle';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'AUTHOR_ID' => array(
				'data_type' => 'integer',
			),
			'INFORM_AUTHOR' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateInformAuthor'),
			),
			'STATE_ORIG' => array(
				'data_type' => 'text',
			),
			'STATE_LAST' => array(
				'data_type' => 'text',
			),
		);
	}
	/**
	 * Returns validators for INFORM_AUTHOR field.
	 *
	 * @return array
	 */
	public static function validateInformAuthor()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}

	public static function submitUpdateMessage($taskId, $authorId, array $stateOrig, array $stateLast)
	{
		if(!intval($authorId))
		{
			throw new \Bitrix\Tasks\Exception('Incorrect author id');
		}

		$item = static::getByTaskId($taskId);
		if ($item['ID'] ?? null)
		{
			$last = unserialize($item['STATE_LAST'], ['allowed_classes' => false]);
			if(is_array($last) && is_array($stateLast))
			{
				$stateLast = array_merge($last, $stateLast);
			}

			$data = array(
				'STATE_LAST' => serialize($stateLast)
			);

			// if the next change was made by someone else, the origin author should know about that
			if($authorId != $item['AUTHOR_ID'])
			{
				$data['INFORM_AUTHOR'] = 1;
			}

			static::update($item['ID'], $data);
		}
		else
		{
			static::add(array(
				'TASK_ID' => $taskId,
				'AUTHOR_ID' => $authorId,
				'STATE_ORIG' => serialize($stateOrig),
				'STATE_LAST' => serialize($stateLast)
			));
		}
	}

	public static function getUpdateMessages(): array
	{
		$result = [];

		$res = static::getList(['select' => ['TASK_ID', 'AUTHOR_ID', 'STATE_ORIG', 'STATE_LAST', 'INFORM_AUTHOR']]);

		static::cleanUp();

		while ($item = $res->fetch())
		{
			$stateOrig = unserialize(
				$item['STATE_ORIG'],
				['allowed_classes' => ['DateTime', 'Bitrix\Tasks\Util\Type\DateTime']]
			);
			if (!is_array($stateOrig))
			{
				$stateOrig = [];
			}

			$stateLast = unserialize(
				$item['STATE_LAST'],
				['allowed_classes' => ['DateTime', 'Bitrix\Tasks\Util\Type\DateTime']]
			);
			if (!is_array($stateLast))
			{
				$stateLast = [];
			}

			$result[$item['TASK_ID']] = [
				'STATE_ORIG' => $stateOrig,
				'STATE_LAST' => $stateLast,
				'AUTHOR_ID' => $item['AUTHOR_ID'],
				'TASK_ID' => $item['TASK_ID'],
				'IGNORE_RECIPIENTS' => [
					// null to inform
					$item['AUTHOR_ID'] => ((int)$item['INFORM_AUTHOR'] ? null : true),
				],
			];
		}

		return $result;
	}

	public static function cleanUp()
	{
		global $DB;

		$DB->query("delete from ".static::getTableName());
	}

	private static function getByTaskId($taskId)
	{
		global $DB;

		$item = $DB->query("select ID, AUTHOR_ID, STATE_LAST from ".static::getTableName()." where TASK_ID = '".intval($taskId)."'")->fetch();
		return $item;
	}
}