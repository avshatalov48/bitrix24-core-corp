/**
 * @module lists/entity-detail/tab
*/
jn.define('lists/entity-detail/tab', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { EventEmitter } = require('event-emitter');
	class Tab extends PureComponent
	{
		static create(props)
		{
			return new this(props);
		}

		constructor(props) {
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.iBlock = props.iBlock || {};
			this.socNetGroupId = props.socNetGroupId || 0;

			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
		}

		get layout()
		{
			return this.props.layout || layout || {};
		}

		load()
		{}

		render()
		{
			return View();
		}

		validate()
		{
			return new Promise((resolve, reject) => {
				resolve(true);
			});
		}

		getData()
		{
			return new Promise((resolve, reject) => {
				resolve({});
			});
		}

		setResult(result)
		{
			return new Promise((resolve, reject) => {
				resolve();
			});
		}
	}

	module.exports = { Tab };
});
