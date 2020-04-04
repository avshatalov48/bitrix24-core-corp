<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Disk\Document\BitrixHandler;
use Bitrix\Disk\Driver;
use Bitrix\Main\UI\Viewer\ItemAttributes;
use Bitrix\Main\UI\Viewer\Renderer\Pdf;
use Bitrix\Main\UI\Viewer\Renderer\Renderer;

final class ExternalLinkAttributes extends ItemAttributes
{
	const JS_TYPE_CLASS_CLOUD_DOCUMENT = 'BX.Disk.Viewer.DocumentItem';

	protected static function getViewerTypeByFile(array $fileArray)
	{
		$defaultHandlerForView = Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();
		$viewerTypeByFile = parent::getViewerTypeByFile($fileArray);
		if ($viewerTypeByFile === Pdf::JS_TYPE_DOCUMENT && !($defaultHandlerForView instanceof BitrixHandler))
		{
			return Renderer::JS_TYPE_UNKNOWN;
		}

		return $viewerTypeByFile;
	}
}
