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
		global $APPLICATION;

		$search = $this->requestData['SEARCH'];
		$offset = $this->requestData['OFFSET'];
		if(!$offset)
		{
			$offset = 0;
		}
		$search = $APPLICATION->convertCharset($search, 'UTF-8', LANG_CHARSET);
		$filter = array('TEXT' => '%'.$search.'%');
		$sectionId = intval($this->requestData['SECTION_ID']);
		if($sectionId > 0)
		{
			$filter['CATEGORY'] = $sectionId;
		}
		$answers = QuickAnswer::getList($filter, $offset);
		$this->responseData['result'] = array();
		foreach($answers as $answer)
		{
			$this->responseData['result'][] = array(
				'name' => $answer->getName(),
				'text' => $answer->getText(),
				'id' => $answer->getId(),
				'section' => $answer->getCategory(),
			);
		}
		$this->responseData['allCount'] = QuickAnswer::getCount($filter);
	}

	protected function edit()
	{
		global $APPLICATION;
		$text = $this->requestData['TEXT'];
		$text = htmlspecialchars_decode($text);
		$text = $APPLICATION->convertCharset($text, 'UTF-8', LANG_CHARSET);
		if(empty($text))
		{
			$this->errors[] = 'Text cannot be empty';
			return false;
		}
		$id = $this->requestData['ID'];
		$sectionId = intval($this->requestData['SECTION_ID']);

		$answer = QuickAnswer::getById($id);
		if($answer)
		{
			$answer->update(array('TEXT' => $text, 'MESSAGEID' => '', 'CATEGORY' => $sectionId));
		}
		else
		{
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
		$this->requestData = array(
			'SEARCH' => htmlspecialcharsbx($this->request->get('search')),
			'TEXT' => htmlspecialcharsbx($this->request->get('text')),
			'ID' => intval($this->request->get('id')),
			'SECTION_ID' => intval($this->request->get('sectionId')),
			'OFFSET' => htmlspecialcharsbx($this->request->get('offset')),
			'LINE_ID' => intval($this->request->get('lineId')),
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