<?php

namespace Bitrix\Main\Engine\Response;

use Bitrix\Main\Text\StringHelper;

final class Converter
{
	const TO_SNAKE  		= 0x00001;
	const TO_CAMEL  		= 0x00002;
	const TO_SNAKE_DIGIT  	= 0x00004;
	const TO_UPPER  		= 0x00010;
	const TO_LOWER  		= 0x00020;
	const LC_FIRST  		= 0x00100;
	const UC_FIRST  		= 0x00200;
	const KEYS      		= 0x01000;
	const VALUES    		= 0x02000;
	const RECURSIVE 		= 0x04000;

	const OUTPUT_JSON_FORMAT = self::KEYS | self::RECURSIVE | self::TO_CAMEL | self::LC_FIRST;

	private $format;

	public function __construct($format)
	{
		$this->setFormat($format);
	}

	public static function toJson()
	{
		return new self(self::OUTPUT_JSON_FORMAT);
	}

	public function process($data)
	{
		if (!$data)
		{
			return $data;
		}

		if (is_string($data))
		{
			return $this->formatString($data);
		}

		if (is_array($data))
		{
			return $this->formatArray($data);
		}

		return $data;
	}

	protected function formatArray(array $array)
	{
		$newData = [];
		foreach ($array as $key => $item)
		{
			$itemConverted = false;
			if ($this->format & self::VALUES)
			{
				if (($this->format & self::RECURSIVE) && is_array($item))
				{
					$item = $this->process($item);
				}
				elseif (is_string($item))
				{
					$item = $this->formatString($item);
				}

				$itemConverted = true;
			}

			if ($this->format & self::KEYS)
			{
				if (!is_int($key))
				{
					$key = $this->formatString($key);
				}

				if (($this->format & self::RECURSIVE) && is_array($item) && !$itemConverted)
				{
					$item = $this->formatArray($item);
				}
			}

			$newData[$key] = $item;
		}

		return $newData;
	}

	protected function formatString($string)
	{
		if ($this->format & self::TO_SNAKE)
		{
			$string = StringHelper::camel2snake($string);
		}

		if ($this->format & self::TO_SNAKE_DIGIT)
		{
			$string = preg_replace('/(\d+)([A-Za-z])/', '$1_$2', $string);
			$string = preg_replace('/([A-Za-z])(\d)/', '$1_$2', $string);
			$string = preg_replace('/([^_])([A-Z])/', '$1_$2', $string);
			$string = mb_strtolower($string);
		}

		if ($this->format & self::TO_CAMEL)
		{
			$string = StringHelper::snake2camel($string);
		}

		if ($this->format & self::TO_LOWER)
		{
			$string = mb_strtolower($string);
		}

		if ($this->format & self::TO_UPPER)
		{
			$string = mb_strtoupper($string);
		}

		if ($this->format & self::UC_FIRST)
		{
			$string = ucfirst($string);
		}

		if ($this->format & self::LC_FIRST)
		{
			$string = lcfirst($string);
		}


		return $string;
	}

	public function getFormat()
	{
		return $this->format;
	}

	public function setFormat($format)
	{
		$this->format = $format;

		return $this;
	}
}