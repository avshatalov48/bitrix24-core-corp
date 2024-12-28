<?php

namespace Bitrix\Sign;

final class FeatureResolver
{
	/** @var list<string> */
	private const FEATURE_CODES = [
		'createDocumentChat',
		'sendByEmployee',
		'mobileSendByEmployee',
		'mobileMyDocumentsGrid',
	];
	private static self $instance;

	private function __construct()
	{
	}

	public static function instance(): self
	{
		self::$instance ??= new self();

		return self::$instance;
	}

	public function released(string $code): bool
	{
		return in_array($code, self::FEATURE_CODES, true);
	}

	/**
	 * @return list<string>
	 */
	public function getCodes(): array
	{
		return self::FEATURE_CODES;
	}
}