/**
 * @module layout/ui/entity-editor/control/section
 */
jn.define('layout/ui/entity-editor/control/section', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { transition } = require('animation');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { ToggleButton } = require('layout/ui/entity-editor/control/section/toggle-button');
	const { EntityEditorBaseControl } = require('layout/ui/entity-editor/control/base');
	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');
	const { EntityEditorMode } = require('layout/ui/entity-editor/editor-enum/mode');

	const VIEW_MODE_SECTION_BACKGROUND_COLOR = AppTheme.colors.bgContentPrimary;
	const EDIT_MODE_SECTION_BACKGROUND_COLOR = AppTheme.colors.bgContentSecondary;

	/**
	 * @class EntityEditorSection
	 */
	class EntityEditorSection extends EntityEditorBaseControl
	{
		constructor(props)
		{
			super(props);

			/** @type {Map<String, EntityEditorField>} */
			this.fields = new Map();

			this.fieldsContainerOpacity = 1;
			this.fieldsContainerRef = null;

			this.openQrPopup = this.openQrPopup.bind(this);
			this.enableToggling = this.getEnableToggling();
			this.onToggleButtonClick = this.onToggleClick.bind(this);
		}

		componentWillUnmount()
		{
			this.fields.clear();
		}

		initializeStateFromModel()
		{
			this.enableToggling = this.getEnableToggling();
		}

		getEnableToggling()
		{
			return this.isModeToggleEnabled() && this.schemeElement.getDataBooleanParam('enableToggling', true);
		}

		isModeToggleEnabled()
		{
			return this.editor.isModeToggleEnabled();
		}

		/**
		 * @returns {EntityEditorBaseControl[]}
		 */
		getControls()
		{
			return [...this.fields.values()].filter((field) => field instanceof EntityEditorBaseControl);
		}

		getTitle()
		{
			return this.schemeElement.getTitle();
		}

		render()
		{
			const hasRenderedFields = (renderedView) => {
				return renderedView.children && renderedView.children.length;
			};

			const renderedFields = this.renderFields();
			if (!renderedFields || !hasRenderedFields(renderedFields))
			{
				return null;
			}

			return View(
				{
					style: styles.sectionWrapper,
				},
				View(
					{
						style: styles.sectionContainer(this.state.mode, this.getDataBooleanParam('showBorder', false)),
					},
					this.renderTitleBar(),
					renderedFields,
					this.renderSectionManaging(),
				),
			);
		}

		renderTitleBar()
		{
			if (!this.isModeToggleEnabled() && this.getTitle().length === 0)
			{
				return null;
			}

			return View(
				{
					style: styles.titleBarContainer,
				},
				this.renderTitle(),
				this.renderToggleModeButton(),
			);
		}

		renderTitle()
		{
			const title = this.getTitle().toLocaleUpperCase(env.languageId);

			return View(
				{
					style: styles.titleContainer,
				},
				title && Text({
					style: styles.titleText,
					numberOfLines: 1,
					ellipsize: 'end',
					text: String(title),
				}),
			);
		}

		renderToggleModeButton()
		{
			return new ToggleButton({
				ref: (ref) => this.toggleButton = ref,
				onToggleClick: this.onToggleButtonClick,
				isShown: this.enableToggling && !((this.isChanged || this.editor.isNew) && this.isInEditMode()),
				text: this.state.mode === EntityEditorMode.view
					? BX.message('ENTITY_EDITOR_SECTION_EDIT_TITLE')
					: BX.message('ENTITY_EDITOR_SECTION_CANCEL_TITLE'),
			});
		}

		onToggleClick()
		{
			if (!this.enableToggling)
			{
				return;
			}

			const fadeOut = transition(this.fieldsContainerRef, {
				opacity: 0,
				duration: 300,
				option: 'easeIn',
			});
			const fadeIn = transition(this.fieldsContainerRef, {
				opacity: 1,
				duration: 600,
				option: 'easeOut',
			});

			const mode = (
				this.state.mode === EntityEditorMode.view
					? EntityEditorMode.edit
					: EntityEditorMode.view
			);

			FocusManager
				.blurFocusedFieldIfHas()
				.then(() => {
					fadeOut();
					this.fieldsContainerOpacity = 0;

					return this.editor.switchControlMode(this, mode);
				})
				.then(() => {
					fadeIn();
					this.fieldsContainerOpacity = 1;
				})
			;
		}

		renderFieldsAndSaveRefs()
		{
			return this.renderFromModel((ref) => {
				if (ref)
				{
					this.fields.set(ref.getId(), ref);
				}
			});
		}

		renderFromModel(ref)
		{
			let elements = this.schemeElement ? this.schemeElement.getElements() : [];

			if (this.editor.isNew)
			{
				elements = elements.filter((element) => {
					if (element.getType() === 'product_row_summary')
					{
						return true;
					}

					return element.isEditable() || element.isRequired() || element.isShowNew();
				});
			}

			const reversedElements = elements.reverse();
			const findPreviousElementToShow = (fields) => {
				for (const field of fields)
				{
					if (field && field.isVisible())
					{
						return field;
					}
				}
			};

			return (
				reversedElements
					.reduce((renderedFields, element, index) => {
						const previousElementToShow = findPreviousElementToShow(renderedFields);
						const showBorder = this.hasVisibleBorder(previousElementToShow, element);

						return [
							this.renderElementFromModel(element, (controlRef) => ref(controlRef, index), showBorder),
							...renderedFields,
						];
					}, [])
					.filter((element) => element));
		}

		hasVisibleBorder(previousField, element)
		{
			if (element.getDataBooleanParam('hasSolidBorder', false))
			{
				return true;
			}

			if (previousField && previousField instanceof EntityEditorField)
			{
				return !previousField.hasSolidBorderContainer();
			}

			return this.editor.canChangeScheme();
		}

		renderElementFromModel(schemeElement, ref, showBorder)
		{
			return this.editor.renderControl(
				ref,
				schemeElement.getType(),
				schemeElement.getName(),
				this.uid,
				{
					parent: this,
					schemeElement,
					model: this.model,
					readOnly: this.readOnly,
					isChanged: this.isChanged,
					mode: this.state.mode,
					showBorder,
					analytics: this.editor.getAnalytics(),
				},
			);
		}

		renderFields()
		{
			return View(
				{
					ref: (ref) => this.fieldsContainerRef = ref,
					style: {
						opacity: this.fieldsContainerOpacity,
					},
				},
				...this.renderFieldsAndSaveRefs()
					.map((field) => {
						if (field instanceof EntityEditorField)
						{
							return field;
						}

						const showBorder = BX.prop.getBoolean(field.props.settings, 'showBorder', true);

						return View(
							{
								style: styles.fieldDefaultWrapper(field.isVisible(), showBorder),
							},
							field,
						);
					}),
			);
		}

		renderSectionManaging()
		{
			if (!this.editor.canChangeScheme() || !this.getDataBooleanParam('showButtonPanel', true))
			{
				return null;
			}

			return View(
				{
					style: styles.sectionManagingContainer,
				},
				View(
					{
						style: styles.sectionManagingSeparator,
					},
					View(
						{
							style: {
								...styles.sectionManagingTextContainer,
								marginRight: 12,
							},
							onClick: this.openQrPopup,
						},
						View(
							{
								style: styles.sectionManagingTextWrapper,
							},
							Text(
								{
									style: styles.sectionManagingText,
									text: BX.message('ENTITY_EDITOR_SECTION_SELECT_FIELD'),
								},
							),
						),
					),
					View(
						{
							style: styles.sectionManagingTextContainer,
							onClick: this.openQrPopup,
						},
						View(
							{
								style: styles.sectionManagingTextWrapper,
							},
							Text(
								{
									style: styles.sectionManagingText,
									text: BX.message('ENTITY_EDITOR_SECTION_ADD_FIELD'),
								},
							),
						),
					),
				),
				View(
					{
						style: styles.sectionManagingTextContainer,
						onClick: this.openQrPopup,
					},
					View(
						{
							style: styles.sectionManagingTextWrapper,
						},
						Text(
							{
								style: styles.sectionManagingText,
								text: BX.message('ENTITY_EDITOR_SECTION_FIELD_SETTINGS'),
							},
						),
					),
				),
			);
		}

		openQrPopup()
		{
			const pathToExtension = `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/entity-editor/control/section/`;
			const imagePath = `${pathToExtension}images/settings.png`;

			this.settingsMenu = new ContextMenu({
				banner: {
					featureItems: [
						BX.message('ENTITY_EDITOR_SECTION_FIELDS_SETTINGS_DETAIL'),
						BX.message('ENTITY_EDITOR_SECTION_FIELDS_SETTINGS_VISIBILITY'),
						BX.message('ENTITY_EDITOR_SECTION_FIELDS_SETTINGS_SORT'),
						BX.message('ENTITY_EDITOR_SECTION_FIELDS_SETTINGS_DEPENDENCY'),
					],
					imagePath,
					qrauth: {
						redirectUrl: this.editor.getEntityDetailsUrl(),
						analyticsSection: this.editor.getAnalytics()?.analyticsSection || '',
					},
				},
				params: {
					title: BX.message('ENTITY_EDITOR_SECTION_FIELDS_SETTINGS_TITLE'),
				},
			});

			this.settingsMenu.show(PageManager);
		}

		markAsChanged()
		{
			super.markAsChanged();

			if (this.isInEditMode() && this.enableToggling)
			{
				this.enableToggling = false;
				this.toggleButton.hide();
			}
		}
	}

	const styles = {
		sectionWrapper: {
			flexDirection: 'column',
			paddingBottom: 12,
		},
		sectionContainer: (mode, showBorder) => ({
			borderRadius: 12,
			backgroundColor: mode === EntityEditorMode.edit
				? EDIT_MODE_SECTION_BACKGROUND_COLOR
				: VIEW_MODE_SECTION_BACKGROUND_COLOR,
			borderWidth: showBorder ? 1 : 0,
			borderColor: AppTheme.colors.bgSeparatorPrimary,
		}),
		titleBarContainer: {
			flexDirection: 'row',
			justifyContent: 'space-between',
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
			borderBottomWidth: 0.5,
			marginHorizontal: 16,
			marginBottom: 6,
		},
		titleContainer: {
			flexDirection: 'row',
			flex: 1,
			alignItems: 'center',
			paddingTop: 13.5,
			paddingBottom: 9.5,
		},
		titleText: {
			color: AppTheme.colors.base2,
			fontSize: 11,
			fontWeight: '600',
		},
		titleIconContainer: {
			width: 16,
			height: 16,
			justifyContent: 'center',
			alignItems: 'center',
			marginLeft: 2,
		},
		titleIcon: {
			width: 10,
			height: 10,
		},
		sectionManagingContainer: {
			paddingLeft: 16,
			paddingRight: 16,
			flexDirection: 'row',
			justifyContent: 'space-between',
		},
		sectionManagingSeparator: {
			flexDirection: 'row',
			flex: 1,
		},
		sectionManagingTextContainer: {
			paddingTop: 8,
			paddingBottom: 17,
		},
		sectionManagingTextWrapper: {
			borderBottomWidth: 1,
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 3,
		},
		sectionManagingText: {
			color: AppTheme.colors.base4,
			fontSize: 13,
		},
		fieldDefaultWrapper: (visible, showBorder) => ({
			display: visible ? 'flex' : 'none',
			marginBottom: 6,
			marginLeft: 16,
			marginRight: 16,
			borderBottomWidth: showBorder ? 0.5 : 0,
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
		}),
	};

	EntityEditorSection.Styles = styles;

	module.exports = { EntityEditorSection };
});
