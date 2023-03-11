<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserField;
use Bitrix\Rpa\Permission;
use Bitrix\Rpa\UserField\UserFieldCollection;
use Bitrix\Rpa\UserPermissions;

/**
 * Class Type
 * @see TypeTable
 * @method string getName()
 * @method setName(string $name)
 * @method string getTitle()
 * @method setTitle(string $title)
 * @method array getSettings()
 */
class Type extends UserField\Internal\Type implements Permission\Containable
{
	use Permission\ModelTrait;

	public static $dataClass = TypeTable::class;
	protected $stages;
	protected $userFieldCollection;

	public function getId(): ?int
	{
		return parent::getId();
	}

	/**
	 * Stages should be sorted by sort, but in the end always goes success stages and fail stages.
	 * @param PageNavigation|null $pageNavigation
	 * @return EO_Stage_Collection
	 */
	public function getStages(PageNavigation $pageNavigation = null): EO_Stage_Collection
	{
		if($this->stages === null)
		{
			$this->stages = StageTable::getList([
				'select' => [
					'*',
					new ExpressionField(
						'SEMANTIC_SORT',
						'CASE 
							WHEN %s = \'FAIL\' THEN (%d + 34294967295) 
							WHEN %s = \'SUCCESS\' THEN (%d + 24294967295) 
							WHEN %s IS NOT NULL THEN (%d + 14294967295) 
							ELSE %d
						END',
						[
							'SEMANTIC', 'SORT', 'SEMANTIC', 'SORT', 'SEMANTIC', 'SORT', 'SORT'
						]
					)
				],
				'filter' => [
					'TYPE_ID' => $this->getId(),
				],
				'order' => [
					'SEMANTIC_SORT' => 'ASC',
					'SORT' => 'ASC',
				],
				'offset' => $pageNavigation ? $pageNavigation->getOffset() : null,
				'limit' => $pageNavigation ? $pageNavigation->getLimit(): null,
			])->fetchCollection();
		}

		return $this->stages;
	}

	public function getStage(int $stageId): ?Stage
	{
		return $this->getStages()->getByPrimary($stageId);
	}

	/**
	 * Resort stages in actual order (if sort of some stages had been updated)
	 */
	public function resortStages(): Type
	{
		$stages = $this->getStages();
		$sortedStages = clone $stages;
		foreach($sortedStages as $stage)
		{
			$sortedStages->remove($stage);
		}
		$sorts = array_flip($stages->getSortList());
		ksort($sorts);
		$ids = $stages->getIdList();
		foreach($sorts as $sort => $index)
		{
			$id = $ids[$index];
			if(!$id)
			{
				continue;
			}
			$stage = $stages->getByPrimary($id);
			if(!$stage)
			{
				continue;
			}
			if($stage->isFinal())
			{
				continue;
			}
			$sortedStages->add($stage);
		}
		foreach($stages as $stage)
		{
			if($stage->isSuccess())
			{
				$sortedStages->add($stage);
				break;
			}
		}
		foreach($stages as $stage)
		{
			if($stage->isFail())
			{
				$sortedStages->add($stage);
			}
		}
		$this->stages = $sortedStages;
		unset($stages);

		return $this;
	}

	public function getFirstStage(): ?Stage
	{
		$stages = $this->getStages();
		if($stages->count() > 0)
		{
			return $stages->getAll()[0];
		}

		return null;
	}

	public function getSuccessStage(): ?Stage
	{
		$stages = $this->getStages();
		foreach($stages as $stage)
		{
			if($stage->isSuccess())
			{
				return $stage;
			}
		}

		return null;
	}

	public function getFailStages(): EO_Stage_Collection
	{
		$stages = $this->getStages();
		$failStages = clone $stages;
		foreach($failStages as $stage)
		{
			if(!$stage->isFail())
			{
				$failStages->remove($stage);
			}
		}

		return $failStages;
	}

	public function getFinalStages(): EO_Stage_Collection
	{
		$stages = $this->getStages();
		$finalStages = clone $stages;
		foreach($finalStages as $stage)
		{
			if(!$stage->isFinal())
			{
				$finalStages->remove($stage);
			}
		}

		return $finalStages;
	}

	public function createStage(): Stage
	{
		$stage = StageTable::createObject();
		$stage->setTypeId($this->getId());

		return $stage;
	}

	public function createItem(): Item
	{
		return $this->getFactory()->getItemDataClass($this)::createObject();
	}

	public function getItem(int $itemId): ?Item
	{
		$itemDataClass = $this->getFactory()->getItemDataClass($this);

		return $itemDataClass::getById($itemId)->fetchObject();
	}

	public function getItems(array $parameters = []): Collection
	{
		$itemDataClass = $this->getFactory()->getItemDataClass($this);
		if (
			isset($parameters['filter'])
			&& is_array($parameters['filter'])
			&& array_key_exists('*FULL_TEXT.SEARCH_CONTENT', $parameters['filter'])
		)
		{
			if(!is_array($parameters['runtime']))
			{
				$parameters['runtime'] = [];
			}
			$parameters['runtime'][] = $itemDataClass::getFullTextReferenceField();
		}

		return $itemDataClass::getList($parameters)->fetchCollection();
	}

	public function getItemsCount(array $filter = []): int
	{
		$itemDataClass = $this->getFactory()->getItemDataClass($this);
		$parameters = [
			'filter' => $filter,
		];
		if(array_key_exists('*FULL_TEXT.SEARCH_CONTENT', $parameters['filter']))
		{
			$parameters['runtime'][] = $itemDataClass::getFullTextReferenceField();
		}
		$parameters['select'] = [
			new \Bitrix\Main\ORM\Fields\ExpressionField('CNT', 'COUNT(1)')
		];

		$result = $itemDataClass::getList($parameters)->fetch();

		return (int) $result['CNT'];
	}

	public function getItemUserFieldsEntityId(): string
	{
		return $this->getFactory()->getUserFieldEntityId($this->getId());
	}

	public function getUserFieldCollection(): UserFieldCollection
	{
		if($this->userFieldCollection === null)
		{
			$this->userFieldCollection = new UserFieldCollection($this->loadUserFields(), FieldTable::getGroupedList($this->getId(), 0));
		}

		return $this->userFieldCollection;
	}

	protected function loadUserFields(): array
	{
		global $USER_FIELD_MANAGER;
		$fields = [];
		$userFields = $USER_FIELD_MANAGER->GetUserFields($this->getItemUserFieldsEntityId(), 0, LANGUAGE_ID);
		foreach($userFields as $field)
		{
			$field['ID'] = (int)$field['ID'];
			$fields[$field['ID']] = $field;
		}
		if(count($fields) > 0)
		{
			$enumEntity = new \CUserFieldEnum();
			$enumList = $enumEntity->GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => array_keys($fields)]);
			while($enum = $enumList->Fetch())
			{
				$fieldId = (int)$enum['USER_FIELD_ID'];
				if(isset($fields[$fieldId]))
				{
					$fields[$fieldId]['ENUM'][] = $enum;
				}
			}
		}

		return $fields;
	}

	public function getPermissionEntity(): string
	{
		return UserPermissions::ENTITY_TYPE;
	}

	public function getAccessCodesForView(): array
	{
		if(UserPermissions::canViewAnyType())
		{
			return [UserPermissions::ACCESS_CODE_ALL_USERS];
		}
		if($this->getId() > 0)
		{
			return $this->getUserCodesForAction(UserPermissions::ACTION_VIEW);
		}

		return [UserPermissions::ACCESS_CODE_ALL_USERS];
	}

	public function getAccessCodesForModify(): array
	{
		if($this->getId() > 0)
		{
			return $this->getUserCodesForAction(UserPermissions::ACTION_MODIFY);
		}

		return [UserPermissions::ACCESS_CODE_ALL_USERS];
	}

	public function getAccessCodesForAddItems(): array
	{
		if($this->getId() > 0)
		{
			return $this->getUserCodesForAction(UserPermissions::ACTION_ITEMS_CREATE);
		}

		return [UserPermissions::ACCESS_CODE_ALL_USERS];
	}

	public function getItemUfNameFieldName(): string
	{
		return 'UF_'.$this->getItemUserFieldsEntityId().'_'.TypeTable::ITEM_TITLE_UF_SUFFIX;
	}

	public function getImage(): string
	{
		$image = parent::getImage();
		if(empty($image))
		{
			$image = 'list';
		}

		return $image;
	}
}