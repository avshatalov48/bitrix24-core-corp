<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Disk\Driver;

global $USER;

$langAdditional = array(
	'MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES' => (
		Loader::includeModule('disk')
		&& $USER->isAuthorized()
		&& ($userStorage = Driver::getInstance()->getStorageByUserId($USER->getId()))
		&& ($folder = $userStorage->getFolderForUploadedFiles())
			? $folder->getId()
			: 0
	),
	'MOBILE_EXT_UTILS_MAX_UPLOAD_CHUNK_SIZE' => min(
		intval(\CUtil::unformat(ini_get('upload_max_filesize'))),
		intval(\CUtil::unformat(ini_get('post_max_size'))),
		(1024*1024*5 - 1024)
	)
);

return [
	'js' => './dist/utils.bundle.js',
	'lang_additional' => $langAdditional,
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];