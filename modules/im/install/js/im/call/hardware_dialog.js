;(function ()
{
	BX.namespace('BX.Call');

	if (BX.Call.HardwareDialog)
		return;

	BX.Call.HardwareDialog = function(params)
	{
		this.bindNode = params.bindNode;
		this.offsetTop = params.offsetTop;
		this.offsetLeft = params.offsetLeft;

		this.popup = null;

		this.callbacks = {
			onDestroy: BX.type.isFunction(params.onDestroy) ? params.onDestroy : BX.DoNothing
		}
	};

	BX.Call.HardwareDialog.prototype.createPopup = function()
	{
		this.popup = new BX.PopupWindow('bx-messenger-call-access', this.bindNode, {
			targetContainer: document.body,
			lightShadow: true,
			zIndex: 200,
			offsetTop: this.offsetTop,
			offsetLeft: this.offsetLeft,
			events: {
				onPopupClose: function ()
				{
					this.destroy();
				},
				onPopupDestroy: function ()
				{
					this.popup = null;
					this.callbacks.onDestroy();
				}.bind(this)
			},
			content: this.createLayout()
		});
	};

	BX.Call.HardwareDialog.prototype.createLayout = function()
	{
		return BX.create("div", {props: {className: 'bx-messenger-call-dialog-allow'}, children: [
			BX.create("div", {props: {className: 'bx-messenger-call-dialog-allow-image-block'}, children: [
				BX.create("div", {props: {className: 'bx-messenger-call-dialog-allow-center'}, children: [
					BX.create("div", {props: {className: 'bx-messenger-call-dialog-allow-arrow'}})
				]}),
				BX.create("div", {props: {className : 'bx-messenger-call-dialog-allow-center'}, children: [
					BX.create("div", {props: {className : 'bx-messenger-call-dialog-allow-button'}, html: BX.message('IM_M_CALL_ALLOW_BTN')})
				]})
			]}),
			BX.create("div", {props: {className : 'bx-messenger-call-dialog-allow-text'}, html: BX.message('IM_M_CALL_ALLOW_TEXT')})
		]});
	};

	BX.Call.HardwareDialog.prototype.show = function()
	{
		if(!this.popup)
		{
			this.createPopup();
		}

		this.popup.show();
	};

	BX.Call.HardwareDialog.prototype.close = function()
	{
		if(this.popup)
		{
			this.popup.close();
		}
	}
})();