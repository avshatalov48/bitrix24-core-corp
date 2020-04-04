<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$bInTrash = false;
if (isset($arParams['OBJECT'])
	&& is_object($arParams['OBJECT'])
	&& isset($arParams["DOCUMENT_ID"][2])
	&& (intval($arParams["DOCUMENT_ID"][2]) > 0)
)
{
	$ob =& $arParams['OBJECT'];
	$elm = $ob->GetObject(array('element_id' => $arParams["DOCUMENT_ID"][2]));
	$bInTrash = $ob->InTrash($elm);
}

if ($bInTrash)
{
	if ( ! $GLOBALS['USER']->CanDoOperation('webdav_change_settings')) // if not superuser
	{
		foreach ($arResult["GRID_VERSIONS"] as $docID => &$oHist)
		{
			if (
				(sizeof($oHist['actions']) == 2) // safety ...
			)
			{
				$oHist['actions'] = array(); // disable all actions
			}
		}
	}
}
else
{
	foreach ($arResult["GRID_VERSIONS"] as $docID => &$oHist)
	{
		if (
			isset($oHist['data']['DOCUMENT']['PROPERTIES']['WEBDAV_SIZE']['VALUE'])
			&& (intval($oHist['data']['DOCUMENT']['PROPERTIES']['WEBDAV_SIZE']['VALUE']) <= 0)
			&& (sizeof($oHist['actions']) == 2) // safety ...
		)
		{
			$oHist['actions'][0] = $oHist['actions'][1];
			unset($oHist['actions'][1]); // restore prohibited if size=0
		}
	}
}
?>
