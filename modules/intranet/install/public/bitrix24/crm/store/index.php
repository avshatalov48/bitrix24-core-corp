<?
use Bitrix\Main\Context;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>
<div id="c"></div>
<script>
	BX.ready(function () {
		BX.SidePanel.Instance.open(
			'/shop/documents/receipt_adjustment/',
			{
				cacheable: false,
				allowChangeHistory: true,
				events: {
					onCloseComplete: function(event) {
						setTimeout(function() {
							window.history.replaceState({}, '', '/crm/deal/');
						}, 500);
					}
				}
			}
		);
	});
		
</script>
<?
require($_SERVER['DOCUMENT_ROOT'].'/crm/deal/index.php');?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>