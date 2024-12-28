/**
 * @module collab/create/src/security
 */
jn.define('collab/create/src/security', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Area } = require('ui-system/layout/area');
	const {
		SettingSelectorList,
		SettingSelectorListItemDesign,
		IconOrSwitcherPlacement,
	} = require('layout/ui/setting-selector-list');
	const { Link4, LinkMode, Ellipsize } = require('ui-system/blocks/link');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');

	const helpArticleCode = '22707844';

	const Security = {
		PROHIBIT_SCREENSHOT_FOR_GUESTS: 'prohibitScreenshotForGuests',
		PROHIBIT_COPY_TEXT_FOR_GUESTS: 'prohibitCopyTextForGuests',
		BITRIX_SP_PROTECTION: 'bitrixSpProtection',
		PROHIBIT_DOWNLOAD_FILES_FOR_GUESTS: 'prohibitDownloadFilesForGuests',
	};

	class CollabCreateSecurity extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.#initializeState();
		}

		#initializeState = () => {
			this.state = {
				[Security.PROHIBIT_SCREENSHOT_FOR_GUESTS]: this.props[Security.PROHIBIT_SCREENSHOT_FOR_GUESTS] ?? false,
				[Security.PROHIBIT_COPY_TEXT_FOR_GUESTS]: this.props[Security.PROHIBIT_COPY_TEXT_FOR_GUESTS] ?? false,
				[Security.BITRIX_SP_PROTECTION]: this.props[Security.BITRIX_SP_PROTECTION] ?? false,
				[Security.PROHIBIT_DOWNLOAD_FILES_FOR_GUESTS]: this.props[Security.PROHIBIT_DOWNLOAD_FILES_FOR_GUESTS] ?? false,
			};
		};

		get testId()
		{
			return `${this.props.testId}-security`;
		}

		render()
		{
			return Box(
				{
					testId: `${this.testId}-security-screen-box`,
					resizableByKeyboard: true,
					safeArea: { bottom: true },
					style: {
						width: '100%',
						flex: 1,
					},
				},
				AreaList(
					{
						testId: `${this.testId}-area-list`,
						style: {
							flex: 1,
							flexDirection: 'column',
							width: '100%',
						},
						resizableByKeyboard: true,
						showsVerticalScrollIndicator: true,
					},
					this.#renderSecurityListArea(),
				),
			);
		}

		#renderSecurityListArea()
		{
			return Area(
				{
					testId: `${this.testId}-area-security-list`,
					isFirst: false,
					divider: false,
					style: {
						justifyContent: 'flex-start',
						alignItems: 'center',
					},
				},
				SettingSelectorList({
					testId: `${this.testId}-security-setting-selector-list`,
					items: [
						{
							id: Security.PROHIBIT_SCREENSHOT_FOR_GUESTS,
							title: Loc.getMessage('M_COLLAB_SECURITY_PROHIBIT_SCREENSHOT_FOR_GUESTS_TITLE'),
							subtitle: Loc.getMessage('M_COLLAB_SECURITY_PROHIBIT_SCREENSHOT_FOR_GUESTS_SUBTITLE'),
							design: SettingSelectorListItemDesign.TOGGLE,
							checked: this.state[Security.PROHIBIT_SCREENSHOT_FOR_GUESTS],
							iconOrSwitcherPlacement: IconOrSwitcherPlacement.TITLE,
						},
						{
							id: Security.PROHIBIT_COPY_TEXT_FOR_GUESTS,
							title: Loc.getMessage('M_COLLAB_SECURITY_PROHIBIT_COPY_TEXT_FOR_GUESTS_TITLE'),
							subtitle: Loc.getMessage('M_COLLAB_SECURITY_PROHIBIT_COPY_TEXT_FOR_GUESTS_SUBTITLE'),
							design: SettingSelectorListItemDesign.TOGGLE,
							checked: this.state[Security.PROHIBIT_COPY_TEXT_FOR_GUESTS],
							iconOrSwitcherPlacement: IconOrSwitcherPlacement.TITLE,
						},
						{
							id: Security.BITRIX_SP_PROTECTION,
							title: Loc.getMessage('M_COLLAB_SECURITY_BITRIX_SP_PROTECTION_TITLE'),
							subtitle: Loc.getMessage('M_COLLAB_SECURITY_BITRIX_SP_PROTECTION_SUBTITLE'),
							design: SettingSelectorListItemDesign.TOGGLE,
							checked: this.state[Security.BITRIX_SP_PROTECTION],
							iconOrSwitcherPlacement: IconOrSwitcherPlacement.TITLE,
						},
						{
							id: Security.PROHIBIT_DOWNLOAD_FILES_FOR_GUESTS,
							title: Loc.getMessage('M_COLLAB_SECURITY_PROHIBIT_DOWNLOAD_FILES_FOR_GUESTS_TITLE'),
							subtitle: Loc.getMessage('M_COLLAB_SECURITY_PROHIBIT_DOWNLOAD_FILES_FOR_GUESTS_SUBTITLE'),
							design: SettingSelectorListItemDesign.TOGGLE,
							checked: this.state[Security.PROHIBIT_DOWNLOAD_FILES_FOR_GUESTS],
							iconOrSwitcherPlacement: IconOrSwitcherPlacement.TITLE,
						},
					],
					onCheckedChange: this.#onSecurityCheckedChange,
				}),
				this.#renderDetailsButton(),
			);
		}

		#onSecurityCheckedChange = (id, checked) => {
			this.setState({ [id]: checked }, () => {
				this.props.onChange?.({
					...this.state,
				});
			});
		};

		#renderDetailsButton = () => {
			return View(
				{
					style: {
						alignItems: 'center',
					},
				},
				Link4({
					testId: `${this.testId}-details-link`,
					text: Loc.getMessage('M_COLLAB_CREATE_DETAILS_LINK'),
					ellipsize: Ellipsize.END,
					mode: LinkMode.SOLID,
					color: Color.base4,
					numberOfLines: 1,
					textDecorationLine: 'underline',
					style: {
						marginTop: Indent.XL3.toNumber(),
					},
					onClick: () => helpdesk.openHelpArticle(helpArticleCode),
				}),
			);
		};
	}

	module.exports = {
		CollabCreateSecurity: (props) => new CollabCreateSecurity(props),
		Security,
	};
});
