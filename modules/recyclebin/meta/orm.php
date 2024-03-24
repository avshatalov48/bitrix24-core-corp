<?php

/* ORMENTITYANNOTATION:Bitrix\Recyclebin\Internals\Models\RecyclebinTable:recyclebin/lib/internals/models/recyclebin.php */
namespace Bitrix\Recyclebin\Internals\Models {
	/**
	 * RecyclebinEntity
	 * @see \Bitrix\Recyclebin\Internals\Models\RecyclebinTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity resetName()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity unsetName()
	 * @method \string fillName()
	 * @method \string getSiteId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity setSiteId(\string|\Bitrix\Main\DB\SqlExpression $siteId)
	 * @method bool hasSiteId()
	 * @method bool isSiteIdFilled()
	 * @method bool isSiteIdChanged()
	 * @method \string remindActualSiteId()
	 * @method \string requireSiteId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity resetSiteId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity unsetSiteId()
	 * @method \string fillSiteId()
	 * @method \string getModuleId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity resetModuleId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity unsetModuleId()
	 * @method \string fillModuleId()
	 * @method \string getEntityId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity setEntityId(\string|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \string remindActualEntityId()
	 * @method \string requireEntityId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity resetEntityId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity unsetEntityId()
	 * @method \string fillEntityId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity resetEntityType()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \Bitrix\Main\Type\DateTime getTimestamp()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity setTimestamp(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestamp)
	 * @method bool hasTimestamp()
	 * @method bool isTimestampFilled()
	 * @method bool isTimestampChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestamp()
	 * @method \Bitrix\Main\Type\DateTime requireTimestamp()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity resetTimestamp()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity unsetTimestamp()
	 * @method \Bitrix\Main\Type\DateTime fillTimestamp()
	 * @method \int getUserId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity resetUserId()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity resetUser()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity unsetUser()
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
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity set($fieldName, $value)
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity reset($fieldName)
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity wakeUp($data)
	 */
	class EO_Recyclebin {
		/* @var \Bitrix\Recyclebin\Internals\Models\RecyclebinTable */
		static public $dataClass = '\Bitrix\Recyclebin\Internals\Models\RecyclebinTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Recyclebin\Internals\Models {
	/**
	 * RecyclebinEntityCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getSiteIdList()
	 * @method \string[] fillSiteId()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method \string[] getEntityIdList()
	 * @method \string[] fillEntityId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestamp()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Recyclebin\Internals\Models\RecyclebinEntity $object)
	 * @method bool has(\Bitrix\Recyclebin\Internals\Models\RecyclebinEntity $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity getByPrimary($primary)
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity[] getAll()
	 * @method bool remove(\Bitrix\Recyclebin\Internals\Models\RecyclebinEntity $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method RecyclebinEntityCollection merge(?RecyclebinEntityCollection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Recyclebin_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Recyclebin\Internals\Models\RecyclebinTable */
		static public $dataClass = '\Bitrix\Recyclebin\Internals\Models\RecyclebinTable';
	}
}
namespace Bitrix\Recyclebin\Internals\Models {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Recyclebin_Result exec()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity fetchObject()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Recyclebin_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity fetchObject()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection fetchCollection()
	 */
	class EO_Recyclebin_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity createObject($setDefaultValues = true)
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection createCollection()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity wakeUpObject($row)
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection wakeUpCollection($rows)
	 */
	class EO_Recyclebin_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable:recyclebin/lib/internals/models/recyclebindata.php */
namespace Bitrix\Recyclebin\Internals\Models {
	/**
	 * EO_RecyclebinData
	 * @see \Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRecyclebinId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData setRecyclebinId(\int|\Bitrix\Main\DB\SqlExpression $recyclebinId)
	 * @method bool hasRecyclebinId()
	 * @method bool isRecyclebinIdFilled()
	 * @method bool isRecyclebinIdChanged()
	 * @method \int remindActualRecyclebinId()
	 * @method \int requireRecyclebinId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData resetRecyclebinId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData unsetRecyclebinId()
	 * @method \int fillRecyclebinId()
	 * @method \string getAction()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData resetAction()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData unsetAction()
	 * @method \string fillAction()
	 * @method \string getData()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData setData(\string|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method \string remindActualData()
	 * @method \string requireData()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData resetData()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData unsetData()
	 * @method \string fillData()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity getRecyclebin()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity remindActualRecyclebin()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity requireRecyclebin()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData setRecyclebin(\Bitrix\Recyclebin\Internals\Models\RecyclebinEntity $object)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData resetRecyclebin()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData unsetRecyclebin()
	 * @method bool hasRecyclebin()
	 * @method bool isRecyclebinFilled()
	 * @method bool isRecyclebinChanged()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity fillRecyclebin()
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
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData set($fieldName, $value)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData reset($fieldName)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData wakeUp($data)
	 */
	class EO_RecyclebinData {
		/* @var \Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable */
		static public $dataClass = '\Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Recyclebin\Internals\Models {
	/**
	 * EO_RecyclebinData_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRecyclebinIdList()
	 * @method \int[] fillRecyclebinId()
	 * @method \string[] getActionList()
	 * @method \string[] fillAction()
	 * @method \string[] getDataList()
	 * @method \string[] fillData()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity[] getRecyclebinList()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection getRecyclebinCollection()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection fillRecyclebin()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData $object)
	 * @method bool has(\Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData getByPrimary($primary)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData[] getAll()
	 * @method bool remove(\Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_RecyclebinData_Collection merge(?EO_RecyclebinData_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_RecyclebinData_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable */
		static public $dataClass = '\Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable';
	}
}
namespace Bitrix\Recyclebin\Internals\Models {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RecyclebinData_Result exec()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData fetchObject()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RecyclebinData_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData fetchObject()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection fetchCollection()
	 */
	class EO_RecyclebinData_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData createObject($setDefaultValues = true)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection createCollection()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData wakeUpObject($row)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection wakeUpCollection($rows)
	 */
	class EO_RecyclebinData_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Recyclebin\Internals\Models\RecyclebinFileTable:recyclebin/lib/internals/models/recyclebinfile.php */
namespace Bitrix\Recyclebin\Internals\Models {
	/**
	 * EO_RecyclebinFile
	 * @see \Bitrix\Recyclebin\Internals\Models\RecyclebinFileTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRecyclebinId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile setRecyclebinId(\int|\Bitrix\Main\DB\SqlExpression $recyclebinId)
	 * @method bool hasRecyclebinId()
	 * @method bool isRecyclebinIdFilled()
	 * @method bool isRecyclebinIdChanged()
	 * @method \int remindActualRecyclebinId()
	 * @method \int requireRecyclebinId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile resetRecyclebinId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile unsetRecyclebinId()
	 * @method \int fillRecyclebinId()
	 * @method \int getFileId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile resetFileId()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile unsetFileId()
	 * @method \int fillFileId()
	 * @method \string getStorageType()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile setStorageType(\string|\Bitrix\Main\DB\SqlExpression $storageType)
	 * @method bool hasStorageType()
	 * @method bool isStorageTypeFilled()
	 * @method bool isStorageTypeChanged()
	 * @method \string remindActualStorageType()
	 * @method \string requireStorageType()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile resetStorageType()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile unsetStorageType()
	 * @method \string fillStorageType()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity getRecyclebin()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity remindActualRecyclebin()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity requireRecyclebin()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile setRecyclebin(\Bitrix\Recyclebin\Internals\Models\RecyclebinEntity $object)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile resetRecyclebin()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile unsetRecyclebin()
	 * @method bool hasRecyclebin()
	 * @method bool isRecyclebinFilled()
	 * @method bool isRecyclebinChanged()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity fillRecyclebin()
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
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile set($fieldName, $value)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile reset($fieldName)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile wakeUp($data)
	 */
	class EO_RecyclebinFile {
		/* @var \Bitrix\Recyclebin\Internals\Models\RecyclebinFileTable */
		static public $dataClass = '\Bitrix\Recyclebin\Internals\Models\RecyclebinFileTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Recyclebin\Internals\Models {
	/**
	 * EO_RecyclebinFile_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRecyclebinIdList()
	 * @method \int[] fillRecyclebinId()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \string[] getStorageTypeList()
	 * @method \string[] fillStorageType()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity[] getRecyclebinList()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection getRecyclebinCollection()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection fillRecyclebin()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile $object)
	 * @method bool has(\Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile getByPrimary($primary)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile[] getAll()
	 * @method bool remove(\Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_RecyclebinFile_Collection merge(?EO_RecyclebinFile_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_RecyclebinFile_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Recyclebin\Internals\Models\RecyclebinFileTable */
		static public $dataClass = '\Bitrix\Recyclebin\Internals\Models\RecyclebinFileTable';
	}
}
namespace Bitrix\Recyclebin\Internals\Models {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RecyclebinFile_Result exec()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile fetchObject()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RecyclebinFile_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile fetchObject()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection fetchCollection()
	 */
	class EO_RecyclebinFile_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile createObject($setDefaultValues = true)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection createCollection()
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile wakeUpObject($row)
	 * @method \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection wakeUpCollection($rows)
	 */
	class EO_RecyclebinFile_Entity extends \Bitrix\Main\ORM\Entity {}
}