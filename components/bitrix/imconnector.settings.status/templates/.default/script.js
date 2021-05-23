BX.namespace('BX.ImConnectorSettingStatus');

BX.ImConnectorSettingStatus.reload = function (params) {
	var loader = new BX.Loader({
		target: document.querySelector("#bx-imconnector-status-wrap")
	});

	loader.show();
	BX.ajax.runComponentAction(params.componentName, 'reload', {
		mode: 'class',
		signedParameters: params.signedParameters
	}).then(
		function(response) {
			var elem = BX.create('div');
			elem.innerHTML = response.data.html;
			BX('bx-imconnector-status-wrap').innerHTML = elem.querySelector('#bx-imconnector-status-wrap').innerHTML;
			elem.remove();
			loader.hide();
		},
		function(response) {
			loader.hide();
		}
	);
};
