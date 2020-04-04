'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetMemberSelectorView != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetMemberSelectorView = BX.Tasks.Component.extend({
		sys: {
			code: 'mem-sel'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				this.switchDeleteButtonShow();
			},

			bindEvents: function()
			{
				this.getManager();
			},

			setHeaderButtonLabelText: function(text)
			{
				this.control('header-button').innerHTML = BX.util.htmlspecialchars(text);
			},

			getManager: function()
			{
				return this.subInstance('mgr', function(){
					var mgr = new this.constructor.Manager({
						scope: this.scope(),
						data: this.option('data'),
						query: this.getQuery(),
						nameTemplate: this.option('nameTemplate'),

						min: this.option('min'),
						max: this.option('max'),

						path: this.option('path')
					});

					mgr.bindEvent('change-by-user', this.onChangeByUser.bind(this));

					return mgr;
				});
			},

			onChangeByUser: function()
			{
				if(this.option('enableSync'))
				{
					var id = parseInt(this.option('entityId'));
					var route = this.option('entityRoute');
					var fieldName = this.option('fieldName');

					if(!id || !route || !fieldName)
					{
						return;
					}

					var args = {
						id: id,
						data: {}
					};

					var data = [];
					var userIds = [];

					this.getManager().each(function(item){
						userIds.push(item.value());
						data.push({
							ID: item.value(),
							NAME: item.data().NAME,
							LAST_NAME: item.data().LAST_NAME,
							EMAIL: item.data().EMAIL
						});
					});

					var mngr = this.getManager();
					this.getQuery().add('integration.intranet.absence', {userIds: userIds}, {}, BX.delegate(function(errors, data)
					{
						if (!errors.checkHasErrors())
						{
							if(data.RESULT.length > 0)
							{
								var text = data.RESULT.reduce(function(sum, current)
								{
									return sum + '<br />' + current;
								});

								var popup = BX.PopupWindowManager.create(
									"popupMenuOptions",
									BX(mngr.scope()),
									{
										content: text,
										darkMode: true,
										autoHide: true,
										width: 200
									}
								);

								popup.show();
							}
						}

					}, this));

					args.data[fieldName] = data;

					this.callRemote(route+'.update', args).then(function(){
						BX.Tasks.Util.fireGlobalTaskEvent('UPDATE', {ID: id}, {STAY_AT_PAGE: true}, {id: id});
					});

					this.switchDeleteButtonShow();
				}
			},

			addItem: function(data)
			{
				this.getManager().addItem(data);
			},

			deleteItem: function(data)
			{
				this.getManager().deleteItem(this.getManager().extractItemValue(data));
			},

			switchDeleteButtonShow: function(beforeDelete)
			{
				if (this.option('min') !== 1)
				{
					return;
				}

				var crosses = document.getElementsByClassName('js-id-mem-sel-is-i-delete');

				if (beforeDelete)
				{
					if (crosses.length === 2)
					{
						Object.keys(crosses).forEach(function(key)
						{
							BX.addClass(crosses[key], 'hidden');
						});
					}
				}
				else
				{
					if (crosses.length === 1)
					{
						BX.addClass(crosses[0], 'hidden');
					}
					else
					{
						Object.keys(crosses).forEach(function(key)
						{
							BX.removeClass(crosses[key], 'hidden');
						});
					}
				}
			}
		}
	});

	BX.Tasks.Component.TasksWidgetMemberSelectorView.Manager = BX.Tasks.UserItemSet.extend({
		sys: {
			code: 'mem-sel-is'
		},
		options: {
			preRendered: true,
			autoSync: true,
			role: false,
			multiple: false,
			useSearch: true,
			forceTop: true,
			useAdd: true,
			controlBind: 'class',
			itemFx: 'vertical',
			useSmartCodeNaming: true
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.UserItemSet);

				this.fireUserTriggeredChangeDebounce = BX.debounce(this.fireUserTriggeredChangeDebounce, 800);
			},

			prepareData: function(data)
			{
				data = this.callMethod(BX.Tasks.UserItemSet, 'prepareData', arguments);
				data.AVATAR_CSS = data.AVATAR ? "background: url('"+data.AVATAR+"') center no-repeat; background-size: 35px;" : '';

				return data;
			},

			// sync all on popup close
			onClose: function()
			{
				if(this.vars.changed)
				{
					this.fireUserTriggeredChange();
				}

				this.vars.changed = false;
			},

			// sync all on item deleted by clicking "delete" button
			onItemDeleteClicked: function(node)
			{
				this.parent().switchDeleteButtonShow(true);

				var value = this.doOnItem(node, this.deleteItem);
				if(value)
				{
					this.fireUserTriggeredChangeDebounce();
				}
			},

			fireUserTriggeredChangeDebounce: function()
			{
				this.fireUserTriggeredChange();
			},

			fireUserTriggeredChange: function()
			{
				this.fireEvent('change-by-user');
			}
		}
	});

}).call(this);