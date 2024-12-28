<?php

/* ORMENTITYANNOTATION:Bitrix\Call\Model\CallTrackTable:call/lib/model/calltracktable.php */
namespace Bitrix\Call\Model {
	/**
	 * Track
	 * @see \Bitrix\Call\Model\CallTrackTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Call\Track setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCallId()
	 * @method \Bitrix\Call\Track setCallId(\int|\Bitrix\Main\DB\SqlExpression $callId)
	 * @method bool hasCallId()
	 * @method bool isCallIdFilled()
	 * @method bool isCallIdChanged()
	 * @method \int remindActualCallId()
	 * @method \int requireCallId()
	 * @method \Bitrix\Call\Track resetCallId()
	 * @method \Bitrix\Call\Track unsetCallId()
	 * @method \int fillCallId()
	 * @method null|\int getExternalTrackId()
	 * @method \Bitrix\Call\Track setExternalTrackId(null|\int|\Bitrix\Main\DB\SqlExpression $externalTrackId)
	 * @method bool hasExternalTrackId()
	 * @method bool isExternalTrackIdFilled()
	 * @method bool isExternalTrackIdChanged()
	 * @method null|\int remindActualExternalTrackId()
	 * @method null|\int requireExternalTrackId()
	 * @method \Bitrix\Call\Track resetExternalTrackId()
	 * @method \Bitrix\Call\Track unsetExternalTrackId()
	 * @method null|\int fillExternalTrackId()
	 * @method null|\int getFileId()
	 * @method \Bitrix\Call\Track setFileId(null|\int|\Bitrix\Main\DB\SqlExpression $fileId)
	 * @method bool hasFileId()
	 * @method bool isFileIdFilled()
	 * @method bool isFileIdChanged()
	 * @method null|\int remindActualFileId()
	 * @method null|\int requireFileId()
	 * @method \Bitrix\Call\Track resetFileId()
	 * @method \Bitrix\Call\Track unsetFileId()
	 * @method null|\int fillFileId()
	 * @method null|\int getDiskFileId()
	 * @method \Bitrix\Call\Track setDiskFileId(null|\int|\Bitrix\Main\DB\SqlExpression $diskFileId)
	 * @method bool hasDiskFileId()
	 * @method bool isDiskFileIdFilled()
	 * @method bool isDiskFileIdChanged()
	 * @method null|\int remindActualDiskFileId()
	 * @method null|\int requireDiskFileId()
	 * @method \Bitrix\Call\Track resetDiskFileId()
	 * @method \Bitrix\Call\Track unsetDiskFileId()
	 * @method null|\int fillDiskFileId()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Call\Track setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Call\Track resetDateCreate()
	 * @method \Bitrix\Call\Track unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method null|\string getType()
	 * @method \Bitrix\Call\Track setType(null|\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method null|\string remindActualType()
	 * @method null|\string requireType()
	 * @method \Bitrix\Call\Track resetType()
	 * @method \Bitrix\Call\Track unsetType()
	 * @method null|\string fillType()
	 * @method \string getDownloadUrl()
	 * @method \Bitrix\Call\Track setDownloadUrl(\string|\Bitrix\Main\DB\SqlExpression $downloadUrl)
	 * @method bool hasDownloadUrl()
	 * @method bool isDownloadUrlFilled()
	 * @method bool isDownloadUrlChanged()
	 * @method \string remindActualDownloadUrl()
	 * @method \string requireDownloadUrl()
	 * @method \Bitrix\Call\Track resetDownloadUrl()
	 * @method \Bitrix\Call\Track unsetDownloadUrl()
	 * @method \string fillDownloadUrl()
	 * @method null|\string getFileName()
	 * @method \Bitrix\Call\Track setFileName(null|\string|\Bitrix\Main\DB\SqlExpression $fileName)
	 * @method bool hasFileName()
	 * @method bool isFileNameFilled()
	 * @method bool isFileNameChanged()
	 * @method null|\string remindActualFileName()
	 * @method null|\string requireFileName()
	 * @method \Bitrix\Call\Track resetFileName()
	 * @method \Bitrix\Call\Track unsetFileName()
	 * @method null|\string fillFileName()
	 * @method null|\int getDuration()
	 * @method \Bitrix\Call\Track setDuration(null|\int|\Bitrix\Main\DB\SqlExpression $duration)
	 * @method bool hasDuration()
	 * @method bool isDurationFilled()
	 * @method bool isDurationChanged()
	 * @method null|\int remindActualDuration()
	 * @method null|\int requireDuration()
	 * @method \Bitrix\Call\Track resetDuration()
	 * @method \Bitrix\Call\Track unsetDuration()
	 * @method null|\int fillDuration()
	 * @method null|\int getFileSize()
	 * @method \Bitrix\Call\Track setFileSize(null|\int|\Bitrix\Main\DB\SqlExpression $fileSize)
	 * @method bool hasFileSize()
	 * @method bool isFileSizeFilled()
	 * @method bool isFileSizeChanged()
	 * @method null|\int remindActualFileSize()
	 * @method null|\int requireFileSize()
	 * @method \Bitrix\Call\Track resetFileSize()
	 * @method \Bitrix\Call\Track unsetFileSize()
	 * @method null|\int fillFileSize()
	 * @method null|\string getFileMimeType()
	 * @method \Bitrix\Call\Track setFileMimeType(null|\string|\Bitrix\Main\DB\SqlExpression $fileMimeType)
	 * @method bool hasFileMimeType()
	 * @method bool isFileMimeTypeFilled()
	 * @method bool isFileMimeTypeChanged()
	 * @method null|\string remindActualFileMimeType()
	 * @method null|\string requireFileMimeType()
	 * @method \Bitrix\Call\Track resetFileMimeType()
	 * @method \Bitrix\Call\Track unsetFileMimeType()
	 * @method null|\string fillFileMimeType()
	 * @method null|\string getTempPath()
	 * @method \Bitrix\Call\Track setTempPath(null|\string|\Bitrix\Main\DB\SqlExpression $tempPath)
	 * @method bool hasTempPath()
	 * @method bool isTempPathFilled()
	 * @method bool isTempPathChanged()
	 * @method null|\string remindActualTempPath()
	 * @method null|\string requireTempPath()
	 * @method \Bitrix\Call\Track resetTempPath()
	 * @method \Bitrix\Call\Track unsetTempPath()
	 * @method null|\string fillTempPath()
	 * @method \boolean getDownloaded()
	 * @method \Bitrix\Call\Track setDownloaded(\boolean|\Bitrix\Main\DB\SqlExpression $downloaded)
	 * @method bool hasDownloaded()
	 * @method bool isDownloadedFilled()
	 * @method bool isDownloadedChanged()
	 * @method \boolean remindActualDownloaded()
	 * @method \boolean requireDownloaded()
	 * @method \Bitrix\Call\Track resetDownloaded()
	 * @method \Bitrix\Call\Track unsetDownloaded()
	 * @method \boolean fillDownloaded()
	 * @method \Bitrix\Im\Model\EO_Call getCall()
	 * @method \Bitrix\Im\Model\EO_Call remindActualCall()
	 * @method \Bitrix\Im\Model\EO_Call requireCall()
	 * @method \Bitrix\Call\Track setCall(\Bitrix\Im\Model\EO_Call $object)
	 * @method \Bitrix\Call\Track resetCall()
	 * @method \Bitrix\Call\Track unsetCall()
	 * @method bool hasCall()
	 * @method bool isCallFilled()
	 * @method bool isCallChanged()
	 * @method \Bitrix\Im\Model\EO_Call fillCall()
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
	 * @method \Bitrix\Call\Track set($fieldName, $value)
	 * @method \Bitrix\Call\Track reset($fieldName)
	 * @method \Bitrix\Call\Track unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Call\Track wakeUp($data)
	 */
	class EO_CallTrack {
		/* @var \Bitrix\Call\Model\CallTrackTable */
		static public $dataClass = '\Bitrix\Call\Model\CallTrackTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Call\Model {
	/**
	 * TrackCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCallIdList()
	 * @method \int[] fillCallId()
	 * @method null|\int[] getExternalTrackIdList()
	 * @method null|\int[] fillExternalTrackId()
	 * @method null|\int[] getFileIdList()
	 * @method null|\int[] fillFileId()
	 * @method null|\int[] getDiskFileIdList()
	 * @method null|\int[] fillDiskFileId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method null|\string[] getTypeList()
	 * @method null|\string[] fillType()
	 * @method \string[] getDownloadUrlList()
	 * @method \string[] fillDownloadUrl()
	 * @method null|\string[] getFileNameList()
	 * @method null|\string[] fillFileName()
	 * @method null|\int[] getDurationList()
	 * @method null|\int[] fillDuration()
	 * @method null|\int[] getFileSizeList()
	 * @method null|\int[] fillFileSize()
	 * @method null|\string[] getFileMimeTypeList()
	 * @method null|\string[] fillFileMimeType()
	 * @method null|\string[] getTempPathList()
	 * @method null|\string[] fillTempPath()
	 * @method \boolean[] getDownloadedList()
	 * @method \boolean[] fillDownloaded()
	 * @method \Bitrix\Im\Model\EO_Call[] getCallList()
	 * @method \Bitrix\Call\Track\TrackCollection getCallCollection()
	 * @method \Bitrix\Im\Model\EO_Call_Collection fillCall()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Call\Track $object)
	 * @method bool has(\Bitrix\Call\Track $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Call\Track getByPrimary($primary)
	 * @method \Bitrix\Call\Track[] getAll()
	 * @method bool remove(\Bitrix\Call\Track $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Call\Track\TrackCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Call\Track current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Call\Track\TrackCollection merge(?\Bitrix\Call\Track\TrackCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_CallTrack_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Call\Model\CallTrackTable */
		static public $dataClass = '\Bitrix\Call\Model\CallTrackTable';
	}
}
namespace Bitrix\Call\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CallTrack_Result exec()
	 * @method \Bitrix\Call\Track fetchObject()
	 * @method \Bitrix\Call\Track\TrackCollection fetchCollection()
	 */
	class EO_CallTrack_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Call\Track fetchObject()
	 * @method \Bitrix\Call\Track\TrackCollection fetchCollection()
	 */
	class EO_CallTrack_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Call\Track createObject($setDefaultValues = true)
	 * @method \Bitrix\Call\Track\TrackCollection createCollection()
	 * @method \Bitrix\Call\Track wakeUpObject($row)
	 * @method \Bitrix\Call\Track\TrackCollection wakeUpCollection($rows)
	 */
	class EO_CallTrack_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Call\Model\CallOutcomeTable:call/lib/model/calloutcometable.php */
namespace Bitrix\Call\Model {
	/**
	 * Outcome
	 * @see \Bitrix\Call\Model\CallOutcomeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Call\Integration\AI\Outcome setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCallId()
	 * @method \Bitrix\Call\Integration\AI\Outcome setCallId(\int|\Bitrix\Main\DB\SqlExpression $callId)
	 * @method bool hasCallId()
	 * @method bool isCallIdFilled()
	 * @method bool isCallIdChanged()
	 * @method \int remindActualCallId()
	 * @method \int requireCallId()
	 * @method \Bitrix\Call\Integration\AI\Outcome resetCallId()
	 * @method \Bitrix\Call\Integration\AI\Outcome unsetCallId()
	 * @method \int fillCallId()
	 * @method null|\int getTrackId()
	 * @method \Bitrix\Call\Integration\AI\Outcome setTrackId(null|\int|\Bitrix\Main\DB\SqlExpression $trackId)
	 * @method bool hasTrackId()
	 * @method bool isTrackIdFilled()
	 * @method bool isTrackIdChanged()
	 * @method null|\int remindActualTrackId()
	 * @method null|\int requireTrackId()
	 * @method \Bitrix\Call\Integration\AI\Outcome resetTrackId()
	 * @method \Bitrix\Call\Integration\AI\Outcome unsetTrackId()
	 * @method null|\int fillTrackId()
	 * @method null|\string getType()
	 * @method \Bitrix\Call\Integration\AI\Outcome setType(null|\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method null|\string remindActualType()
	 * @method null|\string requireType()
	 * @method \Bitrix\Call\Integration\AI\Outcome resetType()
	 * @method \Bitrix\Call\Integration\AI\Outcome unsetType()
	 * @method null|\string fillType()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Call\Integration\AI\Outcome setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Call\Integration\AI\Outcome resetDateCreate()
	 * @method \Bitrix\Call\Integration\AI\Outcome unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \string getLanguageId()
	 * @method \Bitrix\Call\Integration\AI\Outcome setLanguageId(\string|\Bitrix\Main\DB\SqlExpression $languageId)
	 * @method bool hasLanguageId()
	 * @method bool isLanguageIdFilled()
	 * @method bool isLanguageIdChanged()
	 * @method \string remindActualLanguageId()
	 * @method \string requireLanguageId()
	 * @method \Bitrix\Call\Integration\AI\Outcome resetLanguageId()
	 * @method \Bitrix\Call\Integration\AI\Outcome unsetLanguageId()
	 * @method \string fillLanguageId()
	 * @method null|\string getContent()
	 * @method \Bitrix\Call\Integration\AI\Outcome setContent(null|\string|\Bitrix\Main\DB\SqlExpression $content)
	 * @method bool hasContent()
	 * @method bool isContentFilled()
	 * @method bool isContentChanged()
	 * @method null|\string remindActualContent()
	 * @method null|\string requireContent()
	 * @method \Bitrix\Call\Integration\AI\Outcome resetContent()
	 * @method \Bitrix\Call\Integration\AI\Outcome unsetContent()
	 * @method null|\string fillContent()
	 * @method \Bitrix\Call\Track getTrack()
	 * @method \Bitrix\Call\Track remindActualTrack()
	 * @method \Bitrix\Call\Track requireTrack()
	 * @method \Bitrix\Call\Integration\AI\Outcome setTrack(\Bitrix\Call\Track $object)
	 * @method \Bitrix\Call\Integration\AI\Outcome resetTrack()
	 * @method \Bitrix\Call\Integration\AI\Outcome unsetTrack()
	 * @method bool hasTrack()
	 * @method bool isTrackFilled()
	 * @method bool isTrackChanged()
	 * @method \Bitrix\Call\Track fillTrack()
	 * @method \Bitrix\Im\Model\EO_Call getCall()
	 * @method \Bitrix\Im\Model\EO_Call remindActualCall()
	 * @method \Bitrix\Im\Model\EO_Call requireCall()
	 * @method \Bitrix\Call\Integration\AI\Outcome setCall(\Bitrix\Im\Model\EO_Call $object)
	 * @method \Bitrix\Call\Integration\AI\Outcome resetCall()
	 * @method \Bitrix\Call\Integration\AI\Outcome unsetCall()
	 * @method bool hasCall()
	 * @method bool isCallFilled()
	 * @method bool isCallChanged()
	 * @method \Bitrix\Im\Model\EO_Call fillCall()
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
	 * @method \Bitrix\Call\Integration\AI\Outcome set($fieldName, $value)
	 * @method \Bitrix\Call\Integration\AI\Outcome reset($fieldName)
	 * @method \Bitrix\Call\Integration\AI\Outcome unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Call\Integration\AI\Outcome wakeUp($data)
	 */
	class EO_CallOutcome {
		/* @var \Bitrix\Call\Model\CallOutcomeTable */
		static public $dataClass = '\Bitrix\Call\Model\CallOutcomeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Call\Model {
	/**
	 * OutcomeCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCallIdList()
	 * @method \int[] fillCallId()
	 * @method null|\int[] getTrackIdList()
	 * @method null|\int[] fillTrackId()
	 * @method null|\string[] getTypeList()
	 * @method null|\string[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \string[] getLanguageIdList()
	 * @method \string[] fillLanguageId()
	 * @method null|\string[] getContentList()
	 * @method null|\string[] fillContent()
	 * @method \Bitrix\Call\Track[] getTrackList()
	 * @method \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection getTrackCollection()
	 * @method \Bitrix\Call\Track\TrackCollection fillTrack()
	 * @method \Bitrix\Im\Model\EO_Call[] getCallList()
	 * @method \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection getCallCollection()
	 * @method \Bitrix\Im\Model\EO_Call_Collection fillCall()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Call\Integration\AI\Outcome $object)
	 * @method bool has(\Bitrix\Call\Integration\AI\Outcome $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Call\Integration\AI\Outcome getByPrimary($primary)
	 * @method \Bitrix\Call\Integration\AI\Outcome[] getAll()
	 * @method bool remove(\Bitrix\Call\Integration\AI\Outcome $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Call\Integration\AI\Outcome current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection merge(?\Bitrix\Call\Integration\AI\Outcome\OutcomeCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_CallOutcome_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Call\Model\CallOutcomeTable */
		static public $dataClass = '\Bitrix\Call\Model\CallOutcomeTable';
	}
}
namespace Bitrix\Call\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CallOutcome_Result exec()
	 * @method \Bitrix\Call\Integration\AI\Outcome fetchObject()
	 * @method \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection fetchCollection()
	 */
	class EO_CallOutcome_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Call\Integration\AI\Outcome fetchObject()
	 * @method \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection fetchCollection()
	 */
	class EO_CallOutcome_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Call\Integration\AI\Outcome createObject($setDefaultValues = true)
	 * @method \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection createCollection()
	 * @method \Bitrix\Call\Integration\AI\Outcome wakeUpObject($row)
	 * @method \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection wakeUpCollection($rows)
	 */
	class EO_CallOutcome_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Call\Model\CallOutcomePropertyTable:call/lib/model/calloutcomepropertytable.php */
namespace Bitrix\Call\Model {
	/**
	 * Property
	 * @see \Bitrix\Call\Model\CallOutcomePropertyTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getOutcomeId()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property setOutcomeId(\int|\Bitrix\Main\DB\SqlExpression $outcomeId)
	 * @method bool hasOutcomeId()
	 * @method bool isOutcomeIdFilled()
	 * @method bool isOutcomeIdChanged()
	 * @method \int remindActualOutcomeId()
	 * @method \int requireOutcomeId()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property resetOutcomeId()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property unsetOutcomeId()
	 * @method \int fillOutcomeId()
	 * @method \string getCode()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property resetCode()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property unsetCode()
	 * @method \string fillCode()
	 * @method null|\string getContent()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property setContent(null|\string|\Bitrix\Main\DB\SqlExpression $content)
	 * @method bool hasContent()
	 * @method bool isContentFilled()
	 * @method bool isContentChanged()
	 * @method null|\string remindActualContent()
	 * @method null|\string requireContent()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property resetContent()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property unsetContent()
	 * @method null|\string fillContent()
	 * @method \Bitrix\Call\Integration\AI\Outcome getOutcome()
	 * @method \Bitrix\Call\Integration\AI\Outcome remindActualOutcome()
	 * @method \Bitrix\Call\Integration\AI\Outcome requireOutcome()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property setOutcome(\Bitrix\Call\Integration\AI\Outcome $object)
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property resetOutcome()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property unsetOutcome()
	 * @method bool hasOutcome()
	 * @method bool isOutcomeFilled()
	 * @method bool isOutcomeChanged()
	 * @method \Bitrix\Call\Integration\AI\Outcome fillOutcome()
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
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property set($fieldName, $value)
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property reset($fieldName)
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Call\Integration\AI\Outcome\Property wakeUp($data)
	 */
	class EO_CallOutcomeProperty {
		/* @var \Bitrix\Call\Model\CallOutcomePropertyTable */
		static public $dataClass = '\Bitrix\Call\Model\CallOutcomePropertyTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Call\Model {
	/**
	 * EO_CallOutcomeProperty_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getOutcomeIdList()
	 * @method \int[] fillOutcomeId()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method null|\string[] getContentList()
	 * @method null|\string[] fillContent()
	 * @method \Bitrix\Call\Integration\AI\Outcome[] getOutcomeList()
	 * @method \Bitrix\Call\Model\EO_CallOutcomeProperty_Collection getOutcomeCollection()
	 * @method \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection fillOutcome()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Call\Integration\AI\Outcome\Property $object)
	 * @method bool has(\Bitrix\Call\Integration\AI\Outcome\Property $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property getByPrimary($primary)
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property[] getAll()
	 * @method bool remove(\Bitrix\Call\Integration\AI\Outcome\Property $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Call\Model\EO_CallOutcomeProperty_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Call\Model\EO_CallOutcomeProperty_Collection merge(?\Bitrix\Call\Model\EO_CallOutcomeProperty_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_CallOutcomeProperty_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Call\Model\CallOutcomePropertyTable */
		static public $dataClass = '\Bitrix\Call\Model\CallOutcomePropertyTable';
	}
}
namespace Bitrix\Call\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CallOutcomeProperty_Result exec()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property fetchObject()
	 * @method \Bitrix\Call\Model\EO_CallOutcomeProperty_Collection fetchCollection()
	 */
	class EO_CallOutcomeProperty_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property fetchObject()
	 * @method \Bitrix\Call\Model\EO_CallOutcomeProperty_Collection fetchCollection()
	 */
	class EO_CallOutcomeProperty_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property createObject($setDefaultValues = true)
	 * @method \Bitrix\Call\Model\EO_CallOutcomeProperty_Collection createCollection()
	 * @method \Bitrix\Call\Integration\AI\Outcome\Property wakeUpObject($row)
	 * @method \Bitrix\Call\Model\EO_CallOutcomeProperty_Collection wakeUpCollection($rows)
	 */
	class EO_CallOutcomeProperty_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Call\Model\CallAITaskTable:call/lib/model/callaitasktable.php */
namespace Bitrix\Call\Model {
	/**
	 * EO_CallAITask
	 * @see \Bitrix\Call\Model\CallAITaskTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Call\Model\EO_CallAITask setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCallId()
	 * @method \Bitrix\Call\Model\EO_CallAITask setCallId(\int|\Bitrix\Main\DB\SqlExpression $callId)
	 * @method bool hasCallId()
	 * @method bool isCallIdFilled()
	 * @method bool isCallIdChanged()
	 * @method \int remindActualCallId()
	 * @method \int requireCallId()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetCallId()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetCallId()
	 * @method \int fillCallId()
	 * @method null|\int getTrackId()
	 * @method \Bitrix\Call\Model\EO_CallAITask setTrackId(null|\int|\Bitrix\Main\DB\SqlExpression $trackId)
	 * @method bool hasTrackId()
	 * @method bool isTrackIdFilled()
	 * @method bool isTrackIdChanged()
	 * @method null|\int remindActualTrackId()
	 * @method null|\int requireTrackId()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetTrackId()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetTrackId()
	 * @method null|\int fillTrackId()
	 * @method null|\int getOutcomeId()
	 * @method \Bitrix\Call\Model\EO_CallAITask setOutcomeId(null|\int|\Bitrix\Main\DB\SqlExpression $outcomeId)
	 * @method bool hasOutcomeId()
	 * @method bool isOutcomeIdFilled()
	 * @method bool isOutcomeIdChanged()
	 * @method null|\int remindActualOutcomeId()
	 * @method null|\int requireOutcomeId()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetOutcomeId()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetOutcomeId()
	 * @method null|\int fillOutcomeId()
	 * @method null|\string getType()
	 * @method \Bitrix\Call\Model\EO_CallAITask setType(null|\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method null|\string remindActualType()
	 * @method null|\string requireType()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetType()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetType()
	 * @method null|\string fillType()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Call\Model\EO_CallAITask setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetDateCreate()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime getDateFinished()
	 * @method \Bitrix\Call\Model\EO_CallAITask setDateFinished(null|\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateFinished)
	 * @method bool hasDateFinished()
	 * @method bool isDateFinishedFilled()
	 * @method bool isDateFinishedChanged()
	 * @method null|\Bitrix\Main\Type\DateTime remindActualDateFinished()
	 * @method null|\Bitrix\Main\Type\DateTime requireDateFinished()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetDateFinished()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetDateFinished()
	 * @method null|\Bitrix\Main\Type\DateTime fillDateFinished()
	 * @method \string getStatus()
	 * @method \Bitrix\Call\Model\EO_CallAITask setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetStatus()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetStatus()
	 * @method \string fillStatus()
	 * @method null|\string getHash()
	 * @method \Bitrix\Call\Model\EO_CallAITask setHash(null|\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method null|\string remindActualHash()
	 * @method null|\string requireHash()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetHash()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetHash()
	 * @method null|\string fillHash()
	 * @method null|\string getLanguageId()
	 * @method \Bitrix\Call\Model\EO_CallAITask setLanguageId(null|\string|\Bitrix\Main\DB\SqlExpression $languageId)
	 * @method bool hasLanguageId()
	 * @method bool isLanguageIdFilled()
	 * @method bool isLanguageIdChanged()
	 * @method null|\string remindActualLanguageId()
	 * @method null|\string requireLanguageId()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetLanguageId()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetLanguageId()
	 * @method null|\string fillLanguageId()
	 * @method null|\string getErrorCode()
	 * @method \Bitrix\Call\Model\EO_CallAITask setErrorCode(null|\string|\Bitrix\Main\DB\SqlExpression $errorCode)
	 * @method bool hasErrorCode()
	 * @method bool isErrorCodeFilled()
	 * @method bool isErrorCodeChanged()
	 * @method null|\string remindActualErrorCode()
	 * @method null|\string requireErrorCode()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetErrorCode()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetErrorCode()
	 * @method null|\string fillErrorCode()
	 * @method null|\string getErrorMessage()
	 * @method \Bitrix\Call\Model\EO_CallAITask setErrorMessage(null|\string|\Bitrix\Main\DB\SqlExpression $errorMessage)
	 * @method bool hasErrorMessage()
	 * @method bool isErrorMessageFilled()
	 * @method bool isErrorMessageChanged()
	 * @method null|\string remindActualErrorMessage()
	 * @method null|\string requireErrorMessage()
	 * @method \Bitrix\Call\Model\EO_CallAITask resetErrorMessage()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetErrorMessage()
	 * @method null|\string fillErrorMessage()
	 * @method \Bitrix\Call\Track getTrack()
	 * @method \Bitrix\Call\Track remindActualTrack()
	 * @method \Bitrix\Call\Track requireTrack()
	 * @method \Bitrix\Call\Model\EO_CallAITask setTrack(\Bitrix\Call\Track $object)
	 * @method \Bitrix\Call\Model\EO_CallAITask resetTrack()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetTrack()
	 * @method bool hasTrack()
	 * @method bool isTrackFilled()
	 * @method bool isTrackChanged()
	 * @method \Bitrix\Call\Track fillTrack()
	 * @method \Bitrix\Im\Model\EO_Call getCall()
	 * @method \Bitrix\Im\Model\EO_Call remindActualCall()
	 * @method \Bitrix\Im\Model\EO_Call requireCall()
	 * @method \Bitrix\Call\Model\EO_CallAITask setCall(\Bitrix\Im\Model\EO_Call $object)
	 * @method \Bitrix\Call\Model\EO_CallAITask resetCall()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetCall()
	 * @method bool hasCall()
	 * @method bool isCallFilled()
	 * @method bool isCallChanged()
	 * @method \Bitrix\Im\Model\EO_Call fillCall()
	 * @method \Bitrix\Call\Integration\AI\Outcome getOutcome()
	 * @method \Bitrix\Call\Integration\AI\Outcome remindActualOutcome()
	 * @method \Bitrix\Call\Integration\AI\Outcome requireOutcome()
	 * @method \Bitrix\Call\Model\EO_CallAITask setOutcome(\Bitrix\Call\Integration\AI\Outcome $object)
	 * @method \Bitrix\Call\Model\EO_CallAITask resetOutcome()
	 * @method \Bitrix\Call\Model\EO_CallAITask unsetOutcome()
	 * @method bool hasOutcome()
	 * @method bool isOutcomeFilled()
	 * @method bool isOutcomeChanged()
	 * @method \Bitrix\Call\Integration\AI\Outcome fillOutcome()
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
	 * @method \Bitrix\Call\Model\EO_CallAITask set($fieldName, $value)
	 * @method \Bitrix\Call\Model\EO_CallAITask reset($fieldName)
	 * @method \Bitrix\Call\Model\EO_CallAITask unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Call\Model\EO_CallAITask wakeUp($data)
	 */
	class EO_CallAITask {
		/* @var \Bitrix\Call\Model\CallAITaskTable */
		static public $dataClass = '\Bitrix\Call\Model\CallAITaskTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Call\Model {
	/**
	 * EO_CallAITask_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCallIdList()
	 * @method \int[] fillCallId()
	 * @method null|\int[] getTrackIdList()
	 * @method null|\int[] fillTrackId()
	 * @method null|\int[] getOutcomeIdList()
	 * @method null|\int[] fillOutcomeId()
	 * @method null|\string[] getTypeList()
	 * @method null|\string[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method null|\Bitrix\Main\Type\DateTime[] getDateFinishedList()
	 * @method null|\Bitrix\Main\Type\DateTime[] fillDateFinished()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method null|\string[] getHashList()
	 * @method null|\string[] fillHash()
	 * @method null|\string[] getLanguageIdList()
	 * @method null|\string[] fillLanguageId()
	 * @method null|\string[] getErrorCodeList()
	 * @method null|\string[] fillErrorCode()
	 * @method null|\string[] getErrorMessageList()
	 * @method null|\string[] fillErrorMessage()
	 * @method \Bitrix\Call\Track[] getTrackList()
	 * @method \Bitrix\Call\Model\EO_CallAITask_Collection getTrackCollection()
	 * @method \Bitrix\Call\Track\TrackCollection fillTrack()
	 * @method \Bitrix\Im\Model\EO_Call[] getCallList()
	 * @method \Bitrix\Call\Model\EO_CallAITask_Collection getCallCollection()
	 * @method \Bitrix\Im\Model\EO_Call_Collection fillCall()
	 * @method \Bitrix\Call\Integration\AI\Outcome[] getOutcomeList()
	 * @method \Bitrix\Call\Model\EO_CallAITask_Collection getOutcomeCollection()
	 * @method \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection fillOutcome()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Call\Model\EO_CallAITask $object)
	 * @method bool has(\Bitrix\Call\Model\EO_CallAITask $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Call\Model\EO_CallAITask getByPrimary($primary)
	 * @method \Bitrix\Call\Model\EO_CallAITask[] getAll()
	 * @method bool remove(\Bitrix\Call\Model\EO_CallAITask $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Call\Model\EO_CallAITask_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Call\Model\EO_CallAITask current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Call\Model\EO_CallAITask_Collection merge(?\Bitrix\Call\Model\EO_CallAITask_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_CallAITask_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Call\Model\CallAITaskTable */
		static public $dataClass = '\Bitrix\Call\Model\CallAITaskTable';
	}
}
namespace Bitrix\Call\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CallAITask_Result exec()
	 * @method \Bitrix\Call\Model\EO_CallAITask fetchObject()
	 * @method \Bitrix\Call\Model\EO_CallAITask_Collection fetchCollection()
	 */
	class EO_CallAITask_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Call\Model\EO_CallAITask fetchObject()
	 * @method \Bitrix\Call\Model\EO_CallAITask_Collection fetchCollection()
	 */
	class EO_CallAITask_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Call\Model\EO_CallAITask createObject($setDefaultValues = true)
	 * @method \Bitrix\Call\Model\EO_CallAITask_Collection createCollection()
	 * @method \Bitrix\Call\Model\EO_CallAITask wakeUpObject($row)
	 * @method \Bitrix\Call\Model\EO_CallAITask_Collection wakeUpCollection($rows)
	 */
	class EO_CallAITask_Entity extends \Bitrix\Main\ORM\Entity {}
}