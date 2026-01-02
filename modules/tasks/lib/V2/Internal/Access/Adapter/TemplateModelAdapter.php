<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Adapter;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Model;
use Bitrix\Tasks\V2\Internal\Entity;

class TemplateModelAdapter implements EntityModelAdapterInterface
{
	public function __construct(
		private readonly Entity\EntityInterface $entity
	)
	{

	}

	public function transform(): ?AccessibleItem
	{
		if (!$this->entity instanceof Entity\Template)
		{
			return null;
		}

		$data['ID'] = (int)$this->entity->getId();

		$data['GROUP_ID'] = (int)$this->entity->group?->getId();

		if ($this->entity->creator !== null)
		{
			$data['CREATED_BY'] = $this->entity->creator->getId();
		}

		if ($this->entity->responsibleCollection !== null)
		{
			$data['RESPONSIBLES'] = $this->entity->responsibleCollection->getIds();
		}

		if ($this->entity->accomplices !== null)
		{
			$data['ACCOMPLICES'] = $this->entity->accomplices->getIds();
		}

		if ($this->entity->auditors !== null)
		{
			$data['AUDITORS'] = $this->entity->auditors->getIds();
		}

		return Model\TemplateModel::createFromArray($data);
	}

	public function create(): AccessibleItem
	{
		return Model\TemplateModel::createFromId($this->entity->getId());
	}
}
