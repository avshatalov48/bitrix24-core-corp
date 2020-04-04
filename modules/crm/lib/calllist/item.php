<?php

namespace Bitrix\Crm\CallList;

use Bitrix\Crm\CallList\Internals\CallListCallTable;
use Bitrix\Crm\CallList\Internals\CallListItemTable;

/**
 * Class Entity
 * @package Bitrix\Crm\CallList
  */
final class Item
{
	protected $listId;
	protected $entityTypeId;
	protected $elementId;
	protected $statusId;
	protected $callId;
	protected $rank;
	protected $name;
	protected $description;
	protected $companyTitle;
	protected $companyPost;
	protected $editUrl;
	protected $webformResultId;
	/** @var array [int: [TYPE: string, VALUE: string]] */
	protected $phones = array();
	protected $new = true;

	protected $associatedEntity = null;

	protected function __construct()
	{
	}

	public static function createFromArray(array $fields, $new = true)
	{
		$entity = new self;
		$entity->setFromArray($fields);
		$entity->new = $new;
		return $entity;
	}

	public function persist()
	{
		$record = array(
			'RANK' => $this->rank
		);

		if($this->statusId != '')
			$record['STATUS_ID'] = $this->statusId;
		
		if($this->callId != '')
			$record['CALL_ID'] = $this->callId;

		if($this->new)
		{
			$record['LIST_ID'] = $this->listId;
			$record['ENTITY_TYPE_ID'] = $this->entityTypeId;
			$record['ELEMENT_ID'] = $this->elementId;

			CallListItemTable::add($record);
		}
		else
			CallListItemTable::update(
				array(
					'LIST_ID' => $this->listId,
					'ENTITY_TYPE_ID' => $this->entityTypeId,
					'ELEMENT_ID' => $this->elementId
				),
				$record
			);
	}

	public function setFromArray(array $fields)
	{
		if(isset($fields['LIST_ID']))
			$this->listId = (int)$fields['LIST_ID'];

		if(isset($fields['ENTITY_TYPE_ID']))
			$this->entityTypeId = (int)$fields['ENTITY_TYPE_ID'];

		if(isset($fields['ELEMENT_ID']))
			$this->elementId = (int)$fields['ELEMENT_ID'];

		if(isset($fields['STATUS_ID']))
			$this->statusId = (string)$fields['STATUS_ID'];

		if(isset($fields['CALL_ID']))
			$this->callId = (int)$fields['CALL_ID'];

		if(isset($fields['RANK']))
			$this->rank = (int)$fields['RANK'];

		if(isset($fields['WEBFORM_RESULT_ID']))
			$this->webformResultId = (int)$fields['WEBFORM_RESULT_ID'];
	}

	public function toArray()
	{
		return array(
			'LIST_ID' => $this->listId,
			'ENTITY_TYPE_ID' => $this->entityTypeId,
			'ELEMENT_ID' => $this->elementId,
			'STATUS_ID' => $this->statusId,
			'CALL_ID' => $this->callId,
			'RANK' => $this->rank,
			'NAME' => $this->name,
			'DESCRIPTION' => $this->description,
			'COMPANY_TITLE' => $this->companyTitle,
			'EDIT_URL' => $this->editUrl,
			'POST' => $this->companyPost,
			'PHONES' => $this->phones,
			'WEBFORM_RESULT_ID' => $this->webformResultId,
			'ASSOCIATED_ENTITY' => $this->associatedEntity
		);
	}

	/**
	 * @return mixed
	 */
	public function getListId()
	{
		return $this->listId;
	}

	/**
	 * @param mixed $listId
	 */
	public function setListId($listId)
	{
		$this->listId = $listId;
	}

	/**
	 * @return mixed
	 */
	public function getElementId()
	{
		return $this->elementId;
	}

	/**
	 * @param mixed $elementId
	 */
	public function setElementId($elementId)
	{
		$this->elementId = $elementId;
	}

	/**
	 * @return mixed
	 */
	public function getStatusId()
	{
		return $this->statusId;
	}
	
	public static function getStatusName($statusId)
	{
		static $statusList = null;
		if(is_null($statusList))
			$statusList = \CCrmStatus::GetStatusList('CALL_LIST', true);

		return $statusList[$statusId];
	}

	public function getCallRecordUrl()
	{
		return '';
	}

	/**
	 * @param mixed $statusId
	 */
	public function setStatusId($statusId)
	{
		$this->statusId = $statusId;
	}

	/**
	 * @return mixed
	 */
	public function getCallId()
	{
		return $this->callId;
	}

	/**
	 * @param mixed $callId
	 */
	public function setCallId($callId)
	{
		$this->callId = $callId;
	}

	/**
	 * @return mixed
	 */
	public function getRank()
	{
		return $this->rank;
	}

	/**
	 * @param mixed $rank
	 */
	public function setRank($rank)
	{
		$this->rank = $rank;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param mixed $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return mixed
	 */
	public function getCompanyTitle()
	{
		return $this->companyTitle;
	}

	/**
	 * @param mixed $companyTitle
	 */
	public function setCompanyTitle($companyTitle)
	{
		$this->companyTitle = $companyTitle;
	}

	/**
	 * @return mixed
	 */
	public function getCompanyPost()
	{
		return $this->companyPost;
	}

	/**
	 * @param mixed $companyPost
	 */
	public function setCompanyPost($companyPost)
	{
		$this->companyPost = $companyPost;
	}

	/**
	 * @return mixed
	 */
	public function getEditUrl()
	{
		return $this->editUrl;
	}

	/**
	 * @param mixed $editUrl
	 */
	public function setEditUrl($editUrl)
	{
		$this->editUrl = $editUrl;
	}

	/**
	 * @return array
	 */
	public function getPhones()
	{
		return $this->phones;
	}

	/**
	 * @param array $phones
	 */
	public function setPhones(array $phones)
	{
		$this->phones = $phones;
	}

	/**
	 * @return mixed
	 */
	public function getWebformResultId()
	{
		return $this->webformResultId;
	}

	/**
	 * @param mixed $webformResultId
	 */
	public function setWebformResultId($webformResultId)
	{
		$this->webformResultId = $webformResultId;
	}

	/**
	 * @return array|null
	 */
	public function getAssociatedEntity()
	{
		return $this->associatedEntity;
	}

	/**
	 * @param array $associatedEntity
	 */
	public function setAssociatedEntity(array $associatedEntity)
	{
		$this->associatedEntity = $associatedEntity;
	}

	public function compare(Item $other)
	{
		static $statusIndex = null;
		if(is_null($statusIndex))
		{
			$statusList = CallList::getStatusList();
			foreach ($statusList as $statusRecord)
			{
				$statusIndex[$statusRecord['STATUS_ID']] = $statusRecord['SORT'];
			}
		}

		$myStatusSort = $statusIndex[$this->getStatusId()];
		$otherStatusSort = $statusIndex[$other->getStatusId()];
		$result = $otherStatusSort - $myStatusSort;

		return ($result == 0) ? $other->getRank() - $this->getRank() : $result;
	}
}