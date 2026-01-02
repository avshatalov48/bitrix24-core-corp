/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_vue3_components_button,ui_uploader_core,ui_lexical_core,tasks_v2_core,main_core_events,ui_vue3_directives_hint,tasks_v2_component_elements_hint,ui_bbcode_formatter_htmlFormatter,ui_iconSet_api_vue,ui_iconSet_api_core,ui_iconSet_outline,disk_uploader_userFieldWidget,tasks_v2_component_elements_bottomSheet,tasks_v2_component_dropZone,ui_vue3_components_popup,ui_vue3_vuex,main_core,ui_textEditor,tasks_v2_model_tasks,tasks_v2_const,tasks_v2_provider_service_taskService,tasks_v2_provider_service_fileService) {
	'use strict';

	const DefaultEditorOptions = Object.freeze({
	  toolbar: [],
	  floatingToolbar: ['bold', 'italic', 'underline', 'strikethrough', '|', 'numbered-list', 'bulleted-list', '|', 'link'],
	  removePlugins: ['BlockToolbar'],
	  visualOptions: {
	    borderWidth: 0,
	    blockSpaceInline: 0,
	    colorBackground: 'transparent'
	  },
	  mention: {
	    dialogOptions: {
	      entities: [{
	        id: tasks_v2_const.EntitySelectorEntity.User,
	        options: {
	          emailUsers: true,
	          inviteEmployeeLink: false
	        },
	        itemOptions: {
	          default: {
	            link: '',
	            linkTitle: ''
	          }
	        }
	      }, {
	        id: tasks_v2_const.EntitySelectorEntity.StructureNode,
	        options: {
	          selectMode: 'usersOnly',
	          allowFlatDepartments: false
	        }
	      }]
	    }
	  },
	  copilot: {
	    copilotOptions: {}
	  },
	  paragraphPlaceholder: 'auto'
	});

	var _editor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editor");
	var _fileService = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fileService");
	var _uploaderAdapter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uploaderAdapter");
	var _syncHighlightsDebounced = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("syncHighlightsDebounced");
	var _subscribeToEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToEvents");
	var _unsubscribeToEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unsubscribeToEvents");
	var _registerCommands = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("registerCommands");
	var _syncHighlights = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("syncHighlights");
	class DescriptionTextEditor extends main_core_events.EventEmitter {
	  constructor(taskId, options) {
	    super();
	    Object.defineProperty(this, _syncHighlights, {
	      value: _syncHighlights2
	    });
	    Object.defineProperty(this, _registerCommands, {
	      value: _registerCommands2
	    });
	    Object.defineProperty(this, _unsubscribeToEvents, {
	      value: _unsubscribeToEvents2
	    });
	    Object.defineProperty(this, _subscribeToEvents, {
	      value: _subscribeToEvents2
	    });
	    Object.defineProperty(this, _editor, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _fileService, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _uploaderAdapter, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _syncHighlightsDebounced, {
	      writable: true,
	      value: void 0
	    });
	    this.onFileComplete = event => {
	      const file = event.getData();
	      babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].dispatchCommand(ui_textEditor.Plugins.File.ADD_FILE_COMMAND, file);
	      this.emit('filesChanged');
	    };
	    this.onFileRemove = event => {
	      const {
	        file
	      } = event.getData();
	      this.handleRemoveFile(file.serverFileId);
	      this.emit('filesChanged');
	    };
	    this.handleEditorChange = () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _syncHighlightsDebounced)[_syncHighlightsDebounced]();
	      this.emit('editorChanged');
	    };
	    this.setEventNamespace('Tasks.V2.Component.Description-Text-Editor');
	    this.initService(taskId);
	    this.initEditor(taskId, options);
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToEvents)[_subscribeToEvents]();
	    babelHelpers.classPrivateFieldLooseBase(this, _registerCommands)[_registerCommands]();
	    babelHelpers.classPrivateFieldLooseBase(this, _syncHighlightsDebounced)[_syncHighlightsDebounced] = main_core.Runtime.debounce(babelHelpers.classPrivateFieldLooseBase(this, _syncHighlights)[_syncHighlights], 500, this);
	  }
	  getEditor() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor];
	  }
	  initService(taskId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _fileService)[_fileService] = tasks_v2_provider_service_fileService.fileService.get(taskId);
	    babelHelpers.classPrivateFieldLooseBase(this, _uploaderAdapter)[_uploaderAdapter] = tasks_v2_provider_service_fileService.fileService.get(taskId).getAdapter();
	  }
	  initEditor(taskId, options) {
	    const content = this.initDescription(taskId, options.content);
	    const additionalEditorOptions = {
	      content,
	      minHeight: 118,
	      placeholder: main_core.Loc.getMessage('TASKS_V2_CHANGE_DESCRIPTION'),
	      newLineMode: 'paragraph',
	      events: {
	        onChange: this.handleEditorChange
	      },
	      file: {
	        mode: 'disk',
	        files: this.getFiles()
	      },
	      visualOptions: {
	        borderWidth: 0,
	        blockSpaceInline: 'var(--ui-space-stack-md2)',
	        colorBackground: 'transparent'
	      }
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor] = new ui_textEditor.TextEditor({
	      ...DefaultEditorOptions,
	      ...additionalEditorOptions
	    });
	  }
	  initDescription(taskId, originalDescription) {
	    if (!main_core.Type.isStringFilled(originalDescription)) {
	      return originalDescription;
	    }
	    const mapping = this.getTempToServerFileIdMap();
	    const changedDescription = originalDescription.replaceAll(/(\[disk file id=)(n\d+)/gi, (match, prefix, nId) => {
	      return prefix + (mapping[nId] === undefined ? nId : mapping[nId]);
	    });
	    if (originalDescription !== changedDescription) {
	      const fields = {
	        description: changedDescription
	      };
	      void this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	        id: taskId,
	        fields
	      });
	    }
	    return changedDescription;
	  }
	  setEditorText(taskId, content) {
	    const text = this.initDescription(taskId, content);
	    babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].setText(text);
	  }
	  getFiles() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _uploaderAdapter)[_uploaderAdapter].getItems();
	  }
	  getTempToServerFileIdMap() {
	    const filesMap = {};
	    this.getFiles().forEach(file => {
	      const key = `n${file.customData.objectId}`;
	      filesMap[key] = file.serverFileId;
	    });
	    return filesMap;
	  }
	  destroy() {
	    babelHelpers.classPrivateFieldLooseBase(this, _unsubscribeToEvents)[_unsubscribeToEvents]();
	    babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor] = null;
	  }
	  handleRemoveFile(serverFileId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].dispatchCommand(ui_textEditor.Plugins.File.REMOVE_FILE_COMMAND, {
	      serverFileId,
	      skipHistoryStack: true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _syncHighlights)[_syncHighlights]();
	  }
	  insertFile(fileInfo) {
	    babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].dispatchCommand(ui_textEditor.Plugins.File.INSERT_FILE_COMMAND, {
	      serverFileId: fileInfo.serverFileId,
	      width: 600,
	      height: 600,
	      info: fileInfo,
	      inline: true
	    });
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	}
	function _subscribeToEvents2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _fileService)[_fileService].subscribe('onFileComplete', this.onFileComplete);
	  babelHelpers.classPrivateFieldLooseBase(this, _fileService)[_fileService].subscribe('onFileRemove', this.onFileRemove);
	}
	function _unsubscribeToEvents2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _fileService)[_fileService].unsubscribe('onFileComplete', this.onFileComplete);
	  babelHelpers.classPrivateFieldLooseBase(this, _fileService)[_fileService].unsubscribe('onFileRemove', this.onFileRemove);
	}
	function _registerCommands2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].registerCommand(ui_lexical_core.PASTE_COMMAND, clipboardEvent => {
	    const clipboardData = clipboardEvent.clipboardData;
	    if (!clipboardData || !ui_uploader_core.isFilePasted(clipboardData)) {
	      return false;
	    }
	    clipboardEvent.preventDefault();
	    ui_uploader_core.getFilesFromDataTransfer(clipboardData).then(files => {
	      files.forEach(file => {
	        babelHelpers.classPrivateFieldLooseBase(this, _uploaderAdapter)[_uploaderAdapter].getUploader().addFile(file, {
	          events: {
	            [ui_uploader_core.FileEvent.LOAD_ERROR]: () => {},
	            [ui_uploader_core.FileEvent.UPLOAD_ERROR]: () => {},
	            [ui_uploader_core.FileEvent.UPLOAD_COMPLETE]: event => {
	              const uploaderFile = event.getTarget();
	              this.insertFile(uploaderFile.toJSON());
	            }
	          }
	        });
	      });
	    }).catch(() => {
	      console.error('clipboard pasting error');
	    });
	    return true;
	  }, ui_lexical_core.COMMAND_PRIORITY_NORMAL);
	}
	function _syncHighlights2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].dispatchCommand(ui_textEditor.Plugins.File.GET_INSERTED_FILES_COMMAND, nodes => {
	    const inserted = new Set();
	    for (const node of nodes) {
	      const {
	        serverFileId
	      } = node.getInfo();
	      if (main_core.Type.isStringFilled(serverFileId) || main_core.Type.isNumber(serverFileId)) {
	        inserted.add(serverFileId);
	      }
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _uploaderAdapter)[_uploaderAdapter].getUploader().getFiles().forEach(file => {
	      if (inserted.has(file.getServerFileId())) {
	        file.setCustomData('tileSelected', true);
	        inserted.delete(file.getServerFileId());
	      } else {
	        file.setCustomData('tileSelected', false);
	      }
	    });
	    for (const serverFileId of inserted) {
	      this.handleRemoveFile(serverFileId);
	    }
	  });
	}
	const instances = {};
	const descriptionTextEditor = {
	  get(taskId, options = {}) {
	    var _instances$taskId;
	    (_instances$taskId = instances[taskId]) != null ? _instances$taskId : instances[taskId] = new DescriptionTextEditor(taskId, options);
	    if (main_core.Type.isStringFilled(options == null ? void 0 : options.content)) {
	      instances[taskId].setEditorText(taskId, options.content);
	    }
	    return instances[taskId];
	  },
	  replace(tempId, taskId) {
	    instances[taskId] = instances[tempId];
	    instances[taskId].initService(taskId);
	    delete instances[tempId];
	  },
	  delete(taskId) {
	    var _instances$taskId2;
	    (_instances$taskId2 = instances[taskId]) == null ? void 0 : _instances$taskId2.destroy();
	    delete instances[taskId];
	  }
	};

	// @vue/component
	const DescriptionTextArea = {
	  name: 'TaskDescriptionTextArea',
	  components: {
	    TextEditorComponent: ui_textEditor.TextEditorComponent,
	    UserFieldWidgetComponent: disk_uploader_userFieldWidget.UserFieldWidgetComponent
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['change', 'filesChange'],
	  setup(props) {
	    return {
	      /** @type TextEditor */
	      editor: null,
	      descriptionTextEditor: descriptionTextEditor.get(props.taskId),
	      fileService: tasks_v2_provider_service_fileService.fileService.get(props.taskId),
	      uploaderAdapter: tasks_v2_provider_service_fileService.fileService.get(props.taskId).getAdapter()
	    };
	  },
	  data() {
	    return {
	      files: this.fileService.getFiles()
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    isEdit() {
	      return main_core.Type.isNumber(this.taskId) && this.taskId > 0;
	    },
	    widgetOptions() {
	      return {
	        isEmbedded: true,
	        withControlPanel: false,
	        canCreateDocuments: false,
	        insertIntoText: true,
	        tileWidgetOptions: {
	          compact: true,
	          hideDropArea: true,
	          readonly: this.readonly,
	          autoCollapse: false,
	          removeFromServer: !this.isEdit,
	          events: {
	            onInsertIntoText: this.handleInsertFile
	          }
	        }
	      };
	    },
	    filesCount() {
	      return this.files.length;
	    }
	  },
	  created() {
	    this.editor = this.descriptionTextEditor.getEditor();
	  },
	  mounted() {
	    this.fileService.subscribe('onFileAdd', this.onFileAdd);
	    this.fileService.subscribe('onFileRemove', this.onFileRemove);
	    this.descriptionTextEditor.subscribe('editorChanged', this.onEditorChange);
	  },
	  unmounted() {
	    this.fileService.unsubscribe('onFileAdd', this.onFileAdd);
	    this.fileService.unsubscribe('onFileRemove', this.onFileRemove);
	    this.descriptionTextEditor.unsubscribe('editorChanged', this.onEditorChange);
	  },
	  methods: {
	    onFileAdd() {
	      this.$emit('filesChange');
	    },
	    onFileRemove() {
	      this.$emit('filesChange');
	    },
	    onEditorChange() {
	      this.$emit('change');
	    },
	    handleInsertFile(event) {
	      const fileInfo = event.getData().item;
	      this.descriptionTextEditor.insertFile(fileInfo);
	    }
	  },
	  template: `
		<div class="tasks-card-description-text-area-wrapper" ref="editorWrapper">
			<TextEditorComponent :editorInstance="editor">
				<template v-if="filesCount > 0" #footer>
					<div id="descriptionEditorFiles" class="tasks-card-description-editor-files" ref="filesWrapper">
						<UserFieldWidgetComponent
							:uploaderAdapter="uploaderAdapter"
							:widgetOptions="widgetOptions"
						/>
					</div>
				</template>
			</TextEditorComponent>
		</div>
	`
	};

	// @vue/component
	const ActionButton = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    title: {
	      type: String,
	      default: ''
	    },
	    iconName: {
	      type: String,
	      required: true
	    },
	    iconColor: {
	      type: String,
	      default: ''
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  computed: {
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.title,
	        popupOptions: {
	          offsetLeft: this.$el.offsetWidth / 2
	        }
	      });
	    }
	  },
	  template: `
		<button class="tasks-card-description-action-button" type="button" v-hint="tooltip">
			<BIcon
				:name="iconName"
				:color="iconColor"
				:hoverable="true"
				class="tasks-card-description-field-icon"
			/>
		</button>
	`
	};

	// @vue/component
	const Copilot = {
	  name: 'TaskDescriptionCopilot',
	  components: {
	    ActionButton,
	    Outline: ui_iconSet_api_vue.Outline
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  computed: {
	    buttonColor() {
	      return 'var(--ui-color-copilot-primary)';
	    }
	  },
	  methods: {
	    handleClick() {
	      alert('Not implemented yet');
	    }
	  },
	  template: `
		<ActionButton
			:iconName="Outline.COPILOT"
			:title="loc('TASKS_V2_DESCRIPTION_ACTION_COPILOT_TITLE')"
			:iconColor="buttonColor"
			@click="handleClick"
		/>
	`
	};

	// @vue/component
	const Attach = {
	  name: 'TaskDescriptionMention',
	  components: {
	    ActionButton,
	    Outline: ui_iconSet_api_vue.Outline
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  template: `
		<ActionButton
			:iconName="Outline.ATTACH"
			:title="loc('TASKS_V2_DESCRIPTION_ACTION_ATTACH_TITLE')"
		/>
	`
	};

	// @vue/component
	const Mention = {
	  name: 'TaskDescriptionMention',
	  components: {
	    ActionButton,
	    Outline: ui_iconSet_api_vue.Outline
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  template: `
		<ActionButton
			:iconName="Outline.MENTION"
			:title="loc('TASKS_V2_DESCRIPTION_ACTION_MENTION_TITLE')"
		/>
	`
	};

	// @vue/component
	const DescriptionEditor = {
	  name: 'TaskDescriptionContent',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UiButton: ui_vue3_components_button.Button,
	    Copilot,
	    Attach,
	    Mention,
	    DescriptionTextArea
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['close', 'show'],
	  setup(props) {
	    return {
	      ButtonSize: ui_vue3_components_button.ButtonSize,
	      ButtonColor: ui_vue3_components_button.ButtonColor,
	      Outline: ui_iconSet_api_vue.Outline,
	      fileService: tasks_v2_provider_service_fileService.fileService.get(props.taskId),
	      descriptionTextEditor: descriptionTextEditor.get(props.taskId)
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    editor() {
	      return this.descriptionTextEditor.getEditor();
	    }
	  },
	  mounted() {
	    this.$emit('show');
	    if (!main_core.Type.isStringFilled(this.task.description) || this.taskId === 0) {
	      setTimeout(this.focusToEnd, 500);
	    }
	  },
	  methods: {
	    handleClose() {
	      this.$emit('close');
	    },
	    focusToEnd() {
	      this.editor.focus(null, {
	        defaultSelection: 'rootEnd'
	      });
	    },
	    handleMentionButtonClick() {
	      this.editor.focus(() => {
	        this.editor.dispatchCommand(BX.UI.TextEditor.Plugins.Mention.INSERT_MENTION_DIALOG_COMMAND);
	      }, {
	        defaultSelection: 'rootEnd'
	      });
	    },
	    handleAttachButtonClick() {
	      this.fileService.browse({
	        bindElement: this.$refs.attach.$el,
	        onHideCallback: this.onFileBrowserClose
	      });
	    },
	    onFileBrowserClose() {
	      this.fileService.setFileBrowserClosed(false);
	    }
	  },
	  template: `
		<div class="tasks-card-description-wrapper" ref="wrapper">
			<div class="tasks-card-description-header" ref="descriptionHeader">
				<div class="tasks-card-description-title">
					{{ loc('TASKS_V2_CHANGE_DESCRIPTION') }}
				</div>
				<BIcon
					:name="Outline.CROSS_L"
					:hoverable="true"
					class="tasks-card-description-field-icon"
					@click="handleClose"
				/>
			</div>
			<div class="tasks-card-description-editor-wrapper" id="descriptionTextAreaDestination"/>
			<div v-if="!readonly" class="tasks-card-description-footer" ref="descriptionActions">
				<div class="tasks-card-description-action-list">
					<Copilot />
					<Attach ref="attach" @click="handleAttachButtonClick"/>
					<Mention @click="handleMentionButtonClick"/>
				</div>
				<div class="tasks-card-description-footer-buttons">
					<UiButton
						:text="loc('TASKS_V2_DESCRIPTION_BUTTON_SAVE')"
						:size="ButtonSize.MEDIUM"
						:color="ButtonColor.PRIMARY"
						@click="handleClose"
					/>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const MiniFormButton = {
	  name: 'TaskDescriptionMiniFormButton',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    filesCount: {
	      type: Number,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  computed: {
	    iconSize() {
	      return 20;
	    }
	  },
	  template: `
		<div class="tasks-card-change-description-mini-btn">
			<div class="tasks-full-card-field-container --small-vertical-padding">
				<div class="tasks-card-change-description" :class="{ '--no-hover': filesCount }">
					<template v-if="filesCount">
						<BIcon
							:name="Outline.ATTACH"
							:size=iconSize
							class="tasks-card-description-field-icon-link"
						/>
						<div class="tasks-card-change-description-mini-text-files">
							{{ loc('TASKS_V2_DESCRIPTION_FILES_COUNT', { '#COUNT#': String(filesCount) }) }}
						</div>
					</template>
					<template v-else>
						<div class="tasks-card-change-description-mini-text">
							{{ loc('TASKS_V2_CHANGE_DESCRIPTION') }}
						</div>
						<BIcon
							:name="Outline.CREATE_CHAT"
							:size=iconSize
							:hoverable="true"
							class="tasks-card-description-field-icon"
						/>
					</template>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const FullDescription = {
	  name: 'TaskFullDescription',
	  components: {
	    ActionButton,
	    Outline: ui_iconSet_api_core.Outline
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  template: `
		<ActionButton
			:icon-name="Outline.GO_TO_L"
			:title="loc('TASKS_V2_DESCRIPTION_BUTTON_EXPAND')"
		/>
	`
	};

	// @vue/component
	const MiniForm = {
	  name: 'TaskDescriptionMiniForm',
	  components: {
	    TextEditorComponent: ui_textEditor.TextEditorComponent,
	    Copilot,
	    Attach,
	    Mention,
	    FullDescription,
	    DescriptionTextArea
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    isSlotShown: {
	      type: Boolean,
	      required: true
	    }
	  },
	  emits: ['expand', 'closeEdit'],
	  setup(props) {
	    return {
	      Outline: ui_iconSet_api_vue.Outline,
	      fileService: tasks_v2_provider_service_fileService.fileService.get(props.taskId),
	      descriptionTextEditor: descriptionTextEditor.get(props.taskId)
	    };
	  },
	  data() {
	    return {
	      isNeedTeleport: false,
	      hasChanges: false,
	      closed: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    taskDescription() {
	      var _this$task$descriptio;
	      return (_this$task$descriptio = this.task.description) != null ? _this$task$descriptio : '';
	    },
	    editor() {
	      return this.descriptionTextEditor.getEditor();
	    }
	  },
	  watch: {
	    isSlotShown(newValue) {
	      this.handleTeleport(newValue);
	    }
	  },
	  async created() {
	    this.saveDebounced = main_core.Runtime.debounce(this.handleSave, 30000, this);
	    main_core.Event.bind(window, 'beforeunload', this.handleSave);
	  },
	  mounted() {
	    if (!main_core.Type.isStringFilled(this.taskDescription)) {
	      this.focusToEnd();
	    }
	  },
	  async beforeUnmount() {
	    main_core.Event.unbind(window, 'beforeunload', this.handleSave);
	    await this.handleSave();
	    this.closed = true;
	  },
	  methods: {
	    focusToEnd() {
	      this.editor.focus(null, {
	        defaultSelection: 'rootEnd'
	      });
	    },
	    handleExpand() {
	      this.$emit('expand');
	    },
	    handleMentionButtonClick() {
	      this.editor.focus(() => {
	        this.editor.dispatchCommand(BX.UI.TextEditor.Plugins.Mention.INSERT_MENTION_DIALOG_COMMAND);
	      }, {
	        defaultSelection: 'rootEnd'
	      });
	    },
	    handleAttachButtonClick() {
	      this.fileService.browse({
	        bindElement: this.$refs.attach.$el,
	        onHideCallback: this.onFileBrowserClose
	      });
	    },
	    handleEditorChange() {
	      this.hasChanges = this.taskDescription !== this.getEditorText();
	      void this.saveDebounced();
	    },
	    async handleAddButtonClick() {
	      await this.save();
	    },
	    getEditorText() {
	      var _this$editor;
	      return (_this$editor = this.editor) == null ? void 0 : _this$editor.getText().replaceAll(/\[p]\n|\[p]\[\/p]|\[\/p]/gi, '').trim();
	    },
	    async handleSave() {
	      if (this.closed || !this.hasChanges || !this.editor) {
	        return;
	      }
	      await this.save();
	      this.hasChanges = false;
	    },
	    async save() {
	      await tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        description: this.editor.getText()
	      });
	    },
	    handleTeleport(isSlotShown) {
	      if (isSlotShown === true) {
	        setTimeout(() => {
	          this.isNeedTeleport = true;
	        }, 100);
	        this.editor.setVisualOptions({
	          blockSpaceInline: 0
	        });
	      } else {
	        this.isNeedTeleport = false;
	        this.editor.setMaxHeight(null);
	        this.editor.setVisualOptions({
	          blockSpaceInline: 'var(--ui-space-stack-md2)'
	        });
	      }
	    },
	    onFileBrowserClose() {
	      this.fileService.setFileBrowserClosed(false);
	    }
	  },
	  template: `
		<div class="tasks-card-change-description-mini-container">
			<div
				class="tasks-full-card-field-container --description-preview"
				ref="container"
				tabindex="-1"
			>
				<Teleport :to="isNeedTeleport ? '#descriptionTextAreaDestination' : undefined" :disabled="!isNeedTeleport">
					<DescriptionTextArea
						:taskId="taskId"
						ref="descriptionTextArea"
						@change="handleEditorChange"
						@filesChange="handleEditorChange"
					/>
				</Teleport>
				<div class="tasks-card-description-footer-container">
					<div class="tasks-card-description-footer">
						<div class="tasks-card-description-action-list">
							<Copilot />
							<Attach ref="attach" @click="handleAttachButtonClick"/>
							<Mention @click="handleMentionButtonClick"/>
						</div>
						<div
							class="tasks-card-description-footer-buttons"
							ref="fullDescriptionArea"
						>
							<FullDescription @click="handleExpand"/>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const DescriptionPreview = {
	  name: 'TaskDescriptionPreview',
	  components: {
	    UserFieldWidgetComponent: disk_uploader_userFieldWidget.UserFieldWidgetComponent,
	    HtmlFormatterComponent: ui_bbcode_formatter_htmlFormatter.HtmlFormatterComponent,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    files: {
	      type: Array,
	      required: true
	    },
	    filesCount: {
	      type: Number,
	      required: true
	    },
	    isMiniFormShown: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['previewButtonClick'],
	  setup(props) {
	    return {
	      BIcon: ui_iconSet_api_vue.BIcon,
	      Outline: ui_iconSet_api_core.Outline,
	      uploaderAdapter: tasks_v2_provider_service_fileService.fileService.get(props.taskId).getAdapter()
	    };
	  },
	  data() {
	    return {
	      isOverflowing: false,
	      opened: false,
	      isMouseDown: false,
	      selectionMade: false,
	      showRightShadow: null
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    taskDescription() {
	      var _this$task$descriptio;
	      return (_this$task$descriptio = this.task.description) != null ? _this$task$descriptio : '';
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    isEdit() {
	      return main_core.Type.isNumber(this.taskId) && this.taskId > 0;
	    },
	    widgetOptions() {
	      return {
	        isEmbedded: true,
	        withControlPanel: false,
	        canCreateDocuments: false,
	        tileWidgetOptions: {
	          compact: true,
	          hideDropArea: true,
	          readonly: this.readonly,
	          enableDropzone: false,
	          autoCollapse: false,
	          removeFromServer: !this.isEdit
	        }
	      };
	    },
	    hidden() {
	      if (this.opened) {
	        return false;
	      }
	      return this.filesCount || this.isOverflowing;
	    },
	    iconSize() {
	      return 20;
	    }
	  },
	  watch: {
	    async taskDescription() {
	      await this.$nextTick();
	      this.updateIsOverflowing();
	    }
	  },
	  async mounted() {
	    setTimeout(this.updateIsOverflowing, 400);
	    if (this.isMiniFormShown) {
	      this.openPreview();
	    }
	  },
	  methods: {
	    updateIsOverflowing() {
	      if (this.opened || !this.$refs.htmlFormatter) {
	        return;
	      }
	      this.showRightShadow = this.$refs.htmlFormatter.$el.offsetHeight < 50;
	      this.isOverflowing = this.$refs.preview.offsetHeight - 20 < this.$refs.htmlFormatter.$el.offsetHeight;
	    },
	    onPreviewClick() {
	      if (this.readonly && this.hidden) {
	        this.openPreview();
	      }
	      if (!this.readonly) {
	        this.$emit('previewButtonClick', {
	          doOpenInEditMode: false
	        });
	      }
	    },
	    openPreview() {
	      if (this.$refs.htmlFormatter) {
	        main_core.Dom.style(this.$refs.preview, 'maxHeight', `${this.$refs.htmlFormatter.$el.offsetHeight + 32}px`);
	      }
	      this.opened = true;
	    },
	    onHideClick() {
	      main_core.Dom.style(this.$refs.preview, 'maxHeight', '120px');
	      this.opened = false;
	    },
	    onMouseDown(event) {
	      if (event.button === 0) {
	        this.isMouseDown = true;
	        this.selectionMade = false;
	      }
	    },
	    onMouseMove() {
	      if (this.selectionMade) {
	        return;
	      }
	      if (this.isMouseDown) {
	        const selection = window.getSelection();
	        if (selection.toString().length > 0) {
	          this.selectionMade = true;
	        }
	      }
	    },
	    onMouseUp() {
	      this.isMouseDown = false;
	      if (!this.selectionMade) {
	        this.onPreviewClick();
	      }
	    }
	  },
	  template: `
		<div class="tasks-full-card-field-container">
			<div
				v-if="taskDescription.length > 0"
				class="tasks-card-description-preview"
				ref="preview"
			>
				<HtmlFormatterComponent
					:bbcode="taskDescription"
					:options="{ fileMode: 'disk' }"
					:formatData="{ files }"
					ref="htmlFormatter"
					@mousedown="onMouseDown"
					@mousemove="onMouseMove"
					@mouseup="onMouseUp"
				/>
				<template v-if="hidden">
					<div class="tasks-card-description-shadow">
						<div v-if="showRightShadow === true" class="tasks-card-description-shadow-white-right"/>
						<div v-else-if="showRightShadow === false" class="tasks-card-description-shadow-white-bottom"/>
					</div>
				</template>
				<div
					v-if="hidden"
					class="tasks-card-description-preview-button"
					:style="{ 'bottom': showRightShadow ? '16px' : '12px' }"
					@click="onPreviewClick"
				>
					<span class="tasks-card-description-preview-button-files" v-if="filesCount">
						<BIcon
							:name="Outline.ATTACH"
							:size="iconSize"
							class="tasks-card-description-field-icon-link"
						/>
						<span class="tasks-card-description-preview-button-text">
							{{ filesCount }}
						</span>
					</span>
					<span v-if="isOverflowing" class="tasks-card-description-preview-button-text">
						{{ loc('TASKS_V2_DESCRIPTION_PREVIEW_BUTTON_MORE') }}
					</span>
				</div>
			</div>
			<div
				v-if="opened && filesCount"
				class="tasks-card-description-editor-files --read-only"
				:class="{ '--with-description': taskDescription.length > 0 }"
				ref="filesWrapper"
			>
				<UserFieldWidgetComponent
					:uploaderAdapter="uploaderAdapter"
					:widgetOptions="widgetOptions"
				/>
			</div>
			<div
				v-if="opened && !isMiniFormShown"
				class="tasks-card-description-preview-button --hide"
				@click="onHideClick"
			>
				<div class="tasks-card-description-preview-button-more">
					<span class="tasks-card-description-preview-button-text">
						{{ loc('TASKS_V2_DESCRIPTION_BUTTON_COLLAPSE') }}
					</span>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const DescriptionField = {
	  name: 'TaskDescriptionField',
	  components: {
	    MiniFormButton,
	    MiniForm,
	    DescriptionPreview
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup(props) {
	    return {
	      fileService: tasks_v2_provider_service_fileService.fileService.get(props.taskId),
	      uploaderAdapter: tasks_v2_provider_service_fileService.fileService.get(props.taskId).getAdapter()
	    };
	  },
	  data() {
	    return {
	      isMiniFormShown: false,
	      isSlotShown: false,
	      doOpenInEditMode: false,
	      files: this.fileService.getFiles()
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isEdit() {
	      return main_core.Type.isNumber(this.taskId) && this.taskId > 0;
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    filesCount() {
	      return this.files.length;
	    }
	  },
	  methods: {
	    openSlotInEditMode() {
	      this.doOpenInEditMode = true;
	      this.isSlotShown = true;
	    },
	    closeMiniForm() {
	      this.isMiniFormShown = false;
	    },
	    onPreviewButtonClick(eventData) {
	      this.doOpenInEditMode = eventData.doOpenInEditMode === true;
	      this.isMiniFormShown = true;
	    },
	    closeSlot() {
	      this.isSlotShown = false;
	    },
	    async save() {
	      var _this$$refs, _this$$refs$miniForm;
	      await ((_this$$refs = this.$refs) == null ? void 0 : (_this$$refs$miniForm = _this$$refs.miniForm) == null ? void 0 : _this$$refs$miniForm.handleAddButtonClick());
	    }
	  },
	  template: `
		<slot
			:isShown="isSlotShown"
			:doOpenInEditMode="doOpenInEditMode"
			:close="closeSlot"
		/>
		<div
			v-if="!readonly || task.description.length > 0 || filesCount > 0"
			class="tasks-card-description-field"
			:data-task-id="taskId"
			:data-task-field-id="'description'"
		>
			<MiniFormButton
				v-if="(task.description.length === 0) && !isMiniFormShown"
				:filesCount="filesCount"
				@click="isMiniFormShown = true"
			/>
			<MiniForm
				v-else-if="!readonly && (isMiniFormShown || (task.description.length > 0 && !isEdit))"
				:taskId="taskId"
				:isSlotShown="isSlotShown"
				@expand="openSlotInEditMode"
				@closeEdit="closeMiniForm"
				ref="miniForm"
			/>
			<DescriptionPreview
				v-else-if="isMiniFormShown || isEdit"
				:taskId="taskId"
				:files="files"
				:filesCount="filesCount"
				:isMiniFormShown="isMiniFormShown"
				@previewButtonClick="onPreviewButtonClick"
			/>
		</div>
	`
	};

	// @vue/component
	const DescriptionSheet = {
	  name: 'TaskDescriptionSheet',
	  components: {
	    BottomSheet: tasks_v2_component_elements_bottomSheet.BottomSheet,
	    DescriptionEditor,
	    DropZone: tasks_v2_component_dropZone.DropZone
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    isShown: {
	      type: Boolean,
	      required: true
	    },
	    doOpenInEditMode: {
	      type: Boolean,
	      default: false
	    },
	    getBindElement: {
	      type: Function,
	      default: null
	    },
	    getTargetContainer: {
	      type: Function,
	      default: null
	    }
	  },
	  emits: ['show', 'close'],
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      titleFieldOffsetHeight: `${tasks_v2_const.Model.Interface}/titleFieldOffsetHeight`
	    })
	  },
	  watch: {
	    titleFieldOffsetHeight() {
	      var _this$$refs$bottomShe;
	      (_this$$refs$bottomShe = this.$refs.bottomSheet) == null ? void 0 : _this$$refs$bottomShe.adjustPosition();
	    }
	  },
	  methods: {
	    handleShow() {
	      this.$emit('show');
	    }
	  },
	  template: `
		<BottomSheet
			v-if="isShown"
			:isExpanded="true"
			:getBindElement="getBindElement"
			:getTargetContainer="getTargetContainer"
			ref="bottomSheet"
		>
			<DescriptionEditor
				ref="editorComponent"
				:taskId="taskId"
				:doOpenInEditMode="doOpenInEditMode"
				:isExpanded="true"
				@show="handleShow"
				@close="$emit('close')"
			/>
			<DropZone :taskId="taskId"/>
		</BottomSheet>
	`
	};

	// @vue/component
	const DescriptionPopup = {
	  name: 'TaskDescriptionPopup',
	  components: {
	    Popup: ui_vue3_components_popup.Popup,
	    DescriptionEditor
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    isShown: {
	      type: Boolean,
	      required: true
	    },
	    doOpenInEditMode: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['show', 'close', 'resize'],
	  setup() {
	    return {
	      resizeObserver: null
	    };
	  },
	  data() {
	    return {
	      popupHeight: 0
	    };
	  },
	  computed: {
	    popupId() {
	      return `tasks-field-description-popup-${this.taskId}`;
	    },
	    popupOptions() {
	      return {
	        className: 'tasks-card-description-popup',
	        minHeight: 360,
	        maxHeight: this.popupMaxHeight,
	        width: 580,
	        offsetTop: 0,
	        padding: 0,
	        autoHide: false,
	        closeByEsc: false,
	        animation: {
	          showClassName: 'tasks-description-popup-show',
	          closeClassName: 'tasks-description-popup-close',
	          closeAnimationType: 'animation'
	        },
	        events: {
	          onAfterShow: () => this.$refs.editorComponent.focusToEnd()
	        }
	      };
	    },
	    popupMaxHeight() {
	      return document.body.offsetHeight - 120 - this.titleFieldOffsetHeight;
	    },
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    ...ui_vue3_vuex.mapGetters({
	      titleFieldOffsetHeight: `${tasks_v2_const.Model.Interface}/titleFieldOffsetHeight`
	    })
	  },
	  watch: {
	    async titleFieldOffsetHeight() {
	      if (!this.$refs.popupComponent) {
	        return;
	      }
	      this.resizeEditor();
	      await this.$nextTick();
	      this.onResize();
	    }
	  },
	  created() {
	    this.resizeObserver = new ResizeObserver(entries => {
	      for (const entry of entries) {
	        if (entry.target === this.$refs.popupWrapper) {
	          this.onResize();
	        }
	      }
	    });
	  },
	  mounted() {
	    main_core.Event.bind(window, 'resize', this.onResize);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(window, 'resize', this.onResize);
	  },
	  methods: {
	    onShow() {
	      var _this$$refs$popupComp;
	      this.resizeEditor();
	      this.$emit('show', {
	        popupInstance: this.$refs.popupComponent.getPopupInstance()
	      });
	      (_this$$refs$popupComp = this.$refs.popupComponent) == null ? void 0 : _this$$refs$popupComp.getPopupInstance().adjustPosition();
	      setTimeout(() => this.resizeObserver.observe(this.$refs.popupWrapper), 300);
	    },
	    resizeEditor() {
	      var _this$$refs$popupComp2;
	      const popupInstance = (_this$$refs$popupComp2 = this.$refs.popupComponent) == null ? void 0 : _this$$refs$popupComp2.getPopupInstance();
	      const popupContainer = popupInstance.getPopupContainer();
	      this.$refs.editorComponent.hideEditor();
	      main_core.Dom.style(popupContainer, 'min-height', 0);
	      const popupWithoutEditorHeight = popupContainer.clientHeight;
	      const additionalOffset = 240;
	      const maxHeight = document.body.clientHeight - popupWithoutEditorHeight - additionalOffset - this.titleFieldOffsetHeight;
	      main_core.Dom.style(popupContainer, 'min-height', '360px');
	      this.$refs.editorComponent.showEditor();
	      this.$refs.editorComponent.setMaxHeight(maxHeight);
	      popupInstance.setOffset();
	    },
	    onResize() {
	      var _this$$refs$popupComp3;
	      const popupInstance = (_this$$refs$popupComp3 = this.$refs.popupComponent) == null ? void 0 : _this$$refs$popupComp3.getPopupInstance();
	      if (popupInstance) {
	        this.$emit('resize');
	        popupInstance.adjustPosition();
	      }
	    },
	    onClose() {
	      this.resizeObserver.disconnect();
	      this.$emit('close');
	    }
	  },
	  template: `
		<Popup v-if="isShown" :options="popupOptions" ref="popupComponent">
			<div class="tasks-card-description-popup-wrapper" ref="popupWrapper">
				<DescriptionEditor
					ref="editorComponent"
					:taskId="taskId"
					:doOpenInEditMode="doOpenInEditMode"
					@show="onShow"
					@close="onClose"
				></DescriptionEditor>
			</div>
		</Popup>
	`
	};

	// @vue/component
	const DescriptionInline = {
	  name: 'TaskDescriptionInline',
	  components: {
	    TextEditorComponent: ui_textEditor.TextEditorComponent
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      editor: null,
	      DefaultEditorOptions
	    };
	  },
	  data(props) {
	    return {
	      isFocused: false,
	      isScrolledToTop: true,
	      isScrolledToBottom: true,
	      fileService: tasks_v2_provider_service_fileService.fileService.get(props.taskId)
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    taskDescription() {
	      return this.task.description;
	    }
	  },
	  created() {
	    const additionalEditorOptions = {
	      minHeight: 20,
	      maxHeight: 112,
	      placeholder: this.loc('TASKS_V2_DESCRIPTION_INLINE_EDITOR_PLACEHOLDER'),
	      content: this.taskDescription,
	      newLineMode: 'paragraph',
	      events: {
	        onFocus: this.handleEditorFocus,
	        onBlur: this.handleEditorBlur,
	        onChange: this.handleEditorChange
	      }
	    };
	    this.editor = new ui_textEditor.TextEditor({
	      ...DefaultEditorOptions,
	      ...additionalEditorOptions
	    });
	  },
	  mounted() {
	    main_core.Event.bind(this.editor.getScrollerContainer(), 'scroll', this.handleScroll);
	    this.fileService.getAdapter().getUploader().assignPaste(this.$refs.description);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(this.editor.getScrollerContainer(), 'scroll', this.handleScroll);
	    this.fileService.getAdapter().getUploader().unassignPaste(this.$refs.description);
	  },
	  methods: {
	    hasScroll() {
	      return !this.isScrolledToTop || !this.isScrolledToBottom;
	    },
	    async handleEditorFocus() {
	      this.isFocused = true;
	    },
	    async handleEditorBlur() {
	      this.isFocused = false;
	      const description = this.editor.getText();
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        description
	      });
	    },
	    handleEditorChange() {
	      this.handleScroll();
	    },
	    handleScroll() {
	      const container = this.editor.getScrollerContainer();
	      this.isScrolledToTop = container.scrollTop === 0;
	      this.isScrolledToBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 5;
	    }
	  },
	  template: `
		<div
			class="tasks-card-description-inline"
			:class="{ '--bottom-shadow': !isScrolledToBottom, '--top-shadow': !isScrolledToTop }"
			:data-task-id="taskId"
			:data-task-field-id="'description'"
			ref="description"
		>
			<div class="tasks-card-description-inline-shadow --revert" :class="{'--shown': !isScrolledToTop}">
				<div class="tasks-card-description-inline-shadow-white"/>
				<div class="tasks-card-description-inline-shadow-black"/>
			</div>
			<TextEditorComponent :editorInstance="editor"/>
			<div class="tasks-card-description-inline-shadow" :class="{'--shown': !isScrolledToBottom}">
				<div class="tasks-card-description-inline-shadow-white"/>
				<div class="tasks-card-description-inline-shadow-black"/>
			</div>
		</div>
	`
	};

	exports.DescriptionEditor = DescriptionEditor;
	exports.DescriptionField = DescriptionField;
	exports.DescriptionSheet = DescriptionSheet;
	exports.DescriptionPopup = DescriptionPopup;
	exports.DescriptionInline = DescriptionInline;
	exports.descriptionTextEditor = descriptionTextEditor;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Vue3.Components,BX.UI.Uploader,BX.UI.Lexical.Core,BX.Tasks.V2,BX.Event,BX.Vue3.Directives,BX.Tasks.V2.Component.Elements,BX.UI.BBCode.Formatter,BX.UI.IconSet,BX.UI.IconSet,BX,BX.Disk.Uploader,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component,BX.UI.Vue3.Components,BX.Vue3.Vuex,BX,BX.UI.TextEditor,BX.Tasks.V2.Model,BX.Tasks.V2.Const,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=description.bundle.js.map
