<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

foreach ($arResult['PROJECTS'] as &$arProject)
{
	$img = $this->__component->initGroupImage($arProject['PATHES']['IN_WORK'], $arProject['IMAGE_ID'], 42);
	$arProject['IMAGE'] = $img["FILE"];
}
unset($arProject);

usort(
	$arResult['PROJECTS'],
	function($a, $b){
		if ($a['COUNTERS']['IN_WORK'] < $b['COUNTERS']['IN_WORK'])
			return (1);
		elseif ($a['COUNTERS']['IN_WORK'] > $b['COUNTERS']['IN_WORK'])
			return (-1);
		else
			return strcmp($a['TITLE'], $b['TITLE']);
	}
);