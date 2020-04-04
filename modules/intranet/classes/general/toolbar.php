<?
class CIntranetToolbar
{
	var $arButtons = array();
	var $bDisplay = true;
	var $bDisabled = false;
	
	/*
	arButton = array(
		'ID' => 'anchor id',	
		'TEXT' => 'Button text',
		'TITLE' => 'Button alternative title',
		'ICON' => 'button-icon-class',
		'HREF' => 'button link href',
		'ONCLICK' => 'button link onclick',
		'SORT' => 'button sorting weight',
	)
	*/
	function AddButton($arButton)
	{
		if ($arButton['SORT'] <= 0)
			$arButton['SORT'] = 10000;
	
		$this->arButtons[] = $arButton;
		return count($this->arButtons)-1;
	}
	
	function Disable()
	{
		$this->bDisabled = true;
	}

	function Enable()
	{
		$this->bDisabled = false;
	}
	
	function Show()
	{
		global $APPLICATION;
		
		if (!$this->bDisabled)
		{
			//$APPLICATION->SetTemplateCSS('intranet/toolbar.css');
			$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/intranet_toolbar.css');
			$APPLICATION->AddBufferContent(array(&$this, '__display'));
		}
	}
	
	function __display()
	{
		global $APPLICATION;
	
		$result = '';
	
		if ($this->bDisplay)
		{
			if (($cnt = count($this->arButtons)) > 0)
			{
				$bAjaxMode = defined('PUBLIC_AJAX_MODE') && PUBLIC_AJAX_MODE == 1;
				uasort($this->arButtons, create_function('$a, $b', 'if($a["SORT"] == $b["SORT"]) return 0; return ($a["SORT"] < $b["SORT"])? -1 : 1;'));

				ob_start();				
				$APPLICATION->IncludeComponent("bitrix:intranet.toolbar",
					"",
					array(
						"AJAX_MODE" => $bAjaxMode,
						"OBJECT" => $this
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				$result = ob_get_contents();
				ob_end_clean();
			}

			$this->bDisplay = false;
		}
		
		return $result;
	}
}
?>