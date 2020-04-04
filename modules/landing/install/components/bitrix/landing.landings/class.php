<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Landing\Landing;
use \Bitrix\Landing\Manager;
use \Bitrix\Landing\Rights;
use \Bitrix\Main\Entity;

\CBitrixComponent::includeComponentClass('bitrix:landing.base');
\CBitrixComponent::includeComponentClass('bitrix:landing.filter');

class LandingLandingsComponent extends LandingBaseComponent
{
	/**
	 * Count items per page.
	 */
	const COUNT_PER_PAGE = 23;

	/**
	 * Copy some landing.
	 * @param int $id Landing id.
	 * @param array $additional Additional params.
	 * @return boolean
	 */
	protected function actionCopy($id, $additional = array())
	{
		$res = \Bitrix\Landing\PublicAction\Landing::copy(
			$id,
			isset($additional['siteId']) ? $additional['siteId'] : null,
			isset($additional['folderId']) ? $additional['folderId'] : null
		);

		if ($res->getError()->isEmpty())
		{
			return true;
		}
		else
		{
			$this->setErrors(
				$res->getError()->getErrors()
			);
		}

		return false;
	}

	/**
	 * Get previews from folder.
	 * @param int $folderId Folder id.
	 * @return array
	 */
	protected function getFolderPreviews($folderId)
	{
		$previews = array();
		$pages = $this->getLandings(array(
			'select' => array(
				'ID'
			),
			'filter' => array(
				'FOLDER_ID' => $folderId
			),
			'order' => array(
				'ID' => 'DESC'
			),
			'limit' => 4
		));
		foreach ($pages as $page)
		{
			$landing = Landing::createInstance($page['ID'], array(
				'blocks_limit' => 1
			));
			$previews[$page['ID']] = $landing->getPreview();
		}
		return $previews;
	}

	/**
	 * Base executable method.
	 * @return void
	 */
	public function executeComponent()
	{
		$init = $this->init();

		if ($init)
		{
			$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
			$deletedLTdays = Manager::getDeletedLT();

			$this->checkParam('SITE_ID', 0);
			$this->checkParam('TYPE', '');
			$this->checkParam('ACTION_FOLDER', 'folderId');
			$this->checkParam('PAGE_URL_LANDING_EDIT', '');
			$this->checkParam('PAGE_URL_LANDING_VIEW', '');
			$this->checkParam('~AGREEMENT', []);

			// check agreements for Bitrix24
			if (Manager::isB24())
			{
				$this->arResult['AGREEMENT'] = $this->arParams['~AGREEMENT'];
			}
			else
			{
				$this->arResult['AGREEMENT'] = [];
			}

			// make filter
			$filter = LandingFilterComponent::getFilter(
				LandingFilterComponent::TYPE_LANDING
			);
			$filter['SITE_ID'] = $this->arParams['SITE_ID'];
			if ($request->offsetExists($this->arParams['ACTION_FOLDER']))
			{
				$filter[] = array(
					'LOGIC' => 'OR',
					'FOLDER_ID' => $request->get($this->arParams['ACTION_FOLDER']),
					'ID' => $request->get($this->arParams['ACTION_FOLDER'])
				);
			}
			else
			{
				$filter['FOLDER_ID'] = false;
			}

			$this->arResult['IS_DELETED'] = LandingFilterComponent::isDeleted();
			$this->arResult['SITES'] = $sites = $this->getSites();

			// access
			$rights = Rights::getOperationsForSite(
				$this->arParams['SITE_ID']
			);
			$this->arResult['ACCESS_SITE'] = [
				'EDIT' => in_array(Rights::ACCESS_TYPES['edit'], $rights) ? 'Y' : 'N',
				'SETTINGS' => in_array(Rights::ACCESS_TYPES['sett'], $rights) ? 'Y' : 'N',
				'PUBLICATION' => in_array(Rights::ACCESS_TYPES['public'], $rights) ? 'Y' : 'N',
				'DELETE' => in_array(Rights::ACCESS_TYPES['delete'], $rights) ? 'Y' : 'N'
			];

			// get list
			$this->arResult['LANDINGS'] = $this->getLandings(array(
				'select' => array(
					'*',
					'DATE_MODIFY_UNIX',
					'DATE_PUBLIC_UNIX'
				),
				'filter' => $filter,
				'runtime' => array(
					new Entity\ExpressionField(
						'DATE_MODIFY_UNIX', 'UNIX_TIMESTAMP(%s)', array('DATE_MODIFY')
					),
					new Entity\ExpressionField(
						'DATE_PUBLIC_UNIX', 'UNIX_TIMESTAMP(%s)', array('DATE_PUBLIC')
					),
					new Entity\ExpressionField(
						'CHANGED', 'CASE WHEN %s > %s THEN 1 ELSE 0 END', ['DATE_MODIFY', 'DATE_PUBLIC']
					)
				),
				'order' => $this->arResult['IS_DELETED']
					? array(
						'DATE_MODIFY' => 'desc'
					)
					: array(
						'ID' => 'desc'
					),
				'navigation' => $this::COUNT_PER_PAGE
			));
			$this->arResult['NAVIGATION'] = $this->getLastNavigation();

			// base data
			foreach ($this->arResult['LANDINGS'] as &$item)
			{
				if (isset($sites[$item['SITE_ID']]))
				{
					$item['IS_HOMEPAGE'] = $item['ID'] == $sites[$item['SITE_ID']]['LANDING_ID_INDEX'];
				}
				else
				{
					$item['IS_HOMEPAGE'] = false;
				}
				if ($item['IS_HOMEPAGE'])
				{
					$item['SORT'] = PHP_INT_MAX;
				}
				else
				{
					$item['SORT'] = $item['ID'];
				}
				$landing = Landing::createInstance($item['ID'], array(
					'blocks_limit' => 1,
					'force_deleted' => true
				));
				$item['PUBLIC_URL'] = '';
				$item['PREVIEW'] = $landing->getPreview();
				if ($item['FOLDER'] == 'Y')
				{
					$item['FOLDER_PREVIEW'] = $this->getFolderPreviews($item['ID']);
				}
				if ($item['DELETED'] == 'Y')
				{
					$item['DATE_DELETED_DAYS'] = $deletedLTdays - intval((time() - $item['DATE_MODIFY']->getTimeStamp()) / 86400);
					$item['DELETE_FINISH'] = $item['DATE_DELETED_DAYS'] <= 0;//@tmp
				}
			}

			// checking areas
			$areas = \Bitrix\Landing\TemplateRef::landingIsArea(
				array_keys($this->arResult['LANDINGS'])
			);
			foreach ($this->arResult['LANDINGS'] as &$landingItem)
			{
				$landingItem['IS_AREA'] = $areas[$landingItem['ID']] === true;
			}
			unset($landingItem);

			// sort by homepage additional
			uasort($this->arResult['LANDINGS'], function($a, $b)
			{
				return ($a['SORT'] < $b['SORT']) ? 1 : -1;
			});

			// public url
			if (isset($landing))
			{
				$publicUrls = $landing->getPublicUrl(array_keys($this->arResult['LANDINGS']));
				foreach ($publicUrls as $id => $url)
				{
					$this->arResult['LANDINGS'][$id]['PUBLIC_URL'] = $this->getTimestampUrl($url);
				}
			}

		}

		parent::executeComponent();
	}
}