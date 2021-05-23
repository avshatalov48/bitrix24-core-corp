<?

namespace Bitrix\Mobile\Livefeed;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Json;
use Bitrix\Main\IO;

class Helper
{
	public static function getBlogPostFullData(array $params = [])
	{
		global $USER, $USER_FIELD_MANAGER;

		$result = [];

		if (
			empty($params)
			|| !Loader::includeModule('socialnetwork')
			|| !Loader::includeModule('blog')
		)
		{
			return $result;
		}

		$postId = (isset($params['postId']) ? (int)$params['postId'] : 0);
		$siteId = (isset($params['siteId']) ? $params['siteId'] : SITE_ID);
		$nameTemplate = (isset($params['nameTemplate']) ? $params['nameTemplate'] : \CSite::getNameFormat(false, $siteId));
		$showLogin = (isset($params['showLogin']) && $params['showLogin'] === 'Y');
		$htmlEncode = (!isset($params['htmlEncode']) || $params['htmlEncode'] !== 'N');
		$previewImageSize = (isset($params['previewImageSize']) && (int)$params['previewImageSize'] > 0 ? (int)$params['previewImageSize'] : 144);
		$getAdditionalData = (isset($params['getAdditionalData']) && $params['getAdditionalData'] === 'Y');

		if ($postId <= 0)
		{
			return $result;
		}

		$blogPostFields = \CBlogPost::getById($postId);
		if (empty($blogPostFields))
		{
			return $result;
		}

		$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;

		$res = \CSocNetLog::getList(
			[],
			[
				'EVENT_ID' => $blogPostLivefeedProvider->getEventId(),
				'SOURCE_ID' => $postId
			],
			false,
			false,
			[ 'ID' ]
		);
		if (!($logEntryFields = $res->fetch()))
		{
			return $result;
		}

		$result['log_id'] = $logEntryFields['ID'];
		$result['post_user_id'] = $blogPostFields['AUTHOR_ID'];
		$result['PostPerm'] = \CBlogPost::getSocNetPostPerms($postId, true, $USER->getId(), $blogPostFields['AUTHOR_ID']);
		if ($result['PostPerm'] < BLOG_PERMS_FULL)
		{
			return $result;
		}

		$rights = [];
		$res = \CSocNetLogRights::getList([], [ 'LOG_ID' => $logEntryFields['ID'] ]);
		while ($rightFields = $res->fetch())
		{
			$rights[] = $rightFields['GROUP_CODE'];
		}

		$destinationsAll = \CSocNetLogTools::formatDestinationFromRights($rights, [
			'CHECK_PERMISSIONS_DEST' => 'N',
			'DESTINATION_LIMIT' => 100,
			'NAME_TEMPLATE' => $nameTemplate,
			'SHOW_LOGIN' => ($showLogin ? 'Y' : 'N'),
			'HTML_ENCODE' => ($htmlEncode ? 'Y' : 'N'),
			'GET_AVATARS' => true,
			'AVATAR_SIZE' => 100,
		]);

		$destinationsAvailable = \CSocNetLogTools::formatDestinationFromRights($rights, [
			'CHECK_PERMISSIONS_DEST' => 'Y',
			'DESTINATION_LIMIT' => 100,
			'NAME_TEMPLATE' => $nameTemplate,
			'SHOW_LOGIN' => ($showLogin ? 'Y' : 'N')
		]);

		if (count($destinationsAvailable) > 1) // not only author, so delete author
		{
			foreach($destinationsAvailable as $key => $destination)
			{
				if (
					!empty($destination['TYPE'])
					&& $destination['TYPE'] === 'U'
					&& !empty($destination['ID'])
					&& $destination['ID'] == $blogPostFields['AUTHOR_ID']
				)
				{
					unset($destinationsAvailable[$key]);
					break;
				}
			}
		}

		$destinationCodesList = [];
		foreach($destinationsAvailable as $key => $destination)
		{
			if (
				!empty($destination['TYPE'])
				&& !empty($destination['ID'])
			)
			{
				$destinationCodesList[] = $destination['TYPE'].$destination['ID'];
			}
		}

		$result['PostDestination'] = [];
		$result['PostDestinationHidden'] = [];

		foreach($destinationsAll as $key => $destination)
		{
			if (
				!empty($destination['TYPE'])
				&& !empty($destination['ID'])
			)
			{
				$destCode = $destination['TYPE'].$destination['ID'];
				if (in_array($destCode, $destinationCodesList))
				{
					$result['PostDestination'][] = $destination;
				}
				else
				{
					$result['PostDestinationHidden'][] = [
						'TYPE' => $destination['TYPE'],
						'ID' => $destination['ID']
					];
				}
			}
			else
			{
				$result['PostDestination'][] = $destination;
			}
		}

		$result['PostDetailText'] = \Bitrix\Main\Text\Emoji::decode(htmlspecialcharsback($blogPostFields['DETAIL_TEXT']));
		$result['PostTitle'] = ($blogPostFields['MICRO'] !== 'Y' ? $blogPostFields['TITLE'] : '');
		$diskOrWebDavInstalled = (ModuleManager::isModuleInstalled('disk') || ModuleManager::isModuleInstalled('webdav'));

		$ufCode = (
			$diskOrWebDavInstalled
				? 'UF_BLOG_POST_FILE'
				: 'UF_BLOG_POST_DOC'
		);

		$result['PostUFCode'] = $ufCode;

		$result['PostFiles'] = \CMobileHelper::getUFForPostForm([
			'ENTITY_TYPE' => 'BLOG_POST',
			'ENTITY_ID' => $postId,
			'UF_CODE' => $ufCode,
			'IS_DISK_OR_WEBDAV_INSTALLED' => $diskOrWebDavInstalled,
			'PREVIEW_IMAGE_SIZE' => $previewImageSize
		]);

		$result['PostBackgroundCode'] = (isset($blogPostFields['BACKGROUND_CODE']) ? $blogPostFields['BACKGROUND_CODE'] : '');

		if ($getAdditionalData)
		{
			$blogPostUserFields = $USER_FIELD_MANAGER->getUserFields('BLOG_POST', $postId, LANGUAGE_ID);

			$result['PostImportantData'] = [];
			if (isset($blogPostUserFields['UF_BLOG_POST_IMPRTNT']))
			{
				$result['PostImportantData']['value'] = ((int)$blogPostUserFields['UF_BLOG_POST_IMPRTNT']['VALUE'] > 0 ? 'Y' : 'N');
				if (isset($blogPostUserFields['UF_IMPRTANT_DATE_END']))
				{
					$result['PostImportantData']['endDate'] = MakeTimeStamp($blogPostUserFields['UF_IMPRTANT_DATE_END']['VALUE']);
				}
			}

			$result['PostGratitudeData'] = [];
			if (
				isset($blogPostUserFields['UF_GRATITUDE'])
				&& isset($blogPostUserFields['UF_GRATITUDE']['VALUE'])
				&& (int)$blogPostUserFields['UF_GRATITUDE']['VALUE'] > 0
				&& Loader::includeModule('iblock')
			)
			{
				$gratitudesIblockId = \Bitrix\Socialnetwork\Helper\Gratitude::getIblockId();
				if ($gratitudesIblockId)
				{
					$res = \Bitrix\Iblock\ElementPropertyTable::getList([
						'filter' => [
							'=IBLOCK_ELEMENT_ID' => (int)$blogPostUserFields['UF_GRATITUDE']['VALUE'],
							'=PROPERTY.CODE' => [ 'GRATITUDE', 'USERS' ]
						],
						'runtime' => [
							new ReferenceField(
								'PROPERTY',
								'\Bitrix\Iblock\PropertyTable',
								[ '=this.IBLOCK_PROPERTY_ID' => 'ref.ID' ],
								[ 'join_type' => 'INNER' ]
							)
						],
						'select' => [
							'IBLOCK_PROPERTY_ID',
							'VALUE',
							'VALUE_ENUM',
							'VALUE_XML_ID' => 'ENUM.XML_ID',
							'PROPERTY_CODE' => 'PROPERTY.CODE'
						]
					]);

					while($prop = $res->fetch())
					{
						switch($prop['PROPERTY_CODE'])
						{
							case 'GRATITUDE':
								$result['PostGratitudeData']['gratitude'] = $prop['VALUE_XML_ID'];
								break;
							case 'USERS':
								if (!isset($result['PostGratitudeData']['employees']))
								{
									$result['PostGratitudeData']['employees'] = [];
								}
								$result['PostGratitudeData']['employees'][] = [
									'id' => (int)$prop['VALUE'],
									'title' => '',
									'subtitle' => '',
									'imageUrl' => '',
								];
								break;
							default:
						}
					}

					if (
						!empty($result['PostGratitudeData']['employees'])
						&& is_array($result['PostGratitudeData']['employees'])
					)
					{
						$userData = [];
						$res = UserTable::getList([
							'filter' => [
								'=ID' => array_map(function($item) { return $item['id']; }, $result['PostGratitudeData']['employees'])
							],
							'select' => [ 'ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'WORK_POSITION', 'PERSONAL_PHOTO', 'PERSONAL_GENDER' ]
						]);
						while($userFields = $res->fetch())
						{
							$userData[(int)$userFields['ID']] = $userFields;
						}

						foreach($result['PostGratitudeData']['employees'] as $key => $user)
						{
							if (!isset($userData[$user['id']]))
							{
								continue;
							}

							$result['PostGratitudeData']['employees'][$key]['title'] = \CUser::formatName(\CSite::getNameFormat(), $userData[$user['id']], true, false);
							$result['PostGratitudeData']['employees'][$key]['subtitle'] =  $userData[$user['id']]['WORK_POSITION'];

							$fileId = (int)$userData[$user['id']]['PERSONAL_PHOTO'];
							if ($fileId <= 0)
							{
								switch ($userData[$user['id']]['PERSONAL_GENDER'])
								{
									case 'M':
										$suffix = 'male';
										break;
									case 'F':
										$suffix = 'female';
										break;
									default:
										$suffix = 'unknown';
								}
								$fileId = Option::get('socialnetwork', 'default_user_picture_'.$suffix, false, SITE_ID);
							}

							if ($fileId > 0)
							{
								$imageFile = \CFile::getFileArray($fileId);
								if ($imageFile !== false)
								{
									$file = \CFile::resizeImageGet(
										$imageFile,
										[ 'width' => 150, 'height' => 150 ],
										BX_RESIZE_IMAGE_EXACT,
										false
									);
									$result['PostGratitudeData']['employees'][$key]['imageUrl'] = \CHTTP::URN2URI(\CHTTP::urnEncode($file['src']));
								}
							}
						}
					}
				}
			}

			$result['PostVoteData'] = [];
			if (
				isset($blogPostUserFields['UF_BLOG_POST_VOTE'])
				&& isset($blogPostUserFields['UF_BLOG_POST_VOTE']['VALUE'])
				&& (int)$blogPostUserFields['UF_BLOG_POST_VOTE']['VALUE'] > 0
				&& Loader::includeModule('vote')
			)
			{
				if (
					($userFieldManager = \Bitrix\Vote\Uf\Manager::getInstance($blogPostUserFields['UF_BLOG_POST_VOTE']))
					&& ($attach = $userFieldManager->loadFromAttachId((int)$blogPostUserFields['UF_BLOG_POST_VOTE']['VALUE']))
				)
				{
					$result['PostVoteData']['questions'] = [];

					foreach ($attach['QUESTIONS'] as $question)
					{
						$answers = [];

						foreach ($question['ANSWERS'] as $answer)
						{
							$answers[] = [
								'value' => $answer['MESSAGE']
							];
						}

						$result['PostVoteData']['questions'][] = [
							'value' => $question['QUESTION'],
							'allowMultiSelect' => ($question['FIELD_TYPE'] === '1' ? 'Y' : 'N'),
							'answers' => $answers
						];
					}
				}
			}
		}

		return $result;
	}

	public static function getSiteName()
	{
		return (Context::getCurrent()->getRequest()->isHttps() ? 'https' : 'http') . '://' . ((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : Option::get('main', 'server_name', $_SERVER['SERVER_NAME']));
	}

	public static function getMedalsData()
	{
		$result = [];

		$mobileSourceDir = Application::getInstance()->getPersonalRoot() . '/templates/mobile_app/images/lenta/medal';
		$sourceDirPath = Application::getDocumentRoot() . $mobileSourceDir;
		$folder = new IO\Directory($sourceDirPath);

		if(!$folder->isExists())
		{
			return $result;
		}

		$backgroundColor = '#FFFFFF';
		$descriptionFilePath = Application::getDocumentRoot() . $mobileSourceDir . '/description.json';
		$descriptionFile = new IO\File($descriptionFilePath);
		if($descriptionFile->isExists())
		{
			$descriptionContent = IO\File::getFileContents($descriptionFilePath);
			if ($descriptionContent !== false)
			{
				try
				{
					$descriptionData = Json::decode($descriptionContent);
					if (isset($descriptionData['mobileBackgroundColor']))
					{
						$backgroundColor = $descriptionData['mobileBackgroundColor'];
					}
				}
				catch (\Exception $e)
				{
				}
			}
		}

		$items = $folder->getChildren();
		foreach ($items as $item)
		{
			if (!$item->isDirectory())
			{
				continue;
			}

			$folderName = $item->getName();

			if (preg_match('/^(\d+)_(.+)$/', $folderName, $matches))
			{
				$result[$matches[2]] =  [
					'sort' => $matches[1],
					'name' => Loc::getMessage('MOBILE_LIVEFEED_MEDAL_NAME_' . mb_strtoupper($matches[2])),
					'medalUrl' => $mobileSourceDir . '/' . $folderName . '/medal_mobile.svg',
					'medalSelectorUrl' => $mobileSourceDir . '/' . $folderName . '/medal_selector_mobile_mono.svg',
					'backgroundUrl' => $mobileSourceDir . '/' . $folderName.'/background_mobile_mono.svg',
					'backgroundColor' => $backgroundColor
				];
			}
		}

		return $result;
	}

	public static function getBackgroundData()
	{
		$result = [];

		$sourceDir = Application::getInstance()->getPersonalRoot() . '/js/ui/livefeed/background/src/css/images';
		$sourceDirPath = Application::getDocumentRoot() . $sourceDir;
		$folder = new IO\Directory($sourceDirPath);

		if(!$folder->isExists())
		{
			return $result;
		}

		$result['folder'] = $sourceDir;
		$result['images'] = [];

		$items = $folder->getChildren();
		foreach ($items as $item)
		{
			if (!$item->isDirectory())
			{
				continue;
			}

			$folderName = $item->getName();

			$result['images'][$folderName] =  [
				'originalUrl' => $sourceDir . '/' . $folderName . '/full.jpg',
				'resizedUrl' => $sourceDir . '/' . $folderName . '/half.jpg'
			];
		}

		ksort($result['images']);

		return $result;
	}
}