/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,main_popup,ui_iconSet_api_vue,main_core) {
	'use strict';

	const POPUP_CONTAINER_PREFIX = '#popup-window-content-';
	const BasePopup = {
	  name: 'BasePopup',
	  emits: ['close'],
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    config: {
	      type: Object,
	      required: false,
	      default: {}
	    }
	  },
	  computed: {
	    popupContainer() {
	      return `${POPUP_CONTAINER_PREFIX}${this.id}`;
	    }
	  },
	  created() {
	    this.instance = this.getPopupInstance();
	    this.instance.show();
	  },
	  mounted() {
	    this.instance.adjustPosition({
	      forceBindPosition: true,
	      position: this.getPopupConfig().bindOptions.position
	    });
	  },
	  beforeUnmount() {
	    if (!this.instance) {
	      return;
	    }
	    this.closePopup();
	  },
	  methods: {
	    getPopupInstance() {
	      if (!this.instance) {
	        var _PopupManager$getPopu;
	        (_PopupManager$getPopu = main_popup.PopupManager.getPopupById(this.id)) == null ? void 0 : _PopupManager$getPopu.destroy();
	        const config = this.getPopupConfig();
	        this.instance = new main_popup.Popup(config);
	      }
	      return this.instance;
	    },
	    getDefaultConfig() {
	      return {
	        id: this.id,
	        className: 'hr-structure-components-base-popup',
	        autoHide: true,
	        animation: 'fading-slide',
	        bindOptions: {
	          position: 'bottom'
	        },
	        cacheable: false,
	        events: {
	          onPopupClose: () => this.closePopup(),
	          onPopupShow: async () => {
	            const container = this.instance.getPopupContainer();
	            await Promise.resolve();
	            const {
	              top
	            } = container.getBoundingClientRect();
	            const offset = top + container.offsetHeight - document.body.offsetHeight;
	            if (offset > 0) {
	              const margin = 5;
	              this.instance.setMaxHeight(container.offsetHeight - offset - margin);
	            }
	          }
	        }
	      };
	    },
	    getPopupConfig() {
	      var _this$config$offsetTo, _this$config$bindOpti;
	      const defaultConfig = this.getDefaultConfig();
	      const modifiedOptions = {};
	      const defaultClassName = defaultConfig.className;
	      if (this.config.className) {
	        modifiedOptions.className = `${defaultClassName} ${this.config.className}`;
	      }
	      const offsetTop = (_this$config$offsetTo = this.config.offsetTop) != null ? _this$config$offsetTo : defaultConfig.offsetTop;
	      if (((_this$config$bindOpti = this.config.bindOptions) == null ? void 0 : _this$config$bindOpti.position) === 'top' && main_core.Type.isNumber(this.config.offsetTop)) {
	        modifiedOptions.offsetTop = offsetTop - 10;
	      }
	      return {
	        ...defaultConfig,
	        ...this.config,
	        ...modifiedOptions
	      };
	    },
	    closePopup() {
	      this.$emit('close');
	      this.instance.destroy();
	      this.instance = null;
	    },
	    enableAutoHide() {
	      this.getPopupInstance().setAutoHide(true);
	    },
	    disableAutoHide() {
	      this.getPopupInstance().setAutoHide(false);
	    },
	    adjustPosition() {
	      this.getPopupInstance().adjustPosition({
	        forceBindPosition: true,
	        position: this.getPopupConfig().bindOptions.position
	      });
	    }
	  },
	  template: `
		<Teleport :to="popupContainer">
			<slot
				:adjustPosition="adjustPosition"
				:enableAutoHide="enableAutoHide"
				:disableAutoHide="disableAutoHide"
				:closePopup="closePopup"
			></slot>
		</Teleport>
	`
	};

	const BaseActionMenuPropsMixin = {
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    items: {
	      type: Array,
	      required: true,
	      default: []
	    }
	  }
	};
	const BaseActionMenu = {
	  name: 'BaseActionMenu',
	  mixins: [BaseActionMenuPropsMixin],
	  props: {
	    width: {
	      type: Number,
	      required: false,
	      default: 260
	    },
	    delimiter: {
	      type: Boolean,
	      required: false,
	      default: true
	    }
	  },
	  emits: ['action', 'close'],
	  components: {
	    BasePopup
	  },
	  computed: {
	    popupConfig() {
	      return {
	        width: this.width,
	        bindElement: this.bindElement,
	        borderRadius: 12,
	        contentNoPaddings: true,
	        contentPadding: 0,
	        padding: 0,
	        offsetTop: 4
	      };
	    }
	  },
	  methods: {
	    onItemClick(event, item, closePopup) {
	      var _item$disabled;
	      event.stopPropagation();
	      if ((_item$disabled = item.disabled) != null ? _item$disabled : false) {
	        return;
	      }
	      this.$emit('action', item.id);
	      closePopup();
	    },
	    close() {
	      this.$emit('close');
	    }
	  },
	  template: `
		<BasePopup
			:config="popupConfig"
            v-slot="{closePopup}"
			:id="id"
			@close="close"
		>
		  <div class="hr-structure-components-action-menu-container">
			<template v-for="(item, index) in items">
				<div 
					class="hr-structure-components-action-menu-item-wrapper"
					:class="{ '--disabled': item.disabled ?? false }"
					@click="onItemClick($event, item, closePopup)"
				>
					<slot :item="item"></slot>
				</div>
				<span v-if="delimiter && index < items.length - 1"
					class="hr-structure-action-popup-menu-item-delimiter"
				></span>
			</template>
		  </div>
		</BasePopup>
	`
	};

	const RouteActionMenuItem = {
	  name: 'RouteActionMenuItem',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    description: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    imageClass: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    bIcon: {
	      type: Object,
	      required: false,
	      default: null
	    }
	  },
	  methods: {
	    capitalizedText(text) {
	      return text.charAt(0).toUpperCase() + text.slice(1);
	    }
	  },
	  template: `
		<div class="hr-structure-route-action-popup-menu-item">
			<div class="hr-structure-route-action-popup-menu-item__content">
				<BIcon
					v-if="bIcon"
					:name="bIcon.name"
					:size="bIcon.size || 20"
					:color="bIcon.color || 'black'"
				/>
				<div
					v-if="!bIcon && imageClass"
					class="hr-structure-route-action-popup-menu-item__content-icon-container"

				>
					<div 
						class="hr-structure-route-action-popup-menu-item__content-icon"
						:class="imageClass"
					/>
				</div>
				<div class="hr-structure-route-action-popup-menu-item__content-text-container">
					<div
						class="hr-structure-route-action-popup-menu-item__content-title"
					>
						{{ capitalizedText(this.title) }}
					</div>
					<div class="hr-structure-route-action-popup-menu-item__content-description">{{ capitalizedText(this.description) }}</div>
				</div>
			</div>
		</div>
	`
	};

	const RouteActionMenu = {
	  name: 'RouteActionMenu',
	  mixins: [BaseActionMenuPropsMixin],
	  components: {
	    BaseActionMenu,
	    RouteActionMenuItem
	  },
	  template: `
		<BaseActionMenu
			:id="id"
			:items="items" 
			:bindElement="bindElement"
			:width="260"
			v-slot="{item}"
			@close="this.$emit('close')"
		>
			<RouteActionMenuItem
				:id="item.id" 
				:title="item.title"
				:description="item.description"
				:imageClass="item.imageClass"
				:bIcon="item.bIcon"
			/>
		</BaseActionMenu>
	`
	};

	const SupportedColors = ['red'];
	const ActionMenuItem = {
	  name: 'ActionMenuItem',
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    imageClass: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    colorClass() {
	      if (SupportedColors.includes(this.color)) {
	        return `--${this.color}`;
	      }
	      return '';
	    }
	  },
	  template: `
		<div class="hr-structure-action-popup-menu-item">
			<div class="hr-structure-action-popup-menu-item__content">
				<div
					class="hr-structure-action-popup-menu-item__content-title"
					:class="[imageClass, colorClass]"
				>
					{{ title }}
				</div>
			</div>
		</div>
	`
	};

	const ActionMenu = {
	  name: 'ActionMenu',
	  mixins: [BaseActionMenuPropsMixin],
	  components: {
	    BaseActionMenu,
	    ActionMenuItem
	  },
	  template: `
		<BaseActionMenu 
			:id="id"
			:items="items" 
			:bindElement="bindElement"
			:width="260"
			:delimiter="false"
 			v-slot="{item}"
			@close="this.$emit('close')"
		>
			<ActionMenuItem
				:id="item.id" 
				:title="item.title"
				:imageClass="item.imageClass"
				:color="item.color"
				@click="this.$emit('action', item.id)"
			/>
		</BaseActionMenu>
	`
	};

	const DefaultPopupLayout = {
	  name: 'DefaultPopupLayout',
	  template: `
		<div
			v-if="$slots.content"
			class="hr-default-popup-layout__content"
		>
			<slot name="content"></slot>
		</div>
	`
	};

	let _ = t => t,
	  _t;
	const ConfirmationPopup = {
	  name: 'ConfirmationPopup',
	  emits: ['close', 'action'],
	  components: {
	    BasePopup,
	    DefaultLayoutPopup: DefaultPopupLayout
	  },
	  props: {
	    title: {
	      type: String,
	      required: false,
	      default: null
	    },
	    withoutTitleBar: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    description: {
	      type: String,
	      required: false
	    },
	    onlyConfirmButtonMode: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    confirmBtnText: {
	      type: String,
	      required: false,
	      default: null
	    },
	    showActionButtonLoader: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    lockActionButton: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    cancelBtnText: {
	      type: String,
	      required: false,
	      default: null
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: false,
	      default: null
	    },
	    width: {
	      type: Number,
	      required: false,
	      default: 300
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    closeAction() {
	      if (this.showActionButtonLoader) {
	        return;
	      }
	      this.$emit('close');
	    },
	    performAction() {
	      if (this.lockActionButton) {
	        return;
	      }
	      this.$emit('action');
	    },
	    getTitleBar() {
	      var _this$title;
	      const {
	        root,
	        closeButton
	      } = main_core.Tag.render(_t || (_t = _`
				<div class="hr-confirmation-popup__title-bar">
					<span class="hr-confirmation-popup__title-bar-text">
						${0}
					</span>
					<div
						class="ui-icon-set --cross-25 hr-confirmation-popup__title-bar-close-button"
						ref="closeButton"
					>
					</div>
				</div>
			`), (_this$title = this.title) != null ? _this$title : '');
	      main_core.Event.bind(closeButton, 'click', () => {
	        this.closeAction();
	      });
	      return {
	        content: root
	      };
	    }
	  },
	  computed: {
	    popupConfig() {
	      return {
	        width: this.width,
	        bindElement: this.bindElement,
	        borderRadius: 12,
	        overlay: this.bindElement === null ? {
	          opacity: 40
	        } : false,
	        contentNoPaddings: true,
	        contentPadding: 0,
	        padding: 0,
	        className: 'hr_structure_confirmation_popup',
	        autoHide: false,
	        draggable: true,
	        titleBar: this.withoutTitleBar ? null : this.getTitleBar()
	      };
	    }
	  },
	  template: `
		<BasePopup
			:id="'id'"
			:config="popupConfig"
		>
			<template v-slot="{ closePopup }">
				<DefaultLayoutPopup>
					<template v-slot:content>
						<div
							class="hr-confirmation-popup__content-container"
							:class="{ '--without-title-bar': withoutTitleBar }"
						>
							<div v-if="$slots.content">
								<slot name="content"></slot>
							</div>
							<div v-else class="hr-confirmation-popup__content-text">
								{{ description }}
							</div>
						</div>
						<div class="hr-confirmation-popup__buttons-container">
							<button
								class="ui-btn ui-btn-primary ui-btn-round"
								:class="{ 'ui-btn-wait': showActionButtonLoader, 'ui-btn-disabled': lockActionButton }"
								@click="performAction"
							>
								{{ confirmBtnText ?? '' }}
							</button>
							<button
								v-if="!onlyConfirmButtonMode"
								class="ui-btn ui-btn-light-border ui-btn-round"
								@click="closeAction"
							>
								{{ cancelBtnText ?? loc('HUMANRESOURCES_COMPANY_STRUCTURE_STRUCTURE_COMPONENTS_POPUP_CONFIRMATION_POPUP_CANCEL_BUTTON') }}
							</button>
						</div>
					</template>
				</DefaultLayoutPopup>
			</template>
		</BasePopup>
	`
	};

	exports.BasePopup = BasePopup;
	exports.BaseActionMenu = BaseActionMenu;
	exports.RouteActionMenu = RouteActionMenu;
	exports.ActionMenu = ActionMenu;
	exports.ConfirmationPopup = ConfirmationPopup;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Main,BX.UI.IconSet,BX));
//# sourceMappingURL=structure-components.bundle.js.map
