<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Integration\Disk\AttachedObjectService;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class CommentController extends EntityController
{
	protected static $parser = null;

	const UF_FIELD_NAME = 'CRM_TIMELINE';
	const UF_COMMENT_FILE_NAME = 'UF_CRM_COMMENT_FILES';

	private static function getParser()
	{
		if (self::$parser == null && Loader::includeModule('blog'))
		{
			self::$parser = new \blogTextParser(LANGUAGE_ID);
		}
		if (self::$parser == null && Loader::includeModule('forum'))
		{
			self::$parser = new \forumTextParser(LANGUAGE_ID);
		}
		if (self::$parser == null && Loader::includeModule('socialnetwork'))
		{
			self::$parser = new \logTextParser(LANGUAGE_ID);
		}
		if (self::$parser == null)
		{
			self::$parser = new \CTextParser();
		}

		//@codingStandardsIgnoreStart
		self::$parser->arUserfields = array();
		//@codingStandardsIgnoreEnd

		return self::$parser;
	}

	public static function getFileBlock($id, $options = ['MOBILE' => 'N'])
	{
		$id = (int)$id;
		if ($id <= 0)
			return null;

		$fileFields = null;
		if (ModuleManager::isModuleInstalled('disk'))
			$fileFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(self::UF_FIELD_NAME, $id);

		$html = "";

		if ($fileFields && !empty($fileFields[self::UF_COMMENT_FILE_NAME]['VALUE']))
		{
			$rules["USERFIELDS"] = $fileFields[self::UF_COMMENT_FILE_NAME];

			if ($fileFields)
			{
				ob_start();
				$GLOBALS['APPLICATION']->IncludeComponent(
					'bitrix:system.field.view',
					$fileFields[self::UF_COMMENT_FILE_NAME]["USER_TYPE"]["USER_TYPE_ID"],
					array(
						"PUBLIC_MODE" => false,
						"ENABLE_AUTO_BINDING_VIEWER" => true,
						"LAZYLOAD" => 'Y',
						'MOBILE' => $options['MOBILE'],
						'arUserField' => $fileFields[self::UF_COMMENT_FILE_NAME]
					),
					null,
					array("HIDE_ICONS" => "Y")
				);

				$html = ob_get_clean();
			}
		}

		return $html;
	}

	public static function getFiles(int $id, int $ownerId, int $ownerTypeId): array
	{
		$ids = self::getFilesIds($id);

		if (empty($ids))
		{
			return [];
		}

		return self::loadFilesData($ids, $ownerId, $ownerTypeId);
	}

	private static function getFilesIds(int $id): array
	{
		if ($id <= 0 || !ModuleManager::isModuleInstalled('disk'))
		{
			return [];
		}

		$fileFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(self::UF_FIELD_NAME, $id);

		if (!isset($fileFields[self::UF_COMMENT_FILE_NAME]))
		{
			return [];
		}

		$ids = $fileFields[self::UF_COMMENT_FILE_NAME]['VALUE'] ?? [];

		if (empty($ids))
		{
			return [];
		}

		return (is_array($ids) ? $ids : [$ids]);
	}

	private static function loadFilesData(array $ids, int $ownerId, int $ownerTypeId): array
	{
		return AttachedObjectService::loadFilesData($ids, $ownerId, $ownerTypeId);
	}

	public static function convertToHtml(array $data, array $options = null)
	{
		$parser = static::getParser();

		$rules = array(
			"HTML" => "N",
			"ALIGN" => "Y",
			"ANCHOR" => "Y", "BIU" => "Y",
			"IMG" => "Y", "QUOTE" => "Y",
			"CODE" => "Y", "FONT" => "Y",
			"LIST" => "Y", "SMILES" => "Y",
			"NL2BR" => "Y", "MULTIPLE_BR" => "N",
			"VIDEO" => "Y", "LOG_VIDEO" => "N",
			"SHORT_ANCHOR" => "Y"
		);

		if (($options['INCLUDE_FILES'] ?? null) === 'Y' && ModuleManager::isModuleInstalled('disk'))
		{
			$fileFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(self::UF_FIELD_NAME, $data['ID']);

			if ($fileFields && !empty($fileFields[self::UF_COMMENT_FILE_NAME]['VALUE']))
			{
				$rules["USERFIELDS"] = $fileFields[self::UF_COMMENT_FILE_NAME];

				if (isset($options['LAZYLOAD']) &&$options['LAZYLOAD'] === 'Y')
				{
					$parser->LAZYLOAD = 'Y';
				}
				//@codingStandardsIgnoreStart
				$parser->arUserfields = $fileFields;
				//@codingStandardsIgnoreEnd
			}
		}

		//@codingStandardsIgnoreStart
		$parser->bMobile = (($options['MOBILE'] ?? null) === 'Y');
		//@codingStandardsIgnoreEnd
		$data['TEXT'] = $parser::clearAllTags($data['COMMENT']);
		if (self::$parser instanceof \blogTextParser)
		{
			$data['TEXT'] = $parser::killAllTags($data['COMMENT']);
			$data['COMMENT'] = $parser->convert(
				$data['COMMENT'],
				array(),
				$rules
			);
		}
		elseif (self::$parser instanceof \forumTextParser)
		{
			$data['COMMENT'] = $parser->convert(
				$data['COMMENT'],
				$rules,
				"html",
				array()
			);
		}
		elseif (self::$parser instanceof \logTextParser)
		{
			$data['COMMENT'] = $parser->convert(
				$data['COMMENT'],
				array(),
				$rules
			);
		}
		elseif (!empty(self::$parser))
		{
			$data['COMMENT'] = $parser->convertText($data['COMMENT']);
		}

		$data['COMMENT'] = \Bitrix\Main\Text\Emoji::decode($data['COMMENT']);
	//	$data['COMMENT'] = preg_replace('/\[[^\]]+\]/', '', $data['COMMENT']);

		return $data;
	}

	public static function extractPlainText($sourceText, array $options = null)
	{
		$parser = static::getParser();
		if(self::$parser instanceof \blogTextParser)
		{
			return $parser::killAllTags($sourceText);
		}
		elseif(self::$parser instanceof \forumTextParser || self::$parser instanceof \logTextParser)
		{
			return $parser::clearAllTags($sourceText);
		}
		elseif(!empty(self::$parser))
		{
			return $parser::clearAllTags($sourceText);
		}

		return preg_replace('/\[[^\]]+\]/', '', $sourceText);
	}

	public static function getMentionIds($text)
	{
		preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/isu", $text, $mentionList);
		$mentionList = $mentionList[1];
		if (empty($mentionList) || !is_array($mentionList))
			return array();

		$mentionList = array_unique($mentionList);
		return $mentionList;
	}

	public function onCreate($id, array $params = [])
	{
		$id = (int)$id;
		if ($id <= 0)
		{
			throw new ArgumentException('ID must be greater than zero.', 'ID');
		}

		$this->onSave($id, $params);

		$this->sendPullEventOnAdd(
			new ItemIdentifier($params['ENTITY_TYPE_ID'], $params['ENTITY_ID']),
			$id,
			$params['AUTHOR_ID'] ?? null
		);
	}

	public function onModify($id, array $params = [])
	{
		$id = (int)$id;
		if ($id <= 0)
		{
			throw new ArgumentException('ID must be greater than zero.', 'ID');
		}

		$ownerTypeId = (int)($params['ENTITY_TYPE_ID'] ?? 0);
		$ownerId = (int)($params['ENTITY_ID'] ?? 0);
		if ($ownerId <= 0 || !\CCrmOwnerType::IsDefined($ownerTypeId))
		{
			throw new ArgumentException('Owner ID and owner type ID must be greater than zero.', 'ID');
		}

		$this->onSave($id, $params);

		$this->sendPullEventOnUpdate(
			new ItemIdentifier($ownerTypeId, $ownerId),
			$id
		);
	}

	public function onDelete($id, array $params = array())
	{
		$id = (int)$id;
		if($id <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('ID must be greater than zero.', 'ID');
		}

		$this->sendPullEventOnDelete(new ItemIdentifier($params['ENTITY_TYPE_ID'], $params['ENTITY_ID']), $id);
	}

	protected function onSave($id, array $data)
	{
		if (
			isset($data['COMMENT']) &&
			(int)$data['ENTITY_TYPE_ID'] &&
			(int)$data['ENTITY_ID'] &&
			Loader::includeModule('im')
		)
		{
			$this->sendMentions($id, $data);
		}
	}

	private function sendMentions($id, array $data)
	{
		$mentionList = self::getMentionIds($data['COMMENT']);

		if (!empty($mentionList))
		{
			$currentUser = \CCrmSecurityHelper::GetCurrentUserID();
			$parser = static::getParser();
			if ($parser instanceof \forumTextParser)
			{
				$data['COMMENT'] = $parser->clearAllTags($data['COMMENT']);
			}
			else
			{
				$data['COMMENT'] = preg_replace('/\[[^\]]+\]/', '', $data['COMMENT']);
			}
			$data['COMMENT'] = trim($data['COMMENT']);
			$cuttedComment = TruncateText($data['COMMENT'], 255);
			$userDB = \CUser::GetByID($currentUser);
			if (\CCrmOwnerType::isPossibleDynamicTypeId((int)$data['ENTITY_TYPE_ID']))
			{
				$entityName = \CCrmOwnerType::CommonDynamicName;
			}
			else
			{
				$entityName = \CCrmOwnerType::ResolveName($data['ENTITY_TYPE_ID']);
			}
			$genderSuffix = "";
			if ($user = $userDB->Fetch())
			{
				switch ($user["PERSONAL_GENDER"])
				{
					case "M":
						$genderSuffix = "_M";
						break;
					case "F":
						$genderSuffix = "_F";
						break;
				}
			}

			$info = array();
			\CCrmOwnerType::TryGetInfo($data['ENTITY_TYPE_ID'], $data['ENTITY_ID'], $info);
			$info['LINK'] = \CCrmOwnerType::GetEntityShowPath($data['ENTITY_TYPE_ID'], $data['ENTITY_ID']);
			$nameLink = "<a href=\"" . $info['LINK'] . "\" class=\"bx-notifier-item-action\">" . htmlspecialcharsbx($info['CAPTION']) . "</a>";
			$phrase = "CRM_ENTITY_TITLE_" . $entityName;
			if ($entityName === \CCrmOwnerType::SmartInvoiceName)
			{
				$phrase = "CRM_ENTITY_TITLE_" . \CCrmOwnerType::InvoiceName;
			}

			$notifyMessageCallback = static function (?string $languageId = null) use (
				$genderSuffix,
				$nameLink,
				$cuttedComment,
				$phrase,
			) {
				$entityTitle = Loc::getMessage($phrase, ["#ENTITY_NAME#" => $nameLink], $languageId);
				if (!$entityTitle)
				{
					$entityTitle = Loc::getMessage(
						$phrase . '_MSGVER_1',
						["#ENTITY_NAME#" => $nameLink],
						$languageId,
					);
				}

				return Loc::getMessage(
					"CRM_COMMENT_IM_MENTION_POST" . $genderSuffix,
					[
						"#COMMENT#" => $cuttedComment,
						"#ENTITY_TITLE#" => $entityTitle
					],
					$languageId,
				);
			};

			$oldMentionList = $data['OLD_MENTION_LIST'] ?? [];
			foreach ($mentionList as $mentionId)
			{
				$mentionId = (int)$mentionId;
				if ($mentionId <= 0 || $currentUser === $mentionId || in_array( $mentionId, $oldMentionList ))
					continue;

				\CIMNotify::Add(array(
					'TO_USER_ID' => $mentionId,
					'FROM_USER_ID' => $currentUser,
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'crm',
					'NOTIFY_EVENT' => 'mention',
					'NOTIFY_TAG' => 'CRM|MESSAGE_TIMELINE_MENTION|' . $id,
					'NOTIFY_MESSAGE' => $notifyMessageCallback
				));
			}
		}
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$data['HAS_FILES'] = $data['SETTINGS']['HAS_FILES'] ?? 'N';
		if (
			$data['HAS_FILES'] === 'Y'
			&& preg_match("/\\[(\\/?)(file|document id|disk file id)(.*?)\\]/isu", $data['COMMENT'])
		)
		{
			$data['HAS_INLINE_ATTACHMENT'] = 'Y';
		}

		$type = $options['TYPE'] ?? Context::DESKTOP;

		if ($type === Context::MOBILE)
		{
			$data['COMMENT'] = TextHelper::sanitizeBbCode($data['COMMENT']);
		}
		else
		{
			$data = self::convertToHtml($data);
		}

		return parent::prepareHistoryDataModel($data, $options);
	}

	public function prepareSearchContent(array $params)
	{
		$result = '';
		if(isset($params['COMMENT']))
		{
			$result = self::extractPlainText($params['COMMENT']);
		}

		if (
			isset($params['SETTINGS']['HAS_FILES'])
			&& $params['SETTINGS']['HAS_FILES'] === 'Y'
			&& Loader::includeModule('disk')
		)
		{
			$fileFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(self::UF_FIELD_NAME, $params['ID']);
			$attachedIds = $fileFields[self::UF_COMMENT_FILE_NAME]['VALUE'];
			if (!empty($attachedIds))
			{
				$fileIds = [];
				$attachedObjects = AttachedObject::getList([
					'select' => ['OBJECT_ID'],
					'filter' => ['=ID' => $attachedIds]
				]);
				while ($attach = $attachedObjects->fetch())
				{
					$fileIds[] = $attach['OBJECT_ID'];
				}
				if (!empty($fileIds))
				{
					$fileRaw = File::getList([
						'filter' => ['=ID' => $fileIds],
						'select' => ['NAME']
					]);
					while ($file = $fileRaw->fetch())
					{
						$result .= " {$file['NAME']}";
					}
				}
			}
		}

		return $result;
	}
}
