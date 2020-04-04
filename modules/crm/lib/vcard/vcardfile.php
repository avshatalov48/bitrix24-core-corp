<?php
namespace Bitrix\Crm\VCard;
use Bitrix\Main;
class VCardFile
{
	protected $value = '';
	protected $valueType = '';
	protected $type = '';
	protected $encoding = '';

	/**
	* @return string
	*/
	public function __toString()
	{
		return '{ value: '.$this->value.', valueType: '.$this->valueType.', type: '.$this->type.', encoding: '.$this->encoding.' }';
	}

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
	* @return string
	*/
	public function getType()
	{
		return $this->type;
	}

	/**
	* @return void
	*/
	public function setType(array $s)
	{
		$this->type = $s;
	}

	/**
	* @return string
	*/
	public function getEncoding()
	{
		return $this->encoding;
	}

	/**
	* @return void
	*/
	public function setEncoding(array $s)
	{
		$this->encoding = $s;
	}

	/**
	* @return string
	*/
	public function getFileInfo()
	{
		$fileInfo = null;

		if($this->encoding === 'B' || $this->encoding === 'BASE64')
		{
			$type = $this->type !== '' ? strtolower($this->type) : 'jpg';
			if($type === '' || !in_array($type, explode(',', \CFile::GetImageExtensions()), true))
			{
				$type = 'jpg';
			}

			$filePath = \CTempFile::GetFileName(uniqid('vcard_img').'.'.$type);
			CheckDirPath($filePath);

			//Removing of line folding
			$encodedData = preg_replace("/\\\\n/i", "\n", $this->value);
			if(file_put_contents($filePath, base64_decode($encodedData)) !== false)
			{
				$fileInfo = \CFile::MakeFileArray($filePath, "image/{$type}");
			}
		}
		elseif($this->valueType === 'URI'
			&& \CCrmUrlUtil::HasScheme($this->value) && \CCrmUrlUtil::IsSecureUrl($this->value))
		{
			$fileInfo = \CFile::MakeFileArray($this->value);
		}

		return is_array($fileInfo) && strlen(\CFile::CheckImageFile($fileInfo)) === 0
			? $fileInfo : null;
	}

	/**
	* @return VCardFile|null
	*/
	public static function createFromAttribute(VCardElementAttribute $attr)
	{
		$item = new VCardFile();
		$item->value = $attr->getValue();
		$item->valueType = strtoupper($attr->getFirstParamValue('VALUE', ''));;
		$item->type = strtoupper($attr->getFirstParamValue('TYPE', ''));
		$item->encoding = strtoupper($attr->getFirstParamValue('ENCODING', ''));

		return $item;
	}
}