BX.namespace("BX.Disk");
BX.Disk.BreadcrumbsClass = (function ()
{

	var BreadcrumbsClass = function (parameters)
	{
		this.storageId = parameters.storageId;
		this.containerId = parameters.containerId;
		this.collapsedCrumbs = parameters.collapsedCrumbs || [];
		this.showOnlyDeleted = parameters.showOnlyDeleted || 0;
		this.enableDropdown = parameters.enableDropdown;
		this.container = BX(this.containerId);

		this.ajaxUrl = '/bitrix/components/bitrix/disk.breadcrumbs/ajax.php';

		this.container.style.opacity = 1;

		this.setEvents();
	};

	BreadcrumbsClass.prototype.setEvents = function ()
	{
		BX.bindDelegate(this.container, "click", {className: 'js-disk-breadcrumbs-arrow'}, this.onClickArrow.bind(this));

		BX.bind(this.getFirstCrumb(), 'dblclick', function (e){
			var mainCrumb = this.getFirstCrumb();
			(new BX.Disk.Tree.NavigateModal({
				id: mainCrumb.dataset.objectId,
				name: mainCrumb.dataset.objectName
			}, {})).show();

			e.preventDefault();
		}.bind(this));

		BX.bindDelegate(this.container, "click", {className: 'js-disk-breadcrumbs-folder-link'}, this.onClickBreadcrumb.bind(this));

		BX.addCustomEvent("Disk.FolderListClass:onFolderOpen", this.onOpenGridFolder.bind(this));
		BX.addCustomEvent("Disk.FolderListComponent:onFolderOpen", this.onOpenGridFolder.bind(this));
		BX.addCustomEvent("Disk.FolderListClass:onPopState", this.reloadByPath.bind(this));
		BX.addCustomEvent("Disk.FolderListComponent:onPopState", this.reloadByPath.bind(this));
		BX.addCustomEvent("Disk.FolderListClass:openFolderAfterFilter", this.reloadByPath.bind(this));
		BX.addCustomEvent("Disk.TrashCanClass:openFolderAfterFilter", this.reloadByPath.bind(this));
		BX.addCustomEvent("Disk.TrashCanClass:onPopState", this.reloadByPath.bind(this));
		BX.addCustomEvent("Disk.TrashCanClass:onFolderOpen", this.onOpenGridFolder.bind(this));
	};

	/**
	 * Get right siblings of an element.
	 *
	 * @param  {Element} elem
	 * @return {Object}
	 */
	BreadcrumbsClass.prototype.getSiblings = function (elem) {

		var siblings = [];
		var sibling = elem;
		for (; sibling; sibling = sibling.nextSibling)
		{
			if (sibling.nodeType == 1 && sibling != elem)
			{
				siblings.push(sibling);
			}
		}

		return siblings;
	};

	BreadcrumbsClass.prototype.expand = function (crumb, arrow, items)
	{
		var objectId = crumb.dataset.objectId;
		var basePath = crumb.dataset.objectParentPath;
		if (basePath == '/') {
			basePath = '';
		}
		else if (basePath.lastIndexOf('/') == basePath.length - 1) {
			basePath = basePath.substr(0, basePath.length - 1);
		}

		var dropdownElements = [];
		for (var i in items) {
			if (!items.hasOwnProperty(i)) {
				continue;
			}
			var item = items[i];
			var menuItem = {
				text: item.name,
				title: item.name,
				href: basePath + '/' + encodeURIComponent(item.uriComponent) + '/',
				dataset: {
					objectId: item.id
				},
				onclick : function(event, item){
					item.menuWindow.close();

					var crumb = BX.findParent(item.menuWindow.bindElement, {className: 'js-disk-breadcrumbs-folder'});
					this.removeRightSidedCrumbs(crumb);

				}.bind(this)
			};

			if(item.className)
			{
				menuItem.className = item.className + ' menu-popup-no-icon';
			}

			dropdownElements.push(menuItem);
		}
		BX.PopupMenu.show(
			'disk_breadcrumbs_' + objectId,
			arrow,
			dropdownElements,
			{
				autoHide: true,
				//offsetTop: 0,
				//offsetLeft:25,
				angle: {offset: 0},
				events: {
					onPopupClose: function ()
					{
					}
				}
			}
		);
	};

	BreadcrumbsClass.prototype.onClickBreadcrumb = function(event)
	{
		var crumb = event.target || event.srcElement;
		if(BX.hasClass(crumb, 'js-disk-breadcrumbs-arrow'))
		{
			return;
		}

		if(!BX.hasClass(crumb, 'js-disk-breadcrumbs-folder'))
		{
			crumb = BX.findParent(crumb, {className: 'js-disk-breadcrumbs-folder'});
		}

		this.removeRightSidedCrumbs(crumb);
		this.hideArrow(crumb);
	};

	BreadcrumbsClass.prototype.removeRightSidedCrumbs = function(crumbNode)
	{
		this.getSiblings(crumbNode).forEach(function(node){
			BX.remove(node);
		});
	};

	BreadcrumbsClass.prototype.onOpenGridFolder = function (folder, isJumpFromFilteredList)
	{
		var lastCrumb = this.getLastCrumb();

		if(lastCrumb.dataset.objectId == folder.id)
		{
			return;
		}

		if(isJumpFromFilteredList)
		{
			return;
		}

		this.appendCrumb(folder);
	};

	BreadcrumbsClass.prototype.getLastCrumb = function ()
	{
		var crumbs = this.container.querySelectorAll('.js-disk-breadcrumbs-folder');
		if(!crumbs.length)
		{
			return null;
		}

		return crumbs[crumbs.length - 1];
	};

	BreadcrumbsClass.prototype.getFirstCrumb = function ()
	{
		return this.container.querySelector('.js-disk-breadcrumbs-folder');
	};

	BreadcrumbsClass.prototype.appendCrumb = function (folder)
	{
		var newCrumb = this.createCrumb(folder);
		var lastCrumb = this.getLastCrumb();

		this.showArrow(lastCrumb);
		this.container.appendChild(newCrumb);
	};

	BreadcrumbsClass.prototype.reloadByPath = function (folder, isTrashcan)
	{
		isTrashcan = isTrashcan || false;

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'reloadBreadcrumbs'),
			data: {
				storageId: this.storageId,
				path: folder.link,
				isTrashcan: isTrashcan? 1 : 0
			},
			onsuccess: function (data)
			{
				if (!data || !data.html)
				{
					return;
				}

				var newCrumbs = BX.create('div', {
					html: data.html
				});

				var newContainer = newCrumbs.querySelector('.js-disk-breadcrumbs');
				this.container.innerHTML = newContainer.innerHTML;

			}.bind(this)
		});

	};

	BreadcrumbsClass.prototype.createCrumb = function (folder)
	{
		var mainCrumb = this.getFirstCrumb();
		var newCrumb = BX.clone(mainCrumb);
		newCrumb.setAttribute('data-object-parent-path', folder.link);
		newCrumb.setAttribute('data-is-root', '');
		newCrumb.setAttribute('data-object-id', folder.id);
		newCrumb.setAttribute('data-object-name', folder.name);

		var link = newCrumb.querySelector('.js-disk-breadcrumbs-folder-link');
		link.href = folder.link;
		link.dataset.objectId = folder.id;
		newCrumb.setAttribute('title', folder.name);
		newCrumb.setAttribute('alt', folder.name);

		this.hideArrow(newCrumb);

		BX.adjust(link, {text: folder.name});

		return newCrumb;
	};

	BreadcrumbsClass.prototype.hideArrow = function (crumbNode)
	{
		var arrow = crumbNode.querySelector('.js-disk-breadcrumbs-arrow');
		if(arrow)
		{
			arrow.style.opacity = '0';
		}
	};

	BreadcrumbsClass.prototype.showArrow = function (crumbNode)
	{
		var arrow = crumbNode.querySelector('.js-disk-breadcrumbs-arrow');
		if(arrow)
		{
			arrow.style.opacity = '';
		}
	};

	BreadcrumbsClass.prototype.onClickDots = function (event)
	{
		var arrowTarget = event.srcElement || event.target;
		BX.PopupMenu.show(
			'disk_breadcrumbs_dots',
			arrowTarget,
			this.collapsedCrumbs,
			{
				autoHide: true,
				//offsetTop: 0,
				//offsetLeft:25,
				angle: {offset: 0},
				events: {
					onPopupClose: function ()
					{
					}
				}
			}
		);

		BX.PreventDefault(event);
	};

	BreadcrumbsClass.prototype.onClickArrow = function (event)
	{
		if(!this.enableDropdown)
		{
			event.preventDefault();
			return;
		}

		var arrowTarget = event.srcElement || event.target;
		var crumb = BX.findParent(arrowTarget, {
			className: 'js-disk-breadcrumbs-folder'
		}, this.container);

		var objectId = crumb.getAttribute('data-object-id');
		var isRoot = crumb.getAttribute('data-is-root');
		if (!objectId) {
			BX.PreventDefault(event);
			return;
		}

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSubFolders'),
			data: {
				objectId: objectId,
				showOnlyDeleted: this.showOnlyDeleted? 1 : 0,
				isRoot: isRoot? 1 : 0
			},
			onsuccess: BX.delegate(function (data)
			{
				if (!data) {
					return;
				}

				this.expand(crumb, arrowTarget, data.items);
			}, this)
		});


		BX.PreventDefault(event);
	};

	return BreadcrumbsClass;
})();
