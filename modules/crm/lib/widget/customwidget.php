<?php
namespace Bitrix\Crm\Widget;

use Bitrix\Main;
use Bitrix\Crm\Widget\Data\DataSourceFactory;

class CustomWidget extends Widget
{
	/** @var array[WidgetConfig] */
	private $configs = null;
	/** @var string */
	private $groupField = '';

	public function __construct(array $settings, Filter $filter, $userID = 0, $enablePermissionCheck = true)
	{
		parent::__construct($settings, $filter, $userID, $enablePermissionCheck);

		$this->configs = array();
		$configs = $this->getSettingArray('configs', array());
		foreach($configs as $config)
		{
			$this->configs[] = new WidgetConfig($config);
		}

		if(isset($settings['group']) && is_string($settings['group']) && $settings['group'] !== '')
		{
			$this->setGroupField($settings['group']);
		}
	}

	/** @return string */
	public function getGroupField()
	{
		return $this->groupField;
	}

	/**
	 * @param string $name Group Field Name
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

	/**
	* @return array
	*/
	public function prepareData()
	{
		/** @var WidgetConfig|null $config */
		$config = count($this->configs) > 0 ? $this->configs[0] : null;
		if($config === null)
		{
			return array();
		}

		$this->filter->setExtras($config->getFilterParams());
		$sourceSettings = $config->getDataSourceSettings();
		$source =  DataSourceFactory::checkSettings($sourceSettings)
			? DataSourceFactory::create($sourceSettings, $this->userID, $this->enablePermissionCheck)
			: null;

		$selectField = $config->getSelectField();
		$aggregate = $config->getAggregate();
		$groupField = $this->groupField !== '' ? $this->groupField : $config->getGroupField();
		$items = $source !== null
			? $source->getList(
				array(
					'filter' => $this->filter,
					'select' => array(array('name' => $selectField, 'aggregate' => $aggregate)),
					'group' => $groupField,
					'sort' => array(array('name' => $selectField))
				)
			) : array();

		$title = $config->getTitle();
		if (!empty($title))
			$items[0]['title'] = $title;

		$display = $config->getDisplayParams();
		if(!empty($display))
			$items[0]['display'] = $display;

		$res = array(
			'items' => $items,
			'valueField' => $selectField,
			'titleField' => $groupField,
			'attributes' => $source->getAttributes()
		);
		return $res;
	}
	/**
	* @return array
	*/
	public function initializeDemoData(array $data)
	{
		if(!(isset($data['items']) && is_array($data['items'])))
		{
			return $data;
		}

		/** @var WidgetConfig|null $config */
		$config = count($this->configs) > 0 ? $this->configs[0] : null;
		if($config === null)
		{
			return $data;
		}

		$sourceSettings = $config->getDataSourceSettings();
		$source = DataSourceFactory::checkSettings($sourceSettings)
			? DataSourceFactory::create($sourceSettings, $this->userID, $this->enablePermissionCheck)
			: null;

		if($source === null)
		{
			return $data;
		}

		$groupField = $this->groupField !== '' ? $this->groupField : $config->getGroupField();
		$data = $source->initializeDemoData($data, array('group' => $groupField));
		return $data;
	}
}