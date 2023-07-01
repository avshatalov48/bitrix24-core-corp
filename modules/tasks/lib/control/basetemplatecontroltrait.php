<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use Bitrix\Tasks\Internals\Task\TemplateTable;


trait BaseTemplateControlTrait
{
	public function deleteByTemplate(): void
	{
		$class = $this->getTableClass();

		/** @var DataManager $class */
		$class::deleteList([
			'TEMPLATE_ID' => $this->templateId,
		]);
	}

	/**
	 * @throws TemplateNotFoundException
	 */
	public function loadTemplate(): void
	{
		if (isset($this->template))
		{
			return;
		}

		try
		{
			$this->template = TemplateTable::getByPrimary($this->templateId)->fetchObject();
		}
		catch (SystemException $exception)
		{
			throw new TemplateNotFoundException();
		}

		if (!$this->template)
		{
			throw new TemplateNotFoundException();
		}
	}
}