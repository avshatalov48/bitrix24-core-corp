<?php

namespace Bitrix\Crm\WebForm\Options\Integration\Compatible;

use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\WebForm\Options\Integration;

final class FacebookFieldsMapper implements Integration\IFieldMapper
{
	/**@var Form $integration*/
	private $form;

	/**
	 * @param Form $form
	 */
	public function __construct(Form $form)
	{
		$this->form = $form;
	}

	/**
	 * @param array $incomeValues
	 *
	 * @return array
	 */
	public function prepareFormFillResult(array $incomeValues): array
	{
		$formFieldsWithResult = $this->form->getFieldsMap();
		foreach ($formFieldsWithResult as $key => $field)
		{
			$fieldName = $field['name'];
			if(!$fieldName || !$values = $incomeValues[$fieldName])
			{
				$values = [];
			}

			$field["values"] = is_array($values)? $values : [$values];
			$formFieldsWithResult[$key] = $field;
		}

		return $formFieldsWithResult;
	}
}
