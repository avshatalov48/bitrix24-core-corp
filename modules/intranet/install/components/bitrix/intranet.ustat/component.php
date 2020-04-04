<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


if (isset($arParams['BY']))
{
	if ($arParams['BY'] == 'user')
	{
		$componentPage = 'user';
	}
	elseif ($arParams['BY'] == 'department')
	{
		$componentPage = 'department';
	}
	elseif ($arParams['BY'] == 'rating')
	{
		$componentPage = 'rating';
	}
	elseif ($arParams['BY'] == 'tellabout')
	{
		$componentPage = 'tellabout';
	}
}

$this->IncludeComponentTemplate($componentPage);
