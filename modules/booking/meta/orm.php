<?php

/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\OptionTable:booking/lib/Internals/Model/OptionTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Option
	 * @see \Bitrix\Booking\Internals\Model\OptionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option resetUserId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getName()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option resetName()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option unsetName()
	 * @method \string fillName()
	 * @method \string getValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option resetValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option unsetValue()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_Option set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_Option reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_Option unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_Option wakeUp($data)
	 */
	class EO_Option {
		/* @var \Bitrix\Booking\Internals\Model\OptionTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\OptionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
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
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_Option $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_Option $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Option getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Option[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_Option $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_Option_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_Option current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_Option_Collection merge(?\Bitrix\Booking\Internals\Model\EO_Option_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Option_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\OptionTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\OptionTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Option_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option_Collection fetchCollection()
	 */
	class EO_Option_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Option fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option_Collection fetchCollection()
	 */
	class EO_Option_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Option createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_Option_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Option wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_Option_Collection wakeUpCollection($rows)
	 */
	class EO_Option_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\FavoritesTable:booking/lib/Internals/Model/FavoritesTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Favorites
	 * @see \Bitrix\Booking\Internals\Model\FavoritesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getManagerId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites setManagerId(\int|\Bitrix\Main\DB\SqlExpression $managerId)
	 * @method bool hasManagerId()
	 * @method bool isManagerIdFilled()
	 * @method bool isManagerIdChanged()
	 * @method \int remindActualManagerId()
	 * @method \int requireManagerId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites resetManagerId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites unsetManagerId()
	 * @method \int fillManagerId()
	 * @method \int getResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites setResourceId(\int|\Bitrix\Main\DB\SqlExpression $resourceId)
	 * @method bool hasResourceId()
	 * @method bool isResourceIdFilled()
	 * @method bool isResourceIdChanged()
	 * @method \int remindActualResourceId()
	 * @method \int requireResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites resetResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites unsetResourceId()
	 * @method \int fillResourceId()
	 * @method \string getType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites resetType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites unsetType()
	 * @method \string fillType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource getResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource remindActualResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource requireResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites setResource(\Bitrix\Booking\Internals\Model\EO_Resource $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites resetResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites unsetResource()
	 * @method bool hasResource()
	 * @method bool isResourceFilled()
	 * @method bool isResourceChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource fillResource()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_Favorites wakeUp($data)
	 */
	class EO_Favorites {
		/* @var \Bitrix\Booking\Internals\Model\FavoritesTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\FavoritesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Favorites_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getManagerIdList()
	 * @method \int[] fillManagerId()
	 * @method \int[] getResourceIdList()
	 * @method \int[] fillResourceId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource[] getResourceList()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites_Collection getResourceCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection fillResource()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_Favorites $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_Favorites $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_Favorites $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_Favorites_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites_Collection merge(?\Bitrix\Booking\Internals\Model\EO_Favorites_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Favorites_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\FavoritesTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\FavoritesTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Favorites_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites_Collection fetchCollection()
	 */
	class EO_Favorites_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites_Collection fetchCollection()
	 */
	class EO_Favorites_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_Favorites_Collection wakeUpCollection($rows)
	 */
	class EO_Favorites_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\BookingResourceTable:booking/lib/Internals/Model/BookingResourceTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingResource
	 * @see \Bitrix\Booking\Internals\Model\BookingResourceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource setBookingId(\int|\Bitrix\Main\DB\SqlExpression $bookingId)
	 * @method bool hasBookingId()
	 * @method bool isBookingIdFilled()
	 * @method bool isBookingIdChanged()
	 * @method \int remindActualBookingId()
	 * @method \int requireBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource resetBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource unsetBookingId()
	 * @method \int fillBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking getBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking remindActualBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking requireBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource setBooking(\Bitrix\Booking\Internals\Model\EO_Booking $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource resetBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource unsetBooking()
	 * @method bool hasBooking()
	 * @method bool isBookingFilled()
	 * @method bool isBookingChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking fillBooking()
	 * @method \int getResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource setResourceId(\int|\Bitrix\Main\DB\SqlExpression $resourceId)
	 * @method bool hasResourceId()
	 * @method bool isResourceIdFilled()
	 * @method bool isResourceIdChanged()
	 * @method \int remindActualResourceId()
	 * @method \int requireResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource resetResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource unsetResourceId()
	 * @method \int fillResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource getResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource remindActualResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource requireResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource setResource(\Bitrix\Booking\Internals\Model\EO_Resource $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource resetResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource unsetResource()
	 * @method bool hasResource()
	 * @method bool isResourceFilled()
	 * @method bool isResourceChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource fillResource()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingResource wakeUp($data)
	 */
	class EO_BookingResource {
		/* @var \Bitrix\Booking\Internals\Model\BookingResourceTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingResourceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingResource_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBookingIdList()
	 * @method \int[] fillBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking[] getBookingList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection getBookingCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection fillBooking()
	 * @method \int[] getResourceIdList()
	 * @method \int[] fillResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource[] getResourceList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection getResourceCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection fillResource()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_BookingResource $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_BookingResource $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_BookingResource $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection merge(?\Bitrix\Booking\Internals\Model\EO_BookingResource_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_BookingResource_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\BookingResourceTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingResourceTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_BookingResource_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection fetchCollection()
	 */
	class EO_BookingResource_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection fetchCollection()
	 */
	class EO_BookingResource_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection wakeUpCollection($rows)
	 */
	class EO_BookingResource_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\ScorerTable:booking/lib/Internals/Model/ScorerTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Scorer
	 * @see \Bitrix\Booking\Internals\Model\ScorerTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer resetUserId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getEntityId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer resetEntityId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer resetType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer unsetType()
	 * @method \string fillType()
	 * @method \int getValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer setValue(\int|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \int remindActualValue()
	 * @method \int requireValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer resetValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer unsetValue()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_Scorer wakeUp($data)
	 */
	class EO_Scorer {
		/* @var \Bitrix\Booking\Internals\Model\ScorerTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ScorerTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Scorer_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getValueList()
	 * @method \int[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_Scorer $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_Scorer $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_Scorer $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_Scorer_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer_Collection merge(?\Bitrix\Booking\Internals\Model\EO_Scorer_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Scorer_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\ScorerTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ScorerTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Scorer_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer_Collection fetchCollection()
	 */
	class EO_Scorer_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer_Collection fetchCollection()
	 */
	class EO_Scorer_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_Scorer_Collection wakeUpCollection($rows)
	 */
	class EO_Scorer_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\BookingClientTable:booking/lib/Internals/Model/BookingClientTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingClient
	 * @see \Bitrix\Booking\Internals\Model\BookingClientTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient setBookingId(\int|\Bitrix\Main\DB\SqlExpression $bookingId)
	 * @method bool hasBookingId()
	 * @method bool isBookingIdFilled()
	 * @method bool isBookingIdChanged()
	 * @method \int remindActualBookingId()
	 * @method \int requireBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient resetBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient unsetBookingId()
	 * @method \int fillBookingId()
	 * @method \int getClientTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient setClientTypeId(\int|\Bitrix\Main\DB\SqlExpression $clientTypeId)
	 * @method bool hasClientTypeId()
	 * @method bool isClientTypeIdFilled()
	 * @method bool isClientTypeIdChanged()
	 * @method \int remindActualClientTypeId()
	 * @method \int requireClientTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient resetClientTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient unsetClientTypeId()
	 * @method \int fillClientTypeId()
	 * @method \int getClientId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient setClientId(\int|\Bitrix\Main\DB\SqlExpression $clientId)
	 * @method bool hasClientId()
	 * @method bool isClientIdFilled()
	 * @method bool isClientIdChanged()
	 * @method \int remindActualClientId()
	 * @method \int requireClientId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient resetClientId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient unsetClientId()
	 * @method \int fillClientId()
	 * @method \boolean getIsPrimary()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient setIsPrimary(\boolean|\Bitrix\Main\DB\SqlExpression $isPrimary)
	 * @method bool hasIsPrimary()
	 * @method bool isIsPrimaryFilled()
	 * @method bool isIsPrimaryChanged()
	 * @method \boolean remindActualIsPrimary()
	 * @method \boolean requireIsPrimary()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient resetIsPrimary()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient unsetIsPrimary()
	 * @method \boolean fillIsPrimary()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking getBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking remindActualBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking requireBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient setBooking(\Bitrix\Booking\Internals\Model\EO_Booking $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient resetBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient unsetBooking()
	 * @method bool hasBooking()
	 * @method bool isBookingFilled()
	 * @method bool isBookingChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking fillBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType getClientType()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType remindActualClientType()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType requireClientType()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient setClientType(\Bitrix\Booking\Internals\Model\EO_ClientType $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient resetClientType()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient unsetClientType()
	 * @method bool hasClientType()
	 * @method bool isClientTypeFilled()
	 * @method bool isClientTypeChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType fillClientType()
	 * @method \boolean getIsReturning()
	 * @method \boolean remindActualIsReturning()
	 * @method \boolean requireIsReturning()
	 * @method bool hasIsReturning()
	 * @method bool isIsReturningFilled()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient unsetIsReturning()
	 * @method \boolean fillIsReturning()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingClient wakeUp($data)
	 */
	class EO_BookingClient {
		/* @var \Bitrix\Booking\Internals\Model\BookingClientTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingClientTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingClient_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBookingIdList()
	 * @method \int[] fillBookingId()
	 * @method \int[] getClientTypeIdList()
	 * @method \int[] fillClientTypeId()
	 * @method \int[] getClientIdList()
	 * @method \int[] fillClientId()
	 * @method \boolean[] getIsPrimaryList()
	 * @method \boolean[] fillIsPrimary()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking[] getBookingList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection getBookingCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection fillBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType[] getClientTypeList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection getClientTypeCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType_Collection fillClientType()
	 * @method \boolean[] getIsReturningList()
	 * @method \boolean[] fillIsReturning()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_BookingClient $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_BookingClient $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_BookingClient $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection merge(?\Bitrix\Booking\Internals\Model\EO_BookingClient_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_BookingClient_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\BookingClientTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingClientTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_BookingClient_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection fetchCollection()
	 */
	class EO_BookingClient_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection fetchCollection()
	 */
	class EO_BookingClient_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection wakeUpCollection($rows)
	 */
	class EO_BookingClient_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\ClientTypeTable:booking/lib/Internals/Model/ClientTypeTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ClientType
	 * @see \Bitrix\Booking\Internals\Model\ClientTypeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType resetModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType unsetModuleId()
	 * @method \string fillModuleId()
	 * @method \string getCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType resetCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType unsetCode()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_ClientType wakeUp($data)
	 */
	class EO_ClientType {
		/* @var \Bitrix\Booking\Internals\Model\ClientTypeTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ClientTypeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ClientType_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_ClientType $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_ClientType $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_ClientType $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_ClientType_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType_Collection merge(?\Bitrix\Booking\Internals\Model\EO_ClientType_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ClientType_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\ClientTypeTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ClientTypeTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ClientType_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType_Collection fetchCollection()
	 */
	class EO_ClientType_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType_Collection fetchCollection()
	 */
	class EO_ClientType_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_ClientType_Collection wakeUpCollection($rows)
	 */
	class EO_ClientType_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\ResourceTypeTable:booking/lib/Internals/Model/ResourceTypeTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceType
	 * @see \Bitrix\Booking\Internals\Model\ResourceTypeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType resetModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType unsetModuleId()
	 * @method \string fillModuleId()
	 * @method null|\string getCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType setCode(null|\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method null|\string remindActualCode()
	 * @method null|\string requireCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType resetCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType unsetCode()
	 * @method null|\string fillCode()
	 * @method \string getName()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType resetName()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType unsetName()
	 * @method \string fillName()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection getResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection requireResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection fillResources()
	 * @method bool hasResources()
	 * @method bool isResourcesFilled()
	 * @method bool isResourcesChanged()
	 * @method void addToResources(\Bitrix\Booking\Internals\Model\EO_Resource $resource)
	 * @method void removeFromResources(\Bitrix\Booking\Internals\Model\EO_Resource $resource)
	 * @method void removeAllResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType resetResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType unsetResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings getNotificationSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings remindActualNotificationSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings requireNotificationSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType setNotificationSettings(\Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType resetNotificationSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType unsetNotificationSettings()
	 * @method bool hasNotificationSettings()
	 * @method bool isNotificationSettingsFilled()
	 * @method bool isNotificationSettingsChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings fillNotificationSettings()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceType wakeUp($data)
	 */
	class EO_ResourceType {
		/* @var \Bitrix\Booking\Internals\Model\ResourceTypeTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceTypeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceType_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method null|\string[] getCodeList()
	 * @method null|\string[] fillCode()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection[] getResourcesList()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection getResourcesCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection fillResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings[] getNotificationSettingsList()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection getNotificationSettingsCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection fillNotificationSettings()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_ResourceType $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_ResourceType $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_ResourceType $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection merge(?\Bitrix\Booking\Internals\Model\EO_ResourceType_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ResourceType_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\ResourceTypeTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceTypeTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ResourceType_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection fetchCollection()
	 */
	class EO_ResourceType_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection fetchCollection()
	 */
	class EO_ResourceType_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection wakeUpCollection($rows)
	 */
	class EO_ResourceType_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\JournalTable:booking/lib/Internals/Model/JournalTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Journal
	 * @see \Bitrix\Booking\Internals\Model\JournalTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getEntityId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal resetEntityId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal resetType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal unsetType()
	 * @method \string fillType()
	 * @method \string getData()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal setData(\string|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method \string remindActualData()
	 * @method \string requireData()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal resetData()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal unsetData()
	 * @method \string fillData()
	 * @method \string getStatus()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal resetStatus()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal unsetStatus()
	 * @method \string fillStatus()
	 * @method \string getInfo()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal setInfo(\string|\Bitrix\Main\DB\SqlExpression $info)
	 * @method bool hasInfo()
	 * @method bool isInfoFilled()
	 * @method bool isInfoChanged()
	 * @method \string remindActualInfo()
	 * @method \string requireInfo()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal resetInfo()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal unsetInfo()
	 * @method \string fillInfo()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal resetCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal unsetCreatedAt()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_Journal wakeUp($data)
	 */
	class EO_Journal {
		/* @var \Bitrix\Booking\Internals\Model\JournalTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\JournalTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Journal_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getDataList()
	 * @method \string[] fillData()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \string[] getInfoList()
	 * @method \string[] fillInfo()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_Journal $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_Journal $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_Journal $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_Journal_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal_Collection merge(?\Bitrix\Booking\Internals\Model\EO_Journal_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Journal_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\JournalTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\JournalTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Journal_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal_Collection fetchCollection()
	 */
	class EO_Journal_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal_Collection fetchCollection()
	 */
	class EO_Journal_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_Journal_Collection wakeUpCollection($rows)
	 */
	class EO_Journal_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\ResourceDataTable:booking/lib/Internals/Model/ResourceDataTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceData
	 * @see \Bitrix\Booking\Internals\Model\ResourceDataTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData setResourceId(\int|\Bitrix\Main\DB\SqlExpression $resourceId)
	 * @method bool hasResourceId()
	 * @method bool isResourceIdFilled()
	 * @method bool isResourceIdChanged()
	 * @method \int remindActualResourceId()
	 * @method \int requireResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData resetResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData unsetResourceId()
	 * @method \int fillResourceId()
	 * @method \string getName()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData resetName()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData unsetName()
	 * @method \string fillName()
	 * @method \string getDescription()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData resetDescription()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData unsetDescription()
	 * @method \string fillDescription()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData resetCreatedBy()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData resetCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData resetUpdatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceData wakeUp($data)
	 */
	class EO_ResourceData {
		/* @var \Bitrix\Booking\Internals\Model\ResourceDataTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceDataTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceData_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getResourceIdList()
	 * @method \int[] fillResourceId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_ResourceData $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_ResourceData $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_ResourceData $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceData_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData_Collection merge(?\Bitrix\Booking\Internals\Model\EO_ResourceData_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ResourceData_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\ResourceDataTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceDataTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ResourceData_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData_Collection fetchCollection()
	 */
	class EO_ResourceData_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData_Collection fetchCollection()
	 */
	class EO_ResourceData_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData_Collection wakeUpCollection($rows)
	 */
	class EO_ResourceData_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\ResourceTable:booking/lib/Internals/Model/ResourceTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Resource
	 * @see \Bitrix\Booking\Internals\Model\ResourceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource setTypeId(\int|\Bitrix\Main\DB\SqlExpression $typeId)
	 * @method bool hasTypeId()
	 * @method bool isTypeIdFilled()
	 * @method bool isTypeIdChanged()
	 * @method \int remindActualTypeId()
	 * @method \int requireTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource resetTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource unsetTypeId()
	 * @method \int fillTypeId()
	 * @method \int getExternalId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource setExternalId(\int|\Bitrix\Main\DB\SqlExpression $externalId)
	 * @method bool hasExternalId()
	 * @method bool isExternalIdFilled()
	 * @method bool isExternalIdChanged()
	 * @method \int remindActualExternalId()
	 * @method \int requireExternalId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource resetExternalId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource unsetExternalId()
	 * @method \int fillExternalId()
	 * @method \boolean getIsMain()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource setIsMain(\boolean|\Bitrix\Main\DB\SqlExpression $isMain)
	 * @method bool hasIsMain()
	 * @method bool isIsMainFilled()
	 * @method bool isIsMainChanged()
	 * @method \boolean remindActualIsMain()
	 * @method \boolean requireIsMain()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource resetIsMain()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource unsetIsMain()
	 * @method \boolean fillIsMain()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType getType()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType remindActualType()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType requireType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource setType(\Bitrix\Booking\Internals\Model\EO_ResourceType $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource resetType()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource unsetType()
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType fillType()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection getSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection requireSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection fillSettings()
	 * @method bool hasSettings()
	 * @method bool isSettingsFilled()
	 * @method bool isSettingsChanged()
	 * @method void addToSettings(\Bitrix\Booking\Internals\Model\EO_ResourceSettings $resourceSettings)
	 * @method void removeFromSettings(\Bitrix\Booking\Internals\Model\EO_ResourceSettings $resourceSettings)
	 * @method void removeAllSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource resetSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource unsetSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData getData()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData remindActualData()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData requireData()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource setData(\Bitrix\Booking\Internals\Model\EO_ResourceData $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource resetData()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource unsetData()
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData fillData()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings getNotificationSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings remindActualNotificationSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings requireNotificationSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource setNotificationSettings(\Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource resetNotificationSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource unsetNotificationSettings()
	 * @method bool hasNotificationSettings()
	 * @method bool isNotificationSettingsFilled()
	 * @method bool isNotificationSettingsChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings fillNotificationSettings()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_Resource wakeUp($data)
	 */
	class EO_Resource {
		/* @var \Bitrix\Booking\Internals\Model\ResourceTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Resource_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTypeIdList()
	 * @method \int[] fillTypeId()
	 * @method \int[] getExternalIdList()
	 * @method \int[] fillExternalId()
	 * @method \boolean[] getIsMainList()
	 * @method \boolean[] fillIsMain()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType[] getTypeList()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection getTypeCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection fillType()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection[] getSettingsList()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection getSettingsCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection fillSettings()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData[] getDataList()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection getDataCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceData_Collection fillData()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings[] getNotificationSettingsList()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection getNotificationSettingsCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection fillNotificationSettings()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_Resource $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_Resource $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_Resource $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_Resource_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection merge(?\Bitrix\Booking\Internals\Model\EO_Resource_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Resource_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\ResourceTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Resource_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection fetchCollection()
	 */
	class EO_Resource_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection fetchCollection()
	 */
	class EO_Resource_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection wakeUpCollection($rows)
	 */
	class EO_Resource_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\NotesTable:booking/lib/Internals/Model/NotesTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Notes
	 * @see \Bitrix\Booking\Internals\Model\NotesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes setBookingId(\int|\Bitrix\Main\DB\SqlExpression $bookingId)
	 * @method bool hasBookingId()
	 * @method bool isBookingIdFilled()
	 * @method bool isBookingIdChanged()
	 * @method \int remindActualBookingId()
	 * @method \int requireBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes resetBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes unsetBookingId()
	 * @method \int fillBookingId()
	 * @method \string getDescription()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes resetDescription()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes unsetDescription()
	 * @method \string fillDescription()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_Notes wakeUp($data)
	 */
	class EO_Notes {
		/* @var \Bitrix\Booking\Internals\Model\NotesTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\NotesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Notes_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBookingIdList()
	 * @method \int[] fillBookingId()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_Notes $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_Notes $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_Notes $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_Notes_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes_Collection merge(?\Bitrix\Booking\Internals\Model\EO_Notes_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Notes_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\NotesTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\NotesTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Notes_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes_Collection fetchCollection()
	 */
	class EO_Notes_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes_Collection fetchCollection()
	 */
	class EO_Notes_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes_Collection wakeUpCollection($rows)
	 */
	class EO_Notes_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\ResourceTypeNotificationSettingsTable:booking/lib/Internals/Model/ResourceTypeNotificationSettingsTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceTypeNotificationSettings
	 * @see \Bitrix\Booking\Internals\Model\ResourceTypeNotificationSettingsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setTypeId(\int|\Bitrix\Main\DB\SqlExpression $typeId)
	 * @method bool hasTypeId()
	 * @method bool isTypeIdFilled()
	 * @method bool isTypeIdChanged()
	 * @method \int remindActualTypeId()
	 * @method \int requireTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetTypeId()
	 * @method \int fillTypeId()
	 * @method \boolean getIsInfoOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setIsInfoOn(\boolean|\Bitrix\Main\DB\SqlExpression $isInfoOn)
	 * @method bool hasIsInfoOn()
	 * @method bool isIsInfoOnFilled()
	 * @method bool isIsInfoOnChanged()
	 * @method \boolean remindActualIsInfoOn()
	 * @method \boolean requireIsInfoOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetIsInfoOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetIsInfoOn()
	 * @method \boolean fillIsInfoOn()
	 * @method \string getTemplateTypeInfo()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setTemplateTypeInfo(\string|\Bitrix\Main\DB\SqlExpression $templateTypeInfo)
	 * @method bool hasTemplateTypeInfo()
	 * @method bool isTemplateTypeInfoFilled()
	 * @method bool isTemplateTypeInfoChanged()
	 * @method \string remindActualTemplateTypeInfo()
	 * @method \string requireTemplateTypeInfo()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetTemplateTypeInfo()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetTemplateTypeInfo()
	 * @method \string fillTemplateTypeInfo()
	 * @method \boolean getIsConfirmationOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setIsConfirmationOn(\boolean|\Bitrix\Main\DB\SqlExpression $isConfirmationOn)
	 * @method bool hasIsConfirmationOn()
	 * @method bool isIsConfirmationOnFilled()
	 * @method bool isIsConfirmationOnChanged()
	 * @method \boolean remindActualIsConfirmationOn()
	 * @method \boolean requireIsConfirmationOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetIsConfirmationOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetIsConfirmationOn()
	 * @method \boolean fillIsConfirmationOn()
	 * @method \string getTemplateTypeConfirmation()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setTemplateTypeConfirmation(\string|\Bitrix\Main\DB\SqlExpression $templateTypeConfirmation)
	 * @method bool hasTemplateTypeConfirmation()
	 * @method bool isTemplateTypeConfirmationFilled()
	 * @method bool isTemplateTypeConfirmationChanged()
	 * @method \string remindActualTemplateTypeConfirmation()
	 * @method \string requireTemplateTypeConfirmation()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetTemplateTypeConfirmation()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetTemplateTypeConfirmation()
	 * @method \string fillTemplateTypeConfirmation()
	 * @method \boolean getIsReminderOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setIsReminderOn(\boolean|\Bitrix\Main\DB\SqlExpression $isReminderOn)
	 * @method bool hasIsReminderOn()
	 * @method bool isIsReminderOnFilled()
	 * @method bool isIsReminderOnChanged()
	 * @method \boolean remindActualIsReminderOn()
	 * @method \boolean requireIsReminderOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetIsReminderOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetIsReminderOn()
	 * @method \boolean fillIsReminderOn()
	 * @method \string getTemplateTypeReminder()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setTemplateTypeReminder(\string|\Bitrix\Main\DB\SqlExpression $templateTypeReminder)
	 * @method bool hasTemplateTypeReminder()
	 * @method bool isTemplateTypeReminderFilled()
	 * @method bool isTemplateTypeReminderChanged()
	 * @method \string remindActualTemplateTypeReminder()
	 * @method \string requireTemplateTypeReminder()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetTemplateTypeReminder()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetTemplateTypeReminder()
	 * @method \string fillTemplateTypeReminder()
	 * @method \boolean getIsFeedbackOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setIsFeedbackOn(\boolean|\Bitrix\Main\DB\SqlExpression $isFeedbackOn)
	 * @method bool hasIsFeedbackOn()
	 * @method bool isIsFeedbackOnFilled()
	 * @method bool isIsFeedbackOnChanged()
	 * @method \boolean remindActualIsFeedbackOn()
	 * @method \boolean requireIsFeedbackOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetIsFeedbackOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetIsFeedbackOn()
	 * @method \boolean fillIsFeedbackOn()
	 * @method \string getTemplateTypeFeedback()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setTemplateTypeFeedback(\string|\Bitrix\Main\DB\SqlExpression $templateTypeFeedback)
	 * @method bool hasTemplateTypeFeedback()
	 * @method bool isTemplateTypeFeedbackFilled()
	 * @method bool isTemplateTypeFeedbackChanged()
	 * @method \string remindActualTemplateTypeFeedback()
	 * @method \string requireTemplateTypeFeedback()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetTemplateTypeFeedback()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetTemplateTypeFeedback()
	 * @method \string fillTemplateTypeFeedback()
	 * @method \boolean getIsDelayedOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setIsDelayedOn(\boolean|\Bitrix\Main\DB\SqlExpression $isDelayedOn)
	 * @method bool hasIsDelayedOn()
	 * @method bool isIsDelayedOnFilled()
	 * @method bool isIsDelayedOnChanged()
	 * @method \boolean remindActualIsDelayedOn()
	 * @method \boolean requireIsDelayedOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetIsDelayedOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetIsDelayedOn()
	 * @method \boolean fillIsDelayedOn()
	 * @method \string getTemplateTypeDelayed()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings setTemplateTypeDelayed(\string|\Bitrix\Main\DB\SqlExpression $templateTypeDelayed)
	 * @method bool hasTemplateTypeDelayed()
	 * @method bool isTemplateTypeDelayedFilled()
	 * @method bool isTemplateTypeDelayedChanged()
	 * @method \string remindActualTemplateTypeDelayed()
	 * @method \string requireTemplateTypeDelayed()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings resetTemplateTypeDelayed()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unsetTemplateTypeDelayed()
	 * @method \string fillTemplateTypeDelayed()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings wakeUp($data)
	 */
	class EO_ResourceTypeNotificationSettings {
		/* @var \Bitrix\Booking\Internals\Model\ResourceTypeNotificationSettingsTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceTypeNotificationSettingsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceTypeNotificationSettings_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTypeIdList()
	 * @method \int[] fillTypeId()
	 * @method \boolean[] getIsInfoOnList()
	 * @method \boolean[] fillIsInfoOn()
	 * @method \string[] getTemplateTypeInfoList()
	 * @method \string[] fillTemplateTypeInfo()
	 * @method \boolean[] getIsConfirmationOnList()
	 * @method \boolean[] fillIsConfirmationOn()
	 * @method \string[] getTemplateTypeConfirmationList()
	 * @method \string[] fillTemplateTypeConfirmation()
	 * @method \boolean[] getIsReminderOnList()
	 * @method \boolean[] fillIsReminderOn()
	 * @method \string[] getTemplateTypeReminderList()
	 * @method \string[] fillTemplateTypeReminder()
	 * @method \boolean[] getIsFeedbackOnList()
	 * @method \boolean[] fillIsFeedbackOn()
	 * @method \string[] getTemplateTypeFeedbackList()
	 * @method \string[] fillTemplateTypeFeedback()
	 * @method \boolean[] getIsDelayedOnList()
	 * @method \boolean[] fillIsDelayedOn()
	 * @method \string[] getTemplateTypeDelayedList()
	 * @method \string[] fillTemplateTypeDelayed()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection merge(?\Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ResourceTypeNotificationSettings_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\ResourceTypeNotificationSettingsTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceTypeNotificationSettingsTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ResourceTypeNotificationSettings_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection fetchCollection()
	 */
	class EO_ResourceTypeNotificationSettings_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection fetchCollection()
	 */
	class EO_ResourceTypeNotificationSettings_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection wakeUpCollection($rows)
	 */
	class EO_ResourceTypeNotificationSettings_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\ResourceSettingsTable:booking/lib/Internals/Model/ResourceSettingsTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceSettings
	 * @see \Bitrix\Booking\Internals\Model\ResourceSettingsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings setResourceId(\int|\Bitrix\Main\DB\SqlExpression $resourceId)
	 * @method bool hasResourceId()
	 * @method bool isResourceIdFilled()
	 * @method bool isResourceIdChanged()
	 * @method \int remindActualResourceId()
	 * @method \int requireResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings resetResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings unsetResourceId()
	 * @method \int fillResourceId()
	 * @method \string getWeekdays()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings setWeekdays(\string|\Bitrix\Main\DB\SqlExpression $weekdays)
	 * @method bool hasWeekdays()
	 * @method bool isWeekdaysFilled()
	 * @method bool isWeekdaysChanged()
	 * @method \string remindActualWeekdays()
	 * @method \string requireWeekdays()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings resetWeekdays()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings unsetWeekdays()
	 * @method \string fillWeekdays()
	 * @method \int getSlotSize()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings setSlotSize(\int|\Bitrix\Main\DB\SqlExpression $slotSize)
	 * @method bool hasSlotSize()
	 * @method bool isSlotSizeFilled()
	 * @method bool isSlotSizeChanged()
	 * @method \int remindActualSlotSize()
	 * @method \int requireSlotSize()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings resetSlotSize()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings unsetSlotSize()
	 * @method \int fillSlotSize()
	 * @method \int getTimeFrom()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings setTimeFrom(\int|\Bitrix\Main\DB\SqlExpression $timeFrom)
	 * @method bool hasTimeFrom()
	 * @method bool isTimeFromFilled()
	 * @method bool isTimeFromChanged()
	 * @method \int remindActualTimeFrom()
	 * @method \int requireTimeFrom()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings resetTimeFrom()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings unsetTimeFrom()
	 * @method \int fillTimeFrom()
	 * @method \int getTimeTo()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings setTimeTo(\int|\Bitrix\Main\DB\SqlExpression $timeTo)
	 * @method bool hasTimeTo()
	 * @method bool isTimeToFilled()
	 * @method bool isTimeToChanged()
	 * @method \int remindActualTimeTo()
	 * @method \int requireTimeTo()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings resetTimeTo()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings unsetTimeTo()
	 * @method \int fillTimeTo()
	 * @method \string getTimezone()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings setTimezone(\string|\Bitrix\Main\DB\SqlExpression $timezone)
	 * @method bool hasTimezone()
	 * @method bool isTimezoneFilled()
	 * @method bool isTimezoneChanged()
	 * @method \string remindActualTimezone()
	 * @method \string requireTimezone()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings resetTimezone()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings unsetTimezone()
	 * @method \string fillTimezone()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource getResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource remindActualResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource requireResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings setResource(\Bitrix\Booking\Internals\Model\EO_Resource $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings resetResource()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings unsetResource()
	 * @method bool hasResource()
	 * @method bool isResourceFilled()
	 * @method bool isResourceChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource fillResource()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceSettings wakeUp($data)
	 */
	class EO_ResourceSettings {
		/* @var \Bitrix\Booking\Internals\Model\ResourceSettingsTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceSettingsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceSettings_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getResourceIdList()
	 * @method \int[] fillResourceId()
	 * @method \string[] getWeekdaysList()
	 * @method \string[] fillWeekdays()
	 * @method \int[] getSlotSizeList()
	 * @method \int[] fillSlotSize()
	 * @method \int[] getTimeFromList()
	 * @method \int[] fillTimeFrom()
	 * @method \int[] getTimeToList()
	 * @method \int[] fillTimeTo()
	 * @method \string[] getTimezoneList()
	 * @method \string[] fillTimezone()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource[] getResourceList()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection getResourceCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Resource_Collection fillResource()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_ResourceSettings $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_ResourceSettings $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_ResourceSettings $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection merge(?\Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ResourceSettings_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\ResourceSettingsTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceSettingsTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ResourceSettings_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection fetchCollection()
	 */
	class EO_ResourceSettings_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection fetchCollection()
	 */
	class EO_ResourceSettings_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection wakeUpCollection($rows)
	 */
	class EO_ResourceSettings_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\BookingMessageTable:booking/lib/Internals/Model/BookingMessageTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingMessage
	 * @see \Bitrix\Booking\Internals\Model\BookingMessageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage setBookingId(\int|\Bitrix\Main\DB\SqlExpression $bookingId)
	 * @method bool hasBookingId()
	 * @method bool isBookingIdFilled()
	 * @method bool isBookingIdChanged()
	 * @method \int remindActualBookingId()
	 * @method \int requireBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage resetBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage unsetBookingId()
	 * @method \int fillBookingId()
	 * @method \string getNotificationType()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage setNotificationType(\string|\Bitrix\Main\DB\SqlExpression $notificationType)
	 * @method bool hasNotificationType()
	 * @method bool isNotificationTypeFilled()
	 * @method bool isNotificationTypeChanged()
	 * @method \string remindActualNotificationType()
	 * @method \string requireNotificationType()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage resetNotificationType()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage unsetNotificationType()
	 * @method \string fillNotificationType()
	 * @method \string getSenderModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage setSenderModuleId(\string|\Bitrix\Main\DB\SqlExpression $senderModuleId)
	 * @method bool hasSenderModuleId()
	 * @method bool isSenderModuleIdFilled()
	 * @method bool isSenderModuleIdChanged()
	 * @method \string remindActualSenderModuleId()
	 * @method \string requireSenderModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage resetSenderModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage unsetSenderModuleId()
	 * @method \string fillSenderModuleId()
	 * @method \string getSenderCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage setSenderCode(\string|\Bitrix\Main\DB\SqlExpression $senderCode)
	 * @method bool hasSenderCode()
	 * @method bool isSenderCodeFilled()
	 * @method bool isSenderCodeChanged()
	 * @method \string remindActualSenderCode()
	 * @method \string requireSenderCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage resetSenderCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage unsetSenderCode()
	 * @method \string fillSenderCode()
	 * @method \int getExternalMessageId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage setExternalMessageId(\int|\Bitrix\Main\DB\SqlExpression $externalMessageId)
	 * @method bool hasExternalMessageId()
	 * @method bool isExternalMessageIdFilled()
	 * @method bool isExternalMessageIdChanged()
	 * @method \int remindActualExternalMessageId()
	 * @method \int requireExternalMessageId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage resetExternalMessageId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage unsetExternalMessageId()
	 * @method \int fillExternalMessageId()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage resetCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking getBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking remindActualBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking requireBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage setBooking(\Bitrix\Booking\Internals\Model\EO_Booking $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage resetBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage unsetBooking()
	 * @method bool hasBooking()
	 * @method bool isBookingFilled()
	 * @method bool isBookingChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking fillBooking()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingMessage wakeUp($data)
	 */
	class EO_BookingMessage {
		/* @var \Bitrix\Booking\Internals\Model\BookingMessageTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingMessageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingMessage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBookingIdList()
	 * @method \int[] fillBookingId()
	 * @method \string[] getNotificationTypeList()
	 * @method \string[] fillNotificationType()
	 * @method \string[] getSenderModuleIdList()
	 * @method \string[] fillSenderModuleId()
	 * @method \string[] getSenderCodeList()
	 * @method \string[] fillSenderCode()
	 * @method \int[] getExternalMessageIdList()
	 * @method \int[] fillExternalMessageId()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking[] getBookingList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection getBookingCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection fillBooking()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_BookingMessage $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_BookingMessage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_BookingMessage $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection merge(?\Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_BookingMessage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\BookingMessageTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingMessageTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_BookingMessage_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection fetchCollection()
	 */
	class EO_BookingMessage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection fetchCollection()
	 */
	class EO_BookingMessage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection wakeUpCollection($rows)
	 */
	class EO_BookingMessage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\ResourceNotificationSettingsTable:booking/lib/Internals/Model/ResourceNotificationSettingsTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceNotificationSettings
	 * @see \Bitrix\Booking\Internals\Model\ResourceNotificationSettingsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setResourceId(\int|\Bitrix\Main\DB\SqlExpression $resourceId)
	 * @method bool hasResourceId()
	 * @method bool isResourceIdFilled()
	 * @method bool isResourceIdChanged()
	 * @method \int remindActualResourceId()
	 * @method \int requireResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetResourceId()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetResourceId()
	 * @method \int fillResourceId()
	 * @method \boolean getIsInfoOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setIsInfoOn(\boolean|\Bitrix\Main\DB\SqlExpression $isInfoOn)
	 * @method bool hasIsInfoOn()
	 * @method bool isIsInfoOnFilled()
	 * @method bool isIsInfoOnChanged()
	 * @method \boolean remindActualIsInfoOn()
	 * @method \boolean requireIsInfoOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetIsInfoOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetIsInfoOn()
	 * @method \boolean fillIsInfoOn()
	 * @method \string getTemplateTypeInfo()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setTemplateTypeInfo(\string|\Bitrix\Main\DB\SqlExpression $templateTypeInfo)
	 * @method bool hasTemplateTypeInfo()
	 * @method bool isTemplateTypeInfoFilled()
	 * @method bool isTemplateTypeInfoChanged()
	 * @method \string remindActualTemplateTypeInfo()
	 * @method \string requireTemplateTypeInfo()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetTemplateTypeInfo()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetTemplateTypeInfo()
	 * @method \string fillTemplateTypeInfo()
	 * @method \boolean getIsConfirmationOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setIsConfirmationOn(\boolean|\Bitrix\Main\DB\SqlExpression $isConfirmationOn)
	 * @method bool hasIsConfirmationOn()
	 * @method bool isIsConfirmationOnFilled()
	 * @method bool isIsConfirmationOnChanged()
	 * @method \boolean remindActualIsConfirmationOn()
	 * @method \boolean requireIsConfirmationOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetIsConfirmationOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetIsConfirmationOn()
	 * @method \boolean fillIsConfirmationOn()
	 * @method \string getTemplateTypeConfirmation()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setTemplateTypeConfirmation(\string|\Bitrix\Main\DB\SqlExpression $templateTypeConfirmation)
	 * @method bool hasTemplateTypeConfirmation()
	 * @method bool isTemplateTypeConfirmationFilled()
	 * @method bool isTemplateTypeConfirmationChanged()
	 * @method \string remindActualTemplateTypeConfirmation()
	 * @method \string requireTemplateTypeConfirmation()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetTemplateTypeConfirmation()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetTemplateTypeConfirmation()
	 * @method \string fillTemplateTypeConfirmation()
	 * @method \boolean getIsReminderOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setIsReminderOn(\boolean|\Bitrix\Main\DB\SqlExpression $isReminderOn)
	 * @method bool hasIsReminderOn()
	 * @method bool isIsReminderOnFilled()
	 * @method bool isIsReminderOnChanged()
	 * @method \boolean remindActualIsReminderOn()
	 * @method \boolean requireIsReminderOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetIsReminderOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetIsReminderOn()
	 * @method \boolean fillIsReminderOn()
	 * @method \string getTemplateTypeReminder()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setTemplateTypeReminder(\string|\Bitrix\Main\DB\SqlExpression $templateTypeReminder)
	 * @method bool hasTemplateTypeReminder()
	 * @method bool isTemplateTypeReminderFilled()
	 * @method bool isTemplateTypeReminderChanged()
	 * @method \string remindActualTemplateTypeReminder()
	 * @method \string requireTemplateTypeReminder()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetTemplateTypeReminder()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetTemplateTypeReminder()
	 * @method \string fillTemplateTypeReminder()
	 * @method \boolean getIsFeedbackOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setIsFeedbackOn(\boolean|\Bitrix\Main\DB\SqlExpression $isFeedbackOn)
	 * @method bool hasIsFeedbackOn()
	 * @method bool isIsFeedbackOnFilled()
	 * @method bool isIsFeedbackOnChanged()
	 * @method \boolean remindActualIsFeedbackOn()
	 * @method \boolean requireIsFeedbackOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetIsFeedbackOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetIsFeedbackOn()
	 * @method \boolean fillIsFeedbackOn()
	 * @method \string getTemplateTypeFeedback()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setTemplateTypeFeedback(\string|\Bitrix\Main\DB\SqlExpression $templateTypeFeedback)
	 * @method bool hasTemplateTypeFeedback()
	 * @method bool isTemplateTypeFeedbackFilled()
	 * @method bool isTemplateTypeFeedbackChanged()
	 * @method \string remindActualTemplateTypeFeedback()
	 * @method \string requireTemplateTypeFeedback()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetTemplateTypeFeedback()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetTemplateTypeFeedback()
	 * @method \string fillTemplateTypeFeedback()
	 * @method \boolean getIsDelayedOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setIsDelayedOn(\boolean|\Bitrix\Main\DB\SqlExpression $isDelayedOn)
	 * @method bool hasIsDelayedOn()
	 * @method bool isIsDelayedOnFilled()
	 * @method bool isIsDelayedOnChanged()
	 * @method \boolean remindActualIsDelayedOn()
	 * @method \boolean requireIsDelayedOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetIsDelayedOn()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetIsDelayedOn()
	 * @method \boolean fillIsDelayedOn()
	 * @method \string getTemplateTypeDelayed()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings setTemplateTypeDelayed(\string|\Bitrix\Main\DB\SqlExpression $templateTypeDelayed)
	 * @method bool hasTemplateTypeDelayed()
	 * @method bool isTemplateTypeDelayedFilled()
	 * @method bool isTemplateTypeDelayedChanged()
	 * @method \string remindActualTemplateTypeDelayed()
	 * @method \string requireTemplateTypeDelayed()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings resetTemplateTypeDelayed()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unsetTemplateTypeDelayed()
	 * @method \string fillTemplateTypeDelayed()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings wakeUp($data)
	 */
	class EO_ResourceNotificationSettings {
		/* @var \Bitrix\Booking\Internals\Model\ResourceNotificationSettingsTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceNotificationSettingsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_ResourceNotificationSettings_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getResourceIdList()
	 * @method \int[] fillResourceId()
	 * @method \boolean[] getIsInfoOnList()
	 * @method \boolean[] fillIsInfoOn()
	 * @method \string[] getTemplateTypeInfoList()
	 * @method \string[] fillTemplateTypeInfo()
	 * @method \boolean[] getIsConfirmationOnList()
	 * @method \boolean[] fillIsConfirmationOn()
	 * @method \string[] getTemplateTypeConfirmationList()
	 * @method \string[] fillTemplateTypeConfirmation()
	 * @method \boolean[] getIsReminderOnList()
	 * @method \boolean[] fillIsReminderOn()
	 * @method \string[] getTemplateTypeReminderList()
	 * @method \string[] fillTemplateTypeReminder()
	 * @method \boolean[] getIsFeedbackOnList()
	 * @method \boolean[] fillIsFeedbackOn()
	 * @method \string[] getTemplateTypeFeedbackList()
	 * @method \string[] fillTemplateTypeFeedback()
	 * @method \boolean[] getIsDelayedOnList()
	 * @method \boolean[] fillIsDelayedOn()
	 * @method \string[] getTemplateTypeDelayedList()
	 * @method \string[] fillTemplateTypeDelayed()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection merge(?\Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ResourceNotificationSettings_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\ResourceNotificationSettingsTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\ResourceNotificationSettingsTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ResourceNotificationSettings_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection fetchCollection()
	 */
	class EO_ResourceNotificationSettings_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection fetchCollection()
	 */
	class EO_ResourceNotificationSettings_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection wakeUpCollection($rows)
	 */
	class EO_ResourceNotificationSettings_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\BookingExternalDataTable:booking/lib/Internals/Model/BookingExternalDataTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingExternalData
	 * @see \Bitrix\Booking\Internals\Model\BookingExternalDataTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData setBookingId(\int|\Bitrix\Main\DB\SqlExpression $bookingId)
	 * @method bool hasBookingId()
	 * @method bool isBookingIdFilled()
	 * @method bool isBookingIdChanged()
	 * @method \int remindActualBookingId()
	 * @method \int requireBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData resetBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData unsetBookingId()
	 * @method \int fillBookingId()
	 * @method \string getModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData resetModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData unsetModuleId()
	 * @method \string fillModuleId()
	 * @method \string getEntityTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData setEntityTypeId(\string|\Bitrix\Main\DB\SqlExpression $entityTypeId)
	 * @method bool hasEntityTypeId()
	 * @method bool isEntityTypeIdFilled()
	 * @method bool isEntityTypeIdChanged()
	 * @method \string remindActualEntityTypeId()
	 * @method \string requireEntityTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData resetEntityTypeId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData unsetEntityTypeId()
	 * @method \string fillEntityTypeId()
	 * @method \string getValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData resetValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData unsetValue()
	 * @method \string fillValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking getBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking remindActualBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking requireBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData setBooking(\Bitrix\Booking\Internals\Model\EO_Booking $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData resetBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData unsetBooking()
	 * @method bool hasBooking()
	 * @method bool isBookingFilled()
	 * @method bool isBookingChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking fillBooking()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingExternalData wakeUp($data)
	 */
	class EO_BookingExternalData {
		/* @var \Bitrix\Booking\Internals\Model\BookingExternalDataTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingExternalDataTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingExternalData_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBookingIdList()
	 * @method \int[] fillBookingId()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method \string[] getEntityTypeIdList()
	 * @method \string[] fillEntityTypeId()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking[] getBookingList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection getBookingCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection fillBooking()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_BookingExternalData $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_BookingExternalData $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_BookingExternalData $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection merge(?\Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_BookingExternalData_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\BookingExternalDataTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingExternalDataTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_BookingExternalData_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection fetchCollection()
	 */
	class EO_BookingExternalData_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection fetchCollection()
	 */
	class EO_BookingExternalData_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection wakeUpCollection($rows)
	 */
	class EO_BookingExternalData_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\BookingMessageFailureLogTable:booking/lib/Internals/Model/BookingMessageFailureLogTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingMessageFailureLog
	 * @see \Bitrix\Booking\Internals\Model\BookingMessageFailureLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog setBookingId(\int|\Bitrix\Main\DB\SqlExpression $bookingId)
	 * @method bool hasBookingId()
	 * @method bool isBookingIdFilled()
	 * @method bool isBookingIdChanged()
	 * @method \int remindActualBookingId()
	 * @method \int requireBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog resetBookingId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog unsetBookingId()
	 * @method \int fillBookingId()
	 * @method \string getNotificationType()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog setNotificationType(\string|\Bitrix\Main\DB\SqlExpression $notificationType)
	 * @method bool hasNotificationType()
	 * @method bool isNotificationTypeFilled()
	 * @method bool isNotificationTypeChanged()
	 * @method \string remindActualNotificationType()
	 * @method \string requireNotificationType()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog resetNotificationType()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog unsetNotificationType()
	 * @method \string fillNotificationType()
	 * @method \string getSenderModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog setSenderModuleId(\string|\Bitrix\Main\DB\SqlExpression $senderModuleId)
	 * @method bool hasSenderModuleId()
	 * @method bool isSenderModuleIdFilled()
	 * @method bool isSenderModuleIdChanged()
	 * @method \string remindActualSenderModuleId()
	 * @method \string requireSenderModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog resetSenderModuleId()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog unsetSenderModuleId()
	 * @method \string fillSenderModuleId()
	 * @method \string getSenderCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog setSenderCode(\string|\Bitrix\Main\DB\SqlExpression $senderCode)
	 * @method bool hasSenderCode()
	 * @method bool isSenderCodeFilled()
	 * @method bool isSenderCodeChanged()
	 * @method \string remindActualSenderCode()
	 * @method \string requireSenderCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog resetSenderCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog unsetSenderCode()
	 * @method \string fillSenderCode()
	 * @method \string getReasonCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog setReasonCode(\string|\Bitrix\Main\DB\SqlExpression $reasonCode)
	 * @method bool hasReasonCode()
	 * @method bool isReasonCodeFilled()
	 * @method bool isReasonCodeChanged()
	 * @method \string remindActualReasonCode()
	 * @method \string requireReasonCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog resetReasonCode()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog unsetReasonCode()
	 * @method \string fillReasonCode()
	 * @method \string getReasonText()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog setReasonText(\string|\Bitrix\Main\DB\SqlExpression $reasonText)
	 * @method bool hasReasonText()
	 * @method bool isReasonTextFilled()
	 * @method bool isReasonTextChanged()
	 * @method \string remindActualReasonText()
	 * @method \string requireReasonText()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog resetReasonText()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog unsetReasonText()
	 * @method \string fillReasonText()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog resetCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking getBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking remindActualBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking requireBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog setBooking(\Bitrix\Booking\Internals\Model\EO_Booking $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog resetBooking()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog unsetBooking()
	 * @method bool hasBooking()
	 * @method bool isBookingFilled()
	 * @method bool isBookingChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking fillBooking()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog wakeUp($data)
	 */
	class EO_BookingMessageFailureLog {
		/* @var \Bitrix\Booking\Internals\Model\BookingMessageFailureLogTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingMessageFailureLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_BookingMessageFailureLog_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBookingIdList()
	 * @method \int[] fillBookingId()
	 * @method \string[] getNotificationTypeList()
	 * @method \string[] fillNotificationType()
	 * @method \string[] getSenderModuleIdList()
	 * @method \string[] fillSenderModuleId()
	 * @method \string[] getSenderCodeList()
	 * @method \string[] fillSenderCode()
	 * @method \string[] getReasonCodeList()
	 * @method \string[] fillReasonCode()
	 * @method \string[] getReasonTextList()
	 * @method \string[] fillReasonText()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking[] getBookingList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection getBookingCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection fillBooking()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection merge(?\Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_BookingMessageFailureLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\BookingMessageFailureLogTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingMessageFailureLogTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_BookingMessageFailureLog_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection fetchCollection()
	 */
	class EO_BookingMessageFailureLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection fetchCollection()
	 */
	class EO_BookingMessageFailureLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection wakeUpCollection($rows)
	 */
	class EO_BookingMessageFailureLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Booking\Internals\Model\BookingTable:booking/lib/Internals/Model/BookingTable.php */
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Booking
	 * @see \Bitrix\Booking\Internals\Model\BookingTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetName()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetName()
	 * @method \string fillName()
	 * @method \int getDateFrom()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setDateFrom(\int|\Bitrix\Main\DB\SqlExpression $dateFrom)
	 * @method bool hasDateFrom()
	 * @method bool isDateFromFilled()
	 * @method bool isDateFromChanged()
	 * @method \int remindActualDateFrom()
	 * @method \int requireDateFrom()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetDateFrom()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetDateFrom()
	 * @method \int fillDateFrom()
	 * @method \int getDateTo()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setDateTo(\int|\Bitrix\Main\DB\SqlExpression $dateTo)
	 * @method bool hasDateTo()
	 * @method bool isDateToFilled()
	 * @method bool isDateToChanged()
	 * @method \int remindActualDateTo()
	 * @method \int requireDateTo()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetDateTo()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetDateTo()
	 * @method \int fillDateTo()
	 * @method \string getTimezoneFrom()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setTimezoneFrom(\string|\Bitrix\Main\DB\SqlExpression $timezoneFrom)
	 * @method bool hasTimezoneFrom()
	 * @method bool isTimezoneFromFilled()
	 * @method bool isTimezoneFromChanged()
	 * @method \string remindActualTimezoneFrom()
	 * @method \string requireTimezoneFrom()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetTimezoneFrom()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetTimezoneFrom()
	 * @method \string fillTimezoneFrom()
	 * @method \string getTimezoneTo()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setTimezoneTo(\string|\Bitrix\Main\DB\SqlExpression $timezoneTo)
	 * @method bool hasTimezoneTo()
	 * @method bool isTimezoneToFilled()
	 * @method bool isTimezoneToChanged()
	 * @method \string remindActualTimezoneTo()
	 * @method \string requireTimezoneTo()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetTimezoneTo()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetTimezoneTo()
	 * @method \string fillTimezoneTo()
	 * @method \int getTimezoneFromOffset()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setTimezoneFromOffset(\int|\Bitrix\Main\DB\SqlExpression $timezoneFromOffset)
	 * @method bool hasTimezoneFromOffset()
	 * @method bool isTimezoneFromOffsetFilled()
	 * @method bool isTimezoneFromOffsetChanged()
	 * @method \int remindActualTimezoneFromOffset()
	 * @method \int requireTimezoneFromOffset()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetTimezoneFromOffset()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetTimezoneFromOffset()
	 * @method \int fillTimezoneFromOffset()
	 * @method \int getTimezoneToOffset()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setTimezoneToOffset(\int|\Bitrix\Main\DB\SqlExpression $timezoneToOffset)
	 * @method bool hasTimezoneToOffset()
	 * @method bool isTimezoneToOffsetFilled()
	 * @method bool isTimezoneToOffsetChanged()
	 * @method \int remindActualTimezoneToOffset()
	 * @method \int requireTimezoneToOffset()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetTimezoneToOffset()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetTimezoneToOffset()
	 * @method \int fillTimezoneToOffset()
	 * @method \int getDateMax()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setDateMax(\int|\Bitrix\Main\DB\SqlExpression $dateMax)
	 * @method bool hasDateMax()
	 * @method bool isDateMaxFilled()
	 * @method bool isDateMaxChanged()
	 * @method \int remindActualDateMax()
	 * @method \int requireDateMax()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetDateMax()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetDateMax()
	 * @method \int fillDateMax()
	 * @method \boolean getIsRecurring()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setIsRecurring(\boolean|\Bitrix\Main\DB\SqlExpression $isRecurring)
	 * @method bool hasIsRecurring()
	 * @method bool isIsRecurringFilled()
	 * @method bool isIsRecurringChanged()
	 * @method \boolean remindActualIsRecurring()
	 * @method \boolean requireIsRecurring()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetIsRecurring()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetIsRecurring()
	 * @method \boolean fillIsRecurring()
	 * @method \string getRrule()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setRrule(\string|\Bitrix\Main\DB\SqlExpression $rrule)
	 * @method bool hasRrule()
	 * @method bool isRruleFilled()
	 * @method bool isRruleChanged()
	 * @method \string remindActualRrule()
	 * @method \string requireRrule()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetRrule()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetRrule()
	 * @method \string fillRrule()
	 * @method \int getParentId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetParentId()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetParentId()
	 * @method \int fillParentId()
	 * @method \boolean getIsDeleted()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setIsDeleted(\boolean|\Bitrix\Main\DB\SqlExpression $isDeleted)
	 * @method bool hasIsDeleted()
	 * @method bool isIsDeletedFilled()
	 * @method bool isIsDeletedChanged()
	 * @method \boolean remindActualIsDeleted()
	 * @method \boolean requireIsDeleted()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetIsDeleted()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetIsDeleted()
	 * @method \boolean fillIsDeleted()
	 * @method \boolean getIsConfirmed()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setIsConfirmed(\boolean|\Bitrix\Main\DB\SqlExpression $isConfirmed)
	 * @method bool hasIsConfirmed()
	 * @method bool isIsConfirmedFilled()
	 * @method bool isIsConfirmedChanged()
	 * @method \boolean remindActualIsConfirmed()
	 * @method \boolean requireIsConfirmed()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetIsConfirmed()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetIsConfirmed()
	 * @method \boolean fillIsConfirmed()
	 * @method \string getDescription()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetDescription()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetDescription()
	 * @method \string fillDescription()
	 * @method \string getVisitStatus()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setVisitStatus(\string|\Bitrix\Main\DB\SqlExpression $visitStatus)
	 * @method bool hasVisitStatus()
	 * @method bool isVisitStatusFilled()
	 * @method bool isVisitStatusChanged()
	 * @method \string remindActualVisitStatus()
	 * @method \string requireVisitStatus()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetVisitStatus()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetVisitStatus()
	 * @method \string fillVisitStatus()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetCreatedBy()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetCreatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetUpdatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection getResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection requireResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection fillResources()
	 * @method bool hasResources()
	 * @method bool isResourcesFilled()
	 * @method bool isResourcesChanged()
	 * @method void addToResources(\Bitrix\Booking\Internals\Model\EO_BookingResource $bookingResource)
	 * @method void removeFromResources(\Bitrix\Booking\Internals\Model\EO_BookingResource $bookingResource)
	 * @method void removeAllResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection getClients()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection requireClients()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection fillClients()
	 * @method bool hasClients()
	 * @method bool isClientsFilled()
	 * @method bool isClientsChanged()
	 * @method void addToClients(\Bitrix\Booking\Internals\Model\EO_BookingClient $bookingClient)
	 * @method void removeFromClients(\Bitrix\Booking\Internals\Model\EO_BookingClient $bookingClient)
	 * @method void removeAllClients()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetClients()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetClients()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection getExternalData()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection requireExternalData()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection fillExternalData()
	 * @method bool hasExternalData()
	 * @method bool isExternalDataFilled()
	 * @method bool isExternalDataChanged()
	 * @method void addToExternalData(\Bitrix\Booking\Internals\Model\EO_BookingExternalData $bookingExternalData)
	 * @method void removeFromExternalData(\Bitrix\Booking\Internals\Model\EO_BookingExternalData $bookingExternalData)
	 * @method void removeAllExternalData()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetExternalData()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetExternalData()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection getMessages()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection requireMessages()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection fillMessages()
	 * @method bool hasMessages()
	 * @method bool isMessagesFilled()
	 * @method bool isMessagesChanged()
	 * @method void addToMessages(\Bitrix\Booking\Internals\Model\EO_BookingMessage $bookingMessage)
	 * @method void removeFromMessages(\Bitrix\Booking\Internals\Model\EO_BookingMessage $bookingMessage)
	 * @method void removeAllMessages()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetMessages()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetMessages()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection getFailureLogItems()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection requireFailureLogItems()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection fillFailureLogItems()
	 * @method bool hasFailureLogItems()
	 * @method bool isFailureLogItemsFilled()
	 * @method bool isFailureLogItemsChanged()
	 * @method void addToFailureLogItems(\Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog $bookingMessageFailureLog)
	 * @method void removeFromFailureLogItems(\Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog $bookingMessageFailureLog)
	 * @method void removeAllFailureLogItems()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetFailureLogItems()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetFailureLogItems()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes getNote()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes remindActualNote()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes requireNote()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking setNote(\Bitrix\Booking\Internals\Model\EO_Notes $object)
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking resetNote()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unsetNote()
	 * @method bool hasNote()
	 * @method bool isNoteFilled()
	 * @method bool isNoteChanged()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes fillNote()
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
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking set($fieldName, $value)
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking reset($fieldName)
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Booking\Internals\Model\EO_Booking wakeUp($data)
	 */
	class EO_Booking {
		/* @var \Bitrix\Booking\Internals\Model\BookingTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * EO_Booking_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getDateFromList()
	 * @method \int[] fillDateFrom()
	 * @method \int[] getDateToList()
	 * @method \int[] fillDateTo()
	 * @method \string[] getTimezoneFromList()
	 * @method \string[] fillTimezoneFrom()
	 * @method \string[] getTimezoneToList()
	 * @method \string[] fillTimezoneTo()
	 * @method \int[] getTimezoneFromOffsetList()
	 * @method \int[] fillTimezoneFromOffset()
	 * @method \int[] getTimezoneToOffsetList()
	 * @method \int[] fillTimezoneToOffset()
	 * @method \int[] getDateMaxList()
	 * @method \int[] fillDateMax()
	 * @method \boolean[] getIsRecurringList()
	 * @method \boolean[] fillIsRecurring()
	 * @method \string[] getRruleList()
	 * @method \string[] fillRrule()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \boolean[] getIsDeletedList()
	 * @method \boolean[] fillIsDeleted()
	 * @method \boolean[] getIsConfirmedList()
	 * @method \boolean[] fillIsConfirmed()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \string[] getVisitStatusList()
	 * @method \string[] fillVisitStatus()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection[] getResourcesList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection getResourcesCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection fillResources()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection[] getClientsList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection getClientsCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection fillClients()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection[] getExternalDataList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection getExternalDataCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection fillExternalData()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection[] getMessagesList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection getMessagesCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection fillMessages()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection[] getFailureLogItemsList()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection getFailureLogItemsCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_BookingMessageFailureLog_Collection fillFailureLogItems()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes[] getNoteList()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection getNoteCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Notes_Collection fillNote()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Booking\Internals\Model\EO_Booking $object)
	 * @method bool has(\Bitrix\Booking\Internals\Model\EO_Booking $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking getByPrimary($primary)
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking[] getAll()
	 * @method bool remove(\Bitrix\Booking\Internals\Model\EO_Booking $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Booking\Internals\Model\EO_Booking_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection merge(?\Bitrix\Booking\Internals\Model\EO_Booking_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Booking_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Booking\Internals\Model\BookingTable */
		static public $dataClass = '\Bitrix\Booking\Internals\Model\BookingTable';
	}
}
namespace Bitrix\Booking\Internals\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Booking_Result exec()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection fetchCollection()
	 */
	class EO_Booking_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking fetchObject()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection fetchCollection()
	 */
	class EO_Booking_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking createObject($setDefaultValues = true)
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection createCollection()
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking wakeUpObject($row)
	 * @method \Bitrix\Booking\Internals\Model\EO_Booking_Collection wakeUpCollection($rows)
	 */
	class EO_Booking_Entity extends \Bitrix\Main\ORM\Entity {}
}