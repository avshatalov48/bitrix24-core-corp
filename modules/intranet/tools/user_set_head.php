<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if (check_bitrix_sessid())
{
	$IBLOCK_ID = COption::getOptionInt('intranet', 'iblock_structure', 0);
	$SECTION_ID = intval($_REQUEST['SECTION_ID']);
	$USER_ID = intval($_REQUEST['USER_ID']);

	if ($IBLOCK_ID && $SECTION_ID && $USER_ID && CModule::IncludeModule('iblock'))
	{
		// TODO: This check will be in the "humanresources" module.
		$perm = CIBlock::GetPermission($IBLOCK_ID);
		if ($perm >= 'W')
		{
			$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
				->departmentRepository();
			try
			{
				$departmentRepository->setHead($SECTION_ID, $USER_ID);
				echo '<script>BX.reload(true);</script>';
			}
			catch (\Exception $exception)
			{
				echo '<script>alert(\''.CUtil::JSEscape($exception->getMessage()).'\');</script>';
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