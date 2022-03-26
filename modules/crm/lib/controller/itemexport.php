<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Error;

class ItemExport extends Main\Controller\Export
{
	/** @var string - Module Id. */
	protected $module = 'crm';

	protected $entityTypeId;
	protected $categoryId;

	/**
	 * Initializes controller.
	 *
	 * @return void
	 */
	protected function init(): void
	{
		$this->keepFieldInProcess('entityTypeId');
		$this->keepFieldInProcess('categoryId');

		$this->entityTypeId = $this->request->get('entityTypeId');
		$this->categoryId = $this->request->get('categoryId');

		parent::init();
	}

	/**
	 * Checks for common errors.
	 *
	 * @param \Bitrix\Main\Engine\Action $action Action.
	 * @return bool - True if errors not exist.
	 */
	protected function checkCommonErrors($action): bool
	{
		parent::checkCommonErrors($action);

		$this->entityTypeId = (int)$this->entityTypeId;
		$factory = Container::getInstance()->getFactory($this->entityTypeId);
		if (!$factory)
		{
			$this->addError(new Error('EntityTypeId is not valid.'));
		}

		return count($this->getErrors()) === 0;
	}

	/**
	 * Returns progress option name
	 *
	 * @return string
	 */
	protected function getProgressParameterOptionName(): string
	{
		return parent::getProgressParameterOptionName() . '_' . $this->entityTypeId . '_' . $this->categoryId;
	}

	/**
	 * Returns file name
	 *
	 * @return string
	 */
	protected function generateExportFileName(): string
	{
		if ($this->exportType === self::EXPORT_TYPE_CSV)
		{
			$fileExt = 'csv';
		}
		elseif ($this->exportType === self::EXPORT_TYPE_EXCEL)
		{
			$fileExt = 'xls';
		}

		$prefix = $this->entityTypeId . '_' . date('Ymd');
		$hash = str_pad(dechex(crc32($prefix)), 8, '0', STR_PAD_LEFT);

		return uniqid($prefix . '_' . $hash . '_', false) . '.' . $fileExt;
	}

	/**
	 * Returns temporally directory
	 *
	 * @return string
	 */
	protected function generateTempDirPath(): string
	{
		$tempDir = \CTempFile::GetDirectoryName(
			self::KEEP_FILE_HOURS,
			[
				$this->module,
				uniqid(
					$this->entityTypeId . '_export_', true)
			]
		);

		\CheckDirPath($tempDir);

		return $tempDir;
	}

	/**
	 * Generate link to download local exported temporally file.
	 */
	protected function generateDownloadLink(): Main\Web\Uri
	{
		$params = [
			'PROCESS_TOKEN' => $this->processToken,
			'EXPORT_TYPE' => $this->exportType,
			'COMPONENT_NAME' => $this->componentName,
			'entityTypeId' => $this->entityTypeId,
			'categoryId' => $this->categoryId,
		];

		return $this->getActionUri(self::ACTION_DOWNLOAD, $params);
	}
}
