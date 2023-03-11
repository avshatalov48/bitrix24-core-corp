<?
/**
 * Class implements all further interactions with "rest" module considering userfields.
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Rest;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Tasks\Integration\Disk\Rest\Attachment;
use Bitrix\Rest\UserFieldProxy;
use Bitrix\Main\UserFieldTable;
use ReflectionClass;
use ReflectionException;
use TasksException;

abstract class UserField extends UserFieldProxy
{
	abstract public static function getTargetEntityId();

	public static function getClassName()
	{
		return 'userfield'; // also could be smth like "subentity.submethod"
	}

	public static function getAvailableMethods()
	{
		return array(
			'get',
			'getlist',
			'add',
			'update',
			'delete',
			'getfields',
			'gettypes'
		);
	}

	// a part of rest class-method router, for code-consistency purposes
	// see CTaskRestService::__callStatic()
	/**
	 * @throws ArgumentTypeException
	 * @throws TasksException
	 */
	public static function runRestMethod($executiveUserId, $methodName, array $args)
	{
		$instance = new static(static::getTargetEntityId(), new \CUser($executiveUserId));
		static::validateArgs($instance, $methodName, $args);
		$res = call_user_func_array([$instance, $methodName], $args);

		return [
			$res,
			[],
		];
	}

	/**
	 * Returns list of user fields in ORM way
	 *
	 * @param array $parameters A standard ORM getList() first argument
	 */
	public static function getFieldList(array $parameters = [])
	{
		if (!is_array($parameters))
		{
			$parameters = [];
		}

		$parameters['filter']['=ENTITY_ID'] = static::getTargetEntityId();
		$parameters['cache'] = ['ttl' => 3600];

		return UserFieldTable::getList($parameters);
	}

	public static function postProcessValues($values, $parameters = array())
	{
		if(!is_array($parameters))
			$parameters = array();

		if(!isset($parameters['FIELDS']))
		{
			$parameters['FIELDS'] = array();

			$res = static::getFieldList();
			while($item = $res->fetch())
			{
				$parameters['FIELDS'][$item['FIELD_NAME']] = $item;
			}
		}

		if(!isset($parameters['SERVER']) || !($parameters['SERVER'] instanceof \CRestServer))
		{
			throw new \Bitrix\Main\ArgumentException('Argument $parameters[SERVER] should be a valid CRestServer instance');
		}

		foreach($parameters['FIELDS'] as $fieldName => $fieldData)
		{
			if(isset($values[$fieldData['FIELD_NAME']]))
			{
				$value = $values[$fieldData['FIELD_NAME']];

				if((is_array($value) && empty($value)) || ((string) $value == ''))
				{
					continue;
				}

				if($fieldData['MULTIPLE'] == 'N')
				{
					$value = array($value);
				}

				// only disk files will be converted
				if($fieldData['USER_TYPE_ID'] == 'disk_file')
				{
					foreach($value as $i => $attachmentId)
					{
						$value[$i] = Attachment::getById($attachmentId, array('SERVER' => $parameters['SERVER']));
					}
				}

				$values[$fieldData['FIELD_NAME']] = $fieldData['MULTIPLE'] == 'N' ? $value[0] : $value;
			}
		}

		return $values;
	}

	protected function checkReadPermission()
	{
		return $this->isAuthorizedUser();
	}

	/**
	 * @throws TasksException
	 */
	private static function validateArgs(UserFieldProxy $class, string $method, array $args): void
	{
		try
		{
			$targetClass = new ReflectionClass($class);
			$targetMethod = $targetClass->getMethod($method);
		}
		catch (ReflectionException $e)
		{
			throw new TasksException($e->getMessage());
		}

		if (count($args) < $targetMethod->getNumberOfRequiredParameters())
		{
			throw new TasksException("Invalid arguments for {$targetClass->getName()}::{$method}");
		}
	}
}