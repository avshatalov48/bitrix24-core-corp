<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class RecyclebinBaseComponent extends CBitrixComponent
{
	public static function doFinalActions()
	{
		CMain::FinalActions();
		die();
	}

	public function executeComponent()
	{
		$this->doPreActions();
		$this->getData();
		$this->doPostActions();

		$this->display();
	}

	protected function doPreActions()
	{
	}

	protected function getData()
	{
	}

	protected function doPostActions()
	{
	}

	protected function display()
	{
		$this->includeComponentTemplate();
	}
}