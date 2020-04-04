<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\TypeFile;

/**
 * Class Icon
 * @package Bitrix\Disk\Ui
 *
 * CSS classes (modules/disk/install/js/disk/css/disk.css)
 */
final class Icon
{
	protected static $possibleIconClasses = array(
		'pdf' => 'icon-pdf',
		'doc' => 'icon-doc',
		'docx' => 'icon-doc',
		'ppt' => 'icon-ppt',
		'pptx' => 'icon-ppt',
		'xls' => 'icon-xls',
		'xlsx' => 'icon-xls',
		'php' => 'icon-php',
		'txt' => 'icon-txt',
		'zip' => 'icon-zip',
		'rar' => 'icon-rar',
		'emp' => 'icon-emp',
		'img' => 'icon-img',
		'exe' => 'icon-exe',
		'vid' => 'icon-vid',

		'non' => 'icon-non',
	);

	public static function getIconClassByObject(BaseObject $object, $appendSharedClass = false)
	{
		$class = '';
		if($object instanceof Folder)
		{
			$class = 'bx-disk-folder-icon';
		}
		elseif($object instanceof File)
		{
			$class = 'bx-disk-file-icon';
			$ext = strtolower($object->getExtension());
			if(isset(self::$possibleIconClasses[$ext]))
			{
				$class .= ' ' . self::$possibleIconClasses[$ext];
			}
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			elseif(TypeFile::isImage($object))
			{
				$class .= ' ' . self::$possibleIconClasses['img'];
			}
			elseif(TypeFile::isVideo($object))
			{
				$class .= ' ' . self::$possibleIconClasses['vid'];
			}

		}
		if($object->isLink())
		{
			$class .= ' icon-shared shared icon-shared_2';
		}
		elseif($appendSharedClass)
		{
			$class .= ' icon-shared shared icon-shared_1 icon-shared_2';
		}

		return $class;
	}
}