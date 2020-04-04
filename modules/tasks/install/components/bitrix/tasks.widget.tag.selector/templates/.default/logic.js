'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetTagSelector != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetTagSelector = BX.Tasks.Component.extend({
		sys: {
			code: 'tag-sel-is'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				this.subInstance('items', function(){
					return new this.constructor.Items({
						scope: this.scope(),
						data: this.option('data'),
						preRendered: true
					});
				});
			}
		}
	});

	BX.Tasks.Component.TasksWidgetTagSelector.Items = BX.Tasks.Util.ItemSet.extend({
		sys: {
			code: 'tag-sel'
		},
		options: {
			controlBind: 'class',
			itemFx: 'horizontal',
			itemFxHoverDelete: true
		},
		methods: {

			bindEvents: function()
			{
				this.callMethod(BX.Tasks.Util.ItemSet, 'bindEvents');

				BX.addCustomEvent(window, 'onTaskTagSelectAlt', this.onTagsChange.bind(this));
			},

			onTagsChange: function(tags)
			{
				// add new
				for(var k = 0; k < tags.length; k++)
				{
					var tag = {NAME: tags[k]};
					this.addItem(tag);
				}

				// delete deleted
				this.each(function(item){
					if(!BX.util.in_array(item.display(), tags))
					{
						this.deleteItem(item.value());
					}
				});
			},

			openAddForm: function(node)
			{
				if(!window.tasksTagsPopUp)
				{
					BX.debug('tasksTagsPopUp is not defined');
					return;
				}

				window.tasksTagsPopUp.popupWindow.setBindElement(node);
				window.tasksTagsPopUp.showPopup();
			},

			onItemDeleteByCross: function(value)
			{
				BX.onCustomEvent("onTaskTagDeleteByCross", [value.opts.data]);
				this.callMethod(BX.Tasks.Util.ItemSet, 'onItemDeleteByCross', arguments);
			},

			extractItemDisplay: function(data)
			{
				return data.NAME;
			},

			extractItemValue: function(data)
			{
				if('VALUE' in data)
				{
					return data.VALUE;
				}

				return Math.abs(this.hashCode(data.NAME));
			},

			hashCode: function(str)
			{
				if(!BX.type.isNotEmptyString(str))
				{
					return 0;
				}

				var hash = 0;
				for (var i = 0; i < str.length; i++)
				{
					var c = str.charCodeAt(i);

					if (c > 0xFF)
					{
						c -= 0x350;
					}

					hash = ((hash << 5) - hash) + c;
					hash = hash & hash;
				}

				return hash;
			}
		}
	});

}).call(this);