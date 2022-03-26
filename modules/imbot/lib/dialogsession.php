<?php declare(strict_types=1);

namespace Bitrix\ImBot;

use Bitrix\Main;
use Bitrix\ImBot\Model\NetworkSessionTable;
use Bitrix\Main\Type\DateTime;

class DialogSession
{
	public const EXPIRES_DAYS = 90;

	/** @var int */
	protected $primaryId;

	/** @var int */
	protected $botId;

	/** @var string */
	protected $dialogId;

	/** @var int */
	protected $sessionId;

	/**
	 * @param int|null $botId
	 * @param string|null $dialogId
	 */
	public function __construct(?int $botId = null, ?string $dialogId = null)
	{
		$this->init([
			'BOT_ID' => $botId,
			'DIALOG_ID' => $dialogId,
		]);
	}

	/**
	 * Init object fields from params.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) ID
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) BOT_ID
	 * ]
	 * </pre>
	 *
	 * @return self
	 */
	private function init(array $params): self
	{
		if ($params['ID'])
		{
			$this->primaryId = (int)$params['ID'];
		}
		if ($params['BOT_ID'])
		{
			$this->botId = (int)$params['BOT_ID'];
		}
		if ($params['DIALOG_ID'])
		{
			$this->dialogId = $params['DIALOG_ID'];
		}
		if ($params['SESSION_ID'])
		{
			$this->sessionId = (int)$params['SESSION_ID'];
		}

		return $this;
	}

	/**
	 * Loads session params and init object fields.
	 *
	 * @param array $params
	 * <pre>
	 * [
	 * 	(int) BOT_ID Bot id.
	 * 	(string) DIALOG_ID Dialog id.
	 * ]
	 * </pre>
	 *
	 * @return array|null
	 */
	public function load(array $params = []): ?array
	{
		$filter = $this->init($params)->initFilter();
		if (!empty($filter))
		{
			$res = NetworkSessionTable::getList([
				'filter' => $filter
			]);
			if ($sessData = $res->fetch())
			{
				$this->init($sessData);

				return $sessData;
			}
		}

		return null;
	}

	/**
	 * Returns current session id.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * ]
	 * </pre>
	 *
	 * @return int|null
	 */
	public function getSessionId(array $params = []): ?int
	{
		if (empty($this->primaryId))
		{
			$this->load($params);
		}
		if (empty($this->sessionId))
		{
			$filter = $this->initFilter($params);
			if (!empty($filter))
			{
				$res = NetworkSessionTable::getList([
					'select' => ['ID', 'SESSION_ID'],
					'filter' => $filter
				]);
				if ($sessData = $res->fetch())
				{
					$this->init($sessData);
				}
			}
		}

		return $this->sessionId;
	}

	/**
	 * Starts dialog session.
	 *
	 * @param int $sessionId
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) SESSION_ID Current session Id.
	 * ]
	 * </pre>
	 *
	 * @return self
	 */
	public function setSessionId(int $sessionId): self
	{
		if ($sessionId > 0)
		{
			if (empty($this->primaryId))
			{
				$this->load();
			}
			$this->sessionId = $sessionId;
			$this->update(['SESSION_ID' => $this->sessionId]);
		}

		return $this;
	}

	/**
	 * Starts dialog session.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) SESSION_ID Current session Id.
	 * 	(string) GREETING_SHOWN - Y|N
	 * 	(array) MENU_STATE
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public function start(array $params = []): bool
	{
		$this->init($params);
		if (empty($this->botId) || empty($this->dialogId))
		{
			return false;
		}

		$newData = [
			'DATE_LAST_ACTIVITY' => new DateTime
		];

		if (!empty($params['GREETING_SHOWN']))
		{
			$newData['GREETING_SHOWN'] = $params['GREETING_SHOWN'];
		}
		if (array_key_exists('MENU_STATE', $params))
		{
			if (is_array($params['MENU_STATE']))
			{
				$newData['MENU_STATE'] = Main\Web\Json::encode($params['MENU_STATE']);
			}
			else
			{
				$newData['MENU_STATE'] = $params['MENU_STATE'];
			}
		}
		if (!empty($params['SESSION_ID']))
		{
			$newData['SESSION_ID'] = (int)$params['SESSION_ID'];
		}
		elseif (!empty($this->sessionId))
		{
			$newData['SESSION_ID'] = $this->sessionId;
		}

		if ($this->primaryId)
		{
			$filter = ['ID' => $this->primaryId];
		}
		else
		{
			$filter = $this->initFilter();
		}
		$res = NetworkSessionTable::getList([
			'select' => ['*'],
			'filter' => $filter
		]);
		if ($sessData = $res->fetch())
		{
			$this->primaryId = (int)$sessData['ID'];
			foreach ($newData as $field => $value)
			{
				if ($sessData[$field] === $newData[$field])
				{
					unset($newData[$field]);
				}
			}
			$this->update($newData);
		}
		else
		{
			$newData['BOT_ID'] = $this->botId;
			$newData['DIALOG_ID'] = $this->dialogId;

			$res = NetworkSessionTable::add($newData);
			if ($res->isSuccess())
			{
				$this->primaryId = (int)$res->getId();
			}
		}

		return true;
	}

	/**
	 * Updates session's parameters.
	 *
	 * @param array $params Command arguments.
	 *
	 * @return bool
	 */
	public function update(array $params): bool
	{
		if ($this->primaryId && !empty($params))
		{
			$res = NetworkSessionTable::update($this->primaryId, $params);

			return $res->isSuccess();
		}

		return false;
	}

	/**
	 * Finalizes session.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) SESSION_ID Current session Id.
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public function finish(array $params = []): bool
	{
		if (empty($this->primaryId))
		{
			$this->load($params);
		}
		$this->update([
			'SESSION_ID' => 0,
			'MENU_STATE' => null,
			'DATE_FINISH' => new DateTime(),
		]);
		$this->sessionId = null;

		return true;
	}

	/**
	 * Deletes bot's session data.
	 *
	 * @param array $params Params for session select.
	 *
	 * @return void
	 */
	public function clearSessions(array $params = []): void
	{
		$filter = $this->initFilter($params);
		if (!empty($filter))
		{
			$res = NetworkSessionTable::getList([
				'select' => ['ID'],
				'filter' => $filter
			]);
			while ($sess = $res->fetch())
			{
				NetworkSessionTable::delete($sess['ID']);
			}
		}
	}

	/**
	 * Counts active sessions.
	 *
	 * @param array $filter
	 * <pre>
	 * [
	 * 	(int) BOT_ID Bot id.
	 * ]
	 * </pre>
	 *
	 * @return int
	 */
	public function countActiveSessions(array $filter = []): int
	{
		if (!isset($filter['=BOT_ID']))
		{
			$filter['=BOT_ID'] = $this->botId;
		}

		$filter['>SESSION_ID'] = 0;
		$days = self::EXPIRES_DAYS;
		$filter['>DATE_CREATE'] = (new \Bitrix\Main\Type\DateTime())->add("-{$days}D");

		return NetworkSessionTable::getCount($filter);
	}

	/**
	 * Returns filter for query.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * ]
	 * </pre>
	 *
	 * @return array
	 */
	private function initFilter(array $params = []): array
	{
		$filter = [];
		if (!empty($params['BOT_ID']))
		{
			$filter['=BOT_ID'] = (int)$params['BOT_ID'];
			unset($params['BOT_ID']);
		}
		elseif (!empty($this->botId))
		{
			$filter['=BOT_ID'] = $this->botId;
		}

		if (!empty($params['DIALOG_ID']))
		{
			$filter['=DIALOG_ID'] = $params['DIALOG_ID'];
			unset($params['DIALOG_ID']);
		}
		elseif (!empty($this->dialogId))
		{
			$filter['=DIALOG_ID'] = $this->dialogId;
		}

		foreach ($params as $key => $value)
		{
			if (
				!empty($value)
				&& NetworkSessionTable::getEntity()->hasField(trim($key, '<>!=@~%*'))
			)
			{
				$filter[$key] = $value;
			}
		}

		return $filter;
	}

	/**
	 * Deletes ancient sessions.
	 *
	 * @return string
	 */
	public static function clearDeprecatedSessions(): string
	{
		$days = self::EXPIRES_DAYS;
		$res = NetworkSessionTable::getList([
			'select' => ['ID'],
			'filter' => [
				'<DATE_LAST_ACTIVITY' => (new DateTime())->add("- {$days} days")
			]
		]);
		while ($sess = $res->fetch())
		{
			NetworkSessionTable::delete($sess['ID']);
		}

		return __METHOD__. '();';
	}
}
