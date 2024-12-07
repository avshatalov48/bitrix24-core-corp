/**
 * @module layout/ui/context-menu/section
 */
jn.define('layout/ui/context-menu/section', (require, exports, module) => {
	const { Color, Indent, Component } = require('tokens');
	const { Icon } = require('assets/icons');
	const { Area } = require('ui-system/layout/area');
	const { Link4 } = require('ui-system/blocks/link');
	const { Text4 } = require('ui-system/typography/text');
	const { PropTypes } = require('utils/validation');

	const SECTION_TITLE_HEIGHT = 38;
	const SECTION_DEFAULT = 'default';
	const SECTION_SERVICE = 'service';

	/**
	 * @param {string} testId
	 * @param {string | number} [id]
	 * @param {string} title
	 * @param {Object} [titleAction]
	 * @param {ContextMenuItemProps[]} actions
	 * @param {Function} [renderAction]
	 * @param {Function} [closeHandler]
	 * @class ContextMenuSection
	 */
	class ContextMenuSection extends LayoutComponent
	{
		static create(props)
		{
			return new ContextMenuSection(props);
		}

		render()
		{
			return Area(
				{
					isFirst: !this.getTitle(),
					title: this.getTitle(),
					excludePaddingSide: {
						horizontal: true,
						bottom: true,
					},
				},
				this.renderTitleAction(),
				...this.renderActions(),
			);
		}

		getTitle()
		{
			const { title, titleAction } = this.props;

			if (titleAction)
			{
				return null;
			}

			return title;
		}

		/**
		 * @deprecated
		 * @returns {Link|null}
		 */
		renderTitleAction()
		{
			const svgIcons = {
				add: Icon.PLUS,
			};
			const { title, titleAction, testId } = this.props;

			if (!titleAction)
			{
				return null;
			}

			const { iconType, text, action } = titleAction;

			const icon = svgIcons[iconType] || null;

			return View(
				{
					style: {
						width: '100%',
						alignItems: 'center',
						flexDirection: 'row',
						paddingVertical: Indent.L.toNumber(),
					},
				},
				View(
					{
						style: {
							flex: 1,
							alignItems: 'center',
							flexDirection: 'row',
							marginHorizontal: Component.areaPaddingLr.toNumber(),
						},
					},
					Text4({
						text: title,
						color: Color.base4,
						style: {
							marginRight: Indent.XS2.toNumber(),
						},
					}),
					Link4({
						text,
						testId: `${testId}_title_action`,
						rightIcon: icon,
						useInAppLink: false,
						onClick: this.handleTitleActionClick(action),
					}),
				),
			);
		}

		/**
		 * @deprecated
		 * @returns {Link|null}
		 */
		handleTitleActionClick = (callback) => () => {
			const { closeHandler } = this.props;

			let promise = callback();

			if (!(promise instanceof Promise))
			{
				promise = Promise.resolve();
			}

			promise.then(({ closeMenu = true, closeCallback } = {}) => {
				if (closeMenu && closeHandler)
				{
					closeHandler(closeCallback);
				}
			}).catch(console.error);
		};

		renderActions()
		{
			const { renderAction } = this.props;

			if (!renderAction)
			{
				return [];
			}

			const { actions } = this.props;

			return actions.map((action, i) => renderAction(action, { divider: actions.length - 1 !== i }));
		}

		static getDefaultSectionName()
		{
			return SECTION_DEFAULT;
		}

		static getServiceSectionName()
		{
			return SECTION_SERVICE;
		}

		static getHeight()
		{
			return SECTION_TITLE_HEIGHT;
		}

		static getIndentBetweenSections()
		{
			return Component.areaPaddingLr.toNumber();
		}
	}

	ContextMenuSection.propTypes = {
		testId: PropTypes.string.isRequired,
		id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		titleAction: PropTypes.object,
		title: PropTypes.string,
		actions: PropTypes.array,
		renderAction: PropTypes.func,
		closeHandler: PropTypes.func,
	};

	module.exports = {
		ContextMenuSection,
	};
});

(() => {
	const { ContextMenuSection } = jn.require('layout/ui/context-menu/section');

	this.ContextMenuSection = ContextMenuSection;
})();
