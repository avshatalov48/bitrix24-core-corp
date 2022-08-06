<?php
namespace Bitrix\Mobile\Component\LogList;

use Bitrix\Socialnetwork\LogPageTable;
use Bitrix\Socialnetwork\Component\LogList\Util;

class Page
{
	protected $component;
	protected $processorInstance;
	protected $request;

	protected $prevPageLogIdList = [];
	protected $dateLastPageStart = null;
	protected $needSetLogPage = false;

	public function __construct($params)
	{
		if (!empty($params['component']))
		{
			$this->component = $params['component'];
		}
		if (!empty($params['processorInstance']))
		{
			$this->processorInstance = $params['processorInstance'];
		}
		if (!empty($params['request']))
		{
			$this->request = $params['request'];
		}
		else
		{
			$this->request = Util::getRequest();;
		}

	}

	public function getRequest()
	{
		return $this->request;
	}

	public function getComponent()
	{
		return $this->component;
	}

	protected function getProcessorInstance()
	{
		return $this->processorInstance;
	}

	public function setPrevPageLogIdList($value = [])
	{
		$this->prevPageLogIdList = $value;
	}
	public function getPrevPageLogIdList()
	{
		return $this->prevPageLogIdList;
	}

	public function setDateLastPageStart($value = null)
	{
		$this->dateLastPageStart = $value;
	}
	public function getDateLastPageStart()
	{
		return $this->dateLastPageStart;
	}

	public function setNeedSetLogPage($value = false)
	{
		$this->needSetLogPage = $value;
	}
	public function getNeedSetLogPage()
	{
		return $this->needSetLogPage;
	}

	public function preparePrevPageLogId()
	{
		$request = $this->getRequest();

		if ($request->get('pplogid') == '')
		{
			return;
		}

		$prevPageLogIdList = explode('|', trim($request->get('pplogid')));
		if (!is_array($prevPageLogIdList))
		{
			return;
		}

		foreach($prevPageLogIdList as $key => $val)
		{
			preg_match('/^(\d+)$/', $val, $matches);
			if (count($matches) <= 0)
			{
				unset($prevPageLogIdList[$key]);
			}
		}
		$prevPageLogIdList = array_unique($prevPageLogIdList);

		$this->setPrevPageLogIdList($prevPageLogIdList);
	}

	public function getLogPageData(&$result)
	{
		$params = $this->getComponent()->arParams;
		$processorInstance = $this->getProcessorInstance();

		if ($params['SET_LOG_PAGE_CACHE'] === 'Y')
		{
			$groupCode = ($result['COUNTER_TYPE'] <> '' ? $result['COUNTER_TYPE'] : '**');
			$res = LogPageTable::getList([
				'order' => [],
				'filter' => [
					'USER_ID' => $result['currentUserId'],
					'=SITE_ID' => SITE_ID,
					'=GROUP_CODE' => $groupCode,
					'PAGE_SIZE' => $params['PAGE_SIZE'],
					'PAGE_NUM' => $result['PAGE_NUMBER']
				],
				'select' => [ 'PAGE_LAST_DATE' ]
			]);

			if ($logPageFields = $res->fetch())
			{
				$this->setDateLastPageStart($logPageFields['PAGE_LAST_DATE']);
				$processorInstance->setFilterKey('>=LOG_UPDATE', convertTimeStamp(makeTimeStamp($logPageFields['PAGE_LAST_DATE'], \CSite::getDateFormat('FULL')) - 60*60*24*1, 'FULL'));
			}
			elseif (
				$groupCode !== '**'
				|| $result['MY_GROUPS_ONLY'] !== 'Y'
			)
			{
				$res = LogPageTable::getList([
					'order' => [
						'PAGE_LAST_DATE' => 'DESC'
					],
					'filter' => [
						'=SITE_ID' => SITE_ID,
						'=GROUP_CODE' => $groupCode,
						'PAGE_SIZE' => $params['PAGE_SIZE'],
						'PAGE_NUM' => $result['PAGE_NUMBER']
					],
					'select' => [ 'PAGE_LAST_DATE' ]
				]);

				if ($logPageFields = $res->fetch())
				{
					$this->setDateLastPageStart($logPageFields['PAGE_LAST_DATE']);
					$processorInstance->setFilterKey('>=LOG_UPDATE', convertTimeStamp(makeTimeStamp($logPageFields['PAGE_LAST_DATE'], \CSite::getDateFormat('FULL')) - 60*60*24*4, 'FULL'));
					$this->setNeedSetLogPage(true);
				}
			}
		}
	}

	public function deleteLogPageData($result)
	{
		$params = $this->getComponent()->arParams;

		if (
			count($result['arLogTmpID']) == 0
			&& $this->getDateLastPageStart() !== null
			&& Util::checkUserAuthorized()
			&& $params['SET_LOG_PAGE_CACHE'] === 'Y'
		)
		{
			\CSocNetLogPages::deleteEx($result['currentUserId'], SITE_ID, $params['PAGE_SIZE'], ($result['COUNTER_TYPE'] <> '' ? $result['COUNTER_TYPE'] : '**'));
		}
	}

	public function setLogPageData(&$result)
	{
		$params = $this->getComponent()->arParams;

		$lastEventFields = false;
		if (is_array($result['Events']))
		{
			$tmp = $result['Events'];
			$lastEventFields = array_pop($tmp);
			unset($tmp);
		}

		$dateLastPage = false;
		$result['lastPageId'] = 0;

		if (!empty($lastEventFields))
		{
			if (
				$params['USE_FOLLOW'] === 'N'
				&& $lastEventFields['LOG_UPDATE']
			)
			{
				$result['dateLastPageTS'] = makeTimeStamp($lastEventFields['LOG_UPDATE'], \CSite::getDateFormat());
				$dateLastPage = convertTimeStamp($result['dateLastPageTS'], 'FULL');
			}
			elseif ($lastEventFields['DATE_FOLLOW'])
			{
				$result['dateLastPageTS'] = MakeTimeStamp($lastEventFields['DATE_FOLLOW'], \CSite::getDateFormat());
				$dateLastPage = convertTimeStamp($result['dateLastPageTS'], 'FULL');
			}

			$result['lastPageId'] = (int)$lastEventFields['ID'];
		}

		$dateLastPageStart = $this->getDateLastPageStart();

		if (
			$params['SET_LOG_PAGE_CACHE'] === 'Y'
			&& $dateLastPage
			&& (
				$dateLastPageStart === null
				|| $dateLastPageStart !== $dateLastPage
				|| $this->getNeedSetLogPage()
			)
		)
		{
			\CSocNetLogPages::set(
				$result['currentUserId'],
				convertTimeStamp(makeTimeStamp($dateLastPage, \CSite::getDateFormat('FULL')) - $result['TZ_OFFSET'], 'FULL'),
				$params['PAGE_SIZE'],
				$result['PAGE_NUMBER'],
				SITE_ID,
				($result['COUNTER_TYPE'] <> '' ? $result['COUNTER_TYPE'] : \CUserCounter::LIVEFEED_CODE)
			);
		}
	}

}
?>