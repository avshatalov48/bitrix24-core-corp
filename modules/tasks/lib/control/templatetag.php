<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\Task\Template\TemplateTagTable;

class TemplateTag
{
	use BaseTemplateControlTrait;

	private const FIELD_TAGS = 'TAGS';

	/* @var TemplateObject $template */
	private $template;

	public function __construct(private int $userId, private int $templateId)
	{
	}

	/**
	 * @throws TemplateNotFoundException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function set(array $data): void
	{
		if (
			!array_key_exists(self::FIELD_TAGS, $data)
			|| !is_array($data[self::FIELD_TAGS])
		)
		{
			return;
		}

		$this->loadByTemplate();
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

		$dbHelper = Application::getConnection()->getSqlHelper();

		$insertRows = [];
		foreach ($tags as $tag)
		{
			if (empty($tag))
			{
				continue;
			}
			$insertRows[] = '('.$this->templateId.', '. $this->userId .', \''. $dbHelper->forSql($tag) .'\')';
		}

		$sql = $this->getInsertIgnore(
			'(TEMPLATE_ID, USER_ID, NAME)',
			"VALUES ". implode(", ", $insertRows)
		);

		Application::getConnection()->query($sql);
	}

	/**
	 * @throws TemplateNotFoundException
	 * @throws SystemException
	 */
	private function loadByTemplate(): void
	{
		$this->loadTemplate();
		$this->template->fillTagList();
	}

	public function getTableClass(): string
	{
		return TemplateTagTable::class;
	}
}