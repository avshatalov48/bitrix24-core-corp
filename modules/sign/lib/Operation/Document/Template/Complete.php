<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Template\Status;

final class Complete implements Contract\Operation
{
	private readonly TemplateRepository $templateRepository;

	public function __construct(
		private readonly Item\Document\Template $template,
		?TemplateRepository $templateRepository = null,
	)
	{
		$this->templateRepository = $templateRepository ?? Container::instance()->getDocumentTemplateRepository();
	}

	public function launch(): Main\Result
	{
		if ($this->template->id === null)
		{
			return (new Main\Result())->addError(new Main\Error('Template is not saved'));
		}
		if ($this->template->status === Status::COMPLETED)
		{
			return new Main\Result();
		}

		$this->template->status = Status::COMPLETED;

		return $this->templateRepository->update($this->template);
	}
}