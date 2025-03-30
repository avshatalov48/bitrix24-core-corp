<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Helper\IterationHelper;
use Bitrix\Sign\Helper\StringHelper;
use Bitrix\Sign\Internal;
use Bitrix\Sign\Item;
use Bitrix\Sign\Serializer;

use Bitrix\Main;
use Bitrix\Sign\Type\BlockCode;
use Bitrix\Sign\Type\BlockType;
use Bitrix\Sign\Type\Member\Role;

class BlockRepository
{
	private const ROLE_TO_INT_MAP = [
		Role::SIGNER => 0,
		Role::ASSIGNEE => 1,
		Role::REVIEWER => 2,
		Role::EDITOR => 3,
	];

	public function __construct(
		private ?Serializer\ItemPropertyJsonSerializer $serializer
	)
	{
		$this->serializer ??= new Serializer\ItemPropertyJsonSerializer();
	}

	public function add(Item\Block $item): Main\Result
	{
		$block = new Internal\Block();
		$block = $this->getFilledModelFromItem($item, $block);

		$result = $block->save();
		if ($result->isSuccess())
		{
			$item->id = $result->getId();
		}

		return $result;
	}

	public function addCollection(Item\BlockCollection $itemCollection): Main\Result
	{
		foreach ($itemCollection->toArray() as $item)
		{
			$result = $this->add($item);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Main\Result();
	}

	public function deleteById($id): Main\Result
	{
		return Internal\BlockTable::delete($id);
	}

	/**
	 * @param iterable<int> $ids
	 * @return Main\Result
	 */
	public function deleteByIds(iterable $ids): Main\Result
	{
		$ids = IterationHelper::getArrayByIterable($ids);
		if (empty($ids))
		{
			return new Main\Result();
		}

		try
		{
			Internal\BlockTable::deleteByFilter([
				'@ID' => $ids,
			]);
		}
		catch (Main\ArgumentException $e)
		{
			return (new Main\Result())->addError(new Main\Error($e->getMessage()));
		}

		return new Main\Result();
	}

	public function getById(int $id): ?Item\Block
	{
		$model = Internal\BlockTable::getById($id)->fetchObject();
		if (!$model)
		{
			return null;
		}

		return $this->extractItemFromModel($model);
	}

	public function getCollectionByBlankId(int $blankId): Item\BlockCollection
	{
		$collection = Internal\BlockTable::query()
			->setSelect(['*'])
			->whereIn('BLANK_ID', [$blankId])
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($collection);
	}

	public function loadBlocks(Item\Blank $blank): ?Item\BlockCollection
	{
		if ($blank->id === null)
		{
			return null;
		}

		$blank->blockCollection = $this->getCollectionByBlankId($blank->id);
		return $blank->blockCollection;
	}

	private function extractItemFromModel(Internal\Block $model): Item\Block
	{
		$typeByCode = match ($model->getCode())
		{
			BlockCode::SIGN,
			BlockCode::STAMP,
			BlockCode::MY_STAMP,
			BlockCode::MY_SIGN => BlockType::IMAGE,
			BlockCode::MY_REQUISITES, BlockCode::REQUISITES => BlockType::MULTILINE_TEXT,
			default => BlockType::TEXT
		};

		$item = new Item\Block(
			party: $model->getPart(),
			type: $model->getType() ?? $typeByCode,
			code: $model->getCode() ?? '',
			blankId: $model->getBlankId(),
			position: $this->serializer->deserialize($model->getPosition(), Item\Block\Position::class),
			data: $model->getData(),
			id: $model->getId(),
			role: $this->convertIntToRoleWithCompatibility($model->getRole(), $model->getPart()),
		);

		/** Old blank support */
		if (in_array($item->code, BlockCode::getCommon(), true))
		{
			$item->party = 0;
		}

		$serializedStyle = $model->getStyle();
		$normalizedStyle = [];
		foreach ($serializedStyle as $key => $value)
		{
			$normalizedStyle[StringHelper::convertCssCaseToCamelCase($key)] = $value;
		}

		if (!empty($normalizedStyle))
		{
			$item->style = $this->serializer->deserialize($normalizedStyle, Item\Block\Style::class);
		}

		return $item;
	}

	private function extractItemCollectionFromModelCollection(Internal\BlockCollection $modelCollection): Item\BlockCollection
	{
		return new Item\BlockCollection(
			...array_map(
				fn ($model) => $this->extractItemFromModel($model),
				$modelCollection->getAll()
			)
		);
	}

	private function getFilledModelFromItem(Item\Block $item, Internal\Block $model): Internal\Block
	{
		$model = clone $model;
		$model
			->setData($item->data)
			->setCode($item->code)
			->setType($item->type)
			->setCreatedById(CurrentUser::get()->getId() ?? 0)
			->setModifiedById(CurrentUser::get()->getId() ?? 0)
			->setDateCreate(new Main\Type\DateTime())
			->setDateModify(new Main\Type\DateTime())
			->setBlankId($item->blankId)
			->setPart($item->party)
			->setPosition($this->serializer->serialize($item->position))
			->setRole($this->convertRoleToIntWithCompatibility($item->role, $item->party))
		;
		if ($item->style !== null)
		{
			$model->setStyle($this->serializer->serialize($item->style));
		}

		return $model;
	}

	private function convertRoleToIntWithCompatibility(?string $role, ?int $party): ?int
	{
		if ($party > 0 && $role === null)
		{
			$role = \Bitrix\Sign\Compatibility\Role::createByParty($party);
		}

		return $this->convertRoleToInt($role);
	}

	private function convertRoleToInt(?string $role): ?int
	{
		return static::ROLE_TO_INT_MAP[$role] ?? null;
	}

	private function convertIntToRoleWithCompatibility(?int $roleNumber, int $party): ?string
	{
		if ($party > 0 && $roleNumber === null)
		{
			$role = \Bitrix\Sign\Compatibility\Role::createByParty($party);
			$roleNumber = $this->convertRoleToInt($role);
		}

		return $this->convertIntToRole($roleNumber);
	}

	private function convertIntToRole(?int $roleNumber): ?string
	{
		return array_flip(static::ROLE_TO_INT_MAP)[$roleNumber] ?? null;
	}
}
