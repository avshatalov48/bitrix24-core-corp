<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CJSCore::Init(["voximplant.common"]);

?>
<div class="tel-set-wrap">
	<div class="tel-top-title-block">
		<div class="tel-top-title-icon"></div>
		<?=GetMessage('VI_CONFIG_WELCOME_MESSAGE')?>
	</div>
	<table class="tel-block-table" id="tel-block-table">
		<tr class="tel-block-top">
			<?if(!$arResult['REST_ONLY']):?>
				<td class="tel-block tel-block-rent-num <?=($arResult['MODE_RENT']? 'tel-block-active': '')?>"  data-block="tel-set-first">
					<div class="tel-block-title">
						<span class="tel-block-title-icon"></span>
						<span class="tel-block-title-text"><?=GetMessage('VI_CONFIG_BUY')?></span>
					</div>
					<div class="tel-block-text">
						<ul>
							<li><?=GetMessage('VI_CONFIG_BUY_DESC_1')?></li>
							<li><?=GetMessage('VI_CONFIG_BUY_DESC_2')?></li>
							<li><?=GetMessage('VI_CONFIG_BUY_DESC_3')?></li>
							<li><?=GetMessage('VI_CONFIG_BUY_DESC_4')?></li>
							<li><?=GetMessage('VI_CONFIG_BUY_DESC_5')?></li>
						</ul>
					</div>
				</td>
				<td class="tel-block-space"></td>
			<?endif;?>
			<td class="tel-block tel-block-connect-atc <?=($arResult['MODE_SIP']? 'tel-block-active': '')?>" data-block="tel-set-second">
				<div class="tel-block-title">
					<span class="tel-block-title-icon"></span>
					<span class="tel-block-title-text"><?=GetMessage('VI_CONFIG_OWN')?></span>
				</div>
				<div class="tel-block-text">
					<ul>
						<li><?=GetMessage('VI_CONFIG_OWN_DESC_1')?></li>
						<li><?=GetMessage('VI_CONFIG_OWN_DESC_2')?></li>
						<li><?=GetMessage('VI_CONFIG_OWN_DESC_3')?></li>
						<li><?=GetMessage('VI_CONFIG_OWN_DESC_4')?></li>
					</ul>
				</div>
			</td>
			<td class="tel-block-space"></td>
			<?if(!in_array($arResult['LANG'], ['ua', 'kz']) && !$arResult['REST_ONLY']):?>
			<td class="tel-block tel-block-own-num <?=($arResult['MODE_LINK']? 'tel-block-active': '')?>"  data-block="tel-set-third">
				<div class="tel-block-title">
					<span class="tel-block-title-icon"></span>
					<span class="tel-block-title-text"><?=GetMessage('VI_CONFIG_LINK')?></span>
				</div>
				<div class="tel-block-text">
					<ul>
						<li><?=GetMessage('VI_CONFIG_LINK_DESC_1')?></li>
						<li><?=GetMessage('VI_CONFIG_LINK_DESC_2')?></li>
						<li><?=GetMessage('VI_CONFIG_LINK_DESC_3')?></li>
						<li><?=GetMessage('VI_CONFIG_LINK_DESC_4')?></li>
						<li><?=GetMessage('VI_CONFIG_LINK_DESC_5')?></li>
					</ul>
				</div>
			</td>

			<?endif;?>
		</tr>
		<tr class="tel-block-bottom">
			<?if(!$arResult['REST_ONLY']):?>
				<td class="tel-block"  data-block="tel-set-first">
					<div class="tel-block-footer">
						<span class="tel-block-btn" id="tel-set-first-btn"><?=GetMessage($arResult['MODE_RENT']? 'VI_CONFIG_SET_ACTIVE': 'VI_CONFIG_SET')?></span>
					</div>
				</td>
				<td class="tel-block-space"></td>
			<?endif;?>
			<td class="tel-block <?=($arResult['MODE_SIP']? 'tel-block-active': '')?>" data-block="tel-set-second">
				<div class="tel-block-footer">
					<span class="tel-block-btn" id="tel-set-second-btn"><?=GetMessage($arResult['MODE_SIP']? 'VI_CONFIG_SET_ACTIVE': 'VI_CONFIG_SET')?></span>
				</div>
			</td>
			<td class="tel-block-space"></td>
			<?if(!in_array($arResult['LANG'], ['ua', 'kz']) && !$arResult['REST_ONLY']):?>
			<td class="tel-block <?=($arResult['MODE_LINK']? 'tel-block-active': '')?>"  data-block="tel-set-third">
				<div class="tel-block-footer">
					<span class="tel-block-btn" id="tel-set-third-btn"><?=GetMessage($arResult['MODE_LINK']? 'VI_CONFIG_SET_ACTIVE': 'VI_CONFIG_SET')?></span>
				</div>
			</td>
			<?endif;?>
		</tr>
	</table>
	<div class="tel-set-block-wrap-config" id="tel-set-block-wrap">
		<div class="tel-set-block tel-set-block-active" id="tel-set-block">
			<?if(!$arResult['REST_ONLY']):?>
				<div id="tel-set-first" class="tel-set-block-inner-wrap-config">
					<div class="tel-set-inner">
						<?if(in_array($arResult['LANG'], ['ua', 'kz'])):?>
							<?$APPLICATION->IncludeComponent("bitrix:voximplant.config.rent.order", "", array());?>
						<?else:?>
							<?$APPLICATION->IncludeComponent("bitrix:voximplant.config.rent", "", array());?>
						<?endif;?>
					</div>
				</div>
			<?endif;?>
			<div id="tel-set-second" class="tel-set-block-inner-wrap-config" <?=($arResult['MODE_SIP']? 'style="display: block;"': '')?>>
				<div class="tel-set-inner">
					<?$APPLICATION->IncludeComponent("bitrix:voximplant.config.sip", "", array());?>
				</div>
			</div>
			<?if(!in_array($arResult['LANG'], ['ua', 'kz']) && !$arResult['REST_ONLY']):?>
				<div id="tel-set-third" class="tel-set-block-inner-wrap-config" <?=($arResult['MODE_LINK']? 'style="display: block;"': '')?>>
					<div class="tel-set-inner">
						<?$APPLICATION->IncludeComponent("bitrix:voximplant.config.link", "", array());?>
					</div>
				</div>
			<?endif;?>

			<div id="tel-set-corner" class="tel-set-corner"></div>
		</div>
	</div>

	<?if (\Bitrix\Main\ModuleManager::isModuleInstalled("rest")):
		$arParams["MARKETPLACE_DETAIL_URL_TPL"] = isset($arParams["MARKETPLACE_DETAIL_URL_TPL"]) ? $arParams["MARKETPLACE_DETAIL_URL_TPL"] : SITE_DIR."marketplace/detail/#app#/";
		$arParams["CATEGORY_URL_TPL"] = isset($arParams["CATEGORY_URL_TPL"]) ? $arParams["MARKETPLACE_DETAIL_URL_TPL"] : SITE_DIR."marketplace/category/#category#/";
	?>
	<div id="tel-set-first-partners" <?=($arResult['MODE_RENT']? 'style="display: block;"': '')?>>
		<?
		$tag = array("telephony", "partners");
		if (\Bitrix\Main\Loader::includeModule("bitrix24"))
		{
			$tag[] = \CBitrix24::getLicensePrefix();
		}
		$APPLICATION->IncludeComponent("bitrix:rest.marketplace.category", "rows", array(
			"CATEGORY" => "telephony",
			"TAG" => $tag,
			"DETAIL_URL_TPL" =>  $arParams["MARKETPLACE_DETAIL_URL_TPL"],
			"CATEGORY_URL_TPL" => $arParams["CATEGORY_URL_TPL"],
			"TITLE" => GetMessage("VI_CONFIG_PARTNERS_TITLE"),
			"SET_TITLE" => "N"
		));
		?>
	</div>
	<?endif?>
</div>

<div class="tel-set-phone-numbers">
	<div class="tel-set-item-alert">
		<?=GetMessage('VI_CONFIG_NOTICE_NEW', Array(
			'#LINK_CONFIG#' => $arResult['IFRAME'] ?
				'<a onclick="BX.SidePanel.Instance.open(\''.CVoxImplantMain::GetPublicFolder().'configs.php\')">'.GetMessage('VI_CONFIG_PAGE_CONFIG').'</a>' :
				'<a href="'.CVoxImplantMain::GetPublicFolder().'configs.php">'.GetMessage('VI_CONFIG_PAGE_CONFIG').'</a>',
			'#LINK_USERS#' => $arResult['IFRAME'] ?
				'<a onclick="BX.SidePanel.Instance.open(\''.CVoxImplantMain::GetPublicFolder().'users.php\')">'.GetMessage('VI_CONFIG_PAGE_CONFIG_USERS').'</a>' :
				'<a href="'.CVoxImplantMain::GetPublicFolder().'users.php">'.GetMessage('VI_CONFIG_PAGE_CONFIG_USERS').'</a>'
		))?>
	</div>
</div>

<script type="text/javascript">
	var setPost = {
		corner : BX('tel-set-corner'),
		anim_block : null,
		btn : null,
		wrap_block : BX('tel-set-block'),
		block_list : null,
		table : BX('tel-block-table'),
		active_cell_num : null,
		over_cell_num : null,

		show : function(ev)
		{
			var event = ev || window.event;
			var target = event.target || event.srcElement;
			var active_cell,
				btn;

			while(target != this)
			{
				if (target.tagName == 'TD')
				{
					active_cell = target;
					break;
				}
				target = target.parentNode;
			}

			if(!active_cell.hasAttribute('data-block')) return;

			if(event.type == 'mouseover'){
				setPost.block_hover(active_cell);
			}
			else if (event.type == 'mouseout'){
				setPost.block_out();
			}
			else if(event.type == 'click')
			{
				var blockID = active_cell.getAttribute('data-block');

				if(blockID == 'tel-set-first'){
					btn = BX('tel-set-first-btn')
				}else if(blockID == 'tel-set-second'){
					btn = BX('tel-set-second-btn')
				}else if(blockID == 'tel-set-third'){
					btn = BX('tel-set-third-btn')
				}

				BX("tel-set-block-wrap").style.height = 'auto';
				BX("tel-set-corner").style.display = "block";
				/*if (BX("tel-set-block-wrap").style.display == "none")
				{
					BX("tel-set-block-wrap").style.display = "block";
				}*/

				setPost.anim(blockID, btn);

				BX("tel-set-first-partners").style.display = (blockID == 'tel-set-first') ? "block" : "none";
			}
		},

		anim : function(blockID, btn)
		{
			this.block_list = this.wrap_block.childNodes;

			this.anim_block = BX(blockID);
			this.btn = btn;

			//this.wrap_block.style.height = this.wrap_block.offsetHeight + 'px';

			for(var i = this.block_list.length-1; i>=0; i--){
				if(this.block_list[i].tagName == 'DIV' && this.block_list[i] != this.corner){
					this.block_list[i].style.display = 'none';
				}
			}

			this.anim_block.style.display = 'block';
			this.anim_block.style.height = 'auto';


			var corner_offset =  ((this.btn.offsetWidth/2) + BX.pos(this.btn).left) - ((this.corner.offsetWidth/2) + BX.pos(this.corner).left);
			this.corner.style.left = parseInt(BX.style(this.corner, 'left')) + corner_offset + 'px';

			for(var i = this.table.rows.length-1; i >=0; i--){
				for(var b = this.table.rows[i].cells.length-1; b>=0; b--)
				{
					BX.removeClass(this.table.rows[i].cells[b], 'tel-block-active');

					if(this.btn.parentNode.parentNode == this.table.rows[i].cells[b]){
						this.active_cell_num = b;
					}
				}
			}

			BX.addClass(this.table.rows[0].cells[this.active_cell_num], 'tel-block-active');
			BX.addClass(this.table.rows[1].cells[this.active_cell_num], 'tel-block-active')

		},

		anim_easing : function(params){
			var _this = this;
			var easing = new BX.easing({
				duration:300,
				start : params.start,
				finish : params.finish,
				transition : BX.easing.makeEaseOut(BX.easing.transitions.linear),
				step:function(state){
					_this.wrap_block.style.height = state.height +'px';
				},
				complete:function(){}
			});

			easing.animate()
		},

		block_hover : function(cell)
		{
			var tr;
			tr = cell.parentNode;

			for(var i = tr.cells.length-1; i>=0; i--){
				if(tr.cells[i] == cell){
					this.over_cell_num = i;
				}
			}

			for(var i = this.table.rows.length-1; i >=0; i--)
			{
				BX.addClass(this.table.rows[i].cells[this.over_cell_num] ,'tel-block-hover')
			}
		},

		block_out : function()
		{
			for(var i = this.table.rows.length-1; i >=0; i--)
			{
				BX.removeClass( this.table.rows[i].cells[this.over_cell_num] ,'tel-block-hover')
			}
		}
	};

	BX.bind(BX('tel-block-table'), 'mouseover', setPost.show);
	BX.bind(BX('tel-block-table'), 'mouseout', setPost.show);
	BX.bind(BX('tel-block-table'), 'click', setPost.show);

	<?
		if ($arResult['MODE_ACTIVE'] == 'SIP' || $arResult['MODE_ACTIVE'] == '' && $arResult['MODE_SIP'])
		{?>
			var blockID = 'tel-set-second';
			var btn = BX('tel-set-second-btn');
		<?}
		else if ($arResult['MODE_ACTIVE'] == 'RENT')
		{?>
			var blockID = 'tel-set-first';
			var btn = BX('tel-set-first-btn');
		<?}
		else if ($arResult['MODE_ACTIVE'] == 'LINK' || $arResult['MODE_ACTIVE'] == '' && $arResult['MODE_LINK'])
		{?>
			var blockID = 'tel-set-third';
			var btn = BX('tel-set-third-btn');
		<?}
		else if ($arResult['MODE_ACTIVE'] == '')
		{?>
			var blockID = 'tel-set-first';
			var btn = BX('tel-set-first-btn');
		<?}
	?>

	<?if (!empty($arResult['MODE_ACTIVE'])):?>
		BX.ready(function () {
			BX("tel-set-block-wrap").style.height = 'auto';
			BX("tel-set-corner").style.display = "block";
			setPost.anim(blockID, btn);
		});
	<?endif?>

</script>