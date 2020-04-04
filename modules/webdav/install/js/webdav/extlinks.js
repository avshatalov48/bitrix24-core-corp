function ShowExtLinkDialog(urlGetExtLink, urlGetDialogDiv)
{
	window.urlGetDialogDivIsLoaded = false;
	window.urlGetExtLink = urlGetExtLink;
	window.urlGetDialogDiv = urlGetDialogDiv;
	window.ExtLinksDialog = BX.PopupWindowManager.create("BXExtLinksDialog", null, {
			autoHide: false,
			overlay: true,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			draggable: {restrict:true},
			closeByEsc: true,
			titleBar: BX.create("span", { props: {className: "ext-link-title-div"}, html: BX.message('WD_EXT_LINKS_DIALOG_TITLE' )}),
			closeIcon : true,
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('WD_EXT_LINKS_DIALOG_GET'),
					id: "popup-window-button-link-get",
					className: "popup-window-button-link-get",
					events: { click : function()
					{
						GetExtLinkFromServer();
					}}
				})
			],
			content: GetExtLinkDialogWaitPicture(),
			events: {
				onAfterPopupShow: function()
				{
					GetExtLinkDialogDiv();
				}
			}
		});
	window.ExtLinksDialog.show();
}

function GetExtLinkDialogDiv()
{
	window.ExtLinksDialog.setContent(GetExtLinkDialogWaitPicture());
	BX.ajax.post(
		window.urlGetDialogDiv,
		{},
		BX.delegate(function(result)
		{
			if(result != 'error')
			{ 
				window.ExtLinksDialog.setContent(result);
				GetExtLinkAddEvents();
				window.ExtLinkDialogPassCB = null;
				document.getElementById("popup-window-button-link-get").style.display = "inline-block";
				window.urlGetDialogDivIsLoaded = true;
			}
			else
			{ 
				window.ExtLinksDialog.setContent(BX.message('WD_EXT_LINKS_DIALOG_ERROR'));
			}
		},
		this)
	);
}

function GetExtLinkDialogWaitPicture()
{
	return '<div style="width:600px;height:230px"><div class="ext-link-dialog-wait"></div></div>';
}

function GetExtLinkFromServer()
{
	if(window.urlGetDialogDivIsLoaded !== true)
	{
		return;
	}
	if(!CheckPassword(true))
	{
		return;
	}
	lt_t_cb = document.getElementById("ext-link-time-cb").checked;
	lt_t_n = document.getElementById("ext-link-time-inp").value;
	objSel = document.getElementById("ext-link-time-sel");
	lt_t_t =  objSel.options[objSel.selectedIndex].value;/*'day','hour','minute','notlimited'*/
	lt_p_cb = document.getElementById("ext-link-pass-cb").checked;
	lt_p_n = document.getElementById("ext-link-pass-inp1").value;
	arParam = {};
	if(lt_p_cb == true)
	{
			arParam.PASSWORD = lt_p_n;
	}
	if(lt_t_cb == true)
	{
		arParam.LIFETIME_NUMBER = lt_t_n;
		arParam.LIFETIME_TYPE = lt_t_t;
	}
	lt_c = document.getElementById("text-link-comments-textarea").value;
	if(String(lt_c).length)
	{
		arParam.DESCRIPTION = lt_c;
	}
	
	BX.ajax.post(
		window.urlGetExtLink,
		arParam,
		BX.delegate(function(result)
		{
			if(result != 'error')
			{
				document.getElementById("ext-link-url-div").className = "ext-link-section ext-link-link";
				document.getElementById("ext-link-white-block").style.display = "block";
				document.getElementById("popup-window-button-link-get").style.display = "none";
				document.getElementById("ext-link-res-url").value = result;
				ExtLinkDialogAmount(1);
			}
			else
			{ 
				document.getElementById("ext-link-res-url").value = BX.message('WD_EXT_LINKS_DIALOG_ERROR');
			}
		},
		this)
	);
}

function GetExtLinkAddEvents() 
{
			BX.bind(BX("ext-link-time-cb"), "click", function() {
			
			var block = BX("e2p");
				
				var easing = new BX.easing({
					duration : 300,
					start:{width: this.checked ? 0 : 160, opacity: this.checked ? 0 : 100},
					finish:{width: this.checked ? 160 : 0, opacity: this.checked ? 100 : 0},
					transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step : function(state){
						block.style.width = state.width + 'px';
						block.style.opacity = state.opacity / 100;
					},
					complete: function(){
						
					}
				});
				easing.animate(); 
			});
			
			BX.bind(BX("ext-link-pass-cb"), "click", function() {
			
			var block = BX("e3p");
				
				var easing = new BX.easing({
					duration : 400,
					start:{height: this.checked ? 0 : 68, opacity: this.checked ? 0 : 100},
					finish:{height: this.checked ? 68 : 0, opacity: this.checked ? 100 : 0},
					transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step : function(state){
						block.style.height = state.height + 'px';
						block.style.opacity = state.opacity / 100;
					},
					complete: function(){
						
					}
				});
				easing.animate(); 
			});
						
			//document.getElementById("ext-link-pass-inp1").onChange = function(x){ alert('+1+');};
			//document.getElementById("ext-link-pass-inp2").onChange = function(x){ alert('+2+');};
}

function ExtLinkDialogInitSpoiler (oHead)
{
    if (typeof oHead != "object" || !oHead)
        return false; 
    var oBody = oHead.nextSibling;

    while (oBody.nodeType != 1)
        oBody=oBody.nextSibling;

    oBody.style.display = (oBody.style.display == 'none' ? '' : 'none'); 
    oHead.className = (oBody.style.display == 'none' ? '' : 'ext-link-list-spoiler-head-open'); 
}

function ExtLinkDialogCloseGreenWindow(objT)
{
	objT.parentNode.parentNode.style.height = objT.parentNode.parentNode.offsetHeight + 'px';
	var block = objT.parentNode.parentNode;
	setTimeout(
		function(){
			BX.addClass(block, 'ext-link-dialog-wrap-animate')
		}, 
		20
	);		
}

function ExtLinkDialogDeleteLink(urlDelete, id, data)
{
	data = data || {};
	data.sessid = BX.bitrix_sessid()
	BX.ajax.post(
		urlDelete,
		data,
		BX.delegate(function(result)
		{
			if(result != 'error')
			{ 
				document.getElementById(id).className = "ext-link-hidden";
				ExtLinkDialogAmount(-1);
			}
			/*else
			{ 
				alert("error");
			}*/
		},
		this)
	);
	
}

function ExtLinkDialogDeleteAllLinks(urlDeleteAll, urlT, data)
{
	data = data || {};
	data.DeleteAllLinks = urlT;
	data.sessid = BX.bitrix_sessid();
	BX.ajax.post(
		urlDeleteAll,
		data,
		BX.delegate(function(result)
		{
			if(result != 'error')
			{ 
				document.getElementById("ext-link-list-div").innerHTML = "";
				ExtLinkDialogAmount(0);
			}
			/*else
			{ 
				alert("error");
			}*/
		},
		this)
	);
	
}

function ExtLinkDialogAddDescription()
{

	document.getElementById("ext-link-comments-link").style.display = "none";
	desc = document.getElementById("text-link-comments-textarea2").value;
	ta = document.getElementById("text-link-comments-textarea");
	ta.style.display = "block";
	ta.value = desc;
	document.getElementById("ext-link-comments-link-remove").style.display = "inline-block";
	
}

function ExtLinkDialogDeleteDescription()
{
	document.getElementById("ext-link-comments-link").style.display = "block";
	ta = document.getElementById("text-link-comments-textarea");
	ta.style.display = "none";
	ta.value = "";
	document.getElementById("ext-link-comments-link-remove").style.display = "none";
	
}

function ExtLinkDialogAmount(t0)
{
	linkListNum = document.getElementById("ext-link-spoiler-amount");
	if(linkListNum != null)
	{
		i = parseInt(linkListNum.innerHTML);
		t = parseInt(t0);
		if(t == 0)
		{
			i = 0;
		}
		else
		{
			i = i + t;
		}
		if(i == 0)
		{
			document.getElementById("ext-link-section").style.display = "none";
		}
		linkListNum.innerHTML = String(i);
	}
}

function CheckPassword(finalCheck)
{
	if(window.ExtLinkDialogPassCB == null)
	{
		window.ExtLinkDialogPassCB = document.getElementById("ext-link-pass-cb");
		window.ExtLinkDialogPassP1 = document.getElementById("ext-link-pass-inp1");
		window.ExtLinkDialogPassP2 = document.getElementById("ext-link-pass-inp2");
		window.ExtLinkDialogPassOk = document.getElementById("ext-link-pass-ico-ok");
		window.ExtLinkDialogPassWrong = document.getElementById("ext-link-pass-text-wrong");
		window.ExtLinkDialogPassEmpty = document.getElementById("ext-link-pass-text-empty");
	}

	window.ExtLinkDialogPassWrong.style.display = "none";
	window.ExtLinkDialogPassEmpty.style.display = "none";
		
	if(finalCheck ==true &&
		window.ExtLinkDialogPassCB.checked &&
		String(window.ExtLinkDialogPassP1.value).length == 0 &&
		String(window.ExtLinkDialogPassP2.value).length == 0
	)
	{
		window.ExtLinkDialogPassEmpty.style.display = "inline-block";
		window.ExtLinkDialogPassP2.className = "ext-link-pass-inp-error";
		return false;
	}
	
	if(!window.ExtLinkDialogPassCB.checked)
	{
		return true;
	}
	
	if(window.ExtLinkDialogPassP1.value == window.ExtLinkDialogPassP2.value && String(window.ExtLinkDialogPassP1.value).length > 0)
	{
		window.ExtLinkDialogPassOk.style.display = "inline-block";
		window.ExtLinkDialogPassP2.className = "ext-link-pass-inp";
		window.ExtLinkDialogPassWrong.style.display = "none";
		return true;
	}
	if(String(window.ExtLinkDialogPassP1.value).length > 0 && String(window.ExtLinkDialogPassP2.value).length > 0)
	{
		window.ExtLinkDialogPassWrong.style.display = "inline-block";
		window.ExtLinkDialogPassP2.className = "ext-link-pass-inp-error";
	}
	window.ExtLinkDialogPassOk.style.display = "none";
	return false;
}

var tmr = false;
function onKeyPress()
{
	if (tmr !== false)
		clearTimeout(tmr);
	
	tmr = window.setTimeout(
		function()
		{
			CheckPassword()
		},
		500
	);
}