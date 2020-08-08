<?
define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);
global $USER;

if (!\Bitrix\Main\Loader::includeModule("scale") || !$GLOBALS['USER']->IsAdmin())
	return;

if ($_SERVER["REQUEST_METHOD"]=="POST" && $_POST["action"] <> '' && check_bitrix_sessid())
{
	$arJsonData = array();

	switch ($_POST["action"])
	{
		case "set_certificate":

			if ($_POST["type"] == "le")
			{
				$certificateParams = array(
					"SITE_NAME_CONF" => $_POST["certificateData"]["siteNameConf"],
					"EMAIL" => $_POST["certificateData"]["email"],
					"DNS" => $_POST["certificateData"]["dns"]
				);

				$action = \Bitrix\Scale\ActionsData::getActionObject("CERTIFICATE_LETS_ENCRYPT_CONF", "", $certificateParams);
				$result = $action->start();
			}
			elseif ($_POST["type"] == "self")
			{
				$certificateParams = array(
					"SITE_NAME_CONF" => $_POST["certificateData"]["siteNameConf"],
					"PRIVATE_KEY_PATH" => $_POST["certificateData"]["keyPath"],
					"CERTIFICATE_PATH" => $_POST["certificateData"]["path"],
					"CERTIFICATE_CHAIN_PATH" => $_POST["certificateData"]["chainPath"]
				);

				$action = \Bitrix\Scale\ActionsData::getActionObject("CERTIFICATE_SELF_CONF", "", $certificateParams);
				$result = $action->start();
			}

			if (!$result)
			{
				$error = GetMessage("CERTIFICATE_ERROR");
			}
			break;

		case "check_state":
			$result = \Bitrix\Scale\ActionsData::checkRunningAction();
			if (empty($result))
			{
				$arJsonData["success"] = "Y";
			}
			else
			{
				$result = array_shift($result);
				if (!empty($result["error_messages"]))
				{
					$arJsonData["error"] = $result["error_messages"];
				}
				else
				{
					$arJsonData["process"] = "Y";
				}
			}

			break;

		case "upload_files":
			if(!empty($_FILES))
			{
				$tmpDir = \CTempFile::GetDirectoryName(1);
				CheckDirPath($tmpDir);
				$uploadedFiles = array();

				foreach($_FILES as $file)
				{
					if(!is_uploaded_file($file['tmp_name']))
						continue;

					if($file['size'] <= 0)
						continue;

					$uploadFile = $tmpDir.basename($file['name']);

					if(move_uploaded_file($file['tmp_name'], $uploadFile))
					{
						//$arJsonData = $uploadFile;

						$APPLICATION->RestartBuffer();
						echo htmlspecialcharsbx($uploadFile);
						die();
					}
				}
			}

			break;
	}

	if (!empty($error))
		$arJsonData["error"] = $error;

	$APPLICATION->RestartBuffer();
	echo \Bitrix\Main\Web\Json::encode($arJsonData);
	die();
}