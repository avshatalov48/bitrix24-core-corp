/**
 * @module intranet/user-mini-profile
 */
jn.define('intranet/user-mini-profile', (require, exports, module) => {
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { Color, Indent } = require('tokens');
	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { Loc } = require('loc');
	const { PortalLogo } = require('intranet/portal-logo');
	const { UserMiniProfileForm } = require('intranet/user-mini-profile-form');
	const { H3 } = require('ui-system/typography/heading');
	const { PureComponent } = require('layout/pure-component');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Haptics } = require('haptics');

	class UserMiniProfile extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.form = null;

			this.state = {
				logoLoaded: false,
				profileLoaded: false,
			};
		}

		componentWillUnmount()
		{
			BX.postComponentEvent('userMiniProfileClosed', null);
		}

		getProfileForm = () => {
			return new UserMiniProfileForm({
				ref: this.bindProfileFormRef,
				scrollToInput: this.scrollToInput,
				profileData: this.props.profileData,
			});
		};

		bindProfileFormRef = (ref) => {
			this.formRef = ref;
		};

		getPortalLogoView = () => {
			return new PortalLogo({
				portalLogo: this.props.portalLogoData,
			});
		};

		getTitle()
		{
			return H3({
				color: Color.base1,
				style: {
					alignSelf: 'center',
					marginBottom: 24,
				},
				text: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_TITLE') ?? '',
			});
		}

		onContinue = () => {
			this.formRef?.onContinue();
		};

		getSubmitButton()
		{
			return Button({
				size: ButtonSize.L,
				design: ButtonDesign.FILLED,
				text: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_BUTTON_SUBMIT') ?? '',
				border: true,
				onClick: this.onSubmit,
				testId: 'USER_MINI_PROFILE_BUTTON_SUBMIT',
				stretched: true,
				loading: this.state.isLoading,
			});
		}

		onSubmit = () => {
			this.setState({
				isLoading: true,
			});
			this.formRef?.submitForm()
				.then(() => {
					Haptics.notifySuccess();
					layout.close();
				})
				.catch(() => {
					this.setState({
						isLoading: false,
					});
				});
		};

		renderButtons() {
			return BoxFooter(
				{
					safeArea: true,
					keyboardButton: {
						text: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_BUTTON_CONTINUE') ?? '',
						onClick: this.onContinue,
					},
				},
				this.getSubmitButton(),
			);
		}

		bindScrollViewRef = (ref) => {
			this.scrollViewRef = ref;
		};

		scrollToInput = (input) => {
			const { y } = this.scrollViewRef.getPosition(input.contentFieldRef);
			const positionY = Application.getPlatform() === 'ios' ? y - 20 : y - 140;
			this.scrollViewRef.scrollTo({ y: positionY, animated: true });
		};

		render()
		{
			const imageUri = `${currentDomain}/bitrix/mobileapp/intranetmobile/components/intranet/user-mini-profile/images/confetti-background.svg`;

			return Box(
				{
					footer: this.renderButtons(),
					withScroll: true,
					resizableByKeyboard: true,
					style: {
						position: 'relative',
					},
					scrollProps: {
						ref: this.bindScrollViewRef,
					},
				},
				Image({
					resizeMode: 'contain',
					style: {
						width: '100%',
						height: 127,
						position: 'absolute',
					},
					svg: {
						uri: encodeURI(imageUri),
					},
				}),
				Area(
					{},
					View(
						{
							style: {
								paddingTop: Indent.XL3.toNumber(),
							},
						},
						this.getPortalLogoView(),
						this.getTitle(),
						this.getProfileForm(),
					),
				),
			);
		}
	}

	module.exports = {
		UserMiniProfile,
	};
});

(() => {
	const { UserMiniProfile } = jn.require('intranet/user-mini-profile');

	BX.onViewLoaded(() => {
		const profileData = BX.componentParameters.get('profileDataParams', null);
		const portalLogoData = BX.componentParameters.get('portalLogoParams', null);

		layout.showComponent(
			new UserMiniProfile({
				profileData,
				portalLogoData,
			}),
		);
	});
})();
