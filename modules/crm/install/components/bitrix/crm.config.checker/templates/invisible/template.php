<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
$res = \Bitrix\Main\Config\Option::get("crm", "crm_was_imported");
$res = empty($res) ? [] : unserialize($res, ['allowed_classes' => false]);
if (
	empty($res) ||
	!is_array($res) ||
	$res["CHECKED"] === true
)
{
	return;
}
$config = \CUserOptions::GetOption("crm", "config_checker", ["lastTime" => null, "show" => "Y"]);
if ($config["show"] === "N")
{
	return;
}

Bitrix\Main\UI\Extension::load(["sidepanel", "ajax"]);

$period = 3600;
$nextTime = 5;
if (is_integer($config["lastTime"]))
{
	$nextTime = max($period - (time() - $config["lastTime"]), 5);
}
?>
<script>
	BX.ready(function(){
		var sideSlider = function(period)
		{
			BX.ajax
				.runComponentAction("bitrix:crm.config.checker", "showSlider", {mode : "class"})
				.then(function(result)
				{
					if (result.data.show !== "N")
					{
						if (result.data.lastVisit > 0 && (period - result.data.lastVisit) > 0)
						{
							setTimeout(sideSlider, (period - result.data.lastVisit)*1000, period);
						}
						else
						{
							BX.SidePanel.Instance.open('<?=$arResult["PATH_TO_CONFIG_CHECKER"]?>', {
								events : {
									onClose : function() {
										BX.ajax.runComponentAction("bitrix:crm.config.checker", "closeSlider", {mode : "class"});
										setTimeout(sideSlider, period*1000, period);
									}
								}
							});
						}
					}
				}
			);
		}
		setTimeout(sideSlider, <?=$nextTime?>*1000, <?=$period?>);
	});
</script><?
