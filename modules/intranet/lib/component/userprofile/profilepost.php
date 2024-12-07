<?php
namespace Bitrix\Intranet\Component\UserProfile;

use Bitrix\Blog\PostSocnetRightsTable;
use Bitrix\Main\Engine\Response\HtmlContent;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ProfilePost
{
	private $permissions;
	private $profileId;
	private $pathToPostEdit;
	private $pathToUser;

	public function __construct($params)
	{
		if (!empty($params['permissions']))
		{
			$this->permissions = $params['permissions'];
		}
		if (!empty($params['profileId']))
		{
			$this->profileId = intval($params['profileId']);
		}
		if (!empty($params['pathToPostEdit']))
		{
			$this->pathToPostEdit = $params['pathToPostEdit'];
		}
		if (!empty($params['pathToUser']))
		{
			$this->pathToUser = $params['pathToUser'];
		}
	}

	private function getPermissions()
	{
		return $this->permissions;
	}

	private function getProfileId()
	{
		return $this->profileId;
	}

	private function getPathToPostEdit()
	{
		return $this->pathToPostEdit;
	}

	private function getPathToUser()
	{
		return $this->pathToUser;
	}

	public function getPostId()
	{
		$result = false;

		if (Loader::includeModule('blog'))
		{
			$blogGroupId = Option::get('socialnetwork', 'userbloggroup_id', false);
			if($blogGroupId)
			{
				$res = PostSocnetRightsTable::getList([
					'filter' => [
						'=ENTITY' => 'UP'.$this->getProfileId(),
					],
					'select' => [ 'POST_ID' ]
				]);
				if($blogRightsFields = $res->fetch())
				{
					$result = intval($blogRightsFields['POST_ID']);
				}
			}
		}

		return $result;
	}

	public function getStub()
	{
		global $USER, $USER_FIELD_MANAGER;

		$result = [];

		if (ModuleManager::isModuleInstalled('blog'))
		{
			$blogGroupId = Option::get('socialnetwork', 'userbloggroup_id', false);
			if ($blogGroupId)
			{
				if ($postId = $this->getPostId())
				{
					$result['POST_ID'] = $postId;
				}

				$result['URL_EDIT'] = \CComponentEngine::makePathFromTemplate(
					$this->getPathToPostEdit(),
					[
						"user_id" => $this->getProfileId(),
						"post_id" => (!empty($result['POST_ID']) ? $result['POST_ID'] : 0)
					]
				);
			}

			$permissions = $this->getPermissions();

			if (
				$permissions['edit']
				|| $USER->getId() == $this->getProfileId()
			)
			{
				$result["UID"] = randString(4);
				$result["POST_PROPERTIES"] = [
					"DATA" => [],
					"SHOW" => "N"
				];

				$postFieldsList = $USER_FIELD_MANAGER->getUserFields("BLOG_POST", 0, LANGUAGE_ID);

				$postPropertyList = $this->getProfileBlogPostPropertyList();

				foreach ($postFieldsList as $FIELD_NAME => $postField)
				{
					if (!in_array($FIELD_NAME, $postPropertyList))
					{
						continue;
					}

					$postField["EDIT_FORM_LABEL"] = $postField["EDIT_FORM_LABEL"] <> '' ? $postField["EDIT_FORM_LABEL"] : $postField["FIELD_NAME"];
					$postField["~EDIT_FORM_LABEL"] = $postField["EDIT_FORM_LABEL"];
					$postField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($postField["EDIT_FORM_LABEL"]);
					$result["POST_PROPERTIES"]["DATA"][$FIELD_NAME] = $postField;
				}

				if (!empty($result["POST_PROPERTIES"]["DATA"]))
				{
					$result["POST_PROPERTIES"]["SHOW"] = "Y";
				}

				$formId = "postProfileForm".$result["UID"];

				$result["formParams"] = [
					"FORM_ID" => $formId,
					"SHOW_MORE" => "Y",
					"PARSER" => [
						"Bold", "Italic", "Underline", "Strike", "ForeColor",
						"FontList", "FontSizeList", "RemoveFormat", "Quote",
						"Code", "CreateLink",
						"Image", "UploadFile",
						"InputVideo",
						"Table", "Justify", "InsertOrderedList",
						"InsertUnorderedList",
						"Source", "MentionUser", "Spoiler"
					],
					"BUTTONS" => [
						"UploadFile",
						"CreateLink",
						"InputVideo",
						"Quote", "MentionUser"
					],
					"TEXT" => [
						"NAME" => "profilepost",
						"VALUE" => "",
						"HEIGHT" => "80px"
					],
					"UPLOAD_FILE" => (isset($result["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]) ? $result["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"] : false),
					"UPLOAD_WEBDAV_ELEMENT" => [],
					"UPLOAD_FILE_PARAMS" => [
						"width" => 400,
						"height" => 400
					],
					"FILES" => [
						"VALUE" => [],
						"DEL_LINK" => false,
						"SHOW" => "N"
					],
					"SMILES" => (Loader::includeModule('blog') ? \CBlogSmile::getSmilesList() : []),
					"LHE" => [
						"id" => "id".$formId,
						"documentCSS" => "body {color:#434343;}",
						"iframeCss" => "html body {padding-left: 14px!important; line-height: 18px!important;}",
						"ctrlEnterHandler" => "__logSubmitCommentForm".$result["UID"],
						"fontSize" => "14px",
						"bInitByJS" => true,
						"height" => 80
					],
					"PROPERTIES" => [
						array_merge(
							(
							isset($result["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_URL_PRV"])
							&& is_array($result["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_URL_PRV"])
								? $result["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_URL_PRV"]
								: []
							),
							[ 'ELEMENT_ID' => 'url_preview_'.$formId ]
						)
					],
					"SELECTOR_VERSION" => 2,
					"HIDE_CHECKBOX_ALLOW_EDIT" => 'Y'
				];
			}
		}

		return $result;
	}

	private function getProfileBlogPostPropertyList()
	{
		$result = [
//			'UF_BLOG_POST_URL_PRV'
		];

		if (ModuleManager::isModuleInstalled('disk'))
		{
			$result[] = 'UF_BLOG_POST_FILE';
		}

		return $result;
	}

	private function getProfileBlogPostUF($params)
	{
		global $USER_FIELD_MANAGER;

		$result = [];

		if (!is_array($params))
		{
			return $result;
		}

		$postId = (!empty($params['postId']) ? intval($params['postId']) : 0);
		if ($postId <= 0)
		{
			return $result;
		}

		$postPropertyList = $this->getProfileBlogPostPropertyList();

		$uf = $USER_FIELD_MANAGER->getUserFields("BLOG_POST", $postId, LANGUAGE_ID);
		if (is_array($uf))
		{
			foreach($uf as $key => $field)
			{
				if (in_array($key, $postPropertyList))
				{
					$result[$key] = $field;
				}
			}
		}

		return $result;
	}

	private function getProfileBlogPostUFRendered($params = [])
	{
		global $APPLICATION;

		$postId = (!empty($params['postId']) ? intval($params['postId']) : 0);
		$ufList = (!empty($params['ufList']) && is_array($params['ufList']) ? $params['ufList'] : []);
		$detailText = (isset($params['detailText']) ? trim($params['detailText']) : '');

		$inlineAttachmentsList = [];
		if (
			$postId > 0
			&& !empty($ufList)
			&& $detailText <> ''
			&& preg_match_all('/\[DISK\sFILE\sID=([n]*)(\d+)\]/', $detailText, $matches)
		)
		{
			foreach($matches[2] as $key => $value)
			{
				$inlineAttachmentsList[] = [
					'ID' => $value,
					'KEY' => ($matches[1][$key] === 'n' ? 'OBJECT_ID' : 'ID')
				];
			}

			if (!empty($inlineAttachmentsList))
			{
				$attachedImagesList = [];

				$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();

				$res = \Bitrix\Disk\AttachedObject::getList(array(
					'filter' => array(
						'=ENTITY_TYPE' => \Bitrix\Disk\Uf\BlogPostConnector::className(),
						'ENTITY_ID' => $postId
					),
					'select' => array('ID', 'OBJECT_ID', 'OBJECT.NAME')
				));
				foreach ($res as $attachedObjectFields)
				{
					if (\Bitrix\Disk\TypeFile::isImage($userFieldManager->getAttachedObjectById($attachedObjectFields['ID'])->getFile()))
					{
						$attachedImagesList[$attachedObjectFields['ID']] = $attachedObjectFields;
					}
				}

				if (!empty($attachedImagesList))
				{
					foreach($ufList as $fieldName => $fieldData)
					{
						if (
							$fieldData['USER_TYPE_ID'] == 'disk_file'
							&& !empty($fieldData['VALUE'])
							&& is_array($fieldData['VALUE'])
						)
						{
							foreach($fieldData['VALUE'] as $key => $attachedObjectId)
							{
								if (isset($attachedImagesList[$attachedObjectId])) // is image
								{
									$foundInline = false;
									foreach($inlineAttachmentsList as $inlineAttachment)
									{
										if($attachedImagesList[$attachedObjectId][$inlineAttachment['KEY']] == $inlineAttachment['ID'])
										{
											$foundInline = true;
											break;
										}

									}

									if ($foundInline)
									{
										unset($ufList[$fieldName]['VALUE'][$key]);
									}
								}
							}
						}
					}
				}
			}
		}

		$result = [
			'CONTENT' => '',
			'CSS' => [],
			'JS' => [],
		];

		ob_start();

		foreach ($ufList as $uf)
		{
			if(!empty($uf["VALUE"]))
			{
				$APPLICATION->includeComponent(
					"bitrix:system.field.view",
					$uf["USER_TYPE"]["USER_TYPE_ID"],
					[
						"arUserField" => $uf,
						"TEMPLATE" => '',
						"LAZYLOAD" => 'N',
					],
					null,
					["HIDE_ICONS" => 'Y']
				);
			}
		}

		$result['CONTENT'] .= ob_get_clean();

		return $result;
	}

	public function getPostData($postId = 0)
	{
		$result = [];

		$postId = intval($postId);

		if (
			$postId > 0
			&& Loader::includeModule('blog')
		)
		{
			$res = \Bitrix\Blog\PostTable::getList([
				'filter' => [
					'=ID' => $postId
				],
				'select' => [ 'ID', 'TITLE', 'MICRO', 'DETAIL_TEXT' ]
			]);
			if ($postFields = $res->fetch())
			{
				$result = [
					'ID' => $postFields['ID'],
					'TITLE' => ($postFields['MICRO'] == 'Y' ? '' : $postFields['TITLE']),
					'DETAIL_TEXT' => $postFields['DETAIL_TEXT']
				];

				$result['UF'] = $this->getProfileBlogPostUF([
					'postId' => $postFields['ID']
				]);
				$result['UF_RENDERED'] = $this->getProfileBlogPostUFRendered([
					'postId' => $postFields['ID'],
					'ufList' => $result['UF'],
					'detailText' => $postFields['DETAIL_TEXT']
				]);
			}
		}

		return $result;
	}

	public function getProfileBlogPostAction($errorCollection)
	{
		$result = [];

		if ($postId = $this->getPostId())
		{
			$result['POST_ID'] = $postId;

			if (
				!empty($result['POST_ID'])
				&& Loader::includeModule('blog')
			)
			{
				$result['POST'] = $this->getPostData($result['POST_ID']);

				if (!empty($result['POST']))
				{
					$postArea = new ProfilePostArea([
						'postFields' => $result['POST'],
						'pathToUser' => $this->getPathToUser()
					]);

					return new HtmlContent($postArea, HtmlContent::STATUS_SUCCESS, $errorCollection, $result);
				}
			}
		}

		return [];
	}

	public function getProfileBlogPostFormAction()
	{
		global $USER;

		$result = [];

		$permissions = $this->getPermissions();

		if (
			(
				$permissions['edit']
				|| $USER->getId() == $this->getProfileId()
			)
			&& Loader::includeModule('blog')
		)
		{
			if ($postId = $this->getPostId())
			{
				$result = $this->getPostData($postId);
			}
			else
			{
				$result = [];
			}
		}

		return $result;
	}

	public function sendProfileBlogPostFormAction(array $params = [])
	{
		global $USER, $USER_FIELD_MANAGER;

		$result = false;

		$permissions = $this->getPermissions();

		$title = '';
		$text = (!empty($params['text']) ? trim($params['text']) : '');
		$additionalData = (!empty($params['additionalData']) ? $params['additionalData'] : []);

		if ($text == '')
		{
			return $result;
		}

		if (
			!empty($additionalData['UF_BLOG_POST_FILE'])
			&& is_array($additionalData['UF_BLOG_POST_FILE'])
		)
		{
			$additionalData['UF_BLOG_POST_FILE'] = array_filter($additionalData['UF_BLOG_POST_FILE'], function($val) { return !empty($val); });
		}

		if (
			(
				$permissions['edit']
				|| $USER->getId() == $this->getProfileId()
			)
			&& Loader::includeModule('blog')
		)
		{
			$postFilesList = [];

			$action = 'add';
			if ($postId = $this->getPostId())
			{
				$action = 'update';
			}

			$blogParams = [
				"GROUP_ID" => Option::get("socialnetwork", "userbloggroup_id", false, SITE_ID),
				"SITE_ID" => SITE_ID,
				"USER_ID" => $this->getProfileId(),
				"CREATE" => "Y",
			];
			if ($action == 'update')
			{
				$blogParams["CREATE"] = "Y";
			}
			$blog = \Bitrix\Blog\Item\Blog::getByUser($blogParams);

			if (!$blog)
			{
				return $result;
			}

			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			if ($postId)
			{
				$postUF = $USER_FIELD_MANAGER->GetUserFields("BLOG_POST", $postId, LANGUAGE_ID);
				if (
					!empty($postUF['UF_BLOG_POST_FILE'])
					&& !empty($postUF['UF_BLOG_POST_FILE']['VALUE'])
				)
				{
					$postFilesList = $postUF['UF_BLOG_POST_FILE']['VALUE'];
				}

				$updateFields = $this->buildBlogPostFields([
					'title' => $title,
					'text' => $text,
					'files' => $additionalData['UF_BLOG_POST_FILE']
				]);

				$result = \CBlogPost::update($postId, $updateFields);
			}
			else
			{
				$addFields = $this->buildBlogPostFields([
					'title' => $title,
					'text' => $text,
					'files' => $additionalData['UF_BLOG_POST_FILE']
				]);
				$addFields = array_merge($addFields, [
					'BLOG_ID' => $blog["ID"],
					'AUTHOR_ID' => $this->getProfileId(),
					'=DATE_CREATE' => $helper->getCurrentDateTimeFunction(),
					'=DATE_PUBLISH' => $helper->getCurrentDateTimeFunction(),
					'DETAIL_TEXT_TYPE' => 'text',
					'PUBLISH_STATUS' => BLOG_PUBLISH_STATUS_PUBLISH,
					'HAS_IMAGES' => 'N',
					'HAS_TAGS' => 'N',
					'HAS_SOCNET_ALL' => 'N',
					'SOCNET_RIGHTS' => [ 'UP'.$this->getProfileId() ]
				]);

				$postId = \CBlogPost::add($addFields);
				$result = $postId;
			}

			if (
				$result
				&& (
					!empty($additionalData['UF_BLOG_POST_FILE'])
					|| !empty($postFilesList)
				)
			)
			{
				\CBlogPost::update($postId, [
					"HAS_PROPS" => (
						!empty($additionalData['UF_BLOG_POST_FILE'])
							? 'Y'
							: 'N'
					),
					"UF_BLOG_POST_FILE" => (
						!empty($additionalData['UF_BLOG_POST_FILE'])
							? $additionalData['UF_BLOG_POST_FILE']
							: []
					)
				]);
			}
		}

		return $result;
	}

	public function deleteProfileBlogPostAction(array $params = [])
	{
		global $USER;

		$result = false;

		$permissions = $this->getPermissions();

		if (
			(
				$permissions['edit']
				|| $USER->getId() == $this->getProfileId()
			)
			&& Loader::includeModule('blog')
			&& ($postId = $this->getPostId())
		)
		{
			$result = \CBlogPost::delete($postId);
		}

		return $result;
	}

	private function buildBlogPostFields(array $params = [])
	{
		$result = [
			'MICRO' => 'N',
			'TITLE' => '',
			'DETAIL_TEXT' => ''
		];

		if (Loader::includeModule('blog'))
		{
			$title = (!empty($params['title']) ? $params['title'] : '');
			$result['DETAIL_TEXT'] = (!empty($params['text']) ? $params['text'] : '');

			if ($title == '')
			{
				$result["MICRO"] = "Y";
				$result["TITLE"] = preg_replace(
					[ "/\n+/isu", "/\s+/isu" ],
					" ",
					\blogTextParser::killAllTags($result['DETAIL_TEXT'])
				);
				$result["TITLE"] = trim($result["TITLE"], " \t\n\r\0\x0B\xA0");

				if (
					$result["TITLE"] == ''
					&& !empty($params["files"])
					&& is_array($params["files"])
				)
				{
					foreach ($params["files"] as $file)
					{
						if (!empty($file))
						{
							$result["TITLE"] = Loc::getMessage("INTRANET_USER_PROFILE_POST_TITLE_PLACEHOLDER");
							break;
						}
					}
				}
			}
		}

		return $result;
	}

}