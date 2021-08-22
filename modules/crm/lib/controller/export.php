<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Main;
use Bitrix\Main\Error;


class Export extends Main\Controller\Export
{
	/** @var string - Module Id. */
	protected $module = 'crm';

	/** @var string - Entity type name to export. */
	protected $entityType;


	/**
	 * Initializes controller.
	 *
	 * @return void
	 */
	protected function init()
	{
		$this->keepFieldInProcess('entityType');

		$this->entityType = $this->request->get('ENTITY_TYPE');

		parent::init();
	}


	/**
	 * Checks for common errors.
	 *
	 * @param \Bitrix\Main\Engine\Action $action Action.
	 * @return bool - True if errors not exist.
	 */
	protected function checkCommonErrors($action)
	{
		parent::checkCommonErrors($action);

		if ($this->entityType === '')
		{
			$this->addError(new Error('Entity type is not specified.'));
		}
		$entityTypeId = \CCrmOwnerType::ResolveID($this->entityType);
		$allowedTypes = ['PRODUCT', \CCrmOwnerType::CheckCorrectionName];
		if($entityTypeId === \CCrmOwnerType::Undefined && !in_array($this->entityType, $allowedTypes))
		{
			$this->addError(new Error('Undefined entity type is specified.'));
		}

		if ($this->entityType === \CCrmOwnerType::ContactName)
		{
			if (!\CCrmContact::CheckExportPermission())
			{
				$this->addError(new Error('Access denied.'));
			}
		}

		return count($this->getErrors()) === 0;
	}

	/**
	 * Returns progress option name
	 *
	 * @return string
	 */
	protected function getProgressParameterOptionName()
	{
		return parent::getProgressParameterOptionName(). '_'. $this->entityType;
	}

	/**
	 * Returns file name
	 *
	 * @return string
	 */
	protected function generateExportFileName()
	{
		if ($this->exportType === self::EXPORT_TYPE_CSV)
		{
			$fileExt = 'csv';
		}
		elseif ($this->exportType === self::EXPORT_TYPE_EXCEL)
		{
			$fileExt = 'xls';
		}

		$prefix = $this->entityType. '_'. date('Ymd');
		$hash = str_pad(dechex(crc32($prefix)), 8, '0', STR_PAD_LEFT);

		return uniqid($prefix. '_'. $hash. '_', false).'.'.$fileExt;
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
			'ENTITY_TYPE' => $this->entityType,
		);

		return $this->getActionUri(self::ACTION_DOWNLOAD, $params);
	}
}
