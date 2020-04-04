<?php

namespace Bitrix\Landing\Assets;

use Bitrix\Main\Localization\Loc;

class StandartBuilder extends Builder
{
	/**
	 * Add assets output at the page
	 */
	public function setOutput()
	{
		if($this->resources->isEmpty())
		{
			return;
		}
		
		$this->normalizeResources();
		$this->initResourcesAsJsExtension($this->normalizedResources);
		
		$this->setStrings();
	}
	
	protected function normalizeResources()
	{
		$this->normalizedResources = $this->resources->getNormalized();
		$this->normalizeLangResources();
	}
	
	protected function normalizeLangResources()
	{
		$langResources = $this->normalizedResources[Types::TYPE_LANG];
		if (isset($langResources) && !empty($langResources))
		{
//			convert array to string (get first element)
			$this->normalizedResources[Types::TYPE_LANG] = $this->normalizedResources[Types::TYPE_LANG][0];

//			other files load by additional lang
			if ($additionalLang = self::loadAdditionalLangPhrases(array_slice($langResources, 1)))
			{
				$this->normalizedResources[Types::TYPE_LANG_ADDITIONAL] = $additionalLang;
			}
		}
	}
	
	protected static function loadAdditionalLangPhrases($langResources)
	{
		$additionalLangPhrases = [];
		
		if (!empty($langResources))
		{
			foreach ($langResources as $file)
			{
				$additionalLangPhrases = array_merge(
					$additionalLangPhrases,
					Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . $file)
				);
			}
		}
		
		return $additionalLangPhrases;
	}
}