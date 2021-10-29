<?php
/* ORMENTITYANNOTATION:Bitrix\ImConnector\Model\ChatLastMessageTable:imconnector/lib/model/chatlastmessage.php:c6e453abd89042365ff50adba656965c */
namespace Bitrix\ImConnector\Model {
	/**
	 * EO_ChatLastMessage
	 * @see \Bitrix\ImConnector\Model\ChatLastMessageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getExternalChatId()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage setExternalChatId(\string|\Bitrix\Main\DB\SqlExpression $externalChatId)
	 * @method bool hasExternalChatId()
	 * @method bool isExternalChatIdFilled()
	 * @method bool isExternalChatIdChanged()
	 * @method \string remindActualExternalChatId()
	 * @method \string requireExternalChatId()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage resetExternalChatId()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage unsetExternalChatId()
	 * @method \string fillExternalChatId()
	 * @method \string getConnector()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage setConnector(\string|\Bitrix\Main\DB\SqlExpression $connector)
	 * @method bool hasConnector()
	 * @method bool isConnectorFilled()
	 * @method bool isConnectorChanged()
	 * @method \string remindActualConnector()
	 * @method \string requireConnector()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage resetConnector()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage unsetConnector()
	 * @method \string fillConnector()
	 * @method \string getExternalMessageId()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage setExternalMessageId(\string|\Bitrix\Main\DB\SqlExpression $externalMessageId)
	 * @method bool hasExternalMessageId()
	 * @method bool isExternalMessageIdFilled()
	 * @method bool isExternalMessageIdChanged()
	 * @method \string remindActualExternalMessageId()
	 * @method \string requireExternalMessageId()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage resetExternalMessageId()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage unsetExternalMessageId()
	 * @method \string fillExternalMessageId()
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
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage set($fieldName, $value)
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage reset($fieldName)
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage wakeUp($data)
	 */
	class EO_ChatLastMessage {
		/* @var \Bitrix\ImConnector\Model\ChatLastMessageTable */
		static public $dataClass = '\Bitrix\ImConnector\Model\ChatLastMessageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImConnector\Model {
	/**
	 * EO_ChatLastMessage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getExternalChatIdList()
	 * @method \string[] fillExternalChatId()
	 * @method \string[] getConnectorList()
	 * @method \string[] fillConnector()
	 * @method \string[] getExternalMessageIdList()
	 * @method \string[] fillExternalMessageId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImConnector\Model\EO_ChatLastMessage $object)
	 * @method bool has(\Bitrix\ImConnector\Model\EO_ChatLastMessage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage getByPrimary($primary)
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage[] getAll()
	 * @method bool remove(\Bitrix\ImConnector\Model\EO_ChatLastMessage $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ChatLastMessage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImConnector\Model\ChatLastMessageTable */
		static public $dataClass = '\Bitrix\ImConnector\Model\ChatLastMessageTable';
	}
}
namespace Bitrix\ImConnector\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ChatLastMessage_Result exec()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage fetchObject()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ChatLastMessage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage fetchObject()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage_Collection fetchCollection()
	 */
	class EO_ChatLastMessage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage createObject($setDefaultValues = true)
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage_Collection createCollection()
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage wakeUpObject($row)
	 * @method \Bitrix\ImConnector\Model\EO_ChatLastMessage_Collection wakeUpCollection($rows)
	 */
	class EO_ChatLastMessage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImConnector\Model\CustomConnectorsTable:imconnector/lib/model/customconnectors.php:c34669c8b6820fa4a946a6195bf8ddd0 */
namespace Bitrix\ImConnector\Model {
	/**
	 * EO_CustomConnectors
	 * @see \Bitrix\ImConnector\Model\CustomConnectorsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getIdConnector()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setIdConnector(\string|\Bitrix\Main\DB\SqlExpression $idConnector)
	 * @method bool hasIdConnector()
	 * @method bool isIdConnectorFilled()
	 * @method bool isIdConnectorChanged()
	 * @method \string remindActualIdConnector()
	 * @method \string requireIdConnector()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetIdConnector()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetIdConnector()
	 * @method \string fillIdConnector()
	 * @method \string getName()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetName()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetName()
	 * @method \string fillName()
	 * @method \string getIcon()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setIcon(\string|\Bitrix\Main\DB\SqlExpression $icon)
	 * @method bool hasIcon()
	 * @method bool isIconFilled()
	 * @method bool isIconChanged()
	 * @method \string remindActualIcon()
	 * @method \string requireIcon()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetIcon()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetIcon()
	 * @method \string fillIcon()
	 * @method \string getIconDisabled()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setIconDisabled(\string|\Bitrix\Main\DB\SqlExpression $iconDisabled)
	 * @method bool hasIconDisabled()
	 * @method bool isIconDisabledFilled()
	 * @method bool isIconDisabledChanged()
	 * @method \string remindActualIconDisabled()
	 * @method \string requireIconDisabled()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetIconDisabled()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetIconDisabled()
	 * @method \string fillIconDisabled()
	 * @method \string getComponent()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setComponent(\string|\Bitrix\Main\DB\SqlExpression $component)
	 * @method bool hasComponent()
	 * @method bool isComponentFilled()
	 * @method bool isComponentChanged()
	 * @method \string remindActualComponent()
	 * @method \string requireComponent()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetComponent()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetComponent()
	 * @method \string fillComponent()
	 * @method \boolean getDelExternalMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setDelExternalMessages(\boolean|\Bitrix\Main\DB\SqlExpression $delExternalMessages)
	 * @method bool hasDelExternalMessages()
	 * @method bool isDelExternalMessagesFilled()
	 * @method bool isDelExternalMessagesChanged()
	 * @method \boolean remindActualDelExternalMessages()
	 * @method \boolean requireDelExternalMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetDelExternalMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetDelExternalMessages()
	 * @method \boolean fillDelExternalMessages()
	 * @method \boolean getEditInternalMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setEditInternalMessages(\boolean|\Bitrix\Main\DB\SqlExpression $editInternalMessages)
	 * @method bool hasEditInternalMessages()
	 * @method bool isEditInternalMessagesFilled()
	 * @method bool isEditInternalMessagesChanged()
	 * @method \boolean remindActualEditInternalMessages()
	 * @method \boolean requireEditInternalMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetEditInternalMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetEditInternalMessages()
	 * @method \boolean fillEditInternalMessages()
	 * @method \boolean getDelInternalMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setDelInternalMessages(\boolean|\Bitrix\Main\DB\SqlExpression $delInternalMessages)
	 * @method bool hasDelInternalMessages()
	 * @method bool isDelInternalMessagesFilled()
	 * @method bool isDelInternalMessagesChanged()
	 * @method \boolean remindActualDelInternalMessages()
	 * @method \boolean requireDelInternalMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetDelInternalMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetDelInternalMessages()
	 * @method \boolean fillDelInternalMessages()
	 * @method \boolean getNewsletter()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setNewsletter(\boolean|\Bitrix\Main\DB\SqlExpression $newsletter)
	 * @method bool hasNewsletter()
	 * @method bool isNewsletterFilled()
	 * @method bool isNewsletterChanged()
	 * @method \boolean remindActualNewsletter()
	 * @method \boolean requireNewsletter()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetNewsletter()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetNewsletter()
	 * @method \boolean fillNewsletter()
	 * @method \boolean getNeedSystemMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setNeedSystemMessages(\boolean|\Bitrix\Main\DB\SqlExpression $needSystemMessages)
	 * @method bool hasNeedSystemMessages()
	 * @method bool isNeedSystemMessagesFilled()
	 * @method bool isNeedSystemMessagesChanged()
	 * @method \boolean remindActualNeedSystemMessages()
	 * @method \boolean requireNeedSystemMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetNeedSystemMessages()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetNeedSystemMessages()
	 * @method \boolean fillNeedSystemMessages()
	 * @method \boolean getNeedSignature()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setNeedSignature(\boolean|\Bitrix\Main\DB\SqlExpression $needSignature)
	 * @method bool hasNeedSignature()
	 * @method bool isNeedSignatureFilled()
	 * @method bool isNeedSignatureChanged()
	 * @method \boolean remindActualNeedSignature()
	 * @method \boolean requireNeedSignature()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetNeedSignature()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetNeedSignature()
	 * @method \boolean fillNeedSignature()
	 * @method \boolean getChatGroup()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setChatGroup(\boolean|\Bitrix\Main\DB\SqlExpression $chatGroup)
	 * @method bool hasChatGroup()
	 * @method bool isChatGroupFilled()
	 * @method bool isChatGroupChanged()
	 * @method \boolean remindActualChatGroup()
	 * @method \boolean requireChatGroup()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetChatGroup()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetChatGroup()
	 * @method \boolean fillChatGroup()
	 * @method \int getRestAppId()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setRestAppId(\int|\Bitrix\Main\DB\SqlExpression $restAppId)
	 * @method bool hasRestAppId()
	 * @method bool isRestAppIdFilled()
	 * @method bool isRestAppIdChanged()
	 * @method \int remindActualRestAppId()
	 * @method \int requireRestAppId()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetRestAppId()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetRestAppId()
	 * @method \int fillRestAppId()
	 * @method \int getRestPlacementId()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors setRestPlacementId(\int|\Bitrix\Main\DB\SqlExpression $restPlacementId)
	 * @method bool hasRestPlacementId()
	 * @method bool isRestPlacementIdFilled()
	 * @method bool isRestPlacementIdChanged()
	 * @method \int remindActualRestPlacementId()
	 * @method \int requireRestPlacementId()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors resetRestPlacementId()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unsetRestPlacementId()
	 * @method \int fillRestPlacementId()
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
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors set($fieldName, $value)
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors reset($fieldName)
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImConnector\Model\EO_CustomConnectors wakeUp($data)
	 */
	class EO_CustomConnectors {
		/* @var \Bitrix\ImConnector\Model\CustomConnectorsTable */
		static public $dataClass = '\Bitrix\ImConnector\Model\CustomConnectorsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImConnector\Model {
	/**
	 * EO_CustomConnectors_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getIdConnectorList()
	 * @method \string[] fillIdConnector()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getIconList()
	 * @method \string[] fillIcon()
	 * @method \string[] getIconDisabledList()
	 * @method \string[] fillIconDisabled()
	 * @method \string[] getComponentList()
	 * @method \string[] fillComponent()
	 * @method \boolean[] getDelExternalMessagesList()
	 * @method \boolean[] fillDelExternalMessages()
	 * @method \boolean[] getEditInternalMessagesList()
	 * @method \boolean[] fillEditInternalMessages()
	 * @method \boolean[] getDelInternalMessagesList()
	 * @method \boolean[] fillDelInternalMessages()
	 * @method \boolean[] getNewsletterList()
	 * @method \boolean[] fillNewsletter()
	 * @method \boolean[] getNeedSystemMessagesList()
	 * @method \boolean[] fillNeedSystemMessages()
	 * @method \boolean[] getNeedSignatureList()
	 * @method \boolean[] fillNeedSignature()
	 * @method \boolean[] getChatGroupList()
	 * @method \boolean[] fillChatGroup()
	 * @method \int[] getRestAppIdList()
	 * @method \int[] fillRestAppId()
	 * @method \int[] getRestPlacementIdList()
	 * @method \int[] fillRestPlacementId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImConnector\Model\EO_CustomConnectors $object)
	 * @method bool has(\Bitrix\ImConnector\Model\EO_CustomConnectors $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors getByPrimary($primary)
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors[] getAll()
	 * @method bool remove(\Bitrix\ImConnector\Model\EO_CustomConnectors $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImConnector\Model\EO_CustomConnectors_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_CustomConnectors_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImConnector\Model\CustomConnectorsTable */
		static public $dataClass = '\Bitrix\ImConnector\Model\CustomConnectorsTable';
	}
}
namespace Bitrix\ImConnector\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CustomConnectors_Result exec()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors fetchObject()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CustomConnectors_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors fetchObject()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors_Collection fetchCollection()
	 */
	class EO_CustomConnectors_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors createObject($setDefaultValues = true)
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors_Collection createCollection()
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors wakeUpObject($row)
	 * @method \Bitrix\ImConnector\Model\EO_CustomConnectors_Collection wakeUpCollection($rows)
	 */
	class EO_CustomConnectors_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImConnector\Model\InfoConnectorsTable:imconnector/lib/model/infoconnectors.php:21b409d63d005b612a6703227ef47f5b */
namespace Bitrix\ImConnector\Model {
	/**
	 * EO_InfoConnectors
	 * @see \Bitrix\ImConnector\Model\InfoConnectorsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getLineId()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors setLineId(\int|\Bitrix\Main\DB\SqlExpression $lineId)
	 * @method bool hasLineId()
	 * @method bool isLineIdFilled()
	 * @method bool isLineIdChanged()
	 * @method \string getData()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors setData(\string|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method \string remindActualData()
	 * @method \string requireData()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors resetData()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors unsetData()
	 * @method \string fillData()
	 * @method \Bitrix\Main\Type\DateTime getExpires()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors setExpires(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $expires)
	 * @method bool hasExpires()
	 * @method bool isExpiresFilled()
	 * @method bool isExpiresChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualExpires()
	 * @method \Bitrix\Main\Type\DateTime requireExpires()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors resetExpires()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors unsetExpires()
	 * @method \Bitrix\Main\Type\DateTime fillExpires()
	 * @method \string getDataHash()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors setDataHash(\string|\Bitrix\Main\DB\SqlExpression $dataHash)
	 * @method bool hasDataHash()
	 * @method bool isDataHashFilled()
	 * @method bool isDataHashChanged()
	 * @method \string remindActualDataHash()
	 * @method \string requireDataHash()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors resetDataHash()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors unsetDataHash()
	 * @method \string fillDataHash()
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
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors set($fieldName, $value)
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors reset($fieldName)
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImConnector\Model\EO_InfoConnectors wakeUp($data)
	 */
	class EO_InfoConnectors {
		/* @var \Bitrix\ImConnector\Model\InfoConnectorsTable */
		static public $dataClass = '\Bitrix\ImConnector\Model\InfoConnectorsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImConnector\Model {
	/**
	 * EO_InfoConnectors_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getLineIdList()
	 * @method \string[] getDataList()
	 * @method \string[] fillData()
	 * @method \Bitrix\Main\Type\DateTime[] getExpiresList()
	 * @method \Bitrix\Main\Type\DateTime[] fillExpires()
	 * @method \string[] getDataHashList()
	 * @method \string[] fillDataHash()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImConnector\Model\EO_InfoConnectors $object)
	 * @method bool has(\Bitrix\ImConnector\Model\EO_InfoConnectors $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors getByPrimary($primary)
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors[] getAll()
	 * @method bool remove(\Bitrix\ImConnector\Model\EO_InfoConnectors $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImConnector\Model\EO_InfoConnectors_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_InfoConnectors_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImConnector\Model\InfoConnectorsTable */
		static public $dataClass = '\Bitrix\ImConnector\Model\InfoConnectorsTable';
	}
}
namespace Bitrix\ImConnector\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_InfoConnectors_Result exec()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors fetchObject()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_InfoConnectors_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors fetchObject()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors_Collection fetchCollection()
	 */
	class EO_InfoConnectors_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors createObject($setDefaultValues = true)
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors_Collection createCollection()
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors wakeUpObject($row)
	 * @method \Bitrix\ImConnector\Model\EO_InfoConnectors_Collection wakeUpCollection($rows)
	 */
	class EO_InfoConnectors_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImConnector\Model\StatusConnectorsTable:imconnector/lib/model/statusconnectors.php:332a3af610a7fa4b194b4cdf51c7568b */
namespace Bitrix\ImConnector\Model {
	/**
	 * EO_StatusConnectors
	 * @see \Bitrix\ImConnector\Model\StatusConnectorsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getConnector()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors setConnector(\string|\Bitrix\Main\DB\SqlExpression $connector)
	 * @method bool hasConnector()
	 * @method bool isConnectorFilled()
	 * @method bool isConnectorChanged()
	 * @method \string remindActualConnector()
	 * @method \string requireConnector()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors resetConnector()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors unsetConnector()
	 * @method \string fillConnector()
	 * @method \string getLine()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors setLine(\string|\Bitrix\Main\DB\SqlExpression $line)
	 * @method bool hasLine()
	 * @method bool isLineFilled()
	 * @method bool isLineChanged()
	 * @method \string remindActualLine()
	 * @method \string requireLine()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors resetLine()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors unsetLine()
	 * @method \string fillLine()
	 * @method \boolean getActive()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors resetActive()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors unsetActive()
	 * @method \boolean fillActive()
	 * @method \boolean getConnection()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors setConnection(\boolean|\Bitrix\Main\DB\SqlExpression $connection)
	 * @method bool hasConnection()
	 * @method bool isConnectionFilled()
	 * @method bool isConnectionChanged()
	 * @method \boolean remindActualConnection()
	 * @method \boolean requireConnection()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors resetConnection()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors unsetConnection()
	 * @method \boolean fillConnection()
	 * @method \boolean getRegister()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors setRegister(\boolean|\Bitrix\Main\DB\SqlExpression $register)
	 * @method bool hasRegister()
	 * @method bool isRegisterFilled()
	 * @method bool isRegisterChanged()
	 * @method \boolean remindActualRegister()
	 * @method \boolean requireRegister()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors resetRegister()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors unsetRegister()
	 * @method \boolean fillRegister()
	 * @method \boolean getError()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors setError(\boolean|\Bitrix\Main\DB\SqlExpression $error)
	 * @method bool hasError()
	 * @method bool isErrorFilled()
	 * @method bool isErrorChanged()
	 * @method \boolean remindActualError()
	 * @method \boolean requireError()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors resetError()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors unsetError()
	 * @method \boolean fillError()
	 * @method \string getData()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors setData(\string|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method \string remindActualData()
	 * @method \string requireData()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors resetData()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors unsetData()
	 * @method \string fillData()
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
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors set($fieldName, $value)
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors reset($fieldName)
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImConnector\Model\EO_StatusConnectors wakeUp($data)
	 */
	class EO_StatusConnectors {
		/* @var \Bitrix\ImConnector\Model\StatusConnectorsTable */
		static public $dataClass = '\Bitrix\ImConnector\Model\StatusConnectorsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImConnector\Model {
	/**
	 * EO_StatusConnectors_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getConnectorList()
	 * @method \string[] fillConnector()
	 * @method \string[] getLineList()
	 * @method \string[] fillLine()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \boolean[] getConnectionList()
	 * @method \boolean[] fillConnection()
	 * @method \boolean[] getRegisterList()
	 * @method \boolean[] fillRegister()
	 * @method \boolean[] getErrorList()
	 * @method \boolean[] fillError()
	 * @method \string[] getDataList()
	 * @method \string[] fillData()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImConnector\Model\EO_StatusConnectors $object)
	 * @method bool has(\Bitrix\ImConnector\Model\EO_StatusConnectors $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors getByPrimary($primary)
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors[] getAll()
	 * @method bool remove(\Bitrix\ImConnector\Model\EO_StatusConnectors $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImConnector\Model\EO_StatusConnectors_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_StatusConnectors_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImConnector\Model\StatusConnectorsTable */
		static public $dataClass = '\Bitrix\ImConnector\Model\StatusConnectorsTable';
	}
}
namespace Bitrix\ImConnector\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_StatusConnectors_Result exec()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors fetchObject()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_StatusConnectors_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors fetchObject()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors_Collection fetchCollection()
	 */
	class EO_StatusConnectors_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors createObject($setDefaultValues = true)
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors_Collection createCollection()
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors wakeUpObject($row)
	 * @method \Bitrix\ImConnector\Model\EO_StatusConnectors_Collection wakeUpCollection($rows)
	 */
	class EO_StatusConnectors_Entity extends \Bitrix\Main\ORM\Entity {}
}
