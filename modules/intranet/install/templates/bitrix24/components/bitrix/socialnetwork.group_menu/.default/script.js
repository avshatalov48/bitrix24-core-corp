(function(){

	if (!!BX.BXSGM24)
	{
		return;
	}

	BX.BXSGM24 = {
		currentUserId: null,
		groupdId: null,
		groupType: null,
		userIsMember: null,
		userIsAutoMember: null,
		userRole: null,
		isProject: null,
		favoritesValue: null,
		canInitiate: null,
		canModify: null,
		editFeaturesAllowed: true,
		pageId: null,
	};

	BX.BXSGM24.init = function(params) {

		this.currentUserId = parseInt(params.currentUserId);
		this.groupId = parseInt(params.groupId);
		this.groupType = params.groupType;
		this.userIsMember = !!params.userIsMember;
		this.userIsAutoMember = !!params.userIsAutoMember;
		this.userRole = params.userRole;
		this.isProject = !!params.isProject;
		this.isOpened = !!params.isOpened;
		this.favoritesValue = !!params.favoritesValue;
		this.canInitiate = !!params.canInitiate;
		this.canModify = !!params.canModify;
		this.pageId = params.pageId;

		if (typeof params.urls != 'undefined')
		{
			this.urls = params.urls;
		}

		this.editFeaturesAllowed = (typeof params.editFeaturesAllowed != 'undefined' ? !!params.editFeaturesAllowed : true);

		var f = BX.delegate(function(eventData){
			if (BX.type.isNotEmptyString(eventData.code))
			{
				if (BX.util.in_array(eventData.code, [ 'afterJoinRequestSend', 'afterEdit' ]))
				{
					if (BX('bx-group-menu-join-cont'))
					{
						BX('bx-group-menu-join-cont').style.display = "none";
					}
					BX.SocialnetworkUICommon.reload();
				}
				else if (BX.util.in_array(eventData.code, [ 'afterSetFavorites' ]))
				{
					var sonetGroupMenu = BX.SocialnetworkUICommon.SonetGroupMenu.getInstance();
					var favoritesValue = sonetGroupMenu.favoritesValue;

					sonetGroupMenu.setItemTitle(!favoritesValue);
					sonetGroupMenu.favoritesValue = !favoritesValue;
				}
			}
		}, this);

		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function(event) {
			if (event.getEventId() == 'sonetGroupEvent')
			{
				var eventData = event.getData();
				f(eventData)
			}
		}, this));
		BX.addCustomEvent('sonetGroupEvent', f);

		if (BX('bx-group-menu-join'))
		{
			BX.bind(BX('bx-group-menu-join'), 'click', BX.delegate(this.sendJoinRequest, this));
		}

		if (BX('bx-group-menu-settings'))
		{
			var sonetGroupMenu = BX.SocialnetworkUICommon.SonetGroupMenu.getInstance();
			sonetGroupMenu.favoritesValue = this.favoritesValue;

			BX.bind(BX('bx-group-menu-settings'), 'click', BX.delegate(function(event) {
				BX.SocialnetworkUICommon.showGroupMenuPopup({
					bindElement: BX.getEventTarget(event),
					groupId: this.groupId,
					groupType: this.groupType,
					userIsMember: this.userIsMember,
					userIsAutoMember: this.userIsAutoMember,
					userRole: this.userRole,
					isProject: this.isProject,
					isOpened: this.isOpened,
					editFeaturesAllowed: this.editFeaturesAllowed,
					perms: {
						canInitiate: this.canInitiate,
						canProcessRequestsIn: this.canProcessRequestsIn,
						canModify: this.canModify
					},
					urls: {
						requestUser: BX.message('SGMPathToRequestUser'),
						edit: BX.message('SGMPathToEdit'),
						delete: BX.message('SGMPathToDelete'),
						features: BX.message('SGMPathToFeatures'),
						members: BX.message('SGMPathToMembers'),
						requests: BX.message('SGMPathToRequests'),
						requestsOut: BX.message('SGMPathToRequestsOut'),
						userRequestGroup: BX.message('SGMPathToUserRequestGroup'),
						userLeaveGroup: BX.message('SGMPathToUserLeaveGroup'),
						copy: BX.message('SGMPathToCopy')
					}
				});

				event.preventDefault();
			}, this));
		}

		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function(event){
			if (event.getEventId() == 'sonetGroupEvent')
			{
				var eventData = event.getData();
				if (
					BX.type.isNotEmptyString(eventData.code)
					&& typeof eventData.data != 'undefined'
					&& BX.util.in_array(eventData.code, [ 'afterDelete', 'afterLeave' ])
					&& typeof eventData.data.groupId != 'undefined'
					&& parseInt(eventData.data.groupId) == this.groupId
				)
				{
					top.location.href = this.urls.groupsList;
				}
			}
		}, this));

		this.bindEvents();
	};

	BX.BXSGM24.bindEvents = function()
	{
		if (this.pageId !== 'group_tasks')
		{
			return;
		}

		try
		{
			var elements = document.getElementsByClassName("tasks_role_link");
			if (elements.length)
			{
				for (var key = 0; key < elements.length; key++)
				{
					BX.bind(elements[key], 'click', function(event) {
						event.preventDefault();

						var targetClass = event.target.className;
						var roleId = (this.dataset.id == 'view_all' ? '' : this.dataset.id);
						var url = this.dataset.url;

						if (
							(targetClass === 'main-buttons-item-sublink ' && roleId === '')
							|| targetClass === 'main-buttons-item-edit-button'
						)
						{
							return;
						}

						BX.onCustomEvent('Tasks.TopMenu:onItem', [roleId, url]);

						var elements = document.getElementsByClassName('tasks_role_link');
						if (elements.length)
						{
							for (var key = 0; key < elements.length; key++)
							{
								BX.removeClass(elements[key], 'main-buttons-item-active');
							}
						}
						BX.addClass(this, 'main-buttons-item-active');
					});
				}
			}
		}
		catch(e){}

		BX.addCustomEvent('BX.Main.Filter:apply', function(filterId, data, ctx) {
			this.onFilterApply(filterId, data, ctx);
		}.bind(this));
	};

	BX.BXSGM24.onFilterApply = function(filterId, data, ctx)
	{
		try
		{
			var roleId = ctx.getFilterFieldsValues().ROLEID;
			var el = document.querySelectorAll('.tasks_role_link');

			for (var i = 0; i < el.length; i++)
			{
				BX.removeClass(el[i], 'main-buttons-item-active');
			}

			if (
				typeof roleId === 'undefined'
				|| !roleId
			)
			{
				roleId = 'view_role_view_all';
			}

			BX.addClass(BX('group_panel_menu_' + this.groupId + '_' + roleId), 'main-buttons-item-active');

			var toolbar = BX.Tasks.Component.TasksToolbar.getInstance();
			if (toolbar)
			{
				toolbar.rerender(roleId);
			}
		}
		catch (e)
		{

		}
	};

	BX.BXSGM24.sendJoinRequest = function(event)
	{
		var button = event.currentTarget;

		BX.SocialnetworkUICommon.showButtonWait(button);

		BX.ajax.runAction('socialnetwork.api.usertogroup.join', {
			data: {
				params: {
					groupId: this.groupId
				}
			}
		}).then(function(response) {
			BX.SocialnetworkUICommon.hideButtonWait(button);

			if (
				response.data.success
				&& BX.type.isNotEmptyString(this.urls.group)
			)
			{
				BX.onCustomEvent(window.top, 'sonetGroupEvent', [ {
					code: 'afterJoinRequestSend',
					data: {
						groupId: this.groupId
					}
				} ]);

				top.location.href = this.urls.group;
			}
		}.bind(this), function(response) {
			BX.SocialnetworkUICommon.hideButtonWait(button);
		});
	};
})();