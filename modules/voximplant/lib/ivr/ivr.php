<?php

namespace Bitrix\Voximplant\Ivr;

use Bitrix\Bitrix24;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Bitrix\Voximplant\ConfigTable;
use Bitrix\Voximplant\Model\IvrTable;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Ivr
{
	protected $id;
	protected $name;
	protected $firstItemId;

	/** @var Item[] */
	protected $items = array();

	/** @var Item[] */
	protected $itemsToDelete = array();
	public function __construct($id = null)
	{
		$id = (int)$id;
		if($id > 0)
		{
			$row = IvrTable::getById($id)->fetch();
			if($row)
			{
				$this->setFromArray($row);
				$this->id = $id;
			}
			$this->items = Item::getItemsByIvrId($this->id);
		}
	}
	
	public static function createFromArray(array $ivrDefinition)
	{
		$ivr = new self();
		$ivr->setFromArray($ivrDefinition);

		return $ivr;
	}

	public function getId()
	{
		return $this->id;
	}
	
	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getFirstItemId()
	{
		return $this->firstItemId;
	}

	/**
	 * @param mixed $firstItemId
	 */
	public function setFirstItemId($firstItemId)
	{
		$this->firstItemId = $firstItemId;
	}

	public function getItems()
	{
		return $this->items;
	}

	public function getItem($id)
	{
		foreach ($this->items as $item)
		{
			if($item->getId() == $id)
				return $item;
		}
		return false;
	}

	public function setItems(array $newItems)
	{
		$oldItems = array();
		foreach ($this->items as $item)
		{
			if($item->getId() > 0)
			{
				$oldItems[$item->getId()] = $item;
			}
		}
		$this->items = array();

		foreach ($newItems as $item)
		{
			if(is_array($item))
			{
				$item = Item::createFromArray($item);
			}

			if($item->getId() > 0 && count($oldItems) > 0)
			{
				if(isset($oldItems[$item->getId()]))
				{
					$tmpNewItem = $oldItems[$item->getId()];
					$tmpNewItem->setFromArray($item->toArray());
					$this->items[] = $tmpNewItem;
					unset($oldItems[$item->getId()]);
				}
				else
				{
					$item->setId(0);
					$this->items[] = $item;
				}
			}
			else
			{
				$this->items[] = $item;
			}
		}

		foreach ($oldItems as $item)
		{
			$this->itemsToDelete[] = $item;
		}

		return $this;
	}
	
	public function addItem(Item $item)
	{
		$this->items[] = $item;

		return $this;
	}
	
	public function persist()
	{
		$row = $this->toArray();
		unset($row['ID']);
		unset($row['ITEMS']);

		if($this->id > 0)
		{
			IvrTable::update($this->id, $row);
		}
		else
		{
			$insertResult = IvrTable::add($row);
			if(!$insertResult->isSuccess())
				throw new SystemException('Error while saving IVR menu to database');

			$this->id = $insertResult->getId();
		}
		
		foreach ($this->itemsToDelete as $item)
			$item->delete();

		$this->itemsToDelete = array();
		foreach ($this->items as $item)
		{
			$item->setIvrId($this->id);
			$item->persist();
		}
	}
	
	public function toArray($resolveAdditioanFields = false)
	{
		$result = array(
			'ID' => $this->id,
			'NAME' => $this->name,
			'FIRST_ITEM_ID' => $this->firstItemId,
			'ITEMS' => array()
		);
		
		foreach ($this->items as $item)
		{
			$result['ITEMS'][] = $item->toArray($resolveAdditioanFields);
		}
		return $result;		
	}
	
	public function toTree($resolveAdditionalFields = false)
	{
		return array(
			'ID' => $this->id,
			'NAME' => $this->name,
			'ROOT_ITEM' => $this->convertItemsToTree($this->firstItemId, $resolveAdditionalFields)
		);

	}

	public function setFromArray(array $parameters)
	{
		if(isset($parameters['NAME']))
			$this->setName($parameters['NAME']);

		if(isset($parameters['FIRST_ITEM_ID']))
			$this->setFirstItemId($parameters['FIRST_ITEM_ID']);

		if(isset($parameters['ITEMS']))
			$this->setItems($parameters['ITEMS']);
	}

	public function toJson()
	{
		return Json::encode($this->toArray());
	}

	protected function convertItemsToTree($rootItemId, $resolveAdditionalFields = false, $level = 0)
	{
		$item = $this->getItem($rootItemId);
		if(!$item instanceof Item)
			return false;

		$result = $item->toArray($resolveAdditionalFields);
		$result['LEVEL'] = $level;
		foreach ($result['ACTIONS'] as $k => $action)
		{
			if($action['ACTION'] === Action::ACTION_ITEM)
			{
				$result['ACTIONS'][$k]['ITEM'] = $this->convertItemsToTree($action['PARAMETERS']['ITEM_ID'], $resolveAdditionalFields, ++$level);
			}
		}
		return $result;
	}

	/**
	 * Deletes IVR from the database.
	 * @return Result
	 */
	public function delete()
	{
		$result = new Result();

		if(!$this->id)
		{
			return $result;
		}

		$checkCursor = ConfigTable::getList(array(
			'filter' => array(
				'IVR' => 'Y',
				'IVR_ID' => $this->id
			)
		));

		$attachedToNumbers = array();
		while ($row = $checkCursor->fetch())
		{
			$attachedToNumbers[] = $row['PHONE_NAME'];
		}

		if(count($attachedToNumbers) > 0)
		{
			$result->addError(new Error(Loc::getMessage(
				"IVR_ERROR_IN_USE",
				array(
					"#NUMBERS#" => implode('. ', $attachedToNumbers))
			)));
			return $result;
		}

		foreach ($this->items as $item)
		{
			$item->delete();
		}

		IvrTable::delete($this->id);
		return $result;
	}

	/**
	 * Returns true if IVR is enabled for the portal.
	 */
	public static function isEnabled()
	{
		if(!Loader::includeModule('bitrix24'))
			return true;

		return Bitrix24\Feature::isFeatureEnabled('voximplant_ivr');
	}
}