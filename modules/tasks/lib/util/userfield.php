<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util;

use Bitrix\Tasks\Util;

abstract class UserField // todo: extends Dictionary, to iterate over user field scheme
{
	public static function getEntityCode()
	{
		return false;
	}

	/**
	 * Get system fields for this entity
	 */
	public static function getSysScheme()
	{
		return array();
	}

	/**
	 * Get user field map for this entity, with optional restoring missing fields
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param string $languageId
	 * @return mixed
	 */
	public static function getScheme($entityId = 0, $userId = 0, $languageId = LANGUAGE_ID)
	{
		$userId = intval($userId);
		if(!$userId)
		{
			$userId = User::getId();
		}

		$ufs = static::getUserFields($entityId, $userId, $languageId);

		// restore system fields for this entity
		if(User::isSuper($userId)) // only admin is allowed to create user fields
		{
			$reFetch = false;
			$scheme = static::getSysScheme();
			foreach($scheme as $field)
			{
				if(!array_key_exists($field['FIELD_NAME'], $ufs))
				{
					if(static::add(array_merge(array('ENTITY_ID' => static::getEntityCode()), $field)))
					{
						$reFetch = true;
					}
				}
			}

			if($reFetch)
			{
				$ufs = static::getUserFields($entityId, $userId, $languageId);
			}
		}

		/*
		// todo: restricted completely or restricted by the current project plan? you must now both ways!
		// determine what we can do here
		$canSortAndFilter = Util\UserField\Restriction::canManage(static::getEntityCode());

		foreach($ufs as $code => $desc)
		{
			$ufs[$code]['TASKS_RESTRICTION'] = array(
				'SORT' => $canSortAndFilter,     // whether we can sort by this field or not
				'FILTER' => $canSortAndFilter,   // whether we can filter by this field or not
			);
		}
		*/

		return $ufs;
	}

	public static function getDefaultValue($code, $userId = false)
	{
		$code = trim((string) $code);
		if($code == '')
		{
			return null;
		}

		$scheme = static::getScheme(0, $userId);
		if(!array_key_exists($code, $scheme))
		{
			return null;
		}

		$field = $scheme[$code];
		if(!array_key_exists('SETTINGS', $field) || !is_array($field['SETTINGS']) || !array_key_exists('DEFAULT_VALUE', $field['SETTINGS']))
		{
			return null;
		}

		$typeClass = \Bitrix\Tasks\Util\UserField\Type::getClass($field['USER_TYPE_ID']);

		$single = $typeClass::getDefaultValueSingle($field);
		if($single === null) // no default value is assumed in concept
		{
			return null;
		}

		if($field['MULTIPLE'] == 'Y')
		{
			return array($single);
		}
		else
		{
			return $single;
		}
	}

	public static function getTypes()
	{
		return $GLOBALS['USER_FIELD_MANAGER']->getUserType();
	}

	/**
	 * Checks if a userfield with $code exists for this entity
	 *
	 * @param $code
	 * @return bool
	 */
	public static function checkFieldExists($code)
	{
		$code = trim((string) $code);
		if($code == '')
		{
			return false;
		}

		$scheme = static::getScheme();

		return static::isUFKey($code) && array_key_exists($code, $scheme);
	}

	/**
	 * Checks if argument array contains userfield keys
	 *
	 * @param mixed $fields
	 * @return bool
	 */
	public static function checkContainsUFKeys($fields)
	{
		if(!Type::isIterable($fields))
		{
			return false;
		}

		foreach($fields as $fld => $value)
		{
			if(static::isUFKey($fld))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a given string looks like a user field name
	 *
	 * @param $key
	 * @return bool
	 */
	public static function isUFKey($key)
	{
		return substr(trim((string) $key), 0, 3) == 'UF_';
	}

	/**
	 * @param Collection|string|integer|array|null $value
	 * @return bool
	 */
	public static function isValueEmpty($value)
	{
		if(Collection::isA($value))
		{
			return $value->isEmpty();
		}

		if(is_array($value))
		{
			return count($value) == 0;
		}

		return (string) $value == '';
	}

	public static function isFieldExist($name)
	{
		return static::getField($name) !== null;
	}

	public static function getField($id)
	{
		$id = trim((string) $id);
		if($id == '')
		{
			return null;
		}

		$scheme = static::getScheme();

		if(static::isUFKey($id))
		{
			return $scheme[$id];
		}
		elseif(is_numeric($id))
		{
			foreach($scheme as $fData)
			{
				if($fData['ID'] == $id)
				{
					return $fData;
				}
			}
		}

		return null;
	}

	/**
	 * Solves problem of matching entity code to user field controller class
	 *
	 * @param $code
	 * @return string
	 */
	public static function getControllerClassByEntityCode($code)
	{
		static $map = array();

		if(!isset($map[$code]))
		{
			$code = trim((string) $code);
			$code = ToLower(preg_replace('#^TASKS_#', '', $code));
			if($code == '' || !preg_match('#^[a-z0-9_]+$#', $code))
			{
				return '';
			}

			$className = '\\'.__NAMESPACE__.'\\UserField\\'.implode('\\', explode('_', $code));

			if(!class_exists($className))
			{
				return '';
			}

			$map[$code] = $className;
		}

		return $map[$code];
	}

	/**
	 * @param $data
	 * @param \Bitrix\Tasks\Util\UserField $dstUFController
	 * @param int $userId
	 * @param array $parameters
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function cloneValues($data, $dstUFController, $userId = 0, array $parameters = array() /*todo: configure object later */)
	{
		$result = new Result();

		$scheme = static::getScheme();
		$toScheme = $dstUFController->getScheme();

		$newData = $data;
		$values = array();

		// try to map field values...
		foreach($scheme as $field => $desc)
		{
			if(
				array_key_exists($field, $toScheme) && // field exists in the destination entity
				!empty($scheme[$field]['USER_TYPE_ID']) &&
				$toScheme[$field]['USER_TYPE_ID'] == $scheme[$field]['USER_TYPE_ID'] && // types are equal
				!static::isValueEmpty($data[$field]) // the source field is not empty
			)
			{
				$skip = Filter::isA($parameters['FILTER']) && !$parameters['FILTER']->match($desc);

				$typeClass = \Bitrix\Tasks\Util\UserField\Type::getClass($scheme[$field]['USER_TYPE_ID']);

				// even if $skip == true here, we must call cloneValue(), because it may affect other entity fields
				$valueClone = $typeClass::cloneValue(
					$data[$field],
					$newData,
					$scheme[$field],
					$toScheme[$field],
					$userId,
					array('SKIP' => $skip)
				);

				if($skip)
				{
					unset($newData[$field]);
				}
				else
				{
					if(!static::isValueEmpty($valueClone))
					{
						$newData[$field] = $valueClone;
						$values[$field] = $valueClone;
					}
				}
			}
		}

		$result->setData($newData);

		return $result;
	}

	public function cancelCloneValues($data, $userId = 0)
	{
		$result = new Result();

		$scheme = static::getScheme();

		foreach($scheme as $field => $desc)
		{
			if(
				!empty($scheme[$field]['USER_TYPE_ID'])
				&&
				array_key_exists($field, $data)
			)
			{
				$typeClass = \Bitrix\Tasks\Util\UserField\Type::getClass($scheme[$field]['USER_TYPE_ID']);

				$typeClass::cancelCloneValue($data[$field], $scheme[$field], array(), $userId);
			}
		}

		return $result;
	}

	/**
	 * Check values of the specified user fields for the specified entity
	 *
	 * @param int $entityId
	 * @param mixed $data
	 * @param int $userId
	 * @return Result
	 */
	public function checkValues($data, $entityId = 0, $userId = 0)
	{
		$result = new Result();

		if(!empty($data) && static::checkContainsUFKeys($data))
		{
			global $USER_FIELD_MANAGER;

			if(!$USER_FIELD_MANAGER->checkFields(static::getEntityCode(), intval($entityId), $data, $userId ? $userId : false))
			{
				global $APPLICATION;

				$e = $APPLICATION->getException();
				foreach($e->messages as $msg)
				{
					$msgText = $msg;
					$fieldId = '';
					if(is_array($msg))
					{
						$msgText = $msg['text'];
						$fieldId = $msg['id'];
					}

					$result->getErrors()->add('USER_FIELD', $msgText, Error::TYPE_FATAL, array('FIELD_ID' => $fieldId));
				}
			}
		}

		return $result;
	}

	/**
	 * Update values of the specified user fields for the specified entity
	 *
	 * @param int $entityId
	 * @param array $data
	 * @param int $userId
	 * @return Result
	 */
	public function updateValues($data, $entityId = 0, $userId = 0)
	{
		$result = new Result();

		if(!empty($data) && static::checkContainsUFKeys($data))
		{
			global $USER_FIELD_MANAGER;
			$USER_FIELD_MANAGER->update(static::getEntityCode(), intval($entityId), $data, $userId ? $userId : false);
		}

		return $result;
	}

	public function addField(array $fields)
	{
		$result = new Result();

		if(!array_key_exists('FIELD_NAME', $fields))
		{
			// have to auto-assign field name
			$name = static::getFreeFieldName();

			if(!$name)
			{
				$result->getErrors()->add('ACTION_FAILED', 'Unable to generate field name');
			}

			$fields['FIELD_NAME'] = $name;
		}

		if($result->isSuccess())
		{
			$id = static::add(array_merge($fields, array('ENTITY_ID' => static::getEntityCode())));

			if(!$id)
			{
				static::getApplicationErrors($result);
			}
			else
			{
				$result->setData($id);
			}
		}

		return $result;
	}

	public function updateField($id, array $fields)
	{
		$result = new Result();

		$updateResult = static::update($id, $fields);
		if(!$updateResult)
		{
			static::getApplicationErrors($result);
		}

		return $result;
	}

	public function deleteField($id)
	{
		$result = new Result();

		$entity = new \CUserTypeEntity();
		$deleteResult = $entity->delete($id);
		if(!$deleteResult)
		{
			static::getApplicationErrors($result);
		}

		return $result;
	}

	public static function clearCache()
	{
		global $CACHE_MANAGER;

		$CACHE_MANAGER->clearByTag(static::getCacheTagName());
	}

	public static function onAfterUserTypeAdd(array $field)
	{
		static::onUserTypeSchemeChange($field, 0);
	}

	public static function OnAfterUserTypeUpdate(array $field, $id)
	{
		static::onUserTypeSchemeChange($field, $id);
	}

	public static function OnAfterUserTypeDelete(array $field, $id)
	{
		static::onUserTypeSchemeChange($field, $id);
	}

	public static function getFreeFieldName()
	{
		$scheme = static::getScheme();

		for($i = 0; $i < 10; $i++)
		{
			$name = 'UF_AUTO_'.rand(100, 999).rand(100, 999).rand(100, 999).rand(100, 999);
			if(array_key_exists($name, $scheme))
			{
				continue;
			}
			else
			{
				return $name;
			}
//			$item = \Bitrix\Main\UserFieldTable::getList(array('limit' => 1, 'filter' => array(
//				'=FIELD_NAME' => $name,
//				'=ENTITY_ID' => static::getEntityCode()
//			), 'select' => array('ID')))->fetch();
//			if(!intval($item['ID']))
//			{
//				return $name;
//			}
		}

		return false;
	}

	private static function onUserTypeSchemeChange(array $field, $id)
	{
		$entityCode = false;

		if(array_key_exists('ENTITY_ID', $field))
		{
			$entityCode = $field['ENTITY_ID'];
		}
		else
		{
			$item = \CUserTypeEntity::GetByID($id);
			if($item)
			{
				$entityCode = $item['ENTITY_ID'];
			}
		}

		if($entityCode && strpos($entityCode, 'TASKS_') == 0)
		{
			$className = static::getControllerClassByEntityCode($entityCode);
			if($className)
			{
				$className::clearCache();
			}
		}
	}

	private static function add($fields)
	{
		$entity = new \CUserTypeEntity();
		return $entity->add($fields);
	}

	private static function update($id, $fields)
	{
		if(array_key_exists('EDIT_FORM_LABEL', $fields))
		{
			$uf = \CAllUserTypeEntity::GetByID($id);
			if($uf)
			{
				$origLabel = is_array($uf['EDIT_FORM_LABEL']) ? $uf['EDIT_FORM_LABEL'] : array();
				foreach($origLabel as $lid => $text)
				{
					if(!isset($fields['EDIT_FORM_LABEL'][$lid]))
					{
						$fields['EDIT_FORM_LABEL'][$lid] = $text;
					}
				}
			}
		}

		$entity = new \CUserTypeEntity();
		return $entity->update($id, $fields);
	}

	private static function getUserFields($entityId = 0, $userId = 0, $languageId = false)
	{
		return $GLOBALS['USER_FIELD_MANAGER']->getUserFields(static::getEntityCode(), $entityId, $languageId, $userId ? $userId : false);
	}

	private static function getApplicationErrors(Result $result)
	{
		$e = $GLOBALS['APPLICATION']->getException();

		if($e)
		{
			if ($e instanceof \CAdminException)
			{
				if (is_array($e->messages))
				{
					foreach($e->messages as $msg)
					{
						$result->getErrors()->add('ACTION_FAILED', $msg['text'], false, array('ID' => $msg['id']));
					}
				}
			}
			else
			{
				$result->getErrors()->add('ACTION_FAILED', $e->getString(), false, array('EXCEPTION' => $e));
			}
		}
	}

	private static function getCacheTagName()
	{
		return "tasks_uf_".ToLower(static::getEntityCode());
	}

	public static function getClass()
	{
		return get_called_class();
	}
}