<?php

namespace Bitrix\Crm\Relation;

use Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap;
use Bitrix\Main\ArgumentOutOfRangeException;

final class Settings
{
	protected $isPredefined = false;
	protected $isChildrenListEnabled = true;
	protected $relationType = RelationType::BINDING;

	public static function createByEntityRelationObject(EO_EntityConversionMap $entityRelation): self
	{
		return
			(new self())
				->setIsPredefined(false)
				->setIsChildrenListEnabled($entityRelation->getIsChildrenListEnabled())
				->setRelationType($entityRelation->getRelationType())
		;
	}

	public function setIsChildrenListEnabled(bool $isEnabled): self
	{
		$this->isChildrenListEnabled = $isEnabled;

		return $this;
	}

	public function isChildrenListEnabled(): bool
	{
		return $this->isChildrenListEnabled;
	}

	public function setRelationType(string $relationType): self
	{
		if (!RelationType::isDefined($relationType))
		{
			throw new ArgumentOutOfRangeException('relationType');
		}

		$this->relationType = $relationType;

		return $this;
	}

	public function getRelationType(): string
	{
		return $this->relationType;
	}

	public function isBinding(): bool
	{
		return $this->relationType === RelationType::BINDING;
	}

	public function isConversion(): bool
	{
		return $this->relationType === RelationType::CONVERSION;
	}

	public function setIsPredefined(bool $isPredefined): self
	{
		$this->isPredefined = $isPredefined;

		return $this;
	}

	public function isPredefined(): bool
	{
		return $this->isPredefined;
	}
}
