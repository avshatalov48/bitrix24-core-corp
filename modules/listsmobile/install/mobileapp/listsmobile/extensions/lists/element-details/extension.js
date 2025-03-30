/**
 * @module lists/element-details
*/
jn.define('lists/element-details', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { EventEmitter } = require('event-emitter');
	const { inAppUrl } = require('in-app-url');
	const { NotifyManager } = require('notify-manager');
	const { Haptics } = require('haptics');
	const { Alert } = require('alert');
	const { PureComponent } = require('layout/pure-component');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { EntityManager } = require('layout/ui/entity-editor/manager');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { Line } = require('utils/skeleton');

	class ElementDetails extends PureComponent
	{
		static open(props, layout = PageManager)
		{
			layout.openWidget('layout', {
				modal: true,
				titleParams: {
					text: props.title || Loc.getMessage('M_LISTS_ELEMENT_DETAILS_WIDGET_TITLE'),
					textColor: AppTheme.colors.base1,
					type: 'dialog',
				},
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					mediumPositionPercent: 90,
					onlyMediumPosition: true,
					swipeAllowed: true,
					swipeContentAllowed: true,
					horizontalSwipeAllowed: false,
					hideNavigationBar: false,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
				onReady: (readyLayout) => {
					readyLayout.showComponent(new ElementDetails({
						layout: readyLayout,
						elementId: props.elementId || 0,
						isEmbedded: props.isEmbedded || false,
						isNeedShowSkeleton: props.isNeedShowSkeleton !== false,
						uid: props.uid || null,
						interceptExit: props.interceptExit !== false,
					}));
				},
			});
		}

		constructor(props)
		{
			super(props);

			this.state = {
				iBlockName: null,
				elementName: null,
				iBlockDescription: null,
				editorConfig: null,
				hasBPParametersOnStartUp: false,
				signedBpDocument: null,
				perms: {
					canEdit: false,
					canRead: false,
				},
			};

			this.elementId = props.elementId || -1;

			this.isLoaded = false;
			this.isClosing = false;
			this.isChanged = false;

			this.editorRef = null;
			this.isReRenderEditor = false;

			this.scrollViewRef = null;
			this.scrollY = 0;

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.isEmbedded = props.isEmbedded || false;
			this.isNeedShowSkeleton = props.isNeedShowSkeleton !== false;
			this.interceptExit = props.interceptExit !== false;

			this.handleChangeFields = this.handleChangeFields.bind(this);
			this.handleExit = this.handleExit.bind(this);
			this.handleScrollToInvalidField = this.handleScrollToInvalidField.bind(this);
			this.handleScrollToFocusedField = this.handleScrollToFocusedField.bind(this);
		}

		get layout()
		{
			return this.props.layout;
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.customEventEmitter.on('UI.EntityEditor.Field::onChangeState', this.handleChangeFields);

			if (!this.isEmbedded)
			{
				this.customEventEmitter
					.on('UI.EntityEditor::onScrollToInvalidField', this.handleScrollToInvalidField)
					.on('UI.EntityEditor::onScrollToFocusedField', this.handleScrollToFocusedField)
				;
			}

			if (this.interceptExit)
			{
				this.layout.preventBottomSheetDismiss(true);
				this.layout.on('preventDismiss', this.handleExit);
			}

			this.loadDetails();
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			this.customEventEmitter.off('UI.EntityEditor.Field::onChangeState', this.handleChangeFields);

			if (!this.isEmbedded)
			{
				this.customEventEmitter
					.off('UI.EntityEditor::onScrollToInvalidField', this.handleScrollToInvalidField)
					.off('UI.EntityEditor::onScrollToFocusedField', this.handleScrollToFocusedField)
				;
			}

			if (this.interceptExit)
			{
				this.layout.preventBottomSheetDismiss(false);
				this.layout.off('preventDismiss', this.handleExit);
			}
		}

		loadDetails()
		{
			if (this.isLoading === true || this.isLoaded === true)
			{
				return;
			}

			this.isLoading = true;

			BX.ajax.runAction('listsmobile.ElementDetails.load', { data: { elementId: this.elementId } })
				.then(({ data }) => {
					this.isLoaded = true;
					this.setState({
						iBlockName: String(data.iBlockName).trim(),
						elementName: String(data.elementName).trim(),
						iBlockDescription: String(data.iBlockDescription).trim(),
						editorConfig: data.editor,
						hasBPParametersOnStartUp: data.hasBPParametersOnStartUp,
						signedBpDocument: data.signedBpDocument,
						perms: Object.assign(this.state.perms, data.perms),
					});

					this.layout.setTitle({
						text: this.state.iBlockName || Loc.getMessage('M_LISTS_ELEMENT_DETAILS_WIDGET_TITLE'),
						type: 'dialog',
					});
				})
				.catch((response) => {
					this.isLoaded = true;
					console.error(response.errors);
					if (Array.isArray(response.errors))
					{
						NotifyManager.showErrors(response.errors);
					}
				})
				.finally(() => {
					this.isLoading = false;

					this.customEventEmitter.emit('Lists.ElementDetails:OnAfterLoadContent');
				})
			;
		}

		handleChangeFields()
		{
			if (this.isChanged === false)
			{
				this.isChanged = true;
				this.layout.setRightButtons([
					{
						name: Loc.getMessage('M_LISTS_ELEMENT_DETAILS_SAVE_BTN'),
						type: 'text',
						color: AppTheme.colors.accentMainLinks,
						callback: this.save.bind(this),
					},
				]);

				this.customEventEmitter.emit('Lists.ElementDetails:onChange', [this.isChanged]);
			}
		}

		save()
		{
			this.layout.setRightButtons([
				{
					name: Loc.getMessage('M_LISTS_ELEMENT_DETAILS_SAVING_BTN'),
					type: 'text',
					color: AppTheme.colors.accentSoftBlue1,
				},
			]);

			NotifyManager.showLoadingIndicator()
				.then(() => FocusManager.blurFocusedFieldIfHas())
				.then(() => this.validate())
				.then(() => this.getData())
				.then((fields) => this.addBpData(fields))
				.then((fields) => this.startSaveElement(fields))
				.then((response) => {
					this.isReRenderEditor = !this.isReRenderEditor;
					this.setState({
						editorConfig: response.data.editor,
						elementName: response.data.elementName,
					});

					this.isChanged = false;
					this.layout.setRightButtons([]);
					NotifyManager.hideLoadingIndicator(true);

					this.customEventEmitter.emit('Lists.ElementDetails:onChange', [this.isChanged]);
				})
				.catch((errors) => {
					console.error(errors);
					NotifyManager.hideLoadingIndicator(false);

					if (Array.isArray(errors))
					{
						NotifyManager.showErrors(errors);
					}

					this.isChanged = false;
					this.handleChangeFields();
				})
			;
		}

		validate()
		{
			return this.editorRef.validate() ? Promise.resolve() : Promise.reject();
		}

		getData()
		{
			return new Promise((resolve, reject) => {
				this.editorRef.getValuesToSave()
					.then((fields) => resolve(fields))
					.catch((errors) => reject(errors))
				;
			});
		}

		addBpData(fields)
		{
			return new Promise((resolve, reject) => {
				if (this.state.hasBPParametersOnStartUp === false || this.state.signedBpDocument === null)
				{
					resolve(fields);

					return;
				}

				void requireLazy('bizproc:workflow/required-parameters', false)
					.then(({ WorkflowRequiredParameters }) => {
						return WorkflowRequiredParameters.open(
							{ signedDocument: this.state.signedBpDocument },
							this.props.layout,
						);
					})
					.then(({ data }) => resolve(Object.assign(fields, data)))
					.catch((errors) => reject(errors))
				;
			});
		}

		startSaveElement(fields)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'listsmobile.ElementDetails.save',
					{ data: { elementId: this.elementId, fields } },
				)
					.then((response) => resolve(response))
					.catch((response) => reject(response.errors))
				;
			});
		}

		handleExit()
		{
			let promise = Promise.resolve();

			if (this.isClosing)
			{
				return promise;
			}

			if (this.isChanged)
			{
				promise = promise.then(() => new Promise((resolve) => {
					this.showConfirmExit(() => {
						resolve();
					});
				}));
			}

			return promise.then(() => {
				this.isClosing = true;

				this.layout.close();
			});
		}

		showConfirmExit(onDiscard)
		{
			Haptics.impactLight();

			Alert.confirm(
				Loc.getMessage('M_LISTS_ELEMENT_DETAILS_CONFIRM_EXIT_TITLE'),
				Loc.getMessage('M_LISTS_ELEMENT_DETAILS_CONFIRM_EXIT_DESCRIPTION'),
				[
					{
						text: Loc.getMessage('M_LISTS_ELEMENT_DETAILS_CONFIRM_EXIT_EXIT'),
						type: 'destructive',
						onPress: onDiscard,
					},
					{
						text: Loc.getMessage('M_LISTS_ELEMENT_DETAILS_CONFIRM_EXIT_CONTINUE'),
						type: 'cancel',
					},
				],
			);
		}

		handleScrollToInvalidField(fieldView)
		{
			if (this.scrollViewRef && fieldView)
			{
				const position = this.scrollViewRef.getPosition(fieldView);
				position.y -= 50;
				this.scrollViewRef.scrollTo({ ...position, animated: true });
			}
		}

		handleScrollToFocusedField(fieldView)
		{
			if (this.scrollViewRef && fieldView)
			{
				const { y } = this.scrollViewRef.getPosition(fieldView);
				if (y > this.scrollY + device.screen.height * 0.4)
				{
					const positionY = y - 150;
					this.scrollViewRef.scrollTo({ y: positionY, animated: true });
				}
			}
		}

		render()
		{
			if (this.isEmbedded)
			{
				return this.renderContent();
			}

			const showPadding = this.isLoaded || (!this.isLoaded && this.isNeedShowSkeleton);

			return View(
				{
					style: {
						flex: 1,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
					resizableByKeyboard: true,
					safeArea: { bottom: true },
				},
				ScrollView(
					{
						style: { flex: 1 },
						ref: (ref) => {
							this.scrollViewRef = ref;
						},
						onScroll: (params) => {
							this.scrollY = params.contentOffset.y;
						},
					},
					View(
						{ onClick: () => FocusManager.blurFocusedFieldIfHas() },
						View(
							{
								style: {
									backgroundColor: AppTheme.colors.bgContentPrimary,
									paddingHorizontal: showPadding ? 6 : 0,
									paddingVertical: showPadding ? 12 : 0,
								},
							},
							this.renderContent(),
						),
					),
				),
			);
		}

		renderContent()
		{
			return View(
				{},
				View(
					{
						style: {
							paddingHorizontal: 10,
							borderRadius: 12,
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
					},
					this.renderName(),
					this.renderDescription(),
				),
				this.renderEditor(),
			);
		}

		renderName()
		{
			if (!this.isLoaded && this.isNeedShowSkeleton)
			{
				return View(
					{},
					Line(159, 10, 10, 7, 12),
					Line(196, 10, 12, 12, 12),
				);
			}

			if (this.isLoaded && this.state.elementName)
			{
				return Text(
					{
						testId: 'LISTS_DETAILS_ELEMENT_NAME',
						style: {
							fontWeight: '600',
							fontSize: 18,
							lineHeightMultiple: 1.22,
							color: AppTheme.colors.base1,
							marginBottom: 12,
						},
						text: this.state.elementName,
					},
				);
			}

			return null;
		}

		renderDescription()
		{
			if (!this.isLoaded && this.isNeedShowSkeleton)
			{
				return View(
					{},
					Line(307, 4, 15, 8, 12),
					Line(278, 4, 11, 8, 12),
					Line(307, 4, 11, 9, 12),
					Line(175, 4, 11, 11, 12),
				);
			}

			if (this.isLoaded && this.state.iBlockDescription)
			{
				const description = new CollapsibleText({
					bbCodeMode: true,
					testId: 'WORKFLOW_DETAILS_DESCRIPTION',
					value: this.state.iBlockDescription,
					style: {
						fontWeight: '400',
						fontSize: 14,
						lineHeightMultiple: 1.28,
						color: AppTheme.colors.base2,
						marginBottom: 12,
					},
					containerStyle: {
						flexGrow: 0,
					},
					onLinkClick: ({ url }) => {
						inAppUrl.open(url);
					},
				});

				if (description && !this.canRenderEditor())
				{
					description.toggleExpand();
				}

				return description;
			}

			return null;
		}

		renderEditor()
		{
			if (!this.isLoaded)
			{
				return this.isNeedShowSkeleton ? this.renderEditorSkeleton() : null;
			}

			if (!this.state.perms.canRead)
			{
				return this.renderTextBlockStub(Loc.getMessage('M_LISTS_ELEMENT_DETAILS_CANT_READ_ELEMENT'));
			}

			if (this.canRenderEditor())
			{
				if (this.isReRenderEditor)
				{
					return View(
						{},
						this.createEditor(),
					);
				}

				return this.createEditor();
			}

			return null;
		}

		canRenderEditor()
		{
			return Boolean(this.isLoaded && this.state.perms.canRead && this.state.editorConfig);
		}

		renderEditorSkeleton()
		{
			return View(
				{
					style: {
						marginTop: 20,
						marginBottom: 2,
						padding: 11,
						borderRadius: 12,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						backgroundColor: AppTheme.colors.bgContentSecondary,
					},
				},
				Line(70, 4, 11, 2, 12),
				Line(140, 8, 14, 15, 12),
				View({ style: { height: 1, backgroundColor: AppTheme.colors.bgSeparatorPrimary } }),
				Line(40, 4, 15, 1, 12),
				Line(210, 8, 13, 18, 12),
			);
		}

		renderTextBlockStub(text)
		{
			return View(
				{
					style: {
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						borderRadius: 12,
						marginVertical: 12,
					},
				},
				Text({
					style: {
						color: AppTheme.colors.base5,
						fontSize: 14,
						fontWeight: '400',
						marginHorizontal: 24,
						marginVertical: 16,
						textAlign: 'center',
					},
					text,
				}),
			);
		}

		createEditor()
		{
			return EntityManager.create({
				uid: this.uid,
				layout: this.layout,
				editorProps: this.state.editorConfig,
				isEmbedded: true,
				refCallback: (ref) => {
					this.editorRef = ref;
				},
				showBottomPadding: !this.isEmbedded,
			});
		}
	}

	module.exports = { ElementDetails };
});
