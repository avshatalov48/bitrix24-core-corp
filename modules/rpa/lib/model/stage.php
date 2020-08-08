<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Permission;
use Bitrix\Rpa\UserField\UserFieldCollection;
use Bitrix\Rpa\UserPermissions;

class Stage extends EO_Stage implements Permission\Containable
{
	protected const DEFAULT_COLOR = 'ace9fb';
	public const SEMANTIC_SUCCESS = 'SUCCESS';
	public const SEMANTIC_FAIL = 'FAIL';

	use Permission\ModelTrait;

	protected $type;
	protected $fieldSettings;
	protected $possibleNextStageIds = [];
	protected $userFieldCollection;

	public function getId(): ?int
	{
		return parent::getId();
	}

	public function getColor(): string
	{
		$color = parent::getColor();
		if(empty($color))
		{
			$color = static::DEFAULT_COLOR;
		}

		return $color;
	}

	public function getType(): Type
	{
		if($this->type === null && $this->getTypeId() > 0)
		{
			$this->type = Driver::getInstance()->getType($this->getTypeId());
		}

		return $this->type;
	}

	public function getUserFieldCollection(): UserFieldCollection
	{
		if($this->userFieldCollection === null)
		{
			$collection = $this->getType()->getUserFieldCollection();
			$this->userFieldCollection = new UserFieldCollection($collection->toArray(), $this->getFieldSettings());
		}

		return $this->userFieldCollection;
	}

	public function getAccessCodesForView(): array
	{
		return $this->getUserCodesForAction(UserPermissions::ACTION_VIEW);
	}

	public function getAccessCodesForModify(): array
	{
		return $this->getUserCodesForAction(UserPermissions::ACTION_MODIFY);
	}

	public function getAccessCodesForItemsMove(): array
	{
		return $this->getUserCodesForAction(UserPermissions::ACTION_MOVE);
	}

	public function getPermissionEntity(): string
	{
		return UserPermissions::ENTITY_STAGE;
	}

	public function getFieldSettings(bool $isFromCache = true): array
	{
		if($this->fieldSettings === null || !$isFromCache)
		{
			$this->fieldSettings = FieldTable::getGroupedList($this->getTypeId(), $this->getId());
		}

		return $this->fieldSettings;
	}

	public function getPossibleNextStageIds(bool $isFromCache = false): array
	{
		if($this->possibleNextStageIds === null || !$isFromCache)
		{
			$this->possibleNextStageIds = [];
			if(UserPermissions::canMoveAnywhere())
			{
				$stages = clone $this->getType()->getStages();
				reset($stages);
				foreach($stages as $stage)
				{
					$stageid = $stage->getId();
					$this->possibleNextStageIds[$stageid] = $stageid;
				}
			}
			else
			{
				$list = StageToStageTable::getList([
					'filter' => [
						'=STAGE_ID' => $this->getId(),
					],
				]);
				while($setting = $list->fetch())
				{
					$stageId = (int) $setting['STAGE_TO_ID'];
					$this->possibleNextStageIds[$stageId] = $stageId;
				}

				if(UserPermissions::isAlwaysCanMoveToTheNextStage())
				{
					$stages = clone $this->getType()->getStages();
					reset($stages);
					$isStageReached = false;
					foreach($stages as $stage)
					{
						if($isStageReached)
						{
							$this->possibleNextStageIds[$stage->getId()] = $stage->getId();
							break;
						}
						if($stage === $this)
						{
							$isStageReached = true;
						}
					}
				}
			}
		}

		return $this->possibleNextStageIds;
	}

	public function getItems(array $parameters = []): Collection
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = [];
		}

		$parameters['filter']['=STAGE_ID'] = $this->getId();
		return $this->getType()->getItems($parameters);
	}

	public function getUserSortedItems(array $parameters = [], int $userId = null): Collection
	{
		if($userId === null)
		{
			$userId = Driver::getInstance()->getUserId();
		}
		if($userId > 0 && $this->getTypeId() > 0)
		{
			if(!isset($parameters['select']))
			{
				$parameters['select'] = ['*'];
			}
			$parameters['select']['USORT'] = 'USER_SORT.SORT';
			$parameters['select'][] = 'USER_SORT.ID';
			$parameters['select'][] = new ExpressionField('EUSORT', 'CASE WHEN %s > 0 THEN %s ELSE 999999999 END', ['USER_SORT.ID', 'USER_SORT.SORT']);
			$sortOrder = 'ASC';
			if(isset($parameters['order']['USORT']) && is_array($parameters['order']) && $parameters['order']['USORT'] === 'DESC')
			{
				$sortOrder = 'DESC';
			}
			$parameters['order'] = [
				'EUSORT' => $sortOrder,
				'MOVED_TIME' => 'DESC',
				'ID' => 'DESC',
			];
			$parameters['runtime'] = [
				PrototypeItem::getUserSortReferenceField($this->getTypeId(), $userId),
			];
		}

		return $this->getItems($parameters);
	}

	public function getItemsCount(): int
	{
		if($this->getId() > 0)
		{
			return $this->getType()->getItemsCount([
				'=STAGE_ID' => $this->getId(),
			]);
		}

		return 0;
	}

	public function isFirst(): bool
	{
		$firstStage = $this->getType()->getFirstStage();
		return ($firstStage === null || $firstStage->getId() === $this->getId());
	}

	public function isSuccess(): bool
	{
		return ($this->getSemantic() === static::SEMANTIC_SUCCESS);
	}

	public function isFail(): bool
	{
		return ($this->getSemantic() === static::SEMANTIC_FAIL);
	}

	public function isFinal(): bool
	{
		return ($this->isSuccess() || $this->isFail());
	}
}