<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
//IncludeModuleLangFile(__FILE__);
class DuplicateSearchParams
{
	protected $fieldNames = array();
	protected $userID = 0;
	protected $enablePermissionCheck = false;
	protected $enableRanking = false;

	protected ?int $entityTypeId = null;
	protected ?int $categoryId = null;

	public function __construct($fieldNames = array(), $userID = 0, $enablePermissionCheck = false)
	{
		$this->setFieldNames($fieldNames);
		$this->setUserID($userID);
		$this->enablePermissionCheck($enablePermissionCheck);
	}

	public function setFieldNames(array $fieldNames)
	{
		$this->fieldNames = $fieldNames;
	}
	public function getUserID()
	{
		return $this->userID;
	}
	public function setUserID($userID)
	{
		if(!is_int($userID))
		{
			throw new Main\ArgumentTypeException('userID', 'integer');
		}
		$this->userID = $userID > 0 ? $userID : 0;
	}
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}
	public function enablePermissionCheck($enable)
	{
		$this->enablePermissionCheck = (bool)$enable;
	}

	public function isRankingEnabled()
	{
		return $this->enableRanking;
	}
	public function enableRanking($enable)
	{
		$this->enableRanking = (bool)$enable;
	}

	public function getFieldNames()
	{
		return $this->fieldNames;
	}

	public function getEntityTypeId(): ?int
	{
		return $this->entityTypeId;
	}

	public function setEntityTypeId(?int $entityTypeId): void
	{
		$this->entityTypeId = $entityTypeId;
	}


	public function getCategoryId(): ?int
	{
		return $this->categoryId;
	}

	public function setCategoryId(?int $categoryId): void
	{
		$this->categoryId = $categoryId;
	}
}