<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

	if (!isset($WIZARD_SERVICE_ABSOLUTE_PATH))
		$WIZARD_SERVICE_ABSOLUTE_PATH = WIZARD_SERVICE_ABSOLUTE_PATH;

	CopyDirFiles($WIZARD_SERVICE_ABSOLUTE_PATH."/smiles_install/icons/", $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/main/icons/3/', true, true);
	CopyDirFiles($WIZARD_SERVICE_ABSOLUTE_PATH."/smiles_install/icons/", $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/main/icons/4/', true, true);
	CopyDirFiles($WIZARD_SERVICE_ABSOLUTE_PATH."/smiles_install/smiles/", $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/main/smiles/3/', true, true);
	CopyDirFiles($WIZARD_SERVICE_ABSOLUTE_PATH."/smiles_install/smiles/", $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/main/smiles/4/', true, true);

	global $DB;
	$DB->Query("TRUNCATE TABLE b_smile");
	$DB->Query("TRUNCATE TABLE b_smile_set");
	$DB->Query("TRUNCATE TABLE b_smile_lang");

	$arLang = Array();
	$arLang2 = Array();
	$langs = CLanguage::GetList(($b=""), ($o=""));
	while($language = $langs->Fetch())
	{
		$MESS = Array();
		if (file_exists($WIZARD_SERVICE_ABSOLUTE_PATH."/lang/".$language["LID"]."/smiles_install.php"))
		{
			include_once($WIZARD_SERVICE_ABSOLUTE_PATH."/lang/".$language["LID"]."/smiles_install.php");
			if ($MESS && isset($MESS['MAIN_SMILE_DEF_SET_NAME']))
			{
				$arLang[$language["LID"]] = $MESS['MAIN_SMILE_DEF_SET_NAME'];
				$arLang2[$language["LID"]] = $MESS['MAIN_SMILE_DEF_SET_NAME'];
			}
			if ($MESS && isset($MESS['MAIN_SMILE_DEF_GALLERY_NAME']))
			{
				$arLang[$language["LID"]] = $MESS['MAIN_SMILE_DEF_GALLERY_NAME'];
			}
		}
	}
	$smileGalleryId = CSmileGallery::add(Array(
		'STRING_ID' => 'bitrix',
		'LANG' => $arLang,
	));
	$smileSetId = CSmileSet::add(Array(
		'STRING_ID' => 'bitrix_main',
		'PARENT_ID' => $smileGalleryId,
		'LANG' => $arLang2,
	));
	if (intval($smileSetId) > 0)
	{
		$arSmiles = Array();
		if (file_exists($WIZARD_SERVICE_ABSOLUTE_PATH.'/smiles_install/install.csv'))
		{
			$arLang = Array();
			$db_res = CLanguage::GetList(($b="sort"), ($o="asc"));
			while ($res = $db_res->Fetch())
			{
				if (file_exists($WIZARD_SERVICE_ABSOLUTE_PATH.'/smiles_install/install_lang_'. $res["LID"].'.csv'))
				{
					$arSmiles = Array();
					$csvFile = new CCSVData();
					$csvFile->LoadFile($WIZARD_SERVICE_ABSOLUTE_PATH.'/smiles_install/install_lang_'.$res["LID"].'.csv');
					$csvFile->SetFieldsType("R");
					$csvFile->SetFirstHeader(false);
					while($smile = $csvFile->Fetch())
					{
						if (defined('BX_UTF') && BX_UTF && $res["LID"] == 'ru')
							$smile[1] = $GLOBALS['APPLICATION']->ConvertCharset($smile[1], 'windows-1251', 'utf-8');

						$arLang[$smile[0]][$res["LID"]] = $smile[1];
					}
				}
			}

			$csvFile = new CCSVData();
			$csvFile->LoadFile($WIZARD_SERVICE_ABSOLUTE_PATH.'/smiles_install/install.csv');
			$csvFile->SetFieldsType("R");
			$csvFile->SetFirstHeader(false);
			while($smileRes = $csvFile->Fetch())
			{
				$smile = Array(
					'TYPE' => $smileRes[0],
					'CLICKABLE' => $smileRes[1] == 'Y'? 'Y': 'N',
					'SORT' => intval($smileRes[2]),
					'IMAGE' => $smileRes[3],
					'IMAGE_WIDTH' => intval($smileRes[4]),
					'IMAGE_HEIGHT' => intval($smileRes[5]),
					'IMAGE_DEFINITION' => in_array($smileRes[6], Array(CSmile::IMAGE_SD, CSmile::IMAGE_HD, CSmile::IMAGE_UHD))? $smileRes[6]: ($smileRes[6] == 'Y'? CSmile::IMAGE_HD: CSmile::IMAGE_SD),
					'HIDDEN' => in_array($smileRes[7], Array('Y', 'N'))? $smileRes[7]: 'N',
					'IMAGE_LANG' => in_array($smileRes[7], Array('Y', 'N'))? $smileRes[8]: $smileRes[7], // for legacy
					'TYPING' => in_array($smileRes[7], Array('Y', 'N'))? $smileRes[9]: $smileRes[8]
				);

				if (!in_array($smile['TYPE'], Array(CSmile::TYPE_SMILE, CSmile::TYPE_ICON)))
					continue;

				$smile['IMAGE'] = GetFileName($smile['IMAGE']);

				$arInsert = Array(
					'TYPE' => $smile['TYPE'],
					'SET_ID' => $smileSetId,
					'CLICKABLE' => $smile['CLICKABLE'],
					'SORT' => $smile['SORT'],
					'IMAGE' => $smile['IMAGE'],
					'IMAGE_WIDTH' => $smile['IMAGE_WIDTH'],
					'IMAGE_HEIGHT' => $smile['IMAGE_HEIGHT'],
					'IMAGE_DEFINITION' => $smile['IMAGE_DEFINITION'],
					'HIDDEN' => $smile['HIDDEN'],
					'TYPING' => $smile['TYPING'],
				);

				if (isset($arLang[$smile['IMAGE_LANG']]))
					$arInsert['LANG'] = $arLang[$smile['IMAGE_LANG']];

				$arSmiles[] = $arInsert;
			}
		}
		foreach ($arSmiles as $smile)
			CSmile::add($smile);
	}
?>