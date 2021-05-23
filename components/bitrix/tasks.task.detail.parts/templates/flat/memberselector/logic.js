BX.namespace('Tasks.Component');

(function(){

	BX.Tasks.Component.TaskDetailPartsMemberSelector = BX.Tasks.UserItemSet.extend({
		sys: {
			code: 'tdp-mem-sel',
			// hacky flag, allows to hide previouly selected item when picking a new one in (single mode (max == 1) and required mode (min > 0))
			hidePreviousIfSingleAndRequired: false
		},
		options: {
			controlBind: 'class',
			itemFx: 'horizontal',
			itemFxHoverDelete: true,
			prefixId: true,
			mode: 'all' // users, groups and departments selected
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.UserItemSet);

				var min = this.vars.constraint.min;
				var max = this.vars.constraint.max;

				this.vars.constraint.single = max == 1;
				this.vars.constraint.strict = this.option('hidePreviousIfSingleAndRequired') && this.vars.constraint.single && min > 1;
			},

			onSearchBlurred: function()
			{
				if(this.callMethod(BX.Tasks.UserItemSet, 'onSearchBlurred'))
				{
					if(this.vars.constraint.strict)
					{
						this.restoreKept();
					}
				}
			},

			onSelectorItemSelected: function(data)
			{
				if(this.vars.constraint.strict)
				{
					this.vars.changed = true;
					var value = this.extractItemValue(data);

					if(!this.hasItem(value))
					{
						this.addItem(data);
						this.vars.toDelete = false;

						if(!this.checkCanAddItems()) // can not add new items anymore - close search form
						{
							this.instances.selector.close();
							this.onSearchBlurred();
						}
					}

					this.resetInput();
				}
				else
				{
					this.callMethod(BX.Tasks.UserItemSet, 'onSelectorItemSelected', arguments);
				}
			},

			openAddForm: function()
			{
				// in single mode when we open the form, we must "hide" (actually, temporary delete) the previous item
				if(this.vars.constraint.strict)
				{
					var first = this.getItemFirst();
					if(first)
					{
						this.vars.toDelete = first.data();
						this.callMethod(BX.Tasks.UserItemSet, 'deleteItem', [first.value(), {checkRestrictions: false}]);
					}
				}

				this.callMethod(BX.Tasks.UserItemSet, 'openAddForm');
			},

			deleteItem: function(value)
			{
				if(!this.callMethod(BX.Tasks.UserItemSet, 'deleteItem', arguments))
				{
					// item had not been deleted
					// in strict mode we re-open form to pick a new one after deleting the previous
					if(this.vars.constraint.strict)
					{
						this.openAddForm();
						return false;
					}
				}

				return true;
			},

			// aux function for the strict mode
			restoreKept: function()
			{
				if(this.vars.toDelete)
				{
					this.addItem(this.vars.toDelete, {checkRestrictions: false});
					this.vars.toDelete = false;
				}
			}

		}
	});

}).call(this);