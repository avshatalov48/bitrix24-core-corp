/**
 * @module animation/components/blink-view
 */
jn.define('animation/components/blink-view', (require, exports, module) => {

	const { isObjectLike } = require('utils/object');

	/**
	 * @class BlinkView
	 */
	class BlinkView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.containerRef = null;

			this.state = this.buildState(props);
		}

		buildState(props)
		{
			return {
				hidden: false,
				data: props.data,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = this.buildState(props);
		}

		get animation()
		{
			const settings = isObjectLike(this.props.animation) ? this.props.animation : {};
			const optional = (value, defaultValue) => typeof value === 'undefined' ? defaultValue : value;

			return {
				delay: optional(settings.delay, 0),
				fadeOutDuration: optional(settings.fadeOutDuration, 200),
				fadeInDuration: optional(settings.fadeInDuration, 300),
			};
		}

		render()
		{
			return View(
				{
					ref: (ref) => this.containerRef = ref,
					style: {
						opacity: this.state.hidden ? 0 : 1,
						...this.props.style,
					}
				},
				this.renderSlot(),
			);
		}

		renderSlot()
		{
			if (this.props.slot)
			{
				return this.props.slot(this.state.data);
			}
			return null;
		}

		/**
		 * @public
		 * @param {*} data
		 * @returns {Promise}
		 */
		blink(data)
		{
			if (!this.containerRef)
			{
				return Promise.resolve();
			}

			if (typeof data === 'undefined')
			{
				data = this.state.data;
			}

			const fadeOut = () => new Promise((resolve) => this.containerRef.animate({
				delay: this.animation.delay,
				duration: this.animation.fadeOutDuration,
				opacity: 0,
			}, resolve));

			const fadeIn = () => new Promise((resolve) => this.containerRef.animate({
				duration: this.animation.fadeInDuration,
				opacity: 1,
			}, resolve));

			const setState = (nextState) => new Promise((resolve) => this.setState(nextState, resolve));

			return Promise.resolve()
				.then(() => fadeOut())
				.then(() => setState({data, hidden: true}))
				.then(() => fadeIn())
				.then(() => setState({hidden: false}));
		}
	}

	module.exports = { BlinkView };

});