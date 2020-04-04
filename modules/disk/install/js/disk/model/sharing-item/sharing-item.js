(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Model
	 */
	BX.namespace("BX.Disk.Model");

	/**
	 *
	 * @param {object} parameters
	 * @extends {BX.Disk.Model.Item}
	 * @constructor
	 */
	BX.Disk.Model.SharingItem = function(parameters)
	{
		BX.Disk.Model.Item.apply(this, arguments);

		this.templateId = this.templateId || 'sharing-item';
	};

	BX.Disk.Model.SharingItem.prototype =
	{
		__proto__: BX.Disk.Model.Item.prototype,
		constructor: BX.Disk.Model.SharingItem,

		bindEvents: function ()
		{
			var container = this.getContainer();

			var deleteButton = this.getEntity(container, 'sharing-item-delete');
			if (deleteButton)
			{
				BX.bind(deleteButton, 'click', this.handleDeleteSharing.bind(this));
			}

			var changeRightButton = this.getEntity(container, 'sharing-item-change-right');
			if (changeRightButton)
			{
				BX.bind(changeRightButton, 'click', this.handleChangeRight.bind(this));
			}

			BX.addCustomEvent("Disk.FileView:onUnSelectSelectorItem", this.removeOnUnSelect.bind(this));
		},

		removeOnUnSelect: function(selectorData)
		{
			if(selectorData.item.id === this.state.entity.id)
			{
				this.remove();
				this.deleteSharing();
			}
		},

		deleteSharing: function()
		{
			if (this.state.sharing.id)
			{
				BX.ajax.runAction('disk.api.sharing.delete', {
					data: {
						sharingId: this.state.sharing.id
					}
				}).then(function (response) {

					if (BX.UI.SelectorManager)
					{
						var selectorInstance = BX.UI.SelectorManager.instances['disk-file-add-sharing'];
						if (selectorInstance)
						{
							selectorInstance.getRenderInstance().deleteItem({
								entityType: this.state.entity.type,
								itemId: this.state.entity.id
							});
						}
					}
					else
					{
						BX.SocNetLogDestination.deleteItem(this.state.entity.id, this.state.entity.type, 'disk-file-add-sharing');
					}
				}.bind(this));
			}
		},

		handleDeleteSharing: function(event)
		{
			this.remove();
			this.deleteSharing();
		},

		changeRight: function(event, item)
		{
			item.getMenuWindow().close();

			this.state.sharing.taskName = item.taskName;
			this.state.sharing.name = BX.Disk.getRightLabelByTaskName(item.taskName);

			this.render();
			this.save();
		},

		handleChangeRight: function(event)
		{
			var target = BX.getEventTarget(event);
			var popupMenuId = 'menu-rights' + this.state.id;
			var onclick = this.changeRight.bind(this);

			BX.PopupMenu.show(popupMenuId, BX(target), [
					(pseudoCompareTaskName(this.state.sharing.maxTaskName, 'disk_access_read') >= 0? {
						text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_READ'),
						className: 'disk-detail-user-access',
						taskName: 'disk_access_read',
						onclick: onclick
					} : null),
					(pseudoCompareTaskName(this.state.sharing.maxTaskName, 'disk_access_add') >= 0? {
						text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_ADD'),
						className: 'disk-detail-user-access',
						taskName: 'disk_access_add',
						onclick: onclick
					} : null),
					(pseudoCompareTaskName(this.state.sharing.maxTaskName, 'disk_access_edit') >= 0? {
						text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_EDIT'),
						className: 'disk-detail-user-access',
						taskName: 'disk_access_edit',
						onclick: onclick
					} : null),
					(pseudoCompareTaskName(this.state.sharing.maxTaskName, 'disk_access_full') >= 0? {
						text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_FULL'),
						className: 'disk-detail-user-access',
						taskName: 'disk_access_full',
						onclick: onclick
					} : null)
				],
				{
					angle: true,
					autoHide : true,
					className: 'disk-detail-user-access-popup',
					offsetLeft: 20,
					overlay: {
						opacity: 0.01
					},
					zIndex: (BX.getClass('BX.SidePanel.Instance') && BX.SidePanel.Instance.isOpen())? BX.SidePanel.Instance.getTopSlider().getZindex() : null,
					events: {
						onPopupClose: function() {BX.PopupMenu.destroy(popupMenuId);}
					}
				}
			);

		},

		save: function ()
		{
			BX.ajax.runAction('disk.api.sharing.changeTaskName', {
				data: {
					sharingId: this.state.sharing.id,
					newTaskName: this.state.sharing.taskName
				}
			}).then(function (response) {});
		}
	};

	/**
	 *
	 * @param {object} parameters
	 * @extends {BX.Disk.Model.SharingItem}
	 * @constructor
	 */
	BX.Disk.Model.DraftSharingItem = function(parameters)
	{
		BX.Disk.Model.SharingItem.apply(this, arguments);

		if (!this.state.sharing)
		{
			this.state.sharing = {
				canDelete: true,
				canChange: true,
				name: BX.Disk.getRightLabelByTaskName('disk_access_read'),
				taskName: 'disk_access_read',
				maxTaskName: 'disk_access_full'
			};
		}
	};

	BX.Disk.Model.DraftSharingItem.prototype =
	{
		__proto__: BX.Disk.Model.Item.prototype,
		constructor: BX.Disk.Model.DraftSharingItem,

		getAdditionalContainerClasses: function()
		{
			return [
				'disk-item-container-disabled'
			];
		},

		save: function ()
		{
			BX.ajax.runAction('disk.api.file.addSharing', {
				data: {
					fileId: this.state.object.id,
					entity: this.state.entity.id,
					taskName: this.state.sharing.taskName
				}
			}).then(function (response) {
				if (response.data && response.data.sharing.id)
				{
					this.state.sharing.id = response.data.sharing.id;

					this.replaceByRealItem();

					var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
					if (sliderByWindow)
					{
						BX.SidePanel.Instance.postMessageAll(window, 'Disk.File:onAddSharing', {
							objectId: this.state.object.id
						});
					}
				}
			}.bind(this));
		},

		replaceByRealItem: function()
		{
			var savedSharingItem = new BX.Disk.Model.SharingItem({
				state: this.state
			});

			savedSharingItem.render();
			this.getContainer().parentNode.replaceChild(savedSharingItem.getContainer(), this.getContainer());
			this.remove();
		}
	};

	function pseudoCompareTaskName(taskName1, taskName2)
	{
		var taskName1Pos;
		var taskName2Pos;
		switch (taskName1)
		{
			case 'disk_access_read':
				taskName1Pos = 2;
				break;
			case 'disk_access_add':
				taskName1Pos = 3;
				break;
			case 'disk_access_edit':
				taskName1Pos = 4;
				break;
			case 'disk_access_full':
				taskName1Pos = 5;
				break;
			default:
				//unknown task names
				return 0;
		}
		switch (taskName2)
		{
			case 'disk_access_read':
				taskName2Pos = 2;
				break;
			case 'disk_access_add':
				taskName2Pos = 3;
				break;
			case 'disk_access_edit':
				taskName2Pos = 4;
				break;
			case 'disk_access_full':
				taskName2Pos = 5;
				break;
			default:
				//unknown task names
				return 0;
		}
		if (taskName1Pos == taskName2Pos)
		{
			return 0;
		}

		return taskName1Pos > taskName2Pos ? 1 : -1;
	}
})();