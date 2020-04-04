<?php

namespace Bitrix\Disk\Internals\Entity;

use Bitrix\Disk\Internals;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Internals\Entity;
use Bitrix\Main\ArgumentTypeException;

final class FieldManager implements IErrorable
{
	/** @var array */
	protected $referenceFieldsRepository = array();
	/** @var array */
	protected $mapFieldsRepository = array();
	/** @var  ErrorCollection */
	protected $errorCollection;

	/** @var  FieldManager */
	private static $instance;


	private function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	private function __clone()
	{}

	public function getMapFieldsByModel(Internals\Model $model)
	{
		$modelClassName = get_class($model);

		if (isset($this->mapFieldsRepository[$modelClassName]))
		{
			return $this->mapFieldsRepository[$modelClassName];
		}

		$this->mapFieldsRepository[$modelClassName] = $model->getMapAttributes();

		return $this->mapFieldsRepository[$modelClassName];
	}

	public function getReferenceFieldsByModel(Internals\Model $model)
	{
		$modelClassName = get_class($model);
		if (isset($this->referenceFieldsRepository[$modelClassName]))
		{
			return $this->referenceFieldsRepository[$modelClassName];
		}

		$rawFields = $model::getMapReferenceAttributes();
		if (!$rawFields)
		{
			$this->referenceFieldsRepository[$modelClassName] = array();

			return $this->referenceFieldsRepository[$modelClassName];
		}

		$this->referenceFieldsRepository[$modelClassName] = $this->normalizeReferenceFields($rawFields);

		return $this->referenceFieldsRepository[$modelClassName];
	}

	public function getReferenceFields($modelClassName)
	{
		$modelClassName = $this->resolveClassName($modelClassName);

		if (isset($this->referenceFieldsRepository[$modelClassName]))
		{
			return $this->referenceFieldsRepository[$modelClassName];
		}
		/** @var Model $modelClassName */
		$rawFields = $modelClassName::getMapReferenceAttributes();

		/** @var string $modelClassName */
		if (!$rawFields)
		{
			$this->referenceFieldsRepository[$modelClassName] = array();

			return $this->referenceFieldsRepository[$modelClassName];
		}

		$this->referenceFieldsRepository[$modelClassName] = $this->normalizeReferenceFields($rawFields);

		return $this->referenceFieldsRepository[$modelClassName];
	}

	public function getReferenceFieldByName($modelClassName, $name)
	{
		$modelClassName = $this->resolveClassName($modelClassName);

		if (
			isset($this->referenceFieldsRepository[$modelClassName]) &&
			!isset($this->referenceFieldsRepository[$modelClassName][$name])
		)
		{
			return null;
		}

		$fields = $this->getReferenceFields($modelClassName);
		if (!$fields || !isset($fields[$name]))
		{
			return null;
		}

		return $this->referenceFieldsRepository[$modelClassName][$name];
	}

	protected function resolveClassName($model)
	{
		if (is_string($model))
		{
			//it's class name
			return $model;
		}

		if ($model instanceof Internals\Model || $model instanceof Entity\Model)
		{
			return $model::className();
		}

		throw new ArgumentTypeException('model', 'string or Model');
	}

	protected function normalizeReferenceFields(array $fields)
	{
		$normalizedFields = array();
		foreach ($fields as $name => $conf)
		{
			if(!is_array($conf))
			{
				$conf = array(
					'orm_alias' => strtoupper($this->camel2snake($name)),
					'class' => $conf,
					'load' => null,
					'select' => '*',
				);
			}

			if(!isset($conf['select']))
			{
				$conf['select'] = '*';
			}

			if(!isset($conf['orm_alias']))
			{
				$conf['orm_alias'] = strtoupper($this->camel2snake($name));
			}

			$normalizedFields[$name] = $conf;
		}

		return $normalizedFields;
	}

	private function camel2snake($str)
	{
		return strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $str));
	}

	private function snake2camel($str)
	{
		$str = str_replace('_', ' ', strtolower($str));

		return str_replace(' ', '', ucwords($str));
	}

	/**
	 * Returns Singleton of FieldManager
	 * @return FieldManager
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new FieldManager;
		}

		return self::$instance;
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}