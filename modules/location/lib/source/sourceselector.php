<?php

namespace Bitrix\Location\Source;

use Bitrix\Location\Entity\Source\Factory;
use Bitrix\Location\Entity\Source\OrmConverter;
use Bitrix\Location\Repository\SourceRepository;
use Bitrix\Location\Source\Google;
use Bitrix\Location\Source\Osm;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class SourceSelector
{
	protected $sourceRepository;

	public function __construct(SourceRepository $sourceRepository = null)
	{
		$this->sourceRepository = $sourceRepository ?: new SourceRepository(new OrmConverter());
	}

	protected function isConstantDefinesOtherSource(string $sourceCode): bool
	{
		$result = false;

		if (defined('LOCATION_DEFAULT_SOURCE_CODE') && $sourceCode !== LOCATION_DEFAULT_SOURCE_CODE)
		{
			$result = true;
		}

		return $result;
	}

	public function setSource(string $sourceCode, IConfigFactory $configFactory): Result
	{
		$result = new Result();

		if($this->isConstantDefinesOtherSource($sourceCode))
		{
			$result->addError(
				new Error('Defined constant "LOCATION_DEFAULT_SOURCE_CODE" with other source')
			);

			return $result;
		}

		$source = $this->sourceRepository->findByCode($sourceCode);

		if (!$source)
		{
			$source = Factory::makeSource($sourceCode);
			$source->setName($sourceCode);
		}

		$source->setConfig(
			$configFactory->createConfig()
		);

		$res = $this->sourceRepository->save($source);

		if(!$res->isSuccess())
		{
			$result->addErrors($res->getErrors());
		}

		Option::set('location', 'location_default_source_code', $sourceCode);

		return $result;
	}

	public static function setGoogleSource(string $frontendKey, string $backendKey): Result
	{
		$sourceSelector = new self();
		$configFactory = new Google\ConfigFactory($frontendKey, $backendKey);
		return $sourceSelector->setSource(Factory::GOOGLE_SOURCE_CODE, $configFactory);
	}

	public static function setOsmSource(string $serviceUrl, string $token): Result
	{
		$sourceSelector = new self();
		$configFactory = new Osm\ConfigFactory($serviceUrl, $token);
		return $sourceSelector->setSource(Factory::OSM_SOURCE_CODE, $configFactory);
	}
}