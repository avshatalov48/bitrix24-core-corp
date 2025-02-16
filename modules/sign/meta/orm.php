<?php

/* ORMENTITYANNOTATION:Bitrix\Sign\Access\Permission\PermissionTable:sign/lib/access/permission/permissiontable.php */
namespace Bitrix\Sign\Access\Permission {
	/**
	 * Permission
	 * @see \Bitrix\Sign\Access\Permission\PermissionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Access\Permission\Permission setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\Sign\Access\Permission\Permission setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\Sign\Access\Permission\Permission resetRoleId()
	 * @method \Bitrix\Sign\Access\Permission\Permission unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getPermissionId()
	 * @method \Bitrix\Sign\Access\Permission\Permission setPermissionId(\string|\Bitrix\Main\DB\SqlExpression $permissionId)
	 * @method bool hasPermissionId()
	 * @method bool isPermissionIdFilled()
	 * @method bool isPermissionIdChanged()
	 * @method \string remindActualPermissionId()
	 * @method \string requirePermissionId()
	 * @method \Bitrix\Sign\Access\Permission\Permission resetPermissionId()
	 * @method \Bitrix\Sign\Access\Permission\Permission unsetPermissionId()
	 * @method \string fillPermissionId()
	 * @method \string getValue()
	 * @method \Bitrix\Sign\Access\Permission\Permission setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\Sign\Access\Permission\Permission resetValue()
	 * @method \Bitrix\Sign\Access\Permission\Permission unsetValue()
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
	 * @method \Bitrix\Sign\Access\Permission\Permission set($fieldName, $value)
	 * @method \Bitrix\Sign\Access\Permission\Permission reset($fieldName)
	 * @method \Bitrix\Sign\Access\Permission\Permission unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Access\Permission\Permission wakeUp($data)
	 */
	class EO_Permission {
		/* @var \Bitrix\Sign\Access\Permission\PermissionTable */
		static public $dataClass = '\Bitrix\Sign\Access\Permission\PermissionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Access\Permission {
	/**
	 * EO_Permission_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getPermissionIdList()
	 * @method \string[] fillPermissionId()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Access\Permission\Permission $object)
	 * @method bool has(\Bitrix\Sign\Access\Permission\Permission $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Access\Permission\Permission getByPrimary($primary)
	 * @method \Bitrix\Sign\Access\Permission\Permission[] getAll()
	 * @method bool remove(\Bitrix\Sign\Access\Permission\Permission $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Access\Permission\EO_Permission_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Access\Permission\Permission current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Access\Permission\EO_Permission_Collection merge(?\Bitrix\Sign\Access\Permission\EO_Permission_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Permission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Access\Permission\PermissionTable */
		static public $dataClass = '\Bitrix\Sign\Access\Permission\PermissionTable';
	}
}
namespace Bitrix\Sign\Access\Permission {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Permission_Result exec()
	 * @method \Bitrix\Sign\Access\Permission\Permission fetchObject()
	 * @method \Bitrix\Sign\Access\Permission\EO_Permission_Collection fetchCollection()
	 */
	class EO_Permission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Access\Permission\Permission fetchObject()
	 * @method \Bitrix\Sign\Access\Permission\EO_Permission_Collection fetchCollection()
	 */
	class EO_Permission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Access\Permission\Permission createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Access\Permission\EO_Permission_Collection createCollection()
	 * @method \Bitrix\Sign\Access\Permission\Permission wakeUpObject($row)
	 * @method \Bitrix\Sign\Access\Permission\EO_Permission_Collection wakeUpCollection($rows)
	 */
	class EO_Permission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\DocumentTable:sign/lib/internal/documenttable.php */
namespace Bitrix\Sign\Internal {
	/**
	 * Document
	 * @see \Bitrix\Sign\Internal\DocumentTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\Document setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\Sign\Internal\Document setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Sign\Internal\Document resetTitle()
	 * @method \Bitrix\Sign\Internal\Document unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getHash()
	 * @method \Bitrix\Sign\Internal\Document setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\Sign\Internal\Document resetHash()
	 * @method \Bitrix\Sign\Internal\Document unsetHash()
	 * @method \string fillHash()
	 * @method \string getSecCode()
	 * @method \Bitrix\Sign\Internal\Document setSecCode(\string|\Bitrix\Main\DB\SqlExpression $secCode)
	 * @method bool hasSecCode()
	 * @method bool isSecCodeFilled()
	 * @method bool isSecCodeChanged()
	 * @method \string remindActualSecCode()
	 * @method \string requireSecCode()
	 * @method \Bitrix\Sign\Internal\Document resetSecCode()
	 * @method \Bitrix\Sign\Internal\Document unsetSecCode()
	 * @method \string fillSecCode()
	 * @method \string getHost()
	 * @method \Bitrix\Sign\Internal\Document setHost(\string|\Bitrix\Main\DB\SqlExpression $host)
	 * @method bool hasHost()
	 * @method bool isHostFilled()
	 * @method bool isHostChanged()
	 * @method \string remindActualHost()
	 * @method \string requireHost()
	 * @method \Bitrix\Sign\Internal\Document resetHost()
	 * @method \Bitrix\Sign\Internal\Document unsetHost()
	 * @method \string fillHost()
	 * @method \int getBlankId()
	 * @method \Bitrix\Sign\Internal\Document setBlankId(\int|\Bitrix\Main\DB\SqlExpression $blankId)
	 * @method bool hasBlankId()
	 * @method bool isBlankIdFilled()
	 * @method bool isBlankIdChanged()
	 * @method \int remindActualBlankId()
	 * @method \int requireBlankId()
	 * @method \Bitrix\Sign\Internal\Document resetBlankId()
	 * @method \Bitrix\Sign\Internal\Document unsetBlankId()
	 * @method \int fillBlankId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Sign\Internal\Document setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Sign\Internal\Document resetEntityType()
	 * @method \Bitrix\Sign\Internal\Document unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \int getEntityId()
	 * @method \Bitrix\Sign\Internal\Document setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Sign\Internal\Document resetEntityId()
	 * @method \Bitrix\Sign\Internal\Document unsetEntityId()
	 * @method \int fillEntityId()
	 * @method array getMeta()
	 * @method \Bitrix\Sign\Internal\Document setMeta(array|\Bitrix\Main\DB\SqlExpression $meta)
	 * @method bool hasMeta()
	 * @method bool isMetaFilled()
	 * @method bool isMetaChanged()
	 * @method array remindActualMeta()
	 * @method array requireMeta()
	 * @method \Bitrix\Sign\Internal\Document resetMeta()
	 * @method \Bitrix\Sign\Internal\Document unsetMeta()
	 * @method array fillMeta()
	 * @method \string getProcessingStatus()
	 * @method \Bitrix\Sign\Internal\Document setProcessingStatus(\string|\Bitrix\Main\DB\SqlExpression $processingStatus)
	 * @method bool hasProcessingStatus()
	 * @method bool isProcessingStatusFilled()
	 * @method bool isProcessingStatusChanged()
	 * @method \string remindActualProcessingStatus()
	 * @method \string requireProcessingStatus()
	 * @method \Bitrix\Sign\Internal\Document resetProcessingStatus()
	 * @method \Bitrix\Sign\Internal\Document unsetProcessingStatus()
	 * @method \string fillProcessingStatus()
	 * @method \string getProcessingError()
	 * @method \Bitrix\Sign\Internal\Document setProcessingError(\string|\Bitrix\Main\DB\SqlExpression $processingError)
	 * @method bool hasProcessingError()
	 * @method bool isProcessingErrorFilled()
	 * @method bool isProcessingErrorChanged()
	 * @method \string remindActualProcessingError()
	 * @method \string requireProcessingError()
	 * @method \Bitrix\Sign\Internal\Document resetProcessingError()
	 * @method \Bitrix\Sign\Internal\Document unsetProcessingError()
	 * @method \string fillProcessingError()
	 * @method \string getLangId()
	 * @method \Bitrix\Sign\Internal\Document setLangId(\string|\Bitrix\Main\DB\SqlExpression $langId)
	 * @method bool hasLangId()
	 * @method bool isLangIdFilled()
	 * @method bool isLangIdChanged()
	 * @method \string remindActualLangId()
	 * @method \string requireLangId()
	 * @method \Bitrix\Sign\Internal\Document resetLangId()
	 * @method \Bitrix\Sign\Internal\Document unsetLangId()
	 * @method \string fillLangId()
	 * @method \int getResultFileId()
	 * @method \Bitrix\Sign\Internal\Document setResultFileId(\int|\Bitrix\Main\DB\SqlExpression $resultFileId)
	 * @method bool hasResultFileId()
	 * @method bool isResultFileIdFilled()
	 * @method bool isResultFileIdChanged()
	 * @method \int remindActualResultFileId()
	 * @method \int requireResultFileId()
	 * @method \Bitrix\Sign\Internal\Document resetResultFileId()
	 * @method \Bitrix\Sign\Internal\Document unsetResultFileId()
	 * @method \int fillResultFileId()
	 * @method \int getCreatedById()
	 * @method \Bitrix\Sign\Internal\Document setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\Sign\Internal\Document resetCreatedById()
	 * @method \Bitrix\Sign\Internal\Document unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method \int getModifiedById()
	 * @method \Bitrix\Sign\Internal\Document setModifiedById(\int|\Bitrix\Main\DB\SqlExpression $modifiedById)
	 * @method bool hasModifiedById()
	 * @method bool isModifiedByIdFilled()
	 * @method bool isModifiedByIdChanged()
	 * @method \int remindActualModifiedById()
	 * @method \int requireModifiedById()
	 * @method \Bitrix\Sign\Internal\Document resetModifiedById()
	 * @method \Bitrix\Sign\Internal\Document unsetModifiedById()
	 * @method \int fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\Document setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\Document resetDateCreate()
	 * @method \Bitrix\Sign\Internal\Document unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\Sign\Internal\Document setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\Sign\Internal\Document resetDateModify()
	 * @method \Bitrix\Sign\Internal\Document unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
	 * @method \Bitrix\Main\Type\DateTime getDateSign()
	 * @method \Bitrix\Sign\Internal\Document setDateSign(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateSign)
	 * @method bool hasDateSign()
	 * @method bool isDateSignFilled()
	 * @method bool isDateSignChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateSign()
	 * @method \Bitrix\Main\Type\DateTime requireDateSign()
	 * @method \Bitrix\Sign\Internal\Document resetDateSign()
	 * @method \Bitrix\Sign\Internal\Document unsetDateSign()
	 * @method \Bitrix\Main\Type\DateTime fillDateSign()
	 * @method \string getStatus()
	 * @method \Bitrix\Sign\Internal\Document setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Sign\Internal\Document resetStatus()
	 * @method \Bitrix\Sign\Internal\Document unsetStatus()
	 * @method \string fillStatus()
	 * @method \string getUid()
	 * @method \Bitrix\Sign\Internal\Document setUid(\string|\Bitrix\Main\DB\SqlExpression $uid)
	 * @method bool hasUid()
	 * @method bool isUidFilled()
	 * @method bool isUidChanged()
	 * @method \string remindActualUid()
	 * @method \string requireUid()
	 * @method \Bitrix\Sign\Internal\Document resetUid()
	 * @method \Bitrix\Sign\Internal\Document unsetUid()
	 * @method \string fillUid()
	 * @method null|\int getScenario()
	 * @method \Bitrix\Sign\Internal\Document setScenario(null|\int|\Bitrix\Main\DB\SqlExpression $scenario)
	 * @method bool hasScenario()
	 * @method bool isScenarioFilled()
	 * @method bool isScenarioChanged()
	 * @method null|\int remindActualScenario()
	 * @method null|\int requireScenario()
	 * @method \Bitrix\Sign\Internal\Document resetScenario()
	 * @method \Bitrix\Sign\Internal\Document unsetScenario()
	 * @method null|\int fillScenario()
	 * @method \int getVersion()
	 * @method \Bitrix\Sign\Internal\Document setVersion(\int|\Bitrix\Main\DB\SqlExpression $version)
	 * @method bool hasVersion()
	 * @method bool isVersionFilled()
	 * @method bool isVersionChanged()
	 * @method \int remindActualVersion()
	 * @method \int requireVersion()
	 * @method \Bitrix\Sign\Internal\Document resetVersion()
	 * @method \Bitrix\Sign\Internal\Document unsetVersion()
	 * @method \int fillVersion()
	 * @method null|\string getCompanyUid()
	 * @method \Bitrix\Sign\Internal\Document setCompanyUid(null|\string|\Bitrix\Main\DB\SqlExpression $companyUid)
	 * @method bool hasCompanyUid()
	 * @method bool isCompanyUidFilled()
	 * @method bool isCompanyUidChanged()
	 * @method null|\string remindActualCompanyUid()
	 * @method null|\string requireCompanyUid()
	 * @method \Bitrix\Sign\Internal\Document resetCompanyUid()
	 * @method \Bitrix\Sign\Internal\Document unsetCompanyUid()
	 * @method null|\string fillCompanyUid()
	 * @method null|\int getRepresentativeId()
	 * @method \Bitrix\Sign\Internal\Document setRepresentativeId(null|\int|\Bitrix\Main\DB\SqlExpression $representativeId)
	 * @method bool hasRepresentativeId()
	 * @method bool isRepresentativeIdFilled()
	 * @method bool isRepresentativeIdChanged()
	 * @method null|\int remindActualRepresentativeId()
	 * @method null|\int requireRepresentativeId()
	 * @method \Bitrix\Sign\Internal\Document resetRepresentativeId()
	 * @method \Bitrix\Sign\Internal\Document unsetRepresentativeId()
	 * @method null|\int fillRepresentativeId()
	 * @method null|\int getParties()
	 * @method \Bitrix\Sign\Internal\Document setParties(null|\int|\Bitrix\Main\DB\SqlExpression $parties)
	 * @method bool hasParties()
	 * @method bool isPartiesFilled()
	 * @method bool isPartiesChanged()
	 * @method null|\int remindActualParties()
	 * @method null|\int requireParties()
	 * @method \Bitrix\Sign\Internal\Document resetParties()
	 * @method \Bitrix\Sign\Internal\Document unsetParties()
	 * @method null|\int fillParties()
	 * @method null|\string getExternalId()
	 * @method \Bitrix\Sign\Internal\Document setExternalId(null|\string|\Bitrix\Main\DB\SqlExpression $externalId)
	 * @method bool hasExternalId()
	 * @method bool isExternalIdFilled()
	 * @method bool isExternalIdChanged()
	 * @method null|\string remindActualExternalId()
	 * @method null|\string requireExternalId()
	 * @method \Bitrix\Sign\Internal\Document resetExternalId()
	 * @method \Bitrix\Sign\Internal\Document unsetExternalId()
	 * @method null|\string fillExternalId()
	 * @method null|\string getRegionDocumentType()
	 * @method \Bitrix\Sign\Internal\Document setRegionDocumentType(null|\string|\Bitrix\Main\DB\SqlExpression $regionDocumentType)
	 * @method bool hasRegionDocumentType()
	 * @method bool isRegionDocumentTypeFilled()
	 * @method bool isRegionDocumentTypeChanged()
	 * @method null|\string remindActualRegionDocumentType()
	 * @method null|\string requireRegionDocumentType()
	 * @method \Bitrix\Sign\Internal\Document resetRegionDocumentType()
	 * @method \Bitrix\Sign\Internal\Document unsetRegionDocumentType()
	 * @method null|\string fillRegionDocumentType()
	 * @method \int getScheme()
	 * @method \Bitrix\Sign\Internal\Document setScheme(\int|\Bitrix\Main\DB\SqlExpression $scheme)
	 * @method bool hasScheme()
	 * @method bool isSchemeFilled()
	 * @method bool isSchemeChanged()
	 * @method \int remindActualScheme()
	 * @method \int requireScheme()
	 * @method \Bitrix\Sign\Internal\Document resetScheme()
	 * @method \Bitrix\Sign\Internal\Document unsetScheme()
	 * @method \int fillScheme()
	 * @method null|\int getStoppedById()
	 * @method \Bitrix\Sign\Internal\Document setStoppedById(null|\int|\Bitrix\Main\DB\SqlExpression $stoppedById)
	 * @method bool hasStoppedById()
	 * @method bool isStoppedByIdFilled()
	 * @method bool isStoppedByIdChanged()
	 * @method null|\int remindActualStoppedById()
	 * @method null|\int requireStoppedById()
	 * @method \Bitrix\Sign\Internal\Document resetStoppedById()
	 * @method \Bitrix\Sign\Internal\Document unsetStoppedById()
	 * @method null|\int fillStoppedById()
	 * @method null|\Bitrix\Main\Type\DateTime getExternalDateCreate()
	 * @method \Bitrix\Sign\Internal\Document setExternalDateCreate(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $externalDateCreate)
	 * @method bool hasExternalDateCreate()
	 * @method bool isExternalDateCreateFilled()
	 * @method bool isExternalDateCreateChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualExternalDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime requireExternalDateCreate()
	 * @method \Bitrix\Sign\Internal\Document resetExternalDateCreate()
	 * @method \Bitrix\Sign\Internal\Document unsetExternalDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime fillExternalDateCreate()
	 * @method null|\string getProviderCode()
	 * @method \Bitrix\Sign\Internal\Document setProviderCode(null|\string|\Bitrix\Main\DB\SqlExpression $providerCode)
	 * @method bool hasProviderCode()
	 * @method bool isProviderCodeFilled()
	 * @method bool isProviderCodeChanged()
	 * @method null|\string remindActualProviderCode()
	 * @method null|\string requireProviderCode()
	 * @method \Bitrix\Sign\Internal\Document resetProviderCode()
	 * @method \Bitrix\Sign\Internal\Document unsetProviderCode()
	 * @method null|\string fillProviderCode()
	 * @method null|\int getTemplateId()
	 * @method \Bitrix\Sign\Internal\Document setTemplateId(null|\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method null|\int remindActualTemplateId()
	 * @method null|\int requireTemplateId()
	 * @method \Bitrix\Sign\Internal\Document resetTemplateId()
	 * @method \Bitrix\Sign\Internal\Document unsetTemplateId()
	 * @method null|\int fillTemplateId()
	 * @method null|\int getGroupId()
	 * @method \Bitrix\Sign\Internal\Document setGroupId(null|\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method null|\int remindActualGroupId()
	 * @method null|\int requireGroupId()
	 * @method \Bitrix\Sign\Internal\Document resetGroupId()
	 * @method \Bitrix\Sign\Internal\Document unsetGroupId()
	 * @method null|\int fillGroupId()
	 * @method null|\int getChatId()
	 * @method \Bitrix\Sign\Internal\Document setChatId(null|\int|\Bitrix\Main\DB\SqlExpression $chatId)
	 * @method bool hasChatId()
	 * @method bool isChatIdFilled()
	 * @method bool isChatIdChanged()
	 * @method null|\int remindActualChatId()
	 * @method null|\int requireChatId()
	 * @method \Bitrix\Sign\Internal\Document resetChatId()
	 * @method \Bitrix\Sign\Internal\Document unsetChatId()
	 * @method null|\int fillChatId()
	 * @method null|\int getCreatedFromDocumentId()
	 * @method \Bitrix\Sign\Internal\Document setCreatedFromDocumentId(null|\int|\Bitrix\Main\DB\SqlExpression $createdFromDocumentId)
	 * @method bool hasCreatedFromDocumentId()
	 * @method bool isCreatedFromDocumentIdFilled()
	 * @method bool isCreatedFromDocumentIdChanged()
	 * @method null|\int remindActualCreatedFromDocumentId()
	 * @method null|\int requireCreatedFromDocumentId()
	 * @method \Bitrix\Sign\Internal\Document resetCreatedFromDocumentId()
	 * @method \Bitrix\Sign\Internal\Document unsetCreatedFromDocumentId()
	 * @method null|\int fillCreatedFromDocumentId()
	 * @method \int getInitiatedByType()
	 * @method \Bitrix\Sign\Internal\Document setInitiatedByType(\int|\Bitrix\Main\DB\SqlExpression $initiatedByType)
	 * @method bool hasInitiatedByType()
	 * @method bool isInitiatedByTypeFilled()
	 * @method bool isInitiatedByTypeChanged()
	 * @method \int remindActualInitiatedByType()
	 * @method \int requireInitiatedByType()
	 * @method \Bitrix\Sign\Internal\Document resetInitiatedByType()
	 * @method \Bitrix\Sign\Internal\Document unsetInitiatedByType()
	 * @method \int fillInitiatedByType()
	 * @method null|\int getHcmlinkCompanyId()
	 * @method \Bitrix\Sign\Internal\Document setHcmlinkCompanyId(null|\int|\Bitrix\Main\DB\SqlExpression $hcmlinkCompanyId)
	 * @method bool hasHcmlinkCompanyId()
	 * @method bool isHcmlinkCompanyIdFilled()
	 * @method bool isHcmlinkCompanyIdChanged()
	 * @method null|\int remindActualHcmlinkCompanyId()
	 * @method null|\int requireHcmlinkCompanyId()
	 * @method \Bitrix\Sign\Internal\Document resetHcmlinkCompanyId()
	 * @method \Bitrix\Sign\Internal\Document unsetHcmlinkCompanyId()
	 * @method null|\int fillHcmlinkCompanyId()
	 * @method null|\Bitrix\Main\Type\DateTime getDateStatusChanged()
	 * @method \Bitrix\Sign\Internal\Document setDateStatusChanged(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStatusChanged)
	 * @method bool hasDateStatusChanged()
	 * @method bool isDateStatusChangedFilled()
	 * @method bool isDateStatusChangedChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualDateStatusChanged()
	 * @method null|\Bitrix\Main\Type\DateTime requireDateStatusChanged()
	 * @method \Bitrix\Sign\Internal\Document resetDateStatusChanged()
	 * @method \Bitrix\Sign\Internal\Document unsetDateStatusChanged()
	 * @method null|\Bitrix\Main\Type\DateTime fillDateStatusChanged()
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
	 * @method \Bitrix\Sign\Internal\Document set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\Document reset($fieldName)
	 * @method \Bitrix\Sign\Internal\Document unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\Document wakeUp($data)
	 */
	class EO_Document {
		/* @var \Bitrix\Sign\Internal\DocumentTable */
		static public $dataClass = '\Bitrix\Sign\Internal\DocumentTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * DocumentCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \string[] getSecCodeList()
	 * @method \string[] fillSecCode()
	 * @method \string[] getHostList()
	 * @method \string[] fillHost()
	 * @method \int[] getBlankIdList()
	 * @method \int[] fillBlankId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method array[] getMetaList()
	 * @method array[] fillMeta()
	 * @method \string[] getProcessingStatusList()
	 * @method \string[] fillProcessingStatus()
	 * @method \string[] getProcessingErrorList()
	 * @method \string[] fillProcessingError()
	 * @method \string[] getLangIdList()
	 * @method \string[] fillLangId()
	 * @method \int[] getResultFileIdList()
	 * @method \int[] fillResultFileId()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 * @method \int[] getModifiedByIdList()
	 * @method \int[] fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 * @method \Bitrix\Main\Type\DateTime[] getDateSignList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateSign()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \string[] getUidList()
	 * @method \string[] fillUid()
	 * @method null|\int[] getScenarioList()
	 * @method null|\int[] fillScenario()
	 * @method \int[] getVersionList()
	 * @method \int[] fillVersion()
	 * @method null|\string[] getCompanyUidList()
	 * @method null|\string[] fillCompanyUid()
	 * @method null|\int[] getRepresentativeIdList()
	 * @method null|\int[] fillRepresentativeId()
	 * @method null|\int[] getPartiesList()
	 * @method null|\int[] fillParties()
	 * @method null|\string[] getExternalIdList()
	 * @method null|\string[] fillExternalId()
	 * @method null|\string[] getRegionDocumentTypeList()
	 * @method null|\string[] fillRegionDocumentType()
	 * @method \int[] getSchemeList()
	 * @method \int[] fillScheme()
	 * @method null|\int[] getStoppedByIdList()
	 * @method null|\int[] fillStoppedById()
	 * @method null|\Bitrix\Main\Type\DateTime[] getExternalDateCreateList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillExternalDateCreate()
	 * @method null|\string[] getProviderCodeList()
	 * @method null|\string[] fillProviderCode()
	 * @method null|\int[] getTemplateIdList()
	 * @method null|\int[] fillTemplateId()
	 * @method null|\int[] getGroupIdList()
	 * @method null|\int[] fillGroupId()
	 * @method null|\int[] getChatIdList()
	 * @method null|\int[] fillChatId()
	 * @method null|\int[] getCreatedFromDocumentIdList()
	 * @method null|\int[] fillCreatedFromDocumentId()
	 * @method \int[] getInitiatedByTypeList()
	 * @method \int[] fillInitiatedByType()
	 * @method null|\int[] getHcmlinkCompanyIdList()
	 * @method null|\int[] fillHcmlinkCompanyId()
	 * @method null|\Bitrix\Main\Type\DateTime[] getDateStatusChangedList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillDateStatusChanged()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\Document $object)
	 * @method bool has(\Bitrix\Sign\Internal\Document $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Document getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Document[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\Document $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\DocumentCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\Document current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\DocumentCollection merge(?\Bitrix\Sign\Internal\DocumentCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Document_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\DocumentTable */
		static public $dataClass = '\Bitrix\Sign\Internal\DocumentTable';
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Document_Result exec()
	 * @method \Bitrix\Sign\Internal\Document fetchObject()
	 * @method \Bitrix\Sign\Internal\DocumentCollection fetchCollection()
	 */
	class EO_Document_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\Document fetchObject()
	 * @method \Bitrix\Sign\Internal\DocumentCollection fetchCollection()
	 */
	class EO_Document_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\Document createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\DocumentCollection createCollection()
	 * @method \Bitrix\Sign\Internal\Document wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\DocumentCollection wakeUpCollection($rows)
	 */
	class EO_Document_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\BlockTable:sign/lib/internal/blocktable.php */
namespace Bitrix\Sign\Internal {
	/**
	 * Block
	 * @see \Bitrix\Sign\Internal\BlockTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\Block setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getCode()
	 * @method \Bitrix\Sign\Internal\Block setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Sign\Internal\Block resetCode()
	 * @method \Bitrix\Sign\Internal\Block unsetCode()
	 * @method \string fillCode()
	 * @method null|\string getType()
	 * @method \Bitrix\Sign\Internal\Block setType(null|\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method null|\string remindActualType()
	 * @method null|\string requireType()
	 * @method \Bitrix\Sign\Internal\Block resetType()
	 * @method \Bitrix\Sign\Internal\Block unsetType()
	 * @method null|\string fillType()
	 * @method \int getBlankId()
	 * @method \Bitrix\Sign\Internal\Block setBlankId(\int|\Bitrix\Main\DB\SqlExpression $blankId)
	 * @method bool hasBlankId()
	 * @method bool isBlankIdFilled()
	 * @method bool isBlankIdChanged()
	 * @method \int remindActualBlankId()
	 * @method \int requireBlankId()
	 * @method \Bitrix\Sign\Internal\Block resetBlankId()
	 * @method \Bitrix\Sign\Internal\Block unsetBlankId()
	 * @method \int fillBlankId()
	 * @method array getPosition()
	 * @method \Bitrix\Sign\Internal\Block setPosition(array|\Bitrix\Main\DB\SqlExpression $position)
	 * @method bool hasPosition()
	 * @method bool isPositionFilled()
	 * @method bool isPositionChanged()
	 * @method array remindActualPosition()
	 * @method array requirePosition()
	 * @method \Bitrix\Sign\Internal\Block resetPosition()
	 * @method \Bitrix\Sign\Internal\Block unsetPosition()
	 * @method array fillPosition()
	 * @method array getStyle()
	 * @method \Bitrix\Sign\Internal\Block setStyle(array|\Bitrix\Main\DB\SqlExpression $style)
	 * @method bool hasStyle()
	 * @method bool isStyleFilled()
	 * @method bool isStyleChanged()
	 * @method array remindActualStyle()
	 * @method array requireStyle()
	 * @method \Bitrix\Sign\Internal\Block resetStyle()
	 * @method \Bitrix\Sign\Internal\Block unsetStyle()
	 * @method array fillStyle()
	 * @method array getData()
	 * @method \Bitrix\Sign\Internal\Block setData(array|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method array remindActualData()
	 * @method array requireData()
	 * @method \Bitrix\Sign\Internal\Block resetData()
	 * @method \Bitrix\Sign\Internal\Block unsetData()
	 * @method array fillData()
	 * @method \int getPart()
	 * @method \Bitrix\Sign\Internal\Block setPart(\int|\Bitrix\Main\DB\SqlExpression $part)
	 * @method bool hasPart()
	 * @method bool isPartFilled()
	 * @method bool isPartChanged()
	 * @method \int remindActualPart()
	 * @method \int requirePart()
	 * @method \Bitrix\Sign\Internal\Block resetPart()
	 * @method \Bitrix\Sign\Internal\Block unsetPart()
	 * @method \int fillPart()
	 * @method \int getCreatedById()
	 * @method \Bitrix\Sign\Internal\Block setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\Sign\Internal\Block resetCreatedById()
	 * @method \Bitrix\Sign\Internal\Block unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method \int getModifiedById()
	 * @method \Bitrix\Sign\Internal\Block setModifiedById(\int|\Bitrix\Main\DB\SqlExpression $modifiedById)
	 * @method bool hasModifiedById()
	 * @method bool isModifiedByIdFilled()
	 * @method bool isModifiedByIdChanged()
	 * @method \int remindActualModifiedById()
	 * @method \int requireModifiedById()
	 * @method \Bitrix\Sign\Internal\Block resetModifiedById()
	 * @method \Bitrix\Sign\Internal\Block unsetModifiedById()
	 * @method \int fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\Block setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\Block resetDateCreate()
	 * @method \Bitrix\Sign\Internal\Block unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\Sign\Internal\Block setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\Sign\Internal\Block resetDateModify()
	 * @method \Bitrix\Sign\Internal\Block unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
	 * @method null|\int getRole()
	 * @method \Bitrix\Sign\Internal\Block setRole(null|\int|\Bitrix\Main\DB\SqlExpression $role)
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method null|\int remindActualRole()
	 * @method null|\int requireRole()
	 * @method \Bitrix\Sign\Internal\Block resetRole()
	 * @method \Bitrix\Sign\Internal\Block unsetRole()
	 * @method null|\int fillRole()
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
	 * @method \Bitrix\Sign\Internal\Block set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\Block reset($fieldName)
	 * @method \Bitrix\Sign\Internal\Block unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\Block wakeUp($data)
	 */
	class EO_Block {
		/* @var \Bitrix\Sign\Internal\BlockTable */
		static public $dataClass = '\Bitrix\Sign\Internal\BlockTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * BlockCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method null|\string[] getTypeList()
	 * @method null|\string[] fillType()
	 * @method \int[] getBlankIdList()
	 * @method \int[] fillBlankId()
	 * @method array[] getPositionList()
	 * @method array[] fillPosition()
	 * @method array[] getStyleList()
	 * @method array[] fillStyle()
	 * @method array[] getDataList()
	 * @method array[] fillData()
	 * @method \int[] getPartList()
	 * @method \int[] fillPart()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 * @method \int[] getModifiedByIdList()
	 * @method \int[] fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 * @method null|\int[] getRoleList()
	 * @method null|\int[] fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\Block $object)
	 * @method bool has(\Bitrix\Sign\Internal\Block $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Block getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Block[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\Block $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\BlockCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\Block current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\BlockCollection merge(?\Bitrix\Sign\Internal\BlockCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Block_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\BlockTable */
		static public $dataClass = '\Bitrix\Sign\Internal\BlockTable';
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Block_Result exec()
	 * @method \Bitrix\Sign\Internal\Block fetchObject()
	 * @method \Bitrix\Sign\Internal\BlockCollection fetchCollection()
	 */
	class EO_Block_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\Block fetchObject()
	 * @method \Bitrix\Sign\Internal\BlockCollection fetchCollection()
	 */
	class EO_Block_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\Block createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\BlockCollection createCollection()
	 * @method \Bitrix\Sign\Internal\Block wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\BlockCollection wakeUpCollection($rows)
	 */
	class EO_Block_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\NodeSyncTable:sign/lib/internal/nodesynctable.php */
namespace Bitrix\Sign\Internal {
	/**
	 * NodeSync
	 * @see \Bitrix\Sign\Internal\NodeSyncTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\NodeSync setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDocumentId()
	 * @method \Bitrix\Sign\Internal\NodeSync setDocumentId(\int|\Bitrix\Main\DB\SqlExpression $documentId)
	 * @method bool hasDocumentId()
	 * @method bool isDocumentIdFilled()
	 * @method bool isDocumentIdChanged()
	 * @method \int remindActualDocumentId()
	 * @method \int requireDocumentId()
	 * @method \Bitrix\Sign\Internal\NodeSync resetDocumentId()
	 * @method \Bitrix\Sign\Internal\NodeSync unsetDocumentId()
	 * @method \int fillDocumentId()
	 * @method \int getNodeId()
	 * @method \Bitrix\Sign\Internal\NodeSync setNodeId(\int|\Bitrix\Main\DB\SqlExpression $nodeId)
	 * @method bool hasNodeId()
	 * @method bool isNodeIdFilled()
	 * @method bool isNodeIdChanged()
	 * @method \int remindActualNodeId()
	 * @method \int requireNodeId()
	 * @method \Bitrix\Sign\Internal\NodeSync resetNodeId()
	 * @method \Bitrix\Sign\Internal\NodeSync unsetNodeId()
	 * @method \int fillNodeId()
	 * @method \boolean getIsFlat()
	 * @method \Bitrix\Sign\Internal\NodeSync setIsFlat(\boolean|\Bitrix\Main\DB\SqlExpression $isFlat)
	 * @method bool hasIsFlat()
	 * @method bool isIsFlatFilled()
	 * @method bool isIsFlatChanged()
	 * @method \boolean remindActualIsFlat()
	 * @method \boolean requireIsFlat()
	 * @method \Bitrix\Sign\Internal\NodeSync resetIsFlat()
	 * @method \Bitrix\Sign\Internal\NodeSync unsetIsFlat()
	 * @method \boolean fillIsFlat()
	 * @method \int getStatus()
	 * @method \Bitrix\Sign\Internal\NodeSync setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\Sign\Internal\NodeSync resetStatus()
	 * @method \Bitrix\Sign\Internal\NodeSync unsetStatus()
	 * @method \int fillStatus()
	 * @method \int getPage()
	 * @method \Bitrix\Sign\Internal\NodeSync setPage(\int|\Bitrix\Main\DB\SqlExpression $page)
	 * @method bool hasPage()
	 * @method bool isPageFilled()
	 * @method bool isPageChanged()
	 * @method \int remindActualPage()
	 * @method \int requirePage()
	 * @method \Bitrix\Sign\Internal\NodeSync resetPage()
	 * @method \Bitrix\Sign\Internal\NodeSync unsetPage()
	 * @method \int fillPage()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\NodeSync setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\NodeSync resetDateCreate()
	 * @method \Bitrix\Sign\Internal\NodeSync unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\Sign\Internal\NodeSync setDateModify(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method null|\Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\Sign\Internal\NodeSync resetDateModify()
	 * @method \Bitrix\Sign\Internal\NodeSync unsetDateModify()
	 * @method null|\Bitrix\Main\Type\DateTime fillDateModify()
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
	 * @method \Bitrix\Sign\Internal\NodeSync set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\NodeSync reset($fieldName)
	 * @method \Bitrix\Sign\Internal\NodeSync unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\NodeSync wakeUp($data)
	 */
	class EO_NodeSync {
		/* @var \Bitrix\Sign\Internal\NodeSyncTable */
		static public $dataClass = '\Bitrix\Sign\Internal\NodeSyncTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * NodeSyncCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDocumentIdList()
	 * @method \int[] fillDocumentId()
	 * @method \int[] getNodeIdList()
	 * @method \int[] fillNodeId()
	 * @method \boolean[] getIsFlatList()
	 * @method \boolean[] fillIsFlat()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \int[] getPageList()
	 * @method \int[] fillPage()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillDateModify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\NodeSync $object)
	 * @method bool has(\Bitrix\Sign\Internal\NodeSync $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\NodeSync getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\NodeSync[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\NodeSync $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\NodeSyncCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\NodeSync current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\NodeSyncCollection merge(?\Bitrix\Sign\Internal\NodeSyncCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_NodeSync_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\NodeSyncTable */
		static public $dataClass = '\Bitrix\Sign\Internal\NodeSyncTable';
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_NodeSync_Result exec()
	 * @method \Bitrix\Sign\Internal\NodeSync fetchObject()
	 * @method \Bitrix\Sign\Internal\NodeSyncCollection fetchCollection()
	 */
	class EO_NodeSync_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\NodeSync fetchObject()
	 * @method \Bitrix\Sign\Internal\NodeSyncCollection fetchCollection()
	 */
	class EO_NodeSync_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\NodeSync createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\NodeSyncCollection createCollection()
	 * @method \Bitrix\Sign\Internal\NodeSync wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\NodeSyncCollection wakeUpCollection($rows)
	 */
	class EO_NodeSync_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\BlankTable:sign/lib/internal/blanktable.php */
namespace Bitrix\Sign\Internal {
	/**
	 * Blank
	 * @see \Bitrix\Sign\Internal\BlankTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\Blank setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\Sign\Internal\Blank setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Sign\Internal\Blank resetTitle()
	 * @method \Bitrix\Sign\Internal\Blank unsetTitle()
	 * @method \string fillTitle()
	 * @method \int getExternalId()
	 * @method \Bitrix\Sign\Internal\Blank setExternalId(\int|\Bitrix\Main\DB\SqlExpression $externalId)
	 * @method bool hasExternalId()
	 * @method bool isExternalIdFilled()
	 * @method bool isExternalIdChanged()
	 * @method \int remindActualExternalId()
	 * @method \int requireExternalId()
	 * @method \Bitrix\Sign\Internal\Blank resetExternalId()
	 * @method \Bitrix\Sign\Internal\Blank unsetExternalId()
	 * @method \int fillExternalId()
	 * @method \string getHost()
	 * @method \Bitrix\Sign\Internal\Blank setHost(\string|\Bitrix\Main\DB\SqlExpression $host)
	 * @method bool hasHost()
	 * @method bool isHostFilled()
	 * @method bool isHostChanged()
	 * @method \string remindActualHost()
	 * @method \string requireHost()
	 * @method \Bitrix\Sign\Internal\Blank resetHost()
	 * @method \Bitrix\Sign\Internal\Blank unsetHost()
	 * @method \string fillHost()
	 * @method \string getStatus()
	 * @method \Bitrix\Sign\Internal\Blank setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Sign\Internal\Blank resetStatus()
	 * @method \Bitrix\Sign\Internal\Blank unsetStatus()
	 * @method \string fillStatus()
	 * @method array getFileId()
	 * @method \Bitrix\Sign\Internal\Blank setFileId(array|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method array remindActualFileId()
	 * @method array requireFileId()
	 * @method \Bitrix\Sign\Internal\Blank resetFileId()
	 * @method \Bitrix\Sign\Internal\Blank unsetFileId()
	 * @method array fillFileId()
	 * @method \string getConverted()
	 * @method \Bitrix\Sign\Internal\Blank setConverted(\string|\Bitrix\Main\DB\SqlExpression $converted)
	 * @method bool hasConverted()
	 * @method bool isConvertedFilled()
	 * @method bool isConvertedChanged()
	 * @method \string remindActualConverted()
	 * @method \string requireConverted()
	 * @method \Bitrix\Sign\Internal\Blank resetConverted()
	 * @method \Bitrix\Sign\Internal\Blank unsetConverted()
	 * @method \string fillConverted()
	 * @method \int getScenario()
	 * @method \Bitrix\Sign\Internal\Blank setScenario(\int|\Bitrix\Main\DB\SqlExpression $scenario)
	 * @method bool hasScenario()
	 * @method bool isScenarioFilled()
	 * @method bool isScenarioChanged()
	 * @method \int remindActualScenario()
	 * @method \int requireScenario()
	 * @method \Bitrix\Sign\Internal\Blank resetScenario()
	 * @method \Bitrix\Sign\Internal\Blank unsetScenario()
	 * @method \int fillScenario()
	 * @method \int getCreatedById()
	 * @method \Bitrix\Sign\Internal\Blank setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\Sign\Internal\Blank resetCreatedById()
	 * @method \Bitrix\Sign\Internal\Blank unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method \int getModifiedById()
	 * @method \Bitrix\Sign\Internal\Blank setModifiedById(\int|\Bitrix\Main\DB\SqlExpression $modifiedById)
	 * @method bool hasModifiedById()
	 * @method bool isModifiedByIdFilled()
	 * @method bool isModifiedByIdChanged()
	 * @method \int remindActualModifiedById()
	 * @method \int requireModifiedById()
	 * @method \Bitrix\Sign\Internal\Blank resetModifiedById()
	 * @method \Bitrix\Sign\Internal\Blank unsetModifiedById()
	 * @method \int fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\Blank setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\Blank resetDateCreate()
	 * @method \Bitrix\Sign\Internal\Blank unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\Sign\Internal\Blank setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\Sign\Internal\Blank resetDateModify()
	 * @method \Bitrix\Sign\Internal\Blank unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
	 * @method \boolean getForTemplate()
	 * @method \Bitrix\Sign\Internal\Blank setForTemplate(\boolean|\Bitrix\Main\DB\SqlExpression $forTemplate)
	 * @method bool hasForTemplate()
	 * @method bool isForTemplateFilled()
	 * @method bool isForTemplateChanged()
	 * @method \boolean remindActualForTemplate()
	 * @method \boolean requireForTemplate()
	 * @method \Bitrix\Sign\Internal\Blank resetForTemplate()
	 * @method \Bitrix\Sign\Internal\Blank unsetForTemplate()
	 * @method \boolean fillForTemplate()
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
	 * @method \Bitrix\Sign\Internal\Blank set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\Blank reset($fieldName)
	 * @method \Bitrix\Sign\Internal\Blank unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\Blank wakeUp($data)
	 */
	class EO_Blank {
		/* @var \Bitrix\Sign\Internal\BlankTable */
		static public $dataClass = '\Bitrix\Sign\Internal\BlankTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * BlankCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \int[] getExternalIdList()
	 * @method \int[] fillExternalId()
	 * @method \string[] getHostList()
	 * @method \string[] fillHost()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method array[] getFileIdList()
	 * @method array[] fillFileId()
	 * @method \string[] getConvertedList()
	 * @method \string[] fillConverted()
	 * @method \int[] getScenarioList()
	 * @method \int[] fillScenario()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 * @method \int[] getModifiedByIdList()
	 * @method \int[] fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 * @method \boolean[] getForTemplateList()
	 * @method \boolean[] fillForTemplate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\Blank $object)
	 * @method bool has(\Bitrix\Sign\Internal\Blank $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Blank getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Blank[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\Blank $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\BlankCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\Blank current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\BlankCollection merge(?\Bitrix\Sign\Internal\BlankCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Blank_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\BlankTable */
		static public $dataClass = '\Bitrix\Sign\Internal\BlankTable';
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Blank_Result exec()
	 * @method \Bitrix\Sign\Internal\Blank fetchObject()
	 * @method \Bitrix\Sign\Internal\BlankCollection fetchCollection()
	 */
	class EO_Blank_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\Blank fetchObject()
	 * @method \Bitrix\Sign\Internal\BlankCollection fetchCollection()
	 */
	class EO_Blank_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\Blank createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\BlankCollection createCollection()
	 * @method \Bitrix\Sign\Internal\Blank wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\BlankCollection wakeUpCollection($rows)
	 */
	class EO_Blank_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\Integration\FormTable:sign/lib/internal/integration/form.php */
namespace Bitrix\Sign\Internal\Integration {
	/**
	 * EO_Form
	 * @see \Bitrix\Sign\Internal\Integration\FormTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBlankId()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form setBlankId(\int|\Bitrix\Main\DB\SqlExpression $blankId)
	 * @method bool hasBlankId()
	 * @method bool isBlankIdFilled()
	 * @method bool isBlankIdChanged()
	 * @method \int remindActualBlankId()
	 * @method \int requireBlankId()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form resetBlankId()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form unsetBlankId()
	 * @method \int fillBlankId()
	 * @method \int getPart()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form setPart(\int|\Bitrix\Main\DB\SqlExpression $part)
	 * @method bool hasPart()
	 * @method bool isPartFilled()
	 * @method bool isPartChanged()
	 * @method \int remindActualPart()
	 * @method \int requirePart()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form resetPart()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form unsetPart()
	 * @method \int fillPart()
	 * @method \int getFormId()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form setFormId(\int|\Bitrix\Main\DB\SqlExpression $formId)
	 * @method bool hasFormId()
	 * @method bool isFormIdFilled()
	 * @method bool isFormIdChanged()
	 * @method \int remindActualFormId()
	 * @method \int requireFormId()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form resetFormId()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form unsetFormId()
	 * @method \int fillFormId()
	 * @method \int getCreatedById()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form resetCreatedById()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method \int getModifiedById()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form setModifiedById(\int|\Bitrix\Main\DB\SqlExpression $modifiedById)
	 * @method bool hasModifiedById()
	 * @method bool isModifiedByIdFilled()
	 * @method bool isModifiedByIdChanged()
	 * @method \int remindActualModifiedById()
	 * @method \int requireModifiedById()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form resetModifiedById()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form unsetModifiedById()
	 * @method \int fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form resetDateCreate()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form resetDateModify()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form unsetDateModify()
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
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form reset($fieldName)
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\Integration\EO_Form wakeUp($data)
	 */
	class EO_Form {
		/* @var \Bitrix\Sign\Internal\Integration\FormTable */
		static public $dataClass = '\Bitrix\Sign\Internal\Integration\FormTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal\Integration {
	/**
	 * EO_Form_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBlankIdList()
	 * @method \int[] fillBlankId()
	 * @method \int[] getPartList()
	 * @method \int[] fillPart()
	 * @method \int[] getFormIdList()
	 * @method \int[] fillFormId()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 * @method \int[] getModifiedByIdList()
	 * @method \int[] fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\Integration\EO_Form $object)
	 * @method bool has(\Bitrix\Sign\Internal\Integration\EO_Form $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\Integration\EO_Form $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\Integration\EO_Form_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form_Collection merge(?\Bitrix\Sign\Internal\Integration\EO_Form_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Form_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\Integration\FormTable */
		static public $dataClass = '\Bitrix\Sign\Internal\Integration\FormTable';
	}
}
namespace Bitrix\Sign\Internal\Integration {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Form_Result exec()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form fetchObject()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form_Collection fetchCollection()
	 */
	class EO_Form_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form fetchObject()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form_Collection fetchCollection()
	 */
	class EO_Form_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form_Collection createCollection()
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\Integration\EO_Form_Collection wakeUpCollection($rows)
	 */
	class EO_Form_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\FileTable:sign/lib/internal/filetable.php */
namespace Bitrix\Sign\Internal {
	/**
	 * File
	 * @see \Bitrix\Sign\Internal\FileTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\File setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getEntityTypeId()
	 * @method \Bitrix\Sign\Internal\File setEntityTypeId(\int|\Bitrix\Main\DB\SqlExpression $entityTypeId)
	 * @method bool hasEntityTypeId()
	 * @method bool isEntityTypeIdFilled()
	 * @method bool isEntityTypeIdChanged()
	 * @method \int remindActualEntityTypeId()
	 * @method \int requireEntityTypeId()
	 * @method \Bitrix\Sign\Internal\File resetEntityTypeId()
	 * @method \Bitrix\Sign\Internal\File unsetEntityTypeId()
	 * @method \int fillEntityTypeId()
	 * @method \int getEntityId()
	 * @method \Bitrix\Sign\Internal\File setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Sign\Internal\File resetEntityId()
	 * @method \Bitrix\Sign\Internal\File unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \int getCode()
	 * @method \Bitrix\Sign\Internal\File setCode(\int|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \int remindActualCode()
	 * @method \int requireCode()
	 * @method \Bitrix\Sign\Internal\File resetCode()
	 * @method \Bitrix\Sign\Internal\File unsetCode()
	 * @method \int fillCode()
	 * @method \int getFileId()
	 * @method \Bitrix\Sign\Internal\File setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Sign\Internal\File resetFileId()
	 * @method \Bitrix\Sign\Internal\File unsetFileId()
	 * @method \int fillFileId()
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
	 * @method \Bitrix\Sign\Internal\File set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\File reset($fieldName)
	 * @method \Bitrix\Sign\Internal\File unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\File wakeUp($data)
	 */
	class EO_File {
		/* @var \Bitrix\Sign\Internal\FileTable */
		static public $dataClass = '\Bitrix\Sign\Internal\FileTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * FileCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getEntityTypeIdList()
	 * @method \int[] fillEntityTypeId()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \int[] getCodeList()
	 * @method \int[] fillCode()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\File $object)
	 * @method bool has(\Bitrix\Sign\Internal\File $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\File getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\File[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\File $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\FileCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\File current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\FileCollection merge(?\Bitrix\Sign\Internal\FileCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_File_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\FileTable */
		static public $dataClass = '\Bitrix\Sign\Internal\FileTable';
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_File_Result exec()
	 * @method \Bitrix\Sign\Internal\File fetchObject()
	 * @method \Bitrix\Sign\Internal\FileCollection fetchCollection()
	 */
	class EO_File_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\File fetchObject()
	 * @method \Bitrix\Sign\Internal\FileCollection fetchCollection()
	 */
	class EO_File_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\File createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\FileCollection createCollection()
	 * @method \Bitrix\Sign\Internal\File wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\FileCollection wakeUpCollection($rows)
	 */
	class EO_File_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\DocumentChatTable:sign/lib/internal/documentchattable.php */
namespace Bitrix\Sign\Internal {
	/**
	 * DocumentChat
	 * @see \Bitrix\Sign\Internal\DocumentChatTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\DocumentChat setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getChatId()
	 * @method \Bitrix\Sign\Internal\DocumentChat setChatId(\int|\Bitrix\Main\DB\SqlExpression $chatId)
	 * @method bool hasChatId()
	 * @method bool isChatIdFilled()
	 * @method bool isChatIdChanged()
	 * @method \int remindActualChatId()
	 * @method \int requireChatId()
	 * @method \Bitrix\Sign\Internal\DocumentChat resetChatId()
	 * @method \Bitrix\Sign\Internal\DocumentChat unsetChatId()
	 * @method \int fillChatId()
	 * @method \int getDocumentId()
	 * @method \Bitrix\Sign\Internal\DocumentChat setDocumentId(\int|\Bitrix\Main\DB\SqlExpression $documentId)
	 * @method bool hasDocumentId()
	 * @method bool isDocumentIdFilled()
	 * @method bool isDocumentIdChanged()
	 * @method \int remindActualDocumentId()
	 * @method \int requireDocumentId()
	 * @method \Bitrix\Sign\Internal\DocumentChat resetDocumentId()
	 * @method \Bitrix\Sign\Internal\DocumentChat unsetDocumentId()
	 * @method \int fillDocumentId()
	 * @method \int getType()
	 * @method \Bitrix\Sign\Internal\DocumentChat setType(\int|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \int remindActualType()
	 * @method \int requireType()
	 * @method \Bitrix\Sign\Internal\DocumentChat resetType()
	 * @method \Bitrix\Sign\Internal\DocumentChat unsetType()
	 * @method \int fillType()
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
	 * @method \Bitrix\Sign\Internal\DocumentChat set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\DocumentChat reset($fieldName)
	 * @method \Bitrix\Sign\Internal\DocumentChat unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\DocumentChat wakeUp($data)
	 */
	class EO_DocumentChat {
		/* @var \Bitrix\Sign\Internal\DocumentChatTable */
		static public $dataClass = '\Bitrix\Sign\Internal\DocumentChatTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * EO_DocumentChat_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getChatIdList()
	 * @method \int[] fillChatId()
	 * @method \int[] getDocumentIdList()
	 * @method \int[] fillDocumentId()
	 * @method \int[] getTypeList()
	 * @method \int[] fillType()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\DocumentChat $object)
	 * @method bool has(\Bitrix\Sign\Internal\DocumentChat $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\DocumentChat getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\DocumentChat[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\DocumentChat $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\EO_DocumentChat_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\DocumentChat current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\EO_DocumentChat_Collection merge(?\Bitrix\Sign\Internal\EO_DocumentChat_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_DocumentChat_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\DocumentChatTable */
		static public $dataClass = '\Bitrix\Sign\Internal\DocumentChatTable';
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DocumentChat_Result exec()
	 * @method \Bitrix\Sign\Internal\DocumentChat fetchObject()
	 * @method \Bitrix\Sign\Internal\EO_DocumentChat_Collection fetchCollection()
	 */
	class EO_DocumentChat_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\DocumentChat fetchObject()
	 * @method \Bitrix\Sign\Internal\EO_DocumentChat_Collection fetchCollection()
	 */
	class EO_DocumentChat_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\DocumentChat createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\EO_DocumentChat_Collection createCollection()
	 * @method \Bitrix\Sign\Internal\DocumentChat wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\EO_DocumentChat_Collection wakeUpCollection($rows)
	 */
	class EO_DocumentChat_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\ServiceUser\ServiceUserTable:sign/lib/internal/serviceuser/serviceusertable.php */
namespace Bitrix\Sign\Internal\ServiceUser {
	/**
	 * ServiceUser
	 * @see \Bitrix\Sign\Internal\ServiceUser\ServiceUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getUserId()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \string getUid()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser setUid(\string|\Bitrix\Main\DB\SqlExpression $uid)
	 * @method bool hasUid()
	 * @method bool isUidFilled()
	 * @method bool isUidChanged()
	 * @method \string remindActualUid()
	 * @method \string requireUid()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser resetUid()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser unsetUid()
	 * @method \string fillUid()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser resetDateCreate()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser unsetDateCreate()
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
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser reset($fieldName)
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\ServiceUser\ServiceUser wakeUp($data)
	 */
	class EO_ServiceUser {
		/* @var \Bitrix\Sign\Internal\ServiceUser\ServiceUserTable */
		static public $dataClass = '\Bitrix\Sign\Internal\ServiceUser\ServiceUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal\ServiceUser {
	/**
	 * ServiceUserCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getUserIdList()
	 * @method \string[] getUidList()
	 * @method \string[] fillUid()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\ServiceUser\ServiceUser $object)
	 * @method bool has(\Bitrix\Sign\Internal\ServiceUser\ServiceUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\ServiceUser\ServiceUser $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\ServiceUser\ServiceUserCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUserCollection merge(?\Bitrix\Sign\Internal\ServiceUser\ServiceUserCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ServiceUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\ServiceUser\ServiceUserTable */
		static public $dataClass = '\Bitrix\Sign\Internal\ServiceUser\ServiceUserTable';
	}
}
namespace Bitrix\Sign\Internal\ServiceUser {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ServiceUser_Result exec()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser fetchObject()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUserCollection fetchCollection()
	 */
	class EO_ServiceUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser fetchObject()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUserCollection fetchCollection()
	 */
	class EO_ServiceUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUserCollection createCollection()
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUser wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\ServiceUser\ServiceUserCollection wakeUpCollection($rows)
	 */
	class EO_ServiceUser_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\Document\GroupTable:sign/lib/internal/document/grouptable.php */
namespace Bitrix\Sign\Internal\Document {
	/**
	 * Group
	 * @see \Bitrix\Sign\Internal\Document\GroupTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\Document\Group setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCreatedById()
	 * @method \Bitrix\Sign\Internal\Document\Group setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\Sign\Internal\Document\Group resetCreatedById()
	 * @method \Bitrix\Sign\Internal\Document\Group unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\Document\Group setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\Document\Group resetDateCreate()
	 * @method \Bitrix\Sign\Internal\Document\Group unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\Sign\Internal\Document\Group setDateModify(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method null|\Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\Sign\Internal\Document\Group resetDateModify()
	 * @method \Bitrix\Sign\Internal\Document\Group unsetDateModify()
	 * @method null|\Bitrix\Main\Type\DateTime fillDateModify()
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
	 * @method \Bitrix\Sign\Internal\Document\Group set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\Document\Group reset($fieldName)
	 * @method \Bitrix\Sign\Internal\Document\Group unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\Document\Group wakeUp($data)
	 */
	class EO_Group {
		/* @var \Bitrix\Sign\Internal\Document\GroupTable */
		static public $dataClass = '\Bitrix\Sign\Internal\Document\GroupTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal\Document {
	/**
	 * GroupCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillDateModify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\Document\Group $object)
	 * @method bool has(\Bitrix\Sign\Internal\Document\Group $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Document\Group getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Document\Group[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\Document\Group $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\Document\GroupCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\Document\Group current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\Document\GroupCollection merge(?\Bitrix\Sign\Internal\Document\GroupCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Group_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\Document\GroupTable */
		static public $dataClass = '\Bitrix\Sign\Internal\Document\GroupTable';
	}
}
namespace Bitrix\Sign\Internal\Document {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Group_Result exec()
	 * @method \Bitrix\Sign\Internal\Document\Group fetchObject()
	 * @method \Bitrix\Sign\Internal\Document\GroupCollection fetchCollection()
	 */
	class EO_Group_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\Document\Group fetchObject()
	 * @method \Bitrix\Sign\Internal\Document\GroupCollection fetchCollection()
	 */
	class EO_Group_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\Document\Group createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\Document\GroupCollection createCollection()
	 * @method \Bitrix\Sign\Internal\Document\Group wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\Document\GroupCollection wakeUpCollection($rows)
	 */
	class EO_Group_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\Document\TemplateTable:sign/lib/internal/document/templatetable.php */
namespace Bitrix\Sign\Internal\Document {
	/**
	 * Template
	 * @see \Bitrix\Sign\Internal\Document\TemplateTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\Document\Template setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getUid()
	 * @method \Bitrix\Sign\Internal\Document\Template setUid(\string|\Bitrix\Main\DB\SqlExpression $uid)
	 * @method bool hasUid()
	 * @method bool isUidFilled()
	 * @method bool isUidChanged()
	 * @method \string remindActualUid()
	 * @method \string requireUid()
	 * @method \Bitrix\Sign\Internal\Document\Template resetUid()
	 * @method \Bitrix\Sign\Internal\Document\Template unsetUid()
	 * @method \string fillUid()
	 * @method \string getTitle()
	 * @method \Bitrix\Sign\Internal\Document\Template setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Sign\Internal\Document\Template resetTitle()
	 * @method \Bitrix\Sign\Internal\Document\Template unsetTitle()
	 * @method \string fillTitle()
	 * @method \int getStatus()
	 * @method \Bitrix\Sign\Internal\Document\Template setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\Sign\Internal\Document\Template resetStatus()
	 * @method \Bitrix\Sign\Internal\Document\Template unsetStatus()
	 * @method \int fillStatus()
	 * @method \int getCreatedById()
	 * @method \Bitrix\Sign\Internal\Document\Template setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\Sign\Internal\Document\Template resetCreatedById()
	 * @method \Bitrix\Sign\Internal\Document\Template unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method null|\int getModifiedById()
	 * @method \Bitrix\Sign\Internal\Document\Template setModifiedById(null|\int|\Bitrix\Main\DB\SqlExpression $modifiedById)
	 * @method bool hasModifiedById()
	 * @method bool isModifiedByIdFilled()
	 * @method bool isModifiedByIdChanged()
	 * @method null|\int remindActualModifiedById()
	 * @method null|\int requireModifiedById()
	 * @method \Bitrix\Sign\Internal\Document\Template resetModifiedById()
	 * @method \Bitrix\Sign\Internal\Document\Template unsetModifiedById()
	 * @method null|\int fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\Document\Template setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\Document\Template resetDateCreate()
	 * @method \Bitrix\Sign\Internal\Document\Template unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\Sign\Internal\Document\Template setDateModify(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method null|\Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\Sign\Internal\Document\Template resetDateModify()
	 * @method \Bitrix\Sign\Internal\Document\Template unsetDateModify()
	 * @method null|\Bitrix\Main\Type\DateTime fillDateModify()
	 * @method \int getVisibility()
	 * @method \Bitrix\Sign\Internal\Document\Template setVisibility(\int|\Bitrix\Main\DB\SqlExpression $visibility)
	 * @method bool hasVisibility()
	 * @method bool isVisibilityFilled()
	 * @method bool isVisibilityChanged()
	 * @method \int remindActualVisibility()
	 * @method \int requireVisibility()
	 * @method \Bitrix\Sign\Internal\Document\Template resetVisibility()
	 * @method \Bitrix\Sign\Internal\Document\Template unsetVisibility()
	 * @method \int fillVisibility()
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
	 * @method \Bitrix\Sign\Internal\Document\Template set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\Document\Template reset($fieldName)
	 * @method \Bitrix\Sign\Internal\Document\Template unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\Document\Template wakeUp($data)
	 */
	class EO_Template {
		/* @var \Bitrix\Sign\Internal\Document\TemplateTable */
		static public $dataClass = '\Bitrix\Sign\Internal\Document\TemplateTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal\Document {
	/**
	 * TemplateCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getUidList()
	 * @method \string[] fillUid()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 * @method null|\int[] getModifiedByIdList()
	 * @method null|\int[] fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillDateModify()
	 * @method \int[] getVisibilityList()
	 * @method \int[] fillVisibility()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\Document\Template $object)
	 * @method bool has(\Bitrix\Sign\Internal\Document\Template $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Document\Template getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Document\Template[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\Document\Template $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\Document\TemplateCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\Document\Template current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\Document\TemplateCollection merge(?\Bitrix\Sign\Internal\Document\TemplateCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Template_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\Document\TemplateTable */
		static public $dataClass = '\Bitrix\Sign\Internal\Document\TemplateTable';
	}
}
namespace Bitrix\Sign\Internal\Document {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Template_Result exec()
	 * @method \Bitrix\Sign\Internal\Document\Template fetchObject()
	 * @method \Bitrix\Sign\Internal\Document\TemplateCollection fetchCollection()
	 */
	class EO_Template_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\Document\Template fetchObject()
	 * @method \Bitrix\Sign\Internal\Document\TemplateCollection fetchCollection()
	 */
	class EO_Template_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\Document\Template createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\Document\TemplateCollection createCollection()
	 * @method \Bitrix\Sign\Internal\Document\Template wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\Document\TemplateCollection wakeUpCollection($rows)
	 */
	class EO_Template_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\Blank\ResourceTable:sign/lib/internal/blank/resourcetable.php */
namespace Bitrix\Sign\Internal\Blank {
	/**
	 * Resource
	 * @see \Bitrix\Sign\Internal\Blank\ResourceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\Blank\Resource setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBlankId()
	 * @method \Bitrix\Sign\Internal\Blank\Resource setBlankId(\int|\Bitrix\Main\DB\SqlExpression $blankId)
	 * @method bool hasBlankId()
	 * @method bool isBlankIdFilled()
	 * @method bool isBlankIdChanged()
	 * @method \int remindActualBlankId()
	 * @method \int requireBlankId()
	 * @method \Bitrix\Sign\Internal\Blank\Resource resetBlankId()
	 * @method \Bitrix\Sign\Internal\Blank\Resource unsetBlankId()
	 * @method \int fillBlankId()
	 * @method \int getFileId()
	 * @method \Bitrix\Sign\Internal\Blank\Resource setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Sign\Internal\Blank\Resource resetFileId()
	 * @method \Bitrix\Sign\Internal\Blank\Resource unsetFileId()
	 * @method \int fillFileId()
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
	 * @method \Bitrix\Sign\Internal\Blank\Resource set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\Blank\Resource reset($fieldName)
	 * @method \Bitrix\Sign\Internal\Blank\Resource unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\Blank\Resource wakeUp($data)
	 */
	class EO_Resource {
		/* @var \Bitrix\Sign\Internal\Blank\ResourceTable */
		static public $dataClass = '\Bitrix\Sign\Internal\Blank\ResourceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal\Blank {
	/**
	 * ResourceCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBlankIdList()
	 * @method \int[] fillBlankId()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\Blank\Resource $object)
	 * @method bool has(\Bitrix\Sign\Internal\Blank\Resource $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Blank\Resource getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Blank\Resource[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\Blank\Resource $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\Blank\ResourceCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\Blank\Resource current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\Blank\ResourceCollection merge(?\Bitrix\Sign\Internal\Blank\ResourceCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Resource_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\Blank\ResourceTable */
		static public $dataClass = '\Bitrix\Sign\Internal\Blank\ResourceTable';
	}
}
namespace Bitrix\Sign\Internal\Blank {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Resource_Result exec()
	 * @method \Bitrix\Sign\Internal\Blank\Resource fetchObject()
	 * @method \Bitrix\Sign\Internal\Blank\ResourceCollection fetchCollection()
	 */
	class EO_Resource_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\Blank\Resource fetchObject()
	 * @method \Bitrix\Sign\Internal\Blank\ResourceCollection fetchCollection()
	 */
	class EO_Resource_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\Blank\Resource createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\Blank\ResourceCollection createCollection()
	 * @method \Bitrix\Sign\Internal\Blank\Resource wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\Blank\ResourceCollection wakeUpCollection($rows)
	 */
	class EO_Resource_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\FieldValue\FieldValueTable:sign/lib/internal/fieldvalue/fieldvaluetable.php */
namespace Bitrix\Sign\Internal\FieldValue {
	/**
	 * FieldValue
	 * @see \Bitrix\Sign\Internal\FieldValue\FieldValueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getMemberId()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue setMemberId(\int|\Bitrix\Main\DB\SqlExpression $memberId)
	 * @method bool hasMemberId()
	 * @method bool isMemberIdFilled()
	 * @method bool isMemberIdChanged()
	 * @method \int remindActualMemberId()
	 * @method \int requireMemberId()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue resetMemberId()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue unsetMemberId()
	 * @method \int fillMemberId()
	 * @method \string getFieldName()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue setFieldName(\string|\Bitrix\Main\DB\SqlExpression $fieldName)
	 * @method bool hasFieldName()
	 * @method bool isFieldNameFilled()
	 * @method bool isFieldNameChanged()
	 * @method \string remindActualFieldName()
	 * @method \string requireFieldName()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue resetFieldName()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue unsetFieldName()
	 * @method \string fillFieldName()
	 * @method \string getValue()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue resetValue()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue unsetValue()
	 * @method \string fillValue()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue resetDateCreate()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue setDateModify(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method null|\Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue resetDateModify()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue unsetDateModify()
	 * @method null|\Bitrix\Main\Type\DateTime fillDateModify()
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
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue reset($fieldName)
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\FieldValue\FieldValue wakeUp($data)
	 */
	class EO_FieldValue {
		/* @var \Bitrix\Sign\Internal\FieldValue\FieldValueTable */
		static public $dataClass = '\Bitrix\Sign\Internal\FieldValue\FieldValueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal\FieldValue {
	/**
	 * FieldValueCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getMemberIdList()
	 * @method \int[] fillMemberId()
	 * @method \string[] getFieldNameList()
	 * @method \string[] fillFieldName()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillDateModify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\FieldValue\FieldValue $object)
	 * @method bool has(\Bitrix\Sign\Internal\FieldValue\FieldValue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\FieldValue\FieldValue $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\FieldValue\FieldValueCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValueCollection merge(?\Bitrix\Sign\Internal\FieldValue\FieldValueCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FieldValue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\FieldValue\FieldValueTable */
		static public $dataClass = '\Bitrix\Sign\Internal\FieldValue\FieldValueTable';
	}
}
namespace Bitrix\Sign\Internal\FieldValue {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FieldValue_Result exec()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue fetchObject()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValueCollection fetchCollection()
	 */
	class EO_FieldValue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue fetchObject()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValueCollection fetchCollection()
	 */
	class EO_FieldValue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValueCollection createCollection()
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValue wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\FieldValue\FieldValueCollection wakeUpCollection($rows)
	 */
	class EO_FieldValue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\LegalLog\LegalLogTable:sign/lib/internal/legallog/legallogtable.php */
namespace Bitrix\Sign\Internal\LegalLog {
	/**
	 * LegalLog
	 * @see \Bitrix\Sign\Internal\LegalLog\LegalLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDocumentId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog setDocumentId(\int|\Bitrix\Main\DB\SqlExpression $documentId)
	 * @method bool hasDocumentId()
	 * @method bool isDocumentIdFilled()
	 * @method bool isDocumentIdChanged()
	 * @method \int remindActualDocumentId()
	 * @method \int requireDocumentId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog resetDocumentId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog unsetDocumentId()
	 * @method \int fillDocumentId()
	 * @method \string getDocumentUid()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog setDocumentUid(\string|\Bitrix\Main\DB\SqlExpression $documentUid)
	 * @method bool hasDocumentUid()
	 * @method bool isDocumentUidFilled()
	 * @method bool isDocumentUidChanged()
	 * @method \string remindActualDocumentUid()
	 * @method \string requireDocumentUid()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog resetDocumentUid()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog unsetDocumentUid()
	 * @method \string fillDocumentUid()
	 * @method null|\int getMemberId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog setMemberId(null|\int|\Bitrix\Main\DB\SqlExpression $memberId)
	 * @method bool hasMemberId()
	 * @method bool isMemberIdFilled()
	 * @method bool isMemberIdChanged()
	 * @method null|\int remindActualMemberId()
	 * @method null|\int requireMemberId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog resetMemberId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog unsetMemberId()
	 * @method null|\int fillMemberId()
	 * @method null|\string getMemberUid()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog setMemberUid(null|\string|\Bitrix\Main\DB\SqlExpression $memberUid)
	 * @method bool hasMemberUid()
	 * @method bool isMemberUidFilled()
	 * @method bool isMemberUidChanged()
	 * @method null|\string remindActualMemberUid()
	 * @method null|\string requireMemberUid()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog resetMemberUid()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog unsetMemberUid()
	 * @method null|\string fillMemberUid()
	 * @method \string getCode()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog resetCode()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog unsetCode()
	 * @method \string fillCode()
	 * @method null|\string getDescription()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog setDescription(null|\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method null|\string remindActualDescription()
	 * @method null|\string requireDescription()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog resetDescription()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog unsetDescription()
	 * @method null|\string fillDescription()
	 * @method null|\int getUserId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog setUserId(null|\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method null|\int remindActualUserId()
	 * @method null|\int requireUserId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog resetUserId()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog unsetUserId()
	 * @method null|\int fillUserId()
	 * @method null|\Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog setDateCreate(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog resetDateCreate()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog unsetDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime fillDateCreate()
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
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog reset($fieldName)
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\LegalLog\LegalLog wakeUp($data)
	 */
	class EO_LegalLog {
		/* @var \Bitrix\Sign\Internal\LegalLog\LegalLogTable */
		static public $dataClass = '\Bitrix\Sign\Internal\LegalLog\LegalLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal\LegalLog {
	/**
	 * LegalLogCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDocumentIdList()
	 * @method \int[] fillDocumentId()
	 * @method \string[] getDocumentUidList()
	 * @method \string[] fillDocumentUid()
	 * @method null|\int[] getMemberIdList()
	 * @method null|\int[] fillMemberId()
	 * @method null|\string[] getMemberUidList()
	 * @method null|\string[] fillMemberUid()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method null|\string[] getDescriptionList()
	 * @method null|\string[] fillDescription()
	 * @method null|\int[] getUserIdList()
	 * @method null|\int[] fillUserId()
	 * @method null|\Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\LegalLog\LegalLog $object)
	 * @method bool has(\Bitrix\Sign\Internal\LegalLog\LegalLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\LegalLog\LegalLog $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\LegalLog\LegalLogCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLogCollection merge(?\Bitrix\Sign\Internal\LegalLog\LegalLogCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_LegalLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\LegalLog\LegalLogTable */
		static public $dataClass = '\Bitrix\Sign\Internal\LegalLog\LegalLogTable';
	}
}
namespace Bitrix\Sign\Internal\LegalLog {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_LegalLog_Result exec()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog fetchObject()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLogCollection fetchCollection()
	 */
	class EO_LegalLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog fetchObject()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLogCollection fetchCollection()
	 */
	class EO_LegalLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLogCollection createCollection()
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLog wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\LegalLog\LegalLogCollection wakeUpCollection($rows)
	 */
	class EO_LegalLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\MemberNodeTable:sign/lib/internal/membernodetable.php */
namespace Bitrix\Sign\Internal {
	/**
	 * EO_MemberNode
	 * @see \Bitrix\Sign\Internal\MemberNodeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getMemberId()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode setMemberId(\int|\Bitrix\Main\DB\SqlExpression $memberId)
	 * @method bool hasMemberId()
	 * @method bool isMemberIdFilled()
	 * @method bool isMemberIdChanged()
	 * @method \int getNodeSyncId()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode setNodeSyncId(\int|\Bitrix\Main\DB\SqlExpression $nodeSyncId)
	 * @method bool hasNodeSyncId()
	 * @method bool isNodeSyncIdFilled()
	 * @method bool isNodeSyncIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode resetUserId()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getDocumentId()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode setDocumentId(\int|\Bitrix\Main\DB\SqlExpression $documentId)
	 * @method bool hasDocumentId()
	 * @method bool isDocumentIdFilled()
	 * @method bool isDocumentIdChanged()
	 * @method \int remindActualDocumentId()
	 * @method \int requireDocumentId()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode resetDocumentId()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode unsetDocumentId()
	 * @method \int fillDocumentId()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode resetDateCreate()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode unsetDateCreate()
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
	 * @method \Bitrix\Sign\Internal\EO_MemberNode set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\EO_MemberNode reset($fieldName)
	 * @method \Bitrix\Sign\Internal\EO_MemberNode unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\EO_MemberNode wakeUp($data)
	 */
	class EO_MemberNode {
		/* @var \Bitrix\Sign\Internal\MemberNodeTable */
		static public $dataClass = '\Bitrix\Sign\Internal\MemberNodeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * EO_MemberNode_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getMemberIdList()
	 * @method \int[] getNodeSyncIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getDocumentIdList()
	 * @method \int[] fillDocumentId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\EO_MemberNode $object)
	 * @method bool has(\Bitrix\Sign\Internal\EO_MemberNode $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\EO_MemberNode getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\EO_MemberNode[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\EO_MemberNode $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\EO_MemberNode_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\EO_MemberNode current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\EO_MemberNode_Collection merge(?\Bitrix\Sign\Internal\EO_MemberNode_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_MemberNode_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\MemberNodeTable */
		static public $dataClass = '\Bitrix\Sign\Internal\MemberNodeTable';
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_MemberNode_Result exec()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode fetchObject()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode_Collection fetchCollection()
	 */
	class EO_MemberNode_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\EO_MemberNode fetchObject()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode_Collection fetchCollection()
	 */
	class EO_MemberNode_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\EO_MemberNode createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\EO_MemberNode_Collection createCollection()
	 * @method \Bitrix\Sign\Internal\EO_MemberNode wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\EO_MemberNode_Collection wakeUpCollection($rows)
	 */
	class EO_MemberNode_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Model\SignDocumentGeneratorBlankTable:sign/lib/model/signdocumentgeneratorblanktable.php */
namespace Bitrix\Sign\Model {
	/**
	 * SignDocumentGeneratorBlank
	 * @see \Bitrix\Sign\Model\SignDocumentGeneratorBlankTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBlankId()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank setBlankId(\int|\Bitrix\Main\DB\SqlExpression $blankId)
	 * @method bool hasBlankId()
	 * @method bool isBlankIdFilled()
	 * @method bool isBlankIdChanged()
	 * @method \int remindActualBlankId()
	 * @method \int requireBlankId()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank resetBlankId()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank unsetBlankId()
	 * @method \int fillBlankId()
	 * @method \int getDocumentGeneratorTemplateId()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank setDocumentGeneratorTemplateId(\int|\Bitrix\Main\DB\SqlExpression $documentGeneratorTemplateId)
	 * @method bool hasDocumentGeneratorTemplateId()
	 * @method bool isDocumentGeneratorTemplateIdFilled()
	 * @method bool isDocumentGeneratorTemplateIdChanged()
	 * @method \int remindActualDocumentGeneratorTemplateId()
	 * @method \int requireDocumentGeneratorTemplateId()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank resetDocumentGeneratorTemplateId()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank unsetDocumentGeneratorTemplateId()
	 * @method \int fillDocumentGeneratorTemplateId()
	 * @method \string getInitiator()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank setInitiator(\string|\Bitrix\Main\DB\SqlExpression $initiator)
	 * @method bool hasInitiator()
	 * @method bool isInitiatorFilled()
	 * @method bool isInitiatorChanged()
	 * @method \string remindActualInitiator()
	 * @method \string requireInitiator()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank resetInitiator()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank unsetInitiator()
	 * @method \string fillInitiator()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank resetCreatedAt()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank unsetCreatedAt()
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
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank set($fieldName, $value)
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank reset($fieldName)
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Model\SignDocumentGeneratorBlank wakeUp($data)
	 */
	class EO_SignDocumentGeneratorBlank {
		/* @var \Bitrix\Sign\Model\SignDocumentGeneratorBlankTable */
		static public $dataClass = '\Bitrix\Sign\Model\SignDocumentGeneratorBlankTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Model {
	/**
	 * EO_SignDocumentGeneratorBlank_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBlankIdList()
	 * @method \int[] fillBlankId()
	 * @method \int[] getDocumentGeneratorTemplateIdList()
	 * @method \int[] fillDocumentGeneratorTemplateId()
	 * @method \string[] getInitiatorList()
	 * @method \string[] fillInitiator()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Model\SignDocumentGeneratorBlank $object)
	 * @method bool has(\Bitrix\Sign\Model\SignDocumentGeneratorBlank $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank getByPrimary($primary)
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank[] getAll()
	 * @method bool remove(\Bitrix\Sign\Model\SignDocumentGeneratorBlank $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Model\EO_SignDocumentGeneratorBlank_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Model\EO_SignDocumentGeneratorBlank_Collection merge(?\Bitrix\Sign\Model\EO_SignDocumentGeneratorBlank_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_SignDocumentGeneratorBlank_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Model\SignDocumentGeneratorBlankTable */
		static public $dataClass = '\Bitrix\Sign\Model\SignDocumentGeneratorBlankTable';
	}
}
namespace Bitrix\Sign\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SignDocumentGeneratorBlank_Result exec()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank fetchObject()
	 * @method \Bitrix\Sign\Model\EO_SignDocumentGeneratorBlank_Collection fetchCollection()
	 */
	class EO_SignDocumentGeneratorBlank_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank fetchObject()
	 * @method \Bitrix\Sign\Model\EO_SignDocumentGeneratorBlank_Collection fetchCollection()
	 */
	class EO_SignDocumentGeneratorBlank_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Model\EO_SignDocumentGeneratorBlank_Collection createCollection()
	 * @method \Bitrix\Sign\Model\SignDocumentGeneratorBlank wakeUpObject($row)
	 * @method \Bitrix\Sign\Model\EO_SignDocumentGeneratorBlank_Collection wakeUpCollection($rows)
	 */
	class EO_SignDocumentGeneratorBlank_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Sign\Model\DocumentRequiredFieldTable:sign/lib/model/documentrequiredfieldtable.php */
namespace Bitrix\Sign\Model {
	/**
	 * EO_DocumentRequiredField
	 * @see \Bitrix\Sign\Model\DocumentRequiredFieldTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDocumentId()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField setDocumentId(\int|\Bitrix\Main\DB\SqlExpression $documentId)
	 * @method bool hasDocumentId()
	 * @method bool isDocumentIdFilled()
	 * @method bool isDocumentIdChanged()
	 * @method \int remindActualDocumentId()
	 * @method \int requireDocumentId()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField resetDocumentId()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField unsetDocumentId()
	 * @method \int fillDocumentId()
	 * @method \string getType()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField resetType()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField unsetType()
	 * @method \string fillType()
	 * @method \int getRole()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField setRole(\int|\Bitrix\Main\DB\SqlExpression $role)
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \int remindActualRole()
	 * @method \int requireRole()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField resetRole()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField unsetRole()
	 * @method \int fillRole()
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
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField set($fieldName, $value)
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField reset($fieldName)
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Model\EO_DocumentRequiredField wakeUp($data)
	 */
	class EO_DocumentRequiredField {
		/* @var \Bitrix\Sign\Model\DocumentRequiredFieldTable */
		static public $dataClass = '\Bitrix\Sign\Model\DocumentRequiredFieldTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Model {
	/**
	 * EO_DocumentRequiredField_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDocumentIdList()
	 * @method \int[] fillDocumentId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getRoleList()
	 * @method \int[] fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Model\EO_DocumentRequiredField $object)
	 * @method bool has(\Bitrix\Sign\Model\EO_DocumentRequiredField $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField getByPrimary($primary)
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField[] getAll()
	 * @method bool remove(\Bitrix\Sign\Model\EO_DocumentRequiredField $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Model\EO_DocumentRequiredField_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField_Collection merge(?\Bitrix\Sign\Model\EO_DocumentRequiredField_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_DocumentRequiredField_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Model\DocumentRequiredFieldTable */
		static public $dataClass = '\Bitrix\Sign\Model\DocumentRequiredFieldTable';
	}
}
namespace Bitrix\Sign\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DocumentRequiredField_Result exec()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField fetchObject()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField_Collection fetchCollection()
	 */
	class EO_DocumentRequiredField_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField fetchObject()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField_Collection fetchCollection()
	 */
	class EO_DocumentRequiredField_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField_Collection createCollection()
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField wakeUpObject($row)
	 * @method \Bitrix\Sign\Model\EO_DocumentRequiredField_Collection wakeUpCollection($rows)
	 */
	class EO_DocumentRequiredField_Entity extends \Bitrix\Main\ORM\Entity {}
}