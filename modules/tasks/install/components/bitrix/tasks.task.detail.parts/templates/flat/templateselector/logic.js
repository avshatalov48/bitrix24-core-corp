BX.namespace('Tasks.Component');

(function() {

	if(typeof BX.Tasks.Component.TaskDetailPartsTemplateSelector != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TaskDetailPartsTemplateSelector = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'templateselector'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				BX.bind(this.control('open'), 'click', this.onPopupOpen.bind(this));
				this.menu = null;
				this.menuItems = null;
			},

			onPopupOpen: function(e)
			{
				e = e || window.event;
				var target = e.currentTarget;

				this.getMenuItems().then(function(items){
					this.menuItems = items;
					this.showMenu(target);
				}.bind(this));
			},

			showMenu: function(bindElement)
			{
				if(!this.menu)
				{
					this.menu = this.createMenu(bindElement);
				}

				this.menu.popupWindow.show();
				BX.addClass(bindElement, "webform-button-active");
			},

			getMenuItems: function()
			{
				var p = new BX.Promise();

				if(this.option('menuItems').length)
				{
					p.fulfill(this.option('menuItems'));
				}
				else if(this.menuItems)
				{
					p.fulfill(this.menuItems);
				}
				else
				{
					BX.ajax.runComponentAction('bitrix:tasks.templates.list', 'getList', {
						mode: 'class',
						data: {
							select: ['ID', 'TITLE'],
							order: {ID: 'DESC'},
							filter: {ZOMBIE: 'N'}
						}
					}).then(
						function(response)
						{
							p.fulfill(this.makeItemsFromResult(response));
						}.bind(this),
						function(response)
						{
							p.reject();
						}.bind(this)
					);
				}

				return p;
			},

			makeItemsFromResult: function(response)
			{
				var items = [];
				var commonUrl = this.option('commonUrl');

				var data = response.data;
				if(data && data.length)
				{
					var url = commonUrl+(commonUrl.indexOf('?') < 0 ? "?" : "&");
					var href = window.location.href;
					var path = window.location.pathname;
					var _query = href.split(path)[1];
					var query = _query.substr(1, _query.length - 1);
					var params = query.split('&');

					Object.keys(params).forEach(function(i) {
						if (params[i].indexOf('IFRAME') === 0 ||
							params[i].indexOf('TEMPLATE') === 0)
						{
							delete params[i];
						}
					});

					params = params.filter(function(value){
						return value !== undefined;
					});

					if ( params.length )
					{
						url += params.join('&')+'&';
					}

					BX.Tasks.each(data, function(item){
						items.push({
							ID: parseInt(item.ID),
							TITLE: item.TITLE,
							URL: url + "TEMPLATE="+parseInt(item.ID)
						});
					});
				}

				return items;
			},

			createMenu: function(bindElement)
			{
				var menu = [];
				var onClick = function(){
					this.popupWindow.close();
				};
				BX.Tasks.each(this.menuItems || [], function(item){
					menu.push({
						text: BX.util.htmlspecialchars(item.TITLE),
						title: item.TITLE,
						href: item.URL,
						onclick: onClick
					});
				});

				menu.push({
					delimiter: true
				});

				menu.push({
					text: BX.util.htmlspecialchars(BX.message('TASKS_TTDP_TEMPLATESELECTOR_TO_LIST')),
					title: BX.util.htmlspecialchars(BX.message('TASKS_TTDP_TEMPLATESELECTOR_TO_LIST')),
					href: this.option('toTemplates'),
					target: '_top',
					onclick: onClick
				});

				var menuId = this.id()+'-form-transport';
				var popupMenu = new BX.PopupMenuWindow(menuId, bindElement, menu, {
					offsetLeft: 20,
					closeByEsc: true,
					angle: {
						position: 'top'
					},
					events: {
						onPopupClose : this.onPopupClose.bind(this)
					}
				});

				if(!this.option('useSlider'))
				{
					var items = popupMenu.menuItems;
					for (var i = 0; i < items.length; i++)
					{
						var itemLayout = items[i].layout;
						if (itemLayout && itemLayout.item)
						{
							itemLayout.item.dataset.sliderIgnoreAutobinding = true;
						}
					}
				}

				return popupMenu;
			},

			onPopupClose: function()
			{
				BX.removeClass(this.control('open'), "webform-button-active");
			}
		}
	});

}).call(this);