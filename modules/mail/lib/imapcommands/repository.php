<?php
namespace Bitrix\Mail\ImapCommands;

use Bitrix\Mail;
use Bitrix\Main;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Mail\Internals;

class Repository
{
	private $mailboxId;
	private $messagesIds;

	public function __construct($mailboxId, $messagesIds)
	{
		$this->mailboxId = $mailboxId;
		$this->messagesIds = $messagesIds;
	}

	public function getMailbox($mailboxUserId = null)
	{
		return Mail\MailboxTable::getUserMailbox($this->mailboxId, $mailboxUserId);
	}

	public function deleteOldMessages($folderCurrentName)
	{
		$connection = Main\Application::getInstance()->getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$sql = 'DELETE from ' . Mail\MailMessageUidTable::getTableName() .
			' WHERE MAILBOX_ID = ' . intval($this->mailboxId) .
			" AND DIR_MD5 = '" . $sqlHelper->forSql(md5($folderCurrentName)) . "'" .
			' AND MSG_UID = 0;';
		$connection->query($sql);
		return $connection->getAffectedRowsCount();
	}

	public function markMessagesUnseen($messages, $mailbox)
	{
		$this->setMessagesSeen('N', $messages, $mailbox);
	}

	public function markMessagesSeen($messages, $mailbox)
	{
		$this->setMessagesSeen('Y', $messages, $mailbox);
	}

	protected function setMessagesSeen($isSeen, $messages, $mailbox)
	{
		$messagesIds = [];

		foreach ($this->messagesIds as $index => $messageId)
		{
			$messagesIds[$index] = $messageId;
		}

		if (empty($messagesIds) || empty($messages) || empty($mailbox))
		{
			return;
		}

		$mailsData = [];

		foreach ($messages as $messageData)
		{
			$mailsData[] = [
				'HEADER_MD5' => $messageData['HEADER_MD5'],
				'MAILBOX_USER_ID' => $mailbox['USER_ID'],
				'IS_SEEN' => $isSeen,
			];
		}

		$mailboxId = intval($this->mailboxId);

		Mail\MailMessageUidTable::updateList(
			[
				'=MAILBOX_ID' => $mailboxId,
				'@ID' => $messagesIds,
			],
			[
				'IS_SEEN' => $isSeen,
			],
			$mailsData
		);

		$dirId = Internals\MailboxDirectoryTable::getList([
			'runtime' => array(
				new Main\ORM\Fields\Relations\Reference(
				'UID',
				'Bitrix\Mail\MailMessageUidTable',
					[
						'=this.DIR_MD5' => 'ref.DIR_MD5',
						'=this.MAILBOX_ID' => 'ref.MAILBOX_ID',
					],
					[
						'join_type' => 'INNER',
					]
				),
			),
			'select' => [
				'ID',
			],
			'filter' => [
				'@UID.ID' => $messagesIds,
				'=MAILBOX_ID' => $mailboxId,
			],
			'limit' => 1,
		])->fetchAll();

		if(isset($dirId[0]['ID']))
		{
			$keyRowsForDirAndMailbox = [
				['MAILBOX_ID' => $mailboxId, 'ENTITY_TYPE' => 'MAILBOX','ENTITY_ID' => $mailboxId],
				['MAILBOX_ID' => $mailboxId, 'ENTITY_TYPE' => 'DIR','ENTITY_ID' => $dirId[0]['ID']]
			];

			foreach ($keyRowsForDirAndMailbox as $keyRow)
			{
				$filter = [
					'=MAILBOX_ID' => $keyRow['MAILBOX_ID'],
					'=ENTITY_TYPE' => $keyRow['ENTITY_TYPE'],
					'=ENTITY_ID' => $keyRow['ENTITY_ID'],
				];
				if(Internals\MailCounterTable::getCount($filter))
				{
					$value = (int)Internals\MailCounterTable::getList([
						'select' => [
							'VALUE',
						],
						'filter' => $filter,
					])->fetchAll()[0]['VALUE'];

					$rowValue = ['VALUE' => ($isSeen === 'Y' ? $value - count($messagesIds) : $value + count($messagesIds))];
					Internals\MailCounterTable::update($keyRow, $rowValue);
				}
			}
		}
	}

	public function updateMessageFieldsAfterMove($messages, $folderNewName, $mailbox)
	{
		$messagesIds = [];
		foreach ($messages as $message)
		{
			$messagesIds[] = $message['ID'];
		}
		if (empty($messagesIds))
		{
			return;
		}

		$mailsData = [];
		foreach ($messages as $messageData)
		{
			$mailsData[] = [
				'HEADER_MD5' => $messageData['HEADER_MD5'],
				'MAILBOX_USER_ID' => $mailbox['USER_ID']
			];
		}

		// @TODO: make a log optional
		/*$messagesForRemove = Mail\MailMessageUidTable::getList([
			'runtime' => [
			   new Main\ORM\Fields\Relations\Reference(
				   'B_MAIL_MESSAGE', Mail\MailMessageTable::class, [
				   '=this.MAILBOX_ID' => 'ref.MAILBOX_ID',
				   '=this.MESSAGE_ID' => 'ref.ID',
			   ], [
					   'join_type' => 'INNER',
				   ]
			   ),
			],
			'select' => [
			   'MESSAGE_ID',
			   'MAILBOX_ID',
			   'DIR_MD5',
			   'DIR_UIDV',
			   'MSG_UID',
			   'INTERNALDATE',
			   'HEADER_MD5',
			   'SESSION_ID',
			   'TIMESTAMP_X',
			   'DATE_INSERT',
			   'B_MAIL_MESSAGE.DATE_INSERT',
			   'B_MAIL_MESSAGE.FIELD_DATE',
			   'B_MAIL_MESSAGE.FIELD_FROM',
			   'B_MAIL_MESSAGE.SUBJECT',
			   'B_MAIL_MESSAGE.MSG_ID',
		   ],
		   'filter' => [
			   '=MAILBOX_ID' => intval($this->mailboxId),
			   '@ID' => $messagesIds,
		   ],
		])->fetchAll();

		for($i=0; $i < count($messagesForRemove); $i++)
		{
			foreach ($messagesForRemove[$i] as $key => $value)
			{
				if ($messagesForRemove[$i][$key] instanceof \Bitrix\Main\Type\DateTime)
				{
					$messagesForRemove[$i][$key] = $messagesForRemove[$i][$key]->toString();
				}
			}
		}

		if(count($messagesForRemove)>0)
		{
			$toLog = [
				'filter'=>[
					'cause' => 'updateMessageFieldsAfterMove',
					'=MAILBOX_ID' => intval($this->mailboxId),
					'@ID' => $messagesIds,
				],
				'removedMessages'=>$messagesForRemove,
			];
			AddMessage2Log($toLog);
		}*/

		Mail\MailMessageUidTable::updateList(
			[
				'=MAILBOX_ID' => intval($this->mailboxId),
				'@ID' => $messagesIds,
			],
			[
				'MSG_UID' => 0,
				'DIR_MD5' => md5($folderNewName),
			],
			$mailsData
		);
	}

	public function addMailsToBlacklist($blacklistMails, $userId)
	{
		$result = new Main\Result();
		$result->setData([Mail\BlacklistTable::addMailsBatch($blacklistMails, $userId)]);
		return $result;
	}

	/**
	 * Used to delete small sample of messages from the database ( at the user's request ).
	 *
	 * @param array $messagesToDelete Each message in the array must be represented by an associative array containing the "MESSAGE_ID" field.
	 * @param $mailboxUserId
	 *
	 * @return null - if messages are missing
	 */
	public function deleteMailsCompletely($messagesToDelete, $mailboxUserId)
	{
		// @TODO: make a log optional
		/*$messageToLog = [
			'cause' => 'deleteMailsCompletely',
			'filter' => 'manual deletion of messages',
			'removedMessages'=>$messagesToDelete,
		];
		AddMessage2Log($messageToLog);*/

		$ids = array_map(
			function ($mail)
			{
				return intval($mail['MESSAGE_ID']);
			},
			$messagesToDelete
		);
		if (empty($ids))
		{
			return;
		}
		$mailFieldsForEvent = [];

		foreach ($messagesToDelete as $index => $item)
		{
			$mailFieldsForEvent[] = [
				'HEADER_MD5' => $item['HEADER_MD5'],
				'MESSAGE_ID' => $item['MESSAGE_ID'],
				'MAILBOX_USER_ID' => $mailboxUserId,
			];
		}
		Mail\MailMessageUidTable::deleteList(
			[
				'=MAILBOX_ID' => $this->mailboxId,
				'@MESSAGE_ID' => $ids,
			],
			$mailFieldsForEvent
		);

		// @TODO: use API
		$connection = Main\Application::getInstance()->getConnection();
		$connection->query(
			'DELETE from ' . Mail\MailMessageTable::getTableName() .
			' WHERE ID IN (' . implode(',', $ids) . ');'
		);
	}

	public function getMessages()
	{
		if (empty($this->messagesIds))
		{
			return [];
		}
		$messages = [];
		$messagesSelected = Mail\MailMessageUidTable::query()
			->addSelect('MESSAGE_ID')
			->where('MAILBOX_ID', $this->mailboxId)
			->whereIn('ID', $this->messagesIds)
			->whereNot('MSG_UID', 0)
			->where('MESSAGE_ID', '>', 0)
			->addFilter('==DELETE_TIME', 0)
			->exec()
			->fetchAll();
		if ($messagesSelected)
		{
			$messagesSelectedIds = array_map(
				function ($item)
				{
					return $item['MESSAGE_ID'];
				},
				$messagesSelected
			);
			if (empty($messagesSelectedIds))
			{
				return [];
			}
			$messages = Mail\MailMessageUidTable::query()
				->registerRuntimeField(
					'',
					new ReferenceField(
						'ref',
						Mail\MailMessageTable::class,
						['=this.MESSAGE_ID' => 'ref.ID']
					)
				)
				->addSelect('ID')
				->addSelect('MAILBOX_ID')
				->addSelect('DIR_MD5')
				->addSelect('DIR_UIDV')
				->addSelect('MSG_UID')
				->addSelect('HEADER_MD5')
				->addSelect('IS_SEEN')
				->addSelect('SESSION_ID')
				->addSelect('MESSAGE_ID')
				->addSelect('ref.FIELD_FROM', 'FIELD_FROM')
				->whereIn('MESSAGE_ID', $messagesSelectedIds)
				->where('MAILBOX_ID', $this->mailboxId)
				->whereNot('MSG_UID', 0)
				->exec()
				->fetchAll();
		}

		return $messages;
	}
}
