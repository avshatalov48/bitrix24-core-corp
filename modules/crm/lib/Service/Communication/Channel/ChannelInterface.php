<?php

namespace Bitrix\Crm\Service\Communication\Channel;

use Bitrix\Crm\Service\Communication\Channel\Property\PropertiesCollection;

interface ChannelInterface
{
	public static function createInstance(string $channelCode): self;
	public function getTitle(): string;
	public function isActive(): bool;
	public function getPropertiesCollection(): PropertiesCollection;
}
