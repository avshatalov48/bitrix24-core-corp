<?php
namespace Bitrix\Landing\PublicAction;

use \Bitrix\Landing\Hook;
use \Bitrix\Landing\Manager;
use \Bitrix\Landing\File;
use \Bitrix\Landing\Site;
use \Bitrix\Landing\Block as BlockCore;
use \Bitrix\Landing\TemplateRef;
use \Bitrix\Landing\Landing as LandingCore;
use \Bitrix\Landing\PublicActionResult;
use \Bitrix\Landing\Internals\HookDataTable;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Landing
{
	/**
	 * Clear disallow keys from add/update fields.
	 * @param array $fields
	 * @return array
	 */
	protected static function clearDisallowFields(array $fields)
	{
		$disallow = ['RULE', 'TPL_CODE', 'ACTIVE', 'INITIATOR_APP_CODE', 'VIEWS'];

		if (is_array($fields))
		{
			foreach ($fields as $k => $v)
			{
				if (in_array($k, $disallow))
				{
					unset($fields[$k]);
				}
			}
		}

		return $fields;
	}

	/**
	 * Get preview picture of landing.
	 * @param int $lid Id of landing.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function getPreview($lid)
	{
		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid, [
			'skip_blocks' => true
		]);

		if ($landing->exist())
		{
			$result->setResult($landing->getPreview());
		}
		$result->setError($landing->getError());

		return $result;
	}

	/**
	 * Get public url of landing.
	 * @param int $lid Id of landing.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function getPublicUrl($lid)
	{
		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid, [
			'skip_blocks' => true
		]);

		if ($landing->exist())
		{
			$result->setResult(
				$landing->getPublicUrl()
			);
		}
		$result->setError($landing->getError());

		return $result;
	}

	/**
	 * Get additional fields of landing.
	 * @param int $lid Id of landing.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function getAdditionalFields($lid)
	{
		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid, [
			'skip_blocks' => true
		]);

		if ($landing->exist())
		{
			$fields = $landing->getAdditionalFields($landing->getId());
			foreach ($fields as $key => $field)
			{
				$fields[$key] = $field->getValue();
				if (!$fields[$key])
				{
					unset($fields[$key]);
				}
			}
			$result->setResult(
				$fields
			);
		}
		$result->setError($landing->getError());

		return $result;
	}

	/**
	 * Publication of landing.
	 * @param int $lid Id of landing.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function publication($lid)
	{
		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid, [
			'skip_blocks' => true
		]);

		if ($landing->exist())
		{
			if ($landing->publication())
			{
				$result->setResult(true);
			}
		}

		$result->setError($landing->getError());

		return $result;
	}

	/**
	 * Cancel publication of landing.
	 * @param int $lid Id of landing.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function unpublic($lid)
	{
		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid, [
			'skip_blocks' => true
		]);

		if ($landing->exist())
		{
			$result->setResult(
				$landing->unpublic()
			);
		}

		$result->setError($landing->getError());

		return $result;
	}

	/**
	 * Add new block to the landing.
	 * @param int $lid Id of landing.
	 * @param array $fields Data array of block.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function addBlock($lid, array $fields)
	{
		LandingCore::setEditMode();
		Hook::setEditMode(true);

		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid);
		if ($landing->exist())
		{
			$data = array(
				'PUBLIC' => 'N',
			);
			if (isset($fields['ACTIVE']))
			{
				$data['ACTIVE'] = $fields['ACTIVE'];
			}
			if (isset($fields['CONTENT']))
			{
				$data['CONTENT'] = Manager::sanitize(
					$fields['CONTENT'],
					$bad
				);
			}
			// sort
			if (isset($fields['AFTER_ID']))
			{
				$blocks = $landing->getBlocks();
				if (isset($blocks[$fields['AFTER_ID']]))
				{
					$data['SORT'] = $blocks[$fields['AFTER_ID']]->getSort() + 1;
				}
			}
			else
			{
				$data['SORT'] = -1;
			}
			$newBlockId = $landing->addBlock(
				isset($fields['CODE']) ? $fields['CODE'] : '',
				$data
			);
			// re-sort
			$landing->resortBlocks();
			// want return content ob block
			if (
				isset($fields['RETURN_CONTENT']) &&
				$fields['RETURN_CONTENT'] == 'Y'
			)
			{
				$return = BlockCore::getBlockContent($newBlockId, false);
			}
			else
			{
				$return = $newBlockId;
			}
			$result->setResult($return);
		}
		$result->setError($landing->getError());
		return $result;
	}

	/**
	 * Delete the block from the landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function deleteBlock($lid, $block)
	{
		LandingCore::setEditMode();

		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid);
		if ($landing->exist())
		{
			$result->setResult($landing->deleteBlock($block));
			$landing->resortBlocks();
		}
		$result->setError($landing->getError());
		return $result;
	}

	/**
	 * Mark delete or not the block.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @param boolean $mark Mark.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function markDeletedBlock($lid, $block, $mark = true)
	{
		LandingCore::setEditMode();

		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid);
		if ($landing->exist())
		{
			$result->setResult(
				$landing->markDeletedBlock($block, $mark)
			);
			$landing->resortBlocks();
		}
		$result->setError($landing->getError());
		return $result;
	}

	/**
	 * Mark undelete the block.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function markUnDeletedBlock($lid, $block)
	{
		return self::markDeletedBlock($lid, $block, false);
	}

	/**
	 * Sort the block on the landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @param string $action Code: up or down.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	private static function sort($lid, $block, $action)
	{
		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid);
		if ($landing->exist())
		{
			if ($action == 'up')
			{
				$result->setResult($landing->upBlock($block));
			}
			else
			{
				$result->setResult($landing->downBlock($block));
			}
			if ($landing->getError()->isEmpty())
			{
				$landing->resortBlocks();
			}
		}
		$result->setError($landing->getError());
		return $result;
	}

	/**
	 * Sort up the block on the landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function upBlock($lid, $block)
	{
		LandingCore::setEditMode();
		return self::sort($lid, $block, 'up');
	}

	/**
	 * Sort down the block on the landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function downBlock($lid, $block)
	{
		LandingCore::setEditMode();
		return self::sort($lid, $block, 'down');
	}

	/**
	 * Show/hide the block on the landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @param string $action Code: show or hide.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	private static function activate($lid, $block, $action)
	{
		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid);
		if ($landing->exist())
		{
			if ($action == 'show')
			{
				$result->setResult($landing->showBlock($block));
			}
			else
			{
				$result->setResult($landing->hideBlock($block));
			}
		}
		$result->setError($landing->getError());
		return $result;
	}

	/**
	 * Activate the block on the landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function showBlock($lid, $block)
	{
		LandingCore::setEditMode();
		return self::activate($lid, $block, 'show');
	}

	/**
	 * Dectivate the block on the landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function hideBlock($lid, $block)
	{
		LandingCore::setEditMode();
		return self::activate($lid, $block, 'hide');
	}

	/**
	 * Copy/move other block to this landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @param array $params Params array.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	private static function changeParentOfBlock($lid, $block, array $params)
	{
		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid);
		$afterId = isset($params['AFTER_ID']) ? $params['AFTER_ID'] : 0;
		if ($landing->exist())
		{
			if ($params['MOVE'])
			{
				$res = $landing->moveBlock($block, $afterId);
			}
			else
			{
				$res = $landing->copyBlock($block, $afterId);
			}

			if (
				isset($params['RETURN_CONTENT']) &&
				$params['RETURN_CONTENT'] == 'Y'
			)
			{
				$result->setResult(array(
					'result' => $res > 0,
					'content' => BlockCore::getBlockContent($res, false)
				));
			}
			else
			{
				$result->setResult($res);
			}
		}
		$result->setError($landing->getError());
		return $result;
	}

	/**
	 * Copy other block to this landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @param array $params Params array.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function copyBlock($lid, $block, array $params = array())
	{
		if (!is_array($params))
		{
			$params = array();
		}
		$params['MOVE'] = false;
		LandingCore::setEditMode();
		return self::changeParentOfBlock($lid, $block, $params);
	}

	/**
	 * Move other block to this landing.
	 * @param int $lid Id of landing.
	 * @param int $block Block id.
	 * @param array $params Params array.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function moveBlock($lid, $block, array $params = array())
	{
		if (!is_array($params))
		{
			$params = array();
		}
		$params['MOVE'] = true;
		LandingCore::setEditMode();
		return self::changeParentOfBlock($lid, $block, $params);
	}

	/**
	 * Remove entities of Landing - images / blocks.
	 * @param int $lid Landing id.
	 * @param array $data Data for remove.
	 * @return PublicActionResult
	 */
	public static function removeEntities($lid, array $data)
	{
		$result = new PublicActionResult();

		LandingCore::setEditMode();
		$landing = LandingCore::createInstance($lid);

		if ($landing->exist())
		{
			$blocks = $landing->getBlocks();
			if (isset($data['blocks']) && is_array($data['blocks']))
			{
				foreach ($data['blocks'] as $block)
				{
					self::deleteBlock($lid, $block);
					unset($blocks[$block]);
				}
			}
			if (isset($data['images']) && is_array($data['images']))
			{
				foreach ($data['images'] as $item)
				{
					if (isset($blocks[$item['block']]))
					{
						File::deleteFromBlock($item['block'], $item['image']);
					}
				}
			}
			$result->setResult(true);
		}

		$result->setError($landing->getError());

		return $result;
	}

	/**
	 * Get available landings.
	 * @param array $params Params ORM array.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function getList(array $params = array())
	{
		$result = new PublicActionResult();
		$params = $result->sanitizeKeys($params);
		$preview = false;
		$checkArea = false;

		if (isset($params['get_preview']))
		{
			$preview = !!$params['get_preview'];
			unset($params['get_preview']);
		}

		if (isset($params['check_area']))
		{
			$checkArea = !!$params['check_area'];
			unset($params['check_area']);
		}

		if (isset($params['filter']['CHECK_PERMISSIONS']))
		{
			unset($params['filter']['CHECK_PERMISSIONS']);
		}

		$data = array();
		$res = LandingCore::getList($params);
		while ($row = $res->fetch())
		{
			if (isset($row['DATE_CREATE']))
			{
				$row['DATE_CREATE'] = (string) $row['DATE_CREATE'];
			}
			if (isset($row['DATE_MODIFY']))
			{
				$row['DATE_MODIFY'] = (string) $row['DATE_MODIFY'];
			}
			if ($preview && isset($row['ID']))
			{
				$landing = LandingCore::createInstance($row['ID'], [
					'skip_blocks' => true
				]);
				if ($landing->getDomainId() == 0)
				{
					\Bitrix\Landing\Hook::setEditMode(true);
				}
				$row['PREVIEW'] = $landing->getPreview(
					null,
					$landing->getDomainId() == 0
				);
			}
			if ($checkArea && isset($row['ID']))
			{
				$data[$row['ID']] = $row;
			}
			else
			{
				$checkArea = false;
				$data[] = $row;
			}
		}

		// landing is area?
		if ($checkArea)
		{
			$areas = TemplateRef::landingIsArea(
				array_keys($data)
			);
			foreach ($areas as $lid => $isA)
			{
				$data[$lid]['IS_AREA'] = $isA;
			}
		}

		$result->setResult(array_values($data));

		return $result;
	}

	/**
	 * Checks that page also adding in some menu.
	 * @param array $fields Landing data array.
	 * @param bool $willAdded Flag that menu item will be added.
	 * @return array
	 */
	protected static function checkAddingInMenu(array $fields, ?bool &$willAdded = null): array
	{
		$blockId = null;
		$menuCode = null;

		if (isset($fields['BLOCK_ID']))
		{
			$blockId = (int)$fields['BLOCK_ID'];
			unset($fields['BLOCK_ID']);
		}
		if (isset($fields['MENU_CODE']))
		{
			$menuCode = $fields['MENU_CODE'];
			unset($fields['MENU_CODE']);
		}

		if (!$blockId || !$menuCode || !is_string($menuCode))
		{
			return $fields;
		}

		$willAdded = true;

		LandingCore::callback('OnAfterAdd',
			function(\Bitrix\Main\Event $event) use ($blockId, $menuCode)
			{
				$primary = $event->getParameter('primary');
				$fields = $event->getParameter('fields');

				if ($primary)
				{
					$landingId = BlockCore::getLandingIdByBlockId($blockId);
					if ($landingId)
					{
						$updateData = [
							$menuCode => [
								[
									'text' => $fields['TITLE'],
									'href' => '#landing' . $primary['ID']
								]
							]
						];
						Block::updateNodes(
							$landingId,
							$blockId,
							$updateData,
							['appendMenu' => true]
						);
					}
				}
			}
		);


		return $fields;
	}

	/**
	 * Create new landing.
	 * @param array $fields Landing data.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function add(array $fields)
	{
		$result = new PublicActionResult();
		$error = new \Bitrix\Landing\Error;

		$fields = self::clearDisallowFields($fields);
		$fields['ACTIVE'] = 'N';

		$fields = self::checkAddingInMenu($fields);

		$res = LandingCore::add($fields);

		if ($res->isSuccess())
		{
			$result->setResult($res->getId());
		}
		else
		{
			$error->addFromResult($res);
			$result->setError($error);
		}

		return $result;
	}

	/**
	 * Create a page by template.
	 * @param int $siteId Site id.
	 * @param string $code Code of template.
	 * @param array $fields Landing fields.
	 * @return PublicActionResult
	 */
	public static function addByTemplate($siteId, $code, array $fields = [])
	{
		$result = new PublicActionResult();
		$error = new \Bitrix\Landing\Error;

		$willAdded = false;
		$siteId = intval($siteId);
		$fields = self::checkAddingInMenu($fields, $willAdded);

		$res = LandingCore::addByTemplate($siteId, $code, $fields);

		if ($res->isSuccess())
		{
			$result->setResult($res->getId());
			if (
				!$willAdded &&
				isset($fields['ADD_IN_MENU']) &&
				isset($fields['TITLE']) &&
				$fields['ADD_IN_MENU'] == 'Y'
			)
			{
				Site::addLandingToMenu($siteId, [
					'ID' => $res->getId(),
					'TITLE' => $fields['TITLE']
				]);
			}
		}
		else
		{
			$error->addFromResult($res);
			$result->setError($error);
		}

		return $result;
	}

	/**
	 * Update landing.
	 * @param int $lid Landing id.
	 * @param array $fields Landing new data.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function update($lid, array $fields)
	{
		$result = new PublicActionResult();
		$error = new \Bitrix\Landing\Error;

		$fields = self::clearDisallowFields($fields);

		$res = LandingCore::update($lid, $fields);

		if ($res->isSuccess())
		{
			$result->setResult(true);
		}
		else
		{
			$error->addFromResult($res);
			$result->setError($error);
		}

		return $result;
	}

	/**
	 * Delete landing.
	 * @param int $lid Landing id.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function delete($lid)
	{
		$result = new PublicActionResult();
		$error = new \Bitrix\Landing\Error;

		$res = LandingCore::delete($lid);

		if ($res->isSuccess())
		{
			$result->setResult(true);
		}
		else
		{
			$error->addFromResult($res);
			$result->setError($error);
		}

		return $result;
	}

	/**
	 * Copy landing.
	 * @param int $lid Landing id.
	 * @param int $toSiteId Site id (if you want copy in another site).
	 * @param int $toFolderId Folder id (if you want copy in some folder).
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function copy($lid, $toSiteId = null, $toFolderId = null)
	{
		$result = new PublicActionResult();

		LandingCore::disableCheckDeleted();

		$landing = LandingCore::createInstance($lid);
		$result->setResult(
			$landing->copy($toSiteId, $toFolderId)
		);
		$result->setError($landing->getError());

		LandingCore::enableCheckDeleted();

		return $result;
	}

	/**
	 * Mark entity as deleted.
	 * @param int $lid Entity id.
	 * @param boolean $mark Mark.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function markDelete($lid, $mark = true)
	{
		$result = new PublicActionResult();
		$error = new \Bitrix\Landing\Error;

		if ($mark)
		{
			$res = LandingCore::markDelete($lid);
		}
		else
		{
			$res = LandingCore::markUnDelete($lid);
		}
		if ($res->isSuccess())
		{
			$result->setResult($res->getId());
		}
		else
		{
			$error->addFromResult($res);
			$result->setError($error);
		}

		return $result;
	}

	/**
	 * Mark entity as undeleted.
	 * @param int $lid Entity id.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function markUnDelete($lid)
	{
		return self::markDelete($lid, false);
	}

	/**
	 * Upload file by url or from FILE.
	 * @param int $lid Landing id.
	 * @param string $picture File url / file array.
	 * @param string $ext File extension.
	 * @param array $params Some file params.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function uploadFile($lid, $picture, $ext = false, array $params = array())
	{
		static $internal = true;
		static $mixedParams = ['picture'];

		$result = new PublicActionResult();
		$error = new \Bitrix\Landing\Error;
		$lid = intval($lid);

		$landing = LandingCore::createInstance($lid, [
			'skip_blocks' => true
		]);

		if ($landing->exist())
		{
			$file = Manager::savePicture($picture, $ext, $params);
			if ($file)
			{
				File::addToLanding($lid, $file['ID']);
				$result->setResult(array(
					'id' => $file['ID'],
					'src' => $file['SRC']
				));
			}
			else
			{
				$error->addError(
					'FILE_ERROR',
					Loc::getMessage('LANDING_FILE_ERROR')
				);
				$result->setError($error);
			}
		}

		$result->setError($landing->getError());

		return $result;
	}

	/**
	 * Set some content to the Head section.
	 * @param int $lid Landing id.
	 * @param string $content Some content.
	 * @return \Bitrix\Landing\PublicActionResult
	 */
	public static function updateHead($lid, $content)
	{
		static $internal = true;

		$lid = intval($lid);
		$result = new PublicActionResult();
		$landing = LandingCore::createInstance($lid, [
			'skip_blocks' => true
		]);
		$result->setResult(false);

		if ($landing->exist())
		{
			// fix module security
			$content = str_replace('<st yle', '<style', $content);
			$content = str_replace('<li nk ', '<link ', $content);

			$fields = array(
				'ENTITY_ID' => $lid,
				'ENTITY_TYPE' => \Bitrix\Landing\Hook::ENTITY_TYPE_LANDING,
				'HOOK' => 'FONTS',
				'CODE' => 'CODE',
				'PUBLIC' => 'N'
			);
			$res = HookDataTable::getList(array(
				'select' => array(
					'ID', 'VALUE'
				),
				'filter' => $fields
			));
			if ($row = $res->fetch())
			{
				$existsContent = $row['VALUE'];

				// concat new fonts to the exists
				$found = preg_match_all(
					'#(<noscript>.*?<style.*?data-id="([^"]+)"[^>]*>[^<]+</style>)#is',
					$content,
					$newFonts
				);
				if ($found)
				{
					foreach ($newFonts[1] as $i => $newFont)
					{
						if (mb_strpos($existsContent, '"' . $newFonts[2][$i] . '"') === false)
						{
							$existsContent .= $newFont;
						}
					}
				}

				if ($existsContent != $row['VALUE'])
				{
					HookDataTable::update(
						$row['ID'],
						['VALUE' => $existsContent]
					);
				}
			}
			else
			{
				$fields['VALUE'] = $content;
				HookDataTable::add($fields);
			}
			$result->setResult(true);
		}

		$result->setError($landing->getError());

		return $result;
	}
}
