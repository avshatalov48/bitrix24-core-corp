/**
 * @module crm/entity-detail/toolbar/content/templates/single-action
 */
jn.define('crm/entity-detail/toolbar/content/templates/single-action', (require, exports, module) => {
	const { ToolbarContentTemplateBase } = require('crm/entity-detail/toolbar/content/templates/base');

	const { mergeImmutable } = require('utils/object');
	const { bigCross } = require('assets/common');
	const { Loc } = require('loc');

	/**
	 * @abstract
	 * @class ToolbarContentTemplateSingleAction
	 */
	class ToolbarContentTemplateSingleAction extends ToolbarContentTemplateBase
	{
		constructor(props)
		{
			super(props);

			this.styles = mergeImmutable(defaultStyles, (this.props.style || {}));
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getTitle()
		{
			return '';
		}

		/**
		 * @abstract
		 * @return {string|null}
		 */
		getSubTitle()
		{
			return null;
		}

		/**
		 * @abstract
		 * @return {string|null}
		 */
		getPrimaryIconSvgContent()
		{
			return null;
		}

		/**
		 * @abstract
		 * @return {void}
		 */
		handlePrimaryAction()
		{}

		render()
		{
			return View(
				{
					style: this.styles.mainWrapper,
					clickable: false,
					ref: (ref) => this.ref = ref,
				},
				this.renderPrimaryIcon(),
				View(
					{
						style: this.styles.contentWrapper,
						clickable: false,
					},
					...this.renderContent(),
				),
				this.renderRightIcon(),
			);
		}

		renderPrimaryIcon()
		{
			const content = this.getPrimaryIconSvgContent();
			if (!content)
			{
				return null;
			}

			return View(
				{
					style: this.styles.imageWrapper,
					clickable: false,
				},
				Image({
					style: this.styles.icon,
					resizeMode: 'contain',
					svg: { content },
				}),
			);
		}

		renderContent()
		{
			const subtitle = this.getSubTitle();

			return [
				View(
					{
						style: this.styles.contentBlock,
						clickable: false,
					},
					Text({
						style: this.styles.title,
						text: this.getTitle(),
						numberOfLines: 1,
						ellipsize: 'end',
					}),
					subtitle && Text({
						style: this.styles.subtitle,
						text: subtitle,
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				this.renderPrimaryActionButton(),
			];
		}

		renderPrimaryActionButton()
		{
			return Button({
				style: this.styles.openButton,
				text: Loc.getMessage('M_CRM_E_D_TOOLBAR_BUTTON_OPEN'),
				onClick: () => this.state.visible && this.handlePrimaryAction(),
			});
		}

		renderRightIcon()
		{
			const { onHide } = this.props;

			if (!onHide)
			{
				return null;
			}

			return View(
				{
					style: this.styles.rightIconWrapper,
					clickable: false,
				},
				ImageButton({
					resizeMode: 'contain',
					style: this.styles.rightIcon,
					onClick: this.state.visible && onHide,
					svg: {
						content: bigCross('#6a737f'),
					},
				}),
			);
		}
	}

	const ICON_SIZE = 26;
	const HEIGHT = 60;
	const defaultStyles = {
		icon: {
			width: ICON_SIZE,
			height: ICON_SIZE,
		},
		mainWrapper: {
			flex: 1,
			flexDirection: 'row',
			alignItems: 'center',
			justifyContent: 'space-between',
			paddingVertical: 12,
			paddingRight: 12,
			paddingLeft: 20,
			borderBottomWidth: 1,
			borderBottomColor: '#DFE0E3',
			backgroundColor: '#fff',
			top: -HEIGHT,
			height: HEIGHT,
			position: 'absolute',
			width: '100%',
		},
		imageWrapper: {
			width: 34,
			height: 34,
			backgroundColor: '#2FC6F6',
			borderRadius: 30,
			alignItems: 'center',
			justifyContent: 'center',
		},
		contentWrapper: {
			flex: 1,
			flexDirection: 'row',
			alignItems: 'center',
			justifyContent: 'space-between',
			paddingLeft: 11,
			paddingRight: 8,
		},
		contentBlock: {
			flex: 1,
			flexDirection: 'column',
			marginRight: 8,
		},
		rightIconWrapper: {
			width: 33,
			height: 33,
			alignItems: 'center',
			justifyContent: 'center',
		},
		rightIcon: {
			width: 33,
			height: 33,
		},
		circle: (big, grey) => ({
			width: big ? 34 : 23,
			height: big ? 34 : 23,
			borderRadius: 30,
			backgroundColor: grey ? '#DFE0E3' : '#2FC6F6',
		}),
		title: {
			fontSize: 15,
			fontWeight: '500',
		},
		subtitle: {
			fontSize: 12,
			fontWeight: '500',
			color: '#A8ADB4',
		},
		openButton: {
			fontSize: 15,
			fontWeight: '500',
			color: '#FFFFFF',
			backgroundColor: '#00A2E8',
			height: 30,
			borderRadius: 30,
			paddingHorizontal: 10,
			maxWidth: 85,
		},
	};

	module.exports = { ToolbarContentTemplateSingleAction };
});
