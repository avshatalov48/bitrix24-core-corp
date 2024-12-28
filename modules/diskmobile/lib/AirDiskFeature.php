<?php

declare(strict_types=1);

namespace Bitrix\DiskMobile;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Component\SocNetFeatures;
use Bitrix\Mobile\Config\FeatureFlag;
use Bitrix\Socialnetwork\Collab\CollabFeature;

final class AirDiskFeature extends FeatureFlag
{
	public const MINIMAL_API_VERSION = 55;

	public function isEnabled(): bool
	{
		return $this->featureGenerallyEnabled()
			&& $this->diskConverted()
			&& $this->featureEnabledForCurrentUser()
			&& $this->clientHasApiVersion(self::MINIMAL_API_VERSION);
	}

	private function featureGenerallyEnabled(): bool
	{
		return (
			Loader::includeModule('socialnetwork')
			&& class_exists(CollabFeature::class)
			&& CollabFeature::isOn()
		);
	}

	private function diskConverted(): bool
	{
		return (bool)Option::get('disk', 'successfully_converted', false);
	}

	private function featureEnabledForCurrentUser(): bool
	{
		return Loader::includeModule('socialnetwork')
			&& (new SocNetFeatures((int)$this->getCurrentUserId()))->isEnabledForUser('files');
	}

	public function enable(): void
	{}

	public function disable(): void
	{}
}
