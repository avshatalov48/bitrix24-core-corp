(() => {
	const require = (ext) => jn.require(ext);

	const { isEqual, isEmpty } = require('utils/object');
	const { NavigationLoader } = require('navigation-loader');
	const { NotifyManager } = require('notify-manager');
	const { CategoryStorage } = require('crm/storage/category');
	const { CategorySvg } = require('crm/assets/category');
	const { CategoryPermissions } = require('crm/category-permissions');
	const { StageStorage } = require('crm/storage/stage');
	const { StageList } = require('crm/stage-list');
	const { Alert } = require('alert');
	const { Haptics } = require('haptics');
	const { throttle } = require('utils/function');
	const { stringify } = require('utils/string');
	const { EntityName } = require('crm/entity-name');

	const DEFAULT_PROCESS_STAGE_COLOR = '#c3f0ff';
	const DEFAULT_FAILED_STAGE_COLOR = '#ff5752';

	const SEMANTICS = {
		PROCESS: 'P',
		SUCCESS: 'S',
		FAILED: 'F',
	};

	class CrmCategoryDetailComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				category: this.getCategoryByProps(props),
			};

			this.changedFields = {};

			this.layoutClose = this.onLayoutClose.bind(this);

			this.onOpenStageDetail = throttle(this.openStageDetail, 500, this);
			this.deleteCategoryHandler = throttle(this.deleteCategory, 500, this);
			this.createStageAndOpenStageDetail = throttle((semantics) => {
				this.createStage(semantics).then((stage) => {
					this.openStageDetail(stage);
				});
			}, 500, this);

			this.scrollViewRef = null;
			this.categoryNameRef = null;
		}

		getCategoryByProps(props)
		{
			const { entityTypeId, categoryId } = props;

			return CategoryStorage.getCategory(entityTypeId, categoryId);
		}

		componentDidMount()
		{
			layout.enableNavigationBarBorder(false);
			layout.setRightButtons([
				{
					name: BX.message('M_CRM_CATEGORY_DETAIL_SAVE'),
					type: 'text',
					color: '#2066b0',
					callback: () => this.saveAndClose(),
				},
			]);
			layout.setTitle({
				text: this.getTitleForNavigation(),
				svg: {
					content: CategorySvg.funnelForTitle(),
				},
			});

			this.bindEvents();

			CategoryStorage
				.subscribeOnChange(() => this.reloadCategory())
				.subscribeOnLoading(({ status }) => NavigationLoader.setLoading(status))
				.markReady()
			;
		}

		bindEvents()
		{
			BX.addCustomEvent('Crm.StageDetail::onUpdateStage', this.onUpdateStage.bind(this));
			BX.addCustomEvent('Crm.StageDetail::onDeleteStage', this.onDeleteStage.bind(this));
		}

		reloadCategory()
		{
			const category = this.getCategoryByProps(this.props);

			return this.changeCategory(category);
		}

		changeCategory(category)
		{
			return new Promise((resolve) => {
				if (isEqual(this.state.category, category))
				{
					resolve();
				}
				else
				{
					this.setState({ category }, () => resolve());
				}
			});
		}

		getCategoryFields()
		{
			return {
				...this.state.category,
				...this.changedFields,
			};
		}

		saveAndClose()
		{
			const category = this.getCategoryFields();
			if (isEqual(this.state.category, category))
			{
				this.layoutClose();
			}
			else
			{
				this.save().then(this.layoutClose);
			}
		}

		save()
		{
			return new Promise((resolve, reject) => {
				const { entityTypeId } = this.props;
				const fields = this.getCategoryFields();

				if (fields.name.trim() === '')
				{
					Haptics.notifyWarning();

					if (this.scrollViewRef)
					{
						this.scrollViewRef.scrollToBegin(true);
					}

					if (this.categoryNameRef)
					{
						this.categoryNameRef.focus();
					}

					reject();
					return;
				}

				NotifyManager.showLoadingIndicator();

				CategoryStorage
					.updateCategory(entityTypeId, fields.id, this.getCategoryFields())
					.then(() => {
						NotifyManager.hideLoadingIndicator(true);
						resolve();
					})
					.catch((response) => {
						NotifyManager.showErrors(response.errors);
						reject();
					})
				;
			});
		}

		onLayoutClose()
		{
			const { category } = this.state;
			BX.postComponentEvent('Crm.CategoryDetail::onClose', [category]);
			layout.close();
		}

		getTitleForNavigation()
		{
			const category = this.getCategoryFields();

			if (isEmpty(category))
			{
				return BX.message('M_CRM_CATEGORY_DETAIL_FUNNEL_NOT_LOADED2');
			}

			if (!category.categoriesEnabled)
			{
				return category.name;
			}

			const name = stringify(category.name).trim();

			return (
				name === ''
					? BX.message('M_CRM_CATEGORY_DETAIL_FUNNEL_EMPTY2')
					: BX.message('M_CRM_CATEGORY_DETAIL_FUNNEL2').replace('#CATEGORY_NAME#', name)
			);
		}

		updateLayoutTitle()
		{
			const text = this.getTitleForNavigation();

			layout.setTitle({ text }, true);
		}

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
					style: {
						flexDirection: 'column',
						backgroundColor: '#eef2f4',
					},
				},
				this.state.category === null ? this.renderLoader() : this.renderContent(),
			);
		}

		renderLoader()
		{
			return new LoadingScreenComponent({ backgroundColor: '#eef2f4' });
		}

		renderContent()
		{
			this.updateLayoutTitle();

			return ScrollView(
				{
					ref: (ref) => this.scrollViewRef = ref,
					resizableByKeyboard: true,
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
					style: {
						flex: 1,
					},
				},
				View(
					{
						onClick: () => Keyboard.dismiss(),
						onPan: () => Keyboard.dismiss(),
					},
					this.renderCategoryName(),
					this.renderPermissions(),
					this.renderStageList(),
					this.renderStageButtons(),
					this.renderDeleteButton(),
				),
			);
		}

		renderCategoryName()
		{
			const { name, categoriesEnabled } = this.getCategoryFields();
			if (!categoriesEnabled)
			{
				return null;
			}

			return View(
				{
					style: styles.categoryNameContainer,
				},
				new EntityName({
					ref: (ref) => this.categoryNameRef = ref,
					title: BX.message('M_CRM_CATEGORY_DETAIL_NAME_FIELD_TITLE2'),
					name,
					placeholder: BX.message('M_CRM_CATEGORY_DETAIL_DEFAULT_CATEGORY_NAME2'),
					required: true,
					showRequired: false,
					config: {
						deepMergeStyles: styles.categoryNameField,
						selectionOnFocus: name === BX.message('M_CRM_CATEGORY_DETAIL_DEFAULT_CATEGORY_NAME2'),
						enableKeyboardHide: true,
					},
					onChange: (value) => {
						this.changedFields.name = value;
						this.updateLayoutTitle();
					},
				}),
			);
		}

		renderPermissions()
		{
			const { access, categoriesEnabled } = this.getCategoryFields();

			return new CategoryPermissions({
				access,
				categoriesEnabled,
				entityTypeId: this.props.entityTypeId,
				categoryId: this.props.categoryId,
				onChange: (access) => {
					this.changedFields.access = access;
				},
			});
		}

		renderStageList()
		{
			const category = this.getCategoryFields();
			const { processStages, successStages, failedStages, tunnelsEnabled } = category;

			return new StageList({
				category,
				processStages,
				finalStages: [...successStages, ...failedStages],
				onOpenStageDetail: this.onOpenStageDetail,
				readOnly: false,
				canMoveStages: true,
				onStageMove: (processStages) => {
					this.changedFields.processStages = processStages;
					this.setState({});
				},
				stageParams: {
					showTunnels: tunnelsEnabled,
				},
			});
		}

		renderDeleteButton()
		{
			const { category } = this.state;

			if (!category || !category.categoriesEnabled)
			{
				return null;
			}

			return new BaseButton({
				style: {
					button: styles.deleteButtonContainer(category.isDefault),
					icon: styles.deleteButtonIcon,
					text: styles.buttonText,
				},
				icon: svgImages.deleteIcon,
				text: BX.message('M_CRM_CATEGORY_DETAIL_DELETE2'),
				onClick: () => this.openAlertOnDeleteCategory(),
			});
		}

		renderStageButtons()
		{
			return View(
				{
					style: {
						borderRadius: 12,
						marginBottom: 8,
					},
				},
				this.renderCreateStageButton({
					buttonText: BX.message('M_CRM_CATEGORY_DETAIL_CREATE_PROCESS_STAGE'),
					onClick: () => {
						this.createStageAndOpenStageDetail(SEMANTICS.PROCESS);
					},
				}),
				this.renderCreateStageButton({
					buttonText: BX.message('M_CRM_CATEGORY_DETAIL_CREATE_FAILED_STAGE'),
					onClick: () => {
						this.createStageAndOpenStageDetail(SEMANTICS.FAILED);
					},
				}),
			);
		}

		renderCreateStageButton({ buttonText, onClick })
		{
			return new BaseButton({
				style: {
					button: styles.createButtonContainer,
					icon: styles.createButtonIcon,
					text: styles.buttonText,
				},
				icon: svgImages.createIcon,
				text: buttonText,
				onClick,
			});
		}

		createStage(stageSemantics)
		{
			const stage = new Stage({ semantics: stageSemantics });
			stage.sort = this.getDefaultNewSort(stage.type);

			const { name, sort, semantics, color } = stage;

			return new Promise((resolve, reject) => {
				NotifyManager.showLoadingIndicator();

				StageStorage
					.createStage(this.props.entityTypeId, this.state.category.id, {
						name,
						sort,
						semantics,
						color,
					})
					.then((stage) => {
						const stageEntity = new Stage(stage);

						if (this.changedFields[stageEntity.type])
						{
							this.changedFields[stageEntity.type] = [
								...this.changedFields[stageEntity.type],
								stageEntity.toStorageJson(),
							];
						}

						NotifyManager.hideLoadingIndicator(
							true,
							BX.message('M_CRM_CATEGORY_DETAIL_SUCCESS_CREATION'),
							1000,
						);
						setTimeout(() => resolve(stage), 1300);
					})
					.catch((response) => {
						NotifyManager.showErrors(response.errors);
						reject();
					})
				;
			});
		}

		deleteCategory(categoryId)
		{
			return new Promise((resolve, reject) => {
				const { entityTypeId } = this.props;

				NotifyManager.showLoadingIndicator();

				CategoryStorage
					.deleteCategory(entityTypeId, this.state.category.id)
					.then(() => {
						BX.postComponentEvent('Crm.CategoryDetail::onDeleteCategory', [categoryId]);
						NotifyManager.hideLoadingIndicator(true);
						resolve();
					})
					.catch((response) => {
						NotifyManager.showErrors(response.errors);
						reject();
					})
				;
			});
		}

		openStageDetail(stage)
		{
			const { id: categoryId, tunnelsEnabled, documentFields } = this.getCategoryFields();

			ComponentHelper.openLayout({
				name: 'crm:crm.stage.detail',
				componentParams: {
					entityTypeId: this.props.entityTypeId,
					stage,
					categoryId,
					tunnelsEnabled,
					documentFields,
				},
				widgetParams: {
					modal: true,
					backgroundColor: '#eef2f4',
					backdrop: {
						showOnTop: true,
						forceDismissOnSwipeDown: true,
						horizontalSwipeAllowed: false,
						swipeContentAllowed: true,
						navigationBarColor: '#eef2f4',
					},
				},
			});
		}

		getDefaultNewSort(stageType)
		{
			const category = this.getCategoryFields();
			const lastStage = category[stageType].length - 1;

			return category[stageType][lastStage].sort + 1;
		}

		openAlertOnDeleteCategory()
		{
			const { category } = this.state;

			if (category.isDefault)
			{
				const title = BX.message('M_CRM_CATEGORY_DETAIL_IS_DEFAULT_TITLE2');
				const text = BX.message('M_CRM_CATEGORY_DETAIL_IS_DEFAULT_TEXT');

				Haptics.notifyWarning();
				Notify.showUniqueMessage(text, title, { time: 5 });
			}
			else
			{
				Alert.confirm(
					'',
					BX.message('M_CRM_CATEGORY_DETAIL_DELETE_CATEGORY2'),
					[
						{
							type: 'cancel',
						},
						{
							text: BX.message('M_CRM_CATEGORY_DETAIL_DELETE_CATEGORY_OK'),
							type: 'destructive',
							onPress: () => this.deleteCategoryHandler(category.id).then(this.layoutClose),
						},
					],
				);
			}
		}

		onUpdateStage(stageData)
		{
			const category = this.getCategoryFields();
			const stage = new Stage(stageData);
			const stageIndex = category[stage.type].findIndex((item) => item.id === stage.id);

			if (stageIndex !== -1)
			{
				const modifiedStages = [...category[stage.type]];
				modifiedStages[stageIndex] = stage.toStorageJson();

				this.changedFields[stage.type] = modifiedStages;

				this.setState({}, () => this.getCategoryByProps(this.props));
			}
		}

		onDeleteStage(stageData)
		{
			const category = this.getCategoryFields();
			const stage = new Stage(stageData);
			const stageIndex = category[stage.type].findIndex((item) => item.id === stage.id);
			const modifiedStages = [...category[stage.type]];

			modifiedStages.splice(stageIndex, 1);

			this.changedFields[stage.type] = modifiedStages;

			this.setState({}, () => this.getCategoryByProps(this.props));
		}
	}

	const STAGE_TYPE = {
		PROCESS_STAGES: 'processStages',
		SUCCESS_STAGES: 'successStages',
		FAILED_STAGES: 'failedStages',
	};

	class Stage
	{
		constructor({ id, name, sort, color, semantics, type, tunnels, statusId, total, currency, count })
		{
			this.id = id;
			this.name = name || BX.message('M_CRM_CATEGORY_DETAIL_DEFAULT_STAGE_NAME2');
			this.sort = sort || 0;
			this.semantics = semantics || SEMANTICS.PROCESS;
			this.color = this.getColorByProps(color) || this.getColorBySemantics(semantics);
			this.type = type === undefined ? Stage.getType(this.semantics) : type;
			this.tunnels = tunnels || [];
			this.statusId = statusId || null;
			this.total = total;
			this.currency = currency || null;
			this.count = count;
		}

		static getType(semantics)
		{
			if (semantics === SEMANTICS.FAILED)
			{
				return STAGE_TYPE.FAILED_STAGES;
			}
			if (semantics === SEMANTICS.SUCCESS)
			{
				return STAGE_TYPE.SUCCESS_STAGES;
			}

			return STAGE_TYPE.PROCESS_STAGES;
		}

		getColorByProps(color)
		{
			return color && color.length > 0 ? color : null;
		}

		getColorBySemantics(semantics)
		{
			return semantics === SEMANTICS.PROCESS ? DEFAULT_PROCESS_STAGE_COLOR : DEFAULT_FAILED_STAGE_COLOR;
		}

		toStorageJson()
		{
			return {
				id: this.id,
				name: this.name,
				semantics: this.semantics,
				color: this.color,
				statusId: this.statusId,
				sort: this.sort,
				count: this.count,
				total: this.total,
				currency: this.currency,
				tunnels: this.tunnels,
			};
		}
	}

	const styles = {
		categoryNameContainer: {
			marginBottom: 8,
		},
		categoryNameField: {
			wrapper: {
				paddingTop: 0,
				paddingBottom: 0,
			},
			title: {
				marginBottom: 0,
			},
		},
		deleteButtonContainer: (disabled) => ({
			paddingTop: 18,
			paddingBottom: 18,
			paddingLeft: 25,
			paddingRight: 25,
			borderRadius: 12,
			backgroundColor: '#fff',
			borderColor: '#fff',
			flexDirection: 'row',
			alignItems: 'center',
			height: 'auto',
			justifyContent: 'flex-start',
			marginBottom: 8,
			opacity: disabled ? 0.6 : 1,
		}),
		deleteButtonIcon: {
			width: 15,
			height: 20,
			marginRight: 16,
		},
		buttonText: {
			color: '#333',
			fontSize: 18,
			fontWeight: 'normal',
		},
		createButtonContainer: {
			paddingTop: 18,
			paddingBottom: 18,
			paddingLeft: 25,
			paddingRight: 25,
			borderRadius: 0,
			backgroundColor: '#fff',
			borderColor: '#fff',
			flexDirection: 'row',
			alignItems: 'center',
			height: 'auto',
			justifyContent: 'flex-start',
		},
		createButtonIcon: {
			width: 12,
			height: 12,
			marginRight: 22,
		},
	};

	const svgImages = {
		deleteIcon: '<svg width="16" height="21" viewBox="0 0 16 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.22602 0H6.15062V1.54677H1.43631C0.64306 1.54677 0 2.18983 0 2.98309V4.64037H15.377V2.98309C15.377 2.18983 14.7339 1.54677 13.9407 1.54677H9.22602V0Z" fill="#828B95"/><path d="M1.53777 6.18721H13.8394L12.6864 19.2351C12.6427 19.7294 12.2287 20.1084 11.7326 20.1084H3.64459C3.14842 20.1084 2.73444 19.7294 2.69077 19.2351L1.53777 6.18721Z" fill="#828B95"/></svg>',
		createIcon: '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 0H7V12H5V0Z" fill="#525C69"/><path d="M12 5V7L0 7L1.19209e-07 5L12 5Z" fill="#525C69"/></svg>',
	};

	BX.onViewLoaded(() => {
		layout.showComponent(new CrmCategoryDetailComponent({
			entityTypeId: BX.componentParameters.get('entityTypeId'),
			categoryId: BX.componentParameters.get('categoryId'),
			sort: BX.componentParameters.get('sort'),
			categories: BX.componentParameters.get('categories'),
		}));
	});
})();
