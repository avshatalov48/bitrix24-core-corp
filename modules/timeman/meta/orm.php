<?php

/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\AbsenceTable:timeman\lib\model\absence.php:74f535f8bcd846aad809435a4696a675 */
namespace Bitrix\Timeman\Model {
	/**
	 * EO_Absence
	 * @see \Bitrix\Timeman\Model\AbsenceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\EO_Absence setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getEntryId()
	 * @method \Bitrix\Timeman\Model\EO_Absence setEntryId(\int|\Bitrix\Main\DB\SqlExpression $entryId)
	 * @method bool hasEntryId()
	 * @method bool isEntryIdFilled()
	 * @method bool isEntryIdChanged()
	 * @method \int remindActualEntryId()
	 * @method \int requireEntryId()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetEntryId()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetEntryId()
	 * @method \int fillEntryId()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\EO_Absence setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetUserId()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getType()
	 * @method \Bitrix\Timeman\Model\EO_Absence setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetType()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetType()
	 * @method \string fillType()
	 * @method \Bitrix\Main\Type\DateTime getDateStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence setDateStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStart()
	 * @method \Bitrix\Main\Type\DateTime requireDateStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetDateStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetDateStart()
	 * @method \Bitrix\Main\Type\DateTime fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime getDateFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence setDateFinish(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateFinish)
	 * @method bool hasDateFinish()
	 * @method bool isDateFinishFilled()
	 * @method bool isDateFinishChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateFinish()
	 * @method \Bitrix\Main\Type\DateTime requireDateFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetDateFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetDateFinish()
	 * @method \Bitrix\Main\Type\DateTime fillDateFinish()
	 * @method \int getTimeStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence setTimeStart(\int|\Bitrix\Main\DB\SqlExpression $timeStart)
	 * @method bool hasTimeStart()
	 * @method bool isTimeStartFilled()
	 * @method bool isTimeStartChanged()
	 * @method \int remindActualTimeStart()
	 * @method \int requireTimeStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetTimeStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetTimeStart()
	 * @method \int fillTimeStart()
	 * @method \int getTimeFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence setTimeFinish(\int|\Bitrix\Main\DB\SqlExpression $timeFinish)
	 * @method bool hasTimeFinish()
	 * @method bool isTimeFinishFilled()
	 * @method bool isTimeFinishChanged()
	 * @method \int remindActualTimeFinish()
	 * @method \int requireTimeFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetTimeFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetTimeFinish()
	 * @method \int fillTimeFinish()
	 * @method \int getDuration()
	 * @method \Bitrix\Timeman\Model\EO_Absence setDuration(\int|\Bitrix\Main\DB\SqlExpression $duration)
	 * @method bool hasDuration()
	 * @method bool isDurationFilled()
	 * @method bool isDurationChanged()
	 * @method \int remindActualDuration()
	 * @method \int requireDuration()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetDuration()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetDuration()
	 * @method \int fillDuration()
	 * @method \string getSourceStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence setSourceStart(\string|\Bitrix\Main\DB\SqlExpression $sourceStart)
	 * @method bool hasSourceStart()
	 * @method bool isSourceStartFilled()
	 * @method bool isSourceStartChanged()
	 * @method \string remindActualSourceStart()
	 * @method \string requireSourceStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetSourceStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetSourceStart()
	 * @method \string fillSourceStart()
	 * @method \string getSourceFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence setSourceFinish(\string|\Bitrix\Main\DB\SqlExpression $sourceFinish)
	 * @method bool hasSourceFinish()
	 * @method bool isSourceFinishFilled()
	 * @method bool isSourceFinishChanged()
	 * @method \string remindActualSourceFinish()
	 * @method \string requireSourceFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetSourceFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetSourceFinish()
	 * @method \string fillSourceFinish()
	 * @method \boolean getActive()
	 * @method \Bitrix\Timeman\Model\EO_Absence setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetActive()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetActive()
	 * @method \boolean fillActive()
	 * @method \int getReportType()
	 * @method \Bitrix\Timeman\Model\EO_Absence setReportType(\int|\Bitrix\Main\DB\SqlExpression $reportType)
	 * @method bool hasReportType()
	 * @method bool isReportTypeFilled()
	 * @method bool isReportTypeChanged()
	 * @method \int remindActualReportType()
	 * @method \int requireReportType()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetReportType()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetReportType()
	 * @method \int fillReportType()
	 * @method \string getReportText()
	 * @method \Bitrix\Timeman\Model\EO_Absence setReportText(\string|\Bitrix\Main\DB\SqlExpression $reportText)
	 * @method bool hasReportText()
	 * @method bool isReportTextFilled()
	 * @method bool isReportTextChanged()
	 * @method \string remindActualReportText()
	 * @method \string requireReportText()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetReportText()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetReportText()
	 * @method \string fillReportText()
	 * @method \string getSystemText()
	 * @method \Bitrix\Timeman\Model\EO_Absence setSystemText(\string|\Bitrix\Main\DB\SqlExpression $systemText)
	 * @method bool hasSystemText()
	 * @method bool isSystemTextFilled()
	 * @method bool isSystemTextChanged()
	 * @method \string remindActualSystemText()
	 * @method \string requireSystemText()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetSystemText()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetSystemText()
	 * @method \string fillSystemText()
	 * @method \string getIpStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence setIpStart(\string|\Bitrix\Main\DB\SqlExpression $ipStart)
	 * @method bool hasIpStart()
	 * @method bool isIpStartFilled()
	 * @method bool isIpStartChanged()
	 * @method \string remindActualIpStart()
	 * @method \string requireIpStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetIpStart()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetIpStart()
	 * @method \string fillIpStart()
	 * @method \string getIpFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence setIpFinish(\string|\Bitrix\Main\DB\SqlExpression $ipFinish)
	 * @method bool hasIpFinish()
	 * @method bool isIpFinishFilled()
	 * @method bool isIpFinishChanged()
	 * @method \string remindActualIpFinish()
	 * @method \string requireIpFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetIpFinish()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetIpFinish()
	 * @method \string fillIpFinish()
	 * @method \int getReportCalendarId()
	 * @method \Bitrix\Timeman\Model\EO_Absence setReportCalendarId(\int|\Bitrix\Main\DB\SqlExpression $reportCalendarId)
	 * @method bool hasReportCalendarId()
	 * @method bool isReportCalendarIdFilled()
	 * @method bool isReportCalendarIdChanged()
	 * @method \int remindActualReportCalendarId()
	 * @method \int requireReportCalendarId()
	 * @method \Bitrix\Timeman\Model\EO_Absence resetReportCalendarId()
	 * @method \Bitrix\Timeman\Model\EO_Absence unsetReportCalendarId()
	 * @method \int fillReportCalendarId()
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
	 * @method \Bitrix\Timeman\Model\EO_Absence set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\EO_Absence reset($fieldName)
	 * @method \Bitrix\Timeman\Model\EO_Absence unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\EO_Absence wakeUp($data)
	 */
	class EO_Absence {
		/* @var \Bitrix\Timeman\Model\AbsenceTable */
		static public $dataClass = '\Bitrix\Timeman\Model\AbsenceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model {
	/**
	 * EO_Absence_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getEntryIdList()
	 * @method \int[] fillEntryId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime[] getDateFinishList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateFinish()
	 * @method \int[] getTimeStartList()
	 * @method \int[] fillTimeStart()
	 * @method \int[] getTimeFinishList()
	 * @method \int[] fillTimeFinish()
	 * @method \int[] getDurationList()
	 * @method \int[] fillDuration()
	 * @method \string[] getSourceStartList()
	 * @method \string[] fillSourceStart()
	 * @method \string[] getSourceFinishList()
	 * @method \string[] fillSourceFinish()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \int[] getReportTypeList()
	 * @method \int[] fillReportType()
	 * @method \string[] getReportTextList()
	 * @method \string[] fillReportText()
	 * @method \string[] getSystemTextList()
	 * @method \string[] fillSystemText()
	 * @method \string[] getIpStartList()
	 * @method \string[] fillIpStart()
	 * @method \string[] getIpFinishList()
	 * @method \string[] fillIpFinish()
	 * @method \int[] getReportCalendarIdList()
	 * @method \int[] fillReportCalendarId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\EO_Absence $object)
	 * @method bool has(\Bitrix\Timeman\Model\EO_Absence $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\EO_Absence getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\EO_Absence[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\EO_Absence $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\EO_Absence_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\EO_Absence current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Absence_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\AbsenceTable */
		static public $dataClass = '\Bitrix\Timeman\Model\AbsenceTable';
	}
}
namespace Bitrix\Timeman\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Absence_Result exec()
	 * @method \Bitrix\Timeman\Model\EO_Absence fetchObject()
	 * @method \Bitrix\Timeman\Model\EO_Absence_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Absence_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\EO_Absence fetchObject()
	 * @method \Bitrix\Timeman\Model\EO_Absence_Collection fetchCollection()
	 */
	class EO_Absence_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\EO_Absence createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\EO_Absence_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\EO_Absence wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\EO_Absence_Collection wakeUpCollection($rows)
	 */
	class EO_Absence_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\EntriesTable:timeman\lib\model\entries.php:48e0eef0323edd844afb604136e9013f */
namespace Bitrix\Timeman\Model {
	/**
	 * EO_Entries
	 * @see \Bitrix\Timeman\Model\EntriesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\EO_Entries setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Timeman\Model\EO_Entries setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetTimestampX()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\EO_Entries setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetUserId()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getModifiedBy()
	 * @method \Bitrix\Timeman\Model\EO_Entries setModifiedBy(\int|\Bitrix\Main\DB\SqlExpression $modifiedBy)
	 * @method bool hasModifiedBy()
	 * @method bool isModifiedByFilled()
	 * @method bool isModifiedByChanged()
	 * @method \int remindActualModifiedBy()
	 * @method \int requireModifiedBy()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetModifiedBy()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetModifiedBy()
	 * @method \int fillModifiedBy()
	 * @method \boolean getActive()
	 * @method \Bitrix\Timeman\Model\EO_Entries setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetActive()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetActive()
	 * @method \boolean fillActive()
	 * @method \boolean getPaused()
	 * @method \Bitrix\Timeman\Model\EO_Entries setPaused(\boolean|\Bitrix\Main\DB\SqlExpression $paused)
	 * @method bool hasPaused()
	 * @method bool isPausedFilled()
	 * @method bool isPausedChanged()
	 * @method \boolean remindActualPaused()
	 * @method \boolean requirePaused()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetPaused()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetPaused()
	 * @method \boolean fillPaused()
	 * @method \Bitrix\Main\Type\DateTime getDateStart()
	 * @method \Bitrix\Timeman\Model\EO_Entries setDateStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStart()
	 * @method \Bitrix\Main\Type\DateTime requireDateStart()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetDateStart()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetDateStart()
	 * @method \Bitrix\Main\Type\DateTime fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime getDateFinish()
	 * @method \Bitrix\Timeman\Model\EO_Entries setDateFinish(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateFinish)
	 * @method bool hasDateFinish()
	 * @method bool isDateFinishFilled()
	 * @method bool isDateFinishChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateFinish()
	 * @method \Bitrix\Main\Type\DateTime requireDateFinish()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetDateFinish()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetDateFinish()
	 * @method \Bitrix\Main\Type\DateTime fillDateFinish()
	 * @method \int getTimeStart()
	 * @method \Bitrix\Timeman\Model\EO_Entries setTimeStart(\int|\Bitrix\Main\DB\SqlExpression $timeStart)
	 * @method bool hasTimeStart()
	 * @method bool isTimeStartFilled()
	 * @method bool isTimeStartChanged()
	 * @method \int remindActualTimeStart()
	 * @method \int requireTimeStart()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetTimeStart()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetTimeStart()
	 * @method \int fillTimeStart()
	 * @method \int getTimeFinish()
	 * @method \Bitrix\Timeman\Model\EO_Entries setTimeFinish(\int|\Bitrix\Main\DB\SqlExpression $timeFinish)
	 * @method bool hasTimeFinish()
	 * @method bool isTimeFinishFilled()
	 * @method bool isTimeFinishChanged()
	 * @method \int remindActualTimeFinish()
	 * @method \int requireTimeFinish()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetTimeFinish()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetTimeFinish()
	 * @method \int fillTimeFinish()
	 * @method \int getDuration()
	 * @method \Bitrix\Timeman\Model\EO_Entries setDuration(\int|\Bitrix\Main\DB\SqlExpression $duration)
	 * @method bool hasDuration()
	 * @method bool isDurationFilled()
	 * @method bool isDurationChanged()
	 * @method \int remindActualDuration()
	 * @method \int requireDuration()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetDuration()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetDuration()
	 * @method \int fillDuration()
	 * @method \int getTimeLeaks()
	 * @method \Bitrix\Timeman\Model\EO_Entries setTimeLeaks(\int|\Bitrix\Main\DB\SqlExpression $timeLeaks)
	 * @method bool hasTimeLeaks()
	 * @method bool isTimeLeaksFilled()
	 * @method bool isTimeLeaksChanged()
	 * @method \int remindActualTimeLeaks()
	 * @method \int requireTimeLeaks()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetTimeLeaks()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetTimeLeaks()
	 * @method \int fillTimeLeaks()
	 * @method \string getTasks()
	 * @method \Bitrix\Timeman\Model\EO_Entries setTasks(\string|\Bitrix\Main\DB\SqlExpression $tasks)
	 * @method bool hasTasks()
	 * @method bool isTasksFilled()
	 * @method bool isTasksChanged()
	 * @method \string remindActualTasks()
	 * @method \string requireTasks()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetTasks()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetTasks()
	 * @method \string fillTasks()
	 * @method \string getIpOpen()
	 * @method \Bitrix\Timeman\Model\EO_Entries setIpOpen(\string|\Bitrix\Main\DB\SqlExpression $ipOpen)
	 * @method bool hasIpOpen()
	 * @method bool isIpOpenFilled()
	 * @method bool isIpOpenChanged()
	 * @method \string remindActualIpOpen()
	 * @method \string requireIpOpen()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetIpOpen()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetIpOpen()
	 * @method \string fillIpOpen()
	 * @method \string getIpClose()
	 * @method \Bitrix\Timeman\Model\EO_Entries setIpClose(\string|\Bitrix\Main\DB\SqlExpression $ipClose)
	 * @method bool hasIpClose()
	 * @method bool isIpCloseFilled()
	 * @method bool isIpCloseChanged()
	 * @method \string remindActualIpClose()
	 * @method \string requireIpClose()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetIpClose()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetIpClose()
	 * @method \string fillIpClose()
	 * @method \int getForumTopicId()
	 * @method \Bitrix\Timeman\Model\EO_Entries setForumTopicId(\int|\Bitrix\Main\DB\SqlExpression $forumTopicId)
	 * @method bool hasForumTopicId()
	 * @method bool isForumTopicIdFilled()
	 * @method bool isForumTopicIdChanged()
	 * @method \int remindActualForumTopicId()
	 * @method \int requireForumTopicId()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetForumTopicId()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetForumTopicId()
	 * @method \int fillForumTopicId()
	 * @method \float getLatOpen()
	 * @method \Bitrix\Timeman\Model\EO_Entries setLatOpen(\float|\Bitrix\Main\DB\SqlExpression $latOpen)
	 * @method bool hasLatOpen()
	 * @method bool isLatOpenFilled()
	 * @method bool isLatOpenChanged()
	 * @method \float remindActualLatOpen()
	 * @method \float requireLatOpen()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetLatOpen()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetLatOpen()
	 * @method \float fillLatOpen()
	 * @method \float getLonOpen()
	 * @method \Bitrix\Timeman\Model\EO_Entries setLonOpen(\float|\Bitrix\Main\DB\SqlExpression $lonOpen)
	 * @method bool hasLonOpen()
	 * @method bool isLonOpenFilled()
	 * @method bool isLonOpenChanged()
	 * @method \float remindActualLonOpen()
	 * @method \float requireLonOpen()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetLonOpen()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetLonOpen()
	 * @method \float fillLonOpen()
	 * @method \float getLatClose()
	 * @method \Bitrix\Timeman\Model\EO_Entries setLatClose(\float|\Bitrix\Main\DB\SqlExpression $latClose)
	 * @method bool hasLatClose()
	 * @method bool isLatCloseFilled()
	 * @method bool isLatCloseChanged()
	 * @method \float remindActualLatClose()
	 * @method \float requireLatClose()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetLatClose()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetLatClose()
	 * @method \float fillLatClose()
	 * @method \float getLonClose()
	 * @method \Bitrix\Timeman\Model\EO_Entries setLonClose(\float|\Bitrix\Main\DB\SqlExpression $lonClose)
	 * @method bool hasLonClose()
	 * @method bool isLonCloseFilled()
	 * @method bool isLonCloseChanged()
	 * @method \float remindActualLonClose()
	 * @method \float requireLonClose()
	 * @method \Bitrix\Timeman\Model\EO_Entries resetLonClose()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetLonClose()
	 * @method \float fillLonClose()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Timeman\Model\EO_Entries setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Timeman\Model\EO_Entries resetUser()
	 * @method \Bitrix\Timeman\Model\EO_Entries unsetUser()
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
	 * @method \Bitrix\Timeman\Model\EO_Entries set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\EO_Entries reset($fieldName)
	 * @method \Bitrix\Timeman\Model\EO_Entries unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\EO_Entries wakeUp($data)
	 */
	class EO_Entries {
		/* @var \Bitrix\Timeman\Model\EntriesTable */
		static public $dataClass = '\Bitrix\Timeman\Model\EntriesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model {
	/**
	 * EO_Entries_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getModifiedByList()
	 * @method \int[] fillModifiedBy()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \boolean[] getPausedList()
	 * @method \boolean[] fillPaused()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime[] getDateFinishList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateFinish()
	 * @method \int[] getTimeStartList()
	 * @method \int[] fillTimeStart()
	 * @method \int[] getTimeFinishList()
	 * @method \int[] fillTimeFinish()
	 * @method \int[] getDurationList()
	 * @method \int[] fillDuration()
	 * @method \int[] getTimeLeaksList()
	 * @method \int[] fillTimeLeaks()
	 * @method \string[] getTasksList()
	 * @method \string[] fillTasks()
	 * @method \string[] getIpOpenList()
	 * @method \string[] fillIpOpen()
	 * @method \string[] getIpCloseList()
	 * @method \string[] fillIpClose()
	 * @method \int[] getForumTopicIdList()
	 * @method \int[] fillForumTopicId()
	 * @method \float[] getLatOpenList()
	 * @method \float[] fillLatOpen()
	 * @method \float[] getLonOpenList()
	 * @method \float[] fillLonOpen()
	 * @method \float[] getLatCloseList()
	 * @method \float[] fillLatClose()
	 * @method \float[] getLonCloseList()
	 * @method \float[] fillLonClose()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Timeman\Model\EO_Entries_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\EO_Entries $object)
	 * @method bool has(\Bitrix\Timeman\Model\EO_Entries $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\EO_Entries getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\EO_Entries[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\EO_Entries $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\EO_Entries_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\EO_Entries current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Entries_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\EntriesTable */
		static public $dataClass = '\Bitrix\Timeman\Model\EntriesTable';
	}
}
namespace Bitrix\Timeman\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Entries_Result exec()
	 * @method \Bitrix\Timeman\Model\EO_Entries fetchObject()
	 * @method \Bitrix\Timeman\Model\EO_Entries_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Entries_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\EO_Entries fetchObject()
	 * @method \Bitrix\Timeman\Model\EO_Entries_Collection fetchCollection()
	 */
	class EO_Entries_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\EO_Entries createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\EO_Entries_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\EO_Entries wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\EO_Entries_Collection wakeUpCollection($rows)
	 */
	class EO_Entries_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Monitor\MonitorAbsenceTable:timeman\lib\model\monitor\monitorabsencetable.php:e026dbf97d02a9e8bfea38a53cacfae1 */
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorAbsence
	 * @see \Bitrix\Timeman\Model\Monitor\MonitorAbsenceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserLogId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence setUserLogId(\int|\Bitrix\Main\DB\SqlExpression $userLogId)
	 * @method bool hasUserLogId()
	 * @method bool isUserLogIdFilled()
	 * @method bool isUserLogIdChanged()
	 * @method \int remindActualUserLogId()
	 * @method \int requireUserLogId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence resetUserLogId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence unsetUserLogId()
	 * @method \int fillUserLogId()
	 * @method \Bitrix\Main\Type\DateTime getTimeStart()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence setTimeStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timeStart)
	 * @method bool hasTimeStart()
	 * @method bool isTimeStartFilled()
	 * @method bool isTimeStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimeStart()
	 * @method \Bitrix\Main\Type\DateTime requireTimeStart()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence resetTimeStart()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence unsetTimeStart()
	 * @method \Bitrix\Main\Type\DateTime fillTimeStart()
	 * @method \Bitrix\Main\Type\DateTime getTimeFinish()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence setTimeFinish(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timeFinish)
	 * @method bool hasTimeFinish()
	 * @method bool isTimeFinishFilled()
	 * @method bool isTimeFinishChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimeFinish()
	 * @method \Bitrix\Main\Type\DateTime requireTimeFinish()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence resetTimeFinish()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence unsetTimeFinish()
	 * @method \Bitrix\Main\Type\DateTime fillTimeFinish()
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
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence wakeUp($data)
	 */
	class EO_MonitorAbsence {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorAbsenceTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorAbsenceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorAbsence_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserLogIdList()
	 * @method \int[] fillUserLogId()
	 * @method \Bitrix\Main\Type\DateTime[] getTimeStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimeStart()
	 * @method \Bitrix\Main\Type\DateTime[] getTimeFinishList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimeFinish()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence $object)
	 * @method bool has(\Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_MonitorAbsence_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorAbsenceTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorAbsenceTable';
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_MonitorAbsence_Result exec()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_MonitorAbsence_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence_Collection fetchCollection()
	 */
	class EO_MonitorAbsence_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorAbsence_Collection wakeUpCollection($rows)
	 */
	class EO_MonitorAbsence_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Monitor\MonitorCommentTable:timeman\lib\model\monitor\monitorcommenttable.php:f0ff3f6ae2e4fca4bf70057da0bd336a */
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorComment
	 * @see \Bitrix\Timeman\Model\Monitor\MonitorCommentTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserLogId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment setUserLogId(\int|\Bitrix\Main\DB\SqlExpression $userLogId)
	 * @method bool hasUserLogId()
	 * @method bool isUserLogIdFilled()
	 * @method bool isUserLogIdChanged()
	 * @method \int remindActualUserLogId()
	 * @method \int requireUserLogId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment resetUserLogId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment unsetUserLogId()
	 * @method \int fillUserLogId()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment resetUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getComment()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment setComment(\string|\Bitrix\Main\DB\SqlExpression $comment)
	 * @method bool hasComment()
	 * @method bool isCommentFilled()
	 * @method bool isCommentChanged()
	 * @method \string remindActualComment()
	 * @method \string requireComment()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment resetComment()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment unsetComment()
	 * @method \string fillComment()
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
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorComment wakeUp($data)
	 */
	class EO_MonitorComment {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorCommentTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorCommentTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorComment_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserLogIdList()
	 * @method \int[] fillUserLogId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getCommentList()
	 * @method \string[] fillComment()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Monitor\EO_MonitorComment $object)
	 * @method bool has(\Bitrix\Timeman\Model\Monitor\EO_MonitorComment $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Monitor\EO_MonitorComment $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorComment_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_MonitorComment_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorCommentTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorCommentTable';
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_MonitorComment_Result exec()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_MonitorComment_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment_Collection fetchCollection()
	 */
	class EO_MonitorComment_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorComment_Collection wakeUpCollection($rows)
	 */
	class EO_MonitorComment_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Monitor\MonitorEntityTable:timeman\lib\model\monitor\monitorentitytable.php:ef7c7861a3cca14bf4c6496fd19bfbab */
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorEntity
	 * @see \Bitrix\Timeman\Model\Monitor\MonitorEntityTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getType()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity resetType()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity unsetType()
	 * @method \string fillType()
	 * @method \string getTitle()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity resetTitle()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getPublicCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity setPublicCode(\string|\Bitrix\Main\DB\SqlExpression $publicCode)
	 * @method bool hasPublicCode()
	 * @method bool isPublicCodeFilled()
	 * @method bool isPublicCodeChanged()
	 * @method \string remindActualPublicCode()
	 * @method \string requirePublicCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity resetPublicCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity unsetPublicCode()
	 * @method \string fillPublicCode()
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
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity wakeUp($data)
	 */
	class EO_MonitorEntity {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorEntityTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorEntityTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorEntity_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getPublicCodeList()
	 * @method \string[] fillPublicCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Monitor\EO_MonitorEntity $object)
	 * @method bool has(\Bitrix\Timeman\Model\Monitor\EO_MonitorEntity $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Monitor\EO_MonitorEntity $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_MonitorEntity_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorEntityTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorEntityTable';
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_MonitorEntity_Result exec()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_MonitorEntity_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity_Collection fetchCollection()
	 */
	class EO_MonitorEntity_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity_Collection wakeUpCollection($rows)
	 */
	class EO_MonitorEntity_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Monitor\MonitorReportCommentTable:timeman\lib\model\monitor\monitorreportcommenttable.php:0666c0e4f347b7b5ab16cab188b1bb48 */
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorReportComment
	 * @see \Bitrix\Timeman\Model\Monitor\MonitorReportCommentTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\Date getDateLog()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment setDateLog(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateLog)
	 * @method bool hasDateLog()
	 * @method bool isDateLogFilled()
	 * @method bool isDateLogChanged()
	 * @method \Bitrix\Main\Type\Date remindActualDateLog()
	 * @method \Bitrix\Main\Type\Date requireDateLog()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment resetDateLog()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment unsetDateLog()
	 * @method \Bitrix\Main\Type\Date fillDateLog()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment resetUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getDesktopCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment setDesktopCode(\string|\Bitrix\Main\DB\SqlExpression $desktopCode)
	 * @method bool hasDesktopCode()
	 * @method bool isDesktopCodeFilled()
	 * @method bool isDesktopCodeChanged()
	 * @method \string remindActualDesktopCode()
	 * @method \string requireDesktopCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment resetDesktopCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment unsetDesktopCode()
	 * @method \string fillDesktopCode()
	 * @method \string getComment()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment setComment(\string|\Bitrix\Main\DB\SqlExpression $comment)
	 * @method bool hasComment()
	 * @method bool isCommentFilled()
	 * @method bool isCommentChanged()
	 * @method \string remindActualComment()
	 * @method \string requireComment()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment resetComment()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment unsetComment()
	 * @method \string fillComment()
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
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment wakeUp($data)
	 */
	class EO_MonitorReportComment {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorReportCommentTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorReportCommentTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorReportComment_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\Date[] getDateLogList()
	 * @method \Bitrix\Main\Type\Date[] fillDateLog()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getDesktopCodeList()
	 * @method \string[] fillDesktopCode()
	 * @method \string[] getCommentList()
	 * @method \string[] fillComment()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment $object)
	 * @method bool has(\Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_MonitorReportComment_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorReportCommentTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorReportCommentTable';
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_MonitorReportComment_Result exec()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_MonitorReportComment_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment_Collection fetchCollection()
	 */
	class EO_MonitorReportComment_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment_Collection wakeUpCollection($rows)
	 */
	class EO_MonitorReportComment_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Monitor\MonitorUserChartTable:timeman\lib\model\monitor\monitorusercharttable.php:b03c9cc95748f7e3e9313656bd4e0fa6 */
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorUserChart
	 * @see \Bitrix\Timeman\Model\Monitor\MonitorUserChartTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\Date getDateLog()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart setDateLog(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateLog)
	 * @method bool hasDateLog()
	 * @method bool isDateLogFilled()
	 * @method bool isDateLogChanged()
	 * @method \Bitrix\Main\Type\Date remindActualDateLog()
	 * @method \Bitrix\Main\Type\Date requireDateLog()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart resetDateLog()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart unsetDateLog()
	 * @method \Bitrix\Main\Type\Date fillDateLog()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart resetUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getDesktopCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart setDesktopCode(\string|\Bitrix\Main\DB\SqlExpression $desktopCode)
	 * @method bool hasDesktopCode()
	 * @method bool isDesktopCodeFilled()
	 * @method bool isDesktopCodeChanged()
	 * @method \string remindActualDesktopCode()
	 * @method \string requireDesktopCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart resetDesktopCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart unsetDesktopCode()
	 * @method \string fillDesktopCode()
	 * @method \string getGroupType()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart setGroupType(\string|\Bitrix\Main\DB\SqlExpression $groupType)
	 * @method bool hasGroupType()
	 * @method bool isGroupTypeFilled()
	 * @method bool isGroupTypeChanged()
	 * @method \string remindActualGroupType()
	 * @method \string requireGroupType()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart resetGroupType()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart unsetGroupType()
	 * @method \string fillGroupType()
	 * @method \Bitrix\Main\Type\DateTime getTimeStart()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart setTimeStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timeStart)
	 * @method bool hasTimeStart()
	 * @method bool isTimeStartFilled()
	 * @method bool isTimeStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimeStart()
	 * @method \Bitrix\Main\Type\DateTime requireTimeStart()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart resetTimeStart()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart unsetTimeStart()
	 * @method \Bitrix\Main\Type\DateTime fillTimeStart()
	 * @method \Bitrix\Main\Type\DateTime getTimeFinish()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart setTimeFinish(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timeFinish)
	 * @method bool hasTimeFinish()
	 * @method bool isTimeFinishFilled()
	 * @method bool isTimeFinishChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimeFinish()
	 * @method \Bitrix\Main\Type\DateTime requireTimeFinish()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart resetTimeFinish()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart unsetTimeFinish()
	 * @method \Bitrix\Main\Type\DateTime fillTimeFinish()
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
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart wakeUp($data)
	 */
	class EO_MonitorUserChart {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorUserChartTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorUserChartTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorUserChart_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\Date[] getDateLogList()
	 * @method \Bitrix\Main\Type\Date[] fillDateLog()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getDesktopCodeList()
	 * @method \string[] fillDesktopCode()
	 * @method \string[] getGroupTypeList()
	 * @method \string[] fillGroupType()
	 * @method \Bitrix\Main\Type\DateTime[] getTimeStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimeStart()
	 * @method \Bitrix\Main\Type\DateTime[] getTimeFinishList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimeFinish()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart $object)
	 * @method bool has(\Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_MonitorUserChart_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorUserChartTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorUserChartTable';
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_MonitorUserChart_Result exec()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_MonitorUserChart_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart_Collection fetchCollection()
	 */
	class EO_MonitorUserChart_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart_Collection wakeUpCollection($rows)
	 */
	class EO_MonitorUserChart_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Monitor\MonitorUserLogTable:timeman\lib\model\monitor\monitoruserlogtable.php:ec95826efdb72b7e661f06c33796ec13 */
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorUserLog
	 * @see \Bitrix\Timeman\Model\Monitor\MonitorUserLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\Date getDateLog()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog setDateLog(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateLog)
	 * @method bool hasDateLog()
	 * @method bool isDateLogFilled()
	 * @method bool isDateLogChanged()
	 * @method \Bitrix\Main\Type\Date remindActualDateLog()
	 * @method \Bitrix\Main\Type\Date requireDateLog()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog resetDateLog()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog unsetDateLog()
	 * @method \Bitrix\Main\Type\Date fillDateLog()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog resetUserId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getPrivateCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog setPrivateCode(\string|\Bitrix\Main\DB\SqlExpression $privateCode)
	 * @method bool hasPrivateCode()
	 * @method bool isPrivateCodeFilled()
	 * @method bool isPrivateCodeChanged()
	 * @method \string remindActualPrivateCode()
	 * @method \string requirePrivateCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog resetPrivateCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog unsetPrivateCode()
	 * @method \string fillPrivateCode()
	 * @method \int getEntityId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog resetEntityId()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \int getTimeSpend()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog setTimeSpend(\int|\Bitrix\Main\DB\SqlExpression $timeSpend)
	 * @method bool hasTimeSpend()
	 * @method bool isTimeSpendFilled()
	 * @method bool isTimeSpendChanged()
	 * @method \int remindActualTimeSpend()
	 * @method \int requireTimeSpend()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog resetTimeSpend()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog unsetTimeSpend()
	 * @method \int fillTimeSpend()
	 * @method \string getDesktopCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog setDesktopCode(\string|\Bitrix\Main\DB\SqlExpression $desktopCode)
	 * @method bool hasDesktopCode()
	 * @method bool isDesktopCodeFilled()
	 * @method bool isDesktopCodeChanged()
	 * @method \string remindActualDesktopCode()
	 * @method \string requireDesktopCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog resetDesktopCode()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog unsetDesktopCode()
	 * @method \string fillDesktopCode()
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
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog wakeUp($data)
	 */
	class EO_MonitorUserLog {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorUserLogTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorUserLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * EO_MonitorUserLog_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\Date[] getDateLogList()
	 * @method \Bitrix\Main\Type\Date[] fillDateLog()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getPrivateCodeList()
	 * @method \string[] fillPrivateCode()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \int[] getTimeSpendList()
	 * @method \int[] fillTimeSpend()
	 * @method \string[] getDesktopCodeList()
	 * @method \string[] fillDesktopCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog $object)
	 * @method bool has(\Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_MonitorUserLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Monitor\MonitorUserLogTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Monitor\MonitorUserLogTable';
	}
}
namespace Bitrix\Timeman\Model\Monitor {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_MonitorUserLog_Result exec()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_MonitorUserLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog fetchObject()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog_Collection fetchCollection()
	 */
	class EO_MonitorUserLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog_Collection wakeUpCollection($rows)
	 */
	class EO_MonitorUserLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable:timeman\lib\model\schedule\assignment\department\scheduledepartmenttable.php:370963363e9ad7a615590f6396c8cdd3 */
namespace Bitrix\Timeman\Model\Schedule\Assignment\Department {
	/**
	 * ScheduleDepartment
	 * @see \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getScheduleId()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment setScheduleId(\int|\Bitrix\Main\DB\SqlExpression $scheduleId)
	 * @method bool hasScheduleId()
	 * @method bool isScheduleIdFilled()
	 * @method bool isScheduleIdChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule getSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule remindActualSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule requireSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment setSchedule(\Bitrix\Timeman\Model\Schedule\Schedule $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment resetSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment unsetSchedule()
	 * @method bool hasSchedule()
	 * @method bool isScheduleFilled()
	 * @method bool isScheduleChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule fillSchedule()
	 * @method \int getDepartmentId()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment setDepartmentId(\int|\Bitrix\Main\DB\SqlExpression $departmentId)
	 * @method bool hasDepartmentId()
	 * @method bool isDepartmentIdFilled()
	 * @method bool isDepartmentIdChanged()
	 * @method \Bitrix\Iblock\EO_Section getDepartment()
	 * @method \Bitrix\Iblock\EO_Section remindActualDepartment()
	 * @method \Bitrix\Iblock\EO_Section requireDepartment()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment setDepartment(\Bitrix\Iblock\EO_Section $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment resetDepartment()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment unsetDepartment()
	 * @method bool hasDepartment()
	 * @method bool isDepartmentFilled()
	 * @method bool isDepartmentChanged()
	 * @method \Bitrix\Iblock\EO_Section fillDepartment()
	 * @method \int getStatus()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment resetStatus()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment unsetStatus()
	 * @method \int fillStatus()
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
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment wakeUp($data)
	 */
	class EO_ScheduleDepartment {
		/* @var \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Schedule\Assignment\Department {
	/**
	 * EO_ScheduleDepartment_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getScheduleIdList()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule[] getScheduleList()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection getScheduleCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection fillSchedule()
	 * @method \int[] getDepartmentIdList()
	 * @method \Bitrix\Iblock\EO_Section[] getDepartmentList()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection getDepartmentCollection()
	 * @method \Bitrix\Iblock\EO_Section_Collection fillDepartment()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment $object)
	 * @method bool has(\Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ScheduleDepartment_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable';
	}
}
namespace Bitrix\Timeman\Model\Schedule\Assignment\Department {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ScheduleDepartment_Result exec()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ScheduleDepartment_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection fetchCollection()
	 */
	class EO_ScheduleDepartment_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection wakeUpCollection($rows)
	 */
	class EO_ScheduleDepartment_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable:timeman\lib\model\schedule\assignment\user\scheduleusertable.php:d06afbb05b4e26b464feeafa17bb967a */
namespace Bitrix\Timeman\Model\Schedule\Assignment\User {
	/**
	 * ScheduleUser
	 * @see \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getScheduleId()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser setScheduleId(\int|\Bitrix\Main\DB\SqlExpression $scheduleId)
	 * @method bool hasScheduleId()
	 * @method bool isScheduleIdFilled()
	 * @method bool isScheduleIdChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule getSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule remindActualSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule requireSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser setSchedule(\Bitrix\Timeman\Model\Schedule\Schedule $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser resetSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser unsetSchedule()
	 * @method bool hasSchedule()
	 * @method bool isScheduleFilled()
	 * @method bool isScheduleChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule fillSchedule()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser resetUser()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \int getStatus()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser resetStatus()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser unsetStatus()
	 * @method \int fillStatus()
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
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser wakeUp($data)
	 */
	class EO_ScheduleUser {
		/* @var \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Schedule\Assignment\User {
	/**
	 * EO_ScheduleUser_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getScheduleIdList()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule[] getScheduleList()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection getScheduleCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection fillSchedule()
	 * @method \int[] getUserIdList()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser $object)
	 * @method bool has(\Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ScheduleUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable';
	}
}
namespace Bitrix\Timeman\Model\Schedule\Assignment\User {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ScheduleUser_Result exec()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ScheduleUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection fetchCollection()
	 */
	class EO_ScheduleUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection wakeUpCollection($rows)
	 */
	class EO_ScheduleUser_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable:timeman\lib\model\schedule\calendar\calendartable.php:474aefa893118b2751b96ec2b0d217f7 */
namespace Bitrix\Timeman\Model\Schedule\Calendar {
	/**
	 * Calendar
	 * @see \Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar resetName()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar unsetName()
	 * @method \string fillName()
	 * @method \int getParentCalendarId()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar setParentCalendarId(\int|\Bitrix\Main\DB\SqlExpression $parentCalendarId)
	 * @method bool hasParentCalendarId()
	 * @method bool isParentCalendarIdFilled()
	 * @method bool isParentCalendarIdChanged()
	 * @method \int remindActualParentCalendarId()
	 * @method \int requireParentCalendarId()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar resetParentCalendarId()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar unsetParentCalendarId()
	 * @method \int fillParentCalendarId()
	 * @method \string getSystemCode()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar setSystemCode(\string|\Bitrix\Main\DB\SqlExpression $systemCode)
	 * @method bool hasSystemCode()
	 * @method bool isSystemCodeFilled()
	 * @method bool isSystemCodeChanged()
	 * @method \string remindActualSystemCode()
	 * @method \string requireSystemCode()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar resetSystemCode()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar unsetSystemCode()
	 * @method \string fillSystemCode()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection getExclusions()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection requireExclusions()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection fillExclusions()
	 * @method bool hasExclusions()
	 * @method bool isExclusionsFilled()
	 * @method bool isExclusionsChanged()
	 * @method void addToExclusions(\Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion $calendarExclusion)
	 * @method void removeFromExclusions(\Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion $calendarExclusion)
	 * @method void removeAllExclusions()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar resetExclusions()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar unsetExclusions()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar getParentCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar remindActualParentCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar requireParentCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar setParentCalendar(\Bitrix\Timeman\Model\Schedule\Calendar\Calendar $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar resetParentCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar unsetParentCalendar()
	 * @method bool hasParentCalendar()
	 * @method bool isParentCalendarFilled()
	 * @method bool isParentCalendarChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar fillParentCalendar()
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
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\Calendar wakeUp($data)
	 */
	class EO_Calendar {
		/* @var \Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Schedule\Calendar {
	/**
	 * EO_Calendar_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getParentCalendarIdList()
	 * @method \int[] fillParentCalendarId()
	 * @method \string[] getSystemCodeList()
	 * @method \string[] fillSystemCode()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection[] getExclusionsList()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection getExclusionsCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection fillExclusions()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar[] getParentCalendarList()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection getParentCalendarCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection fillParentCalendar()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Schedule\Calendar\Calendar $object)
	 * @method bool has(\Bitrix\Timeman\Model\Schedule\Calendar\Calendar $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Schedule\Calendar\Calendar $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Calendar_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable';
	}
}
namespace Bitrix\Timeman\Model\Schedule\Calendar {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Calendar_Result exec()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Calendar_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection fetchCollection()
	 */
	class EO_Calendar_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection wakeUpCollection($rows)
	 */
	class EO_Calendar_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusionTable:timeman\lib\model\schedule\calendar\calendarexclusiontable.php:1e7d5a19e8095dc3629267935b15400f */
namespace Bitrix\Timeman\Model\Schedule\Calendar {
	/**
	 * CalendarExclusion
	 * @see \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getCalendarId()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion setCalendarId(\int|\Bitrix\Main\DB\SqlExpression $calendarId)
	 * @method bool hasCalendarId()
	 * @method bool isCalendarIdFilled()
	 * @method bool isCalendarIdChanged()
	 * @method \int getYear()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion setYear(\int|\Bitrix\Main\DB\SqlExpression $year)
	 * @method bool hasYear()
	 * @method bool isYearFilled()
	 * @method bool isYearChanged()
	 * @method array getDates()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion setDates(array|\Bitrix\Main\DB\SqlExpression $dates)
	 * @method bool hasDates()
	 * @method bool isDatesFilled()
	 * @method bool isDatesChanged()
	 * @method array remindActualDates()
	 * @method array requireDates()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion resetDates()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion unsetDates()
	 * @method array fillDates()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar getCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar remindActualCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar requireCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion setCalendar(\Bitrix\Timeman\Model\Schedule\Calendar\Calendar $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion resetCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion unsetCalendar()
	 * @method bool hasCalendar()
	 * @method bool isCalendarFilled()
	 * @method bool isCalendarChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar fillCalendar()
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
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion wakeUp($data)
	 */
	class EO_CalendarExclusion {
		/* @var \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusionTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Schedule\Calendar {
	/**
	 * EO_CalendarExclusion_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getCalendarIdList()
	 * @method \int[] getYearList()
	 * @method array[] getDatesList()
	 * @method array[] fillDates()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar[] getCalendarList()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection getCalendarCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection fillCalendar()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion $object)
	 * @method bool has(\Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_CalendarExclusion_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusionTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusionTable';
	}
}
namespace Bitrix\Timeman\Model\Schedule\Calendar {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CalendarExclusion_Result exec()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CalendarExclusion_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection fetchCollection()
	 */
	class EO_CalendarExclusion_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection wakeUpCollection($rows)
	 */
	class EO_CalendarExclusion_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Schedule\ScheduleTable:timeman\lib\model\schedule\scheduletable.php:f89b09cf29b7bc8e823d0a1abcbe8ed4 */
namespace Bitrix\Timeman\Model\Schedule {
	/**
	 * Schedule
	 * @see \Bitrix\Timeman\Model\Schedule\ScheduleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetName()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetName()
	 * @method \string fillName()
	 * @method \string getScheduleType()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setScheduleType(\string|\Bitrix\Main\DB\SqlExpression $scheduleType)
	 * @method bool hasScheduleType()
	 * @method bool isScheduleTypeFilled()
	 * @method bool isScheduleTypeChanged()
	 * @method \string remindActualScheduleType()
	 * @method \string requireScheduleType()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetScheduleType()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetScheduleType()
	 * @method \string fillScheduleType()
	 * @method \string getReportPeriod()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setReportPeriod(\string|\Bitrix\Main\DB\SqlExpression $reportPeriod)
	 * @method bool hasReportPeriod()
	 * @method bool isReportPeriodFilled()
	 * @method bool isReportPeriodChanged()
	 * @method \string remindActualReportPeriod()
	 * @method \string requireReportPeriod()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetReportPeriod()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetReportPeriod()
	 * @method \string fillReportPeriod()
	 * @method array getReportPeriodOptions()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setReportPeriodOptions(array|\Bitrix\Main\DB\SqlExpression $reportPeriodOptions)
	 * @method bool hasReportPeriodOptions()
	 * @method bool isReportPeriodOptionsFilled()
	 * @method bool isReportPeriodOptionsChanged()
	 * @method array remindActualReportPeriodOptions()
	 * @method array requireReportPeriodOptions()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetReportPeriodOptions()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetReportPeriodOptions()
	 * @method array fillReportPeriodOptions()
	 * @method \int getCalendarId()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setCalendarId(\int|\Bitrix\Main\DB\SqlExpression $calendarId)
	 * @method bool hasCalendarId()
	 * @method bool isCalendarIdFilled()
	 * @method bool isCalendarIdChanged()
	 * @method \int remindActualCalendarId()
	 * @method \int requireCalendarId()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetCalendarId()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetCalendarId()
	 * @method \int fillCalendarId()
	 * @method array getAllowedDevices()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setAllowedDevices(array|\Bitrix\Main\DB\SqlExpression $allowedDevices)
	 * @method bool hasAllowedDevices()
	 * @method bool isAllowedDevicesFilled()
	 * @method bool isAllowedDevicesChanged()
	 * @method array remindActualAllowedDevices()
	 * @method array requireAllowedDevices()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetAllowedDevices()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetAllowedDevices()
	 * @method array fillAllowedDevices()
	 * @method \string getDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setDeleted(\string|\Bitrix\Main\DB\SqlExpression $deleted)
	 * @method bool hasDeleted()
	 * @method bool isDeletedFilled()
	 * @method bool isDeletedChanged()
	 * @method \string remindActualDeleted()
	 * @method \string requireDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetDeleted()
	 * @method \string fillDeleted()
	 * @method \boolean getIsForAllUsers()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setIsForAllUsers(\boolean|\Bitrix\Main\DB\SqlExpression $isForAllUsers)
	 * @method bool hasIsForAllUsers()
	 * @method bool isIsForAllUsersFilled()
	 * @method bool isIsForAllUsersChanged()
	 * @method \boolean remindActualIsForAllUsers()
	 * @method \boolean requireIsForAllUsers()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetIsForAllUsers()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetIsForAllUsers()
	 * @method \boolean fillIsForAllUsers()
	 * @method array getWorktimeRestrictions()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setWorktimeRestrictions(array|\Bitrix\Main\DB\SqlExpression $worktimeRestrictions)
	 * @method bool hasWorktimeRestrictions()
	 * @method bool isWorktimeRestrictionsFilled()
	 * @method bool isWorktimeRestrictionsChanged()
	 * @method array remindActualWorktimeRestrictions()
	 * @method array requireWorktimeRestrictions()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetWorktimeRestrictions()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetWorktimeRestrictions()
	 * @method array fillWorktimeRestrictions()
	 * @method \int getControlledActions()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setControlledActions(\int|\Bitrix\Main\DB\SqlExpression $controlledActions)
	 * @method bool hasControlledActions()
	 * @method bool isControlledActionsFilled()
	 * @method bool isControlledActionsChanged()
	 * @method \int remindActualControlledActions()
	 * @method \int requireControlledActions()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetControlledActions()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetControlledActions()
	 * @method \int fillControlledActions()
	 * @method \int getUpdatedBy()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setUpdatedBy(\int|\Bitrix\Main\DB\SqlExpression $updatedBy)
	 * @method bool hasUpdatedBy()
	 * @method bool isUpdatedByFilled()
	 * @method bool isUpdatedByChanged()
	 * @method \int remindActualUpdatedBy()
	 * @method \int requireUpdatedBy()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetUpdatedBy()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetUpdatedBy()
	 * @method \int fillUpdatedBy()
	 * @method \int getDeletedBy()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setDeletedBy(\int|\Bitrix\Main\DB\SqlExpression $deletedBy)
	 * @method bool hasDeletedBy()
	 * @method bool isDeletedByFilled()
	 * @method bool isDeletedByChanged()
	 * @method \int remindActualDeletedBy()
	 * @method \int requireDeletedBy()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetDeletedBy()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetDeletedBy()
	 * @method \int fillDeletedBy()
	 * @method \string getDeletedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setDeletedAt(\string|\Bitrix\Main\DB\SqlExpression $deletedAt)
	 * @method bool hasDeletedAt()
	 * @method bool isDeletedAtFilled()
	 * @method bool isDeletedAtChanged()
	 * @method \string remindActualDeletedAt()
	 * @method \string requireDeletedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetDeletedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetDeletedAt()
	 * @method \string fillDeletedAt()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetCreatedBy()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetCreatedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection getShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection requireShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection fillShifts()
	 * @method bool hasShifts()
	 * @method bool isShiftsFilled()
	 * @method bool isShiftsChanged()
	 * @method void addToShifts(\Bitrix\Timeman\Model\Schedule\Shift\Shift $shift)
	 * @method void removeFromShifts(\Bitrix\Timeman\Model\Schedule\Shift\Shift $shift)
	 * @method void removeAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection getAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection requireAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection fillAllShifts()
	 * @method bool hasAllShifts()
	 * @method bool isAllShiftsFilled()
	 * @method bool isAllShiftsChanged()
	 * @method void addToAllShifts(\Bitrix\Timeman\Model\Schedule\Shift\Shift $shift)
	 * @method void removeFromAllShifts(\Bitrix\Timeman\Model\Schedule\Shift\Shift $shift)
	 * @method void removeAllAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules getScheduleViolationRules()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules remindActualScheduleViolationRules()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules requireScheduleViolationRules()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setScheduleViolationRules(\Bitrix\Timeman\Model\Schedule\Violation\ViolationRules $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetScheduleViolationRules()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetScheduleViolationRules()
	 * @method bool hasScheduleViolationRules()
	 * @method bool isScheduleViolationRulesFilled()
	 * @method bool isScheduleViolationRulesChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules fillScheduleViolationRules()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar getCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar remindActualCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar requireCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule setCalendar(\Bitrix\Timeman\Model\Schedule\Calendar\Calendar $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetCalendar()
	 * @method bool hasCalendar()
	 * @method bool isCalendarFilled()
	 * @method bool isCalendarChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar fillCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection getUserAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection requireUserAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection fillUserAssignments()
	 * @method bool hasUserAssignments()
	 * @method bool isUserAssignmentsFilled()
	 * @method bool isUserAssignmentsChanged()
	 * @method void addToUserAssignments(\Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser $scheduleUser)
	 * @method void removeFromUserAssignments(\Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser $scheduleUser)
	 * @method void removeAllUserAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetUserAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetUserAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection getDepartmentAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection requireDepartmentAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection fillDepartmentAssignments()
	 * @method bool hasDepartmentAssignments()
	 * @method bool isDepartmentAssignmentsFilled()
	 * @method bool isDepartmentAssignmentsChanged()
	 * @method void addToDepartmentAssignments(\Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment $scheduleDepartment)
	 * @method void removeFromDepartmentAssignments(\Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment $scheduleDepartment)
	 * @method void removeAllDepartmentAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule resetDepartmentAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unsetDepartmentAssignments()
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
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Schedule\Schedule wakeUp($data)
	 */
	class EO_Schedule {
		/* @var \Bitrix\Timeman\Model\Schedule\ScheduleTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\ScheduleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Schedule {
	/**
	 * ScheduleCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getScheduleTypeList()
	 * @method \string[] fillScheduleType()
	 * @method \string[] getReportPeriodList()
	 * @method \string[] fillReportPeriod()
	 * @method array[] getReportPeriodOptionsList()
	 * @method array[] fillReportPeriodOptions()
	 * @method \int[] getCalendarIdList()
	 * @method \int[] fillCalendarId()
	 * @method array[] getAllowedDevicesList()
	 * @method array[] fillAllowedDevices()
	 * @method \string[] getDeletedList()
	 * @method \string[] fillDeleted()
	 * @method \boolean[] getIsForAllUsersList()
	 * @method \boolean[] fillIsForAllUsers()
	 * @method array[] getWorktimeRestrictionsList()
	 * @method array[] fillWorktimeRestrictions()
	 * @method \int[] getControlledActionsList()
	 * @method \int[] fillControlledActions()
	 * @method \int[] getUpdatedByList()
	 * @method \int[] fillUpdatedBy()
	 * @method \int[] getDeletedByList()
	 * @method \int[] fillDeletedBy()
	 * @method \string[] getDeletedAtList()
	 * @method \string[] fillDeletedAt()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection[] getShiftsList()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection getShiftsCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection fillShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection[] getAllShiftsList()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection getAllShiftsCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection fillAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules[] getScheduleViolationRulesList()
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection getScheduleViolationRulesCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection fillScheduleViolationRules()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\Calendar[] getCalendarList()
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection getCalendarCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection fillCalendar()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection[] getUserAssignmentsList()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection getUserAssignmentsCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection fillUserAssignments()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection[] getDepartmentAssignmentsList()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection getDepartmentAssignmentsCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection fillDepartmentAssignments()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Schedule\Schedule $object)
	 * @method bool has(\Bitrix\Timeman\Model\Schedule\Schedule $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Schedule\Schedule $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Schedule\ScheduleCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Schedule_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Schedule\ScheduleTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\ScheduleTable';
	}
}
namespace Bitrix\Timeman\Model\Schedule {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Schedule_Result exec()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Schedule_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection fetchCollection()
	 */
	class EO_Schedule_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection createCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection wakeUpCollection($rows)
	 */
	class EO_Schedule_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Schedule\Shift\ShiftTable:timeman\lib\model\schedule\shift\shifttable.php:87ff8911b7bc994a4cab62e9f981332e */
namespace Bitrix\Timeman\Model\Schedule\Shift {
	/**
	 * Shift
	 * @see \Bitrix\Timeman\Model\Schedule\Shift\ShiftTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift resetName()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unsetName()
	 * @method \string fillName()
	 * @method \int getBreakDuration()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setBreakDuration(\int|\Bitrix\Main\DB\SqlExpression $breakDuration)
	 * @method bool hasBreakDuration()
	 * @method bool isBreakDurationFilled()
	 * @method bool isBreakDurationChanged()
	 * @method \int remindActualBreakDuration()
	 * @method \int requireBreakDuration()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift resetBreakDuration()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unsetBreakDuration()
	 * @method \int fillBreakDuration()
	 * @method \int getWorkTimeStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setWorkTimeStart(\int|\Bitrix\Main\DB\SqlExpression $workTimeStart)
	 * @method bool hasWorkTimeStart()
	 * @method bool isWorkTimeStartFilled()
	 * @method bool isWorkTimeStartChanged()
	 * @method \int remindActualWorkTimeStart()
	 * @method \int requireWorkTimeStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift resetWorkTimeStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unsetWorkTimeStart()
	 * @method \int fillWorkTimeStart()
	 * @method \int getWorkTimeEnd()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setWorkTimeEnd(\int|\Bitrix\Main\DB\SqlExpression $workTimeEnd)
	 * @method bool hasWorkTimeEnd()
	 * @method bool isWorkTimeEndFilled()
	 * @method bool isWorkTimeEndChanged()
	 * @method \int remindActualWorkTimeEnd()
	 * @method \int requireWorkTimeEnd()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift resetWorkTimeEnd()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unsetWorkTimeEnd()
	 * @method \int fillWorkTimeEnd()
	 * @method \string getWorkDays()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setWorkDays(\string|\Bitrix\Main\DB\SqlExpression $workDays)
	 * @method bool hasWorkDays()
	 * @method bool isWorkDaysFilled()
	 * @method bool isWorkDaysChanged()
	 * @method \string remindActualWorkDays()
	 * @method \string requireWorkDays()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift resetWorkDays()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unsetWorkDays()
	 * @method \string fillWorkDays()
	 * @method \int getScheduleId()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setScheduleId(\int|\Bitrix\Main\DB\SqlExpression $scheduleId)
	 * @method bool hasScheduleId()
	 * @method bool isScheduleIdFilled()
	 * @method bool isScheduleIdChanged()
	 * @method \int remindActualScheduleId()
	 * @method \int requireScheduleId()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift resetScheduleId()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unsetScheduleId()
	 * @method \int fillScheduleId()
	 * @method \boolean getDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setDeleted(\boolean|\Bitrix\Main\DB\SqlExpression $deleted)
	 * @method bool hasDeleted()
	 * @method bool isDeletedFilled()
	 * @method bool isDeletedChanged()
	 * @method \boolean remindActualDeleted()
	 * @method \boolean requireDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift resetDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unsetDeleted()
	 * @method \boolean fillDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule getSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule remindActualSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule requireSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setSchedule(\Bitrix\Timeman\Model\Schedule\Schedule $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift resetSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unsetSchedule()
	 * @method bool hasSchedule()
	 * @method bool isScheduleFilled()
	 * @method bool isScheduleChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule fillSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule getScheduleWithAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule remindActualScheduleWithAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule requireScheduleWithAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift setScheduleWithAllShifts(\Bitrix\Timeman\Model\Schedule\Schedule $object)
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift resetScheduleWithAllShifts()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unsetScheduleWithAllShifts()
	 * @method bool hasScheduleWithAllShifts()
	 * @method bool isScheduleWithAllShiftsFilled()
	 * @method bool isScheduleWithAllShiftsChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule fillScheduleWithAllShifts()
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
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Schedule\Shift\Shift wakeUp($data)
	 */
	class EO_Shift {
		/* @var \Bitrix\Timeman\Model\Schedule\Shift\ShiftTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Shift\ShiftTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Schedule\Shift {
	/**
	 * ShiftCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getBreakDurationList()
	 * @method \int[] fillBreakDuration()
	 * @method \int[] getWorkTimeStartList()
	 * @method \int[] fillWorkTimeStart()
	 * @method \int[] getWorkTimeEndList()
	 * @method \int[] fillWorkTimeEnd()
	 * @method \string[] getWorkDaysList()
	 * @method \string[] fillWorkDays()
	 * @method \int[] getScheduleIdList()
	 * @method \int[] fillScheduleId()
	 * @method \boolean[] getDeletedList()
	 * @method \boolean[] fillDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule[] getScheduleList()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection getScheduleCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection fillSchedule()
	 * @method \Bitrix\Timeman\Model\Schedule\Schedule[] getScheduleWithAllShiftsList()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection getScheduleWithAllShiftsCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\ScheduleCollection fillScheduleWithAllShifts()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Schedule\Shift\Shift $object)
	 * @method bool has(\Bitrix\Timeman\Model\Schedule\Shift\Shift $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Schedule\Shift\Shift $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Shift_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Schedule\Shift\ShiftTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Shift\ShiftTable';
	}
}
namespace Bitrix\Timeman\Model\Schedule\Shift {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Shift_Result exec()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Shift_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection fetchCollection()
	 */
	class EO_Shift_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection createCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection wakeUpCollection($rows)
	 */
	class EO_Shift_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable:timeman\lib\model\schedule\shiftplan\shiftplantable.php:c4d0a90692bfe8293fc827884910f164 */
namespace Bitrix\Timeman\Model\Schedule\ShiftPlan {
	/**
	 * ShiftPlan
	 * @see \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getShiftId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan setShiftId(\int|\Bitrix\Main\DB\SqlExpression $shiftId)
	 * @method bool hasShiftId()
	 * @method bool isShiftIdFilled()
	 * @method bool isShiftIdChanged()
	 * @method \int remindActualShiftId()
	 * @method \int requireShiftId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan resetShiftId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan unsetShiftId()
	 * @method \int fillShiftId()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan resetUserId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\Type\Date getDateAssigned()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan setDateAssigned(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $dateAssigned)
	 * @method bool hasDateAssigned()
	 * @method bool isDateAssignedFilled()
	 * @method bool isDateAssignedChanged()
	 * @method \Bitrix\Main\Type\Date remindActualDateAssigned()
	 * @method \Bitrix\Main\Type\Date requireDateAssigned()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan resetDateAssigned()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan unsetDateAssigned()
	 * @method \Bitrix\Main\Type\Date fillDateAssigned()
	 * @method \boolean getDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan setDeleted(\boolean|\Bitrix\Main\DB\SqlExpression $deleted)
	 * @method bool hasDeleted()
	 * @method bool isDeletedFilled()
	 * @method bool isDeletedChanged()
	 * @method \boolean remindActualDeleted()
	 * @method \boolean requireDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan resetDeleted()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan unsetDeleted()
	 * @method \boolean fillDeleted()
	 * @method \int getCreatedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan setCreatedAt(\int|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \int remindActualCreatedAt()
	 * @method \int requireCreatedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan resetCreatedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan unsetCreatedAt()
	 * @method \int fillCreatedAt()
	 * @method \int getDeletedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan setDeletedAt(\int|\Bitrix\Main\DB\SqlExpression $deletedAt)
	 * @method bool hasDeletedAt()
	 * @method bool isDeletedAtFilled()
	 * @method bool isDeletedAtChanged()
	 * @method \int remindActualDeletedAt()
	 * @method \int requireDeletedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan resetDeletedAt()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan unsetDeletedAt()
	 * @method \int fillDeletedAt()
	 * @method \int getMissedShiftAgentId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan setMissedShiftAgentId(\int|\Bitrix\Main\DB\SqlExpression $missedShiftAgentId)
	 * @method bool hasMissedShiftAgentId()
	 * @method bool isMissedShiftAgentIdFilled()
	 * @method bool isMissedShiftAgentIdChanged()
	 * @method \int remindActualMissedShiftAgentId()
	 * @method \int requireMissedShiftAgentId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan resetMissedShiftAgentId()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan unsetMissedShiftAgentId()
	 * @method \int fillMissedShiftAgentId()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift getShift()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift remindActualShift()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift requireShift()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan setShift(\Bitrix\Timeman\Model\Schedule\Shift\Shift $object)
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan resetShift()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan unsetShift()
	 * @method bool hasShift()
	 * @method bool isShiftFilled()
	 * @method bool isShiftChanged()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift fillShift()
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
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan wakeUp($data)
	 */
	class EO_ShiftPlan {
		/* @var \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Schedule\ShiftPlan {
	/**
	 * ShiftPlanCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getShiftIdList()
	 * @method \int[] fillShiftId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\Type\Date[] getDateAssignedList()
	 * @method \Bitrix\Main\Type\Date[] fillDateAssigned()
	 * @method \boolean[] getDeletedList()
	 * @method \boolean[] fillDeleted()
	 * @method \int[] getCreatedAtList()
	 * @method \int[] fillCreatedAt()
	 * @method \int[] getDeletedAtList()
	 * @method \int[] fillDeletedAt()
	 * @method \int[] getMissedShiftAgentIdList()
	 * @method \int[] fillMissedShiftAgentId()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\Shift[] getShiftList()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanCollection getShiftCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection fillShift()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan $object)
	 * @method bool has(\Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ShiftPlan_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable';
	}
}
namespace Bitrix\Timeman\Model\Schedule\ShiftPlan {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ShiftPlan_Result exec()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ShiftPlan_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanCollection fetchCollection()
	 */
	class EO_ShiftPlan_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanCollection createCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanCollection wakeUpCollection($rows)
	 */
	class EO_ShiftPlan_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable:timeman\lib\model\schedule\violation\violationrulestable.php:43315f226036e8ccd3e0003c29ce42d6 */
namespace Bitrix\Timeman\Model\Schedule\Violation {
	/**
	 * ViolationRules
	 * @see \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getScheduleId()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setScheduleId(\int|\Bitrix\Main\DB\SqlExpression $scheduleId)
	 * @method bool hasScheduleId()
	 * @method bool isScheduleIdFilled()
	 * @method bool isScheduleIdChanged()
	 * @method \int remindActualScheduleId()
	 * @method \int requireScheduleId()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetScheduleId()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetScheduleId()
	 * @method \int fillScheduleId()
	 * @method \string getEntityCode()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setEntityCode(\string|\Bitrix\Main\DB\SqlExpression $entityCode)
	 * @method bool hasEntityCode()
	 * @method bool isEntityCodeFilled()
	 * @method bool isEntityCodeChanged()
	 * @method \string remindActualEntityCode()
	 * @method \string requireEntityCode()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetEntityCode()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetEntityCode()
	 * @method \string fillEntityCode()
	 * @method \int getMaxExactStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setMaxExactStart(\int|\Bitrix\Main\DB\SqlExpression $maxExactStart)
	 * @method bool hasMaxExactStart()
	 * @method bool isMaxExactStartFilled()
	 * @method bool isMaxExactStartChanged()
	 * @method \int remindActualMaxExactStart()
	 * @method \int requireMaxExactStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetMaxExactStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetMaxExactStart()
	 * @method \int fillMaxExactStart()
	 * @method \int getMinExactEnd()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setMinExactEnd(\int|\Bitrix\Main\DB\SqlExpression $minExactEnd)
	 * @method bool hasMinExactEnd()
	 * @method bool isMinExactEndFilled()
	 * @method bool isMinExactEndChanged()
	 * @method \int remindActualMinExactEnd()
	 * @method \int requireMinExactEnd()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetMinExactEnd()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetMinExactEnd()
	 * @method \int fillMinExactEnd()
	 * @method \int getMaxOffsetStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setMaxOffsetStart(\int|\Bitrix\Main\DB\SqlExpression $maxOffsetStart)
	 * @method bool hasMaxOffsetStart()
	 * @method bool isMaxOffsetStartFilled()
	 * @method bool isMaxOffsetStartChanged()
	 * @method \int remindActualMaxOffsetStart()
	 * @method \int requireMaxOffsetStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetMaxOffsetStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetMaxOffsetStart()
	 * @method \int fillMaxOffsetStart()
	 * @method \int getMinOffsetEnd()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setMinOffsetEnd(\int|\Bitrix\Main\DB\SqlExpression $minOffsetEnd)
	 * @method bool hasMinOffsetEnd()
	 * @method bool isMinOffsetEndFilled()
	 * @method bool isMinOffsetEndChanged()
	 * @method \int remindActualMinOffsetEnd()
	 * @method \int requireMinOffsetEnd()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetMinOffsetEnd()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetMinOffsetEnd()
	 * @method \int fillMinOffsetEnd()
	 * @method \int getRelativeStartFrom()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setRelativeStartFrom(\int|\Bitrix\Main\DB\SqlExpression $relativeStartFrom)
	 * @method bool hasRelativeStartFrom()
	 * @method bool isRelativeStartFromFilled()
	 * @method bool isRelativeStartFromChanged()
	 * @method \int remindActualRelativeStartFrom()
	 * @method \int requireRelativeStartFrom()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetRelativeStartFrom()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetRelativeStartFrom()
	 * @method \int fillRelativeStartFrom()
	 * @method \int getRelativeStartTo()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setRelativeStartTo(\int|\Bitrix\Main\DB\SqlExpression $relativeStartTo)
	 * @method bool hasRelativeStartTo()
	 * @method bool isRelativeStartToFilled()
	 * @method bool isRelativeStartToChanged()
	 * @method \int remindActualRelativeStartTo()
	 * @method \int requireRelativeStartTo()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetRelativeStartTo()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetRelativeStartTo()
	 * @method \int fillRelativeStartTo()
	 * @method \int getRelativeEndFrom()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setRelativeEndFrom(\int|\Bitrix\Main\DB\SqlExpression $relativeEndFrom)
	 * @method bool hasRelativeEndFrom()
	 * @method bool isRelativeEndFromFilled()
	 * @method bool isRelativeEndFromChanged()
	 * @method \int remindActualRelativeEndFrom()
	 * @method \int requireRelativeEndFrom()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetRelativeEndFrom()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetRelativeEndFrom()
	 * @method \int fillRelativeEndFrom()
	 * @method \int getRelativeEndTo()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setRelativeEndTo(\int|\Bitrix\Main\DB\SqlExpression $relativeEndTo)
	 * @method bool hasRelativeEndTo()
	 * @method bool isRelativeEndToFilled()
	 * @method bool isRelativeEndToChanged()
	 * @method \int remindActualRelativeEndTo()
	 * @method \int requireRelativeEndTo()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetRelativeEndTo()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetRelativeEndTo()
	 * @method \int fillRelativeEndTo()
	 * @method \int getMinDayDuration()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setMinDayDuration(\int|\Bitrix\Main\DB\SqlExpression $minDayDuration)
	 * @method bool hasMinDayDuration()
	 * @method bool isMinDayDurationFilled()
	 * @method bool isMinDayDurationChanged()
	 * @method \int remindActualMinDayDuration()
	 * @method \int requireMinDayDuration()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetMinDayDuration()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetMinDayDuration()
	 * @method \int fillMinDayDuration()
	 * @method \int getMaxAllowedToEditWorkTime()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setMaxAllowedToEditWorkTime(\int|\Bitrix\Main\DB\SqlExpression $maxAllowedToEditWorkTime)
	 * @method bool hasMaxAllowedToEditWorkTime()
	 * @method bool isMaxAllowedToEditWorkTimeFilled()
	 * @method bool isMaxAllowedToEditWorkTimeChanged()
	 * @method \int remindActualMaxAllowedToEditWorkTime()
	 * @method \int requireMaxAllowedToEditWorkTime()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetMaxAllowedToEditWorkTime()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetMaxAllowedToEditWorkTime()
	 * @method \int fillMaxAllowedToEditWorkTime()
	 * @method \int getMaxWorkTimeLackForPeriod()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setMaxWorkTimeLackForPeriod(\int|\Bitrix\Main\DB\SqlExpression $maxWorkTimeLackForPeriod)
	 * @method bool hasMaxWorkTimeLackForPeriod()
	 * @method bool isMaxWorkTimeLackForPeriodFilled()
	 * @method bool isMaxWorkTimeLackForPeriodChanged()
	 * @method \int remindActualMaxWorkTimeLackForPeriod()
	 * @method \int requireMaxWorkTimeLackForPeriod()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetMaxWorkTimeLackForPeriod()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetMaxWorkTimeLackForPeriod()
	 * @method \int fillMaxWorkTimeLackForPeriod()
	 * @method \int getPeriodTimeLackAgentId()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setPeriodTimeLackAgentId(\int|\Bitrix\Main\DB\SqlExpression $periodTimeLackAgentId)
	 * @method bool hasPeriodTimeLackAgentId()
	 * @method bool isPeriodTimeLackAgentIdFilled()
	 * @method bool isPeriodTimeLackAgentIdChanged()
	 * @method \int remindActualPeriodTimeLackAgentId()
	 * @method \int requirePeriodTimeLackAgentId()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetPeriodTimeLackAgentId()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetPeriodTimeLackAgentId()
	 * @method \int fillPeriodTimeLackAgentId()
	 * @method \int getMaxShiftStartDelay()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setMaxShiftStartDelay(\int|\Bitrix\Main\DB\SqlExpression $maxShiftStartDelay)
	 * @method bool hasMaxShiftStartDelay()
	 * @method bool isMaxShiftStartDelayFilled()
	 * @method bool isMaxShiftStartDelayChanged()
	 * @method \int remindActualMaxShiftStartDelay()
	 * @method \int requireMaxShiftStartDelay()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetMaxShiftStartDelay()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetMaxShiftStartDelay()
	 * @method \int fillMaxShiftStartDelay()
	 * @method \int getMissedShiftStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setMissedShiftStart(\int|\Bitrix\Main\DB\SqlExpression $missedShiftStart)
	 * @method bool hasMissedShiftStart()
	 * @method bool isMissedShiftStartFilled()
	 * @method bool isMissedShiftStartChanged()
	 * @method \int remindActualMissedShiftStart()
	 * @method \int requireMissedShiftStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetMissedShiftStart()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetMissedShiftStart()
	 * @method \int fillMissedShiftStart()
	 * @method array getUsersToNotify()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules setUsersToNotify(array|\Bitrix\Main\DB\SqlExpression $usersToNotify)
	 * @method bool hasUsersToNotify()
	 * @method bool isUsersToNotifyFilled()
	 * @method bool isUsersToNotifyChanged()
	 * @method array remindActualUsersToNotify()
	 * @method array requireUsersToNotify()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules resetUsersToNotify()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unsetUsersToNotify()
	 * @method array fillUsersToNotify()
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
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules wakeUp($data)
	 */
	class EO_ViolationRules {
		/* @var \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Schedule\Violation {
	/**
	 * ViolationRulesCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getScheduleIdList()
	 * @method \int[] fillScheduleId()
	 * @method \string[] getEntityCodeList()
	 * @method \string[] fillEntityCode()
	 * @method \int[] getMaxExactStartList()
	 * @method \int[] fillMaxExactStart()
	 * @method \int[] getMinExactEndList()
	 * @method \int[] fillMinExactEnd()
	 * @method \int[] getMaxOffsetStartList()
	 * @method \int[] fillMaxOffsetStart()
	 * @method \int[] getMinOffsetEndList()
	 * @method \int[] fillMinOffsetEnd()
	 * @method \int[] getRelativeStartFromList()
	 * @method \int[] fillRelativeStartFrom()
	 * @method \int[] getRelativeStartToList()
	 * @method \int[] fillRelativeStartTo()
	 * @method \int[] getRelativeEndFromList()
	 * @method \int[] fillRelativeEndFrom()
	 * @method \int[] getRelativeEndToList()
	 * @method \int[] fillRelativeEndTo()
	 * @method \int[] getMinDayDurationList()
	 * @method \int[] fillMinDayDuration()
	 * @method \int[] getMaxAllowedToEditWorkTimeList()
	 * @method \int[] fillMaxAllowedToEditWorkTime()
	 * @method \int[] getMaxWorkTimeLackForPeriodList()
	 * @method \int[] fillMaxWorkTimeLackForPeriod()
	 * @method \int[] getPeriodTimeLackAgentIdList()
	 * @method \int[] fillPeriodTimeLackAgentId()
	 * @method \int[] getMaxShiftStartDelayList()
	 * @method \int[] fillMaxShiftStartDelay()
	 * @method \int[] getMissedShiftStartList()
	 * @method \int[] fillMissedShiftStart()
	 * @method array[] getUsersToNotifyList()
	 * @method array[] fillUsersToNotify()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Schedule\Violation\ViolationRules $object)
	 * @method bool has(\Bitrix\Timeman\Model\Schedule\Violation\ViolationRules $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Schedule\Violation\ViolationRules $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ViolationRules_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable';
	}
}
namespace Bitrix\Timeman\Model\Schedule\Violation {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ViolationRules_Result exec()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ViolationRules_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules fetchObject()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection fetchCollection()
	 */
	class EO_ViolationRules_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection createCollection()
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection wakeUpCollection($rows)
	 */
	class EO_ViolationRules_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Security\TaskAccessCodeTable:timeman\lib\model\security\taskaccesscodetable.php:4fedce21534d0b676950a07c541a6f05 */
namespace Bitrix\Timeman\Model\Security {
	/**
	 * TaskAccessCode
	 * @see \Bitrix\Timeman\Model\Security\TaskAccessCodeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \string getAccessCode()
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \Bitrix\Main\EO_TaskOperation getTaskOperation()
	 * @method \Bitrix\Main\EO_TaskOperation remindActualTaskOperation()
	 * @method \Bitrix\Main\EO_TaskOperation requireTaskOperation()
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode setTaskOperation(\Bitrix\Main\EO_TaskOperation $object)
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode resetTaskOperation()
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode unsetTaskOperation()
	 * @method bool hasTaskOperation()
	 * @method bool isTaskOperationFilled()
	 * @method bool isTaskOperationChanged()
	 * @method \Bitrix\Main\EO_TaskOperation fillTaskOperation()
	 * @method \Bitrix\Main\EO_UserAccess getUserAccess()
	 * @method \Bitrix\Main\EO_UserAccess remindActualUserAccess()
	 * @method \Bitrix\Main\EO_UserAccess requireUserAccess()
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode setUserAccess(\Bitrix\Main\EO_UserAccess $object)
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode resetUserAccess()
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode unsetUserAccess()
	 * @method bool hasUserAccess()
	 * @method bool isUserAccessFilled()
	 * @method bool isUserAccessChanged()
	 * @method \Bitrix\Main\EO_UserAccess fillUserAccess()
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
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Security\TaskAccessCode wakeUp($data)
	 */
	class EO_TaskAccessCode {
		/* @var \Bitrix\Timeman\Model\Security\TaskAccessCodeTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Security\TaskAccessCodeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Security {
	/**
	 * EO_TaskAccessCode_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \string[] getAccessCodeList()
	 * @method \Bitrix\Main\EO_TaskOperation[] getTaskOperationList()
	 * @method \Bitrix\Timeman\Model\Security\EO_TaskAccessCode_Collection getTaskOperationCollection()
	 * @method \Bitrix\Main\EO_TaskOperation_Collection fillTaskOperation()
	 * @method \Bitrix\Main\EO_UserAccess[] getUserAccessList()
	 * @method \Bitrix\Timeman\Model\Security\EO_TaskAccessCode_Collection getUserAccessCollection()
	 * @method \Bitrix\Main\EO_UserAccess_Collection fillUserAccess()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Security\TaskAccessCode $object)
	 * @method bool has(\Bitrix\Timeman\Model\Security\TaskAccessCode $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Security\TaskAccessCode $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Security\EO_TaskAccessCode_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TaskAccessCode_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Security\TaskAccessCodeTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Security\TaskAccessCodeTable';
	}
}
namespace Bitrix\Timeman\Model\Security {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TaskAccessCode_Result exec()
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode fetchObject()
	 * @method \Bitrix\Timeman\Model\Security\EO_TaskAccessCode_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TaskAccessCode_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode fetchObject()
	 * @method \Bitrix\Timeman\Model\Security\EO_TaskAccessCode_Collection fetchCollection()
	 */
	class EO_TaskAccessCode_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Security\EO_TaskAccessCode_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Security\TaskAccessCode wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Security\EO_TaskAccessCode_Collection wakeUpCollection($rows)
	 */
	class EO_TaskAccessCode_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\User\UserTable:timeman\lib\model\user\usertable.php:63d8402d774159475fe7a50fcacfed09 */
namespace Bitrix\Timeman\Model\User {
	/**
	 * User
	 * @see \Bitrix\Timeman\Model\User\UserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\User\User setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getLogin()
	 * @method \Bitrix\Timeman\Model\User\User setLogin(\string|\Bitrix\Main\DB\SqlExpression $login)
	 * @method bool hasLogin()
	 * @method bool isLoginFilled()
	 * @method bool isLoginChanged()
	 * @method \string remindActualLogin()
	 * @method \string requireLogin()
	 * @method \Bitrix\Timeman\Model\User\User resetLogin()
	 * @method \Bitrix\Timeman\Model\User\User unsetLogin()
	 * @method \string fillLogin()
	 * @method \string getPassword()
	 * @method \Bitrix\Timeman\Model\User\User setPassword(\string|\Bitrix\Main\DB\SqlExpression $password)
	 * @method bool hasPassword()
	 * @method bool isPasswordFilled()
	 * @method bool isPasswordChanged()
	 * @method \string remindActualPassword()
	 * @method \string requirePassword()
	 * @method \Bitrix\Timeman\Model\User\User resetPassword()
	 * @method \Bitrix\Timeman\Model\User\User unsetPassword()
	 * @method \string fillPassword()
	 * @method \string getEmail()
	 * @method \Bitrix\Timeman\Model\User\User setEmail(\string|\Bitrix\Main\DB\SqlExpression $email)
	 * @method bool hasEmail()
	 * @method bool isEmailFilled()
	 * @method bool isEmailChanged()
	 * @method \string remindActualEmail()
	 * @method \string requireEmail()
	 * @method \Bitrix\Timeman\Model\User\User resetEmail()
	 * @method \Bitrix\Timeman\Model\User\User unsetEmail()
	 * @method \string fillEmail()
	 * @method \boolean getActive()
	 * @method \Bitrix\Timeman\Model\User\User setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Timeman\Model\User\User resetActive()
	 * @method \Bitrix\Timeman\Model\User\User unsetActive()
	 * @method \boolean fillActive()
	 * @method \boolean getBlocked()
	 * @method \Bitrix\Timeman\Model\User\User setBlocked(\boolean|\Bitrix\Main\DB\SqlExpression $blocked)
	 * @method bool hasBlocked()
	 * @method bool isBlockedFilled()
	 * @method bool isBlockedChanged()
	 * @method \boolean remindActualBlocked()
	 * @method \boolean requireBlocked()
	 * @method \Bitrix\Timeman\Model\User\User resetBlocked()
	 * @method \Bitrix\Timeman\Model\User\User unsetBlocked()
	 * @method \boolean fillBlocked()
	 * @method \Bitrix\Main\Type\DateTime getDateRegister()
	 * @method \Bitrix\Timeman\Model\User\User setDateRegister(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateRegister)
	 * @method bool hasDateRegister()
	 * @method bool isDateRegisterFilled()
	 * @method bool isDateRegisterChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateRegister()
	 * @method \Bitrix\Main\Type\DateTime requireDateRegister()
	 * @method \Bitrix\Timeman\Model\User\User resetDateRegister()
	 * @method \Bitrix\Timeman\Model\User\User unsetDateRegister()
	 * @method \Bitrix\Main\Type\DateTime fillDateRegister()
	 * @method \Bitrix\Main\Type\DateTime getDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime requireDateRegShort()
	 * @method bool hasDateRegShort()
	 * @method bool isDateRegShortFilled()
	 * @method \Bitrix\Timeman\Model\User\User unsetDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime fillDateRegShort()
	 * @method \Bitrix\Main\Type\DateTime getLastLogin()
	 * @method \Bitrix\Timeman\Model\User\User setLastLogin(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastLogin)
	 * @method bool hasLastLogin()
	 * @method bool isLastLoginFilled()
	 * @method bool isLastLoginChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastLogin()
	 * @method \Bitrix\Main\Type\DateTime requireLastLogin()
	 * @method \Bitrix\Timeman\Model\User\User resetLastLogin()
	 * @method \Bitrix\Timeman\Model\User\User unsetLastLogin()
	 * @method \Bitrix\Main\Type\DateTime fillLastLogin()
	 * @method \Bitrix\Main\Type\DateTime getLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime requireLastLoginShort()
	 * @method bool hasLastLoginShort()
	 * @method bool isLastLoginShortFilled()
	 * @method \Bitrix\Timeman\Model\User\User unsetLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime fillLastLoginShort()
	 * @method \Bitrix\Main\Type\DateTime getLastActivityDate()
	 * @method \Bitrix\Timeman\Model\User\User setLastActivityDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastActivityDate)
	 * @method bool hasLastActivityDate()
	 * @method bool isLastActivityDateFilled()
	 * @method bool isLastActivityDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime requireLastActivityDate()
	 * @method \Bitrix\Timeman\Model\User\User resetLastActivityDate()
	 * @method \Bitrix\Timeman\Model\User\User unsetLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime fillLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Timeman\Model\User\User setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Timeman\Model\User\User resetTimestampX()
	 * @method \Bitrix\Timeman\Model\User\User unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \string getName()
	 * @method \Bitrix\Timeman\Model\User\User setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Timeman\Model\User\User resetName()
	 * @method \Bitrix\Timeman\Model\User\User unsetName()
	 * @method \string fillName()
	 * @method \string getSecondName()
	 * @method \Bitrix\Timeman\Model\User\User setSecondName(\string|\Bitrix\Main\DB\SqlExpression $secondName)
	 * @method bool hasSecondName()
	 * @method bool isSecondNameFilled()
	 * @method bool isSecondNameChanged()
	 * @method \string remindActualSecondName()
	 * @method \string requireSecondName()
	 * @method \Bitrix\Timeman\Model\User\User resetSecondName()
	 * @method \Bitrix\Timeman\Model\User\User unsetSecondName()
	 * @method \string fillSecondName()
	 * @method \string getLastName()
	 * @method \Bitrix\Timeman\Model\User\User setLastName(\string|\Bitrix\Main\DB\SqlExpression $lastName)
	 * @method bool hasLastName()
	 * @method bool isLastNameFilled()
	 * @method bool isLastNameChanged()
	 * @method \string remindActualLastName()
	 * @method \string requireLastName()
	 * @method \Bitrix\Timeman\Model\User\User resetLastName()
	 * @method \Bitrix\Timeman\Model\User\User unsetLastName()
	 * @method \string fillLastName()
	 * @method \string getTitle()
	 * @method \Bitrix\Timeman\Model\User\User setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Timeman\Model\User\User resetTitle()
	 * @method \Bitrix\Timeman\Model\User\User unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getExternalAuthId()
	 * @method \Bitrix\Timeman\Model\User\User setExternalAuthId(\string|\Bitrix\Main\DB\SqlExpression $externalAuthId)
	 * @method bool hasExternalAuthId()
	 * @method bool isExternalAuthIdFilled()
	 * @method bool isExternalAuthIdChanged()
	 * @method \string remindActualExternalAuthId()
	 * @method \string requireExternalAuthId()
	 * @method \Bitrix\Timeman\Model\User\User resetExternalAuthId()
	 * @method \Bitrix\Timeman\Model\User\User unsetExternalAuthId()
	 * @method \string fillExternalAuthId()
	 * @method \string getXmlId()
	 * @method \Bitrix\Timeman\Model\User\User setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Timeman\Model\User\User resetXmlId()
	 * @method \Bitrix\Timeman\Model\User\User unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \string getBxUserId()
	 * @method \Bitrix\Timeman\Model\User\User setBxUserId(\string|\Bitrix\Main\DB\SqlExpression $bxUserId)
	 * @method bool hasBxUserId()
	 * @method bool isBxUserIdFilled()
	 * @method bool isBxUserIdChanged()
	 * @method \string remindActualBxUserId()
	 * @method \string requireBxUserId()
	 * @method \Bitrix\Timeman\Model\User\User resetBxUserId()
	 * @method \Bitrix\Timeman\Model\User\User unsetBxUserId()
	 * @method \string fillBxUserId()
	 * @method \string getConfirmCode()
	 * @method \Bitrix\Timeman\Model\User\User setConfirmCode(\string|\Bitrix\Main\DB\SqlExpression $confirmCode)
	 * @method bool hasConfirmCode()
	 * @method bool isConfirmCodeFilled()
	 * @method bool isConfirmCodeChanged()
	 * @method \string remindActualConfirmCode()
	 * @method \string requireConfirmCode()
	 * @method \Bitrix\Timeman\Model\User\User resetConfirmCode()
	 * @method \Bitrix\Timeman\Model\User\User unsetConfirmCode()
	 * @method \string fillConfirmCode()
	 * @method \string getLid()
	 * @method \Bitrix\Timeman\Model\User\User setLid(\string|\Bitrix\Main\DB\SqlExpression $lid)
	 * @method bool hasLid()
	 * @method bool isLidFilled()
	 * @method bool isLidChanged()
	 * @method \string remindActualLid()
	 * @method \string requireLid()
	 * @method \Bitrix\Timeman\Model\User\User resetLid()
	 * @method \Bitrix\Timeman\Model\User\User unsetLid()
	 * @method \string fillLid()
	 * @method \string getLanguageId()
	 * @method \Bitrix\Timeman\Model\User\User setLanguageId(\string|\Bitrix\Main\DB\SqlExpression $languageId)
	 * @method bool hasLanguageId()
	 * @method bool isLanguageIdFilled()
	 * @method bool isLanguageIdChanged()
	 * @method \string remindActualLanguageId()
	 * @method \string requireLanguageId()
	 * @method \Bitrix\Timeman\Model\User\User resetLanguageId()
	 * @method \Bitrix\Timeman\Model\User\User unsetLanguageId()
	 * @method \string fillLanguageId()
	 * @method \string getTimeZone()
	 * @method \Bitrix\Timeman\Model\User\User setTimeZone(\string|\Bitrix\Main\DB\SqlExpression $timeZone)
	 * @method bool hasTimeZone()
	 * @method bool isTimeZoneFilled()
	 * @method bool isTimeZoneChanged()
	 * @method \string remindActualTimeZone()
	 * @method \string requireTimeZone()
	 * @method \Bitrix\Timeman\Model\User\User resetTimeZone()
	 * @method \Bitrix\Timeman\Model\User\User unsetTimeZone()
	 * @method \string fillTimeZone()
	 * @method \int getTimeZoneOffset()
	 * @method \Bitrix\Timeman\Model\User\User setTimeZoneOffset(\int|\Bitrix\Main\DB\SqlExpression $timeZoneOffset)
	 * @method bool hasTimeZoneOffset()
	 * @method bool isTimeZoneOffsetFilled()
	 * @method bool isTimeZoneOffsetChanged()
	 * @method \int remindActualTimeZoneOffset()
	 * @method \int requireTimeZoneOffset()
	 * @method \Bitrix\Timeman\Model\User\User resetTimeZoneOffset()
	 * @method \Bitrix\Timeman\Model\User\User unsetTimeZoneOffset()
	 * @method \int fillTimeZoneOffset()
	 * @method \string getPersonalProfession()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalProfession(\string|\Bitrix\Main\DB\SqlExpression $personalProfession)
	 * @method bool hasPersonalProfession()
	 * @method bool isPersonalProfessionFilled()
	 * @method bool isPersonalProfessionChanged()
	 * @method \string remindActualPersonalProfession()
	 * @method \string requirePersonalProfession()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalProfession()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalProfession()
	 * @method \string fillPersonalProfession()
	 * @method \string getPersonalPhone()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalPhone(\string|\Bitrix\Main\DB\SqlExpression $personalPhone)
	 * @method bool hasPersonalPhone()
	 * @method bool isPersonalPhoneFilled()
	 * @method bool isPersonalPhoneChanged()
	 * @method \string remindActualPersonalPhone()
	 * @method \string requirePersonalPhone()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalPhone()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalPhone()
	 * @method \string fillPersonalPhone()
	 * @method \string getPersonalMobile()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalMobile(\string|\Bitrix\Main\DB\SqlExpression $personalMobile)
	 * @method bool hasPersonalMobile()
	 * @method bool isPersonalMobileFilled()
	 * @method bool isPersonalMobileChanged()
	 * @method \string remindActualPersonalMobile()
	 * @method \string requirePersonalMobile()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalMobile()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalMobile()
	 * @method \string fillPersonalMobile()
	 * @method \string getPersonalWww()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalWww(\string|\Bitrix\Main\DB\SqlExpression $personalWww)
	 * @method bool hasPersonalWww()
	 * @method bool isPersonalWwwFilled()
	 * @method bool isPersonalWwwChanged()
	 * @method \string remindActualPersonalWww()
	 * @method \string requirePersonalWww()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalWww()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalWww()
	 * @method \string fillPersonalWww()
	 * @method \string getPersonalIcq()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalIcq(\string|\Bitrix\Main\DB\SqlExpression $personalIcq)
	 * @method bool hasPersonalIcq()
	 * @method bool isPersonalIcqFilled()
	 * @method bool isPersonalIcqChanged()
	 * @method \string remindActualPersonalIcq()
	 * @method \string requirePersonalIcq()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalIcq()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalIcq()
	 * @method \string fillPersonalIcq()
	 * @method \string getPersonalFax()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalFax(\string|\Bitrix\Main\DB\SqlExpression $personalFax)
	 * @method bool hasPersonalFax()
	 * @method bool isPersonalFaxFilled()
	 * @method bool isPersonalFaxChanged()
	 * @method \string remindActualPersonalFax()
	 * @method \string requirePersonalFax()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalFax()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalFax()
	 * @method \string fillPersonalFax()
	 * @method \string getPersonalPager()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalPager(\string|\Bitrix\Main\DB\SqlExpression $personalPager)
	 * @method bool hasPersonalPager()
	 * @method bool isPersonalPagerFilled()
	 * @method bool isPersonalPagerChanged()
	 * @method \string remindActualPersonalPager()
	 * @method \string requirePersonalPager()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalPager()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalPager()
	 * @method \string fillPersonalPager()
	 * @method \string getPersonalStreet()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalStreet(\string|\Bitrix\Main\DB\SqlExpression $personalStreet)
	 * @method bool hasPersonalStreet()
	 * @method bool isPersonalStreetFilled()
	 * @method bool isPersonalStreetChanged()
	 * @method \string remindActualPersonalStreet()
	 * @method \string requirePersonalStreet()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalStreet()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalStreet()
	 * @method \string fillPersonalStreet()
	 * @method \string getPersonalMailbox()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalMailbox(\string|\Bitrix\Main\DB\SqlExpression $personalMailbox)
	 * @method bool hasPersonalMailbox()
	 * @method bool isPersonalMailboxFilled()
	 * @method bool isPersonalMailboxChanged()
	 * @method \string remindActualPersonalMailbox()
	 * @method \string requirePersonalMailbox()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalMailbox()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalMailbox()
	 * @method \string fillPersonalMailbox()
	 * @method \string getPersonalCity()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalCity(\string|\Bitrix\Main\DB\SqlExpression $personalCity)
	 * @method bool hasPersonalCity()
	 * @method bool isPersonalCityFilled()
	 * @method bool isPersonalCityChanged()
	 * @method \string remindActualPersonalCity()
	 * @method \string requirePersonalCity()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalCity()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalCity()
	 * @method \string fillPersonalCity()
	 * @method \string getPersonalState()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalState(\string|\Bitrix\Main\DB\SqlExpression $personalState)
	 * @method bool hasPersonalState()
	 * @method bool isPersonalStateFilled()
	 * @method bool isPersonalStateChanged()
	 * @method \string remindActualPersonalState()
	 * @method \string requirePersonalState()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalState()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalState()
	 * @method \string fillPersonalState()
	 * @method \string getPersonalZip()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalZip(\string|\Bitrix\Main\DB\SqlExpression $personalZip)
	 * @method bool hasPersonalZip()
	 * @method bool isPersonalZipFilled()
	 * @method bool isPersonalZipChanged()
	 * @method \string remindActualPersonalZip()
	 * @method \string requirePersonalZip()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalZip()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalZip()
	 * @method \string fillPersonalZip()
	 * @method \string getPersonalCountry()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalCountry(\string|\Bitrix\Main\DB\SqlExpression $personalCountry)
	 * @method bool hasPersonalCountry()
	 * @method bool isPersonalCountryFilled()
	 * @method bool isPersonalCountryChanged()
	 * @method \string remindActualPersonalCountry()
	 * @method \string requirePersonalCountry()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalCountry()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalCountry()
	 * @method \string fillPersonalCountry()
	 * @method \Bitrix\Main\Type\Date getPersonalBirthday()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalBirthday(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $personalBirthday)
	 * @method bool hasPersonalBirthday()
	 * @method bool isPersonalBirthdayFilled()
	 * @method bool isPersonalBirthdayChanged()
	 * @method \Bitrix\Main\Type\Date remindActualPersonalBirthday()
	 * @method \Bitrix\Main\Type\Date requirePersonalBirthday()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalBirthday()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalBirthday()
	 * @method \Bitrix\Main\Type\Date fillPersonalBirthday()
	 * @method \string getPersonalGender()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalGender(\string|\Bitrix\Main\DB\SqlExpression $personalGender)
	 * @method bool hasPersonalGender()
	 * @method bool isPersonalGenderFilled()
	 * @method bool isPersonalGenderChanged()
	 * @method \string remindActualPersonalGender()
	 * @method \string requirePersonalGender()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalGender()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalGender()
	 * @method \string fillPersonalGender()
	 * @method \int getPersonalPhoto()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalPhoto(\int|\Bitrix\Main\DB\SqlExpression $personalPhoto)
	 * @method bool hasPersonalPhoto()
	 * @method bool isPersonalPhotoFilled()
	 * @method bool isPersonalPhotoChanged()
	 * @method \int remindActualPersonalPhoto()
	 * @method \int requirePersonalPhoto()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalPhoto()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalPhoto()
	 * @method \int fillPersonalPhoto()
	 * @method \string getPersonalNotes()
	 * @method \Bitrix\Timeman\Model\User\User setPersonalNotes(\string|\Bitrix\Main\DB\SqlExpression $personalNotes)
	 * @method bool hasPersonalNotes()
	 * @method bool isPersonalNotesFilled()
	 * @method bool isPersonalNotesChanged()
	 * @method \string remindActualPersonalNotes()
	 * @method \string requirePersonalNotes()
	 * @method \Bitrix\Timeman\Model\User\User resetPersonalNotes()
	 * @method \Bitrix\Timeman\Model\User\User unsetPersonalNotes()
	 * @method \string fillPersonalNotes()
	 * @method \string getWorkCompany()
	 * @method \Bitrix\Timeman\Model\User\User setWorkCompany(\string|\Bitrix\Main\DB\SqlExpression $workCompany)
	 * @method bool hasWorkCompany()
	 * @method bool isWorkCompanyFilled()
	 * @method bool isWorkCompanyChanged()
	 * @method \string remindActualWorkCompany()
	 * @method \string requireWorkCompany()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkCompany()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkCompany()
	 * @method \string fillWorkCompany()
	 * @method \string getWorkDepartment()
	 * @method \Bitrix\Timeman\Model\User\User setWorkDepartment(\string|\Bitrix\Main\DB\SqlExpression $workDepartment)
	 * @method bool hasWorkDepartment()
	 * @method bool isWorkDepartmentFilled()
	 * @method bool isWorkDepartmentChanged()
	 * @method \string remindActualWorkDepartment()
	 * @method \string requireWorkDepartment()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkDepartment()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkDepartment()
	 * @method \string fillWorkDepartment()
	 * @method \string getWorkPhone()
	 * @method \Bitrix\Timeman\Model\User\User setWorkPhone(\string|\Bitrix\Main\DB\SqlExpression $workPhone)
	 * @method bool hasWorkPhone()
	 * @method bool isWorkPhoneFilled()
	 * @method bool isWorkPhoneChanged()
	 * @method \string remindActualWorkPhone()
	 * @method \string requireWorkPhone()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkPhone()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkPhone()
	 * @method \string fillWorkPhone()
	 * @method \string getWorkPosition()
	 * @method \Bitrix\Timeman\Model\User\User setWorkPosition(\string|\Bitrix\Main\DB\SqlExpression $workPosition)
	 * @method bool hasWorkPosition()
	 * @method bool isWorkPositionFilled()
	 * @method bool isWorkPositionChanged()
	 * @method \string remindActualWorkPosition()
	 * @method \string requireWorkPosition()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkPosition()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkPosition()
	 * @method \string fillWorkPosition()
	 * @method \string getWorkWww()
	 * @method \Bitrix\Timeman\Model\User\User setWorkWww(\string|\Bitrix\Main\DB\SqlExpression $workWww)
	 * @method bool hasWorkWww()
	 * @method bool isWorkWwwFilled()
	 * @method bool isWorkWwwChanged()
	 * @method \string remindActualWorkWww()
	 * @method \string requireWorkWww()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkWww()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkWww()
	 * @method \string fillWorkWww()
	 * @method \string getWorkFax()
	 * @method \Bitrix\Timeman\Model\User\User setWorkFax(\string|\Bitrix\Main\DB\SqlExpression $workFax)
	 * @method bool hasWorkFax()
	 * @method bool isWorkFaxFilled()
	 * @method bool isWorkFaxChanged()
	 * @method \string remindActualWorkFax()
	 * @method \string requireWorkFax()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkFax()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkFax()
	 * @method \string fillWorkFax()
	 * @method \string getWorkPager()
	 * @method \Bitrix\Timeman\Model\User\User setWorkPager(\string|\Bitrix\Main\DB\SqlExpression $workPager)
	 * @method bool hasWorkPager()
	 * @method bool isWorkPagerFilled()
	 * @method bool isWorkPagerChanged()
	 * @method \string remindActualWorkPager()
	 * @method \string requireWorkPager()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkPager()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkPager()
	 * @method \string fillWorkPager()
	 * @method \string getWorkStreet()
	 * @method \Bitrix\Timeman\Model\User\User setWorkStreet(\string|\Bitrix\Main\DB\SqlExpression $workStreet)
	 * @method bool hasWorkStreet()
	 * @method bool isWorkStreetFilled()
	 * @method bool isWorkStreetChanged()
	 * @method \string remindActualWorkStreet()
	 * @method \string requireWorkStreet()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkStreet()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkStreet()
	 * @method \string fillWorkStreet()
	 * @method \string getWorkMailbox()
	 * @method \Bitrix\Timeman\Model\User\User setWorkMailbox(\string|\Bitrix\Main\DB\SqlExpression $workMailbox)
	 * @method bool hasWorkMailbox()
	 * @method bool isWorkMailboxFilled()
	 * @method bool isWorkMailboxChanged()
	 * @method \string remindActualWorkMailbox()
	 * @method \string requireWorkMailbox()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkMailbox()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkMailbox()
	 * @method \string fillWorkMailbox()
	 * @method \string getWorkCity()
	 * @method \Bitrix\Timeman\Model\User\User setWorkCity(\string|\Bitrix\Main\DB\SqlExpression $workCity)
	 * @method bool hasWorkCity()
	 * @method bool isWorkCityFilled()
	 * @method bool isWorkCityChanged()
	 * @method \string remindActualWorkCity()
	 * @method \string requireWorkCity()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkCity()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkCity()
	 * @method \string fillWorkCity()
	 * @method \string getWorkState()
	 * @method \Bitrix\Timeman\Model\User\User setWorkState(\string|\Bitrix\Main\DB\SqlExpression $workState)
	 * @method bool hasWorkState()
	 * @method bool isWorkStateFilled()
	 * @method bool isWorkStateChanged()
	 * @method \string remindActualWorkState()
	 * @method \string requireWorkState()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkState()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkState()
	 * @method \string fillWorkState()
	 * @method \string getWorkZip()
	 * @method \Bitrix\Timeman\Model\User\User setWorkZip(\string|\Bitrix\Main\DB\SqlExpression $workZip)
	 * @method bool hasWorkZip()
	 * @method bool isWorkZipFilled()
	 * @method bool isWorkZipChanged()
	 * @method \string remindActualWorkZip()
	 * @method \string requireWorkZip()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkZip()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkZip()
	 * @method \string fillWorkZip()
	 * @method \string getWorkCountry()
	 * @method \Bitrix\Timeman\Model\User\User setWorkCountry(\string|\Bitrix\Main\DB\SqlExpression $workCountry)
	 * @method bool hasWorkCountry()
	 * @method bool isWorkCountryFilled()
	 * @method bool isWorkCountryChanged()
	 * @method \string remindActualWorkCountry()
	 * @method \string requireWorkCountry()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkCountry()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkCountry()
	 * @method \string fillWorkCountry()
	 * @method \string getWorkProfile()
	 * @method \Bitrix\Timeman\Model\User\User setWorkProfile(\string|\Bitrix\Main\DB\SqlExpression $workProfile)
	 * @method bool hasWorkProfile()
	 * @method bool isWorkProfileFilled()
	 * @method bool isWorkProfileChanged()
	 * @method \string remindActualWorkProfile()
	 * @method \string requireWorkProfile()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkProfile()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkProfile()
	 * @method \string fillWorkProfile()
	 * @method \int getWorkLogo()
	 * @method \Bitrix\Timeman\Model\User\User setWorkLogo(\int|\Bitrix\Main\DB\SqlExpression $workLogo)
	 * @method bool hasWorkLogo()
	 * @method bool isWorkLogoFilled()
	 * @method bool isWorkLogoChanged()
	 * @method \int remindActualWorkLogo()
	 * @method \int requireWorkLogo()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkLogo()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkLogo()
	 * @method \int fillWorkLogo()
	 * @method \string getWorkNotes()
	 * @method \Bitrix\Timeman\Model\User\User setWorkNotes(\string|\Bitrix\Main\DB\SqlExpression $workNotes)
	 * @method bool hasWorkNotes()
	 * @method bool isWorkNotesFilled()
	 * @method bool isWorkNotesChanged()
	 * @method \string remindActualWorkNotes()
	 * @method \string requireWorkNotes()
	 * @method \Bitrix\Timeman\Model\User\User resetWorkNotes()
	 * @method \Bitrix\Timeman\Model\User\User unsetWorkNotes()
	 * @method \string fillWorkNotes()
	 * @method \string getAdminNotes()
	 * @method \Bitrix\Timeman\Model\User\User setAdminNotes(\string|\Bitrix\Main\DB\SqlExpression $adminNotes)
	 * @method bool hasAdminNotes()
	 * @method bool isAdminNotesFilled()
	 * @method bool isAdminNotesChanged()
	 * @method \string remindActualAdminNotes()
	 * @method \string requireAdminNotes()
	 * @method \Bitrix\Timeman\Model\User\User resetAdminNotes()
	 * @method \Bitrix\Timeman\Model\User\User unsetAdminNotes()
	 * @method \string fillAdminNotes()
	 * @method \string getShortName()
	 * @method \string remindActualShortName()
	 * @method \string requireShortName()
	 * @method bool hasShortName()
	 * @method bool isShortNameFilled()
	 * @method \Bitrix\Timeman\Model\User\User unsetShortName()
	 * @method \string fillShortName()
	 * @method \boolean getIsOnline()
	 * @method \boolean remindActualIsOnline()
	 * @method \boolean requireIsOnline()
	 * @method bool hasIsOnline()
	 * @method bool isIsOnlineFilled()
	 * @method \Bitrix\Timeman\Model\User\User unsetIsOnline()
	 * @method \boolean fillIsOnline()
	 * @method \boolean getIsRealUser()
	 * @method \boolean remindActualIsRealUser()
	 * @method \boolean requireIsRealUser()
	 * @method bool hasIsRealUser()
	 * @method bool isIsRealUserFilled()
	 * @method \Bitrix\Timeman\Model\User\User unsetIsRealUser()
	 * @method \boolean fillIsRealUser()
	 * @method \Bitrix\Main\EO_UserIndex getIndex()
	 * @method \Bitrix\Main\EO_UserIndex remindActualIndex()
	 * @method \Bitrix\Main\EO_UserIndex requireIndex()
	 * @method \Bitrix\Timeman\Model\User\User setIndex(\Bitrix\Main\EO_UserIndex $object)
	 * @method \Bitrix\Timeman\Model\User\User resetIndex()
	 * @method \Bitrix\Timeman\Model\User\User unsetIndex()
	 * @method bool hasIndex()
	 * @method bool isIndexFilled()
	 * @method bool isIndexChanged()
	 * @method \Bitrix\Main\EO_UserIndex fillIndex()
	 * @method \Bitrix\Main\EO_UserCounter getCounter()
	 * @method \Bitrix\Main\EO_UserCounter remindActualCounter()
	 * @method \Bitrix\Main\EO_UserCounter requireCounter()
	 * @method \Bitrix\Timeman\Model\User\User setCounter(\Bitrix\Main\EO_UserCounter $object)
	 * @method \Bitrix\Timeman\Model\User\User resetCounter()
	 * @method \Bitrix\Timeman\Model\User\User unsetCounter()
	 * @method bool hasCounter()
	 * @method bool isCounterFilled()
	 * @method bool isCounterChanged()
	 * @method \Bitrix\Main\EO_UserCounter fillCounter()
	 * @method \Bitrix\Main\EO_UserPhoneAuth getPhoneAuth()
	 * @method \Bitrix\Main\EO_UserPhoneAuth remindActualPhoneAuth()
	 * @method \Bitrix\Main\EO_UserPhoneAuth requirePhoneAuth()
	 * @method \Bitrix\Timeman\Model\User\User setPhoneAuth(\Bitrix\Main\EO_UserPhoneAuth $object)
	 * @method \Bitrix\Timeman\Model\User\User resetPhoneAuth()
	 * @method \Bitrix\Timeman\Model\User\User unsetPhoneAuth()
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
	 * @method \Bitrix\Timeman\Model\User\User resetGroups()
	 * @method \Bitrix\Timeman\Model\User\User unsetGroups()
	 * @method \string getAutoTimeZone()
	 * @method \Bitrix\Timeman\Model\User\User setAutoTimeZone(\string|\Bitrix\Main\DB\SqlExpression $autoTimeZone)
	 * @method bool hasAutoTimeZone()
	 * @method bool isAutoTimeZoneFilled()
	 * @method bool isAutoTimeZoneChanged()
	 * @method \string remindActualAutoTimeZone()
	 * @method \string requireAutoTimeZone()
	 * @method \Bitrix\Timeman\Model\User\User resetAutoTimeZone()
	 * @method \Bitrix\Timeman\Model\User\User unsetAutoTimeZone()
	 * @method \string fillAutoTimeZone()
	 * @method \string getUserType()
	 * @method \string remindActualUserType()
	 * @method \string requireUserType()
	 * @method bool hasUserType()
	 * @method bool isUserTypeFilled()
	 * @method \Bitrix\Timeman\Model\User\User unsetUserType()
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
	 * @method \Bitrix\Timeman\Model\User\User resetTags()
	 * @method \Bitrix\Timeman\Model\User\User unsetTags()
	 * @method \string getUserTypeInner()
	 * @method \string remindActualUserTypeInner()
	 * @method \string requireUserTypeInner()
	 * @method bool hasUserTypeInner()
	 * @method bool isUserTypeInnerFilled()
	 * @method \Bitrix\Timeman\Model\User\User unsetUserTypeInner()
	 * @method \string fillUserTypeInner()
	 * @method \string getUserTypeIsEmployee()
	 * @method \string remindActualUserTypeIsEmployee()
	 * @method \string requireUserTypeIsEmployee()
	 * @method bool hasUserTypeIsEmployee()
	 * @method bool isUserTypeIsEmployeeFilled()
	 * @method \Bitrix\Timeman\Model\User\User unsetUserTypeIsEmployee()
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
	 * @method \Bitrix\Timeman\Model\User\User set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\User\User reset($fieldName)
	 * @method \Bitrix\Timeman\Model\User\User unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\User\User wakeUp($data)
	 */
	class EO_User {
		/* @var \Bitrix\Timeman\Model\User\UserTable */
		static public $dataClass = '\Bitrix\Timeman\Model\User\UserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\User {
	/**
	 * UserCollection
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
	 * @method \Bitrix\Timeman\Model\User\UserCollection getIndexCollection()
	 * @method \Bitrix\Main\EO_UserIndex_Collection fillIndex()
	 * @method \Bitrix\Main\EO_UserCounter[] getCounterList()
	 * @method \Bitrix\Timeman\Model\User\UserCollection getCounterCollection()
	 * @method \Bitrix\Main\EO_UserCounter_Collection fillCounter()
	 * @method \Bitrix\Main\EO_UserPhoneAuth[] getPhoneAuthList()
	 * @method \Bitrix\Timeman\Model\User\UserCollection getPhoneAuthCollection()
	 * @method \Bitrix\Main\EO_UserPhoneAuth_Collection fillPhoneAuth()
	 * @method \Bitrix\Main\EO_UserGroup_Collection[] getGroupsList()
	 * @method \Bitrix\Main\EO_UserGroup_Collection getGroupsCollection()
	 * @method \Bitrix\Main\EO_UserGroup_Collection fillGroups()
	 * @method \string[] getAutoTimeZoneList()
	 * @method \string[] fillAutoTimeZone()
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
	 * @method void add(\Bitrix\Timeman\Model\User\User $object)
	 * @method bool has(\Bitrix\Timeman\Model\User\User $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\User\User getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\User\User[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\User\User $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\User\UserCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\User\User current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_User_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\User\UserTable */
		static public $dataClass = '\Bitrix\Timeman\Model\User\UserTable';
	}
}
namespace Bitrix\Timeman\Model\User {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_User_Result exec()
	 * @method \Bitrix\Timeman\Model\User\User fetchObject()
	 * @method \Bitrix\Timeman\Model\User\UserCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_User_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\User\User fetchObject()
	 * @method \Bitrix\Timeman\Model\User\UserCollection fetchCollection()
	 */
	class EO_User_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\User\User createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\User\UserCollection createCollection()
	 * @method \Bitrix\Timeman\Model\User\User wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\User\UserCollection wakeUpCollection($rows)
	 */
	class EO_User_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable:timeman\lib\model\worktime\eventlog\worktimeeventtable.php:f00db95b82b060a7704c45294dcf2bbb */
namespace Bitrix\Timeman\Model\Worktime\EventLog {
	/**
	 * WorktimeEvent
	 * @see \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent resetUserId()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getEventType()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setEventType(\string|\Bitrix\Main\DB\SqlExpression $eventType)
	 * @method bool hasEventType()
	 * @method bool isEventTypeFilled()
	 * @method bool isEventTypeChanged()
	 * @method \string remindActualEventType()
	 * @method \string requireEventType()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent resetEventType()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unsetEventType()
	 * @method \string fillEventType()
	 * @method \string getEventSource()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setEventSource(\string|\Bitrix\Main\DB\SqlExpression $eventSource)
	 * @method bool hasEventSource()
	 * @method bool isEventSourceFilled()
	 * @method bool isEventSourceChanged()
	 * @method \string remindActualEventSource()
	 * @method \string requireEventSource()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent resetEventSource()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unsetEventSource()
	 * @method \string fillEventSource()
	 * @method \int getActualTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setActualTimestamp(\int|\Bitrix\Main\DB\SqlExpression $actualTimestamp)
	 * @method bool hasActualTimestamp()
	 * @method bool isActualTimestampFilled()
	 * @method bool isActualTimestampChanged()
	 * @method \int remindActualActualTimestamp()
	 * @method \int requireActualTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent resetActualTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unsetActualTimestamp()
	 * @method \int fillActualTimestamp()
	 * @method \int getRecordedValue()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setRecordedValue(\int|\Bitrix\Main\DB\SqlExpression $recordedValue)
	 * @method bool hasRecordedValue()
	 * @method bool isRecordedValueFilled()
	 * @method bool isRecordedValueChanged()
	 * @method \int remindActualRecordedValue()
	 * @method \int requireRecordedValue()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent resetRecordedValue()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unsetRecordedValue()
	 * @method \int fillRecordedValue()
	 * @method \int getRecordedOffset()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setRecordedOffset(\int|\Bitrix\Main\DB\SqlExpression $recordedOffset)
	 * @method bool hasRecordedOffset()
	 * @method bool isRecordedOffsetFilled()
	 * @method bool isRecordedOffsetChanged()
	 * @method \int remindActualRecordedOffset()
	 * @method \int requireRecordedOffset()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent resetRecordedOffset()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unsetRecordedOffset()
	 * @method \int fillRecordedOffset()
	 * @method \int getWorktimeRecordId()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setWorktimeRecordId(\int|\Bitrix\Main\DB\SqlExpression $worktimeRecordId)
	 * @method bool hasWorktimeRecordId()
	 * @method bool isWorktimeRecordIdFilled()
	 * @method bool isWorktimeRecordIdChanged()
	 * @method \int remindActualWorktimeRecordId()
	 * @method \int requireWorktimeRecordId()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent resetWorktimeRecordId()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unsetWorktimeRecordId()
	 * @method \int fillWorktimeRecordId()
	 * @method \string getReason()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setReason(\string|\Bitrix\Main\DB\SqlExpression $reason)
	 * @method bool hasReason()
	 * @method bool isReasonFilled()
	 * @method bool isReasonChanged()
	 * @method \string remindActualReason()
	 * @method \string requireReason()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent resetReason()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unsetReason()
	 * @method \string fillReason()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord getWorktimeRecord()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord remindActualWorktimeRecord()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord requireWorktimeRecord()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent setWorktimeRecord(\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord $object)
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent resetWorktimeRecord()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unsetWorktimeRecord()
	 * @method bool hasWorktimeRecord()
	 * @method bool isWorktimeRecordFilled()
	 * @method bool isWorktimeRecordChanged()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord fillWorktimeRecord()
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
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent wakeUp($data)
	 */
	class EO_WorktimeEvent {
		/* @var \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Worktime\EventLog {
	/**
	 * WorktimeEventCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getEventTypeList()
	 * @method \string[] fillEventType()
	 * @method \string[] getEventSourceList()
	 * @method \string[] fillEventSource()
	 * @method \int[] getActualTimestampList()
	 * @method \int[] fillActualTimestamp()
	 * @method \int[] getRecordedValueList()
	 * @method \int[] fillRecordedValue()
	 * @method \int[] getRecordedOffsetList()
	 * @method \int[] fillRecordedOffset()
	 * @method \int[] getWorktimeRecordIdList()
	 * @method \int[] fillWorktimeRecordId()
	 * @method \string[] getReasonList()
	 * @method \string[] fillReason()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord[] getWorktimeRecordList()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection getWorktimeRecordCollection()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection fillWorktimeRecord()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent $object)
	 * @method bool has(\Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_WorktimeEvent_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable';
	}
}
namespace Bitrix\Timeman\Model\Worktime\EventLog {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_WorktimeEvent_Result exec()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent fetchObject()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_WorktimeEvent_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent fetchObject()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection fetchCollection()
	 */
	class EO_WorktimeEvent_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection createCollection()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection wakeUpCollection($rows)
	 */
	class EO_WorktimeEvent_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable:timeman\lib\model\worktime\record\worktimerecordtable.php:844b112ef7b32d51b95e6034840b05e9 */
namespace Bitrix\Timeman\Model\Worktime\Record {
	/**
	 * WorktimeRecord
	 * @see \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetUserId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getRecordedStartTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setRecordedStartTimestamp(\int|\Bitrix\Main\DB\SqlExpression $recordedStartTimestamp)
	 * @method bool hasRecordedStartTimestamp()
	 * @method bool isRecordedStartTimestampFilled()
	 * @method bool isRecordedStartTimestampChanged()
	 * @method \int remindActualRecordedStartTimestamp()
	 * @method \int requireRecordedStartTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetRecordedStartTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetRecordedStartTimestamp()
	 * @method \int fillRecordedStartTimestamp()
	 * @method \int getStartOffset()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setStartOffset(\int|\Bitrix\Main\DB\SqlExpression $startOffset)
	 * @method bool hasStartOffset()
	 * @method bool isStartOffsetFilled()
	 * @method bool isStartOffsetChanged()
	 * @method \int remindActualStartOffset()
	 * @method \int requireStartOffset()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetStartOffset()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetStartOffset()
	 * @method \int fillStartOffset()
	 * @method \int getActualStartTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setActualStartTimestamp(\int|\Bitrix\Main\DB\SqlExpression $actualStartTimestamp)
	 * @method bool hasActualStartTimestamp()
	 * @method bool isActualStartTimestampFilled()
	 * @method bool isActualStartTimestampChanged()
	 * @method \int remindActualActualStartTimestamp()
	 * @method \int requireActualStartTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetActualStartTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetActualStartTimestamp()
	 * @method \int fillActualStartTimestamp()
	 * @method \int getRecordedStopTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setRecordedStopTimestamp(\int|\Bitrix\Main\DB\SqlExpression $recordedStopTimestamp)
	 * @method bool hasRecordedStopTimestamp()
	 * @method bool isRecordedStopTimestampFilled()
	 * @method bool isRecordedStopTimestampChanged()
	 * @method \int remindActualRecordedStopTimestamp()
	 * @method \int requireRecordedStopTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetRecordedStopTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetRecordedStopTimestamp()
	 * @method \int fillRecordedStopTimestamp()
	 * @method \int getStopOffset()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setStopOffset(\int|\Bitrix\Main\DB\SqlExpression $stopOffset)
	 * @method bool hasStopOffset()
	 * @method bool isStopOffsetFilled()
	 * @method bool isStopOffsetChanged()
	 * @method \int remindActualStopOffset()
	 * @method \int requireStopOffset()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetStopOffset()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetStopOffset()
	 * @method \int fillStopOffset()
	 * @method \int getActualStopTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setActualStopTimestamp(\int|\Bitrix\Main\DB\SqlExpression $actualStopTimestamp)
	 * @method bool hasActualStopTimestamp()
	 * @method bool isActualStopTimestampFilled()
	 * @method bool isActualStopTimestampChanged()
	 * @method \int remindActualActualStopTimestamp()
	 * @method \int requireActualStopTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetActualStopTimestamp()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetActualStopTimestamp()
	 * @method \int fillActualStopTimestamp()
	 * @method \string getCurrentStatus()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setCurrentStatus(\string|\Bitrix\Main\DB\SqlExpression $currentStatus)
	 * @method bool hasCurrentStatus()
	 * @method bool isCurrentStatusFilled()
	 * @method bool isCurrentStatusChanged()
	 * @method \string remindActualCurrentStatus()
	 * @method \string requireCurrentStatus()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetCurrentStatus()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetCurrentStatus()
	 * @method \string fillCurrentStatus()
	 * @method \int getDuration()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setDuration(\int|\Bitrix\Main\DB\SqlExpression $duration)
	 * @method bool hasDuration()
	 * @method bool isDurationFilled()
	 * @method bool isDurationChanged()
	 * @method \int remindActualDuration()
	 * @method \int requireDuration()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetDuration()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetDuration()
	 * @method \int fillDuration()
	 * @method \int getRecordedDuration()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setRecordedDuration(\int|\Bitrix\Main\DB\SqlExpression $recordedDuration)
	 * @method bool hasRecordedDuration()
	 * @method bool isRecordedDurationFilled()
	 * @method bool isRecordedDurationChanged()
	 * @method \int remindActualRecordedDuration()
	 * @method \int requireRecordedDuration()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetRecordedDuration()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetRecordedDuration()
	 * @method \int fillRecordedDuration()
	 * @method \int getTimeLeaks()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setTimeLeaks(\int|\Bitrix\Main\DB\SqlExpression $timeLeaks)
	 * @method bool hasTimeLeaks()
	 * @method bool isTimeLeaksFilled()
	 * @method bool isTimeLeaksChanged()
	 * @method \int remindActualTimeLeaks()
	 * @method \int requireTimeLeaks()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetTimeLeaks()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetTimeLeaks()
	 * @method \int fillTimeLeaks()
	 * @method \int getActualBreakLength()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setActualBreakLength(\int|\Bitrix\Main\DB\SqlExpression $actualBreakLength)
	 * @method bool hasActualBreakLength()
	 * @method bool isActualBreakLengthFilled()
	 * @method bool isActualBreakLengthChanged()
	 * @method \int remindActualActualBreakLength()
	 * @method \int requireActualBreakLength()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetActualBreakLength()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetActualBreakLength()
	 * @method \int fillActualBreakLength()
	 * @method \int getScheduleId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setScheduleId(\int|\Bitrix\Main\DB\SqlExpression $scheduleId)
	 * @method bool hasScheduleId()
	 * @method bool isScheduleIdFilled()
	 * @method bool isScheduleIdChanged()
	 * @method \int remindActualScheduleId()
	 * @method \int requireScheduleId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetScheduleId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetScheduleId()
	 * @method \int fillScheduleId()
	 * @method \int getShiftId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setShiftId(\int|\Bitrix\Main\DB\SqlExpression $shiftId)
	 * @method bool hasShiftId()
	 * @method bool isShiftIdFilled()
	 * @method bool isShiftIdChanged()
	 * @method \int remindActualShiftId()
	 * @method \int requireShiftId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetShiftId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetShiftId()
	 * @method \int fillShiftId()
	 * @method \int getAutoClosingAgentId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setAutoClosingAgentId(\int|\Bitrix\Main\DB\SqlExpression $autoClosingAgentId)
	 * @method bool hasAutoClosingAgentId()
	 * @method bool isAutoClosingAgentIdFilled()
	 * @method bool isAutoClosingAgentIdChanged()
	 * @method \int remindActualAutoClosingAgentId()
	 * @method \int requireAutoClosingAgentId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetAutoClosingAgentId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetAutoClosingAgentId()
	 * @method \int fillAutoClosingAgentId()
	 * @method \boolean getApproved()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setApproved(\boolean|\Bitrix\Main\DB\SqlExpression $approved)
	 * @method bool hasApproved()
	 * @method bool isApprovedFilled()
	 * @method bool isApprovedChanged()
	 * @method \boolean remindActualApproved()
	 * @method \boolean requireApproved()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetApproved()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetApproved()
	 * @method \boolean fillApproved()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetTimestampX()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getModifiedBy()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setModifiedBy(\int|\Bitrix\Main\DB\SqlExpression $modifiedBy)
	 * @method bool hasModifiedBy()
	 * @method bool isModifiedByFilled()
	 * @method bool isModifiedByChanged()
	 * @method \int remindActualModifiedBy()
	 * @method \int requireModifiedBy()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetModifiedBy()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetModifiedBy()
	 * @method \int fillModifiedBy()
	 * @method \int getApprovedBy()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setApprovedBy(\int|\Bitrix\Main\DB\SqlExpression $approvedBy)
	 * @method bool hasApprovedBy()
	 * @method bool isApprovedByFilled()
	 * @method bool isApprovedByChanged()
	 * @method \int remindActualApprovedBy()
	 * @method \int requireApprovedBy()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetApprovedBy()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetApprovedBy()
	 * @method \int fillApprovedBy()
	 * @method \boolean getActive()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetActive()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetActive()
	 * @method \boolean fillActive()
	 * @method \boolean getPaused()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setPaused(\boolean|\Bitrix\Main\DB\SqlExpression $paused)
	 * @method bool hasPaused()
	 * @method bool isPausedFilled()
	 * @method bool isPausedChanged()
	 * @method \boolean remindActualPaused()
	 * @method \boolean requirePaused()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetPaused()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetPaused()
	 * @method \boolean fillPaused()
	 * @method \Bitrix\Main\Type\DateTime getDateStart()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setDateStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStart()
	 * @method \Bitrix\Main\Type\DateTime requireDateStart()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetDateStart()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetDateStart()
	 * @method \Bitrix\Main\Type\DateTime fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime getDateFinish()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setDateFinish(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateFinish)
	 * @method bool hasDateFinish()
	 * @method bool isDateFinishFilled()
	 * @method bool isDateFinishChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateFinish()
	 * @method \Bitrix\Main\Type\DateTime requireDateFinish()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetDateFinish()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetDateFinish()
	 * @method \Bitrix\Main\Type\DateTime fillDateFinish()
	 * @method \int getTimeStart()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setTimeStart(\int|\Bitrix\Main\DB\SqlExpression $timeStart)
	 * @method bool hasTimeStart()
	 * @method bool isTimeStartFilled()
	 * @method bool isTimeStartChanged()
	 * @method \int remindActualTimeStart()
	 * @method \int requireTimeStart()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetTimeStart()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetTimeStart()
	 * @method \int fillTimeStart()
	 * @method \int getTimeFinish()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setTimeFinish(\int|\Bitrix\Main\DB\SqlExpression $timeFinish)
	 * @method bool hasTimeFinish()
	 * @method bool isTimeFinishFilled()
	 * @method bool isTimeFinishChanged()
	 * @method \int remindActualTimeFinish()
	 * @method \int requireTimeFinish()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetTimeFinish()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetTimeFinish()
	 * @method \int fillTimeFinish()
	 * @method array getTasks()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setTasks(array|\Bitrix\Main\DB\SqlExpression $tasks)
	 * @method bool hasTasks()
	 * @method bool isTasksFilled()
	 * @method bool isTasksChanged()
	 * @method array remindActualTasks()
	 * @method array requireTasks()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetTasks()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetTasks()
	 * @method array fillTasks()
	 * @method \string getIpOpen()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setIpOpen(\string|\Bitrix\Main\DB\SqlExpression $ipOpen)
	 * @method bool hasIpOpen()
	 * @method bool isIpOpenFilled()
	 * @method bool isIpOpenChanged()
	 * @method \string remindActualIpOpen()
	 * @method \string requireIpOpen()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetIpOpen()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetIpOpen()
	 * @method \string fillIpOpen()
	 * @method \string getIpClose()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setIpClose(\string|\Bitrix\Main\DB\SqlExpression $ipClose)
	 * @method bool hasIpClose()
	 * @method bool isIpCloseFilled()
	 * @method bool isIpCloseChanged()
	 * @method \string remindActualIpClose()
	 * @method \string requireIpClose()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetIpClose()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetIpClose()
	 * @method \string fillIpClose()
	 * @method \int getForumTopicId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setForumTopicId(\int|\Bitrix\Main\DB\SqlExpression $forumTopicId)
	 * @method bool hasForumTopicId()
	 * @method bool isForumTopicIdFilled()
	 * @method bool isForumTopicIdChanged()
	 * @method \int remindActualForumTopicId()
	 * @method \int requireForumTopicId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetForumTopicId()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetForumTopicId()
	 * @method \int fillForumTopicId()
	 * @method \float getLatOpen()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setLatOpen(\float|\Bitrix\Main\DB\SqlExpression $latOpen)
	 * @method bool hasLatOpen()
	 * @method bool isLatOpenFilled()
	 * @method bool isLatOpenChanged()
	 * @method \float remindActualLatOpen()
	 * @method \float requireLatOpen()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetLatOpen()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetLatOpen()
	 * @method \float fillLatOpen()
	 * @method \float getLonOpen()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setLonOpen(\float|\Bitrix\Main\DB\SqlExpression $lonOpen)
	 * @method bool hasLonOpen()
	 * @method bool isLonOpenFilled()
	 * @method bool isLonOpenChanged()
	 * @method \float remindActualLonOpen()
	 * @method \float requireLonOpen()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetLonOpen()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetLonOpen()
	 * @method \float fillLonOpen()
	 * @method \float getLatClose()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setLatClose(\float|\Bitrix\Main\DB\SqlExpression $latClose)
	 * @method bool hasLatClose()
	 * @method bool isLatCloseFilled()
	 * @method bool isLatCloseChanged()
	 * @method \float remindActualLatClose()
	 * @method \float requireLatClose()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetLatClose()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetLatClose()
	 * @method \float fillLatClose()
	 * @method \float getLonClose()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setLonClose(\float|\Bitrix\Main\DB\SqlExpression $lonClose)
	 * @method bool hasLonClose()
	 * @method bool isLonCloseFilled()
	 * @method bool isLonCloseChanged()
	 * @method \float remindActualLonClose()
	 * @method \float requireLonClose()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetLonClose()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetLonClose()
	 * @method \float fillLonClose()
	 * @method \Bitrix\Timeman\Model\User\User getUser()
	 * @method \Bitrix\Timeman\Model\User\User remindActualUser()
	 * @method \Bitrix\Timeman\Model\User\User requireUser()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord setUser(\Bitrix\Timeman\Model\User\User $object)
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetUser()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Timeman\Model\User\User fillUser()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection getWorktimeEvents()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection requireWorktimeEvents()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection fillWorktimeEvents()
	 * @method bool hasWorktimeEvents()
	 * @method bool isWorktimeEventsFilled()
	 * @method bool isWorktimeEventsChanged()
	 * @method void addToWorktimeEvents(\Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent $worktimeEvent)
	 * @method void removeFromWorktimeEvents(\Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent $worktimeEvent)
	 * @method void removeAllWorktimeEvents()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord resetWorktimeEvents()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unsetWorktimeEvents()
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
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord wakeUp($data)
	 */
	class EO_WorktimeRecord {
		/* @var \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Worktime\Record {
	/**
	 * WorktimeRecordCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getRecordedStartTimestampList()
	 * @method \int[] fillRecordedStartTimestamp()
	 * @method \int[] getStartOffsetList()
	 * @method \int[] fillStartOffset()
	 * @method \int[] getActualStartTimestampList()
	 * @method \int[] fillActualStartTimestamp()
	 * @method \int[] getRecordedStopTimestampList()
	 * @method \int[] fillRecordedStopTimestamp()
	 * @method \int[] getStopOffsetList()
	 * @method \int[] fillStopOffset()
	 * @method \int[] getActualStopTimestampList()
	 * @method \int[] fillActualStopTimestamp()
	 * @method \string[] getCurrentStatusList()
	 * @method \string[] fillCurrentStatus()
	 * @method \int[] getDurationList()
	 * @method \int[] fillDuration()
	 * @method \int[] getRecordedDurationList()
	 * @method \int[] fillRecordedDuration()
	 * @method \int[] getTimeLeaksList()
	 * @method \int[] fillTimeLeaks()
	 * @method \int[] getActualBreakLengthList()
	 * @method \int[] fillActualBreakLength()
	 * @method \int[] getScheduleIdList()
	 * @method \int[] fillScheduleId()
	 * @method \int[] getShiftIdList()
	 * @method \int[] fillShiftId()
	 * @method \int[] getAutoClosingAgentIdList()
	 * @method \int[] fillAutoClosingAgentId()
	 * @method \boolean[] getApprovedList()
	 * @method \boolean[] fillApproved()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getModifiedByList()
	 * @method \int[] fillModifiedBy()
	 * @method \int[] getApprovedByList()
	 * @method \int[] fillApprovedBy()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \boolean[] getPausedList()
	 * @method \boolean[] fillPaused()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime[] getDateFinishList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateFinish()
	 * @method \int[] getTimeStartList()
	 * @method \int[] fillTimeStart()
	 * @method \int[] getTimeFinishList()
	 * @method \int[] fillTimeFinish()
	 * @method array[] getTasksList()
	 * @method array[] fillTasks()
	 * @method \string[] getIpOpenList()
	 * @method \string[] fillIpOpen()
	 * @method \string[] getIpCloseList()
	 * @method \string[] fillIpClose()
	 * @method \int[] getForumTopicIdList()
	 * @method \int[] fillForumTopicId()
	 * @method \float[] getLatOpenList()
	 * @method \float[] fillLatOpen()
	 * @method \float[] getLonOpenList()
	 * @method \float[] fillLonOpen()
	 * @method \float[] getLatCloseList()
	 * @method \float[] fillLatClose()
	 * @method \float[] getLonCloseList()
	 * @method \float[] fillLonClose()
	 * @method \Bitrix\Timeman\Model\User\User[] getUserList()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection getUserCollection()
	 * @method \Bitrix\Timeman\Model\User\UserCollection fillUser()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection[] getWorktimeEventsList()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection getWorktimeEventsCollection()
	 * @method \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection fillWorktimeEvents()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord $object)
	 * @method bool has(\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_WorktimeRecord_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable';
	}
}
namespace Bitrix\Timeman\Model\Worktime\Record {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_WorktimeRecord_Result exec()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord fetchObject()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_WorktimeRecord_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord fetchObject()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection fetchCollection()
	 */
	class EO_WorktimeRecord_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection createCollection()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection wakeUpCollection($rows)
	 */
	class EO_WorktimeRecord_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable:timeman\lib\model\worktime\report\worktimereporttable.php:f3c1298889c747348d7be808bc0d2981 */
namespace Bitrix\Timeman\Model\Worktime\Report {
	/**
	 * WorktimeReport
	 * @see \Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport resetTimestampX()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getEntryId()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport setEntryId(\int|\Bitrix\Main\DB\SqlExpression $entryId)
	 * @method bool hasEntryId()
	 * @method bool isEntryIdFilled()
	 * @method bool isEntryIdChanged()
	 * @method \int remindActualEntryId()
	 * @method \int requireEntryId()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport resetEntryId()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport unsetEntryId()
	 * @method \int fillEntryId()
	 * @method \int getUserId()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport resetUserId()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport unsetUserId()
	 * @method \int fillUserId()
	 * @method \boolean getActive()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport resetActive()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport unsetActive()
	 * @method \boolean fillActive()
	 * @method \string getReportType()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport setReportType(\string|\Bitrix\Main\DB\SqlExpression $reportType)
	 * @method bool hasReportType()
	 * @method bool isReportTypeFilled()
	 * @method bool isReportTypeChanged()
	 * @method \string remindActualReportType()
	 * @method \string requireReportType()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport resetReportType()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport unsetReportType()
	 * @method \string fillReportType()
	 * @method \string getReport()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport setReport(\string|\Bitrix\Main\DB\SqlExpression $report)
	 * @method bool hasReport()
	 * @method bool isReportFilled()
	 * @method bool isReportChanged()
	 * @method \string remindActualReport()
	 * @method \string requireReport()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport resetReport()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport unsetReport()
	 * @method \string fillReport()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord getRecord()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord remindActualRecord()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord requireRecord()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport setRecord(\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord $object)
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport resetRecord()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport unsetRecord()
	 * @method bool hasRecord()
	 * @method bool isRecordFilled()
	 * @method bool isRecordChanged()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord fillRecord()
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
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport set($fieldName, $value)
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport reset($fieldName)
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport wakeUp($data)
	 */
	class EO_WorktimeReport {
		/* @var \Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Timeman\Model\Worktime\Report {
	/**
	 * EO_WorktimeReport_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getEntryIdList()
	 * @method \int[] fillEntryId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \string[] getReportTypeList()
	 * @method \string[] fillReportType()
	 * @method \string[] getReportList()
	 * @method \string[] fillReport()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord[] getRecordList()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\EO_WorktimeReport_Collection getRecordCollection()
	 * @method \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection fillRecord()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Timeman\Model\Worktime\Report\WorktimeReport $object)
	 * @method bool has(\Bitrix\Timeman\Model\Worktime\Report\WorktimeReport $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport getByPrimary($primary)
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport[] getAll()
	 * @method bool remove(\Bitrix\Timeman\Model\Worktime\Report\WorktimeReport $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Timeman\Model\Worktime\Report\EO_WorktimeReport_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_WorktimeReport_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable */
		static public $dataClass = '\Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable';
	}
}
namespace Bitrix\Timeman\Model\Worktime\Report {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_WorktimeReport_Result exec()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport fetchObject()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\EO_WorktimeReport_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_WorktimeReport_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport fetchObject()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\EO_WorktimeReport_Collection fetchCollection()
	 */
	class EO_WorktimeReport_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport createObject($setDefaultValues = true)
	 * @method \Bitrix\Timeman\Model\Worktime\Report\EO_WorktimeReport_Collection createCollection()
	 * @method \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport wakeUpObject($row)
	 * @method \Bitrix\Timeman\Model\Worktime\Report\EO_WorktimeReport_Collection wakeUpCollection($rows)
	 */
	class EO_WorktimeReport_Entity extends \Bitrix\Main\ORM\Entity {}
}