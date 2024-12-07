<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$selectors = array();

foreach ($arParams['FILTER'] as $filterItem)
{
	if (!(isset($filterItem['type']) &&
		$filterItem['type'] === 'custom_entity' &&
		isset($filterItem['selector']) &&
		is_array($filterItem['selector']))
	)
	{
		continue;
	}

	$selector = $filterItem['selector'];
	$selectorType = isset($selector['TYPE']) ? $selector['TYPE'] : '';
	$selectorData = isset($selector['DATA']) && is_array($selector['DATA']) ? $selector['DATA'] : null;
	$selectorData['MODE'] = $selectorType;
	$selectorData['MULTI'] = $filterItem['params']['multiple'] && $filterItem['params']['multiple'] == 'Y';

	if (!empty($selectorData) && $selectorType == 'user')
	{
		$selectors[] = $selectorData;
	}
	if (!empty($selectorData) && $selectorType == 'group')
	{
		$selectors[] = $selectorData;
	}
}

if (!empty($selectors))
{
	\CUtil::initJSCore(
		array(
			'tasks_integration_socialnetwork'
		)
	);
}
?>

<?php if (!empty($selectors)):?>
	<script>
		<?php foreach ($selectors as $groupSelector):?>
		<?php
			$selectorID = $groupSelector['ID'];
			$selectorMode = $groupSelector['MODE'];
			$fieldID = $groupSelector['FIELD_ID'];
			$multi = $groupSelector['MULTI'];
		?>
		BX.ready(
			function()
			{
				BX.FilterEntitySelector.create(
					"<?= \CUtil::JSEscape($selectorID)?>",
					{
						fieldId: "<?= \CUtil::JSEscape($fieldID)?>",
						mode: "<?= \CUtil::JSEscape($selectorMode)?>",
						multi: <?= $multi ? 'true' : 'false'?>
					}
				);
			}
		);
		<? endforeach; ?>
	</script>
<? endif; ?>