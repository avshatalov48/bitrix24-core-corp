<?php
namespace Bitrix\BIConnector;

class Service
{
	protected $manager = null;
	protected $languageMap = null;
	protected $languageId = 'en';

	public function __construct(Manager $manager)
	{
		$this->manager = $manager;
	}

	public function setLanguage($languageId)
	{
		$this->languageId = \Bitrix\Main\Localization\Loc::getCurrentLang();
		$dbLanguage = \Bitrix\Main\Localization\LanguageTable::getList([
			'select' => ['LID'],
			'filter' => [
				'=LID' => $languageId,
				'=ACTIVE' => 'Y'
			],
		])->fetch();
		if ($dbLanguage)
		{
			$this->languageId = $dbLanguage['LID'];
		}
	}

	public function getLanguage()
	{
		return $this->languageId;
	}

	public function validateDashboardUrl($url)
	{
		return false;
	}
}
