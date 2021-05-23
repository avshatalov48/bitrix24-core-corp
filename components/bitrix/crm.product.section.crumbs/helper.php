<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CCrmProductSectionCrumbsHelper
{
	public function checkRights()
	{
		$permissions = CCrmPerms::GetCurrentUserPermissions();
		if (!(CCrmPerms::IsAccessEnabled($permissions) && $permissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ')))
			return false;

		return true;
	}

	public function getCrumbs($catalogId, $sectionId, $urlTemplate)
	{
		$arCrumbs = array();

		$arCrumb = array(
			'ID' => 0,
			'NAME' => GetMessage('CRM_PRODUCT_SECTION_ROOT_CRUMB_NAME'),
			'LINK' => str_replace('#section_id#', '0', $urlTemplate),
			'CHILDREN' => array()
		);

		$resRootElements = CIBlockSection::GetTreeList(
			array(
				'=IBLOCK_ID' => $catalogId,
				'=DEPTH_LEVEL' => 1,
				'CHECK_PERMISSIONS' => 'N'
			),
			array('ID', 'NAME')
		);
		while ($arElement = $resRootElements->Fetch())
		{
			$arCrumb['CHILDREN'][] = array(
				'ID' => $arElement['ID'],
				'NAME' => $arElement['NAME'],
				'LINK' => str_replace('#section_id#', $arElement['ID'], $urlTemplate),
				'CHILDREN' => array()
			);
		}

		$arCrumbs[] = $arCrumb;

		$resElements = CIBlockSection::GetNavChain($catalogId, $sectionId, array('ID', 'NAME', 'DEPTH_LEVEL'));
		while ($arElement = $resElements->Fetch())
		{
			$arCrumb = array(
				'ID' => $arElement['ID'],
				'NAME' => $arElement['NAME'],
				'LINK' => str_replace('#section_id#', $arElement['ID'], $urlTemplate),
				'CHILDREN' => array()
			);
			$resElement = CIBlockSection::GetTreeList(
				array(
					'=IBLOCK_ID' => $catalogId,
					'=SECTION_ID' => $arElement['ID'],
					'=DEPTH_LEVEL' => 1 + $arElement['DEPTH_LEVEL'],
					'CHECK_PERMISSIONS' => 'N'
				),
				array('ID', 'NAME')
			);
			while ($arElement = $resElement->Fetch())
			{
				$arCrumb['CHILDREN'][] = array(
					'ID' => $arElement['ID'],
					'NAME' => $arElement['NAME'],
					'LINK' => str_replace('#section_id#', $arElement['ID'], $urlTemplate),
					'CHILDREN' => array()
				);
			}

			$arCrumbs[] = $arCrumb;
		}

		return $arCrumbs;
	}

	public function encodeUrn($urn)
	{
		global $APPLICATION;

		$result = '';
		$parts = preg_split("#(://|:\\d+/|/|\\?|=|&)#", $urn, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach($parts as $i => $part)
		{
			$result .= ($i % 2)
				? $part
				: rawurlencode($APPLICATION->ConvertCharset($part, LANG_CHARSET, 'UTF-8'));
		}

		return $result;
	}

	public function PrepareCrumbLinks(&$crumbs, $componentId)
	{
		if (!is_array($crumbs) || empty($crumbs))
			return;

		$template = 'return BX.Crm["ProductSectionCrumbs_'.$componentId.'"].onSectionSelect({sectionId: "#section_id#", sectionName: "#section_name#"});';

		foreach ($crumbs as &$crumb)
		{
			if (isset($crumb['LINK']) && isset($crumb['NAME']))
			{
				$crumb['LINK'] = str_replace(
					array('#section_id#', '#section_name#'),
					array(CUtil::JSEscape($crumb['LINK']), CUtil::JSEscape($crumb['NAME'])),
					$template
				);
			}
			if (is_array($crumb['CHILDREN']) && !empty($crumb['CHILDREN']))
			{
				$this->PrepareCrumbLinks($crumb['CHILDREN'], $componentId);
			}
		}
		unset($crumb);
	}
}
