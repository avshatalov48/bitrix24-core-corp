<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksQuickFormComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		parent::checkParameters();

		$arParams =& $this->arParams;

		static::tryParseStringParameter($arParams["NAME_TEMPLATE"], \CSite::GetNameFormat(false));
	}

	protected function getData()
	{
		parent::getData();

		$this->arResult["DESTINATION"] = \Bitrix\Tasks\Integration\SocialNetwork::getLogDestination('TASKS', array(
			'USE_PROJECTS' => 'Y'
		));
		$this->arResult["GROUP"] = \CSocNetGroup::getByID($this->arParams["GROUP_ID"]);

		$canAddMailUsers = (
			\Bitrix\Main\ModuleManager::isModuleInstalled("mail") &&
			\Bitrix\Main\ModuleManager::isModuleInstalled("intranet") &&
			(
				!\Bitrix\Main\Loader::includeModule("bitrix24")
				|| \CBitrix24::isEmailConfirmed()
			)
		);

		$this->arResult["CAN"] = array(
			"addMailUsers" => $canAddMailUsers,
			"manageTask" => \Bitrix\Tasks\Util\Restriction::canManageTask()
		);


		$user = \CUser::getByID($this->arParams["USER_ID"]);
		$this->arResult["USER"] = $user->fetch();
	}
}