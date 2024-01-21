/**
 * @module crm/tunnel/tunnel-content
 */
jn.define('crm/tunnel/tunnel-content', (require, exports, module) => {
	const {
		selectById: selectStageById,
	} = require('crm/statemanager/redux/slices/stage-settings');
	const {
		getCrmKanbanUniqId,
		selectById: selectKanbanSettingsById,
	} = require('crm/statemanager/redux/slices/kanban-settings');
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { connect } = require('statemanager/redux/connect');

	const DEFAULT_STAGE_BACKGROUND_COLOR = AppTheme.colors.accentSoftBlue1;

	/**
	 * @class TunnelContent
	 */
	class TunnelContent extends PureComponent
	{
		get tunnel()
		{
			return BX.prop.getObject(this.props, 'tunnel', {});
		}

		get dstStageColor()
		{
			return BX.prop.getString(this.tunnel, 'dstStageColor', null);
		}

		get dstStageName()
		{
			return BX.prop.getString(this.tunnel, 'dstStageName', null);
		}

		get dstCategoryName()
		{
			return BX.prop.getString(this.tunnel, 'dstCategoryName', null);
		}

		get stageName()
		{
			return BX.prop.getString(this.props, 'stageName', null);
		}

		get categoryName()
		{
			return BX.prop.getString(this.props, 'categoryName', null);
		}

		get stageColor()
		{
			return BX.prop.getString(this.props, 'stageColor', null);
		}

		render()
		{
			const color = this.stageColor || this.dstStageColor;

			return View(
				{
					style: styles.tunnelContent,
				},
				Text(
					{
						text: BX.message('CRM_TUNNEL_TITLE'),
						style: styles.tunnelTitle,
					},
				),
				Image({
					style: styles.tunnelArrow,
					resizeMode: 'center',
					svg: {
						content: svgImages.tunnelArrow,
					},
				}),
				Image({
					style: styles.tunnelStageIcon,
					resizeMode: 'center',
					svg: {
						content: svgImages.tunnelStageIcon
							.replace(
								'#COLOR#',
								(color || DEFAULT_STAGE_BACKGROUND_COLOR).replaceAll(/[^\d#A-Fa-f]/g, ''),
							),
					},
				}),
				View(
					{
						style: styles.tunnelTextContainer,
					},
					Text(
						{
							text: this.stageName || this.dstStageName,
							numberOfLines: 1,
							ellipsize: 'end',
							style: styles.tunnelText,
						},
					),
					Text(
						{
							style: styles.tunnelTextSeparator,
							text: '/',
						},
					),
					Text(
						{
							style: styles.tunnelText,
							text: this.categoryName || this.dstCategoryName,
							numberOfLines: 1,
							ellipsize: 'end',
						},
					),
				),
			);
		}
	}

	const styles = {
		tunnelContent: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		tunnelTitle: {
			color: AppTheme.colors.base5,
			fontWeight: '600',
			fontSize: 12,
		},
		tunnelArrow: {
			width: 5,
			height: 8,
			marginHorizontal: 10,
		},
		tunnelStageIcon: {
			width: 13,
			height: 11,
			marginRight: 4,
		},
		tunnelTextContainer: {
			flexDirection: 'row',
			flex: 1,
		},
		tunnelText: {
			color: AppTheme.colors.accentMainLinks,
			flexWrap: 'no-wrap',
			maxWidth: '47%',
		},
		tunnelTextSeparator: {
			color: AppTheme.colors.accentMainLinks,
			flexWrap: 'no-wrap',
		},
	};

	const svgImages = {
		tunnelArrow: '<svg width="5" height="8" viewBox="0 0 5 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M0 6.56513L2.10294 4.5123L2.64763 4.00018L2.10294 3.48775L0 1.43493L0.742066 0.710546L4.11182 4L0.742066 7.28945L0 6.56513Z" fill="#A8ADB4"/></svg>',
		tunnelStageIcon: '<svg width="13" height="11" viewBox="0 0 13 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2C0 0.895431 0.895431 0 2 0L8.52745 0C9.22536 0 9.87278 0.3638 10.2357 0.959904L13 5.5L10.2357 10.0401C9.87278 10.6362 9.22536 11 8.52745 11H2C0.895432 11 0 10.1046 0 9V2Z" fill="#COLOR#"/></svg>',
	};

	const mapStateToProps = (state, { tunnel, entityTypeId }) => {
		const {
			dstStageId,
			dstCategoryId,
		} = tunnel;

		const {
			name: stageName,
			color: stageColor,
		} = selectStageById(state, dstStageId) || {};

		const {
			name: categoryName,
		} = selectKanbanSettingsById(state, getCrmKanbanUniqId(entityTypeId, dstCategoryId)) || {};

		return {
			stageName,
			categoryName,
			stageColor,
		};
	};

	module.exports = {
		TunnelContent: connect(mapStateToProps)(TunnelContent),
	};
});
