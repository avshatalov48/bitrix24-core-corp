<?php
namespace Bitrix\Landing\Transfer\Import;

use \Bitrix\Landing\Site as SiteCore;
use \Bitrix\Landing\Landing as LandingCore;
use \Bitrix\Landing\Transfer\AppConfiguration;
use \Bitrix\Landing\Block;
use \Bitrix\Landing\File;
use \Bitrix\Landing\Hook;
use \Bitrix\Landing\Template;
use \Bitrix\Landing\TemplateRef;
use \Bitrix\Landing\Manager;
use \Bitrix\Landing\Syspage;
use \Bitrix\Landing\Internals\BlockTable;
use \Bitrix\Rest\Marketplace;
use \Bitrix\Rest\Configuration;
use \Bitrix\Main\Event;
use \Bitrix\Main\ORM\Data\AddResult;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Site
{
	/**
	 * Returns export url for the site.
	 * @param string $type Site type.
	 * @return string
	 */
	public static function getUrl(string $type): string
	{
		if (!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return '';
		}
		return Marketplace\Url::getConfigurationImportManifestUrl(
			AppConfiguration::PREFIX_CODE . strtolower($type)
		);
	}

	/**
	 * Returns prepare manifest settings for export.
	 * @param Event $event Event instance.
	 * @return array|null
	 */
	public static function getInitManifest(Event $event): ?array
	{
		return [
			'NEXT' => false
		];
	}

	/**
	 * Import site data.
	 * @param array $data Site data.
	 * @param Configuration\Structure $structure Instance for getting files.
	 * @return AddResult
	 */
	protected static function importSite(array $data, Configuration\Structure $structure): AddResult
	{
		$code = isset($data['CODE']) ? $data['CODE'] : null;

		// clear old keys
		$notAllowedKeys = [
			'ID', 'DOMAIN_ID', 'DATE_CREATE', 'DATE_MODIFY',
			'CREATED_BY_ID', 'MODIFIED_BY_ID', 'CODE'
		];
		foreach ($notAllowedKeys as $key)
		{
			if (isset($data[$key]))
			{
				unset($data[$key]);
			}
		}

		// if site path are exist, create random one
		if ($code)
		{
			$check = SiteCore::getList([
				'select' => [
					'ID'
				],
				'filter' => [
					'=CODE' => $code
				]
			]);
			if ($check->fetch())
			{
				$code = null;
			}
		}
		if (!$code)
		{
			$code = strtolower(\randString(10));
		}
		$data['CODE'] = $code;
		$data['ACTIVE'] = 'Y';

		// files
		$files = [];
		foreach (Hook::HOOKS_CODES_FILES as $hookCode)
		{
			if (
				isset($data['ADDITIONAL_FIELDS'][$hookCode]) &&
				$data['ADDITIONAL_FIELDS'][$hookCode] > 0
			)
			{
				$unpackFile = $structure->getUnpackFile($data['ADDITIONAL_FIELDS'][$hookCode]);
				if ($unpackFile)
				{
					$files[] = $data['ADDITIONAL_FIELDS'][$hookCode] = AppConfiguration::saveFile(
						$unpackFile
					);
				}
				else
				{
					unset($data['ADDITIONAL_FIELDS'][$hookCode]);
				}
			}
		}

		$res = SiteCore::add($data);

		// save files to site
		if ($files && $res->isSuccess())
		{
			foreach ($files as $fileId)
			{
				File::addToSite($res->getId(), $fileId);
			}
		}

		return $res;
	}

	/**
	 * Process one export step.
	 * @param Event $event Event instance.
	 * @return array|null
	 */
	public static function nextStep(Event $event): ?array
	{
		$code = $event->getParameter('CODE');
		$content = $event->getParameter('CONTENT');
		$ratio = $event->getParameter('RATIO');
		$contextUser = $event->getParameter('CONTEXT_USER');
		$structure = new Configuration\Structure($contextUser);
		$return = [
			'RATIO' => isset($ratio[$code]) ? $ratio[$code] : [],
			'ERROR_EXCEPTION' => []
		];

		if (!isset($content['~DATA']))
		{
			return null;
		}

		$data = $content['~DATA'];

		// site import
		if (!isset($data['SITE_ID']))
		{
			if (!isset($data['TYPE']))
			{
				$data['TYPE'] = 'PAGE';
			}
			\Bitrix\Landing\Site\Type::setScope($data['TYPE']);
			$res = self::importSite($data, $structure);
			if ($res->isSuccess())
			{
				$return['RATIO']['BLOCKS'] = [];
				$return['RATIO']['BLOCKS_PENDING'] = [];
				$return['RATIO']['LANDINGS'] = [];
				$return['RATIO']['FOLDERS'] = [];
				$return['RATIO']['TEMPLATES'] = [];
				$return['RATIO']['TEMPLATE_LINKING'] = [];
				$return['RATIO']['SITE_ID'] = $res->getId();
				$return['RATIO']['TYPE'] = $data['TYPE'];
				$return['RATIO']['SYS_PAGES'] = $data['SYS_PAGES'];
				$return['RATIO']['SPECIAL_PAGES'] = [
					'LANDING_ID_INDEX' => isset($data['LANDING_ID_INDEX']) ? (int)$data['LANDING_ID_INDEX'] : 0,
					'LANDING_ID_404' => isset($data['LANDING_ID_404']) ? (int)$data['LANDING_ID_404'] : 0,
					'LANDING_ID_503' => isset($data['LANDING_ID_INDEX']) ? (int)$data['LANDING_ID_503'] : 0
				];
				if (isset($data['TEMPLATES']) && is_array($data['TEMPLATES']))
				{
					$return['RATIO']['TEMPLATES'] = $data['TEMPLATES'];
				}
				if (isset($data['TPL_ID']) && $data['TPL_ID'])
				{
					$return['RATIO']['TEMPLATE_LINKING'][-1 * $res->getId()] = [
						'TPL_ID' => (int) $data['TPL_ID'],
						'TEMPLATE_REF' => isset($data['TEMPLATE_REF'])
										? (array) $data['TEMPLATE_REF']
										: []
					];
				}
				return $return;
			}
			else
			{
				$return['ERROR_EXCEPTION'] = $res->getErrorMessages();
				return $return;
			}
		}
		// something went wrong, site was not created
		else if (!isset($return['RATIO']['SITE_ID']))
		{
			$return['ERROR_EXCEPTION'][] = Loc::getMessage('LANDING_IMPORT_ERROR_SITE_ID_NOT_FOUND');
			return $return;
		}
		// pages import
		else
		{
			return Landing::importLanding($event);
		}
	}

	/**
	 * Sets replace array to the pending blocks.
	 * @param array $pendingIds Pending block ids.
	 * @param array $replace Array for future linking.
	 * @return void
	 */
	protected static function linkingPendingBlocks(array $pendingIds, array $replace): void
	{
		$replace = base64_encode(serialize($replace));
		$res = BlockTable::getList([
			'select' => [
				'ID'
			],
			'filter' => [
				'ID' => $pendingIds
			]
		]);
		while ($row = $res->fetch())
		{
			$blockInstance = new Block($row['ID']);
			if ($blockInstance->exist())
			{
				$blockInstance->updateNodes([
					AppConfiguration::SYSTEM_COMPONENT_REST_PENDING => [
						'REPLACE' => $replace
					]
				]);
				$blockInstance->save();
			}
		}
	}

	/**
	 * Final step.
	 * @param Event $event
	 * @return array
	 */
	public static function onFinish(Event $event): array
	{
		$ratio = $event->getParameter('RATIO');

		if (isset($ratio['LANDING']))
		{
			\Bitrix\Landing\Rights::setGlobalOff();
			$siteType = $ratio['LANDING']['TYPE'];
			$siteId = $ratio['LANDING']['SITE_ID'];
			$blocks = $ratio['LANDING']['BLOCKS'];
			$landings = $ratio['LANDING']['LANDINGS'];
			$blocksPending = $ratio['LANDING']['BLOCKS_PENDING'];
			$folders = $ratio['LANDING']['FOLDERS'];
			$templatesOld = $ratio['LANDING']['TEMPLATES'];
			$templateLinking = $ratio['LANDING']['TEMPLATE_LINKING'];
			$specialPages = $ratio['LANDING']['SPECIAL_PAGES'];
			$sysPages = $ratio['LANDING']['SYS_PAGES'];
			\Bitrix\Landing\Site\Type::setScope($siteType);
			if ($blocksPending)
			{
				self::linkingPendingBlocks($blocksPending, [
					'block' => $blocks,
					'landing' => $landings
				]);
			}
			// move pages to the folders if needed
			foreach ($folders as $lid => $folderId)
			{
				if (isset($landings[$lid]) && isset($landings[$folderId]))
				{
					LandingCore::update($landings[$lid], [
						'FOLDER_ID' => $landings[$folderId]
					]);
				}
			}
			// gets actual layouts
			$templatesNew = [];
			$templatesRefs = [];
			$res = Template::getList([
				'select' => [
					'ID', 'XML_ID'
				]
			]);
			while ($row = $res->fetch())
			{
				$templatesNew[$row['XML_ID']] = $row['ID'];
			}
			foreach ($templatesOld as $oldId => $oldXmlId)
			{
				if (is_string($oldXmlId) && isset($templatesNew[$oldXmlId]))
				{
					$templatesRefs[$oldId] = $templatesNew[$oldXmlId];
				}
			}
			// set layouts to site and landings
			foreach ($templateLinking as $entityId => $templateItem)
			{
				$tplId = $templateItem['TPL_ID'];
				$tplRefs = [];
				if (isset($templatesRefs[$tplId]))
				{
					$tplId = $templatesRefs[$tplId];
					foreach ($templateItem['TEMPLATE_REF'] as $areaId => $landingId)
					{
						if (intval($landingId) && isset($landings[$landingId]))
						{
							$tplRefs[$areaId] = $landings[$landingId];
						}
					}
					if ($entityId < 0)
					{
						SiteCore::update(-1 * $entityId, [
							'TPL_ID' => $tplId
						]);
						TemplateRef::setForSite(-1 * $entityId, $tplRefs);
					}
					else
					{
						LandingCore::update($entityId, [
							'TPL_ID' => $tplId
						]);
						TemplateRef::setForLanding($entityId, $tplRefs);
					}
				}
			}
			// replace links in blocks content
			if ($blocks)
			{
				$replace = [];
				ksort($blocks);
				ksort($landings);
				$blocks = array_reverse($blocks, true);
				$landings = array_reverse($landings, true);
				foreach ($blocks as $oldId => $newId)
				{
					$replace['/#block' . $oldId . '([^\d]{1})/'] = '#block' . $newId . '$1';
				}
				foreach ($landings as $oldId => $newId)
				{
					$replace['/#landing' . $oldId . '([^\d]{1})/'] = '#landing' . $newId . '$1';
				}

				$res = BlockTable::getList([
					'select' => [
						'ID', 'CONTENT'
					],
					'filter' => [
						'ID' => array_values($blocks),
						'!ID' => $blocksPending
					]
				]);
				while ($row = $res->fetch())
				{
					$count = 0;
					$row['CONTENT'] = preg_replace(
						array_keys($replace),
						array_values($replace),
						$row['CONTENT'],
						-1,
						$count
					);
					if ($count)
					{
						BlockTable::update($row['ID'], [
							'CONTENT' => $row['CONTENT']
						]);
					}
				}
			}
			// replace special pages in site (503, 404)
			if ($specialPages && $siteId)
			{
				foreach ($specialPages as $code => $id)
				{
					$specialPages[$code] = isset($landings[$id]) ? $landings[$id] : 0;
				}
				SiteCore::update($siteId, $specialPages);
			}
			// system pages
			if (is_array($sysPages) && $siteId)
			{
				foreach ($sysPages as $sysPage)
				{
					if (isset($landings[$sysPage['LANDING_ID']]))
					{
						Syspage::set($siteId, $sysPage['TYPE'], $landings[$sysPage['LANDING_ID']]);
					}
				}
			}

			\Bitrix\Landing\Rights::setGlobalOn();

			return [
				'CREATE_DOM_LIST' => [
					[
						'TAG' => 'a',
						'DATA' => [
							'attrs' => [
								'class' => 'ui-btn ui-btn-lg ui-btn-primary',
								'data-issite' => 'Y',
								'href' => '#' . $siteId,
								'target' => '_top'
							],
							'text' => Loc::getMessage('LANDING_IMPORT_FINISH_GOTO_SITE')
						]
					]
				],
				'ADDITIONAL' => [
					'id' => $siteId,
					'publicUrl' => \Bitrix\Landing\Site::getPublicUrl($siteId),
					'imageUrl' => \Bitrix\Landing\Site::getPreview($siteId)
				]
			];
		}

		\Bitrix\Landing\Rights::setGlobalOn();

		return [];
	}
}