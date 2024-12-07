<?php

namespace Bitrix\AI\History;

use Bitrix\AI\Config;
use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Payload\IPayload;
use Bitrix\AI\Result;
use Bitrix\AI\Model\HistoryTable;
use Bitrix\Main\Type\DateTime;
use ReflectionClass;

class Manager
{
	public function __construct(
		private IEngine $engine,
	) {}

	/**
	 * Finally removes all records for the user.
	 *
	 * @param int $userId User id.
	 * @return void
	 */
	public static function deleteForUser(int $userId): void
	{
		$rows = HistoryTable::query()
			->setSelect(['ID'])
			->where('CREATED_BY_ID', $userId)
			->fetchAll()
		;
		foreach ($rows as $row)
		{
			HistoryTable::delete($row['ID'])->isSuccess();
		}
	}

	/**
	 * Returns true, if in module settings enabled corresponding option.
	 *
	 * @return bool
	 */
	public static function shouldAlwaysWrite(): bool
	{
		return Config::getValue('write_history_always') === 'Y';
	}

	/**
	 * Returns true if history will not be saved for current user.
	 *
	 * @return bool
	 */
	public static function shouldDisableHistoryForUser(int $userId): bool
	{
		if (empty($userId))
		{
			return false;
		}

		$codes = Config::getValue('disable_history_for_users');
		if (empty($codes))
		{
			return false;
		}

		if (($GLOBALS['USER'] instanceof \CUser) && (int)$GLOBALS['USER']->getId() === $userId)
		{
			$userAccessCodes = [];
			$user = User::getInstance();
			if (!empty($user))
			{
				$userAccessCodes = $user->getAccessCodes();
			}
		}
		else
		{
			$userAccessCodes = \CAccess::GetUserCodesArray($userId);
		}

		$diffCodes = array_intersect(explode(',', $codes), $userAccessCodes);

		return !empty($diffCodes);
	}

	/**
	 * Returns capacity of history per user-context.
	 *
	 * @return int
	 */
	public static function getCapacity(): int
	{
		return (int)Config::getValue('max_history_per_user');
	}

	/**
	 * Returns last history item for current context.
	 *
	 * @param Context $context Context instance.
	 * @return array|null
	 */
	public static function getLastItem(Context $context): ?array
	{
		return self::readHistory($context, 1)->toArray()[0] ?? null;
	}

	/**
	 * Returns fake History Item (use when history doesn't exist for current user).
	 *
	 * @param string|null $data Data.
	 * @param IEngine|Engine $engine Engine instance.
	 * @return Item
	 */
	public static function getFakeItem(?string $data, IEngine|Engine $engine): Item
	{
		return new Item(
			null,
			new DateTime(),
			$data,
			$engine->getCode(),
			$engine->getPayload()->getRawData(),
		);
	}

	/**
	 * Returns collection of history Items.
	 *
	 * @param Context $context History context.
	 * @param int|null $limit Limit of records.
	 * @return Collection
	 */
	public static function readHistory(Context $context, ?int $limit = 0): Collection
	{
		if ($limit === null)
		{
			$limit = self::getCapacity();
		}

		 $items = HistoryTable::query()
			->setSelect(['ID', 'DATE_CREATE', 'RESULT_TEXT', 'GROUP_ID', 'PAYLOAD', 'PAYLOAD_CLASS', 'ENGINE_CODE'])
			->where('CONTEXT_MODULE', $context->getModuleId())
			->where('CONTEXT_ID', $context->getContextId())
			->where('CREATED_BY_ID', $context->getUserId())
			->whereIn('GROUP_ID', [-1, 0])
			->setOrder(['ID' => 'DESC'])
			->setLimit($limit)
			->fetchAll()
		;

		$collection = [];
		$groupIds = [];
		foreach($items as $item)
		{
			if ($item['GROUP_ID'] !== -1)
			{
				$groupIds[] = $item['ID'];
			}

			/** @var IPayload $payloadClass */
			$payloadClass = $item['PAYLOAD_CLASS'];
			$payload = $payloadClass::unpack($item['PAYLOAD']);

			// todo: how add group item
			$collection[$item['ID']] = new Item(
				$item['ID'],
				$item['DATE_CREATE'],
				$item['RESULT_TEXT'],
				$item['ENGINE_CODE'],
				$payload?->getRawData(),
			);
		}

		if (!empty($groupIds))
		{
			$groupItems = HistoryTable::query()
				->setSelect(['ID', 'RESULT_TEXT', 'GROUP_ID'])
				->where('CONTEXT_MODULE', $context->getModuleId())
				->where('CONTEXT_ID', $context->getContextId())
				->where('CREATED_BY_ID', $context->getUserId())
				->whereIn('GROUP_ID', $groupIds)
				->setOrder(['ID' => 'ASC'])
				->fetchAll()
			;

			foreach($groupItems as $groupItem)
			{
				if ($collection[$groupItem['GROUP_ID']])
				{
					$collection[$groupItem['GROUP_ID']]->addGroupData($groupItem['RESULT_TEXT']);
				}
			}
		}

		return new Collection(array_values($collection));
	}

	/**
	 * Writes history for Engine's work result.
	 *
	 * @param Result $result Result data.
	 * @return bool
	 */
	public function writeHistory(Result $result): bool
	{
		$context = $this->engine->getContext();
		$this->truncateForUser($context);
		$requestText = null;
		$contextText = null;

		if (Config::getValue('write_history_request') === 'Y')
		{
			$requestText = $this->engine->getPayload()->getData();
			if (is_array($requestText))
			{
				$requestText = serialize($requestText);
			}

			if ($this->engine instanceof IContext)
			{
				$contextText = array_map(fn($it) => $it->toArray(), $this->engine->getMessages());

				$roleText = $this->engine->getPayload()->getRole()?->getInstruction();
				if (!empty($roleText))
				{
					array_unshift($contextText, ['role_instruction' => $roleText]);
				}

				$contextText = json_encode($contextText);
			}
		}

		$res = HistoryTable::add([
			'CONTEXT_MODULE' => $context->getModuleId(),
			'CONTEXT_ID' => $context->getContextId(),
			'ENGINE_CLASS' => (new ReflectionClass($this->engine))->getName(),
			'ENGINE_CODE' => $this->engine->getCode(),
			'PAYLOAD_CLASS' => (new ReflectionClass($this->engine->getPayload()))->getName(),
			'PAYLOAD' => $this->engine->getPayload()->pack(),
			'PARAMETERS' => $this->engine->getParameters(),
			'GROUP_ID' => $this->engine->getHistoryGroupId(),
			'REQUEST_TEXT' => $requestText,
			'RESULT_TEXT' => $result->getPrettifiedData(),
			'CONTEXT' => $contextText,
			'CACHED' => $result->isCached(),
			'CREATED_BY_ID' => $context->getUserId(),
		]);

		return $res->isSuccess();
	}

	/**
	 * Truncates records for user, keeps only specified count.
	 *
	 * @param Context $context Context.
	 * @return void
	 */
	private function truncateForUser(Context $context): void
	{
		$keepCount = self::getCapacity();
		$keepCount--;

		$items = HistoryTable::query()
			->setSelect(['ID', 'GROUP_ID'])
			->where('CREATED_BY_ID', $context->getUserId())
			->whereIn('GROUP_ID', [-1, 0])
			->setOrder(['ID' => 'DESC'])
			->setLimit(1100)
			->fetchAll()
		;

		$itemsForDelete = [];
		$groupsForDel = [];
		$offset = 0;
		foreach ($items as $item)
		{
			if (++$offset <= $keepCount)
			{
				continue;
			}
			if ($item['GROUP_ID'] !== -1)
			{
				$groupsForDel[] = $item['ID'];
			}
			$itemsForDelete[] = $item['ID'];
		}

		if (count($itemsForDelete) > 0)
		{
			HistoryTable::deleteByFilter(['ID' => $itemsForDelete]);
		}

		if (count($groupsForDel) > 0)
		{
			HistoryTable::deleteByFilter([
				'GROUP_ID' => $groupsForDel,
				'=CONTEXT_MODULE' => $context->getModuleId(),
				'=CONTEXT_ID' => $context->getContextId(),
				'=CREATED_BY_ID' => $context->getUserId(),
			]);
		}
	}

	/**
	 * Removes all History by Context.
	 *
	 * @param array|string $context Specified context.
	 * @return void
	 */
	public static function clearHistoryByContext(array|string $context): void
	{
		$res = HistoryTable::query()
			->setSelect(['ID'])
			->whereIn('CONTEXT_ID', (array)$context)
		;
		while ($row = $res->fetch())
		{
			HistoryTable::delete($row['ID'])->isSuccess();
		}
	}
}
