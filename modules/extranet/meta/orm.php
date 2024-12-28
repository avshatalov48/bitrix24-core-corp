<?php

/* ORMENTITYANNOTATION:Bitrix\Extranet\Model\ExtranetUserTable:extranet\lib\Model\ExtranetUserTable.php */
namespace Bitrix\Extranet\Model {
	/**
	 * EO_ExtranetUser
	 * @see \Bitrix\Extranet\Model\ExtranetUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser resetUserId()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser unsetUserId()
	 * @method \int fillUserId()
	 * @method \boolean getChargeable()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser setChargeable(\boolean|\Bitrix\Main\DB\SqlExpression $chargeable)
	 * @method bool hasChargeable()
	 * @method bool isChargeableFilled()
	 * @method bool isChargeableChanged()
	 * @method \boolean remindActualChargeable()
	 * @method \boolean requireChargeable()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser resetChargeable()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser unsetChargeable()
	 * @method \boolean fillChargeable()
	 * @method \string getRole()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser setRole(\string|\Bitrix\Main\DB\SqlExpression $role)
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \string remindActualRole()
	 * @method \string requireRole()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser resetRole()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser unsetRole()
	 * @method \string fillRole()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser resetUser()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser set($fieldName, $value)
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser reset($fieldName)
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Extranet\Model\EO_ExtranetUser wakeUp($data)
	 */
	class EO_ExtranetUser {
		/* @var \Bitrix\Extranet\Model\ExtranetUserTable */
		static public $dataClass = '\Bitrix\Extranet\Model\ExtranetUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Extranet\Model {
	/**
	 * EO_ExtranetUser_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \boolean[] getChargeableList()
	 * @method \boolean[] fillChargeable()
	 * @method \string[] getRoleList()
	 * @method \string[] fillRole()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Extranet\Model\EO_ExtranetUser $object)
	 * @method bool has(\Bitrix\Extranet\Model\EO_ExtranetUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser getByPrimary($primary)
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser[] getAll()
	 * @method bool remove(\Bitrix\Extranet\Model\EO_ExtranetUser $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Extranet\Model\EO_ExtranetUser_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser_Collection merge(?\Bitrix\Extranet\Model\EO_ExtranetUser_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_ExtranetUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Extranet\Model\ExtranetUserTable */
		static public $dataClass = '\Bitrix\Extranet\Model\ExtranetUserTable';
	}
}
namespace Bitrix\Extranet\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExtranetUser_Result exec()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser fetchObject()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ExtranetUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser fetchObject()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser_Collection fetchCollection()
	 */
	class EO_ExtranetUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser createObject($setDefaultValues = true)
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser_Collection createCollection()
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser wakeUpObject($row)
	 * @method \Bitrix\Extranet\Model\EO_ExtranetUser_Collection wakeUpCollection($rows)
	 */
	class EO_ExtranetUser_Entity extends \Bitrix\Main\ORM\Entity {}
}