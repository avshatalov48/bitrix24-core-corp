<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

CUtil::InitJSCore(array('taskQuickPopups'));

$loggedInUserId = (int) $GLOBALS['USER']->GetID();
$loggedInUserFormattedName = '';

$rsUser = CUser::GetList(
	$by = 'ID', $order = 'ASC', 
	array('ID' => $loggedInUserId), 
	array('FIELDS' => array('NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'))
);

if ($arUser = $rsUser->Fetch())
{
	$loggedInUserFormattedName = CUser::FormatName(
		CSite::GetNameFormat(false), 
		array(
			'NAME'        => $arUser['NAME'],
			'LAST_NAME'   => $arUser['LAST_NAME'],
			'SECOND_NAME' => $arUser['SECOND_NAME'],
			'LOGIN'       => $arUser['LOGIN']
		),
		$bUseLogin = true,
		$bHtmlSpecialChars = false
	);
}

ob_start();
?>
	<div class="task-filter-popup" id="task-filter-popup" style="display: block;">
		<div class="task-filter-popup-header">
			<div class="task-filter-popup-name"><?php echo GetMessage('TASKS_FILTERV2_CONSTRUCTOR_FILTER_TITLE'); ?></div>
			<div class="task-filter-popup-inp-wrap">
				<input type="text" value="" id="tasks-filter-name" class="task-filter-popup-inp"
					onkeyup="BX.Tasks.filterV2.engine.setFilterName({},this.value,{renderer : {skipRender: true}});"
					onchange="BX.Tasks.filterV2.engine.setFilterName({},this.value,{renderer : {skipRender: true}});">
			</div>
		</div>
		<div id="task-filter-popup-root-level" class="task-filter-popup-items-wrap task-filter-and"></div>
	</div>
<?php
$html = ob_get_clean();

$href = '';
if (isset($arParams["PATH_TO_TASKS"]))
{
	$href = $arParams["PATH_TO_TASKS"];

	if (isset($_GET['VIEW']))
		$href .= '?VIEW=' . (int) $_GET['VIEW'] . '&F_FILTER_SWITCH_PRESET=';
	else
		$href .= '?F_FILTER_SWITCH_PRESET=';
}


?><script>
BX.ready(function(){
	if ( ! BX.Tasks.filterV2.engine )
		return;

	var oEngine = BX.Tasks.filterV2.engine;

	BX.message({
		TASKS_FILTERV2_CONSTRUCTOR_TITLE_EDIT   : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_TITLE_EDIT'); ?>',
		TASKS_FILTERV2_CONSTRUCTOR_TITLE_CREATE : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_TITLE_CREATE'); ?>',
		TASKS_FILTERV2_CONSTRUCTOR_BTN_ADD_CONDITION : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_BTN_ADD_CONDITION'); ?>',
		TASKS_FILTERV2_CONSTRUCTOR_BTN_ADD_GROUP     : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_BTN_ADD_GROUP'); ?>',
		TASKS_FILTERV2_CONSTRUCTOR_BTN_REMOVE        : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_BTN_REMOVE'); ?>',
		TASKS_FILTERV2_CONSTRUCTOR_REMOVE_CONFIRM    : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_REMOVE_CONFIRM'); ?>',
		TASKS_FILTERV2_CONSTRUCTOR_NO_NAME_GIVEN     : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_NO_NAME_GIVEN'); ?>',
		TASKS_FILTERV2_CONSTRUCTOR_PLACEHOLDER_SET_DAYS_COUNT : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_PLACEHOLDER_SET_DAYS_COUNT'); ?>'
	});

	oEngine.loggedInUserId     = <?php echo (int) $loggedInUserId; ?>;
	oEngine.formattedGroupsNames = {};	// cache of groups names
	oEngine.formattedUserNames = {};	// cache of user names
	oEngine.formattedUserNames['u' + <?php echo (int) $loggedInUserId; ?>] = '<?php echo CUtil::JSEscape($loggedInUserFormattedName); ?>';

	oEngine.manifest = <?php echo CUtil::PhpToJsObject(CTaskFilterCtrl::getManifest()); ?>;

	oEngine.objForm = BX.Tasks.lwPopup.registerForm({
		callbacks: {
			onAfterPopupCreated : function(){},
			onBeforePopupShow   : function(){},
			onAfterPopupShow    : function(){},
			onAfterEditorInited : function(){},
			onPopupClose        : (function(objEngine){
				return function(){
					if (objEngine.renderer.bFormShowed)
						objEngine.renderer.bFormShowed = false;
				}
			})(oEngine)
		}
	});

	oEngine.objForm.objTemplate = {
		oEngine        : oEngine,
		prepareButtons : function()
		{
			return [
				new BX.PopupWindowButton({
					text: '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_BTN_APPLY'); ?>',
					className: "popup-window-button-accept",
					events: {
						click : function()
						{
							oEngine.savePresetData(function(reply){
								if (reply['status'] === 'success')
								{
									BX.Tasks.filterV2.engine.renderer.closeForm();

									if (reply.reply.newPresetId)
										window.location = BX.Tasks.filterV2.pathToTasks + '&F_FILTER_SWITCH_PRESET=' + reply.reply.newPresetId;
								}
								else
								{
									if (reply['reply'] === 'no name given')
										alert(BX.message('TASKS_FILTERV2_CONSTRUCTOR_NO_NAME_GIVEN'));
								}
							});							
						}
					}
				}),
				new BX.PopupWindowButton({
					text: '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_BTN_CANCEL'); ?>',
					className: "",
					events: {
						click : function()
						{
							BX.Tasks.filterV2.engine.renderer.closeForm();
						}
					}
				}),
				new BX.PopupWindowButton({
					text: '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_BTN_REMOVE'); ?>',
					className: "popup-window-button-decline",
					events: {
						click : function()
						{
							if ( ! confirm(BX.message('TASKS_FILTERV2_CONSTRUCTOR_REMOVE_CONFIRM')) )
								return;

							BX.Tasks.filterV2.engine.renderer.closeForm();
							BX.Tasks.filterV2.engine.removeCurrentPreset(function(){
								window.location = BX.Tasks.filterV2.pathToTasks;
							});							
						}
					}
				})
			];
		},
		prepareTitleBar : function()
		{
			return ({
				content: BX.create(
					'span',
					{
						html : 'Some title'
					}
				)
			});
		},
		prepareContent : function(pData)
		{
			var html = '<?php echo CUtil::jsEscape($html); ?>';

			return html;
		}
	};

	BX.Tasks.lwPopup.buildForm(oEngine.objForm.formIndex, {}, 113);

	oEngine.renderer = {
		serialId    : 0,
		oEngine     : oEngine,
		bFormShowed : false,


		startTransaction : function()
		{
		},


		commit : function()
		{
			this.showForm();
		},


		showForm : function()
		{
			if ( ! this.bFormShowed )
			{
				BX.Tasks.lwPopup.showForm(this.oEngine.objForm.formIndex);
				this.bFormShowed = true;
			}
		},


		closeForm : function()
		{
			this.oEngine.objForm.objPopup.close();
		},


		recreateRootBlock : function(meta)
		{
			var btnAddCondition = BX.create(
				'span',
				{
					props : { className : 'task-filter-popup-add-item-text' },
					text  : BX.message('TASKS_FILTERV2_CONSTRUCTOR_BTN_ADD_CONDITION')
				}
			);

			var btnAddGroup = BX.create(
				'span',
				{
					props : { className : 'task-filter-popup-add-item-text' },
					text  : BX.message('TASKS_FILTERV2_CONSTRUCTOR_BTN_ADD_GROUP')
				}
			);

			BX('task-filter-popup-root-level').innerHTML = '';
			BX('task-filter-popup-root-level').appendChild(
				BX.create(
					'div',
					{
						props : { className : 'task-filter-popup-add-item' },
						children : [
							btnAddCondition,
							btnAddGroup
						]
					}
				)
			);

			if ( ! meta.hasOwnProperty('rendererIdentify') )
				meta.rendererIdentify = {};

			meta.rendererIdentify.domNode = BX('task-filter-popup-root-level');

			btnAddGroup.onclick = (function(blockMeta){
				return function()
				{
					var newBlockMeta = BX.Tasks.filterV2.engine.createBlock(blockMeta);
					BX.Tasks.filterV2.engine.setBlockLogic(newBlockMeta, 'AND');
				}
			})(meta);

			btnAddCondition.onclick = (function(blockMeta){
				return function()
				{
					BX.Tasks.filterV2.engine.addCondition(
						blockMeta,
						'TITLE', 
						3,		// operation code of operation named 'contains'
						''
					);
				}
			})(meta);

			return (meta);
		},


		setBlockLogic : function(meta, logic)
		{
			var block = meta.rendererIdentify.domNode;

			BX.removeClass(block, 'task-filter-and');
			BX.removeClass(block, 'task-filter-or');

			if (logic === 'OR')
				BX.addClass(block, 'task-filter-or');
			else
				BX.addClass(block, 'task-filter-and');
		},


		setFilterName : function(meta, name, params)
		{
			if (
				params
				&& params.renderer
				&& params.renderer.hasOwnProperty('skipRender')
				&& (params.renderer.skipRender === true)
			)
			{
				return;		// nothing to do
			}

			BX('tasks-filter-name').value = name;
		},


		setFilterId : function(meta, id)
		{
			if (id > 0)
				var title = BX.message('TASKS_FILTERV2_CONSTRUCTOR_TITLE_EDIT');
			else
				var title = BX.message('TASKS_FILTERV2_CONSTRUCTOR_TITLE_CREATE');

			this.oEngine.objForm.objPopup.setTitleBar(title);
		},


		createBlock : function(meta, parentBlockMeta)	// block, parentCondition, index)
		{
			var parentBlock = parentBlockMeta.rendererIdentify.domNode;

			var removeNode = BX.create('div', {
				props : { className: 'task-filter-popup-item-remove' }
			});

			var btnAddCondition = BX.create(
				'span',
				{
					props : { className : 'task-filter-popup-add-item-text' },
					text  : BX.message('TASKS_FILTERV2_CONSTRUCTOR_BTN_ADD_CONDITION')
				}
			);

			var addBtnsNode = BX.create(
				'div',
				{
					props : { className : 'task-filter-popup-add-item' },
					children : [
						btnAddCondition
					]
				}
			);

			var node = BX.create('div', {
				props : { className: 'task-filter-popup-subitems-wrap' }
			});

			if ( ! meta.hasOwnProperty('rendererIdentify') )
				meta.rendererIdentify = {};

			meta.rendererIdentify.domNode = node;

			removeNode.onclick = (function(meta, objSelf){
				return function(e) {
					if (e && e.target)
						e.target.style.display = 'none';

					objSelf.__onRemoveCondition(meta);
				}
			})(meta, this)

			node.appendChild(addBtnsNode);
			node.appendChild(removeNode);
			node.appendChild(this.__createLabelDiv(parentBlockMeta, meta));

			btnAddCondition.onclick = (function(blockMeta){
				return function()
				{
					BX.Tasks.filterV2.engine.addCondition(
						blockMeta,
						'TITLE', 
						3,		// contains
						''
					);
				}
			})(meta);

			this.__insertToBlock(parentBlock, node);

			return (meta);
		},


		__onRemoveCondition : function(meta)
		{
			BX.Tasks.filterV2.engine.removeCondition.call(
				BX.Tasks.filterV2.engine,
				meta
			);
		},


		removeCondition : function(meta)
		{
			BX.remove(meta.rendererIdentify.domNode);
		},


		__insertToBlock : function(insertTo, insertWhat)
		{
			//insertTo.appendChild(insertWhat);

			var referenceNode = null;

			// Find child with className = 'task-filter-popup-item-remove'
			// or 'task-filter-popup-add-item'
			for (var i = 0; i < insertTo.childNodes.length; i++)
			{
				if (
					(insertTo.childNodes[i].className == 'task-filter-popup-item-remove')
					|| (insertTo.childNodes[i].className == 'task-filter-popup-add-item')
				)
				{
					referenceNode = insertTo.childNodes[i];
					break;
				}        
			}			

			insertTo.insertBefore(insertWhat, referenceNode);
		},


		addCondition : function(blockMeta, itemMeta, itemType, operation, value)
		{
			var blockNode = blockMeta.rendererIdentify.domNode;

			itemMeta = this.__createItemNode(blockMeta, itemMeta, itemType, operation, value);

			this.__insertToBlock(blockNode, itemMeta.rendererIdentify.domNode);

			return (itemMeta);
		},


		__createLabelDiv : function(blockMeta, itemMeta)
		{
			return (
				BX.create('div', {
					props: {className: 'task-filter-popup-item-label'},
					children : [
						BX.create('span', {
							props : { className: 'task-filter-text-and' },
							text  : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_LOGIC_AND'); ?>'						}),
						BX.create('span', {
							props : { className: 'task-filter-text-or' },
							text  : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_LOGIC_OR'); ?>'
						})
					],
					events : {
						click : (function(objSelf, blockMeta){
							return function() {
								var logic = BX.Tasks.filterV2.engine.getBlockLogic(blockMeta);
								var newLogic = null;

								if (logic === 'AND')
									newLogic = 'OR';
								else
									newLogic = 'AND';

								BX.Tasks.filterV2.engine.setBlockLogic(blockMeta, newLogic);
							};
						})(this, blockMeta)
					}
				})
			);
		},


		__createItemNode : function(blockMeta, itemMeta, itemType, operation, value)
		{
			var removeNode = BX.create('div', {
				props : { className: 'task-filter-popup-item-remove' }
			});

			var nodeItemTypeSelector  = this.__createFieldsSelector(blockMeta, itemMeta, itemType);
			var nodeOperationSelector = this.__createOperationSelector(blockMeta, itemMeta, itemType, operation);

			var objValueSelector      = this.__createValueSelector(itemMeta, itemType, value);
			var nodeValueSelector     = objValueSelector.getNode();

			var node = BX.create('div', {
				props : { className: 'task-filter-popup-item-wrap' },
				children : [
					BX.create('div', {
						props : { className: 'task-filter-popup-item' },
						children : [
							nodeItemTypeSelector,
							nodeOperationSelector,
							nodeValueSelector,
							removeNode
						]
					}),					
				]
			});

			if ( ! itemMeta.hasOwnProperty('rendererIdentify') )
				itemMeta.rendererIdentify = {};

			itemMeta.rendererIdentify.domNode =	node;
			itemMeta.rendererIdentify.domNodeItemTypeSelector  = nodeItemTypeSelector;
			itemMeta.rendererIdentify.domNodeOperationSelector = nodeOperationSelector;
			itemMeta.rendererIdentify.objValueSelector         = objValueSelector;

			node.appendChild(this.__createLabelDiv(blockMeta, itemMeta));

			removeNode.onclick = (function(itemMeta, objSelf){
				return function(e) {
					if (e && e.target)
						e.target.style.display = 'none';

					objSelf.__onRemoveCondition.call(
						objSelf,
						itemMeta
					);
				}
			})(itemMeta, this);

			return (itemMeta);
		},


		setValue : function(itemMeta, newValue, params)
		{
			if (params && params.hasOwnProperty('skipRender') && (params.skipRender === true))
				return;

			itemMeta.rendererIdentify.objValueSelector.setValue(newValue);
		},


		setOperation : function(itemMeta, newOperation, params)
		{
			var itemType = oEngine.getItemType(itemMeta);

			if (params && params.hasOwnProperty('skipRender') && (params.skipRender === true))
				return;

			var oList = itemMeta.rendererIdentify.domNodeOperationSelector;

			oList.options.length = 0;

			var operationId = null;
			var operationName = null;
			var len = oEngine.manifest.Fields[itemType]['Supported operations'].length;

			for (var i = 0; i < len; i++)
			{
				operationId   = oEngine.manifest.Fields[itemType]['Supported operations'][i];
				operationName = this.__getOperationHumanReadableName(itemType, operationId);

				this.__addOption(oList, operationName, operationId, operationId == newOperation, operationId == newOperation);
			}

			if (params && params.hasOwnProperty('recreateValueSelector') && (params.recreateValueSelector === true))
			{
				var domNode = itemMeta.rendererIdentify.objValueSelector.getNode();
				delete itemMeta.rendererIdentify.objValueSelector;

				if (params && params.hasOwnProperty('selectedValue'))
					var oValueSelector = this.__createValueSelector(itemMeta, itemType, params.selectedValue);
				else
					var oValueSelector = this.__createValueSelector(itemMeta, itemType);

				domNode.parentNode.replaceChild(oValueSelector.getNode(), domNode);

				itemMeta.rendererIdentify.objValueSelector = oValueSelector;

				BX.Tasks.filterV2.engine.setValue(
					itemMeta,
					oValueSelector.getValue(),
					{}
				);
			}
		},


		setItemType : function(itemMeta, newItemType, params)
		{
			BX.Tasks.filterV2.engine.setOperation(
				itemMeta,
				this.oEngine.manifest.Fields[newItemType]['Supported operations'][0],
				{
					recreateValueSelector : true
				}
			);

			if (params && params.hasOwnProperty('skipRender') && (params.newItemType === true))
				return;
		},


		__createValueSelector : function(itemMeta, itemType, selectedValue)
		{
			var operation = oEngine.getItemOperation(itemMeta);

			if (itemType === 'STATUS')
			{
				var valuesMap = oEngine.renderer.phrases.statusesMap;
				var objValueSelector = new this.__createListValueSelector(valuesMap, itemMeta, selectedValue);
			}
			else if (itemType === 'PRIORITY')
			{
				var valuesMap = BX.clone(oEngine.renderer.phrases.prioritiesMap);
				if(selectedValue != '0') // hide low priority, otherwise left it for compatibility
				{
					valuesMap.shift();
				}

				var objValueSelector = new this.__createListValueSelector(valuesMap, itemMeta, selectedValue);
			}
			else if (
				(itemType === 'CREATED_BY')
				|| (itemType === 'RESPONSIBLE_ID')
				|| (itemType === 'ACCOMPLICE')
				|| (itemType === 'AUDITOR')
			)
			{
				var objValueSelector = new this.__createUsersSelector(itemMeta, selectedValue);
			}
			else if (itemType === 'GROUP_ID')
			{
				var objValueSelector = new this.__createGroupsSelector(itemMeta, selectedValue);
			}
			else if (this.__getDateFieldSubType(itemType, operation) !== null)	// is date field?
			{
				var dateSubtype = this.__getDateFieldSubType(itemType, operation);

				if (dateSubtype == 'no value')
					var objValueSelector = new this.__createHiddenTextValueSelector(itemMeta, selectedValue);
				else if (dateSubtype == 'raw text')
					var objValueSelector = new this.__createTextValueSelector(itemMeta, selectedValue, {placeholder : BX.message('TASKS_FILTERV2_CONSTRUCTOR_PLACEHOLDER_SET_DAYS_COUNT')});
				else
					var objValueSelector = new this.__createDateSelector(itemMeta, selectedValue);
			}
			else
				var objValueSelector = new this.__createTextValueSelector(itemMeta, selectedValue);

			return (objValueSelector);
		},


		__createTextValueSelector : function(itemMeta, selectedValue, params)
		{
			if (typeof selectedValue === 'undefined')
				var selectedValue = '';

			this.itemMeta  = itemMeta;

			var props = {
				className : 'task-filter-popup-item-part-3',
				type      : 'text',
				value     :  selectedValue
			};

			if (params && params.placeholder)
				props.placeholder = params.placeholder;

			this.node = BX.create('input', {
				props : props,
				events : {
					keyup : (function(itemMeta){
						return function(e) {
							BX.Tasks.filterV2.engine.setValue(
								itemMeta,
								this.value,
								{ skipRender : true }
							);
						}
					})(itemMeta),
					change : (function(itemMeta){
						return function(e) {
							BX.Tasks.filterV2.engine.setValue(
								itemMeta,
								this.value,
								{ skipRender : true }
							);
						}
					})(itemMeta)
				}
			});

			this.setValue = function(newValue)
			{
				this.node.value = newValue;
			};

			this.getValue = function()
			{
				return (this.node.value);
			};

			this.getNode = function()
			{
				return (this.node);
			};
		},


		__createDateSelector : function(itemMeta, selectedValue)
		{
			if (typeof selectedValue === 'undefined')
				var selectedValue = '';

			var uniqId = oEngine.renderer.__getUniqid();

			this.node = BX.create('input', {
				props : {
					id        :  uniqId,
					className : 'task-filter-popup-item-part-3',
					type      : 'text',
					value     :  selectedValue
				},
				events : {
					click: (function(uniqId){
						return function()
						{
							BX.calendar({
								node: this, 
								field: this, 
								form: '', 
								bTime: false, 
								bHideTimebar: true,
								currentTime: Math.round((new Date()) / 1000) - (new Date()).getTimezoneOffset()*60, 
								callback_after: function(value) {
									BX.Tasks.filterV2.engine.setValue(
										itemMeta,
										BX(uniqId).value,
										{ skipRender : true }
									);
								}
							});
						}
					})(uniqId)
				}
			});

			this.setValue = function(newValue, params)
			{
				this.selectedValue = newValue;
				this.node.value = newValue;
			};

			this.getValue = function()
			{
				return (this.selectedValue);
			};

			this.getNode = function()
			{
				return (this.node);
			};

			this.setValue(selectedValue);
		},


		__createHiddenTextValueSelector : function(itemMeta, selectedValue)
		{
			if (typeof selectedValue === 'undefined')
				var selectedValue = '';

			this.itemMeta  = itemMeta;

			this.node = BX.create('input', {
				props : {
					className : 'task-filter-popup-item-part-3',
					type      : 'text',
					value     :  selectedValue
				},
				style  : {
					display: 'none'
				},
				events : {
					keyup : (function(itemMeta){
						return function(e) {
							BX.Tasks.filterV2.engine.setValue(
								itemMeta,
								this.value,
								{ skipRender : true }
							);
						}
					})(itemMeta),
					change : (function(itemMeta){
						return function(e) {
							BX.Tasks.filterV2.engine.setValue(
								itemMeta,
								this.value,
								{ skipRender : true }
							);
						}
					})(itemMeta)
				}
			});

			this.setValue = function(newValue)
			{
				this.node.value = newValue;
			};

			this.getValue = function()
			{
				return (this.node.value);
			};

			this.getNode = function()
			{
				return (this.node);
			};
		},


		__createListValueSelector : function(valuesMap, itemMeta, selectedValue)
		{
			if (typeof selectedValue === 'undefined')
				var selectedValue = [-2, -1, 1, 2, 3, 7];	// STATE_IN_PROGRESS

			this.valuesMap = valuesMap;

			this.selectorValueId = 0;

			this.itemMeta  = itemMeta;

			this.node = BX.create('select', {
				props : { className : 'task-filter-popup-item-part-3' },
				children: [],
				events : {
					keyup : (function(itemMeta, objSelf){
						return function(e) {
							BX.Tasks.filterV2.engine.setValue(
								itemMeta,
								objSelf.valuesMap[this.options.selectedIndex]['value'],
								{ skipRender : true }
							);
						}
					})(itemMeta, this),
					change : (function(itemMeta, objSelf){
						return function(e) {
							BX.Tasks.filterV2.engine.setValue(
								itemMeta,
								objSelf.valuesMap[this.options.selectedIndex]['value'],
								{ skipRender : true }
							);
						}
					})(itemMeta, this)
				}
			});

			var selectorValueId = null;		// id of value in SELECT element
			var effectiveValue  = null;		// real value for filter driver
			var phrase          = null;		// phrase for human
			var isSelected      = false;
			var len = this.valuesMap.length;

			for (var i = 0; i < len; i++)
			{
				selectorValueId = i;
				effectiveValue  = this.valuesMap[i]['value'];
				phrase          = this.valuesMap[i]['phrase'];

				if (effectiveValue.toString() === selectedValue.toString())
				{
					this.selectorValueId = selectorValueId;
					isSelected = true;
				}
				else
					isSelected = false;

				BX.Tasks.filterV2.engine.renderer.__addOption(this.node, phrase, selectorValueId, isSelected, isSelected);
			}


			this.setValue = function(newValue)
			{
				var len = this.valuesMap.length;

				for (var i = 0; i < len; i++)
				{
					if (this.valuesMap[i]['value'] === newValue)
					{
						this.selectorValueId = i;
						break;
					}

					BX.Tasks.filterV2.engine.renderer.__addOption(this.node, phrase, selectorValueId, isSelected, isSelected);
				}

				this.node.options.selectedIndex = this.selectorValueId;
				this.node.options[this.node.options.selectedIndex].selected = true;
			};

			this.getValue = function()
			{
				return (this.valuesMap[this.selectorValueId]['value']);
			};

			this.getNode = function()
			{
				return (this.node);
			};
		},


		__getUniqid : function()
		{
			return ('tasks-filterv2-some-unique-per-page-id' + this.serialId++);
		},


		__createUsersSelector : function(itemMeta, selectedValue)
		{
			if (typeof selectedValue === 'undefined')
				var selectedValue = oEngine.loggedInUserId;

			var uniqId = oEngine.renderer.__getUniqid();

			this.node = BX.create('input', {
				props : {
					id        :  uniqId,
					className : 'task-filter-popup-item-part-3',
					type      : 'text',
					value     : ''
				}
			});

			var selectors = BX.Tasks.lwPopup.__initSelectors([{
				requestedObject  : 'intranet.user.selector.new',
				selectedUsersIds :  [selectedValue],
				anchorId         :  uniqId,
				bindClickTo      :  uniqId,
				userInputId      :  uniqId,
				multiple         : 'N',
				callbackOnSelect :  (function(itemMeta, objSelf){
					return function (arUser)
					{
						BX.Tasks.filterV2.engine.formattedUserNames['u' + arUser['id']] = arUser['name'];

						objSelf.setValue(arUser['id'], {skipSetSelector: true});

						BX.Tasks.filterV2.engine.setValue(
							itemMeta,
							arUser['id'],
							{ skipRender : true }
						);
					};
				})(itemMeta, this)
			}]);

			this.objSelector = selectors[0];

			this.setValue = function(newValue, params)
			{
				var params = params || {};

				var skipSetSelector = false;
				if (params.hasOwnProperty('skipSetSelector'))
					skipSetSelector = params.skipSetSelector;

				var bNeedFormatUserName = true;

				this.selectedValue =  newValue;
				var selectedValueFormattedName = '...';

				if (BX.Tasks.filterV2.engine.formattedUserNames.hasOwnProperty('u' + newValue))
				{
					selectedValueFormattedName = BX.Tasks.filterV2.engine.formattedUserNames['u' + newValue];
					bNeedFormatUserName = false;
				}

				this.node.value = selectedValueFormattedName;

				if ( ! skipSetSelector )
				{
					this.objSelector.setSelectedUsers([{
						id   : newValue,
						name : selectedValueFormattedName
					}]);
				}

				if (bNeedFormatUserName)
				{
					BX.CJSTask.formatUsersNames(
						[newValue],
						{
							callback: (function(skipSetSelector, newValue, selfObj){
								return function(arUsers) {
									selfObj.node.value = arUsers['u' + newValue];

									BX.Tasks.filterV2.engine.formattedUserNames['u' + newValue] = arUsers['u' + newValue];

									if ( ! skipSetSelector )
									{
										selfObj.objSelector.setSelectedUsers([{
											id   : newValue,
											name : arUsers['u' + newValue]
										}]);
									}
								}
							})(skipSetSelector, newValue, this)
						}
					);
				}
			};

			this.getValue = function()
			{
				return (this.selectedValue);
			};

			this.getNode = function()
			{
				return (this.node);
			};

			this.setValue(selectedValue, {skipSetSelector : false});
		},


		__createGroupsSelector : function(itemMeta, selectedValue)
		{
			if (typeof selectedValue === 'undefined')
				var selectedValue = 0;

			var uniqId = oEngine.renderer.__getUniqid();

			this.node = BX.create('input', {
				props : {
					id        :  uniqId,
					className : 'task-filter-popup-item-part-3',
					type      : 'text',
					value     : ''
				}
			});

			var selectors = BX.Tasks.lwPopup.__initSelectors([{
				requestedObject  : 'socialnetwork.group.selector',
				bindElement      :  uniqId,
				callbackOnSelect : (function(itemMeta, objSelf){
					return function (arGroups, params)
					{
						BX.Tasks.filterV2.engine.formattedGroupsNames['g' + arGroups[0]['id']] = arGroups[0]['title'];
						objSelf.setValue(arGroups[0]['id'], {skipSetSelector: true});

						BX.Tasks.filterV2.engine.setValue(
							itemMeta,
							arGroups[0]['id'],
							{ skipRender : true }
						);
					};
				})(itemMeta, this)
			}]);

			this.objSelector = selectors[0];

			this.setValue = function(newValue, params)
			{
				var params = params || {};

				var bNeedFormatName = true;

				var skipSetSelector = false;
				if (params.hasOwnProperty('skipSetSelector'))
					skipSetSelector = params.skipSetSelector;

				this.selectedValue =  newValue;
				var selectedValueFormattedName = '...';

				if (newValue == 0)
					selectedValueFormattedName = '';

				if (BX.Tasks.filterV2.engine.formattedGroupsNames.hasOwnProperty('g' + newValue))
				{
					selectedValueFormattedName = BX.Tasks.filterV2.engine.formattedGroupsNames['g' + newValue];
					bNeedFormatUserName = false;
				}

				this.node.value = selectedValueFormattedName;

				if ( ! skipSetSelector )
				{
					this.objSelector.setSelected({
						id    : newValue,
						title : selectedValueFormattedName
					});
				}

				if (bNeedFormatName && (newValue > 0))
				{
					BX.CJSTask.getGroupsData(
						[newValue], {
							callback: (function(skipSetSelector, groupId, selfObj){
								return function(arGroups) {
									var groupName = arGroups[groupId]['NAME'];

									selfObj.node.value = groupName;
									BX.Tasks.filterV2.engine.formattedGroupsNames['g' + groupId] = groupName;

									if ( ! skipSetSelector )
									{
										selfObj.objSelector.setSelected({
											id    : groupId,
											title : groupName
										});
									}
								}
							})(skipSetSelector, newValue, this)
						}
					);					
				}
			};

			this.getValue = function()
			{
				return (this.selectedValue);
			};

			this.getNode = function()
			{
				return (this.node);
			};

			this.setValue(selectedValue, {skipSetSelector : false});
		},


		__addOption : function (oListbox, text, value, isDefaultSelected, isSelected)
		{
			var oOption = document.createElement("option");
			oOption.appendChild(document.createTextNode(text));
			oOption.setAttribute("value", value);

			if (isDefaultSelected) oOption.defaultSelected = true;
			else if (isSelected) oOption.selected = true;

			oListbox.appendChild(oOption);
		},


		__createOperationSelector : function(blockMeta, itemMeta, itemType, selectedOperation)
		{
			var options = [];
			var operationId = null;
			var operationName = null;

			var len = oEngine.manifest.Fields[itemType]['Supported operations'].length;

			for (var i = 0; i < len; i++)
			{
				operationId = oEngine.manifest.Fields[itemType]['Supported operations'][i];

				options.push(
					BX.create('option', {
						props : {
							value : operationId
						},
						html  : this.__getOperationHumanReadableName(itemType, operationId)
					})
				);
			}

			if ( ! oEngine.manifest.Fields.hasOwnProperty(itemType) )
			{
				options.push(
					BX.create('option', {
						props : {
							value : operationId
						},
						html  : operationId
					})
				);
			}

			var func = (function(itemMeta){
				return function(e) {
					// don't redraw value selector by default on operation change (redraw in some cases below)
					var params = {
						skipRender : true,
						recreateValueSelector : false
					}

					var itemCodeName = oEngine.getItemType(itemMeta);
					var newOperation = this.value;
					// null will be returned for non-dates fields
					var newDateFieldSubType = oEngine.renderer.__getDateFieldSubType(itemCodeName, newOperation);

					// Is it date field?
					if (newDateFieldSubType !== null)
					{
						// Yes, so we should rerender value selector, ...
						var prevOperation = oEngine.getItemOperation(itemMeta);
						var prevDateFieldSubType = oEngine.renderer.__getDateFieldSubType(itemCodeName, prevOperation);

						// ...if subtype of date field changed
						if (newDateFieldSubType !== prevDateFieldSubType)
						{
							params = {
								skipRender : false,
								recreateValueSelector : true
							};
						}
					}

					BX.Tasks.filterV2.engine.setOperation(
						itemMeta,
						this.value,
						params
					);
				}
			})(itemMeta);

			var node = BX.create('select', {
				props : { className : 'task-filter-popup-item-part-2' },
				children: options,
				events : {
					keyup  : func,
					change : func
				}
			});

			for (var i=0, opt; opt = node.options[i]; i++)
			{
				if (opt.value == selectedOperation)
				{
					node.options.selectedIndex = i;
					opt.selected = true;
					break;
				}
			}

			if (node.options.selectedIndex >= 0)
				node.options[node.options.selectedIndex].selected = true;

			return (node);
		},


		__createFieldsSelector : function(blockMeta, itemMeta, itemType)
		{
			var options = [];

			for (var k in oEngine.manifest.Fields)
			{
				options.push(
					BX.create('option', {
						props : {
							value : k
						},
						html  : this.__getFieldHumanReadableName(k)
					})
				);
			}

			if ( ! oEngine.manifest.Fields.hasOwnProperty(itemType) )
			{
				options.push(
					BX.create('option', {
						props : {
							value : itemType
						},
						html  : itemType
					})
				);
			}

			var node = BX.create('select', {
				props : { className : 'task-filter-popup-item-part-1' },
				children: options,
				events : {
					keyup : (function(itemMeta){
						return function(e) {
							if (BX.Tasks.filterV2.engine.getItemType(itemMeta) != this.value)
							{
								BX.Tasks.filterV2.engine.setItemType(
									itemMeta,
									this.value,
									{ skipRender : true }
								);
							}
						}
					})(itemMeta),
					change : (function(itemMeta){
						return function(e) {
							if (BX.Tasks.filterV2.engine.getItemType(itemMeta) != this.value)
							{
								BX.Tasks.filterV2.engine.setItemType(
									itemMeta,
									this.value,
									{ skipRender : true }
								);
							}
						}
					})(itemMeta)
				}
			});

			for (var i=0, opt; opt = node.options[i]; i++)
			{
				if (opt.value == itemType)
				{
					node.options.selectedIndex = i;
					opt.selected = true;
					break;
				}
			}

			if (node.options.selectedIndex >= 0)
				node.options[node.options.selectedIndex].selected = true;

			return (node);
		},


		__getFieldHumanReadableName : function(fieldCodeName)
		{
			if (this.phrases.fieldsPhrases.hasOwnProperty(fieldCodeName))
				return (this.phrases.fieldsPhrases[fieldCodeName]);
			else
				return (fieldCodeName);
		},


		__getOperationHumanReadableName : function(itemType, operationId)
		{
			operationId = '' + operationId;

			if (
				this.phrases.operationsPhrasesOverride.hasOwnProperty(itemType)
				&& this.phrases.operationsPhrasesOverride[itemType].hasOwnProperty(operationId)
			)
			{
				return (this.phrases.operationsPhrasesOverride[itemType][operationId]);
			}
			else if (this.phrases.operationsPhrases.hasOwnProperty(operationId))
				return (this.phrases.operationsPhrases[operationId]);
			else
				return ('No name operation ' + operationId);
		},


		__getDateFieldSubType : function(fieldCodeName, operationId)
		{
			var len = fieldCodeName.length;

			if (len <= 3)
				return null;

			if (fieldCodeName.substr(len - 3, 3) !== '_TS')
				return null;

			if ((operationId >= 4) && (operationId <= 13))	// from OP_DATE_TODAY to OP_DATE_NEXT_MONTH
				return ('no value');
			else if ((operationId == 14) || (operationId == 15))	// OP_DATE_LAST_DAYS || OP_DATE_NEXT_DAYS
				return ('raw text');
			else
				return ('date picker');
		}
	};


	oEngine.renderer.phrases = {};

	oEngine.renderer.phrases.operationsPhrases = {
		'<?php echo (int) CTaskFilterCtrl::OP_EQUAL;             ?>' : '<?php echo GetMessageJs('TASKS_OP_EQUAL'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_NOT_EQUAL;         ?>' : '<?php echo GetMessageJs('TASKS_OP_NOT_EQUAL'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_SUBSTRING;         ?>' : '<?php echo GetMessageJs('TASKS_OP_SUBSTRING'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_NOT_SUBSTRING;     ?>' : '<?php echo GetMessageJs('TASKS_OP_NOT_SUBSTRING'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_TODAY;        ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_TODAY'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_YESTERDAY;    ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_YESTERDAY'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_TOMORROW;     ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_TOMORROW'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_CUR_WEEK;     ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_CUR_WEEK'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_PREV_WEEK;    ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_PREV_WEEK'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_NEXT_WEEK;    ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_NEXT_WEEK'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_CUR_MONTH;    ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_CUR_MONTH'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_PREV_MONTH;   ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_PREV_MONTH'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_NEXT_MONTH;   ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_NEXT_MONTH'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_LAST_DAYS;    ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_LAST_DAYS'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_DATE_NEXT_DAYS;    ?>' : '<?php echo GetMessageJs('TASKS_OP_DATE_NEXT_DAYS'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_LESS;     ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_LESS'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_GREATER;  ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_GREATER'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_LESS_OR_EQUAL;     ?>' : '<?php echo GetMessageJs('TASKS_OP_LESS_OR_EQUAL'); ?>',
		'<?php echo (int) CTaskFilterCtrl::OP_GREATER_OR_EQUAL;  ?>' : '<?php echo GetMessageJs('TASKS_OP_GREATER_OR_EQUAL'); ?>'
	};

	oEngine.renderer.phrases.operationsPhrasesOverride = {
		'META:DEADLINE_TS' : {
			'<?php echo (int) CTaskFilterCtrl::OP_EQUAL;             ?>' : '<?php echo GetMessageJs('TASKS_OP_EQUAL_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_LESS;     ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_LESS_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_GREATER;  ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_GREATER_FOR_DATE'); ?>'
		},
		'META:START_DATE_PLAN_TS' : {
			'<?php echo (int) CTaskFilterCtrl::OP_EQUAL;             ?>' : '<?php echo GetMessageJs('TASKS_OP_EQUAL_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_LESS;     ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_LESS_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_GREATER;  ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_GREATER_FOR_DATE'); ?>'
		},
		'META:END_DATE_PLAN_TS' : {
			'<?php echo (int) CTaskFilterCtrl::OP_EQUAL;             ?>' : '<?php echo GetMessageJs('TASKS_OP_EQUAL_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_LESS;     ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_LESS_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_GREATER;  ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_GREATER_FOR_DATE'); ?>'
		},
		'META:DATE_START_TS' : {
			'<?php echo (int) CTaskFilterCtrl::OP_EQUAL;             ?>' : '<?php echo GetMessageJs('TASKS_OP_EQUAL_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_LESS;     ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_LESS_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_GREATER;  ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_GREATER_FOR_DATE'); ?>'
		},
		'META:CREATED_DATE_TS' : {
			'<?php echo (int) CTaskFilterCtrl::OP_EQUAL;             ?>' : '<?php echo GetMessageJs('TASKS_OP_EQUAL_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_LESS;     ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_LESS_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_GREATER;  ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_GREATER_FOR_DATE'); ?>'
		},
		'META:CLOSED_DATE_TS' : {
			'<?php echo (int) CTaskFilterCtrl::OP_EQUAL;             ?>' : '<?php echo GetMessageJs('TASKS_OP_EQUAL_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_LESS;     ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_LESS_FOR_DATE'); ?>',
			'<?php echo (int) CTaskFilterCtrl::OP_STRICTLY_GREATER;  ?>' : '<?php echo GetMessageJs('TASKS_OP_STRICTLY_GREATER_FOR_DATE'); ?>'
		}
	};

	oEngine.renderer.phrases.fieldsPhrases = {
		'TITLE'          : '<?php echo GetMessageJs('TASKS_FIELD_TITLE'); ?>',
		'GROUP_ID'       : '<?php echo GetMessageJs('TASKS_FIELD_GROUP'); ?>',
		'CREATED_BY'     : '<?php echo GetMessageJs('TASKS_FIELD_DIRECTOR'); ?>',
		'RESPONSIBLE_ID' : '<?php echo GetMessageJs('TASKS_FIELD_RESPOSNSIBLE'); ?>',
		'ACCOMPLICE'     : '<?php echo GetMessageJs('TASKS_FIELD_ACCOMPLICE'); ?>',
		'AUDITOR'        : '<?php echo GetMessageJs('TASKS_FIELD_AUDITOR'); ?>',
		'STATUS'         : '<?php echo GetMessageJs('TASKS_FIELD_STATUS'); ?>',
		'PRIORITY'       : '<?php echo GetMessageJs('TASKS_FIELD_PRIORITY_V2'); ?>',
		'META:DEADLINE_TS'        : '<?php echo GetMessageJs('TASKS_FIELD_META:DEADLINE_TS'); ?>',
		'META:START_DATE_PLAN_TS' : '<?php echo GetMessageJs('TASKS_FIELD_META:START_DATE_PLAN_TS'); ?>',
		'META:END_DATE_PLAN_TS'   : '<?php echo GetMessageJs('TASKS_FIELD_META:END_DATE_PLAN_TS'); ?>',
		'META:DATE_START_TS'      : '<?php echo GetMessageJs('TASKS_FIELD_META:DATE_START_TS'); ?>',
		'META:CREATED_DATE_TS'    : '<?php echo GetMessageJs('TASKS_FIELD_META:CREATED_DATE_TS'); ?>',
		'META:CLOSED_DATE_TS'     : '<?php echo GetMessageJs('TASKS_FIELD_META:CLOSED_DATE_TS'); ?>'
	};


	oEngine.renderer.phrases.prioritiesMap = [
		{
			phrase : '<?=(GetMessageJs('TASKS_COMMON_NO').' '.GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_PRIORITY_LOW_V2'))?>',
			value  : <?php echo CTasks::PRIORITY_LOW; ?>
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_COMMON_NO'); ?>',
			value  : <?php echo CTasks::PRIORITY_AVERAGE; ?>
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_COMMON_YES'); ?>',
			value  : <?php echo CTasks::PRIORITY_HIGH; ?>
		}
	];


	oEngine.renderer.phrases.statusesMap = [
		{
			phrase : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_STATUS_ACTIVE'); ?>',
			value  : [
				<?php echo CTasks::METASTATE_VIRGIN_NEW; ?>,
				<?php echo CTasks::METASTATE_EXPIRED; ?>,
				<?php echo CTasks::STATE_NEW; ?>,
				<?php echo CTasks::STATE_PENDING; ?>,
				<?php echo CTasks::STATE_IN_PROGRESS; ?>,
				<?php echo CTasks::STATE_DECLINED; ?>
			]
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_STATUS_NEW'); ?>',
			value  : [
				<?php echo CTasks::METASTATE_VIRGIN_NEW; ?>,
				<?php echo CTasks::STATE_NEW; ?>
			]
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_STATUS_WAIT_CONTROL'); ?>',
			value  : <?php echo CTasks::STATE_SUPPOSEDLY_COMPLETED; ?>
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_STATUS_IN_PROGRESS'); ?>',
			value  : <?php echo CTasks::STATE_IN_PROGRESS; ?>
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_STATUS_ACCEPTED'); ?>',
			value  : <?php echo CTasks::STATE_PENDING; ?>
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_STATUS_EXPIRED'); ?>',
			value  : <?php echo CTasks::METASTATE_EXPIRED; ?>
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_STATUS_DEFERRED'); ?>',
			value  : <?php echo CTasks::STATE_DEFERRED; ?>
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_STATUS_DECLINED'); ?>',
			value  : <?php echo CTasks::STATE_DECLINED; ?>
		},
		{
			phrase : '<?php echo GetMessageJs('TASKS_FILTERV2_CONSTRUCTOR_STATUS_COMPLETE'); ?>',
			value  : <?php echo CTasks::STATE_COMPLETED; ?>
		}
	];


	BX.onCustomEvent(
		BX.Tasks.filterV2,
		'onConstructorLoad',
		[]
	);
});
</script>