<?php

namespace Bitrix\Disk\Internals\Engine\ActionFilter;

use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CheckImageSignature extends ActionFilter\Base
{
	const ERROR_EMPTY_SIGNATURE   = 'empty_signature';
	const ERROR_INVALID_SIGNATURE = 'invalid_signature';

	/**
	 * @var array
	 */
	private $requestParameterNames;

	/**
	 * CheckImageSignature constructor.
	 *
	 * @param array $requestParameterNames
	 */
	public function __construct(array $requestParameterNames = [
		'signature' => 'signature',
		'width' => 'width',
		'height' => 'height'
	])
	{
		parent::__construct();

		$this->requestParameterNames = $requestParameterNames;
	}

	public function onBeforeAction(Event $event)
	{
		$signature = Context::getCurrent()->getRequest()->get($this->requestParameterNames['signature']);
		$width = (int)Context::getCurrent()->getRequest()->get($this->requestParameterNames['width']);
		$height = (int)Context::getCurrent()->getRequest()->get($this->requestParameterNames['height']);

		$fileId = $this->getFileIdFromActionArguments();
		if (!$signature && ($width > 0 || $height > 0))
		{
			$this->errorCollection[] = new Error(
				'Empty signature', self::ERROR_EMPTY_SIGNATURE
			);

			return new EventResult(EventResult::ERROR, null, null, $this);
		}
		elseif ($signature && !ParameterSigner::validateImageSignature($signature, $fileId, $width, $height))
		{
			$this->errorCollection[] = new Error(
				'Invalid signature', self::ERROR_INVALID_SIGNATURE
			);

			return new EventResult(EventResult::ERROR, null, null, $this);
		}
	}

	private function getFileIdFromActionArguments()
	{
		foreach ($this->action->getArguments() as $argument)
		{
			if ($argument instanceof File)
			{
				return $argument->getId();
			}
		}

		return null;
	}
}