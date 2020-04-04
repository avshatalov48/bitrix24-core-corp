<?php
namespace Bitrix\Crm\Statistics;
use Bitrix\Main;

class StatisticFieldBinding
{
	private $slotName = '';
	private $fieldName = '';
	private $fieldTitle = '';
	private $options = null;

	public function __construct($slotName = '', $fieldName = '', $options = null)
	{
		$this->setSlotName($slotName);
		$this->setFieldName($fieldName);
		if(is_array($options))
		{
			$this->setOptions($options);
		}
	}

	/**
	* @param string $slotName Statistics entity slot name.
	* @return void
	*/
	public function setSlotName($slotName)
	{
		$this->slotName = $slotName;
	}
	/**
	* @return string
	*/
	public function getSlotName()
	{
		return $this->slotName;
	}
	/**
	* @param string $fieldName Entity field name.
	* @return void
	*/
	public function setFieldName($fieldName)
	{
		$this->fieldName = $fieldName;
	}
	/**
	* @return string
	*/
	public function getFieldName()
	{
		return $this->fieldName;
	}
	/**
	* @param string $fieldTitle Entity field title.
	* @return void
	*/
	public function setFieldTitle($fieldTitle)
	{
		$this->fieldTitle = $fieldTitle;
	}
	/**
	* @return string
	*/
	public function getFieldTitle()
	{
		return $this->fieldTitle;
	}
	/**
	* @param array $options Option value.
	* @return void
	*/
	public function setOptions(array $options)
	{
		$this->options = $options;
	}
	/**
	* @param string $name Option name.
	* @param mixed $value Option value.
	* @return void
	*/
	public function setOption($name, $value)
	{
		if($this->options === null)
		{
			$this->options = array();
		}
		$this->options[$name] = $value;
	}
	/**
	* @param string $name Option name.
	* @return mixed
	*/
	public function getOption($name)
	{
		return $this->options !== null && $this->options[$name] ? $this->options[$name] : null;
	}
	/**
	* @param string $name Option name.
	* @return void
	*/
	public function removeOption($name)
	{
		if($this->options !== null)
		{
			unset($this->options[$name]);
		}
	}
	public function toArray()
	{
		$result = array('SLOT' => $this->slotName, 'FIELD' => $this->fieldName);
		if($this->options !== null)
		{
			$result['OPTIONS'] = $this->options;
		}
		return $result;
	}
	/**
	* @param array $array Array returned by toArray.
	* @return StatisticFieldBinding
	*/
	public static function fromArray(array $array)
	{
		return new StatisticFieldBinding(
			isset($array['SLOT']) ? $array['SLOT'] : '',
			isset($array['FIELD']) ? $array['FIELD'] : '',
			isset($array['OPTIONS']) ? $array['OPTIONS'] : null
		);
	}
}