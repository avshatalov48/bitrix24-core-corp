<?php

namespace Bitrix\Imopenlines\Ui;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Update\Stepper;

class Helper
{
	public static function renderCustomSelectors($filterId, array $filterDefinition)
	{
		global $APPLICATION;
		Asset::getInstance()->addJs('/bitrix/js/imopenlines/common.js');

		$entitySelectors = array();

		foreach($filterDefinition as $filterItem)
		{
			if(!(isset($filterItem['type'])
				&& $filterItem['type'] === 'custom_entity'
				&& isset($filterItem['selector'])
				&& is_array($filterItem['selector']))
			)
			{
				continue;
			}

			$selector = $filterItem['selector'];

			$selectorType = isset($selector['TYPE']) ? $selector['TYPE'] : '';
			$selectorData = isset($selector['DATA']) && is_array($selector['DATA']) ? $selector['DATA'] : null;

			if(empty($selectorData))
			{
				continue;
			}

			if($selectorType === 'crm_entity')
			{
				$entitySelectors[] = $selectorData;
			}
		}

		//region CRM Entity Selectors
		if(!empty($entitySelectors) && Loader::includeModule('crm'))
		{
			Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
			Asset::getInstance()->addJs('/bitrix/js/crm/crm.js');
			Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');

			\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
			Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
			?><script type="text/javascript">
			BX.ready(
				function()
				{
					BX.CrmEntitySelector.messages =
						{
							"selectButton": "<?=GetMessageJS('IMOL_UI_HELPER_ENTITY_SEL_BTN')?>",
							"noresult": "<?=GetMessageJS('IMOL_UI_HELPER_SEL_SEARCH_NO_RESULT')?>",
							"search": "<?=GetMessageJS('IMOL_UI_HELPER_ENTITY_SEL_SEARCH')?>",
							"last": "<?=GetMessageJS('IMOL_UI_HELPER_ENTITY_SEL_LAST')?>"
						};
					BX.CrmEntityType.setCaptions(<?=\CUtil::PhpToJSObject(\CCrmOwnerType::GetJavascriptDescriptions())?>);
				}
			);<?
			foreach($entitySelectors as $entitySelector)
			{
				$selectorID = $entitySelector['ID'];
				$fieldID = $entitySelector['FIELD_ID'];
				$entityTypeNames = $entitySelector['ENTITY_TYPE_NAMES'];
				$isMultiple = $entitySelector['IS_MULTIPLE'];
				$title = isset($entitySelector['TITLE']) ? $entitySelector['TITLE'] : '';
				?>BX.ready(
				function()
				{
					BX.CrmUIFilterEntitySelector.create(
						"<?=\CUtil::JSEscape($selectorID)?>",
						{
							fieldId: "<?=\CUtil::JSEscape($fieldID)?>",
							entityTypeNames: <?=\CUtil::PhpToJSObject($entityTypeNames)?>,
							isMultiple: <?=$isMultiple ? 'true' : 'false'?>,
							title: "<?=\CUtil::JSEscape($title)?>"
						}
					);
				}
			);<?
			}
			?></script><?
		}
		//endregion
	}

	public static function getStatisticStepper()
	{
		$res = array(
			"imopenlines" => array('Bitrix\Imopenlines\Update\Session')
		);

		return Stepper::getHtml($res, Loc::getMessage('IMOL_UI_HELPER_STAT_INDEX'));
	}
}