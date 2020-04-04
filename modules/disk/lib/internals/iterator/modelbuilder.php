<?php

namespace Bitrix\Disk\Internals\Iterator;


use Bitrix\Disk\Internals;
use Bitrix\Main\ObjectException;
use Traversable;

class ModelBuilder extends \IteratorIterator
{
	/** @var Internals\Model */
	private $classNameModel;

	public function __construct(Traversable $iterator, $classNameModel)
	{
		parent::__construct($iterator);
		$this->classNameModel = $classNameModel;

		if(
			!is_subclass_of($classNameModel, Internals\Model::className()) &&
			!in_array(Internals\Model::className(), class_parents($classNameModel)) //5.3.9
		)
		{
			throw new ObjectException("{$classNameModel} must be subclass of " . Internals\Model::className());
		}
	}

	public function current()
	{
		$classNameModel = $this->classNameModel;
		return $classNameModel::buildFromArray(parent::current());
	}
}