<?php

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Tasks\Internals\Task\EO_Template;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Main\ORM\Fields;

class TemplateObject extends EO_Template
{
	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$fields = TemplateTable::getEntity()->getFields();

		$data = [];
		foreach ($fields as $fieldName => $field)
		{
			if (
				$field instanceof Fields\Relations\Reference
				|| $field instanceof Fields\Relations\OneToMany
				|| $field instanceof Fields\Relations\ManyToMany
				|| $field instanceof Fields\ExpressionField
			)
			{
				continue;
			}

			$data[$fieldName] = $this->get($fieldName);

			if ($data[$fieldName] instanceof DateTime)
			{
				$data[$fieldName] = $data[$fieldName]->getTimestamp();
			}
		}
		return $data;
	}
}