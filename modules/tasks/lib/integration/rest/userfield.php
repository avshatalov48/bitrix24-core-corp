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

use Bitrix\Tasks\Integration\Disk\Rest\Attachment;

use Bitrix\Rest\UserFieldProxy;
use Bitrix\Main\EO_UserField_Result;
use Bitrix\Main\UserFieldTable;

abstract class UserField extends UserFieldProxy
{
	abstract static public function getTargetEntityId();

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
	public static function runRestMethod($executiveUserId, $methodName, array $args)
	{
		if(!is_array($args))
		{
		    $args = array();
		}

		$instance = new static(static::getTargetEntityId(), new \CUser($executiveUserId));
		$res = call_user_func_array(array($instance, $methodName), $args);

		return array(
			$res,
			array()
		);
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
}