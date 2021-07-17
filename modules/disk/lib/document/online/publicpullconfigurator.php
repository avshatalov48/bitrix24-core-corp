<?php

namespace Bitrix\Disk\Document\Online;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Main\Errorable;
use Bitrix\Main\Loader;
use Bitrix\Pull;

class PublicPullConfigurator implements Errorable
{
	/** @var ErrorCollection */
	private $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();

		$this->init();
	}

	protected function init(): void
	{
		if (!defined('BX_PULL_SKIP_INIT'))
		{
			define("BX_PULL_SKIP_INIT", true);
		}
		elseif (BX_PULL_SKIP_INIT !== true)
		{
			$this->errorCollection->addOne(new Error('Could not set BX_PULL_SKIP_INIT to prevent default p&p configuration.'));
		}

		if (!Loader::includeModule('pull'))
		{
			$this->errorCollection->addOne(new Error('Could not work without "pull" module.'));
		}
	}

	public function getConfig(int $objectId): array
	{
		[
			'privateId' => $privateId,
			'publicId' => $publicId
		] = $this->getPublicChannelIdsByObjectId($objectId);

		$config = $this->getBaseConfigForGuest();
		$config['channels'] = [
			'private' => [
				'id' => \CPullChannel::SignChannel("$privateId:$publicId"),
				'public_id' => \CPullChannel::SignPublicChannel($publicId),
				'start' => date('c'),
				'end' => date('c', time() + 3600 * 24 * 365),
				'type' => 'private'
			]
		];

		$config['publicChannels'] = [
			'-1' => [
				'user_id' => -1,
				'public_id' => $publicId,
				'signature' => \CPullChannel::GetPublicSignature($publicId),
				'start' => date('c'),
				'end' => date('c', time() + 3600 * 24 * 365),
			]
		];

		return $config;
	}

	protected function getPublicChannelIdsByObjectId(int $objectId): array
	{
		$serverUniqID = \CMain::GetServerUniqID();
		$privateId = md5("DISK_FILE{$objectId}|{$serverUniqID}");
		$publicId = md5("DISK_FILE{$objectId}|{$serverUniqID}|PUB");

		return [
			'privateId' => $privateId,
			'publicId' => $publicId,
		];
	}

	protected function getBaseConfigForGuest(): ?array
	{
		$baseConfig = Pull\Config::get([
			'USER_ID' => -1,
			'JSON' => true
		]);

		if (!$baseConfig)
		{
			$baseConfig = null;
			$this->errorCollection->addOne(new Error('Could not get pull config.'));
		}

		return $baseConfig;
	}


	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}