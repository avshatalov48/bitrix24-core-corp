<?php

/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeMemberRoleTable:humanresources/lib/Model/NodeMemberRoleTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\RoleTable:humanresources/lib/Model/RoleTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeRoleTable:humanresources/lib/Model/NodeRoleTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\StructureTable:humanresources/lib/Model/StructureTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\LogTable:humanresources/lib/Model/LogTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeMemberTable:humanresources/lib/Model/NodeMemberTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeRelationTable:humanresources/lib/Model/NodeRelationTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable:humanresources/lib/Model/NodeBackwardAccessCodeTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodeTable:humanresources/lib/Model/NodeTable.php */
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
	 * @method \string getDescription()
	 * @method \Bitrix\HumanResources\Model\Node setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\HumanResources\Model\Node resetDescription()
	 * @method \Bitrix\HumanResources\Model\Node unsetDescription()
	 * @method \string fillDescription()
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
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\Access\AccessPermissionTable:humanresources/lib/Model/Access/AccessPermissionTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\Access\AccessRoleRelationTable:humanresources/lib/Model/Access/AccessRoleRelationTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\Access\AccessRoleTable:humanresources/lib/Model/Access/AccessRoleTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\HumanResources\Model\NodePathTable:humanresources/lib/Model/NodePathTable.php */
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