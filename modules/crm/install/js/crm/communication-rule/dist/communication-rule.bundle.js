/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue3,main_core_events,main_popup,main_core,ui_entitySelector) {
	'use strict';

	const RuleAction = {
	  emits: ['removeActionBlock', 'addActionBlock', 'changeActionBlock'],
	  props: {
	    id: {
	      type: Symbol,
	      required: true
	    },
	    type: {
	      type: String,
	      required: true
	    },
	    data: {
	      type: Object,
	      required: false,
	      default: {}
	    },
	    entities: {
	      type: Array,
	      required: true,
	      default: []
	    }
	  },
	  data() {
	    var _this$data$actionCate, _this$data$entityType, _this$data$categoryId, _this$data$entityReus, _this$data$searchStra;
	    return {
	      currentActionCategory: (_this$data$actionCate = this.data.actionCategory) != null ? _this$data$actionCate : null,
	      currentSelectedEntityId: (_this$data$entityType = this.data.entityTypeId) != null ? _this$data$entityType : null,
	      currentSelectedCategoryId: (_this$data$categoryId = this.data.categoryId) != null ? _this$data$categoryId : null,
	      entityReuseMode: (_this$data$entityReus = this.data.entityReuseMode) != null ? _this$data$entityReus : null,
	      searchStrategy: (_this$data$searchStra = this.data.searchStrategy) != null ? _this$data$searchStra : null
	    };
	  },
	  computed: {
	    currentEntity() {
	      return this.getEntityById(this.currentSelectedEntityId);
	    }
	  },
	  methods: {
	    getEntityById(entityTypeId) {
	      var _this$entities$find;
	      return (_this$entities$find = this.entities.find(entity => entity.entityTypeId === Number(entityTypeId))) != null ? _this$entities$find : null;
	    },
	    removeActionBlock() {
	      this.$emit('removeActionBlock', this.id);
	    },
	    emitChanged() {
	      const data = {
	        actionCategory: this.currentActionCategory,
	        entityTypeId: this.currentSelectedEntityId,
	        categoryId: this.currentSelectedCategoryId,
	        entityReuseMode: this.entityReuseMode,
	        searchStrategy: this.searchStrategy
	      };
	      this.$emit('changeActionBlock', this.id, data);
	    }
	  },
	  watch: {
	    currentSelectedEntityId() {
	      this.emitChanged();
	    },
	    currentSelectedCategoryId() {
	      this.emitChanged();
	    },
	    entityReuseMode() {
	      this.emitChanged();
	    },
	    searchStrategy() {
	      this.emitChanged();
	    },
	    currentActionCategory() {
	      this.emitChanged();
	    }
	  },
	  created() {
	    this.$watch('data', data => {
	      var _data$actionCategory, _data$entityTypeId, _data$categoryId, _data$entityReuseMode, _data$searchStrategy;
	      this.currentActionCategory = (_data$actionCategory = data.actionCategory) != null ? _data$actionCategory : null;
	      this.currentSelectedEntityId = (_data$entityTypeId = data.entityTypeId) != null ? _data$entityTypeId : null;
	      this.currentSelectedCategoryId = (_data$categoryId = data.categoryId) != null ? _data$categoryId : null;
	      this.entityReuseMode = (_data$entityReuseMode = data.entityReuseMode) != null ? _data$entityReuseMode : null;
	      this.searchStrategy = (_data$searchStrategy = data.searchStrategy) != null ? _data$searchStrategy : null;
	    }, {
	      deep: true
	    });
	  },
	  template: `
		<div class="communication-rule-action-wrapper">
			<div
				class="communication-rule-property-close"
				@click="removeActionBlock"
			>
				X
			</div>
			
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_CATEGORY_TITLE')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="currentActionCategory">
							<option value="entity">${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_CATEGORY_ITEM_CREATE')}</option>
							<option value="exit">${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_CATEGORY_EXIT')}</option>
						</select>
					</div>
				</div>
			</div>
			
			<div class="ui-form-row" v-if="currentActionCategory === 'entity'">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_TITLE')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="currentSelectedEntityId">
							<option
								v-for="entity in entities"
								:key="entity.entityTypeId"
								:value="entity.entityTypeId"
							>
								{{ entity.name }}
							</option>
						</select>
					</div>
				</div>
			</div>
			
			<div
				v-if="currentActionCategory === 'entity' && currentEntity?.categories?.length > 1"
				class="ui-form-row"
			>
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_CATEGORY_TITLE')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="currentSelectedCategoryId">
							<option
								v-for="category in currentEntity?.categories"
								:key="category.id"
								:value="category.id"
							>
								{{ category.name }}
							</option>
						</select>
					</div>
				</div>
			</div>
			
			<div class="ui-form-row" v-if="currentActionCategory === 'entity'">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_REUSE_MODE')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="entityReuseMode">
							<option value="new">${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_REUSE_MODE_NEW')}</option>
							<option value="exist">${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_ENTITY_REUSE_MODE_EXIST')}</option>
						</select>
					</div>
				</div>
			</div>
			
			<div class="ui-form-row" v-if="entityReuseMode === 'exist'">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_SEARCH_STRATEGY')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" v-model="searchStrategy">
							<option value="1">${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_SEARCH_STRATEGY_MAX_CREATE')}</option>
							<option value="2">${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ACTION_SEARCH_STRATEGY_MAX_UPDATE')}</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	`
	};

	const RuleActions = {
	  components: {
	    RuleAction
	  },
	  props: {
	    actions: {
	      type: Array,
	      required: true,
	      default: []
	    },
	    entities: {
	      type: Array,
	      required: true,
	      default: []
	    }
	  },
	  data() {
	    return {
	      preparedActions: this.getPreparedActions()
	    };
	  },
	  computed: {
	    currentEntity() {
	      return this.getEntityById(this.currentSelectedEntityId);
	    }
	  },
	  methods: {
	    reset() {
	      this.preparedActions = this.getPreparedActions();
	    },
	    getPreparedActions() {
	      const preparedActions = [];
	      this.actions.forEach(action => {
	        preparedActions.push({
	          id: Symbol('actionId'),
	          ...action
	        });
	      });
	      return preparedActions;
	    },
	    getEntityById(entityTypeId) {
	      return this.entities.find(entity => entity.entityTypeId === entityTypeId);
	    },
	    addAction() {
	      this.preparedActions.push({
	        id: Symbol('actionId'),
	        type: 'entity',
	        data: {}
	      });
	    },
	    removeActionBlock(id) {
	      const index = this.preparedActions.findIndex(action => action.id === id);
	      if (index >= 0) {
	        this.preparedActions.splice(index, 1);
	      }
	    },
	    changeActionBlock(id, data) {
	      const action = this.preparedActions.find(item => item.id === id);
	      if (action) {
	        action.data = data;
	      }
	    },
	    getData() {
	      const data = [];
	      this.preparedActions.forEach(action => {
	        data.push({
	          type: action.type,
	          data: action.data
	        });
	      });
	      return data;
	    }
	  },
	  template: `
		<div>
			<div class="communication-rule-title">
				<span class="communication-rule-title-text">
					${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_ACTIONS_SETTINGS_TITLE')}
				</span>
			</div>
			<div class="ui-form">
				<RuleAction
					v-for="action in preparedActions"
					:id="action.id"
					:type="action.type"
					:data="action.data"
					:entities="entities"
					@changeActionBlock="changeActionBlock"
					@removeActionBlock="removeActionBlock"
				/>
				<span
					class="communication-rule-add-rule-property"
					@click="addAction"
				>
					${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ADD_ACTION')}
				</span>
			</div>	
		</div>
	`
	};

	const LogicSelector = {
	  props: {
	    id: {
	      type: Symbol,
	      required: true
	    },
	    value: {
	      type: String,
	      required: true
	    }
	  },
	  methods: {
	    changeLogicSelector(value) {
	      this.$emit('onChange', this.id, value);
	    }
	  },
	  computed: {
	    andClass() {
	      return ['communication-rule-property-logic-selector', {
	        '--active': this.value === 'AND'
	      }];
	    },
	    orClass() {
	      return ['communication-rule-property-logic-selector', {
	        '--active': this.value === 'OR'
	      }];
	    }
	  },
	  template: `
		<div class="communication-rule-property-logic-selector-container">
			<div
				:class="andClass"
				@click="changeLogicSelector('AND')"
			>
				${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_PROPERTY_AND')}
			</div>
			<div
				:class="orClass"
				@click="changeLogicSelector('OR')"
			>
				${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_PROPERTY_OR')}
			</div>
		</div>
	`
	};

	const RuleProperty = {
	  emits: ['appendValue', 'removeValue', 'inputValue', 'removePropertyBlock'],
	  props: {
	    id: {
	      type: Symbol,
	      required: true
	    },
	    property: {
	      type: Object,
	      required: true
	    },
	    values: {
	      type: Array,
	      required: false,
	      default: [null]
	    }
	  },
	  methods: {
	    appendValue() {
	      this.$emit('appendValue', this.id);
	    },
	    removeValue(index) {
	      this.$emit('removeValue', this.id, index);
	    },
	    inputValue(value, index) {
	      this.$emit('inputValue', this.id, index, value);
	    },
	    removePropertyBlock() {
	      this.$emit('removePropertyBlock', this.id);
	    }
	  },
	  template: `
		<div class="ui-form-row communication-rule-property-wrapper">
			<div 
				class="communication-rule-property-close"
				@click="removePropertyBlock"
			>
				X
			</div>
			<div class="ui-form-label">
				<div class="ui-ctl-label-text">
					{{ property.title }}
				</div>
			</div>
			<div class="ui-form-content">
				<div
					v-for="(value, index) in values"
					key="index"
					class="ui-ctl ui-ctl-row ui-ctl-w100"
				>
					<div
						v-if="index > 0"
						class="communication-rule-label-or"
					>
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_PROPERTY_OR')}
					</div>
					<select
						v-if="property.type === 'enumeration'"
						ref="values"
						class="ui-ctl-element"
						@input="inputValue($event.target.value, index)"
						:value="value ?? ''"
					>
						<option
							v-for="(elementValue, elementIndex) in property.params.list"
							:key="elementIndex"
							:value="elementIndex"
						>
							{{ elementValue }}
						</option>
					</select>
					<input
						v-else
						ref="values"
						type="text"
						class="ui-ctl-element"
						@input="inputValue($event.target.value, index)"
						:value="value ?? ''"
					>
					<div 
						class="communication-rule-rule-value-remove"
						ref="remove"
						@click="removeValue(index)"
					>
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_REMOVE_RULE_VALUE')}
					</div>
				</div>
				
				<div class="communication-rule-rule-value-add-wrapper">
					<div
						class="communication-rule-rule-value-add"
						ref="add"
						@click="appendValue"
					>
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ADD_RULE_VALUE')}
					</div>
				</div>
			</div>
		</div>
	`
	};

	const LOGIC_AND = 'AND';
	const RuleProperties = {
	  components: {
	    RuleProperty,
	    LogicSelector
	  },
	  props: {
	    properties: {
	      type: Object,
	      required: true,
	      default: {}
	    },
	    rules: {
	      type: Array,
	      required: false,
	      default: []
	    }
	  },
	  data() {
	    return {
	      filledProperties: this.rules || []
	    };
	  },
	  methods: {
	    getPropertyByCode(code) {
	      var _this$properties$find;
	      return (_this$properties$find = this.properties.find(property => property.code === code)) != null ? _this$properties$find : null;
	    },
	    showRuleSelector() {
	      const menuItems = [];
	      const menuParams = {
	        closeByEsc: true,
	        autoHide: true,
	        //offsetLeft: 60,
	        angle: true,
	        cacheable: false
	      };
	      this.properties.forEach(property => {
	        menuItems.push({
	          id: `rule-selector-menu-id-${property.code}`,
	          onclick: this.onRuleSelectorItemClick.bind(this, property.code),
	          html: main_core.Text.encode(property.title)
	        });
	      });
	      this.ruleSelector = main_popup.MenuManager.create('communication-rule-selector', this.$refs.showRuleSelector, menuItems, menuParams);
	      this.ruleSelector.show();
	    },
	    onRuleSelectorItemClick(code) {
	      const id = Symbol('ruleId');
	      this.filledProperties.push({
	        id,
	        code,
	        values: [null],
	        logic: LOGIC_AND
	      });
	      this.ruleSelector.close();
	    },
	    appendValue(id) {
	      const filledProperty = this.filledProperties.find(property => property.id === id);
	      filledProperty == null ? void 0 : filledProperty.values.push(null);
	    },
	    removeValue(id, index) {
	      const filledProperty = this.filledProperties.find(property => property.id === id);
	      filledProperty == null ? void 0 : filledProperty.values.splice(index, 1);
	    },
	    inputValue(id, index, value) {
	      const filledProperty = this.filledProperties.find(property => property.id === id);
	      if (filledProperty) {
	        filledProperty.values[index] = value;
	      }
	    },
	    removePropertyBlock(id) {
	      const index = this.filledProperties.findIndex(property => property.id === id);
	      if (index >= 0) {
	        this.filledProperties.splice(index, 1);
	      }
	    },
	    onChangeLogicValue(id, value) {
	      const filledProperty = this.filledProperties.find(property => property.id === id);
	      if (filledProperty) {
	        filledProperty.logic = value;
	      }
	    },
	    getData() {
	      const data = [];
	      this.filledProperties.forEach(property => {
	        data.push({
	          values: property.values,
	          code: property.code,
	          logic: property.logic
	        });
	      });
	      return data;
	    }
	  },
	  template: `
		<div>
			<div class="communication-rule-title">
				<span class="communication-rule-title-text">
					${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_RULES_SETTINGS_TITLE')}
				</span>
			</div>
			<div class="ui-form">
				<div
					class="communication-rule-property-container"
					v-for="(filledProperty, index) in filledProperties"
				>
					<RuleProperty
						:key="filledProperty.code"
						:id="filledProperty.id"
						:property="getPropertyByCode(filledProperty.code)"
						:values="filledProperty.values"
						@appendValue="appendValue"
						@removeValue="removeValue"
						@inputValue="inputValue"
						@removePropertyBlock="removePropertyBlock"
					/>
					<div
						v-if="index < filledProperties.length - 1"
					>
						<LogicSelector
							:id="filledProperty.id"
							:value="filledProperty.logic"
							@onChange="onChangeLogicValue"
						/>
					</div>
				</div>
				<span
					class="communication-rule-add-rule-property"
					@click="showRuleSelector"
					ref="showRuleSelector"
				>
					${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_ADD_RULE')}
				</span>
			</div>
		</div>
	`
	};

	const QueueConfig = {
	  props: {
	    properties: {
	      type: Object,
	      required: true,
	      default: {}
	    },
	    config: {
	      type: Object,
	      required: true,
	      default: {}
	    }
	  },
	  data() {
	    var _this$config$SETTINGS, _this$config$SETTINGS2, _this$config$SETTINGS3, _this$config$SETTINGS4;
	    return {
	      isMembersSelectorReadOnly: false,
	      filledMembers: this.config.MEMBERS || [],
	      isForwardTo: ((_this$config$SETTINGS = this.config.SETTINGS) == null ? void 0 : _this$config$SETTINGS.FORWARD_TO) || false,
	      isTimeTracking: ((_this$config$SETTINGS2 = this.config.SETTINGS) == null ? void 0 : _this$config$SETTINGS2.TIME_TRACKING) || false,
	      filledMemberRequestDistribution: ((_this$config$SETTINGS3 = this.config.SETTINGS) == null ? void 0 : _this$config$SETTINGS3.MEMBER_REQUEST_DISTRIBUTION) || 'STRICTLY',
	      filledTimeBeforeRequestNextMember: ((_this$config$SETTINGS4 = this.config.SETTINGS) == null ? void 0 : _this$config$SETTINGS4.TIME_BEFORE_REQUEST_NEXT_MEMBER) || 5
	    };
	  },
	  methods: {
	    isPropertyEnabled(code) {
	      return Object.prototype.hasOwnProperty.call(this.properties, code) && (this.properties[code] === true || main_core.Type.isArrayFilled(this.properties[code]));
	    },
	    getSelectedMembers() {
	      return this.filledMembers.map(item => {
	        return [item.ENTITY_TYPE, parseInt(item.ENTITY_ID, 10)];
	      });
	    },
	    getData() {
	      const selectedMembers = this.membersSelector.getDialog().getSelectedItems();
	      if (main_core.Type.isArrayFilled(selectedMembers)) {
	        this.filledMembers = selectedMembers.map(item => ({
	          ENTITY_ID: item.getId(),
	          ENTITY_TYPE: item.getEntityId()
	        }));
	      }
	      return {
	        members: this.filledMembers,
	        properties: {
	          FORWARD_TO: this.isForwardTo,
	          TIME_TRACKING: this.isTimeTracking,
	          MEMBER_REQUEST_DISTRIBUTION: this.filledMemberRequestDistribution,
	          TIME_BEFORE_REQUEST_NEXT_MEMBER: this.filledTimeBeforeRequestNextMember
	        }
	      };
	    }
	  },
	  mounted() {
	    this.membersSelector = new ui_entitySelector.TagSelector({
	      id: 'queue-config-members-tag-selector',
	      context: 'QUEUE_CONFIG_MEMBERS_SELECTOR',
	      readonly: this.isMembersSelectorReadOnly,
	      dialogOptions: {
	        id: 'queue-config-members-tag-selector',
	        preselectedItems: this.getSelectedMembers(),
	        entities: [{
	          id: 'user',
	          options: {
	            inviteEmployeeLink: false,
	            intranetUsersOnly: true
	          }
	        }, {
	          id: 'department',
	          options: {
	            inviteEmployeeLink: false,
	            selectMode: 'usersAndDepartments'
	          }
	        }]
	      }
	    });
	    this.membersSelector.renderTo(this.$refs.membersSelector);
	  },
	  template: `
		<div class="ui-form">
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_SELECTOR_TITLE')}
					</div>
				</div>
				<div class="ui-form-content" ref="membersSelector"></div>
			</div>
			<div
				v-if="isPropertyEnabled('FORWARD_TO')"
				class="ui-form-row"
			>
				<label for="isForwardTo" class="communication-queue-checkbox-block-wrapper ui-ctl ui-ctl-checkbox">
					<input
						type="checkbox"
						class="ui-ctl-element"
						name="isForwardTo"
						v-model="isForwardTo"
					>
					<span class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_FORWARD_TO_TITLE')}
					</span>
				</label>
			</div>
			<div
				v-if="isPropertyEnabled('TIME_TRACKING')"
				class="ui-form-row"
			>
				<label for="isTimeTracking" class="communication-queue-checkbox-block-wrapper ui-ctl ui-ctl-checkbox">
					<input
						type="checkbox"
						class="ui-ctl-element"
						name="isTimeTracking"
						v-model="isTimeTracking"
					>
					<span class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_TIME_TRACKING_TITLE')}
					</span>
				</label>
			</div>
			<div
				v-if="isPropertyEnabled('MEMBER_REQUEST_DISTRIBUTION_STRICTLY')"
				class="ui-form-row"
			>
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_MEMBER_REQUEST_DISTRIBUTION_NAME')}
					</div>
				</div>
				<div class="ui-form-content">
					<select
						class="communication-queue-checkbox-block-wrapper ui-ctl ui-ctl-row ui-ctl-w100 ui-ctl-element"
						v-model="filledMemberRequestDistribution"
					>
						<option 
							value="STRICTLY"
						>
							${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_MEMBER_REQUEST_DISTRIBUTION_STRICTLY')}
						</option>
						<option 
							v-if="isPropertyEnabled('MEMBER_REQUEST_DISTRIBUTION_EVENLY')"
							value="EVENLY"
						>
							${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_MEMBER_REQUEST_DISTRIBUTION_EVENLY')}
						</option>
						<option
							v-if="isPropertyEnabled('MEMBER_REQUEST_DISTRIBUTION_ALL')"
							value="ALL"
						>
							${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_MEMBER_REQUEST_DISTRIBUTION_ALL')}
						</option>
					</select>
				</div>
			</div>
			<div
				v-if="isPropertyEnabled('TIME_BEFORE_REQUEST_NEXT_MEMBER')"
				class="ui-form-row"
			>
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_TIME_BEFORE_REQUEST_NEXT_MEMBER_NAME')}
					</div>
				</div>
				<div class="ui-form-content">
					<select
						class="communication-queue-checkbox-block-wrapper ui-ctl ui-ctl-row ui-ctl-w100 ui-ctl-element"
						v-model="filledTimeBeforeRequestNextMember"
					>
						<option v-for="value in this.properties.TIME_BEFORE_REQUEST_NEXT_MEMBER" :value="value">
							{{ value }}
						</option>
					</select>
				</div>
			</div>
		</div>
	`
	};

	const EMPTY = 0;
	const LEAD = 1;
	const CONTACT = 3;
	const COMPANY = 4;
	const CommunicationRule = {
	  components: {
	    RuleProperties,
	    RuleActions,
	    QueueConfig
	  },
	  props: {
	    rule: {
	      type: Object,
	      required: false,
	      default: {}
	    },
	    channels: {
	      type: Array,
	      required: true,
	      default: []
	    },
	    searchTargetEntities: {
	      type: Array,
	      required: true,
	      default: []
	    },
	    entities: {
	      type: Array,
	      required: true,
	      default: []
	    },
	    selectedChannelId: {
	      type: Number,
	      required: false,
	      default: null
	    }
	  },
	  data() {
	    var _this$rule$SEARCH_TAR, _this$rule, _this$selectedChannel, _Runtime$clone, _this$rule2, _this$rule3, _this$rule4, _this$rule5, _this$rule6, _this$rule7, _this$rule7$SETTINGS, _this$rule8, _this$rule8$SETTINGS, _this$rule9, _this$rule9$SETTINGS;
	    const searchTarget = (_this$rule$SEARCH_TAR = (_this$rule = this.rule) == null ? void 0 : _this$rule.SEARCH_TARGETS) != null ? _this$rule$SEARCH_TAR : {};
	    return {
	      currentSelectedChannelId: (_this$selectedChannel = this.selectedChannelId) != null ? _this$selectedChannel : this.channels[0].id,
	      currentSelectedTargetEntitySectionId: searchTarget.sectionId,
	      currentSelectedTargetEntityTypeIds: (_Runtime$clone = main_core.Runtime.clone(searchTarget.entityTypeIds)) != null ? _Runtime$clone : [],
	      ruleId: ((_this$rule2 = this.rule) == null ? void 0 : _this$rule2.ID) || null,
	      actions: ((_this$rule3 = this.rule) == null ? void 0 : _this$rule3.ENTITIES) || [],
	      title: ((_this$rule4 = this.rule) == null ? void 0 : _this$rule4.TITLE) || [],
	      rules: ((_this$rule5 = this.rule) == null ? void 0 : _this$rule5.RULES) || [],
	      queueConfig: ((_this$rule6 = this.rule) == null ? void 0 : _this$rule6.QUEUE_CONFIG) || [],
	      skipNextRules: ((_this$rule7 = this.rule) == null ? void 0 : (_this$rule7$SETTINGS = _this$rule7.SETTINGS) == null ? void 0 : _this$rule7$SETTINGS.skipNextRules) === 'Y' || false,
	      manualItemsCreate: ((_this$rule8 = this.rule) == null ? void 0 : (_this$rule8$SETTINGS = _this$rule8.SETTINGS) == null ? void 0 : _this$rule8$SETTINGS.manualItemsCreate) === 'Y' || false,
	      runWorkflowLater: ((_this$rule9 = this.rule) == null ? void 0 : (_this$rule9$SETTINGS = _this$rule9.SETTINGS) == null ? void 0 : _this$rule9$SETTINGS.runWorkflowLater) === 'Y' || false
	    };
	  },
	  mounted() {
	    main_core_events.EventEmitter.subscribe(BX.UI.ButtonPanel, 'button-click', this.onButtonClick.bind(this));
	  },
	  beforeUnmount() {
	    main_core_events.EventEmitter.unsubscribe(BX.UI.ButtonPanel, 'button-click', this.onButtonClick);
	  },
	  computed: {
	    selectedChannelRuleProperties() {
	      var _this$getChannelById;
	      return (_this$getChannelById = this.getChannelById(this.currentSelectedChannelId)) == null ? void 0 : _this$getChannelById.properties;
	    },
	    selectedChannelQueueConfig() {
	      var _this$getChannelById2;
	      return (_this$getChannelById2 = this.getChannelById(this.currentSelectedChannelId)) == null ? void 0 : _this$getChannelById2.queueConfig;
	    },
	    targetEntitySections() {
	      const sections = [];
	      this.searchTargetEntities.forEach(item => {
	        sections.push(item.section);
	      });
	      return sections;
	    },
	    compatibleEntities() {
	      // empty -> all excepts repeated lead
	      if (this.currentSelectedTargetEntityTypeIds.includes(EMPTY)) {
	        return this.entities.filter(entity => {
	          var _entity$data;
	          return entity.entityTypeId !== LEAD || entity.entityTypeId === LEAD && ((_entity$data = entity.data) == null ? void 0 : _entity$data.isReturnCustomer) !== true;
	        });
	      }

	      // lead -> lead
	      if (this.currentSelectedTargetEntityTypeIds.includes(LEAD) && this.currentSelectedTargetEntityTypeIds.length === 1) {
	        const leadEntity = this.entities.find(entity => {
	          var _entity$data2;
	          return entity.entityTypeId === LEAD && ((_entity$data2 = entity.data) == null ? void 0 : _entity$data2.isReturnCustomer) !== true;
	        });
	        return [leadEntity];
	      }

	      // contact -> repeated lead, contact, deal, dynamics
	      if (this.currentSelectedTargetEntityTypeIds.includes(CONTACT) && this.currentSelectedTargetEntityTypeIds.length === 1) {
	        return this.entities.filter(entity => {
	          var _entity$data3;
	          return entity.entityTypeId !== COMPANY && entity.entityTypeId !== LEAD || entity.entityTypeId === LEAD && ((_entity$data3 = entity.data) == null ? void 0 : _entity$data3.isReturnCustomer) === true;
	        });
	      }
	      if (this.currentSelectedTargetEntityTypeIds.includes(LEAD) && this.currentSelectedTargetEntityTypeIds.includes(CONTACT) && this.currentSelectedTargetEntityTypeIds.length === 2) {
	        return this.entities.filter(entity => {
	          return entity.entityTypeId !== COMPANY;
	        });
	      }
	      if (this.currentSelectedTargetEntityTypeIds.includes(LEAD) && this.currentSelectedTargetEntityTypeIds.includes(COMPANY) && this.currentSelectedTargetEntityTypeIds.length === 2) {
	        return this.entities.filter(entity => {
	          return entity.entityTypeId !== CONTACT;
	        });
	      }
	      if (this.currentSelectedTargetEntityTypeIds.length === 0) {
	        return this.entities.filter(entity => {
	          var _entity$data4;
	          return entity.entityTypeId !== LEAD || ((_entity$data4 = entity.data) == null ? void 0 : _entity$data4.isReturnCustomer) !== true;
	        });
	      }
	      return this.entities;
	    }
	  },
	  methods: {
	    async onButtonClick(event) {
	      var _data$;
	      const data = event.getData();
	      const button = (_data$ = data[0]) != null ? _data$ : null;
	      if (!main_core.Type.isObject(button)) {
	        return;
	      }
	      if (button.TYPE === 'save') {
	        await this.save();
	      } else if (button.TYPE === 'remove') {
	        await this.delete();
	      }
	      const currentSlider = top.BX.SidePanel.Instance.getSliderByWindow(window);
	      if (currentSlider) {
	        currentSlider.setCacheable(false);
	        currentSlider.close(false);
	      }
	    },
	    async save() {
	      var _this$$refs$propertie, _this$$refs$queueConf, _this$$refs$actions$g;
	      const data = {
	        id: this.ruleId,
	        title: this.title,
	        channelId: this.currentSelectedChannelId,
	        properties: (_this$$refs$propertie = this.$refs.properties.getData()) != null ? _this$$refs$propertie : [],
	        queueConfig: (_this$$refs$queueConf = this.$refs.queueConfig.getData()) != null ? _this$$refs$queueConf : [],
	        searchTargets: {
	          sectionId: this.currentSelectedTargetEntitySectionId,
	          entityTypeIds: this.currentSelectedTargetEntityTypeIds
	        },
	        actions: (_this$$refs$actions$g = this.$refs.actions.getData()) != null ? _this$$refs$actions$g : [],
	        settings: {
	          skipNextRules: this.skipNextRules ? 'Y' : 'N',
	          manualItemsCreate: this.manualItemsCreate ? 'Y' : 'N',
	          runWorkflowLater: this.runWorkflowLater ? 'Y' : 'N'
	        }
	      };
	      return new Promise(async (resolve, reject) => {
	        main_core.ajax.runAction('crm.controller.communication.rule.save', {
	          data
	        }).then(response => {
	          resolve(response);
	        }).catch(response => {
	          const errors = [];
	          response.errors.forEach(({
	            message
	          }) => {
	            errors.push(message);
	          });
	          reject(errors);
	        });
	      });
	    },
	    async delete() {
	      const data = {
	        id: this.ruleId,
	        withQueue: true
	      };

	      // eslint-disable-next-line no-async-promise-executor
	      return new Promise(async (resolve, reject) => {
	        main_core.ajax.runAction('crm.controller.communication.rule.delete', {
	          data
	        }).then(response => {
	          resolve(response);
	        }).catch(response => {
	          const errors = [];
	          response.errors.forEach(({
	            message
	          }) => {
	            errors.push(message);
	          });
	          reject(errors);
	        });
	      });
	    },
	    getChannelById(id) {
	      var _this$channels$find;
	      return (_this$channels$find = this.channels.find(channel => channel.id === id)) != null ? _this$channels$find : null;
	    },
	    getTargetEntitiesBySectionId(sectionId) {
	      var _section$entities;
	      const section = this.searchTargetEntities.find(entity => entity.section.id === sectionId);
	      return (_section$entities = section == null ? void 0 : section.entities) != null ? _section$entities : [];
	    },
	    onChangeCurrentSelectedTargetEntityTypeIds() {
	      this.actions = [];
	      if (this.currentSelectedTargetEntityTypeIds.includes(LEAD)) {
	        this.currentSelectedTargetEntityTypeIds = [LEAD];
	      }
	      void this.$nextTick(() => {
	        this.$refs.actions.reset();
	      });
	    }
	  },
	  template: `
		<div>
			<div class="communication-rule-block-wrapper">
				<div class="communication-rule-title">
					<span class="communication-rule-title-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_COMMON_SETTINGS_TITLE')}
					</span>
				</div>
				<div class="ui-form">
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">
								${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_TITLE')}
							</div>
						</div>
						<div class="ui-form-content">
							<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
								<input
									type="text" 
									class="ui-ctl-element"
									v-model="title"
								>
							</div>
						</div>
					</div>
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">
								${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_TITLE')}
							</div>
						</div>
						<div class="ui-form-content">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element" v-model="currentSelectedChannelId">
									<option v-for="channel in channels" :value="channel.id" :key="channel.id">
		    							{{ channel.title }}
		  							</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
	
			<div class="communication-rule-block-wrapper">
				<div class="communication-rule-title">
					<span class="communication-rule-title-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_SEARCH_TARGET_TITLE')}
					</span>
				</div>
				<div class="ui-form">
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">
								${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_SEARCH_TARGET_CATEGORY')}
							</div>
						</div>
						<div class="ui-form-content">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element" v-model="currentSelectedTargetEntitySectionId">
									<option v-for="section in targetEntitySections" :value="section.id" :key="section.id">
		    							{{ section.title }}
		  							</option>
								</select>
							</div>
						</div>
					</div>
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">
								${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_SEARCH_TARGET_ENTITY_TYPE')}
							</div>
						</div>
						<div class="ui-form-content">
							<div class="ui-ctl ui-ctl-w100">
								<select 
									class="ui-ctl-element" 
									v-model="currentSelectedTargetEntityTypeIds"
									multiple
									@change="onChangeCurrentSelectedTargetEntityTypeIds"
								>
									<option :value="0">
		    							${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_SEARCH_TARGET_ENTITY_EMPTY')}
		  							</option>
									<option 
										v-for="entity in getTargetEntitiesBySectionId(currentSelectedTargetEntitySectionId)"
										:value="entity.id"
										:key="entity.id"
									>
		    							{{ entity.title }}
		  							</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
	
			<div class="communication-rule-block-wrapper">
				<RuleProperties
					ref="properties"
					:properties="selectedChannelRuleProperties"
					:rules="rules"
				/>
			</div>

			<div class="communication-rule-block-wrapper">
				<div class="communication-rule-title">
					<span class="communication-rule-title-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_TITLE')}
					</span>
				</div>
				<QueueConfig
					ref="queueConfig"
					:properties="selectedChannelQueueConfig"
				 	:config="queueConfig"
				/>
			</div>

			<div class="communication-rule-block-wrapper">
				<RuleActions
					ref="actions"
					:actions="actions"
					:entities="compatibleEntities"
				/>
			</div>
	
			<div class="communication-rule-block-wrapper">
				<div class="communication-rule-title">
					<span class="communication-rule-title-text">
						${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_ADDITIONAL_SETTINGS_TITLE')}
					</span>
				</div>
				<div class="ui-form">
					<div class="ui-form-content">
						<div class="ui-form-row">
							<label for="skipNextRules" class="ui-ctl ui-ctl-checkbox">
								<input 
									class="ui-ctl-element"
									type="checkbox"
									name="skipNextRules"
									v-model="skipNextRules"
								>
								<span class="ui-ctl-label-text">
									${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CS_SKIP_RULES')}
								</span>
							</label>
						</div>
						<div class="ui-form-row">
							<label for="manualItemsCreate" class="ui-ctl ui-ctl-checkbox">
								<input 
									class="ui-ctl-element"
									type="checkbox"
									name="manualItemsCreate"
									v-model="manualItemsCreate"
								>
								<span class="ui-ctl-label-text">
									${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CS_MANUAL_ITEMS_CREATE')}
								</span>
							</label>
						</div>
						<div class="ui-form-row">
							<label for="runWorkflowLater" class="ui-ctl ui-ctl-checkbox">
								<input 
									class="ui-ctl-element"
									type="checkbox"
									name="runWorkflowLater"
									v-model="runWorkflowLater"
								>
								<span class="ui-ctl-label-text">
									${main_core.Loc.getMessage('CRM_COMMUNICATION_RULE_CS_RUN_WORKFLOW_LATER')}
								</span>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _app = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("app");
	var _channels = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("channels");
	var _entities = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entities");
	var _rule = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rule");
	var _searchTargetEntities = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("searchTargetEntities");
	var _selectedTargetEntitySectionId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedTargetEntitySectionId");
	var _selectedTargetEntityTypeIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedTargetEntityTypeIds");
	class CommunicationRule$1 {
	  constructor(containerId, params) {
	    var _params$selectedTarge, _params$searchTargetE;
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _app, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _channels, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entities, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _rule, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _searchTargetEntities, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedTargetEntitySectionId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedTargetEntityTypeIds, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _channels)[_channels] = params.channels;
	    babelHelpers.classPrivateFieldLooseBase(this, _entities)[_entities] = params.entities;
	    babelHelpers.classPrivateFieldLooseBase(this, _rule)[_rule] = params.rule;
	    babelHelpers.classPrivateFieldLooseBase(this, _searchTargetEntities)[_searchTargetEntities] = params.searchTargetEntities;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedTargetEntitySectionId)[_selectedTargetEntitySectionId] = (_params$selectedTarge = params.selectedTargetEntitySectionId) != null ? _params$selectedTarge : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedTargetEntityTypeIds)[_selectedTargetEntityTypeIds] = (_params$searchTargetE = params.searchTargetEntities) != null ? _params$searchTargetE : [];
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = document.getElementById(containerId);
	    if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container])) {
	      throw new Error('container not found');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app] = ui_vue3.BitrixVue.createApp(CommunicationRule, {
	      rule: babelHelpers.classPrivateFieldLooseBase(this, _rule)[_rule],
	      channels: babelHelpers.classPrivateFieldLooseBase(this, _channels)[_channels],
	      entities: babelHelpers.classPrivateFieldLooseBase(this, _entities)[_entities],
	      searchTargetEntities: babelHelpers.classPrivateFieldLooseBase(this, _searchTargetEntities)[_searchTargetEntities],
	      selectedTargetEntitySectionId: babelHelpers.classPrivateFieldLooseBase(this, _selectedTargetEntitySectionId)[_selectedTargetEntitySectionId],
	      selectedTargetEntityTypeIds: babelHelpers.classPrivateFieldLooseBase(this, _selectedTargetEntityTypeIds)[_selectedTargetEntityTypeIds]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].mount(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	  }
	}

	exports.CommunicationRule = CommunicationRule$1;

}((this.BX.Crm = this.BX.Crm || {}),BX.Vue3,BX.Event,BX.Main,BX,BX.UI.EntitySelector));
//# sourceMappingURL=communication-rule.bundle.js.map
