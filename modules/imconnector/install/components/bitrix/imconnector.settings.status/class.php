<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Page\Asset,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Engine\Contract\Controllerable;
use \Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Connector,
	\Bitrix\ImConnector\CustomConnectors;

class ImConnectorSettingsStatus extends CBitrixComponent implements Controllerable
{
	private $error = array();
	private $messages = array();

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules()
	{
		if (Loader::includeModule('imconnector'))
		{
			return true;
		}
		else
		{
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_STATUS_CONFIG_MODULE_NOT_INSTALLED_MSGVER_1'));
			return false;
		}
	}

	public function constructionForm()
	{
		$listActiveConnector = Connector::getListActiveConnectorReal();

		Connector::initIconCss();
		Connector::initIconDisabledCss();

		foreach ($listActiveConnector as $id => $value)
		{
			$this->arResult[$id] = array(
				'ID' => $id,
				'NAME' => $value,
				'STATUS' => Status::getInstance($id, (int)$this->arParams['LINE'])->isStatus()
			);
		}
	}

	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if($this->checkModules())
		{
			$this->constructionForm();

			$this->includeComponentTemplate();
		}
	}

	protected function listKeysSignedParameters()
	{
		//We list the names of the parameters to be used in Ajax actions
		return array(
			'LINE',
			'LINK_ON'
		);
	}

	public function configureActions()
	{
		return array();
	}

	public function reloadAction()
	{
		ob_start();
		$this->executeComponent();
		$html = ob_get_clean();
		return array(
			'html' => $html
		);
	}
};