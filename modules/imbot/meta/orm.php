<?php

/* ORMENTITYANNOTATION:Bitrix\ImBot\Model\NetworkSessionTable:imbot/lib/model/networksession.php:b3913d472b31cba0e05d03f6a930d54d */
namespace Bitrix\ImBot\Model {
	/**
	 * EO_NetworkSession
	 * @see \Bitrix\ImBot\Model\NetworkSessionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBotId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession setBotId(\int|\Bitrix\Main\DB\SqlExpression $botId)
	 * @method bool hasBotId()
	 * @method bool isBotIdFilled()
	 * @method bool isBotIdChanged()
	 * @method \int remindActualBotId()
	 * @method \int requireBotId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession resetBotId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession unsetBotId()
	 * @method \int fillBotId()
	 * @method \string getDialogId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession setDialogId(\string|\Bitrix\Main\DB\SqlExpression $dialogId)
	 * @method bool hasDialogId()
	 * @method bool isDialogIdFilled()
	 * @method bool isDialogIdChanged()
	 * @method \string remindActualDialogId()
	 * @method \string requireDialogId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession resetDialogId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession unsetDialogId()
	 * @method \string fillDialogId()
	 * @method \int getSessionId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession resetSessionId()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \boolean getGreetingShown()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession setGreetingShown(\boolean|\Bitrix\Main\DB\SqlExpression $greetingShown)
	 * @method bool hasGreetingShown()
	 * @method bool isGreetingShownFilled()
	 * @method bool isGreetingShownChanged()
	 * @method \boolean remindActualGreetingShown()
	 * @method \boolean requireGreetingShown()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession resetGreetingShown()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession unsetGreetingShown()
	 * @method \boolean fillGreetingShown()
	 * @method \string getMenuState()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession setMenuState(\string|\Bitrix\Main\DB\SqlExpression $menuState)
	 * @method bool hasMenuState()
	 * @method bool isMenuStateFilled()
	 * @method bool isMenuStateChanged()
	 * @method \string remindActualMenuState()
	 * @method \string requireMenuState()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession resetMenuState()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession unsetMenuState()
	 * @method \string fillMenuState()
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
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession set($fieldName, $value)
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession reset($fieldName)
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImBot\Model\EO_NetworkSession wakeUp($data)
	 */
	class EO_NetworkSession {
		/* @var \Bitrix\ImBot\Model\NetworkSessionTable */
		static public $dataClass = '\Bitrix\ImBot\Model\NetworkSessionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImBot\Model {
	/**
	 * EO_NetworkSession_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBotIdList()
	 * @method \int[] fillBotId()
	 * @method \string[] getDialogIdList()
	 * @method \string[] fillDialogId()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \boolean[] getGreetingShownList()
	 * @method \boolean[] fillGreetingShown()
	 * @method \string[] getMenuStateList()
	 * @method \string[] fillMenuState()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImBot\Model\EO_NetworkSession $object)
	 * @method bool has(\Bitrix\ImBot\Model\EO_NetworkSession $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession getByPrimary($primary)
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession[] getAll()
	 * @method bool remove(\Bitrix\ImBot\Model\EO_NetworkSession $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImBot\Model\EO_NetworkSession_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_NetworkSession_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImBot\Model\NetworkSessionTable */
		static public $dataClass = '\Bitrix\ImBot\Model\NetworkSessionTable';
	}
}
namespace Bitrix\ImBot\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_NetworkSession_Result exec()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession fetchObject()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_NetworkSession_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession fetchObject()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession_Collection fetchCollection()
	 */
	class EO_NetworkSession_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession createObject($setDefaultValues = true)
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession_Collection createCollection()
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession wakeUpObject($row)
	 * @method \Bitrix\ImBot\Model\EO_NetworkSession_Collection wakeUpCollection($rows)
	 */
	class EO_NetworkSession_Entity extends \Bitrix\Main\ORM\Entity {}
}