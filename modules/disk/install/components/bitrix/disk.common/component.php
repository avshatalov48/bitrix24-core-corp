<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('disk'))
{
	return;
}
global $USER;
if(!isset($USER) || !$USER->isAuthorized() || !$USER->getId())
{
	return;
}

$arDefaultUrlTemplates404 = array(
	"trashcan_list" => "trashcan/#TRASH_PATH#",
	"trashcan_file_view" => "trash/file/#TRASH_FILE_PATH#",

	"folder_list" => "path/#PATH#",

	"file_view" => "file/#FILE_PATH#",
	"file_history" => "file-history/#FILE_ID#",

	"external_link_list" => "external",
	"disk_help" => "help",

	"disk_bizproc_workflow_admin" => "bp/",
	"disk_bizproc_workflow_edit" => "bp_edit/#ID#/",
	"disk_start_bizproc" => "bp_start/#ELEMENT_ID#/",
	"disk_task" => "bp_task/#ID#/",
	"disk_task_list" => "bp_task_list/",
);

if (\Bitrix\Disk\User::isCurrentUserAdmin())
{
	$arDefaultUrlTemplates404["disk_volume"] = "volume/#ACTION#";
}

$arDefaultVariableAliases404 = array(

);
$arDefaultVariableAliases = array();
$componentPage = '';
$arComponentVariables = array('FOLDER_ID', 'FILE_ID', 'PATH');

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
if(empty($arParams['STORAGE']))
{
	if(empty($arParams['STORAGE_ID']))
	{
		return;
	}
	$arParams['STORAGE'] = \Bitrix\Disk\Storage::loadById((int)$arParams['STORAGE_ID'], array('ROOT_OBJECT'));
}
if(empty($arParams['STORAGE']))
{
	//necessary
	return;
}

if ($arParams['SEF_MODE'] == 'Y')
{
	$arVariables = array();

	$engine = new CComponentEngine($this);
	$engine->addGreedyPart("#PATH#");
	$engine->addGreedyPart("#FILE_PATH#");
	$engine->addGreedyPart("#TRASH_PATH#");
	$engine->addGreedyPart("#TRASH_FILE_PATH#");
	$engine->addGreedyPart("#ACTION#");
	$engine->setResolveCallback(array(\Bitrix\Disk\Driver::getInstance()->getUrlManager(), "resolvePathComponentEngine"));

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, isset($arParams["SEF_URL_TEMPLATES"])? $arParams["SEF_URL_TEMPLATES"] : array());
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, isset($arParams["VARIABLE_ALIASES"])? $arParams["VARIABLE_ALIASES"] : array());

	$componentPage = $engine->guessComponentPath(
		$arParams["SEF_FOLDER"],
		$arUrlTemplates,
		$arVariables
	);

	if($componentPage === '')
	{
		$componentPage = 'error_page';
	}
	elseif (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
	{
		/** @var \Bitrix\Disk\Storage $arParams['STORAGE'] */
		$componentPage = 'folder_list';
		if($arParams['STORAGE'] instanceof \Bitrix\Disk\Storage)
		{
			$arVariables = array(
				'STORAGE' => $arParams['STORAGE'],
				'FOLDER_ID' => $arParams['STORAGE']->getRootObjectId(),
				'RELATIVE_PATH' => '/',
				'RELATIVE_ITEMS' => array(),
			);
		}
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	$arParams['SEF_FOLDER'] = rtrim($arParams['SEF_FOLDER'], '/') . '/';
	foreach ($arUrlTemplates as $url => $value)
	{
		if(empty($arParams['PATH_TO_'.mb_strtoupper($url)]))
		{
			$arResult['PATH_TO_'.mb_strtoupper($url)] = $arParams['SEF_FOLDER'] . $value;
		}
		elseif(is_string($arParams['PATH_TO_'.mb_strtoupper($url)]))
		{
			$arResult['PATH_TO_'.mb_strtoupper($url)] = $arParams['PATH_TO_'.mb_strtoupper($url)];
		}
	}
}
else
{
	throw new Exception('Only SEF!');
}

$arResult = array_merge(
	array(
		'STORAGE' => $arParams['STORAGE'],
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] == 'Y'? array(): $arVariableAliases,
		'ELEMENT_ID' => isset($arParams['ELEMENT_ID'])? $arParams['ELEMENT_ID'] : null,
	),
	$arResult
);

$this->IncludeComponentTemplate($componentPage);
?>