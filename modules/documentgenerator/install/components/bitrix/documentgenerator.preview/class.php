<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Template;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\ModuleManager;

class DocumentsPreviewComponent extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	/** @var Template */
	protected $template;
	/** @var Document */
	protected $document;
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
			$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
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
		$arParams['EDIT'] = false;
		if(isset($arParams['MODE']) && $arParams['MODE'] === 'edit')
		{
			$arParams['EDIT'] = true;
		}

		return $arParams;
	}

	/**
	 * @return mixed|void
	 */
	public function executeComponent()
	{
		Loc::loadLanguageFile(__FILE__);
		if(!$this->includeModules())
		{
			ShowError(Loc::getMessage('DOCGEN_MODULE_IS_NOT_INSTALLED'));
			return;
		}

		$this->arResult['signedParameters'] = $this->getSignedParameters();
		$this->arResult['previewId'] = 'documentgenerator-preview-' . $this->randString();
		$result = $this->initDocument();
		if($result->isSuccess())
		{
			$this->arResult['TITLE'] = $this->document->getTitle();
			$emptyFields = $this->document->checkFields();
			if((!empty($emptyFields) && !$this->document->ID) || $this->arParams['EDIT'])
			{
				$this->arResult['REQUIRED_FIELDS'] = $emptyFields;
				$this->includeComponentTemplate('edit');
				return;
			}
			$result = $this->document->getFile();
			if($result->isSuccess())
			{
				// todo изолировать
				if(!isset($this->arParams['DOCUMENT_ID']))
				{
					$this->addComment();
				}
				$this->arResult['NAME'] = $this->template->getFileName();
				$this->arResult['downloadUrl'] = $result->getData()['downloadUrl'];
				$this->arResult['publicUrl'] = $result->getData()['publicUrl'];
				$this->arResult['TITLE'] = $this->template->NAME.' №'.$this->document->ID;
				$this->arResult['documentId'] = $this->document->ID;
				$this->arResult['stampsEnabled'] = $this->document->isStampsEnabled();
				if($result->getData()['imageUrl'])
				{
					$this->arResult['imageUrl'] = $result->getData()['imageUrl'];
					$this->arResult['printUrl'] = $result->getData()['printUrl'];
					$this->arResult['pdfUrl'] = $result->getData()['pdfUrl'];
				}
				else
				{
					$templateImage = $this->template->getPreview();
					if($templateImage->isSuccess())
					{
						if($templateImage->getData()['imageUrl'])
						{
							$this->arResult['imageUrl'] = $templateImage->getData()['imageUrl'];
						}
					}
					$this->arResult['pullTag'] = $result->getData()['pullTag'];
				}
			}
			else
			{
				$templateImage = $this->template->getPreview();
				if($templateImage->isSuccess())
				{
					if($templateImage->getData()['imageUrl'])
					{
						$this->arResult['imageUrl'] = $templateImage->getData()['imageUrl'];
					}
					else
					{
						$this->arResult['pullTag'] = $templateImage->getData()['pullTag'];
					}
				}
				else
				{
					$result->addErrors($templateImage->getErrors());
				}
				$this->arResult['ERROR'] = implode('<br />', $result->getErrorMessages());
			}
		}
		else
		{
			$this->arResult['ERROR'] = implode('<br />', $result->getErrorMessages());
		}
		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return array(
			'TEMPLATE_ID',
			'VALUE',
			'PROVIDER',
			'VALUES',
		);
	}

	/**
	 * @param int $documentId
	 * @param int $withImages
	 * @return AjaxJson
	 */
	public function addStampsAction($documentId, $withImages = 0)
	{
		if(!$this->includeModules())
		{
			return AjaxJson::createError(new ErrorCollection([new Error(Loc::getMessage('DOCGEN_MODULE_IS_NOT_INSTALLED'))]));
		}
		if($documentId > 0)
		{
			$this->document = Document::loadById($documentId);
		}
		if(!$this->document)
		{
			return AjaxJson::createError(new ErrorCollection([new Error('Document with id '.$documentId.' is not found')]));
		}
		if(!$this->document->hasAccess(Driver::getInstance()->getUserId()))
		{
			return AjaxJson::createDenied();
		}

		$values = $this->arParams['VALUES'];
		$this->document->enableStamps($withImages > 0);

		$result = $this->document->update($values);
		if($result->isSuccess())
		{
			return AjaxJson::createSuccess($result->getData());
		}

		return AjaxJson::createError($result->getErrorCollection());
	}

	/* todo изолировать */
	protected function addComment()
	{
		if($this->includeModules())
		{
			$text = 'Создан новый документ по шаблону '.$this->template->NAME;
			$entryID = \Bitrix\Crm\Timeline\DocumentEntry::create(
				[
					'TEXT' => $text,
					'AUTHOR_ID' => Driver::getInstance()->getUserId(),
					'BINDINGS' => [['ENTITY_TYPE_ID' => CCrmOwnerType::Deal, 'ENTITY_ID' => $this->value]],
					'SETTINGS' => [
						'DOCUMENT_ID' => $this->document->ID,
					]
				]
			);
			if($entryID > 0)
			{
				$saveData = array(
					'COMMENT' => $text,
					'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
					'ENTITY_ID' => $this->value,
					'USER_ID' => Driver::getInstance()->getUserId(),
					'DOCUMENT_ID' => $this->document->ID,
				);
				Bitrix\Crm\Timeline\DocumentController::getInstance()->onCreate($entryID, $saveData);
			}
		}
	}

	/**
	 * @return \Bitrix\Main\Result
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function initDocument()
	{
		$result = new \Bitrix\Main\Result();
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
					$this->template = $document->getTemplate();
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
				if($template)
				{
					$template->setSourceType($this->arParams['PROVIDER']);
					$this->template = $template;
					$this->value = $this->arParams['VALUE'];
					$this->document = Document::createByTemplate($template, $this->value);
					if(!$this->document->hasAccess(Driver::getInstance()->getUserId()))
					{
						$result->addError(new Error('Access denied'));
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
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function includeModules()
	{
		if(Loader::includeModule('documentgenerator'))
		{
			if(empty($this->arParams['MODULE']) || (ModuleManager::isModuleInstalled($this->arParams['MODULE'])) && Loader::includeModule($this->arParams['MODULE']))
			{
				return true;
			}
		}

		return false;
	}
}