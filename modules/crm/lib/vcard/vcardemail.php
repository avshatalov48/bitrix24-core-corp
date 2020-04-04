<?php
namespace Bitrix\Crm\VCard;
use Bitrix\Main;
class VCardEmail
{
	protected $value = '';
	protected $types = null;

	/**
	* @return string
	*/
	public function getValue()
	{
		return $this->value;
	}

	/**
	* @return void
	*/
	public function setValue($s)
	{
		$this->value = $s;
	}

	/**
	* @return array
	*/
	public function getTypes()
	{
		return $this->types;
	}

	/**
	* @return void
	*/
	public function setTypes(array $ary)
	{
		$this->types = $ary;
	}

	/**
	* @return string
	*/
	public function getMultiFieldValue()
	{
		return $this->value;
	}

	/**
	* @return string
	*/
	public function getMultiFieldValueType()
	{
		$typeMap = array_flip($this->types);
		return isset($typeMap['WORK']) || isset($typeMap['PREF']) ? 'WORK' : 'OTHER';
	}

	/**
	* @return VCardEmail|null
	*/
	public static function createFromAttribute(VCardElementAttribute $attr)
	{
		$types = array_map('strtoupper', $attr->getParamValues('TYPE'));
		$typeMap = array_flip($types);

		if(isset($typeMap['x400']))
		{
			// X.400 is not supported
			return null;
		}

		$item = new VCardEmail();
		$item->value = $attr->getValue();
		$item->types = $types;

		return $item;
	}
}