<?php

namespace Bitrix\DocumentGenerator\Engine;

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Model\ExternalLinkTable;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CheckHash extends Base
{
	const WRONG_HASH = 'WRONG_HASH_ERROR';

	protected $document;
	protected $hash;

	public function onBeforeAction(Event $event)
	{
		foreach($this->action->getArguments() as $name => $argument)
		{
			if($argument instanceof Document)
			{
				$this->document = $argument;
			}
			elseif($name == 'hash')
			{
				$this->hash = $argument;
			}
		}

		if($this->document && $this->hash)
		{
			$link = ExternalLinkTable::getByHash($this->hash);
			if($link && $link['DOCUMENT_ID'] == $this->document->ID)
			{
				return null;
			}
		}

		$this->errorCollection[] = new Error(
			'Wrong hash', static::WRONG_HASH
		);
		return new EventResult(EventResult::ERROR, null, null, $this);
	}
}