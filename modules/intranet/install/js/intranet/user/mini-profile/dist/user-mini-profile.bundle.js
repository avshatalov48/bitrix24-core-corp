/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_core_cache,ui_vue3_components_button,ui_vue3,ui_iconSet_outline,humanresources_companyStructure_public,ui_vue3_components_menu,ui_iconSet_api_core,main_date,ui_notification,ui_vue3_components_richMenu,ui_iconSet_api_vue,ui_vue3_components_avatar,main_core_events,main_core,main_popup) {
	'use strict';

	class Backend {
	  static load(userId) {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('intranet.user.miniProfile.load', {
	        data: {
	          userId
	        }
	      }).then(result => {
	        resolve(result.data);
	      }).catch(result => {
	        var _result$errors$0$code, _result$errors$;
	        reject((_result$errors$0$code = (_result$errors$ = result.errors[0]) == null ? void 0 : _result$errors$.code) != null ? _result$errors$0$code : null);
	      });
	    });
	  }
	}

	const InitialParamDict = {
	  RightSideExpand: 'right-side-expand'
	};
	const prefix = 'intranet-user-mini-profile';
	var _getKeyByType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getKeyByType");
	class InitialParamService {
	  // eslint-disable-next-line flowtype/require-return-type
	  static getValue(type) {
	    const key = babelHelpers.classPrivateFieldLooseBase(this, _getKeyByType)[_getKeyByType](type);
	    return localStorage.getItem(key);
	  }
	  static save(type, value) {
	    const key = babelHelpers.classPrivateFieldLooseBase(this, _getKeyByType)[_getKeyByType](type);
	    if (!Object.values(InitialParamDict).includes(type)) {
	      return;
	    }
	    localStorage.setItem(key, value);
	  }
	}
	function _getKeyByType2(type) {
	  return prefix + type;
	}
	Object.defineProperty(InitialParamService, _getKeyByType, {
	  value: _getKeyByType2
	});

	// @vue/component
	const Divider = {
	  name: 'UserMiniProfileDivider',
	  props: {
	    isVertical: {
	      type: Boolean,
	      default: false
	    }
	  },
	  template: `
		<div 
			class="intranet-user-mini-profile__divider"
			:class="isVertical ? '--vertical' : '--horizontal'"
		>
			<div class="intranet-user-mini-profile__divider-inner"></div>
		</div>
	`
	};

	const ErrorStateSettingByType = {
	  default: {
	    class: '--default',
	    title: main_core.Loc.getMessage('INTRANET_USER_MINI_ERROR_STATE_TITLE'),
	    description: main_core.Loc.getMessage('INTRANET_USER_MINI_ERROR_STATE_DESCRIPTION')
	  },
	  'access-denied': {
	    class: '--access-denied',
	    title: main_core.Loc.getMessage('INTRANET_USER_MINI_ERROR_STATE_ACCESS_DENIED_TITLE'),
	    description: main_core.Loc.getMessage('INTRANET_USER_MINI_ERROR_STATE_ACCESS_DENIED_DESCRIPTION')
	  }
	};
	const ErrorStateDict = Object.freeze({
	  AccessDenied: 'access-denied',
	  Default: 'default'
	});

	// @vue/component
	const ErrorState = {
	  name: 'ErrorState',
	  props: {
	    type: {
	      /** @type ErrorStateType */
	      type: String,
	      default: ErrorStateDict.Default,
	      validator: value => {
	        return Object.values(ErrorStateDict).includes(value);
	      }
	    }
	  },
	  computed: {
	    setting() {
	      var _ErrorStateSettingByT;
	      return (_ErrorStateSettingByT = ErrorStateSettingByType[this.type]) != null ? _ErrorStateSettingByT : null;
	    }
	  },
	  template: `
		<div 
			class="intranet-user-mini-profile__error-state"
			:class="setting?.class"
		>
			<div class="intranet-user-mini-profile__error-state-content">
				<div class="intranet-user-mini-profile__error-state__icon"></div>
				<div class="intranet-user-mini-profile__error-state__title">
					{{ setting?.title }}
				</div>
				<div class="intranet-user-mini-profile__error-state__description">
					{{ setting?.description }}
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const UserMiniProfileLoader = {
	  name: 'UserMiniProfileLoader',
	  props: {
	    isShort: {
	      type: Boolean,
	      default: false
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile-loader" :class="{ '--short': isShort }"></div>
	`
	};

	// @vue/component
	const LoaderTransition = {
	  name: 'LoaderTransition',
	  components: {
	    UserMiniProfileLoader
	  },
	  props: {
	    isLoading: {
	      type: Boolean,
	      required: true
	    },
	    isShowContent: {
	      type: Boolean,
	      required: true
	    },
	    isLoaderShort: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['end'],
	  methods: {
	    resetSize() {
	      main_core.Dom.style(this.$el, {
	        width: '',
	        height: ''
	      });
	    },
	    onEnd() {
	      this.resetSize();
	      this.$emit('end');
	    },
	    onEnter(el) {
	      requestAnimationFrame(() => {
	        main_core.Dom.style(this.$el, {
	          width: `${el.offsetWidth}px`,
	          height: `${el.offsetHeight}px`
	        });
	      });
	    },
	    onBeforeLeave(el) {
	      main_core.Dom.style(this.$el, {
	        width: `${el.offsetWidth}px`,
	        height: `${el.offsetHeight}px`
	      });
	    },
	    onAfterLeave() {
	      this.onEnd();
	    },
	    onAfterEnter() {
	      this.onEnd();
	    }
	  },
	  template: `
		<TransitionGroup 
			name="intranet-user-mini-profile-fade"
			tag="div"
			class="intranet-user-mini-profile__loader-transition-wrapper"
			@enter="onEnter"
			@beforeLeave="onBeforeLeave"
			@afterLeave="onAfterLeave"
			@afterEnter="onAfterEnter"
		>
			<div v-if="isLoading"
                 class="intranet-user-mini-profile__loader-transition-wrapper__loader"
				 key="loader"
			>
				<UserMiniProfileLoader
				   :isShort="isLoaderShort"
				/>
			</div>
			<slot v-else-if="isShowContent"></slot>
		</TransitionGroup>
	`
	};

	const ButtonMixin = {
	  components: {
	    Button: ui_vue3_components_button.Button
	  },
	  computed: {
	    buttonSize: () => ui_vue3_components_button.ButtonSize,
	    buttonStyle: () => ui_vue3_components_button.AirButtonStyle,
	    buttonIcon: () => ui_vue3_components_button.ButtonIcon
	  }
	};

	// @vue/mixin
	const IconSetMixin = {
	  computed: {
	    set: () => ui_iconSet_api_vue.Set,
	    outlineSet: () => ui_iconSet_api_vue.Outline
	  }
	};

	// @vue/mixin
	const LocMixin = {
	  methods: {
	    loc(code, replacements = null) {
	      return main_core.Loc.getMessage(code, replacements);
	    },
	    locPlural(code, value, replacements = null) {
	      return main_core.Loc.getMessagePlural(code, value, replacements);
	    }
	  }
	};

	// @vue/component
	const DepartmentConnector = {
	  name: 'DepartmentConnector',
	  props: {
	    topBindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    bottomBindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    offsetLeft: {
	      type: Number,
	      default: 11
	    }
	  },
	  computed: {
	    top() {
	      const {
	        height
	      } = this.topBindElement.getBoundingClientRect();
	      const value = this.topBindElement.offsetTop + height;
	      return `${value}px`;
	    },
	    left() {
	      const value = this.topBindElement.offsetLeft + this.offsetLeft;
	      return `${value}px`;
	    },
	    height() {
	      const topElementBottom = this.topBindElement.offsetTop + this.topBindElement.offsetHeight;
	      const bottomElementCenter = this.bottomBindElement.offsetTop + this.bottomBindElement.offsetHeight / 2;
	      return Math.round(bottomElementCenter - topElementBottom);
	    },
	    pathD() {
	      const height = this.height;
	      const d = ['M 1 0', `V ${height - 5}`, `C 1 ${height - 3}.2091 2.7909 ${height - 1} 5 ${height - 1}`, 'H 9'];
	      return d.join('');
	    },
	    viewBox() {
	      return `0 0 9 ${this.height}`;
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile__structure-view-connector" 
			 :style="{ 'top': top, 'left': left }"
		>
			<svg width="9" :height="height" :viewBox="viewBox" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path :d="pathD" stroke="#F0F0F0"/>
			</svg>
		</div>
	`
	};

	class OpenActionService {
	  static openStructureNodeId(nodeId) {
	    humanresources_companyStructure_public.Structure == null ? void 0 : humanresources_companyStructure_public.Structure.open({
	      focusNodeId: nodeId
	    });
	  }
	  static openUserProfile(url) {
	    if (!main_core.Type.isStringFilled(url)) {
	      return;
	    }
	    BX.SidePanel.Instance.open(url);
	  }
	}

	// @vue/component
	const DepartmentBlock = {
	  name: 'DepartmentBlock',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    Avatar: ui_vue3_components_avatar.Avatar
	  },
	  mixins: [LocMixin, IconSetMixin],
	  props: {
	    nodeId: {
	      type: Number,
	      required: true
	    },
	    highlighted: {
	      type: Boolean,
	      default: false
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    employeeCount: {
	      type: Number,
	      required: true
	    },
	    user: {
	      /** @type UserData | null */
	      type: [Object, null],
	      default: () => {}
	    },
	    head: {
	      /** @type UserData | null */
	      type: [Object, null],
	      default: () => {}
	    }
	  },
	  computed: {
	    employeeCountTitle() {
	      const {
	        employeeCount
	      } = this;
	      return this.locPlural('INTRANET_USER_MINI_PROFILE_EMPLOYEES_COUNT', employeeCount, {
	        '#COUNT#': employeeCount
	      });
	    },
	    isShowHead() {
	      const {
	        head,
	        user
	      } = this;
	      return head && head.id !== (user == null ? void 0 : user.id);
	    }
	  },
	  methods: {
	    onTitleClick() {
	      OpenActionService.openStructureNodeId(this.nodeId);
	    },
	    onUserClick(user) {
	      OpenActionService.openUserProfile(user.url);
	    }
	  },
	  template: `
		<div 
			class="intranet-user-mini-profile__structure-view-department-block"
			:class="{'--highlighted': highlighted }"
			data-test-id="usermp_department-block"
		>
			<div class="intranet-user-mini-profile__structure-view-department-block__title"
				 :title="title"
				 data-test-id="usermp_department-title"
				 @click="onTitleClick"
			>
				<span>{{ title }}</span>
				<div class="intranet-user-mini-profile__structure-view-department-block__title-chevron">
					<div class="intranet-user-mini-profile__structure-view-department-block__title-chevron-icon">
						<BIcon
							:name="outlineSet.CHEVRON_RIGHT_S"
							:size="20"
						/>
					</div>
				</div>
			</div>
			<div class="intranet-user-mini-profile__structure-view-department-block__employee-count" data-test-id="usermp_employee-count">
				{{ employeeCountTitle }}
			</div>
			<div v-if="user"
				class="intranet-user-mini-profile__structure-view-department-block__user"
				data-test-id="usermp_department-user"
				@click="onUserClick(user)"
			>
				<div class="intranet-user-mini-profile__structure-view-department-block__user-avatar">
					<Avatar :options="{ 
						picPath: user.avatar ? encodeURI(user.avatar) : undefined,
						size: 28, 
						title: user.name 
					}"/>
				</div>
				<div class="intranet-user-mini-profile__structure-view-department-block__user-info">
					<div 
						class="intranet-user-mini-profile__structure-view-department-block__user-info__name"
						:title="user.name"
						data-test-id="usermp_department-user-name"
					>
						{{ user.name }}
					</div>
					<div v-if="user.workPosition"
						class="intranet-user-mini-profile__structure-view-department-block__user-info__position"
						:title="user.workPosition"
						data-test-id="usermp_department-user-position"
					>
						{{ user.workPosition }}
					</div>
				</div>
			</div>
			<div v-if="isShowHead" 
				class="intranet-user-mini-profile__structure-view-department-block__head"
				data-test-id="usermp_department-head-section"
			>
				<div class="intranet-user-mini-profile__structure-view-department-block__head-title" data-test-id="usermp_department-head-title">
					{{ loc('INTRANET_USER_MINI_DETAILED_INFO_HEAD') }}
				</div>
				<div
					class="intranet-user-mini-profile__structure-view-department-block__head-info"
					data-test-id="usermp_department-head"
					@click="onUserClick(head)"
				>
					<div
						class="intranet-user-mini-profile__structure-view-department-block__head-info__avatar"
						data-test-id="usermp_department-head-avatar"
					>
						<Avatar :options="{ picPath: head.avatar, size: 20, title: head.name }"/>
					</div>
					<div
						class="intranet-user-mini-profile__structure-view-department-block__head-info__name"
						:title="head.name"
						data-test-id="usermp_department-head-name"
					>
						{{ head.name }}
					</div>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const LockedDepartmentBlock = {
	  name: 'LockedDepartmentBlock',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  mixins: [IconSetMixin],
	  template: `
		<div 
			class="intranet-user-mini-profile__structure-view-department-block --locked"
		>
			<div class="intranet-user-mini-profile__structure-view-department-block-lock">
				<BIcon 
					:size="30" 
					:name="outlineSet.LOCK_L"
				/>
			</div>
		</div>
	`
	};

	const DepartmentSpacer = {
	  name: 'DepartmentSpacer',
	  props: {
	    value: {
	      type: Number,
	      required: true
	    },
	    isVertical: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: {
	    style() {
	      const value = `${this.value}px`;
	      if (!this.isVertical) {
	        return {
	          width: value,
	          minWidth: value
	        };
	      }
	      return {
	        height: value,
	        minHeight: value
	      };
	    }
	  },
	  template: `
		<div :style="style"/>
	`
	};

	const LockedDepartment = 'locked-department';

	// @vue/component
	const StructureView = {
	  name: 'StructureView',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    DepartmentBlock,
	    LockedDepartmentBlock,
	    DepartmentConnector,
	    DepartmentSpacer
	  },
	  mixins: [LocMixin, IconSetMixin],
	  props: {
	    title: {
	      type: String,
	      default: ''
	    },
	    branch: {
	      /** @type BranchProp */
	      type: Array,
	      required: true
	    },
	    headDictionary: {
	      /** @type HeadDictionary */
	      type: Object,
	      required: true
	    },
	    userDepartmentId: {
	      type: Number,
	      default: null
	    },
	    user: {
	      /** @type UserData | null */
	      type: Object,
	      default: null
	    }
	  },
	  data() {
	    return {
	      blocks: [],
	      connectorBindElementPairs: []
	    };
	  },
	  computed: {
	    LockedDepartment: () => LockedDepartment
	  },
	  mounted() {
	    this.$nextTick(() => {
	      this.makeConnectors();
	    });
	  },
	  methods: {
	    makeConnectors() {
	      this.connectorBindElementPairs = [];
	      const connectorCount = this.blocks.length - 2;
	      for (let i = 0; i <= connectorCount; ++i) {
	        const topBlock = this.blocks[i].$el;
	        const bottomBlock = this.blocks[i + 1].$el;
	        this.connectorBindElementPairs.push([topBlock, bottomBlock]);
	      }
	    },
	    getHeadForDepartment(department) {
	      var _this$headDictionary$;
	      const {
	        id: departmentId
	      } = department;
	      if (departmentId !== this.userDepartmentId) {
	        return null;
	      }
	      const {
	        headIds
	      } = department;
	      const userIsHead = headIds.includes(this.user.id);
	      if (userIsHead) {
	        return null;
	      }
	      const firstHeadId = headIds[0];
	      if (!firstHeadId) {
	        return null;
	      }
	      return (_this$headDictionary$ = this.headDictionary[firstHeadId]) != null ? _this$headDictionary$ : null;
	    },
	    getUserForDepartment(department) {
	      var _this$headDictionary$2;
	      const {
	        id: departmentId
	      } = department;
	      if (departmentId === this.userDepartmentId) {
	        return this.user;
	      }
	      const {
	        headIds
	      } = department;
	      const firstHeadId = headIds[0];
	      if (!firstHeadId) {
	        return null;
	      }
	      return (_this$headDictionary$2 = this.headDictionary[firstHeadId]) != null ? _this$headDictionary$2 : null;
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile__structure-view" data-test-id="usermp_structure-view">
			<div class="intranet-user-mini-profile__structure-view__title" data-test-id="usermp_structure-title">
				<div class="intranet-user-mini-profile__structure-view__title-icon" data-test-id="usermp_structure-title-icon">
					<BIcon :name="outlineSet.COMPANY" :size="18"/>
				</div>
				<span data-test-id="usermp_structure-title-text">{{ title }}</span>
			</div>
			<div class="intranet-user-mini-profile__structure-view__preview" v-if="branch.length" data-test-id="usermp_structure-preview">
				<div v-for="(department, index) in branch"
					 class="intranet-user-mini-profile__structure-view__preview-row"
					 data-test-id="usermp_structure-department-row"
				>
					<DepartmentSpacer :value="index * 20"/>
					<template v-if="department === LockedDepartment">
						<LockedDepartmentBlock
							:ref="el => { blocks[index] = el}"
							data-test-id="usermp_locked-department-block"
						/>
					</template>
					<template v-else>
						<DepartmentBlock
							:ref="el => { blocks[index] = el}"
							:key="'department-' + department.id"
							:title="department.title"
							:nodeId="department.id"
							:employee-count="department.employeeCount"
							:user="getUserForDepartment(department)"
							:head="getHeadForDepartment(department)"
							:highlighted="department.id === userDepartmentId"
							data-test-id="usermp_department-block"
						/>
					</template>
				</div>
				<template
					v-for="elementPair in connectorBindElementPairs"
				>
					<DepartmentConnector
						:topBindElement="elementPair[0]"
						:bottomBindElement="elementPair[1]"
						:offsetLeft="11"
						data-test-id="usermp_department-connector"
					/>
				</template>
			</div>
		</div>
	`
	};

	const StructureViewListAnimation = Object.freeze({
	  next: 'intranet-user-mini-profile-structure-view-carousel-next',
	  prev: 'intranet-user-mini-profile-structure-view-carousel-prev'
	});
	const maxElementsInBranch = 3;

	// @vue/component
	const StructureViewList = {
	  name: 'StructureViewList',
	  components: {
	    StructureView,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  mixins: [IconSetMixin, ButtonMixin],
	  props: {
	    structure: {
	      /** @type StructureType */
	      type: Object,
	      required: true
	    },
	    user: {
	      /** @type UserData */
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      index: 0,
	      animationName: '',
	      isTransitionInProgress: false
	    };
	  },
	  computed: {
	    isPrevDisabled() {
	      return this.isTransitionInProgress || this.index === 0;
	    },
	    isNextDisabled() {
	      return this.isTransitionInProgress || this.index >= this.structure.userDepartmentIds.length - 1;
	    },
	    hasManyUserDepartments() {
	      return this.structure.userDepartmentIds.length > 1;
	    },
	    maxBranchHeight() {
	      let result = 0;
	      for (const departmentId of this.structure.userDepartmentIds) {
	        const departmentBranchHeight = this.makeDepartmentBranch(departmentId).length;
	        if (departmentBranchHeight === maxElementsInBranch) {
	          return maxElementsInBranch;
	        }
	        result = Math.max(result, departmentBranchHeight);
	      }
	      return result;
	    },
	    missingMaxDepartmentCount() {
	      return maxElementsInBranch - this.maxBranchHeight;
	    }
	  },
	  methods: {
	    makeDepartmentBranch(departmentId) {
	      const {
	        departmentDictionary
	      } = this.structure;
	      const department = departmentDictionary[departmentId];
	      if (!department) {
	        return [];
	      }
	      const branch = [];
	      let node = department;
	      while (node && branch.length < maxElementsInBranch) {
	        branch.push(node);
	        if (node.parentId === null) {
	          break;
	        }
	        node = departmentDictionary[node.parentId];
	      }
	      if (branch.length < maxElementsInBranch && branch[branch.length - 1].parentId !== 0) {
	        branch.push(LockedDepartment);
	      }
	      return branch.reverse();
	    },
	    next() {
	      if (this.isNextDisabled) {
	        return;
	      }
	      this.animationName = StructureViewListAnimation.next;
	      this.index += 1;
	    },
	    prev() {
	      if (this.isPrevDisabled) {
	        return;
	      }
	      this.animationName = StructureViewListAnimation.prev;
	      this.index -= 1;
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile__structure-view-list" data-test-id="usermp_structure-view-list">
			<div
				class="intranet-user-mini-profile__structure-view-list__preview-zone"
				:class="{ '--one-branch': !hasManyUserDepartments }"
				:style="{ '--missing-max-department-count': missingMaxDepartmentCount }"
				data-test-id="usermp_structure-preview-zone"
			>
				<TransitionGroup 
					type="transition" 
					:name="animationName"
					@beforeEnter="isTransitionInProgress = true"
					@afterLeave="isTransitionInProgress = false"
				>
					<template v-for="(departmentId, index) in structure.userDepartmentIds">
						<StructureView
							style="height: 100%;"
							v-if="index === this.index"
							:title="structure.title"
							:branch="makeDepartmentBranch(departmentId)"
							:key="departmentId"
							:userDepartmentId="departmentId"
							:headDictionary="structure.headDictionary"
							:user="user"
							data-test-id="usermp_structure-view"
						/>
					</template>
				</TransitionGroup>
			</div>
			<div v-if="hasManyUserDepartments"
				class="intranet-user-mini-profile__structure-view-control"
				data-test-id="usermp_structure-view-control"
			>
				<Button 
					:leftIcon="buttonIcon.CHEVRON_LEFT_S"
					:style="buttonStyle.PLAIN_NO_ACCENT"
					:size="buttonSize.EXTRA_SMALL"
					:removeRightCorners="true"
					:disabled="isPrevDisabled"
					@click="prev"
				/>
				<Button 
					:leftIcon="buttonIcon.CHEVRON_RIGHT_S"
					:style="buttonStyle.PLAIN_NO_ACCENT"
					:size="buttonSize.EXTRA_SMALL"
					:removeLeftCorners="true"
					:disabled="isNextDisabled"
					@click="next"
				/>
			</div>
		</div>
	`
	};

	// @vue/component
	const CollapseTransition = {
	  name: 'CollapseTransition',
	  props: {
	    initialHeight: {
	      type: Number,
	      default: 0
	    }
	  },
	  emits: ['start', 'end'],
	  created() {
	    if (!this.$slots.default) {
	      throw new Error('Slot is required');
	    }
	  },
	  methods: {
	    onEnter(el) {
	      this.targetWidth = el.offsetWidth;
	      this.targetHeight = Math.max(el.offsetHeight, this.initialHeight);
	      const fromHeight = Math.min(el.offsetHeight, this.initialHeight);
	      main_core.Dom.style(el, {
	        width: 0,
	        height: `${fromHeight}px`
	      });
	      requestAnimationFrame(() => {
	        main_core.Dom.style(el, {
	          width: `${this.targetWidth}px`,
	          height: `${this.targetHeight}px`
	        });
	      });
	    },
	    onBeforeLeave(el) {
	      const minHeight = Math.min(this.initialHeight, el.offsetHeight);
	      main_core.Dom.style(el, {
	        width: `${el.offsetWidth}px`,
	        height: `${el.offsetHeight}px`
	      });
	      requestAnimationFrame(() => {
	        main_core.Dom.style(el, {
	          width: 0,
	          height: `${minHeight}px`
	        });
	      });
	      this.$emit('start');
	    }
	  },
	  template: `
		<Transition
			name="intranet-user-mini-profile-collapse"
			@enter="onEnter"
			@beforeLeave="onBeforeLeave"
			@afterEnter="this.$emit('end')"
			@afterLeave="this.$emit('end')"
			@beforeEnter="this.$emit('start')"
		>
			<slot></slot>
		</Transition>
	`
	};

	class ChatService {
	  static openMessenger(userId) {
	    var _top$BX$Messenger$Pub;
	    (_top$BX$Messenger$Pub = top.BX.Messenger.Public) == null ? void 0 : _top$BX$Messenger$Pub.openChat(String(userId));
	  }
	  static call(userId, withVideo) {
	    var _top$BX$Messenger$Pub2;
	    (_top$BX$Messenger$Pub2 = top.BX.Messenger.Public) == null ? void 0 : _top$BX$Messenger$Pub2.startVideoCall(String(userId), withVideo);
	  }
	  static isMessengerAvailable() {
	    return Boolean(top.BX.Messenger.Public);
	  }
	}

	const UserRoleTitleByCode = {
	  shop: main_core.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ROLE_SHOP'),
	  email: main_core.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ROLE_EMAIL'),
	  integrator: main_core.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ROLE_INTEGRATOR'),
	  visitor: main_core.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ROLE_VISITOR')
	};

	// @vue/component
	const UserRole = {
	  name: 'UserRole',
	  mixins: [LocMixin],
	  props: {
	    role: {
	      type: [String, null],
	      required: true
	    }
	  },
	  computed: {
	    title() {
	      var _UserRoleTitleByCode$;
	      if (!this.role) {
	        return null;
	      }
	      return (_UserRoleTitleByCode$ = UserRoleTitleByCode[this.role]) != null ? _UserRoleTitleByCode$ : null;
	    }
	  },
	  template: `
		<div v-if="title"
			class="intranet-user-mini-profile__role"
			data-test-id="usermp_role-title"
		>
			<div class="intranet-user-mini-profile__role-inner-text">
				{{ title }}
			</div>
		</div>
	`
	};

	const IconSettingByStatus = {
	  online: {
	    iconName: ui_iconSet_api_core.Outline.EARTH_WITH_CHECK,
	    colorVar: '--ui-color-accent-main-success'
	  },
	  offline: {
	    iconName: ui_iconSet_api_core.Outline.EARTH_WITH_CLOCK,
	    colorVar: '--ui-color-accent-main-warning'
	  },
	  dnd: {
	    iconName: ui_iconSet_api_core.Outline.EARTH_WITH_STOP,
	    colorVar: '--ui-color-accent-main-alert'
	  },
	  vacation: {
	    iconName: ui_iconSet_api_core.Outline.EARTH_WITH_TREE,
	    colorVar: '--ui-color-accent-extra-aqua'
	  },
	  fired: {
	    iconName: ui_iconSet_api_core.Outline.EARTH_WITH_CROSS,
	    colorVar: '--ui-color-gray-50'
	  }
	};

	const UserStatus = Object.freeze({
	  Online: 'online',
	  Offline: 'offline',
	  DoNotDisturb: 'dnd',
	  Vacation: 'vacation',
	  Fired: 'fired'
	});

	class StatusService {
	  static isSupported(statusCode) {
	    return Object.values(UserStatus).includes(statusCode);
	  }
	  static getFailoverStatus() {
	    return UserStatus.Offline;
	  }
	}

	// @vue/component
	const UserStatusIcon = {
	  name: 'UserStatusIcon',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  mixins: [IconSetMixin],
	  props: {
	    status: {
	      /** @type UserStatusCodeType */
	      type: String,
	      default: 'offline'
	    }
	  },
	  computed: {
	    iconSetting() {
	      var _IconSettingByStatus$;
	      let status = this.status;
	      if (!main_core.Type.isStringFilled(status)) {
	        return null;
	      }
	      if (!StatusService.isSupported(status)) {
	        status = StatusService.getFailoverStatus();
	      }
	      return (_IconSettingByStatus$ = IconSettingByStatus[status]) != null ? _IconSettingByStatus$ : null;
	    }
	  },
	  template: `
		<div v-if="iconSetting"
			class="intranet-user-mini-profile__user-status" 
			:style="{ '--ui-icon-set__icon-color': 'var(' + iconSetting.colorVar + ')' }"
			data-test-id="usermp_status-icon"
		>
			<BIcon
				:size="24"
				:name="iconSetting.iconName"
			/>
		</div>
	`
	};

	const StaticDescriptionByStatus = {
	  online: main_core.Loc.getMessage('INTRANET_USER_MINI_PROFILE_USER_STATUS_ONLINE'),
	  dnd: main_core.Loc.getMessage('INTRANET_USER_MINI_PROFILE_USER_STATUS_DND'),
	  fired: main_core.Loc.getMessage('INTRANET_USER_MINI_PROFILE_USER_STATUS_FIRED')
	};

	// @vue/component
	const UserStatusDescription = {
	  name: 'UserStatusDescription',
	  mixins: [LocMixin],
	  props: {
	    status: {
	      /** @type UserStatusType */
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    text() {
	      var _StaticDescriptionByS;
	      let {
	        code
	      } = this.status;
	      if (!main_core.Type.isStringFilled(code)) {
	        return '';
	      }
	      if (!StatusService.isSupported(code)) {
	        code = StatusService.getFailoverStatus();
	      }
	      const staticText = (_StaticDescriptionByS = StaticDescriptionByStatus[code]) != null ? _StaticDescriptionByS : null;
	      if (staticText) {
	        return staticText;
	      }
	      if (code === UserStatus.Offline) {
	        return this.formatTextForOfflineStatus({
	          ...this.status,
	          code
	        });
	      }
	      if (code === UserStatus.Vacation) {
	        return this.formatTextForVacationStatus({
	          ...this.status,
	          code
	        });
	      }
	      return '';
	    }
	  },
	  methods: {
	    formatTextForOfflineStatus(status) {
	      if (!main_core.Type.isNumber(status.lastSeenTs) || status.lastSeenTs === 0) {
	        return this.loc('INTRANET_USER_MINI_PROFILE_USER_STATUS_OFFLINE');
	      }
	      const dayMonthFormat = main_date.DateTimeFormat.getFormat('DAY_MONTH_FORMAT');
	      const shortTimeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      return this.loc('INTRANET_USER_MINI_PROFILE_USER_STATUS_OFFLINE_LAST_SEEN_TEMPLATE', {
	        '#DATE#': main_date.DateTimeFormat.format(dayMonthFormat, status.lastSeenTs),
	        '#TIME#': main_date.DateTimeFormat.format(shortTimeFormat, status.lastSeenTs)
	      });
	    },
	    formatTextForVacationStatus(status) {
	      if (!main_core.Type.isNumber(status.vacationTs)) {
	        return this.loc('INTRANET_USER_MINI_PROFILE_USER_STATUS_VACATION');
	      }
	      const dayMonthFormat = main_date.DateTimeFormat.getFormat('DAY_MONTH_FORMAT');
	      return this.loc('INTRANET_USER_MINI_PROFILE_USER_STATUS_VACATION_TEMPLATE', {
	        '#DATE#': main_date.DateTimeFormat.format(dayMonthFormat, status.vacationTs)
	      });
	    }
	  },
	  template: `
		<span v-if="text"
			class="intranet-user-mini-profile__user-status-description"
			data-test-id="usermp_status-description-text"
		>
			{{ text }}
		</span>
	`
	};

	// @vue/component
	const UserTime = {
	  name: 'UserTime',
	  mixins: [LocMixin],
	  props: {
	    utcOffset: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      date: new Date(),
	      tickInterval: null
	    };
	  },
	  computed: {
	    formattedTime() {
	      const date = this.date;
	      const localOffset = date.getTimezoneOffset() * 60 * 1000;
	      const targetOffset = this.utcOffset * 1000;
	      const totalOffset = localOffset + targetOffset;
	      date.setTime(date.getTime() + totalOffset);
	      const sign = this.utcOffset >= 0 ? '+' : '-';
	      const absOffset = Math.abs(this.utcOffset);
	      const hours = Math.floor(absOffset / 3600);
	      const minutes = Math.floor(absOffset % 3600 / 60);
	      const timezoneParts = [this.loc('INTRANET_USER_MINI_PROFILE_USER_TZ_TEMPLATE', {
	        '#VALUE#': `${sign}${hours.toString()}`
	      })];
	      if (minutes > 0) {
	        timezoneParts.push(`:${minutes.toString().padStart(2, 0)}`);
	      }
	      return `${timezoneParts.join('')} (${this.formatDate(date)})`;
	    }
	  },
	  created() {
	    this.tickInterval = setInterval(() => {
	      this.date = new Date();
	    }, 1000);
	  },
	  unmounted() {
	    clearInterval(this.tickInterval);
	  },
	  methods: {
	    formatDate(date) {
	      const template = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      return main_date.DateTimeFormat.format(template, date);
	    }
	  },
	  template: `
		<span class="intranet-user-mini-profile__user-time" data-test-id="usermp_user-time-display">
			{{ formattedTime }}
		</span>
	`
	};

	const UserAvatarTypeByRole = Object.freeze({
	  collaber: 'round-guest',
	  extranet: 'round-extranet',
	  employee: 'round'
	});

	// @vue/component
	const UserBaseInfo = {
	  name: 'UserBaseInfo',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UserRole,
	    UserStatusIcon,
	    UserStatusDescription,
	    RichMenuPopup: ui_vue3_components_richMenu.RichMenuPopup,
	    UserTime,
	    Avatar: ui_vue3_components_avatar.Avatar,
	    BMenu: ui_vue3_components_menu.BMenu
	  },
	  mixins: [LocMixin, IconSetMixin],
	  props: {
	    isShowExpand: {
	      type: Boolean,
	      default: false
	    },
	    isExpanded: {
	      type: Boolean,
	      required: true
	    },
	    userId: {
	      type: Number,
	      required: true
	    },
	    info: {
	      /** @type UserMiniProfileData['baseInfo'] */
	      type: Object,
	      required: true
	    },
	    canChat: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['expand'],
	  data() {
	    return {
	      isShowCallMenu: false
	    };
	  },
	  computed: {
	    callMenuPopupOptions() {
	      return {
	        id: `${PopupPrefixId}call-menu`,
	        autoHide: true,
	        bindElement: this.$refs.callActionMenu,
	        bindOptions: {
	          forceBindPosition: true
	        },
	        minWidth: 190,
	        width: 190,
	        items: [{
	          title: this.loc('INTRANET_USER_MINI_PROFILE_ACTION_CALL_WITH_VIDEO'),
	          icon: this.outlineSet.RECORD_VIDEO,
	          onClick: () => this.onCallMenuItemClick()
	        }, {
	          title: this.loc('INTRANET_USER_MINI_PROFILE_ACTION_CALL'),
	          icon: this.outlineSet.HEADSET,
	          onClick: () => this.onCallMenuItemClick(false)
	        }]
	      };
	    },
	    shouldShowUserTime() {
	      return [UserStatus.Online, UserStatus.DoNotDisturb].includes(this.info.status.code);
	    },
	    shouldShowMessengerActionButtons() {
	      return ChatService.isMessengerAvailable() && this.canChat;
	    },
	    avatarType() {
	      var _UserAvatarTypeByRole;
	      return (_UserAvatarTypeByRole = UserAvatarTypeByRole[this.info.role]) != null ? _UserAvatarTypeByRole : 'round';
	    }
	  },
	  methods: {
	    openChat() {
	      ChatService.openMessenger(this.userId);
	    },
	    call(withVideo = true) {
	      ChatService.call(this.userId, withVideo);
	    },
	    onCallMenuItemClick(withVideo = true) {
	      this.isShowCallMenu = false;
	      this.call(withVideo);
	    },
	    openProfile() {
	      OpenActionService.openUserProfile(this.info.url);
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile__base-info">
			<div class="intranet-user-mini-profile__base-info__user">
				<div class="intranet-user-mini-profile__base-info__user-avatar-wrapper">
					<div 
						class="intranet-user-mini-profile__base-info__user-avatar"
						data-test-id="usermp_avatar"
						@click="openProfile"
					>
						<Avatar 
							:type="avatarType"
							:options="{
								userName: info.name, 
								size: 72, 
								title: info.name, 
								picPath: info.avatar ? encodeURI(info.avatar) : undefined,
							}"
						/>
					</div>
					<UserStatusIcon v-if="info.status" 
						:status="info.status.code"
						data-test-id="usermp_status"
					/>
				</div>
				<div class="intranet-user-mini-profile__base-info__user-data">
					<div class="intranet-user-mini-profile__base-info__user-data__name"
						 :title="info.name"
						 data-test-id="usermp_name"
						 @click="openProfile"
					>
						{{ info.name }}
					</div>
					<div class="intranet-user-mini-profile__base-info__user-data__position"
						 :title="info.workPosition"
						 data-test-id="usermp_position"
					>
						{{ info.workPosition }}
					</div>
					<div class="intranet-user-mini-profile__base-info__user-data__status">
						<UserStatusDescription v-if="info.status"
							:status="info.status"
							data-test-id="usermp_status-description"
						/>
						<UserTime v-if="shouldShowUserTime" 
							:utcOffset="info.utcOffset"
							data-test-id="usermp_user-time"
						/>
					</div>
				</div>
			</div>
			<div v-if="shouldShowMessengerActionButtons"
				class="intranet-user-mini-profile__base-info__actions"
			>
				<div class="intranet-user-mini-profile__base-info__action">
					<button
						class="ui-btn ui-btn-sm ui-btn-no-caps --air --wide --style-outline-accent-2"
						data-test-id="usermp_chat-button"
						@click="openChat"
					>
					<span class="ui-btn-text">
						{{ loc('INTRANET_USER_MINI_PROFILE_ACTION_CHAT') }}
					</span>
					</button>
				</div>
				<div class="intranet-user-mini-profile__base-info__action">
					<div class="ui-btn-split --air ui-btn-sm --style-filled ui-btn-no-caps">
						<button
							class="ui-btn-main --air"
							data-test-id="usermp_call-video-button"
							@click="call()"
						>
							<span class="ui-btn-text">
								{{ loc('INTRANET_USER_MINI_PROFILE_ACTION_CALL_WITH_VIDEO') }}
							</span>
						</button>
						<button
							ref="callActionMenu"
							class="ui-btn-menu"
							data-test-id="usermp_call-menu-button"
							@click="isShowCallMenu = !isShowCallMenu"
						>
							<BMenu v-if="isShowCallMenu"
								:options="callMenuPopupOptions"
								@close="isShowCallMenu = false"
							/>
						</button>
					</div>
				</div>
			</div>
			<div v-if="isShowExpand"
				class="intranet-user-mini-profile__expand"
				data-test-id="usermp_expand-button"
				@click="() => $emit('expand')"
			>
				<BIcon :name="!isExpanded ? outlineSet.OPEN_CHAT : outlineSet.CLOSE_CHAT"/>
			</div>
			<div class="intranet-user-mini-profile__base-info__role">
				<UserRole 
					:role="info.role"
					data-test-id="usermp_role"
				/>
			</div>
		</div>
	`
	};

	const ContactItem = Object.freeze({
	  Mail: 'mail',
	  Phone: 'phone'
	});

	// @vue/component
	const UserDetailedInfoContactItemValue = {
	  name: 'UserDetailedInfoContactItemValue',
	  props: {
	    type: {
	      /** @type ContactItemType */
	      type: String,
	      required: true
	    },
	    value: {
	      type: String,
	      required: true
	    }
	  },
	  computed: {
	    href() {
	      if (this.type === ContactItem.Mail) {
	        return `mailto:${this.value}`;
	      }
	      return null;
	    }
	  },
	  methods: {
	    onClick(event) {
	      if (this.type === ContactItem.Phone) {
	        event.preventDefault();
	        if (navigator.clipboard) {
	          navigator.clipboard.writeText(this.value).then(() => {
	            ui_notification.UI.Notification.Center.notify({
	              content: this.$Bitrix.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ACTION_CLIPBOARD_COPY_PHONE_SUCCESS')
	            });
	          }).catch(() => {
	            ui_notification.UI.Notification.Center.notify({
	              content: this.$Bitrix.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ACTION_CLIPBOARD_COPY_PHONE_ERROR')
	            });
	          });
	        } else {
	          ui_notification.UI.Notification.Center.notify({
	            content: this.$Bitrix.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ACTION_CLIPBOARD_COPY_PHONE_ERROR')
	          });
	        }
	      }
	    }
	  },
	  template: `
		<a 
			class="intranet-user-mini-profile__detailed-info__contact-item-value"
			:href="href"
			target
			:data-test-id="'usermp_contact-' + type"
			@click="onClick"
		> 
			{{ value }}
		</a>
	`
	};

	// @vue/component
	const UserDetailedInfoItem = {
	  name: 'UserDetailedInfoItem',
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    type: {
	      type: String,
	      required: false,
	      default: 'general'
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile__detailed-info-item" :data-test-id="'usermp_detailed-info-' + type">
			<div class="intranet-user-mini-profile__detailed-info-item__title" :data-test-id="'usermp_detailed-info-' + type + '-title'">
				{{ title }}
			</div>
			<div class="intranet-user-mini-profile__detailed-info-item__value" :data-test-id="'usermp_detailed-info-' + type + '-value'">
				<slot></slot>
			</div>
		</div>
	`
	};

	// eslint-disable-next-line no-unused-vars

	// @vue/component
	const EntityMenuItem = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    Avatar: ui_vue3_components_avatar.Avatar
	  },
	  mixins: [IconSetMixin],
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    image: {
	      /** @type ImageProp */
	      type: Object,
	      default: () => ({})
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile__entity-menu-item" data-test-id="usermp_entity-menu-item">
			<div class="intranet-user-mini-profile__entity-menu-item-content">
				<div v-if="image"
					class="intranet-user-mini-profile__entity-menu-item__icon"
					:class="image.iconClass ?? null"
					data-test-id="usermp_entity-menu-item-icon"
				>
					<BIcon v-if="image.bIconName" 
						:name="image.bIconName"
						:size="18"
					/>
					<Avatar v-else
						:options="{
							size: 24,
							title,
							picPath: image.imageSrc ? encodeURI(image.imageSrc) : undefined,
						}"
					/>
				</div>
				<div 
					class="intranet-user-mini-profile__entity-menu-item__title"
					:title="title"
					data-test-id="usermp_entity-menu-item-title"
				>
					{{ title }}
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const UserDetailedInfoEntityListValue = {
	  name: 'UserDetailedInfoEntityListValue',
	  components: {
	    RichMenuPopup: ui_vue3_components_richMenu.RichMenuPopup,
	    EntityMenuItem
	  },
	  props: {
	    items: {
	      type: Array,
	      required: true
	    },
	    entityType: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  emits: ['click'],
	  data() {
	    return {
	      isMenuShow: false
	    };
	  },
	  computed: {
	    popupOptions() {
	      const formatPopupId = type => {
	        return main_core.Type.isStringFilled(type) ? `${PopupPrefixId}entity-list-${type}` : undefined;
	      };
	      return {
	        id: formatPopupId(this.entityType),
	        bindElement: this.$refs.counter,
	        width: 240,
	        maxHeight: 270,
	        autoHide: true
	      };
	    },
	    firstItem() {
	      var _this$items$;
	      return (_this$items$ = this.items[0]) != null ? _this$items$ : null;
	    },
	    isCounterShow() {
	      return this.items.length > 1;
	    },
	    counterTitle() {
	      return this.items.length - 1;
	    }
	  },
	  methods: {
	    openMenu() {
	      this.isMenuShow = true;
	    },
	    onElementClick(id) {
	      this.$emit('click', id);
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile__user-detailed-info__list-value" v-if="items.length" :data-test-id="'usermp_entity-list-' + entityType">
			<div class="intranet-user-mini-profile__user-detailed-info__list-value__element-container">
					<div v-if="this.$slots.default"
						class="intranet-user-mini-profile__user-detailed-info__list-value__before-element"
					>
						<slot 
							:item="firstItem"
						>
						</slot>
					</div>
				<div class="intranet-user-mini-profile__user-detailed-info__list-value__element">
					<a
						class="intranet-user-mini-profile__user-detailed-info__list-value__element-text"
						:title="firstItem.title"
						:data-test-id="'usermp_entity-' + entityType + '-item'"
						@click="onElementClick(firstItem.id)"
					>
						{{ firstItem.title }}
					</a>
					<div v-if="isCounterShow"
						 class="intranet-user-mini-profile__user-detailed-info__list-value__counter ui-counter"
						 ref="counter"
						 data-test-id="usermp_entity-counter"
						 @click="openMenu"
					>
						<div class="ui-counter-inner">
							+{{ counterTitle }}
						</div>
					</div>
				</div>
			</div>
			<RichMenuPopup v-if="isMenuShow"
				class="intranet-user-mini-profile__user-detailed-info__list-value__entity-menu"
				:popup-options="popupOptions"
				@close="isMenuShow = false"
			>
				<EntityMenuItem v-for="item in items"
					:title="item.title"
					:image="item.image"
					:data-test-id="'usermp_entity-' + entityType + '-menu-item'"
					@click="onElementClick(item.id)"
				/>
			</RichMenuPopup>
		</div>
	`
	};

	// @vue/component
	const UserDetailedInfo = {
	  name: 'UserDetailedInfo',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UserDetailedInfoItem,
	    UserDetailedInfoContactItemValue,
	    UserDetailedInfoEntityListValue
	  },
	  mixins: [LocMixin, IconSetMixin],
	  props: {
	    info: {
	      /** @type UserMiniProfileData['detailInfo'] */
	      type: Object,
	      required: true
	    },
	    heads: {
	      /** @type Array<UserInfo> */
	      type: Array,
	      default: () => []
	    },
	    userDepartments: {
	      /** @type Array<DepartmentType> */
	      type: Array,
	      default: () => []
	    },
	    departments: {
	      /** @type Array<DepartmentType> */
	      type: Array,
	      default: () => []
	    },
	    teams: {
	      /** @type Array<TeamType> */
	      type: Array,
	      default: () => []
	    }
	  },
	  computed: {
	    departmentItems() {
	      return this.userDepartments.map(department => ({
	        id: department.id,
	        title: department.title,
	        parentId: department.parentId,
	        image: {
	          bIconName: this.outlineSet.GROUP,
	          iconClass: '--department'
	        }
	      }));
	    },
	    headItems() {
	      return this.heads.map(head => ({
	        id: head.id,
	        title: head.name,
	        image: {
	          imageSrc: head.avatar
	        },
	        href: head.url
	      }));
	    },
	    teamItems() {
	      return this.teams.map(team => ({
	        id: team.id,
	        title: team.title,
	        image: {
	          bIconName: this.outlineSet.MY_PLAN,
	          iconClass: '--team'
	        }
	      }));
	    },
	    headTitle() {
	      return this.headItems.length < 2 ? this.loc('INTRANET_USER_MINI_DETAILED_INFO_HEAD') : this.loc('INTRANET_USER_MINI_DETAILED_INFO_HEAD_MULTIPLE');
	    },
	    teamTitle() {
	      return this.teamItems.length < 2 ? this.loc('INTRANET_USER_MINI_DETAILED_INFO_FC_SINGLE') : this.loc('INTRANET_USER_MINI_DETAILED_INFO_FC');
	    },
	    departmentTitle() {
	      return this.departmentItems.length < 2 ? this.loc('INTRANET_USER_MINI_DETAILED_INFO_DEPARTMENT') : this.loc('INTRANET_USER_MINI_DETAILED_INFO_DEPARTMENT_MULTIPLE');
	    }
	  },
	  methods: {
	    onHeadClicked(id) {
	      const head = this.heads.find(item => item.id === id);
	      if (head != null && head.url) {
	        OpenActionService.openUserProfile(head.url);
	      }
	    },
	    onStructureNodeClicked(id) {
	      OpenActionService.openStructureNodeId(id);
	    },
	    getParentDepartmentById(id) {
	      return this.departments.find(item => item.id === id);
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile__detailed-info">
			<UserDetailedInfoItem v-if="info.personalMobile"
				:title="loc('INTRANET_USER_MINI_DETAILED_INFO_PERSONAL_MOBILE')"
				type="personal-mobile"
			>
				<UserDetailedInfoContactItemValue :value="info.personalMobile" type="phone"/>
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="info.innerPhone"
				:title="loc('INTRANET_USER_MINI_DETAILED_INFO_PHONE_INNER')"
				type="inner-phone"
			>
				{{ info.innerPhone }}
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="info.email"
				:title="loc('INTRANET_USER_MINI_DETAILED_INFO_EMAIL')"
				type="email"
			>
				<UserDetailedInfoContactItemValue :value="info.email" type="mail"/>
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="headItems.length"
				:title="headTitle"
				type="head"
			>
				<UserDetailedInfoEntityListValue
					entityType="user"
					:items="headItems"
					@click="(id) => onHeadClicked(id)"
				/>
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="departmentItems.length"
				:title="departmentTitle"
				type="department"
			>
				<UserDetailedInfoEntityListValue 
					:items="departmentItems"
					entityType="department"
					@click="(id) => onStructureNodeClicked(id)"
					v-slot="{ item }"
				>
					<template v-if="getParentDepartmentById(item.parentId)">
						<div class="intranet-user-mini-profile__detailed-info-item__parent-department">
							<div class="intranet-user-mini-profile__detailed-info-item__parent-department-text"
								 :title="getParentDepartmentById(item.parentId)?.title"
								 data-test-id="usermp_parent-department"
							>
								{{ getParentDepartmentById(item.parentId)?.title }}
							</div>
							<div class="intranet-user-mini-profile__detailed-info-item__parent-department-arrow">
								<div class="intranet-user-mini-profile__detailed-info-item__parent-department-arrow-icon">
									<BIcon :name="outlineSet.CHEVRON_RIGHT_L" :size="16"/>
								</div>
							</div>
						</div>
					</template>
				</UserDetailedInfoEntityListValue>
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="teamItems.length"
				:title="teamTitle"
				type="team"
			>
				<UserDetailedInfoEntityListValue 
					:items="teamItems"
					entityType="team"
					@click="(id) => onStructureNodeClicked(id)"
				/>
			</UserDetailedInfoItem>
		</div>
	`
	};

	// @vue/component
	const UserMiniProfileComponent = {
	  name: 'UserMiniProfile',
	  components: {
	    UserMiniProfileLoader,
	    UserBaseInfo,
	    UserDetailedInfo,
	    Divider,
	    StructureViewList,
	    CollapseTransition,
	    ErrorState,
	    LoaderTransition
	  },
	  props: {
	    popup: {
	      /** @type Popup */
	      type: Object,
	      required: true
	    },
	    userId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      isError: false,
	      errorType: ErrorStateDict.Default,
	      isLoaded: false,
	      isLoading: false,
	      isExpanded: true,
	      isExpandBlocked: false,
	      backendData: null
	    };
	  },
	  computed: {
	    userDepartments() {
	      var _this$backendData$str, _this$backendData$str2, _this$backendData$str3, _this$backendData$str4;
	      const userDepartmentIds = (_this$backendData$str = (_this$backendData$str2 = this.backendData.structure) == null ? void 0 : _this$backendData$str2.userDepartmentIds) != null ? _this$backendData$str : [];
	      const departmentDictionary = (_this$backendData$str3 = (_this$backendData$str4 = this.backendData.structure) == null ? void 0 : _this$backendData$str4.departmentDictionary) != null ? _this$backendData$str3 : [];
	      const userDepartments = [];
	      userDepartmentIds.forEach(id => {
	        const department = departmentDictionary[id];
	        if (!department) {
	          return;
	        }
	        userDepartments.push(department);
	      });
	      return userDepartments;
	    },
	    departments() {
	      var _this$backendData$str5, _this$backendData$str6;
	      return Object.values((_this$backendData$str5 = (_this$backendData$str6 = this.backendData.structure) == null ? void 0 : _this$backendData$str6.departmentDictionary) != null ? _this$backendData$str5 : {});
	    },
	    heads() {
	      var _this$backendData$str7, _this$backendData$str8, _this$backendData$str9, _this$backendData$str10, _this$backendData$str11, _this$backendData$str12;
	      const userDepartmentIds = (_this$backendData$str7 = (_this$backendData$str8 = this.backendData.structure) == null ? void 0 : _this$backendData$str8.userDepartmentIds) != null ? _this$backendData$str7 : [];
	      const departmentDictionary = (_this$backendData$str9 = (_this$backendData$str10 = this.backendData.structure) == null ? void 0 : _this$backendData$str10.departmentDictionary) != null ? _this$backendData$str9 : [];
	      const headDictionary = (_this$backendData$str11 = (_this$backendData$str12 = this.backendData.structure) == null ? void 0 : _this$backendData$str12.headDictionary) != null ? _this$backendData$str11 : [];
	      const headList = [];
	      const headIds = new Set();
	      userDepartmentIds.forEach(departmentId => {
	        /** @type DepartmentType | null */
	        const department = departmentDictionary[departmentId];
	        if (!department) {
	          return;
	        }
	        const {
	          headIds: departmentHeadIds
	        } = department;
	        if (departmentHeadIds.includes(this.userId)) {
	          return;
	        }
	        departmentHeadIds.forEach(headId => {
	          if (!headIds.has(headId) && headDictionary[headId]) {
	            headList.push(headDictionary[headId]);
	            headIds.add(headId);
	          }
	        });
	      });
	      return headList.filter(head => head.id !== this.userId);
	    },
	    canShowDepartments() {
	      return this.userDepartments.length > 0;
	    },
	    isShouldBeExpandedByInitial() {
	      return InitialParamService.getValue(InitialParamDict.RightSideExpand) === 'Y';
	    },
	    canChat() {
	      var _this$backendData$acc, _this$backendData;
	      return (_this$backendData$acc = (_this$backendData = this.backendData) == null ? void 0 : _this$backendData.access.canChat) != null ? _this$backendData$acc : false;
	    },
	    isShowStructure() {
	      return this.canShowDepartments && this.isExpanded;
	    }
	  },
	  created() {
	    if (!this.isLoaded) {
	      this.isLoading = true;
	      void Backend.load(this.userId).then(data => {
	        this.backendData = data;
	        this.isLoaded = true;
	      }).catch(errorCode => {
	        if (errorCode === 'ACCESS_DENIED') {
	          this.errorType = ErrorStateDict.AccessDenied;
	        }
	        this.isError = true;
	      }).finally(() => {
	        this.isLoading = false;
	        this.adjustPopup();
	      });
	    }
	    this.isExpanded = this.isShouldBeExpandedByInitial;
	  },
	  mounted() {
	    this.adjustPopup();
	  },
	  methods: {
	    onExpand() {
	      if (this.isExpandBlocked) {
	        return;
	      }
	      this.isExpanded = !this.isExpanded;
	      InitialParamService.save(InitialParamDict.RightSideExpand, this.isExpanded ? 'Y' : 'N');
	    },
	    adjustPopup() {
	      var _this$popup;
	      (_this$popup = this.popup) == null ? void 0 : _this$popup.adjustPosition();
	    },
	    onCollapseStart() {
	      this.isExpandBlocked = true;
	      this.adjustPopup();
	    },
	    onCollapseEnd() {
	      this.isExpandBlocked = false;
	      this.adjustPopup();
	    },
	    getUserData() {
	      const {
	        avatar,
	        name,
	        workPosition,
	        url
	      } = this.backendData.baseInfo;
	      return {
	        id: this.userId,
	        avatar,
	        name,
	        workPosition,
	        url
	      };
	    }
	  },
	  template: `
		<div class="intranet-user-mini-profile-wrapper">
			<template v-if="!isError">
				<LoaderTransition 
					:isLoading="isLoading" 
					:isShowContent="!!backendData"
					:isLoaderShort="!isShouldBeExpandedByInitial"
					@end="() => this.adjustPopup()"
				>
					<div class="intranet-user-mini-profile-wrapper__content">
						<div class="intranet-user-mini-profile-wrapper__column --left"
							 ref="leftColumn"
						>
							<UserBaseInfo
								:userId="userId"
								:info="backendData.baseInfo"
								:isShowExpand="canShowDepartments"
								:isExpanded="isExpanded"
								:canChat="canChat"
								@expand="onExpand"
							/>
							<template v-if="backendData.detailInfo">
								<Divider style="margin-top: 18px; margin-bottom: 14px"/>
								<UserDetailedInfo
								  :info="backendData.detailInfo"
								  :userDepartments="userDepartments"
								  :departments="departments"
								  :heads="heads"
								  :teams="backendData.structure.teams"
								/>
							</template>
						</div>
						<CollapseTransition
							@start="onCollapseStart"
							@end="onCollapseEnd"
							:initialHeight="$refs.leftColumn?.offsetHeight"
						>
							<div v-if="isShowStructure" 
								class="intranet-user-mini-profile-wrapper__content__right-wrapper"
								data-test-id="usermp_structure-wrapper"
							>
								<Divider isVertical style="margin: 0 18px"/>
								<div class="intranet-user-mini-profile-wrapper__column --right" data-test-id="usermp_structure-column">
									<StructureViewList
										:structure="backendData.structure"
										:user="getUserData()"
										data-test-id="usermp_structure-view-list"
									/>
								</div>
							</div>
						</CollapseTransition>
					</div>
				</LoaderTransition>
			</template>
			<ErrorState v-if="isError"
				:type="errorType"
			/>
		</div>
	`
	};

	const ShowDelayMs = 1000;
	const CloseDelayMs = 500;
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _bindElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindElement");
	var _showOrCloseTimeout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showOrCloseTimeout");
	var _haveToCloseCheckInterval = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("haveToCloseCheckInterval");
	var _handler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handler");
	var _onBindElementClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBindElementClick");
	var _onMouseEnter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMouseEnter");
	var _onMouseLeave = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMouseLeave");
	var _haveToClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("haveToClose");
	var _isPopupOnTop = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPopupOnTop");
	var _scheduleClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scheduleClose");
	var _scheduleShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scheduleShow");
	var _getTrackingElements = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTrackingElements");
	class Tracking extends main_core_events.EventEmitter {
	  constructor(trackingOptions) {
	    super();
	    Object.defineProperty(this, _getTrackingElements, {
	      value: _getTrackingElements2
	    });
	    Object.defineProperty(this, _scheduleShow, {
	      value: _scheduleShow2
	    });
	    Object.defineProperty(this, _scheduleClose, {
	      value: _scheduleClose2
	    });
	    Object.defineProperty(this, _isPopupOnTop, {
	      value: _isPopupOnTop2
	    });
	    Object.defineProperty(this, _haveToClose, {
	      value: _haveToClose2
	    });
	    Object.defineProperty(this, _onMouseLeave, {
	      value: _onMouseLeave2
	    });
	    Object.defineProperty(this, _onMouseEnter, {
	      value: _onMouseEnter2
	    });
	    Object.defineProperty(this, _onBindElementClick, {
	      value: _onBindElementClick2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _bindElement, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _showOrCloseTimeout, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _haveToCloseCheckInterval, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _handler, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = trackingOptions.popup;
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement] = trackingOptions.bindElement;
	    babelHelpers.classPrivateFieldLooseBase(this, _handler)[_handler] = {
	      onMouseEnter: event => babelHelpers.classPrivateFieldLooseBase(this, _onMouseEnter)[_onMouseEnter](event),
	      onMouseLeave: event => babelHelpers.classPrivateFieldLooseBase(this, _onMouseLeave)[_onMouseLeave](event),
	      onBindElementClick: event => babelHelpers.classPrivateFieldLooseBase(this, _onBindElementClick)[_onBindElementClick](event)
	    };
	    this.setEventNamespace('Intranet.User.MiniProfile.Tracking');
	  }
	  setBindElement(element) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement] === element) {
	      return;
	    }
	    this.unbindTracking();
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement] = element;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement]) {
	      this.setupTracking();
	    }
	  }
	  setupTracking() {
	    const {
	      onMouseEnter,
	      onMouseLeave,
	      onBindElementClick
	    } = babelHelpers.classPrivateFieldLooseBase(this, _handler)[_handler];
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement], 'click', onBindElementClick);
	    babelHelpers.classPrivateFieldLooseBase(this, _getTrackingElements)[_getTrackingElements]().forEach(element => {
	      main_core.Event.bind(element, 'mouseenter', onMouseEnter);
	      main_core.Event.bind(element, 'mouseleave', onMouseLeave);
	    });
	  }
	  unbindTracking() {
	    const {
	      onMouseEnter,
	      onMouseLeave,
	      onBindElementClick
	    } = babelHelpers.classPrivateFieldLooseBase(this, _handler)[_handler];
	    main_core.Event.unbind(babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement], 'click', onBindElementClick);
	    babelHelpers.classPrivateFieldLooseBase(this, _getTrackingElements)[_getTrackingElements]().forEach(element => {
	      main_core.Event.unbind(element, 'mouseenter', onMouseEnter);
	      main_core.Event.unbind(element, 'mouseleave', onMouseLeave);
	    });
	    clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _showOrCloseTimeout)[_showOrCloseTimeout]);
	    clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _haveToCloseCheckInterval)[_haveToCloseCheckInterval]);
	  }
	}
	function _onBindElementClick2() {
	  clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _haveToCloseCheckInterval)[_haveToCloseCheckInterval]);
	  clearTimeout(babelHelpers.classPrivateFieldLooseBase(this, _showOrCloseTimeout)[_showOrCloseTimeout]);
	  this.emit('close');
	}
	function _onMouseEnter2(event) {
	  clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _haveToCloseCheckInterval)[_haveToCloseCheckInterval]);
	  babelHelpers.classPrivateFieldLooseBase(this, _scheduleShow)[_scheduleShow]();
	}
	function _onMouseLeave2(event) {
	  clearTimeout(babelHelpers.classPrivateFieldLooseBase(this, _showOrCloseTimeout)[_showOrCloseTimeout]);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _haveToClose)[_haveToClose]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _scheduleClose)[_scheduleClose]();
	  } else if (!babelHelpers.classPrivateFieldLooseBase(this, _isPopupOnTop)[_isPopupOnTop]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _haveToCloseCheckInterval)[_haveToCloseCheckInterval] = setInterval(() => {
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _haveToClose)[_haveToClose]()) {
	        return;
	      }
	      this.emit('close');
	      clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _haveToCloseCheckInterval)[_haveToCloseCheckInterval]);
	    }, CloseDelayMs * 2);
	  }
	}
	function _haveToClose2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].isShown() && babelHelpers.classPrivateFieldLooseBase(this, _isPopupOnTop)[_isPopupOnTop]()) {
	    return true;
	  }
	  return false;
	}
	function _isPopupOnTop2() {
	  const popupStack = main_popup.PopupManager.getPopups();
	  for (let i = popupStack.length - 1; i >= 0; --i) {
	    const popup = popupStack[i];
	    if (popup.getId() === babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].getId()) {
	      return true;
	    }
	    if (popup.isShown() && popup.getId().includes(PopupPrefixId)) {
	      return false;
	    }
	  }
	  return true;
	}
	function _scheduleClose2() {
	  clearTimeout(babelHelpers.classPrivateFieldLooseBase(this, _showOrCloseTimeout)[_showOrCloseTimeout]);
	  babelHelpers.classPrivateFieldLooseBase(this, _showOrCloseTimeout)[_showOrCloseTimeout] = setTimeout(() => {
	    this.emit('close');
	  }, CloseDelayMs);
	}
	function _scheduleShow2() {
	  clearTimeout(babelHelpers.classPrivateFieldLooseBase(this, _showOrCloseTimeout)[_showOrCloseTimeout]);
	  babelHelpers.classPrivateFieldLooseBase(this, _showOrCloseTimeout)[_showOrCloseTimeout] = setTimeout(() => {
	    this.emit('show');
	  }, ShowDelayMs);
	}
	function _getTrackingElements2() {
	  return [babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement], babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].getPopupContainer()];
	}

	let _ = t => t,
	  _t;
	const PopupPrefixId = 'intranet-user-mini-profile-';
	const AngleWidth = 33;
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _tracking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tracking");
	var _app = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("app");
	var _closeHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeHandler");
	var _getPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopup");
	var _getContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContainer");
	var _createAppIfNeed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createAppIfNeed");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	class UserMiniProfile {
	  constructor(options) {
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _createAppIfNeed, {
	      value: _createAppIfNeed2
	    });
	    Object.defineProperty(this, _getContainer, {
	      value: _getContainer2
	    });
	    Object.defineProperty(this, _getPopup, {
	      value: _getPopup2
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _tracking, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _app, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _closeHandler, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _tracking)[_tracking] = new Tracking({
	      popup: babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup](),
	      bindElement: options.bindElement
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _closeHandler)[_closeHandler] = () => this.close();
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	  }
	  destroy() {
	    var _babelHelpers$classPr;
	    babelHelpers.classPrivateFieldLooseBase(this, _tracking)[_tracking].unbindTracking();
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().destroy();
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _app)[_app]) == null ? void 0 : _babelHelpers$classPr.unmount();
	    main_core_events.EventEmitter.unsubscribe('SidePanel.Slider:onOpen', babelHelpers.classPrivateFieldLooseBase(this, _closeHandler)[_closeHandler]);
	    main_core_events.EventEmitter.unsubscribe('Intranet.User.MiniProfile:close', babelHelpers.classPrivateFieldLooseBase(this, _closeHandler)[_closeHandler]);
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _createAppIfNeed)[_createAppIfNeed]();
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().show();
	  }
	  close() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().close();
	    main_popup.PopupManager.getPopups().filter(popup => popup.isShown() && popup.getId().includes(PopupPrefixId)).forEach(popup => {
	      popup.close();
	    });
	  }
	  setBindElement(element) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].bindElement === element) {
	      return;
	    }
	    const popup = babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]();
	    popup.close();
	    popup.setBindElement(element);
	    babelHelpers.classPrivateFieldLooseBase(this, _tracking)[_tracking].setBindElement(element);
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].bindElement = element;
	  }
	  getBindElement() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].bindElement;
	  }
	}
	function _getPopup2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('popup', () => {
	    return new main_popup.Popup({
	      className: 'intranet-user-mini-profile-popup',
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getContainer)[_getContainer](),
	      targetContainer: document.body,
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].bindElement,
	      maxWidth: 643,
	      maxHeight: 497,
	      padding: 0,
	      contentNoPaddings: true,
	      angle: {
	        offset: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].bindElement.offsetWidth / 2 + AngleWidth / 2
	      },
	      animation: 'fading',
	      bindOptions: {
	        forceBindPosition: true,
	        forceTop: true
	      }
	    });
	  });
	}
	function _getContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('container', () => {
	    return main_core.Tag.render(_t || (_t = _`
				<div class="intranet-user-mini-profile --ui-context-content-light"></div>
			`));
	  });
	}
	function _createAppIfNeed2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _app)[_app]) {
	    return;
	  }
	  const {
	    userId
	  } = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options];
	  const popup = babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]();
	  babelHelpers.classPrivateFieldLooseBase(this, _app)[_app] = ui_vue3.BitrixVue.createApp(UserMiniProfileComponent, {
	    userId,
	    popup
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].mount(babelHelpers.classPrivateFieldLooseBase(this, _getContainer)[_getContainer]());
	}
	function _bindEvents2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _tracking)[_tracking].setupTracking();
	  babelHelpers.classPrivateFieldLooseBase(this, _tracking)[_tracking].subscribe('close', () => this.close());
	  babelHelpers.classPrivateFieldLooseBase(this, _tracking)[_tracking].subscribe('show', () => this.show());
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onOpen', babelHelpers.classPrivateFieldLooseBase(this, _closeHandler)[_closeHandler]);
	  main_core_events.EventEmitter.subscribe('Intranet.User.MiniProfile:close', babelHelpers.classPrivateFieldLooseBase(this, _closeHandler)[_closeHandler]);
	}

	var _instanceByIdMap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instanceByIdMap");
	var _instanceByBindElementMap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instanceByBindElementMap");
	class UserMiniProfileManager {
	  static getById(id) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _instanceByIdMap)[_instanceByIdMap].get(id);
	  }
	  static create(options) {
	    const {
	      id,
	      bindElement
	    } = options;

	    // If other widget was already binded to element, we need to unbind it
	    if (babelHelpers.classPrivateFieldLooseBase(this, _instanceByBindElementMap)[_instanceByBindElementMap].has(bindElement)) {
	      const instanceByElement = babelHelpers.classPrivateFieldLooseBase(this, _instanceByBindElementMap)[_instanceByBindElementMap].get(bindElement);
	      const instanceById = babelHelpers.classPrivateFieldLooseBase(this, _instanceByIdMap)[_instanceByIdMap].get(id);
	      if (instanceById !== instanceByElement) {
	        instanceByElement.setBindElement(null);
	        babelHelpers.classPrivateFieldLooseBase(this, _instanceByBindElementMap)[_instanceByBindElementMap].delete(bindElement);
	      }
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _instanceByIdMap)[_instanceByIdMap].has(id)) {
	      const instance = babelHelpers.classPrivateFieldLooseBase(this, _instanceByIdMap)[_instanceByIdMap].get(id);
	      const previousBindElement = instance.getBindElement();
	      if (previousBindElement !== bindElement) {
	        babelHelpers.classPrivateFieldLooseBase(this, _instanceByBindElementMap)[_instanceByBindElementMap].delete(previousBindElement);
	        instance.setBindElement(bindElement);
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _instanceByBindElementMap)[_instanceByBindElementMap].set(bindElement, instance);
	      return instance;
	    }
	    const instance = new UserMiniProfile(options);
	    babelHelpers.classPrivateFieldLooseBase(this, _instanceByIdMap)[_instanceByIdMap].set(id, instance);
	    babelHelpers.classPrivateFieldLooseBase(this, _instanceByBindElementMap)[_instanceByBindElementMap].set(bindElement, instance);
	    return instance;
	  }
	}
	Object.defineProperty(UserMiniProfileManager, _instanceByIdMap, {
	  writable: true,
	  value: new Map()
	});
	Object.defineProperty(UserMiniProfileManager, _instanceByBindElementMap, {
	  writable: true,
	  value: new Map()
	});

	exports.UserMiniProfileManager = UserMiniProfileManager;

}((this.BX.Intranet.User = this.BX.Intranet.User || {}),BX.Cache,BX.Vue3.Components,BX.Vue3,BX,BX.Humanresources.CompanyStructure,BX.UI.Vue3.Components,BX.UI.IconSet,BX.Main,BX,BX.UI.Vue3.Components,BX.UI.IconSet,BX.UI.Vue3.Components,BX.Event,BX,BX.Main));
//# sourceMappingURL=user-mini-profile.bundle.js.map
