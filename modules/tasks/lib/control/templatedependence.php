<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateTagTable;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use \Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;

class TemplateDependence
{
	private const FIELD_DEPEND = 'DEPEND_ON';

	private $userId;
	private $templateId;

	/* @var \Bitrix\Tasks\Internals\Task\Template\TemplateObject $template */
	private $template;

	public function __construct(int $userId, int $templateId)
	{
		$this->userId = $userId;
		$this->templateId = $templateId;
	}

	/**
	 * @param array $data
	 * @return void
	 * @throws TemplateNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function set(array $data)
	{
		if (
			!array_key_exists(self::FIELD_DEPEND, $data)
			|| !is_array($data[self::FIELD_DEPEND])
		)
		{
			return;
		}

		$this->loadTemplate();
		$this->deleteByTemplate();

		if (empty($data[self::FIELD_DEPEND]))
		{
			return;
		}

		$depends = array_values($data[self::FIELD_DEPEND]);

		if (empty($depends))
		{
			return;
		}

		$insertRows = [];
		foreach ($depends as $depend)
		{
			if ((int) $depend < 1)
			{
				continue;
			}
			$insertRows = '('.$this->templateId.', '. $depend .')';
		}

		if (empty($insertRows))
		{
			return;
		}

		$sql = "
			INSERT IGNORE INTO ". TemplateDependenceTable::getTableName() ."
			(`TEMPLATE_ID`, `DEPENDS_ON_ID`)
			VALUES
			". implode(", ", $insertRows) ."
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @return void
	 * @throws TemplateNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function loadTemplate(): void
	{
		if ($this->template)
		{
			return;
		}

		$this->template = TemplateTable::getByPrimary($this->templateId)->fetchObject();
		if (!$this->template)
		{
			throw new TemplateNotFoundException();
		}
		$this->template->fillDependencies();
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteByTemplate()
	{
		TemplateTagTable::deleteList([
			'TEMPLATE_ID' => $this->templateId,
		]);
	}
}