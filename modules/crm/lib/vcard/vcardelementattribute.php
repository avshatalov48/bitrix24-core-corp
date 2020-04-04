<?php
namespace Bitrix\Crm\VCard;
use Bitrix\Main;
use Bitrix\Main\Text\Encoding;
class VCardElementAttribute
{
	protected $name = '';
	protected $value = '';
	protected $decodedValue = null;
	protected $groupName = '';
	protected $rawParams = '';
	protected $params = null;

	public function __construct($name, $value, array $params = null)
	{
		$this->name = $name;
		$this->value = $value;
		$this->params = $params;
	}

	/**
	* @return string
	*/
	public function __toString()
	{
		$params = $this->getParams();
		if(empty($params))
		{
			return '{ name: '.$this->name.', value: '.$this->value.' }';
		}

		$paramStrings = array();
		foreach($params as $k => $v)
		{
			$paramStrings[] = '{ name: '.$k.', values: [ '.implode(', ', $v).' ] }';
		}
		return '{ name: '.$this->name.', value: '.$this->value.', params: [ '.implode(', ', $paramStrings).' ] }';
	}

	/**
	* @return string
	*/
	public function getName()
	{
		return $this->name;
	}

	/**
	* @return string
	*/
	public function getValue()
	{
		if($this->decodedValue !== null)
		{
			return $this->decodedValue;
		}

		if($this->value === '')
		{
			return ($this->decodedValue = '');
		}

		$this->decodedValue = $this->value;
		$encoding = $this->getFirstParamValue('ENCODING', '');
		if($encoding === 'QUOTED-PRINTABLE')
		{
			$this->decodedValue = quoted_printable_decode($this->decodedValue);
		}

		$charset = $this->getFirstParamValue('CHARSET', '');
		if($charset !== '' && strcasecmp($charset, SITE_CHARSET) !== 0)
		{
			$this->decodedValue = Encoding::convertEncoding($this->decodedValue, $charset, SITE_CHARSET);
		}

		return $this->decodedValue;
	}

	/**
	* @return string
	*/
	public function getRawValue()
	{
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function getGroupName()
	{
		return $this->groupName;
	}

	/**
	* @return array
	*/
	public function getParams()
	{
		if($this->params === null)
		{
			$this->params = array();
			if($this->rawParams !== '')
			{
				$params = explode(';', $this->rawParams);
				foreach($params as $param)
				{
					$pos = stripos($param, '=');
					if($pos === false)
					{
						continue;
					}

					$name = trim(preg_replace(array("/^\"/", "/\"$/"), '', substr($param, 0, $pos)));
					$this->params[$name] = preg_split("/\s*\,\s*/", substr($param, $pos + 1), -1, PREG_SPLIT_NO_EMPTY);
				}
			}
		}
		return $this->params;
	}

	/**
	* @return array
	*/
	public function getParamValues($name)
	{
		$ownParams = $this->getParams();
		return isset($ownParams[$name]) ? $ownParams[$name] : array();
	}

	/**
	* @return string
	*/
	public function getFirstParamValue($name, $default = null)
	{
		$ownParams = $this->getParams();
		return isset($ownParams[$name]) && !empty($ownParams[$name]) ? $ownParams[$name][0] : $default;
	}

	public function hasParams(array $params)
	{
		$ownParams = $this->getParams();
		if(empty($ownParams))
		{
			return empty($params);
		}

		/**
		* @var $v array
		*/
		foreach($params as $k => $v)
		{
			if(!is_array($v))
			{
				$v = array($v);
			}

			if(!isset($ownParams[$k]) || count(array_diff($v, $ownParams[$k])) > 1)
			{
				return false;
			}
		}

		return true;
	}

	public static function isValidAttributeString($str)
	{
		return preg_match("/[^\\\\]:/", $str) === 1;
	}

	/**
	* @return VCardElementAttribute|null
	*/
	public static function parseFromString($str)
	{
		if(preg_match("/[^\\\\]:/", $str, $match, PREG_OFFSET_CAPTURE) !== 1)
		{
			return null;
		}

		$pos = $match[0][1] + 1;
		$name = trim(substr($str, 0, $pos));
		$value = trim(substr($str, $pos + 1));
		$params = '';
		$pos = stripos($name, ';');

		if($pos !== false)
		{
			$params = trim(substr($name, $pos + 1));
			$name = trim(substr($name, 0, $pos));
		}

		//region Support Apple Contacts format
		$groupName = '';
		if(preg_match("/^(item[1-9]+)\./", $name, $match) === 1)
		{
			$groupName = $match[1];
			$name = substr($name, strlen($match[0]));
		}
		//endregion

		$item = new VCardElementAttribute($name, $value);
		if($params !== '')
		{
			$item->rawParams = $params;
		}
		if($groupName !== '')
		{
			$item->groupName = $groupName;
		}

		return $item;
	}
}