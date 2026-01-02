<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Disk\Public\Service\UnifiedLink\Render\MobileUnifiedLinkRenderer;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');


if (!Loader::includeModule('disk') || !Loader::includeModule('mobile'))
{
	return;
}

$request = Context::getCurrent()->getRequest();
$uniqueCode = (string)$request->get('uniqueCode');
$attachedId = (int)$request->get('attachedId');
$versionId = (int)$request->get('version');

if ($uniqueCode !== '')
{
	echo MobileUnifiedLinkRenderer::renderByUniqueCode($uniqueCode, $attachedId, $versionId);

	return;
}

$boardId = (int)$request->get('boardId');
if ($boardId > 0)
{
	$isAttached = ($request->get('attached') === 'Y');

	if ($isAttached)
	{
		echo MobileUnifiedLinkRenderer::renderByAttachedObject($boardId, 0);
	}
	else
	{
		echo MobileUnifiedLinkRenderer::renderByFileId($boardId, 0, 0);
	}
}
