<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!$USER->CanDoOperation('edit_php'))
	$APPLICATION->AuthForm("");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/backup.php");

if (!function_exists("ParseFileName"))
{
	function ParseFileName($name)
	{
		if (preg_match('#^(.+)\.(tar.*)$#', $name, $regs))
			return array('name' => $regs[1], 'ext' => $regs[2]);
		elseif (preg_match('#^(.+)\.([^\.]+)$#', $name, $regs))
			return array('name' => $regs[1], 'ext' => $regs[2]);
		return array('name' => $name, 'ext' => '');
	}
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid())
{
	$path = BX_ROOT."/backup";

	if (isset($_REQUEST['action']))
	{
		$APPLICATION->RestartBuffer();

		switch ($_REQUEST['action'])
		{
			case "download":
				$arLink = array();
				$name = $path.'/'.$_REQUEST['f_id'];

				while(file_exists($_SERVER["DOCUMENT_ROOT"].$name))
				{
					$arLink[] = htmlspecialcharsbx($name);
					$name = CTar::getNextName($name);
				}

				echo "links=".\Bitrix\Main\Web\Json::encode($arLink).";";
				break;

			case "delete":
				$item = $_REQUEST['f_id'];

				while(file_exists($f = $_SERVER["DOCUMENT_ROOT"].$path.'/'.$item))
				{
					if (!unlink($f))
						echo \Bitrix\Main\Web\Json::encode(array("error" => GetMessage('DUMP_DELETE_ERROR', array('#FILE#' => $f))));

					$item = CTar::getNextName($item);
				}

				break;

			case "link":
				$name = $path.'/'.$_REQUEST['f_id'];
				$host = COption::GetOptionString('main', 'server_name', $_SERVER['HTTP_HOST']);
				$url = 'http://'.htmlspecialcharsbx($host.$name);

				if ($url)
					echo '<script>window.prompt("'.GetMessage("MAIN_DUMP_USE_THIS_LINK").' restore.php", "'.htmlspecialcharsbx($url).'");'."</script>";
				break;

			case "restore":
				$http = new CHTTP;
				if (!$http->Download('https://www.1c-bitrix.ru/download/files/scripts/restore.php', $_SERVER["DOCUMENT_ROOT"].'/restore.php'))
				{
					echo \Bitrix\Main\Web\Json::encode(array("error" => GetMessage('MAIN_DUMP_ERR_COPY_FILE', array('#FILE#' => "restore.php"))));
				}
				else
				{
					$name = $path.'/'.$_REQUEST['f_id'];
					$url = 'local_arc_name='.htmlspecialcharsbx($name);
					if ($url)
						echo '<script>document.location = "/restore.php?Step=1&'.$url.'";</script>';
				}
				break;
			case "rename":
				if (preg_match('#^[a-z0-9\-\._]+$#i',$_REQUEST['name']))
				{
					$arName = ParseFileName($_REQUEST['ID']);
					$new_name = $_REQUEST['name'].'.'.$arName['ext'];

					$ID = $_REQUEST['ID'];
					while(file_exists($_SERVER["DOCUMENT_ROOT"].$path.'/'.$ID))
					{
						if (!rename($_SERVER["DOCUMENT_ROOT"].$path.'/'.$ID, $_SERVER["DOCUMENT_ROOT"].$path.'/'.$new_name))
						{
							echo \Bitrix\Main\Web\Json::encode(array("error" => GetMessage("MAIN_DUMP_ERR_FILE_RENAME").htmlspecialcharsbx($ID)));
							break;
						}

						$ID = CTar::getNextName($ID);
						$new_name = CTar::getNextName($new_name);
					}
				}
				else
					echo \Bitrix\Main\Web\Json::encode(array("error" => GetMessage("MAIN_DUMP_ERR_NAME")));
				break;
		}
		CMain::FinalActions();
		die();
	}

	//delete several files from grid
	if (isset($_REQUEST["action_button_backup_grid"]) && $_REQUEST["action_button_backup_grid"] == "delete")
	{
		foreach ($_REQUEST["ID"] as $item)
		{
			while(file_exists($f = $_SERVER["DOCUMENT_ROOT"].$path.'/'.$item))
			{
				if (!unlink($f))
					echo \Bitrix\Main\Web\Json::encode(array("error" => GetMessage('DUMP_DELETE_ERROR', array('#FILE#' => $f))));

				$item = CTar::getNextName($item);
			}
		}
	}
}

//get files backup
$arResult["BACKUP_FILES"] = array();
$arTmpFiles = array();
$arFilter = array();
if (is_dir($p = $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/backup'))
{
	if ($dir = opendir($p))
	{
		while(($item = readdir($dir)) !== false)
		{
			$f = $p.'/'.$item;
			if (!is_file($f))
				continue;
			$arTmpFiles[] = array(
				'NAME' => $item,
				'SIZE' => filesize($f),
				'DATE' => filemtime($f),
				'BUCKET_ID' => 0,
				'PLACE' => GetMessage("MAIN_DUMP_LOCAL")
			);
		}
		closedir($dir);
	}
}

$arParts = array();
$arSize = array();
$arFiles = array();
$i=0;
$by = 'timestamp';
foreach($arTmpFiles as $k=>$ar)
{
	if (preg_match('#^(.*\.(enc|tar|gz|sql))(\.[0-9]+)?$#',$ar['NAME'],$regs))
	{
		$i++;
		$BUCKET_ID = intval($ar['BUCKET_ID']);
		$arParts[$BUCKET_ID.$regs[1]]++;
		$arSize[$BUCKET_ID.$regs[1]] += $ar['SIZE'];
		if (!$regs[3])
		{
			if ($by == 'size')
				$key = $arSize[$BUCKET_ID.$regs[1]];
			elseif ($by == 'timestamp')
				$key = $ar['DATE'];
			elseif ($by == 'location')
				$key = $ar['PLACE'];
			else // name
				$key = $regs[1];
			$key .= '_'.$i;
			$arFiles[$key] = $ar;
		}
	}
}

$gridObj = new \Bitrix\Main\Grid\Options("backup_grid");
$sort = $gridObj->getSorting();

if (isset($sort["sort"]["DATE"]) && $sort["sort"]["DATE"] == 'asc')
	ksort($arFiles);
else
	krsort($arFiles);

$rsDirContent = new CDBResult;
$rsDirContent->InitFromArray($arFiles);
$rsDirContent->NavStart(20);
$arResult["NAV"] = $rsDirContent;
$arResult['TOTAL_RECORD_COUNT'] = $rsDirContent->NavRecordCount;

while($f = $rsDirContent->NavNext(true, "f_"))
{
	$BUCKET_ID = intval($f['BUCKET_ID']);

	$c = $arParts[$BUCKET_ID.$f['NAME']];
	if ($c > 1)
	{
		$parts = ' ('.GetMessage("MAIN_DUMP_PARTS").$c.')';
		$size = $arSize[$BUCKET_ID.$f['NAME']];
	}
	else
	{
		$parts = '';
		$size = $f['SIZE'];
	}

	$arName = ParseFileName($f['NAME']);

	$actions = array();
	if (!preg_match('#\.sql$#i',$f['NAME']))
	{
		$actions = array(
			array(
				"text" => GetMessage("BACKUP_ACTION_DOWNLOAD"),
				"onclick" => "BX.Intranet.Backup.downloadFiles('".$f['NAME']."')"
			),
			array(
				"text" => GetMessage("BACKUP_ACTION_LINK"),
				"onclick" => "BX.Intranet.Backup.getLink('".$f['NAME']."')"
			),
			array(
				"text" => GetMessage("BACKUP_ACTION_RESTORE"),
				"onclick" => "BX.Intranet.Backup.restoreFiles('".$f['NAME']."')"
			),
			array(
				"text" => GetMessage("BACKUP_ACTION_RENAME"),
				"onclick" => "BX.Intranet.Backup.renameFiles('".$f['NAME']."', '".$arName["name"]."', this.parentNode)"
			)
		);
	}
	$actions[] = array(
		"text" => GetMessage("BACKUP_ACTION_DELETE"),
		"onclick" => "BX.Intranet.Backup.deleteFiles('".$f['NAME']."')"
	);

	$arResult["BACKUP_FILES"][] = array(
		"id" => $f['NAME'],
		"columns" => array(
			"NAME" => $f['NAME'].$parts,
			"SIZE" => CFile::FormatSize($size),
			"DATE" => FormatDate('x', $f['DATE']),
			"PLACE" => $f['PLACE']
		),
		"actions" => $actions
	);
}

$this->IncludeComponentTemplate();
?>
