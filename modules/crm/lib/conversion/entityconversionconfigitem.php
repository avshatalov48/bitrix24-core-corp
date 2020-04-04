<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class EntityConversionConfigItem
{
	/** @var \CCrmOwnerType */
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	/** @var bool */
	protected $active = false;
	/** @var bool */
	protected $enableSynchronization = false;
	/** @var array */
	protected $initData = array();

	public function __construct($entityTypeID = 0)
	{
		$this->setEntityTypeID($entityTypeID);
	}

	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}

	public function setEntityTypeID($entityTypeID)
	{
		$this->entityTypeID = $entityTypeID;
	}

	public function isActive()
	{
		return $this->active;
	}

	public function setActive($active)
	{
		$this->active = $active;
	}

	public function isSynchronizationEnabled()
	{
		return $this->enableSynchronization;
	}

	public function enableSynchronization($enable)
	{
		$this->enableSynchronization = $enable;
	}
	/**
	 * Get entity initialization data.
	 * This data are used for initialize new entity that is created by converter.
	 * @return array
	 */
	public function getInitData()
	{
		return $this->initData;
	}

	/**
	 * Set entity initialization data.
	 * @param array $data Initialization data.
	 * @return void
	 */
	public function setInitData(array $data)
	{
		$this->initData = $data;
	}

	public function toJavaScript()
	{
		return array(
			'active' => $this->active ? 'Y' : 'N',
			'enableSync' => $this->enableSynchronization ? 'Y' : 'N',
			'initData' => $this->initData
		);
	}

	public function fromJavaScript(array $params)
	{
		$this->active = isset($params['active']) && $params['active'] === 'Y';
		$this->enableSynchronization = isset($params['enableSync']) && $params['enableSync'] === 'Y';
		$this->initData = isset($params['initData']) && is_array($params['initData']) ? $params['initData'] : array();
	}

	public function externalize()
	{
		return array(
			'entityTypeId' => $this->entityTypeID,
			'active' => $this->active,
			'enableSync' => $this->enableSynchronization,
			'initData' => $this->initData
		);
	}

	public function internalize(array $params)
	{
		if(isset($params['entityTypeId']))
		{
			$this->entityTypeID = (int)$params['entityTypeId'];
		}
		$this->active = isset($params['active']) ? (bool)$params['active'] : false;
		$this->enableSynchronization = isset($params['enableSync']) ? (bool)$params['enableSync'] : false;
		$this->initData = isset($params['initData']) && is_array($params['initData']) ? $params['initData'] : array();
	}
}