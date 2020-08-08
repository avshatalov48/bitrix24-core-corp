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

	public function __construct()
	{

	}

	/**
	 * @param Document $document
	 * @return Context
	 */
	public static function createFromDocument(Document $document): Context
	{
		$context = new static();
		$context->setIsCheckAccess($document->getIsCheckAccess());

		$template = $document->getTemplate();
		if($template)
		{
			$context->setRegion($template->REGION);
		}

		return $context;
	}

	/**
	 * @return bool
	 */
	public function getIsCheckAccess(): bool
	{
		return ($this->isCheckAccess === true);
	}

	/**
	 * @param bool $isCheckAccess
	 * @return Context
	 */
	public function setIsCheckAccess(bool $isCheckAccess): Context
	{
		$this->isCheckAccess = $isCheckAccess;
		return $this;
	}

	/**
	 * @param mixed $region
	 * @return Context
	 */
	public function setRegion($region): Context
	{
		$this->region = $region;
		$culture = false;
		if(is_numeric($region) && $region > 0)
		{
			$regionData = Driver::getInstance()->getRegionsList()[$region];
			if($regionData)
			{
				$culture = new Culture();
				$culture->setFormatDate($regionData['FORMAT_DATE'])
					->setFormatDatetime($regionData['FORMAT_DATETIME'])
					->setFormatName($regionData['FORMAT_NAME'])
					->setCharset('UTF-8');
			}
		}
		elseif(is_string($region) && !empty($region))
		{
			$culture = CultureTable::getList(['filter' => ['=CODE' => $region]])->fetchObject();
		}

		if($culture)
		{
			$this->culture = $culture;
		}

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getRegion()
	{
		if(!$this->region)
		{
			return Loc::getCurrentLang();
		}
		return $this->region;
	}

	/**
	 * @return string
	 */
	public function getRegionLanguageId(): string
	{
		if($this->region)
		{
			$regionDescription = Driver::getInstance()->getRegionsList()[$this->region];
			if($regionDescription && $regionDescription['LANGUAGE_ID'])
			{
				return $regionDescription['LANGUAGE_ID'];
			}
		}

		return Loc::getCurrentLang();
	}

	/**
	 * @return Culture
	 */
	public function getCulture(): Culture
	{
		if(!$this->culture)
		{
			$this->culture = Application::getInstance()->getContext()->getCulture();
		}
		return $this->culture;
	}
}