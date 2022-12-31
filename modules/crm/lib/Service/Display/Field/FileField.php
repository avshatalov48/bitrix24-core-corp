<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Crm\UserField\FileViewer;
use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\File;

class FileField extends BaseLinkedEntitiesField
{
	public const TYPE = 'file';

	protected const VALUE_TYPE_FILE = 'file';
	protected const VALUE_TYPE_IMAGE = 'image';

	/** @var FileViewer */
	protected $fileViewer;

	public function loadLinkedEntities(array &$linkedEntitiesValues, array $linkedEntity): void
	{
		$linkedEntitiesId = $linkedEntity['ID'];
		$fieldType = $this->getType();
		$linkedEntitiesValues[$fieldType] = Container::getInstance()->getFileBroker()->getBunchByIds($linkedEntitiesId);
	}

	public function getFormattedValue($fieldValue, ?int $itemId = null, ?Options $displayOptions = null)
	{
		if ($displayOptions === null)
		{
			return '';
		}

		$fileViewer = new FileViewer(
			$displayOptions->getFileEntityTypeId() ?? $this->getEntityTypeId()
		);
		$this->setFileViewer($fileViewer);

		return parent::getFormattedValue($fieldValue, $itemId, $displayOptions);
	}

	/**
	 * @param FileViewer $fileViewer
	 * @return $this
	 */
	public function setFileViewer(FileViewer $fileViewer): FileField
	{
		$this->fileViewer = $fileViewer;
		return $this;
	}

	/**
	 * @param $fieldValue
	 * @param int $itemId
	 * @param Options $displayOptions
	 * @return array|string
	 */
	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		$this->setWasRenderedAsHtml(true);
		$displayParams = $this->getDisplayParams();
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();

		$results = [];
		$fieldValue = is_array($fieldValue) ? $fieldValue : [$fieldValue];
		foreach ($fieldValue as $fileId)
		{
			if (
				!isset($linkedEntitiesValues[$fileId])
				|| !is_array($linkedEntitiesValues[$fileId])
			)
			{
				continue;
			}
			$file = $linkedEntitiesValues[$fileId];

			if (
				isset($displayParams['VALUE_TYPE'])
				&& $displayParams['VALUE_TYPE'] === self::VALUE_TYPE_IMAGE
			)
			{
				$resizedFile = \CFile::ResizeImageGet(
					$file,
					[
						'width' => (int)($displayParams['IMAGE_WIDTH'] ?? 50),
						'height' => (int)($displayParams['IMAGE_HEIGHT'] ?? 50),
					],
					BX_RESIZE_IMAGE_PROPORTIONAL,
					true
				);

				if (!$this->isMultiple())
				{
					return \CFile::ShowImage([
						'SRC' => $resizedFile['src'],
						'WIDTH' => $resizedFile['width'],
						'HEIGHT' => $resizedFile['height'],
					]);
				}

				$results[] = \CFile::ShowImage([
					'SRC' => $resizedFile['src'],
					'WIDTH' => $resizedFile['width'],
					'HEIGHT' => $resizedFile['height'],
				]);
			}
			else
			{
				$fileUrl = $this->getFileUrl($file['ID'], $itemId, $displayOptions);
				$fileName = htmlspecialcharsbx($file['ORIGINAL_NAME'] ?? $file['FILE_NAME']);

				if (!$this->isMultiple())
				{
					return '<a href="' . htmlspecialcharsbx($fileUrl) . '" target="_blank">' . $fileName . '</a>';
				}

				$results[] = '<a href="' . htmlspecialcharsbx($fileUrl) . '" target="_blank">' . $fileName . '</a>';
			}
		}

		return $results;
	}

	protected function getFormattedValueForExport($fieldValue, int $itemId, Options $displayOptions): string
	{
		$results = [];
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();

		if (!$this->isMultiple())
		{
			return
				isset($linkedEntitiesValues[$fieldValue])
					? \CFile::GetFileSRC($linkedEntitiesValues[$fieldValue])
					: ''
			;
		}

		foreach ($fieldValue as $fileId)
		{
			if (isset($linkedEntitiesValues[$fileId]))
			{
				$results[] = \CFile::GetFileSRC($linkedEntitiesValues[$fileId]);
			}
		}

		return implode($displayOptions->getMultipleFieldsDelimiter(), $results);
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		if (!Loader::includeModule('mobile'))
		{
			return [];
		}

		$fileInfo = [];
		$value = [];

		$isMultiple = $this->isMultiple();
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();
		$displayParams = $this->getDisplayParams();
		$isImage = isset($displayParams['VALUE_TYPE']) && $displayParams['VALUE_TYPE'] === self::VALUE_TYPE_IMAGE;

		foreach ((array)$fieldValue as $fileId)
		{
			if (!empty($fileInfo) && !$isMultiple)
			{
				break;
			}

			if (!isset($linkedEntitiesValues[$fileId]) || !is_array($linkedEntitiesValues[$fileId]))
			{
				continue;
			}

			if ($isImage)
			{
				$fileInfo[$fileId] = File::loadWithPreview($fileId);
			}
			else
			{
				$fileInfo[$fileId] = $this->getPreparedFileForMobile((int)$fileId, $itemId, $displayOptions);
			}

			$value[] = $fileId;
		}

		return [
			'config' => [
				'module' => 'crm',
				'fileInfo' => $fileInfo,
				'mediaType' => $isImage ? self::VALUE_TYPE_IMAGE : self::VALUE_TYPE_FILE,
			],
			'value' => $value,
		];
	}

	/**
	 * @param int $fileId
	 * @param int $itemId
	 * @param Options $displayOptions
	 * @return array
	 */
	protected function getPreparedFileForMobile(int $fileId, int $itemId, Options $displayOptions): array
	{
		if ($file = File::loadWithPreview($fileId))
		{
			$url = $this->getFileUrl($fileId, $itemId, $displayOptions);

			return array_merge(
				$file->getInfo(),
				['url' => $url]
			);
		}

		return [];
	}

	/**
	 * @param int $fileId
	 * @param int $itemId
	 * @param Options $displayOptions
	 * @return string
	 */
	protected function getFileUrl(int $fileId, int $itemId, Options $displayOptions): string
	{
		$fileUrlTemplate = ($displayOptions->getFileUrlTemplate() ?? '');
		if ($fileUrlTemplate !== '')
		{
			return \CComponentEngine::MakePathFromTemplate(
				$fileUrlTemplate,
				[
					'owner_id' => $itemId,
					'field_name' => $this->getId(),
					'file_id' => $fileId,
				]
			);
		}

		return $this->fileViewer->getUrl($itemId, $this->getId(), $fileId);
	}
}
