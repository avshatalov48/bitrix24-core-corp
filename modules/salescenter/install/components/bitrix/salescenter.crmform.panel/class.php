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
use Bitrix\Crm\Service\WebForm\WebFormScenarioService;
use Bitrix\Crm\Service\WebForm\Scenario\BaseScenario;
use Bitrix\Main\Engine\CurrentUser;

Loc::loadMessages(__FILE__);

class SalesCenterCrmFormPanel extends CBitrixComponent
{
	private const HELPDESK_SLIDER_URL = 'redirect=detail&code=13774372';
	private const HELPDESK_HASH_WITHOUT_PICTURES = 'withoutpictures';
	private const HELPDESK_HASH_PAYMENT = 'payment';
	private const HELPDESK_HASH_WITH_PICTURES = 'withpictures';
	private const HELPDESK_HASH_VISUAL_GOODS = 'visualgoods';

	private $requiredModules = ['salescenter', 'sale', 'crm'];

	/**
	 * @return string[]
	 */
	public static function getAllowedTemplates(): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		return [
			BaseScenario::SCENARIO_PRODUCT1,
			BaseScenario::SCENARIO_PRODUCT2,
			BaseScenario::SCENARIO_PRODUCT3,
			BaseScenario::SCENARIO_PRODUCT4,
		];
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

		$formsCollection = WebForm\Internals\FormTable::getDefaultTypeList([
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

	/**
	 * Helpdesk pages for need language.
	 *
	 * @param mixed $lang
	 *
	 * @return string
	 */
	private function getHelpdeskPageUrl($lang): string
	{
		$pages = [
			'ru' => 'https://helpdesk.bitrix24.ru/open/13774372/',
			'en' => 'https://helpdesk.bitrix24.com/open/13796854/',
			'de' => 'https://helpdesk.bitrix24.de/open/13790640/',
			'pl' => 'https://helpdesk.bitrix24.pl/open/13796424/',
		];

		// links
		$pages['by'] = $pages['kz'] = $pages['ru'];

		return $pages[$lang] ?? $pages['en'];
	}

	public function prepareResult()
	{
		$this->arResult['HELPDESK_PAGE_URL'] = $this->getHelpdeskPageUrl(LANGUAGE_ID);
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

		$formsCollection = WebForm\Internals\FormTable::getDefaultTypeList([
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
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$presets = [];
		$webFormScenarioService = new WebFormScenarioService(CurrentUser::get());
		$scenarios = $webFormScenarioService->getScenarioList();
		foreach ($scenarios as $scenario)
		{
			if (!in_array($scenario['id'], self::getAllowedTemplates(), true))
			{
				continue;
			}

			$presets[] = [
				'id' => $scenario['id'],
				'title' => $scenario['title'],
				'image' => $this->getScenarioDefaultPictureByTemplateId($scenario['id']),
				'activeImage' => $this->getScenarioActivePictureByTemplateId($scenario['id']),
				'helpdeskHash' => $this->getHelpdeskHashByTemplateId($scenario['id']),
			];
		}

		return $presets;
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

	private function getScenarioDefaultPictureByTemplateId(string $templateId): string
	{
		if (!Loader::includeModule('crm'))
		{
			return '';
		}

		return match ($templateId)
		{
			BaseScenario::SCENARIO_PRODUCT1 => $this->getImagePath().'products1.svg',
			BaseScenario::SCENARIO_PRODUCT2 => $this->getImagePath().'products2.svg',
			BaseScenario::SCENARIO_PRODUCT3 => $this->getImagePath().'products3.svg',
			BaseScenario::SCENARIO_PRODUCT4 => $this->getImagePath().'products4.svg',
			default => '',
		};
	}

	private function getScenarioActivePictureByTemplateId(string $templateId): string
	{
		if (!Loader::includeModule('crm'))
		{
			return '';
		}

		return match ($templateId)
		{
			BaseScenario::SCENARIO_PRODUCT1 => $this->getImagePath().'products1-active.svg',
			BaseScenario::SCENARIO_PRODUCT2 => $this->getImagePath().'products2-active.svg',
			BaseScenario::SCENARIO_PRODUCT3 => $this->getImagePath().'products3-active.svg',
			BaseScenario::SCENARIO_PRODUCT4 => $this->getImagePath().'products4-active.svg',
			default => '',
		};
	}

	private function getHelpdeskHashByTemplateId(string $templateId): string
	{
		if (!Loader::includeModule('crm'))
		{
			return '';
		}

		return match ($templateId)
		{
			BaseScenario::SCENARIO_PRODUCT1 => self::HELPDESK_HASH_WITHOUT_PICTURES,
			BaseScenario::SCENARIO_PRODUCT2 => self::HELPDESK_HASH_PAYMENT,
			BaseScenario::SCENARIO_PRODUCT3 => self::HELPDESK_HASH_WITH_PICTURES,
			BaseScenario::SCENARIO_PRODUCT4 => self::HELPDESK_HASH_VISUAL_GOODS,
			default => '',
		};
	}
}
