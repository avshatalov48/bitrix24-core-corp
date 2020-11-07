<?php
namespace Bitrix\Mobile\Component\LogList;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Component\LogList\Util;

class Param
{
	protected $component;
	protected $request;

	protected $ajaxCall = false;
	protected $reloadCall = false;

	public function __construct($params)
	{
		if (!empty($params['component']))
		{
			$this->component = $params['component'];
		}

		if (!empty($params['request']))
		{
			$this->request = $params['request'];
		}
		else
		{
			$this->request = Util::getRequest();;
		}

		if (!empty($params['ajaxCall']))
		{
			$this->ajaxCall = $params['ajaxCall'];
		}

		if (!empty($params['reloadCall']))
		{
			$this->reloadCall = $params['reloadCall'];
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

	public function prepareDestinationParams(&$componentParams)
	{
		Util::checkEmptyParamInteger($componentParams, 'DESTINATION_LIMIT', 100);
		Util::checkEmptyParamInteger($componentParams, 'DESTINATION_LIMIT_SHOW', 3);
	}

	public function prepareCommentsParams(&$componentParams)
	{
		Util::checkEmptyParamInteger($componentParams, 'COMMENTS_IN_EVENT', 3);
	}

	public function prepareDimensionsParams(&$componentParams)
	{
		if (!Loader::includeModule('mobileapp'))
		{
			return;
		}

		$minDimension = min(
			[
				(int)\CMobile::getInstance()->getDevicewidth(),
				(int)\CMobile::getInstance()->getDeviceheight()
			]
		);

		if ($minDimension < 650)
		{
			$minDimension = 650;
		}
		elseif ($minDimension < 1300)
		{
			$minDimension = 1300;
		}
		else
		{
			$minDimension = 2050;
		}

		$componentParams['IMAGE_MAX_WIDTH'] = (int)(($minDimension - 100) / 2);
	}

	public function prepareCounterParams(&$componentParams)
	{
		$componentParams['SET_LOG_CACHE'] = (
			isset($componentParams['SET_LOG_CACHE'])
			&& $componentParams['LOG_ID'] <= 0
			&& !$this->ajaxCall
				? $componentParams['SET_LOG_CACHE']
				: 'N'
		);

		$componentParams['SET_LOG_COUNTER'] = (
			$componentParams['SET_LOG_CACHE'] === 'Y'
			&& (
				(
					!$this->ajaxCall
					&& \Bitrix\Main\Page\Frame::isAjaxRequest()
				)
				|| $this->reloadCall
			)
				? 'Y'
				: 'N'
		);
	}

	public function preparePageParams(&$componentParams)
	{
		$componentParams['SET_LOG_PAGE_CACHE'] = (
			$componentParams['LOG_ID'] <= 0
				? 'Y' 
				: 'N'
		);
	}

	public function prepareBehaviourParams(&$componentParams)
	{
		$request = $this->getRequest();

		$componentParams['EMPTY_PAGE'] = ($request->get('empty') === 'Y' ? 'Y' : 'N');

		if ($componentParams['EMPTY_PAGE'] === 'Y')
		{
			return;
		}

		if (
			$componentParams['IS_CRM'] === "Y"
			&& ($componentParams["CRM_ENTITY_TYPE"] <> '')
		)
		{
			$componentParams['SET_LOG_COUNTER'] = $componentParams['SET_LOG_PAGE_CACHE'] = 'N';
		}

		if (
			$componentParams['LOG_ID'] <= 0
			&& $componentParams['NEW_LOG_ID'] <= 0
			&& in_array($componentParams['FILTER'], [ 'favorites', 'my', 'important', 'work', 'bizproc', 'blog' ])
		)
		{
			$componentParams['SET_LOG_COUNTER'] = 'N';
			$componentParams['SET_LOG_PAGE_CACHE'] = 'N';
			$componentParams['USE_FOLLOW'] = 'N';
		}

		if ($componentParams['GROUP_ID'] > 0)
		{
			$componentParams['SET_LOG_PAGE_CACHE'] = 'Y';
			$componentParams['USE_FOLLOW'] = 'N';
			$componentParams['SET_LOG_COUNTER'] = 'N';
		}
		elseif (
			$componentParams['IS_CRM'] === 'Y'
			&& $componentParams['SET_LOG_COUNTER'] !== 'N'
		)
		{
		}
		elseif ($componentParams['FIND'] <> '')
		{
			$componentParams['SET_LOG_COUNTER'] = 'N';
			$componentParams['SET_LOG_PAGE_CACHE'] = 'N';
			$componentParams['USE_FOLLOW'] = 'N';
		}

		if ((int)$request->get('pagesize') > 0)
		{
			$componentParams['SET_LOG_PAGE_CACHE'] = 'N';
		}
	}

	public function prepareAvatarParams(&$componentParams)
	{
		Util::checkEmptyParamInteger($componentParams, 'AVATAR_SIZE', 100);
		Util::checkEmptyParamInteger($componentParams, 'AVATAR_SIZE_COMMENT', 100);
	}

	public function prepareNameTemplateParams(&$componentParams)
	{
		Util::checkEmptyParamString($componentParams, 'NAME_TEMPLATE', \CSite::getNameFormat());

		$componentParams['NAME_TEMPLATE_WO_NOBR'] = str_replace(
			[ '#NOBR#', '#/NOBR#' ],
			'',
			$componentParams['NAME_TEMPLATE']
		);
		$componentParams['NAME_TEMPLATE'] = $componentParams['NAME_TEMPLATE_WO_NOBR'];
	}
}
?>