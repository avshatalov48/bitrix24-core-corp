BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TaskDetailPartsProjDep != 'undefined')
	{
		return;
	}

	var Item = BX.Tasks.Util.ItemSet.Item.extend({
		sys: {
			code: 'item'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.ItemSet.Item);

				this.vars.data = this.option('data');
                this.bindEvents();
			},

            bindEvents: function()
			{
				this.bindDelegateControl('type-left', 'change', this.passCtx(this.onRelationLeftChange));
				this.bindDelegateControl('type-right', 'change', this.passCtx(this.onRelationRightChange));
			},

			value: function()
			{
				return this.vars.data.VALUE;
			},
			display: function()
			{
				return this.vars.data.DISPLAY;
			},

			destruct: function()
			{
				var value = this.value();

				BX.remove(this.sys.scope);
				this.sys.scope = null;
				this.ctrls = null;
				this.vars.data = null;

				return value;
			},

			onRelationLeftChange: function(node)
			{
				this.control('type').value = this.getLinkTypeByEnds(node.value, this.control('type-right').value);
			},

			onRelationRightChange: function(node)
			{
				this.control('type').value = this.getLinkTypeByEnds(this.control('type-left').value, node.value);
			},

			onDeleteClick: function()
			{
				this.fireEvent('delete', [this.value()]);
			},

			getLinkTypeByEnds: function(left, right)
			{
				if(left == 's')
				{
					return right == 's' ? Item.LINK_TYPE_START_START : Item.LINK_TYPE_START_FINISH;
				}
				else
				{
					return right == 's' ? Item.LINK_TYPE_FINISH_START : Item.LINK_TYPE_FINISH_FINISH;
				}
			}
		}
	});

	Item.LINK_TYPE_START_START = 		0;
	Item.LINK_TYPE_START_FINISH = 		1;
	Item.LINK_TYPE_FINISH_START = 		2;
	Item.LINK_TYPE_FINISH_FINISH = 		3;

	BX.Tasks.Component.TaskDetailPartsProjDep = BX.Tasks.PopupItemSet.extend({
		sys: {
			code: 'projdep-item-set'
		},
        options: {
            itemFx: 'vertical',
            itemFxHoverDelete: true
        },
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.PopupItemSet);

				if(typeof this.instances != 'undefined')
				{
					this.instances = {calendar: false};
				}

				this.instances.selector = window['O_'+this.option('selectorCode')];
			},

			load: function()
			{
				this.callMethod(BX.Tasks.PopupItemSet, 'load', arguments);
				this.toggleContainer();
			},

			openAddForm: function()
			{
				if(this.option('restricted') && B24)
				{
					B24.licenseInfoPopup.show(this.code(), BX.message('TASKS_TTDP_LICENSE_TITLE'), '<span>'+BX.message('TASKS_TTDP_LICENSE_BODY')+'</span>');
					return;
				}

				return this.callMethod(BX.Tasks.PopupItemSet, 'openAddForm', arguments);
			},

			assignCalendar: function(calendar)
			{
				this.instances.calendar = calendar;
			},

			bindFormEvents: function()
			{
				BX.addCustomEvent(this.instances.selector, 'on-change', BX.delegate(this.itemsChanged, this));
                this.bindEvent('item-destroy', this.onItemDestroy);
			},

            toggleContainer: function()
            {
                var cont = this.control('container');
                if(cont)
                {
                    BX[this.itemCount() ? 'removeClass' : 'addClass'](cont, 'hidden');
                }
            },

            onItemDestroy: function()
            {
                this.toggleContainer();
            },

            addItem: function(data, parameters)
            {
                if(this.callMethod(BX.Tasks.PopupItemSet, 'addItem', arguments))
                {
                    if(!parameters.load)
                    {
                        this.toggleContainer();
                    }
                }
            },

			createItem: function(data)
			{
				// "template logic" emulation :(
				data.DEPENDS_ON_TITLE = data.SE_DEPENDS_ON.TITLE;

				data.L_START = 	data.TYPE == Item.LINK_TYPE_START_START || data.TYPE == Item.LINK_TYPE_START_FINISH ? 'selected' : '';
				data.L_FINISH = data.TYPE == Item.LINK_TYPE_FINISH_START || data.TYPE == Item.LINK_TYPE_FINISH_FINISH ? 'selected' : '';

				data.R_START = 	data.TYPE == Item.LINK_TYPE_START_START || data.TYPE == Item.LINK_TYPE_FINISH_START ? 'selected' : '';
				data.R_FINISH = data.TYPE == Item.LINK_TYPE_START_FINISH || data.TYPE == Item.LINK_TYPE_FINISH_FINISH ? 'selected' : '';

				// prepare scope
				var scope = this.getNodeByTemplate('item', data)[0];
				BX.append(scope, this.control('items'));

				// make widget-like item
				var item = new Item({
					scope: scope,
					data: data,
                    parent: this
				});

				return item;
			},

			getPopupAttachTo: function()
			{
				return this.control('open-form');
			},

			applySelectionChange: function()
			{
				for(var k in this.vars.temporalItems)
				{
					var item = this.vars.temporalItems[k];

					this.addItem({
						TASK_ID: this.option('task').data.TASK_ID,
						DEPENDS_ON_ID: item.id,
						SE_DEPENDS_ON: {
							TITLE: item.name
						},
						TYPE: Item.LINK_TYPE_FINISH_START
					}, {});

					break; // hell-code
				}

				this.instances.window.close();
			},

			extractItemValue: function(data)
			{
				return data.DEPENDS_ON_ID;
			},

			extractItemDisplay: function(data)
			{
				return '1'; // whatever, display is unused here
			}
		}
	});

})();