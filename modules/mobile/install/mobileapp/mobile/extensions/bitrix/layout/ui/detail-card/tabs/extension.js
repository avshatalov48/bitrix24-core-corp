(() => {
	/**
	 * @class BaseTab
	 */
	class BaseTab
	{
		constructor(props)
		{
			this.id = props.id;
			this.title = props.title;
			this.selectable = props.selectable;
			this.desktopUrl = props.desktopUrl || '';
			this.type = props.type;
			this.payload = props.payload || {};
		}

		on(eventName, callback)
		{
			BX.addCustomEvent(eventName, callback);

			return this;
		}

		emit(eventName, args)
		{
			BX.postComponentEvent(eventName, args);
		}

		/**
		 * @returns {Promise.<Object>}
		 */
		getData()
		{
			return Promise.resolve();
		}

		/**
		 * @returns {Promise.<boolean|Array>}
		 */
		validate()
		{
			return new Promise((resolve, reject) => resolve(true));
		}

		render(result, refresh)
		{
			throw new Error('Method {render} must be implemented');
		}
	}

	this.BaseTab = BaseTab;

})();
