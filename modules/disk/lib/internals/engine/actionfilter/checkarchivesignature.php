<?php

namespace Bitrix\Disk\Internals\Engine\ActionFilter;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Disk\Type\ObjectCollection;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CheckArchiveSignature extends ActionFilter\Base
{
	const ERROR_EMPTY_SIGNATURE   = 'empty_signature';
	const ERROR_INVALID_SIGNATURE = 'invalid_signature';

	/**
	 * @var array
	 */
	private $requestParameterNames;

	public function __construct(array $requestParameterNames = [
		'signature' => 'signature',
		'objectCollection' => 'objectCollection',
	])
	{
		parent::__construct();

		$this->requestParameterNames = $requestParameterNames;
	}

	public function onBeforeAction(Event $event)
	{
		$signature = Context::getCurrent()->getRequest()->get($this->requestParameterNames['signature']);
		$objectCollectionName = $this->requestParameterNames['objectCollection'];

		$objectCollection = null;
		foreach ($this->action->getArguments() as $name => $argument)
		{
			if ($name === $objectCollectionName)
			{
				/** @var ObjectCollection $objectCollection */
				$objectCollection = $argument;
			}
		}

		if (!$objectCollection)
		{
			$this->errorCollection[] = new Error('Could not find {objectCollection}');

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		if (!$signature)
		{
			$this->errorCollection[] = new Error(
				'Empty signature', self::ERROR_EMPTY_SIGNATURE
			);

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		if ($signature && !ParameterSigner::validateArchiveSignature($signature, $objectCollection->getIds()))
		{
			$this->errorCollection[] = new Error(
				'Invalid signature', self::ERROR_INVALID_SIGNATURE
			);

			return new EventResult(EventResult::ERROR, null, null, $this);
		}
	}
}