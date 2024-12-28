/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue3_components_hint,ui_alerts,ui_layoutForm,ui_uploader_core,ui_notification,main_core_events,ui_entitySelector,main_loader,ui_iconSet_main,ui_buttons,ui_iconSet_api_vue,ui_iconSet_api_core,ui_iconSet_actions,ui_vue3,main_core,main_popup) {
	'use strict';

	const RoleMasterEditor = {
	  props: {
	    text: String,
	    maxTextLength: Number,
	    minTextLength: {
	      type: Number,
	      required: false,
	      default: 5
	    },
	    placeholder: String
	  },
	  emits: ['update:text'],
	  computed: {
	    textLength() {
	      var _this$text;
	      return ((_this$text = this.text) == null ? void 0 : _this$text.length) || 0;
	    }
	  },
	  methods: {
	    handleInput(e) {
	      this.$emit('update:text', e.target.value);
	    }
	  },
	  mounted() {
	    requestAnimationFrame(() => {
	      this.$refs.textField.focus();
	    });
	  },
	  template: `
		<div class="ai__role-master_editor ui-ctl-textarea">
			<textarea
				ref="textField"
				:value="text"
				:maxlength="maxTextLength"
				:minlength="minTextLength"
				:placeholder="placeholder"
				@input="handleInput"
				class="ai__role-master_editor-text-field ui-ctl-element"
			></textarea>
			<div class="ai__role-master_editor-character-counter">
				{{ textLength }}/{{ maxTextLength }}
			</div>
		</div>
	`
	};

	const RoleMasterProgress = {
	  props: {
	    current: Number
	  },
	  computed: {
	    total() {
	      return 2;
	    }
	  },
	  methods: {
	    getProgressStepClassname(isActive) {
	      return {
	        'ai__role-master_progress-step': true,
	        '--active': isActive
	      };
	    }
	  },
	  template: `
		<div class="ai__role-master_progress">
			<span
				v-for="index in total"
				:class="getProgressStepClassname(index <= current)"
			>
			</span>
		</div>
	`
	};

	const RoleMasterWarning = {
	  props: {
	    text: String
	  },
	  computed: {
	    alertHtmlString() {
	      const alert = new ui_alerts.Alert({
	        inline: true,
	        text: this.text,
	        color: ui_alerts.Alert.Color.WARNING,
	        animated: false,
	        icon: ui_alerts.Alert.Icon.INFO,
	        size: ui_alerts.Alert.Size.XS,
	        closeBtn: false
	      });
	      alert.show();
	      return alert.render().outerHTML;
	    }
	  },
	  template: `
		<div v-html="alertHtmlString"></div>
	`
	};

	const RoleMasterStep = {
	  name: 'RoleMasterStep',
	  components: {
	    RoleMasterProgress,
	    RoleMasterWarning,
	    Hint: ui_vue3_components_hint.Hint
	  },
	  props: {
	    title: String,
	    titleHint: String,
	    stepNumber: Number,
	    warningText: String
	  },
	  template: `
		<div class="ai__role-master_step">
			<div class="ai__role-master_step-header">
				<div class="ai__role-master_step-title-with-hint">
					<h4 class="ai__role-master_step-title">{{ title }}</h4>
					<span v-if="titleHint" class="ai__role-master_step-title-hint">
						<Hint :text="titleHint"></Hint>
					</span>
				</div>
				<div class="ai__role-master_step-progress">
					<RoleMasterProgress
						:current="stepNumber"
					/>
				</div>
				<div v-if="warningText" class="ai__role-master_step-warning">
					<RoleMasterWarning :text="warningText" />
				</div>
			</div>
			<div class="ai__role-master_step-content">
				<slot></slot>
			</div>
		</div>
	`
	};

	const RoleMasterTextStep = {
	  props: {
	    roleText: String,
	    stepNumber: Number,
	    maxTextLength: Number,
	    minTextLength: Number,
	    warningText: String
	  },
	  components: {
	    RoleMasterStep,
	    RoleMasterEditor
	  },
	  methods: {
	    handleRoleTextUpdate(value) {
	      this.$emit('update:role-text', value);
	    }
	  },
	  template: `
		<RoleMasterStep
			:title="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_TEXT_STEP_TITLE')"
			:title-hint="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_TEXT_STEP_TITLE_HINT')"
			:warningText="warningText"
			:step-number="stepNumber"
		>
			<slot>
				<RoleMasterEditor
					:text="roleText"
					@update:text="handleRoleTextUpdate"
					:max-text-length="maxTextLength"
					:min-text-length="minTextLength"
					:placeholder="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_TEXT_FIELD_PLACEHOLDER')"
				></RoleMasterEditor>
			</slot>
		</RoleMasterStep>
	`
	};

	const RoleMasterBtn = {
	  props: {
	    text: String,
	    state: {
	      type: String,
	      required: false,
	      default: null
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ui_buttons.Button.Color.AI
	    }
	  },
	  computed: {
	    buttonOuterHtmlString() {
	      const button = new ui_buttons.Button({
	        text: this.text,
	        state: this.state,
	        color: this.color,
	        round: true
	      });
	      return button.render().outerHTML;
	    },
	    ButtonState() {
	      return ui_buttons.ButtonState;
	    }
	  },
	  methods: {
	    handleClick(e) {
	      if (this.state === ui_buttons.ButtonState.DISABLED) {
	        e.preventDefault();
	        e.stopImmediatePropagation();
	        return false;
	      }
	      return true;
	    }
	  },
	  template: '<div @click="handleClick" v-html="buttonOuterHtmlString"></div>'
	};

	const RoleMasterAvatarUploader = {
	  name: 'RoleMasterAvatarUploader',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    avatarUrl: ''
	  },
	  emits: ['uploadAvatarFile', 'removeAvatarFile', 'selectAvatar', 'generateAvatar'],
	  data() {
	    return {
	      uploader: null,
	      isDraggingFile: false
	    };
	  },
	  computed: {
	    avatarUploaderStyle() {
	      return {
	        backgroundImage: this.avatarUrl ? `url(${this.avatarUrl})` : null
	      };
	    },
	    IconSet() {
	      return ui_iconSet_api_vue.Set;
	    }
	  },
	  methods: {
	    handleDragElementEnterDocument() {
	      this.isDraggingFile = true;
	    },
	    handleDragElementLeaveDocument(event) {
	      if (event.relatedTarget === null) {
	        this.isDraggingFile = false;
	      }
	    },
	    handleDropFile() {
	      this.isDraggingFile = false;
	    },
	    handleDragOverFile(event) {
	      event.preventDefault();
	    },
	    showNotification(message) {
	      const id = String(Math.random() * 1000);
	      ui_notification.UI.Notification.Center.notify({
	        id,
	        content: message
	      });
	    },
	    removeUploadedFile() {
	      const files = this.uploader.getFiles();
	      if (files.length > 0) {
	        ui_vue3.toRaw(this.uploader).removeFiles();
	        this.$emit('removeAvatarFile');
	      }
	    }
	  },
	  mounted() {
	    this.uploader = new ui_uploader_core.Uploader({
	      dropElement: this.$refs.dropArea,
	      browseElement: this.$refs.uploaderContainer,
	      assignServerFile: false,
	      allowReplaceSingle: true,
	      assignAsFile: true,
	      acceptOnlyImages: true,
	      acceptedFileTypes: ['image/jpeg', 'image/png'],
	      maxFileSize: 1048076,
	      multiple: false,
	      events: {
	        [ui_uploader_core.UploaderEvent.FILE_LOAD_COMPLETE]: event => {
	          this.uploadedFile = event.getData().file;
	          this.$emit('uploadAvatarFile', event.getData().file);
	          this.$refs.uploaderContainer.blur();
	        },
	        [ui_uploader_core.UploaderEvent.FILE_ERROR]: event => {
	          const error = event.getData().error;
	          this.showNotification(error.getMessage());
	          this.$refs.uploaderContainer.blur();
	        },
	        [ui_uploader_core.UploaderEvent.FILE_REMOVE]: () => {
	          // TODO don't work this event
	          this.$emit('removeAvatarFile');
	          this.$refs.uploaderContainer.blur();
	        }
	      }
	    });
	    if (this.avatarUrl) {
	      this.uploader.addFile(0);
	      this.$emit('loadAvatarFile', ui_vue3.toRaw(this.uploader.getFiles()[0]));
	    }
	    main_core.bind(document, 'dragenter', this.handleDragElementEnterDocument);
	    main_core.bind(document, 'dragleave', this.handleDragElementLeaveDocument);
	    main_core.bind(document, 'drop', this.handleDropFile);
	    main_core.bind(document, 'dragover', this.handleDragOverFile);
	  },
	  unmounted() {
	    main_core.unbind(document, 'dragenter', this.handleDragElementEnterDocument);
	    main_core.unbind(document, 'dragleave', this.handleDragElementLeaveDocument);
	    main_core.unbind(document, 'drop', this.handleDropFile);
	    main_core.unbind(document, 'dragover', this.handleDragOverFile);
	  },
	  template: `
		<div
			class="ai__role-master_avatar-uploader-wrapper"
			:style="avatarUploaderStyle"
		>
			<div
				v-show="!avatarUrl"
				ref="uploaderContainer"
				class="ai__role-master_avatar-uploader"
				:tabindex="0"
				@keyup.enter="$event.target.click()"
			>
				<div class="ai__role-master_avatar-uploader-icon">
					<BIcon
						v-if="!avatarUrl"
						:size="28"
						:name="IconSet.CAMERA"
					></BIcon>
				</div>
				<div class="ai__role-master_avatar-uploader-hover">
					<BIcon
						v-if="isDraggingFile === false && !avatarUrl"
						:size="24"
						:name="IconSet.EDIT_PENCIL"
					></BIcon>
					<BIcon
						v-else-if="isDraggingFile === false && avatarUrl"
						:size="24"
						:name="IconSet.TRASH_BIN"
					></BIcon>
				</div>
				<teleport to=".ai__role-master-app-container main">
					<transition name="ai-role-master-drop-area-fade">
						<div
							ref="dropArea"
							v-show="isDraggingFile"
							class="ai__role-master_avatar-uploader-drop-area"
						>
							<div class="ai__role-master_avatar-uploader-drop-area-icon">
								<BIcon
									:size="64"
									:name="IconSet.FILE_UPLOAD"
								></BIcon>

							</div>
							<span class="ai__role-master_avatar-uploader-drop-area-text">
						{{ $Bitrix.Loc.getMessage('ROLE_MASTER_FILE_DROPAREA_TEXT') }}
					</span>
						</div>
					</transition>
				</teleport>
			</div>
			<div
				class="ai__role-master_avatar-uploader"
				v-if="isDraggingFile === false && avatarUrl"
				@click="removeUploadedFile"
			>
				<div class="ai__role-master_avatar-uploader-hover">
					<BIcon
						:size="24"
						:name="IconSet.TRASH_BIN"
					></BIcon>
				</div>
			</div>
		</div>
	`
	};

	const clickableHint = {
	  beforeMount(bindElement, bindings) {
	    let popup = null;
	    let isMouseOnHintPopup = false;
	    const destroyPopup = () => {
	      var _popup;
	      (_popup = popup) == null ? void 0 : _popup.destroy();
	      popup = null;
	      isMouseOnHintPopup = false;
	    };
	    main_core.Event.bind(bindElement, 'mouseenter', () => {
	      if (popup === null) {
	        popup = createHintPopup(bindElement, bindings.value);
	        popup.show();
	        main_core.Event.bind(popup.getPopupContainer(), 'mouseenter', () => {
	          isMouseOnHintPopup = true;
	        });
	      }
	    });
	    main_core.Event.bind(bindElement, 'mouseleave', () => {
	      var _popup2;
	      const popupContainer = (_popup2 = popup) == null ? void 0 : _popup2.getPopupContainer();
	      setTimeout(() => {
	        if (isMouseOnHintPopup) {
	          main_core.bind(popupContainer, 'mouseleave', e => {
	            if (bindElement.contains(e.relatedTarget) === false) {
	              destroyPopup();
	            }
	          });
	        } else {
	          destroyPopup();
	        }
	      }, 100);
	    });
	  }
	};
	function createHintPopup(bindElement, html) {
	  const bindElementPosition = main_core.Dom.getPosition(bindElement);
	  return new main_popup.Popup({
	    bindElement: {
	      top: bindElementPosition.top + 10,
	      left: bindElementPosition.left + bindElementPosition.width / 2
	    },
	    className: 'ai__role-master_hint-popup',
	    darkMode: true,
	    content: html,
	    maxWidth: 266,
	    maxHeight: 300,
	    animation: 'fading-slide',
	    angle: true,
	    bindOptions: {
	      position: 'top'
	    }
	  });
	}

	const RoleMasterUserSelector = {
	  directives: {
	    clickableHint
	  },
	  events: ['update:selected-items'],
	  props: {
	    selectedItems: {
	      type: Array,
	      required: false,
	      default: () => {
	        return [];
	      }
	    },
	    maxCirclesInInput: {
	      type: Number,
	      required: false,
	      default: 8
	    },
	    undeselectedItems: {
	      type: Array,
	      required: false,
	      default: () => {
	        return [];
	      }
	    }
	  },
	  data() {
	    return {
	      etcItemHint: null,
	      cursorOnEtcItem: false,
	      selectedItemsWithData: [],
	      dataIsLoaded: false
	    };
	  },
	  computed: {
	    preselectedItems() {
	      return this.typedSelectedItems.map(item => {
	        return item;
	      });
	    },
	    typedSelectedItems() {
	      return this.selectedItems;
	    },
	    etcItemHintContent() {
	      const titles = this.selectedItemsWithData.slice(this.maxCirclesInInput).map(item => this.getEncodedString(item.title));
	      const titlesText = titles.join('<br>');
	      return `<div>${titlesText}</div>`;
	    },
	    etcSelectedItemsCount() {
	      return this.selectedItems.slice(this.maxCirclesInInput).length;
	    },
	    etcSelectedItemsCircleNumber() {
	      return this.etcSelectedItemsCount < 100 ? this.etcSelectedItemsCount : 99;
	    }
	  },
	  methods: {
	    updateSelectedItemsWithData() {
	      const selectedItems = this.getUserSelectorDialog().getSelectedItems();
	      if (selectedItems.length === this.selectedItemsWithData.length) {
	        return;
	      }
	      this.selectedItemsWithData = selectedItems.map(item => {
	        return this.getSelectedItemsWithDataFromDialogItem(item);
	      });
	    },
	    getSelectedItemsWithDataFromDialogItem(item) {
	      return {
	        id: item.id,
	        avatar: item.avatar,
	        entityId: item.entityId,
	        title: item.title.text
	      };
	    },
	    getUserSelectorDialog() {
	      const existingDialog = ui_entitySelector.Dialog.getById('ai-role-master-user-selector');
	      if (existingDialog) {
	        existingDialog.setTargetNode(this.$refs.userSelector);
	        return existingDialog;
	      }
	      return new ui_entitySelector.Dialog({
	        id: 'ai-role-master-user-selector',
	        targetNode: this.$refs.userSelector,
	        width: 400,
	        height: 300,
	        dropdownMode: false,
	        showAvatars: true,
	        compactView: true,
	        multiple: true,
	        preload: true,
	        enableSearch: true,
	        entities: [{
	          id: 'user',
	          options: {
	            inviteEmployeeLink: false
	          }
	        }, {
	          id: 'department',
	          options: {
	            selectMode: 'usersAndDepartments'
	          }
	        }, {
	          id: 'meta-user',
	          options: {
	            'all-users': true
	          }
	        }, {
	          id: 'project'
	        }],
	        preselectedItems: this.preselectedItems,
	        undeselectedItems: this.undeselectedItems,
	        events: {
	          'Item:onSelect': event => {
	            this.selectItem(event.getData().item);
	          },
	          'Item:onDeselect': event => {
	            this.deselectItem(event.getData().item);
	          },
	          onLoad: () => {
	            this.dataIsLoaded = true;
	            this.updateSelectedItemsWithData();
	          },
	          onHide: () => {
	            this.$refs.addBtn.focus();
	          }
	        }
	      });
	    },
	    showUserSelector() {
	      const dialog = this.getUserSelectorDialog();
	      dialog.show();
	    },
	    selectItem(item) {
	      this.$emit('update:selected-items', this.getDialogSelectedItems());
	    },
	    deselectItem(item) {
	      this.$emit('update:selected-items', this.getDialogSelectedItems());
	    },
	    getDialogSelectedItems() {
	      return this.getUserSelectorDialog().getSelectedItems().map(item => {
	        return [item.entityId, item.getId()];
	      });
	    },
	    getSelectedItemStyle(item, index) {
	      const backgroundImage = `url('${this.getAvatarFromItem(item)}')`;
	      return {
	        backgroundImage,
	        left: `${index > 0 ? 24 * index : 0}px`
	      };
	    },
	    getAvatarFromItem(item) {
	      if (item.avatar) {
	        return item.avatar;
	      }
	      if (item.entityId === 'user') {
	        return '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';
	      }
	      if (item.entityId === 'meta-user') {
	        return '/bitrix/js/socialnetwork/entity-selector/src/images/meta-user-all.svg';
	      }
	      return '';
	    },
	    getDepartmentFirstLetter(title) {
	      return title.split(' ')[0][0].toUpperCase();
	    },
	    showEtcItemsHint() {
	      this.cursorOnEtcItem = true;
	      if (this.etcItemHint) {
	        return;
	      }
	      this.etcItemHint = new main_popup.Popup({
	        bindElement: this.$refs.etcItem,
	        darkMode: true,
	        content: this.etcItemHintContent,
	        autoHide: true,
	        maxHeight: 300,
	        bindOptions: {
	          position: 'top'
	        },
	        animation: 'fading-slide',
	        angle: true
	      });
	      this.etcItemHint.setOffset({
	        offsetTop: -10,
	        offsetLeft: 16
	      });
	      this.etcItemHint.show();
	    },
	    closeEtcItemsHint() {
	      this.cursorOnEtcItem = false;
	      setTimeout(() => {
	        const hoveredItems = document.querySelectorAll(':hover');
	        const lastHoveredItem = hoveredItems[hoveredItems.length - 1];
	        const popupContainer = this.etcItemHint.getPopupContainer();
	        const isHintPopupUnderCursor = popupContainer.contains(lastHoveredItem);
	        if (isHintPopupUnderCursor === false) {
	          this.destroyEtcItemsHint();
	          return;
	        }
	        main_core.bind(popupContainer, 'mouseleave', () => {
	          setTimeout(() => {
	            if (this.cursorOnEtcItem === false) {
	              this.destroyEtcItemsHint();
	            }
	          }, 100);
	        });
	      }, 100);
	    },
	    destroyEtcItemsHint() {
	      var _this$etcItemHint;
	      (_this$etcItemHint = this.etcItemHint) == null ? void 0 : _this$etcItemHint.destroy();
	      this.etcItemHint = null;
	    },
	    getEncodedString(str) {
	      return main_core.Text.encode(str);
	    }
	  },
	  watch: {
	    'selectedItems.length': function () {
	      this.updateSelectedItemsWithData();
	    }
	  },
	  mounted() {
	    this.updateSelectedItemsWithData();
	  },
	  unmounted() {
	    this.getUserSelectorDialog().destroy();
	  },
	  template: `
		<div class="ai__role-master_user-selector">
			<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
				<div
					type="text"
					class="ui-ctl-element"
				>
					<div ref="userSelector" class="ai__role-master_user-selector_inner">
						<ul class="ai__role-master-user-selector_users">
							<li
								v-for="(item, index) in selectedItemsWithData.slice(0, maxCirclesInInput)"
								:style="getSelectedItemStyle(item, index)"
								v-clickable-hint="getEncodedString(item.title)"
								class="ai__role-master-user-selector_user"
							>
								<span v-if="item.entityId === 'department'">
									{{ getDepartmentFirstLetter(item.title) }}
								</span>
							</li>
							<li
								v-if="etcSelectedItemsCount > 0"
								ref="etcItem"
								class="ai__role-master-user-selector_etc-item"
								@mouseenter="showEtcItemsHint"
								@mouseleave="closeEtcItemsHint"
								:style="{left: 24 * this.maxCirclesInInput - 8 + 'px'}"
							>
								<span class="ai__role-master-user-selector_etc-item-plus">+</span>
								<span>{{ etcSelectedItemsCircleNumber }}</span>
							</li>
						</ul>
						<button ref="addBtn" @click="showUserSelector" class="ai__role-master-user-selector_add">
							<span class="ai__role-master-user-selector_add-text">
								{{ $Bitrix.Loc.getMessage('ROLE_MASTER_USER_SELECTOR_ADD_BTN') }}
							</span>
						</button>
					</div>
				</div>
			</div>
			<div v-if="getUserSelectorDialog().getItems().length === 99999" class="ai__role-master_user-selector-loader">
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
					<div
						type="text"
						class="ui-ctl-element"
					>
						<div class="ai__role-master_user-selector_inner">
							<ul class="ai__role-master-user-selector_users">
								<li
									v-for="(item, index) in selectedItems.slice(0, maxCirclesInInput)"
									:style="getSelectedItemStyle(item, index)"
									class="ai__role-master-user-selector_user"
								>
								<span v-if="item.entityId === 'department'">
									{{ getDepartmentFirstLetter(item.title) }}
								</span>
								</li>
								<li
									v-if="etcSelectedItemsCount > 0"
									class="ai__role-master-user-selector_etc-item"
									:style="{left: 24 * this.maxCirclesInInput - 8 + 'px'}"
								>
									<span class="ai__role-master-user-selector_etc-item-plus">+</span>
									<span>{{ etcSelectedItemsCircleNumber }}</span>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	const RoleMasterMainStep = {
	  components: {
	    RoleMasterStep,
	    RoleMasterUserSelector,
	    RoleMasterAvatarUploader,
	    RoleMasterEditor
	  },
	  emits: ['uploadAvatarFile', 'removeAvatarFile', 'update:name', 'update:description', 'update:items-with-access'],
	  props: {
	    stepNumber: Number,
	    avatar: String,
	    name: String,
	    description: String,
	    itemsWithAccess: {
	      type: Array,
	      required: false,
	      default: () => {
	        return [];
	      }
	    },
	    undeselectedItemsWithAccess: {
	      type: Array,
	      required: false,
	      default: () => {
	        return [];
	      }
	    }
	  },
	  methods: {
	    handleAvatarFileUpload(file) {
	      this.$emit('uploadAvatarFile', file);
	    },
	    handleAvatarFileRemove() {
	      this.$emit('removeAvatarFile');
	    },
	    handleAvatarFileLoad(file) {
	      this.$emit('loadAvatarFile', file);
	    }
	  },
	  mounted() {
	    requestAnimationFrame(() => {
	      this.$refs.roleNameInput.focus();
	    });
	  },
	  template: `
		<RoleMasterStep
			:title="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_MAIN_STEP_TITLE')"
			:step-number="stepNumber"
		>
			<slot>
				<div class="ai__role-master_main-step">
					<div class="ui-form">
						<div class="ui-form-row-inline">
							<div class="ui-form-row">
								<div class="ui-form-label">
									<div class="ui-ctl-label-text">
										{{ $Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_AVATAR_AND_NAME_FIELD') }}
									</div>
								</div>
								<div class="ui-form-content">
									<div class="ui-ctl">
										<RoleMasterAvatarUploader
											:avatar-url="avatar"
											@upload-avatar-file="handleAvatarFileUpload"
											@remove-avatar-file="handleAvatarFileRemove"
											@load-avatar-file="handleAvatarFileLoad"
										/>
									</div>
									<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
										<input
											ref="roleNameInput"
											:value="name"
											@input="$emit('update:name', $event.target.value)"
											type="text"
											:minlength="1"
											:maxlength="70"
											class="ui-ctl-element"
											:placeholder="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_AVATAR_AND_NAME_FIELD_PLACEHOLDER')"
										/>
									</div>
								</div>
							</div>
						</div>
						<div class="ui-form-row">
							<div class="ui-form-label">
								<div class="ui-ctl-label-text">
									{{ $Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_USERS_WITH_ACCESS_FIELD') }}
								</div>
							</div>
							<div class="ui-form-content">
								<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
									<RoleMasterUserSelector
										:selected-items="itemsWithAccess"
										:undeselected-items="undeselectedItemsWithAccess"
										@update:selected-items="items => $emit('update:items-with-access', items)"
									/>
								</div>
							</div>
						</div>
						<div class="ui-form-row --with-textarea">
							<div class="ui-form-label">
								<div class="ui-ctl-label-text">
									{{ $Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_DESCRIPTION_FIELD') }}
								</div>
							</div>
							<div class="ui-form-content">
								<div class="ui-ctl ui-ctl-textarea ui-ctl-w100 ui-ctl-no-resize">
									<RoleMasterEditor
										:min-text-length="0"
										:max-text-length="150"
										:text="description"
										:placeholder="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_DESCRIPTION_FIELD_PLACEHOLDER')"
										@update:text="$emit('update:description', $event)"
									/>
								</div>
							</div>
						</div>
					</div>
				</div>
			</slot>
		</RoleMasterStep>
	`
	};

	const RoleMasterSavingStatus = {
	  props: {
	    status: String
	  },
	  emits: ['repeat-request', 'back-to-editor', 'close-master'],
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    RoleMasterBtn
	  },
	  computed: {
	    savingStatus() {
	      return RoleSavingStatus;
	    },
	    IconSet() {
	      return ui_iconSet_api_vue.Set;
	    },
	    ButtonColor() {
	      return ui_buttons.ButtonColor;
	    }
	  },
	  methods: {
	    showLoader() {
	      var _getComputedStyle$get;
	      const loader = new main_loader.Loader({
	        size: 80,
	        color: (_getComputedStyle$get = getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary')) != null ? _getComputedStyle$get : '#8e52ec',
	        strokeWidth: 4
	      });
	      loader.show(this.$refs.loaderContainer);
	    }
	  },
	  mounted() {
	    if (this.status === RoleSavingStatus.SAVING) {
	      this.showLoader();
	    }
	  },
	  updated() {
	    if (this.status === RoleSavingStatus.SAVING) {
	      this.showLoader();
	    }
	  },
	  template: `
		<div class="ai__role-master_saving-status">
			<div v-if="status === savingStatus.SAVING" class="ai__role-master_saving-loading">
				<div ref="loaderContainer" class="ai__role-master_saving-loader"></div>
				<div class="ai__role-master_saving-loading-text">
					{{ $Bitrix.Loc.getMessage('ROLE_MASTER_LOADER_TEXT') }}
				</div>
			</div>
			<div v-else-if="status === savingStatus.SAVING_SUCCESS" class="ai__role-master_saving-success">
				<div class="ai__role-master_saving-success-top">
					<div class="ai__role-master_saving-success-icon">
						<BIcon
							:name="IconSet.CHECK"
							:size="58"
						/>
					</div>
					<div class="ai__role-master_saving-success-text">
						{{ $Bitrix.Loc.getMessage('ROLE_MASTER_SAVING_DONE') }}
					</div>
				</div>
				<div class="ai__role-master_saving-success-bottom">
					<RoleMasterBtn
						@click="$emit('close-master')"
						:text="$Bitrix.Loc.getMessage('ROLE_MASTER_CLOSE_BTN')"
						:color="ButtonColor.LIGHT_BORDER"
					/>
				</div>
			</div>
			<div v-else-if="status === savingStatus.SAVING_ERROR" class="ai__role-master_saving-error">
				<div class="ai__role-master_saving-error-center">
					<div class="ai__role-master_saving-error-icon">
						<BIcon
							:name="IconSet.NOTE_CIRCLE"
							:size="66"
						/>
					</div>
					<div class="ai__role-master_saving-error-status-text">
						{{ $Bitrix.Loc.getMessage('ROLE_MASTER_SAVE_PROMPT_ON_ERROR_SHORT_TEXT') }}
					</div>
					<RoleMasterBtn
						@click="$emit('repeat-request')"
						:text="$Bitrix.Loc.getMessage('ROLE_MASTER_REPEAT_REQUEST_BTN')"
						:color="ButtonColor.LIGHT_BORDER"
					/>
				</div>
				<div class="ai__role-master_saving-error-bottom">
					<p class="ai__role-master_saving-error-description-text">
						{{ $Bitrix.Loc.getMessage('ROLE_MASTER_SAVE_PROMPT_ON_ERROR_TEXT') }}
					</p>
					<button
						@click="$emit('back-to-editor')"
						class="ai__role-master_back-to-editor-btn"
					>
						<BIcon
							:size="16"
							:name="IconSet.CHEVRON_LEFT"
						/>
						<span class="ai__role-master_back-to-editor-btn-text">
							{{ $Bitrix.Loc.getMessage('ROLE_MASTER_BACK_TO_EDITOR_BTN') }}
						</span>
					</button>
				</div>
			</div>
		</div>
	`
	};

	async function loadImageAsFile(imgPath) {
	  const fileName = imgPath.split('/').pop();
	  const response = await fetch(imgPath);
	  const mimeType = response.headers.get('Content-Type') || 'application/octet-stream';
	  const data = await response.blob();
	  const blob = new Blob([data], {
	    type: mimeType
	  });
	  return new File([blob], fileName, {
	    type: mimeType
	  });
	}

	const currentUserId = main_core.Extension.getSettings('ai.role-master').get('currentUserId');
	const RoleSavingStatus = Object.freeze({
	  NONE: 'none',
	  SAVING: 'saving',
	  SAVING_ERROR: 'saving-error',
	  SAVING_SUCCESS: 'saving-success'
	});
	const RoleMaster = {
	  name: 'RoleMaster',
	  components: {
	    RoleMasterTextStep,
	    RoleMasterMainStep,
	    RoleMasterSavingStatus,
	    RoleMasterBtn,
	    RoleMasterWarning,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    id: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    authorId: String,
	    text: String,
	    name: String,
	    avatar: String,
	    description: String,
	    avatarUrl: String,
	    itemsWithAccess: {
	      type: Array,
	      required: false,
	      default: () => []
	    }
	  },
	  data() {
	    return {
	      currentStepNumber: 1,
	      roleText: this.text,
	      roleName: this.name,
	      roleAvatar: this.avatar,
	      roleAvatarUrl: this.avatarUrl,
	      roleDescription: this.description,
	      roleItemsWithAccess: this.itemsWithAccess.length > 0 ? this.itemsWithAccess : [['user', currentUserId]],
	      uploadedAvatarFile: null,
	      roleSavingStatus: RoleSavingStatus.NONE
	    };
	  },
	  computed: {
	    isEditRoleMode() {
	      return this.id;
	    },
	    maxTextLength() {
	      return 2000;
	    },
	    minTextLength() {
	      return 1;
	    },
	    isEditMode() {
	      return false;
	    },
	    uploadedAvatarFilePreview() {
	      var _this$uploadedAvatarF, _this$uploadedAvatarF2;
	      return (_this$uploadedAvatarF = (_this$uploadedAvatarF2 = this.uploadedAvatarFile) == null ? void 0 : _this$uploadedAvatarF2.getPreviewUrl()) != null ? _this$uploadedAvatarF : null;
	    },
	    RoleSavingStatus() {
	      return RoleSavingStatus;
	    },
	    ButtonState() {
	      return ui_buttons.ButtonState;
	    },
	    isRoleTextValid() {
	      return this.roleText.length >= this.minTextLength && this.roleText.length <= this.maxTextLength;
	    },
	    isRoleNameValid() {
	      return this.roleName.length > 0 && this.roleName.length <= 70;
	    },
	    isRoleDescriptionValid() {
	      return this.roleDescription.length > 0 && this.roleDescription.length <= 150;
	    },
	    isRoleDataValid() {
	      return this.isRoleTextValid && this.isRoleNameValid && this.isRoleDescriptionValid && this.roleItemsWithAccess.length > 0;
	    },
	    nextStepBtnState() {
	      return this.isRoleTextValid ? '' : this.ButtonState.DISABLED;
	    },
	    saveBtnState() {
	      if (this.isRoleDataValid === false) {
	        return ui_buttons.ButtonState.DISABLED;
	      }
	      if (this.roleSavingStatus === RoleSavingStatus.SAVING) {
	        return ui_buttons.ButtonState.CLOCKING;
	      }
	      return '';
	    },
	    backIconProps() {
	      return {
	        size: 16,
	        icon: ui_iconSet_api_core.Actions.CHEVRON_LEFT
	      };
	    },
	    warningText() {
	      return this.isEditRoleMode && this.itemsWithAccess.length > 1 ? this.$Bitrix.Loc.getMessage('ROLE_MASTER_EDIT_WARNING_TEXT') : '';
	    },
	    undeselectedItemsWithAccess() {
	      if (this.isEditRoleMode) {
	        return [['user', this.authorId]];
	      }
	      return [['user', currentUserId]];
	    }
	  },
	  methods: {
	    async saveRole() {
	      const action = this.isEditRoleMode ? 'change' : 'create';
	      let isLoadFinished = false;
	      try {
	        setTimeout(() => {
	          if (isLoadFinished === false) {
	            this.roleSavingStatus = RoleSavingStatus.SAVING;
	          }
	        }, 100);
	        const roleAvatar = await this.getAvatarFile();
	        const data = {
	          roleText: this.roleText,
	          roleTitle: this.roleName,
	          roleAvatar,
	          roleAvatarUrl: this.roleAvatarUrl,
	          roleDescription: this.roleDescription,
	          accessCodes: this.roleItemsWithAccess
	        };
	        if (action === 'change') {
	          data.roleCode = this.id;
	        }
	        await main_core.ajax.runAction(`ai.shareRole.${action}`, {
	          data: main_core.Http.Data.convertObjectToFormData(data)
	        });
	        this.roleSavingStatus = RoleSavingStatus.SAVING_SUCCESS;
	        main_core_events.EventEmitter.emit('AI.RoleMasterApp:Save-success', data);
	      } catch (e) {
	        console.error(e);
	        this.roleSavingStatus = RoleSavingStatus.SAVING_ERROR;
	        main_core_events.EventEmitter.emit('AI.RoleMasterApp:Save-failed');
	      } finally {
	        isLoadFinished = true;
	      }
	    },
	    async getAvatarFile() {
	      if (!this.uploadedAvatarFile) {
	        const pathToDefaultAvatar = '/bitrix/js/ai/role-master/images/role-master-default-avatar.svg';
	        return loadImageAsFile(pathToDefaultAvatar);
	      }
	      if (!this.uploadedAvatarFile.getBinary()) {
	        return this.avatarUrl;
	      }
	      return this.uploadedAvatarFile.getBinary();
	    },
	    handleAvatarFileUpload(file) {
	      this.uploadedAvatarFile = file;
	    },
	    handleAvatarFileRemove() {
	      this.uploadedAvatarFile = null;
	      this.roleAvatar = null;
	    },
	    handleAvatarFileLoad(file) {
	      this.uploadedAvatarFile = ui_vue3.toRaw(file);
	    },
	    closeMaster() {
	      main_core_events.EventEmitter.emit('AI.RoleMasterApp:Close');
	    },
	    backToEditor() {
	      this.roleSavingStatus = RoleSavingStatus.NONE;
	    },
	    openHelpdeskSlider() {
	      const articleCode = '23184474';
	      const Helper = main_core.Reflection.getClass('top.BX.Helper');
	      if (Helper) {
	        Helper.show(`redirect=detail&code=${articleCode}`);
	      }
	    }
	  },
	  template: `
		<div>
			<header></header>
			<main>
				<RoleMasterTextStep
					v-if="currentStepNumber === 1"
					:step-number="1"
					:role-text="roleText"
					:warning-text="warningText"
					@update:role-text="roleText = $event"
					:max-text-length="maxTextLength"
					:min-text-length="minTextLength"
				/>
				<RoleMasterMainStep
					v-if="currentStepNumber === 2"
					:step-number="2"
					:name="roleName"
					:description="roleDescription"
					:avatar="uploadedAvatarFilePreview || roleAvatar"
					:items-with-access="roleItemsWithAccess"
					:undeselected-items-with-access="undeselectedItemsWithAccess"
					@upload-avatar-file="handleAvatarFileUpload"
					@remove-avatar-file="handleAvatarFileRemove"
					@load-avatar-file="handleAvatarFileLoad"
					@update:name="roleName = $event"
					@update:description="roleDescription = $event"
					@update:avatar="roleAvatar = $event"
					@update:items-with-access="roleItemsWithAccess = $event"
				/>
			</main>
			<footer class="ai__role-master-app_footer">
				<a
					v-if="currentStepNumber === 1"
					@click="openHelpdeskSlider"
					class="ai__role-master_about-link"
					href="#"
				>
					{{ $Bitrix.Loc.getMessage('ROLE_MASTER_ABOUT_ROLE_MASTER_LINK') }}
				</a>
				<button
					v-if="currentStepNumber > 1"
					@click="currentStepNumber -= 1"
					class="ai__role-master_back-btn"
				>
					<BIcon
						:size="backIconProps.size"
						:name="backIconProps.icon"
					/>
					{{ $Bitrix.Loc.getMessage('ROLE_MASTER_PREV_BUTTON') }}
				</button>
				<RoleMasterBtn
					v-if="currentStepNumber < 2"
					@click="currentStepNumber += 1"
					:state="nextStepBtnState"
					:text="$Bitrix.Loc.getMessage('ROLE_MASTER_NEXT_BUTTON')"
				/>
				<RoleMasterBtn
					v-else
					@click="saveRole"
					:state="saveBtnState"
					:text="$Bitrix.Loc.getMessage('ROLE_MASTER_SAVE_BUTTON')"
				/>
			</footer>

			<div v-if="roleSavingStatus !== RoleSavingStatus.NONE" class="ai__role-master_saving-status-container">
				<RoleMasterSavingStatus
					:status="roleSavingStatus"
					@close-master="closeMaster"
					@back-to-editor="backToEditor"
					@repeat-request="saveRole"
				/>
			</div>
		</div>
	`
	};

	let _ = t => t,
	  _t;
	const RoleMasterEvents = {
	  CLOSE: 'close',
	  SAVE_SUCCESS: 'save-success'
	};
	var _roleMasterOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("roleMasterOptions");
	var _app = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("app");
	class RoleMaster$1 extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _roleMasterOptions, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _app, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.RoleMaster');
	    babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions)[_roleMasterOptions] = options;
	  }
	  render() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9, _babelHelpers$classPr10, _babelHelpers$classPr11, _babelHelpers$classPr12;
	    main_core_events.EventEmitter.subscribe('AI.RoleMasterApp:Close', () => {
	      this.emit(RoleMasterEvents.CLOSE);
	    });
	    main_core_events.EventEmitter.subscribe('AI.RoleMasterApp:Save-success', event => {
	      this.emit(RoleMasterEvents.SAVE_SUCCESS, event.getData());
	    });
	    const appContainer = main_core.Tag.render(_t || (_t = _`<div class="ai__role-master-app-container"></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app] = ui_vue3.BitrixVue.createApp(RoleMaster, {
	      id: (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions)[_roleMasterOptions].id) != null ? _babelHelpers$classPr : '',
	      authorId: (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions)[_roleMasterOptions].authorId) != null ? _babelHelpers$classPr2 : '',
	      name: (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions)[_roleMasterOptions].name) != null ? _babelHelpers$classPr3 : '',
	      text: (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions)[_roleMasterOptions].text) != null ? _babelHelpers$classPr4 : '',
	      description: (_babelHelpers$classPr5 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions)[_roleMasterOptions]) == null ? void 0 : _babelHelpers$classPr6.description) != null ? _babelHelpers$classPr5 : '',
	      avatar: (_babelHelpers$classPr7 = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions)[_roleMasterOptions]) == null ? void 0 : _babelHelpers$classPr8.avatar) != null ? _babelHelpers$classPr7 : '',
	      avatarUrl: (_babelHelpers$classPr9 = (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions)[_roleMasterOptions]) == null ? void 0 : _babelHelpers$classPr10.avatarUrl) != null ? _babelHelpers$classPr9 : '',
	      itemsWithAccess: (_babelHelpers$classPr11 = (_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions)[_roleMasterOptions]) == null ? void 0 : _babelHelpers$classPr12.itemsWithAccess) != null ? _babelHelpers$classPr11 : []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].mount(appContainer);
	    return appContainer;
	  }
	  destroy() {
	    var _babelHelpers$classPr13;
	    (_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _app)[_app]) == null ? void 0 : _babelHelpers$classPr13.unmount();
	  }
	  static validateOptions(options) {
	    if (!options) {
	      return;
	    }
	    if (options && main_core.Type.isObject(options) === false) {
	      throw new Error('AI.RoleMaster: options must be the object');
	    }
	    if (options.id && main_core.Type.isStringFilled(options.id) === false) {
	      throw new Error('AI.RoleMaster: id option must be the filled string');
	    }
	    if (options.text && main_core.Type.isStringFilled(options.text) === false) {
	      throw new Error('AI.RoleMaster: roleText option must be the filled string');
	    }
	    if (options.avatar && main_core.Type.isStringFilled(options.avatar) === false) {
	      throw new Error('AI.RoleMaster: avatar option must be the url string');
	    }
	    if (options.avatarUrl && main_core.Type.isStringFilled(options.avatar) === false) {
	      throw new Error('AI.RoleMaster: avatar option must be the url string');
	    }
	    if (options.description && main_core.Type.isStringFilled(options.description) === false) {
	      throw new Error('AI.RoleMaster: description option must be the filled string');
	    }
	    if (options.name && main_core.Type.isStringFilled(options.name) === false) {
	      throw new Error('AI.RoleMaster: name option must be the filled string');
	    }
	    if (options.itemsWithAccess && main_core.Type.isArrayFilled(options.itemsWithAccess) === false) {
	      throw new Error('AI.RoleMaster: users option must be the array.');
	    }
	  }
	}

	let _$1 = t => t,
	  _t$1;
	const RoleMasterPopupEvents = Object.freeze({
	  OPEN: 'open',
	  CANCEL: 'cancel',
	  SAVE_SUCCESS: 'role-save-success',
	  SAVE_FAILED: 'role-save-success'
	});
	var _roleMasterOptions$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("roleMasterOptions");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _roleMaster = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("roleMaster");
	var _cancelledRoleSavingHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelledRoleSavingHandler");
	var _initPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _cancelRoleSavingAnalyticEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelRoleSavingAnalyticEvent");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	var _validateOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateOptions");
	class RoleMasterPopup extends main_core.Event.EventEmitter {
	  constructor(_options = {}) {
	    super(_options);
	    Object.defineProperty(this, _validateOptions, {
	      value: _validateOptions2
	    });
	    Object.defineProperty(this, _renderPopupContent, {
	      value: _renderPopupContent2
	    });
	    Object.defineProperty(this, _cancelRoleSavingAnalyticEvent, {
	      value: _cancelRoleSavingAnalyticEvent2
	    });
	    Object.defineProperty(this, _initPopup, {
	      value: _initPopup2
	    });
	    Object.defineProperty(this, _roleMasterOptions$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _roleMaster, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cancelledRoleSavingHandler, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.RoleMaster');
	    babelHelpers.classPrivateFieldLooseBase(this, _validateOptions)[_validateOptions](_options);
	    babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions$1)[_roleMasterOptions$1] = _options.roleMaster || {};
	  }
	  async sendAnalytics(event) {
	    try {
	      const {
	        sendData
	      } = await main_core.Runtime.loadExtension('ui.analytics');
	      const sendDataOptions = {
	        event,
	        status: 'success',
	        tool: 'ai',
	        category: 'roles_saving'
	      };
	      sendData(sendDataOptions);
	    } catch (e) {
	      console.error('AI: RolesDialog: Can\'t send analytics', e);
	    }
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = babelHelpers.classPrivateFieldLooseBase(this, _initPopup)[_initPopup]();
	    }
	    this.sendAnalytics(RoleMasterPopupEvents.OPEN);
	    babelHelpers.classPrivateFieldLooseBase(this, _cancelledRoleSavingHandler)[_cancelledRoleSavingHandler] = babelHelpers.classPrivateFieldLooseBase(this, _cancelRoleSavingAnalyticEvent)[_cancelRoleSavingAnalyticEvent].bind(this);
	    main_core.bind(babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].closeIcon, 'click', babelHelpers.classPrivateFieldLooseBase(this, _cancelledRoleSavingHandler)[_cancelledRoleSavingHandler]);
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  hide() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.close();
	  }
	}
	function _initPopup2() {
	  return new main_popup.Popup({
	    id: 'role-master-popup',
	    width: 360,
	    minHeight: 592,
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent](),
	    cacheable: false,
	    closeIcon: true,
	    overlay: true,
	    padding: 17,
	    borderRadius: '12px',
	    events: {
	      onPopupClose: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	        babelHelpers.classPrivateFieldLooseBase(this, _roleMaster)[_roleMaster].unsubscribeAll(RoleMasterEvents.CLOSE);
	        babelHelpers.classPrivateFieldLooseBase(this, _roleMaster)[_roleMaster].unsubscribeAll(RoleMasterEvents.SAVE_SUCCESS);
	        babelHelpers.classPrivateFieldLooseBase(this, _roleMaster)[_roleMaster].destroy();
	      }
	    }
	  });
	}
	function _cancelRoleSavingAnalyticEvent2() {
	  this.sendAnalytics(RoleMasterPopupEvents.CANCEL);
	}
	function _renderPopupContent2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _roleMaster)[_roleMaster] = new RoleMaster$1({
	    ...babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions$1)[_roleMasterOptions$1]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _roleMaster)[_roleMaster].subscribeOnce(RoleMasterEvents.CLOSE, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].close();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _roleMaster)[_roleMaster].subscribeOnce(RoleMasterEvents.SAVE_SUCCESS, event => {
	    this.emit(RoleMasterPopupEvents.SAVE_SUCCESS, event.getData());
	  });
	  const headerText = babelHelpers.classPrivateFieldLooseBase(this, _roleMasterOptions$1)[_roleMasterOptions$1].id === undefined ? main_core.Loc.getMessage('ROLE_MASTER_POPUP_TITLE') : main_core.Loc.getMessage('ROLE_MASTER_POPUP_TITLE_EDIT_MODE');
	  return main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="ai__role-master-popup-content">
				<header class="ai__role-master-popup-content-header">
					${0}
				</header>
				<div class="ai__role-master-popup-content-main">
					${0}
				</div>
			</div>
		`), headerText, babelHelpers.classPrivateFieldLooseBase(this, _roleMaster)[_roleMaster].render());
	}
	function _validateOptions2(options) {
	  RoleMaster$1.validateOptions(options.roleMaster);
	}

	exports.RoleMaster = RoleMaster$1;
	exports.RoleMasterPopup = RoleMasterPopup;
	exports.RoleMasterPopupEvents = RoleMasterPopupEvents;

}((this.BX.AI = this.BX.AI || {}),BX.Vue3.Components,BX.UI,BX.UI,BX.UI.Uploader,BX,BX.Event,BX.UI.EntitySelector,BX,BX,BX.UI,BX.UI.IconSet,BX.UI.IconSet,BX,BX.Vue3,BX,BX.Main));
//# sourceMappingURL=role-master.bundle.js.map
