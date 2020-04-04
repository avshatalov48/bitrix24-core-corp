<?php
namespace Bitrix\Landing\Source;

abstract class DataLoader
{
	protected $config = [
		'select' => [],
		'filter' => [],
		'order' => [],
		'limit' => 0,
		'internal_filter' => []
	];

	protected $options = [];

	/** @var Seo */
	protected $seo = null;

	public function __construct()
	{
		$this->seo = new Seo();
	}

	/**
	 * @param array $config
	 * @return void
	 */
	public function setConfig(array $config)
	{
		if (empty($config))
		{
			return;
		}
		$this->config = array_merge($this->config, $config);
	}

	/**
	 * @param array $options
	 * @return void
	 */
	public function setOptions(array $options)
	{
		if (empty($options))
		{
			return;
		}
		$this->options = array_merge($this->options, $options);
	}

	/**
	 * @return array
	 */
	abstract public function getElementListData();

	/**
	 * @param mixed $element
	 * @return array
	 */
	abstract public function getElementData($element);

	/**
	 * @param mixed $filter
	 * @return array
	 */
	abstract public function normalizeFilter($filter);

	/**
	 * @param mixed $filter
	 * @return string
	 */
	abstract public function calculateFilterHash($filter);

	/**
	 * @param mixed $filter
	 * @return string
	 */
	public function getFilterHash($filter)
	{
		return $this->calculateFilterHash($this->normalizeFilter($filter));
	}

	/**
	 * Returns showed fields list, if exists.
	 *
	 * @return array|null
	 */
	protected function getSelectFields()
	{
		return $this->getSettingsValue('select');
	}

	/**
	 * Returns user filter, if exists.
	 *
	 * @return array|null
	 */
	protected function getFilter()
	{
		return $this->getSettingsValue('filter');
	}

	/**
	 * Returns prepared filter.
	 *
	 * @param array $fields
	 * @return array
	 */
	protected function getPreparedFilter(array $fields)
	{
		$result = [];
		$filter = $this->getFilter();
		if (!empty($filter) && is_array($filter))
		{
			$dataFilter = new DataFilter();
			$dataFilter->setFields($fields);
			$result = $dataFilter->create($filter);
			unset($dataFilter);
		}
		return $result;
	}

	/**
	 * Returns element order.
	 *
	 * @return array|null
	 */
	protected function getOrder()
	{
		return $this->getSettingsValue('order');
	}

	/**
	 * Returns max element count for showing.
	 *
	 * @return int
	 */
	protected function getLimit()
	{
		return (int)$this->getSettingsValue('limit');
	}

	/**
	 * Returns additional user-uncontrolled filter. Can be absent.
	 *
	 * @return array|null
	 */
	protected function getInternalFilter()
	{
		return $this->getSettingsValue('internal_filter');
	}

	/**
	 * Returns settings option, if exists.
	 *
	 * @param string $index Option name.
	 * @return mixed|null
	 */
	protected function getSettingsValue($index)
	{
		$index = trim((string)$index);
		if ($index === '')
		{
			return null;
		}
		if (!empty($this->config[$index]))
		{
			return $this->config[$index];
		}
		return null;
	}

	/**
	 * Returns additinal option value, if exists.
	 *
	 * @param string $index Option name.
	 * @return mixed|null
	 */
	protected function getOptionsValue($index)
	{
		$index = trim((string)$index);
		if ($index === '')
		{
			return null;
		}
		if (!empty($this->options[$index]))
		{
			return $this->options[$index];
		}
		return null;
	}

	/**
	 * @return Seo
	 */
	public function getSeo()
	{
		return $this->seo;
	}

	/**
	 * @param string $name
	 * @return string|null
	 */
	public function getSeoProperty($name)
	{
		return $this->seo->getProperty($name);
	}

	/**
	 * @return string|null
	 */
	public function getSeoTitle()
	{
		return $this->getSeoProperty(Seo::TITLE);
	}
}