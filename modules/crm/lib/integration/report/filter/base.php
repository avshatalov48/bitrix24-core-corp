<?php

namespace Bitrix\Crm\Integration\Report\Filter;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;use Bitrix\Report\VisualConstructor\Helper\Filter;

class Base extends Filter
{
	public function getJsList()
	{
		return [

			'/bitrix/js/crm/crm.js',
			'/bitrix/js/crm/common.js',
			'/bitrix/js/crm/interface_grid.js'
		];
	}

	public function getCssList()
	{
		return [
			'/bitrix/js/crm/css/crm.css'
		];
	}

	public function getStringList()
	{
		$str = '<script type="text/javascript">'.
			   		'BX.ready(function() {'.
			   			'BX.CrmEntityType.setCaptions(' .
			   				\CUtil::PhpToJSObject(\CCrmOwnerType::GetJavascriptDescriptions()) .
						');' .
						'if(typeof(BX.CrmEntitySelector) !== "undefined"){' .
							'BX.CrmEntitySelector.messages = {'.
								'"selectButton": "' . Loc::getMessage('CRM_GRID_ENTITY_SEL_BTN') . '",'.
								'"noresult": "' . Loc::getMessage('CRM_GRID_SEL_SEARCH_NO_RESULT') . '",'.
								'"search": "' . Loc::getMessage('CRM_GRID_ENTITY_SEL_SEARCH') . '",'.
								'"last": "' . Loc::getMessage('CRM_GRID_ENTITY_SEL_LAST') . '"'.
							'};'.
						'}' .
			   		'})' .
				'</script>';
		return [
			$str
		];
	}

	public static function getFieldsList()
	{
		Loader::includeModule('socialnetwork');
		\CJSCore::init(array('socnetlogdest'));
		return parent::getFieldsList();
	}

}