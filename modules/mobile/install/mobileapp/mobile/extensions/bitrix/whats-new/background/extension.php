<?php

use Bitrix\Mobile\Config\Feature;
use Bitrix\Mobile\Feature\WhatsNewFeature;

return [
	'isWhatsNewFeatureEnabled' => Feature::isEnabled(WhatsNewFeature::class),
];
