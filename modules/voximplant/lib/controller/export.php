<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main;
use Bitrix\Main\Error;

class Export extends Main\Controller\Export
{

	/** @var string - Module Id. */
	protected $module = 'voximplant';

	/** @var int - ID of the last exported record. */
	protected $lastExportedId = -1;

	/**
	 * Initializes controller.
	 *
	 * @return void
	 */
	protected function init()
	{
		$this->keepFieldInProcess('lastExportedId');

		parent::init();
	}

	/**
	 * Returns file name
	 *
	 * @return string
	 */
	protected function generateExportFileName()
	{
		$fileExt = 'xls';

		$prefix = 'calls_detail'. '_' . date('Ymd');
		$hash = str_pad(dechex(crc32($prefix)), 8, '0', STR_PAD_LEFT);

		return uniqid($prefix. '_' . $hash. '_', false). '.' .$fileExt;
	}

	/**
	 * Returns temporally directory
	 *
	 * @return string
	 */
	protected function generateTempDirPath()
	{
		$tempDir = \CTempFile::GetDirectoryName(self::KEEP_FILE_HOURS, array($this->module, uniqid($this->entityType. '_export_', true)));

		\CheckDirPath($tempDir);

		return $tempDir;
	}

	/**
	 * Generate link to download local exported temporally file.
	 *
	 * @return string
	 */
	protected function generateDownloadLink()
	{
		$params = array(
			'PROCESS_TOKEN' => $this->processToken,
			'EXPORT_TYPE' => $this->exportType,
			'COMPONENT_NAME' => $this->componentName,
		);

		return $this->getActionUri(self::ACTION_DOWNLOAD, $params);
	}

	/**
	 * Performs exporting action.
	 *
	 * @return array|Main\Engine\Response\AjaxJson
	 */
	public function exportAction()
	{
		/** @global \CMain */
		global $APPLICATION;

		if ($this->isNewProcess)
		{
			$this->fileType = $this->getFileType();
			$this->fileName = $this->generateExportFileName();
			$this->filePath = $this->generateTempDirPath(). $this->fileName;
			$this->processedItems = 0;
			$this->totalItems = 0;
			$this->pageSize = self::ROWS_PER_PAGE;
			$this->saveProgressParameters();
		}

		$this->startTimer(self::TIME_LIMIT);
		do
		{
			$nextPage = (int)floor($this->processedItems / $this->pageSize) + 1;

			$componentParameters = array_merge(
				$this->componentParameters,
				array(
					'STEXPORT_MODE' => 'Y',
					'EXPORT_TYPE' => $this->exportType,
					'STEXPORT_PAGE_SIZE' => $this->pageSize,
					'STEXPORT_TOTAL_ITEMS' => $this->totalItems,
					'PAGE_NUMBER' => $nextPage,
					'STEXPORT_LAST_EXPORTED_ID' => $this->lastExportedId,
				)
			);

			ob_start();
			$componentResult = $APPLICATION->IncludeComponent(
				$this->componentName,
				'',
				$componentParameters
			);
			$exportData = ob_get_contents();
			ob_end_clean();

			$processedItemsOnStep = 0;

			if (is_array($componentResult))
			{
				if (isset($componentResult['ERROR']))
				{
					$this->addError(new Error($componentResult['ERROR']));
					break;
				}
				else
				{
					if (isset($componentResult['PROCESSED_ITEMS']))
					{
						$processedItemsOnStep = (int)$componentResult['PROCESSED_ITEMS'];
						$this->lastExportedRowId = $componentResult['LAST_EXPORTED_ROW_ID'];
					}

					// Get total items quantity on 1st step.
					if ($nextPage === 1 && isset($componentResult['TOTAL_ITEMS']))
					{
						$this->totalItems = (int)$componentResult['TOTAL_ITEMS'];
					}

					if (isset($componentResult['LAST_EXPORTED_ID']))
					{
						$this->lastExportedId = (int)$componentResult['LAST_EXPORTED_ID'];
					}
				}
			}

			if ($this->totalItems == 0)
			{
				break;
			}

			if ($processedItemsOnStep > 0)
			{
				$this->processedItems += $processedItemsOnStep;

				$this->writeTempFile($exportData, ($nextPage === 1));
				unset($exportData);

				$this->isExportCompleted = ($this->processedItems >= $this->totalItems);

				if ($this->isExportCompleted && !$this->isCloudAvailable)
				{
					// adjust completed file size
					$this->fileSize = $this->getSizeTempFile();
				}
			}
			elseif ($processedItemsOnStep == 0)
			{
				// Smth went wrong - terminate process.
				$this->isExportCompleted = true;

				if (!$this->isCloudAvailable)
				{
					$this->fileSize = $this->getSizeTempFile();
				}
			}

			if ($this->isExportCompleted === true)
			{
				// finish
				break;
			}
			if ($nextPage === 1)
			{
				// to answer faster
				break;
			}
		}
		while ($this->hasTimeLimitReached() !== true);

		if ($this->totalItems == 0)
		{
			$this->isExportCompleted = true;

			// Save progress
			$this->saveProgressParameters();

			// finish
			$result = $this->preformAnswer(self::ACTION_VOID);
			$result['STATUS'] = self::STATUS_COMPLETED;
		}
		else
		{
			// Save progress
			$this->saveProgressParameters();

			$result = $this->preformAnswer(self::ACTION_EXPORT);
			$result['STATUS'] = self::STATUS_PROGRESS;
		}

		return $result;
	}
}
