<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:intranet.structure.birthday.nearest", $arCurrentValues);

$arParameters = Array(
		"PARAMETERS"=> Array(
			"STRUCTURE_PAGE"=>$arComponentProps["PARAMETERS"]["STRUCTURE_PAGE"],
			"PM_URL"=>$arComponentProps["PARAMETERS"]["PM_URL"],
			"SHOW_YEAR"=>$arComponentProps["PARAMETERS"]["SHOW_YEAR"],
			"USER_PROPERTY"=>$arComponentProps["PARAMETERS"]["USER_PROPERTY"],
			"LIST_URL"=> Array(
				"NAME" => GetMessage("GD_BIRTHDAY_P_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/company/birthdays.php",
			),
		),
		"USER_PARAMETERS"=> Array(
			"NUM_USERS"=>$arComponentProps["PARAMETERS"]["NUM_USERS"],
		),
	);

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
	"NAME" => GetMessage("GD_BIRTHDAY_P_DEP"),
	"TYPE" => "LIST",
	"VALUES" => $arDepartments,
	"MULTIPLE" => "N",
	"DEFAULT" => "",
);


$arParameters["USER_PARAMETERS"]["NUM_USERS"]["DEFAULT"] = 5;
?>
