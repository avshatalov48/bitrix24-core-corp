'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetMemberSelectorProjectLink != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetMemberSelectorProjectLink = BX.Tasks.Component.extend({
		sys: {
			code: 'ms-plink'
		},
		options: {
			entityRoute: ''
		},
		methods: {

			bindEvents: function()
			{
				this.bindDelegateControl('deselect', 'click', this.onDeSelect.bind(this));
				this.bindDelegateControl('open-form', 'click', this.onOpenForm.bind(this));
			},

			onDeSelect: function()
			{
				this.setItem(0);
			},

			setItem: function(id, text)
			{
				id = parseInt(id);

				if(id)
				{
					BX.removeClass(this.control('item'), 'invisible');
					BX.addClass(this.control('open-form'), 'invisible');

					this.control('item-link').innerHTML = BX.util.htmlspecialchars(text);
					this.control('item-link').setAttribute('href', this.option('path').SG.toString().replace('{{ID}}', id));
				}
				else
				{
					BX.addClass(this.control('item'), 'invisible');
					BX.removeClass(this.control('open-form'), 'invisible');
				}

				this.saveId(id);
			},

			onOpenForm: function()
			{
				this.getSelector().open();
			},

			saveId: function(groupId)
			{
				var prefix = this.option('entityRoute');
				if (!prefix)
				{
					return;
				}
				
				var entityId = this.option('entityId');
				groupId = parseInt(groupId);

				this.callRemote(prefix+'.update', {
					id: entityId,
					data: {
						GROUP_ID: groupId
					}
				}, {}, function(){
					BX.Tasks.Util.fireGlobalTaskEvent(
						'UPDATE', 
						{ID: entityId}, 
						{STAY_AT_PAGE: true}, 
						{id: entityId}
					);
					BX.onCustomEvent(this, 'onChangeProjectLink', [groupId, entityId]);
				});
			},

			onSelectorItemSelected: function(data)
			{
				this.setItem(data.id, BX.util.htmlspecialcharsback(data.nameFormatted));

				// deselect it again
				this.getSelector().close();
				this.getSelector().deselectItem(data.id);
			},

			getSelector: function()
			{
				return this.subInstance('socnet', function(){
					var selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
						scope: this.control('open-form'),
						id: this.id()+'socnet-sel',
						mode: 'group',
						query: this.getQuery(),
						useSearch: true,
						useAdd: false,
						controlBind: this.option('controlBind'),
						parent: this,
						popupOffsetTop: 5,
						popupOffsetLeft: 40
					});
					selector.bindEvent('item-selected', this.onSelectorItemSelected.bind(this));

					return selector;
				});
			}
		}
	});

}).call(this);