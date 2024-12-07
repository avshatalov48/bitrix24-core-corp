<?php

namespace Bitrix\ImBot;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\ImBot\Bot\Network;
use Bitrix\ImBot\Model\NetworkSessionTable;

class DialogSession
{
	public const EXPIRES_DAYS = 30;

	/** @var int */
	protected $primaryId;

	/** @var int */
	protected $botId;

	/** @var string */
	protected $dialogId;

	/** @var int */
	protected $sessionId;

	/** @var int */
	protected $closeTerm;

	/** @var array */
	protected $data = [];

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
	 * 	(int) CLOSE_TERM
	 * 	(string) GREETING_SHOWN
	 * ]
	 * </pre>
	 *
	 * @return self
	 */
	private function init(array $params): self
	{
		$this->data = array_merge($this->data, $params);

		if (!empty($params['ID']))
		{
			$this->primaryId = (int)$params['ID'];
		}
		if (!empty($params['BOT_ID']))
		{
			$this->botId = (int)$params['BOT_ID'];
		}
		if (!empty($params['DIALOG_ID']))
		{
			$this->dialogId = $params['DIALOG_ID'];
		}
		if (!empty($params['SESSION_ID']))
		{
			$this->sessionId = (int)$params['SESSION_ID'];
		}
		if (!empty($params['CLOSE_TERM']))
		{
			$this->closeTerm = (int)$params['CLOSE_TERM'];
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
				'select' => [
					'ID',
					'BOT_ID',
					'DIALOG_ID',
					'SESSION_ID',
					'GREETING_SHOWN',
					'MENU_STATE',
					'DATE_CREATE',
					'DATE_FINISH',
					'DATE_LAST_ACTIVITY',
					'CLOSE_TERM',
					'CLOSED',
					'TELEMETRY_SENT',
				],
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
	 * @return mixed
	 */
	public function getParam(string $param)
	{
		return isset($this->data, $this->data[$param]) ? $this->data[$param] : null;
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
			$this->update([
				'SESSION_ID' => $this->sessionId,
				'STATUS' => Network::MULTIDIALOG_STATUS_OPEN
			]);
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
	 * 	(int) CLOSE_TERM Delay time (minutes) to close session.
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
			'DATE_LAST_ACTIVITY' => new DateTime,
			'DATE_FINISH' => null,
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

		if (!empty($params['CLOSE_TERM']))
		{
			$newData['CLOSE_TERM'] = (int)$params['CLOSE_TERM'];
		}
		elseif (!empty($this->closeTerm))
		{
			$newData['CLOSE_TERM'] = $this->closeTerm;
		}

		if (!empty($params['STATUS']))
		{
			$newData['STATUS'] = $params['STATUS'];
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
			'select' => [
				'ID',
				'BOT_ID',
				'DIALOG_ID',
				'SESSION_ID',
				'GREETING_SHOWN',
				'MENU_STATE',
				'DATE_CREATE',
				'DATE_FINISH',
				'DATE_LAST_ACTIVITY',
				'CLOSE_TERM',
				'CLOSED',
				'TELEMETRY_SENT',
			],
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
			'DATE_FINISH' => new DateTime(),
			'SESSION_ID' => 0,
			'STATUS' => Network::MULTIDIALOG_STATUS_CLOSE,
		]);

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

		$filter['=CLOSED'] = 0;
		$filter['!=SESSION_ID'] = 0;
		$days = self::EXPIRES_DAYS;
		$filter['>DATE_CREATE'] = (new \Bitrix\Main\Type\DateTime())->add("-{$days}D");
		$filter['=STATUS'] = Network::MULTIDIALOG_STATUS_OPEN;

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
	 * 	(int) SESSION_ID
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
			$key = trim($key, '<>!=@~%*');
			if (
				!empty($value)
				&& NetworkSessionTable::getEntity()->hasField($key)
			)
			{
				$filter["={$key}"] = $value;
			}
		}

		return $filter;
	}

	/**
	 * Deletes closed sessions in after term minutes operator finished.
	 *
	 * @return string
	 */
	public static function clearClosedSessions(): string
	{
		$res = NetworkSessionTable::getList([
			'select' => ['ID', 'CLOSED'],
			'filter' => [
				'!DATE_FINISH' => null,
				'>CLOSE_TERM' => 0,
				'=CLOSED' => 1,
			],
		]);
		while ($sess = $res->fetch())
		{
			NetworkSessionTable::delete($sess['ID']);
		}

		return __METHOD__. '();';
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

	/**
	 * Close old sessions.
	 *
	 * @return string
	 */
	public static function closeSessions(): string
	{
		$res = NetworkSessionTable::getList([
			'select' => ['ID', 'CLOSED'],
			'filter' => [
				'!DATE_FINISH' => null,
				'>CLOSE_TERM' => 0,
				'=CLOSED' => 1,
				'!=STATUS' => Network::MULTIDIALOG_STATUS_CLOSE,
			],
		]);

		while ($session = $res->fetch())
		{
			NetworkSessionTable::update(
				$session['ID'],
				[
					'SESSION_ID' => 0,
					'STATUS' => Network::MULTIDIALOG_STATUS_CLOSE
				]
			);
		}

		return __METHOD__. '();';
	}

	/**
	 * Set sessions statuses.
	 *
	 * @return string
	 */
	public static function setStatusToSessions(): string
	{
		self::closeSessions();

		$res = NetworkSessionTable::getList([
			'select' => ['ID', 'SESSION_ID', 'DATE_FINISH',  'CLOSED'],
			'filter' => [
				'=CLOSED' => 0,
				'=STATUS' => NULL,
			],
		]);

		while ($session = $res->fetch())
		{
			if ($session['SESSION_ID'] > 0)
			{
				$status = Network::MULTIDIALOG_STATUS_OPEN;
			}
			else
			{
				$status = Network::MULTIDIALOG_STATUS_NEW;
			}

			NetworkSessionTable::update(
				$session['ID'],
				[
					'STATUS' => $status
				]
			);
		}

		return '';
	}
}
