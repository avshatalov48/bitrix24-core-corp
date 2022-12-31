<?php

namespace Bitrix\Crm\Component;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Request;
use Bitrix\Main\Web\Uri;

abstract class Base extends \CBitrixComponent implements Errorable
{
	public const TOOLBAR_SETTINGS_BUTTON_ID = 'crm-toolbar-settings-button';

	/** @var ErrorCollection */
	protected $errorCollection;
	/** @var \Bitrix\Crm\Service\UserPermissions */
	protected $userPermissions;
	protected $entityTypeId;

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		return ($this->errorCollection ? $this->errorCollection->toArray() : []);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error
	 */
	public function getErrorByCode($code): ?Error
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @return string[]
	 */
	public function getErrorMessages(): array
	{
		$messages = [];
		foreach ($this->getErrors() as $error)
		{
			$messages[] = $error->getMessage();
		}

		return $messages;
	}

	protected function init(): void
	{
		$this->errorCollection = new ErrorCollection();
		// always load common messages
		Container::getInstance()->getLocalization()->loadMessages();
		// Set if it wasn't set in __construct (\Bitrix\Crm\Component\EntityDetails\BaseComponent::__construct)
		if (is_null($this->userPermissions))
		{
			$this->userPermissions = Container::getInstance()->getUserPermissions();
		}
	}

	protected function isIframe(): bool
	{
		return ($this->request->get('IFRAME') === 'Y');
	}

	protected function getApplication(): \CMain
	{
		global $APPLICATION;
		return $APPLICATION;
	}

	protected function fillParameterFromRequest(string $parameterName, array &$arParams, Request $request = null): void
	{
		if(!$request)
		{
			$request = $this->request;
		}

		if(!empty($arParams[$parameterName]))
		{
			return;
		}

		$value = $request->get($parameterName);
		if(!empty($value))
		{
			$arParams[$parameterName] = $value;
		}
	}

	protected function prepareUserDataForGrid(array $userData): string
	{
		return '<a href="'.htmlspecialcharsbx($userData['SHOW_URL']).'">'.htmlspecialcharsbx($userData['FORMATTED_NAME']).'</a>';
	}

	protected function getEntityTypeIdFromParams(): int
	{
		$entityTypeId = 0;
		if (isset($this->arParams['entityTypeId']) && $this->arParams['entityTypeId'] >= 0)
		{
			$entityTypeId = (int)$this->arParams['entityTypeId'];
		}
		elseif (isset($this->arParams['ENTITY_TYPE_ID']) && $this->arParams['ENTITY_TYPE_ID'] >= 0)
		{
			$entityTypeId = (int)$this->arParams['ENTITY_TYPE_ID'];
		}

		return $entityTypeId;
	}

	public function addTopPanel(\CBitrixComponentTemplate $template): void
	{
		if (!$this->isIframe() && !IntranetManager::isEntityTypeInCustomSection($this->getEntityTypeIdFromParams()))
		{
			$template->setViewTarget('above_pagetitle');
			global $APPLICATION;
			$APPLICATION->IncludeComponent(
				'bitrix:crm.control_panel',
				'',
				$this->getTopPanelParameters(),
				$this
			);
			$template->endViewTarget();
		}
	}

	protected function getTopPanelParameters(): array
	{
		return [
			'ID' => $this->getTopPanelId($this->getEntityTypeIdFromParams()),
			'ACTIVE_ITEM_ID' => $this->getTopPanelId($this->getEntityTypeIdFromParams()),
			'PATH_TO_QUOTE_DETAILS' => Container::getInstance()->getRouter()->getItemDetailUrlCompatibleTemplate(\CCrmOwnerType::Quote),
		];
	}

	protected function getTopPanelId(int $entityTypeId): string
	{
		return \CCrmOwnerType::ResolveName($entityTypeId);
	}

	public function addToolbar(\CBitrixComponentTemplate $template): void
	{
		$parameters = $this->getToolbarParameters();
		if(!empty($parameters))
		{
			$bodyClass = $GLOBALS['APPLICATION']->GetPageProperty('BodyClass');
			$GLOBALS['APPLICATION']->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-pagetitle-view');

			$template->SetViewTarget('below_pagetitle', 100);
			global $APPLICATION;
			$APPLICATION->IncludeComponent(
				"bitrix:crm.toolbar",
				"",
				$parameters,
				$this
			);
			$template->EndViewTarget();
		}
	}

	protected function getToolbarParameters(): array
	{
		return [
			'buttons' => [], //ui.toolbar buttons
			'filter' => [], //filter options
			'views' => [],
			'isWithFavoriteStar' => false,
			'spotlight' => null,
		];
	}

	/**
	 * @param Category[] $categories
	 * @return array
	 */
	protected function getToolbarCategories(array $categories): array
	{
		$menu = [];
		foreach($categories as $category)
		{
			$menu[] = [
				'id' => 'toolbar-category-' . $category->getId(),
				'categoryId' => $category->getId(),
				'text' => htmlspecialcharsbx($category->getName()),
				'href' => $this->getListUrl($category->getId()),
			];
		}

		return $menu;
	}

	public function addJsRouter(\CBitrixComponentTemplate $template): void
	{
		$template->setViewTarget('above_pagetitle');

		\Bitrix\Main\UI\Extension::load('crm.router');

		$router = Container::getInstance()->getRouter();

		$urlTemplates = \CUtil::PhpToJSObject($router->getTemplatesForJsRouter());

		$script = "<script>
				BX.ready(function()
				{
					BX.Crm.Router.Instance.setUrlTemplates({$urlTemplates});";

		$entityTypeId = (int)$this->entityTypeId;
		if ($entityTypeId > 0)
		{
			$currentListView = \CUtil::JSEscape($router->getCurrentListView($entityTypeId));
			$script .= "BX.Crm.Router.Instance.setCurrentListView({$entityTypeId}, '{$currentListView}');";
		}
		$script .= "
				});
			</script>";

		echo $script;

		$template->endViewTarget();
	}

	protected function getListUrl(int $categoryId = null): Uri
	{
		return Container::getInstance()->getRouter()->getItemListUrl($this->entityTypeId, $categoryId);
	}
}
