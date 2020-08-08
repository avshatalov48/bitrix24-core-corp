<?php

namespace Bitrix\DocumentGenerator\DataProvider;

use Bitrix\DocumentGenerator\DataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;

class ArrayDataProvider extends DataProvider implements \Iterator, \Countable
{
	public const INDEX_PLACEHOLDER = 'INDEX';
	public const NUMBER_PLACEHOLDER = 'NUMBER';

	protected $itemProvider;
	protected $itemKey;
	protected $itemTitle;

	/**
	 * @param array $source
	 * @param array $options
	 * @throws ArgumentTypeException
	 */
	public function __construct($source, array $options = [])
	{
		parent::__construct($source, $options);
		if(is_array($source))
		{
			$this->data = $source;
		}
		if(isset($options['ITEM_PROVIDER'], $options['ITEM_NAME']))
		{
			$itemTitle = '';
			if($options['ITEM_TITLE'])
			{
				$itemTitle = $options['ITEM_TITLE'];
			}
			$this->setItemProvider($options['ITEM_NAME'], $options['ITEM_PROVIDER'], $itemTitle);
		}
	}

	/**
	 * @param string $itemKey
	 * @param string|DataProvider $itemProvider
	 * @param string $itemTitle
	 * @throws ArgumentTypeException
	 */
	protected function setItemProvider($itemKey, $itemProvider, $itemTitle = '')
	{
		if(DataProviderManager::getInstance()->checkProviderName($itemProvider))
		{
			if(is_object($itemProvider))
			{
				$this->itemProvider = get_class($itemProvider);
			}
			else
			{
				$this->itemProvider = $itemProvider;
			}
			$this->itemKey = $itemKey;
			$this->itemTitle = $itemTitle;
		}
		else
		{
			throw new ArgumentTypeException('itemProvider');
		}
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		$fields = [];
		if($this->itemProvider && $this->itemKey)
		{
			$fields[$this->itemKey] = [
				'PROVIDER' => $this->itemProvider,
			];
			if($this->itemTitle)
			{
				$fields[$this->itemKey]['TITLE'] = $this->itemTitle;
			}
			if(isset($this->options['ITEM_OPTIONS']) && is_array($this->options['ITEM_OPTIONS']))
			{
				$fields[$this->itemKey]['OPTIONS'] = $this->options['ITEM_OPTIONS'];
			}
		}
		$fields[static::NUMBER_PLACEHOLDER] = [
			'TITLE' => Loc::getMessage('DOCGEN_DATAPROVIDER_ARRAY_NUMBER_TITLE'),
		];
		$fields[static::INDEX_PLACEHOLDER] = [
			'TITLE' => Loc::getMessage('DOCGEN_DATAPROVIDER_ARRAY_INDEX_TITLE'),
		];

		return $fields;
	}

	/**
	 * Returns value by its name.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getValue($name)
	{
		$value = null;

		if(is_array($this->data))
		{
			if($name === static::NUMBER_PLACEHOLDER)
			{
				$value = count($this->data);
			}
			elseif($name === static::INDEX_PLACEHOLDER)
			{
				$value = ($this->key() + 1);
			}
			elseif(isset($this->data[$name]))
			{
				$value = $this->data[$name];
			}
		}

		return $value;
	}

	public function getItemByIndex(int $index)
	{
		return $this->getValue($index);
	}

	public function replaceItem(int $index, $item): ArrayDataProvider
	{
		$oldItem = $this->getItemByIndex($index);
		if(!$oldItem)
		{
			throw new \OutOfRangeException('There is no item with index ' . $index);
		}
		$this->data[$index] = $item;

		return $this;
	}

	public function addItem($item): int
	{
		$this->data[] = $item;

		return count($this->data);
	}

	public function deleteItemByIndex(int $index): ArrayDataProvider
	{
		unset($this->data[$index]);
		$this->data = array_values($this->data);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getItemKey(): ?string
	{
		return $this->itemKey;
	}

	public function current()
	{
		if(is_array($this->data))
		{
			return current($this->data);
		}

		return false;
	}

	public function next()
	{
		if(is_array($this->data))
		{
			next($this->data);
			return $this->current();
		}

		return false;
	}

	public function key()
	{
		if(is_array($this->data))
		{
			return key($this->data);
		}

		return null;
	}

	public function valid()
	{
		if(is_array($this->data))
		{
			return($this->current());
		}

		return false;
	}

	public function rewind()
	{
		if(is_array($this->data))
		{
			reset($this->data);
			return $this->current();
		}

		return false;
	}

	public function count()
	{
		if(is_array($this->data))
		{
			return count($this->data);
		}

		return 0;
	}
}