<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/extranet/404/index.php");
?>
<div class="bx-extranet-grid">
	<style>
		.bx-extranet-404-container{
			padding-top: 130px;
			margin: 0 auto;
			width: 90%;
			max-width: 450px
		}
		.bx-extranet-404-image{
			text-align: center;
			padding-bottom: 35px;
		}
		.bx-extranet-404-title{
			text-align: center;
			padding-bottom: 25px;
			font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
			color: #535c69;
			font-size: 23px;
			font-weight: bold;
		}
		.bx-extranet-404-description{
			color: #535c69;
			font-size: 13px;
			line-height: 166%;
			font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		}
		.bx-extranet-404-description a{
			color: #2067b0;
			text-decoration: none;
		}
	</style>
	<div class="bx-extranet-404-container">
		<div class="bx-extranet-404-image"><img src="images/404.png" alt=""></div>
		<div class="bx-extranet-404-title"><?=GetMessage("EXTRANET_404_TITLE")?></div>
		<div class="bx-extranet-404-description">
			<?=GetMessage("EXTRANET_404_TEXT")?>
		</div>
	</div>
</div> <!-- / bx-extranet-grid -->
