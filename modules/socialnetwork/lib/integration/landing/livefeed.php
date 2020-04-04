<?php
namespace Bitrix\Socialnetwork\Integration\Landing;

use Bitrix\Blog\PostTable;
use Bitrix\Main;
use Bitrix\Landing;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork\WorkgroupTable;

class Livefeed extends Landing\Source\DataLoader
{
	/**
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * @return array
	 */
	private static function getOrderFields()
	{
		return [
			[
				'ID' => 'LOG_DATE',
				'NAME' => Loc::getMessage('SONET_LANDING_DYNAMIC_BLOCK_LIVEFEED_FIELD_LOG_DATE')
			]
		];
	}

	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onBuildSourceListHandler(Main\Event $event)
	{
		$siteId = null;
		$restrictions = $event->getParameter('RESTRICTIONS');

		if (!empty($restrictions['livefeed']))
		{
			if (!empty($restrictions['livefeed']['SITE_ID']))
			{
				$siteId = $restrictions['livefeed']['SITE_ID'];
			}
		}
		unset($restrictions);

		/** @var Landing\Source\Selector $selector */
		$selector = $event->getParameter('SELECTOR');

		$dataSettings = [
			'ORDER' => self::getOrderFields(),
			'FIELDS' => [
				[
					'ID' => 'TITLE',
					'NAME' => Loc::getMessage('SONET_LANDING_DYNAMIC_BLOCK_LIVEFEED_FIELD_TITLE'),
					'TYPE' => Landing\Node\Type::TEXT
				],
				[
					'ID' => 'PREVIEW_TEXT',
					'NAME' => Loc::getMessage('SONET_LANDING_DYNAMIC_BLOCK_LIVEFEED_FIELD_PREVIEW_TEXT'),
					'TYPE' => Landing\Node\Type::TEXT
				],
				[
					'ID' => 'DETAIL_TEXT',
					'NAME' => Loc::getMessage('SONET_LANDING_DYNAMIC_BLOCK_LIVEFEED_FIELD_DETAIL_TEXT'),
					'TYPE' => Landing\Node\Type::TEXT
				],
				[
					'ID' => 'PICTURE',
					'NAME' => Loc::getMessage('SONET_LANDING_DYNAMIC_BLOCK_LIVEFEED_FIELD_PICTURE'),
					'TYPE' => Landing\Node\Type::IMAGE
				],
				[
					'ID' => 'LINK',
					'NAME' => Loc::getMessage('SONET_LANDING_DYNAMIC_BLOCK_LIVEFEED_ACTIONS'),
					'TYPE' => Landing\Node\Type::LINK,
					'ACTIONS' => $selector->getDefaultLinkActions()
				]
			]
		];

		$result = [];

		$result[] = [
			'SOURCE_ID' => 'livefeed',
			'TITLE' => Loc::getMessage('SONET_LANDING_DYNAMIC_BLOCK_LIVEFEED_TITLE'),
			'TYPE' => Landing\Source\Selector::SOURCE_TYPE_COMPONENT,
			'SETTINGS' => [
				'COMPONENT_NAME' => 'bitrix:socialnetwork.landing.livefeed.selector',
				'COMPONENT_TEMPLATE_NAME' => '.default',
				'COMPONENT_PARAMS' => [
					'SITE_ID' => $siteId,
				],
				"USE_UI_TOOLBAR" => "Y", // TODO: remove this key after stable update landing
				'WRAPPER' => [
					'USE_PADDING' => false,
					'PLAIN_VIEW' => false,
					'USE_UI_TOOLBAR' => 'Y'
				]
			],
			'SOURCE_FILTER' => [],
			'DATA_SETTINGS' => $dataSettings,
			'DATA_LOADER' => __CLASS__
		];


		unset($selector);

		return new Main\EventResult(Main\EventResult::SUCCESS, $result, 'socialnetwork');
	}

	/**
	 * @return array
	 */
	private function getRightsFilter()
	{
		global $CACHE_MANAGER;

		$result = [];

		$settingsFilter = $this->getFilter();
		if (!is_array($settingsFilter))
		{
			$settingsFilter = [];
		}

		foreach ($settingsFilter as $filterField)
		{
			if (
				!empty($filterField['key'])
				&& !empty($filterField['value'])
				&& $filterField['key'] == 'GROUP_ID'
				&& $filterField['value'] != 'all'
			)
			{
				$result[] = $filterField['value'];

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->RegisterTag("landing_dynamic_filter_".$filterField['value']);
				}
			}
		}

		$groupIdList = array_filter(array_map(function($val) {
			$res = false;
			if (preg_match('/^SG(\d+)$/i', $val, $matches))
			{
				$res = intval($matches[1]);
			}

			return $res;
		}, $result), function ($val) {
			return ($val > 0);
		});

		if (empty($groupIdList))
		{
			return $result;
		}

		$result = [];
		$res = WorkgroupTable::getList([
			'filter' => [
				'=LANDING' => 'Y',
				'=ACTIVE' => 'Y',
				'@ID' => $groupIdList
			],
			'select' => [ 'ID' ]
		]);
		while ($workgroupFields = $res->fetch())
		{
			$result[] = 'SG'.$workgroupFields['ID'];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getAuthorFilter()
	{
		$result = [];

		$settingsFilter = $this->getFilter();
		if (!is_array($settingsFilter))
		{
			$settingsFilter = [];
		}

		foreach ($settingsFilter as $filterField)
		{
			if (
				!empty($filterField['key'])
				&& !empty($filterField['value'])
				&& $filterField['key'] == 'AUTHOR_ID'
				&& $filterField['value'] != 'all'
			)
			{
				$result[] = $filterField['value'];
			}
		}

		$authorIdList = array_filter(array_map(function($val) {
			$res = false;
			if (preg_match('/^U(\d+)$/i', $val, $matches))
			{
				$res = intval($matches[1]);
			}

			return $res;
		}, $result), function ($val) {
			return ($val > 0);
		});

		if (empty($authorIdList))
		{
			return $result;
		}

		$result = [];
		$res = UserTable::getList([
			'filter' => [
				'=ACTIVE' => 'Y',
				'@ID' => $authorIdList
			],
			'select' => [ 'ID' ]
		]);
		while ($userFields = $res->fetch())
		{
			$result[] = $userFields['ID'];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getElementListData()
	{
		$this->seo->clear();

		$result = [];

		$rightsFilter = $this->getRightsFilter();
		$authorFilter = $this->getAuthorFilter();

		if (empty($rightsFilter))
		{
			return $result;
		}

		$orderFields = self::getOrderFields();
		$order = $this->getOrder();

		if (!is_array($order))
		{
			$order = [];
		}

		$livefeedOrder = [];

		if (
			!empty($order)
			&& !empty($order['by'])
			&& !empty($order['order'])
		)
		{
			foreach($orderFields as $field)
			{
				if ($order['by'] == $field['ID'])
				{
					$livefeedOrder = [
						$order['by'] => $order['order']
					];
				}
			}
		}

		if (empty($livefeedOrder))
		{
			$livefeedOrder = [
				'LOG_DATE' => 'DESC'
			];
		}

		$livefeedFilter = [
			'LOG_RIGHTS' => $rightsFilter,
			'EVENT_ID' => \CSocNetLogTools::findFullSetByEventID('blog_post'),
			'<=LOG_DATE' => 'NOW',
		];

		if (
			!empty($authorFilter)
			&& is_array($authorFilter)
			&& !empty($authorFilter[0])
		)
		{
			$livefeedFilter['USER_ID'] = intval($authorFilter[0]);
		}

		$settings = [
			'order' => $livefeedOrder,
			'filter' => $livefeedFilter,
			'limit' => [
				'nTopCount' => $this->getLimit()
			]
		];

		$result = $this->getElementsInternal($settings);

		return $result;
	}

	/**
	 * @param mixed $element
	 * @return array
	 */
	public function getElementData($element)
	{
		$this->seo->clear();

		$result = [];
		if (!is_string($element) && !is_int($element))
			return $result;
		$element = (int)$element;
		if ($element <= 0)
			return $result;

		$rightsFilter = $this->getRightsFilter();
		if (empty($rightsFilter))
		{
			return $result;
		}

		$settings = [
			'order' => [],
			'filter' => [
				'SOURCE_ID' => $element,
				'LOG_RIGHTS' => $rightsFilter,
				'EVENT_ID' => \CSocNetLogTools::findFullSetByEventID('blog_post'),
				'<=LOG_DATE' => 'NOW',
			],
			'limit' => [
				'nTopCount' => 1
			]
		];

		$result = $this->getElementsInternal($settings);

		if (!empty($result))
		{
			$current = reset($result);
			if (!empty($current))
			{
				$this->seo->setTitle($current['TITLE']);
			}
			unset($current);
		}
		return $result;
	}

	/**
	 * @param array $settings
	 * @return array
	 */
	private function getElementsInternal(array $settings)
	{
		global $CACHE_MANAGER, $USER_FIELD_MANAGER;

		$result = [];

		$pathToSmile = Main\Config\Option::get("socialnetwork", "smile_page", false, SITE_ID);
		$pathToSmile = ($pathToSmile ? $pathToSmile : "/bitrix/images/socialnetwork/smile/");

		$parserParams = Array(
			"imageWidth" => 500,
			"imageHeight" => 500,
		);

		$allow = [
			"HTML" => "N",
			"ANCHOR" => "Y",
			"BIU" => "Y",
			"IMG" => "Y",
			"QUOTE" => "Y",
			"CODE" => "Y",
			"FONT" => "Y",
			"LIST" => "Y",
			"SMILES" => "Y",
			"NL2BR" => "N",
			"VIDEO" => "Y",
			"USER" => "N",
			"TAG" => "Y",
			"SHORT_ANCHOR" => "Y"
		];
		if(Main\Config\Option::get("blog","allow_video", "Y") != "Y")
		{
			$allow["VIDEO"] = "N";
		}

		$iterator = \CSocNetLog::getList(
			$settings['order'],
			$settings['filter'],
			false,
			$settings['limit'],
			[ 'SOURCE_ID' ],
			[
				'CHECK_RIGHTS' => 'N',
				'USE_FOLLOW' => 'N',
				'USE_SUBSCRIBE' => 'N',
				'USE_FAVORITES' => 'N',
			]
		);

		$blogPostIdList = [];
		while ($row = $iterator->fetch())
		{
			$blogPostIdList[] = $row['SOURCE_ID'];
		}

		$diskInstalled = Main\Loader::includeModule('disk');

		if (
			!empty($blogPostIdList)
			&& Main\Loader::includeModule('blog')
		)
		{
			if ($diskInstalled)
			{
				$driver = \Bitrix\Disk\Driver::getInstance();
				$urlManager = $driver->getUrlManager();
			}

			$iterator = PostTable::getList([
				'order' => [
					'DATE_PUBLISH' => 'DESC'
				],
				'filter' => [
					'@ID' => $blogPostIdList
				],
				'select' => [
					'ID', 'MICRO', 'TITLE', 'DETAIL_TEXT'
				]
			]);

			while ($row = $iterator->fetch())
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->RegisterTag("blog_post_".$row['ID']);
				}

				$attachedFilesList = [];

				if ($diskInstalled)
				{
					$res = \Bitrix\Disk\AttachedObject::getList(array(
						'filter' => array(
							'=ENTITY_TYPE' => \Bitrix\Disk\Uf\BlogPostConnector::className(),
							'ENTITY_ID' => $row
						),
						'select' => array('ID', 'OBJECT_ID', 'FILENAME' => 'OBJECT.NAME')
					));
					foreach ($res as $attachedObjectFields)
					{
						$attachedObjectFields['URL'] = (\Bitrix\Disk\TypeFile::isImage($attachedObjectFields['FILENAME'])
							? $urlManager->getUrlUfController('show', array('attachedId' => $attachedObjectFields['ID']))
							: ''
						);
						$attachedFilesList[] = $attachedObjectFields;
					}
				}

				$detailTextInOneString = str_replace("\r\n", "", $row["DETAIL_TEXT"]);

				$inlineAttachmentsList = [];
				if (preg_match_all('/\[DISK\sFILE\sID=([n]*)(\d+)\]/', $row["DETAIL_TEXT"], $matches))
				{
					foreach($matches[2] as $key => $value)
					{
						$inlineAttachmentsList[] = [
							'ID' => $value,
							'KEY' => ($matches[1][$key] === 'n' ? 'OBJECT_ID' : 'ID'),
							'POSITION' => strpos($detailTextInOneString, $matches[0][$key])
						];
					}
				}

				$picture = '';
				$diskPicturePosition = false;
				foreach($inlineAttachmentsList as $inlineAttachment)
				{
					foreach($attachedFilesList as $attachedFile)
					{
						if(
							$attachedFile[$inlineAttachment['KEY']] == $inlineAttachment['ID']
							&& !empty($attachedFile['URL'])
						)
						{
							$picture = [
								'alt' => (!empty($attachedFile['FILENAME']) ? $attachedFile['FILENAME'] : ''),
								'src' => $attachedFile['URL']
							];
							$diskPicturePosition = $inlineAttachment['POSITION'];
							break;
						}
					}
					if(!empty($picture))
					{
						break;
					}
				}

				$imgPattern = '/\[IMG\s+WIDTH\s*=\s*\d+\s+HEIGHT\s*=\s*\d+\s*\](.+?)\[\/IMG\]/is'.BX_UTF_PCRE_MODIFIER;
				$videoPattern = '/\[VIDEO[^\]]*](.+?)\[\/VIDEO\]/is'.BX_UTF_PCRE_MODIFIER;

				$detailText = preg_replace(
					'/\[USER\s*=\s*([^\]]*)\](.+?)\[\/USER\]/is'.BX_UTF_PCRE_MODIFIER,
					'\\2',
					$row["DETAIL_TEXT"]
				);

				if (
					preg_match_all($imgPattern, $detailText, $matches)
					&& !empty($matches[0])
					&& !empty($matches[0][0])
				)
				{
					if (
						$diskPicturePosition === false
						|| strpos($detailTextInOneString, $matches[0][0]) < $diskPicturePosition
					)
					{
						$picture = [
							'alt' => '',
							'src' => $matches[1][0]
						];
					}
				}

				$parser = new \blogTextParser(false, $pathToSmile, []);
				$parser->LAZYLOAD = "N";

				$postFields = $USER_FIELD_MANAGER->getUserFields("BLOG_POST", $row['ID'], LANGUAGE_ID);
				if (!empty($postFields["UF_BLOG_POST_FILE"]))
				{
					$parser->arUserfields = array("UF_BLOG_POST_FILE" => array_merge($postFields["UF_BLOG_POST_FILE"], ["TAG" => "DOCUMENT ID"]));
				}

				$clearedText = $detailText;

				$clearedText = preg_replace(
					[ $imgPattern, $videoPattern ],
					'',
					$clearedText
				);
				$clearedText = preg_replace(
					'/\[URL(.*?)]([^\]\s]{20,})\[\/URL\]/is'.BX_UTF_PCRE_MODIFIER,
					'',
					$clearedText
				);
				$clearedText = preg_replace(
					'/\[URL(.*?)]((?:[^\ ]{1,19}\s+)+)\[\/URL\]/is'.BX_UTF_PCRE_MODIFIER,
					'\\2',
					$clearedText
				);
				$clearedText = \blogTextParser::killAllTags($clearedText);

				$title = (
					$row["MICRO"] == "Y"
						? truncateText($clearedText, 100)
						: htmlspecialcharsEx($row["TITLE"])
				);

				$result[] = [
					'ID' => $row['ID'],
					'TITLE' => $title,
					'PREVIEW_TEXT' => truncateText($clearedText, 255),
					'DETAIL_TEXT' => $parser->convert($detailText, false, [], $allow, $parserParams),
					'PICTURE' => $picture,
				];
			}
			if (!empty($result))
			{
				usort($result, function($a, $b) use ($blogPostIdList) {
					$keyA = array_search($a['ID'], $blogPostIdList);
					$keyB = array_search($b['ID'], $blogPostIdList);
					return ($keyA > $keyB) ? +1 : -1;
				});
			}
		}

		return $result;
	}

	/**
	 * @param string $code Log right code
	 * @return void
	 */
	public static function onSocNetLogRightsAddHandler($code)
	{
		global $CACHE_MANAGER;

		if (
			defined("BX_COMP_MANAGED_CACHE")
			&& strlen($code) > 0
			&& preg_match('/^SG(\d+)$/i', $code, $matches)
			&& Main\ModuleManager::isModuleInstalled('landing')
		)
		{
			$CACHE_MANAGER->ClearByTag("landing_dynamic_filter_".$code);
		}
	}

	/**
	 * @param mixed $filter
	 * @return array
	 */
	public function normalizeFilter($filter)
	{
		if (!is_array($filter))
		{
			return [];
		}
		if (empty($filter))
		{
			return $filter;
		}

		$result = [];
		foreach ($filter as $row)
		{
			if (empty($row) || !is_array($row))
			{
				continue;
			}
			if (empty($row['key']) || empty($row['value']))
			{
				continue;
			}
			$result[] = $row;
		}
		unset($row);

		if (!empty($result))
		{
			Main\Type\Collection::sortByColumn($result, ['key' => SORT_ASC]);
		}
		return $result;
	}

	/**
	 * @param mixed $filter
	 * @return string
	 */
	public function calculateFilterHash($filter)
	{
		if (!is_array($filter))
		{
			$filter = [];
		}
		return md5(serialize($filter));
	}
}