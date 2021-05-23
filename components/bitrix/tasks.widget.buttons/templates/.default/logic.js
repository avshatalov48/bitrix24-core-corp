'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetButtons != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetButtons = BX.Tasks.Component.extend({
		sys: {
			code: 'buttons'
		},
		methods: {
			bindEvents: function()
			{
				this.bindDelegateControl('group', 'click', this.passCtx(this.onGroupClick));
			},

			onGroupClick: function(node)
			{
				var code = BX.data(node, 'code');
				if(code)
				{
					code = code.toString().replace('GROUP_', '');

					BX.PopupMenu.show(
						this.id()+code,
						node,
						this.getGroupMenu(code)
					);
				}
			},

			getGroupMenu: function(code)
			{
				var items = [];
				BX.Tasks.each(this.option('groups')[code], function(item){

					if(!item.ACTIVE)
					{
						return;
					}

					var data = {
						code: item.CODE,
						text: item.TITLE,
						title: item.TITLE,
						onclick: this.passCtx(this.doMenuAction)
					};

					if(item.TYPE == 'link' && item.URL != '')
					{
						data.href = item.URL;
					}
					if(item.MENU_CLASS)
					{
						data.className = item.MENU_CLASS;
					}

					items.push(data);
				}.bind(this));

				return items;
			},

			doMenuAction: function(menu, e, item)
			{
				var code = item.code;
				if(code)
				{
					this.fireEvent('button-click', [code]);
				}

				menu.popupWindow.close();
			}
		}
	});

}).call(this);