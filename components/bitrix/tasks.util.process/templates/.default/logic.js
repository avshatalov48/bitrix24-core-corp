'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.UtilProcess != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.UtilProcess = BX.Tasks.Component.extend({
		sys: {
			code: 'util-proc'
		},
		methods: {

			bindEvents: function()
			{
				this.bindControlThis('start', 'click', this.startProcess);
				this.bindControlThis('hide-notification', 'click', this.onHideNotification);
			},

			startProcess: function()
			{
				this.getPopup().show().percent(0);
				this.getIterator().start();
			},

			cancelProcess: function()
			{
				if(!this.vars.completed)
				{
					this.getIterator().stop();
				}
				else
				{
					this.hideNotification();
				}

				this.getPopup().hide();
			},

			onHideNotification: function()
			{
				BX.Tasks.confirm(BX.message('TASKS_TUP_WARNING')).then(BX.delegate(function(){
					return this.callRemote('this.setConverted');
				}, this)).then(BX.delegate(function(){
					return this.hideNotification();
				}, this));
			},

			hideNotification: function()
			{
				return BX.Tasks.Util.fadeSlideToggleByClass(this.control('notification-wrap'));
			},

			onHit: function(p, data)
			{
				var percent = data.PERCENT;

				this.getPopup().percent(percent);

				if(percent >= 100)
				{
					this.getPopup().showSuccess();
					this.vars.completed = true;
					p.reject(); // completed
				}
				else
				{
					p.fulfill();
				}
			},

			onStop: function(reason)
			{
				if(reason) // if there was a reason
				{
					this.getPopup().showError(reason);
				}
			},

			getPopup: function()
			{
				return this.subInstance('popup', function(){

					var win = new BX.Tasks.Component.UtilProcess.Popup({
						scope: this.control('popup-content'),
						title: BX.message('TASKS_TUP_POPUP_TITLE')
					});
					win.bindEvent('cancel', BX.delegate(this.cancelProcess, this));

					return win;
				});
			},

			getIterator: function()
			{
				return this.subInstance('iterator', function(){
					var iterator = new BX.Tasks.Util.Query.Iterator({
						url: this.option('url'),
						handler: 'this.doStep'
					});

					iterator.bindEvent('hit', BX.delegate(this.onHit, this));
					iterator.bindEvent('stop', BX.delegate(this.onStop, this));

					return iterator;
				});
			}
		}
	});

	BX.Tasks.Component.UtilProcess.Popup = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'progress-popup'
		},
		options: {
			title: 'Some process',
			controlBind: 'class'
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				this.vars.popupId =
					BX.type.isNotEmptyString(this.option('windowId')) ?
						this.option('windowId') :
					this.code()+"-popup-"+Math.floor(Math.random()*10000);
			},

			show: function()
			{
				this.getPopup().show();
				return this;
			},

			hide: function()
			{
				this.getPopup().close();
				return this;
			},

			percent: function(value)
			{
				if(value < 0 || value > 100)
				{
					return;
				}

				if(value == 0)
				{
					this.hideSuccess();
					this.hideError();
				}

				value = parseInt(value)+'%';

				this.control('percent').innerHTML = value;
				this.control('fill').style.width = value;

				return this;
			},

			showSuccess: function()
			{
				BX.removeClass(this.control('success'), 'no-display');
				this.setButtonText(BX.message('TASKS_COMMON_CLOSE'));

				return this;
			},

			hideSuccess: function()
			{
				BX.addClass(this.control('success'), 'no-display');
				this.setButtonText(BX.message('JS_CORE_WINDOW_CANCEL'));

				return this;
			},

			hideError: function()
			{
				BX.addClass(this.control('error'), 'no-display');
				this.setButtonText(BX.message('JS_CORE_WINDOW_CANCEL'));

				return this;
			},

			showError: function(errors)
			{
				this.control('error').innerHTML = errors.getMessages(true).join('<br />');
				BX.removeClass(this.control('error'), 'no-display');
				this.setButtonText(BX.message('TASKS_COMMON_CLOSE'));

				return this;
			},

			setButtonText: function(text)
			{
				var btn = this.subInstance('cancelBtn');
				if(btn)
				{
					btn.buttonNode.innerHTML = text;
				}
			},

			getPopup: function()
			{
				return this.subInstance('popup', function(){

					var win = new BX.PopupWindow(
						this.vars.popupId,
						null,
						{
							zIndex : 22000,
							content : this.scope(),
							autoHide   : false,
							closeByEsc : false,
							overlay: true,
							titleBar: {content: BX.create('div', {
								html: '<span class="popup-window-titlebar-text">'+this.option('title')+'</span>'
							})}
						}
					);

					win.setButtons([
						this.subInstance('cancelBtn', function(){
							return new BX.PopupWindowButtonLink({
								text: BX.message('JS_CORE_WINDOW_CANCEL'),
								events : {
									click : BX.delegate(function(){
										this.fireEvent('cancel');
									}, this)
								}
							});
						})
					]);

					return win;
				});
			}
		}
	})

}).call(this);