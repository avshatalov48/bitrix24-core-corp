<?php

namespace Bitrix\Mobile\Component;

class SocNetFeatures
{
	private $arUserActiveFeatures;
	private $arSocNetFeaturesSettings;

	public function __construct(int $userId)
	{
		$this->arUserActiveFeatures = \CSocNetFeatures::getActiveFeatures(SONET_ENTITY_USER, $userId);
		$this->arSocNetFeaturesSettings = \CSocNetAllowed::getAllowedFeatures();
	}

	public function isEnabledForUser(string $feature): bool
	{
		return
			$this->isAllowed($feature)
			&& $this->userHave($feature);
	}

	public function isEnabledForGroup($feature): bool
	{
		return
			$this->isAllowed($feature)
			&& ( $this->userHave($feature) || $this->groupHave($feature))
			;
	}

	private function isAllowed($feature): bool
	{
		return array_key_exists($feature, $this->arSocNetFeaturesSettings)
			&& array_key_exists('allowed', $this->arSocNetFeaturesSettings[$feature])
			;
	}

	private function userHave($feature): bool
	{
		return in_array(SONET_ENTITY_USER, $this->arSocNetFeaturesSettings[$feature]['allowed'])
			&& is_array($this->arUserActiveFeatures)
			&& in_array($feature, $this->arUserActiveFeatures)
			;
	}

	private function groupHave($feature): bool
	{
		return in_array(SONET_ENTITY_GROUP, $this->arSocNetFeaturesSettings[$feature]['allowed']);
	}
}