<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

class CCrmProductSectionTreeHelper
{
	public function checkRights()
	{
		$permissions = CCrmPerms::GetCurrentUserPermissions();
		if (!(CCrmPerms::IsAccessEnabled($permissions) && $permissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ')))
			return false;

		return true;
	}

	public function getInitialTree($catalogId, $sectionId)
	{
		$initialTree = array();

		$resRootElements = CIBlockSection::GetTreeList(
			array(
				'=IBLOCK_ID' => $catalogId,
				'=DEPTH_LEVEL' => 1,
				'CHECK_PERMISSIONS' => 'N'
			),
			array('ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN')
		);
		$parentIndex = array();
		$i = 0;
		while ($arElement = $resRootElements->Fetch())
		{
			$bSelected = (intval($arElement['ID']) === intval($sectionId));
			$initialTree[$i] = array(
				'ID' => $arElement['ID'],
				'NAME' => $arElement['NAME'],
				'SELECTED' => $bSelected ? 'Y' : 'N',
				'HAS_CHILDREN' =>
					((intval($arElement["RIGHT_MARGIN"]) - intval($arElement["LEFT_MARGIN"])) > 1) ? 'Y' : 'N',
				'CHILDREN' => array()
			);
			$parentIndex[$arElement['ID']] = $i;
			$i++;
		}

		$resHeadElements = CIBlockSection::GetNavChain(
			$catalogId, $sectionId,
			array('ID', 'NAME', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN')
		);
		$parentElement = null;
		while ($arHead = $resHeadElements->Fetch())
		{
			/*if (intval($arHead['ID']) === intval($sectionId))
				break;*/

			if ($parentElement === null)
			{
				$parentElement = &$initialTree[$parentIndex[$arHead['ID']]];
			}
			else
			{
				$tmp = &$parentElement['CHILDREN'][$parentIndex[$arHead['ID']]];
				unset($parentElement);
				$parentElement = &$tmp;
				unset($tmp);
			}

			$resElement = CIBlockSection::GetTreeList(
				array(
					'=IBLOCK_ID' => $catalogId,
					'=SECTION_ID' => $arHead['ID'],
					'=DEPTH_LEVEL' => 1 + $arHead['DEPTH_LEVEL'],
					'CHECK_PERMISSIONS' => 'N'
				),
				array('ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN')
			);
			$parentIndex = array();
			$i = 0;
			while ($arElement = $resElement->Fetch())
			{
				$bSelected = (intval($arElement['ID']) === intval($sectionId));
				$parentElement['CHILDREN'][$i] = array(
					'ID' => $arElement['ID'],
					'NAME' => $arElement['NAME'],
					'SELECTED' => $bSelected ? 'Y' : 'N',
					'HAS_CHILDREN' =>
						((intval($arElement["RIGHT_MARGIN"]) - intval($arElement["LEFT_MARGIN"])) > 1) ? 'Y' : 'N',
					'CHILDREN' => array()
				);
				$parentIndex[$arElement['ID']] = $i;
				$i++;
			}
		}

		return $initialTree;
	}

	public function getSubsections($catalogId, $sectionId)
	{
		$arSubsections = array();

		$resSection = CIBlockSection::GetList(
			array(), array('ID'=>intval($sectionId)), false,
			array('ID', 'NAME', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN')
		);
		if ($resSection)
		{
			if ($arSection = $resSection->Fetch())
			{
				$resElement = CIBlockSection::GetTreeList(
					array(
						'=IBLOCK_ID' => $catalogId,
						'=SECTION_ID' => $arSection['ID'],
						'=DEPTH_LEVEL' => 1 + $arSection['DEPTH_LEVEL'],
						'CHECK_PERMISSIONS' => 'N'
					),
					array('ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN')
				);
				while ($arElement = $resElement->Fetch())
					$arSubsections[] = array(
						'ID' => $arElement['ID'],
						'NAME' => $arElement['NAME'],
						'HAS_CHILDREN' =>
							((intval($arElement["RIGHT_MARGIN"]) - intval($arElement["LEFT_MARGIN"])) > 1) ? 'Y' : 'N',
						'SELECTED' => 'N',
						'CHILDREN' => array()
					);
			}
		}

		return $arSubsections;
	}
}
