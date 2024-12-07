<?php

namespace Bitrix\Crm\UI\Tools;

class NavigationBar
{
	public const BINDING_MENU_MASK = '/(lead|deal|invoice|quote|company|contact|order)/i';

	private array $switchViewList = [
		'items' => [],
		'binding' => [],
	];
	private string $automationView = '';
	private bool $isEnabled = true;

	public function __construct(array $input)
	{
		$this->init($input);
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	public function getSwitchViewList(): array
	{
		return $this->switchViewList;
	}

	public function getAutomationView(): string
	{
		return $this->automationView;
	}

	private function init(array $input): void
	{
		if (isset($input['~DISABLE_NAVIGATION_BAR']) && $input['~DISABLE_NAVIGATION_BAR'] === 'Y')
		{
			$this->isEnabled = false;

			return;
		}

		$data = isset($input['~NAVIGATION_BAR']) && is_array($input['~NAVIGATION_BAR'])
			? $input['~NAVIGATION_BAR']
			: [];

		if (empty($data) || empty($data['ITEMS']))
		{
			return;
		}

		$itemQty = 0;
		foreach ($data['ITEMS'] as $row)
		{
			$itemQty++;

			$itemId = $row['id'] ?? $itemQty;
			$itemName = $row['name'] ?? $itemId;
			$itemUrl = $row['url'] ?? '';
			if ($itemId === 'automation')
			{
				if (!IsModuleInstalled('bizproc'))
				{
					// hide "Robots" button if module is not installed
					continue;
				}

				$this->automationView = sprintf(
					'<a class="%s" href="%s">%s</a>',
					'ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round crm-robot-btn',
					htmlspecialcharsbx($itemUrl),
					htmlspecialcharsbx($itemName)
				);

				continue;
			}

			$this->switchViewList['items'][] = [
				'id' => htmlspecialcharsbx($itemId),
				'title' => htmlspecialcharsbx($itemName),
				'active' => isset($row['active']) && $row['active'],
				'lockedCallback' => $row['lockedCallback'] ?? '',
				'url' => $itemUrl,
			];
		}

		if (!empty($this->switchViewList['items']))
		{
			$this->switchViewList['binding'] = $data['BINDING'] ?? [];
		}
	}
}
