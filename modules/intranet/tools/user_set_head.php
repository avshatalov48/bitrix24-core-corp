<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if (check_bitrix_sessid())
{
	$IBLOCK_ID = COption::getOptionInt('intranet', 'iblock_structure', 0);
	$SECTION_ID = intval($_REQUEST['SECTION_ID']);
	$USER_ID = intval($_REQUEST['USER_ID']);

	if ($IBLOCK_ID && $SECTION_ID && $USER_ID && CModule::IncludeModule('iblock'))
	{
		$perm = CIBlock::GetPermission($IBLOCK_ID);
		if ($perm >= 'W')
		{
			$obS = new CIBlockSection();
			
			if ($obS->Update($SECTION_ID, array('UF_HEAD' => $USER_ID)))
			{
				echo '<script>BX.reload(true);</script>';
			}
			elseif ($obS->LAST_ERROR)
			{
				echo '<script>alert(\''.CUtil::JSEscape($obS->LAST_ERROR).'\');</script>';
			}
		}
		else
		{
			echo '<script>alert(\'Access denied!\');</script>';
		}
	}
	else
	{
		echo '<script>alert(\'Params error!\');</script>';
	}
}
else
{
	echo '<script>alert(\'Session expired!\');</script>';
}
?>