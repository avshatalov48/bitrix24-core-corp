<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\Main\Application;
use Bitrix\Main\Context\Culture;
use Bitrix\Main\Localization\CultureTable;
use Bitrix\Main\Localization\Loc;

class Context
{
	protected $isCheckAccess;
	protected $region;
	protected $culture;
	protected $userId = 0;

	private int $templateId = 0;

	public function __construct()
	{}

	public static function createFromDocument(Document $document): Context
	{
		$context = new static();
		$context->setIsCheckAccess($document->getIsCheckAccess());
		$context->setUserId($document->getUserId());

		$template = $document->getTemplate();
		if ($template)
		{
			$context->setRegion($template->REGION);
			$context->setTemplateId($template->ID ?? 0);
		}

		return $context;
	}

	public function getIsCheckAccess(): bool
	{
		return ($this->isCheckAccess === true);
	}

	public function setIsCheckAccess(bool $isCheckAccess): Context
	{
		$this->isCheckAccess = $isCheckAccess;
		return $this;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function setUserId(int $userId): Context
	{
		$this->userId = $userId;

		return $this;
	}

	final public function getTemplateId(): int
	{
		return $this->templateId;
	}

	final public function setTemplateId(int $templateId): Context
	{
		$this->templateId = $templateId;

		return $this;
	}

	public function setRegion($region): Context
	{
		$this->region = $region;

		$culture = false;
		if (is_numeric($region) && $region > 0)
		{
			$regionData = Driver::getInstance()->getRegionsList()[$region];
			if ($regionData)
			{
				$culture = new Culture();
				$culture
					->setFormatDate($regionData['FORMAT_DATE'])
					->setFormatDatetime($regionData['FORMAT_DATETIME'])
					->setFormatName($regionData['FORMAT_NAME'])
					->setCharset('UTF-8')
				;
			}
		}
		elseif (is_string($region) && !empty($region))
		{
			$culture = CultureTable::getList(['filter' => ['=CODE' => $region]])->fetchObject();
		}

		if ($culture)
		{
			$this->culture = $culture;
		}

		return $this;
	}

	public function getRegion()
	{
		if (!$this->region)
		{
			return Loc::getCurrentLang();
		}

		return $this->region;
	}

	public function getRegionLanguageId(): string
	{
		if ($this->region)
		{
			$regionDescription = Driver::getInstance()->getRegionsList()[$this->region];
			if ($regionDescription && $regionDescription['LANGUAGE_ID'])
			{
				return $regionDescription['LANGUAGE_ID'];
			}
		}

		return Loc::getCurrentLang();
	}

	public function getCulture(): Culture
	{
		if (!$this->culture)
		{
			$this->culture = Application::getInstance()->getContext()->getCulture();
		}

		return $this->culture;
	}
}
