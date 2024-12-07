<?php

/* ORMENTITYANNOTATION:Bitrix\SignMobile\Model\SignMobileNotificationQueueTable:signmobile\lib\Model\SignMobileNotificationQueueTable.php */
namespace Bitrix\SignMobile\Model {
	/**
	 * EO_SignMobileNotificationQueue
	 * @see \Bitrix\SignMobile\Model\SignMobileNotificationQueueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue resetUserId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getSignMemberId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue setSignMemberId(\int|\Bitrix\Main\DB\SqlExpression $signMemberId)
	 * @method bool hasSignMemberId()
	 * @method bool isSignMemberIdFilled()
	 * @method bool isSignMemberIdChanged()
	 * @method \int remindActualSignMemberId()
	 * @method \int requireSignMemberId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue resetSignMemberId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue unsetSignMemberId()
	 * @method \int fillSignMemberId()
	 * @method \int getType()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue setType(\int|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \int remindActualType()
	 * @method \int requireType()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue resetType()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue unsetType()
	 * @method \int fillType()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue resetDateCreate()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue unsetDateCreate()
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
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue set($fieldName, $value)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue reset($fieldName)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue wakeUp($data)
	 */
	class EO_SignMobileNotificationQueue {
		/* @var \Bitrix\SignMobile\Model\SignMobileNotificationQueueTable */
		static public $dataClass = '\Bitrix\SignMobile\Model\SignMobileNotificationQueueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\SignMobile\Model {
	/**
	 * EO_SignMobileNotificationQueue_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getSignMemberIdList()
	 * @method \int[] fillSignMemberId()
	 * @method \int[] getTypeList()
	 * @method \int[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue $object)
	 * @method bool has(\Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue getByPrimary($primary)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue[] getAll()
	 * @method bool remove(\Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue_Collection merge(?\Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_SignMobileNotificationQueue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\SignMobile\Model\SignMobileNotificationQueueTable */
		static public $dataClass = '\Bitrix\SignMobile\Model\SignMobileNotificationQueueTable';
	}
}
namespace Bitrix\SignMobile\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SignMobileNotificationQueue_Result exec()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue fetchObject()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_SignMobileNotificationQueue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue fetchObject()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue_Collection fetchCollection()
	 */
	class EO_SignMobileNotificationQueue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue createObject($setDefaultValues = true)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue_Collection createCollection()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue wakeUpObject($row)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue_Collection wakeUpCollection($rows)
	 */
	class EO_SignMobileNotificationQueue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\SignMobile\Model\SignMobileNotificationsTable:signmobile\lib\Model\SignMobileNotificationsTable.php */
namespace Bitrix\SignMobile\Model {
	/**
	 * EO_SignMobileNotifications
	 * @see \Bitrix\SignMobile\Model\SignMobileNotificationsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications resetUserId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getSignMemberId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications setSignMemberId(\int|\Bitrix\Main\DB\SqlExpression $signMemberId)
	 * @method bool hasSignMemberId()
	 * @method bool isSignMemberIdFilled()
	 * @method bool isSignMemberIdChanged()
	 * @method \int remindActualSignMemberId()
	 * @method \int requireSignMemberId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications resetSignMemberId()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications unsetSignMemberId()
	 * @method \int fillSignMemberId()
	 * @method \int getType()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications setType(\int|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \int remindActualType()
	 * @method \int requireType()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications resetType()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications unsetType()
	 * @method \int fillType()
	 * @method \Bitrix\Main\Type\DateTime getDateUpdate()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications setDateUpdate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateUpdate)
	 * @method bool hasDateUpdate()
	 * @method bool isDateUpdateFilled()
	 * @method bool isDateUpdateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateUpdate()
	 * @method \Bitrix\Main\Type\DateTime requireDateUpdate()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications resetDateUpdate()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications unsetDateUpdate()
	 * @method \Bitrix\Main\Type\DateTime fillDateUpdate()
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
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications set($fieldName, $value)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications reset($fieldName)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotifications wakeUp($data)
	 */
	class EO_SignMobileNotifications {
		/* @var \Bitrix\SignMobile\Model\SignMobileNotificationsTable */
		static public $dataClass = '\Bitrix\SignMobile\Model\SignMobileNotificationsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\SignMobile\Model {
	/**
	 * EO_SignMobileNotifications_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getSignMemberIdList()
	 * @method \int[] fillSignMemberId()
	 * @method \int[] getTypeList()
	 * @method \int[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getDateUpdateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateUpdate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\SignMobile\Model\EO_SignMobileNotifications $object)
	 * @method bool has(\Bitrix\SignMobile\Model\EO_SignMobileNotifications $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications getByPrimary($primary)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications[] getAll()
	 * @method bool remove(\Bitrix\SignMobile\Model\EO_SignMobileNotifications $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotifications_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications_Collection merge(?\Bitrix\SignMobile\Model\EO_SignMobileNotifications_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_SignMobileNotifications_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\SignMobile\Model\SignMobileNotificationsTable */
		static public $dataClass = '\Bitrix\SignMobile\Model\SignMobileNotificationsTable';
	}
}
namespace Bitrix\SignMobile\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SignMobileNotifications_Result exec()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications fetchObject()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_SignMobileNotifications_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications fetchObject()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications_Collection fetchCollection()
	 */
	class EO_SignMobileNotifications_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications createObject($setDefaultValues = true)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications_Collection createCollection()
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications wakeUpObject($row)
	 * @method \Bitrix\SignMobile\Model\EO_SignMobileNotifications_Collection wakeUpCollection($rows)
	 */
	class EO_SignMobileNotifications_Entity extends \Bitrix\Main\ORM\Entity {}
}