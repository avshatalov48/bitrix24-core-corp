<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CMedialib::ShowDialogScript(array(
			"event" => "LHED_Img_MLOpen",
			"arResultDest" => Array("FUNCTION_NAME" => "LHED_Img_SetUrl")
		));
CAdminFileDialog::ShowScript(Array(
			"event" => "LHED_Link_FDOpen",
			"arResultDest" => Array("ELEMENT_ID" => "lhed_link_href"),
			"arPath" => Array("SITE" => SITE_ID),
			"select" => 'F',
			"operation" => 'O',
			"showUploadTab" => true,
			"showAddToMenuTab" => false,
			"fileFilter" => 'php, html',
			"allowAllFiles" => true,
			"SaveConfig" => true
		));

CAdminFileDialog::ShowScript(Array
		(
			"event" => "LHED_Img_FDOpen",
			"arResultDest" => Array("FUNCTION_NAME" => "LHED_Img_SetUrl"),
			"arPath" => Array("SITE" => SITE_ID),
			"select" => 'F',
			"operation" => 'O',
			"showUploadTab" => true,
			"showAddToMenuTab" => false,
			"fileFilter" => 'image',
			"allowAllFiles" => true,
			"SaveConfig" => true
		));
?>