<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class TextWithTranslationField extends Validator
{
	protected string $fieldToCheck;

	public function __construct(Dto $dto, string $fieldToCheck)
	{
		parent::__construct($dto);

		$this->fieldToCheck = $fieldToCheck;
	}

	public function validate(array $fields): Result
	{
		$result = new Result();

		if (isset($fields[$this->fieldToCheck]) && is_array($fields[$this->fieldToCheck]))
		{
			$languages = $this->getLanguages();
			foreach ($fields[$this->fieldToCheck] as $lid => $value)
			{
				if (!in_array($lid, $languages))
				{
					$result->addError(new Error(Loc::getMessage('CRM_TIMELINE_DTO_VALIDATOR_WRONG_LANG', [
							'#LANG#' => $lid,
						]),
						'WRONG_LANG',
						[
							'LANG' => $lid,
							'PARENT_OBJECT' => $this->dto->getName(),
						]
					));
				}
				elseif (!is_string($value))
				{
					$result->addError($this->getWrongFieldError($this->fieldToCheck . '[' . $lid . ']', $this->dto->getName()));
				}
			}
		}

		return $result;
	}

	private function getLanguages(): array
	{
		return array_column(
			\Bitrix\Main\Localization\LanguageTable::query()
				->setSelect(['LID'])
				->setCacheTtl(3600)
				->fetchAll(),
			'LID'
		);
	}
}
