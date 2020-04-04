<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if($helper->checkHasFatals())
{
	return;
}

$stateStructure = $helper->makeStructure();
$state = $helper->getStateInstance($stateStructure)->get();

$layout = array(
	'FLAGS' => array(
		'FORM_FOOTER_PIN' => $state['FLAGS']['FORM_FOOTER_PIN'],
	)
);
$categoryNames = array('HEAD_BOTTOM', 'STATIC', 'DYNAMIC');
foreach($categoryNames as $name)
{
	if(!is_array($arParams['BLOCKS'][$name]))
	{
		$arParams['BLOCKS'][$name] = array();
		continue;
	}

	foreach($arParams['BLOCKS'][$name] as $block)
	{
		$pinned = $state['BLOCKS'][$block['CODE']]['PINNED'];

		$layout['BLOCKS'][$block['CODE']] = array(
			'CODE' => $block['CODE'],
			'PINNED' => $pinned,
			'OPENED' => $block['FILLED'] || $pinned,
			'CATEGORY' => $name,
		);

		if(is_array($block['SUB']))
		{
			foreach($block['SUB'] as $subBlock)
			{
				$subPinned = $state['BLOCKS'][$subBlock['CODE']]['PINNED'];

				$layout['BLOCKS'][$subBlock['CODE']] = array(
					'CODE' => $subBlock['CODE'],
					'PINNED' => $subPinned,
					'OPENED' => $subBlock['FILLED'] || $subPinned,
					'CATEGORY' => $name,
				);
			}
		}
	}
}

$additionalDynamicDisplayed = false;
$allDynamicOpened = true;
foreach($arParams['BLOCKS']['DYNAMIC'] as $block)
{
	$blockState = $layout['BLOCKS'][$block['CODE']];
	if(!$blockState['OPENED'])
	{
		$additionalDynamicDisplayed = true;
		$allDynamicOpened = false;
	}
}

$arResult['TEMPLATE_DATA'] = array(
	'ADDITIONAL_DYNAMIC_DISPLAYED' => $additionalDynamicDisplayed,
	'ALL_DYNAMIC_OPENED' => $allDynamicOpened
);
$arResult['JS_DATA'] = array(
	'state' => $layout,
	'structure' => $helper->signStructure($stateStructure),
);