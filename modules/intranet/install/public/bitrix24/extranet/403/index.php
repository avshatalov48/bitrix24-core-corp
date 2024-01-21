<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/extranet/403/index.php');

use Bitrix\Main\Localization\Loc;

?>
<div class="bx-extranet-grid">
	<style>
		.bx-extranet-403-container{
			padding-top: 130px;
			margin: 0 auto;
			width: 90%;
			max-width: 450px
		}
		.bx-extranet-403-image{
			text-align: center;
			padding-bottom: 35px;
		}
		.bx-extranet-403-title{
			text-align: center;
			padding-bottom: 25px;
			font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
			color: #535c69;
			font-size: 23px;
			font-weight: bold;
		}
	</style>
	<div class="bx-extranet-403-container">
		<div class="bx-extranet-403-image"><img src="images/403.png" alt=""></div>
		<div class="bx-extranet-403-title">
			<?= Loc::getMessage('EXTRANET_403_TITLE') ?>
		</div>
	</div>
</div>
