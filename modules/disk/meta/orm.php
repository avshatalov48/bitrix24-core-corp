<?php

/* ORMENTITYANNOTATION:Bitrix\Disk\Document\Models\RestrictionLogTable:disk/lib/document/onlyoffice/models/restrictionlogtable.php */
namespace Bitrix\Disk\Document\Models {
	/**
	 * RestrictionLog
	 * @see \Bitrix\Disk\Document\Models\RestrictionLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog resetUserId()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getExternalHash()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog setExternalHash(\string|\Bitrix\Main\DB\SqlExpression $externalHash)
	 * @method bool hasExternalHash()
	 * @method bool isExternalHashFilled()
	 * @method bool isExternalHashChanged()
	 * @method \string remindActualExternalHash()
	 * @method \string requireExternalHash()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog resetExternalHash()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog unsetExternalHash()
	 * @method \string fillExternalHash()
	 * @method \int getStatus()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog resetStatus()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog unsetStatus()
	 * @method \int fillStatus()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog resetCreateTime()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog resetUpdateTime()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
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
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog set($fieldName, $value)
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog reset($fieldName)
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Document\Models\RestrictionLog wakeUp($data)
	 */
	class EO_RestrictionLog {
		/* @var \Bitrix\Disk\Document\Models\RestrictionLogTable */
		static public $dataClass = '\Bitrix\Disk\Document\Models\RestrictionLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Document\Models {
	/**
	 * EO_RestrictionLog_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getExternalHashList()
	 * @method \string[] fillExternalHash()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Document\Models\RestrictionLog $object)
	 * @method bool has(\Bitrix\Disk\Document\Models\RestrictionLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog getByPrimary($primary)
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog[] getAll()
	 * @method bool remove(\Bitrix\Disk\Document\Models\RestrictionLog $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Document\Models\EO_RestrictionLog_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RestrictionLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Document\Models\RestrictionLogTable */
		static public $dataClass = '\Bitrix\Disk\Document\Models\RestrictionLogTable';
	}
}
namespace Bitrix\Disk\Document\Models {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RestrictionLog_Result exec()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog fetchObject()
	 * @method \Bitrix\Disk\Document\Models\EO_RestrictionLog_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RestrictionLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog fetchObject()
	 * @method \Bitrix\Disk\Document\Models\EO_RestrictionLog_Collection fetchCollection()
	 */
	class EO_RestrictionLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Document\Models\EO_RestrictionLog_Collection createCollection()
	 * @method \Bitrix\Disk\Document\Models\RestrictionLog wakeUpObject($row)
	 * @method \Bitrix\Disk\Document\Models\EO_RestrictionLog_Collection wakeUpCollection($rows)
	 */
	class EO_RestrictionLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Document\Models\DocumentInfoTable:disk/lib/document/onlyoffice/models/documentinfotable.php */
namespace Bitrix\Disk\Document\Models {
	/**
	 * EO_DocumentInfo
	 * @see \Bitrix\Disk\Document\Models\DocumentInfoTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getExternalHash()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo setExternalHash(\string|\Bitrix\Main\DB\SqlExpression $externalHash)
	 * @method bool hasExternalHash()
	 * @method bool isExternalHashFilled()
	 * @method bool isExternalHashChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo resetObjectId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \int getVersionId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo setVersionId(\int|\Bitrix\Main\DB\SqlExpression $versionId)
	 * @method bool hasVersionId()
	 * @method bool isVersionIdFilled()
	 * @method bool isVersionIdChanged()
	 * @method \int remindActualVersionId()
	 * @method \int requireVersionId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo resetVersionId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo unsetVersionId()
	 * @method \int fillVersionId()
	 * @method \int getOwnerId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo setOwnerId(\int|\Bitrix\Main\DB\SqlExpression $ownerId)
	 * @method bool hasOwnerId()
	 * @method bool isOwnerIdFilled()
	 * @method bool isOwnerIdChanged()
	 * @method \int remindActualOwnerId()
	 * @method \int requireOwnerId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo resetOwnerId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo unsetOwnerId()
	 * @method \int fillOwnerId()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo resetCreateTime()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo resetUpdateTime()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
	 * @method \int getUsers()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo setUsers(\int|\Bitrix\Main\DB\SqlExpression $users)
	 * @method bool hasUsers()
	 * @method bool isUsersFilled()
	 * @method bool isUsersChanged()
	 * @method \int remindActualUsers()
	 * @method \int requireUsers()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo resetUsers()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo unsetUsers()
	 * @method \int fillUsers()
	 * @method \int getContentStatus()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo setContentStatus(\int|\Bitrix\Main\DB\SqlExpression $contentStatus)
	 * @method bool hasContentStatus()
	 * @method bool isContentStatusFilled()
	 * @method bool isContentStatusChanged()
	 * @method \int remindActualContentStatus()
	 * @method \int requireContentStatus()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo resetContentStatus()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo unsetContentStatus()
	 * @method \int fillContentStatus()
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
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo set($fieldName, $value)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo reset($fieldName)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Document\Models\EO_DocumentInfo wakeUp($data)
	 */
	class EO_DocumentInfo {
		/* @var \Bitrix\Disk\Document\Models\DocumentInfoTable */
		static public $dataClass = '\Bitrix\Disk\Document\Models\DocumentInfoTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Document\Models {
	/**
	 * EO_DocumentInfo_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getExternalHashList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \int[] getVersionIdList()
	 * @method \int[] fillVersionId()
	 * @method \int[] getOwnerIdList()
	 * @method \int[] fillOwnerId()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 * @method \int[] getUsersList()
	 * @method \int[] fillUsers()
	 * @method \int[] getContentStatusList()
	 * @method \int[] fillContentStatus()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Document\Models\EO_DocumentInfo $object)
	 * @method bool has(\Bitrix\Disk\Document\Models\EO_DocumentInfo $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo getByPrimary($primary)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo[] getAll()
	 * @method bool remove(\Bitrix\Disk\Document\Models\EO_DocumentInfo $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Document\Models\EO_DocumentInfo_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_DocumentInfo_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Document\Models\DocumentInfoTable */
		static public $dataClass = '\Bitrix\Disk\Document\Models\DocumentInfoTable';
	}
}
namespace Bitrix\Disk\Document\Models {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DocumentInfo_Result exec()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo fetchObject()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_DocumentInfo_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo fetchObject()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo_Collection fetchCollection()
	 */
	class EO_DocumentInfo_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo_Collection createCollection()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo wakeUpObject($row)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentInfo_Collection wakeUpCollection($rows)
	 */
	class EO_DocumentInfo_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Document\Models\DocumentSessionTable:disk/lib/document/onlyoffice/models/documentsessiontable.php */
namespace Bitrix\Disk\Document\Models {
	/**
	 * EO_DocumentSession
	 * @see \Bitrix\Disk\Document\Models\DocumentSessionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetObjectId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \int getVersionId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setVersionId(\int|\Bitrix\Main\DB\SqlExpression $versionId)
	 * @method bool hasVersionId()
	 * @method bool isVersionIdFilled()
	 * @method bool isVersionIdChanged()
	 * @method \int remindActualVersionId()
	 * @method \int requireVersionId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetVersionId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetVersionId()
	 * @method \int fillVersionId()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetUserId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getOwnerId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setOwnerId(\int|\Bitrix\Main\DB\SqlExpression $ownerId)
	 * @method bool hasOwnerId()
	 * @method bool isOwnerIdFilled()
	 * @method bool isOwnerIdChanged()
	 * @method \int remindActualOwnerId()
	 * @method \int requireOwnerId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetOwnerId()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetOwnerId()
	 * @method \int fillOwnerId()
	 * @method \boolean getIsExclusive()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setIsExclusive(\boolean|\Bitrix\Main\DB\SqlExpression $isExclusive)
	 * @method bool hasIsExclusive()
	 * @method bool isIsExclusiveFilled()
	 * @method bool isIsExclusiveChanged()
	 * @method \boolean remindActualIsExclusive()
	 * @method \boolean requireIsExclusive()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetIsExclusive()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetIsExclusive()
	 * @method \boolean fillIsExclusive()
	 * @method \string getExternalHash()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setExternalHash(\string|\Bitrix\Main\DB\SqlExpression $externalHash)
	 * @method bool hasExternalHash()
	 * @method bool isExternalHashFilled()
	 * @method bool isExternalHashChanged()
	 * @method \string remindActualExternalHash()
	 * @method \string requireExternalHash()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetExternalHash()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetExternalHash()
	 * @method \string fillExternalHash()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetCreateTime()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \int getType()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setType(\int|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \int remindActualType()
	 * @method \int requireType()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetType()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetType()
	 * @method \int fillType()
	 * @method \int getStatus()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetStatus()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetStatus()
	 * @method \int fillStatus()
	 * @method \string getContext()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession setContext(\string|\Bitrix\Main\DB\SqlExpression $context)
	 * @method bool hasContext()
	 * @method bool isContextFilled()
	 * @method bool isContextChanged()
	 * @method \string remindActualContext()
	 * @method \string requireContext()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession resetContext()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unsetContext()
	 * @method \string fillContext()
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
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession set($fieldName, $value)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession reset($fieldName)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Document\Models\EO_DocumentSession wakeUp($data)
	 */
	class EO_DocumentSession {
		/* @var \Bitrix\Disk\Document\Models\DocumentSessionTable */
		static public $dataClass = '\Bitrix\Disk\Document\Models\DocumentSessionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Document\Models {
	/**
	 * EO_DocumentSession_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \int[] getVersionIdList()
	 * @method \int[] fillVersionId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getOwnerIdList()
	 * @method \int[] fillOwnerId()
	 * @method \boolean[] getIsExclusiveList()
	 * @method \boolean[] fillIsExclusive()
	 * @method \string[] getExternalHashList()
	 * @method \string[] fillExternalHash()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \int[] getTypeList()
	 * @method \int[] fillType()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \string[] getContextList()
	 * @method \string[] fillContext()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Document\Models\EO_DocumentSession $object)
	 * @method bool has(\Bitrix\Disk\Document\Models\EO_DocumentSession $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession getByPrimary($primary)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession[] getAll()
	 * @method bool remove(\Bitrix\Disk\Document\Models\EO_DocumentSession $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Document\Models\EO_DocumentSession_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_DocumentSession_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Document\Models\DocumentSessionTable */
		static public $dataClass = '\Bitrix\Disk\Document\Models\DocumentSessionTable';
	}
}
namespace Bitrix\Disk\Document\Models {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DocumentSession_Result exec()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession fetchObject()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_DocumentSession_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession fetchObject()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession_Collection fetchCollection()
	 */
	class EO_DocumentSession_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession_Collection createCollection()
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession wakeUpObject($row)
	 * @method \Bitrix\Disk\Document\Models\EO_DocumentSession_Collection wakeUpCollection($rows)
	 */
	class EO_DocumentSession_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\DeletedLogV2Table:disk/lib/internals/deletedlogv2.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_DeletedLogV2
	 * @see \Bitrix\Disk\Internals\DeletedLogV2Table
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 resetUserId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getStorageId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 setStorageId(\int|\Bitrix\Main\DB\SqlExpression $storageId)
	 * @method bool hasStorageId()
	 * @method bool isStorageIdFilled()
	 * @method bool isStorageIdChanged()
	 * @method \int remindActualStorageId()
	 * @method \int requireStorageId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 resetStorageId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 unsetStorageId()
	 * @method \int fillStorageId()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \string getType()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 resetType()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 unsetType()
	 * @method \string fillType()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
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
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_DeletedLogV2 wakeUp($data)
	 */
	class EO_DeletedLogV2 {
		/* @var \Bitrix\Disk\Internals\DeletedLogV2Table */
		static public $dataClass = '\Bitrix\Disk\Internals\DeletedLogV2Table';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_DeletedLogV2_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getStorageIdList()
	 * @method \int[] fillStorageId()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_DeletedLogV2 $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_DeletedLogV2 $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_DeletedLogV2 $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_DeletedLogV2_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_DeletedLogV2_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\DeletedLogV2Table */
		static public $dataClass = '\Bitrix\Disk\Internals\DeletedLogV2Table';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DeletedLogV2_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_DeletedLogV2_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2_Collection fetchCollection()
	 */
	class EO_DeletedLogV2_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2 wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLogV2_Collection wakeUpCollection($rows)
	 */
	class EO_DeletedLogV2_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\ObjectSaveIndexTable:disk/lib/internals/objectsaveindextable.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ObjectSaveIndex
	 * @see \Bitrix\Disk\Internals\ObjectSaveIndexTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getSearchIndex()
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex setSearchIndex(\string|\Bitrix\Main\DB\SqlExpression $searchIndex)
	 * @method bool hasSearchIndex()
	 * @method bool isSearchIndexFilled()
	 * @method bool isSearchIndexChanged()
	 * @method \string remindActualSearchIndex()
	 * @method \string requireSearchIndex()
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex resetSearchIndex()
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex unsetSearchIndex()
	 * @method \string fillSearchIndex()
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
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_ObjectSaveIndex wakeUp($data)
	 */
	class EO_ObjectSaveIndex {
		/* @var \Bitrix\Disk\Internals\ObjectSaveIndexTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectSaveIndexTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ObjectSaveIndex_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getSearchIndexList()
	 * @method \string[] fillSearchIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_ObjectSaveIndex $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_ObjectSaveIndex $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_ObjectSaveIndex $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_ObjectSaveIndex_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ObjectSaveIndex_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\ObjectSaveIndexTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectSaveIndexTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ObjectSaveIndex_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ObjectSaveIndex_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex_Collection fetchCollection()
	 */
	class EO_ObjectSaveIndex_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_ObjectSaveIndex_Collection wakeUpCollection($rows)
	 */
	class EO_ObjectSaveIndex_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\ObjectTable:disk/lib/internals/object.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Object
	 * @see \Bitrix\Disk\Internals\ObjectTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_Object setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Disk\Internals\EO_Object setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Disk\Internals\EO_Object resetName()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetName()
	 * @method \string fillName()
	 * @method \string getType()
	 * @method \Bitrix\Disk\Internals\EO_Object setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Disk\Internals\EO_Object resetType()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetType()
	 * @method \string fillType()
	 * @method \string getCode()
	 * @method \Bitrix\Disk\Internals\EO_Object setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Disk\Internals\EO_Object resetCode()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetCode()
	 * @method \string fillCode()
	 * @method \string getXmlId()
	 * @method \Bitrix\Disk\Internals\EO_Object setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Disk\Internals\EO_Object resetXmlId()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \int getStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Object setStorageId(\int|\Bitrix\Main\DB\SqlExpression $storageId)
	 * @method bool hasStorageId()
	 * @method bool isStorageIdFilled()
	 * @method bool isStorageIdChanged()
	 * @method \int remindActualStorageId()
	 * @method \int requireStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Object resetStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetStorageId()
	 * @method \int fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage getStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage remindActualStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage requireStorage()
	 * @method \Bitrix\Disk\Internals\EO_Object setStorage(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetStorage()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetStorage()
	 * @method bool hasStorage()
	 * @method bool isStorageFilled()
	 * @method bool isStorageChanged()
	 * @method \Bitrix\Disk\Internals\EO_Storage fillStorage()
	 * @method \int getRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object setRealObjectId(\int|\Bitrix\Main\DB\SqlExpression $realObjectId)
	 * @method bool hasRealObjectId()
	 * @method bool isRealObjectIdFilled()
	 * @method bool isRealObjectIdChanged()
	 * @method \int remindActualRealObjectId()
	 * @method \int requireRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object resetRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetRealObjectId()
	 * @method \int fillRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object getRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object setRealObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetRealObject()
	 * @method bool hasRealObject()
	 * @method bool isRealObjectFilled()
	 * @method bool isRealObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock getLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock remindActualLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock requireLock()
	 * @method \Bitrix\Disk\Internals\EO_Object setLock(\Bitrix\Disk\Internals\EO_ObjectLock $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetLock()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetLock()
	 * @method bool hasLock()
	 * @method bool isLockFilled()
	 * @method bool isLockChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock fillLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl getTtl()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl remindActualTtl()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl requireTtl()
	 * @method \Bitrix\Disk\Internals\EO_Object setTtl(\Bitrix\Disk\Internals\EO_ObjectTtl $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetTtl()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetTtl()
	 * @method bool hasTtl()
	 * @method bool isTtlFilled()
	 * @method bool isTtlChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl fillTtl()
	 * @method \int getParentId()
	 * @method \Bitrix\Disk\Internals\EO_Object setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Disk\Internals\EO_Object resetParentId()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetParentId()
	 * @method \int fillParentId()
	 * @method \string getContentProvider()
	 * @method \Bitrix\Disk\Internals\EO_Object setContentProvider(\string|\Bitrix\Main\DB\SqlExpression $contentProvider)
	 * @method bool hasContentProvider()
	 * @method bool isContentProviderFilled()
	 * @method bool isContentProviderChanged()
	 * @method \string remindActualContentProvider()
	 * @method \string requireContentProvider()
	 * @method \Bitrix\Disk\Internals\EO_Object resetContentProvider()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetContentProvider()
	 * @method \string fillContentProvider()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Object setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Object resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Object setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Object resetUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime getSyncUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Object setSyncUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $syncUpdateTime)
	 * @method bool hasSyncUpdateTime()
	 * @method bool isSyncUpdateTimeFilled()
	 * @method bool isSyncUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireSyncUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Object resetSyncUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime getDeleteTime()
	 * @method \Bitrix\Disk\Internals\EO_Object setDeleteTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $deleteTime)
	 * @method bool hasDeleteTime()
	 * @method bool isDeleteTimeFilled()
	 * @method bool isDeleteTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDeleteTime()
	 * @method \Bitrix\Main\Type\DateTime requireDeleteTime()
	 * @method \Bitrix\Disk\Internals\EO_Object resetDeleteTime()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetDeleteTime()
	 * @method \Bitrix\Main\Type\DateTime fillDeleteTime()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Object setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Object resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\EO_User getCreateUser()
	 * @method \Bitrix\Main\EO_User remindActualCreateUser()
	 * @method \Bitrix\Main\EO_User requireCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_Object setCreateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetCreateUser()
	 * @method bool hasCreateUser()
	 * @method bool isCreateUserFilled()
	 * @method bool isCreateUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreateUser()
	 * @method \int getUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Object setUpdatedBy(\int|\Bitrix\Main\DB\SqlExpression $updatedBy)
	 * @method bool hasUpdatedBy()
	 * @method bool isUpdatedByFilled()
	 * @method bool isUpdatedByChanged()
	 * @method \int remindActualUpdatedBy()
	 * @method \int requireUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Object resetUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetUpdatedBy()
	 * @method \int fillUpdatedBy()
	 * @method \Bitrix\Main\EO_User getUpdateUser()
	 * @method \Bitrix\Main\EO_User remindActualUpdateUser()
	 * @method \Bitrix\Main\EO_User requireUpdateUser()
	 * @method \Bitrix\Disk\Internals\EO_Object setUpdateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetUpdateUser()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetUpdateUser()
	 * @method bool hasUpdateUser()
	 * @method bool isUpdateUserFilled()
	 * @method bool isUpdateUserChanged()
	 * @method \Bitrix\Main\EO_User fillUpdateUser()
	 * @method \int getDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_Object setDeletedBy(\int|\Bitrix\Main\DB\SqlExpression $deletedBy)
	 * @method bool hasDeletedBy()
	 * @method bool isDeletedByFilled()
	 * @method bool isDeletedByChanged()
	 * @method \int remindActualDeletedBy()
	 * @method \int requireDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_Object resetDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetDeletedBy()
	 * @method \int fillDeletedBy()
	 * @method \Bitrix\Main\EO_User getDeleteUser()
	 * @method \Bitrix\Main\EO_User remindActualDeleteUser()
	 * @method \Bitrix\Main\EO_User requireDeleteUser()
	 * @method \Bitrix\Disk\Internals\EO_Object setDeleteUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetDeleteUser()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetDeleteUser()
	 * @method bool hasDeleteUser()
	 * @method bool isDeleteUserFilled()
	 * @method bool isDeleteUserChanged()
	 * @method \Bitrix\Main\EO_User fillDeleteUser()
	 * @method \int getGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_Object setGlobalContentVersion(\int|\Bitrix\Main\DB\SqlExpression $globalContentVersion)
	 * @method bool hasGlobalContentVersion()
	 * @method bool isGlobalContentVersionFilled()
	 * @method bool isGlobalContentVersionChanged()
	 * @method \int remindActualGlobalContentVersion()
	 * @method \int requireGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_Object resetGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetGlobalContentVersion()
	 * @method \int fillGlobalContentVersion()
	 * @method \int getFileId()
	 * @method \Bitrix\Disk\Internals\EO_Object setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Disk\Internals\EO_Object resetFileId()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetFileId()
	 * @method \int fillFileId()
	 * @method \Bitrix\Main\EO_File getFileContent()
	 * @method \Bitrix\Main\EO_File remindActualFileContent()
	 * @method \Bitrix\Main\EO_File requireFileContent()
	 * @method \Bitrix\Disk\Internals\EO_Object setFileContent(\Bitrix\Main\EO_File $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetFileContent()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetFileContent()
	 * @method bool hasFileContent()
	 * @method bool isFileContentFilled()
	 * @method bool isFileContentChanged()
	 * @method \Bitrix\Main\EO_File fillFileContent()
	 * @method \int getSize()
	 * @method \Bitrix\Disk\Internals\EO_Object setSize(\int|\Bitrix\Main\DB\SqlExpression $size)
	 * @method bool hasSize()
	 * @method bool isSizeFilled()
	 * @method bool isSizeChanged()
	 * @method \int remindActualSize()
	 * @method \int requireSize()
	 * @method \Bitrix\Disk\Internals\EO_Object resetSize()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetSize()
	 * @method \int fillSize()
	 * @method \string getExternalHash()
	 * @method \Bitrix\Disk\Internals\EO_Object setExternalHash(\string|\Bitrix\Main\DB\SqlExpression $externalHash)
	 * @method bool hasExternalHash()
	 * @method bool isExternalHashFilled()
	 * @method bool isExternalHashChanged()
	 * @method \string remindActualExternalHash()
	 * @method \string requireExternalHash()
	 * @method \Bitrix\Disk\Internals\EO_Object resetExternalHash()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetExternalHash()
	 * @method \string fillExternalHash()
	 * @method \string getEtag()
	 * @method \Bitrix\Disk\Internals\EO_Object setEtag(\string|\Bitrix\Main\DB\SqlExpression $etag)
	 * @method bool hasEtag()
	 * @method bool isEtagFilled()
	 * @method bool isEtagChanged()
	 * @method \string remindActualEtag()
	 * @method \string requireEtag()
	 * @method \Bitrix\Disk\Internals\EO_Object resetEtag()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetEtag()
	 * @method \string fillEtag()
	 * @method \string getDeletedType()
	 * @method \Bitrix\Disk\Internals\EO_Object setDeletedType(\string|\Bitrix\Main\DB\SqlExpression $deletedType)
	 * @method bool hasDeletedType()
	 * @method bool isDeletedTypeFilled()
	 * @method bool isDeletedTypeChanged()
	 * @method \string remindActualDeletedType()
	 * @method \string requireDeletedType()
	 * @method \Bitrix\Disk\Internals\EO_Object resetDeletedType()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetDeletedType()
	 * @method \string fillDeletedType()
	 * @method \string getTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_Object setTypeFile(\string|\Bitrix\Main\DB\SqlExpression $typeFile)
	 * @method bool hasTypeFile()
	 * @method bool isTypeFileFilled()
	 * @method bool isTypeFileChanged()
	 * @method \string remindActualTypeFile()
	 * @method \string requireTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_Object resetTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetTypeFile()
	 * @method \string fillTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathParent()
	 * @method \Bitrix\Disk\Internals\EO_Object setPathParent(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetPathParent()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetPathParent()
	 * @method bool hasPathParent()
	 * @method bool isPathParentFilled()
	 * @method bool isPathParentChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathChild()
	 * @method \Bitrix\Disk\Internals\EO_Object setPathChild(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetPathChild()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetPathChild()
	 * @method bool hasPathChild()
	 * @method bool isPathChildFilled()
	 * @method bool isPathChildChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathChild()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed getRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed remindActualRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed requireRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_Object setRecentlyUsed(\Bitrix\Disk\Internals\EO_RecentlyUsed $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetRecentlyUsed()
	 * @method bool hasRecentlyUsed()
	 * @method bool isRecentlyUsedFilled()
	 * @method bool isRecentlyUsedChanged()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed fillRecentlyUsed()
	 * @method \int getPreviewId()
	 * @method \Bitrix\Disk\Internals\EO_Object setPreviewId(\int|\Bitrix\Main\DB\SqlExpression $previewId)
	 * @method bool hasPreviewId()
	 * @method bool isPreviewIdFilled()
	 * @method bool isPreviewIdChanged()
	 * @method \int remindActualPreviewId()
	 * @method \int requirePreviewId()
	 * @method \Bitrix\Disk\Internals\EO_Object resetPreviewId()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetPreviewId()
	 * @method \int fillPreviewId()
	 * @method \int getViewId()
	 * @method \Bitrix\Disk\Internals\EO_Object setViewId(\int|\Bitrix\Main\DB\SqlExpression $viewId)
	 * @method bool hasViewId()
	 * @method bool isViewIdFilled()
	 * @method bool isViewIdChanged()
	 * @method \int remindActualViewId()
	 * @method \int requireViewId()
	 * @method \Bitrix\Disk\Internals\EO_Object resetViewId()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetViewId()
	 * @method \int fillViewId()
	 * @method \string getSearchIndex()
	 * @method \string remindActualSearchIndex()
	 * @method \string requireSearchIndex()
	 * @method bool hasSearchIndex()
	 * @method bool isSearchIndexFilled()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetSearchIndex()
	 * @method \string fillSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex getHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex remindActualHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex requireHeadIndex()
	 * @method \Bitrix\Disk\Internals\EO_Object setHeadIndex(\Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetHeadIndex()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetHeadIndex()
	 * @method bool hasHeadIndex()
	 * @method bool isHeadIndexFilled()
	 * @method bool isHeadIndexChanged()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex fillHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex getExtendedIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex remindActualExtendedIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex requireExtendedIndex()
	 * @method \Bitrix\Disk\Internals\EO_Object setExtendedIndex(\Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetExtendedIndex()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetExtendedIndex()
	 * @method bool hasExtendedIndex()
	 * @method bool isExtendedIndexFilled()
	 * @method bool isExtendedIndexChanged()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex fillExtendedIndex()
	 * @method \boolean getHasSearchIndex()
	 * @method \boolean remindActualHasSearchIndex()
	 * @method \boolean requireHasSearchIndex()
	 * @method bool hasHasSearchIndex()
	 * @method bool isHasSearchIndexFilled()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetHasSearchIndex()
	 * @method \boolean fillHasSearchIndex()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject getTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject remindActualTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject requireTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_Object setTrackedObject(\Bitrix\Disk\Internals\EO_TrackedObject $object)
	 * @method \Bitrix\Disk\Internals\EO_Object resetTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_Object unsetTrackedObject()
	 * @method bool hasTrackedObject()
	 * @method bool isTrackedObjectFilled()
	 * @method bool isTrackedObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject fillTrackedObject()
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
	 * @method \Bitrix\Disk\Internals\EO_Object set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_Object reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_Object unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_Object wakeUp($data)
	 */
	class EO_Object {
		/* @var \Bitrix\Disk\Internals\ObjectTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Object_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 * @method \int[] getStorageIdList()
	 * @method \int[] fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage[] getStorageList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getStorageCollection()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection fillStorage()
	 * @method \int[] getRealObjectIdList()
	 * @method \int[] fillRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getRealObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getRealObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock[] getLockList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getLockCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock_Collection fillLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl[] getTtlList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getTtlCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl_Collection fillTtl()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \string[] getContentProviderList()
	 * @method \string[] fillContentProvider()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getSyncUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getDeleteTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDeleteTime()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\EO_User[] getCreateUserList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getCreateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreateUser()
	 * @method \int[] getUpdatedByList()
	 * @method \int[] fillUpdatedBy()
	 * @method \Bitrix\Main\EO_User[] getUpdateUserList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getUpdateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUpdateUser()
	 * @method \int[] getDeletedByList()
	 * @method \int[] fillDeletedBy()
	 * @method \Bitrix\Main\EO_User[] getDeleteUserList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getDeleteUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillDeleteUser()
	 * @method \int[] getGlobalContentVersionList()
	 * @method \int[] fillGlobalContentVersion()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \Bitrix\Main\EO_File[] getFileContentList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getFileContentCollection()
	 * @method \Bitrix\Main\EO_File_Collection fillFileContent()
	 * @method \int[] getSizeList()
	 * @method \int[] fillSize()
	 * @method \string[] getExternalHashList()
	 * @method \string[] fillExternalHash()
	 * @method \string[] getEtagList()
	 * @method \string[] fillEtag()
	 * @method \string[] getDeletedTypeList()
	 * @method \string[] fillDeletedType()
	 * @method \string[] getTypeFileList()
	 * @method \string[] fillTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathParentList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getPathParentCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathChildList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getPathChildCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathChild()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed[] getRecentlyUsedList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getRecentlyUsedCollection()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection fillRecentlyUsed()
	 * @method \int[] getPreviewIdList()
	 * @method \int[] fillPreviewId()
	 * @method \int[] getViewIdList()
	 * @method \int[] fillViewId()
	 * @method \string[] getSearchIndexList()
	 * @method \string[] fillSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex[] getHeadIndexList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getHeadIndexCollection()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection fillHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex[] getExtendedIndexList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getExtendedIndexCollection()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection fillExtendedIndex()
	 * @method \boolean[] getHasSearchIndexList()
	 * @method \boolean[] fillHasSearchIndex()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject[] getTrackedObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection getTrackedObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject_Collection fillTrackedObject()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Object getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Object[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_Object_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_Object current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Object_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\ObjectTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Object_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_Object fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Object_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Object fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fetchCollection()
	 */
	class EO_Object_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Object createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection wakeUpCollection($rows)
	 */
	class EO_Object_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\ExternalLinkTable:disk/lib/internals/externallink.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ExternalLink
	 * @see \Bitrix\Disk\Internals\ExternalLinkTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object getObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireObject()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetObject()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillObject()
	 * @method \int getVersionId()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setVersionId(\int|\Bitrix\Main\DB\SqlExpression $versionId)
	 * @method bool hasVersionId()
	 * @method bool isVersionIdFilled()
	 * @method bool isVersionIdChanged()
	 * @method \int remindActualVersionId()
	 * @method \int requireVersionId()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetVersionId()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetVersionId()
	 * @method \int fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version getVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version remindActualVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version requireVersion()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setVersion(\Bitrix\Disk\Internals\EO_Version $object)
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetVersion()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetVersion()
	 * @method bool hasVersion()
	 * @method bool isVersionFilled()
	 * @method bool isVersionChanged()
	 * @method \Bitrix\Disk\Internals\EO_Version fillVersion()
	 * @method \string getHash()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetHash()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetHash()
	 * @method \string fillHash()
	 * @method \string getPassword()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setPassword(\string|\Bitrix\Main\DB\SqlExpression $password)
	 * @method bool hasPassword()
	 * @method bool isPasswordFilled()
	 * @method bool isPasswordChanged()
	 * @method \string remindActualPassword()
	 * @method \string requirePassword()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetPassword()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetPassword()
	 * @method \string fillPassword()
	 * @method \string getSalt()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setSalt(\string|\Bitrix\Main\DB\SqlExpression $salt)
	 * @method bool hasSalt()
	 * @method bool isSaltFilled()
	 * @method bool isSaltChanged()
	 * @method \string remindActualSalt()
	 * @method \string requireSalt()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetSalt()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetSalt()
	 * @method \string fillSalt()
	 * @method \Bitrix\Main\Type\DateTime getDeathTime()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setDeathTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $deathTime)
	 * @method bool hasDeathTime()
	 * @method bool isDeathTimeFilled()
	 * @method bool isDeathTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDeathTime()
	 * @method \Bitrix\Main\Type\DateTime requireDeathTime()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetDeathTime()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetDeathTime()
	 * @method \Bitrix\Main\Type\DateTime fillDeathTime()
	 * @method \string getDescription()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetDescription()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetDescription()
	 * @method \string fillDescription()
	 * @method \int getDownloadCount()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setDownloadCount(\int|\Bitrix\Main\DB\SqlExpression $downloadCount)
	 * @method bool hasDownloadCount()
	 * @method bool isDownloadCountFilled()
	 * @method bool isDownloadCountChanged()
	 * @method \int remindActualDownloadCount()
	 * @method \int requireDownloadCount()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetDownloadCount()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetDownloadCount()
	 * @method \int fillDownloadCount()
	 * @method \int getAccessRight()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setAccessRight(\int|\Bitrix\Main\DB\SqlExpression $accessRight)
	 * @method bool hasAccessRight()
	 * @method bool isAccessRightFilled()
	 * @method bool isAccessRightChanged()
	 * @method \int remindActualAccessRight()
	 * @method \int requireAccessRight()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetAccessRight()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetAccessRight()
	 * @method \int fillAccessRight()
	 * @method \boolean getIsExpired()
	 * @method \boolean remindActualIsExpired()
	 * @method \boolean requireIsExpired()
	 * @method bool hasIsExpired()
	 * @method bool isIsExpiredFilled()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetIsExpired()
	 * @method \boolean fillIsExpired()
	 * @method \string getType()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetType()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetType()
	 * @method \string fillType()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\EO_User getCreateUser()
	 * @method \Bitrix\Main\EO_User remindActualCreateUser()
	 * @method \Bitrix\Main\EO_User requireCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink setCreateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink resetCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unsetCreateUser()
	 * @method bool hasCreateUser()
	 * @method bool isCreateUserFilled()
	 * @method bool isCreateUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreateUser()
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
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_ExternalLink wakeUp($data)
	 */
	class EO_ExternalLink {
		/* @var \Bitrix\Disk\Internals\ExternalLinkTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ExternalLinkTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ExternalLink_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getObjectList()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillObject()
	 * @method \int[] getVersionIdList()
	 * @method \int[] fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version[] getVersionList()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink_Collection getVersionCollection()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection fillVersion()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \string[] getPasswordList()
	 * @method \string[] fillPassword()
	 * @method \string[] getSaltList()
	 * @method \string[] fillSalt()
	 * @method \Bitrix\Main\Type\DateTime[] getDeathTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDeathTime()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \int[] getDownloadCountList()
	 * @method \int[] fillDownloadCount()
	 * @method \int[] getAccessRightList()
	 * @method \int[] fillAccessRight()
	 * @method \boolean[] getIsExpiredList()
	 * @method \boolean[] fillIsExpired()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\EO_User[] getCreateUserList()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink_Collection getCreateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreateUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_ExternalLink $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_ExternalLink $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_ExternalLink $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_ExternalLink_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ExternalLink_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\ExternalLinkTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ExternalLinkTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExternalLink_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ExternalLink_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink_Collection fetchCollection()
	 */
	class EO_ExternalLink_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_ExternalLink_Collection wakeUpCollection($rows)
	 */
	class EO_ExternalLink_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\VolumeTable:disk/lib/internals/volume.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Volume
	 * @see \Bitrix\Disk\Internals\VolumeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getIndicatorType()
	 * @method \Bitrix\Disk\Internals\EO_Volume setIndicatorType(\string|\Bitrix\Main\DB\SqlExpression $indicatorType)
	 * @method bool hasIndicatorType()
	 * @method bool isIndicatorTypeFilled()
	 * @method bool isIndicatorTypeChanged()
	 * @method \string remindActualIndicatorType()
	 * @method \string requireIndicatorType()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetIndicatorType()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetIndicatorType()
	 * @method \string fillIndicatorType()
	 * @method \int getOwnerId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setOwnerId(\int|\Bitrix\Main\DB\SqlExpression $ownerId)
	 * @method bool hasOwnerId()
	 * @method bool isOwnerIdFilled()
	 * @method bool isOwnerIdChanged()
	 * @method \int remindActualOwnerId()
	 * @method \int requireOwnerId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetOwnerId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetOwnerId()
	 * @method \int fillOwnerId()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Volume setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \string getTitle()
	 * @method \Bitrix\Disk\Internals\EO_Volume setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetTitle()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetTitle()
	 * @method \string fillTitle()
	 * @method \int getFileSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume setFileSize(\int|\Bitrix\Main\DB\SqlExpression $fileSize)
	 * @method bool hasFileSize()
	 * @method bool isFileSizeFilled()
	 * @method bool isFileSizeChanged()
	 * @method \int remindActualFileSize()
	 * @method \int requireFileSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetFileSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetFileSize()
	 * @method \int fillFileSize()
	 * @method \int getFileCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setFileCount(\int|\Bitrix\Main\DB\SqlExpression $fileCount)
	 * @method bool hasFileCount()
	 * @method bool isFileCountFilled()
	 * @method bool isFileCountChanged()
	 * @method \int remindActualFileCount()
	 * @method \int requireFileCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetFileCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetFileCount()
	 * @method \int fillFileCount()
	 * @method \int getDiskSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume setDiskSize(\int|\Bitrix\Main\DB\SqlExpression $diskSize)
	 * @method bool hasDiskSize()
	 * @method bool isDiskSizeFilled()
	 * @method bool isDiskSizeChanged()
	 * @method \int remindActualDiskSize()
	 * @method \int requireDiskSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetDiskSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetDiskSize()
	 * @method \int fillDiskSize()
	 * @method \int getDiskCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setDiskCount(\int|\Bitrix\Main\DB\SqlExpression $diskCount)
	 * @method bool hasDiskCount()
	 * @method bool isDiskCountFilled()
	 * @method bool isDiskCountChanged()
	 * @method \int remindActualDiskCount()
	 * @method \int requireDiskCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetDiskCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetDiskCount()
	 * @method \int fillDiskCount()
	 * @method \int getVersionCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setVersionCount(\int|\Bitrix\Main\DB\SqlExpression $versionCount)
	 * @method bool hasVersionCount()
	 * @method bool isVersionCountFilled()
	 * @method bool isVersionCountChanged()
	 * @method \int remindActualVersionCount()
	 * @method \int requireVersionCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetVersionCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetVersionCount()
	 * @method \int fillVersionCount()
	 * @method \int getPreviewSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume setPreviewSize(\int|\Bitrix\Main\DB\SqlExpression $previewSize)
	 * @method bool hasPreviewSize()
	 * @method bool isPreviewSizeFilled()
	 * @method bool isPreviewSizeChanged()
	 * @method \int remindActualPreviewSize()
	 * @method \int requirePreviewSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetPreviewSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetPreviewSize()
	 * @method \int fillPreviewSize()
	 * @method \int getPreviewCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setPreviewCount(\int|\Bitrix\Main\DB\SqlExpression $previewCount)
	 * @method bool hasPreviewCount()
	 * @method bool isPreviewCountFilled()
	 * @method bool isPreviewCountChanged()
	 * @method \int remindActualPreviewCount()
	 * @method \int requirePreviewCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetPreviewCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetPreviewCount()
	 * @method \int fillPreviewCount()
	 * @method \int getAttachedCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setAttachedCount(\int|\Bitrix\Main\DB\SqlExpression $attachedCount)
	 * @method bool hasAttachedCount()
	 * @method bool isAttachedCountFilled()
	 * @method bool isAttachedCountChanged()
	 * @method \int remindActualAttachedCount()
	 * @method \int requireAttachedCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetAttachedCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetAttachedCount()
	 * @method \int fillAttachedCount()
	 * @method \int getLinkCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setLinkCount(\int|\Bitrix\Main\DB\SqlExpression $linkCount)
	 * @method bool hasLinkCount()
	 * @method bool isLinkCountFilled()
	 * @method bool isLinkCountChanged()
	 * @method \int remindActualLinkCount()
	 * @method \int requireLinkCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetLinkCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetLinkCount()
	 * @method \int fillLinkCount()
	 * @method \int getSharingCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setSharingCount(\int|\Bitrix\Main\DB\SqlExpression $sharingCount)
	 * @method bool hasSharingCount()
	 * @method bool isSharingCountFilled()
	 * @method bool isSharingCountChanged()
	 * @method \int remindActualSharingCount()
	 * @method \int requireSharingCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetSharingCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetSharingCount()
	 * @method \int fillSharingCount()
	 * @method \int getUnnecessaryVersionSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume setUnnecessaryVersionSize(\int|\Bitrix\Main\DB\SqlExpression $unnecessaryVersionSize)
	 * @method bool hasUnnecessaryVersionSize()
	 * @method bool isUnnecessaryVersionSizeFilled()
	 * @method bool isUnnecessaryVersionSizeChanged()
	 * @method \int remindActualUnnecessaryVersionSize()
	 * @method \int requireUnnecessaryVersionSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetUnnecessaryVersionSize()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetUnnecessaryVersionSize()
	 * @method \int fillUnnecessaryVersionSize()
	 * @method \int getUnnecessaryVersionCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setUnnecessaryVersionCount(\int|\Bitrix\Main\DB\SqlExpression $unnecessaryVersionCount)
	 * @method bool hasUnnecessaryVersionCount()
	 * @method bool isUnnecessaryVersionCountFilled()
	 * @method bool isUnnecessaryVersionCountChanged()
	 * @method \int remindActualUnnecessaryVersionCount()
	 * @method \int requireUnnecessaryVersionCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetUnnecessaryVersionCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetUnnecessaryVersionCount()
	 * @method \int fillUnnecessaryVersionCount()
	 * @method \float getPercent()
	 * @method \Bitrix\Disk\Internals\EO_Volume setPercent(\float|\Bitrix\Main\DB\SqlExpression $percent)
	 * @method bool hasPercent()
	 * @method bool isPercentFilled()
	 * @method bool isPercentChanged()
	 * @method \float remindActualPercent()
	 * @method \float requirePercent()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetPercent()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetPercent()
	 * @method \float fillPercent()
	 * @method \int getStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setStorageId(\int|\Bitrix\Main\DB\SqlExpression $storageId)
	 * @method bool hasStorageId()
	 * @method bool isStorageIdFilled()
	 * @method bool isStorageIdChanged()
	 * @method \int remindActualStorageId()
	 * @method \int requireStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetStorageId()
	 * @method \int fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage getStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage remindActualStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage requireStorage()
	 * @method \Bitrix\Disk\Internals\EO_Volume setStorage(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method \Bitrix\Disk\Internals\EO_Volume resetStorage()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetStorage()
	 * @method bool hasStorage()
	 * @method bool isStorageFilled()
	 * @method bool isStorageChanged()
	 * @method \Bitrix\Disk\Internals\EO_Storage fillStorage()
	 * @method \string getModuleId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetModuleId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetModuleId()
	 * @method \string fillModuleId()
	 * @method \int getFolderId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setFolderId(\int|\Bitrix\Main\DB\SqlExpression $folderId)
	 * @method bool hasFolderId()
	 * @method bool isFolderIdFilled()
	 * @method bool isFolderIdChanged()
	 * @method \int remindActualFolderId()
	 * @method \int requireFolderId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetFolderId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetFolderId()
	 * @method \int fillFolderId()
	 * @method \Bitrix\Disk\Internals\EO_Folder getFolder()
	 * @method \Bitrix\Disk\Internals\EO_Folder remindActualFolder()
	 * @method \Bitrix\Disk\Internals\EO_Folder requireFolder()
	 * @method \Bitrix\Disk\Internals\EO_Volume setFolder(\Bitrix\Disk\Internals\EO_Folder $object)
	 * @method \Bitrix\Disk\Internals\EO_Volume resetFolder()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetFolder()
	 * @method bool hasFolder()
	 * @method bool isFolderFilled()
	 * @method bool isFolderChanged()
	 * @method \Bitrix\Disk\Internals\EO_Folder fillFolder()
	 * @method \int getParentId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetParentId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetParentId()
	 * @method \int fillParentId()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetUserId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getGroupId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetGroupId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Disk\Internals\EO_Volume setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetEntityType()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \string getEntityId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setEntityId(\string|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \string remindActualEntityId()
	 * @method \string requireEntityId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetEntityId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetEntityId()
	 * @method \string fillEntityId()
	 * @method \string getTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_Volume setTypeFile(\string|\Bitrix\Main\DB\SqlExpression $typeFile)
	 * @method bool hasTypeFile()
	 * @method bool isTypeFileFilled()
	 * @method bool isTypeFileChanged()
	 * @method \string remindActualTypeFile()
	 * @method \string requireTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetTypeFile()
	 * @method \string fillTypeFile()
	 * @method \int getIblockId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setIblockId(\int|\Bitrix\Main\DB\SqlExpression $iblockId)
	 * @method bool hasIblockId()
	 * @method bool isIblockIdFilled()
	 * @method bool isIblockIdChanged()
	 * @method \int remindActualIblockId()
	 * @method \int requireIblockId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetIblockId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetIblockId()
	 * @method \int fillIblockId()
	 * @method \string getCollected()
	 * @method \Bitrix\Disk\Internals\EO_Volume setCollected(\string|\Bitrix\Main\DB\SqlExpression $collected)
	 * @method bool hasCollected()
	 * @method bool isCollectedFilled()
	 * @method bool isCollectedChanged()
	 * @method \string remindActualCollected()
	 * @method \string requireCollected()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetCollected()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetCollected()
	 * @method \string fillCollected()
	 * @method \string getData()
	 * @method \Bitrix\Disk\Internals\EO_Volume setData(\string|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method \string remindActualData()
	 * @method \string requireData()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetData()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetData()
	 * @method \string fillData()
	 * @method \string getAgentLock()
	 * @method \Bitrix\Disk\Internals\EO_Volume setAgentLock(\string|\Bitrix\Main\DB\SqlExpression $agentLock)
	 * @method bool hasAgentLock()
	 * @method bool isAgentLockFilled()
	 * @method bool isAgentLockChanged()
	 * @method \string remindActualAgentLock()
	 * @method \string requireAgentLock()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetAgentLock()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetAgentLock()
	 * @method \string fillAgentLock()
	 * @method \string getDropUnnecessaryVersion()
	 * @method \Bitrix\Disk\Internals\EO_Volume setDropUnnecessaryVersion(\string|\Bitrix\Main\DB\SqlExpression $dropUnnecessaryVersion)
	 * @method bool hasDropUnnecessaryVersion()
	 * @method bool isDropUnnecessaryVersionFilled()
	 * @method bool isDropUnnecessaryVersionChanged()
	 * @method \string remindActualDropUnnecessaryVersion()
	 * @method \string requireDropUnnecessaryVersion()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetDropUnnecessaryVersion()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetDropUnnecessaryVersion()
	 * @method \string fillDropUnnecessaryVersion()
	 * @method \string getDropTrashcan()
	 * @method \Bitrix\Disk\Internals\EO_Volume setDropTrashcan(\string|\Bitrix\Main\DB\SqlExpression $dropTrashcan)
	 * @method bool hasDropTrashcan()
	 * @method bool isDropTrashcanFilled()
	 * @method bool isDropTrashcanChanged()
	 * @method \string remindActualDropTrashcan()
	 * @method \string requireDropTrashcan()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetDropTrashcan()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetDropTrashcan()
	 * @method \string fillDropTrashcan()
	 * @method \string getDropFolder()
	 * @method \Bitrix\Disk\Internals\EO_Volume setDropFolder(\string|\Bitrix\Main\DB\SqlExpression $dropFolder)
	 * @method bool hasDropFolder()
	 * @method bool isDropFolderFilled()
	 * @method bool isDropFolderChanged()
	 * @method \string remindActualDropFolder()
	 * @method \string requireDropFolder()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetDropFolder()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetDropFolder()
	 * @method \string fillDropFolder()
	 * @method \string getEmptyFolder()
	 * @method \Bitrix\Disk\Internals\EO_Volume setEmptyFolder(\string|\Bitrix\Main\DB\SqlExpression $emptyFolder)
	 * @method bool hasEmptyFolder()
	 * @method bool isEmptyFolderFilled()
	 * @method bool isEmptyFolderChanged()
	 * @method \string remindActualEmptyFolder()
	 * @method \string requireEmptyFolder()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetEmptyFolder()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetEmptyFolder()
	 * @method \string fillEmptyFolder()
	 * @method \int getDroppedFileCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setDroppedFileCount(\int|\Bitrix\Main\DB\SqlExpression $droppedFileCount)
	 * @method bool hasDroppedFileCount()
	 * @method bool isDroppedFileCountFilled()
	 * @method bool isDroppedFileCountChanged()
	 * @method \int remindActualDroppedFileCount()
	 * @method \int requireDroppedFileCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetDroppedFileCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetDroppedFileCount()
	 * @method \int fillDroppedFileCount()
	 * @method \int getDroppedVersionCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setDroppedVersionCount(\int|\Bitrix\Main\DB\SqlExpression $droppedVersionCount)
	 * @method bool hasDroppedVersionCount()
	 * @method bool isDroppedVersionCountFilled()
	 * @method bool isDroppedVersionCountChanged()
	 * @method \int remindActualDroppedVersionCount()
	 * @method \int requireDroppedVersionCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetDroppedVersionCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetDroppedVersionCount()
	 * @method \int fillDroppedVersionCount()
	 * @method \int getDroppedFolderCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setDroppedFolderCount(\int|\Bitrix\Main\DB\SqlExpression $droppedFolderCount)
	 * @method bool hasDroppedFolderCount()
	 * @method bool isDroppedFolderCountFilled()
	 * @method bool isDroppedFolderCountChanged()
	 * @method \int remindActualDroppedFolderCount()
	 * @method \int requireDroppedFolderCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetDroppedFolderCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetDroppedFolderCount()
	 * @method \int fillDroppedFolderCount()
	 * @method \int getLastFileId()
	 * @method \Bitrix\Disk\Internals\EO_Volume setLastFileId(\int|\Bitrix\Main\DB\SqlExpression $lastFileId)
	 * @method bool hasLastFileId()
	 * @method bool isLastFileIdFilled()
	 * @method bool isLastFileIdChanged()
	 * @method \int remindActualLastFileId()
	 * @method \int requireLastFileId()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetLastFileId()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetLastFileId()
	 * @method \int fillLastFileId()
	 * @method \int getFailCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume setFailCount(\int|\Bitrix\Main\DB\SqlExpression $failCount)
	 * @method bool hasFailCount()
	 * @method bool isFailCountFilled()
	 * @method bool isFailCountChanged()
	 * @method \int remindActualFailCount()
	 * @method \int requireFailCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetFailCount()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetFailCount()
	 * @method \int fillFailCount()
	 * @method \string getLastError()
	 * @method \Bitrix\Disk\Internals\EO_Volume setLastError(\string|\Bitrix\Main\DB\SqlExpression $lastError)
	 * @method bool hasLastError()
	 * @method bool isLastErrorFilled()
	 * @method bool isLastErrorChanged()
	 * @method \string remindActualLastError()
	 * @method \string requireLastError()
	 * @method \Bitrix\Disk\Internals\EO_Volume resetLastError()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetLastError()
	 * @method \string fillLastError()
	 * @method \int getFilesLeft()
	 * @method \int remindActualFilesLeft()
	 * @method \int requireFilesLeft()
	 * @method bool hasFilesLeft()
	 * @method bool isFilesLeftFilled()
	 * @method \Bitrix\Disk\Internals\EO_Volume unsetFilesLeft()
	 * @method \int fillFilesLeft()
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
	 * @method \Bitrix\Disk\Internals\EO_Volume set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_Volume reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_Volume unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_Volume wakeUp($data)
	 */
	class EO_Volume {
		/* @var \Bitrix\Disk\Internals\VolumeTable */
		static public $dataClass = '\Bitrix\Disk\Internals\VolumeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Volume_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getIndicatorTypeList()
	 * @method \string[] fillIndicatorType()
	 * @method \int[] getOwnerIdList()
	 * @method \int[] fillOwnerId()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \int[] getFileSizeList()
	 * @method \int[] fillFileSize()
	 * @method \int[] getFileCountList()
	 * @method \int[] fillFileCount()
	 * @method \int[] getDiskSizeList()
	 * @method \int[] fillDiskSize()
	 * @method \int[] getDiskCountList()
	 * @method \int[] fillDiskCount()
	 * @method \int[] getVersionCountList()
	 * @method \int[] fillVersionCount()
	 * @method \int[] getPreviewSizeList()
	 * @method \int[] fillPreviewSize()
	 * @method \int[] getPreviewCountList()
	 * @method \int[] fillPreviewCount()
	 * @method \int[] getAttachedCountList()
	 * @method \int[] fillAttachedCount()
	 * @method \int[] getLinkCountList()
	 * @method \int[] fillLinkCount()
	 * @method \int[] getSharingCountList()
	 * @method \int[] fillSharingCount()
	 * @method \int[] getUnnecessaryVersionSizeList()
	 * @method \int[] fillUnnecessaryVersionSize()
	 * @method \int[] getUnnecessaryVersionCountList()
	 * @method \int[] fillUnnecessaryVersionCount()
	 * @method \float[] getPercentList()
	 * @method \float[] fillPercent()
	 * @method \int[] getStorageIdList()
	 * @method \int[] fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage[] getStorageList()
	 * @method \Bitrix\Disk\Internals\EO_Volume_Collection getStorageCollection()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection fillStorage()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method \int[] getFolderIdList()
	 * @method \int[] fillFolderId()
	 * @method \Bitrix\Disk\Internals\EO_Folder[] getFolderList()
	 * @method \Bitrix\Disk\Internals\EO_Volume_Collection getFolderCollection()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection fillFolder()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \string[] getEntityIdList()
	 * @method \string[] fillEntityId()
	 * @method \string[] getTypeFileList()
	 * @method \string[] fillTypeFile()
	 * @method \int[] getIblockIdList()
	 * @method \int[] fillIblockId()
	 * @method \string[] getCollectedList()
	 * @method \string[] fillCollected()
	 * @method \string[] getDataList()
	 * @method \string[] fillData()
	 * @method \string[] getAgentLockList()
	 * @method \string[] fillAgentLock()
	 * @method \string[] getDropUnnecessaryVersionList()
	 * @method \string[] fillDropUnnecessaryVersion()
	 * @method \string[] getDropTrashcanList()
	 * @method \string[] fillDropTrashcan()
	 * @method \string[] getDropFolderList()
	 * @method \string[] fillDropFolder()
	 * @method \string[] getEmptyFolderList()
	 * @method \string[] fillEmptyFolder()
	 * @method \int[] getDroppedFileCountList()
	 * @method \int[] fillDroppedFileCount()
	 * @method \int[] getDroppedVersionCountList()
	 * @method \int[] fillDroppedVersionCount()
	 * @method \int[] getDroppedFolderCountList()
	 * @method \int[] fillDroppedFolderCount()
	 * @method \int[] getLastFileIdList()
	 * @method \int[] fillLastFileId()
	 * @method \int[] getFailCountList()
	 * @method \int[] fillFailCount()
	 * @method \string[] getLastErrorList()
	 * @method \string[] fillLastError()
	 * @method \int[] getFilesLeftList()
	 * @method \int[] fillFilesLeft()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_Volume $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_Volume $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Volume getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Volume[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_Volume $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_Volume_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_Volume current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Volume_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\VolumeTable */
		static public $dataClass = '\Bitrix\Disk\Internals\VolumeTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Volume_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_Volume fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Volume_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Volume_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Volume fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Volume_Collection fetchCollection()
	 */
	class EO_Volume_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Volume createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_Volume_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_Volume wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_Volume_Collection wakeUpCollection($rows)
	 */
	class EO_Volume_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\SimpleRightTable:disk/lib/internals/simpleright.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_SimpleRight
	 * @see \Bitrix\Disk\Internals\SimpleRightTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight resetAccessCode()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight unsetAccessCode()
	 * @method \string fillAccessCode()
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
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_SimpleRight wakeUp($data)
	 */
	class EO_SimpleRight {
		/* @var \Bitrix\Disk\Internals\SimpleRightTable */
		static public $dataClass = '\Bitrix\Disk\Internals\SimpleRightTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_SimpleRight_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_SimpleRight $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_SimpleRight $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_SimpleRight $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_SimpleRight_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_SimpleRight_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\SimpleRightTable */
		static public $dataClass = '\Bitrix\Disk\Internals\SimpleRightTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SimpleRight_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_SimpleRight_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight_Collection fetchCollection()
	 */
	class EO_SimpleRight_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_SimpleRight_Collection wakeUpCollection($rows)
	 */
	class EO_SimpleRight_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\ObjectPathTable:disk/lib/internals/objectpath.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ObjectPath
	 * @see \Bitrix\Disk\Internals\ObjectPathTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getParentId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath resetParentId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath unsetParentId()
	 * @method \int fillParentId()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \int getDepthLevel()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath setDepthLevel(\int|\Bitrix\Main\DB\SqlExpression $depthLevel)
	 * @method bool hasDepthLevel()
	 * @method bool isDepthLevelFilled()
	 * @method bool isDepthLevelChanged()
	 * @method \int remindActualDepthLevel()
	 * @method \int requireDepthLevel()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath resetDepthLevel()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath unsetDepthLevel()
	 * @method \int fillDepthLevel()
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
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_ObjectPath wakeUp($data)
	 */
	class EO_ObjectPath {
		/* @var \Bitrix\Disk\Internals\ObjectPathTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectPathTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ObjectPath_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \int[] getDepthLevelList()
	 * @method \int[] fillDepthLevel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_ObjectPath_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ObjectPath_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\ObjectPathTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectPathTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ObjectPath_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ObjectPath_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fetchCollection()
	 */
	class EO_ObjectPath_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection wakeUpCollection($rows)
	 */
	class EO_ObjectPath_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\AttachedObjectTable:disk/lib/internals/attachedobject.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_AttachedObject
	 * @see \Bitrix\Disk\Internals\AttachedObjectTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File getObject()
	 * @method \Bitrix\Disk\Internals\EO_File remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_File requireObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setObject(\Bitrix\Disk\Internals\EO_File $object)
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_File fillObject()
	 * @method \int getVersionId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setVersionId(\int|\Bitrix\Main\DB\SqlExpression $versionId)
	 * @method bool hasVersionId()
	 * @method bool isVersionIdFilled()
	 * @method bool isVersionIdChanged()
	 * @method \int remindActualVersionId()
	 * @method \int requireVersionId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetVersionId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetVersionId()
	 * @method \int fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version getVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version remindActualVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version requireVersion()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setVersion(\Bitrix\Disk\Internals\EO_Version $object)
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetVersion()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetVersion()
	 * @method bool hasVersion()
	 * @method bool isVersionFilled()
	 * @method bool isVersionChanged()
	 * @method \Bitrix\Disk\Internals\EO_Version fillVersion()
	 * @method \string getIsEditable()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setIsEditable(\string|\Bitrix\Main\DB\SqlExpression $isEditable)
	 * @method bool hasIsEditable()
	 * @method bool isIsEditableFilled()
	 * @method bool isIsEditableChanged()
	 * @method \string remindActualIsEditable()
	 * @method \string requireIsEditable()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetIsEditable()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetIsEditable()
	 * @method \string fillIsEditable()
	 * @method \string getAllowEdit()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setAllowEdit(\string|\Bitrix\Main\DB\SqlExpression $allowEdit)
	 * @method bool hasAllowEdit()
	 * @method bool isAllowEditFilled()
	 * @method bool isAllowEditChanged()
	 * @method \string remindActualAllowEdit()
	 * @method \string requireAllowEdit()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetAllowEdit()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetAllowEdit()
	 * @method \string fillAllowEdit()
	 * @method \string getAllowAutoComment()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setAllowAutoComment(\string|\Bitrix\Main\DB\SqlExpression $allowAutoComment)
	 * @method bool hasAllowAutoComment()
	 * @method bool isAllowAutoCommentFilled()
	 * @method bool isAllowAutoCommentChanged()
	 * @method \string remindActualAllowAutoComment()
	 * @method \string requireAllowAutoComment()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetAllowAutoComment()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetAllowAutoComment()
	 * @method \string fillAllowAutoComment()
	 * @method \string getModuleId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetModuleId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetModuleId()
	 * @method \string fillModuleId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetEntityType()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \int getEntityId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetEntityId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\EO_User getCreateUser()
	 * @method \Bitrix\Main\EO_User remindActualCreateUser()
	 * @method \Bitrix\Main\EO_User requireCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject setCreateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject resetCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unsetCreateUser()
	 * @method bool hasCreateUser()
	 * @method bool isCreateUserFilled()
	 * @method bool isCreateUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreateUser()
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
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_AttachedObject wakeUp($data)
	 */
	class EO_AttachedObject {
		/* @var \Bitrix\Disk\Internals\AttachedObjectTable */
		static public $dataClass = '\Bitrix\Disk\Internals\AttachedObjectTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_AttachedObject_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File[] getObjectList()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection fillObject()
	 * @method \int[] getVersionIdList()
	 * @method \int[] fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version[] getVersionList()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject_Collection getVersionCollection()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection fillVersion()
	 * @method \string[] getIsEditableList()
	 * @method \string[] fillIsEditable()
	 * @method \string[] getAllowEditList()
	 * @method \string[] fillAllowEdit()
	 * @method \string[] getAllowAutoCommentList()
	 * @method \string[] fillAllowAutoComment()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\EO_User[] getCreateUserList()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject_Collection getCreateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreateUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_AttachedObject $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_AttachedObject $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_AttachedObject $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_AttachedObject_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_AttachedObject_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\AttachedObjectTable */
		static public $dataClass = '\Bitrix\Disk\Internals\AttachedObjectTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_AttachedObject_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_AttachedObject_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject_Collection fetchCollection()
	 */
	class EO_AttachedObject_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject_Collection wakeUpCollection($rows)
	 */
	class EO_AttachedObject_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\FileTable:disk/lib/internals/file.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_File
	 * @see \Bitrix\Disk\Internals\FileTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_File setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Disk\Internals\EO_File setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Disk\Internals\EO_File resetName()
	 * @method \Bitrix\Disk\Internals\EO_File unsetName()
	 * @method \string fillName()
	 * @method \string getType()
	 * @method \Bitrix\Disk\Internals\EO_File setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Disk\Internals\EO_File resetType()
	 * @method \Bitrix\Disk\Internals\EO_File unsetType()
	 * @method \string fillType()
	 * @method \string getCode()
	 * @method \Bitrix\Disk\Internals\EO_File setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Disk\Internals\EO_File resetCode()
	 * @method \Bitrix\Disk\Internals\EO_File unsetCode()
	 * @method \string fillCode()
	 * @method \string getXmlId()
	 * @method \Bitrix\Disk\Internals\EO_File setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Disk\Internals\EO_File resetXmlId()
	 * @method \Bitrix\Disk\Internals\EO_File unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \int getStorageId()
	 * @method \Bitrix\Disk\Internals\EO_File setStorageId(\int|\Bitrix\Main\DB\SqlExpression $storageId)
	 * @method bool hasStorageId()
	 * @method bool isStorageIdFilled()
	 * @method bool isStorageIdChanged()
	 * @method \int remindActualStorageId()
	 * @method \int requireStorageId()
	 * @method \Bitrix\Disk\Internals\EO_File resetStorageId()
	 * @method \Bitrix\Disk\Internals\EO_File unsetStorageId()
	 * @method \int fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage getStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage remindActualStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage requireStorage()
	 * @method \Bitrix\Disk\Internals\EO_File setStorage(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetStorage()
	 * @method \Bitrix\Disk\Internals\EO_File unsetStorage()
	 * @method bool hasStorage()
	 * @method bool isStorageFilled()
	 * @method bool isStorageChanged()
	 * @method \Bitrix\Disk\Internals\EO_Storage fillStorage()
	 * @method \int getRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File setRealObjectId(\int|\Bitrix\Main\DB\SqlExpression $realObjectId)
	 * @method bool hasRealObjectId()
	 * @method bool isRealObjectIdFilled()
	 * @method bool isRealObjectIdChanged()
	 * @method \int remindActualRealObjectId()
	 * @method \int requireRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File resetRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File unsetRealObjectId()
	 * @method \int fillRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object getRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireRealObject()
	 * @method \Bitrix\Disk\Internals\EO_File setRealObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetRealObject()
	 * @method \Bitrix\Disk\Internals\EO_File unsetRealObject()
	 * @method bool hasRealObject()
	 * @method bool isRealObjectFilled()
	 * @method bool isRealObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock getLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock remindActualLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock requireLock()
	 * @method \Bitrix\Disk\Internals\EO_File setLock(\Bitrix\Disk\Internals\EO_ObjectLock $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetLock()
	 * @method \Bitrix\Disk\Internals\EO_File unsetLock()
	 * @method bool hasLock()
	 * @method bool isLockFilled()
	 * @method bool isLockChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock fillLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl getTtl()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl remindActualTtl()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl requireTtl()
	 * @method \Bitrix\Disk\Internals\EO_File setTtl(\Bitrix\Disk\Internals\EO_ObjectTtl $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetTtl()
	 * @method \Bitrix\Disk\Internals\EO_File unsetTtl()
	 * @method bool hasTtl()
	 * @method bool isTtlFilled()
	 * @method bool isTtlChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl fillTtl()
	 * @method \int getParentId()
	 * @method \Bitrix\Disk\Internals\EO_File setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Disk\Internals\EO_File resetParentId()
	 * @method \Bitrix\Disk\Internals\EO_File unsetParentId()
	 * @method \int fillParentId()
	 * @method \string getContentProvider()
	 * @method \Bitrix\Disk\Internals\EO_File setContentProvider(\string|\Bitrix\Main\DB\SqlExpression $contentProvider)
	 * @method bool hasContentProvider()
	 * @method bool isContentProviderFilled()
	 * @method bool isContentProviderChanged()
	 * @method \string remindActualContentProvider()
	 * @method \string requireContentProvider()
	 * @method \Bitrix\Disk\Internals\EO_File resetContentProvider()
	 * @method \Bitrix\Disk\Internals\EO_File unsetContentProvider()
	 * @method \string fillContentProvider()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_File setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_File resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_File unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_File setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_File resetUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_File unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime getSyncUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_File setSyncUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $syncUpdateTime)
	 * @method bool hasSyncUpdateTime()
	 * @method bool isSyncUpdateTimeFilled()
	 * @method bool isSyncUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireSyncUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_File resetSyncUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_File unsetSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime getDeleteTime()
	 * @method \Bitrix\Disk\Internals\EO_File setDeleteTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $deleteTime)
	 * @method bool hasDeleteTime()
	 * @method bool isDeleteTimeFilled()
	 * @method bool isDeleteTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDeleteTime()
	 * @method \Bitrix\Main\Type\DateTime requireDeleteTime()
	 * @method \Bitrix\Disk\Internals\EO_File resetDeleteTime()
	 * @method \Bitrix\Disk\Internals\EO_File unsetDeleteTime()
	 * @method \Bitrix\Main\Type\DateTime fillDeleteTime()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_File setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_File resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_File unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\EO_User getCreateUser()
	 * @method \Bitrix\Main\EO_User remindActualCreateUser()
	 * @method \Bitrix\Main\EO_User requireCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_File setCreateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_File unsetCreateUser()
	 * @method bool hasCreateUser()
	 * @method bool isCreateUserFilled()
	 * @method bool isCreateUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreateUser()
	 * @method \int getUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_File setUpdatedBy(\int|\Bitrix\Main\DB\SqlExpression $updatedBy)
	 * @method bool hasUpdatedBy()
	 * @method bool isUpdatedByFilled()
	 * @method bool isUpdatedByChanged()
	 * @method \int remindActualUpdatedBy()
	 * @method \int requireUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_File resetUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_File unsetUpdatedBy()
	 * @method \int fillUpdatedBy()
	 * @method \Bitrix\Main\EO_User getUpdateUser()
	 * @method \Bitrix\Main\EO_User remindActualUpdateUser()
	 * @method \Bitrix\Main\EO_User requireUpdateUser()
	 * @method \Bitrix\Disk\Internals\EO_File setUpdateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetUpdateUser()
	 * @method \Bitrix\Disk\Internals\EO_File unsetUpdateUser()
	 * @method bool hasUpdateUser()
	 * @method bool isUpdateUserFilled()
	 * @method bool isUpdateUserChanged()
	 * @method \Bitrix\Main\EO_User fillUpdateUser()
	 * @method \int getDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_File setDeletedBy(\int|\Bitrix\Main\DB\SqlExpression $deletedBy)
	 * @method bool hasDeletedBy()
	 * @method bool isDeletedByFilled()
	 * @method bool isDeletedByChanged()
	 * @method \int remindActualDeletedBy()
	 * @method \int requireDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_File resetDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_File unsetDeletedBy()
	 * @method \int fillDeletedBy()
	 * @method \Bitrix\Main\EO_User getDeleteUser()
	 * @method \Bitrix\Main\EO_User remindActualDeleteUser()
	 * @method \Bitrix\Main\EO_User requireDeleteUser()
	 * @method \Bitrix\Disk\Internals\EO_File setDeleteUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetDeleteUser()
	 * @method \Bitrix\Disk\Internals\EO_File unsetDeleteUser()
	 * @method bool hasDeleteUser()
	 * @method bool isDeleteUserFilled()
	 * @method bool isDeleteUserChanged()
	 * @method \Bitrix\Main\EO_User fillDeleteUser()
	 * @method \int getGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_File setGlobalContentVersion(\int|\Bitrix\Main\DB\SqlExpression $globalContentVersion)
	 * @method bool hasGlobalContentVersion()
	 * @method bool isGlobalContentVersionFilled()
	 * @method bool isGlobalContentVersionChanged()
	 * @method \int remindActualGlobalContentVersion()
	 * @method \int requireGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_File resetGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_File unsetGlobalContentVersion()
	 * @method \int fillGlobalContentVersion()
	 * @method \int getFileId()
	 * @method \Bitrix\Disk\Internals\EO_File setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Disk\Internals\EO_File resetFileId()
	 * @method \Bitrix\Disk\Internals\EO_File unsetFileId()
	 * @method \int fillFileId()
	 * @method \Bitrix\Main\EO_File getFileContent()
	 * @method \Bitrix\Main\EO_File remindActualFileContent()
	 * @method \Bitrix\Main\EO_File requireFileContent()
	 * @method \Bitrix\Disk\Internals\EO_File setFileContent(\Bitrix\Main\EO_File $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetFileContent()
	 * @method \Bitrix\Disk\Internals\EO_File unsetFileContent()
	 * @method bool hasFileContent()
	 * @method bool isFileContentFilled()
	 * @method bool isFileContentChanged()
	 * @method \Bitrix\Main\EO_File fillFileContent()
	 * @method \int getSize()
	 * @method \Bitrix\Disk\Internals\EO_File setSize(\int|\Bitrix\Main\DB\SqlExpression $size)
	 * @method bool hasSize()
	 * @method bool isSizeFilled()
	 * @method bool isSizeChanged()
	 * @method \int remindActualSize()
	 * @method \int requireSize()
	 * @method \Bitrix\Disk\Internals\EO_File resetSize()
	 * @method \Bitrix\Disk\Internals\EO_File unsetSize()
	 * @method \int fillSize()
	 * @method \string getExternalHash()
	 * @method \Bitrix\Disk\Internals\EO_File setExternalHash(\string|\Bitrix\Main\DB\SqlExpression $externalHash)
	 * @method bool hasExternalHash()
	 * @method bool isExternalHashFilled()
	 * @method bool isExternalHashChanged()
	 * @method \string remindActualExternalHash()
	 * @method \string requireExternalHash()
	 * @method \Bitrix\Disk\Internals\EO_File resetExternalHash()
	 * @method \Bitrix\Disk\Internals\EO_File unsetExternalHash()
	 * @method \string fillExternalHash()
	 * @method \string getEtag()
	 * @method \Bitrix\Disk\Internals\EO_File setEtag(\string|\Bitrix\Main\DB\SqlExpression $etag)
	 * @method bool hasEtag()
	 * @method bool isEtagFilled()
	 * @method bool isEtagChanged()
	 * @method \string remindActualEtag()
	 * @method \string requireEtag()
	 * @method \Bitrix\Disk\Internals\EO_File resetEtag()
	 * @method \Bitrix\Disk\Internals\EO_File unsetEtag()
	 * @method \string fillEtag()
	 * @method \string getDeletedType()
	 * @method \Bitrix\Disk\Internals\EO_File setDeletedType(\string|\Bitrix\Main\DB\SqlExpression $deletedType)
	 * @method bool hasDeletedType()
	 * @method bool isDeletedTypeFilled()
	 * @method bool isDeletedTypeChanged()
	 * @method \string remindActualDeletedType()
	 * @method \string requireDeletedType()
	 * @method \Bitrix\Disk\Internals\EO_File resetDeletedType()
	 * @method \Bitrix\Disk\Internals\EO_File unsetDeletedType()
	 * @method \string fillDeletedType()
	 * @method \string getTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_File setTypeFile(\string|\Bitrix\Main\DB\SqlExpression $typeFile)
	 * @method bool hasTypeFile()
	 * @method bool isTypeFileFilled()
	 * @method bool isTypeFileChanged()
	 * @method \string remindActualTypeFile()
	 * @method \string requireTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_File resetTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_File unsetTypeFile()
	 * @method \string fillTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathParent()
	 * @method \Bitrix\Disk\Internals\EO_File setPathParent(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetPathParent()
	 * @method \Bitrix\Disk\Internals\EO_File unsetPathParent()
	 * @method bool hasPathParent()
	 * @method bool isPathParentFilled()
	 * @method bool isPathParentChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathChild()
	 * @method \Bitrix\Disk\Internals\EO_File setPathChild(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetPathChild()
	 * @method \Bitrix\Disk\Internals\EO_File unsetPathChild()
	 * @method bool hasPathChild()
	 * @method bool isPathChildFilled()
	 * @method bool isPathChildChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathChild()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed getRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed remindActualRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed requireRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_File setRecentlyUsed(\Bitrix\Disk\Internals\EO_RecentlyUsed $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_File unsetRecentlyUsed()
	 * @method bool hasRecentlyUsed()
	 * @method bool isRecentlyUsedFilled()
	 * @method bool isRecentlyUsedChanged()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed fillRecentlyUsed()
	 * @method \int getPreviewId()
	 * @method \Bitrix\Disk\Internals\EO_File setPreviewId(\int|\Bitrix\Main\DB\SqlExpression $previewId)
	 * @method bool hasPreviewId()
	 * @method bool isPreviewIdFilled()
	 * @method bool isPreviewIdChanged()
	 * @method \int remindActualPreviewId()
	 * @method \int requirePreviewId()
	 * @method \Bitrix\Disk\Internals\EO_File resetPreviewId()
	 * @method \Bitrix\Disk\Internals\EO_File unsetPreviewId()
	 * @method \int fillPreviewId()
	 * @method \int getViewId()
	 * @method \Bitrix\Disk\Internals\EO_File setViewId(\int|\Bitrix\Main\DB\SqlExpression $viewId)
	 * @method bool hasViewId()
	 * @method bool isViewIdFilled()
	 * @method bool isViewIdChanged()
	 * @method \int remindActualViewId()
	 * @method \int requireViewId()
	 * @method \Bitrix\Disk\Internals\EO_File resetViewId()
	 * @method \Bitrix\Disk\Internals\EO_File unsetViewId()
	 * @method \int fillViewId()
	 * @method \string getSearchIndex()
	 * @method \string remindActualSearchIndex()
	 * @method \string requireSearchIndex()
	 * @method bool hasSearchIndex()
	 * @method bool isSearchIndexFilled()
	 * @method \Bitrix\Disk\Internals\EO_File unsetSearchIndex()
	 * @method \string fillSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex getHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex remindActualHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex requireHeadIndex()
	 * @method \Bitrix\Disk\Internals\EO_File setHeadIndex(\Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetHeadIndex()
	 * @method \Bitrix\Disk\Internals\EO_File unsetHeadIndex()
	 * @method bool hasHeadIndex()
	 * @method bool isHeadIndexFilled()
	 * @method bool isHeadIndexChanged()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex fillHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex getExtendedIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex remindActualExtendedIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex requireExtendedIndex()
	 * @method \Bitrix\Disk\Internals\EO_File setExtendedIndex(\Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetExtendedIndex()
	 * @method \Bitrix\Disk\Internals\EO_File unsetExtendedIndex()
	 * @method bool hasExtendedIndex()
	 * @method bool isExtendedIndexFilled()
	 * @method bool isExtendedIndexChanged()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex fillExtendedIndex()
	 * @method \boolean getHasSearchIndex()
	 * @method \boolean remindActualHasSearchIndex()
	 * @method \boolean requireHasSearchIndex()
	 * @method bool hasHasSearchIndex()
	 * @method bool isHasSearchIndexFilled()
	 * @method \Bitrix\Disk\Internals\EO_File unsetHasSearchIndex()
	 * @method \boolean fillHasSearchIndex()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject getTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject remindActualTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject requireTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_File setTrackedObject(\Bitrix\Disk\Internals\EO_TrackedObject $object)
	 * @method \Bitrix\Disk\Internals\EO_File resetTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_File unsetTrackedObject()
	 * @method bool hasTrackedObject()
	 * @method bool isTrackedObjectFilled()
	 * @method bool isTrackedObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject fillTrackedObject()
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
	 * @method \Bitrix\Disk\Internals\EO_File set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_File reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_File unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_File wakeUp($data)
	 */
	class EO_File {
		/* @var \Bitrix\Disk\Internals\FileTable */
		static public $dataClass = '\Bitrix\Disk\Internals\FileTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_File_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 * @method \int[] getStorageIdList()
	 * @method \int[] fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage[] getStorageList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getStorageCollection()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection fillStorage()
	 * @method \int[] getRealObjectIdList()
	 * @method \int[] fillRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getRealObjectList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getRealObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock[] getLockList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getLockCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock_Collection fillLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl[] getTtlList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getTtlCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl_Collection fillTtl()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \string[] getContentProviderList()
	 * @method \string[] fillContentProvider()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getSyncUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getDeleteTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDeleteTime()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\EO_User[] getCreateUserList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getCreateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreateUser()
	 * @method \int[] getUpdatedByList()
	 * @method \int[] fillUpdatedBy()
	 * @method \Bitrix\Main\EO_User[] getUpdateUserList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getUpdateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUpdateUser()
	 * @method \int[] getDeletedByList()
	 * @method \int[] fillDeletedBy()
	 * @method \Bitrix\Main\EO_User[] getDeleteUserList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getDeleteUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillDeleteUser()
	 * @method \int[] getGlobalContentVersionList()
	 * @method \int[] fillGlobalContentVersion()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \Bitrix\Main\EO_File[] getFileContentList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getFileContentCollection()
	 * @method \Bitrix\Main\EO_File_Collection fillFileContent()
	 * @method \int[] getSizeList()
	 * @method \int[] fillSize()
	 * @method \string[] getExternalHashList()
	 * @method \string[] fillExternalHash()
	 * @method \string[] getEtagList()
	 * @method \string[] fillEtag()
	 * @method \string[] getDeletedTypeList()
	 * @method \string[] fillDeletedType()
	 * @method \string[] getTypeFileList()
	 * @method \string[] fillTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathParentList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getPathParentCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathChildList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getPathChildCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathChild()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed[] getRecentlyUsedList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getRecentlyUsedCollection()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection fillRecentlyUsed()
	 * @method \int[] getPreviewIdList()
	 * @method \int[] fillPreviewId()
	 * @method \int[] getViewIdList()
	 * @method \int[] fillViewId()
	 * @method \string[] getSearchIndexList()
	 * @method \string[] fillSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex[] getHeadIndexList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getHeadIndexCollection()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection fillHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex[] getExtendedIndexList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getExtendedIndexCollection()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection fillExtendedIndex()
	 * @method \boolean[] getHasSearchIndexList()
	 * @method \boolean[] fillHasSearchIndex()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject[] getTrackedObjectList()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection getTrackedObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject_Collection fillTrackedObject()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_File $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_File $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_File getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_File[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_File $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_File_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_File current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_File_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\FileTable */
		static public $dataClass = '\Bitrix\Disk\Internals\FileTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_File_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_File fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_File_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_File fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection fetchCollection()
	 */
	class EO_File_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_File createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_File_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_File wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_File_Collection wakeUpCollection($rows)
	 */
	class EO_File_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\RecentlyUsedTable:disk/lib/internals/recentlyused.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_RecentlyUsed
	 * @see \Bitrix\Disk\Internals\RecentlyUsedTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed resetUserId()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
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
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_RecentlyUsed wakeUp($data)
	 */
	class EO_RecentlyUsed {
		/* @var \Bitrix\Disk\Internals\RecentlyUsedTable */
		static public $dataClass = '\Bitrix\Disk\Internals\RecentlyUsedTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_RecentlyUsed_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_RecentlyUsed $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_RecentlyUsed $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_RecentlyUsed $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RecentlyUsed_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\RecentlyUsedTable */
		static public $dataClass = '\Bitrix\Disk\Internals\RecentlyUsedTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RecentlyUsed_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RecentlyUsed_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection fetchCollection()
	 */
	class EO_RecentlyUsed_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection wakeUpCollection($rows)
	 */
	class EO_RecentlyUsed_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\TmpFileTable:disk/lib/internals/tmpfile.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_TmpFile
	 * @see \Bitrix\Disk\Internals\TmpFileTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getToken()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setToken(\string|\Bitrix\Main\DB\SqlExpression $token)
	 * @method bool hasToken()
	 * @method bool isTokenFilled()
	 * @method bool isTokenChanged()
	 * @method \string remindActualToken()
	 * @method \string requireToken()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetToken()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetToken()
	 * @method \string fillToken()
	 * @method \string getFilename()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setFilename(\string|\Bitrix\Main\DB\SqlExpression $filename)
	 * @method bool hasFilename()
	 * @method bool isFilenameFilled()
	 * @method bool isFilenameChanged()
	 * @method \string remindActualFilename()
	 * @method \string requireFilename()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetFilename()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetFilename()
	 * @method \string fillFilename()
	 * @method \string getContentType()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setContentType(\string|\Bitrix\Main\DB\SqlExpression $contentType)
	 * @method bool hasContentType()
	 * @method bool isContentTypeFilled()
	 * @method bool isContentTypeChanged()
	 * @method \string remindActualContentType()
	 * @method \string requireContentType()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetContentType()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetContentType()
	 * @method \string fillContentType()
	 * @method \string getPath()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setPath(\string|\Bitrix\Main\DB\SqlExpression $path)
	 * @method bool hasPath()
	 * @method bool isPathFilled()
	 * @method bool isPathChanged()
	 * @method \string remindActualPath()
	 * @method \string requirePath()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetPath()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetPath()
	 * @method \string fillPath()
	 * @method \int getBucketId()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setBucketId(\int|\Bitrix\Main\DB\SqlExpression $bucketId)
	 * @method bool hasBucketId()
	 * @method bool isBucketIdFilled()
	 * @method bool isBucketIdChanged()
	 * @method \int remindActualBucketId()
	 * @method \int requireBucketId()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetBucketId()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetBucketId()
	 * @method \int fillBucketId()
	 * @method \int getSize()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setSize(\int|\Bitrix\Main\DB\SqlExpression $size)
	 * @method bool hasSize()
	 * @method bool isSizeFilled()
	 * @method bool isSizeChanged()
	 * @method \int remindActualSize()
	 * @method \int requireSize()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetSize()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetSize()
	 * @method \int fillSize()
	 * @method \int getReceivedSize()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setReceivedSize(\int|\Bitrix\Main\DB\SqlExpression $receivedSize)
	 * @method bool hasReceivedSize()
	 * @method bool isReceivedSizeFilled()
	 * @method bool isReceivedSizeChanged()
	 * @method \int remindActualReceivedSize()
	 * @method \int requireReceivedSize()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetReceivedSize()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetReceivedSize()
	 * @method \int fillReceivedSize()
	 * @method \int getWidth()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setWidth(\int|\Bitrix\Main\DB\SqlExpression $width)
	 * @method bool hasWidth()
	 * @method bool isWidthFilled()
	 * @method bool isWidthChanged()
	 * @method \int remindActualWidth()
	 * @method \int requireWidth()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetWidth()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetWidth()
	 * @method \int fillWidth()
	 * @method \int getHeight()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setHeight(\int|\Bitrix\Main\DB\SqlExpression $height)
	 * @method bool hasHeight()
	 * @method bool isHeightFilled()
	 * @method bool isHeightChanged()
	 * @method \int remindActualHeight()
	 * @method \int requireHeight()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetHeight()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetHeight()
	 * @method \int fillHeight()
	 * @method \boolean getIsCloud()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setIsCloud(\boolean|\Bitrix\Main\DB\SqlExpression $isCloud)
	 * @method bool hasIsCloud()
	 * @method bool isIsCloudFilled()
	 * @method bool isIsCloudChanged()
	 * @method \boolean remindActualIsCloud()
	 * @method \boolean requireIsCloud()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetIsCloud()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetIsCloud()
	 * @method \boolean fillIsCloud()
	 * @method \boolean getIrrelevant()
	 * @method \boolean remindActualIrrelevant()
	 * @method \boolean requireIrrelevant()
	 * @method bool hasIrrelevant()
	 * @method bool isIrrelevantFilled()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetIrrelevant()
	 * @method \boolean fillIrrelevant()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\EO_User getCreateUser()
	 * @method \Bitrix\Main\EO_User remindActualCreateUser()
	 * @method \Bitrix\Main\EO_User requireCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setCreateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetCreateUser()
	 * @method bool hasCreateUser()
	 * @method bool isCreateUserFilled()
	 * @method bool isCreateUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreateUser()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
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
	 * @method \Bitrix\Disk\Internals\EO_TmpFile set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_TmpFile reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_TmpFile unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_TmpFile wakeUp($data)
	 */
	class EO_TmpFile {
		/* @var \Bitrix\Disk\Internals\TmpFileTable */
		static public $dataClass = '\Bitrix\Disk\Internals\TmpFileTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_TmpFile_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTokenList()
	 * @method \string[] fillToken()
	 * @method \string[] getFilenameList()
	 * @method \string[] fillFilename()
	 * @method \string[] getContentTypeList()
	 * @method \string[] fillContentType()
	 * @method \string[] getPathList()
	 * @method \string[] fillPath()
	 * @method \int[] getBucketIdList()
	 * @method \int[] fillBucketId()
	 * @method \int[] getSizeList()
	 * @method \int[] fillSize()
	 * @method \int[] getReceivedSizeList()
	 * @method \int[] fillReceivedSize()
	 * @method \int[] getWidthList()
	 * @method \int[] fillWidth()
	 * @method \int[] getHeightList()
	 * @method \int[] fillHeight()
	 * @method \boolean[] getIsCloudList()
	 * @method \boolean[] fillIsCloud()
	 * @method \boolean[] getIrrelevantList()
	 * @method \boolean[] fillIrrelevant()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\EO_User[] getCreateUserList()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile_Collection getCreateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreateUser()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_TmpFile $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_TmpFile $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_TmpFile getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_TmpFile[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_TmpFile $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_TmpFile_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_TmpFile current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TmpFile_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\TmpFileTable */
		static public $dataClass = '\Bitrix\Disk\Internals\TmpFileTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TmpFile_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TmpFile_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_TmpFile fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile_Collection fetchCollection()
	 */
	class EO_TmpFile_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_TmpFile createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_TmpFile_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_TmpFile_Collection wakeUpCollection($rows)
	 */
	class EO_TmpFile_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\ObjectLockTable:disk/lib/internals/objectlock.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ObjectLock
	 * @see \Bitrix\Disk\Internals\ObjectLockTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getToken()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock setToken(\string|\Bitrix\Main\DB\SqlExpression $token)
	 * @method bool hasToken()
	 * @method bool isTokenFilled()
	 * @method bool isTokenChanged()
	 * @method \string remindActualToken()
	 * @method \string requireToken()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock resetToken()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unsetToken()
	 * @method \string fillToken()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object getObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock setObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock resetObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillObject()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \boolean getIsReadyAutoUnlock()
	 * @method \boolean remindActualIsReadyAutoUnlock()
	 * @method \boolean requireIsReadyAutoUnlock()
	 * @method bool hasIsReadyAutoUnlock()
	 * @method bool isIsReadyAutoUnlockFilled()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unsetIsReadyAutoUnlock()
	 * @method \boolean fillIsReadyAutoUnlock()
	 * @method \Bitrix\Main\Type\DateTime getExpiryTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock setExpiryTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $expiryTime)
	 * @method bool hasExpiryTime()
	 * @method bool isExpiryTimeFilled()
	 * @method bool isExpiryTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualExpiryTime()
	 * @method \Bitrix\Main\Type\DateTime requireExpiryTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock resetExpiryTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unsetExpiryTime()
	 * @method \Bitrix\Main\Type\DateTime fillExpiryTime()
	 * @method \string getType()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock resetType()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unsetType()
	 * @method \string fillType()
	 * @method \int getIsExclusive()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock setIsExclusive(\int|\Bitrix\Main\DB\SqlExpression $isExclusive)
	 * @method bool hasIsExclusive()
	 * @method bool isIsExclusiveFilled()
	 * @method bool isIsExclusiveChanged()
	 * @method \int remindActualIsExclusive()
	 * @method \int requireIsExclusive()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock resetIsExclusive()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unsetIsExclusive()
	 * @method \int fillIsExclusive()
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
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_ObjectLock wakeUp($data)
	 */
	class EO_ObjectLock {
		/* @var \Bitrix\Disk\Internals\ObjectLockTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectLockTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ObjectLock_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTokenList()
	 * @method \string[] fillToken()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getObjectList()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillObject()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \boolean[] getIsReadyAutoUnlockList()
	 * @method \boolean[] fillIsReadyAutoUnlock()
	 * @method \Bitrix\Main\Type\DateTime[] getExpiryTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillExpiryTime()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getIsExclusiveList()
	 * @method \int[] fillIsExclusive()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_ObjectLock $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_ObjectLock $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_ObjectLock $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_ObjectLock_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ObjectLock_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\ObjectLockTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectLockTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ObjectLock_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ObjectLock_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock_Collection fetchCollection()
	 */
	class EO_ObjectLock_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock_Collection wakeUpCollection($rows)
	 */
	class EO_ObjectLock_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\TrackedObjectTable:disk/lib/internals/trackedobjecttable.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_TrackedObject
	 * @see \Bitrix\Disk\Internals\TrackedObjectTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject resetUserId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \int getRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject setRealObjectId(\int|\Bitrix\Main\DB\SqlExpression $realObjectId)
	 * @method bool hasRealObjectId()
	 * @method bool isRealObjectIdFilled()
	 * @method bool isRealObjectIdChanged()
	 * @method \int remindActualRealObjectId()
	 * @method \int requireRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject resetRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject unsetRealObjectId()
	 * @method \int fillRealObjectId()
	 * @method \int getAttachedObjectId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject setAttachedObjectId(\int|\Bitrix\Main\DB\SqlExpression $attachedObjectId)
	 * @method bool hasAttachedObjectId()
	 * @method bool isAttachedObjectIdFilled()
	 * @method bool isAttachedObjectIdChanged()
	 * @method \int remindActualAttachedObjectId()
	 * @method \int requireAttachedObjectId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject resetAttachedObjectId()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject unsetAttachedObjectId()
	 * @method \int fillAttachedObjectId()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject resetUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
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
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_TrackedObject wakeUp($data)
	 */
	class EO_TrackedObject {
		/* @var \Bitrix\Disk\Internals\TrackedObjectTable */
		static public $dataClass = '\Bitrix\Disk\Internals\TrackedObjectTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_TrackedObject_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \int[] getRealObjectIdList()
	 * @method \int[] fillRealObjectId()
	 * @method \int[] getAttachedObjectIdList()
	 * @method \int[] fillAttachedObjectId()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_TrackedObject $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_TrackedObject $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_TrackedObject $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_TrackedObject_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TrackedObject_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\TrackedObjectTable */
		static public $dataClass = '\Bitrix\Disk\Internals\TrackedObjectTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TrackedObject_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TrackedObject_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject_Collection fetchCollection()
	 */
	class EO_TrackedObject_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject_Collection wakeUpCollection($rows)
	 */
	class EO_TrackedObject_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\EditSessionTable:disk/lib/internals/editsession.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_EditSession
	 * @see \Bitrix\Disk\Internals\EditSessionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File getObject()
	 * @method \Bitrix\Disk\Internals\EO_File remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_File requireObject()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setObject(\Bitrix\Disk\Internals\EO_File $object)
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetObject()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_File fillObject()
	 * @method \int getVersionId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setVersionId(\int|\Bitrix\Main\DB\SqlExpression $versionId)
	 * @method bool hasVersionId()
	 * @method bool isVersionIdFilled()
	 * @method bool isVersionIdChanged()
	 * @method \int remindActualVersionId()
	 * @method \int requireVersionId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetVersionId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetVersionId()
	 * @method \int fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version getVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version remindActualVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version requireVersion()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setVersion(\Bitrix\Disk\Internals\EO_Version $object)
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetVersion()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetVersion()
	 * @method bool hasVersion()
	 * @method bool isVersionFilled()
	 * @method bool isVersionChanged()
	 * @method \Bitrix\Disk\Internals\EO_Version fillVersion()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetUserId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getOwnerId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setOwnerId(\int|\Bitrix\Main\DB\SqlExpression $ownerId)
	 * @method bool hasOwnerId()
	 * @method bool isOwnerIdFilled()
	 * @method bool isOwnerIdChanged()
	 * @method \int remindActualOwnerId()
	 * @method \int requireOwnerId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetOwnerId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetOwnerId()
	 * @method \int fillOwnerId()
	 * @method \boolean getIsExclusive()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setIsExclusive(\boolean|\Bitrix\Main\DB\SqlExpression $isExclusive)
	 * @method bool hasIsExclusive()
	 * @method bool isIsExclusiveFilled()
	 * @method bool isIsExclusiveChanged()
	 * @method \boolean remindActualIsExclusive()
	 * @method \boolean requireIsExclusive()
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetIsExclusive()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetIsExclusive()
	 * @method \boolean fillIsExclusive()
	 * @method \string getService()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setService(\string|\Bitrix\Main\DB\SqlExpression $service)
	 * @method bool hasService()
	 * @method bool isServiceFilled()
	 * @method bool isServiceChanged()
	 * @method \string remindActualService()
	 * @method \string requireService()
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetService()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetService()
	 * @method \string fillService()
	 * @method \string getServiceFileId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setServiceFileId(\string|\Bitrix\Main\DB\SqlExpression $serviceFileId)
	 * @method bool hasServiceFileId()
	 * @method bool isServiceFileIdFilled()
	 * @method bool isServiceFileIdChanged()
	 * @method \string remindActualServiceFileId()
	 * @method \string requireServiceFileId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetServiceFileId()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetServiceFileId()
	 * @method \string fillServiceFileId()
	 * @method \string getServiceFileLink()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setServiceFileLink(\string|\Bitrix\Main\DB\SqlExpression $serviceFileLink)
	 * @method bool hasServiceFileLink()
	 * @method bool isServiceFileLinkFilled()
	 * @method bool isServiceFileLinkChanged()
	 * @method \string remindActualServiceFileLink()
	 * @method \string requireServiceFileLink()
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetServiceFileLink()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetServiceFileLink()
	 * @method \string fillServiceFileLink()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_EditSession setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_EditSession resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_EditSession unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
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
	 * @method \Bitrix\Disk\Internals\EO_EditSession set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_EditSession reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_EditSession unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_EditSession wakeUp($data)
	 */
	class EO_EditSession {
		/* @var \Bitrix\Disk\Internals\EditSessionTable */
		static public $dataClass = '\Bitrix\Disk\Internals\EditSessionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_EditSession_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File[] getObjectList()
	 * @method \Bitrix\Disk\Internals\EO_EditSession_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection fillObject()
	 * @method \int[] getVersionIdList()
	 * @method \int[] fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version[] getVersionList()
	 * @method \Bitrix\Disk\Internals\EO_EditSession_Collection getVersionCollection()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection fillVersion()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getOwnerIdList()
	 * @method \int[] fillOwnerId()
	 * @method \boolean[] getIsExclusiveList()
	 * @method \boolean[] fillIsExclusive()
	 * @method \string[] getServiceList()
	 * @method \string[] fillService()
	 * @method \string[] getServiceFileIdList()
	 * @method \string[] fillServiceFileId()
	 * @method \string[] getServiceFileLinkList()
	 * @method \string[] fillServiceFileLink()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_EditSession $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_EditSession $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_EditSession getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_EditSession[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_EditSession $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_EditSession_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_EditSession current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_EditSession_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\EditSessionTable */
		static public $dataClass = '\Bitrix\Disk\Internals\EditSessionTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_EditSession_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_EditSession fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_EditSession_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_EditSession_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_EditSession fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_EditSession_Collection fetchCollection()
	 */
	class EO_EditSession_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_EditSession createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_EditSession_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_EditSession wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_EditSession_Collection wakeUpCollection($rows)
	 */
	class EO_EditSession_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\VolumeDeletedLogTable:disk/lib/internals/volumedeletedlog.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_VolumeDeletedLog
	 * @see \Bitrix\Disk\Internals\VolumeDeletedLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getStorageId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setStorageId(\int|\Bitrix\Main\DB\SqlExpression $storageId)
	 * @method bool hasStorageId()
	 * @method bool isStorageIdFilled()
	 * @method bool isStorageIdChanged()
	 * @method \int remindActualStorageId()
	 * @method \int requireStorageId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetStorageId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetStorageId()
	 * @method \int fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage getStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage remindActualStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage requireStorage()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setStorage(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetStorage()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetStorage()
	 * @method bool hasStorage()
	 * @method bool isStorageFilled()
	 * @method bool isStorageChanged()
	 * @method \Bitrix\Disk\Internals\EO_Storage fillStorage()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \int getObjectParentId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectParentId(\int|\Bitrix\Main\DB\SqlExpression $objectParentId)
	 * @method bool hasObjectParentId()
	 * @method bool isObjectParentIdFilled()
	 * @method bool isObjectParentIdChanged()
	 * @method \int remindActualObjectParentId()
	 * @method \int requireObjectParentId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectParentId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectParentId()
	 * @method \int fillObjectParentId()
	 * @method \string getObjectType()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectType(\string|\Bitrix\Main\DB\SqlExpression $objectType)
	 * @method bool hasObjectType()
	 * @method bool isObjectTypeFilled()
	 * @method bool isObjectTypeChanged()
	 * @method \string remindActualObjectType()
	 * @method \string requireObjectType()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectType()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectType()
	 * @method \string fillObjectType()
	 * @method \string getObjectName()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectName(\string|\Bitrix\Main\DB\SqlExpression $objectName)
	 * @method bool hasObjectName()
	 * @method bool isObjectNameFilled()
	 * @method bool isObjectNameChanged()
	 * @method \string remindActualObjectName()
	 * @method \string requireObjectName()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectName()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectName()
	 * @method \string fillObjectName()
	 * @method \string getObjectPath()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectPath(\string|\Bitrix\Main\DB\SqlExpression $objectPath)
	 * @method bool hasObjectPath()
	 * @method bool isObjectPathFilled()
	 * @method bool isObjectPathChanged()
	 * @method \string remindActualObjectPath()
	 * @method \string requireObjectPath()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectPath()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectPath()
	 * @method \string fillObjectPath()
	 * @method \int getObjectSize()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectSize(\int|\Bitrix\Main\DB\SqlExpression $objectSize)
	 * @method bool hasObjectSize()
	 * @method bool isObjectSizeFilled()
	 * @method bool isObjectSizeChanged()
	 * @method \int remindActualObjectSize()
	 * @method \int requireObjectSize()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectSize()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectSize()
	 * @method \int fillObjectSize()
	 * @method \int getObjectCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $objectCreatedBy)
	 * @method bool hasObjectCreatedBy()
	 * @method bool isObjectCreatedByFilled()
	 * @method bool isObjectCreatedByChanged()
	 * @method \int remindActualObjectCreatedBy()
	 * @method \int requireObjectCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectCreatedBy()
	 * @method \int fillObjectCreatedBy()
	 * @method \Bitrix\Main\EO_User getObjectCreateUser()
	 * @method \Bitrix\Main\EO_User remindActualObjectCreateUser()
	 * @method \Bitrix\Main\EO_User requireObjectCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectCreateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectCreateUser()
	 * @method bool hasObjectCreateUser()
	 * @method bool isObjectCreateUserFilled()
	 * @method bool isObjectCreateUserChanged()
	 * @method \Bitrix\Main\EO_User fillObjectCreateUser()
	 * @method \int getObjectUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectUpdatedBy(\int|\Bitrix\Main\DB\SqlExpression $objectUpdatedBy)
	 * @method bool hasObjectUpdatedBy()
	 * @method bool isObjectUpdatedByFilled()
	 * @method bool isObjectUpdatedByChanged()
	 * @method \int remindActualObjectUpdatedBy()
	 * @method \int requireObjectUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectUpdatedBy()
	 * @method \int fillObjectUpdatedBy()
	 * @method \Bitrix\Main\EO_User getObjectUpdateUser()
	 * @method \Bitrix\Main\EO_User remindActualObjectUpdateUser()
	 * @method \Bitrix\Main\EO_User requireObjectUpdateUser()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setObjectUpdateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetObjectUpdateUser()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetObjectUpdateUser()
	 * @method bool hasObjectUpdateUser()
	 * @method bool isObjectUpdateUserFilled()
	 * @method bool isObjectUpdateUserChanged()
	 * @method \Bitrix\Main\EO_User fillObjectUpdateUser()
	 * @method \int getVersionId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setVersionId(\int|\Bitrix\Main\DB\SqlExpression $versionId)
	 * @method bool hasVersionId()
	 * @method bool isVersionIdFilled()
	 * @method bool isVersionIdChanged()
	 * @method \int remindActualVersionId()
	 * @method \int requireVersionId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetVersionId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetVersionId()
	 * @method \int fillVersionId()
	 * @method \string getVersionName()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setVersionName(\string|\Bitrix\Main\DB\SqlExpression $versionName)
	 * @method bool hasVersionName()
	 * @method bool isVersionNameFilled()
	 * @method bool isVersionNameChanged()
	 * @method \string remindActualVersionName()
	 * @method \string requireVersionName()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetVersionName()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetVersionName()
	 * @method \string fillVersionName()
	 * @method \int getFileId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetFileId()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetFileId()
	 * @method \int fillFileId()
	 * @method \Bitrix\Main\Type\DateTime getDeletedTime()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setDeletedTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $deletedTime)
	 * @method bool hasDeletedTime()
	 * @method bool isDeletedTimeFilled()
	 * @method bool isDeletedTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDeletedTime()
	 * @method \Bitrix\Main\Type\DateTime requireDeletedTime()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetDeletedTime()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetDeletedTime()
	 * @method \Bitrix\Main\Type\DateTime fillDeletedTime()
	 * @method \int getDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setDeletedBy(\int|\Bitrix\Main\DB\SqlExpression $deletedBy)
	 * @method bool hasDeletedBy()
	 * @method bool isDeletedByFilled()
	 * @method bool isDeletedByChanged()
	 * @method \int remindActualDeletedBy()
	 * @method \int requireDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetDeletedBy()
	 * @method \int fillDeletedBy()
	 * @method \Bitrix\Main\EO_User getDeletedByUser()
	 * @method \Bitrix\Main\EO_User remindActualDeletedByUser()
	 * @method \Bitrix\Main\EO_User requireDeletedByUser()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setDeletedByUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetDeletedByUser()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetDeletedByUser()
	 * @method bool hasDeletedByUser()
	 * @method bool isDeletedByUserFilled()
	 * @method bool isDeletedByUserChanged()
	 * @method \Bitrix\Main\EO_User fillDeletedByUser()
	 * @method \string getOperation()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog setOperation(\string|\Bitrix\Main\DB\SqlExpression $operation)
	 * @method bool hasOperation()
	 * @method bool isOperationFilled()
	 * @method bool isOperationChanged()
	 * @method \string remindActualOperation()
	 * @method \string requireOperation()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog resetOperation()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unsetOperation()
	 * @method \string fillOperation()
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
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_VolumeDeletedLog wakeUp($data)
	 */
	class EO_VolumeDeletedLog {
		/* @var \Bitrix\Disk\Internals\VolumeDeletedLogTable */
		static public $dataClass = '\Bitrix\Disk\Internals\VolumeDeletedLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_VolumeDeletedLog_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getStorageIdList()
	 * @method \int[] fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage[] getStorageList()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection getStorageCollection()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection fillStorage()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \int[] getObjectParentIdList()
	 * @method \int[] fillObjectParentId()
	 * @method \string[] getObjectTypeList()
	 * @method \string[] fillObjectType()
	 * @method \string[] getObjectNameList()
	 * @method \string[] fillObjectName()
	 * @method \string[] getObjectPathList()
	 * @method \string[] fillObjectPath()
	 * @method \int[] getObjectSizeList()
	 * @method \int[] fillObjectSize()
	 * @method \int[] getObjectCreatedByList()
	 * @method \int[] fillObjectCreatedBy()
	 * @method \Bitrix\Main\EO_User[] getObjectCreateUserList()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection getObjectCreateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillObjectCreateUser()
	 * @method \int[] getObjectUpdatedByList()
	 * @method \int[] fillObjectUpdatedBy()
	 * @method \Bitrix\Main\EO_User[] getObjectUpdateUserList()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection getObjectUpdateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillObjectUpdateUser()
	 * @method \int[] getVersionIdList()
	 * @method \int[] fillVersionId()
	 * @method \string[] getVersionNameList()
	 * @method \string[] fillVersionName()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \Bitrix\Main\Type\DateTime[] getDeletedTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDeletedTime()
	 * @method \int[] getDeletedByList()
	 * @method \int[] fillDeletedBy()
	 * @method \Bitrix\Main\EO_User[] getDeletedByUserList()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection getDeletedByUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillDeletedByUser()
	 * @method \string[] getOperationList()
	 * @method \string[] fillOperation()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_VolumeDeletedLog $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_VolumeDeletedLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_VolumeDeletedLog $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_VolumeDeletedLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\VolumeDeletedLogTable */
		static public $dataClass = '\Bitrix\Disk\Internals\VolumeDeletedLogTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_VolumeDeletedLog_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_VolumeDeletedLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection fetchCollection()
	 */
	class EO_VolumeDeletedLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection wakeUpCollection($rows)
	 */
	class EO_VolumeDeletedLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\DeletedLogTable:disk/lib/internals/deletedlog.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_DeletedLog
	 * @see \Bitrix\Disk\Internals\DeletedLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog resetUserId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getStorageId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog setStorageId(\int|\Bitrix\Main\DB\SqlExpression $storageId)
	 * @method bool hasStorageId()
	 * @method bool isStorageIdFilled()
	 * @method bool isStorageIdChanged()
	 * @method \int remindActualStorageId()
	 * @method \int requireStorageId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog resetStorageId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog unsetStorageId()
	 * @method \int fillStorageId()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \string getType()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog resetType()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog unsetType()
	 * @method \string fillType()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
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
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_DeletedLog wakeUp($data)
	 */
	class EO_DeletedLog {
		/* @var \Bitrix\Disk\Internals\DeletedLogTable */
		static public $dataClass = '\Bitrix\Disk\Internals\DeletedLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_DeletedLog_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getStorageIdList()
	 * @method \int[] fillStorageId()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_DeletedLog $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_DeletedLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_DeletedLog $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_DeletedLog_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_DeletedLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\DeletedLogTable */
		static public $dataClass = '\Bitrix\Disk\Internals\DeletedLogTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DeletedLog_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_DeletedLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog_Collection fetchCollection()
	 */
	class EO_DeletedLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_DeletedLog_Collection wakeUpCollection($rows)
	 */
	class EO_DeletedLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\AttachedViewTypeTable:disk/lib/internals/attachedviewtype.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_AttachedViewType
	 * @see \Bitrix\Disk\Internals\AttachedViewTypeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getEntityType()
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \int getEntityId()
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \string getValue()
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType resetValue()
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType unsetValue()
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
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_AttachedViewType wakeUp($data)
	 */
	class EO_AttachedViewType {
		/* @var \Bitrix\Disk\Internals\AttachedViewTypeTable */
		static public $dataClass = '\Bitrix\Disk\Internals\AttachedViewTypeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_AttachedViewType_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getEntityTypeList()
	 * @method \int[] getEntityIdList()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_AttachedViewType $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_AttachedViewType $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_AttachedViewType $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_AttachedViewType_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_AttachedViewType_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\AttachedViewTypeTable */
		static public $dataClass = '\Bitrix\Disk\Internals\AttachedViewTypeTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_AttachedViewType_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_AttachedViewType_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType_Collection fetchCollection()
	 */
	class EO_AttachedViewType_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_AttachedViewType_Collection wakeUpCollection($rows)
	 */
	class EO_AttachedViewType_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\FolderTable:disk/lib/internals/folder.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Folder
	 * @see \Bitrix\Disk\Internals\FolderTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_Folder setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Disk\Internals\EO_Folder setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetName()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetName()
	 * @method \string fillName()
	 * @method \string getType()
	 * @method \Bitrix\Disk\Internals\EO_Folder setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetType()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetType()
	 * @method \string fillType()
	 * @method \string getCode()
	 * @method \Bitrix\Disk\Internals\EO_Folder setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetCode()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetCode()
	 * @method \string fillCode()
	 * @method \string getXmlId()
	 * @method \Bitrix\Disk\Internals\EO_Folder setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetXmlId()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \int getStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Folder setStorageId(\int|\Bitrix\Main\DB\SqlExpression $storageId)
	 * @method bool hasStorageId()
	 * @method bool isStorageIdFilled()
	 * @method bool isStorageIdChanged()
	 * @method \int remindActualStorageId()
	 * @method \int requireStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetStorageId()
	 * @method \int fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage getStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage remindActualStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage requireStorage()
	 * @method \Bitrix\Disk\Internals\EO_Folder setStorage(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetStorage()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetStorage()
	 * @method bool hasStorage()
	 * @method bool isStorageFilled()
	 * @method bool isStorageChanged()
	 * @method \Bitrix\Disk\Internals\EO_Storage fillStorage()
	 * @method \int getRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Folder setRealObjectId(\int|\Bitrix\Main\DB\SqlExpression $realObjectId)
	 * @method bool hasRealObjectId()
	 * @method bool isRealObjectIdFilled()
	 * @method bool isRealObjectIdChanged()
	 * @method \int remindActualRealObjectId()
	 * @method \int requireRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetRealObjectId()
	 * @method \int fillRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object getRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Folder setRealObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetRealObject()
	 * @method bool hasRealObject()
	 * @method bool isRealObjectFilled()
	 * @method bool isRealObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock getLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock remindActualLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock requireLock()
	 * @method \Bitrix\Disk\Internals\EO_Folder setLock(\Bitrix\Disk\Internals\EO_ObjectLock $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetLock()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetLock()
	 * @method bool hasLock()
	 * @method bool isLockFilled()
	 * @method bool isLockChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock fillLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl getTtl()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl remindActualTtl()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl requireTtl()
	 * @method \Bitrix\Disk\Internals\EO_Folder setTtl(\Bitrix\Disk\Internals\EO_ObjectTtl $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetTtl()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetTtl()
	 * @method bool hasTtl()
	 * @method bool isTtlFilled()
	 * @method bool isTtlChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl fillTtl()
	 * @method \int getParentId()
	 * @method \Bitrix\Disk\Internals\EO_Folder setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetParentId()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetParentId()
	 * @method \int fillParentId()
	 * @method \string getContentProvider()
	 * @method \Bitrix\Disk\Internals\EO_Folder setContentProvider(\string|\Bitrix\Main\DB\SqlExpression $contentProvider)
	 * @method bool hasContentProvider()
	 * @method bool isContentProviderFilled()
	 * @method bool isContentProviderChanged()
	 * @method \string remindActualContentProvider()
	 * @method \string requireContentProvider()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetContentProvider()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetContentProvider()
	 * @method \string fillContentProvider()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime getSyncUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder setSyncUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $syncUpdateTime)
	 * @method bool hasSyncUpdateTime()
	 * @method bool isSyncUpdateTimeFilled()
	 * @method bool isSyncUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireSyncUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetSyncUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime getDeleteTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder setDeleteTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $deleteTime)
	 * @method bool hasDeleteTime()
	 * @method bool isDeleteTimeFilled()
	 * @method bool isDeleteTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDeleteTime()
	 * @method \Bitrix\Main\Type\DateTime requireDeleteTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetDeleteTime()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetDeleteTime()
	 * @method \Bitrix\Main\Type\DateTime fillDeleteTime()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Folder setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\EO_User getCreateUser()
	 * @method \Bitrix\Main\EO_User remindActualCreateUser()
	 * @method \Bitrix\Main\EO_User requireCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_Folder setCreateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetCreateUser()
	 * @method bool hasCreateUser()
	 * @method bool isCreateUserFilled()
	 * @method bool isCreateUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreateUser()
	 * @method \int getUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Folder setUpdatedBy(\int|\Bitrix\Main\DB\SqlExpression $updatedBy)
	 * @method bool hasUpdatedBy()
	 * @method bool isUpdatedByFilled()
	 * @method bool isUpdatedByChanged()
	 * @method \int remindActualUpdatedBy()
	 * @method \int requireUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetUpdatedBy()
	 * @method \int fillUpdatedBy()
	 * @method \Bitrix\Main\EO_User getUpdateUser()
	 * @method \Bitrix\Main\EO_User remindActualUpdateUser()
	 * @method \Bitrix\Main\EO_User requireUpdateUser()
	 * @method \Bitrix\Disk\Internals\EO_Folder setUpdateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetUpdateUser()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetUpdateUser()
	 * @method bool hasUpdateUser()
	 * @method bool isUpdateUserFilled()
	 * @method bool isUpdateUserChanged()
	 * @method \Bitrix\Main\EO_User fillUpdateUser()
	 * @method \int getDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_Folder setDeletedBy(\int|\Bitrix\Main\DB\SqlExpression $deletedBy)
	 * @method bool hasDeletedBy()
	 * @method bool isDeletedByFilled()
	 * @method bool isDeletedByChanged()
	 * @method \int remindActualDeletedBy()
	 * @method \int requireDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetDeletedBy()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetDeletedBy()
	 * @method \int fillDeletedBy()
	 * @method \Bitrix\Main\EO_User getDeleteUser()
	 * @method \Bitrix\Main\EO_User remindActualDeleteUser()
	 * @method \Bitrix\Main\EO_User requireDeleteUser()
	 * @method \Bitrix\Disk\Internals\EO_Folder setDeleteUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetDeleteUser()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetDeleteUser()
	 * @method bool hasDeleteUser()
	 * @method bool isDeleteUserFilled()
	 * @method bool isDeleteUserChanged()
	 * @method \Bitrix\Main\EO_User fillDeleteUser()
	 * @method \int getGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_Folder setGlobalContentVersion(\int|\Bitrix\Main\DB\SqlExpression $globalContentVersion)
	 * @method bool hasGlobalContentVersion()
	 * @method bool isGlobalContentVersionFilled()
	 * @method bool isGlobalContentVersionChanged()
	 * @method \int remindActualGlobalContentVersion()
	 * @method \int requireGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetGlobalContentVersion()
	 * @method \int fillGlobalContentVersion()
	 * @method \int getFileId()
	 * @method \Bitrix\Disk\Internals\EO_Folder setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetFileId()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetFileId()
	 * @method \int fillFileId()
	 * @method \Bitrix\Main\EO_File getFileContent()
	 * @method \Bitrix\Main\EO_File remindActualFileContent()
	 * @method \Bitrix\Main\EO_File requireFileContent()
	 * @method \Bitrix\Disk\Internals\EO_Folder setFileContent(\Bitrix\Main\EO_File $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetFileContent()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetFileContent()
	 * @method bool hasFileContent()
	 * @method bool isFileContentFilled()
	 * @method bool isFileContentChanged()
	 * @method \Bitrix\Main\EO_File fillFileContent()
	 * @method \int getSize()
	 * @method \Bitrix\Disk\Internals\EO_Folder setSize(\int|\Bitrix\Main\DB\SqlExpression $size)
	 * @method bool hasSize()
	 * @method bool isSizeFilled()
	 * @method bool isSizeChanged()
	 * @method \int remindActualSize()
	 * @method \int requireSize()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetSize()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetSize()
	 * @method \int fillSize()
	 * @method \string getExternalHash()
	 * @method \Bitrix\Disk\Internals\EO_Folder setExternalHash(\string|\Bitrix\Main\DB\SqlExpression $externalHash)
	 * @method bool hasExternalHash()
	 * @method bool isExternalHashFilled()
	 * @method bool isExternalHashChanged()
	 * @method \string remindActualExternalHash()
	 * @method \string requireExternalHash()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetExternalHash()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetExternalHash()
	 * @method \string fillExternalHash()
	 * @method \string getEtag()
	 * @method \Bitrix\Disk\Internals\EO_Folder setEtag(\string|\Bitrix\Main\DB\SqlExpression $etag)
	 * @method bool hasEtag()
	 * @method bool isEtagFilled()
	 * @method bool isEtagChanged()
	 * @method \string remindActualEtag()
	 * @method \string requireEtag()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetEtag()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetEtag()
	 * @method \string fillEtag()
	 * @method \string getDeletedType()
	 * @method \Bitrix\Disk\Internals\EO_Folder setDeletedType(\string|\Bitrix\Main\DB\SqlExpression $deletedType)
	 * @method bool hasDeletedType()
	 * @method bool isDeletedTypeFilled()
	 * @method bool isDeletedTypeChanged()
	 * @method \string remindActualDeletedType()
	 * @method \string requireDeletedType()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetDeletedType()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetDeletedType()
	 * @method \string fillDeletedType()
	 * @method \string getTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_Folder setTypeFile(\string|\Bitrix\Main\DB\SqlExpression $typeFile)
	 * @method bool hasTypeFile()
	 * @method bool isTypeFileFilled()
	 * @method bool isTypeFileChanged()
	 * @method \string remindActualTypeFile()
	 * @method \string requireTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetTypeFile()
	 * @method \string fillTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathParent()
	 * @method \Bitrix\Disk\Internals\EO_Folder setPathParent(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetPathParent()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetPathParent()
	 * @method bool hasPathParent()
	 * @method bool isPathParentFilled()
	 * @method bool isPathParentChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathChild()
	 * @method \Bitrix\Disk\Internals\EO_Folder setPathChild(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetPathChild()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetPathChild()
	 * @method bool hasPathChild()
	 * @method bool isPathChildFilled()
	 * @method bool isPathChildChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathChild()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed getRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed remindActualRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed requireRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_Folder setRecentlyUsed(\Bitrix\Disk\Internals\EO_RecentlyUsed $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetRecentlyUsed()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetRecentlyUsed()
	 * @method bool hasRecentlyUsed()
	 * @method bool isRecentlyUsedFilled()
	 * @method bool isRecentlyUsedChanged()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed fillRecentlyUsed()
	 * @method \int getPreviewId()
	 * @method \Bitrix\Disk\Internals\EO_Folder setPreviewId(\int|\Bitrix\Main\DB\SqlExpression $previewId)
	 * @method bool hasPreviewId()
	 * @method bool isPreviewIdFilled()
	 * @method bool isPreviewIdChanged()
	 * @method \int remindActualPreviewId()
	 * @method \int requirePreviewId()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetPreviewId()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetPreviewId()
	 * @method \int fillPreviewId()
	 * @method \int getViewId()
	 * @method \Bitrix\Disk\Internals\EO_Folder setViewId(\int|\Bitrix\Main\DB\SqlExpression $viewId)
	 * @method bool hasViewId()
	 * @method bool isViewIdFilled()
	 * @method bool isViewIdChanged()
	 * @method \int remindActualViewId()
	 * @method \int requireViewId()
	 * @method \Bitrix\Disk\Internals\EO_Folder resetViewId()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetViewId()
	 * @method \int fillViewId()
	 * @method \string getSearchIndex()
	 * @method \string remindActualSearchIndex()
	 * @method \string requireSearchIndex()
	 * @method bool hasSearchIndex()
	 * @method bool isSearchIndexFilled()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetSearchIndex()
	 * @method \string fillSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex getHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex remindActualHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex requireHeadIndex()
	 * @method \Bitrix\Disk\Internals\EO_Folder setHeadIndex(\Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetHeadIndex()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetHeadIndex()
	 * @method bool hasHeadIndex()
	 * @method bool isHeadIndexFilled()
	 * @method bool isHeadIndexChanged()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex fillHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex getExtendedIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex remindActualExtendedIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex requireExtendedIndex()
	 * @method \Bitrix\Disk\Internals\EO_Folder setExtendedIndex(\Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetExtendedIndex()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetExtendedIndex()
	 * @method bool hasExtendedIndex()
	 * @method bool isExtendedIndexFilled()
	 * @method bool isExtendedIndexChanged()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex fillExtendedIndex()
	 * @method \boolean getHasSearchIndex()
	 * @method \boolean remindActualHasSearchIndex()
	 * @method \boolean requireHasSearchIndex()
	 * @method bool hasHasSearchIndex()
	 * @method bool isHasSearchIndexFilled()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetHasSearchIndex()
	 * @method \boolean fillHasSearchIndex()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject getTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject remindActualTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject requireTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_Folder setTrackedObject(\Bitrix\Disk\Internals\EO_TrackedObject $object)
	 * @method \Bitrix\Disk\Internals\EO_Folder resetTrackedObject()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetTrackedObject()
	 * @method bool hasTrackedObject()
	 * @method bool isTrackedObjectFilled()
	 * @method bool isTrackedObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject fillTrackedObject()
	 * @method \boolean getHasSubfolders()
	 * @method \boolean remindActualHasSubfolders()
	 * @method \boolean requireHasSubfolders()
	 * @method bool hasHasSubfolders()
	 * @method bool isHasSubfoldersFilled()
	 * @method \Bitrix\Disk\Internals\EO_Folder unsetHasSubfolders()
	 * @method \boolean fillHasSubfolders()
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
	 * @method \Bitrix\Disk\Internals\EO_Folder set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_Folder reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_Folder unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_Folder wakeUp($data)
	 */
	class EO_Folder {
		/* @var \Bitrix\Disk\Internals\FolderTable */
		static public $dataClass = '\Bitrix\Disk\Internals\FolderTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Folder_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 * @method \int[] getStorageIdList()
	 * @method \int[] fillStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage[] getStorageList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getStorageCollection()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection fillStorage()
	 * @method \int[] getRealObjectIdList()
	 * @method \int[] fillRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getRealObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getRealObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock[] getLockList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getLockCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectLock_Collection fillLock()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl[] getTtlList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getTtlCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl_Collection fillTtl()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \string[] getContentProviderList()
	 * @method \string[] fillContentProvider()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getSyncUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillSyncUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getDeleteTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDeleteTime()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\EO_User[] getCreateUserList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getCreateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreateUser()
	 * @method \int[] getUpdatedByList()
	 * @method \int[] fillUpdatedBy()
	 * @method \Bitrix\Main\EO_User[] getUpdateUserList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getUpdateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUpdateUser()
	 * @method \int[] getDeletedByList()
	 * @method \int[] fillDeletedBy()
	 * @method \Bitrix\Main\EO_User[] getDeleteUserList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getDeleteUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillDeleteUser()
	 * @method \int[] getGlobalContentVersionList()
	 * @method \int[] fillGlobalContentVersion()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \Bitrix\Main\EO_File[] getFileContentList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getFileContentCollection()
	 * @method \Bitrix\Main\EO_File_Collection fillFileContent()
	 * @method \int[] getSizeList()
	 * @method \int[] fillSize()
	 * @method \string[] getExternalHashList()
	 * @method \string[] fillExternalHash()
	 * @method \string[] getEtagList()
	 * @method \string[] fillEtag()
	 * @method \string[] getDeletedTypeList()
	 * @method \string[] fillDeletedType()
	 * @method \string[] getTypeFileList()
	 * @method \string[] fillTypeFile()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathParentList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getPathParentCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathChildList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getPathChildCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathChild()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed[] getRecentlyUsedList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getRecentlyUsedCollection()
	 * @method \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection fillRecentlyUsed()
	 * @method \int[] getPreviewIdList()
	 * @method \int[] fillPreviewId()
	 * @method \int[] getViewIdList()
	 * @method \int[] fillViewId()
	 * @method \string[] getSearchIndexList()
	 * @method \string[] fillSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex[] getHeadIndexList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getHeadIndexCollection()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection fillHeadIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex[] getExtendedIndexList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getExtendedIndexCollection()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection fillExtendedIndex()
	 * @method \boolean[] getHasSearchIndexList()
	 * @method \boolean[] fillHasSearchIndex()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject[] getTrackedObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection getTrackedObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_TrackedObject_Collection fillTrackedObject()
	 * @method \boolean[] getHasSubfoldersList()
	 * @method \boolean[] fillHasSubfolders()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_Folder $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_Folder $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Folder getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Folder[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_Folder $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_Folder_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_Folder current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Folder_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\FolderTable */
		static public $dataClass = '\Bitrix\Disk\Internals\FolderTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Folder_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_Folder fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Folder_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Folder fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection fetchCollection()
	 */
	class EO_Folder_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Folder createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_Folder wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_Folder_Collection wakeUpCollection($rows)
	 */
	class EO_Folder_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\Rights\Table\RightSetupSessionTable:disk/lib/internals/rights/table/rightsetupsession.php */
namespace Bitrix\Disk\Internals\Rights\Table {
	/**
	 * EO_RightSetupSession
	 * @see \Bitrix\Disk\Internals\Rights\Table\RightSetupSessionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getParentId()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession resetParentId()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession unsetParentId()
	 * @method \int fillParentId()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession getParent()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession remindActualParent()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession requireParent()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession setParent(\Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession $object)
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession resetParent()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession unsetParent()
	 * @method bool hasParent()
	 * @method bool isParentFilled()
	 * @method bool isParentChanged()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession fillParent()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession resetObjectId()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object getObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireObject()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession setObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession resetObject()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillObject()
	 * @method \int getStatus()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession resetStatus()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession unsetStatus()
	 * @method \int fillStatus()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession resetCreateTime()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \boolean getIsExpired()
	 * @method \boolean remindActualIsExpired()
	 * @method \boolean requireIsExpired()
	 * @method bool hasIsExpired()
	 * @method bool isIsExpiredFilled()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession unsetIsExpired()
	 * @method \boolean fillIsExpired()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession unsetCreatedBy()
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
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession reset($fieldName)
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession wakeUp($data)
	 */
	class EO_RightSetupSession {
		/* @var \Bitrix\Disk\Internals\Rights\Table\RightSetupSessionTable */
		static public $dataClass = '\Bitrix\Disk\Internals\Rights\Table\RightSetupSessionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals\Rights\Table {
	/**
	 * EO_RightSetupSession_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession[] getParentList()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection getParentCollection()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection fillParent()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getObjectList()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillObject()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \boolean[] getIsExpiredList()
	 * @method \boolean[] fillIsExpired()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession $object)
	 * @method bool has(\Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RightSetupSession_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\Rights\Table\RightSetupSessionTable */
		static public $dataClass = '\Bitrix\Disk\Internals\Rights\Table\RightSetupSessionTable';
	}
}
namespace Bitrix\Disk\Internals\Rights\Table {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RightSetupSession_Result exec()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession fetchObject()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RightSetupSession_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession fetchObject()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection fetchCollection()
	 */
	class EO_RightSetupSession_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection wakeUpCollection($rows)
	 */
	class EO_RightSetupSession_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\VersionTable:disk/lib/internals/version.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Version
	 * @see \Bitrix\Disk\Internals\VersionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_Version setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Version setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Version resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File getObject()
	 * @method \Bitrix\Disk\Internals\EO_File remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_File requireObject()
	 * @method \Bitrix\Disk\Internals\EO_Version setObject(\Bitrix\Disk\Internals\EO_File $object)
	 * @method \Bitrix\Disk\Internals\EO_Version resetObject()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_File fillObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject getAttachedObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject remindActualAttachedObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject requireAttachedObject()
	 * @method \Bitrix\Disk\Internals\EO_Version setAttachedObject(\Bitrix\Disk\Internals\EO_AttachedObject $object)
	 * @method \Bitrix\Disk\Internals\EO_Version resetAttachedObject()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetAttachedObject()
	 * @method bool hasAttachedObject()
	 * @method bool isAttachedObjectFilled()
	 * @method bool isAttachedObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject fillAttachedObject()
	 * @method \int getSize()
	 * @method \Bitrix\Disk\Internals\EO_Version setSize(\int|\Bitrix\Main\DB\SqlExpression $size)
	 * @method bool hasSize()
	 * @method bool isSizeFilled()
	 * @method bool isSizeChanged()
	 * @method \int remindActualSize()
	 * @method \int requireSize()
	 * @method \Bitrix\Disk\Internals\EO_Version resetSize()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetSize()
	 * @method \int fillSize()
	 * @method \int getFileId()
	 * @method \Bitrix\Disk\Internals\EO_Version setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Disk\Internals\EO_Version resetFileId()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetFileId()
	 * @method \int fillFileId()
	 * @method \string getName()
	 * @method \Bitrix\Disk\Internals\EO_Version setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Disk\Internals\EO_Version resetName()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetName()
	 * @method \string fillName()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Version setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Version resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Version setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Version resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\EO_User getCreateUser()
	 * @method \Bitrix\Main\EO_User remindActualCreateUser()
	 * @method \Bitrix\Main\EO_User requireCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_Version setCreateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_Version resetCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetCreateUser()
	 * @method bool hasCreateUser()
	 * @method bool isCreateUserFilled()
	 * @method bool isCreateUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathParent()
	 * @method \Bitrix\Disk\Internals\EO_Version setPathParent(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Version resetPathParent()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetPathParent()
	 * @method bool hasPathParent()
	 * @method bool isPathParentFilled()
	 * @method bool isPathParentChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathChild()
	 * @method \Bitrix\Disk\Internals\EO_Version setPathChild(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Version resetPathChild()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetPathChild()
	 * @method bool hasPathChild()
	 * @method bool isPathChildFilled()
	 * @method bool isPathChildChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathChild()
	 * @method \Bitrix\Main\Type\DateTime getObjectCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Version setObjectCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $objectCreateTime)
	 * @method bool hasObjectCreateTime()
	 * @method bool isObjectCreateTimeFilled()
	 * @method bool isObjectCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualObjectCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireObjectCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Version resetObjectCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetObjectCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillObjectCreateTime()
	 * @method \int getObjectCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Version setObjectCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $objectCreatedBy)
	 * @method bool hasObjectCreatedBy()
	 * @method bool isObjectCreatedByFilled()
	 * @method bool isObjectCreatedByChanged()
	 * @method \int remindActualObjectCreatedBy()
	 * @method \int requireObjectCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Version resetObjectCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetObjectCreatedBy()
	 * @method \int fillObjectCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getObjectUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Version setObjectUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $objectUpdateTime)
	 * @method bool hasObjectUpdateTime()
	 * @method bool isObjectUpdateTimeFilled()
	 * @method bool isObjectUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualObjectUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireObjectUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Version resetObjectUpdateTime()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetObjectUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillObjectUpdateTime()
	 * @method \int getObjectUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Version setObjectUpdatedBy(\int|\Bitrix\Main\DB\SqlExpression $objectUpdatedBy)
	 * @method bool hasObjectUpdatedBy()
	 * @method bool isObjectUpdatedByFilled()
	 * @method bool isObjectUpdatedByChanged()
	 * @method \int remindActualObjectUpdatedBy()
	 * @method \int requireObjectUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Version resetObjectUpdatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetObjectUpdatedBy()
	 * @method \int fillObjectUpdatedBy()
	 * @method \int getGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version setGlobalContentVersion(\int|\Bitrix\Main\DB\SqlExpression $globalContentVersion)
	 * @method bool hasGlobalContentVersion()
	 * @method bool isGlobalContentVersionFilled()
	 * @method bool isGlobalContentVersionChanged()
	 * @method \int remindActualGlobalContentVersion()
	 * @method \int requireGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version resetGlobalContentVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetGlobalContentVersion()
	 * @method \int fillGlobalContentVersion()
	 * @method \string getMiscData()
	 * @method \Bitrix\Disk\Internals\EO_Version setMiscData(\string|\Bitrix\Main\DB\SqlExpression $miscData)
	 * @method bool hasMiscData()
	 * @method bool isMiscDataFilled()
	 * @method bool isMiscDataChanged()
	 * @method \string remindActualMiscData()
	 * @method \string requireMiscData()
	 * @method \Bitrix\Disk\Internals\EO_Version resetMiscData()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetMiscData()
	 * @method \string fillMiscData()
	 * @method \int getViewId()
	 * @method \Bitrix\Disk\Internals\EO_Version setViewId(\int|\Bitrix\Main\DB\SqlExpression $viewId)
	 * @method bool hasViewId()
	 * @method bool isViewIdFilled()
	 * @method bool isViewIdChanged()
	 * @method \int remindActualViewId()
	 * @method \int requireViewId()
	 * @method \Bitrix\Disk\Internals\EO_Version resetViewId()
	 * @method \Bitrix\Disk\Internals\EO_Version unsetViewId()
	 * @method \int fillViewId()
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
	 * @method \Bitrix\Disk\Internals\EO_Version set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_Version reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_Version unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_Version wakeUp($data)
	 */
	class EO_Version {
		/* @var \Bitrix\Disk\Internals\VersionTable */
		static public $dataClass = '\Bitrix\Disk\Internals\VersionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Version_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File[] getObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection fillObject()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject[] getAttachedObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection getAttachedObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_AttachedObject_Collection fillAttachedObject()
	 * @method \int[] getSizeList()
	 * @method \int[] fillSize()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\EO_User[] getCreateUserList()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection getCreateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathParentList()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection getPathParentCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathChildList()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection getPathChildCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathChild()
	 * @method \Bitrix\Main\Type\DateTime[] getObjectCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillObjectCreateTime()
	 * @method \int[] getObjectCreatedByList()
	 * @method \int[] fillObjectCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getObjectUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillObjectUpdateTime()
	 * @method \int[] getObjectUpdatedByList()
	 * @method \int[] fillObjectUpdatedBy()
	 * @method \int[] getGlobalContentVersionList()
	 * @method \int[] fillGlobalContentVersion()
	 * @method \string[] getMiscDataList()
	 * @method \string[] fillMiscData()
	 * @method \int[] getViewIdList()
	 * @method \int[] fillViewId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_Version $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_Version $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Version getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Version[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_Version $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_Version_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_Version current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Version_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\VersionTable */
		static public $dataClass = '\Bitrix\Disk\Internals\VersionTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Version_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_Version fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Version_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Version fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection fetchCollection()
	 */
	class EO_Version_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Version createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_Version wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection wakeUpCollection($rows)
	 */
	class EO_Version_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\ShowSessionTable:disk/lib/internals/showsession.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ShowSession
	 * @see \Bitrix\Disk\Internals\ShowSessionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File getObject()
	 * @method \Bitrix\Disk\Internals\EO_File remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_File requireObject()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setObject(\Bitrix\Disk\Internals\EO_File $object)
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetObject()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_File fillObject()
	 * @method \int getVersionId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setVersionId(\int|\Bitrix\Main\DB\SqlExpression $versionId)
	 * @method bool hasVersionId()
	 * @method bool isVersionIdFilled()
	 * @method bool isVersionIdChanged()
	 * @method \int remindActualVersionId()
	 * @method \int requireVersionId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetVersionId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetVersionId()
	 * @method \int fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version getVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version remindActualVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version requireVersion()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setVersion(\Bitrix\Disk\Internals\EO_Version $object)
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetVersion()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetVersion()
	 * @method bool hasVersion()
	 * @method bool isVersionFilled()
	 * @method bool isVersionChanged()
	 * @method \Bitrix\Disk\Internals\EO_Version fillVersion()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetUserId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getOwnerId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setOwnerId(\int|\Bitrix\Main\DB\SqlExpression $ownerId)
	 * @method bool hasOwnerId()
	 * @method bool isOwnerIdFilled()
	 * @method bool isOwnerIdChanged()
	 * @method \int remindActualOwnerId()
	 * @method \int requireOwnerId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetOwnerId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetOwnerId()
	 * @method \int fillOwnerId()
	 * @method \string getService()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setService(\string|\Bitrix\Main\DB\SqlExpression $service)
	 * @method bool hasService()
	 * @method bool isServiceFilled()
	 * @method bool isServiceChanged()
	 * @method \string remindActualService()
	 * @method \string requireService()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetService()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetService()
	 * @method \string fillService()
	 * @method \string getServiceFileId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setServiceFileId(\string|\Bitrix\Main\DB\SqlExpression $serviceFileId)
	 * @method bool hasServiceFileId()
	 * @method bool isServiceFileIdFilled()
	 * @method bool isServiceFileIdChanged()
	 * @method \string remindActualServiceFileId()
	 * @method \string requireServiceFileId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetServiceFileId()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetServiceFileId()
	 * @method \string fillServiceFileId()
	 * @method \string getServiceFileLink()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setServiceFileLink(\string|\Bitrix\Main\DB\SqlExpression $serviceFileLink)
	 * @method bool hasServiceFileLink()
	 * @method bool isServiceFileLinkFilled()
	 * @method bool isServiceFileLinkChanged()
	 * @method \string remindActualServiceFileLink()
	 * @method \string requireServiceFileLink()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetServiceFileLink()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetServiceFileLink()
	 * @method \string fillServiceFileLink()
	 * @method \string getEtag()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setEtag(\string|\Bitrix\Main\DB\SqlExpression $etag)
	 * @method bool hasEtag()
	 * @method bool isEtagFilled()
	 * @method bool isEtagChanged()
	 * @method \string remindActualEtag()
	 * @method \string requireEtag()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetEtag()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetEtag()
	 * @method \string fillEtag()
	 * @method \boolean getIsExpired()
	 * @method \boolean remindActualIsExpired()
	 * @method \boolean requireIsExpired()
	 * @method bool hasIsExpired()
	 * @method bool isIsExpiredFilled()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetIsExpired()
	 * @method \boolean fillIsExpired()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
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
	 * @method \Bitrix\Disk\Internals\EO_ShowSession set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_ShowSession reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_ShowSession unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_ShowSession wakeUp($data)
	 */
	class EO_ShowSession {
		/* @var \Bitrix\Disk\Internals\ShowSessionTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ShowSessionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ShowSession_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File[] getObjectList()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection fillObject()
	 * @method \int[] getVersionIdList()
	 * @method \int[] fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version[] getVersionList()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession_Collection getVersionCollection()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection fillVersion()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getOwnerIdList()
	 * @method \int[] fillOwnerId()
	 * @method \string[] getServiceList()
	 * @method \string[] fillService()
	 * @method \string[] getServiceFileIdList()
	 * @method \string[] fillServiceFileId()
	 * @method \string[] getServiceFileLinkList()
	 * @method \string[] fillServiceFileLink()
	 * @method \string[] getEtagList()
	 * @method \string[] fillEtag()
	 * @method \boolean[] getIsExpiredList()
	 * @method \boolean[] fillIsExpired()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_ShowSession $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_ShowSession $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ShowSession getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ShowSession[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_ShowSession $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_ShowSession_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_ShowSession current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ShowSession_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\ShowSessionTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ShowSessionTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ShowSession_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ShowSession_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ShowSession fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession_Collection fetchCollection()
	 */
	class EO_ShowSession_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ShowSession createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_ShowSession_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_ShowSession wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_ShowSession_Collection wakeUpCollection($rows)
	 */
	class EO_ShowSession_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\StorageTable:disk/lib/internals/storage.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Storage
	 * @see \Bitrix\Disk\Internals\StorageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_Storage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Disk\Internals\EO_Storage setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetName()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetName()
	 * @method \string fillName()
	 * @method \string getCode()
	 * @method \Bitrix\Disk\Internals\EO_Storage setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetCode()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetCode()
	 * @method \string fillCode()
	 * @method \string getXmlId()
	 * @method \Bitrix\Disk\Internals\EO_Storage setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetXmlId()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \string getModuleId()
	 * @method \Bitrix\Disk\Internals\EO_Storage setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetModuleId()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetModuleId()
	 * @method \string fillModuleId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Disk\Internals\EO_Storage setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetEntityType()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \string getEntityId()
	 * @method \Bitrix\Disk\Internals\EO_Storage setEntityId(\string|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \string remindActualEntityId()
	 * @method \string requireEntityId()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetEntityId()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetEntityId()
	 * @method \string fillEntityId()
	 * @method \string getEntityMiscData()
	 * @method \Bitrix\Disk\Internals\EO_Storage setEntityMiscData(\string|\Bitrix\Main\DB\SqlExpression $entityMiscData)
	 * @method bool hasEntityMiscData()
	 * @method bool isEntityMiscDataFilled()
	 * @method bool isEntityMiscDataChanged()
	 * @method \string remindActualEntityMiscData()
	 * @method \string requireEntityMiscData()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetEntityMiscData()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetEntityMiscData()
	 * @method \string fillEntityMiscData()
	 * @method \int getRootObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Storage setRootObjectId(\int|\Bitrix\Main\DB\SqlExpression $rootObjectId)
	 * @method bool hasRootObjectId()
	 * @method bool isRootObjectIdFilled()
	 * @method bool isRootObjectIdChanged()
	 * @method \int remindActualRootObjectId()
	 * @method \int requireRootObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetRootObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetRootObjectId()
	 * @method \int fillRootObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object getRootObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualRootObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireRootObject()
	 * @method \Bitrix\Disk\Internals\EO_Storage setRootObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_Storage resetRootObject()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetRootObject()
	 * @method bool hasRootObject()
	 * @method bool isRootObjectFilled()
	 * @method bool isRootObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillRootObject()
	 * @method \boolean getUseInternalRights()
	 * @method \Bitrix\Disk\Internals\EO_Storage setUseInternalRights(\boolean|\Bitrix\Main\DB\SqlExpression $useInternalRights)
	 * @method bool hasUseInternalRights()
	 * @method bool isUseInternalRightsFilled()
	 * @method bool isUseInternalRightsChanged()
	 * @method \boolean remindActualUseInternalRights()
	 * @method \boolean requireUseInternalRights()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetUseInternalRights()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetUseInternalRights()
	 * @method \boolean fillUseInternalRights()
	 * @method \string getSiteId()
	 * @method \Bitrix\Disk\Internals\EO_Storage setSiteId(\string|\Bitrix\Main\DB\SqlExpression $siteId)
	 * @method bool hasSiteId()
	 * @method bool isSiteIdFilled()
	 * @method bool isSiteIdChanged()
	 * @method \string remindActualSiteId()
	 * @method \string requireSiteId()
	 * @method \Bitrix\Disk\Internals\EO_Storage resetSiteId()
	 * @method \Bitrix\Disk\Internals\EO_Storage unsetSiteId()
	 * @method \string fillSiteId()
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
	 * @method \Bitrix\Disk\Internals\EO_Storage set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_Storage reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_Storage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_Storage wakeUp($data)
	 */
	class EO_Storage {
		/* @var \Bitrix\Disk\Internals\StorageTable */
		static public $dataClass = '\Bitrix\Disk\Internals\StorageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Storage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \string[] getEntityIdList()
	 * @method \string[] fillEntityId()
	 * @method \string[] getEntityMiscDataList()
	 * @method \string[] fillEntityMiscData()
	 * @method \int[] getRootObjectIdList()
	 * @method \int[] fillRootObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getRootObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection getRootObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillRootObject()
	 * @method \boolean[] getUseInternalRightsList()
	 * @method \boolean[] fillUseInternalRights()
	 * @method \string[] getSiteIdList()
	 * @method \string[] fillSiteId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Storage getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Storage[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_Storage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_Storage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Storage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\StorageTable */
		static public $dataClass = '\Bitrix\Disk\Internals\StorageTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Storage_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_Storage fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Storage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Storage fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection fetchCollection()
	 */
	class EO_Storage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Storage createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_Storage wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection wakeUpCollection($rows)
	 */
	class EO_Storage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\CloudImportTable:disk/lib/internals/cloudimport.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_CloudImport
	 * @see \Bitrix\Disk\Internals\CloudImportTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File getObject()
	 * @method \Bitrix\Disk\Internals\EO_File remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_File requireObject()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setObject(\Bitrix\Disk\Internals\EO_File $object)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetObject()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_File fillObject()
	 * @method \int getVersionId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setVersionId(\int|\Bitrix\Main\DB\SqlExpression $versionId)
	 * @method bool hasVersionId()
	 * @method bool isVersionIdFilled()
	 * @method bool isVersionIdChanged()
	 * @method \int remindActualVersionId()
	 * @method \int requireVersionId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetVersionId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetVersionId()
	 * @method \int fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version getVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version remindActualVersion()
	 * @method \Bitrix\Disk\Internals\EO_Version requireVersion()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setVersion(\Bitrix\Disk\Internals\EO_Version $object)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetVersion()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetVersion()
	 * @method bool hasVersion()
	 * @method bool isVersionFilled()
	 * @method bool isVersionChanged()
	 * @method \Bitrix\Disk\Internals\EO_Version fillVersion()
	 * @method \int getTmpFileId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setTmpFileId(\int|\Bitrix\Main\DB\SqlExpression $tmpFileId)
	 * @method bool hasTmpFileId()
	 * @method bool isTmpFileIdFilled()
	 * @method bool isTmpFileIdChanged()
	 * @method \int remindActualTmpFileId()
	 * @method \int requireTmpFileId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetTmpFileId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetTmpFileId()
	 * @method \int fillTmpFileId()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile getTmpFile()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile remindActualTmpFile()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile requireTmpFile()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setTmpFile(\Bitrix\Disk\Internals\EO_TmpFile $object)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetTmpFile()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetTmpFile()
	 * @method bool hasTmpFile()
	 * @method bool isTmpFileFilled()
	 * @method bool isTmpFileChanged()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile fillTmpFile()
	 * @method \int getDownloadedContentSize()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setDownloadedContentSize(\int|\Bitrix\Main\DB\SqlExpression $downloadedContentSize)
	 * @method bool hasDownloadedContentSize()
	 * @method bool isDownloadedContentSizeFilled()
	 * @method bool isDownloadedContentSizeChanged()
	 * @method \int remindActualDownloadedContentSize()
	 * @method \int requireDownloadedContentSize()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetDownloadedContentSize()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetDownloadedContentSize()
	 * @method \int fillDownloadedContentSize()
	 * @method \int getContentSize()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setContentSize(\int|\Bitrix\Main\DB\SqlExpression $contentSize)
	 * @method bool hasContentSize()
	 * @method bool isContentSizeFilled()
	 * @method bool isContentSizeChanged()
	 * @method \int remindActualContentSize()
	 * @method \int requireContentSize()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetContentSize()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetContentSize()
	 * @method \int fillContentSize()
	 * @method \string getContentUrl()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setContentUrl(\string|\Bitrix\Main\DB\SqlExpression $contentUrl)
	 * @method bool hasContentUrl()
	 * @method bool isContentUrlFilled()
	 * @method bool isContentUrlChanged()
	 * @method \string remindActualContentUrl()
	 * @method \string requireContentUrl()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetContentUrl()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetContentUrl()
	 * @method \string fillContentUrl()
	 * @method \string getMimeType()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setMimeType(\string|\Bitrix\Main\DB\SqlExpression $mimeType)
	 * @method bool hasMimeType()
	 * @method bool isMimeTypeFilled()
	 * @method bool isMimeTypeChanged()
	 * @method \string remindActualMimeType()
	 * @method \string requireMimeType()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetMimeType()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetMimeType()
	 * @method \string fillMimeType()
	 * @method \int getUserId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetUserId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetUser()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \string getService()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setService(\string|\Bitrix\Main\DB\SqlExpression $service)
	 * @method bool hasService()
	 * @method bool isServiceFilled()
	 * @method bool isServiceChanged()
	 * @method \string remindActualService()
	 * @method \string requireService()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetService()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetService()
	 * @method \string fillService()
	 * @method \string getServiceObjectId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setServiceObjectId(\string|\Bitrix\Main\DB\SqlExpression $serviceObjectId)
	 * @method bool hasServiceObjectId()
	 * @method bool isServiceObjectIdFilled()
	 * @method bool isServiceObjectIdChanged()
	 * @method \string remindActualServiceObjectId()
	 * @method \string requireServiceObjectId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetServiceObjectId()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetServiceObjectId()
	 * @method \string fillServiceObjectId()
	 * @method \string getEtag()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setEtag(\string|\Bitrix\Main\DB\SqlExpression $etag)
	 * @method bool hasEtag()
	 * @method bool isEtagFilled()
	 * @method bool isEtagChanged()
	 * @method \string remindActualEtag()
	 * @method \string requireEtag()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetEtag()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetEtag()
	 * @method \string fillEtag()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
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
	 * @method \Bitrix\Disk\Internals\EO_CloudImport set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_CloudImport wakeUp($data)
	 */
	class EO_CloudImport {
		/* @var \Bitrix\Disk\Internals\CloudImportTable */
		static public $dataClass = '\Bitrix\Disk\Internals\CloudImportTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_CloudImport_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_File[] getObjectList()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_File_Collection fillObject()
	 * @method \int[] getVersionIdList()
	 * @method \int[] fillVersionId()
	 * @method \Bitrix\Disk\Internals\EO_Version[] getVersionList()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport_Collection getVersionCollection()
	 * @method \Bitrix\Disk\Internals\EO_Version_Collection fillVersion()
	 * @method \int[] getTmpFileIdList()
	 * @method \int[] fillTmpFileId()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile[] getTmpFileList()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport_Collection getTmpFileCollection()
	 * @method \Bitrix\Disk\Internals\EO_TmpFile_Collection fillTmpFile()
	 * @method \int[] getDownloadedContentSizeList()
	 * @method \int[] fillDownloadedContentSize()
	 * @method \int[] getContentSizeList()
	 * @method \int[] fillContentSize()
	 * @method \string[] getContentUrlList()
	 * @method \string[] fillContentUrl()
	 * @method \string[] getMimeTypeList()
	 * @method \string[] fillMimeType()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \string[] getServiceList()
	 * @method \string[] fillService()
	 * @method \string[] getServiceObjectIdList()
	 * @method \string[] fillServiceObjectId()
	 * @method \string[] getEtagList()
	 * @method \string[] fillEtag()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_CloudImport $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_CloudImport $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_CloudImport $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_CloudImport_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_CloudImport current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_CloudImport_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\CloudImportTable */
		static public $dataClass = '\Bitrix\Disk\Internals\CloudImportTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CloudImport_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CloudImport_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_CloudImport fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport_Collection fetchCollection()
	 */
	class EO_CloudImport_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_CloudImport createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_CloudImport wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_CloudImport_Collection wakeUpCollection($rows)
	 */
	class EO_CloudImport_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\Index\ObjectExtendedIndexTable:disk/lib/internals/index/objectextendedindextable.php */
namespace Bitrix\Disk\Internals\Index {
	/**
	 * EO_ObjectExtendedIndex
	 * @see \Bitrix\Disk\Internals\Index\ObjectExtendedIndexTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object getObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireObject()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex setObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex resetObject()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillObject()
	 * @method \string getSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex setSearchIndex(\string|\Bitrix\Main\DB\SqlExpression $searchIndex)
	 * @method bool hasSearchIndex()
	 * @method bool isSearchIndexFilled()
	 * @method bool isSearchIndexChanged()
	 * @method \string remindActualSearchIndex()
	 * @method \string requireSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex resetSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex unsetSearchIndex()
	 * @method \string fillSearchIndex()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex resetUpdateTime()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
	 * @method \string getStatus()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex resetStatus()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex unsetStatus()
	 * @method \string fillStatus()
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
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex reset($fieldName)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex wakeUp($data)
	 */
	class EO_ObjectExtendedIndex {
		/* @var \Bitrix\Disk\Internals\Index\ObjectExtendedIndexTable */
		static public $dataClass = '\Bitrix\Disk\Internals\Index\ObjectExtendedIndexTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals\Index {
	/**
	 * EO_ObjectExtendedIndex_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getObjectIdList()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getObjectList()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillObject()
	 * @method \string[] getSearchIndexList()
	 * @method \string[] fillSearchIndex()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex $object)
	 * @method bool has(\Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ObjectExtendedIndex_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\Index\ObjectExtendedIndexTable */
		static public $dataClass = '\Bitrix\Disk\Internals\Index\ObjectExtendedIndexTable';
	}
}
namespace Bitrix\Disk\Internals\Index {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ObjectExtendedIndex_Result exec()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex fetchObject()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ObjectExtendedIndex_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex fetchObject()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection fetchCollection()
	 */
	class EO_ObjectExtendedIndex_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection wakeUpCollection($rows)
	 */
	class EO_ObjectExtendedIndex_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\Index\ObjectHeadIndexTable:disk/lib/internals/index/objectheadindextable.php */
namespace Bitrix\Disk\Internals\Index {
	/**
	 * EO_ObjectHeadIndex
	 * @see \Bitrix\Disk\Internals\Index\ObjectHeadIndexTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object getObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireObject()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex setObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex resetObject()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillObject()
	 * @method \string getSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex setSearchIndex(\string|\Bitrix\Main\DB\SqlExpression $searchIndex)
	 * @method bool hasSearchIndex()
	 * @method bool isSearchIndexFilled()
	 * @method bool isSearchIndexChanged()
	 * @method \string remindActualSearchIndex()
	 * @method \string requireSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex resetSearchIndex()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex unsetSearchIndex()
	 * @method \string fillSearchIndex()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex resetUpdateTime()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
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
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex reset($fieldName)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex wakeUp($data)
	 */
	class EO_ObjectHeadIndex {
		/* @var \Bitrix\Disk\Internals\Index\ObjectHeadIndexTable */
		static public $dataClass = '\Bitrix\Disk\Internals\Index\ObjectHeadIndexTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals\Index {
	/**
	 * EO_ObjectHeadIndex_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getObjectIdList()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getObjectList()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillObject()
	 * @method \string[] getSearchIndexList()
	 * @method \string[] fillSearchIndex()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex $object)
	 * @method bool has(\Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ObjectHeadIndex_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\Index\ObjectHeadIndexTable */
		static public $dataClass = '\Bitrix\Disk\Internals\Index\ObjectHeadIndexTable';
	}
}
namespace Bitrix\Disk\Internals\Index {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ObjectHeadIndex_Result exec()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex fetchObject()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ObjectHeadIndex_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex fetchObject()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection fetchCollection()
	 */
	class EO_ObjectHeadIndex_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection wakeUpCollection($rows)
	 */
	class EO_ObjectHeadIndex_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\SharingTable:disk/lib/internals/sharing.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Sharing
	 * @see \Bitrix\Disk\Internals\SharingTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetCreatedBy()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\EO_User getCreateUser()
	 * @method \Bitrix\Main\EO_User remindActualCreateUser()
	 * @method \Bitrix\Main\EO_User requireCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setCreateUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetCreateUser()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetCreateUser()
	 * @method bool hasCreateUser()
	 * @method bool isCreateUserFilled()
	 * @method bool isCreateUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreateUser()
	 * @method \string getToEntity()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setToEntity(\string|\Bitrix\Main\DB\SqlExpression $toEntity)
	 * @method bool hasToEntity()
	 * @method bool isToEntityFilled()
	 * @method bool isToEntityChanged()
	 * @method \string remindActualToEntity()
	 * @method \string requireToEntity()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetToEntity()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetToEntity()
	 * @method \string fillToEntity()
	 * @method \string getFromEntity()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setFromEntity(\string|\Bitrix\Main\DB\SqlExpression $fromEntity)
	 * @method bool hasFromEntity()
	 * @method bool isFromEntityFilled()
	 * @method bool isFromEntityChanged()
	 * @method \string remindActualFromEntity()
	 * @method \string requireFromEntity()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetFromEntity()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetFromEntity()
	 * @method \string fillFromEntity()
	 * @method \int getParentId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetParentId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetParentId()
	 * @method \int fillParentId()
	 * @method \int getLinkObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setLinkObjectId(\int|\Bitrix\Main\DB\SqlExpression $linkObjectId)
	 * @method bool hasLinkObjectId()
	 * @method bool isLinkObjectIdFilled()
	 * @method bool isLinkObjectIdChanged()
	 * @method \int remindActualLinkObjectId()
	 * @method \int requireLinkObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetLinkObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetLinkObjectId()
	 * @method \int fillLinkObjectId()
	 * @method \int getLinkStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setLinkStorageId(\int|\Bitrix\Main\DB\SqlExpression $linkStorageId)
	 * @method bool hasLinkStorageId()
	 * @method bool isLinkStorageIdFilled()
	 * @method bool isLinkStorageIdChanged()
	 * @method \int remindActualLinkStorageId()
	 * @method \int requireLinkStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetLinkStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetLinkStorageId()
	 * @method \int fillLinkStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage getLinkStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage remindActualLinkStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage requireLinkStorage()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setLinkStorage(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetLinkStorage()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetLinkStorage()
	 * @method bool hasLinkStorage()
	 * @method bool isLinkStorageFilled()
	 * @method bool isLinkStorageChanged()
	 * @method \Bitrix\Disk\Internals\EO_Storage fillLinkStorage()
	 * @method \Bitrix\Disk\Internals\EO_Object getLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setLinkObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetLinkObject()
	 * @method bool hasLinkObject()
	 * @method bool isLinkObjectFilled()
	 * @method bool isLinkObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillLinkObject()
	 * @method \int getRealStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setRealStorageId(\int|\Bitrix\Main\DB\SqlExpression $realStorageId)
	 * @method bool hasRealStorageId()
	 * @method bool isRealStorageIdFilled()
	 * @method bool isRealStorageIdChanged()
	 * @method \int remindActualRealStorageId()
	 * @method \int requireRealStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetRealStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetRealStorageId()
	 * @method \int fillRealStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage getRealStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage remindActualRealStorage()
	 * @method \Bitrix\Disk\Internals\EO_Storage requireRealStorage()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setRealStorage(\Bitrix\Disk\Internals\EO_Storage $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetRealStorage()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetRealStorage()
	 * @method bool hasRealStorage()
	 * @method bool isRealStorageFilled()
	 * @method bool isRealStorageChanged()
	 * @method \Bitrix\Disk\Internals\EO_Storage fillRealStorage()
	 * @method \int getRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setRealObjectId(\int|\Bitrix\Main\DB\SqlExpression $realObjectId)
	 * @method bool hasRealObjectId()
	 * @method bool isRealObjectIdFilled()
	 * @method bool isRealObjectIdChanged()
	 * @method \int remindActualRealObjectId()
	 * @method \int requireRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetRealObjectId()
	 * @method \int fillRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object getRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setRealObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetRealObject()
	 * @method bool hasRealObject()
	 * @method bool isRealObjectFilled()
	 * @method bool isRealObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillRealObject()
	 * @method \string getDescription()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetDescription()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetDescription()
	 * @method \string fillDescription()
	 * @method \boolean getCanForward()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setCanForward(\boolean|\Bitrix\Main\DB\SqlExpression $canForward)
	 * @method bool hasCanForward()
	 * @method bool isCanForwardFilled()
	 * @method bool isCanForwardChanged()
	 * @method \boolean remindActualCanForward()
	 * @method \boolean requireCanForward()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetCanForward()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetCanForward()
	 * @method \boolean fillCanForward()
	 * @method \string getType()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetType()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetType()
	 * @method \string fillType()
	 * @method \string getStatus()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetStatus()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetStatus()
	 * @method \string fillStatus()
	 * @method \string getTaskName()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setTaskName(\string|\Bitrix\Main\DB\SqlExpression $taskName)
	 * @method bool hasTaskName()
	 * @method bool isTaskNameFilled()
	 * @method bool isTaskNameChanged()
	 * @method \string remindActualTaskName()
	 * @method \string requireTaskName()
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetTaskName()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetTaskName()
	 * @method \string fillTaskName()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathParentRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathParentRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathParentRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setPathParentRealObject(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetPathParentRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetPathParentRealObject()
	 * @method bool hasPathParentRealObject()
	 * @method bool isPathParentRealObjectFilled()
	 * @method bool isPathParentRealObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathParentRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathChildRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathChildRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathChildRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setPathChildRealObject(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetPathChildRealObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetPathChildRealObject()
	 * @method bool hasPathChildRealObject()
	 * @method bool isPathChildRealObjectFilled()
	 * @method bool isPathChildRealObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathChildRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathChildRealObjectSoft()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathChildRealObjectSoft()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathChildRealObjectSoft()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setPathChildRealObjectSoft(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetPathChildRealObjectSoft()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetPathChildRealObjectSoft()
	 * @method bool hasPathChildRealObjectSoft()
	 * @method bool isPathChildRealObjectSoftFilled()
	 * @method bool isPathChildRealObjectSoftChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathChildRealObjectSoft()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathParentLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathParentLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathParentLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setPathParentLinkObject(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetPathParentLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetPathParentLinkObject()
	 * @method bool hasPathParentLinkObject()
	 * @method bool isPathParentLinkObjectFilled()
	 * @method bool isPathParentLinkObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathParentLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathChildLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathChildLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathChildLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing setPathChildLinkObject(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Sharing resetPathChildLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing unsetPathChildLinkObject()
	 * @method bool hasPathChildLinkObject()
	 * @method bool isPathChildLinkObjectFilled()
	 * @method bool isPathChildLinkObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathChildLinkObject()
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
	 * @method \Bitrix\Disk\Internals\EO_Sharing set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_Sharing reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_Sharing unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_Sharing wakeUp($data)
	 */
	class EO_Sharing {
		/* @var \Bitrix\Disk\Internals\SharingTable */
		static public $dataClass = '\Bitrix\Disk\Internals\SharingTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Sharing_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\EO_User[] getCreateUserList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getCreateUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreateUser()
	 * @method \string[] getToEntityList()
	 * @method \string[] fillToEntity()
	 * @method \string[] getFromEntityList()
	 * @method \string[] fillFromEntity()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \int[] getLinkObjectIdList()
	 * @method \int[] fillLinkObjectId()
	 * @method \int[] getLinkStorageIdList()
	 * @method \int[] fillLinkStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage[] getLinkStorageList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getLinkStorageCollection()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection fillLinkStorage()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getLinkObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getLinkObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillLinkObject()
	 * @method \int[] getRealStorageIdList()
	 * @method \int[] fillRealStorageId()
	 * @method \Bitrix\Disk\Internals\EO_Storage[] getRealStorageList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getRealStorageCollection()
	 * @method \Bitrix\Disk\Internals\EO_Storage_Collection fillRealStorage()
	 * @method \int[] getRealObjectIdList()
	 * @method \int[] fillRealObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getRealObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getRealObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillRealObject()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \boolean[] getCanForwardList()
	 * @method \boolean[] fillCanForward()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \string[] getTaskNameList()
	 * @method \string[] fillTaskName()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathParentRealObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getPathParentRealObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathParentRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathChildRealObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getPathChildRealObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathChildRealObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathChildRealObjectSoftList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getPathChildRealObjectSoftCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathChildRealObjectSoft()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathParentLinkObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getPathParentLinkObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathParentLinkObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathChildLinkObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection getPathChildLinkObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathChildLinkObject()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_Sharing $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_Sharing $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Sharing getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Sharing[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_Sharing $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_Sharing_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_Sharing current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Sharing_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\SharingTable */
		static public $dataClass = '\Bitrix\Disk\Internals\SharingTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Sharing_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_Sharing fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Sharing_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Sharing fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection fetchCollection()
	 */
	class EO_Sharing_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Sharing createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_Sharing wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_Sharing_Collection wakeUpCollection($rows)
	 */
	class EO_Sharing_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\ObjectTtlTable:disk/lib/internals/objectttl.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ObjectTtl
	 * @see \Bitrix\Disk\Internals\ObjectTtlTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object getObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl setObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl resetObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillObject()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl resetCreateTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getDeathTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl setDeathTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $deathTime)
	 * @method bool hasDeathTime()
	 * @method bool isDeathTimeFilled()
	 * @method bool isDeathTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDeathTime()
	 * @method \Bitrix\Main\Type\DateTime requireDeathTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl resetDeathTime()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl unsetDeathTime()
	 * @method \Bitrix\Main\Type\DateTime fillDeathTime()
	 * @method \boolean getIsExpired()
	 * @method \boolean remindActualIsExpired()
	 * @method \boolean requireIsExpired()
	 * @method bool hasIsExpired()
	 * @method bool isIsExpiredFilled()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl unsetIsExpired()
	 * @method \boolean fillIsExpired()
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
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_ObjectTtl wakeUp($data)
	 */
	class EO_ObjectTtl {
		/* @var \Bitrix\Disk\Internals\ObjectTtlTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectTtlTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_ObjectTtl_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getObjectList()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillObject()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getDeathTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDeathTime()
	 * @method \boolean[] getIsExpiredList()
	 * @method \boolean[] fillIsExpired()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_ObjectTtl $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_ObjectTtl $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_ObjectTtl $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_ObjectTtl_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ObjectTtl_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\ObjectTtlTable */
		static public $dataClass = '\Bitrix\Disk\Internals\ObjectTtlTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ObjectTtl_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ObjectTtl_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl_Collection fetchCollection()
	 */
	class EO_ObjectTtl_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_ObjectTtl_Collection wakeUpCollection($rows)
	 */
	class EO_ObjectTtl_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Disk\Internals\RightTable:disk/lib/internals/right.php */
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Right
	 * @see \Bitrix\Disk\Internals\RightTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Disk\Internals\EO_Right setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Right setObjectId(\int|\Bitrix\Main\DB\SqlExpression $objectId)
	 * @method bool hasObjectId()
	 * @method bool isObjectIdFilled()
	 * @method bool isObjectIdChanged()
	 * @method \int remindActualObjectId()
	 * @method \int requireObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Right resetObjectId()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetObjectId()
	 * @method \int fillObjectId()
	 * @method \int getTaskId()
	 * @method \Bitrix\Disk\Internals\EO_Right setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Disk\Internals\EO_Right resetTaskId()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\Disk\Internals\EO_Right setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\Disk\Internals\EO_Right resetAccessCode()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \string getDomain()
	 * @method \Bitrix\Disk\Internals\EO_Right setDomain(\string|\Bitrix\Main\DB\SqlExpression $domain)
	 * @method bool hasDomain()
	 * @method bool isDomainFilled()
	 * @method bool isDomainChanged()
	 * @method \string remindActualDomain()
	 * @method \string requireDomain()
	 * @method \Bitrix\Disk\Internals\EO_Right resetDomain()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetDomain()
	 * @method \string fillDomain()
	 * @method \boolean getNegative()
	 * @method \Bitrix\Disk\Internals\EO_Right setNegative(\boolean|\Bitrix\Main\DB\SqlExpression $negative)
	 * @method bool hasNegative()
	 * @method bool isNegativeFilled()
	 * @method bool isNegativeChanged()
	 * @method \boolean remindActualNegative()
	 * @method \boolean requireNegative()
	 * @method \Bitrix\Disk\Internals\EO_Right resetNegative()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetNegative()
	 * @method \boolean fillNegative()
	 * @method \Bitrix\Disk\Internals\EO_Object getObject()
	 * @method \Bitrix\Disk\Internals\EO_Object remindActualObject()
	 * @method \Bitrix\Disk\Internals\EO_Object requireObject()
	 * @method \Bitrix\Disk\Internals\EO_Right setObject(\Bitrix\Disk\Internals\EO_Object $object)
	 * @method \Bitrix\Disk\Internals\EO_Right resetObject()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetObject()
	 * @method bool hasObject()
	 * @method bool isObjectFilled()
	 * @method bool isObjectChanged()
	 * @method \Bitrix\Disk\Internals\EO_Object fillObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathParent()
	 * @method \Bitrix\Disk\Internals\EO_Right setPathParent(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Right resetPathParent()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetPathParent()
	 * @method bool hasPathParent()
	 * @method bool isPathParentFilled()
	 * @method bool isPathParentChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath getPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath remindActualPathChild()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath requirePathChild()
	 * @method \Bitrix\Disk\Internals\EO_Right setPathChild(\Bitrix\Disk\Internals\EO_ObjectPath $object)
	 * @method \Bitrix\Disk\Internals\EO_Right resetPathChild()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetPathChild()
	 * @method bool hasPathChild()
	 * @method bool isPathChildFilled()
	 * @method bool isPathChildChanged()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath fillPathChild()
	 * @method \Bitrix\Main\EO_TaskOperation getTaskOperation()
	 * @method \Bitrix\Main\EO_TaskOperation remindActualTaskOperation()
	 * @method \Bitrix\Main\EO_TaskOperation requireTaskOperation()
	 * @method \Bitrix\Disk\Internals\EO_Right setTaskOperation(\Bitrix\Main\EO_TaskOperation $object)
	 * @method \Bitrix\Disk\Internals\EO_Right resetTaskOperation()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetTaskOperation()
	 * @method bool hasTaskOperation()
	 * @method bool isTaskOperationFilled()
	 * @method bool isTaskOperationChanged()
	 * @method \Bitrix\Main\EO_TaskOperation fillTaskOperation()
	 * @method \Bitrix\Main\EO_UserAccess getUserAccess()
	 * @method \Bitrix\Main\EO_UserAccess remindActualUserAccess()
	 * @method \Bitrix\Main\EO_UserAccess requireUserAccess()
	 * @method \Bitrix\Disk\Internals\EO_Right setUserAccess(\Bitrix\Main\EO_UserAccess $object)
	 * @method \Bitrix\Disk\Internals\EO_Right resetUserAccess()
	 * @method \Bitrix\Disk\Internals\EO_Right unsetUserAccess()
	 * @method bool hasUserAccess()
	 * @method bool isUserAccessFilled()
	 * @method bool isUserAccessChanged()
	 * @method \Bitrix\Main\EO_UserAccess fillUserAccess()
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
	 * @method \Bitrix\Disk\Internals\EO_Right set($fieldName, $value)
	 * @method \Bitrix\Disk\Internals\EO_Right reset($fieldName)
	 * @method \Bitrix\Disk\Internals\EO_Right unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Disk\Internals\EO_Right wakeUp($data)
	 */
	class EO_Right {
		/* @var \Bitrix\Disk\Internals\RightTable */
		static public $dataClass = '\Bitrix\Disk\Internals\RightTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * EO_Right_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getObjectIdList()
	 * @method \int[] fillObjectId()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \string[] getDomainList()
	 * @method \string[] fillDomain()
	 * @method \boolean[] getNegativeList()
	 * @method \boolean[] fillNegative()
	 * @method \Bitrix\Disk\Internals\EO_Object[] getObjectList()
	 * @method \Bitrix\Disk\Internals\EO_Right_Collection getObjectCollection()
	 * @method \Bitrix\Disk\Internals\EO_Object_Collection fillObject()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathParentList()
	 * @method \Bitrix\Disk\Internals\EO_Right_Collection getPathParentCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathParent()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath[] getPathChildList()
	 * @method \Bitrix\Disk\Internals\EO_Right_Collection getPathChildCollection()
	 * @method \Bitrix\Disk\Internals\EO_ObjectPath_Collection fillPathChild()
	 * @method \Bitrix\Main\EO_TaskOperation[] getTaskOperationList()
	 * @method \Bitrix\Disk\Internals\EO_Right_Collection getTaskOperationCollection()
	 * @method \Bitrix\Main\EO_TaskOperation_Collection fillTaskOperation()
	 * @method \Bitrix\Main\EO_UserAccess[] getUserAccessList()
	 * @method \Bitrix\Disk\Internals\EO_Right_Collection getUserAccessCollection()
	 * @method \Bitrix\Main\EO_UserAccess_Collection fillUserAccess()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Disk\Internals\EO_Right $object)
	 * @method bool has(\Bitrix\Disk\Internals\EO_Right $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Right getByPrimary($primary)
	 * @method \Bitrix\Disk\Internals\EO_Right[] getAll()
	 * @method bool remove(\Bitrix\Disk\Internals\EO_Right $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Disk\Internals\EO_Right_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Disk\Internals\EO_Right current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Right_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Disk\Internals\RightTable */
		static public $dataClass = '\Bitrix\Disk\Internals\RightTable';
	}
}
namespace Bitrix\Disk\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Right_Result exec()
	 * @method \Bitrix\Disk\Internals\EO_Right fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Right_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Right_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Right fetchObject()
	 * @method \Bitrix\Disk\Internals\EO_Right_Collection fetchCollection()
	 */
	class EO_Right_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Disk\Internals\EO_Right createObject($setDefaultValues = true)
	 * @method \Bitrix\Disk\Internals\EO_Right_Collection createCollection()
	 * @method \Bitrix\Disk\Internals\EO_Right wakeUpObject($row)
	 * @method \Bitrix\Disk\Internals\EO_Right_Collection wakeUpCollection($rows)
	 */
	class EO_Right_Entity extends \Bitrix\Main\ORM\Entity {}
}