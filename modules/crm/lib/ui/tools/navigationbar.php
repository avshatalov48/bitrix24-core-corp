<?php

namespace Bitrix\Crm\UI\Tools;

use Bitrix\Crm\RepeatSale\Widget\WidgetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Buttons\Tag;

class NavigationBar
{
	public const BINDING_MENU_MASK = '/(lead|deal|invoice|quote|company|contact|order)/i';

	private array $switchViewList = [
		'items' => [],
		'binding' => [],
	];
	private string $automationView = '';
	private string $repeatSaleView = '';
	private bool $isEnabled = true;

	public function __construct(array $input)
	{
		$this->init($input);
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	private function getActiveView(): ?string
	{
		$viewList = $this->getSwitchViewList();
		$items = $viewList['items'] ?? [];

		foreach ($items as $item)
		{
			if ($item['active'])
			{
				return $item['id'];
			}
		}

		return null;
	}

	public function getSwitchViewList(): array
	{
		return $this->switchViewList;
	}

	public function getAutomationView(): string
	{
		return $this->automationView;
	}

	public function getAutomationViewLayout(): Button
	{
		Container::getInstance()->getLocalization()->loadMessages();

		return Button::create([
			'tag' => Tag::LINK,
			'color' => Color::LIGHT_BORDER,
			'icon' => Icon::ROBOTS,
			'classList' => [
				'crm-robot-btn', // used for custom styles and js
				'ui-btn-themes',
			],
			'noCaps' => true,
			'round' => true,
			'text' => Loc::getMessage('CRM_COMMON_ROBOTS'),
		]);
	}

	public function getRepeatSaleView(): string
	{
		return $this->repeatSaleView;
	}

	public function getRepeatSaleViewLayout(): ?Button
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$config = WidgetManager::getInstance()->getWidgetConfig();
		$type = $config['type'] ?? null;
		if ($type === null)
		{
			return null;
		}

		Extension::load([
			'crm.repeat-sale.widget',
		]);

		$showConfetti = $config['showConfetti'] ?? false;
		$periodTypeId = $config['periodTypeId'] ?? null;

		$params = Json::encode([
			'showConfetti' => $showConfetti,
			'periodTypeId' => $periodTypeId,
		]);

		return new Button([
			'text' => Loc::getMessage('CRM_COMMON_REPEAT_SALE'),
			'noCaps' => true,
			'round' => true,
			'color' => Color::LIGHT_BORDER,
			'tag' => Tag::LINK,
			'icon' => Icon::COPILOT,
			'classList' => [
				'crm-repeat-sale-btn',
				'ui-btn-themes',
			],
			'onclick' => new JsCode(
				"BX.Crm.RepeatSale.Widget.execute(
					'{$type}',
					event.target,
					$params,
				)",
			),
			'dataset' => [
				'id' => 'crm-repeat-sale-widget-button',
				'subsection' => $this->getActiveView(),
			],
		]);
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

				if (!Loader::includeModule('ui'))
				{
					continue;
				}

				$button = $this->getAutomationViewLayout();
				$button->setLink((string)$itemUrl);

				$this->automationView = $button->render(false);

				continue;
			}

			if ($itemId === 'repeatSale')
			{
				$button = $this->getRepeatSaleViewLayout();

				$jsInit = defined('AIR_SITE_TEMPLATE') === false;
				$this->repeatSaleView = $button?->render($jsInit) ?? '';

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
