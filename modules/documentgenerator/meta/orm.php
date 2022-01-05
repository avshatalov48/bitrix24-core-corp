<?php

/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\DocumentTable:documentgenerator/lib/model/document.php:e461ddb8a69522f16894cfd0abe20463 */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Document
	 * @see \Bitrix\DocumentGenerator\Model\DocumentTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getNumber()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setNumber(\string|\Bitrix\Main\DB\SqlExpression $number)
	 * @method bool hasNumber()
	 * @method bool isNumberFilled()
	 * @method bool isNumberChanged()
	 * @method \string remindActualNumber()
	 * @method \string requireNumber()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetNumber()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetNumber()
	 * @method \string fillNumber()
	 * @method \int getTemplateId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \int remindActualTemplateId()
	 * @method \int requireTemplateId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetTemplateId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetTemplateId()
	 * @method \int fillTemplateId()
	 * @method \string getProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setProvider(\string|\Bitrix\Main\DB\SqlExpression $provider)
	 * @method bool hasProvider()
	 * @method bool isProviderFilled()
	 * @method bool isProviderChanged()
	 * @method \string remindActualProvider()
	 * @method \string requireProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetProvider()
	 * @method \string fillProvider()
	 * @method \string getValue()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetValue()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetValue()
	 * @method \string fillValue()
	 * @method \int getFileId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetFileId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetFileId()
	 * @method \int fillFileId()
	 * @method \int getImageId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setImageId(\int|\Bitrix\Main\DB\SqlExpression $imageId)
	 * @method bool hasImageId()
	 * @method bool isImageIdFilled()
	 * @method bool isImageIdChanged()
	 * @method \int remindActualImageId()
	 * @method \int requireImageId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetImageId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetImageId()
	 * @method \int fillImageId()
	 * @method \int getPdfId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setPdfId(\int|\Bitrix\Main\DB\SqlExpression $pdfId)
	 * @method bool hasPdfId()
	 * @method bool isPdfIdFilled()
	 * @method bool isPdfIdChanged()
	 * @method \int remindActualPdfId()
	 * @method \int requirePdfId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetPdfId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetPdfId()
	 * @method \int fillPdfId()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetCreateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetUpdateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
	 * @method \string getValues()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setValues(\string|\Bitrix\Main\DB\SqlExpression $values)
	 * @method bool hasValues()
	 * @method bool isValuesFilled()
	 * @method bool isValuesChanged()
	 * @method \string remindActualValues()
	 * @method \string requireValues()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetValues()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetValues()
	 * @method \string fillValues()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetCreatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setUpdatedBy(\int|\Bitrix\Main\DB\SqlExpression $updatedBy)
	 * @method bool hasUpdatedBy()
	 * @method bool isUpdatedByFilled()
	 * @method bool isUpdatedByChanged()
	 * @method \int remindActualUpdatedBy()
	 * @method \int requireUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetUpdatedBy()
	 * @method \int fillUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template getTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template remindActualTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template requireTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document setTemplate(\Bitrix\DocumentGenerator\Model\EO_Template $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document resetTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unsetTemplate()
	 * @method bool hasTemplate()
	 * @method bool isTemplateFilled()
	 * @method bool isTemplateChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template fillTemplate()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Document wakeUp($data)
	 */
	class EO_Document {
		/* @var \Bitrix\DocumentGenerator\Model\DocumentTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\DocumentTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Document_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getNumberList()
	 * @method \string[] fillNumber()
	 * @method \int[] getTemplateIdList()
	 * @method \int[] fillTemplateId()
	 * @method \string[] getProviderList()
	 * @method \string[] fillProvider()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \int[] getImageIdList()
	 * @method \int[] fillImageId()
	 * @method \int[] getPdfIdList()
	 * @method \int[] fillPdfId()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 * @method \string[] getValuesList()
	 * @method \string[] fillValues()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getUpdatedByList()
	 * @method \int[] fillUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template[] getTemplateList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document_Collection getTemplateCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection fillTemplate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_Document $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_Document $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_Document $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Document_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Document_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\DocumentTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\DocumentTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Document_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Document_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document_Collection fetchCollection()
	 */
	class EO_Document_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document_Collection wakeUpCollection($rows)
	 */
	class EO_Document_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\ExternalLinkTable:documentgenerator/lib/model/externallinktable.php:7bc113332199d027f5b9a82216eb938b */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_ExternalLink
	 * @see \Bitrix\DocumentGenerator\Model\ExternalLinkTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getDocumentId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink setDocumentId(\int|\Bitrix\Main\DB\SqlExpression $documentId)
	 * @method bool hasDocumentId()
	 * @method bool isDocumentIdFilled()
	 * @method bool isDocumentIdChanged()
	 * @method \int remindActualDocumentId()
	 * @method \int requireDocumentId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink resetDocumentId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink unsetDocumentId()
	 * @method \int fillDocumentId()
	 * @method \string getHash()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink resetHash()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink unsetHash()
	 * @method \string fillHash()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document getDocument()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document remindActualDocument()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document requireDocument()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink setDocument(\Bitrix\DocumentGenerator\Model\EO_Document $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink resetDocument()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink unsetDocument()
	 * @method bool hasDocument()
	 * @method bool isDocumentFilled()
	 * @method bool isDocumentChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document fillDocument()
	 * @method \Bitrix\Main\Type\DateTime getViewedTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink setViewedTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $viewedTime)
	 * @method bool hasViewedTime()
	 * @method bool isViewedTimeFilled()
	 * @method bool isViewedTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualViewedTime()
	 * @method \Bitrix\Main\Type\DateTime requireViewedTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink resetViewedTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink unsetViewedTime()
	 * @method \Bitrix\Main\Type\DateTime fillViewedTime()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_ExternalLink wakeUp($data)
	 */
	class EO_ExternalLink {
		/* @var \Bitrix\DocumentGenerator\Model\ExternalLinkTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\ExternalLinkTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_ExternalLink_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getDocumentIdList()
	 * @method \int[] fillDocumentId()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document[] getDocumentList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink_Collection getDocumentCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Document_Collection fillDocument()
	 * @method \Bitrix\Main\Type\DateTime[] getViewedTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillViewedTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_ExternalLink $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_ExternalLink $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_ExternalLink $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_ExternalLink_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ExternalLink_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\ExternalLinkTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\ExternalLinkTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExternalLink_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ExternalLink_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink_Collection fetchCollection()
	 */
	class EO_ExternalLink_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_ExternalLink_Collection wakeUpCollection($rows)
	 */
	class EO_ExternalLink_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\FieldTable:documentgenerator/lib/model/field.php:9e142ccab6820ef23953f962947902da */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Field
	 * @see \Bitrix\DocumentGenerator\Model\FieldTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTemplateId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \int remindActualTemplateId()
	 * @method \int requireTemplateId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetTemplateId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetTemplateId()
	 * @method \int fillTemplateId()
	 * @method \string getTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getPlaceholder()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setPlaceholder(\string|\Bitrix\Main\DB\SqlExpression $placeholder)
	 * @method bool hasPlaceholder()
	 * @method bool isPlaceholderFilled()
	 * @method bool isPlaceholderChanged()
	 * @method \string remindActualPlaceholder()
	 * @method \string requirePlaceholder()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetPlaceholder()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetPlaceholder()
	 * @method \string fillPlaceholder()
	 * @method \string getProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setProvider(\string|\Bitrix\Main\DB\SqlExpression $provider)
	 * @method bool hasProvider()
	 * @method bool isProviderFilled()
	 * @method bool isProviderChanged()
	 * @method \string remindActualProvider()
	 * @method \string requireProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetProvider()
	 * @method \string fillProvider()
	 * @method \string getProviderName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setProviderName(\string|\Bitrix\Main\DB\SqlExpression $providerName)
	 * @method bool hasProviderName()
	 * @method bool isProviderNameFilled()
	 * @method bool isProviderNameChanged()
	 * @method \string remindActualProviderName()
	 * @method \string requireProviderName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetProviderName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetProviderName()
	 * @method \string fillProviderName()
	 * @method \string getValue()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetValue()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetValue()
	 * @method \string fillValue()
	 * @method \boolean getRequired()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setRequired(\boolean|\Bitrix\Main\DB\SqlExpression $required)
	 * @method bool hasRequired()
	 * @method bool isRequiredFilled()
	 * @method bool isRequiredChanged()
	 * @method \boolean remindActualRequired()
	 * @method \boolean requireRequired()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetRequired()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetRequired()
	 * @method \boolean fillRequired()
	 * @method \boolean getHideRow()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setHideRow(\boolean|\Bitrix\Main\DB\SqlExpression $hideRow)
	 * @method bool hasHideRow()
	 * @method bool isHideRowFilled()
	 * @method bool isHideRowChanged()
	 * @method \boolean remindActualHideRow()
	 * @method \boolean requireHideRow()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetHideRow()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetHideRow()
	 * @method \boolean fillHideRow()
	 * @method \string getType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetType()
	 * @method \string fillType()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetCreateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetUpdateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetCreatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field setUpdatedBy(\int|\Bitrix\Main\DB\SqlExpression $updatedBy)
	 * @method bool hasUpdatedBy()
	 * @method bool isUpdatedByFilled()
	 * @method bool isUpdatedByChanged()
	 * @method \int remindActualUpdatedBy()
	 * @method \int requireUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field resetUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unsetUpdatedBy()
	 * @method \int fillUpdatedBy()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Field wakeUp($data)
	 */
	class EO_Field {
		/* @var \Bitrix\DocumentGenerator\Model\FieldTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\FieldTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Field_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTemplateIdList()
	 * @method \int[] fillTemplateId()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getPlaceholderList()
	 * @method \string[] fillPlaceholder()
	 * @method \string[] getProviderList()
	 * @method \string[] fillProvider()
	 * @method \string[] getProviderNameList()
	 * @method \string[] fillProviderName()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \boolean[] getRequiredList()
	 * @method \boolean[] fillRequired()
	 * @method \boolean[] getHideRowList()
	 * @method \boolean[] fillHideRow()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getUpdatedByList()
	 * @method \int[] fillUpdatedBy()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_Field $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_Field $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_Field $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Field_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Field_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\FieldTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\FieldTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Field_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Field_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field_Collection fetchCollection()
	 */
	class EO_Field_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Field_Collection wakeUpCollection($rows)
	 */
	class EO_Field_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\FileTable:documentgenerator/lib/model/file.php:8c4aaa465f463e59c15980c2dbc6993a */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_File
	 * @see \Bitrix\DocumentGenerator\Model\FileTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getStorageType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File setStorageType(\string|\Bitrix\Main\DB\SqlExpression $storageType)
	 * @method bool hasStorageType()
	 * @method bool isStorageTypeFilled()
	 * @method bool isStorageTypeChanged()
	 * @method \string remindActualStorageType()
	 * @method \string requireStorageType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File resetStorageType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File unsetStorageType()
	 * @method \string fillStorageType()
	 * @method \string getStorageWhere()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File setStorageWhere(\string|\Bitrix\Main\DB\SqlExpression $storageWhere)
	 * @method bool hasStorageWhere()
	 * @method bool isStorageWhereFilled()
	 * @method bool isStorageWhereChanged()
	 * @method \string remindActualStorageWhere()
	 * @method \string requireStorageWhere()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File resetStorageWhere()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File unsetStorageWhere()
	 * @method \string fillStorageWhere()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_File set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_File reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_File unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_File wakeUp($data)
	 */
	class EO_File {
		/* @var \Bitrix\DocumentGenerator\Model\FileTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\FileTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_File_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getStorageTypeList()
	 * @method \string[] fillStorageType()
	 * @method \string[] getStorageWhereList()
	 * @method \string[] fillStorageWhere()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_File $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_File $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_File getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_File[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_File $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_File_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_File current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_File_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\FileTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\FileTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_File_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_File_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_File fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File_Collection fetchCollection()
	 */
	class EO_File_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_File createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_File_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_File wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_File_Collection wakeUpCollection($rows)
	 */
	class EO_File_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\RegionTable:documentgenerator/lib/model/region.php:353d8a89bca8ce3ae54d20a129c11439 */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Region
	 * @see \Bitrix\DocumentGenerator\Model\RegionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region resetTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getLanguageId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region setLanguageId(\string|\Bitrix\Main\DB\SqlExpression $languageId)
	 * @method bool hasLanguageId()
	 * @method bool isLanguageIdFilled()
	 * @method bool isLanguageIdChanged()
	 * @method \string remindActualLanguageId()
	 * @method \string requireLanguageId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region resetLanguageId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region unsetLanguageId()
	 * @method \string fillLanguageId()
	 * @method \string getCode()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region unsetCode()
	 * @method \string fillCode()
	 * @method \string getFormatDate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region setFormatDate(\string|\Bitrix\Main\DB\SqlExpression $formatDate)
	 * @method bool hasFormatDate()
	 * @method bool isFormatDateFilled()
	 * @method bool isFormatDateChanged()
	 * @method \string remindActualFormatDate()
	 * @method \string requireFormatDate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region resetFormatDate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region unsetFormatDate()
	 * @method \string fillFormatDate()
	 * @method \string getFormatDatetime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region setFormatDatetime(\string|\Bitrix\Main\DB\SqlExpression $formatDatetime)
	 * @method bool hasFormatDatetime()
	 * @method bool isFormatDatetimeFilled()
	 * @method bool isFormatDatetimeChanged()
	 * @method \string remindActualFormatDatetime()
	 * @method \string requireFormatDatetime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region resetFormatDatetime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region unsetFormatDatetime()
	 * @method \string fillFormatDatetime()
	 * @method \string getFormatName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region setFormatName(\string|\Bitrix\Main\DB\SqlExpression $formatName)
	 * @method bool hasFormatName()
	 * @method bool isFormatNameFilled()
	 * @method bool isFormatNameChanged()
	 * @method \string remindActualFormatName()
	 * @method \string requireFormatName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region resetFormatName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region unsetFormatName()
	 * @method \string fillFormatName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template getTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template remindActualTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template requireTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region setTemplate(\Bitrix\DocumentGenerator\Model\EO_Template $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region resetTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region unsetTemplate()
	 * @method bool hasTemplate()
	 * @method bool isTemplateFilled()
	 * @method bool isTemplateChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template fillTemplate()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Region wakeUp($data)
	 */
	class EO_Region {
		/* @var \Bitrix\DocumentGenerator\Model\RegionTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RegionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Region_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getLanguageIdList()
	 * @method \string[] fillLanguageId()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getFormatDateList()
	 * @method \string[] fillFormatDate()
	 * @method \string[] getFormatDatetimeList()
	 * @method \string[] fillFormatDatetime()
	 * @method \string[] getFormatNameList()
	 * @method \string[] fillFormatName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template[] getTemplateList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region_Collection getTemplateCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection fillTemplate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_Region $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_Region $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_Region $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Region_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Region_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\RegionTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RegionTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Region_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Region_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region_Collection fetchCollection()
	 */
	class EO_Region_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region_Collection wakeUpCollection($rows)
	 */
	class EO_Region_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\RegionPhraseTable:documentgenerator/lib/model/regionphrase.php:60ebbac436ce937276be4907599bcc6b */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_RegionPhrase
	 * @see \Bitrix\DocumentGenerator\Model\RegionPhraseTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRegionId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase setRegionId(\int|\Bitrix\Main\DB\SqlExpression $regionId)
	 * @method bool hasRegionId()
	 * @method bool isRegionIdFilled()
	 * @method bool isRegionIdChanged()
	 * @method \int remindActualRegionId()
	 * @method \int requireRegionId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase resetRegionId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase unsetRegionId()
	 * @method \int fillRegionId()
	 * @method \string getCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase resetCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase unsetCode()
	 * @method \string fillCode()
	 * @method \string getPhrase()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase setPhrase(\string|\Bitrix\Main\DB\SqlExpression $phrase)
	 * @method bool hasPhrase()
	 * @method bool isPhraseFilled()
	 * @method bool isPhraseChanged()
	 * @method \string remindActualPhrase()
	 * @method \string requirePhrase()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase resetPhrase()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase unsetPhrase()
	 * @method \string fillPhrase()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region getRegion()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region remindActualRegion()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region requireRegion()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase setRegion(\Bitrix\DocumentGenerator\Model\EO_Region $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase resetRegion()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase unsetRegion()
	 * @method bool hasRegion()
	 * @method bool isRegionFilled()
	 * @method bool isRegionChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region fillRegion()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_RegionPhrase wakeUp($data)
	 */
	class EO_RegionPhrase {
		/* @var \Bitrix\DocumentGenerator\Model\RegionPhraseTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RegionPhraseTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_RegionPhrase_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRegionIdList()
	 * @method \int[] fillRegionId()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getPhraseList()
	 * @method \string[] fillPhrase()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region[] getRegionList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase_Collection getRegionCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Region_Collection fillRegion()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_RegionPhrase $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_RegionPhrase $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_RegionPhrase $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_RegionPhrase_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RegionPhrase_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\RegionPhraseTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RegionPhraseTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RegionPhrase_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RegionPhrase_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase_Collection fetchCollection()
	 */
	class EO_RegionPhrase_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RegionPhrase_Collection wakeUpCollection($rows)
	 */
	class EO_RegionPhrase_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\RoleTable:documentgenerator/lib/model/roletable.php:2d79026cff42f062323a3e6ada48c52b */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Role
	 * @see \Bitrix\DocumentGenerator\Model\RoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\Role setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\DocumentGenerator\Model\Role setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\DocumentGenerator\Model\Role resetName()
	 * @method \Bitrix\DocumentGenerator\Model\Role unsetName()
	 * @method \string fillName()
	 * @method \string getCode()
	 * @method \Bitrix\DocumentGenerator\Model\Role setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\DocumentGenerator\Model\Role resetCode()
	 * @method \Bitrix\DocumentGenerator\Model\Role unsetCode()
	 * @method \string fillCode()
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
	 * @method \Bitrix\DocumentGenerator\Model\Role set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\Role reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\Role unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\Role wakeUp($data)
	 */
	class EO_Role {
		/* @var \Bitrix\DocumentGenerator\Model\RoleTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Role_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\Role $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\Role $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\Role getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\Role[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\Role $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Role_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\Role current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Role_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\RoleTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RoleTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Role_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\Role fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Role_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Role_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\Role fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Role_Collection fetchCollection()
	 */
	class EO_Role_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\Role createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Role_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\Role wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Role_Collection wakeUpCollection($rows)
	 */
	class EO_Role_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\RoleAccessTable:documentgenerator/lib/model/roleaccesstable.php:8b1fdd41463819590b0a35d8d05d5117 */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_RoleAccess
	 * @see \Bitrix\DocumentGenerator\Model\RoleAccessTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess resetRoleId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess resetAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\Role getRole()
	 * @method \Bitrix\DocumentGenerator\Model\Role remindActualRole()
	 * @method \Bitrix\DocumentGenerator\Model\Role requireRole()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess setRole(\Bitrix\DocumentGenerator\Model\Role $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess resetRole()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\DocumentGenerator\Model\Role fillRole()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_RoleAccess wakeUp($data)
	 */
	class EO_RoleAccess {
		/* @var \Bitrix\DocumentGenerator\Model\RoleAccessTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RoleAccessTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_RoleAccess_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\Role[] getRoleList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess_Collection getRoleCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Role_Collection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_RoleAccess $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_RoleAccess $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_RoleAccess $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_RoleAccess_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RoleAccess_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\RoleAccessTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RoleAccessTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RoleAccess_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RoleAccess_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess_Collection fetchCollection()
	 */
	class EO_RoleAccess_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess_Collection wakeUpCollection($rows)
	 */
	class EO_RoleAccess_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\RolePermissionTable:documentgenerator/lib/model/rolepermissiontable.php:a62a0d4c547162bb4fffcfaffc84e26a */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_RolePermission
	 * @see \Bitrix\DocumentGenerator\Model\RolePermissionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission resetRoleId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getEntity()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission setEntity(\string|\Bitrix\Main\DB\SqlExpression $entity)
	 * @method bool hasEntity()
	 * @method bool isEntityFilled()
	 * @method bool isEntityChanged()
	 * @method \string remindActualEntity()
	 * @method \string requireEntity()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission resetEntity()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission unsetEntity()
	 * @method \string fillEntity()
	 * @method \string getAction()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission resetAction()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission unsetAction()
	 * @method \string fillAction()
	 * @method \string getPermission()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission setPermission(\string|\Bitrix\Main\DB\SqlExpression $permission)
	 * @method bool hasPermission()
	 * @method bool isPermissionFilled()
	 * @method bool isPermissionChanged()
	 * @method \string remindActualPermission()
	 * @method \string requirePermission()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission resetPermission()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission unsetPermission()
	 * @method \string fillPermission()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess getRoleAccess()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess remindActualRoleAccess()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess requireRoleAccess()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission setRoleAccess(\Bitrix\DocumentGenerator\Model\EO_RoleAccess $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission resetRoleAccess()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission unsetRoleAccess()
	 * @method bool hasRoleAccess()
	 * @method bool isRoleAccessFilled()
	 * @method bool isRoleAccessChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess fillRoleAccess()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role getRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role remindActualRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role requireRole()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission setRole(\Bitrix\ImOpenLines\Model\EO_Role $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission resetRole()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role fillRole()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_RolePermission wakeUp($data)
	 */
	class EO_RolePermission {
		/* @var \Bitrix\DocumentGenerator\Model\RolePermissionTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RolePermissionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_RolePermission_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getEntityList()
	 * @method \string[] fillEntity()
	 * @method \string[] getActionList()
	 * @method \string[] fillAction()
	 * @method \string[] getPermissionList()
	 * @method \string[] fillPermission()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess[] getRoleAccessList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission_Collection getRoleAccessCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RoleAccess_Collection fillRoleAccess()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role[] getRoleList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission_Collection getRoleCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role_Collection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_RolePermission $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_RolePermission $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_RolePermission $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_RolePermission_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RolePermission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\RolePermissionTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\RolePermissionTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RolePermission_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RolePermission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission_Collection fetchCollection()
	 */
	class EO_RolePermission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_RolePermission_Collection wakeUpCollection($rows)
	 */
	class EO_RolePermission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\SpreadsheetTable:documentgenerator/lib/model/spreadsheet.php:215ae81f1a64a78984f113bd16990696 */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Spreadsheet
	 * @see \Bitrix\DocumentGenerator\Model\SpreadsheetTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFieldId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet setFieldId(\int|\Bitrix\Main\DB\SqlExpression $fieldId)
	 * @method bool hasFieldId()
	 * @method bool isFieldIdFilled()
	 * @method bool isFieldIdChanged()
	 * @method \int remindActualFieldId()
	 * @method \int requireFieldId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet resetFieldId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet unsetFieldId()
	 * @method \int fillFieldId()
	 * @method \string getTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet resetTitle()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getPlaceholder()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet setPlaceholder(\string|\Bitrix\Main\DB\SqlExpression $placeholder)
	 * @method bool hasPlaceholder()
	 * @method bool isPlaceholderFilled()
	 * @method bool isPlaceholderChanged()
	 * @method \string remindActualPlaceholder()
	 * @method \string requirePlaceholder()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet resetPlaceholder()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet unsetPlaceholder()
	 * @method \string fillPlaceholder()
	 * @method \string getEntityName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet setEntityName(\string|\Bitrix\Main\DB\SqlExpression $entityName)
	 * @method bool hasEntityName()
	 * @method bool isEntityNameFilled()
	 * @method bool isEntityNameChanged()
	 * @method \string remindActualEntityName()
	 * @method \string requireEntityName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet resetEntityName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet unsetEntityName()
	 * @method \string fillEntityName()
	 * @method \string getValue()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet resetValue()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet unsetValue()
	 * @method \string fillValue()
	 * @method \int getSort()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet resetSort()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet unsetSort()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Spreadsheet wakeUp($data)
	 */
	class EO_Spreadsheet {
		/* @var \Bitrix\DocumentGenerator\Model\SpreadsheetTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\SpreadsheetTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Spreadsheet_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFieldIdList()
	 * @method \int[] fillFieldId()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getPlaceholderList()
	 * @method \string[] fillPlaceholder()
	 * @method \string[] getEntityNameList()
	 * @method \string[] fillEntityName()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_Spreadsheet $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_Spreadsheet $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_Spreadsheet $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Spreadsheet_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Spreadsheet_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\SpreadsheetTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\SpreadsheetTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Spreadsheet_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Spreadsheet_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet_Collection fetchCollection()
	 */
	class EO_Spreadsheet_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Spreadsheet_Collection wakeUpCollection($rows)
	 */
	class EO_Spreadsheet_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\TemplateTable:documentgenerator/lib/model/template.php:3289b38e0df7a40c767ce8bd13c46bc9 */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Template
	 * @see \Bitrix\DocumentGenerator\Model\TemplateTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \boolean getActive()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetActive()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetActive()
	 * @method \boolean fillActive()
	 * @method \string getName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetName()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetName()
	 * @method \string fillName()
	 * @method \string getCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetCode()
	 * @method \string fillCode()
	 * @method \string getRegion()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setRegion(\string|\Bitrix\Main\DB\SqlExpression $region)
	 * @method bool hasRegion()
	 * @method bool isRegionFilled()
	 * @method bool isRegionChanged()
	 * @method \string remindActualRegion()
	 * @method \string requireRegion()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetRegion()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetRegion()
	 * @method \string fillRegion()
	 * @method \int getSort()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetSort()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetSort()
	 * @method \int fillSort()
	 * @method \Bitrix\Main\Type\DateTime getCreateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setCreateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createTime)
	 * @method bool hasCreateTime()
	 * @method bool isCreateTimeFilled()
	 * @method bool isCreateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreateTime()
	 * @method \Bitrix\Main\Type\DateTime requireCreateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetCreateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetCreateTime()
	 * @method \Bitrix\Main\Type\DateTime fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetUpdateTime()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetCreatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setUpdatedBy(\int|\Bitrix\Main\DB\SqlExpression $updatedBy)
	 * @method bool hasUpdatedBy()
	 * @method bool isUpdatedByFilled()
	 * @method bool isUpdatedByChanged()
	 * @method \int remindActualUpdatedBy()
	 * @method \int requireUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetUpdatedBy()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetUpdatedBy()
	 * @method \int fillUpdatedBy()
	 * @method \string getModuleId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetModuleId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetModuleId()
	 * @method \string fillModuleId()
	 * @method \int getFileId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetFileId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetFileId()
	 * @method \int fillFileId()
	 * @method \string getBodyType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setBodyType(\string|\Bitrix\Main\DB\SqlExpression $bodyType)
	 * @method bool hasBodyType()
	 * @method bool isBodyTypeFilled()
	 * @method bool isBodyTypeChanged()
	 * @method \string remindActualBodyType()
	 * @method \string requireBodyType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetBodyType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetBodyType()
	 * @method \string fillBodyType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider getProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider remindActualProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider requireProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setProvider(\Bitrix\DocumentGenerator\Model\EO_TemplateProvider $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetProvider()
	 * @method bool hasProvider()
	 * @method bool isProviderFilled()
	 * @method bool isProviderChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider fillProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser getUser()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser remindActualUser()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser requireUser()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setUser(\Bitrix\DocumentGenerator\Model\EO_TemplateUser $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetUser()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser fillUser()
	 * @method \int getNumeratorId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setNumeratorId(\int|\Bitrix\Main\DB\SqlExpression $numeratorId)
	 * @method bool hasNumeratorId()
	 * @method bool isNumeratorIdFilled()
	 * @method bool isNumeratorIdChanged()
	 * @method \int remindActualNumeratorId()
	 * @method \int requireNumeratorId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetNumeratorId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetNumeratorId()
	 * @method \int fillNumeratorId()
	 * @method \boolean getWithStamps()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setWithStamps(\boolean|\Bitrix\Main\DB\SqlExpression $withStamps)
	 * @method bool hasWithStamps()
	 * @method bool isWithStampsFilled()
	 * @method bool isWithStampsChanged()
	 * @method \boolean remindActualWithStamps()
	 * @method \boolean requireWithStamps()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetWithStamps()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetWithStamps()
	 * @method \boolean fillWithStamps()
	 * @method \boolean getIsDeleted()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template setIsDeleted(\boolean|\Bitrix\Main\DB\SqlExpression $isDeleted)
	 * @method bool hasIsDeleted()
	 * @method bool isIsDeletedFilled()
	 * @method bool isIsDeletedChanged()
	 * @method \boolean remindActualIsDeleted()
	 * @method \boolean requireIsDeleted()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template resetIsDeleted()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unsetIsDeleted()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Template wakeUp($data)
	 */
	class EO_Template {
		/* @var \Bitrix\DocumentGenerator\Model\TemplateTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\TemplateTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_Template_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getRegionList()
	 * @method \string[] fillRegion()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \Bitrix\Main\Type\DateTime[] getCreateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreateTime()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getUpdatedByList()
	 * @method \int[] fillUpdatedBy()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \string[] getBodyTypeList()
	 * @method \string[] fillBodyType()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider[] getProviderList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection getProviderCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider_Collection fillProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser[] getUserList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection getUserCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser_Collection fillUser()
	 * @method \int[] getNumeratorIdList()
	 * @method \int[] fillNumeratorId()
	 * @method \boolean[] getWithStampsList()
	 * @method \boolean[] fillWithStamps()
	 * @method \boolean[] getIsDeletedList()
	 * @method \boolean[] fillIsDeleted()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_Template $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_Template $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_Template $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_Template_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Template_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\TemplateTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\TemplateTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Template_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Template_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection fetchCollection()
	 */
	class EO_Template_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection wakeUpCollection($rows)
	 */
	class EO_Template_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\TemplateProviderTable:documentgenerator/lib/model/templateprovider.php:6e3bb49fa38f0dfb9e1c179537d6ae75 */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_TemplateProvider
	 * @see \Bitrix\DocumentGenerator\Model\TemplateProviderTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTemplateId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \string getProvider()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider setProvider(\string|\Bitrix\Main\DB\SqlExpression $provider)
	 * @method bool hasProvider()
	 * @method bool isProviderFilled()
	 * @method bool isProviderChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template getTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template remindActualTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template requireTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider setTemplate(\Bitrix\DocumentGenerator\Model\EO_Template $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider resetTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider unsetTemplate()
	 * @method bool hasTemplate()
	 * @method bool isTemplateFilled()
	 * @method bool isTemplateChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template fillTemplate()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateProvider wakeUp($data)
	 */
	class EO_TemplateProvider {
		/* @var \Bitrix\DocumentGenerator\Model\TemplateProviderTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\TemplateProviderTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_TemplateProvider_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTemplateIdList()
	 * @method \string[] getProviderList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template[] getTemplateList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider_Collection getTemplateCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection fillTemplate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_TemplateProvider $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_TemplateProvider $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_TemplateProvider $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateProvider_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TemplateProvider_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\TemplateProviderTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\TemplateProviderTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TemplateProvider_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TemplateProvider_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider_Collection fetchCollection()
	 */
	class EO_TemplateProvider_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateProvider_Collection wakeUpCollection($rows)
	 */
	class EO_TemplateProvider_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\DocumentGenerator\Model\TemplateUserTable:documentgenerator/lib/model/templateuser.php:6fc0699370f5d8cfd4a3091013f0d9bc */
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_TemplateUser
	 * @see \Bitrix\DocumentGenerator\Model\TemplateUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTemplateId()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \string getAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser resetAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template getTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template remindActualTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template requireTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser setTemplate(\Bitrix\DocumentGenerator\Model\EO_Template $object)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser resetTemplate()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser unsetTemplate()
	 * @method bool hasTemplate()
	 * @method bool isTemplateFilled()
	 * @method bool isTemplateChanged()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template fillTemplate()
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
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser set($fieldName, $value)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser reset($fieldName)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateUser wakeUp($data)
	 */
	class EO_TemplateUser {
		/* @var \Bitrix\DocumentGenerator\Model\TemplateUserTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\TemplateUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * EO_TemplateUser_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTemplateIdList()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template[] getTemplateList()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser_Collection getTemplateCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_Template_Collection fillTemplate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\DocumentGenerator\Model\EO_TemplateUser $object)
	 * @method bool has(\Bitrix\DocumentGenerator\Model\EO_TemplateUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser getByPrimary($primary)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser[] getAll()
	 * @method bool remove(\Bitrix\DocumentGenerator\Model\EO_TemplateUser $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateUser_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TemplateUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\DocumentGenerator\Model\TemplateUserTable */
		static public $dataClass = '\Bitrix\DocumentGenerator\Model\TemplateUserTable';
	}
}
namespace Bitrix\DocumentGenerator\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TemplateUser_Result exec()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TemplateUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser fetchObject()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser_Collection fetchCollection()
	 */
	class EO_TemplateUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser createObject($setDefaultValues = true)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser_Collection createCollection()
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser wakeUpObject($row)
	 * @method \Bitrix\DocumentGenerator\Model\EO_TemplateUser_Collection wakeUpCollection($rows)
	 */
	class EO_TemplateUser_Entity extends \Bitrix\Main\ORM\Entity {}
}