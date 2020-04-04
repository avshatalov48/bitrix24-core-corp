<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/** @var $APPLICATION CAllMain */
/** @var $arResult array */
CJSCore::Init(array('socnetlogdest'));

function wdPrintAccessTab(array $arResult, $printButtons = true)
{
	return '
	<div class="bx-webdav-invite-access">
		<form onsubmit="BX.PreventDefault();" action="' . $arResult['USER_DISK']['SHARE_URL'] .'" name="webdav-invite-share" id="webdav-invite-share" method="POST">
		<div class="bx-webdav-invite-access-title">' . GetMessage('WD_INVITE_MODAL_TITLE_SHARING'). ' "' . $arResult['TARGET_NAME']  .'"</div>
		<div class="bx-webdav-invite-cont">
			<div class="bx-webdav-invite-whom">
				<div class="bx-webdav-invite-whom-l">' . GetMessage('WD_INVITE_MODAL_DESTINATION'). '</div>
				<div id="feed-add-post-destination-container-post" class="bx-webdav-invite-whom-r">
					<span id="feed-add-post-destination-item"></span>
					<span class="feed-add-destination-input-box" id="feed-add-post-destination-input-box">
						<input autocomplete="off" type="text" value="" class="feed-add-destination-inp" id="feed-add-post-destination-input"/>
					</span>
					<a href="#" class="feed-add-destination-link" id="bx-destination-tag">' . GetMessageJS("WD_INVITE_MODAL_DESTINATION_1"). '</a>
				</div>
			</div>
			<textarea name="invite_description" id="inviteDescription" class="bx-webdav-invite-textar" placeholder="' .  GetMessage('WD_INVITE_MODAL_PLACEHOLDER') . '"></textarea>
			<div class="bx-webdav-invite-checkbox-wrap">
				<input name="can_edit" type="checkbox" class="bx-webdav-invite-checkbox" id="canEdit" checked="checked"/><label for="canEdit" class="bx-webdav-invite-label">' .  GetMessage('WD_INVITE_MODAL_CAN_EDIT'). '</label>
			</div>
		</div>
		' . ($printButtons? wdPrintAccessTabButtons($arResult) : '') . '
	</div>
';
}

function wdPrintAccessTabButtons(array $arResult)
{
	return '
	<div class="bx-webdav-invite-footer">
		<a style="display: none;" id="bx-webdav-invite-link-access" href="javascript:void(0)" onclick="wdShareToUser();" class="webform-button webform-button-create"><span class="webform-button-left"></span><span class="webform-button-text">' .  GetMessage('WD_INVITE_MODAL_BTN_OPEN_ACCESS'). '</span><span class="webform-button-right"></span></a><a href="javascript:void(0)" onclick="wdCloseCurrentPopup();" class="webform-button"><span class="webform-button-left"></span><span class="webform-button-text">' .  GetMessage('WD_INVITE_MODAL_BTN_CANCEL'). '</span><span class="webform-button-right"></span></a>
	</div>
	';
}
?>
<script type="text/javascript">
	var BXSocNetLogDestinationFormName = '<?=randString(6)?>';
	BX.loadCSS('/bitrix/components/bitrix/webdav.invite/templates/.default/style.css');
</script>
<? if(empty($arResult['CONNECTED_USERS_CAN_EDITED_COUNT']) && empty($arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT']) && empty($arResult['DISCONNECTED_USERS_COUNT'])) {?>
	<?= wdPrintAccessTab($arResult) ?>
<? } else {?>
<div class="bx-webdav-invite-tabs-wrap" id="bx-webdav-invite-tabs">
	<div class="bx-webdav-invite-tabs">
		<span onclick="wdChangeTab(this, 'bx-webdav-invite-show-access-list')" class="bx-webdav-invite-tab bx-webdav-invite-tab-active"><?= GetMessage('WD_INVITE_MODAL_TAB_HEADER_ACCESS') ?></span><span id="wd-tab-access-add" onclick="wdChangeTab(this, 'bx-webdav-invite-show-access-add')" class="bx-webdav-invite-tab"><?= GetMessage('WD_INVITE_MODAL_TAB_HEADER_MANAGE_ACCESS') ?></span>
	</div>
	<div id="bx-webdav-invite-show-access-list" class="bx-webdav-invite-tabs-cont">
		<span class="bx-webdav-invite-users bx-webdav-invite-owner">
			<span class="bx-webdav-invite-us-avatar"><?
				if(!empty($arResult['OWNER']['PHOTO_SRC']))
				{
					?><img height="21" width="21" src="<?= $arResult['OWNER']['PHOTO_SRC'] ?>" alt="<?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?>"/><?
				}
			?></span>
			<a class="bx-webdav-invite-us-name" href="<?= $arResult['OWNER']['HREF']; ?>" target="_blank" title="<?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?>"><?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?></a><span class="bx-webdav-invite-us-descript"><?= GetMessage('WD_INVITE_MODAL_OWNER_USER') ?></span>
		</span>
		<? if($arResult['CONNECTED_USERS_CAN_EDITED_COUNT'] > 0){ ?>
		<div class="bx-webdav-invite-users-list" style="height: auto;">
			<div onclick="wdOpenUsersList(this, 'can_edit');" class="bx-webdav-invite-users-title">
				<span class="bx-webdav-invite-users-title-text"><?=
					CWebDavTools::getNumericCase(
						$arResult['CONNECTED_USERS_CAN_EDITED_COUNT'],
						GetMessage('WD_INVITE_MODAL_TAB_USERS_USAGE_CAN_EDIT_COUNT_1', array('#COUNT#' => $arResult['CONNECTED_USERS_CAN_EDITED_COUNT'])),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_USAGE_CAN_EDIT_COUNT_21', array('#COUNT#' => $arResult['CONNECTED_USERS_CAN_EDITED_COUNT'])),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_USAGE_CAN_EDIT_COUNT_2_4', array('#COUNT#' => $arResult['CONNECTED_USERS_CAN_EDITED_COUNT'])),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_USAGE_CAN_EDIT_COUNT_5_20', array('#COUNT#' => $arResult['CONNECTED_USERS_CAN_EDITED_COUNT']))
					);
				?></span>
				<span class="bx-webdav-invite-users-arrow"></span>
			</div>
			<div class="bx-webdav-invite-users-block"></div>
		</div>
		<? } ?>
		<? if($arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'] > 0){ ?>
		<div class="bx-webdav-invite-users-list" style="height: auto;">
			<div onclick="wdOpenUsersList(this, 'cannot_edit');" class="bx-webdav-invite-users-title">
				<span class="bx-webdav-invite-users-title-text"><?=
					CWebDavTools::getNumericCase(
						$arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'],
						GetMessage('WD_INVITE_MODAL_TAB_USERS_USAGE_CANNOT_EDIT_COUNT_1', array('#COUNT#' => $arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'])),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_USAGE_CANNOT_EDIT_COUNT_21', array('#COUNT#' => $arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'])),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_USAGE_CANNOT_EDIT_COUNT_2_4', array('#COUNT#' => $arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'])),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_USAGE_CANNOT_EDIT_COUNT_5_20', array('#COUNT#' => $arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT']))
					);
				?></span>
				<span class="bx-webdav-invite-users-arrow"></span>
			</div>
			<div class="bx-webdav-invite-users-block"></div>
		</div>
		<? } ?>
		<? if($arResult['DISCONNECTED_USERS_COUNT'] > 0){ ?>
		<div class="bx-webdav-invite-users-list" style="height: auto;">
			<div onclick="wdOpenUsersList(this, 'disconnect');" class="bx-webdav-invite-users-title">
				<span class="bx-webdav-invite-users-title-text"><?=
					CWebDavTools::getNumericCase(
						$arResult['DISCONNECTED_USERS_COUNT'],
						GetMessage('WD_INVITE_MODAL_TAB_USERS_NOT_USAGE_COUNT_1', array('#COUNT#' => $arResult['DISCONNECTED_USERS_COUNT'])),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_NOT_USAGE_COUNT_21', array('#COUNT#' => $arResult['DISCONNECTED_USERS_COUNT'])),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_NOT_USAGE_COUNT_2_4', array('#COUNT#' => $arResult['DISCONNECTED_USERS_COUNT'])),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_NOT_USAGE_COUNT_5_20', array('#COUNT#' => $arResult['DISCONNECTED_USERS_COUNT']))
					);
				?></span>
				<span class="bx-webdav-invite-users-arrow"></span>
			</div>
			<div class="bx-webdav-invite-users-block"></div>
		</div>
		<? } ?>
		<div class="bx-webdav-invite-footer-link-block">
			<a onclick="wdUnshareAllUser();" class="bx-webdav-invite-footer-link" href="javascript:void(0)"><?= GetMessage('WD_INVITE_MODAL_BTN_DIE_ACCESS'); ?></a>
		</div>
	</div>
	<div id="bx-webdav-invite-show-access-add" class="bx-webdav-invite-tabs-cont" style="display: none;">
		<?= wdPrintAccessTab($arResult, false) ?>
	</div>
	<?= wdPrintAccessTabButtons($arResult) ?>
</div>
<? } ?>

<script type="text/javascript">

wdUserToShare = {};
function onSelectUserToShare(users)
{
	wdUserToShare = {};
	for(var i in users)
	{
		if(!users.hasOwnProperty(i))
		{
			continue;
		}
		if(users[i])
		{
			wdUserToShare[users[i].id] = users[i];
		}
	}
}

function wdOpenUsersList(currentTitle, type)
{
	currentTitle = BX(currentTitle);
	if(!BX.hasClass(currentTitle, 'bx-webdav-invite-users-list-open'))
	{
		BX.addClass(currentTitle, 'bx-webdav-invite-users-list-open');
		var listContainer = BX.findChild(currentTitle.parentNode, {className : "bx-webdav-invite-users-block"}, true);
		if(!listContainer)
		{
			return;
		}
		if(BX.hasClass(currentTitle, 'bx-webdav-invite-users-list-loaded'))
		{
			BX.show(listContainer);
		}
		else
		{
			BX.ajax({
				'method': 'POST',
				'dataType': 'html',
				'url': "<?= $arResult['USER_DISK']['LIST_USERS_CAN_EDIT_URL']; ?>",
				'data': {
					'userListType': type,
					'sessid': BX.bitrix_sessid()
				},
				'onsuccess': function (data) {
					if (!data) {
						return;
					}
					listContainer.innerHTML = data;
					BX.show(listContainer);
					BX.addClass(currentTitle, 'bx-webdav-invite-users-list-loaded');
				}
			});
		}
	}
	else
	{
		BX.removeClass(currentTitle, 'bx-webdav-invite-users-list-open');
		var listContainer = BX.findChild(currentTitle.parentNode, {className : "bx-webdav-invite-users-block"}, true);
		if(!listContainer)
		{
			return;
		}
		BX.hide(listContainer);
	}
}

function wdLoadPortionUsersList(currentMore, type, page)
{
	page = page || 0;
	currentMore = BX(currentMore);
	var moreCont = BX.findPreviousSibling(BX(currentMore).parentNode, {className : "bx-webdav-invite-us-place-for-users"});
	if(!moreCont)
	{
		return;
	}
	BX.ajax({
		'method': 'POST',
		'dataType': 'html',
		'url': "<?= $arResult['USER_DISK']['LIST_USERS_CAN_EDIT_URL']; ?>",
		'data': {
			'userListType': type,
			page: page,
			sessid: BX.bitrix_sessid()
		},
		'onsuccess': function (data) {
			if (!data) {
				return;
			}
			moreCont.innerHTML = data;
			BX.remove(currentMore.parentNode);
			return;

			if (moreCont.children && moreCont.children.length > 0)
			{
				for (var j=0, len=moreCont.children.length; j<len; j++)
				{
					if (BX.type.isNotEmptyString(moreCont.children[j]))
						moreCont.parentNode.innerHTML += moreCont.children[j];
					else if (BX.type.isElementNode(moreCont.children[j]))
						moreCont.parentNode.appendChild(moreCont.children[j]);
				}
			}
			BX.remove(moreCont);
			BX.remove(currentMore.parentNode);
		}
	});
}

function wdChangeTab(currentTab, tabId)
{
	if(currentTab)
	{
		var tabs = BX.findChildren(BX('bx-webdav-invite-tabs'), {className : "bx-webdav-invite-tab"}, true);
		for (var i = 0; i < tabs.length; i++)
		{
			if (tabs[i] === currentTab)
			{
				BX.addClass(tabs[i], "bx-webdav-invite-tab-active");
			}
			else
			{
				BX.removeClass(tabs[i], "bx-webdav-invite-tab-active");
			}
		}
	}

	if(tabId == 'bx-webdav-invite-show-access-list')
	{
		var accessAdd = BX('bx-webdav-invite-show-access-add');
		var accessShow = BX('bx-webdav-invite-link-access');
		if(accessAdd)
		{
			BX.hide(accessAdd);
		}
		if(accessShow)
		{
			BX.hide(accessShow);
		}
		var tab = BX(tabId);
		if(tab)
		{
			BX.show(tab);
		}
	}
	else if(tabId == 'bx-webdav-invite-show-access-add')
	{
		var accessShow = BX('bx-webdav-invite-link-access');
		if(accessShow)
		{
			BX.show(accessShow, 'inline-block');
		}

		var tab = BX('bx-webdav-invite-show-access-list');
		if(tab)
		{
			BX.hide(tab);
		}
		tab = BX(tabId);
		if(tab)
		{
			BX.show(BX(tabId));
		}
	}
}

function wdUnshareAllUser() {

	var createDialog = BX.create('div', {
		props: {
			className: 'bx-viewer-confirm'
		},
		children: [
			BX.create('div', {
				props: {
					className: 'bx-viewer-confirm-title'
				},
				text: "<?= GetMessageJS('WD_INVITE_MODAL_TITLE_DIE_ALL_ACCESS_SIMPLE');  ?>",
				children: []
			}),
			BX.create('div', {
				props: {
					className: 'bx-viewer-confirm-text-wrap'
				},
				children: [
					BX.create('span', {
						props: {
							className: 'bx-viewer-confirm-text-alignment'
						}
					}),
					BX.create('span', {
						props: {
							className: 'bx-viewer-confirm-text'
						},
						text: "<?= GetMessageJS('WD_INVITE_MODAL_TITLE_DIE_ALL_ACCESS_DESCR');  ?>"
					})
				]
			})
		]
	});

	var paramsWindow = {
		content: createDialog,
		onPopupClose : function() { this.destroy() },
		closeByEsc: true,
		autoHide: false,
		overlay: true,
		buttons: [
			new BX.PopupWindowButton({
				text : "<?= GetMessageJS('WD_INVITE_MODAL_BTN_DIE_SELF_ACCESS_SIMPLE');  ?>",
				className : "popup-window-button-accept",
				events : { click : function(e)
							{
								BX.PopupWindowManager.getCurrentPopup().destroy();
								BX.PreventDefault(e);


var waiterBox = BX.create('div', {
	props: {
		className: 'bx-viewer-alert'
	},
	children: [
		BX.create('span', {
			props: {
				className: 'bx-viewer-alert-icon'
			},
			children: [
				BX.create('img', {
					props: {
						src: '/bitrix/js/main/core/images/yell-waiter.gif'
					}
				})
			]
		}),
		BX.create('span', {
			props: {
				className: 'bx-viewer-aligner'
			}
		}),
		BX.create('span', {
			props: {
				className: 'bx-viewer-alert-text'
			},
			text: '<?= GetMessageJS('WD_INVITE_MODAL_TAB_PROCESS_DIE_ACCESS');  ?>'
		}),
		BX.create('a', {
			props: {
				className: 'bx-viewer-alert-close-icon',
				href: '#'
			},
			events: {
				click: function(e)
				{
					BX.PopupWindowManager.getCurrentPopup().destroy();
					BX.PreventDefault(e);
					return false;
				}
			}
		})
	]
});

var paramsWindow = {
	content: waiterBox,
	onPopupClose : function() { this.destroy() },
	closeByEsc: false,
	autoHide: false,
	overlay: true,
	zIndex: 10200,
	className: 'bx-viewer-alert-popup'
};

var popupConfirm = BX.PopupWindowManager.create('bx-gedit-convert-confirm-copy', null, paramsWindow);
popupConfirm.show();

BX.ajax({
	'method': 'POST',
	'dataType': 'json',
	'url': "<?= $arResult['USER_DISK']['UNSHARE_URL']; ?>",
	'data': {
		sessid: BX.bitrix_sessid()
	},
	'onsuccess': function (data) {
		if (!data) {
			return;
		}
		BX.onCustomEvent('OnUnshareInviteAllUsers', [data]);
		if (!data.status || data.status != 'success') {
			return;
		}
		var messageBox = BX.create('div', {
			props: {
				className: 'bx-viewer-alert'
			},
			children: [
				BX.create('span', {
					props: {
						className: 'bx-viewer-alert-icon'
					},
					children: [
						BX.create('img', {
							props: {
								src: '/bitrix/js/main/core/images/viewer-tick.png'
							}
						})
					]
				}),
				BX.create('span', {
					props: {
						className: 'bx-viewer-aligner'
					}
				}),
				BX.create('span', {
					props: {
						className: 'bx-viewer-alert-text'
					},
					text: '<?= GetMessageJS('WD_INVITE_MODAL_TAB_PROCESS_DIE_ACCESS_SUCCESS');  ?>'
				}),
				BX.create('div', {
					props: {
						className: 'bx-viewer-alert-footer'
					},
					children: []
				}),
				BX.create('a', {
					props: {
						className: 'bx-viewer-alert-close-icon',
						href: '#'
					},
					events: {
						click: function(e)
						{
							BX.PopupWindowManager.getCurrentPopup().destroy();
							BX.PreventDefault(e);
							return false;
						}
					}
				})
			]
		});
		BX.CViewer.unlockScroll();
		var paramsWindow = {
			content: messageBox,
			onPopupClose : function() { this.destroy() },
			closeByEsc: false,
			autoHide: false,
			zIndex: 10200,
			className: 'bx-viewer-alert-popup'
		};
		BX.PopupWindowManager.getCurrentPopup().destroy();
		var popupConfirm = BX.PopupWindowManager.create('bx-gedit-convert-confirm-copy', null, paramsWindow);
		popupConfirm.show();

		var idTimeout = setTimeout(function(){
			var w = BX.PopupWindowManager.getCurrentPopup();
			w.close();
			w.destroy();
		}, 3000);

		BX('bx-gedit-convert-confirm-copy').onmouseover = function(e){
			clearTimeout(idTimeout);
		};

		BX('bx-gedit-convert-confirm-copy').onmouseout = function(e){
			idTimeout = setTimeout(function(){
				var w = BX.PopupWindowManager.getCurrentPopup();
				w.close();
				w.destroy();
			}, 3000);
		};
	}
});

								return false;
							}
				}
			}),
			new BX.PopupWindowButton({
				text : "<?= GetMessageJS('WD_INVITE_MODAL_BTN_DIE_SELF_ACCESS_SIMPLE_CANCEL');  ?>",
				events : { click : function (e){
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		],
		zIndex: 10200
	};

	var popupConfirm = BX.PopupWindowManager.create('bx-link-unshare-users-confirm', null, paramsWindow);
	popupConfirm.show();
};

function wdUnshareUser(target, userId) {
	target = BX(target);
	if(!target)
	{
		return false;
	}

	BX.ajax({
		'method': 'POST',
		'dataType': 'json',
		'url': "<?= $arResult['USER_DISK']['UNSHARE_URL']; ?>",
		'data': {
			unshareUsers: [userId],
			sessid: BX.bitrix_sessid()
		},
		'onsuccess': function (data) {
			if (!data) {
				return;
			}
			if (data.status && data.status == 'success') {
				if(target.parentNode)
				{
					BX.remove(target.parentNode);
				}
			}
			return window.event? BX.PreventDefault() : false;
		}
	});

	//speeeed responsive user interfase
	if(target.parentNode)
	{
		BX.remove(target.parentNode);
	}


};

function wdCloseCurrentPopup()
{
	if(!BX.PopupWindowManager)
	{
		return;
	}
	if(BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog())
	{
		BX.SocNetLogDestination.closeDialog()
	}
	BX.PopupWindowManager.getCurrentPopup().close();
}

function wdShareToUser()
{
	var shareForm = BX('webdav-invite-share');
	var userIds = [];
	for(var i=0; i<shareForm.elements.length; i++)
	{
		var el = shareForm.elements[i];
		if (el.disabled)
			continue;

		if(el.type.toLowerCase() == 'hidden' && el.name == 'SPERM[U][]')
		{
			var search = el.value.match('U([0-9]+)');
			if(search[1])
			{
				userIds.push(search[1]);
			}
		}
	}
	if(!userIds.length)
	{
		return window.event? BX.PreventDefault() : false;
	}
	var inviteDescription = BX('inviteDescription').value;
	var canEdit = BX('canEdit') && BX('canEdit').checked ? 1 : 0;

	var waiterBox = BX.create('div', {
		props: {
			className: 'bx-viewer-alert'
		},
		children: [
			BX.create('span', {
				props: {
					className: 'bx-viewer-alert-icon'
				},
				children: [
					BX.create('img', {
						props: {
							src: '/bitrix/js/main/core/images/yell-waiter.gif'
						}
					})
				]
			}),
			BX.create('span', {
				props: {
					className: 'bx-viewer-aligner'
				}
			}),
			BX.create('span', {
				props: {
					className: 'bx-viewer-alert-text'
				},
				text: '<?= GetMessageJS('WD_INVITE_MODAL_TAB_PROCESS_ACCESS');  ?>'
			}),
			BX.create('a', {
				props: {
					className: 'bx-viewer-alert-close-icon',
					href: '#'
				},
				events: {
					click: function(e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		]
	});

	var paramsWindow = {
		content: waiterBox,
		onPopupClose : function() { this.destroy() },
		closeByEsc: false,
		autoHide: false,
		overlay: true,
		zIndex: 10200,
		className: 'bx-viewer-alert-popup'
	};

	var popupConfirm = BX.PopupWindowManager.create('bx-gedit-convert-confirm-copy', null, paramsWindow);
	popupConfirm.show();

	BX.ajax({
		'method': 'POST',
		'dataType': 'json',
		'url': shareForm.action,
		'data': {
			sessid: BX.bitrix_sessid(),
			inviteDescription: inviteDescription,
			canEdit: canEdit,
			shareToUsers: userIds
		},
		'onsuccess': function (data) {
			if (!data) {
				return;
			}
			BX.onCustomEvent('OnShareInviteToUsers', [data]);
			if (!data.status || data.status != 'success') {
				return;
			}

			var messageBox = BX.create('div', {
				props: {
					className: 'bx-viewer-alert'
				},
				children: [
					BX.create('span', {
						props: {
							className: 'bx-viewer-alert-icon'
						},
						children: [
							BX.create('img', {
								props: {
									src: '/bitrix/js/main/core/images/viewer-tick.png'
								}
							})
						]
					}),
					BX.create('span', {
						props: {
							className: 'bx-viewer-aligner'
						}
					}),
					BX.create('span', {
						props: {
							className: 'bx-viewer-alert-text'
						},
						text: '<?= GetMessageJS('WD_INVITE_MODAL_TAB_PROCESS_ACCESS_SUCCESS');  ?>'.replace('#FOLDERNAME#', wdGlobalSectionName)
					}),
					BX.create('div', {
						props: {
							className: 'bx-viewer-alert-footer'
						},
						children: []
					}),
					BX.create('a', {
						props: {
							className: 'bx-viewer-alert-close-icon',
							href: '#'
						},
						events: {
							click: function(e)
							{
								BX.PopupWindowManager.getCurrentPopup().destroy();
								BX.PreventDefault(e);
								return false;
							}
						}
					})
				]
			});
			BX.CViewer.unlockScroll();
			var paramsWindow = {
				content: messageBox,
				onPopupClose : function() { this.destroy() },
				closeByEsc: false,
				autoHide: false,
				zIndex: 10200,
				className: 'bx-viewer-alert-popup'
			};
			BX.PopupWindowManager.getCurrentPopup().destroy();
			var popupConfirm = BX.PopupWindowManager.create('bx-gedit-convert-confirm-copy', null, paramsWindow);
			popupConfirm.show();

			var idTimeout = setTimeout(function(){
				var w = BX.PopupWindowManager.getCurrentPopup();
				w.close();
				w.destroy();
			}, 3000);

			BX('bx-gedit-convert-confirm-copy').onmouseover = function(e){
				clearTimeout(idTimeout);
			};

			BX('bx-gedit-convert-confirm-copy').onmouseout = function(e){
				idTimeout = setTimeout(function(){
					var w = BX.PopupWindowManager.getCurrentPopup();
					w.close();
					w.destroy();
				}, 3000);
			};
		}
	});

	return window.event? BX.PreventDefault() : false;
}


window.BXWebdavPostSetLinkName = function(name)
{
	if (BX.SocNetLogDestination.getSelectedCount(name) <= 0)
		BX('bx-destination-tag').innerHTML = BX.message("BX_FPD_LINK_1");
	else
		BX('bx-destination-tag').innerHTML = BX.message("BX_FPD_LINK_2");
}

window.BXWebdavPostSelectCallback = function(item, type, search)
{
	if (type != 'users')
	{
		return;
	}
	var prefix = 'U';

	BX('feed-add-post-destination-item').appendChild(
		BX.create("span", { attrs : { 'data-id' : item.id }, props : { className : "feed-add-post-destination feed-add-post-destination-users" }, children: [
			BX.create("input", { attrs : { 'type' : 'hidden', 'name' : 'SPERM['+prefix+'][]', 'value' : item.id }}),
			BX.create("span", { props : { 'className' : "feed-add-post-destination-text" }, html : item.name}),
			BX.create("span", { props : { 'className' : "feed-add-post-del-but"}, events : {'click' : function(e){BX.SocNetLogDestination.deleteItem(item.id, type, BXSocNetLogDestinationFormName);BX.PreventDefault(e)}, 'mouseover' : function(){BX.addClass(this.parentNode, 'feed-add-post-destination-hover')}, 'mouseout' : function(){BX.removeClass(this.parentNode, 'feed-add-post-destination-hover')}}})
		]})
	);

	BX('feed-add-post-destination-input').value = '';
	BXWebdavPostSetLinkName(BXSocNetLogDestinationFormName);
}

// remove block
window.BXWebdavPostUnSelectCallback = function(item, type, search)
{
	var elements = BX.findChildren(BX('feed-add-post-destination-item'), {attribute: {'data-id': ''+item.id+''}}, true);
	if (elements != null)
	{
		for (var j = 0; j < elements.length; j++)
			BX.remove(elements[j]);
	}
	BX('feed-add-post-destination-input').value = '';
	BXWebdavPostSetLinkName(BXSocNetLogDestinationFormName);
}
window.BXWebdavPostOpenDialogCallback = function()
{
	BX.style(BX('feed-add-post-destination-input-box'), 'display', 'inline-block');
	BX.style(BX('bx-destination-tag'), 'display', 'none');
	BX.focus(BX('feed-add-post-destination-input'));
}

window.BXWebdavPostCloseDialogCallback = function()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-destination-input').value.length <= 0)
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
		BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
		BXWebdavPostDisableBackspace();
	}
}

window.BXWebdavPostCloseSearchCallback = function()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-destination-input').value.length > 0)
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
		BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
		BX('feed-add-post-destination-input').value = '';
		BXWebdavPostDisableBackspace();
	}

}
window.BXWebdavPostDisableBackspace = function(event)
{
	if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable != null)
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

	BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event){
		if (event.keyCode == 8)
		{
			BX.PreventDefault(event);
			return false;
		}
	});
	setTimeout(function(){
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
		BX.SocNetLogDestination.backspaceDisable = null;
	}, 5000);
}

window.BXWebdavPostSearchBefore = function(event)
{
	if (event.keyCode == 8 && BX('feed-add-post-destination-input').value.length <= 0)
	{
		BX.SocNetLogDestination.sendEvent = false;
		BX.SocNetLogDestination.deleteLastItem(BXSocNetLogDestinationFormName);
	}

	return true;
}
window.BXWebdavPostSearch = function(event)
{
	if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
		return false;

	if (event.keyCode == 13)
	{
		BX.SocNetLogDestination.selectFirstSearchItem(BXSocNetLogDestinationFormName);
		return BX.PreventDefault(event);
	}
	if (event.keyCode == 27)
	{
		BX('feed-add-post-destination-input').value = '';
		BX.style(BX('bx-destination-tag'), 'display', 'inline');
	}
	else
	{
		BX.SocNetLogDestination.search(BX('feed-add-post-destination-input').value, true, BXSocNetLogDestinationFormName);
	}

	if (!BX.SocNetLogDestination.isOpenDialog() && BX('feed-add-post-destination-input').value.length <= 0)
	{
		BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);
	}
	else
	{
		if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
			BX.SocNetLogDestination.closeDialog();
	}
	if (event.keyCode == 8)
	{
		BX.SocNetLogDestination.sendEvent = true;
	}
	return BX.PreventDefault(event);
}
BX.ready(function(){
	<? if(empty($arResult['CONNECTED_USERS_CAN_EDITED_COUNT']) && empty($arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT']) && empty($arResult['DISCONNECTED_USERS_COUNT'])){ ?>
	wdChangeTab(null, 'bx-webdav-invite-show-access-add');
	<? } ?>
	if(window.location.href.match(/[#]share/) && BX('wd-tab-access-add'))
	{
		BX.fireEvent(BX('wd-tab-access-add'), 'click');
	}

});
</script>

<script>
	BX.message({
		'BX_FPD_LINK_1':'<?=GetMessageJS("WD_INVITE_MODAL_DESTINATION_1")?>',
		'BX_FPD_LINK_2':'<?=GetMessageJS("WD_INVITE_MODAL_DESTINATION_2")?>'
		});

	var socBPDest = {
			department : <?=(empty($arResult["FEED_DESTINATION"]['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['DEPARTMENT']))?>,
			departmentRelation : {},
			relation : {}
		};

	<?if(empty($arResult["FEED_DESTINATION"]['DEPARTMENT_RELATION']))
	{
		?>
		for(var iid in socBPDest.department)
		{
			var p = socBPDest.department[iid]['parent'];
			if (!socBPDest.relation[p])
				socBPDest.relation[p] = [];
			socBPDest.relation[p][socBPDest.relation[p].length] = iid;
		}
		function makeDepartmentTree(id, relation)
		{
			var arRelations = {};
			if (relation[id])
			{
				for (var x in relation[id])
				{
					var relId = relation[id][x];
					var arItems = [];
					if (relation[relId] && relation[relId].length > 0)
						arItems = makeDepartmentTree(relId, relation);

					arRelations[relId] = {
						id: relId,
						type: 'category',
						items: arItems
					};
				}
			}

			return arRelations;
		}
		socBPDest.departmentRelation = makeDepartmentTree('DR0', socBPDest.relation);
		<?
	}
	else
	{
		?>socBPDest.departmentRelation = <?=CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['DEPARTMENT_RELATION'])?>;<?
	}
	?>
</script>

	<script type="text/javascript">
		var BXSocNetLogDestinationFormName = '<?=randString(6)?>';
		BXSocNetLogDestinationDisableBackspace = null;
		BX.SocNetLogDestination.init({
			'name' : BXSocNetLogDestinationFormName,
			'searchInput' : BX('feed-add-post-destination-input'),
			'extranetUser' :  false,
			'departmentSelectDisable' :  true,
			'bindMainPopup' : { 'node' : BX('feed-add-post-destination-container-post'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
			'bindSearchPopup' : { 'node' : BX('feed-add-post-destination-container-post'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
			'callback' : {
				'select' : BXWebdavPostSelectCallback,
				'unSelect' : BXWebdavPostUnSelectCallback,
				'openDialog' : BXWebdavPostOpenDialogCallback,
				'closeDialog' : BXWebdavPostCloseDialogCallback,
				'openSearch' : BXWebdavPostOpenDialogCallback,
				'closeSearch' : BXWebdavPostCloseSearchCallback
			},
			'items' : {
				'users' : <?=(empty($arResult["FEED_DESTINATION"]['USERS'])? '{}': CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['USERS']))?>,
				'groups' : {},
				'sonetgroups' : <?=(empty($arResult["FEED_DESTINATION"]['SONETGROUPS'])? '{}': CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['SONETGROUPS']))?>,
				'department' : socBPDest.department,
				'departmentRelation' : socBPDest.departmentRelation
			},
			'itemsLast' : {
				'users' : <?=(empty($arResult["FEED_DESTINATION"]['LAST']['USERS'])? '{}': CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['LAST']['USERS']))?>,
				'sonetgroups' : {},
				'department' : {},
				'groups' : {}
			},
			'itemsSelected' : {}
		});
		BX.bind(BX('feed-add-post-destination-input'), 'keyup', BXWebdavPostSearch);
		BX.bind(BX('feed-add-post-destination-input'), 'keydown', BXWebdavPostSearchBefore);
		BX.bind(BX('feed-add-post-destination-container-post'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);BX.PreventDefault(e); });
	</script>