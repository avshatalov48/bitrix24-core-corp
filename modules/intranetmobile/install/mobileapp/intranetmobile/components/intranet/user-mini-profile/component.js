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
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { PureComponent } = require('layout/pure-component');
	const { isEmpty } = require('utils/object');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');

	class UserMiniProfile extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.form = null;

			this.state = {
				logoLoaded: false,
				profileLoaded: false,
				primaryFieldSelected: false,
			};
		}

		fetchPortalLogo()
		{
			PortalLogo.getPortalLogo()
				.then((result) => {
					if (!result?.answer?.result)
					{
						return;
					}

					const portalLogo = {
						title: result.answer.result.title ?? '',
						logo24: result.answer.result.logo24 ?? '',
						logo: result.answer.result.logo ? currentDomain + (result.answer.result.logo.src ?? '') : '',
						defaultLogo: result.answer.result.defaultLogo ?? [],
					};

					this.setState({
						logoLoaded: true,
						portalLogo,
					});
				})
				.catch(console.error);
		}

		fetchUserCurrent()
		{
			BX.rest.callMethod('user.current')
				.then((result) => {
					if (!result?.answer?.result)
					{
						return;
					}

					const { ID, NAME, LAST_NAME, PERSONAL_MOBILE, EMAIL, PERSONAL_PHOTO } = result.answer.result;

					this.setState({
						profileLoaded: true,
						profile: {
							profileData: { ID, NAME, LAST_NAME, PERSONAL_MOBILE, EMAIL },
							photo: PERSONAL_PHOTO,
						},
					});
				})
				.catch(console.error);
		}

		getProfileForm()
		{
			const { profile } = this.state;

			if (isEmpty(profile))
			{
				return null;
			}

			return new UserMiniProfileForm({
				...profile,
				ref: this.bindProfileFormRef,
				scrollToInput: this.scrollToInput,
			});
		}

		bindProfileFormRef = (ref) => {
			this.formRef = ref;
		};

		getPortalLogoView()
		{
			const { portalLogo } = this.state;

			if (isEmpty(portalLogo))
			{
				return null;
			}

			return new PortalLogo(portalLogo);
		}

		componentDidMount()
		{
			this.fetchPortalLogo();
			this.fetchUserCurrent();
		}

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
			});
		}

		onSubmit = () => {
			this.formRef?.submit();
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
			const { profileLoaded, logoLoaded } = this.state;
			const imageUri = `${currentDomain}/bitrix/mobileapp/intranetmobile/components/intranet/user-mini-profile/images/confetti-background.svg`;

			if (!profileLoaded || !logoLoaded)
			{
				return StatusBlock({
					testId: 'USER_MINI_PROFILE_LOADING',
				});
			}

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

	layout.showComponent(new UserMiniProfile());
})();
