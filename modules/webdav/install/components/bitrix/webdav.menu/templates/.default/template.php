<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav.invite/templates/.default/style.css');
	$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/webdav/templates/.default/script.js");
endif;
CAjax::Init(); 
CUtil::InitJSCore(array(/*'ajax', */'window'));
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');
/********************************************************************
				Input params
********************************************************************/
$arParams["USE_SEARCH"] = ($arParams["USE_SEARCH"] == "Y" && IsModuleInstalled("search") ? "Y" : "N");
$arParams["SHOW_WEBDAV"] = ($arParams["SHOW_WEBDAV"] == "N" ? "N" : "Y");
$res = strtolower($_SERVER["HTTP_USER_AGENT"]); 
$bIsIE = (strpos($res, "opera") === false && strpos($res, "msie") !== false); 
$bIsFF = (strpos($res, "firefox") !== false); 
$ob = $arParams['OBJECT'];
$bInTrash = ($ob->meta_state == 'TRASH');
$pathToUser = !empty($arParams["USER_VIEW_URL"])? rtrim($arParams["USER_VIEW_URL"], '/') . '/files/lib/' : '/company/personal/user/#user_id#/files/lib/';
$pathToUser = \CComponentEngine::makePathFromTemplate(
	$pathToUser,
	array(
		'user_id' => $GLOBALS['USER']->getId(),
		'USER_ID' => $GLOBALS['USER']->getId()
));
/********************************************************************
				/Input params
********************************************************************/
$bBitrix24Tpl = function_exists('BX24ShowPanel');
$arButtons = array();
$arSubButtons = $arFixedSubButtons = array();
if (strpos($arParams["PAGE_NAME"], "WEBDAV_BIZPROC_WORKFLOW") !== false)
{
	if ($arParams["USE_BIZPROC"] == "Y" && $ob->CheckRight($arParams["PERMISSION"], 'element_edit') >= "W" && IsModuleInstalled("bizprocdesigner"))
	{
		$arButtons[] = array(
			"TEXT" => GetMessage("BPATT_HELP1"),
			"TITLE" => GetMessage("BPATT_HELP1_TEXT"),
			"LINK" => $arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"].(strpos($arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"], "?") === false ? "?" : "&").
				"init=statemachine",
			"ICON" => "btn-list"); 
		$arButtons[] = array(
			"TEXT" => GetMessage("BPATT_HELP2"),
			"TITLE" => GetMessage("BPATT_HELP2_TEXT"),
			"LINK" => $arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"].(strpos($arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"], "?") === false ? "?" : ""),
			"ICON" => "btn-list"); 
	}
}
elseif ($arParams["PAGE_NAME"] == "SECTIONS")
{
	$cannotReadSection = $ob->e_rights && (!$ob->GetPermission('SECTION', $ob->arParams['item_id'], 'section_read'));
	$cannotEditSection = $ob->e_rights && (!$ob->GetPermission('SECTION', $ob->arParams['item_id'], 'section_edit'));

	if (
		$ob->CheckRight($arParams["PERMISSION"], 'section_element_bind') >= "U"
		&& !(
			$arParams["OBJECT"]->workflow == 'workflow'
			&& !$arParams["OBJECT"]->permission_wf_edit)
		)
	{
		if (!$bInTrash)
		{
			if ($arParams["SHOW_CREATE_ELEMENT"] != "N" && !$cannotEditSection)
			{
				if ($arParams["SHOW_WEBDAV"] == "Y" && $bIsIE)
				{
					$arButtons[] = array(
						"TEXT" => GetMessage("WD_ELEMENT_ADD"),
						"TITLE" => GetMessage("WD_ELEMENT_ADD_ALT"),
						"LINK" => "javascript:WDAddElement('".CUtil::JSEscape($arResult["URL"]["ELEMENT"]["ADD"])."');",
						"ICON" => "btn-new element-add"); 
				}

				$urlParams = array("use_light_view" => "Y");
				if ($arResult['BP_PARAM_REQUIRED'] == 'Y')
					$urlParams['bp_param_required'] = 'Y';

				$arButtons[] = array(
					"TEXT" => \Bitrix\Main\Localization\Loc::getMessage('WD_UPLOAD'),
					"TITLE" => ($arParams["SECTION_ID"] > 0 ? GetMessage("WD_UPLOAD_ALT") : GetMessage("WD_UPLOAD_ROOT_ALT")),
					"LINK" => "javascript:".$APPLICATION->GetPopupLink(
						Array(
							"URL"=> WDAddPageParams(
								$arResult["URL"]["ELEMENT"]["UPLOAD"],
								$urlParams,
								false),
							"PARAMS" => Array("width" => 600, "height" => 200)
						)
					),
					"ICON" => "btn-new element-upload"
				);
			}

			if ($ob->CheckRight($arParams["PERMISSION"],"section_section_bind") >= "W" && $arParams["CHECK_CREATOR"] != "Y")
			{
				$arButtons[] = array(
					"TEXT" => GetMessage("WD_SECTION_ADD"),
					"TITLE" => GetMessage("WD_SECTION_ADD_ALT"),
					"LINK" => "javascript:".$APPLICATION->GetPopupLink(
						Array(
							"URL"=> WDAddPageParams(
								WDAddPageParams($arResult["URL"]["SECTION"]["~POPUP_ADD"], array('bxpublic'=>'Y')), 
								array("use_light_view" => "Y"), 
								false),
							"PARAMS" => Array("width" => 450, "height" => (($arParams["OBJECT"]->Type == "folder")?160:60), "content_url" => $arResult["URL"]["SECTION"]["~POPUP_ADD"])
						)
					), 
					"ICON" => "btn-new section-add"
				); 
			}

			if ($ob->CheckRight($arParams["PERMISSION"], "section_edit") >= "W")
			{
				$arButtons[] = array(
					"TEXT" => GetMessage("WD_TRASH"),
					"TITLE" => GetMessage("WD_TRASH"),
					"LINK" => $arParams["OBJECT"]->base_url . '/'. $arParams["OBJECT"]->meta_names["TRASH"]["alias"],
					"ICON" => "btn-new ". ($arParams["OBJECT"]->IsTrashEmpty() ? "trash-go": "trash-go-full") ); 
			}
		} else {
			if ($ob->CheckRight($arParams["PERMISSION"], "iblock_edit") >= "X")
			{
				$url = WDAddPageParams(str_replace("use_light_view=Y","",$arResult["URL"]["SECTION"]["EMPTY_TRASH"]), array("sessid" => bitrix_sessid(), "edit_section"=>"Y", 'get_count_elements' => 'Y'));
				$arButtons[] = array(
					"TEXT" => GetMessage("WD_CLEAN_TRASH"),
					"TITLE" => GetMessage("WD_CLEAN_TRASH"),
					"LINK" => "javascript:WDConfirmTrash('".CUtil::JSEscape(GetMessage("WD_CLEAN_TRASH"))."', '".CUtil::JSEscape(GetMessage("WD_CONFIRM_CLEAN_TRASH"))."', function() {WDDropTrashFlow('".$url."');} );",
					"ICON" => "btn-new trash-clean");
			}
		}
	}
	if ($arParams["SHOW_WEBDAV"] == "Y" && !$bInTrash /*&& $bIsIE*/)
	{
		if (!$cannotReadSection)
		{
			$arBtnMount = array(
				"TEXT" => GetMessage("WD_MAPING"),
				"TITLE" => GetMessage("WD_MAPING_ALT"),
				"LINK" => "javascript:".$APPLICATION->GetPopupLink(
					Array(
						"URL"=> WDAddPageParams(
							$arResult["URL"]["CONNECTOR"], 
							array("use_light_view" => "Y"), 
							false),
						//"PARAMS" => Array("width" => 450, "height" => 200)
					)
				),
				"ICON" => "btn-list mapping"
			); 

			$arFixedSubButtons[] = $arBtnMount;
		}

		if(!empty($arResult['GROUP_DISK']))
		{
			if(!empty($arResult['GROUP_DISK']['CONNECTED']))
			{
				$arButtons[] = array(
					"TEXT" => GetMessage("WD_MENU_GROUP_DISK_DISCONNECTED"),
					"TITLE" => GetMessage("WD_MENU_GROUP_DISK_DISCONNECTED"),
					'LINK_PARAM' => 'id="wd-connect-group-disk"',
					"LINK" => $arResult['GROUP_DISK']['CONNECT_URL'],
					"ICON" => "btn-list disk-group"
				);
			}
			else
			{
				$arButtons[] = array(
					"TEXT" => GetMessage("WD_MENU_GROUP_DISK_CONNECTED"),
					"TITLE" => GetMessage("WD_MENU_GROUP_DISK_CONNECTED"),
					'LINK_PARAM' => 'id="wd-disconnect-group-disk"',
					"LINK" => $arResult['GROUP_DISK']['DETAIL_URL'],
					"ICON" => "btn-list disk-group-on"
				);
			}
		}
	}

	$arBtnHelp = array(
		"TEXT" => GetMessage("WD_HELP"),
		"TITLE" => GetMessage("WD_HELP_ALT"),
		"LINK" => $arResult["URL"]["HELP"],
		"ICON" => "btn-list help"
	);
	if ($bBitrix24Tpl)
		$arSubButtons[] = $arBtnHelp;
	else
		$arButtons[] = $arBtnHelp;


	if ($bIsFF && $arParams["SHOW_WEBDAV"] == "Y") {
		$arSubButtons[] = array(
			"TEXT" => GetMessage("WD_MENU_FF_EXTENSION_TEXT"),
			"TITLE" => GetMessage("WD_MENU_FF_EXTENSION_TITLE"),
			"LINK" => "javascript:FFWDExtDialog()",
			"ICON" => "btn-list ffext");
	}

	if ($arParams["USE_BIZPROC"] == "Y" && $ob->CheckRight($arParams["PERMISSION"], "iblock_edit") > "U" && $arParams["CHECK_CREATOR"] != "Y")
	{
		$arSubButtons[] = array(
			"TEXT" => GetMessage("WD_BP"),
			"TITLE" => GetMessage("WD_BP"),
			"LINK" => $arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_ADMIN"],
			"ICON" => "btn-list bizproc"); 
	}
	if ($this->__component->__parent)
	{
		// if no filter (and go-back) button exists
		if (!(isset($this->__component->__parent->arResult["arButtons"]) && 
			isset($this->__component->__parent->arResult["arButtons"][0]['PREORDER']) &&
			$this->__component->__parent->arResult["arButtons"])) 
		{
			$link = false;
			$ob = $arParams['OBJECT'];
			if ($ob->arParams['not_found'] == false && $ob->_path != '/')
				$link = $ob->base_url.$ob->_get_path($ob->arParams['parent_id']);
			if ($link)
			{
				array_unshift($arButtons, array(
					"TEXT" => GetMessage("WD_GO_BACK"),
					"TITLE" => GetMessage("WD_GO_BACK_ALT"),
					"LINK" => $link,
					"ICON" => "btn-list go-back"));
			}
		}
	}
}
else
{
	if ($this->__component->__parent)
	{
		$bElmInTrash = false;
		if (
			isset($arParams['ELEMENT_ID']) 
			&& intval($arParams['ELEMENT_ID']) > 0
		)
		{
			$oElement = $ob->GetObject(array('element_id' => intval($arParams['ELEMENT_ID'])));
			$bElmInTrash = $ob->InTrash($oElement);
		}

		$link = false;
		$arChain = $GLOBALS['APPLICATION']->arAdditionalChain;

		if ($bElmInTrash)
		{
			$link = $ob->base_url . '/' . $ob->MetaNamesReverse('.Trash', 'name', 'alias');
		}
		elseif (sizeof($arChain) > 1)
		{
			$lastChain = array_pop($arChain);
			if (!empty($lastChain)) $lastChain = array_pop($arChain);
			if (!empty($lastChain)) $link = $lastChain['LINK'];
		} 
		elseif (sizeof($arChain) > 0)
		{
			$link = $ob->base_url;
		}

		if ($link)
		{
			if( strrpos($link, '/') != (strlen($link) - 1) )
			{
				$link .= '/';
			}
			array_unshift($arButtons, array(
				"TEXT" => GetMessage("WD_GO_BACK"),
				"TITLE" => GetMessage("WD_GO_BACK_ALT"),
				"LINK" => $link,
				"ICON" => "btn-list go-back"));
		}
	}
}

if (empty($arButtons))
	$arButtons = array();

if ($this->__component->__parent && is_array($this->__component->__parent->arResult["arButtons"]))
{
	foreach ($this->__component->__parent->arResult["arButtons"] as $arButton)
	{
		if (isset($arButton["PREORDER"]) && $arButton["PREORDER"])
			array_unshift($arButtons, $arButton);
		else
			$arButtons[] = $arButton;
	}
}

foreach($arButtons as $buttonID => $arButton)
{
	if (strpos($arButton['ICON'], 'settings') !== false)
	{
		$arSubButtons[] = $arButton;
		unset($arButtons[$buttonID]);
	}
}

if (
	(sizeof($arButtons) < 4)
	&& (sizeof($arSubButtons) > 0)
)
{
	for ($i=sizeof($arButtons); $i<4; $i++)
	{
		if (sizeof($arSubButtons) > 0)
			$arButtons[] = array_shift($arSubButtons);
	}
}

if (sizeof($arSubButtons) > 0)
{
	$arButtons[] = array("NEWBAR" => true);
	$arButtons = array_merge($arButtons, $arSubButtons, $arFixedSubButtons);
}

if ($arParams["SHOW_WEBDAV"] == "Y"):
?>
<script>

BX.ready(function(){
	BX.bind(BX('wd-connect-group-disk'), 'click', function(event){
		var bindElement = event.srcElement || event.target;

		showWebdavNewConnectGroupDiskPopup(BX('wd-connect-group-disk').getAttribute('href'), bindElement);
		return BX.PreventDefault(event);
	});
	BX.bind(BX('wd-disconnect-group-disk'), 'click', function(event){
		var bindElement = event.srcElement || event.target;

		showWebdavConnectGroupDiskPopup(BX('wd-disconnect-group-disk').getAttribute('href'), bindElement);
		return BX.PreventDefault(event);
	});
});


function showWebdavSharedSectionDiskPopup(dataUrl, id, sectionDisconnectUrl, sectionName)
{
	if(!dataUrl)
	{
		return false;
	}
	//shame
	wdGlobalSectionDisconnectUrl = sectionDisconnectUrl || false;
	wdGlobalSectionName = sectionName || '';
	wdGlobalSectionId = id || false;

	var popup = BX.PopupWindowManager.create(
		'bx_webdav_connect_shared_section_disk_popup',
		null,
		{
			overlay: {
				opacity: 0.01
			},
			closeIcon : true,
			offsetTop: 5,
			autoHide: true,
			content: BX.create('div', {
				children: [
					BX.create('div', {
							style: {
								display: 'table',
								width: '451px',
								height: '225px'
							},
							children: [
								BX.create('div', {
									style: {
										display: 'table-cell',
										verticalAlign: 'middle',
										textAlign: 'center'
									},
									children: [
										BX.create('div', {
											props: {
												className: 'bx-viewer-wrap-loading-modal'
											}
										}),
										BX.create('span', {
											text: ''
										})
									]
								})
							]
						}
					)
				]
			}),
			closeByEsc: true,
			events : {
				'onPopupClose': function()
				{
				}
			}
		}
	);
	popup.show();

	BX.ajax({
		'method': 'POST',
		'dataType': 'json',
		'url': dataUrl,
		'data': {
			sessid: BX.bitrix_sessid()
		},
		'onsuccess': function (data) {
			if (!data) {
				return;
			}
			if (data.status && data.status == 'success') {

				BX.onCustomEvent('OnConnectSharedSection', [data]);
				var actionTextDisk;
				var actionBtnDisk;

				if(data.statusDisk == "<?= CWebDavTools::DESKTOP_DISK_STATUS_NOT_INSTALLED ?>")
				{
					actionBtnDisk = function(){
						WDDownloadDesktop();
						return;
					}
					actionTextDisk = "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_BTN_INSTALL_DESKTOP');  ?>";
				}
				else if(BXIM && !BXIM.desktop.ready() && BXIM.desktopStatus && BXIM.desktopVersion >= 18)
				{
					if(data.statusDisk == "<?= CWebDavTools::DESKTOP_DISK_STATUS_NOT_ENABLED ?>")
					{
						actionBtnDisk = function(){
							document.location.href = 'bx://openDiskTab';
							return;
						}
						actionTextDisk = "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_BTN_ENABLE_DESKTOP');  ?>";
					}
					else if(data.statusDisk == "<?= CWebDavTools::DESKTOP_DISK_STATUS_ONLINE ?>")
					{
						actionBtnDisk = function(){
							location.href = 'bx://openFolder/path/' + encodeURIComponent(data.sectionName);
							return;
						}
						actionTextDisk = "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_BTN_OPEN_FOLDER_DESKTOP');  ?>";
					}
				}
				else
				{
					actionBtnDisk = function(){
						location.href = '<?= $pathToUser ?>' + '?result=sec' + data.sectionId;
						return;
					}
					actionTextDisk = "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_BTN_OPEN_FOLDER_DESKTOP');  ?>";
				}

				var createDialog = BX.create('div', {
					props: {
						className: 'bx-viewer-confirm'
					},
					children: [
						BX.create('div', {
							props: {
								className: 'bx-viewer-confirm-title'
							},
							text: "<?= GetMessageJS('WD_SHARE_CONNECT_SHARED_SECTION_TITLE');  ?>",
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
									text: "<?= GetMessageJS('WD_SHARE_CONNECT_SHARED_SECTION_DESCR');  ?>".replace('#NAME#', data.sectionName)
								})
							]
						})
					]
				});

				BX.PopupWindowManager.create(
					'bx_webdav_connect_group_disk_popup_status',
					null,
					{
						overlay: {
							opacity: 0.01
						},
						closeIcon : true,
						offsetTop: 5,
						autoHide: true,
						content: createDialog,
						buttons: [
							new BX.PopupWindowButton({
								text : actionTextDisk,
								className : "popup-window-button-accept",
								events : { click : function (e){
										BX.PopupWindowManager.getCurrentPopup().destroy();
										BX.PreventDefault(e);

										if(actionBtnDisk)
										{
											actionBtnDisk();
										}

										return false;
									}
								}
							})
						],
						closeByEsc: true
					}
				).show();
			}
		}
	});
}
function showWebdavNewConnectGroupDiskPopup(dataUrl, bindElement)
{
	if(!dataUrl)
	{
		return false;
	}
	bindElement = bindElement || null;
	var popup = BX.PopupWindowManager.create(
		'bx_webdav_connect_group_disk_popup',
		null,
		{
			overlay: {
				opacity: 0.01
			},
			closeIcon : true,
			offsetTop: 5,
			autoHide: true,
			content: BX.create('div', {
				children: [
					BX.create('div', {
							style: {
								display: 'table',
								width: '451px',
								height: '225px'
							},
							children: [
								BX.create('div', {
									style: {
										display: 'table-cell',
										verticalAlign: 'middle',
										textAlign: 'center'
									},
									children: [
										BX.create('div', {
											props: {
												className: 'bx-viewer-wrap-loading-modal'
											}
										}),
										BX.create('span', {
											text: ''
										})
									]
								})
							]
						}
					)
				]
			}),
			closeByEsc: true,
			events : {
				'onPopupClose': function()
				{
				}
			}
		}
	);
	popup.show();

	BX.ajax({
		'method': 'POST',
		'dataType': 'json',
		'url': dataUrl,
		'data': {
			sessid: BX.bitrix_sessid()
		},
		'onsuccess': function (data) {
			if (!data) {
				return;
			}
			if (data.status && data.status == 'success') {

				var actionTextDisk;
				var actionBtnDisk;

				if(data.statusDisk == "<?= CWebDavTools::DESKTOP_DISK_STATUS_NOT_INSTALLED ?>")
				{
					actionBtnDisk = function(){
						WDDownloadDesktop();
						return;
					}
					actionTextDisk = "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_BTN_INSTALL_DESKTOP');  ?>";
				}
				else if(BXIM && !BXIM.desktop.ready() && BXIM.desktopStatus && BXIM.desktopVersion >= 18)
				{
					if(data.statusDisk == "<?= CWebDavTools::DESKTOP_DISK_STATUS_NOT_ENABLED ?>")
					{
						actionBtnDisk = function(){
							document.location.href = 'bx://openDiskTab';
							return;
						}
						actionTextDisk = "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_BTN_ENABLE_DESKTOP');  ?>";
					}
					else if(data.statusDisk == "<?= CWebDavTools::DESKTOP_DISK_STATUS_ONLINE ?>")
					{
						actionBtnDisk = function(){
							location.href = 'bx://openFolder/path/' + encodeURIComponent(data.sectionName);
							return;
						}
						actionTextDisk = "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_BTN_OPEN_FOLDER_DESKTOP');  ?>";
					}
				}
				else
				{
					actionBtnDisk = function(){
						location.href = '<?= $pathToUser ?>' + '?result=sec' + data.sectionId;
						return;
					}
					actionTextDisk = "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_BTN_OPEN_FOLDER_DESKTOP');  ?>";
				}

				var createDialog = BX.create('div', {
					props: {
						className: 'bx-viewer-confirm'
					},
					children: [
						BX.create('div', {
							props: {
								className: 'bx-viewer-confirm-title'
							},
							text: "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_TITLE');  ?>",
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
									text: "<?= GetMessageJS('WD_SHARE_CONNECT_GROUP_DESCR');  ?>"
								})
							]
						})
					]
				});

				BX.PopupWindowManager.create(
					'bx_webdav_connect_group_disk_popup_status',
					null,
					{
						overlay: {
							opacity: 0.01
						},
						closeIcon : true,
						offsetTop: 5,
						autoHide: true,
						content: createDialog,
						buttons: [
							new BX.PopupWindowButton({
								text : actionTextDisk,
								className : "popup-window-button-accept",
								events : { click : function (e){
										BX.PopupWindowManager.getCurrentPopup().destroy();
										BX.PreventDefault(e);

										if(actionBtnDisk)
										{
											actionBtnDisk();
										}

										return false;
									}
								}
							})
						],
						closeByEsc: true
					}
				).show();
			}
		}
	});
}

function showWebdavConnectGroupDiskPopup(dataUrl, bindElement)
{
	if(!dataUrl)
	{
		return false;
	}
	bindElement = bindElement || null;
	var popup = BX.PopupWindowManager.create(
		'bx_webdav_connect_group_disk_popup',
		bindElement,
		{
			angle : {
				position: 'top',
				offset: 45
			},
			overlay: {
				opacity: 0.01
			},
			closeIcon : true,
			offsetTop: 5,
			autoHide: true,
			content: BX.create('div', {
				children: [
					BX.create('div', {
						style: {
							display: 'table',
							width: '503px',
							height: '225px'
						},
						children: [
							BX.create('div', {
								style: {
									display: 'table-cell',
									verticalAlign: 'middle',
									textAlign: 'center'
								},
								children: [
									BX.create('div', {
										props: {
											className: 'bx-viewer-wrap-loading-modal'
										}
									}),
									BX.create('span', {
										text: ''
									})
								]
							})
						]
					})
				]
			}),
			closeByEsc: true,
			events : {
				'onPopupClose': function()
				{
				}
			}
		}
	);
	popup.show();

	BX.ajax({
		'method': 'POST',
		'dataType': 'html',
		'url': dataUrl,
		'data': {
			sessid: BX.bitrix_sessid()
		},
		'onsuccess': function (data) {
			popup.setContent(BX.create('DIV', {html: data}));
			popup.adjustPosition();
		}
	});
}


if (document.attachEvent && navigator.userAgent.toLowerCase().indexOf('opera') == -1)
{
	if (document.getElementById('wd_create_in_ie'))
		document.getElementById('wd_create_in_ie').style.display = '';
	if (document.getElementById('wd_create_in_ie_separator'))
		document.getElementById('wd_create_in_ie_separator').style.display = '';
	if (document.getElementById('wd_map_in_ie'))
		document.getElementById('wd_map_in_ie').style.display = '';
	if (document.getElementById('wd_map_in_ie_separator'))
		document.getElementById('wd_map_in_ie_separator').style.display = '';
}
function WDMappingDrive(path)
{
	if (!jsUtils.IsIE())
	{
		return false;
	}
	if (!path || path.length <= 0)
	{
		alert('<?=GetMessageJS("WD_EMPTY_PATH")?>');
		return false;
	}

	var sizer = false;
	var text = '';
	var src = "";
	sizer = window.open("",'',"height=600,width=800,top=0,left=0");

	text = '<HTML><BODY>' +
			'<SPAN ID="oWebFolder" style="BEHAVIOR:url(#default#httpFolder)">' +
				'<?=CUtil::JSEscape(str_replace("#BASE_URL#", str_replace(":443", "", $arParams["BASE_URL"]), GetMessage("WD_HELP_TEXT")))?>' +
			'</SPAN>' +
		'<script>' +
			'var res = oWebFolder.navigate(\'' + path + '\');' +
		'<' + '/' + 'script' + '>' +
		'</BODY></HTML>';
	sizer.document.write(text);
}
function FFWDExtDialog()
{
	(new BXFFDocLink()).ShowDialog();
	try
	{
		return BX.PreventDefault();
	} catch (err) {}
}
</script>
<?
endif;
?>
<script>
BX.message({
	'stop_drop_trash': "<?= GetMessageJS('WD_STOP_DROP_TRASH') ?>",
	'drop_trash_count_elements': "<?= GetMessageJS('WD_DROP_TRASH_COUNT_ELEMENTS') ?>"
});

if (typeof oText != "object")
	var oText = {};
oText['error_create_1'] = '<?=CUtil::JSEscape(GetMessage("WD_ERROR_1"))?>';
oText['error_create_2'] = '<?=CUtil::JSEscape(GetMessage("WD_ERROR_2"))?>';
oText['message01'] = '<?=CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRM"))?>';
oText['delete_title'] = '<?=CUtil::JSEscape(GetMessage("WD_DELETE_TITLE"))?>';
oText['yes'] = '<?=CUtil::JSEscape(GetMessage("WD_Y"))?>';
oText['no'] = '<?=CUtil::JSEscape(GetMessage("WD_N"))?>';
oText['ff_extension_update'] = '<?=CUtil::JSEscape(GetMessage("WD_FF_EXTENSION_UPDATE", array("#NAME#" => GetMessage("WD_FF_EXTENSION_NAME") )))?>';
oText['ff_extension_install'] = '<?=CUtil::JSEscape(GetMessage("WD_FF_EXTENSION_INSTALL", array("#NAME#" => GetMessage("WD_FF_EXTENSION_NAME") )))?>';
oText['ff_extension_title'] = '<?=CUtil::JSEscape(GetMessage("WD_FF_EXTENSION_TITLE"))?>';
oText['ff_extension_help'] = '<?=CUtil::JSEscape(GetMessage("WD_FF_EXTENSION_HELP"))?>';
oText['ff_extension_disable'] = '<?=CUtil::JSEscape(GetMessage("WD_FF_EXTENSION_DISABLE"))?>';
oText['wd_install'] = '<?=CUtil::JSEscape(GetMessage("WD_BTN_INSTALL"))?>';
oText['wd_update'] = '<?=CUtil::JSEscape(GetMessage("WD_BTN_UPDATE"))?>';
oText['wd_open'] = '<?=CUtil::JSEscape(GetMessage("WD_BTN_OPEN"))?>';
oText['wd_edit_in'] = '<?=CUtil::JSEscape(GetMessage("WD_MENU_EDIT_IN"))?>';
oText['wd_edit_in_other'] = '<?=CUtil::JSEscape(GetMessage("WD_MENU_EDIT_IN_OTHER"))?>';
oText['wd_install_cancel'] = '<?=CUtil::JSEscape(GetMessage("WD_BTN_INSTALL_CANCEL"))?>';
<? if ($bIsFF) {
	$arUserOptions = CUserOptions::GetOption('webdav', 'suggest', array('ff_extension' => true));
	if ($arUserOptions['ff_extension'] === true) {
?>
window.suggest_ff_extension = true;
<? }} ?>
</script><?

if($ob instanceof CWebDavIblock && $ob->Type === "iblock" && CWebDavIblock::needBlockByDisk())
{
	ShowError(GetMessage('WD_BLOCKED_BY_DISK'));
}

if (!empty($arButtons))
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.toolbar",
		"",
		array(
			"BUTTONS" => $arButtons
		),
		($this->__component->__parent ? $this->__component->__parent : $component)
	);
}
?>
