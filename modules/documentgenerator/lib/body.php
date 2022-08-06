<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\DocumentGenerator\Value\Multiple;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Result;
use Bitrix\Main\Text\BinaryString;

abstract class Body
{
	protected static $valuesPattern = '#\{(([a-zA-Z0-9._-]*?)\~?([^\~\r\t\n\<]*))\}#Uu';
	protected $content;
	protected $values = [];
	protected $fields = [];
	protected $storage;
	protected $excludedPlaceholders = [];
	protected $arrayValuePlaceholders = [];

	public const ARRAY_INDEX_MODIFIER = 'index';
	public const BLOCK_START_PLACEHOLDER = 'BLOCK_START';
	public const BLOCK_END_PLACEHOLDER = 'BLOCK_END';
	protected const DO_NOT_INSERT_VALUE_MODIFIER = '__SystemDeletePlaceholder';

	/**
	 * Body constructor.
	 * @param string $content
	 */
	public function __construct($content)
	{
		$this->content = $content;
	}

	/**
	 * @param array $fields
	 * @return $this
	 */
	public function setFields(array $fields)
	{
		$this->fields = $fields;
		return $this;
	}

	/**
	 * @param Storage $storage
	 * @param mixed $from
	 * @return static|false
	 */
	public static function readFromStorage(Storage $storage, $from)
	{
		$content = $storage->read($from);
		if($content)
		{
			$body = new static($content);
			return $body->setStorage($storage);
		}

		return false;
	}

	/**
	 * @return Storage
	 */
	public function getStorage()
	{
		return $this->storage;
	}

	/**
	 * @param Storage $storage
	 * @return $this
	 */
	public function setStorage(Storage $storage)
	{
		$this->storage = $storage;

		return $this;
	}

	/**
	 * @param array $options
	 * @param Storage|null $storage
	 * @return AddResult
	 */
	public function save(array $options, Storage $storage = null)
	{
		$filename = $options['fileName'];
		if(!$storage)
		{
			$storage = $this->storage;
		}

		if(!$storage)
		{
			$storage = Driver::getInstance()->getDefaultStorage();
		}

		$result = $storage->write($this->content, array_merge($options, [
			'fileName' => $this->getFileName($filename),
			'contentType' => $this->getFileMimeType(),
		]));
		if($result->isSuccess())
		{
			$path = $result->getId();
			$result = FileTable::add([
				'STORAGE_TYPE' => get_class($storage),
				'STORAGE_WHERE' => $path,
			]);
		}

		return $result;
	}

	/**
	 * Parse $content, process commands, fill values.
	 * Returns true on success, false on failure.
	 *
	 * @return Result
	 */
	abstract public function process();

	/**
	 * @return array
	 */
	abstract public function getPlaceholders();

	/**
	 * @return string
	 */
	abstract public function getFileExtension();

	/**
	 * @return string
	 */
	abstract public function getFileMimeType();

	/**
	 * @return bool
	 */
	public function isFileProcessable()
	{
		return true;
	}

	/**
	 * Normalizes content of the body.
	 */
	public function normalizeContent()
	{

	}

	/**
	 * @param string $filename
	 * @return string
	 */
	protected function getFileName($filename = '')
	{
		if(!$filename)
		{
			$filename = randString(5);
		}

		if(BinaryString::getSubstring($filename, -5) !== '.'.$this->getFileExtension())
		{
			$filename = $filename.'.'.$this->getFileExtension();
		}

		return $filename;
	}

	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @param array $values
	 * @return $this
	 */
	public function setValues(array $values)
	{
		$this->values = array_merge($this->values, $values);
		foreach($values as $placeholder => $value)
		{
			if($value instanceof ArrayDataProvider)
			{
				$this->arrayValuePlaceholders[$placeholder] = $placeholder;
			}
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function getFieldNames()
	{
		return array_merge($this->getPlaceholders(), array_keys($this->getFields()));
	}

	/**
	 * Set placeholders list that will not be filled with values.
	 *
	 * @param array $placeholders
	 */
	public function setExcludedPlaceholders(array $placeholders)
	{
		$this->excludedPlaceholders = array_fill_keys($placeholders, true);
	}

	/**
	 * Replaces placeholders on pattern static::$valuesPattern in $content.
	 *
	 * @param array $params
	 * @return string
	 */
	protected function replacePlaceholders(array $params = [])
	{
		if(isset($params['content']) && is_string($params['content']))
		{
			$content = $params['content'];
		}
		else
		{
			$content = $this->content;
		}
		if(isset($params['callback']) && is_callable($params['callback']))
		{
			$callback = $params['callback'];
		}
		else
		{
			/** @see Body::getReplaceValue() */
			$callback = [$this, 'getReplaceValue'];
		}
		return preg_replace_callback(
			static::$valuesPattern,
			function(array $matches) use ($callback, $params)
			{
				return call_user_func_array($callback, [$matches, $params]);
			},
			$content
		);
	}

	/**
	 * @param array $matches
	 * @param array $params
	 * @return string
	 */
	protected function getReplaceValue(array $matches, array $params = [])
	{
		if(isset($matches[2]) && isset($this->values[$matches[2]]))
		{
			if(isset($this->excludedPlaceholders[$matches[2]]))
			{
				return $matches[0];
			}

			return $this->printValue($this->values[$matches[2]], $matches[2], $matches[3], $params);
		}

		return '';
	}

	/**
	 * @param string $code
	 * @return string
	 */
	public static function getCodeFromPlaceholder($code)
	{
		$matches = static::matchFieldNames($code);
		return reset($matches);
	}

	/**
	 * Returns array of placeholders with TYPE = $type.
	 * If $type is null - returns array of all $placeholders with specified TYPE.
	 *
	 * @param array $types
	 * @return array
	 */
	protected function getTypePlaceholders(array $types = [])
	{
		$placeholders = [];

		foreach($this->fields as $placeholder => $field)
		{
			if(isset($field['TYPE']) && (in_array($field['TYPE'], $types)) || empty($types) && !isset($this->excludedPlaceholders[$placeholder]))
			{
				$placeholders[$placeholder] = $placeholder;
			}
		}

		return $placeholders;
	}

	/**
	 * Parses $content on pattern static::$valuesPattern and returns array of value names.
	 *
	 * @param string $content
	 * @return array
	 */
	protected static function matchFieldNames($content)
	{
		$names = [];
		if(preg_match_all(static::$valuesPattern, $content, $fieldMatches, PREG_SET_ORDER))
		{
			foreach($fieldMatches as $fieldMatch)
			{
				if (
					mb_strpos($fieldMatch[2], static::BLOCK_START_PLACEHOLDER) === false
					&& mb_strpos($fieldMatch[2], static::BLOCK_END_PLACEHOLDER) === false
				)
				{
					$names[$fieldMatch[2]] = $fieldMatch[2];
				}
			}
		}

		return $names;
	}

	protected static function getModifierFromPlaceholder(string $placeholder): string
	{
		if(preg_match(static::$valuesPattern, $placeholder, $matches))
		{
			return $matches[3] ?? '';
		}

		return '';
	}

	protected function getStringValue($value, $placeholder, $modifier = '', array $params = []): string
	{
		if (mb_strpos($modifier, static::DO_NOT_INSERT_VALUE_MODIFIER) !== false)
		{
			return '';
		}
		if (is_object($value))
		{
			if ($value instanceof Value)
			{
				return $value->toString($modifier);
			}
			if (class_exists($value) && method_exists($value, '__toString'))
			{
				return $value->__toString();
			}

			return '';
		}
		if (is_array($value))
		{
			return '';
		}
		if ($this->isArrayValue($value, $placeholder))
		{
			$valueNameParts = explode('.', $value);
			$name = implode('.', array_slice($valueNameParts, 2));
			$modifierData = Value::parseModifier($modifier);
			$index = 0;
			/** @var ArrayDataProvider $innerProvider */
			$arrayProvider = $this->values[$valueNameParts[0]];
			if (isset($modifierData['all']))
			{
				$value = $this->printAllArrayValues($arrayProvider, $placeholder, $name, $modifier);
			}
			else
			{
				if (isset($modifierData[static::ARRAY_INDEX_MODIFIER]))
				{
					$index = (int) $modifierData[static::ARRAY_INDEX_MODIFIER];
				}
				$value = $this->printArrayValueByIndex($arrayProvider, $placeholder, $name, $index, $modifier);
			}
		}

		return (string)$value;
	}

	/**
	 * Generates string from value.
	 *
	 * @param mixed $value
	 * @param string $placeholder
	 * @param string $modifier
	 * @param array $params
	 * @return string
	 */
	protected function printValue($value, $placeholder, $modifier = '', array $params = [])
	{
		$value = $this->getStringValue($value, $placeholder, $modifier, $params);

		$stringValue = new Value\PlaneString($value);

		return $stringValue->toString($modifier);
	}

	/**
	 * @param ArrayDataProvider $arrayDataProvider
	 * @param $placeholder
	 * @param $name
	 * @param int $index
	 * @param string $modifier
	 * @return string
	 */
	protected function printArrayValueByIndex(ArrayDataProvider $arrayDataProvider, $placeholder, $name, $index = 0, $modifier = '')
	{
		$innerProvider = $arrayDataProvider->getValue($index);
		$modifier = preg_replace('#index=[\d]+#', '', $modifier);
		if($innerProvider instanceof DataProvider)
		{
			$value = self::printValue($innerProvider->getValue($name), $placeholder, $modifier);
		}
		else
		{
			$value = '';
		}

		return $value;
	}

	/**
	 * @param ArrayDataProvider $arrayDataProvider
	 * @param $placeholder
	 * @param $name
	 * @param string $modifier
	 * @return string
	 */
	protected function printAllArrayValues(ArrayDataProvider $arrayDataProvider, $placeholder, $name, $modifier = '')
	{
		$value = [];
		[$outerModifier, $innerModifier] = explode('all', $modifier, 2);
		$innerModifier = preg_replace('#^=[y,n]#', '', $innerModifier);
		/** @var DataProvider $innerProvider */
		foreach ($arrayDataProvider as $innerProvider)
		{
			$value[] = self::printValue($innerProvider->getValue($name), $placeholder, $innerModifier);
		}

		$value = new Multiple($value);

		return self::printValue($value, $placeholder, $outerModifier);
	}

	/**
	 * @param string $value
	 * @param string $placeholder
	 * @return bool
	 */
	protected function isArrayValue($value, $placeholder)
	{
		if(!is_string($value) || !is_string($placeholder) || empty($value))
		{
			return false;
		}
		$valueParts = explode('.', $value);
		if(count($valueParts) == 1)
		{
			return false;
		}
		$providerName = $valueParts[0];

		return isset($this->arrayValuePlaceholders[$providerName]);
	}

	/**
	 * Returns array of placeholders that starts with $providerName.'.'
	 *
	 * @param $providerName
	 * @return array
	 */
	protected function getLinkedPlaceholders($providerName)
	{
		$linkedPlaceholders = [];
		$placeholders = $this->getPlaceholders();
		foreach($placeholders as $placeholder)
		{
			if(mb_strpos($placeholder, $providerName.'.') === 0)
			{
				$linkedPlaceholders[] = $placeholder;
			}
			elseif(
				isset($this->values[$placeholder]) &&
				is_string($this->values[$placeholder]) &&
				mb_strpos($this->values[$placeholder], $providerName.'.') === 0
			)
			{
				$linkedPlaceholders[] = $placeholder;
			}
		}

		return array_unique($linkedPlaceholders);
	}

	/**
	 * Returns array of unique objects.
	 *
	 * @param array $nodes
	 * @return array
	 */
	protected function getUniqueObjects(array $nodes)
	{
		$result = $hashs = [];
		foreach($nodes as $node)
		{
			if(!is_object($node))
			{
				continue;
			}
			$hash = spl_object_hash($node);
			if(!isset($hashs[$hash]))
			{
				$hashs[$hash] = 1;
				$result[] = $node;
			}
		}

		return $result;
	}

	public static function detectHtml(string $string): bool
	{
		return (preg_match('/(<\s?[^\>]*\/?\s?>(.+)<\/?\s?[^\>]*\/?\s?>)|(<br\s?\/?>)/is', $string) != false);
	}
}
