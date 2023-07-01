/**
 * @module crm/entity-detail/toolbar/content/templates/base
 */
jn.define('crm/entity-detail/toolbar/content/templates/base', (require, exports, module) => {
	const { transition, pause, chain } = require('animation');

	/**
	 * @abstract
	 * @class ToolbarContentTemplateBase
	 */
	class ToolbarContentTemplateBase extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				visible: false,
			};

			/** @type {LayoutComponent|null} */
			this.ref = null;
		}

		/**
		 * @abstract
		 * @return {LayoutComponent}
		 */
		render()
		{
			return View({
				ref: (ref) => this.ref = ref,
			});
		}

		/**
		 * @abstract
		 * @return {boolean}
		 */
		shouldHighlightOnShow()
		{
			return true;
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		show()
		{
			const { animation = {} } = this.props;
			const shouldHighlightOnShow = this.shouldHighlightOnShow() && !this.state.visible;

			const open = transition(this.ref, {
				...animation,
				top: 0,
				option: 'linear',
			});

			const toGrey = transition(this.ref, {
				duration: 200,
				backgroundColor: '#dfe0e3',
			});

			const toWhite = transition(this.ref, {
				duration: 200,
				backgroundColor: '#ffffff',
			});

			const start = () => new Promise((resolve) => this.setState({ visible: true }, resolve));

			const none = () => Promise.resolve();

			return chain(
				start,
				open,
				shouldHighlightOnShow ? toGrey : none,
				shouldHighlightOnShow ? pause(100) : none,
				shouldHighlightOnShow ? toWhite : none,
			)();
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		hide()
		{
			if (!this.state.visible)
			{
				return Promise.resolve();
			}

			let { animation = {} } = this.props;
			animation = {
				...animation,
				top: -80,
				option: 'linear',
			};

			return new Promise((resolve) => {
				this.ref.animate(
					animation,
					() => this.setState({ visible: false }, resolve),
				);
			});
		}
	}

	module.exports = { ToolbarContentTemplateBase };
});
