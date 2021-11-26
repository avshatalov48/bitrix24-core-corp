<?php

namespace Bitrix\Crm\Integration\Intranet\BindingMenu;

abstract class SectionCode
{
	public const SWITCHER = 'crm_switcher';
	public const GRID_CONTEXT_ACTIONS = 'crm_grid_context_actions';
	public const DETAIL = 'crm_detail';
	public const TIMELINE = 'crm_timeline';
	public const DOCUMENTS = 'crm_documents';
	public const TUNNELS = 'crm_tunnels';
	public const AUTOMATION = 'bizproc_automation';

	public static function getAll(): array
	{
		$reflection = new \ReflectionClass(static::class);

		$sections = [];
		foreach ($reflection->getReflectionConstants() as $constant)
		{
			if ($constant->isPublic())
			{
				$sections[] = $constant->getValue();
			}
		}

		return $sections;
	}
}
