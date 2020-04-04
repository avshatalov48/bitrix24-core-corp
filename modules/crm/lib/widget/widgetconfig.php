<?php
namespace Bitrix\Crm\Widget;

use Bitrix\Main;

class WidgetConfig
{
	/** @var string  */
	private $name = '';
	/** @var string  */
	private $title = '';
	/** @var array  */
	private $dataSourceSettings = null;
	/** @var string  */
	private $selectField = '';
	/** @var string  */
	private $groupField = '';
	/** @var string  */
	private $aggregate = '';
	/** @var array */
	private $filterParams = array();
	/** @var array */
	private $sortParams = array();
	/** @var array */
	private $displayParams = array();
	/** @var array */
	private $formatParams = array();
	/** @var array */
	private $extraParams = array();

	public function __construct(array $settings)
	{
		if(isset($settings['name']) && is_string($settings['name']) && $settings['name'] !== '')
		{
			$this->setName($settings['name']);
		}

		if(isset($settings['title']) && is_string($settings['title']) && $settings['title'] !== '')
		{
			$this->setTitle($settings['title']);
		}

		if(isset($settings['dataSource']) && is_string($settings['dataSource']) && $settings['dataSource'] !== '')
		{
			$dataSourceSettings = array('name' => $settings['dataSource']);
			if(isset($settings['dataPreset']) && is_string($settings['dataPreset']) && $settings['dataPreset'] !== '')
			{
				$dataSourceSettings['presetName'] = $settings['dataPreset'];
			}
			$this->setDataSourceSettings($dataSourceSettings);
		}
		else
		{
			$this->setDataSourceSettings(
				isset($settings['dataSource']) && is_array($settings['dataSource']) ? $settings['dataSource'] : array()
			);
		}

		if(isset($settings['select']) && is_array($settings['select']))
		{
			$select = $settings['select'];
			if(isset($select['name']) && is_string($select['name']))
			{
				$this->setSelectField($select['name']);
			}

			if(isset($select['aggregate']) && is_string($select['aggregate']))
			{
				$this->setAggregate($select['aggregate']);
			}
		}

		if(isset($settings['group']) && is_string($settings['group']) && $settings['group'] !== '')
		{
			$this->setGroupField($settings['group']);
		}

		if(isset($settings['filter']) && is_array($settings['filter']))
		{
			$this->setFilterParams($settings['filter']);
		}

		if(isset($settings['sort']) && is_array($settings['sort']))
		{
			$this->setSortParams($settings['sort']);
		}

		if(isset($settings['display']) && is_array($settings['display']))
		{
			$this->setDispalyParams($settings['display']);
		}

		if(isset($settings['format']) && is_array($settings['format']))
		{
			$this->setFormatParams($settings['format']);
		}

		if(isset($settings['extras']) && is_array($settings['extras']))
		{
			$this->setExtraParams($settings['extras']);
		}
	}
	/** @return string */
	public function getName()
	{
		return $this->name;
	}
	public function setName($name)
	{
		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}

		$this->name = $name;
	}
	/** @return string */
	public function getTitle()
	{
		return $this->title;
	}
	public function setTitle($title)
	{
		if(!is_string($title))
		{
			throw new Main\ArgumentTypeException('title', 'string');
		}

		$this->title = $title;
	}
	/** @return array */
	public function getDataSourceSettings()
	{
		return $this->dataSourceSettings;
	}
	/**
	* @return void
	*/
	public function setDataSourceSettings(array $settings)
	{
		$this->dataSourceSettings = $settings;
	}
	/** @return string */
	public function getSelectField()
	{
		return $this->selectField;
	}
	public function setSelectField($name)
	{
		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}

		$this->selectField = $name;
	}
	/** @return string */
	public function getGroupField()
	{
		return $this->groupField;
	}
	/**
	* @return void
	*/
	public function setGroupField($name)
	{
		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}

		$this->groupField = $name;
	}
	/** @return string */
	public function getAggregate()
	{
		return $this->aggregate;
	}
	/**
	* @return void
	*/
	public function setAggregate($aggregate)
	{
		if(!is_string($aggregate))
		{
			throw new Main\ArgumentTypeException('aggregate', 'string');
		}

		$this->aggregate = $aggregate;
	}
	/** @return array */
	public function getFilterParams()
	{
		return $this->filterParams;
	}
	/**
	* @return void
	*/
	public function setFilterParams(array $params)
	{
		$this->filterParams = $params;
	}
	/** @return array */
	public function getSortParams()
	{
		return $this->sortParams;
	}
	/**
	* @return void
	*/
	public function setSortParams(array $params)
	{
		$this->sortParams = $params;
	}
	/** @return array */
	public function getDisplayParams()
	{
		return $this->displayParams;
	}
	/**
	* @return void
	*/
	public function setDispalyParams(array $params)
	{
		$this->displayParams = $params;
	}
	/** @return array */
	public function getFomatParams()
	{
		return $this->formatParams;
	}
	/**
	* @return void
	*/
	public function setFormatParams(array $params)
	{
		$this->formatParams = $params;
	}
	/** @return array */
	public function getExtraParams()
	{
		return $this->extraParams;
	}
	/**
	 * @return void
	 */
	public function setExtraParams(array $params)
	{
		$this->extraParams = $params;
	}
	/**
	 * @return mixed
	 */
	public function getExtraParam($name, $defaultValue)
	{
		return isset($this->extraParams[$name]) ? $this->extraParams[$name] : $defaultValue;
	}
}