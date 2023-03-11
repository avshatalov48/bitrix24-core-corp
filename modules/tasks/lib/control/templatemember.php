<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use \Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;

class TemplateMember
{
	private const FIELD_CREATED_BY = 'CREATED_BY';
	private const FIELD_RESPONSIBLES = 'RESPONSIBLES';
	private const FIELD_ACCOMPLICES = 'ACCOMPLICES';
	private const FIELD_AUDITORS = 'AUDITORS';

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
		$this->loadTemplate();

		$members = $this->getCurrentMembers();

		$changed = false;
		if (array_key_exists(self::FIELD_CREATED_BY, $data))
		{
			$members[TemplateMemberTable::MEMBER_TYPE_ORIGINATOR] = [];
			$members[TemplateMemberTable::MEMBER_TYPE_ORIGINATOR][] = [
				'USER_ID' => $data[self::FIELD_CREATED_BY],
				'TYPE' => TemplateMemberTable::MEMBER_TYPE_ORIGINATOR,
			];

			$changed = true;
		}

		if (array_key_exists(self::FIELD_RESPONSIBLES, $data))
		{
			$members[TemplateMemberTable::MEMBER_TYPE_RESPONSIBLE] = [];
			foreach ($data[self::FIELD_RESPONSIBLES] as $userId)
			{
				$members[TemplateMemberTable::MEMBER_TYPE_RESPONSIBLE][] = [
					'USER_ID' => $userId,
					'TYPE' => TemplateMemberTable::MEMBER_TYPE_RESPONSIBLE,
				];
			}

			$changed = true;
		}

		if (array_key_exists(self::FIELD_ACCOMPLICES, $data))
		{
			$members[TemplateMemberTable::MEMBER_TYPE_ACCOMPLICE] = [];
			foreach ($data[self::FIELD_ACCOMPLICES] as $userId)
			{
				$members[TemplateMemberTable::MEMBER_TYPE_ACCOMPLICE][] = [
					'USER_ID' => $userId,
					'TYPE' => TemplateMemberTable::MEMBER_TYPE_ACCOMPLICE,
				];
			}

			$changed = true;
		}

		if (array_key_exists(self::FIELD_AUDITORS, $data))
		{
			$members[TemplateMemberTable::MEMBER_TYPE_AUDITOR] = [];
			foreach ($data[self::FIELD_AUDITORS] as $userId)
			{
				$members[TemplateMemberTable::MEMBER_TYPE_AUDITOR][] = [
					'USER_ID' => $userId,
					'TYPE' => TemplateMemberTable::MEMBER_TYPE_AUDITOR,
				];
			}

			$changed = true;
		}

		if (!$changed)
		{
			return;
		}

		$this->deleteByTemplate();

		if (empty($members))
		{
			return;
		}

		$insertRows = [];
		foreach ($members as $type => $list)
		{
			$insertRows = array_merge(
				$insertRows,
				array_map(function($el) {
					$implode = (int) $el['USER_ID'];
					$implode .= ','.$this->templateId;
					$implode .= ',\''.$el['TYPE'].'\'';
					return $implode;
				}, $list)
			);
		}

		$sql = "
			INSERT IGNORE INTO ". TemplateMemberTable::getTableName() ."
			(`USER_ID`, `TEMPLATE_ID`, `TYPE`)
			VALUES
			(". implode("),(", $insertRows) .")
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
		$this->template->fillMembers();
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteByTemplate()
	{
		TemplateMemberTable::deleteList([
			'TEMPLATE_ID' => $this->templateId,
		]);
	}

	/**
	 * @return array
	 */
	private function getCurrentMembers(): array
	{
		$members = [];

		$this->template->fillMembers();

		$memberList = $this->template->getMembers();
		foreach($memberList as $member)
		{
			$memberType = $member->getType();
			$members[$memberType][] = [
				'USER_ID' => $member->getUserId(),
				'TYPE' => $memberType,
			];
		}

		return $members;
	}
}