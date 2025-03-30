<?php

/* ORMENTITYANNOTATION:Bitrix\StaffTrack\Model\UserStatisticsHashTable:stafftrack/lib/model/userstatisticshashtable.php */
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_UserStatisticsHash
	 * @see \Bitrix\StaffTrack\Model\UserStatisticsHashTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash resetUserId()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getHash()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash resetHash()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash unsetHash()
	 * @method \string fillHash()
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
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash set($fieldName, $value)
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash reset($fieldName)
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\StaffTrack\Model\EO_UserStatisticsHash wakeUp($data)
	 */
	class EO_UserStatisticsHash {
		/* @var \Bitrix\StaffTrack\Model\UserStatisticsHashTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\UserStatisticsHashTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_UserStatisticsHash_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\StaffTrack\Model\EO_UserStatisticsHash $object)
	 * @method bool has(\Bitrix\StaffTrack\Model\EO_UserStatisticsHash $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash getByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash[] getAll()
	 * @method bool remove(\Bitrix\StaffTrack\Model\EO_UserStatisticsHash $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\StaffTrack\Model\EO_UserStatisticsHash_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash_Collection merge(?\Bitrix\StaffTrack\Model\EO_UserStatisticsHash_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_UserStatisticsHash_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\StaffTrack\Model\UserStatisticsHashTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\UserStatisticsHashTable';
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UserStatisticsHash_Result exec()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_UserStatisticsHash_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash_Collection fetchCollection()
	 */
	class EO_UserStatisticsHash_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash createObject($setDefaultValues = true)
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash_Collection createCollection()
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash wakeUpObject($row)
	 * @method \Bitrix\StaffTrack\Model\EO_UserStatisticsHash_Collection wakeUpCollection($rows)
	 */
	class EO_UserStatisticsHash_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\StaffTrack\Model\ShiftGeoTable:stafftrack/lib/model/shiftgeotable.php */
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_ShiftGeo
	 * @see \Bitrix\StaffTrack\Model\ShiftGeoTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getShiftId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo setShiftId(\int|\Bitrix\Main\DB\SqlExpression $shiftId)
	 * @method bool hasShiftId()
	 * @method bool isShiftIdFilled()
	 * @method bool isShiftIdChanged()
	 * @method \int remindActualShiftId()
	 * @method \int requireShiftId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo resetShiftId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo unsetShiftId()
	 * @method \int fillShiftId()
	 * @method \string getImageUrl()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo setImageUrl(\string|\Bitrix\Main\DB\SqlExpression $imageUrl)
	 * @method bool hasImageUrl()
	 * @method bool isImageUrlFilled()
	 * @method bool isImageUrlChanged()
	 * @method \string remindActualImageUrl()
	 * @method \string requireImageUrl()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo resetImageUrl()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo unsetImageUrl()
	 * @method \string fillImageUrl()
	 * @method \string getAddress()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo setAddress(\string|\Bitrix\Main\DB\SqlExpression $address)
	 * @method bool hasAddress()
	 * @method bool isAddressFilled()
	 * @method bool isAddressChanged()
	 * @method \string remindActualAddress()
	 * @method \string requireAddress()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo resetAddress()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo unsetAddress()
	 * @method \string fillAddress()
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
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo set($fieldName, $value)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo reset($fieldName)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\StaffTrack\Model\EO_ShiftGeo wakeUp($data)
	 */
	class EO_ShiftGeo {
		/* @var \Bitrix\StaffTrack\Model\ShiftGeoTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\ShiftGeoTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_ShiftGeo_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getShiftIdList()
	 * @method \int[] fillShiftId()
	 * @method \string[] getImageUrlList()
	 * @method \string[] fillImageUrl()
	 * @method \string[] getAddressList()
	 * @method \string[] fillAddress()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\StaffTrack\Model\EO_ShiftGeo $object)
	 * @method bool has(\Bitrix\StaffTrack\Model\EO_ShiftGeo $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo getByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo[] getAll()
	 * @method bool remove(\Bitrix\StaffTrack\Model\EO_ShiftGeo $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection merge(?\Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_ShiftGeo_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\StaffTrack\Model\ShiftGeoTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\ShiftGeoTable';
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ShiftGeo_Result exec()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ShiftGeo_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection fetchCollection()
	 */
	class EO_ShiftGeo_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo createObject($setDefaultValues = true)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection createCollection()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo wakeUpObject($row)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection wakeUpCollection($rows)
	 */
	class EO_ShiftGeo_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\StaffTrack\Model\ShiftMessageTable:stafftrack/lib/model/shiftmessagetable.php */
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_ShiftMessage
	 * @see \Bitrix\StaffTrack\Model\ShiftMessageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getShiftId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage setShiftId(\int|\Bitrix\Main\DB\SqlExpression $shiftId)
	 * @method bool hasShiftId()
	 * @method bool isShiftIdFilled()
	 * @method bool isShiftIdChanged()
	 * @method \int remindActualShiftId()
	 * @method \int requireShiftId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage resetShiftId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage unsetShiftId()
	 * @method \int fillShiftId()
	 * @method \int getMessageId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage setMessageId(\int|\Bitrix\Main\DB\SqlExpression $messageId)
	 * @method bool hasMessageId()
	 * @method bool isMessageIdFilled()
	 * @method bool isMessageIdChanged()
	 * @method \int remindActualMessageId()
	 * @method \int requireMessageId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage resetMessageId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage unsetMessageId()
	 * @method \int fillMessageId()
	 * @method \Bitrix\StaffTrack\Model\Shift getShift()
	 * @method \Bitrix\StaffTrack\Model\Shift remindActualShift()
	 * @method \Bitrix\StaffTrack\Model\Shift requireShift()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage setShift(\Bitrix\StaffTrack\Model\Shift $object)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage resetShift()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage unsetShift()
	 * @method bool hasShift()
	 * @method bool isShiftFilled()
	 * @method bool isShiftChanged()
	 * @method \Bitrix\StaffTrack\Model\Shift fillShift()
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
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage set($fieldName, $value)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage reset($fieldName)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\StaffTrack\Model\EO_ShiftMessage wakeUp($data)
	 */
	class EO_ShiftMessage {
		/* @var \Bitrix\StaffTrack\Model\ShiftMessageTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\ShiftMessageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * ShiftMessageCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getShiftIdList()
	 * @method \int[] fillShiftId()
	 * @method \int[] getMessageIdList()
	 * @method \int[] fillMessageId()
	 * @method \Bitrix\StaffTrack\Model\Shift[] getShiftList()
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection getShiftCollection()
	 * @method \Bitrix\StaffTrack\Model\ShiftCollection fillShift()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\StaffTrack\Model\EO_ShiftMessage $object)
	 * @method bool has(\Bitrix\StaffTrack\Model\EO_ShiftMessage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage getByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage[] getAll()
	 * @method bool remove(\Bitrix\StaffTrack\Model\EO_ShiftMessage $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\StaffTrack\Model\ShiftMessageCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection merge(?\Bitrix\StaffTrack\Model\ShiftMessageCollection $collection)
	 * @method bool isEmpty()
	 */
	class EO_ShiftMessage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\StaffTrack\Model\ShiftMessageTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\ShiftMessageTable';
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ShiftMessage_Result exec()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage fetchObject()
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ShiftMessage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage fetchObject()
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection fetchCollection()
	 */
	class EO_ShiftMessage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage createObject($setDefaultValues = true)
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection createCollection()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftMessage wakeUpObject($row)
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection wakeUpCollection($rows)
	 */
	class EO_ShiftMessage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\StaffTrack\Model\ShiftCancellationTable:stafftrack/lib/model/shiftcancellationtable.php */
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_ShiftCancellation
	 * @see \Bitrix\StaffTrack\Model\ShiftCancellationTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getShiftId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation setShiftId(\int|\Bitrix\Main\DB\SqlExpression $shiftId)
	 * @method bool hasShiftId()
	 * @method bool isShiftIdFilled()
	 * @method bool isShiftIdChanged()
	 * @method \int remindActualShiftId()
	 * @method \int requireShiftId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation resetShiftId()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation unsetShiftId()
	 * @method \int fillShiftId()
	 * @method \string getReason()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation setReason(\string|\Bitrix\Main\DB\SqlExpression $reason)
	 * @method bool hasReason()
	 * @method bool isReasonFilled()
	 * @method bool isReasonChanged()
	 * @method \string remindActualReason()
	 * @method \string requireReason()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation resetReason()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation unsetReason()
	 * @method \string fillReason()
	 * @method \Bitrix\Main\Type\DateTime getDateCancel()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation setDateCancel(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCancel)
	 * @method bool hasDateCancel()
	 * @method bool isDateCancelFilled()
	 * @method bool isDateCancelChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCancel()
	 * @method \Bitrix\Main\Type\DateTime requireDateCancel()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation resetDateCancel()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation unsetDateCancel()
	 * @method \Bitrix\Main\Type\DateTime fillDateCancel()
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
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation set($fieldName, $value)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation reset($fieldName)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\StaffTrack\Model\EO_ShiftCancellation wakeUp($data)
	 */
	class EO_ShiftCancellation {
		/* @var \Bitrix\StaffTrack\Model\ShiftCancellationTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\ShiftCancellationTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_ShiftCancellation_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getShiftIdList()
	 * @method \int[] fillShiftId()
	 * @method \string[] getReasonList()
	 * @method \string[] fillReason()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCancelList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCancel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\StaffTrack\Model\EO_ShiftCancellation $object)
	 * @method bool has(\Bitrix\StaffTrack\Model\EO_ShiftCancellation $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation getByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation[] getAll()
	 * @method bool remove(\Bitrix\StaffTrack\Model\EO_ShiftCancellation $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection merge(?\Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_ShiftCancellation_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\StaffTrack\Model\ShiftCancellationTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\ShiftCancellationTable';
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ShiftCancellation_Result exec()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ShiftCancellation_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection fetchCollection()
	 */
	class EO_ShiftCancellation_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation createObject($setDefaultValues = true)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection createCollection()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation wakeUpObject($row)
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection wakeUpCollection($rows)
	 */
	class EO_ShiftCancellation_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\StaffTrack\Model\OptionTable:stafftrack/lib/model/optiontable.php */
namespace Bitrix\StaffTrack\Model {
	/**
	 * Option
	 * @see \Bitrix\StaffTrack\Model\OptionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\StaffTrack\Model\Option setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\StaffTrack\Model\Option setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\StaffTrack\Model\Option resetUserId()
	 * @method \Bitrix\StaffTrack\Model\Option unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getName()
	 * @method \Bitrix\StaffTrack\Model\Option setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\StaffTrack\Model\Option resetName()
	 * @method \Bitrix\StaffTrack\Model\Option unsetName()
	 * @method \string fillName()
	 * @method \string getValue()
	 * @method \Bitrix\StaffTrack\Model\Option setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\StaffTrack\Model\Option resetValue()
	 * @method \Bitrix\StaffTrack\Model\Option unsetValue()
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
	 * @method \Bitrix\StaffTrack\Model\Option set($fieldName, $value)
	 * @method \Bitrix\StaffTrack\Model\Option reset($fieldName)
	 * @method \Bitrix\StaffTrack\Model\Option unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\StaffTrack\Model\Option wakeUp($data)
	 */
	class EO_Option {
		/* @var \Bitrix\StaffTrack\Model\OptionTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\OptionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_Option_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\StaffTrack\Model\Option $object)
	 * @method bool has(\Bitrix\StaffTrack\Model\Option $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\Option getByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\Option[] getAll()
	 * @method bool remove(\Bitrix\StaffTrack\Model\Option $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\StaffTrack\Model\EO_Option_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\StaffTrack\Model\Option current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\StaffTrack\Model\EO_Option_Collection merge(?\Bitrix\StaffTrack\Model\EO_Option_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Option_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\StaffTrack\Model\OptionTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\OptionTable';
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Option_Result exec()
	 * @method \Bitrix\StaffTrack\Model\Option fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_Option_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Option_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\StaffTrack\Model\Option fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_Option_Collection fetchCollection()
	 */
	class EO_Option_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\StaffTrack\Model\Option createObject($setDefaultValues = true)
	 * @method \Bitrix\StaffTrack\Model\EO_Option_Collection createCollection()
	 * @method \Bitrix\StaffTrack\Model\Option wakeUpObject($row)
	 * @method \Bitrix\StaffTrack\Model\EO_Option_Collection wakeUpCollection($rows)
	 */
	class EO_Option_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\StaffTrack\Model\ShiftTable:stafftrack/lib/model/shifttable.php */
namespace Bitrix\StaffTrack\Model {
	/**
	 * Shift
	 * @see \Bitrix\StaffTrack\Model\ShiftTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\StaffTrack\Model\Shift setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\StaffTrack\Model\Shift setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\StaffTrack\Model\Shift resetUserId()
	 * @method \Bitrix\StaffTrack\Model\Shift unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\Type\Date getShiftDate()
	 * @method \Bitrix\StaffTrack\Model\Shift setShiftDate(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $shiftDate)
	 * @method bool hasShiftDate()
	 * @method bool isShiftDateFilled()
	 * @method bool isShiftDateChanged()
	 * @method \Bitrix\Main\Type\Date remindActualShiftDate()
	 * @method \Bitrix\Main\Type\Date requireShiftDate()
	 * @method \Bitrix\StaffTrack\Model\Shift resetShiftDate()
	 * @method \Bitrix\StaffTrack\Model\Shift unsetShiftDate()
	 * @method \Bitrix\Main\Type\Date fillShiftDate()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\StaffTrack\Model\Shift setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\StaffTrack\Model\Shift resetDateCreate()
	 * @method \Bitrix\StaffTrack\Model\Shift unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \int getStatus()
	 * @method \Bitrix\StaffTrack\Model\Shift setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\StaffTrack\Model\Shift resetStatus()
	 * @method \Bitrix\StaffTrack\Model\Shift unsetStatus()
	 * @method \int fillStatus()
	 * @method \string getLocation()
	 * @method \Bitrix\StaffTrack\Model\Shift setLocation(\string|\Bitrix\Main\DB\SqlExpression $location)
	 * @method bool hasLocation()
	 * @method bool isLocationFilled()
	 * @method bool isLocationChanged()
	 * @method \string remindActualLocation()
	 * @method \string requireLocation()
	 * @method \Bitrix\StaffTrack\Model\Shift resetLocation()
	 * @method \Bitrix\StaffTrack\Model\Shift unsetLocation()
	 * @method \string fillLocation()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo getGeo()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo remindActualGeo()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo requireGeo()
	 * @method \Bitrix\StaffTrack\Model\Shift setGeo(\Bitrix\StaffTrack\Model\EO_ShiftGeo $object)
	 * @method \Bitrix\StaffTrack\Model\Shift resetGeo()
	 * @method \Bitrix\StaffTrack\Model\Shift unsetGeo()
	 * @method bool hasGeo()
	 * @method bool isGeoFilled()
	 * @method bool isGeoChanged()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo fillGeo()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation getCancellation()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation remindActualCancellation()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation requireCancellation()
	 * @method \Bitrix\StaffTrack\Model\Shift setCancellation(\Bitrix\StaffTrack\Model\EO_ShiftCancellation $object)
	 * @method \Bitrix\StaffTrack\Model\Shift resetCancellation()
	 * @method \Bitrix\StaffTrack\Model\Shift unsetCancellation()
	 * @method bool hasCancellation()
	 * @method bool isCancellationFilled()
	 * @method bool isCancellationChanged()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation fillCancellation()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo getGeoInner()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo remindActualGeoInner()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo requireGeoInner()
	 * @method \Bitrix\StaffTrack\Model\Shift setGeoInner(\Bitrix\StaffTrack\Model\EO_ShiftGeo $object)
	 * @method \Bitrix\StaffTrack\Model\Shift resetGeoInner()
	 * @method \Bitrix\StaffTrack\Model\Shift unsetGeoInner()
	 * @method bool hasGeoInner()
	 * @method bool isGeoInnerFilled()
	 * @method bool isGeoInnerChanged()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo fillGeoInner()
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection getMessages()
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection requireMessages()
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection fillMessages()
	 * @method bool hasMessages()
	 * @method bool isMessagesFilled()
	 * @method bool isMessagesChanged()
	 * @method void addToMessages(\Bitrix\StaffTrack\Model\EO_ShiftMessage $shiftMessage)
	 * @method void removeFromMessages(\Bitrix\StaffTrack\Model\EO_ShiftMessage $shiftMessage)
	 * @method void removeAllMessages()
	 * @method \Bitrix\StaffTrack\Model\Shift resetMessages()
	 * @method \Bitrix\StaffTrack\Model\Shift unsetMessages()
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
	 * @method \Bitrix\StaffTrack\Model\Shift set($fieldName, $value)
	 * @method \Bitrix\StaffTrack\Model\Shift reset($fieldName)
	 * @method \Bitrix\StaffTrack\Model\Shift unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\StaffTrack\Model\Shift wakeUp($data)
	 */
	class EO_Shift {
		/* @var \Bitrix\StaffTrack\Model\ShiftTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\ShiftTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * ShiftCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\Type\Date[] getShiftDateList()
	 * @method \Bitrix\Main\Type\Date[] fillShiftDate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \string[] getLocationList()
	 * @method \string[] fillLocation()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo[] getGeoList()
	 * @method \Bitrix\StaffTrack\Model\ShiftCollection getGeoCollection()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection fillGeo()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation[] getCancellationList()
	 * @method \Bitrix\StaffTrack\Model\ShiftCollection getCancellationCollection()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection fillCancellation()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo[] getGeoInnerList()
	 * @method \Bitrix\StaffTrack\Model\ShiftCollection getGeoInnerCollection()
	 * @method \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection fillGeoInner()
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection[] getMessagesList()
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection getMessagesCollection()
	 * @method \Bitrix\StaffTrack\Model\ShiftMessageCollection fillMessages()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\StaffTrack\Model\Shift $object)
	 * @method bool has(\Bitrix\StaffTrack\Model\Shift $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\Shift getByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\Shift[] getAll()
	 * @method bool remove(\Bitrix\StaffTrack\Model\Shift $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\StaffTrack\Model\ShiftCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\StaffTrack\Model\Shift current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\StaffTrack\Model\ShiftCollection merge(?\Bitrix\StaffTrack\Model\ShiftCollection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Shift_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\StaffTrack\Model\ShiftTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\ShiftTable';
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Shift_Result exec()
	 * @method \Bitrix\StaffTrack\Model\Shift fetchObject()
	 * @method \Bitrix\StaffTrack\Model\ShiftCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Shift_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\StaffTrack\Model\Shift fetchObject()
	 * @method \Bitrix\StaffTrack\Model\ShiftCollection fetchCollection()
	 */
	class EO_Shift_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\StaffTrack\Model\Shift createObject($setDefaultValues = true)
	 * @method \Bitrix\StaffTrack\Model\ShiftCollection createCollection()
	 * @method \Bitrix\StaffTrack\Model\Shift wakeUpObject($row)
	 * @method \Bitrix\StaffTrack\Model\ShiftCollection wakeUpCollection($rows)
	 */
	class EO_Shift_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\StaffTrack\Model\CounterTable:stafftrack/lib/model/countertable.php */
namespace Bitrix\StaffTrack\Model {
	/**
	 * Counter
	 * @see \Bitrix\StaffTrack\Model\CounterTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\StaffTrack\Model\Counter setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\StaffTrack\Model\Counter setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\StaffTrack\Model\Counter resetUserId()
	 * @method \Bitrix\StaffTrack\Model\Counter unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getMuteStatus()
	 * @method \Bitrix\StaffTrack\Model\Counter setMuteStatus(\int|\Bitrix\Main\DB\SqlExpression $muteStatus)
	 * @method bool hasMuteStatus()
	 * @method bool isMuteStatusFilled()
	 * @method bool isMuteStatusChanged()
	 * @method \int remindActualMuteStatus()
	 * @method \int requireMuteStatus()
	 * @method \Bitrix\StaffTrack\Model\Counter resetMuteStatus()
	 * @method \Bitrix\StaffTrack\Model\Counter unsetMuteStatus()
	 * @method \int fillMuteStatus()
	 * @method \Bitrix\Main\Type\DateTime getMuteUntil()
	 * @method \Bitrix\StaffTrack\Model\Counter setMuteUntil(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $muteUntil)
	 * @method bool hasMuteUntil()
	 * @method bool isMuteUntilFilled()
	 * @method bool isMuteUntilChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualMuteUntil()
	 * @method \Bitrix\Main\Type\DateTime requireMuteUntil()
	 * @method \Bitrix\StaffTrack\Model\Counter resetMuteUntil()
	 * @method \Bitrix\StaffTrack\Model\Counter unsetMuteUntil()
	 * @method \Bitrix\Main\Type\DateTime fillMuteUntil()
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
	 * @method \Bitrix\StaffTrack\Model\Counter set($fieldName, $value)
	 * @method \Bitrix\StaffTrack\Model\Counter reset($fieldName)
	 * @method \Bitrix\StaffTrack\Model\Counter unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\StaffTrack\Model\Counter wakeUp($data)
	 */
	class EO_Counter {
		/* @var \Bitrix\StaffTrack\Model\CounterTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\CounterTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_Counter_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getMuteStatusList()
	 * @method \int[] fillMuteStatus()
	 * @method \Bitrix\Main\Type\DateTime[] getMuteUntilList()
	 * @method \Bitrix\Main\Type\DateTime[] fillMuteUntil()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\StaffTrack\Model\Counter $object)
	 * @method bool has(\Bitrix\StaffTrack\Model\Counter $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\Counter getByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\Counter[] getAll()
	 * @method bool remove(\Bitrix\StaffTrack\Model\Counter $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\StaffTrack\Model\EO_Counter_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\StaffTrack\Model\Counter current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\StaffTrack\Model\EO_Counter_Collection merge(?\Bitrix\StaffTrack\Model\EO_Counter_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Counter_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\StaffTrack\Model\CounterTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\CounterTable';
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Counter_Result exec()
	 * @method \Bitrix\StaffTrack\Model\Counter fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_Counter_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Counter_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\StaffTrack\Model\Counter fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_Counter_Collection fetchCollection()
	 */
	class EO_Counter_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\StaffTrack\Model\Counter createObject($setDefaultValues = true)
	 * @method \Bitrix\StaffTrack\Model\EO_Counter_Collection createCollection()
	 * @method \Bitrix\StaffTrack\Model\Counter wakeUpObject($row)
	 * @method \Bitrix\StaffTrack\Model\EO_Counter_Collection wakeUpCollection($rows)
	 */
	class EO_Counter_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\StaffTrack\Model\HandledChatTable:stafftrack/lib/model/handledchattable.php */
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_HandledChat
	 * @see \Bitrix\StaffTrack\Model\HandledChatTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getChatId()
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat setChatId(\int|\Bitrix\Main\DB\SqlExpression $chatId)
	 * @method bool hasChatId()
	 * @method bool isChatIdFilled()
	 * @method bool isChatIdChanged()
	 * @method \int remindActualChatId()
	 * @method \int requireChatId()
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat resetChatId()
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat unsetChatId()
	 * @method \int fillChatId()
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
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat set($fieldName, $value)
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat reset($fieldName)
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\StaffTrack\Model\EO_HandledChat wakeUp($data)
	 */
	class EO_HandledChat {
		/* @var \Bitrix\StaffTrack\Model\HandledChatTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\HandledChatTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * EO_HandledChat_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getChatIdList()
	 * @method \int[] fillChatId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\StaffTrack\Model\EO_HandledChat $object)
	 * @method bool has(\Bitrix\StaffTrack\Model\EO_HandledChat $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat getByPrimary($primary)
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat[] getAll()
	 * @method bool remove(\Bitrix\StaffTrack\Model\EO_HandledChat $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\StaffTrack\Model\EO_HandledChat_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat_Collection merge(?\Bitrix\StaffTrack\Model\EO_HandledChat_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_HandledChat_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\StaffTrack\Model\HandledChatTable */
		static public $dataClass = '\Bitrix\StaffTrack\Model\HandledChatTable';
	}
}
namespace Bitrix\StaffTrack\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_HandledChat_Result exec()
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_HandledChat_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat fetchObject()
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat_Collection fetchCollection()
	 */
	class EO_HandledChat_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat createObject($setDefaultValues = true)
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat_Collection createCollection()
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat wakeUpObject($row)
	 * @method \Bitrix\StaffTrack\Model\EO_HandledChat_Collection wakeUpCollection($rows)
	 */
	class EO_HandledChat_Entity extends \Bitrix\Main\ORM\Entity {}
}