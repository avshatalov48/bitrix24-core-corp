<?php
namespace Bitrix\ImOpenLines\Controller\Session;

use Bitrix\ImOpenLines\Chat;
use Bitrix\ImOpenLines\Config,
	Bitrix\ImOpenLines\Session,
	Bitrix\ImOpenlines\Security,
	Bitrix\ImOpenLines\Model\SessionTable,
	Bitrix\ImOpenLines\Controller\Stepper;
use Bitrix\Main\Error,
	Bitrix\Main\Engine\Controller,
	Bitrix\Main\Engine\ActionFilter;

class GroupActions extends Controller
{
	use Stepper;

	protected function getDefaultPreFilters(): array
	{
		return [
			new Filter\AccessCheck(),
		];
	}

	/**
	 * @return array
	 */
	public function getDialogAction(): array
	{
		$result = [];

		$filter = [
			'<STATUS' => Session::STATUS_CLOSE,
			'!=CLOSED' => 'Y',
			'CONFIG_ID' => Config::getIdConfigCanJoin()
		];

		$requestData = $this->request->toArray();
		$currentFilter = \Bitrix\Imopenlines\Helpers\Filter::getFilter($requestData['fields']['filterId']);
		if ($currentFilter)
		{
			$filter = array_merge($filter, $currentFilter);
		}

		$sessions = SessionTable::getList([
			'filter' => $filter,
			'select' => [
				'ID',
				'CHAT_ID'
			]
		]);

		while ($session = $sessions->fetch())
		{
			if (
				!empty($session['ID'])
				&& !empty($session['CHAT_ID'])
			)
			{
				$result[] = [
					'sessionId' => $session['ID'],
					'chatId' => $session['CHAT_ID']
				];
			}
		}

		$this->declareTotalItems(count($result));
		$this->declareProcessDone();

		return $this->preformProcessAnswer([
			'sessions' => $result,
		]);
	}

	/**
	 * @param array $fields
	 * @return array|null
	 */
	public function closeAction(array $fields):? array
	{
		return $this->close($fields);
	}

	/**
	 * @param array $fields
	 * @return array|null
	 */
	public function closeSpamAction(array $fields):? array
	{
		return $this->close($fields, true);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	public function transferAction(array $fields): array
	{
		$result = [];

		$this->startTimer();

		// from previous step
		if (isset($fields['totalItems']))
		{
			$this->declareTotalItems((int)$fields['totalItems']);
		}
		if (isset($fields['processedItems']))
		{
			$this->declareProcessedItems((int)$fields['processedItems']);
		}

		$transferId = $this->getValidIdTransfer($fields['transferId']);

		if (!empty($transferId))
		{
			$sessions = $this->getAccessSession($fields);

			// from previous step
			if (isset($fields['totalItems']))
			{
				$this->declareTotalItems((int)$fields['totalItems']);
			}
			else
			{
				$this->declareTotalItems(count($sessions));
			}

			if (!empty($sessions))
			{
				$currentUserId = Security\Helper::getCurrentUserId();

				foreach ($sessions as $session)
				{
					$chat = new Chat($session['CHAT_ID']);

					$resultTransfer = $chat->transfer([
						'FROM' => $currentUserId,
						'TO' => $transferId
					]);

					if ($resultTransfer === true)
					{
						if (mb_substr($transferId, 0, 5) == 'queue')
						{
							\CUserCounter::Increment($currentUserId, 'imopenlines_transfer_count_' . mb_substr($transferId, 5));
						}

						$result[] = $session['ID'];
					}

					if ($this->hasTimeLimitReached())
					{
						break;
					}
				}
			}
		}

		$this->incrementProcessedItems(count($result));

		if (!$this->hasTimeLimitReached() || empty($sessions))
		{
			$this->declareProcessDone();
		}

		return $this->preformProcessAnswer([
			'sessions' => $result,
		]);
	}

	/**
	 * @param $fields
	 * @param false $spam
	 * @return array
	 */
	protected function close($fields, $spam = false): array
	{
		$result = [];

		$this->startTimer();

		$sessions = $this->getAccessSession($fields);

		// from previous step
		if (isset($fields['totalItems']))
		{
			$this->declareTotalItems((int)$fields['totalItems']);
		}
		else
		{
			$this->declareTotalItems(count($sessions));
		}
		if (isset($fields['processedItems']))
		{
			$this->declareProcessedItems((int)$fields['processedItems']);
		}

		if (!empty($sessions))
		{
			$currentUserId = Security\Helper::getCurrentUserId();

			foreach ($sessions as $session)
			{
				$chat = new Chat($session['CHAT_ID']);
				if($spam === true)
				{
					$resultFinishChat = $chat->markSpamAndFinish($currentUserId);
				}
				else
				{
					$resultFinishChat = $chat->finish($currentUserId);
				}

				if ($resultFinishChat->isSuccess())
				{
					$result[] = $session['ID'];
				}

				if ($this->hasTimeLimitReached())
				{
					break;
				}
			}
		}

		$this->incrementProcessedItems(count($result));

		if (!$this->hasTimeLimitReached() || empty($sessions))
		{
			$this->declareProcessDone();
		}

		return $this->preformProcessAnswer([
			'sessions' => $result,
		]);
	}

	/**
	 * @param $ids
	 * @return array
	 */
	protected function getValidIds($ids): array
	{
		$result = [];

		if(
			!empty($ids) &&
			is_array($ids)
		)
		{
			foreach ($ids as $key=>$id)
			{
				$id = (int)$id;

				if(
					!empty($id) &&
					$id > 0
				)
				{
					$result[$key] = $id;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $id
	 * @return string
	 */
	protected function getValidIdTransfer($id): string
	{
		$result = 0;

		if(!empty($id))
		{
			if (mb_strpos($id, 'queue') === 0)
			{
				$id = (int)mb_substr($id, 5);

				if($id > 0)
				{
					$result = 'queue' . $id;
				}

			}
			else
			{
				$id = (int)$id;

				if($id > 0)
				{
					$result = $id;
				}
			}
		}

		return (string)$result;
	}

	/**
	 * @param $fields
	 * @return array
	 */
	protected function getAccessSession($fields): array
	{
		$result = [];
		$currentUserId = Security\Helper::getCurrentUserId();

		$fields['idsChat'] = $this->getValidIds($fields['idsChat']);
		$fields['idsSession'] = $this->getValidIds($fields['idsSession']);

		if(
			!empty($fields['idsSession']) ||
			!empty($fields['idsChat'] )
		)
		{
			$sessions = SessionTable::getList([
				'filter' => [
					[
						'LOGIC' => 'OR',
						'ID' => $fields['idsSession'],
						'CHAT_ID' => $fields['idsChat']
					],
					//Not closed
					'<STATUS' => Session::STATUS_CLOSE,
					'!=CLOSED' => 'Y',
				],
				'select' => [
					'ID',
					'CHAT_ID',
					'OPERATOR_ID',
					'CONFIG_ID'
				]
			]);

			while ($session = $sessions->fetch())
			{
				if(
					!empty($session['CHAT_ID']) &&
					(
						(
							!empty($currentUserId) &&
							!empty($session['OPERATOR_ID']) &&
							(int)$session['OPERATOR_ID'] === (int)$currentUserId
						) ||
						Config::canJoin($session['CONFIG_ID'])
					)
				)
				{
					$result[] = $session;
				}
			}
		}

		return $result;
	}
}