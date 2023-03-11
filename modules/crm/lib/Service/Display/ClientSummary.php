<?php

namespace Bitrix\Crm\Service\Display;

use Bitrix\Main\Web\Uri;

class ClientSummary
{
	private const DEFAULT_PHOTO_SIZE = 50;

	protected $entityTypeId;
	protected $entityId;
	protected $url = '';
	protected $title = '';
	protected $description = '';
	protected $withTracking = false;
	protected $photoFileId;

	public function __construct(int $entityTypeId, int $entityId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;
	}

	public function withUrl(string $url): self
	{
		$this->url = htmlspecialcharsbx($url);

		return $this;
	}

	public function withTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function withDescription(string $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function withTracking(bool $withTracking): self
	{
		$this->withTracking = $withTracking;

		return $this;
	}

	public function withPhoto(?int $photoFileId): self
	{
		$this->photoFileId = $photoFileId;

		return $this;
	}

	public function render(): string
	{
		return <<<HTML
			<div class="crm-client-summary-wrapper">
				<div class="crm-client-photo-wrapper">{$this->renderPhoto()}</div>
				<div class="crm-client-info-wrapper">{$this->renderTitle()}{$this->renderDescription()}</div>
			</div>
		HTML;
	}

	private function renderPhoto(): string
	{
		$entityDependantIcon =
			$this->entityTypeId === \CCrmOwnerType::Company
				? 'ui-icon-common-company'
				: 'ui-icon-common-user';

		$style = '';
		if ($this->photoFileId)
		{
			$fileData = \CFile::GetFileArray($this->photoFileId);
			$originalWidth = $fileData['WIDTH'];
			$originalHeight = $fileData['HEIGHT'];

			$resizeWidth = static::DEFAULT_PHOTO_SIZE;
			$resizeHeight = static::DEFAULT_PHOTO_SIZE;

			$needResizeByShortestDimension = true;

			$ratio = $originalHeight > 0 ? ($originalWidth / $originalHeight) : 0;
			if ($ratio > 2 || $ratio < 0.5)
			{
				$needResizeByShortestDimension = false;
			}

			if ($needResizeByShortestDimension && $originalWidth > $originalHeight)
			{
				$resizeHeight = static::DEFAULT_PHOTO_SIZE;
				$resizeWidth = $originalWidth * static::DEFAULT_PHOTO_SIZE / $originalHeight;
			}
			elseif ($needResizeByShortestDimension && $originalHeight > $originalWidth)
			{
				$resizeHeight = $originalHeight * static::DEFAULT_PHOTO_SIZE / $originalWidth;
				$resizeWidth = static::DEFAULT_PHOTO_SIZE;
			}

			$resizedFile = \CFile::ResizeImageGet(
				$this->photoFileId,
				[
					'width' => $resizeWidth,
					'height' => $resizeHeight,
				],
				BX_RESIZE_IMAGE_PROPORTIONAL,
				true,
				false,
				true
			);
			if ($resizedFile)
			{
				$styles = [];
				if ($needResizeByShortestDimension)
				{
					$styles[] = 'background-image: url(\'' . Uri::urnEncode(htmlspecialcharsbx($resizedFile['src'])) . '\')';
				}
				else
				{
					$backgroundFile = \CFile::ResizeImageGet(
						$this->photoFileId,
						[
							'width' => 1,
							'height' => 1,
						],
						BX_RESIZE_IMAGE_EXACT,
						true,
						false,
						true
					);
					$styles[] = 'background-image: url(\'' . Uri::urnEncode(htmlspecialcharsbx($resizedFile['src'])) . '\'), url(\'' . Uri::urnEncode(htmlspecialcharsbx($backgroundFile['src'])) . '\')';
					$styles[] = 'background-size: contain, cover';
				}

				$style = ' style="' . implode(';', $styles) . ';"';
			}
		}

		return '<div class="ui-icon crm-client-summary-photo ' . $entityDependantIcon . '"><i' . $style . '></i></div>';
	}

	private function renderTitle(): string
	{
		if ($this->url === '' && $this->title === '')
		{
			return '';
		}

		$title = $this->title !== '' ? $this->title : $this->url;

		$titleContent =
			$this->url === ''
			? $this->title
			: '<a target="_top" href="'.$this->url.'">' . $title . '</a>'
		;

		return '<div class="crm-client-title-wrapper">' . $titleContent . '</div>';
	}

	private function renderDescription(): string
	{
		if ($this->withTracking)
		{
			return \Bitrix\Crm\Tracking\UI\Grid::enrichSourceName(
				$this->entityTypeId,
				$this->entityId,
				$this->description
			);
		}

		return $this->description;
	}
}
