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
		editFeaturesAllowed: true
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