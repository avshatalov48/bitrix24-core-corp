/**
 * @module spotlight
 */
jn.define('spotlight', (require, exports, module) => {

	class Spotlight
	{
		constructor(props = {})
		{
			const defaults = {
				target: '',
				delay: 200,
				text: '',
				key: 'seen_spotlight_' + props.target,
			};
			this.props = {...defaults, ...props};
		}

		show()
		{
			const seenSpotlight = Application.storage.get(this.props.key, '');
			if (seenSpotlight === true)
			{
				return;
			}

			setTimeout(() => {
				const spotlight = dialogs.createSpotlight();
				spotlight.setTarget(this.props.target);
				spotlight.setHint({text: this.props.text});
				spotlight.show();
				// set spotlight as seen
				Application.storage.set(this.props.key, true);
			}, this.props.delay);
		}
	}

	module.exports = { Spotlight };
});