<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2017 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

class ImOpenlinesMailHistoryComponent extends CBitrixComponent
{
	protected function checkModules(): bool
	{
		if (!Loader::includeModule('im'))
		{
			\ShowError(Loc::getMessage('IMOL_COMPONENT_MODULE_IM_NOT_INSTALLED'));
			return false;
		}
		if (!Loader::includeModule('imopenlines'))
		{
			\ShowError(Loc::getMessage('IMOL_COMPONENT_MODULE_NOT_INSTALLED'));
			return false;
		}
		return true;
	}

	public function executeComponent(): bool
	{
		$this->includeComponentLang('class.php');

		if (!$this->checkModules())
		{
			\Bitrix\Main\Mail\EventMessageThemeCompiler::stop();
			return false;
		}

		$this->arResult = $this->arParams;

		$select = \Bitrix\ImOpenLines\Model\SessionTable::getSelectFieldsPerformance();
		$select['CONFIG_LANGUAGE_ID'] = 'CONFIG.LANGUAGE_ID';
		$select['CONFIG_LINE_NAME'] = 'CONFIG.LINE_NAME';
		$select['LIVECHAT_TEXT_PHRASES'] = 'LIVECHAT.TEXT_PHRASES';

		$orm = \Bitrix\ImOpenLines\Model\SessionTable::getList([
			'select' => $select,
			'filter' => ['=ID' => $this->arParams['TEMPLATE_SESSION_ID']]
		]);
		$session = $orm->fetch();
		if (!$session)
		{
			return false;
		}

		if (
			isset($session['LIVECHAT_TEXT_PHRASES']['BX_LIVECHAT_TITLE'])
			&& $session['LIVECHAT_TEXT_PHRASES']['BX_LIVECHAT_TITLE'] !== ''
		)
		{
			$this->arResult['TEMPLATE_WIDGET_TITLE'] = $session['LIVECHAT_TEXT_PHRASES']['BX_LIVECHAT_TITLE'];
		}
		else
		{
			$this->arResult['TEMPLATE_WIDGET_TITLE'] = $session['CONFIG_LINE_NAME'];
		}

		$this->arResult['TEMPLATE_WIDGET_SESSION_ID'] = str_replace(
			'#SESSION_ID#',
			$session['ID'],
			Loc::getMessage('IMOL_COMPONENT_SESSION_ID', null, $session['CONFIG_LANGUAGE_ID'] ?: null)
		);

		if ($this->arParams['TEMPLATE_TYPE'] === 'HISTORY')
		{
			$this->arResult['TEMPLATE_MESSAGES'] = \Bitrix\ImOpenLines\Mail::prepareSessionHistoryForTemplate($this->arParams['TEMPLATE_SESSION_ID']);
			if (!$this->arResult['TEMPLATE_MESSAGES'])
			{
				\Bitrix\Main\Mail\EventMessageThemeCompiler::stop();
				return false;
			}
		}
		else
		{
			$this->arResult['TEMPLATE_MESSAGES'] = \Bitrix\ImOpenLines\Mail::prepareOperatorAnswerForTemplate($this->arParams['TEMPLATE_SESSION_ID']);
			if (!$this->arResult['TEMPLATE_MESSAGES'])
			{
				\Bitrix\Main\Mail\EventMessageThemeCompiler::stop();
				return false;
			}
		}

		$parsedUrl = parse_url($this->arResult['TEMPLATE_WIDGET_URL']);
		if (isset($parsedUrl['query']))
		{
			$this->arResult['TEMPLATE_WIDGET_URL'] .= '&imolAction=answer';
		}
		else
		{
			$this->arResult['TEMPLATE_WIDGET_URL'] .= (mb_substr($this->arResult['TEMPLATE_WIDGET_URL'], -1) != '?'? '?': '').'imolAction=answer';
		}

		$this->arResult['LANGUAGE_ID'] = $session['CONFIG_LANGUAGE_ID'] ?: null;
		$this->arResult['SESSION'] = $session;

		$this->includeComponentTemplate();

		return true;
	}
}