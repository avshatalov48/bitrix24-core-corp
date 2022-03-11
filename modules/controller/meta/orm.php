<?php

/* ORMENTITYANNOTATION:Bitrix\Controller\AuthGrantTable:controller/lib/authgrant.php:d6738b5636e3cf86ae0278980e380150 */
namespace Bitrix\Controller {
	/**
	 * EO_AuthGrant
	 * @see \Bitrix\Controller\AuthGrantTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Controller\EO_AuthGrant setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Controller\EO_AuthGrant setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Controller\EO_AuthGrant resetTimestampX()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getGrantedBy()
	 * @method \Bitrix\Controller\EO_AuthGrant setGrantedBy(\int|\Bitrix\Main\DB\SqlExpression $grantedBy)
	 * @method bool hasGrantedBy()
	 * @method bool isGrantedByFilled()
	 * @method bool isGrantedByChanged()
	 * @method \int remindActualGrantedBy()
	 * @method \int requireGrantedBy()
	 * @method \Bitrix\Controller\EO_AuthGrant resetGrantedBy()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetGrantedBy()
	 * @method \int fillGrantedBy()
	 * @method \int getControllerMemberId()
	 * @method \Bitrix\Controller\EO_AuthGrant setControllerMemberId(\int|\Bitrix\Main\DB\SqlExpression $controllerMemberId)
	 * @method bool hasControllerMemberId()
	 * @method bool isControllerMemberIdFilled()
	 * @method bool isControllerMemberIdChanged()
	 * @method \int remindActualControllerMemberId()
	 * @method \int requireControllerMemberId()
	 * @method \Bitrix\Controller\EO_AuthGrant resetControllerMemberId()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetControllerMemberId()
	 * @method \int fillControllerMemberId()
	 * @method \int getGranteeUserId()
	 * @method \Bitrix\Controller\EO_AuthGrant setGranteeUserId(\int|\Bitrix\Main\DB\SqlExpression $granteeUserId)
	 * @method bool hasGranteeUserId()
	 * @method bool isGranteeUserIdFilled()
	 * @method bool isGranteeUserIdChanged()
	 * @method \int remindActualGranteeUserId()
	 * @method \int requireGranteeUserId()
	 * @method \Bitrix\Controller\EO_AuthGrant resetGranteeUserId()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetGranteeUserId()
	 * @method \int fillGranteeUserId()
	 * @method \int getGranteeGroupId()
	 * @method \Bitrix\Controller\EO_AuthGrant setGranteeGroupId(\int|\Bitrix\Main\DB\SqlExpression $granteeGroupId)
	 * @method bool hasGranteeGroupId()
	 * @method bool isGranteeGroupIdFilled()
	 * @method bool isGranteeGroupIdChanged()
	 * @method \int remindActualGranteeGroupId()
	 * @method \int requireGranteeGroupId()
	 * @method \Bitrix\Controller\EO_AuthGrant resetGranteeGroupId()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetGranteeGroupId()
	 * @method \int fillGranteeGroupId()
	 * @method \boolean getActive()
	 * @method \Bitrix\Controller\EO_AuthGrant setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Controller\EO_AuthGrant resetActive()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetActive()
	 * @method \boolean fillActive()
	 * @method \string getScope()
	 * @method \Bitrix\Controller\EO_AuthGrant setScope(\string|\Bitrix\Main\DB\SqlExpression $scope)
	 * @method bool hasScope()
	 * @method bool isScopeFilled()
	 * @method bool isScopeChanged()
	 * @method \string remindActualScope()
	 * @method \string requireScope()
	 * @method \Bitrix\Controller\EO_AuthGrant resetScope()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetScope()
	 * @method \string fillScope()
	 * @method \Bitrix\Main\Type\DateTime getDateStart()
	 * @method \Bitrix\Controller\EO_AuthGrant setDateStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStart()
	 * @method \Bitrix\Main\Type\DateTime requireDateStart()
	 * @method \Bitrix\Controller\EO_AuthGrant resetDateStart()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetDateStart()
	 * @method \Bitrix\Main\Type\DateTime fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime getDateEnd()
	 * @method \Bitrix\Controller\EO_AuthGrant setDateEnd(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateEnd)
	 * @method bool hasDateEnd()
	 * @method bool isDateEndFilled()
	 * @method bool isDateEndChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateEnd()
	 * @method \Bitrix\Main\Type\DateTime requireDateEnd()
	 * @method \Bitrix\Controller\EO_AuthGrant resetDateEnd()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetDateEnd()
	 * @method \Bitrix\Main\Type\DateTime fillDateEnd()
	 * @method \string getNote()
	 * @method \Bitrix\Controller\EO_AuthGrant setNote(\string|\Bitrix\Main\DB\SqlExpression $note)
	 * @method bool hasNote()
	 * @method bool isNoteFilled()
	 * @method bool isNoteChanged()
	 * @method \string remindActualNote()
	 * @method \string requireNote()
	 * @method \Bitrix\Controller\EO_AuthGrant resetNote()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetNote()
	 * @method \string fillNote()
	 * @method \Bitrix\Controller\EO_Member getControllerMember()
	 * @method \Bitrix\Controller\EO_Member remindActualControllerMember()
	 * @method \Bitrix\Controller\EO_Member requireControllerMember()
	 * @method \Bitrix\Controller\EO_AuthGrant setControllerMember(\Bitrix\Controller\EO_Member $object)
	 * @method \Bitrix\Controller\EO_AuthGrant resetControllerMember()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetControllerMember()
	 * @method bool hasControllerMember()
	 * @method bool isControllerMemberFilled()
	 * @method bool isControllerMemberChanged()
	 * @method \Bitrix\Controller\EO_Member fillControllerMember()
	 * @method \Bitrix\Main\EO_User getGranted()
	 * @method \Bitrix\Main\EO_User remindActualGranted()
	 * @method \Bitrix\Main\EO_User requireGranted()
	 * @method \Bitrix\Controller\EO_AuthGrant setGranted(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Controller\EO_AuthGrant resetGranted()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetGranted()
	 * @method bool hasGranted()
	 * @method bool isGrantedFilled()
	 * @method bool isGrantedChanged()
	 * @method \Bitrix\Main\EO_User fillGranted()
	 * @method \string getGrantedName()
	 * @method \string remindActualGrantedName()
	 * @method \string requireGrantedName()
	 * @method bool hasGrantedName()
	 * @method bool isGrantedNameFilled()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetGrantedName()
	 * @method \string fillGrantedName()
	 * @method \Bitrix\Main\EO_User getGranteeUser()
	 * @method \Bitrix\Main\EO_User remindActualGranteeUser()
	 * @method \Bitrix\Main\EO_User requireGranteeUser()
	 * @method \Bitrix\Controller\EO_AuthGrant setGranteeUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Controller\EO_AuthGrant resetGranteeUser()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetGranteeUser()
	 * @method bool hasGranteeUser()
	 * @method bool isGranteeUserFilled()
	 * @method bool isGranteeUserChanged()
	 * @method \Bitrix\Main\EO_User fillGranteeUser()
	 * @method \string getGranteeUserName()
	 * @method \string remindActualGranteeUserName()
	 * @method \string requireGranteeUserName()
	 * @method bool hasGranteeUserName()
	 * @method bool isGranteeUserNameFilled()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetGranteeUserName()
	 * @method \string fillGranteeUserName()
	 * @method \Bitrix\Main\EO_Group getGranteeGroup()
	 * @method \Bitrix\Main\EO_Group remindActualGranteeGroup()
	 * @method \Bitrix\Main\EO_Group requireGranteeGroup()
	 * @method \Bitrix\Controller\EO_AuthGrant setGranteeGroup(\Bitrix\Main\EO_Group $object)
	 * @method \Bitrix\Controller\EO_AuthGrant resetGranteeGroup()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetGranteeGroup()
	 * @method bool hasGranteeGroup()
	 * @method bool isGranteeGroupFilled()
	 * @method bool isGranteeGroupChanged()
	 * @method \Bitrix\Main\EO_Group fillGranteeGroup()
	 * @method \string getGranteeGroupName()
	 * @method \string remindActualGranteeGroupName()
	 * @method \string requireGranteeGroupName()
	 * @method bool hasGranteeGroupName()
	 * @method bool isGranteeGroupNameFilled()
	 * @method \Bitrix\Controller\EO_AuthGrant unsetGranteeGroupName()
	 * @method \string fillGranteeGroupName()
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
	 * @method \Bitrix\Controller\EO_AuthGrant set($fieldName, $value)
	 * @method \Bitrix\Controller\EO_AuthGrant reset($fieldName)
	 * @method \Bitrix\Controller\EO_AuthGrant unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Controller\EO_AuthGrant wakeUp($data)
	 */
	class EO_AuthGrant {
		/* @var \Bitrix\Controller\AuthGrantTable */
		static public $dataClass = '\Bitrix\Controller\AuthGrantTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Controller {
	/**
	 * EO_AuthGrant_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getGrantedByList()
	 * @method \int[] fillGrantedBy()
	 * @method \int[] getControllerMemberIdList()
	 * @method \int[] fillControllerMemberId()
	 * @method \int[] getGranteeUserIdList()
	 * @method \int[] fillGranteeUserId()
	 * @method \int[] getGranteeGroupIdList()
	 * @method \int[] fillGranteeGroupId()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \string[] getScopeList()
	 * @method \string[] fillScope()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime[] getDateEndList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateEnd()
	 * @method \string[] getNoteList()
	 * @method \string[] fillNote()
	 * @method \Bitrix\Controller\EO_Member[] getControllerMemberList()
	 * @method \Bitrix\Controller\EO_AuthGrant_Collection getControllerMemberCollection()
	 * @method \Bitrix\Controller\EO_Member_Collection fillControllerMember()
	 * @method \Bitrix\Main\EO_User[] getGrantedList()
	 * @method \Bitrix\Controller\EO_AuthGrant_Collection getGrantedCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillGranted()
	 * @method \string[] getGrantedNameList()
	 * @method \string[] fillGrantedName()
	 * @method \Bitrix\Main\EO_User[] getGranteeUserList()
	 * @method \Bitrix\Controller\EO_AuthGrant_Collection getGranteeUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillGranteeUser()
	 * @method \string[] getGranteeUserNameList()
	 * @method \string[] fillGranteeUserName()
	 * @method \Bitrix\Main\EO_Group[] getGranteeGroupList()
	 * @method \Bitrix\Controller\EO_AuthGrant_Collection getGranteeGroupCollection()
	 * @method \Bitrix\Main\EO_Group_Collection fillGranteeGroup()
	 * @method \string[] getGranteeGroupNameList()
	 * @method \string[] fillGranteeGroupName()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Controller\EO_AuthGrant $object)
	 * @method bool has(\Bitrix\Controller\EO_AuthGrant $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Controller\EO_AuthGrant getByPrimary($primary)
	 * @method \Bitrix\Controller\EO_AuthGrant[] getAll()
	 * @method bool remove(\Bitrix\Controller\EO_AuthGrant $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Controller\EO_AuthGrant_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Controller\EO_AuthGrant current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_AuthGrant_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Controller\AuthGrantTable */
		static public $dataClass = '\Bitrix\Controller\AuthGrantTable';
	}
}
namespace Bitrix\Controller {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_AuthGrant_Result exec()
	 * @method \Bitrix\Controller\EO_AuthGrant fetchObject()
	 * @method \Bitrix\Controller\EO_AuthGrant_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_AuthGrant_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Controller\EO_AuthGrant fetchObject()
	 * @method \Bitrix\Controller\EO_AuthGrant_Collection fetchCollection()
	 */
	class EO_AuthGrant_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Controller\EO_AuthGrant createObject($setDefaultValues = true)
	 * @method \Bitrix\Controller\EO_AuthGrant_Collection createCollection()
	 * @method \Bitrix\Controller\EO_AuthGrant wakeUpObject($row)
	 * @method \Bitrix\Controller\EO_AuthGrant_Collection wakeUpCollection($rows)
	 */
	class EO_AuthGrant_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Controller\AuthLogTable:controller/lib/authlog.php:e05228df2968a544fd9a7944588b7da5 */
namespace Bitrix\Controller {
	/**
	 * EO_AuthLog
	 * @see \Bitrix\Controller\AuthLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Controller\EO_AuthLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Controller\EO_AuthLog setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Controller\EO_AuthLog resetTimestampX()
	 * @method \Bitrix\Controller\EO_AuthLog unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getFromControllerMemberId()
	 * @method \Bitrix\Controller\EO_AuthLog setFromControllerMemberId(\int|\Bitrix\Main\DB\SqlExpression $fromControllerMemberId)
	 * @method bool hasFromControllerMemberId()
	 * @method bool isFromControllerMemberIdFilled()
	 * @method bool isFromControllerMemberIdChanged()
	 * @method \int remindActualFromControllerMemberId()
	 * @method \int requireFromControllerMemberId()
	 * @method \Bitrix\Controller\EO_AuthLog resetFromControllerMemberId()
	 * @method \Bitrix\Controller\EO_AuthLog unsetFromControllerMemberId()
	 * @method \int fillFromControllerMemberId()
	 * @method \int getToControllerMemberId()
	 * @method \Bitrix\Controller\EO_AuthLog setToControllerMemberId(\int|\Bitrix\Main\DB\SqlExpression $toControllerMemberId)
	 * @method bool hasToControllerMemberId()
	 * @method bool isToControllerMemberIdFilled()
	 * @method bool isToControllerMemberIdChanged()
	 * @method \int remindActualToControllerMemberId()
	 * @method \int requireToControllerMemberId()
	 * @method \Bitrix\Controller\EO_AuthLog resetToControllerMemberId()
	 * @method \Bitrix\Controller\EO_AuthLog unsetToControllerMemberId()
	 * @method \int fillToControllerMemberId()
	 * @method \string getType()
	 * @method \Bitrix\Controller\EO_AuthLog setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Controller\EO_AuthLog resetType()
	 * @method \Bitrix\Controller\EO_AuthLog unsetType()
	 * @method \string fillType()
	 * @method \boolean getStatus()
	 * @method \Bitrix\Controller\EO_AuthLog setStatus(\boolean|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \boolean remindActualStatus()
	 * @method \boolean requireStatus()
	 * @method \Bitrix\Controller\EO_AuthLog resetStatus()
	 * @method \Bitrix\Controller\EO_AuthLog unsetStatus()
	 * @method \boolean fillStatus()
	 * @method \int getUserId()
	 * @method \Bitrix\Controller\EO_AuthLog setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Controller\EO_AuthLog resetUserId()
	 * @method \Bitrix\Controller\EO_AuthLog unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getUserName()
	 * @method \Bitrix\Controller\EO_AuthLog setUserName(\string|\Bitrix\Main\DB\SqlExpression $userName)
	 * @method bool hasUserName()
	 * @method bool isUserNameFilled()
	 * @method bool isUserNameChanged()
	 * @method \string remindActualUserName()
	 * @method \string requireUserName()
	 * @method \Bitrix\Controller\EO_AuthLog resetUserName()
	 * @method \Bitrix\Controller\EO_AuthLog unsetUserName()
	 * @method \string fillUserName()
	 * @method \Bitrix\Controller\EO_Member getFromControllerMember()
	 * @method \Bitrix\Controller\EO_Member remindActualFromControllerMember()
	 * @method \Bitrix\Controller\EO_Member requireFromControllerMember()
	 * @method \Bitrix\Controller\EO_AuthLog setFromControllerMember(\Bitrix\Controller\EO_Member $object)
	 * @method \Bitrix\Controller\EO_AuthLog resetFromControllerMember()
	 * @method \Bitrix\Controller\EO_AuthLog unsetFromControllerMember()
	 * @method bool hasFromControllerMember()
	 * @method bool isFromControllerMemberFilled()
	 * @method bool isFromControllerMemberChanged()
	 * @method \Bitrix\Controller\EO_Member fillFromControllerMember()
	 * @method \Bitrix\Controller\EO_Member getToControllerMember()
	 * @method \Bitrix\Controller\EO_Member remindActualToControllerMember()
	 * @method \Bitrix\Controller\EO_Member requireToControllerMember()
	 * @method \Bitrix\Controller\EO_AuthLog setToControllerMember(\Bitrix\Controller\EO_Member $object)
	 * @method \Bitrix\Controller\EO_AuthLog resetToControllerMember()
	 * @method \Bitrix\Controller\EO_AuthLog unsetToControllerMember()
	 * @method bool hasToControllerMember()
	 * @method bool isToControllerMemberFilled()
	 * @method bool isToControllerMemberChanged()
	 * @method \Bitrix\Controller\EO_Member fillToControllerMember()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Controller\EO_AuthLog setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Controller\EO_AuthLog resetUser()
	 * @method \Bitrix\Controller\EO_AuthLog unsetUser()
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
	 * @method \Bitrix\Controller\EO_AuthLog set($fieldName, $value)
	 * @method \Bitrix\Controller\EO_AuthLog reset($fieldName)
	 * @method \Bitrix\Controller\EO_AuthLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Controller\EO_AuthLog wakeUp($data)
	 */
	class EO_AuthLog {
		/* @var \Bitrix\Controller\AuthLogTable */
		static public $dataClass = '\Bitrix\Controller\AuthLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Controller {
	/**
	 * EO_AuthLog_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getFromControllerMemberIdList()
	 * @method \int[] fillFromControllerMemberId()
	 * @method \int[] getToControllerMemberIdList()
	 * @method \int[] fillToControllerMemberId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \boolean[] getStatusList()
	 * @method \boolean[] fillStatus()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getUserNameList()
	 * @method \string[] fillUserName()
	 * @method \Bitrix\Controller\EO_Member[] getFromControllerMemberList()
	 * @method \Bitrix\Controller\EO_AuthLog_Collection getFromControllerMemberCollection()
	 * @method \Bitrix\Controller\EO_Member_Collection fillFromControllerMember()
	 * @method \Bitrix\Controller\EO_Member[] getToControllerMemberList()
	 * @method \Bitrix\Controller\EO_AuthLog_Collection getToControllerMemberCollection()
	 * @method \Bitrix\Controller\EO_Member_Collection fillToControllerMember()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Controller\EO_AuthLog_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Controller\EO_AuthLog $object)
	 * @method bool has(\Bitrix\Controller\EO_AuthLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Controller\EO_AuthLog getByPrimary($primary)
	 * @method \Bitrix\Controller\EO_AuthLog[] getAll()
	 * @method bool remove(\Bitrix\Controller\EO_AuthLog $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Controller\EO_AuthLog_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Controller\EO_AuthLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_AuthLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Controller\AuthLogTable */
		static public $dataClass = '\Bitrix\Controller\AuthLogTable';
	}
}
namespace Bitrix\Controller {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_AuthLog_Result exec()
	 * @method \Bitrix\Controller\EO_AuthLog fetchObject()
	 * @method \Bitrix\Controller\EO_AuthLog_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_AuthLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Controller\EO_AuthLog fetchObject()
	 * @method \Bitrix\Controller\EO_AuthLog_Collection fetchCollection()
	 */
	class EO_AuthLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Controller\EO_AuthLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Controller\EO_AuthLog_Collection createCollection()
	 * @method \Bitrix\Controller\EO_AuthLog wakeUpObject($row)
	 * @method \Bitrix\Controller\EO_AuthLog_Collection wakeUpCollection($rows)
	 */
	class EO_AuthLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Controller\CounterHistoryTable:controller/lib/counterhistory.php:df0c7f94c212e92d45f7d47b2ba42186 */
namespace Bitrix\Controller {
	/**
	 * EO_CounterHistory
	 * @see \Bitrix\Controller\CounterHistoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Controller\EO_CounterHistory setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCounterId()
	 * @method \Bitrix\Controller\EO_CounterHistory setCounterId(\int|\Bitrix\Main\DB\SqlExpression $counterId)
	 * @method bool hasCounterId()
	 * @method bool isCounterIdFilled()
	 * @method bool isCounterIdChanged()
	 * @method \int remindActualCounterId()
	 * @method \int requireCounterId()
	 * @method \Bitrix\Controller\EO_CounterHistory resetCounterId()
	 * @method \Bitrix\Controller\EO_CounterHistory unsetCounterId()
	 * @method \int fillCounterId()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Controller\EO_CounterHistory setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Controller\EO_CounterHistory resetTimestampX()
	 * @method \Bitrix\Controller\EO_CounterHistory unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getUserId()
	 * @method \Bitrix\Controller\EO_CounterHistory setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Controller\EO_CounterHistory resetUserId()
	 * @method \Bitrix\Controller\EO_CounterHistory unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getName()
	 * @method \Bitrix\Controller\EO_CounterHistory setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Controller\EO_CounterHistory resetName()
	 * @method \Bitrix\Controller\EO_CounterHistory unsetName()
	 * @method \string fillName()
	 * @method \string getCommandFrom()
	 * @method \Bitrix\Controller\EO_CounterHistory setCommandFrom(\string|\Bitrix\Main\DB\SqlExpression $commandFrom)
	 * @method bool hasCommandFrom()
	 * @method bool isCommandFromFilled()
	 * @method bool isCommandFromChanged()
	 * @method \string remindActualCommandFrom()
	 * @method \string requireCommandFrom()
	 * @method \Bitrix\Controller\EO_CounterHistory resetCommandFrom()
	 * @method \Bitrix\Controller\EO_CounterHistory unsetCommandFrom()
	 * @method \string fillCommandFrom()
	 * @method \string getCommandTo()
	 * @method \Bitrix\Controller\EO_CounterHistory setCommandTo(\string|\Bitrix\Main\DB\SqlExpression $commandTo)
	 * @method bool hasCommandTo()
	 * @method bool isCommandToFilled()
	 * @method bool isCommandToChanged()
	 * @method \string remindActualCommandTo()
	 * @method \string requireCommandTo()
	 * @method \Bitrix\Controller\EO_CounterHistory resetCommandTo()
	 * @method \Bitrix\Controller\EO_CounterHistory unsetCommandTo()
	 * @method \string fillCommandTo()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Controller\EO_CounterHistory setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Controller\EO_CounterHistory resetUser()
	 * @method \Bitrix\Controller\EO_CounterHistory unsetUser()
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
	 * @method \Bitrix\Controller\EO_CounterHistory set($fieldName, $value)
	 * @method \Bitrix\Controller\EO_CounterHistory reset($fieldName)
	 * @method \Bitrix\Controller\EO_CounterHistory unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Controller\EO_CounterHistory wakeUp($data)
	 */
	class EO_CounterHistory {
		/* @var \Bitrix\Controller\CounterHistoryTable */
		static public $dataClass = '\Bitrix\Controller\CounterHistoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Controller {
	/**
	 * EO_CounterHistory_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCounterIdList()
	 * @method \int[] fillCounterId()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getCommandFromList()
	 * @method \string[] fillCommandFrom()
	 * @method \string[] getCommandToList()
	 * @method \string[] fillCommandTo()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Controller\EO_CounterHistory_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Controller\EO_CounterHistory $object)
	 * @method bool has(\Bitrix\Controller\EO_CounterHistory $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Controller\EO_CounterHistory getByPrimary($primary)
	 * @method \Bitrix\Controller\EO_CounterHistory[] getAll()
	 * @method bool remove(\Bitrix\Controller\EO_CounterHistory $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Controller\EO_CounterHistory_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Controller\EO_CounterHistory current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_CounterHistory_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Controller\CounterHistoryTable */
		static public $dataClass = '\Bitrix\Controller\CounterHistoryTable';
	}
}
namespace Bitrix\Controller {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CounterHistory_Result exec()
	 * @method \Bitrix\Controller\EO_CounterHistory fetchObject()
	 * @method \Bitrix\Controller\EO_CounterHistory_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CounterHistory_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Controller\EO_CounterHistory fetchObject()
	 * @method \Bitrix\Controller\EO_CounterHistory_Collection fetchCollection()
	 */
	class EO_CounterHistory_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Controller\EO_CounterHistory createObject($setDefaultValues = true)
	 * @method \Bitrix\Controller\EO_CounterHistory_Collection createCollection()
	 * @method \Bitrix\Controller\EO_CounterHistory wakeUpObject($row)
	 * @method \Bitrix\Controller\EO_CounterHistory_Collection wakeUpCollection($rows)
	 */
	class EO_CounterHistory_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Controller\GroupTable:controller/lib/group.php:243e34d19ce46b1f8d595b95a420eab0 */
namespace Bitrix\Controller {
	/**
	 * EO_Group
	 * @see \Bitrix\Controller\GroupTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Controller\EO_Group setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Controller\EO_Group setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Controller\EO_Group resetTimestampX()
	 * @method \Bitrix\Controller\EO_Group unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \string getName()
	 * @method \Bitrix\Controller\EO_Group setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Controller\EO_Group resetName()
	 * @method \Bitrix\Controller\EO_Group unsetName()
	 * @method \string fillName()
	 * @method \int getUpdatePeriod()
	 * @method \Bitrix\Controller\EO_Group setUpdatePeriod(\int|\Bitrix\Main\DB\SqlExpression $updatePeriod)
	 * @method bool hasUpdatePeriod()
	 * @method bool isUpdatePeriodFilled()
	 * @method bool isUpdatePeriodChanged()
	 * @method \int remindActualUpdatePeriod()
	 * @method \int requireUpdatePeriod()
	 * @method \Bitrix\Controller\EO_Group resetUpdatePeriod()
	 * @method \Bitrix\Controller\EO_Group unsetUpdatePeriod()
	 * @method \int fillUpdatePeriod()
	 * @method \boolean getDisableDeactivated()
	 * @method \Bitrix\Controller\EO_Group setDisableDeactivated(\boolean|\Bitrix\Main\DB\SqlExpression $disableDeactivated)
	 * @method bool hasDisableDeactivated()
	 * @method bool isDisableDeactivatedFilled()
	 * @method bool isDisableDeactivatedChanged()
	 * @method \boolean remindActualDisableDeactivated()
	 * @method \boolean requireDisableDeactivated()
	 * @method \Bitrix\Controller\EO_Group resetDisableDeactivated()
	 * @method \Bitrix\Controller\EO_Group unsetDisableDeactivated()
	 * @method \boolean fillDisableDeactivated()
	 * @method \string getDescription()
	 * @method \Bitrix\Controller\EO_Group setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Controller\EO_Group resetDescription()
	 * @method \Bitrix\Controller\EO_Group unsetDescription()
	 * @method \string fillDescription()
	 * @method \int getModifiedBy()
	 * @method \Bitrix\Controller\EO_Group setModifiedBy(\int|\Bitrix\Main\DB\SqlExpression $modifiedBy)
	 * @method bool hasModifiedBy()
	 * @method bool isModifiedByFilled()
	 * @method bool isModifiedByChanged()
	 * @method \int remindActualModifiedBy()
	 * @method \int requireModifiedBy()
	 * @method \Bitrix\Controller\EO_Group resetModifiedBy()
	 * @method \Bitrix\Controller\EO_Group unsetModifiedBy()
	 * @method \int fillModifiedBy()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Controller\EO_Group setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Controller\EO_Group resetDateCreate()
	 * @method \Bitrix\Controller\EO_Group unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Controller\EO_Group setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Controller\EO_Group resetCreatedBy()
	 * @method \Bitrix\Controller\EO_Group unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \string getInstallInfo()
	 * @method \Bitrix\Controller\EO_Group setInstallInfo(\string|\Bitrix\Main\DB\SqlExpression $installInfo)
	 * @method bool hasInstallInfo()
	 * @method bool isInstallInfoFilled()
	 * @method bool isInstallInfoChanged()
	 * @method \string remindActualInstallInfo()
	 * @method \string requireInstallInfo()
	 * @method \Bitrix\Controller\EO_Group resetInstallInfo()
	 * @method \Bitrix\Controller\EO_Group unsetInstallInfo()
	 * @method \string fillInstallInfo()
	 * @method \string getUninstallInfo()
	 * @method \Bitrix\Controller\EO_Group setUninstallInfo(\string|\Bitrix\Main\DB\SqlExpression $uninstallInfo)
	 * @method bool hasUninstallInfo()
	 * @method bool isUninstallInfoFilled()
	 * @method bool isUninstallInfoChanged()
	 * @method \string remindActualUninstallInfo()
	 * @method \string requireUninstallInfo()
	 * @method \Bitrix\Controller\EO_Group resetUninstallInfo()
	 * @method \Bitrix\Controller\EO_Group unsetUninstallInfo()
	 * @method \string fillUninstallInfo()
	 * @method \string getInstallPhp()
	 * @method \Bitrix\Controller\EO_Group setInstallPhp(\string|\Bitrix\Main\DB\SqlExpression $installPhp)
	 * @method bool hasInstallPhp()
	 * @method bool isInstallPhpFilled()
	 * @method bool isInstallPhpChanged()
	 * @method \string remindActualInstallPhp()
	 * @method \string requireInstallPhp()
	 * @method \Bitrix\Controller\EO_Group resetInstallPhp()
	 * @method \Bitrix\Controller\EO_Group unsetInstallPhp()
	 * @method \string fillInstallPhp()
	 * @method \string getUninstallPhp()
	 * @method \Bitrix\Controller\EO_Group setUninstallPhp(\string|\Bitrix\Main\DB\SqlExpression $uninstallPhp)
	 * @method bool hasUninstallPhp()
	 * @method bool isUninstallPhpFilled()
	 * @method bool isUninstallPhpChanged()
	 * @method \string remindActualUninstallPhp()
	 * @method \string requireUninstallPhp()
	 * @method \Bitrix\Controller\EO_Group resetUninstallPhp()
	 * @method \Bitrix\Controller\EO_Group unsetUninstallPhp()
	 * @method \string fillUninstallPhp()
	 * @method \int getTrialPeriod()
	 * @method \Bitrix\Controller\EO_Group setTrialPeriod(\int|\Bitrix\Main\DB\SqlExpression $trialPeriod)
	 * @method bool hasTrialPeriod()
	 * @method bool isTrialPeriodFilled()
	 * @method bool isTrialPeriodChanged()
	 * @method \int remindActualTrialPeriod()
	 * @method \int requireTrialPeriod()
	 * @method \Bitrix\Controller\EO_Group resetTrialPeriod()
	 * @method \Bitrix\Controller\EO_Group unsetTrialPeriod()
	 * @method \int fillTrialPeriod()
	 * @method \int getCounterUpdatePeriod()
	 * @method \Bitrix\Controller\EO_Group setCounterUpdatePeriod(\int|\Bitrix\Main\DB\SqlExpression $counterUpdatePeriod)
	 * @method bool hasCounterUpdatePeriod()
	 * @method bool isCounterUpdatePeriodFilled()
	 * @method bool isCounterUpdatePeriodChanged()
	 * @method \int remindActualCounterUpdatePeriod()
	 * @method \int requireCounterUpdatePeriod()
	 * @method \Bitrix\Controller\EO_Group resetCounterUpdatePeriod()
	 * @method \Bitrix\Controller\EO_Group unsetCounterUpdatePeriod()
	 * @method \int fillCounterUpdatePeriod()
	 * @method \string getCheckCounterFreeSpace()
	 * @method \Bitrix\Controller\EO_Group setCheckCounterFreeSpace(\string|\Bitrix\Main\DB\SqlExpression $checkCounterFreeSpace)
	 * @method bool hasCheckCounterFreeSpace()
	 * @method bool isCheckCounterFreeSpaceFilled()
	 * @method bool isCheckCounterFreeSpaceChanged()
	 * @method \string remindActualCheckCounterFreeSpace()
	 * @method \string requireCheckCounterFreeSpace()
	 * @method \Bitrix\Controller\EO_Group resetCheckCounterFreeSpace()
	 * @method \Bitrix\Controller\EO_Group unsetCheckCounterFreeSpace()
	 * @method \string fillCheckCounterFreeSpace()
	 * @method \string getCheckCounterSites()
	 * @method \Bitrix\Controller\EO_Group setCheckCounterSites(\string|\Bitrix\Main\DB\SqlExpression $checkCounterSites)
	 * @method bool hasCheckCounterSites()
	 * @method bool isCheckCounterSitesFilled()
	 * @method bool isCheckCounterSitesChanged()
	 * @method \string remindActualCheckCounterSites()
	 * @method \string requireCheckCounterSites()
	 * @method \Bitrix\Controller\EO_Group resetCheckCounterSites()
	 * @method \Bitrix\Controller\EO_Group unsetCheckCounterSites()
	 * @method \string fillCheckCounterSites()
	 * @method \string getCheckCounterUsers()
	 * @method \Bitrix\Controller\EO_Group setCheckCounterUsers(\string|\Bitrix\Main\DB\SqlExpression $checkCounterUsers)
	 * @method bool hasCheckCounterUsers()
	 * @method bool isCheckCounterUsersFilled()
	 * @method bool isCheckCounterUsersChanged()
	 * @method \string remindActualCheckCounterUsers()
	 * @method \string requireCheckCounterUsers()
	 * @method \Bitrix\Controller\EO_Group resetCheckCounterUsers()
	 * @method \Bitrix\Controller\EO_Group unsetCheckCounterUsers()
	 * @method \string fillCheckCounterUsers()
	 * @method \string getCheckCounterLastAuth()
	 * @method \Bitrix\Controller\EO_Group setCheckCounterLastAuth(\string|\Bitrix\Main\DB\SqlExpression $checkCounterLastAuth)
	 * @method bool hasCheckCounterLastAuth()
	 * @method bool isCheckCounterLastAuthFilled()
	 * @method bool isCheckCounterLastAuthChanged()
	 * @method \string remindActualCheckCounterLastAuth()
	 * @method \string requireCheckCounterLastAuth()
	 * @method \Bitrix\Controller\EO_Group resetCheckCounterLastAuth()
	 * @method \Bitrix\Controller\EO_Group unsetCheckCounterLastAuth()
	 * @method \string fillCheckCounterLastAuth()
	 * @method \Bitrix\Main\EO_User getCreated()
	 * @method \Bitrix\Main\EO_User remindActualCreated()
	 * @method \Bitrix\Main\EO_User requireCreated()
	 * @method \Bitrix\Controller\EO_Group setCreated(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Controller\EO_Group resetCreated()
	 * @method \Bitrix\Controller\EO_Group unsetCreated()
	 * @method bool hasCreated()
	 * @method bool isCreatedFilled()
	 * @method bool isCreatedChanged()
	 * @method \Bitrix\Main\EO_User fillCreated()
	 * @method \Bitrix\Main\EO_User getModified()
	 * @method \Bitrix\Main\EO_User remindActualModified()
	 * @method \Bitrix\Main\EO_User requireModified()
	 * @method \Bitrix\Controller\EO_Group setModified(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Controller\EO_Group resetModified()
	 * @method \Bitrix\Controller\EO_Group unsetModified()
	 * @method bool hasModified()
	 * @method bool isModifiedFilled()
	 * @method bool isModifiedChanged()
	 * @method \Bitrix\Main\EO_User fillModified()
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
	 * @method \Bitrix\Controller\EO_Group set($fieldName, $value)
	 * @method \Bitrix\Controller\EO_Group reset($fieldName)
	 * @method \Bitrix\Controller\EO_Group unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Controller\EO_Group wakeUp($data)
	 */
	class EO_Group {
		/* @var \Bitrix\Controller\GroupTable */
		static public $dataClass = '\Bitrix\Controller\GroupTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Controller {
	/**
	 * EO_Group_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getUpdatePeriodList()
	 * @method \int[] fillUpdatePeriod()
	 * @method \boolean[] getDisableDeactivatedList()
	 * @method \boolean[] fillDisableDeactivated()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \int[] getModifiedByList()
	 * @method \int[] fillModifiedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \string[] getInstallInfoList()
	 * @method \string[] fillInstallInfo()
	 * @method \string[] getUninstallInfoList()
	 * @method \string[] fillUninstallInfo()
	 * @method \string[] getInstallPhpList()
	 * @method \string[] fillInstallPhp()
	 * @method \string[] getUninstallPhpList()
	 * @method \string[] fillUninstallPhp()
	 * @method \int[] getTrialPeriodList()
	 * @method \int[] fillTrialPeriod()
	 * @method \int[] getCounterUpdatePeriodList()
	 * @method \int[] fillCounterUpdatePeriod()
	 * @method \string[] getCheckCounterFreeSpaceList()
	 * @method \string[] fillCheckCounterFreeSpace()
	 * @method \string[] getCheckCounterSitesList()
	 * @method \string[] fillCheckCounterSites()
	 * @method \string[] getCheckCounterUsersList()
	 * @method \string[] fillCheckCounterUsers()
	 * @method \string[] getCheckCounterLastAuthList()
	 * @method \string[] fillCheckCounterLastAuth()
	 * @method \Bitrix\Main\EO_User[] getCreatedList()
	 * @method \Bitrix\Controller\EO_Group_Collection getCreatedCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreated()
	 * @method \Bitrix\Main\EO_User[] getModifiedList()
	 * @method \Bitrix\Controller\EO_Group_Collection getModifiedCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillModified()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Controller\EO_Group $object)
	 * @method bool has(\Bitrix\Controller\EO_Group $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Controller\EO_Group getByPrimary($primary)
	 * @method \Bitrix\Controller\EO_Group[] getAll()
	 * @method bool remove(\Bitrix\Controller\EO_Group $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Controller\EO_Group_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Controller\EO_Group current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Group_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Controller\GroupTable */
		static public $dataClass = '\Bitrix\Controller\GroupTable';
	}
}
namespace Bitrix\Controller {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Group_Result exec()
	 * @method \Bitrix\Controller\EO_Group fetchObject()
	 * @method \Bitrix\Controller\EO_Group_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Group_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Controller\EO_Group fetchObject()
	 * @method \Bitrix\Controller\EO_Group_Collection fetchCollection()
	 */
	class EO_Group_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Controller\EO_Group createObject($setDefaultValues = true)
	 * @method \Bitrix\Controller\EO_Group_Collection createCollection()
	 * @method \Bitrix\Controller\EO_Group wakeUpObject($row)
	 * @method \Bitrix\Controller\EO_Group_Collection wakeUpCollection($rows)
	 */
	class EO_Group_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Controller\GroupMapTable:controller/lib/groupmap.php:959744460060491b11b7f136af4ffff3 */
namespace Bitrix\Controller {
	/**
	 * EO_GroupMap
	 * @see \Bitrix\Controller\GroupMapTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Controller\EO_GroupMap setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getControllerGroupId()
	 * @method \Bitrix\Controller\EO_GroupMap setControllerGroupId(\int|\Bitrix\Main\DB\SqlExpression $controllerGroupId)
	 * @method bool hasControllerGroupId()
	 * @method bool isControllerGroupIdFilled()
	 * @method bool isControllerGroupIdChanged()
	 * @method \int remindActualControllerGroupId()
	 * @method \int requireControllerGroupId()
	 * @method \Bitrix\Controller\EO_GroupMap resetControllerGroupId()
	 * @method \Bitrix\Controller\EO_GroupMap unsetControllerGroupId()
	 * @method \int fillControllerGroupId()
	 * @method \string getRemoteGroupCode()
	 * @method \Bitrix\Controller\EO_GroupMap setRemoteGroupCode(\string|\Bitrix\Main\DB\SqlExpression $remoteGroupCode)
	 * @method bool hasRemoteGroupCode()
	 * @method bool isRemoteGroupCodeFilled()
	 * @method bool isRemoteGroupCodeChanged()
	 * @method \string remindActualRemoteGroupCode()
	 * @method \string requireRemoteGroupCode()
	 * @method \Bitrix\Controller\EO_GroupMap resetRemoteGroupCode()
	 * @method \Bitrix\Controller\EO_GroupMap unsetRemoteGroupCode()
	 * @method \string fillRemoteGroupCode()
	 * @method \string getLocalGroupCode()
	 * @method \Bitrix\Controller\EO_GroupMap setLocalGroupCode(\string|\Bitrix\Main\DB\SqlExpression $localGroupCode)
	 * @method bool hasLocalGroupCode()
	 * @method bool isLocalGroupCodeFilled()
	 * @method bool isLocalGroupCodeChanged()
	 * @method \string remindActualLocalGroupCode()
	 * @method \string requireLocalGroupCode()
	 * @method \Bitrix\Controller\EO_GroupMap resetLocalGroupCode()
	 * @method \Bitrix\Controller\EO_GroupMap unsetLocalGroupCode()
	 * @method \string fillLocalGroupCode()
	 * @method \Bitrix\Controller\EO_Group getControllerGroup()
	 * @method \Bitrix\Controller\EO_Group remindActualControllerGroup()
	 * @method \Bitrix\Controller\EO_Group requireControllerGroup()
	 * @method \Bitrix\Controller\EO_GroupMap setControllerGroup(\Bitrix\Controller\EO_Group $object)
	 * @method \Bitrix\Controller\EO_GroupMap resetControllerGroup()
	 * @method \Bitrix\Controller\EO_GroupMap unsetControllerGroup()
	 * @method bool hasControllerGroup()
	 * @method bool isControllerGroupFilled()
	 * @method bool isControllerGroupChanged()
	 * @method \Bitrix\Controller\EO_Group fillControllerGroup()
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
	 * @method \Bitrix\Controller\EO_GroupMap set($fieldName, $value)
	 * @method \Bitrix\Controller\EO_GroupMap reset($fieldName)
	 * @method \Bitrix\Controller\EO_GroupMap unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Controller\EO_GroupMap wakeUp($data)
	 */
	class EO_GroupMap {
		/* @var \Bitrix\Controller\GroupMapTable */
		static public $dataClass = '\Bitrix\Controller\GroupMapTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Controller {
	/**
	 * EO_GroupMap_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getControllerGroupIdList()
	 * @method \int[] fillControllerGroupId()
	 * @method \string[] getRemoteGroupCodeList()
	 * @method \string[] fillRemoteGroupCode()
	 * @method \string[] getLocalGroupCodeList()
	 * @method \string[] fillLocalGroupCode()
	 * @method \Bitrix\Controller\EO_Group[] getControllerGroupList()
	 * @method \Bitrix\Controller\EO_GroupMap_Collection getControllerGroupCollection()
	 * @method \Bitrix\Controller\EO_Group_Collection fillControllerGroup()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Controller\EO_GroupMap $object)
	 * @method bool has(\Bitrix\Controller\EO_GroupMap $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Controller\EO_GroupMap getByPrimary($primary)
	 * @method \Bitrix\Controller\EO_GroupMap[] getAll()
	 * @method bool remove(\Bitrix\Controller\EO_GroupMap $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Controller\EO_GroupMap_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Controller\EO_GroupMap current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_GroupMap_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Controller\GroupMapTable */
		static public $dataClass = '\Bitrix\Controller\GroupMapTable';
	}
}
namespace Bitrix\Controller {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_GroupMap_Result exec()
	 * @method \Bitrix\Controller\EO_GroupMap fetchObject()
	 * @method \Bitrix\Controller\EO_GroupMap_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_GroupMap_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Controller\EO_GroupMap fetchObject()
	 * @method \Bitrix\Controller\EO_GroupMap_Collection fetchCollection()
	 */
	class EO_GroupMap_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Controller\EO_GroupMap createObject($setDefaultValues = true)
	 * @method \Bitrix\Controller\EO_GroupMap_Collection createCollection()
	 * @method \Bitrix\Controller\EO_GroupMap wakeUpObject($row)
	 * @method \Bitrix\Controller\EO_GroupMap_Collection wakeUpCollection($rows)
	 */
	class EO_GroupMap_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Controller\MemberTable:controller/lib/member.php:a487a5fb5f06d80f3435edd24a31e160 */
namespace Bitrix\Controller {
	/**
	 * EO_Member
	 * @see \Bitrix\Controller\MemberTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Controller\EO_Member setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getMemberId()
	 * @method \Bitrix\Controller\EO_Member setMemberId(\string|\Bitrix\Main\DB\SqlExpression $memberId)
	 * @method bool hasMemberId()
	 * @method bool isMemberIdFilled()
	 * @method bool isMemberIdChanged()
	 * @method \string remindActualMemberId()
	 * @method \string requireMemberId()
	 * @method \Bitrix\Controller\EO_Member resetMemberId()
	 * @method \Bitrix\Controller\EO_Member unsetMemberId()
	 * @method \string fillMemberId()
	 * @method \string getSecretId()
	 * @method \Bitrix\Controller\EO_Member setSecretId(\string|\Bitrix\Main\DB\SqlExpression $secretId)
	 * @method bool hasSecretId()
	 * @method bool isSecretIdFilled()
	 * @method bool isSecretIdChanged()
	 * @method \string remindActualSecretId()
	 * @method \string requireSecretId()
	 * @method \Bitrix\Controller\EO_Member resetSecretId()
	 * @method \Bitrix\Controller\EO_Member unsetSecretId()
	 * @method \string fillSecretId()
	 * @method \string getName()
	 * @method \Bitrix\Controller\EO_Member setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Controller\EO_Member resetName()
	 * @method \Bitrix\Controller\EO_Member unsetName()
	 * @method \string fillName()
	 * @method \string getUrl()
	 * @method \Bitrix\Controller\EO_Member setUrl(\string|\Bitrix\Main\DB\SqlExpression $url)
	 * @method bool hasUrl()
	 * @method bool isUrlFilled()
	 * @method bool isUrlChanged()
	 * @method \string remindActualUrl()
	 * @method \string requireUrl()
	 * @method \Bitrix\Controller\EO_Member resetUrl()
	 * @method \Bitrix\Controller\EO_Member unsetUrl()
	 * @method \string fillUrl()
	 * @method \string getEmail()
	 * @method \Bitrix\Controller\EO_Member setEmail(\string|\Bitrix\Main\DB\SqlExpression $email)
	 * @method bool hasEmail()
	 * @method bool isEmailFilled()
	 * @method bool isEmailChanged()
	 * @method \string remindActualEmail()
	 * @method \string requireEmail()
	 * @method \Bitrix\Controller\EO_Member resetEmail()
	 * @method \Bitrix\Controller\EO_Member unsetEmail()
	 * @method \string fillEmail()
	 * @method \string getContactPerson()
	 * @method \Bitrix\Controller\EO_Member setContactPerson(\string|\Bitrix\Main\DB\SqlExpression $contactPerson)
	 * @method bool hasContactPerson()
	 * @method bool isContactPersonFilled()
	 * @method bool isContactPersonChanged()
	 * @method \string remindActualContactPerson()
	 * @method \string requireContactPerson()
	 * @method \Bitrix\Controller\EO_Member resetContactPerson()
	 * @method \Bitrix\Controller\EO_Member unsetContactPerson()
	 * @method \string fillContactPerson()
	 * @method \int getControllerGroupId()
	 * @method \Bitrix\Controller\EO_Member setControllerGroupId(\int|\Bitrix\Main\DB\SqlExpression $controllerGroupId)
	 * @method bool hasControllerGroupId()
	 * @method bool isControllerGroupIdFilled()
	 * @method bool isControllerGroupIdChanged()
	 * @method \int remindActualControllerGroupId()
	 * @method \int requireControllerGroupId()
	 * @method \Bitrix\Controller\EO_Member resetControllerGroupId()
	 * @method \Bitrix\Controller\EO_Member unsetControllerGroupId()
	 * @method \int fillControllerGroupId()
	 * @method \boolean getDisconnected()
	 * @method \Bitrix\Controller\EO_Member setDisconnected(\boolean|\Bitrix\Main\DB\SqlExpression $disconnected)
	 * @method bool hasDisconnected()
	 * @method bool isDisconnectedFilled()
	 * @method bool isDisconnectedChanged()
	 * @method \boolean remindActualDisconnected()
	 * @method \boolean requireDisconnected()
	 * @method \Bitrix\Controller\EO_Member resetDisconnected()
	 * @method \Bitrix\Controller\EO_Member unsetDisconnected()
	 * @method \boolean fillDisconnected()
	 * @method \boolean getSharedKernel()
	 * @method \Bitrix\Controller\EO_Member setSharedKernel(\boolean|\Bitrix\Main\DB\SqlExpression $sharedKernel)
	 * @method bool hasSharedKernel()
	 * @method bool isSharedKernelFilled()
	 * @method bool isSharedKernelChanged()
	 * @method \boolean remindActualSharedKernel()
	 * @method \boolean requireSharedKernel()
	 * @method \Bitrix\Controller\EO_Member resetSharedKernel()
	 * @method \Bitrix\Controller\EO_Member unsetSharedKernel()
	 * @method \boolean fillSharedKernel()
	 * @method \boolean getActive()
	 * @method \Bitrix\Controller\EO_Member setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Controller\EO_Member resetActive()
	 * @method \Bitrix\Controller\EO_Member unsetActive()
	 * @method \boolean fillActive()
	 * @method \Bitrix\Main\Type\DateTime getDateActiveFrom()
	 * @method \Bitrix\Controller\EO_Member setDateActiveFrom(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateActiveFrom)
	 * @method bool hasDateActiveFrom()
	 * @method bool isDateActiveFromFilled()
	 * @method bool isDateActiveFromChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateActiveFrom()
	 * @method \Bitrix\Main\Type\DateTime requireDateActiveFrom()
	 * @method \Bitrix\Controller\EO_Member resetDateActiveFrom()
	 * @method \Bitrix\Controller\EO_Member unsetDateActiveFrom()
	 * @method \Bitrix\Main\Type\DateTime fillDateActiveFrom()
	 * @method \Bitrix\Main\Type\DateTime getDateActiveTo()
	 * @method \Bitrix\Controller\EO_Member setDateActiveTo(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateActiveTo)
	 * @method bool hasDateActiveTo()
	 * @method bool isDateActiveToFilled()
	 * @method bool isDateActiveToChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateActiveTo()
	 * @method \Bitrix\Main\Type\DateTime requireDateActiveTo()
	 * @method \Bitrix\Controller\EO_Member resetDateActiveTo()
	 * @method \Bitrix\Controller\EO_Member unsetDateActiveTo()
	 * @method \Bitrix\Main\Type\DateTime fillDateActiveTo()
	 * @method \boolean getSiteActive()
	 * @method \Bitrix\Controller\EO_Member setSiteActive(\boolean|\Bitrix\Main\DB\SqlExpression $siteActive)
	 * @method bool hasSiteActive()
	 * @method bool isSiteActiveFilled()
	 * @method bool isSiteActiveChanged()
	 * @method \boolean remindActualSiteActive()
	 * @method \boolean requireSiteActive()
	 * @method \Bitrix\Controller\EO_Member resetSiteActive()
	 * @method \Bitrix\Controller\EO_Member unsetSiteActive()
	 * @method \boolean fillSiteActive()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Controller\EO_Member setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Controller\EO_Member resetTimestampX()
	 * @method \Bitrix\Controller\EO_Member unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getModifiedBy()
	 * @method \Bitrix\Controller\EO_Member setModifiedBy(\int|\Bitrix\Main\DB\SqlExpression $modifiedBy)
	 * @method bool hasModifiedBy()
	 * @method bool isModifiedByFilled()
	 * @method bool isModifiedByChanged()
	 * @method \int remindActualModifiedBy()
	 * @method \int requireModifiedBy()
	 * @method \Bitrix\Controller\EO_Member resetModifiedBy()
	 * @method \Bitrix\Controller\EO_Member unsetModifiedBy()
	 * @method \int fillModifiedBy()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Controller\EO_Member setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Controller\EO_Member resetDateCreate()
	 * @method \Bitrix\Controller\EO_Member unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Controller\EO_Member setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Controller\EO_Member resetCreatedBy()
	 * @method \Bitrix\Controller\EO_Member unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getInGroupFrom()
	 * @method \Bitrix\Controller\EO_Member setInGroupFrom(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $inGroupFrom)
	 * @method bool hasInGroupFrom()
	 * @method bool isInGroupFromFilled()
	 * @method bool isInGroupFromChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualInGroupFrom()
	 * @method \Bitrix\Main\Type\DateTime requireInGroupFrom()
	 * @method \Bitrix\Controller\EO_Member resetInGroupFrom()
	 * @method \Bitrix\Controller\EO_Member unsetInGroupFrom()
	 * @method \Bitrix\Main\Type\DateTime fillInGroupFrom()
	 * @method \string getNotes()
	 * @method \Bitrix\Controller\EO_Member setNotes(\string|\Bitrix\Main\DB\SqlExpression $notes)
	 * @method bool hasNotes()
	 * @method bool isNotesFilled()
	 * @method bool isNotesChanged()
	 * @method \string remindActualNotes()
	 * @method \string requireNotes()
	 * @method \Bitrix\Controller\EO_Member resetNotes()
	 * @method \Bitrix\Controller\EO_Member unsetNotes()
	 * @method \string fillNotes()
	 * @method \float getCounterFreeSpace()
	 * @method \Bitrix\Controller\EO_Member setCounterFreeSpace(\float|\Bitrix\Main\DB\SqlExpression $counterFreeSpace)
	 * @method bool hasCounterFreeSpace()
	 * @method bool isCounterFreeSpaceFilled()
	 * @method bool isCounterFreeSpaceChanged()
	 * @method \float remindActualCounterFreeSpace()
	 * @method \float requireCounterFreeSpace()
	 * @method \Bitrix\Controller\EO_Member resetCounterFreeSpace()
	 * @method \Bitrix\Controller\EO_Member unsetCounterFreeSpace()
	 * @method \float fillCounterFreeSpace()
	 * @method \int getCounterSites()
	 * @method \Bitrix\Controller\EO_Member setCounterSites(\int|\Bitrix\Main\DB\SqlExpression $counterSites)
	 * @method bool hasCounterSites()
	 * @method bool isCounterSitesFilled()
	 * @method bool isCounterSitesChanged()
	 * @method \int remindActualCounterSites()
	 * @method \int requireCounterSites()
	 * @method \Bitrix\Controller\EO_Member resetCounterSites()
	 * @method \Bitrix\Controller\EO_Member unsetCounterSites()
	 * @method \int fillCounterSites()
	 * @method \int getCounterUsers()
	 * @method \Bitrix\Controller\EO_Member setCounterUsers(\int|\Bitrix\Main\DB\SqlExpression $counterUsers)
	 * @method bool hasCounterUsers()
	 * @method bool isCounterUsersFilled()
	 * @method bool isCounterUsersChanged()
	 * @method \int remindActualCounterUsers()
	 * @method \int requireCounterUsers()
	 * @method \Bitrix\Controller\EO_Member resetCounterUsers()
	 * @method \Bitrix\Controller\EO_Member unsetCounterUsers()
	 * @method \int fillCounterUsers()
	 * @method \Bitrix\Main\Type\DateTime getCounterLastAuth()
	 * @method \Bitrix\Controller\EO_Member setCounterLastAuth(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $counterLastAuth)
	 * @method bool hasCounterLastAuth()
	 * @method bool isCounterLastAuthFilled()
	 * @method bool isCounterLastAuthChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCounterLastAuth()
	 * @method \Bitrix\Main\Type\DateTime requireCounterLastAuth()
	 * @method \Bitrix\Controller\EO_Member resetCounterLastAuth()
	 * @method \Bitrix\Controller\EO_Member unsetCounterLastAuth()
	 * @method \Bitrix\Main\Type\DateTime fillCounterLastAuth()
	 * @method \Bitrix\Main\Type\DateTime getCountersUpdated()
	 * @method \Bitrix\Controller\EO_Member setCountersUpdated(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $countersUpdated)
	 * @method bool hasCountersUpdated()
	 * @method bool isCountersUpdatedFilled()
	 * @method bool isCountersUpdatedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCountersUpdated()
	 * @method \Bitrix\Main\Type\DateTime requireCountersUpdated()
	 * @method \Bitrix\Controller\EO_Member resetCountersUpdated()
	 * @method \Bitrix\Controller\EO_Member unsetCountersUpdated()
	 * @method \Bitrix\Main\Type\DateTime fillCountersUpdated()
	 * @method \string getHostname()
	 * @method \Bitrix\Controller\EO_Member setHostname(\string|\Bitrix\Main\DB\SqlExpression $hostname)
	 * @method bool hasHostname()
	 * @method bool isHostnameFilled()
	 * @method bool isHostnameChanged()
	 * @method \string remindActualHostname()
	 * @method \string requireHostname()
	 * @method \Bitrix\Controller\EO_Member resetHostname()
	 * @method \Bitrix\Controller\EO_Member unsetHostname()
	 * @method \string fillHostname()
	 * @method \Bitrix\Controller\EO_Group getControllerGroup()
	 * @method \Bitrix\Controller\EO_Group remindActualControllerGroup()
	 * @method \Bitrix\Controller\EO_Group requireControllerGroup()
	 * @method \Bitrix\Controller\EO_Member setControllerGroup(\Bitrix\Controller\EO_Group $object)
	 * @method \Bitrix\Controller\EO_Member resetControllerGroup()
	 * @method \Bitrix\Controller\EO_Member unsetControllerGroup()
	 * @method bool hasControllerGroup()
	 * @method bool isControllerGroupFilled()
	 * @method bool isControllerGroupChanged()
	 * @method \Bitrix\Controller\EO_Group fillControllerGroup()
	 * @method \Bitrix\Main\EO_User getCreated()
	 * @method \Bitrix\Main\EO_User remindActualCreated()
	 * @method \Bitrix\Main\EO_User requireCreated()
	 * @method \Bitrix\Controller\EO_Member setCreated(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Controller\EO_Member resetCreated()
	 * @method \Bitrix\Controller\EO_Member unsetCreated()
	 * @method bool hasCreated()
	 * @method bool isCreatedFilled()
	 * @method bool isCreatedChanged()
	 * @method \Bitrix\Main\EO_User fillCreated()
	 * @method \Bitrix\Main\EO_User getModified()
	 * @method \Bitrix\Main\EO_User remindActualModified()
	 * @method \Bitrix\Main\EO_User requireModified()
	 * @method \Bitrix\Controller\EO_Member setModified(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Controller\EO_Member resetModified()
	 * @method \Bitrix\Controller\EO_Member unsetModified()
	 * @method bool hasModified()
	 * @method bool isModifiedFilled()
	 * @method bool isModifiedChanged()
	 * @method \Bitrix\Main\EO_User fillModified()
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
	 * @method \Bitrix\Controller\EO_Member set($fieldName, $value)
	 * @method \Bitrix\Controller\EO_Member reset($fieldName)
	 * @method \Bitrix\Controller\EO_Member unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Controller\EO_Member wakeUp($data)
	 */
	class EO_Member {
		/* @var \Bitrix\Controller\MemberTable */
		static public $dataClass = '\Bitrix\Controller\MemberTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Controller {
	/**
	 * EO_Member_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getMemberIdList()
	 * @method \string[] fillMemberId()
	 * @method \string[] getSecretIdList()
	 * @method \string[] fillSecretId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getUrlList()
	 * @method \string[] fillUrl()
	 * @method \string[] getEmailList()
	 * @method \string[] fillEmail()
	 * @method \string[] getContactPersonList()
	 * @method \string[] fillContactPerson()
	 * @method \int[] getControllerGroupIdList()
	 * @method \int[] fillControllerGroupId()
	 * @method \boolean[] getDisconnectedList()
	 * @method \boolean[] fillDisconnected()
	 * @method \boolean[] getSharedKernelList()
	 * @method \boolean[] fillSharedKernel()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \Bitrix\Main\Type\DateTime[] getDateActiveFromList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateActiveFrom()
	 * @method \Bitrix\Main\Type\DateTime[] getDateActiveToList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateActiveTo()
	 * @method \boolean[] getSiteActiveList()
	 * @method \boolean[] fillSiteActive()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getModifiedByList()
	 * @method \int[] fillModifiedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getInGroupFromList()
	 * @method \Bitrix\Main\Type\DateTime[] fillInGroupFrom()
	 * @method \string[] getNotesList()
	 * @method \string[] fillNotes()
	 * @method \float[] getCounterFreeSpaceList()
	 * @method \float[] fillCounterFreeSpace()
	 * @method \int[] getCounterSitesList()
	 * @method \int[] fillCounterSites()
	 * @method \int[] getCounterUsersList()
	 * @method \int[] fillCounterUsers()
	 * @method \Bitrix\Main\Type\DateTime[] getCounterLastAuthList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCounterLastAuth()
	 * @method \Bitrix\Main\Type\DateTime[] getCountersUpdatedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCountersUpdated()
	 * @method \string[] getHostnameList()
	 * @method \string[] fillHostname()
	 * @method \Bitrix\Controller\EO_Group[] getControllerGroupList()
	 * @method \Bitrix\Controller\EO_Member_Collection getControllerGroupCollection()
	 * @method \Bitrix\Controller\EO_Group_Collection fillControllerGroup()
	 * @method \Bitrix\Main\EO_User[] getCreatedList()
	 * @method \Bitrix\Controller\EO_Member_Collection getCreatedCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreated()
	 * @method \Bitrix\Main\EO_User[] getModifiedList()
	 * @method \Bitrix\Controller\EO_Member_Collection getModifiedCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillModified()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Controller\EO_Member $object)
	 * @method bool has(\Bitrix\Controller\EO_Member $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Controller\EO_Member getByPrimary($primary)
	 * @method \Bitrix\Controller\EO_Member[] getAll()
	 * @method bool remove(\Bitrix\Controller\EO_Member $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Controller\EO_Member_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Controller\EO_Member current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Member_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Controller\MemberTable */
		static public $dataClass = '\Bitrix\Controller\MemberTable';
	}
}
namespace Bitrix\Controller {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Member_Result exec()
	 * @method \Bitrix\Controller\EO_Member fetchObject()
	 * @method \Bitrix\Controller\EO_Member_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Member_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Controller\EO_Member fetchObject()
	 * @method \Bitrix\Controller\EO_Member_Collection fetchCollection()
	 */
	class EO_Member_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Controller\EO_Member createObject($setDefaultValues = true)
	 * @method \Bitrix\Controller\EO_Member_Collection createCollection()
	 * @method \Bitrix\Controller\EO_Member wakeUpObject($row)
	 * @method \Bitrix\Controller\EO_Member_Collection wakeUpCollection($rows)
	 */
	class EO_Member_Entity extends \Bitrix\Main\ORM\Entity {}
}