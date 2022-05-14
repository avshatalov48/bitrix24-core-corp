<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$panelItems = [];
$idPrefix = 'crm_control_panel_';
$crmPanelContainer = $idPrefix . 'container';
$menuContainerId = $idPrefix . 'menu';
$searchContainerId = $idPrefix . 'search';
$searchInputId = $searchContainerId . '_input';

$arResult['CRM_PANEL_CONTAINER_ID'] = $crmPanelContainer;
$arResult['CRM_PANEL_MENU_CONTAINER_ID'] = $menuContainerId;
$arResult['CRM_PANEL_SEARCH_CONTAINER_ID'] = $searchContainerId;
$arResult['CRM_PANEL_SEARCH_INPUT_ID'] = $searchInputId;