<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/** @var $APPLICATION CAllMain */
/** @var $arResult array */
?>
<script type="text/javascript">
	BX.loadCSS('/bitrix/components/bitrix/webdav.invite/templates/.default/style.css');
</script>
<div class="bx-webdav-invite-access bx-webdav-invite-access-wide">
	<div class="bx-webdav-invite-access-title"><?= GetMessage('WD_INVITE_MODAL_TAB_GROUP_CONNECTED_TITLE'); ?></div>
	<div id="bx-webdav-invite-show-access-list" class="bx-webdav-invite-tabs-cont">
		<span class="bx-webdav-invite-users bx-webdav-invite-owner">
			<span class="bx-webdav-invite-us-avatar"><?
				if(!empty($arResult['OWNER']['PHOTO_SRC']))
				{
					?><img height="21" width="21" src="<?= $arResult['OWNER']['PHOTO_SRC'] ?>" alt="<?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?>"/><?
				}
			?></span>
			<a class="bx-webdav-invite-us-name" href="<?= $arResult['OWNER']['HREF']; ?>" target="_blank" title="<?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?>"><?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?></a>
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
	</div>
	<div class="bx-webdav-invite-footer">
		<a href="javascript:void(0)" onclick="wdOpenConfirmUnlink();" class="webform-button"><span class="webform-button-left"></span><span class="webform-button-text"><?= GetMessage('WD_INVITE_MODAL_BTN_DIE_SELF_ACCESS_SIMPLE'); ?></span><span class="webform-button-right"></span></a>
	</div>
</div>

<script type="text/javascript">
function wdOpenConfirmUnlink()
{
	var createDialog = BX.create('div', {
		props: {
			className: 'bx-viewer-confirm'
		},
		children: [
			BX.create('div', {
				props: {
					className: 'bx-viewer-confirm-title'
				},
				text: "<?= GetMessageJS('WD_INVITE_MODAL_TITLE_GROUP_DIE_SELF_ACCESS_SIMPLE');  ?>",
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
						text: "<?= GetMessageJS('WD_INVITE_MODAL_TITLE_GROUP_DIE_SELF_ACCESS_SIMPLE_DESCR');  ?>"
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
				events : { click : function(e){
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
			text: '<?= GetMessageJS('WD_INVITE_MODAL_TAB_GROUP_PROCESS_DIE_ACCESS');  ?>'
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
	'url': "<?= $arResult['GROUP_DISK']['DISCONNECT_URL']; ?>",
	'data': {
		sessid: BX.bitrix_sessid()
	},
	'onsuccess': function (data) {
		if (!data) {
			return;
		}
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
					text: '<?= GetMessageJS('WD_INVITE_MODAL_TAB_GROUP_PROCESS_DIE_ACCESS_SUCCESS');  ?>'
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

	var popupConfirm = BX.PopupWindowManager.create('bx-link-unlink-confirm', null, paramsWindow);
	popupConfirm.show();
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

function wdCloseCurrentPopup()
{
	if(!BX.PopupWindowManager)
	{
		return;
	}
	BX.PopupWindowManager.getCurrentPopup().close();
}
</script>