<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$result = [
	"settings"=> [
		"nameFormat"=>CSite::GetNameFormat(),
		"profilePath"=>\Bitrix\MobileApp\Janative\Manager::getComponentPath("user.profile")
	]
];

return $result;
