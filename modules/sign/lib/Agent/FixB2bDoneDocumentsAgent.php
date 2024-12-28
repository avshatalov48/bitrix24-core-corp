<?php

namespace Bitrix\Sign\Agent;

use Bitrix\Main\Config\Option;
use Bitrix\Sign\Internal\DocumentTable;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Sign\Type\Document\EntityType;
use Bitrix\Sign\Type\DocumentStatus;

final class FixB2bDoneDocumentsAgent
{
	private const START_ISSUE_AFFECTED_DATE = 1732060800;
	private const LIMIT_ROWS_PROCESS = 100;
	private const OPTION_NAME = '~fix_b2e_done_documents_agent_installed';

	public static function run(): string
	{
		$fromDate = DateTime::createFromTimestamp(self::START_ISSUE_AFFECTED_DATE);
		$documents = DocumentTable::query()
			->whereNotNull('RESULT_FILE_ID')
			->where('STATUS', DocumentStatus::SIGNING)
			->where('ENTITY_TYPE', EntityType::SMART)
			->where('DATE_SIGN', '>', $fromDate)
			->setSelect(['ID'])
			->setLimit(self::LIMIT_ROWS_PROCESS)
			->fetchCollection()
		;

		if ($documents->isEmpty())
		{
			return '';
		}

		DocumentTable::updateMulti($documents->getIdList(), ['STATUS' => DocumentStatus::DONE]);

		return self::getAgentName();
	}

	public static function install(): mixed
	{
		return \CAgent::AddAgent(
			name: self::getAgentName(),
			module: 'sign',
			interval: 60,
			existError: false
		);
	}

	public static function installOnce(): void
	{
		if (self::isAgentInstalled())
		{
			return;
		}

		if (self::install())
		{
			self::setAgentInstalled();
		}
	}

	private static function getAgentName(): string
	{
		return '\\Bitrix\\Sign\\Agent\\FixB2bDoneDocumentsAgent::run();';
	}

	private static function isAgentInstalled(): bool
	{
		return Option::get('sign', self::OPTION_NAME, 'N') === 'Y';
	}

	private static function setAgentInstalled(): void
	{
		Option::set('sign', self::OPTION_NAME, 'Y');
	}
}