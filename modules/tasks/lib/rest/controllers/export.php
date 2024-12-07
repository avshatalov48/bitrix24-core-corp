<?php
namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Controller;

class Export extends Controller\Export
{
	/** @var string - Module Id. */
	protected $module = 'tasks';

	/** @var string - Entity type name to export. */
	protected $entityType;

	/**
	 * Initializes controller.
	 */
	protected function init(): void
	{
		$this->keepFieldInProcess('entityType');

		$this->entityType = $this->request->get('ENTITY_TYPE');

		parent::init();
	}

	/**
	 * Returns file name
	 */
	protected function generateExportFileName(): string
	{
		if ($this->isExcel())
		{
			$fileExt = 'xls';
		}

		$date = (new DateTime())->format('Y-m-d_H:i:s');

		return 'tasks_' . $date . '.' . $fileExt;
	}

	private function isExcel(): bool
	{
		return $this->exportType === self::EXPORT_TYPE_EXCEL;
	}

	/**
	 * Returns temporally directory
	 */
	protected function generateTempDirPath(): string
	{
		$tempDir = \CTempFile::GetDirectoryName(
			self::KEEP_FILE_HOURS,
			[
				$this->module,
				uniqid($this->entityType. '_export_', true)
			]
		);

		\CheckDirPath($tempDir);

		return $tempDir;
	}

	/**
	 * Generate link to download local exported temporally file.
	 */
	protected function generateDownloadLink(): string
	{
		$params = [
			'PROCESS_TOKEN' => $this->processToken,
			'EXPORT_TYPE' => $this->exportType,
			'COMPONENT_NAME' => $this->componentName,
			'ENTITY_TYPE' => $this->entityType,
		];

		return $this->getActionUri(self::ACTION_DOWNLOAD, $params);
	}
}