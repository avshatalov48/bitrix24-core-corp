<?php

namespace Bitrix\Crm\FileUploader;

use Bitrix\Crm\WebForm;

use Bitrix\UI\FileUploader\Contracts\CustomFingerprint;
use Bitrix\UI\FileUploader\FileOwnershipCollection;
use Bitrix\UI\FileUploader\Configuration;
use Bitrix\UI\FileUploader\UploaderController;

use Bitrix\Main;

class SiteFormFileUploaderController extends UploaderController implements CustomFingerprint
{
	public function __construct(array $options)
	{
		parent::__construct($options);
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function getConfiguration(): Configuration
	{
		return (new Configuration())
			->setMaxFileSize(null)
			->setImageMaxFileSize(null)
			->setImageMinWidth(0)
			->setImageMinHeight(0)
			->setImageMaxWidth(100 * 1000)
			->setImageMaxHeight(100 * 1000)
		;
	}

	public function canUpload(): bool
	{
		if (
			empty($this->options['formId'])
			|| empty($this->options['secCode'])
			|| empty($this->options['fieldId'])
			|| empty($this->getFingerprint())
			|| !is_array($this->options['fieldsSize'])
		)
		{
			return false;
		}

		$fieldsSize = $this->options['fieldsSize'];
		$formId = intval($this->options['formId']);
		$fieldId = $this->options['fieldId'];

		$form = new WebForm\Form($formId);
		if (
			!$form->checkSecurityCode($this->options['secCode'])
			|| !$form->isActive()
			|| !$form->hasField($fieldId)
		)
		{
			return false;
		}

		$fileFields = $form->getFieldsByType('file');
		if (
			!$this->checkFieldsSize($fileFields, $fieldsSize)
			|| !$this->checkDailyLimit($fieldsSize)
		)
		{
			return false;
		}

		return true;
	}

	private function checkDailyLimit(array $fieldsSizeMap): bool
	{
		$summarySizeBytes = array_reduce(
			array_values($fieldsSizeMap),
			function ($carry, $item) {
				return $carry + $item;
			},
			0
		);

		$dailyLimiter = WebForm\Limitations\DailyFileUploadLimit::instance();
		if (!$dailyLimiter->isUsed())
		{
			return true;
		}

		return $dailyLimiter->check($summarySizeBytes);
	}

	private function checkFieldsSize(array $fields, array $fieldsSizeMap): bool
	{
		foreach ($fieldsSizeMap as $fieldCode => $sizeBytes)
		{
			$field = array_filter(
				$fields,
				function ($value) use ($fieldCode) {
					return $value['CODE'] === $fieldCode;
				}
			);
			if (empty(array_values($field)[0]))
			{
				continue;
			}
			$field = array_values($field)[0];

			if (!isset($field['SETTINGS_DATA']['MAX_SIZE_MB']))
			{
				return true;
			}

			$maxFieldSizeBytes = intval($field['SETTINGS_DATA']['MAX_SIZE_MB']) * (1024 * 1024);
			if ($maxFieldSizeBytes <= 0)
			{
				return true;
			}

			if ($sizeBytes > $maxFieldSizeBytes)
			{
				return false;
			}
		}

		return true;
	}

	public function canView(): bool
	{
		return false;
	}

	public function verifyFileOwner(FileOwnershipCollection $files): void {}

	public function canRemove(): bool
	{
		return false;
	}

	public function getFingerprint(): string
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();

		return ($request->getUserAgent() ?? "") . Main\Service\GeoIp\Manager::getRealIp();
	}

	public static function getSettings(): array
	{
		$postMaxSize = \CUtil::unformat(ini_get('post_max_size'));
		$uploadMaxFileSize = \CUtil::unformat(ini_get('upload_max_filesize'));
		$maxFileSize = min($postMaxSize, $uploadMaxFileSize);

		$megabyte = 1024 * 1024;
		$chunkMaxSize = $maxFileSize;
		$chunkMinSize = $maxFileSize > $megabyte ? $megabyte : $maxFileSize;

		$cloud = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') && defined('BX24_HOST_NAME');
		if ($cloud)
		{
			$chunkMinSize = 5 * $megabyte;
			$chunkMaxSize = 100 * $megabyte;
		}

		$defaultChunkSize = 10 * $megabyte;
		$defaultChunkSize = min(max($chunkMinSize, $defaultChunkSize), $chunkMaxSize);

		return [
			'chunkMinSize' => $chunkMinSize,
			'defaultChunkSize' => $defaultChunkSize,
			'chunkMaxSize' => $chunkMaxSize,
		];
	}
}