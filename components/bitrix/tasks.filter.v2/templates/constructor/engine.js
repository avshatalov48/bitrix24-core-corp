if (BX.Tasks.filterV2)
{
	BX.Tasks.filterV2.engine = {
		// This block of vars will be inited in template
		manifest          : null,
		operationsPhrases : null,
		fieldsPhrases     : null,
		objForm           : null,
		renderer          : null,


		preset : {
			id        : null,
			name      : null,
			condition : null
		},


		recreateRootBlock : function()
		{
			var meta = {
				engineIdentify : {
					parentItem :  this.preset,
					key        : 'condition'
				}
			};

			delete this.preset.condition;
			this.preset.condition = {};
			this.preset['id']   = null;
			this.preset['name'] = null;

			meta = this.renderer.recreateRootBlock(meta);

			return(meta);
		},


		getBlockLogic : function(blockMeta)
		{
			oLinkToCondition = blockMeta.engineIdentify.parentItem[blockMeta.engineIdentify.key];

			if (oLinkToCondition['::LOGIC'] == 'AND')
				return 'AND';
			else
				return 'OR';
		},


		setBlockLogic : function(blockMeta, newLogic)
		{
			oLinkToCondition = blockMeta.engineIdentify.parentItem[blockMeta.engineIdentify.key];

			if (newLogic === 'AND')
				oLinkToCondition['::LOGIC'] = 'AND';
			else
				oLinkToCondition['::LOGIC'] = 'OR';

			this.renderer.setBlockLogic(blockMeta, newLogic);
		},


		setFilterName : function(meta, newName, params)
		{
			this.preset['name'] = newName;
			this.renderer.setFilterName(meta, newName, params);
		},


		setFilterId : function(meta, newId)
		{
			this.preset['id'] = newId;
			this.renderer.setFilterId(meta, newId);
		},


		createBlock : function(parentBlockMeta)
		{
			oLinkToCondition = parentBlockMeta.engineIdentify.parentItem[parentBlockMeta.engineIdentify.key];

			var i = 0;
			var blockKey = '';
			while (++i)
			{
				blockKey = '::SUBFILTER-' + i;
				if ( ! oLinkToCondition.hasOwnProperty(blockKey) )
				{
					oLinkToCondition[blockKey] = {};
					break;
				}
			}

			var meta = {
				engineIdentify : {
					parentItem : oLinkToCondition,
					key        : blockKey
				}
			};

			meta = this.renderer.createBlock(meta, parentBlockMeta);

			return (meta);
		},


		removeCondition : function(meta)
		{
			var parentCondition = meta.engineIdentify.parentItem;
			var index           = meta.engineIdentify.key;
			delete parentCondition[index];

			this.renderer.removeCondition(meta);
		},


		editPreset : function(presetId)
		{
			this.getPresetDefinition(
				presetId,
				(function(presetId){
					return function(reply){
						this.__implementPresetData(presetId, reply.reply.presetData);
					}
				})(presetId)
			);
		},


		createPreset : function()
		{
			this.__implementPresetData(
				null,
				{
					Name      : '',
					Condition : {
						'::LOGIC' : 'AND'
					}
				}
			);
		},


		__implementPresetData : function(presetId, presetData)
		{
			this.renderer.startTransaction();

			var rootBlockMeta = this.recreateRootBlock();

			this.setBlockLogic(rootBlockMeta, presetData.Condition['::LOGIC']);
			this.setFilterName({}, presetData.Name);
			this.setFilterId({}, presetId);

			var allConditions = presetData.Condition;

			for (var k in allConditions)
			{
				if (k === '::LOGIC')
					continue;

				if (k.substr(0, 12) === '::SUBFILTER-')
				{
					var blockMeta = this.createBlock(rootBlockMeta);
					this.setBlockLogic(blockMeta, allConditions[k]['::LOGIC']);

					for (var i in allConditions[k])
					{
						if (i === '::LOGIC')
							continue;

						this.addCondition(
							blockMeta,
							allConditions[k][i]['field'], 
							allConditions[k][i]['operation'],
							allConditions[k][i]['value']
						);
					}

					continue;
				}

				this.addCondition(
					rootBlockMeta,
					allConditions[k]['field'], 
					allConditions[k]['operation'],
					allConditions[k]['value']
				);
			}

			this.renderer.commit();
		},


		addCondition : function(blockMeta, itemType, operation, value)
		{
			oLinkToConditionBlock = blockMeta.engineIdentify.parentItem[blockMeta.engineIdentify.key];

			var i = 0;
			while (++i)
			{
				if ( ! oLinkToConditionBlock.hasOwnProperty('' + i) )
				{
					oLinkToConditionBlock['' + i] = {
						'field'     : itemType,
						'operation' : operation,
						'value'     : value
					};
					oLinkToCondition = oLinkToConditionBlock['' + i];
					break;
				}
			}

			var itemMeta = {
				engineIdentify : {
					parentItem : oLinkToConditionBlock,
					key        : '' + i
				}
			};

			itemMeta = this.renderer.addCondition(blockMeta, itemMeta, itemType, operation, value);

			return (itemMeta);
		},


		setValue : function(itemMeta, newValue, params)
		{
			var parentCondition = itemMeta.engineIdentify.parentItem;
			var index           = itemMeta.engineIdentify.key;
			parentCondition[index]['value'] = newValue;

			this.renderer.setValue(itemMeta, newValue, params);
		},


		setOperation : function(itemMeta, newOperation, params)
		{
			var parentCondition = itemMeta.engineIdentify.parentItem;
			var index           = itemMeta.engineIdentify.key;
			parentCondition[index]['operation'] = newOperation;

			this.renderer.setOperation(itemMeta, newOperation, params);
		},


		setItemType : function(itemMeta, newItemType, params)
		{
			var parentCondition = itemMeta.engineIdentify.parentItem;
			var index           = itemMeta.engineIdentify.key;
			parentCondition[index]['field'] = newItemType;

			this.renderer.setItemType(itemMeta, newItemType, params);
		},


		savePresetData : function(callback)
		{
			if (this.preset['condition'] === null)
				throw Error('no condition!');

			if (this.preset['name'].replace(/^\s+|\s+$/g, '') == '')
			{
				callback.call(
					this,
					{
						status : 'fail',
						reply  : 'no name given'
					}
				);

				return;
			}

			var postData = {
				action     : 'createPreset',
				presetData : {
					Name : this.preset['name'],
					Condition : this.preset['condition']
				}
			}

			if (this.preset['id'] > 0)
			{
				postData['presetId'] = this.preset['id'];
				postData['action']   = 'replacePreset';
			}

			this.__requestAction(postData, callback);
		},


		getPresetDefinition : function(presetId, callback)
		{
			this.__requestAction(
				{
					action   : 'getPresetDefinition',
					presetId :  presetId
				},
				callback
			);
		},


		getItemType : function(itemMeta)
		{
			var parentCondition = itemMeta.engineIdentify.parentItem;
			var index           = itemMeta.engineIdentify.key;

			return (parentCondition[index]['field']);
		},


		getItemOperation : function(itemMeta)
		{
			var parentCondition = itemMeta.engineIdentify.parentItem;
			var index           = itemMeta.engineIdentify.key;

			return (parentCondition[index]['operation']);
		},


		removeCurrentPreset : function(callback)
		{
			if (this.preset['id'] < 1)
				callback(false);

			this.__removePreset(this.preset['id'], callback);
		},


		__createPreset : function(presetData, callback)
		{
			this.__requestAction(
				{
					action     : 'createPreset',
					presetData :  presetData
				},
				callback
			);
		},


		__replacePreset : function(presetId, presetData, callback)
		{
			this.__requestAction(
				{
					action     : 'replacePreset',
					presetId   :  presetId,
					presetData :  presetData
				},
				callback
			);
		},


		__removePreset : function(presetId, callback)
		{
			this.__requestAction(
				{
					action   : 'removePreset',
					presetId :  presetId
				},
				callback
			);
		},


		__requestAction : function(postData, callback)
		{
			postData['sessid'] = BX.message('bitrix_sessid');

			BX.ajax({
				method      : 'POST',
				dataType    : 'json',
				url         : '/bitrix/components/bitrix/tasks.filter.v2/ajax.php',
				data        :  postData,
				processData :  true,
				onsuccess   : (function(callback, objSelf){
					return function(reply){
						callback.call(objSelf, reply);
					}
				})(callback, this)
			});
		}
	};
}
