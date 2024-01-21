/**
 * @module calendar/storage/sqlite/event
 */
jn.define('calendar/storage/sqlite/event', (require, exports, module) => {
	// eslint-disable-next-line no-undef
	include('sqllite');

	class EventTable extends DatabaseTable
	{
		constructor()
		{
			const fields = [
				{ name: 'key', type: 'text', unique: true, index: true },
				{ name: 'value', type: 'text' },
			];
			super('events', fields);
		}

		async get(key)
		// eslint-disable-next-line no-empty-function
		{}

		set(key, value)
		{}
	}

	module.exports = { EventTable };
});
