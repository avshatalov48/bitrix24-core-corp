<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Crm\UserField\Types\StatusType;

/**
 * @var StatusUfComponent $component
 * @var array $arResult
 */
$component = $this->getComponent();

$postfix = $this->randString();
if ($component->isAjaxRequest())
{
	$postfix .= time();
}
$arResult['valueContainerId'] .= $postfix;
$arResult['spanAttrList']['id'] = $arResult['valueContainerId'];
$arResult['controlNodeId'] .= $postfix;
$defaultFieldName = $arResult['fieldName'].'_default_'.$postfix;
?>

<span class="fields crm_status field-wrap" data-has-input="no">
	<input
		type="hidden"
		value=""
		id="<?= $defaultFieldName ?>"
	>
	<span <?= $component->getHtmlBuilder()->buildTagAttributes($arResult['spanAttrList']) ?>>
		<?php
		if (count($arResult['attrList']))
		{
			foreach ($arResult['attrList'] as $attrList)
			{
				?>
				<input <?= $component->getHtmlBuilder()->buildTagAttributes($attrList) ?>>
				<?php
			}
		}
		?>
	</span>
	<span id="<?= $arResult['controlNodeId'] ?>"></span>
</span>

<script>
	BX.ready(function ()
	{
		new BX.Desktop.Field.Enum(
			<?=CUtil::PhpToJSObject([
				'defaultFieldName' => $defaultFieldName,
				'fieldName' => $arResult['fieldNameJs'],
				'container' => $arResult['controlNodeId'],
				'valueContainerId' => $arResult['valueContainerId'],
				'block' => $arResult['block'],
				'value' => $arResult['currentValue'],
				'items' => $arResult['items'],
				'params' => $arResult['params']
			])?>
		);
	});
</script>