<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\AI;
use Bitrix\Main;

final class MainPostForm extends CBitrixComponent
{
	const STATUS_SCOPE_MOBILE = 'mobile';
	const STATUS_SCOPE_WEB = 'web';
	private $scope;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->scope = self::STATUS_SCOPE_WEB;
		if (is_callable(array('\Bitrix\MobileApp\Mobile', 'getApiVersion')) && \Bitrix\MobileApp\Mobile::getApiVersion() >= 1 &&
			defined("BX_MOBILE") && BX_MOBILE === true)
			$this->scope = self::STATUS_SCOPE_MOBILE;

		$templateName = $this->getTemplateName();

		if ((empty($templateName) || $templateName == ".default" || $templateName == "bitrix24"))
		{
			if ($this->isWeb())
				$this->setTemplateName(".default");
			else
				$this->setTemplateName("mobile_app");
		}
	}

	protected function isWeb()
	{
		return ($this->scope == self::STATUS_SCOPE_WEB);
	}

	private function prepareParams(&$arParams)
	{
		if($arParams["FORM_ID"] == '')
			$arParams["FORM_ID"] = "POST_FORM_".RandString(3);
		$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? \CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), "", $arParams["NAME_TEMPLATE"]);

		if ($this->iaAIAvailable(true))
		{
			$arParams["PARSER"][] = 'AIText';
		}
		if ($this->iaAIAvailable(false))
		{
			$arParams["PARSER"][] = 'AIImage';
		}
	}

	public function executeComponent()
	{
		$this->prepareParams($this->arParams);

		$this->includeComponentTemplate();
	}

	private function iaAIAvailable(bool $isTextFor = true): bool
	{
		if (!Main\Loader::includeModule('ai'))
		{
			return false;
		}

		$engine = AI\Engine::getByCategory('text', new AI\Context('main', ''));
		if (is_null($engine))
		{
			return false;
		}
		if ($isTextFor)
		{
			return Main\Config\Option::get('main', 'bitrix:main.post.form:AIText', 'N') === 'Y';
		}

		return Main\Config\Option::get('main', 'bitrix:main.post.form:AIImage', 'N') === 'Y';
	}

	/*
		\COption::SetOptionString('main', 'bitrix:main.post.form:AIText', 'Y');
		\COption::SetOptionString('main', 'bitrix:main.post.form:AIImage', 'Y');
		\COption::SetOptionString('tasks', 'tasks_ai_text_available', 'N');
		\COption::SetOptionString('tasks', 'tasks_ai_image_available', 'N');
		\COption::SetOptionString('socialnetwork', 'ai_base_enabled', 'N');
	*/
}