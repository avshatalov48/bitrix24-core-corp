/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,ui_vue3,ui_vue3_mixins_locMixin,tasks_v2_component_elements_fieldList,tasks_v2_component_dropZone,tasks_v2_component_fields_description,tasks_v2_component_fields_creator,tasks_v2_component_fields_deadline,tasks_v2_component_fields_status,tasks_v2_component_fields_files,tasks_v2_component_fields_checkList,tasks_v2_component_fields_group,tasks_v2_component_fields_flow,tasks_v2_component_fields_accomplices,tasks_v2_component_fields_auditors,tasks_v2_component_fields_tags,tasks_v2_component_fields_crm,tasks_v2_component_fields_datePlan,tasks_v2_component_fields_subTasks,tasks_v2_component_fields_parentTask,tasks_v2_component_fields_relatedTasks,tasks_v2_provider_service_fileService,tasks_v2_provider_service_checkListService,tasks_v2_component_fields_title,tasks_v2_component_fields_importance,ui_iconSet_main,tasks_v2_application_taskCard,main_core_events,im_public,im_v2_application_integration_task,ui_system_chip_vue,tasks_v2_lib_heightTransition,tasks_v2_core,tasks_v2_component_addTaskButton,ui_vue3_components_button,main_core,ui_vue3_vuex,ui_vue3_components_menu,ui_iconSet_api_vue,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_const,tasks_v2_component_fields_responsible,tasks_v2_lib_entitySelectorDialog,tasks_v2_provider_service_taskService,tasks_v2_provider_service_statusService) {
	'use strict';

	let _ = t => t,
	  _t;
	const MENU_SECTION_PERSONAL = 'MENU_SECTION_PERSONAL';
	const MENU_SECTION_TASKS = 'MENU_SECTION_TASKS';
	const MENU_SECTION_COPY = 'MENU_SECTION_COPY';
	const MENU_SECTION_LINKS = 'MENU_SECTION_LINKS';

	// @vue/component
	const BurgerMenu = {
	  name: 'TaskFullCardBurgerMenu',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    BMenu: ui_vue3_components_menu.BMenu
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline,
	      userRights: tasks_v2_core.Core.getParams().rights
	    };
	  },
	  data() {
	    return {
	      isMenuShown: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      currentUserId: `${tasks_v2_const.Model.Interface}/currentUserId`
	    }),
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    menuOptions() {
	      return () => ({
	        id: 'tasks-full-card-header-burger-menu',
	        bindElement: this.$refs.button.$el,
	        sections: [{
	          code: MENU_SECTION_PERSONAL
	        }, {
	          code: MENU_SECTION_TASKS
	        }, {
	          code: MENU_SECTION_COPY
	        }, {
	          code: MENU_SECTION_LINKS
	        }],
	        items: this.menuItems
	      });
	    },
	    menuItems() {
	      const actionsMenuItems = [];
	      if (this.task.rights.favorite) {
	        actionsMenuItems.push(this.getToggleFavoriteItem());
	      }
	      if (this.task.rights.watch) {
	        actionsMenuItems.push(this.getBecomeAuditorItem());
	      }
	      if (this.task.rights.mute) {
	        actionsMenuItems.push(this.getToggleMuteItem());
	      }
	      if (this.userRights.tasks.create) {
	        actionsMenuItems.push(this.getCreateNewTaskItem());
	      }
	      if (this.task.rights.createSubtask) {
	        actionsMenuItems.push(this.getCreateSubtaskItem());
	      }
	      if (this.task.rights.copy) {
	        actionsMenuItems.push(this.getCreateDuplicateTaskItem());
	      }
	      if (this.userRights.tasks.createFromTemplate) {
	        actionsMenuItems.push(this.getCreateNewTaskWithTemplateItem());
	      }
	      if (this.task.rights.saveAsTemplate) {
	        actionsMenuItems.push(this.getCreateTemplateFromTaskItem());
	      }
	      actionsMenuItems.push(this.getCopyTaskIdItem(), this.getGoToBitrixMarketItem());
	      if (this.userRights.tasks.robot) {
	        actionsMenuItems.push(this.getGoToRobotsItem());
	      }
	      return actionsMenuItems;
	    }
	  },
	  methods: {
	    getNotificationLayoutWithTitle({
	      title,
	      text
	    }) {
	      return main_core.Tag.render(_t || (_t = _`
				<div>
					<p style="margin: 0">${0}</p>
					<p style="margin: 5px 0 0 0; font-size: 12px">${0}</p>
				</div>
			`), title, text);
	    },
	    // handlers
	    handleClickOpener() {
	      this.isMenuShown = true;
	    },
	    // handlers end

	    // button getters
	    getToggleFavoriteItem() {
	      const isTurningOn = !this.task.inFavorite.includes(this.currentUserId);
	      return {
	        sectionCode: MENU_SECTION_PERSONAL,
	        title: isTurningOn ? this.loc('TASKS_V2_TASK_FULL_CARD_FAVOR_ACTION') : this.loc('TASKS_V2_TASK_FULL_CARD_UNFAVOR_ACTION'),
	        icon: isTurningOn ? ui_iconSet_api_vue.Outline.FAVORITE : ui_iconSet_api_vue.Outline.NON_FAVORITE,
	        onClick: async () => {
	          if (isTurningOn) {
	            const isSuccess = await tasks_v2_provider_service_taskService.taskService.addFavorite(this.task, this.currentUserId);
	            BX.UI.Notification.Center.notify({
	              content: isSuccess ? this.loc('TASKS_V2_TASK_FULL_CARD_FAVOR_NOTIF_SUCC') : this.loc('TASKS_V2_TASK_FULL_CARD_FAVOR_NOTIF_FAIL')
	            });
	          } else {
	            const isSuccess = await tasks_v2_provider_service_taskService.taskService.removeFavorite(this.task, this.currentUserId);
	            BX.UI.Notification.Center.notify({
	              content: isSuccess ? this.loc('TASKS_V2_TASK_FULL_CARD_UNFAVOR_NOTIF_SUCC') : this.loc('TASKS_V2_TASK_FULL_CARD_UNFAVOR_NOTIF_FAIL')
	            });
	          }
	        }
	      };
	    },
	    getBecomeAuditorItem() {
	      const auditorsIdsCurrent = this.task.auditorsIds;
	      const isBecomingAuditor = !auditorsIdsCurrent.includes(this.currentUserId);
	      return {
	        sectionCode: MENU_SECTION_PERSONAL,
	        title: isBecomingAuditor ? this.loc('TASKS_V2_TASK_FULL_CARD_BECOME_AUDITOR_ACTION') : this.loc('TASKS_V2_TASK_FULL_CARD_STOP_BEING_AUDITOR_ACTION'),
	        icon: isBecomingAuditor ? ui_iconSet_api_vue.Outline.OBSERVER : ui_iconSet_api_vue.Outline.CROSSED_EYE,
	        onClick: async () => {
	          if (isBecomingAuditor) {
	            const isSuccess = await tasks_v2_provider_service_taskService.taskService.setAuditors(this.task.id, [...auditorsIdsCurrent, this.currentUserId]);
	            BX.UI.Notification.Center.notify({
	              content: isSuccess ? this.loc('TASKS_V2_TASK_FULL_CARD_BECOME_AUDITOR_NOTIF_SUCC') : this.loc('TASKS_V2_TASK_FULL_CARD_BECOME_AUDITOR_NOTIF_FAIL')
	            });
	          } else {
	            const isSuccess = await tasks_v2_provider_service_taskService.taskService.setAuditors(this.task.id, auditorsIdsCurrent.filter(auditorId => auditorId !== this.currentUserId));
	            BX.UI.Notification.Center.notify({
	              content: isSuccess ? this.loc('TASKS_V2_TASK_FULL_CARD_STOP_BEING_AUDITOR_NOTIF_SUCC') : this.loc('TASKS_V2_TASK_FULL_CARD_STOP_BEING_AUDITOR_NOTIF_FAIL')
	            });
	          }
	        }
	      };
	    },
	    getToggleMuteItem() {
	      const isMuting = !this.task.inMute.includes(this.currentUserId);
	      return {
	        sectionCode: MENU_SECTION_PERSONAL,
	        title: isMuting ? this.loc('TASKS_V2_TASK_FULL_CARD_MUTE_ACTION') : this.loc('TASKS_V2_TASK_FULL_CARD_UNMUTE_ACTION'),
	        icon: isMuting ? ui_iconSet_api_vue.Outline.SOUND_OFF : ui_iconSet_api_vue.Outline.SOUND_ON,
	        onClick: async () => {
	          if (isMuting) {
	            const isSuccess = await tasks_v2_provider_service_taskService.taskService.muteTask(this.task, this.currentUserId);
	            BX.UI.Notification.Center.notify({
	              content: isSuccess ? this.getNotificationLayoutWithTitle({
	                title: this.loc('TASKS_V2_TASK_FULL_CARD_MUTE_NOTIF_SUCC_TITLE'),
	                text: this.loc('TASKS_V2_TASK_FULL_CARD_MUTE_NOTIF_SUCC_DESCR')
	              }) : this.loc('TASKS_V2_TASK_FULL_CARD_MUTE_NOTIF_FAIL')
	            });
	          } else {
	            const isSuccess = await tasks_v2_provider_service_taskService.taskService.unmuteTask(this.task, this.currentUserId);
	            BX.UI.Notification.Center.notify({
	              content: isSuccess ? this.getNotificationLayoutWithTitle({
	                title: this.loc('TASKS_V2_TASK_FULL_CARD_UNMUTE_NOTIF_SUCC_TITLE'),
	                text: this.loc('TASKS_V2_TASK_FULL_CARD_UNMUTE_NOTIF_SUCC_DESCR')
	              }) : this.loc('TASKS_V2_TASK_FULL_CARD_UNMUTE_NOTIF_FAIL')
	            });
	          }
	        }
	      };
	    },
	    getCreateNewTaskItem() {
	      return {
	        sectionCode: MENU_SECTION_TASKS,
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_STANDALONE_TASK'),
	        icon: ui_iconSet_api_vue.Outline.TASK,
	        onClick: async () => {
	          tasks_v2_application_taskCard.TaskCard.showCompactCard({});
	        }
	      };
	    },
	    getCreateSubtaskItem() {
	      return {
	        sectionCode: MENU_SECTION_TASKS,
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_SUBTASK'),
	        // icon: Outline.SUBTASK,
	        icon: ui_iconSet_api_vue.Outline.RELATED_TASKS,
	        onClick: async () => {
	          tasks_v2_application_taskCard.TaskCard.showCompactCard({
	            parentId: this.taskId
	          });
	        }
	      };
	    },
	    getCreateDuplicateTaskItem() {
	      return {
	        sectionCode: MENU_SECTION_TASKS,
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_DUPLICATE_TASK'),
	        icon: ui_iconSet_api_vue.Outline.DUPLICATE,
	        onClick: async () => {
	          tasks_v2_application_taskCard.TaskCard.showCompactCard({});
	        }
	      };
	    },
	    getCreateNewTaskWithTemplateItem() {
	      return {
	        sectionCode: MENU_SECTION_TASKS,
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_STANDALONE_TASK_WITH_TEMPLATE'),
	        icon: ui_iconSet_api_vue.Outline.CHEVRON_RIGHT_L,
	        onClick: async () => {
	          tasks_v2_application_taskCard.TaskCard.showCompactCard({});
	        }
	      };
	    },
	    getCreateTemplateFromTaskItem() {
	      return {
	        sectionCode: MENU_SECTION_TASKS,
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_CREATE_TEMPLATE_FROM_TASK'),
	        icon: ui_iconSet_api_vue.Outline.TEMPLATE_TASK,
	        onClick: async () => {
	          tasks_v2_application_taskCard.TaskCard.showCompactCard({});
	        }
	      };
	    },
	    getCopyTaskIdItem() {
	      return {
	        sectionCode: MENU_SECTION_COPY,
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_COPY_TASK_ID_ACTION'),
	        icon: ui_iconSet_api_vue.Outline.COPY,
	        onClick: async () => {
	          const isCopyingSuccess = BX.clipboard.copy(this.taskId);
	          if (isCopyingSuccess) {
	            BX.UI.Notification.Center.notify({
	              content: this.loc('TASKS_V2_TASK_FULL_CARD_COPY_TASK_ID_NOTIF')
	            });
	          }
	        }
	      };
	    },
	    getGoToBitrixMarketItem() {
	      return {
	        sectionCode: MENU_SECTION_LINKS,
	        uiButtonOptions: {
	          icon: ui_iconSet_api_vue.Outline.MARKET,
	          text: this.loc('TASKS_V2_TASK_FULL_CARD_GO_TO_BITRIX_MARKET'),
	          size: ui_vue3_components_button.ButtonSize.SMALL,
	          useAirDesign: true,
	          style: ui_vue3_components_button.AirButtonStyle.OUTLINE,
	          wide: true,
	          disabled: false,
	          onclick: async () => {
	            BX.rest.Marketplace.open({
	              PLACEMENT: 'TASK_LIST_CONTEXT_MENU'
	            });
	          }
	        }
	      };
	    },
	    getGoToRobotsItem() {
	      return {
	        sectionCode: MENU_SECTION_LINKS,
	        uiButtonOptions: {
	          icon: ui_iconSet_api_vue.Outline.ROBOT,
	          text: this.loc('TASKS_V2_TASK_FULL_CARD_GO_TO_ROBOTS'),
	          size: ui_vue3_components_button.ButtonSize.SMALL,
	          useAirDesign: true,
	          style: ui_vue3_components_button.AirButtonStyle.OUTLINE,
	          wide: true,
	          disabled: false,
	          onclick: () => {
	            BX.SidePanel.Instance.open(`/bitrix/components/bitrix/tasks.automation/slider.php?site_id=${main_core.Loc.getMessage('SITE_ID')}&project_id=${this.task.groupId}&task_id=${this.taskId}`, {
	              cacheable: false,
	              customLeftBoundary: 0,
	              loader: 'bizproc:automation-loader'
	            });
	          }
	        }
	      };
	    }
	    // button getters end
	  },

	  template: `
		<div
			v-if="menuItems"
			class="tasks-full-card-header-burger-container"
			@click="handleClickOpener"
		>
			<BIcon
				class="tasks-full-card-header-burger"
				ref="button"
				:name="Outline.MORE_L"
			/>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions()" @close="isMenuShown = false"/>
	`
	};

	// @vue/component
	const TaskHeader = {
	  name: 'TaskFullCardHeader',
	  components: {
	    TitleField: tasks_v2_component_fields_title.Title,
	    Importance: tasks_v2_component_fields_importance.Importance,
	    BurgerMenu
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  computed: {
	    isEdit() {
	      return main_core.Type.isNumber(this.taskId) && this.taskId > 0;
	    }
	  },
	  template: `
		<div class="tasks-full-card-header">
			<TitleField :taskId />
			<Importance :taskId />
			<BurgerMenu v-if="isEdit" :taskId/>
		</div>
	`
	};

	// @vue/component
	const Chat = {
	  name: 'TaskFullCardChat',
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    }
	  },
	  watch: {
	    taskId() {
	      void this.openChat();
	    }
	  },
	  mounted() {
	    void this.openChat();
	  },
	  methods: {
	    async openChat() {
	      var _this$app, _this$app2;
	      (_this$app = this.app) == null ? void 0 : _this$app.bitrixVue.unmount();
	      (_this$app2 = this.app) != null ? _this$app2 : this.app = await im_public.Messenger.initApplication('task');
	      if (this.isEdit) {
	        void this.app.mount({
	          rootContainer: this.$el,
	          chatId: this.task.chatId,
	          taskId: this.taskId,
	          type: tasks_v2_core.Core.getParams().chatType
	        });
	      } else {
	        await this.app.mountPlaceholder({
	          rootContainer: this.$el,
	          taskId: `'${this.taskId}'`
	        });
	        main_core_events.EventEmitter.emit('tasks:card:onMembersCountChange', {
	          taskId: this.taskId,
	          userCounter: 1
	        });
	      }
	    }
	  },
	  template: `
		<div class="tasks-full-card-chat"></div>
	`
	};

	// @vue/component
	const Chips = {
	  components: {
	    Chip: ui_system_chip_vue.Chip
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    chips: {
	      type: Array,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline,
	      ChipDesign: ui_system_chip_vue.ChipDesign
	    };
	  },
	  data() {
	    return {
	      chipsCollapsed: true
	    };
	  },
	  mounted() {
	    tasks_v2_lib_heightTransition.heightTransition.animate(this.$el);
	  },
	  updated() {
	    tasks_v2_lib_heightTransition.heightTransition.animate(this.$el);
	  },
	  template: `
		<div class="tasks-full-card-chips">
			<template v-for="(chip, index) of chips" :key="index">
				<component
					v-if="!chip.collapsed || !chipsCollapsed"
					:is="chip.component ?? chip"
					v-bind="{ taskId }"
					v-on="chip.events ?? {}"
				/>
			</template>
			<Chip
				:design="ChipDesign.ShadowNoAccent"
				:icon="chipsCollapsed ? Outline.APPS : Outline.CHEVRON_TOP_L"
				:text="chipsCollapsed ? loc('TASKS_V2_TASK_FULL_CARD_MORE') : ''"
				@click="chipsCollapsed = !chipsCollapsed"
			/>
		</div>
	`
	};

	// @vue/component
	const FooterCreate = {
	  components: {
	    UiButton: ui_vue3_components_button.Button,
	    AddTaskButton: tasks_v2_component_addTaskButton.AddTaskButton
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['addTask'],
	  setup() {
	    return {
	      AirButtonStyle: ui_vue3_components_button.AirButtonStyle,
	      ButtonSize: ui_vue3_components_button.ButtonSize
	    };
	  },
	  computed: {
	    isTemplateEnabled() {
	      return tasks_v2_core.Core.getParams().features.isTemplateEnabled;
	    }
	  },
	  methods: {
	    close() {
	      main_core.Event.EventEmitter.emit(tasks_v2_const.EventName.CloseFullCard, {
	        taskId: this.taskId
	      });
	    }
	  },
	  template: `
		<div class="tasks-full-card-footer-create">
			<div class="tasks-full-card-footer-main-buttons">
				<AddTaskButton :taskId="taskId" @addTask="$emit('addTask')"/>
				<UiButton
					:text="loc('TASKS_V2_TASK_FULL_CARD_CANCEL')"
					:size="ButtonSize.LARGE"
					:style="AirButtonStyle.PLAIN"
					data-task-cancel-button
					@click="close"
				/>
			</div>
			<UiButton
				v-if="isTemplateEnabled"
				class="tasks-full-card-footer-template-button"
				:text="loc('TASKS_V2_TASK_FULL_CARD_TEMPLATES')"
				:size="ButtonSize.SMALL"
				:style="AirButtonStyle.PLAIN_NO_ACCENT"
				:dropdown="true"
			/>
		</div>
	`
	};

	// @vue/component
	const More = {
	  name: 'TaskFullCardMoreActionsStatus',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    BMenu: ui_vue3_components_menu.BMenu
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  data() {
	    return {
	      isMenuShown: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      currentUserId: `${tasks_v2_const.Model.Interface}/currentUserId`
	    }),
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    preselected() {
	      return [['user', this.task.responsibleId || this.currentUserId]];
	    },
	    isCreator() {
	      return this.currentUserId === this.task.creatorId;
	    },
	    isResponsible() {
	      return this.currentUserId === this.task.responsibleId;
	    },
	    menuOptions() {
	      return () => ({
	        id: 'tasks-full-card-footer-more-menu',
	        bindElement: this.$refs.button.$el,
	        items: this.menuItems
	      });
	    },
	    menuItems() {
	      const statuses = {
	        [tasks_v2_const.TaskStatus.Pending]: {
	          [this.isResponsible]: [this.getCompleteItem(), this.getDefferItem(), this.getDelegateItem()],
	          [this.isCreator]: [this.getDefferItem(), this.getDelegateItem(), this.getDeleteItem()],
	          [this.isResponsible && this.isCreator]: [this.getCompleteItem(), this.getDefferItem(), this.getDelegateItem(), this.getDeleteItem()]
	        },
	        [tasks_v2_const.TaskStatus.InProgress]: {
	          [this.isResponsible]: [this.getPauseItem(), this.getDefferItem(), this.getDelegateItem()],
	          [this.isCreator]: [this.getDefferItem(), this.getDelegateItem(), this.getDeleteItem()],
	          [this.isResponsible && this.isCreator]: [this.getPauseItem(), this.getDefferItem(), this.getDelegateItem(), this.getDeleteItem()]
	        },
	        [tasks_v2_const.TaskStatus.Deferred]: {
	          [this.isResponsible]: [this.getCompleteItem(), this.getDelegateItem()],
	          [this.isCreator]: [this.getCompleteItem(), this.getDelegateItem(), this.getDeleteItem()]
	        },
	        [tasks_v2_const.TaskStatus.SupposedlyCompleted]: {
	          [this.isResponsible]: [this.getFixItem(), this.getDelegateItem()],
	          [this.isCreator]: [this.getDisapproveItem(), this.getDelegateItem(), this.getDeleteItem()],
	          [this.isResponsible && this.isCreator]: [this.getCompleteItem(), this.getDelegateItem(), this.getDeleteItem()]
	        },
	        [tasks_v2_const.TaskStatus.Completed]: {
	          [this.isCreator]: [this.getRenewItem(), this.getDeleteItem()]
	        }
	      };
	      return statuses[this.task.status].true;
	    }
	  },
	  methods: {
	    handleClick() {
	      this.isMenuShown = true;
	    },
	    getDefferItem() {
	      if (!this.task.rights.defer) {
	        return null;
	      }
	      return {
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_DEFER'),
	        icon: ui_iconSet_api_core.Outline.PAUSE_L,
	        onClick: () => tasks_v2_provider_service_statusService.statusService.defer(this.taskId)
	      };
	    },
	    getPauseItem() {
	      if (!this.task.rights.pause) {
	        return null;
	      }
	      return {
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_PAUSE'),
	        icon: ui_iconSet_api_core.Outline.STOP_L,
	        onClick: () => tasks_v2_provider_service_statusService.statusService.pause(this.taskId)
	      };
	    },
	    getRenewItem() {
	      if (!this.task.rights.renew) {
	        return null;
	      }
	      return {
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_RENEW'),
	        icon: ui_iconSet_api_core.Outline.UNDO,
	        onClick: () => tasks_v2_provider_service_statusService.statusService.renew(this.taskId)
	      };
	    },
	    getFixItem() {
	      if (!this.task.rights.renew) {
	        return null;
	      }
	      return {
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_FIX'),
	        icon: ui_iconSet_api_core.Outline.UNDO,
	        onClick: () => tasks_v2_provider_service_statusService.statusService.renew(this.taskId)
	      };
	    },
	    getDisapproveItem() {
	      if (!this.task.rights.disapprove) {
	        return null;
	      }
	      return {
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_DISAPPROVE'),
	        icon: ui_iconSet_api_core.Outline.UNDO,
	        onClick: () => tasks_v2_provider_service_statusService.statusService.disapprove(this.taskId)
	      };
	    },
	    getCompleteItem() {
	      if (!this.task.rights.complete) {
	        return null;
	      }
	      return {
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_COMPLETE'),
	        icon: ui_iconSet_api_core.Outline.SENDED,
	        onClick: () => tasks_v2_provider_service_statusService.statusService.complete(this.taskId)
	      };
	    },
	    getDelegateItem() {
	      if (!this.task.rights.delegate) {
	        return null;
	      }
	      return {
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_DELEGATE'),
	        icon: ui_iconSet_api_core.Outline.DELEGATE,
	        onClick: () => {
	          var _this$dialog;
	          const onItemChangeDebounced = main_core.Runtime.debounce(this.handleDelegateSelect, 10, this);
	          (_this$dialog = this.dialog) != null ? _this$dialog : this.dialog = new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	            ...tasks_v2_component_fields_responsible.responsibleMeta.dialogOptions(this.$options.name),
	            preselectedItems: this.preselected,
	            events: {
	              'Item:onSelect': onItemChangeDebounced,
	              'Item:onDeselect': onItemChangeDebounced
	            }
	          });
	          this.dialog.selectItemsByIds(this.preselected);
	          this.dialog.showTo(this.$refs.button.$el);
	        }
	      };
	    },
	    handleDelegateSelect() {
	      var _this$dialog$getSelec, _this$dialog$getSelec2;
	      const responsibleId = (_this$dialog$getSelec = (_this$dialog$getSelec2 = this.dialog.getSelectedItems()[0]) == null ? void 0 : _this$dialog$getSelec2.getId()) != null ? _this$dialog$getSelec : 0;
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        responsibleId
	      });
	    },
	    getDeleteItem() {
	      if (!this.task.rights.remove) {
	        return null;
	      }
	      return {
	        design: ui_vue3_components_menu.MenuItemDesign.Alert,
	        title: this.loc('TASKS_V2_TASK_FULL_CARD_DELETE'),
	        icon: ui_iconSet_api_core.Outline.TRASHCAN,
	        onClick: () => {
	          void tasks_v2_provider_service_taskService.taskService.delete(this.taskId);
	          main_core.Event.EventEmitter.emit(tasks_v2_const.EventName.CloseFullCard, {
	            taskId: this.taskId
	          });
	        }
	      };
	    }
	  },
	  template: `
		<BIcon
			v-if="menuItems"
			class="tasks-full-card-footer-more"
			:name="Outline.MORE_L"
			data-task-more-button
			ref="button"
			@click="handleClick"
		/>
		<BMenu v-if="isMenuShown" :options="menuOptions()" @close="isMenuShown = false"/>
	`
	};

	// @vue/component
	const FooterEdit = {
	  components: {
	    UiButton: ui_vue3_components_button.Button,
	    BIcon: ui_iconSet_api_vue.BIcon,
	    More
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      AirButtonStyle: ui_vue3_components_button.AirButtonStyle,
	      ButtonSize: ui_vue3_components_button.ButtonSize,
	      ButtonIcon: ui_vue3_components_button.ButtonIcon,
	      Outline: ui_iconSet_api_core.Outline,
	      TaskStatus: tasks_v2_const.TaskStatus
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      currentUserId: `${tasks_v2_const.Model.Interface}/currentUserId`
	    }),
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isCreator() {
	      return this.currentUserId === this.task.creatorId;
	    },
	    isResponsible() {
	      return this.currentUserId === this.task.responsibleId;
	    },
	    statusButton() {
	      const statuses = {
	        [tasks_v2_const.TaskStatus.Pending]: {
	          [this.isCreator]: this.getCompleteButton(),
	          [this.isResponsible]: this.getStartButton()
	        },
	        [tasks_v2_const.TaskStatus.InProgress]: {
	          [this.isResponsible]: this.getCompleteButton(),
	          [this.isCreator]: this.getCompleteButton()
	        },
	        [tasks_v2_const.TaskStatus.Deferred]: {
	          [this.isResponsible]: this.getRenewButton(),
	          [this.isCreator]: this.getRenewButton()
	        },
	        [tasks_v2_const.TaskStatus.SupposedlyCompleted]: {
	          [this.isResponsible]: this.getReviewButton(),
	          [this.isCreator]: this.getApproveButton()
	        },
	        [tasks_v2_const.TaskStatus.Completed]: {
	          [this.isResponsible && !this.isCreator]: this.getRenewButton(ui_vue3_components_button.AirButtonStyle.PLAIN)
	        }
	      };
	      return statuses[this.task.status].true;
	    }
	  },
	  methods: {
	    getStartButton() {
	      if (!this.task.rights.start) {
	        return null;
	      }
	      return {
	        text: this.loc('TASKS_V2_TASK_FULL_CARD_START'),
	        onClick: () => tasks_v2_provider_service_statusService.statusService.start(this.taskId)
	      };
	    },
	    getCompleteButton() {
	      if (!this.task.rights.complete) {
	        return null;
	      }
	      return {
	        text: this.loc('TASKS_V2_TASK_FULL_CARD_COMPLETE'),
	        onClick: () => tasks_v2_provider_service_statusService.statusService.complete(this.taskId)
	      };
	    },
	    getRenewButton(style) {
	      if (!this.task.rights.renew) {
	        return null;
	      }
	      return {
	        text: this.loc('TASKS_V2_TASK_FULL_CARD_RENEW'),
	        style,
	        onClick: () => tasks_v2_provider_service_statusService.statusService.renew(this.taskId)
	      };
	    },
	    getReviewButton() {
	      return {
	        text: this.loc('TASKS_V2_TASK_FULL_CARD_ON_REVIEW'),
	        disabled: true
	      };
	    },
	    getApproveButton() {
	      if (!this.task.rights.approve) {
	        return null;
	      }
	      return {
	        text: this.loc('TASKS_V2_TASK_FULL_CARD_APPROVE'),
	        onClick: () => tasks_v2_provider_service_statusService.statusService.complete(this.taskId)
	      };
	    }
	  },
	  template: `
		<div class="tasks-full-card-footer-edit">
			<UiButton
				v-if="statusButton"
				:text="statusButton.text"
				:size="ButtonSize.LARGE"
				:style="statusButton.style ?? AirButtonStyle.FILLED"
				:disabled="statusButton.disabled ?? false"
				data-task-status-button
				@click="statusButton.onClick"
			/>
			<More :taskId="taskId"/>
			<div class="tasks-full-card-footer-actions">
				<div class="tasks-full-card-footer-reaction">
					<BIcon :name="Outline.LIKE"/>
					<div class="tasks-full-card-footer-reaction-text">Нравится</div>
				</div>
				<div class="tasks-full-card-footer-views">
					<BIcon :name="Outline.OBSERVER"/>
					<div class="tasks-full-card-footer-views-text">1</div>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const App = {
	  name: 'TaskFullCard',
	  components: {
	    TaskHeader,
	    DescriptionField: tasks_v2_component_fields_description.DescriptionField,
	    DescriptionSheet: tasks_v2_component_fields_description.DescriptionSheet,
	    Files: tasks_v2_component_fields_files.Files,
	    FilesSheet: tasks_v2_component_fields_files.FilesSheet,
	    CheckList: tasks_v2_component_fields_checkList.CheckList,
	    DatePlan: tasks_v2_component_fields_datePlan.DatePlan,
	    DatePlanSheet: tasks_v2_component_fields_datePlan.DatePlanSheet,
	    SubTasks: tasks_v2_component_fields_subTasks.SubTasks,
	    ParentTask: tasks_v2_component_fields_parentTask.ParentTask,
	    RelatedTasks: tasks_v2_component_fields_relatedTasks.RelatedTasks,
	    FieldList: tasks_v2_component_elements_fieldList.FieldList,
	    Chat,
	    FooterCreate,
	    FooterEdit,
	    DropZone: tasks_v2_component_dropZone.DropZone,
	    Chips
	  },
	  provide() {
	    return {
	      analytics: this.analytics,
	      cardType: tasks_v2_const.CardType.Full
	    };
	  },
	  props: {
	    id: {
	      type: [Number, String],
	      default: () => main_core.Text.getRandom()
	    },
	    analytics: {
	      type: Object,
	      default: () => ({})
	    }
	  },
	  setup() {
	    return {
	      filesMeta: tasks_v2_component_fields_files.filesMeta,
	      datePlanMeta: tasks_v2_component_fields_datePlan.datePlanMeta,
	      checkListMeta: tasks_v2_component_fields_checkList.checkListMeta,
	      subTasksMeta: tasks_v2_component_fields_subTasks.subTasksMeta,
	      parentTaskMeta: tasks_v2_component_fields_parentTask.parentTaskMeta,
	      relatedTasksMeta: tasks_v2_component_fields_relatedTasks.relatedTasksMeta,
	      resizeObserver: null
	    };
	  },
	  data() {
	    return {
	      taskId: this.id,
	      isFilesSheetShown: false,
	      isDescriptionSheetShown: false,
	      isCheckListSheetShown: false,
	      isDatePlanSheetShown: false,
	      checkListId: 0,
	      files: tasks_v2_provider_service_fileService.fileService.get(this.id).getFiles()
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      currentUserId: `${tasks_v2_const.Model.Interface}/currentUserId`
	    }),
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    checkLists() {
	      var _this$task;
	      return (_this$task = this.task) != null && _this$task.checklist ? this.$store.getters[`${tasks_v2_const.Model.CheckList}/getByIds`](this.task.checklist) : [];
	    },
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    isPartiallyLoaded() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/isPartiallyLoaded`](this.taskId);
	    },
	    fields() {
	      const fields = [{
	        title: tasks_v2_component_fields_creator.creatorMeta.title,
	        component: tasks_v2_component_fields_creator.Creator,
	        props: {
	          context: this.$options.name,
	          selectorWithActionMenu: this.isEdit
	        }
	      }, {
	        title: tasks_v2_component_fields_responsible.responsibleMeta.title,
	        component: tasks_v2_component_fields_responsible.Responsible,
	        props: {
	          context: this.$options.name,
	          selectorWithActionMenu: this.isEdit
	        }
	      }, {
	        title: tasks_v2_component_fields_deadline.deadlineMeta.title,
	        component: tasks_v2_component_fields_deadline.Deadline
	      }, {
	        title: tasks_v2_component_fields_status.statusMeta.title,
	        component: tasks_v2_component_fields_status.Status,
	        withSeparator: true
	      }, {
	        chip: tasks_v2_component_fields_files.FilesChip
	      }, {
	        chip: {
	          component: tasks_v2_component_fields_checkList.CheckListChip,
	          events: {
	            showCheckList: this.openCheckList
	          }
	        }
	      }, {
	        title: tasks_v2_component_fields_group.groupMeta.getTitle(this.task.groupId),
	        component: tasks_v2_component_fields_group.Group,
	        chip: tasks_v2_component_fields_group.GroupChip,
	        isEnabled: tasks_v2_core.Core.getParams().features.isProjectsEnabled
	      }, {
	        title: tasks_v2_component_fields_accomplices.accomplicesMeta.title,
	        component: tasks_v2_component_fields_accomplices.Accomplices,
	        chip: tasks_v2_component_fields_accomplices.AccomplicesChip
	      }, {
	        title: tasks_v2_component_fields_auditors.auditorsMeta.title,
	        component: tasks_v2_component_fields_auditors.Auditors,
	        chip: tasks_v2_component_fields_auditors.AuditorsChip,
	        withSeparator: this.wasFilled(tasks_v2_component_fields_accomplices.accomplicesMeta.id)
	      }, {
	        title: tasks_v2_component_fields_flow.flowMeta.title,
	        component: tasks_v2_component_fields_flow.Flow,
	        chip: tasks_v2_component_fields_flow.FlowChip,
	        withSeparator: true,
	        isEnabled: tasks_v2_core.Core.getParams().features.isFlowEnabled
	      }, {
	        title: tasks_v2_component_fields_tags.tagsMeta.title,
	        component: tasks_v2_component_fields_tags.Tags,
	        chip: tasks_v2_component_fields_tags.TagsChip
	      }, {
	        title: tasks_v2_component_fields_crm.crmMeta.title,
	        component: tasks_v2_component_fields_crm.Crm,
	        chip: {
	          component: tasks_v2_component_fields_crm.CrmChip,
	          collapsed: true
	        },
	        withSeparator: this.wasFilled(tasks_v2_component_fields_group.groupMeta.id) || this.wasFilled(tasks_v2_component_fields_flow.flowMeta.id)
	      }, {
	        chip: {
	          component: tasks_v2_component_fields_parentTask.ParentTaskChip,
	          collapsed: true
	        }
	      }, {
	        chip: {
	          component: tasks_v2_component_fields_subTasks.SubTasksChip,
	          collapsed: true
	        }
	      }, {
	        chip: {
	          component: tasks_v2_component_fields_relatedTasks.RelatedTasksChip,
	          collapsed: true
	        }
	      }, {
	        chip: {
	          component: tasks_v2_component_fields_datePlan.DatePlanChip,
	          collapsed: true,
	          events: {
	            open: this.openDatePlan
	          }
	        }
	      }];

	      /* eslint-disable no-param-reassign */
	      fields.forEach(field => {
	        var _field$props;
	        (_field$props = field.props) != null ? _field$props : field.props = {};
	        field.props.taskId = this.taskId;
	      });
	      return fields;
	    },
	    primaryFields() {
	      const primaryFields = new Set([tasks_v2_component_fields_creator.Creator, tasks_v2_component_fields_responsible.Responsible, tasks_v2_component_fields_deadline.Deadline, ...(this.isEdit ? [tasks_v2_component_fields_status.Status] : [])]);
	      return this.fields.filter(({
	        component
	      }) => primaryFields.has(component));
	    },
	    projectFields() {
	      const projectFields = new Set();
	      if (this.wasFilled(tasks_v2_component_fields_group.groupMeta.id)) {
	        projectFields.add(tasks_v2_component_fields_group.Group);
	      }
	      if (this.wasFilled(tasks_v2_component_fields_flow.flowMeta.id)) {
	        projectFields.add(tasks_v2_component_fields_flow.Flow);
	      }
	      if (this.wasFilled(tasks_v2_component_fields_crm.crmMeta.id)) {
	        projectFields.add(tasks_v2_component_fields_crm.Crm);
	      }
	      return this.fields.filter(({
	        component
	      }) => projectFields.has(component));
	    },
	    participantsFields() {
	      const participantsFields = new Set();
	      if (this.wasFilled(tasks_v2_component_fields_accomplices.accomplicesMeta.id)) {
	        participantsFields.add(tasks_v2_component_fields_accomplices.Accomplices);
	      }
	      if (this.wasFilled(tasks_v2_component_fields_auditors.auditorsMeta.id)) {
	        participantsFields.add(tasks_v2_component_fields_auditors.Auditors);
	      }
	      return this.fields.filter(({
	        component
	      }) => participantsFields.has(component));
	    },
	    tagsFields() {
	      if (!this.wasFilled(tasks_v2_component_fields_tags.tagsMeta.id)) {
	        return [];
	      }
	      return this.fields.filter(({
	        component
	      }) => component === tasks_v2_component_fields_tags.Tags);
	    },
	    chips() {
	      return this.fields.filter(({
	        chip,
	        isEnabled
	      }) => chip && isEnabled !== false).map(({
	        chip
	      }) => chip);
	    },
	    isBottomSheetShown() {
	      return this.isDescriptionSheetShown || this.isFilesSheetShown || this.isCheckListSheetShown || this.isDatePlanSheetShown;
	    }
	  },
	  watch: {
	    async task() {
	      if (this.task) {
	        await this.$nextTick();
	        this.tryStartObserver();
	      }
	    }
	  },
	  async created() {
	    var _this$task2;
	    if (!this.isEdit && !this.task) {
	      void this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/insert`, {
	        id: this.taskId,
	        creatorId: this.currentUserId
	      });
	    }
	    await this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/clearFieldsFilled`, this.taskId);
	    if (this.isEdit && (!this.task || this.isPartiallyLoaded)) {
	      await tasks_v2_provider_service_taskService.taskService.getById(this.taskId);
	    }
	    await tasks_v2_provider_service_fileService.fileService.get(this.taskId).list(this.task.fileIds);
	    tasks_v2_component_fields_description.descriptionTextEditor.get(this.taskId, {
	      content: (_this$task2 = this.task) == null ? void 0 : _this$task2.description
	    });
	  },
	  mounted() {
	    var _this$$refs$skeleton;
	    this.tryStartObserver();
	    (_this$$refs$skeleton = this.$refs.skeleton) == null ? void 0 : _this$$refs$skeleton.append(tasks_v2_application_taskCard.FullSkeleton());
	  },
	  beforeUnmount() {
	    var _this$resizeObserver;
	    (_this$resizeObserver = this.resizeObserver) == null ? void 0 : _this$resizeObserver.disconnect();
	  },
	  unmounted() {
	    if (!this.isEdit) {
	      void this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/delete`, this.taskId);
	      tasks_v2_provider_service_fileService.fileService.delete(this.taskId);
	      tasks_v2_component_fields_description.descriptionTextEditor.delete(this.taskId);
	    }
	  },
	  methods: {
	    wasFilled(fieldId) {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, fieldId);
	    },
	    tryStartObserver() {
	      if (this.$refs.title && !this.resizeObserver) {
	        this.resizeObserver = this.getObserver();
	        this.resizeObserver.observe(this.$refs.title);
	      }
	    },
	    getObserver() {
	      return new ResizeObserver(entries => {
	        for (const entry of entries) {
	          if (entry.target === this.$refs.title) {
	            void this.$store.dispatch(`${tasks_v2_const.Model.Interface}/updateTitleFieldOffsetHeight`, entry.contentRect.height);
	          }
	        }
	      });
	    },
	    async addTask() {
	      var _this$$refs, _this$$refs$descripti;
	      await ((_this$$refs = this.$refs) == null ? void 0 : (_this$$refs$descripti = _this$$refs.description) == null ? void 0 : _this$$refs$descripti.save());
	      const checkLists = this.checkLists;
	      const [id] = await tasks_v2_provider_service_taskService.taskService.add(this.task);
	      this.taskId = id;
	      if (checkLists.length > 0) {
	        await tasks_v2_provider_service_checkListService.checkListService.save(this.taskId, checkLists);
	      }
	      tasks_v2_provider_service_fileService.fileService.replace(this.id, this.taskId);
	      tasks_v2_component_fields_description.descriptionTextEditor.replace(this.id, this.taskId);
	    },
	    openEditor() {
	      this.isDescriptionSheetShown = true;
	      this.handleShowBottomSheet();
	    },
	    closeEditor(slot) {
	      slot.close();
	      this.isDescriptionSheetShown = false;
	      this.handleCloseBottomSheet();
	    },
	    openFiles() {
	      this.isFilesSheetShown = true;
	      this.handleShowBottomSheet();
	    },
	    closeFiles() {
	      this.isFilesSheetShown = false;
	      this.handleCloseBottomSheet();
	    },
	    openCheckList(checkListId) {
	      this.checkListId = checkListId;
	      this.isCheckListSheetShown = true;
	      this.handleShowBottomSheet();
	    },
	    closeCheckList(checkListId) {
	      this.checkListId = checkListId;
	      this.isCheckListSheetShown = false;
	      this.handleCloseBottomSheet();
	    },
	    openDatePlan() {
	      this.isDatePlanSheetShown = true;
	      this.handleShowBottomSheet();
	    },
	    closeDatePlan() {
	      this.isDatePlanSheetShown = false;
	      this.handleCloseBottomSheet();
	    },
	    handleShowBottomSheet() {
	      main_core.Dom.addClass(this.$refs.scrollContent, '--disable-scroll');
	    },
	    handleCloseBottomSheet() {
	      main_core.Dom.removeClass(this.$refs.scrollContent, '--disable-scroll');
	    },
	    close() {
	      main_core_events.EventEmitter.emit(tasks_v2_const.EventName.CloseFullCard, {
	        taskId: this.taskId
	      });
	    }
	  },
	  template: `
		<div class="tasks-full-card" :class="{ '--blur': isDescriptionSheetShown }" :data-task-id="taskId" data-task-full>
			<template v-if="task && !isPartiallyLoaded">
				<div class="tasks-full-card-main" :class="{ '--overlay': isBottomSheetShown }" ref="main">
					<div class="tasks-full-card-content" :data-task-card-scroll="taskId" ref="scrollContent">
						<div
							ref="title">
							<TaskHeader :taskId/>
						</div>
						<FilesSheet
							:taskId="taskId"
							:isShown="isFilesSheetShown"
							:getBindElement="() => $refs.title"
							:getTargetContainer="() => $refs.main"
							@close="closeFiles"
						/>
						<CheckList
							:taskId="taskId"
							:checkListId="checkListId"
							:isShown="isCheckListSheetShown"
							:getBindElement="() => $refs.title"
							:getTargetContainer="() => $refs.main"
							@close="closeCheckList"
						/>
						<DatePlanSheet
							:taskId="taskId"
							:isShown="isDatePlanSheetShown"
							:getBindElement="() => $refs.title"
							:getTargetContainer="() => $refs.main"
							@close="closeDatePlan"
						/>
						<DescriptionField
							:taskId="taskId"
							v-slot="slot"
							ref="description"
						>
							<DescriptionSheet
								:taskId="taskId"
								:isShown="slot.isShown"
								:doOpenInEditMode="slot.doOpenInEditMode"
								:getBindElement="() => $refs.title"
								:getTargetContainer="() => $refs.main"
								@show="openEditor"
								@close="closeEditor(slot)"
							/>
						</DescriptionField>
						<div class="tasks-full-card-fields">
							<div class="tasks-full-card-field-container" data-field-container>
								<FieldList :fields="primaryFields"/>
							</div>
							<div class="tasks-full-card-chips-fields">
								<div
									v-if="files.length > 0 || wasFilled(filesMeta.id)"
									class="tasks-full-card-field-container --small-vertical-padding"
									data-field-container
								>
									<Files :taskId="taskId" @open="openFiles"/>
								</div>
								<div
									v-if="wasFilled(checkListMeta.id)"
									class="tasks-full-card-field-container --custom"
									data-field-container
								>
									<CheckList
										:taskId="taskId"
										:isPreview="true"
										:isComponentShown="!isCheckListSheetShown"
										:checkListId="checkListId"
										@open="openCheckList"
									/>
								</div>
								<div
									v-if="projectFields.length > 0"
									class="tasks-full-card-field-container"
									data-field-container
								>
									<FieldList :fields="projectFields"/>
								</div>
								<div
									v-if="participantsFields.length > 0"
									class="tasks-full-card-field-container"
									data-field-container
								>
									<FieldList
										:fields="participantsFields"
										:useSeparator="participantsFields.length > 1"
									/>
								</div>
								<div
									v-if="tagsFields.length > 0"
									class="tasks-full-card-field-container"
									data-field-container
								>
									<FieldList :fields="tagsFields"/>
								</div>
								<div
									v-if="wasFilled(datePlanMeta.id)"
									class="tasks-full-card-field-container"
									data-field-container
								>
									<DatePlan :taskId="taskId" @open="openDatePlan"/>
								</div>
								<div
									v-if="wasFilled(parentTaskMeta.id)"
									class="tasks-full-card-field-container --custom"
									data-field-container
								>
									<ParentTask :taskId="taskId"/>
								</div>
								<div
									v-if="wasFilled(subTasksMeta.id)"
									class="tasks-full-card-field-container --custom"
									data-field-container
								>
									<SubTasks :taskId="taskId"/>
								</div>
								<div
									v-if="wasFilled(relatedTasksMeta.id)"
									class="tasks-full-card-field-container --custom"
									data-field-container
								>
									<RelatedTasks :taskId="taskId"/>
								</div>
								<Chips :taskId="taskId" :chips="chips"/>
							</div>
						</div>
						<DropZone
							v-if="!isBottomSheetShown"
							:taskId="taskId"
						/>
					</div>
					<div class="tasks-full-card-footer">
						<FooterEdit v-if="isEdit" :taskId="taskId"/>
						<FooterCreate v-else :taskId="taskId" @addTask="addTask"/>
					</div>
				</div>
				<Chat :taskId="taskId"/>
			</template>
			<template v-else>
				<div ref="skeleton" style="width: 100%;"></div>
			</template>
		</div>
	`
	};

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _slider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("slider");
	var _application = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("application");
	var _handlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handlers");
	var _needToReloadGrid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needToReloadGrid");
	var _mountApplication = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mountApplication");
	var _unmountApplication = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unmountApplication");
	var _subscribe = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribe");
	var _unsubscribe = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unsubscribe");
	var _handleTaskUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleTaskUpdate");
	var _onClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClose");
	var _openFullCard = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openFullCard");
	var _openCompactCard = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openCompactCard");
	var _openGrid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openGrid");
	var _handlePopupShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handlePopupShow");
	class TaskFullCard {
	  constructor(_params2 = {}) {
	    Object.defineProperty(this, _unsubscribe, {
	      value: _unsubscribe2
	    });
	    Object.defineProperty(this, _subscribe, {
	      value: _subscribe2
	    });
	    Object.defineProperty(this, _unmountApplication, {
	      value: _unmountApplication2
	    });
	    Object.defineProperty(this, _mountApplication, {
	      value: _mountApplication2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _slider, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _application, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _handlers, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _needToReloadGrid, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _handleTaskUpdate, {
	      writable: true,
	      value: event => {
	        if (event.getData().id === babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].taskId) {
	          const fieldsForReloadGrid = new Set(['deadlineTs', 'responsibleId']);
	          const fields = Object.keys(event.getData());
	          if (fields.some(field => fieldsForReloadGrid.has(field))) {
	            babelHelpers.classPrivateFieldLooseBase(this, _needToReloadGrid)[_needToReloadGrid] = true;
	          }
	        }
	      }
	    });
	    Object.defineProperty(this, _onClose, {
	      writable: true,
	      value: event => {
	        if (event.getData().taskId === babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].taskId) {
	          babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].close();
	        }
	      }
	    });
	    Object.defineProperty(this, _openFullCard, {
	      writable: true,
	      value: async baseEvent => {
	        const {
	          TaskCard
	        } = await main_core.Runtime.loadExtension('tasks.v2.application.task-card');
	        const params = baseEvent.getData();
	        TaskCard.showFullCard(params);
	      }
	    });
	    Object.defineProperty(this, _openCompactCard, {
	      writable: true,
	      value: async baseEvent => {
	        const {
	          TaskCard
	        } = await main_core.Runtime.loadExtension('tasks.v2.application.task-card');
	        const params = baseEvent.getData();
	        TaskCard.showCompactCard(params);
	      }
	    });
	    Object.defineProperty(this, _openGrid, {
	      writable: true,
	      value: baseEvent => {
	        const {
	          taskId,
	          type
	        } = baseEvent.getData();
	        const userId = tasks_v2_core.Core.getParams().currentUserId;
	        BX.SidePanel.Instance.open(`/company/personal/user/${userId}/tasks/?relationToId=${taskId}&relationType=${type}`);
	      }
	    });
	    Object.defineProperty(this, _handlePopupShow, {
	      writable: true,
	      value: event => {
	        const popup = event.getCompatData()[0];
	        if (popup.getTargetContainer() !== document.body) {
	          return;
	        }
	        const onScroll = () => popup.adjustPosition();
	        const onClose = () => {
	          popup.unsubscribe('onClose', onClose);
	          popup.unsubscribe('onDestroy', onClose);
	          main_core.Event.unbind(document, 'scroll', onScroll, true);
	        };
	        popup.subscribe('onClose', onClose);
	        popup.subscribe('onDestroy', onClose);
	        main_core.Event.bind(document, 'scroll', onScroll, true);
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = _params2;
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].taskId = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].taskId || main_core.Text.getRandom();
	  }
	  async mountCard(slider) {
	    if (!slider.isOpen()) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider] = slider;
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribe)[_subscribe]();
	    babelHelpers.classPrivateFieldLooseBase(this, _application)[_application] = await babelHelpers.classPrivateFieldLooseBase(this, _mountApplication)[_mountApplication](slider.getContentContainer());
	  }
	  unmountCard() {
	    babelHelpers.classPrivateFieldLooseBase(this, _unmountApplication)[_unmountApplication]();
	    babelHelpers.classPrivateFieldLooseBase(this, _unsubscribe)[_unsubscribe]();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _needToReloadGrid)[_needToReloadGrid] && BX.Tasks.Util) {
	      const id = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].taskId;
	      BX.Tasks.Util.fireGlobalTaskEvent('UPDATE', {
	        ID: id
	      }, {
	        STAY_AT_PAGE: true
	      }, {
	        id
	      });
	    }
	  }
	}
	async function _mountApplication2(container) {
	  await tasks_v2_core.Core.init();
	  const application = ui_vue3.BitrixVue.createApp(App, {
	    ...babelHelpers.classPrivateFieldLooseBase(this, _params)[_params],
	    id: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].taskId
	  });
	  application.mixin(ui_vue3_mixins_locMixin.locMixin);
	  application.use(tasks_v2_core.Core.getStore());
	  application.mount(container);
	  return application;
	}
	function _unmountApplication2() {
	  var _babelHelpers$classPr;
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _application)[_application]) == null ? void 0 : _babelHelpers$classPr.unmount();
	}
	function _subscribe2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers] = {
	    [tasks_v2_const.EventName.TaskUpdate]: babelHelpers.classPrivateFieldLooseBase(this, _handleTaskUpdate)[_handleTaskUpdate],
	    [tasks_v2_const.EventName.CloseFullCard]: babelHelpers.classPrivateFieldLooseBase(this, _onClose)[_onClose],
	    [tasks_v2_const.EventName.OpenFullCard]: babelHelpers.classPrivateFieldLooseBase(this, _openFullCard)[_openFullCard],
	    [tasks_v2_const.EventName.OpenCompactCard]: babelHelpers.classPrivateFieldLooseBase(this, _openCompactCard)[_openCompactCard],
	    [tasks_v2_const.EventName.OpenGrid]: babelHelpers.classPrivateFieldLooseBase(this, _openGrid)[_openGrid],
	    'BX.Main.Popup:onShow': babelHelpers.classPrivateFieldLooseBase(this, _handlePopupShow)[_handlePopupShow]
	  };
	  Object.entries(babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers]).forEach(([event, handler]) => main_core_events.EventEmitter.subscribe(event, handler));
	}
	function _unsubscribe2() {
	  Object.entries(babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers]).forEach(([event, handler]) => main_core_events.EventEmitter.unsubscribe(event, handler));
	}

	exports.TaskFullCard = TaskFullCard;

}((this.BX.Tasks.V2.Application = this.BX.Tasks.V2.Application || {}),BX.Vue3,BX.Vue3.Mixins,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX,BX.Tasks.V2.Application,BX.Event,BX.Messenger.v2.Lib,BX.Messenger.v2.Application,BX.UI.System.Chip.Vue,BX.Tasks.V2.Lib,BX.Tasks.V2,BX.Tasks.V2.Component,BX.Vue3.Components,BX,BX.Vue3.Vuex,BX.UI.Vue3.Components,BX.UI.IconSet,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=task-full-card.bundle.js.map
