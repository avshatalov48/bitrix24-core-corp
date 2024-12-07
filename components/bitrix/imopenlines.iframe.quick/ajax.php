<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImOpenlines\QuickAnswers\QuickAnswer;

define("IM_AJAX_INIT", true);
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::includeModule('imopenlines'))
{
	return;
}

Loc::loadMessages(__FILE__);

class ImopenlinesIframeQuickAjaxController
{
	protected $errors = array();
	protected $action = null;
	protected $responseData = array();
	protected $requestData = array();

	/** @var \Bitrix\Main\HttpRequest $request */
	protected $request = array();

	protected function getActions()
	{
		return array(
			'search', 'edit', 'rating',
		);
	}

	protected function search()
	{
		$search = $this->requestData['SEARCH'];
		$offset = $this->requestData['OFFSET'];
		if(!$offset)
		{
			$offset = 0;
		}
		$filter = [
			[
				'LOGIC' => 'OR',
				[
					'DETAIL_TEXT' => '%'.$search.'%',
				],
				[
					'NAME' => '%'.$search.'%',
				],
			],
		];
		$sectionId = (int)$this->requestData['SECTION_ID'];
		if($sectionId > 0)
		{
			$filter['CATEGORY'] = $sectionId;
		}
		$converter = \Bitrix\Main\Text\Converter::getHtmlConverter();
		$answers = QuickAnswer::getListByUserPermissions($filter, $offset);
		$this->responseData['allCount'] = QuickAnswer::getCountByUserPermissions($filter);
		$this->responseData['result'] = [];
		foreach($answers as $answer)
		{
			$this->responseData['result'][] = [
				'name' => $converter->decode($answer->getName()),
				'text' => $converter->decode($answer->getText()),
				'id' => (int)$answer->getId(),
				'can_edit' => \CIBlockElementRights::UserHasRightTo(
					$answer->getIblock(),
					$answer->getId(),
					'element_edit'
				),
				'section' => (int)$answer->getCategory(),
			];
		}
	}

	protected function edit()
	{
		$text = $this->requestData['TEXT'];
		$converter = \Bitrix\Main\Text\Converter::getHtmlConverter();
		$text = $converter->decode($text);
		if(empty($text))
		{
			$this->errors[] = 'Text cannot be empty';
			return false;
		}
		$id = $this->requestData['ID'];
		$sectionId = (int)$this->requestData['SECTION_ID'];

		$answer = QuickAnswer::getById($id);
		if($answer)
		{
			if (!\CIBlockElementRights::UserHasRightTo($answer->getIblock(), $id, 'element_edit')) {
				$this->errors[] = Loc::getMessage('IMOP_QUICK_ANSWERS_AJAX_EDIT_EDIT');
				return false;
			}
			$answer->update(array('TEXT' => $text, 'MESSAGEID' => '', 'CATEGORY' => $sectionId));
		}
		else
		{
			$listsDataManager = new \Bitrix\ImOpenlines\QuickAnswers\ListsDataManager($this->requestData['LINE_ID']);
			$iblockId = $listsDataManager->getIblockId();
			if (!\CIBlockRights::UserHasRightTo($iblockId, $iblockId, 'section_element_bind'))
			{
				$this->errors[] = Loc::getMessage('IMOP_QUICK_ANSWERS_AJAX_EDIT_EDIT');
				return false;
			}
			$answer = QuickAnswer::add(array('TEXT' => $text, 'CATEGORY' => $sectionId));
		}
		if($answer->getId() > 0)
		{
			$this->responseData['text'] = Loc::getMessage('IMOP_QUICK_ANSWERS_AJAX_EDIT_SUCCESS');
			$this->responseData['id'] = $answer->getId();
			return true;
		}
		else
		{
			$this->errors[] = Loc::getMessage('IMOP_QUICK_ANSWERS_AJAX_EDIT_EDIT');
			return false;
		}
	}

	protected function rating()
	{
		$id = $this->requestData['ID'];
		if(empty($id))
		{
			return false;
		}

		$answer = QuickAnswer::getById($id);
		if($answer)
		{
			$answer->incrementRating();
		}
	}

	protected function prepareRequestData()
	{
		$converter = \Bitrix\Main\Text\Converter::getHtmlConverter();
		$this->requestData = array(
			'SEARCH' => $converter->encode($this->request->get('search')),
			'TEXT' => $converter->encode($this->request->get('text')),
			'ID' => (int)$this->request->get('id'),
			'SECTION_ID' => (int)$this->request->get('sectionId'),
			'OFFSET' => $converter->encode($this->request->get('offset')),
			'LINE_ID' => (int)$this->request->get('lineId'),
		);
	}

	protected function giveResponse()
	{
		global $APPLICATION;
		$APPLICATION->restartBuffer();

		header('Content-Type:application/json; charset=UTF-8');
		if($this->hasErrors())
		{
			$this->responseData['error'] = true;
			$this->responseData['text'] = implode('<br>', $this->errors);
		}
		echo \Bitrix\Main\Web\Json::encode(
			$this->responseData
		);

		\CMain::finalActions();
		exit;
	}

	protected function getActionCall()
	{
		return array($this, $this->action);
	}

	protected function hasErrors()
	{
		return count($this->errors) > 0;
	}

	protected function check()
	{
		if(!in_array($this->action, $this->getActions()))
		{
			$this->errors[] = 'Action "' . $this->action . '" not found.';
		}
		elseif(!check_bitrix_sessid() || !$this->request->isPost())
		{
			$this->errors[] = 'Security error.';
		}
		elseif(!is_callable($this->getActionCall()))
		{
			$this->errors[] = 'Action method "' . $this->action . '" not found.';
		}
		elseif(!$this->initDataManager())
		{
			$this->errors[] = 'You do not have access to canned answers list of this line';
		}

		return !$this->hasErrors();
	}

	public function exec()
	{
		$this->request = Context::getCurrent()->getRequest();
		$this->action = $this->request->get('action');

		$this->prepareRequestData();

		if($this->check())
		{
			call_user_func_array($this->getActionCall(), array($this->requestData));
		}
		$this->giveResponse();
	}

	/**
	 * Init data manager for quick answers. Returns true if user has rights to work with it.
	 *
	 * @return bool
	 */
	protected function initDataManager()
	{
		$listsDataManager = new \Bitrix\ImOpenlines\QuickAnswers\ListsDataManager($this->requestData['LINE_ID']);
		QuickAnswer::setDataManager($listsDataManager);
		return $listsDataManager->isHasRights();
	}
}

$controller = new ImopenlinesIframeQuickAjaxController();
$controller->exec();