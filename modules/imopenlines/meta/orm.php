<?php

/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable:imopenlines/lib/integrations/report/statistics/entity/dialogstat.php:9b0ca97d6da69753bb73429e4b39ab90 */
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity {
	/**
	 * EO_DialogStat
	 * @see \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \Bitrix\Main\Type\Date getDate()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setDate(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $date)
	 * @method bool hasDate()
	 * @method bool isDateFilled()
	 * @method bool isDateChanged()
	 * @method \int getOpenLineId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setOpenLineId(\int|\Bitrix\Main\DB\SqlExpression $openLineId)
	 * @method bool hasOpenLineId()
	 * @method bool isOpenLineIdFilled()
	 * @method bool isOpenLineIdChanged()
	 * @method \string getSourceId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setSourceId(\string|\Bitrix\Main\DB\SqlExpression $sourceId)
	 * @method bool hasSourceId()
	 * @method bool isSourceIdFilled()
	 * @method bool isSourceIdChanged()
	 * @method \int getOperatorId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setOperatorId(\int|\Bitrix\Main\DB\SqlExpression $operatorId)
	 * @method bool hasOperatorId()
	 * @method bool isOperatorIdFilled()
	 * @method bool isOperatorIdChanged()
	 * @method \int getAnsweredQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setAnsweredQty(\int|\Bitrix\Main\DB\SqlExpression $answeredQty)
	 * @method bool hasAnsweredQty()
	 * @method bool isAnsweredQtyFilled()
	 * @method bool isAnsweredQtyChanged()
	 * @method \int remindActualAnsweredQty()
	 * @method \int requireAnsweredQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat resetAnsweredQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetAnsweredQty()
	 * @method \int fillAnsweredQty()
	 * @method \int getSkipQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setSkipQty(\int|\Bitrix\Main\DB\SqlExpression $skipQty)
	 * @method bool hasSkipQty()
	 * @method bool isSkipQtyFilled()
	 * @method bool isSkipQtyChanged()
	 * @method \int remindActualSkipQty()
	 * @method \int requireSkipQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat resetSkipQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetSkipQty()
	 * @method \int fillSkipQty()
	 * @method \int getAppointedQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setAppointedQty(\int|\Bitrix\Main\DB\SqlExpression $appointedQty)
	 * @method bool hasAppointedQty()
	 * @method bool isAppointedQtyFilled()
	 * @method bool isAppointedQtyChanged()
	 * @method \int remindActualAppointedQty()
	 * @method \int requireAppointedQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat resetAppointedQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetAppointedQty()
	 * @method \int fillAppointedQty()
	 * @method \int getAverageSecsToAnswer()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setAverageSecsToAnswer(\int|\Bitrix\Main\DB\SqlExpression $averageSecsToAnswer)
	 * @method bool hasAverageSecsToAnswer()
	 * @method bool isAverageSecsToAnswerFilled()
	 * @method bool isAverageSecsToAnswerChanged()
	 * @method \int remindActualAverageSecsToAnswer()
	 * @method \int requireAverageSecsToAnswer()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat resetAverageSecsToAnswer()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetAverageSecsToAnswer()
	 * @method \int fillAverageSecsToAnswer()
	 * @method \int getPositiveQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setPositiveQty(\int|\Bitrix\Main\DB\SqlExpression $positiveQty)
	 * @method bool hasPositiveQty()
	 * @method bool isPositiveQtyFilled()
	 * @method bool isPositiveQtyChanged()
	 * @method \int remindActualPositiveQty()
	 * @method \int requirePositiveQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat resetPositiveQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetPositiveQty()
	 * @method \int fillPositiveQty()
	 * @method \int getNegativeQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setNegativeQty(\int|\Bitrix\Main\DB\SqlExpression $negativeQty)
	 * @method bool hasNegativeQty()
	 * @method bool isNegativeQtyFilled()
	 * @method bool isNegativeQtyChanged()
	 * @method \int remindActualNegativeQty()
	 * @method \int requireNegativeQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat resetNegativeQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetNegativeQty()
	 * @method \int fillNegativeQty()
	 * @method \int getWithoutMarkQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setWithoutMarkQty(\int|\Bitrix\Main\DB\SqlExpression $withoutMarkQty)
	 * @method bool hasWithoutMarkQty()
	 * @method bool isWithoutMarkQtyFilled()
	 * @method bool isWithoutMarkQtyChanged()
	 * @method \int remindActualWithoutMarkQty()
	 * @method \int requireWithoutMarkQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat resetWithoutMarkQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetWithoutMarkQty()
	 * @method \int fillWithoutMarkQty()
	 * @method \string getTotalMarkCnt()
	 * @method \string remindActualTotalMarkCnt()
	 * @method \string requireTotalMarkCnt()
	 * @method bool hasTotalMarkCnt()
	 * @method bool isTotalMarkCntFilled()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetTotalMarkCnt()
	 * @method \string fillTotalMarkCnt()
	 * @method \int getFirstTreatmentQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setFirstTreatmentQty(\int|\Bitrix\Main\DB\SqlExpression $firstTreatmentQty)
	 * @method bool hasFirstTreatmentQty()
	 * @method bool isFirstTreatmentQtyFilled()
	 * @method bool isFirstTreatmentQtyChanged()
	 * @method \int remindActualFirstTreatmentQty()
	 * @method \int requireFirstTreatmentQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat resetFirstTreatmentQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetFirstTreatmentQty()
	 * @method \int fillFirstTreatmentQty()
	 * @method \int getRepeatedTreatmentQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat setRepeatedTreatmentQty(\int|\Bitrix\Main\DB\SqlExpression $repeatedTreatmentQty)
	 * @method bool hasRepeatedTreatmentQty()
	 * @method bool isRepeatedTreatmentQtyFilled()
	 * @method bool isRepeatedTreatmentQtyChanged()
	 * @method \int remindActualRepeatedTreatmentQty()
	 * @method \int requireRepeatedTreatmentQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat resetRepeatedTreatmentQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetRepeatedTreatmentQty()
	 * @method \int fillRepeatedTreatmentQty()
	 * @method \string getTotalTreatmentCnt()
	 * @method \string remindActualTotalTreatmentCnt()
	 * @method \string requireTotalTreatmentCnt()
	 * @method bool hasTotalTreatmentCnt()
	 * @method bool isTotalTreatmentCntFilled()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unsetTotalTreatmentCnt()
	 * @method \string fillTotalTreatmentCnt()
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
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat wakeUp($data)
	 */
	class EO_DialogStat {
		/* @var \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity {
	/**
	 * EO_DialogStat_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \Bitrix\Main\Type\Date[] getDateList()
	 * @method \int[] getOpenLineIdList()
	 * @method \string[] getSourceIdList()
	 * @method \int[] getOperatorIdList()
	 * @method \int[] getAnsweredQtyList()
	 * @method \int[] fillAnsweredQty()
	 * @method \int[] getSkipQtyList()
	 * @method \int[] fillSkipQty()
	 * @method \int[] getAppointedQtyList()
	 * @method \int[] fillAppointedQty()
	 * @method \int[] getAverageSecsToAnswerList()
	 * @method \int[] fillAverageSecsToAnswer()
	 * @method \int[] getPositiveQtyList()
	 * @method \int[] fillPositiveQty()
	 * @method \int[] getNegativeQtyList()
	 * @method \int[] fillNegativeQty()
	 * @method \int[] getWithoutMarkQtyList()
	 * @method \int[] fillWithoutMarkQty()
	 * @method \string[] getTotalMarkCntList()
	 * @method \string[] fillTotalMarkCnt()
	 * @method \int[] getFirstTreatmentQtyList()
	 * @method \int[] fillFirstTreatmentQty()
	 * @method \int[] getRepeatedTreatmentQtyList()
	 * @method \int[] fillRepeatedTreatmentQty()
	 * @method \string[] getTotalTreatmentCntList()
	 * @method \string[] fillTotalTreatmentCnt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat $object)
	 * @method bool has(\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_DialogStat_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable';
	}
}
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_DialogStat_Result exec()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat fetchObject()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_DialogStat_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat fetchObject()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat_Collection fetchCollection()
	 */
	class EO_DialogStat_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_DialogStat_Collection wakeUpCollection($rows)
	 */
	class EO_DialogStat_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\StatisticQueueTable:imopenlines/lib/integrations/report/statistics/entity/statisticqueue.php:526a02160bf11664f71e331be0ca83cb */
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity {
	/**
	 * EO_StatisticQueue
	 * @see \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\StatisticQueueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getSessionId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue resetSessionId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \string getStatisticKey()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue setStatisticKey(\string|\Bitrix\Main\DB\SqlExpression $statisticKey)
	 * @method bool hasStatisticKey()
	 * @method bool isStatisticKeyFilled()
	 * @method bool isStatisticKeyChanged()
	 * @method \string remindActualStatisticKey()
	 * @method \string requireStatisticKey()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue resetStatisticKey()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue unsetStatisticKey()
	 * @method \string fillStatisticKey()
	 * @method \Bitrix\Main\Type\DateTime getDateQueue()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue setDateQueue(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateQueue)
	 * @method bool hasDateQueue()
	 * @method bool isDateQueueFilled()
	 * @method bool isDateQueueChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateQueue()
	 * @method \Bitrix\Main\Type\DateTime requireDateQueue()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue resetDateQueue()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue unsetDateQueue()
	 * @method \Bitrix\Main\Type\DateTime fillDateQueue()
	 * @method \string getParams()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue setParams(\string|\Bitrix\Main\DB\SqlExpression $params)
	 * @method bool hasParams()
	 * @method bool isParamsFilled()
	 * @method bool isParamsChanged()
	 * @method \string remindActualParams()
	 * @method \string requireParams()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue resetParams()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue unsetParams()
	 * @method \string fillParams()
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
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue wakeUp($data)
	 */
	class EO_StatisticQueue {
		/* @var \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\StatisticQueueTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\StatisticQueueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity {
	/**
	 * EO_StatisticQueue_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \string[] getStatisticKeyList()
	 * @method \string[] fillStatisticKey()
	 * @method \Bitrix\Main\Type\DateTime[] getDateQueueList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateQueue()
	 * @method \string[] getParamsList()
	 * @method \string[] fillParams()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue $object)
	 * @method bool has(\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_StatisticQueue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\StatisticQueueTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\StatisticQueueTable';
	}
}
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_StatisticQueue_Result exec()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue fetchObject()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_StatisticQueue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue fetchObject()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue_Collection fetchCollection()
	 */
	class EO_StatisticQueue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue_Collection wakeUpCollection($rows)
	 */
	class EO_StatisticQueue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentByHourStatTable:imopenlines/lib/integrations/report/statistics/entity/treatmentbyhourstat.php:c49d90a3fb9151bd765c30d3bf2c2985 */
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity {
	/**
	 * EO_TreatmentByHourStat
	 * @see \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentByHourStatTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \Bitrix\Main\Type\DateTime getDate()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat setDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $date)
	 * @method bool hasDate()
	 * @method bool isDateFilled()
	 * @method bool isDateChanged()
	 * @method \int getOpenLineId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat setOpenLineId(\int|\Bitrix\Main\DB\SqlExpression $openLineId)
	 * @method bool hasOpenLineId()
	 * @method bool isOpenLineIdFilled()
	 * @method bool isOpenLineIdChanged()
	 * @method \string getSourceId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat setSourceId(\string|\Bitrix\Main\DB\SqlExpression $sourceId)
	 * @method bool hasSourceId()
	 * @method bool isSourceIdFilled()
	 * @method bool isSourceIdChanged()
	 * @method \int getOperatorId()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat setOperatorId(\int|\Bitrix\Main\DB\SqlExpression $operatorId)
	 * @method bool hasOperatorId()
	 * @method bool isOperatorIdFilled()
	 * @method bool isOperatorIdChanged()
	 * @method \int getQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat setQty(\int|\Bitrix\Main\DB\SqlExpression $qty)
	 * @method bool hasQty()
	 * @method bool isQtyFilled()
	 * @method bool isQtyChanged()
	 * @method \int remindActualQty()
	 * @method \int requireQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat resetQty()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat unsetQty()
	 * @method \int fillQty()
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
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat wakeUp($data)
	 */
	class EO_TreatmentByHourStat {
		/* @var \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentByHourStatTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentByHourStatTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity {
	/**
	 * EO_TreatmentByHourStat_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \Bitrix\Main\Type\DateTime[] getDateList()
	 * @method \int[] getOpenLineIdList()
	 * @method \string[] getSourceIdList()
	 * @method \int[] getOperatorIdList()
	 * @method \int[] getQtyList()
	 * @method \int[] fillQty()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat $object)
	 * @method bool has(\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_TreatmentByHourStat_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentByHourStatTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentByHourStatTable';
	}
}
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TreatmentByHourStat_Result exec()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat fetchObject()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TreatmentByHourStat_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat fetchObject()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat_Collection fetchCollection()
	 */
	class EO_TreatmentByHourStat_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat_Collection wakeUpCollection($rows)
	 */
	class EO_TreatmentByHourStat_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\ConfigTable:imopenlines/lib/model/config.php:7a9a132ebd5a634fbb3d742b495d3ace */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Config
	 * @see \Bitrix\ImOpenLines\Model\ConfigTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \boolean getActive()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetActive()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetActive()
	 * @method \boolean fillActive()
	 * @method \string getLineName()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setLineName(\string|\Bitrix\Main\DB\SqlExpression $lineName)
	 * @method bool hasLineName()
	 * @method bool isLineNameFilled()
	 * @method bool isLineNameChanged()
	 * @method \string remindActualLineName()
	 * @method \string requireLineName()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetLineName()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetLineName()
	 * @method \string fillLineName()
	 * @method \boolean getCrm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCrm(\boolean|\Bitrix\Main\DB\SqlExpression $crm)
	 * @method bool hasCrm()
	 * @method bool isCrmFilled()
	 * @method bool isCrmChanged()
	 * @method \boolean remindActualCrm()
	 * @method \boolean requireCrm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCrm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCrm()
	 * @method \boolean fillCrm()
	 * @method \string getCrmCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCrmCreate(\string|\Bitrix\Main\DB\SqlExpression $crmCreate)
	 * @method bool hasCrmCreate()
	 * @method bool isCrmCreateFilled()
	 * @method bool isCrmCreateChanged()
	 * @method \string remindActualCrmCreate()
	 * @method \string requireCrmCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCrmCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCrmCreate()
	 * @method \string fillCrmCreate()
	 * @method \string getCrmCreateSecond()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCrmCreateSecond(\string|\Bitrix\Main\DB\SqlExpression $crmCreateSecond)
	 * @method bool hasCrmCreateSecond()
	 * @method bool isCrmCreateSecondFilled()
	 * @method bool isCrmCreateSecondChanged()
	 * @method \string remindActualCrmCreateSecond()
	 * @method \string requireCrmCreateSecond()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCrmCreateSecond()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCrmCreateSecond()
	 * @method \string fillCrmCreateSecond()
	 * @method \string getCrmCreateThird()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCrmCreateThird(\string|\Bitrix\Main\DB\SqlExpression $crmCreateThird)
	 * @method bool hasCrmCreateThird()
	 * @method bool isCrmCreateThirdFilled()
	 * @method bool isCrmCreateThirdChanged()
	 * @method \string remindActualCrmCreateThird()
	 * @method \string requireCrmCreateThird()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCrmCreateThird()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCrmCreateThird()
	 * @method \string fillCrmCreateThird()
	 * @method \boolean getCrmForward()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCrmForward(\boolean|\Bitrix\Main\DB\SqlExpression $crmForward)
	 * @method bool hasCrmForward()
	 * @method bool isCrmForwardFilled()
	 * @method bool isCrmForwardChanged()
	 * @method \boolean remindActualCrmForward()
	 * @method \boolean requireCrmForward()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCrmForward()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCrmForward()
	 * @method \boolean fillCrmForward()
	 * @method \boolean getCrmChatTracker()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCrmChatTracker(\boolean|\Bitrix\Main\DB\SqlExpression $crmChatTracker)
	 * @method bool hasCrmChatTracker()
	 * @method bool isCrmChatTrackerFilled()
	 * @method bool isCrmChatTrackerChanged()
	 * @method \boolean remindActualCrmChatTracker()
	 * @method \boolean requireCrmChatTracker()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCrmChatTracker()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCrmChatTracker()
	 * @method \boolean fillCrmChatTracker()
	 * @method \boolean getCrmTransferChange()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCrmTransferChange(\boolean|\Bitrix\Main\DB\SqlExpression $crmTransferChange)
	 * @method bool hasCrmTransferChange()
	 * @method bool isCrmTransferChangeFilled()
	 * @method bool isCrmTransferChangeChanged()
	 * @method \boolean remindActualCrmTransferChange()
	 * @method \boolean requireCrmTransferChange()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCrmTransferChange()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCrmTransferChange()
	 * @method \boolean fillCrmTransferChange()
	 * @method \string getCrmSource()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCrmSource(\string|\Bitrix\Main\DB\SqlExpression $crmSource)
	 * @method bool hasCrmSource()
	 * @method bool isCrmSourceFilled()
	 * @method bool isCrmSourceChanged()
	 * @method \string remindActualCrmSource()
	 * @method \string requireCrmSource()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCrmSource()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCrmSource()
	 * @method \string fillCrmSource()
	 * @method \int getQueueTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setQueueTime(\int|\Bitrix\Main\DB\SqlExpression $queueTime)
	 * @method bool hasQueueTime()
	 * @method bool isQueueTimeFilled()
	 * @method bool isQueueTimeChanged()
	 * @method \int remindActualQueueTime()
	 * @method \int requireQueueTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetQueueTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetQueueTime()
	 * @method \int fillQueueTime()
	 * @method \int getNoAnswerTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setNoAnswerTime(\int|\Bitrix\Main\DB\SqlExpression $noAnswerTime)
	 * @method bool hasNoAnswerTime()
	 * @method bool isNoAnswerTimeFilled()
	 * @method bool isNoAnswerTimeChanged()
	 * @method \int remindActualNoAnswerTime()
	 * @method \int requireNoAnswerTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetNoAnswerTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetNoAnswerTime()
	 * @method \int fillNoAnswerTime()
	 * @method \string getQueueType()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setQueueType(\string|\Bitrix\Main\DB\SqlExpression $queueType)
	 * @method bool hasQueueType()
	 * @method bool isQueueTypeFilled()
	 * @method bool isQueueTypeChanged()
	 * @method \string remindActualQueueType()
	 * @method \string requireQueueType()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetQueueType()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetQueueType()
	 * @method \string fillQueueType()
	 * @method \boolean getCheckAvailable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCheckAvailable(\boolean|\Bitrix\Main\DB\SqlExpression $checkAvailable)
	 * @method bool hasCheckAvailable()
	 * @method bool isCheckAvailableFilled()
	 * @method bool isCheckAvailableChanged()
	 * @method \boolean remindActualCheckAvailable()
	 * @method \boolean requireCheckAvailable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCheckAvailable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCheckAvailable()
	 * @method \boolean fillCheckAvailable()
	 * @method \boolean getWatchTyping()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWatchTyping(\boolean|\Bitrix\Main\DB\SqlExpression $watchTyping)
	 * @method bool hasWatchTyping()
	 * @method bool isWatchTypingFilled()
	 * @method bool isWatchTypingChanged()
	 * @method \boolean remindActualWatchTyping()
	 * @method \boolean requireWatchTyping()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWatchTyping()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWatchTyping()
	 * @method \boolean fillWatchTyping()
	 * @method \boolean getWelcomeBotEnable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWelcomeBotEnable(\boolean|\Bitrix\Main\DB\SqlExpression $welcomeBotEnable)
	 * @method bool hasWelcomeBotEnable()
	 * @method bool isWelcomeBotEnableFilled()
	 * @method bool isWelcomeBotEnableChanged()
	 * @method \boolean remindActualWelcomeBotEnable()
	 * @method \boolean requireWelcomeBotEnable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWelcomeBotEnable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWelcomeBotEnable()
	 * @method \boolean fillWelcomeBotEnable()
	 * @method \boolean getWelcomeMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWelcomeMessage(\boolean|\Bitrix\Main\DB\SqlExpression $welcomeMessage)
	 * @method bool hasWelcomeMessage()
	 * @method bool isWelcomeMessageFilled()
	 * @method bool isWelcomeMessageChanged()
	 * @method \boolean remindActualWelcomeMessage()
	 * @method \boolean requireWelcomeMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWelcomeMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWelcomeMessage()
	 * @method \boolean fillWelcomeMessage()
	 * @method \string getWelcomeMessageText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWelcomeMessageText(\string|\Bitrix\Main\DB\SqlExpression $welcomeMessageText)
	 * @method bool hasWelcomeMessageText()
	 * @method bool isWelcomeMessageTextFilled()
	 * @method bool isWelcomeMessageTextChanged()
	 * @method \string remindActualWelcomeMessageText()
	 * @method \string requireWelcomeMessageText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWelcomeMessageText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWelcomeMessageText()
	 * @method \string fillWelcomeMessageText()
	 * @method \boolean getVoteMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteMessage(\boolean|\Bitrix\Main\DB\SqlExpression $voteMessage)
	 * @method bool hasVoteMessage()
	 * @method bool isVoteMessageFilled()
	 * @method bool isVoteMessageChanged()
	 * @method \boolean remindActualVoteMessage()
	 * @method \boolean requireVoteMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteMessage()
	 * @method \boolean fillVoteMessage()
	 * @method \int getVoteTimeLimit()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteTimeLimit(\int|\Bitrix\Main\DB\SqlExpression $voteTimeLimit)
	 * @method bool hasVoteTimeLimit()
	 * @method bool isVoteTimeLimitFilled()
	 * @method bool isVoteTimeLimitChanged()
	 * @method \int remindActualVoteTimeLimit()
	 * @method \int requireVoteTimeLimit()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteTimeLimit()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteTimeLimit()
	 * @method \int fillVoteTimeLimit()
	 * @method \boolean getVoteBeforeFinish()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteBeforeFinish(\boolean|\Bitrix\Main\DB\SqlExpression $voteBeforeFinish)
	 * @method bool hasVoteBeforeFinish()
	 * @method bool isVoteBeforeFinishFilled()
	 * @method bool isVoteBeforeFinishChanged()
	 * @method \boolean remindActualVoteBeforeFinish()
	 * @method \boolean requireVoteBeforeFinish()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteBeforeFinish()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteBeforeFinish()
	 * @method \boolean fillVoteBeforeFinish()
	 * @method \boolean getVoteClosingDelay()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteClosingDelay(\boolean|\Bitrix\Main\DB\SqlExpression $voteClosingDelay)
	 * @method bool hasVoteClosingDelay()
	 * @method bool isVoteClosingDelayFilled()
	 * @method bool isVoteClosingDelayChanged()
	 * @method \boolean remindActualVoteClosingDelay()
	 * @method \boolean requireVoteClosingDelay()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteClosingDelay()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteClosingDelay()
	 * @method \boolean fillVoteClosingDelay()
	 * @method \string getVoteMessage1Text()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteMessage1Text(\string|\Bitrix\Main\DB\SqlExpression $voteMessage1Text)
	 * @method bool hasVoteMessage1Text()
	 * @method bool isVoteMessage1TextFilled()
	 * @method bool isVoteMessage1TextChanged()
	 * @method \string remindActualVoteMessage1Text()
	 * @method \string requireVoteMessage1Text()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteMessage1Text()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteMessage1Text()
	 * @method \string fillVoteMessage1Text()
	 * @method \string getVoteMessage1Like()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteMessage1Like(\string|\Bitrix\Main\DB\SqlExpression $voteMessage1Like)
	 * @method bool hasVoteMessage1Like()
	 * @method bool isVoteMessage1LikeFilled()
	 * @method bool isVoteMessage1LikeChanged()
	 * @method \string remindActualVoteMessage1Like()
	 * @method \string requireVoteMessage1Like()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteMessage1Like()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteMessage1Like()
	 * @method \string fillVoteMessage1Like()
	 * @method \string getVoteMessage1Dislike()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteMessage1Dislike(\string|\Bitrix\Main\DB\SqlExpression $voteMessage1Dislike)
	 * @method bool hasVoteMessage1Dislike()
	 * @method bool isVoteMessage1DislikeFilled()
	 * @method bool isVoteMessage1DislikeChanged()
	 * @method \string remindActualVoteMessage1Dislike()
	 * @method \string requireVoteMessage1Dislike()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteMessage1Dislike()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteMessage1Dislike()
	 * @method \string fillVoteMessage1Dislike()
	 * @method \string getVoteMessage2Text()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteMessage2Text(\string|\Bitrix\Main\DB\SqlExpression $voteMessage2Text)
	 * @method bool hasVoteMessage2Text()
	 * @method bool isVoteMessage2TextFilled()
	 * @method bool isVoteMessage2TextChanged()
	 * @method \string remindActualVoteMessage2Text()
	 * @method \string requireVoteMessage2Text()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteMessage2Text()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteMessage2Text()
	 * @method \string fillVoteMessage2Text()
	 * @method \string getVoteMessage2Like()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteMessage2Like(\string|\Bitrix\Main\DB\SqlExpression $voteMessage2Like)
	 * @method bool hasVoteMessage2Like()
	 * @method bool isVoteMessage2LikeFilled()
	 * @method bool isVoteMessage2LikeChanged()
	 * @method \string remindActualVoteMessage2Like()
	 * @method \string requireVoteMessage2Like()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteMessage2Like()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteMessage2Like()
	 * @method \string fillVoteMessage2Like()
	 * @method \string getVoteMessage2Dislike()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setVoteMessage2Dislike(\string|\Bitrix\Main\DB\SqlExpression $voteMessage2Dislike)
	 * @method bool hasVoteMessage2Dislike()
	 * @method bool isVoteMessage2DislikeFilled()
	 * @method bool isVoteMessage2DislikeChanged()
	 * @method \string remindActualVoteMessage2Dislike()
	 * @method \string requireVoteMessage2Dislike()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetVoteMessage2Dislike()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetVoteMessage2Dislike()
	 * @method \string fillVoteMessage2Dislike()
	 * @method \boolean getAgreementMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setAgreementMessage(\boolean|\Bitrix\Main\DB\SqlExpression $agreementMessage)
	 * @method bool hasAgreementMessage()
	 * @method bool isAgreementMessageFilled()
	 * @method bool isAgreementMessageChanged()
	 * @method \boolean remindActualAgreementMessage()
	 * @method \boolean requireAgreementMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetAgreementMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetAgreementMessage()
	 * @method \boolean fillAgreementMessage()
	 * @method \int getAgreementId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setAgreementId(\int|\Bitrix\Main\DB\SqlExpression $agreementId)
	 * @method bool hasAgreementId()
	 * @method bool isAgreementIdFilled()
	 * @method bool isAgreementIdChanged()
	 * @method \int remindActualAgreementId()
	 * @method \int requireAgreementId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetAgreementId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetAgreementId()
	 * @method \int fillAgreementId()
	 * @method \boolean getCategoryEnable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCategoryEnable(\boolean|\Bitrix\Main\DB\SqlExpression $categoryEnable)
	 * @method bool hasCategoryEnable()
	 * @method bool isCategoryEnableFilled()
	 * @method bool isCategoryEnableChanged()
	 * @method \boolean remindActualCategoryEnable()
	 * @method \boolean requireCategoryEnable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCategoryEnable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCategoryEnable()
	 * @method \boolean fillCategoryEnable()
	 * @method \int getCategoryId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCategoryId(\int|\Bitrix\Main\DB\SqlExpression $categoryId)
	 * @method bool hasCategoryId()
	 * @method bool isCategoryIdFilled()
	 * @method bool isCategoryIdChanged()
	 * @method \int remindActualCategoryId()
	 * @method \int requireCategoryId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCategoryId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCategoryId()
	 * @method \int fillCategoryId()
	 * @method \string getWelcomeBotJoin()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWelcomeBotJoin(\string|\Bitrix\Main\DB\SqlExpression $welcomeBotJoin)
	 * @method bool hasWelcomeBotJoin()
	 * @method bool isWelcomeBotJoinFilled()
	 * @method bool isWelcomeBotJoinChanged()
	 * @method \string remindActualWelcomeBotJoin()
	 * @method \string requireWelcomeBotJoin()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWelcomeBotJoin()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWelcomeBotJoin()
	 * @method \string fillWelcomeBotJoin()
	 * @method \int getWelcomeBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWelcomeBotId(\int|\Bitrix\Main\DB\SqlExpression $welcomeBotId)
	 * @method bool hasWelcomeBotId()
	 * @method bool isWelcomeBotIdFilled()
	 * @method bool isWelcomeBotIdChanged()
	 * @method \int remindActualWelcomeBotId()
	 * @method \int requireWelcomeBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWelcomeBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWelcomeBotId()
	 * @method \int fillWelcomeBotId()
	 * @method \int getWelcomeBotTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWelcomeBotTime(\int|\Bitrix\Main\DB\SqlExpression $welcomeBotTime)
	 * @method bool hasWelcomeBotTime()
	 * @method bool isWelcomeBotTimeFilled()
	 * @method bool isWelcomeBotTimeChanged()
	 * @method \int remindActualWelcomeBotTime()
	 * @method \int requireWelcomeBotTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWelcomeBotTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWelcomeBotTime()
	 * @method \int fillWelcomeBotTime()
	 * @method \string getWelcomeBotLeft()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWelcomeBotLeft(\string|\Bitrix\Main\DB\SqlExpression $welcomeBotLeft)
	 * @method bool hasWelcomeBotLeft()
	 * @method bool isWelcomeBotLeftFilled()
	 * @method bool isWelcomeBotLeftChanged()
	 * @method \string remindActualWelcomeBotLeft()
	 * @method \string requireWelcomeBotLeft()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWelcomeBotLeft()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWelcomeBotLeft()
	 * @method \string fillWelcomeBotLeft()
	 * @method \string getNoAnswerRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setNoAnswerRule(\string|\Bitrix\Main\DB\SqlExpression $noAnswerRule)
	 * @method bool hasNoAnswerRule()
	 * @method bool isNoAnswerRuleFilled()
	 * @method bool isNoAnswerRuleChanged()
	 * @method \string remindActualNoAnswerRule()
	 * @method \string requireNoAnswerRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetNoAnswerRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetNoAnswerRule()
	 * @method \string fillNoAnswerRule()
	 * @method \int getNoAnswerFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setNoAnswerFormId(\int|\Bitrix\Main\DB\SqlExpression $noAnswerFormId)
	 * @method bool hasNoAnswerFormId()
	 * @method bool isNoAnswerFormIdFilled()
	 * @method bool isNoAnswerFormIdChanged()
	 * @method \int remindActualNoAnswerFormId()
	 * @method \int requireNoAnswerFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetNoAnswerFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetNoAnswerFormId()
	 * @method \int fillNoAnswerFormId()
	 * @method \int getNoAnswerBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setNoAnswerBotId(\int|\Bitrix\Main\DB\SqlExpression $noAnswerBotId)
	 * @method bool hasNoAnswerBotId()
	 * @method bool isNoAnswerBotIdFilled()
	 * @method bool isNoAnswerBotIdChanged()
	 * @method \int remindActualNoAnswerBotId()
	 * @method \int requireNoAnswerBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetNoAnswerBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetNoAnswerBotId()
	 * @method \int fillNoAnswerBotId()
	 * @method \string getNoAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setNoAnswerText(\string|\Bitrix\Main\DB\SqlExpression $noAnswerText)
	 * @method bool hasNoAnswerText()
	 * @method bool isNoAnswerTextFilled()
	 * @method bool isNoAnswerTextChanged()
	 * @method \string remindActualNoAnswerText()
	 * @method \string requireNoAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetNoAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetNoAnswerText()
	 * @method \string fillNoAnswerText()
	 * @method \boolean getWorktimeEnable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeEnable(\boolean|\Bitrix\Main\DB\SqlExpression $worktimeEnable)
	 * @method bool hasWorktimeEnable()
	 * @method bool isWorktimeEnableFilled()
	 * @method bool isWorktimeEnableChanged()
	 * @method \boolean remindActualWorktimeEnable()
	 * @method \boolean requireWorktimeEnable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeEnable()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeEnable()
	 * @method \boolean fillWorktimeEnable()
	 * @method \string getWorktimeFrom()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeFrom(\string|\Bitrix\Main\DB\SqlExpression $worktimeFrom)
	 * @method bool hasWorktimeFrom()
	 * @method bool isWorktimeFromFilled()
	 * @method bool isWorktimeFromChanged()
	 * @method \string remindActualWorktimeFrom()
	 * @method \string requireWorktimeFrom()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeFrom()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeFrom()
	 * @method \string fillWorktimeFrom()
	 * @method \string getWorktimeTo()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeTo(\string|\Bitrix\Main\DB\SqlExpression $worktimeTo)
	 * @method bool hasWorktimeTo()
	 * @method bool isWorktimeToFilled()
	 * @method bool isWorktimeToChanged()
	 * @method \string remindActualWorktimeTo()
	 * @method \string requireWorktimeTo()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeTo()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeTo()
	 * @method \string fillWorktimeTo()
	 * @method \string getWorktimeTimezone()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeTimezone(\string|\Bitrix\Main\DB\SqlExpression $worktimeTimezone)
	 * @method bool hasWorktimeTimezone()
	 * @method bool isWorktimeTimezoneFilled()
	 * @method bool isWorktimeTimezoneChanged()
	 * @method \string remindActualWorktimeTimezone()
	 * @method \string requireWorktimeTimezone()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeTimezone()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeTimezone()
	 * @method \string fillWorktimeTimezone()
	 * @method \string getWorktimeHolidays()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeHolidays(\string|\Bitrix\Main\DB\SqlExpression $worktimeHolidays)
	 * @method bool hasWorktimeHolidays()
	 * @method bool isWorktimeHolidaysFilled()
	 * @method bool isWorktimeHolidaysChanged()
	 * @method \string remindActualWorktimeHolidays()
	 * @method \string requireWorktimeHolidays()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeHolidays()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeHolidays()
	 * @method \string fillWorktimeHolidays()
	 * @method \string getWorktimeDayoff()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeDayoff(\string|\Bitrix\Main\DB\SqlExpression $worktimeDayoff)
	 * @method bool hasWorktimeDayoff()
	 * @method bool isWorktimeDayoffFilled()
	 * @method bool isWorktimeDayoffChanged()
	 * @method \string remindActualWorktimeDayoff()
	 * @method \string requireWorktimeDayoff()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeDayoff()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeDayoff()
	 * @method \string fillWorktimeDayoff()
	 * @method \string getWorktimeDayoffRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeDayoffRule(\string|\Bitrix\Main\DB\SqlExpression $worktimeDayoffRule)
	 * @method bool hasWorktimeDayoffRule()
	 * @method bool isWorktimeDayoffRuleFilled()
	 * @method bool isWorktimeDayoffRuleChanged()
	 * @method \string remindActualWorktimeDayoffRule()
	 * @method \string requireWorktimeDayoffRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeDayoffRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeDayoffRule()
	 * @method \string fillWorktimeDayoffRule()
	 * @method \int getWorktimeDayoffFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeDayoffFormId(\int|\Bitrix\Main\DB\SqlExpression $worktimeDayoffFormId)
	 * @method bool hasWorktimeDayoffFormId()
	 * @method bool isWorktimeDayoffFormIdFilled()
	 * @method bool isWorktimeDayoffFormIdChanged()
	 * @method \int remindActualWorktimeDayoffFormId()
	 * @method \int requireWorktimeDayoffFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeDayoffFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeDayoffFormId()
	 * @method \int fillWorktimeDayoffFormId()
	 * @method \int getWorktimeDayoffBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeDayoffBotId(\int|\Bitrix\Main\DB\SqlExpression $worktimeDayoffBotId)
	 * @method bool hasWorktimeDayoffBotId()
	 * @method bool isWorktimeDayoffBotIdFilled()
	 * @method bool isWorktimeDayoffBotIdChanged()
	 * @method \int remindActualWorktimeDayoffBotId()
	 * @method \int requireWorktimeDayoffBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeDayoffBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeDayoffBotId()
	 * @method \int fillWorktimeDayoffBotId()
	 * @method \string getWorktimeDayoffText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setWorktimeDayoffText(\string|\Bitrix\Main\DB\SqlExpression $worktimeDayoffText)
	 * @method bool hasWorktimeDayoffText()
	 * @method bool isWorktimeDayoffTextFilled()
	 * @method bool isWorktimeDayoffTextChanged()
	 * @method \string remindActualWorktimeDayoffText()
	 * @method \string requireWorktimeDayoffText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetWorktimeDayoffText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetWorktimeDayoffText()
	 * @method \string fillWorktimeDayoffText()
	 * @method \string getCloseRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCloseRule(\string|\Bitrix\Main\DB\SqlExpression $closeRule)
	 * @method bool hasCloseRule()
	 * @method bool isCloseRuleFilled()
	 * @method bool isCloseRuleChanged()
	 * @method \string remindActualCloseRule()
	 * @method \string requireCloseRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCloseRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCloseRule()
	 * @method \string fillCloseRule()
	 * @method \int getCloseFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCloseFormId(\int|\Bitrix\Main\DB\SqlExpression $closeFormId)
	 * @method bool hasCloseFormId()
	 * @method bool isCloseFormIdFilled()
	 * @method bool isCloseFormIdChanged()
	 * @method \int remindActualCloseFormId()
	 * @method \int requireCloseFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCloseFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCloseFormId()
	 * @method \int fillCloseFormId()
	 * @method \int getCloseBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCloseBotId(\int|\Bitrix\Main\DB\SqlExpression $closeBotId)
	 * @method bool hasCloseBotId()
	 * @method bool isCloseBotIdFilled()
	 * @method bool isCloseBotIdChanged()
	 * @method \int remindActualCloseBotId()
	 * @method \int requireCloseBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCloseBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCloseBotId()
	 * @method \int fillCloseBotId()
	 * @method \string getCloseText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setCloseText(\string|\Bitrix\Main\DB\SqlExpression $closeText)
	 * @method bool hasCloseText()
	 * @method bool isCloseTextFilled()
	 * @method bool isCloseTextChanged()
	 * @method \string remindActualCloseText()
	 * @method \string requireCloseText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetCloseText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetCloseText()
	 * @method \string fillCloseText()
	 * @method \int getFullCloseTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setFullCloseTime(\int|\Bitrix\Main\DB\SqlExpression $fullCloseTime)
	 * @method bool hasFullCloseTime()
	 * @method bool isFullCloseTimeFilled()
	 * @method bool isFullCloseTimeChanged()
	 * @method \int remindActualFullCloseTime()
	 * @method \int requireFullCloseTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetFullCloseTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetFullCloseTime()
	 * @method \int fillFullCloseTime()
	 * @method \string getAutoCloseRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setAutoCloseRule(\string|\Bitrix\Main\DB\SqlExpression $autoCloseRule)
	 * @method bool hasAutoCloseRule()
	 * @method bool isAutoCloseRuleFilled()
	 * @method bool isAutoCloseRuleChanged()
	 * @method \string remindActualAutoCloseRule()
	 * @method \string requireAutoCloseRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetAutoCloseRule()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetAutoCloseRule()
	 * @method \string fillAutoCloseRule()
	 * @method \int getAutoCloseFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setAutoCloseFormId(\int|\Bitrix\Main\DB\SqlExpression $autoCloseFormId)
	 * @method bool hasAutoCloseFormId()
	 * @method bool isAutoCloseFormIdFilled()
	 * @method bool isAutoCloseFormIdChanged()
	 * @method \int remindActualAutoCloseFormId()
	 * @method \int requireAutoCloseFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetAutoCloseFormId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetAutoCloseFormId()
	 * @method \int fillAutoCloseFormId()
	 * @method \int getAutoCloseBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setAutoCloseBotId(\int|\Bitrix\Main\DB\SqlExpression $autoCloseBotId)
	 * @method bool hasAutoCloseBotId()
	 * @method bool isAutoCloseBotIdFilled()
	 * @method bool isAutoCloseBotIdChanged()
	 * @method \int remindActualAutoCloseBotId()
	 * @method \int requireAutoCloseBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetAutoCloseBotId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetAutoCloseBotId()
	 * @method \int fillAutoCloseBotId()
	 * @method \int getAutoCloseTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setAutoCloseTime(\int|\Bitrix\Main\DB\SqlExpression $autoCloseTime)
	 * @method bool hasAutoCloseTime()
	 * @method bool isAutoCloseTimeFilled()
	 * @method bool isAutoCloseTimeChanged()
	 * @method \int remindActualAutoCloseTime()
	 * @method \int requireAutoCloseTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetAutoCloseTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetAutoCloseTime()
	 * @method \int fillAutoCloseTime()
	 * @method \string getAutoCloseText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setAutoCloseText(\string|\Bitrix\Main\DB\SqlExpression $autoCloseText)
	 * @method bool hasAutoCloseText()
	 * @method bool isAutoCloseTextFilled()
	 * @method bool isAutoCloseTextChanged()
	 * @method \string remindActualAutoCloseText()
	 * @method \string requireAutoCloseText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetAutoCloseText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetAutoCloseText()
	 * @method \string fillAutoCloseText()
	 * @method \int getAutoExpireTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setAutoExpireTime(\int|\Bitrix\Main\DB\SqlExpression $autoExpireTime)
	 * @method bool hasAutoExpireTime()
	 * @method bool isAutoExpireTimeFilled()
	 * @method bool isAutoExpireTimeChanged()
	 * @method \int remindActualAutoExpireTime()
	 * @method \int requireAutoExpireTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetAutoExpireTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetAutoExpireTime()
	 * @method \int fillAutoExpireTime()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetDateCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetDateModify()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
	 * @method \int getModifyUserId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setModifyUserId(\int|\Bitrix\Main\DB\SqlExpression $modifyUserId)
	 * @method bool hasModifyUserId()
	 * @method bool isModifyUserIdFilled()
	 * @method bool isModifyUserIdChanged()
	 * @method \int remindActualModifyUserId()
	 * @method \int requireModifyUserId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetModifyUserId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetModifyUserId()
	 * @method \int fillModifyUserId()
	 * @method \boolean getTemporary()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setTemporary(\boolean|\Bitrix\Main\DB\SqlExpression $temporary)
	 * @method bool hasTemporary()
	 * @method bool isTemporaryFilled()
	 * @method bool isTemporaryChanged()
	 * @method \boolean remindActualTemporary()
	 * @method \boolean requireTemporary()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetTemporary()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetTemporary()
	 * @method \boolean fillTemporary()
	 * @method \string getXmlId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetXmlId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \string getLanguageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setLanguageId(\string|\Bitrix\Main\DB\SqlExpression $languageId)
	 * @method bool hasLanguageId()
	 * @method bool isLanguageIdFilled()
	 * @method bool isLanguageIdChanged()
	 * @method \string remindActualLanguageId()
	 * @method \string requireLanguageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetLanguageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetLanguageId()
	 * @method \string fillLanguageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic getStatistic()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic remindActualStatistic()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic requireStatistic()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setStatistic(\Bitrix\ImOpenLines\Model\EO_ConfigStatistic $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetStatistic()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetStatistic()
	 * @method bool hasStatistic()
	 * @method bool isStatisticFilled()
	 * @method bool isStatisticChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic fillStatistic()
	 * @method \int getQuickAnswersIblockId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setQuickAnswersIblockId(\int|\Bitrix\Main\DB\SqlExpression $quickAnswersIblockId)
	 * @method bool hasQuickAnswersIblockId()
	 * @method bool isQuickAnswersIblockIdFilled()
	 * @method bool isQuickAnswersIblockIdChanged()
	 * @method \int remindActualQuickAnswersIblockId()
	 * @method \int requireQuickAnswersIblockId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetQuickAnswersIblockId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetQuickAnswersIblockId()
	 * @method \int fillQuickAnswersIblockId()
	 * @method \int getSessionPriority()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setSessionPriority(\int|\Bitrix\Main\DB\SqlExpression $sessionPriority)
	 * @method bool hasSessionPriority()
	 * @method bool isSessionPriorityFilled()
	 * @method bool isSessionPriorityChanged()
	 * @method \int remindActualSessionPriority()
	 * @method \int requireSessionPriority()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetSessionPriority()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetSessionPriority()
	 * @method \int fillSessionPriority()
	 * @method \string getTypeMaxChat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setTypeMaxChat(\string|\Bitrix\Main\DB\SqlExpression $typeMaxChat)
	 * @method bool hasTypeMaxChat()
	 * @method bool isTypeMaxChatFilled()
	 * @method bool isTypeMaxChatChanged()
	 * @method \string remindActualTypeMaxChat()
	 * @method \string requireTypeMaxChat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetTypeMaxChat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetTypeMaxChat()
	 * @method \string fillTypeMaxChat()
	 * @method \int getMaxChat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setMaxChat(\int|\Bitrix\Main\DB\SqlExpression $maxChat)
	 * @method bool hasMaxChat()
	 * @method bool isMaxChatFilled()
	 * @method bool isMaxChatChanged()
	 * @method \int remindActualMaxChat()
	 * @method \int requireMaxChat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetMaxChat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetMaxChat()
	 * @method \int fillMaxChat()
	 * @method \string getOperatorData()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setOperatorData(\string|\Bitrix\Main\DB\SqlExpression $operatorData)
	 * @method bool hasOperatorData()
	 * @method bool isOperatorDataFilled()
	 * @method bool isOperatorDataChanged()
	 * @method \string remindActualOperatorData()
	 * @method \string requireOperatorData()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetOperatorData()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetOperatorData()
	 * @method \string fillOperatorData()
	 * @method \string getDefaultOperatorData()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setDefaultOperatorData(\string|\Bitrix\Main\DB\SqlExpression $defaultOperatorData)
	 * @method bool hasDefaultOperatorData()
	 * @method bool isDefaultOperatorDataFilled()
	 * @method bool isDefaultOperatorDataChanged()
	 * @method \string remindActualDefaultOperatorData()
	 * @method \string requireDefaultOperatorData()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetDefaultOperatorData()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetDefaultOperatorData()
	 * @method \string fillDefaultOperatorData()
	 * @method \int getKpiFirstAnswerTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setKpiFirstAnswerTime(\int|\Bitrix\Main\DB\SqlExpression $kpiFirstAnswerTime)
	 * @method bool hasKpiFirstAnswerTime()
	 * @method bool isKpiFirstAnswerTimeFilled()
	 * @method bool isKpiFirstAnswerTimeChanged()
	 * @method \int remindActualKpiFirstAnswerTime()
	 * @method \int requireKpiFirstAnswerTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetKpiFirstAnswerTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetKpiFirstAnswerTime()
	 * @method \int fillKpiFirstAnswerTime()
	 * @method \boolean getKpiFirstAnswerAlert()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setKpiFirstAnswerAlert(\boolean|\Bitrix\Main\DB\SqlExpression $kpiFirstAnswerAlert)
	 * @method bool hasKpiFirstAnswerAlert()
	 * @method bool isKpiFirstAnswerAlertFilled()
	 * @method bool isKpiFirstAnswerAlertChanged()
	 * @method \boolean remindActualKpiFirstAnswerAlert()
	 * @method \boolean requireKpiFirstAnswerAlert()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetKpiFirstAnswerAlert()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetKpiFirstAnswerAlert()
	 * @method \boolean fillKpiFirstAnswerAlert()
	 * @method \string getKpiFirstAnswerList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setKpiFirstAnswerList(\string|\Bitrix\Main\DB\SqlExpression $kpiFirstAnswerList)
	 * @method bool hasKpiFirstAnswerList()
	 * @method bool isKpiFirstAnswerListFilled()
	 * @method bool isKpiFirstAnswerListChanged()
	 * @method \string remindActualKpiFirstAnswerList()
	 * @method \string requireKpiFirstAnswerList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetKpiFirstAnswerList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetKpiFirstAnswerList()
	 * @method \string fillKpiFirstAnswerList()
	 * @method \string getKpiFirstAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setKpiFirstAnswerText(\string|\Bitrix\Main\DB\SqlExpression $kpiFirstAnswerText)
	 * @method bool hasKpiFirstAnswerText()
	 * @method bool isKpiFirstAnswerTextFilled()
	 * @method bool isKpiFirstAnswerTextChanged()
	 * @method \string remindActualKpiFirstAnswerText()
	 * @method \string requireKpiFirstAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetKpiFirstAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetKpiFirstAnswerText()
	 * @method \string fillKpiFirstAnswerText()
	 * @method \int getKpiFurtherAnswerTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setKpiFurtherAnswerTime(\int|\Bitrix\Main\DB\SqlExpression $kpiFurtherAnswerTime)
	 * @method bool hasKpiFurtherAnswerTime()
	 * @method bool isKpiFurtherAnswerTimeFilled()
	 * @method bool isKpiFurtherAnswerTimeChanged()
	 * @method \int remindActualKpiFurtherAnswerTime()
	 * @method \int requireKpiFurtherAnswerTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetKpiFurtherAnswerTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetKpiFurtherAnswerTime()
	 * @method \int fillKpiFurtherAnswerTime()
	 * @method \boolean getKpiFurtherAnswerAlert()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setKpiFurtherAnswerAlert(\boolean|\Bitrix\Main\DB\SqlExpression $kpiFurtherAnswerAlert)
	 * @method bool hasKpiFurtherAnswerAlert()
	 * @method bool isKpiFurtherAnswerAlertFilled()
	 * @method bool isKpiFurtherAnswerAlertChanged()
	 * @method \boolean remindActualKpiFurtherAnswerAlert()
	 * @method \boolean requireKpiFurtherAnswerAlert()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetKpiFurtherAnswerAlert()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetKpiFurtherAnswerAlert()
	 * @method \boolean fillKpiFurtherAnswerAlert()
	 * @method \string getKpiFurtherAnswerList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setKpiFurtherAnswerList(\string|\Bitrix\Main\DB\SqlExpression $kpiFurtherAnswerList)
	 * @method bool hasKpiFurtherAnswerList()
	 * @method bool isKpiFurtherAnswerListFilled()
	 * @method bool isKpiFurtherAnswerListChanged()
	 * @method \string remindActualKpiFurtherAnswerList()
	 * @method \string requireKpiFurtherAnswerList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetKpiFurtherAnswerList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetKpiFurtherAnswerList()
	 * @method \string fillKpiFurtherAnswerList()
	 * @method \string getKpiFurtherAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setKpiFurtherAnswerText(\string|\Bitrix\Main\DB\SqlExpression $kpiFurtherAnswerText)
	 * @method bool hasKpiFurtherAnswerText()
	 * @method bool isKpiFurtherAnswerTextFilled()
	 * @method bool isKpiFurtherAnswerTextChanged()
	 * @method \string remindActualKpiFurtherAnswerText()
	 * @method \string requireKpiFurtherAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetKpiFurtherAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetKpiFurtherAnswerText()
	 * @method \string fillKpiFurtherAnswerText()
	 * @method \boolean getKpiCheckOperatorActivity()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config setKpiCheckOperatorActivity(\boolean|\Bitrix\Main\DB\SqlExpression $kpiCheckOperatorActivity)
	 * @method bool hasKpiCheckOperatorActivity()
	 * @method bool isKpiCheckOperatorActivityFilled()
	 * @method bool isKpiCheckOperatorActivityChanged()
	 * @method \boolean remindActualKpiCheckOperatorActivity()
	 * @method \boolean requireKpiCheckOperatorActivity()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config resetKpiCheckOperatorActivity()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unsetKpiCheckOperatorActivity()
	 * @method \boolean fillKpiCheckOperatorActivity()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_Config set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_Config reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_Config unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_Config wakeUp($data)
	 */
	class EO_Config {
		/* @var \Bitrix\ImOpenLines\Model\ConfigTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\ConfigTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Config_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \string[] getLineNameList()
	 * @method \string[] fillLineName()
	 * @method \boolean[] getCrmList()
	 * @method \boolean[] fillCrm()
	 * @method \string[] getCrmCreateList()
	 * @method \string[] fillCrmCreate()
	 * @method \string[] getCrmCreateSecondList()
	 * @method \string[] fillCrmCreateSecond()
	 * @method \string[] getCrmCreateThirdList()
	 * @method \string[] fillCrmCreateThird()
	 * @method \boolean[] getCrmForwardList()
	 * @method \boolean[] fillCrmForward()
	 * @method \boolean[] getCrmChatTrackerList()
	 * @method \boolean[] fillCrmChatTracker()
	 * @method \boolean[] getCrmTransferChangeList()
	 * @method \boolean[] fillCrmTransferChange()
	 * @method \string[] getCrmSourceList()
	 * @method \string[] fillCrmSource()
	 * @method \int[] getQueueTimeList()
	 * @method \int[] fillQueueTime()
	 * @method \int[] getNoAnswerTimeList()
	 * @method \int[] fillNoAnswerTime()
	 * @method \string[] getQueueTypeList()
	 * @method \string[] fillQueueType()
	 * @method \boolean[] getCheckAvailableList()
	 * @method \boolean[] fillCheckAvailable()
	 * @method \boolean[] getWatchTypingList()
	 * @method \boolean[] fillWatchTyping()
	 * @method \boolean[] getWelcomeBotEnableList()
	 * @method \boolean[] fillWelcomeBotEnable()
	 * @method \boolean[] getWelcomeMessageList()
	 * @method \boolean[] fillWelcomeMessage()
	 * @method \string[] getWelcomeMessageTextList()
	 * @method \string[] fillWelcomeMessageText()
	 * @method \boolean[] getVoteMessageList()
	 * @method \boolean[] fillVoteMessage()
	 * @method \int[] getVoteTimeLimitList()
	 * @method \int[] fillVoteTimeLimit()
	 * @method \boolean[] getVoteBeforeFinishList()
	 * @method \boolean[] fillVoteBeforeFinish()
	 * @method \boolean[] getVoteClosingDelayList()
	 * @method \boolean[] fillVoteClosingDelay()
	 * @method \string[] getVoteMessage1TextList()
	 * @method \string[] fillVoteMessage1Text()
	 * @method \string[] getVoteMessage1LikeList()
	 * @method \string[] fillVoteMessage1Like()
	 * @method \string[] getVoteMessage1DislikeList()
	 * @method \string[] fillVoteMessage1Dislike()
	 * @method \string[] getVoteMessage2TextList()
	 * @method \string[] fillVoteMessage2Text()
	 * @method \string[] getVoteMessage2LikeList()
	 * @method \string[] fillVoteMessage2Like()
	 * @method \string[] getVoteMessage2DislikeList()
	 * @method \string[] fillVoteMessage2Dislike()
	 * @method \boolean[] getAgreementMessageList()
	 * @method \boolean[] fillAgreementMessage()
	 * @method \int[] getAgreementIdList()
	 * @method \int[] fillAgreementId()
	 * @method \boolean[] getCategoryEnableList()
	 * @method \boolean[] fillCategoryEnable()
	 * @method \int[] getCategoryIdList()
	 * @method \int[] fillCategoryId()
	 * @method \string[] getWelcomeBotJoinList()
	 * @method \string[] fillWelcomeBotJoin()
	 * @method \int[] getWelcomeBotIdList()
	 * @method \int[] fillWelcomeBotId()
	 * @method \int[] getWelcomeBotTimeList()
	 * @method \int[] fillWelcomeBotTime()
	 * @method \string[] getWelcomeBotLeftList()
	 * @method \string[] fillWelcomeBotLeft()
	 * @method \string[] getNoAnswerRuleList()
	 * @method \string[] fillNoAnswerRule()
	 * @method \int[] getNoAnswerFormIdList()
	 * @method \int[] fillNoAnswerFormId()
	 * @method \int[] getNoAnswerBotIdList()
	 * @method \int[] fillNoAnswerBotId()
	 * @method \string[] getNoAnswerTextList()
	 * @method \string[] fillNoAnswerText()
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
	 * @method \int[] getWorktimeDayoffFormIdList()
	 * @method \int[] fillWorktimeDayoffFormId()
	 * @method \int[] getWorktimeDayoffBotIdList()
	 * @method \int[] fillWorktimeDayoffBotId()
	 * @method \string[] getWorktimeDayoffTextList()
	 * @method \string[] fillWorktimeDayoffText()
	 * @method \string[] getCloseRuleList()
	 * @method \string[] fillCloseRule()
	 * @method \int[] getCloseFormIdList()
	 * @method \int[] fillCloseFormId()
	 * @method \int[] getCloseBotIdList()
	 * @method \int[] fillCloseBotId()
	 * @method \string[] getCloseTextList()
	 * @method \string[] fillCloseText()
	 * @method \int[] getFullCloseTimeList()
	 * @method \int[] fillFullCloseTime()
	 * @method \string[] getAutoCloseRuleList()
	 * @method \string[] fillAutoCloseRule()
	 * @method \int[] getAutoCloseFormIdList()
	 * @method \int[] fillAutoCloseFormId()
	 * @method \int[] getAutoCloseBotIdList()
	 * @method \int[] fillAutoCloseBotId()
	 * @method \int[] getAutoCloseTimeList()
	 * @method \int[] fillAutoCloseTime()
	 * @method \string[] getAutoCloseTextList()
	 * @method \string[] fillAutoCloseText()
	 * @method \int[] getAutoExpireTimeList()
	 * @method \int[] fillAutoExpireTime()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 * @method \int[] getModifyUserIdList()
	 * @method \int[] fillModifyUserId()
	 * @method \boolean[] getTemporaryList()
	 * @method \boolean[] fillTemporary()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 * @method \string[] getLanguageIdList()
	 * @method \string[] fillLanguageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic[] getStatisticList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config_Collection getStatisticCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic_Collection fillStatistic()
	 * @method \int[] getQuickAnswersIblockIdList()
	 * @method \int[] fillQuickAnswersIblockId()
	 * @method \int[] getSessionPriorityList()
	 * @method \int[] fillSessionPriority()
	 * @method \string[] getTypeMaxChatList()
	 * @method \string[] fillTypeMaxChat()
	 * @method \int[] getMaxChatList()
	 * @method \int[] fillMaxChat()
	 * @method \string[] getOperatorDataList()
	 * @method \string[] fillOperatorData()
	 * @method \string[] getDefaultOperatorDataList()
	 * @method \string[] fillDefaultOperatorData()
	 * @method \int[] getKpiFirstAnswerTimeList()
	 * @method \int[] fillKpiFirstAnswerTime()
	 * @method \boolean[] getKpiFirstAnswerAlertList()
	 * @method \boolean[] fillKpiFirstAnswerAlert()
	 * @method \string[] getKpiFirstAnswerListList()
	 * @method \string[] fillKpiFirstAnswerList()
	 * @method \string[] getKpiFirstAnswerTextList()
	 * @method \string[] fillKpiFirstAnswerText()
	 * @method \int[] getKpiFurtherAnswerTimeList()
	 * @method \int[] fillKpiFurtherAnswerTime()
	 * @method \boolean[] getKpiFurtherAnswerAlertList()
	 * @method \boolean[] fillKpiFurtherAnswerAlert()
	 * @method \string[] getKpiFurtherAnswerListList()
	 * @method \string[] fillKpiFurtherAnswerList()
	 * @method \string[] getKpiFurtherAnswerTextList()
	 * @method \string[] fillKpiFurtherAnswerText()
	 * @method \boolean[] getKpiCheckOperatorActivityList()
	 * @method \boolean[] fillKpiCheckOperatorActivity()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_Config $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_Config $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Config getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Config[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_Config $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_Config_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_Config current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Config_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\ConfigTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\ConfigTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Config_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Config_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Config fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config_Collection fetchCollection()
	 */
	class EO_Config_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Config createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_Config_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_Config_Collection wakeUpCollection($rows)
	 */
	class EO_Config_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\ConfigAutomaticMessagesTable:imopenlines/lib/model/configautomaticmessages.php:ea8886d3189c8703e1522b388cb028d5 */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_ConfigAutomaticMessages
	 * @see \Bitrix\Imopenlines\Model\ConfigAutomaticMessagesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \int getTimeTask()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setTimeTask(\int|\Bitrix\Main\DB\SqlExpression $timeTask)
	 * @method bool hasTimeTask()
	 * @method bool isTimeTaskFilled()
	 * @method bool isTimeTaskChanged()
	 * @method \int remindActualTimeTask()
	 * @method \int requireTimeTask()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetTimeTask()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetTimeTask()
	 * @method \int fillTimeTask()
	 * @method \string getMessage()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setMessage(\string|\Bitrix\Main\DB\SqlExpression $message)
	 * @method bool hasMessage()
	 * @method bool isMessageFilled()
	 * @method bool isMessageChanged()
	 * @method \string remindActualMessage()
	 * @method \string requireMessage()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetMessage()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetMessage()
	 * @method \string fillMessage()
	 * @method \string getTextButtonClose()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setTextButtonClose(\string|\Bitrix\Main\DB\SqlExpression $textButtonClose)
	 * @method bool hasTextButtonClose()
	 * @method bool isTextButtonCloseFilled()
	 * @method bool isTextButtonCloseChanged()
	 * @method \string remindActualTextButtonClose()
	 * @method \string requireTextButtonClose()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetTextButtonClose()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetTextButtonClose()
	 * @method \string fillTextButtonClose()
	 * @method \string getLongTextButtonClose()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setLongTextButtonClose(\string|\Bitrix\Main\DB\SqlExpression $longTextButtonClose)
	 * @method bool hasLongTextButtonClose()
	 * @method bool isLongTextButtonCloseFilled()
	 * @method bool isLongTextButtonCloseChanged()
	 * @method \string remindActualLongTextButtonClose()
	 * @method \string requireLongTextButtonClose()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetLongTextButtonClose()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetLongTextButtonClose()
	 * @method \string fillLongTextButtonClose()
	 * @method \string getAutomaticTextClose()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setAutomaticTextClose(\string|\Bitrix\Main\DB\SqlExpression $automaticTextClose)
	 * @method bool hasAutomaticTextClose()
	 * @method bool isAutomaticTextCloseFilled()
	 * @method bool isAutomaticTextCloseChanged()
	 * @method \string remindActualAutomaticTextClose()
	 * @method \string requireAutomaticTextClose()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetAutomaticTextClose()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetAutomaticTextClose()
	 * @method \string fillAutomaticTextClose()
	 * @method \string getTextButtonContinue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setTextButtonContinue(\string|\Bitrix\Main\DB\SqlExpression $textButtonContinue)
	 * @method bool hasTextButtonContinue()
	 * @method bool isTextButtonContinueFilled()
	 * @method bool isTextButtonContinueChanged()
	 * @method \string remindActualTextButtonContinue()
	 * @method \string requireTextButtonContinue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetTextButtonContinue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetTextButtonContinue()
	 * @method \string fillTextButtonContinue()
	 * @method \string getLongTextButtonContinue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setLongTextButtonContinue(\string|\Bitrix\Main\DB\SqlExpression $longTextButtonContinue)
	 * @method bool hasLongTextButtonContinue()
	 * @method bool isLongTextButtonContinueFilled()
	 * @method bool isLongTextButtonContinueChanged()
	 * @method \string remindActualLongTextButtonContinue()
	 * @method \string requireLongTextButtonContinue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetLongTextButtonContinue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetLongTextButtonContinue()
	 * @method \string fillLongTextButtonContinue()
	 * @method \string getAutomaticTextContinue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setAutomaticTextContinue(\string|\Bitrix\Main\DB\SqlExpression $automaticTextContinue)
	 * @method bool hasAutomaticTextContinue()
	 * @method bool isAutomaticTextContinueFilled()
	 * @method bool isAutomaticTextContinueChanged()
	 * @method \string remindActualAutomaticTextContinue()
	 * @method \string requireAutomaticTextContinue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetAutomaticTextContinue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetAutomaticTextContinue()
	 * @method \string fillAutomaticTextContinue()
	 * @method \string getTextButtonNew()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setTextButtonNew(\string|\Bitrix\Main\DB\SqlExpression $textButtonNew)
	 * @method bool hasTextButtonNew()
	 * @method bool isTextButtonNewFilled()
	 * @method bool isTextButtonNewChanged()
	 * @method \string remindActualTextButtonNew()
	 * @method \string requireTextButtonNew()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetTextButtonNew()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetTextButtonNew()
	 * @method \string fillTextButtonNew()
	 * @method \string getLongTextButtonNew()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setLongTextButtonNew(\string|\Bitrix\Main\DB\SqlExpression $longTextButtonNew)
	 * @method bool hasLongTextButtonNew()
	 * @method bool isLongTextButtonNewFilled()
	 * @method bool isLongTextButtonNewChanged()
	 * @method \string remindActualLongTextButtonNew()
	 * @method \string requireLongTextButtonNew()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetLongTextButtonNew()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetLongTextButtonNew()
	 * @method \string fillLongTextButtonNew()
	 * @method \string getAutomaticTextNew()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages setAutomaticTextNew(\string|\Bitrix\Main\DB\SqlExpression $automaticTextNew)
	 * @method bool hasAutomaticTextNew()
	 * @method bool isAutomaticTextNewFilled()
	 * @method bool isAutomaticTextNewChanged()
	 * @method \string remindActualAutomaticTextNew()
	 * @method \string requireAutomaticTextNew()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages resetAutomaticTextNew()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unsetAutomaticTextNew()
	 * @method \string fillAutomaticTextNew()
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
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages wakeUp($data)
	 */
	class EO_ConfigAutomaticMessages {
		/* @var \Bitrix\Imopenlines\Model\ConfigAutomaticMessagesTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\ConfigAutomaticMessagesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_ConfigAutomaticMessages_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \int[] getTimeTaskList()
	 * @method \int[] fillTimeTask()
	 * @method \string[] getMessageList()
	 * @method \string[] fillMessage()
	 * @method \string[] getTextButtonCloseList()
	 * @method \string[] fillTextButtonClose()
	 * @method \string[] getLongTextButtonCloseList()
	 * @method \string[] fillLongTextButtonClose()
	 * @method \string[] getAutomaticTextCloseList()
	 * @method \string[] fillAutomaticTextClose()
	 * @method \string[] getTextButtonContinueList()
	 * @method \string[] fillTextButtonContinue()
	 * @method \string[] getLongTextButtonContinueList()
	 * @method \string[] fillLongTextButtonContinue()
	 * @method \string[] getAutomaticTextContinueList()
	 * @method \string[] fillAutomaticTextContinue()
	 * @method \string[] getTextButtonNewList()
	 * @method \string[] fillTextButtonNew()
	 * @method \string[] getLongTextButtonNewList()
	 * @method \string[] fillLongTextButtonNew()
	 * @method \string[] getAutomaticTextNewList()
	 * @method \string[] fillAutomaticTextNew()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ConfigAutomaticMessages_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\ConfigAutomaticMessagesTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\ConfigAutomaticMessagesTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ConfigAutomaticMessages_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ConfigAutomaticMessages_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages_Collection fetchCollection()
	 */
	class EO_ConfigAutomaticMessages_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages_Collection wakeUpCollection($rows)
	 */
	class EO_ConfigAutomaticMessages_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\ConfigCategoryTable:imopenlines/lib/model/configcategory.php:bd8827f6f161cff1000787bf4873028b */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_ConfigCategory
	 * @see \Bitrix\Imopenlines\Model\ConfigCategoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory resetConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \string getCode()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory resetCode()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory unsetCode()
	 * @method \string fillCode()
	 * @method \string getValue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory resetValue()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory unsetValue()
	 * @method \string fillValue()
	 * @method \int getSort()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory resetSort()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory unsetSort()
	 * @method \int fillSort()
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
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_ConfigCategory wakeUp($data)
	 */
	class EO_ConfigCategory {
		/* @var \Bitrix\Imopenlines\Model\ConfigCategoryTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\ConfigCategoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_ConfigCategory_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_ConfigCategory $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_ConfigCategory $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_ConfigCategory $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_ConfigCategory_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ConfigCategory_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\ConfigCategoryTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\ConfigCategoryTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ConfigCategory_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ConfigCategory_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory_Collection fetchCollection()
	 */
	class EO_ConfigCategory_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigCategory_Collection wakeUpCollection($rows)
	 */
	class EO_ConfigCategory_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\ConfigQueueTable:imopenlines/lib/model/configqueue.php:61b2c7bf33fc7f7ca53c0187899c46dc */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_ConfigQueue
	 * @see \Bitrix\ImOpenLines\Model\ConfigQueueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getSort()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue resetSort()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue unsetSort()
	 * @method \int fillSort()
	 * @method \int getConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue resetConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \int getEntityId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue resetEntityId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getEntityType()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue resetEntityType()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config getConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config remindActualConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config requireConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue setConfig(\Bitrix\ImOpenLines\Model\EO_Config $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue resetConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue unsetConfig()
	 * @method bool hasConfig()
	 * @method bool isConfigFilled()
	 * @method bool isConfigChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config fillConfig()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_ConfigQueue wakeUp($data)
	 */
	class EO_ConfigQueue {
		/* @var \Bitrix\ImOpenLines\Model\ConfigQueueTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\ConfigQueueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_ConfigQueue_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config[] getConfigList()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue_Collection getConfigCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config_Collection fillConfig()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_ConfigQueue $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_ConfigQueue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_ConfigQueue $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_ConfigQueue_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ConfigQueue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\ConfigQueueTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\ConfigQueueTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ConfigQueue_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ConfigQueue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue_Collection fetchCollection()
	 */
	class EO_ConfigQueue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigQueue_Collection wakeUpCollection($rows)
	 */
	class EO_ConfigQueue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\ConfigStatisticTable:imopenlines/lib/model/configstatistic.php:53ca45aa32a193a293b05ad309e98aff */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_ConfigStatistic
	 * @see \Bitrix\ImOpenLines\Model\ConfigStatisticTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int getSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic setSession(\int|\Bitrix\Main\DB\SqlExpression $session)
	 * @method bool hasSession()
	 * @method bool isSessionFilled()
	 * @method bool isSessionChanged()
	 * @method \int remindActualSession()
	 * @method \int requireSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic resetSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic unsetSession()
	 * @method \int fillSession()
	 * @method \int getMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic setMessage(\int|\Bitrix\Main\DB\SqlExpression $message)
	 * @method bool hasMessage()
	 * @method bool isMessageFilled()
	 * @method bool isMessageChanged()
	 * @method \int remindActualMessage()
	 * @method \int requireMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic resetMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic unsetMessage()
	 * @method \int fillMessage()
	 * @method \int getClosed()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic setClosed(\int|\Bitrix\Main\DB\SqlExpression $closed)
	 * @method bool hasClosed()
	 * @method bool isClosedFilled()
	 * @method bool isClosedChanged()
	 * @method \int remindActualClosed()
	 * @method \int requireClosed()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic resetClosed()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic unsetClosed()
	 * @method \int fillClosed()
	 * @method \int getInWork()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic setInWork(\int|\Bitrix\Main\DB\SqlExpression $inWork)
	 * @method bool hasInWork()
	 * @method bool isInWorkFilled()
	 * @method bool isInWorkChanged()
	 * @method \int remindActualInWork()
	 * @method \int requireInWork()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic resetInWork()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic unsetInWork()
	 * @method \int fillInWork()
	 * @method \int getLead()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic setLead(\int|\Bitrix\Main\DB\SqlExpression $lead)
	 * @method bool hasLead()
	 * @method bool isLeadFilled()
	 * @method bool isLeadChanged()
	 * @method \int remindActualLead()
	 * @method \int requireLead()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic resetLead()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic unsetLead()
	 * @method \int fillLead()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_ConfigStatistic wakeUp($data)
	 */
	class EO_ConfigStatistic {
		/* @var \Bitrix\ImOpenLines\Model\ConfigStatisticTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\ConfigStatisticTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_ConfigStatistic_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getConfigIdList()
	 * @method \int[] getSessionList()
	 * @method \int[] fillSession()
	 * @method \int[] getMessageList()
	 * @method \int[] fillMessage()
	 * @method \int[] getClosedList()
	 * @method \int[] fillClosed()
	 * @method \int[] getInWorkList()
	 * @method \int[] fillInWork()
	 * @method \int[] getLeadList()
	 * @method \int[] fillLead()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_ConfigStatistic $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_ConfigStatistic $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_ConfigStatistic $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_ConfigStatistic_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ConfigStatistic_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\ConfigStatisticTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\ConfigStatisticTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ConfigStatistic_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ConfigStatistic_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic_Collection fetchCollection()
	 */
	class EO_ConfigStatistic_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_ConfigStatistic_Collection wakeUpCollection($rows)
	 */
	class EO_ConfigStatistic_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\EventLogTable:imopenlines/lib/model/eventlog.php:28a97e52a3c50f30285c7469ae92e396 */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_EventLog
	 * @see \Bitrix\Imopenlines\Model\EventLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getLineId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog setLineId(\int|\Bitrix\Main\DB\SqlExpression $lineId)
	 * @method bool hasLineId()
	 * @method bool isLineIdFilled()
	 * @method bool isLineIdChanged()
	 * @method \int remindActualLineId()
	 * @method \int requireLineId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog resetLineId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog unsetLineId()
	 * @method \int fillLineId()
	 * @method \string getEventType()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog setEventType(\string|\Bitrix\Main\DB\SqlExpression $eventType)
	 * @method bool hasEventType()
	 * @method bool isEventTypeFilled()
	 * @method bool isEventTypeChanged()
	 * @method \string remindActualEventType()
	 * @method \string requireEventType()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog resetEventType()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog unsetEventType()
	 * @method \string fillEventType()
	 * @method \Bitrix\Main\Type\DateTime getDateTime()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog setDateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateTime)
	 * @method bool hasDateTime()
	 * @method bool isDateTimeFilled()
	 * @method bool isDateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateTime()
	 * @method \Bitrix\Main\Type\DateTime requireDateTime()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog resetDateTime()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog unsetDateTime()
	 * @method \Bitrix\Main\Type\DateTime fillDateTime()
	 * @method \boolean getIsError()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog setIsError(\boolean|\Bitrix\Main\DB\SqlExpression $isError)
	 * @method bool hasIsError()
	 * @method bool isIsErrorFilled()
	 * @method bool isIsErrorChanged()
	 * @method \boolean remindActualIsError()
	 * @method \boolean requireIsError()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog resetIsError()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog unsetIsError()
	 * @method \boolean fillIsError()
	 * @method \string getEventMessage()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog setEventMessage(\string|\Bitrix\Main\DB\SqlExpression $eventMessage)
	 * @method bool hasEventMessage()
	 * @method bool isEventMessageFilled()
	 * @method bool isEventMessageChanged()
	 * @method \string remindActualEventMessage()
	 * @method \string requireEventMessage()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog resetEventMessage()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog unsetEventMessage()
	 * @method \string fillEventMessage()
	 * @method \int getSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog resetSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \int getMessageId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog setMessageId(\int|\Bitrix\Main\DB\SqlExpression $messageId)
	 * @method bool hasMessageId()
	 * @method bool isMessageIdFilled()
	 * @method bool isMessageIdChanged()
	 * @method \int remindActualMessageId()
	 * @method \int requireMessageId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog resetMessageId()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog unsetMessageId()
	 * @method \int fillMessageId()
	 * @method \string getAdditionalFields()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog setAdditionalFields(\string|\Bitrix\Main\DB\SqlExpression $additionalFields)
	 * @method bool hasAdditionalFields()
	 * @method bool isAdditionalFieldsFilled()
	 * @method bool isAdditionalFieldsChanged()
	 * @method \string remindActualAdditionalFields()
	 * @method \string requireAdditionalFields()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog resetAdditionalFields()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog unsetAdditionalFields()
	 * @method \string fillAdditionalFields()
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
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_EventLog wakeUp($data)
	 */
	class EO_EventLog {
		/* @var \Bitrix\Imopenlines\Model\EventLogTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\EventLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_EventLog_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getLineIdList()
	 * @method \int[] fillLineId()
	 * @method \string[] getEventTypeList()
	 * @method \string[] fillEventType()
	 * @method \Bitrix\Main\Type\DateTime[] getDateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateTime()
	 * @method \boolean[] getIsErrorList()
	 * @method \boolean[] fillIsError()
	 * @method \string[] getEventMessageList()
	 * @method \string[] fillEventMessage()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \int[] getMessageIdList()
	 * @method \int[] fillMessageId()
	 * @method \string[] getAdditionalFieldsList()
	 * @method \string[] fillAdditionalFields()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_EventLog $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_EventLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_EventLog $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_EventLog_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_EventLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\EventLogTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\EventLogTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_EventLog_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_EventLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog_Collection fetchCollection()
	 */
	class EO_EventLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_EventLog_Collection wakeUpCollection($rows)
	 */
	class EO_EventLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\ExecLogTable:imopenlines/lib/model/execlog.php:bd8506fe2bb4089ba42f0bf68393a7ad */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_ExecLog
	 * @see \Bitrix\Imopenlines\Model\ExecLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getExecFunction()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog setExecFunction(\string|\Bitrix\Main\DB\SqlExpression $execFunction)
	 * @method bool hasExecFunction()
	 * @method bool isExecFunctionFilled()
	 * @method bool isExecFunctionChanged()
	 * @method \string remindActualExecFunction()
	 * @method \string requireExecFunction()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog resetExecFunction()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog unsetExecFunction()
	 * @method \string fillExecFunction()
	 * @method \Bitrix\Main\Type\DateTime getLastExecTime()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog setLastExecTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastExecTime)
	 * @method bool hasLastExecTime()
	 * @method bool isLastExecTimeFilled()
	 * @method bool isLastExecTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastExecTime()
	 * @method \Bitrix\Main\Type\DateTime requireLastExecTime()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog resetLastExecTime()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog unsetLastExecTime()
	 * @method \Bitrix\Main\Type\DateTime fillLastExecTime()
	 * @method \boolean getIsSuccess()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog setIsSuccess(\boolean|\Bitrix\Main\DB\SqlExpression $isSuccess)
	 * @method bool hasIsSuccess()
	 * @method bool isIsSuccessFilled()
	 * @method bool isIsSuccessChanged()
	 * @method \boolean remindActualIsSuccess()
	 * @method \boolean requireIsSuccess()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog resetIsSuccess()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog unsetIsSuccess()
	 * @method \boolean fillIsSuccess()
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
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_ExecLog wakeUp($data)
	 */
	class EO_ExecLog {
		/* @var \Bitrix\Imopenlines\Model\ExecLogTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\ExecLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_ExecLog_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getExecFunctionList()
	 * @method \string[] fillExecFunction()
	 * @method \Bitrix\Main\Type\DateTime[] getLastExecTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLastExecTime()
	 * @method \boolean[] getIsSuccessList()
	 * @method \boolean[] fillIsSuccess()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_ExecLog $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_ExecLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_ExecLog $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_ExecLog_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_ExecLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\ExecLogTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\ExecLogTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ExecLog_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_ExecLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog_Collection fetchCollection()
	 */
	class EO_ExecLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_ExecLog_Collection wakeUpCollection($rows)
	 */
	class EO_ExecLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\LivechatTable:imopenlines/lib/model/livechat.php:ef8c2bb4e54da002c1c1531c19e83d49 */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_Livechat
	 * @see \Bitrix\Imopenlines\Model\LivechatTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \string getUrlCode()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setUrlCode(\string|\Bitrix\Main\DB\SqlExpression $urlCode)
	 * @method bool hasUrlCode()
	 * @method bool isUrlCodeFilled()
	 * @method bool isUrlCodeChanged()
	 * @method \string remindActualUrlCode()
	 * @method \string requireUrlCode()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetUrlCode()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetUrlCode()
	 * @method \string fillUrlCode()
	 * @method \int getUrlCodeId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setUrlCodeId(\int|\Bitrix\Main\DB\SqlExpression $urlCodeId)
	 * @method bool hasUrlCodeId()
	 * @method bool isUrlCodeIdFilled()
	 * @method bool isUrlCodeIdChanged()
	 * @method \int remindActualUrlCodeId()
	 * @method \int requireUrlCodeId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetUrlCodeId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetUrlCodeId()
	 * @method \int fillUrlCodeId()
	 * @method \string getUrlCodePublic()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setUrlCodePublic(\string|\Bitrix\Main\DB\SqlExpression $urlCodePublic)
	 * @method bool hasUrlCodePublic()
	 * @method bool isUrlCodePublicFilled()
	 * @method bool isUrlCodePublicChanged()
	 * @method \string remindActualUrlCodePublic()
	 * @method \string requireUrlCodePublic()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetUrlCodePublic()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetUrlCodePublic()
	 * @method \string fillUrlCodePublic()
	 * @method \int getUrlCodePublicId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setUrlCodePublicId(\int|\Bitrix\Main\DB\SqlExpression $urlCodePublicId)
	 * @method bool hasUrlCodePublicId()
	 * @method bool isUrlCodePublicIdFilled()
	 * @method bool isUrlCodePublicIdChanged()
	 * @method \int remindActualUrlCodePublicId()
	 * @method \int requireUrlCodePublicId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetUrlCodePublicId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetUrlCodePublicId()
	 * @method \int fillUrlCodePublicId()
	 * @method \string getTemplateId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setTemplateId(\string|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \string remindActualTemplateId()
	 * @method \string requireTemplateId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetTemplateId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetTemplateId()
	 * @method \string fillTemplateId()
	 * @method \int getBackgroundImage()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setBackgroundImage(\int|\Bitrix\Main\DB\SqlExpression $backgroundImage)
	 * @method bool hasBackgroundImage()
	 * @method bool isBackgroundImageFilled()
	 * @method bool isBackgroundImageChanged()
	 * @method \int remindActualBackgroundImage()
	 * @method \int requireBackgroundImage()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetBackgroundImage()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetBackgroundImage()
	 * @method \int fillBackgroundImage()
	 * @method \boolean getCssActive()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setCssActive(\boolean|\Bitrix\Main\DB\SqlExpression $cssActive)
	 * @method bool hasCssActive()
	 * @method bool isCssActiveFilled()
	 * @method bool isCssActiveChanged()
	 * @method \boolean remindActualCssActive()
	 * @method \boolean requireCssActive()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetCssActive()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetCssActive()
	 * @method \boolean fillCssActive()
	 * @method \string getCssPath()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setCssPath(\string|\Bitrix\Main\DB\SqlExpression $cssPath)
	 * @method bool hasCssPath()
	 * @method bool isCssPathFilled()
	 * @method bool isCssPathChanged()
	 * @method \string remindActualCssPath()
	 * @method \string requireCssPath()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetCssPath()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetCssPath()
	 * @method \string fillCssPath()
	 * @method \string getCssText()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setCssText(\string|\Bitrix\Main\DB\SqlExpression $cssText)
	 * @method bool hasCssText()
	 * @method bool isCssTextFilled()
	 * @method bool isCssTextChanged()
	 * @method \string remindActualCssText()
	 * @method \string requireCssText()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetCssText()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetCssText()
	 * @method \string fillCssText()
	 * @method \boolean getCopyrightRemoved()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setCopyrightRemoved(\boolean|\Bitrix\Main\DB\SqlExpression $copyrightRemoved)
	 * @method bool hasCopyrightRemoved()
	 * @method bool isCopyrightRemovedFilled()
	 * @method bool isCopyrightRemovedChanged()
	 * @method \boolean remindActualCopyrightRemoved()
	 * @method \boolean requireCopyrightRemoved()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetCopyrightRemoved()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetCopyrightRemoved()
	 * @method \boolean fillCopyrightRemoved()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config getConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config remindActualConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config requireConfig()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setConfig(\Bitrix\ImOpenLines\Model\EO_Config $object)
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetConfig()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetConfig()
	 * @method bool hasConfig()
	 * @method bool isConfigFilled()
	 * @method bool isConfigChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config fillConfig()
	 * @method \int getCacheWidgetId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setCacheWidgetId(\int|\Bitrix\Main\DB\SqlExpression $cacheWidgetId)
	 * @method bool hasCacheWidgetId()
	 * @method bool isCacheWidgetIdFilled()
	 * @method bool isCacheWidgetIdChanged()
	 * @method \int remindActualCacheWidgetId()
	 * @method \int requireCacheWidgetId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetCacheWidgetId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetCacheWidgetId()
	 * @method \int fillCacheWidgetId()
	 * @method \int getCacheButtonId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setCacheButtonId(\int|\Bitrix\Main\DB\SqlExpression $cacheButtonId)
	 * @method bool hasCacheButtonId()
	 * @method bool isCacheButtonIdFilled()
	 * @method bool isCacheButtonIdChanged()
	 * @method \int remindActualCacheButtonId()
	 * @method \int requireCacheButtonId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetCacheButtonId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetCacheButtonId()
	 * @method \int fillCacheButtonId()
	 * @method \string getPhoneCode()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setPhoneCode(\string|\Bitrix\Main\DB\SqlExpression $phoneCode)
	 * @method bool hasPhoneCode()
	 * @method bool isPhoneCodeFilled()
	 * @method bool isPhoneCodeChanged()
	 * @method \string remindActualPhoneCode()
	 * @method \string requirePhoneCode()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetPhoneCode()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetPhoneCode()
	 * @method \string fillPhoneCode()
	 * @method \string getTextPhrases()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setTextPhrases(\string|\Bitrix\Main\DB\SqlExpression $textPhrases)
	 * @method bool hasTextPhrases()
	 * @method bool isTextPhrasesFilled()
	 * @method bool isTextPhrasesChanged()
	 * @method \string remindActualTextPhrases()
	 * @method \string requireTextPhrases()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetTextPhrases()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetTextPhrases()
	 * @method \string fillTextPhrases()
	 * @method \boolean getShowSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat setShowSessionId(\boolean|\Bitrix\Main\DB\SqlExpression $showSessionId)
	 * @method bool hasShowSessionId()
	 * @method bool isShowSessionIdFilled()
	 * @method bool isShowSessionIdChanged()
	 * @method \boolean remindActualShowSessionId()
	 * @method \boolean requireShowSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat resetShowSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unsetShowSessionId()
	 * @method \boolean fillShowSessionId()
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
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_Livechat wakeUp($data)
	 */
	class EO_Livechat {
		/* @var \Bitrix\Imopenlines\Model\LivechatTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\LivechatTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_Livechat_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getConfigIdList()
	 * @method \string[] getUrlCodeList()
	 * @method \string[] fillUrlCode()
	 * @method \int[] getUrlCodeIdList()
	 * @method \int[] fillUrlCodeId()
	 * @method \string[] getUrlCodePublicList()
	 * @method \string[] fillUrlCodePublic()
	 * @method \int[] getUrlCodePublicIdList()
	 * @method \int[] fillUrlCodePublicId()
	 * @method \string[] getTemplateIdList()
	 * @method \string[] fillTemplateId()
	 * @method \int[] getBackgroundImageList()
	 * @method \int[] fillBackgroundImage()
	 * @method \boolean[] getCssActiveList()
	 * @method \boolean[] fillCssActive()
	 * @method \string[] getCssPathList()
	 * @method \string[] fillCssPath()
	 * @method \string[] getCssTextList()
	 * @method \string[] fillCssText()
	 * @method \boolean[] getCopyrightRemovedList()
	 * @method \boolean[] fillCopyrightRemoved()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config[] getConfigList()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat_Collection getConfigCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config_Collection fillConfig()
	 * @method \int[] getCacheWidgetIdList()
	 * @method \int[] fillCacheWidgetId()
	 * @method \int[] getCacheButtonIdList()
	 * @method \int[] fillCacheButtonId()
	 * @method \string[] getPhoneCodeList()
	 * @method \string[] fillPhoneCode()
	 * @method \string[] getTextPhrasesList()
	 * @method \string[] fillTextPhrases()
	 * @method \boolean[] getShowSessionIdList()
	 * @method \boolean[] fillShowSessionId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_Livechat $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_Livechat $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_Livechat $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_Livechat_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Livechat_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\LivechatTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\LivechatTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Livechat_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Livechat_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat_Collection fetchCollection()
	 */
	class EO_Livechat_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat_Collection wakeUpCollection($rows)
	 */
	class EO_Livechat_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\LockTable:imopenlines/lib/model/lock.php:e8dba3a0364a9a5978a376f44ac831b3 */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Lock
	 * @see \Bitrix\ImOpenLines\Model\LockTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock setId(\string|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock resetDateCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getLockTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock setLockTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lockTime)
	 * @method bool hasLockTime()
	 * @method bool isLockTimeFilled()
	 * @method bool isLockTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLockTime()
	 * @method \Bitrix\Main\Type\DateTime requireLockTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock resetLockTime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock unsetLockTime()
	 * @method \Bitrix\Main\Type\DateTime fillLockTime()
	 * @method \string getPid()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock setPid(\string|\Bitrix\Main\DB\SqlExpression $pid)
	 * @method bool hasPid()
	 * @method bool isPidFilled()
	 * @method bool isPidChanged()
	 * @method \string remindActualPid()
	 * @method \string requirePid()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock resetPid()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock unsetPid()
	 * @method \string fillPid()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_Lock wakeUp($data)
	 */
	class EO_Lock {
		/* @var \Bitrix\ImOpenLines\Model\LockTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\LockTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Lock_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getLockTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLockTime()
	 * @method \string[] getPidList()
	 * @method \string[] fillPid()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_Lock $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_Lock $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_Lock $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_Lock_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Lock_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\LockTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\LockTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Lock_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Lock_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock_Collection fetchCollection()
	 */
	class EO_Lock_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_Lock_Collection wakeUpCollection($rows)
	 */
	class EO_Lock_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\LogTable:imopenlines/lib/model/log.php:49996ccd9d4736084cdcdbce8a2c72a6 */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_Log
	 * @see \Bitrix\Imopenlines\Model\LogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getDataTime()
	 * @method \Bitrix\Imopenlines\Model\EO_Log setDataTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dataTime)
	 * @method bool hasDataTime()
	 * @method bool isDataTimeFilled()
	 * @method bool isDataTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDataTime()
	 * @method \Bitrix\Main\Type\DateTime requireDataTime()
	 * @method \Bitrix\Imopenlines\Model\EO_Log resetDataTime()
	 * @method \Bitrix\Imopenlines\Model\EO_Log unsetDataTime()
	 * @method \Bitrix\Main\Type\DateTime fillDataTime()
	 * @method \string getLineId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log setLineId(\string|\Bitrix\Main\DB\SqlExpression $lineId)
	 * @method bool hasLineId()
	 * @method bool isLineIdFilled()
	 * @method bool isLineIdChanged()
	 * @method \string remindActualLineId()
	 * @method \string requireLineId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log resetLineId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log unsetLineId()
	 * @method \string fillLineId()
	 * @method \string getConnectorId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log setConnectorId(\string|\Bitrix\Main\DB\SqlExpression $connectorId)
	 * @method bool hasConnectorId()
	 * @method bool isConnectorIdFilled()
	 * @method bool isConnectorIdChanged()
	 * @method \string remindActualConnectorId()
	 * @method \string requireConnectorId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log resetConnectorId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log unsetConnectorId()
	 * @method \string fillConnectorId()
	 * @method \int getSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log resetSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_Log unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \string getType()
	 * @method \Bitrix\Imopenlines\Model\EO_Log setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Imopenlines\Model\EO_Log resetType()
	 * @method \Bitrix\Imopenlines\Model\EO_Log unsetType()
	 * @method \string fillType()
	 * @method \string getData()
	 * @method \Bitrix\Imopenlines\Model\EO_Log setData(\string|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method \string remindActualData()
	 * @method \string requireData()
	 * @method \Bitrix\Imopenlines\Model\EO_Log resetData()
	 * @method \Bitrix\Imopenlines\Model\EO_Log unsetData()
	 * @method \string fillData()
	 * @method \string getTrace()
	 * @method \Bitrix\Imopenlines\Model\EO_Log setTrace(\string|\Bitrix\Main\DB\SqlExpression $trace)
	 * @method bool hasTrace()
	 * @method bool isTraceFilled()
	 * @method bool isTraceChanged()
	 * @method \string remindActualTrace()
	 * @method \string requireTrace()
	 * @method \Bitrix\Imopenlines\Model\EO_Log resetTrace()
	 * @method \Bitrix\Imopenlines\Model\EO_Log unsetTrace()
	 * @method \string fillTrace()
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
	 * @method \Bitrix\Imopenlines\Model\EO_Log set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_Log reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_Log unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_Log wakeUp($data)
	 */
	class EO_Log {
		/* @var \Bitrix\Imopenlines\Model\LogTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\LogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_Log_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getDataTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDataTime()
	 * @method \string[] getLineIdList()
	 * @method \string[] fillLineId()
	 * @method \string[] getConnectorIdList()
	 * @method \string[] fillConnectorId()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getDataList()
	 * @method \string[] fillData()
	 * @method \string[] getTraceList()
	 * @method \string[] fillTrace()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_Log $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_Log $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_Log getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_Log[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_Log $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_Log_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_Log current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Log_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\LogTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\LogTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Log_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_Log fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_Log_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Log_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_Log fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_Log_Collection fetchCollection()
	 */
	class EO_Log_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_Log createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_Log_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_Log wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_Log_Collection wakeUpCollection($rows)
	 */
	class EO_Log_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\OperatorTransferTable:imopenlines/lib/model/operatortransfer.php:8d89fe1f75111c01547ce78c904da782 */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_OperatorTransfer
	 * @see \Bitrix\Imopenlines\Model\OperatorTransferTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer resetConfigId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \int getSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer resetSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \int getUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer resetUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getTransferMode()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer setTransferMode(\string|\Bitrix\Main\DB\SqlExpression $transferMode)
	 * @method bool hasTransferMode()
	 * @method bool isTransferModeFilled()
	 * @method bool isTransferModeChanged()
	 * @method \string remindActualTransferMode()
	 * @method \string requireTransferMode()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer resetTransferMode()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer unsetTransferMode()
	 * @method \string fillTransferMode()
	 * @method \string getTransferType()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer setTransferType(\string|\Bitrix\Main\DB\SqlExpression $transferType)
	 * @method bool hasTransferType()
	 * @method bool isTransferTypeFilled()
	 * @method bool isTransferTypeChanged()
	 * @method \string remindActualTransferType()
	 * @method \string requireTransferType()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer resetTransferType()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer unsetTransferType()
	 * @method \string fillTransferType()
	 * @method \int getTransferUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer setTransferUserId(\int|\Bitrix\Main\DB\SqlExpression $transferUserId)
	 * @method bool hasTransferUserId()
	 * @method bool isTransferUserIdFilled()
	 * @method bool isTransferUserIdChanged()
	 * @method \int remindActualTransferUserId()
	 * @method \int requireTransferUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer resetTransferUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer unsetTransferUserId()
	 * @method \int fillTransferUserId()
	 * @method \int getTransferLineId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer setTransferLineId(\int|\Bitrix\Main\DB\SqlExpression $transferLineId)
	 * @method bool hasTransferLineId()
	 * @method bool isTransferLineIdFilled()
	 * @method bool isTransferLineIdChanged()
	 * @method \int remindActualTransferLineId()
	 * @method \int requireTransferLineId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer resetTransferLineId()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer unsetTransferLineId()
	 * @method \int fillTransferLineId()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer resetDateCreate()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
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
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_OperatorTransfer wakeUp($data)
	 */
	class EO_OperatorTransfer {
		/* @var \Bitrix\Imopenlines\Model\OperatorTransferTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\OperatorTransferTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_OperatorTransfer_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getTransferModeList()
	 * @method \string[] fillTransferMode()
	 * @method \string[] getTransferTypeList()
	 * @method \string[] fillTransferType()
	 * @method \int[] getTransferUserIdList()
	 * @method \int[] fillTransferUserId()
	 * @method \int[] getTransferLineIdList()
	 * @method \int[] fillTransferLineId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_OperatorTransfer $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_OperatorTransfer $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_OperatorTransfer $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_OperatorTransfer_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_OperatorTransfer_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\OperatorTransferTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\OperatorTransferTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_OperatorTransfer_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_OperatorTransfer_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer_Collection fetchCollection()
	 */
	class EO_OperatorTransfer_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_OperatorTransfer_Collection wakeUpCollection($rows)
	 */
	class EO_OperatorTransfer_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\QueueTable:imopenlines/lib/model/queue.php:d4f957db5ac44c11120eba5b1f6ef3e6 */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Queue
	 * @see \Bitrix\ImOpenLines\Model\QueueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getSort()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetSort()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetSort()
	 * @method \int fillSort()
	 * @method \int getConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \int getUserId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetUserId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getDepartmentId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setDepartmentId(\int|\Bitrix\Main\DB\SqlExpression $departmentId)
	 * @method bool hasDepartmentId()
	 * @method bool isDepartmentIdFilled()
	 * @method bool isDepartmentIdChanged()
	 * @method \int remindActualDepartmentId()
	 * @method \int requireDepartmentId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetDepartmentId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetDepartmentId()
	 * @method \int fillDepartmentId()
	 * @method \Bitrix\Main\Type\DateTime getLastActivityDate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setLastActivityDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $lastActivityDate)
	 * @method bool hasLastActivityDate()
	 * @method bool isLastActivityDateFilled()
	 * @method bool isLastActivityDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime requireLastActivityDate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetLastActivityDate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetLastActivityDate()
	 * @method \Bitrix\Main\Type\DateTime fillLastActivityDate()
	 * @method \int getLastActivityDateExact()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setLastActivityDateExact(\int|\Bitrix\Main\DB\SqlExpression $lastActivityDateExact)
	 * @method bool hasLastActivityDateExact()
	 * @method bool isLastActivityDateExactFilled()
	 * @method bool isLastActivityDateExactChanged()
	 * @method \int remindActualLastActivityDateExact()
	 * @method \int requireLastActivityDateExact()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetLastActivityDateExact()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetLastActivityDateExact()
	 * @method \int fillLastActivityDateExact()
	 * @method \string getUserName()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setUserName(\string|\Bitrix\Main\DB\SqlExpression $userName)
	 * @method bool hasUserName()
	 * @method bool isUserNameFilled()
	 * @method bool isUserNameChanged()
	 * @method \string remindActualUserName()
	 * @method \string requireUserName()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetUserName()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetUserName()
	 * @method \string fillUserName()
	 * @method \string getUserWorkPosition()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setUserWorkPosition(\string|\Bitrix\Main\DB\SqlExpression $userWorkPosition)
	 * @method bool hasUserWorkPosition()
	 * @method bool isUserWorkPositionFilled()
	 * @method bool isUserWorkPositionChanged()
	 * @method \string remindActualUserWorkPosition()
	 * @method \string requireUserWorkPosition()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetUserWorkPosition()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetUserWorkPosition()
	 * @method \string fillUserWorkPosition()
	 * @method \string getUserAvatar()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setUserAvatar(\string|\Bitrix\Main\DB\SqlExpression $userAvatar)
	 * @method bool hasUserAvatar()
	 * @method bool isUserAvatarFilled()
	 * @method bool isUserAvatarChanged()
	 * @method \string remindActualUserAvatar()
	 * @method \string requireUserAvatar()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetUserAvatar()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetUserAvatar()
	 * @method \string fillUserAvatar()
	 * @method \int getUserAvatarId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setUserAvatarId(\int|\Bitrix\Main\DB\SqlExpression $userAvatarId)
	 * @method bool hasUserAvatarId()
	 * @method bool isUserAvatarIdFilled()
	 * @method bool isUserAvatarIdChanged()
	 * @method \int remindActualUserAvatarId()
	 * @method \int requireUserAvatarId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetUserAvatarId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetUserAvatarId()
	 * @method \int fillUserAvatarId()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetUser()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config getConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config remindActualConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config requireConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue setConfig(\Bitrix\ImOpenLines\Model\EO_Config $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue resetConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unsetConfig()
	 * @method bool hasConfig()
	 * @method bool isConfigFilled()
	 * @method bool isConfigChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config fillConfig()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_Queue wakeUp($data)
	 */
	class EO_Queue {
		/* @var \Bitrix\ImOpenLines\Model\QueueTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\QueueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Queue_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getDepartmentIdList()
	 * @method \int[] fillDepartmentId()
	 * @method \Bitrix\Main\Type\DateTime[] getLastActivityDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillLastActivityDate()
	 * @method \int[] getLastActivityDateExactList()
	 * @method \int[] fillLastActivityDateExact()
	 * @method \string[] getUserNameList()
	 * @method \string[] fillUserName()
	 * @method \string[] getUserWorkPositionList()
	 * @method \string[] fillUserWorkPosition()
	 * @method \string[] getUserAvatarList()
	 * @method \string[] fillUserAvatar()
	 * @method \int[] getUserAvatarIdList()
	 * @method \int[] fillUserAvatarId()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config[] getConfigList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue_Collection getConfigCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config_Collection fillConfig()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_Queue $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_Queue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_Queue $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_Queue_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Queue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\QueueTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\QueueTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Queue_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Queue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue_Collection fetchCollection()
	 */
	class EO_Queue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_Queue_Collection wakeUpCollection($rows)
	 */
	class EO_Queue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\RestNetworkLimitTable:imopenlines/lib/model/restnetworklimit.php:712ffd7126c3a95281a297fa8d92f489 */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_RestNetworkLimit
	 * @see \Bitrix\Imopenlines\Model\RestNetworkLimitTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getBotId()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit setBotId(\int|\Bitrix\Main\DB\SqlExpression $botId)
	 * @method bool hasBotId()
	 * @method bool isBotIdFilled()
	 * @method bool isBotIdChanged()
	 * @method \int remindActualBotId()
	 * @method \int requireBotId()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit resetBotId()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit unsetBotId()
	 * @method \int fillBotId()
	 * @method \int getUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit resetUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit resetDateCreate()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
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
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_RestNetworkLimit wakeUp($data)
	 */
	class EO_RestNetworkLimit {
		/* @var \Bitrix\Imopenlines\Model\RestNetworkLimitTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\RestNetworkLimitTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_RestNetworkLimit_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getBotIdList()
	 * @method \int[] fillBotId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_RestNetworkLimit $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_RestNetworkLimit $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_RestNetworkLimit $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_RestNetworkLimit_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RestNetworkLimit_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\RestNetworkLimitTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\RestNetworkLimitTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RestNetworkLimit_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RestNetworkLimit_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit_Collection fetchCollection()
	 */
	class EO_RestNetworkLimit_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_RestNetworkLimit_Collection wakeUpCollection($rows)
	 */
	class EO_RestNetworkLimit_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\RoleTable:imopenlines/lib/model/role.php:6521bb2b76d7d984c7e78e6c27dedbf7 */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Role
	 * @see \Bitrix\ImOpenLines\Model\RoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role resetName()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role unsetName()
	 * @method \string fillName()
	 * @method \string getXmlId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role resetXmlId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role unsetXmlId()
	 * @method \string fillXmlId()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_Role set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_Role reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_Role unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_Role wakeUp($data)
	 */
	class EO_Role {
		/* @var \Bitrix\ImOpenLines\Model\RoleTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\RoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Role_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_Role $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_Role $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Role getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Role[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_Role $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_Role_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_Role current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Role_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\RoleTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\RoleTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Role_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Role_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Role fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role_Collection fetchCollection()
	 */
	class EO_Role_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Role createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_Role_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_Role_Collection wakeUpCollection($rows)
	 */
	class EO_Role_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\RoleAccessTable:imopenlines/lib/model/roleaccess.php:e41f14f6d6ea52e80a634287a31fdd72 */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_RoleAccess
	 * @see \Bitrix\ImOpenLines\Model\RoleAccessTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess resetRoleId()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess resetAccessCode()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role getRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role remindActualRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role requireRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess setRole(\Bitrix\ImOpenLines\Model\EO_Role $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess resetRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role fillRole()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_RoleAccess wakeUp($data)
	 */
	class EO_RoleAccess {
		/* @var \Bitrix\ImOpenLines\Model\RoleAccessTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\RoleAccessTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
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
	 * @method \Bitrix\ImOpenLines\Model\EO_Role[] getRoleList()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess_Collection getRoleCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role_Collection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_RoleAccess $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_RoleAccess $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_RoleAccess $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_RoleAccess_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RoleAccess_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\RoleAccessTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\RoleAccessTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RoleAccess_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RoleAccess_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess_Collection fetchCollection()
	 */
	class EO_RoleAccess_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess_Collection wakeUpCollection($rows)
	 */
	class EO_RoleAccess_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\RolePermissionTable:imopenlines/lib/model/rolepermission.php:46ce73a013d5f570add7f9685036d838 */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_RolePermission
	 * @see \Bitrix\ImOpenLines\Model\RolePermissionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission resetRoleId()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getEntity()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission setEntity(\string|\Bitrix\Main\DB\SqlExpression $entity)
	 * @method bool hasEntity()
	 * @method bool isEntityFilled()
	 * @method bool isEntityChanged()
	 * @method \string remindActualEntity()
	 * @method \string requireEntity()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission resetEntity()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission unsetEntity()
	 * @method \string fillEntity()
	 * @method \string getAction()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission resetAction()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission unsetAction()
	 * @method \string fillAction()
	 * @method \string getPermission()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission setPermission(\string|\Bitrix\Main\DB\SqlExpression $permission)
	 * @method bool hasPermission()
	 * @method bool isPermissionFilled()
	 * @method bool isPermissionChanged()
	 * @method \string remindActualPermission()
	 * @method \string requirePermission()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission resetPermission()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission unsetPermission()
	 * @method \string fillPermission()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess getRoleAccess()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess remindActualRoleAccess()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess requireRoleAccess()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission setRoleAccess(\Bitrix\ImOpenLines\Model\EO_RoleAccess $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission resetRoleAccess()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission unsetRoleAccess()
	 * @method bool hasRoleAccess()
	 * @method bool isRoleAccessFilled()
	 * @method bool isRoleAccessChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess fillRoleAccess()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role getRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role remindActualRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role requireRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission setRole(\Bitrix\ImOpenLines\Model\EO_Role $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission resetRole()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission unsetRole()
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role fillRole()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_RolePermission wakeUp($data)
	 */
	class EO_RolePermission {
		/* @var \Bitrix\ImOpenLines\Model\RolePermissionTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\RolePermissionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
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
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess[] getRoleAccessList()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission_Collection getRoleAccessCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_RoleAccess_Collection fillRoleAccess()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role[] getRoleList()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission_Collection getRoleCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Role_Collection fillRole()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_RolePermission $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_RolePermission $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_RolePermission $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_RolePermission_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_RolePermission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\RolePermissionTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\RolePermissionTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RolePermission_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_RolePermission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission_Collection fetchCollection()
	 */
	class EO_RolePermission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_RolePermission_Collection wakeUpCollection($rows)
	 */
	class EO_RolePermission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\SessionTable:imopenlines/lib/model/session.php:ac4172be59e80563c2feedc6c20a2b1a */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Session
	 * @see \Bitrix\ImOpenLines\Model\SessionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getMode()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setMode(\string|\Bitrix\Main\DB\SqlExpression $mode)
	 * @method bool hasMode()
	 * @method bool isModeFilled()
	 * @method bool isModeChanged()
	 * @method \string remindActualMode()
	 * @method \string requireMode()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetMode()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetMode()
	 * @method \string fillMode()
	 * @method \string getSource()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setSource(\string|\Bitrix\Main\DB\SqlExpression $source)
	 * @method bool hasSource()
	 * @method bool isSourceFilled()
	 * @method bool isSourceChanged()
	 * @method \string remindActualSource()
	 * @method \string requireSource()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetSource()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetSource()
	 * @method \string fillSource()
	 * @method \int getStatus()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetStatus()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetStatus()
	 * @method \int fillStatus()
	 * @method \int getConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setConfigId(\int|\Bitrix\Main\DB\SqlExpression $configId)
	 * @method bool hasConfigId()
	 * @method bool isConfigIdFilled()
	 * @method bool isConfigIdChanged()
	 * @method \int remindActualConfigId()
	 * @method \int requireConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetConfigId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetConfigId()
	 * @method \int fillConfigId()
	 * @method \int getUserId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetUserId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetUserId()
	 * @method \int fillUserId()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetUser()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \int getOperatorId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setOperatorId(\int|\Bitrix\Main\DB\SqlExpression $operatorId)
	 * @method bool hasOperatorId()
	 * @method bool isOperatorIdFilled()
	 * @method bool isOperatorIdChanged()
	 * @method \int remindActualOperatorId()
	 * @method \int requireOperatorId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetOperatorId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetOperatorId()
	 * @method \int fillOperatorId()
	 * @method \boolean getOperatorFromCrm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setOperatorFromCrm(\boolean|\Bitrix\Main\DB\SqlExpression $operatorFromCrm)
	 * @method bool hasOperatorFromCrm()
	 * @method bool isOperatorFromCrmFilled()
	 * @method bool isOperatorFromCrmChanged()
	 * @method \boolean remindActualOperatorFromCrm()
	 * @method \boolean requireOperatorFromCrm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetOperatorFromCrm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetOperatorFromCrm()
	 * @method \boolean fillOperatorFromCrm()
	 * @method \Bitrix\Main\EO_User getOperator()
	 * @method \Bitrix\Main\EO_User remindActualOperator()
	 * @method \Bitrix\Main\EO_User requireOperator()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setOperator(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetOperator()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetOperator()
	 * @method bool hasOperator()
	 * @method bool isOperatorFilled()
	 * @method bool isOperatorChanged()
	 * @method \Bitrix\Main\EO_User fillOperator()
	 * @method \string getUserCode()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setUserCode(\string|\Bitrix\Main\DB\SqlExpression $userCode)
	 * @method bool hasUserCode()
	 * @method bool isUserCodeFilled()
	 * @method bool isUserCodeChanged()
	 * @method \string remindActualUserCode()
	 * @method \string requireUserCode()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetUserCode()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetUserCode()
	 * @method \string fillUserCode()
	 * @method \int getChatId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setChatId(\int|\Bitrix\Main\DB\SqlExpression $chatId)
	 * @method bool hasChatId()
	 * @method bool isChatIdFilled()
	 * @method bool isChatIdChanged()
	 * @method \int remindActualChatId()
	 * @method \int requireChatId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetChatId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetChatId()
	 * @method \int fillChatId()
	 * @method \int getMessageCount()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setMessageCount(\int|\Bitrix\Main\DB\SqlExpression $messageCount)
	 * @method bool hasMessageCount()
	 * @method bool isMessageCountFilled()
	 * @method bool isMessageCountChanged()
	 * @method \int remindActualMessageCount()
	 * @method \int requireMessageCount()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetMessageCount()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetMessageCount()
	 * @method \int fillMessageCount()
	 * @method \int getLikeCount()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setLikeCount(\int|\Bitrix\Main\DB\SqlExpression $likeCount)
	 * @method bool hasLikeCount()
	 * @method bool isLikeCountFilled()
	 * @method bool isLikeCountChanged()
	 * @method \int remindActualLikeCount()
	 * @method \int requireLikeCount()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetLikeCount()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetLikeCount()
	 * @method \int fillLikeCount()
	 * @method \int getStartId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setStartId(\int|\Bitrix\Main\DB\SqlExpression $startId)
	 * @method bool hasStartId()
	 * @method bool isStartIdFilled()
	 * @method bool isStartIdChanged()
	 * @method \int remindActualStartId()
	 * @method \int requireStartId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetStartId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetStartId()
	 * @method \int fillStartId()
	 * @method \int getEndId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setEndId(\int|\Bitrix\Main\DB\SqlExpression $endId)
	 * @method bool hasEndId()
	 * @method bool isEndIdFilled()
	 * @method bool isEndIdChanged()
	 * @method \int remindActualEndId()
	 * @method \int requireEndId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetEndId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetEndId()
	 * @method \int fillEndId()
	 * @method \boolean getCrm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCrm(\boolean|\Bitrix\Main\DB\SqlExpression $crm)
	 * @method bool hasCrm()
	 * @method bool isCrmFilled()
	 * @method bool isCrmChanged()
	 * @method \boolean remindActualCrm()
	 * @method \boolean requireCrm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCrm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCrm()
	 * @method \boolean fillCrm()
	 * @method \boolean getCrmCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCrmCreate(\boolean|\Bitrix\Main\DB\SqlExpression $crmCreate)
	 * @method bool hasCrmCreate()
	 * @method bool isCrmCreateFilled()
	 * @method bool isCrmCreateChanged()
	 * @method \boolean remindActualCrmCreate()
	 * @method \boolean requireCrmCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCrmCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCrmCreate()
	 * @method \boolean fillCrmCreate()
	 * @method \boolean getCrmCreateLead()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCrmCreateLead(\boolean|\Bitrix\Main\DB\SqlExpression $crmCreateLead)
	 * @method bool hasCrmCreateLead()
	 * @method bool isCrmCreateLeadFilled()
	 * @method bool isCrmCreateLeadChanged()
	 * @method \boolean remindActualCrmCreateLead()
	 * @method \boolean requireCrmCreateLead()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCrmCreateLead()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCrmCreateLead()
	 * @method \boolean fillCrmCreateLead()
	 * @method \boolean getCrmCreateCompany()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCrmCreateCompany(\boolean|\Bitrix\Main\DB\SqlExpression $crmCreateCompany)
	 * @method bool hasCrmCreateCompany()
	 * @method bool isCrmCreateCompanyFilled()
	 * @method bool isCrmCreateCompanyChanged()
	 * @method \boolean remindActualCrmCreateCompany()
	 * @method \boolean requireCrmCreateCompany()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCrmCreateCompany()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCrmCreateCompany()
	 * @method \boolean fillCrmCreateCompany()
	 * @method \boolean getCrmCreateContact()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCrmCreateContact(\boolean|\Bitrix\Main\DB\SqlExpression $crmCreateContact)
	 * @method bool hasCrmCreateContact()
	 * @method bool isCrmCreateContactFilled()
	 * @method bool isCrmCreateContactChanged()
	 * @method \boolean remindActualCrmCreateContact()
	 * @method \boolean requireCrmCreateContact()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCrmCreateContact()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCrmCreateContact()
	 * @method \boolean fillCrmCreateContact()
	 * @method \boolean getCrmCreateDeal()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCrmCreateDeal(\boolean|\Bitrix\Main\DB\SqlExpression $crmCreateDeal)
	 * @method bool hasCrmCreateDeal()
	 * @method bool isCrmCreateDealFilled()
	 * @method bool isCrmCreateDealChanged()
	 * @method \boolean remindActualCrmCreateDeal()
	 * @method \boolean requireCrmCreateDeal()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCrmCreateDeal()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCrmCreateDeal()
	 * @method \boolean fillCrmCreateDeal()
	 * @method \int getCrmActivityId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCrmActivityId(\int|\Bitrix\Main\DB\SqlExpression $crmActivityId)
	 * @method bool hasCrmActivityId()
	 * @method bool isCrmActivityIdFilled()
	 * @method bool isCrmActivityIdChanged()
	 * @method \int remindActualCrmActivityId()
	 * @method \int requireCrmActivityId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCrmActivityId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCrmActivityId()
	 * @method \int fillCrmActivityId()
	 * @method \string getCrmTraceData()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCrmTraceData(\string|\Bitrix\Main\DB\SqlExpression $crmTraceData)
	 * @method bool hasCrmTraceData()
	 * @method bool isCrmTraceDataFilled()
	 * @method bool isCrmTraceDataChanged()
	 * @method \string remindActualCrmTraceData()
	 * @method \string requireCrmTraceData()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCrmTraceData()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCrmTraceData()
	 * @method \string fillCrmTraceData()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateCreate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime getDateOperator()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateOperator(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateOperator)
	 * @method bool hasDateOperator()
	 * @method bool isDateOperatorFilled()
	 * @method bool isDateOperatorChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateOperator()
	 * @method \Bitrix\Main\Type\DateTime requireDateOperator()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateOperator()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateOperator()
	 * @method \Bitrix\Main\Type\DateTime fillDateOperator()
	 * @method \Bitrix\Main\Type\DateTime getDateModify()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateModify(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateModify)
	 * @method bool hasDateModify()
	 * @method bool isDateModifyFilled()
	 * @method bool isDateModifyChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateModify()
	 * @method \Bitrix\Main\Type\DateTime requireDateModify()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateModify()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateModify()
	 * @method \Bitrix\Main\Type\DateTime fillDateModify()
	 * @method \Bitrix\Main\Type\DateTime getDateOperatorAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateOperatorAnswer(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateOperatorAnswer)
	 * @method bool hasDateOperatorAnswer()
	 * @method bool isDateOperatorAnswerFilled()
	 * @method bool isDateOperatorAnswerChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateOperatorAnswer()
	 * @method \Bitrix\Main\Type\DateTime requireDateOperatorAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateOperatorAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateOperatorAnswer()
	 * @method \Bitrix\Main\Type\DateTime fillDateOperatorAnswer()
	 * @method \Bitrix\Main\Type\DateTime getDateOperatorClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateOperatorClose(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateOperatorClose)
	 * @method bool hasDateOperatorClose()
	 * @method bool isDateOperatorCloseFilled()
	 * @method bool isDateOperatorCloseChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateOperatorClose()
	 * @method \Bitrix\Main\Type\DateTime requireDateOperatorClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateOperatorClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateOperatorClose()
	 * @method \Bitrix\Main\Type\DateTime fillDateOperatorClose()
	 * @method \Bitrix\Main\Type\DateTime getDateFirstAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateFirstAnswer(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateFirstAnswer)
	 * @method bool hasDateFirstAnswer()
	 * @method bool isDateFirstAnswerFilled()
	 * @method bool isDateFirstAnswerChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateFirstAnswer()
	 * @method \Bitrix\Main\Type\DateTime requireDateFirstAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateFirstAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateFirstAnswer()
	 * @method \Bitrix\Main\Type\DateTime fillDateFirstAnswer()
	 * @method \Bitrix\Main\Type\DateTime getDateLastMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateLastMessage(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateLastMessage)
	 * @method bool hasDateLastMessage()
	 * @method bool isDateLastMessageFilled()
	 * @method bool isDateLastMessageChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateLastMessage()
	 * @method \Bitrix\Main\Type\DateTime requireDateLastMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateLastMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateLastMessage()
	 * @method \Bitrix\Main\Type\DateTime fillDateLastMessage()
	 * @method \Bitrix\Main\Type\DateTime getDateFirstLastUserAction()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateFirstLastUserAction(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateFirstLastUserAction)
	 * @method bool hasDateFirstLastUserAction()
	 * @method bool isDateFirstLastUserActionFilled()
	 * @method bool isDateFirstLastUserActionChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateFirstLastUserAction()
	 * @method \Bitrix\Main\Type\DateTime requireDateFirstLastUserAction()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateFirstLastUserAction()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateFirstLastUserAction()
	 * @method \Bitrix\Main\Type\DateTime fillDateFirstLastUserAction()
	 * @method \Bitrix\Main\Type\DateTime getDateClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateClose(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateClose)
	 * @method bool hasDateClose()
	 * @method bool isDateCloseFilled()
	 * @method bool isDateCloseChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateClose()
	 * @method \Bitrix\Main\Type\DateTime requireDateClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateClose()
	 * @method \Bitrix\Main\Type\DateTime fillDateClose()
	 * @method \Bitrix\Main\Type\DateTime getDateCloseVote()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setDateCloseVote(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCloseVote)
	 * @method bool hasDateCloseVote()
	 * @method bool isDateCloseVoteFilled()
	 * @method bool isDateCloseVoteChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCloseVote()
	 * @method \Bitrix\Main\Type\DateTime requireDateCloseVote()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetDateCloseVote()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetDateCloseVote()
	 * @method \Bitrix\Main\Type\DateTime fillDateCloseVote()
	 * @method \int getTimeBot()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setTimeBot(\int|\Bitrix\Main\DB\SqlExpression $timeBot)
	 * @method bool hasTimeBot()
	 * @method bool isTimeBotFilled()
	 * @method bool isTimeBotChanged()
	 * @method \int remindActualTimeBot()
	 * @method \int requireTimeBot()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetTimeBot()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetTimeBot()
	 * @method \int fillTimeBot()
	 * @method \int getTimeFirstAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setTimeFirstAnswer(\int|\Bitrix\Main\DB\SqlExpression $timeFirstAnswer)
	 * @method bool hasTimeFirstAnswer()
	 * @method bool isTimeFirstAnswerFilled()
	 * @method bool isTimeFirstAnswerChanged()
	 * @method \int remindActualTimeFirstAnswer()
	 * @method \int requireTimeFirstAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetTimeFirstAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetTimeFirstAnswer()
	 * @method \int fillTimeFirstAnswer()
	 * @method \int getTimeAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setTimeAnswer(\int|\Bitrix\Main\DB\SqlExpression $timeAnswer)
	 * @method bool hasTimeAnswer()
	 * @method bool isTimeAnswerFilled()
	 * @method bool isTimeAnswerChanged()
	 * @method \int remindActualTimeAnswer()
	 * @method \int requireTimeAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetTimeAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetTimeAnswer()
	 * @method \int fillTimeAnswer()
	 * @method \int getTimeClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setTimeClose(\int|\Bitrix\Main\DB\SqlExpression $timeClose)
	 * @method bool hasTimeClose()
	 * @method bool isTimeCloseFilled()
	 * @method bool isTimeCloseChanged()
	 * @method \int remindActualTimeClose()
	 * @method \int requireTimeClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetTimeClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetTimeClose()
	 * @method \int fillTimeClose()
	 * @method \int getTimeDialog()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setTimeDialog(\int|\Bitrix\Main\DB\SqlExpression $timeDialog)
	 * @method bool hasTimeDialog()
	 * @method bool isTimeDialogFilled()
	 * @method bool isTimeDialogChanged()
	 * @method \int remindActualTimeDialog()
	 * @method \int requireTimeDialog()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetTimeDialog()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetTimeDialog()
	 * @method \int fillTimeDialog()
	 * @method \boolean getWaitAction()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setWaitAction(\boolean|\Bitrix\Main\DB\SqlExpression $waitAction)
	 * @method bool hasWaitAction()
	 * @method bool isWaitActionFilled()
	 * @method bool isWaitActionChanged()
	 * @method \boolean remindActualWaitAction()
	 * @method \boolean requireWaitAction()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetWaitAction()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetWaitAction()
	 * @method \boolean fillWaitAction()
	 * @method \boolean getWaitVote()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setWaitVote(\boolean|\Bitrix\Main\DB\SqlExpression $waitVote)
	 * @method bool hasWaitVote()
	 * @method bool isWaitVoteFilled()
	 * @method bool isWaitVoteChanged()
	 * @method \boolean remindActualWaitVote()
	 * @method \boolean requireWaitVote()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetWaitVote()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetWaitVote()
	 * @method \boolean fillWaitVote()
	 * @method \boolean getWaitAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setWaitAnswer(\boolean|\Bitrix\Main\DB\SqlExpression $waitAnswer)
	 * @method bool hasWaitAnswer()
	 * @method bool isWaitAnswerFilled()
	 * @method bool isWaitAnswerChanged()
	 * @method \boolean remindActualWaitAnswer()
	 * @method \boolean requireWaitAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetWaitAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetWaitAnswer()
	 * @method \boolean fillWaitAnswer()
	 * @method \boolean getClosed()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setClosed(\boolean|\Bitrix\Main\DB\SqlExpression $closed)
	 * @method bool hasClosed()
	 * @method bool isClosedFilled()
	 * @method bool isClosedChanged()
	 * @method \boolean remindActualClosed()
	 * @method \boolean requireClosed()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetClosed()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetClosed()
	 * @method \boolean fillClosed()
	 * @method \boolean getPause()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setPause(\boolean|\Bitrix\Main\DB\SqlExpression $pause)
	 * @method bool hasPause()
	 * @method bool isPauseFilled()
	 * @method bool isPauseChanged()
	 * @method \boolean remindActualPause()
	 * @method \boolean requirePause()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetPause()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetPause()
	 * @method \boolean fillPause()
	 * @method \boolean getSpam()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setSpam(\boolean|\Bitrix\Main\DB\SqlExpression $spam)
	 * @method bool hasSpam()
	 * @method bool isSpamFilled()
	 * @method bool isSpamChanged()
	 * @method \boolean remindActualSpam()
	 * @method \boolean requireSpam()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetSpam()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetSpam()
	 * @method \boolean fillSpam()
	 * @method \boolean getWorktime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setWorktime(\boolean|\Bitrix\Main\DB\SqlExpression $worktime)
	 * @method bool hasWorktime()
	 * @method bool isWorktimeFilled()
	 * @method bool isWorktimeChanged()
	 * @method \boolean remindActualWorktime()
	 * @method \boolean requireWorktime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetWorktime()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetWorktime()
	 * @method \boolean fillWorktime()
	 * @method \boolean getSendNoAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setSendNoAnswerText(\boolean|\Bitrix\Main\DB\SqlExpression $sendNoAnswerText)
	 * @method bool hasSendNoAnswerText()
	 * @method bool isSendNoAnswerTextFilled()
	 * @method bool isSendNoAnswerTextChanged()
	 * @method \boolean remindActualSendNoAnswerText()
	 * @method \boolean requireSendNoAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetSendNoAnswerText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetSendNoAnswerText()
	 * @method \boolean fillSendNoAnswerText()
	 * @method \boolean getSendNoWorkTimeText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setSendNoWorkTimeText(\boolean|\Bitrix\Main\DB\SqlExpression $sendNoWorkTimeText)
	 * @method bool hasSendNoWorkTimeText()
	 * @method bool isSendNoWorkTimeTextFilled()
	 * @method bool isSendNoWorkTimeTextChanged()
	 * @method \boolean remindActualSendNoWorkTimeText()
	 * @method \boolean requireSendNoWorkTimeText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetSendNoWorkTimeText()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetSendNoWorkTimeText()
	 * @method \boolean fillSendNoWorkTimeText()
	 * @method \string getQueueHistory()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setQueueHistory(\string|\Bitrix\Main\DB\SqlExpression $queueHistory)
	 * @method bool hasQueueHistory()
	 * @method bool isQueueHistoryFilled()
	 * @method bool isQueueHistoryChanged()
	 * @method \string remindActualQueueHistory()
	 * @method \string requireQueueHistory()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetQueueHistory()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetQueueHistory()
	 * @method \string fillQueueHistory()
	 * @method \string getBlockReason()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setBlockReason(\string|\Bitrix\Main\DB\SqlExpression $blockReason)
	 * @method bool hasBlockReason()
	 * @method bool isBlockReasonFilled()
	 * @method bool isBlockReasonChanged()
	 * @method \string remindActualBlockReason()
	 * @method \string requireBlockReason()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetBlockReason()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetBlockReason()
	 * @method \string fillBlockReason()
	 * @method \Bitrix\Main\Type\DateTime getBlockDate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setBlockDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $blockDate)
	 * @method bool hasBlockDate()
	 * @method bool isBlockDateFilled()
	 * @method bool isBlockDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualBlockDate()
	 * @method \Bitrix\Main\Type\DateTime requireBlockDate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetBlockDate()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetBlockDate()
	 * @method \Bitrix\Main\Type\DateTime fillBlockDate()
	 * @method \int getVote()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setVote(\int|\Bitrix\Main\DB\SqlExpression $vote)
	 * @method bool hasVote()
	 * @method bool isVoteFilled()
	 * @method bool isVoteChanged()
	 * @method \int remindActualVote()
	 * @method \int requireVote()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetVote()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetVote()
	 * @method \int fillVote()
	 * @method \int getVoteHead()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setVoteHead(\int|\Bitrix\Main\DB\SqlExpression $voteHead)
	 * @method bool hasVoteHead()
	 * @method bool isVoteHeadFilled()
	 * @method bool isVoteHeadChanged()
	 * @method \int remindActualVoteHead()
	 * @method \int requireVoteHead()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetVoteHead()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetVoteHead()
	 * @method \int fillVoteHead()
	 * @method \string getCommentHead()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCommentHead(\string|\Bitrix\Main\DB\SqlExpression $commentHead)
	 * @method bool hasCommentHead()
	 * @method bool isCommentHeadFilled()
	 * @method bool isCommentHeadChanged()
	 * @method \string remindActualCommentHead()
	 * @method \string requireCommentHead()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCommentHead()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCommentHead()
	 * @method \string fillCommentHead()
	 * @method \int getCategoryId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCategoryId(\int|\Bitrix\Main\DB\SqlExpression $categoryId)
	 * @method bool hasCategoryId()
	 * @method bool isCategoryIdFilled()
	 * @method bool isCategoryIdChanged()
	 * @method \int remindActualCategoryId()
	 * @method \int requireCategoryId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCategoryId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCategoryId()
	 * @method \int fillCategoryId()
	 * @method \int getExtraRegister()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setExtraRegister(\int|\Bitrix\Main\DB\SqlExpression $extraRegister)
	 * @method bool hasExtraRegister()
	 * @method bool isExtraRegisterFilled()
	 * @method bool isExtraRegisterChanged()
	 * @method \int remindActualExtraRegister()
	 * @method \int requireExtraRegister()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetExtraRegister()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetExtraRegister()
	 * @method \int fillExtraRegister()
	 * @method \string getExtraUserLevel()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setExtraUserLevel(\string|\Bitrix\Main\DB\SqlExpression $extraUserLevel)
	 * @method bool hasExtraUserLevel()
	 * @method bool isExtraUserLevelFilled()
	 * @method bool isExtraUserLevelChanged()
	 * @method \string remindActualExtraUserLevel()
	 * @method \string requireExtraUserLevel()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetExtraUserLevel()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetExtraUserLevel()
	 * @method \string fillExtraUserLevel()
	 * @method \string getExtraPortalType()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setExtraPortalType(\string|\Bitrix\Main\DB\SqlExpression $extraPortalType)
	 * @method bool hasExtraPortalType()
	 * @method bool isExtraPortalTypeFilled()
	 * @method bool isExtraPortalTypeChanged()
	 * @method \string remindActualExtraPortalType()
	 * @method \string requireExtraPortalType()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetExtraPortalType()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetExtraPortalType()
	 * @method \string fillExtraPortalType()
	 * @method \string getExtraTariff()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setExtraTariff(\string|\Bitrix\Main\DB\SqlExpression $extraTariff)
	 * @method bool hasExtraTariff()
	 * @method bool isExtraTariffFilled()
	 * @method bool isExtraTariffChanged()
	 * @method \string remindActualExtraTariff()
	 * @method \string requireExtraTariff()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetExtraTariff()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetExtraTariff()
	 * @method \string fillExtraTariff()
	 * @method \string getExtraUrl()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setExtraUrl(\string|\Bitrix\Main\DB\SqlExpression $extraUrl)
	 * @method bool hasExtraUrl()
	 * @method bool isExtraUrlFilled()
	 * @method bool isExtraUrlChanged()
	 * @method \string remindActualExtraUrl()
	 * @method \string requireExtraUrl()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetExtraUrl()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetExtraUrl()
	 * @method \string fillExtraUrl()
	 * @method \string getSendForm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setSendForm(\string|\Bitrix\Main\DB\SqlExpression $sendForm)
	 * @method bool hasSendForm()
	 * @method bool isSendFormFilled()
	 * @method bool isSendFormChanged()
	 * @method \string remindActualSendForm()
	 * @method \string requireSendForm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetSendForm()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetSendForm()
	 * @method \string fillSendForm()
	 * @method \boolean getSendHistory()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setSendHistory(\boolean|\Bitrix\Main\DB\SqlExpression $sendHistory)
	 * @method bool hasSendHistory()
	 * @method bool isSendHistoryFilled()
	 * @method bool isSendHistoryChanged()
	 * @method \boolean remindActualSendHistory()
	 * @method \boolean requireSendHistory()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetSendHistory()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetSendHistory()
	 * @method \boolean fillSendHistory()
	 * @method \int getParentId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetParentId()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetParentId()
	 * @method \int fillParentId()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex getIndex()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex remindActualIndex()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex requireIndex()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setIndex(\Bitrix\Imopenlines\Model\EO_SessionIndex $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetIndex()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetIndex()
	 * @method bool hasIndex()
	 * @method bool isIndexFilled()
	 * @method bool isIndexChanged()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex fillIndex()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config getConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config remindActualConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config requireConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setConfig(\Bitrix\ImOpenLines\Model\EO_Config $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetConfig()
	 * @method bool hasConfig()
	 * @method bool isConfigFilled()
	 * @method bool isConfigChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config fillConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck getCheck()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck remindActualCheck()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck requireCheck()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setCheck(\Bitrix\ImOpenLines\Model\EO_SessionCheck $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetCheck()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetCheck()
	 * @method bool hasCheck()
	 * @method bool isCheckFilled()
	 * @method bool isCheckChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck fillCheck()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages getKpiMessages()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages remindActualKpiMessages()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages requireKpiMessages()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setKpiMessages(\Bitrix\ImOpenLines\Model\EO_SessionKpiMessages $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetKpiMessages()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetKpiMessages()
	 * @method bool hasKpiMessages()
	 * @method bool isKpiMessagesFilled()
	 * @method bool isKpiMessagesChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages fillKpiMessages()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat getLivechat()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat remindActualLivechat()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat requireLivechat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setLivechat(\Bitrix\Imopenlines\Model\EO_Livechat $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetLivechat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetLivechat()
	 * @method bool hasLivechat()
	 * @method bool isLivechatFilled()
	 * @method bool isLivechatChanged()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat fillLivechat()
	 * @method \boolean getIsFirst()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setIsFirst(\boolean|\Bitrix\Main\DB\SqlExpression $isFirst)
	 * @method bool hasIsFirst()
	 * @method bool isIsFirstFilled()
	 * @method bool isIsFirstChanged()
	 * @method \boolean remindActualIsFirst()
	 * @method \boolean requireIsFirst()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetIsFirst()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetIsFirst()
	 * @method \boolean fillIsFirst()
	 * @method \Bitrix\Im\Model\EO_Chat getChat()
	 * @method \Bitrix\Im\Model\EO_Chat remindActualChat()
	 * @method \Bitrix\Im\Model\EO_Chat requireChat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session setChat(\Bitrix\Im\Model\EO_Chat $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session resetChat()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unsetChat()
	 * @method bool hasChat()
	 * @method bool isChatFilled()
	 * @method bool isChatChanged()
	 * @method \Bitrix\Im\Model\EO_Chat fillChat()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_Session set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_Session wakeUp($data)
	 */
	class EO_Session {
		/* @var \Bitrix\ImOpenLines\Model\SessionTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\SessionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_Session_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getModeList()
	 * @method \string[] fillMode()
	 * @method \string[] getSourceList()
	 * @method \string[] fillSource()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \int[] getConfigIdList()
	 * @method \int[] fillConfigId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \int[] getOperatorIdList()
	 * @method \int[] fillOperatorId()
	 * @method \boolean[] getOperatorFromCrmList()
	 * @method \boolean[] fillOperatorFromCrm()
	 * @method \Bitrix\Main\EO_User[] getOperatorList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection getOperatorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillOperator()
	 * @method \string[] getUserCodeList()
	 * @method \string[] fillUserCode()
	 * @method \int[] getChatIdList()
	 * @method \int[] fillChatId()
	 * @method \int[] getMessageCountList()
	 * @method \int[] fillMessageCount()
	 * @method \int[] getLikeCountList()
	 * @method \int[] fillLikeCount()
	 * @method \int[] getStartIdList()
	 * @method \int[] fillStartId()
	 * @method \int[] getEndIdList()
	 * @method \int[] fillEndId()
	 * @method \boolean[] getCrmList()
	 * @method \boolean[] fillCrm()
	 * @method \boolean[] getCrmCreateList()
	 * @method \boolean[] fillCrmCreate()
	 * @method \boolean[] getCrmCreateLeadList()
	 * @method \boolean[] fillCrmCreateLead()
	 * @method \boolean[] getCrmCreateCompanyList()
	 * @method \boolean[] fillCrmCreateCompany()
	 * @method \boolean[] getCrmCreateContactList()
	 * @method \boolean[] fillCrmCreateContact()
	 * @method \boolean[] getCrmCreateDealList()
	 * @method \boolean[] fillCrmCreateDeal()
	 * @method \int[] getCrmActivityIdList()
	 * @method \int[] fillCrmActivityId()
	 * @method \string[] getCrmTraceDataList()
	 * @method \string[] fillCrmTraceData()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateOperatorList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateOperator()
	 * @method \Bitrix\Main\Type\DateTime[] getDateModifyList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateModify()
	 * @method \Bitrix\Main\Type\DateTime[] getDateOperatorAnswerList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateOperatorAnswer()
	 * @method \Bitrix\Main\Type\DateTime[] getDateOperatorCloseList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateOperatorClose()
	 * @method \Bitrix\Main\Type\DateTime[] getDateFirstAnswerList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateFirstAnswer()
	 * @method \Bitrix\Main\Type\DateTime[] getDateLastMessageList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateLastMessage()
	 * @method \Bitrix\Main\Type\DateTime[] getDateFirstLastUserActionList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateFirstLastUserAction()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCloseList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateClose()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCloseVoteList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCloseVote()
	 * @method \int[] getTimeBotList()
	 * @method \int[] fillTimeBot()
	 * @method \int[] getTimeFirstAnswerList()
	 * @method \int[] fillTimeFirstAnswer()
	 * @method \int[] getTimeAnswerList()
	 * @method \int[] fillTimeAnswer()
	 * @method \int[] getTimeCloseList()
	 * @method \int[] fillTimeClose()
	 * @method \int[] getTimeDialogList()
	 * @method \int[] fillTimeDialog()
	 * @method \boolean[] getWaitActionList()
	 * @method \boolean[] fillWaitAction()
	 * @method \boolean[] getWaitVoteList()
	 * @method \boolean[] fillWaitVote()
	 * @method \boolean[] getWaitAnswerList()
	 * @method \boolean[] fillWaitAnswer()
	 * @method \boolean[] getClosedList()
	 * @method \boolean[] fillClosed()
	 * @method \boolean[] getPauseList()
	 * @method \boolean[] fillPause()
	 * @method \boolean[] getSpamList()
	 * @method \boolean[] fillSpam()
	 * @method \boolean[] getWorktimeList()
	 * @method \boolean[] fillWorktime()
	 * @method \boolean[] getSendNoAnswerTextList()
	 * @method \boolean[] fillSendNoAnswerText()
	 * @method \boolean[] getSendNoWorkTimeTextList()
	 * @method \boolean[] fillSendNoWorkTimeText()
	 * @method \string[] getQueueHistoryList()
	 * @method \string[] fillQueueHistory()
	 * @method \string[] getBlockReasonList()
	 * @method \string[] fillBlockReason()
	 * @method \Bitrix\Main\Type\DateTime[] getBlockDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillBlockDate()
	 * @method \int[] getVoteList()
	 * @method \int[] fillVote()
	 * @method \int[] getVoteHeadList()
	 * @method \int[] fillVoteHead()
	 * @method \string[] getCommentHeadList()
	 * @method \string[] fillCommentHead()
	 * @method \int[] getCategoryIdList()
	 * @method \int[] fillCategoryId()
	 * @method \int[] getExtraRegisterList()
	 * @method \int[] fillExtraRegister()
	 * @method \string[] getExtraUserLevelList()
	 * @method \string[] fillExtraUserLevel()
	 * @method \string[] getExtraPortalTypeList()
	 * @method \string[] fillExtraPortalType()
	 * @method \string[] getExtraTariffList()
	 * @method \string[] fillExtraTariff()
	 * @method \string[] getExtraUrlList()
	 * @method \string[] fillExtraUrl()
	 * @method \string[] getSendFormList()
	 * @method \string[] fillSendForm()
	 * @method \boolean[] getSendHistoryList()
	 * @method \boolean[] fillSendHistory()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex[] getIndexList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection getIndexCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex_Collection fillIndex()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config[] getConfigList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection getConfigCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Config_Collection fillConfig()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck[] getCheckList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection getCheckCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck_Collection fillCheck()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages[] getKpiMessagesList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection getKpiMessagesCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages_Collection fillKpiMessages()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat[] getLivechatList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection getLivechatCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_Livechat_Collection fillLivechat()
	 * @method \boolean[] getIsFirstList()
	 * @method \boolean[] fillIsFirst()
	 * @method \Bitrix\Im\Model\EO_Chat[] getChatList()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection getChatCollection()
	 * @method \Bitrix\Im\Model\EO_Chat_Collection fillChat()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_Session $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_Session $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_Session $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_Session_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_Session current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Session_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\SessionTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\SessionTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Session_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Session_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Session fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection fetchCollection()
	 */
	class EO_Session_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_Session createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection wakeUpCollection($rows)
	 */
	class EO_Session_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable:imopenlines/lib/model/sessionautomatictasks.php:77f5c0820b7e1161907f856b19fa4f79 */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_SessionAutomaticTasks
	 * @see \Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getConfigAutomaticMessageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks setConfigAutomaticMessageId(\int|\Bitrix\Main\DB\SqlExpression $configAutomaticMessageId)
	 * @method bool hasConfigAutomaticMessageId()
	 * @method bool isConfigAutomaticMessageIdFilled()
	 * @method bool isConfigAutomaticMessageIdChanged()
	 * @method \int remindActualConfigAutomaticMessageId()
	 * @method \int requireConfigAutomaticMessageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks resetConfigAutomaticMessageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks unsetConfigAutomaticMessageId()
	 * @method \int fillConfigAutomaticMessageId()
	 * @method \int getSessionId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks resetSessionId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \Bitrix\Main\Type\DateTime getDateTask()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks setDateTask(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateTask)
	 * @method bool hasDateTask()
	 * @method bool isDateTaskFilled()
	 * @method bool isDateTaskChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateTask()
	 * @method \Bitrix\Main\Type\DateTime requireDateTask()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks resetDateTask()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks unsetDateTask()
	 * @method \Bitrix\Main\Type\DateTime fillDateTask()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session getSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session remindActualSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session requireSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks setSession(\Bitrix\ImOpenLines\Model\EO_Session $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks resetSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks unsetSession()
	 * @method bool hasSession()
	 * @method bool isSessionFilled()
	 * @method bool isSessionChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session fillSession()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages getConfigAutomaticMessage()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages remindActualConfigAutomaticMessage()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages requireConfigAutomaticMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks setConfigAutomaticMessage(\Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks resetConfigAutomaticMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks unsetConfigAutomaticMessage()
	 * @method bool hasConfigAutomaticMessage()
	 * @method bool isConfigAutomaticMessageFilled()
	 * @method bool isConfigAutomaticMessageChanged()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages fillConfigAutomaticMessage()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks wakeUp($data)
	 */
	class EO_SessionAutomaticTasks {
		/* @var \Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_SessionAutomaticTasks_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getConfigAutomaticMessageIdList()
	 * @method \int[] fillConfigAutomaticMessageId()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateTaskList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateTask()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session[] getSessionList()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks_Collection getSessionCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection fillSession()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages[] getConfigAutomaticMessageList()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks_Collection getConfigAutomaticMessageCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_ConfigAutomaticMessages_Collection fillConfigAutomaticMessage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_SessionAutomaticTasks_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SessionAutomaticTasks_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_SessionAutomaticTasks_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks_Collection fetchCollection()
	 */
	class EO_SessionAutomaticTasks_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks_Collection wakeUpCollection($rows)
	 */
	class EO_SessionAutomaticTasks_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\SessionCheckTable:imopenlines/lib/model/sessioncheck.php:eb03d645c1e80aecd16e954945703ad4 */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_SessionCheck
	 * @see \Bitrix\ImOpenLines\Model\SessionCheckTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getSessionId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getDateClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck setDateClose(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateClose)
	 * @method bool hasDateClose()
	 * @method bool isDateCloseFilled()
	 * @method bool isDateCloseChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateClose()
	 * @method \Bitrix\Main\Type\DateTime requireDateClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck resetDateClose()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck unsetDateClose()
	 * @method \Bitrix\Main\Type\DateTime fillDateClose()
	 * @method \Bitrix\Main\Type\DateTime getDateQueue()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck setDateQueue(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateQueue)
	 * @method bool hasDateQueue()
	 * @method bool isDateQueueFilled()
	 * @method bool isDateQueueChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateQueue()
	 * @method \Bitrix\Main\Type\DateTime requireDateQueue()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck resetDateQueue()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck unsetDateQueue()
	 * @method \Bitrix\Main\Type\DateTime fillDateQueue()
	 * @method \Bitrix\Main\Type\DateTime getDateMail()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck setDateMail(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateMail)
	 * @method bool hasDateMail()
	 * @method bool isDateMailFilled()
	 * @method bool isDateMailChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateMail()
	 * @method \Bitrix\Main\Type\DateTime requireDateMail()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck resetDateMail()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck unsetDateMail()
	 * @method \Bitrix\Main\Type\DateTime fillDateMail()
	 * @method \Bitrix\Main\Type\DateTime getDateNoAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck setDateNoAnswer(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateNoAnswer)
	 * @method bool hasDateNoAnswer()
	 * @method bool isDateNoAnswerFilled()
	 * @method bool isDateNoAnswerChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateNoAnswer()
	 * @method \Bitrix\Main\Type\DateTime requireDateNoAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck resetDateNoAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck unsetDateNoAnswer()
	 * @method \Bitrix\Main\Type\DateTime fillDateNoAnswer()
	 * @method \string getReasonReturn()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck setReasonReturn(\string|\Bitrix\Main\DB\SqlExpression $reasonReturn)
	 * @method bool hasReasonReturn()
	 * @method bool isReasonReturnFilled()
	 * @method bool isReasonReturnChanged()
	 * @method \string remindActualReasonReturn()
	 * @method \string requireReasonReturn()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck resetReasonReturn()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck unsetReasonReturn()
	 * @method \string fillReasonReturn()
	 * @method \boolean getUndistributed()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck setUndistributed(\boolean|\Bitrix\Main\DB\SqlExpression $undistributed)
	 * @method bool hasUndistributed()
	 * @method bool isUndistributedFilled()
	 * @method bool isUndistributedChanged()
	 * @method \boolean remindActualUndistributed()
	 * @method \boolean requireUndistributed()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck resetUndistributed()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck unsetUndistributed()
	 * @method \boolean fillUndistributed()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session getSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session remindActualSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session requireSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck setSession(\Bitrix\ImOpenLines\Model\EO_Session $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck resetSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck unsetSession()
	 * @method bool hasSession()
	 * @method bool isSessionFilled()
	 * @method bool isSessionChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session fillSession()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_SessionCheck wakeUp($data)
	 */
	class EO_SessionCheck {
		/* @var \Bitrix\ImOpenLines\Model\SessionCheckTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\SessionCheckTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_SessionCheck_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getSessionIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCloseList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateClose()
	 * @method \Bitrix\Main\Type\DateTime[] getDateQueueList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateQueue()
	 * @method \Bitrix\Main\Type\DateTime[] getDateMailList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateMail()
	 * @method \Bitrix\Main\Type\DateTime[] getDateNoAnswerList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateNoAnswer()
	 * @method \string[] getReasonReturnList()
	 * @method \string[] fillReasonReturn()
	 * @method \boolean[] getUndistributedList()
	 * @method \boolean[] fillUndistributed()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session[] getSessionList()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck_Collection getSessionCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection fillSession()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_SessionCheck $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_SessionCheck $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_SessionCheck $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_SessionCheck_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_SessionCheck_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\SessionCheckTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\SessionCheckTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SessionCheck_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_SessionCheck_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck_Collection fetchCollection()
	 */
	class EO_SessionCheck_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionCheck_Collection wakeUpCollection($rows)
	 */
	class EO_SessionCheck_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\SessionIndexTable:imopenlines/lib/model/sessionindex.php:0e7518d8a1224f8a2a361f6f192c8eb4 */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_SessionIndex
	 * @see \Bitrix\Imopenlines\Model\SessionIndexTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \string getSearchContent()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex setSearchContent(\string|\Bitrix\Main\DB\SqlExpression $searchContent)
	 * @method bool hasSearchContent()
	 * @method bool isSearchContentFilled()
	 * @method bool isSearchContentChanged()
	 * @method \string remindActualSearchContent()
	 * @method \string requireSearchContent()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex resetSearchContent()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex unsetSearchContent()
	 * @method \string fillSearchContent()
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
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_SessionIndex wakeUp($data)
	 */
	class EO_SessionIndex {
		/* @var \Bitrix\Imopenlines\Model\SessionIndexTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\SessionIndexTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_SessionIndex_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getSessionIdList()
	 * @method \string[] getSearchContentList()
	 * @method \string[] fillSearchContent()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_SessionIndex $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_SessionIndex $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_SessionIndex $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_SessionIndex_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_SessionIndex_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\SessionIndexTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\SessionIndexTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SessionIndex_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_SessionIndex_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex_Collection fetchCollection()
	 */
	class EO_SessionIndex_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_SessionIndex_Collection wakeUpCollection($rows)
	 */
	class EO_SessionIndex_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\ImOpenLines\Model\SessionKpiMessagesTable:imopenlines/lib/model/sessionkpimessages.php:45eb7936be5a5fe32258ef7d2d9a09f7 */
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_SessionKpiMessages
	 * @see \Bitrix\ImOpenLines\Model\SessionKpiMessagesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getSessionId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetSessionId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \int getMessageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setMessageId(\int|\Bitrix\Main\DB\SqlExpression $messageId)
	 * @method bool hasMessageId()
	 * @method bool isMessageIdFilled()
	 * @method bool isMessageIdChanged()
	 * @method \int remindActualMessageId()
	 * @method \int requireMessageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetMessageId()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetMessageId()
	 * @method \int fillMessageId()
	 * @method \boolean getIsFirstMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setIsFirstMessage(\boolean|\Bitrix\Main\DB\SqlExpression $isFirstMessage)
	 * @method bool hasIsFirstMessage()
	 * @method bool isIsFirstMessageFilled()
	 * @method bool isIsFirstMessageChanged()
	 * @method \boolean remindActualIsFirstMessage()
	 * @method \boolean requireIsFirstMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetIsFirstMessage()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetIsFirstMessage()
	 * @method \boolean fillIsFirstMessage()
	 * @method \Bitrix\Main\Type\DateTime getTimeReceived()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setTimeReceived(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timeReceived)
	 * @method bool hasTimeReceived()
	 * @method bool isTimeReceivedFilled()
	 * @method bool isTimeReceivedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimeReceived()
	 * @method \Bitrix\Main\Type\DateTime requireTimeReceived()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetTimeReceived()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetTimeReceived()
	 * @method \Bitrix\Main\Type\DateTime fillTimeReceived()
	 * @method \Bitrix\Main\Type\DateTime getTimeAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setTimeAnswer(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timeAnswer)
	 * @method bool hasTimeAnswer()
	 * @method bool isTimeAnswerFilled()
	 * @method bool isTimeAnswerChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimeAnswer()
	 * @method \Bitrix\Main\Type\DateTime requireTimeAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetTimeAnswer()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetTimeAnswer()
	 * @method \Bitrix\Main\Type\DateTime fillTimeAnswer()
	 * @method \Bitrix\Main\Type\DateTime getTimeExpired()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setTimeExpired(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timeExpired)
	 * @method bool hasTimeExpired()
	 * @method bool isTimeExpiredFilled()
	 * @method bool isTimeExpiredChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimeExpired()
	 * @method \Bitrix\Main\Type\DateTime requireTimeExpired()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetTimeExpired()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetTimeExpired()
	 * @method \Bitrix\Main\Type\DateTime fillTimeExpired()
	 * @method \Bitrix\Main\Type\DateTime getTimeStop()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setTimeStop(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timeStop)
	 * @method bool hasTimeStop()
	 * @method bool isTimeStopFilled()
	 * @method bool isTimeStopChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimeStop()
	 * @method \Bitrix\Main\Type\DateTime requireTimeStop()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetTimeStop()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetTimeStop()
	 * @method \Bitrix\Main\Type\DateTime fillTimeStop()
	 * @method \string getTimeStopHistory()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setTimeStopHistory(\string|\Bitrix\Main\DB\SqlExpression $timeStopHistory)
	 * @method bool hasTimeStopHistory()
	 * @method bool isTimeStopHistoryFilled()
	 * @method bool isTimeStopHistoryChanged()
	 * @method \string remindActualTimeStopHistory()
	 * @method \string requireTimeStopHistory()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetTimeStopHistory()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetTimeStopHistory()
	 * @method \string fillTimeStopHistory()
	 * @method \boolean getIsSentExpiredNotification()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setIsSentExpiredNotification(\boolean|\Bitrix\Main\DB\SqlExpression $isSentExpiredNotification)
	 * @method bool hasIsSentExpiredNotification()
	 * @method bool isIsSentExpiredNotificationFilled()
	 * @method bool isIsSentExpiredNotificationChanged()
	 * @method \boolean remindActualIsSentExpiredNotification()
	 * @method \boolean requireIsSentExpiredNotification()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetIsSentExpiredNotification()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetIsSentExpiredNotification()
	 * @method \boolean fillIsSentExpiredNotification()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session getSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session remindActualSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session requireSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages setSession(\Bitrix\ImOpenLines\Model\EO_Session $object)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages resetSession()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unsetSession()
	 * @method bool hasSession()
	 * @method bool isSessionFilled()
	 * @method bool isSessionChanged()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session fillSession()
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
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages set($fieldName, $value)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages reset($fieldName)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages wakeUp($data)
	 */
	class EO_SessionKpiMessages {
		/* @var \Bitrix\ImOpenLines\Model\SessionKpiMessagesTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\SessionKpiMessagesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * EO_SessionKpiMessages_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \int[] getMessageIdList()
	 * @method \int[] fillMessageId()
	 * @method \boolean[] getIsFirstMessageList()
	 * @method \boolean[] fillIsFirstMessage()
	 * @method \Bitrix\Main\Type\DateTime[] getTimeReceivedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimeReceived()
	 * @method \Bitrix\Main\Type\DateTime[] getTimeAnswerList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimeAnswer()
	 * @method \Bitrix\Main\Type\DateTime[] getTimeExpiredList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimeExpired()
	 * @method \Bitrix\Main\Type\DateTime[] getTimeStopList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimeStop()
	 * @method \string[] getTimeStopHistoryList()
	 * @method \string[] fillTimeStopHistory()
	 * @method \boolean[] getIsSentExpiredNotificationList()
	 * @method \boolean[] fillIsSentExpiredNotification()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session[] getSessionList()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages_Collection getSessionCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_Session_Collection fillSession()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\ImOpenLines\Model\EO_SessionKpiMessages $object)
	 * @method bool has(\Bitrix\ImOpenLines\Model\EO_SessionKpiMessages $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages getByPrimary($primary)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages[] getAll()
	 * @method bool remove(\Bitrix\ImOpenLines\Model\EO_SessionKpiMessages $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_SessionKpiMessages_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\ImOpenLines\Model\SessionKpiMessagesTable */
		static public $dataClass = '\Bitrix\ImOpenLines\Model\SessionKpiMessagesTable';
	}
}
namespace Bitrix\ImOpenLines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SessionKpiMessages_Result exec()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_SessionKpiMessages_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages fetchObject()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages_Collection fetchCollection()
	 */
	class EO_SessionKpiMessages_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages createObject($setDefaultValues = true)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages_Collection createCollection()
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages wakeUpObject($row)
	 * @method \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages_Collection wakeUpCollection($rows)
	 */
	class EO_SessionKpiMessages_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\TrackerTable:imopenlines/lib/model/tracker.php:f6ca398a897f34edafdb42b902b3af2a */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_Tracker
	 * @see \Bitrix\Imopenlines\Model\TrackerTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setSessionId(\int|\Bitrix\Main\DB\SqlExpression $sessionId)
	 * @method bool hasSessionId()
	 * @method bool isSessionIdFilled()
	 * @method bool isSessionIdChanged()
	 * @method \int remindActualSessionId()
	 * @method \int requireSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetSessionId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetSessionId()
	 * @method \int fillSessionId()
	 * @method \int getChatId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setChatId(\int|\Bitrix\Main\DB\SqlExpression $chatId)
	 * @method bool hasChatId()
	 * @method bool isChatIdFilled()
	 * @method bool isChatIdChanged()
	 * @method \int remindActualChatId()
	 * @method \int requireChatId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetChatId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetChatId()
	 * @method \int fillChatId()
	 * @method \int getMessageId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setMessageId(\int|\Bitrix\Main\DB\SqlExpression $messageId)
	 * @method bool hasMessageId()
	 * @method bool isMessageIdFilled()
	 * @method bool isMessageIdChanged()
	 * @method \int remindActualMessageId()
	 * @method \int requireMessageId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetMessageId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetMessageId()
	 * @method \int fillMessageId()
	 * @method \int getMessageOriginId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setMessageOriginId(\int|\Bitrix\Main\DB\SqlExpression $messageOriginId)
	 * @method bool hasMessageOriginId()
	 * @method bool isMessageOriginIdFilled()
	 * @method bool isMessageOriginIdChanged()
	 * @method \int remindActualMessageOriginId()
	 * @method \int requireMessageOriginId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetMessageOriginId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetMessageOriginId()
	 * @method \int fillMessageOriginId()
	 * @method \int getUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getAction()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setAction(\string|\Bitrix\Main\DB\SqlExpression $action)
	 * @method bool hasAction()
	 * @method bool isActionFilled()
	 * @method bool isActionChanged()
	 * @method \string remindActualAction()
	 * @method \string requireAction()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetAction()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetAction()
	 * @method \string fillAction()
	 * @method \string getCrmEntityType()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setCrmEntityType(\string|\Bitrix\Main\DB\SqlExpression $crmEntityType)
	 * @method bool hasCrmEntityType()
	 * @method bool isCrmEntityTypeFilled()
	 * @method bool isCrmEntityTypeChanged()
	 * @method \string remindActualCrmEntityType()
	 * @method \string requireCrmEntityType()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetCrmEntityType()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetCrmEntityType()
	 * @method \string fillCrmEntityType()
	 * @method \int getCrmEntityId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setCrmEntityId(\int|\Bitrix\Main\DB\SqlExpression $crmEntityId)
	 * @method bool hasCrmEntityId()
	 * @method bool isCrmEntityIdFilled()
	 * @method bool isCrmEntityIdChanged()
	 * @method \int remindActualCrmEntityId()
	 * @method \int requireCrmEntityId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetCrmEntityId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetCrmEntityId()
	 * @method \int fillCrmEntityId()
	 * @method \string getFieldId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setFieldId(\string|\Bitrix\Main\DB\SqlExpression $fieldId)
	 * @method bool hasFieldId()
	 * @method bool isFieldIdFilled()
	 * @method bool isFieldIdChanged()
	 * @method \string remindActualFieldId()
	 * @method \string requireFieldId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetFieldId()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetFieldId()
	 * @method \string fillFieldId()
	 * @method \string getFieldType()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setFieldType(\string|\Bitrix\Main\DB\SqlExpression $fieldType)
	 * @method bool hasFieldType()
	 * @method bool isFieldTypeFilled()
	 * @method bool isFieldTypeChanged()
	 * @method \string remindActualFieldType()
	 * @method \string requireFieldType()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetFieldType()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetFieldType()
	 * @method \string fillFieldType()
	 * @method \string getFieldValue()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setFieldValue(\string|\Bitrix\Main\DB\SqlExpression $fieldValue)
	 * @method bool hasFieldValue()
	 * @method bool isFieldValueFilled()
	 * @method bool isFieldValueChanged()
	 * @method \string remindActualFieldValue()
	 * @method \string requireFieldValue()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetFieldValue()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetFieldValue()
	 * @method \string fillFieldValue()
	 * @method \Bitrix\Main\Type\DateTime getDateCreate()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker setDateCreate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateCreate)
	 * @method bool hasDateCreate()
	 * @method bool isDateCreateFilled()
	 * @method bool isDateCreateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateCreate()
	 * @method \Bitrix\Main\Type\DateTime requireDateCreate()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker resetDateCreate()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unsetDateCreate()
	 * @method \Bitrix\Main\Type\DateTime fillDateCreate()
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
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_Tracker wakeUp($data)
	 */
	class EO_Tracker {
		/* @var \Bitrix\Imopenlines\Model\TrackerTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\TrackerTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_Tracker_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getSessionIdList()
	 * @method \int[] fillSessionId()
	 * @method \int[] getChatIdList()
	 * @method \int[] fillChatId()
	 * @method \int[] getMessageIdList()
	 * @method \int[] fillMessageId()
	 * @method \int[] getMessageOriginIdList()
	 * @method \int[] fillMessageOriginId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getActionList()
	 * @method \string[] fillAction()
	 * @method \string[] getCrmEntityTypeList()
	 * @method \string[] fillCrmEntityType()
	 * @method \int[] getCrmEntityIdList()
	 * @method \int[] fillCrmEntityId()
	 * @method \string[] getFieldIdList()
	 * @method \string[] fillFieldId()
	 * @method \string[] getFieldTypeList()
	 * @method \string[] fillFieldType()
	 * @method \string[] getFieldValueList()
	 * @method \string[] fillFieldValue()
	 * @method \Bitrix\Main\Type\DateTime[] getDateCreateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateCreate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_Tracker $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_Tracker $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_Tracker $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_Tracker_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Tracker_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\TrackerTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\TrackerTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Tracker_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Tracker_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker_Collection fetchCollection()
	 */
	class EO_Tracker_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_Tracker_Collection wakeUpCollection($rows)
	 */
	class EO_Tracker_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Imopenlines\Model\UserRelationTable:imopenlines/lib/model/userrelation.php:0995d31f2178a9e8e44b5c6ab47c391f */
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_UserRelation
	 * @see \Bitrix\Imopenlines\Model\UserRelationTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getUserCode()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation setUserCode(\string|\Bitrix\Main\DB\SqlExpression $userCode)
	 * @method bool hasUserCode()
	 * @method bool isUserCodeFilled()
	 * @method bool isUserCodeChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation resetUserId()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getChatId()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation setChatId(\int|\Bitrix\Main\DB\SqlExpression $chatId)
	 * @method bool hasChatId()
	 * @method bool isChatIdFilled()
	 * @method bool isChatIdChanged()
	 * @method \int remindActualChatId()
	 * @method \int requireChatId()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation resetChatId()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation unsetChatId()
	 * @method \int fillChatId()
	 * @method \boolean getAgrees()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation setAgrees(\boolean|\Bitrix\Main\DB\SqlExpression $agrees)
	 * @method bool hasAgrees()
	 * @method bool isAgreesFilled()
	 * @method bool isAgreesChanged()
	 * @method \boolean remindActualAgrees()
	 * @method \boolean requireAgrees()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation resetAgrees()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation unsetAgrees()
	 * @method \boolean fillAgrees()
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
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation set($fieldName, $value)
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation reset($fieldName)
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Imopenlines\Model\EO_UserRelation wakeUp($data)
	 */
	class EO_UserRelation {
		/* @var \Bitrix\Imopenlines\Model\UserRelationTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\UserRelationTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * EO_UserRelation_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getUserCodeList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getChatIdList()
	 * @method \int[] fillChatId()
	 * @method \boolean[] getAgreesList()
	 * @method \boolean[] fillAgrees()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Imopenlines\Model\EO_UserRelation $object)
	 * @method bool has(\Bitrix\Imopenlines\Model\EO_UserRelation $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation getByPrimary($primary)
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation[] getAll()
	 * @method bool remove(\Bitrix\Imopenlines\Model\EO_UserRelation $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Imopenlines\Model\EO_UserRelation_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_UserRelation_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Imopenlines\Model\UserRelationTable */
		static public $dataClass = '\Bitrix\Imopenlines\Model\UserRelationTable';
	}
}
namespace Bitrix\Imopenlines\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UserRelation_Result exec()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_UserRelation_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation fetchObject()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation_Collection fetchCollection()
	 */
	class EO_UserRelation_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation createObject($setDefaultValues = true)
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation_Collection createCollection()
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation wakeUpObject($row)
	 * @method \Bitrix\Imopenlines\Model\EO_UserRelation_Collection wakeUpCollection($rows)
	 */
	class EO_UserRelation_Entity extends \Bitrix\Main\ORM\Entity {}
}