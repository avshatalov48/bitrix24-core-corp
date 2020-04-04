<?php
namespace Bitrix\Crm\VCard;
use Bitrix\Main;
class VCardPhone
{
	protected $value = '';
	protected $valueType = '';
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
	* @return string
	*/
	public function getValueType()
	{
		return $this->valueType;
	}

	/**
	* @return void
	*/
	public function setValueType($s)
	{
		$this->valueType = $s;
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
		if($this->valueType !== 'URI')
		{
			return $this->value;
		}

		return preg_replace("/[a-z]+\:(\/\/)?/i", "", $this->value);
	}

	/**
	* @return string
	*/
	public function getMultiFieldValueType()
	{
		$typeMap = array_flip($this->types);

		if(isset($typeMap['CELL']))
		{
			return 'MOBILE';
		}
		elseif(isset($typeMap['FAX']))
		{
			return 'FAX';
		}
		elseif(isset($typeMap['PAGER']))
		{
			return 'PAGER';
		}
		elseif(isset($typeMap['HOME']))
		{
			return 'HOME';
		}
		elseif(isset($typeMap['WORK']) || isset($typeMap['PREF']))
		{
			return 'WORK';
		}

		return 'OTHER';
	}

	/**
	* @return VCardPhone|null
	*/
	public static function createFromAttribute(VCardElementAttribute $attr)
	{
		$item = new VCardPhone();
		$item->value = $attr->getValue();
		$item->valueType = strtoupper($attr->getFirstParamValue('VALUE', 'text'));
		$item->types = array_map('strtoupper', $attr->getParamValues('TYPE'));
		return $item;
	}
}