/**
 * @module layout/ui/smartphone-contact-selector/src/phone-input-box
 */
jn.define('layout/ui/smartphone-contact-selector/src/phone-input-box', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { PhoneInput, InputSize } = require('ui-system/form/inputs/phone');
	const { isPhoneNumber } = require('utils/phone');
	const { BottomSheet } = require('bottom-sheet');

	class PhoneInputBox extends LayoutComponent
	{
		static async open(props = {}) {
			return new Promise((resolve) => {
				const layoutHeight = 285;
				const parentLayout = props.parentLayout || PageManager;
				const controlInstance = new PhoneInputBox({
					...props,
				});

				const bottomSheet = new BottomSheet({
					component: controlInstance,
					titleParams: {
						text: Loc.getMessage('PHONE_INPUT_BOX_TITLE'),
						type: 'dialog',
					},
				});
				bottomSheet
					.setParentWidget(parentLayout)
					.setBackgroundColor(Color.bgSecondary.toHex())
					.setNavigationBarColor(Color.bgSecondary.toHex())
					.disableShowOnTop()
					.disableOnlyMediumPosition()
					.setMediumPositionHeight(layoutHeight)
					.enableBounce()
					.disableHorizontalSwipe()
					.enableResizeContent()
					.enableAdoptHeightByKeyboard()
					.open()
					.then((layoutWidget) => {
						controlInstance.layoutWidget = layoutWidget;
						resolve(controlInstance);
					})
					.catch(() => resolve(null));
			});
		}

		constructor(props)
		{
			super(props);
			this.inputRef = null;
			this.state = {
				pending: false,
				phone: '',
			};
		}

		componentDidMount()
		{
			this.inputRef?.focus();
		}

		render()
		{
			return Box(
				{
					testId: this.getTestId(),
					resizableByKeyboard: true,
					safeArea: {
						bottom: true,
					},
					footer: this.#renderContinueButton(),
					onClick: this.#onBoxClick,
				},
				Area(
					{
						style: {
							flex: 1,
							justifyContent: 'space-between',
						},
					},
					PhoneInput({
						testId: this.getTestId('input'),
						ref: this.#bindInputRef,
						size: InputSize.L,
						value: this.state.phone,
						onChange: this.#onChangePhone,
					}),
					Button({
						testId: `${this.testId}-open-settings-button`,
						text: Loc.getMessage('PHONE_INPUT_BOX_OPEN_SETTINGS_BUTTON_TEXT'),
						design: ButtonDesign.PLAN_ACCENT,
						size: ButtonSize.S,
						stretched: true,
						onClick: this.openSettingsButtonClick,
					}),
				),
			);
		}

		openSettingsButtonClick = () => {
			Application.openSettings();
		};

		getTestId(suffix)
		{
			const prefix = 'phone-input-box';

			return suffix ? `${prefix}_${suffix}` : prefix;
		}

		#bindInputRef = (ref) => {
			this.inputRef = ref;
		};

		#onBoxClick = () => {
			this.inputRef?.blur?.();
		};

		#onChangePhone = (phone) => {
			this.setState({
				phone,
			});
		};

		#renderContinueButton = () => {
			return BoxFooter(
				{
					safeArea: Application.getPlatform() === 'ios',
					keyboardButton: {
						testId: `${this.testId}-continue-keyboard-button`,
						text: Loc.getMessage('PHONE_INPUT_BOX_BUTTON_TEXT'),
						loading: this.state.pending,
						onClick: this.#onContinueButtonClick,
						disabled: !isPhoneNumber(this.state.phone),
					},
				},
				Button({
					testId: `${this.testId}-continue-button`,
					text: Loc.getMessage('PHONE_INPUT_BOX_BUTTON_TEXT'),
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					loading: this.state.pending,
					stretched: true,
					disabled: !isPhoneNumber(this.state.phone),
					onClick: this.#onContinueButtonClick,
				}),
			);
		};

		enableSendButtonLoadingIndicator = (enable) => {
			this.setState({
				pending: enable,
			});
		};

		close = () => {
			this.layoutWidget.close();
		};

		#onContinueButtonClick = () => {
			if (this.state.pending)
			{
				return;
			}

			this.setState({
				pending: true,
			}, () => {
				this.props.onContinue?.({
					phone: this.state.phone,
					selectorInstance: this,
				});
			});
		};
	}

	module.exports = { PhoneInputBox };
});
