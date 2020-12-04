<?php

/* ORMENTITYANNOTATION:Bitrix\Security\Mfa\RecoveryCodesTable:security/lib/mfa/recoverycodes.php:ec751d7e49c13541d88294907ccee999 */
namespace Bitrix\Security\Mfa {
	/**
	 * EO_RecoveryCodes
	 * @see \Bitrix\Security\Mfa\RecoveryCodesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes resetUserId()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getCode()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes resetCode()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes unsetCode()
	 * @method \string fillCode()
	 * @method \boolean getUsed()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes setUsed(\boolean|\Bitrix\Main\DB\SqlExpression $used)
	 * @method bool hasUsed()
	 * @method bool isUsedFilled()
	 * @method bool isUsedChanged()
	 * @method \boolean remindActualUsed()
	 * @method \boolean requireUsed()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes resetUsed()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes unsetUsed()
	 * @method \boolean fillUsed()
	 * @method \Bitrix\Main\Type\DateTime getUsingDate()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes setUsingDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $usingDate)
	 * @method bool hasUsingDate()
	 * @method bool isUsingDateFilled()
	 * @method bool isUsingDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUsingDate()
	 * @method \Bitrix\Main\Type\DateTime requireUsingDate()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes resetUsingDate()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes unsetUsingDate()
	 * @method \Bitrix\Main\Type\DateTime fillUsingDate()
	 * @method \string getUsingIp()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes setUsingIp(\string|\Bitrix\Main\DB\SqlExpression $usingIp)
	 * @method bool hasUsingIp()
	 * @method bool isUsingIpFilled()
	 * @method bool isUsingIpChanged()
	 * @method \string remindActualUsingIp()
	 * @method \string requireUsingIp()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes resetUsingIp()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes unsetUsingIp()
	 * @method \string fillUsingIp()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes resetUser()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes unsetUser()
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
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes set($fieldName, $value)
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes reset($fieldName)
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Security\Mfa\EO_RecoveryCodes wakeUp($data)
	 */
	class EO_RecoveryCodes {
		/* @var \Bitrix\Security\Mfa\RecoveryCodesTable */
		static public $dataClass = '\Bitrix\Security\Mfa\RecoveryCodesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Security\Mfa {
	/**
	 * EO_RecoveryCodes_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \boolean[] getUsedList()
	 * @method \boolean[] fillUsed()
	 * @method \Bitrix\Main\Type\DateTime[] getUsingDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUsingDate()
	 * @method \string[] getUsingIpList()
	 * @method \string[] fillUsingIp()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Security\Mfa\EO_RecoveryCodes $object)
	 * @method bool has(\Bitrix\Security\Mfa\EO_RecoveryCodes $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes getByPrimary($primary)
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes[] getAll()
	 * @method bool remove(\Bitrix\Security\Mfa\EO_RecoveryCodes $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Security\Mfa\EO_RecoveryCodes_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RecoveryCodes_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Security\Mfa\RecoveryCodesTable */
		static public $dataClass = '\Bitrix\Security\Mfa\RecoveryCodesTable';
	}
}
namespace Bitrix\Security\Mfa {
	/**
	 * @method static EO_RecoveryCodes_Query query()
	 * @method static EO_RecoveryCodes_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_RecoveryCodes_Result getById($id)
	 * @method static EO_RecoveryCodes_Result getList(array $parameters = array())
	 * @method static EO_RecoveryCodes_Entity getEntity()
	 * @method static \Bitrix\Security\Mfa\EO_RecoveryCodes createObject($setDefaultValues = true)
	 * @method static \Bitrix\Security\Mfa\EO_RecoveryCodes_Collection createCollection()
	 * @method static \Bitrix\Security\Mfa\EO_RecoveryCodes wakeUpObject($row)
	 * @method static \Bitrix\Security\Mfa\EO_RecoveryCodes_Collection wakeUpCollection($rows)
	 */
	class RecoveryCodesTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RecoveryCodes_Result exec()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes fetchObject()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RecoveryCodes_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes fetchObject()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes_Collection fetchCollection()
	 */
	class EO_RecoveryCodes_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes createObject($setDefaultValues = true)
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes_Collection createCollection()
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes wakeUpObject($row)
	 * @method \Bitrix\Security\Mfa\EO_RecoveryCodes_Collection wakeUpCollection($rows)
	 */
	class EO_RecoveryCodes_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Security\Mfa\UserTable:security/lib/mfa/user.php:eb52488e371d1a24bf3abae3fa0495c7 */
namespace Bitrix\Security\Mfa {
	/**
	 * EO_User
	 * @see \Bitrix\Security\Mfa\UserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getUserId()
	 * @method \Bitrix\Security\Mfa\EO_User setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Security\Mfa\EO_User setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Security\Mfa\EO_User resetUser()
	 * @method \Bitrix\Security\Mfa\EO_User unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \boolean getActive()
	 * @method \Bitrix\Security\Mfa\EO_User setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Security\Mfa\EO_User resetActive()
	 * @method \Bitrix\Security\Mfa\EO_User unsetActive()
	 * @method \boolean fillActive()
	 * @method \string getSecret()
	 * @method \Bitrix\Security\Mfa\EO_User setSecret(\string|\Bitrix\Main\DB\SqlExpression $secret)
	 * @method bool hasSecret()
	 * @method bool isSecretFilled()
	 * @method bool isSecretChanged()
	 * @method \string remindActualSecret()
	 * @method \string requireSecret()
	 * @method \Bitrix\Security\Mfa\EO_User resetSecret()
	 * @method \Bitrix\Security\Mfa\EO_User unsetSecret()
	 * @method \string fillSecret()
	 * @method \string getParams()
	 * @method \Bitrix\Security\Mfa\EO_User setParams(\string|\Bitrix\Main\DB\SqlExpression $params)
	 * @method bool hasParams()
	 * @method bool isParamsFilled()
	 * @method bool isParamsChanged()
	 * @method \string remindActualParams()
	 * @method \string requireParams()
	 * @method \Bitrix\Security\Mfa\EO_User resetParams()
	 * @method \Bitrix\Security\Mfa\EO_User unsetParams()
	 * @method \string fillParams()
	 * @method \string getType()
	 * @method \Bitrix\Security\Mfa\EO_User setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Security\Mfa\EO_User resetType()
	 * @method \Bitrix\Security\Mfa\EO_User unsetType()
	 * @method \string fillType()
	 * @method \int getAttempts()
	 * @method \Bitrix\Security\Mfa\EO_User setAttempts(\int|\Bitrix\Main\DB\SqlExpression $attempts)
	 * @method bool hasAttempts()
	 * @method bool isAttemptsFilled()
	 * @method bool isAttemptsChanged()
	 * @method \int remindActualAttempts()
	 * @method \int requireAttempts()
	 * @method \Bitrix\Security\Mfa\EO_User resetAttempts()
	 * @method \Bitrix\Security\Mfa\EO_User unsetAttempts()
	 * @method \int fillAttempts()
	 * @method \Bitrix\Main\Type\DateTime getInitialDate()
	 * @method \Bitrix\Security\Mfa\EO_User setInitialDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $initialDate)
	 * @method bool hasInitialDate()
	 * @method bool isInitialDateFilled()
	 * @method bool isInitialDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualInitialDate()
	 * @method \Bitrix\Main\Type\DateTime requireInitialDate()
	 * @method \Bitrix\Security\Mfa\EO_User resetInitialDate()
	 * @method \Bitrix\Security\Mfa\EO_User unsetInitialDate()
	 * @method \Bitrix\Main\Type\DateTime fillInitialDate()
	 * @method \boolean getSkipMandatory()
	 * @method \Bitrix\Security\Mfa\EO_User setSkipMandatory(\boolean|\Bitrix\Main\DB\SqlExpression $skipMandatory)
	 * @method bool hasSkipMandatory()
	 * @method bool isSkipMandatoryFilled()
	 * @method bool isSkipMandatoryChanged()
	 * @method \boolean remindActualSkipMandatory()
	 * @method \boolean requireSkipMandatory()
	 * @method \Bitrix\Security\Mfa\EO_User resetSkipMandatory()
	 * @method \Bitrix\Security\Mfa\EO_User unsetSkipMandatory()
	 * @method \boolean fillSkipMandatory()
	 * @method \Bitrix\Main\Type\DateTime getDeactivateUntil()
	 * @method \Bitrix\Security\Mfa\EO_User setDeactivateUntil(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $deactivateUntil)
	 * @method bool hasDeactivateUntil()
	 * @method bool isDeactivateUntilFilled()
	 * @method bool isDeactivateUntilChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDeactivateUntil()
	 * @method \Bitrix\Main\Type\DateTime requireDeactivateUntil()
	 * @method \Bitrix\Security\Mfa\EO_User resetDeactivateUntil()
	 * @method \Bitrix\Security\Mfa\EO_User unsetDeactivateUntil()
	 * @method \Bitrix\Main\Type\DateTime fillDeactivateUntil()
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
	 * @method \Bitrix\Security\Mfa\EO_User set($fieldName, $value)
	 * @method \Bitrix\Security\Mfa\EO_User reset($fieldName)
	 * @method \Bitrix\Security\Mfa\EO_User unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Security\Mfa\EO_User wakeUp($data)
	 */
	class EO_User {
		/* @var \Bitrix\Security\Mfa\UserTable */
		static public $dataClass = '\Bitrix\Security\Mfa\UserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Security\Mfa {
	/**
	 * EO_User_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getUserIdList()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Security\Mfa\EO_User_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \string[] getSecretList()
	 * @method \string[] fillSecret()
	 * @method \string[] getParamsList()
	 * @method \string[] fillParams()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getAttemptsList()
	 * @method \int[] fillAttempts()
	 * @method \Bitrix\Main\Type\DateTime[] getInitialDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillInitialDate()
	 * @method \boolean[] getSkipMandatoryList()
	 * @method \boolean[] fillSkipMandatory()
	 * @method \Bitrix\Main\Type\DateTime[] getDeactivateUntilList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDeactivateUntil()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Security\Mfa\EO_User $object)
	 * @method bool has(\Bitrix\Security\Mfa\EO_User $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Security\Mfa\EO_User getByPrimary($primary)
	 * @method \Bitrix\Security\Mfa\EO_User[] getAll()
	 * @method bool remove(\Bitrix\Security\Mfa\EO_User $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Security\Mfa\EO_User_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Security\Mfa\EO_User current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_User_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Security\Mfa\UserTable */
		static public $dataClass = '\Bitrix\Security\Mfa\UserTable';
	}
}
namespace Bitrix\Security\Mfa {
	/**
	 * @method static EO_User_Query query()
	 * @method static EO_User_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_User_Result getById($id)
	 * @method static EO_User_Result getList(array $parameters = array())
	 * @method static EO_User_Entity getEntity()
	 * @method static \Bitrix\Security\Mfa\EO_User createObject($setDefaultValues = true)
	 * @method static \Bitrix\Security\Mfa\EO_User_Collection createCollection()
	 * @method static \Bitrix\Security\Mfa\EO_User wakeUpObject($row)
	 * @method static \Bitrix\Security\Mfa\EO_User_Collection wakeUpCollection($rows)
	 */
	class UserTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_User_Result exec()
	 * @method \Bitrix\Security\Mfa\EO_User fetchObject()
	 * @method \Bitrix\Security\Mfa\EO_User_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_User_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Security\Mfa\EO_User fetchObject()
	 * @method \Bitrix\Security\Mfa\EO_User_Collection fetchCollection()
	 */
	class EO_User_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Security\Mfa\EO_User createObject($setDefaultValues = true)
	 * @method \Bitrix\Security\Mfa\EO_User_Collection createCollection()
	 * @method \Bitrix\Security\Mfa\EO_User wakeUpObject($row)
	 * @method \Bitrix\Security\Mfa\EO_User_Collection wakeUpCollection($rows)
	 */
	class EO_User_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Security\SessionTable:security/lib/session.php:57c9ecedf0655849d0280cc1d4410ce8 */
namespace Bitrix\Security {
	/**
	 * EO_Session
	 * @see \Bitrix\Security\SessionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getSessionId()
	 * @method \Bitrix\Security\EO_Session setSessionId(\string|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Security\EO_Session setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Security\EO_Session resetTimestampX()
	 * @method \Bitrix\Security\EO_Session unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \string getSessionData()
	 * @method \Bitrix\Security\EO_Session setSessionData(\string|\Bitrix\Main\DB\SqlExpression $sessionData)
	 * @method bool hasSessionData()
	 * @method bool isSessionDataFilled()
	 * @method bool isSessionDataChanged()
	 * @method \string remindActualSessionData()
	 * @method \string requireSessionData()
	 * @method \Bitrix\Security\EO_Session resetSessionData()
	 * @method \Bitrix\Security\EO_Session unsetSessionData()
	 * @method \string fillSessionData()
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
	 * @method \Bitrix\Security\EO_Session set($fieldName, $value)
	 * @method \Bitrix\Security\EO_Session reset($fieldName)
	 * @method \Bitrix\Security\EO_Session unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Security\EO_Session wakeUp($data)
	 */
	class EO_Session {
		/* @var \Bitrix\Security\SessionTable */
		static public $dataClass = '\Bitrix\Security\SessionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Security {
	/**
	 * EO_Session_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getSessionIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \string[] getSessionDataList()
	 * @method \string[] fillSessionData()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Security\EO_Session $object)
	 * @method bool has(\Bitrix\Security\EO_Session $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Security\EO_Session getByPrimary($primary)
	 * @method \Bitrix\Security\EO_Session[] getAll()
	 * @method bool remove(\Bitrix\Security\EO_Session $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Security\EO_Session_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Security\EO_Session current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Session_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Security\SessionTable */
		static public $dataClass = '\Bitrix\Security\SessionTable';
	}
}
namespace Bitrix\Security {
	/**
	 * @method static EO_Session_Query query()
	 * @method static EO_Session_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Session_Result getById($id)
	 * @method static EO_Session_Result getList(array $parameters = array())
	 * @method static EO_Session_Entity getEntity()
	 * @method static \Bitrix\Security\EO_Session createObject($setDefaultValues = true)
	 * @method static \Bitrix\Security\EO_Session_Collection createCollection()
	 * @method static \Bitrix\Security\EO_Session wakeUpObject($row)
	 * @method static \Bitrix\Security\EO_Session_Collection wakeUpCollection($rows)
	 */
	class SessionTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Session_Result exec()
	 * @method \Bitrix\Security\EO_Session fetchObject()
	 * @method \Bitrix\Security\EO_Session_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Session_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Security\EO_Session fetchObject()
	 * @method \Bitrix\Security\EO_Session_Collection fetchCollection()
	 */
	class EO_Session_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Security\EO_Session createObject($setDefaultValues = true)
	 * @method \Bitrix\Security\EO_Session_Collection createCollection()
	 * @method \Bitrix\Security\EO_Session wakeUpObject($row)
	 * @method \Bitrix\Security\EO_Session_Collection wakeUpCollection($rows)
	 */
	class EO_Session_Entity extends \Bitrix\Main\ORM\Entity {}
}