<?php

namespace Bitrix\XDImport\Update;

use Bitrix\Main\Update\Stepper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\LogCommentTable;
use Bitrix\XDImport\Integration;
use Bitrix\Socialnetwork\Item\LogIndex;
use Bitrix\Socialnetwork\LogIndexTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

final class LivefeedIndexComment extends Stepper
{
	protected static $moduleId = 'xdimport';

	public function execute(array &$result)
	{
		if (!(
			Loader::includeModule('xdimport')
			&& Loader::includeModule('socialnetwork')
			&& Option::get('xdimport', 'needLivefeedIndexComment', 'Y') === 'Y'
		))
		{
			return false;
		}

		$return = false;

		$params = Option::get('xdimport', 'livefeedindexcomment');
		$params = ($params !== '' ? @unserialize($params, [ 'allowed_classes' => false ]) : []);
		$params = (is_array($params) ? $params : []);
		if (empty($params))
		{
			$params = [
				'lastId' => 0,
				'number' => 0,
				'count' => LogCommentTable::getCount([
					'@EVENT_ID' => Integration\Socialnetwork\LogComment::getEventIdList(),
				]),
			];
		}

		if ($params['count'] > 0)
		{
			$result['title'] = Loc::getMessage('FUPD_LF_XDIMPORT_COMMENT_INDEX_TITLE');
			$result['progress'] = 1;
			$result['steps'] = '';
			$result['count'] = $params['count'];

			$res = LogCommentTable::getList([
				'order' => [ 'ID' => 'ASC' ],
				'filter' => [
					'>ID' => $params['lastId'],
					'@EVENT_ID' => Integration\Socialnetwork\LogComment::getEventIdList(),
				],
				'select' => [ 'ID', 'EVENT_ID' ],
				'offset' => 0,
				'limit' => 100,
			]);

			$found = false;
			while ($record = $res->fetch())
			{
				LogIndex::setIndex([
					'itemType' => LogIndexTable::ITEM_TYPE_COMMENT,
					'itemId' => $record['ID'],
					'fields' => $record,
				]);

				$params['lastId'] = $record['ID'];
				$params['number']++;
				$found = true;
			}

			if ($found)
			{
				Option::set('xdimport', 'livefeedindexcomment', serialize($params));
				$return = true;
			}

			$result['progress'] = (int)($params['number'] * 100 / $params['count']);
			$result['steps'] = $params['number'];

			if ($found === false)
			{
				Option::delete('xdimport', [ 'name' => 'livefeedindexcomment' ]);
				Option::set('xdimport', 'needLivefeedIndexComment', 'N');
			}
		}
		return $return;
	}
}
