/**
 * @module layout/ui/fields/crm-stage
 */
jn.define('layout/ui/fields/crm-stage', (require, exports, module) => {

	const { BaseField } = require('layout/ui/fields/base');

	let StageSelector, AnimationMode;

	try
	{
		StageSelector = jn.require('crm/stage-selector').StageSelector;
		AnimationMode = jn.require('crm/stage-selector').AnimationMode;
	}
	catch (e)
	{
		console.warn(e);

		return;
	}

	/**
	 * @class CrmStageField
	 */
	class CrmStageField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.handleChange = this.handleChange.bind(this);
			this.afterContentStyles = null;
			this.forceUpdateHandler = this.forceUpdate.bind(this);
		}

		get animationMode()
		{
			return BX.prop.getString(this.getConfig(), 'animationMode', AnimationMode.UPDATE_BEFORE_ANIMATION);
		}

		getConfig()
		{
			const config = super.getConfig();
			if (config.deepMergeStyles && config.deepMergeStyles.wrapper)
			{
				this.afterContentStyles = config.deepMergeStyles.wrapper;
			}

			return {
				...config,
				entityTypeId: BX.prop.getInteger(config, 'entityTypeId', null),
				categoryId: BX.prop.getInteger(config, 'categoryId', null),
				data: BX.prop.getObject(config, 'data', {}),
				uid: BX.prop.getString(config, 'uid', null),
				useStageChangeMenu: BX.prop.getBoolean(config, 'useStageChangeMenu', false),
				showReadonlyNotification: BX.prop.getBoolean(config, 'showReadonlyNotification', false),
				deepMergeStyles: {
					externalWrapper: {
						borderBottomWidth: 0,
						paddingBottom: 0,
					},
				},
			};
		}

		canFocusTitle()
		{
			return BX.prop.getBoolean(this.props, 'canFocusTitle', false);
		}

		getContentClickHandler()
		{
			return null;
		}

		// workaround to focus if already focused
		setFocus()
		{
			return this.setFocusInternal();
		}

		renderReadOnlyContent()
		{
			return this.renderEditableContent();
		}

		renderEditableContent()
		{
			const {
				entityTypeId,
				categoryId,
				data,
				uid,
				useStageChangeMenu,
				showReadonlyNotification,
			} = this.getConfig();

			return View(
				{
					style: {
						flex: 1,
					},
				},
				new StageSelector({
					activeStageId: this.getValue(),
					readOnly: this.isReadOnly(),
					entityTypeId,
					categoryId,
					data,
					uid,
					useStageChangeMenu,
					showReadonlyNotification,
					onStageSelect: this.handleChange,
					hasHiddenEmptyView: this.hasHiddenEmptyView(),
					showBorder: this.showBorder(),
					borderColor: this.getExternalWrapperBorderColor(),
					forceUpdate: this.forceUpdateHandler,
					animationMode: this.animationMode,
				}),
				View(
					{
						style: {
							height: this.afterContentStyles ? this.afterContentStyles.borderBottomWidth : 0,
							paddingBottom: this.isReadOnly() ? 3 : 0,
							marginLeft: 16,
							marginRight: 16,
							...this.afterContentStyles,
						},
					},
				),
			);
		}

		forceUpdate(params)
		{
			if (this.props.forceUpdate)
			{
				this.props.forceUpdate(params);
			}
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				externalWrapper: {},
				wrapper: {
					...styles.wrapper,
					marginLeft: 0,
					marginRight: 0,
					paddingBottom: 0,
				},
				readOnlyWrapper: {
					...styles.readOnlyWrapper,
					marginLeft: 0,
					marginRight: 0,
					paddingBottom: 0,
					paddingTop: 0,
				},
				title: {
					...styles.title,
					marginLeft: 16,
				},
			};
		}
	}

	module.exports = {
		CrmStageType: 'crm-stage',
		CrmStageField: (props) => new CrmStageField(props),
	};

});
