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
		foreach ($entityConfig as $sections)
		{
			if (isset($sections['elements']))
			{
				foreach ($sections['elements'] as $element)
				{
					if (isset($element['name']))
					{
						$fields[$element['name']] = '';
					}
				}
			}
			else
			{
				foreach ($sections as $section)
				{
					foreach ($section as $element)
					{
						if (isset($element['name']))
						{
							$fields[$element['name']] = '';
						}
					}
				}
			}
		}

		return $fields;
	}
}
