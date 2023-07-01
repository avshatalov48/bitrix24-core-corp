<?php

namespace Bitrix\Market\Integration\BizProc;

use Bitrix\Bizproc\Workflow\Template\Entity\WorkflowTemplateTable;
use Bitrix\Bizproc\Automation\Engine\Template;
use CBPDocumentEventType;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\Market\Tag\Manager;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\BizProc
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'bizproc';
	private const TAG_COUNT_PREFIX = 'bizproc|';

	/**
	 * Return tags list
	 * @return array
	 */
	public static function list(): array
	{
		return static::listTemplateTag();
	}

	/**
	 * Event on add bizproc
	 * @param Event $event
	 */
	public static function onEventAdd(Event $event)
	{
		Manager::deleteByModule(static::MODULE_ID);
		Manager::saveList(static::listTemplateTag());
	}

	/**
	 * Event on delete bizproc
	 * @param Event|null $event
	 */
	public static function onEventDelete(Event $event)
	{
		Manager::deleteByModule(static::MODULE_ID);
		Manager::saveList(static::listTemplateTag());
	}

	private static function listTemplateTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$res = WorkflowTemplateTable::getList(
				[
					'select' => [
						'ID',
						'AUTO_EXECUTE',
						'MODULE_ID',
						'ENTITY',
						'DOCUMENT_TYPE',
						'DOCUMENT_STATUS',
					],
				]
			);

			while ($item = $res->fetch())
			{
				$value = 1;
				$code = $item['MODULE_ID'] . '|' . $item['AUTO_EXECUTE'];
				if (CBPDocumentEventType::Automation === (int)$item['AUTO_EXECUTE'])
				{
					$template = new Template(
						[
							$item['MODULE_ID'],
							$item['ENTITY'],
							$item['DOCUMENT_TYPE'],
						],
						$item['DOCUMENT_STATUS']
					);
					$value = 0;
					if ($template->getId() > 0)
					{
						$value = count($template->getRobots());
					}
				}

				$result[$code] = [
					'MODULE_ID' => static::MODULE_ID,
					'CODE' => static::TAG_COUNT_PREFIX . $code,
					'VALUE' => ($result[$code]['VALUE'] ?? 0) + $value,
				];
			}
		}

		return array_values($result);
	}
}