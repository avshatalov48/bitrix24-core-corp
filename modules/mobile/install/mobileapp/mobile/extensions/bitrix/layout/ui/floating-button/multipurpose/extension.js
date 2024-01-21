/**
 * @module layout/ui/floating-button/multipurpose
 */
jn.define('layout/ui/floating-button/multipurpose', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Haptics } = require('haptics');
	const { md5 } = require('utils/hash');
	const { withPressed } = require('utils/color');
	const { isObjectLike } = require('utils/object');

	const defaultAction = {
		id: 'default',
		icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.875 0H6.125V6.125H0V7.875H6.125V14H7.875V7.875H14V6.125H7.875V0Z" fill="white"/></svg>',
		hint: null,
	};

	const HintMode = {
		ONCE: 'once',
		ALWAYS: 'always',
		NEVER: 'never',
	};

	/**
	 * @class MultipurposeFloatingButton
	 */
	class MultipurposeFloatingButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				actionIndex: this.initialActionIndex,
			};
		}

		get initialActionIndex()
		{
			let actionIndex = 0;
			if (this.props.rememberSelected)
			{
				const lastUsedAction = this.lastUsedAction;
				const lastUsedActionIndex = this.actions.findIndex((item) => item.id === lastUsedAction);
				if (lastUsedActionIndex > -1)
				{
					actionIndex = lastUsedActionIndex;
				}
			}

			return actionIndex;
		}

		get lastUsedAction()
		{
			return this.cache.get('last_used_action');
		}

		set lastUsedAction(value)
		{
			this.cache.set('last_used_action', value);
		}

		get cache()
		{
			const cacheId = this.props.cacheId || md5(this.actions);
			const prefix = 'MultipurposeFloatingButton';
			const storageId = `${prefix}_${cacheId}`;

			return Application.storageById(storageId);
		}

		get actions()
		{
			return this.props.actions || [];
		}

		get action()
		{
			return this.actions[this.state.actionIndex] || defaultAction;
		}

		get position()
		{
			return this.props.position || {};
		}

		get hintMode()
		{
			const allowedValues = Object.values(HintMode);
			const defaultValue = HintMode.ONCE;

			if (allowedValues.includes(this.props.hintMode))
			{
				return this.props.hintMode;
			}

			return defaultValue;
		}

		get vibrationEnabled()
		{
			return BX.prop.getBoolean(this.props, 'vibrationEnabled', true);
		}

		componentDidMount()
		{
			this.showSpotlightOnMounted();
		}

		render()
		{
			return View(
				{
					testId: this.props.testId,
					style: { ...Styles.container, ...this.position },
				},
				Shadow(
					{
						radius: 2,
						color: AppTheme.colors.base5,
						offset: {
							x: 0,
							y: 0,
						},
						style: Styles.shadow,
					},
					View(
						{
							style: Styles.button,
							onClick: () => this.onClick(),
							onLongClick: () => this.toggleAction(),
						},
						Image({
							style: Styles.icon(this.action.icon),
							resizeMode: 'cover',
							svg: {
								content: isObjectLike(this.action.icon) ? this.action.icon.content : this.action.icon,
							},
						}),
					),
				),
			);
		}

		onClick()
		{
			if (this.needToDisplayHint())
			{
				this.showHintOnClick();
			}

			if (this.props.onClick)
			{
				this.props.onClick(this.action.id, this.state.actionIndex);
			}
		}

		toggleAction()
		{
			if (this.actions.length === 0)
			{
				return;
			}

			const mutate = (oldState) => ({ actionIndex: next(oldState.actionIndex) });

			const next = (prev) => (this.actions[prev + 1] ? prev + 1 : 0);

			const finalize = () => {
				this.vibrate();
				this.lastUsedAction = this.action.id;
			};

			this.setState(mutate, finalize);
		}

		vibrate()
		{
			if (this.vibrationEnabled)
			{
				Haptics.impactLight();
			}
		}

		needToDisplayHint()
		{
			if (!this.action.hint || this.hintMode === HintMode.NEVER)
			{
				return false;
			}

			let displayAllowed = true;
			const cacheKey = `hint_shown_${this.action.id}`;

			if (this.hintMode === HintMode.ONCE)
			{
				const alreadyShown = this.cache.getBoolean(cacheKey, false);
				displayAllowed = !alreadyShown;
			}

			if (displayAllowed)
			{
				this.cache.setBoolean(cacheKey, true);
			}

			return displayAllowed;
		}

		showSpotlightOnMounted()
		{
			// You can display some introduction about button mechanics here.
		}

		showHintOnClick()
		{
			// You can display some hint for current action here.
		}
	}

	const isAndroid = Application.getPlatform() === 'android';

	const Styles = {
		container: {
			position: 'absolute',
			right: isAndroid ? 14 : 13,
			bottom: isAndroid ? 14 : 13,
		},
		shadow: {
			borderRadius: 31,
		},
		button: {
			height: isAndroid ? 56 : 60,
			width: isAndroid ? 56 : 60,
			borderRadius: 30,
			backgroundColor: withPressed(AppTheme.colors.accentBrandBlue),
			justifyContent: 'center',
			alignItems: 'center',
		},
		icon: (props) => {
			const width = isObjectLike(props) && props.width ? props.width : 14;
			const height = isObjectLike(props) && props.height ? props.height : 14;

			return {
				width,
				height,
			};
		},
	};

	module.exports = { MultipurposeFloatingButton };
});
