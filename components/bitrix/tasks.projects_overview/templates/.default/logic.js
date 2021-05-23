'use strict';

BX.namespace('Tasks.Component');

(function() {

	if (typeof(BX.FilterEntitySelector) === "undefined")
	{
		BX.FilterEntitySelector = function() {
			this._id = "";
			this._settings = {};
			this._fieldId = "";
			this._control = null;
			this._selector = null;

			this._inputKeyPressHandler = BX.delegate(this.keypress, this);
		};

		BX.FilterEntitySelector.prototype =
			{
				initialize: function(id, settings) {
					this._id = id;
					this._settings = settings ? settings : {};
					this._fieldId = this.getSetting("fieldId", "");

					BX.addCustomEvent(window, "BX.Main.Filter:customEntityFocus", BX.delegate(this.onCustomEntitySelectorOpen, this));
					BX.addCustomEvent(window, "BX.Main.Filter:customEntityBlur", BX.delegate(this.onCustomEntitySelectorClose, this));

				},
				getId: function() {
					return this._id;
				},
				getSetting: function(name, defaultval) {
					return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
				},
				keypress: function(e) {
					//e.target.value
				},
				open: function(field, query) {
					this._selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
						scope: field,
						id: this.getId() + "-selector",
						mode: this.getSetting("mode"),
						query: query ? query : false,
						useSearch: true,
						useAdd: false,
						parent: this,
						popupOffsetTop: 5,
						popupOffsetLeft: 40
					});
					this._selector.bindEvent("item-selected", BX.delegate(function(data) {
						this._control.setData(BX.util.htmlspecialcharsback(data.nameFormatted), data.id);
						if (!this.getSetting("multi"))
						{
							this._selector.close();
						}
					}, this));
					this._selector.open();
				},
				close: function() {
					if (this._selector)
					{
						this._selector.close();
					}
				},
				onCustomEntitySelectorOpen: function(control) {
					this._control = control;

					//BX.bind(control.field, "keyup", this._inputKeyPressHandler);

					if (this._fieldId !== control.getId())
					{
						this._selector = null;
						this.close();
					}
					else
					{
						this._selector = control;
						this.open(control.field);
					}
				},
				onCustomEntitySelectorClose: function(control) {
					if (this._fieldId !== control.getId())
					{
						this.close();
						//BX.unbind(control.field, "keyup", this._inputKeyPressHandler);
					}
				}
			};
		BX.FilterEntitySelector.closeAll = function() {
			for (var k in this.items)
			{
				if (this.items.hasOwnProperty(k))
				{
					this.items[k].close();
				}
			}
		};
		BX.FilterEntitySelector.items = {};
		BX.FilterEntitySelector.create = function(id, settings) {
			var self = new BX.FilterEntitySelector(id, settings);
			self.initialize(id, settings);
			this.items[self.getId()] = self;
			return self;
		};
	}

	if (typeof BX.Tasks.Component.TasksProjectsOverview != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksProjectsOverview = BX.Tasks.Component.extend({
		sys: {
			code: 'projects-overview'
		},
		methodsStatic: {
			instance: {},

			getInstance: function() {
				return BX.Tasks.Component.TasksProjectsOverview.instance;
			},

			addInstance: function(obj) {
				BX.Tasks.Component.TasksProjectsOverview.instance = obj;
			}
		},
		methods: {
			filterOwnerInstances: {},
			construct: function() {
				this.callConstruct(BX.Tasks.Component);
				this.filterOwnerInit();
				BX.Tasks.Component.TasksProjectsOverview.addInstance(this);
				// create sub-instances through this.subInstance(), do some initialization, etc

				// do ajax call, like
				// this.callRemote('this.sampleCreateTask', {data: {TITLE: 'Sample Task'}}).then(function(result){ ... });
				// dont care about CSRF, SITE_ID and LANGUAGE_ID: it will be sent and checked automatically
			},

			bindEvents: function() {
				this.bindDelegateControl('members-list', 'click', this.showMembersListPopup);

				// do some permanent event bindings here, like i.e.
				/*
				this.bindControlPassCtx('some-div', 'click', this.showAddPopup);
				this.bindControlPassCtx('some-div', 'click', this.showActionPopup);
				this.bindControlPassCtx('some-div', 'click', this.showUnHideFieldPopup);
				this.bindDelegateControl('some-div', 'keypress', this.jamEnter, this.control('new-item-place'));
				*/
			},

			filterOwnerInit: function() {
				// debugger
				var customFields = this.option('customFields');
				for (var key in customFields)
				{
					var item = customFields[key];

					BX.FilterEntitySelector.create(
						item.id,
						{
							fieldId: item.fieldId,
							mode: item.mode,
							multi: item.multi ? 'true' : 'false'
						}
					)
				}
			},

			showMembersListPopup: function() {

				var groupId = this.dataset.groupId;
				var instance = BX.Tasks.Component.TasksProjectsOverview.instance;

				var popups = instance.option('popups') || {};

				if (typeof popups[groupId] == 'undefined')
				{
					popups[groupId] = new BX.PopupWindow(
						this.id + '-popup-menu',
						this, {
							closeByEsc: true,
							autoHide: true,
							lightShadow: true,
							zIndex: 2,
							content: instance.renderMembersList(groupId) || '',
							// offsetLeft: 50,
							angle: true
						}
					);

					instance.option('popups', popups);
				}

				popups[groupId].show();
			},
			renderMembersList: function(groupId) {
				//this because call throw "instance" variable in showMembersListPopup
				var query = new BX.Tasks.Util.Query({ autoExec: true });

				var data = BX.create('DIV', {
					props: {
						className: 'structure-dept-emp-popup'
					}
				});

				query.run('integration.socialnetwork.getmemberlist', { groupId: groupId }).then(function(membersList) {
					for (var i in membersList.data)
					{
						var member = membersList.data[i];

						var obEmployee = BX.create('DIV', {
							props: { className: 'structure-boss-block' },
							attrs: {
								'title': BX.util.htmlspecialchars(member.FORMATTED_NAME),
								'data-user': member.ID
							},

							html: '<a' + (member.PHOTO ? ' style="display:inline-block;vertical-align:middle;border-radius:50%;background: url(\'' + member.PHOTO + '\') no-repeat scroll center center transparent; background-size: cover;"' : '') + ' class="structure-avatar" href="' + member.HREF + '"></a><div class="structure-employee-name" style="display:inline-block;vertical-align:middle;"><a href="' + member.HREF + '">' + BX.util.htmlspecialchars(member.FORMATTED_NAME) + '</a></div>' + (member.WORK_POSITION ? '<div class="structure-employee-post">' + BX.util.htmlspecialchars(member.WORK_POSITION) + '</div>' : '')
						});

						if (member.IS_HEAD)
						{
							obEmployee.className += ' bx-popup-head';
							if (data.firstChild)
							{
								data.insertBefore(obEmployee, data.firstChild);
								continue;
							}
						}

						data.appendChild(obEmployee);
					}

				}, function() {
					console.dir('Oopsss...');
				});


				return data;
			}
			// add more methods, then call them like this.methodName()
		}
	});

	// may be some sub-controllers here...
}).call(this);