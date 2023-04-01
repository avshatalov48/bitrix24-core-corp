<?php

use Bitrix\Crm\FieldMultiTable;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
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
				'idSuffix' => '_im',
				'className' => 'ui-btn-icon-chat',
				'class' => 'BX.InterfaceToolBarMessengerButton',
			],
			'EMAIL' => [
				'idSuffix' => '_email',
				'className' => 'ui-btn-icon-mail',
				'class' => 'BX.InterfaceToolBarEmailButton',
			],
			'PHONE' => [
				'idSuffix' => '_call',
				'className' => 'ui-btn-icon-phone-call',
				'class' => 'BX.InterfaceToolBarPhoneButton',
				'messages' => [
					'telephonyNotSupported' => Loc::getMessage('CRM_TOOLBAR_TELEPHONY_NOT_SUPPORTED'),
				]
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

		if (!isset($this->arResult['buttons']) || !is_array($this->arResult['buttons']))
		{
			$this->arResult['buttons'] = [];
		}
		if (!empty($this->arResult['communications']))
		{
			$this->arResult['additionalScripts'][] = $this->getInterfaceToolbarScript();
			$multiFields = FieldMultiTable::rearrangeDataByTypesAndEntities(
				$this->arResult['communications']['multiFields'] ?? []
			);
			$isEnabled = $this->arResult['communications']['isEnabled'] ?? true;
			foreach ($this->getCommunicationTypesMap() as $type => $info)
			{
				$isTypeEnabled = $isEnabled && !empty($multiFields[$type]);
				$classList = ['ui-btn-light-border ui-btn-themes'];
				$classList[] = $info['className'];
				if (!$isTypeEnabled)
				{
					$classList[] = 'ui-btn-disabled';
				}
				$button = new Button([
					'baseClassName' => 'ui-btn',
					'classList' => $classList,
				]);
				$this->arResult['communications']['buttons'][$type] = [
					'buttonUniqueId' => $button->getUniqId(),
					'objectId' => 'crm_toolbar_button_' . $info['idSuffix'],
					'messages' => $info['messages'] ?? [],
					'data' => $multiFields[$type] ?? null,
					'ownerInfo' => $this->arResult['communications']['ownerInfo'] ?? [],
					'class' => $info['class'],
				];
				if(!isset($this->arResult['buttons'][ButtonLocation::AFTER_TITLE]))
				{
					$this->arResult['buttons'][ButtonLocation::AFTER_TITLE] = [];
				}
				array_unshift(
					$this->arResult['buttons'][ButtonLocation::AFTER_TITLE],
					$button
				);
			}
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

		$this->includeComponentTemplate();
	}

	protected function getInterfaceToolbarScript(): ?string
	{
		$path = CComponentEngine::makeComponentPath('bitrix:crm.interface.toolbar');
		if($path)
		{
			Loc::loadMessages(Path::combine(
				getLocalPath('components'.$path),
				'templates',
				'slider',
				'template.php'
			));

			return Path::combine(
				getLocalPath('components'.$path),
				'templates',
				'slider',
				'script.js'
			);
		}

		return null;
	}
}
