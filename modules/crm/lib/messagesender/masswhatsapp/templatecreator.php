<?php

namespace Bitrix\Crm\MessageSender\MassWhatsApp;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;

class TemplateCreator
{
	use Singleton;

	public function prepareTemplate(
		ItemIdentifier $identifier,
		string $messageBody,
		?string $messageTemplateCode
	): TplCreatorResult
	{
		if (!Loader::includeModule('documentgenerator'))
		{
			return new TplCreatorResult();
		}

		$documentGeneratorManager = DocumentGeneratorManager::getInstance();
		$htmlMessageBody = $documentGeneratorManager->replacePlaceholdersInText(
			$identifier->getEntityTypeId(),
			$identifier->getEntityId(),
			$messageBody,
			' '
		);

		$messageBody = html_entity_decode(
			preg_replace('/<br\/?>/i', PHP_EOL, $htmlMessageBody),
			ENT_NOQUOTES | ENT_HTML401
		);

		if (is_string($messageTemplateCode))
		{
			$data = Json::decode($messageTemplateCode);
			$data['text'] = $messageBody;
			$messageTemplateCode = Json::encode($data, 0);
		}

		return new TplCreatorResult($messageBody, $messageTemplateCode);
	}
}
