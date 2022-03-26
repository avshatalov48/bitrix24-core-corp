<?php

namespace Bitrix\Crm\WebForm\Options\Integration\Compatible;

use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\WebForm\Options\Integration;

final class VkontakteFieldsMapper implements Integration\IFieldMapper
{
	private const MAPPINGS = [
		'LEAD_NAME' => 'first_name',
		'LEAD_LAST_NAME' => 'last_name',
		'LEAD_EMAIL' => 'email',
		'LEAD_PHONE' => 'phone_number',
		'CONTACT_NAME' => 'first_name',
		'CONTACT_LAST_NAME' => 'last_name',
		'CONTACT_EMAIL' => 'email',
		'CONTACT_PHONE' => 'phone_number',
	];


	private function getAdsFieldName(string $crmName): string
	{
		return self::MAPPINGS[$crmName] ?? $crmName;
	}

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
			$fieldName = $this->getAdsFieldName($field['name']);

			if (!$values = $incomeValues[$fieldName])
			{
				$field["values"] = [];
				$formFieldsWithResult[$key] = $field;

				continue;
			}

			if (is_array($field["items"]))
			{
				$values = is_array($values)? current($values) : $values;
				$values = is_string($values)? explode(', ',$values) : $values;
			}

			$values = is_array($values)? $values : [$values];

			if (is_array($field['items']))
			{
				$vkMapping = array_combine(
					array_column($field['items'],'title'),
					$field['items']
				);

				foreach ($values as $optionKey => $optionValue)
				{
					if (!is_array($vkMapping[$optionValue]) || !$item = $vkMapping[$optionValue]['value'])
					{
						unset($values[$optionKey]);
						continue;
					}

					$values[$optionKey] = $item;
				}
			}

			$field["values"] = array_values($values);
			$formFieldsWithResult[$key] = $field;
		}

		return $formFieldsWithResult;
	}
}
