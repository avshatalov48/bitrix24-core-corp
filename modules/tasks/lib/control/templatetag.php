<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use Bitrix\Tasks\Internals\Task\Template\TemplateTagTable;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use \Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;

class TemplateTag
{
	private const FIELD_TAGS = 'TAGS';

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
			!array_key_exists(self::FIELD_TAGS, $data)
			|| !is_array($data[self::FIELD_TAGS])
		)
		{
			return;
		}

		$this->loadTemplate();
		$this->deleteByTemplate();

		if (empty($data[self::FIELD_TAGS]))
		{
			return;
		}

		$tags = array_values($data[self::FIELD_TAGS]);

		if (empty($tags))
		{
			return;
		}

		$insertRows = [];
		foreach ($tags as $tag)
		{
			if (empty($tag))
			{
				continue;
			}
			$insertRows[] = '('.$this->templateId.', '. $this->userId .', "'. str_replace('"', '\"', $tag) .'")';
		}

		$sql = "
			INSERT IGNORE INTO ". TemplateTagTable::getTableName() ."
			(`TEMPLATE_ID`, `USER_ID`, `NAME`)
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
		$this->template->fillTagList();
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