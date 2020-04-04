<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $meeting_folder;

// $folder = $_REQUEST["install_public_path"];
// $folder = str_replace('\\', '/', $folder);


$meeting_folder = '/services/meeting/';

$io = CBXVirtualIo::GetInstance();

if (substr($meeting_folder, -4) != '.php')
{
	$meeting_folder .= (substr($meeting_folder, -1) == '/' ? '' : '/').'index.php';
}

$fileName = $io->ExtractNameFromPath($meeting_folder);
$meeting_folder = $io->ExtractPathFromPath($meeting_folder);
$menuFolder = $io->ExtractPathFromPath($meeting_folder);

$absPath = $io->RelativeToAbsolutePath($meeting_folder);
$absMenuPath = $io->RelativeToAbsolutePath($menuFolder);
$absFile = $absPath.'/'.$fileName;

$arReplace = array(
	'#TITLE#' => GetMessage('MEETING_MODULE_NAME'),
	'#PATH#' => $meeting_folder.'/',
	'#RESERVE_MEETING_IBLOCK_ID#' => 0,
	'#RESERVE_VMEETING_IBLOCK_ID#' => 0
);

$site = CSite::GetSiteByFullPath($absPath);

if (CModule::IncludeModule('iblock'))
{
	$arXMLID = array(
		"meeting_rooms_".$site => '#RESERVE_MEETING_IBLOCK_ID#',
		"video-meeting_".$site => '#RESERVE_VMEETING_IBLOCK_ID#',
	);

	$rsIBlock = CIBlock::GetList(
		array(), array("XML_ID" => array_keys($arXMLID), "TYPE" => "events")
	);

	while ($arIBlock = $rsIBlock->Fetch())
	{
		$arReplace[$arXMLID[$arIBlock['XML_ID']]] = $arIBlock['ID'];
	}
}


if ($io->CreateDirectory($absPath))
{
	$c = str_replace(
		array_keys($arReplace),
		array_values($arReplace),
		file_get_contents(dirname(__FILE__)."/public/index.php")
	);

	if ($f = $io->GetFile($absFile))
	{
		$f->PutContents($c);
		CUrlRewriter::Add(array(
			'CONDITION' => '#^'.$meeting_folder.'/#',
			'RULE' => '',
			'ID' => 'bitrix:meetings',
			'PATH' => $meeting_folder.'/'.$fileName,
		));
	}

	if (CModule::IncludeModule('fileman'))
	{
		$absMenuFile = $absMenuPath.'/.left.menu.php';
		$menuFile = $menuFolder.'/.left.menu.php';

		$arResult = CFileMan::GetMenuArray($absMenuFile);
		$arMenuItems = $arResult["aMenuLinks"];
		$menuTemplate = $arResult["sMenuTemplate"];

		$menuItemPosition = 1;
		foreach ($arMenuItems as $item)
		{
			if ($item[0] == GetMessage('MEETING_MODULE_NAME') || $item[1] == $meeting_folder.'/')
			{
				$menuItemPosition = -1;
				break;
			}
		}

		if ($menuItemPosition > 0)
		{
			if ($menuItemPosition > count($arMenuItems))
				$menuItemPosition = 0;

			for ($i = count($arMenuItems) - 1; $i >= $menuItemPosition; $i--)
				$arMenuItems[$i+1] = $arMenuItems[$i];

			$arMenuItems[$menuItemPosition] = Array(
				GetMessage('MEETING_MODULE_NAME'), 
				$meeting_folder.'/', 
				Array(), 
				Array(), 
				"CBXFeatures::IsFeatureEnabled('Meeting')" 
			);

			CFileMan::SaveMenu(Array($site, $menuFile), $arMenuItems, $menuTemplate);
		}
	}
}
?>