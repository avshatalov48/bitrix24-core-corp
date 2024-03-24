<?php
namespace Bitrix\Tasks\Internals\Task\Template\Convert;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlHelper;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateTagTable;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;

class TemplateConverter implements AgentInterface
{
	use AgentTrait;

	private const MAX_ATTEMPS = 5;
	private const LIMIT = 500;

	private const PROCEED_OPTION = 'task_template_convert';
	private const ATTEMPS_OPTION = 'task_template_convert_attemps';
	private const LAST_ID_OPTION = 'task_template_convert_id';

	private static $processing = false;

	public function __construct()
	{

	}

	/**
	 * @return bool
	 */
	public static function isProceed(): bool
	{
		return (int) \COption::GetOptionString('tasks', self::PROCEED_OPTION, 0) === 1;
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
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

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function run()
	{
		if (!Loader::includeModule('tasks'))
		{
			$this->convertDone();
			return '';
		}

		if ($this->getAttemps() > self::MAX_ATTEMPS)
		{
			AddMessage2Log('[ERROR] Unable to convert templates.', 'tasks');
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
		$depends = [];
		foreach ($templates as $template)
		{
			$templateId = (int) $template['ID'];
			$userId = (int) $template['CREATED_BY'];

			// members
			$accomplices = [];
			if (!empty($template['ACCOMPLICES']))
			{
				$accomplices = array_values(unserialize($template['ACCOMPLICES'], ['allowed_classes' => false]));
			}

			$responsibles = [];
			if (!empty($template['RESPONSIBLES']))
			{
				$responsibles = array_values(unserialize($template['RESPONSIBLES'], ['allowed_classes' => false]));
			}
			$responsibles = array_unique(array_merge($responsibles, [$template['RESPONSIBLE_ID']]));

			$auditors = [];
			if (!empty($template['AUDITORS']))
			{
				$auditors = array_values(unserialize($template['AUDITORS'], ['allowed_classes' => false]));
			}

			$members[] = [
				'TEMPLATE_ID' => $templateId,
				'USER_ID' => $userId,
				'TYPE' => TemplateMemberTable::MEMBER_TYPE_ORIGINATOR,
			];

			$members = array_merge($members, $this->getMembers($templateId, TemplateMemberTable::MEMBER_TYPE_ACCOMPLICE, $accomplices));
			$members = array_merge($members, $this->getMembers($templateId, TemplateMemberTable::MEMBER_TYPE_AUDITOR, $auditors));
			$members = array_merge($members, $this->getMembers($templateId, TemplateMemberTable::MEMBER_TYPE_RESPONSIBLE, $responsibles));

			// tags
			if (!empty($template['TAGS']))
			{
				$tags[] = [
					'TEMPLATE_ID' => $templateId,
					'USER_ID' => $userId,
					'TAGS' => unserialize($template['TAGS'], ['allowed_classes' => false]),
				];
			}

			// dependencies
			if (!empty($template['DEPENDS_ON']))
			{
				$depends[] = [
					'TEMPLATE_ID' => $templateId,
					'DEPENDS' => unserialize($template['DEPENDS_ON'], ['allowed_classes' => false]),
				];
			}
		}

		try
		{
			$this->saveMembers($members);
			$this->saveTags($tags);
			$this->saveDepends($depends);
		}
		catch (\Exception $e)
		{
			$this->increaseAttemps();
			return self::getAgentName();
		}

		$this->setLastId($templateId);
		$this->resetAttemps();

		return self::getAgentName();
	}

	/**
	 * @param array $tags
	 * @return void
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function saveTags(array $tags)
	{
		if (empty($tags))
		{
			return;
		}

		$rows = [];
		foreach ($tags as $templateTags)
		{
			if (!is_array($templateTags['TAGS']))
			{
				continue;
			}

			foreach ($templateTags['TAGS'] as $tag)
			{
				$rows[] = '('. $templateTags['TEMPLATE_ID'] .', '. $templateTags['USER_ID'] .', "'. $tag .'")';
			}
		}

		if (empty($rows))
		{
			return;
		}

		$rows = implode(',', $rows);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			TemplateTagTable::getTableName(),
			' (TEMPLATE_ID, USER_ID, NAME)',
			" VALUES {$rows}"
		);

		Application::getConnection()->query($sql);
	}

	/**
	 * @param array $depends
	 * @return void
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function saveDepends(array $depends)
	{
		if (empty($depends))
		{
			return;
		}

		$rows = [];
		foreach ($depends as $templateDepend)
		{
			if (
				!is_array($templateDepend['DEPENDS'])
				|| empty($templateDepend['DEPENDS'])
			)
			{
				continue;
			}
			foreach ($templateDepend['DEPENDS'] as $depId)
			{
				$depId = (int)$depId;
				if ($depId < 1)
				{
					continue;
				}
				$rows[] = '('. $templateDepend['TEMPLATE_ID'] .', "'. $depId .'")';
			}
		}

		if (empty($rows))
		{
			return;
		}

		$rows = implode(',', $rows);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			TemplateDependenceTable::getTableName(),
			' (TEMPLATE_ID, DEPENDS_ON_ID)',
			" VALUES {$rows}"
		);

		Application::getConnection()->query($sql);
	}

	/**
	 * @param array $members
	 * @return void
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function saveMembers(array $members)
	{
		if (empty($members))
		{
			return;
		}

		$rows = [];
		foreach ($members as $member)
		{
			$rows[] = '('. $member['TEMPLATE_ID'] .', '. $member['USER_ID'] .', "'. $member['TYPE'] .'")';
		}

		if (empty($rows))
		{
			return;
		}

		$rows = implode(',', $rows);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			TemplateMemberTable::getTableName(),
			' (TEMPLATE_ID, USER_ID, TYPE)',
			" VALUES {$rows}"
		);

		Application::getConnection()->query($sql);
	}

	/**
	 * @param int $templateId
	 * @param string $type
	 * @param array $users
	 * @return array
	 */
	private function getMembers(int $templateId, string $type, array $users): array
	{
		if (empty($users))
		{
			return [];
		}

		$members = [];
		foreach ($users as $userId)
		{
			if ((int) $userId <= 0)
			{
				continue;
			}

			$members[] = [
				'TEMPLATE_ID' => $templateId,
				'USER_ID' => (int) $userId,
				'TYPE' => $type,
			];
		}

		return $members;
	}

	/**
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getList(): ?array
	{
		$lastId = $this->getLastId();

		return TemplateTable::getList([
			'filter' => [
				'>ID' => $lastId,
			],
			'order' => [
				'ID' => 'ASC'
			],
			'limit' => self::LIMIT,
		])->fetchAll();
	}

	/**
	 * @param int $id
	 * @return void
	 */
	private function setLastId(int $id): void
	{
		\COption::SetOptionString('tasks', self::LAST_ID_OPTION, $id, false, '');
	}

	/**
	 * @return int
	 */
	private function getLastId(): int
	{
		return (int) \COption::GetOptionString('tasks', self::LAST_ID_OPTION, 0, '');
	}

	/**
	 * @return void
	 */
	private function convertDone(): void
	{
		\COption::RemoveOption('tasks', self::PROCEED_OPTION, '');
		\COption::RemoveOption('tasks', self::LAST_ID_OPTION, '');
	}

	/**
	 * @return int
	 */
	private function getAttemps(): int
	{
		return (int) \COption::GetOptionString('tasks', self::ATTEMPS_OPTION, 0, '');
	}

	/**
	 * @return void
	 */
	private function increaseAttemps(): void
	{
		$attemps = $this->getAttemps() + 1;
		\COption::SetOptionString('tasks', self::ATTEMPS_OPTION, $attemps, false, '');
	}

	/**
	 * @return void
	 */
	private function resetAttemps()
	{
		\COption::RemoveOption('tasks', self::ATTEMPS_OPTION, '');
	}

	private function getSqlHelper(): SqlHelper
	{
		return Application::getConnection()->getSqlHelper();
	}
}
