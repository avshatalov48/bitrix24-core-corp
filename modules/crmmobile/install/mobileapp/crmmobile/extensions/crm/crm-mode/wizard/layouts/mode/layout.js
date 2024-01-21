/**
 * @module crm/crm-mode/wizard/layouts/mode/layout
 */
jn.define('crm/crm-mode/wizard/layouts/mode/layout', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const { UIScrollView } = require('layout/ui/scroll-view');
	const AppTheme = require('apptheme');
	const { BackdropHeader } = require('layout/ui/banners');
	const { ModeBlock } = require('crm/crm-mode/wizard/layouts/mode/block');
	const { MODES } = require('crm/crm-mode/wizard/layouts/src/constants');
	const { BannerImage } = require('crm/crm-mode/wizard/layouts/src/images');

	/**
	 * @class ModeLayout
	 */
	class ModeLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				active: props.mode,
			};
		}

		getActiveMode()
		{
			const { active } = this.state;

			return active;
		}

		handleOnClickMode(mode)
		{
			const { onChange } = this.props;

			if (mode === this.getActiveMode())
			{
				return;
			}

			Alert.confirm(
				Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONFIRM_TITLE'),
				Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONFIRM_DESCRIPTION'),
				[
					{
						text: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONFIRM_SAVE'),
						type: 'default',
						onPress: () => {
							this.setState(
								{ active: mode },
								() => {
									onChange({ mode });
								},
							);
						},
					},
					{
						text: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONFIRM_CANCEL'),
						type: 'cancel',
					},
				],
			);
		}

		renderModeBlock(mode)
		{
			return new ModeBlock({
				active: mode === this.getActiveMode(),
				mode,
				onClick: this.handleOnClickMode.bind(this),
			});
		}

		render()
		{
			return UIScrollView(
				{
					style: {
						height: '100%',
					},
					children: [
						View(
							{
								style: {
									marginBottom: 12,
									borderRadius: 12,
								},
							},
							BackdropHeader({
								title: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_MODE_HEADER_TITLE'),
								description: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_MODE_HEADER_DESCRIPTION'),
								image: BannerImage('mode'),
								position: 'flex-start',
							}),
						),
						View(
							{
								style: {
									backgroundColor: AppTheme.colors.bgContentPrimary,
									paddingTop: 14,
									paddingBottom: 12,
									paddingHorizontal: 12,
									borderRadius: 12,
								},
							},
							Text(
								{
									text: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_MODE_HEADER'),
									style: {
										color: AppTheme.colors.base1,
										fontSize: 16,
										marginBottom: 14,
									},
								},
							),
							this.renderModeBlock(MODES.simple),
							this.renderModeBlock(MODES.classic),
						),
					],
				},
			);
		}
	}

	module.exports = { ModeLayout };
});
