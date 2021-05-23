<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if(is_array($arResult['PRESETS_TREE']) && !empty($arResult['PRESETS_TREE']))
{
	$arResult['PRESETS_TREE'] = $this->__component->flatterizePresetTree($arResult['PRESETS_TREE']);

	if(is_array($arResult['PRESETS_TREE']) && !empty($arResult['PRESETS_TREE']))
	{
		$arResult['CUSTOM_PRESETS'] = array();
		foreach($arResult['PRESETS_TREE'] as $i => $filter)
		{
			if(is_numeric($filter['FILTER']) && intval($filter['FILTER']) > 0)
			{
				unset($filter['Condition']);
				$arResult['CUSTOM_PRESETS'][] = array_change_key_case($filter, CASE_LOWER);
				unset($arResult['PRESETS_TREE'][$i]);
			}
		}
	}
}