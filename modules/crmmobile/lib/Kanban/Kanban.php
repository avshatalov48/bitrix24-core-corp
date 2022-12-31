<?php


namespace Bitrix\CrmMobile\Kanban;


class Kanban extends \Bitrix\Crm\Kanban
{
	/**
	 * @return array
	 */
	protected function getAdditionalColumnParams(): array
	{
		return [
			'blockPage' => 1,
			'allItemsLoaded' => false,
			'isRefreshing' => true,
		];
	}

	/**
	 * If the field is displayed in the kanban card,
	 * then it is in array format (value and configuration for mobile),
	 * if not displayed, then only the value.
	 * Therefore, we bring the field values to the format for mobile.
	 *
	 * @param array $data
	 * @param $value
	 * @param array|null $displayedFieldsValues
	 */
	protected function prepareField(array &$data, $value, ?array $displayedFieldsValues = []): void
	{
		if (
			isset($data['code'], $displayedFieldsValues[$data['code']])
			&& is_array($displayedFieldsValues[$data['code']])
		)
		{
			$value = $displayedFieldsValues[$data['code']];
		}
		elseif (is_array($value))
		{
			$value['value'] = $value;
		}
		else
		{
			$value = [
				'value' => $value,
			];
		}

		$data['value'] = $value['value'];
		if (isset($value['config']))
		{
			$data['config'] = $value['config'];
		}
	}

	/**
	 * @param array $row
	 * @param array $displayedFields
	 * @return array
	 */
	protected function mergeItemFieldsValues(array $row, array $displayedFields = []): array
	{
		foreach ($displayedFields as $key => $displayedField)
		{
			$displayedFields[$key] = ($displayedField['value'] ?? '');
		}

		return array_merge($row, $displayedFields);
	}

	protected function prepareAdditionalFields(array $item): array
	{
		$result = [];

		if (!array_key_exists('CLIENT', $this->additionalSelect))
		{
			$result['client']['hidden'] = true;
		}

		if (isset($item['ADDITIONAL_CONTACT_INFO']))
		{
			$result['client']['contact'] = [$item['ADDITIONAL_CONTACT_INFO']];
		}
		if (isset($item['ADDITIONAL_COMPANY_INFO']))
		{
			$result['client']['company'] = [$item['ADDITIONAL_COMPANY_INFO']];
		}

		return $result;
	}
}
