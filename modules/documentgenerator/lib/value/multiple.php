<?php

namespace Bitrix\DocumentGenerator\Value;

use Bitrix\DocumentGenerator\Value;

class Multiple extends Value
{
	const SEPARATOR_COMMA = 1;
	const SEPARATOR_NEWLINE = 2;

	/**
	 * @param string $modifier
	 * @return string|Value
	 */
	public function toString($modifier = '')
	{
		$options = $this->getOptions($modifier);

		$modifier = preg_replace('#mseparator=\d#', '', $modifier);
		$modifier = preg_replace('#mfirst=[y,n]#', '', $modifier);
		$modifier = preg_replace('#[,+]#', ',', $modifier);
		$modifier = trim($modifier, ',');
		$separator = $this->getSeparatorByCode($options['mseparator']);
		$isFirst = $options['mfirst'];

		if(is_array($this->value) || $this->value instanceof \Traversable)
		{
			$values = [];
			foreach($this->value as $value)
			{
				if($value instanceof Value)
				{
					$values[] = $value->toString($modifier);
				}
				elseif(is_array($value) || is_object($value))
				{
					continue;
				}
				elseif(!empty($value) && $value !== 0)
				{
					$values[] = $value;
				}
				if($isFirst && count($values) == 1)
				{
					break;
				}
			}

			return implode($separator, $values);
		}
		elseif($this->value instanceof Value)
		{
			return $this->value->toString($modifier);
		}
		elseif(is_object($this->value) && method_exists($this->value, '__toString'))
		{
			return $this->value->__toString();
		}
		elseif(!is_object($this->value))
		{
			return $this->value;
		}

		return '';
	}

	protected static function getDefaultOptions()
	{
		return ['mseparator' => static::SEPARATOR_COMMA, 'mfirst' => false];
	}

	protected function getSeparatorByCode($separatorCode)
	{
		if($separatorCode == static::SEPARATOR_NEWLINE)
		{
			return PHP_EOL;
		}

		return ', ';
	}
}