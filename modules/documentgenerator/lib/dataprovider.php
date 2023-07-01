<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\Main\Result;

abstract class DataProvider
{
	public const FIELD_TYPE_IMAGE = 'IMAGE';
	public const FIELD_TYPE_STAMP = 'STAMP';
	public const FIELD_TYPE_DATE = 'DATE';
	public const FIELD_TYPE_TEXT = 'TEXT';
	public const FIELD_TYPE_NAME = 'NAME';
	public const FIELD_TYPE_PHONE = 'PHONE';

	protected $source;
	protected $data;
	protected $options = [];
	/** @var DataProvider */
	protected $parentProvider;
	protected $fields;

	public function __construct($source, array $options = [])
	{
		$this->source = $source;
		$this->options = $options;
	}

	/**
	 * @return array
	 */
	abstract public function getFields();

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getValue($name)
	{
		if($this->fields === null)
		{
			$this->getFields();
		}
		if(!isset($this->data[$name]))
		{
			$this->data[$name] = DataProviderManager::getInstance()->getDataProviderValue($this, $name);
		}

		return DataProviderManager::getInstance()->prepareValue($this->data[$name], $this->fields[$name] ?? []);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	protected function getRawValue($name)
	{
		$value = $this->getValue($name);
		while($value instanceof Value)
		{
			$value = $value->getValue();
		}

		return $value;
	}

	/**
	 * @return bool
	 */
	public function isLoaded()
	{
		return $this->data !== null;
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	public function hasAccess($userId)
	{
		if($this->isLoaded() && $this->parentProvider && $this->parentProvider !== $this)
		{
			return $this->parentProvider->hasAccess($userId);
		}

		return false;
	}

	/**
	 * @return DataProvider
	 */
	public function getParentProvider()
	{
		return $this->parentProvider;
	}

	/**
	 * @param mixed $parentProvider
	 * @return $this
	 */
	public function setParentProvider(DataProvider $parentProvider)
	{
		$this->parentProvider = $parentProvider;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @return mixed
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * Returns path to the directory where files with language phrases are placed
	 *
	 * @return string|null
	 */
	public function getLangPhrasesPath()
	{
		return null;
	}

	/**
	 * @param Document $document
	 * @return array
	 */
	public function getAdditionalDocumentInfo(Document $document)
	{
		return [];
	}

	/**
	 * @return bool
	 */
	public function isRootProvider()
	{
		return false;
	}

	/**
	 * @return Result
	 */
	public function isPrintable(): Result
	{
		return new Result();
	}
}
