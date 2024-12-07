jn.define('intent', (require, exports, module) => {
	/**
	 * @typedef {{addHandler: Intent.addHandler, handlers: *[], check: (function(*): *), execute:  (function(): *), notify: (function(data:String, eventName:String):void)}} MobileIntent
	 */
	const Intent = {
		addHandler: (func) => { Intent.handlers.push(func); },
		handlers: [],
		execute: () => {
			BX.onCustomEvent('onIntentHandle', [Intent]);
			Intent.handlers.forEach((handler) => handler.call());
			Intent.handlers = [];
		},
		notify: (data, eventName) => {
			const intent = Intent.check(data);
			if (intent)
			{
				analytics.send(eventName, { type: intent }, ['fbonly']);
			}
		},
		check: (data) => {
			let targetIntents;
			if (Array.isArray(data))
			{
				targetIntents = data;
			}
			else
			{
				targetIntents = [data];
			}

			return targetIntents.find((name) => {
				const { intent } = Application.getLastNotification(name);

				return name === intent;
			});
		},
	};

	module.exports = Intent;
});
