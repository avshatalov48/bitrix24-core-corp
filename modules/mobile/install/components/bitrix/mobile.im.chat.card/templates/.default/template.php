<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadString('<script type="text/javascript" src="'.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH."/im_mobile.js").'"></script>');

if ($arResult['CHAT']['id'] == CIMChat::GetGeneralChatId())
{
	$chatType = 'general';
}
else
{
	$chatType = $arResult['CHAT']['type'];
}
?>
<div class="chat-profile-wrap">
	<div id="chat-profile-top" class="chat-profile-top <?=$chatType?>-profile-top" style="background-color: <?=$arResult['CHAT']['color']?>;<?=($arResult['CHAT']['avatar']? "background-image: url('".$arResult['CHAT']['avatar']."')": '')?>">
		<div class="chat-profile-title" id="chat-profile-title">
			<div class="chat-profile-title-inner">
				<?=$arResult['CHAT']['name']?>
			</div>
		</div>
	</div>
	<div class="chat-profile-users-list-block">
		<?if ($_GET['actions'] == 'Y'):?>
		<div class="chat-profile-actions">
			<span id="chat-write" class="chat-profile-action chat-profile-action-write"><span><?=GetMessage('GO_TO_CHAT')?></span></span>
		</div>
		<?endif?>
		<div class="chat-profile-users-list-title"><?=GetMessage('USERS')?>:</div>
		<div class="chat-profile-users-list" id="chat-profile-users-list" >
			<?
			$jsIds = "";
			foreach ($arResult['USERS'] as $user):
				if (!$user['bot'] && !$user['active'])
					continue;
				$avatarId = "chat-avatar-".$user['id'];
				$jsIds .= $jsIds !== "" ? ', "'.$avatarId.'"' : '"'.$avatarId.'"';
				?><a id="chat-profile-user-<?=$user['id']?>" class="chat-profile-user" href="#" onclick="app.loadPageBlank({url: '<?=SITE_DIR?>mobile/users/?user_id=<?=$user['id']?>', bx24ModernStyle: true}); return false;"><span class="ml-avatar"><span class="ml-avatar-sub" style="background-size:cover" id="<?=$avatarId?>" data-src="<?=$user['avatar']?>"></span></span><span class="chat-profile-user-name"><?=$user['name']?></span></a>
			<?endforeach;?>
			<script type="text/javascript">
				BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);
			</script>
		</div>
	</div>
</div>
<script type="text/javascript">
	BX.bind(BX('chat-write'), 'click', function(){
		BX.MobileTools.openChat('chat<?=$arResult['CHAT']['id']?>', {
			name: 'chat<?=$arResult['CHAT']['name']?>',
			avatar: '<?=$arResult['CHAT']['avatar']?>',
			color: '<?=$arResult['CHAT']['color']?>'
		});
	});
	if (app.enableInVersion(10))
	{
		BXMobileApp.UI.Page.TopBar.title.setText("<?=GetMessage("CHAT_TITLE")?>");
		BXMobileApp.UI.Page.TopBar.title.show();
		app.exec("setTopBarColors",{background: "<?=$arResult['CHAT']['color']?>", titleText:"#ffffff", titleDetailText:"#f0f0f0"});
	}
	closeDialog = false;
	app.pullDown({
		enable: true,
		action: "RELOAD"
	});
	pageColor = '<?=$arResult['CHAT']['color']?>';
	BX.addCustomEvent("onPull-im", function(data)
	{
		if (data.command == 'messageChat')
		{
			if (data.params.MESSAGE.recipientId == 'chat'+<?=$arResult['CHAT_ID']?>)
			{
				closeDialog = false;
			}
		}
		else if (data.command == 'chatChangeColor')
		{
			if (data.params.chatId == <?=$arResult['CHAT_ID']?>)
			{
				pageColor = data.params.color;
				BX.style(BX('chat-profile-top'), 'background-color', data.params.color);
				app.exec("setTopBarColors",{background: data.params.color, titleText:"#ffffff", titleDetailText:"#f0f0f0"});
			}
		}
		else if (data.command == 'chatRename')
		{
			if (<?=$arResult['CHAT_ID']?> == data.params.chatId)
				BX('chat-profile-title').innerHTML = data.params.name;
		}
		else if (data.command == 'chatAvatar')
		{
			if (<?=$arResult['CHAT_ID']?> == data.params.chatId)
			{
				BX.ajax({
					url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/')+'mobile/ajax.php?mobile_action=im&',
					method: 'POST',
					dataType: 'json',
					timeout: 60,
					data: {'IM_GET_MOBILE_CHAT_AVATAR' : 'Y', 'CHAT_ID' : data.params.chatId, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
					onsuccess: BX.delegate(function(data){
						if (data.AVATAR != '')
							BX.style(BX('chat-profile-top'), 'background-image', "url('"+data.AVATAR+"')");
					}, this)
				});
			}
		}
		else if (data.command == 'chatUserAdd')
		{
			for (var i = 0; i < data.params.newUsers; i++)
			{
				var user = data.params.users[data.params.newUsers[i]];
				BX('chat-profile-users-list').appendChild(BX.create("a", {
					props : { className : "chat-profile-user"},
					attrs : { id: 'chat-profile-user-'+user.id, href : '#', onclick : 'app.loadPageBlank({url:\'<?=SITE_DIR?>mobile/users/?user_id='+user.id+'\', bx24ModernStyle: true}); return false;'},
					children : [
						BX.create("span", { props : { className : "avatar"}, children: [
							BX.create("span", { props : { className : "avatar_sub"}, attrs : {style : 'background:url('+user.avatar+') center no-repeat; background-size:29px'}})
						]}),
						BX.create("span", { props : { className : "chat-profile-user-name"}, html: user.name})
					]
				}));
			}
		}
		else if (data.command == 'chatUserLeave')
		{
			if (data.params.userId == BX.message('USER_ID'))
			{
				app.checkOpenStatus({
					'callback' : function(data)
					{
						if (data && data.status == 'visible')
						{
							app.closeController();
						}
						else
						{
							closeDialog = true;
						}
					}
				});
			}
			else
			{
				BX.remove(BX('chat-profile-user-'+data.params.userId));
			}
		}
	});
	BX.addCustomEvent("onOpenPageAfter", function(){
		app.exec("setTopBarColors",{background: pageColor, titleText:"#ffffff", titleDetailText:"#f0f0f0"});
		if (closeDialog)
		{
			closeDialog = false;
			app.closeController({'drop': true});
		}
	});
</script>