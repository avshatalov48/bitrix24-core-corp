<?php

namespace Bitrix\Sign\Ui\ViewModel\Wizard;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\Document\SignUntilService;

final class TemplateDocument implements Arrayable
{
	private \Bitrix\Sign\Item\Document $document;
	private readonly SignUntilService $signUntilService;

	public function __construct(\Bitrix\Sign\Item\Document $document)
	{
		$this->document = $document;
		$this->signUntilService = Container::instance()->getSignUntilService();
	}

	public function toArray(): array
	{
		$viewData = (new Document($this->document))->toArray();

		$viewData['dateSignUntilUserTime'] = $this->signUntilService
			->calcDefaultSignUntilDate(new Main\Type\DateTime())
			->toUserTime()
			->format('Y-m-d H:i:s')
		;

		return $viewData;
	}
}
