<?php

/* ORMENTITYANNOTATION:Bitrix\Faceid\AgreementTable:faceid/lib/agreement.php:646517d0bb1f3964a487abba9811117b */
namespace Bitrix\Faceid {
	/**
	 * EO_Agreement
	 * @see \Bitrix\Faceid\AgreementTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Faceid\EO_Agreement setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Faceid\EO_Agreement setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Faceid\EO_Agreement resetUserId()
	 * @method \Bitrix\Faceid\EO_Agreement unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getName()
	 * @method \Bitrix\Faceid\EO_Agreement setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Faceid\EO_Agreement resetName()
	 * @method \Bitrix\Faceid\EO_Agreement unsetName()
	 * @method \string fillName()
	 * @method \string getEmail()
	 * @method \Bitrix\Faceid\EO_Agreement setEmail(\string|\Bitrix\Main\DB\SqlExpression $email)
	 * @method bool hasEmail()
	 * @method bool isEmailFilled()
	 * @method bool isEmailChanged()
	 * @method \string remindActualEmail()
	 * @method \string requireEmail()
	 * @method \Bitrix\Faceid\EO_Agreement resetEmail()
	 * @method \Bitrix\Faceid\EO_Agreement unsetEmail()
	 * @method \string fillEmail()
	 * @method \Bitrix\Main\Type\DateTime getDate()
	 * @method \Bitrix\Faceid\EO_Agreement setDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $date)
	 * @method bool hasDate()
	 * @method bool isDateFilled()
	 * @method bool isDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDate()
	 * @method \Bitrix\Main\Type\DateTime requireDate()
	 * @method \Bitrix\Faceid\EO_Agreement resetDate()
	 * @method \Bitrix\Faceid\EO_Agreement unsetDate()
	 * @method \Bitrix\Main\Type\DateTime fillDate()
	 * @method \string getIpAddress()
	 * @method \Bitrix\Faceid\EO_Agreement setIpAddress(\string|\Bitrix\Main\DB\SqlExpression $ipAddress)
	 * @method bool hasIpAddress()
	 * @method bool isIpAddressFilled()
	 * @method bool isIpAddressChanged()
	 * @method \string remindActualIpAddress()
	 * @method \string requireIpAddress()
	 * @method \Bitrix\Faceid\EO_Agreement resetIpAddress()
	 * @method \Bitrix\Faceid\EO_Agreement unsetIpAddress()
	 * @method \string fillIpAddress()
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
	 * @method \Bitrix\Faceid\EO_Agreement set($fieldName, $value)
	 * @method \Bitrix\Faceid\EO_Agreement reset($fieldName)
	 * @method \Bitrix\Faceid\EO_Agreement unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Faceid\EO_Agreement wakeUp($data)
	 */
	class EO_Agreement {
		/* @var \Bitrix\Faceid\AgreementTable */
		static public $dataClass = '\Bitrix\Faceid\AgreementTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Faceid {
	/**
	 * EO_Agreement_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getEmailList()
	 * @method \string[] fillEmail()
	 * @method \Bitrix\Main\Type\DateTime[] getDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDate()
	 * @method \string[] getIpAddressList()
	 * @method \string[] fillIpAddress()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Faceid\EO_Agreement $object)
	 * @method bool has(\Bitrix\Faceid\EO_Agreement $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_Agreement getByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_Agreement[] getAll()
	 * @method bool remove(\Bitrix\Faceid\EO_Agreement $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Faceid\EO_Agreement_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Faceid\EO_Agreement current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Agreement_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Faceid\AgreementTable */
		static public $dataClass = '\Bitrix\Faceid\AgreementTable';
	}
}
namespace Bitrix\Faceid {
	/**
	 * @method static EO_Agreement_Query query()
	 * @method static EO_Agreement_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Agreement_Result getById($id)
	 * @method static EO_Agreement_Result getList(array $parameters = array())
	 * @method static EO_Agreement_Entity getEntity()
	 * @method static \Bitrix\Faceid\EO_Agreement createObject($setDefaultValues = true)
	 * @method static \Bitrix\Faceid\EO_Agreement_Collection createCollection()
	 * @method static \Bitrix\Faceid\EO_Agreement wakeUpObject($row)
	 * @method static \Bitrix\Faceid\EO_Agreement_Collection wakeUpCollection($rows)
	 */
	class AgreementTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Agreement_Result exec()
	 * @method \Bitrix\Faceid\EO_Agreement fetchObject()
	 * @method \Bitrix\Faceid\EO_Agreement_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Agreement_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Faceid\EO_Agreement fetchObject()
	 * @method \Bitrix\Faceid\EO_Agreement_Collection fetchCollection()
	 */
	class EO_Agreement_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Faceid\EO_Agreement createObject($setDefaultValues = true)
	 * @method \Bitrix\Faceid\EO_Agreement_Collection createCollection()
	 * @method \Bitrix\Faceid\EO_Agreement wakeUpObject($row)
	 * @method \Bitrix\Faceid\EO_Agreement_Collection wakeUpCollection($rows)
	 */
	class EO_Agreement_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Faceid\FaceTable:faceid/lib/face.php:5d4565b6b7078488319be98ee0087d2c */
namespace Bitrix\Faceid {
	/**
	 * EO_Face
	 * @see \Bitrix\Faceid\FaceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Faceid\EO_Face setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFileId()
	 * @method \Bitrix\Faceid\EO_Face setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Faceid\EO_Face resetFileId()
	 * @method \Bitrix\Faceid\EO_Face unsetFileId()
	 * @method \int fillFileId()
	 * @method \int getCloudFaceId()
	 * @method \Bitrix\Faceid\EO_Face setCloudFaceId(\int|\Bitrix\Main\DB\SqlExpression $cloudFaceId)
	 * @method bool hasCloudFaceId()
	 * @method bool isCloudFaceIdFilled()
	 * @method bool isCloudFaceIdChanged()
	 * @method \int remindActualCloudFaceId()
	 * @method \int requireCloudFaceId()
	 * @method \Bitrix\Faceid\EO_Face resetCloudFaceId()
	 * @method \Bitrix\Faceid\EO_Face unsetCloudFaceId()
	 * @method \int fillCloudFaceId()
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
	 * @method \Bitrix\Faceid\EO_Face set($fieldName, $value)
	 * @method \Bitrix\Faceid\EO_Face reset($fieldName)
	 * @method \Bitrix\Faceid\EO_Face unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Faceid\EO_Face wakeUp($data)
	 */
	class EO_Face {
		/* @var \Bitrix\Faceid\FaceTable */
		static public $dataClass = '\Bitrix\Faceid\FaceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Faceid {
	/**
	 * EO_Face_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \int[] getCloudFaceIdList()
	 * @method \int[] fillCloudFaceId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Faceid\EO_Face $object)
	 * @method bool has(\Bitrix\Faceid\EO_Face $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_Face getByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_Face[] getAll()
	 * @method bool remove(\Bitrix\Faceid\EO_Face $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Faceid\EO_Face_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Faceid\EO_Face current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Face_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Faceid\FaceTable */
		static public $dataClass = '\Bitrix\Faceid\FaceTable';
	}
}
namespace Bitrix\Faceid {
	/**
	 * @method static EO_Face_Query query()
	 * @method static EO_Face_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Face_Result getById($id)
	 * @method static EO_Face_Result getList(array $parameters = array())
	 * @method static EO_Face_Entity getEntity()
	 * @method static \Bitrix\Faceid\EO_Face createObject($setDefaultValues = true)
	 * @method static \Bitrix\Faceid\EO_Face_Collection createCollection()
	 * @method static \Bitrix\Faceid\EO_Face wakeUpObject($row)
	 * @method static \Bitrix\Faceid\EO_Face_Collection wakeUpCollection($rows)
	 */
	class FaceTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Face_Result exec()
	 * @method \Bitrix\Faceid\EO_Face fetchObject()
	 * @method \Bitrix\Faceid\EO_Face_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Face_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Faceid\EO_Face fetchObject()
	 * @method \Bitrix\Faceid\EO_Face_Collection fetchCollection()
	 */
	class EO_Face_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Faceid\EO_Face createObject($setDefaultValues = true)
	 * @method \Bitrix\Faceid\EO_Face_Collection createCollection()
	 * @method \Bitrix\Faceid\EO_Face wakeUpObject($row)
	 * @method \Bitrix\Faceid\EO_Face_Collection wakeUpCollection($rows)
	 */
	class EO_Face_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Faceid\TrackingVisitorsTable:faceid/lib/trackingvisitors.php:95e7215a49608ff4d46bd4c7d63c199a */
namespace Bitrix\Faceid {
	/**
	 * EO_TrackingVisitors
	 * @see \Bitrix\Faceid\TrackingVisitorsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFileId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors resetFileId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unsetFileId()
	 * @method \int fillFileId()
	 * @method \int getFaceId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setFaceId(\int|\Bitrix\Main\DB\SqlExpression $faceId)
	 * @method bool hasFaceId()
	 * @method bool isFaceIdFilled()
	 * @method bool isFaceIdChanged()
	 * @method \int remindActualFaceId()
	 * @method \int requireFaceId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors resetFaceId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unsetFaceId()
	 * @method \int fillFaceId()
	 * @method \int getCrmId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setCrmId(\int|\Bitrix\Main\DB\SqlExpression $crmId)
	 * @method bool hasCrmId()
	 * @method bool isCrmIdFilled()
	 * @method bool isCrmIdChanged()
	 * @method \int remindActualCrmId()
	 * @method \int requireCrmId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors resetCrmId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unsetCrmId()
	 * @method \int fillCrmId()
	 * @method \string getVkId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setVkId(\string|\Bitrix\Main\DB\SqlExpression $vkId)
	 * @method bool hasVkId()
	 * @method bool isVkIdFilled()
	 * @method bool isVkIdChanged()
	 * @method \string remindActualVkId()
	 * @method \string requireVkId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors resetVkId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unsetVkId()
	 * @method \string fillVkId()
	 * @method \Bitrix\Main\Type\DateTime getFirstVisit()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setFirstVisit(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $firstVisit)
	 * @method bool hasFirstVisit()
	 * @method bool isFirstVisitFilled()
	 * @method bool isFirstVisitChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualFirstVisit()
	 * @method \Bitrix\Main\Type\DateTime requireFirstVisit()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors resetFirstVisit()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unsetFirstVisit()
	 * @method \Bitrix\Main\Type\DateTime fillFirstVisit()
	 * @method \Bitrix\Main\Type\DateTime getPrelastVisit()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setPrelastVisit(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $prelastVisit)
	 * @method bool hasPrelastVisit()
	 * @method bool isPrelastVisitFilled()
	 * @method bool isPrelastVisitChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualPrelastVisit()
	 * @method \Bitrix\Main\Type\DateTime requirePrelastVisit()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors resetPrelastVisit()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unsetPrelastVisit()
	 * @method \Bitrix\Main\Type\DateTime fillPrelastVisit()
	 * @method \Bitrix\Main\Type\DateTime getLastVisit()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setLastVisit(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastVisit)
	 * @method bool hasLastVisit()
	 * @method bool isLastVisitFilled()
	 * @method bool isLastVisitChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastVisit()
	 * @method \Bitrix\Main\Type\DateTime requireLastVisit()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors resetLastVisit()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unsetLastVisit()
	 * @method \Bitrix\Main\Type\DateTime fillLastVisit()
	 * @method \int getLastVisitId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setLastVisitId(\int|\Bitrix\Main\DB\SqlExpression $lastVisitId)
	 * @method bool hasLastVisitId()
	 * @method bool isLastVisitIdFilled()
	 * @method bool isLastVisitIdChanged()
	 * @method \int remindActualLastVisitId()
	 * @method \int requireLastVisitId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors resetLastVisitId()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unsetLastVisitId()
	 * @method \int fillLastVisitId()
	 * @method \int getVisitsCount()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors setVisitsCount(\int|\Bitrix\Main\DB\SqlExpression $visitsCount)
	 * @method bool hasVisitsCount()
	 * @method bool isVisitsCountFilled()
	 * @method bool isVisitsCountChanged()
	 * @method \int remindActualVisitsCount()
	 * @method \int requireVisitsCount()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors resetVisitsCount()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unsetVisitsCount()
	 * @method \int fillVisitsCount()
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
	 * @method \Bitrix\Faceid\EO_TrackingVisitors set($fieldName, $value)
	 * @method \Bitrix\Faceid\EO_TrackingVisitors reset($fieldName)
	 * @method \Bitrix\Faceid\EO_TrackingVisitors unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Faceid\EO_TrackingVisitors wakeUp($data)
	 */
	class EO_TrackingVisitors {
		/* @var \Bitrix\Faceid\TrackingVisitorsTable */
		static public $dataClass = '\Bitrix\Faceid\TrackingVisitorsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Faceid {
	/**
	 * EO_TrackingVisitors_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \int[] getFaceIdList()
	 * @method \int[] fillFaceId()
	 * @method \int[] getCrmIdList()
	 * @method \int[] fillCrmId()
	 * @method \string[] getVkIdList()
	 * @method \string[] fillVkId()
	 * @method \Bitrix\Main\Type\DateTime[] getFirstVisitList()
	 * @method \Bitrix\Main\Type\DateTime[] fillFirstVisit()
	 * @method \Bitrix\Main\Type\DateTime[] getPrelastVisitList()
	 * @method \Bitrix\Main\Type\DateTime[] fillPrelastVisit()
	 * @method \Bitrix\Main\Type\DateTime[] getLastVisitList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLastVisit()
	 * @method \int[] getLastVisitIdList()
	 * @method \int[] fillLastVisitId()
	 * @method \int[] getVisitsCountList()
	 * @method \int[] fillVisitsCount()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Faceid\EO_TrackingVisitors $object)
	 * @method bool has(\Bitrix\Faceid\EO_TrackingVisitors $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_TrackingVisitors getByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_TrackingVisitors[] getAll()
	 * @method bool remove(\Bitrix\Faceid\EO_TrackingVisitors $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Faceid\EO_TrackingVisitors_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Faceid\EO_TrackingVisitors current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TrackingVisitors_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Faceid\TrackingVisitorsTable */
		static public $dataClass = '\Bitrix\Faceid\TrackingVisitorsTable';
	}
}
namespace Bitrix\Faceid {
	/**
	 * @method static EO_TrackingVisitors_Query query()
	 * @method static EO_TrackingVisitors_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_TrackingVisitors_Result getById($id)
	 * @method static EO_TrackingVisitors_Result getList(array $parameters = array())
	 * @method static EO_TrackingVisitors_Entity getEntity()
	 * @method static \Bitrix\Faceid\EO_TrackingVisitors createObject($setDefaultValues = true)
	 * @method static \Bitrix\Faceid\EO_TrackingVisitors_Collection createCollection()
	 * @method static \Bitrix\Faceid\EO_TrackingVisitors wakeUpObject($row)
	 * @method static \Bitrix\Faceid\EO_TrackingVisitors_Collection wakeUpCollection($rows)
	 */
	class TrackingVisitorsTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TrackingVisitors_Result exec()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors fetchObject()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TrackingVisitors_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Faceid\EO_TrackingVisitors fetchObject()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors_Collection fetchCollection()
	 */
	class EO_TrackingVisitors_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Faceid\EO_TrackingVisitors createObject($setDefaultValues = true)
	 * @method \Bitrix\Faceid\EO_TrackingVisitors_Collection createCollection()
	 * @method \Bitrix\Faceid\EO_TrackingVisitors wakeUpObject($row)
	 * @method \Bitrix\Faceid\EO_TrackingVisitors_Collection wakeUpCollection($rows)
	 */
	class EO_TrackingVisitors_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Faceid\TrackingVisitsTable:faceid/lib/trackingvisits.php:7ab00c71a3923cf90ef18081299108de */
namespace Bitrix\Faceid {
	/**
	 * EO_TrackingVisits
	 * @see \Bitrix\Faceid\TrackingVisitsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Faceid\EO_TrackingVisits setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getVisitorId()
	 * @method \Bitrix\Faceid\EO_TrackingVisits setVisitorId(\int|\Bitrix\Main\DB\SqlExpression $visitorId)
	 * @method bool hasVisitorId()
	 * @method bool isVisitorIdFilled()
	 * @method bool isVisitorIdChanged()
	 * @method \int remindActualVisitorId()
	 * @method \int requireVisitorId()
	 * @method \Bitrix\Faceid\EO_TrackingVisits resetVisitorId()
	 * @method \Bitrix\Faceid\EO_TrackingVisits unsetVisitorId()
	 * @method \int fillVisitorId()
	 * @method \Bitrix\Main\Type\DateTime getDate()
	 * @method \Bitrix\Faceid\EO_TrackingVisits setDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $date)
	 * @method bool hasDate()
	 * @method bool isDateFilled()
	 * @method bool isDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDate()
	 * @method \Bitrix\Main\Type\DateTime requireDate()
	 * @method \Bitrix\Faceid\EO_TrackingVisits resetDate()
	 * @method \Bitrix\Faceid\EO_TrackingVisits unsetDate()
	 * @method \Bitrix\Main\Type\DateTime fillDate()
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
	 * @method \Bitrix\Faceid\EO_TrackingVisits set($fieldName, $value)
	 * @method \Bitrix\Faceid\EO_TrackingVisits reset($fieldName)
	 * @method \Bitrix\Faceid\EO_TrackingVisits unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Faceid\EO_TrackingVisits wakeUp($data)
	 */
	class EO_TrackingVisits {
		/* @var \Bitrix\Faceid\TrackingVisitsTable */
		static public $dataClass = '\Bitrix\Faceid\TrackingVisitsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Faceid {
	/**
	 * EO_TrackingVisits_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getVisitorIdList()
	 * @method \int[] fillVisitorId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Faceid\EO_TrackingVisits $object)
	 * @method bool has(\Bitrix\Faceid\EO_TrackingVisits $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_TrackingVisits getByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_TrackingVisits[] getAll()
	 * @method bool remove(\Bitrix\Faceid\EO_TrackingVisits $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Faceid\EO_TrackingVisits_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Faceid\EO_TrackingVisits current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TrackingVisits_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Faceid\TrackingVisitsTable */
		static public $dataClass = '\Bitrix\Faceid\TrackingVisitsTable';
	}
}
namespace Bitrix\Faceid {
	/**
	 * @method static EO_TrackingVisits_Query query()
	 * @method static EO_TrackingVisits_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_TrackingVisits_Result getById($id)
	 * @method static EO_TrackingVisits_Result getList(array $parameters = array())
	 * @method static EO_TrackingVisits_Entity getEntity()
	 * @method static \Bitrix\Faceid\EO_TrackingVisits createObject($setDefaultValues = true)
	 * @method static \Bitrix\Faceid\EO_TrackingVisits_Collection createCollection()
	 * @method static \Bitrix\Faceid\EO_TrackingVisits wakeUpObject($row)
	 * @method static \Bitrix\Faceid\EO_TrackingVisits_Collection wakeUpCollection($rows)
	 */
	class TrackingVisitsTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TrackingVisits_Result exec()
	 * @method \Bitrix\Faceid\EO_TrackingVisits fetchObject()
	 * @method \Bitrix\Faceid\EO_TrackingVisits_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TrackingVisits_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Faceid\EO_TrackingVisits fetchObject()
	 * @method \Bitrix\Faceid\EO_TrackingVisits_Collection fetchCollection()
	 */
	class EO_TrackingVisits_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Faceid\EO_TrackingVisits createObject($setDefaultValues = true)
	 * @method \Bitrix\Faceid\EO_TrackingVisits_Collection createCollection()
	 * @method \Bitrix\Faceid\EO_TrackingVisits wakeUpObject($row)
	 * @method \Bitrix\Faceid\EO_TrackingVisits_Collection wakeUpCollection($rows)
	 */
	class EO_TrackingVisits_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Faceid\TrackingWorkdayTable:faceid/lib/trackingworkday.php:92a514b53be7090fc5960f1882969406 */
namespace Bitrix\Faceid {
	/**
	 * EO_TrackingWorkday
	 * @see \Bitrix\Faceid\TrackingWorkdayTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday resetUserId()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\Type\DateTime getDate()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday setDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $date)
	 * @method bool hasDate()
	 * @method bool isDateFilled()
	 * @method bool isDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDate()
	 * @method \Bitrix\Main\Type\DateTime requireDate()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday resetDate()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday unsetDate()
	 * @method \Bitrix\Main\Type\DateTime fillDate()
	 * @method \string getAction()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday resetAction()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday unsetAction()
	 * @method \string fillAction()
	 * @method \int getSnapshotId()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday setSnapshotId(\int|\Bitrix\Main\DB\SqlExpression $snapshotId)
	 * @method bool hasSnapshotId()
	 * @method bool isSnapshotIdFilled()
	 * @method bool isSnapshotIdChanged()
	 * @method \int remindActualSnapshotId()
	 * @method \int requireSnapshotId()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday resetSnapshotId()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday unsetSnapshotId()
	 * @method \int fillSnapshotId()
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
	 * @method \Bitrix\Faceid\EO_TrackingWorkday set($fieldName, $value)
	 * @method \Bitrix\Faceid\EO_TrackingWorkday reset($fieldName)
	 * @method \Bitrix\Faceid\EO_TrackingWorkday unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Faceid\EO_TrackingWorkday wakeUp($data)
	 */
	class EO_TrackingWorkday {
		/* @var \Bitrix\Faceid\TrackingWorkdayTable */
		static public $dataClass = '\Bitrix\Faceid\TrackingWorkdayTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Faceid {
	/**
	 * EO_TrackingWorkday_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDate()
	 * @method \string[] getActionList()
	 * @method \string[] fillAction()
	 * @method \int[] getSnapshotIdList()
	 * @method \int[] fillSnapshotId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Faceid\EO_TrackingWorkday $object)
	 * @method bool has(\Bitrix\Faceid\EO_TrackingWorkday $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_TrackingWorkday getByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_TrackingWorkday[] getAll()
	 * @method bool remove(\Bitrix\Faceid\EO_TrackingWorkday $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Faceid\EO_TrackingWorkday_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Faceid\EO_TrackingWorkday current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TrackingWorkday_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Faceid\TrackingWorkdayTable */
		static public $dataClass = '\Bitrix\Faceid\TrackingWorkdayTable';
	}
}
namespace Bitrix\Faceid {
	/**
	 * @method static EO_TrackingWorkday_Query query()
	 * @method static EO_TrackingWorkday_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_TrackingWorkday_Result getById($id)
	 * @method static EO_TrackingWorkday_Result getList(array $parameters = array())
	 * @method static EO_TrackingWorkday_Entity getEntity()
	 * @method static \Bitrix\Faceid\EO_TrackingWorkday createObject($setDefaultValues = true)
	 * @method static \Bitrix\Faceid\EO_TrackingWorkday_Collection createCollection()
	 * @method static \Bitrix\Faceid\EO_TrackingWorkday wakeUpObject($row)
	 * @method static \Bitrix\Faceid\EO_TrackingWorkday_Collection wakeUpCollection($rows)
	 */
	class TrackingWorkdayTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TrackingWorkday_Result exec()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday fetchObject()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TrackingWorkday_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Faceid\EO_TrackingWorkday fetchObject()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday_Collection fetchCollection()
	 */
	class EO_TrackingWorkday_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Faceid\EO_TrackingWorkday createObject($setDefaultValues = true)
	 * @method \Bitrix\Faceid\EO_TrackingWorkday_Collection createCollection()
	 * @method \Bitrix\Faceid\EO_TrackingWorkday wakeUpObject($row)
	 * @method \Bitrix\Faceid\EO_TrackingWorkday_Collection wakeUpCollection($rows)
	 */
	class EO_TrackingWorkday_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Faceid\UsersTable:faceid/lib/users.php:e8dd46792b21359dfbc0ab39524d4454 */
namespace Bitrix\Faceid {
	/**
	 * EO_Users
	 * @see \Bitrix\Faceid\UsersTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Faceid\EO_Users setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFileId()
	 * @method \Bitrix\Faceid\EO_Users setFileId(\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method \int remindActualFileId()
	 * @method \int requireFileId()
	 * @method \Bitrix\Faceid\EO_Users resetFileId()
	 * @method \Bitrix\Faceid\EO_Users unsetFileId()
	 * @method \int fillFileId()
	 * @method \int getCloudFaceId()
	 * @method \Bitrix\Faceid\EO_Users setCloudFaceId(\int|\Bitrix\Main\DB\SqlExpression $cloudFaceId)
	 * @method bool hasCloudFaceId()
	 * @method bool isCloudFaceIdFilled()
	 * @method bool isCloudFaceIdChanged()
	 * @method \int remindActualCloudFaceId()
	 * @method \int requireCloudFaceId()
	 * @method \Bitrix\Faceid\EO_Users resetCloudFaceId()
	 * @method \Bitrix\Faceid\EO_Users unsetCloudFaceId()
	 * @method \int fillCloudFaceId()
	 * @method \int getUserId()
	 * @method \Bitrix\Faceid\EO_Users setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Faceid\EO_Users resetUserId()
	 * @method \Bitrix\Faceid\EO_Users unsetUserId()
	 * @method \int fillUserId()
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
	 * @method \Bitrix\Faceid\EO_Users set($fieldName, $value)
	 * @method \Bitrix\Faceid\EO_Users reset($fieldName)
	 * @method \Bitrix\Faceid\EO_Users unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Faceid\EO_Users wakeUp($data)
	 */
	class EO_Users {
		/* @var \Bitrix\Faceid\UsersTable */
		static public $dataClass = '\Bitrix\Faceid\UsersTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Faceid {
	/**
	 * EO_Users_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFileIdList()
	 * @method \int[] fillFileId()
	 * @method \int[] getCloudFaceIdList()
	 * @method \int[] fillCloudFaceId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Faceid\EO_Users $object)
	 * @method bool has(\Bitrix\Faceid\EO_Users $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_Users getByPrimary($primary)
	 * @method \Bitrix\Faceid\EO_Users[] getAll()
	 * @method bool remove(\Bitrix\Faceid\EO_Users $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Faceid\EO_Users_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Faceid\EO_Users current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Users_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Faceid\UsersTable */
		static public $dataClass = '\Bitrix\Faceid\UsersTable';
	}
}
namespace Bitrix\Faceid {
	/**
	 * @method static EO_Users_Query query()
	 * @method static EO_Users_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Users_Result getById($id)
	 * @method static EO_Users_Result getList(array $parameters = array())
	 * @method static EO_Users_Entity getEntity()
	 * @method static \Bitrix\Faceid\EO_Users createObject($setDefaultValues = true)
	 * @method static \Bitrix\Faceid\EO_Users_Collection createCollection()
	 * @method static \Bitrix\Faceid\EO_Users wakeUpObject($row)
	 * @method static \Bitrix\Faceid\EO_Users_Collection wakeUpCollection($rows)
	 */
	class UsersTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Users_Result exec()
	 * @method \Bitrix\Faceid\EO_Users fetchObject()
	 * @method \Bitrix\Faceid\EO_Users_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Users_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Faceid\EO_Users fetchObject()
	 * @method \Bitrix\Faceid\EO_Users_Collection fetchCollection()
	 */
	class EO_Users_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Faceid\EO_Users createObject($setDefaultValues = true)
	 * @method \Bitrix\Faceid\EO_Users_Collection createCollection()
	 * @method \Bitrix\Faceid\EO_Users wakeUpObject($row)
	 * @method \Bitrix\Faceid\EO_Users_Collection wakeUpCollection($rows)
	 */
	class EO_Users_Entity extends \Bitrix\Main\ORM\Entity {}
}