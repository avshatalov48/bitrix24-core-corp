<?php

/* ORMENTITYANNOTATION:Bitrix\Ml\Entity\ModelTable:ml/lib/entity/modeltable.php:821d9efe41791ebc151c33acbfb0e4ce */
namespace Bitrix\Ml\Entity {
	/**
	 * Model
	 * @see \Bitrix\Ml\Entity\ModelTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Ml\Model setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Ml\Model setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Ml\Model resetName()
	 * @method \Bitrix\Ml\Model unsetName()
	 * @method \string fillName()
	 * @method \string getType()
	 * @method \Bitrix\Ml\Model setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Ml\Model resetType()
	 * @method \Bitrix\Ml\Model unsetType()
	 * @method \string fillType()
	 * @method \int getVersion()
	 * @method \Bitrix\Ml\Model setVersion(\int|\Bitrix\Main\DB\SqlExpression $version)
	 * @method bool hasVersion()
	 * @method bool isVersionFilled()
	 * @method bool isVersionChanged()
	 * @method \int remindActualVersion()
	 * @method \int requireVersion()
	 * @method \Bitrix\Ml\Model resetVersion()
	 * @method \Bitrix\Ml\Model unsetVersion()
	 * @method \int fillVersion()
	 * @method \string getState()
	 * @method \Bitrix\Ml\Model setState(\string|\Bitrix\Main\DB\SqlExpression $state)
	 * @method bool hasState()
	 * @method bool isStateFilled()
	 * @method bool isStateChanged()
	 * @method \string remindActualState()
	 * @method \string requireState()
	 * @method \Bitrix\Ml\Model resetState()
	 * @method \Bitrix\Ml\Model unsetState()
	 * @method \string fillState()
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
	 * @method \Bitrix\Ml\Model set($fieldName, $value)
	 * @method \Bitrix\Ml\Model reset($fieldName)
	 * @method \Bitrix\Ml\Model unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Ml\Model wakeUp($data)
	 */
	class EO_Model {
		/* @var \Bitrix\Ml\Entity\ModelTable */
		static public $dataClass = '\Bitrix\Ml\Entity\ModelTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Ml\Entity {
	/**
	 * EO_Model_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getVersionList()
	 * @method \int[] fillVersion()
	 * @method \string[] getStateList()
	 * @method \string[] fillState()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Ml\Model $object)
	 * @method bool has(\Bitrix\Ml\Model $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Ml\Model getByPrimary($primary)
	 * @method \Bitrix\Ml\Model[] getAll()
	 * @method bool remove(\Bitrix\Ml\Model $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Ml\Entity\EO_Model_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Ml\Model current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Model_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Ml\Entity\ModelTable */
		static public $dataClass = '\Bitrix\Ml\Entity\ModelTable';
	}
}
namespace Bitrix\Ml\Entity {
	/**
	 * @method static EO_Model_Query query()
	 * @method static EO_Model_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Model_Result getById($id)
	 * @method static EO_Model_Result getList(array $parameters = array())
	 * @method static EO_Model_Entity getEntity()
	 * @method static \Bitrix\Ml\Model createObject($setDefaultValues = true)
	 * @method static \Bitrix\Ml\Entity\EO_Model_Collection createCollection()
	 * @method static \Bitrix\Ml\Model wakeUpObject($row)
	 * @method static \Bitrix\Ml\Entity\EO_Model_Collection wakeUpCollection($rows)
	 */
	class ModelTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Model_Result exec()
	 * @method \Bitrix\Ml\Model fetchObject()
	 * @method \Bitrix\Ml\Entity\EO_Model_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Model_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Ml\Model fetchObject()
	 * @method \Bitrix\Ml\Entity\EO_Model_Collection fetchCollection()
	 */
	class EO_Model_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Ml\Model createObject($setDefaultValues = true)
	 * @method \Bitrix\Ml\Entity\EO_Model_Collection createCollection()
	 * @method \Bitrix\Ml\Model wakeUpObject($row)
	 * @method \Bitrix\Ml\Entity\EO_Model_Collection wakeUpCollection($rows)
	 */
	class EO_Model_Entity extends \Bitrix\Main\ORM\Entity {}
}