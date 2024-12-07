<?php

namespace Bitrix\Tasks\Update;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlHelper;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateTagTable;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Main\Config\Option;

class TemplateConverter implements AgentInterface
{
	use AgentTrait;

	public const LIMIT = 500;

	private const OPTION_KEY = 'task_template_member_convert';

	private static $processing = false;

	/**
	 * @return bool
	 */
	public static function isProceed(): bool
	{
		return Option::get('tasks', self::OPTION_KEY, 'null') !== 'null';
	}

	public static function execute(): string
	{
		if (self::$processing)
		{
			return static::getAgentName();
		}

		self::$processing = true;

		$agent = new self();
		$res = $agent->run();

		self::$processing = false;

		return $res;
	}

	private function __construct()
	{

	}

	private function run()
	{
		if (!Loader::includeModule('tasks'))
		{
			$this->convertDone();
			return '';
		}

		$templates = $this->getList();
		if (empty($templates))
		{
			$this->convertDone();
			return '';
		}

		$members = [];
		$tags = [];
		$dependencies = [];
		foreach ($templates as $template)
		{
			$members = array_merge($members, $this->readMembers($template));
			$tags = array_merge($tags, $this->readTags($template));
			$dependencies = array_merge($dependencies, $this->readDependencies($template));
		}

		try
		{
			$this->saveMembers($members);
			$this->saveTags($tags);
			$this->saveDependencies($dependencies);
		}
		catch (\Exception $e)
		{
			(new Log())->collect('Unable to convert templates. '.$e->getMessage());
			return '';
		}


		if (count($templates) < self::LIMIT)
		{
			$this->convertDone();
			return '';
		}

		Option::set('tasks', self::OPTION_KEY, (int) $template['ID']);

		return static::getAgentName();
	}

	/**
	 * @param $template
	 * @return array
	 */
	private function readMembers($template): array
	{
		$members = [];

		$templateId = (int) $template['ID'];

		$members[] = [
			'TEMPLATE_ID' => $templateId,
			'USER_ID' => (int) $template['CREATED_BY'],
			'TYPE' => MemberTable::MEMBER_TYPE_ORIGINATOR,
		];

		$members[] = [
			'TEMPLATE_ID' => $templateId,
			'USER_ID' => (int) $template['RESPONSIBLE_ID'],
			'TYPE' => MemberTable::MEMBER_TYPE_RESPONSIBLE,
		];

		$responsibles = $this->unserializeMembers($template['RESPONSIBLES']);
		foreach ($responsibles as $userId)
		{
			$userId = (int) $userId;
			if ($userId === (int) $template['RESPONSIBLE_ID'])
			{
				continue;
			}

			$members[] = [
				'TEMPLATE_ID' => $templateId,
				'USER_ID' => $userId,
				'TYPE' => MemberTable::MEMBER_TYPE_RESPONSIBLE,
			];
		}

		$auditors = $this->unserializeMembers($template['AUDITORS']);
		foreach ($auditors as $userId)
		{
			$userId = (int) $userId;
			$members[] = [
				'TEMPLATE_ID' => $templateId,
				'USER_ID' => $userId,
				'TYPE' => MemberTable::MEMBER_TYPE_AUDITOR,
			];
		}

		$accomplices = $this->unserializeMembers($template['ACCOMPLICES']);
		foreach ($accomplices as $userId)
		{
			$userId = (int) $userId;
			$members[] = [
				'TEMPLATE_ID' => $templateId,
				'USER_ID' => $userId,
				'TYPE' => MemberTable::MEMBER_TYPE_ACCOMPLICE,
			];
		}

		return $members;
	}

	/**
	 * @param $members
	 * @return array
	 */
	private function unserializeMembers($members): array
	{
		if (empty($members))
		{
			return [];
		}

		$members = unserialize($members, ['allowed_classes' => false]);
		if (
			!$members
			|| !is_array($members)
		)
		{
			return [];
		}

		return array_values($members);
	}

	/**
	 * @param $template
	 * @return array
	 */
	private function readTags($template): array
	{
		$templateId = (int) $template['ID'];
		$userId = (int) $template['CREATED_BY'];

		if (empty($template['TAGS']))
		{
			return [];
		}

		$tags = unserialize($template['TAGS'], ['allowed_classes' => false]);
		if (
			!$tags
			|| !is_array($tags)
		)
		{
			return [];
		}

		$res = [];
		foreach ($tags as $tag)
		{
			if (empty($tag))
			{
				continue;
			}

			$res[] = [
				'TEMPLATE_ID' => $templateId,
				'USER_ID' => $userId,
				'NAME' => $tag,
			];
		}

		return $res;
	}

	/**
	 * @param $template
	 * @return array
	 */
	private function readDependencies($template): array
	{
		$templateId = (int) $template['ID'];

		if (empty($template['DEPENDS_ON']))
		{
			return [];
		}

		$depends = unserialize($template['DEPENDS_ON'], ['allowed_classes' => false]);
		if (
			!$depends
			|| !is_array($depends)
		)
		{
			return [];
		}

		$res = [];
		foreach ($depends as $dep)
		{
			$dep = (int) $dep;
			if (empty($dep))
			{
				continue;
			}

			$res[] = [
				'TEMPLATE_ID' => $templateId,
				'DEPENDS_ON_ID' => $dep,
			];
		}

		return $res;
	}

	/**
	 * @param $members
	 * @return void
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function saveMembers($members)
	{
		if (empty($members))
		{
			return;
		}

		$insertRows = array_map(function($el) {
			$implode = $el['TEMPLATE_ID'];
			$implode .= ','.$el['USER_ID'];
			$implode .= ',\''.$this->getSqlHelper()->forSql($el['TYPE']) .'\'';
			return $implode;
		}, $members);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			TemplateMemberTable::getTableName(),
			' (TEMPLATE_ID, USER_ID, TYPE)',
			" VALUES (". implode("),(", $insertRows) .")"
		);

		Application::getConnection()->query($sql);
	}

	/**
	 * @param $tags
	 * @return void
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function saveTags($tags)
	{
		if (empty($tags))
		{
			return;
		}

		$insertRows = array_map(function($el) {
			$implode = $el['USER_ID'];
			$implode .= ','.$el['TEMPLATE_ID'];
			$implode .= ',\''. $this->getSqlHelper()->forSql($el['NAME']) .'\'';
			return $implode;
		}, $tags);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			TemplateTagTable::getTableName(),
			' (USER_ID, TEMPLATE_ID, NAME)',
			" VALUES (". implode("),(", $insertRows) .")"
		);

		Application::getConnection()->query($sql);
	}

	/**
	 * @param $depends
	 * @return void
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function saveDependencies($depends)
	{
		if (empty($depends))
		{
			return;
		}

		$insertRows = array_map(function($el) {
			$implode = $el['TEMPLATE_ID'];
			$implode .= ','.$el['DEPENDS_ON_ID'];
			return $implode;
		}, $depends);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			TemplateDependenceTable::getTableName(),
			' (TEMPLATE_ID, DEPENDS_ON_ID)',
			" VALUES (". implode("),(", $insertRows) .")"
		);

		Application::getConnection()->query($sql);
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getList(): array
	{
		$startId = Option::get('tasks', self::OPTION_KEY, 0);

		$templates = TemplateTable::getList([
			'filter' => [
				'>ID' => $startId,
			],
			'order' => [
				'ID' => 'ASC'
			],
			'limit' => self::LIMIT
		])->fetchCollection();

		$list = [];
		foreach ($templates as $template)
		{
			$list[$template->getId()] = $template->toArray();
		}

		return $list;
	}

	/**
	 *
	 */
	private function convertDone(): void
	{
		Option::delete('tasks', ['name' => self::OPTION_KEY, 'site_id' => '-']);
		Option::delete('tasks', ['name' => self::OPTION_KEY]);
	}

	private function getSqlHelper(): SqlHelper
	{
		return Application::getConnection()->getSqlHelper();
	}
}