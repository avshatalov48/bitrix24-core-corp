/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core,ui_uploader_core,ui_iconSet_api_vue,ui_iconSet_main,tasks_v2_const,tasks_v2_provider_service_fileService) {
	'use strict';

	// @vue/component
	const DropZone = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    bottom: {
	      type: Number,
	      default: 100
	    }
	  },
	  setup(props) {
	    return {
	      Main: ui_iconSet_api_vue.Main,
	      fileService: tasks_v2_provider_service_fileService.fileService.get(props.taskId)
	    };
	  },
	  data() {
	    return {
	      showDropArea: false,
	      lastDropAreaEnterTarget: null
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    dropAreaStyles() {
	      return {
	        bottom: `${this.bottom}px`
	      };
	    },
	    iconSize() {
	      return 69;
	    },
	    container() {
	      var _this$$parent;
	      return (_this$$parent = this.$parent) == null ? void 0 : _this$$parent.$el;
	    }
	  },
	  mounted() {
	    if (!main_core.Type.isElementNode(this.container) || this.readonly) {
	      return;
	    }
	    this.bindEvents();
	  },
	  beforeUnmount() {
	    if (!main_core.Type.isElementNode(this.container) || this.readonly) {
	      return;
	    }
	    this.unbindEvents();
	  },
	  methods: {
	    bindEvents() {
	      this.fileService.getAdapter().getUploader().assignDropzone(this.container);
	      main_core.Event.bind(this.container, 'dragenter', this.onDragEnter);
	      main_core.Event.bind(this.container, 'dragleave', this.onDragLeave);
	      main_core.Event.bind(this.container, 'drop', this.onDrop);
	    },
	    unbindEvents() {
	      this.fileService.getAdapter().getUploader().unassignDropzone(this.container);
	      main_core.Event.unbind(this.container, 'dragenter', this.onDragEnter);
	      main_core.Event.unbind(this.container, 'dragleave', this.onDragLeave);
	      main_core.Event.unbind(this.container, 'drop', this.onDrop);
	    },
	    async onDragEnter(event) {
	      event.stopPropagation();
	      event.preventDefault();
	      const success = await ui_uploader_core.hasDataTransferOnlyFiles(event.dataTransfer, false);
	      if (!success) {
	        return;
	      }
	      this.lastDropAreaEnterTarget = event.target;
	      this.showDropArea = true;
	    },
	    onDragLeave(event) {
	      event.stopPropagation();
	      event.preventDefault();
	      if (this.lastDropAreaEnterTarget !== event.target) {
	        return;
	      }
	      this.showDropArea = false;
	    },
	    async onDrop(event) {
	      event.preventDefault();
	      this.showDropArea = false;
	    }
	  },
	  template: `
		<Transition name="tasks-drop-zone-fade">
			<div v-if="showDropArea" :style="dropAreaStyles" class="tasks-drop-zone__container">
				<div class="tasks-drop-zone__box">
					<BIcon :name="Main.ATTACH" :size="iconSize" class="tasks-drop-zone__icon"/>
					<label class="tasks-drop-zone__label_text">{{ loc('TASKS_V2_DROP_ZONE_TITLE') }}</label>
				</div>
			</div>
		</Transition>
	`
	};

	exports.DropZone = DropZone;

}((this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {}),BX,BX.UI.Uploader,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=drop-zone.bundle.js.map
