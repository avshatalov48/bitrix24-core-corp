/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_vue3_directives_hint,tasks_v2_component_elements_hint,main_core,ui_vue3_vuex,tasks_v2_component_elements_bottomSheet,tasks_v2_component_dropZone,ui_iconSet_api_vue,ui_uploader_core,ui_iconSet_animated,tasks_v2_component_elements_chip,tasks_v2_const,tasks_v2_lib_fieldHighlighter,tasks_v2_lib_analytics,ui_vue3_components_popup,ui_vue3_components_button,ui_iconSet_api_core,ui_iconSet_outline,disk_uploader_userFieldWidget,tasks_v2_provider_service_fileService) {
	'use strict';

	const filesMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Files,
	  title: main_core.Loc.getMessage('TASKS_V2_FILES_TITLE')
	});

	// @vue/component
	const Files = {
	  name: 'TaskFiles',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UserFieldWidgetComponent: disk_uploader_userFieldWidget.UserFieldWidgetComponent
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['open'],
	  setup(props) {
	    return {
	      Animated: ui_iconSet_api_core.Animated,
	      Outline: ui_iconSet_api_core.Outline,
	      filesMeta,
	      fileService: tasks_v2_provider_service_fileService.fileService.get(props.taskId),
	      uploaderAdapter: tasks_v2_provider_service_fileService.fileService.get(props.taskId).getAdapter()
	    };
	  },
	  data() {
	    return {
	      files: this.fileService.getFiles(),
	      isFilesScrollable: false,
	      showPreview: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    filesCount() {
	      return this.files.length;
	    },
	    textFormatted() {
	      if (this.filesCount > 0) {
	        return this.loc('TASKS_V2_FILES_COUNT', {
	          '#COUNT#': this.filesCount
	        });
	      }
	      return this.loc('TASKS_V2_FILES_TITLE');
	    },
	    isUploading() {
	      return this.files.some(({
	        status
	      }) => [ui_uploader_core.FileStatus.UPLOADING, ui_uploader_core.FileStatus.LOADING].includes(status));
	    },
	    canAttachFiles() {
	      return this.task.rights.attachFile || this.task.rights.edit;
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
	          enableDropzone: false,
	          readonly: !this.canAttachFiles,
	          autoCollapse: false,
	          removeFromServer: !this.isEdit,
	          forceDisableSelection: true
	        }
	      };
	    },
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.loc('TASKS_V2_FILES_ADD'),
	        popupOptions: {
	          offsetLeft: this.$refs.add.$el.offsetWidth / 2
	        }
	      });
	    }
	  },
	  watch: {
	    files: {
	      handler(newFiles) {
	        if (newFiles.length > 0 && this.fileService.isFileBrowserClosed()) {
	          this.showPreview = true;
	          this.fileService.resetFileBrowserClosedState();
	        }
	        this.handleFilesScrollable();
	      },
	      deep: true
	    }
	  },
	  mounted() {
	    this.showPreview = this.isEdit;
	    this.fileService.subscribe('onFileAdd', this.handleFilesScrollable);
	    this.fileService.subscribe('onFileRemove', this.handleFilesScrollable);
	  },
	  beforeUnmount() {
	    this.fileService.unsubscribe('onFileAdd', this.handleFilesScrollable);
	    this.fileService.unsubscribe('onFileRemove', this.handleFilesScrollable);
	  },
	  methods: {
	    handleAddClick() {
	      this.fileService.browse({
	        bindElement: this.$refs.add.$el,
	        onHideCallback: this.onFileBrowserClose
	      });
	    },
	    handleOpenClick() {
	      if (this.filesCount === 0) {
	        this.fileService.browse({
	          bindElement: this.$refs.title,
	          onHideCallback: this.onFileBrowserClose
	        });
	      } else {
	        this.$emit('open');
	      }
	    },
	    async handleFilesScrollable() {
	      await this.$nextTick();
	      this.checkFilesScrollable();
	    },
	    checkFilesScrollable() {
	      if (this.$refs.files) {
	        this.isFilesScrollable = this.$refs.files.scrollHeight > this.$refs.files.clientHeight;
	      }
	    },
	    onFileBrowserClose() {
	      this.fileService.setFileBrowserClosed(true);
	    }
	  },
	  template: `
		<div
			class="tasks-field-files"
			:data-task-id="taskId"
			:data-task-field-id="filesMeta.id"
			:data-task-files-count="filesCount"
		>
			<div class="tasks-field-files-title">
				<div
					class="tasks-field-files-main"
					data-task-files-open
					@click="handleOpenClick"
					ref="title"
				>
					<template v-if="isUploading">
						<BIcon class="tasks-field-files-icon" :name="Animated.LOADER_WAIT"/>
						<div class="tasks-field-files-text">{{ loc('TASKS_V2_FILES_LOADING') }}</div>
					</template>
					<template v-else>
						<BIcon class="tasks-field-files-icon" :name="Outline.ATTACH"/>
						<div class="tasks-field-files-text">{{ textFormatted }}</div>
					</template>
				</div>
				<div v-if="canAttachFiles" class="tasks-field-files-add-container" v-hint="tooltip">
					<BIcon
						class="tasks-field-files-add"
						:name="Outline.PLUS_L"
						:hoverable="true"
						data-task-files-plus
						@click="handleAddClick"
						ref="add"
					/>
				</div>
			</div>
			<div
				v-if="showPreview && filesCount > 0"
				class="tasks-field-files-list --card"
				ref="files"
			>
				<UserFieldWidgetComponent
					:uploaderAdapter="uploaderAdapter"
					:widgetOptions="widgetOptions"
				/>
			</div>
			<template v-if="isFilesScrollable">
				<div class="tasks-field-files-shadow">
					<div class="tasks-field-files-shadow-white"/>
				</div>
				<div
					class="tasks-field-files-more-button"
					@click="$emit('open')"
				>
					<div class="tasks-field-files-more-button-text">
						{{ loc('TASKS_V2_FILES_MORE') }}
					</div>
				</div>
			</template>
		</div>
	`
	};

	// @vue/component
	const UploadButton = {
	  components: {
	    UiButton: ui_vue3_components_button.Button
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      ButtonSize: ui_vue3_components_button.ButtonSize,
	      AirButtonStyle: ui_vue3_components_button.AirButtonStyle,
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  methods: {
	    handleClick() {
	      tasks_v2_provider_service_fileService.fileService.get(this.taskId).browse({
	        bindElement: this.$el
	      });
	    }
	  },
	  template: `
		<span data-task-files-upload>
			<UiButton
				:text="loc('TASKS_V2_FILES_UPLOAD')"
				:size="ButtonSize.MEDIUM"
				:style="AirButtonStyle.SELECTION"
				:leftIcon="Outline.ATTACH"
				@click="handleClick"
			/>
		</span>
	`
	};

	// @vue/component
	const DownloadArchiveButton = {
	  components: {
	    UiButton: ui_vue3_components_button.Button
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    files: {
	      type: Array,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      ButtonSize: ui_vue3_components_button.ButtonSize,
	      AirButtonStyle: ui_vue3_components_button.AirButtonStyle,
	      ButtonCounterColor: ui_vue3_components_button.ButtonCounterColor,
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    archiveLink() {
	      return this.task.archiveLink;
	    },
	    filesSize() {
	      return this.files.reduce((total, file) => total + file.size, 0);
	    },
	    formattedFilesSize() {
	      return ui_uploader_core.formatFileSize(this.filesSize);
	    }
	  },
	  template: `
		<a :href="archiveLink" data-task-files-download-archive>
			<UiButton
				:text="loc('TASKS_V2_FILES_DOWNLOAD_ARCHIVE', { '#FILES_SIZE#': formattedFilesSize })"
				:size="ButtonSize.MEDIUM"
				:style="AirButtonStyle.PLAIN_NO_ACCENT"
			/>
		</a>
	`
	};

	// @vue/component
	const FilesSheet = {
	  name: 'TaskFilesSheet',
	  components: {
	    UserFieldWidgetComponent: disk_uploader_userFieldWidget.UserFieldWidgetComponent,
	    UploadButton,
	    DownloadArchiveButton,
	    BottomSheet: tasks_v2_component_elements_bottomSheet.BottomSheet,
	    BIcon: ui_iconSet_api_vue.BIcon,
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
	    getBindElement: {
	      type: Function,
	      default: null
	    },
	    getTargetContainer: {
	      type: Function,
	      default: null
	    }
	  },
	  emits: ['close'],
	  setup(props) {
	    return {
	      Outline: ui_iconSet_api_core.Outline,
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
	    ...ui_vue3_vuex.mapGetters({
	      titleFieldOffsetHeight: `${tasks_v2_const.Model.Interface}/titleFieldOffsetHeight`
	    }),
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    canAttachFiles() {
	      return this.task.rights.attachFile || this.task.rights.edit;
	    },
	    filesCount() {
	      return this.files.length;
	    },
	    archiveLink() {
	      return this.task.archiveLink;
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
	          autoCollapse: false,
	          readonly: !this.canAttachFiles,
	          enableDropzone: false,
	          hideDropArea: true,
	          removeFromServer: !this.isEdit,
	          forceDisableSelection: true
	        }
	      };
	    },
	    showFooter() {
	      return this.filesCount > 1 || this.canAttachFiles;
	    },
	    canDownloadArchive() {
	      return this.filesCount > 1 && this.archiveLink;
	    }
	  },
	  watch: {
	    titleFieldOffsetHeight() {
	      var _this$$refs$bottomShe;
	      (_this$$refs$bottomShe = this.$refs.bottomSheet) == null ? void 0 : _this$$refs$bottomShe.adjustPosition();
	    }
	  },
	  template: `
		<BottomSheet
			v-if="isShown"
			:getBindElement="getBindElement"
			:getTargetContainer="getTargetContainer"
			ref="bottomSheet"
		>
			<div class="tasks-field-files-sheet" :data-task-id="taskId">
				<div class="tasks-field-files-header">
					<div class="tasks-field-files-title">
						{{ loc('TASKS_V2_FILES_TITLE') }}
					</div>
					<BIcon
						data-task-files-close
						class="tasks-field-files-close"
						:hoverable="true"
						:name="Outline.CROSS_L"
						@click="$emit('close')"
					/>
				</div>
				<div class="tasks-field-files-list">
					<UserFieldWidgetComponent
						:uploaderAdapter="uploaderAdapter"
						:widgetOptions="widgetOptions"
					/>
				</div>
				<div
					v-if="showFooter"
					class="tasks-field-files-footer"
					:class="{ '--with-archive': canDownloadArchive }"
				>
					<DownloadArchiveButton
						v-if="canDownloadArchive"
						:taskId="taskId"
						:files="files"
					/>
					<UploadButton v-if="canAttachFiles" :taskId="taskId"/>
				</div>
			</div>
			<DropZone :taskId="taskId"/>
		</BottomSheet>
	`
	};

	// @vue/component
	const FilesPopup = {
	  components: {
	    Popup: ui_vue3_components_popup.Popup,
	    UiButton: ui_vue3_components_button.Button,
	    UserFieldWidgetComponent: disk_uploader_userFieldWidget.UserFieldWidgetComponent
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    getBindElement: {
	      type: Function,
	      required: true
	    }
	  },
	  emits: ['upload', 'close'],
	  setup(props) {
	    return {
	      ButtonSize: ui_vue3_components_button.ButtonSize,
	      AirButtonStyle: ui_vue3_components_button.AirButtonStyle,
	      ButtonIcon: ui_vue3_components_button.ButtonIcon,
	      Outline: ui_iconSet_api_core.Outline,
	      uploaderAdapter: tasks_v2_provider_service_fileService.fileService.get(props.taskId).getAdapter()
	    };
	  },
	  data() {
	    return {
	      files: tasks_v2_provider_service_fileService.fileService.get(this.taskId).getFiles()
	    };
	  },
	  computed: {
	    bindElement() {
	      return this.getBindElement();
	    },
	    popupOptions() {
	      return {
	        id: 'tasks-field-files-popup',
	        bindElement: this.bindElement,
	        minWidth: 380,
	        maxHeight: 320,
	        padding: 18,
	        bindOptions: {
	          forceBindPosition: true,
	          forceTop: true,
	          position: 'top'
	        },
	        closeByEsc: true,
	        autoHideHandler: event => {
	          var _this$$refs$popup;
	          const anyTileMenuShown = this.files.some(({
	            isMenuShown
	          }) => isMenuShown);
	          return ((_this$$refs$popup = this.$refs.popup) == null ? void 0 : _this$$refs$popup.autoHideHandler(event)) && !anyTileMenuShown;
	        }
	      };
	    },
	    widgetOptions() {
	      return {
	        isEmbedded: true,
	        withControlPanel: false,
	        canCreateDocuments: false,
	        tileWidgetOptions: {
	          hideDropArea: true,
	          enableDropzone: false,
	          compact: true,
	          autoCollapse: false
	        }
	      };
	    },
	    filesCount() {
	      return this.files.length;
	    }
	  },
	  watch: {
	    filesCount() {
	      if (this.filesCount === 0) {
	        this.closePopup();
	      }
	    }
	  },
	  methods: {
	    handleClearClick() {
	      tasks_v2_provider_service_fileService.fileService.get(this.taskId).getAdapter().getUploader().removeFiles();
	      this.closePopup();
	    },
	    handleUploadClick() {
	      tasks_v2_provider_service_fileService.fileService.get(this.taskId).browseFiles();
	    },
	    handleMyDriveClick() {
	      tasks_v2_provider_service_fileService.fileService.get(this.taskId).browseMyDrive();
	    },
	    closePopup() {
	      this.$emit('close');
	    }
	  },
	  template: `
		<Popup
			:options="popupOptions"
			ref="popup"
			@close="closePopup"
		>
			<div class="tasks-field-files-popup">
				<div class="tasks-field-files-popup-files">
					<UserFieldWidgetComponent :uploaderAdapter="uploaderAdapter" :widgetOptions="widgetOptions"/>
				</div>
				<div class="tasks-field-files-popup-footer">
					<div class="tasks-field-files-popup-add-buttons">
						<UiButton
							:text="loc('TASKS_V2_FILES_UPLOAD_BUTTON')"
							:size="ButtonSize.SMALL"
							:style="AirButtonStyle.TINTED"
							:leftIcon="Outline.DOWNLOAD"
							@click="handleUploadClick"
						/>
						<UiButton
							:text="loc('TASKS_V2_FILES_MY_DRIVE')"
							:size="ButtonSize.SMALL"
							:style="AirButtonStyle.TINTED"
							:leftIcon="Outline.UPLOAD"
							@click="handleMyDriveClick"
						/>
					</div>
					<UiButton
						:text="loc('TASKS_V2_FILES_CLEAR')"
						:size="ButtonSize.SMALL"
						:style="AirButtonStyle.PLAIN_NO_ACCENT"
						:leftIcon="Outline.TRASHCAN"
						@click="handleClearClick"
					/>
				</div>
			</div>
		</Popup>
	`
	};

	// @vue/component
	const FilesChip = {
	  components: {
	    Chip: tasks_v2_component_elements_chip.Chip,
	    FilesPopup
	  },
	  inject: ['analytics', 'cardType'],
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    isAutonomous: {
	      type: Boolean,
	      default: false
	    }
	  },
	  setup(props) {
	    return {
	      filesMeta,
	      fileService: tasks_v2_provider_service_fileService.fileService.get(props.taskId)
	    };
	  },
	  data() {
	    return {
	      files: this.fileService.getFiles(),
	      isPopupShown: false,
	      isPopupShownWithFiles: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    group() {
	      return this.$store.getters[`${tasks_v2_const.Model.Groups}/getById`](this.task.groupId);
	    },
	    filesCount() {
	      return this.files.length;
	    },
	    design() {
	      return {
	        [!this.isAutonomous && !this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.ShadowNoAccent,
	        [!this.isAutonomous && this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.ShadowAccent,
	        [this.isAutonomous && !this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.OutlineNoAccent,
	        [this.isAutonomous && this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.OutlineAccent,
	        [this.hasError]: tasks_v2_component_elements_chip.ChipDesign.OutlineAlert
	      }.true;
	    },
	    isSelected() {
	      if (this.isAutonomous) {
	        return this.files.length > 0;
	      }
	      const wasFilesFilled = this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, filesMeta.id);
	      return wasFilesFilled || this.files.length > 0;
	    },
	    icon() {
	      if (this.isAutonomous && this.isUploading) {
	        return ui_iconSet_api_core.Animated.LOADER_WAIT;
	      }
	      return ui_iconSet_api_core.Outline.ATTACH;
	    },
	    text() {
	      if (this.isAutonomous && this.filesCount > 0) {
	        return this.loc('TASKS_V2_FILES_COUNT', {
	          '#COUNT#': this.filesCount
	        });
	      }
	      return filesMeta.title;
	    },
	    isUploading() {
	      return this.fileService.isUploading();
	    },
	    hasError() {
	      return this.files.some(({
	        status
	      }) => [ui_uploader_core.FileStatus.UPLOAD_FAILED, ui_uploader_core.FileStatus.LOAD_FAILED].includes(status));
	    },
	    canAttachFiles() {
	      return this.task.rights.attachFile || this.task.rights.edit;
	    },
	    popupHasAlreadyBeenShown() {
	      return this.filesCount > 0 && this.isPopupShownWithFiles;
	    }
	  },
	  mounted() {
	    this.fileService.subscribe('onFileAdd', this.handleFileAdd);
	    this.fileService.subscribe('onFileComplete', this.handleFileComplete);
	  },
	  beforeUnmount() {
	    this.fileService.unsubscribe('onFileAdd', this.handleFileAdd);
	    this.fileService.unsubscribe('onFileComplete', this.handleFileComplete);
	  },
	  methods: {
	    handleFileAdd() {
	      if (this.isAutonomous && this.popupHasAlreadyBeenShown) {
	        this.showPopup();
	      }
	    },
	    handleFileComplete(event) {
	      this.sendAnalytics(event.getData());
	    },
	    sendAnalytics(file) {
	      var _this$group;
	      tasks_v2_lib_analytics.analytics.sendAttachFile(this.analytics, {
	        cardType: this.cardType,
	        collabId: ((_this$group = this.group) == null ? void 0 : _this$group.type) === tasks_v2_const.GroupType.Collab ? this.group.id : null,
	        fileOrigin: file.origin,
	        fileSize: file.size,
	        fileExtension: file.extension,
	        filesCount: this.filesCount
	      });
	    },
	    handleClick() {
	      if (!this.isAutonomous && this.isSelected) {
	        this.highlightField();
	        return;
	      }
	      if (this.files.length > 0) {
	        this.isPopupShownWithFiles = true;
	        this.showPopup();
	      } else {
	        this.browseFiles();
	      }
	    },
	    showPopup() {
	      this.isPopupShown = true;
	    },
	    closePopup() {
	      this.isPopupShown = false;
	      if (!this.filesCount) {
	        this.isPopupShownWithFiles = false;
	      }
	    },
	    browseFiles() {
	      this.fileService.browse({
	        bindElement: this.$refs.chip.$el,
	        onHideCallback: this.onFileBrowserClose,
	        compact: this.cardType === tasks_v2_const.CardType.Compact
	      });
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(filesMeta.id);
	    },
	    onFileBrowserClose() {
	      var _this$$refs$chip;
	      (_this$$refs$chip = this.$refs.chip) == null ? void 0 : _this$$refs$chip.$el.focus();
	      this.fileService.setFileBrowserClosed(true);
	    }
	  },
	  template: `
		<Chip
			v-if="isSelected || canAttachFiles"
			:design="design"
			:icon="icon"
			:text="text"
			:data-task-id="taskId"
			:data-task-chip-id="filesMeta.id"
			ref="chip"
			@click="handleClick"
		/>
		<FilesPopup
			v-if="isPopupShown"
			:taskId="taskId"
			:getBindElement="() => $refs.chip.$el"
			@upload="browseFiles"
			@close="closePopup"
		/>
	`
	};

	exports.Files = Files;
	exports.FilesSheet = FilesSheet;
	exports.FilesChip = FilesChip;
	exports.filesMeta = filesMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Vue3.Directives,BX.Tasks.V2.Component.Elements,BX,BX.Vue3.Vuex,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component,BX.UI.IconSet,BX.UI.Uploader,BX,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Const,BX.Tasks.V2.Lib,BX.Tasks.V2.Lib,BX.UI.Vue3.Components,BX.Vue3.Components,BX.UI.IconSet,BX,BX.Disk.Uploader,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=files.bundle.js.map
