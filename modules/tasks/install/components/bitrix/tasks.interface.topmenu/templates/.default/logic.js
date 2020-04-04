'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TopMenu != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TopMenu = BX.Tasks.Component.extend({
		sys: {
			code: 'topmenu'
		},

		methodsStatic: {
			instances: {},

			getInstance: function(name)
			{
				return BX.Tasks.Component.TopMenu.instances[name];
			},

			addInstance: function(name, obj)
			{
				BX.Tasks.Component.TopMenu.instances[name] = obj;
			}
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				BX.Tasks.Component.TopMenu.addInstance(this.sys.code, this);

				// if(this.option('show_sl_effective'))
				// {
				// 	this.spotLightInit();
				// }
			},

			bindEvents: function()
			{
				var self = this;

				try
				{
					var elements = this.option('use_ajax_filter') ? this.scope().getElementsByClassName("tasks_role_link") : {};
					if (elements.length)
					{
						for (var key = 0; key < elements.length; key++)
						{
							BX.bind(elements[key], 'click', function(event)
							{
								//TODO
								event.preventDefault();

								var targetClass = event.target.className;
								var roleId = this.dataset.id == 'view_all' ? '' : this.dataset.id;
								var url = this.dataset.url;

								if (targetClass === 'main-buttons-item-sublink ' && roleId === '' || targetClass === 'main-buttons-item-edit-button')
								{
									return;
								}

								BX.onCustomEvent("Tasks.TopMenu:onItem", [roleId, url]); //FIRE

								var elements = this.parentElement.getElementsByClassName("tasks_role_link");
								if (elements.length)
								{
									for (var key = 0; key < elements.length; key++)
									{
										BX.removeClass(elements[key], 'main-buttons-item-active');
									}
								}
								BX.addClass(this, 'main-buttons-item-active');
							});
						}
					}
				}
				catch(e)
				{}

				BX.addCustomEvent('BX.Main.Filter:apply', function(filterId, data, ctx, promise, params)
				{
					var fields = ctx.getFilterFieldsValues();
					var roleId = fields.ROLEID;

					try
					{
						var scope = BX.Tasks.Component.TopMenu.getInstance('topmenu').scope();
						var el = scope.querySelectorAll('.tasks_role_link');

						for (var i = 0; i < el.length; i++)
						{
							BX.removeClass(el[i], 'main-buttons-item-active');
						}

						if (typeof roleId !== 'undefined')
						{
							if (!roleId)
							{
								roleId = 'view_all';
							}
							BX.addClass(BX('tasks_panel_menu_' + roleId), 'main-buttons-item-active');

							var toolbar = BX.Tasks.Component.TasksToolbar.getInstance();
							toolbar.getToolbarData(roleId, function()
							{
								toolbar.render();
							});
						}
					}
					catch (e)
					{
						//console.log(e);
					}
				});
			},

			spotLightInit: function()
			{
				var self = this;

				var moreBtn = BX('tasks_panel_menu_more_button');
				var menuItemEffective = BX("tasks_panel_menu_view_effective");

				if(menuItemEffective)
				{
					var spotlight = new BX.SpotLight({
						id: 'tasks_sl_effective',
						targetElement: menuItemEffective,
						content: self.option('text_sl_effective'),
						targetVertex: "middle-center",
						autoSave: true
					});
					spotlight.show();
				}
			}
		}
	});


	// may be some sub-controllers here...

}).call(this);