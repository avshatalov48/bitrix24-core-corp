<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Crm\WebForm;

Loc::loadMessages(__FILE__);

class SalesCenterCrmFormPanel extends CBitrixComponent
{
	const HELPDESK_SLIDER_URL = 'redirect=detail&code=13774372';

	const HELPDESK_PAGE_URL = 'https://helpdesk.bitrix24.ru/open/13774372/';

	private $requiredModules = ['salescenter', 'sale', 'crm'];

	/**
	 * @return string[]
	 */
	public static function getAllowedTemplates(): array
	{
		return ['products1', 'products2', 'products3', 'products4'];
	}

	/**
	 * @param string[] $templates
	 * @return bool
	 */
	public static function hasFormsWithTemplates(array $templates = []): bool
	{
		if (empty($templates))
		{
			$templates = self::getAllowedTemplates();
		}

		$formsCollection = WebForm\Internals\FormTable::getList([
			'select' => ['ID'],
			'filter' => ['ACTIVE' => 'Y', 'TEMPLATE_ID' => $templates],
			'limit' => 1
		]);

		return $formsCollection->fetch() ? true : false;
	}
	
	public function executeComponent()
	{
		if (!$this->requireModules())
		{
			return;
		}

		if (!$this->checkAccess())
		{
			return;
		}

		$this->prepareResult();

		$this->includeComponentTemplate();
	}

	private function requireModules(): bool
	{
		foreach ($this->requiredModules as $module)
		{
			if (!Loader::includeModule($module))
			{
				ShowError(
					Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_MODULE_ERROR', ['#MODULE#' => $module])
				);
				return false;
			}
		}
		return true;
	}

	private function checkAccess(): bool
	{
		if(!SaleManager::getInstance()->isManagerAccess())
		{
			ShowError(Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_ACCESS_DENIED'));
			return false;
		}

		return true;
	}

	public function prepareResult()
	{
		$this->arResult['HELPDESK_PAGE_URL'] = self::HELPDESK_PAGE_URL;
		$this->arResult['crmFormsPanelParams'] = [
			'id' => 'crm-forms-panel',
			'items' => $this->getTiles(),
		];

		return $this->arResult;
	}

	private function getTiles(): array
	{
		$tiles = [];
		$existingForms = $this->loadExistingForms();
		$presets = $this->getPresets();

		foreach ($presets as $preset)
		{
			$tiles[] = [
				'id' => 'crm-form-preset-' . $preset['id'],
				'title' => $preset['title'],
				'image' => $preset['image'],
				'itemSelectedColor' => '#2FC6F6',
				'itemSelectedImage' => $preset['activeImage'],
				'itemSelected' => isset($existingForms[$preset['id']]),
				'data' => [
					'menu' => $this->buildTileMenu($preset, $existingForms),
				],
			];
		}

		return $tiles;
	}

	/**
	 * @return array<string, array>
	 */
	private function loadExistingForms(): array
	{
		$existingForms = [];

		$formsCollection = WebForm\Internals\FormTable::getList([
			'select' => ['ID', 'NAME', 'TEMPLATE_ID'],
			'filter' => ['ACTIVE' => 'Y', 'TEMPLATE_ID' => self::getAllowedTemplates()],
			'order' => ['ID' => 'DESC'],
		]);
		
		while ($form = $formsCollection->fetch())
		{
			if (!isset($existingForms[$form['TEMPLATE_ID']]))
			{
				$existingForms[$form['TEMPLATE_ID']] = [];
			}
			$existingForms[$form['TEMPLATE_ID']][] = $form;
		}

		return $existingForms;
	}

	private function getPresets(): array
	{
		return [
			[
				'id' => 'products1',
				'title' => Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_PRESET_1'),
				'image' => $this->getImagePath().'products1.svg',
				'activeImage' => $this->getImagePath().'products1-active.svg',
				'helpdeskHash' => 'withoutpictures',
			],
			[
				'id' => 'products2',
				'title' => Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_PRESET_2'),
				'image' => $this->getImagePath().'products2.svg',
				'activeImage' => $this->getImagePath().'products2-active.svg',
				'helpdeskHash' => 'payment',
			],
			[
				'id' => 'products3',
				'title' => Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_PRESET_3'),
				'image' => $this->getImagePath().'products3.svg',
				'activeImage' => $this->getImagePath().'products3-active.svg',
				'helpdeskHash' => 'withpictures',
			],
			[
				'id' => 'products4',
				'title' => Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_PRESET_4'),
				'image' => $this->getImagePath().'products4.svg',
				'activeImage' => $this->getImagePath().'products4-active.svg',
				'helpdeskHash' => 'visualgoods',
			],
		];
	}

	private function getImagePath(): string
	{
		static $imagePath = '';
		if ($imagePath)
		{
			return $imagePath;
		}

		$componentPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.crmform.panel');
		$componentPath = getLocalPath('components'.$componentPath);

		$imagePath = $componentPath.'/templates/.default/images/';
		return $imagePath;
	}

	private function buildTileMenu(array $preset, array $existingForms): array
	{
		$formCreateUrlTemplate = WebForm\Manager::getEditUrl(0);
		$formCreateUrl = new Uri($formCreateUrlTemplate);
		$formCreateUrl->addParams([
			'ACTIVE' => 'Y',
			'RELOAD_LIST' => 'N',
			'PRESET' => $preset['id'],
			'analyticsLabel' => 'salescenterCrmFormCreate',
		]);

		$menu = [
			[
				'text' => Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_CREATE_NEW_FORM'),
				'link' => \CUtil::JSEscape((string)$formCreateUrl)
			]
		];

		if (isset($existingForms[$preset['id']]))
		{
			$menu[] = ['delimiter' => true];

			foreach ($existingForms[$preset['id']] as $form)
			{
				$formEditUrl = new Uri(WebForm\Manager::getEditUrl($form['ID']));
				$formEditUrl->addParams(['analyticsLabel' => 'salescenterCrmFormEdit']);
				$menu[] = [
					'text' => $form['NAME'],
					'link' => \CUtil::JSEscape((string)$formEditUrl)
				];
			}
		}

		$menu[] = ['delimiter' => true];

		$helpdeskUrl = self::HELPDESK_SLIDER_URL . '#' . $preset['helpdeskHash'];

		$menu[] = [
			'text' => Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_HOW_IT_WORKS'),
			'onclick' => "BX.Salescenter.CrmFormPanel.closeContextMenus();BX.Salescenter.Manager.openHowCrmFormsWorks(arguments[0], '$helpdeskUrl')"
		];

		return $menu;
	}
}
