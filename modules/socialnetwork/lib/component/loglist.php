<?php
namespace Bitrix\Socialnetwork\Component;

use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class LogList extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	/** @var ErrorCollection errorCollection */
	protected $errorCollection;

	public function configureActions()
	{
		return array();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	* Getting array of errors.
	* @return Error[]
	*/
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new ErrorCollection();

		$params["LOG_CNT"] = (array_key_exists("LOG_CNT", $params) && intval($params["LOG_CNT"]) > 0 ? $params["LOG_CNT"] : 0);
		$params["PAGE_SIZE"] = (array_key_exists("PAGE_SIZE", $params) && intval($params["PAGE_SIZE"]) > 0 ? $params["PAGE_SIZE"] : 20);

		if (
			!empty($params['PUBLIC_MODE'])
			&& $params['PUBLIC_MODE'] == 'Y'
		)
		{
			$params['MODE'] = 'PUB';
		}
		if (!empty($params['MODE']))
		{
			if ($params['MODE'] == 'LANDING')
			{
				$params['HIDE_EDIT_FORM'] = 'Y';
				$params['SHOW_RATING'] = 'N';
				$params['USE_TASKS'] = 'N';
				$params['SHOW_EVENT_ID_FILTER'] = 'N';
				$params['USE_FAVORITES'] = 'N';
				$params['SHOW_NAV_STRING'] = 'N';
				$params['SET_LOG_PAGE_CACHE'] = 'N';
				$params['EVENT_ID'] = 'blog_post';
			}
			elseif ($params['MODE'] == 'PUB')
			{
				$params['PUBLIC_MODE'] = 'Y';
			}
		}
		else
		{
			$params['MODE'] = 'STANDARD';
			$params['USE_TASKS'] = (ModuleManager::isModuleInstalled('tasks') ? 'Y' : 'N');
		}

		return $params;
	}

	public static function getGratitudesIblockId()
	{
		static $result = null;

		if ($result === null)
		{
			$result = false;

			if (!Loader::includeModule('iblock'))
			{
				return $result;
			}

			$res = \Bitrix\Iblock\IblockTable::getList(array(
				'filter' => [
					'=CODE' => 'honour',
					'=IBLOCK_TYPE_ID' => 'structure',
				],
				'select' => [ 'ID' ]
			));
			if ($iblockFields = $res->fetch())
			{
				$result = intval($iblockFields['ID']);
			}
		}

		return $result;
	}

	public static function getGratitudesIblockData(array $params = [])
	{
		$result = [
			'BADGES_DATA' => [],
			'ELEMENT_ID_LIST' => [],
			'GRAT_VALUE' => ''
		];

		$userId = (!empty($params['userId']) && intval($params['userId']) > 0 ? intval($params['userId']) : 0);
		if ($userId <= 0)
		{
			return $result;
		}

		if (!Loader::includeModule('iblock'))
		{
			return $result;
		}

		$honourIblockId = self::getGratitudesIblockId();
		$filter = [
			'IBLOCK_ID' => $honourIblockId,
			'ACTIVE' => 'Y',
			'PROPERTY_USERS' => $userId
		];

		$gratCode = (!empty($params['gratCode']) ? $params['gratCode'] : false);
		if ($gratCode)
		{
			$res = \CIBlockPropertyEnum::getList(
				[],
				[
					"IBLOCK_ID" => $honourIblockId,
					"CODE" => "GRATITUDE",
					"XML_ID" => $gratCode
				]
			);
			if ($enumFields = $res->fetch())
			{
				$filter['PROPERTY_GRATITUDE'] = $enumFields['ID'];
				$result['GRAT_VALUE'] = $enumFields['VALUE'];
			}
		}

		$iblockElementsIdList = [];
		$badgesData = [];

		$res = \CIBlockElement::getList(
			[],
			$filter,
			false,
			false,
			[ 'ID', 'PROPERTY_GRATITUDE' ]
		);
		while($iblockElementFields = $res->fetch())
		{
			$badgeEnumId = $iblockElementFields['PROPERTY_GRATITUDE_ENUM_ID'];
			if (!isset($badgesData[$badgeEnumId]))
			{
				$badgesData[$badgeEnumId] = array(
					'NAME' => $iblockElementFields['PROPERTY_GRATITUDE_VALUE'],
					'COUNT' => 0,
					'ID' => []
				);
			}
			$badgesData[$badgeEnumId]['ID'][] = intval($iblockElementFields['ID']);
			$iblockElementsIdList[] = $iblockElementFields['ID'];
		}

		$result['BADGES_DATA'] = $badgesData;
		$result['ELEMENT_ID_LIST'] = $iblockElementsIdList;

		return $result;
	}

	public static function getGratitudesBlogData(array $params = [])
	{
		global $CACHE_MANAGER;

		$result = [
			'POST_ID_LIST' => [],
			'AUTHOR_ID_LIST' => [],
			'ELEMENT_ID_LIST' => [],
		];

		$iblockElementsIdList = (!empty($params['iblockElementsIdList']) && is_array($params['iblockElementsIdList']) ? $params['iblockElementsIdList'] : []);
		if (empty($iblockElementsIdList))
		{
			return $result;
		}

		if (!Loader::includeModule('blog'))
		{
			return $result;
		}

		$authorsIdList = [];

		$res = \Bitrix\Blog\PostTable::getList([
			'filter' => [
				'@UF_GRATITUDE' => $iblockElementsIdList
			],
			'select' => ['ID', 'AUTHOR_ID', 'UF_GRATITUDE']
		]);

		$iblockElementsIdList = [];
		while($postFields = $res->fetch())
		{
			$postIdList[] = $postFields['ID'];
			$authorsIdList[] = $postFields['AUTHOR_ID'];
			$iblockElementsIdList[] = $postFields['UF_GRATITUDE'];

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->registerTag("blog_post_".$postFields['ID']);
				$CACHE_MANAGER->registerTag("USER_CARD_".intval($postFields['AUTHOR_ID'] / TAGGED_user_card_size));
			}
		}

		$result['POST_ID_LIST'] = $postIdList;
		$result['AUTHOR_ID_LIST'] = array_unique($authorsIdList);
		$result['ELEMENT_ID_LIST'] = $iblockElementsIdList;

		return $result;
	}

	public static function setFilter(array $filter = [], array $componentResult = [])
	{
		if (!empty($componentResult['GRAT_POST_FILTER']))
		{
			unset($filter['EVENT_ID']);
			$filter['EVENT_ID'] = 'blog_post_grat';
			$filter['SOURCE_ID'] = $componentResult['GRAT_POST_FILTER'];
		}

		return $filter;
	}
}
?>