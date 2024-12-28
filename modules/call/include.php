<?php

\CModule::AddAutoloadClasses(
	'call',
	[
		'call' => 'install/index.php',
	]
);

if (\Bitrix\Call\Integration\AI\CallAISettings::isDebugEnable())
{
	\Bitrix\Call\Integration\AI\ChatEventLog::registerHandlers();
}
