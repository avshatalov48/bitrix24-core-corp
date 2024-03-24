<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use \Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;

class TemplateMember
{
	use BaseTemplateControlTrait;

	private const FIELD_CREATED_BY = 'CREATED_BY';
	private const FIELD_RESPONSIBLES = 'RESPONSIBLES';
	private const FIELD_ACCOMPLICES = 'ACCOMPLICES';
	private const FIELD_AUDITORS = 'AUDITORS';

	/* @var \Bitrix\Tasks\Internals\Task\Template\TemplateObject $template */
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
		$this->loadByTemplate();

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

		$sql = $this->getInsertIgnore(
			'(USER_ID, TEMPLATE_ID, TYPE)',
			"VALUES (" . implode("),(", $insertRows) . ")"
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
		$this->template->fillMembers();
	}

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

	public function getTableClass(): string
	{
		return TemplateMemberTable::class;
	}
}