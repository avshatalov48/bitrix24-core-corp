<?php

/* ORMENTITYANNOTATION:Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatTable:biconnector/lib/ExternalSource/Internal/ExternalDatasetFieldFormatTable.php */
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalDatasetFieldFormat
	 * @see \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat setDatasetId(\int|\Bitrix\Main\DB\SqlExpression $datasetId)
	 * @method bool hasDatasetId()
	 * @method bool isDatasetIdFilled()
	 * @method bool isDatasetIdChanged()
	 * @method \int remindActualDatasetId()
	 * @method \int requireDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat resetDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat unsetDatasetId()
	 * @method \int fillDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset getDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset remindActualDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset requireDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat setDataset(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset $object)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat resetDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat unsetDataset()
	 * @method bool hasDataset()
	 * @method bool isDatasetFilled()
	 * @method bool isDatasetChanged()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset fillDataset()
	 * @method \string getType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat resetType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat unsetType()
	 * @method \string fillType()
	 * @method \string getFormat()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat setFormat(\string|\Bitrix\Main\DB\SqlExpression $format)
	 * @method bool hasFormat()
	 * @method bool isFormatFilled()
	 * @method bool isFormatChanged()
	 * @method \string remindActualFormat()
	 * @method \string requireFormat()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat resetFormat()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat unsetFormat()
	 * @method \string fillFormat()
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
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat set($fieldName, $value)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat reset($fieldName)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat wakeUp($data)
	 */
	class EO_ExternalDatasetFieldFormat {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalDatasetFieldFormatCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDatasetIdList()
	 * @method \int[] fillDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset[] getDatasetList()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection getDatasetCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection fillDataset()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getFormatList()
	 * @method \string[] fillFormat()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat $object)
	 * @method bool has(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat getByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection merge(?\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ExternalDatasetFieldFormat_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatTable';
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExternalDatasetFieldFormat_Result exec()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection fetchCollection()
	 */
	class EO_ExternalDatasetFieldFormat_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection fetchCollection()
	 */
	class EO_ExternalDatasetFieldFormat_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection createCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat wakeUpObject($row)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection wakeUpCollection($rows)
	 */
	class EO_ExternalDatasetFieldFormat_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationTable:biconnector/lib/ExternalSource/Internal/ExternalSourceDatasetRelationTable.php */
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalSourceDatasetRelation
	 * @see \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation setSourceId(\int|\Bitrix\Main\DB\SqlExpression $sourceId)
	 * @method bool hasSourceId()
	 * @method bool isSourceIdFilled()
	 * @method bool isSourceIdChanged()
	 * @method \int remindActualSourceId()
	 * @method \int requireSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation resetSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation unsetSourceId()
	 * @method \int fillSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource getSource()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource remindActualSource()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource requireSource()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation setSource(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSource $object)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation resetSource()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation unsetSource()
	 * @method bool hasSource()
	 * @method bool isSourceFilled()
	 * @method bool isSourceChanged()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource fillSource()
	 * @method \int getDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation setDatasetId(\int|\Bitrix\Main\DB\SqlExpression $datasetId)
	 * @method bool hasDatasetId()
	 * @method bool isDatasetIdFilled()
	 * @method bool isDatasetIdChanged()
	 * @method \int remindActualDatasetId()
	 * @method \int requireDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation resetDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation unsetDatasetId()
	 * @method \int fillDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset getDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset remindActualDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset requireDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation setDataset(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset $object)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation resetDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation unsetDataset()
	 * @method bool hasDataset()
	 * @method bool isDatasetFilled()
	 * @method bool isDatasetChanged()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset fillDataset()
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
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation set($fieldName, $value)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation reset($fieldName)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation wakeUp($data)
	 */
	class EO_ExternalSourceDatasetRelation {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalSourceDatasetRelationCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getSourceIdList()
	 * @method \int[] fillSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource[] getSourceList()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection getSourceCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection fillSource()
	 * @method \int[] getDatasetIdList()
	 * @method \int[] fillDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset[] getDatasetList()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection getDatasetCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection fillDataset()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation $object)
	 * @method bool has(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation getByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection merge(?\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ExternalSourceDatasetRelation_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationTable';
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExternalSourceDatasetRelation_Result exec()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection fetchCollection()
	 */
	class EO_ExternalSourceDatasetRelation_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection fetchCollection()
	 */
	class EO_ExternalSourceDatasetRelation_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection createCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation wakeUpObject($row)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection wakeUpCollection($rows)
	 */
	class EO_ExternalSourceDatasetRelation_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable:biconnector/lib/ExternalSource/Internal/ExternalDatasetFieldTable.php */
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalDatasetField
	 * @see \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField setDatasetId(\int|\Bitrix\Main\DB\SqlExpression $datasetId)
	 * @method bool hasDatasetId()
	 * @method bool isDatasetIdFilled()
	 * @method bool isDatasetIdChanged()
	 * @method \int remindActualDatasetId()
	 * @method \int requireDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField resetDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField unsetDatasetId()
	 * @method \int fillDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset getDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset remindActualDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset requireDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField setDataset(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset $object)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField resetDataset()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField unsetDataset()
	 * @method bool hasDataset()
	 * @method bool isDatasetFilled()
	 * @method bool isDatasetChanged()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset fillDataset()
	 * @method \string getType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField resetType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField unsetType()
	 * @method \string fillType()
	 * @method \string getName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField resetName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField unsetName()
	 * @method \string fillName()
	 * @method \string getExternalCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField setExternalCode(\string|\Bitrix\Main\DB\SqlExpression $externalCode)
	 * @method bool hasExternalCode()
	 * @method bool isExternalCodeFilled()
	 * @method bool isExternalCodeChanged()
	 * @method \string remindActualExternalCode()
	 * @method \string requireExternalCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField resetExternalCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField unsetExternalCode()
	 * @method \string fillExternalCode()
	 * @method \boolean getVisible()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField setVisible(\boolean|\Bitrix\Main\DB\SqlExpression $visible)
	 * @method bool hasVisible()
	 * @method bool isVisibleFilled()
	 * @method bool isVisibleChanged()
	 * @method \boolean remindActualVisible()
	 * @method \boolean requireVisible()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField resetVisible()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField unsetVisible()
	 * @method \boolean fillVisible()
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
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField set($fieldName, $value)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField reset($fieldName)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField wakeUp($data)
	 */
	class EO_ExternalDatasetField {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalDatasetFieldCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDatasetIdList()
	 * @method \int[] fillDatasetId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset[] getDatasetList()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection getDatasetCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection fillDataset()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getExternalCodeList()
	 * @method \string[] fillExternalCode()
	 * @method \boolean[] getVisibleList()
	 * @method \boolean[] fillVisible()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField $object)
	 * @method bool has(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField getByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection merge(?\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ExternalDatasetField_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable';
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExternalDatasetField_Result exec()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection fetchCollection()
	 */
	class EO_ExternalDatasetField_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection fetchCollection()
	 */
	class EO_ExternalDatasetField_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection createCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField wakeUpObject($row)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection wakeUpCollection($rows)
	 */
	class EO_ExternalDatasetField_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable:biconnector/lib/ExternalSource/Internal/ExternalDatasetTable.php */
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalDataset
	 * @see \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetType()
	 * @method \string fillType()
	 * @method \string getName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetName()
	 * @method \string fillName()
	 * @method \string getDescription()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetDescription()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetDescription()
	 * @method \string fillDescription()
	 * @method \string getExternalCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setExternalCode(\string|\Bitrix\Main\DB\SqlExpression $externalCode)
	 * @method bool hasExternalCode()
	 * @method bool isExternalCodeFilled()
	 * @method bool isExternalCodeChanged()
	 * @method \string remindActualExternalCode()
	 * @method \string requireExternalCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetExternalCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetExternalCode()
	 * @method \string fillExternalCode()
	 * @method \string getExternalName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setExternalName(\string|\Bitrix\Main\DB\SqlExpression $externalName)
	 * @method bool hasExternalName()
	 * @method bool isExternalNameFilled()
	 * @method bool isExternalNameChanged()
	 * @method \string remindActualExternalName()
	 * @method \string requireExternalName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetExternalName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetExternalName()
	 * @method \string fillExternalName()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetDateCreate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateUpdate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setDateUpdate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateUpdate)
	 * @method bool hasDateUpdate()
	 * @method bool isDateUpdateFilled()
	 * @method bool isDateUpdateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateUpdate()
	 * @method \Bitrix\Main\Type\DateTime requireDateUpdate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetDateUpdate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetDateUpdate()
	 * @method \Bitrix\Main\Type\DateTime fillDateUpdate()
	 * @method \int getCreatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetCreatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method \int getUpdatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setUpdatedById(\int|\Bitrix\Main\DB\SqlExpression $updatedById)
	 * @method bool hasUpdatedById()
	 * @method bool isUpdatedByIdFilled()
	 * @method bool isUpdatedByIdChanged()
	 * @method \int remindActualUpdatedById()
	 * @method \int requireUpdatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetUpdatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetUpdatedById()
	 * @method \int fillUpdatedById()
	 * @method \int getExternalId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset setExternalId(\int|\Bitrix\Main\DB\SqlExpression $externalId)
	 * @method bool hasExternalId()
	 * @method bool isExternalIdFilled()
	 * @method bool isExternalIdChanged()
	 * @method \int remindActualExternalId()
	 * @method \int requireExternalId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset resetExternalId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unsetExternalId()
	 * @method \int fillExternalId()
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
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset set($fieldName, $value)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset reset($fieldName)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset wakeUp($data)
	 */
	class EO_ExternalDataset {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalDatasetCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \string[] getExternalCodeList()
	 * @method \string[] fillExternalCode()
	 * @method \string[] getExternalNameList()
	 * @method \string[] fillExternalName()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateUpdateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateUpdate()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 * @method \int[] getUpdatedByIdList()
	 * @method \int[] fillUpdatedById()
	 * @method \int[] getExternalIdList()
	 * @method \int[] fillExternalId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset $object)
	 * @method bool has(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset getByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection merge(?\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ExternalDataset_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable';
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExternalDataset_Result exec()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection fetchCollection()
	 */
	class EO_ExternalDataset_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection fetchCollection()
	 */
	class EO_ExternalDataset_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection createCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset wakeUpObject($row)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection wakeUpCollection($rows)
	 */
	class EO_ExternalDataset_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable:biconnector/lib/ExternalSource/Internal/ExternalSourceTable.php */
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalSource
	 * @see \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource resetType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unsetType()
	 * @method \string fillType()
	 * @method \string getCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource resetCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unsetCode()
	 * @method \string fillCode()
	 * @method \string getTitle()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource resetTitle()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getDescription()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource resetDescription()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unsetDescription()
	 * @method \string fillDescription()
	 * @method \boolean getActive()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource resetActive()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unsetActive()
	 * @method \boolean fillActive()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource resetDateCreate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateUpdate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setDateUpdate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateUpdate)
	 * @method bool hasDateUpdate()
	 * @method bool isDateUpdateFilled()
	 * @method bool isDateUpdateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateUpdate()
	 * @method \Bitrix\Main\Type\DateTime requireDateUpdate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource resetDateUpdate()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unsetDateUpdate()
	 * @method \Bitrix\Main\Type\DateTime fillDateUpdate()
	 * @method \int getCreatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource resetCreatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method \int getUpdatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource setUpdatedById(\int|\Bitrix\Main\DB\SqlExpression $updatedById)
	 * @method bool hasUpdatedById()
	 * @method bool isUpdatedByIdFilled()
	 * @method bool isUpdatedByIdChanged()
	 * @method \int remindActualUpdatedById()
	 * @method \int requireUpdatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource resetUpdatedById()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unsetUpdatedById()
	 * @method \int fillUpdatedById()
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
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource set($fieldName, $value)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource reset($fieldName)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource wakeUp($data)
	 */
	class EO_ExternalSource {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalSourceCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateUpdateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateUpdate()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 * @method \int[] getUpdatedByIdList()
	 * @method \int[] fillUpdatedById()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSource $object)
	 * @method bool has(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSource $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource getByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSource $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection merge(?\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ExternalSource_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable';
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExternalSource_Result exec()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection fetchCollection()
	 */
	class EO_ExternalSource_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection fetchCollection()
	 */
	class EO_ExternalSource_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection createCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource wakeUpObject($row)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection wakeUpCollection($rows)
	 */
	class EO_ExternalSource_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable:biconnector/lib/ExternalSource/Internal/ExternalSourceSettingsTable.php */
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalSourceSettings
	 * @see \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings setSourceId(\int|\Bitrix\Main\DB\SqlExpression $sourceId)
	 * @method bool hasSourceId()
	 * @method bool isSourceIdFilled()
	 * @method bool isSourceIdChanged()
	 * @method \int remindActualSourceId()
	 * @method \int requireSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings resetSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings unsetSourceId()
	 * @method \int fillSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource getSource()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource remindActualSource()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource requireSource()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings setSource(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSource $object)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings resetSource()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings unsetSource()
	 * @method bool hasSource()
	 * @method bool isSourceFilled()
	 * @method bool isSourceChanged()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource fillSource()
	 * @method \string getCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings resetCode()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings unsetCode()
	 * @method \string fillCode()
	 * @method \string getValue()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings resetValue()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings unsetValue()
	 * @method \string fillValue()
	 * @method \string getName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings resetName()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings unsetName()
	 * @method \string fillName()
	 * @method \string getType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings resetType()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings unsetType()
	 * @method \string fillType()
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
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings set($fieldName, $value)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings reset($fieldName)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings wakeUp($data)
	 */
	class EO_ExternalSourceSettings {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * ExternalSourceSettingsCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getSourceIdList()
	 * @method \int[] fillSourceId()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource[] getSourceList()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection getSourceCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection fillSource()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings $object)
	 * @method bool has(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings getByPrimary($primary)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection merge(?\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ExternalSourceSettings_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable */
		static public $dataClass = '\Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable';
	}
}
namespace Bitrix\BIConnector\ExternalSource\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExternalSourceSettings_Result exec()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection fetchCollection()
	 */
	class EO_ExternalSourceSettings_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings fetchObject()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection fetchCollection()
	 */
	class EO_ExternalSourceSettings_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection createCollection()
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings wakeUpObject($row)
	 * @method \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection wakeUpCollection($rows)
	 */
	class EO_ExternalSourceSettings_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\DashboardUserTable:biconnector/lib/dashboardusertable.php */
namespace Bitrix\BIConnector {
	/**
	 * EO_DashboardUser
	 * @see \Bitrix\BIConnector\DashboardUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\EO_DashboardUser setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\BIConnector\EO_DashboardUser setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\BIConnector\EO_DashboardUser resetTimestampX()
	 * @method \Bitrix\BIConnector\EO_DashboardUser unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\BIConnector\EO_DashboardUser setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\BIConnector\EO_DashboardUser resetCreatedBy()
	 * @method \Bitrix\BIConnector\EO_DashboardUser unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getDashboardId()
	 * @method \Bitrix\BIConnector\EO_DashboardUser setDashboardId(\int|\Bitrix\Main\DB\SqlExpression $dashboardId)
	 * @method bool hasDashboardId()
	 * @method bool isDashboardIdFilled()
	 * @method bool isDashboardIdChanged()
	 * @method \int remindActualDashboardId()
	 * @method \int requireDashboardId()
	 * @method \Bitrix\BIConnector\EO_DashboardUser resetDashboardId()
	 * @method \Bitrix\BIConnector\EO_DashboardUser unsetDashboardId()
	 * @method \int fillDashboardId()
	 * @method \string getUserId()
	 * @method \Bitrix\BIConnector\EO_DashboardUser setUserId(\string|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \string remindActualUserId()
	 * @method \string requireUserId()
	 * @method \Bitrix\BIConnector\EO_DashboardUser resetUserId()
	 * @method \Bitrix\BIConnector\EO_DashboardUser unsetUserId()
	 * @method \string fillUserId()
	 * @method \Bitrix\BIConnector\EO_Dashboard getDashboard()
	 * @method \Bitrix\BIConnector\EO_Dashboard remindActualDashboard()
	 * @method \Bitrix\BIConnector\EO_Dashboard requireDashboard()
	 * @method \Bitrix\BIConnector\EO_DashboardUser setDashboard(\Bitrix\BIConnector\EO_Dashboard $object)
	 * @method \Bitrix\BIConnector\EO_DashboardUser resetDashboard()
	 * @method \Bitrix\BIConnector\EO_DashboardUser unsetDashboard()
	 * @method bool hasDashboard()
	 * @method bool isDashboardFilled()
	 * @method bool isDashboardChanged()
	 * @method \Bitrix\BIConnector\EO_Dashboard fillDashboard()
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
	 * @method \Bitrix\BIConnector\EO_DashboardUser set($fieldName, $value)
	 * @method \Bitrix\BIConnector\EO_DashboardUser reset($fieldName)
	 * @method \Bitrix\BIConnector\EO_DashboardUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\EO_DashboardUser wakeUp($data)
	 */
	class EO_DashboardUser {
		/* @var \Bitrix\BIConnector\DashboardUserTable */
		static public $dataClass = '\Bitrix\BIConnector\DashboardUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector {
	/**
	 * EO_DashboardUser_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getDashboardIdList()
	 * @method \int[] fillDashboardId()
	 * @method \string[] getUserIdList()
	 * @method \string[] fillUserId()
	 * @method \Bitrix\BIConnector\EO_Dashboard[] getDashboardList()
	 * @method \Bitrix\BIConnector\EO_DashboardUser_Collection getDashboardCollection()
	 * @method \Bitrix\BIConnector\EO_Dashboard_Collection fillDashboard()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\EO_DashboardUser $object)
	 * @method bool has(\Bitrix\BIConnector\EO_DashboardUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_DashboardUser getByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_DashboardUser[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\EO_DashboardUser $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\EO_DashboardUser_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\EO_DashboardUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\EO_DashboardUser_Collection merge(?\Bitrix\BIConnector\EO_DashboardUser_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_DashboardUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\DashboardUserTable */
		static public $dataClass = '\Bitrix\BIConnector\DashboardUserTable';
	}
}
namespace Bitrix\BIConnector {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DashboardUser_Result exec()
	 * @method \Bitrix\BIConnector\EO_DashboardUser fetchObject()
	 * @method \Bitrix\BIConnector\EO_DashboardUser_Collection fetchCollection()
	 */
	class EO_DashboardUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\EO_DashboardUser fetchObject()
	 * @method \Bitrix\BIConnector\EO_DashboardUser_Collection fetchCollection()
	 */
	class EO_DashboardUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\EO_DashboardUser createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\EO_DashboardUser_Collection createCollection()
	 * @method \Bitrix\BIConnector\EO_DashboardUser wakeUpObject($row)
	 * @method \Bitrix\BIConnector\EO_DashboardUser_Collection wakeUpCollection($rows)
	 */
	class EO_DashboardUser_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\DictionaryCacheTable:biconnector/lib/dictionarycachetable.php */
namespace Bitrix\BIConnector {
	/**
	 * EO_DictionaryCache
	 * @see \Bitrix\BIConnector\DictionaryCacheTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getDictionaryId()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache setDictionaryId(\int|\Bitrix\Main\DB\SqlExpression $dictionaryId)
	 * @method bool hasDictionaryId()
	 * @method bool isDictionaryIdFilled()
	 * @method bool isDictionaryIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getUpdateDate()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache setUpdateDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateDate)
	 * @method bool hasUpdateDate()
	 * @method bool isUpdateDateFilled()
	 * @method bool isUpdateDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateDate()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateDate()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache resetUpdateDate()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache unsetUpdateDate()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateDate()
	 * @method \int getTtl()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache setTtl(\int|\Bitrix\Main\DB\SqlExpression $ttl)
	 * @method bool hasTtl()
	 * @method bool isTtlFilled()
	 * @method bool isTtlChanged()
	 * @method \int remindActualTtl()
	 * @method \int requireTtl()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache resetTtl()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache unsetTtl()
	 * @method \int fillTtl()
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
	 * @method \Bitrix\BIConnector\EO_DictionaryCache set($fieldName, $value)
	 * @method \Bitrix\BIConnector\EO_DictionaryCache reset($fieldName)
	 * @method \Bitrix\BIConnector\EO_DictionaryCache unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\EO_DictionaryCache wakeUp($data)
	 */
	class EO_DictionaryCache {
		/* @var \Bitrix\BIConnector\DictionaryCacheTable */
		static public $dataClass = '\Bitrix\BIConnector\DictionaryCacheTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector {
	/**
	 * EO_DictionaryCache_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getDictionaryIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateDate()
	 * @method \int[] getTtlList()
	 * @method \int[] fillTtl()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\EO_DictionaryCache $object)
	 * @method bool has(\Bitrix\BIConnector\EO_DictionaryCache $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_DictionaryCache getByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_DictionaryCache[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\EO_DictionaryCache $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\EO_DictionaryCache_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\EO_DictionaryCache current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\EO_DictionaryCache_Collection merge(?\Bitrix\BIConnector\EO_DictionaryCache_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_DictionaryCache_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\DictionaryCacheTable */
		static public $dataClass = '\Bitrix\BIConnector\DictionaryCacheTable';
	}
}
namespace Bitrix\BIConnector {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DictionaryCache_Result exec()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache fetchObject()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache_Collection fetchCollection()
	 */
	class EO_DictionaryCache_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\EO_DictionaryCache fetchObject()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache_Collection fetchCollection()
	 */
	class EO_DictionaryCache_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\EO_DictionaryCache createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\EO_DictionaryCache_Collection createCollection()
	 * @method \Bitrix\BIConnector\EO_DictionaryCache wakeUpObject($row)
	 * @method \Bitrix\BIConnector\EO_DictionaryCache_Collection wakeUpCollection($rows)
	 */
	class EO_DictionaryCache_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable:biconnector/lib/integration/superset/model/supersetusertable.php */
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetUser
	 * @see \Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser resetUserId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getClientId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser setClientId(\string|\Bitrix\Main\DB\SqlExpression $clientId)
	 * @method bool hasClientId()
	 * @method bool isClientIdFilled()
	 * @method bool isClientIdChanged()
	 * @method \string remindActualClientId()
	 * @method \string requireClientId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser resetClientId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser unsetClientId()
	 * @method \string fillClientId()
	 * @method \string getPermissionHash()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser setPermissionHash(\string|\Bitrix\Main\DB\SqlExpression $permissionHash)
	 * @method bool hasPermissionHash()
	 * @method bool isPermissionHashFilled()
	 * @method bool isPermissionHashChanged()
	 * @method \string remindActualPermissionHash()
	 * @method \string requirePermissionHash()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser resetPermissionHash()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser unsetPermissionHash()
	 * @method \string fillPermissionHash()
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
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser reset($fieldName)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser wakeUp($data)
	 */
	class EO_SupersetUser {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetUser_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getClientIdList()
	 * @method \string[] fillClientId()
	 * @method \string[] getPermissionHashList()
	 * @method \string[] fillPermissionHash()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser $object)
	 * @method bool has(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser_Collection merge(?\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_SupersetUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable';
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SupersetUser_Result exec()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser_Collection fetchCollection()
	 */
	class EO_SupersetUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser_Collection fetchCollection()
	 */
	class EO_SupersetUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser_Collection createCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser_Collection wakeUpCollection($rows)
	 */
	class EO_SupersetUser_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTagTable:biconnector/lib/integration/superset/model/supersetdashboardtagtable.php */
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetDashboardTag
	 * @see \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTagTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDashboardId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag setDashboardId(\int|\Bitrix\Main\DB\SqlExpression $dashboardId)
	 * @method bool hasDashboardId()
	 * @method bool isDashboardIdFilled()
	 * @method bool isDashboardIdChanged()
	 * @method \int remindActualDashboardId()
	 * @method \int requireDashboardId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag resetDashboardId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag unsetDashboardId()
	 * @method \int fillDashboardId()
	 * @method \int getTagId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag setTagId(\int|\Bitrix\Main\DB\SqlExpression $tagId)
	 * @method bool hasTagId()
	 * @method bool isTagIdFilled()
	 * @method bool isTagIdChanged()
	 * @method \int remindActualTagId()
	 * @method \int requireTagId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag resetTagId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag unsetTagId()
	 * @method \int fillTagId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag getTag()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag remindActualTag()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag requireTag()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag setTag(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag $object)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag resetTag()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag unsetTag()
	 * @method bool hasTag()
	 * @method bool isTagFilled()
	 * @method bool isTagChanged()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag fillTag()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard getDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard remindActualDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard requireDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag setDashboard(\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard $object)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag resetDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag unsetDashboard()
	 * @method bool hasDashboard()
	 * @method bool isDashboardFilled()
	 * @method bool isDashboardChanged()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard fillDashboard()
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
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag reset($fieldName)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag wakeUp($data)
	 */
	class EO_SupersetDashboardTag {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTagTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTagTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetDashboardTag_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDashboardIdList()
	 * @method \int[] fillDashboardId()
	 * @method \int[] getTagIdList()
	 * @method \int[] fillTagId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag[] getTagList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection getTagCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection fillTag()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard[] getDashboardList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection getDashboardCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection fillDashboard()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag $object)
	 * @method bool has(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection merge(?\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_SupersetDashboardTag_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTagTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTagTable';
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SupersetDashboardTag_Result exec()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection fetchCollection()
	 */
	class EO_SupersetDashboardTag_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection fetchCollection()
	 */
	class EO_SupersetDashboardTag_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection createCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection wakeUpCollection($rows)
	 */
	class EO_SupersetDashboardTag_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Integration\Superset\Model\SupersetTagTable:biconnector/lib/integration/superset/model/supersettagtable.php */
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetTag
	 * @see \Bitrix\BIConnector\Integration\Superset\Model\SupersetTagTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag resetUserId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getTitle()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag resetTitle()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag unsetTitle()
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
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag reset($fieldName)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag wakeUp($data)
	 */
	class EO_SupersetTag {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetTagTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetTagTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetTag_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag $object)
	 * @method bool has(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection merge(?\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_SupersetTag_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetTagTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetTagTable';
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SupersetTag_Result exec()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection fetchCollection()
	 */
	class EO_SupersetTag_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection fetchCollection()
	 */
	class EO_SupersetTag_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection createCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection wakeUpCollection($rows)
	 */
	class EO_SupersetTag_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardUrlParameterTable:biconnector/lib/integration/superset/model/supersetdashboardurlparametertable.php */
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetDashboardUrlParameter
	 * @see \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardUrlParameterTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDashboardId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter setDashboardId(\int|\Bitrix\Main\DB\SqlExpression $dashboardId)
	 * @method bool hasDashboardId()
	 * @method bool isDashboardIdFilled()
	 * @method bool isDashboardIdChanged()
	 * @method \int remindActualDashboardId()
	 * @method \int requireDashboardId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter resetDashboardId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter unsetDashboardId()
	 * @method \int fillDashboardId()
	 * @method \string getCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter resetCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter unsetCode()
	 * @method \string fillCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard getDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard remindActualDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard requireDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter setDashboard(\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard $object)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter resetDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter unsetDashboard()
	 * @method bool hasDashboard()
	 * @method bool isDashboardFilled()
	 * @method bool isDashboardChanged()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard fillDashboard()
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
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter reset($fieldName)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter wakeUp($data)
	 */
	class EO_SupersetDashboardUrlParameter {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardUrlParameterTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardUrlParameterTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetDashboardUrlParameter_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDashboardIdList()
	 * @method \int[] fillDashboardId()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard[] getDashboardList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection getDashboardCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection fillDashboard()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter $object)
	 * @method bool has(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection merge(?\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_SupersetDashboardUrlParameter_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardUrlParameterTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardUrlParameterTable';
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SupersetDashboardUrlParameter_Result exec()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection fetchCollection()
	 */
	class EO_SupersetDashboardUrlParameter_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection fetchCollection()
	 */
	class EO_SupersetDashboardUrlParameter_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection createCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection wakeUpCollection($rows)
	 */
	class EO_SupersetDashboardUrlParameter_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable:biconnector/lib/integration/superset/model/supersetdashboardtable.php */
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * SupersetDashboard
	 * @see \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getExternalId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setExternalId(\int|\Bitrix\Main\DB\SqlExpression $externalId)
	 * @method bool hasExternalId()
	 * @method bool isExternalIdFilled()
	 * @method bool isExternalIdChanged()
	 * @method \int remindActualExternalId()
	 * @method \int requireExternalId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetExternalId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetExternalId()
	 * @method \int fillExternalId()
	 * @method \string getStatus()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetStatus()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetStatus()
	 * @method \string fillStatus()
	 * @method \string getTitle()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetTitle()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetTitle()
	 * @method \string fillTitle()
	 * @method null|\Bitrix\Main\Type\Date getDateFilterStart()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setDateFilterStart(null|\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateFilterStart)
	 * @method bool hasDateFilterStart()
	 * @method bool isDateFilterStartFilled()
	 * @method bool isDateFilterStartChanged()
	 * @method null|\Bitrix\Main\Type\Date remindActualDateFilterStart()
	 * @method null|\Bitrix\Main\Type\Date requireDateFilterStart()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetDateFilterStart()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetDateFilterStart()
	 * @method null|\Bitrix\Main\Type\Date fillDateFilterStart()
	 * @method null|\Bitrix\Main\Type\Date getDateFilterEnd()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setDateFilterEnd(null|\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateFilterEnd)
	 * @method bool hasDateFilterEnd()
	 * @method bool isDateFilterEndFilled()
	 * @method bool isDateFilterEndChanged()
	 * @method null|\Bitrix\Main\Type\Date remindActualDateFilterEnd()
	 * @method null|\Bitrix\Main\Type\Date requireDateFilterEnd()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetDateFilterEnd()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetDateFilterEnd()
	 * @method null|\Bitrix\Main\Type\Date fillDateFilterEnd()
	 * @method \string getType()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetType()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetType()
	 * @method \string fillType()
	 * @method \string getFilterPeriod()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setFilterPeriod(\string|\Bitrix\Main\DB\SqlExpression $filterPeriod)
	 * @method bool hasFilterPeriod()
	 * @method bool isFilterPeriodFilled()
	 * @method bool isFilterPeriodChanged()
	 * @method \string remindActualFilterPeriod()
	 * @method \string requireFilterPeriod()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetFilterPeriod()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetFilterPeriod()
	 * @method \string fillFilterPeriod()
	 * @method \string getAppId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setAppId(\string|\Bitrix\Main\DB\SqlExpression $appId)
	 * @method bool hasAppId()
	 * @method bool isAppIdFilled()
	 * @method bool isAppIdChanged()
	 * @method \string remindActualAppId()
	 * @method \string requireAppId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetAppId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetAppId()
	 * @method \string fillAppId()
	 * @method \Bitrix\Rest\EO_App getApp()
	 * @method \Bitrix\Rest\EO_App remindActualApp()
	 * @method \Bitrix\Rest\EO_App requireApp()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setApp(\Bitrix\Rest\EO_App $object)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetApp()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetApp()
	 * @method bool hasApp()
	 * @method bool isAppFilled()
	 * @method bool isAppChanged()
	 * @method \Bitrix\Rest\EO_App fillApp()
	 * @method \int getSourceId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setSourceId(\int|\Bitrix\Main\DB\SqlExpression $sourceId)
	 * @method bool hasSourceId()
	 * @method bool isSourceIdFilled()
	 * @method bool isSourceIdChanged()
	 * @method \int remindActualSourceId()
	 * @method \int requireSourceId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetSourceId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetSourceId()
	 * @method \int fillSourceId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard getSource()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard remindActualSource()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard requireSource()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setSource(\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard $object)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetSource()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetSource()
	 * @method bool hasSource()
	 * @method bool isSourceFilled()
	 * @method bool isSourceChanged()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard fillSource()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetDateCreate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetDateModify()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
	 * @method null|\int getCreatedById()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setCreatedById(null|\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method null|\int remindActualCreatedById()
	 * @method null|\int requireCreatedById()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetCreatedById()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetCreatedById()
	 * @method null|\int fillCreatedById()
	 * @method null|\int getOwnerId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setOwnerId(null|\int|\Bitrix\Main\DB\SqlExpression $ownerId)
	 * @method bool hasOwnerId()
	 * @method bool isOwnerIdFilled()
	 * @method bool isOwnerIdChanged()
	 * @method null|\int remindActualOwnerId()
	 * @method null|\int requireOwnerId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetOwnerId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetOwnerId()
	 * @method null|\int fillOwnerId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection getTags()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection requireTags()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection fillTags()
	 * @method bool hasTags()
	 * @method bool isTagsFilled()
	 * @method bool isTagsChanged()
	 * @method void addToTags(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag $supersetTag)
	 * @method void removeFromTags(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag $supersetTag)
	 * @method void removeAllTags()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetTags()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetTags()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection getScope()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection requireScope()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection fillScope()
	 * @method bool hasScope()
	 * @method bool isScopeFilled()
	 * @method bool isScopeChanged()
	 * @method void addToScope(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope $supersetScope)
	 * @method void removeFromScope(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope $supersetScope)
	 * @method void removeAllScope()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetScope()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetScope()
	 * @method null|\string getIncludeLastFilterDate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard setIncludeLastFilterDate(null|\string|\Bitrix\Main\DB\SqlExpression $includeLastFilterDate)
	 * @method bool hasIncludeLastFilterDate()
	 * @method bool isIncludeLastFilterDateFilled()
	 * @method bool isIncludeLastFilterDateChanged()
	 * @method null|\string remindActualIncludeLastFilterDate()
	 * @method null|\string requireIncludeLastFilterDate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetIncludeLastFilterDate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetIncludeLastFilterDate()
	 * @method null|\string fillIncludeLastFilterDate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection getUrlParams()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection requireUrlParams()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection fillUrlParams()
	 * @method bool hasUrlParams()
	 * @method bool isUrlParamsFilled()
	 * @method bool isUrlParamsChanged()
	 * @method void addToUrlParams(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter $supersetDashboardUrlParameter)
	 * @method void removeFromUrlParams(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter $supersetDashboardUrlParameter)
	 * @method void removeAllUrlParams()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard resetUrlParams()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unsetUrlParams()
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
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard reset($fieldName)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard wakeUp($data)
	 */
	class EO_SupersetDashboard {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetDashboard_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getExternalIdList()
	 * @method \int[] fillExternalId()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method null|\Bitrix\Main\Type\Date[] getDateFilterStartList()
	 * @method null|\Bitrix\Main\Type\Date[] fillDateFilterStart()
	 * @method null|\Bitrix\Main\Type\Date[] getDateFilterEndList()
	 * @method null|\Bitrix\Main\Type\Date[] fillDateFilterEnd()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getFilterPeriodList()
	 * @method \string[] fillFilterPeriod()
	 * @method \string[] getAppIdList()
	 * @method \string[] fillAppId()
	 * @method \Bitrix\Rest\EO_App[] getAppList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection getAppCollection()
	 * @method \Bitrix\Rest\EO_App_Collection fillApp()
	 * @method \int[] getSourceIdList()
	 * @method \int[] fillSourceId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard[] getSourceList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection getSourceCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection fillSource()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 * @method null|\int[] getCreatedByIdList()
	 * @method null|\int[] fillCreatedById()
	 * @method null|\int[] getOwnerIdList()
	 * @method null|\int[] fillOwnerId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection[] getTagsList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection getTagsCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection fillTags()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection[] getScopeList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection getScopeCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection fillScope()
	 * @method null|\string[] getIncludeLastFilterDateList()
	 * @method null|\string[] fillIncludeLastFilterDate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection[] getUrlParamsList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection getUrlParamsCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection fillUrlParams()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard $object)
	 * @method bool has(\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection merge(?\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_SupersetDashboard_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable';
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SupersetDashboard_Result exec()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection fetchCollection()
	 */
	class EO_SupersetDashboard_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection fetchCollection()
	 */
	class EO_SupersetDashboard_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection createCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection wakeUpCollection($rows)
	 */
	class EO_SupersetDashboard_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Integration\Superset\Model\SupersetScopeTable:biconnector/lib/integration/superset/model/supersetscopetable.php */
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetScope
	 * @see \Bitrix\BIConnector\Integration\Superset\Model\SupersetScopeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDashboardId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope setDashboardId(\int|\Bitrix\Main\DB\SqlExpression $dashboardId)
	 * @method bool hasDashboardId()
	 * @method bool isDashboardIdFilled()
	 * @method bool isDashboardIdChanged()
	 * @method \int remindActualDashboardId()
	 * @method \int requireDashboardId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope resetDashboardId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope unsetDashboardId()
	 * @method \int fillDashboardId()
	 * @method \string getScopeCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope setScopeCode(\string|\Bitrix\Main\DB\SqlExpression $scopeCode)
	 * @method bool hasScopeCode()
	 * @method bool isScopeCodeFilled()
	 * @method bool isScopeCodeChanged()
	 * @method \string remindActualScopeCode()
	 * @method \string requireScopeCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope resetScopeCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope unsetScopeCode()
	 * @method \string fillScopeCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard getDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard remindActualDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard requireDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope setDashboard(\Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard $object)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope resetDashboard()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope unsetDashboard()
	 * @method bool hasDashboard()
	 * @method bool isDashboardFilled()
	 * @method bool isDashboardChanged()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard fillDashboard()
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
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope reset($fieldName)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope wakeUp($data)
	 */
	class EO_SupersetScope {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetScopeTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetScopeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetScope_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDashboardIdList()
	 * @method \int[] fillDashboardId()
	 * @method \string[] getScopeCodeList()
	 * @method \string[] fillScopeCode()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard[] getDashboardList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection getDashboardCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection fillDashboard()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope $object)
	 * @method bool has(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection merge(?\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_SupersetScope_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\Integration\Superset\Model\SupersetScopeTable */
		static public $dataClass = '\Bitrix\BIConnector\Integration\Superset\Model\SupersetScopeTable';
	}
}
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SupersetScope_Result exec()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection fetchCollection()
	 */
	class EO_SupersetScope_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection fetchCollection()
	 */
	class EO_SupersetScope_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection createCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection wakeUpCollection($rows)
	 */
	class EO_SupersetScope_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\KeyUserTable:biconnector/lib/keyusertable.php */
namespace Bitrix\BIConnector {
	/**
	 * EO_KeyUser
	 * @see \Bitrix\BIConnector\KeyUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\EO_KeyUser setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\BIConnector\EO_KeyUser setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\BIConnector\EO_KeyUser resetTimestampX()
	 * @method \Bitrix\BIConnector\EO_KeyUser unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\BIConnector\EO_KeyUser setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\BIConnector\EO_KeyUser resetCreatedBy()
	 * @method \Bitrix\BIConnector\EO_KeyUser unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getKeyId()
	 * @method \Bitrix\BIConnector\EO_KeyUser setKeyId(\int|\Bitrix\Main\DB\SqlExpression $keyId)
	 * @method bool hasKeyId()
	 * @method bool isKeyIdFilled()
	 * @method bool isKeyIdChanged()
	 * @method \int remindActualKeyId()
	 * @method \int requireKeyId()
	 * @method \Bitrix\BIConnector\EO_KeyUser resetKeyId()
	 * @method \Bitrix\BIConnector\EO_KeyUser unsetKeyId()
	 * @method \int fillKeyId()
	 * @method \string getUserId()
	 * @method \Bitrix\BIConnector\EO_KeyUser setUserId(\string|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \string remindActualUserId()
	 * @method \string requireUserId()
	 * @method \Bitrix\BIConnector\EO_KeyUser resetUserId()
	 * @method \Bitrix\BIConnector\EO_KeyUser unsetUserId()
	 * @method \string fillUserId()
	 * @method \Bitrix\BIConnector\EO_Key getKey()
	 * @method \Bitrix\BIConnector\EO_Key remindActualKey()
	 * @method \Bitrix\BIConnector\EO_Key requireKey()
	 * @method \Bitrix\BIConnector\EO_KeyUser setKey(\Bitrix\BIConnector\EO_Key $object)
	 * @method \Bitrix\BIConnector\EO_KeyUser resetKey()
	 * @method \Bitrix\BIConnector\EO_KeyUser unsetKey()
	 * @method bool hasKey()
	 * @method bool isKeyFilled()
	 * @method bool isKeyChanged()
	 * @method \Bitrix\BIConnector\EO_Key fillKey()
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
	 * @method \Bitrix\BIConnector\EO_KeyUser set($fieldName, $value)
	 * @method \Bitrix\BIConnector\EO_KeyUser reset($fieldName)
	 * @method \Bitrix\BIConnector\EO_KeyUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\EO_KeyUser wakeUp($data)
	 */
	class EO_KeyUser {
		/* @var \Bitrix\BIConnector\KeyUserTable */
		static public $dataClass = '\Bitrix\BIConnector\KeyUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector {
	/**
	 * EO_KeyUser_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getKeyIdList()
	 * @method \int[] fillKeyId()
	 * @method \string[] getUserIdList()
	 * @method \string[] fillUserId()
	 * @method \Bitrix\BIConnector\EO_Key[] getKeyList()
	 * @method \Bitrix\BIConnector\EO_KeyUser_Collection getKeyCollection()
	 * @method \Bitrix\BIConnector\EO_Key_Collection fillKey()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\EO_KeyUser $object)
	 * @method bool has(\Bitrix\BIConnector\EO_KeyUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_KeyUser getByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_KeyUser[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\EO_KeyUser $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\EO_KeyUser_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\EO_KeyUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\EO_KeyUser_Collection merge(?\Bitrix\BIConnector\EO_KeyUser_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_KeyUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\KeyUserTable */
		static public $dataClass = '\Bitrix\BIConnector\KeyUserTable';
	}
}
namespace Bitrix\BIConnector {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_KeyUser_Result exec()
	 * @method \Bitrix\BIConnector\EO_KeyUser fetchObject()
	 * @method \Bitrix\BIConnector\EO_KeyUser_Collection fetchCollection()
	 */
	class EO_KeyUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\EO_KeyUser fetchObject()
	 * @method \Bitrix\BIConnector\EO_KeyUser_Collection fetchCollection()
	 */
	class EO_KeyUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\EO_KeyUser createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\EO_KeyUser_Collection createCollection()
	 * @method \Bitrix\BIConnector\EO_KeyUser wakeUpObject($row)
	 * @method \Bitrix\BIConnector\EO_KeyUser_Collection wakeUpCollection($rows)
	 */
	class EO_KeyUser_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\DictStructureAggTable:biconnector/lib/dictstructureagg.php */
namespace Bitrix\BIConnector {
	/**
	 * EO_DictStructureAgg
	 * @see \Bitrix\BIConnector\DictStructureAggTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getDepId()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg setDepId(\int|\Bitrix\Main\DB\SqlExpression $depId)
	 * @method bool hasDepId()
	 * @method bool isDepIdFilled()
	 * @method bool isDepIdChanged()
	 * @method \string getDepName()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg setDepName(\string|\Bitrix\Main\DB\SqlExpression $depName)
	 * @method bool hasDepName()
	 * @method bool isDepNameFilled()
	 * @method bool isDepNameChanged()
	 * @method \string remindActualDepName()
	 * @method \string requireDepName()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg resetDepName()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg unsetDepName()
	 * @method \string fillDepName()
	 * @method \string getDepIds()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg setDepIds(\string|\Bitrix\Main\DB\SqlExpression $depIds)
	 * @method bool hasDepIds()
	 * @method bool isDepIdsFilled()
	 * @method bool isDepIdsChanged()
	 * @method \string remindActualDepIds()
	 * @method \string requireDepIds()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg resetDepIds()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg unsetDepIds()
	 * @method \string fillDepIds()
	 * @method \string getDepNames()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg setDepNames(\string|\Bitrix\Main\DB\SqlExpression $depNames)
	 * @method bool hasDepNames()
	 * @method bool isDepNamesFilled()
	 * @method bool isDepNamesChanged()
	 * @method \string remindActualDepNames()
	 * @method \string requireDepNames()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg resetDepNames()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg unsetDepNames()
	 * @method \string fillDepNames()
	 * @method \string getDepNameIds()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg setDepNameIds(\string|\Bitrix\Main\DB\SqlExpression $depNameIds)
	 * @method bool hasDepNameIds()
	 * @method bool isDepNameIdsFilled()
	 * @method bool isDepNameIdsChanged()
	 * @method \string remindActualDepNameIds()
	 * @method \string requireDepNameIds()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg resetDepNameIds()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg unsetDepNameIds()
	 * @method \string fillDepNameIds()
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
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg set($fieldName, $value)
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg reset($fieldName)
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\EO_DictStructureAgg wakeUp($data)
	 */
	class EO_DictStructureAgg {
		/* @var \Bitrix\BIConnector\DictStructureAggTable */
		static public $dataClass = '\Bitrix\BIConnector\DictStructureAggTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector {
	/**
	 * EO_DictStructureAgg_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getDepIdList()
	 * @method \string[] getDepNameList()
	 * @method \string[] fillDepName()
	 * @method \string[] getDepIdsList()
	 * @method \string[] fillDepIds()
	 * @method \string[] getDepNamesList()
	 * @method \string[] fillDepNames()
	 * @method \string[] getDepNameIdsList()
	 * @method \string[] fillDepNameIds()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\EO_DictStructureAgg $object)
	 * @method bool has(\Bitrix\BIConnector\EO_DictStructureAgg $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg getByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\EO_DictStructureAgg $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\EO_DictStructureAgg_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg_Collection merge(?\Bitrix\BIConnector\EO_DictStructureAgg_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_DictStructureAgg_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\DictStructureAggTable */
		static public $dataClass = '\Bitrix\BIConnector\DictStructureAggTable';
	}
}
namespace Bitrix\BIConnector {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DictStructureAgg_Result exec()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg fetchObject()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg_Collection fetchCollection()
	 */
	class EO_DictStructureAgg_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg fetchObject()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg_Collection fetchCollection()
	 */
	class EO_DictStructureAgg_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg_Collection createCollection()
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg wakeUpObject($row)
	 * @method \Bitrix\BIConnector\EO_DictStructureAgg_Collection wakeUpCollection($rows)
	 */
	class EO_DictStructureAgg_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\DashboardTable:biconnector/lib/dashboardtable.php */
namespace Bitrix\BIConnector {
	/**
	 * EO_Dashboard
	 * @see \Bitrix\BIConnector\DashboardTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\EO_Dashboard setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\BIConnector\EO_Dashboard setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\BIConnector\EO_Dashboard resetDateCreate()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateLastView()
	 * @method \Bitrix\BIConnector\EO_Dashboard setDateLastView(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateLastView)
	 * @method bool hasDateLastView()
	 * @method bool isDateLastViewFilled()
	 * @method bool isDateLastViewChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateLastView()
	 * @method \Bitrix\Main\Type\DateTime requireDateLastView()
	 * @method \Bitrix\BIConnector\EO_Dashboard resetDateLastView()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetDateLastView()
	 * @method \Bitrix\Main\Type\DateTime fillDateLastView()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\BIConnector\EO_Dashboard setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\BIConnector\EO_Dashboard resetTimestampX()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\BIConnector\EO_Dashboard setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\BIConnector\EO_Dashboard resetCreatedBy()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getLastViewBy()
	 * @method \Bitrix\BIConnector\EO_Dashboard setLastViewBy(\int|\Bitrix\Main\DB\SqlExpression $lastViewBy)
	 * @method bool hasLastViewBy()
	 * @method bool isLastViewByFilled()
	 * @method bool isLastViewByChanged()
	 * @method \int remindActualLastViewBy()
	 * @method \int requireLastViewBy()
	 * @method \Bitrix\BIConnector\EO_Dashboard resetLastViewBy()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetLastViewBy()
	 * @method \int fillLastViewBy()
	 * @method \string getName()
	 * @method \Bitrix\BIConnector\EO_Dashboard setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\BIConnector\EO_Dashboard resetName()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetName()
	 * @method \string fillName()
	 * @method \string getUrl()
	 * @method \Bitrix\BIConnector\EO_Dashboard setUrl(\string|\Bitrix\Main\DB\SqlExpression $url)
	 * @method bool hasUrl()
	 * @method bool isUrlFilled()
	 * @method bool isUrlChanged()
	 * @method \string remindActualUrl()
	 * @method \string requireUrl()
	 * @method \Bitrix\BIConnector\EO_Dashboard resetUrl()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetUrl()
	 * @method \string fillUrl()
	 * @method \Bitrix\BIConnector\EO_DashboardUser getPermission()
	 * @method \Bitrix\BIConnector\EO_DashboardUser remindActualPermission()
	 * @method \Bitrix\BIConnector\EO_DashboardUser requirePermission()
	 * @method \Bitrix\BIConnector\EO_Dashboard setPermission(\Bitrix\BIConnector\EO_DashboardUser $object)
	 * @method \Bitrix\BIConnector\EO_Dashboard resetPermission()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetPermission()
	 * @method bool hasPermission()
	 * @method bool isPermissionFilled()
	 * @method bool isPermissionChanged()
	 * @method \Bitrix\BIConnector\EO_DashboardUser fillPermission()
	 * @method \Bitrix\Main\EO_User getCreatedUser()
	 * @method \Bitrix\Main\EO_User remindActualCreatedUser()
	 * @method \Bitrix\Main\EO_User requireCreatedUser()
	 * @method \Bitrix\BIConnector\EO_Dashboard setCreatedUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\BIConnector\EO_Dashboard resetCreatedUser()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetCreatedUser()
	 * @method bool hasCreatedUser()
	 * @method bool isCreatedUserFilled()
	 * @method bool isCreatedUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreatedUser()
	 * @method \Bitrix\Main\EO_User getLastViewUser()
	 * @method \Bitrix\Main\EO_User remindActualLastViewUser()
	 * @method \Bitrix\Main\EO_User requireLastViewUser()
	 * @method \Bitrix\BIConnector\EO_Dashboard setLastViewUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\BIConnector\EO_Dashboard resetLastViewUser()
	 * @method \Bitrix\BIConnector\EO_Dashboard unsetLastViewUser()
	 * @method bool hasLastViewUser()
	 * @method bool isLastViewUserFilled()
	 * @method bool isLastViewUserChanged()
	 * @method \Bitrix\Main\EO_User fillLastViewUser()
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
	 * @method \Bitrix\BIConnector\EO_Dashboard set($fieldName, $value)
	 * @method \Bitrix\BIConnector\EO_Dashboard reset($fieldName)
	 * @method \Bitrix\BIConnector\EO_Dashboard unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\EO_Dashboard wakeUp($data)
	 */
	class EO_Dashboard {
		/* @var \Bitrix\BIConnector\DashboardTable */
		static public $dataClass = '\Bitrix\BIConnector\DashboardTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector {
	/**
	 * EO_Dashboard_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateLastViewList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateLastView()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getLastViewByList()
	 * @method \int[] fillLastViewBy()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getUrlList()
	 * @method \string[] fillUrl()
	 * @method \Bitrix\BIConnector\EO_DashboardUser[] getPermissionList()
	 * @method \Bitrix\BIConnector\EO_Dashboard_Collection getPermissionCollection()
	 * @method \Bitrix\BIConnector\EO_DashboardUser_Collection fillPermission()
	 * @method \Bitrix\Main\EO_User[] getCreatedUserList()
	 * @method \Bitrix\BIConnector\EO_Dashboard_Collection getCreatedUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreatedUser()
	 * @method \Bitrix\Main\EO_User[] getLastViewUserList()
	 * @method \Bitrix\BIConnector\EO_Dashboard_Collection getLastViewUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillLastViewUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\EO_Dashboard $object)
	 * @method bool has(\Bitrix\BIConnector\EO_Dashboard $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_Dashboard getByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_Dashboard[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\EO_Dashboard $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\EO_Dashboard_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\EO_Dashboard current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\EO_Dashboard_Collection merge(?\Bitrix\BIConnector\EO_Dashboard_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Dashboard_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\DashboardTable */
		static public $dataClass = '\Bitrix\BIConnector\DashboardTable';
	}
}
namespace Bitrix\BIConnector {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Dashboard_Result exec()
	 * @method \Bitrix\BIConnector\EO_Dashboard fetchObject()
	 * @method \Bitrix\BIConnector\EO_Dashboard_Collection fetchCollection()
	 */
	class EO_Dashboard_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\EO_Dashboard fetchObject()
	 * @method \Bitrix\BIConnector\EO_Dashboard_Collection fetchCollection()
	 */
	class EO_Dashboard_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\EO_Dashboard createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\EO_Dashboard_Collection createCollection()
	 * @method \Bitrix\BIConnector\EO_Dashboard wakeUpObject($row)
	 * @method \Bitrix\BIConnector\EO_Dashboard_Collection wakeUpCollection($rows)
	 */
	class EO_Dashboard_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\KeyTable:biconnector/lib/keytable.php */
namespace Bitrix\BIConnector {
	/**
	 * EO_Key
	 * @see \Bitrix\BIConnector\KeyTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\EO_Key setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\BIConnector\EO_Key setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\BIConnector\EO_Key resetDateCreate()
	 * @method \Bitrix\BIConnector\EO_Key unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\BIConnector\EO_Key setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\BIConnector\EO_Key resetTimestampX()
	 * @method \Bitrix\BIConnector\EO_Key unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\BIConnector\EO_Key setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\BIConnector\EO_Key resetCreatedBy()
	 * @method \Bitrix\BIConnector\EO_Key unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \string getAccessKey()
	 * @method \Bitrix\BIConnector\EO_Key setAccessKey(\string|\Bitrix\Main\DB\SqlExpression $accessKey)
	 * @method bool hasAccessKey()
	 * @method bool isAccessKeyFilled()
	 * @method bool isAccessKeyChanged()
	 * @method \string remindActualAccessKey()
	 * @method \string requireAccessKey()
	 * @method \Bitrix\BIConnector\EO_Key resetAccessKey()
	 * @method \Bitrix\BIConnector\EO_Key unsetAccessKey()
	 * @method \string fillAccessKey()
	 * @method \string getConnection()
	 * @method \Bitrix\BIConnector\EO_Key setConnection(\string|\Bitrix\Main\DB\SqlExpression $connection)
	 * @method bool hasConnection()
	 * @method bool isConnectionFilled()
	 * @method bool isConnectionChanged()
	 * @method \string remindActualConnection()
	 * @method \string requireConnection()
	 * @method \Bitrix\BIConnector\EO_Key resetConnection()
	 * @method \Bitrix\BIConnector\EO_Key unsetConnection()
	 * @method \string fillConnection()
	 * @method \boolean getActive()
	 * @method \Bitrix\BIConnector\EO_Key setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\BIConnector\EO_Key resetActive()
	 * @method \Bitrix\BIConnector\EO_Key unsetActive()
	 * @method \boolean fillActive()
	 * @method \int getAppId()
	 * @method \Bitrix\BIConnector\EO_Key setAppId(\int|\Bitrix\Main\DB\SqlExpression $appId)
	 * @method bool hasAppId()
	 * @method bool isAppIdFilled()
	 * @method bool isAppIdChanged()
	 * @method \int remindActualAppId()
	 * @method \int requireAppId()
	 * @method \Bitrix\BIConnector\EO_Key resetAppId()
	 * @method \Bitrix\BIConnector\EO_Key unsetAppId()
	 * @method \int fillAppId()
	 * @method \Bitrix\Main\Type\DateTime getLastActivityDate()
	 * @method \Bitrix\BIConnector\EO_Key setLastActivityDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastActivityDate)
	 * @method bool hasLastActivityDate()
	 * @method bool isLastActivityDateFilled()
	 * @method bool isLastActivityDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime requireLastActivityDate()
	 * @method \Bitrix\BIConnector\EO_Key resetLastActivityDate()
	 * @method \Bitrix\BIConnector\EO_Key unsetLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime fillLastActivityDate()
	 * @method \Bitrix\BIConnector\EO_KeyUser getPermission()
	 * @method \Bitrix\BIConnector\EO_KeyUser remindActualPermission()
	 * @method \Bitrix\BIConnector\EO_KeyUser requirePermission()
	 * @method \Bitrix\BIConnector\EO_Key setPermission(\Bitrix\BIConnector\EO_KeyUser $object)
	 * @method \Bitrix\BIConnector\EO_Key resetPermission()
	 * @method \Bitrix\BIConnector\EO_Key unsetPermission()
	 * @method bool hasPermission()
	 * @method bool isPermissionFilled()
	 * @method bool isPermissionChanged()
	 * @method \Bitrix\BIConnector\EO_KeyUser fillPermission()
	 * @method \Bitrix\Main\EO_User getCreatedUser()
	 * @method \Bitrix\Main\EO_User remindActualCreatedUser()
	 * @method \Bitrix\Main\EO_User requireCreatedUser()
	 * @method \Bitrix\BIConnector\EO_Key setCreatedUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\BIConnector\EO_Key resetCreatedUser()
	 * @method \Bitrix\BIConnector\EO_Key unsetCreatedUser()
	 * @method bool hasCreatedUser()
	 * @method bool isCreatedUserFilled()
	 * @method bool isCreatedUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreatedUser()
	 * @method \string getServiceId()
	 * @method \Bitrix\BIConnector\EO_Key setServiceId(\string|\Bitrix\Main\DB\SqlExpression $serviceId)
	 * @method bool hasServiceId()
	 * @method bool isServiceIdFilled()
	 * @method bool isServiceIdChanged()
	 * @method \string remindActualServiceId()
	 * @method \string requireServiceId()
	 * @method \Bitrix\BIConnector\EO_Key resetServiceId()
	 * @method \Bitrix\BIConnector\EO_Key unsetServiceId()
	 * @method \string fillServiceId()
	 * @method \Bitrix\Rest\EO_App getApplication()
	 * @method \Bitrix\Rest\EO_App remindActualApplication()
	 * @method \Bitrix\Rest\EO_App requireApplication()
	 * @method \Bitrix\BIConnector\EO_Key setApplication(\Bitrix\Rest\EO_App $object)
	 * @method \Bitrix\BIConnector\EO_Key resetApplication()
	 * @method \Bitrix\BIConnector\EO_Key unsetApplication()
	 * @method bool hasApplication()
	 * @method bool isApplicationFilled()
	 * @method bool isApplicationChanged()
	 * @method \Bitrix\Rest\EO_App fillApplication()
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
	 * @method \Bitrix\BIConnector\EO_Key set($fieldName, $value)
	 * @method \Bitrix\BIConnector\EO_Key reset($fieldName)
	 * @method \Bitrix\BIConnector\EO_Key unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\EO_Key wakeUp($data)
	 */
	class EO_Key {
		/* @var \Bitrix\BIConnector\KeyTable */
		static public $dataClass = '\Bitrix\BIConnector\KeyTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector {
	/**
	 * EO_Key_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \string[] getAccessKeyList()
	 * @method \string[] fillAccessKey()
	 * @method \string[] getConnectionList()
	 * @method \string[] fillConnection()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \int[] getAppIdList()
	 * @method \int[] fillAppId()
	 * @method \Bitrix\Main\Type\DateTime[] getLastActivityDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLastActivityDate()
	 * @method \Bitrix\BIConnector\EO_KeyUser[] getPermissionList()
	 * @method \Bitrix\BIConnector\EO_Key_Collection getPermissionCollection()
	 * @method \Bitrix\BIConnector\EO_KeyUser_Collection fillPermission()
	 * @method \Bitrix\Main\EO_User[] getCreatedUserList()
	 * @method \Bitrix\BIConnector\EO_Key_Collection getCreatedUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreatedUser()
	 * @method \string[] getServiceIdList()
	 * @method \string[] fillServiceId()
	 * @method \Bitrix\Rest\EO_App[] getApplicationList()
	 * @method \Bitrix\BIConnector\EO_Key_Collection getApplicationCollection()
	 * @method \Bitrix\Rest\EO_App_Collection fillApplication()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\EO_Key $object)
	 * @method bool has(\Bitrix\BIConnector\EO_Key $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_Key getByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_Key[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\EO_Key $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\EO_Key_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\EO_Key current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\EO_Key_Collection merge(?\Bitrix\BIConnector\EO_Key_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Key_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\KeyTable */
		static public $dataClass = '\Bitrix\BIConnector\KeyTable';
	}
}
namespace Bitrix\BIConnector {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Key_Result exec()
	 * @method \Bitrix\BIConnector\EO_Key fetchObject()
	 * @method \Bitrix\BIConnector\EO_Key_Collection fetchCollection()
	 */
	class EO_Key_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\EO_Key fetchObject()
	 * @method \Bitrix\BIConnector\EO_Key_Collection fetchCollection()
	 */
	class EO_Key_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\EO_Key createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\EO_Key_Collection createCollection()
	 * @method \Bitrix\BIConnector\EO_Key wakeUpObject($row)
	 * @method \Bitrix\BIConnector\EO_Key_Collection wakeUpCollection($rows)
	 */
	class EO_Key_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Access\Role\RoleRelationTable:biconnector/lib/Access/Role/RoleRelationTable.php */
namespace Bitrix\BIConnector\Access\Role {
	/**
	 * RoleRelation
	 * @see \Bitrix\BIConnector\Access\Role\RoleRelationTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation resetRoleId()
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getRelation()
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation setRelation(\string|\Bitrix\Main\DB\SqlExpression $relation)
	 * @method bool hasRelation()
	 * @method bool isRelationFilled()
	 * @method bool isRelationChanged()
	 * @method \string remindActualRelation()
	 * @method \string requireRelation()
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation resetRelation()
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation unsetRelation()
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
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation reset($fieldName)
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Access\Role\RoleRelation wakeUp($data)
	 */
	class EO_RoleRelation {
		/* @var \Bitrix\BIConnector\Access\Role\RoleRelationTable */
		static public $dataClass = '\Bitrix\BIConnector\Access\Role\RoleRelationTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\Access\Role {
	/**
	 * EO_RoleRelation_Collection
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
	 * @method void add(\Bitrix\BIConnector\Access\Role\RoleRelation $object)
	 * @method bool has(\Bitrix\BIConnector\Access\Role\RoleRelation $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Access\Role\RoleRelation $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Access\Role\EO_RoleRelation_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\Access\Role\EO_RoleRelation_Collection merge(?\Bitrix\BIConnector\Access\Role\EO_RoleRelation_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_RoleRelation_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\Access\Role\RoleRelationTable */
		static public $dataClass = '\Bitrix\BIConnector\Access\Role\RoleRelationTable';
	}
}
namespace Bitrix\BIConnector\Access\Role {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RoleRelation_Result exec()
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation fetchObject()
	 * @method \Bitrix\BIConnector\Access\Role\EO_RoleRelation_Collection fetchCollection()
	 */
	class EO_RoleRelation_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation fetchObject()
	 * @method \Bitrix\BIConnector\Access\Role\EO_RoleRelation_Collection fetchCollection()
	 */
	class EO_RoleRelation_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Access\Role\EO_RoleRelation_Collection createCollection()
	 * @method \Bitrix\BIConnector\Access\Role\RoleRelation wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Access\Role\EO_RoleRelation_Collection wakeUpCollection($rows)
	 */
	class EO_RoleRelation_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Access\Role\RoleTable:biconnector/lib/Access/Role/RoleTable.php */
namespace Bitrix\BIConnector\Access\Role {
	/**
	 * Role
	 * @see \Bitrix\BIConnector\Access\Role\RoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Access\Role\Role setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\BIConnector\Access\Role\Role setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\BIConnector\Access\Role\Role resetName()
	 * @method \Bitrix\BIConnector\Access\Role\Role unsetName()
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
	 * @method \Bitrix\BIConnector\Access\Role\Role set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Access\Role\Role reset($fieldName)
	 * @method \Bitrix\BIConnector\Access\Role\Role unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Access\Role\Role wakeUp($data)
	 */
	class EO_Role {
		/* @var \Bitrix\BIConnector\Access\Role\RoleTable */
		static public $dataClass = '\Bitrix\BIConnector\Access\Role\RoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\Access\Role {
	/**
	 * EO_Role_Collection
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
	 * @method void add(\Bitrix\BIConnector\Access\Role\Role $object)
	 * @method bool has(\Bitrix\BIConnector\Access\Role\Role $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Access\Role\Role getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Access\Role\Role[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Access\Role\Role $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Access\Role\EO_Role_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Access\Role\Role current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\Access\Role\EO_Role_Collection merge(?\Bitrix\BIConnector\Access\Role\EO_Role_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Role_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\Access\Role\RoleTable */
		static public $dataClass = '\Bitrix\BIConnector\Access\Role\RoleTable';
	}
}
namespace Bitrix\BIConnector\Access\Role {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Role_Result exec()
	 * @method \Bitrix\BIConnector\Access\Role\Role fetchObject()
	 * @method \Bitrix\BIConnector\Access\Role\EO_Role_Collection fetchCollection()
	 */
	class EO_Role_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Access\Role\Role fetchObject()
	 * @method \Bitrix\BIConnector\Access\Role\EO_Role_Collection fetchCollection()
	 */
	class EO_Role_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Access\Role\Role createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Access\Role\EO_Role_Collection createCollection()
	 * @method \Bitrix\BIConnector\Access\Role\Role wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Access\Role\EO_Role_Collection wakeUpCollection($rows)
	 */
	class EO_Role_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Access\Permission\PermissionTable:biconnector/lib/Access/Permission/PermissionTable.php */
namespace Bitrix\BIConnector\Access\Permission {
	/**
	 * Permission
	 * @see \Bitrix\BIConnector\Access\Permission\PermissionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission resetRoleId()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getPermissionId()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission setPermissionId(\string|\Bitrix\Main\DB\SqlExpression $permissionId)
	 * @method bool hasPermissionId()
	 * @method bool isPermissionIdFilled()
	 * @method bool isPermissionIdChanged()
	 * @method \string remindActualPermissionId()
	 * @method \string requirePermissionId()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission resetPermissionId()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission unsetPermissionId()
	 * @method \string fillPermissionId()
	 * @method \int getValue()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission setValue(\int|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \int remindActualValue()
	 * @method \int requireValue()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission resetValue()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission unsetValue()
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
	 * @method \Bitrix\BIConnector\Access\Permission\Permission set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Access\Permission\Permission reset($fieldName)
	 * @method \Bitrix\BIConnector\Access\Permission\Permission unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Access\Permission\Permission wakeUp($data)
	 */
	class EO_Permission {
		/* @var \Bitrix\BIConnector\Access\Permission\PermissionTable */
		static public $dataClass = '\Bitrix\BIConnector\Access\Permission\PermissionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector\Access\Permission {
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
	 * @method \int[] getValueList()
	 * @method \int[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\Access\Permission\Permission $object)
	 * @method bool has(\Bitrix\BIConnector\Access\Permission\Permission $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Access\Permission\Permission getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Access\Permission\Permission[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Access\Permission\Permission $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Access\Permission\EO_Permission_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Access\Permission\Permission current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\Access\Permission\EO_Permission_Collection merge(?\Bitrix\BIConnector\Access\Permission\EO_Permission_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Permission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\Access\Permission\PermissionTable */
		static public $dataClass = '\Bitrix\BIConnector\Access\Permission\PermissionTable';
	}
}
namespace Bitrix\BIConnector\Access\Permission {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Permission_Result exec()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission fetchObject()
	 * @method \Bitrix\BIConnector\Access\Permission\EO_Permission_Collection fetchCollection()
	 */
	class EO_Permission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Access\Permission\Permission fetchObject()
	 * @method \Bitrix\BIConnector\Access\Permission\EO_Permission_Collection fetchCollection()
	 */
	class EO_Permission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Access\Permission\Permission createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Access\Permission\EO_Permission_Collection createCollection()
	 * @method \Bitrix\BIConnector\Access\Permission\Permission wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Access\Permission\EO_Permission_Collection wakeUpCollection($rows)
	 */
	class EO_Permission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\DictionaryDataTable:biconnector/lib/dictionarydatatable.php */
namespace Bitrix\BIConnector {
	/**
	 * EO_DictionaryData
	 * @see \Bitrix\BIConnector\DictionaryDataTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getDictionaryId()
	 * @method \Bitrix\BIConnector\EO_DictionaryData setDictionaryId(\int|\Bitrix\Main\DB\SqlExpression $dictionaryId)
	 * @method bool hasDictionaryId()
	 * @method bool isDictionaryIdFilled()
	 * @method bool isDictionaryIdChanged()
	 * @method \int getValueId()
	 * @method \Bitrix\BIConnector\EO_DictionaryData setValueId(\int|\Bitrix\Main\DB\SqlExpression $valueId)
	 * @method bool hasValueId()
	 * @method bool isValueIdFilled()
	 * @method bool isValueIdChanged()
	 * @method \string getValueStr()
	 * @method \Bitrix\BIConnector\EO_DictionaryData setValueStr(\string|\Bitrix\Main\DB\SqlExpression $valueStr)
	 * @method bool hasValueStr()
	 * @method bool isValueStrFilled()
	 * @method bool isValueStrChanged()
	 * @method \string remindActualValueStr()
	 * @method \string requireValueStr()
	 * @method \Bitrix\BIConnector\EO_DictionaryData resetValueStr()
	 * @method \Bitrix\BIConnector\EO_DictionaryData unsetValueStr()
	 * @method \string fillValueStr()
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
	 * @method \Bitrix\BIConnector\EO_DictionaryData set($fieldName, $value)
	 * @method \Bitrix\BIConnector\EO_DictionaryData reset($fieldName)
	 * @method \Bitrix\BIConnector\EO_DictionaryData unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\EO_DictionaryData wakeUp($data)
	 */
	class EO_DictionaryData {
		/* @var \Bitrix\BIConnector\DictionaryDataTable */
		static public $dataClass = '\Bitrix\BIConnector\DictionaryDataTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector {
	/**
	 * EO_DictionaryData_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getDictionaryIdList()
	 * @method \int[] getValueIdList()
	 * @method \string[] getValueStrList()
	 * @method \string[] fillValueStr()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\EO_DictionaryData $object)
	 * @method bool has(\Bitrix\BIConnector\EO_DictionaryData $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_DictionaryData getByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_DictionaryData[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\EO_DictionaryData $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\EO_DictionaryData_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\EO_DictionaryData current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\EO_DictionaryData_Collection merge(?\Bitrix\BIConnector\EO_DictionaryData_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_DictionaryData_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\DictionaryDataTable */
		static public $dataClass = '\Bitrix\BIConnector\DictionaryDataTable';
	}
}
namespace Bitrix\BIConnector {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DictionaryData_Result exec()
	 * @method \Bitrix\BIConnector\EO_DictionaryData fetchObject()
	 * @method \Bitrix\BIConnector\EO_DictionaryData_Collection fetchCollection()
	 */
	class EO_DictionaryData_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\EO_DictionaryData fetchObject()
	 * @method \Bitrix\BIConnector\EO_DictionaryData_Collection fetchCollection()
	 */
	class EO_DictionaryData_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\EO_DictionaryData createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\EO_DictionaryData_Collection createCollection()
	 * @method \Bitrix\BIConnector\EO_DictionaryData wakeUpObject($row)
	 * @method \Bitrix\BIConnector\EO_DictionaryData_Collection wakeUpCollection($rows)
	 */
	class EO_DictionaryData_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\BIConnector\LogTable:biconnector/lib/logtable.php */
namespace Bitrix\BIConnector {
	/**
	 * EO_Log
	 * @see \Bitrix\BIConnector\LogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\EO_Log setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\BIConnector\EO_Log setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\BIConnector\EO_Log resetTimestampX()
	 * @method \Bitrix\BIConnector\EO_Log unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getKeyId()
	 * @method \Bitrix\BIConnector\EO_Log setKeyId(\int|\Bitrix\Main\DB\SqlExpression $keyId)
	 * @method bool hasKeyId()
	 * @method bool isKeyIdFilled()
	 * @method bool isKeyIdChanged()
	 * @method \int remindActualKeyId()
	 * @method \int requireKeyId()
	 * @method \Bitrix\BIConnector\EO_Log resetKeyId()
	 * @method \Bitrix\BIConnector\EO_Log unsetKeyId()
	 * @method \int fillKeyId()
	 * @method \string getServiceId()
	 * @method \Bitrix\BIConnector\EO_Log setServiceId(\string|\Bitrix\Main\DB\SqlExpression $serviceId)
	 * @method bool hasServiceId()
	 * @method bool isServiceIdFilled()
	 * @method bool isServiceIdChanged()
	 * @method \string remindActualServiceId()
	 * @method \string requireServiceId()
	 * @method \Bitrix\BIConnector\EO_Log resetServiceId()
	 * @method \Bitrix\BIConnector\EO_Log unsetServiceId()
	 * @method \string fillServiceId()
	 * @method \string getSourceId()
	 * @method \Bitrix\BIConnector\EO_Log setSourceId(\string|\Bitrix\Main\DB\SqlExpression $sourceId)
	 * @method bool hasSourceId()
	 * @method bool isSourceIdFilled()
	 * @method bool isSourceIdChanged()
	 * @method \string remindActualSourceId()
	 * @method \string requireSourceId()
	 * @method \Bitrix\BIConnector\EO_Log resetSourceId()
	 * @method \Bitrix\BIConnector\EO_Log unsetSourceId()
	 * @method \string fillSourceId()
	 * @method \string getFields()
	 * @method \Bitrix\BIConnector\EO_Log setFields(\string|\Bitrix\Main\DB\SqlExpression $fields)
	 * @method bool hasFields()
	 * @method bool isFieldsFilled()
	 * @method bool isFieldsChanged()
	 * @method \string remindActualFields()
	 * @method \string requireFields()
	 * @method \Bitrix\BIConnector\EO_Log resetFields()
	 * @method \Bitrix\BIConnector\EO_Log unsetFields()
	 * @method \string fillFields()
	 * @method \string getFilters()
	 * @method \Bitrix\BIConnector\EO_Log setFilters(\string|\Bitrix\Main\DB\SqlExpression $filters)
	 * @method bool hasFilters()
	 * @method bool isFiltersFilled()
	 * @method bool isFiltersChanged()
	 * @method \string remindActualFilters()
	 * @method \string requireFilters()
	 * @method \Bitrix\BIConnector\EO_Log resetFilters()
	 * @method \Bitrix\BIConnector\EO_Log unsetFilters()
	 * @method \string fillFilters()
	 * @method \string getInput()
	 * @method \Bitrix\BIConnector\EO_Log setInput(\string|\Bitrix\Main\DB\SqlExpression $input)
	 * @method bool hasInput()
	 * @method bool isInputFilled()
	 * @method bool isInputChanged()
	 * @method \string remindActualInput()
	 * @method \string requireInput()
	 * @method \Bitrix\BIConnector\EO_Log resetInput()
	 * @method \Bitrix\BIConnector\EO_Log unsetInput()
	 * @method \string fillInput()
	 * @method \string getRequestMethod()
	 * @method \Bitrix\BIConnector\EO_Log setRequestMethod(\string|\Bitrix\Main\DB\SqlExpression $requestMethod)
	 * @method bool hasRequestMethod()
	 * @method bool isRequestMethodFilled()
	 * @method bool isRequestMethodChanged()
	 * @method \string remindActualRequestMethod()
	 * @method \string requireRequestMethod()
	 * @method \Bitrix\BIConnector\EO_Log resetRequestMethod()
	 * @method \Bitrix\BIConnector\EO_Log unsetRequestMethod()
	 * @method \string fillRequestMethod()
	 * @method \string getRequestUri()
	 * @method \Bitrix\BIConnector\EO_Log setRequestUri(\string|\Bitrix\Main\DB\SqlExpression $requestUri)
	 * @method bool hasRequestUri()
	 * @method bool isRequestUriFilled()
	 * @method bool isRequestUriChanged()
	 * @method \string remindActualRequestUri()
	 * @method \string requireRequestUri()
	 * @method \Bitrix\BIConnector\EO_Log resetRequestUri()
	 * @method \Bitrix\BIConnector\EO_Log unsetRequestUri()
	 * @method \string fillRequestUri()
	 * @method \int getRowNum()
	 * @method \Bitrix\BIConnector\EO_Log setRowNum(\int|\Bitrix\Main\DB\SqlExpression $rowNum)
	 * @method bool hasRowNum()
	 * @method bool isRowNumFilled()
	 * @method bool isRowNumChanged()
	 * @method \int remindActualRowNum()
	 * @method \int requireRowNum()
	 * @method \Bitrix\BIConnector\EO_Log resetRowNum()
	 * @method \Bitrix\BIConnector\EO_Log unsetRowNum()
	 * @method \int fillRowNum()
	 * @method \int getDataSize()
	 * @method \Bitrix\BIConnector\EO_Log setDataSize(\int|\Bitrix\Main\DB\SqlExpression $dataSize)
	 * @method bool hasDataSize()
	 * @method bool isDataSizeFilled()
	 * @method bool isDataSizeChanged()
	 * @method \int remindActualDataSize()
	 * @method \int requireDataSize()
	 * @method \Bitrix\BIConnector\EO_Log resetDataSize()
	 * @method \Bitrix\BIConnector\EO_Log unsetDataSize()
	 * @method \int fillDataSize()
	 * @method \float getRealTime()
	 * @method \Bitrix\BIConnector\EO_Log setRealTime(\float|\Bitrix\Main\DB\SqlExpression $realTime)
	 * @method bool hasRealTime()
	 * @method bool isRealTimeFilled()
	 * @method bool isRealTimeChanged()
	 * @method \float remindActualRealTime()
	 * @method \float requireRealTime()
	 * @method \Bitrix\BIConnector\EO_Log resetRealTime()
	 * @method \Bitrix\BIConnector\EO_Log unsetRealTime()
	 * @method \float fillRealTime()
	 * @method \boolean getIsOverLimit()
	 * @method \Bitrix\BIConnector\EO_Log setIsOverLimit(\boolean|\Bitrix\Main\DB\SqlExpression $isOverLimit)
	 * @method bool hasIsOverLimit()
	 * @method bool isIsOverLimitFilled()
	 * @method bool isIsOverLimitChanged()
	 * @method \boolean remindActualIsOverLimit()
	 * @method \boolean requireIsOverLimit()
	 * @method \Bitrix\BIConnector\EO_Log resetIsOverLimit()
	 * @method \Bitrix\BIConnector\EO_Log unsetIsOverLimit()
	 * @method \boolean fillIsOverLimit()
	 * @method \Bitrix\BIConnector\EO_Key getKey()
	 * @method \Bitrix\BIConnector\EO_Key remindActualKey()
	 * @method \Bitrix\BIConnector\EO_Key requireKey()
	 * @method \Bitrix\BIConnector\EO_Log setKey(\Bitrix\BIConnector\EO_Key $object)
	 * @method \Bitrix\BIConnector\EO_Log resetKey()
	 * @method \Bitrix\BIConnector\EO_Log unsetKey()
	 * @method bool hasKey()
	 * @method bool isKeyFilled()
	 * @method bool isKeyChanged()
	 * @method \Bitrix\BIConnector\EO_Key fillKey()
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
	 * @method \Bitrix\BIConnector\EO_Log set($fieldName, $value)
	 * @method \Bitrix\BIConnector\EO_Log reset($fieldName)
	 * @method \Bitrix\BIConnector\EO_Log unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\EO_Log wakeUp($data)
	 */
	class EO_Log {
		/* @var \Bitrix\BIConnector\LogTable */
		static public $dataClass = '\Bitrix\BIConnector\LogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\BIConnector {
	/**
	 * EO_Log_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getKeyIdList()
	 * @method \int[] fillKeyId()
	 * @method \string[] getServiceIdList()
	 * @method \string[] fillServiceId()
	 * @method \string[] getSourceIdList()
	 * @method \string[] fillSourceId()
	 * @method \string[] getFieldsList()
	 * @method \string[] fillFields()
	 * @method \string[] getFiltersList()
	 * @method \string[] fillFilters()
	 * @method \string[] getInputList()
	 * @method \string[] fillInput()
	 * @method \string[] getRequestMethodList()
	 * @method \string[] fillRequestMethod()
	 * @method \string[] getRequestUriList()
	 * @method \string[] fillRequestUri()
	 * @method \int[] getRowNumList()
	 * @method \int[] fillRowNum()
	 * @method \int[] getDataSizeList()
	 * @method \int[] fillDataSize()
	 * @method \float[] getRealTimeList()
	 * @method \float[] fillRealTime()
	 * @method \boolean[] getIsOverLimitList()
	 * @method \boolean[] fillIsOverLimit()
	 * @method \Bitrix\BIConnector\EO_Key[] getKeyList()
	 * @method \Bitrix\BIConnector\EO_Log_Collection getKeyCollection()
	 * @method \Bitrix\BIConnector\EO_Key_Collection fillKey()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\EO_Log $object)
	 * @method bool has(\Bitrix\BIConnector\EO_Log $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_Log getByPrimary($primary)
	 * @method \Bitrix\BIConnector\EO_Log[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\EO_Log $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\EO_Log_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\EO_Log current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\BIConnector\EO_Log_Collection merge(?\Bitrix\BIConnector\EO_Log_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Log_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\BIConnector\LogTable */
		static public $dataClass = '\Bitrix\BIConnector\LogTable';
	}
}
namespace Bitrix\BIConnector {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Log_Result exec()
	 * @method \Bitrix\BIConnector\EO_Log fetchObject()
	 * @method \Bitrix\BIConnector\EO_Log_Collection fetchCollection()
	 */
	class EO_Log_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\EO_Log fetchObject()
	 * @method \Bitrix\BIConnector\EO_Log_Collection fetchCollection()
	 */
	class EO_Log_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\EO_Log createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\EO_Log_Collection createCollection()
	 * @method \Bitrix\BIConnector\EO_Log wakeUpObject($row)
	 * @method \Bitrix\BIConnector\EO_Log_Collection wakeUpCollection($rows)
	 */
	class EO_Log_Entity extends \Bitrix\Main\ORM\Entity {}
}