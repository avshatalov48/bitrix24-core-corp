<?php
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();

$url = $request->get('URL');
$urlSign = $request->get('URL_SIGN');

if (isset($url, $urlSign) && $url !== 'undefined' && $urlSign !== 'undefined')
{
	$sign = new \Bitrix\Main\Security\Sign\Signer;
	try
	{
		$message = $sign->unsign($urlSign, 'promo_url');
		if ($message === $url)
		{
			echo "
				<html>
					<head>
						<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
						<style>
							body {
								margin: 0;
								padding: 0;
								overflow: hidden;
							}
			
							iframe {
								width: 100%;
								height: 100%;
								border: none;
							}
						</style>
					</head>
					<body>
						 <iframe src=\"{$url}\"></iframe>
					</body>
				</html>
			";
		}
	}
	catch (\Bitrix\Main\Security\Sign\BadSignatureException | \Bitrix\Main\ArgumentTypeException $e)
	{
		echo '';
	}
}


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")
?>
