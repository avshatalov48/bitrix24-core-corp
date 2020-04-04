<?php

namespace Bitrix\DocumentGenerator\Components;

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Template;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
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
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function initDocument()
	{
		$result = new Result();
		if(!$this->includeModules())
		{
			$result->addError(new Error('no modules'));
		}
		else
		{
			if(isset($this->arParams['DOCUMENT_ID']) && $this->arParams['DOCUMENT_ID'] > 0)
			{
				$document = Document::loadById($this->arParams['DOCUMENT_ID']);
				if($document)
				{
					$this->document = $document;
					if(!$this->document->hasAccess(Driver::getInstance()->getUserId()))
					{
						$result->addError(new Error('Access denied'));
						return $result;
					}
					$provider = $this->document->getProvider();
					if($provider)
					{
						$this->value = $provider->getSource();
					}
					$this->template = $document->getTemplate();
					if($this->template && $this->template->MODULE_ID != $this->getModule())
					{
						$result->addError(new Error('Access denied'));
					}
				}
				else
				{
					$result->addError(new Error('Document with id '.$this->arParams['DOCUMENT_ID'].' is not found'));
				}
				return $result;
			}
			elseif(isset($this->arParams['TEMPLATE_ID']) && isset($this->arParams['PROVIDER']) && isset($this->arParams['VALUE']))
			{
				$template = Template::loadById($this->arParams['TEMPLATE_ID']);
				if($template && !$template->isDeleted())
				{
					$template->setSourceType($this->arParams['PROVIDER']);
					if($template->MODULE_ID != $this->getModule())
					{
						$result->addError(new Error('Access denied'));
						return $result;
					}
					$this->template = $template;
					$this->value = $this->arParams['VALUE'];
					$data = [];
					if(isset($this->arParams['NUMBER']) && !empty($this->arParams['NUMBER']))
					{
						$data['NUMBER'] = $this->arParams['NUMBER'];
					}
					$this->document = Document::createByTemplate($template, $this->value, $data);
					if(!$this->document->hasAccess(Driver::getInstance()->getUserId()))
					{
						$result->addError(new Error('Access denied'));
						return $result;
					}
					$this->document->setValues($this->arParams['VALUES']);
				}
				else
				{
					$result->addError(new Error('template not found'));
				}
			}
			else
			{
				$result->addError(new Error('Wrong parameters'));
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