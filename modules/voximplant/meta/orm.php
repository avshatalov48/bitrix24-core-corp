<?php

/* ORMENTITYANNOTATION:Bitrix\Voximplant\BlacklistTable:voximplant\lib\blacklist.php:c1e6cbb42ddef3152c26defb3bbe424c */
namespace Bitrix\Voximplant {
	/**
	 * EO_Blacklist
	 * @see \Bitrix\Voximplant\BlacklistTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\EO_Blacklist setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getPhoneNumber()
	 * @method \Bitrix\Voximplant\EO_Blacklist setPhoneNumber(\string|\Bitrix\Main\DB\SqlExpression $phoneNumber)
	 * @method bool hasPhoneNumber()
	 * @method bool isPhoneNumberFilled()
	 * @method bool isPhoneNumberChanged()
	 * @method \string remindActualPhoneNumber()
	 * @method \string requirePhoneNumber()
	 * @method \Bitrix\Voximplant\EO_Blacklist resetPhoneNumber()
	 * @method \Bitrix\Voximplant\EO_Blacklist unsetPhoneNumber()
	 * @method \string fillPhoneNumber()
	 * @method \string getNumberStripped()
	 * @method \Bitrix\Voximplant\EO_Blacklist setNumberStripped(\string|\Bitrix\Main\DB\SqlExpression $numberStripped)
	 * @method bool hasNumberStripped()
	 * @method bool isNumberStrippedFilled()
	 * @method bool isNumberStrippedChanged()
	 * @method \string remindActualNumberStripped()
	 * @method \string requireNumberStripped()
	 * @method \Bitrix\Voximplant\EO_Blacklist resetNumberStripped()
	 * @method \Bitrix\Voximplant\EO_Blacklist unsetNumberStripped()
	 * @method \string fillNumberStripped()
	 * @method \string getNumberE164()
	 * @method \Bitrix\Voximplant\EO_Blacklist setNumberE164(\string|\Bitrix\Main\DB\SqlExpression $numberE164)
	 * @method bool hasNumberE164()
	 * @method bool isNumberE164Filled()
	 * @method bool isNumberE164Changed()
	 * @method \string remindActualNumberE164()
	 * @method \string requireNumberE164()
	 * @method \Bitrix\Voximplant\EO_Blacklist resetNumberE164()
	 * @method \Bitrix\Voximplant\EO_Blacklist unsetNumberE164()
	 * @method \string fillNumberE164()
	 * @method \Bitrix\Main\Type\DateTime getInserted()
	 * @method \Bitrix\Voximplant\EO_Blacklist setInserted(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $inserted)
	 * @method bool hasInserted()
	 * @method bool isInsertedFilled()
	 * @method bool isInsertedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualInserted()
	 * @method \Bitrix\Main\Type\DateTime requireInserted()
	 * @method \Bitrix\Voximplant\EO_Blacklist resetInserted()
	 * @method \Bitrix\Voximplant\EO_Blacklist unsetInserted()
	 * @method \Bitrix\Main\Type\DateTime fillInserted()
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
	 * @method \Bitrix\Voximplant\EO_Blacklist set($fieldName, $value)
	 * @method \Bitrix\Voximplant\EO_Blacklist reset($fieldName)
	 * @method \Bitrix\Voximplant\EO_Blacklist unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\EO_Blacklist wakeUp($data)
	 */
	class EO_Blacklist {
		/* @var \Bitrix\Voximplant\BlacklistTable */
		static public $dataClass = '\Bitrix\Voximplant\BlacklistTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant {
	/**
	 * EO_Blacklist_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getPhoneNumberList()
	 * @method \string[] fillPhoneNumber()
	 * @method \string[] getNumberStrippedList()
	 * @method \string[] fillNumberStripped()
	 * @method \string[] getNumberE164List()
	 * @method \string[] fillNumberE164()
	 * @method \Bitrix\Main\Type\DateTime[] getInsertedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillInserted()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\EO_Blacklist $object)
	 * @method bool has(\Bitrix\Voximplant\EO_Blacklist $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Blacklist getByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Blacklist[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\EO_Blacklist $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\EO_Blacklist_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\EO_Blacklist current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Blacklist_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\BlacklistTable */
		static public $dataClass = '\Bitrix\Voximplant\BlacklistTable';
	}
}
namespace Bitrix\Voximplant {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Blacklist_Result exec()
	 * @method \Bitrix\Voximplant\EO_Blacklist fetchObject()
	 * @method \Bitrix\Voximplant\EO_Blacklist_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Blacklist_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\EO_Blacklist fetchObject()
	 * @method \Bitrix\Voximplant\EO_Blacklist_Collection fetchCollection()
	 */
	class EO_Blacklist_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\EO_Blacklist createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\EO_Blacklist_Collection createCollection()
	 * @method \Bitrix\Voximplant\EO_Blacklist wakeUpObject($row)
	 * @method \Bitrix\Voximplant\EO_Blacklist_Collection wakeUpCollection($rows)
	 */
	class EO_Blacklist_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\ConfigTable:voximplant\lib\config.php:4758ce434e7f3ba46a43a38d06631ef4 */
namespace Bitrix\Voximplant {
	/**
	 * EO_Config
	 * @see \Bitrix\Voximplant\ConfigTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\EO_Config setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getPortalMode()
	 * @method \Bitrix\Voximplant\EO_Config setPortalMode(\string|\Bitrix\Main\DB\SqlExpression $portalMode)
	 * @method bool hasPortalMode()
	 * @method bool isPortalModeFilled()
	 * @method bool isPortalModeChanged()
	 * @method \string remindActualPortalMode()
	 * @method \string requirePortalMode()
	 * @method \Bitrix\Voximplant\EO_Config resetPortalMode()
	 * @method \Bitrix\Voximplant\EO_Config unsetPortalMode()
	 * @method \string fillPortalMode()
	 * @method \string getSearchId()
	 * @method \Bitrix\Voximplant\EO_Config setSearchId(\string|\Bitrix\Main\DB\SqlExpression $searchId)
	 * @method bool hasSearchId()
	 * @method bool isSearchIdFilled()
	 * @method bool isSearchIdChanged()
	 * @method \string remindActualSearchId()
	 * @method \string requireSearchId()
	 * @method \Bitrix\Voximplant\EO_Config resetSearchId()
	 * @method \Bitrix\Voximplant\EO_Config unsetSearchId()
	 * @method \string fillSearchId()
	 * @method \string getPhoneName()
	 * @method \Bitrix\Voximplant\EO_Config setPhoneName(\string|\Bitrix\Main\DB\SqlExpression $phoneName)
	 * @method bool hasPhoneName()
	 * @method bool isPhoneNameFilled()
	 * @method bool isPhoneNameChanged()
	 * @method \string remindActualPhoneName()
	 * @method \string requirePhoneName()
	 * @method \Bitrix\Voximplant\EO_Config resetPhoneName()
	 * @method \Bitrix\Voximplant\EO_Config unsetPhoneName()
	 * @method \string fillPhoneName()
	 * @method \string getPhoneCountryCode()
	 * @method \Bitrix\Voximplant\EO_Config setPhoneCountryCode(\string|\Bitrix\Main\DB\SqlExpression $phoneCountryCode)
	 * @method bool hasPhoneCountryCode()
	 * @method bool isPhoneCountryCodeFilled()
	 * @method bool isPhoneCountryCodeChanged()
	 * @method \string remindActualPhoneCountryCode()
	 * @method \string requirePhoneCountryCode()
	 * @method \Bitrix\Voximplant\EO_Config resetPhoneCountryCode()
	 * @method \Bitrix\Voximplant\EO_Config unsetPhoneCountryCode()
	 * @method \string fillPhoneCountryCode()
	 * @method \boolean getPhoneVerified()
	 * @method \Bitrix\Voximplant\EO_Config setPhoneVerified(\boolean|\Bitrix\Main\DB\SqlExpression $phoneVerified)
	 * @method bool hasPhoneVerified()
	 * @method bool isPhoneVerifiedFilled()
	 * @method bool isPhoneVerifiedChanged()
	 * @method \boolean remindActualPhoneVerified()
	 * @method \boolean requirePhoneVerified()
	 * @method \Bitrix\Voximplant\EO_Config resetPhoneVerified()
	 * @method \Bitrix\Voximplant\EO_Config unsetPhoneVerified()
	 * @method \boolean fillPhoneVerified()
	 * @method \boolean getCrm()
	 * @method \Bitrix\Voximplant\EO_Config setCrm(\boolean|\Bitrix\Main\DB\SqlExpression $crm)
	 * @method bool hasCrm()
	 * @method bool isCrmFilled()
	 * @method bool isCrmChanged()
	 * @method \boolean remindActualCrm()
	 * @method \boolean requireCrm()
	 * @method \Bitrix\Voximplant\EO_Config resetCrm()
	 * @method \Bitrix\Voximplant\EO_Config unsetCrm()
	 * @method \boolean fillCrm()
	 * @method \string getCrmRule()
	 * @method \Bitrix\Voximplant\EO_Config setCrmRule(\string|\Bitrix\Main\DB\SqlExpression $crmRule)
	 * @method bool hasCrmRule()
	 * @method bool isCrmRuleFilled()
	 * @method bool isCrmRuleChanged()
	 * @method \string remindActualCrmRule()
	 * @method \string requireCrmRule()
	 * @method \Bitrix\Voximplant\EO_Config resetCrmRule()
	 * @method \Bitrix\Voximplant\EO_Config unsetCrmRule()
	 * @method \string fillCrmRule()
	 * @method \string getCrmCreate()
	 * @method \Bitrix\Voximplant\EO_Config setCrmCreate(\string|\Bitrix\Main\DB\SqlExpression $crmCreate)
	 * @method bool hasCrmCreate()
	 * @method bool isCrmCreateFilled()
	 * @method bool isCrmCreateChanged()
	 * @method \string remindActualCrmCreate()
	 * @method \string requireCrmCreate()
	 * @method \Bitrix\Voximplant\EO_Config resetCrmCreate()
	 * @method \Bitrix\Voximplant\EO_Config unsetCrmCreate()
	 * @method \string fillCrmCreate()
	 * @method \string getCrmCreateCallType()
	 * @method \Bitrix\Voximplant\EO_Config setCrmCreateCallType(\string|\Bitrix\Main\DB\SqlExpression $crmCreateCallType)
	 * @method bool hasCrmCreateCallType()
	 * @method bool isCrmCreateCallTypeFilled()
	 * @method bool isCrmCreateCallTypeChanged()
	 * @method \string remindActualCrmCreateCallType()
	 * @method \string requireCrmCreateCallType()
	 * @method \Bitrix\Voximplant\EO_Config resetCrmCreateCallType()
	 * @method \Bitrix\Voximplant\EO_Config unsetCrmCreateCallType()
	 * @method \string fillCrmCreateCallType()
	 * @method \string getCrmSource()
	 * @method \Bitrix\Voximplant\EO_Config setCrmSource(\string|\Bitrix\Main\DB\SqlExpression $crmSource)
	 * @method bool hasCrmSource()
	 * @method bool isCrmSourceFilled()
	 * @method bool isCrmSourceChanged()
	 * @method \string remindActualCrmSource()
	 * @method \string requireCrmSource()
	 * @method \Bitrix\Voximplant\EO_Config resetCrmSource()
	 * @method \Bitrix\Voximplant\EO_Config unsetCrmSource()
	 * @method \string fillCrmSource()
	 * @method \boolean getCrmForward()
	 * @method \Bitrix\Voximplant\EO_Config setCrmForward(\boolean|\Bitrix\Main\DB\SqlExpression $crmForward)
	 * @method bool hasCrmForward()
	 * @method bool isCrmForwardFilled()
	 * @method bool isCrmForwardChanged()
	 * @method \boolean remindActualCrmForward()
	 * @method \boolean requireCrmForward()
	 * @method \Bitrix\Voximplant\EO_Config resetCrmForward()
	 * @method \Bitrix\Voximplant\EO_Config unsetCrmForward()
	 * @method \boolean fillCrmForward()
	 * @method \boolean getCrmTransferChange()
	 * @method \Bitrix\Voximplant\EO_Config setCrmTransferChange(\boolean|\Bitrix\Main\DB\SqlExpression $crmTransferChange)
	 * @method bool hasCrmTransferChange()
	 * @method bool isCrmTransferChangeFilled()
	 * @method bool isCrmTransferChangeChanged()
	 * @method \boolean remindActualCrmTransferChange()
	 * @method \boolean requireCrmTransferChange()
	 * @method \Bitrix\Voximplant\EO_Config resetCrmTransferChange()
	 * @method \Bitrix\Voximplant\EO_Config unsetCrmTransferChange()
	 * @method \boolean fillCrmTransferChange()
	 * @method \boolean getIvr()
	 * @method \Bitrix\Voximplant\EO_Config setIvr(\boolean|\Bitrix\Main\DB\SqlExpression $ivr)
	 * @method bool hasIvr()
	 * @method bool isIvrFilled()
	 * @method bool isIvrChanged()
	 * @method \boolean remindActualIvr()
	 * @method \boolean requireIvr()
	 * @method \Bitrix\Voximplant\EO_Config resetIvr()
	 * @method \Bitrix\Voximplant\EO_Config unsetIvr()
	 * @method \boolean fillIvr()
	 * @method \int getQueueId()
	 * @method \Bitrix\Voximplant\EO_Config setQueueId(\int|\Bitrix\Main\DB\SqlExpression $queueId)
	 * @method bool hasQueueId()
	 * @method bool isQueueIdFilled()
	 * @method bool isQueueIdChanged()
	 * @method \int remindActualQueueId()
	 * @method \int requireQueueId()
	 * @method \Bitrix\Voximplant\EO_Config resetQueueId()
	 * @method \Bitrix\Voximplant\EO_Config unsetQueueId()
	 * @method \int fillQueueId()
	 * @method \int getIvrId()
	 * @method \Bitrix\Voximplant\EO_Config setIvrId(\int|\Bitrix\Main\DB\SqlExpression $ivrId)
	 * @method bool hasIvrId()
	 * @method bool isIvrIdFilled()
	 * @method bool isIvrIdChanged()
	 * @method \int remindActualIvrId()
	 * @method \int requireIvrId()
	 * @method \Bitrix\Voximplant\EO_Config resetIvrId()
	 * @method \Bitrix\Voximplant\EO_Config unsetIvrId()
	 * @method \int fillIvrId()
	 * @method \boolean getDirectCode()
	 * @method \Bitrix\Voximplant\EO_Config setDirectCode(\boolean|\Bitrix\Main\DB\SqlExpression $directCode)
	 * @method bool hasDirectCode()
	 * @method bool isDirectCodeFilled()
	 * @method bool isDirectCodeChanged()
	 * @method \boolean remindActualDirectCode()
	 * @method \boolean requireDirectCode()
	 * @method \Bitrix\Voximplant\EO_Config resetDirectCode()
	 * @method \Bitrix\Voximplant\EO_Config unsetDirectCode()
	 * @method \boolean fillDirectCode()
	 * @method \string getDirectCodeRule()
	 * @method \Bitrix\Voximplant\EO_Config setDirectCodeRule(\string|\Bitrix\Main\DB\SqlExpression $directCodeRule)
	 * @method bool hasDirectCodeRule()
	 * @method bool isDirectCodeRuleFilled()
	 * @method bool isDirectCodeRuleChanged()
	 * @method \string remindActualDirectCodeRule()
	 * @method \string requireDirectCodeRule()
	 * @method \Bitrix\Voximplant\EO_Config resetDirectCodeRule()
	 * @method \Bitrix\Voximplant\EO_Config unsetDirectCodeRule()
	 * @method \string fillDirectCodeRule()
	 * @method \boolean getRecording()
	 * @method \Bitrix\Voximplant\EO_Config setRecording(\boolean|\Bitrix\Main\DB\SqlExpression $recording)
	 * @method bool hasRecording()
	 * @method bool isRecordingFilled()
	 * @method bool isRecordingChanged()
	 * @method \boolean remindActualRecording()
	 * @method \boolean requireRecording()
	 * @method \Bitrix\Voximplant\EO_Config resetRecording()
	 * @method \Bitrix\Voximplant\EO_Config unsetRecording()
	 * @method \boolean fillRecording()
	 * @method \int getRecordingTime()
	 * @method \Bitrix\Voximplant\EO_Config setRecordingTime(\int|\Bitrix\Main\DB\SqlExpression $recordingTime)
	 * @method bool hasRecordingTime()
	 * @method bool isRecordingTimeFilled()
	 * @method bool isRecordingTimeChanged()
	 * @method \int remindActualRecordingTime()
	 * @method \int requireRecordingTime()
	 * @method \Bitrix\Voximplant\EO_Config resetRecordingTime()
	 * @method \Bitrix\Voximplant\EO_Config unsetRecordingTime()
	 * @method \int fillRecordingTime()
	 * @method \boolean getRecordingNotice()
	 * @method \Bitrix\Voximplant\EO_Config setRecordingNotice(\boolean|\Bitrix\Main\DB\SqlExpression $recordingNotice)
	 * @method bool hasRecordingNotice()
	 * @method bool isRecordingNoticeFilled()
	 * @method bool isRecordingNoticeChanged()
	 * @method \boolean remindActualRecordingNotice()
	 * @method \boolean requireRecordingNotice()
	 * @method \Bitrix\Voximplant\EO_Config resetRecordingNotice()
	 * @method \Bitrix\Voximplant\EO_Config unsetRecordingNotice()
	 * @method \boolean fillRecordingNotice()
	 * @method \string getForwardLine()
	 * @method \Bitrix\Voximplant\EO_Config setForwardLine(\string|\Bitrix\Main\DB\SqlExpression $forwardLine)
	 * @method bool hasForwardLine()
	 * @method bool isForwardLineFilled()
	 * @method bool isForwardLineChanged()
	 * @method \string remindActualForwardLine()
	 * @method \string requireForwardLine()
	 * @method \Bitrix\Voximplant\EO_Config resetForwardLine()
	 * @method \Bitrix\Voximplant\EO_Config unsetForwardLine()
	 * @method \string fillForwardLine()
	 * @method \boolean getVoicemail()
	 * @method \Bitrix\Voximplant\EO_Config setVoicemail(\boolean|\Bitrix\Main\DB\SqlExpression $voicemail)
	 * @method bool hasVoicemail()
	 * @method bool isVoicemailFilled()
	 * @method bool isVoicemailChanged()
	 * @method \boolean remindActualVoicemail()
	 * @method \boolean requireVoicemail()
	 * @method \Bitrix\Voximplant\EO_Config resetVoicemail()
	 * @method \Bitrix\Voximplant\EO_Config unsetVoicemail()
	 * @method \boolean fillVoicemail()
	 * @method \boolean getVote()
	 * @method \Bitrix\Voximplant\EO_Config setVote(\boolean|\Bitrix\Main\DB\SqlExpression $vote)
	 * @method bool hasVote()
	 * @method bool isVoteFilled()
	 * @method bool isVoteChanged()
	 * @method \boolean remindActualVote()
	 * @method \boolean requireVote()
	 * @method \Bitrix\Voximplant\EO_Config resetVote()
	 * @method \Bitrix\Voximplant\EO_Config unsetVote()
	 * @method \boolean fillVote()
	 * @method \string getMelodyLang()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyLang(\string|\Bitrix\Main\DB\SqlExpression $melodyLang)
	 * @method bool hasMelodyLang()
	 * @method bool isMelodyLangFilled()
	 * @method bool isMelodyLangChanged()
	 * @method \string remindActualMelodyLang()
	 * @method \string requireMelodyLang()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyLang()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyLang()
	 * @method \string fillMelodyLang()
	 * @method \int getMelodyWelcome()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyWelcome(\int|\Bitrix\Main\DB\SqlExpression $melodyWelcome)
	 * @method bool hasMelodyWelcome()
	 * @method bool isMelodyWelcomeFilled()
	 * @method bool isMelodyWelcomeChanged()
	 * @method \int remindActualMelodyWelcome()
	 * @method \int requireMelodyWelcome()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyWelcome()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyWelcome()
	 * @method \int fillMelodyWelcome()
	 * @method \boolean getMelodyWelcomeEnable()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyWelcomeEnable(\boolean|\Bitrix\Main\DB\SqlExpression $melodyWelcomeEnable)
	 * @method bool hasMelodyWelcomeEnable()
	 * @method bool isMelodyWelcomeEnableFilled()
	 * @method bool isMelodyWelcomeEnableChanged()
	 * @method \boolean remindActualMelodyWelcomeEnable()
	 * @method \boolean requireMelodyWelcomeEnable()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyWelcomeEnable()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyWelcomeEnable()
	 * @method \boolean fillMelodyWelcomeEnable()
	 * @method \int getMelodyWait()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyWait(\int|\Bitrix\Main\DB\SqlExpression $melodyWait)
	 * @method bool hasMelodyWait()
	 * @method bool isMelodyWaitFilled()
	 * @method bool isMelodyWaitChanged()
	 * @method \int remindActualMelodyWait()
	 * @method \int requireMelodyWait()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyWait()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyWait()
	 * @method \int fillMelodyWait()
	 * @method \int getMelodyEnqueue()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyEnqueue(\int|\Bitrix\Main\DB\SqlExpression $melodyEnqueue)
	 * @method bool hasMelodyEnqueue()
	 * @method bool isMelodyEnqueueFilled()
	 * @method bool isMelodyEnqueueChanged()
	 * @method \int remindActualMelodyEnqueue()
	 * @method \int requireMelodyEnqueue()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyEnqueue()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyEnqueue()
	 * @method \int fillMelodyEnqueue()
	 * @method \int getMelodyHold()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyHold(\int|\Bitrix\Main\DB\SqlExpression $melodyHold)
	 * @method bool hasMelodyHold()
	 * @method bool isMelodyHoldFilled()
	 * @method bool isMelodyHoldChanged()
	 * @method \int remindActualMelodyHold()
	 * @method \int requireMelodyHold()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyHold()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyHold()
	 * @method \int fillMelodyHold()
	 * @method \int getMelodyRecording()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyRecording(\int|\Bitrix\Main\DB\SqlExpression $melodyRecording)
	 * @method bool hasMelodyRecording()
	 * @method bool isMelodyRecordingFilled()
	 * @method bool isMelodyRecordingChanged()
	 * @method \int remindActualMelodyRecording()
	 * @method \int requireMelodyRecording()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyRecording()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyRecording()
	 * @method \int fillMelodyRecording()
	 * @method \int getMelodyVote()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyVote(\int|\Bitrix\Main\DB\SqlExpression $melodyVote)
	 * @method bool hasMelodyVote()
	 * @method bool isMelodyVoteFilled()
	 * @method bool isMelodyVoteChanged()
	 * @method \int remindActualMelodyVote()
	 * @method \int requireMelodyVote()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyVote()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyVote()
	 * @method \int fillMelodyVote()
	 * @method \int getMelodyVoteEnd()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyVoteEnd(\int|\Bitrix\Main\DB\SqlExpression $melodyVoteEnd)
	 * @method bool hasMelodyVoteEnd()
	 * @method bool isMelodyVoteEndFilled()
	 * @method bool isMelodyVoteEndChanged()
	 * @method \int remindActualMelodyVoteEnd()
	 * @method \int requireMelodyVoteEnd()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyVoteEnd()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyVoteEnd()
	 * @method \int fillMelodyVoteEnd()
	 * @method \int getMelodyVoicemail()
	 * @method \Bitrix\Voximplant\EO_Config setMelodyVoicemail(\int|\Bitrix\Main\DB\SqlExpression $melodyVoicemail)
	 * @method bool hasMelodyVoicemail()
	 * @method bool isMelodyVoicemailFilled()
	 * @method bool isMelodyVoicemailChanged()
	 * @method \int remindActualMelodyVoicemail()
	 * @method \int requireMelodyVoicemail()
	 * @method \Bitrix\Voximplant\EO_Config resetMelodyVoicemail()
	 * @method \Bitrix\Voximplant\EO_Config unsetMelodyVoicemail()
	 * @method \int fillMelodyVoicemail()
	 * @method \boolean getTimeman()
	 * @method \Bitrix\Voximplant\EO_Config setTimeman(\boolean|\Bitrix\Main\DB\SqlExpression $timeman)
	 * @method bool hasTimeman()
	 * @method bool isTimemanFilled()
	 * @method bool isTimemanChanged()
	 * @method \boolean remindActualTimeman()
	 * @method \boolean requireTimeman()
	 * @method \Bitrix\Voximplant\EO_Config resetTimeman()
	 * @method \Bitrix\Voximplant\EO_Config unsetTimeman()
	 * @method \boolean fillTimeman()
	 * @method \boolean getWorktimeEnable()
	 * @method \Bitrix\Voximplant\EO_Config setWorktimeEnable(\boolean|\Bitrix\Main\DB\SqlExpression $worktimeEnable)
	 * @method bool hasWorktimeEnable()
	 * @method bool isWorktimeEnableFilled()
	 * @method bool isWorktimeEnableChanged()
	 * @method \boolean remindActualWorktimeEnable()
	 * @method \boolean requireWorktimeEnable()
	 * @method \Bitrix\Voximplant\EO_Config resetWorktimeEnable()
	 * @method \Bitrix\Voximplant\EO_Config unsetWorktimeEnable()
	 * @method \boolean fillWorktimeEnable()
	 * @method \string getWorktimeFrom()
	 * @method \Bitrix\Voximplant\EO_Config setWorktimeFrom(\string|\Bitrix\Main\DB\SqlExpression $worktimeFrom)
	 * @method bool hasWorktimeFrom()
	 * @method bool isWorktimeFromFilled()
	 * @method bool isWorktimeFromChanged()
	 * @method \string remindActualWorktimeFrom()
	 * @method \string requireWorktimeFrom()
	 * @method \Bitrix\Voximplant\EO_Config resetWorktimeFrom()
	 * @method \Bitrix\Voximplant\EO_Config unsetWorktimeFrom()
	 * @method \string fillWorktimeFrom()
	 * @method \string getWorktimeTo()
	 * @method \Bitrix\Voximplant\EO_Config setWorktimeTo(\string|\Bitrix\Main\DB\SqlExpression $worktimeTo)
	 * @method bool hasWorktimeTo()
	 * @method bool isWorktimeToFilled()
	 * @method bool isWorktimeToChanged()
	 * @method \string remindActualWorktimeTo()
	 * @method \string requireWorktimeTo()
	 * @method \Bitrix\Voximplant\EO_Config resetWorktimeTo()
	 * @method \Bitrix\Voximplant\EO_Config unsetWorktimeTo()
	 * @method \string fillWorktimeTo()
	 * @method \string getWorktimeTimezone()
	 * @method \Bitrix\Voximplant\EO_Config setWorktimeTimezone(\string|\Bitrix\Main\DB\SqlExpression $worktimeTimezone)
	 * @method bool hasWorktimeTimezone()
	 * @method bool isWorktimeTimezoneFilled()
	 * @method bool isWorktimeTimezoneChanged()
	 * @method \string remindActualWorktimeTimezone()
	 * @method \string requireWorktimeTimezone()
	 * @method \Bitrix\Voximplant\EO_Config resetWorktimeTimezone()
	 * @method \Bitrix\Voximplant\EO_Config unsetWorktimeTimezone()
	 * @method \string fillWorktimeTimezone()
	 * @method \string getWorktimeHolidays()
	 * @method \Bitrix\Voximplant\EO_Config setWorktimeHolidays(\string|\Bitrix\Main\DB\SqlExpression $worktimeHolidays)
	 * @method bool hasWorktimeHolidays()
	 * @method bool isWorktimeHolidaysFilled()
	 * @method bool isWorktimeHolidaysChanged()
	 * @method \string remindActualWorktimeHolidays()
	 * @method \string requireWorktimeHolidays()
	 * @method \Bitrix\Voximplant\EO_Config resetWorktimeHolidays()
	 * @method \Bitrix\Voximplant\EO_Config unsetWorktimeHolidays()
	 * @method \string fillWorktimeHolidays()
	 * @method \string getWorktimeDayoff()
	 * @method \Bitrix\Voximplant\EO_Config setWorktimeDayoff(\string|\Bitrix\Main\DB\SqlExpression $worktimeDayoff)
	 * @method bool hasWorktimeDayoff()
	 * @method bool isWorktimeDayoffFilled()
	 * @method bool isWorktimeDayoffChanged()
	 * @method \string remindActualWorktimeDayoff()
	 * @method \string requireWorktimeDayoff()
	 * @method \Bitrix\Voximplant\EO_Config resetWorktimeDayoff()
	 * @method \Bitrix\Voximplant\EO_Config unsetWorktimeDayoff()
	 * @method \string fillWorktimeDayoff()
	 * @method \string getWorktimeDayoffRule()
	 * @method \Bitrix\Voximplant\EO_Config setWorktimeDayoffRule(\string|\Bitrix\Main\DB\SqlExpression $worktimeDayoffRule)
	 * @method bool hasWorktimeDayoffRule()
	 * @method bool isWorktimeDayoffRuleFilled()
	 * @method bool isWorktimeDayoffRuleChanged()
	 * @method \string remindActualWorktimeDayoffRule()
	 * @method \string requireWorktimeDayoffRule()
	 * @method \Bitrix\Voximplant\EO_Config resetWorktimeDayoffRule()
	 * @method \Bitrix\Voximplant\EO_Config unsetWorktimeDayoffRule()
	 * @method \string fillWorktimeDayoffRule()
	 * @method \string getWorktimeDayoffNumber()
	 * @method \Bitrix\Voximplant\EO_Config setWorktimeDayoffNumber(\string|\Bitrix\Main\DB\SqlExpression $worktimeDayoffNumber)
	 * @method bool hasWorktimeDayoffNumber()
	 * @method bool isWorktimeDayoffNumberFilled()
	 * @method bool isWorktimeDayoffNumberChanged()
	 * @method \string remindActualWorktimeDayoffNumber()
	 * @method \string requireWorktimeDayoffNumber()
	 * @method \Bitrix\Voximplant\EO_Config resetWorktimeDayoffNumber()
	 * @method \Bitrix\Voximplant\EO_Config unsetWorktimeDayoffNumber()
	 * @method \string fillWorktimeDayoffNumber()
	 * @method \int getWorktimeDayoffMelody()
	 * @method \Bitrix\Voximplant\EO_Config setWorktimeDayoffMelody(\int|\Bitrix\Main\DB\SqlExpression $worktimeDayoffMelody)
	 * @method bool hasWorktimeDayoffMelody()
	 * @method bool isWorktimeDayoffMelodyFilled()
	 * @method bool isWorktimeDayoffMelodyChanged()
	 * @method \int remindActualWorktimeDayoffMelody()
	 * @method \int requireWorktimeDayoffMelody()
	 * @method \Bitrix\Voximplant\EO_Config resetWorktimeDayoffMelody()
	 * @method \Bitrix\Voximplant\EO_Config unsetWorktimeDayoffMelody()
	 * @method \int fillWorktimeDayoffMelody()
	 * @method \boolean getUseSipTo()
	 * @method \Bitrix\Voximplant\EO_Config setUseSipTo(\boolean|\Bitrix\Main\DB\SqlExpression $useSipTo)
	 * @method bool hasUseSipTo()
	 * @method bool isUseSipToFilled()
	 * @method bool isUseSipToChanged()
	 * @method \boolean remindActualUseSipTo()
	 * @method \boolean requireUseSipTo()
	 * @method \Bitrix\Voximplant\EO_Config resetUseSipTo()
	 * @method \Bitrix\Voximplant\EO_Config unsetUseSipTo()
	 * @method \boolean fillUseSipTo()
	 * @method \int getWaitCrm()
	 * @method \Bitrix\Voximplant\EO_Config setWaitCrm(\int|\Bitrix\Main\DB\SqlExpression $waitCrm)
	 * @method bool hasWaitCrm()
	 * @method bool isWaitCrmFilled()
	 * @method bool isWaitCrmChanged()
	 * @method \int remindActualWaitCrm()
	 * @method \int requireWaitCrm()
	 * @method \Bitrix\Voximplant\EO_Config resetWaitCrm()
	 * @method \Bitrix\Voximplant\EO_Config unsetWaitCrm()
	 * @method \int fillWaitCrm()
	 * @method \int getWaitDirect()
	 * @method \Bitrix\Voximplant\EO_Config setWaitDirect(\int|\Bitrix\Main\DB\SqlExpression $waitDirect)
	 * @method bool hasWaitDirect()
	 * @method bool isWaitDirectFilled()
	 * @method bool isWaitDirectChanged()
	 * @method \int remindActualWaitDirect()
	 * @method \int requireWaitDirect()
	 * @method \Bitrix\Voximplant\EO_Config resetWaitDirect()
	 * @method \Bitrix\Voximplant\EO_Config unsetWaitDirect()
	 * @method \int fillWaitDirect()
	 * @method \boolean getTranscribe()
	 * @method \Bitrix\Voximplant\EO_Config setTranscribe(\boolean|\Bitrix\Main\DB\SqlExpression $transcribe)
	 * @method bool hasTranscribe()
	 * @method bool isTranscribeFilled()
	 * @method bool isTranscribeChanged()
	 * @method \boolean remindActualTranscribe()
	 * @method \boolean requireTranscribe()
	 * @method \Bitrix\Voximplant\EO_Config resetTranscribe()
	 * @method \Bitrix\Voximplant\EO_Config unsetTranscribe()
	 * @method \boolean fillTranscribe()
	 * @method \string getTranscribeLang()
	 * @method \Bitrix\Voximplant\EO_Config setTranscribeLang(\string|\Bitrix\Main\DB\SqlExpression $transcribeLang)
	 * @method bool hasTranscribeLang()
	 * @method bool isTranscribeLangFilled()
	 * @method bool isTranscribeLangChanged()
	 * @method \string remindActualTranscribeLang()
	 * @method \string requireTranscribeLang()
	 * @method \Bitrix\Voximplant\EO_Config resetTranscribeLang()
	 * @method \Bitrix\Voximplant\EO_Config unsetTranscribeLang()
	 * @method \string fillTranscribeLang()
	 * @method \string getTranscribeProvider()
	 * @method \Bitrix\Voximplant\EO_Config setTranscribeProvider(\string|\Bitrix\Main\DB\SqlExpression $transcribeProvider)
	 * @method bool hasTranscribeProvider()
	 * @method bool isTranscribeProviderFilled()
	 * @method bool isTranscribeProviderChanged()
	 * @method \string remindActualTranscribeProvider()
	 * @method \string requireTranscribeProvider()
	 * @method \Bitrix\Voximplant\EO_Config resetTranscribeProvider()
	 * @method \Bitrix\Voximplant\EO_Config unsetTranscribeProvider()
	 * @method \string fillTranscribeProvider()
	 * @method \string getCallbackRedial()
	 * @method \Bitrix\Voximplant\EO_Config setCallbackRedial(\string|\Bitrix\Main\DB\SqlExpression $callbackRedial)
	 * @method bool hasCallbackRedial()
	 * @method bool isCallbackRedialFilled()
	 * @method bool isCallbackRedialChanged()
	 * @method \string remindActualCallbackRedial()
	 * @method \string requireCallbackRedial()
	 * @method \Bitrix\Voximplant\EO_Config resetCallbackRedial()
	 * @method \Bitrix\Voximplant\EO_Config unsetCallbackRedial()
	 * @method \string fillCallbackRedial()
	 * @method \int getCallbackRedialAttempts()
	 * @method \Bitrix\Voximplant\EO_Config setCallbackRedialAttempts(\int|\Bitrix\Main\DB\SqlExpression $callbackRedialAttempts)
	 * @method bool hasCallbackRedialAttempts()
	 * @method bool isCallbackRedialAttemptsFilled()
	 * @method bool isCallbackRedialAttemptsChanged()
	 * @method \int remindActualCallbackRedialAttempts()
	 * @method \int requireCallbackRedialAttempts()
	 * @method \Bitrix\Voximplant\EO_Config resetCallbackRedialAttempts()
	 * @method \Bitrix\Voximplant\EO_Config unsetCallbackRedialAttempts()
	 * @method \int fillCallbackRedialAttempts()
	 * @method \int getCallbackRedialPeriod()
	 * @method \Bitrix\Voximplant\EO_Config setCallbackRedialPeriod(\int|\Bitrix\Main\DB\SqlExpression $callbackRedialPeriod)
	 * @method bool hasCallbackRedialPeriod()
	 * @method bool isCallbackRedialPeriodFilled()
	 * @method bool isCallbackRedialPeriodChanged()
	 * @method \int remindActualCallbackRedialPeriod()
	 * @method \int requireCallbackRedialPeriod()
	 * @method \Bitrix\Voximplant\EO_Config resetCallbackRedialPeriod()
	 * @method \Bitrix\Voximplant\EO_Config unsetCallbackRedialPeriod()
	 * @method \int fillCallbackRedialPeriod()
	 * @method \string getLinePrefix()
	 * @method \Bitrix\Voximplant\EO_Config setLinePrefix(\string|\Bitrix\Main\DB\SqlExpression $linePrefix)
	 * @method bool hasLinePrefix()
	 * @method bool isLinePrefixFilled()
	 * @method bool isLinePrefixChanged()
	 * @method \string remindActualLinePrefix()
	 * @method \string requireLinePrefix()
	 * @method \Bitrix\Voximplant\EO_Config resetLinePrefix()
	 * @method \Bitrix\Voximplant\EO_Config unsetLinePrefix()
	 * @method \string fillLinePrefix()
	 * @method \boolean getCanBeSelected()
	 * @method \Bitrix\Voximplant\EO_Config setCanBeSelected(\boolean|\Bitrix\Main\DB\SqlExpression $canBeSelected)
	 * @method bool hasCanBeSelected()
	 * @method bool isCanBeSelectedFilled()
	 * @method bool isCanBeSelectedChanged()
	 * @method \boolean remindActualCanBeSelected()
	 * @method \boolean requireCanBeSelected()
	 * @method \Bitrix\Voximplant\EO_Config resetCanBeSelected()
	 * @method \Bitrix\Voximplant\EO_Config unsetCanBeSelected()
	 * @method \boolean fillCanBeSelected()
	 * @method \string getBackupNumber()
	 * @method \Bitrix\Voximplant\EO_Config setBackupNumber(\string|\Bitrix\Main\DB\SqlExpression $backupNumber)
	 * @method bool hasBackupNumber()
	 * @method bool isBackupNumberFilled()
	 * @method bool isBackupNumberChanged()
	 * @method \string remindActualBackupNumber()
	 * @method \string requireBackupNumber()
	 * @method \Bitrix\Voximplant\EO_Config resetBackupNumber()
	 * @method \Bitrix\Voximplant\EO_Config unsetBackupNumber()
	 * @method \string fillBackupNumber()
	 * @method \string getBackupLine()
	 * @method \Bitrix\Voximplant\EO_Config setBackupLine(\string|\Bitrix\Main\DB\SqlExpression $backupLine)
	 * @method bool hasBackupLine()
	 * @method bool isBackupLineFilled()
	 * @method bool isBackupLineChanged()
	 * @method \string remindActualBackupLine()
	 * @method \string requireBackupLine()
	 * @method \Bitrix\Voximplant\EO_Config resetBackupLine()
	 * @method \Bitrix\Voximplant\EO_Config unsetBackupLine()
	 * @method \string fillBackupLine()
	 * @method \boolean getRedirectWithClientNumber()
	 * @method \Bitrix\Voximplant\EO_Config setRedirectWithClientNumber(\boolean|\Bitrix\Main\DB\SqlExpression $redirectWithClientNumber)
	 * @method bool hasRedirectWithClientNumber()
	 * @method bool isRedirectWithClientNumberFilled()
	 * @method bool isRedirectWithClientNumberChanged()
	 * @method \boolean remindActualRedirectWithClientNumber()
	 * @method \boolean requireRedirectWithClientNumber()
	 * @method \Bitrix\Voximplant\EO_Config resetRedirectWithClientNumber()
	 * @method \Bitrix\Voximplant\EO_Config unsetRedirectWithClientNumber()
	 * @method \boolean fillRedirectWithClientNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Queue getQueue()
	 * @method \Bitrix\Voximplant\Model\EO_Queue remindActualQueue()
	 * @method \Bitrix\Voximplant\Model\EO_Queue requireQueue()
	 * @method \Bitrix\Voximplant\EO_Config setQueue(\Bitrix\Voximplant\Model\EO_Queue $object)
	 * @method \Bitrix\Voximplant\EO_Config resetQueue()
	 * @method \Bitrix\Voximplant\EO_Config unsetQueue()
	 * @method bool hasQueue()
	 * @method bool isQueueFilled()
	 * @method bool isQueueChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Queue fillQueue()
	 * @method \Bitrix\Voximplant\EO_Sip getSipConfig()
	 * @method \Bitrix\Voximplant\EO_Sip remindActualSipConfig()
	 * @method \Bitrix\Voximplant\EO_Sip requireSipConfig()
	 * @method \Bitrix\Voximplant\EO_Config setSipConfig(\Bitrix\Voximplant\EO_Sip $object)
	 * @method \Bitrix\Voximplant\EO_Config resetSipConfig()
	 * @method \Bitrix\Voximplant\EO_Config unsetSipConfig()
	 * @method bool hasSipConfig()
	 * @method bool isSipConfigFilled()
	 * @method bool isSipConfigChanged()
	 * @method \Bitrix\Voximplant\EO_Sip fillSipConfig()
	 * @method \Bitrix\Voximplant\Model\EO_Number getNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Number remindActualNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Number requireNumber()
	 * @method \Bitrix\Voximplant\EO_Config setNumber(\Bitrix\Voximplant\Model\EO_Number $object)
	 * @method \Bitrix\Voximplant\EO_Config resetNumber()
	 * @method \Bitrix\Voximplant\EO_Config unsetNumber()
	 * @method bool hasNumber()
	 * @method bool isNumberFilled()
	 * @method bool isNumberChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Number fillNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Number getGroupNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Number remindActualGroupNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Number requireGroupNumber()
	 * @method \Bitrix\Voximplant\EO_Config setGroupNumber(\Bitrix\Voximplant\Model\EO_Number $object)
	 * @method \Bitrix\Voximplant\EO_Config resetGroupNumber()
	 * @method \Bitrix\Voximplant\EO_Config unsetGroupNumber()
	 * @method bool hasGroupNumber()
	 * @method bool isGroupNumberFilled()
	 * @method bool isGroupNumberChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Number fillGroupNumber()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId getCallerId()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId remindActualCallerId()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId requireCallerId()
	 * @method \Bitrix\Voximplant\EO_Config setCallerId(\Bitrix\Voximplant\Model\EO_CallerId $object)
	 * @method \Bitrix\Voximplant\EO_Config resetCallerId()
	 * @method \Bitrix\Voximplant\EO_Config unsetCallerId()
	 * @method bool hasCallerId()
	 * @method bool isCallerIdFilled()
	 * @method bool isCallerIdChanged()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId fillCallerId()
	 * @method \string getCnt()
	 * @method \string remindActualCnt()
	 * @method \string requireCnt()
	 * @method bool hasCnt()
	 * @method bool isCntFilled()
	 * @method \Bitrix\Voximplant\EO_Config unsetCnt()
	 * @method \string fillCnt()
	 * @method \string getHasNumber()
	 * @method \string remindActualHasNumber()
	 * @method \string requireHasNumber()
	 * @method bool hasHasNumber()
	 * @method bool isHasNumberFilled()
	 * @method \Bitrix\Voximplant\EO_Config unsetHasNumber()
	 * @method \string fillHasNumber()
	 * @method \string getHasSipConnection()
	 * @method \string remindActualHasSipConnection()
	 * @method \string requireHasSipConnection()
	 * @method bool hasHasSipConnection()
	 * @method bool isHasSipConnectionFilled()
	 * @method \Bitrix\Voximplant\EO_Config unsetHasSipConnection()
	 * @method \string fillHasSipConnection()
	 * @method \string getHasCallerId()
	 * @method \string remindActualHasCallerId()
	 * @method \string requireHasCallerId()
	 * @method bool hasHasCallerId()
	 * @method bool isHasCallerIdFilled()
	 * @method \Bitrix\Voximplant\EO_Config unsetHasCallerId()
	 * @method \string fillHasCallerId()
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
	 * @method \Bitrix\Voximplant\EO_Config set($fieldName, $value)
	 * @method \Bitrix\Voximplant\EO_Config reset($fieldName)
	 * @method \Bitrix\Voximplant\EO_Config unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\EO_Config wakeUp($data)
	 */
	class EO_Config {
		/* @var \Bitrix\Voximplant\ConfigTable */
		static public $dataClass = '\Bitrix\Voximplant\ConfigTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant {
	/**
	 * EO_Config_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getPortalModeList()
	 * @method \string[] fillPortalMode()
	 * @method \string[] getSearchIdList()
	 * @method \string[] fillSearchId()
	 * @method \string[] getPhoneNameList()
	 * @method \string[] fillPhoneName()
	 * @method \string[] getPhoneCountryCodeList()
	 * @method \string[] fillPhoneCountryCode()
	 * @method \boolean[] getPhoneVerifiedList()
	 * @method \boolean[] fillPhoneVerified()
	 * @method \boolean[] getCrmList()
	 * @method \boolean[] fillCrm()
	 * @method \string[] getCrmRuleList()
	 * @method \string[] fillCrmRule()
	 * @method \string[] getCrmCreateList()
	 * @method \string[] fillCrmCreate()
	 * @method \string[] getCrmCreateCallTypeList()
	 * @method \string[] fillCrmCreateCallType()
	 * @method \string[] getCrmSourceList()
	 * @method \string[] fillCrmSource()
	 * @method \boolean[] getCrmForwardList()
	 * @method \boolean[] fillCrmForward()
	 * @method \boolean[] getCrmTransferChangeList()
	 * @method \boolean[] fillCrmTransferChange()
	 * @method \boolean[] getIvrList()
	 * @method \boolean[] fillIvr()
	 * @method \int[] getQueueIdList()
	 * @method \int[] fillQueueId()
	 * @method \int[] getIvrIdList()
	 * @method \int[] fillIvrId()
	 * @method \boolean[] getDirectCodeList()
	 * @method \boolean[] fillDirectCode()
	 * @method \string[] getDirectCodeRuleList()
	 * @method \string[] fillDirectCodeRule()
	 * @method \boolean[] getRecordingList()
	 * @method \boolean[] fillRecording()
	 * @method \int[] getRecordingTimeList()
	 * @method \int[] fillRecordingTime()
	 * @method \boolean[] getRecordingNoticeList()
	 * @method \boolean[] fillRecordingNotice()
	 * @method \string[] getForwardLineList()
	 * @method \string[] fillForwardLine()
	 * @method \boolean[] getVoicemailList()
	 * @method \boolean[] fillVoicemail()
	 * @method \boolean[] getVoteList()
	 * @method \boolean[] fillVote()
	 * @method \string[] getMelodyLangList()
	 * @method \string[] fillMelodyLang()
	 * @method \int[] getMelodyWelcomeList()
	 * @method \int[] fillMelodyWelcome()
	 * @method \boolean[] getMelodyWelcomeEnableList()
	 * @method \boolean[] fillMelodyWelcomeEnable()
	 * @method \int[] getMelodyWaitList()
	 * @method \int[] fillMelodyWait()
	 * @method \int[] getMelodyEnqueueList()
	 * @method \int[] fillMelodyEnqueue()
	 * @method \int[] getMelodyHoldList()
	 * @method \int[] fillMelodyHold()
	 * @method \int[] getMelodyRecordingList()
	 * @method \int[] fillMelodyRecording()
	 * @method \int[] getMelodyVoteList()
	 * @method \int[] fillMelodyVote()
	 * @method \int[] getMelodyVoteEndList()
	 * @method \int[] fillMelodyVoteEnd()
	 * @method \int[] getMelodyVoicemailList()
	 * @method \int[] fillMelodyVoicemail()
	 * @method \boolean[] getTimemanList()
	 * @method \boolean[] fillTimeman()
	 * @method \boolean[] getWorktimeEnableList()
	 * @method \boolean[] fillWorktimeEnable()
	 * @method \string[] getWorktimeFromList()
	 * @method \string[] fillWorktimeFrom()
	 * @method \string[] getWorktimeToList()
	 * @method \string[] fillWorktimeTo()
	 * @method \string[] getWorktimeTimezoneList()
	 * @method \string[] fillWorktimeTimezone()
	 * @method \string[] getWorktimeHolidaysList()
	 * @method \string[] fillWorktimeHolidays()
	 * @method \string[] getWorktimeDayoffList()
	 * @method \string[] fillWorktimeDayoff()
	 * @method \string[] getWorktimeDayoffRuleList()
	 * @method \string[] fillWorktimeDayoffRule()
	 * @method \string[] getWorktimeDayoffNumberList()
	 * @method \string[] fillWorktimeDayoffNumber()
	 * @method \int[] getWorktimeDayoffMelodyList()
	 * @method \int[] fillWorktimeDayoffMelody()
	 * @method \boolean[] getUseSipToList()
	 * @method \boolean[] fillUseSipTo()
	 * @method \int[] getWaitCrmList()
	 * @method \int[] fillWaitCrm()
	 * @method \int[] getWaitDirectList()
	 * @method \int[] fillWaitDirect()
	 * @method \boolean[] getTranscribeList()
	 * @method \boolean[] fillTranscribe()
	 * @method \string[] getTranscribeLangList()
	 * @method \string[] fillTranscribeLang()
	 * @method \string[] getTranscribeProviderList()
	 * @method \string[] fillTranscribeProvider()
	 * @method \string[] getCallbackRedialList()
	 * @method \string[] fillCallbackRedial()
	 * @method \int[] getCallbackRedialAttemptsList()
	 * @method \int[] fillCallbackRedialAttempts()
	 * @method \int[] getCallbackRedialPeriodList()
	 * @method \int[] fillCallbackRedialPeriod()
	 * @method \string[] getLinePrefixList()
	 * @method \string[] fillLinePrefix()
	 * @method \boolean[] getCanBeSelectedList()
	 * @method \boolean[] fillCanBeSelected()
	 * @method \string[] getBackupNumberList()
	 * @method \string[] fillBackupNumber()
	 * @method \string[] getBackupLineList()
	 * @method \string[] fillBackupLine()
	 * @method \boolean[] getRedirectWithClientNumberList()
	 * @method \boolean[] fillRedirectWithClientNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Queue[] getQueueList()
	 * @method \Bitrix\Voximplant\EO_Config_Collection getQueueCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Queue_Collection fillQueue()
	 * @method \Bitrix\Voximplant\EO_Sip[] getSipConfigList()
	 * @method \Bitrix\Voximplant\EO_Config_Collection getSipConfigCollection()
	 * @method \Bitrix\Voximplant\EO_Sip_Collection fillSipConfig()
	 * @method \Bitrix\Voximplant\Model\EO_Number[] getNumberList()
	 * @method \Bitrix\Voximplant\EO_Config_Collection getNumberCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Number_Collection fillNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Number[] getGroupNumberList()
	 * @method \Bitrix\Voximplant\EO_Config_Collection getGroupNumberCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Number_Collection fillGroupNumber()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId[] getCallerIdList()
	 * @method \Bitrix\Voximplant\EO_Config_Collection getCallerIdCollection()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId_Collection fillCallerId()
	 * @method \string[] getCntList()
	 * @method \string[] fillCnt()
	 * @method \string[] getHasNumberList()
	 * @method \string[] fillHasNumber()
	 * @method \string[] getHasSipConnectionList()
	 * @method \string[] fillHasSipConnection()
	 * @method \string[] getHasCallerIdList()
	 * @method \string[] fillHasCallerId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\EO_Config $object)
	 * @method bool has(\Bitrix\Voximplant\EO_Config $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Config getByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Config[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\EO_Config $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\EO_Config_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\EO_Config current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Config_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\ConfigTable */
		static public $dataClass = '\Bitrix\Voximplant\ConfigTable';
	}
}
namespace Bitrix\Voximplant {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Config_Result exec()
	 * @method \Bitrix\Voximplant\EO_Config fetchObject()
	 * @method \Bitrix\Voximplant\EO_Config_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Config_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\EO_Config fetchObject()
	 * @method \Bitrix\Voximplant\EO_Config_Collection fetchCollection()
	 */
	class EO_Config_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\EO_Config createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\EO_Config_Collection createCollection()
	 * @method \Bitrix\Voximplant\EO_Config wakeUpObject($row)
	 * @method \Bitrix\Voximplant\EO_Config_Collection wakeUpCollection($rows)
	 */
	class EO_Config_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\CallTable:voximplant\lib\model\call.php:115634cb398a41beb6292b151199763e */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Call
	 * @see \Bitrix\Voximplant\Model\CallTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \int getUserId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetUserId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getPortalUserId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setPortalUserId(\int|\Bitrix\Main\DB\SqlExpression $portalUserId)
	 * @method bool hasPortalUserId()
	 * @method bool isPortalUserIdFilled()
	 * @method bool isPortalUserIdChanged()
	 * @method \int remindActualPortalUserId()
	 * @method \int requirePortalUserId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetPortalUserId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetPortalUserId()
	 * @method \int fillPortalUserId()
	 * @method \string getCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setCallId(\string|\Bitrix\Main\DB\SqlExpression $callId)
	 * @method bool hasCallId()
	 * @method bool isCallIdFilled()
	 * @method bool isCallIdChanged()
	 * @method \string remindActualCallId()
	 * @method \string requireCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetCallId()
	 * @method \string fillCallId()
	 * @method \string getExternalCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setExternalCallId(\string|\Bitrix\Main\DB\SqlExpression $externalCallId)
	 * @method bool hasExternalCallId()
	 * @method bool isExternalCallIdFilled()
	 * @method bool isExternalCallIdChanged()
	 * @method \string remindActualExternalCallId()
	 * @method \string requireExternalCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetExternalCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetExternalCallId()
	 * @method \string fillExternalCallId()
	 * @method \string getIncoming()
	 * @method \Bitrix\Voximplant\Model\EO_Call setIncoming(\string|\Bitrix\Main\DB\SqlExpression $incoming)
	 * @method bool hasIncoming()
	 * @method bool isIncomingFilled()
	 * @method bool isIncomingChanged()
	 * @method \string remindActualIncoming()
	 * @method \string requireIncoming()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetIncoming()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetIncoming()
	 * @method \string fillIncoming()
	 * @method \string getCallerId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setCallerId(\string|\Bitrix\Main\DB\SqlExpression $callerId)
	 * @method bool hasCallerId()
	 * @method bool isCallerIdFilled()
	 * @method bool isCallerIdChanged()
	 * @method \string remindActualCallerId()
	 * @method \string requireCallerId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetCallerId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetCallerId()
	 * @method \string fillCallerId()
	 * @method \string getStatus()
	 * @method \Bitrix\Voximplant\Model\EO_Call setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetStatus()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetStatus()
	 * @method \string fillStatus()
	 * @method \boolean getCrm()
	 * @method \Bitrix\Voximplant\Model\EO_Call setCrm(\boolean|\Bitrix\Main\DB\SqlExpression $crm)
	 * @method bool hasCrm()
	 * @method bool isCrmFilled()
	 * @method bool isCrmChanged()
	 * @method \boolean remindActualCrm()
	 * @method \boolean requireCrm()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetCrm()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetCrm()
	 * @method \boolean fillCrm()
	 * @method \int getCrmActivityId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setCrmActivityId(\int|\Bitrix\Main\DB\SqlExpression $crmActivityId)
	 * @method bool hasCrmActivityId()
	 * @method bool isCrmActivityIdFilled()
	 * @method bool isCrmActivityIdChanged()
	 * @method \int remindActualCrmActivityId()
	 * @method \int requireCrmActivityId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetCrmActivityId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetCrmActivityId()
	 * @method \int fillCrmActivityId()
	 * @method \int getCrmCallList()
	 * @method \Bitrix\Voximplant\Model\EO_Call setCrmCallList(\int|\Bitrix\Main\DB\SqlExpression $crmCallList)
	 * @method bool hasCrmCallList()
	 * @method bool isCrmCallListFilled()
	 * @method bool isCrmCallListChanged()
	 * @method \int remindActualCrmCallList()
	 * @method \int requireCrmCallList()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetCrmCallList()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetCrmCallList()
	 * @method \int fillCrmCallList()
	 * @method \string getCrmBindings()
	 * @method \Bitrix\Voximplant\Model\EO_Call setCrmBindings(\string|\Bitrix\Main\DB\SqlExpression $crmBindings)
	 * @method bool hasCrmBindings()
	 * @method bool isCrmBindingsFilled()
	 * @method bool isCrmBindingsChanged()
	 * @method \string remindActualCrmBindings()
	 * @method \string requireCrmBindings()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetCrmBindings()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetCrmBindings()
	 * @method \string fillCrmBindings()
	 * @method \string getAccessUrl()
	 * @method \Bitrix\Voximplant\Model\EO_Call setAccessUrl(\string|\Bitrix\Main\DB\SqlExpression $accessUrl)
	 * @method bool hasAccessUrl()
	 * @method bool isAccessUrlFilled()
	 * @method bool isAccessUrlChanged()
	 * @method \string remindActualAccessUrl()
	 * @method \string requireAccessUrl()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetAccessUrl()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetAccessUrl()
	 * @method \string fillAccessUrl()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_Call setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \int getRestAppId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setRestAppId(\int|\Bitrix\Main\DB\SqlExpression $restAppId)
	 * @method bool hasRestAppId()
	 * @method bool isRestAppIdFilled()
	 * @method bool isRestAppIdChanged()
	 * @method \int remindActualRestAppId()
	 * @method \int requireRestAppId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetRestAppId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetRestAppId()
	 * @method \int fillRestAppId()
	 * @method \int getExternalLineId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setExternalLineId(\int|\Bitrix\Main\DB\SqlExpression $externalLineId)
	 * @method bool hasExternalLineId()
	 * @method bool isExternalLineIdFilled()
	 * @method bool isExternalLineIdChanged()
	 * @method \int remindActualExternalLineId()
	 * @method \int requireExternalLineId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetExternalLineId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetExternalLineId()
	 * @method \int fillExternalLineId()
	 * @method \string getPortalNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Call setPortalNumber(\string|\Bitrix\Main\DB\SqlExpression $portalNumber)
	 * @method bool hasPortalNumber()
	 * @method bool isPortalNumberFilled()
	 * @method bool isPortalNumberChanged()
	 * @method \string remindActualPortalNumber()
	 * @method \string requirePortalNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetPortalNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetPortalNumber()
	 * @method \string fillPortalNumber()
	 * @method \string getStage()
	 * @method \Bitrix\Voximplant\Model\EO_Call setStage(\string|\Bitrix\Main\DB\SqlExpression $stage)
	 * @method bool hasStage()
	 * @method bool isStageFilled()
	 * @method bool isStageChanged()
	 * @method \string remindActualStage()
	 * @method \string requireStage()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetStage()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetStage()
	 * @method \string fillStage()
	 * @method \int getIvrActionId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setIvrActionId(\int|\Bitrix\Main\DB\SqlExpression $ivrActionId)
	 * @method bool hasIvrActionId()
	 * @method bool isIvrActionIdFilled()
	 * @method bool isIvrActionIdChanged()
	 * @method \int remindActualIvrActionId()
	 * @method \int requireIvrActionId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetIvrActionId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetIvrActionId()
	 * @method \int fillIvrActionId()
	 * @method \int getQueueId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setQueueId(\int|\Bitrix\Main\DB\SqlExpression $queueId)
	 * @method bool hasQueueId()
	 * @method bool isQueueIdFilled()
	 * @method bool isQueueIdChanged()
	 * @method \int remindActualQueueId()
	 * @method \int requireQueueId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetQueueId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetQueueId()
	 * @method \int fillQueueId()
	 * @method \string getQueueHistory()
	 * @method \Bitrix\Voximplant\Model\EO_Call setQueueHistory(\string|\Bitrix\Main\DB\SqlExpression $queueHistory)
	 * @method bool hasQueueHistory()
	 * @method bool isQueueHistoryFilled()
	 * @method bool isQueueHistoryChanged()
	 * @method \string remindActualQueueHistory()
	 * @method \string requireQueueHistory()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetQueueHistory()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetQueueHistory()
	 * @method \string fillQueueHistory()
	 * @method \int getSessionId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetSessionId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \string getCallbackParameters()
	 * @method \Bitrix\Voximplant\Model\EO_Call setCallbackParameters(\string|\Bitrix\Main\DB\SqlExpression $callbackParameters)
	 * @method bool hasCallbackParameters()
	 * @method bool isCallbackParametersFilled()
	 * @method bool isCallbackParametersChanged()
	 * @method \string remindActualCallbackParameters()
	 * @method \string requireCallbackParameters()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetCallbackParameters()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetCallbackParameters()
	 * @method \string fillCallbackParameters()
	 * @method \string getComment()
	 * @method \Bitrix\Voximplant\Model\EO_Call setComment(\string|\Bitrix\Main\DB\SqlExpression $comment)
	 * @method bool hasComment()
	 * @method bool isCommentFilled()
	 * @method bool isCommentChanged()
	 * @method \string remindActualComment()
	 * @method \string requireComment()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetComment()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetComment()
	 * @method \string fillComment()
	 * @method \boolean getWorktimeSkipped()
	 * @method \Bitrix\Voximplant\Model\EO_Call setWorktimeSkipped(\boolean|\Bitrix\Main\DB\SqlExpression $worktimeSkipped)
	 * @method bool hasWorktimeSkipped()
	 * @method bool isWorktimeSkippedFilled()
	 * @method bool isWorktimeSkippedChanged()
	 * @method \boolean remindActualWorktimeSkipped()
	 * @method \boolean requireWorktimeSkipped()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetWorktimeSkipped()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetWorktimeSkipped()
	 * @method \boolean fillWorktimeSkipped()
	 * @method \string getSipHeaders()
	 * @method \Bitrix\Voximplant\Model\EO_Call setSipHeaders(\string|\Bitrix\Main\DB\SqlExpression $sipHeaders)
	 * @method bool hasSipHeaders()
	 * @method bool isSipHeadersFilled()
	 * @method bool isSipHeadersChanged()
	 * @method \string remindActualSipHeaders()
	 * @method \string requireSipHeaders()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetSipHeaders()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetSipHeaders()
	 * @method \string fillSipHeaders()
	 * @method \string getGatheredDigits()
	 * @method \Bitrix\Voximplant\Model\EO_Call setGatheredDigits(\string|\Bitrix\Main\DB\SqlExpression $gatheredDigits)
	 * @method bool hasGatheredDigits()
	 * @method bool isGatheredDigitsFilled()
	 * @method bool isGatheredDigitsChanged()
	 * @method \string remindActualGatheredDigits()
	 * @method \string requireGatheredDigits()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetGatheredDigits()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetGatheredDigits()
	 * @method \string fillGatheredDigits()
	 * @method \string getParentCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Call setParentCallId(\string|\Bitrix\Main\DB\SqlExpression $parentCallId)
	 * @method bool hasParentCallId()
	 * @method bool isParentCallIdFilled()
	 * @method bool isParentCallIdChanged()
	 * @method \string remindActualParentCallId()
	 * @method \string requireParentCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetParentCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetParentCallId()
	 * @method \string fillParentCallId()
	 * @method \Bitrix\Main\Type\DateTime getLastPing()
	 * @method \Bitrix\Voximplant\Model\EO_Call setLastPing(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastPing)
	 * @method bool hasLastPing()
	 * @method bool isLastPingFilled()
	 * @method bool isLastPingChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastPing()
	 * @method \Bitrix\Main\Type\DateTime requireLastPing()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetLastPing()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetLastPing()
	 * @method \Bitrix\Main\Type\DateTime fillLastPing()
	 * @method \string getExecutionGraph()
	 * @method \Bitrix\Voximplant\Model\EO_Call setExecutionGraph(\string|\Bitrix\Main\DB\SqlExpression $executionGraph)
	 * @method bool hasExecutionGraph()
	 * @method bool isExecutionGraphFilled()
	 * @method bool isExecutionGraphChanged()
	 * @method \string remindActualExecutionGraph()
	 * @method \string requireExecutionGraph()
	 * @method \Bitrix\Voximplant\Model\EO_Call resetExecutionGraph()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetExecutionGraph()
	 * @method \string fillExecutionGraph()
	 * @method \Bitrix\Voximplant\Model\EO_Queue getQueue()
	 * @method \Bitrix\Voximplant\Model\EO_Queue remindActualQueue()
	 * @method \Bitrix\Voximplant\Model\EO_Queue requireQueue()
	 * @method \Bitrix\Voximplant\Model\EO_Call setQueue(\Bitrix\Voximplant\Model\EO_Queue $object)
	 * @method \Bitrix\Voximplant\Model\EO_Call resetQueue()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetQueue()
	 * @method bool hasQueue()
	 * @method bool isQueueFilled()
	 * @method bool isQueueChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Queue fillQueue()
	 * @method \Bitrix\Voximplant\EO_Config getConfig()
	 * @method \Bitrix\Voximplant\EO_Config remindActualConfig()
	 * @method \Bitrix\Voximplant\EO_Config requireConfig()
	 * @method \Bitrix\Voximplant\Model\EO_Call setConfig(\Bitrix\Voximplant\EO_Config $object)
	 * @method \Bitrix\Voximplant\Model\EO_Call resetConfig()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetConfig()
	 * @method bool hasConfig()
	 * @method bool isConfigFilled()
	 * @method bool isConfigChanged()
	 * @method \Bitrix\Voximplant\EO_Config fillConfig()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine getExternalLine()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine remindActualExternalLine()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine requireExternalLine()
	 * @method \Bitrix\Voximplant\Model\EO_Call setExternalLine(\Bitrix\Voximplant\Model\EO_ExternalLine $object)
	 * @method \Bitrix\Voximplant\Model\EO_Call resetExternalLine()
	 * @method \Bitrix\Voximplant\Model\EO_Call unsetExternalLine()
	 * @method bool hasExternalLine()
	 * @method bool isExternalLineFilled()
	 * @method bool isExternalLineChanged()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine fillExternalLine()
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
	 * @method \Bitrix\Voximplant\Model\EO_Call set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_Call reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_Call unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_Call wakeUp($data)
	 */
	class EO_Call {
		/* @var \Bitrix\Voximplant\Model\CallTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\CallTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Call_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getPortalUserIdList()
	 * @method \int[] fillPortalUserId()
	 * @method \string[] getCallIdList()
	 * @method \string[] fillCallId()
	 * @method \string[] getExternalCallIdList()
	 * @method \string[] fillExternalCallId()
	 * @method \string[] getIncomingList()
	 * @method \string[] fillIncoming()
	 * @method \string[] getCallerIdList()
	 * @method \string[] fillCallerId()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \boolean[] getCrmList()
	 * @method \boolean[] fillCrm()
	 * @method \int[] getCrmActivityIdList()
	 * @method \int[] fillCrmActivityId()
	 * @method \int[] getCrmCallListList()
	 * @method \int[] fillCrmCallList()
	 * @method \string[] getCrmBindingsList()
	 * @method \string[] fillCrmBindings()
	 * @method \string[] getAccessUrlList()
	 * @method \string[] fillAccessUrl()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \int[] getRestAppIdList()
	 * @method \int[] fillRestAppId()
	 * @method \int[] getExternalLineIdList()
	 * @method \int[] fillExternalLineId()
	 * @method \string[] getPortalNumberList()
	 * @method \string[] fillPortalNumber()
	 * @method \string[] getStageList()
	 * @method \string[] fillStage()
	 * @method \int[] getIvrActionIdList()
	 * @method \int[] fillIvrActionId()
	 * @method \int[] getQueueIdList()
	 * @method \int[] fillQueueId()
	 * @method \string[] getQueueHistoryList()
	 * @method \string[] fillQueueHistory()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \string[] getCallbackParametersList()
	 * @method \string[] fillCallbackParameters()
	 * @method \string[] getCommentList()
	 * @method \string[] fillComment()
	 * @method \boolean[] getWorktimeSkippedList()
	 * @method \boolean[] fillWorktimeSkipped()
	 * @method \string[] getSipHeadersList()
	 * @method \string[] fillSipHeaders()
	 * @method \string[] getGatheredDigitsList()
	 * @method \string[] fillGatheredDigits()
	 * @method \string[] getParentCallIdList()
	 * @method \string[] fillParentCallId()
	 * @method \Bitrix\Main\Type\DateTime[] getLastPingList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLastPing()
	 * @method \string[] getExecutionGraphList()
	 * @method \string[] fillExecutionGraph()
	 * @method \Bitrix\Voximplant\Model\EO_Queue[] getQueueList()
	 * @method \Bitrix\Voximplant\Model\EO_Call_Collection getQueueCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Queue_Collection fillQueue()
	 * @method \Bitrix\Voximplant\EO_Config[] getConfigList()
	 * @method \Bitrix\Voximplant\Model\EO_Call_Collection getConfigCollection()
	 * @method \Bitrix\Voximplant\EO_Config_Collection fillConfig()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine[] getExternalLineList()
	 * @method \Bitrix\Voximplant\Model\EO_Call_Collection getExternalLineCollection()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine_Collection fillExternalLine()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_Call $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_Call $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Call getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Call[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_Call $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_Call_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_Call current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Call_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\CallTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\CallTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Call_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_Call fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Call_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Call_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Call fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Call_Collection fetchCollection()
	 */
	class EO_Call_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Call createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_Call_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Call wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_Call_Collection wakeUpCollection($rows)
	 */
	class EO_Call_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\CallCrmEntityTable:voximplant\lib\model\callcrmentity.php:8210837b4213920b0ef6202aa0e5729c */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_CallCrmEntity
	 * @see \Bitrix\Voximplant\Model\CallCrmEntityTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getCallId()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity setCallId(\string|\Bitrix\Main\DB\SqlExpression $callId)
	 * @method bool hasCallId()
	 * @method bool isCallIdFilled()
	 * @method bool isCallIdChanged()
	 * @method \string getEntityType()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \int getEntityId()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \boolean getIsPrimary()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity setIsPrimary(\boolean|\Bitrix\Main\DB\SqlExpression $isPrimary)
	 * @method bool hasIsPrimary()
	 * @method bool isIsPrimaryFilled()
	 * @method bool isIsPrimaryChanged()
	 * @method \boolean remindActualIsPrimary()
	 * @method \boolean requireIsPrimary()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity resetIsPrimary()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity unsetIsPrimary()
	 * @method \boolean fillIsPrimary()
	 * @method \boolean getIsCreated()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity setIsCreated(\boolean|\Bitrix\Main\DB\SqlExpression $isCreated)
	 * @method bool hasIsCreated()
	 * @method bool isIsCreatedFilled()
	 * @method bool isIsCreatedChanged()
	 * @method \boolean remindActualIsCreated()
	 * @method \boolean requireIsCreated()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity resetIsCreated()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity unsetIsCreated()
	 * @method \boolean fillIsCreated()
	 * @method \Bitrix\Voximplant\EO_Statistic getCall()
	 * @method \Bitrix\Voximplant\EO_Statistic remindActualCall()
	 * @method \Bitrix\Voximplant\EO_Statistic requireCall()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity setCall(\Bitrix\Voximplant\EO_Statistic $object)
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity resetCall()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity unsetCall()
	 * @method bool hasCall()
	 * @method bool isCallFilled()
	 * @method bool isCallChanged()
	 * @method \Bitrix\Voximplant\EO_Statistic fillCall()
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
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_CallCrmEntity wakeUp($data)
	 */
	class EO_CallCrmEntity {
		/* @var \Bitrix\Voximplant\Model\CallCrmEntityTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\CallCrmEntityTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_CallCrmEntity_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getCallIdList()
	 * @method \string[] getEntityTypeList()
	 * @method \int[] getEntityIdList()
	 * @method \boolean[] getIsPrimaryList()
	 * @method \boolean[] fillIsPrimary()
	 * @method \boolean[] getIsCreatedList()
	 * @method \boolean[] fillIsCreated()
	 * @method \Bitrix\Voximplant\EO_Statistic[] getCallList()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection getCallCollection()
	 * @method \Bitrix\Voximplant\EO_Statistic_Collection fillCall()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_CallCrmEntity $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_CallCrmEntity $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_CallCrmEntity $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_CallCrmEntity_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\CallCrmEntityTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\CallCrmEntityTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CallCrmEntity_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CallCrmEntity_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection fetchCollection()
	 */
	class EO_CallCrmEntity_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection wakeUpCollection($rows)
	 */
	class EO_CallCrmEntity_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\CallerIdTable:voximplant\lib\model\callerid.php:6b2c099146885e3f3cca01f804f9fa4c */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_CallerId
	 * @see \Bitrix\Voximplant\Model\CallerIdTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getNumber()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId setNumber(\string|\Bitrix\Main\DB\SqlExpression $number)
	 * @method bool hasNumber()
	 * @method bool isNumberFilled()
	 * @method bool isNumberChanged()
	 * @method \string remindActualNumber()
	 * @method \string requireNumber()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId resetNumber()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId unsetNumber()
	 * @method \string fillNumber()
	 * @method \boolean getVerified()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId setVerified(\boolean|\Bitrix\Main\DB\SqlExpression $verified)
	 * @method bool hasVerified()
	 * @method bool isVerifiedFilled()
	 * @method bool isVerifiedChanged()
	 * @method \boolean remindActualVerified()
	 * @method \boolean requireVerified()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId resetVerified()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId unsetVerified()
	 * @method \boolean fillVerified()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId resetDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\Date getVerifiedUntil()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId setVerifiedUntil(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $verifiedUntil)
	 * @method bool hasVerifiedUntil()
	 * @method bool isVerifiedUntilFilled()
	 * @method bool isVerifiedUntilChanged()
	 * @method \Bitrix\Main\Type\Date remindActualVerifiedUntil()
	 * @method \Bitrix\Main\Type\Date requireVerifiedUntil()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId resetVerifiedUntil()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId unsetVerifiedUntil()
	 * @method \Bitrix\Main\Type\Date fillVerifiedUntil()
	 * @method \int getConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId resetConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId unsetConfigId()
	 * @method \int fillConfigId()
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
	 * @method \Bitrix\Voximplant\Model\EO_CallerId set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_CallerId reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_CallerId unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_CallerId wakeUp($data)
	 */
	class EO_CallerId {
		/* @var \Bitrix\Voximplant\Model\CallerIdTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\CallerIdTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_CallerId_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNumberList()
	 * @method \string[] fillNumber()
	 * @method \boolean[] getVerifiedList()
	 * @method \boolean[] fillVerified()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\Date[] getVerifiedUntilList()
	 * @method \Bitrix\Main\Type\Date[] fillVerifiedUntil()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_CallerId $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_CallerId $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_CallerId getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_CallerId[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_CallerId $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_CallerId_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_CallerId current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_CallerId_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\CallerIdTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\CallerIdTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CallerId_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CallerId_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_CallerId fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId_Collection fetchCollection()
	 */
	class EO_CallerId_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_CallerId createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_CallerId_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_CallerId wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_CallerId_Collection wakeUpCollection($rows)
	 */
	class EO_CallerId_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\CallUserTable:voximplant\lib\model\calluser.php:051049f7ded27784bf4b35072f4901a3 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_CallUser
	 * @see \Bitrix\Voximplant\Model\CallUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getCallId()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser setCallId(\string|\Bitrix\Main\DB\SqlExpression $callId)
	 * @method bool hasCallId()
	 * @method bool isCallIdFilled()
	 * @method bool isCallIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \string getRole()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser setRole(\string|\Bitrix\Main\DB\SqlExpression $role)
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \string remindActualRole()
	 * @method \string requireRole()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser resetRole()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser unsetRole()
	 * @method \string fillRole()
	 * @method \string getStatus()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser resetStatus()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser unsetStatus()
	 * @method \string fillStatus()
	 * @method \string getDevice()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser setDevice(\string|\Bitrix\Main\DB\SqlExpression $device)
	 * @method bool hasDevice()
	 * @method bool isDeviceFilled()
	 * @method bool isDeviceChanged()
	 * @method \string remindActualDevice()
	 * @method \string requireDevice()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser resetDevice()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser unsetDevice()
	 * @method \string fillDevice()
	 * @method \Bitrix\Main\Type\DateTime getInserted()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser setInserted(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $inserted)
	 * @method bool hasInserted()
	 * @method bool isInsertedFilled()
	 * @method bool isInsertedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualInserted()
	 * @method \Bitrix\Main\Type\DateTime requireInserted()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser resetInserted()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser unsetInserted()
	 * @method \Bitrix\Main\Type\DateTime fillInserted()
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
	 * @method \Bitrix\Voximplant\Model\EO_CallUser set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_CallUser reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_CallUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_CallUser wakeUp($data)
	 */
	class EO_CallUser {
		/* @var \Bitrix\Voximplant\Model\CallUserTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\CallUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_CallUser_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getCallIdList()
	 * @method \int[] getUserIdList()
	 * @method \string[] getRoleList()
	 * @method \string[] fillRole()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \string[] getDeviceList()
	 * @method \string[] fillDevice()
	 * @method \Bitrix\Main\Type\DateTime[] getInsertedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillInserted()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_CallUser $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_CallUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_CallUser getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_CallUser[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_CallUser $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_CallUser_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_CallUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_CallUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\CallUserTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\CallUserTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CallUser_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CallUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_CallUser fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser_Collection fetchCollection()
	 */
	class EO_CallUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_CallUser createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_CallUser_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_CallUser wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_CallUser_Collection wakeUpCollection($rows)
	 */
	class EO_CallUser_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\ExternalLineTable:voximplant\lib\model\externalline.php:3908ee82901a6172ba6b0e78fb12cfd4 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_ExternalLine
	 * @see \Bitrix\Voximplant\Model\ExternalLineTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getType()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine resetType()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unsetType()
	 * @method \string fillType()
	 * @method \string getNumber()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setNumber(\string|\Bitrix\Main\DB\SqlExpression $number)
	 * @method bool hasNumber()
	 * @method bool isNumberFilled()
	 * @method bool isNumberChanged()
	 * @method \string remindActualNumber()
	 * @method \string requireNumber()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine resetNumber()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unsetNumber()
	 * @method \string fillNumber()
	 * @method \string getNormalizedNumber()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setNormalizedNumber(\string|\Bitrix\Main\DB\SqlExpression $normalizedNumber)
	 * @method bool hasNormalizedNumber()
	 * @method bool isNormalizedNumberFilled()
	 * @method bool isNormalizedNumberChanged()
	 * @method \string remindActualNormalizedNumber()
	 * @method \string requireNormalizedNumber()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine resetNormalizedNumber()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unsetNormalizedNumber()
	 * @method \string fillNormalizedNumber()
	 * @method \string getName()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine resetName()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unsetName()
	 * @method \string fillName()
	 * @method \int getRestAppId()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setRestAppId(\int|\Bitrix\Main\DB\SqlExpression $restAppId)
	 * @method bool hasRestAppId()
	 * @method bool isRestAppIdFilled()
	 * @method bool isRestAppIdChanged()
	 * @method \int remindActualRestAppId()
	 * @method \int requireRestAppId()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine resetRestAppId()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unsetRestAppId()
	 * @method \int fillRestAppId()
	 * @method \int getSipId()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setSipId(\int|\Bitrix\Main\DB\SqlExpression $sipId)
	 * @method bool hasSipId()
	 * @method bool isSipIdFilled()
	 * @method bool isSipIdChanged()
	 * @method \int remindActualSipId()
	 * @method \int requireSipId()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine resetSipId()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unsetSipId()
	 * @method \int fillSipId()
	 * @method \boolean getIsManual()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setIsManual(\boolean|\Bitrix\Main\DB\SqlExpression $isManual)
	 * @method bool hasIsManual()
	 * @method bool isIsManualFilled()
	 * @method bool isIsManualChanged()
	 * @method \boolean remindActualIsManual()
	 * @method \boolean requireIsManual()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine resetIsManual()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unsetIsManual()
	 * @method \boolean fillIsManual()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine resetDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Voximplant\EO_Sip getSip()
	 * @method \Bitrix\Voximplant\EO_Sip remindActualSip()
	 * @method \Bitrix\Voximplant\EO_Sip requireSip()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine setSip(\Bitrix\Voximplant\EO_Sip $object)
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine resetSip()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unsetSip()
	 * @method bool hasSip()
	 * @method bool isSipFilled()
	 * @method bool isSipChanged()
	 * @method \Bitrix\Voximplant\EO_Sip fillSip()
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
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_ExternalLine wakeUp($data)
	 */
	class EO_ExternalLine {
		/* @var \Bitrix\Voximplant\Model\ExternalLineTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\ExternalLineTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_ExternalLine_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getNumberList()
	 * @method \string[] fillNumber()
	 * @method \string[] getNormalizedNumberList()
	 * @method \string[] fillNormalizedNumber()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getRestAppIdList()
	 * @method \int[] fillRestAppId()
	 * @method \int[] getSipIdList()
	 * @method \int[] fillSipId()
	 * @method \boolean[] getIsManualList()
	 * @method \boolean[] fillIsManual()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Voximplant\EO_Sip[] getSipList()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine_Collection getSipCollection()
	 * @method \Bitrix\Voximplant\EO_Sip_Collection fillSip()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_ExternalLine $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_ExternalLine $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_ExternalLine $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_ExternalLine_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ExternalLine_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\ExternalLineTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\ExternalLineTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExternalLine_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ExternalLine_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine_Collection fetchCollection()
	 */
	class EO_ExternalLine_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_ExternalLine_Collection wakeUpCollection($rows)
	 */
	class EO_ExternalLine_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\IvrTable:voximplant\lib\model\ivr.php:f82dbdcd97e4a080f0898b1089261773 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Ivr
	 * @see \Bitrix\Voximplant\Model\IvrTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr resetName()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr unsetName()
	 * @method \string fillName()
	 * @method \int getFirstItemId()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr setFirstItemId(\int|\Bitrix\Main\DB\SqlExpression $firstItemId)
	 * @method bool hasFirstItemId()
	 * @method bool isFirstItemIdFilled()
	 * @method bool isFirstItemIdChanged()
	 * @method \int remindActualFirstItemId()
	 * @method \int requireFirstItemId()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr resetFirstItemId()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr unsetFirstItemId()
	 * @method \int fillFirstItemId()
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
	 * @method \Bitrix\Voximplant\Model\EO_Ivr set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_Ivr reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_Ivr unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_Ivr wakeUp($data)
	 */
	class EO_Ivr {
		/* @var \Bitrix\Voximplant\Model\IvrTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\IvrTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Ivr_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getFirstItemIdList()
	 * @method \int[] fillFirstItemId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_Ivr $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_Ivr $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Ivr getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Ivr[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_Ivr $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_Ivr_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_Ivr current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Ivr_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\IvrTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\IvrTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Ivr_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Ivr_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Ivr fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr_Collection fetchCollection()
	 */
	class EO_Ivr_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Ivr createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_Ivr_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_Ivr_Collection wakeUpCollection($rows)
	 */
	class EO_Ivr_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\IvrActionTable:voximplant\lib\model\ivraction.php:d5acf17507d7b8c321b78ef256020a2d */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_IvrAction
	 * @see \Bitrix\Voximplant\Model\IvrActionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getItemId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction setItemId(\int|\Bitrix\Main\DB\SqlExpression $itemId)
	 * @method bool hasItemId()
	 * @method bool isItemIdFilled()
	 * @method bool isItemIdChanged()
	 * @method \int remindActualItemId()
	 * @method \int requireItemId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction resetItemId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction unsetItemId()
	 * @method \int fillItemId()
	 * @method \string getDigit()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction setDigit(\string|\Bitrix\Main\DB\SqlExpression $digit)
	 * @method bool hasDigit()
	 * @method bool isDigitFilled()
	 * @method bool isDigitChanged()
	 * @method \string remindActualDigit()
	 * @method \string requireDigit()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction resetDigit()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction unsetDigit()
	 * @method \string fillDigit()
	 * @method \string getAction()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction resetAction()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction unsetAction()
	 * @method \string fillAction()
	 * @method \string getParameters()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction setParameters(\string|\Bitrix\Main\DB\SqlExpression $parameters)
	 * @method bool hasParameters()
	 * @method bool isParametersFilled()
	 * @method bool isParametersChanged()
	 * @method \string remindActualParameters()
	 * @method \string requireParameters()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction resetParameters()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction unsetParameters()
	 * @method \string fillParameters()
	 * @method \string getLeadFields()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction setLeadFields(\string|\Bitrix\Main\DB\SqlExpression $leadFields)
	 * @method bool hasLeadFields()
	 * @method bool isLeadFieldsFilled()
	 * @method bool isLeadFieldsChanged()
	 * @method \string remindActualLeadFields()
	 * @method \string requireLeadFields()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction resetLeadFields()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction unsetLeadFields()
	 * @method \string fillLeadFields()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem getItem()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem remindActualItem()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem requireItem()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction setItem(\Bitrix\Voximplant\Model\EO_IvrItem $object)
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction resetItem()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction unsetItem()
	 * @method bool hasItem()
	 * @method bool isItemFilled()
	 * @method bool isItemChanged()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem fillItem()
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
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_IvrAction wakeUp($data)
	 */
	class EO_IvrAction {
		/* @var \Bitrix\Voximplant\Model\IvrActionTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\IvrActionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_IvrAction_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getItemIdList()
	 * @method \int[] fillItemId()
	 * @method \string[] getDigitList()
	 * @method \string[] fillDigit()
	 * @method \string[] getActionList()
	 * @method \string[] fillAction()
	 * @method \string[] getParametersList()
	 * @method \string[] fillParameters()
	 * @method \string[] getLeadFieldsList()
	 * @method \string[] fillLeadFields()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem[] getItemList()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction_Collection getItemCollection()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem_Collection fillItem()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_IvrAction $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_IvrAction $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_IvrAction $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_IvrAction_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_IvrAction_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\IvrActionTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\IvrActionTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_IvrAction_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_IvrAction_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction_Collection fetchCollection()
	 */
	class EO_IvrAction_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_IvrAction_Collection wakeUpCollection($rows)
	 */
	class EO_IvrAction_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\IvrItemTable:voximplant\lib\model\ivritem.php:f4de50b860a0f9db8b1095070ed2d6f6 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_IvrItem
	 * @see \Bitrix\Voximplant\Model\IvrItemTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getCode()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetCode()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetCode()
	 * @method \string fillCode()
	 * @method \int getIvrId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setIvrId(\int|\Bitrix\Main\DB\SqlExpression $ivrId)
	 * @method bool hasIvrId()
	 * @method bool isIvrIdFilled()
	 * @method bool isIvrIdChanged()
	 * @method \int remindActualIvrId()
	 * @method \int requireIvrId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetIvrId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetIvrId()
	 * @method \int fillIvrId()
	 * @method \string getName()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetName()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetName()
	 * @method \string fillName()
	 * @method \string getType()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetType()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetType()
	 * @method \string fillType()
	 * @method \string getUrl()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setUrl(\string|\Bitrix\Main\DB\SqlExpression $url)
	 * @method bool hasUrl()
	 * @method bool isUrlFilled()
	 * @method bool isUrlChanged()
	 * @method \string remindActualUrl()
	 * @method \string requireUrl()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetUrl()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetUrl()
	 * @method \string fillUrl()
	 * @method \string getMessage()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setMessage(\string|\Bitrix\Main\DB\SqlExpression $message)
	 * @method bool hasMessage()
	 * @method bool isMessageFilled()
	 * @method bool isMessageChanged()
	 * @method \string remindActualMessage()
	 * @method \string requireMessage()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetMessage()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetMessage()
	 * @method \string fillMessage()
	 * @method \int getFileId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetFileId()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetFileId()
	 * @method \int fillFileId()
	 * @method \int getTimeout()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setTimeout(\int|\Bitrix\Main\DB\SqlExpression $timeout)
	 * @method bool hasTimeout()
	 * @method bool isTimeoutFilled()
	 * @method bool isTimeoutChanged()
	 * @method \int remindActualTimeout()
	 * @method \int requireTimeout()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetTimeout()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetTimeout()
	 * @method \int fillTimeout()
	 * @method \string getTimeoutAction()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setTimeoutAction(\string|\Bitrix\Main\DB\SqlExpression $timeoutAction)
	 * @method bool hasTimeoutAction()
	 * @method bool isTimeoutActionFilled()
	 * @method bool isTimeoutActionChanged()
	 * @method \string remindActualTimeoutAction()
	 * @method \string requireTimeoutAction()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetTimeoutAction()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetTimeoutAction()
	 * @method \string fillTimeoutAction()
	 * @method \string getTtsVoice()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setTtsVoice(\string|\Bitrix\Main\DB\SqlExpression $ttsVoice)
	 * @method bool hasTtsVoice()
	 * @method bool isTtsVoiceFilled()
	 * @method bool isTtsVoiceChanged()
	 * @method \string remindActualTtsVoice()
	 * @method \string requireTtsVoice()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetTtsVoice()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetTtsVoice()
	 * @method \string fillTtsVoice()
	 * @method \string getTtsSpeed()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setTtsSpeed(\string|\Bitrix\Main\DB\SqlExpression $ttsSpeed)
	 * @method bool hasTtsSpeed()
	 * @method bool isTtsSpeedFilled()
	 * @method bool isTtsSpeedChanged()
	 * @method \string remindActualTtsSpeed()
	 * @method \string requireTtsSpeed()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetTtsSpeed()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetTtsSpeed()
	 * @method \string fillTtsSpeed()
	 * @method \string getTtsVolume()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setTtsVolume(\string|\Bitrix\Main\DB\SqlExpression $ttsVolume)
	 * @method bool hasTtsVolume()
	 * @method bool isTtsVolumeFilled()
	 * @method bool isTtsVolumeChanged()
	 * @method \string remindActualTtsVolume()
	 * @method \string requireTtsVolume()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetTtsVolume()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetTtsVolume()
	 * @method \string fillTtsVolume()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr getIvr()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr remindActualIvr()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr requireIvr()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem setIvr(\Bitrix\Voximplant\Model\EO_Ivr $object)
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem resetIvr()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unsetIvr()
	 * @method bool hasIvr()
	 * @method bool isIvrFilled()
	 * @method bool isIvrChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr fillIvr()
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
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_IvrItem wakeUp($data)
	 */
	class EO_IvrItem {
		/* @var \Bitrix\Voximplant\Model\IvrItemTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\IvrItemTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_IvrItem_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \int[] getIvrIdList()
	 * @method \int[] fillIvrId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getUrlList()
	 * @method \string[] fillUrl()
	 * @method \string[] getMessageList()
	 * @method \string[] fillMessage()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \int[] getTimeoutList()
	 * @method \int[] fillTimeout()
	 * @method \string[] getTimeoutActionList()
	 * @method \string[] fillTimeoutAction()
	 * @method \string[] getTtsVoiceList()
	 * @method \string[] fillTtsVoice()
	 * @method \string[] getTtsSpeedList()
	 * @method \string[] fillTtsSpeed()
	 * @method \string[] getTtsVolumeList()
	 * @method \string[] fillTtsVolume()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr[] getIvrList()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem_Collection getIvrCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Ivr_Collection fillIvr()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_IvrItem $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_IvrItem $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_IvrItem $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_IvrItem_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_IvrItem_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\IvrItemTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\IvrItemTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_IvrItem_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_IvrItem_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem_Collection fetchCollection()
	 */
	class EO_IvrItem_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_IvrItem_Collection wakeUpCollection($rows)
	 */
	class EO_IvrItem_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\LineAccessTable:voximplant\lib\model\lineaccess.php:52cc06d9decb1716a4067ee031cabf30 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_LineAccess
	 * @see \Bitrix\Voximplant\Model\LineAccessTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess resetConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess resetAccessCode()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \Bitrix\Voximplant\EO_Config getConfig()
	 * @method \Bitrix\Voximplant\EO_Config remindActualConfig()
	 * @method \Bitrix\Voximplant\EO_Config requireConfig()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess setConfig(\Bitrix\Voximplant\EO_Config $object)
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess resetConfig()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess unsetConfig()
	 * @method bool hasConfig()
	 * @method bool isConfigFilled()
	 * @method bool isConfigChanged()
	 * @method \Bitrix\Voximplant\EO_Config fillConfig()
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
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_LineAccess wakeUp($data)
	 */
	class EO_LineAccess {
		/* @var \Bitrix\Voximplant\Model\LineAccessTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\LineAccessTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_LineAccess_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \Bitrix\Voximplant\EO_Config[] getConfigList()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess_Collection getConfigCollection()
	 * @method \Bitrix\Voximplant\EO_Config_Collection fillConfig()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_LineAccess $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_LineAccess $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_LineAccess $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_LineAccess_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_LineAccess_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\LineAccessTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\LineAccessTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_LineAccess_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_LineAccess_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess_Collection fetchCollection()
	 */
	class EO_LineAccess_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_LineAccess_Collection wakeUpCollection($rows)
	 */
	class EO_LineAccess_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\NumberTable:voximplant\lib\model\number.php:656dbcf75d7e2be59540a16ba5fc0ced */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Number
	 * @see \Bitrix\Voximplant\Model\NumberTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_Number setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Number setNumber(\string|\Bitrix\Main\DB\SqlExpression $number)
	 * @method bool hasNumber()
	 * @method bool isNumberFilled()
	 * @method bool isNumberChanged()
	 * @method \string remindActualNumber()
	 * @method \string requireNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Number resetNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetNumber()
	 * @method \string fillNumber()
	 * @method \string getName()
	 * @method \Bitrix\Voximplant\Model\EO_Number setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Voximplant\Model\EO_Number resetName()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetName()
	 * @method \string fillName()
	 * @method \string getCountryCode()
	 * @method \Bitrix\Voximplant\Model\EO_Number setCountryCode(\string|\Bitrix\Main\DB\SqlExpression $countryCode)
	 * @method bool hasCountryCode()
	 * @method bool isCountryCodeFilled()
	 * @method bool isCountryCodeChanged()
	 * @method \string remindActualCountryCode()
	 * @method \string requireCountryCode()
	 * @method \Bitrix\Voximplant\Model\EO_Number resetCountryCode()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetCountryCode()
	 * @method \string fillCountryCode()
	 * @method \boolean getVerified()
	 * @method \Bitrix\Voximplant\Model\EO_Number setVerified(\boolean|\Bitrix\Main\DB\SqlExpression $verified)
	 * @method bool hasVerified()
	 * @method bool isVerifiedFilled()
	 * @method bool isVerifiedChanged()
	 * @method \boolean remindActualVerified()
	 * @method \boolean requireVerified()
	 * @method \Bitrix\Voximplant\Model\EO_Number resetVerified()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetVerified()
	 * @method \boolean fillVerified()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_Number setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_Number resetDateCreate()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \int getSubscriptionId()
	 * @method \Bitrix\Voximplant\Model\EO_Number setSubscriptionId(\int|\Bitrix\Main\DB\SqlExpression $subscriptionId)
	 * @method bool hasSubscriptionId()
	 * @method bool isSubscriptionIdFilled()
	 * @method bool isSubscriptionIdChanged()
	 * @method \int remindActualSubscriptionId()
	 * @method \int requireSubscriptionId()
	 * @method \Bitrix\Voximplant\Model\EO_Number resetSubscriptionId()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetSubscriptionId()
	 * @method \int fillSubscriptionId()
	 * @method \int getConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_Number setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_Number resetConfigId()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \boolean getToDelete()
	 * @method \Bitrix\Voximplant\Model\EO_Number setToDelete(\boolean|\Bitrix\Main\DB\SqlExpression $toDelete)
	 * @method bool hasToDelete()
	 * @method bool isToDeleteFilled()
	 * @method bool isToDeleteChanged()
	 * @method \boolean remindActualToDelete()
	 * @method \boolean requireToDelete()
	 * @method \Bitrix\Voximplant\Model\EO_Number resetToDelete()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetToDelete()
	 * @method \boolean fillToDelete()
	 * @method \Bitrix\Main\Type\DateTime getDateDelete()
	 * @method \Bitrix\Voximplant\Model\EO_Number setDateDelete(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateDelete)
	 * @method bool hasDateDelete()
	 * @method bool isDateDeleteFilled()
	 * @method bool isDateDeleteChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateDelete()
	 * @method \Bitrix\Main\Type\DateTime requireDateDelete()
	 * @method \Bitrix\Voximplant\Model\EO_Number resetDateDelete()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetDateDelete()
	 * @method \Bitrix\Main\Type\DateTime fillDateDelete()
	 * @method \string getCnt()
	 * @method \string remindActualCnt()
	 * @method \string requireCnt()
	 * @method bool hasCnt()
	 * @method bool isCntFilled()
	 * @method \Bitrix\Voximplant\Model\EO_Number unsetCnt()
	 * @method \string fillCnt()
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
	 * @method \Bitrix\Voximplant\Model\EO_Number set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_Number reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_Number unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_Number wakeUp($data)
	 */
	class EO_Number {
		/* @var \Bitrix\Voximplant\Model\NumberTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\NumberTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Number_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNumberList()
	 * @method \string[] fillNumber()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getCountryCodeList()
	 * @method \string[] fillCountryCode()
	 * @method \boolean[] getVerifiedList()
	 * @method \boolean[] fillVerified()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \int[] getSubscriptionIdList()
	 * @method \int[] fillSubscriptionId()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \boolean[] getToDeleteList()
	 * @method \boolean[] fillToDelete()
	 * @method \Bitrix\Main\Type\DateTime[] getDateDeleteList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateDelete()
	 * @method \string[] getCntList()
	 * @method \string[] fillCnt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_Number $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_Number $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Number getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Number[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_Number $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_Number_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_Number current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Number_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\NumberTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\NumberTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Number_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_Number fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Number_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Number_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Number fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Number_Collection fetchCollection()
	 */
	class EO_Number_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Number createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_Number_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Number wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_Number_Collection wakeUpCollection($rows)
	 */
	class EO_Number_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\QueueTable:voximplant\lib\model\queue.php:ecedb303c2f43b7047cb96751e99dc98 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Queue
	 * @see \Bitrix\Voximplant\Model\QueueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_Queue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Voximplant\Model\EO_Queue setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Voximplant\Model\EO_Queue resetName()
	 * @method \Bitrix\Voximplant\Model\EO_Queue unsetName()
	 * @method \string fillName()
	 * @method \string getType()
	 * @method \Bitrix\Voximplant\Model\EO_Queue setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Voximplant\Model\EO_Queue resetType()
	 * @method \Bitrix\Voximplant\Model\EO_Queue unsetType()
	 * @method \string fillType()
	 * @method \int getWaitTime()
	 * @method \Bitrix\Voximplant\Model\EO_Queue setWaitTime(\int|\Bitrix\Main\DB\SqlExpression $waitTime)
	 * @method bool hasWaitTime()
	 * @method bool isWaitTimeFilled()
	 * @method bool isWaitTimeChanged()
	 * @method \int remindActualWaitTime()
	 * @method \int requireWaitTime()
	 * @method \Bitrix\Voximplant\Model\EO_Queue resetWaitTime()
	 * @method \Bitrix\Voximplant\Model\EO_Queue unsetWaitTime()
	 * @method \int fillWaitTime()
	 * @method \string getNoAnswerRule()
	 * @method \Bitrix\Voximplant\Model\EO_Queue setNoAnswerRule(\string|\Bitrix\Main\DB\SqlExpression $noAnswerRule)
	 * @method bool hasNoAnswerRule()
	 * @method bool isNoAnswerRuleFilled()
	 * @method bool isNoAnswerRuleChanged()
	 * @method \string remindActualNoAnswerRule()
	 * @method \string requireNoAnswerRule()
	 * @method \Bitrix\Voximplant\Model\EO_Queue resetNoAnswerRule()
	 * @method \Bitrix\Voximplant\Model\EO_Queue unsetNoAnswerRule()
	 * @method \string fillNoAnswerRule()
	 * @method \int getNextQueueId()
	 * @method \Bitrix\Voximplant\Model\EO_Queue setNextQueueId(\int|\Bitrix\Main\DB\SqlExpression $nextQueueId)
	 * @method bool hasNextQueueId()
	 * @method bool isNextQueueIdFilled()
	 * @method bool isNextQueueIdChanged()
	 * @method \int remindActualNextQueueId()
	 * @method \int requireNextQueueId()
	 * @method \Bitrix\Voximplant\Model\EO_Queue resetNextQueueId()
	 * @method \Bitrix\Voximplant\Model\EO_Queue unsetNextQueueId()
	 * @method \int fillNextQueueId()
	 * @method \string getForwardNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Queue setForwardNumber(\string|\Bitrix\Main\DB\SqlExpression $forwardNumber)
	 * @method bool hasForwardNumber()
	 * @method bool isForwardNumberFilled()
	 * @method bool isForwardNumberChanged()
	 * @method \string remindActualForwardNumber()
	 * @method \string requireForwardNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Queue resetForwardNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Queue unsetForwardNumber()
	 * @method \string fillForwardNumber()
	 * @method \boolean getAllowIntercept()
	 * @method \Bitrix\Voximplant\Model\EO_Queue setAllowIntercept(\boolean|\Bitrix\Main\DB\SqlExpression $allowIntercept)
	 * @method bool hasAllowIntercept()
	 * @method bool isAllowInterceptFilled()
	 * @method bool isAllowInterceptChanged()
	 * @method \boolean remindActualAllowIntercept()
	 * @method \boolean requireAllowIntercept()
	 * @method \Bitrix\Voximplant\Model\EO_Queue resetAllowIntercept()
	 * @method \Bitrix\Voximplant\Model\EO_Queue unsetAllowIntercept()
	 * @method \boolean fillAllowIntercept()
	 * @method \string getPhoneNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Queue setPhoneNumber(\string|\Bitrix\Main\DB\SqlExpression $phoneNumber)
	 * @method bool hasPhoneNumber()
	 * @method bool isPhoneNumberFilled()
	 * @method bool isPhoneNumberChanged()
	 * @method \string remindActualPhoneNumber()
	 * @method \string requirePhoneNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Queue resetPhoneNumber()
	 * @method \Bitrix\Voximplant\Model\EO_Queue unsetPhoneNumber()
	 * @method \string fillPhoneNumber()
	 * @method \string getCnt()
	 * @method \string remindActualCnt()
	 * @method \string requireCnt()
	 * @method bool hasCnt()
	 * @method bool isCntFilled()
	 * @method \Bitrix\Voximplant\Model\EO_Queue unsetCnt()
	 * @method \string fillCnt()
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
	 * @method \Bitrix\Voximplant\Model\EO_Queue set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_Queue reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_Queue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_Queue wakeUp($data)
	 */
	class EO_Queue {
		/* @var \Bitrix\Voximplant\Model\QueueTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\QueueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Queue_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getWaitTimeList()
	 * @method \int[] fillWaitTime()
	 * @method \string[] getNoAnswerRuleList()
	 * @method \string[] fillNoAnswerRule()
	 * @method \int[] getNextQueueIdList()
	 * @method \int[] fillNextQueueId()
	 * @method \string[] getForwardNumberList()
	 * @method \string[] fillForwardNumber()
	 * @method \boolean[] getAllowInterceptList()
	 * @method \boolean[] fillAllowIntercept()
	 * @method \string[] getPhoneNumberList()
	 * @method \string[] fillPhoneNumber()
	 * @method \string[] getCntList()
	 * @method \string[] fillCnt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_Queue $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_Queue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Queue getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Queue[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_Queue $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_Queue_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_Queue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Queue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\QueueTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\QueueTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Queue_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_Queue fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Queue_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Queue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Queue fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Queue_Collection fetchCollection()
	 */
	class EO_Queue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Queue createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_Queue_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Queue wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_Queue_Collection wakeUpCollection($rows)
	 */
	class EO_Queue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\QueueUserTable:voximplant\lib\model\queueuser.php:9ffb487268d8f3700f98c2ccdc23178e */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_QueueUser
	 * @see \Bitrix\Voximplant\Model\QueueUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getQueueId()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser setQueueId(\int|\Bitrix\Main\DB\SqlExpression $queueId)
	 * @method bool hasQueueId()
	 * @method bool isQueueIdFilled()
	 * @method bool isQueueIdChanged()
	 * @method \int remindActualQueueId()
	 * @method \int requireQueueId()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser resetQueueId()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser unsetQueueId()
	 * @method \int fillQueueId()
	 * @method \int getUserId()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser resetUserId()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getStatus()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser resetStatus()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser unsetStatus()
	 * @method \string fillStatus()
	 * @method \Bitrix\Main\Type\DateTime getLastActivityDate()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser setLastActivityDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastActivityDate)
	 * @method bool hasLastActivityDate()
	 * @method bool isLastActivityDateFilled()
	 * @method bool isLastActivityDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime requireLastActivityDate()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser resetLastActivityDate()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser unsetLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime fillLastActivityDate()
	 * @method \Bitrix\Voximplant\Model\EO_User getUser()
	 * @method \Bitrix\Voximplant\Model\EO_User remindActualUser()
	 * @method \Bitrix\Voximplant\Model\EO_User requireUser()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser setUser(\Bitrix\Voximplant\Model\EO_User $object)
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser resetUser()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Voximplant\Model\EO_User fillUser()
	 * @method \Bitrix\Voximplant\Model\EO_Queue getQueue()
	 * @method \Bitrix\Voximplant\Model\EO_Queue remindActualQueue()
	 * @method \Bitrix\Voximplant\Model\EO_Queue requireQueue()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser setQueue(\Bitrix\Voximplant\Model\EO_Queue $object)
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser resetQueue()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser unsetQueue()
	 * @method bool hasQueue()
	 * @method bool isQueueFilled()
	 * @method bool isQueueChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Queue fillQueue()
	 * @method \string getIsOnlineCustom()
	 * @method \string remindActualIsOnlineCustom()
	 * @method \string requireIsOnlineCustom()
	 * @method bool hasIsOnlineCustom()
	 * @method bool isIsOnlineCustomFilled()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser unsetIsOnlineCustom()
	 * @method \string fillIsOnlineCustom()
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
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_QueueUser wakeUp($data)
	 */
	class EO_QueueUser {
		/* @var \Bitrix\Voximplant\Model\QueueUserTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\QueueUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_QueueUser_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getQueueIdList()
	 * @method \int[] fillQueueId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \Bitrix\Main\Type\DateTime[] getLastActivityDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLastActivityDate()
	 * @method \Bitrix\Voximplant\Model\EO_User[] getUserList()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser_Collection getUserCollection()
	 * @method \Bitrix\Voximplant\Model\EO_User_Collection fillUser()
	 * @method \Bitrix\Voximplant\Model\EO_Queue[] getQueueList()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser_Collection getQueueCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Queue_Collection fillQueue()
	 * @method \string[] getIsOnlineCustomList()
	 * @method \string[] fillIsOnlineCustom()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_QueueUser $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_QueueUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_QueueUser $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_QueueUser_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_QueueUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\QueueUserTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\QueueUserTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_QueueUser_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_QueueUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser_Collection fetchCollection()
	 */
	class EO_QueueUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_QueueUser_Collection wakeUpCollection($rows)
	 */
	class EO_QueueUser_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\RoleTable:voximplant\lib\model\role.php:a7f36d45e80214d64f68130097d3f3bf */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Role
	 * @see \Bitrix\Voximplant\Model\RoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_Role setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Voximplant\Model\EO_Role setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Voximplant\Model\EO_Role resetName()
	 * @method \Bitrix\Voximplant\Model\EO_Role unsetName()
	 * @method \string fillName()
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
	 * @method \Bitrix\Voximplant\Model\EO_Role set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_Role reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_Role unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_Role wakeUp($data)
	 */
	class EO_Role {
		/* @var \Bitrix\Voximplant\Model\RoleTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\RoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Role_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_Role $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_Role $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Role getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Role[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_Role $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_Role_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_Role current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Role_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\RoleTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\RoleTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Role_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_Role fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Role_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Role_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Role fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Role_Collection fetchCollection()
	 */
	class EO_Role_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Role createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_Role_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Role wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_Role_Collection wakeUpCollection($rows)
	 */
	class EO_Role_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\RoleAccessTable:voximplant\lib\model\roleaccess.php:31a47153a2b79f9fb3ba183a1aa42be9 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_RoleAccess
	 * @see \Bitrix\Voximplant\Model\RoleAccessTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess resetRoleId()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess resetAccessCode()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \Bitrix\Voximplant\Model\EO_Role getRole()
	 * @method \Bitrix\Voximplant\Model\EO_Role remindActualRole()
	 * @method \Bitrix\Voximplant\Model\EO_Role requireRole()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess setRole(\Bitrix\Voximplant\Model\EO_Role $object)
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess resetRole()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Role fillRole()
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
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_RoleAccess wakeUp($data)
	 */
	class EO_RoleAccess {
		/* @var \Bitrix\Voximplant\Model\RoleAccessTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\RoleAccessTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_RoleAccess_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \Bitrix\Voximplant\Model\EO_Role[] getRoleList()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess_Collection getRoleCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Role_Collection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_RoleAccess $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_RoleAccess $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_RoleAccess $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_RoleAccess_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RoleAccess_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\RoleAccessTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\RoleAccessTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RoleAccess_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RoleAccess_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess_Collection fetchCollection()
	 */
	class EO_RoleAccess_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess_Collection wakeUpCollection($rows)
	 */
	class EO_RoleAccess_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\RolePermissionTable:voximplant\lib\model\rolepermission.php:51213339c3b047105d1321f65bbdadfa */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_RolePermission
	 * @see \Bitrix\Voximplant\Model\RolePermissionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission resetRoleId()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getEntity()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission setEntity(\string|\Bitrix\Main\DB\SqlExpression $entity)
	 * @method bool hasEntity()
	 * @method bool isEntityFilled()
	 * @method bool isEntityChanged()
	 * @method \string remindActualEntity()
	 * @method \string requireEntity()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission resetEntity()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission unsetEntity()
	 * @method \string fillEntity()
	 * @method \string getAction()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission resetAction()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission unsetAction()
	 * @method \string fillAction()
	 * @method \string getPermission()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission setPermission(\string|\Bitrix\Main\DB\SqlExpression $permission)
	 * @method bool hasPermission()
	 * @method bool isPermissionFilled()
	 * @method bool isPermissionChanged()
	 * @method \string remindActualPermission()
	 * @method \string requirePermission()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission resetPermission()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission unsetPermission()
	 * @method \string fillPermission()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess getRoleAccess()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess remindActualRoleAccess()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess requireRoleAccess()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission setRoleAccess(\Bitrix\Voximplant\Model\EO_RoleAccess $object)
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission resetRoleAccess()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission unsetRoleAccess()
	 * @method bool hasRoleAccess()
	 * @method bool isRoleAccessFilled()
	 * @method bool isRoleAccessChanged()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess fillRoleAccess()
	 * @method \Bitrix\Voximplant\Model\EO_Role getRole()
	 * @method \Bitrix\Voximplant\Model\EO_Role remindActualRole()
	 * @method \Bitrix\Voximplant\Model\EO_Role requireRole()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission setRole(\Bitrix\Voximplant\Model\EO_Role $object)
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission resetRole()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Role fillRole()
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
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_RolePermission wakeUp($data)
	 */
	class EO_RolePermission {
		/* @var \Bitrix\Voximplant\Model\RolePermissionTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\RolePermissionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_RolePermission_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getEntityList()
	 * @method \string[] fillEntity()
	 * @method \string[] getActionList()
	 * @method \string[] fillAction()
	 * @method \string[] getPermissionList()
	 * @method \string[] fillPermission()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess[] getRoleAccessList()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission_Collection getRoleAccessCollection()
	 * @method \Bitrix\Voximplant\Model\EO_RoleAccess_Collection fillRoleAccess()
	 * @method \Bitrix\Voximplant\Model\EO_Role[] getRoleList()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission_Collection getRoleCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Role_Collection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_RolePermission $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_RolePermission $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_RolePermission $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_RolePermission_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RolePermission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\RolePermissionTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\RolePermissionTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RolePermission_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RolePermission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission_Collection fetchCollection()
	 */
	class EO_RolePermission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_RolePermission_Collection wakeUpCollection($rows)
	 */
	class EO_RolePermission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\StatisticIndexTable:voximplant\lib\model\statisticindex.php:e4b3fd52ade6297bbd67c0418f3e31b9 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_StatisticIndex
	 * @see \Bitrix\Voximplant\Model\StatisticIndexTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getStatisticId()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex setStatisticId(\int|\Bitrix\Main\DB\SqlExpression $statisticId)
	 * @method bool hasStatisticId()
	 * @method bool isStatisticIdFilled()
	 * @method bool isStatisticIdChanged()
	 * @method \string getContent()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex setContent(\string|\Bitrix\Main\DB\SqlExpression $content)
	 * @method bool hasContent()
	 * @method bool isContentFilled()
	 * @method bool isContentChanged()
	 * @method \string remindActualContent()
	 * @method \string requireContent()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex resetContent()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex unsetContent()
	 * @method \string fillContent()
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
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_StatisticIndex wakeUp($data)
	 */
	class EO_StatisticIndex {
		/* @var \Bitrix\Voximplant\Model\StatisticIndexTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\StatisticIndexTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_StatisticIndex_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getStatisticIdList()
	 * @method \string[] getContentList()
	 * @method \string[] fillContent()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_StatisticIndex $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_StatisticIndex $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_StatisticIndex $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_StatisticIndex_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_StatisticIndex_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\StatisticIndexTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\StatisticIndexTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_StatisticIndex_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_StatisticIndex_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex_Collection fetchCollection()
	 */
	class EO_StatisticIndex_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex_Collection wakeUpCollection($rows)
	 */
	class EO_StatisticIndex_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\StatisticMissedTable:voximplant\lib\model\statisticmissed.php:107526b4342e768d10ea1a6d1388b6ff */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_StatisticMissed
	 * @see \Bitrix\Voximplant\Model\StatisticMissedTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getCallStartDate()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed setCallStartDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $callStartDate)
	 * @method bool hasCallStartDate()
	 * @method bool isCallStartDateFilled()
	 * @method bool isCallStartDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCallStartDate()
	 * @method \Bitrix\Main\Type\DateTime requireCallStartDate()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed resetCallStartDate()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed unsetCallStartDate()
	 * @method \Bitrix\Main\Type\DateTime fillCallStartDate()
	 * @method \string getPhoneNumber()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed setPhoneNumber(\string|\Bitrix\Main\DB\SqlExpression $phoneNumber)
	 * @method bool hasPhoneNumber()
	 * @method bool isPhoneNumberFilled()
	 * @method bool isPhoneNumberChanged()
	 * @method \string remindActualPhoneNumber()
	 * @method \string requirePhoneNumber()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed resetPhoneNumber()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed unsetPhoneNumber()
	 * @method \string fillPhoneNumber()
	 * @method \int getPortalUserId()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed setPortalUserId(\int|\Bitrix\Main\DB\SqlExpression $portalUserId)
	 * @method bool hasPortalUserId()
	 * @method bool isPortalUserIdFilled()
	 * @method bool isPortalUserIdChanged()
	 * @method \int remindActualPortalUserId()
	 * @method \int requirePortalUserId()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed resetPortalUserId()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed unsetPortalUserId()
	 * @method \int fillPortalUserId()
	 * @method \int getCallbackId()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed setCallbackId(\int|\Bitrix\Main\DB\SqlExpression $callbackId)
	 * @method bool hasCallbackId()
	 * @method bool isCallbackIdFilled()
	 * @method bool isCallbackIdChanged()
	 * @method \int remindActualCallbackId()
	 * @method \int requireCallbackId()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed resetCallbackId()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed unsetCallbackId()
	 * @method \int fillCallbackId()
	 * @method \Bitrix\Main\Type\DateTime getCallbackCallStartDate()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed setCallbackCallStartDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $callbackCallStartDate)
	 * @method bool hasCallbackCallStartDate()
	 * @method bool isCallbackCallStartDateFilled()
	 * @method bool isCallbackCallStartDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCallbackCallStartDate()
	 * @method \Bitrix\Main\Type\DateTime requireCallbackCallStartDate()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed resetCallbackCallStartDate()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed unsetCallbackCallStartDate()
	 * @method \Bitrix\Main\Type\DateTime fillCallbackCallStartDate()
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
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_StatisticMissed wakeUp($data)
	 */
	class EO_StatisticMissed {
		/* @var \Bitrix\Voximplant\Model\StatisticMissedTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\StatisticMissedTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_StatisticMissed_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getCallStartDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCallStartDate()
	 * @method \string[] getPhoneNumberList()
	 * @method \string[] fillPhoneNumber()
	 * @method \int[] getPortalUserIdList()
	 * @method \int[] fillPortalUserId()
	 * @method \int[] getCallbackIdList()
	 * @method \int[] fillCallbackId()
	 * @method \Bitrix\Main\Type\DateTime[] getCallbackCallStartDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCallbackCallStartDate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_StatisticMissed $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_StatisticMissed $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_StatisticMissed $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_StatisticMissed_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_StatisticMissed_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\StatisticMissedTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\StatisticMissedTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_StatisticMissed_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_StatisticMissed_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed_Collection fetchCollection()
	 */
	class EO_StatisticMissed_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_StatisticMissed_Collection wakeUpCollection($rows)
	 */
	class EO_StatisticMissed_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\TranscriptTable:voximplant\lib\model\transcript.php:bba45bb012c47e0762cecff00f6272a2 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Transcript
	 * @see \Bitrix\Voximplant\Model\TranscriptTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \float getCost()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript setCost(\float|\Bitrix\Main\DB\SqlExpression $cost)
	 * @method bool hasCost()
	 * @method bool isCostFilled()
	 * @method bool isCostChanged()
	 * @method \float remindActualCost()
	 * @method \float requireCost()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript resetCost()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript unsetCost()
	 * @method \float fillCost()
	 * @method \string getCostCurrency()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript setCostCurrency(\string|\Bitrix\Main\DB\SqlExpression $costCurrency)
	 * @method bool hasCostCurrency()
	 * @method bool isCostCurrencyFilled()
	 * @method bool isCostCurrencyChanged()
	 * @method \string remindActualCostCurrency()
	 * @method \string requireCostCurrency()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript resetCostCurrency()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript unsetCostCurrency()
	 * @method \string fillCostCurrency()
	 * @method \int getSessionId()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript resetSessionId()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \string getCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript setCallId(\string|\Bitrix\Main\DB\SqlExpression $callId)
	 * @method bool hasCallId()
	 * @method bool isCallIdFilled()
	 * @method bool isCallIdChanged()
	 * @method \string remindActualCallId()
	 * @method \string requireCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript resetCallId()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript unsetCallId()
	 * @method \string fillCallId()
	 * @method \string getContent()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript setContent(\string|\Bitrix\Main\DB\SqlExpression $content)
	 * @method bool hasContent()
	 * @method bool isContentFilled()
	 * @method bool isContentChanged()
	 * @method \string remindActualContent()
	 * @method \string requireContent()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript resetContent()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript unsetContent()
	 * @method \string fillContent()
	 * @method \string getUrl()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript setUrl(\string|\Bitrix\Main\DB\SqlExpression $url)
	 * @method bool hasUrl()
	 * @method bool isUrlFilled()
	 * @method bool isUrlChanged()
	 * @method \string remindActualUrl()
	 * @method \string requireUrl()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript resetUrl()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript unsetUrl()
	 * @method \string fillUrl()
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
	 * @method \Bitrix\Voximplant\Model\EO_Transcript set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_Transcript reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_Transcript unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_Transcript wakeUp($data)
	 */
	class EO_Transcript {
		/* @var \Bitrix\Voximplant\Model\TranscriptTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\TranscriptTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_Transcript_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \float[] getCostList()
	 * @method \float[] fillCost()
	 * @method \string[] getCostCurrencyList()
	 * @method \string[] fillCostCurrency()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \string[] getCallIdList()
	 * @method \string[] fillCallId()
	 * @method \string[] getContentList()
	 * @method \string[] fillContent()
	 * @method \string[] getUrlList()
	 * @method \string[] fillUrl()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_Transcript $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_Transcript $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Transcript getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_Transcript[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_Transcript $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_Transcript_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_Transcript current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Transcript_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\TranscriptTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\TranscriptTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Transcript_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Transcript_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Transcript fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript_Collection fetchCollection()
	 */
	class EO_Transcript_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_Transcript createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_Transcript_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_Transcript_Collection wakeUpCollection($rows)
	 */
	class EO_Transcript_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\TranscriptLineTable:voximplant\lib\model\transcriptline.php:ffd3bde790e50d3e843c410fc84c2cf1 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_TranscriptLine
	 * @see \Bitrix\Voximplant\Model\TranscriptLineTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTranscriptId()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine setTranscriptId(\string|\Bitrix\Main\DB\SqlExpression $transcriptId)
	 * @method bool hasTranscriptId()
	 * @method bool isTranscriptIdFilled()
	 * @method bool isTranscriptIdChanged()
	 * @method \string remindActualTranscriptId()
	 * @method \string requireTranscriptId()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine resetTranscriptId()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine unsetTranscriptId()
	 * @method \string fillTranscriptId()
	 * @method \string getSide()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine setSide(\string|\Bitrix\Main\DB\SqlExpression $side)
	 * @method bool hasSide()
	 * @method bool isSideFilled()
	 * @method bool isSideChanged()
	 * @method \string remindActualSide()
	 * @method \string requireSide()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine resetSide()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine unsetSide()
	 * @method \string fillSide()
	 * @method \int getStartTime()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine setStartTime(\int|\Bitrix\Main\DB\SqlExpression $startTime)
	 * @method bool hasStartTime()
	 * @method bool isStartTimeFilled()
	 * @method bool isStartTimeChanged()
	 * @method \int remindActualStartTime()
	 * @method \int requireStartTime()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine resetStartTime()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine unsetStartTime()
	 * @method \int fillStartTime()
	 * @method \int getStopTime()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine setStopTime(\int|\Bitrix\Main\DB\SqlExpression $stopTime)
	 * @method bool hasStopTime()
	 * @method bool isStopTimeFilled()
	 * @method bool isStopTimeChanged()
	 * @method \int remindActualStopTime()
	 * @method \int requireStopTime()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine resetStopTime()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine unsetStopTime()
	 * @method \int fillStopTime()
	 * @method \string getMessage()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine setMessage(\string|\Bitrix\Main\DB\SqlExpression $message)
	 * @method bool hasMessage()
	 * @method bool isMessageFilled()
	 * @method bool isMessageChanged()
	 * @method \string remindActualMessage()
	 * @method \string requireMessage()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine resetMessage()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine unsetMessage()
	 * @method \string fillMessage()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript getTranscript()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript remindActualTranscript()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript requireTranscript()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine setTranscript(\Bitrix\Voximplant\Model\EO_Transcript $object)
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine resetTranscript()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine unsetTranscript()
	 * @method bool hasTranscript()
	 * @method bool isTranscriptFilled()
	 * @method bool isTranscriptChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript fillTranscript()
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
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_TranscriptLine wakeUp($data)
	 */
	class EO_TranscriptLine {
		/* @var \Bitrix\Voximplant\Model\TranscriptLineTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\TranscriptLineTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_TranscriptLine_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTranscriptIdList()
	 * @method \string[] fillTranscriptId()
	 * @method \string[] getSideList()
	 * @method \string[] fillSide()
	 * @method \int[] getStartTimeList()
	 * @method \int[] fillStartTime()
	 * @method \int[] getStopTimeList()
	 * @method \int[] fillStopTime()
	 * @method \string[] getMessageList()
	 * @method \string[] fillMessage()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript[] getTranscriptList()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine_Collection getTranscriptCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript_Collection fillTranscript()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_TranscriptLine $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_TranscriptLine $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_TranscriptLine $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_TranscriptLine_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TranscriptLine_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\TranscriptLineTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\TranscriptLineTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TranscriptLine_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TranscriptLine_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine_Collection fetchCollection()
	 */
	class EO_TranscriptLine_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_TranscriptLine_Collection wakeUpCollection($rows)
	 */
	class EO_TranscriptLine_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\Model\UserTable:voximplant\lib\model\user.php:d656f801f5f209e2816f5cbfa8ce7b34 */
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_User
	 * @see \Bitrix\Voximplant\Model\UserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\Model\EO_User setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getLogin()
	 * @method \Bitrix\Voximplant\Model\EO_User setLogin(\string|\Bitrix\Main\DB\SqlExpression $login)
	 * @method bool hasLogin()
	 * @method bool isLoginFilled()
	 * @method bool isLoginChanged()
	 * @method \string remindActualLogin()
	 * @method \string requireLogin()
	 * @method \Bitrix\Voximplant\Model\EO_User resetLogin()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetLogin()
	 * @method \string fillLogin()
	 * @method \string getPassword()
	 * @method \Bitrix\Voximplant\Model\EO_User setPassword(\string|\Bitrix\Main\DB\SqlExpression $password)
	 * @method bool hasPassword()
	 * @method bool isPasswordFilled()
	 * @method bool isPasswordChanged()
	 * @method \string remindActualPassword()
	 * @method \string requirePassword()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPassword()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPassword()
	 * @method \string fillPassword()
	 * @method \string getEmail()
	 * @method \Bitrix\Voximplant\Model\EO_User setEmail(\string|\Bitrix\Main\DB\SqlExpression $email)
	 * @method bool hasEmail()
	 * @method bool isEmailFilled()
	 * @method bool isEmailChanged()
	 * @method \string remindActualEmail()
	 * @method \string requireEmail()
	 * @method \Bitrix\Voximplant\Model\EO_User resetEmail()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetEmail()
	 * @method \string fillEmail()
	 * @method \boolean getActive()
	 * @method \Bitrix\Voximplant\Model\EO_User setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Voximplant\Model\EO_User resetActive()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetActive()
	 * @method \boolean fillActive()
	 * @method \boolean getBlocked()
	 * @method \Bitrix\Voximplant\Model\EO_User setBlocked(\boolean|\Bitrix\Main\DB\SqlExpression $blocked)
	 * @method bool hasBlocked()
	 * @method bool isBlockedFilled()
	 * @method bool isBlockedChanged()
	 * @method \boolean remindActualBlocked()
	 * @method \boolean requireBlocked()
	 * @method \Bitrix\Voximplant\Model\EO_User resetBlocked()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetBlocked()
	 * @method \boolean fillBlocked()
	 * @method \Bitrix\Main\Type\DateTime getDateRegister()
	 * @method \Bitrix\Voximplant\Model\EO_User setDateRegister(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateRegister)
	 * @method bool hasDateRegister()
	 * @method bool isDateRegisterFilled()
	 * @method bool isDateRegisterChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateRegister()
	 * @method \Bitrix\Main\Type\DateTime requireDateRegister()
	 * @method \Bitrix\Voximplant\Model\EO_User resetDateRegister()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetDateRegister()
	 * @method \Bitrix\Main\Type\DateTime fillDateRegister()
	 * @method \Bitrix\Main\Type\DateTime getDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime requireDateRegShort()
	 * @method bool hasDateRegShort()
	 * @method bool isDateRegShortFilled()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime fillDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime getLastLogin()
	 * @method \Bitrix\Voximplant\Model\EO_User setLastLogin(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastLogin)
	 * @method bool hasLastLogin()
	 * @method bool isLastLoginFilled()
	 * @method bool isLastLoginChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastLogin()
	 * @method \Bitrix\Main\Type\DateTime requireLastLogin()
	 * @method \Bitrix\Voximplant\Model\EO_User resetLastLogin()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetLastLogin()
	 * @method \Bitrix\Main\Type\DateTime fillLastLogin()
	 * @method \Bitrix\Main\Type\DateTime getLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime requireLastLoginShort()
	 * @method bool hasLastLoginShort()
	 * @method bool isLastLoginShortFilled()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime fillLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime getLastActivityDate()
	 * @method \Bitrix\Voximplant\Model\EO_User setLastActivityDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastActivityDate)
	 * @method bool hasLastActivityDate()
	 * @method bool isLastActivityDateFilled()
	 * @method bool isLastActivityDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime requireLastActivityDate()
	 * @method \Bitrix\Voximplant\Model\EO_User resetLastActivityDate()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime fillLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Voximplant\Model\EO_User setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Voximplant\Model\EO_User resetTimestampX()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \string getName()
	 * @method \Bitrix\Voximplant\Model\EO_User setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Voximplant\Model\EO_User resetName()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetName()
	 * @method \string fillName()
	 * @method \string getSecondName()
	 * @method \Bitrix\Voximplant\Model\EO_User setSecondName(\string|\Bitrix\Main\DB\SqlExpression $secondName)
	 * @method bool hasSecondName()
	 * @method bool isSecondNameFilled()
	 * @method bool isSecondNameChanged()
	 * @method \string remindActualSecondName()
	 * @method \string requireSecondName()
	 * @method \Bitrix\Voximplant\Model\EO_User resetSecondName()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetSecondName()
	 * @method \string fillSecondName()
	 * @method \string getLastName()
	 * @method \Bitrix\Voximplant\Model\EO_User setLastName(\string|\Bitrix\Main\DB\SqlExpression $lastName)
	 * @method bool hasLastName()
	 * @method bool isLastNameFilled()
	 * @method bool isLastNameChanged()
	 * @method \string remindActualLastName()
	 * @method \string requireLastName()
	 * @method \Bitrix\Voximplant\Model\EO_User resetLastName()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetLastName()
	 * @method \string fillLastName()
	 * @method \string getTitle()
	 * @method \Bitrix\Voximplant\Model\EO_User setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Voximplant\Model\EO_User resetTitle()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getExternalAuthId()
	 * @method \Bitrix\Voximplant\Model\EO_User setExternalAuthId(\string|\Bitrix\Main\DB\SqlExpression $externalAuthId)
	 * @method bool hasExternalAuthId()
	 * @method bool isExternalAuthIdFilled()
	 * @method bool isExternalAuthIdChanged()
	 * @method \string remindActualExternalAuthId()
	 * @method \string requireExternalAuthId()
	 * @method \Bitrix\Voximplant\Model\EO_User resetExternalAuthId()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetExternalAuthId()
	 * @method \string fillExternalAuthId()
	 * @method \string getXmlId()
	 * @method \Bitrix\Voximplant\Model\EO_User setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Voximplant\Model\EO_User resetXmlId()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \string getBxUserId()
	 * @method \Bitrix\Voximplant\Model\EO_User setBxUserId(\string|\Bitrix\Main\DB\SqlExpression $bxUserId)
	 * @method bool hasBxUserId()
	 * @method bool isBxUserIdFilled()
	 * @method bool isBxUserIdChanged()
	 * @method \string remindActualBxUserId()
	 * @method \string requireBxUserId()
	 * @method \Bitrix\Voximplant\Model\EO_User resetBxUserId()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetBxUserId()
	 * @method \string fillBxUserId()
	 * @method \string getConfirmCode()
	 * @method \Bitrix\Voximplant\Model\EO_User setConfirmCode(\string|\Bitrix\Main\DB\SqlExpression $confirmCode)
	 * @method bool hasConfirmCode()
	 * @method bool isConfirmCodeFilled()
	 * @method bool isConfirmCodeChanged()
	 * @method \string remindActualConfirmCode()
	 * @method \string requireConfirmCode()
	 * @method \Bitrix\Voximplant\Model\EO_User resetConfirmCode()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetConfirmCode()
	 * @method \string fillConfirmCode()
	 * @method \string getLid()
	 * @method \Bitrix\Voximplant\Model\EO_User setLid(\string|\Bitrix\Main\DB\SqlExpression $lid)
	 * @method bool hasLid()
	 * @method bool isLidFilled()
	 * @method bool isLidChanged()
	 * @method \string remindActualLid()
	 * @method \string requireLid()
	 * @method \Bitrix\Voximplant\Model\EO_User resetLid()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetLid()
	 * @method \string fillLid()
	 * @method \string getLanguageId()
	 * @method \Bitrix\Voximplant\Model\EO_User setLanguageId(\string|\Bitrix\Main\DB\SqlExpression $languageId)
	 * @method bool hasLanguageId()
	 * @method bool isLanguageIdFilled()
	 * @method bool isLanguageIdChanged()
	 * @method \string remindActualLanguageId()
	 * @method \string requireLanguageId()
	 * @method \Bitrix\Voximplant\Model\EO_User resetLanguageId()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetLanguageId()
	 * @method \string fillLanguageId()
	 * @method \string getTimeZone()
	 * @method \Bitrix\Voximplant\Model\EO_User setTimeZone(\string|\Bitrix\Main\DB\SqlExpression $timeZone)
	 * @method bool hasTimeZone()
	 * @method bool isTimeZoneFilled()
	 * @method bool isTimeZoneChanged()
	 * @method \string remindActualTimeZone()
	 * @method \string requireTimeZone()
	 * @method \Bitrix\Voximplant\Model\EO_User resetTimeZone()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetTimeZone()
	 * @method \string fillTimeZone()
	 * @method \int getTimeZoneOffset()
	 * @method \Bitrix\Voximplant\Model\EO_User setTimeZoneOffset(\int|\Bitrix\Main\DB\SqlExpression $timeZoneOffset)
	 * @method bool hasTimeZoneOffset()
	 * @method bool isTimeZoneOffsetFilled()
	 * @method bool isTimeZoneOffsetChanged()
	 * @method \int remindActualTimeZoneOffset()
	 * @method \int requireTimeZoneOffset()
	 * @method \Bitrix\Voximplant\Model\EO_User resetTimeZoneOffset()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetTimeZoneOffset()
	 * @method \int fillTimeZoneOffset()
	 * @method \string getPersonalProfession()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalProfession(\string|\Bitrix\Main\DB\SqlExpression $personalProfession)
	 * @method bool hasPersonalProfession()
	 * @method bool isPersonalProfessionFilled()
	 * @method bool isPersonalProfessionChanged()
	 * @method \string remindActualPersonalProfession()
	 * @method \string requirePersonalProfession()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalProfession()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalProfession()
	 * @method \string fillPersonalProfession()
	 * @method \string getPersonalPhone()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalPhone(\string|\Bitrix\Main\DB\SqlExpression $personalPhone)
	 * @method bool hasPersonalPhone()
	 * @method bool isPersonalPhoneFilled()
	 * @method bool isPersonalPhoneChanged()
	 * @method \string remindActualPersonalPhone()
	 * @method \string requirePersonalPhone()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalPhone()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalPhone()
	 * @method \string fillPersonalPhone()
	 * @method \string getPersonalMobile()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalMobile(\string|\Bitrix\Main\DB\SqlExpression $personalMobile)
	 * @method bool hasPersonalMobile()
	 * @method bool isPersonalMobileFilled()
	 * @method bool isPersonalMobileChanged()
	 * @method \string remindActualPersonalMobile()
	 * @method \string requirePersonalMobile()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalMobile()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalMobile()
	 * @method \string fillPersonalMobile()
	 * @method \string getPersonalWww()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalWww(\string|\Bitrix\Main\DB\SqlExpression $personalWww)
	 * @method bool hasPersonalWww()
	 * @method bool isPersonalWwwFilled()
	 * @method bool isPersonalWwwChanged()
	 * @method \string remindActualPersonalWww()
	 * @method \string requirePersonalWww()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalWww()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalWww()
	 * @method \string fillPersonalWww()
	 * @method \string getPersonalIcq()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalIcq(\string|\Bitrix\Main\DB\SqlExpression $personalIcq)
	 * @method bool hasPersonalIcq()
	 * @method bool isPersonalIcqFilled()
	 * @method bool isPersonalIcqChanged()
	 * @method \string remindActualPersonalIcq()
	 * @method \string requirePersonalIcq()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalIcq()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalIcq()
	 * @method \string fillPersonalIcq()
	 * @method \string getPersonalFax()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalFax(\string|\Bitrix\Main\DB\SqlExpression $personalFax)
	 * @method bool hasPersonalFax()
	 * @method bool isPersonalFaxFilled()
	 * @method bool isPersonalFaxChanged()
	 * @method \string remindActualPersonalFax()
	 * @method \string requirePersonalFax()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalFax()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalFax()
	 * @method \string fillPersonalFax()
	 * @method \string getPersonalPager()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalPager(\string|\Bitrix\Main\DB\SqlExpression $personalPager)
	 * @method bool hasPersonalPager()
	 * @method bool isPersonalPagerFilled()
	 * @method bool isPersonalPagerChanged()
	 * @method \string remindActualPersonalPager()
	 * @method \string requirePersonalPager()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalPager()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalPager()
	 * @method \string fillPersonalPager()
	 * @method \string getPersonalStreet()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalStreet(\string|\Bitrix\Main\DB\SqlExpression $personalStreet)
	 * @method bool hasPersonalStreet()
	 * @method bool isPersonalStreetFilled()
	 * @method bool isPersonalStreetChanged()
	 * @method \string remindActualPersonalStreet()
	 * @method \string requirePersonalStreet()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalStreet()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalStreet()
	 * @method \string fillPersonalStreet()
	 * @method \string getPersonalMailbox()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalMailbox(\string|\Bitrix\Main\DB\SqlExpression $personalMailbox)
	 * @method bool hasPersonalMailbox()
	 * @method bool isPersonalMailboxFilled()
	 * @method bool isPersonalMailboxChanged()
	 * @method \string remindActualPersonalMailbox()
	 * @method \string requirePersonalMailbox()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalMailbox()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalMailbox()
	 * @method \string fillPersonalMailbox()
	 * @method \string getPersonalCity()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalCity(\string|\Bitrix\Main\DB\SqlExpression $personalCity)
	 * @method bool hasPersonalCity()
	 * @method bool isPersonalCityFilled()
	 * @method bool isPersonalCityChanged()
	 * @method \string remindActualPersonalCity()
	 * @method \string requirePersonalCity()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalCity()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalCity()
	 * @method \string fillPersonalCity()
	 * @method \string getPersonalState()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalState(\string|\Bitrix\Main\DB\SqlExpression $personalState)
	 * @method bool hasPersonalState()
	 * @method bool isPersonalStateFilled()
	 * @method bool isPersonalStateChanged()
	 * @method \string remindActualPersonalState()
	 * @method \string requirePersonalState()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalState()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalState()
	 * @method \string fillPersonalState()
	 * @method \string getPersonalZip()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalZip(\string|\Bitrix\Main\DB\SqlExpression $personalZip)
	 * @method bool hasPersonalZip()
	 * @method bool isPersonalZipFilled()
	 * @method bool isPersonalZipChanged()
	 * @method \string remindActualPersonalZip()
	 * @method \string requirePersonalZip()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalZip()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalZip()
	 * @method \string fillPersonalZip()
	 * @method \string getPersonalCountry()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalCountry(\string|\Bitrix\Main\DB\SqlExpression $personalCountry)
	 * @method bool hasPersonalCountry()
	 * @method bool isPersonalCountryFilled()
	 * @method bool isPersonalCountryChanged()
	 * @method \string remindActualPersonalCountry()
	 * @method \string requirePersonalCountry()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalCountry()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalCountry()
	 * @method \string fillPersonalCountry()
	 * @method \Bitrix\Main\Type\Date getPersonalBirthday()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalBirthday(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $personalBirthday)
	 * @method bool hasPersonalBirthday()
	 * @method bool isPersonalBirthdayFilled()
	 * @method bool isPersonalBirthdayChanged()
	 * @method \Bitrix\Main\Type\Date remindActualPersonalBirthday()
	 * @method \Bitrix\Main\Type\Date requirePersonalBirthday()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalBirthday()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalBirthday()
	 * @method \Bitrix\Main\Type\Date fillPersonalBirthday()
	 * @method \string getPersonalGender()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalGender(\string|\Bitrix\Main\DB\SqlExpression $personalGender)
	 * @method bool hasPersonalGender()
	 * @method bool isPersonalGenderFilled()
	 * @method bool isPersonalGenderChanged()
	 * @method \string remindActualPersonalGender()
	 * @method \string requirePersonalGender()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalGender()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalGender()
	 * @method \string fillPersonalGender()
	 * @method \int getPersonalPhoto()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalPhoto(\int|\Bitrix\Main\DB\SqlExpression $personalPhoto)
	 * @method bool hasPersonalPhoto()
	 * @method bool isPersonalPhotoFilled()
	 * @method bool isPersonalPhotoChanged()
	 * @method \int remindActualPersonalPhoto()
	 * @method \int requirePersonalPhoto()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalPhoto()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalPhoto()
	 * @method \int fillPersonalPhoto()
	 * @method \string getPersonalNotes()
	 * @method \Bitrix\Voximplant\Model\EO_User setPersonalNotes(\string|\Bitrix\Main\DB\SqlExpression $personalNotes)
	 * @method bool hasPersonalNotes()
	 * @method bool isPersonalNotesFilled()
	 * @method bool isPersonalNotesChanged()
	 * @method \string remindActualPersonalNotes()
	 * @method \string requirePersonalNotes()
	 * @method \Bitrix\Voximplant\Model\EO_User resetPersonalNotes()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPersonalNotes()
	 * @method \string fillPersonalNotes()
	 * @method \string getWorkCompany()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkCompany(\string|\Bitrix\Main\DB\SqlExpression $workCompany)
	 * @method bool hasWorkCompany()
	 * @method bool isWorkCompanyFilled()
	 * @method bool isWorkCompanyChanged()
	 * @method \string remindActualWorkCompany()
	 * @method \string requireWorkCompany()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkCompany()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkCompany()
	 * @method \string fillWorkCompany()
	 * @method \string getWorkDepartment()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkDepartment(\string|\Bitrix\Main\DB\SqlExpression $workDepartment)
	 * @method bool hasWorkDepartment()
	 * @method bool isWorkDepartmentFilled()
	 * @method bool isWorkDepartmentChanged()
	 * @method \string remindActualWorkDepartment()
	 * @method \string requireWorkDepartment()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkDepartment()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkDepartment()
	 * @method \string fillWorkDepartment()
	 * @method \string getWorkPhone()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkPhone(\string|\Bitrix\Main\DB\SqlExpression $workPhone)
	 * @method bool hasWorkPhone()
	 * @method bool isWorkPhoneFilled()
	 * @method bool isWorkPhoneChanged()
	 * @method \string remindActualWorkPhone()
	 * @method \string requireWorkPhone()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkPhone()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkPhone()
	 * @method \string fillWorkPhone()
	 * @method \string getWorkPosition()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkPosition(\string|\Bitrix\Main\DB\SqlExpression $workPosition)
	 * @method bool hasWorkPosition()
	 * @method bool isWorkPositionFilled()
	 * @method bool isWorkPositionChanged()
	 * @method \string remindActualWorkPosition()
	 * @method \string requireWorkPosition()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkPosition()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkPosition()
	 * @method \string fillWorkPosition()
	 * @method \string getWorkWww()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkWww(\string|\Bitrix\Main\DB\SqlExpression $workWww)
	 * @method bool hasWorkWww()
	 * @method bool isWorkWwwFilled()
	 * @method bool isWorkWwwChanged()
	 * @method \string remindActualWorkWww()
	 * @method \string requireWorkWww()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkWww()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkWww()
	 * @method \string fillWorkWww()
	 * @method \string getWorkFax()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkFax(\string|\Bitrix\Main\DB\SqlExpression $workFax)
	 * @method bool hasWorkFax()
	 * @method bool isWorkFaxFilled()
	 * @method bool isWorkFaxChanged()
	 * @method \string remindActualWorkFax()
	 * @method \string requireWorkFax()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkFax()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkFax()
	 * @method \string fillWorkFax()
	 * @method \string getWorkPager()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkPager(\string|\Bitrix\Main\DB\SqlExpression $workPager)
	 * @method bool hasWorkPager()
	 * @method bool isWorkPagerFilled()
	 * @method bool isWorkPagerChanged()
	 * @method \string remindActualWorkPager()
	 * @method \string requireWorkPager()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkPager()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkPager()
	 * @method \string fillWorkPager()
	 * @method \string getWorkStreet()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkStreet(\string|\Bitrix\Main\DB\SqlExpression $workStreet)
	 * @method bool hasWorkStreet()
	 * @method bool isWorkStreetFilled()
	 * @method bool isWorkStreetChanged()
	 * @method \string remindActualWorkStreet()
	 * @method \string requireWorkStreet()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkStreet()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkStreet()
	 * @method \string fillWorkStreet()
	 * @method \string getWorkMailbox()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkMailbox(\string|\Bitrix\Main\DB\SqlExpression $workMailbox)
	 * @method bool hasWorkMailbox()
	 * @method bool isWorkMailboxFilled()
	 * @method bool isWorkMailboxChanged()
	 * @method \string remindActualWorkMailbox()
	 * @method \string requireWorkMailbox()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkMailbox()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkMailbox()
	 * @method \string fillWorkMailbox()
	 * @method \string getWorkCity()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkCity(\string|\Bitrix\Main\DB\SqlExpression $workCity)
	 * @method bool hasWorkCity()
	 * @method bool isWorkCityFilled()
	 * @method bool isWorkCityChanged()
	 * @method \string remindActualWorkCity()
	 * @method \string requireWorkCity()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkCity()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkCity()
	 * @method \string fillWorkCity()
	 * @method \string getWorkState()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkState(\string|\Bitrix\Main\DB\SqlExpression $workState)
	 * @method bool hasWorkState()
	 * @method bool isWorkStateFilled()
	 * @method bool isWorkStateChanged()
	 * @method \string remindActualWorkState()
	 * @method \string requireWorkState()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkState()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkState()
	 * @method \string fillWorkState()
	 * @method \string getWorkZip()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkZip(\string|\Bitrix\Main\DB\SqlExpression $workZip)
	 * @method bool hasWorkZip()
	 * @method bool isWorkZipFilled()
	 * @method bool isWorkZipChanged()
	 * @method \string remindActualWorkZip()
	 * @method \string requireWorkZip()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkZip()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkZip()
	 * @method \string fillWorkZip()
	 * @method \string getWorkCountry()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkCountry(\string|\Bitrix\Main\DB\SqlExpression $workCountry)
	 * @method bool hasWorkCountry()
	 * @method bool isWorkCountryFilled()
	 * @method bool isWorkCountryChanged()
	 * @method \string remindActualWorkCountry()
	 * @method \string requireWorkCountry()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkCountry()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkCountry()
	 * @method \string fillWorkCountry()
	 * @method \string getWorkProfile()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkProfile(\string|\Bitrix\Main\DB\SqlExpression $workProfile)
	 * @method bool hasWorkProfile()
	 * @method bool isWorkProfileFilled()
	 * @method bool isWorkProfileChanged()
	 * @method \string remindActualWorkProfile()
	 * @method \string requireWorkProfile()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkProfile()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkProfile()
	 * @method \string fillWorkProfile()
	 * @method \int getWorkLogo()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkLogo(\int|\Bitrix\Main\DB\SqlExpression $workLogo)
	 * @method bool hasWorkLogo()
	 * @method bool isWorkLogoFilled()
	 * @method bool isWorkLogoChanged()
	 * @method \int remindActualWorkLogo()
	 * @method \int requireWorkLogo()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkLogo()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkLogo()
	 * @method \int fillWorkLogo()
	 * @method \string getWorkNotes()
	 * @method \Bitrix\Voximplant\Model\EO_User setWorkNotes(\string|\Bitrix\Main\DB\SqlExpression $workNotes)
	 * @method bool hasWorkNotes()
	 * @method bool isWorkNotesFilled()
	 * @method bool isWorkNotesChanged()
	 * @method \string remindActualWorkNotes()
	 * @method \string requireWorkNotes()
	 * @method \Bitrix\Voximplant\Model\EO_User resetWorkNotes()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetWorkNotes()
	 * @method \string fillWorkNotes()
	 * @method \string getAdminNotes()
	 * @method \Bitrix\Voximplant\Model\EO_User setAdminNotes(\string|\Bitrix\Main\DB\SqlExpression $adminNotes)
	 * @method bool hasAdminNotes()
	 * @method bool isAdminNotesFilled()
	 * @method bool isAdminNotesChanged()
	 * @method \string remindActualAdminNotes()
	 * @method \string requireAdminNotes()
	 * @method \Bitrix\Voximplant\Model\EO_User resetAdminNotes()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetAdminNotes()
	 * @method \string fillAdminNotes()
	 * @method \string getShortName()
	 * @method \string remindActualShortName()
	 * @method \string requireShortName()
	 * @method bool hasShortName()
	 * @method bool isShortNameFilled()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetShortName()
	 * @method \string fillShortName()
	 * @method \boolean getIsOnline()
	 * @method \boolean remindActualIsOnline()
	 * @method \boolean requireIsOnline()
	 * @method bool hasIsOnline()
	 * @method bool isIsOnlineFilled()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetIsOnline()
	 * @method \boolean fillIsOnline()
	 * @method \boolean getIsRealUser()
	 * @method \boolean remindActualIsRealUser()
	 * @method \boolean requireIsRealUser()
	 * @method bool hasIsRealUser()
	 * @method bool isIsRealUserFilled()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetIsRealUser()
	 * @method \boolean fillIsRealUser()
	 * @method \Bitrix\Main\EO_UserIndex getIndex()
	 * @method \Bitrix\Main\EO_UserIndex remindActualIndex()
	 * @method \Bitrix\Main\EO_UserIndex requireIndex()
	 * @method \Bitrix\Voximplant\Model\EO_User setIndex(\Bitrix\Main\EO_UserIndex $object)
	 * @method \Bitrix\Voximplant\Model\EO_User resetIndex()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetIndex()
	 * @method bool hasIndex()
	 * @method bool isIndexFilled()
	 * @method bool isIndexChanged()
	 * @method \Bitrix\Main\EO_UserIndex fillIndex()
	 * @method \Bitrix\Main\EO_UserCounter getCounter()
	 * @method \Bitrix\Main\EO_UserCounter remindActualCounter()
	 * @method \Bitrix\Main\EO_UserCounter requireCounter()
	 * @method \Bitrix\Voximplant\Model\EO_User setCounter(\Bitrix\Main\EO_UserCounter $object)
	 * @method \Bitrix\Voximplant\Model\EO_User resetCounter()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetCounter()
	 * @method bool hasCounter()
	 * @method bool isCounterFilled()
	 * @method bool isCounterChanged()
	 * @method \Bitrix\Main\EO_UserCounter fillCounter()
	 * @method \Bitrix\Main\EO_UserPhoneAuth getPhoneAuth()
	 * @method \Bitrix\Main\EO_UserPhoneAuth remindActualPhoneAuth()
	 * @method \Bitrix\Main\EO_UserPhoneAuth requirePhoneAuth()
	 * @method \Bitrix\Voximplant\Model\EO_User setPhoneAuth(\Bitrix\Main\EO_UserPhoneAuth $object)
	 * @method \Bitrix\Voximplant\Model\EO_User resetPhoneAuth()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetPhoneAuth()
	 * @method bool hasPhoneAuth()
	 * @method bool isPhoneAuthFilled()
	 * @method bool isPhoneAuthChanged()
	 * @method \Bitrix\Main\EO_UserPhoneAuth fillPhoneAuth()
	 * @method \Bitrix\Main\EO_UserGroup_Collection getGroups()
	 * @method \Bitrix\Main\EO_UserGroup_Collection requireGroups()
	 * @method \Bitrix\Main\EO_UserGroup_Collection fillGroups()
	 * @method bool hasGroups()
	 * @method bool isGroupsFilled()
	 * @method bool isGroupsChanged()
	 * @method void addToGroups(\Bitrix\Main\EO_UserGroup $userGroup)
	 * @method void removeFromGroups(\Bitrix\Main\EO_UserGroup $userGroup)
	 * @method void removeAllGroups()
	 * @method \Bitrix\Voximplant\Model\EO_User resetGroups()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetGroups()
	 * @method \boolean getIsBusy()
	 * @method \boolean remindActualIsBusy()
	 * @method \boolean requireIsBusy()
	 * @method bool hasIsBusy()
	 * @method bool isIsBusyFilled()
	 * @method \Bitrix\Voximplant\Model\EO_User unsetIsBusy()
	 * @method \boolean fillIsBusy()
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
	 * @method \Bitrix\Voximplant\Model\EO_User set($fieldName, $value)
	 * @method \Bitrix\Voximplant\Model\EO_User reset($fieldName)
	 * @method \Bitrix\Voximplant\Model\EO_User unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\Model\EO_User wakeUp($data)
	 */
	class EO_User {
		/* @var \Bitrix\Voximplant\Model\UserTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\UserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * EO_User_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getLoginList()
	 * @method \string[] fillLogin()
	 * @method \string[] getPasswordList()
	 * @method \string[] fillPassword()
	 * @method \string[] getEmailList()
	 * @method \string[] fillEmail()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \boolean[] getBlockedList()
	 * @method \boolean[] fillBlocked()
	 * @method \Bitrix\Main\Type\DateTime[] getDateRegisterList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateRegister()
	 * @method \Bitrix\Main\Type\DateTime[] getDateRegShortList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime[] getLastLoginList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLastLogin()
	 * @method \Bitrix\Main\Type\DateTime[] getLastLoginShortList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime[] getLastActivityDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getSecondNameList()
	 * @method \string[] fillSecondName()
	 * @method \string[] getLastNameList()
	 * @method \string[] fillLastName()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getExternalAuthIdList()
	 * @method \string[] fillExternalAuthId()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 * @method \string[] getBxUserIdList()
	 * @method \string[] fillBxUserId()
	 * @method \string[] getConfirmCodeList()
	 * @method \string[] fillConfirmCode()
	 * @method \string[] getLidList()
	 * @method \string[] fillLid()
	 * @method \string[] getLanguageIdList()
	 * @method \string[] fillLanguageId()
	 * @method \string[] getTimeZoneList()
	 * @method \string[] fillTimeZone()
	 * @method \int[] getTimeZoneOffsetList()
	 * @method \int[] fillTimeZoneOffset()
	 * @method \string[] getPersonalProfessionList()
	 * @method \string[] fillPersonalProfession()
	 * @method \string[] getPersonalPhoneList()
	 * @method \string[] fillPersonalPhone()
	 * @method \string[] getPersonalMobileList()
	 * @method \string[] fillPersonalMobile()
	 * @method \string[] getPersonalWwwList()
	 * @method \string[] fillPersonalWww()
	 * @method \string[] getPersonalIcqList()
	 * @method \string[] fillPersonalIcq()
	 * @method \string[] getPersonalFaxList()
	 * @method \string[] fillPersonalFax()
	 * @method \string[] getPersonalPagerList()
	 * @method \string[] fillPersonalPager()
	 * @method \string[] getPersonalStreetList()
	 * @method \string[] fillPersonalStreet()
	 * @method \string[] getPersonalMailboxList()
	 * @method \string[] fillPersonalMailbox()
	 * @method \string[] getPersonalCityList()
	 * @method \string[] fillPersonalCity()
	 * @method \string[] getPersonalStateList()
	 * @method \string[] fillPersonalState()
	 * @method \string[] getPersonalZipList()
	 * @method \string[] fillPersonalZip()
	 * @method \string[] getPersonalCountryList()
	 * @method \string[] fillPersonalCountry()
	 * @method \Bitrix\Main\Type\Date[] getPersonalBirthdayList()
	 * @method \Bitrix\Main\Type\Date[] fillPersonalBirthday()
	 * @method \string[] getPersonalGenderList()
	 * @method \string[] fillPersonalGender()
	 * @method \int[] getPersonalPhotoList()
	 * @method \int[] fillPersonalPhoto()
	 * @method \string[] getPersonalNotesList()
	 * @method \string[] fillPersonalNotes()
	 * @method \string[] getWorkCompanyList()
	 * @method \string[] fillWorkCompany()
	 * @method \string[] getWorkDepartmentList()
	 * @method \string[] fillWorkDepartment()
	 * @method \string[] getWorkPhoneList()
	 * @method \string[] fillWorkPhone()
	 * @method \string[] getWorkPositionList()
	 * @method \string[] fillWorkPosition()
	 * @method \string[] getWorkWwwList()
	 * @method \string[] fillWorkWww()
	 * @method \string[] getWorkFaxList()
	 * @method \string[] fillWorkFax()
	 * @method \string[] getWorkPagerList()
	 * @method \string[] fillWorkPager()
	 * @method \string[] getWorkStreetList()
	 * @method \string[] fillWorkStreet()
	 * @method \string[] getWorkMailboxList()
	 * @method \string[] fillWorkMailbox()
	 * @method \string[] getWorkCityList()
	 * @method \string[] fillWorkCity()
	 * @method \string[] getWorkStateList()
	 * @method \string[] fillWorkState()
	 * @method \string[] getWorkZipList()
	 * @method \string[] fillWorkZip()
	 * @method \string[] getWorkCountryList()
	 * @method \string[] fillWorkCountry()
	 * @method \string[] getWorkProfileList()
	 * @method \string[] fillWorkProfile()
	 * @method \int[] getWorkLogoList()
	 * @method \int[] fillWorkLogo()
	 * @method \string[] getWorkNotesList()
	 * @method \string[] fillWorkNotes()
	 * @method \string[] getAdminNotesList()
	 * @method \string[] fillAdminNotes()
	 * @method \string[] getShortNameList()
	 * @method \string[] fillShortName()
	 * @method \boolean[] getIsOnlineList()
	 * @method \boolean[] fillIsOnline()
	 * @method \boolean[] getIsRealUserList()
	 * @method \boolean[] fillIsRealUser()
	 * @method \Bitrix\Main\EO_UserIndex[] getIndexList()
	 * @method \Bitrix\Voximplant\Model\EO_User_Collection getIndexCollection()
	 * @method \Bitrix\Main\EO_UserIndex_Collection fillIndex()
	 * @method \Bitrix\Main\EO_UserCounter[] getCounterList()
	 * @method \Bitrix\Voximplant\Model\EO_User_Collection getCounterCollection()
	 * @method \Bitrix\Main\EO_UserCounter_Collection fillCounter()
	 * @method \Bitrix\Main\EO_UserPhoneAuth[] getPhoneAuthList()
	 * @method \Bitrix\Voximplant\Model\EO_User_Collection getPhoneAuthCollection()
	 * @method \Bitrix\Main\EO_UserPhoneAuth_Collection fillPhoneAuth()
	 * @method \Bitrix\Main\EO_UserGroup_Collection[] getGroupsList()
	 * @method \Bitrix\Main\EO_UserGroup_Collection getGroupsCollection()
	 * @method \Bitrix\Main\EO_UserGroup_Collection fillGroups()
	 * @method \boolean[] getIsBusyList()
	 * @method \boolean[] fillIsBusy()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\Model\EO_User $object)
	 * @method bool has(\Bitrix\Voximplant\Model\EO_User $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_User getByPrimary($primary)
	 * @method \Bitrix\Voximplant\Model\EO_User[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\Model\EO_User $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\Model\EO_User_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\Model\EO_User current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_User_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\Model\UserTable */
		static public $dataClass = '\Bitrix\Voximplant\Model\UserTable';
	}
}
namespace Bitrix\Voximplant\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_User_Result exec()
	 * @method \Bitrix\Voximplant\Model\EO_User fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_User_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_User_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_User fetchObject()
	 * @method \Bitrix\Voximplant\Model\EO_User_Collection fetchCollection()
	 */
	class EO_User_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\Model\EO_User createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\Model\EO_User_Collection createCollection()
	 * @method \Bitrix\Voximplant\Model\EO_User wakeUpObject($row)
	 * @method \Bitrix\Voximplant\Model\EO_User_Collection wakeUpCollection($rows)
	 */
	class EO_User_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\PhoneTable:voximplant\lib\phone.php:59c52cc831025e5203131106f09aa8c7 */
namespace Bitrix\Voximplant {
	/**
	 * EO_Phone
	 * @see \Bitrix\Voximplant\PhoneTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\EO_Phone setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Voximplant\EO_Phone setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Voximplant\EO_Phone resetUserId()
	 * @method \Bitrix\Voximplant\EO_Phone unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Voximplant\EO_Phone setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Voximplant\EO_Phone resetUser()
	 * @method \Bitrix\Voximplant\EO_Phone unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \string getPhoneNumber()
	 * @method \Bitrix\Voximplant\EO_Phone setPhoneNumber(\string|\Bitrix\Main\DB\SqlExpression $phoneNumber)
	 * @method bool hasPhoneNumber()
	 * @method bool isPhoneNumberFilled()
	 * @method bool isPhoneNumberChanged()
	 * @method \string remindActualPhoneNumber()
	 * @method \string requirePhoneNumber()
	 * @method \Bitrix\Voximplant\EO_Phone resetPhoneNumber()
	 * @method \Bitrix\Voximplant\EO_Phone unsetPhoneNumber()
	 * @method \string fillPhoneNumber()
	 * @method \string getPhoneMnemonic()
	 * @method \Bitrix\Voximplant\EO_Phone setPhoneMnemonic(\string|\Bitrix\Main\DB\SqlExpression $phoneMnemonic)
	 * @method bool hasPhoneMnemonic()
	 * @method bool isPhoneMnemonicFilled()
	 * @method bool isPhoneMnemonicChanged()
	 * @method \string remindActualPhoneMnemonic()
	 * @method \string requirePhoneMnemonic()
	 * @method \Bitrix\Voximplant\EO_Phone resetPhoneMnemonic()
	 * @method \Bitrix\Voximplant\EO_Phone unsetPhoneMnemonic()
	 * @method \string fillPhoneMnemonic()
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
	 * @method \Bitrix\Voximplant\EO_Phone set($fieldName, $value)
	 * @method \Bitrix\Voximplant\EO_Phone reset($fieldName)
	 * @method \Bitrix\Voximplant\EO_Phone unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\EO_Phone wakeUp($data)
	 */
	class EO_Phone {
		/* @var \Bitrix\Voximplant\PhoneTable */
		static public $dataClass = '\Bitrix\Voximplant\PhoneTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant {
	/**
	 * EO_Phone_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Voximplant\EO_Phone_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \string[] getPhoneNumberList()
	 * @method \string[] fillPhoneNumber()
	 * @method \string[] getPhoneMnemonicList()
	 * @method \string[] fillPhoneMnemonic()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\EO_Phone $object)
	 * @method bool has(\Bitrix\Voximplant\EO_Phone $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Phone getByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Phone[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\EO_Phone $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\EO_Phone_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\EO_Phone current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Phone_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\PhoneTable */
		static public $dataClass = '\Bitrix\Voximplant\PhoneTable';
	}
}
namespace Bitrix\Voximplant {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Phone_Result exec()
	 * @method \Bitrix\Voximplant\EO_Phone fetchObject()
	 * @method \Bitrix\Voximplant\EO_Phone_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Phone_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\EO_Phone fetchObject()
	 * @method \Bitrix\Voximplant\EO_Phone_Collection fetchCollection()
	 */
	class EO_Phone_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\EO_Phone createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\EO_Phone_Collection createCollection()
	 * @method \Bitrix\Voximplant\EO_Phone wakeUpObject($row)
	 * @method \Bitrix\Voximplant\EO_Phone_Collection wakeUpCollection($rows)
	 */
	class EO_Phone_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\SipTable:voximplant\lib\sip.php:9fd6e4ace3ac17a1c8e8dcdacbb559d6 */
namespace Bitrix\Voximplant {
	/**
	 * EO_Sip
	 * @see \Bitrix\Voximplant\SipTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\EO_Sip setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getType()
	 * @method \Bitrix\Voximplant\EO_Sip setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Voximplant\EO_Sip resetType()
	 * @method \Bitrix\Voximplant\EO_Sip unsetType()
	 * @method \string fillType()
	 * @method \string getTitle()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method \Bitrix\Voximplant\EO_Sip unsetTitle()
	 * @method \string fillTitle()
	 * @method \int getConfigId()
	 * @method \Bitrix\Voximplant\EO_Sip setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\Voximplant\EO_Sip resetConfigId()
	 * @method \Bitrix\Voximplant\EO_Sip unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \int getRegId()
	 * @method \Bitrix\Voximplant\EO_Sip setRegId(\int|\Bitrix\Main\DB\SqlExpression $regId)
	 * @method bool hasRegId()
	 * @method bool isRegIdFilled()
	 * @method bool isRegIdChanged()
	 * @method \int remindActualRegId()
	 * @method \int requireRegId()
	 * @method \Bitrix\Voximplant\EO_Sip resetRegId()
	 * @method \Bitrix\Voximplant\EO_Sip unsetRegId()
	 * @method \int fillRegId()
	 * @method \string getAppId()
	 * @method \Bitrix\Voximplant\EO_Sip setAppId(\string|\Bitrix\Main\DB\SqlExpression $appId)
	 * @method bool hasAppId()
	 * @method bool isAppIdFilled()
	 * @method bool isAppIdChanged()
	 * @method \string remindActualAppId()
	 * @method \string requireAppId()
	 * @method \Bitrix\Voximplant\EO_Sip resetAppId()
	 * @method \Bitrix\Voximplant\EO_Sip unsetAppId()
	 * @method \string fillAppId()
	 * @method \string getServer()
	 * @method \Bitrix\Voximplant\EO_Sip setServer(\string|\Bitrix\Main\DB\SqlExpression $server)
	 * @method bool hasServer()
	 * @method bool isServerFilled()
	 * @method bool isServerChanged()
	 * @method \string remindActualServer()
	 * @method \string requireServer()
	 * @method \Bitrix\Voximplant\EO_Sip resetServer()
	 * @method \Bitrix\Voximplant\EO_Sip unsetServer()
	 * @method \string fillServer()
	 * @method \string getLogin()
	 * @method \Bitrix\Voximplant\EO_Sip setLogin(\string|\Bitrix\Main\DB\SqlExpression $login)
	 * @method bool hasLogin()
	 * @method bool isLoginFilled()
	 * @method bool isLoginChanged()
	 * @method \string remindActualLogin()
	 * @method \string requireLogin()
	 * @method \Bitrix\Voximplant\EO_Sip resetLogin()
	 * @method \Bitrix\Voximplant\EO_Sip unsetLogin()
	 * @method \string fillLogin()
	 * @method \string getPassword()
	 * @method \Bitrix\Voximplant\EO_Sip setPassword(\string|\Bitrix\Main\DB\SqlExpression $password)
	 * @method bool hasPassword()
	 * @method bool isPasswordFilled()
	 * @method bool isPasswordChanged()
	 * @method \string remindActualPassword()
	 * @method \string requirePassword()
	 * @method \Bitrix\Voximplant\EO_Sip resetPassword()
	 * @method \Bitrix\Voximplant\EO_Sip unsetPassword()
	 * @method \string fillPassword()
	 * @method \string getIncomingServer()
	 * @method \Bitrix\Voximplant\EO_Sip setIncomingServer(\string|\Bitrix\Main\DB\SqlExpression $incomingServer)
	 * @method bool hasIncomingServer()
	 * @method bool isIncomingServerFilled()
	 * @method bool isIncomingServerChanged()
	 * @method \string remindActualIncomingServer()
	 * @method \string requireIncomingServer()
	 * @method \Bitrix\Voximplant\EO_Sip resetIncomingServer()
	 * @method \Bitrix\Voximplant\EO_Sip unsetIncomingServer()
	 * @method \string fillIncomingServer()
	 * @method \string getIncomingLogin()
	 * @method \Bitrix\Voximplant\EO_Sip setIncomingLogin(\string|\Bitrix\Main\DB\SqlExpression $incomingLogin)
	 * @method bool hasIncomingLogin()
	 * @method bool isIncomingLoginFilled()
	 * @method bool isIncomingLoginChanged()
	 * @method \string remindActualIncomingLogin()
	 * @method \string requireIncomingLogin()
	 * @method \Bitrix\Voximplant\EO_Sip resetIncomingLogin()
	 * @method \Bitrix\Voximplant\EO_Sip unsetIncomingLogin()
	 * @method \string fillIncomingLogin()
	 * @method \string getIncomingPassword()
	 * @method \Bitrix\Voximplant\EO_Sip setIncomingPassword(\string|\Bitrix\Main\DB\SqlExpression $incomingPassword)
	 * @method bool hasIncomingPassword()
	 * @method bool isIncomingPasswordFilled()
	 * @method bool isIncomingPasswordChanged()
	 * @method \string remindActualIncomingPassword()
	 * @method \string requireIncomingPassword()
	 * @method \Bitrix\Voximplant\EO_Sip resetIncomingPassword()
	 * @method \Bitrix\Voximplant\EO_Sip unsetIncomingPassword()
	 * @method \string fillIncomingPassword()
	 * @method \string getAuthUser()
	 * @method \Bitrix\Voximplant\EO_Sip setAuthUser(\string|\Bitrix\Main\DB\SqlExpression $authUser)
	 * @method bool hasAuthUser()
	 * @method bool isAuthUserFilled()
	 * @method bool isAuthUserChanged()
	 * @method \string remindActualAuthUser()
	 * @method \string requireAuthUser()
	 * @method \Bitrix\Voximplant\EO_Sip resetAuthUser()
	 * @method \Bitrix\Voximplant\EO_Sip unsetAuthUser()
	 * @method \string fillAuthUser()
	 * @method \string getOutboundProxy()
	 * @method \Bitrix\Voximplant\EO_Sip setOutboundProxy(\string|\Bitrix\Main\DB\SqlExpression $outboundProxy)
	 * @method bool hasOutboundProxy()
	 * @method bool isOutboundProxyFilled()
	 * @method bool isOutboundProxyChanged()
	 * @method \string remindActualOutboundProxy()
	 * @method \string requireOutboundProxy()
	 * @method \Bitrix\Voximplant\EO_Sip resetOutboundProxy()
	 * @method \Bitrix\Voximplant\EO_Sip unsetOutboundProxy()
	 * @method \string fillOutboundProxy()
	 * @method \boolean getDetectLineNumber()
	 * @method \Bitrix\Voximplant\EO_Sip setDetectLineNumber(\boolean|\Bitrix\Main\DB\SqlExpression $detectLineNumber)
	 * @method bool hasDetectLineNumber()
	 * @method bool isDetectLineNumberFilled()
	 * @method bool isDetectLineNumberChanged()
	 * @method \boolean remindActualDetectLineNumber()
	 * @method \boolean requireDetectLineNumber()
	 * @method \Bitrix\Voximplant\EO_Sip resetDetectLineNumber()
	 * @method \Bitrix\Voximplant\EO_Sip unsetDetectLineNumber()
	 * @method \boolean fillDetectLineNumber()
	 * @method \string getLineDetectHeaderOrder()
	 * @method \Bitrix\Voximplant\EO_Sip setLineDetectHeaderOrder(\string|\Bitrix\Main\DB\SqlExpression $lineDetectHeaderOrder)
	 * @method bool hasLineDetectHeaderOrder()
	 * @method bool isLineDetectHeaderOrderFilled()
	 * @method bool isLineDetectHeaderOrderChanged()
	 * @method \string remindActualLineDetectHeaderOrder()
	 * @method \string requireLineDetectHeaderOrder()
	 * @method \Bitrix\Voximplant\EO_Sip resetLineDetectHeaderOrder()
	 * @method \Bitrix\Voximplant\EO_Sip unsetLineDetectHeaderOrder()
	 * @method \string fillLineDetectHeaderOrder()
	 * @method \Bitrix\Voximplant\EO_Config getConfig()
	 * @method \Bitrix\Voximplant\EO_Config remindActualConfig()
	 * @method \Bitrix\Voximplant\EO_Config requireConfig()
	 * @method \Bitrix\Voximplant\EO_Sip setConfig(\Bitrix\Voximplant\EO_Config $object)
	 * @method \Bitrix\Voximplant\EO_Sip resetConfig()
	 * @method \Bitrix\Voximplant\EO_Sip unsetConfig()
	 * @method bool hasConfig()
	 * @method bool isConfigFilled()
	 * @method bool isConfigChanged()
	 * @method \Bitrix\Voximplant\EO_Config fillConfig()
	 * @method \int getRegistrationStatusCode()
	 * @method \Bitrix\Voximplant\EO_Sip setRegistrationStatusCode(\int|\Bitrix\Main\DB\SqlExpression $registrationStatusCode)
	 * @method bool hasRegistrationStatusCode()
	 * @method bool isRegistrationStatusCodeFilled()
	 * @method bool isRegistrationStatusCodeChanged()
	 * @method \int remindActualRegistrationStatusCode()
	 * @method \int requireRegistrationStatusCode()
	 * @method \Bitrix\Voximplant\EO_Sip resetRegistrationStatusCode()
	 * @method \Bitrix\Voximplant\EO_Sip unsetRegistrationStatusCode()
	 * @method \int fillRegistrationStatusCode()
	 * @method \string getRegistrationErrorMessage()
	 * @method \Bitrix\Voximplant\EO_Sip setRegistrationErrorMessage(\string|\Bitrix\Main\DB\SqlExpression $registrationErrorMessage)
	 * @method bool hasRegistrationErrorMessage()
	 * @method bool isRegistrationErrorMessageFilled()
	 * @method bool isRegistrationErrorMessageChanged()
	 * @method \string remindActualRegistrationErrorMessage()
	 * @method \string requireRegistrationErrorMessage()
	 * @method \Bitrix\Voximplant\EO_Sip resetRegistrationErrorMessage()
	 * @method \Bitrix\Voximplant\EO_Sip unsetRegistrationErrorMessage()
	 * @method \string fillRegistrationErrorMessage()
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
	 * @method \Bitrix\Voximplant\EO_Sip set($fieldName, $value)
	 * @method \Bitrix\Voximplant\EO_Sip reset($fieldName)
	 * @method \Bitrix\Voximplant\EO_Sip unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\EO_Sip wakeUp($data)
	 */
	class EO_Sip {
		/* @var \Bitrix\Voximplant\SipTable */
		static public $dataClass = '\Bitrix\Voximplant\SipTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant {
	/**
	 * EO_Sip_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \int[] getRegIdList()
	 * @method \int[] fillRegId()
	 * @method \string[] getAppIdList()
	 * @method \string[] fillAppId()
	 * @method \string[] getServerList()
	 * @method \string[] fillServer()
	 * @method \string[] getLoginList()
	 * @method \string[] fillLogin()
	 * @method \string[] getPasswordList()
	 * @method \string[] fillPassword()
	 * @method \string[] getIncomingServerList()
	 * @method \string[] fillIncomingServer()
	 * @method \string[] getIncomingLoginList()
	 * @method \string[] fillIncomingLogin()
	 * @method \string[] getIncomingPasswordList()
	 * @method \string[] fillIncomingPassword()
	 * @method \string[] getAuthUserList()
	 * @method \string[] fillAuthUser()
	 * @method \string[] getOutboundProxyList()
	 * @method \string[] fillOutboundProxy()
	 * @method \boolean[] getDetectLineNumberList()
	 * @method \boolean[] fillDetectLineNumber()
	 * @method \string[] getLineDetectHeaderOrderList()
	 * @method \string[] fillLineDetectHeaderOrder()
	 * @method \Bitrix\Voximplant\EO_Config[] getConfigList()
	 * @method \Bitrix\Voximplant\EO_Sip_Collection getConfigCollection()
	 * @method \Bitrix\Voximplant\EO_Config_Collection fillConfig()
	 * @method \int[] getRegistrationStatusCodeList()
	 * @method \int[] fillRegistrationStatusCode()
	 * @method \string[] getRegistrationErrorMessageList()
	 * @method \string[] fillRegistrationErrorMessage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\EO_Sip $object)
	 * @method bool has(\Bitrix\Voximplant\EO_Sip $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Sip getByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Sip[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\EO_Sip $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\EO_Sip_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\EO_Sip current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Sip_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\SipTable */
		static public $dataClass = '\Bitrix\Voximplant\SipTable';
	}
}
namespace Bitrix\Voximplant {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Sip_Result exec()
	 * @method \Bitrix\Voximplant\EO_Sip fetchObject()
	 * @method \Bitrix\Voximplant\EO_Sip_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Sip_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\EO_Sip fetchObject()
	 * @method \Bitrix\Voximplant\EO_Sip_Collection fetchCollection()
	 */
	class EO_Sip_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\EO_Sip createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\EO_Sip_Collection createCollection()
	 * @method \Bitrix\Voximplant\EO_Sip wakeUpObject($row)
	 * @method \Bitrix\Voximplant\EO_Sip_Collection wakeUpCollection($rows)
	 */
	class EO_Sip_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Voximplant\StatisticTable:voximplant\lib\statistic.php:8858bfd7d5bd6ba373ded1017db389cc */
namespace Bitrix\Voximplant {
	/**
	 * EO_Statistic
	 * @see \Bitrix\Voximplant\StatisticTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Voximplant\EO_Statistic setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getAccountId()
	 * @method \Bitrix\Voximplant\EO_Statistic setAccountId(\int|\Bitrix\Main\DB\SqlExpression $accountId)
	 * @method bool hasAccountId()
	 * @method bool isAccountIdFilled()
	 * @method bool isAccountIdChanged()
	 * @method \int remindActualAccountId()
	 * @method \int requireAccountId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetAccountId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetAccountId()
	 * @method \int fillAccountId()
	 * @method \int getApplicationId()
	 * @method \Bitrix\Voximplant\EO_Statistic setApplicationId(\int|\Bitrix\Main\DB\SqlExpression $applicationId)
	 * @method bool hasApplicationId()
	 * @method bool isApplicationIdFilled()
	 * @method bool isApplicationIdChanged()
	 * @method \int remindActualApplicationId()
	 * @method \int requireApplicationId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetApplicationId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetApplicationId()
	 * @method \int fillApplicationId()
	 * @method \string getApplicationName()
	 * @method \Bitrix\Voximplant\EO_Statistic setApplicationName(\string|\Bitrix\Main\DB\SqlExpression $applicationName)
	 * @method bool hasApplicationName()
	 * @method bool isApplicationNameFilled()
	 * @method bool isApplicationNameChanged()
	 * @method \string remindActualApplicationName()
	 * @method \string requireApplicationName()
	 * @method \Bitrix\Voximplant\EO_Statistic resetApplicationName()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetApplicationName()
	 * @method \string fillApplicationName()
	 * @method \int getPortalUserId()
	 * @method \Bitrix\Voximplant\EO_Statistic setPortalUserId(\int|\Bitrix\Main\DB\SqlExpression $portalUserId)
	 * @method bool hasPortalUserId()
	 * @method bool isPortalUserIdFilled()
	 * @method bool isPortalUserIdChanged()
	 * @method \int remindActualPortalUserId()
	 * @method \int requirePortalUserId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetPortalUserId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetPortalUserId()
	 * @method \int fillPortalUserId()
	 * @method \string getPortalNumber()
	 * @method \Bitrix\Voximplant\EO_Statistic setPortalNumber(\string|\Bitrix\Main\DB\SqlExpression $portalNumber)
	 * @method bool hasPortalNumber()
	 * @method bool isPortalNumberFilled()
	 * @method bool isPortalNumberChanged()
	 * @method \string remindActualPortalNumber()
	 * @method \string requirePortalNumber()
	 * @method \Bitrix\Voximplant\EO_Statistic resetPortalNumber()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetPortalNumber()
	 * @method \string fillPortalNumber()
	 * @method \string getPhoneNumber()
	 * @method \Bitrix\Voximplant\EO_Statistic setPhoneNumber(\string|\Bitrix\Main\DB\SqlExpression $phoneNumber)
	 * @method bool hasPhoneNumber()
	 * @method bool isPhoneNumberFilled()
	 * @method bool isPhoneNumberChanged()
	 * @method \string remindActualPhoneNumber()
	 * @method \string requirePhoneNumber()
	 * @method \Bitrix\Voximplant\EO_Statistic resetPhoneNumber()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetPhoneNumber()
	 * @method \string fillPhoneNumber()
	 * @method \string getIncoming()
	 * @method \Bitrix\Voximplant\EO_Statistic setIncoming(\string|\Bitrix\Main\DB\SqlExpression $incoming)
	 * @method bool hasIncoming()
	 * @method bool isIncomingFilled()
	 * @method bool isIncomingChanged()
	 * @method \string remindActualIncoming()
	 * @method \string requireIncoming()
	 * @method \Bitrix\Voximplant\EO_Statistic resetIncoming()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetIncoming()
	 * @method \string fillIncoming()
	 * @method \string getCallId()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallId(\string|\Bitrix\Main\DB\SqlExpression $callId)
	 * @method bool hasCallId()
	 * @method bool isCallIdFilled()
	 * @method bool isCallIdChanged()
	 * @method \string remindActualCallId()
	 * @method \string requireCallId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallId()
	 * @method \string fillCallId()
	 * @method \string getExternalCallId()
	 * @method \Bitrix\Voximplant\EO_Statistic setExternalCallId(\string|\Bitrix\Main\DB\SqlExpression $externalCallId)
	 * @method bool hasExternalCallId()
	 * @method bool isExternalCallIdFilled()
	 * @method bool isExternalCallIdChanged()
	 * @method \string remindActualExternalCallId()
	 * @method \string requireExternalCallId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetExternalCallId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetExternalCallId()
	 * @method \string fillExternalCallId()
	 * @method \string getCallCategory()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallCategory(\string|\Bitrix\Main\DB\SqlExpression $callCategory)
	 * @method bool hasCallCategory()
	 * @method bool isCallCategoryFilled()
	 * @method bool isCallCategoryChanged()
	 * @method \string remindActualCallCategory()
	 * @method \string requireCallCategory()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallCategory()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallCategory()
	 * @method \string fillCallCategory()
	 * @method \string getCallLog()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallLog(\string|\Bitrix\Main\DB\SqlExpression $callLog)
	 * @method bool hasCallLog()
	 * @method bool isCallLogFilled()
	 * @method bool isCallLogChanged()
	 * @method \string remindActualCallLog()
	 * @method \string requireCallLog()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallLog()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallLog()
	 * @method \string fillCallLog()
	 * @method \string getCallDirection()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallDirection(\string|\Bitrix\Main\DB\SqlExpression $callDirection)
	 * @method bool hasCallDirection()
	 * @method bool isCallDirectionFilled()
	 * @method bool isCallDirectionChanged()
	 * @method \string remindActualCallDirection()
	 * @method \string requireCallDirection()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallDirection()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallDirection()
	 * @method \string fillCallDirection()
	 * @method \int getCallDuration()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallDuration(\int|\Bitrix\Main\DB\SqlExpression $callDuration)
	 * @method bool hasCallDuration()
	 * @method bool isCallDurationFilled()
	 * @method bool isCallDurationChanged()
	 * @method \int remindActualCallDuration()
	 * @method \int requireCallDuration()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallDuration()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallDuration()
	 * @method \int fillCallDuration()
	 * @method \Bitrix\Main\Type\DateTime getCallStartDate()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallStartDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $callStartDate)
	 * @method bool hasCallStartDate()
	 * @method bool isCallStartDateFilled()
	 * @method bool isCallStartDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCallStartDate()
	 * @method \Bitrix\Main\Type\DateTime requireCallStartDate()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallStartDate()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallStartDate()
	 * @method \Bitrix\Main\Type\DateTime fillCallStartDate()
	 * @method \int getCallStatus()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallStatus(\int|\Bitrix\Main\DB\SqlExpression $callStatus)
	 * @method bool hasCallStatus()
	 * @method bool isCallStatusFilled()
	 * @method bool isCallStatusChanged()
	 * @method \int remindActualCallStatus()
	 * @method \int requireCallStatus()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallStatus()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallStatus()
	 * @method \int fillCallStatus()
	 * @method \int getCallRecordId()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallRecordId(\int|\Bitrix\Main\DB\SqlExpression $callRecordId)
	 * @method bool hasCallRecordId()
	 * @method bool isCallRecordIdFilled()
	 * @method bool isCallRecordIdChanged()
	 * @method \int remindActualCallRecordId()
	 * @method \int requireCallRecordId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallRecordId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallRecordId()
	 * @method \int fillCallRecordId()
	 * @method \string getCallRecordUrl()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallRecordUrl(\string|\Bitrix\Main\DB\SqlExpression $callRecordUrl)
	 * @method bool hasCallRecordUrl()
	 * @method bool isCallRecordUrlFilled()
	 * @method bool isCallRecordUrlChanged()
	 * @method \string remindActualCallRecordUrl()
	 * @method \string requireCallRecordUrl()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallRecordUrl()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallRecordUrl()
	 * @method \string fillCallRecordUrl()
	 * @method \int getCallWebdavId()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallWebdavId(\int|\Bitrix\Main\DB\SqlExpression $callWebdavId)
	 * @method bool hasCallWebdavId()
	 * @method bool isCallWebdavIdFilled()
	 * @method bool isCallWebdavIdChanged()
	 * @method \int remindActualCallWebdavId()
	 * @method \int requireCallWebdavId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallWebdavId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallWebdavId()
	 * @method \int fillCallWebdavId()
	 * @method \int getCallVote()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallVote(\int|\Bitrix\Main\DB\SqlExpression $callVote)
	 * @method bool hasCallVote()
	 * @method bool isCallVoteFilled()
	 * @method bool isCallVoteChanged()
	 * @method \int remindActualCallVote()
	 * @method \int requireCallVote()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallVote()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallVote()
	 * @method \int fillCallVote()
	 * @method \float getCost()
	 * @method \Bitrix\Voximplant\EO_Statistic setCost(\float|\Bitrix\Main\DB\SqlExpression $cost)
	 * @method bool hasCost()
	 * @method bool isCostFilled()
	 * @method bool isCostChanged()
	 * @method \float remindActualCost()
	 * @method \float requireCost()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCost()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCost()
	 * @method \float fillCost()
	 * @method \string getCostCurrency()
	 * @method \Bitrix\Voximplant\EO_Statistic setCostCurrency(\string|\Bitrix\Main\DB\SqlExpression $costCurrency)
	 * @method bool hasCostCurrency()
	 * @method bool isCostCurrencyFilled()
	 * @method bool isCostCurrencyChanged()
	 * @method \string remindActualCostCurrency()
	 * @method \string requireCostCurrency()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCostCurrency()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCostCurrency()
	 * @method \string fillCostCurrency()
	 * @method \string getCallFailedCode()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallFailedCode(\string|\Bitrix\Main\DB\SqlExpression $callFailedCode)
	 * @method bool hasCallFailedCode()
	 * @method bool isCallFailedCodeFilled()
	 * @method bool isCallFailedCodeChanged()
	 * @method \string remindActualCallFailedCode()
	 * @method \string requireCallFailedCode()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallFailedCode()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallFailedCode()
	 * @method \string fillCallFailedCode()
	 * @method \string getCallFailedReason()
	 * @method \Bitrix\Voximplant\EO_Statistic setCallFailedReason(\string|\Bitrix\Main\DB\SqlExpression $callFailedReason)
	 * @method bool hasCallFailedReason()
	 * @method bool isCallFailedReasonFilled()
	 * @method bool isCallFailedReasonChanged()
	 * @method \string remindActualCallFailedReason()
	 * @method \string requireCallFailedReason()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCallFailedReason()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCallFailedReason()
	 * @method \string fillCallFailedReason()
	 * @method \string getCrmEntityType()
	 * @method \Bitrix\Voximplant\EO_Statistic setCrmEntityType(\string|\Bitrix\Main\DB\SqlExpression $crmEntityType)
	 * @method bool hasCrmEntityType()
	 * @method bool isCrmEntityTypeFilled()
	 * @method bool isCrmEntityTypeChanged()
	 * @method \string remindActualCrmEntityType()
	 * @method \string requireCrmEntityType()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCrmEntityType()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCrmEntityType()
	 * @method \string fillCrmEntityType()
	 * @method \int getCrmEntityId()
	 * @method \Bitrix\Voximplant\EO_Statistic setCrmEntityId(\int|\Bitrix\Main\DB\SqlExpression $crmEntityId)
	 * @method bool hasCrmEntityId()
	 * @method bool isCrmEntityIdFilled()
	 * @method bool isCrmEntityIdChanged()
	 * @method \int remindActualCrmEntityId()
	 * @method \int requireCrmEntityId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCrmEntityId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCrmEntityId()
	 * @method \int fillCrmEntityId()
	 * @method \int getCrmActivityId()
	 * @method \Bitrix\Voximplant\EO_Statistic setCrmActivityId(\int|\Bitrix\Main\DB\SqlExpression $crmActivityId)
	 * @method bool hasCrmActivityId()
	 * @method bool isCrmActivityIdFilled()
	 * @method bool isCrmActivityIdChanged()
	 * @method \int remindActualCrmActivityId()
	 * @method \int requireCrmActivityId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCrmActivityId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCrmActivityId()
	 * @method \int fillCrmActivityId()
	 * @method \int getRestAppId()
	 * @method \Bitrix\Voximplant\EO_Statistic setRestAppId(\int|\Bitrix\Main\DB\SqlExpression $restAppId)
	 * @method bool hasRestAppId()
	 * @method bool isRestAppIdFilled()
	 * @method bool isRestAppIdChanged()
	 * @method \int remindActualRestAppId()
	 * @method \int requireRestAppId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetRestAppId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetRestAppId()
	 * @method \int fillRestAppId()
	 * @method \string getRestAppName()
	 * @method \Bitrix\Voximplant\EO_Statistic setRestAppName(\string|\Bitrix\Main\DB\SqlExpression $restAppName)
	 * @method bool hasRestAppName()
	 * @method bool isRestAppNameFilled()
	 * @method bool isRestAppNameChanged()
	 * @method \string remindActualRestAppName()
	 * @method \string requireRestAppName()
	 * @method \Bitrix\Voximplant\EO_Statistic resetRestAppName()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetRestAppName()
	 * @method \string fillRestAppName()
	 * @method \int getTranscriptId()
	 * @method \Bitrix\Voximplant\EO_Statistic setTranscriptId(\int|\Bitrix\Main\DB\SqlExpression $transcriptId)
	 * @method bool hasTranscriptId()
	 * @method bool isTranscriptIdFilled()
	 * @method bool isTranscriptIdChanged()
	 * @method \int remindActualTranscriptId()
	 * @method \int requireTranscriptId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetTranscriptId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetTranscriptId()
	 * @method \int fillTranscriptId()
	 * @method \boolean getTranscriptPending()
	 * @method \Bitrix\Voximplant\EO_Statistic setTranscriptPending(\boolean|\Bitrix\Main\DB\SqlExpression $transcriptPending)
	 * @method bool hasTranscriptPending()
	 * @method bool isTranscriptPendingFilled()
	 * @method bool isTranscriptPendingChanged()
	 * @method \boolean remindActualTranscriptPending()
	 * @method \boolean requireTranscriptPending()
	 * @method \Bitrix\Voximplant\EO_Statistic resetTranscriptPending()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetTranscriptPending()
	 * @method \boolean fillTranscriptPending()
	 * @method \int getSessionId()
	 * @method \Bitrix\Voximplant\EO_Statistic setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\Voximplant\EO_Statistic resetSessionId()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \int getRedialAttempt()
	 * @method \Bitrix\Voximplant\EO_Statistic setRedialAttempt(\int|\Bitrix\Main\DB\SqlExpression $redialAttempt)
	 * @method bool hasRedialAttempt()
	 * @method bool isRedialAttemptFilled()
	 * @method bool isRedialAttemptChanged()
	 * @method \int remindActualRedialAttempt()
	 * @method \int requireRedialAttempt()
	 * @method \Bitrix\Voximplant\EO_Statistic resetRedialAttempt()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetRedialAttempt()
	 * @method \int fillRedialAttempt()
	 * @method \string getComment()
	 * @method \Bitrix\Voximplant\EO_Statistic setComment(\string|\Bitrix\Main\DB\SqlExpression $comment)
	 * @method bool hasComment()
	 * @method bool isCommentFilled()
	 * @method bool isCommentChanged()
	 * @method \string remindActualComment()
	 * @method \string requireComment()
	 * @method \Bitrix\Voximplant\EO_Statistic resetComment()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetComment()
	 * @method \string fillComment()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex getSearchIndex()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex remindActualSearchIndex()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex requireSearchIndex()
	 * @method \Bitrix\Voximplant\EO_Statistic setSearchIndex(\Bitrix\Voximplant\Model\EO_StatisticIndex $object)
	 * @method \Bitrix\Voximplant\EO_Statistic resetSearchIndex()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetSearchIndex()
	 * @method bool hasSearchIndex()
	 * @method bool isSearchIndexFilled()
	 * @method bool isSearchIndexChanged()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex fillSearchIndex()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript getTranscript()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript remindActualTranscript()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript requireTranscript()
	 * @method \Bitrix\Voximplant\EO_Statistic setTranscript(\Bitrix\Voximplant\Model\EO_Transcript $object)
	 * @method \Bitrix\Voximplant\EO_Statistic resetTranscript()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetTranscript()
	 * @method bool hasTranscript()
	 * @method bool isTranscriptFilled()
	 * @method bool isTranscriptChanged()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript fillTranscript()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection getCrmBindings()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection requireCrmBindings()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection fillCrmBindings()
	 * @method bool hasCrmBindings()
	 * @method bool isCrmBindingsFilled()
	 * @method bool isCrmBindingsChanged()
	 * @method void addToCrmBindings(\Bitrix\Voximplant\Model\EO_CallCrmEntity $callCrmEntity)
	 * @method void removeFromCrmBindings(\Bitrix\Voximplant\Model\EO_CallCrmEntity $callCrmEntity)
	 * @method void removeAllCrmBindings()
	 * @method \Bitrix\Voximplant\EO_Statistic resetCrmBindings()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetCrmBindings()
	 * @method \string getHasRecord()
	 * @method \string remindActualHasRecord()
	 * @method \string requireHasRecord()
	 * @method bool hasHasRecord()
	 * @method bool isHasRecordFilled()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetHasRecord()
	 * @method \string fillHasRecord()
	 * @method \string getTotalDuration()
	 * @method \string remindActualTotalDuration()
	 * @method \string requireTotalDuration()
	 * @method bool hasTotalDuration()
	 * @method bool isTotalDurationFilled()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetTotalDuration()
	 * @method \string fillTotalDuration()
	 * @method \string getTotalCost()
	 * @method \string remindActualTotalCost()
	 * @method \string requireTotalCost()
	 * @method bool hasTotalCost()
	 * @method bool isTotalCostFilled()
	 * @method \Bitrix\Voximplant\EO_Statistic unsetTotalCost()
	 * @method \string fillTotalCost()
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
	 * @method \Bitrix\Voximplant\EO_Statistic set($fieldName, $value)
	 * @method \Bitrix\Voximplant\EO_Statistic reset($fieldName)
	 * @method \Bitrix\Voximplant\EO_Statistic unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Voximplant\EO_Statistic wakeUp($data)
	 */
	class EO_Statistic {
		/* @var \Bitrix\Voximplant\StatisticTable */
		static public $dataClass = '\Bitrix\Voximplant\StatisticTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Voximplant {
	/**
	 * EO_Statistic_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getAccountIdList()
	 * @method \int[] fillAccountId()
	 * @method \int[] getApplicationIdList()
	 * @method \int[] fillApplicationId()
	 * @method \string[] getApplicationNameList()
	 * @method \string[] fillApplicationName()
	 * @method \int[] getPortalUserIdList()
	 * @method \int[] fillPortalUserId()
	 * @method \string[] getPortalNumberList()
	 * @method \string[] fillPortalNumber()
	 * @method \string[] getPhoneNumberList()
	 * @method \string[] fillPhoneNumber()
	 * @method \string[] getIncomingList()
	 * @method \string[] fillIncoming()
	 * @method \string[] getCallIdList()
	 * @method \string[] fillCallId()
	 * @method \string[] getExternalCallIdList()
	 * @method \string[] fillExternalCallId()
	 * @method \string[] getCallCategoryList()
	 * @method \string[] fillCallCategory()
	 * @method \string[] getCallLogList()
	 * @method \string[] fillCallLog()
	 * @method \string[] getCallDirectionList()
	 * @method \string[] fillCallDirection()
	 * @method \int[] getCallDurationList()
	 * @method \int[] fillCallDuration()
	 * @method \Bitrix\Main\Type\DateTime[] getCallStartDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCallStartDate()
	 * @method \int[] getCallStatusList()
	 * @method \int[] fillCallStatus()
	 * @method \int[] getCallRecordIdList()
	 * @method \int[] fillCallRecordId()
	 * @method \string[] getCallRecordUrlList()
	 * @method \string[] fillCallRecordUrl()
	 * @method \int[] getCallWebdavIdList()
	 * @method \int[] fillCallWebdavId()
	 * @method \int[] getCallVoteList()
	 * @method \int[] fillCallVote()
	 * @method \float[] getCostList()
	 * @method \float[] fillCost()
	 * @method \string[] getCostCurrencyList()
	 * @method \string[] fillCostCurrency()
	 * @method \string[] getCallFailedCodeList()
	 * @method \string[] fillCallFailedCode()
	 * @method \string[] getCallFailedReasonList()
	 * @method \string[] fillCallFailedReason()
	 * @method \string[] getCrmEntityTypeList()
	 * @method \string[] fillCrmEntityType()
	 * @method \int[] getCrmEntityIdList()
	 * @method \int[] fillCrmEntityId()
	 * @method \int[] getCrmActivityIdList()
	 * @method \int[] fillCrmActivityId()
	 * @method \int[] getRestAppIdList()
	 * @method \int[] fillRestAppId()
	 * @method \string[] getRestAppNameList()
	 * @method \string[] fillRestAppName()
	 * @method \int[] getTranscriptIdList()
	 * @method \int[] fillTranscriptId()
	 * @method \boolean[] getTranscriptPendingList()
	 * @method \boolean[] fillTranscriptPending()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \int[] getRedialAttemptList()
	 * @method \int[] fillRedialAttempt()
	 * @method \string[] getCommentList()
	 * @method \string[] fillComment()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex[] getSearchIndexList()
	 * @method \Bitrix\Voximplant\EO_Statistic_Collection getSearchIndexCollection()
	 * @method \Bitrix\Voximplant\Model\EO_StatisticIndex_Collection fillSearchIndex()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript[] getTranscriptList()
	 * @method \Bitrix\Voximplant\EO_Statistic_Collection getTranscriptCollection()
	 * @method \Bitrix\Voximplant\Model\EO_Transcript_Collection fillTranscript()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection[] getCrmBindingsList()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection getCrmBindingsCollection()
	 * @method \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection fillCrmBindings()
	 * @method \string[] getHasRecordList()
	 * @method \string[] fillHasRecord()
	 * @method \string[] getTotalDurationList()
	 * @method \string[] fillTotalDuration()
	 * @method \string[] getTotalCostList()
	 * @method \string[] fillTotalCost()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Voximplant\EO_Statistic $object)
	 * @method bool has(\Bitrix\Voximplant\EO_Statistic $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Statistic getByPrimary($primary)
	 * @method \Bitrix\Voximplant\EO_Statistic[] getAll()
	 * @method bool remove(\Bitrix\Voximplant\EO_Statistic $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Voximplant\EO_Statistic_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Voximplant\EO_Statistic current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Statistic_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Voximplant\StatisticTable */
		static public $dataClass = '\Bitrix\Voximplant\StatisticTable';
	}
}
namespace Bitrix\Voximplant {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Statistic_Result exec()
	 * @method \Bitrix\Voximplant\EO_Statistic fetchObject()
	 * @method \Bitrix\Voximplant\EO_Statistic_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Statistic_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Voximplant\EO_Statistic fetchObject()
	 * @method \Bitrix\Voximplant\EO_Statistic_Collection fetchCollection()
	 */
	class EO_Statistic_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Voximplant\EO_Statistic createObject($setDefaultValues = true)
	 * @method \Bitrix\Voximplant\EO_Statistic_Collection createCollection()
	 * @method \Bitrix\Voximplant\EO_Statistic wakeUpObject($row)
	 * @method \Bitrix\Voximplant\EO_Statistic_Collection wakeUpCollection($rows)
	 */
	class EO_Statistic_Entity extends \Bitrix\Main\ORM\Entity {}
}