;(function(window){

	window.checkViberFirst = function() {
		document.getElementById('webform-small-button-have-bot').disabled = this.value ? false : "disabled";
	};

	BX.ready(function(){
		BX.bindDelegate(
			document.body,
			'click',
			{className: 'imconnector-field-box-entity-icon-copy-to-clipboard'},
			copyToClipboard
		);
		BX.bindDelegate(
			document.body,
			'keyup',
			{props:{id: 'imconnector-viber-have-bot'}},
			checkViberFirst
		);
		BX.bindDelegate(
			document.body,
			'mouseout',
			{props:{id: 'imconnector-viber-have-bot'}},
			checkViberFirst
		);
		BX.bindDelegate(
			document.body,
			'click',
			{className: 'show-preloader-button'},
			addPreloader
		);
		BX.bindDelegate(
			document.body,
			'submit',
			{tag: 'form'},
			addPreloader
		);
		BX.bindDelegate(
			document.body,
			'click',
			{props: {id:'imconnector-viber-link-help'}},
			() => {
				top.BX.Helper.show('redirect=detail&code=7417097');
				return false;
			}
		);
	});
})(window);