<?php

/* ORMENTITYANNOTATION:Bitrix\Dav\Internals\DavConnectionTable:dav/lib/internals/davconnectiontable.php */
namespace Bitrix\Dav\Internals {
	/**
	 * EO_DavConnection
	 * @see \Bitrix\Dav\Internals\DavConnectionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getEntityType()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetEntityType()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \int getEntityId()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetEntityId()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getAccountType()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setAccountType(\string|\Bitrix\Main\DB\SqlExpression $accountType)
	 * @method bool hasAccountType()
	 * @method bool isAccountTypeFilled()
	 * @method bool isAccountTypeChanged()
	 * @method \string remindActualAccountType()
	 * @method \string requireAccountType()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetAccountType()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetAccountType()
	 * @method \string fillAccountType()
	 * @method \string getSyncToken()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setSyncToken(\string|\Bitrix\Main\DB\SqlExpression $syncToken)
	 * @method bool hasSyncToken()
	 * @method bool isSyncTokenFilled()
	 * @method bool isSyncTokenChanged()
	 * @method \string remindActualSyncToken()
	 * @method \string requireSyncToken()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetSyncToken()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetSyncToken()
	 * @method \string fillSyncToken()
	 * @method \string getName()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetName()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetName()
	 * @method \string fillName()
	 * @method \string getServerScheme()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setServerScheme(\string|\Bitrix\Main\DB\SqlExpression $serverScheme)
	 * @method bool hasServerScheme()
	 * @method bool isServerSchemeFilled()
	 * @method bool isServerSchemeChanged()
	 * @method \string remindActualServerScheme()
	 * @method \string requireServerScheme()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetServerScheme()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetServerScheme()
	 * @method \string fillServerScheme()
	 * @method \string getServerHost()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setServerHost(\string|\Bitrix\Main\DB\SqlExpression $serverHost)
	 * @method bool hasServerHost()
	 * @method bool isServerHostFilled()
	 * @method bool isServerHostChanged()
	 * @method \string remindActualServerHost()
	 * @method \string requireServerHost()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetServerHost()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetServerHost()
	 * @method \string fillServerHost()
	 * @method \int getServerPort()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setServerPort(\int|\Bitrix\Main\DB\SqlExpression $serverPort)
	 * @method bool hasServerPort()
	 * @method bool isServerPortFilled()
	 * @method bool isServerPortChanged()
	 * @method \int remindActualServerPort()
	 * @method \int requireServerPort()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetServerPort()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetServerPort()
	 * @method \int fillServerPort()
	 * @method \string getServerUsername()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setServerUsername(\string|\Bitrix\Main\DB\SqlExpression $serverUsername)
	 * @method bool hasServerUsername()
	 * @method bool isServerUsernameFilled()
	 * @method bool isServerUsernameChanged()
	 * @method \string remindActualServerUsername()
	 * @method \string requireServerUsername()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetServerUsername()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetServerUsername()
	 * @method \string fillServerUsername()
	 * @method \string getServerPassword()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setServerPassword(\string|\Bitrix\Main\DB\SqlExpression $serverPassword)
	 * @method bool hasServerPassword()
	 * @method bool isServerPasswordFilled()
	 * @method bool isServerPasswordChanged()
	 * @method \string remindActualServerPassword()
	 * @method \string requireServerPassword()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetServerPassword()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetServerPassword()
	 * @method \string fillServerPassword()
	 * @method \string getServerPath()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setServerPath(\string|\Bitrix\Main\DB\SqlExpression $serverPath)
	 * @method bool hasServerPath()
	 * @method bool isServerPathFilled()
	 * @method bool isServerPathChanged()
	 * @method \string remindActualServerPath()
	 * @method \string requireServerPath()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetServerPath()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetServerPath()
	 * @method \string fillServerPath()
	 * @method \string getLastResult()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setLastResult(\string|\Bitrix\Main\DB\SqlExpression $lastResult)
	 * @method bool hasLastResult()
	 * @method bool isLastResultFilled()
	 * @method bool isLastResultChanged()
	 * @method \string remindActualLastResult()
	 * @method \string requireLastResult()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetLastResult()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetLastResult()
	 * @method \string fillLastResult()
	 * @method \Bitrix\Main\Type\DateTime getCreated()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setCreated(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $created)
	 * @method bool hasCreated()
	 * @method bool isCreatedFilled()
	 * @method bool isCreatedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreated()
	 * @method \Bitrix\Main\Type\DateTime requireCreated()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetCreated()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetCreated()
	 * @method \Bitrix\Main\Type\DateTime fillCreated()
	 * @method \Bitrix\Main\Type\DateTime getModified()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setModified(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $modified)
	 * @method bool hasModified()
	 * @method bool isModifiedFilled()
	 * @method bool isModifiedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualModified()
	 * @method \Bitrix\Main\Type\DateTime requireModified()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetModified()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetModified()
	 * @method \Bitrix\Main\Type\DateTime fillModified()
	 * @method \Bitrix\Main\Type\DateTime getSynchronized()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection setSynchronized(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $synchronized)
	 * @method bool hasSynchronized()
	 * @method bool isSynchronizedFilled()
	 * @method bool isSynchronizedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualSynchronized()
	 * @method \Bitrix\Main\Type\DateTime requireSynchronized()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection resetSynchronized()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unsetSynchronized()
	 * @method \Bitrix\Main\Type\DateTime fillSynchronized()
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
	 * @method \Bitrix\Dav\Internals\EO_DavConnection set($fieldName, $value)
	 * @method \Bitrix\Dav\Internals\EO_DavConnection reset($fieldName)
	 * @method \Bitrix\Dav\Internals\EO_DavConnection unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Dav\Internals\EO_DavConnection wakeUp($data)
	 */
	class EO_DavConnection {
		/* @var \Bitrix\Dav\Internals\DavConnectionTable */
		static public $dataClass = '\Bitrix\Dav\Internals\DavConnectionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Dav\Internals {
	/**
	 * EO_DavConnection_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getAccountTypeList()
	 * @method \string[] fillAccountType()
	 * @method \string[] getSyncTokenList()
	 * @method \string[] fillSyncToken()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getServerSchemeList()
	 * @method \string[] fillServerScheme()
	 * @method \string[] getServerHostList()
	 * @method \string[] fillServerHost()
	 * @method \int[] getServerPortList()
	 * @method \int[] fillServerPort()
	 * @method \string[] getServerUsernameList()
	 * @method \string[] fillServerUsername()
	 * @method \string[] getServerPasswordList()
	 * @method \string[] fillServerPassword()
	 * @method \string[] getServerPathList()
	 * @method \string[] fillServerPath()
	 * @method \string[] getLastResultList()
	 * @method \string[] fillLastResult()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreated()
	 * @method \Bitrix\Main\Type\DateTime[] getModifiedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillModified()
	 * @method \Bitrix\Main\Type\DateTime[] getSynchronizedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillSynchronized()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Dav\Internals\EO_DavConnection $object)
	 * @method bool has(\Bitrix\Dav\Internals\EO_DavConnection $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Dav\Internals\EO_DavConnection getByPrimary($primary)
	 * @method \Bitrix\Dav\Internals\EO_DavConnection[] getAll()
	 * @method bool remove(\Bitrix\Dav\Internals\EO_DavConnection $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Dav\Internals\EO_DavConnection_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Dav\Internals\EO_DavConnection current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_DavConnection_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Dav\Internals\DavConnectionTable */
		static public $dataClass = '\Bitrix\Dav\Internals\DavConnectionTable';
	}
}
namespace Bitrix\Dav\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DavConnection_Result exec()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection fetchObject()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_DavConnection_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Dav\Internals\EO_DavConnection fetchObject()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection_Collection fetchCollection()
	 */
	class EO_DavConnection_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Dav\Internals\EO_DavConnection createObject($setDefaultValues = true)
	 * @method \Bitrix\Dav\Internals\EO_DavConnection_Collection createCollection()
	 * @method \Bitrix\Dav\Internals\EO_DavConnection wakeUpObject($row)
	 * @method \Bitrix\Dav\Internals\EO_DavConnection_Collection wakeUpCollection($rows)
	 */
	class EO_DavConnection_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Dav\TokensTable:dav/lib/tokens.php */
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