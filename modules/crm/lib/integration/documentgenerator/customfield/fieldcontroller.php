<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField;

use Bitrix\Crm\Integration\DocumentGenerator\CustomField\Entity\CustomFieldTable;
use Bitrix\Main\Web\Json;

final class FieldController
{
	private ?int $templateId;

	public function __construct(?int $templateId = null)
	{
		$this->templateId = $templateId;
	}

	public function load(): array
	{
		if ($this->templateId <= 0)
		{
			return [];
		}

		$result = CustomFieldTable::getList([
			'filter' => [
				'=TEMPLATE_ID' => $this->templateId,
			],
			'select' => [
				'FIELD_UID',
				'FIELD_VALUE',
			]
		]);

		$values = [];
		while ($row = $result->fetch())
		{
			$values[$row['FIELD_UID']] = $row['FIELD_VALUE'];
		}

		return $values;
	}

	public function save(array $values): void
	{
		if ($this->templateId <= 0)
		{
			return;
		}

		CustomFieldTable::deleteByTemplateId($this->templateId); // remove old values

		// save new values
		foreach ($values as $fieldId => $value)
		{
			CustomFieldTable::add([
				'TEMPLATE_ID' => $this->templateId,
				'FIELD_UID' => $fieldId,
				'FIELD_VALUE' => is_array($value) ? Json::encode($value) : $value,
			]);
		}
	}

	public function clear(): void
	{
		if ($this->templateId <= 0)
		{
			return;
		}

		CustomFieldTable::deleteByTemplateId($this->templateId);
	}
}
