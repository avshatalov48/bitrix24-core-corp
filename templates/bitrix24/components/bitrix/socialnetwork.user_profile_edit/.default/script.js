BX.namespace("BX.Socialnetwork.User");

BX.Socialnetwork.User.ProfileEdit =
{
	init: function ()
	{
		this.popupHint = {};

		BX.ready(BX.delegate(function(){
			if (BX.type.isDomNode(BX("group_admin_hint")))
			{
				this.initHint('group_admin_hint');
			}
		}, this));
	},

	initHint : function(nodeId)
	{
		var node = BX(nodeId);
		if (node)
		{
			node.setAttribute('data-id', node);
			BX.bind(node, 'mouseover', BX.proxy(function(){
				var id = BX.proxy_context.getAttribute('data-id');
				var text = BX.proxy_context.getAttribute('data-text');
				this.showHint(id, BX.proxy_context, text);
			}, this));
			BX.bind(node, 'mouseout',  BX.proxy(function(){
				var id = BX.proxy_context.getAttribute('data-id');
				this.hideHint(id);
			}, this));
		}
	},

	showHint : function(id, bind, text)
	{
		if (this.popupHint[id])
		{
			this.popupHint[id].close();
		}

		this.popupHint[id] = new BX.PopupWindow('user-profile-email-hint', bind, {
			lightShadow: true,
			autoHide: false,
			darkMode: true,
			offsetLeft: 0,
			offsetTop: 2,
			bindOptions: {position: "top"},
			zIndex: 200,
			events : {
				onPopupClose : function() {this.destroy()}
			},
			content : BX.create("div", { attrs : { style : "padding-right: 5px; width: 250px;" }, html: text})
		});
		this.popupHint[id].setAngle({offset:13, position: 'bottom'});
		this.popupHint[id].show();

		return true;
	},

	hideHint : function(id)
	{
		this.popupHint[id].close();
		this.popupHint[id] = null;
	}
};