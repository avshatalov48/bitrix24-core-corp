(() => {
	/**
	 * @typedef {{check: (function((String|Array)): T), notify: (function(intent:String, event:String):void)}} MobileIntent
	 */

	/** @var  {MobileIntent} mobileIntent */
	let mobileIntent = {
		/**
		 * @param {String|Array} data
		 * @return {*}
		 */
		check: data => {
			let targetIntents;
			if (Array.isArray(data))
			{
				targetIntents = data;
			}
			else
			{
				targetIntents = [data]
			}
			return targetIntents.find((name, index, intents) => {
				({intent} = Application.getLastNotification(name))
				return intents.includes(intent)
			})
		},
		notify: (data, eventName) => {
			let intent = mobileIntent.check(data)
			if (intent)  {
				analytics.send(eventName, {type: intent}, ["fbonly"])
			}
		}
	}

	jnexport([mobileIntent, 'mobileIntent'])
})();