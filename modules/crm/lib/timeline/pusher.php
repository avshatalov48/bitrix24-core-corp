<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Loader;

class Pusher
{
	public const ADD_ACTIVITY_PULL_COMMAND = 'timeline_activity_add';
	public const ITEM_ACTION_COMMAND = 'timeline_item_action';

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
	 * Send pull event with history data model to timeline
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
		$this->sendPullCommand(
			$targetEntityTypeId,
			$targetEntityId,
			$command,
			$historyDataModel ? [
				'HISTORY_ITEM' => $historyDataModel,
			] : []
		);
	}

	/**
	 * Send pull event with action to timeline
	 *
	 * @param int $targetEntityTypeId
	 * @param int $targetEntityId
	 * @param string $action
	 * @param array $actionParams
	 * @return void
	 */
	public function sendPullActionEvent(
		int $targetEntityTypeId,
		int $targetEntityId,
		string $action,
		array $actionParams = []
	)
	{
		$this->sendPullCommand(
			$targetEntityTypeId,
			$targetEntityId,
			self::ITEM_ACTION_COMMAND,
			array_merge(
				['action' => $action],
				$actionParams
			)
		);
	}

	/**
	 *  Send pull event to timeline
	 *
	 * @param int $targetEntityTypeId
	 * @param int $targetEntityId
	 * @param string $command
	 * @param array $commandParams
	 * @return void
	 */
	protected function sendPullCommand(
		int $targetEntityTypeId,
		int $targetEntityId,
		string $command,
		array $commandParams = []
	): void
	{
		if (!$this->includePullModule())
		{
			return;
		}

		$pushParams = $this->preparePushParamsByCommand($targetEntityTypeId, $targetEntityId, $command);

		$this->pullWatch::AddToStack(
			$pushParams['TAG'],
			[
				'module_id' => 'crm',
				'command' => $command,
				'params' => array_merge($pushParams, $commandParams),
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

	public function isDetailsPageChannelActive(ItemIdentifier $targetEntityIdentifier): bool
	{
		$pushTag = $this->prepareEntityPushTag($targetEntityIdentifier->getEntityTypeId(), $targetEntityIdentifier->getEntityId());

		return \Bitrix\Crm\Integration\PullManager::isPullChannelActiveByTag($pushTag);
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
			static::ITEM_ACTION_COMMAND,
		];

		return in_array($command, $itemDetailsCommands, true);
	}
}
