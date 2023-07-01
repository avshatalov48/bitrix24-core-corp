/**
 * @module crm/timeline/item/ui/background
 */
jn.define('crm/timeline/item/ui/background', (require, exports, module) => {
	const { transition, pause, chain } = require('animation');

	/**
	 * @class TimelineItemBackgroundLayer
	 */
	class TimelineItemBackgroundLayer extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.nodeRef = null;
			this.animating = false;
		}

		get color()
		{
			return this.props.color || '#ffffff';
		}

		get opacity()
		{
			return this.props.opacity || 1;
		}

		render()
		{
			return View(
				{
					ref: (ref) => this.nodeRef = ref,
					style: {
						position: 'absolute',
						top: 0,
						left: 0,
						right: 0,
						bottom: 0,
						backgroundColor: this.color,
						opacity: this.opacity,
					},
				},
			);
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		blink()
		{
			if (!this.nodeRef || this.animating)
			{
				return Promise.resolve();
			}

			const start = () => {
				this.animating = true;
				return Promise.resolve();
			};

			const finish = () => {
				this.animating = false;
				return Promise.resolve();
			};

			const toYellow = transition(this.nodeRef, {
				duration: 300,
				backgroundColor: '#ffe9be', // same as in kanban
				opacity: 1,
			});

			const restoreBackground = transition(this.nodeRef, {
				duration: 300,
				backgroundColor: this.color,
				opacity: this.opacity,
			});

			return chain(
				start,
				toYellow,
				pause(500),
				restoreBackground,
				finish,
			)();
		}
	}

	module.exports = { TimelineItemBackgroundLayer };
});
