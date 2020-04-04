<?php
namespace Bitrix\Crm\VCard;
use Bitrix\Main;
class VCardElement
{
	/**
	* @var $attributes array(VCardElementAttribute[])
	*/
	protected $attributes = array();
	protected $version = null;

	public function __construct(array $attributes)
	{
		$this->attributes = $attributes;
	}

	public function getVersion()
	{
		if($this->version === null)
		{
			$attr = $this->getFirstAttributeByName('VERSION');
			$this->version = $attr !== null ? $attr->getValue() : '';
		}

		return $this->version;
	}

	/**
	* @return VCardElementAttribute[]
	*/
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	* @return VCardElementAttribute[]
	*/
	public function getAttributesByName($name)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : array();
	}

	/**
	* @return VCardElementAttribute|null
	*/
	public function getFirstAttributeByName($name)
	{
		return isset($this->attributes[$name]) && !empty($this->attributes[$name]) ? $this->attributes[$name][0] : null;
	}

	/**
	* @return VCardElementAttribute|null
	*/
	public function findAttribute($name, array $params = null)
	{
		if(!isset($this->attributes[$name]) || empty($this->attributes[$name]))
		{
			return null;
		}

		$attrs = $this->attributes[$name];
		if($params === null || empty($params))
		{
			return $attrs[0];
		}

		/**
		* @var $attr VCardElementAttribute
		*/
		foreach($attrs as $attr)
		{
			if($attr->hasParams($params))
			{
				return $attr;
			}
		}

		return null;
	}

	/**
	* @return VCardElement
	*/
	public static function parseFromString($str)
	{
		return self::parseFromArray(preg_split("/\r\n|\r|\n/", $str));
	}

	/**
	* @return VCardElement
	*/
	public static function parseFromArray(array $array)
	{
		$attributes = array();
		$qty = count($array);
		$s = '';
		for($i = $qty - 1; $i >= 0; $i--)
		{
			if($s === '')
			{
				$s = $array[$i];
			}
			else
			{
				//Try to parse multiline attribute
				$s = $array[$i].PHP_EOL.$s;
			}

			if(!VCardElementAttribute::isValidAttributeString($s))
			{
				continue;
			}

			$attr = VCardElementAttribute::parseFromString($s);
			if($attr !== null)
			{
				$name = $attr->getName();
				if(!isset($attributes[$name]))
				{
					$attributes[$name] = array();
				}
				array_unshift($attributes[$name], $attr);
			}
			$s = '';
		}
		return new VCardElement(array_reverse($attributes));
	}
}