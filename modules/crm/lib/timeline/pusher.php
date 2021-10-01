<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main\Loader;

class Pusher
{
	public const ADD_ACTIVITY_PULL_COMMAND = 'timeline_activity_add';
	public const ADD_LINK_PULL_COMMAND = 'timeline_link_add';
	public const DELETE_LINK_PULL_COMMAND = 'timeline_link_delete';

	/** @var \CPullWatch|string|null */
	protected $pullWatch;

	public function __construct()
	{
		if ($this->includePullModule())
		{
			$this->pullWatch = \CPullWatch::class;
		}
	}

	protected function includePullModule(): bool
	{
		return Loader::includeModule('pull');
	}

	/**
	 * Send pull event about timeline change
	 *
	 * @param int $targetEntityTypeId
	 * @param int $targetEntityId
	 * @param string $command
	 * @param array|null $historyDataModel
	 */
	public function sendPullEvent(
		int $targetEntityTypeId,
		int $targetEntityId,
		string $command,
		array $historyDataModel = null
	): void
	{
		if (!$this->includePullModule())
		{
			return;
		}

		$pushParams = $this->preparePushParamsByCommand($targetEntityTypeId, $targetEntityId, $command);
		if (!is_null($historyDataModel))
		{
			$pushParams['HISTORY_ITEM'] = $historyDataModel;
		}

		$this->pullWatch::AddToStack(
			$pushParams['TAG'],
			[
				'module_id' => 'crm',
				'command' => $command,
				'params' => $pushParams,
			]
		);
	}

	public function prepareEntityPushTag(int $targetEntityTypeId, int $targetEntityId = null): string
	{
		$ownerTypeName = \CCrmOwnerType::ResolveName($targetEntityTypeId);

		$tag = "CRM_TIMELINE_{$ownerTypeName}";

		if ($targetEntityId > 0)
		{
			$targetEntityId = (int)$targetEntityId;

			$tag .= "_{$targetEntityId}";
		}

		return $tag;
	}

	protected function preparePushParamsByCommand(int $targetEntityTypeId, int $targetEntityId, string $command): array
	{
		if ($this->isCommandShouldBeSentToItemDetailsPage($command))
		{
			return [
				'TAG' => $this->prepareEntityPushTag($targetEntityTypeId, $targetEntityId),
			];
		}

		return [
			'ID' => $targetEntityId,
			'TAG' => $this->prepareEntityPushTag($targetEntityTypeId),
		];
	}

	protected function isCommandShouldBeSentToItemDetailsPage(string $command): bool
	{
		// events with this commands should be sent to item (deal, quote) details page
		$itemDetailsCommands = [
			static::ADD_ACTIVITY_PULL_COMMAND,
			static::ADD_LINK_PULL_COMMAND,
			static::DELETE_LINK_PULL_COMMAND,
		];

		return in_array($command, $itemDetailsCommands, true);
	}
}
