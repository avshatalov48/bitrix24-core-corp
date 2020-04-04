<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$arValues = isset($arResult['VALUES']) ? $arResult['VALUES'] : array();
if (count($arValues) === 0):
	return;
endif;

$crmFieldMultiViewTemplates = array(
	'_LINK_' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-#FIELD_TYPE#">
		<a class="crm-fld-text" href="#VIEW_VALUE#" target="_blank" >#VALUE#</a>
		<span class="crm-fld-value">
			<input class="crm-fld-element-input" type="text" value="#VALUE#" />
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-#FIELD_TYPE#"></span>
</span>',
	'_INPUT_' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-input">
		<span class="crm-fld-text">#VALUE#</span>
		<span class="crm-fld-value">
			<input class="crm-fld-element-input" type="text" value="#VALUE#" />
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-input"></span>
</span>'
);

$typeID = strtoupper($arResult['TYPE_ID']);
$readOnly = isset($arResult['READ_ONLY']) ? $arResult['READ_ONLY'] : false;

?>
<table cellspacing="0" cellpadding="0" border="0" class="bx-crm-view-fieldset-content-table">
<?foreach($arValues as $ID => &$arValue):
	$valueType = $arValue['VALUE_TYPE'];
	$value = $arValue['VALUE'];
	$fieldID = "FM.{$typeID}.{$valueType}.{$ID}";?>
	<tr>
		<td class="bx-field-name bx-padding"><?=htmlspecialcharsbx(CCrmFieldMulti::GetEntityName($typeID, strtoupper($valueType), true))?>:</td>
		<td class="bx-field-value">
			<?if($readOnly):
				echo '<div class="crm-fld-block-readonly">', CCrmFieldMulti::GetTemplate($typeID, $valueType, $value), '</div>';
			else:
				$templateType = '_INPUT_';
				$editorFieldType = strtolower($typeID);

				if($typeID === 'PHONE' || $typeID === 'EMAIL' || $typeID === 'WEB'):
					$templateType = '_LINK_';

					if($typeID === 'WEB' && $valueType !== 'WORK' && $valueType !== 'HOME' && $valueType !== 'OTHER'):
						$editorFieldType .= '-'.strtolower($valueType);
					endif;
				elseif($typeID === 'IM'):
					$templateType = $valueType === 'SKYPE' || $valueType === 'ICQ' || $valueType === 'MSN' ? '_LINK_' : '_INPUT_';
					$editorFieldType .= '-'.strtolower($valueType);
				endif;

				$template = isset($crmFieldMultiViewTemplates[$templateType]) ? $crmFieldMultiViewTemplates[$templateType] : '';

				if($template === ''):
					echo CCrmFieldMulti::GetTemplate($typeID, $valueType, $value);
				else:
					$viewValue = $value;
					if($typeID === 'PHONE'):
						$viewValue = CCrmCallToUrl::Format($value);
					elseif($typeID === 'EMAIL'):
						$viewValue = "mailto:{$value}";
					elseif($typeID === 'WEB'):
						if($valueType === 'OTHER' || $valueType === 'WORK' || $valueType === 'HOME'):
							$hasProto = preg_match('/^http(?:s)?:\/\/(.+)/', $value, $urlMatches) > 0;
							if($hasProto):
								$value = $urlMatches[1];
							else:
								$viewValue = "http://{$value}";
							endif;
						elseif($valueType === 'FACEBOOK'):
							$viewValue = "http://www.facebook.com/{$value}/";
						elseif($valueType === 'TWITTER'):
							$viewValue = "http://twitter.com/{$value}/";
						elseif($valueType === 'LIVEJOURNAL'):
							$viewValue = "http://{$value}.livejournal.com/";
						endif;
					elseif($typeID === 'IM'):
						if($valueType === 'SKYPE'):
							$viewValue = "skype:{$value}?chat";
						elseif($valueType === 'ICQ'):
							$viewValue = "http://www.icq.com/people/{$value}/";
						elseif($valueType === 'MSN'):
							$viewValue = "msn:{$value}";
						endif;
					endif;

					echo str_replace(
						array('#NAME#', '#FIELD_TYPE#', '#VALUE#', '#VIEW_VALUE#'),
						array($fieldID, htmlspecialcharsbx($editorFieldType), htmlspecialcharsbx($value), htmlspecialcharsbx($viewValue)),
						$template
					);
				endif;
			endif;?>
		</td>
	</tr>
<?endforeach;
unset($arValue)?>
</table>