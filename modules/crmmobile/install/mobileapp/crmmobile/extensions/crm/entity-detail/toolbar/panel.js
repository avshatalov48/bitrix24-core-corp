/**
 * @module crm/entity-detail/toolbar/panel
 */
jn.define('crm/entity-detail/toolbar/panel', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');
	const { FadeView } = require('animation/components/fade-view');

	/**
	 * @class ToolbarPanelWrapper
	 */
	class ToolbarPanelWrapper extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.visible = false;
			this.ref = null;
			/** @type {ToolbarContent} */
			this.toolbar = null;
		}

		/**
		 * @public
		 * @param {string} template
		 * @param {object} data
		 * @return void
		 */
		show(template, data = {})
		{
			if (this.toolbar)
			{
				this.toolbar.show(template, data);
			}
		}

		/**
		 * @public
		 * @return void
		 */
		hide()
		{
			if (this.toolbar)
			{
				this.toolbar.hide();
			}
		}

		getStyles()
		{
			const { style } = this.props;

			return mergeImmutable(defaultStyles, style);
		}

		render()
		{
			const { children, fade, ...props } = this.props;
			const styles = this.getStyles();
			const content = View(
				{
					style: styles.mainWrapper,
					clickable: false,
				},
				children && new children({
					...props,
					ref: (ref) => this.toolbar = ref,
				}),
			);

			return View(
				{
					ref: (ref) => this.ref = ref,
					style: {
						...styles.rootWrapper,
					},
					clickable: false,
				},
				fade ? new FadeView({
					visible: false,
					fadeInOnMount: true,
					style: {},
					slot: () => Shadow(
						styles.shadow,
						content,
					),
				}) : content,
			);
		}
	}

	const defaultStyles = {
		rootWrapper: {
			flex: 1,
		},
		shadow: {
			radius: 3,
			offset: {
				y: 3,
			},
			inset: {
				left: 3,
				right: 3,
			},
			style: {},
		},
		mainWrapper: {
			flex: 1,
		},
	};

	module.exports = { ToolbarPanelWrapper };
});
