<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arResult["SKIP_BP"] = 'N';

if (!empty($arResult['TASK']['PARAMETERS']['REQUEST']))
{
	//primitive checking
	if (empty($arResult['TypesMap']))
	{
		$whiteList = array(
			'bool', 'double', 'int', 'select', 'string', 'text', 'internalselect',
			//CBPVirtualDocument
			'B', 'N', 'L', 'S', 'T'
		);
		foreach ($arResult['TASK']['PARAMETERS']['REQUEST'] as $request)
		{
			if (!in_array($request['Type'], $whiteList))
			{
				$arResult["SKIP_BP"] = 'Y';
				break;
			}
		}
	}
	//smart checking
	else
	{
		$checkedTypes = array();
		foreach ($arResult['TASK']['PARAMETERS']['REQUEST'] as $request)
		{
			$type = strtolower($request['Type']);
			if (!in_array($type, $checkedTypes))
			{
				if (isset($arResult['TypesMap'][$type]))
				{
					/** @var Bitrix\Bizproc\BaseType\Base $typeClass */
					$typeClass = $arResult['TypesMap'][$type];
					if (!$typeClass::canRenderControl(\Bitrix\Bizproc\FieldType::RENDER_MODE_MOBILE))
					{
						$arResult["SKIP_BP"] = 'Y';
						break;
					}
					$checkedTypes[] = $type;
				}
				else
				{
					$arResult["SKIP_BP"] = 'Y';
					break;
				}
			}
		}
	}
}

if (!empty($arResult["TASK"]["DESCRIPTION"]))
{
	$arResult["TASK"]["DESCRIPTION"] = preg_replace_callback(
		'|<a href="/bitrix/tools/bizproc_show_file.php\?([^"]+)"\starget=\'_blank\'>|',
		function($matches)
		{
			parse_str($matches[1], $query);
			$filename = '';
			if (isset($query['f']))
			{
				$query['hash'] = md5($query['f']);
				$filename = $query['f'];
				unset($query['f']);
			}
			$query['mobile_action'] = 'bp_show_file';
			$query['filename'] = $filename;

			return '<a href="#" data-url="'.SITE_DIR.'mobile/ajax.php?'.http_build_query($query)
				.'" data-name="'.htmlspecialcharsbx($filename).'" onclick="BXMobileApp.UI.Document.open({url: this.getAttribute(\'data-url\'), filename: this.getAttribute(\'data-name\')}); return false;">';
		},
		$arResult["TASK"]["DESCRIPTION"]
	);

	$arResult["TASK"]["DESCRIPTION"] = preg_replace_callback(
		'|<a href="/bitrix/tools/disk/uf.php\?([^"]+)"\starget=\'_blank\'>([^<]+)|',
		function($matches)
		{
			parse_str($matches[1], $query);
			$filename = htmlspecialcharsback($matches[2]);
			$query['mobile_action'] = 'disk_uf_view';
			$query['filename'] = $filename;

			return '<a href="#" data-url="'.SITE_DIR.'mobile/ajax.php?'.http_build_query($query)
				.'" data-name="'.htmlspecialcharsbx($filename).'" onclick="BXMobileApp.UI.Document.open({url: this.getAttribute(\'data-url\'), filename: this.getAttribute(\'data-name\')}); return false;">'.$matches[2];
		},
		$arResult["TASK"]["DESCRIPTION"]
	);
}

$arResult['TASK']['PARAMETERS']['DOCUMENT_URL'] = null;

if (is_array($arResult['TASK']['PARAMETERS']['DOCUMENT_ID'])
	&&
	(
		$arResult['TASK']['PARAMETERS']['DOCUMENT_ID'][0] === 'disk'
		|| $arResult['TASK']['PARAMETERS']['DOCUMENT_ID'][0] === 'crm'
	)
)
{
	$url = SITE_DIR;
	if ($arResult['TASK']['PARAMETERS']['DOCUMENT_ID'][0] === 'crm')
	{
		list($entityType, $entityId) = explode('_', $arResult['TASK']['PARAMETERS']['DOCUMENT_ID'][2]);
		$entityType = strtolower($entityType);
		$url .= 'mobile/crm/'.$entityType.'/?page=view&'.$entityType.'_id='.$entityId;
	}
	else
	{
		$url .= 'mobile/disk/file_detail.php?objectId='.$arResult['TASK']['PARAMETERS']['DOCUMENT_ID'][2];
	}
	$arResult['TASK']['PARAMETERS']['DOCUMENT_URL'] = $url;
}