<?php

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
	 * @method null|\int[] getCreatedFromDocumentIdList()
	 * @method null|\int[] fillCreatedFromDocumentId()
	 * @method \int[] getInitiatedByTypeList()
	 * @method \int[] fillInitiatedByType()
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
/* ORMENTITYANNOTATION:Bitrix\Sign\Internal\MemberTable:sign/lib/internal/membertable.php */
namespace Bitrix\Sign\Internal {
	/**
	 * Member
	 * @see \Bitrix\Sign\Internal\MemberTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Sign\Internal\Member setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDocumentId()
	 * @method \Bitrix\Sign\Internal\Member setDocumentId(\int|\Bitrix\Main\DB\SqlExpression $documentId)
	 * @method bool hasDocumentId()
	 * @method bool isDocumentIdFilled()
	 * @method bool isDocumentIdChanged()
	 * @method \int remindActualDocumentId()
	 * @method \int requireDocumentId()
	 * @method \Bitrix\Sign\Internal\Member resetDocumentId()
	 * @method \Bitrix\Sign\Internal\Member unsetDocumentId()
	 * @method \int fillDocumentId()
	 * @method \Bitrix\Sign\Internal\Document getDocument()
	 * @method \Bitrix\Sign\Internal\Document remindActualDocument()
	 * @method \Bitrix\Sign\Internal\Document requireDocument()
	 * @method \Bitrix\Sign\Internal\Member setDocument(\Bitrix\Sign\Internal\Document $object)
	 * @method \Bitrix\Sign\Internal\Member resetDocument()
	 * @method \Bitrix\Sign\Internal\Member unsetDocument()
	 * @method bool hasDocument()
	 * @method bool isDocumentFilled()
	 * @method bool isDocumentChanged()
	 * @method \Bitrix\Sign\Internal\Document fillDocument()
	 * @method \int getContactId()
	 * @method \Bitrix\Sign\Internal\Member setContactId(\int|\Bitrix\Main\DB\SqlExpression $contactId)
	 * @method bool hasContactId()
	 * @method bool isContactIdFilled()
	 * @method bool isContactIdChanged()
	 * @method \int remindActualContactId()
	 * @method \int requireContactId()
	 * @method \Bitrix\Sign\Internal\Member resetContactId()
	 * @method \Bitrix\Sign\Internal\Member unsetContactId()
	 * @method \int fillContactId()
	 * @method \int getPart()
	 * @method \Bitrix\Sign\Internal\Member setPart(\int|\Bitrix\Main\DB\SqlExpression $part)
	 * @method bool hasPart()
	 * @method bool isPartFilled()
	 * @method bool isPartChanged()
	 * @method \int remindActualPart()
	 * @method \int requirePart()
	 * @method \Bitrix\Sign\Internal\Member resetPart()
	 * @method \Bitrix\Sign\Internal\Member unsetPart()
	 * @method \int fillPart()
	 * @method \string getHash()
	 * @method \Bitrix\Sign\Internal\Member setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\Sign\Internal\Member resetHash()
	 * @method \Bitrix\Sign\Internal\Member unsetHash()
	 * @method \string fillHash()
	 * @method \string getSigned()
	 * @method \Bitrix\Sign\Internal\Member setSigned(\string|\Bitrix\Main\DB\SqlExpression $signed)
	 * @method bool hasSigned()
	 * @method bool isSignedFilled()
	 * @method bool isSignedChanged()
	 * @method \string remindActualSigned()
	 * @method \string requireSigned()
	 * @method \Bitrix\Sign\Internal\Member resetSigned()
	 * @method \Bitrix\Sign\Internal\Member unsetSigned()
	 * @method \string fillSigned()
	 * @method \string getVerified()
	 * @method \Bitrix\Sign\Internal\Member setVerified(\string|\Bitrix\Main\DB\SqlExpression $verified)
	 * @method bool hasVerified()
	 * @method bool isVerifiedFilled()
	 * @method bool isVerifiedChanged()
	 * @method \string remindActualVerified()
	 * @method \string requireVerified()
	 * @method \Bitrix\Sign\Internal\Member resetVerified()
	 * @method \Bitrix\Sign\Internal\Member unsetVerified()
	 * @method \string fillVerified()
	 * @method \string getMute()
	 * @method \Bitrix\Sign\Internal\Member setMute(\string|\Bitrix\Main\DB\SqlExpression $mute)
	 * @method bool hasMute()
	 * @method bool isMuteFilled()
	 * @method bool isMuteChanged()
	 * @method \string remindActualMute()
	 * @method \string requireMute()
	 * @method \Bitrix\Sign\Internal\Member resetMute()
	 * @method \Bitrix\Sign\Internal\Member unsetMute()
	 * @method \string fillMute()
	 * @method \string getCommunicationType()
	 * @method \Bitrix\Sign\Internal\Member setCommunicationType(\string|\Bitrix\Main\DB\SqlExpression $communicationType)
	 * @method bool hasCommunicationType()
	 * @method bool isCommunicationTypeFilled()
	 * @method bool isCommunicationTypeChanged()
	 * @method \string remindActualCommunicationType()
	 * @method \string requireCommunicationType()
	 * @method \Bitrix\Sign\Internal\Member resetCommunicationType()
	 * @method \Bitrix\Sign\Internal\Member unsetCommunicationType()
	 * @method \string fillCommunicationType()
	 * @method \string getCommunicationValue()
	 * @method \Bitrix\Sign\Internal\Member setCommunicationValue(\string|\Bitrix\Main\DB\SqlExpression $communicationValue)
	 * @method bool hasCommunicationValue()
	 * @method bool isCommunicationValueFilled()
	 * @method bool isCommunicationValueChanged()
	 * @method \string remindActualCommunicationValue()
	 * @method \string requireCommunicationValue()
	 * @method \Bitrix\Sign\Internal\Member resetCommunicationValue()
	 * @method \Bitrix\Sign\Internal\Member unsetCommunicationValue()
	 * @method \string fillCommunicationValue()
	 * @method array getUserData()
	 * @method \Bitrix\Sign\Internal\Member setUserData(array|\Bitrix\Main\DB\SqlExpression $userData)
	 * @method bool hasUserData()
	 * @method bool isUserDataFilled()
	 * @method bool isUserDataChanged()
	 * @method array remindActualUserData()
	 * @method array requireUserData()
	 * @method \Bitrix\Sign\Internal\Member resetUserData()
	 * @method \Bitrix\Sign\Internal\Member unsetUserData()
	 * @method array fillUserData()
	 * @method array getMeta()
	 * @method \Bitrix\Sign\Internal\Member setMeta(array|\Bitrix\Main\DB\SqlExpression $meta)
	 * @method bool hasMeta()
	 * @method bool isMetaFilled()
	 * @method bool isMetaChanged()
	 * @method array remindActualMeta()
	 * @method array requireMeta()
	 * @method \Bitrix\Sign\Internal\Member resetMeta()
	 * @method \Bitrix\Sign\Internal\Member unsetMeta()
	 * @method array fillMeta()
	 * @method null|\int getSignatureFileId()
	 * @method \Bitrix\Sign\Internal\Member setSignatureFileId(null|\int|\Bitrix\Main\DB\SqlExpression $signatureFileId)
	 * @method bool hasSignatureFileId()
	 * @method bool isSignatureFileIdFilled()
	 * @method bool isSignatureFileIdChanged()
	 * @method null|\int remindActualSignatureFileId()
	 * @method null|\int requireSignatureFileId()
	 * @method \Bitrix\Sign\Internal\Member resetSignatureFileId()
	 * @method \Bitrix\Sign\Internal\Member unsetSignatureFileId()
	 * @method null|\int fillSignatureFileId()
	 * @method null|\int getStampFileId()
	 * @method \Bitrix\Sign\Internal\Member setStampFileId(null|\int|\Bitrix\Main\DB\SqlExpression $stampFileId)
	 * @method bool hasStampFileId()
	 * @method bool isStampFileIdFilled()
	 * @method bool isStampFileIdChanged()
	 * @method null|\int remindActualStampFileId()
	 * @method null|\int requireStampFileId()
	 * @method \Bitrix\Sign\Internal\Member resetStampFileId()
	 * @method \Bitrix\Sign\Internal\Member unsetStampFileId()
	 * @method null|\int fillStampFileId()
	 * @method \int getCreatedById()
	 * @method \Bitrix\Sign\Internal\Member setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\Sign\Internal\Member resetCreatedById()
	 * @method \Bitrix\Sign\Internal\Member unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method \int getModifiedById()
	 * @method \Bitrix\Sign\Internal\Member setModifiedById(\int|\Bitrix\Main\DB\SqlExpression $modifiedById)
	 * @method bool hasModifiedById()
	 * @method bool isModifiedByIdFilled()
	 * @method bool isModifiedByIdChanged()
	 * @method \int remindActualModifiedById()
	 * @method \int requireModifiedById()
	 * @method \Bitrix\Sign\Internal\Member resetModifiedById()
	 * @method \Bitrix\Sign\Internal\Member unsetModifiedById()
	 * @method \int fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Sign\Internal\Member setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Sign\Internal\Member resetDateCreate()
	 * @method \Bitrix\Sign\Internal\Member unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\Sign\Internal\Member setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\Sign\Internal\Member resetDateModify()
	 * @method \Bitrix\Sign\Internal\Member unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
	 * @method \Bitrix\Main\Type\DateTime getDateSign()
	 * @method \Bitrix\Sign\Internal\Member setDateSign(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateSign)
	 * @method bool hasDateSign()
	 * @method bool isDateSignFilled()
	 * @method bool isDateSignChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateSign()
	 * @method \Bitrix\Main\Type\DateTime requireDateSign()
	 * @method \Bitrix\Sign\Internal\Member resetDateSign()
	 * @method \Bitrix\Sign\Internal\Member unsetDateSign()
	 * @method \Bitrix\Main\Type\DateTime fillDateSign()
	 * @method \Bitrix\Main\Type\DateTime getDateDocDownload()
	 * @method \Bitrix\Sign\Internal\Member setDateDocDownload(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateDocDownload)
	 * @method bool hasDateDocDownload()
	 * @method bool isDateDocDownloadFilled()
	 * @method bool isDateDocDownloadChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateDocDownload()
	 * @method \Bitrix\Main\Type\DateTime requireDateDocDownload()
	 * @method \Bitrix\Sign\Internal\Member resetDateDocDownload()
	 * @method \Bitrix\Sign\Internal\Member unsetDateDocDownload()
	 * @method \Bitrix\Main\Type\DateTime fillDateDocDownload()
	 * @method \Bitrix\Main\Type\DateTime getDateDocVerify()
	 * @method \Bitrix\Sign\Internal\Member setDateDocVerify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateDocVerify)
	 * @method bool hasDateDocVerify()
	 * @method bool isDateDocVerifyFilled()
	 * @method bool isDateDocVerifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateDocVerify()
	 * @method \Bitrix\Main\Type\DateTime requireDateDocVerify()
	 * @method \Bitrix\Sign\Internal\Member resetDateDocVerify()
	 * @method \Bitrix\Sign\Internal\Member unsetDateDocVerify()
	 * @method \Bitrix\Main\Type\DateTime fillDateDocVerify()
	 * @method \string getIp()
	 * @method \Bitrix\Sign\Internal\Member setIp(\string|\Bitrix\Main\DB\SqlExpression $ip)
	 * @method bool hasIp()
	 * @method bool isIpFilled()
	 * @method bool isIpChanged()
	 * @method \string remindActualIp()
	 * @method \string requireIp()
	 * @method \Bitrix\Sign\Internal\Member resetIp()
	 * @method \Bitrix\Sign\Internal\Member unsetIp()
	 * @method \string fillIp()
	 * @method \int getTimeZoneOffset()
	 * @method \Bitrix\Sign\Internal\Member setTimeZoneOffset(\int|\Bitrix\Main\DB\SqlExpression $timeZoneOffset)
	 * @method bool hasTimeZoneOffset()
	 * @method bool isTimeZoneOffsetFilled()
	 * @method bool isTimeZoneOffsetChanged()
	 * @method \int remindActualTimeZoneOffset()
	 * @method \int requireTimeZoneOffset()
	 * @method \Bitrix\Sign\Internal\Member resetTimeZoneOffset()
	 * @method \Bitrix\Sign\Internal\Member unsetTimeZoneOffset()
	 * @method \int fillTimeZoneOffset()
	 * @method \int getEntityId()
	 * @method \Bitrix\Sign\Internal\Member setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Sign\Internal\Member resetEntityId()
	 * @method \Bitrix\Sign\Internal\Member unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Sign\Internal\Member setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Sign\Internal\Member resetEntityType()
	 * @method \Bitrix\Sign\Internal\Member unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \int getPresetId()
	 * @method \Bitrix\Sign\Internal\Member setPresetId(\int|\Bitrix\Main\DB\SqlExpression $presetId)
	 * @method bool hasPresetId()
	 * @method bool isPresetIdFilled()
	 * @method bool isPresetIdChanged()
	 * @method \int remindActualPresetId()
	 * @method \int requirePresetId()
	 * @method \Bitrix\Sign\Internal\Member resetPresetId()
	 * @method \Bitrix\Sign\Internal\Member unsetPresetId()
	 * @method \int fillPresetId()
	 * @method \string getUid()
	 * @method \Bitrix\Sign\Internal\Member setUid(\string|\Bitrix\Main\DB\SqlExpression $uid)
	 * @method bool hasUid()
	 * @method bool isUidFilled()
	 * @method bool isUidChanged()
	 * @method \string remindActualUid()
	 * @method \string requireUid()
	 * @method \Bitrix\Sign\Internal\Member resetUid()
	 * @method \Bitrix\Sign\Internal\Member unsetUid()
	 * @method \string fillUid()
	 * @method null|\int getRole()
	 * @method \Bitrix\Sign\Internal\Member setRole(null|\int|\Bitrix\Main\DB\SqlExpression $role)
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method null|\int remindActualRole()
	 * @method null|\int requireRole()
	 * @method \Bitrix\Sign\Internal\Member resetRole()
	 * @method \Bitrix\Sign\Internal\Member unsetRole()
	 * @method null|\int fillRole()
	 * @method null|\int getReminderType()
	 * @method \Bitrix\Sign\Internal\Member setReminderType(null|\int|\Bitrix\Main\DB\SqlExpression $reminderType)
	 * @method bool hasReminderType()
	 * @method bool isReminderTypeFilled()
	 * @method bool isReminderTypeChanged()
	 * @method null|\int remindActualReminderType()
	 * @method null|\int requireReminderType()
	 * @method \Bitrix\Sign\Internal\Member resetReminderType()
	 * @method \Bitrix\Sign\Internal\Member unsetReminderType()
	 * @method null|\int fillReminderType()
	 * @method null|\Bitrix\Main\Type\DateTime getReminderLastSendDate()
	 * @method \Bitrix\Sign\Internal\Member setReminderLastSendDate(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $reminderLastSendDate)
	 * @method bool hasReminderLastSendDate()
	 * @method bool isReminderLastSendDateFilled()
	 * @method bool isReminderLastSendDateChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualReminderLastSendDate()
	 * @method null|\Bitrix\Main\Type\DateTime requireReminderLastSendDate()
	 * @method \Bitrix\Sign\Internal\Member resetReminderLastSendDate()
	 * @method \Bitrix\Sign\Internal\Member unsetReminderLastSendDate()
	 * @method null|\Bitrix\Main\Type\DateTime fillReminderLastSendDate()
	 * @method null|\Bitrix\Main\Type\DateTime getReminderPlannedNextSendDate()
	 * @method \Bitrix\Sign\Internal\Member setReminderPlannedNextSendDate(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $reminderPlannedNextSendDate)
	 * @method bool hasReminderPlannedNextSendDate()
	 * @method bool isReminderPlannedNextSendDateFilled()
	 * @method bool isReminderPlannedNextSendDateChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualReminderPlannedNextSendDate()
	 * @method null|\Bitrix\Main\Type\DateTime requireReminderPlannedNextSendDate()
	 * @method \Bitrix\Sign\Internal\Member resetReminderPlannedNextSendDate()
	 * @method \Bitrix\Sign\Internal\Member unsetReminderPlannedNextSendDate()
	 * @method null|\Bitrix\Main\Type\DateTime fillReminderPlannedNextSendDate()
	 * @method \boolean getReminderCompleted()
	 * @method \Bitrix\Sign\Internal\Member setReminderCompleted(\boolean|\Bitrix\Main\DB\SqlExpression $reminderCompleted)
	 * @method bool hasReminderCompleted()
	 * @method bool isReminderCompletedFilled()
	 * @method bool isReminderCompletedChanged()
	 * @method \boolean remindActualReminderCompleted()
	 * @method \boolean requireReminderCompleted()
	 * @method \Bitrix\Sign\Internal\Member resetReminderCompleted()
	 * @method \Bitrix\Sign\Internal\Member unsetReminderCompleted()
	 * @method \boolean fillReminderCompleted()
	 * @method null|\Bitrix\Main\Type\DateTime getReminderStartDate()
	 * @method \Bitrix\Sign\Internal\Member setReminderStartDate(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $reminderStartDate)
	 * @method bool hasReminderStartDate()
	 * @method bool isReminderStartDateFilled()
	 * @method bool isReminderStartDateChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualReminderStartDate()
	 * @method null|\Bitrix\Main\Type\DateTime requireReminderStartDate()
	 * @method \Bitrix\Sign\Internal\Member resetReminderStartDate()
	 * @method \Bitrix\Sign\Internal\Member unsetReminderStartDate()
	 * @method null|\Bitrix\Main\Type\DateTime fillReminderStartDate()
	 * @method null|\int getConfigured()
	 * @method \Bitrix\Sign\Internal\Member setConfigured(null|\int|\Bitrix\Main\DB\SqlExpression $configured)
	 * @method bool hasConfigured()
	 * @method bool isConfiguredFilled()
	 * @method bool isConfiguredChanged()
	 * @method null|\int remindActualConfigured()
	 * @method null|\int requireConfigured()
	 * @method \Bitrix\Sign\Internal\Member resetConfigured()
	 * @method \Bitrix\Sign\Internal\Member unsetConfigured()
	 * @method null|\int fillConfigured()
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
	 * @method \Bitrix\Sign\Internal\Member set($fieldName, $value)
	 * @method \Bitrix\Sign\Internal\Member reset($fieldName)
	 * @method \Bitrix\Sign\Internal\Member unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Sign\Internal\Member wakeUp($data)
	 */
	class EO_Member {
		/* @var \Bitrix\Sign\Internal\MemberTable */
		static public $dataClass = '\Bitrix\Sign\Internal\MemberTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * MemberCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDocumentIdList()
	 * @method \int[] fillDocumentId()
	 * @method \Bitrix\Sign\Internal\Document[] getDocumentList()
	 * @method \Bitrix\Sign\Internal\MemberCollection getDocumentCollection()
	 * @method \Bitrix\Sign\Internal\DocumentCollection fillDocument()
	 * @method \int[] getContactIdList()
	 * @method \int[] fillContactId()
	 * @method \int[] getPartList()
	 * @method \int[] fillPart()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \string[] getSignedList()
	 * @method \string[] fillSigned()
	 * @method \string[] getVerifiedList()
	 * @method \string[] fillVerified()
	 * @method \string[] getMuteList()
	 * @method \string[] fillMute()
	 * @method \string[] getCommunicationTypeList()
	 * @method \string[] fillCommunicationType()
	 * @method \string[] getCommunicationValueList()
	 * @method \string[] fillCommunicationValue()
	 * @method array[] getUserDataList()
	 * @method array[] fillUserData()
	 * @method array[] getMetaList()
	 * @method array[] fillMeta()
	 * @method null|\int[] getSignatureFileIdList()
	 * @method null|\int[] fillSignatureFileId()
	 * @method null|\int[] getStampFileIdList()
	 * @method null|\int[] fillStampFileId()
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
	 * @method \Bitrix\Main\Type\DateTime[] getDateDocDownloadList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateDocDownload()
	 * @method \Bitrix\Main\Type\DateTime[] getDateDocVerifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateDocVerify()
	 * @method \string[] getIpList()
	 * @method \string[] fillIp()
	 * @method \int[] getTimeZoneOffsetList()
	 * @method \int[] fillTimeZoneOffset()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \int[] getPresetIdList()
	 * @method \int[] fillPresetId()
	 * @method \string[] getUidList()
	 * @method \string[] fillUid()
	 * @method null|\int[] getRoleList()
	 * @method null|\int[] fillRole()
	 * @method null|\int[] getReminderTypeList()
	 * @method null|\int[] fillReminderType()
	 * @method null|\Bitrix\Main\Type\DateTime[] getReminderLastSendDateList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillReminderLastSendDate()
	 * @method null|\Bitrix\Main\Type\DateTime[] getReminderPlannedNextSendDateList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillReminderPlannedNextSendDate()
	 * @method \boolean[] getReminderCompletedList()
	 * @method \boolean[] fillReminderCompleted()
	 * @method null|\Bitrix\Main\Type\DateTime[] getReminderStartDateList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillReminderStartDate()
	 * @method null|\int[] getConfiguredList()
	 * @method null|\int[] fillConfigured()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Sign\Internal\Member $object)
	 * @method bool has(\Bitrix\Sign\Internal\Member $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Member getByPrimary($primary)
	 * @method \Bitrix\Sign\Internal\Member[] getAll()
	 * @method bool remove(\Bitrix\Sign\Internal\Member $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Sign\Internal\MemberCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Sign\Internal\Member current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Sign\Internal\MemberCollection merge(?\Bitrix\Sign\Internal\MemberCollection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Member_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Sign\Internal\MemberTable */
		static public $dataClass = '\Bitrix\Sign\Internal\MemberTable';
	}
}
namespace Bitrix\Sign\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Member_Result exec()
	 * @method \Bitrix\Sign\Internal\Member fetchObject()
	 * @method \Bitrix\Sign\Internal\MemberCollection fetchCollection()
	 */
	class EO_Member_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Sign\Internal\Member fetchObject()
	 * @method \Bitrix\Sign\Internal\MemberCollection fetchCollection()
	 */
	class EO_Member_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Sign\Internal\Member createObject($setDefaultValues = true)
	 * @method \Bitrix\Sign\Internal\MemberCollection createCollection()
	 * @method \Bitrix\Sign\Internal\Member wakeUpObject($row)
	 * @method \Bitrix\Sign\Internal\MemberCollection wakeUpCollection($rows)
	 */
	class EO_Member_Entity extends \Bitrix\Main\ORM\Entity {}
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
/* ORMENTITYANNOTATION:Bitrix\Sign\Model\SignDocumentGeneratorBlankTable:sign/lib/Model/SignDocumentGeneratorBlankTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\Sign\Model\DocumentRequiredFieldTable:sign/lib/Model/DocumentRequiredFieldTable.php */
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
/* ORMENTITYANNOTATION:Bitrix\Sign\Access\Permission\PermissionTable:sign/lib/Access/Permission/PermissionTable.php */
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