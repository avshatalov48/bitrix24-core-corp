BX.namespace('Tasks.Util');

if(typeof BX.Tasks.Util.ScrollPane == 'undefined')
{
	BX.Tasks.Util.ScrollPane = BX.Tasks.Util.Widget.extend({
		options: {
			controlBind: 'class'
		},
		sys: {
			code: 'scrollpane'
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				this.vars.prevScroll = false;

				this.bindEvents();
			},

			bindEvents: function()
			{
				this.bindControlThis('pane', 'mousewheel', this.filterWheel); // disable wheel event when borders reached

				this.onScrollChanged = BX.throttle(this.onScrollChanged, 50, this);

				this.bindControlThis('pane', 'scroll', this.onScrollChanged);
				this.bindControlThis('body', 'touchstart', this.onScrollChanged);
				BX.bind(window, 'resize', BX.delegate(this.onScrollChanged, this));

				this.onScrollChanged(); // test scroll for the first time
			},

			onScrollChanged: function(force)
			{
				var pane = this.control('pane');
				if(!pane)
				{
					return;
				}

				var scroll = pane.scrollTop;

				if(force || this.vars.prevScroll === false || this.vars.prevScroll !== scroll)
				{
					if(!scroll)
					{
						this.fireEvent('top-reached');
					}

					var bHeight = this.control('body').clientHeight;
					var pHeight = pane.clientHeight;

					if(scroll + pHeight >= bHeight)
					{
						this.fireEvent('bottom-reached');
					}
				}

				this.vars.prevScroll = scroll;
			},

			filterWheel: function(e)
			{
				var wData = BX.getWheelData(e);
				var pane = this.control('pane');
				var jam = false;

				if(wData > 0 && pane.scrollTop == 0) // move up
				{
					jam = true;
				}

				if(wData < 0 && (pane.scrollTop >= pane.scrollHeight - pane.clientHeight)) // move down
				{
					jam = true;
				}

				if(jam)
				{
					BX.PreventDefault(e);
					BX.eventCancelBubble(e);
					return false;
				}
			},

			append: function(html)
			{
				var cont = this.control('container');
				if(cont)
				{
					if(html)
					{
						var vial = BX.create('div'); // use "vial", to avoid the entire container html to be re-rendered on innerHTML change

						if(BX.type.isNotEmptyString(html))
						{
							vial.innerHTML = html;
							BX.append(vial, cont);
						}

						this.onScrollChanged(true);
					}
				}
			},
			prepend: function(html)
			{
				// todo
				this.onScrollChanged(true);
			},

			// this will clear any contents inside
			clear: function()
			{
				var cont = this.control('container');
				if(cont)
				{
					cont.innerHTML = '';
				}
				this.vars.prevScroll = false;
			},

			destroy: function()
			{
			}
		}
	});
}

if(typeof BX.Tasks.Util.ScrollPanePopup == 'undefined')
{
	BX.Tasks.Util.ScrollPanePopup = BX.Tasks.Util.ScrollPane.extend({
		options: {
			popupParameters: {},
			windowId: '',
			maxHeight: 300
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.ScrollPane);

				this.vars.bindTo = null;
				this.vars.popupId =
					BX.type.isNotEmptyString(this.option('windowId')) ?
					this.option('windowId') :
					this.code()+"-popup-"+Math.floor(Math.random()*10000);
			},

			getPopup: function()
			{
				if(typeof this.instances.window == 'undefined')
				{
					var baseParams = {
						zIndex : 22000,
						content : this.scope(),
						autoHide   : true,
						closeByEsc : true
					};
					var params = this.option('popupParameters');
					for(var k in params)
					{
						if(params.hasOwnProperty(k))
						{
							baseParams[k] = params[k];
						}
					}

					this.instances.window = new BX.PopupWindow(
						this.vars.popupId,
						this.vars.bindTo,
						baseParams
					);
				}

				return this.instances.window;
			},

			bindTo: function(node)
			{
				this.vars.bindTo = node;
				if(this.instances.window)
				{
					this.instances.window.setBindElement(node);
				}

				return this;
			},

			show: function(node)
			{
				if(BX.type.isElementNode(node))
				{
					this.bindTo(node);
				}

				this.control('pane').style.maxHeight = this.option('maxHeight')+'px';

				this.getPopup().show();
				this.onScrollChanged(true);

				return this;
			},

			hide: function()
			{
				if(this.instances.window)
				{
					this.getPopup().close();
				}

				return this;
			},

			onScrollChanged: function(force)
			{
				if(this.instances.window && this.instances.window.isShown())
				{
					this.callMethod(BX.Tasks.Util.ScrollPane, 'onScrollChanged', arguments);
				}
			},

			destroy: function()
			{
				this.hide();

				if(this.instances.window)
				{
					this.instances.window.close();
					this.instances.window.destroy();
					this.instances.window = null;
				}
			}
		}
	});
}