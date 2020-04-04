'use strict';

BX.namespace('Tasks.Util');

if(typeof BX.Tasks.Util.SelectBox == 'undefined')
{
	BX.Tasks.Util.SelectBox = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'selectbox'
		},
		options: {
			controlBind: 'class',
			items: [],
			selected: null,
			allowDeselect: true,
			notSelectedLabel: null
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				this.vars.items = new BX.Tasks.Util.Collection({
					keyField: 'VALUE'
				});
				this.vars.rendered = false;

				this.load(this.option('items'));
				this.value(this.option('selected'));

				this.bindControlThis('open', 'click', this.showPopup);
			},

			getPane: function()
			{
				return this.subInstance('pane', function(){
					var pane = new BX.Tasks.Util.ScrollPanePopup({
						scope: this.getNodeByTemplate('pane')[0],
						popupParameters: this.getPopupParameters(),
					});

					pane.bindDelegateControlPassCtx('item', 'click', BX.delegate(this.itemSelected, this));

					return pane;
				});
			},

			showPopup: function()
			{
				var pane = this.getPane();

				if(!this.vars.rendered)
				{
					pane.append(this.getItemsHTML(this.vars.items, true));

					this.vars.rendered = true;
				}

				pane.bindTo(this.getBindPaneTo()).show();
			},

			getItemsHTML: function(items, addDeselect)
			{
				var html = '';

				if(addDeselect && this.option('allowDeselect')) // place empty option here
				{
					html += this.getHTMLByTemplate('item', {
						VALUE: '',
						DISPLAY: this.getLabelNotSelected(),
						SELECTED: this.vars.value ? 'selected' : '',
						DESELECT: 'deselect',
						DISPLAY_PREFIX_UNSAFE: ''
					});
				}

				items.each(function(item){

					if(!('DISPLAY_PREFIX_UNSAFE' in item))
					{
						item['DISPLAY_PREFIX_UNSAFE'] = '';
					}

					var itemData = BX.mergeEx(BX.clone(item), {
						SELECTED: this.vars.value == item.VALUE ? 'selected' : '',
						DESELECT: ''
					});

					html += this.getHTMLByTemplate('item', itemData);
				}, this);

				return html;
			},

			getPopupParameters: function()
			{
				return {noAllPaddings: true};
			},

			getBindPaneTo: function()
			{
				return this.control('open');
			},

			itemSelected: function(node)
			{
				var value = BX.data(node, 'value');

				this.value(value, {node: node});
				this.getPane().hide();
			},

			load: function(items)
			{
				if(this.subInstance('pane'))
				{
					this.clear();
					this.getPane().hide();
				}

				this.vars.items.load(items);
			},

			reload: function(items)
			{
				this.clear();
				this.load(items);
			},

			clear: function()
			{
				this.vars.items.clear(); // clear data
				this.getPane().clear(); // clear rendered
				this.vars.rendered = false;
				this.value('', true); // clear value, fire event
			},

			value: function(value, event)
			{
				if(typeof value == 'undefined')
				{
					return this.vars.value;
				}
				else
				{
					if(this.vars.value != value)
					{
						// find value among items
						var item = this.vars.items.getByKey(value);
						var display;

						if(item !== null) // found
						{
							this.vars.value = value;
							display = item.DISPLAY;
						}
						else
						{
							this.vars.value = null;
							display = this.getLabelNotSelected().toLowerCase();
						}

						this.control('current-display').innerHTML = BX.util.htmlspecialchars(display);

						if(event)
						{
							this.fireEvent('change', [value, event]);
						}
					}
				}
			},

			highlight: function(value)
			{
				// todo
			},

			getLabelNotSelected: function()
			{
				var label = this.option('notSelectedLabel');
				return label ? label : BX.message('TASKS_COMMON_NOT_SELECTED');
			},

			// built-in default templates. you can overriding it by including the corresponding <script> tag inside scope
			getDefaultTemplates: function()
			{
				return {
					pane: '<div>' +
								'<div class="js-id-scrollpane-pane menu-popup tasks-scrollpane">'+
									'<div class="js-id-scrollpane-body js-id-scrollpane-container menu-popup-items tasks-scrollpane-body"></div>'+
								'</div>' +
							'</div>',
					item: '<span title="{{DISPLAY}}" data-value="{{VALUE}}" class="js-id-scrollpane-item menu-popup-item menu-popup-no-icon {{DESELECT}} {{SELECTED}}">'+
								'<span class="menu-popup-item-text">'+
									'{{{DISPLAY_PREFIX_UNSAFE}}}{{DISPLAY}}'+
								'</span>'+
							'</span>'
				};
			}
		}
	});
}

// todo: select by pressing arrow up\down
// todo: lazy show on huge item sets
// todo: highlight the currently selected item in the dropdown
// todo: de-select item by cleaning text in input
if(typeof BX.Tasks.Util.ComboBox == 'undefined')
{
	BX.Tasks.Util.ComboBox = BX.Tasks.Util.SelectBox.extend({
		sys: {
			code: 'combobox'
		},
		options: {
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.SelectBox);

				this.vars.lastValue = null;
				this.vars.filterHandlerFabric = function(value){
					return new RegExp('(^|\\s+)'+value, 'i');
				};

				this.bindControlPassCtx('search', 'keyup', BX.debounce(this.onUserType, 250));
				BX.bind(document, 'click', BX.delegate(this.onDocumentClick, this));
			},

			showPopup: function()
			{
				this.toggleSearch(true);

				var value = this.value();

				this.control('search').value = value ? this.vars.items.getByKey(value).DISPLAY : '';
				this.control('search').focus();

				this.vars.lastValue = this.control('search').value;

				this.callMethod(BX.Tasks.Util.SelectBox, 'showPopup', arguments);
			},

			setFilterHandlerFabric: function(fn)
			{
				this.vars.filterHandlerFabric = fn;
			},

			toggleSearch: function(way)
			{
				this.changeCSSFlag('search', way);
			},

			itemSelected: function(node)
			{
				this.callMethod(BX.Tasks.Util.SelectBox, 'itemSelected', arguments);

				this.toggleSearch(false);

				// todo: try to optimize pane cleanings
				this.subInstance('pane').clear();
				this.vars.rendered = false;
			},

			onDocumentClick: function(e)
			{
				if(!this.subInstance('pane') || !this.subInstance('pane').subInstance('window') || !this.subInstance('pane').subInstance('window').isShown())
				{
					return true;
				}

				var top = e.target;
				var pane = this.subInstance('pane');
				var boundTo = this.getBindPaneTo();
				var search = this.control('search');
				var open = this.control('open');
				var inside = false;

				while(top)
				{
					if(top == boundTo || top == search || top == open)
					{
						// we are inside
						inside = true;
						break;
					}

					top = top.parentNode;
				}

				if(!inside)
				{
					pane.clear();
					this.vars.rendered = false;
					pane.hide();
					this.toggleSearch(false);
				}
			},

			onUserType: function(node, e)
			{
				var value = node.value.toString().trim();
				var found = this.vars.items;
				var renderReset = true;

				if(value == this.vars.lastValue)
				{
					return;
				}
				this.vars.lastValue = value;

				if(value.length > 1)
				{
					found = this.vars.items.find(this.vars.filterHandlerFabric.apply(this, [value]));
					renderReset = false;
				}

				if(found !== null)
				{
					var pane = this.getPane();
					pane.clear();
					pane.append(this.getItemsHTML(found, renderReset));

					// try to re-attach popup, to force it to recalculate its own y-position
					pane.hide();
					pane.getPopup().adjustPosition(this.getBindPaneTo());
					pane.show(null);
					this.vars.rendered = false;
				}
			},

			getBindPaneTo: function()
			{
				return this.control('search');
			},

			getPopupParameters: function()
			{
				return {
					noAllPaddings: true,
					autoHide: false
				};
			}
		}
	});
}