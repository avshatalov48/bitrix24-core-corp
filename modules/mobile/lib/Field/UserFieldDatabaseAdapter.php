<?php

namespace Bitrix\Mobile\Field;

use Bitrix\Crm\Integration\UI\EntitySelector\DynamicMultipleProvider;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\FileInputUtility;
use Bitrix\Mobile\AppTabs\Crm;
use Bitrix\Mobile\Field\Type\AddressField;
use Bitrix\Mobile\Field\Type\CrmField;
use Bitrix\Mobile\Field\Type\CrmStatusField;
use Bitrix\Mobile\Field\Type\DateField;
use Bitrix\Mobile\Field\Type\DateTimeField;
use Bitrix\Mobile\Field\Type\EnumerationField;
use Bitrix\Mobile\Field\Type\FileField;
use Bitrix\Mobile\Field\Type\IblockElementField;
use Bitrix\Mobile\Field\Type\IblockSectionField;
use Bitrix\Mobile\Field\Type\MoneyField;
use Bitrix\Mobile\Field\Type\UserField;
use Bitrix\UI\FileUploader\PendingFileCollection;
use Bitrix\UI\FileUploader\Uploader;

final class UserFieldDatabaseAdapter
{
	private ?string $uploaderControllerClass = '';

	/**
	 * @param string $uploaderControllerClass
	 * @return $this
	 */
	public function setUploaderControllerClass(string $uploaderControllerClass): self
	{
		$this->uploaderControllerClass = $uploaderControllerClass;
		return $this;
	}

	/**
	 * @param array $fieldDescription
	 * @param $value
	 * @return mixed
	 */
	public function getAdaptedUserFieldValue(array $fieldDescription, $value)
	{
		$isMultiple = $fieldDescription['MULTIPLE'] === 'Y';
		if ($isMultiple && (empty($value)))
		{
			return [];
		}

		if ($value === '' || $value === false || !isset($value))
		{
			return null;
		}

		$result = $value;

		$type = $fieldDescription['USER_TYPE_ID'];
		if (!$type)
		{
			return $result;
		}

		if ($isMultiple)
		{
			$result = $this->prepareMultipleField($fieldDescription, $result);
		}

		if ($type === MoneyField::TYPE)
		{
			$result = $this->prepareMoneyField($fieldDescription, $result);
		}
		elseif ($type === DateField::TYPE || $type === DateTimeField::TYPE)
		{
			$result = $this->prepareDateTimeField($fieldDescription, $result);
		}
		elseif ($type === AddressField::TYPE)
		{
			$result = $this->prepareAddressField($fieldDescription, $result);
		}
		elseif ($type === FileField::TYPE)
		{
			$result = $this->prepareFileField($fieldDescription, $result);
		}
		elseif ($type === CrmField::TYPE)
		{
			$result = $this->prepareCrmField($fieldDescription, $result);
		}

		return $result;
	}

	/**
	 * @param array $fieldDescription
	 * @param $data
	 * @return array|mixed
	 */
	private function prepareMultipleField(array $fieldDescription, $data)
	{
		if (
			$fieldDescription['USER_TYPE_ID'] === FileField::TYPE
			|| $fieldDescription['USER_TYPE_ID'] === EnumerationField::TYPE
			|| $fieldDescription['USER_TYPE_ID'] === CrmField::TYPE
			|| $fieldDescription['USER_TYPE_ID'] === CrmStatusField::TYPE
			|| $fieldDescription['USER_TYPE_ID'] === IblockElementField::TYPE
			|| $fieldDescription['USER_TYPE_ID'] === IblockSectionField::TYPE
			|| $fieldDescription['USER_TYPE_ID'] === UserField::TYPE
		)
		{
			return $data;
		}

		$result = [];

		if (!empty($data) && is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (isset($value['value']))
				{
					$result[$key] = $value['value'];
				}
			}
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	/**
	 * @param array $fieldDescription
	 * @param $data
	 * @return array|string|null
	 */
	private function prepareMoneyField(array $fieldDescription, $data)
	{
		$result = [];
		$isMultiple = $fieldDescription['MULTIPLE'] === 'Y';
		if ($isMultiple)
		{
			if (!empty($data) && is_array($data))
			{
				foreach ($data as $key => $value)
				{
					if (is_array($value) && is_numeric($value['amount']))
					{
						$result[$key] = "{$value['amount']}|{$value['currency']}";
					}
				}
			}
		}
		elseif (is_array($data) && is_numeric($data['amount']))
		{
			$result = "{$data['amount']}|{$data['currency']}";
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	/**
	 * @param array $fieldDescription
	 * @param $data
	 * @return array|Date|DateTime
	 */
	private function prepareDateTimeField(array $fieldDescription, $data)
	{
		$createFromTimestamp = static fn ($timestamp) => (
		$fieldDescription['USER_TYPE_ID'] === DateTimeField::TYPE
			? DateTime::createFromTimestamp($timestamp)
			: Date::createFromTimestamp($timestamp)
		);

		$useTimezone = ($fieldDescription['SETTINGS']['USE_TIMEZONE'] ?? 'Y') === 'Y';

		$result = [];
		$isMultiple = $fieldDescription['MULTIPLE'] === 'Y';
		if ($isMultiple)
		{
			if (!empty($data) && is_array($data))
			{
				foreach ($data as $key => $value)
				{
					if (!empty($value))
					{
						$result[$key] = $createFromTimestamp($value);

						if (!$useTimezone)
						{
							$result[$key] = $result[$key]->toUserTime();
						}
					}
				}
			}
		}
		else
		{
			$result = $createFromTimestamp($data);

			if (!$useTimezone)
			{
				$result = $result->toUserTime();
			}
		}

		return $result;
	}

	private function prepareCrmField(array $fieldDescription, $data)
	{
		$preparedData = $data;
		$isMultiple = $fieldDescription['MULTIPLE'] === 'Y';

		if (!empty($preparedData) && is_array($preparedData))
		{
			foreach ($preparedData as $key => $value)
			{
				if (!empty($value) && is_array($value))
				{
					[$entityTypeName, $entityId] = $value;

					if ($entityTypeName === DynamicMultipleProvider::DYNAMIC_MULTIPLE_ID)
					{
						[$entityTypeId, $entityId] = DynamicMultipleProvider::parseId($entityId);
						$entityTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
					}
					else
					{
						$entityTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeName($entityTypeName);
					}

					if ($entityTypeAbbr)
					{
						$preparedData[$key] = "{$entityTypeAbbr}_{$entityId}";
					}
				}
				else
				{
					unset($preparedData[$key]);
				}
			}

			if (!$isMultiple)
			{
				$preparedData = $preparedData[0] ?? null;
			}
		}
		else
		{
			$preparedData = $isMultiple ? [] : null;
		}

		return $preparedData;
	}

	/**
	 * @param array $fieldDescription
	 * @param $data
	 * @return array|mixed|null
	 */
	private function prepareFileField(array $fieldDescription, $data)
	{
		if (!$this->uploaderControllerClass || !$data)
		{
			return null;
		}

		$value = $data;

		$isMultiple = $fieldDescription['MULTIPLE'] === 'Y';
		if (!$isMultiple)
		{
			$value = [$value];
		}

		$fileIds = array_filter($value, static fn ($file) => $file && !is_array($file));
		$filesToSave = array_filter($value, static fn ($file) => is_array($file) && !empty($file['token']));

		$pendingFiles = null;
		if (!empty($filesToSave))
		{
			$tokens = array_column($filesToSave, 'token');
			$fieldName = $fieldDescription['FIELD_NAME'];
			$pendingFiles = $this->getPendingFilesForField($tokens, $fieldName);
			$fileIds = array_merge($fileIds, $pendingFiles->getFileIds());
		}

		foreach ($fileIds as $fileId)
		{
			$this->registerFileId($fieldDescription, $fileId);
		}

		if (!$isMultiple)
		{
			$result = $fileIds[0];
		}
		else
		{
			$result = $fileIds;
		}

		if ($pendingFiles)
		{
			$pendingFiles->makePersistent();
		}

		return $result;
	}

	private function prepareAddressField(array $fieldDescription, $data)
	{
		$result = [];
		$isMultiple = $fieldDescription['MULTIPLE'] === 'Y';
		if ($isMultiple)
		{
			if (!empty($data) && is_array($data))
			{
				foreach ($data as $key => $value)
				{
					if (!empty($value) && is_array($value))
					{
						$formattedValue = "{$value[0]}|{$value[1][0]};{$value[1][1]}";
						if ($value[2])
						{
							$formattedValue .= "|{$value[2]}";
						}
						$result[$key] = $formattedValue;
					}
				}
			}
		}
		elseif (!empty($data) && is_array($data) && !is_null($data[0]))
		{
			$formattedValue = "{$data[0]}|{$data[1][0]};{$data[1][1]}";
			if ($data[2])
			{
				$formattedValue .= "|{$data[2]}";
			}
			$result = $formattedValue;
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	/**
	 * @param array $tokens
	 * @param string $fieldName
	 * @return PendingFileCollection
	 */
	private function getPendingFilesForField(array $tokens, string $fieldName): PendingFileCollection
	{
		$fileController = new $this->uploaderControllerClass([
			'fieldName' => $fieldName,
		]);

		return (new Uploader($fileController))->getPendingFiles($tokens);
	}

	/**
	 * @param $field
	 * @param $fileId
	 * @return void
	 */
	private function registerFileId($field, $fileId): void
	{
		$fileInputUtility = FileInputUtility::instance();
		$controlId = $fileInputUtility->getUserFieldCid($field);

		$fileInputUtility->registerControl($controlId, $controlId);
		$fileInputUtility->registerFile($controlId, $fileId);
	}
}
