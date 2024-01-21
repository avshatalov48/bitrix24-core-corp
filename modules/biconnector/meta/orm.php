<?php

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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method EO_DashboardUser_Collection merge(?EO_DashboardUser_Collection $collection)
	 * @method bool isEmpty()
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
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable:biconnector/lib/integration/superset/model/supersetdashboardtable.php */
namespace Bitrix\BIConnector\Integration\Superset\Model {
	/**
	 * EO_SupersetDashboard
	 * @see \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getExternalId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setExternalId(\int|\Bitrix\Main\DB\SqlExpression $externalId)
	 * @method bool hasExternalId()
	 * @method bool isExternalIdFilled()
	 * @method bool isExternalIdChanged()
	 * @method \int remindActualExternalId()
	 * @method \int requireExternalId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetExternalId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetExternalId()
	 * @method \int fillExternalId()
	 * @method \string getStatus()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetStatus()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetStatus()
	 * @method \string fillStatus()
	 * @method \string getTitle()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetTitle()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetTitle()
	 * @method \string fillTitle()
	 * @method ?\Bitrix\Main\Type\Date getDateFilterStart()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setDateFilterStart(?\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateFilterStart)
	 * @method bool hasDateFilterStart()
	 * @method bool isDateFilterStartFilled()
	 * @method bool isDateFilterStartChanged()
	 * @method ?\Bitrix\Main\Type\Date remindActualDateFilterStart()
	 * @method ?\Bitrix\Main\Type\Date requireDateFilterStart()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetDateFilterStart()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetDateFilterStart()
	 * @method ?\Bitrix\Main\Type\Date fillDateFilterStart()
	 * @method ?\Bitrix\Main\Type\Date getDateFilterEnd()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setDateFilterEnd(?\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateFilterEnd)
	 * @method bool hasDateFilterEnd()
	 * @method bool isDateFilterEndFilled()
	 * @method bool isDateFilterEndChanged()
	 * @method ?\Bitrix\Main\Type\Date remindActualDateFilterEnd()
	 * @method ?\Bitrix\Main\Type\Date requireDateFilterEnd()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetDateFilterEnd()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetDateFilterEnd()
	 * @method ?\Bitrix\Main\Type\Date fillDateFilterEnd()
	 * @method \string getType()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetType()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetType()
	 * @method \string fillType()
	 * @method \string getFilterPeriod()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setFilterPeriod(\string|\Bitrix\Main\DB\SqlExpression $filterPeriod)
	 * @method bool hasFilterPeriod()
	 * @method bool isFilterPeriodFilled()
	 * @method bool isFilterPeriodChanged()
	 * @method \string remindActualFilterPeriod()
	 * @method \string requireFilterPeriod()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetFilterPeriod()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetFilterPeriod()
	 * @method \string fillFilterPeriod()
	 * @method \string getAppId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setAppId(\string|\Bitrix\Main\DB\SqlExpression $appId)
	 * @method bool hasAppId()
	 * @method bool isAppIdFilled()
	 * @method bool isAppIdChanged()
	 * @method \string remindActualAppId()
	 * @method \string requireAppId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetAppId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetAppId()
	 * @method \string fillAppId()
	 * @method \Bitrix\Rest\EO_App getApp()
	 * @method \Bitrix\Rest\EO_App remindActualApp()
	 * @method \Bitrix\Rest\EO_App requireApp()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setApp(\Bitrix\Rest\EO_App $object)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetApp()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetApp()
	 * @method bool hasApp()
	 * @method bool isAppFilled()
	 * @method bool isAppChanged()
	 * @method \Bitrix\Rest\EO_App fillApp()
	 * @method \int getSourceId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setSourceId(\int|\Bitrix\Main\DB\SqlExpression $sourceId)
	 * @method bool hasSourceId()
	 * @method bool isSourceIdFilled()
	 * @method bool isSourceIdChanged()
	 * @method \int remindActualSourceId()
	 * @method \int requireSourceId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetSourceId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetSourceId()
	 * @method \int fillSourceId()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard getSource()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard remindActualSource()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard requireSource()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setSource(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard $object)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetSource()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetSource()
	 * @method bool hasSource()
	 * @method bool isSourceFilled()
	 * @method bool isSourceChanged()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard fillSource()
	 * @method \Bitrix\Main\Type\Date getDateCreate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setDateCreate(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\Date remindActualDateCreate()
	 * @method \Bitrix\Main\Type\Date requireDateCreate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetDateCreate()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetDateCreate()
	 * @method \Bitrix\Main\Type\Date fillDateCreate()
	 * @method ?\int getCreatedById()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard setCreatedById(?\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method ?\int remindActualCreatedById()
	 * @method ?\int requireCreatedById()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard resetCreatedById()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unsetCreatedById()
	 * @method ?\int fillCreatedById()
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
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard set($fieldName, $value)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard reset($fieldName)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard wakeUp($data)
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
	 * @method ?\Bitrix\Main\Type\Date[] getDateFilterStartList()
	 * @method ?\Bitrix\Main\Type\Date[] fillDateFilterStart()
	 * @method ?\Bitrix\Main\Type\Date[] getDateFilterEndList()
	 * @method ?\Bitrix\Main\Type\Date[] fillDateFilterEnd()
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
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard[] getSourceList()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection getSourceCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection fillSource()
	 * @method \Bitrix\Main\Type\Date[] getDateCreateList()
	 * @method \Bitrix\Main\Type\Date[] fillDateCreate()
	 * @method ?\int[] getCreatedByIdList()
	 * @method ?\int[] fillCreatedById()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard $object)
	 * @method bool has(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard getByPrimary($primary)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard[] getAll()
	 * @method bool remove(\Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_SupersetDashboard_Collection merge(?EO_SupersetDashboard_Collection $collection)
	 * @method bool isEmpty()
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
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_SupersetDashboard_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard fetchObject()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection fetchCollection()
	 */
	class EO_SupersetDashboard_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard createObject($setDefaultValues = true)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection createCollection()
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard wakeUpObject($row)
	 * @method \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection wakeUpCollection($rows)
	 */
	class EO_SupersetDashboard_Entity extends \Bitrix\Main\ORM\Entity {}
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method EO_Log_Collection merge(?EO_Log_Collection $collection)
	 * @method bool isEmpty()
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
	 *
	 * Custom methods:
	 * ---------------
	 *
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method EO_Key_Collection merge(?EO_Key_Collection $collection)
	 * @method bool isEmpty()
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
	 *
	 * Custom methods:
	 * ---------------
	 *
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method EO_DictionaryData_Collection merge(?EO_DictionaryData_Collection $collection)
	 * @method bool isEmpty()
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
	 *
	 * Custom methods:
	 * ---------------
	 *
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method EO_KeyUser_Collection merge(?EO_KeyUser_Collection $collection)
	 * @method bool isEmpty()
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
	 *
	 * Custom methods:
	 * ---------------
	 *
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method EO_Dashboard_Collection merge(?EO_Dashboard_Collection $collection)
	 * @method bool isEmpty()
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
	 *
	 * Custom methods:
	 * ---------------
	 *
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method EO_DictionaryCache_Collection merge(?EO_DictionaryCache_Collection $collection)
	 * @method bool isEmpty()
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
	 *
	 * Custom methods:
	 * ---------------
	 *
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