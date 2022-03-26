<?php

namespace Bitrix\Crm\WebForm\Options\Integration;

use Bitrix\Crm\WebForm\Form;

final class VkontakteFieldsMapper implements IFieldMapper
{
	/**@var array $mappings */
	private $mappings;

	/**@var Form $integration*/
	private $form;

	/**
	 * @param array $mappings
	 * @param Form $form
	 */
	public function __construct(array $mappings, Form $form)
	{
		$this->mappings = array_combine(
			array_column($mappings,'CRM_FIELD_KEY'),
			$mappings
		);
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
			$crmName = $field['name'];
			$incomeFieldKey = $this->mappings[$crmName]['ADS_FIELD_KEY'];
			if (!$incomeFieldKey || !$values = $incomeValues[$incomeFieldKey])
			{
				$field["values"] = [];
				$formFieldsWithResult[$key] = $field;

				continue;
			}

			if (!empty($this->mappings[$crmName]["items"]))
			{
				$values = is_array($values)? current($values) : $values;
				$values = is_string($values)? explode(', ',$values) : $values;
			}

			$values = is_array($values)? $values : [$values];

			if (!empty($items = $this->mappings[$crmName]['items']))
			{
				foreach ($values as $optionKey => $optionValue)
				{
					if (!$item = $items[$optionValue])
					{
						continue;
					}

					$values[$optionKey] = $item;
				}
			}

			$field["values"] = $field["multiple"]? array_values($values) : [implode(', ',$values)];
			$formFieldsWithResult[$key] = $field;
		}

		return $formFieldsWithResult;
	}
}
