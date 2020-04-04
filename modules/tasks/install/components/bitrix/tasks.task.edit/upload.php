<?php
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule("tasks");

global $USER;
$curUserId = (int) $USER->GetID();

$callbackFunctionName = 'window.parent.window.taskManagerForm._filesUploaded';

if (isset($_POST['callbackFunctionName']) && check_bitrix_sessid())
	$callbackFunctionName = $_POST['callbackFunctionName'];

if ($_POST["mode"] == "upload")
{
	$arResult = array();
	if (check_bitrix_sessid())
	{
		$count = sizeof($_FILES["task-attachments"]["name"]);
		for($i = 0; $i < $count; $i++)
		{
			$fileID = CTaskFiles::saveFileTemporary(
				$curUserId,
				$_FILES["task-attachments"]["name"][$i],
				$_FILES["task-attachments"]["size"][$i],
				$_FILES["task-attachments"]["tmp_name"][$i],
				$_FILES["task-attachments"]["type"][$i]
			);

			$tmp = array(
				"name" => $_FILES["task-attachments"]["name"][$i],
				"fileID" => $fileID
			);

			if ($fileID)
				$tmp["fileULR"] = "/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=" . (int) $fileID;

			$arResult[] = $tmp;
		}
	}
	$APPLICATION->RestartBuffer();
	Header('Content-Type: text/html; charset='.LANG_CHARSET);
?>
	<script type="text/javascript">
		<?php echo CUtil::JSEscape($callbackFunctionName); ?>(<?php echo CUtil::PhpToJsObject($arResult);?>, <?php echo intval($_POST["uniqueID"])?>);
	</script>
<?php
}
elseif ($_POST["mode"] == "delete")
{
	if (check_bitrix_sessid())
		CTaskFiles::removeTemporaryFile($curUserId, (int) $_POST['fileID']);
}
?>
<?php die();?>