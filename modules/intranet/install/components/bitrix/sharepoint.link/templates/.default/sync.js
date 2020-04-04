if (null == window.BXSPData)
{
	window.BXSPData = {
		loaded: 0,
		loaded_el: null,
		url: ''
	}
}

function BXSPSync(load, el, url, bFull)
{
	BX.ajax.Setup({cache: false}, true);
	
	window.BXSPData.loaded_el = window.BXSPData.loaded_el || el;
	window.BXSPData.loaded += parseInt(load);
	
	if (null != url) window.BXSPData.url = url
	else url = window.BXSPData.url;
	
	url += '&sessid=' + BX.bitrix_sessid();
	if (!!bFull) url += '&full=yes';
	
	BX.showWait(window.BXSPData.loaded_el, BX.message('JS_CORE_LOADING') + window.BXSPData.loaded + ' items loaded...');
	BX.ajax.get(url, function() {BX.closeWait(window.BXSPData.loaded_el)});
	
	return false;
}

function BXSPSyncAdditions(action)
{
	if (action != 'log') action = 'queue';
	BX.ajax.Setup({cache: false}, true);

	var url = window.BXSPData.url;
	
	url += '&sync_action=' + action;
	url += '&sessid=' + BX.bitrix_sessid();
	
	BX.showWait(window.BXSPData.loaded_el, BX.message('JS_CORE_LOADING') + action + ' is processing...');

	BX.ajax.get(url, function() {BX.closeWait(window.BXSPData.loaded_el)});
	
	return false;
}