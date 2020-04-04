this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var TreeItem =
	/*#__PURE__*/
	function () {
	  babelHelpers.createClass(TreeItem, null, [{
	    key: "generateUniqueNodeId",
	    value: function generateUniqueNodeId() {
	      return Math.random().toString(36).substr(2, 9);
	    }
	  }]);

	  function TreeItem() {
	    babelHelpers.classCallCheck(this, TreeItem);
	    this.setNodeId(TreeItem.generateUniqueNodeId());
	    this.setParent(null);
	  }

	  babelHelpers.createClass(TreeItem, [{
	    key: "getRootNode",
	    value: function getRootNode() {
	      var parent = this;

	      while (parent.getParent() !== null) {
	        parent = parent.getParent();
	      }

	      return parent;
	    }
	  }, {
	    key: "getNodeId",
	    value: function getNodeId() {
	      return this.nodeId;
	    }
	  }, {
	    key: "setNodeId",
	    value: function setNodeId(nodeId) {
	      this.nodeId = nodeId;
	    }
	  }, {
	    key: "getParent",
	    value: function getParent() {
	      return this.parent;
	    }
	  }, {
	    key: "setParent",
	    value: function setParent(parent) {
	      this.parent = parent;
	    }
	  }]);
	  return TreeItem;
	}();

	var CompositeTreeItem =
	/*#__PURE__*/
	function (_TreeItem) {
	  babelHelpers.inherits(CompositeTreeItem, _TreeItem);

	  function CompositeTreeItem() {
	    var _this;

	    babelHelpers.classCallCheck(this, CompositeTreeItem);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CompositeTreeItem).call(this));
	    _this.descendants = [];
	    return _this;
	  }

	  babelHelpers.createClass(CompositeTreeItem, [{
	    key: "add",
	    value: function add(item) {
	      var position = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      item.setParent(this);

	      if (position === null) {
	        this.descendants.push(item);
	      } else {
	        this.descendants.splice(position, 0, item);
	      }
	    }
	  }, {
	    key: "addAfter",
	    value: function addAfter(item, after) {
	      var index = this.descendants.findIndex(function (descendant) {
	        return descendant === after;
	      });

	      if (index !== -1) {
	        this.add(item, index + 1);
	      }
	    }
	  }, {
	    key: "addBefore",
	    value: function addBefore(item, before) {
	      var index = this.descendants.findIndex(function (descendant) {
	        return descendant === before;
	      });

	      if (index !== -1) {
	        this.add(item, index);
	      }
	    }
	  }, {
	    key: "remove",
	    value: function remove(item) {
	      var index = this.descendants.findIndex(function (descendant) {
	        return descendant === item;
	      });

	      if (index !== -1) {
	        this.descendants.splice(index, 1);
	      }
	    }
	  }, {
	    key: "getDescendants",
	    value: function getDescendants() {
	      return this.descendants;
	    }
	  }, {
	    key: "getDescendantsCount",
	    value: function getDescendantsCount() {
	      return this.descendants.length;
	    }
	  }, {
	    key: "getFirstDescendant",
	    value: function getFirstDescendant() {
	      if (this.descendants.length > 0) {
	        return this.descendants[0];
	      }

	      return false;
	    }
	  }, {
	    key: "getLastDescendant",
	    value: function getLastDescendant() {
	      if (this.descendants.length > 0) {
	        return this.descendants[this.descendants.length - 1];
	      }

	      return false;
	    }
	  }, {
	    key: "findChild",
	    value: function findChild(nodeId) {
	      if (this.getNodeId().toString() === nodeId.toString()) {
	        return this;
	      }

	      var found = null;
	      this.descendants.forEach(function (descendant) {
	        if (found === null) {
	          found = descendant.findChild(nodeId);
	        }
	      });
	      return found;
	    }
	  }, {
	    key: "countTreeSize",
	    value: function countTreeSize() {
	      var size = this.getDescendantsCount();
	      this.descendants.forEach(function (descendant) {
	        size += descendant.countTreeSize();
	      });
	      return size;
	    }
	  }, {
	    key: "getTreeSize",
	    value: function getTreeSize() {
	      return this.getRootNode().countTreeSize() + 1;
	    }
	  }]);
	  return CompositeTreeItem;
	}(TreeItem);

	var CheckListItemFields =
	/*#__PURE__*/
	function () {
	  babelHelpers.createClass(CheckListItemFields, null, [{
	    key: "snakeToCamelCase",
	    value: function snakeToCamelCase(string) {
	      var camelCaseString = string;

	      if (main_core.Type.isString(camelCaseString)) {
	        camelCaseString = camelCaseString.toLowerCase();
	        camelCaseString = camelCaseString.replace(/[-_\s]+(.)?/g, function (match, chr) {
	          return chr ? chr.toUpperCase() : '';
	        });
	        return camelCaseString.substr(0, 1).toLowerCase() + camelCaseString.substr(1);
	      }

	      return camelCaseString;
	    }
	  }, {
	    key: "camelToSnakeCase",
	    value: function camelToSnakeCase(string) {
	      var snakeCaseString = string;

	      if (main_core.Type.isString(snakeCaseString)) {
	        snakeCaseString = snakeCaseString.replace(/(.)([A-Z])/g, '$1_$2').toUpperCase();
	      }

	      return snakeCaseString;
	    }
	  }]);

	  function CheckListItemFields(fields) {
	    babelHelpers.classCallCheck(this, CheckListItemFields);
	    this.fields = ['id', 'copiedId', 'parentId', 'title', 'sortIndex', 'displayTitle', 'displaySortIndex', 'isComplete', 'isImportant', 'isSelected', 'isCollapse', 'completedCount', 'totalCount', 'members', 'attachments'];
	    this.id = null;
	    this.parentId = null;
	    this.title = '';
	    this.sortIndex = 0;
	    this.displayTitle = '';
	    this.displaySortIndex = '';
	    this.isComplete = false;
	    this.isImportant = false;
	    this.isSelected = false;
	    this.isCollapse = false;
	    this.completedCount = 0;
	    this.totalCount = 0;
	    this.members = new Map();
	    this.attachments = {};
	    this.setFields(fields);
	  }

	  babelHelpers.createClass(CheckListItemFields, [{
	    key: "setFields",
	    value: function setFields(fields) {
	      var _this = this;

	      Object.keys(fields).forEach(function (name) {
	        var camelCaseName = CheckListItemFields.snakeToCamelCase(name);

	        if (_this.fields.indexOf(name) !== -1) {
	          var snakeCaseName = CheckListItemFields.camelToSnakeCase(name);

	          var setMethod = _this[CheckListItemFields.snakeToCamelCase("SET_".concat(snakeCaseName))].bind(_this);

	          setMethod(fields[name]);
	        } else if (_this.fields.indexOf(camelCaseName) !== -1) {
	          var _setMethod = _this[CheckListItemFields.snakeToCamelCase("SET_".concat(name))].bind(_this);

	          _setMethod(fields[name]);
	        }
	      });
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "setId",
	    value: function setId(id) {
	      this.id = id;
	    }
	  }, {
	    key: "getCopiedId",
	    value: function getCopiedId() {
	      return this.copiedId;
	    }
	  }, {
	    key: "setCopiedId",
	    value: function setCopiedId(copiedId) {
	      this.copiedId = copiedId;
	    }
	  }, {
	    key: "getParentId",
	    value: function getParentId() {
	      return this.parentId;
	    }
	  }, {
	    key: "setParentId",
	    value: function setParentId(parentId) {
	      this.parentId = parentId;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.title;
	    }
	  }, {
	    key: "setTitle",
	    value: function setTitle(title) {
	      this.title = title;
	    }
	  }, {
	    key: "getSortIndex",
	    value: function getSortIndex() {
	      return this.sortIndex;
	    }
	  }, {
	    key: "setSortIndex",
	    value: function setSortIndex(sortIndex) {
	      this.sortIndex = sortIndex;
	    }
	  }, {
	    key: "getDisplayTitle",
	    value: function getDisplayTitle() {
	      return this.displayTitle;
	    }
	  }, {
	    key: "setDisplayTitle",
	    value: function setDisplayTitle(displayTitle) {
	      this.displayTitle = displayTitle;
	    }
	  }, {
	    key: "getDisplaySortIndex",
	    value: function getDisplaySortIndex() {
	      return this.displaySortIndex;
	    }
	  }, {
	    key: "setDisplaySortIndex",
	    value: function setDisplaySortIndex(displaySortIndex) {
	      this.displaySortIndex = displaySortIndex;
	    }
	  }, {
	    key: "getIsComplete",
	    value: function getIsComplete() {
	      return this.isComplete;
	    }
	  }, {
	    key: "setIsComplete",
	    value: function setIsComplete(isComplete) {
	      this.isComplete = isComplete;
	    }
	  }, {
	    key: "getIsImportant",
	    value: function getIsImportant() {
	      return this.isImportant;
	    }
	  }, {
	    key: "setIsImportant",
	    value: function setIsImportant(isImportant) {
	      this.isImportant = isImportant;
	    }
	  }, {
	    key: "getIsSelected",
	    value: function getIsSelected() {
	      return this.isSelected;
	    }
	  }, {
	    key: "setIsSelected",
	    value: function setIsSelected(isSelected) {
	      this.isSelected = isSelected;
	    }
	  }, {
	    key: "getIsCollapse",
	    value: function getIsCollapse() {
	      return this.isCollapse;
	    }
	  }, {
	    key: "setIsCollapse",
	    value: function setIsCollapse(isCollapse) {
	      this.isCollapse = isCollapse;
	    }
	  }, {
	    key: "getCompletedCount",
	    value: function getCompletedCount() {
	      return this.completedCount;
	    }
	  }, {
	    key: "setCompletedCount",
	    value: function setCompletedCount(completedCount) {
	      this.completedCount = completedCount;
	    }
	  }, {
	    key: "getTotalCount",
	    value: function getTotalCount() {
	      return this.totalCount;
	    }
	  }, {
	    key: "setTotalCount",
	    value: function setTotalCount(totalCount) {
	      this.totalCount = totalCount;
	    }
	  }, {
	    key: "getMembers",
	    value: function getMembers() {
	      return this.members;
	    }
	  }, {
	    key: "setMembers",
	    value: function setMembers(members) {
	      var _this2 = this;

	      var types = {
	        A: 'accomplice',
	        U: 'auditor'
	      };
	      this.members.clear();
	      Object.keys(members).forEach(function (id) {
	        var _members$id = members[id],
	            NAME = _members$id.NAME,
	            TYPE = _members$id.TYPE;

	        _this2.members.set(id, {
	          id: id,
	          nameFormatted: main_core.Text.encode(NAME),
	          type: types[TYPE]
	        });
	      });
	    }
	  }, {
	    key: "addMember",
	    value: function addMember(member) {
	      this.members.set(member.id, member);
	    }
	  }, {
	    key: "removeMember",
	    value: function removeMember(id) {
	      this.members.delete(id);
	    }
	  }, {
	    key: "getAttachments",
	    value: function getAttachments() {
	      return this.attachments;
	    }
	  }, {
	    key: "setAttachments",
	    value: function setAttachments(attachments) {
	      this.attachments = attachments;
	    }
	  }, {
	    key: "addAttachments",
	    value: function addAttachments(attachments) {
	      var _this3 = this;

	      Object.keys(attachments).forEach(function (id) {
	        _this3.attachments[id] = attachments[id];
	      });
	    }
	  }, {
	    key: "removeAttachment",
	    value: function removeAttachment(id) {
	      delete this.attachments[id];
	    }
	  }]);
	  return CheckListItemFields;
	}();

	function _templateObject31() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"checklist-item\" id=\"", "\">\n\t\t\t\t<div class=\"checklist-item-inner droppable ", "\"\n\t\t\t\t\t onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"checklist-item-flag-block\">\n\t\t\t\t\t\t<div class=\"checklist-item-flag\">\n\t\t\t\t\t\t\t<label class=\"checklist-item-flag-element\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t<span class=\"checklist-item-flag-sub-checklist-progress\" id=\"progress_", "\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span class=\"checklist-item-flag-element-decorate\"/>\n\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"checklist-item-content-block\">\n\t\t\t\t\t\t<div class=\"checklist-item-number\">", "</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"checklist-item-description\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"checklist-item-additional-block\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"checklist-item-actions-block\">\n\t\t\t\t\t\t<input class=\"checklist-item-group-checkbox\" id=\"select_", "\" type=\"checkbox\"\n\t\t\t\t\t\t\t   onclick=\"", "\"/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"checklist-item-attachment\">\n\t\t\t\t\t<div class=\"checklist-item-attachment-list\" id=\"attachments_", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"checklist-sublist-items-wrapper\" id=\"subItems_", "\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject31 = function _templateObject31() {
	    return data;
	  };

	  return data;
	}

	function _templateObject30() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"checklist-item-dragndrop\"></div>"]);

	  _templateObject30 = function _templateObject30() {
	    return data;
	  };

	  return data;
	}

	function _templateObject29() {
	  var data = babelHelpers.taggedTemplateLiteral(["<button class=\"checklist-item-remove\" onclick=\"", "\"/>"]);

	  _templateObject29 = function _templateObject29() {
	    return data;
	  };

	  return data;
	}

	function _templateObject28() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"checklist-wrapper checklist-item-group-editor-collapse\" id=\"", "\">\n\t\t\t\t<div class=\"checklist-header-wrapper droppable\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"checklist-header-block\">\n\t\t\t\t\t\t<div class=\"checklist-header-inner\">\n\t\t\t\t\t\t\t<div class=\"checklist-header-name\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"checklist-header-progress-block\">\n\t\t\t\t\t\t\t\t<div class=\"checklist-header-progress\" id=\"progress_", "\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"checklist-header-actions\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"checklist-action-collapse-btn collapsed\" onclick=\"", "\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"checklist-items-wrapper\">\n\t\t\t\t\t<div class=\"checklist-items-list\" id=\"subItems_", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject28 = function _templateObject28() {
	    return data;
	  };

	  return data;
	}

	function _templateObject27() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"checklist-item-remove-btn\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t"]);

	  _templateObject27 = function _templateObject27() {
	    return data;
	  };

	  return data;
	}

	function _templateObject26() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"checklist-action-group-btn\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject26 = function _templateObject26() {
	    return data;
	  };

	  return data;
	}

	function _templateObject25() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"checklist-item-add-btn\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t"]);

	  _templateObject25 = function _templateObject25() {
	    return data;
	  };

	  return data;
	}

	function _templateObject24() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"checklist-wrapper-dragndrop\"></div>"]);

	  _templateObject24 = function _templateObject24() {
	    return data;
	  };

	  return data;
	}

	function _templateObject23() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"checklist-items-list-actions droppable\"></div>"]);

	  _templateObject23 = function _templateObject23() {
	    return data;
	  };

	  return data;
	}

	function _templateObject22() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"checklist-task-root\">\n\t\t\t\t<div id=\"subItems_", "\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject22 = function _templateObject22() {
	    return data;
	  };

	  return data;
	}

	function _templateObject21() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"checklist-item-attachment-file\" id=\"disk-attach-", "\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"checklist-item-attachment-file-name\">\n\t\t\t\t\t\t<label class=\"checklist-item-attachment-file-name-text\" title=\"", "\">", "</label>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"checklist-item-attachment-file-size\">\n\t\t\t\t\t\t<label class=\"checklist-item-attachment-file-size-text\">", "</label>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject21 = function _templateObject21() {
	    return data;
	  };

	  return data;
	}

	function _templateObject20() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"files_chooser\">\n\t\t\t\t<div id=\"diskuf-selectdialog-", "\" class=\"diskuf-files-entity diskuf-selectdialog bx-disk\">\n\t\t\t\t\t<div class=\"diskuf-files-block checklist-loader-files\">\n\t\t\t\t\t\t<div class=\"diskuf-placeholder\">\n\t\t\t\t\t\t\t<table class=\"files-list\">\n\t\t\t\t\t\t\t\t<tbody class=\"diskuf-placeholder-tbody\"></tbody>\n\t\t\t\t\t\t\t</table>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"diskuf-extended\" style=\"display: block\">\n\t\t\t\t\t\t<input type=\"hidden\" name=\"", "[", "][UF_CHECKLIST_FILES][]\" value=\"\"/>\n\t\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t\t<label for=\"file_loader_", "\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t<input class=\"diskuf-fileUploader\" id=\"file_loader_", "\" type=\"file\"\n\t\t\t\t\t\t\t\t   multiple=\"multiple\" size=\"1\" style=\"display: none\"/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"diskuf-extended-item\" onclick=\"", "\">\n\t\t\t\t\t\t\t<span class=\"diskuf-selector-link\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"diskuf-extended-item\" onclick=\"", "\">\n\t\t\t\t\t\t\t<span class=\"diskuf-selector-link-cloud\" data-bx-doc-handler=\"gdrive\">\n\t\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject20 = function _templateObject20() {
	    return data;
	  };

	  return data;
	}

	function _templateObject19() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"checklist-item-attachment-file\" id=\"disk-attach-", "\" data-bx-id=\"", "\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"checklist-item-attachment-file-name\">\n\t\t\t\t\t<label class=\"checklist-item-attachment-file-name-text\" title=\"", "\">", "</label>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"checklist-item-attachment-file-size\">\n\t\t\t\t\t<label class=\"checklist-item-attachment-file-size-text\">", "</label>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject19 = function _templateObject19() {
	    return data;
	  };

	  return data;
	}

	function _templateObject18() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"checklist-item-attachment-file-cover\">\n\t\t\t\t\t<div class=\"ui-icon ui-icon-file-", "\"><i></i></div>\n\t\t\t\t\t<div class=\"checklist-item-attachment-file-remove\" onclick=\"", "\"></div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject18 = function _templateObject18() {
	    return data;
	  };

	  return data;
	}

	function _templateObject17() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"checklist-item-attachment-file-cover\" style=\"background-image: url(", ")\">\n\t\t\t\t\t<div class=\"checklist-item-attachment-file-remove\" onclick=\"", "\"></div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject17 = function _templateObject17() {
	    return data;
	  };

	  return data;
	}

	function _templateObject16() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"checklist-item-attachment-file-remove\"\n\t\t\t\t\t\t onclick=\"", "\"></div>\n\t\t\t\t"]);

	  _templateObject16 = function _templateObject16() {
	    return data;
	  };

	  return data;
	}

	function _templateObject15() {
	  var data = babelHelpers.taggedTemplateLiteral(["", ""]);

	  _templateObject15 = function _templateObject15() {
	    return data;
	  };

	  return data;
	}

	function _templateObject14() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"request_", "\">\n\t\t\t\t\t<input type=\"hidden\" id=\"NODE_ID\" name=\"", "[NODE_ID]\" value=\"", "\"/>\n\t\t\t\t\t<input type=\"hidden\" id=\"PARENT_NODE_ID\" name=\"", "[PARENT_NODE_ID]\" value=\"", "\"/>\n\t\t\t\t\t<input type=\"hidden\" id=\"ID\" name=\"", "[ID]\" value=\"", "\"/>\n\t\t\t\t\t<input type=\"hidden\" id=\"COPIED_ID\" name=\"", "[COPIED_ID]\" value=\"", "\"/>\n\t\t\t\t\t<input type=\"hidden\" id=\"PARENT_ID\" name=\"", "[PARENT_ID]\" value=\"", "\"/>\n\t\t\t\t\t<input type=\"hidden\" id=\"TITLE\" name=\"", "[TITLE]\" value=\"", "\"/>\n\t\t\t\t\t<input type=\"hidden\" id=\"SORT_INDEX\" name=\"", "[SORT_INDEX]\" value=\"", "\"/>\n\t\t\t\t\t<input type=\"hidden\" id=\"IS_COMPLETE\" name=\"", "[IS_COMPLETE]\" value=\"", "\"/>\n\t\t\t\t\t<input type=\"hidden\" id=\"IS_IMPORTANT\" name=\"", "[IS_IMPORTANT]\" value=\"", "\"/>\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject14 = function _templateObject14() {
	    return data;
	  };

	  return data;
	}

	function _templateObject13() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"checklist-item-important\" onclick=\"", "\"></div>\n\t\t"]);

	  _templateObject13 = function _templateObject13() {
	    return data;
	  };

	  return data;
	}

	function _templateObject12() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"checklist-item-editor-panel-container\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject12 = function _templateObject12() {
	    return data;
	  };

	  return data;
	}

	function _templateObject11() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"checklist-item-inner checklist-item-new ", "\">\n\t\t\t\t<div class=\"checklist-item-flag-block\">\n\t\t\t\t\t<div class=\"checklist-item-flag\">\n\t\t\t\t\t\t<label class=\"checklist-item-flag-element\" onclick=\"", "\">\n\t\t\t\t\t\t\t<span class=\"checklist-item-flag-sub-checklist-progress\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<span class=\"checklist-item-flag-element-decorate\"/>\n\t\t\t\t\t\t</label>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"checklist-item-content-block\">\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-after-icon ui-ctl-w100\">\n\t\t\t\t\t\t<input class=\"ui-ctl-element\" type=\"text\" id=\"text_", "\"\n\t\t\t\t\t\t\t   placeholder=\"", "\"\n\t\t\t\t\t\t\t   value=\"", "\"\n\t\t\t\t\t\t\t   onkeypress=\"", "\"\n\t\t\t\t\t\t\t   onblur=\"", "\"/>\n\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" onclick=\"", "\"/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject11 = function _templateObject11() {
	    return data;
	  };

	  return data;
	}

	function _templateObject10() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"checklist-header-name\">\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-after-icon ui-ctl-xs ui-ctl-no-padding ui-ctl-underline \n\t\t\t\t\t\t\t\tchecklist-header-name-editor\">\n\t\t\t\t\t\t<input class=\"ui-ctl-element\" type=\"text\" id=\"text_", "\"\n\t\t\t\t\t\t\t   value=\"", "\"\n\t\t\t\t\t\t\t   onkeypress=\"", "\"\n\t\t\t\t\t\t\t   onblur=\"", "\"/>\n\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" onclick=\"", "\"/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject10 = function _templateObject10() {
	    return data;
	  };

	  return data;
	}

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"checklist-item-editor-panel ", "\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t", "\n\t\t\t<div class=\"checklist-item-editor-panel-btn checklist-item-editor-panel-btn-tabin\" onclick=\"", "\"></div>\n\t\t\t<div class=\"checklist-item-editor-panel-btn checklist-item-editor-panel-btn-tabout\" onclick=\"", "\"></div>\n\t\t\t<div class=\"checklist-item-editor-panel-separator\"></div>\n\t\t\t<div class=\"checklist-item-editor-panel-btn checklist-item-editor-panel-btn-important\n\t\t\t\t", "\" onclick=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"checklist-item-editor-panel-separator\"></div>\n\t\t\t<div class=\"checklist-item-editor-panel-btn checklist-item-editor-panel-btn-checklist\" onclick=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"checklist-item-editor-panel-separator\"></div>\n\t\t\t<div class=\"checklist-item-editor-panel-btn checklist-item-editor-panel-btn-remove\" onclick=\"", "\"></div>\n\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"checklist-item-editor-panel-btn checklist-item-editor-panel-btn-attachment\" onclick=\"", "\"></div>\n\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["+ ", ""]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["+ ", ""]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"checklist-item-editor-panel-btn checklist-item-editor-panel-btn-auditor\" onclick=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"checklist-item-editor-panel-separator\"></div>\n\t\t\t<div class=\"checklist-item-editor-panel-btn checklist-item-editor-panel-btn-accomplice\" onclick=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"checklist-item-editor-panel-separator\"></div>\n\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["+ ", ""]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"checklist-notification-balloon-message-container\">\n\t\t\t\t\t\t<div class=\"checklist-notification-balloon-avatar\">\n\t\t\t\t\t\t\t<img class=\"checklist-notification-balloon-avatar-img\" src=\"", "\" alt=\"\"/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<span class=\"checklist-notification-balloon-message\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var CheckListItem =
	/*#__PURE__*/
	function (_CompositeTreeItem) {
	  babelHelpers.inherits(CheckListItem, _CompositeTreeItem);
	  babelHelpers.createClass(CheckListItem, null, [{
	    key: "makeDangerElement",
	    value: function makeDangerElement(element) {
	      var dangerClass = 'ui-ctl-danger';

	      if (!main_core.Dom.hasClass(element, dangerClass)) {
	        main_core.Dom.addClass(element, dangerClass);
	      }
	    }
	  }, {
	    key: "updateParents",
	    value: function updateParents(oldParent, newParent) {
	      if (oldParent !== newParent) {
	        oldParent.updateCounts();
	        newParent.updateCounts();
	        oldParent.updateProgress();
	        newParent.updateProgress();
	        oldParent.updateIndexes();
	        newParent.updateIndexes();
	      } else {
	        newParent.updateIndexes();
	      }
	    }
	  }, {
	    key: "getProgressText",
	    value: function getProgressText(completed, total) {
	      var replaces = {
	        '#total#': total,
	        '#completed#': completed
	      };
	      var progressText = main_core.Loc.getMessage('TASKS_CHECKLIST_PROGRESS_BAR_PROGRESS_TEXT');
	      Object.keys(replaces).forEach(function (search) {
	        progressText = progressText.replace(search, replaces[search]);
	      });
	      return progressText;
	    }
	  }, {
	    key: "getFileExtension",
	    value: function getFileExtension(ext) {
	      var fileExtension = ext;

	      switch (ext) {
	        case 'mp4':
	        case 'mkv':
	        case 'mpeg':
	        case 'avi':
	        case '3gp':
	        case 'flv':
	        case 'm4v':
	        case 'ogg':
	        case 'swf':
	        case 'wmv':
	          fileExtension = 'mov';
	          break;

	        case 'txt':
	          fileExtension = 'txt';
	          break;

	        case 'doc':
	        case 'docx':
	          fileExtension = 'doc';
	          break;

	        case 'xls':
	        case 'xlsx':
	          fileExtension = 'xls';
	          break;

	        case 'php':
	          fileExtension = 'php';
	          break;

	        case 'pdf':
	          fileExtension = 'pdf';
	          break;

	        case 'ppt':
	        case 'pptx':
	          fileExtension = 'ppt';
	          break;

	        case 'rar':
	          fileExtension = 'rar';
	          break;

	        case 'zip':
	          fileExtension = 'zip';
	          break;

	        case 'set':
	          fileExtension = 'set';
	          break;

	        case 'mov':
	          fileExtension = 'mov';
	          break;

	        case 'img':
	        case 'jpg':
	        case 'jpeg':
	        case 'gif':
	          fileExtension = 'img';
	          break;

	        default:
	          fileExtension = 'empty';
	          break;
	      }

	      return fileExtension;
	    }
	  }, {
	    key: "getInputSelection",
	    value: function getInputSelection(input) {
	      var start = 0;
	      var end = 0;
	      var normalizedValue;
	      var range;
	      var textInputRange;
	      var len;
	      var endRange;

	      if (typeof input.selectionStart === 'number' && typeof input.selectionEnd === 'number') {
	        start = input.selectionStart;
	        end = input.selectionEnd;
	      } else {
	        range = document.selection.createRange();

	        if (range && range.parentElement() === input) {
	          len = input.value.length;
	          normalizedValue = input.value.replace(/\r\n/g, '\n'); // Create a working TextRange that lives only in the input

	          textInputRange = input.createTextRange();
	          textInputRange.moveToBookmark(range.getBookmark()); // Check if the start and end of the selection are at the very end
	          // of the input, since moveStart/moveEnd doesn't return what we want
	          // in those cases

	          endRange = input.createTextRange();
	          endRange.collapse(false);

	          if (textInputRange.compareEndPoints('StartToEnd', endRange) > -1) {
	            start = len;
	            end = len;
	          } else {
	            start = -textInputRange.moveStart('character', -len);
	            start += normalizedValue.slice(0, start).split('\n').length - 1;

	            if (textInputRange.compareEndPoints('EndToEnd', endRange) > -1) {
	              end = len;
	            } else {
	              end = -textInputRange.moveEnd('character', -len);
	              end += normalizedValue.slice(0, end).split('\n').length - 1;
	            }
	          }
	        }
	      }

	      return {
	        start: start,
	        end: end
	      };
	    }
	  }, {
	    key: "setDefaultStyles",
	    value: function setDefaultStyles(layout) {
	      var action = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'add';

	      if (action === 'add') {
	        layout.style.overflow = 'hidden';
	        layout.style.height = 0;
	        layout.style.opacity = 0;
	        main_core.Dom.addClass(layout, 'checklist-item-show');
	      } else if (action === 'delete') {
	        layout.style.overflow = 'hidden';
	        layout.style.height = "".concat(layout.scrollHeight, "px");
	        layout.style.opacity = 1;
	        main_core.Dom.addClass(layout, 'checklist-item-hide');
	      }
	    }
	  }, {
	    key: "getDefaultDisplayTitle",
	    value: function getDefaultDisplayTitle(title) {
	      var defaultDisplayTitle = title;

	      if (title.indexOf('BX_CHECKLIST') === 0) {
	        if (title.length === 12) {
	          defaultDisplayTitle = main_core.Loc.getMessage('TASKS_CHECKLIST_DEFAULT_DISPLAY_TITLE');
	        } else if (title.match(/BX_CHECKLIST_\d+$/)) {
	          defaultDisplayTitle = title.replace('BX_CHECKLIST_', "".concat(main_core.Loc.getMessage('TASKS_CHECKLIST_DEFAULT_DISPLAY_TITLE'), " "));
	        }
	      }

	      return defaultDisplayTitle;
	    }
	  }]);

	  function CheckListItem() {
	    var _this;

	    var fields = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, CheckListItem);
	    var action = fields.action;
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CheckListItem).call(this));
	    _this.fields = new CheckListItemFields(fields);
	    _this.action = {
	      canUpdate: action && 'MODIFY' in action ? action.MODIFY : true,
	      canRemove: action && 'REMOVE' in action ? action.REMOVE : true,
	      canToggle: action && 'TOGGLE' in action ? action.TOGGLE : true,
	      canDrag: action && 'DRAG' in action ? action.DRAG : true
	    };
	    _this.input = null;
	    _this.panel = null;
	    _this.progress = null;
	    _this.filesLoaderPopup = null;
	    _this.filesLoaderProgressBars = new Map();
	    _this.updateMode = false;
	    return _this;
	  }

	  babelHelpers.createClass(CheckListItem, [{
	    key: "add",
	    value: function add(item) {
	      var position = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      babelHelpers.get(babelHelpers.getPrototypeOf(CheckListItem.prototype), "add", this).call(this, item, position);
	      item.optionManager = this.optionManager;
	    }
	  }, {
	    key: "isTaskRoot",
	    value: function isTaskRoot() {
	      return this.getNodeId() === 0 && this.getParent() === null;
	    }
	  }, {
	    key: "isCheckList",
	    value: function isCheckList() {
	      return !this.isTaskRoot() && this.getParent().isTaskRoot();
	    }
	  }, {
	    key: "getCheckList",
	    value: function getCheckList() {
	      var parent = this;

	      while (!parent.getParent().isTaskRoot()) {
	        parent = parent.getParent();
	      }

	      return parent;
	    }
	  }, {
	    key: "countCompletedCount",
	    value: function countCompletedCount() {
	      var recursively = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      var completedCount = 0;
	      this.getDescendants().forEach(function (descendant) {
	        if (descendant.fields.getIsComplete()) {
	          completedCount += 1;
	        }

	        if (recursively) {
	          completedCount += descendant.countCompletedCount(recursively);
	        }
	      });
	      return completedCount;
	    }
	  }, {
	    key: "countTotalCount",
	    value: function countTotalCount() {
	      var recursively = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      var totalCount = 0;

	      if (!recursively) {
	        totalCount = this.getDescendantsCount();
	      } else {
	        this.getDescendants().forEach(function (descendant) {
	          totalCount += 1;
	          totalCount += descendant.countTotalCount(recursively);
	        });
	      }

	      return totalCount;
	    }
	  }, {
	    key: "updateCompletedCount",
	    value: function updateCompletedCount() {
	      var completedCount = this.countCompletedCount();
	      this.fields.setCompletedCount(completedCount);
	    }
	  }, {
	    key: "updateTotalCount",
	    value: function updateTotalCount() {
	      var totalCount = this.countTotalCount();
	      this.fields.setTotalCount(totalCount);
	    }
	  }, {
	    key: "updateCounts",
	    value: function updateCounts() {
	      this.updateCompletedCount();
	      this.updateTotalCount();
	    }
	  }, {
	    key: "updateProgress",
	    value: function updateProgress() {
	      if (this.progress === null) {
	        return;
	      }

	      var total = this.fields.getTotalCount();
	      var completed = this.fields.getCompletedCount();
	      this.progress.setMaxValue(total);
	      this.progress.update(completed);

	      if (this.isCheckList()) {
	        this.updateProgressText(completed, total);
	      }
	    }
	  }, {
	    key: "updateProgressText",
	    value: function updateProgressText(completed, total) {
	      var progressText = CheckListItem.getProgressText(completed, total);
	      this.progress.setTextAfter(progressText);
	    }
	  }, {
	    key: "delete",
	    value: function _delete() {
	      var _this2 = this;

	      var parent = this.getParent();
	      parent.remove(this);
	      parent.updateCounts();
	      parent.updateProgress();
	      CheckListItem.setDefaultStyles(this.container, 'delete');
	      setTimeout(function () {
	        _this2.container.style.height = 0;
	        _this2.container.style.opacity = 0;
	        _this2.container.style.paddingTop = 0;
	      }, 1);
	      setTimeout(function () {
	        main_core.Dom.remove(_this2.container);
	      }, 250);
	    }
	  }, {
	    key: "restore",
	    value: function restore() {
	      var parent = this.getParent();
	      var position = this.fields.getSortIndex();

	      if (position === 0) {
	        if (parent.getDescendantsCount() > 0) {
	          parent.addCheckListItem(this, parent.getFirstDescendant(), 'before');
	        } else {
	          parent.addCheckListItem(this);
	        }
	      } else {
	        parent.addCheckListItem(this, parent.getDescendants()[position - 1]);
	      }
	    }
	  }, {
	    key: "deleteAction",
	    value: function deleteAction() {
	      var showBalloon = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      var title = this.fields.getTitle();
	      this.delete();

	      if (showBalloon && title.length > 0 && title !== '') {
	        var action = 'DELETE';
	        var data = {
	          type: this.isCheckList() ? 'CHECKLIST' : 'ITEM'
	        };
	        this.getNotificationBalloon(action, data);
	      }

	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	      this.input = null;
	      this.updateMode = false;
	    }
	  }, {
	    key: "onDeleteClick",
	    value: function onDeleteClick(e) {
	      e.preventDefault();
	      main_core.Dom.hide(this.getRootNode().panel);

	      if (this.checkSelectedItems()) {
	        var items = this.getSelectedItems();
	        var action = 'DELETE_SELECTED';
	        var data = {
	          items: items
	        };
	        this.runForEachSelectedItem(function (item) {
	          item.fields.setIsSelected(false);
	          item.deleteAction(false);
	        });
	        items.forEach(function (item) {
	          item.getParent().updateIndexes();
	          item.handleCheckListChanges();
	        });
	        this.getNotificationBalloon(action, data);
	        return;
	      }

	      this.deleteAction();
	      this.getParent().updateIndexes();
	      this.handleCheckListChanges();
	    }
	  }, {
	    key: "getNotificationBalloon",
	    value: function getNotificationBalloon(action, data) {
	      var _this3 = this;

	      var actions = [];
	      var content = '';

	      switch (action) {
	        case 'DELETE':
	          {
	            content = main_core.Loc.getMessage("TASKS_CHECKLIST_NOTIFICATION_BALLOON_ACTION_".concat(action, "_").concat(data.type));
	            actions.push({
	              title: main_core.Loc.getMessage('TASKS_CHECKLIST_NOTIFICATION_BALLOON_CANCEL'),
	              events: {
	                click: function click(event, balloon) {
	                  balloon.close();

	                  _this3.restore();

	                  _this3.handleCheckListChanges();

	                  _this3.handleTaskOptions();
	                }
	              }
	            });
	            break;
	          }

	        case 'DELETE_SELECTED':
	          {
	            content = main_core.Loc.getMessage("TASKS_CHECKLIST_NOTIFICATION_BALLOON_ACTION_".concat(action, "_ITEMS"));
	            actions.push({
	              title: main_core.Loc.getMessage('TASKS_CHECKLIST_NOTIFICATION_BALLOON_CANCEL'),
	              events: {
	                click: function click(event, balloon) {
	                  balloon.close();
	                  data.items.forEach(function (item) {
	                    item.restore();
	                    item.handleCheckListChanges();
	                  });

	                  _this3.handleTaskOptions();
	                }
	              }
	            });
	            break;
	          }

	        case 'AUDITOR_ADDED':
	        case 'ACCOMPLICE_ADDED':
	          {
	            content = main_core.Tag.render(_templateObject(), data.avatar, main_core.Loc.getMessage("TASKS_CHECKLIST_NOTIFICATION_BALLOON_ACTION_".concat(action)));
	            break;
	          }

	        default:
	          {
	            break;
	          }
	      }

	      BX.loadExt('ui.notification').then(function () {
	        BX.UI.Notification.Center.notify({
	          content: content,
	          actions: actions
	        });
	      });
	    }
	  }, {
	    key: "onToAnotherCheckListClick",
	    value: function onToAnotherCheckListClick(e) {
	      var rootNode = this.getRootNode();

	      if (rootNode.getDescendantsCount() === 1) {
	        this.moveToNewCheckList(2);
	        return;
	      }

	      new BX.PopupMenuWindow('to-another-checklist', e.target, this.getToAnotherCheckListPopupItems(), {
	        autoHide: true,
	        closeByEsc: true,
	        offsetLeft: e.target.offsetWidth / 3,
	        angle: true,
	        events: {
	          onPopupClose: function onPopupClose() {
	            this.destroy();
	          }
	        }
	      }).show();
	    }
	  }, {
	    key: "getToAnotherCheckListPopupItems",
	    value: function getToAnotherCheckListPopupItems() {
	      var _this4 = this;

	      var selectMode = this.checkSelectedItems();
	      var popupMenuItems = [];
	      var toNewCheckListMenuItem = {
	        text: main_core.Tag.message(_templateObject2(), 'TASKS_CHECKLIST_PANEL_TO_ANOTHER_CHECKLIST_POPUP_NEW_CHECKLIST'),
	        onclick: function onclick(event, item) {
	          item.getMenuWindow().close();

	          _this4.moveToNewCheckList(_this4.getRootNode().getDescendantsCount() + 1);
	        }
	      };

	      if (selectMode) {
	        this.getDescendants().forEach(function (descendant) {
	          popupMenuItems.push({
	            text: descendant.fields.getDisplayTitle(),
	            onclick: function onclick(event, item) {
	              item.getMenuWindow().close();

	              _this4.runForEachSelectedItem(function (selectedItem) {
	                selectedItem.makeChildOf(descendant);
	                descendant.unselectAll();

	                if (!_this4.checkSelectedItems()) {
	                  main_core.Dom.hide(_this4.getRootNode().panel);
	                }
	              });
	            }
	          });
	        });
	        popupMenuItems.push({
	          delimiter: true
	        });
	        popupMenuItems.push(toNewCheckListMenuItem);
	        return popupMenuItems;
	      }

	      var checkList = this.getCheckList();
	      var checkLists = this.getRootNode().getDescendants().filter(function (item) {
	        return item !== checkList;
	      });
	      checkLists.forEach(function (descendant) {
	        popupMenuItems.push({
	          text: descendant.fields.getDisplayTitle(),
	          onclick: function onclick(event, item) {
	            item.getMenuWindow().close();

	            _this4.makeChildOf(descendant);

	            _this4.handleUpdateEnding();
	          }
	        });
	      });
	      popupMenuItems.push({
	        delimiter: true
	      });
	      popupMenuItems.push(toNewCheckListMenuItem);
	      return popupMenuItems;
	    }
	  }, {
	    key: "moveToNewCheckList",
	    value: function moveToNewCheckList(number) {
	      var _this5 = this;

	      var title = "".concat(main_core.Loc.getMessage('TASKS_CHECKLIST_NEW_CHECKLIST_TITLE')).replace('#ITEM_NUMBER#', number);
	      var newCheckList = new CheckListItem({
	        TITLE: title,
	        DISPLAY_TITLE: title
	      });
	      this.getRootNode().addCheckListItem(newCheckList).then(function () {
	        if (_this5.checkSelectedItems()) {
	          _this5.runForEachSelectedItem(function (selectedItem) {
	            selectedItem.makeChildOf(newCheckList);
	            newCheckList.unselectAll();

	            if (!_this5.checkSelectedItems()) {
	              main_core.Dom.hide(_this5.getRootNode().panel);
	            }
	          });
	        } else {
	          _this5.makeChildOf(newCheckList);

	          _this5.handleUpdateEnding();
	        }
	      });
	    }
	  }, {
	    key: "processMemberSelect",
	    value: function processMemberSelect(member) {
	      if (this.memberSelector) {
	        this.memberSelector.close();
	      }

	      if (this.checkSelectedItems()) {
	        this.runForEachSelectedItem(function (selectedItem) {
	          var displayTitle = selectedItem.fields.getDisplayTitle();
	          var space = displayTitle.slice(-1) === ' ' ? '' : ' ';
	          var newTitle = "".concat(displayTitle).concat(space).concat(member.nameFormatted).substring(0, 255);
	          selectedItem.fields.addMember(member);
	          selectedItem.updateTitle(newTitle);
	          selectedItem.updateDisplayTitle(newTitle);
	        });
	        return;
	      }

	      var start = this.inputCursorPosition.start || 0;
	      var inputText = this.input.value;
	      var startSpace = start === 0 || inputText.charAt(start - 1) === ' ' ? '' : ' ';
	      var endSpace = inputText.charAt(start) === ' ' ? '' : ' ';
	      this.fields.addMember(member);
	      var newInputText = "".concat(inputText.slice(0, start)).concat(startSpace).concat(main_core.Text.decode(member.nameFormatted)).concat(endSpace);
	      this.inputCursorPosition.start = newInputText.length;
	      this.inputCursorPosition.end = newInputText.length;
	      this.input.value = "".concat(newInputText).concat(inputText.slice(start));
	      this.retrieveFocus();
	    }
	  }, {
	    key: "onSocNetSelectorAuditorSelected",
	    value: function onSocNetSelectorAuditorSelected(auditor) {
	      var type = 'auditor';
	      var action = "".concat(type.toUpperCase(), "_ADDED");
	      var data = {
	        avatar: auditor.avatar
	      };
	      var resultAuditor = babelHelpers.objectSpread({}, auditor, {
	        type: type
	      });
	      this.processMemberSelect(resultAuditor);
	      this.getNotificationBalloon(action, data);
	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:auditorAdded', auditor);
	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	    }
	  }, {
	    key: "onSocNetSelectorAccompliceSelected",
	    value: function onSocNetSelectorAccompliceSelected(accomplice) {
	      var type = 'accomplice';
	      var action = "".concat(type.toUpperCase(), "_ADDED");
	      var data = {
	        avatar: accomplice.avatar
	      };
	      var resultAccomplice = babelHelpers.objectSpread({}, accomplice, {
	        type: type
	      });
	      this.processMemberSelect(resultAccomplice);
	      this.getNotificationBalloon(action, data);
	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:accompliceAdded', accomplice);
	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	    }
	  }, {
	    key: "onAddAuditorClick",
	    value: function onAddAuditorClick(e) {
	      this.memberSelector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
	        scope: e.target,
	        mode: 'user',
	        useSearch: true,
	        useAdd: false,
	        controlBind: e.target,
	        parent: this
	      });
	      this.memberSelector.bindEvent('item-selected', this.onSocNetSelectorAuditorSelected.bind(this));
	      this.memberSelector.open();
	    }
	  }, {
	    key: "onAddAccompliceClick",
	    value: function onAddAccompliceClick(e) {
	      this.memberSelector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
	        scope: e.target,
	        mode: 'user',
	        useSearch: true,
	        useAdd: false,
	        controlBind: e.target,
	        parent: this
	      });
	      this.memberSelector.bindEvent('item-selected', this.onSocNetSelectorAccompliceSelected.bind(this));
	      this.memberSelector.open();
	    }
	  }, {
	    key: "onUploadAttachmentClick",
	    value: function onUploadAttachmentClick(e) {
	      var nodeId = this.getNodeId();
	      var _this$optionManager = this.optionManager,
	          prefix = _this$optionManager.prefix,
	          diskUrls = _this$optionManager.diskUrls;
	      var urlSelect = diskUrls.urlSelect,
	          urlRenameFile = diskUrls.urlRenameFile,
	          urlDeleteFile = diskUrls.urlDeleteFile,
	          urlUpload = diskUrls.urlUpload;

	      if (this.filesLoaderPopup === null) {
	        this.filesLoaderPopup = new BX.PopupWindow({
	          content: this.getAttachmentsLoaderLayout(),
	          bindElement: e.target,
	          offsetLeft: e.target.offsetWidth / 2,
	          autoHide: true,
	          closeByEsc: true,
	          angle: true
	        });
	      } else {
	        this.filesLoaderPopup.setBindElement(e.target);
	      }

	      this.filesLoaderPopup.show();
	      BX.Disk.UF.add({
	        UID: nodeId,
	        controlName: "".concat(prefix, "[").concat(nodeId, "][UF_CHECKLIST_FILES][]"),
	        hideSelectDialog: false,
	        urlSelect: urlSelect,
	        urlRenameFile: urlRenameFile,
	        urlDeleteFile: urlDeleteFile,
	        urlUpload: urlUpload
	      });
	      BX.onCustomEvent(this.filesLoaderPopup.contentContainer.querySelector('#files_chooser'), 'DiskLoadFormController', ['show']);
	    }
	  }, {
	    key: "onDeleteAttachmentClick",
	    value: function onDeleteAttachmentClick(fileId) {
	      this.fields.removeAttachment(fileId);
	      main_core.Dom.remove(this.getAttachmentsContainer().querySelector("#disk-attach-".concat(fileId)));
	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	    }
	  }, {
	    key: "getPanelBodyLayout",
	    value: function getPanelBodyLayout() {
	      var membersLayout = main_core.Tag.render(_templateObject3(), this.onAddAuditorClick.bind(this), main_core.Tag.message(_templateObject4(), 'TASKS_CHECKLIST_PANEL_AUDITOR'), this.onAddAccompliceClick.bind(this), main_core.Tag.message(_templateObject5(), 'TASKS_CHECKLIST_PANEL_ACCOMPLICE'));
	      var attachmentButtonLayout = main_core.Tag.render(_templateObject6(), this.onUploadAttachmentClick.bind(this));
	      var itemsActionButtonsLayout = main_core.Tag.render(_templateObject7(), this.checkSelectedItems() ? '' : attachmentButtonLayout, this.onTabInClick.bind(this), this.onTabOutClick.bind(this), this.fields.getIsImportant() ? ' checklist-item-editor-panel-btn-important-selected' : '', this.onImportantClick.bind(this), main_core.Loc.getMessage('TASKS_CHECKLIST_PANEL_IMPORTANT'), this.onToAnotherCheckListClick.bind(this), main_core.Loc.getMessage('TASKS_CHECKLIST_PANEL_TO_ANOTHER_CHECKLIST'), this.onDeleteClick.bind(this));
	      return main_core.Tag.render(_templateObject8(), this.isTaskRoot() || this.isCheckList() ? 'checklist-item-editor-group-panel' : '', this.checkCanAddAccomplice() ? membersLayout : '', !this.isCheckList() ? itemsActionButtonsLayout : '');
	    }
	  }, {
	    key: "updateTitle",
	    value: function updateTitle(text) {
	      this.fields.setTitle(text);
	    }
	  }, {
	    key: "updateDisplayTitle",
	    value: function updateDisplayTitle(text) {
	      var oldTitleNode = this.container.querySelector(this.isCheckList() ? '.checklist-header-name-text' : '.checklist-item-description-text');
	      var newTitleNode = this.getTitleLayout();
	      this.fields.setDisplayTitle(text);
	      main_core.Dom.replace(oldTitleNode, newTitleNode);
	    }
	  }, {
	    key: "getTitleLayout",
	    value: function getTitleLayout() {
	      var userPath = this.optionManager.userPath;
	      var title = this.fields.getTitle();
	      title = this.isCheckList() ? CheckListItem.getDefaultDisplayTitle(title) : title;
	      this.fields.setDisplayTitle(title);
	      this.fields.getMembers().forEach(function (_ref) {
	        var id = _ref.id,
	            nameFormatted = _ref.nameFormatted,
	            type = _ref.type;
	        var regExp = new RegExp(nameFormatted, 'g');
	        var href = userPath.replace('#user_id#', id);
	        title = title.replace(regExp, "<a href=".concat(href, " class=\"checklist-item-").concat(type, "\">").concat(nameFormatted, "</a>"));
	      });
	      title = title.replace(/(https?:\/\/[^\s]+)/g, function (url) {
	        return "<a class=\"checklist-item-link\" href=\"".concat(url, "\" target=\"_blank\">").concat(url, "</a>");
	      });
	      return main_core.Tag.render(_templateObject9(), this.isCheckList() ? 'checklist-header-name-text' : 'checklist-item-description-text', title);
	    }
	  }, {
	    key: "processMembersFromText",
	    value: function processMembersFromText(text) {
	      var _this6 = this;

	      var membersToDelete = [];
	      this.fields.getMembers().forEach(function (_ref2) {
	        var id = _ref2.id,
	            nameFormatted = _ref2.nameFormatted;

	        if (text.indexOf(nameFormatted) === -1) {
	          membersToDelete.push(id);
	        }
	      });
	      membersToDelete.forEach(function (id) {
	        _this6.fields.removeMember(id);
	      });
	    }
	  }, {
	    key: "updateIndexes",
	    value: function updateIndexes() {
	      this.updateSortIndexes();
	      this.updateDisplaySortIndexes();
	    }
	  }, {
	    key: "updateSortIndexes",
	    value: function updateSortIndexes() {
	      var sortIndex = 0;
	      this.getDescendants().forEach(function (descendant) {
	        descendant.fields.setSortIndex(sortIndex);
	        sortIndex += 1;
	      });
	    }
	  }, {
	    key: "updateDisplaySortIndexes",
	    value: function updateDisplaySortIndexes() {
	      var parentSortIndex = this.isCheckList() || this.isTaskRoot() ? '' : "".concat(this.fields.getDisplaySortIndex(), ".");
	      var localSortIndex = 0;
	      this.getDescendants().forEach(function (descendant) {
	        localSortIndex += 1;
	        var newSortIndex = "".concat(parentSortIndex).concat(localSortIndex);
	        descendant.fields.setDisplaySortIndex(newSortIndex);

	        if (!descendant.isCheckList()) {
	          descendant.container.querySelector('.checklist-item-number').innerText = newSortIndex;
	        }

	        descendant.updateDisplaySortIndexes();
	      });
	    }
	  }, {
	    key: "handleTaskOptions",
	    value: function handleTaskOptions() {
	      var _this$optionManager2 = this.optionManager,
	          userId = _this$optionManager2.userId,
	          showCompleted = _this$optionManager2.showCompleted,
	          showOnlyMine = _this$optionManager2.showOnlyMine;
	      this.getRootNode().hideByCondition(function (item) {
	        var isComplete = item.fields.getIsComplete();
	        var hasUserInMembers = item.fields.getMembers().has(userId.toString());
	        var condition;

	        if (!showCompleted && showOnlyMine) {
	          condition = isComplete || !hasUserInMembers;
	        } else if (!showCompleted) {
	          condition = isComplete;
	        } else if (showOnlyMine) {
	          condition = !hasUserInMembers;
	        } else {
	          condition = false;
	        }

	        return condition;
	      });
	    }
	  }, {
	    key: "hideByCondition",
	    value: function hideByCondition(condition) {
	      if (this.checkCanHide(condition)) {
	        this.hide();
	      } else {
	        this.show();
	        this.getDescendants().forEach(function (descendant) {
	          descendant.hideByCondition(condition);
	        });
	      }
	    }
	  }, {
	    key: "checkCanHide",
	    value: function checkCanHide(condition) {
	      if (this.isTaskRoot() || this.updateMode || main_core.Dom.hasClass(this.container, 'checklist-item-show') || !condition(this)) {
	        return false;
	      }

	      var canHide = true;
	      this.getDescendants().forEach(function (descendant) {
	        if (!condition(descendant)) {
	          canHide = false;
	        } else if (canHide) {
	          canHide = descendant.checkCanHide(condition);
	        }
	      });
	      return canHide;
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      main_core.Dom.hide(this.container);
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      main_core.Dom.show(this.container);
	    }
	  }, {
	    key: "checkIsComplete",
	    value: function checkIsComplete() {
	      var isComplete;

	      if (this.isTaskRoot()) {
	        isComplete = false;
	      } else if (this.isCheckList()) {
	        var completedCount = this.countCompletedCount(true);
	        var totalCount = this.countTotalCount(true);
	        isComplete = completedCount === totalCount && totalCount > 0;
	      } else {
	        isComplete = this.fields.getIsComplete();
	      }

	      return isComplete;
	    }
	  }, {
	    key: "checkActiveUpdateExist",
	    value: function checkActiveUpdateExist() {
	      if (this.updateMode) {
	        return true;
	      }

	      var found = false;
	      this.getDescendants().forEach(function (descendant) {
	        if (found === false) {
	          found = descendant.checkActiveUpdateExist();
	        }
	      });
	      return found;
	    }
	  }, {
	    key: "disableAllGroup",
	    value: function disableAllGroup() {
	      this.getDescendants().forEach(function (descendant) {
	        if (descendant.fields.getIsSelected()) {
	          descendant.toggleGroup();
	        }
	      });
	    }
	  }, {
	    key: "disableAllUpdateModes",
	    value: function disableAllUpdateModes() {
	      if (this.updateMode) {
	        this.handleUpdateEnding();
	      }

	      this.getDescendants().forEach(function (descendant) {
	        descendant.disableAllUpdateModes();
	      });
	    }
	  }, {
	    key: "rememberInputState",
	    value: function rememberInputState() {
	      this.input = this.container.querySelector("#text_".concat(this.getNodeId()));
	      this.inputCursorPosition = CheckListItem.getInputSelection(this.input);
	    }
	  }, {
	    key: "clearInput",
	    value: function clearInput(e) {
	      e.preventDefault();
	      this.container.querySelector("#text_".concat(this.getNodeId())).value = '';
	      this.retrieveFocus();
	    }
	  }, {
	    key: "retrieveFocus",
	    value: function retrieveFocus() {
	      var _this7 = this;

	      if (this.input !== null && this.inputCursorPosition) {
	        var _this$inputCursorPosi = this.inputCursorPosition,
	            start = _this$inputCursorPosi.start,
	            end = _this$inputCursorPosi.end;
	        setTimeout(function () {
	          _this7.input.focus();

	          _this7.input.setSelectionRange(start, end);
	        }, 10);
	      }
	    }
	  }, {
	    key: "getUpdateModeLayout",
	    value: function getUpdateModeLayout() {
	      var nodeId = this.getNodeId();

	      if (this.isCheckList()) {
	        return main_core.Tag.render(_templateObject10(), nodeId, this.fields.getDisplayTitle(), this.onInputKeyPressed.bind(this), this.rememberInputState.bind(this), this.clearInput.bind(this));
	      }

	      var progressBarLayout = new BX.UI.ProgressRound({
	        value: this.fields.getCompletedCount(),
	        maxValue: this.fields.getTotalCount(),
	        width: 20,
	        lineSize: 3,
	        fill: false,
	        color: BX.UI.ProgressRound.Color.PRIMARY
	      });
	      return main_core.Tag.render(_templateObject11(), this.fields.getIsComplete() ? 'checklist-item-solved' : '', this.onCompleteButtonClick.bind(this), progressBarLayout.getContainer(), nodeId, main_core.Loc.getMessage('TASKS_CHECKLIST_NEW_ITEM_PLACEHOLDER'), this.fields.getDisplayTitle(), this.onInputKeyPressed.bind(this), this.rememberInputState.bind(this), this.clearInput.bind(this));
	    }
	  }, {
	    key: "showEditorPanel",
	    value: function showEditorPanel(item) {
	      var nodeToPosition = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var position = BX.pos(nodeToPosition || item.getContainer());

	      if (!this.panel) {
	        this.panel = main_core.Tag.render(_templateObject12(), item.getPanelBodyLayout());
	        this.panel.style.top = "".concat(position.top, "px");
	        this.panel.style.left = "".concat(position.left, "px");
	        main_core.Dom.append(this.panel, document.body);
	      } else {
	        main_core.Dom.replace(this.panel.querySelector('.checklist-item-editor-panel'), item.getPanelBodyLayout());
	        this.panel.style.top = "".concat(position.top, "px");
	        this.panel.style.left = "".concat(position.left, "px");
	      }

	      if (!main_core.Dom.isShown(this.panel)) {
	        main_core.Dom.show(this.panel);
	      }

	      if (main_core.Dom.isShown(this.panel) && item.isCheckList() && !item.checkCanAddAccomplice()) {
	        main_core.Dom.hide(this.panel);
	      }
	    }
	  }, {
	    key: "enableUpdateMode",
	    value: function enableUpdateMode() {
	      var _this8 = this;

	      var viewModeLayout = this.container.querySelector(this.isCheckList() ? '.checklist-header-name' : '.checklist-item-inner');
	      var updateModeLayout = this.getUpdateModeLayout();
	      main_core.Dom.addClass(viewModeLayout, 'checklist-item-hidden');
	      main_core.Dom.insertBefore(updateModeLayout, viewModeLayout);
	      this.input = updateModeLayout.querySelector("#text_".concat(this.getNodeId()));
	      this.input.focus();
	      this.input.setSelectionRange(this.input.value.length, this.input.value.length);

	      if (this.input.value === '' || this.input.value.length === 0) {
	        setTimeout(function () {
	          if (main_core.Dom.isShown(_this8.input)) {
	            _this8.getRootNode().showEditorPanel(_this8, _this8.input);
	          }
	        }, 250);
	      } else {
	        this.getRootNode().showEditorPanel(this, this.input);
	      }

	      this.updateMode = true;
	    }
	  }, {
	    key: "disableUpdateMode",
	    value: function disableUpdateMode() {
	      var currentInner = this.container.querySelector(this.isCheckList() ? '.checklist-header-name' : '.checklist-item-inner');
	      var text = main_core.Text.encode(currentInner.querySelector("#text_".concat(this.getNodeId())).value.trim().substring(0, 255));
	      this.processMembersFromText(text);
	      this.updateTitle(text);
	      this.updateDisplayTitle(text);
	      main_core.Dom.removeClass(currentInner.nextElementSibling, 'checklist-item-hidden');
	      main_core.Dom.remove(currentInner);
	      main_core.Dom.hide(this.getRootNode().panel);
	      this.input = null;
	      this.updateMode = false;
	    }
	  }, {
	    key: "checkCanDeleteOnUpdateEnding",
	    value: function checkCanDeleteOnUpdateEnding() {
	      return this.getDescendantsCount() === 0 && Object.keys(this.fields.getAttachments()).length === 0 && this.filesLoaderProgressBars.size === 0;
	    }
	  }, {
	    key: "handleUpdateEnding",
	    value: function handleUpdateEnding() {
	      var createNewItem = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      var input = this.container.querySelector("#text_".concat(this.getNodeId()));
	      var text = input.value.trim();

	      if (text.length === 0) {
	        if (this.checkCanDeleteOnUpdateEnding()) {
	          this.deleteAction(false);
	          this.getParent().updateIndexes();
	          this.handleCheckListIsEmpty();
	          main_core.Dom.hide(this.getRootNode().panel);
	        } else {
	          CheckListItem.makeDangerElement(input.parentElement);

	          if (this.input !== null) {
	            this.getRootNode().showEditorPanel(this, this.input);
	          }
	        }
	      } else if (createNewItem) {
	        this.getParent().addCheckListItem(null, this);
	      } else {
	        this.disableUpdateMode();
	        this.handleTaskOptions();
	      }

	      if (this.filesLoaderPopup !== null) {
	        this.filesLoaderPopup.close();
	      }
	    }
	  }, {
	    key: "toggleUpdateMode",
	    value: function toggleUpdateMode(e) {
	      if (this.updateMode) {
	        if (e.keyCode === 13) {
	          this.handleUpdateEnding(!this.isCheckList());
	        }
	      } else {
	        var rootNode = this.getRootNode();
	        rootNode.disableAllUpdateModes();
	        rootNode.disableAllGroup();

	        if (!rootNode.checkActiveUpdateExist()) {
	          this.enableUpdateMode();
	          main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	        }
	      }
	    }
	  }, {
	    key: "onInputKeyPressed",
	    value: function onInputKeyPressed(e) {
	      if (e.keyCode === 13) {
	        this.toggleUpdateMode(e);
	        e.preventDefault();
	      }

	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	    }
	  }, {
	    key: "onHeaderNameClick",
	    value: function onHeaderNameClick(e) {
	      if (!this.checkCanUpdate() || e.target.closest('.checklist-item-auditor') || e.target.closest('.checklist-item-accomplice') || e.target.closest('.checklist-item-link')) {
	        return;
	      }

	      this.toggleUpdateMode(e);
	    }
	  }, {
	    key: "onInnerContainerClick",
	    value: function onInnerContainerClick(e) {
	      if (!this.checkCanUpdate() || e.target.closest('.checklist-item-auditor') || e.target.closest('.checklist-item-accomplice') || e.target.closest('.checklist-item-link') || e.target.closest('.checklist-item-important') || e.target.closest('.checklist-item-dragndrop') || e.target.closest('.checklist-item-group-checkbox') || e.target.closest('.checklist-item-remove') || e.target.closest('.checklist-item-flag-block')) {
	        return;
	      }

	      if (this.getCheckList().fields.getIsSelected()) {
	        this.toggleSelect(e);
	        return;
	      }

	      this.toggleUpdateMode(e);
	    }
	  }, {
	    key: "getImportantLayout",
	    value: function getImportantLayout() {
	      return main_core.Tag.render(_templateObject13(), this.onImportantClick.bind(this));
	    }
	  }, {
	    key: "toggleImportant",
	    value: function toggleImportant() {
	      if (this.fields.getIsImportant()) {
	        this.fields.setIsImportant(false);
	        main_core.Dom.remove(this.container.querySelector('.checklist-item-important'));
	      } else {
	        this.fields.setIsImportant(true);
	        main_core.Dom.insertBefore(this.getImportantLayout(), this.container.querySelector('.checklist-item-description'));
	      }

	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	      this.retrieveFocus();
	    }
	  }, {
	    key: "checkSelectedItems",
	    value: function checkSelectedItems() {
	      return this.getRootNode().getSelectedItems().length > 0;
	    }
	  }, {
	    key: "runForEachSelectedItem",
	    value: function runForEachSelectedItem(callback) {
	      var reverse = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      var selectedItems = this.getRootNode().getSelectedItems();

	      if (reverse) {
	        selectedItems = babelHelpers.toConsumableArray(selectedItems.reverse());
	      }

	      selectedItems.forEach(function (item) {
	        callback(item);
	      });
	    }
	  }, {
	    key: "onImportantClick",
	    value: function onImportantClick(e) {
	      if (!this.checkCanUpdate()) {
	        return;
	      }

	      if (this.checkSelectedItems()) {
	        this.runForEachSelectedItem(function (selectedItem) {
	          selectedItem.toggleImportant();
	        });
	        return;
	      }

	      this.toggleImportant();
	      var panelImportantButton = e.target.closest('.checklist-item-editor-panel-btn-important');

	      if (panelImportantButton) {
	        main_core.Dom.toggleClass(panelImportantButton, 'checklist-item-editor-panel-btn-important-selected');
	      }
	    }
	  }, {
	    key: "onCompleteButtonClick",
	    value: function onCompleteButtonClick() {
	      if (this.getCheckList().fields.getIsSelected() || this.updateMode || !this.checkCanToggle()) {
	        return;
	      }

	      this.toggleComplete();
	    }
	  }, {
	    key: "toggleComplete",
	    value: function toggleComplete() {
	      var isComplete = this.fields.getIsComplete();
	      this.fields.setIsComplete(!isComplete);
	      this.getParent().updateCounts();
	      this.getParent().updateProgress();
	      main_core.Dom.toggleClass(this.getInnerContainer(), 'checklist-item-solved');
	      this.handleCheckListChanges();
	      this.handleTaskOptions();
	      this.runAjaxToggleComplete();
	    }
	  }, {
	    key: "runAjaxToggleComplete",
	    value: function runAjaxToggleComplete() {
	      var _this9 = this;

	      var id = this.fields.getId();

	      if (!id) {
	        return;
	      }

	      var data = {};
	      var _this$optionManager3 = this.optionManager,
	          ajaxActions = _this$optionManager3.ajaxActions,
	          entityId = _this$optionManager3.entityId,
	          entityType = _this$optionManager3.entityType,
	          stableTreeStructure = _this$optionManager3.stableTreeStructure;
	      var actionName = this.fields.getIsComplete() ? ajaxActions.COMPLETE : ajaxActions.RENEW;
	      data["".concat(entityType.toLowerCase(), "Id")] = entityId;
	      data.checkListItemId = id;
	      BX.ajax.runAction(actionName, {
	        data: data
	      }).then(function (response) {
	        var isComplete = response.data.checkListItem.isComplete;

	        _this9.updateStableTreeStructure(isComplete, stableTreeStructure, stableTreeStructure);
	      });
	    }
	  }, {
	    key: "updateStableTreeStructure",
	    value: function updateStableTreeStructure(isComplete, item, parent) {
	      var _this10 = this;

	      if (this.fields.getId() === item.FIELDS.id) {
	        item.FIELDS.isComplete = isComplete;
	        parent.FIELDS.completedCount += isComplete ? 1 : -1;
	        return this;
	      }

	      var found = null;
	      item.DESCENDANTS.forEach(function (descendant) {
	        if (found === null) {
	          found = _this10.updateStableTreeStructure(isComplete, descendant, item);
	        }
	      });
	      return found;
	    }
	  }, {
	    key: "unselectAll",
	    value: function unselectAll() {
	      var checkBox = this.container.querySelector("#select_".concat(this.getNodeId()));

	      if (checkBox && checkBox.checked === true) {
	        this.fields.setIsSelected(false);
	        checkBox.checked = false;
	        main_core.Dom.removeClass(this.getInnerContainer(), 'checklist-item-selected');
	      }

	      this.getDescendants().forEach(function (descendant) {
	        descendant.unselectAll();
	      });
	    }
	  }, {
	    key: "getSelected",
	    value: function getSelected() {
	      var selected = [];

	      if (this.fields.getIsSelected()) {
	        selected.push(this);
	      }

	      this.getDescendants().forEach(function (descendant) {
	        selected = [].concat(babelHelpers.toConsumableArray(selected), babelHelpers.toConsumableArray(descendant.getSelected()));
	      });
	      return selected;
	    }
	  }, {
	    key: "getSelectedItems",
	    value: function getSelectedItems() {
	      return this.getSelected().filter(function (item) {
	        return !item.isCheckList() && !item.isTaskRoot();
	      });
	    }
	  }, {
	    key: "onSelectCheckboxClick",
	    value: function onSelectCheckboxClick(e) {
	      if (!this.checkCanUpdate()) {
	        e.target.checked = false;
	        return;
	      }

	      this.toggleSelect();
	    }
	  }, {
	    key: "toggleSelect",
	    value: function toggleSelect() {
	      var rootNode = this.getRootNode();

	      if (this.fields.getIsSelected()) {
	        this.container.querySelector("#select_".concat(this.getNodeId())).checked = false;
	        this.fields.setIsSelected(false);
	        rootNode.showEditorPanel(rootNode, this.container);

	        if (!this.checkSelectedItems()) {
	          main_core.Dom.hide(rootNode.panel);
	        }
	      } else {
	        this.container.querySelector("#select_".concat(this.getNodeId())).checked = true;
	        this.fields.setIsSelected(true);
	        rootNode.showEditorPanel(rootNode, this.container);
	      }

	      main_core.Dom.toggleClass(this.getInnerContainer(), 'checklist-item-selected');
	    }
	  }, {
	    key: "onGroupButtonClick",
	    value: function onGroupButtonClick() {
	      if (!this.getRootNode().checkActiveUpdateExist()) {
	        this.toggleGroup();
	      }
	    }
	  }, {
	    key: "toggleGroup",
	    value: function toggleGroup() {
	      if (this.fields.getIsSelected()) {
	        this.unselectAll();
	        this.fields.setIsSelected(false);

	        if (!this.checkSelectedItems()) {
	          main_core.Dom.hide(this.getRootNode().panel);
	        }
	      } else {
	        this.fields.setIsSelected(true);
	      }

	      main_core.Dom.toggleClass(this.container, 'checklist-item-group-editor-collapse');
	      main_core.Dom.toggleClass(this.container, 'checklist-item-group-editor-expand');
	    }
	  }, {
	    key: "onCollapseButtonClick",
	    value: function onCollapseButtonClick() {
	      this.toggleCollapse();
	    }
	  }, {
	    key: "toggleCollapse",
	    value: function toggleCollapse() {
	      main_core.Dom.toggleClass(this.container, 'checklist-collapse');
	      var wrapperList = this.container.querySelector('.checklist-items-wrapper');
	      var wrapperListHeight = "".concat(BX.pos(wrapperList).height, "px");

	      if (main_core.Dom.hasClass(this.container, 'checklist-collapse')) {
	        this.fields.setIsCollapse(true);
	        wrapperList.style.overflow = 'hidden';
	        wrapperList.style.height = wrapperListHeight;
	        setTimeout(function () {
	          wrapperList.style.height = 0;
	        }, 0);
	      } else {
	        this.fields.setIsCollapse(false);
	        wrapperList.style.height = 0;
	        setTimeout(function () {
	          wrapperList.style.height = "".concat(wrapperList.scrollHeight, "px");
	        }, 0);
	        setTimeout(function () {
	          wrapperList.style.height = '';
	          wrapperList.style.overflow = '';
	        }, 250);
	      }
	    }
	  }, {
	    key: "toggleEmpty",
	    value: function toggleEmpty() {
	      main_core.Dom.toggleClass(this.container, 'checklist-empty');
	    }
	  }, {
	    key: "handleCheckListIsComplete",
	    value: function handleCheckListIsComplete() {
	      var checkList = this.getCheckList();
	      var checkListIsComplete = checkList.checkIsComplete();
	      checkList.fields.setIsComplete(checkListIsComplete);

	      if (checkListIsComplete && !main_core.Dom.hasClass(checkList.container, 'checklist-collapse')) {
	        checkList.toggleCollapse();
	      }
	    }
	  }, {
	    key: "handleCheckListIsEmpty",
	    value: function handleCheckListIsEmpty() {
	      var checkList = this.getCheckList();
	      var checkListIsEmpty = checkList.getDescendantsCount() === 0;

	      if (checkListIsEmpty && !main_core.Dom.hasClass(checkList.container, 'checklist-empty') || !checkListIsEmpty && main_core.Dom.hasClass(checkList.container, 'checklist-empty')) {
	        checkList.toggleEmpty();
	      }
	    }
	  }, {
	    key: "handleCheckListChanges",
	    value: function handleCheckListChanges() {
	      this.handleCheckListIsComplete();
	      this.handleCheckListIsEmpty();
	    }
	  }, {
	    key: "checkCanAdd",
	    value: function checkCanAdd() {
	      return this.optionManager.getCanAdd();
	    }
	  }, {
	    key: "checkCanAddAccomplice",
	    value: function checkCanAddAccomplice() {
	      return this.optionManager.getCanAddAccomplice();
	    }
	  }, {
	    key: "checkCanUpdate",
	    value: function checkCanUpdate() {
	      return this.action.canUpdate;
	    }
	  }, {
	    key: "checkCanRemove",
	    value: function checkCanRemove() {
	      return this.action.canRemove;
	    }
	  }, {
	    key: "checkCanToggle",
	    value: function checkCanToggle() {
	      return this.action.canToggle;
	    }
	  }, {
	    key: "checkCanDrag",
	    value: function checkCanDrag() {
	      return this.action.canDrag;
	    }
	  }, {
	    key: "getAttachmentsContainer",
	    value: function getAttachmentsContainer() {
	      return this.container.querySelector("#attachments_".concat(this.getNodeId()));
	    }
	  }, {
	    key: "getSubItemsContainer",
	    value: function getSubItemsContainer() {
	      return this.container.querySelector("#subItems_".concat(this.getNodeId()));
	    }
	  }, {
	    key: "getInnerContainer",
	    value: function getInnerContainer() {
	      return this.container.querySelector('.checklist-item-inner');
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return this.container;
	    }
	  }, {
	    key: "move",
	    value: function move(item) {
	      var position = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'bottom';

	      if (this.getNodeId() === item.getNodeId() || this.findChild(item.getNodeId()) !== null) {
	        return;
	      }

	      var oldParent = this.getParent();
	      var newParent = item.getParent();
	      oldParent.remove(this);

	      if (position === 'top') {
	        newParent.addBefore(this, item);
	      } else {
	        newParent.addAfter(this, item);
	      }

	      CheckListItem.updateParents(oldParent, newParent);

	      if (position === 'top') {
	        main_core.Dom.insertBefore(this.container, item.container);
	      } else {
	        main_core.Dom.insertAfter(this.container, item.container);
	      }

	      this.handleCheckListIsEmpty();
	      this.handleTaskOptions();
	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	    }
	  }, {
	    key: "makeChildOf",
	    value: function makeChildOf(item) {
	      var position = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'bottom';

	      if (item.getDescendantsCount() > 0) {
	        var borderItems = {
	          top: item.getFirstDescendant(),
	          bottom: item.getLastDescendant()
	        };
	        this.move(borderItems[position], position);
	      } else {
	        var oldParent = this.getParent();
	        var newParent = item;
	        oldParent.remove(this);
	        newParent.add(this);
	        CheckListItem.updateParents(oldParent, newParent);
	        main_core.Dom.append(this.container, newParent.getSubItemsContainer());
	        this.handleCheckListIsEmpty();
	        this.handleTaskOptions();
	        main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	      }
	    }
	  }, {
	    key: "tabIn",
	    value: function tabIn() {
	      var _this11 = this;

	      var descendants = this.getParent().getDescendants();
	      var index = descendants.findIndex(function (item) {
	        return item === _this11;
	      });

	      if (index === 0) {
	        return;
	      }

	      this.makeChildOf(descendants[index - 1]);
	    }
	  }, {
	    key: "onTabInClick",
	    value: function onTabInClick() {
	      if (this.checkSelectedItems()) {
	        this.runForEachSelectedItem(function (selectedItem) {
	          selectedItem.tabIn();
	        });
	        return;
	      }

	      this.tabIn();
	      this.retrieveFocus();
	    }
	  }, {
	    key: "tabOut",
	    value: function tabOut() {
	      var parent = this.getParent();

	      if (parent.isCheckList()) {
	        return;
	      }

	      this.move(parent, 'bottom');
	    }
	  }, {
	    key: "onTabOutClick",
	    value: function onTabOutClick() {
	      if (this.checkSelectedItems()) {
	        this.runForEachSelectedItem(function (selectedItem) {
	          selectedItem.tabOut();
	        }, true);
	        return;
	      }

	      this.tabOut();
	      this.retrieveFocus();
	    }
	  }, {
	    key: "addCheckListItem",
	    value: function addCheckListItem() {
	      var _this12 = this;

	      var item = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var dependsOn = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var position = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'after';
	      var itemGet = item instanceof CheckListItem;
	      return new Promise(function (resolve) {
	        var newCheckListItem = itemGet ? item : new CheckListItem();
	        var newCheckListItemLayout;

	        if (dependsOn instanceof CheckListItem) {
	          if (position === 'before') {
	            _this12.addBefore(newCheckListItem, dependsOn);

	            newCheckListItemLayout = newCheckListItem.getLayout();
	            CheckListItem.setDefaultStyles(newCheckListItemLayout);
	            main_core.Dom.insertBefore(newCheckListItemLayout, dependsOn.container);
	          } else if (position === 'after') {
	            _this12.addAfter(newCheckListItem, dependsOn);

	            newCheckListItemLayout = newCheckListItem.getLayout();
	            CheckListItem.setDefaultStyles(newCheckListItemLayout);
	            main_core.Dom.insertAfter(newCheckListItemLayout, dependsOn.container);
	          }
	        } else {
	          _this12.add(newCheckListItem);

	          newCheckListItemLayout = newCheckListItem.getLayout();
	          CheckListItem.setDefaultStyles(newCheckListItemLayout);
	          main_core.Dom.append(newCheckListItemLayout, _this12.getSubItemsContainer());
	        }

	        _this12.updateCounts();

	        _this12.updateIndexes();

	        if (!_this12.isTaskRoot()) {
	          _this12.updateProgress();

	          _this12.handleCheckListIsEmpty();

	          if (!itemGet) {
	            newCheckListItem.toggleUpdateMode();
	          }
	        }

	        setTimeout(function () {
	          newCheckListItemLayout.style.height = "".concat(newCheckListItemLayout.scrollHeight, "px");
	          newCheckListItemLayout.style.opacity = 1;
	        }, 1);
	        setTimeout(function () {
	          newCheckListItemLayout.style.overflow = '';
	          newCheckListItemLayout.style.height = '';
	          newCheckListItemLayout.style.opacity = '';
	          main_core.Dom.removeClass(newCheckListItemLayout, 'checklist-item-show');
	          resolve(newCheckListItem);
	        }, 250);
	      });
	    }
	  }, {
	    key: "onAddCheckListItemClick",
	    value: function onAddCheckListItemClick() {
	      if (this.getRootNode().checkActiveUpdateExist()) {
	        return;
	      }

	      this.addCheckListItem();
	    }
	  }, {
	    key: "getRequestData",
	    value: function getRequestData() {
	      var inputData = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var title = this.fields.getTitle();
	      var data = inputData || [];

	      if (!this.isTaskRoot() && title !== '' && title.length > 0) {
	        var requestData = {
	          NODE_ID: this.getNodeId(),
	          PARENT_NODE_ID: this.getParent().getNodeId(),
	          ID: this.fields.getId(),
	          COPIED_ID: this.fields.getCopiedId(),
	          PARENT_ID: this.fields.getParentId(),
	          TITLE: main_core.Text.decode(this.fields.getTitle()),
	          SORT_INDEX: this.fields.getSortIndex(),
	          IS_COMPLETE: this.fields.getIsComplete(),
	          IS_IMPORTANT: this.fields.getIsImportant(),
	          MEMBERS: [],
	          ATTACHMENTS: {}
	        };
	        this.fields.getMembers().forEach(function (value, key) {
	          var nameFormatted = value.nameFormatted,
	              type = value.type;
	          requestData.MEMBERS.push(babelHelpers.defineProperty({}, key, {
	            TYPE: type,
	            NAME: main_core.Text.decode(nameFormatted)
	          }));
	        });
	        var attachments = this.fields.getAttachments();
	        Object.keys(attachments).forEach(function (id) {
	          requestData.ATTACHMENTS[id] = attachments[id];
	        });
	        data.push(requestData);
	      }

	      this.getDescendants().forEach(function (descendant) {
	        data = descendant.getRequestData(data);
	      });
	      return data;
	    }
	  }, {
	    key: "appendRequestLayout",
	    value: function appendRequestLayout() {
	      if (!this.isTaskRoot()) {
	        var nodeId = this.getNodeId();
	        var attachments = this.fields.getAttachments();
	        var prefix = "".concat(this.optionManager.prefix, "[").concat(nodeId, "]");
	        var membersLayout = '';
	        var attachmentsLayout = '';
	        this.fields.getMembers().forEach(function (value, key) {
	          membersLayout += "<input type=\"hidden\" id=\"MEMBERS_".concat(key, "\" name=\"").concat(prefix, "[MEMBERS][").concat(key, "][TYPE]\" value=\"").concat(value.type, "\"/>");
	        });
	        Object.keys(attachments).forEach(function (id) {
	          attachmentsLayout += "<input type=\"hidden\" id=\"ATTACHMENTS_".concat(id, "\" name=\"").concat(prefix, "[ATTACHMENTS][").concat(id, "]\" value=\"").concat(attachments[id], "\"/>");
	        });
	        var requestLayout = main_core.Tag.render(_templateObject14(), nodeId, prefix, nodeId, prefix, this.getParent().getNodeId(), prefix, this.fields.getId(), prefix, this.fields.getCopiedId(), prefix, this.fields.getParentId(), prefix, this.fields.getTitle(), prefix, this.fields.getSortIndex(), prefix, this.fields.getIsComplete(), prefix, this.fields.getIsImportant(), membersLayout, attachmentsLayout);
	        main_core.Dom.remove(this.container.querySelector("#request_".concat(nodeId)));
	        main_core.Dom.append(requestLayout, this.container);
	      }

	      this.getDescendants().forEach(function (descendant) {
	        descendant.appendRequestLayout();
	      });
	    }
	  }, {
	    key: "getAttachmentsLayout",
	    value: function getAttachmentsLayout() {
	      var _this13 = this;

	      var searchId = this.fields.getId() || this.fields.getCopiedId();
	      var optionManager = this.optionManager;
	      var optionAttachments = optionManager.attachments;
	      var attachmentsLayout = '';

	      if (optionAttachments && searchId in optionAttachments) {
	        var attachments = main_core.Tag.render(_templateObject15(), optionAttachments[searchId]);
	        var stableAttachments = this.getStableAttachments(optionManager.getStableTreeStructure());
	        var attachmentsToDelete = [];

	        if (!attachments) {
	          return attachmentsLayout;
	        }

	        attachmentsLayout = attachments;

	        if (!Array.isArray(attachments)) {
	          attachmentsLayout = [attachments];
	        }

	        Object.keys(attachmentsLayout).forEach(function (key) {
	          var attachment = attachmentsLayout[key];
	          var fileId = attachment.getAttribute('data-bx-id');
	          var extension = CheckListItem.getFileExtension(attachment.getAttribute('data-bx-extension'));
	          var extensionClass = "ui-icon-file-".concat(extension);
	          var iconContainer = attachment.querySelector("#disk-attach-file-".concat(fileId));
	          var deleteButton = main_core.Tag.render(_templateObject16(), _this13.onDeleteAttachmentClick.bind(_this13, fileId));
	          var has = Object.prototype.hasOwnProperty;

	          if (!has.call(stableAttachments, fileId)) {
	            attachmentsToDelete.push(key);
	            return;
	          }

	          if (iconContainer && !main_core.Dom.hasClass(iconContainer, extensionClass)) {
	            main_core.Dom.addClass(iconContainer, extensionClass);
	          }

	          if (_this13.checkCanUpdate()) {
	            main_core.Dom.append(deleteButton, attachment.querySelector('.checklist-item-attachment-file-cover'));
	          }
	        });
	        attachmentsToDelete.sort(function (a, b) {
	          return b - a;
	        });
	        attachmentsToDelete.forEach(function (id) {
	          attachmentsLayout.splice(id, 1);
	        });
	      } else {
	        this.fields.setAttachments({});
	      }

	      return attachmentsLayout;
	    }
	  }, {
	    key: "getStableAttachments",
	    value: function getStableAttachments(item) {
	      var _this14 = this;

	      var fields = item.FIELDS;
	      var id = fields.id || fields.copiedId;

	      if (id === this.fields.getId() || id === this.fields.getCopiedId()) {
	        return fields.attachments;
	      }

	      var found = null;
	      item.DESCENDANTS.forEach(function (descendant) {
	        if (found === null) {
	          found = _this14.getStableAttachments(descendant);
	        }
	      });
	      return found;
	    }
	  }, {
	    key: "getLoadedAttachmentLayout",
	    value: function getLoadedAttachmentLayout(attachment) {
	      var id = attachment.id,
	          name = attachment.name,
	          viewUrl = attachment.viewUrl,
	          size = attachment.size,
	          ext = attachment.ext;
	      var img = '';

	      if (viewUrl) {
	        img = main_core.Tag.render(_templateObject17(), viewUrl, this.onDeleteAttachmentClick.bind(this, id));
	      } else {
	        var extension = CheckListItem.getFileExtension(ext);
	        img = main_core.Tag.render(_templateObject18(), extension, this.onDeleteAttachmentClick.bind(this, id));
	      }

	      return main_core.Tag.render(_templateObject19(), id, id, img, name, name, size);
	    }
	  }, {
	    key: "onAttachmentsLoaderMenuItemClick",
	    value: function onAttachmentsLoaderMenuItemClick() {
	      if (this.filesLoaderPopup !== null) {
	        this.filesLoaderPopup.close();
	      }
	    }
	  }, {
	    key: "getAttachmentsLoaderLayout",
	    value: function getAttachmentsLoaderLayout() {
	      var _this15 = this;

	      var nodeId = this.getNodeId();
	      var prefix = this.optionManager.prefix;
	      var filesChooser = main_core.Tag.render(_templateObject20(), nodeId, prefix, nodeId, nodeId, this.onAttachmentsLoaderMenuItemClick.bind(this), main_core.Loc.getMessage('TASKS_CHECKLIST_FILES_LOADER_POPUP_FROM_COMPUTER'), nodeId, this.onAttachmentsLoaderMenuItemClick.bind(this), main_core.Loc.getMessage('TASKS_CHECKLIST_FILES_LOADER_POPUP_FROM_B24'), this.onAttachmentsLoaderMenuItemClick.bind(this), main_core.Loc.getMessage('TASKS_CHECKLIST_FILES_LOADER_POPUP_FROM_CLOUD'));
	      BX.addCustomEvent(filesChooser, 'OnFileUploadSuccess', this.OnFileUploadSuccess.bind(this));
	      BX.addCustomEvent(filesChooser, 'DiskDLoadFormControllerInit', function (uf) {
	        uf._onUploadProgress = _this15.onUploadProgress.bind(_this15);
	      });
	      return filesChooser;
	    }
	  }, {
	    key: "onUploadProgress",
	    value: function onUploadProgress(item, progress) {
	      var id = item.id,
	          name = item.name,
	          size = item.size;
	      var newProgress = Math.min(progress, 98);

	      if (!this.filesLoaderProgressBars.has(id)) {
	        var myProgress = new BX.UI.ProgressRound({
	          id: "load_progress_".concat(id),
	          value: newProgress,
	          maxValue: 100,
	          width: 69,
	          lineSize: 5,
	          fill: false,
	          color: BX.UI.ProgressRound.Color.PRIMARY,
	          statusType: BX.UI.ProgressRound.Status.INCIRCLE
	        });
	        var filePreview = main_core.Tag.render(_templateObject21(), id, myProgress.getContainer(), name, name, size);
	        this.filesLoaderProgressBars.set(id, myProgress);
	        main_core.Dom.append(filePreview, this.getAttachmentsContainer());
	      }

	      if (!item.progressBarWidth) {
	        item.progressBarWidth = 5;
	      }

	      if (newProgress > item.progressBarWidth) {
	        item.progressBarWidth = Math.ceil(newProgress);
	        item.progressBarWidth = item.progressBarWidth > 100 ? 100 : item.progressBarWidth;

	        if (this.filesLoaderProgressBars.has(id)) {
	          this.filesLoaderProgressBars.get(id).update(item.progressBarWidth);
	        }
	      }
	    }
	  }, {
	    key: "OnFileUploadSuccess",
	    value: function OnFileUploadSuccess(fileResult, uf, file, uploaderFile) {
	      if (typeof file === 'undefined' || typeof uploaderFile === 'undefined') {
	        return;
	      }

	      var attachmentId = fileResult.element_id.toString();
	      var attachment = {
	        id: attachmentId,
	        name: fileResult.element_name,
	        viewUrl: fileResult.element_url,
	        size: uploaderFile.size,
	        ext: uploaderFile.ext
	      };
	      this.fields.addAttachments(babelHelpers.defineProperty({}, attachmentId, attachmentId));
	      this.filesLoaderProgressBars.delete(uploaderFile.id);
	      var attachmentProgress = this.getAttachmentsContainer().querySelector("#disk-attach-".concat(uploaderFile.id));
	      var attachmentLayout = this.getLoadedAttachmentLayout(attachment);

	      if (attachmentProgress) {
	        main_core.Dom.replace(attachmentProgress, attachmentLayout);
	      } else {
	        main_core.Dom.append(attachmentLayout, this.getAttachmentsContainer());
	      }

	      var id = this.fields.getId();
	      var optionAttachments = this.optionManager.attachments;

	      if (optionAttachments) {
	        if (id in optionAttachments) {
	          this.optionManager.attachments[id] += attachmentLayout.outerHTML;
	        } else {
	          this.optionManager.attachments[id] = attachmentLayout.outerHTML;
	        }
	      }

	      main_core.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	    }
	  }, {
	    key: "getTaskRootLayout",
	    value: function getTaskRootLayout(children) {
	      this.container = main_core.Tag.render(_templateObject22(), this.getNodeId(), children);
	      return this.container;
	    }
	  }, {
	    key: "getCheckListLayout",
	    value: function getCheckListLayout(children) {
	      var nodeId = this.getNodeId();
	      var value = this.fields.getCompletedCount();
	      var maxValue = this.fields.getTotalCount();
	      var layouts = {
	        listActionsPanel: main_core.Tag.render(_templateObject23()),
	        groupButton: '',
	        dndButton: main_core.Tag.render(_templateObject24())
	      };

	      if (this.checkCanAdd()) {
	        var addButtonLayout = main_core.Tag.render(_templateObject25(), this.onAddCheckListItemClick.bind(this), main_core.Loc.getMessage('TASKS_CHECKLIST_ADD_NEW_ITEM'));
	        var groupButton = main_core.Tag.render(_templateObject26(), this.onGroupButtonClick.bind(this), main_core.Loc.getMessage('TASKS_CHECKLIST_GROUP_ACTIONS'));
	        main_core.Dom.append(addButtonLayout, layouts.listActionsPanel);
	        layouts.groupButton = groupButton;
	      }

	      if (this.checkCanRemove()) {
	        var removeButtonLayout = main_core.Tag.render(_templateObject27(), this.onDeleteClick.bind(this), main_core.Loc.getMessage('TASKS_CHECKLIST_DELETE_CHECKLIST'));
	        main_core.Dom.append(removeButtonLayout, layouts.listActionsPanel);
	      }

	      if (!this.checkCanDrag()) {
	        layouts.dndButton.style.visibility = 'hidden';
	      }

	      this.progress = new BX.UI.ProgressBar({
	        value: value,
	        maxValue: maxValue,
	        size: BX.UI.ProgressBar.Size.MEDIUM,
	        textAfter: CheckListItem.getProgressText(value, maxValue)
	      });
	      this.container = main_core.Tag.render(_templateObject28(), nodeId, layouts.dndButton, this.onHeaderNameClick.bind(this), this.getTitleLayout(), nodeId, this.progress.getContainer(), layouts.groupButton, this.onCollapseButtonClick.bind(this), nodeId, children, layouts.listActionsPanel);
	      return this.container;
	    }
	  }, {
	    key: "getCheckListItemLayout",
	    value: function getCheckListItemLayout(children) {
	      var nodeId = this.getNodeId();
	      var layouts = {
	        deleteButton: main_core.Tag.render(_templateObject29(), this.onDeleteClick.bind(this)),
	        dndButton: main_core.Tag.render(_templateObject30()),
	        attachments: this.getAttachmentsLayout()
	      };

	      if (!this.checkCanRemove()) {
	        layouts.deleteButton = '';
	      }

	      if (!this.checkCanDrag()) {
	        layouts.dndButton.style.visibility = 'hidden';
	      }

	      this.progress = new BX.UI.ProgressRound({
	        id: "progress_".concat(nodeId),
	        value: this.fields.getCompletedCount(),
	        maxValue: this.fields.getTotalCount(),
	        width: 20,
	        lineSize: 3,
	        fill: false,
	        color: BX.UI.ProgressRound.Color.PRIMARY
	      });
	      this.container = main_core.Tag.render(_templateObject31(), nodeId, this.fields.getIsComplete() ? 'checklist-item-solved' : '', this.onInnerContainerClick.bind(this), layouts.dndButton, this.onCompleteButtonClick.bind(this), nodeId, this.progress.getContainer(), this.fields.getDisplaySortIndex(), this.fields.getIsImportant() ? this.getImportantLayout() : '', this.getTitleLayout(), layouts.deleteButton, nodeId, this.onSelectCheckboxClick.bind(this), nodeId, layouts.attachments, nodeId, children);
	      return this.container;
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var children = [];
	      this.descendants.forEach(function (descendant) {
	        children.push(descendant.getLayout());
	      });

	      if (this.isTaskRoot()) {
	        return this.getTaskRootLayout(children);
	      }

	      if (this.isCheckList()) {
	        var checkListLayout = this.getCheckListLayout(children);
	        this.handleCheckListChanges();
	        return checkListLayout;
	      }

	      return this.getCheckListItemLayout(children);
	    }
	  }]);
	  return CheckListItem;
	}(CompositeTreeItem);

	exports.CheckListItem = CheckListItem;

}((this.BX.Tasks = this.BX.Tasks || {}),BX));
//# sourceMappingURL=check-list-item.bundle.js.map
