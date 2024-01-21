<?php
namespace Bitrix\Intranet\UI\LeftMenu\MenuItem;

use \Bitrix\Main;
use \Bitrix\Intranet\UI\LeftMenu;

// DTO like
abstract class Basic
{
	private $ID;
	protected $TEXT;
	protected $LINK;
	protected $SORT;
	protected $PARENT;
	protected $SELECTED;
	protected $COUNTER_ID;
	protected $ADDITIONAL_LINKS;

	private $errorCollection;
	protected $siteId;

	public function __construct(array $itemData, ?string $siteId = null)
	{
		$this->errorCollection = new Main\ErrorCollection();
		if (isset($itemData['PERMISSION']) && $itemData['PERMISSION'] <= 'D')
		{
			$this->errorCollection->setError(new Main\Error('Access denied.'));
		}
		if (isset($itemData['ID']))
		{
			$this->ID = $itemData['ID'];
		}
		else if (isset($itemData['LINK']))
		{
			$this->ID = crc32($itemData['LINK']);
		}
		else
		{
			$this->errorCollection->setError(new Main\Error('Menu item id is not set.'));
		}

		if (!isset($itemData['TEXT']) || $itemData['TEXT'] === '')
		{
			$this->errorCollection->setError(new Main\Error('Menu item title is not set.'));
		}
		else
		{
			$this->TEXT = $itemData['TEXT'];
		}

		if (isset($itemData['LINK']) && $itemData['LINK'])
		{
			$this->LINK = $itemData['LINK'];
		}

		$this->SELECTED = (isset($itemData['SELECTED']) && $itemData['SELECTED'] === true);
		if (isset($itemData['ADDITIONAL_LINKS']))
		{
			$this->ADDITIONAL_LINKS = $itemData['ADDITIONAL_LINKS'];
		}
		$this->siteId = is_null($siteId) && defined('SITE_ID') ? SITE_ID : $siteId;

		$this->init($itemData);
	}

	abstract function init(array $menuItemData): void;

	public function getId(): ?string
	{
		return $this->ID;
	}

	abstract public function getCode(): string;

	public function isSuccess(): bool
	{
		return $this->errorCollection->isEmpty();
	}

	public function getErrorList(): array
	{
		return $this->errorCollection->getValues();
	}

	abstract public function canUserDelete(LeftMenu\User $user) :bool;

	public function setSort(?int $sorting)
	{
		$this->SORT = $sorting;
	}

	public function getSort(): ?int
	{
		return $this->SORT;
	}

	public function setParent(?LeftMenu\MenuItem\Group $parent)
	{
		if ($parent instanceof LeftMenu\MenuItem\Group)
		{
			$this->PARENT = $parent;
		}
		else
		{
			$this->PARENT = null;
		}
	}

	public function getParent(): ?LeftMenu\MenuItem\Group
	{
		return $this->PARENT;
	}

	public function getLink(): ?string
	{
		return $this->LINK;
	}

	public function hasParent(): bool
	{
		return $this->PARENT instanceof LeftMenu\MenuItem\Group;
	}

	public function prepareData(LeftMenu\User $user): array
	{
		$result = [
			'ID' => $this->getId(),
			'TEXT' => $this->TEXT,
			'LINK' => $this->LINK,
			'SELECTED' => $this->SELECTED,
			'ADDITIONAL_LINKS' => $this->ADDITIONAL_LINKS,
			'ITEM_TYPE' => htmlspecialcharsbx($this->getCode()),
			'PERMISSION' => 'R',
			'DELETE_PERM' => $this->canUserDelete($user) ? 'Y' : 'N',
			'PARAMS' => [
				'menu_item_id' => htmlspecialcharsbx($this->getId()),
				'parent_id' => null
			],
		];

		if ($this->PARENT instanceof LeftMenu\MenuItem\Group
			&& !($this->PARENT instanceof LeftMenu\MenuItem\GroupService))
		{
			$result['GROUP_ID'] = $this->PARENT->getId();
			$result['PARAMS']['parent_id'] = $this->PARENT->getId();
		}
		if ($this->COUNTER_ID !== null)
		{
			$result['PARAMS']['counter_id'] = $this->COUNTER_ID;
		}

		return $this->adjustData($result, $user);
	}

	abstract protected function adjustData($data, LeftMenu\User $user): array;
}
