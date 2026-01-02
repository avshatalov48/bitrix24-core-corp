<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Template;

use Bitrix\Main\Error;
use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;

class DeleteTemplateCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $templateId,
		public readonly int $deletedBy,
	)
	{

	}

	protected function executeInternal(): Result
	{
		try
		{
			$handler = new DeleteTemplateHandler(Container::getInstance()->getTemplateRepository());

			$handler($this);

			return new Result();
		}
		catch (TemplateNotFoundException $taskException)
		{
			return (new Result())->addError(new Error('Failed deleting template.'));
		}
	}
}
