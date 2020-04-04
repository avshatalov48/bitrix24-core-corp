<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!Bitrix\Main\Loader::includeModule("disk"))
	return;

COption::SetOptionString('disk', 'disk_allow_autoconnect_shared_objects', 'N');

$dbDisk = Bitrix\Disk\Storage::getList(array("filter"=>array("ENTITY_ID" => "shared_files_".WIZARD_SITE_ID)));
if ($dbDisk->Fetch())
	return;

$driver = \Bitrix\Disk\Driver::getInstance();
$rightsManager = $driver->getRightsManager();
$taskIdEdit = $rightsManager->getTaskIdByName($rightsManager::TASK_EDIT);
$taskIdFull = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

$employeeCode = 'G'.WIZARD_EMPLOYEES_GROUP;
if (CModule::IncludeModule("iblock"))
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments"));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$dbUpdepartment = CIBlockSection::GetList(
			array(),
			array(
				"SECTION_ID" => 0,
				"IBLOCK_ID" => $arIBlock["ID"]
			)
		);
		if ($upDepartment = $dbUpdepartment->Fetch())
		{
			$employeeCode = "DR".$upDepartment['ID'];
		}
	}
}

//Common storage
$commonStorage = $driver->addCommonStorage(array(
		'NAME' => GetMessage("COMMON_DISK"),
		'ENTITY_ID' => "shared_files_".WIZARD_SITE_ID,
		'SITE_ID' => WIZARD_SITE_ID
	),
	array(
		array(
			'ACCESS_CODE' => $employeeCode, //Edit access for all employees
			'TASK_ID' => $taskIdEdit,
		)
	)
);
$commonStorage->changeBaseUrl(WIZARD_SITE_DIR."docs/");
?>