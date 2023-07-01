/**
 * @module animation/components/fade-view
 */
jn.define('animation/components/fade-view', (require, exports, module) => {

	/**
	 * @class FadeView
	 */
	class FadeView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.containerRef = null;

			this.visible = BX.prop.getBoolean(props, 'visible', true);
		}

		get animation()
		{
			const settings = BX.prop.getObject(this.props, 'animation', {});

			return {
				delay: BX.prop.getInteger(settings, 'delay', 0),
				duration: BX.prop.getInteger(settings, 'duration', 300),
			};
		}

		componentDidMount()
		{
			const fadeInOnMount = BX.prop.getBoolean(this.props, 'fadeInOnMount', false);
			const fadeOutOnMount = BX.prop.getBoolean(this.props, 'fadeOutOnMount', false);

			// android hack: we need small delay, to ensure this.containerRef is available
			setTimeout(() => {
				if (fadeInOnMount)
				{
					void this.fadeIn();
				}
				else if (fadeOutOnMount)
				{
					void this.fadeOut();
				}
			}, 50);
		}

		render()
		{
			return View(
				{
					ref: (ref) => this.containerRef = ref,
					style: {
						opacity: this.isVisible() ? 1 : 0,
						...this.props.style,
					},
					clickable: false,
				},
				this.renderSlot(),
			);
		}

		renderSlot()
		{
			if (this.props.slot)
			{
				return this.props.slot();
			}
			return null;
		}

		/**
		 * @public
		 * @param {object} animationSettings
		 * @returns {Promise}
		 */
		fadeIn(animationSettings = {})
		{
			const options = { opacity: 1, ...animationSettings };
			const enableVisibility = () => this.visible = true;

			return this.animate(options, enableVisibility);
		}

		/**
		 * @public
		 * @param {object} animationSettings
		 * @returns {Promise}
		 */
		fadeOut(animationSettings = {})
		{
			const options = { opacity: 0, ...animationSettings };
			const disableVisibility = () => this.visible = false;

			return this.animate(options, disableVisibility);
		}

		/**
		 * @public
		 * @param {object} animationSettings
		 * @returns {Promise}
		 */
		toggle(animationSettings = {})
		{
			return this.isVisible() ? this.fadeOut(animationSettings) : this.fadeIn(animationSettings);
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isVisible()
		{
			return this.visible;
		}

		/**
		 * @private
		 * @param {object} settings
		 * @param {function} callback
		 * @returns {Promise}
		 */
		animate(settings = {}, callback)
		{
			if (!this.containerRef)
			{
				return Promise.resolve();
			}

			const options = {
				delay: this.animation.delay,
				duration: this.animation.duration,
				...settings,
			};

			return new Promise(resolve => this.containerRef.animate(options, () => {
				if (callback)
				{
					callback();
				}
				this.onAnimationComplete();
				resolve();
			}));
		}

		/**
		 * @private
		 */
		onAnimationComplete()
		{
			if (this.props.onAnimationComplete)
			{
				this.props.onAnimationComplete(this);
			}
		}
	}

	module.exports = { FadeView };

});