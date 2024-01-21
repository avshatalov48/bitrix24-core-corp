/**
 * @module crm/timeline/item/ui/footer
 */
jn.define('crm/timeline/item/ui/footer', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { TimelineItemContextMenu } = require('crm/timeline/item/ui/context-menu');
	const {
		TimelineButtonType,
		TimelineButtonVisibilityFilter,
		TimelineButtonSorter,
	} = require('crm/timeline/item/ui/styles');
	const { transparent, withPressed } = require('utils/color');
	const { get } = require('utils/object');
	const { dots } = require('assets/common');

	const nothing = () => {};

	const MAX_BUTTONS_COUNT = 3;
	const MAX_ICONS_COUNT = 2;

	/**
	 * @class TimelineItemFooter
	 */
	class TimelineItemFooter extends LayoutComponent
	{
		/**
		 * @return {TimelineItemContextMenu}
		 */
		get menu()
		{
			return new TimelineItemContextMenu({
				items: this.availableMenuItems,
				onAction: (action) => this.onAction(action),
				isReadonly: this.props.isReadonly,
			});
		}

		/**
		 * @return {*[]}
		 */
		get availableButtons()
		{
			return Object.values(this.props.buttons || {})
				.filter((button) => TimelineButtonVisibilityFilter(button, this.props.isReadonly))
				.sort(TimelineButtonSorter);
		}

		/**
		 * @return {*[]}
		 */
		get displayedButtons()
		{
			return this.availableButtons.slice(0, MAX_BUTTONS_COUNT);
		}

		/**
		 * @return {*[]}
		 */
		get hiddenButtons()
		{
			return this.availableButtons.slice(MAX_BUTTONS_COUNT);
		}

		/**
		 * @return {*[]}
		 */
		get availableIcons()
		{
			return Object.values(this.props.additionalButtons || {})
				.filter((button) => TimelineButtonVisibilityFilter(button, this.props.isReadonly))
				.sort(TimelineButtonSorter);
		}

		/**
		 * @return {*[]}
		 */
		get displayedIcons()
		{
			return this.availableIcons.slice(0, MAX_ICONS_COUNT);
		}

		/**
		 * @return {*[]}
		 */
		get availableMenuItems()
		{
			const rawItems = Object.values(get(this.props, 'menu.items', {}));

			return [...rawItems, ...this.hiddenButtons];
		}

		render()
		{
			const hasButtons = this.displayedButtons.length > 0;
			const hasIconsOrMenu = hasButtons || this.menu.hasItems();

			if (!hasButtons && !hasIconsOrMenu)
			{
				return null;
			}

			return View(
				{
					style: {
						paddingHorizontal: 16,
						paddingTop: 0,
						paddingBottom: 12,
						flexDirection: 'row',
						flexWrap: 'wrap',
					},
				},
				...this.displayedButtons.map((button, index) => this.renderButton(button, index)),
				hasIconsOrMenu && View(
					{
						style: {
							width: this.displayedButtons.length % 2 === 0 ? '100%' : '50%',
							flexDirection: 'row',
							justifyContent: 'flex-end',
							marginBottom: 12,
						},
					},
					...this.displayedIcons.map((icon) => this.renderIcon(icon)),
					this.renderMenu(),
				),
			);
		}

		renderButton(button, index)
		{
			return View(
				{
					style: {
						width: '50%',
						marginBottom: 12,
						paddingRight: index % 2 === 0 ? 6 : 0,
						paddingLeft: index % 2 === 1 ? 6 : 0,
					},
				},
				TimelineItemButton({
					...button,
					onClick: () => this.onAction(button.action),
				}),
			);
		}

		renderIcon(icon)
		{
			const code = icon.iconName;

			if (!Icons[code])
			{
				return null;
			}

			return View(
				{
					testId: `TimelineItemFooterIcon_${code}`,
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						paddingLeft: 12,
						paddingRight: 12,
						paddingTop: 5,
						paddingBottom: 5,
					},
					onClick: () => this.onAction(icon.action),
				},
				Image({
					style: {
						width: 28,
						height: 28,
					},
					svg: {
						content: typeof Icons[code] === 'function' ? Icons[code](icon, this) : Icons[code],
					},
				}),
			);
		}

		renderMenu()
		{
			if (!this.menu.hasItems())
			{
				return null;
			}

			return View(
				{
					testId: 'TimelineItemFooterMenuIcon',
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						paddingLeft: 8,
						paddingRight: 4,
						paddingTop: 16,
						paddingBottom: 16,
					},
					onClick: () => this.menu.open(),
				},
				Image({
					tintColor: AppTheme.colors.base3,
					style: {
						width: 20,
						height: 6,
					},
					svg: {
						content: dots(),
					},
				}),
			);
		}

		onAction(action)
		{
			if (action && this.props.onAction)
			{
				this.props.onAction(action);
			}
		}
	}

	function TimelineItemButton({ type, title, sort = 0, onClick = nothing })
	{
		return View(
			{
				onClick,
				testId: `TimelineItemFooterButton_${sort}_clickable`,
				style: {
					backgroundColor: getTimelineButtonBackground(type),
					borderRadius: 512,
					borderWidth: 1,
					borderColor: type === TimelineButtonType.PRIMARY ? AppTheme.colors.accentMainPrimary : AppTheme.colors.base3,
					paddingHorizontal: 16,
					paddingVertical: 10,
					flexDirection: 'row',
					justifyContent: 'center',
				},
			},
			Text({
				text: title,
				testId: `TimelineItemFooterButton_${sort}_caption`,
				ellipsize: 'end',
				numberOfLines: 1,
				style: {
					color: type === TimelineButtonType.PRIMARY ? AppTheme.colors.baseWhiteFixed : AppTheme.colors.base1,
					fontSize: 15,
					fontWeight: '500',
				},
			}),
		);
	}

	function getTimelineButtonBackground(type)
	{
		if (type === TimelineButtonType.PRIMARY)
		{
			return withPressed(AppTheme.colors.accentMainPrimary);
		}

		const { default: defaultColor, pressed } = withPressed(AppTheme.colors.bgContentPrimary);

		return {
			default: transparent(defaultColor),
			pressed,
		};
	}

	const IconColors = {
		primary: AppTheme.colors.accentBrandBlue,
		default: AppTheme.colors.base3,
	};

	const Icons = {
		script: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.5936 10.7289C10.2714 10.7289 10.0102 10.9901 10.0102 11.3123V12.1735C10.0102 12.4956 10.2714 12.7568 10.5936 12.7568H16.5031C16.8252 12.7568 17.0864 12.4956 17.0864 12.1735V11.3123C17.0864 10.9901 16.8252 10.7289 16.5031 10.7289H10.5936Z" fill="${AppTheme.colors.base3}"/><path d="M10.0102 15.2022C10.0102 14.88 10.2714 14.6188 10.5936 14.6188H16.5031C16.8252 14.6188 17.0864 14.88 17.0864 15.2022V16.0633C17.0864 16.3855 16.8252 16.6467 16.5031 16.6467H10.5936C10.2714 16.6467 10.0102 16.3855 10.0102 16.0633V15.2022Z" fill="${AppTheme.colors.base3}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M5.83325 12.25C5.83325 8.70621 8.70609 5.83333 12.2499 5.83333H21.6495C21.8019 5.83333 21.9441 5.86099 22.0731 5.91063C22.636 6.01969 23.3622 6.29573 23.9882 6.95656C24.7222 7.73129 25.0406 8.72797 25.1802 9.34005C25.3974 10.2929 24.6235 11.0297 23.8006 11.0297L20.9999 11.0297V18.0833C20.9999 20.3385 19.1717 22.1667 16.9166 22.1667H6.41659C6.09442 22.1667 5.83325 21.9059 5.83325 21.5837V12.25ZM21.4596 9.2797L23.3479 9.27969C23.2161 8.88784 23.0151 8.47386 22.7179 8.16015C22.6252 8.06239 22.5285 7.9812 22.4305 7.91382L21.5432 9.15607C21.5142 9.19664 21.4863 9.23786 21.4596 9.2797ZM7.82686 12.4934C7.82686 9.91611 9.91619 7.82693 12.4935 7.82693H20.166C20.166 7.82693 19.0339 9.07451 19.0339 10.5V17.4898C19.0339 18.9717 17.8325 20.1731 16.3506 20.1731H7.82686V12.4934Z" fill="${AppTheme.colors.base3}"/></svg>`,
		note: (icon) => {
			const color = IconColors[icon.color] || IconColors.default;

			return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 5C6.11929 5 5 6.11929 5 7.5V16.5C5 17.8807 6.11929 19 7.5 19H14.5222C15.16 19 15.7736 18.7562 16.2376 18.3186L18.2154 16.4531C18.7162 15.9808 19 15.3229 19 14.6345V7.5C19 6.11929 17.8807 5 16.5 5H7.5ZM7.7088 6.7088C7.15652 6.7088 6.7088 7.15652 6.7088 7.7088V16.2912C6.7088 16.8435 7.15652 17.2912 7.7088 17.2912H14V14.5C14 14.2239 14.2239 14 14.5 14H17.2912V7.7088C17.2912 7.15652 16.8435 6.7088 16.2912 6.7088H7.7088ZM9.08035 9C8.8042 9 8.58035 9.22386 8.58035 9.5V10.2381C8.58035 10.5143 8.8042 10.7381 9.08035 10.7381H14.1456C14.4218 10.7381 14.6456 10.5143 14.6456 10.2381V9.5C14.6456 9.22386 14.4218 9 14.1456 9H9.08035Z" fill="${color}"/></svg>`;
		},
		print: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.1889 4.66669C9.58755 4.66669 9.10004 5.1583 9.10004 5.76473V7.9608H18.9V5.76473C18.9 5.1583 18.4125 4.66669 17.8112 4.66669H10.1889Z" fill="${AppTheme.colors.base3}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M5.83337 11.2549C5.83337 10.0421 6.8084 9.05884 8.01115 9.05884H19.9889C21.1917 9.05884 22.1667 10.0421 22.1667 11.2549V15.6471C22.1667 16.8599 21.1917 17.8432 19.9889 17.8432V16.7451C19.9889 16.1387 19.5014 15.6471 18.9 15.6471H9.10004C8.49866 15.6471 8.01115 16.1387 8.01115 16.7451L8.01115 17.8432C6.8084 17.8432 5.83337 16.8599 5.83337 15.6471V11.2549ZM19.9889 12.353C19.9889 12.9594 19.5014 13.451 18.9 13.451C18.2987 13.451 17.8112 12.9594 17.8112 12.353C17.8112 11.7465 18.2987 11.2549 18.9 11.2549C19.5014 11.2549 19.9889 11.7465 19.9889 12.353ZM13.4556 12.3615C13.4556 12.1112 13.6568 11.9083 13.9051 11.9083H16.2728C16.521 11.9083 16.7223 12.1112 16.7223 12.3615C16.7223 12.6119 16.521 12.8148 16.2728 12.8148H13.9051C13.6568 12.8148 13.4556 12.6119 13.4556 12.3615Z" fill="${AppTheme.colors.base3}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.10004 17.2941C9.10004 16.9909 9.3438 16.7451 9.64448 16.7451H18.3556C18.6563 16.7451 18.9 16.9909 18.9 17.2941V22.7843C18.9 23.0875 18.6563 23.3334 18.3556 23.3334H9.64448C9.3438 23.3334 9.10004 23.0875 9.10004 22.7843V17.2941ZM10.1889 19.4902C10.1889 19.187 10.4327 18.9412 10.7334 18.9412H17.2667C17.5674 18.9412 17.8112 19.187 17.8112 19.4902C17.8112 19.7934 17.5674 20.0392 17.2667 20.0392H10.7334C10.4327 20.0392 10.1889 19.7934 10.1889 19.4902ZM10.7334 21.1373C10.4327 21.1373 10.1889 21.3831 10.1889 21.6863C10.1889 21.9895 10.4327 22.2353 10.7334 22.2353H17.2667C17.5674 22.2353 17.8112 21.9895 17.8112 21.6863C17.8112 21.3831 17.5674 21.1373 17.2667 21.1373H10.7334Z" fill="${AppTheme.colors.base3}"/></svg>`,
		videoconference: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M14.8409 7.7027C13.0211 7.26418 11.2009 7.04492 9.38045 7.04492C7.55261 7.04492 5.68802 7.26596 3.78667 7.70802C3.37832 7.80297 3.0885 8.17508 3.0885 8.60437V15.9461C3.0885 16.3615 3.36028 16.7252 3.75146 16.8334C5.56824 17.3359 7.38563 17.5871 9.20362 17.5871C11.0317 17.5871 12.9163 17.3331 14.8575 16.8251C15.2545 16.7212 15.5322 16.3546 15.5322 15.9345V8.59739C15.5322 8.1708 15.2459 7.80028 14.8409 7.7027ZM21.1311 8.01741C21.1311 7.98399 21.1251 7.95085 21.1136 7.9196C21.0609 7.77723 20.9056 7.7056 20.7667 7.75962L16.7085 9.33827C16.604 9.37891 16.535 9.48152 16.535 9.59606V14.5791C16.535 14.6937 16.604 14.7963 16.7085 14.8369L20.7667 16.4156C20.7972 16.4274 20.8295 16.4335 20.8621 16.4335C21.0107 16.4335 21.1311 16.3101 21.1311 16.1578V8.01741ZM9.24012 13.1492C9.72627 13.1492 10.1204 12.7551 10.1204 12.2689C10.1204 11.7828 9.72627 11.3887 9.24012 11.3887C8.75396 11.3887 8.35986 11.7828 8.35986 12.2689C8.35986 12.7551 8.75396 13.1492 9.24012 13.1492ZM6.39983 13.1492C6.88598 13.1492 7.28009 12.7551 7.28009 12.269C7.28009 11.7828 6.88598 11.3887 6.39983 11.3887C5.91368 11.3887 5.51957 11.7828 5.51957 12.269C5.51957 12.7551 5.91368 13.1492 6.39983 13.1492ZM12.9622 12.2689C12.9622 12.7551 12.5681 13.1492 12.0819 13.1492C11.5958 13.1492 11.2017 12.7551 11.2017 12.2689C11.2017 11.7828 11.5958 11.3887 12.0819 11.3887C12.5681 11.3887 12.9622 11.7828 12.9622 12.2689Z" fill="${AppTheme.colors.base3}"/></svg>`,
	};

	module.exports = { TimelineItemFooter };
});
