/**
 * @module text-editor
 */
jn.define('text-editor', (require, exports, module) => {
	const { Type } = require('type');
	const { TextInputComponent } = require('text-editor/components/text-input');
	const { ToolbarComponent } = require('text-editor/components/toolbar');
	const { EntitySelector } = require('text-editor/entity-selector');
	const { AstProcessor } = require('bbcode/ast-processor');
	const { DiskAdapter } = require('text-editor/adapters/disk-adapter');
	const { CodeAdapter } = require('text-editor/adapters/code-adapter');
	const { ImageAdapter } = require('text-editor/adapters/image-adapter');
	const { TableAdapter } = require('text-editor/adapters/table-adapter');
	const { MentionAdapter } = require('text-editor/adapters/mention-adapter');
	const { parser } = require('text-editor/internal/parser');
	const { scheme } = require('text-editor/internal/scheme');
	const { BbcodeView } = require('text-editor/bbcode-view/bbcode-view');
	const { Color, Indent } = require('tokens');
	const { FileField } = require('layout/ui/fields/file/theme/air');
	const { BottomSheet } = require('bottom-sheet');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { Loc } = require('loc');
	const { confirmClosing } = require('alert');
	const { Haptics } = require('haptics');
	const { inAppUrl } = require('in-app-url');
	const { BBCodeNode } = require('bbcode/model');
	const { copyToClipboard } = require('utils/copy');

	const fileFieldPromise = Symbol('fileFieldPromise');
	const fileFieldPromiseResolver = Symbol('fileFieldPromiseResolver');

	const onSaveHandlerStub = () => {};

	class TextEditor extends LayoutComponent
	{
		adapters = [];
		[fileFieldPromise] = null;
		[fileFieldPromiseResolver] = null;

		/**
		 * @param props {{
		 *     value?: string,
		 *     autoFocus?: boolean,
		 *     readOnly?: boolean,
		 *     onSave?: (data: { bbcode: string, diskFiles: Array<any> }) => any,
		 *     onFocus?: () => void,
		 *     onBlur?: () => void,
		 *     view?: { style?: {} },
		 *     textInput?: {
		 *     		style?: {},
		 *     		placeholder?: string,
		 *     		placeholderTextColor?: string,
		 *     },
		 *     fileField?: {},
		 *     saveButton?: {},
		 *     allowFiles?: boolean,
		 *     allowBBCode?: boolean,
		 *     mention?: {
		 *     		paths?: {
		 *     			user?: (id) => string,
		 *     			project?: (id) => string,
		 *     			department?: (id) => string,
		 *     		},
		 *     },
		 * }}
		 */
		constructor(props = {})
		{
			super(props);

			this.onLinkClick = this.onLinkClick.bind(this);
			this.onLongClickInReadOnlyMode = this.onLongClickInReadOnlyMode.bind(this);
			this.onFileFieldFocusOut = this.onFileFieldFocusOut.bind(this);

			const defaultPlaceholder = (
				this.isBBCodeAllowed() ? '' : Loc.getMessage('MOBILEAPP_TEXT_EDITOR_PLACEHOLDER')
			);

			this.state = {
				value: props.value,
				preparedValue: '',
				onSave: props.onSave,
				autoFocus: props.autoFocus,
				readOnly: props.readOnly ?? false,
				view: {
					style: {
						backgroundColor: Color.bgSecondary.toHex(),
						height: 200,
						...(Type.isPlainObject(props?.view?.style) ? props.view.style : {}),
					},
				},
				textInput: {
					style: {
						backgroundColor: Color.bgSecondary.toHex(),
						color: Color.base1.toHex(),
						fontSize: 18,
						...(Type.isPlainObject(props?.textInput?.style) ? props.textInput.style : {}),
					},
					placeholder: props?.textInput?.placeholder ?? defaultPlaceholder,
					placeholderTextColor: props?.textInput?.placeholderTextColor ?? Color.base5.toHex(),
					onLinkClick: props?.textInput?.onLinkClick || this.onLinkClick,
				},
				fileField: {
					value: [],
					items: [],
					...props?.fileField,
				},
				allowFiles: this.props.allowFiles !== false,
				mention: {
					paths: {
						user: props?.mention?.paths?.user || ((id) => {
							return `/company/personal/user/${id}/`;
						}),
						project: props?.mention?.paths?.project || ((id) => {
							return `/workgroups/group/${id}/`;
						}),
						department: props?.mention?.paths?.department || ((id) => {
							return `/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=${id}`;
						}),
					},
				},
				testId: this.props.testId || 'TEXT_EDITOR',
			};

			this.isChangedValue = false;

			this[fileFieldPromise] = new Promise((resolve) => {
				this[fileFieldPromiseResolver] = resolve;
			});

			/**
			 * @private
			 */
			this.textInput = new TextInputComponent({
				autoFocus: this.state.autoFocus,
				allowBBCode: this.isBBCodeAllowed(),
				events: {
					onStyleChange: this.onStyleChange.bind(this),
					onFocus: this.onFocus.bind(this),
					onBlur: this.onBlur.bind(this),
					onChange: this.onTextChange.bind(this),
				},
				style: {
					...this.state.textInput.style,
				},
				placeholder: this.state.textInput.placeholder,
				placeholderTextColor: this.state.textInput.placeholderTextColor,
				testId: `${this.state.testId}_TEXT_INPUT`,
				onLinkClick: this.state.textInput.onLinkClick,
			});

			/**
			 * @private
			 */
			this.toolbar = new ToolbarComponent({
				events: {
					onFormat: this.onFormat.bind(this),
					onMention: this.onMention.bind(this),
					onAttach: this.onAttach.bind(this),
					onSave: this.onSaveClick.bind(this),
				},
				allowFiles: this.state.allowFiles,
				allowBBCode: this.isBBCodeAllowed(),
				testId: `${this.state.testId}_TOOLBAR`,
			});

			/**
			 * @private
			 */
			this.entitySelector = new EntitySelector({
				testId: `${this.state.testId}_ENTITY_SELECTOR`,
				onClose: () => {
					this.getTextInput().focus();
				},
			});

			this.parentWidget = null;
			this.onFileFieldRef = this.onFileFieldRef.bind(this);
			this.onFilesChange = this.onFilesChange.bind(this);
			this.onFilePreviewMenuClick = this.onFilePreviewMenuClick.bind(this);

			void this.setValue(this.state.value);

			if (this.state.autoFocus)
			{
				this.onFocus();
			}
		}

		getOnSaveHandler()
		{
			if (Type.isFunction(this.state.onSave))
			{
				return this.state.onSave;
			}

			return onSaveHandlerStub;
		}

		componentWillReceiveProps(props)
		{
			this.setState({
				readOnly: props.readOnly === true,
				testId: props.testId,
				fileField: {
					...this.state.fileField,
					...props.fileField,
				},
				allowFiles: props.allowFiles !== false,
				textInput: {
					...this.state.textInput,
					onLinkClick: props?.textInput?.onLinkClick,
				},
			});

			this.getToolbar().forceAllowFiles(props.allowFiles !== false);

			if (props.saveButton)
			{
				this.getToolbar().forceSaveButtonProps(props.saveButton);
			}

			if (props.value)
			{
				this.initialValue = String(props.value);
				void this.setValue(props.value);
			}

			super.componentWillReceiveProps(props);
		}

		async setValue(value)
		{
			const sourceBbcode = Type.isString(value) ? value : '';

			if (!this.isBBCodeAllowed())
			{
				if (this.state.readOnly === true)
				{
					this.setState({ preparedValue: sourceBbcode });
				}
				else
				{
					this.getTextInput().setValue(sourceBbcode);
				}

				return;
			}

			const ast = parser.parse(sourceBbcode);

			BBCodeNode.flattenAst(ast).forEach((node) => {
				if (node.getName() === 'p')
				{
					node.replace(
						...node.getChildren(),
					);
				}

				if (node.getName() === '#text')
				{
					node.setContent(
						String(node.getContent())
							.replaceAll('&#39;', '\'')
							.replaceAll('&quot;', '"')
							.replaceAll('&lt;', '<')
							.replaceAll('&gt;', '>')
							.replaceAll('&amp;', '&'),
					);
				}
			});

			try
			{
				const diskNodes = AstProcessor.findElements(ast, 'BBCodeElementNode[name="disk"]');
				if (
					Type.isArrayFilled(diskNodes)
					&& Type.isArrayFilled(this.state.fileField.value)
				)
				{
					diskNodes.forEach((sourceNode) => {
						const sourceFileId = sourceNode.getAttribute('id');
						const fileId = sourceFileId.replace(/^n/, '');
						const idPropName = sourceFileId.startsWith('n') ? 'objectId' : 'id';

						const fileOptions = this.state.fileField.value.find((file) => {
							return String(file[idPropName]) === String(fileId);
						});

						if (fileOptions)
						{
							const adapter = new DiskAdapter({
								node: sourceNode,
								fileOptions,
							});
							const previewNode = adapter.getPreview();

							this.addAdapter({
								adapter,
								previewNode,
							});
						}
					});
				}

				const mentionNodes = [
					...AstProcessor.findElements(ast, 'BBCodeElementNode[name="user"]'),
					...AstProcessor.findElements(ast, 'BBCodeElementNode[name="department"]'),
					...AstProcessor.findElements(ast, 'BBCodeElementNode[name="project"]'),
				];
				if (Type.isArrayFilled(mentionNodes))
				{
					for (const sourceNode of mentionNodes)
					{
						const adapter = new MentionAdapter({ node: sourceNode });
						const previewNode = adapter.getPreview();

						this.addAdapter({
							adapter,
							previewNode,
						});
					}
				}

				const tableNodes = [
					...AstProcessor.findElements(ast, 'BBCodeRootNode > BBCodeElementNode[name="table"]'),
					...AstProcessor.findElements(ast, 'BBCodeElementNode[name="quote"] > BBCodeElementNode[name="table"]'),
					...AstProcessor.findElements(ast, 'BBCodeElementNode[name="spoiler"] > BBCodeElementNode[name="table"]'),
				];
				if (Type.isArrayFilled(tableNodes))
				{
					for (const sourceNode of tableNodes)
					{
						const adapter = new TableAdapter({ node: sourceNode });
						const previewNode = adapter.getPreview();

						this.addAdapter({
							adapter,
							previewNode,
						});
					}
				}

				const codeNodes = AstProcessor.findElements(ast, 'BBCodeElementNode[name="code"]');
				if (Type.isArrayFilled(codeNodes))
				{
					for await (const sourceNode of codeNodes)
					{
						const adapter = new CodeAdapter({ node: sourceNode });
						const previewNode = await adapter.getPreview();

						this.addAdapter({
							adapter,
							previewNode,
						});
					}
				}

				const imageNodes = AstProcessor.findElements(ast, 'BBCodeElementNode[name="img"]');
				if (Type.isArrayFilled(imageNodes))
				{
					for (const sourceNode of imageNodes)
					{
						const adapter = new ImageAdapter({ node: sourceNode });
						const previewNode = adapter.getPreview();

						this.addAdapter({
							adapter,
							previewNode,
						});
					}
				}

				const adapters = this.getAdapters();
				if (Type.isArrayFilled(adapters))
				{
					AstProcessor.reduceAst(ast, (node) => {
						const entry = this.getAdapterBySourceNode(node);
						if (entry)
						{
							return entry.previewNode;
						}

						return node;
					});
				}

				if (this.state.readOnly === true)
				{
					this.setState({
						preparedValue: ast.toString(),
					});
				}
				else
				{
					this.getTextInput().setValue(ast.toString());
				}
			}
			catch (error)
			{
				console.error(error);
			}
		}

		/**
		 * Show editor for passed bbcode
		 *
		 * @param options {{
		 *     title?: string,
		 *     value?: string,
		 *     onSave?: function,
		 *     closeOnSave?: boolean,
		 *     parentWidget?: PageManager,
		 *     view?: { style?: {} },
		 *     textInput?: { style?: {} },
		 *     autoFocus?: boolean,
		 *     allowFiles?: boolean,
		 *     allowBBCode?: boolean,
		 *     allowInsertToText?: boolean,
		 * }}
		 * @return {
		 * 		Promise<{
		 * 			bbcode: string,
		 * 			diskFiles: Array<string>,
		 * 			attachments: Array<string>,
		 * 		}>
		 * 	}
		 */
		static async edit(options = {})
		{
			return new Promise((resolve) => {
				const editor = new TextEditor({
					...options,
					allowFiles: options.allowBBCode === false ? false : options.allowFiles,
					autoFocus: options?.autoFocus !== false,
					view: {
						style: {
							flex: 1,
						},
					},
				});

				const bottomSheet = new BottomSheet({
					titleParams: {
						text: (Type.isString(options.title) ? options.title : ''),
						type: 'dialog',
					},
					component: editor,
				});

				bottomSheet
					.setParentWidget(options.parentWidget || PageManager)
					.setBackgroundColor(Color.bgSecondary.toHex())
					.setNavigationBarColor(Color.bgSecondary.toHex())
					.disableContentSwipe()
					.alwaysOnTop()
					.open()
					.then((layout) => {
						editor.parentWidget = layout;
						resolve(layout);

						layout.preventBottomSheetDismiss(true);
						layout.on('preventDismiss', () => {
							if (editor.isChanged())
							{
								void TextEditor
									.confirmClosing()
									.then(() => {
										layout.close();
									});
							}
							else
							{
								layout.close();
							}
						});
					})
					.catch((error) => {
						console.error(error);
					});
			});
		}

		static confirmClosing()
		{
			Haptics.impactLight();

			return new Promise((resolve, reject) => {
				confirmClosing({
					hasSaveAndClose: false,
					onClose: () => {
						resolve();
					},
					onCancel: () => {
						reject();
					},
				});
			});
		}

		async onTextChange(value)
		{
			this.isChangedValue = true;

			if (Type.isStringFilled(value))
			{
				const plainText = await this.getTextInput().getPlainTextValue();
				const selection = await this.getTextInput().getSelection();

				const left = plainText.slice(0, selection.end + 1);

				if (
					(
						Type.isStringFilled(left)
						&& /\s@$/.test(left)
					)
					|| (selection.end === 0 && left.endsWith('@'))
				)
				{
					void this.onMention({
						start: selection.end,
						end: selection.end + 1,
					});
				}
			}
		}

		isChanged()
		{
			return this.isChangedValue;
		}

		onSaveClick()
		{
			const onSaveHandler = this.getOnSaveHandler();
			const result = onSaveHandler(this.getValue());

			if (Type.isObject(result) && Type.isFunction(result.then))
			{
				result
					.then(() => {
						this.getTextInput().blur();

						if (this.props.closeOnSave)
						{
							this.parentWidget.close();
						}
					})
					.catch((error) => {
						this.getToolbar().setSaveButtonLoading(false);
						console.error(error);
					});
			}
			else
			{
				this.getTextInput().blur();

				if (this.props.closeOnSave)
				{
					this.parentWidget.close();
				}
			}
		}

		onFocus()
		{
			this.props.onFocus?.();
			this.getToolbar().show();
		}

		onBlur()
		{
			this.props.onBlur?.();
			this.getToolbar().hide();
		}

		setAdapters(adapters)
		{
			if (Type.isArray(adapters))
			{
				this.adapters = [...adapters];
			}
		}

		getAdapters()
		{
			return this.adapters;
		}

		/**
		 * @param entry {{
		 *     sourceNode: any,
		 *     previewNode: any,
		 *     adapter: any,
		 * }}
		 */
		addAdapter(entry)
		{
			if (Type.isPlainObject(entry))
			{
				this.adapters.push(entry);
			}
		}

		getAdapterBySourceNode(node)
		{
			return this.getAdapters().find(({ adapter }) => {
				return adapter.getSource() === node;
			});
		}

		getAdapterByPreviewNode(node)
		{
			return this.getAdapters().find(({ adapter }) => {
				return adapter.isPreview(node);
			});
		}

		/**
		 * @param ast {RootNode}
		 * @returns {Array<string>}
		 */
		fetchDiskFileIds(ast)
		{
			return AstProcessor
				.findElements(ast, 'BBCodeElementNode[name="disk" void="true"]')
				.map((node) => {
					return node.getAttribute('id');
				});
		}

		/**
		 * @param file
		 * @private
		 */
		async onInsertAttachmentIntoText(file)
		{
			if (Type.isPlainObject(file))
			{
				const sourceNode = scheme.createElement({
					name: 'disk',
					attributes: {
						file: '',
						id: file.id,
					},
				});

				const adapter = new DiskAdapter({
					node: sourceNode,
					fileOptions: file,
				});

				const previewNode = adapter.getPreview();

				this.addAdapter({
					adapter,
					previewNode,
				});

				void this.getTextInput().insert(previewNode.toString().replaceAll('&amp;', '&'));
			}
		}

		/**
		 * @private
		 */
		async onMention(selection)
		{
			const entitySelector = this.getEntitySelector();
			const mentions = await entitySelector.show();

			const preparedMentions = mentions.map((mention) => {
				const sourceNode = scheme.createElement({
					name: mention.getType(),
					value: mention.getId(),
					children: [
						scheme.createText(mention.getTitle()),
					],
				});

				const adapter = new MentionAdapter({
					node: sourceNode,
				});

				const previewNode = adapter.getPreview();

				this.addAdapter({
					adapter,
					previewNode,
				});

				return previewNode.toString();
			});

			if (Type.isPlainObject(selection))
			{
				await this.getTextInput().setSelection(selection);
			}

			await this.getTextInput().insert(`${preparedMentions.join(', ')} `);
		}

		/**
		 * @private
		 * @param type {string}
		 */
		onFormat({ type })
		{
			void this.getTextInput().format(type);
		}

		/**
		 * @private
		 */
		async onAttach()
		{
			const fileField = await this.getFileField();
			fileField.handleContentClick();
		}

		/**
		 * @private
		 * @param styles {{ styles: Array<string> }}
		 */
		onStyleChange({ styles })
		{
			this.getToolbar().highlightButtons(styles);
		}

		getTextInput()
		{
			return this.textInput;
		}

		getToolbar()
		{
			return this.toolbar;
		}

		getEntitySelector()
		{
			return this.entitySelector;
		}

		getValue()
		{
			const currentValue = (() => {
				if (this.state.readOnly)
				{
					return this.state.value;
				}

				return this.getTextInput().getValue();
			})();
			const ast = parser.parse(currentValue);

			AstProcessor.reduceAst(ast, (node) => {
				const entry = this.getAdapterByPreviewNode(node);
				if (entry)
				{
					return entry.adapter.getSource();
				}

				return node;
			});

			return {
				ast,
				bbcode: ast.toString(),
				files: this.state.fileField.value,
			};
		}

		async getFileField()
		{
			return this[fileFieldPromise];
		}

		static getAbsolutePath(url)
		{
			if (url && !url.startsWith('file://') && !url.startsWith('http://') && !url.startsWith('https://'))
			{
				return currentDomain + url;
			}

			return url;
		}

		static openFileViewer({ path, name, type = 'file' })
		{
			const filePath = TextEditor.getAbsolutePath(path);

			if (type === 'image')
			{
				viewer.openImage(filePath, name);
			}
			else if (type === 'video')
			{
				viewer.openVideo(filePath, name);
			}
			else
			{
				viewer.openDocument(filePath, name);
			}
		}

		onFilePreviewMenuClick(file)
		{
			const menu = new ContextMenu({
				title: Loc.getMessage('MOBILEAPP_TEXT_EDITOR_FILE_MENU_TITLE'),
				actions: [
					this.isInsertToTextAllowed() && {
						id: 'insert-to-text',
						title: Loc.getMessage('MOBILEAPP_TEXT_EDITOR_FILE_INSERT_TO_TEXT'),
						onClickCallback: () => {
							const currentFile = file.files.find((item) => {
								return item.id === file.id;
							});

							void this.onInsertAttachmentIntoText(currentFile);
						},
					},
					{
						id: 'show-file',
						title: Loc.getMessage('MOBILEAPP_TEXT_EDITOR_FILE_SHOW'),
						onClickCallback: () => {
							TextEditor.openFileViewer({
								path: file.url,
								name: file.name,
								type: file.fileType,
							});
						},
					},
					{
						id: 'remove',
						title: Loc.getMessage('MOBILEAPP_TEXT_EDITOR_FILE_REMOVE'),
						onClickCallback: () => {
							file.onDeleteAttachmentItem();
						},
					},
				].filter(Boolean),
				onClose: () => {
					this.getTextInput().focus();
				},
				testId: `${this.state.testId}_FILE_CONTEXT_MENU`,
			});

			menu.show(this.parentWidget || PageManager);
		}

		onFilesChange(value)
		{
			this.isChangedValue = true;

			if (this.state.allowFiles !== false)
			{
				if (this.state.fileField.onChange)
				{
					this.state.fileField.onChange(value);
				}

				this.setState({
					fileField: {
						...this.state.fileField,
						value,
					},
				});
			}
		}

		onFileFieldRef(ref)
		{
			this[fileFieldPromiseResolver](ref);
		}

		onFileFieldFocusOut()
		{
			this.getTextInput().focus();
		}

		renderFileField()
		{
			return View(
				{
					style: {
						display: this.state.fileField.value.length > 0 ? 'flex' : 'none',
						backgroundColor: Color.bgSecondary.toHex(),
						marginLeft: 8,
						marginRight: 8,
						paddingTop: 4,
					},
					testId: `${this.state.testId}_FILE_FIELD_VIEW_WRAPPER`,
				},
				FileField({
					...this.state.fileField,
					config: {
						...(this.state?.fileField?.config),
						parentWidget: this.parentWidget,
					},
					ref: this.onFileFieldRef,
					onChange: this.onFilesChange,
					showTitle: false,
					multiple: true,
					showAddButton: false,
					testId: `${this.state.testId}_FILE_FIELD`,
					...(() => {
						if (this.state.readOnly)
						{
							return {
								disabled: this.state.readOnly,
							};
						}

						return {
							onFilePreviewMenuClick: this.onFilePreviewMenuClick,
						};
					})(),
					onFocusOut: this.onFileFieldFocusOut,
					onFileAttachmentViewHidden: this.onFileFieldFocusOut,
				}),
			);
		}

		onLinkClick({ url } = {})
		{
			if (url.startsWith('#table-'))
			{
				const adapterEntry = this.getAdapters().find((entry) => {
					return entry.previewNode.value === url;
				});

				if (Type.isPlainObject(adapterEntry))
				{
					const { adapter } = adapterEntry;
					const sourceNode = adapter.getSource();
					const bbcode = sourceNode.toString();

					void BbcodeView.show({
						bbcode,
						onClose: () => {
							this.getTextInput().focus();
						},
						parentWidget: this.parentWidget || PageManager,
					});
				}
			}
			else if (url.startsWith('#user-'))
			{
				const id = url.replace(/^#user-(\d+)-/, '');
				inAppUrl.open(
					this.state.mention.paths.user(id),
					{
						parentWidget: (this.parentWidget || PageManager),
					},
				);
			}
			else if (url.startsWith('#department-'))
			{
				const id = url.replace(/^#department-(\d+)-/, '');
				inAppUrl.open(
					this.state.mention.paths.department(id),
					{
						parentWidget: (this.parentWidget || PageManager),
					},
				);
			}
			else if (url.startsWith('#project-'))
			{
				const id = url.replace(/^#project-(\d+)-/, '');
				inAppUrl.open(
					this.state.mention.paths.project(id),
					{
						parentWidget: (this.parentWidget || PageManager),
					},
				);
			}
			else
			{
				const file = this.state.fileField.value.find((currentFile) => {
					return TextEditor.getAbsolutePath(currentFile.url) === TextEditor.getAbsolutePath(url);
				});

				if (file)
				{
					TextEditor.openFileViewer({
						path: file.url,
						name: file.name,
						type: file.fileType,
					});
				}
				else
				{
					inAppUrl.open(url);
				}
			}
		}

		onLongClickInReadOnlyMode()
		{
			if (this.state.readOnly)
			{
				dialogs.showActionSheet({
					callback: () => {
						const { ast } = this.getValue();
						copyToClipboard(
							ast.toPlainText(),
							Loc.getMessage('MOBILEAPP_TEXT_EDITOR_COPY_DONE'),
						);
					},
					items: [
						{
							title: Loc.getMessage('MOBILEAPP_TEXT_EDITOR_COPY'),
						},
					],
				});
			}
		}

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
					style: {
						paddingTop: Indent.XL.toNumber(),
						...this.state.view.style,
					},
					onLongClick: this.onLongClickInReadOnlyMode,
					testId: `${this.state.testId}_ROOT_VIEW`,
				},
				...(() => {
					if (this.state.readOnly)
					{
						return [
							ScrollView(
								{
									style: {
										...this.state.view.style,
										height: 'auto',
										flex: 1,
										marginTop: 5,
									},
									testId: `${this.state.testId}_SCROLL_VIEW`,
								},
								BBCodeText({
									value: this.state.preparedValue,
									style: {
										marginHorizontal: Indent.XL3.toNumber(),
										paddingTop: 20,
										...this.state.textInput.style,
										flex: 0,
									},
									testId: `${this.state.testId}_BBCODE_TEXT`,
									onLinkClick: this.state.textInput.onLinkClick,
								}),
							),
							this.state.allowFiles ? this.renderFileField() : undefined,
						];
					}

					return [
						View(
							{
								style: {
									flex: 1,
								},
								testId: `${this.state.testId}_EDITOR_VIEW`,
							},
							this.getTextInput(),
							this.state.allowFiles ? this.renderFileField() : undefined,
							this.getToolbar(),
						),
					];
				})(),
			);
		}

		isBBCodeAllowed()
		{
			return this.props.allowBBCode !== false;
		}

		isInsertToTextAllowed()
		{
			return this.props.allowInsertToText !== false;
		}
	}

	module.exports = {
		TextEditor,
	};
});
