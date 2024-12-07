/**
 * @module crm/entity-detail/toolbar/activity/templates/base
 */
jn.define('crm/entity-detail/toolbar/activity/templates/base', (require, exports, module) => {
	const { transition, pause, chain } = require('animation');
	const { mergeImmutable } = require('utils/object');
	const { doubleIm } = require('assets/communication');
	const { bigCross } = require('assets/common');
	const { Loc } = require('loc');
	const ICONS = {
		'Activity:OpenLine': doubleIm,
	};
	const ICON_SIZE = 26;
	const HEIGHT = 60;

	/**
	 * @class ActivityPinnedBase
	 */
	class ActivityPinnedBase extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.model = this.props.model;
			this.state.visible = false;
		}

		getTitle()
		{
			return this.model.getTitle();
		}

		getSubTitle()
		{
			return this.model.getSubTitle();
		}

		getIcon()
		{
			const type = this.model.getType();
			const icon = ICONS[type];

			if (!icon)
			{
				return null;
			}

			return icon('#FFFFFF');
		}

		getStyles()
		{
			const { style } = this.props;

			return mergeImmutable(this.getDefaultStyles(), style);
		}

		getDefaultStyles()
		{
			return defaultStyles;
		}

		handleOnActionClick()
		{
			return null;
		}

		renderContent()
		{
			const styles = this.getStyles();
			const subtitle = this.getSubTitle();

			return [
				View({
						style: styles.contentBlock,
						clickable: false,
					},
					Text({
						style: styles.title,
						text: this.getTitle(),
						numberOfLines: 1,
						ellipsize: 'end',
					}),
					subtitle && Text({
						style: styles.subtitle,
						text: subtitle || '',
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				this.renderAction(),
			];
		}

		renderAction()
		{
			const styles = this.getStyles();

			return Button({
				style: styles.openButton,
				text: Loc.getMessage('M_CRM_E_D_TOOLBAR_BUTTON_OPEN'),
				onClick: this.state.visible && this.handleOnActionClick.bind(this),
			});
		}

		renderActivityImage()
		{
			const styles = this.getStyles();

			return View({
					style: styles.imageWrapper,
					clickable: false,
				},
				Image({
					style: styles.icon,
					resizeMode: 'contain',
					svg: this.getIcon(),
				}),
			);
		}

		renderRightIcon()
		{
			const styles = this.getStyles();
			const { onHide } = this.props;

			return View(
				{
					style: styles.rightIconWrapper,
					clickable: false,
				},
				ImageButton({
					resizeMode: 'contain',
					style: styles.rightIcon,
					onClick: this.state.visible && onHide,
					svg: {
						content: bigCross('#666666'),
					},
				}));
		}

		render()
		{
			const styles = this.getStyles();
			const content = this.model.getType()
				? [
					this.renderActivityImage(),
					View(
						{
							style: styles.contentWrapper,
							clickable: false,
						},
						...this.renderContent(),
					),
					this.renderRightIcon(),
				]
				: [];

			return View({
					style: styles.mainWrapper,
					clickable: false,
					ref: (ref) => this.ref = ref,
				},
				...content,
			);
		}

		shouldHighlightOnShow()
		{
			return true;
		}

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
				backgroundColor: '#DFE0E3',
			});

			const toWhite = transition(this.ref, {
				duration: 200,
				backgroundColor: '#ffffff',
			});

			const start = () => {
				return new Promise((resolve) => {
					this.setState({
						visible: true,
					}, resolve);
				});
			};

			return chain(
				start,
				open,
				shouldHighlightOnShow && toGrey,
				shouldHighlightOnShow && pause(100),
				shouldHighlightOnShow && toWhite,
			)();
		}

		hide()
		{
			if (!this.state.visible)
			{
				return Promise.resolve();
			}

			const { animation = {} } = this.props;

			return new Promise((resolve) => {
				this.ref.animate(
					{
						...animation,
						top: -80,
						option: 'linear',
					},
					() => {
						this.setState({
							visible: false,
						}, () => {
							resolve();
						});
					},
				);
			});
		}
	}

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

	module.exports = { ActivityPinnedBase };
});