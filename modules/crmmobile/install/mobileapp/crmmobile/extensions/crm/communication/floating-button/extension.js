/**
 * @module crm/communication/floating-button
 */
jn.define('crm/communication/floating-button', (require, exports, module) => {
	const { HideOnScrollAnimator } = require('animation/hide-on-scroll');
	const { CommunicationButton } = require('crm/communication/button');
	const { Feature } = require('feature');
	const { isEmpty, isEqual } = require('utils/object');

	const isIOS = Application.getPlatform() === 'ios';
	const testId = 'CommunicationFloatingButton';
	const BUTTON_WIDTH = 108;

	/**
	 * @class CommunicationFloatingButton
	 */
	class CommunicationFloatingButton extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);

			this.uid = null;
			this.permissions = null;

			this.showed = false;
			this.animator = null;
			this.buttonRef = null;

			this.state = {
				value: {},
				ownerInfo: {},
				clientOptions: [],
				visible: true,
			};
		}

		componentDidMount()
		{
			if (isIOS && Feature.isKeyboardEventsSupported())
			{
				Keyboard.on(Keyboard.Event.WillShow, () => this.hide(true));
				Keyboard.on(Keyboard.Event.WillHide, () => this.show(true));
			}
		}

		setUid(uid)
		{
			this.uid = uid;
		}

		setPermissions(permissions)
		{
			this.permissions = permissions;
		}

		/**
		 * @public
		 * @param {Object} value
		 * @param {Object} ownerInfo
		 * @param {?Array} clientOptions
		 * @return {void}
		 */
		setValue(value, ownerInfo, clientOptions)
		{
			const {
				value: prevValue,
				ownerInfo: prevOwnerInfo,
				clientOptions: prevClientOptions,
			} = this.state;

			const prevState = {
				value: prevValue,
				ownerInfo: prevOwnerInfo,
			};

			const newState = {
				value,
				ownerInfo,
			};

			if (clientOptions)
			{
				prevState.clientOptions = prevClientOptions;
				newState.clientOptions = clientOptions;
			}

			if (!isEqual(prevState, newState))
			{
				this.setState(newState, () => !this.showed && this.show());
			}
		}

		/**
		 * @public
		 * @return {void}
		 */
		animateOnScroll(scrollParams, scrollViewHeight)
		{
			if (!this.buttonRef)
			{
				return;
			}

			this.getAnimator().animateByScroll(this.buttonRef, scrollParams, scrollViewHeight);
		}

		/**
		 * @return {HideOnScrollAnimator}
		 */
		getAnimator()
		{
			if (!this.animator)
			{
				this.animator = new HideOnScrollAnimator({ initialTopPosition: 22 });
			}

			return this.animator;
		}

		actualize()
		{
			if (this.hasEmptyValues())
			{
				return this.hide();
			}

			return this.show();
		}

		hasEmptyValues()
		{
			return Object.values(this.state.value).every((value) => isEmpty(value));
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		show()
		{
			this.showed = true;

			return this.getAnimator().show(this.buttonRef);
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		hide()
		{
			this.showed = false;

			return this.getAnimator().hide(this.buttonRef);
		}

		getCenterPosition()
		{
			if (!device)
			{
				return '50%';
			}

			const deviceCenter = device.screen.width / 2;
			const buttonCenter = BUTTON_WIDTH / 2;

			return deviceCenter - buttonCenter;
		}

		render()
		{
			const { value, ownerInfo, clientOptions } = this.state;

			return new CommunicationButton({
				...this.props,
				viewRef: (ref) => this.buttonRef = ref,
				testId,
				value,
				ownerInfo,
				clientOptions,
				uid: this.uid,
				permissions: this.permissions,
				border: true,
				horizontal: true,
				showShadow: true,
				showConnectionStubs: true,
				styles: {
					main: {
						left: this.getCenterPosition(),
						position: 'absolute',
						bottom: -100,
					},
				},
			});
		}
	}

	module.exports = { CommunicationFloatingButton };
});
