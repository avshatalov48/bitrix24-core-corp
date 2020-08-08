<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Crm\UserField\Types\StatusType;

/**
 * @var StatusUfComponent $component
 * @var array $arResult
 */
$component = $this->getComponent();
?>

<span class="fields crm_status field-wrap" data-has-input="no">
	<input
		type="hidden"
		value=""
		id="<?= $arResult['userField']['FIELD_NAME'] ?>_default"
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

	<?php
	$script = <<<EOT
		<script>
		function changeHandler_{$arResult['fieldNameJs']}(controlObject, value)
		{
			if(controlObject.params.fieldName === '{$arResult['fieldNameJs']}' && !!BX('{$arResult['valueContainerIdJs']}'))
			{
				var currentValue = JSON.parse(controlObject.node.getAttribute('data-value'));

				var s = '';
				if(!BX.type.isArray(currentValue))
				{
					if(currentValue === null)
					{
						currentValue = [{VALUE:''}];
					}
					else
					{
						currentValue = [currentValue];
					}
				}

				if(currentValue.length > 0)
				{
					for(var i = 0; i < currentValue.length; i++)
					{
						s += '<input type="hidden" name="{$arResult['htmlFieldNameJs']}" value="'+BX.util.htmlspecialchars(currentValue[i].VALUE)+'" />';
					}
				}
				else
				{
					s += '<input type="hidden" name="{$arResult['htmlFieldNameJs']}" value="" />';
				}

				BX('{$arResult['valueContainerIdJs']}').innerHTML = s;
				BX.fireEvent(BX('{$arResult['fieldNameJs']}_default'), 'change');
			}
		}

		BX.ready(function(){

			var params = {$arResult['params']};

			BX('{$arResult['controlNodeIdJs']}').appendChild(BX.decl({
				block: '{$arResult['block']}',
				name: '{$arResult['fieldNameJs']}',
				items: {$arResult['items']},
				value: {$arResult['currentValue']},
				params: params,
				valueDelete: false
			}));
			
			BX.addCustomEvent(
				window,
				'UI::Select::change',
				changeHandler_{$arResult['fieldNameJs']}
			);

			BX.bind(BX('{$arResult['controlNodeIdJs']}'), 'click', BX.defer(function(){
				changeHandler_{$arResult['fieldNameJs']}(
					{
						params: params,
						node: BX('{$arResult['controlNodeIdJs']}').firstChild
					});
			}));
		});
	</script>
EOT;
	print $script;

	?>
</span>