<?php
namespace Bitrix\Crm\Widget;

use Bitrix\Main;

class Widget
{
	/** @var int */
	protected $userID = 0;
	/** @var boolean */
	protected $enablePermissionCheck = true;
	/** @var string */
	protected $permissionSql;
	/** @var  Filter */
	protected $filter;
	/** @var array[int] */
	protected $responsibleIDs = null;
	/** @var array */
	protected $settings = null;
	/** @var array  */
	private $filterContextData = null;

	protected function __construct(array $settings, Filter $filter, $userID = 0, $enablePermissionCheck = true)
	{
		$this->settings = $settings;
		$this->filter = $filter;
		$this->responsibleIDs = $this->filter->getResponsibleIDs();
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}
		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}
		$this->userID = $userID;
		if(!is_bool($enablePermissionCheck))
		{
			$enablePermissionCheck = (bool)$enablePermissionCheck;
		}

		$this->enablePermissionCheck = $enablePermissionCheck;
		//Disable permission check if filter by current user is enabled.
		if($this->enablePermissionCheck
			&& is_array($this->responsibleIDs)
			&& count($this->responsibleIDs) === 1
			&& (int)$this->responsibleIDs[0] === $this->userID)
		{
			$this->enablePermissionCheck = false;
		}

		$this->filterContextData = array();
	}
	/**
	* @return int
	*/
	public function getUserID()
	{
		return $this->userID;
	}
	/**
	* @return boolean
	*/
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}
	/**
	* @return string
	*/
	protected function getSettingString($name, $defaultValue = '')
	{
		return isset($this->settings[$name]) && is_string($this->settings[$name])
			? $this->settings[$name] : $defaultValue;
	}
	/**
	* @return array
	*/
	protected function getSettingArray($name, $defaultValue = null)
	{
		return isset($this->settings[$name]) && is_array($this->settings[$name])
			? $this->settings[$name] : $defaultValue;
	}
	/**
	 * Get filtration context parameters dictionary
	 * @return array
	 */
	public function getFilterContextData()
	{
		return $this->filterContextData;
	}
	/**
	 * Set filtration context parameters dictionary
	 * @return void
	 */
	public function setFilterContextData(array $data)
	{
		$this->filterContextData = $data;
	}
	/**
	* @return string|boolean
	*/
	protected function preparePermissionSql()
	{
		if($this->permissionSql !== null)
		{
			return $this->permissionSql;
		}

		if(\CCrmPerms::IsAdmin($this->userID))
		{
			$this->permissionSql = '';
		}
		else
		{
			$this->permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::DealName,
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS'=> \CCrmPerms::GetUserPermissions($this->userID))
			);
		}
		return $this->permissionSql;
	}
	/**
	* @return array
	* @throws Main\NotImplementedException
	*/
	public function prepareData()
	{
		throw new Main\NotImplementedException('Method prepareData must be overridden.');
	}

	/**
	* @return array
	*/
	public function initializeDemoData(array $data)
	{
		return $data;
	}
}