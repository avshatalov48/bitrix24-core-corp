<?php

namespace Bitrix\DocumentGenerator\Components;

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Service\ActualizeQueue;
use Bitrix\DocumentGenerator\Template;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

abstract class ViewComponent extends \CBitrixComponent
{
	/** @var Document */
	protected $document;
	/** @var Template */
	protected $template;
	protected $value;

	/**
	 * @param $arParams
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public function onPrepareComponentParams($arParams)
	{
		$arParams = parent::onPrepareComponentParams($arParams);
		if(!$arParams['VALUES'])
		{
			$arParams['VALUES'] = [];
		}

		if(!isset($arParams['IS_SLIDER']))
		{
			$request = Application::getInstance()->getContext()->getRequest();
			$arParams['IS_SLIDER'] = false;
			if($request->get('IFRAME') === 'Y')
			{
				$arParams['IS_SLIDER'] = true;
			}
		}

		if(isset($arParams['DOCUMENT_ID']))
		{
			$arParams['DOCUMENT_ID'] = intval($arParams['DOCUMENT_ID']);
		}
		if(isset($arParams['TEMPLATE_ID']))
		{
			$arParams['TEMPLATE_ID'] = intval($arParams['TEMPLATE_ID']);
		}

		return $arParams;
	}

	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function initDocument(): Result
	{
		Loc::loadLanguageFile(__FILE__);
		$result = new Result();
		if(!$this->includeModules())
		{
			$result->addError(new Error('no modules'));
		}
		else
		{
			$userPermissions = Driver::getInstance()->getUserPermissions();
			if(isset($this->arParams['DOCUMENT_ID']) && $this->arParams['DOCUMENT_ID'] > 0)
			{
				if(!$userPermissions->canViewDocuments())
				{
					return $result->addError(new Error(Loc::getMessage('DOCGEN_DOCUMENT_VIEW_PERMISSION_ERROR')));
				}
				$document = Document::loadById($this->arParams['DOCUMENT_ID']);
				if($document)
				{
					$this->document = $document;
					if(!$this->document->hasAccess())
					{
						return $result->addError(new Error(Loc::getMessage('DOCGEN_DOCUMENT_VIEW_ACCESS_ERROR')));
					}
					$provider = $this->document->getProvider();
					if($provider)
					{
						$this->value = $provider->getSource();
					}
					$this->template = $document->getTemplate();
					if($this->template && $this->template->MODULE_ID !== $this->getModule())
					{
						return $result->addError(new Error(Loc::getMessage('DOCGEN_DOCUMENT_VIEW_ACCESS_ERROR')));
					}
					$queue = ServiceLocator::getInstance()->get('documentgenerator.service.actualizeQueue');
					$queue->addTask(
						ActualizeQueue\Task::createByDocument($document)
							->setPosition(ActualizeQueue\Task::ACTUALIZATION_POSITION_IMMEDIATELY)
					);
				}
				else
				{
					$result->addError(new Error(Loc::getMessage('DOCGEN_DOCUMENT_NOT_FOUND_ERROR')));
				}
				return $result;
			}
			elseif(isset($this->arParams['TEMPLATE_ID']) && isset($this->arParams['PROVIDER']) && isset($this->arParams['VALUE']))
			{
				if(!$userPermissions->canModifyDocuments())
				{
					return $result->addError(new Error(Loc::getMessage('DOCGEN_DOCUMENT_MODIFY_PERMISSION_ERROR')));
				}
				$template = Template::loadById($this->arParams['TEMPLATE_ID']);
				if($template && !$template->isDeleted())
				{
					$template->setSourceType($this->arParams['PROVIDER']);
					if($template->MODULE_ID != $this->getModule())
					{
						return $result->addError(new Error(Loc::getMessage('DOCGEN_DOCUMENT_VIEW_ACCESS_ERROR')));
					}
					$this->template = $template;
					$this->value = $this->arParams['VALUE'];
					$data = [];
					if(isset($this->arParams['NUMBER']) && !empty($this->arParams['NUMBER']))
					{
						$data['NUMBER'] = $this->arParams['NUMBER'];
					}
					$this->document = Document::createByTemplate($template, $this->value, $data);
					if(!$this->document->hasAccess())
					{
						$result->addError(new Error(Loc::getMessage('DOCGEN_DOCUMENT_VIEW_ACCESS_ERROR')));
						return $result;
					}
					$this->document->setValues($this->arParams['VALUES']);
				}
				else
				{
					$result->addError(new Error(Loc::getMessage('DOCGEN_TEMPLATE_NOT_FOUND_ERROR')));
				}
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('DOCGEN_UNKNOWN_ERROR')));
			}
		}

		return $result;
	}

	/**
	 * @return string
	 */
	protected function getModule()
	{
		return Driver::MODULE_ID;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function includeModules()
	{
		return Loader::includeModule($this->getModule());
	}

	/**
	 * @deprecated
	 * @return \Bitrix\Main\Web\Uri|false
	 */
	protected function getEditTemplateUrl()
	{
		if($this->template && !$this->template->isDeleted())
		{
			$uri = new \Bitrix\Main\Web\Uri('/bitrix/components/bitrix/documentgenerator.templates/slider.php');
			$uri->addParams(['UPLOAD' => 'Y', 'ID' => $this->template->ID, 'MODULE' => $this->template->MODULE_ID]);
			return $uri;
		}

		return false;
	}
}