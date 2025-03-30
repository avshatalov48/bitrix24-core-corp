<?php

/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\Access\AccessPermissionTable:humanresources\lib\Model\Access\AccessPermissionTable.php */
namespace Bitrix\HumanResources\Model\Access {
	/**
	 * EO_AccessPermission
	 * @see \Bitrix\HumanResources\Model\Access\AccessPermissionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission resetRoleId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getPermissionId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission setPermissionId(\string|\Bitrix\Main\DB\SqlExpression $permissionId)
	 * @method bool hasPermissionId()
	 * @method bool isPermissionIdFilled()
	 * @method bool isPermissionIdChanged()
	 * @method \string remindActualPermissionId()
	 * @method \string requirePermissionId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission resetPermissionId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission unsetPermissionId()
	 * @method \string fillPermissionId()
	 * @method \int getValue()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission setValue(\int|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \int remindActualValue()
	 * @method \int requireValue()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission resetValue()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission unsetValue()
	 * @method \int fillValue()
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
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessPermission wakeUp($data)
	 */
	class EO_AccessPermission {
		/* @var \Bitrix\HumanResources\Model\Access\AccessPermissionTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\Access\AccessPermissionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\Access {
	/**
	 * EO_AccessPermission_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getPermissionIdList()
	 * @method \string[] fillPermissionId()
	 * @method \int[] getValueList()
	 * @method \int[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\Access\EO_AccessPermission $object)
	 * @method bool has(\Bitrix\HumanResources\Model\Access\EO_AccessPermission $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\Access\EO_AccessPermission $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessPermission_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission_Collection merge(?\Bitrix\HumanResources\Model\Access\EO_AccessPermission_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_AccessPermission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\Access\AccessPermissionTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\Access\AccessPermissionTable';
	}
}
namespace Bitrix\HumanResources\Model\Access {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_AccessPermission_Result exec()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission fetchObject()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission_Collection fetchCollection()
	 */
	class EO_AccessPermission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission fetchObject()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission_Collection fetchCollection()
	 */
	class EO_AccessPermission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission_Collection createCollection()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessPermission_Collection wakeUpCollection($rows)
	 */
	class EO_AccessPermission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\Access\AccessRoleRelationTable:humanresources\lib\Model\Access\AccessRoleRelationTable.php */
namespace Bitrix\HumanResources\Model\Access {
	/**
	 * EO_AccessRoleRelation
	 * @see \Bitrix\HumanResources\Model\Access\AccessRoleRelationTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation resetRoleId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getRelation()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation setRelation(\string|\Bitrix\Main\DB\SqlExpression $relation)
	 * @method bool hasRelation()
	 * @method bool isRelationFilled()
	 * @method bool isRelationChanged()
	 * @method \string remindActualRelation()
	 * @method \string requireRelation()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation resetRelation()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation unsetRelation()
	 * @method \string fillRelation()
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
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation wakeUp($data)
	 */
	class EO_AccessRoleRelation {
		/* @var \Bitrix\HumanResources\Model\Access\AccessRoleRelationTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\Access\AccessRoleRelationTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\Access {
	/**
	 * EO_AccessRoleRelation_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getRelationList()
	 * @method \string[] fillRelation()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation $object)
	 * @method bool has(\Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation_Collection merge(?\Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_AccessRoleRelation_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\Access\AccessRoleRelationTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\Access\AccessRoleRelationTable';
	}
}
namespace Bitrix\HumanResources\Model\Access {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_AccessRoleRelation_Result exec()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation fetchObject()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation_Collection fetchCollection()
	 */
	class EO_AccessRoleRelation_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation fetchObject()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation_Collection fetchCollection()
	 */
	class EO_AccessRoleRelation_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation_Collection createCollection()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation_Collection wakeUpCollection($rows)
	 */
	class EO_AccessRoleRelation_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\Access\AccessRoleTable:humanresources\lib\Model\Access\AccessRoleTable.php */
namespace Bitrix\HumanResources\Model\Access {
	/**
	 * EO_AccessRole
	 * @see \Bitrix\HumanResources\Model\Access\AccessRoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole resetName()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole unsetName()
	 * @method \string fillName()
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
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRole wakeUp($data)
	 */
	class EO_AccessRole {
		/* @var \Bitrix\HumanResources\Model\Access\AccessRoleTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\Access\AccessRoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\Access {
	/**
	 * EO_AccessRole_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\Access\EO_AccessRole $object)
	 * @method bool has(\Bitrix\HumanResources\Model\Access\EO_AccessRole $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\Access\EO_AccessRole $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRole_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole_Collection merge(?\Bitrix\HumanResources\Model\Access\EO_AccessRole_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_AccessRole_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\Access\AccessRoleTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\Access\AccessRoleTable';
	}
}
namespace Bitrix\HumanResources\Model\Access {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_AccessRole_Result exec()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole fetchObject()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole_Collection fetchCollection()
	 */
	class EO_AccessRole_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole fetchObject()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole_Collection fetchCollection()
	 */
	class EO_AccessRole_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole_Collection createCollection()
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\Access\EO_AccessRole_Collection wakeUpCollection($rows)
	 */
	class EO_AccessRole_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\HcmLink\CompanyTable:humanresources\lib\Model\HcmLink\CompanyTable.php */
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Company
	 * @see \Bitrix\HumanResources\Model\HcmLink\CompanyTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getMyCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company setMyCompanyId(\int|\Bitrix\Main\DB\SqlExpression $myCompanyId)
	 * @method bool hasMyCompanyId()
	 * @method bool isMyCompanyIdFilled()
	 * @method bool isMyCompanyIdChanged()
	 * @method \int remindActualMyCompanyId()
	 * @method \int requireMyCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company resetMyCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company unsetMyCompanyId()
	 * @method \int fillMyCompanyId()
	 * @method \string getCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company resetCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company unsetCode()
	 * @method \string fillCode()
	 * @method \string getTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company resetTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company unsetTitle()
	 * @method \string fillTitle()
	 * @method array getData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company setData(array|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method array remindActualData()
	 * @method array requireData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company resetData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company unsetData()
	 * @method array fillData()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection getFields()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection requireFields()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection fillFields()
	 * @method bool hasFields()
	 * @method bool isFieldsFilled()
	 * @method bool isFieldsChanged()
	 * @method void addToFields(\Bitrix\HumanResources\Model\HcmLink\Field $field)
	 * @method void removeFromFields(\Bitrix\HumanResources\Model\HcmLink\Field $field)
	 * @method void removeAllFields()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company resetFields()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company unsetFields()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection getPersons()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection requirePersons()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection fillPersons()
	 * @method bool hasPersons()
	 * @method bool isPersonsFilled()
	 * @method bool isPersonsChanged()
	 * @method void addToPersons(\Bitrix\HumanResources\Model\HcmLink\Person $person)
	 * @method void removeFromPersons(\Bitrix\HumanResources\Model\HcmLink\Person $person)
	 * @method void removeAllPersons()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company resetPersons()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company unsetPersons()
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection getJobs()
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection requireJobs()
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection fillJobs()
	 * @method bool hasJobs()
	 * @method bool isJobsFilled()
	 * @method bool isJobsChanged()
	 * @method void addToJobs(\Bitrix\HumanResources\Model\HcmLink\Job $job)
	 * @method void removeFromJobs(\Bitrix\HumanResources\Model\HcmLink\Job $job)
	 * @method void removeAllJobs()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company resetJobs()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company unsetJobs()
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
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\HcmLink\Company wakeUp($data)
	 */
	class EO_Company {
		/* @var \Bitrix\HumanResources\Model\HcmLink\CompanyTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\CompanyTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * CompanyCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getMyCompanyIdList()
	 * @method \int[] fillMyCompanyId()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method array[] getDataList()
	 * @method array[] fillData()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection[] getFieldsList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection getFieldsCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection fillFields()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection[] getPersonsList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection getPersonsCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection fillPersons()
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection[] getJobsList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection getJobsCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection fillJobs()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\HcmLink\Company $object)
	 * @method bool has(\Bitrix\HumanResources\Model\HcmLink\Company $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\HcmLink\Company $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\HcmLink\CompanyCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\HcmLink\CompanyCollection merge(?\Bitrix\HumanResources\Model\HcmLink\CompanyCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Company_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\HcmLink\CompanyTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\CompanyTable';
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Company_Result exec()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\CompanyCollection fetchCollection()
	 */
	class EO_Company_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\CompanyCollection fetchCollection()
	 */
	class EO_Company_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\HcmLink\CompanyCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\HcmLink\CompanyCollection wakeUpCollection($rows)
	 */
	class EO_Company_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\HcmLink\EmployeeTable:humanresources\lib\Model\HcmLink\EmployeeTable.php */
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Employee
	 * @see \Bitrix\HumanResources\Model\HcmLink\EmployeeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getPersonId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee setPersonId(\int|\Bitrix\Main\DB\SqlExpression $personId)
	 * @method bool hasPersonId()
	 * @method bool isPersonIdFilled()
	 * @method bool isPersonIdChanged()
	 * @method \int remindActualPersonId()
	 * @method \int requirePersonId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee resetPersonId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee unsetPersonId()
	 * @method \int fillPersonId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person getPerson()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person remindActualPerson()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person requirePerson()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee setPerson(\Bitrix\HumanResources\Model\HcmLink\Person $object)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee resetPerson()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee unsetPerson()
	 * @method bool hasPerson()
	 * @method bool isPersonFilled()
	 * @method bool isPersonChanged()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person fillPerson()
	 * @method \string getCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee resetCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee unsetCode()
	 * @method \string fillCode()
	 * @method array getData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee setData(array|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method array remindActualData()
	 * @method array requireData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee resetData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee unsetData()
	 * @method array fillData()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
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
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\HcmLink\Employee wakeUp($data)
	 */
	class EO_Employee {
		/* @var \Bitrix\HumanResources\Model\HcmLink\EmployeeTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\EmployeeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * EmployeeCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getPersonIdList()
	 * @method \int[] fillPersonId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person[] getPersonList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection getPersonCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection fillPerson()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method array[] getDataList()
	 * @method array[] fillData()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\HcmLink\Employee $object)
	 * @method bool has(\Bitrix\HumanResources\Model\HcmLink\Employee $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\HcmLink\Employee $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection merge(?\Bitrix\HumanResources\Model\HcmLink\EmployeeCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Employee_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\HcmLink\EmployeeTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\EmployeeTable';
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Employee_Result exec()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection fetchCollection()
	 */
	class EO_Employee_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection fetchCollection()
	 */
	class EO_Employee_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection wakeUpCollection($rows)
	 */
	class EO_Employee_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\HcmLink\FieldTable:humanresources\lib\Model\HcmLink\FieldTable.php */
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Field
	 * @see \Bitrix\HumanResources\Model\HcmLink\FieldTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field setCompanyId(\int|\Bitrix\Main\DB\SqlExpression $companyId)
	 * @method bool hasCompanyId()
	 * @method bool isCompanyIdFilled()
	 * @method bool isCompanyIdChanged()
	 * @method \int remindActualCompanyId()
	 * @method \int requireCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field resetCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field unsetCompanyId()
	 * @method \int fillCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company getCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company remindActualCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company requireCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field setCompany(\Bitrix\HumanResources\Model\HcmLink\Company $object)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field resetCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field unsetCompany()
	 * @method bool hasCompany()
	 * @method bool isCompanyFilled()
	 * @method bool isCompanyChanged()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company fillCompany()
	 * @method \string getCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field resetCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field unsetCode()
	 * @method \string fillCode()
	 * @method \int getType()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field setType(\int|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \int remindActualType()
	 * @method \int requireType()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field resetType()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field unsetType()
	 * @method \int fillType()
	 * @method \int getEntityType()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field setEntityType(\int|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \int remindActualEntityType()
	 * @method \int requireEntityType()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field resetEntityType()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field unsetEntityType()
	 * @method \int fillEntityType()
	 * @method \int getTtl()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field setTtl(\int|\Bitrix\Main\DB\SqlExpression $ttl)
	 * @method bool hasTtl()
	 * @method bool isTtlFilled()
	 * @method bool isTtlChanged()
	 * @method \int remindActualTtl()
	 * @method \int requireTtl()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field resetTtl()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field unsetTtl()
	 * @method \int fillTtl()
	 * @method \string getTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field resetTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field unsetTitle()
	 * @method \string fillTitle()
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
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\HcmLink\Field wakeUp($data)
	 */
	class EO_Field {
		/* @var \Bitrix\HumanResources\Model\HcmLink\FieldTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\FieldTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * FieldCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCompanyIdList()
	 * @method \int[] fillCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company[] getCompanyList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection getCompanyCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\CompanyCollection fillCompany()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \int[] getTypeList()
	 * @method \int[] fillType()
	 * @method \int[] getEntityTypeList()
	 * @method \int[] fillEntityType()
	 * @method \int[] getTtlList()
	 * @method \int[] fillTtl()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\HcmLink\Field $object)
	 * @method bool has(\Bitrix\HumanResources\Model\HcmLink\Field $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\HcmLink\Field $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\HcmLink\FieldCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection merge(?\Bitrix\HumanResources\Model\HcmLink\FieldCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Field_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\HcmLink\FieldTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\FieldTable';
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Field_Result exec()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection fetchCollection()
	 */
	class EO_Field_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection fetchCollection()
	 */
	class EO_Field_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection wakeUpCollection($rows)
	 */
	class EO_Field_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\HcmLink\FieldValueTable:humanresources\lib\Model\HcmLink\FieldValueTable.php */
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * FieldValue
	 * @see \Bitrix\HumanResources\Model\HcmLink\FieldValueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getEmployeeId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue setEmployeeId(\int|\Bitrix\Main\DB\SqlExpression $employeeId)
	 * @method bool hasEmployeeId()
	 * @method bool isEmployeeIdFilled()
	 * @method bool isEmployeeIdChanged()
	 * @method \int remindActualEmployeeId()
	 * @method \int requireEmployeeId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue resetEmployeeId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue unsetEmployeeId()
	 * @method \int fillEmployeeId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee getEmployee()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee remindActualEmployee()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee requireEmployee()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue setEmployee(\Bitrix\HumanResources\Model\HcmLink\Employee $object)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue resetEmployee()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue unsetEmployee()
	 * @method bool hasEmployee()
	 * @method bool isEmployeeFilled()
	 * @method bool isEmployeeChanged()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee fillEmployee()
	 * @method \int getFieldId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue setFieldId(\int|\Bitrix\Main\DB\SqlExpression $fieldId)
	 * @method bool hasFieldId()
	 * @method bool isFieldIdFilled()
	 * @method bool isFieldIdChanged()
	 * @method \int remindActualFieldId()
	 * @method \int requireFieldId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue resetFieldId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue unsetFieldId()
	 * @method \int fillFieldId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field getField()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field remindActualField()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field requireField()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue setField(\Bitrix\HumanResources\Model\HcmLink\Field $object)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue resetField()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue unsetField()
	 * @method bool hasField()
	 * @method bool isFieldFilled()
	 * @method bool isFieldChanged()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field fillField()
	 * @method \string getValue()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue resetValue()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue unsetValue()
	 * @method \string fillValue()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getExpiredAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue setExpiredAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $expiredAt)
	 * @method bool hasExpiredAt()
	 * @method bool isExpiredAtFilled()
	 * @method bool isExpiredAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualExpiredAt()
	 * @method \Bitrix\Main\Type\DateTime requireExpiredAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue resetExpiredAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue unsetExpiredAt()
	 * @method \Bitrix\Main\Type\DateTime fillExpiredAt()
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
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\HcmLink\FieldValue wakeUp($data)
	 */
	class EO_FieldValue {
		/* @var \Bitrix\HumanResources\Model\HcmLink\FieldValueTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\FieldValueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * FieldValueCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getEmployeeIdList()
	 * @method \int[] fillEmployeeId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Employee[] getEmployeeList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection getEmployeeCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection fillEmployee()
	 * @method \int[] getFieldIdList()
	 * @method \int[] fillFieldId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Field[] getFieldList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection getFieldCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldCollection fillField()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getExpiredAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillExpiredAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\HcmLink\FieldValue $object)
	 * @method bool has(\Bitrix\HumanResources\Model\HcmLink\FieldValue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\HcmLink\FieldValue $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection merge(?\Bitrix\HumanResources\Model\HcmLink\FieldValueCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FieldValue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\HcmLink\FieldValueTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\FieldValueTable';
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FieldValue_Result exec()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection fetchCollection()
	 */
	class EO_FieldValue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection fetchCollection()
	 */
	class EO_FieldValue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValue wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection wakeUpCollection($rows)
	 */
	class EO_FieldValue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\HcmLink\Index\PersonTable:humanresources\lib\Model\HcmLink\Index\PersonTable.php */
namespace Bitrix\HumanResources\Model\HcmLink\Index {
	/**
	 * EO_Person
	 * @see \Bitrix\HumanResources\Model\HcmLink\Index\PersonTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getPersonId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person setPersonId(\int|\Bitrix\Main\DB\SqlExpression $personId)
	 * @method bool hasPersonId()
	 * @method bool isPersonIdFilled()
	 * @method bool isPersonIdChanged()
	 * @method \string getSearchContent()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person setSearchContent(\string|\Bitrix\Main\DB\SqlExpression $searchContent)
	 * @method bool hasSearchContent()
	 * @method bool isSearchContentFilled()
	 * @method bool isSearchContentChanged()
	 * @method \string remindActualSearchContent()
	 * @method \string requireSearchContent()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person resetSearchContent()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person unsetSearchContent()
	 * @method \string fillSearchContent()
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
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person wakeUp($data)
	 */
	class EO_Person {
		/* @var \Bitrix\HumanResources\Model\HcmLink\Index\PersonTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\Index\PersonTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\HcmLink\Index {
	/**
	 * EO_Person_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getPersonIdList()
	 * @method \string[] getSearchContentList()
	 * @method \string[] fillSearchContent()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\HcmLink\Index\EO_Person $object)
	 * @method bool has(\Bitrix\HumanResources\Model\HcmLink\Index\EO_Person $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\HcmLink\Index\EO_Person $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person_Collection merge(?\Bitrix\HumanResources\Model\HcmLink\Index\EO_Person_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Person_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\HcmLink\Index\PersonTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\Index\PersonTable';
	}
}
namespace Bitrix\HumanResources\Model\HcmLink\Index {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Person_Result exec()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person_Collection fetchCollection()
	 */
	class EO_Person_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person_Collection fetchCollection()
	 */
	class EO_Person_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person_Collection createCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person_Collection wakeUpCollection($rows)
	 */
	class EO_Person_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\HcmLink\JobTable:humanresources\lib\Model\HcmLink\JobTable.php */
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Job
	 * @see \Bitrix\HumanResources\Model\HcmLink\JobTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setCompanyId(\int|\Bitrix\Main\DB\SqlExpression $companyId)
	 * @method bool hasCompanyId()
	 * @method bool isCompanyIdFilled()
	 * @method bool isCompanyIdChanged()
	 * @method \int remindActualCompanyId()
	 * @method \int requireCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetCompanyId()
	 * @method \int fillCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company getCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company remindActualCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company requireCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setCompany(\Bitrix\HumanResources\Model\HcmLink\Company $object)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetCompany()
	 * @method bool hasCompany()
	 * @method bool isCompanyFilled()
	 * @method bool isCompanyChanged()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company fillCompany()
	 * @method \int getType()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setType(\int|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \int remindActualType()
	 * @method \int requireType()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetType()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetType()
	 * @method \int fillType()
	 * @method \int getStatus()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetStatus()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetStatus()
	 * @method \int fillStatus()
	 * @method \int getProgressReceived()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setProgressReceived(\int|\Bitrix\Main\DB\SqlExpression $progressReceived)
	 * @method bool hasProgressReceived()
	 * @method bool isProgressReceivedFilled()
	 * @method bool isProgressReceivedChanged()
	 * @method \int remindActualProgressReceived()
	 * @method \int requireProgressReceived()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetProgressReceived()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetProgressReceived()
	 * @method \int fillProgressReceived()
	 * @method \int getProgressTotal()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setProgressTotal(\int|\Bitrix\Main\DB\SqlExpression $progressTotal)
	 * @method bool hasProgressTotal()
	 * @method bool isProgressTotalFilled()
	 * @method bool isProgressTotalChanged()
	 * @method \int remindActualProgressTotal()
	 * @method \int requireProgressTotal()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetProgressTotal()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetProgressTotal()
	 * @method \int fillProgressTotal()
	 * @method array getInputData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setInputData(array|\Bitrix\Main\DB\SqlExpression $inputData)
	 * @method bool hasInputData()
	 * @method bool isInputDataFilled()
	 * @method bool isInputDataChanged()
	 * @method array remindActualInputData()
	 * @method array requireInputData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetInputData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetInputData()
	 * @method array fillInputData()
	 * @method array getOutputData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setOutputData(array|\Bitrix\Main\DB\SqlExpression $outputData)
	 * @method bool hasOutputData()
	 * @method bool isOutputDataFilled()
	 * @method bool isOutputDataChanged()
	 * @method array remindActualOutputData()
	 * @method array requireOutputData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetOutputData()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetOutputData()
	 * @method array fillOutputData()
	 * @method \int getEventCount()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setEventCount(\int|\Bitrix\Main\DB\SqlExpression $eventCount)
	 * @method bool hasEventCount()
	 * @method bool isEventCountFilled()
	 * @method bool isEventCountChanged()
	 * @method \int remindActualEventCount()
	 * @method \int requireEventCount()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetEventCount()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetEventCount()
	 * @method \int fillEventCount()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime getFinishedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job setFinishedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $finishedAt)
	 * @method bool hasFinishedAt()
	 * @method bool isFinishedAtFilled()
	 * @method bool isFinishedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualFinishedAt()
	 * @method \Bitrix\Main\Type\DateTime requireFinishedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job resetFinishedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unsetFinishedAt()
	 * @method \Bitrix\Main\Type\DateTime fillFinishedAt()
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
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\HcmLink\Job wakeUp($data)
	 */
	class EO_Job {
		/* @var \Bitrix\HumanResources\Model\HcmLink\JobTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\JobTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * JobCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCompanyIdList()
	 * @method \int[] fillCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company[] getCompanyList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection getCompanyCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\CompanyCollection fillCompany()
	 * @method \int[] getTypeList()
	 * @method \int[] fillType()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \int[] getProgressReceivedList()
	 * @method \int[] fillProgressReceived()
	 * @method \int[] getProgressTotalList()
	 * @method \int[] fillProgressTotal()
	 * @method array[] getInputDataList()
	 * @method array[] fillInputData()
	 * @method array[] getOutputDataList()
	 * @method array[] fillOutputData()
	 * @method \int[] getEventCountList()
	 * @method \int[] fillEventCount()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getFinishedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillFinishedAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\HcmLink\Job $object)
	 * @method bool has(\Bitrix\HumanResources\Model\HcmLink\Job $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\HcmLink\Job $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\HcmLink\JobCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection merge(?\Bitrix\HumanResources\Model\HcmLink\JobCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Job_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\HcmLink\JobTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\JobTable';
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Job_Result exec()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection fetchCollection()
	 */
	class EO_Job_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection fetchCollection()
	 */
	class EO_Job_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Job wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\HcmLink\JobCollection wakeUpCollection($rows)
	 */
	class EO_Job_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\HcmLink\MemberMapTable:humanresources\lib\Model\HcmLink\MemberMapTable.php */
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * MemberMap
	 * @see \Bitrix\HumanResources\Model\HcmLink\MemberMapTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getEntityId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap resetEntityId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \int getCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap setCompanyId(\int|\Bitrix\Main\DB\SqlExpression $companyId)
	 * @method bool hasCompanyId()
	 * @method bool isCompanyIdFilled()
	 * @method bool isCompanyIdChanged()
	 * @method \int remindActualCompanyId()
	 * @method \int requireCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap resetCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap unsetCompanyId()
	 * @method \int fillCompanyId()
	 * @method \string getExternalTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap setExternalTitle(\string|\Bitrix\Main\DB\SqlExpression $externalTitle)
	 * @method bool hasExternalTitle()
	 * @method bool isExternalTitleFilled()
	 * @method bool isExternalTitleChanged()
	 * @method \string remindActualExternalTitle()
	 * @method \string requireExternalTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap resetExternalTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap unsetExternalTitle()
	 * @method \string fillExternalTitle()
	 * @method \string getExternalId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap setExternalId(\string|\Bitrix\Main\DB\SqlExpression $externalId)
	 * @method bool hasExternalId()
	 * @method bool isExternalIdFilled()
	 * @method bool isExternalIdChanged()
	 * @method \string remindActualExternalId()
	 * @method \string requireExternalId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap resetExternalId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap unsetExternalId()
	 * @method \string fillExternalId()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap resetCreatedBy()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getModifiedBy()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap setModifiedBy(\int|\Bitrix\Main\DB\SqlExpression $modifiedBy)
	 * @method bool hasModifiedBy()
	 * @method bool isModifiedByFilled()
	 * @method bool isModifiedByChanged()
	 * @method \int remindActualModifiedBy()
	 * @method \int requireModifiedBy()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap resetModifiedBy()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap unsetModifiedBy()
	 * @method \int fillModifiedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap resetUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
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
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\HcmLink\MemberMap wakeUp($data)
	 */
	class EO_MemberMap {
		/* @var \Bitrix\HumanResources\Model\HcmLink\MemberMapTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\MemberMapTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * MemberMapCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \int[] getCompanyIdList()
	 * @method \int[] fillCompanyId()
	 * @method \string[] getExternalTitleList()
	 * @method \string[] fillExternalTitle()
	 * @method \string[] getExternalIdList()
	 * @method \string[] fillExternalId()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getModifiedByList()
	 * @method \int[] fillModifiedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\HcmLink\MemberMap $object)
	 * @method bool has(\Bitrix\HumanResources\Model\HcmLink\MemberMap $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\HcmLink\MemberMap $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\HcmLink\MemberMapCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMapCollection merge(?\Bitrix\HumanResources\Model\HcmLink\MemberMapCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_MemberMap_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\HcmLink\MemberMapTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\MemberMapTable';
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_MemberMap_Result exec()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMapCollection fetchCollection()
	 */
	class EO_MemberMap_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMapCollection fetchCollection()
	 */
	class EO_MemberMap_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMapCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMap wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\HcmLink\MemberMapCollection wakeUpCollection($rows)
	 */
	class EO_MemberMap_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\HcmLink\PersonTable:humanresources\lib\Model\HcmLink\PersonTable.php */
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Person
	 * @see \Bitrix\HumanResources\Model\HcmLink\PersonTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person setCompanyId(\int|\Bitrix\Main\DB\SqlExpression $companyId)
	 * @method bool hasCompanyId()
	 * @method bool isCompanyIdFilled()
	 * @method bool isCompanyIdChanged()
	 * @method \int remindActualCompanyId()
	 * @method \int requireCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person resetCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unsetCompanyId()
	 * @method \int fillCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company getCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company remindActualCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company requireCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person setCompany(\Bitrix\HumanResources\Model\HcmLink\Company $object)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person resetCompany()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unsetCompany()
	 * @method bool hasCompany()
	 * @method bool isCompanyFilled()
	 * @method bool isCompanyChanged()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company fillCompany()
	 * @method \int getUserId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person resetUserId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getMatchCounter()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person setMatchCounter(\int|\Bitrix\Main\DB\SqlExpression $matchCounter)
	 * @method bool hasMatchCounter()
	 * @method bool isMatchCounterFilled()
	 * @method bool isMatchCounterChanged()
	 * @method \int remindActualMatchCounter()
	 * @method \int requireMatchCounter()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person resetMatchCounter()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unsetMatchCounter()
	 * @method \int fillMatchCounter()
	 * @method \string getCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person resetCode()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unsetCode()
	 * @method \string fillCode()
	 * @method \string getTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person resetTitle()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unsetTitle()
	 * @method \string fillTitle()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person resetUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection getEmployees()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection requireEmployees()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection fillEmployees()
	 * @method bool hasEmployees()
	 * @method bool isEmployeesFilled()
	 * @method bool isEmployeesChanged()
	 * @method void addToEmployees(\Bitrix\HumanResources\Model\HcmLink\Employee $employee)
	 * @method void removeFromEmployees(\Bitrix\HumanResources\Model\HcmLink\Employee $employee)
	 * @method void removeAllEmployees()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person resetEmployees()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unsetEmployees()
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
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\HcmLink\Person wakeUp($data)
	 */
	class EO_Person {
		/* @var \Bitrix\HumanResources\Model\HcmLink\PersonTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\PersonTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * PersonCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCompanyIdList()
	 * @method \int[] fillCompanyId()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Company[] getCompanyList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection getCompanyCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\CompanyCollection fillCompany()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getMatchCounterList()
	 * @method \int[] fillMatchCounter()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection[] getEmployeesList()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection getEmployeesCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection fillEmployees()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\HcmLink\Person $object)
	 * @method bool has(\Bitrix\HumanResources\Model\HcmLink\Person $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\HcmLink\Person $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\HcmLink\PersonCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection merge(?\Bitrix\HumanResources\Model\HcmLink\PersonCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Person_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\HcmLink\PersonTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\HcmLink\PersonTable';
	}
}
namespace Bitrix\HumanResources\Model\HcmLink {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Person_Result exec()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection fetchCollection()
	 */
	class EO_Person_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person fetchObject()
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection fetchCollection()
	 */
	class EO_Person_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\HcmLink\Person wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\HcmLink\PersonCollection wakeUpCollection($rows)
	 */
	class EO_Person_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\LogTable:humanresources\lib\Model\LogTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * Log
	 * @see \Bitrix\HumanResources\Model\LogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\Log setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\HumanResources\Model\Log setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\HumanResources\Model\Log resetDateCreate()
	 * @method \Bitrix\HumanResources\Model\Log unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \string getMessage()
	 * @method \Bitrix\HumanResources\Model\Log setMessage(\string|\Bitrix\Main\DB\SqlExpression $message)
	 * @method bool hasMessage()
	 * @method bool isMessageFilled()
	 * @method bool isMessageChanged()
	 * @method \string remindActualMessage()
	 * @method \string requireMessage()
	 * @method \Bitrix\HumanResources\Model\Log resetMessage()
	 * @method \Bitrix\HumanResources\Model\Log unsetMessage()
	 * @method \string fillMessage()
	 * @method \string getEntityType()
	 * @method \Bitrix\HumanResources\Model\Log setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\HumanResources\Model\Log resetEntityType()
	 * @method \Bitrix\HumanResources\Model\Log unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \int getEntityId()
	 * @method \Bitrix\HumanResources\Model\Log setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\HumanResources\Model\Log resetEntityId()
	 * @method \Bitrix\HumanResources\Model\Log unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \int getUserId()
	 * @method \Bitrix\HumanResources\Model\Log setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\HumanResources\Model\Log resetUserId()
	 * @method \Bitrix\HumanResources\Model\Log unsetUserId()
	 * @method \int fillUserId()
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
	 * @method \Bitrix\HumanResources\Model\Log set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\Log reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\Log unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\Log wakeUp($data)
	 */
	class EO_Log {
		/* @var \Bitrix\HumanResources\Model\LogTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\LogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * EO_Log_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \string[] getMessageList()
	 * @method \string[] fillMessage()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\Log $object)
	 * @method bool has(\Bitrix\HumanResources\Model\Log $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Log getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Log[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\Log $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\EO_Log_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\Log current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\EO_Log_Collection merge(?\Bitrix\HumanResources\Model\EO_Log_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Log_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\LogTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\LogTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Log_Result exec()
	 * @method \Bitrix\HumanResources\Model\Log fetchObject()
	 * @method \Bitrix\HumanResources\Model\EO_Log_Collection fetchCollection()
	 */
	class EO_Log_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\Log fetchObject()
	 * @method \Bitrix\HumanResources\Model\EO_Log_Collection fetchCollection()
	 */
	class EO_Log_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\Log createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\EO_Log_Collection createCollection()
	 * @method \Bitrix\HumanResources\Model\Log wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\EO_Log_Collection wakeUpCollection($rows)
	 */
	class EO_Log_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeTable:humanresources\lib\Model\NodeTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * Node
	 * @see \Bitrix\HumanResources\Model\NodeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\Node setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\HumanResources\Model\Node setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\HumanResources\Model\Node resetName()
	 * @method \Bitrix\HumanResources\Model\Node unsetName()
	 * @method \string fillName()
	 * @method \string getType()
	 * @method \Bitrix\HumanResources\Model\Node setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\HumanResources\Model\Node resetType()
	 * @method \Bitrix\HumanResources\Model\Node unsetType()
	 * @method \string fillType()
	 * @method \int getStructureId()
	 * @method \Bitrix\HumanResources\Model\Node setStructureId(\int|\Bitrix\Main\DB\SqlExpression $structureId)
	 * @method bool hasStructureId()
	 * @method bool isStructureIdFilled()
	 * @method bool isStructureIdChanged()
	 * @method \int remindActualStructureId()
	 * @method \int requireStructureId()
	 * @method \Bitrix\HumanResources\Model\Node resetStructureId()
	 * @method \Bitrix\HumanResources\Model\Node unsetStructureId()
	 * @method \int fillStructureId()
	 * @method \int getParentId()
	 * @method \Bitrix\HumanResources\Model\Node setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\HumanResources\Model\Node resetParentId()
	 * @method \Bitrix\HumanResources\Model\Node unsetParentId()
	 * @method \int fillParentId()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\HumanResources\Model\Node setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\HumanResources\Model\Node resetCreatedBy()
	 * @method \Bitrix\HumanResources\Model\Node unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\Node setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\Node resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\Node unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\Node setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\Node resetUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\Node unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
	 * @method null|\string getXmlId()
	 * @method \Bitrix\HumanResources\Model\Node setXmlId(null|\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method null|\string remindActualXmlId()
	 * @method null|\string requireXmlId()
	 * @method \Bitrix\HumanResources\Model\Node resetXmlId()
	 * @method \Bitrix\HumanResources\Model\Node unsetXmlId()
	 * @method null|\string fillXmlId()
	 * @method \boolean getActive()
	 * @method \Bitrix\HumanResources\Model\Node setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\HumanResources\Model\Node resetActive()
	 * @method \Bitrix\HumanResources\Model\Node unsetActive()
	 * @method \boolean fillActive()
	 * @method \boolean getGlobalActive()
	 * @method \Bitrix\HumanResources\Model\Node setGlobalActive(\boolean|\Bitrix\Main\DB\SqlExpression $globalActive)
	 * @method bool hasGlobalActive()
	 * @method bool isGlobalActiveFilled()
	 * @method bool isGlobalActiveChanged()
	 * @method \boolean remindActualGlobalActive()
	 * @method \boolean requireGlobalActive()
	 * @method \Bitrix\HumanResources\Model\Node resetGlobalActive()
	 * @method \Bitrix\HumanResources\Model\Node unsetGlobalActive()
	 * @method \boolean fillGlobalActive()
	 * @method \int getSort()
	 * @method \Bitrix\HumanResources\Model\Node setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\HumanResources\Model\Node resetSort()
	 * @method \Bitrix\HumanResources\Model\Node unsetSort()
	 * @method \int fillSort()
	 * @method null|\string getDescription()
	 * @method \Bitrix\HumanResources\Model\Node setDescription(null|\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method null|\string remindActualDescription()
	 * @method null|\string requireDescription()
	 * @method \Bitrix\HumanResources\Model\Node resetDescription()
	 * @method \Bitrix\HumanResources\Model\Node unsetDescription()
	 * @method null|\string fillDescription()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection getAccessCode()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection requireAccessCode()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection fillAccessCode()
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method void addToAccessCode(\Bitrix\HumanResources\Model\NodeBackwardAccessCode $nodeBackwardAccessCode)
	 * @method void removeFromAccessCode(\Bitrix\HumanResources\Model\NodeBackwardAccessCode $nodeBackwardAccessCode)
	 * @method void removeAllAccessCode()
	 * @method \Bitrix\HumanResources\Model\Node resetAccessCode()
	 * @method \Bitrix\HumanResources\Model\Node unsetAccessCode()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection getChildNodes()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection requireChildNodes()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection fillChildNodes()
	 * @method bool hasChildNodes()
	 * @method bool isChildNodesFilled()
	 * @method bool isChildNodesChanged()
	 * @method void addToChildNodes(\Bitrix\HumanResources\Model\NodePath $nodePath)
	 * @method void removeFromChildNodes(\Bitrix\HumanResources\Model\NodePath $nodePath)
	 * @method void removeAllChildNodes()
	 * @method \Bitrix\HumanResources\Model\Node resetChildNodes()
	 * @method \Bitrix\HumanResources\Model\Node unsetChildNodes()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection getParentNodes()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection requireParentNodes()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection fillParentNodes()
	 * @method bool hasParentNodes()
	 * @method bool isParentNodesFilled()
	 * @method bool isParentNodesChanged()
	 * @method void addToParentNodes(\Bitrix\HumanResources\Model\NodePath $nodePath)
	 * @method void removeFromParentNodes(\Bitrix\HumanResources\Model\NodePath $nodePath)
	 * @method void removeAllParentNodes()
	 * @method \Bitrix\HumanResources\Model\Node resetParentNodes()
	 * @method \Bitrix\HumanResources\Model\Node unsetParentNodes()
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
	 * @method \Bitrix\HumanResources\Model\Node set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\Node reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\Node unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\Node wakeUp($data)
	 */
	class EO_Node {
		/* @var \Bitrix\HumanResources\Model\NodeTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getStructureIdList()
	 * @method \int[] fillStructureId()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 * @method null|\string[] getXmlIdList()
	 * @method null|\string[] fillXmlId()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \boolean[] getGlobalActiveList()
	 * @method \boolean[] fillGlobalActive()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method null|\string[] getDescriptionList()
	 * @method null|\string[] fillDescription()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection[] getAccessCodeList()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection getAccessCodeCollection()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection fillAccessCode()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection[] getChildNodesList()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection getChildNodesCollection()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection fillChildNodes()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection[] getParentNodesList()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection getParentNodesCollection()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection fillParentNodes()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\Node $object)
	 * @method bool has(\Bitrix\HumanResources\Model\Node $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Node getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Node[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\Node $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\NodeCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\Node current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\NodeCollection merge(?\Bitrix\HumanResources\Model\NodeCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Node_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\NodeTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Node_Result exec()
	 * @method \Bitrix\HumanResources\Model\Node fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeCollection fetchCollection()
	 */
	class EO_Node_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\Node fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeCollection fetchCollection()
	 */
	class EO_Node_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\Node createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\NodeCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\Node wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\NodeCollection wakeUpCollection($rows)
	 */
	class EO_Node_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable:humanresources\lib\Model\NodeBackwardAccessCodeTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeBackwardAccessCode
	 * @see \Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode setNodeId(\int|\Bitrix\Main\DB\SqlExpression $nodeId)
	 * @method bool hasNodeId()
	 * @method bool isNodeIdFilled()
	 * @method bool isNodeIdChanged()
	 * @method \int remindActualNodeId()
	 * @method \int requireNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode resetNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode unsetNodeId()
	 * @method \int fillNodeId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode resetAccessCode()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \Bitrix\HumanResources\Model\Node getNode()
	 * @method \Bitrix\HumanResources\Model\Node remindActualNode()
	 * @method \Bitrix\HumanResources\Model\Node requireNode()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode setNode(\Bitrix\HumanResources\Model\Node $object)
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode resetNode()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode unsetNode()
	 * @method bool hasNode()
	 * @method bool isNodeFilled()
	 * @method bool isNodeChanged()
	 * @method \Bitrix\HumanResources\Model\Node fillNode()
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
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\NodeBackwardAccessCode wakeUp($data)
	 */
	class EO_NodeBackwardAccessCode {
		/* @var \Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeBackwardAccessCodeCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getNodeIdList()
	 * @method \int[] fillNodeId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \Bitrix\HumanResources\Model\Node[] getNodeList()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection getNodeCollection()
	 * @method \Bitrix\HumanResources\Model\NodeCollection fillNode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\NodeBackwardAccessCode $object)
	 * @method bool has(\Bitrix\HumanResources\Model\NodeBackwardAccessCode $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\NodeBackwardAccessCode $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection merge(?\Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_NodeBackwardAccessCode_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_NodeBackwardAccessCode_Result exec()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection fetchCollection()
	 */
	class EO_NodeBackwardAccessCode_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection fetchCollection()
	 */
	class EO_NodeBackwardAccessCode_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCode wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection wakeUpCollection($rows)
	 */
	class EO_NodeBackwardAccessCode_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeMemberTable:humanresources\lib\Model\NodeMemberTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeMember
	 * @see \Bitrix\HumanResources\Model\NodeMemberTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\NodeMember setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getEntityType()
	 * @method \Bitrix\HumanResources\Model\NodeMember setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\HumanResources\Model\NodeMember resetEntityType()
	 * @method \Bitrix\HumanResources\Model\NodeMember unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \int getEntityId()
	 * @method \Bitrix\HumanResources\Model\NodeMember setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\HumanResources\Model\NodeMember resetEntityId()
	 * @method \Bitrix\HumanResources\Model\NodeMember unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \boolean getActive()
	 * @method \Bitrix\HumanResources\Model\NodeMember setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\HumanResources\Model\NodeMember resetActive()
	 * @method \Bitrix\HumanResources\Model\NodeMember unsetActive()
	 * @method \boolean fillActive()
	 * @method \int getNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeMember setNodeId(\int|\Bitrix\Main\DB\SqlExpression $nodeId)
	 * @method bool hasNodeId()
	 * @method bool isNodeIdFilled()
	 * @method bool isNodeIdChanged()
	 * @method \int remindActualNodeId()
	 * @method \int requireNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeMember resetNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeMember unsetNodeId()
	 * @method \int fillNodeId()
	 * @method \int getAddedBy()
	 * @method \Bitrix\HumanResources\Model\NodeMember setAddedBy(\int|\Bitrix\Main\DB\SqlExpression $addedBy)
	 * @method bool hasAddedBy()
	 * @method bool isAddedByFilled()
	 * @method bool isAddedByChanged()
	 * @method \int remindActualAddedBy()
	 * @method \int requireAddedBy()
	 * @method \Bitrix\HumanResources\Model\NodeMember resetAddedBy()
	 * @method \Bitrix\HumanResources\Model\NodeMember unsetAddedBy()
	 * @method \int fillAddedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMember setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMember resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMember unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMember setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMember resetUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMember unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\Node getNode()
	 * @method \Bitrix\HumanResources\Model\Node remindActualNode()
	 * @method \Bitrix\HumanResources\Model\Node requireNode()
	 * @method \Bitrix\HumanResources\Model\NodeMember setNode(\Bitrix\HumanResources\Model\Node $object)
	 * @method \Bitrix\HumanResources\Model\NodeMember resetNode()
	 * @method \Bitrix\HumanResources\Model\NodeMember unsetNode()
	 * @method bool hasNode()
	 * @method bool isNodeFilled()
	 * @method bool isNodeChanged()
	 * @method \Bitrix\HumanResources\Model\Node fillNode()
	 * @method \Bitrix\HumanResources\Model\RoleCollection getRole()
	 * @method \Bitrix\HumanResources\Model\RoleCollection requireRole()
	 * @method \Bitrix\HumanResources\Model\RoleCollection fillRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method void addToRole(\Bitrix\HumanResources\Model\Role $role)
	 * @method void removeFromRole(\Bitrix\HumanResources\Model\Role $role)
	 * @method void removeAllRole()
	 * @method \Bitrix\HumanResources\Model\NodeMember resetRole()
	 * @method \Bitrix\HumanResources\Model\NodeMember unsetRole()
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
	 * @method \Bitrix\HumanResources\Model\NodeMember set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\NodeMember reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\NodeMember unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\NodeMember wakeUp($data)
	 */
	class EO_NodeMember {
		/* @var \Bitrix\HumanResources\Model\NodeMemberTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeMemberTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeMemberCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \int[] getNodeIdList()
	 * @method \int[] fillNodeId()
	 * @method \int[] getAddedByList()
	 * @method \int[] fillAddedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\Node[] getNodeList()
	 * @method \Bitrix\HumanResources\Model\NodeMemberCollection getNodeCollection()
	 * @method \Bitrix\HumanResources\Model\NodeCollection fillNode()
	 * @method \Bitrix\HumanResources\Model\RoleCollection[] getRoleList()
	 * @method \Bitrix\HumanResources\Model\RoleCollection getRoleCollection()
	 * @method \Bitrix\HumanResources\Model\RoleCollection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\NodeMember $object)
	 * @method bool has(\Bitrix\HumanResources\Model\NodeMember $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeMember getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeMember[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\NodeMember $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\NodeMemberCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\NodeMember current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\NodeMemberCollection merge(?\Bitrix\HumanResources\Model\NodeMemberCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_NodeMember_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\NodeMemberTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeMemberTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_NodeMember_Result exec()
	 * @method \Bitrix\HumanResources\Model\NodeMember fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeMemberCollection fetchCollection()
	 */
	class EO_NodeMember_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeMember fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeMemberCollection fetchCollection()
	 */
	class EO_NodeMember_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeMember createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\NodeMemberCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\NodeMember wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\NodeMemberCollection wakeUpCollection($rows)
	 */
	class EO_NodeMember_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeMemberRoleTable:humanresources\lib\Model\NodeMemberRoleTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeMemberRole
	 * @see \Bitrix\HumanResources\Model\NodeMemberRoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getMemberId()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole setMemberId(\int|\Bitrix\Main\DB\SqlExpression $memberId)
	 * @method bool hasMemberId()
	 * @method bool isMemberIdFilled()
	 * @method bool isMemberIdChanged()
	 * @method \int remindActualMemberId()
	 * @method \int requireMemberId()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole resetMemberId()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole unsetMemberId()
	 * @method \int fillMemberId()
	 * @method \int getRoleId()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole resetRoleId()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole resetCreatedBy()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole resetUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
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
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\NodeMemberRole wakeUp($data)
	 */
	class EO_NodeMemberRole {
		/* @var \Bitrix\HumanResources\Model\NodeMemberRoleTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeMemberRoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeMemberRoleCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getMemberIdList()
	 * @method \int[] fillMemberId()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\NodeMemberRole $object)
	 * @method bool has(\Bitrix\HumanResources\Model\NodeMemberRole $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\NodeMemberRole $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\NodeMemberRoleCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\NodeMemberRoleCollection merge(?\Bitrix\HumanResources\Model\NodeMemberRoleCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_NodeMemberRole_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\NodeMemberRoleTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeMemberRoleTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_NodeMemberRole_Result exec()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRoleCollection fetchCollection()
	 */
	class EO_NodeMemberRole_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRoleCollection fetchCollection()
	 */
	class EO_NodeMemberRole_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\NodeMemberRoleCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\NodeMemberRole wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\NodeMemberRoleCollection wakeUpCollection($rows)
	 */
	class EO_NodeMemberRole_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodePathTable:humanresources\lib\Model\NodePathTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * NodePath
	 * @see \Bitrix\HumanResources\Model\NodePathTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\NodePath setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getParentId()
	 * @method \Bitrix\HumanResources\Model\NodePath setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\HumanResources\Model\NodePath resetParentId()
	 * @method \Bitrix\HumanResources\Model\NodePath unsetParentId()
	 * @method \int fillParentId()
	 * @method \int getChildId()
	 * @method \Bitrix\HumanResources\Model\NodePath setChildId(\int|\Bitrix\Main\DB\SqlExpression $childId)
	 * @method bool hasChildId()
	 * @method bool isChildIdFilled()
	 * @method bool isChildIdChanged()
	 * @method \int remindActualChildId()
	 * @method \int requireChildId()
	 * @method \Bitrix\HumanResources\Model\NodePath resetChildId()
	 * @method \Bitrix\HumanResources\Model\NodePath unsetChildId()
	 * @method \int fillChildId()
	 * @method \int getDepth()
	 * @method \Bitrix\HumanResources\Model\NodePath setDepth(\int|\Bitrix\Main\DB\SqlExpression $depth)
	 * @method bool hasDepth()
	 * @method bool isDepthFilled()
	 * @method bool isDepthChanged()
	 * @method \int remindActualDepth()
	 * @method \int requireDepth()
	 * @method \Bitrix\HumanResources\Model\NodePath resetDepth()
	 * @method \Bitrix\HumanResources\Model\NodePath unsetDepth()
	 * @method \int fillDepth()
	 * @method \Bitrix\HumanResources\Model\Node getChildNode()
	 * @method \Bitrix\HumanResources\Model\Node remindActualChildNode()
	 * @method \Bitrix\HumanResources\Model\Node requireChildNode()
	 * @method \Bitrix\HumanResources\Model\NodePath setChildNode(\Bitrix\HumanResources\Model\Node $object)
	 * @method \Bitrix\HumanResources\Model\NodePath resetChildNode()
	 * @method \Bitrix\HumanResources\Model\NodePath unsetChildNode()
	 * @method bool hasChildNode()
	 * @method bool isChildNodeFilled()
	 * @method bool isChildNodeChanged()
	 * @method \Bitrix\HumanResources\Model\Node fillChildNode()
	 * @method \Bitrix\HumanResources\Model\Node getParentNode()
	 * @method \Bitrix\HumanResources\Model\Node remindActualParentNode()
	 * @method \Bitrix\HumanResources\Model\Node requireParentNode()
	 * @method \Bitrix\HumanResources\Model\NodePath setParentNode(\Bitrix\HumanResources\Model\Node $object)
	 * @method \Bitrix\HumanResources\Model\NodePath resetParentNode()
	 * @method \Bitrix\HumanResources\Model\NodePath unsetParentNode()
	 * @method bool hasParentNode()
	 * @method bool isParentNodeFilled()
	 * @method bool isParentNodeChanged()
	 * @method \Bitrix\HumanResources\Model\Node fillParentNode()
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
	 * @method \Bitrix\HumanResources\Model\NodePath set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\NodePath reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\NodePath unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\NodePath wakeUp($data)
	 */
	class EO_NodePath {
		/* @var \Bitrix\HumanResources\Model\NodePathTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodePathTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * NodePathCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \int[] getChildIdList()
	 * @method \int[] fillChildId()
	 * @method \int[] getDepthList()
	 * @method \int[] fillDepth()
	 * @method \Bitrix\HumanResources\Model\Node[] getChildNodeList()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection getChildNodeCollection()
	 * @method \Bitrix\HumanResources\Model\NodeCollection fillChildNode()
	 * @method \Bitrix\HumanResources\Model\Node[] getParentNodeList()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection getParentNodeCollection()
	 * @method \Bitrix\HumanResources\Model\NodeCollection fillParentNode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\NodePath $object)
	 * @method bool has(\Bitrix\HumanResources\Model\NodePath $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodePath getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodePath[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\NodePath $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\NodePathCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\NodePath current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\NodePathCollection merge(?\Bitrix\HumanResources\Model\NodePathCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_NodePath_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\NodePathTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodePathTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_NodePath_Result exec()
	 * @method \Bitrix\HumanResources\Model\NodePath fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection fetchCollection()
	 */
	class EO_NodePath_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodePath fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodePathCollection fetchCollection()
	 */
	class EO_NodePath_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodePath createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\NodePathCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\NodePath wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\NodePathCollection wakeUpCollection($rows)
	 */
	class EO_NodePath_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeRelationTable:humanresources\lib\Model\NodeRelationTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeRelation
	 * @see \Bitrix\HumanResources\Model\NodeRelationTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\NodeRelation setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeRelation setNodeId(\int|\Bitrix\Main\DB\SqlExpression $nodeId)
	 * @method bool hasNodeId()
	 * @method bool isNodeIdFilled()
	 * @method bool isNodeIdChanged()
	 * @method \int remindActualNodeId()
	 * @method \int requireNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeRelation resetNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeRelation unsetNodeId()
	 * @method \int fillNodeId()
	 * @method \int getEntityId()
	 * @method \Bitrix\HumanResources\Model\NodeRelation setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\HumanResources\Model\NodeRelation resetEntityId()
	 * @method \Bitrix\HumanResources\Model\NodeRelation unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getEntityType()
	 * @method \Bitrix\HumanResources\Model\NodeRelation setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\HumanResources\Model\NodeRelation resetEntityType()
	 * @method \Bitrix\HumanResources\Model\NodeRelation unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \boolean getWithChildNodes()
	 * @method \Bitrix\HumanResources\Model\NodeRelation setWithChildNodes(\boolean|\Bitrix\Main\DB\SqlExpression $withChildNodes)
	 * @method bool hasWithChildNodes()
	 * @method bool isWithChildNodesFilled()
	 * @method bool isWithChildNodesChanged()
	 * @method \boolean remindActualWithChildNodes()
	 * @method \boolean requireWithChildNodes()
	 * @method \Bitrix\HumanResources\Model\NodeRelation resetWithChildNodes()
	 * @method \Bitrix\HumanResources\Model\NodeRelation unsetWithChildNodes()
	 * @method \boolean fillWithChildNodes()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\HumanResources\Model\NodeRelation setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\HumanResources\Model\NodeRelation resetCreatedBy()
	 * @method \Bitrix\HumanResources\Model\NodeRelation unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRelation setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRelation resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRelation unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRelation setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRelation resetUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRelation unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
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
	 * @method \Bitrix\HumanResources\Model\NodeRelation set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\NodeRelation reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\NodeRelation unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\NodeRelation wakeUp($data)
	 */
	class EO_NodeRelation {
		/* @var \Bitrix\HumanResources\Model\NodeRelationTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeRelationTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeRelationCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getNodeIdList()
	 * @method \int[] fillNodeId()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \boolean[] getWithChildNodesList()
	 * @method \boolean[] fillWithChildNodes()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\NodeRelation $object)
	 * @method bool has(\Bitrix\HumanResources\Model\NodeRelation $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeRelation getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeRelation[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\NodeRelation $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\NodeRelationCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\NodeRelation current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\NodeRelationCollection merge(?\Bitrix\HumanResources\Model\NodeRelationCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_NodeRelation_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\NodeRelationTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeRelationTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_NodeRelation_Result exec()
	 * @method \Bitrix\HumanResources\Model\NodeRelation fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeRelationCollection fetchCollection()
	 */
	class EO_NodeRelation_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeRelation fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeRelationCollection fetchCollection()
	 */
	class EO_NodeRelation_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeRelation createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\NodeRelationCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\NodeRelation wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\NodeRelationCollection wakeUpCollection($rows)
	 */
	class EO_NodeRelation_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeRoleTable:humanresources\lib\Model\NodeRoleTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeRole
	 * @see \Bitrix\HumanResources\Model\NodeRoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\NodeRole setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeRole setNodeId(\int|\Bitrix\Main\DB\SqlExpression $nodeId)
	 * @method bool hasNodeId()
	 * @method bool isNodeIdFilled()
	 * @method bool isNodeIdChanged()
	 * @method \int remindActualNodeId()
	 * @method \int requireNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeRole resetNodeId()
	 * @method \Bitrix\HumanResources\Model\NodeRole unsetNodeId()
	 * @method \int fillNodeId()
	 * @method \int getRoleId()
	 * @method \Bitrix\HumanResources\Model\NodeRole setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\HumanResources\Model\NodeRole resetRoleId()
	 * @method \Bitrix\HumanResources\Model\NodeRole unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\HumanResources\Model\NodeRole setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\HumanResources\Model\NodeRole resetCreatedBy()
	 * @method \Bitrix\HumanResources\Model\NodeRole unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRole setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRole resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRole unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRole setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRole resetUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\NodeRole unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
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
	 * @method \Bitrix\HumanResources\Model\NodeRole set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\NodeRole reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\NodeRole unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\NodeRole wakeUp($data)
	 */
	class EO_NodeRole {
		/* @var \Bitrix\HumanResources\Model\NodeRoleTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeRoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * NodeRoleCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getNodeIdList()
	 * @method \int[] fillNodeId()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\NodeRole $object)
	 * @method bool has(\Bitrix\HumanResources\Model\NodeRole $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeRole getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\NodeRole[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\NodeRole $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\NodeRoleCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\NodeRole current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\NodeRoleCollection merge(?\Bitrix\HumanResources\Model\NodeRoleCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_NodeRole_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\NodeRoleTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\NodeRoleTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_NodeRole_Result exec()
	 * @method \Bitrix\HumanResources\Model\NodeRole fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeRoleCollection fetchCollection()
	 */
	class EO_NodeRole_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeRole fetchObject()
	 * @method \Bitrix\HumanResources\Model\NodeRoleCollection fetchCollection()
	 */
	class EO_NodeRole_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\NodeRole createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\NodeRoleCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\NodeRole wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\NodeRoleCollection wakeUpCollection($rows)
	 */
	class EO_NodeRole_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\RoleTable:humanresources\lib\Model\RoleTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * Role
	 * @see \Bitrix\HumanResources\Model\RoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\Role setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getEntityType()
	 * @method \Bitrix\HumanResources\Model\Role setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\HumanResources\Model\Role resetEntityType()
	 * @method \Bitrix\HumanResources\Model\Role unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \string getName()
	 * @method \Bitrix\HumanResources\Model\Role setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\HumanResources\Model\Role resetName()
	 * @method \Bitrix\HumanResources\Model\Role unsetName()
	 * @method \string fillName()
	 * @method \int getPriority()
	 * @method \Bitrix\HumanResources\Model\Role setPriority(\int|\Bitrix\Main\DB\SqlExpression $priority)
	 * @method bool hasPriority()
	 * @method bool isPriorityFilled()
	 * @method bool isPriorityChanged()
	 * @method \int remindActualPriority()
	 * @method \int requirePriority()
	 * @method \Bitrix\HumanResources\Model\Role resetPriority()
	 * @method \Bitrix\HumanResources\Model\Role unsetPriority()
	 * @method \int fillPriority()
	 * @method \int getChildAffectionType()
	 * @method \Bitrix\HumanResources\Model\Role setChildAffectionType(\int|\Bitrix\Main\DB\SqlExpression $childAffectionType)
	 * @method bool hasChildAffectionType()
	 * @method bool isChildAffectionTypeFilled()
	 * @method bool isChildAffectionTypeChanged()
	 * @method \int remindActualChildAffectionType()
	 * @method \int requireChildAffectionType()
	 * @method \Bitrix\HumanResources\Model\Role resetChildAffectionType()
	 * @method \Bitrix\HumanResources\Model\Role unsetChildAffectionType()
	 * @method \int fillChildAffectionType()
	 * @method \string getXmlId()
	 * @method \Bitrix\HumanResources\Model\Role setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\HumanResources\Model\Role resetXmlId()
	 * @method \Bitrix\HumanResources\Model\Role unsetXmlId()
	 * @method \string fillXmlId()
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
	 * @method \Bitrix\HumanResources\Model\Role set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\Role reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\Role unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\Role wakeUp($data)
	 */
	class EO_Role {
		/* @var \Bitrix\HumanResources\Model\RoleTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\RoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * RoleCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getPriorityList()
	 * @method \int[] fillPriority()
	 * @method \int[] getChildAffectionTypeList()
	 * @method \int[] fillChildAffectionType()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\Role $object)
	 * @method bool has(\Bitrix\HumanResources\Model\Role $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Role getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Role[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\Role $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\RoleCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\Role current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\RoleCollection merge(?\Bitrix\HumanResources\Model\RoleCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Role_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\RoleTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\RoleTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Role_Result exec()
	 * @method \Bitrix\HumanResources\Model\Role fetchObject()
	 * @method \Bitrix\HumanResources\Model\RoleCollection fetchCollection()
	 */
	class EO_Role_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\Role fetchObject()
	 * @method \Bitrix\HumanResources\Model\RoleCollection fetchCollection()
	 */
	class EO_Role_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\Role createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\RoleCollection createCollection()
	 * @method \Bitrix\HumanResources\Model\Role wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\RoleCollection wakeUpCollection($rows)
	 */
	class EO_Role_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\StructureTable:humanresources\lib\Model\StructureTable.php */
namespace Bitrix\HumanResources\Model {
	/**
	 * Structure
	 * @see \Bitrix\HumanResources\Model\StructureTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\HumanResources\Model\Structure setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\HumanResources\Model\Structure setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\HumanResources\Model\Structure resetName()
	 * @method \Bitrix\HumanResources\Model\Structure unsetName()
	 * @method \string fillName()
	 * @method \string getType()
	 * @method \Bitrix\HumanResources\Model\Structure setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\HumanResources\Model\Structure resetType()
	 * @method \Bitrix\HumanResources\Model\Structure unsetType()
	 * @method \string fillType()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\HumanResources\Model\Structure setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\HumanResources\Model\Structure resetCreatedBy()
	 * @method \Bitrix\HumanResources\Model\Structure unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\HumanResources\Model\Structure setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\HumanResources\Model\Structure resetCreatedAt()
	 * @method \Bitrix\HumanResources\Model\Structure unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\Structure setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\Structure resetUpdatedAt()
	 * @method \Bitrix\HumanResources\Model\Structure unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
	 * @method \string getXmlId()
	 * @method \Bitrix\HumanResources\Model\Structure setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\HumanResources\Model\Structure resetXmlId()
	 * @method \Bitrix\HumanResources\Model\Structure unsetXmlId()
	 * @method \string fillXmlId()
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
	 * @method \Bitrix\HumanResources\Model\Structure set($fieldName, $value)
	 * @method \Bitrix\HumanResources\Model\Structure reset($fieldName)
	 * @method \Bitrix\HumanResources\Model\Structure unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\HumanResources\Model\Structure wakeUp($data)
	 */
	class EO_Structure {
		/* @var \Bitrix\HumanResources\Model\StructureTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\StructureTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * EO_Structure_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\HumanResources\Model\Structure $object)
	 * @method bool has(\Bitrix\HumanResources\Model\Structure $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Structure getByPrimary($primary)
	 * @method \Bitrix\HumanResources\Model\Structure[] getAll()
	 * @method bool remove(\Bitrix\HumanResources\Model\Structure $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\HumanResources\Model\EO_Structure_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\HumanResources\Model\Structure current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\HumanResources\Model\EO_Structure_Collection merge(?\Bitrix\HumanResources\Model\EO_Structure_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Structure_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\HumanResources\Model\StructureTable */
		static public $dataClass = '\Bitrix\HumanResources\Model\StructureTable';
	}
}
namespace Bitrix\HumanResources\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Structure_Result exec()
	 * @method \Bitrix\HumanResources\Model\Structure fetchObject()
	 * @method \Bitrix\HumanResources\Model\EO_Structure_Collection fetchCollection()
	 */
	class EO_Structure_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\HumanResources\Model\Structure fetchObject()
	 * @method \Bitrix\HumanResources\Model\EO_Structure_Collection fetchCollection()
	 */
	class EO_Structure_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\HumanResources\Model\Structure createObject($setDefaultValues = true)
	 * @method \Bitrix\HumanResources\Model\EO_Structure_Collection createCollection()
	 * @method \Bitrix\HumanResources\Model\Structure wakeUpObject($row)
	 * @method \Bitrix\HumanResources\Model\EO_Structure_Collection wakeUpCollection($rows)
	 */
	class EO_Structure_Entity extends \Bitrix\Main\ORM\Entity {}
}