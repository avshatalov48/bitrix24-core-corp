<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

/**
 * @var ElementCrmUfComponent $component
 * @var array $arResult
 * @var array $arParams
 */

$component = $this->getComponent();

$fieldName = $arParams['userField']['FIELD_NAME'];
$formName = (isset($arParams['form_name']) ? (string)$arParams['form_name'] : '');
$fieldUID = mb_strtolower(str_replace('_', '-', $fieldName));
if($formName !== '')
{
	$fieldUID = mb_strtolower(str_replace('_', '-', $formName)).'-' . $fieldUID;
}
$fieldUID = CUtil::JSescape($fieldUID);

$randString = $this->randString();
$jsObject = 'CrmEntitySelector_' . $randString;

if($arResult['PERMISSION_DENIED'])
{
	?>
	<div id="crm-<?= $fieldUID ?>-box">
		<div class="crm-element-button-open">
			<?= Loc::getMessage('CRM_SFE_ENTITY_NOT_SELECTED') ?>
		</div>
		<?php
		foreach($arResult['value'] as $value)
		{
			$name = HtmlFilter::encode($fieldName . ($arResult['MULTIPLE'] === 'Y' ? '[]' : ''));
			$value = HtmlFilter::encode($value);
			?>
			<input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
			<?php
		}
		?>
	</div>
	<?php
}
else
{
	?>
	<div id="crm-<?= $fieldUID ?>-box">
		<span id="crm-<?= $fieldUID ?>-open">
			<?php

			$selectorOptions = [
				'lazyLoad' => 'Y',
				'context' => (!empty($arParams['CONTEXT']) ? $arParams['CONTEXT'] : 'crmEntityCreate'),
				'contextCode' => '',
				'enableSonetgroups' => 'N',
				'enableUsers' => 'N',
				'useClientDatabase' => 'N',
				'enableAll' => 'N',
				'enableDepartments' => 'N',
				'enableCrm' => 'Y',
				'crmPrefixType' => 'SHORT'
			];

			$entityTypesSelectorOptions = ElementType::getEnableEntityTypesForSelectorOptions(
				$arParams['ENTITY_TYPE'],
				$arResult['DYNAMIC_TYPE_TITLES'] ?? null
			);
			$selectorOptions = array_merge($selectorOptions, $entityTypesSelectorOptions);

			$APPLICATION->IncludeComponent(
				'bitrix:main.user.selector',
				'',
				[
					'ID' => $fieldUID,
					'LIST' => (!empty($arResult['SELECTED_LIST']) ? $arResult['SELECTED_LIST'] : []),
					'LAZYLOAD' => 'Y',
					'INPUT_NAME' => $fieldName . ($arResult['MULTIPLE'] === 'Y' ? '[]' : ''),
					'USE_SYMBOLIC_ID' => $arResult['USE_SYMBOLIC_ID'],
					'CONVERT_TO_SYMBOLIC_ID' => (!$arResult['USE_SYMBOLIC_ID'] ? 'N' : false),
					'API_VERSION' => 3,
					'SELECTOR_OPTIONS' => $selectorOptions
				]
			);
			?>
		</span>

		<?php
		if(!empty($arParams['createNewEntity']))
		{
			?>
			<script>
				BX.ready(function ()
				{
					BX['<?=$jsObject?>'] = new BX.CrmEntitySelector({
						randomString: '<?=$randString?>',
						jsObject: '<?=$jsObject?>',
						fieldUid: '<?=$fieldUID?>',
						fieldName: '<?=$fieldName?>',
						usePrefix: '<?=$arResult['PREFIX']?>',
						multiple: '<?=$arResult['MULTIPLE']?>',
						context: '<?=!empty($arParams['CONTEXT']) ? $arParams['CONTEXT'] : 'crmEntityCreate'?>',
						listPrefix: <?=\Bitrix\Main\Web\Json::encode($arResult['LIST_PREFIXES'])?>,
						selectorEntityTypes: <?=\Bitrix\Main\Web\Json::encode($arResult['SELECTOR_ENTITY_TYPES'])?>,
						listElement: <?=\Bitrix\Main\Web\Json::encode($arResult['ELEMENT'])?>,
						listEntityType: <?=\Bitrix\Main\Web\Json::encode($arResult['ENTITY_TYPE'])?>,
						listEntityCreateUrl: <?=\Bitrix\Main\Web\Json::encode($arResult['LIST_ENTITY_CREATE_URL'])?>,
						pluralCreation: '<?=!empty($arResult['PLURAL_CREATION']) ? 'true' : '' ?>',
						currentEntityType: '<?=!empty($arResult['CURRENT_ENTITY_TYPE']) ? $arResult['CURRENT_ENTITY_TYPE'] : null?>'
					});

					BX.message({
						CRM_FF_LEAD: '<?=GetMessageJS('CRM_FF_LEAD')?>',
						CRM_FF_CONTACT: '<?=GetMessageJS('CRM_FF_CONTACT')?>',
						CRM_FF_COMPANY: '<?=GetMessageJS('CRM_FF_COMPANY')?>',
						CRM_FF_DEAL: '<?=GetMessageJS('CRM_FF_DEAL')?>',
						CRM_FF_ORDER: '<?=GetMessageJS('CRM_FF_ORDER')?>',
						CRM_FF_QUOTE: '<?=GetMessageJS('CRM_FF_QUOTE_MSGVER_1')?>',
						CRM_FF_OK: '<?=GetMessageJS('CRM_FF_OK')?>',
						CRM_FF_CANCEL: '<?=GetMessageJS('CRM_FF_CANCEL')?>',
						CRM_FF_CLOSE: '<?=GetMessageJS('CRM_FF_CLOSE')?>',
						CRM_FF_SEARCH: '<?=GetMessageJS('CRM_FF_SEARCH')?>',
						CRM_FF_NO_RESULT: '<?=GetMessageJS('CRM_FF_NO_RESULT')?>',
						CRM_FF_CHOISE: '<?=GetMessageJS('CRM_FF_CHOISE')?>',
						CRM_FF_CHANGE: '<?=GetMessageJS('CRM_FF_CHANGE')?>',
						CRM_FF_LAST: '<?=GetMessageJS('CRM_FF_LAST')?>',
						CRM_CES_CREATE_LEAD: '<?=GetMessageJS('CRM_CES_CREATE_LEAD')?>',
						CRM_CES_CREATE_CONTACT: '<?=GetMessageJS('CRM_CES_CREATE_CONTACT')?>',
						CRM_CES_CREATE_COMPANY: '<?=GetMessageJS('CRM_CES_CREATE_COMPANY')?>',
						CRM_CES_CREATE_DEAL: '<?=GetMessageJS('CRM_CES_CREATE_DEAL')?>'
					});
				});
			</script>

			<div class="crm-button-open crm-element-button-open">
				<span onclick="BX['<?= $jsObject ?>'].createNewEntity(event);">
					<?= Loc::getMessage('CRM_CES_CREATE') ?>
				</span>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}
