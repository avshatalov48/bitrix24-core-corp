<?php

/* ORMENTITYANNOTATION:Bitrix\Rpa\Model\TimelineTable:rpa/lib/model/timelinetable.php */
namespace Bitrix\Rpa\Model {
	/**
	 * Timeline
	 * @see \Bitrix\Rpa\Model\TimelineTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Rpa\Model\Timeline setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTypeId()
	 * @method \Bitrix\Rpa\Model\Timeline setTypeId(\int|\Bitrix\Main\DB\SqlExpression $typeId)
	 * @method bool hasTypeId()
	 * @method bool isTypeIdFilled()
	 * @method bool isTypeIdChanged()
	 * @method \int remindActualTypeId()
	 * @method \int requireTypeId()
	 * @method \Bitrix\Rpa\Model\Timeline resetTypeId()
	 * @method \Bitrix\Rpa\Model\Timeline unsetTypeId()
	 * @method \int fillTypeId()
	 * @method \int getItemId()
	 * @method \Bitrix\Rpa\Model\Timeline setItemId(\int|\Bitrix\Main\DB\SqlExpression $itemId)
	 * @method bool hasItemId()
	 * @method bool isItemIdFilled()
	 * @method bool isItemIdChanged()
	 * @method \int remindActualItemId()
	 * @method \int requireItemId()
	 * @method \Bitrix\Rpa\Model\Timeline resetItemId()
	 * @method \Bitrix\Rpa\Model\Timeline unsetItemId()
	 * @method \int fillItemId()
	 * @method \Bitrix\Main\Type\DateTime getCreatedTime()
	 * @method \Bitrix\Rpa\Model\Timeline setCreatedTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdTime)
	 * @method bool hasCreatedTime()
	 * @method bool isCreatedTimeFilled()
	 * @method bool isCreatedTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedTime()
	 * @method \Bitrix\Rpa\Model\Timeline resetCreatedTime()
	 * @method \Bitrix\Rpa\Model\Timeline unsetCreatedTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedTime()
	 * @method \int getUserId()
	 * @method \Bitrix\Rpa\Model\Timeline setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Rpa\Model\Timeline resetUserId()
	 * @method \Bitrix\Rpa\Model\Timeline unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getTitle()
	 * @method \Bitrix\Rpa\Model\Timeline setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Rpa\Model\Timeline resetTitle()
	 * @method \Bitrix\Rpa\Model\Timeline unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getDescription()
	 * @method \Bitrix\Rpa\Model\Timeline setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Rpa\Model\Timeline resetDescription()
	 * @method \Bitrix\Rpa\Model\Timeline unsetDescription()
	 * @method \string fillDescription()
	 * @method \string getAction()
	 * @method \Bitrix\Rpa\Model\Timeline setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\Rpa\Model\Timeline resetAction()
	 * @method \Bitrix\Rpa\Model\Timeline unsetAction()
	 * @method \string fillAction()
	 * @method \boolean getIsFixed()
	 * @method \Bitrix\Rpa\Model\Timeline setIsFixed(\boolean|\Bitrix\Main\DB\SqlExpression $isFixed)
	 * @method bool hasIsFixed()
	 * @method bool isIsFixedFilled()
	 * @method bool isIsFixedChanged()
	 * @method \boolean remindActualIsFixed()
	 * @method \boolean requireIsFixed()
	 * @method \Bitrix\Rpa\Model\Timeline resetIsFixed()
	 * @method \Bitrix\Rpa\Model\Timeline unsetIsFixed()
	 * @method \boolean fillIsFixed()
	 * @method array getData()
	 * @method \Bitrix\Rpa\Model\Timeline setData(array|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method array remindActualData()
	 * @method array requireData()
	 * @method \Bitrix\Rpa\Model\Timeline resetData()
	 * @method \Bitrix\Rpa\Model\Timeline unsetData()
	 * @method array fillData()
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
	 * @method \Bitrix\Rpa\Model\Timeline set($fieldName, $value)
	 * @method \Bitrix\Rpa\Model\Timeline reset($fieldName)
	 * @method \Bitrix\Rpa\Model\Timeline unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Rpa\Model\Timeline wakeUp($data)
	 */
	class EO_Timeline {
		/* @var \Bitrix\Rpa\Model\TimelineTable */
		static public $dataClass = '\Bitrix\Rpa\Model\TimelineTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * EO_Timeline_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTypeIdList()
	 * @method \int[] fillTypeId()
	 * @method \int[] getItemIdList()
	 * @method \int[] fillItemId()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedTime()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \string[] getActionList()
	 * @method \string[] fillAction()
	 * @method \boolean[] getIsFixedList()
	 * @method \boolean[] fillIsFixed()
	 * @method array[] getDataList()
	 * @method array[] fillData()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Rpa\Model\Timeline $object)
	 * @method bool has(\Bitrix\Rpa\Model\Timeline $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\Timeline getByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\Timeline[] getAll()
	 * @method bool remove(\Bitrix\Rpa\Model\Timeline $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Rpa\Model\EO_Timeline_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Rpa\Model\Timeline current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Timeline_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Rpa\Model\TimelineTable */
		static public $dataClass = '\Bitrix\Rpa\Model\TimelineTable';
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Timeline_Result exec()
	 * @method \Bitrix\Rpa\Model\Timeline fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_Timeline_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Timeline_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Rpa\Model\Timeline fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_Timeline_Collection fetchCollection()
	 */
	class EO_Timeline_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Rpa\Model\Timeline createObject($setDefaultValues = true)
	 * @method \Bitrix\Rpa\Model\EO_Timeline_Collection createCollection()
	 * @method \Bitrix\Rpa\Model\Timeline wakeUpObject($row)
	 * @method \Bitrix\Rpa\Model\EO_Timeline_Collection wakeUpCollection($rows)
	 */
	class EO_Timeline_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Rpa\Model\ItemSortTable:rpa/lib/model/itemsorttable.php */
namespace Bitrix\Rpa\Model {
	/**
	 * EO_ItemSort
	 * @see \Bitrix\Rpa\Model\ItemSortTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort resetUserId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getTypeId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort setTypeId(\int|\Bitrix\Main\DB\SqlExpression $typeId)
	 * @method bool hasTypeId()
	 * @method bool isTypeIdFilled()
	 * @method bool isTypeIdChanged()
	 * @method \int remindActualTypeId()
	 * @method \int requireTypeId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort resetTypeId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort unsetTypeId()
	 * @method \int fillTypeId()
	 * @method \int getItemId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort setItemId(\int|\Bitrix\Main\DB\SqlExpression $itemId)
	 * @method bool hasItemId()
	 * @method bool isItemIdFilled()
	 * @method bool isItemIdChanged()
	 * @method \int remindActualItemId()
	 * @method \int requireItemId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort resetItemId()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort unsetItemId()
	 * @method \int fillItemId()
	 * @method \int getSort()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort resetSort()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort unsetSort()
	 * @method \int fillSort()
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
	 * @method \Bitrix\Rpa\Model\EO_ItemSort set($fieldName, $value)
	 * @method \Bitrix\Rpa\Model\EO_ItemSort reset($fieldName)
	 * @method \Bitrix\Rpa\Model\EO_ItemSort unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Rpa\Model\EO_ItemSort wakeUp($data)
	 */
	class EO_ItemSort {
		/* @var \Bitrix\Rpa\Model\ItemSortTable */
		static public $dataClass = '\Bitrix\Rpa\Model\ItemSortTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * EO_ItemSort_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getTypeIdList()
	 * @method \int[] fillTypeId()
	 * @method \int[] getItemIdList()
	 * @method \int[] fillItemId()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Rpa\Model\EO_ItemSort $object)
	 * @method bool has(\Bitrix\Rpa\Model\EO_ItemSort $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_ItemSort getByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_ItemSort[] getAll()
	 * @method bool remove(\Bitrix\Rpa\Model\EO_ItemSort $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Rpa\Model\EO_ItemSort_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Rpa\Model\EO_ItemSort current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ItemSort_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Rpa\Model\ItemSortTable */
		static public $dataClass = '\Bitrix\Rpa\Model\ItemSortTable';
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ItemSort_Result exec()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ItemSort_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_ItemSort fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort_Collection fetchCollection()
	 */
	class EO_ItemSort_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_ItemSort createObject($setDefaultValues = true)
	 * @method \Bitrix\Rpa\Model\EO_ItemSort_Collection createCollection()
	 * @method \Bitrix\Rpa\Model\EO_ItemSort wakeUpObject($row)
	 * @method \Bitrix\Rpa\Model\EO_ItemSort_Collection wakeUpCollection($rows)
	 */
	class EO_ItemSort_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Rpa\Model\ItemHistoryFieldTable:rpa/lib/model/itemhistoryfieldtable.php */
namespace Bitrix\Rpa\Model {
	/**
	 * EO_ItemHistoryField
	 * @see \Bitrix\Rpa\Model\ItemHistoryFieldTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getItemHistoryId()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField setItemHistoryId(\int|\Bitrix\Main\DB\SqlExpression $itemHistoryId)
	 * @method bool hasItemHistoryId()
	 * @method bool isItemHistoryIdFilled()
	 * @method bool isItemHistoryIdChanged()
	 * @method \int remindActualItemHistoryId()
	 * @method \int requireItemHistoryId()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField resetItemHistoryId()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField unsetItemHistoryId()
	 * @method \int fillItemHistoryId()
	 * @method \Bitrix\Rpa\Model\ItemHistory getItemHistory()
	 * @method \Bitrix\Rpa\Model\ItemHistory remindActualItemHistory()
	 * @method \Bitrix\Rpa\Model\ItemHistory requireItemHistory()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField setItemHistory(\Bitrix\Rpa\Model\ItemHistory $object)
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField resetItemHistory()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField unsetItemHistory()
	 * @method bool hasItemHistory()
	 * @method bool isItemHistoryFilled()
	 * @method bool isItemHistoryChanged()
	 * @method \Bitrix\Rpa\Model\ItemHistory fillItemHistory()
	 * @method \string getFieldName()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField setFieldName(\string|\Bitrix\Main\DB\SqlExpression $fieldName)
	 * @method bool hasFieldName()
	 * @method bool isFieldNameFilled()
	 * @method bool isFieldNameChanged()
	 * @method \string remindActualFieldName()
	 * @method \string requireFieldName()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField resetFieldName()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField unsetFieldName()
	 * @method \string fillFieldName()
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
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField set($fieldName, $value)
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField reset($fieldName)
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Rpa\Model\EO_ItemHistoryField wakeUp($data)
	 */
	class EO_ItemHistoryField {
		/* @var \Bitrix\Rpa\Model\ItemHistoryFieldTable */
		static public $dataClass = '\Bitrix\Rpa\Model\ItemHistoryFieldTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * EO_ItemHistoryField_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getItemHistoryIdList()
	 * @method \int[] fillItemHistoryId()
	 * @method \Bitrix\Rpa\Model\ItemHistory[] getItemHistoryList()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection getItemHistoryCollection()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistory_Collection fillItemHistory()
	 * @method \string[] getFieldNameList()
	 * @method \string[] fillFieldName()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Rpa\Model\EO_ItemHistoryField $object)
	 * @method bool has(\Bitrix\Rpa\Model\EO_ItemHistoryField $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField getByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField[] getAll()
	 * @method bool remove(\Bitrix\Rpa\Model\EO_ItemHistoryField $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ItemHistoryField_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Rpa\Model\ItemHistoryFieldTable */
		static public $dataClass = '\Bitrix\Rpa\Model\ItemHistoryFieldTable';
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ItemHistoryField_Result exec()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ItemHistoryField_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection fetchCollection()
	 */
	class EO_ItemHistoryField_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField createObject($setDefaultValues = true)
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection createCollection()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField wakeUpObject($row)
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection wakeUpCollection($rows)
	 */
	class EO_ItemHistoryField_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Rpa\Model\StageToStageTable:rpa/lib/model/stagetostagetable.php */
namespace Bitrix\Rpa\Model {
	/**
	 * EO_StageToStage
	 * @see \Bitrix\Rpa\Model\StageToStageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getStageId()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage setStageId(\int|\Bitrix\Main\DB\SqlExpression $stageId)
	 * @method bool hasStageId()
	 * @method bool isStageIdFilled()
	 * @method bool isStageIdChanged()
	 * @method \int remindActualStageId()
	 * @method \int requireStageId()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage resetStageId()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage unsetStageId()
	 * @method \int fillStageId()
	 * @method \Bitrix\Rpa\Model\Stage getStage()
	 * @method \Bitrix\Rpa\Model\Stage remindActualStage()
	 * @method \Bitrix\Rpa\Model\Stage requireStage()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage setStage(\Bitrix\Rpa\Model\Stage $object)
	 * @method \Bitrix\Rpa\Model\EO_StageToStage resetStage()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage unsetStage()
	 * @method bool hasStage()
	 * @method bool isStageFilled()
	 * @method bool isStageChanged()
	 * @method \Bitrix\Rpa\Model\Stage fillStage()
	 * @method \int getStageToId()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage setStageToId(\int|\Bitrix\Main\DB\SqlExpression $stageToId)
	 * @method bool hasStageToId()
	 * @method bool isStageToIdFilled()
	 * @method bool isStageToIdChanged()
	 * @method \int remindActualStageToId()
	 * @method \int requireStageToId()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage resetStageToId()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage unsetStageToId()
	 * @method \int fillStageToId()
	 * @method \Bitrix\Rpa\Model\Stage getStageTo()
	 * @method \Bitrix\Rpa\Model\Stage remindActualStageTo()
	 * @method \Bitrix\Rpa\Model\Stage requireStageTo()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage setStageTo(\Bitrix\Rpa\Model\Stage $object)
	 * @method \Bitrix\Rpa\Model\EO_StageToStage resetStageTo()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage unsetStageTo()
	 * @method bool hasStageTo()
	 * @method bool isStageToFilled()
	 * @method bool isStageToChanged()
	 * @method \Bitrix\Rpa\Model\Stage fillStageTo()
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
	 * @method \Bitrix\Rpa\Model\EO_StageToStage set($fieldName, $value)
	 * @method \Bitrix\Rpa\Model\EO_StageToStage reset($fieldName)
	 * @method \Bitrix\Rpa\Model\EO_StageToStage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Rpa\Model\EO_StageToStage wakeUp($data)
	 */
	class EO_StageToStage {
		/* @var \Bitrix\Rpa\Model\StageToStageTable */
		static public $dataClass = '\Bitrix\Rpa\Model\StageToStageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * EO_StageToStage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getStageIdList()
	 * @method \int[] fillStageId()
	 * @method \Bitrix\Rpa\Model\Stage[] getStageList()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage_Collection getStageCollection()
	 * @method \Bitrix\Rpa\Model\EO_Stage_Collection fillStage()
	 * @method \int[] getStageToIdList()
	 * @method \int[] fillStageToId()
	 * @method \Bitrix\Rpa\Model\Stage[] getStageToList()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage_Collection getStageToCollection()
	 * @method \Bitrix\Rpa\Model\EO_Stage_Collection fillStageTo()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Rpa\Model\EO_StageToStage $object)
	 * @method bool has(\Bitrix\Rpa\Model\EO_StageToStage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_StageToStage getByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_StageToStage[] getAll()
	 * @method bool remove(\Bitrix\Rpa\Model\EO_StageToStage $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Rpa\Model\EO_StageToStage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Rpa\Model\EO_StageToStage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_StageToStage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Rpa\Model\StageToStageTable */
		static public $dataClass = '\Bitrix\Rpa\Model\StageToStageTable';
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_StageToStage_Result exec()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_StageToStage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_StageToStage fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage_Collection fetchCollection()
	 */
	class EO_StageToStage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_StageToStage createObject($setDefaultValues = true)
	 * @method \Bitrix\Rpa\Model\EO_StageToStage_Collection createCollection()
	 * @method \Bitrix\Rpa\Model\EO_StageToStage wakeUpObject($row)
	 * @method \Bitrix\Rpa\Model\EO_StageToStage_Collection wakeUpCollection($rows)
	 */
	class EO_StageToStage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Rpa\Model\PermissionTable:rpa/lib/model/permissiontable.php */
namespace Bitrix\Rpa\Model {
	/**
	 * EO_Permission
	 * @see \Bitrix\Rpa\Model\PermissionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Rpa\Model\EO_Permission setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getEntity()
	 * @method \Bitrix\Rpa\Model\EO_Permission setEntity(\string|\Bitrix\Main\DB\SqlExpression $entity)
	 * @method bool hasEntity()
	 * @method bool isEntityFilled()
	 * @method bool isEntityChanged()
	 * @method \string remindActualEntity()
	 * @method \string requireEntity()
	 * @method \Bitrix\Rpa\Model\EO_Permission resetEntity()
	 * @method \Bitrix\Rpa\Model\EO_Permission unsetEntity()
	 * @method \string fillEntity()
	 * @method \int getEntityId()
	 * @method \Bitrix\Rpa\Model\EO_Permission setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Rpa\Model\EO_Permission resetEntityId()
	 * @method \Bitrix\Rpa\Model\EO_Permission unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\Rpa\Model\EO_Permission setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\Rpa\Model\EO_Permission resetAccessCode()
	 * @method \Bitrix\Rpa\Model\EO_Permission unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \string getAction()
	 * @method \Bitrix\Rpa\Model\EO_Permission setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\Rpa\Model\EO_Permission resetAction()
	 * @method \Bitrix\Rpa\Model\EO_Permission unsetAction()
	 * @method \string fillAction()
	 * @method \string getPermission()
	 * @method \Bitrix\Rpa\Model\EO_Permission setPermission(\string|\Bitrix\Main\DB\SqlExpression $permission)
	 * @method bool hasPermission()
	 * @method bool isPermissionFilled()
	 * @method bool isPermissionChanged()
	 * @method \string remindActualPermission()
	 * @method \string requirePermission()
	 * @method \Bitrix\Rpa\Model\EO_Permission resetPermission()
	 * @method \Bitrix\Rpa\Model\EO_Permission unsetPermission()
	 * @method \string fillPermission()
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
	 * @method \Bitrix\Rpa\Model\EO_Permission set($fieldName, $value)
	 * @method \Bitrix\Rpa\Model\EO_Permission reset($fieldName)
	 * @method \Bitrix\Rpa\Model\EO_Permission unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Rpa\Model\EO_Permission wakeUp($data)
	 */
	class EO_Permission {
		/* @var \Bitrix\Rpa\Model\PermissionTable */
		static public $dataClass = '\Bitrix\Rpa\Model\PermissionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * EO_Permission_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getEntityList()
	 * @method \string[] fillEntity()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \string[] getActionList()
	 * @method \string[] fillAction()
	 * @method \string[] getPermissionList()
	 * @method \string[] fillPermission()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Rpa\Model\EO_Permission $object)
	 * @method bool has(\Bitrix\Rpa\Model\EO_Permission $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_Permission getByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_Permission[] getAll()
	 * @method bool remove(\Bitrix\Rpa\Model\EO_Permission $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Rpa\Model\EO_Permission_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Rpa\Model\EO_Permission current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Permission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Rpa\Model\PermissionTable */
		static public $dataClass = '\Bitrix\Rpa\Model\PermissionTable';
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Permission_Result exec()
	 * @method \Bitrix\Rpa\Model\EO_Permission fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_Permission_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Permission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_Permission fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_Permission_Collection fetchCollection()
	 */
	class EO_Permission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_Permission createObject($setDefaultValues = true)
	 * @method \Bitrix\Rpa\Model\EO_Permission_Collection createCollection()
	 * @method \Bitrix\Rpa\Model\EO_Permission wakeUpObject($row)
	 * @method \Bitrix\Rpa\Model\EO_Permission_Collection wakeUpCollection($rows)
	 */
	class EO_Permission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Rpa\Model\FieldTable:rpa/lib/model/fieldtable.php */
namespace Bitrix\Rpa\Model {
	/**
	 * EO_Field
	 * @see \Bitrix\Rpa\Model\FieldTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Rpa\Model\EO_Field setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTypeId()
	 * @method \Bitrix\Rpa\Model\EO_Field setTypeId(\int|\Bitrix\Main\DB\SqlExpression $typeId)
	 * @method bool hasTypeId()
	 * @method bool isTypeIdFilled()
	 * @method bool isTypeIdChanged()
	 * @method \int remindActualTypeId()
	 * @method \int requireTypeId()
	 * @method \Bitrix\Rpa\Model\EO_Field resetTypeId()
	 * @method \Bitrix\Rpa\Model\EO_Field unsetTypeId()
	 * @method \int fillTypeId()
	 * @method \int getStageId()
	 * @method \Bitrix\Rpa\Model\EO_Field setStageId(\int|\Bitrix\Main\DB\SqlExpression $stageId)
	 * @method bool hasStageId()
	 * @method bool isStageIdFilled()
	 * @method bool isStageIdChanged()
	 * @method \int remindActualStageId()
	 * @method \int requireStageId()
	 * @method \Bitrix\Rpa\Model\EO_Field resetStageId()
	 * @method \Bitrix\Rpa\Model\EO_Field unsetStageId()
	 * @method \int fillStageId()
	 * @method \Bitrix\Rpa\Model\Stage getStage()
	 * @method \Bitrix\Rpa\Model\Stage remindActualStage()
	 * @method \Bitrix\Rpa\Model\Stage requireStage()
	 * @method \Bitrix\Rpa\Model\EO_Field setStage(\Bitrix\Rpa\Model\Stage $object)
	 * @method \Bitrix\Rpa\Model\EO_Field resetStage()
	 * @method \Bitrix\Rpa\Model\EO_Field unsetStage()
	 * @method bool hasStage()
	 * @method bool isStageFilled()
	 * @method bool isStageChanged()
	 * @method \Bitrix\Rpa\Model\Stage fillStage()
	 * @method \string getField()
	 * @method \Bitrix\Rpa\Model\EO_Field setField(\string|\Bitrix\Main\DB\SqlExpression $field)
	 * @method bool hasField()
	 * @method bool isFieldFilled()
	 * @method bool isFieldChanged()
	 * @method \string remindActualField()
	 * @method \string requireField()
	 * @method \Bitrix\Rpa\Model\EO_Field resetField()
	 * @method \Bitrix\Rpa\Model\EO_Field unsetField()
	 * @method \string fillField()
	 * @method \string getVisibility()
	 * @method \Bitrix\Rpa\Model\EO_Field setVisibility(\string|\Bitrix\Main\DB\SqlExpression $visibility)
	 * @method bool hasVisibility()
	 * @method bool isVisibilityFilled()
	 * @method bool isVisibilityChanged()
	 * @method \string remindActualVisibility()
	 * @method \string requireVisibility()
	 * @method \Bitrix\Rpa\Model\EO_Field resetVisibility()
	 * @method \Bitrix\Rpa\Model\EO_Field unsetVisibility()
	 * @method \string fillVisibility()
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
	 * @method \Bitrix\Rpa\Model\EO_Field set($fieldName, $value)
	 * @method \Bitrix\Rpa\Model\EO_Field reset($fieldName)
	 * @method \Bitrix\Rpa\Model\EO_Field unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Rpa\Model\EO_Field wakeUp($data)
	 */
	class EO_Field {
		/* @var \Bitrix\Rpa\Model\FieldTable */
		static public $dataClass = '\Bitrix\Rpa\Model\FieldTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * EO_Field_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTypeIdList()
	 * @method \int[] fillTypeId()
	 * @method \int[] getStageIdList()
	 * @method \int[] fillStageId()
	 * @method \Bitrix\Rpa\Model\Stage[] getStageList()
	 * @method \Bitrix\Rpa\Model\EO_Field_Collection getStageCollection()
	 * @method \Bitrix\Rpa\Model\EO_Stage_Collection fillStage()
	 * @method \string[] getFieldList()
	 * @method \string[] fillField()
	 * @method \string[] getVisibilityList()
	 * @method \string[] fillVisibility()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Rpa\Model\EO_Field $object)
	 * @method bool has(\Bitrix\Rpa\Model\EO_Field $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_Field getByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\EO_Field[] getAll()
	 * @method bool remove(\Bitrix\Rpa\Model\EO_Field $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Rpa\Model\EO_Field_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Rpa\Model\EO_Field current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Field_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Rpa\Model\FieldTable */
		static public $dataClass = '\Bitrix\Rpa\Model\FieldTable';
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Field_Result exec()
	 * @method \Bitrix\Rpa\Model\EO_Field fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_Field_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Field_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_Field fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_Field_Collection fetchCollection()
	 */
	class EO_Field_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Rpa\Model\EO_Field createObject($setDefaultValues = true)
	 * @method \Bitrix\Rpa\Model\EO_Field_Collection createCollection()
	 * @method \Bitrix\Rpa\Model\EO_Field wakeUpObject($row)
	 * @method \Bitrix\Rpa\Model\EO_Field_Collection wakeUpCollection($rows)
	 */
	class EO_Field_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Rpa\Model\ItemHistoryTable:rpa/lib/model/itemhistorytable.php */
namespace Bitrix\Rpa\Model {
	/**
	 * ItemHistory
	 * @see \Bitrix\Rpa\Model\ItemHistoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Rpa\Model\ItemHistory setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getItemId()
	 * @method \Bitrix\Rpa\Model\ItemHistory setItemId(\int|\Bitrix\Main\DB\SqlExpression $itemId)
	 * @method bool hasItemId()
	 * @method bool isItemIdFilled()
	 * @method bool isItemIdChanged()
	 * @method \int remindActualItemId()
	 * @method \int requireItemId()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetItemId()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetItemId()
	 * @method \int fillItemId()
	 * @method \int getTypeId()
	 * @method \Bitrix\Rpa\Model\ItemHistory setTypeId(\int|\Bitrix\Main\DB\SqlExpression $typeId)
	 * @method bool hasTypeId()
	 * @method bool isTypeIdFilled()
	 * @method bool isTypeIdChanged()
	 * @method \int remindActualTypeId()
	 * @method \int requireTypeId()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetTypeId()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetTypeId()
	 * @method \int fillTypeId()
	 * @method \Bitrix\Main\Type\DateTime getCreatedTime()
	 * @method \Bitrix\Rpa\Model\ItemHistory setCreatedTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdTime)
	 * @method bool hasCreatedTime()
	 * @method bool isCreatedTimeFilled()
	 * @method bool isCreatedTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedTime()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetCreatedTime()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetCreatedTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedTime()
	 * @method \int getStageId()
	 * @method \Bitrix\Rpa\Model\ItemHistory setStageId(\int|\Bitrix\Main\DB\SqlExpression $stageId)
	 * @method bool hasStageId()
	 * @method bool isStageIdFilled()
	 * @method bool isStageIdChanged()
	 * @method \int remindActualStageId()
	 * @method \int requireStageId()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetStageId()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetStageId()
	 * @method \int fillStageId()
	 * @method \int getNewStageId()
	 * @method \Bitrix\Rpa\Model\ItemHistory setNewStageId(\int|\Bitrix\Main\DB\SqlExpression $newStageId)
	 * @method bool hasNewStageId()
	 * @method bool isNewStageIdFilled()
	 * @method bool isNewStageIdChanged()
	 * @method \int remindActualNewStageId()
	 * @method \int requireNewStageId()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetNewStageId()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetNewStageId()
	 * @method \int fillNewStageId()
	 * @method \int getUserId()
	 * @method \Bitrix\Rpa\Model\ItemHistory setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetUserId()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getAction()
	 * @method \Bitrix\Rpa\Model\ItemHistory setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetAction()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetAction()
	 * @method \string fillAction()
	 * @method \string getScope()
	 * @method \Bitrix\Rpa\Model\ItemHistory setScope(\string|\Bitrix\Main\DB\SqlExpression $scope)
	 * @method bool hasScope()
	 * @method bool isScopeFilled()
	 * @method bool isScopeChanged()
	 * @method \string remindActualScope()
	 * @method \string requireScope()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetScope()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetScope()
	 * @method \string fillScope()
	 * @method \int getTaskId()
	 * @method \Bitrix\Rpa\Model\ItemHistory setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetTaskId()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection getFields()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection requireFields()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection fillFields()
	 * @method bool hasFields()
	 * @method bool isFieldsFilled()
	 * @method bool isFieldsChanged()
	 * @method void addToFields(\Bitrix\Rpa\Model\EO_ItemHistoryField $itemHistoryField)
	 * @method void removeFromFields(\Bitrix\Rpa\Model\EO_ItemHistoryField $itemHistoryField)
	 * @method void removeAllFields()
	 * @method \Bitrix\Rpa\Model\ItemHistory resetFields()
	 * @method \Bitrix\Rpa\Model\ItemHistory unsetFields()
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
	 * @method \Bitrix\Rpa\Model\ItemHistory set($fieldName, $value)
	 * @method \Bitrix\Rpa\Model\ItemHistory reset($fieldName)
	 * @method \Bitrix\Rpa\Model\ItemHistory unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Rpa\Model\ItemHistory wakeUp($data)
	 */
	class EO_ItemHistory {
		/* @var \Bitrix\Rpa\Model\ItemHistoryTable */
		static public $dataClass = '\Bitrix\Rpa\Model\ItemHistoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * EO_ItemHistory_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getItemIdList()
	 * @method \int[] fillItemId()
	 * @method \int[] getTypeIdList()
	 * @method \int[] fillTypeId()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedTime()
	 * @method \int[] getStageIdList()
	 * @method \int[] fillStageId()
	 * @method \int[] getNewStageIdList()
	 * @method \int[] fillNewStageId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getActionList()
	 * @method \string[] fillAction()
	 * @method \string[] getScopeList()
	 * @method \string[] fillScope()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection[] getFieldsList()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection getFieldsCollection()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection fillFields()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Rpa\Model\ItemHistory $object)
	 * @method bool has(\Bitrix\Rpa\Model\ItemHistory $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\ItemHistory getByPrimary($primary)
	 * @method \Bitrix\Rpa\Model\ItemHistory[] getAll()
	 * @method bool remove(\Bitrix\Rpa\Model\ItemHistory $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Rpa\Model\EO_ItemHistory_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Rpa\Model\ItemHistory current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ItemHistory_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Rpa\Model\ItemHistoryTable */
		static public $dataClass = '\Bitrix\Rpa\Model\ItemHistoryTable';
	}
}
namespace Bitrix\Rpa\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ItemHistory_Result exec()
	 * @method \Bitrix\Rpa\Model\ItemHistory fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistory_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ItemHistory_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Rpa\Model\ItemHistory fetchObject()
	 * @method \Bitrix\Rpa\Model\EO_ItemHistory_Collection fetchCollection()
	 */
	class EO_ItemHistory_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Rpa\Model\ItemHistory createObject($setDefaultValues = true)
	 * @method \Bitrix\Rpa\Model\EO_ItemHistory_Collection createCollection()
	 * @method \Bitrix\Rpa\Model\ItemHistory wakeUpObject($row)
	 * @method \Bitrix\Rpa\Model\EO_ItemHistory_Collection wakeUpCollection($rows)
	 */
	class EO_ItemHistory_Entity extends \Bitrix\Main\ORM\Entity {}
}