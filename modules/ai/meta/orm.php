<?php

/* ORMENTITYANNOTATION:Bitrix\AI\ShareRole\Model\ShareTable:ai/lib/ShareRole/Model/ShareTable.php */
namespace Bitrix\AI\ShareRole\Model {
	/**
	 * EO_Share
	 * @see \Bitrix\AI\ShareRole\Model\ShareTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share resetRoleId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share resetAccessCode()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share resetDateCreate()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share resetCreatedBy()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share unsetCreatedBy()
	 * @method \int fillCreatedBy()
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
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share set($fieldName, $value)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share reset($fieldName)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\ShareRole\Model\EO_Share wakeUp($data)
	 */
	class EO_Share {
		/* @var \Bitrix\AI\ShareRole\Model\ShareTable */
		static public $dataClass = '\Bitrix\AI\ShareRole\Model\ShareTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\ShareRole\Model {
	/**
	 * EO_Share_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\ShareRole\Model\EO_Share $object)
	 * @method bool has(\Bitrix\AI\ShareRole\Model\EO_Share $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share getByPrimary($primary)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share[] getAll()
	 * @method bool remove(\Bitrix\AI\ShareRole\Model\EO_Share $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\ShareRole\Model\EO_Share_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share_Collection merge(?\Bitrix\AI\ShareRole\Model\EO_Share_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Share_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\ShareRole\Model\ShareTable */
		static public $dataClass = '\Bitrix\AI\ShareRole\Model\ShareTable';
	}
}
namespace Bitrix\AI\ShareRole\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Share_Result exec()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share fetchObject()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share_Collection fetchCollection()
	 */
	class EO_Share_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share fetchObject()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share_Collection fetchCollection()
	 */
	class EO_Share_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share_Collection createCollection()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share wakeUpObject($row)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share_Collection wakeUpCollection($rows)
	 */
	class EO_Share_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\ShareRole\Model\OwnerTable:ai/lib/ShareRole/Model/OwnerTable.php */
namespace Bitrix\AI\ShareRole\Model {
	/**
	 * EO_Owner
	 * @see \Bitrix\AI\ShareRole\Model\OwnerTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner resetUserId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getRoleId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner resetRoleId()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \boolean getIsDeleted()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner setIsDeleted(\boolean|\Bitrix\Main\DB\SqlExpression $isDeleted)
	 * @method bool hasIsDeleted()
	 * @method bool isIsDeletedFilled()
	 * @method bool isIsDeletedChanged()
	 * @method \boolean remindActualIsDeleted()
	 * @method \boolean requireIsDeleted()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner resetIsDeleted()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner unsetIsDeleted()
	 * @method \boolean fillIsDeleted()
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
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner set($fieldName, $value)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner reset($fieldName)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\ShareRole\Model\EO_Owner wakeUp($data)
	 */
	class EO_Owner {
		/* @var \Bitrix\AI\ShareRole\Model\OwnerTable */
		static public $dataClass = '\Bitrix\AI\ShareRole\Model\OwnerTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\ShareRole\Model {
	/**
	 * EO_Owner_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \boolean[] getIsDeletedList()
	 * @method \boolean[] fillIsDeleted()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\ShareRole\Model\EO_Owner $object)
	 * @method bool has(\Bitrix\AI\ShareRole\Model\EO_Owner $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner getByPrimary($primary)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner[] getAll()
	 * @method bool remove(\Bitrix\AI\ShareRole\Model\EO_Owner $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\ShareRole\Model\EO_Owner_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner_Collection merge(?\Bitrix\AI\ShareRole\Model\EO_Owner_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Owner_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\ShareRole\Model\OwnerTable */
		static public $dataClass = '\Bitrix\AI\ShareRole\Model\OwnerTable';
	}
}
namespace Bitrix\AI\ShareRole\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Owner_Result exec()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner fetchObject()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner_Collection fetchCollection()
	 */
	class EO_Owner_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner fetchObject()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner_Collection fetchCollection()
	 */
	class EO_Owner_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner_Collection createCollection()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner wakeUpObject($row)
	 * @method \Bitrix\AI\ShareRole\Model\EO_Owner_Collection wakeUpCollection($rows)
	 */
	class EO_Owner_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\RoleTranslateDescriptionTable:ai/lib/Model/RoleTranslateDescriptionTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_RoleTranslateDescription
	 * @see \Bitrix\AI\Model\RoleTranslateDescriptionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getRoleId()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription setRoleId(\string|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \string remindActualRoleId()
	 * @method \string requireRoleId()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription resetRoleId()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription unsetRoleId()
	 * @method \string fillRoleId()
	 * @method \string getLang()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription setLang(\string|\Bitrix\Main\DB\SqlExpression $lang)
	 * @method bool hasLang()
	 * @method bool isLangFilled()
	 * @method bool isLangChanged()
	 * @method \string remindActualLang()
	 * @method \string requireLang()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription resetLang()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription unsetLang()
	 * @method \string fillLang()
	 * @method \string getText()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription setText(\string|\Bitrix\Main\DB\SqlExpression $text)
	 * @method bool hasText()
	 * @method bool isTextFilled()
	 * @method bool isTextChanged()
	 * @method \string remindActualText()
	 * @method \string requireText()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription resetText()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription unsetText()
	 * @method \string fillText()
	 * @method \Bitrix\AI\Entity\Role getRole()
	 * @method \Bitrix\AI\Entity\Role remindActualRole()
	 * @method \Bitrix\AI\Entity\Role requireRole()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription setRole(\Bitrix\AI\Entity\Role $object)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription resetRole()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\AI\Entity\Role fillRole()
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
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_RoleTranslateDescription wakeUp($data)
	 */
	class EO_RoleTranslateDescription {
		/* @var \Bitrix\AI\Model\RoleTranslateDescriptionTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleTranslateDescriptionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_RoleTranslateDescription_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getRoleIdList()
	 * @method \string[] fillRoleId()
	 * @method \string[] getLangList()
	 * @method \string[] fillLang()
	 * @method \string[] getTextList()
	 * @method \string[] fillText()
	 * @method \Bitrix\AI\Entity\Role[] getRoleList()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection getRoleCollection()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_RoleTranslateDescription $object)
	 * @method bool has(\Bitrix\AI\Model\EO_RoleTranslateDescription $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_RoleTranslateDescription $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection merge(?\Bitrix\AI\Model\EO_RoleTranslateDescription_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_RoleTranslateDescription_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\RoleTranslateDescriptionTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleTranslateDescriptionTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RoleTranslateDescription_Result exec()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection fetchCollection()
	 */
	class EO_RoleTranslateDescription_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection fetchCollection()
	 */
	class EO_RoleTranslateDescription_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection wakeUpCollection($rows)
	 */
	class EO_RoleTranslateDescription_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\RoleDisplayRuleTable:ai/lib/Model/RoleDisplayRuleTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_RoleDisplayRule
	 * @see \Bitrix\AI\Model\RoleDisplayRuleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule resetRoleId()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getName()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule resetName()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule unsetName()
	 * @method \string fillName()
	 * @method \boolean getIsCheckInvert()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule setIsCheckInvert(\boolean|\Bitrix\Main\DB\SqlExpression $isCheckInvert)
	 * @method bool hasIsCheckInvert()
	 * @method bool isIsCheckInvertFilled()
	 * @method bool isIsCheckInvertChanged()
	 * @method \boolean remindActualIsCheckInvert()
	 * @method \boolean requireIsCheckInvert()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule resetIsCheckInvert()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule unsetIsCheckInvert()
	 * @method \boolean fillIsCheckInvert()
	 * @method \string getValue()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule resetValue()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule unsetValue()
	 * @method \string fillValue()
	 * @method \Bitrix\AI\Entity\Role getRole()
	 * @method \Bitrix\AI\Entity\Role remindActualRole()
	 * @method \Bitrix\AI\Entity\Role requireRole()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule setRole(\Bitrix\AI\Entity\Role $object)
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule resetRole()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\AI\Entity\Role fillRole()
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
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_RoleDisplayRule wakeUp($data)
	 */
	class EO_RoleDisplayRule {
		/* @var \Bitrix\AI\Model\RoleDisplayRuleTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleDisplayRuleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_RoleDisplayRule_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \boolean[] getIsCheckInvertList()
	 * @method \boolean[] fillIsCheckInvert()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \Bitrix\AI\Entity\Role[] getRoleList()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection getRoleCollection()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_RoleDisplayRule $object)
	 * @method bool has(\Bitrix\AI\Model\EO_RoleDisplayRule $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_RoleDisplayRule $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_RoleDisplayRule_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection merge(?\Bitrix\AI\Model\EO_RoleDisplayRule_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_RoleDisplayRule_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\RoleDisplayRuleTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleDisplayRuleTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RoleDisplayRule_Result exec()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection fetchCollection()
	 */
	class EO_RoleDisplayRule_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection fetchCollection()
	 */
	class EO_RoleDisplayRule_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection wakeUpCollection($rows)
	 */
	class EO_RoleDisplayRule_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\PromptTranslateNameTable:ai/lib/Model/PromptTranslateNameTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_PromptTranslateName
	 * @see \Bitrix\AI\Model\PromptTranslateNameTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getPromptId()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName setPromptId(\int|\Bitrix\Main\DB\SqlExpression $promptId)
	 * @method bool hasPromptId()
	 * @method bool isPromptIdFilled()
	 * @method bool isPromptIdChanged()
	 * @method \int remindActualPromptId()
	 * @method \int requirePromptId()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName resetPromptId()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName unsetPromptId()
	 * @method \int fillPromptId()
	 * @method \string getLang()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName setLang(\string|\Bitrix\Main\DB\SqlExpression $lang)
	 * @method bool hasLang()
	 * @method bool isLangFilled()
	 * @method bool isLangChanged()
	 * @method \string remindActualLang()
	 * @method \string requireLang()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName resetLang()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName unsetLang()
	 * @method \string fillLang()
	 * @method \string getText()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName setText(\string|\Bitrix\Main\DB\SqlExpression $text)
	 * @method bool hasText()
	 * @method bool isTextFilled()
	 * @method bool isTextChanged()
	 * @method \string remindActualText()
	 * @method \string requireText()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName resetText()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName unsetText()
	 * @method \string fillText()
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
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_PromptTranslateName wakeUp($data)
	 */
	class EO_PromptTranslateName {
		/* @var \Bitrix\AI\Model\PromptTranslateNameTable */
		static public $dataClass = '\Bitrix\AI\Model\PromptTranslateNameTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_PromptTranslateName_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getPromptIdList()
	 * @method \int[] fillPromptId()
	 * @method \string[] getLangList()
	 * @method \string[] fillLang()
	 * @method \string[] getTextList()
	 * @method \string[] fillText()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_PromptTranslateName $object)
	 * @method bool has(\Bitrix\AI\Model\EO_PromptTranslateName $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_PromptTranslateName $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_PromptTranslateName_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName_Collection merge(?\Bitrix\AI\Model\EO_PromptTranslateName_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_PromptTranslateName_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\PromptTranslateNameTable */
		static public $dataClass = '\Bitrix\AI\Model\PromptTranslateNameTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_PromptTranslateName_Result exec()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName fetchObject()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName_Collection fetchCollection()
	 */
	class EO_PromptTranslateName_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName fetchObject()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName_Collection fetchCollection()
	 */
	class EO_PromptTranslateName_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_PromptTranslateName_Collection wakeUpCollection($rows)
	 */
	class EO_PromptTranslateName_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\PromptTable:ai/lib/Model/PromptTable.php */
namespace Bitrix\AI\Model {
	/**
	 * Prompt
	 * @see \Bitrix\AI\Model\PromptTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Entity\Prompt setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getAppCode()
	 * @method \Bitrix\AI\Entity\Prompt setAppCode(\string|\Bitrix\Main\DB\SqlExpression $appCode)
	 * @method bool hasAppCode()
	 * @method bool isAppCodeFilled()
	 * @method bool isAppCodeChanged()
	 * @method \string remindActualAppCode()
	 * @method \string requireAppCode()
	 * @method \Bitrix\AI\Entity\Prompt resetAppCode()
	 * @method \Bitrix\AI\Entity\Prompt unsetAppCode()
	 * @method \string fillAppCode()
	 * @method \int getParentId()
	 * @method \Bitrix\AI\Entity\Prompt setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\AI\Entity\Prompt resetParentId()
	 * @method \Bitrix\AI\Entity\Prompt unsetParentId()
	 * @method \int fillParentId()
	 * @method array getCacheCategory()
	 * @method \Bitrix\AI\Entity\Prompt setCacheCategory(array|\Bitrix\Main\DB\SqlExpression $cacheCategory)
	 * @method bool hasCacheCategory()
	 * @method bool isCacheCategoryFilled()
	 * @method bool isCacheCategoryChanged()
	 * @method array remindActualCacheCategory()
	 * @method array requireCacheCategory()
	 * @method \Bitrix\AI\Entity\Prompt resetCacheCategory()
	 * @method \Bitrix\AI\Entity\Prompt unsetCacheCategory()
	 * @method array fillCacheCategory()
	 * @method \string getSection()
	 * @method \Bitrix\AI\Entity\Prompt setSection(\string|\Bitrix\Main\DB\SqlExpression $section)
	 * @method bool hasSection()
	 * @method bool isSectionFilled()
	 * @method bool isSectionChanged()
	 * @method \string remindActualSection()
	 * @method \string requireSection()
	 * @method \Bitrix\AI\Entity\Prompt resetSection()
	 * @method \Bitrix\AI\Entity\Prompt unsetSection()
	 * @method \string fillSection()
	 * @method \int getSort()
	 * @method \Bitrix\AI\Entity\Prompt setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\AI\Entity\Prompt resetSort()
	 * @method \Bitrix\AI\Entity\Prompt unsetSort()
	 * @method \int fillSort()
	 * @method \string getCode()
	 * @method \Bitrix\AI\Entity\Prompt setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\AI\Entity\Prompt resetCode()
	 * @method \Bitrix\AI\Entity\Prompt unsetCode()
	 * @method \string fillCode()
	 * @method \string getDefaultTitle()
	 * @method \Bitrix\AI\Entity\Prompt setDefaultTitle(\string|\Bitrix\Main\DB\SqlExpression $defaultTitle)
	 * @method bool hasDefaultTitle()
	 * @method bool isDefaultTitleFilled()
	 * @method bool isDefaultTitleChanged()
	 * @method \string remindActualDefaultTitle()
	 * @method \string requireDefaultTitle()
	 * @method \Bitrix\AI\Entity\Prompt resetDefaultTitle()
	 * @method \Bitrix\AI\Entity\Prompt unsetDefaultTitle()
	 * @method \string fillDefaultTitle()
	 * @method \string getType()
	 * @method \Bitrix\AI\Entity\Prompt setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\AI\Entity\Prompt resetType()
	 * @method \Bitrix\AI\Entity\Prompt unsetType()
	 * @method \string fillType()
	 * @method \string getIcon()
	 * @method \Bitrix\AI\Entity\Prompt setIcon(\string|\Bitrix\Main\DB\SqlExpression $icon)
	 * @method bool hasIcon()
	 * @method bool isIconFilled()
	 * @method bool isIconChanged()
	 * @method \string remindActualIcon()
	 * @method \string requireIcon()
	 * @method \Bitrix\AI\Entity\Prompt resetIcon()
	 * @method \Bitrix\AI\Entity\Prompt unsetIcon()
	 * @method \string fillIcon()
	 * @method \string getHash()
	 * @method \Bitrix\AI\Entity\Prompt setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\AI\Entity\Prompt resetHash()
	 * @method \Bitrix\AI\Entity\Prompt unsetHash()
	 * @method \string fillHash()
	 * @method \string getPrompt()
	 * @method \Bitrix\AI\Entity\Prompt setPrompt(\string|\Bitrix\Main\DB\SqlExpression $prompt)
	 * @method bool hasPrompt()
	 * @method bool isPromptFilled()
	 * @method bool isPromptChanged()
	 * @method \string remindActualPrompt()
	 * @method \string requirePrompt()
	 * @method \Bitrix\AI\Entity\Prompt resetPrompt()
	 * @method \Bitrix\AI\Entity\Prompt unsetPrompt()
	 * @method \string fillPrompt()
	 * @method array getTextTranslates()
	 * @method \Bitrix\AI\Entity\Prompt setTextTranslates(array|\Bitrix\Main\DB\SqlExpression $textTranslates)
	 * @method bool hasTextTranslates()
	 * @method bool isTextTranslatesFilled()
	 * @method bool isTextTranslatesChanged()
	 * @method array remindActualTextTranslates()
	 * @method array requireTextTranslates()
	 * @method \Bitrix\AI\Entity\Prompt resetTextTranslates()
	 * @method \Bitrix\AI\Entity\Prompt unsetTextTranslates()
	 * @method array fillTextTranslates()
	 * @method \string getIsSystem()
	 * @method \Bitrix\AI\Entity\Prompt setIsSystem(\string|\Bitrix\Main\DB\SqlExpression $isSystem)
	 * @method bool hasIsSystem()
	 * @method bool isIsSystemFilled()
	 * @method bool isIsSystemChanged()
	 * @method \string remindActualIsSystem()
	 * @method \string requireIsSystem()
	 * @method \Bitrix\AI\Entity\Prompt resetIsSystem()
	 * @method \Bitrix\AI\Entity\Prompt unsetIsSystem()
	 * @method \string fillIsSystem()
	 * @method \int getAuthorId()
	 * @method \Bitrix\AI\Entity\Prompt setAuthorId(\int|\Bitrix\Main\DB\SqlExpression $authorId)
	 * @method bool hasAuthorId()
	 * @method bool isAuthorIdFilled()
	 * @method bool isAuthorIdChanged()
	 * @method \int remindActualAuthorId()
	 * @method \int requireAuthorId()
	 * @method \Bitrix\AI\Entity\Prompt resetAuthorId()
	 * @method \Bitrix\AI\Entity\Prompt unsetAuthorId()
	 * @method \int fillAuthorId()
	 * @method \int getEditorId()
	 * @method \Bitrix\AI\Entity\Prompt setEditorId(\int|\Bitrix\Main\DB\SqlExpression $editorId)
	 * @method bool hasEditorId()
	 * @method bool isEditorIdFilled()
	 * @method bool isEditorIdChanged()
	 * @method \int remindActualEditorId()
	 * @method \int requireEditorId()
	 * @method \Bitrix\AI\Entity\Prompt resetEditorId()
	 * @method \Bitrix\AI\Entity\Prompt unsetEditorId()
	 * @method \int fillEditorId()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\AI\Entity\Prompt setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\AI\Entity\Prompt resetDateModify()
	 * @method \Bitrix\AI\Entity\Prompt unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\Entity\Prompt setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\Entity\Prompt resetDateCreate()
	 * @method \Bitrix\AI\Entity\Prompt unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method array getSettings()
	 * @method \Bitrix\AI\Entity\Prompt setSettings(array|\Bitrix\Main\DB\SqlExpression $settings)
	 * @method bool hasSettings()
	 * @method bool isSettingsFilled()
	 * @method bool isSettingsChanged()
	 * @method array remindActualSettings()
	 * @method array requireSettings()
	 * @method \Bitrix\AI\Entity\Prompt resetSettings()
	 * @method \Bitrix\AI\Entity\Prompt unsetSettings()
	 * @method array fillSettings()
	 * @method \string getWorkWithResult()
	 * @method \Bitrix\AI\Entity\Prompt setWorkWithResult(\string|\Bitrix\Main\DB\SqlExpression $workWithResult)
	 * @method bool hasWorkWithResult()
	 * @method bool isWorkWithResultFilled()
	 * @method bool isWorkWithResultChanged()
	 * @method \string remindActualWorkWithResult()
	 * @method \string requireWorkWithResult()
	 * @method \Bitrix\AI\Entity\Prompt resetWorkWithResult()
	 * @method \Bitrix\AI\Entity\Prompt unsetWorkWithResult()
	 * @method \string fillWorkWithResult()
	 * @method \boolean getIsNew()
	 * @method \Bitrix\AI\Entity\Prompt setIsNew(\boolean|\Bitrix\Main\DB\SqlExpression $isNew)
	 * @method bool hasIsNew()
	 * @method bool isIsNewFilled()
	 * @method bool isIsNewChanged()
	 * @method \boolean remindActualIsNew()
	 * @method \boolean requireIsNew()
	 * @method \Bitrix\AI\Entity\Prompt resetIsNew()
	 * @method \Bitrix\AI\Entity\Prompt unsetIsNew()
	 * @method \boolean fillIsNew()
	 * @method \boolean getIsActive()
	 * @method \Bitrix\AI\Entity\Prompt setIsActive(\boolean|\Bitrix\Main\DB\SqlExpression $isActive)
	 * @method bool hasIsActive()
	 * @method bool isIsActiveFilled()
	 * @method bool isIsActiveChanged()
	 * @method \boolean remindActualIsActive()
	 * @method \boolean requireIsActive()
	 * @method \Bitrix\AI\Entity\Prompt resetIsActive()
	 * @method \Bitrix\AI\Entity\Prompt unsetIsActive()
	 * @method \boolean fillIsActive()
	 * @method \Bitrix\AI\Model\EO_Role_Collection getRoles()
	 * @method \Bitrix\AI\Model\EO_Role_Collection requireRoles()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fillRoles()
	 * @method bool hasRoles()
	 * @method bool isRolesFilled()
	 * @method bool isRolesChanged()
	 * @method void addToRoles(\Bitrix\AI\Entity\Role $role)
	 * @method void removeFromRoles(\Bitrix\AI\Entity\Role $role)
	 * @method void removeAllRoles()
	 * @method \Bitrix\AI\Entity\Prompt resetRoles()
	 * @method \Bitrix\AI\Entity\Prompt unsetRoles()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection getRules()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection requireRules()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection fillRules()
	 * @method bool hasRules()
	 * @method bool isRulesFilled()
	 * @method bool isRulesChanged()
	 * @method void addToRules(\Bitrix\AI\Model\EO_PromptDisplayRule $promptDisplayRule)
	 * @method void removeFromRules(\Bitrix\AI\Model\EO_PromptDisplayRule $promptDisplayRule)
	 * @method void removeAllRules()
	 * @method \Bitrix\AI\Entity\Prompt resetRules()
	 * @method \Bitrix\AI\Entity\Prompt unsetRules()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share getPromptShares()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share remindActualPromptShares()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share requirePromptShares()
	 * @method \Bitrix\AI\Entity\Prompt setPromptShares(\Bitrix\AI\SharePrompt\Model\EO_Share $object)
	 * @method \Bitrix\AI\Entity\Prompt resetPromptShares()
	 * @method \Bitrix\AI\Entity\Prompt unsetPromptShares()
	 * @method bool hasPromptShares()
	 * @method bool isPromptSharesFilled()
	 * @method bool isPromptSharesChanged()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share fillPromptShares()
	 * @method \Bitrix\AI\Model\EO_PromptCategory getPromptCategories()
	 * @method \Bitrix\AI\Model\EO_PromptCategory remindActualPromptCategories()
	 * @method \Bitrix\AI\Model\EO_PromptCategory requirePromptCategories()
	 * @method \Bitrix\AI\Entity\Prompt setPromptCategories(\Bitrix\AI\Model\EO_PromptCategory $object)
	 * @method \Bitrix\AI\Entity\Prompt resetPromptCategories()
	 * @method \Bitrix\AI\Entity\Prompt unsetPromptCategories()
	 * @method bool hasPromptCategories()
	 * @method bool isPromptCategoriesFilled()
	 * @method bool isPromptCategoriesChanged()
	 * @method \Bitrix\AI\Model\EO_PromptCategory fillPromptCategories()
	 * @method \Bitrix\Main\EO_User getUserEditor()
	 * @method \Bitrix\Main\EO_User remindActualUserEditor()
	 * @method \Bitrix\Main\EO_User requireUserEditor()
	 * @method \Bitrix\AI\Entity\Prompt setUserEditor(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\AI\Entity\Prompt resetUserEditor()
	 * @method \Bitrix\AI\Entity\Prompt unsetUserEditor()
	 * @method bool hasUserEditor()
	 * @method bool isUserEditorFilled()
	 * @method bool isUserEditorChanged()
	 * @method \Bitrix\Main\EO_User fillUserEditor()
	 * @method \Bitrix\Main\EO_User getUserAuthor()
	 * @method \Bitrix\Main\EO_User remindActualUserAuthor()
	 * @method \Bitrix\Main\EO_User requireUserAuthor()
	 * @method \Bitrix\AI\Entity\Prompt setUserAuthor(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\AI\Entity\Prompt resetUserAuthor()
	 * @method \Bitrix\AI\Entity\Prompt unsetUserAuthor()
	 * @method bool hasUserAuthor()
	 * @method bool isUserAuthorFilled()
	 * @method bool isUserAuthorChanged()
	 * @method \Bitrix\Main\EO_User fillUserAuthor()
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
	 * @method \Bitrix\AI\Entity\Prompt set($fieldName, $value)
	 * @method \Bitrix\AI\Entity\Prompt reset($fieldName)
	 * @method \Bitrix\AI\Entity\Prompt unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Entity\Prompt wakeUp($data)
	 */
	class EO_Prompt {
		/* @var \Bitrix\AI\Model\PromptTable */
		static public $dataClass = '\Bitrix\AI\Model\PromptTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_Prompt_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getAppCodeList()
	 * @method \string[] fillAppCode()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method array[] getCacheCategoryList()
	 * @method array[] fillCacheCategory()
	 * @method \string[] getSectionList()
	 * @method \string[] fillSection()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getDefaultTitleList()
	 * @method \string[] fillDefaultTitle()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getIconList()
	 * @method \string[] fillIcon()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \string[] getPromptList()
	 * @method \string[] fillPrompt()
	 * @method array[] getTextTranslatesList()
	 * @method array[] fillTextTranslates()
	 * @method \string[] getIsSystemList()
	 * @method \string[] fillIsSystem()
	 * @method \int[] getAuthorIdList()
	 * @method \int[] fillAuthorId()
	 * @method \int[] getEditorIdList()
	 * @method \int[] fillEditorId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method array[] getSettingsList()
	 * @method array[] fillSettings()
	 * @method \string[] getWorkWithResultList()
	 * @method \string[] fillWorkWithResult()
	 * @method \boolean[] getIsNewList()
	 * @method \boolean[] fillIsNew()
	 * @method \boolean[] getIsActiveList()
	 * @method \boolean[] fillIsActive()
	 * @method \Bitrix\AI\Model\EO_Role_Collection[] getRolesList()
	 * @method \Bitrix\AI\Model\EO_Role_Collection getRolesCollection()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fillRoles()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection[] getRulesList()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection getRulesCollection()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection fillRules()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share[] getPromptSharesList()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection getPromptSharesCollection()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share_Collection fillPromptShares()
	 * @method \Bitrix\AI\Model\EO_PromptCategory[] getPromptCategoriesList()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection getPromptCategoriesCollection()
	 * @method \Bitrix\AI\Model\EO_PromptCategory_Collection fillPromptCategories()
	 * @method \Bitrix\Main\EO_User[] getUserEditorList()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection getUserEditorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUserEditor()
	 * @method \Bitrix\Main\EO_User[] getUserAuthorList()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection getUserAuthorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUserAuthor()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Entity\Prompt $object)
	 * @method bool has(\Bitrix\AI\Entity\Prompt $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Entity\Prompt getByPrimary($primary)
	 * @method \Bitrix\AI\Entity\Prompt[] getAll()
	 * @method bool remove(\Bitrix\AI\Entity\Prompt $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_Prompt_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Entity\Prompt current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection merge(?\Bitrix\AI\Model\EO_Prompt_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Prompt_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\PromptTable */
		static public $dataClass = '\Bitrix\AI\Model\PromptTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Prompt_Result exec()
	 * @method \Bitrix\AI\Entity\Prompt fetchObject()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection fetchCollection()
	 */
	class EO_Prompt_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Entity\Prompt fetchObject()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection fetchCollection()
	 */
	class EO_Prompt_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Entity\Prompt createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection createCollection()
	 * @method \Bitrix\AI\Entity\Prompt wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection wakeUpCollection($rows)
	 */
	class EO_Prompt_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\PromptDisplayRuleTable:ai/lib/Model/PromptDisplayRuleTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_PromptDisplayRule
	 * @see \Bitrix\AI\Model\PromptDisplayRuleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getPromptId()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule setPromptId(\int|\Bitrix\Main\DB\SqlExpression $promptId)
	 * @method bool hasPromptId()
	 * @method bool isPromptIdFilled()
	 * @method bool isPromptIdChanged()
	 * @method \int remindActualPromptId()
	 * @method \int requirePromptId()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule resetPromptId()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule unsetPromptId()
	 * @method \int fillPromptId()
	 * @method \string getName()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule resetName()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule unsetName()
	 * @method \string fillName()
	 * @method \boolean getIsCheckInvert()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule setIsCheckInvert(\boolean|\Bitrix\Main\DB\SqlExpression $isCheckInvert)
	 * @method bool hasIsCheckInvert()
	 * @method bool isIsCheckInvertFilled()
	 * @method bool isIsCheckInvertChanged()
	 * @method \boolean remindActualIsCheckInvert()
	 * @method \boolean requireIsCheckInvert()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule resetIsCheckInvert()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule unsetIsCheckInvert()
	 * @method \boolean fillIsCheckInvert()
	 * @method \string getValue()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule resetValue()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule unsetValue()
	 * @method \string fillValue()
	 * @method \Bitrix\AI\Entity\Prompt getPrompt()
	 * @method \Bitrix\AI\Entity\Prompt remindActualPrompt()
	 * @method \Bitrix\AI\Entity\Prompt requirePrompt()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule setPrompt(\Bitrix\AI\Entity\Prompt $object)
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule resetPrompt()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule unsetPrompt()
	 * @method bool hasPrompt()
	 * @method bool isPromptFilled()
	 * @method bool isPromptChanged()
	 * @method \Bitrix\AI\Entity\Prompt fillPrompt()
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
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_PromptDisplayRule wakeUp($data)
	 */
	class EO_PromptDisplayRule {
		/* @var \Bitrix\AI\Model\PromptDisplayRuleTable */
		static public $dataClass = '\Bitrix\AI\Model\PromptDisplayRuleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_PromptDisplayRule_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getPromptIdList()
	 * @method \int[] fillPromptId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \boolean[] getIsCheckInvertList()
	 * @method \boolean[] fillIsCheckInvert()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \Bitrix\AI\Entity\Prompt[] getPromptList()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection getPromptCollection()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection fillPrompt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_PromptDisplayRule $object)
	 * @method bool has(\Bitrix\AI\Model\EO_PromptDisplayRule $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_PromptDisplayRule $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_PromptDisplayRule_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection merge(?\Bitrix\AI\Model\EO_PromptDisplayRule_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_PromptDisplayRule_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\PromptDisplayRuleTable */
		static public $dataClass = '\Bitrix\AI\Model\PromptDisplayRuleTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_PromptDisplayRule_Result exec()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule fetchObject()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection fetchCollection()
	 */
	class EO_PromptDisplayRule_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule fetchObject()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection fetchCollection()
	 */
	class EO_PromptDisplayRule_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_PromptDisplayRule_Collection wakeUpCollection($rows)
	 */
	class EO_PromptDisplayRule_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\QueueTable:ai/lib/Model/QueueTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_Queue
	 * @see \Bitrix\AI\Model\QueueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_Queue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getHash()
	 * @method \Bitrix\AI\Model\EO_Queue setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\AI\Model\EO_Queue resetHash()
	 * @method \Bitrix\AI\Model\EO_Queue unsetHash()
	 * @method \string fillHash()
	 * @method \string getEngineClass()
	 * @method \Bitrix\AI\Model\EO_Queue setEngineClass(\string|\Bitrix\Main\DB\SqlExpression $engineClass)
	 * @method bool hasEngineClass()
	 * @method bool isEngineClassFilled()
	 * @method bool isEngineClassChanged()
	 * @method \string remindActualEngineClass()
	 * @method \string requireEngineClass()
	 * @method \Bitrix\AI\Model\EO_Queue resetEngineClass()
	 * @method \Bitrix\AI\Model\EO_Queue unsetEngineClass()
	 * @method \string fillEngineClass()
	 * @method \string getEngineCode()
	 * @method \Bitrix\AI\Model\EO_Queue setEngineCode(\string|\Bitrix\Main\DB\SqlExpression $engineCode)
	 * @method bool hasEngineCode()
	 * @method bool isEngineCodeFilled()
	 * @method bool isEngineCodeChanged()
	 * @method \string remindActualEngineCode()
	 * @method \string requireEngineCode()
	 * @method \Bitrix\AI\Model\EO_Queue resetEngineCode()
	 * @method \Bitrix\AI\Model\EO_Queue unsetEngineCode()
	 * @method \string fillEngineCode()
	 * @method array getEngineCustomSettings()
	 * @method \Bitrix\AI\Model\EO_Queue setEngineCustomSettings(array|\Bitrix\Main\DB\SqlExpression $engineCustomSettings)
	 * @method bool hasEngineCustomSettings()
	 * @method bool isEngineCustomSettingsFilled()
	 * @method bool isEngineCustomSettingsChanged()
	 * @method array remindActualEngineCustomSettings()
	 * @method array requireEngineCustomSettings()
	 * @method \Bitrix\AI\Model\EO_Queue resetEngineCustomSettings()
	 * @method \Bitrix\AI\Model\EO_Queue unsetEngineCustomSettings()
	 * @method array fillEngineCustomSettings()
	 * @method \string getPayloadClass()
	 * @method \Bitrix\AI\Model\EO_Queue setPayloadClass(\string|\Bitrix\Main\DB\SqlExpression $payloadClass)
	 * @method bool hasPayloadClass()
	 * @method bool isPayloadClassFilled()
	 * @method bool isPayloadClassChanged()
	 * @method \string remindActualPayloadClass()
	 * @method \string requirePayloadClass()
	 * @method \Bitrix\AI\Model\EO_Queue resetPayloadClass()
	 * @method \Bitrix\AI\Model\EO_Queue unsetPayloadClass()
	 * @method \string fillPayloadClass()
	 * @method \string getPayload()
	 * @method \Bitrix\AI\Model\EO_Queue setPayload(\string|\Bitrix\Main\DB\SqlExpression $payload)
	 * @method bool hasPayload()
	 * @method bool isPayloadFilled()
	 * @method bool isPayloadChanged()
	 * @method \string remindActualPayload()
	 * @method \string requirePayload()
	 * @method \Bitrix\AI\Model\EO_Queue resetPayload()
	 * @method \Bitrix\AI\Model\EO_Queue unsetPayload()
	 * @method \string fillPayload()
	 * @method \string getContext()
	 * @method \Bitrix\AI\Model\EO_Queue setContext(\string|\Bitrix\Main\DB\SqlExpression $context)
	 * @method bool hasContext()
	 * @method bool isContextFilled()
	 * @method bool isContextChanged()
	 * @method \string remindActualContext()
	 * @method \string requireContext()
	 * @method \Bitrix\AI\Model\EO_Queue resetContext()
	 * @method \Bitrix\AI\Model\EO_Queue unsetContext()
	 * @method \string fillContext()
	 * @method array getParameters()
	 * @method \Bitrix\AI\Model\EO_Queue setParameters(array|\Bitrix\Main\DB\SqlExpression $parameters)
	 * @method bool hasParameters()
	 * @method bool isParametersFilled()
	 * @method bool isParametersChanged()
	 * @method array remindActualParameters()
	 * @method array requireParameters()
	 * @method \Bitrix\AI\Model\EO_Queue resetParameters()
	 * @method \Bitrix\AI\Model\EO_Queue unsetParameters()
	 * @method array fillParameters()
	 * @method \string getHistoryWrite()
	 * @method \Bitrix\AI\Model\EO_Queue setHistoryWrite(\string|\Bitrix\Main\DB\SqlExpression $historyWrite)
	 * @method bool hasHistoryWrite()
	 * @method bool isHistoryWriteFilled()
	 * @method bool isHistoryWriteChanged()
	 * @method \string remindActualHistoryWrite()
	 * @method \string requireHistoryWrite()
	 * @method \Bitrix\AI\Model\EO_Queue resetHistoryWrite()
	 * @method \Bitrix\AI\Model\EO_Queue unsetHistoryWrite()
	 * @method \string fillHistoryWrite()
	 * @method \int getHistoryGroupId()
	 * @method \Bitrix\AI\Model\EO_Queue setHistoryGroupId(\int|\Bitrix\Main\DB\SqlExpression $historyGroupId)
	 * @method bool hasHistoryGroupId()
	 * @method bool isHistoryGroupIdFilled()
	 * @method bool isHistoryGroupIdChanged()
	 * @method \int remindActualHistoryGroupId()
	 * @method \int requireHistoryGroupId()
	 * @method \Bitrix\AI\Model\EO_Queue resetHistoryGroupId()
	 * @method \Bitrix\AI\Model\EO_Queue unsetHistoryGroupId()
	 * @method \int fillHistoryGroupId()
	 * @method \string getCacheHash()
	 * @method \Bitrix\AI\Model\EO_Queue setCacheHash(\string|\Bitrix\Main\DB\SqlExpression $cacheHash)
	 * @method bool hasCacheHash()
	 * @method bool isCacheHashFilled()
	 * @method bool isCacheHashChanged()
	 * @method \string remindActualCacheHash()
	 * @method \string requireCacheHash()
	 * @method \Bitrix\AI\Model\EO_Queue resetCacheHash()
	 * @method \Bitrix\AI\Model\EO_Queue unsetCacheHash()
	 * @method \string fillCacheHash()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\Model\EO_Queue setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\Model\EO_Queue resetDateCreate()
	 * @method \Bitrix\AI\Model\EO_Queue unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
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
	 * @method \Bitrix\AI\Model\EO_Queue set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_Queue reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_Queue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_Queue wakeUp($data)
	 */
	class EO_Queue {
		/* @var \Bitrix\AI\Model\QueueTable */
		static public $dataClass = '\Bitrix\AI\Model\QueueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_Queue_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \string[] getEngineClassList()
	 * @method \string[] fillEngineClass()
	 * @method \string[] getEngineCodeList()
	 * @method \string[] fillEngineCode()
	 * @method array[] getEngineCustomSettingsList()
	 * @method array[] fillEngineCustomSettings()
	 * @method \string[] getPayloadClassList()
	 * @method \string[] fillPayloadClass()
	 * @method \string[] getPayloadList()
	 * @method \string[] fillPayload()
	 * @method \string[] getContextList()
	 * @method \string[] fillContext()
	 * @method array[] getParametersList()
	 * @method array[] fillParameters()
	 * @method \string[] getHistoryWriteList()
	 * @method \string[] fillHistoryWrite()
	 * @method \int[] getHistoryGroupIdList()
	 * @method \int[] fillHistoryGroupId()
	 * @method \string[] getCacheHashList()
	 * @method \string[] fillCacheHash()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_Queue $object)
	 * @method bool has(\Bitrix\AI\Model\EO_Queue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Queue getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Queue[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_Queue $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_Queue_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_Queue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_Queue_Collection merge(?\Bitrix\AI\Model\EO_Queue_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Queue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\QueueTable */
		static public $dataClass = '\Bitrix\AI\Model\QueueTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Queue_Result exec()
	 * @method \Bitrix\AI\Model\EO_Queue fetchObject()
	 * @method \Bitrix\AI\Model\EO_Queue_Collection fetchCollection()
	 */
	class EO_Queue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_Queue fetchObject()
	 * @method \Bitrix\AI\Model\EO_Queue_Collection fetchCollection()
	 */
	class EO_Queue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_Queue createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_Queue_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_Queue wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_Queue_Collection wakeUpCollection($rows)
	 */
	class EO_Queue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\RecentRoleTable:ai/lib/Model/RecentRoleTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_RecentRole
	 * @see \Bitrix\AI\Model\RecentRoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_RecentRole setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getRoleCode()
	 * @method \Bitrix\AI\Model\EO_RecentRole setRoleCode(\string|\Bitrix\Main\DB\SqlExpression $roleCode)
	 * @method bool hasRoleCode()
	 * @method bool isRoleCodeFilled()
	 * @method bool isRoleCodeChanged()
	 * @method \string remindActualRoleCode()
	 * @method \string requireRoleCode()
	 * @method \Bitrix\AI\Model\EO_RecentRole resetRoleCode()
	 * @method \Bitrix\AI\Model\EO_RecentRole unsetRoleCode()
	 * @method \string fillRoleCode()
	 * @method \Bitrix\AI\Entity\Role getRole()
	 * @method \Bitrix\AI\Entity\Role remindActualRole()
	 * @method \Bitrix\AI\Entity\Role requireRole()
	 * @method \Bitrix\AI\Model\EO_RecentRole setRole(\Bitrix\AI\Entity\Role $object)
	 * @method \Bitrix\AI\Model\EO_RecentRole resetRole()
	 * @method \Bitrix\AI\Model\EO_RecentRole unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\AI\Entity\Role fillRole()
	 * @method \int getUserId()
	 * @method \Bitrix\AI\Model\EO_RecentRole setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\AI\Model\EO_RecentRole resetUserId()
	 * @method \Bitrix\AI\Model\EO_RecentRole unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\Model\EO_RecentRole setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\Model\EO_RecentRole resetDateCreate()
	 * @method \Bitrix\AI\Model\EO_RecentRole unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateTouch()
	 * @method \Bitrix\AI\Model\EO_RecentRole setDateTouch(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateTouch)
	 * @method bool hasDateTouch()
	 * @method bool isDateTouchFilled()
	 * @method bool isDateTouchChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateTouch()
	 * @method \Bitrix\Main\Type\DateTime requireDateTouch()
	 * @method \Bitrix\AI\Model\EO_RecentRole resetDateTouch()
	 * @method \Bitrix\AI\Model\EO_RecentRole unsetDateTouch()
	 * @method \Bitrix\Main\Type\DateTime fillDateTouch()
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
	 * @method \Bitrix\AI\Model\EO_RecentRole set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_RecentRole reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_RecentRole unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_RecentRole wakeUp($data)
	 */
	class EO_RecentRole {
		/* @var \Bitrix\AI\Model\RecentRoleTable */
		static public $dataClass = '\Bitrix\AI\Model\RecentRoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_RecentRole_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getRoleCodeList()
	 * @method \string[] fillRoleCode()
	 * @method \Bitrix\AI\Entity\Role[] getRoleList()
	 * @method \Bitrix\AI\Model\EO_RecentRole_Collection getRoleCollection()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fillRole()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateTouchList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateTouch()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_RecentRole $object)
	 * @method bool has(\Bitrix\AI\Model\EO_RecentRole $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RecentRole getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RecentRole[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_RecentRole $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_RecentRole_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_RecentRole current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_RecentRole_Collection merge(?\Bitrix\AI\Model\EO_RecentRole_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_RecentRole_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\RecentRoleTable */
		static public $dataClass = '\Bitrix\AI\Model\RecentRoleTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RecentRole_Result exec()
	 * @method \Bitrix\AI\Model\EO_RecentRole fetchObject()
	 * @method \Bitrix\AI\Model\EO_RecentRole_Collection fetchCollection()
	 */
	class EO_RecentRole_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_RecentRole fetchObject()
	 * @method \Bitrix\AI\Model\EO_RecentRole_Collection fetchCollection()
	 */
	class EO_RecentRole_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_RecentRole createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_RecentRole_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_RecentRole wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_RecentRole_Collection wakeUpCollection($rows)
	 */
	class EO_RecentRole_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\UsageTable:ai/lib/Model/UsageTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_Usage
	 * @see \Bitrix\AI\Model\UsageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_Usage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\AI\Model\EO_Usage setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\AI\Model\EO_Usage resetUserId()
	 * @method \Bitrix\AI\Model\EO_Usage unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getUsagePeriod()
	 * @method \Bitrix\AI\Model\EO_Usage setUsagePeriod(\string|\Bitrix\Main\DB\SqlExpression $usagePeriod)
	 * @method bool hasUsagePeriod()
	 * @method bool isUsagePeriodFilled()
	 * @method bool isUsagePeriodChanged()
	 * @method \string remindActualUsagePeriod()
	 * @method \string requireUsagePeriod()
	 * @method \Bitrix\AI\Model\EO_Usage resetUsagePeriod()
	 * @method \Bitrix\AI\Model\EO_Usage unsetUsagePeriod()
	 * @method \string fillUsagePeriod()
	 * @method \int getUsageCount()
	 * @method \Bitrix\AI\Model\EO_Usage setUsageCount(\int|\Bitrix\Main\DB\SqlExpression $usageCount)
	 * @method bool hasUsageCount()
	 * @method bool isUsageCountFilled()
	 * @method bool isUsageCountChanged()
	 * @method \int remindActualUsageCount()
	 * @method \int requireUsageCount()
	 * @method \Bitrix\AI\Model\EO_Usage resetUsageCount()
	 * @method \Bitrix\AI\Model\EO_Usage unsetUsageCount()
	 * @method \int fillUsageCount()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\AI\Model\EO_Usage setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\AI\Model\EO_Usage resetDateModify()
	 * @method \Bitrix\AI\Model\EO_Usage unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
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
	 * @method \Bitrix\AI\Model\EO_Usage set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_Usage reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_Usage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_Usage wakeUp($data)
	 */
	class EO_Usage {
		/* @var \Bitrix\AI\Model\UsageTable */
		static public $dataClass = '\Bitrix\AI\Model\UsageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_Usage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getUsagePeriodList()
	 * @method \string[] fillUsagePeriod()
	 * @method \int[] getUsageCountList()
	 * @method \int[] fillUsageCount()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_Usage $object)
	 * @method bool has(\Bitrix\AI\Model\EO_Usage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Usage getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Usage[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_Usage $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_Usage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_Usage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_Usage_Collection merge(?\Bitrix\AI\Model\EO_Usage_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Usage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\UsageTable */
		static public $dataClass = '\Bitrix\AI\Model\UsageTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Usage_Result exec()
	 * @method \Bitrix\AI\Model\EO_Usage fetchObject()
	 * @method \Bitrix\AI\Model\EO_Usage_Collection fetchCollection()
	 */
	class EO_Usage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_Usage fetchObject()
	 * @method \Bitrix\AI\Model\EO_Usage_Collection fetchCollection()
	 */
	class EO_Usage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_Usage createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_Usage_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_Usage wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_Usage_Collection wakeUpCollection($rows)
	 */
	class EO_Usage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\ImageStylePromptTable:ai/lib/Model/ImageStylePromptTable.php */
namespace Bitrix\AI\Model {
	/**
	 * ImageStylePrompt
	 * @see \Bitrix\AI\Model\ImageStylePromptTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getCode()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt resetCode()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt unsetCode()
	 * @method \string fillCode()
	 * @method \string getHash()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt resetHash()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt unsetHash()
	 * @method \string fillHash()
	 * @method array getNameTranslates()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt setNameTranslates(array|\Bitrix\Main\DB\SqlExpression $nameTranslates)
	 * @method bool hasNameTranslates()
	 * @method bool isNameTranslatesFilled()
	 * @method bool isNameTranslatesChanged()
	 * @method array remindActualNameTranslates()
	 * @method array requireNameTranslates()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt resetNameTranslates()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt unsetNameTranslates()
	 * @method array fillNameTranslates()
	 * @method \string getPrompt()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt setPrompt(\string|\Bitrix\Main\DB\SqlExpression $prompt)
	 * @method bool hasPrompt()
	 * @method bool isPromptFilled()
	 * @method bool isPromptChanged()
	 * @method \string remindActualPrompt()
	 * @method \string requirePrompt()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt resetPrompt()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt unsetPrompt()
	 * @method \string fillPrompt()
	 * @method \string getPreview()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt setPreview(\string|\Bitrix\Main\DB\SqlExpression $preview)
	 * @method bool hasPreview()
	 * @method bool isPreviewFilled()
	 * @method bool isPreviewChanged()
	 * @method \string remindActualPreview()
	 * @method \string requirePreview()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt resetPreview()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt unsetPreview()
	 * @method \string fillPreview()
	 * @method \int getSort()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt resetSort()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt unsetSort()
	 * @method \int fillSort()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt resetDateModify()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
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
	 * @method \Bitrix\AI\Entity\ImageStylePrompt set($fieldName, $value)
	 * @method \Bitrix\AI\Entity\ImageStylePrompt reset($fieldName)
	 * @method \Bitrix\AI\Entity\ImageStylePrompt unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Entity\ImageStylePrompt wakeUp($data)
	 */
	class EO_ImageStylePrompt {
		/* @var \Bitrix\AI\Model\ImageStylePromptTable */
		static public $dataClass = '\Bitrix\AI\Model\ImageStylePromptTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_ImageStylePrompt_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method array[] getNameTranslatesList()
	 * @method array[] fillNameTranslates()
	 * @method \string[] getPromptList()
	 * @method \string[] fillPrompt()
	 * @method \string[] getPreviewList()
	 * @method \string[] fillPreview()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Entity\ImageStylePrompt $object)
	 * @method bool has(\Bitrix\AI\Entity\ImageStylePrompt $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Entity\ImageStylePrompt getByPrimary($primary)
	 * @method \Bitrix\AI\Entity\ImageStylePrompt[] getAll()
	 * @method bool remove(\Bitrix\AI\Entity\ImageStylePrompt $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_ImageStylePrompt_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Entity\ImageStylePrompt current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_ImageStylePrompt_Collection merge(?\Bitrix\AI\Model\EO_ImageStylePrompt_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ImageStylePrompt_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\ImageStylePromptTable */
		static public $dataClass = '\Bitrix\AI\Model\ImageStylePromptTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ImageStylePrompt_Result exec()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt fetchObject()
	 * @method \Bitrix\AI\Model\EO_ImageStylePrompt_Collection fetchCollection()
	 */
	class EO_ImageStylePrompt_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Entity\ImageStylePrompt fetchObject()
	 * @method \Bitrix\AI\Model\EO_ImageStylePrompt_Collection fetchCollection()
	 */
	class EO_ImageStylePrompt_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Entity\ImageStylePrompt createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_ImageStylePrompt_Collection createCollection()
	 * @method \Bitrix\AI\Entity\ImageStylePrompt wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_ImageStylePrompt_Collection wakeUpCollection($rows)
	 */
	class EO_ImageStylePrompt_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\RoleIndustryTable:ai/lib/Model/RoleIndustryTable.php */
namespace Bitrix\AI\Model {
	/**
	 * RoleIndustry
	 * @see \Bitrix\AI\Model\RoleIndustryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Entity\RoleIndustry setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getCode()
	 * @method \Bitrix\AI\Entity\RoleIndustry setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\AI\Entity\RoleIndustry resetCode()
	 * @method \Bitrix\AI\Entity\RoleIndustry unsetCode()
	 * @method \string fillCode()
	 * @method array getNameTranslates()
	 * @method \Bitrix\AI\Entity\RoleIndustry setNameTranslates(array|\Bitrix\Main\DB\SqlExpression $nameTranslates)
	 * @method bool hasNameTranslates()
	 * @method bool isNameTranslatesFilled()
	 * @method bool isNameTranslatesChanged()
	 * @method array remindActualNameTranslates()
	 * @method array requireNameTranslates()
	 * @method \Bitrix\AI\Entity\RoleIndustry resetNameTranslates()
	 * @method \Bitrix\AI\Entity\RoleIndustry unsetNameTranslates()
	 * @method array fillNameTranslates()
	 * @method \string getHash()
	 * @method \Bitrix\AI\Entity\RoleIndustry setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\AI\Entity\RoleIndustry resetHash()
	 * @method \Bitrix\AI\Entity\RoleIndustry unsetHash()
	 * @method \string fillHash()
	 * @method \Bitrix\AI\Model\EO_Role_Collection getRoles()
	 * @method \Bitrix\AI\Model\EO_Role_Collection requireRoles()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fillRoles()
	 * @method bool hasRoles()
	 * @method bool isRolesFilled()
	 * @method bool isRolesChanged()
	 * @method void addToRoles(\Bitrix\AI\Entity\Role $role)
	 * @method void removeFromRoles(\Bitrix\AI\Entity\Role $role)
	 * @method void removeAllRoles()
	 * @method \Bitrix\AI\Entity\RoleIndustry resetRoles()
	 * @method \Bitrix\AI\Entity\RoleIndustry unsetRoles()
	 * @method \boolean getIsNew()
	 * @method \Bitrix\AI\Entity\RoleIndustry setIsNew(\boolean|\Bitrix\Main\DB\SqlExpression $isNew)
	 * @method bool hasIsNew()
	 * @method bool isIsNewFilled()
	 * @method bool isIsNewChanged()
	 * @method \boolean remindActualIsNew()
	 * @method \boolean requireIsNew()
	 * @method \Bitrix\AI\Entity\RoleIndustry resetIsNew()
	 * @method \Bitrix\AI\Entity\RoleIndustry unsetIsNew()
	 * @method \boolean fillIsNew()
	 * @method \int getSort()
	 * @method \Bitrix\AI\Entity\RoleIndustry setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\AI\Entity\RoleIndustry resetSort()
	 * @method \Bitrix\AI\Entity\RoleIndustry unsetSort()
	 * @method \int fillSort()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\AI\Entity\RoleIndustry setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\AI\Entity\RoleIndustry resetDateModify()
	 * @method \Bitrix\AI\Entity\RoleIndustry unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
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
	 * @method \Bitrix\AI\Entity\RoleIndustry set($fieldName, $value)
	 * @method \Bitrix\AI\Entity\RoleIndustry reset($fieldName)
	 * @method \Bitrix\AI\Entity\RoleIndustry unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Entity\RoleIndustry wakeUp($data)
	 */
	class EO_RoleIndustry {
		/* @var \Bitrix\AI\Model\RoleIndustryTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleIndustryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_RoleIndustry_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method array[] getNameTranslatesList()
	 * @method array[] fillNameTranslates()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \Bitrix\AI\Model\EO_Role_Collection[] getRolesList()
	 * @method \Bitrix\AI\Model\EO_Role_Collection getRolesCollection()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fillRoles()
	 * @method \boolean[] getIsNewList()
	 * @method \boolean[] fillIsNew()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Entity\RoleIndustry $object)
	 * @method bool has(\Bitrix\AI\Entity\RoleIndustry $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Entity\RoleIndustry getByPrimary($primary)
	 * @method \Bitrix\AI\Entity\RoleIndustry[] getAll()
	 * @method bool remove(\Bitrix\AI\Entity\RoleIndustry $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_RoleIndustry_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Entity\RoleIndustry current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_RoleIndustry_Collection merge(?\Bitrix\AI\Model\EO_RoleIndustry_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_RoleIndustry_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\RoleIndustryTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleIndustryTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RoleIndustry_Result exec()
	 * @method \Bitrix\AI\Entity\RoleIndustry fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleIndustry_Collection fetchCollection()
	 */
	class EO_RoleIndustry_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Entity\RoleIndustry fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleIndustry_Collection fetchCollection()
	 */
	class EO_RoleIndustry_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Entity\RoleIndustry createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_RoleIndustry_Collection createCollection()
	 * @method \Bitrix\AI\Entity\RoleIndustry wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_RoleIndustry_Collection wakeUpCollection($rows)
	 */
	class EO_RoleIndustry_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\RoleTable:ai/lib/Model/RoleTable.php */
namespace Bitrix\AI\Model {
	/**
	 * Role
	 * @see \Bitrix\AI\Model\RoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Entity\Role setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getCode()
	 * @method \Bitrix\AI\Entity\Role setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\AI\Entity\Role resetCode()
	 * @method \Bitrix\AI\Entity\Role unsetCode()
	 * @method \string fillCode()
	 * @method array getNameTranslates()
	 * @method \Bitrix\AI\Entity\Role setNameTranslates(array|\Bitrix\Main\DB\SqlExpression $nameTranslates)
	 * @method bool hasNameTranslates()
	 * @method bool isNameTranslatesFilled()
	 * @method bool isNameTranslatesChanged()
	 * @method array remindActualNameTranslates()
	 * @method array requireNameTranslates()
	 * @method \Bitrix\AI\Entity\Role resetNameTranslates()
	 * @method \Bitrix\AI\Entity\Role unsetNameTranslates()
	 * @method array fillNameTranslates()
	 * @method array getDescriptionTranslates()
	 * @method \Bitrix\AI\Entity\Role setDescriptionTranslates(array|\Bitrix\Main\DB\SqlExpression $descriptionTranslates)
	 * @method bool hasDescriptionTranslates()
	 * @method bool isDescriptionTranslatesFilled()
	 * @method bool isDescriptionTranslatesChanged()
	 * @method array remindActualDescriptionTranslates()
	 * @method array requireDescriptionTranslates()
	 * @method \Bitrix\AI\Entity\Role resetDescriptionTranslates()
	 * @method \Bitrix\AI\Entity\Role unsetDescriptionTranslates()
	 * @method array fillDescriptionTranslates()
	 * @method \string getIndustryCode()
	 * @method \Bitrix\AI\Entity\Role setIndustryCode(\string|\Bitrix\Main\DB\SqlExpression $industryCode)
	 * @method bool hasIndustryCode()
	 * @method bool isIndustryCodeFilled()
	 * @method bool isIndustryCodeChanged()
	 * @method \string remindActualIndustryCode()
	 * @method \string requireIndustryCode()
	 * @method \Bitrix\AI\Entity\Role resetIndustryCode()
	 * @method \Bitrix\AI\Entity\Role unsetIndustryCode()
	 * @method \string fillIndustryCode()
	 * @method \string getHash()
	 * @method \Bitrix\AI\Entity\Role setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\AI\Entity\Role resetHash()
	 * @method \Bitrix\AI\Entity\Role unsetHash()
	 * @method \string fillHash()
	 * @method \int getAuthorId()
	 * @method \Bitrix\AI\Entity\Role setAuthorId(\int|\Bitrix\Main\DB\SqlExpression $authorId)
	 * @method bool hasAuthorId()
	 * @method bool isAuthorIdFilled()
	 * @method bool isAuthorIdChanged()
	 * @method \int remindActualAuthorId()
	 * @method \int requireAuthorId()
	 * @method \Bitrix\AI\Entity\Role resetAuthorId()
	 * @method \Bitrix\AI\Entity\Role unsetAuthorId()
	 * @method \int fillAuthorId()
	 * @method \int getEditorId()
	 * @method \Bitrix\AI\Entity\Role setEditorId(\int|\Bitrix\Main\DB\SqlExpression $editorId)
	 * @method bool hasEditorId()
	 * @method bool isEditorIdFilled()
	 * @method bool isEditorIdChanged()
	 * @method \int remindActualEditorId()
	 * @method \int requireEditorId()
	 * @method \Bitrix\AI\Entity\Role resetEditorId()
	 * @method \Bitrix\AI\Entity\Role unsetEditorId()
	 * @method \int fillEditorId()
	 * @method \string getInstruction()
	 * @method \Bitrix\AI\Entity\Role setInstruction(\string|\Bitrix\Main\DB\SqlExpression $instruction)
	 * @method bool hasInstruction()
	 * @method bool isInstructionFilled()
	 * @method bool isInstructionChanged()
	 * @method \string remindActualInstruction()
	 * @method \string requireInstruction()
	 * @method \Bitrix\AI\Entity\Role resetInstruction()
	 * @method \Bitrix\AI\Entity\Role unsetInstruction()
	 * @method \string fillInstruction()
	 * @method array getAvatar()
	 * @method \Bitrix\AI\Entity\Role setAvatar(array|\Bitrix\Main\DB\SqlExpression $avatar)
	 * @method bool hasAvatar()
	 * @method bool isAvatarFilled()
	 * @method bool isAvatarChanged()
	 * @method array remindActualAvatar()
	 * @method array requireAvatar()
	 * @method \Bitrix\AI\Entity\Role resetAvatar()
	 * @method \Bitrix\AI\Entity\Role unsetAvatar()
	 * @method array fillAvatar()
	 * @method \boolean getIsNew()
	 * @method \Bitrix\AI\Entity\Role setIsNew(\boolean|\Bitrix\Main\DB\SqlExpression $isNew)
	 * @method bool hasIsNew()
	 * @method bool isIsNewFilled()
	 * @method bool isIsNewChanged()
	 * @method \boolean remindActualIsNew()
	 * @method \boolean requireIsNew()
	 * @method \Bitrix\AI\Entity\Role resetIsNew()
	 * @method \Bitrix\AI\Entity\Role unsetIsNew()
	 * @method \boolean fillIsNew()
	 * @method \boolean getIsRecommended()
	 * @method \Bitrix\AI\Entity\Role setIsRecommended(\boolean|\Bitrix\Main\DB\SqlExpression $isRecommended)
	 * @method bool hasIsRecommended()
	 * @method bool isIsRecommendedFilled()
	 * @method bool isIsRecommendedChanged()
	 * @method \boolean remindActualIsRecommended()
	 * @method \boolean requireIsRecommended()
	 * @method \Bitrix\AI\Entity\Role resetIsRecommended()
	 * @method \Bitrix\AI\Entity\Role unsetIsRecommended()
	 * @method \boolean fillIsRecommended()
	 * @method \boolean getIsActive()
	 * @method \Bitrix\AI\Entity\Role setIsActive(\boolean|\Bitrix\Main\DB\SqlExpression $isActive)
	 * @method bool hasIsActive()
	 * @method bool isIsActiveFilled()
	 * @method bool isIsActiveChanged()
	 * @method \boolean remindActualIsActive()
	 * @method \boolean requireIsActive()
	 * @method \Bitrix\AI\Entity\Role resetIsActive()
	 * @method \Bitrix\AI\Entity\Role unsetIsActive()
	 * @method \boolean fillIsActive()
	 * @method \boolean getIsSystem()
	 * @method \Bitrix\AI\Entity\Role setIsSystem(\boolean|\Bitrix\Main\DB\SqlExpression $isSystem)
	 * @method bool hasIsSystem()
	 * @method bool isIsSystemFilled()
	 * @method bool isIsSystemChanged()
	 * @method \boolean remindActualIsSystem()
	 * @method \boolean requireIsSystem()
	 * @method \Bitrix\AI\Entity\Role resetIsSystem()
	 * @method \Bitrix\AI\Entity\Role unsetIsSystem()
	 * @method \boolean fillIsSystem()
	 * @method \int getSort()
	 * @method \Bitrix\AI\Entity\Role setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\AI\Entity\Role resetSort()
	 * @method \Bitrix\AI\Entity\Role unsetSort()
	 * @method \int fillSort()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\Entity\Role setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\Entity\Role resetDateCreate()
	 * @method \Bitrix\AI\Entity\Role unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\AI\Entity\Role setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\AI\Entity\Role resetDateModify()
	 * @method \Bitrix\AI\Entity\Role unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
	 * @method \string getDefaultName()
	 * @method \Bitrix\AI\Entity\Role setDefaultName(\string|\Bitrix\Main\DB\SqlExpression $defaultName)
	 * @method bool hasDefaultName()
	 * @method bool isDefaultNameFilled()
	 * @method bool isDefaultNameChanged()
	 * @method \string remindActualDefaultName()
	 * @method \string requireDefaultName()
	 * @method \Bitrix\AI\Entity\Role resetDefaultName()
	 * @method \Bitrix\AI\Entity\Role unsetDefaultName()
	 * @method \string fillDefaultName()
	 * @method \string getDefaultDescription()
	 * @method \Bitrix\AI\Entity\Role setDefaultDescription(\string|\Bitrix\Main\DB\SqlExpression $defaultDescription)
	 * @method bool hasDefaultDescription()
	 * @method bool isDefaultDescriptionFilled()
	 * @method bool isDefaultDescriptionChanged()
	 * @method \string remindActualDefaultDescription()
	 * @method \string requireDefaultDescription()
	 * @method \Bitrix\AI\Entity\Role resetDefaultDescription()
	 * @method \Bitrix\AI\Entity\Role unsetDefaultDescription()
	 * @method \string fillDefaultDescription()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection getPrompts()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection requirePrompts()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection fillPrompts()
	 * @method bool hasPrompts()
	 * @method bool isPromptsFilled()
	 * @method bool isPromptsChanged()
	 * @method void addToPrompts(\Bitrix\AI\Entity\Prompt $prompt)
	 * @method void removeFromPrompts(\Bitrix\AI\Entity\Prompt $prompt)
	 * @method void removeAllPrompts()
	 * @method \Bitrix\AI\Entity\Role resetPrompts()
	 * @method \Bitrix\AI\Entity\Role unsetPrompts()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection getRules()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection requireRules()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection fillRules()
	 * @method bool hasRules()
	 * @method bool isRulesFilled()
	 * @method bool isRulesChanged()
	 * @method void addToRules(\Bitrix\AI\Model\EO_RoleDisplayRule $roleDisplayRule)
	 * @method void removeFromRules(\Bitrix\AI\Model\EO_RoleDisplayRule $roleDisplayRule)
	 * @method void removeAllRules()
	 * @method \Bitrix\AI\Entity\Role resetRules()
	 * @method \Bitrix\AI\Entity\Role unsetRules()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection getNames()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection requireNames()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection fillNames()
	 * @method bool hasNames()
	 * @method bool isNamesFilled()
	 * @method bool isNamesChanged()
	 * @method void addToNames(\Bitrix\AI\Model\EO_RoleTranslateName $roleTranslateName)
	 * @method void removeFromNames(\Bitrix\AI\Model\EO_RoleTranslateName $roleTranslateName)
	 * @method void removeAllNames()
	 * @method \Bitrix\AI\Entity\Role resetNames()
	 * @method \Bitrix\AI\Entity\Role unsetNames()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection getDescriptions()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection requireDescriptions()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection fillDescriptions()
	 * @method bool hasDescriptions()
	 * @method bool isDescriptionsFilled()
	 * @method bool isDescriptionsChanged()
	 * @method void addToDescriptions(\Bitrix\AI\Model\EO_RoleTranslateDescription $roleTranslateDescription)
	 * @method void removeFromDescriptions(\Bitrix\AI\Model\EO_RoleTranslateDescription $roleTranslateDescription)
	 * @method void removeAllDescriptions()
	 * @method \Bitrix\AI\Entity\Role resetDescriptions()
	 * @method \Bitrix\AI\Entity\Role unsetDescriptions()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share getRoleShares()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share remindActualRoleShares()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share requireRoleShares()
	 * @method \Bitrix\AI\Entity\Role setRoleShares(\Bitrix\AI\ShareRole\Model\EO_Share $object)
	 * @method \Bitrix\AI\Entity\Role resetRoleShares()
	 * @method \Bitrix\AI\Entity\Role unsetRoleShares()
	 * @method bool hasRoleShares()
	 * @method bool isRoleSharesFilled()
	 * @method bool isRoleSharesChanged()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share fillRoleShares()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite getRoleFavorites()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite remindActualRoleFavorites()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite requireRoleFavorites()
	 * @method \Bitrix\AI\Entity\Role setRoleFavorites(\Bitrix\AI\Model\EO_RoleFavorite $object)
	 * @method \Bitrix\AI\Entity\Role resetRoleFavorites()
	 * @method \Bitrix\AI\Entity\Role unsetRoleFavorites()
	 * @method bool hasRoleFavorites()
	 * @method bool isRoleFavoritesFilled()
	 * @method bool isRoleFavoritesChanged()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite fillRoleFavorites()
	 * @method \Bitrix\Main\EO_User getUserEditor()
	 * @method \Bitrix\Main\EO_User remindActualUserEditor()
	 * @method \Bitrix\Main\EO_User requireUserEditor()
	 * @method \Bitrix\AI\Entity\Role setUserEditor(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\AI\Entity\Role resetUserEditor()
	 * @method \Bitrix\AI\Entity\Role unsetUserEditor()
	 * @method bool hasUserEditor()
	 * @method bool isUserEditorFilled()
	 * @method bool isUserEditorChanged()
	 * @method \Bitrix\Main\EO_User fillUserEditor()
	 * @method \Bitrix\Main\EO_User getUserAuthor()
	 * @method \Bitrix\Main\EO_User remindActualUserAuthor()
	 * @method \Bitrix\Main\EO_User requireUserAuthor()
	 * @method \Bitrix\AI\Entity\Role setUserAuthor(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\AI\Entity\Role resetUserAuthor()
	 * @method \Bitrix\AI\Entity\Role unsetUserAuthor()
	 * @method bool hasUserAuthor()
	 * @method bool isUserAuthorFilled()
	 * @method bool isUserAuthorChanged()
	 * @method \Bitrix\Main\EO_User fillUserAuthor()
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
	 * @method \Bitrix\AI\Entity\Role set($fieldName, $value)
	 * @method \Bitrix\AI\Entity\Role reset($fieldName)
	 * @method \Bitrix\AI\Entity\Role unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Entity\Role wakeUp($data)
	 */
	class EO_Role {
		/* @var \Bitrix\AI\Model\RoleTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_Role_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method array[] getNameTranslatesList()
	 * @method array[] fillNameTranslates()
	 * @method array[] getDescriptionTranslatesList()
	 * @method array[] fillDescriptionTranslates()
	 * @method \string[] getIndustryCodeList()
	 * @method \string[] fillIndustryCode()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \int[] getAuthorIdList()
	 * @method \int[] fillAuthorId()
	 * @method \int[] getEditorIdList()
	 * @method \int[] fillEditorId()
	 * @method \string[] getInstructionList()
	 * @method \string[] fillInstruction()
	 * @method array[] getAvatarList()
	 * @method array[] fillAvatar()
	 * @method \boolean[] getIsNewList()
	 * @method \boolean[] fillIsNew()
	 * @method \boolean[] getIsRecommendedList()
	 * @method \boolean[] fillIsRecommended()
	 * @method \boolean[] getIsActiveList()
	 * @method \boolean[] fillIsActive()
	 * @method \boolean[] getIsSystemList()
	 * @method \boolean[] fillIsSystem()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 * @method \string[] getDefaultNameList()
	 * @method \string[] fillDefaultName()
	 * @method \string[] getDefaultDescriptionList()
	 * @method \string[] fillDefaultDescription()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection[] getPromptsList()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection getPromptsCollection()
	 * @method \Bitrix\AI\Model\EO_Prompt_Collection fillPrompts()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection[] getRulesList()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection getRulesCollection()
	 * @method \Bitrix\AI\Model\EO_RoleDisplayRule_Collection fillRules()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection[] getNamesList()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection getNamesCollection()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection fillNames()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection[] getDescriptionsList()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection getDescriptionsCollection()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection fillDescriptions()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share[] getRoleSharesList()
	 * @method \Bitrix\AI\Model\EO_Role_Collection getRoleSharesCollection()
	 * @method \Bitrix\AI\ShareRole\Model\EO_Share_Collection fillRoleShares()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite[] getRoleFavoritesList()
	 * @method \Bitrix\AI\Model\EO_Role_Collection getRoleFavoritesCollection()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite_Collection fillRoleFavorites()
	 * @method \Bitrix\Main\EO_User[] getUserEditorList()
	 * @method \Bitrix\AI\Model\EO_Role_Collection getUserEditorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUserEditor()
	 * @method \Bitrix\Main\EO_User[] getUserAuthorList()
	 * @method \Bitrix\AI\Model\EO_Role_Collection getUserAuthorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUserAuthor()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Entity\Role $object)
	 * @method bool has(\Bitrix\AI\Entity\Role $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Entity\Role getByPrimary($primary)
	 * @method \Bitrix\AI\Entity\Role[] getAll()
	 * @method bool remove(\Bitrix\AI\Entity\Role $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_Role_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Entity\Role current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_Role_Collection merge(?\Bitrix\AI\Model\EO_Role_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Role_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\RoleTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Role_Result exec()
	 * @method \Bitrix\AI\Entity\Role fetchObject()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fetchCollection()
	 */
	class EO_Role_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Entity\Role fetchObject()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fetchCollection()
	 */
	class EO_Role_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Entity\Role createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_Role_Collection createCollection()
	 * @method \Bitrix\AI\Entity\Role wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_Role_Collection wakeUpCollection($rows)
	 */
	class EO_Role_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\HistoryTable:ai/lib/Model/HistoryTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_History
	 * @see \Bitrix\AI\Model\HistoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_History setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getContextModule()
	 * @method \Bitrix\AI\Model\EO_History setContextModule(\string|\Bitrix\Main\DB\SqlExpression $contextModule)
	 * @method bool hasContextModule()
	 * @method bool isContextModuleFilled()
	 * @method bool isContextModuleChanged()
	 * @method \string remindActualContextModule()
	 * @method \string requireContextModule()
	 * @method \Bitrix\AI\Model\EO_History resetContextModule()
	 * @method \Bitrix\AI\Model\EO_History unsetContextModule()
	 * @method \string fillContextModule()
	 * @method \string getContextId()
	 * @method \Bitrix\AI\Model\EO_History setContextId(\string|\Bitrix\Main\DB\SqlExpression $contextId)
	 * @method bool hasContextId()
	 * @method bool isContextIdFilled()
	 * @method bool isContextIdChanged()
	 * @method \string remindActualContextId()
	 * @method \string requireContextId()
	 * @method \Bitrix\AI\Model\EO_History resetContextId()
	 * @method \Bitrix\AI\Model\EO_History unsetContextId()
	 * @method \string fillContextId()
	 * @method \string getEngineClass()
	 * @method \Bitrix\AI\Model\EO_History setEngineClass(\string|\Bitrix\Main\DB\SqlExpression $engineClass)
	 * @method bool hasEngineClass()
	 * @method bool isEngineClassFilled()
	 * @method bool isEngineClassChanged()
	 * @method \string remindActualEngineClass()
	 * @method \string requireEngineClass()
	 * @method \Bitrix\AI\Model\EO_History resetEngineClass()
	 * @method \Bitrix\AI\Model\EO_History unsetEngineClass()
	 * @method \string fillEngineClass()
	 * @method \string getEngineCode()
	 * @method \Bitrix\AI\Model\EO_History setEngineCode(\string|\Bitrix\Main\DB\SqlExpression $engineCode)
	 * @method bool hasEngineCode()
	 * @method bool isEngineCodeFilled()
	 * @method bool isEngineCodeChanged()
	 * @method \string remindActualEngineCode()
	 * @method \string requireEngineCode()
	 * @method \Bitrix\AI\Model\EO_History resetEngineCode()
	 * @method \Bitrix\AI\Model\EO_History unsetEngineCode()
	 * @method \string fillEngineCode()
	 * @method \string getPayloadClass()
	 * @method \Bitrix\AI\Model\EO_History setPayloadClass(\string|\Bitrix\Main\DB\SqlExpression $payloadClass)
	 * @method bool hasPayloadClass()
	 * @method bool isPayloadClassFilled()
	 * @method bool isPayloadClassChanged()
	 * @method \string remindActualPayloadClass()
	 * @method \string requirePayloadClass()
	 * @method \Bitrix\AI\Model\EO_History resetPayloadClass()
	 * @method \Bitrix\AI\Model\EO_History unsetPayloadClass()
	 * @method \string fillPayloadClass()
	 * @method \string getPayload()
	 * @method \Bitrix\AI\Model\EO_History setPayload(\string|\Bitrix\Main\DB\SqlExpression $payload)
	 * @method bool hasPayload()
	 * @method bool isPayloadFilled()
	 * @method bool isPayloadChanged()
	 * @method \string remindActualPayload()
	 * @method \string requirePayload()
	 * @method \Bitrix\AI\Model\EO_History resetPayload()
	 * @method \Bitrix\AI\Model\EO_History unsetPayload()
	 * @method \string fillPayload()
	 * @method array getParameters()
	 * @method \Bitrix\AI\Model\EO_History setParameters(array|\Bitrix\Main\DB\SqlExpression $parameters)
	 * @method bool hasParameters()
	 * @method bool isParametersFilled()
	 * @method bool isParametersChanged()
	 * @method array remindActualParameters()
	 * @method array requireParameters()
	 * @method \Bitrix\AI\Model\EO_History resetParameters()
	 * @method \Bitrix\AI\Model\EO_History unsetParameters()
	 * @method array fillParameters()
	 * @method \int getGroupId()
	 * @method \Bitrix\AI\Model\EO_History setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\AI\Model\EO_History resetGroupId()
	 * @method \Bitrix\AI\Model\EO_History unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \string getRequestText()
	 * @method \Bitrix\AI\Model\EO_History setRequestText(\string|\Bitrix\Main\DB\SqlExpression $requestText)
	 * @method bool hasRequestText()
	 * @method bool isRequestTextFilled()
	 * @method bool isRequestTextChanged()
	 * @method \string remindActualRequestText()
	 * @method \string requireRequestText()
	 * @method \Bitrix\AI\Model\EO_History resetRequestText()
	 * @method \Bitrix\AI\Model\EO_History unsetRequestText()
	 * @method \string fillRequestText()
	 * @method \string getResultText()
	 * @method \Bitrix\AI\Model\EO_History setResultText(\string|\Bitrix\Main\DB\SqlExpression $resultText)
	 * @method bool hasResultText()
	 * @method bool isResultTextFilled()
	 * @method bool isResultTextChanged()
	 * @method \string remindActualResultText()
	 * @method \string requireResultText()
	 * @method \Bitrix\AI\Model\EO_History resetResultText()
	 * @method \Bitrix\AI\Model\EO_History unsetResultText()
	 * @method \string fillResultText()
	 * @method \string getContext()
	 * @method \Bitrix\AI\Model\EO_History setContext(\string|\Bitrix\Main\DB\SqlExpression $context)
	 * @method bool hasContext()
	 * @method bool isContextFilled()
	 * @method bool isContextChanged()
	 * @method \string remindActualContext()
	 * @method \string requireContext()
	 * @method \Bitrix\AI\Model\EO_History resetContext()
	 * @method \Bitrix\AI\Model\EO_History unsetContext()
	 * @method \string fillContext()
	 * @method \boolean getCached()
	 * @method \Bitrix\AI\Model\EO_History setCached(\boolean|\Bitrix\Main\DB\SqlExpression $cached)
	 * @method bool hasCached()
	 * @method bool isCachedFilled()
	 * @method bool isCachedChanged()
	 * @method \boolean remindActualCached()
	 * @method \boolean requireCached()
	 * @method \Bitrix\AI\Model\EO_History resetCached()
	 * @method \Bitrix\AI\Model\EO_History unsetCached()
	 * @method \boolean fillCached()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\Model\EO_History setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\Model\EO_History resetDateCreate()
	 * @method \Bitrix\AI\Model\EO_History unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \int getCreatedById()
	 * @method \Bitrix\AI\Model\EO_History setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\AI\Model\EO_History resetCreatedById()
	 * @method \Bitrix\AI\Model\EO_History unsetCreatedById()
	 * @method \int fillCreatedById()
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
	 * @method \Bitrix\AI\Model\EO_History set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_History reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_History unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_History wakeUp($data)
	 */
	class EO_History {
		/* @var \Bitrix\AI\Model\HistoryTable */
		static public $dataClass = '\Bitrix\AI\Model\HistoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_History_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getContextModuleList()
	 * @method \string[] fillContextModule()
	 * @method \string[] getContextIdList()
	 * @method \string[] fillContextId()
	 * @method \string[] getEngineClassList()
	 * @method \string[] fillEngineClass()
	 * @method \string[] getEngineCodeList()
	 * @method \string[] fillEngineCode()
	 * @method \string[] getPayloadClassList()
	 * @method \string[] fillPayloadClass()
	 * @method \string[] getPayloadList()
	 * @method \string[] fillPayload()
	 * @method array[] getParametersList()
	 * @method array[] fillParameters()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \string[] getRequestTextList()
	 * @method \string[] fillRequestText()
	 * @method \string[] getResultTextList()
	 * @method \string[] fillResultText()
	 * @method \string[] getContextList()
	 * @method \string[] fillContext()
	 * @method \boolean[] getCachedList()
	 * @method \boolean[] fillCached()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_History $object)
	 * @method bool has(\Bitrix\AI\Model\EO_History $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_History getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_History[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_History $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_History_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_History current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_History_Collection merge(?\Bitrix\AI\Model\EO_History_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_History_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\HistoryTable */
		static public $dataClass = '\Bitrix\AI\Model\HistoryTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_History_Result exec()
	 * @method \Bitrix\AI\Model\EO_History fetchObject()
	 * @method \Bitrix\AI\Model\EO_History_Collection fetchCollection()
	 */
	class EO_History_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_History fetchObject()
	 * @method \Bitrix\AI\Model\EO_History_Collection fetchCollection()
	 */
	class EO_History_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_History createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_History_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_History wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_History_Collection wakeUpCollection($rows)
	 */
	class EO_History_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\SectionTable:ai/lib/Model/SectionTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_Section
	 * @see \Bitrix\AI\Model\SectionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_Section setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getCode()
	 * @method \Bitrix\AI\Model\EO_Section setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\AI\Model\EO_Section resetCode()
	 * @method \Bitrix\AI\Model\EO_Section unsetCode()
	 * @method \string fillCode()
	 * @method \string getHash()
	 * @method \Bitrix\AI\Model\EO_Section setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\AI\Model\EO_Section resetHash()
	 * @method \Bitrix\AI\Model\EO_Section unsetHash()
	 * @method \string fillHash()
	 * @method array getTranslate()
	 * @method \Bitrix\AI\Model\EO_Section setTranslate(array|\Bitrix\Main\DB\SqlExpression $translate)
	 * @method bool hasTranslate()
	 * @method bool isTranslateFilled()
	 * @method bool isTranslateChanged()
	 * @method array remindActualTranslate()
	 * @method array requireTranslate()
	 * @method \Bitrix\AI\Model\EO_Section resetTranslate()
	 * @method \Bitrix\AI\Model\EO_Section unsetTranslate()
	 * @method array fillTranslate()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\AI\Model\EO_Section setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\AI\Model\EO_Section resetDateModify()
	 * @method \Bitrix\AI\Model\EO_Section unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
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
	 * @method \Bitrix\AI\Model\EO_Section set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_Section reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_Section unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_Section wakeUp($data)
	 */
	class EO_Section {
		/* @var \Bitrix\AI\Model\SectionTable */
		static public $dataClass = '\Bitrix\AI\Model\SectionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_Section_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method array[] getTranslateList()
	 * @method array[] fillTranslate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_Section $object)
	 * @method bool has(\Bitrix\AI\Model\EO_Section $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Section getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Section[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_Section $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_Section_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_Section current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_Section_Collection merge(?\Bitrix\AI\Model\EO_Section_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Section_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\SectionTable */
		static public $dataClass = '\Bitrix\AI\Model\SectionTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Section_Result exec()
	 * @method \Bitrix\AI\Model\EO_Section fetchObject()
	 * @method \Bitrix\AI\Model\EO_Section_Collection fetchCollection()
	 */
	class EO_Section_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_Section fetchObject()
	 * @method \Bitrix\AI\Model\EO_Section_Collection fetchCollection()
	 */
	class EO_Section_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_Section createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_Section_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_Section wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_Section_Collection wakeUpCollection($rows)
	 */
	class EO_Section_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\RolePromptTable:ai/lib/Model/RolePromptTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_RolePrompt
	 * @see \Bitrix\AI\Model\RolePromptTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getRoleId()
	 * @method \Bitrix\AI\Model\EO_RolePrompt setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int getPromptId()
	 * @method \Bitrix\AI\Model\EO_RolePrompt setPromptId(\int|\Bitrix\Main\DB\SqlExpression $promptId)
	 * @method bool hasPromptId()
	 * @method bool isPromptIdFilled()
	 * @method bool isPromptIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\Model\EO_RolePrompt setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\Model\EO_RolePrompt resetDateCreate()
	 * @method \Bitrix\AI\Model\EO_RolePrompt unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
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
	 * @method \Bitrix\AI\Model\EO_RolePrompt set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_RolePrompt reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_RolePrompt unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_RolePrompt wakeUp($data)
	 */
	class EO_RolePrompt {
		/* @var \Bitrix\AI\Model\RolePromptTable */
		static public $dataClass = '\Bitrix\AI\Model\RolePromptTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_RolePrompt_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getRoleIdList()
	 * @method \int[] getPromptIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_RolePrompt $object)
	 * @method bool has(\Bitrix\AI\Model\EO_RolePrompt $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RolePrompt getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RolePrompt[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_RolePrompt $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_RolePrompt_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_RolePrompt current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_RolePrompt_Collection merge(?\Bitrix\AI\Model\EO_RolePrompt_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_RolePrompt_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\RolePromptTable */
		static public $dataClass = '\Bitrix\AI\Model\RolePromptTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RolePrompt_Result exec()
	 * @method \Bitrix\AI\Model\EO_RolePrompt fetchObject()
	 * @method \Bitrix\AI\Model\EO_RolePrompt_Collection fetchCollection()
	 */
	class EO_RolePrompt_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_RolePrompt fetchObject()
	 * @method \Bitrix\AI\Model\EO_RolePrompt_Collection fetchCollection()
	 */
	class EO_RolePrompt_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_RolePrompt createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_RolePrompt_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_RolePrompt wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_RolePrompt_Collection wakeUpCollection($rows)
	 */
	class EO_RolePrompt_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\CounterTable:ai/lib/Model/CounterTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_Counter
	 * @see \Bitrix\AI\Model\CounterTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_Counter setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\AI\Model\EO_Counter setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\AI\Model\EO_Counter resetName()
	 * @method \Bitrix\AI\Model\EO_Counter unsetName()
	 * @method \string fillName()
	 * @method \string getValue()
	 * @method \Bitrix\AI\Model\EO_Counter setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\AI\Model\EO_Counter resetValue()
	 * @method \Bitrix\AI\Model\EO_Counter unsetValue()
	 * @method \string fillValue()
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
	 * @method \Bitrix\AI\Model\EO_Counter set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_Counter reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_Counter unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_Counter wakeUp($data)
	 */
	class EO_Counter {
		/* @var \Bitrix\AI\Model\CounterTable */
		static public $dataClass = '\Bitrix\AI\Model\CounterTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_Counter_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_Counter $object)
	 * @method bool has(\Bitrix\AI\Model\EO_Counter $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Counter getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Counter[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_Counter $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_Counter_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_Counter current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_Counter_Collection merge(?\Bitrix\AI\Model\EO_Counter_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Counter_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\CounterTable */
		static public $dataClass = '\Bitrix\AI\Model\CounterTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Counter_Result exec()
	 * @method \Bitrix\AI\Model\EO_Counter fetchObject()
	 * @method \Bitrix\AI\Model\EO_Counter_Collection fetchCollection()
	 */
	class EO_Counter_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_Counter fetchObject()
	 * @method \Bitrix\AI\Model\EO_Counter_Collection fetchCollection()
	 */
	class EO_Counter_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_Counter createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_Counter_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_Counter wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_Counter_Collection wakeUpCollection($rows)
	 */
	class EO_Counter_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\EngineTable:ai/lib/Model/EngineTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_Engine
	 * @see \Bitrix\AI\Model\EngineTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_Engine setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getAppCode()
	 * @method \Bitrix\AI\Model\EO_Engine setAppCode(\string|\Bitrix\Main\DB\SqlExpression $appCode)
	 * @method bool hasAppCode()
	 * @method bool isAppCodeFilled()
	 * @method bool isAppCodeChanged()
	 * @method \string remindActualAppCode()
	 * @method \string requireAppCode()
	 * @method \Bitrix\AI\Model\EO_Engine resetAppCode()
	 * @method \Bitrix\AI\Model\EO_Engine unsetAppCode()
	 * @method \string fillAppCode()
	 * @method \string getName()
	 * @method \Bitrix\AI\Model\EO_Engine setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\AI\Model\EO_Engine resetName()
	 * @method \Bitrix\AI\Model\EO_Engine unsetName()
	 * @method \string fillName()
	 * @method \string getCode()
	 * @method \Bitrix\AI\Model\EO_Engine setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\AI\Model\EO_Engine resetCode()
	 * @method \Bitrix\AI\Model\EO_Engine unsetCode()
	 * @method \string fillCode()
	 * @method \string getCategory()
	 * @method \Bitrix\AI\Model\EO_Engine setCategory(\string|\Bitrix\Main\DB\SqlExpression $category)
	 * @method bool hasCategory()
	 * @method bool isCategoryFilled()
	 * @method bool isCategoryChanged()
	 * @method \string remindActualCategory()
	 * @method \string requireCategory()
	 * @method \Bitrix\AI\Model\EO_Engine resetCategory()
	 * @method \Bitrix\AI\Model\EO_Engine unsetCategory()
	 * @method \string fillCategory()
	 * @method \string getCompletionsUrl()
	 * @method \Bitrix\AI\Model\EO_Engine setCompletionsUrl(\string|\Bitrix\Main\DB\SqlExpression $completionsUrl)
	 * @method bool hasCompletionsUrl()
	 * @method bool isCompletionsUrlFilled()
	 * @method bool isCompletionsUrlChanged()
	 * @method \string remindActualCompletionsUrl()
	 * @method \string requireCompletionsUrl()
	 * @method \Bitrix\AI\Model\EO_Engine resetCompletionsUrl()
	 * @method \Bitrix\AI\Model\EO_Engine unsetCompletionsUrl()
	 * @method \string fillCompletionsUrl()
	 * @method array getSettings()
	 * @method \Bitrix\AI\Model\EO_Engine setSettings(array|\Bitrix\Main\DB\SqlExpression $settings)
	 * @method bool hasSettings()
	 * @method bool isSettingsFilled()
	 * @method bool isSettingsChanged()
	 * @method array remindActualSettings()
	 * @method array requireSettings()
	 * @method \Bitrix\AI\Model\EO_Engine resetSettings()
	 * @method \Bitrix\AI\Model\EO_Engine unsetSettings()
	 * @method array fillSettings()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\Model\EO_Engine setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\Model\EO_Engine resetDateCreate()
	 * @method \Bitrix\AI\Model\EO_Engine unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
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
	 * @method \Bitrix\AI\Model\EO_Engine set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_Engine reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_Engine unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_Engine wakeUp($data)
	 */
	class EO_Engine {
		/* @var \Bitrix\AI\Model\EngineTable */
		static public $dataClass = '\Bitrix\AI\Model\EngineTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_Engine_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getAppCodeList()
	 * @method \string[] fillAppCode()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getCategoryList()
	 * @method \string[] fillCategory()
	 * @method \string[] getCompletionsUrlList()
	 * @method \string[] fillCompletionsUrl()
	 * @method array[] getSettingsList()
	 * @method array[] fillSettings()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_Engine $object)
	 * @method bool has(\Bitrix\AI\Model\EO_Engine $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Engine getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Engine[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_Engine $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_Engine_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_Engine current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_Engine_Collection merge(?\Bitrix\AI\Model\EO_Engine_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Engine_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\EngineTable */
		static public $dataClass = '\Bitrix\AI\Model\EngineTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Engine_Result exec()
	 * @method \Bitrix\AI\Model\EO_Engine fetchObject()
	 * @method \Bitrix\AI\Model\EO_Engine_Collection fetchCollection()
	 */
	class EO_Engine_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_Engine fetchObject()
	 * @method \Bitrix\AI\Model\EO_Engine_Collection fetchCollection()
	 */
	class EO_Engine_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_Engine createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_Engine_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_Engine wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_Engine_Collection wakeUpCollection($rows)
	 */
	class EO_Engine_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\RoleTranslateNameTable:ai/lib/Model/RoleTranslateNameTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_RoleTranslateName
	 * @see \Bitrix\AI\Model\RoleTranslateNameTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getRoleId()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName setRoleId(\string|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \string remindActualRoleId()
	 * @method \string requireRoleId()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName resetRoleId()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName unsetRoleId()
	 * @method \string fillRoleId()
	 * @method \string getLang()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName setLang(\string|\Bitrix\Main\DB\SqlExpression $lang)
	 * @method bool hasLang()
	 * @method bool isLangFilled()
	 * @method bool isLangChanged()
	 * @method \string remindActualLang()
	 * @method \string requireLang()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName resetLang()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName unsetLang()
	 * @method \string fillLang()
	 * @method \string getText()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName setText(\string|\Bitrix\Main\DB\SqlExpression $text)
	 * @method bool hasText()
	 * @method bool isTextFilled()
	 * @method bool isTextChanged()
	 * @method \string remindActualText()
	 * @method \string requireText()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName resetText()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName unsetText()
	 * @method \string fillText()
	 * @method \Bitrix\AI\Entity\Role getRole()
	 * @method \Bitrix\AI\Entity\Role remindActualRole()
	 * @method \Bitrix\AI\Entity\Role requireRole()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName setRole(\Bitrix\AI\Entity\Role $object)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName resetRole()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\AI\Entity\Role fillRole()
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
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_RoleTranslateName wakeUp($data)
	 */
	class EO_RoleTranslateName {
		/* @var \Bitrix\AI\Model\RoleTranslateNameTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleTranslateNameTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_RoleTranslateName_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getRoleIdList()
	 * @method \string[] fillRoleId()
	 * @method \string[] getLangList()
	 * @method \string[] fillLang()
	 * @method \string[] getTextList()
	 * @method \string[] fillText()
	 * @method \Bitrix\AI\Entity\Role[] getRoleList()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection getRoleCollection()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_RoleTranslateName $object)
	 * @method bool has(\Bitrix\AI\Model\EO_RoleTranslateName $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_RoleTranslateName $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_RoleTranslateName_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection merge(?\Bitrix\AI\Model\EO_RoleTranslateName_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_RoleTranslateName_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\RoleTranslateNameTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleTranslateNameTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RoleTranslateName_Result exec()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection fetchCollection()
	 */
	class EO_RoleTranslateName_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection fetchCollection()
	 */
	class EO_RoleTranslateName_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_RoleTranslateName_Collection wakeUpCollection($rows)
	 */
	class EO_RoleTranslateName_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\PlanTable:ai/lib/Model/PlanTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_Plan
	 * @see \Bitrix\AI\Model\PlanTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_Plan setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getCode()
	 * @method \Bitrix\AI\Model\EO_Plan setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\AI\Model\EO_Plan resetCode()
	 * @method \Bitrix\AI\Model\EO_Plan unsetCode()
	 * @method \string fillCode()
	 * @method \string getHash()
	 * @method \Bitrix\AI\Model\EO_Plan setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\AI\Model\EO_Plan resetHash()
	 * @method \Bitrix\AI\Model\EO_Plan unsetHash()
	 * @method \string fillHash()
	 * @method \int getMaxUsage()
	 * @method \Bitrix\AI\Model\EO_Plan setMaxUsage(\int|\Bitrix\Main\DB\SqlExpression $maxUsage)
	 * @method bool hasMaxUsage()
	 * @method bool isMaxUsageFilled()
	 * @method bool isMaxUsageChanged()
	 * @method \int remindActualMaxUsage()
	 * @method \int requireMaxUsage()
	 * @method \Bitrix\AI\Model\EO_Plan resetMaxUsage()
	 * @method \Bitrix\AI\Model\EO_Plan unsetMaxUsage()
	 * @method \int fillMaxUsage()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\AI\Model\EO_Plan setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\AI\Model\EO_Plan resetDateModify()
	 * @method \Bitrix\AI\Model\EO_Plan unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
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
	 * @method \Bitrix\AI\Model\EO_Plan set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_Plan reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_Plan unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_Plan wakeUp($data)
	 */
	class EO_Plan {
		/* @var \Bitrix\AI\Model\PlanTable */
		static public $dataClass = '\Bitrix\AI\Model\PlanTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_Plan_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \int[] getMaxUsageList()
	 * @method \int[] fillMaxUsage()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_Plan $object)
	 * @method bool has(\Bitrix\AI\Model\EO_Plan $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Plan getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_Plan[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_Plan $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_Plan_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_Plan current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_Plan_Collection merge(?\Bitrix\AI\Model\EO_Plan_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Plan_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\PlanTable */
		static public $dataClass = '\Bitrix\AI\Model\PlanTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Plan_Result exec()
	 * @method \Bitrix\AI\Model\EO_Plan fetchObject()
	 * @method \Bitrix\AI\Model\EO_Plan_Collection fetchCollection()
	 */
	class EO_Plan_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_Plan fetchObject()
	 * @method \Bitrix\AI\Model\EO_Plan_Collection fetchCollection()
	 */
	class EO_Plan_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_Plan createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_Plan_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_Plan wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_Plan_Collection wakeUpCollection($rows)
	 */
	class EO_Plan_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\RoleFavoriteTable:ai/lib/Model/RoleFavoriteTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_RoleFavorite
	 * @see \Bitrix\AI\Model\RoleFavoriteTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getRoleCode()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite setRoleCode(\string|\Bitrix\Main\DB\SqlExpression $roleCode)
	 * @method bool hasRoleCode()
	 * @method bool isRoleCodeFilled()
	 * @method bool isRoleCodeChanged()
	 * @method \string remindActualRoleCode()
	 * @method \string requireRoleCode()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite resetRoleCode()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite unsetRoleCode()
	 * @method \string fillRoleCode()
	 * @method \Bitrix\AI\Entity\Role getRole()
	 * @method \Bitrix\AI\Entity\Role remindActualRole()
	 * @method \Bitrix\AI\Entity\Role requireRole()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite setRole(\Bitrix\AI\Entity\Role $object)
	 * @method \Bitrix\AI\Model\EO_RoleFavorite resetRole()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\AI\Entity\Role fillRole()
	 * @method \int getUserId()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite resetUserId()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite resetDateCreate()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
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
	 * @method \Bitrix\AI\Model\EO_RoleFavorite set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_RoleFavorite reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_RoleFavorite unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_RoleFavorite wakeUp($data)
	 */
	class EO_RoleFavorite {
		/* @var \Bitrix\AI\Model\RoleFavoriteTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleFavoriteTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_RoleFavorite_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getRoleCodeList()
	 * @method \string[] fillRoleCode()
	 * @method \Bitrix\AI\Entity\Role[] getRoleList()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite_Collection getRoleCollection()
	 * @method \Bitrix\AI\Model\EO_Role_Collection fillRole()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_RoleFavorite $object)
	 * @method bool has(\Bitrix\AI\Model\EO_RoleFavorite $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RoleFavorite getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_RoleFavorite[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_RoleFavorite $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_RoleFavorite_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_RoleFavorite current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_RoleFavorite_Collection merge(?\Bitrix\AI\Model\EO_RoleFavorite_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_RoleFavorite_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\RoleFavoriteTable */
		static public $dataClass = '\Bitrix\AI\Model\RoleFavoriteTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RoleFavorite_Result exec()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite_Collection fetchCollection()
	 */
	class EO_RoleFavorite_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_RoleFavorite fetchObject()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite_Collection fetchCollection()
	 */
	class EO_RoleFavorite_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_RoleFavorite createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_RoleFavorite_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_RoleFavorite wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_RoleFavorite_Collection wakeUpCollection($rows)
	 */
	class EO_RoleFavorite_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Model\PromptCategoryTable:ai/lib/Model/PromptCategoryTable.php */
namespace Bitrix\AI\Model {
	/**
	 * EO_PromptCategory
	 * @see \Bitrix\AI\Model\PromptCategoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getPromptId()
	 * @method \Bitrix\AI\Model\EO_PromptCategory setPromptId(\int|\Bitrix\Main\DB\SqlExpression $promptId)
	 * @method bool hasPromptId()
	 * @method bool isPromptIdFilled()
	 * @method bool isPromptIdChanged()
	 * @method \string getCode()
	 * @method \Bitrix\AI\Model\EO_PromptCategory setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
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
	 * @method \Bitrix\AI\Model\EO_PromptCategory set($fieldName, $value)
	 * @method \Bitrix\AI\Model\EO_PromptCategory reset($fieldName)
	 * @method \Bitrix\AI\Model\EO_PromptCategory unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Model\EO_PromptCategory wakeUp($data)
	 */
	class EO_PromptCategory {
		/* @var \Bitrix\AI\Model\PromptCategoryTable */
		static public $dataClass = '\Bitrix\AI\Model\PromptCategoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Model {
	/**
	 * EO_PromptCategory_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getPromptIdList()
	 * @method \string[] getCodeList()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Model\EO_PromptCategory $object)
	 * @method bool has(\Bitrix\AI\Model\EO_PromptCategory $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_PromptCategory getByPrimary($primary)
	 * @method \Bitrix\AI\Model\EO_PromptCategory[] getAll()
	 * @method bool remove(\Bitrix\AI\Model\EO_PromptCategory $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Model\EO_PromptCategory_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Model\EO_PromptCategory current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Model\EO_PromptCategory_Collection merge(?\Bitrix\AI\Model\EO_PromptCategory_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_PromptCategory_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Model\PromptCategoryTable */
		static public $dataClass = '\Bitrix\AI\Model\PromptCategoryTable';
	}
}
namespace Bitrix\AI\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_PromptCategory_Result exec()
	 * @method \Bitrix\AI\Model\EO_PromptCategory fetchObject()
	 * @method \Bitrix\AI\Model\EO_PromptCategory_Collection fetchCollection()
	 */
	class EO_PromptCategory_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Model\EO_PromptCategory fetchObject()
	 * @method \Bitrix\AI\Model\EO_PromptCategory_Collection fetchCollection()
	 */
	class EO_PromptCategory_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Model\EO_PromptCategory createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Model\EO_PromptCategory_Collection createCollection()
	 * @method \Bitrix\AI\Model\EO_PromptCategory wakeUpObject($row)
	 * @method \Bitrix\AI\Model\EO_PromptCategory_Collection wakeUpCollection($rows)
	 */
	class EO_PromptCategory_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\SharePrompt\Model\ShareTable:ai/lib/SharePrompt/Model/ShareTable.php */
namespace Bitrix\AI\SharePrompt\Model {
	/**
	 * EO_Share
	 * @see \Bitrix\AI\SharePrompt\Model\ShareTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getPromptId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share setPromptId(\int|\Bitrix\Main\DB\SqlExpression $promptId)
	 * @method bool hasPromptId()
	 * @method bool isPromptIdFilled()
	 * @method bool isPromptIdChanged()
	 * @method \int remindActualPromptId()
	 * @method \int requirePromptId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share resetPromptId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share unsetPromptId()
	 * @method \int fillPromptId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share resetAccessCode()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share resetDateCreate()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share resetCreatedBy()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share unsetCreatedBy()
	 * @method \int fillCreatedBy()
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
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share set($fieldName, $value)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share reset($fieldName)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\SharePrompt\Model\EO_Share wakeUp($data)
	 */
	class EO_Share {
		/* @var \Bitrix\AI\SharePrompt\Model\ShareTable */
		static public $dataClass = '\Bitrix\AI\SharePrompt\Model\ShareTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\SharePrompt\Model {
	/**
	 * EO_Share_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getPromptIdList()
	 * @method \int[] fillPromptId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\SharePrompt\Model\EO_Share $object)
	 * @method bool has(\Bitrix\AI\SharePrompt\Model\EO_Share $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share getByPrimary($primary)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share[] getAll()
	 * @method bool remove(\Bitrix\AI\SharePrompt\Model\EO_Share $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\SharePrompt\Model\EO_Share_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share_Collection merge(?\Bitrix\AI\SharePrompt\Model\EO_Share_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Share_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\SharePrompt\Model\ShareTable */
		static public $dataClass = '\Bitrix\AI\SharePrompt\Model\ShareTable';
	}
}
namespace Bitrix\AI\SharePrompt\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Share_Result exec()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share fetchObject()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share_Collection fetchCollection()
	 */
	class EO_Share_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share fetchObject()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share_Collection fetchCollection()
	 */
	class EO_Share_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share_Collection createCollection()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share wakeUpObject($row)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Share_Collection wakeUpCollection($rows)
	 */
	class EO_Share_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\SharePrompt\Model\OwnerTable:ai/lib/SharePrompt/Model/OwnerTable.php */
namespace Bitrix\AI\SharePrompt\Model {
	/**
	 * EO_Owner
	 * @see \Bitrix\AI\SharePrompt\Model\OwnerTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner resetUserId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getPromptId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner setPromptId(\int|\Bitrix\Main\DB\SqlExpression $promptId)
	 * @method bool hasPromptId()
	 * @method bool isPromptIdFilled()
	 * @method bool isPromptIdChanged()
	 * @method \int remindActualPromptId()
	 * @method \int requirePromptId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner resetPromptId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner unsetPromptId()
	 * @method \int fillPromptId()
	 * @method \boolean getIsFavorite()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner setIsFavorite(\boolean|\Bitrix\Main\DB\SqlExpression $isFavorite)
	 * @method bool hasIsFavorite()
	 * @method bool isIsFavoriteFilled()
	 * @method bool isIsFavoriteChanged()
	 * @method \boolean remindActualIsFavorite()
	 * @method \boolean requireIsFavorite()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner resetIsFavorite()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner unsetIsFavorite()
	 * @method \boolean fillIsFavorite()
	 * @method \boolean getIsDeleted()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner setIsDeleted(\boolean|\Bitrix\Main\DB\SqlExpression $isDeleted)
	 * @method bool hasIsDeleted()
	 * @method bool isIsDeletedFilled()
	 * @method bool isIsDeletedChanged()
	 * @method \boolean remindActualIsDeleted()
	 * @method \boolean requireIsDeleted()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner resetIsDeleted()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner unsetIsDeleted()
	 * @method \boolean fillIsDeleted()
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
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner set($fieldName, $value)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner reset($fieldName)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\SharePrompt\Model\EO_Owner wakeUp($data)
	 */
	class EO_Owner {
		/* @var \Bitrix\AI\SharePrompt\Model\OwnerTable */
		static public $dataClass = '\Bitrix\AI\SharePrompt\Model\OwnerTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\SharePrompt\Model {
	/**
	 * EO_Owner_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getPromptIdList()
	 * @method \int[] fillPromptId()
	 * @method \boolean[] getIsFavoriteList()
	 * @method \boolean[] fillIsFavorite()
	 * @method \boolean[] getIsDeletedList()
	 * @method \boolean[] fillIsDeleted()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\SharePrompt\Model\EO_Owner $object)
	 * @method bool has(\Bitrix\AI\SharePrompt\Model\EO_Owner $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner getByPrimary($primary)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner[] getAll()
	 * @method bool remove(\Bitrix\AI\SharePrompt\Model\EO_Owner $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\SharePrompt\Model\EO_Owner_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner_Collection merge(?\Bitrix\AI\SharePrompt\Model\EO_Owner_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Owner_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\SharePrompt\Model\OwnerTable */
		static public $dataClass = '\Bitrix\AI\SharePrompt\Model\OwnerTable';
	}
}
namespace Bitrix\AI\SharePrompt\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Owner_Result exec()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner fetchObject()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner_Collection fetchCollection()
	 */
	class EO_Owner_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner fetchObject()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner_Collection fetchCollection()
	 */
	class EO_Owner_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner_Collection createCollection()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner wakeUpObject($row)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_Owner_Collection wakeUpCollection($rows)
	 */
	class EO_Owner_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\SharePrompt\Model\OwnerOptionTable:ai/lib/SharePrompt/Model/OwnerOptionTable.php */
namespace Bitrix\AI\SharePrompt\Model {
	/**
	 * EO_OwnerOption
	 * @see \Bitrix\AI\SharePrompt\Model\OwnerOptionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption resetUserId()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getSortingInFavoriteList()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption setSortingInFavoriteList(\string|\Bitrix\Main\DB\SqlExpression $sortingInFavoriteList)
	 * @method bool hasSortingInFavoriteList()
	 * @method bool isSortingInFavoriteListFilled()
	 * @method bool isSortingInFavoriteListChanged()
	 * @method \string remindActualSortingInFavoriteList()
	 * @method \string requireSortingInFavoriteList()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption resetSortingInFavoriteList()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption unsetSortingInFavoriteList()
	 * @method \string fillSortingInFavoriteList()
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
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption set($fieldName, $value)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption reset($fieldName)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\SharePrompt\Model\EO_OwnerOption wakeUp($data)
	 */
	class EO_OwnerOption {
		/* @var \Bitrix\AI\SharePrompt\Model\OwnerOptionTable */
		static public $dataClass = '\Bitrix\AI\SharePrompt\Model\OwnerOptionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\SharePrompt\Model {
	/**
	 * EO_OwnerOption_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getSortingInFavoriteListList()
	 * @method \string[] fillSortingInFavoriteList()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\SharePrompt\Model\EO_OwnerOption $object)
	 * @method bool has(\Bitrix\AI\SharePrompt\Model\EO_OwnerOption $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption getByPrimary($primary)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption[] getAll()
	 * @method bool remove(\Bitrix\AI\SharePrompt\Model\EO_OwnerOption $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\SharePrompt\Model\EO_OwnerOption_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption_Collection merge(?\Bitrix\AI\SharePrompt\Model\EO_OwnerOption_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_OwnerOption_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\SharePrompt\Model\OwnerOptionTable */
		static public $dataClass = '\Bitrix\AI\SharePrompt\Model\OwnerOptionTable';
	}
}
namespace Bitrix\AI\SharePrompt\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_OwnerOption_Result exec()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption fetchObject()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption_Collection fetchCollection()
	 */
	class EO_OwnerOption_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption fetchObject()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption_Collection fetchCollection()
	 */
	class EO_OwnerOption_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption_Collection createCollection()
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption wakeUpObject($row)
	 * @method \Bitrix\AI\SharePrompt\Model\EO_OwnerOption_Collection wakeUpCollection($rows)
	 */
	class EO_OwnerOption_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\AI\Limiter\Model\BaasPackageTable:ai/lib/Limiter/Model/BaasPackageTable.php */
namespace Bitrix\AI\Limiter\Model {
	/**
	 * EO_BaasPackage
	 * @see \Bitrix\AI\Limiter\Model\BaasPackageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\Date getDateStart()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage setDateStart(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\Date remindActualDateStart()
	 * @method \Bitrix\Main\Type\Date requireDateStart()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage resetDateStart()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage unsetDateStart()
	 * @method \Bitrix\Main\Type\Date fillDateStart()
	 * @method \Bitrix\Main\Type\Date getDateExpired()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage setDateExpired(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateExpired)
	 * @method bool hasDateExpired()
	 * @method bool isDateExpiredFilled()
	 * @method bool isDateExpiredChanged()
	 * @method \Bitrix\Main\Type\Date remindActualDateExpired()
	 * @method \Bitrix\Main\Type\Date requireDateExpired()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage resetDateExpired()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage unsetDateExpired()
	 * @method \Bitrix\Main\Type\Date fillDateExpired()
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
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage set($fieldName, $value)
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage reset($fieldName)
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\AI\Limiter\Model\EO_BaasPackage wakeUp($data)
	 */
	class EO_BaasPackage {
		/* @var \Bitrix\AI\Limiter\Model\BaasPackageTable */
		static public $dataClass = '\Bitrix\AI\Limiter\Model\BaasPackageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\AI\Limiter\Model {
	/**
	 * EO_BaasPackage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\Date[] getDateStartList()
	 * @method \Bitrix\Main\Type\Date[] fillDateStart()
	 * @method \Bitrix\Main\Type\Date[] getDateExpiredList()
	 * @method \Bitrix\Main\Type\Date[] fillDateExpired()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\AI\Limiter\Model\EO_BaasPackage $object)
	 * @method bool has(\Bitrix\AI\Limiter\Model\EO_BaasPackage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage getByPrimary($primary)
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage[] getAll()
	 * @method bool remove(\Bitrix\AI\Limiter\Model\EO_BaasPackage $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\AI\Limiter\Model\EO_BaasPackage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage_Collection merge(?\Bitrix\AI\Limiter\Model\EO_BaasPackage_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_BaasPackage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\AI\Limiter\Model\BaasPackageTable */
		static public $dataClass = '\Bitrix\AI\Limiter\Model\BaasPackageTable';
	}
}
namespace Bitrix\AI\Limiter\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_BaasPackage_Result exec()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage fetchObject()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage_Collection fetchCollection()
	 */
	class EO_BaasPackage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage fetchObject()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage_Collection fetchCollection()
	 */
	class EO_BaasPackage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage createObject($setDefaultValues = true)
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage_Collection createCollection()
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage wakeUpObject($row)
	 * @method \Bitrix\AI\Limiter\Model\EO_BaasPackage_Collection wakeUpCollection($rows)
	 */
	class EO_BaasPackage_Entity extends \Bitrix\Main\ORM\Entity {}
}