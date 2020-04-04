<?php
namespace Bitrix\Crm\VCard;

use Bitrix\Main;

class VCardReader
{
	protected $filePath = '';
	protected $filePosition = 0;
	protected $elementBorderPosition = 0;
	//protected $fileSize = 0;
	protected $file = null;
	protected $buffer = '';

	protected $isOpened = false;
	protected $isEOF = false;
	protected $isError = false;

	protected $elementContent = null;
	protected $element = null;

	public function __construct($filePath, $filePosition = 0)
	{
		$this->filePath = $filePath;
		$this->filePosition = $filePosition;
	}

	/**
	* @return string
	*/
	public function getElementContent()
	{
		return $this->elementContent;
	}

	/**
	* @return VCardElement
	*/
	public function getElement()
	{
		return $this->element;
	}

	/**
	* @return bool
	*/
	public function readElementContent()
	{
		if($this->elementContent !== null)
		{
			$this->elementContent = null;
		}

		if(!$this->readToBufferIfEmpty())
		{
			return false;
		}

		do
		{
			$pos = stripos($this->buffer, 'BEGIN:VCARD', 0);
		}
		while($pos === false && $this->readToBuffer());

		if($pos === false)
		{
			return false;
		}

		$beginPos = $pos;

		do
		{
			$pos = stripos($this->buffer, 'END:VCARD', $beginPos + 11); // 11 is strlen('BEGIN:VCARD')
		}
		while($pos === false && $this->readToBuffer());

		if($pos === false)
		{
			return false;
		}

		$endPos = $pos;

		$borderPos = $endPos + 9; // 9 is strlen('END:VCARD')
		$this->elementContent = substr($this->buffer, $beginPos, $borderPos - $beginPos);
		$this->buffer = substr($this->buffer, $borderPos);
		$this->elementBorderPosition = $this->filePosition - Main\Text\BinaryString::getLength($this->buffer);

		return $this->elementContent !== '';
	}

	/**
	* @return bool
	*/
	public function readElement()
	{
		if($this->element !== null)
		{
			$this->element = null;
		}

		if(!$this->open())
		{
			return false;
		}

		if(!$this->readElementContent())
		{
			return false;
		}

		$this->element = VCardElement::parseFromString($this->elementContent);
		return true;
	}

	public function getFilePosition()
	{
		return $this->filePosition;
	}

	public function getElementBorderPosition()
	{
		return $this->elementBorderPosition;
	}

	/**
	* @return bool
	*/
	protected function readToBuffer()
	{
		if($this->isError)
		{
			return false;
		}

		if(!$this->isEOF)
		{
			$this->isEOF = feof($this->file);
		}

		if($this->isEOF)
		{
			return false;
		}

		$s = fread($this->file, 1024);
		$this->filePosition = ftell($this->file);

		if($s === false)
		{
			$this->isError = true;
			return false;
		}

		$this->buffer .= $s;
		return true;
	}

	/**
	* @return bool
	*/
	protected function readToBufferIfEmpty()
	{
		if($this->buffer !== '')
		{
			return true;
		}

		return $this->readToBuffer();
	}

	/**
	* @return bool
	*/
	public function open()
	{
		if($this->isOpened)
		{
			return true;
		}

		$file = fopen($this->filePath, 'rb');
		if(!is_resource($file))
		{
			return false;
		}

		$this->file = $file;
		//$this->fileSize = filesize($this->filePath);

		if($this->filePosition > 0)
		{
			fseek($this->file, $this->filePosition);
		}
		$this->isOpened = true;
		return true;
	}

	/**
	* @return void
	*/
	public function close()
	{
		if(!$this->isOpened)
		{
			return;
		}

		if($this->file)
		{
			fclose($this->file);
			$this->file = null;
		}

		$this->isOpened = false;
	}
}