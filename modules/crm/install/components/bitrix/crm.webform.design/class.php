<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm\WebForm;
use Bitrix\Crm\SiteButton;


if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);


class CCrmWebFormDesignComponent extends \CBitrixComponent
{
	protected $errors = array();

	/** @var  WebForm\Form */
	protected $crmWebForm;

	public function processPost()
	{
		$request = $this->request;

		$designOptions = is_array($request->get('DESIGN')) ? $request->get('DESIGN') : [];
		$embeddingEnabled = $request->get('EMBEDDING_ENABLED') === 'Y';
		$this->crmWebForm
			->setEmbeddingEnabled($embeddingEnabled)
			->setDesignOptions($designOptions)
			->save();

		if(!$this->crmWebForm->hasErrors())
		{
			$this->redirectTo();
		}
		else
		{
			$this->errors = $this->crmWebForm->getErrors();
			$this->arResult['FORM'] = $this->crmWebForm->get();
		}
	}

	protected function redirectTo()
	{
		$isSaved = $this->request->get('save') === 'Y';
		$url = ($isSaved && !$this->arParams['IFRAME']) ? $this->arParams['PATH_TO_WEB_FORM_LIST'] : $this->arParams['PATH_TO_WEB_FORM_DESIGN'];

		$replaceList = array('id' => $this->crmWebForm->getId(), 'form_id' => $this->crmWebForm->getId());
		$url = CComponentEngine::makePathFromTemplate($url, $replaceList);
		if ($this->arParams['IFRAME'])
		{
			$uri = new \Bitrix\Main\Web\Uri($url);
			$uri->addParams(['IFRAME' => 'Y']);
			if ($isSaved)
			{
				$uri->addParams(['IS_SAVED' => 'Y']);
			}
			$url = $uri->getLocator();
		}
		LocalRedirect($url);
	}

	public function prepareResult()
	{
		$this->arResult['FORM'] = [];

		/**@var $USER \CUser*/
		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());
		$hasAccess = !$CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE);
		if(!$hasAccess || !WebForm\Manager::isEmbeddingAvailable())
		{
			ShowError(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return;
		}
		$this->arResult['PERM_CAN_EDIT'] = !$CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE, 'WRITE');

		$id = $this->arParams['ELEMENT_ID'];
		$this->crmWebForm = new WebForm\Form($id);

		/* Set form data */
		$this->arResult['FORM'] = $this->crmWebForm->get();
		$this->arResult['ERRORS'] = [];

		$this->arResult['EMBEDDING_ENABLED'] = $this->crmWebForm->isEmbeddingEnabled();
		$this->arResult['DESIGN'] = $this->crmWebForm->getDesignOptions();
		$this->arResult['THEMES'] = WebForm\Design::getThemes();
		$this->arResult['THEME_NAMES'] = WebForm\Design::getThemeNames();
		$this->arResult['STYLES'] = WebForm\Design::getStyles();
		$this->arResult['MODES'] = WebForm\Design::getModes();

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		if($request->getRequestMethod() == "POST" && check_bitrix_sessid())
		{
			if(!$this->arResult['PERM_CAN_EDIT'])
			{
				ShowError(Loc::getMessage('CRM_PERMISSION_DENIED'));
				return;
			}
			elseif ($this->arResult['FORM']['IS_READONLY'] !== 'Y')
			{
				$this->processPost();
				$this->arResult['ERRORS'] = $this->errors;
			}

		}
	}

	public function checkParams()
	{
		$this->arParams['IFRAME'] = isset($this->arParams['IFRAME']) ? (bool) $this->arParams['IFRAME'] : $this->request->get('IFRAME') === 'Y';
		$this->arParams['IS_SAVED'] = $this->request->get('IS_SAVED') === 'Y';

		return true;
	}

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			$this->showErrors();
			return;
		}

		if (!$this->checkParams())
		{
			$this->showErrors();
			return;
		}

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('CRM_WEBFORM_DESIGN_TITLE'));

		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	protected function checkModules()
	{
		if(!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		return true;
	}

	protected function hasErrors()
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors()
	{
		if(count($this->errors) <= 0)
		{
			return;
		}

		foreach($this->errors as $error)
		{
			ShowError($error);
		}
	}
}