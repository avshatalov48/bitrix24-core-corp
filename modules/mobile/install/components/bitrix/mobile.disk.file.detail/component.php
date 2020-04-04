<?
use Bitrix\Disk\File;

if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

if (intval($USER->GetID()) <= 0)
{
	return;
}

if (!CModule::IncludeModule('disk'))
{
	return;
}

if(empty($_REQUEST['objectId']))
{
	return;
}
/** @var File $file */
$file = File::loadById((int)$_REQUEST['objectId'], array('STORAGE'));
if(!$file)
{
	return;
}
$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
if(!$file->canRead($securityContext))
{
	return;
}

if(!empty($_GET['download']))
{
	$fileData = $file->getFile();
	\CFile::viewByUser($fileData, array("force_download" => false, 'attachment_name' => $file->getName()));
}


if(CFile::isImage($file->getName()))
{
	$icon = 'img.png';
}
else
{
	$icons = array(
		'pdf' => 'pdf.png',
		'doc' => 'doc.png',
		'docx' => 'doc.png',
		'ppt' => 'ppt.png',
		'pptx' => 'ppt.png',
		'rar' => 'rar.png',
		'xls' => 'xls.png',
		'xlsx' => 'xls.png',
		'zip' => 'zip.png',
	);
	$ext = strtolower(getFileExtension($file->getName()));
	$icon = isset($icons[$ext]) ? $icons[$ext] : 'blank.png';
}


$arResult['NAME'] = $file->getName();
$arResult['SIZE'] = $file->getSize();
$arResult['CREATE_TIME'] = $file->getCreateTime();
$arResult['DATE_CREATE'] = $file->getUpdateTime();
$arResult['ICON'] = $this->getPath() . '/images/' . $icon;
$arResult['DESCRIPTION'] = '';

$mobileDiskPrepareForJson = function($string)
{
	if(!\Bitrix\Main\Application::getInstance()->isUtfMode())
	{
		return \Bitrix\Main\Text\Encoding::convertEncodingArray($string, SITE_CHARSET, 'UTF-8');
	}
	return $string;
};

$arResult['URL'] = SITE_DIR . "mobile/disk/{$file->getId()}/download" .'/' . $mobileDiskPrepareForJson($file->getName());

$this->IncludeComponentTemplate();
