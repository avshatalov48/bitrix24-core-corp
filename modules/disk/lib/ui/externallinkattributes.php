<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Disk\Document\BitrixHandler;
use Bitrix\Disk\Document\BoardsHandler;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Driver;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Viewer\ItemAttributes;
use Bitrix\Main\UI\Viewer\Renderer\Pdf;
use Bitrix\Main\UI\Viewer\Renderer\Renderer;

final class ExternalLinkAttributes extends ItemAttributes
{
	protected function setDefaultAttributes()
	{
		parent::setDefaultAttributes();

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();

		$documentHandler = $documentHandlersManager->getDefaultHandlerForView();
		$viewerType = $this->getViewerType();
		if ($documentHandler instanceof OnlyOfficeHandler && $viewerType === 'cloud-document')
		{
			$this->setTypeClass('BX.Disk.Viewer.OnlyofficeExternalLinkItem');
			$this->setAttribute('data-viewer-separate-item', true);

			$this->setExtension('disk.viewer.onlyoffice-item');

			Extension::load('disk.viewer.onlyoffice-item');
		}

		$boardsHandler = $documentHandlersManager->getHandlerByCode(BoardsHandler::getCode());
		if ($viewerType === 'board' && isset($boardsHandler))
		{
			$this->setTypeClass('BX.Disk.Viewer.BoardItem');
			$this->setAttribute('data-viewer-separate-item', true);

			$this->setExtension('disk.viewer.board-item');

			Extension::load(['disk.viewer.board-item', 'disk.viewer.actions']);
		}
	}

	public function setDocumentViewUrl(string $url): self
	{
		$this->setAttribute('data-document-view-url', $url);

		return $this;
	}

	protected static function getViewerTypeByFile(array $fileArray)
	{
		$documentHandler = Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();
		$viewerTypeByFile = parent::getViewerTypeByFile($fileArray);
		if ($viewerTypeByFile === Pdf::JS_TYPE_DOCUMENT && $documentHandler instanceof OnlyOfficeHandler)
		{
			return 'cloud-document';
		}
		if ($viewerTypeByFile === Pdf::JS_TYPE_DOCUMENT && !($documentHandler instanceof BitrixHandler))
		{
			return Renderer::JS_TYPE_UNKNOWN;
		}

		return $viewerTypeByFile;
	}
}
