<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadString('<script type="text/javascript" src="'.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH."/im_mobile.js").'"></script>');
$APPLICATION->AddHeadString('<link href="'.CUtil::GetAdditionalFileURL(BX_PERSONAL_ROOT.'/js/im/css/common.css').'" type="text/css" rel="stylesheet" />');

CJSCore::Init("fx");
if(empty($arResult['NOTIFY'])):?>
	<div class="notif-block-empty"><?=GetMessage('NM_EMPTY');?></div>
<?else:?>
	<div class="notif-block-wrap" id="notif-block-wrap">
		<?
		$jsIds = "";
		$maxId = 0;
		$newFlag = false;
		$firstNewFlag = true;
		foreach ($arResult['NOTIFY'] as $data):
			$avatarId = "notif-avatar-".randString(5);
			$jsIds .= $jsIds !== "" ? ', "'.$avatarId.'"' : '"'.$avatarId.'"';
			$moreUsersCount = 0;
			if (isset($data['params']['USERS']))
			{
				$moreUsersCount = count($data['params']['USERS']);
				$moreUsersText = str_replace(
					['#COUNT#'],
					[$moreUsersCount],
					GetMessage('NM_MORE_USERS')
				);
			}

			$arFormat = Array(
				"tommorow" => "tommorow, ".GetMessage('NM_FORMAT_TIME'),
				"today" => "today, ".GetMessage('NM_FORMAT_TIME'),
				"yesterday" => "yesterday, ".GetMessage('NM_FORMAT_TIME'),
				"" => GetMessage('NM_FORMAT_DATE')
			);
			$maxId = $data['id'] > $maxId? $data['id']: $maxId;
			$data['date'] = FormatDate($arFormat, $data['date']);
			$data['text'] = preg_replace("/<img.*?data-code=\"([^\"]*)\".*?>/i", "$1", $data['text']);
			$data['text'] = preg_replace("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", "$2", $data['text']);
			$data['text'] = preg_replace("/\[RATING=([1-5]{1})\]/i", "$1", $data['text']);
			$data['text'] = preg_replace("/\[CHAT=(imol\|)?([0-9]{1,})\](.*?)\[\/CHAT\]/i", "$3", $data['text']);
			$data['text'] = preg_replace("/\[LIKE\]/i", '<span class="bx-smile bx-im-smile-like"></span>', $data['text']);
			$data['text'] = preg_replace("/\[DISLIKE\]/i", '<span class="bx-smile bx-im-smile-dislike"></span>', $data['text']);
			$data['text'] = CMobileHelper::prepareNotificationText($data['text'], $data['originalTag']);
			$data['link'] = CMobileHelper::createLink($data['originalTag']);


			if ($data['read'] == 'N' && !$newFlag || $data['read'] == 'Y' && $newFlag):
				$newFlag = $newFlag? false: true;
				if (!$firstNewFlag):
					?><div class="notif-new"></div><?
				endif;
			endif;
			$firstNewFlag = false;
			?>
			<div id="notify<?=$data['id']?>"  ontouchstart="onTouch(this, <?=$data['link']? "true":"false"?>, event)" class="notif-block">
				<script>
					BX.bind(BX("notify<?=$data['id']?>"), "click", function(event){
						if(
							BX.hasClass(event.target, "notif-fold-button")
							|| BX.hasClass(event.target, "notif-delete")
							|| event.target.tagName === 'A'
						)
						{
							return;
						}
						<?=$data['link'] && $data['type'] != 1? $data['link']:""?>
					});
				</script>
				<div>
					<div class="notif-avatar ml-avatar"><div class="ml-avatar-sub" id="<?=$avatarId?>" data-src="<?=$data['userAvatar']?>" style="background-size:cover;"></div></div>
				</div>
				<div class="notif-cont">
					<div class="notif-header">
						<div class="notif-title">
							<?=$data['userName']? $data['userName']: GetMessage('NM_SYSTEM_USER');?>
							<?if ($moreUsersCount > 0):?>
								<span style="font-weight: normal">
									<?=$moreUsersText?>
								</span>
							<?endif;?>
						</div>

						<div class="notif-delete" data-id="<?=$data['id']?>" onclick="deleteNotification(event)"></div>
					</div>

					<div class="notif-inner" id="inner<?=$data['id']?>" data-fold="fold<?=$data['id']?>">
						<div class="notif-text"><?=$data['text']?></div>

						<?if(isset($data['params'])):?>
							<?=getNotifyParamsHtml($data['params'])?>
						<?endif;?>
						<?if(isset($data['buttons'])):?>
							<div class="notif-buttons">
								<?foreach ($data['buttons'] as $button):?>
									<div data-notifyId="<?=$data['id']?>"  data-notifyValue="<?=$button['VALUE']?>" class="notif-button notif-button-<?=$button['TYPE']?>" onclick="_confirmRequest(this)"><?=$button['TITLE']?></div>
								<?endforeach;?>
							</div>
						<?endif;?>
					</div>
					<div class="notif-options">
						<div class="notif-time"><?=$data['date']?></div>
						<div class="notif-fold-button" id="fold<?=$data['id']?>" data-inner="inner<?=$data['id']?>"><?=GetMessage("NM_UNFOLD")?></div>
					</div>
				</div>
			</div>
		<?endforeach;?>
	</div>
	<script type="text/javascript">
		BX.ImLegacy.notifyLastId = <?=$maxId?>;
		BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);
	</script>

	<script type="text/javascript">

		newNotifyReload = null;
		BX.addCustomEvent("onPull-im", function(data) {
			if (data.command == 'notifyConfirm')
			{
				var notifyId = parseInt(data.params.id);
				if (BX('notify'+notifyId))
				{
					var elements = BX.findChildren(BX('notify'+notifyId), {className : "notif-buttons"}, true);
					for (var i = 0; i < elements.length; i++)
						BX.remove(elements[i]);
				}
			}
		});

		function _confirmRequest(el)
		{
			BX.remove(el.parentNode);
			BX.ImLegacy.confirmRequest({
				notifyId: el.getAttribute('data-notifyId'),
				notifyValue: el.getAttribute('data-notifyValue')
			})
		}

		function urlValidation(el)
		{
			let link = BX.util.htmlspecialcharsback(el.getAttribute('data-url'));

			try
			{
				var url = new URL(link, location.origin);
			}
			catch(e)
			{
				el.style="";
				el.onclick="";
				return false;
			}

			var allowList = [
				"http:",
				"https:",
				"ftp:",
				"file:",
				"tel:",
				"callto:",
				"mailto:",
				"skype:",
				"viber:",
			];
			if (allowList.indexOf(url.protocol) <= -1)
			{
				el.style="";
				el.onclick="";
				return false;
			}

			BXMobileApp.PageManager.loadPageBlank({url: url.href})
		}
	</script>
<?endif;?>
	<script type="text/javascript">
		document.body.style.overflow = "hidden";
		document.body.style.overflowY = "scroll";
		var maxHeightFromCssStyle = null;
		BX.bind(window, "load", function(){
			var blocks = BX.findChildrenByClassName(BX("notif-block-wrap"), "notif-inner", true);
			if(maxHeightFromCssStyle == null)
			{
				try
				{
					maxHeightFromCssStyle = getComputedStyle(window.document.querySelector(".notif-inner")).maxHeight;
				}
				catch (e)
				{
					maxHeightFromCssStyle = "210px";
				}
			}

			var maxHeight = 200;
			if(typeof maxHeightFromCssStyle == "string")
				maxHeight = parseInt(maxHeightFromCssStyle);

			console.log(maxHeightFromCssStyle);
			for(var i in blocks)
			{
				var foldId = blocks[i].getAttribute("data-fold");
				var foldButton = BX(foldId);
				if(blocks[i].scrollHeight > maxHeight)
				{
					var gradient = BX.create("DIV", {
						props:{
							className: "notif-bottom-gradient"
						}});

					gradient.style.backgroundImage = 'linear-gradient(to bottom,rgba(255,255,255,0),rgba(255,255,255,0.1) 0px,rgba(255,255,255,1) 14px)';
					blocks[i].appendChild(gradient);
					if(foldButton)
						foldButton.style.visibility = "visible";
					BX.bind(foldButton, "click", function(e){

						var foldButton = e.target;
						var block = BX(foldButton.getAttribute("data-inner"));
						var gradient = BX.findChildrenByClassName(block, "notif-bottom-gradient")[0];
						console.log(gradient);
						var scrollToY;

						var delta = Math.abs(parseInt(block.scrollHeight)-maxHeight);
						var initHeight = parseInt(block.style.maxHeight);

						console.log(block.scrollHeight,maxHeight);

						if(typeof block.bxfolded == "undefined" || block.bxfolded == true)
						{
							initHeight = maxHeight;
							gradient.style.visibility = "hidden";
							block.bxfolded = false;
							foldButton.innerHTML = "<?=GetMessage("NM_FOLD")?>";
							gradient.style.backgroundImage = "";

							(new BX.easing({
								duration : 200,
								start : { percent: 0},
								finish : { percent:100},
								transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
								step : BX.delegate(function(state){
									block.style.maxHeight = initHeight+delta*state.percent/100 +"px";
								}, this)
							})).animate();
						}
						else
						{
							initHeight = parseInt(block.style.maxHeight);
							gradient.style.visibility = "visible";
							foldButton.innerHTML = "<?= GetMessage("NM_UNFOLD")?>";
							scrollToY = Math.max(0, window.pageYOffset - (block.scrollHeight - maxHeight));
							block.bxfolded = true;
							(new BX.easing({
								duration : 200,
								start : { pos : window.pageYOffset, height: 0},
								finish : { pos : scrollToY, height:100},
								transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
								step : BX.delegate(function(state){
									window.scrollTo(0, state.pos);
									console.log(delta, delta*state.height/100);
									block.style.maxHeight = initHeight-delta * state.height/100 +"px";
								}, this)
							})).animate();

						}

						e.preventDefault();

					})
				}
				else
				{

					if(foldButton)
						foldButton.style.visibility = "hidden";
				}

			}
		});


		function deleteNotification(e)
		{
			var id = e.target.getAttribute("data-id");

			var height = parseInt(getComputedStyle(BX("notify"+id)).height);
			var paddingTop = parseInt(getComputedStyle(BX("notify"+id)).paddingTop);

			(new BX.easing({
				duration : 400,
				start : { per : 100},
				finish : { per : 0},
				complete:function(){
					BX.remove(BX(BX("notify"+id)));
					var notifyContainer = BX("notif-block-wrap");
					if(notifyContainer.childElementCount == 0)
					{
						BX.remove(BX("notif-block-wrap"));
						document.body.appendChild(BX.create("DIV", {
							props:{
								className: "notif-block-empty"
							},
							html:"<?=GetMessage('NM_EMPTY');?>"
						}))
					}
				},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step : BX.delegate(function(state){
					var percent = state.per/100;
					BX("notify"+id).style.opacity = percent+0.2;
					console.log(paddingTop*(state.per/100));
					BX("notify"+id).style.height = (height*percent)+"px";
					BX("notify"+id).style.paddingTop = paddingTop*percent+"px";
					BX("notify"+id).style.paddingBottom = 0;

				}, this)
			})).animate();



			BX.ajax.post("/mobile/ajax.php?mobile_action=im",
				{
					NOTIFY_REMOVE:"",
					IM_NOTIFY_REMOVE:"Y",
					NOTIFY_ID: id,
					sessid: BX.bitrix_sessid()
				}
			);


		}

		function onTouch(object, link, event)
		{
			if (event.target.tagName === 'A')
			{
				return;
			}

			if (BX.hasClass(event.target, "notif-fold-button"))
			{
				return;
			}

			if(!link)
				return;
			var hoverTimeout = setTimeout(function(){
				object.style.background = "#f0f0f0";
			}, 100);
			var removeHover = BX.proxy(function(){
				clearTimeout(hoverTimeout);
				BX.unbind(document.body, "touchend", removeHover,{ passive: true });
				window.removeEventListener("scroll", removeHover);
				object.style.background = "#ffffff";
			}, this);

			BX.bind(document.body, "touchend", removeHover);
			window.addEventListener("scroll", removeHover, { passive: true });
		}

		if (app.enableInVersion(10))
		{
			BXMobileApp.UI.Page.TopBar.title.setText("<?=GetMessage("NM_TITLE")?>");
			BXMobileApp.UI.Page.TopBar.title.show();

			app.titleAction("setParams", {text: "<?=GetMessage("NM_TITLE")?>", useProgress: false});
		}
		app.pullDown({
			'enable': true,
			'pulltext': '<?=GetMessage('NM_PULLTEXT')?>',
			'downtext': '<?=GetMessage('NM_DOWNTEXT')?>',
			'loadtext': '<?=GetMessage('NM_LOADTEXT')?>',
			'callback': function(){
				app.titleAction("setParams", {text: "<?=GetMessage("NM_TITLE")?>", useProgress: true});
				location.reload();
			}
		});

		clearTimeout(window.onNotificationsOpenTimeout);
		window.onNotificationsOpenTimeout = setTimeout(function(){
			var lastId = <?=$arResult['UNREAD_NOTIFY_ID']?>;

			BXMobileApp.onCustomEvent("onNotificationsOpen", {lastId: lastId}, true);

			BXMobileApp.Events.postToComponent("chatdialog::notification::readAll", [{}, true], 'im.recent');

			if (lastId > 0)
			{
				BX.ajax({
					url: '/mobile/ajax.php?mobile_action=im',
					method: 'POST',
					dataType: 'json',
					skipAuthCheck: true,
					data: {'IM_NOTIFY_READ': 'Y', 'ID': lastId, 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()},
				});
			}

		}, 300);

		BX.addCustomEvent("onFrameDataReceived", function(data){
			BitrixMobile.LazyLoad.showImages();
		});

		BX.addCustomEvent("onOpenPageAfter", () => {
			window.skipReload = false;
			if (window.needReload)
			{
				reloadAfterNewNotify()
			}
		});
		BX.addCustomEvent("onHidePageBefore", () => {
			window.skipReload = true;
		});

		window.refreshEasingStart = false;

		BXMobileApp.addCustomEvent("onBeforeNotificationsReload", reloadAfterNewNotify);

		function reloadAfterNewNotify()
		{
			if (window.skipReload || document.firstElementChild.scrollTop > 100)
			{
				window.needReload = true;
			}
			else
			{
				app.titleAction("setParams", {text: "<?=GetMessage("NM_TITLE")?>", useProgress: true});
				location.reload();
			}
		}

		BX.bind(document, "scroll", BX.debounce(function(e)
		{
			if (!window.needReload)
			{
				return false;
			}

			if(document.firstElementChild.scrollTop < 100)
			{
				window.needReload = false;
				app.titleAction("setParams", {text: "<?=GetMessage("NM_TITLE")?>", useProgress: true});
				location.reload();
			}
		}, 300));

	</script>
<?
function decodeBbCode($text)
{
	$text = htmlspecialcharsbx($text);

	$text = str_replace('[BR]', '<br>', $text);

	$text = preg_replace_callback('/\[url=([^\s\]]+)\s*\](.*?)\[\/url\]/i', function($match) {
		return '<span data-url="'.$match[1].'" onclick="urlValidation(this)" style="color: #2067b0;font-weight: bold;">'.$match[2].'</a>';
	}, $text);

	$text = preg_replace_callback('/\[url\](.*?)\[\/url\]/i', function($match) {
		return '<span data-url="'.$match[1].'" onclick="urlValidation(this)" style="color: #2067b0;font-weight: bold;">'.$match[1].'</a>';
	}, $text);

	$text = preg_replace_callback('/\[([buis])\](.*?)\[(\/[buis])\]/i', function($match) {
		return '<'.$match[1].'>'.$match[2].'<'.$match[3].'>';
	}, $text);

	$text = \Bitrix\Im\Text::removeBbCodes($text);

	return $text;
}

function getNotifyParamsHtml($params)
{
	$result = '';
	if (empty($params['ATTACH']))
		return $result;

	foreach ($params['ATTACH'] as $attachBlock)
	{
		$blockResult = '';
		foreach ($attachBlock['BLOCKS'] as $attach)
		{
			if (isset($attach['USER']))
			{
				$subResult = '';
				foreach ($attach['USER'] as $userNode)
				{
					$subResult .= '<span class="bx-messenger-attach-user">
						<span class="bx-messenger-attach-user-avatar">
							'.($userNode['AVATAR']? '<img src="'.$userNode['AVATAR'].'" class="bx-messenger-attach-user-avatar-img">': '<span class="bx-messenger-attach-user-avatar-img bx-messenger-attach-user-avatar-default">').'
						</span>
						<span class="bx-messenger-attach-user-name">'.htmlspecialcharsbx($userNode['NAME']).'</span>
					</span>';
				}
				$blockResult .= '<span class="bx-messenger-attach-users">'.$subResult.'</span>';
			}
			else if (isset($attach['LINK']))
			{
				$subResult = '';
				foreach ($attach['LINK'] as $linkNode)
				{
					$subResult .= '<span class="bx-messenger-attach-link bx-messenger-attach-link-with-preview">
						<a class="bx-messenger-attach-link-name" href="'.$linkNode['LINK'].'">'.($linkNode['NAME']? htmlspecialcharsbx($linkNode['NAME']): $linkNode['LINK']).'</a>
						'.(!$linkNode['PREVIEW']? '': '<span class="bx-messenger-file-image-src"><img src="'.$linkNode['PREVIEW'].'" class="bx-messenger-file-image-text"></span>').'
					</span>';
				}
				$blockResult .= '<span class="bx-messenger-attach-links">'.$subResult.'</span>';
			}
			else if (isset($attach['MESSAGE']))
			{
				$blockResult .= '<span class="bx-messenger-attach-message">'.decodeBbCode($attach['MESSAGE']).'</span>';
			}
			else if (isset($attach['HTML']))
			{
				$blockResult .= '<span class="bx-messenger-attach-message">'.$attach['HTML'].'</span>';
			}
			else if (isset($attach['GRID']))
			{
				$subResult = '';
				foreach ($attach['GRID'] as $gridNode)
				{
					$width = $gridNode['WIDTH'] ? 'width: '.$gridNode['WIDTH'].'px' : '';
					$subResult .= '<span class="bx-messenger-attach-block bx-messenger-attach-block-'.(mb_strtolower($gridNode['DISPLAY'])).'" style="'.($gridNode['DISPLAY'] == 'LINE' ? $width : '').'">
							<div class="bx-messenger-attach-block-name" style="'.($gridNode['DISPLAY'] == 'ROW' ? $width : '').'">'.htmlspecialcharsbx($gridNode['NAME']).'</div>
							<div class="bx-messenger-attach-block-value" style="'.($gridNode['COLOR'] ? 'color: '.$gridNode['COLOR'] : '').'">'.decodeBbCode($gridNode['VALUE']).'</div>
						</span>';
				}
				$blockResult .= '<span class="bx-messenger-attach-blocks">'.$subResult.'</span>';
			}
			else if (isset($attach['DELIMITER']))
			{
				$style = "";
				if ($attach['DELIMITER']['SIZE'])
				{
					$style .= "width: ".$attach['DELIMITER']['SIZE']."px;";
				}
				if ($attach['DELIMITER']['COLOR'])
				{
					$style .= "background-color: ".htmlspecialcharsbx($attach['DELIMITER']['COLOR']);
				}
				if ($style)
				{
					$style = 'style="'.$style.'"';
				}
				$blockResult .= '<span class="bx-messenger-attach-delimiter" '.$style.'></span>';
			}
			else if (isset($attach['IMAGE']))
			{
				$subResult = '';
				foreach ($attach['IMAGE'] as $imageNode)
				{
					$imageNode['PREVIEW'] = $imageNode['PREVIEW']? $imageNode['PREVIEW']: $imageNode['LINK'];
					$subResult .= '<span class="bx-messenger-file-image-src"><img src="'.$imageNode['PREVIEW'].'" class="bx-messenger-file-image-text"></span>';
				}
				$blockResult .= '<span class="bx-messenger-attach-images">'.$subResult.'</span>';
			}
			else if (isset($attach['FILE']))
			{
				$subResult = '';
				foreach ($attach['FILE'] as $fileNode)
				{
					$subResult .=
						'<div class="bx-messenger-file">
							<div class="bx-messenger-file-attrs">
								<span class="bx-messenger-file-title">
									<span class="bx-messenger-file-title-name">'.htmlspecialcharsbx($fileNode['NAME']).'</span>
								</span>
								'.($fileNode['SIZE']? '<span class="bx-messenger-file-size">'.CFile::FormatSize($fileNode['SIZE']).'</span>':'').'
							</div>
						</div>';
				}
				$blockResult .= '<span class="bx-messenger-attach-files">'.$subResult.'</span>';
			}
		}
		if ($blockResult)
		{
			$color = $attachBlock['COLOR']? htmlspecialcharsbx($attachBlock['COLOR']): '#818181';
			$result .= '<div class="bx-messenger-attach" style="border-color:'.$color.'">'.$blockResult.'</div>';
		}
	}
	if ($result)
	{
		$result = '<div class="bx-messenger-attach-box">'.$result.'</div>';
	}
	return $result;
}
?>