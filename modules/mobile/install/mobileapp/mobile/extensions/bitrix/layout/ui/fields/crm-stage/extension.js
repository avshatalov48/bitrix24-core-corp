/**
 * @module layout/ui/fields/crm-stage
 */
jn.define('layout/ui/fields/crm-stage', (require, exports, module) => {
	const { ShimmerView } = require('layout/polyfill');
	const { BaseField } = require('layout/ui/fields/base');

	let StageSelector, AnimationMode, CategoryStorage;

	try
	{
		StageSelector = require('crm/stage-selector').StageSelector;
		AnimationMode = require('crm/stage-selector').AnimationMode;
		CategoryStorage = require('crm/storage/category').CategoryStorage;
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

			this.state.isCategoryEmpty = this.isCategoryEmpty(this.props);
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			this.state.isCategoryEmpty = this.isCategoryEmpty(newProps);
		}

		componentDidMount()
		{
			super.componentDidMount();

			CategoryStorage
				.subscribeOnChange(() => this.reloadCategory())
				.markReady()
			;
		}

		isCategoryEmpty(props)
		{
			const entityTypeId = BX.prop.getInteger(props.config, 'entityTypeId', null);
			const categoryId = BX.prop.getInteger(props.config, 'categoryId', null);

			if (entityTypeId === null || categoryId === null)
			{
				return true;
			}

			return CategoryStorage.getCategory(entityTypeId, categoryId) === null;
		}

		reloadCategory()
		{
			const isCategoryEmptyNow = this.isCategoryEmpty(this.props);

			if (this.state.isCategoryEmpty !== isCategoryEmptyNow)
			{
				this.setState({
					isCategoryEmpty: isCategoryEmptyNow,
				});
			}
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
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.renderStages(),
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

		renderStages()
		{
			if (this.state.isCategoryEmpty)
			{
				return this.renderStub();
			}

			return this.renderStageSelector();
		}

		renderStageSelector()
		{
			const {
				entityTypeId,
				categoryId,
				entityId,
				isNewEntity,
				data,
				uid: configUid,
				useStageChangeMenu,
				showReadonlyNotification,
			} = this.getConfig();

			return new StageSelector({
				activeStageId: this.getValue(),
				readOnly: this.isReadOnly(),
				entityTypeId,
				entityId,
				categoryId,
				isNewEntity,
				data,
				uid: configUid || this.uid,
				useStageChangeMenu,
				showReadonlyNotification,
				onStageSelect: this.handleChange,
				hasHiddenEmptyView: this.hasHiddenEmptyView(),
				showBorder: this.showBorder(),
				borderColor: this.getExternalWrapperBorderColor(),
				forceUpdate: this.forceUpdateHandler,
				animationMode: this.animationMode,
			});
		}

		renderStub()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						width: '100%',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'flex-start',
							marginTop: 7,
							marginBottom: 9,
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								marginRight: 6,
								paddingLeft: 18,
							},
						},
						this.renderLine(170, 34),
						Image({
							style: {
								width: 25,
								height: 34,
								marginLeft: -10,
							},
							svg: {
								content: '<svg width="25" height="34" viewBox="0 0 25 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 6C0 2.68629 2.68629 0 6 0H12.1383C14.2817 0 16.2623 1.1434 17.3342 2.99956L24.5526 15.4998C25.0887 16.4281 25.0887 17.5719 24.5526 18.5002L17.3342 31.0004C16.2623 32.8566 14.2817 34 12.1383 34H6C2.68629 34 0 31.3137 0 28V6Z" fill="#dfe0e3"/></svg>',
							},
						}),
					),
					View(
						{
							style: {
								flexDirection: 'row',
								marginLeft: 5,
							},
						},
						this.renderLine('100%', 34),
					),
				),
				View(
					{
						style: {
							marginHorizontal: 16,
							padding: 4,
							flexDirection: 'row',
							flex: 1,
							borderBottomWidth: 1,
							borderBottomColor: this.showBorder() ? this.getExternalWrapperBorderColor() : null,
						},
					},
				),
			);
		}

		renderLine(width, height)
		{
			return View({
				style: {
					width,
					height,
					borderRadius: 3,
					backgroundColor: '#dfe0e3',
				},
			});
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
