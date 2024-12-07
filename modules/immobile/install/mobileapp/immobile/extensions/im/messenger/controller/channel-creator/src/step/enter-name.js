/**
 * @module im/messenger/controller/channel-creator/step/enter-name
 */
jn.define('im/messenger/controller/channel-creator/step/enter-name', (require, exports, module) => {
	const { Loc } = require('loc');

	const { Theme } = require('im/lib/theme');

	const { Step } = require('im/messenger/controller/channel-creator/step/base');
	const { AvatarButton } = require('im/messenger/controller/channel-creator/components/avatar-button');
	const { TitleField } = require('im/messenger/controller/channel-creator/components/title-field');
	const { DescriptionField } = require('im/messenger/controller/channel-creator/components/description-field');

	const previewAvatarSvgLight = `<svg width="84" height="84" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.6949 4.19531C10.239 4.19531 4.19489 10.2395 4.19489 17.6953V66.3055C4.19489 73.7613 10.2391 79.8055 17.6949 79.8055H66.3051C73.7609 79.8055 79.8051 73.7613 79.8051 66.3055V17.6953C79.8051 10.2395 73.7609 4.19531 66.305 4.19531H17.6949Z" stroke="url(#paint0_linear_17702_428866)" stroke-width="3"/><path d="M6.94489 17.6953C6.94489 11.7583 11.7578 6.94531 17.6949 6.94531H66.305C72.2421 6.94531 77.0551 11.7583 77.0551 17.6953V66.3055C77.0551 72.2425 72.2421 77.0555 66.3051 77.0555H17.6949C11.7578 77.0555 6.94489 72.2425 6.94489 66.3055V17.6953Z" fill="${Theme.colors.accentSoftBlue3}"/><path d="M6.94489 17.6953C6.94489 11.7583 11.7578 6.94531 17.6949 6.94531H66.305C72.2421 6.94531 77.0551 11.7583 77.0551 17.6953V66.3055C77.0551 72.2425 72.2421 77.0555 66.3051 77.0555H17.6949C11.7578 77.0555 6.94489 72.2425 6.94489 66.3055V17.6953Z" stroke="${Theme.colors.bgContentPrimary}" stroke-width="2.5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M34.8876 43.9335C34.8876 40.0086 38.0754 36.8208 42.0003 36.8208C45.9253 36.8208 49.113 40.0086 49.113 43.9335C49.113 47.8585 45.9253 51.0462 42.0003 51.0462C38.0754 51.0462 34.8876 47.8585 34.8876 43.9335ZM37.2785 43.9335C37.2785 46.5435 39.3903 48.6554 42.0003 48.6554C44.6103 48.6554 46.7222 46.5435 46.7222 43.9335C46.7222 41.3236 44.6103 39.2117 42.0003 39.2117C39.3903 39.2117 37.2785 41.3236 37.2785 43.9335Z" fill="#0075FF"/><path fill-rule="evenodd" clip-rule="evenodd" d="M50.2085 32.8553H53.9541C56.1457 32.8553 57.9388 34.6484 57.9388 36.84V51.9819C57.9388 54.1734 56.1457 55.9666 53.9541 55.9666H30.0459C27.8543 55.9666 26.0612 54.1734 26.0612 51.9819V36.84C26.0612 34.6484 27.8543 32.8553 30.0459 32.8553H33.6719L35.7041 29.8269C36.4413 28.7112 37.6766 28.0537 39.0114 28.0537H44.7893C46.1042 28.0537 47.3395 28.6913 48.0766 29.7671L50.2085 32.8553ZM53.9541 53.5558C54.8307 53.5558 55.548 52.8386 55.548 51.9619V36.8201C55.548 35.9434 54.8307 35.2262 53.9541 35.2262H50.2085C49.4115 35.2262 48.6744 34.8277 48.236 34.1902L46.1042 31.102C45.8054 30.6836 45.3073 30.4246 44.7893 30.4246H39.0114C38.4934 30.4246 37.9953 30.7035 37.6965 31.1419L35.6643 34.1702C35.2061 34.8277 34.4689 35.2262 33.6719 35.2262H30.0459C29.1692 35.2262 28.452 35.9434 28.452 36.8201V51.9619C28.452 52.8386 29.1692 53.5558 30.0459 53.5558H53.9541Z" fill="#0075FF"/><defs><linearGradient id="paint0_linear_17702_428866" x1="15.2327" y1="7.23366" x2="43.846" y2="48.4615" gradientUnits="userSpaceOnUse"><stop stop-color="#86FFC7"/><stop offset="1" stop-color="#0075FF"/></linearGradient></defs></svg>`;
	const previewAvatarSvgDark = '<svg width="84" height="84" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.6949 4.19531C10.2391 4.19531 4.19492 10.2395 4.19492 17.6953V66.3055C4.19492 73.7613 10.2391 79.8055 17.6949 79.8055H66.3051C73.7609 79.8055 79.8051 73.7613 79.8051 66.3055V17.6953C79.8051 10.2395 73.7609 4.19531 66.3051 4.19531H17.6949Z" stroke="url(#paint0_linear_17800_1227307)" stroke-width="3"/><path d="M6.94492 17.6953C6.94492 11.7583 11.7579 6.94531 17.6949 6.94531H66.3051C72.2421 6.94531 77.0551 11.7583 77.0551 17.6953V66.3055C77.0551 72.2425 72.2421 77.0555 66.3051 77.0555H17.6949C11.7579 77.0555 6.94492 72.2425 6.94492 66.3055V17.6953Z" fill="#1A2A33"/><path d="M6.94492 17.6953C6.94492 11.7583 11.7579 6.94531 17.6949 6.94531H66.3051C72.2421 6.94531 77.0551 11.7583 77.0551 17.6953V66.3055C77.0551 72.2425 72.2421 77.0555 66.3051 77.0555H17.6949C11.7579 77.0555 6.94492 72.2425 6.94492 66.3055V17.6953Z" stroke="#292929" stroke-width="2.5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M34.8877 43.9335C34.8877 40.0086 38.0754 36.8208 42.0004 36.8208C45.9253 36.8208 49.1131 40.0086 49.1131 43.9335C49.1131 47.8585 45.9253 51.0462 42.0004 51.0462C38.0754 51.0462 34.8877 47.8585 34.8877 43.9335ZM37.2785 43.9335C37.2785 46.5435 39.3904 48.6554 42.0004 48.6554C44.6103 48.6554 46.7222 46.5435 46.7222 43.9335C46.7222 41.3236 44.6103 39.2117 42.0004 39.2117C39.3904 39.2117 37.2785 41.3236 37.2785 43.9335Z" fill="#1587FA"/><path fill-rule="evenodd" clip-rule="evenodd" d="M50.2085 32.8553H53.9541C56.1457 32.8553 57.9388 34.6484 57.9388 36.84V51.9819C57.9388 54.1734 56.1457 55.9666 53.9541 55.9666H30.0459C27.8543 55.9666 26.0612 54.1734 26.0612 51.9819V36.84C26.0612 34.6484 27.8543 32.8553 30.0459 32.8553H33.672L35.7042 29.8269C36.4413 28.7112 37.6766 28.0537 39.0115 28.0537H44.7893C46.1042 28.0537 47.3395 28.6913 48.0767 29.7671L50.2085 32.8553ZM53.9541 53.5558C54.8308 53.5558 55.548 52.8386 55.548 51.9619V36.8201C55.548 35.9434 54.8308 35.2262 53.9541 35.2262H50.2085C49.4116 35.2262 48.6744 34.8277 48.2361 34.1902L46.1043 31.102C45.8054 30.6836 45.3073 30.4246 44.7893 30.4246H39.0115C38.4935 30.4246 37.9954 30.7035 37.6965 31.1419L35.6643 34.1702C35.2061 34.8277 34.4689 35.2262 33.672 35.2262H30.0459C29.1693 35.2262 28.452 35.9434 28.452 36.8201V51.9619C28.452 52.8386 29.1693 53.5558 30.0459 53.5558H53.9541Z" fill="#1587FA"/><defs><linearGradient id="paint0_linear_17800_1227307" x1="15.2327" y1="7.23366" x2="43.846" y2="48.4615" gradientUnits="userSpaceOnUse"><stop stop-color="#0A4C2E"/><stop offset="1" stop-color="#1B79E6"/></linearGradient></defs></svg>';

	class EnterNameStep extends Step
	{
		#title;
		#description;
		#avatarBase64;
		#previewAvatarPath;

		/**
		 * @return WidgetTitleParamsType
		 */
		static getTitleParams()
		{
			return {
				text: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_ENTER_NAME_TITLE'),
			};
		}

		getStepData()
		{
			return {
				title: this.#title,
				description: this.#description,
				avatarBase64: this.#avatarBase64,
				previewAvatarPath: this.#previewAvatarPath,
			};
		}

		render()
		{
			return ScrollView(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						borderWidth: 1,
						borderColor: Theme.colors.testTech,
					},
					resizableByKeyboard: true,
					horizontal: false,
					ref: (ref) => this.scrollViewRef = ref,
				},
				View(
					{
						style: {
							paddingTop: 4,
							paddingBottom: 4,
							paddingLeft: 6,
							paddingRight: 6,
							borderRadius: 12,
							backgroundColor: Theme.colors.bgContentPrimary,
							flexDirection: 'column',
						},
						resizableByKeyboard: true,

					},
					View(
						{
							style: {
								flexDirection: 'row',
								paddingBottom: 12,
								paddingTop: 12,
								paddingLeft: 12,
								paddingRight: 12,
								marginBottom: 6,
								alignItems: 'center',
							},
							resizableByKeyboard: true,

						},
						View(
							{},
							new AvatarButton({
								defaultIconSvg: Theme.getInstance().getId() === 'light'
									? previewAvatarSvgLight
									: previewAvatarSvgDark,
								cornerRadius: 12,
								onAvatarSelected: ({ avatarBase64, previewAvatarPath }) => {
									this.#avatarBase64 = avatarBase64;
									this.#previewAvatarPath = previewAvatarPath;
								},
							}),
						),
						View(
							{
								style: {
									marginLeft: 18,
									flexGrow: 3,
								},
							},
							new TitleField({
								placeholder: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_ENTER_NAME_TITLE_FIELD_PLACEHOLDER'),
								value: this.props.title,
								onChange: (value) => {
									this.#title = value;
								},
							}),
						),
					),
					View(
						{
							style: {
								flexDirection: 'column',
								paddingBottom: 12,
								paddingTop: 12,
								paddingLeft: 12,
								paddingRight: 12,
							},
						},
						View(
							{},
							new DescriptionField({
								badge: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_ENTER_NAME_DESCRIPTION_FIELD_BADGE'),
								placeholder: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_ENTER_NAME_DESCRIPTION_FIELD_PLACEHOLDER'),
								value: this.props.description ?? '',
								onChange: (value) => {
									this.#description = value;
									this.scrollViewRef.scrollToEnd();
								},
							}),
						),

					),

				),
			);
		}
	}

	module.exports = {
		EnterNameStep,
	};
});
