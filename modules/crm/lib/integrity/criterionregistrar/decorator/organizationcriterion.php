<?php

namespace Bitrix\Crm\Integrity\CriterionRegistrar\Decorator;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Integrity\CriterionRegistrar;
use Bitrix\Crm\Integrity\DuplicateOrganizationCriterion;
use Bitrix\Main\Result;

final class OrganizationCriterion extends CriterionRegistrar\Decorator
{
	/** @var string */
	private $companyTitleFieldName;

	public function __construct(CriterionRegistrar $wrappee, string $companyTitleFieldName)
	{
		parent::__construct($wrappee);

		$this->companyTitleFieldName = $companyTitleFieldName;
	}

	protected function wrapRegister(CriterionRegistrar\Data $data): Result
	{
		$fields = $data->getCurrentFields();

		$companyTitle = (string)($fields[$this->companyTitleFieldName] ?? '');
		if ($companyTitle !== '')
		{
			DuplicateOrganizationCriterion::register($data->getEntityTypeId(), $data->getEntityId(), $companyTitle);
		}

		return new Result();
	}

	protected function wrapUpdate(CriterionRegistrar\Data $data): Result
	{
		$previousFields = $data->getPreviousFields();
		$currentFields = $data->getCurrentFields();

		$difference = ComparerBase::compareEntityFields($previousFields, $currentFields);

		if ($difference->isChanged($this->companyTitleFieldName))
		{
			DuplicateOrganizationCriterion::register(
				$data->getEntityTypeId(),
				$data->getEntityId(),
				(string)$difference->getCurrentValue($this->companyTitleFieldName),
			);
		}

		return new Result();
	}

	protected function wrapUnregister(CriterionRegistrar\Data $data): Result
	{
		DuplicateOrganizationCriterion::unregister($data->getEntityTypeId(), $data->getEntityId());

		return new Result();
	}
}
