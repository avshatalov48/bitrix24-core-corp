'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksToolbar != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksToolbar = BX.Tasks.Component.extend({
		sys: {
			code: 'toolbar'
		},
		methodsStatic: {
			instance: {},

			getInstance: function()
			{
				return BX.Tasks.Component.TasksToolbar.instance;
			},

			addInstance: function(obj)
			{
				BX.Tasks.Component.TasksToolbar.instance = obj;
			}
		},
		methods: {
			construct: function()
			{
				var self = this;
				this.callConstruct(BX.Tasks.Component);
				BX.Tasks.Component.TasksToolbar.addInstance(this);

				if(!this.option('counters'))
				{
					self.getToolbarData('', function()
					{
						self.render();
					});
				}
				else
				{
					self.render();
				}
			},

			bindEvents: function()
			{
				var self = this;

				BX.addCustomEvent("tasksTaskEvent", function() {
					var filterId = null;
					var gridId = null;

					if(BX.Tasks.KanbanComponent && BX.Tasks.KanbanComponent.filterId !== undefined)
						filterId = BX.Tasks.KanbanComponent && BX.Tasks.KanbanComponent.filterId;

					if(BX.Tasks.GridActions && BX.Tasks.GridActions.gridId!== undefined)
						gridId = BX.Tasks.GridActions.gridId;

					filterId = filterId || gridId;

					if (!filterId)
					{
						return false;
					}

					var filterObject = BX.Main.filterManager.getById(filterId);
					var fields = filterObject.getFilterFieldsValues();
					var roleid = fields.ROLEID || 'view_all';//debugger

					BX.onCustomEvent("Tasks.Toolbar.Reload", [roleid]); //FIRE
				});

				BX.addCustomEvent("Tasks.Toolbar.Reload", function(roleid) {

					self.getToolbarData(roleid, function() {
						self.render();
					})
				});

				BX.addCustomEvent("Tasks.TopMenu:onItem", function(counterId)
				{

					self.getToolbarData(counterId, function(){
						self.render();
					})
				});

				BX.addCustomEvent('BX.Kanban.ChangeGroup', function(groupId, oldGroupId)
				{
					self.option('groupId', groupId);
					self.getToolbarData('', function(){
						self.render();
					})
				});
			},

			getToolbarData: function(counterId, cb)
			{
				counterId = counterId || '';
				cb = cb || {};

				var userId = this.option('userId');
				var groupId = this.option('groupId') || 0;
				var self = this;

				this.callRemote('ui.counters.get',
					{
						userId: userId,
						type: counterId,
						groupId: groupId
					}
				).then(function(result)
				{
					self.option('counters', result.getData());
					cb.call(self);
				});
			},

			render: function()
			{
				var templates = this.option('templates');
				var counters = this.option('counters');
				var messages = this.option('messages');
				var classes = this.option('classes');

				var html = [];

				if (typeof counters.total === "object" && "counter" in counters.total && counters.total.counter > 0)
				{
					html.push(
						templates.total
							.replace('#COUNTER#', counters.total.counter)
							.replace('#TEXT#', messages.total)
					);

					for (var key in counters) {
						if(counters[key].counter > 0 && key != 'total')
						{
							html.push(
								templates.counter
									.replace('#COUNTER#', counters[key].counter)
									.replace('#COUNTER#', counters[key].counter)
									.replace('#COUNTER_ID#', key)
									.replace('#COUNTER_CODE#', counters[key].code)
									.replace('#TEXT#', messages[key + '_' + this.getPluralForm(counters[key].counter)])
									.replace('#CLASS#', classes[key])
							);
						}
					}
				}
				else
				{
					html.push(templates.empty.replace('#TEXT#', messages.empty));
				}
				this.scope().innerHTML = html.join('');

				var elements = this.scope().getElementsByClassName("tasks-counter-container");
				if (elements.length)
				{
					for (var key = 0; key < elements.length; key++)
					{
						BX.bind(elements[key], 'click', function(event){ //TODO
							BX.PreventDefault(event);
							var counterId = this.dataset.counterCode;
							var url = window.location.href;
							BX.onCustomEvent("Tasks.Toolbar:onItem", [counterId, url]); //FIRE
						});
					}
				}
			},

			getPluralForm: function(n)
			{
				var pluralForm, langId;

				langId = BX.message('LANGUAGE_ID');
				n = parseInt(n);

				if (n < 0)
				{
					n = (-1) * n;
				}

				if (langId)
				{
					switch (langId)
					{
						case 'de':
						case 'en':
							pluralForm = ((n !== 1) ? 1 : 0);
							break;

						case 'ru':
						case 'ua':
							pluralForm = ( ((n%10 === 1) && (n%100 !== 11)) ? 0 : (((n%10 >= 2) && (n%10 <= 4) && ((n%100 < 10) || (n%100 >= 20))) ? 1 : 2) );
							break;

						default:
							pluralForm = 1;
							break;
					}
				}
				else
				{
					pluralForm = 1;
				}

				return pluralForm;
			}
		}
	});
}).call(this);