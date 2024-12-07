<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:intranet.structure.informer.new", $arCurrentValues);
$arParameters = Array(
		"PARAMETERS"=> Array(
			"LIST_URL"=> Array(
				"NAME" => GetMessage("GD_NEW_EMPLOYEES_P_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/company/events.php",
			),
		),
		"USER_PARAMETERS"=> Array(
			"NUM_USERS"=>$arComponentProps["PARAMETERS"]["NUM_USERS"],
		),
	);

$arParameters["USER_PARAMETERS"]["NUM_USERS"]["DEFAULT"] = 5;

$arDepartments = Array();

$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()->departmentRepository();
$departments = $departmentRepository->getAllTree(
	null,
	\Bitrix\Intranet\Enum\DepthLevel::FULL,
	\Bitrix\Intranet\Enum\DepartmentActiveFilter::ONLY_ACTIVE
);
$arDepartments["-"] = GetMessage("GD_ABSENT_P_ALL");

foreach ($departments as $department)
{
	$arDepartments[$department->getId()] = str_repeat('. ', $department->getDepth()).$department->getName();
}
$arParameters["USER_PARAMETERS"]["DEPARTMENT"] = Array(
	"NAME" => GetMessage("GD_NEW_EMPLOYEES_P_DEP"),
	"TYPE" => "LIST",
	"VALUES" => $arDepartments,
	"MULTIPLE" => "N",
	"DEFAULT" => "",
);

?>
