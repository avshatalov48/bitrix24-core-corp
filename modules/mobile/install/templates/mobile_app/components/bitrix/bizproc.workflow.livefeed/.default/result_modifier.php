<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

foreach ($arResult['TASKS']['RUNNING'] as &$task)
{
	if (!empty($task["DESCRIPTION"]))
	{
		$task["DESCRIPTION"] = preg_replace_callback(
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
			$task["DESCRIPTION"]
		);

		$task["DESCRIPTION"] = preg_replace_callback(
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
			$task["DESCRIPTION"]
		);
	}
}