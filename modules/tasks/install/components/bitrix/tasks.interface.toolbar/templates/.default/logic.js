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
				this.callConstruct(BX.Tasks.Component);
				BX.Tasks.Component.TasksToolbar.addInstance(this);

				if (!this.option('counters'))
				{
					this.rerender('');
				}
				else
				{
					this.render();
				}
			},

			bindEvents: function()
			{
				var eventHandlers = {
					user_counter: this.onUserCounter
				};

				BX.addCustomEvent('onPullEvent-tasks', function(command, params) {
					if (eventHandlers[command])
					{
						eventHandlers[command].apply(this, [params]);
					}
				}.bind(this));

				BX.addCustomEvent('Tasks.Toolbar.Reload', function(roleId) {
					if (this.option('groupId'))
					{
						this.rerender(roleId);
					}
				}.bind(this));

				BX.addCustomEvent('Tasks.TopMenu:onItem', function(roleId) {
					this.rerender(roleId);
				}.bind(this));

				BX.addCustomEvent('BX.Kanban.ChangeGroup', function(groupId, oldGroupId) {
					this.option('groupId', groupId);
					this.rerender('');
				}.bind(this));
			},

			onUserCounter: function(data)
			{
				if (
					!this.option('showCounters')
					|| this.option('groupId')
					|| Number(this.option('userId')) !== Number(data.userId)
				)
				{
					return;
				}

				var filterId = this.option('filterId') || null;
				if (filterId)
				{
					var roleId = 'view_all';
					var filterObject = BX.Main.filterManager.getById(filterId);
					if (filterObject)
					{
						var fields = filterObject.getFilterFieldsValues();
						roleId = fields.ROLEID || roleId;
					}

					this.option('counters', this.prepareCounters(data[roleId]));
					this.render();
				}
			},

			prepareCounters: function(counters)
			{
				var codes = {
					total: '',
					expired: 6291456,
					new_comments: 12582912
				};
				var result = {};

				Object.keys(counters).forEach(function(key) {
					result[key] = {
						code: codes[key],
						counter: counters[key]
					};
				});

				return result;
			},

			rerender: function(roleId)
			{
				this.getToolbarData(roleId, this.render.bind(this));
			},

			getToolbarData: function(roleId, cb)
			{
				roleId = roleId || '';
				cb = cb || {};

				var ownerId = this.option('ownerId');
				var groupId = this.option('groupId') || 0;

				this.callRemote('ui.counters.get', {
					userId: ownerId,
					type: roleId,
					groupId: groupId
				}).then(function(result) {
					this.option('counters', result.getData());
					cb.call(this);
				}.bind(this));
			},

			render: function()
			{
				var templates = this.option('templates');
				var counters = this.option('counters');
				var messages = this.option('messages');
				var classes = this.option('classes');
				var buttons = this.option('buttons');

				var html = [];

				if (typeof counters.total === "object" && "counter" in counters.total && counters.total.counter > 0)
				{
					html.push(
						templates.total
							.replace('#COUNTER#', counters.total.counter)
							.replace('#TEXT#', messages.total)
					);

					Object.keys(counters).forEach(function(key) {
						var counter = counters[key];
						if (counter.counter > 0 && key !== 'total')
						{
							html.push(
								templates.counter
									.replace('#COUNTER#', counter.counter)
									.replace('#COUNTER#', counter.counter)
									.replace('#COUNTER_ID#', key)
									.replace('#COUNTER_CODE#', counter.code)
									.replace('#TEXT#', messages[key + '_' + this.getPluralForm(counter.counter)])
									.replace('#CLASS#', classes[key])
									.replace('#BUTTON#', (buttons[key] || ''))
							);
						}
					}.bind(this));
				}
				else
				{
					html.push(templates.empty.replace('#TEXT#', messages.empty));
				}

				this.scope().innerHTML = html.join('');

				var elements = this.scope().getElementsByClassName("tasks-counter-container");
				Object.values(elements).forEach(function(element) {
					BX.bind(element, 'click', function(event) {
						BX.PreventDefault(event);
						BX.onCustomEvent("Tasks.Toolbar:onItem", [this.dataset.counterCode, window.location.href]);
					});
					if (
						element.nextSibling
						&& BX.hasClass(element.nextSibling, "tasks-counter-counter-button")
						&& element.nextSibling.dataset.counterId === 'new_comments'
					)
					{
						BX.bind(element.nextSibling, 'click', function() {
							BX.ajax.runAction('tasks.task.comment.readAll', {data: {
								groupId: this.option('groupId') || 0,
								userId: this.option('ownerId')
							}});
						}.bind(this));
					}
				}.bind(this));
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