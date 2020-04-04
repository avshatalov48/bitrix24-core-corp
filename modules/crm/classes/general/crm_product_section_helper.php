<?php

class CCrmProductSectionHelper
{
	public function __construct($catalogID = 0)
	{
		if ($catalogID <= 0)
			$catalogID = CCrmCatalog::EnsureDefaultExists();
		$this->catalogID = $catalogID;

		$this->sectionByNameCache = array();

		$this->iblockModuleIncluded = CModule::IncludeModule('iblock');
	}

	public function ImportSectionArray($arSectionName, $level = 0)
	{
		$sectionID = 0;

		$level = intval($level);

		if (is_array($arSectionName) && $this->iblockModuleIncluded)
		{
			$nSections = count($arSectionName);
			if ($nSections > 0 && $level < $nSections && $this->catalogID > 0)
			{
				$curSectionName = '';
				$curSectionPath = $parentSectionPath = '';
				for ($i = 0; $i <= $level; $i++)
				{
					if ($i > 0)
						$curSectionPath .= '|';
					$curSectionName = trim(strval($arSectionName[$i]));
					$curSectionPath .= $curSectionName;
					if ($i === ($level - 1))
						$parentSectionPath = $curSectionPath;
				}
				if ($curSectionName !== '')
				{
					$curSectionHash = md5($curSectionPath);
					$parentSectionID = 0;
					if ($level > 0)
					{
						$parentSectionHash = md5($parentSectionPath);
						if (is_array($this->sectionByNameCache[$level - 1])
							&& isset($this->sectionByNameCache[$level - 1][$parentSectionHash]))
						{
							$parentSectionID = $this->sectionByNameCache[$level - 1][$parentSectionHash];
						}
					}
					if ($parentSectionID > 0 || $level === 0)
					{
						$curSectionID = 0;
						if (is_array($this->sectionByNameCache[$level]) && isset($this->sectionByNameCache[$level][$curSectionHash]))
						{
							$curSectionID = $this->sectionByNameCache[$level][$curSectionHash];
						}
						else
						{
							$dbRes = CIBlockSection::GetTreeList(
								array(
									'=IBLOCK_ID' => $this->catalogID,
									'=SECTION_ID' => $parentSectionID,
									'=NAME' => $curSectionName,
									'=DEPTH_LEVEL' => $level + 1,
									'CHECK_PERMISSIONS' => 'N'
								),
								array('ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN')
							);
							if ($arRes = $dbRes->Fetch())
							{
								$curSectionID = $this->sectionByNameCache[$level][$curSectionHash] = intval($arRes['ID']);
							}
							unset($dbRes, $arRes);
						}
						if ($curSectionID === 0)
						{
							$arSectionFields = array(
								'CATALOG_ID' => $this->catalogID,
								'SECTION_ID' => $parentSectionID,
								'NAME' => $curSectionName
							);
							$res = CCrmProductSection::Add($arSectionFields);
							if ($res !== false)
							{
								$curSectionID = $this->sectionByNameCache[$level][$curSectionHash] = intval($res);
							}
						}
						if ($level === ($nSections - 1))
						{
							$sectionID = $curSectionID;
						}
						else
						{
							$sectionID = $this->ImportSectionArray($arSectionName, $level + 1);
						}
					}
				}
			}
		}

		return $sectionID;
	}
}
