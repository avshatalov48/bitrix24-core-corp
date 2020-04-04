var DragManager = new function() {

	/**
	 *	The object to store information about the transfer element.
	 *	.draggable - class element of which is enough
	 *	.draghandle - Class elements that move between them, and put a movable element
	 *	.droppable - class element which put
	 *	elem - element on which the mouse was clamped
	 *	avatar - the ability to create a copy of the item to move
	 *	downX/downY - coordinates, which was a mousedown
	 *	shiftX/shiftY - the relative shift of the cursor on the angle element
	 */

	var dragObject = {};

	var self = this;

	function checkClosest(element) {
		if(element == null) return null;
		if(!element.closest) {
			element.closest = function(css) {
				var node = this;
				while (node) {
					if (!node.matches) {
						node.matches = node.matchesSelector || node.webkitMatchesSelector ||
							node.mozMatchesSelector || node.msMatchesSelector;
					}
					if (node.matches(css)) return node;
					else node = node.parentElement;
				}
				return null;
			};
		}
		return element;
	}

	function onMouseDown(e) {

		if (e.which != 1) return;

		var target = checkClosest(e.target);
		if (!target) return;
		var elem = target.closest('.draggable');
		if (!elem) return;

		if(!elem.parentNode.className.match(/\bdraghandle\b/)) return;

		dragObject.elem = elem;

		// Remember that the element is clicked on the current coordinates pageX / pageY
		dragObject.downX = e.pageX;
		dragObject.downY = e.pageY;

		return false;
	}

	function onMouseMove(e) {
		if (!dragObject.elem) return; // element is clamped

		if (!dragObject.avatar) { // if the transfer is not started ...
			var moveX = e.pageX - dragObject.downX;
			var moveY = e.pageY - dragObject.downY;

			// If the mouse is moved down the far enough
			if (Math.abs(moveX) < 3 && Math.abs(moveY) < 3) {
				return;
			}

			// start transfer
			dragObject.avatar = createAvatar(e); // create an avatar
			if (!dragObject.avatar) { // cancel the transfer, you can not "grab" for this part of the element
				dragObject = {};
				return;
			}

			// Avatar created successfully, create an auxiliary properties shiftX / shiftY
			var coords = getCoords(dragObject.avatar);
			dragObject.shiftX = dragObject.downX - coords.left;
			dragObject.shiftY = dragObject.downY - coords.top;

			startDrag(e); // see the beginning of the transfer
		}

		// transfer display object each time the mouse moves
		dragObject.avatar.style.left = e.pageX - dragObject.shiftX + 'px';
		dragObject.avatar.style.top = e.pageY - dragObject.shiftY + 'px';

		// We find the element on which there is a movement and give external code
		var overElem = findDraghandle(e);
		if(!overElem) return false;
		self.onDragMove(dragObject, overElem, e);

		return false;
	}

	function onMouseUp(e) {
		if (dragObject.avatar) { // if the transfer is
			finishDrag(e);
		}

		// carrying either not started or completed; in any case, clear "state transfer" dragObject
		dragObject = {};
	}

	function finishDrag(e) {
		var dropElem = findDroppable(e), overElem = findDraghandle(e);
		if (!dropElem) {
			self.onDragCancel(dragObject);
		} else {
			self.onDragEnd(dragObject, dropElem, overElem);
		}
	}

	function createAvatar(e) {
		// Remember the old properties to come back to them when you cancel the transfer
		var avatar = dragObject.elem.parentNode;
		var old = {
			parent: avatar.parentNode,
			nextSibling: avatar.nextSibling,
			position: avatar.position || '',
			left: avatar.left || '',
			top: avatar.top || '',
			zIndex: avatar.zIndex || ''
		};

		// function to cancel the transfer
		avatar.rollback = function() {
			old.parent.insertBefore(avatar, old.nextSibling);
			avatar.style.position = old.position;
			avatar.style.left = old.left;
			avatar.style.top = old.top;
			avatar.style.zIndex = old.zIndex
		};

		// function to cancel styles
		avatar.styleback = function() {
			avatar.style.position = old.position;
			avatar.style.left = old.left;
			avatar.style.top = old.top;
			avatar.style.zIndex = old.zIndex
		};

		return avatar;
	}

	function startDrag(e) {
		var avatar = dragObject.avatar;

		// initiate the transfer
		document.body.appendChild(avatar);
		avatar.style.zIndex = 9999;
		avatar.style.position = 'absolute';
	}

	function findDroppable(event) {
		// hide portable item
		dragObject.avatar.hidden = true;

		// get the sub-element under the mouse cursor
		var elem = document.elementFromPoint(event.clientX, event.clientY);

		// show a portable element back
		dragObject.avatar.hidden = false;

		elem = checkClosest(elem);
		if (elem == null) {
			// This is possible if the cursor "flew" over the window border
			return null;
		}

		return elem.closest('.droppable');
	}

	function findDraghandle(event) {

		dragObject.avatar.hidden = true;
		var elem = document.elementFromPoint(event.clientX, event.clientY);
		dragObject.avatar.hidden = false;

		elem = checkClosest(elem);
		if(elem == null) return null;

		return elem.closest('.draghandle');

	}

	document.onmousemove = onMouseMove;
	document.onmouseup = onMouseUp;
	document.onmousedown = onMouseDown;

	this.onDragEnd = function(dragObject, dropElem, overElem) {};
	this.onDragCancel = function(dragObject) {};
	this.onDragMove = function(dragObject, overElem, e) {};

};


function getCoords(elem) { // besides IE8-
	var box = elem.getBoundingClientRect();

	return {
		top: box.top + pageYOffset,
		left: box.left + pageXOffset
	};

}