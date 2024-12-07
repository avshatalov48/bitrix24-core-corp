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
$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()->departmentRepository();
$rootDepartment = $departmentRepository->getRootDepartment();
if ($rootDepartment)
{
	$employeeCode = "DR".$rootDepartment->getId();
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

if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	\Bitrix\Main\Config\Option::set('disk', 'documents_enabled', 'Y');
	\Bitrix\Main\Config\Option::set('disk', 'default_viewer_service', 'onlyoffice');
	\Bitrix\Main\Config\Option::set('disk', 'disk_onlyoffice_installation_date', time());
	\Bitrix\Main\Config\Option::set('disk', 'reset_user_edit_service_to_onlyoffice', 'Y');
}
?>