<?php

use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\UI\Toolbar\ToolbarGuide;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Random;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loader::includeModule('crm');

class CrmToolbarComponent extends Bitrix\Crm\Component\Base
{
	protected function getCommunicationTypesMap(): array
	{
		return [
			'IM' => [
				'icon' => \Bitrix\UI\Buttons\Icon::CHAT,
				'class' => 'BX.Crm.ToolbarComponent.Communications.MessengerButton',
			],
			'EMAIL' => [
				'icon' => \Bitrix\UI\Buttons\Icon::MAIL,
				'class' => 'BX.Crm.ToolbarComponent.Communications.EmailButton',
				'useClientSelector' => true,
			],
			'PHONE' => [
				'icon' => \Bitrix\UI\Buttons\Icon::PHONE_CALL,
				'class' => 'BX.Crm.ToolbarComponent.Communications.PhoneButton',
				'useClientSelector' => true,
			],
		];
	}

	public function executeComponent()
	{
		$this->init();

		$this->arResult = $this->arParams;
		$views = $this->arResult['views'] ?? [];
		$this->arResult['views'] = [];

		$afterNavigationPos = defined('\Bitrix\UI\Toolbar\ButtonLocation::AFTER_NAVIGATION')
			? ButtonLocation::AFTER_NAVIGATION
			: 'after_navigation';

		foreach ($views as $view)
		{
			$position = $view['position'] ?? null;
			if ($position === ButtonLocation::RIGHT)
			{
				$this->arResult['views']['right'][] = $view;
			}
			elseif ($position === $afterNavigationPos)
			{
				$this->arResult['views']['counter_panel_html'] = $view['html'];
			}
			else
			{
				$this->arResult['views']['left'][] = $view;
			}
		}

		if(!empty($this->arResult['filter']) && is_array($this->arResult['filter']))
		{
			$this->arResult['filter']['THEME'] = Bitrix\Main\UI\Filter\Theme::MUTED;
			Toolbar::addFilter($this->arResult['filter']);
		}

		$isAddStar = $this->arResult['isWithFavoriteStar'] ?? false;
		if($isAddStar === true)
		{
			Toolbar::addFavoriteStar();
		}
		else
		{
			Toolbar::deleteFavoriteStar();
		}

		$isTitleNoShrink = $this->arResult['isTitleNoShrink'] ?? false;
		if ($isTitleNoShrink === true)
		{
			Toolbar::setTitleNoShrink();
		}

		$isEditableTitle = $this->arResult['isEditableTitle'] ?? false;
		if ($isEditableTitle === true)
		{
			Toolbar::addEditableTitle();
		}

		if (!isset($this->arResult['buttons']) || !is_array($this->arResult['buttons']))
		{
			$this->arResult['buttons'] = [];
		}
		if (!empty($this->arResult['communications']))
		{
			$this->prepareCommunicationsButtons();
		}
		if (!empty($this->arResult['buttons']))
		{
			Extension::load(['ui.buttons', 'ui.icons']);
			foreach($this->arResult['buttons'] as $location => $buttons)
			{
				foreach($buttons as $button)
				{
					if($button instanceof Button)
					{
						Toolbar::addButton($button, $location);
					}
				}
			}
		}
		if (!empty($this->arResult['~afterTitleHtml']))
		{
			Toolbar::addAfterTitleHtml($this->arResult['~afterTitleHtml']);
		}

		if (!empty($this->arResult['underTitleHtml']))
		{
			Toolbar::addUnderTitleHtml($this->arResult['~underTitleHtml']);
		}

		$this->addGuideToToolbar();

		$this->includeComponentTemplate();
	}

	protected function addGuideToToolbar(): void
	{
		$guide = $this->arResult['guide'] ?? null;

		if (!($guide instanceof ToolbarGuide))
		{
			return;
		}

		global $APPLICATION;
		$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
		$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . '--has-toolbar-custom-html');

		Toolbar::addRightCustomHtml($this->renderGuide($guide));
	}

	protected function renderGuide(ToolbarGuide $guide): string
	{
		$title = htmlspecialcharsbx($guide->getTitle());
		Extension::load(['ui.manual']);

		$manualCode = CUtil::JSEscape($guide->getManualCode());
		$params = \Bitrix\Main\Web\Json::encode([
			'manualCode' => $guide->getManualCode(),
			'urlParams' => [
				'utm_source' => 'portal',
				'utm_medium' => 'referral',
			],

			// @todo is need analytics?
			// 'analytics' => [
			//'tool' => 'tasks',
			//'category' => 'flows',
			//'event' => 'flow_guide_view',
			//'c_section' => 'tasks',
			//'c_sub_section' => 'flows_grid',
			//'c_element' => 'guide_button',
			// ],
		]);

		$postfix = Random::getString(4);
		$showManualFunctionName = 'crmToolbarShowManual_' . $manualCode . '_' . $postfix;

		return <<<HTML
			<div class="crm-toolbar__guide-btn" onclick="{$showManualFunctionName}()" data-id="crm-toolbar__guide-btn">
				<span class="crm-toolbar__guide-btn_icon-avatar" style="--crm-toolbar__guide-logo-src: url({$guide->getNormalizedLogoPath()})">
					<div class="crm-toolbar__guide-btn_icon">
						<div class="ui-icon-set --play-circle" style="--ui-icon-set__icon-size: 18px; --ui-icon-set__icon-color: white;"></div>
					</div>
				</span>
				<span class="crm-toolbar__guide-btn_text">{$title}</span>
			</div>
			<script>
				function {$showManualFunctionName}()
				{
					const manual = new BX.UI.Manual.Manual($params);
					manual.open();
				};
			</script>
HTML;
	}

	private function prepareCommunicationsButtons(): void
	{
		$multiFields = $this->arResult['communications']['arrangedMultiFields'] ?? [];
		if (empty($multiFields))
		{
			$multiFields = FieldMultiTable::rearrangeDataByTypesAndEntities(
				$this->arResult['communications']['multiFields'] ?? []
			);
		}

		$isEnabled = $this->arResult['communications']['isEnabled'] ?? true;

		foreach ($this->getCommunicationTypesMap() as $type => $info)
		{
			$button = new Button([
				'color' => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
				'icon' => $info['icon'],
			]);
			$button->setCollapsed();

			$isTypeEnabled = $isEnabled && !empty($multiFields[$type]);
			if (!$isTypeEnabled)
			{
				$button->setDisabled();
			}

			$this->arResult['communications']['buttons'][$type] = [
				'buttonUniqueId' => $button->getUniqId(),
				'data' => $multiFields[$type] ?? null,
				'ownerInfo' => $this->arResult['communications']['ownerInfo'] ?? [],
				'class' => $info['class'],
				'useClientSelector' => $info['useClientSelector'] ?? false,
			];

			if (!isset($this->arResult['buttons'][ButtonLocation::RIGHT]))
			{
				$this->arResult['buttons'][ButtonLocation::RIGHT] = [];
			}

			array_unshift(
				$this->arResult['buttons'][ButtonLocation::RIGHT],
				$button,
			);
		}
	}
}
