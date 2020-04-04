<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
					<div class="log-popup-caption-wrap">
						<div class="log-popup-caption">
							<?if (IsModuleInstalled("bitrix24")):?>
								<div id="language-box" class="language-box <?=LANGUAGE_ID?>">
									<span id="language-arrow" class="language-flag"><span class="arrow"></span></span>
								</div>
							<?endif?>
							<?=GetMessage("BITRIX24_COPYRIGHT_B24", array("#CURRENT_YEAR#" => date("Y")))?>
						</div>
					</div>
				</div>
			</div>
		</td>
	</tr>
	<tr>
		<td class="log-bottom-cell"><span class="log-bottom-cap"></span></td>
	</tr>
</table>
<script type="text/javascript">
	BX.ready(function(){
		var lang_toggle = BX('language-box');
		BX.bind(lang_toggle, 'click', function(){
			BX.PopupMenu.show('feed-filter-popup', lang_toggle, [
				{text : "<?=GetMessage("BITRIX24_LANG_RU")?>", className : "language-box-item ru", onclick : function() { reloadPage("ru"); }},
				{text : "<?=GetMessage("BITRIX24_LANG_EN")?>", className : "language-box-item en", onclick : function() { reloadPage("en"); }},
				{text : "<?=GetMessage("BITRIX24_LANG_DE")?>", className : "language-box-item de", onclick : function() { reloadPage("de"); }},
				{text : "<?=GetMessage("BITRIX24_LANG_UA")?>", className : "language-box-item ua", onclick : function() { reloadPage("ua"); }},
				{text : "<?=GetMessage("BITRIX24_LANG_LA")?>", className : "language-box-item la", onclick : function() { reloadPage("la"); }}
			],
					{   offsetTop:10,
						offsetLeft:0,
						angle:{offset: 33}
					}
			);
		})
	});
	function reloadPage(lang)
	{
		var url = window.location.href;
		url = url.replace(/(\?|\&)user_lang=[A-Za-z]{2}/, "");
		url += (url.indexOf("?") == -1 ? "?" : "&") + "user_lang=" + lang;
		window.location.href = url;
	}
</script>

</body>
</html>