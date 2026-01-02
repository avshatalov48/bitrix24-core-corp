<?php

namespace Bitrix\HumanResources\Service\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\FieldCollection;
use Bitrix\HumanResources\Repository\HcmLink\FieldRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\HcmLink\FieldEntityType;
use Bitrix\HumanResources\Type\HcmLink\FieldType;
use Bitrix\HumanResources\Item;

class FieldService
{
	private readonly FieldRepository $fieldRepository;

	public function __construct(
		?FieldRepository $fieldRepository = null
	)
	{
		$this->fieldRepository = $fieldRepository ?? Container::getHcmLinkFieldRepository();
	}

	public function getListByEntityType(FieldEntityType $fieldEntityType, int $companyId): FieldCollection
	{
		return $this->fieldRepository->getByCompanyIdAndEntityType($companyId, $fieldEntityType);
	}

	public function getListByType(FieldType $fieldType, int $companyId): FieldCollection
	{
		return $this->fieldRepository->getByCompanyIdAndType($companyId, $fieldType);
	}
}