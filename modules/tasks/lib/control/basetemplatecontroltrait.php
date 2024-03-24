<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
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

	public function getInsertIgnore(string $fields, string $values): string
	{
		$fields = ' ' . trim($fields) . ' ';
		$values = ' ' . trim($values) . ' ';
		$helper =  Application::getConnection()->getSqlHelper();

		/** @var DataManager $class */
		$class = $this->getTableClass();
		return $helper->getInsertIgnore($class::getTableName(), $fields, $values);
	}
}