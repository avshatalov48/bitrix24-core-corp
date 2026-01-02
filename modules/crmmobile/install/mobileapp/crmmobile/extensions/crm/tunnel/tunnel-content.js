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
	const { Color, Indent } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');

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
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text5(
					{
						text: BX.message('CRM_TUNNEL_TITLE'),
						color: Color.base4,
						numberOfLines: 1,
						ellipsize: 'end',
					},
				),
				IconView({
					testId: 'crm-tunnel-arrow',
					size: 16,
					color: Color.base4,
					icon: Icon.CHEVRON_TO_THE_RIGHT_SIZE_S,
				}),
				Image({
					style: {
						width: 13,
						height: 11,
						marginRight: Indent.XS.toNumber(),
					},
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
						style: {
							flexDirection: 'row',
							flex: 1,
						},
					},
					Text5(
						{
							text: this.stageName || this.dstStageName,
							color: Color.accentMainLinks,
							style: {
								flexWrap: 'no-wrap',
								maxWidth: '47%',
							},
							numberOfLines: 1,
							ellipsize: 'end',
						},
					),
					Text5(
						{
							color: Color.accentMainLinks,
							style: {
								flexWrap: 'no-wrap',
							},
							text: '(',
						},
					),
					Text5(
						{
							text: this.categoryName || this.dstCategoryName,
							color: Color.accentMainLinks,
							style: {
								flexWrap: 'no-wrap',
								maxWidth: '47%',
							},
							numberOfLines: 1,
							ellipsize: 'end',
						},
					),
					Text5(
						{
							color: Color.accentMainLinks,
							style: {
								flexWrap: 'no-wrap',
							},
							text: ')',
						},
					),
				),
			);
		}
	}

	const svgImages = {
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
