<?php

namespace Bitrix\Sign\Agent\Converter;

use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Document\SchemeType;
use Bitrix\Sign\Type\ProviderCode;

final class ConvertProviderSchemesAgent
{
	public static function getName(): string
	{
		return self::class . '::run();';
	}

	public static function run(): string
	{
		$documentRepository = Container::instance()->getDocumentRepository();
		$sesRuDocumentIds = $documentRepository->listIdsByProviderCodeAndScheme(
			ProviderCode::SES_RU,
			SchemeType::DEFAULT,
			100,
		);
		if (empty($sesRuDocumentIds))
		{
			return '';
		}

		$result = $documentRepository->updateSchemeToDocumentIds($sesRuDocumentIds, SchemeType::ORDER);
		if (!$result->isSuccess())
		{
			$logger = Logger::getInstance();
			$logger->error(
				'Failed to update schemes by converter agent. Errors: {errorText}',
				['errorText' => implode('| ', $result->getErrorMessages())],
			);

			return self::getName();
		}

		return self::getName();
	}
}