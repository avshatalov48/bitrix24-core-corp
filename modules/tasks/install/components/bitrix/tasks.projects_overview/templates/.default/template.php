<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be
?>

<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	array(
		'USER_ID' => $arParams[ 'USER_ID' ],
		'SECTION_URL_PREFIX' => '',

		'MARK_SECTION_PROJECTS' => 'Y',
		'USE_AJAX_ROLE_FILTER' => 'N',

		'PATH_TO_GROUP_TASKS' => $arParams[ 'PATH_TO_GROUP_TASKS' ],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams[ 'PATH_TO_GROUP_TASKS_TASK' ],
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams[ 'PATH_TO_GROUP_TASKS_VIEW' ],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams[ 'PATH_TO_GROUP_TASKS_REPORT' ],

		'PATH_TO_USER_TASKS' => $arParams[ 'PATH_TO_USER_TASKS' ],
		'PATH_TO_USER_TASKS_TASK' => $arParams[ 'PATH_TO_USER_TASKS_TASK' ],
		'PATH_TO_USER_TASKS_VIEW' => $arParams[ 'PATH_TO_USER_TASKS_VIEW' ],
		'PATH_TO_USER_TASKS_REPORT' => $arParams[ 'PATH_TO_USER_TASKS_REPORT' ],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams[ 'PATH_TO_USER_TASKS_TEMPLATES' ],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams[ 'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' ],

		'PATH_TO_CONPANY_DEPARTMENT' => $arParams[ 'PATH_TO_CONPANY_DEPARTMENT' ]
	),
	$component,
	array('HIDE_ICONS' => true)
);?>

<?php
	$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

	if ($isBitrix24Template)
	{
		$this->SetViewTarget('inside_pagetitle');
	}
?>
	<div class="pagetitle-container pagetitle-flexible-space">
		<?

		$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
		$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').' pagetitle-toolbar-field-view ');
		?>
		<? $APPLICATION->IncludeComponent(
			"bitrix:main.ui.filter",
			"",
			array(
				"FILTER_ID"             => $arParams["GRID_ID"],
				"GRID_ID"               => $arParams["GRID_ID"],

				"FILTER"                => $arParams["FILTERS"],
				"FILTER_PRESETS"        => $arParams["PRESETS"],
				"ENABLE_LABEL"          => true,
				'ENABLE_LIVE_SEARCH'    => true,
				'RESET_TO_DEFAULT_MODE' => true,
			),
			$component,
			array("HIDE_ICONS" => true)
		); ?>


	</div>

	<div class="pagetitle-container pagetitle-align-right-container tasks-project-filter-btn-add">
		<a class="ui-btn ui-btn-primary ui-btn-icon-add" href="<?=$arParams['PATH_TO_GROUP_ADD']?>">
			<?=GetMessage('TASKS_PROJECT_OVERVIEW_ADD_PROJECT')?>
		</a>

	</div>

<?php
	if ($isBitrix24Template)
	{
		$this->EndViewTarget();
	}
?>


<?$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks">

		<?$helper->displayWarnings();?>

		<?php
		$APPLICATION->IncludeComponent(
			'bitrix:main.ui.grid',
			'',
			array(
				'GRID_ID'   => $arParams['GRID_ID'],
				'HEADERS'   => isset($arParams['HEADERS']) ? $arParams['HEADERS'] : array(),
				//				'SORT'      => isset($arParams['SORT']) ? $arParams['SORT'] : array(),
				//				'SORT_VARS' => isset($arParams['SORT_VARS']) ? $arParams['SORT_VARS'] : array(),
				'ROWS'      => $arResult['ROWS'],

				'AJAX_MODE'           => 'Y',
				//Strongly required
				"AJAX_OPTION_JUMP"    => "N",
				"AJAX_OPTION_STYLE"   => "Y",
				"AJAX_OPTION_HISTORY" => "N",

				"ALLOW_COLUMNS_SORT"      => true,
				//				"ALLOW_ROWS_SORT"         => false,
				"ALLOW_COLUMNS_RESIZE"    => true,
				"ALLOW_HORIZONTAL_SCROLL" => true,
				//				"ALLOW_SORT"              => false,
				"ALLOW_PIN_HEADER"        => true,
				"ACTION_PANEL"            => array(),

				"SHOW_CHECK_ALL_CHECKBOXES" => false,
				"SHOW_ROW_CHECKBOXES"       => false,
				"SHOW_ROW_ACTIONS_MENU"     => false,
				"SHOW_GRID_SETTINGS_MENU"   => true,
				"SHOW_NAVIGATION_PANEL"     => true,
				"SHOW_PAGINATION"           => true,
				"SHOW_SELECTED_COUNTER"     => false,
				"SHOW_TOTAL_COUNTER"        => true,
				"SHOW_PAGESIZE"             => true,
				"SHOW_ACTION_PANEL"         => false,

				"SHOW_MORE_BUTTON" => false,

				"NAV_OBJECT"=>$arResult['NAV'],
				"NAV_PARAMS"=>array(
					"SEF_MODE" => "N"
				),

				"TOTAL_ROWS_COUNT"  => $arResult['NAV']->getRecordCount(),
				"DEFAULT_PAGE_SIZE" => 10
			),
			$component,
			array('HIDE_ICONS' => 'Y')
		);
		?>
	</div>


	<?$helper->initializeExtension();?>

<?endif?>


