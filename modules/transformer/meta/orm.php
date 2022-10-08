<?php

/* ORMENTITYANNOTATION:Bitrix\Transformer\Entity\CommandTable:transformer/lib/entity/command.php:892db9233e5bc6c514f00412aec1beba */
namespace Bitrix\Transformer\Entity {
	/**
	 * EO_Command
	 * @see \Bitrix\Transformer\Entity\CommandTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Transformer\Entity\EO_Command setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getGuid()
	 * @method \Bitrix\Transformer\Entity\EO_Command setGuid(\string|\Bitrix\Main\DB\SqlExpression $guid)
	 * @method bool hasGuid()
	 * @method bool isGuidFilled()
	 * @method bool isGuidChanged()
	 * @method \string remindActualGuid()
	 * @method \string requireGuid()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetGuid()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetGuid()
	 * @method \string fillGuid()
	 * @method \int getStatus()
	 * @method \Bitrix\Transformer\Entity\EO_Command setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetStatus()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetStatus()
	 * @method \int fillStatus()
	 * @method \string getCommand()
	 * @method \Bitrix\Transformer\Entity\EO_Command setCommand(\string|\Bitrix\Main\DB\SqlExpression $command)
	 * @method bool hasCommand()
	 * @method bool isCommandFilled()
	 * @method bool isCommandChanged()
	 * @method \string remindActualCommand()
	 * @method \string requireCommand()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetCommand()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetCommand()
	 * @method \string fillCommand()
	 * @method \string getModule()
	 * @method \Bitrix\Transformer\Entity\EO_Command setModule(\string|\Bitrix\Main\DB\SqlExpression $module)
	 * @method bool hasModule()
	 * @method bool isModuleFilled()
	 * @method bool isModuleChanged()
	 * @method \string remindActualModule()
	 * @method \string requireModule()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetModule()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetModule()
	 * @method \string fillModule()
	 * @method \string getCallback()
	 * @method \Bitrix\Transformer\Entity\EO_Command setCallback(\string|\Bitrix\Main\DB\SqlExpression $callback)
	 * @method bool hasCallback()
	 * @method bool isCallbackFilled()
	 * @method bool isCallbackChanged()
	 * @method \string remindActualCallback()
	 * @method \string requireCallback()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetCallback()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetCallback()
	 * @method \string fillCallback()
	 * @method \string getParams()
	 * @method \Bitrix\Transformer\Entity\EO_Command setParams(\string|\Bitrix\Main\DB\SqlExpression $params)
	 * @method bool hasParams()
	 * @method bool isParamsFilled()
	 * @method bool isParamsChanged()
	 * @method \string remindActualParams()
	 * @method \string requireParams()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetParams()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetParams()
	 * @method \string fillParams()
	 * @method \string getFile()
	 * @method \Bitrix\Transformer\Entity\EO_Command setFile(\string|\Bitrix\Main\DB\SqlExpression $file)
	 * @method bool hasFile()
	 * @method bool isFileFilled()
	 * @method bool isFileChanged()
	 * @method \string remindActualFile()
	 * @method \string requireFile()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetFile()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetFile()
	 * @method \string fillFile()
	 * @method \string getError()
	 * @method \Bitrix\Transformer\Entity\EO_Command setError(\string|\Bitrix\Main\DB\SqlExpression $error)
	 * @method bool hasError()
	 * @method bool isErrorFilled()
	 * @method bool isErrorChanged()
	 * @method \string remindActualError()
	 * @method \string requireError()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetError()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetError()
	 * @method \string fillError()
	 * @method \int getErrorCode()
	 * @method \Bitrix\Transformer\Entity\EO_Command setErrorCode(\int|\Bitrix\Main\DB\SqlExpression $errorCode)
	 * @method bool hasErrorCode()
	 * @method bool isErrorCodeFilled()
	 * @method bool isErrorCodeChanged()
	 * @method \int remindActualErrorCode()
	 * @method \int requireErrorCode()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetErrorCode()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetErrorCode()
	 * @method \int fillErrorCode()
	 * @method \Bitrix\Main\Type\DateTime getUpdateTime()
	 * @method \Bitrix\Transformer\Entity\EO_Command setUpdateTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updateTime)
	 * @method bool hasUpdateTime()
	 * @method bool isUpdateTimeFilled()
	 * @method bool isUpdateTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime requireUpdateTime()
	 * @method \Bitrix\Transformer\Entity\EO_Command resetUpdateTime()
	 * @method \Bitrix\Transformer\Entity\EO_Command unsetUpdateTime()
	 * @method \Bitrix\Main\Type\DateTime fillUpdateTime()
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
	 * @method \Bitrix\Transformer\Entity\EO_Command set($fieldName, $value)
	 * @method \Bitrix\Transformer\Entity\EO_Command reset($fieldName)
	 * @method \Bitrix\Transformer\Entity\EO_Command unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Transformer\Entity\EO_Command wakeUp($data)
	 */
	class EO_Command {
		/* @var \Bitrix\Transformer\Entity\CommandTable */
		static public $dataClass = '\Bitrix\Transformer\Entity\CommandTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Transformer\Entity {
	/**
	 * EO_Command_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getGuidList()
	 * @method \string[] fillGuid()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \string[] getCommandList()
	 * @method \string[] fillCommand()
	 * @method \string[] getModuleList()
	 * @method \string[] fillModule()
	 * @method \string[] getCallbackList()
	 * @method \string[] fillCallback()
	 * @method \string[] getParamsList()
	 * @method \string[] fillParams()
	 * @method \string[] getFileList()
	 * @method \string[] fillFile()
	 * @method \string[] getErrorList()
	 * @method \string[] fillError()
	 * @method \int[] getErrorCodeList()
	 * @method \int[] fillErrorCode()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdateTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdateTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Transformer\Entity\EO_Command $object)
	 * @method bool has(\Bitrix\Transformer\Entity\EO_Command $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Transformer\Entity\EO_Command getByPrimary($primary)
	 * @method \Bitrix\Transformer\Entity\EO_Command[] getAll()
	 * @method bool remove(\Bitrix\Transformer\Entity\EO_Command $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Transformer\Entity\EO_Command_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Transformer\Entity\EO_Command current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Command_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Transformer\Entity\CommandTable */
		static public $dataClass = '\Bitrix\Transformer\Entity\CommandTable';
	}
}
namespace Bitrix\Transformer\Entity {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Command_Result exec()
	 * @method \Bitrix\Transformer\Entity\EO_Command fetchObject()
	 * @method \Bitrix\Transformer\Entity\EO_Command_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Command_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Transformer\Entity\EO_Command fetchObject()
	 * @method \Bitrix\Transformer\Entity\EO_Command_Collection fetchCollection()
	 */
	class EO_Command_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Transformer\Entity\EO_Command createObject($setDefaultValues = true)
	 * @method \Bitrix\Transformer\Entity\EO_Command_Collection createCollection()
	 * @method \Bitrix\Transformer\Entity\EO_Command wakeUpObject($row)
	 * @method \Bitrix\Transformer\Entity\EO_Command_Collection wakeUpCollection($rows)
	 */
	class EO_Command_Entity extends \Bitrix\Main\ORM\Entity {}
}