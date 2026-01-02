<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

$availableCollab = ToolsManager::getInstance()->checkAvailabilityByToolId('collab');
if ($availableCollab)
{
	$rsSites = CSite::GetByID(CExtranet::GetExtranetSiteID());
	if (
		($arExtranetSite = $rsSites->Fetch())
		&& ($arExtranetSite["ACTIVE"] !== "N")
	)
	{
		$URLToRedirect = ($arExtranetSite["SERVER_NAME"] <> '' ? (CMain::IsHTTPS() ? "https" : "http") . "://" . $arExtranetSite["SERVER_NAME"] : "") . $arExtranetSite["DIR"];
		$uri = (new Uri($URLToRedirect . 'online'));
		LocalRedirect($uri->getLocator(), true, '307 Temporary Redirect');
	}
}

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/extranet/public/collab/403/index.php');

?>
<div class="bx-collab-grid">
	<style>
		.bx-collab-403-container {
			padding-top: 131px;
			margin: 0 auto;
			width: 90%;
			max-width: 520px;
			font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif;
		}
		.bx-collab-403-image {
			width: 520px;
			height: 300px;
			text-align: center;
			padding-bottom: 24px;
		}
		.bx-collab-403-title {
			width: 520px;
			height: 33px;
			text-align: center;
			padding-bottom: 20px;
			font-size: 25px;
			font-weight: 600;
			color: rgb(51, 51, 51);
			line-height: 33px;
			letter-spacing: -0.35px;
		}
		.bx-collab-403-action {
			width: 520px;
			text-align: center;
		}
		.bx-collab-403-action__logout {
			width: 180px;
			height: 46px;
			font-size: 16px;
			font-weight: 500;
			line-height: 16px;
			letter-spacing: -0.1px;
			color: rgb(0, 117, 255);
			transition: 160ms linear background-color, 160ms linear color, 160ms linear opacity, 160ms linear box-shadow, 160ms linear border-color;
		}
		.bx-collab-403-action__logout:hover {
			border: 2px solid rgba(122, 183, 255, 0.7);
			font-weight: 500;
			opacity: 0.7;
		}
		.bx-collab-403-action__logout:active {
			border: 1px solid #ffffff;
			color: #ffffff;
			background-color: rgb(122, 183, 255);
		}
		.bx-collab-403-action__logout-container {
			border-radius: 10px;
			border-color: rgb(122, 183, 255);
			background-color: transparent;
			border-style: solid;
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin: 0 auto;
		}
		.bx-collab-403-action__logout-container span {
			margin-left: 5px;
			width: 100px;
			padding-right: 30px;
		}
	</style>
	<script>
		function logout() {
			const newUrl = document.location.origin + '/auth/?logout=yes&backurl=' + encodeURIComponent(document.location.origin) + '&<?= bitrix_sessid_get() ?>';
			document.location.href = newUrl;
		}
	</script>
	<div class="bx-collab-403-container">
		<div class="bx-collab-403-image">
			<img src="images/group.svg" alt="icon">
		</div>
		<div class="bx-collab-403-title">
			<?= Loc::getMessage('COLLAB_403_DESCRIPTION') ?>
		</div>
		<div class="bx-collab-403-action">
			<button type="button" onclick="logout();" class="bx-collab-403-action__logout bx-collab-403-action__logout-container">
				<img src="images/logout.svg" alt="logout">
				<span>
					<?= Loc::getMessage('COLLAB_403_LOGOUT') ?>
				</span>
			</button>
		</div>
	</div>
</div>
