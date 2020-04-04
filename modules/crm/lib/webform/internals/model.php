<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Result as EntityResult;

Loc::loadMessages(__FILE__);

abstract class Model
{
	protected $id = null;
	protected $params = array();
	protected $errors = array();

	public function __construct($id, array $params = null)
	{
		if($id)
		{
			$this->load($id);
		}

		if($params)
		{
			$this->set($params);
		}
	}

	public function set(array $params)
	{
		$this->params = $params;
	}

	public function get()
	{
		return $this->params;
	}

	public function merge($params)
	{
		$this->set($params + $this->get());
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function hasErrors()
	{
		return count($this->errors) > 0;
	}

	protected function prepareResult(EntityResult $entityResult)
	{
		if($entityResult->isSuccess())
		{
			return;
		}

		$errors = $entityResult->getErrors();
		foreach($errors as $error)
		{
			$this->errors[] = $error->getMessage();
		}
	}

	public function save($onlyCheck = false)
	{
		/* @var $class \Bitrix\Main\Entity\DataManager */
		$class = $this->getClassTable();

		$this->errors = array();
		$fields = $this->params;

		if($onlyCheck)
		{
			$result = new EntityResult;
			$class::checkFields($result, $this->id, $fields);
			$this->prepareResult($result);

			return;
		}

		if(!$this->check())
		{
			return;
		}

		if($this->id)
		{
			$result = $class::update($this->id, $fields);
		}
		else
		{
			$result = $class::add($fields);
			$this->id = $result->getId();
		}

		$this->prepareResult($result);
	}

	public function check()
	{
		$this->save(true);

		return count($this->errors) === 0;
	}

	public function getId()
	{
		return $this->id;
	}

	public function delete()
	{
		/* @var $class \Bitrix\Main\Entity\DataManager */
		$class = $this->getClassTable();
		return  $class::delete($this->id);
	}

	abstract protected function getClassTable();
	abstract public function load($id);
}
