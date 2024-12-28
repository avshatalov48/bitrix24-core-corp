<?php

namespace Bitrix\Disk\Document\Online;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Realtime;
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

	public function getChannel(int $objectId): Pull\Model\Channel
	{
		return (new Realtime\Channels\ObjectChannel($objectId))->getPullModel();
	}

	public function getConfig(int $objectId): array
	{
		$channel = $this->getChannel($objectId);

		$config = Pull\Config::get([
			'CHANNEL' => $channel,
			'JSON' => true
		]);

		return $config ? : [];
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