<?php

/* ORMENTITYANNOTATION:Bitrix\Dav\TokensTable:dav/lib/tokens.php:df380696f68f74586cd52d454ec26459 */
namespace Bitrix\Dav {
	/**
	 * EO_Tokens
	 * @see \Bitrix\Dav\TokensTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getToken()
	 * @method \Bitrix\Dav\EO_Tokens setToken(\string|\Bitrix\Main\DB\SqlExpression $token)
	 * @method bool hasToken()
	 * @method bool isTokenFilled()
	 * @method bool isTokenChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Dav\EO_Tokens setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Dav\EO_Tokens resetUserId()
	 * @method \Bitrix\Dav\EO_Tokens unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\Type\DateTime getExpiredAt()
	 * @method \Bitrix\Dav\EO_Tokens setExpiredAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $expiredAt)
	 * @method bool hasExpiredAt()
	 * @method bool isExpiredAtFilled()
	 * @method bool isExpiredAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualExpiredAt()
	 * @method \Bitrix\Main\Type\DateTime requireExpiredAt()
	 * @method \Bitrix\Dav\EO_Tokens resetExpiredAt()
	 * @method \Bitrix\Dav\EO_Tokens unsetExpiredAt()
	 * @method \Bitrix\Main\Type\DateTime fillExpiredAt()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Dav\EO_Tokens setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Dav\EO_Tokens resetUser()
	 * @method \Bitrix\Dav\EO_Tokens unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
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
	 * @method \Bitrix\Dav\EO_Tokens set($fieldName, $value)
	 * @method \Bitrix\Dav\EO_Tokens reset($fieldName)
	 * @method \Bitrix\Dav\EO_Tokens unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Dav\EO_Tokens wakeUp($data)
	 */
	class EO_Tokens {
		/* @var \Bitrix\Dav\TokensTable */
		static public $dataClass = '\Bitrix\Dav\TokensTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Dav {
	/**
	 * EO_Tokens_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getTokenList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\Type\DateTime[] getExpiredAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillExpiredAt()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Dav\EO_Tokens_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Dav\EO_Tokens $object)
	 * @method bool has(\Bitrix\Dav\EO_Tokens $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Dav\EO_Tokens getByPrimary($primary)
	 * @method \Bitrix\Dav\EO_Tokens[] getAll()
	 * @method bool remove(\Bitrix\Dav\EO_Tokens $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Dav\EO_Tokens_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Dav\EO_Tokens current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Tokens_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Dav\TokensTable */
		static public $dataClass = '\Bitrix\Dav\TokensTable';
	}
}
namespace Bitrix\Dav {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Tokens_Result exec()
	 * @method \Bitrix\Dav\EO_Tokens fetchObject()
	 * @method \Bitrix\Dav\EO_Tokens_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Tokens_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Dav\EO_Tokens fetchObject()
	 * @method \Bitrix\Dav\EO_Tokens_Collection fetchCollection()
	 */
	class EO_Tokens_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Dav\EO_Tokens createObject($setDefaultValues = true)
	 * @method \Bitrix\Dav\EO_Tokens_Collection createCollection()
	 * @method \Bitrix\Dav\EO_Tokens wakeUpObject($row)
	 * @method \Bitrix\Dav\EO_Tokens_Collection wakeUpCollection($rows)
	 */
	class EO_Tokens_Entity extends \Bitrix\Main\ORM\Entity {}
}