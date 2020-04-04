<?php
namespace Bitrix\Crm\VCard;
use Bitrix\Main;
class VCardAddress
{
	protected $pobox = '';
	protected $ext = '';
	protected $street = '';
	protected $locality = '';
	protected $region = '';
	protected $code = '';
	protected $country = '';

	public function getPostOfficeBox()
	{
		return $this->pobox;
	}

	public function setPostOfficeBox($s)
	{
		$this->pobox = $s;
	}

	public function getExtendedAddress()
	{
		return $this->ext;
	}

	public function setExtendedAddress($s)
	{
		$this->ext = $s;
	}

	public function getStreet()
	{
		return $this->street;
	}

	public function setStreet($s)
	{
		$this->street = $s;
	}

	public function getLocality()
	{
		return $this->locality;
	}

	public function setLocality($s)
	{
		$this->locality = $s;
	}

	public function getRegion()
	{
		return $this->region;
	}

	public function setRegion($s)
	{
		$this->region = $s;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function setCode($s)
	{
		$this->code = $s;
	}

	public function getCountry()
	{
		return $this->country;
	}

	public function setCountry($s)
	{
		$this->country = $s;
	}

	/**
	* @return string
	*/
	public function getFormatted()
	{
		$ary = array();
		if($this->street !== '')
		{
			$ary[] = $this->street;
		}

		if($this->ext !== '')
		{
			$ary[] = $this->ext;
		}

		if($this->pobox !== '')
		{
			$ary[] = $this->pobox;
		}

		if($this->locality !== '')
		{
			$ary[] = $this->locality;
		}

		if($this->region !== '')
		{
			$ary[] = $this->region;
		}

		if($this->code !== '')
		{
			$ary[] = $this->code;
		}

		if($this->country !== '')
		{
			$ary[] = $this->country;
		}

		return implode(', ', $ary);
	}

	/**
	* @return VCardAddress|null
	*/
	public static function createFromAttribute(VCardElementAttribute $attr)
	{
		$str = $attr->getValue();

		$parts = explode(';', $str);
		$qty = count($parts);
		if($qty === 0)
		{
			return null;
		}

		$qty = min($qty, 7);
		$item = new VCardAddress();
		for($i = 0; $i < $qty; $i++)
		{

			$s = trim($parts[$i]);
			if($s === '')
			{
				continue;
			}

			if($i === 0)
			{
				$item->pobox = $s;
			}
			elseif($i === 1)
			{
				$item->ext = $s;
			}
			elseif($i === 2)
			{
				$item->street = $s;
			}
			elseif($i === 3)
			{
				$item->locality = $s;
			}
			elseif($i === 4)
			{
				$item->region = $s;
			}
			elseif($i === 5)
			{
				$item->code = $s;
			}
			elseif($i === 6)
			{
				$item->country = $s;
			}
		}
		return $item;
	}
}