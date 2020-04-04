<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!empty($arResult["RECORDS"]))
{
	$runtime = CBPRuntime::GetRuntime();
	$runtime->StartRuntime();

	/** @var CBPDocumentService $documentService */
	$documentService = $runtime->GetService('DocumentService');

	foreach ($arResult["RECORDS"] as &$record)
	{
		$record['data']['DOCUMENT_ICON'] = '';
		try
		{
			$record['data']['DOCUMENT_ICON'] = $documentService->getDocumentIcon($record['data']['PARAMETERS']['DOCUMENT_ID']);
		}
		catch (Exception $e)
		{

		}
		$record['data']["DESCRIPTION"] = preg_replace_callback(
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
			$record['data']["DESCRIPTION"]
		);


		$record['data']["DESCRIPTION"] = preg_replace_callback(
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
			$record['data']["DESCRIPTION"]
		);


	}
}
$arResult["currentUserStatus"] = !empty($_GET['USER_STATUS'])? (int)$_GET['USER_STATUS'] : 0;