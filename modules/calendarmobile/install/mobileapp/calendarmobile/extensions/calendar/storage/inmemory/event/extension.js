/**
 * @module calendar/storage/inmemory/event
 */
jn.define('calendar/storage/inmemory/event', (require, exports, module) => {
	class EventTable
	{
		constructor()
		{
			this.storage = Application.sharedStorage('calendar:events');
		}

		async get(key)
		{
			return new Promise((resolve) => {
				resolve(this.storage.get(key));
			});
		}

		set(key, value)
		{
			this.storage.set(key, value);
		}
	}

	module.exports = { EventTable: new EventTable() };
});
