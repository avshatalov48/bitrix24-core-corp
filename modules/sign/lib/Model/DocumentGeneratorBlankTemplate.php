<?php

namespace Bitrix\Sign\Model;

class DocumentGeneratorBlankTemplate
{
	private string $enabled;
	private int $blankId;
	private int $templateId;
	private string $yourSide;

	/**
	 * @param int $blankId
	 * @param int $templateId
	 * @param string $yourSide
	 * @param string $enabled
	 */
	public function __construct(int $blankId, int $templateId, string $yourSide = '', string $enabled = 'Y')
	{
		$this->enabled = $enabled;
		$this->blankId = $blankId;
		$this->templateId = $templateId;
		$this->yourSide = $yourSide;
	}

	/**
	 * @return string
	 */
	public function getEnabled(): string
	{
		return $this->enabled;
	}

	/**
	 * @return int
	 */
	public function getBlankId(): int
	{
		return $this->blankId;
	}

	/**
	 * @return int
	 */
	public function getTemplateId(): int
	{
		return $this->templateId;
	}

	/**
	 * @return string
	 */
	public function getYourSide(): string
	{
		return $this->yourSide;
	}
}