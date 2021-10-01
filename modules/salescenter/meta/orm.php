<?php

/* ORMENTITYANNOTATION:Bitrix\SalesCenter\Model\MetaTable:salescenter/lib/model/metatable.php:71a781b9107d1749a9221475af0f359d */
namespace Bitrix\SalesCenter\Model {
	/**
	 * Meta
	 * @see \Bitrix\SalesCenter\Model\MetaTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\SalesCenter\Model\Meta setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getHash()
	 * @method \Bitrix\SalesCenter\Model\Meta setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\SalesCenter\Model\Meta resetHash()
	 * @method \Bitrix\SalesCenter\Model\Meta unsetHash()
	 * @method \string fillHash()
	 * @method \int getHashCrc()
	 * @method \Bitrix\SalesCenter\Model\Meta setHashCrc(\int|\Bitrix\Main\DB\SqlExpression $hashCrc)
	 * @method bool hasHashCrc()
	 * @method bool isHashCrcFilled()
	 * @method bool isHashCrcChanged()
	 * @method \int remindActualHashCrc()
	 * @method \int requireHashCrc()
	 * @method \Bitrix\SalesCenter\Model\Meta resetHashCrc()
	 * @method \Bitrix\SalesCenter\Model\Meta unsetHashCrc()
	 * @method \int fillHashCrc()
	 * @method \int getUserId()
	 * @method \Bitrix\SalesCenter\Model\Meta setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\SalesCenter\Model\Meta resetUserId()
	 * @method \Bitrix\SalesCenter\Model\Meta unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getMeta()
	 * @method \Bitrix\SalesCenter\Model\Meta setMeta(\string|\Bitrix\Main\DB\SqlExpression $meta)
	 * @method bool hasMeta()
	 * @method bool isMetaFilled()
	 * @method bool isMetaChanged()
	 * @method \string remindActualMeta()
	 * @method \string requireMeta()
	 * @method \Bitrix\SalesCenter\Model\Meta resetMeta()
	 * @method \Bitrix\SalesCenter\Model\Meta unsetMeta()
	 * @method \string fillMeta()
	 * @method \int getMetaCrc()
	 * @method \Bitrix\SalesCenter\Model\Meta setMetaCrc(\int|\Bitrix\Main\DB\SqlExpression $metaCrc)
	 * @method bool hasMetaCrc()
	 * @method bool isMetaCrcFilled()
	 * @method bool isMetaCrcChanged()
	 * @method \int remindActualMetaCrc()
	 * @method \int requireMetaCrc()
	 * @method \Bitrix\SalesCenter\Model\Meta resetMetaCrc()
	 * @method \Bitrix\SalesCenter\Model\Meta unsetMetaCrc()
	 * @method \int fillMetaCrc()
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
	 * @method \Bitrix\SalesCenter\Model\Meta set($fieldName, $value)
	 * @method \Bitrix\SalesCenter\Model\Meta reset($fieldName)
	 * @method \Bitrix\SalesCenter\Model\Meta unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\SalesCenter\Model\Meta wakeUp($data)
	 */
	class EO_Meta {
		/* @var \Bitrix\SalesCenter\Model\MetaTable */
		static public $dataClass = '\Bitrix\SalesCenter\Model\MetaTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\SalesCenter\Model {
	/**
	 * EO_Meta_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \int[] getHashCrcList()
	 * @method \int[] fillHashCrc()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getMetaList()
	 * @method \string[] fillMeta()
	 * @method \int[] getMetaCrcList()
	 * @method \int[] fillMetaCrc()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\SalesCenter\Model\Meta $object)
	 * @method bool has(\Bitrix\SalesCenter\Model\Meta $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\SalesCenter\Model\Meta getByPrimary($primary)
	 * @method \Bitrix\SalesCenter\Model\Meta[] getAll()
	 * @method bool remove(\Bitrix\SalesCenter\Model\Meta $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\SalesCenter\Model\EO_Meta_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\SalesCenter\Model\Meta current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Meta_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\SalesCenter\Model\MetaTable */
		static public $dataClass = '\Bitrix\SalesCenter\Model\MetaTable';
	}
}
namespace Bitrix\SalesCenter\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Meta_Result exec()
	 * @method \Bitrix\SalesCenter\Model\Meta fetchObject()
	 * @method \Bitrix\SalesCenter\Model\EO_Meta_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Meta_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\SalesCenter\Model\Meta fetchObject()
	 * @method \Bitrix\SalesCenter\Model\EO_Meta_Collection fetchCollection()
	 */
	class EO_Meta_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\SalesCenter\Model\Meta createObject($setDefaultValues = true)
	 * @method \Bitrix\SalesCenter\Model\EO_Meta_Collection createCollection()
	 * @method \Bitrix\SalesCenter\Model\Meta wakeUpObject($row)
	 * @method \Bitrix\SalesCenter\Model\EO_Meta_Collection wakeUpCollection($rows)
	 */
	class EO_Meta_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\SalesCenter\Model\PageTable:salescenter/lib/model/pagetable.php:cd8d33307572d5bb1a81313fc56e8d54 */
namespace Bitrix\SalesCenter\Model {
	/**
	 * Page
	 * @see \Bitrix\SalesCenter\Model\PageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\SalesCenter\Model\Page setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\SalesCenter\Model\Page setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\SalesCenter\Model\Page resetName()
	 * @method \Bitrix\SalesCenter\Model\Page unsetName()
	 * @method \string fillName()
	 * @method \string getUrl()
	 * @method \Bitrix\SalesCenter\Model\Page setUrl(\string|\Bitrix\Main\DB\SqlExpression $url)
	 * @method bool hasUrl()
	 * @method bool isUrlFilled()
	 * @method bool isUrlChanged()
	 * @method \string remindActualUrl()
	 * @method \string requireUrl()
	 * @method \Bitrix\SalesCenter\Model\Page resetUrl()
	 * @method \Bitrix\SalesCenter\Model\Page unsetUrl()
	 * @method \string fillUrl()
	 * @method \int getLandingId()
	 * @method \Bitrix\SalesCenter\Model\Page setLandingId(\int|\Bitrix\Main\DB\SqlExpression $landingId)
	 * @method bool hasLandingId()
	 * @method bool isLandingIdFilled()
	 * @method bool isLandingIdChanged()
	 * @method \int remindActualLandingId()
	 * @method \int requireLandingId()
	 * @method \Bitrix\SalesCenter\Model\Page resetLandingId()
	 * @method \Bitrix\SalesCenter\Model\Page unsetLandingId()
	 * @method \int fillLandingId()
	 * @method \boolean getHidden()
	 * @method \Bitrix\SalesCenter\Model\Page setHidden(\boolean|\Bitrix\Main\DB\SqlExpression $hidden)
	 * @method bool hasHidden()
	 * @method bool isHiddenFilled()
	 * @method bool isHiddenChanged()
	 * @method \boolean remindActualHidden()
	 * @method \boolean requireHidden()
	 * @method \Bitrix\SalesCenter\Model\Page resetHidden()
	 * @method \Bitrix\SalesCenter\Model\Page unsetHidden()
	 * @method \boolean fillHidden()
	 * @method \boolean getIsWebform()
	 * @method \Bitrix\SalesCenter\Model\Page setIsWebform(\boolean|\Bitrix\Main\DB\SqlExpression $isWebform)
	 * @method bool hasIsWebform()
	 * @method bool isIsWebformFilled()
	 * @method bool isIsWebformChanged()
	 * @method \boolean remindActualIsWebform()
	 * @method \boolean requireIsWebform()
	 * @method \Bitrix\SalesCenter\Model\Page resetIsWebform()
	 * @method \Bitrix\SalesCenter\Model\Page unsetIsWebform()
	 * @method \boolean fillIsWebform()
	 * @method \boolean getIsFrameDenied()
	 * @method \Bitrix\SalesCenter\Model\Page setIsFrameDenied(\boolean|\Bitrix\Main\DB\SqlExpression $isFrameDenied)
	 * @method bool hasIsFrameDenied()
	 * @method bool isIsFrameDeniedFilled()
	 * @method bool isIsFrameDeniedChanged()
	 * @method \boolean remindActualIsFrameDenied()
	 * @method \boolean requireIsFrameDenied()
	 * @method \Bitrix\SalesCenter\Model\Page resetIsFrameDenied()
	 * @method \Bitrix\SalesCenter\Model\Page unsetIsFrameDenied()
	 * @method \boolean fillIsFrameDenied()
	 * @method \int getSort()
	 * @method \Bitrix\SalesCenter\Model\Page setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\SalesCenter\Model\Page resetSort()
	 * @method \Bitrix\SalesCenter\Model\Page unsetSort()
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
	 * @method \Bitrix\SalesCenter\Model\Page set($fieldName, $value)
	 * @method \Bitrix\SalesCenter\Model\Page reset($fieldName)
	 * @method \Bitrix\SalesCenter\Model\Page unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\SalesCenter\Model\Page wakeUp($data)
	 */
	class EO_Page {
		/* @var \Bitrix\SalesCenter\Model\PageTable */
		static public $dataClass = '\Bitrix\SalesCenter\Model\PageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\SalesCenter\Model {
	/**
	 * EO_Page_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getUrlList()
	 * @method \string[] fillUrl()
	 * @method \int[] getLandingIdList()
	 * @method \int[] fillLandingId()
	 * @method \boolean[] getHiddenList()
	 * @method \boolean[] fillHidden()
	 * @method \boolean[] getIsWebformList()
	 * @method \boolean[] fillIsWebform()
	 * @method \boolean[] getIsFrameDeniedList()
	 * @method \boolean[] fillIsFrameDenied()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\SalesCenter\Model\Page $object)
	 * @method bool has(\Bitrix\SalesCenter\Model\Page $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\SalesCenter\Model\Page getByPrimary($primary)
	 * @method \Bitrix\SalesCenter\Model\Page[] getAll()
	 * @method bool remove(\Bitrix\SalesCenter\Model\Page $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\SalesCenter\Model\EO_Page_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\SalesCenter\Model\Page current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Page_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\SalesCenter\Model\PageTable */
		static public $dataClass = '\Bitrix\SalesCenter\Model\PageTable';
	}
}
namespace Bitrix\SalesCenter\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Page_Result exec()
	 * @method \Bitrix\SalesCenter\Model\Page fetchObject()
	 * @method \Bitrix\SalesCenter\Model\EO_Page_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Page_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\SalesCenter\Model\Page fetchObject()
	 * @method \Bitrix\SalesCenter\Model\EO_Page_Collection fetchCollection()
	 */
	class EO_Page_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\SalesCenter\Model\Page createObject($setDefaultValues = true)
	 * @method \Bitrix\SalesCenter\Model\EO_Page_Collection createCollection()
	 * @method \Bitrix\SalesCenter\Model\Page wakeUpObject($row)
	 * @method \Bitrix\SalesCenter\Model\EO_Page_Collection wakeUpCollection($rows)
	 */
	class EO_Page_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\SalesCenter\Model\PageParamTable:salescenter/lib/model/pageparamtable.php:801ad5823ed12fc3448a753b5f4f4619 */
namespace Bitrix\SalesCenter\Model {
	/**
	 * EO_PageParam
	 * @see \Bitrix\SalesCenter\Model\PageParamTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getPageId()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam setPageId(\int|\Bitrix\Main\DB\SqlExpression $pageId)
	 * @method bool hasPageId()
	 * @method bool isPageIdFilled()
	 * @method bool isPageIdChanged()
	 * @method \int remindActualPageId()
	 * @method \int requirePageId()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam resetPageId()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam unsetPageId()
	 * @method \int fillPageId()
	 * @method \Bitrix\SalesCenter\Model\Page getPage()
	 * @method \Bitrix\SalesCenter\Model\Page remindActualPage()
	 * @method \Bitrix\SalesCenter\Model\Page requirePage()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam setPage(\Bitrix\SalesCenter\Model\Page $object)
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam resetPage()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam unsetPage()
	 * @method bool hasPage()
	 * @method bool isPageFilled()
	 * @method bool isPageChanged()
	 * @method \Bitrix\SalesCenter\Model\Page fillPage()
	 * @method \string getField()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam setField(\string|\Bitrix\Main\DB\SqlExpression $field)
	 * @method bool hasField()
	 * @method bool isFieldFilled()
	 * @method bool isFieldChanged()
	 * @method \string remindActualField()
	 * @method \string requireField()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam resetField()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam unsetField()
	 * @method \string fillField()
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
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam set($fieldName, $value)
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam reset($fieldName)
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\SalesCenter\Model\EO_PageParam wakeUp($data)
	 */
	class EO_PageParam {
		/* @var \Bitrix\SalesCenter\Model\PageParamTable */
		static public $dataClass = '\Bitrix\SalesCenter\Model\PageParamTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\SalesCenter\Model {
	/**
	 * EO_PageParam_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getPageIdList()
	 * @method \int[] fillPageId()
	 * @method \Bitrix\SalesCenter\Model\Page[] getPageList()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam_Collection getPageCollection()
	 * @method \Bitrix\SalesCenter\Model\EO_Page_Collection fillPage()
	 * @method \string[] getFieldList()
	 * @method \string[] fillField()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\SalesCenter\Model\EO_PageParam $object)
	 * @method bool has(\Bitrix\SalesCenter\Model\EO_PageParam $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam getByPrimary($primary)
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam[] getAll()
	 * @method bool remove(\Bitrix\SalesCenter\Model\EO_PageParam $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\SalesCenter\Model\EO_PageParam_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_PageParam_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\SalesCenter\Model\PageParamTable */
		static public $dataClass = '\Bitrix\SalesCenter\Model\PageParamTable';
	}
}
namespace Bitrix\SalesCenter\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_PageParam_Result exec()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam fetchObject()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_PageParam_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam fetchObject()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam_Collection fetchCollection()
	 */
	class EO_PageParam_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam createObject($setDefaultValues = true)
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam_Collection createCollection()
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam wakeUpObject($row)
	 * @method \Bitrix\SalesCenter\Model\EO_PageParam_Collection wakeUpCollection($rows)
	 */
	class EO_PageParam_Entity extends \Bitrix\Main\ORM\Entity {}
}