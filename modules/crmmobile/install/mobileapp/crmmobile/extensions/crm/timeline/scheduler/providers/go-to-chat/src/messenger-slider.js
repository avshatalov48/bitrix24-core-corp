/**
 * @module crm/timeline/scheduler/providers/go-to-chat/messenger-slider
 */
jn.define('crm/timeline/scheduler/providers/go-to-chat/messenger-slider', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Line } = require('utils/skeleton');
	const AppTheme = require('apptheme');

	const messengers = {
		telegram: {
			id: 'telegram',
			title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_TELEGRAM'),
		},
		whatsApp: {
			id: 'whatsApp',
			title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_WHATSAPP'),
		},
		vk: {
			id: 'vk',
			title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_VK'),
		},
		facebook: {
			id: 'facebook',
			title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_FACEBOOK'),
		},
	};

	/**
	 * @class MessengerSlider
	 */
	class MessengerSlider extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: styles.container,
				},
				Text({
					style: styles.title,
					text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_MESSENGER_SLIDER_TITLE'),
				}),
				ScrollView(
					{
						horizontal: true,
						showsHorizontalScrollIndicator: false,
						style: styles.scrollContainer,
					},
					View(
						{
							style: styles.messengersContainer,
						},
						this.renderItem(messengers.telegram),
						this.renderItem(messengers.whatsApp),
						this.renderEmptyItem(),
						this.renderVk(),
						this.renderFacebook(),
					),
				),
			);
		}

		renderVk()
		{
			if (this.region === 'ru')
			{
				return this.renderItem(messengers.vk);
			}

			return null;
		}

		renderFacebook()
		{
			if (this.region && this.region !== 'ru')
			{
				return this.renderItem(messengers.facebook);
			}

			return null;
		}

		renderEmptyItem()
		{
			if (!this.region)
			{
				return View(
					{
						style: styles.messengerContainer,
					},
					View(
						{
							style: styles.iconContainer(this.props.activeId, null),
						},
						Line(42, 42, 6, 6),
					),
					View(
						{
							style: {
								...styles.messengerTitle(false),
								alignItems: 'center',
							},
						},
						Line(60, 14, 2, 0),
					),
				);
			}

			return null;
		}

		get region()
		{
			return BX.prop.get(this.props, 'region', null);
		}

		renderItem(messenger)
		{
			const { id, title } = messenger;

			const active = this.isActive(id);

			return View(
				{
					style: styles.messengerContainer,
				},
				this.renderBadge(id),
				View(
					{
						style: styles.iconContainer(this.props.activeId, id),
					},
					Image({
						style: styles.logo,
						svg: {
							content: icons[id](active),
						},
					}),
				),
				Text({
					style: styles.messengerTitle(active),
					text: title,
				}),
			);
		}

		renderBadge(id)
		{
			if (this.isActive(id))
			{
				return Image({
					style: styles.badge,
					svg: {
						content: icons.active,
					},
				});
			}

			const text = Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_MESSENGER_SOON_BADGE');

			return Text({
				style: styles.badgeTitle,
				text: text.toLocaleUpperCase(env.languageId),
			});
		}

		isActive(id)
		{
			return isActive(this.props.activeId, id);
		}
	}

	const icons = {
		active: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g filter="url(#filter0_d_932_223871)"><circle cx="12" cy="10" r="8" fill="#9DCF00"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M11.2047 10.9087L15.4299 6.73755L16.7085 8.02326L11.2247 13.46L11.2047 13.44L11.1847 13.46L7.83008 10.2132L9.1087 8.92744L11.2047 10.9087Z" fill="white"/><defs><filter id="filter0_d_932_223871" x="0" y="0" width="24" height="24" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2"/><feGaussianBlur stdDeviation="2"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_932_223871"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_932_223871" result="shape"/></filter></defs></svg>',
		telegram: (active = false) => {
			const iconColor = (active ? AppTheme.colors.accentMainPrimaryalt : AppTheme.colors.base3);

			return `<svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M43.3337 26.0001C43.3337 35.573 35.5733 43.3334 26.0003 43.3334C16.4274 43.3334 8.66699 35.573 8.66699 26.0001C8.66699 16.4271 16.4274 8.66675 26.0003 8.66675C35.5733 8.66675 43.3337 16.4271 43.3337 26.0001ZM15.9821 26.7682L20.2881 28.1598C20.3607 28.1723 20.4352 28.1678 20.5057 28.1466L20.5084 28.1449C21.5016 27.5229 30.3609 21.9738 30.8724 21.7872C30.953 21.7629 31.0129 21.7902 30.9971 21.8455C30.792 22.5589 23.0836 29.3549 23.0836 29.3549C23.0836 29.3549 23.0086 29.4396 23.0223 29.508C23.0255 29.5343 23.0343 29.5597 23.0481 29.5823C23.0619 29.6049 23.0805 29.6244 23.1026 29.6393C24.0808 30.2883 28.5077 33.2391 29.7885 34.3319C29.9036 34.4427 30.0401 34.5295 30.1898 34.5869C30.3394 34.6443 30.4992 34.6713 30.6596 34.6661C31.3123 34.6418 31.4945 33.9307 31.4945 33.9307C31.4945 33.9307 34.5392 21.7784 34.641 20.1501C34.6449 20.0884 34.6493 20.0354 34.6533 19.9871C34.6597 19.9108 34.665 19.8466 34.6658 19.7794C34.6711 19.6526 34.6585 19.5258 34.6285 19.4024C34.6132 19.3326 34.5795 19.2681 34.5308 19.2154C34.4821 19.1627 34.4201 19.1239 34.3513 19.1027C34.0731 18.9979 33.6015 19.1555 33.6015 19.1555C33.6015 19.1555 16.8961 25.1111 15.942 25.7707C15.7361 25.9123 15.6673 25.9947 15.6332 26.0915C15.4675 26.5611 15.9821 26.7682 15.9821 26.7682Z" fill="${iconColor}"/></svg>`;
		},
		whatsApp: (active = false) => {
			// @todo active icon will be draw later
			return `<svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M26.181 8.67225C30.7302 8.67225 35.1847 10.4584 38.407 13.7486C41.6294 16.9449 43.4301 21.2692 43.4301 25.8756C43.4301 35.3703 35.6585 43.1729 26.0862 43.1729C23.2429 43.1729 20.3997 42.5148 17.8407 41.1047L8.64748 43.4549L11.1116 34.5242C9.69001 31.892 8.83703 28.9778 8.83703 25.9696C8.74225 16.4748 16.5139 8.67225 26.181 8.67225ZM18.8832 38.2845C21.0631 39.6006 23.622 40.2587 26.181 40.2587C28.8347 40.2587 31.4884 39.5066 33.6682 38.0965C40.4921 33.9602 42.4824 25.2175 38.3123 18.449C34.1421 11.6805 25.328 9.70633 18.5041 13.8426C11.6803 17.979 9.69001 26.8156 13.8601 33.4901L14.2392 34.0542L12.8176 39.4126L18.3146 38.0025L18.8832 38.2845ZM29.296 29.1501C29.869 28.4134 30.2782 28.0041 30.5238 28.0041C30.6875 28.0041 31.4242 28.3315 32.7339 28.9864C34.0436 29.6412 34.6985 30.0505 34.7803 30.2142C34.7803 30.2481 34.7944 30.282 34.8108 30.3218C34.8341 30.3779 34.8622 30.4458 34.8622 30.5417C34.8622 31.0328 34.6985 31.6058 34.4529 32.2606C34.2073 32.8336 33.6343 33.3248 32.8158 33.734C31.9972 34.1433 31.1787 34.307 30.5238 34.307C29.6234 34.307 28.2318 33.8159 26.1855 32.9155C24.712 32.2606 23.4024 31.3602 22.3382 30.2142C21.2741 29.0683 20.1281 27.6767 18.9821 25.9577C17.918 24.3206 17.345 22.8472 17.345 21.5375L17.345 21.3738C17.4269 19.9823 17.918 18.7544 19.064 17.7722C19.3914 17.4447 19.8007 17.281 20.2918 17.281L20.7011 17.281L21.1104 17.281C21.4378 17.281 21.6015 17.3629 21.6834 17.4447C21.7652 17.5266 21.9289 17.7722 22.0108 18.0996C22.0448 18.2357 22.1213 18.4285 22.2227 18.6838C22.365 19.0424 22.5562 19.5244 22.7475 20.146C23.1568 21.2101 23.3205 21.7831 23.3205 21.8649C23.3205 22.1924 23.0749 22.6016 22.5019 23.1746C22.3943 23.3003 22.2945 23.4141 22.2043 23.5169C21.883 23.8833 21.6834 24.1109 21.6834 24.2388C21.6834 24.3206 21.6834 24.4843 21.7652 24.5662C22.2564 25.7122 23.0749 26.6944 24.0572 27.6767C24.8758 28.4953 26.0217 29.232 27.4951 29.9687C27.6589 30.0505 27.8226 30.1324 27.9863 30.1324C28.3137 30.2142 28.723 29.8868 29.296 29.1501Z" fill="${AppTheme.colors.base5}"/></svg>`;
		},
		vk: (active = false) => {
			// @todo active icon will be draw later
			return `<svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M27.8512 38.3456C15.5587 38.3456 8.09862 29.817 7.80859 15.6448H14.0344C14.2285 26.0553 18.9649 30.4733 22.5957 31.374V15.6448H28.5642V24.627C32.0664 24.2388 35.7299 20.1522 36.9642 15.6448H42.8324C41.8925 21.1902 37.9041 25.2768 35.0823 26.9625C37.9041 28.3254 42.4442 31.893 44.1953 38.3456H37.7427C36.3798 34.0322 33.039 30.6914 28.5642 30.2378V38.3456H27.8512Z" fill="${AppTheme.colors.base5}"/></svg>`;
		},
		facebook: (active = false) => {
			// @todo active icon will be draw later
			return `<svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M22.2419 19.9335H19V25.489H22.2419V42H28.901V25.4908H33.3706C33.3706 25.4908 33.7893 22.827 33.9933 19.9138H28.9274V16.1147C28.9274 15.5473 29.6528 14.7846 30.3711 14.7846H34V9H29.0653C22.078 9 22.2419 14.561 22.2419 15.3915V19.9335Z" fill="${AppTheme.colors.base5}"/></svg>`;
		},
	};

	const isActive = (activeMessengerId, currentMessengerId) => activeMessengerId === currentMessengerId;

	const styles = {
		container: {
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderRadius: 12,
			marginTop: 12,
			paddingVertical: 12,
		},
		title: {
			fontSize: 14,
			color: AppTheme.colors.base2,
			marginLeft: 19,
		},
		scrollContainer: {
			marginTop: 8,
			height: 110,
		},
		messengersContainer: {
			flexDirection: 'row',
			paddingHorizontal: 13,
		},
		messengerContainer: {
			width: 122,
			paddingRight: 4,
			paddingTop: 12,
			marginHorizontal: 6,
		},
		badge: {
			width: 24,
			height: 24,
			position: 'absolute',
			right: -4,
			top: 4,
			zIndex: 10,
		},
		badgeTitle: {
			position: 'absolute',
			backgroundColor: AppTheme.colors.bgContentPrimary,
			color: AppTheme.colors.base5,
			paddingLeft: 6,
			paddingRight: 2,
			fontSize: 8,
			fontWeight: '700',
			right: 2,
			top: 11,
			zIndex: 10,
			textAlign: 'center',
		},
		logo: {
			width: 52,
			height: 52,
		},
		messengerTitle: (active) => {
			return {
				textAlign: 'center',
				marginTop: 8,
				fontSize: 16,
				color: (active ? AppTheme.colors.base1 : AppTheme.colors.base3),
			};
		},
		iconContainer: (activeMessengerId, currentMessengerId) => {
			const params = {
				borderRadius: 6,
				justifyContent: 'center',
				alignItems: 'center',
				alignContent: 'center',
				paddingHorizontal: 4,
				paddingVertical: 6,
			};

			if (isActive(activeMessengerId, currentMessengerId))
			{
				params.borderWidth = 2;
				params.borderColor = AppTheme.colors.accentBrandBlue;
			}
			else
			{
				params.borderWidth = 1;
				params.borderColor = AppTheme.colors.base6;
			}

			return params;
		},
	};

	module.exports = { MessengerSlider, messengers };
});
