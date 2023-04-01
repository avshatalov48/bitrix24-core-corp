<?php

namespace Bitrix\Crm\Kanban\Entity;

trait DynamicInlineEditorFieldsTrait
{
	protected function getDefaultAdditionalEditFields(): array
	{
		$fields = [];

		$component = $this->getDetailComponent();
		if (!$component)
		{
			return $fields;
		}

		$entityConfig = $component->getInlineEditorEntityConfig();
		foreach ($entityConfig as $section)
		{
			foreach ($section as $element)
			{
				if (isset($element['name']))
				{
					$fields[$element['name']] = '';
				}
			}
		}

		return $fields;
	}
}
