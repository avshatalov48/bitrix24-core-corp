<?php

/* ORMENTITYANNOTATION:Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable:intranet\lib\CustomSection\Entity\CustomSectionPageTable.php:420e1f543a39c886ce73b6a8e8a34b3c */
namespace Bitrix\Intranet\CustomSection\Entity {
	/**
	 * EO_CustomSectionPage
	 * @see \Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCustomSectionId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage setCustomSectionId(\int|\Bitrix\Main\DB\SqlExpression $customSectionId)
	 * @method bool hasCustomSectionId()
	 * @method bool isCustomSectionIdFilled()
	 * @method bool isCustomSectionIdChanged()
	 * @method \int remindActualCustomSectionId()
	 * @method \int requireCustomSectionId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage resetCustomSectionId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage unsetCustomSectionId()
	 * @method \int fillCustomSectionId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection getCustomSection()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection remindActualCustomSection()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection requireCustomSection()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage setCustomSection(\Bitrix\Intranet\CustomSection\Entity\EO_CustomSection $object)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage resetCustomSection()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage unsetCustomSection()
	 * @method bool hasCustomSection()
	 * @method bool isCustomSectionFilled()
	 * @method bool isCustomSectionChanged()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection fillCustomSection()
	 * @method \string getCode()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage resetCode()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage unsetCode()
	 * @method \string fillCode()
	 * @method \string getTitle()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage resetTitle()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage unsetTitle()
	 * @method \string fillTitle()
	 * @method \int getSort()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage resetSort()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage unsetSort()
	 * @method \int fillSort()
	 * @method \string getModuleId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage resetModuleId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage unsetModuleId()
	 * @method \string fillModuleId()
	 * @method \string getSettings()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage setSettings(\string|\Bitrix\Main\DB\SqlExpression $settings)
	 * @method bool hasSettings()
	 * @method bool isSettingsFilled()
	 * @method bool isSettingsChanged()
	 * @method \string remindActualSettings()
	 * @method \string requireSettings()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage resetSettings()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage unsetSettings()
	 * @method \string fillSettings()
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
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage set($fieldName, $value)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage reset($fieldName)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage wakeUp($data)
	 */
	class EO_CustomSectionPage {
		/* @var \Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable */
		static public $dataClass = '\Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet\CustomSection\Entity {
	/**
	 * EO_CustomSectionPage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCustomSectionIdList()
	 * @method \int[] fillCustomSectionId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection[] getCustomSectionList()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection getCustomSectionCollection()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection_Collection fillCustomSection()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method \string[] getSettingsList()
	 * @method \string[] fillSettings()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage $object)
	 * @method bool has(\Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage getByPrimary($primary)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage[] getAll()
	 * @method bool remove(\Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_CustomSectionPage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable */
		static public $dataClass = '\Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable';
	}
}
namespace Bitrix\Intranet\CustomSection\Entity {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CustomSectionPage_Result exec()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage fetchObject()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CustomSectionPage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage fetchObject()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection fetchCollection()
	 */
	class EO_CustomSectionPage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection createCollection()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage wakeUpObject($row)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection wakeUpCollection($rows)
	 */
	class EO_CustomSectionPage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\CustomSection\Entity\CustomSectionTable:intranet\lib\CustomSection\Entity\CustomSectionTable.php:240c3323e9eaea3322eac848c3b18f9d */
namespace Bitrix\Intranet\CustomSection\Entity {
	/**
	 * EO_CustomSection
	 * @see \Bitrix\Intranet\CustomSection\Entity\CustomSectionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getCode()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection resetCode()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection unsetCode()
	 * @method \string fillCode()
	 * @method \string getTitle()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection resetTitle()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getModuleId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection setModuleId(\string|\Bitrix\Main\DB\SqlExpression $moduleId)
	 * @method bool hasModuleId()
	 * @method bool isModuleIdFilled()
	 * @method bool isModuleIdChanged()
	 * @method \string remindActualModuleId()
	 * @method \string requireModuleId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection resetModuleId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection unsetModuleId()
	 * @method \string fillModuleId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection getPages()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection requirePages()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection fillPages()
	 * @method bool hasPages()
	 * @method bool isPagesFilled()
	 * @method bool isPagesChanged()
	 * @method void addToPages(\Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage $customSectionPage)
	 * @method void removeFromPages(\Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage $customSectionPage)
	 * @method void removeAllPages()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection resetPages()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection unsetPages()
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
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection set($fieldName, $value)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection reset($fieldName)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection wakeUp($data)
	 */
	class EO_CustomSection {
		/* @var \Bitrix\Intranet\CustomSection\Entity\CustomSectionTable */
		static public $dataClass = '\Bitrix\Intranet\CustomSection\Entity\CustomSectionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet\CustomSection\Entity {
	/**
	 * EO_CustomSection_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getModuleIdList()
	 * @method \string[] fillModuleId()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection[] getPagesList()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection getPagesCollection()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection fillPages()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\CustomSection\Entity\EO_CustomSection $object)
	 * @method bool has(\Bitrix\Intranet\CustomSection\Entity\EO_CustomSection $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection getByPrimary($primary)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection[] getAll()
	 * @method bool remove(\Bitrix\Intranet\CustomSection\Entity\EO_CustomSection $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_CustomSection_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\CustomSection\Entity\CustomSectionTable */
		static public $dataClass = '\Bitrix\Intranet\CustomSection\Entity\CustomSectionTable';
	}
}
namespace Bitrix\Intranet\CustomSection\Entity {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CustomSection_Result exec()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection fetchObject()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CustomSection_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection fetchObject()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection_Collection fetchCollection()
	 */
	class EO_CustomSection_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection_Collection createCollection()
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection wakeUpObject($row)
	 * @method \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection_Collection wakeUpCollection($rows)
	 */
	class EO_CustomSection_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\Internals\InvitationTable:intranet\lib\internals\invitation.php:709e8968cf1d06a46897be1e8ee27f9f */
namespace Bitrix\Intranet\Internals {
	/**
	 * EO_Invitation
	 * @see \Bitrix\Intranet\Internals\InvitationTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation resetUserId()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getOriginatorId()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation setOriginatorId(\int|\Bitrix\Main\DB\SqlExpression $originatorId)
	 * @method bool hasOriginatorId()
	 * @method bool isOriginatorIdFilled()
	 * @method bool isOriginatorIdChanged()
	 * @method \int remindActualOriginatorId()
	 * @method \int requireOriginatorId()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation resetOriginatorId()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation unsetOriginatorId()
	 * @method \int fillOriginatorId()
	 * @method \string getInvitationType()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation setInvitationType(\string|\Bitrix\Main\DB\SqlExpression $invitationType)
	 * @method bool hasInvitationType()
	 * @method bool isInvitationTypeFilled()
	 * @method bool isInvitationTypeChanged()
	 * @method \string remindActualInvitationType()
	 * @method \string requireInvitationType()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation resetInvitationType()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation unsetInvitationType()
	 * @method \string fillInvitationType()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation resetDateCreate()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \boolean getInitialized()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation setInitialized(\boolean|\Bitrix\Main\DB\SqlExpression $initialized)
	 * @method bool hasInitialized()
	 * @method bool isInitializedFilled()
	 * @method bool isInitializedChanged()
	 * @method \boolean remindActualInitialized()
	 * @method \boolean requireInitialized()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation resetInitialized()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation unsetInitialized()
	 * @method \boolean fillInitialized()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Intranet\Internals\EO_Invitation resetUser()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Main\EO_User getOriginator()
	 * @method \Bitrix\Main\EO_User remindActualOriginator()
	 * @method \Bitrix\Main\EO_User requireOriginator()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation setOriginator(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Intranet\Internals\EO_Invitation resetOriginator()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation unsetOriginator()
	 * @method bool hasOriginator()
	 * @method bool isOriginatorFilled()
	 * @method bool isOriginatorChanged()
	 * @method \Bitrix\Main\EO_User fillOriginator()
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
	 * @method \Bitrix\Intranet\Internals\EO_Invitation set($fieldName, $value)
	 * @method \Bitrix\Intranet\Internals\EO_Invitation reset($fieldName)
	 * @method \Bitrix\Intranet\Internals\EO_Invitation unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\Internals\EO_Invitation wakeUp($data)
	 */
	class EO_Invitation {
		/* @var \Bitrix\Intranet\Internals\InvitationTable */
		static public $dataClass = '\Bitrix\Intranet\Internals\InvitationTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet\Internals {
	/**
	 * EO_Invitation_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getOriginatorIdList()
	 * @method \int[] fillOriginatorId()
	 * @method \string[] getInvitationTypeList()
	 * @method \string[] fillInvitationType()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \boolean[] getInitializedList()
	 * @method \boolean[] fillInitialized()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Main\EO_User[] getOriginatorList()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation_Collection getOriginatorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillOriginator()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\Internals\EO_Invitation $object)
	 * @method bool has(\Bitrix\Intranet\Internals\EO_Invitation $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\Internals\EO_Invitation getByPrimary($primary)
	 * @method \Bitrix\Intranet\Internals\EO_Invitation[] getAll()
	 * @method bool remove(\Bitrix\Intranet\Internals\EO_Invitation $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\Internals\EO_Invitation_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\Internals\EO_Invitation current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Invitation_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\Internals\InvitationTable */
		static public $dataClass = '\Bitrix\Intranet\Internals\InvitationTable';
	}
}
namespace Bitrix\Intranet\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Invitation_Result exec()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation fetchObject()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Invitation_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\Internals\EO_Invitation fetchObject()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation_Collection fetchCollection()
	 */
	class EO_Invitation_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\Internals\EO_Invitation createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\Internals\EO_Invitation_Collection createCollection()
	 * @method \Bitrix\Intranet\Internals\EO_Invitation wakeUpObject($row)
	 * @method \Bitrix\Intranet\Internals\EO_Invitation_Collection wakeUpCollection($rows)
	 */
	class EO_Invitation_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\Internals\QueueTable:intranet\lib\internals\queue.php:69b9079f32e4e92b5b0a95f700ae1277 */
namespace Bitrix\Intranet\Internals {
	/**
	 * EO_Queue
	 * @see \Bitrix\Intranet\Internals\QueueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getEntityType()
	 * @method \Bitrix\Intranet\Internals\EO_Queue setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string getEntityId()
	 * @method \Bitrix\Intranet\Internals\EO_Queue setEntityId(\string|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \string getLastItem()
	 * @method \Bitrix\Intranet\Internals\EO_Queue setLastItem(\string|\Bitrix\Main\DB\SqlExpression $lastItem)
	 * @method bool hasLastItem()
	 * @method bool isLastItemFilled()
	 * @method bool isLastItemChanged()
	 * @method \string remindActualLastItem()
	 * @method \string requireLastItem()
	 * @method \Bitrix\Intranet\Internals\EO_Queue resetLastItem()
	 * @method \Bitrix\Intranet\Internals\EO_Queue unsetLastItem()
	 * @method \string fillLastItem()
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
	 * @method \Bitrix\Intranet\Internals\EO_Queue set($fieldName, $value)
	 * @method \Bitrix\Intranet\Internals\EO_Queue reset($fieldName)
	 * @method \Bitrix\Intranet\Internals\EO_Queue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\Internals\EO_Queue wakeUp($data)
	 */
	class EO_Queue {
		/* @var \Bitrix\Intranet\Internals\QueueTable */
		static public $dataClass = '\Bitrix\Intranet\Internals\QueueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet\Internals {
	/**
	 * EO_Queue_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getEntityTypeList()
	 * @method \string[] getEntityIdList()
	 * @method \string[] getLastItemList()
	 * @method \string[] fillLastItem()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\Internals\EO_Queue $object)
	 * @method bool has(\Bitrix\Intranet\Internals\EO_Queue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\Internals\EO_Queue getByPrimary($primary)
	 * @method \Bitrix\Intranet\Internals\EO_Queue[] getAll()
	 * @method bool remove(\Bitrix\Intranet\Internals\EO_Queue $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\Internals\EO_Queue_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\Internals\EO_Queue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Queue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\Internals\QueueTable */
		static public $dataClass = '\Bitrix\Intranet\Internals\QueueTable';
	}
}
namespace Bitrix\Intranet\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Queue_Result exec()
	 * @method \Bitrix\Intranet\Internals\EO_Queue fetchObject()
	 * @method \Bitrix\Intranet\Internals\EO_Queue_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Queue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\Internals\EO_Queue fetchObject()
	 * @method \Bitrix\Intranet\Internals\EO_Queue_Collection fetchCollection()
	 */
	class EO_Queue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\Internals\EO_Queue createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\Internals\EO_Queue_Collection createCollection()
	 * @method \Bitrix\Intranet\Internals\EO_Queue wakeUpObject($row)
	 * @method \Bitrix\Intranet\Internals\EO_Queue_Collection wakeUpCollection($rows)
	 */
	class EO_Queue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\Internals\ThemeTable:intranet\lib\internals\theme.php:747c3fadab900c8ab24cfd781e308039 */
namespace Bitrix\Intranet\Internals {
	/**
	 * EO_Theme
	 * @see \Bitrix\Intranet\Internals\ThemeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getThemeId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme setThemeId(\string|\Bitrix\Main\DB\SqlExpression $themeId)
	 * @method bool hasThemeId()
	 * @method bool isThemeIdFilled()
	 * @method bool isThemeIdChanged()
	 * @method \string remindActualThemeId()
	 * @method \string requireThemeId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme resetThemeId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme unsetThemeId()
	 * @method \string fillThemeId()
	 * @method \int getUserId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme resetUserId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Intranet\Internals\EO_Theme setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Intranet\Internals\EO_Theme resetEntityType()
	 * @method \Bitrix\Intranet\Internals\EO_Theme unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \int getEntityId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme resetEntityId()
	 * @method \Bitrix\Intranet\Internals\EO_Theme unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getContext()
	 * @method \Bitrix\Intranet\Internals\EO_Theme setContext(\string|\Bitrix\Main\DB\SqlExpression $context)
	 * @method bool hasContext()
	 * @method bool isContextFilled()
	 * @method bool isContextChanged()
	 * @method \string remindActualContext()
	 * @method \string requireContext()
	 * @method \Bitrix\Intranet\Internals\EO_Theme resetContext()
	 * @method \Bitrix\Intranet\Internals\EO_Theme unsetContext()
	 * @method \string fillContext()
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
	 * @method \Bitrix\Intranet\Internals\EO_Theme set($fieldName, $value)
	 * @method \Bitrix\Intranet\Internals\EO_Theme reset($fieldName)
	 * @method \Bitrix\Intranet\Internals\EO_Theme unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\Internals\EO_Theme wakeUp($data)
	 */
	class EO_Theme {
		/* @var \Bitrix\Intranet\Internals\ThemeTable */
		static public $dataClass = '\Bitrix\Intranet\Internals\ThemeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet\Internals {
	/**
	 * EO_Theme_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getThemeIdList()
	 * @method \string[] fillThemeId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getContextList()
	 * @method \string[] fillContext()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\Internals\EO_Theme $object)
	 * @method bool has(\Bitrix\Intranet\Internals\EO_Theme $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\Internals\EO_Theme getByPrimary($primary)
	 * @method \Bitrix\Intranet\Internals\EO_Theme[] getAll()
	 * @method bool remove(\Bitrix\Intranet\Internals\EO_Theme $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\Internals\EO_Theme_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\Internals\EO_Theme current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Theme_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\Internals\ThemeTable */
		static public $dataClass = '\Bitrix\Intranet\Internals\ThemeTable';
	}
}
namespace Bitrix\Intranet\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Theme_Result exec()
	 * @method \Bitrix\Intranet\Internals\EO_Theme fetchObject()
	 * @method \Bitrix\Intranet\Internals\EO_Theme_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Theme_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\Internals\EO_Theme fetchObject()
	 * @method \Bitrix\Intranet\Internals\EO_Theme_Collection fetchCollection()
	 */
	class EO_Theme_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\Internals\EO_Theme createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\Internals\EO_Theme_Collection createCollection()
	 * @method \Bitrix\Intranet\Internals\EO_Theme wakeUpObject($row)
	 * @method \Bitrix\Intranet\Internals\EO_Theme_Collection wakeUpCollection($rows)
	 */
	class EO_Theme_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\RatingSubordinateTable:intranet\lib\ratingsubordinate.php:9618ca68cb323abbd24f0e569efd7caa */
namespace Bitrix\Intranet {
	/**
	 * EO_RatingSubordinate
	 * @see \Bitrix\Intranet\RatingSubordinateTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRatingId()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate setRatingId(\int|\Bitrix\Main\DB\SqlExpression $ratingId)
	 * @method bool hasRatingId()
	 * @method bool isRatingIdFilled()
	 * @method bool isRatingIdChanged()
	 * @method \int remindActualRatingId()
	 * @method \int requireRatingId()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate resetRatingId()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate unsetRatingId()
	 * @method \int fillRatingId()
	 * @method \int getEntityId()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate resetEntityId()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \float getVotes()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate setVotes(\float|\Bitrix\Main\DB\SqlExpression $votes)
	 * @method bool hasVotes()
	 * @method bool isVotesFilled()
	 * @method bool isVotesChanged()
	 * @method \float remindActualVotes()
	 * @method \float requireVotes()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate resetVotes()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate unsetVotes()
	 * @method \float fillVotes()
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
	 * @method \Bitrix\Intranet\EO_RatingSubordinate set($fieldName, $value)
	 * @method \Bitrix\Intranet\EO_RatingSubordinate reset($fieldName)
	 * @method \Bitrix\Intranet\EO_RatingSubordinate unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\EO_RatingSubordinate wakeUp($data)
	 */
	class EO_RatingSubordinate {
		/* @var \Bitrix\Intranet\RatingSubordinateTable */
		static public $dataClass = '\Bitrix\Intranet\RatingSubordinateTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet {
	/**
	 * EO_RatingSubordinate_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRatingIdList()
	 * @method \int[] fillRatingId()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \float[] getVotesList()
	 * @method \float[] fillVotes()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\EO_RatingSubordinate $object)
	 * @method bool has(\Bitrix\Intranet\EO_RatingSubordinate $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\EO_RatingSubordinate getByPrimary($primary)
	 * @method \Bitrix\Intranet\EO_RatingSubordinate[] getAll()
	 * @method bool remove(\Bitrix\Intranet\EO_RatingSubordinate $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\EO_RatingSubordinate_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\EO_RatingSubordinate current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RatingSubordinate_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\RatingSubordinateTable */
		static public $dataClass = '\Bitrix\Intranet\RatingSubordinateTable';
	}
}
namespace Bitrix\Intranet {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RatingSubordinate_Result exec()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate fetchObject()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RatingSubordinate_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\EO_RatingSubordinate fetchObject()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate_Collection fetchCollection()
	 */
	class EO_RatingSubordinate_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\EO_RatingSubordinate createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\EO_RatingSubordinate_Collection createCollection()
	 * @method \Bitrix\Intranet\EO_RatingSubordinate wakeUpObject($row)
	 * @method \Bitrix\Intranet\EO_RatingSubordinate_Collection wakeUpCollection($rows)
	 */
	class EO_RatingSubordinate_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\UserTable:intranet\lib\user.php:b51fa0a2ffd881f45afc5f5bca5023e5 */
namespace Bitrix\Intranet {
	/**
	 * EO_User
	 * @see \Bitrix\Intranet\UserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Intranet\EO_User setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getLogin()
	 * @method \Bitrix\Intranet\EO_User setLogin(\string|\Bitrix\Main\DB\SqlExpression $login)
	 * @method bool hasLogin()
	 * @method bool isLoginFilled()
	 * @method bool isLoginChanged()
	 * @method \string remindActualLogin()
	 * @method \string requireLogin()
	 * @method \Bitrix\Intranet\EO_User resetLogin()
	 * @method \Bitrix\Intranet\EO_User unsetLogin()
	 * @method \string fillLogin()
	 * @method \string getPassword()
	 * @method \Bitrix\Intranet\EO_User setPassword(\string|\Bitrix\Main\DB\SqlExpression $password)
	 * @method bool hasPassword()
	 * @method bool isPasswordFilled()
	 * @method bool isPasswordChanged()
	 * @method \string remindActualPassword()
	 * @method \string requirePassword()
	 * @method \Bitrix\Intranet\EO_User resetPassword()
	 * @method \Bitrix\Intranet\EO_User unsetPassword()
	 * @method \string fillPassword()
	 * @method \string getEmail()
	 * @method \Bitrix\Intranet\EO_User setEmail(\string|\Bitrix\Main\DB\SqlExpression $email)
	 * @method bool hasEmail()
	 * @method bool isEmailFilled()
	 * @method bool isEmailChanged()
	 * @method \string remindActualEmail()
	 * @method \string requireEmail()
	 * @method \Bitrix\Intranet\EO_User resetEmail()
	 * @method \Bitrix\Intranet\EO_User unsetEmail()
	 * @method \string fillEmail()
	 * @method \boolean getActive()
	 * @method \Bitrix\Intranet\EO_User setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Intranet\EO_User resetActive()
	 * @method \Bitrix\Intranet\EO_User unsetActive()
	 * @method \boolean fillActive()
	 * @method \boolean getBlocked()
	 * @method \Bitrix\Intranet\EO_User setBlocked(\boolean|\Bitrix\Main\DB\SqlExpression $blocked)
	 * @method bool hasBlocked()
	 * @method bool isBlockedFilled()
	 * @method bool isBlockedChanged()
	 * @method \boolean remindActualBlocked()
	 * @method \boolean requireBlocked()
	 * @method \Bitrix\Intranet\EO_User resetBlocked()
	 * @method \Bitrix\Intranet\EO_User unsetBlocked()
	 * @method \boolean fillBlocked()
	 * @method \Bitrix\Main\Type\DateTime getDateRegister()
	 * @method \Bitrix\Intranet\EO_User setDateRegister(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateRegister)
	 * @method bool hasDateRegister()
	 * @method bool isDateRegisterFilled()
	 * @method bool isDateRegisterChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateRegister()
	 * @method \Bitrix\Main\Type\DateTime requireDateRegister()
	 * @method \Bitrix\Intranet\EO_User resetDateRegister()
	 * @method \Bitrix\Intranet\EO_User unsetDateRegister()
	 * @method \Bitrix\Main\Type\DateTime fillDateRegister()
	 * @method \Bitrix\Main\Type\DateTime getDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime requireDateRegShort()
	 * @method bool hasDateRegShort()
	 * @method bool isDateRegShortFilled()
	 * @method \Bitrix\Intranet\EO_User unsetDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime fillDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime getLastLogin()
	 * @method \Bitrix\Intranet\EO_User setLastLogin(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastLogin)
	 * @method bool hasLastLogin()
	 * @method bool isLastLoginFilled()
	 * @method bool isLastLoginChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastLogin()
	 * @method \Bitrix\Main\Type\DateTime requireLastLogin()
	 * @method \Bitrix\Intranet\EO_User resetLastLogin()
	 * @method \Bitrix\Intranet\EO_User unsetLastLogin()
	 * @method \Bitrix\Main\Type\DateTime fillLastLogin()
	 * @method \Bitrix\Main\Type\DateTime getLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime requireLastLoginShort()
	 * @method bool hasLastLoginShort()
	 * @method bool isLastLoginShortFilled()
	 * @method \Bitrix\Intranet\EO_User unsetLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime fillLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime getLastActivityDate()
	 * @method \Bitrix\Intranet\EO_User setLastActivityDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastActivityDate)
	 * @method bool hasLastActivityDate()
	 * @method bool isLastActivityDateFilled()
	 * @method bool isLastActivityDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime requireLastActivityDate()
	 * @method \Bitrix\Intranet\EO_User resetLastActivityDate()
	 * @method \Bitrix\Intranet\EO_User unsetLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime fillLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Intranet\EO_User setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Intranet\EO_User resetTimestampX()
	 * @method \Bitrix\Intranet\EO_User unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \string getName()
	 * @method \Bitrix\Intranet\EO_User setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Intranet\EO_User resetName()
	 * @method \Bitrix\Intranet\EO_User unsetName()
	 * @method \string fillName()
	 * @method \string getSecondName()
	 * @method \Bitrix\Intranet\EO_User setSecondName(\string|\Bitrix\Main\DB\SqlExpression $secondName)
	 * @method bool hasSecondName()
	 * @method bool isSecondNameFilled()
	 * @method bool isSecondNameChanged()
	 * @method \string remindActualSecondName()
	 * @method \string requireSecondName()
	 * @method \Bitrix\Intranet\EO_User resetSecondName()
	 * @method \Bitrix\Intranet\EO_User unsetSecondName()
	 * @method \string fillSecondName()
	 * @method \string getLastName()
	 * @method \Bitrix\Intranet\EO_User setLastName(\string|\Bitrix\Main\DB\SqlExpression $lastName)
	 * @method bool hasLastName()
	 * @method bool isLastNameFilled()
	 * @method bool isLastNameChanged()
	 * @method \string remindActualLastName()
	 * @method \string requireLastName()
	 * @method \Bitrix\Intranet\EO_User resetLastName()
	 * @method \Bitrix\Intranet\EO_User unsetLastName()
	 * @method \string fillLastName()
	 * @method \string getTitle()
	 * @method \Bitrix\Intranet\EO_User setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Intranet\EO_User resetTitle()
	 * @method \Bitrix\Intranet\EO_User unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getExternalAuthId()
	 * @method \Bitrix\Intranet\EO_User setExternalAuthId(\string|\Bitrix\Main\DB\SqlExpression $externalAuthId)
	 * @method bool hasExternalAuthId()
	 * @method bool isExternalAuthIdFilled()
	 * @method bool isExternalAuthIdChanged()
	 * @method \string remindActualExternalAuthId()
	 * @method \string requireExternalAuthId()
	 * @method \Bitrix\Intranet\EO_User resetExternalAuthId()
	 * @method \Bitrix\Intranet\EO_User unsetExternalAuthId()
	 * @method \string fillExternalAuthId()
	 * @method \string getXmlId()
	 * @method \Bitrix\Intranet\EO_User setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Intranet\EO_User resetXmlId()
	 * @method \Bitrix\Intranet\EO_User unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \string getBxUserId()
	 * @method \Bitrix\Intranet\EO_User setBxUserId(\string|\Bitrix\Main\DB\SqlExpression $bxUserId)
	 * @method bool hasBxUserId()
	 * @method bool isBxUserIdFilled()
	 * @method bool isBxUserIdChanged()
	 * @method \string remindActualBxUserId()
	 * @method \string requireBxUserId()
	 * @method \Bitrix\Intranet\EO_User resetBxUserId()
	 * @method \Bitrix\Intranet\EO_User unsetBxUserId()
	 * @method \string fillBxUserId()
	 * @method \string getConfirmCode()
	 * @method \Bitrix\Intranet\EO_User setConfirmCode(\string|\Bitrix\Main\DB\SqlExpression $confirmCode)
	 * @method bool hasConfirmCode()
	 * @method bool isConfirmCodeFilled()
	 * @method bool isConfirmCodeChanged()
	 * @method \string remindActualConfirmCode()
	 * @method \string requireConfirmCode()
	 * @method \Bitrix\Intranet\EO_User resetConfirmCode()
	 * @method \Bitrix\Intranet\EO_User unsetConfirmCode()
	 * @method \string fillConfirmCode()
	 * @method \string getLid()
	 * @method \Bitrix\Intranet\EO_User setLid(\string|\Bitrix\Main\DB\SqlExpression $lid)
	 * @method bool hasLid()
	 * @method bool isLidFilled()
	 * @method bool isLidChanged()
	 * @method \string remindActualLid()
	 * @method \string requireLid()
	 * @method \Bitrix\Intranet\EO_User resetLid()
	 * @method \Bitrix\Intranet\EO_User unsetLid()
	 * @method \string fillLid()
	 * @method \string getLanguageId()
	 * @method \Bitrix\Intranet\EO_User setLanguageId(\string|\Bitrix\Main\DB\SqlExpression $languageId)
	 * @method bool hasLanguageId()
	 * @method bool isLanguageIdFilled()
	 * @method bool isLanguageIdChanged()
	 * @method \string remindActualLanguageId()
	 * @method \string requireLanguageId()
	 * @method \Bitrix\Intranet\EO_User resetLanguageId()
	 * @method \Bitrix\Intranet\EO_User unsetLanguageId()
	 * @method \string fillLanguageId()
	 * @method \string getTimeZone()
	 * @method \Bitrix\Intranet\EO_User setTimeZone(\string|\Bitrix\Main\DB\SqlExpression $timeZone)
	 * @method bool hasTimeZone()
	 * @method bool isTimeZoneFilled()
	 * @method bool isTimeZoneChanged()
	 * @method \string remindActualTimeZone()
	 * @method \string requireTimeZone()
	 * @method \Bitrix\Intranet\EO_User resetTimeZone()
	 * @method \Bitrix\Intranet\EO_User unsetTimeZone()
	 * @method \string fillTimeZone()
	 * @method \int getTimeZoneOffset()
	 * @method \Bitrix\Intranet\EO_User setTimeZoneOffset(\int|\Bitrix\Main\DB\SqlExpression $timeZoneOffset)
	 * @method bool hasTimeZoneOffset()
	 * @method bool isTimeZoneOffsetFilled()
	 * @method bool isTimeZoneOffsetChanged()
	 * @method \int remindActualTimeZoneOffset()
	 * @method \int requireTimeZoneOffset()
	 * @method \Bitrix\Intranet\EO_User resetTimeZoneOffset()
	 * @method \Bitrix\Intranet\EO_User unsetTimeZoneOffset()
	 * @method \int fillTimeZoneOffset()
	 * @method \string getPersonalProfession()
	 * @method \Bitrix\Intranet\EO_User setPersonalProfession(\string|\Bitrix\Main\DB\SqlExpression $personalProfession)
	 * @method bool hasPersonalProfession()
	 * @method bool isPersonalProfessionFilled()
	 * @method bool isPersonalProfessionChanged()
	 * @method \string remindActualPersonalProfession()
	 * @method \string requirePersonalProfession()
	 * @method \Bitrix\Intranet\EO_User resetPersonalProfession()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalProfession()
	 * @method \string fillPersonalProfession()
	 * @method \string getPersonalPhone()
	 * @method \Bitrix\Intranet\EO_User setPersonalPhone(\string|\Bitrix\Main\DB\SqlExpression $personalPhone)
	 * @method bool hasPersonalPhone()
	 * @method bool isPersonalPhoneFilled()
	 * @method bool isPersonalPhoneChanged()
	 * @method \string remindActualPersonalPhone()
	 * @method \string requirePersonalPhone()
	 * @method \Bitrix\Intranet\EO_User resetPersonalPhone()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalPhone()
	 * @method \string fillPersonalPhone()
	 * @method \string getPersonalMobile()
	 * @method \Bitrix\Intranet\EO_User setPersonalMobile(\string|\Bitrix\Main\DB\SqlExpression $personalMobile)
	 * @method bool hasPersonalMobile()
	 * @method bool isPersonalMobileFilled()
	 * @method bool isPersonalMobileChanged()
	 * @method \string remindActualPersonalMobile()
	 * @method \string requirePersonalMobile()
	 * @method \Bitrix\Intranet\EO_User resetPersonalMobile()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalMobile()
	 * @method \string fillPersonalMobile()
	 * @method \string getPersonalWww()
	 * @method \Bitrix\Intranet\EO_User setPersonalWww(\string|\Bitrix\Main\DB\SqlExpression $personalWww)
	 * @method bool hasPersonalWww()
	 * @method bool isPersonalWwwFilled()
	 * @method bool isPersonalWwwChanged()
	 * @method \string remindActualPersonalWww()
	 * @method \string requirePersonalWww()
	 * @method \Bitrix\Intranet\EO_User resetPersonalWww()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalWww()
	 * @method \string fillPersonalWww()
	 * @method \string getPersonalIcq()
	 * @method \Bitrix\Intranet\EO_User setPersonalIcq(\string|\Bitrix\Main\DB\SqlExpression $personalIcq)
	 * @method bool hasPersonalIcq()
	 * @method bool isPersonalIcqFilled()
	 * @method bool isPersonalIcqChanged()
	 * @method \string remindActualPersonalIcq()
	 * @method \string requirePersonalIcq()
	 * @method \Bitrix\Intranet\EO_User resetPersonalIcq()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalIcq()
	 * @method \string fillPersonalIcq()
	 * @method \string getPersonalFax()
	 * @method \Bitrix\Intranet\EO_User setPersonalFax(\string|\Bitrix\Main\DB\SqlExpression $personalFax)
	 * @method bool hasPersonalFax()
	 * @method bool isPersonalFaxFilled()
	 * @method bool isPersonalFaxChanged()
	 * @method \string remindActualPersonalFax()
	 * @method \string requirePersonalFax()
	 * @method \Bitrix\Intranet\EO_User resetPersonalFax()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalFax()
	 * @method \string fillPersonalFax()
	 * @method \string getPersonalPager()
	 * @method \Bitrix\Intranet\EO_User setPersonalPager(\string|\Bitrix\Main\DB\SqlExpression $personalPager)
	 * @method bool hasPersonalPager()
	 * @method bool isPersonalPagerFilled()
	 * @method bool isPersonalPagerChanged()
	 * @method \string remindActualPersonalPager()
	 * @method \string requirePersonalPager()
	 * @method \Bitrix\Intranet\EO_User resetPersonalPager()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalPager()
	 * @method \string fillPersonalPager()
	 * @method \string getPersonalStreet()
	 * @method \Bitrix\Intranet\EO_User setPersonalStreet(\string|\Bitrix\Main\DB\SqlExpression $personalStreet)
	 * @method bool hasPersonalStreet()
	 * @method bool isPersonalStreetFilled()
	 * @method bool isPersonalStreetChanged()
	 * @method \string remindActualPersonalStreet()
	 * @method \string requirePersonalStreet()
	 * @method \Bitrix\Intranet\EO_User resetPersonalStreet()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalStreet()
	 * @method \string fillPersonalStreet()
	 * @method \string getPersonalMailbox()
	 * @method \Bitrix\Intranet\EO_User setPersonalMailbox(\string|\Bitrix\Main\DB\SqlExpression $personalMailbox)
	 * @method bool hasPersonalMailbox()
	 * @method bool isPersonalMailboxFilled()
	 * @method bool isPersonalMailboxChanged()
	 * @method \string remindActualPersonalMailbox()
	 * @method \string requirePersonalMailbox()
	 * @method \Bitrix\Intranet\EO_User resetPersonalMailbox()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalMailbox()
	 * @method \string fillPersonalMailbox()
	 * @method \string getPersonalCity()
	 * @method \Bitrix\Intranet\EO_User setPersonalCity(\string|\Bitrix\Main\DB\SqlExpression $personalCity)
	 * @method bool hasPersonalCity()
	 * @method bool isPersonalCityFilled()
	 * @method bool isPersonalCityChanged()
	 * @method \string remindActualPersonalCity()
	 * @method \string requirePersonalCity()
	 * @method \Bitrix\Intranet\EO_User resetPersonalCity()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalCity()
	 * @method \string fillPersonalCity()
	 * @method \string getPersonalState()
	 * @method \Bitrix\Intranet\EO_User setPersonalState(\string|\Bitrix\Main\DB\SqlExpression $personalState)
	 * @method bool hasPersonalState()
	 * @method bool isPersonalStateFilled()
	 * @method bool isPersonalStateChanged()
	 * @method \string remindActualPersonalState()
	 * @method \string requirePersonalState()
	 * @method \Bitrix\Intranet\EO_User resetPersonalState()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalState()
	 * @method \string fillPersonalState()
	 * @method \string getPersonalZip()
	 * @method \Bitrix\Intranet\EO_User setPersonalZip(\string|\Bitrix\Main\DB\SqlExpression $personalZip)
	 * @method bool hasPersonalZip()
	 * @method bool isPersonalZipFilled()
	 * @method bool isPersonalZipChanged()
	 * @method \string remindActualPersonalZip()
	 * @method \string requirePersonalZip()
	 * @method \Bitrix\Intranet\EO_User resetPersonalZip()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalZip()
	 * @method \string fillPersonalZip()
	 * @method \string getPersonalCountry()
	 * @method \Bitrix\Intranet\EO_User setPersonalCountry(\string|\Bitrix\Main\DB\SqlExpression $personalCountry)
	 * @method bool hasPersonalCountry()
	 * @method bool isPersonalCountryFilled()
	 * @method bool isPersonalCountryChanged()
	 * @method \string remindActualPersonalCountry()
	 * @method \string requirePersonalCountry()
	 * @method \Bitrix\Intranet\EO_User resetPersonalCountry()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalCountry()
	 * @method \string fillPersonalCountry()
	 * @method \Bitrix\Main\Type\Date getPersonalBirthday()
	 * @method \Bitrix\Intranet\EO_User setPersonalBirthday(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $personalBirthday)
	 * @method bool hasPersonalBirthday()
	 * @method bool isPersonalBirthdayFilled()
	 * @method bool isPersonalBirthdayChanged()
	 * @method \Bitrix\Main\Type\Date remindActualPersonalBirthday()
	 * @method \Bitrix\Main\Type\Date requirePersonalBirthday()
	 * @method \Bitrix\Intranet\EO_User resetPersonalBirthday()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalBirthday()
	 * @method \Bitrix\Main\Type\Date fillPersonalBirthday()
	 * @method \string getPersonalGender()
	 * @method \Bitrix\Intranet\EO_User setPersonalGender(\string|\Bitrix\Main\DB\SqlExpression $personalGender)
	 * @method bool hasPersonalGender()
	 * @method bool isPersonalGenderFilled()
	 * @method bool isPersonalGenderChanged()
	 * @method \string remindActualPersonalGender()
	 * @method \string requirePersonalGender()
	 * @method \Bitrix\Intranet\EO_User resetPersonalGender()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalGender()
	 * @method \string fillPersonalGender()
	 * @method \int getPersonalPhoto()
	 * @method \Bitrix\Intranet\EO_User setPersonalPhoto(\int|\Bitrix\Main\DB\SqlExpression $personalPhoto)
	 * @method bool hasPersonalPhoto()
	 * @method bool isPersonalPhotoFilled()
	 * @method bool isPersonalPhotoChanged()
	 * @method \int remindActualPersonalPhoto()
	 * @method \int requirePersonalPhoto()
	 * @method \Bitrix\Intranet\EO_User resetPersonalPhoto()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalPhoto()
	 * @method \int fillPersonalPhoto()
	 * @method \string getPersonalNotes()
	 * @method \Bitrix\Intranet\EO_User setPersonalNotes(\string|\Bitrix\Main\DB\SqlExpression $personalNotes)
	 * @method bool hasPersonalNotes()
	 * @method bool isPersonalNotesFilled()
	 * @method bool isPersonalNotesChanged()
	 * @method \string remindActualPersonalNotes()
	 * @method \string requirePersonalNotes()
	 * @method \Bitrix\Intranet\EO_User resetPersonalNotes()
	 * @method \Bitrix\Intranet\EO_User unsetPersonalNotes()
	 * @method \string fillPersonalNotes()
	 * @method \string getWorkCompany()
	 * @method \Bitrix\Intranet\EO_User setWorkCompany(\string|\Bitrix\Main\DB\SqlExpression $workCompany)
	 * @method bool hasWorkCompany()
	 * @method bool isWorkCompanyFilled()
	 * @method bool isWorkCompanyChanged()
	 * @method \string remindActualWorkCompany()
	 * @method \string requireWorkCompany()
	 * @method \Bitrix\Intranet\EO_User resetWorkCompany()
	 * @method \Bitrix\Intranet\EO_User unsetWorkCompany()
	 * @method \string fillWorkCompany()
	 * @method \string getWorkDepartment()
	 * @method \Bitrix\Intranet\EO_User setWorkDepartment(\string|\Bitrix\Main\DB\SqlExpression $workDepartment)
	 * @method bool hasWorkDepartment()
	 * @method bool isWorkDepartmentFilled()
	 * @method bool isWorkDepartmentChanged()
	 * @method \string remindActualWorkDepartment()
	 * @method \string requireWorkDepartment()
	 * @method \Bitrix\Intranet\EO_User resetWorkDepartment()
	 * @method \Bitrix\Intranet\EO_User unsetWorkDepartment()
	 * @method \string fillWorkDepartment()
	 * @method \string getWorkPhone()
	 * @method \Bitrix\Intranet\EO_User setWorkPhone(\string|\Bitrix\Main\DB\SqlExpression $workPhone)
	 * @method bool hasWorkPhone()
	 * @method bool isWorkPhoneFilled()
	 * @method bool isWorkPhoneChanged()
	 * @method \string remindActualWorkPhone()
	 * @method \string requireWorkPhone()
	 * @method \Bitrix\Intranet\EO_User resetWorkPhone()
	 * @method \Bitrix\Intranet\EO_User unsetWorkPhone()
	 * @method \string fillWorkPhone()
	 * @method \string getWorkPosition()
	 * @method \Bitrix\Intranet\EO_User setWorkPosition(\string|\Bitrix\Main\DB\SqlExpression $workPosition)
	 * @method bool hasWorkPosition()
	 * @method bool isWorkPositionFilled()
	 * @method bool isWorkPositionChanged()
	 * @method \string remindActualWorkPosition()
	 * @method \string requireWorkPosition()
	 * @method \Bitrix\Intranet\EO_User resetWorkPosition()
	 * @method \Bitrix\Intranet\EO_User unsetWorkPosition()
	 * @method \string fillWorkPosition()
	 * @method \string getWorkWww()
	 * @method \Bitrix\Intranet\EO_User setWorkWww(\string|\Bitrix\Main\DB\SqlExpression $workWww)
	 * @method bool hasWorkWww()
	 * @method bool isWorkWwwFilled()
	 * @method bool isWorkWwwChanged()
	 * @method \string remindActualWorkWww()
	 * @method \string requireWorkWww()
	 * @method \Bitrix\Intranet\EO_User resetWorkWww()
	 * @method \Bitrix\Intranet\EO_User unsetWorkWww()
	 * @method \string fillWorkWww()
	 * @method \string getWorkFax()
	 * @method \Bitrix\Intranet\EO_User setWorkFax(\string|\Bitrix\Main\DB\SqlExpression $workFax)
	 * @method bool hasWorkFax()
	 * @method bool isWorkFaxFilled()
	 * @method bool isWorkFaxChanged()
	 * @method \string remindActualWorkFax()
	 * @method \string requireWorkFax()
	 * @method \Bitrix\Intranet\EO_User resetWorkFax()
	 * @method \Bitrix\Intranet\EO_User unsetWorkFax()
	 * @method \string fillWorkFax()
	 * @method \string getWorkPager()
	 * @method \Bitrix\Intranet\EO_User setWorkPager(\string|\Bitrix\Main\DB\SqlExpression $workPager)
	 * @method bool hasWorkPager()
	 * @method bool isWorkPagerFilled()
	 * @method bool isWorkPagerChanged()
	 * @method \string remindActualWorkPager()
	 * @method \string requireWorkPager()
	 * @method \Bitrix\Intranet\EO_User resetWorkPager()
	 * @method \Bitrix\Intranet\EO_User unsetWorkPager()
	 * @method \string fillWorkPager()
	 * @method \string getWorkStreet()
	 * @method \Bitrix\Intranet\EO_User setWorkStreet(\string|\Bitrix\Main\DB\SqlExpression $workStreet)
	 * @method bool hasWorkStreet()
	 * @method bool isWorkStreetFilled()
	 * @method bool isWorkStreetChanged()
	 * @method \string remindActualWorkStreet()
	 * @method \string requireWorkStreet()
	 * @method \Bitrix\Intranet\EO_User resetWorkStreet()
	 * @method \Bitrix\Intranet\EO_User unsetWorkStreet()
	 * @method \string fillWorkStreet()
	 * @method \string getWorkMailbox()
	 * @method \Bitrix\Intranet\EO_User setWorkMailbox(\string|\Bitrix\Main\DB\SqlExpression $workMailbox)
	 * @method bool hasWorkMailbox()
	 * @method bool isWorkMailboxFilled()
	 * @method bool isWorkMailboxChanged()
	 * @method \string remindActualWorkMailbox()
	 * @method \string requireWorkMailbox()
	 * @method \Bitrix\Intranet\EO_User resetWorkMailbox()
	 * @method \Bitrix\Intranet\EO_User unsetWorkMailbox()
	 * @method \string fillWorkMailbox()
	 * @method \string getWorkCity()
	 * @method \Bitrix\Intranet\EO_User setWorkCity(\string|\Bitrix\Main\DB\SqlExpression $workCity)
	 * @method bool hasWorkCity()
	 * @method bool isWorkCityFilled()
	 * @method bool isWorkCityChanged()
	 * @method \string remindActualWorkCity()
	 * @method \string requireWorkCity()
	 * @method \Bitrix\Intranet\EO_User resetWorkCity()
	 * @method \Bitrix\Intranet\EO_User unsetWorkCity()
	 * @method \string fillWorkCity()
	 * @method \string getWorkState()
	 * @method \Bitrix\Intranet\EO_User setWorkState(\string|\Bitrix\Main\DB\SqlExpression $workState)
	 * @method bool hasWorkState()
	 * @method bool isWorkStateFilled()
	 * @method bool isWorkStateChanged()
	 * @method \string remindActualWorkState()
	 * @method \string requireWorkState()
	 * @method \Bitrix\Intranet\EO_User resetWorkState()
	 * @method \Bitrix\Intranet\EO_User unsetWorkState()
	 * @method \string fillWorkState()
	 * @method \string getWorkZip()
	 * @method \Bitrix\Intranet\EO_User setWorkZip(\string|\Bitrix\Main\DB\SqlExpression $workZip)
	 * @method bool hasWorkZip()
	 * @method bool isWorkZipFilled()
	 * @method bool isWorkZipChanged()
	 * @method \string remindActualWorkZip()
	 * @method \string requireWorkZip()
	 * @method \Bitrix\Intranet\EO_User resetWorkZip()
	 * @method \Bitrix\Intranet\EO_User unsetWorkZip()
	 * @method \string fillWorkZip()
	 * @method \string getWorkCountry()
	 * @method \Bitrix\Intranet\EO_User setWorkCountry(\string|\Bitrix\Main\DB\SqlExpression $workCountry)
	 * @method bool hasWorkCountry()
	 * @method bool isWorkCountryFilled()
	 * @method bool isWorkCountryChanged()
	 * @method \string remindActualWorkCountry()
	 * @method \string requireWorkCountry()
	 * @method \Bitrix\Intranet\EO_User resetWorkCountry()
	 * @method \Bitrix\Intranet\EO_User unsetWorkCountry()
	 * @method \string fillWorkCountry()
	 * @method \string getWorkProfile()
	 * @method \Bitrix\Intranet\EO_User setWorkProfile(\string|\Bitrix\Main\DB\SqlExpression $workProfile)
	 * @method bool hasWorkProfile()
	 * @method bool isWorkProfileFilled()
	 * @method bool isWorkProfileChanged()
	 * @method \string remindActualWorkProfile()
	 * @method \string requireWorkProfile()
	 * @method \Bitrix\Intranet\EO_User resetWorkProfile()
	 * @method \Bitrix\Intranet\EO_User unsetWorkProfile()
	 * @method \string fillWorkProfile()
	 * @method \int getWorkLogo()
	 * @method \Bitrix\Intranet\EO_User setWorkLogo(\int|\Bitrix\Main\DB\SqlExpression $workLogo)
	 * @method bool hasWorkLogo()
	 * @method bool isWorkLogoFilled()
	 * @method bool isWorkLogoChanged()
	 * @method \int remindActualWorkLogo()
	 * @method \int requireWorkLogo()
	 * @method \Bitrix\Intranet\EO_User resetWorkLogo()
	 * @method \Bitrix\Intranet\EO_User unsetWorkLogo()
	 * @method \int fillWorkLogo()
	 * @method \string getWorkNotes()
	 * @method \Bitrix\Intranet\EO_User setWorkNotes(\string|\Bitrix\Main\DB\SqlExpression $workNotes)
	 * @method bool hasWorkNotes()
	 * @method bool isWorkNotesFilled()
	 * @method bool isWorkNotesChanged()
	 * @method \string remindActualWorkNotes()
	 * @method \string requireWorkNotes()
	 * @method \Bitrix\Intranet\EO_User resetWorkNotes()
	 * @method \Bitrix\Intranet\EO_User unsetWorkNotes()
	 * @method \string fillWorkNotes()
	 * @method \string getAdminNotes()
	 * @method \Bitrix\Intranet\EO_User setAdminNotes(\string|\Bitrix\Main\DB\SqlExpression $adminNotes)
	 * @method bool hasAdminNotes()
	 * @method bool isAdminNotesFilled()
	 * @method bool isAdminNotesChanged()
	 * @method \string remindActualAdminNotes()
	 * @method \string requireAdminNotes()
	 * @method \Bitrix\Intranet\EO_User resetAdminNotes()
	 * @method \Bitrix\Intranet\EO_User unsetAdminNotes()
	 * @method \string fillAdminNotes()
	 * @method \string getShortName()
	 * @method \string remindActualShortName()
	 * @method \string requireShortName()
	 * @method bool hasShortName()
	 * @method bool isShortNameFilled()
	 * @method \Bitrix\Intranet\EO_User unsetShortName()
	 * @method \string fillShortName()
	 * @method \boolean getIsOnline()
	 * @method \boolean remindActualIsOnline()
	 * @method \boolean requireIsOnline()
	 * @method bool hasIsOnline()
	 * @method bool isIsOnlineFilled()
	 * @method \Bitrix\Intranet\EO_User unsetIsOnline()
	 * @method \boolean fillIsOnline()
	 * @method \boolean getIsRealUser()
	 * @method \boolean remindActualIsRealUser()
	 * @method \boolean requireIsRealUser()
	 * @method bool hasIsRealUser()
	 * @method bool isIsRealUserFilled()
	 * @method \Bitrix\Intranet\EO_User unsetIsRealUser()
	 * @method \boolean fillIsRealUser()
	 * @method \Bitrix\Main\EO_UserIndex getIndex()
	 * @method \Bitrix\Main\EO_UserIndex remindActualIndex()
	 * @method \Bitrix\Main\EO_UserIndex requireIndex()
	 * @method \Bitrix\Intranet\EO_User setIndex(\Bitrix\Main\EO_UserIndex $object)
	 * @method \Bitrix\Intranet\EO_User resetIndex()
	 * @method \Bitrix\Intranet\EO_User unsetIndex()
	 * @method bool hasIndex()
	 * @method bool isIndexFilled()
	 * @method bool isIndexChanged()
	 * @method \Bitrix\Main\EO_UserIndex fillIndex()
	 * @method \Bitrix\Main\EO_UserCounter getCounter()
	 * @method \Bitrix\Main\EO_UserCounter remindActualCounter()
	 * @method \Bitrix\Main\EO_UserCounter requireCounter()
	 * @method \Bitrix\Intranet\EO_User setCounter(\Bitrix\Main\EO_UserCounter $object)
	 * @method \Bitrix\Intranet\EO_User resetCounter()
	 * @method \Bitrix\Intranet\EO_User unsetCounter()
	 * @method bool hasCounter()
	 * @method bool isCounterFilled()
	 * @method bool isCounterChanged()
	 * @method \Bitrix\Main\EO_UserCounter fillCounter()
	 * @method \Bitrix\Main\EO_UserPhoneAuth getPhoneAuth()
	 * @method \Bitrix\Main\EO_UserPhoneAuth remindActualPhoneAuth()
	 * @method \Bitrix\Main\EO_UserPhoneAuth requirePhoneAuth()
	 * @method \Bitrix\Intranet\EO_User setPhoneAuth(\Bitrix\Main\EO_UserPhoneAuth $object)
	 * @method \Bitrix\Intranet\EO_User resetPhoneAuth()
	 * @method \Bitrix\Intranet\EO_User unsetPhoneAuth()
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
	 * @method \Bitrix\Intranet\EO_User resetGroups()
	 * @method \Bitrix\Intranet\EO_User unsetGroups()
	 * @method \string getUserType()
	 * @method \string remindActualUserType()
	 * @method \string requireUserType()
	 * @method bool hasUserType()
	 * @method bool isUserTypeFilled()
	 * @method \Bitrix\Intranet\EO_User unsetUserType()
	 * @method \string fillUserType()
	 * @method \Bitrix\Socialnetwork\EO_UserTag_Collection getTags()
	 * @method \Bitrix\Socialnetwork\EO_UserTag_Collection requireTags()
	 * @method \Bitrix\Socialnetwork\EO_UserTag_Collection fillTags()
	 * @method bool hasTags()
	 * @method bool isTagsFilled()
	 * @method bool isTagsChanged()
	 * @method void addToTags(\Bitrix\Socialnetwork\EO_UserTag $userTag)
	 * @method void removeFromTags(\Bitrix\Socialnetwork\EO_UserTag $userTag)
	 * @method void removeAllTags()
	 * @method \Bitrix\Intranet\EO_User resetTags()
	 * @method \Bitrix\Intranet\EO_User unsetTags()
	 * @method \string getUserTypeInner()
	 * @method \string remindActualUserTypeInner()
	 * @method \string requireUserTypeInner()
	 * @method bool hasUserTypeInner()
	 * @method bool isUserTypeInnerFilled()
	 * @method \Bitrix\Intranet\EO_User unsetUserTypeInner()
	 * @method \string fillUserTypeInner()
	 * @method \string getUserTypeIsEmployee()
	 * @method \string remindActualUserTypeIsEmployee()
	 * @method \string requireUserTypeIsEmployee()
	 * @method bool hasUserTypeIsEmployee()
	 * @method bool isUserTypeIsEmployeeFilled()
	 * @method \Bitrix\Intranet\EO_User unsetUserTypeIsEmployee()
	 * @method \string fillUserTypeIsEmployee()
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
	 * @method \Bitrix\Intranet\EO_User set($fieldName, $value)
	 * @method \Bitrix\Intranet\EO_User reset($fieldName)
	 * @method \Bitrix\Intranet\EO_User unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\EO_User wakeUp($data)
	 */
	class EO_User {
		/* @var \Bitrix\Intranet\UserTable */
		static public $dataClass = '\Bitrix\Intranet\UserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet {
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
	 * @method \Bitrix\Intranet\EO_User_Collection getIndexCollection()
	 * @method \Bitrix\Main\EO_UserIndex_Collection fillIndex()
	 * @method \Bitrix\Main\EO_UserCounter[] getCounterList()
	 * @method \Bitrix\Intranet\EO_User_Collection getCounterCollection()
	 * @method \Bitrix\Main\EO_UserCounter_Collection fillCounter()
	 * @method \Bitrix\Main\EO_UserPhoneAuth[] getPhoneAuthList()
	 * @method \Bitrix\Intranet\EO_User_Collection getPhoneAuthCollection()
	 * @method \Bitrix\Main\EO_UserPhoneAuth_Collection fillPhoneAuth()
	 * @method \Bitrix\Main\EO_UserGroup_Collection[] getGroupsList()
	 * @method \Bitrix\Main\EO_UserGroup_Collection getGroupsCollection()
	 * @method \Bitrix\Main\EO_UserGroup_Collection fillGroups()
	 * @method \string[] getUserTypeList()
	 * @method \string[] fillUserType()
	 * @method \Bitrix\Socialnetwork\EO_UserTag_Collection[] getTagsList()
	 * @method \Bitrix\Socialnetwork\EO_UserTag_Collection getTagsCollection()
	 * @method \Bitrix\Socialnetwork\EO_UserTag_Collection fillTags()
	 * @method \string[] getUserTypeInnerList()
	 * @method \string[] fillUserTypeInner()
	 * @method \string[] getUserTypeIsEmployeeList()
	 * @method \string[] fillUserTypeIsEmployee()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\EO_User $object)
	 * @method bool has(\Bitrix\Intranet\EO_User $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\EO_User getByPrimary($primary)
	 * @method \Bitrix\Intranet\EO_User[] getAll()
	 * @method bool remove(\Bitrix\Intranet\EO_User $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\EO_User_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\EO_User current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_User_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\UserTable */
		static public $dataClass = '\Bitrix\Intranet\UserTable';
	}
}
namespace Bitrix\Intranet {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_User_Result exec()
	 * @method \Bitrix\Intranet\EO_User fetchObject()
	 * @method \Bitrix\Intranet\EO_User_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_User_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\EO_User fetchObject()
	 * @method \Bitrix\Intranet\EO_User_Collection fetchCollection()
	 */
	class EO_User_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\EO_User createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\EO_User_Collection createCollection()
	 * @method \Bitrix\Intranet\EO_User wakeUpObject($row)
	 * @method \Bitrix\Intranet\EO_User_Collection wakeUpCollection($rows)
	 */
	class EO_User_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\UStat\DepartmentDayTable:intranet\lib\ustat\departmentday.php:858e16cabd2beb2d998f7c11304c6dc3 */
namespace Bitrix\Intranet\UStat {
	/**
	 * EO_DepartmentDay
	 * @see \Bitrix\Intranet\UStat\DepartmentDayTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getDeptId()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setDeptId(\int|\Bitrix\Main\DB\SqlExpression $deptId)
	 * @method bool hasDeptId()
	 * @method bool isDeptIdFilled()
	 * @method bool isDeptIdChanged()
	 * @method \Bitrix\Main\Type\Date getDay()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setDay(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $day)
	 * @method bool hasDay()
	 * @method bool isDayFilled()
	 * @method bool isDayChanged()
	 * @method \int getActiveUsers()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setActiveUsers(\int|\Bitrix\Main\DB\SqlExpression $activeUsers)
	 * @method bool hasActiveUsers()
	 * @method bool isActiveUsersFilled()
	 * @method bool isActiveUsersChanged()
	 * @method \int remindActualActiveUsers()
	 * @method \int requireActiveUsers()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetActiveUsers()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetActiveUsers()
	 * @method \int fillActiveUsers()
	 * @method \int getInvolvement()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setInvolvement(\int|\Bitrix\Main\DB\SqlExpression $involvement)
	 * @method bool hasInvolvement()
	 * @method bool isInvolvementFilled()
	 * @method bool isInvolvementChanged()
	 * @method \int remindActualInvolvement()
	 * @method \int requireInvolvement()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetInvolvement()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetInvolvement()
	 * @method \int fillInvolvement()
	 * @method \int getTotal()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setTotal(\int|\Bitrix\Main\DB\SqlExpression $total)
	 * @method bool hasTotal()
	 * @method bool isTotalFilled()
	 * @method bool isTotalChanged()
	 * @method \int remindActualTotal()
	 * @method \int requireTotal()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetTotal()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetTotal()
	 * @method \int fillTotal()
	 * @method \int getSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setSocnet(\int|\Bitrix\Main\DB\SqlExpression $socnet)
	 * @method bool hasSocnet()
	 * @method bool isSocnetFilled()
	 * @method bool isSocnetChanged()
	 * @method \int remindActualSocnet()
	 * @method \int requireSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetSocnet()
	 * @method \int fillSocnet()
	 * @method \int getLikes()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setLikes(\int|\Bitrix\Main\DB\SqlExpression $likes)
	 * @method bool hasLikes()
	 * @method bool isLikesFilled()
	 * @method bool isLikesChanged()
	 * @method \int remindActualLikes()
	 * @method \int requireLikes()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetLikes()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetLikes()
	 * @method \int fillLikes()
	 * @method \int getTasks()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setTasks(\int|\Bitrix\Main\DB\SqlExpression $tasks)
	 * @method bool hasTasks()
	 * @method bool isTasksFilled()
	 * @method bool isTasksChanged()
	 * @method \int remindActualTasks()
	 * @method \int requireTasks()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetTasks()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetTasks()
	 * @method \int fillTasks()
	 * @method \int getIm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setIm(\int|\Bitrix\Main\DB\SqlExpression $im)
	 * @method bool hasIm()
	 * @method bool isImFilled()
	 * @method bool isImChanged()
	 * @method \int remindActualIm()
	 * @method \int requireIm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetIm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetIm()
	 * @method \int fillIm()
	 * @method \int getDisk()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setDisk(\int|\Bitrix\Main\DB\SqlExpression $disk)
	 * @method bool hasDisk()
	 * @method bool isDiskFilled()
	 * @method bool isDiskChanged()
	 * @method \int remindActualDisk()
	 * @method \int requireDisk()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetDisk()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetDisk()
	 * @method \int fillDisk()
	 * @method \int getMobile()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setMobile(\int|\Bitrix\Main\DB\SqlExpression $mobile)
	 * @method bool hasMobile()
	 * @method bool isMobileFilled()
	 * @method bool isMobileChanged()
	 * @method \int remindActualMobile()
	 * @method \int requireMobile()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetMobile()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetMobile()
	 * @method \int fillMobile()
	 * @method \int getCrm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay setCrm(\int|\Bitrix\Main\DB\SqlExpression $crm)
	 * @method bool hasCrm()
	 * @method bool isCrmFilled()
	 * @method bool isCrmChanged()
	 * @method \int remindActualCrm()
	 * @method \int requireCrm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay resetCrm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unsetCrm()
	 * @method \int fillCrm()
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
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay set($fieldName, $value)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay reset($fieldName)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\UStat\EO_DepartmentDay wakeUp($data)
	 */
	class EO_DepartmentDay {
		/* @var \Bitrix\Intranet\UStat\DepartmentDayTable */
		static public $dataClass = '\Bitrix\Intranet\UStat\DepartmentDayTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet\UStat {
	/**
	 * EO_DepartmentDay_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getDeptIdList()
	 * @method \Bitrix\Main\Type\Date[] getDayList()
	 * @method \int[] getActiveUsersList()
	 * @method \int[] fillActiveUsers()
	 * @method \int[] getInvolvementList()
	 * @method \int[] fillInvolvement()
	 * @method \int[] getTotalList()
	 * @method \int[] fillTotal()
	 * @method \int[] getSocnetList()
	 * @method \int[] fillSocnet()
	 * @method \int[] getLikesList()
	 * @method \int[] fillLikes()
	 * @method \int[] getTasksList()
	 * @method \int[] fillTasks()
	 * @method \int[] getImList()
	 * @method \int[] fillIm()
	 * @method \int[] getDiskList()
	 * @method \int[] fillDisk()
	 * @method \int[] getMobileList()
	 * @method \int[] fillMobile()
	 * @method \int[] getCrmList()
	 * @method \int[] fillCrm()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\UStat\EO_DepartmentDay $object)
	 * @method bool has(\Bitrix\Intranet\UStat\EO_DepartmentDay $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay getByPrimary($primary)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay[] getAll()
	 * @method bool remove(\Bitrix\Intranet\UStat\EO_DepartmentDay $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\UStat\EO_DepartmentDay_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_DepartmentDay_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\UStat\DepartmentDayTable */
		static public $dataClass = '\Bitrix\Intranet\UStat\DepartmentDayTable';
	}
}
namespace Bitrix\Intranet\UStat {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DepartmentDay_Result exec()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay fetchObject()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_DepartmentDay_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay fetchObject()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay_Collection fetchCollection()
	 */
	class EO_DepartmentDay_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay_Collection createCollection()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay wakeUpObject($row)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentDay_Collection wakeUpCollection($rows)
	 */
	class EO_DepartmentDay_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\UStat\DepartmentHourTable:intranet\lib\ustat\departmenthour.php:dd6c639e2b23b87930c09f15b18ff69f */
namespace Bitrix\Intranet\UStat {
	/**
	 * EO_DepartmentHour
	 * @see \Bitrix\Intranet\UStat\DepartmentHourTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getDeptId()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setDeptId(\int|\Bitrix\Main\DB\SqlExpression $deptId)
	 * @method bool hasDeptId()
	 * @method bool isDeptIdFilled()
	 * @method bool isDeptIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getHour()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setHour(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $hour)
	 * @method bool hasHour()
	 * @method bool isHourFilled()
	 * @method bool isHourChanged()
	 * @method \int getTotal()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setTotal(\int|\Bitrix\Main\DB\SqlExpression $total)
	 * @method bool hasTotal()
	 * @method bool isTotalFilled()
	 * @method bool isTotalChanged()
	 * @method \int remindActualTotal()
	 * @method \int requireTotal()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour resetTotal()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour unsetTotal()
	 * @method \int fillTotal()
	 * @method \int getSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setSocnet(\int|\Bitrix\Main\DB\SqlExpression $socnet)
	 * @method bool hasSocnet()
	 * @method bool isSocnetFilled()
	 * @method bool isSocnetChanged()
	 * @method \int remindActualSocnet()
	 * @method \int requireSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour resetSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour unsetSocnet()
	 * @method \int fillSocnet()
	 * @method \int getLikes()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setLikes(\int|\Bitrix\Main\DB\SqlExpression $likes)
	 * @method bool hasLikes()
	 * @method bool isLikesFilled()
	 * @method bool isLikesChanged()
	 * @method \int remindActualLikes()
	 * @method \int requireLikes()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour resetLikes()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour unsetLikes()
	 * @method \int fillLikes()
	 * @method \int getTasks()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setTasks(\int|\Bitrix\Main\DB\SqlExpression $tasks)
	 * @method bool hasTasks()
	 * @method bool isTasksFilled()
	 * @method bool isTasksChanged()
	 * @method \int remindActualTasks()
	 * @method \int requireTasks()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour resetTasks()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour unsetTasks()
	 * @method \int fillTasks()
	 * @method \int getIm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setIm(\int|\Bitrix\Main\DB\SqlExpression $im)
	 * @method bool hasIm()
	 * @method bool isImFilled()
	 * @method bool isImChanged()
	 * @method \int remindActualIm()
	 * @method \int requireIm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour resetIm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour unsetIm()
	 * @method \int fillIm()
	 * @method \int getDisk()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setDisk(\int|\Bitrix\Main\DB\SqlExpression $disk)
	 * @method bool hasDisk()
	 * @method bool isDiskFilled()
	 * @method bool isDiskChanged()
	 * @method \int remindActualDisk()
	 * @method \int requireDisk()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour resetDisk()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour unsetDisk()
	 * @method \int fillDisk()
	 * @method \int getMobile()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setMobile(\int|\Bitrix\Main\DB\SqlExpression $mobile)
	 * @method bool hasMobile()
	 * @method bool isMobileFilled()
	 * @method bool isMobileChanged()
	 * @method \int remindActualMobile()
	 * @method \int requireMobile()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour resetMobile()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour unsetMobile()
	 * @method \int fillMobile()
	 * @method \int getCrm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour setCrm(\int|\Bitrix\Main\DB\SqlExpression $crm)
	 * @method bool hasCrm()
	 * @method bool isCrmFilled()
	 * @method bool isCrmChanged()
	 * @method \int remindActualCrm()
	 * @method \int requireCrm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour resetCrm()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour unsetCrm()
	 * @method \int fillCrm()
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
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour set($fieldName, $value)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour reset($fieldName)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\UStat\EO_DepartmentHour wakeUp($data)
	 */
	class EO_DepartmentHour {
		/* @var \Bitrix\Intranet\UStat\DepartmentHourTable */
		static public $dataClass = '\Bitrix\Intranet\UStat\DepartmentHourTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet\UStat {
	/**
	 * EO_DepartmentHour_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getDeptIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getHourList()
	 * @method \int[] getTotalList()
	 * @method \int[] fillTotal()
	 * @method \int[] getSocnetList()
	 * @method \int[] fillSocnet()
	 * @method \int[] getLikesList()
	 * @method \int[] fillLikes()
	 * @method \int[] getTasksList()
	 * @method \int[] fillTasks()
	 * @method \int[] getImList()
	 * @method \int[] fillIm()
	 * @method \int[] getDiskList()
	 * @method \int[] fillDisk()
	 * @method \int[] getMobileList()
	 * @method \int[] fillMobile()
	 * @method \int[] getCrmList()
	 * @method \int[] fillCrm()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\UStat\EO_DepartmentHour $object)
	 * @method bool has(\Bitrix\Intranet\UStat\EO_DepartmentHour $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour getByPrimary($primary)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour[] getAll()
	 * @method bool remove(\Bitrix\Intranet\UStat\EO_DepartmentHour $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\UStat\EO_DepartmentHour_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_DepartmentHour_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\UStat\DepartmentHourTable */
		static public $dataClass = '\Bitrix\Intranet\UStat\DepartmentHourTable';
	}
}
namespace Bitrix\Intranet\UStat {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DepartmentHour_Result exec()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour fetchObject()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_DepartmentHour_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour fetchObject()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour_Collection fetchCollection()
	 */
	class EO_DepartmentHour_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour_Collection createCollection()
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour wakeUpObject($row)
	 * @method \Bitrix\Intranet\UStat\EO_DepartmentHour_Collection wakeUpCollection($rows)
	 */
	class EO_DepartmentHour_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\UStat\UserDayTable:intranet\lib\ustat\userday.php:b68c7253a6faaab371c0fcb147198c29 */
namespace Bitrix\Intranet\UStat {
	/**
	 * EO_UserDay
	 * @see \Bitrix\Intranet\UStat\UserDayTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getUserId()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \Bitrix\Main\Type\Date getDay()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setDay(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $day)
	 * @method bool hasDay()
	 * @method bool isDayFilled()
	 * @method bool isDayChanged()
	 * @method \int getTotal()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setTotal(\int|\Bitrix\Main\DB\SqlExpression $total)
	 * @method bool hasTotal()
	 * @method bool isTotalFilled()
	 * @method bool isTotalChanged()
	 * @method \int remindActualTotal()
	 * @method \int requireTotal()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay resetTotal()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay unsetTotal()
	 * @method \int fillTotal()
	 * @method \int getSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setSocnet(\int|\Bitrix\Main\DB\SqlExpression $socnet)
	 * @method bool hasSocnet()
	 * @method bool isSocnetFilled()
	 * @method bool isSocnetChanged()
	 * @method \int remindActualSocnet()
	 * @method \int requireSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay resetSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay unsetSocnet()
	 * @method \int fillSocnet()
	 * @method \int getLikes()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setLikes(\int|\Bitrix\Main\DB\SqlExpression $likes)
	 * @method bool hasLikes()
	 * @method bool isLikesFilled()
	 * @method bool isLikesChanged()
	 * @method \int remindActualLikes()
	 * @method \int requireLikes()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay resetLikes()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay unsetLikes()
	 * @method \int fillLikes()
	 * @method \int getTasks()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setTasks(\int|\Bitrix\Main\DB\SqlExpression $tasks)
	 * @method bool hasTasks()
	 * @method bool isTasksFilled()
	 * @method bool isTasksChanged()
	 * @method \int remindActualTasks()
	 * @method \int requireTasks()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay resetTasks()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay unsetTasks()
	 * @method \int fillTasks()
	 * @method \int getIm()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setIm(\int|\Bitrix\Main\DB\SqlExpression $im)
	 * @method bool hasIm()
	 * @method bool isImFilled()
	 * @method bool isImChanged()
	 * @method \int remindActualIm()
	 * @method \int requireIm()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay resetIm()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay unsetIm()
	 * @method \int fillIm()
	 * @method \int getDisk()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setDisk(\int|\Bitrix\Main\DB\SqlExpression $disk)
	 * @method bool hasDisk()
	 * @method bool isDiskFilled()
	 * @method bool isDiskChanged()
	 * @method \int remindActualDisk()
	 * @method \int requireDisk()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay resetDisk()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay unsetDisk()
	 * @method \int fillDisk()
	 * @method \int getMobile()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setMobile(\int|\Bitrix\Main\DB\SqlExpression $mobile)
	 * @method bool hasMobile()
	 * @method bool isMobileFilled()
	 * @method bool isMobileChanged()
	 * @method \int remindActualMobile()
	 * @method \int requireMobile()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay resetMobile()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay unsetMobile()
	 * @method \int fillMobile()
	 * @method \int getCrm()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay setCrm(\int|\Bitrix\Main\DB\SqlExpression $crm)
	 * @method bool hasCrm()
	 * @method bool isCrmFilled()
	 * @method bool isCrmChanged()
	 * @method \int remindActualCrm()
	 * @method \int requireCrm()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay resetCrm()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay unsetCrm()
	 * @method \int fillCrm()
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
	 * @method \Bitrix\Intranet\UStat\EO_UserDay set($fieldName, $value)
	 * @method \Bitrix\Intranet\UStat\EO_UserDay reset($fieldName)
	 * @method \Bitrix\Intranet\UStat\EO_UserDay unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\UStat\EO_UserDay wakeUp($data)
	 */
	class EO_UserDay {
		/* @var \Bitrix\Intranet\UStat\UserDayTable */
		static public $dataClass = '\Bitrix\Intranet\UStat\UserDayTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet\UStat {
	/**
	 * EO_UserDay_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getUserIdList()
	 * @method \Bitrix\Main\Type\Date[] getDayList()
	 * @method \int[] getTotalList()
	 * @method \int[] fillTotal()
	 * @method \int[] getSocnetList()
	 * @method \int[] fillSocnet()
	 * @method \int[] getLikesList()
	 * @method \int[] fillLikes()
	 * @method \int[] getTasksList()
	 * @method \int[] fillTasks()
	 * @method \int[] getImList()
	 * @method \int[] fillIm()
	 * @method \int[] getDiskList()
	 * @method \int[] fillDisk()
	 * @method \int[] getMobileList()
	 * @method \int[] fillMobile()
	 * @method \int[] getCrmList()
	 * @method \int[] fillCrm()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\UStat\EO_UserDay $object)
	 * @method bool has(\Bitrix\Intranet\UStat\EO_UserDay $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\UStat\EO_UserDay getByPrimary($primary)
	 * @method \Bitrix\Intranet\UStat\EO_UserDay[] getAll()
	 * @method bool remove(\Bitrix\Intranet\UStat\EO_UserDay $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\UStat\EO_UserDay_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\UStat\EO_UserDay current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_UserDay_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\UStat\UserDayTable */
		static public $dataClass = '\Bitrix\Intranet\UStat\UserDayTable';
	}
}
namespace Bitrix\Intranet\UStat {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UserDay_Result exec()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay fetchObject()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_UserDay_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\UStat\EO_UserDay fetchObject()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay_Collection fetchCollection()
	 */
	class EO_UserDay_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\UStat\EO_UserDay createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\UStat\EO_UserDay_Collection createCollection()
	 * @method \Bitrix\Intranet\UStat\EO_UserDay wakeUpObject($row)
	 * @method \Bitrix\Intranet\UStat\EO_UserDay_Collection wakeUpCollection($rows)
	 */
	class EO_UserDay_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Intranet\UStat\UserHourTable:intranet\lib\ustat\userhour.php:428fa6f1090143bb265196c36df2d34c */
namespace Bitrix\Intranet\UStat {
	/**
	 * EO_UserHour
	 * @see \Bitrix\Intranet\UStat\UserHourTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getUserId()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getHour()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setHour(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $hour)
	 * @method bool hasHour()
	 * @method bool isHourFilled()
	 * @method bool isHourChanged()
	 * @method \int getTotal()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setTotal(\int|\Bitrix\Main\DB\SqlExpression $total)
	 * @method bool hasTotal()
	 * @method bool isTotalFilled()
	 * @method bool isTotalChanged()
	 * @method \int remindActualTotal()
	 * @method \int requireTotal()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour resetTotal()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour unsetTotal()
	 * @method \int fillTotal()
	 * @method \int getSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setSocnet(\int|\Bitrix\Main\DB\SqlExpression $socnet)
	 * @method bool hasSocnet()
	 * @method bool isSocnetFilled()
	 * @method bool isSocnetChanged()
	 * @method \int remindActualSocnet()
	 * @method \int requireSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour resetSocnet()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour unsetSocnet()
	 * @method \int fillSocnet()
	 * @method \int getLikes()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setLikes(\int|\Bitrix\Main\DB\SqlExpression $likes)
	 * @method bool hasLikes()
	 * @method bool isLikesFilled()
	 * @method bool isLikesChanged()
	 * @method \int remindActualLikes()
	 * @method \int requireLikes()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour resetLikes()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour unsetLikes()
	 * @method \int fillLikes()
	 * @method \int getTasks()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setTasks(\int|\Bitrix\Main\DB\SqlExpression $tasks)
	 * @method bool hasTasks()
	 * @method bool isTasksFilled()
	 * @method bool isTasksChanged()
	 * @method \int remindActualTasks()
	 * @method \int requireTasks()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour resetTasks()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour unsetTasks()
	 * @method \int fillTasks()
	 * @method \int getIm()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setIm(\int|\Bitrix\Main\DB\SqlExpression $im)
	 * @method bool hasIm()
	 * @method bool isImFilled()
	 * @method bool isImChanged()
	 * @method \int remindActualIm()
	 * @method \int requireIm()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour resetIm()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour unsetIm()
	 * @method \int fillIm()
	 * @method \int getDisk()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setDisk(\int|\Bitrix\Main\DB\SqlExpression $disk)
	 * @method bool hasDisk()
	 * @method bool isDiskFilled()
	 * @method bool isDiskChanged()
	 * @method \int remindActualDisk()
	 * @method \int requireDisk()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour resetDisk()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour unsetDisk()
	 * @method \int fillDisk()
	 * @method \int getMobile()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setMobile(\int|\Bitrix\Main\DB\SqlExpression $mobile)
	 * @method bool hasMobile()
	 * @method bool isMobileFilled()
	 * @method bool isMobileChanged()
	 * @method \int remindActualMobile()
	 * @method \int requireMobile()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour resetMobile()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour unsetMobile()
	 * @method \int fillMobile()
	 * @method \int getCrm()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour setCrm(\int|\Bitrix\Main\DB\SqlExpression $crm)
	 * @method bool hasCrm()
	 * @method bool isCrmFilled()
	 * @method bool isCrmChanged()
	 * @method \int remindActualCrm()
	 * @method \int requireCrm()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour resetCrm()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour unsetCrm()
	 * @method \int fillCrm()
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
	 * @method \Bitrix\Intranet\UStat\EO_UserHour set($fieldName, $value)
	 * @method \Bitrix\Intranet\UStat\EO_UserHour reset($fieldName)
	 * @method \Bitrix\Intranet\UStat\EO_UserHour unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Intranet\UStat\EO_UserHour wakeUp($data)
	 */
	class EO_UserHour {
		/* @var \Bitrix\Intranet\UStat\UserHourTable */
		static public $dataClass = '\Bitrix\Intranet\UStat\UserHourTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Intranet\UStat {
	/**
	 * EO_UserHour_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getUserIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getHourList()
	 * @method \int[] getTotalList()
	 * @method \int[] fillTotal()
	 * @method \int[] getSocnetList()
	 * @method \int[] fillSocnet()
	 * @method \int[] getLikesList()
	 * @method \int[] fillLikes()
	 * @method \int[] getTasksList()
	 * @method \int[] fillTasks()
	 * @method \int[] getImList()
	 * @method \int[] fillIm()
	 * @method \int[] getDiskList()
	 * @method \int[] fillDisk()
	 * @method \int[] getMobileList()
	 * @method \int[] fillMobile()
	 * @method \int[] getCrmList()
	 * @method \int[] fillCrm()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Intranet\UStat\EO_UserHour $object)
	 * @method bool has(\Bitrix\Intranet\UStat\EO_UserHour $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Intranet\UStat\EO_UserHour getByPrimary($primary)
	 * @method \Bitrix\Intranet\UStat\EO_UserHour[] getAll()
	 * @method bool remove(\Bitrix\Intranet\UStat\EO_UserHour $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Intranet\UStat\EO_UserHour_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Intranet\UStat\EO_UserHour current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_UserHour_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Intranet\UStat\UserHourTable */
		static public $dataClass = '\Bitrix\Intranet\UStat\UserHourTable';
	}
}
namespace Bitrix\Intranet\UStat {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UserHour_Result exec()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour fetchObject()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_UserHour_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Intranet\UStat\EO_UserHour fetchObject()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour_Collection fetchCollection()
	 */
	class EO_UserHour_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Intranet\UStat\EO_UserHour createObject($setDefaultValues = true)
	 * @method \Bitrix\Intranet\UStat\EO_UserHour_Collection createCollection()
	 * @method \Bitrix\Intranet\UStat\EO_UserHour wakeUpObject($row)
	 * @method \Bitrix\Intranet\UStat\EO_UserHour_Collection wakeUpCollection($rows)
	 */
	class EO_UserHour_Entity extends \Bitrix\Main\ORM\Entity {}
}