/**
 * The file contains functionality that is used very often
 */

BX.namespace('Tasks');

BX.mergeEx(BX.Tasks, {

	// todo: accept also a plain string and dom element as errors argument
	// todo: make option to disable BX.reload(); by default
	alert: function(errors, params)
	{
		var p = new BX.Promise();

		if(BX.Tasks.Runtime.errorPopup == null)
		{
			BX.Tasks.Runtime.errorPopup = new BX.PopupWindow("task-error-popup", null, { lightShadow: true });
		}

		var errorPopup = BX.Tasks.Runtime.errorPopup;

		if (errorPopup === null)
		{
			errorPopup = new BX.PopupWindow("task-error-popup", null, { lightShadow: true });
		}

		errorPopup.setButtons([
			new BX.PopupWindowButton({
				text: BX.message("JS_CORE_WINDOW_CLOSE"),
				className: "",
				events: {
					click: function() {
						if (BX.type.isFunction(params))
						{
							params();
						}

						this.popupWindow.close();
						p.resolve();
					}
				}
			})
		]);

		var popupContent = "";
		for (var i = 0; i < errors.length; i++)
		{
			popupContent += BX.util.htmlspecialchars(typeof(errors[i].MESSAGE) !== "undefined" ? errors[i].MESSAGE : errors[i]) + "<br>";
		}

		var title = BX.message('TASKS_COMMON_ERROR_OCCURRED');
		if(BX.type.isPlainObject(params) && typeof params.title != 'undefined')
		{
			title = params.title;
		}

		// this feature does not work due to limitations of PopupWindow
		errorPopup.setTitleBar({content: BX.type.isElementNode(title) ? title : BX.create('div', {
			html: title
		})});
		errorPopup.setContent(
			"<div style='width: 350px;padding: 10px; font-size: 12px; color: red;'>" +
			popupContent +
			"</div>"
		);

		if (window.console && window.console.dir)
		{
			window.console.dir(errors);
		}

		errorPopup.show();

		return p;
	},
	
	confirm: function(body, callback, params)
	{
		if(!BX.type.isFunction(callback))
		{
			callback = BX.DoNothing;
		}

		params = params || {};
		params.ctx = params.ctx || this;

		var p = new BX.Promise(null, params.ctx);

		if(BX.Tasks.Runtime.confirmPopup == null)
		{
			BX.Tasks.Runtime.confirmPopup = new BX.PopupWindow(
				"task-confirm-popup",
				null,
				{
					zIndex : 22000,
					overlay : { opacity: 50 },
					content : '',
					autoHide   : false,
					closeByEsc : false
				}
			);
		}

		var disposable = params.isDisposable && params.id && ('hintManager' in BX.Tasks.Util);
		var cb = null;
		var buttonSet = params.buttonSet || [
				{text: BX.message('JS_CORE_WINDOW_CONTINUE'), type: 'green', code: 'continue', default: true}
			];

		if(disposable)
		{
			if(BX.Tasks.Util.hintManager.wasDisposed(params.id))
			{
				var def = buttonSet.filter(function(item){
					return item.default;
				});
				def = def[0] || buttonSet[0];

				p.fulfill(def.code); // virtually choose the default one
				return p;
			}
			else
			{
				cb = BX.create("P",
					{
						style: {margin: '10px 20px 0 0'},
						children: [
							BX.create("LABEL",
								{
									children: [
										BX.create("INPUT", {
											props: {type: "checkbox", id: 'bx-tasks-disposable-'+params.id}
										}),
										BX.create("SPAN", {
											style: {color: 'gray'},
											text: BX.message('TASKS_COMMON_DONT_ASK_AGAIN')
										})
									]
								}
							)
						]
					}
				);
			}
		}

		var buttons = [];
		BX.Tasks.each(buttonSet, function(button){

			(function(buttons, button, params, disposable, callback){
				buttons.push(new BX.PopupWindowButton({
					text: button.text,
					className: button.type == 'red' ? 'popup-window-button-decline' : 'popup-window-button-accept',
					events: {
						click: function () {

							callback.apply(params.ctx, [true]);
							this.popupWindow.close();

							if (disposable && BX('bx-tasks-disposable-' + params.id).checked) {
								BX.Tasks.Util.hintManager.disable(params.id);
							}

							p.fulfill(button.code);

							delete(params);
						}
					}
				}));
			})(buttons, button, params, disposable, callback);
		});

		buttons.push(new BX.PopupWindowButtonLink({
			text: BX.message('JS_CORE_WINDOW_CANCEL'),
			events : {
				click : function(){
					callback.apply(params.ctx, [false]);
					this.popupWindow.close();

					p.reject();

					delete(params);
				}
			}
		}));

		// each time "callback" variable will be different, so we can not cache buttons
		BX.Tasks.Runtime.confirmPopup.setButtons(buttons);

		if(typeof params.title != 'undefined')
		{
			// this feature does not work due to limitations of PopupWindow
			BX.Tasks.Runtime.confirmPopup.setTitleBar(BX.type.isElementNode(params.title) ? params.title : BX.create('div', {
				html: params.title
			}));
		}
		body = BX.create(
			'div',
			{
				style: {padding: '16px 12px', maxWidth: '400px', maxHeight: '400px', overflow: 'hidden'},
				html : BX.type.isElementNode(body) ? body.outerHTML : body.toString()
			}
		);
		if(cb)
		{
			BX.append(cb, body);
		}

		BX.Tasks.Runtime.confirmPopup.setContent(body.outerHTML);
		BX.Tasks.Runtime.confirmPopup.show();

		return p;
	},

	confirmDelete: function(entityName)
	{
		entityName = (entityName || '').toString();
		entityName = entityName.substr(0, 1).toLowerCase()+entityName.substr(1); // cant do just substr() because of possible different translations

		return this.confirm(BX.message('TASKS_COMMON_CONFIRM_DELETE').replace('#ENTITY_NAME#', entityName));
	},

	passCtx: function(f, ctx)
	{
		return function()
		{
			var args = Array.prototype.slice.call(arguments);
			args.unshift(this); // this is a ctx of the node event happened on
			return f.apply(ctx, args);
		}
	},

	each: function(data, cb, ctx)
	{
		var k;
		ctx = ctx || this;

		if(BX.type.isArray(data) || (data instanceof Object && 'length' in data)) // array or itrable object
		{
			for(k = 0; k < data.length; k++)
			{
				if(data.hasOwnProperty(k))
				{
					if(cb.apply(ctx, [data[k], k]) === false)
					{
						break;
					}
				}
			}
		}
		else if(BX.type.isPlainObject(data))
		{
			for(k in data)
			{
				if(data.hasOwnProperty(k))
				{
					if(cb.apply(ctx, [data[k], k]) === false)
					{
						break;
					}
				}
			}
		}
	},

	deReference: function(name, obj)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return null;
		}

		name = name.split('.');
		var len = name.length;
		var top = obj;
		for(var k = 0; k < len; k++)
		{
			if(name.hasOwnProperty(k))
			{
				if(typeof top == 'undefined' || top === null)
				{
					return null;
				}

				if(!BX.type.isNotEmptyString(name[k]))
				{
					return null;
				}

				top = top[name[k].trim()];
			}
			else
			{
				return null;
			}
		}

		return top;
	}
});

if(typeof BX.Tasks.Runtime == 'undefined')
{
	BX.Tasks.Runtime = {
		errorPopup: null,
		confirmPopup: null
	};
}
