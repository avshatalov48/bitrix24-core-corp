<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Crm\UserField\FileViewer;

class FileField extends BaseLinkedEntitiesField
{
	protected const TYPE = 'file';

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
		$fileUrlTemplate = ($displayOptions->getFileUrlTemplate() ?? '');
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

			if ($fileUrlTemplate !== '')
			{
				$fileUrl = \CComponentEngine::MakePathFromTemplate(
					$fileUrlTemplate,
					[
						'owner_id' => $itemId,
						'field_name' => $this->getId(),
						'file_id' => $file['ID'],
					]
				);
			}
			else
			{
				$fileUrl = $this->fileViewer->getUrl($itemId, $this->getId(), $file['ID']);
			}

			if (
				isset($displayParams['VALUE_TYPE'])
				&& $displayParams['VALUE_TYPE'] === \Bitrix\Crm\Field::VALUE_TYPE_IMAGE
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
}
