<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/.superleft.menu_ext.php");

if (CModule::IncludeModule("imopenlines"))
{
	if (\Bitrix\ImOpenlines\Security\Helper::isStatisticsMenuEnabled())
	{
		$aMenuLinks[] = array(
			GetMessage("MENU_IMOL_STATISTICS"),
			"/openlines/",
			array(),
			array("menu_item_id" => "menu_openlines_statistics"),
			""
		);
	}

	if (\Bitrix\ImOpenlines\Security\Helper::isLinesMenuEnabled())
	{
		$aMenuLinks[] = array(
			GetMessage("MENU_IMOL_LIST_LINES"),
			"/openlines/list/",
			array(),
			array("menu_item_id" => "menu_openlines_lines"),
			""
		);
	}

	if (CModule::IncludeModule("imconnector"))
	{
		$listActiveConnector = \Bitrix\ImConnector\Connector::getListConnectorMenu(true);
		foreach ($listActiveConnector as $idConnector => $fullName)
		{
			$aMenuLinks[] = array(
				empty($listActiveConnector[$idConnector]['short_name'])? $listActiveConnector[$idConnector]['name']:$listActiveConnector[$idConnector]['short_name'],
				"/openlines/connector/?ID=" . $idConnector,
				array(),
				array(
					"title" => $listActiveConnector[$idConnector]['name'],
					"menu_item_id" => "menu_openlines_connector_" . str_replace('.', '_', $idConnector)),
				""
			);
		}
	}

	/**	List */

	if (\Bitrix\ImOpenlines\Security\Helper::isCrmWidgetEnabled())
	{
		$aMenuLinks[] = array(
			GetMessage("MENU_IMOL_BUTTON"),
			"/openlines/button.php",
			array(),
			array("menu_item_id" => "menu_openlines_button"),
			""
		);
	}

	if (\Bitrix\ImOpenlines\Security\Helper::isStatisticsMenuEnabled())
	{
		$aMenuLinks[] = array(
			GetMessage("MENU_IMOL_DETAILED_STATISTICS"),
			"/openlines/statistics.php",
			array(),
			array("menu_item_id" => "menu_openlines_detail_statistics"),
			""
		);
	}

	if (\Bitrix\ImOpenlines\Security\Helper::isSettingsMenuEnabled())
	{
		$aMenuLinks[] = array(
			GetMessage("MENU_IMOL_PERMISSIONS"),
			"/openlines/permissions.php",
			array("/openlines/editrole.php"),
			array("menu_item_id" => "menu_openlines_permissions"),
			""
		);
	}
}