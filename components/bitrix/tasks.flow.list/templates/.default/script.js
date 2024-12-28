/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,tasks_flow_editForm,ui_dialogs_messagebox,ui_infoHelper,pull_queuemanager,tasks_flow_teamPopup,tasks_flow_taskQueue,tasks_clue,ui_manual,ui_notification,main_core_events,main_sidepanel,main_core,main_popup,tasks_flow_copilotAdvice) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7;
	var _rowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rowId");
	var _changeLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeLabel");
	var _renderPersonNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPersonNode");
	class QueueAnimationHelper {
	  constructor(rowId) {
	    Object.defineProperty(this, _renderPersonNode, {
	      value: _renderPersonNode2
	    });
	    Object.defineProperty(this, _changeLabel, {
	      value: _changeLabel2
	    });
	    Object.defineProperty(this, _rowId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _rowId)[_rowId] = rowId;
	  }
	  changeAvatarsToEmpty(nodes) {
	    const promises = nodes.map(node => {
	      return new Promise(resolve => {
	        main_core.Dom.addClass(node, '--icon');
	        main_core.Event.bindOnce(node, 'transitionend', () => {
	          main_core.Dom.style(node, null);
	          main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _renderPersonNode)[_renderPersonNode](), node);
	          requestAnimationFrame(() => {
	            resolve();
	          });
	        });
	      });
	    });
	    return Promise.all(promises);
	  }
	  async showAvatars(nodes) {
	    const promises = nodes.map(node => {
	      return new Promise(resolve => {
	        main_core.Dom.removeClass(node, '--hidden');
	        main_core.Event.bindOnce(node, 'transitionend', () => {
	          resolve();
	        });
	      });
	    });
	    return Promise.all(promises);
	  }
	  async hideAvatars(nodes) {
	    const promises = nodes.map(node => {
	      return new Promise(resolve => {
	        main_core.Dom.addClass(node, '--hidden');
	        main_core.Event.bindOnce(node, 'transitionend', () => {
	          resolve(node);
	        });
	      });
	    });
	    return Promise.all(promises);
	  }
	  renderHiddenEmptyAvatar() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="tasks-flow__list-members-icon_element --icon --hidden --center">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderPersonNode)[_renderPersonNode]());
	  }
	  renderHiddenAvatar(user, extraClass = '') {
	    if ('src' in user.photo) {
	      return main_core.Tag.render(_t2 || (_t2 = _`
				<div
					class="tasks-flow__list-members-icon_element --hidden ${0}"
					style="background-image: url('${0}')"
				></div>
			`), extraClass, encodeURI(user.photo.src));
	    }
	    const uiClasses = 'ui-icon ui-icon-common-user ui-icon-xs';
	    return main_core.Tag.render(_t3 || (_t3 = _`
			<div
				class="tasks-flow__list-members-icon_element ${0} --hidden ${0}"
			>
				<i></i>
			</div>
		`), uiClasses, extraClass);
	  }
	  async changeAvatarsToUser(nodes, users) {
	    return new Promise((resolve, reject) => {
	      nodes.forEach((node, index) => {
	        const user = users[index];
	        main_core.Dom.removeClass(node, ['--icon', '--count', '--at-work']);
	        main_core.Dom.clean(node);
	        if ('src' in user.photo) {
	          main_core.Dom.style(node, 'background-image', `url('${encodeURI(user.photo.src)}')`);
	        } else {
	          main_core.Dom.addClass(node, ['ui-icon', 'ui-icon-common-user', 'ui-icon-xs']);
	          main_core.Dom.append(main_core.Tag.render(_t4 || (_t4 = _`<i></i>`)), node);
	        }
	      });
	      requestAnimationFrame(() => {
	        resolve();
	      });
	    });
	  }
	  changeAvatarToCounter(node, count, memberClass = '') {
	    return new Promise((resolve, reject) => {
	      main_core.Dom.removeClass(node, ['ui-icon', 'ui-icon-common-user', 'ui-icon-xs']);
	      main_core.Dom.addClass(node, ['--count', memberClass]);
	      main_core.Dom.clean(node);
	      main_core.Dom.style(node, null);
	      main_core.Dom.append(main_core.Tag.render(_t5 || (_t5 = _`
				<span class="tasks-flow__warning-icon_element-plus">+</span>
			`)), node);
	      main_core.Dom.append(main_core.Tag.render(_t6 || (_t6 = _`
				<span class="tasks-flow__warning-icon_element-number">${0}</span>
			`), parseInt(count, 10)), node);
	      requestAnimationFrame(() => {
	        resolve();
	      });
	    });
	  }
	  changeNodeAttributes(statusId, node, state, isEmpty) {
	    const wrapper = node.querySelector('.tasks-flow__list-members_wrapper');
	    const label = node.querySelector('.tasks-flow__list-members_info');
	    const list = node.querySelector('.tasks-flow__list-members');
	    main_core.Dom.attr(list, 'data-total', state.total);
	    main_core.Dom.attr(list, 'data-subsequence', state.subsequence.join(','));
	    if (isEmpty) {
	      main_core.Dom.removeClass(wrapper, '--link');
	      main_core.Dom.attr(wrapper, 'onclick', null);
	      babelHelpers.classPrivateFieldLooseBase(this, _changeLabel)[_changeLabel](label, main_core.Loc.getMessage('TASKS_FLOW_LIST_NO_TASKS'), true);
	    } else {
	      main_core.Dom.addClass(wrapper, '--link');
	      wrapper.setAttribute('onclick', `BX.Tasks.Flow.Grid.showTaskQueue('${babelHelpers.classPrivateFieldLooseBase(this, _rowId)[_rowId]}', '${statusId}', this)`);
	      babelHelpers.classPrivateFieldLooseBase(this, _changeLabel)[_changeLabel](label, state.label);
	    }
	  }
	  blinkAvatar(node) {
	    return new Promise(resolve => {
	      main_core.Event.bindOnce(node, 'animationend', () => {
	        main_core.Dom.removeClass(node, ['--blink']);
	        resolve();
	      });
	      main_core.Dom.addClass(node, ['--blink']);
	    });
	  }
	  removeClassesWithoutAnimation(node, classes) {
	    main_core.Dom.style(node, 'transition', 'none');
	    main_core.Dom.removeClass(node, classes);
	    // eslint-disable-next-line no-unused-expressions
	    node.offsetHeight;
	    main_core.Dom.style(node, 'transition', '');
	  }
	  defineSubsequenceIndices(currentSubsequence, newSubsequence) {
	    const iteratedSequence = currentSubsequence.length >= newSubsequence.length ? currentSubsequence : newSubsequence;
	    const comparedSequence = currentSubsequence.length >= newSubsequence.length ? newSubsequence : currentSubsequence;
	    return iteratedSequence.reduce((result, value, index) => {
	      if (comparedSequence.includes(value)) {
	        result.presentIndices.push(index);
	      } else {
	        result.absentIndices.push(index);
	      }
	      return result;
	    }, {
	      presentIndices: [],
	      absentIndices: []
	    });
	  }
	}
	function _changeLabel2(node, text, isEmpty = false) {
	  // eslint-disable-next-line no-param-reassign
	  node.textContent = main_core.Text.encode(text);
	  if (isEmpty) {
	    main_core.Dom.removeClass(node, '--link');
	  } else {
	    main_core.Dom.addClass(node, '--link');
	  }
	}
	function _renderPersonNode2() {
	  return main_core.Tag.render(_t7 || (_t7 = _`
			<div
				class="ui-icon-set --person"
				style="--ui-icon-set__icon-color: var(--ui-color-base-50);"
			></div>
		`));
	}

	class QueueState {
	  animate(node, users, state, statusId) {
	    const list = node.querySelector('.tasks-flow__list-members');
	    const children = list.children;
	    const number = list.childElementCount;
	    const variantHandlers = {
	      1: this.animateFromStateWithOneElement.bind(this, list, children, users, state),
	      2: this.animateFromStateWithTwoElement.bind(this, list, children, users, state),
	      3: this.animateFromStateWithThreeElement.bind(this, list, children, users, state),
	      4: this.animateFromStateWithCounter.bind(this, list, children, users, state),
	      default: this.skipAnimation.bind(this)
	    };
	    const animateFrom = variantHandlers[number] || variantHandlers.default;
	    return animateFrom();
	  }
	  async animateFromStateWithOneElement(list, children, users, state) {
	    return Promise.resolve();
	  }
	  async animateFromStateWithTwoElement(list, children, users, state) {
	    return Promise.resolve();
	  }
	  async animateFromStateWithThreeElement(list, children, users, state) {
	    return Promise.resolve();
	  }
	  async animateFromStateWithCounter(list, children, users, state) {
	    return Promise.resolve();
	  }
	  skipAnimation() {
	    return Promise.resolve();
	  }
	}

	var _animationHelper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animationHelper");
	var _moveAvatarsToCenter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("moveAvatarsToCenter");
	var _moveAvatarsToDefaultPosition = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("moveAvatarsToDefaultPosition");
	class EmptyState extends QueueState {
	  constructor(animationHelper) {
	    super(animationHelper);
	    Object.defineProperty(this, _moveAvatarsToDefaultPosition, {
	      value: _moveAvatarsToDefaultPosition2
	    });
	    Object.defineProperty(this, _moveAvatarsToCenter, {
	      value: _moveAvatarsToCenter2
	    });
	    Object.defineProperty(this, _animationHelper, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _animationHelper)[_animationHelper] = animationHelper;
	  }
	  async animateFromStateWithOneElement(list, children, users, state) {
	    console.log('EmptyState');
	    const currentAvatar = list.lastElementChild;
	    main_core.Dom.prepend(babelHelpers.classPrivateFieldLooseBase(this, _animationHelper)[_animationHelper].renderHiddenEmptyAvatar(), list);
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _animationHelper)[_animationHelper].renderHiddenEmptyAvatar(), list);
	    return new Promise(resolve => {
	      requestAnimationFrame(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _moveAvatarsToCenter)[_moveAvatarsToCenter]([...children]);

	        // eslint-disable-next-line promise/catch-or-return
	        babelHelpers.classPrivateFieldLooseBase(this, _animationHelper)[_animationHelper].changeAvatarsToEmpty([currentAvatar]).then(() => {
	          babelHelpers.classPrivateFieldLooseBase(this, _moveAvatarsToDefaultPosition)[_moveAvatarsToDefaultPosition]([...children]);
	          resolve();
	        });
	      });
	    });
	  }
	  async animateFromStateWithTwoElement(list, children, users, state) {
	    const firstAvatar = children[0];
	    const lastAvatar = children[2];
	    const newEmptyAvatar = babelHelpers.classPrivateFieldLooseBase(this, _animationHelper)[_animationHelper].renderHiddenEmptyAvatar();
	    main_core.Dom.prepend(newEmptyAvatar, list);
	    return requestAnimationFrame(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _moveAvatarsToCenter)[_moveAvatarsToCenter]([...children]);
	      return babelHelpers.classPrivateFieldLooseBase(this, _animationHelper)[_animationHelper].changeAvatarsToEmpty([firstAvatar, lastAvatar]).then(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _moveAvatarsToDefaultPosition)[_moveAvatarsToDefaultPosition]([...children]);
	      });
	    });
	  }
	  async animateFromStateWithThreeElement(list, children, users, state) {
	    babelHelpers.classPrivateFieldLooseBase(this, _moveAvatarsToCenter)[_moveAvatarsToCenter]([...children]);
	    return babelHelpers.classPrivateFieldLooseBase(this, _animationHelper)[_animationHelper].changeAvatarsToEmpty([...children]).then(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _moveAvatarsToDefaultPosition)[_moveAvatarsToDefaultPosition]([...children]);
	    });
	  }
	}
	function _moveAvatarsToCenter2(avatars) {
	  avatars.forEach(avatar => {
	    main_core.Dom.addClass(avatar, '--center');
	  });
	}
	function _moveAvatarsToDefaultPosition2(avatars) {
	  avatars.forEach(avatar => {
	    main_core.Dom.removeClass(avatar, ['--center', '--hidden']);
	  });
	}

	var _animationHelper$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animationHelper");
	class SingleState extends QueueState {
	  constructor(animationHelper) {
	    super(animationHelper);
	    Object.defineProperty(this, _animationHelper$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$1)[_animationHelper$1] = animationHelper;
	  }
	  async animateFromStateWithOneElement(list, children, users, state) {
	    const firstAvatar = children[0];
	    return babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$1)[_animationHelper$1].changeAvatarsToUser([firstAvatar], users).then(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$1)[_animationHelper$1].blinkAvatar(firstAvatar);
	    });
	  }
	  async animateFromStateWithTwoElement(list, children, users, state) {
	    const firstAvatar = children[0];
	    const lastAvatar = children[1];
	    const newFirstAvatar = babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$1)[_animationHelper$1].renderHiddenAvatar(users[0], '--invisible');
	    main_core.Dom.prepend(newFirstAvatar, list);
	    return new Promise((resolve, reject) => {
	      main_core.Event.bindOnce(lastAvatar, 'transitionend', () => {
	        main_core.Dom.remove(firstAvatar);
	        main_core.Dom.remove(lastAvatar);
	        main_core.Event.bindOnce(newFirstAvatar, 'transitionend', () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$1)[_animationHelper$1].blinkAvatar(newFirstAvatar);
	          resolve();
	        });
	        main_core.Dom.removeClass(newFirstAvatar, ['--hidden', '--invisible']);
	      });
	      main_core.Dom.addClass(firstAvatar, '--tiny-right');
	      main_core.Dom.addClass(firstAvatar, '--hidden');
	      main_core.Dom.addClass(lastAvatar, '--tiny-left');
	      main_core.Dom.addClass(lastAvatar, '--hidden');
	    });
	  }
	  async animateFromStateWithThreeElement(list, children, users, state) {
	    const firstAvatar = children[0];
	    const middleAvatar = children[1];
	    const lastAvatar = children[2];
	    main_core.Dom.addClass(firstAvatar, ['--center']);
	    main_core.Dom.addClass(lastAvatar, ['--center']);
	    return babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$1)[_animationHelper$1].changeAvatarsToUser([middleAvatar], users).then(() => {
	      main_core.Event.bindOnce(lastAvatar, 'transitionend', () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$1)[_animationHelper$1].blinkAvatar(middleAvatar);
	        main_core.Dom.remove(firstAvatar);
	        main_core.Dom.remove(lastAvatar);
	      });
	      main_core.Dom.addClass(lastAvatar, '--hidden');
	    });
	  }
	}

	var _animationHelper$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animationHelper");
	class DoubleState extends QueueState {
	  constructor(animationHelper) {
	    super(animationHelper);
	    Object.defineProperty(this, _animationHelper$2, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2] = animationHelper;
	  }
	  animateFromStateWithOneElement(list, children, users, state) {
	    return new Promise(resolve => {
	      const firstAvatar = children[0];
	      const newFirstAvatar = babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].renderHiddenAvatar(users[0], '--start-invisible');
	      main_core.Dom.prepend(newFirstAvatar, list);
	      requestAnimationFrame(() => {
	        main_core.Event.bindOnce(firstAvatar, 'transitionend', () => {
	          // eslint-disable-next-line promise/catch-or-return
	          babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].blinkAvatar(newFirstAvatar).then(() => {
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].removeClassesWithoutAnimation(newFirstAvatar, ['--start-invisible', '--tiny-left']);
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].removeClassesWithoutAnimation(firstAvatar, ['--tiny-right']);
	            resolve();
	          });
	        });
	        main_core.Dom.removeClass(newFirstAvatar, ['--hidden']);
	        main_core.Dom.addClass(newFirstAvatar, ['--tiny-left']);
	        main_core.Dom.addClass(firstAvatar, ['--tiny-right']);
	      });
	    });
	  }
	  async animateFromStateWithTwoElement(list, children, users, state) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].changeAvatarsToUser([...children], users);
	  }
	  async animateFromStateWithThreeElement(list, children, users, state) {
	    const currentSubsequence = list.dataset.subsequence.split(',').map(Number);
	    const newSubsequence = state.subsequence;
	    const {
	      presentIndices,
	      absentIndices
	    } = babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].defineSubsequenceIndices(currentSubsequence, newSubsequence);
	    const removeAbsentAvatars = (absentAvatars, resolve) => {
	      // eslint-disable-next-line promise/catch-or-return
	      return babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].hideAvatars(absentAvatars).then(() => {
	        absentAvatars.forEach(absentAvatar => {
	          main_core.Dom.remove(absentAvatar);
	        });
	        resolve();
	      });
	    };
	    const variantMovements = {
	      '1,2': absentAvatars => {
	        return new Promise(resolve => {
	          const middleAvatar = children[1];
	          const lastAvatar = children[2];
	          main_core.Event.bindOnce(lastAvatar, 'transitionend', () => {
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].removeClassesWithoutAnimation(middleAvatar, ['--tiny-left']);
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].removeClassesWithoutAnimation(lastAvatar, ['--tiny-left']);
	          });
	          main_core.Dom.addClass(middleAvatar, '--tiny-left');
	          main_core.Dom.addClass(lastAvatar, '--tiny-left');
	          removeAbsentAvatars(absentAvatars, resolve);
	        });
	      },
	      '0,1': absentAvatars => {
	        return new Promise(resolve => {
	          const firstAvatar = children[0];
	          const middleAvatar = children[1];
	          main_core.Event.bindOnce(firstAvatar, 'transitionend', () => {
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].removeClassesWithoutAnimation(middleAvatar, ['--tiny-right']);
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].removeClassesWithoutAnimation(firstAvatar, ['--tiny-right']);
	          });
	          main_core.Dom.addClass(middleAvatar, '--tiny-right');
	          main_core.Dom.addClass(firstAvatar, '--tiny-right');
	          removeAbsentAvatars(absentAvatars, resolve);
	        });
	      },
	      '0,2': absentAvatars => {
	        return new Promise(resolve => {
	          const firstAvatar = children[0];
	          const lastAvatar = children[2];
	          main_core.Event.bindOnce(lastAvatar, 'transitionend', () => {
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].removeClassesWithoutAnimation(firstAvatar, ['--tiny-right']);
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$2)[_animationHelper$2].removeClassesWithoutAnimation(lastAvatar, ['--tiny-left']);
	          });
	          main_core.Dom.addClass(firstAvatar, '--tiny-right');
	          main_core.Dom.addClass(lastAvatar, '--tiny-left');
	          removeAbsentAvatars(absentAvatars, resolve);
	        });
	      },
	      default: () => {
	        return Promise.resolve();
	      }
	    };
	    const presentSubsequence = presentIndices.join(',');
	    const variantMovement = variantMovements[presentSubsequence] || variantMovements.default;
	    const absentAvatars = [];
	    absentIndices.forEach(absentIndex => {
	      absentAvatars.push(children[absentIndex]);
	    });
	    return variantMovement(absentAvatars);
	  }
	}

	var _animationHelper$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animationHelper");
	class TripleState extends QueueState {
	  constructor(animationHelper) {
	    super(animationHelper);
	    Object.defineProperty(this, _animationHelper$3, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3] = animationHelper;
	  }
	  async animateFromStateWithTwoElement(list, children, users, state) {
	    return new Promise(resolve => {
	      const firstAvatar = children[0];
	      const lastAvatar = children[1];
	      const newFirstAvatar = babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3].renderHiddenAvatar(users[0], '--start-invisible');
	      main_core.Dom.prepend(newFirstAvatar, list);
	      requestAnimationFrame(() => {
	        main_core.Event.bindOnce(firstAvatar, 'transitionend', () => {
	          // eslint-disable-next-line promise/catch-or-return
	          babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3].blinkAvatar(newFirstAvatar).then(() => {
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3].removeClassesWithoutAnimation(newFirstAvatar, ['--start-invisible', '--tiny-left']);
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3].removeClassesWithoutAnimation(firstAvatar, ['--tiny-right']);
	            babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3].removeClassesWithoutAnimation(lastAvatar, ['--tiny-right']);
	            resolve();
	          });
	        });
	        main_core.Dom.removeClass(newFirstAvatar, ['--hidden']);
	        main_core.Dom.addClass(newFirstAvatar, ['--tiny-left']);
	        main_core.Dom.addClass(firstAvatar, ['--tiny-right']);
	        main_core.Dom.addClass(lastAvatar, ['--tiny-right']);
	      });
	    });
	  }
	  async animateFromStateWithThreeElement(list, children, users, state) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3].changeAvatarsToUser([...children], users);
	  }
	  async animateFromStateWithCounter(list, children, users, state) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3].hideAvatars([...children]);
	    await babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3].changeAvatarsToUser([...children], users).then(() => {
	      return babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$3)[_animationHelper$3].showAvatars([...children]);
	    });
	    return Promise.resolve();
	  }
	}

	var _animationHelper$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animationHelper");
	var _counter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counter");
	var _statusId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("statusId");
	class CounterState extends QueueState {
	  constructor(animationHelper) {
	    super(animationHelper);
	    Object.defineProperty(this, _animationHelper$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _counter, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _statusId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$4)[_animationHelper$4] = animationHelper;
	  }
	  animate(node, users, state, statusId) {
	    const maxVisibleNumberAvatars = 99;
	    const visibleAmount = 2;
	    const invisibleAmount = state.total - visibleAmount;
	    babelHelpers.classPrivateFieldLooseBase(this, _counter)[_counter] = Math.min(invisibleAmount, maxVisibleNumberAvatars);
	    babelHelpers.classPrivateFieldLooseBase(this, _statusId)[_statusId] = statusId;
	    return super.animate(node, users, state, statusId);
	  }
	  async animateFromStateWithThreeElement(list, children, users, state) {
	    const firstAvatar = children[0];
	    const middleAvatar = children[1];
	    const lastAvatar = children[2];
	    const newFirstAvatar = babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$4)[_animationHelper$4].renderHiddenAvatar(users[0], '--invisible');
	    const newMiddleAvatar = babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$4)[_animationHelper$4].renderHiddenAvatar(users[1], '--invisible');
	    main_core.Dom.prepend(newMiddleAvatar, list);
	    main_core.Dom.prepend(newFirstAvatar, list);
	    main_core.Event.bindOnce(firstAvatar, 'transitionend', () => {
	      main_core.Dom.remove(firstAvatar);
	      main_core.Dom.removeClass(newFirstAvatar, ['--invisible']);
	    });
	    main_core.Event.bindOnce(middleAvatar, 'transitionend', () => {
	      main_core.Dom.remove(middleAvatar);
	      main_core.Dom.removeClass(newMiddleAvatar, ['--invisible']);
	    });
	    main_core.Dom.removeClass(newFirstAvatar, ['--hidden']);
	    main_core.Dom.removeClass(newMiddleAvatar, ['--hidden']);
	    main_core.Dom.addClass(firstAvatar, ['--right']);
	    main_core.Dom.addClass(middleAvatar, ['--right']);
	    const memberClass = babelHelpers.classPrivateFieldLooseBase(this, _statusId)[_statusId] === 'AT_WORK' ? '--at-work' : '';
	    return babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$4)[_animationHelper$4].changeAvatarToCounter(lastAvatar, babelHelpers.classPrivateFieldLooseBase(this, _counter)[_counter], memberClass).then(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$4)[_animationHelper$4].blinkAvatar(lastAvatar);
	    });
	  }
	}

	var _animationHelper$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animationHelper");
	class QueueAnimationManager {
	  constructor(rowId) {
	    Object.defineProperty(this, _animationHelper$5, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$5)[_animationHelper$5] = new QueueAnimationHelper(rowId);
	  }
	  async animate(animationInfo) {
	    const animationStates = {
	      0: new EmptyState(babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$5)[_animationHelper$5]),
	      1: new SingleState(babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$5)[_animationHelper$5]),
	      2: new DoubleState(babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$5)[_animationHelper$5]),
	      3: new TripleState(babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$5)[_animationHelper$5]),
	      default: new CounterState(babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$5)[_animationHelper$5])
	    };
	    const processStatusAnimation = async newStateOfStatuses => {
	      for (const [statusId, state] of newStateOfStatuses) {
	        const animationState = animationStates[state.total] || animationStates.default;
	        const node = animationInfo.linkToNodes.get(statusId);
	        const users = animationInfo.linkToQueueData.get(statusId);

	        // eslint-disable-next-line no-await-in-loop
	        await animationState.animate(node, users, state, statusId).then(() => {
	          babelHelpers.classPrivateFieldLooseBase(this, _animationHelper$5)[_animationHelper$5].changeNodeAttributes(statusId, node, state, state.total === 0);
	        });
	      }
	    };
	    await processStatusAnimation(animationInfo.newStateOfStatuses);

	    // eslint-disable-next-line no-promise-executor-return
	    return new Promise(resolve => setTimeout(resolve, 500));
	  }
	}

	var _grid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("grid");
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _rowId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rowId");
	var _animationManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animationManager");
	var _prepareData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareData");
	var _getNodes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNodes");
	var _getNewStatuses = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNewStatuses");
	var _getCurrentState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentState");
	var _getNewState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNewState");
	var _getRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRow");
	var _getCell = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCell");
	var _isValidSequence = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isValidSequence");
	var _statesAreEqual = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("statesAreEqual");
	var _compareAndFilterStates = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("compareAndFilterStates");
	var _removeDeletedKeys = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeDeletedKeys");
	class QueueAnimation {
	  constructor(grid, data) {
	    Object.defineProperty(this, _removeDeletedKeys, {
	      value: _removeDeletedKeys2
	    });
	    Object.defineProperty(this, _compareAndFilterStates, {
	      value: _compareAndFilterStates2
	    });
	    Object.defineProperty(this, _statesAreEqual, {
	      value: _statesAreEqual2
	    });
	    Object.defineProperty(this, _isValidSequence, {
	      value: _isValidSequence2
	    });
	    Object.defineProperty(this, _getCell, {
	      value: _getCell2
	    });
	    Object.defineProperty(this, _getRow, {
	      value: _getRow2
	    });
	    Object.defineProperty(this, _getNewState, {
	      value: _getNewState2
	    });
	    Object.defineProperty(this, _getCurrentState, {
	      value: _getCurrentState2
	    });
	    Object.defineProperty(this, _getNewStatuses, {
	      value: _getNewStatuses2
	    });
	    Object.defineProperty(this, _getNodes, {
	      value: _getNodes2
	    });
	    Object.defineProperty(this, _prepareData, {
	      value: _prepareData2
	    });
	    Object.defineProperty(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _rowId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _animationManager, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid] = grid;
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = data;
	    babelHelpers.classPrivateFieldLooseBase(this, _rowId$1)[_rowId$1] = parseInt(data.FLOW_ID, 10);
	    babelHelpers.classPrivateFieldLooseBase(this, _animationManager)[_animationManager] = new QueueAnimationManager(babelHelpers.classPrivateFieldLooseBase(this, _rowId$1)[_rowId$1]);
	  }
	  start() {
	    return new Promise((resolve, reject) => {
	      const animationInfo = babelHelpers.classPrivateFieldLooseBase(this, _prepareData)[_prepareData]();
	      babelHelpers.classPrivateFieldLooseBase(this, _animationManager)[_animationManager].animate(animationInfo).then(() => {
	        resolve();
	      }).catch(() => {
	        reject();
	      });
	    });
	  }
	}
	function _prepareData2() {
	  const linkToNodes = new Map();
	  const linkToQueueData = new Map();
	  const currentStateOfStatuses = new Map();
	  const newStateOfStatuses = new Map();
	  babelHelpers.classPrivateFieldLooseBase(this, _getNodes)[_getNodes]().forEach((node, statusId) => {
	    linkToNodes.set(statusId, node);
	    currentStateOfStatuses.set(statusId, babelHelpers.classPrivateFieldLooseBase(this, _getCurrentState)[_getCurrentState](node));
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _getNewStatuses)[_getNewStatuses]().forEach((status, statusId) => {
	    linkToQueueData.set(statusId, status.queue);
	    newStateOfStatuses.set(statusId, babelHelpers.classPrivateFieldLooseBase(this, _getNewState)[_getNewState](status));
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _compareAndFilterStates)[_compareAndFilterStates](currentStateOfStatuses, newStateOfStatuses);
	  babelHelpers.classPrivateFieldLooseBase(this, _removeDeletedKeys)[_removeDeletedKeys](newStateOfStatuses, linkToNodes, linkToQueueData);
	  return {
	    linkToNodes,
	    linkToQueueData,
	    currentStateOfStatuses,
	    newStateOfStatuses
	  };
	}
	function _getNodes2() {
	  const list = new Map();
	  const addToList = statusId => {
	    list.set(statusId, babelHelpers.classPrivateFieldLooseBase(this, _getCell)[_getCell](statusId));
	  };
	  if ('newStatus' in babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]) {
	    addToList(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].newStatus.id);
	  }
	  if ('oldStatus' in babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]) {
	    addToList(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].oldStatus.id);
	  }
	  return list;
	}
	function _getNewStatuses2() {
	  const list = new Map();
	  const addToList = status => {
	    list.set(status.id, status);
	  };
	  if ('newStatus' in babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]) {
	    addToList(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].newStatus);
	  }
	  if ('oldStatus' in babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]) {
	    addToList(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].oldStatus);
	  }
	  return list;
	}
	function _getCurrentState2(node) {
	  const listNode = node.querySelector('.tasks-flow__list-members');
	  const labelNode = node.querySelector('.tasks-flow__list-members_info');
	  const subsequence = babelHelpers.classPrivateFieldLooseBase(this, _isValidSequence)[_isValidSequence](listNode.dataset.subsequence) ? listNode.dataset.subsequence.split(',').map(Number) : [];
	  return {
	    total: parseInt(listNode.dataset.total, 10),
	    subsequence,
	    label: labelNode.textContent.trim()
	  };
	}
	function _getNewState2(status) {
	  return {
	    total: parseInt(status.total, 10),
	    subsequence: status.queueSubsequence,
	    label: status.date
	  };
	}
	function _getRow2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getRows().getById(babelHelpers.classPrivateFieldLooseBase(this, _rowId$1)[_rowId$1]);
	}
	function _getCell2(columnId) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getRow)[_getRow](babelHelpers.classPrivateFieldLooseBase(this, _rowId$1)[_rowId$1]).getCellById(columnId);
	}
	function _isValidSequence2(sequence) {
	  const regex = /^\d+(,\d+)*$/;
	  return regex.test(sequence);
	}
	function _statesAreEqual2(first, second) {
	  if (first.total !== second.total) {
	    return false;
	  }
	  if (first.subsequence.length !== second.subsequence.length) {
	    return false;
	  }
	  for (let i = 0; i < first.subsequence.length; i++) {
	    if (first.subsequence[i] !== second.subsequence[i]) {
	      return false;
	    }
	  }
	  return true;
	}
	function _compareAndFilterStates2(first, second) {
	  for (const [key, value] of first) {
	    if (second.has(key) && babelHelpers.classPrivateFieldLooseBase(this, _statesAreEqual)[_statesAreEqual](value, second.get(key))) {
	      first.delete(key);
	      second.delete(key);
	    }
	  }
	}
	function _removeDeletedKeys2(newStateOfStatuses, linkToNodes, linkToQueueData) {
	  for (const key of linkToNodes.keys()) {
	    if (!newStateOfStatuses.has(key)) {
	      linkToNodes.delete(key);
	    }
	  }
	  for (const key of linkToQueueData.keys()) {
	    if (!newStateOfStatuses.has(key)) {
	      linkToQueueData.delete(key);
	    }
	  }
	}

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _grid$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("grid");
	var _instantPullHandlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instantPullHandlers");
	var _delayedPullFlowHandlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("delayedPullFlowHandlers");
	var _delayedPullTasksHandlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("delayedPullTasksHandlers");
	var _clueMyTasks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clueMyTasks");
	var _clueCopilotAdvice = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clueCopilotAdvice");
	var _rowIdForMyTasksAhaMoment = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rowIdForMyTasksAhaMoment");
	var _notificationList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("notificationList");
	var _addedFlowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addedFlowId");
	var _loadItemsDelay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadItemsDelay");
	var _reload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("reload");
	var _updateRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateRow");
	var _removeRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeRow");
	var _moveRowToFirstPosition = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("moveRowToFirstPosition");
	var _highlightRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("highlightRow");
	var _isFirstRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFirstRow");
	var _isRowExist = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRowExist");
	var _isFirstPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFirstPage");
	var _getRowById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRowById");
	var _getFirstPinnedRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFirstPinnedRow");
	var _getFirstUnpinnedRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFirstUnpinnedRow");
	var _getCell$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCell");
	var _subscribeToPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToPull");
	var _subscribeToGridEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToGridEvents");
	var _onBeforePull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforePull");
	var _onPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPull");
	var _onBeforeQueueExecute = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforeQueueExecute");
	var _onQueueExecute = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onQueueExecute");
	var _executeQueueAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("executeQueueAnimation");
	var _processItemAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("processItemAnimation");
	var _getMapIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMapIds");
	var _onReload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onReload");
	var _executeQueue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("executeQueue");
	var _commentReadAll = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("commentReadAll");
	var _onFlowAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFlowAdd");
	var _onFlowUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFlowUpdate");
	var _onFlowDelete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFlowDelete");
	var _afterRowUpdated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("afterRowUpdated");
	var _recognizeFlowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("recognizeFlowId");
	var _recognizeTaskId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("recognizeTaskId");
	var _getEntityIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEntityIds");
	var _identifyFlowItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("identifyFlowItems");
	var _identifyTaskItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("identifyTaskItems");
	var _convertTaskItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("convertTaskItems");
	var _uniqueItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uniqueItems");
	var _findTaskAddAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("findTaskAddAction");
	var _findTaskRemoveAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("findTaskRemoveAction");
	var _addTaskRemoveItemToMap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addTaskRemoveItemToMap");
	var _isCurrentUserCreatorOfTheTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCurrentUserCreatorOfTheTask");
	var _showAhaOnMyTasksColumn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showAhaOnMyTasksColumn");
	var _showAhaCopilotAdvice = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showAhaCopilotAdvice");
	var _getBindElementForAhaOnCell = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBindElementForAhaOnCell");
	var _consoleError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("consoleError");
	var _clearAnalyticsParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearAnalyticsParams");
	var _activateHint = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activateHint");
	var _highlightAddedFlow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("highlightAddedFlow");
	var _showFlowCreationWizard = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showFlowCreationWizard");
	var _colorPinnedRows = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("colorPinnedRows");
	var _isPinned = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPinned");
	class Grid {
	  constructor(_params2) {
	    Object.defineProperty(this, _isPinned, {
	      value: _isPinned2
	    });
	    Object.defineProperty(this, _colorPinnedRows, {
	      value: _colorPinnedRows2
	    });
	    Object.defineProperty(this, _showFlowCreationWizard, {
	      value: _showFlowCreationWizard2
	    });
	    Object.defineProperty(this, _highlightAddedFlow, {
	      value: _highlightAddedFlow2
	    });
	    Object.defineProperty(this, _activateHint, {
	      value: _activateHint2
	    });
	    Object.defineProperty(this, _clearAnalyticsParams, {
	      value: _clearAnalyticsParams2
	    });
	    Object.defineProperty(this, _consoleError, {
	      value: _consoleError2
	    });
	    Object.defineProperty(this, _getBindElementForAhaOnCell, {
	      value: _getBindElementForAhaOnCell2
	    });
	    Object.defineProperty(this, _showAhaCopilotAdvice, {
	      value: _showAhaCopilotAdvice2
	    });
	    Object.defineProperty(this, _showAhaOnMyTasksColumn, {
	      value: _showAhaOnMyTasksColumn2
	    });
	    Object.defineProperty(this, _isCurrentUserCreatorOfTheTask, {
	      value: _isCurrentUserCreatorOfTheTask2
	    });
	    Object.defineProperty(this, _addTaskRemoveItemToMap, {
	      value: _addTaskRemoveItemToMap2
	    });
	    Object.defineProperty(this, _findTaskRemoveAction, {
	      value: _findTaskRemoveAction2
	    });
	    Object.defineProperty(this, _findTaskAddAction, {
	      value: _findTaskAddAction2
	    });
	    Object.defineProperty(this, _uniqueItems, {
	      value: _uniqueItems2
	    });
	    Object.defineProperty(this, _convertTaskItems, {
	      value: _convertTaskItems2
	    });
	    Object.defineProperty(this, _identifyTaskItems, {
	      value: _identifyTaskItems2
	    });
	    Object.defineProperty(this, _identifyFlowItems, {
	      value: _identifyFlowItems2
	    });
	    Object.defineProperty(this, _getEntityIds, {
	      value: _getEntityIds2
	    });
	    Object.defineProperty(this, _recognizeTaskId, {
	      value: _recognizeTaskId2
	    });
	    Object.defineProperty(this, _recognizeFlowId, {
	      value: _recognizeFlowId2
	    });
	    Object.defineProperty(this, _afterRowUpdated, {
	      value: _afterRowUpdated2
	    });
	    Object.defineProperty(this, _onFlowDelete, {
	      value: _onFlowDelete2
	    });
	    Object.defineProperty(this, _onFlowUpdate, {
	      value: _onFlowUpdate2
	    });
	    Object.defineProperty(this, _onFlowAdd, {
	      value: _onFlowAdd2
	    });
	    Object.defineProperty(this, _commentReadAll, {
	      value: _commentReadAll2
	    });
	    Object.defineProperty(this, _executeQueue, {
	      value: _executeQueue2
	    });
	    Object.defineProperty(this, _onReload, {
	      value: _onReload2
	    });
	    Object.defineProperty(this, _getMapIds, {
	      value: _getMapIds2
	    });
	    Object.defineProperty(this, _processItemAnimation, {
	      value: _processItemAnimation2
	    });
	    Object.defineProperty(this, _executeQueueAnimation, {
	      value: _executeQueueAnimation2
	    });
	    Object.defineProperty(this, _onQueueExecute, {
	      value: _onQueueExecute2
	    });
	    Object.defineProperty(this, _onBeforeQueueExecute, {
	      value: _onBeforeQueueExecute2
	    });
	    Object.defineProperty(this, _onPull, {
	      value: _onPull2
	    });
	    Object.defineProperty(this, _onBeforePull, {
	      value: _onBeforePull2
	    });
	    Object.defineProperty(this, _subscribeToGridEvents, {
	      value: _subscribeToGridEvents2
	    });
	    Object.defineProperty(this, _subscribeToPull, {
	      value: _subscribeToPull2
	    });
	    Object.defineProperty(this, _getCell$1, {
	      value: _getCell2$1
	    });
	    Object.defineProperty(this, _getFirstUnpinnedRow, {
	      value: _getFirstUnpinnedRow2
	    });
	    Object.defineProperty(this, _getFirstPinnedRow, {
	      value: _getFirstPinnedRow2
	    });
	    Object.defineProperty(this, _getRowById, {
	      value: _getRowById2
	    });
	    Object.defineProperty(this, _isFirstPage, {
	      value: _isFirstPage2
	    });
	    Object.defineProperty(this, _isRowExist, {
	      value: _isRowExist2
	    });
	    Object.defineProperty(this, _isFirstRow, {
	      value: _isFirstRow2
	    });
	    Object.defineProperty(this, _highlightRow, {
	      value: _highlightRow2
	    });
	    Object.defineProperty(this, _moveRowToFirstPosition, {
	      value: _moveRowToFirstPosition2
	    });
	    Object.defineProperty(this, _removeRow, {
	      value: _removeRow2
	    });
	    Object.defineProperty(this, _updateRow, {
	      value: _updateRow2
	    });
	    Object.defineProperty(this, _reload, {
	      value: _reload2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _grid$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _instantPullHandlers, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _delayedPullFlowHandlers, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _delayedPullTasksHandlers, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _clueMyTasks, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _clueCopilotAdvice, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _rowIdForMyTasksAhaMoment, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _notificationList, {
	      writable: true,
	      value: new Set()
	    });
	    Object.defineProperty(this, _addedFlowId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _loadItemsDelay, {
	      writable: true,
	      value: 500
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = _params2;
	    babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1] = BX.Main.gridManager.getById(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].gridId).instance;
	    babelHelpers.classPrivateFieldLooseBase(this, _instantPullHandlers)[_instantPullHandlers] = {
	      comment_read_all: babelHelpers.classPrivateFieldLooseBase(this, _commentReadAll)[_commentReadAll],
	      flow_add: babelHelpers.classPrivateFieldLooseBase(this, _onFlowAdd)[_onFlowAdd],
	      flow_delete: babelHelpers.classPrivateFieldLooseBase(this, _onFlowDelete)[_onFlowDelete]
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _delayedPullFlowHandlers)[_delayedPullFlowHandlers] = {
	      flow_update: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate]
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _delayedPullTasksHandlers)[_delayedPullTasksHandlers] = {
	      comment_add: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate],
	      task_add: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate],
	      task_update: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate],
	      task_view: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate],
	      task_remove: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate]
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToPull)[_subscribeToPull]();
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToGridEvents)[_subscribeToGridEvents]();
	    babelHelpers.classPrivateFieldLooseBase(this, _clearAnalyticsParams)[_clearAnalyticsParams]();
	    babelHelpers.classPrivateFieldLooseBase(this, _activateHint)[_activateHint]();
	    babelHelpers.classPrivateFieldLooseBase(this, _colorPinnedRows)[_colorPinnedRows]();
	    babelHelpers.classPrivateFieldLooseBase(this, _showFlowCreationWizard)[_showFlowCreationWizard]();
	    babelHelpers.classPrivateFieldLooseBase(this, _showAhaCopilotAdvice)[_showAhaCopilotAdvice]();
	  }
	  activateFlow(flowId) {
	    // eslint-disable-next-line promise/catch-or-return
	    main_core.ajax.runAction('tasks.flow.Flow.activate', {
	      data: {
	        flowId
	      }
	    }).then(() => {});
	  }
	  pinFlow(flowId, skipNotify = false) {
	    // eslint-disable-next-line promise/catch-or-return
	    main_core.ajax.runAction('tasks.flow.Flow.pin', {
	      data: {
	        flowId
	      }
	    }).then(response => {
	      if (!skipNotify) {
	        const code = response.data ? 'TASKS_FLOW_LIST_FLOW_PINNED' : 'TASKS_FLOW_LIST_FLOW_UNPINNED';
	        ui_notification.UI.Notification.Center.notify({
	          content: BX.Loc.getMessage(code),
	          actions: [{
	            title: BX.Loc.getMessage('TASKS_FLOW_LIST_FLOW_PIN_CANCEL'),
	            events: {
	              click: (event, baloon) => {
	                this.pinFlow(flowId, true);
	                baloon.close();
	              }
	            }
	          }]
	        });
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _updateRow)[_updateRow](flowId);
	    });
	  }
	  removeFlow(flowId) {
	    const message = new ui_dialogs_messagebox.MessageBox({
	      message: main_core.Loc.getMessage('TASKS_FLOW_LIST_CONFIRM_REMOVE_MESSAGE'),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('TASKS_FLOW_LIST_CONFIRM_REMOVE_BUTTON'),
	      popupOptions: {
	        id: `tasks-flow-remove-confirm-${flowId}`
	      },
	      onOk: () => {
	        message.close();
	        babelHelpers.classPrivateFieldLooseBase(this, _updateRow)[_updateRow](flowId, 'remove');
	      },
	      onCancel: () => {
	        message.close();
	      }
	    });
	    message.show();
	  }
	  showTeam(flowId, bindElement) {
	    tasks_flow_teamPopup.TeamPopup.showInstance({
	      flowId,
	      bindElement
	    });
	  }
	  showTaskQueue(flowId, type, bindElement) {
	    tasks_flow_taskQueue.TaskQueue.showInstance({
	      flowId,
	      type,
	      bindElement
	    });
	  }
	  showFlowLimit() {
	    ui_infoHelper.FeaturePromotersRegistry.getPromoter({
	      code: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].flowLimitCode
	    }).show();
	  }
	  showNotificationHint(notificationId, textHint) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _notificationList)[_notificationList].has(notificationId)) {
	      BX.UI.Notification.Center.notify({
	        id: notificationId,
	        content: textHint,
	        width: 'auto'
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _notificationList)[_notificationList].add(notificationId);
	      main_core_events.EventEmitter.subscribeOnce('UI.Notification.Balloon:onClose', baseEvent => {
	        const closingBalloon = baseEvent.getTarget();
	        if (closingBalloon.getId() === notificationId) {
	          babelHelpers.classPrivateFieldLooseBase(this, _notificationList)[_notificationList].delete(notificationId);
	        }
	      });
	    }
	  }
	  showGuide(demoSuffix) {
	    ui_manual.Manual.show({
	      manualCode: 'flows',
	      urlParams: {
	        utm_source: 'portal',
	        utm_medium: 'referral'
	      },
	      analytics: {
	        tool: 'tasks',
	        category: 'flows',
	        event: 'flow_guide_view',
	        c_section: 'tasks',
	        c_sub_section: 'flows_grid',
	        c_element: 'guide_button',
	        p1: `isDemo_${demoSuffix}`
	      }
	    });
	  }
	}
	function _reload2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].reload(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUrl);
	}
	function _updateRow2(flowId, action) {
	  babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].updateRow(flowId, {
	    action,
	    currentPage: babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].getCurrentPage()
	  }, babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUrl, babelHelpers.classPrivateFieldLooseBase(this, _afterRowUpdated)[_afterRowUpdated].bind(this));
	}
	function _removeRow2(rowId) {
	  babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].removeRow(rowId);
	}
	function _moveRowToFirstPosition2(rowId) {
	  return new Promise(resolve => {
	    const inputRow = babelHelpers.classPrivateFieldLooseBase(this, _getRowById)[_getRowById](rowId);
	    const firstRow = babelHelpers.classPrivateFieldLooseBase(this, _isPinned)[_isPinned](inputRow.getNode()) ? babelHelpers.classPrivateFieldLooseBase(this, _getFirstPinnedRow)[_getFirstPinnedRow]() : babelHelpers.classPrivateFieldLooseBase(this, _getFirstUnpinnedRow)[_getFirstUnpinnedRow]();

	    // eslint-disable-next-line @bitrix24/bitrix24-rules/no-native-dom-methods
	    babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].getRows().insertBefore(rowId, firstRow.getId());

	    // eslint-disable-next-line promise/catch-or-return
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightRow)[_highlightRow](rowId).then(() => {
	      resolve();
	    });
	  });
	}
	function _highlightRow2(rowId) {
	  return new Promise(resolve => {
	    const rowNode = babelHelpers.classPrivateFieldLooseBase(this, _getRowById)[_getRowById](rowId).getNode();
	    main_core.Event.bindOnce(rowNode, 'animationend', () => {
	      resolve();
	      main_core.Dom.removeClass(rowNode, 'tasks-flow__list-flow-highlighted');
	    });
	    main_core.Dom.addClass(rowNode, 'tasks-flow__list-flow-highlighted');
	  });
	}
	function _isFirstRow2(rowId) {
	  const inputRow = babelHelpers.classPrivateFieldLooseBase(this, _getRowById)[_getRowById](rowId);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isPinned)[_isPinned](inputRow.getNode())) {
	    const row = babelHelpers.classPrivateFieldLooseBase(this, _getFirstPinnedRow)[_getFirstPinnedRow]();
	    return parseInt(row == null ? void 0 : row.getId(), 10) === rowId;
	  }
	  const row = babelHelpers.classPrivateFieldLooseBase(this, _getFirstUnpinnedRow)[_getFirstUnpinnedRow]();
	  return parseInt(row == null ? void 0 : row.getId(), 10) === rowId;
	}
	function _isRowExist2(rowId) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getRowById)[_getRowById](rowId) !== null;
	}
	function _isFirstPage2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].getCurrentPage() === 1;
	}
	function _getRowById2(rowId) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].getRows().getById(rowId);
	}
	function _getFirstPinnedRow2() {
	  const rows = babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].getRows().getBodyChild();
	  return rows.find(row => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isPinned)[_isPinned](row.getNode());
	  });
	}
	function _getFirstUnpinnedRow2() {
	  const rows = babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].getRows().getBodyChild();
	  return rows.find(row => {
	    return !babelHelpers.classPrivateFieldLooseBase(this, _isPinned)[_isPinned](row.getNode());
	  });
	}
	function _getCell2$1(rowId, columnId) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getRowById)[_getRowById](rowId).getCellById(columnId);
	}
	function _subscribeToPull2() {
	  new pull_queuemanager.QueueManager({
	    moduleId: 'tasks',
	    userId: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUserId,
	    config: {
	      loadItemsDelay: babelHelpers.classPrivateFieldLooseBase(this, _loadItemsDelay)[_loadItemsDelay]
	    },
	    additionalData: {},
	    events: {
	      onBeforePull: event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onBeforePull)[_onBeforePull](event);
	      },
	      onPull: event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onPull)[_onPull](event);
	      }
	    },
	    callbacks: {
	      onBeforeQueueExecute: items => {
	        return babelHelpers.classPrivateFieldLooseBase(this, _onBeforeQueueExecute)[_onBeforeQueueExecute](items);
	      },
	      onQueueExecute: items => {
	        return babelHelpers.classPrivateFieldLooseBase(this, _onQueueExecute)[_onQueueExecute](items);
	      },
	      onReload: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onReload)[_onReload]();
	      }
	    }
	  });
	}
	function _subscribeToGridEvents2() {
	  main_core_events.EventEmitter.subscribe('Grid::updated', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _activateHint)[_activateHint]();
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightAddedFlow)[_highlightAddedFlow]();
	    babelHelpers.classPrivateFieldLooseBase(this, _colorPinnedRows)[_colorPinnedRows]();
	  });
	}
	function _onBeforePull2(event) {
	  const {
	    pullData: {
	      command,
	      params
	    }
	  } = event.data;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _instantPullHandlers)[_instantPullHandlers][command]) {
	    const flowId = babelHelpers.classPrivateFieldLooseBase(this, _recognizeFlowId)[_recognizeFlowId](params);
	    babelHelpers.classPrivateFieldLooseBase(this, _instantPullHandlers)[_instantPullHandlers][command].apply(this, [params, flowId]);
	  }
	}
	function _onPull2(event) {
	  const {
	    pullData: {
	      command,
	      params
	    },
	    promises
	  } = event.data;
	  if (Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _delayedPullFlowHandlers)[_delayedPullFlowHandlers]).includes(command)) {
	    const flowId = babelHelpers.classPrivateFieldLooseBase(this, _recognizeFlowId)[_recognizeFlowId](params);
	    if (flowId) {
	      promises.push(Promise.resolve({
	        data: {
	          id: flowId,
	          action: command,
	          actionParams: params
	        }
	      }));
	    }
	  }
	  if (Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _delayedPullTasksHandlers)[_delayedPullTasksHandlers]).includes(command)) {
	    const taskId = babelHelpers.classPrivateFieldLooseBase(this, _recognizeTaskId)[_recognizeTaskId](params);
	    if (taskId) {
	      promises.push(Promise.resolve({
	        data: {
	          id: taskId,
	          action: command,
	          actionParams: params
	        }
	      }));
	    }
	  }
	}
	function _onBeforeQueueExecute2(items) {
	  return Promise.resolve();
	}
	async function _onQueueExecute2(items) {
	  const flowItems = babelHelpers.classPrivateFieldLooseBase(this, _identifyFlowItems)[_identifyFlowItems](items);
	  const taskItems = babelHelpers.classPrivateFieldLooseBase(this, _identifyTaskItems)[_identifyTaskItems](items);
	  if (taskItems.length === 0) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _executeQueueAnimation)[_executeQueueAnimation](flowItems);
	    return babelHelpers.classPrivateFieldLooseBase(this, _executeQueue)[_executeQueue](flowItems, babelHelpers.classPrivateFieldLooseBase(this, _delayedPullFlowHandlers)[_delayedPullFlowHandlers]);
	  }
	  let mapIds = await babelHelpers.classPrivateFieldLooseBase(this, _getMapIds)[_getMapIds](babelHelpers.classPrivateFieldLooseBase(this, _getEntityIds)[_getEntityIds](taskItems));
	  const taskRemoveItem = babelHelpers.classPrivateFieldLooseBase(this, _findTaskRemoveAction)[_findTaskRemoveAction](taskItems);
	  if (taskRemoveItem) {
	    mapIds = babelHelpers.classPrivateFieldLooseBase(this, _addTaskRemoveItemToMap)[_addTaskRemoveItemToMap](taskRemoveItem, mapIds);
	  }
	  const convertedTaskItems = babelHelpers.classPrivateFieldLooseBase(this, _convertTaskItems)[_convertTaskItems](taskItems, mapIds);
	  const taskAddItem = babelHelpers.classPrivateFieldLooseBase(this, _findTaskAddAction)[_findTaskAddAction](convertedTaskItems);
	  if (taskAddItem && babelHelpers.classPrivateFieldLooseBase(this, _isCurrentUserCreatorOfTheTask)[_isCurrentUserCreatorOfTheTask](taskAddItem)) {
	    const {
	      data: {
	        id
	      }
	    } = taskAddItem;
	    babelHelpers.classPrivateFieldLooseBase(this, _rowIdForMyTasksAhaMoment)[_rowIdForMyTasksAhaMoment] = id;
	  }
	  const allItems = [...flowItems, ...convertedTaskItems];
	  await babelHelpers.classPrivateFieldLooseBase(this, _executeQueueAnimation)[_executeQueueAnimation](allItems);
	  return babelHelpers.classPrivateFieldLooseBase(this, _executeQueue)[_executeQueue](babelHelpers.classPrivateFieldLooseBase(this, _uniqueItems)[_uniqueItems](allItems), {
	    ...babelHelpers.classPrivateFieldLooseBase(this, _delayedPullFlowHandlers)[_delayedPullFlowHandlers],
	    ...babelHelpers.classPrivateFieldLooseBase(this, _delayedPullTasksHandlers)[_delayedPullTasksHandlers]
	  });
	}
	async function _executeQueueAnimation2(items) {
	  const processItemAnimation = async () => {
	    for (const item of items) {
	      // eslint-disable-next-line no-await-in-loop
	      await babelHelpers.classPrivateFieldLooseBase(this, _processItemAnimation)[_processItemAnimation](item);
	    }
	  };
	  await processItemAnimation();
	  return Promise.resolve();
	}
	function _processItemAnimation2(item) {
	  const {
	    data: {
	      action,
	      actionParams,
	      id
	    }
	  } = item;
	  return new Promise((resolve, reject) => {
	    if (action === 'flow_update' && 'activity' in actionParams && babelHelpers.classPrivateFieldLooseBase(this, _isRowExist)[_isRowExist](id)) {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isFirstPage)[_isFirstPage]() && !babelHelpers.classPrivateFieldLooseBase(this, _isFirstRow)[_isFirstRow](id)) {
	        // eslint-disable-next-line promise/catch-or-return
	        babelHelpers.classPrivateFieldLooseBase(this, _moveRowToFirstPosition)[_moveRowToFirstPosition](id).then(() => {
	          new QueueAnimation(babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1], actionParams).start().then(() => resolve()).catch(() => resolve());
	        });
	      } else {
	        new QueueAnimation(babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1], actionParams).start().then(() => resolve()).catch(() => resolve());
	      }
	    } else {
	      resolve();
	    }
	  });
	}
	function _getMapIds2(taskIds) {
	  return new Promise(resolve => {
	    // eslint-disable-next-line promise/catch-or-return
	    main_core.ajax.runComponentAction('bitrix:tasks.flow.list', 'getMapIds', {
	      mode: 'class',
	      data: {
	        taskIds
	      }
	    }).then(response => {
	      resolve(main_core.Type.isArray(response.data) ? {} : response.data);
	    }).catch(error => {
	      babelHelpers.classPrivateFieldLooseBase(this, _consoleError)[_consoleError]('getMapIds', error);
	    });
	  });
	}
	function _onReload2(event) {}
	function _executeQueue2(items, handlers) {
	  return new Promise((resolve, reject) => {
	    items.forEach(item => {
	      const {
	        data: {
	          action,
	          actionParams,
	          id
	        }
	      } = item;
	      if (handlers[action]) {
	        handlers[action].apply(this, [actionParams, id]);
	      }
	    });
	    resolve();
	  });
	}
	function _commentReadAll2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _reload)[_reload]();
	}
	function _onFlowAdd2(data, flowId) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isRowExist)[_isRowExist](flowId)) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFirstPage)[_isFirstPage]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId] = flowId;
	    babelHelpers.classPrivateFieldLooseBase(this, _reload)[_reload]();
	  }
	}
	function _onFlowUpdate2(data, flowId) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRowExist)[_isRowExist](flowId)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _updateRow)[_updateRow](flowId, 'update');
	}
	function _onFlowDelete2(data, flowId) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRowExist)[_isRowExist](flowId)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _removeRow)[_removeRow](flowId);
	}
	function _afterRowUpdated2(id, data, grid, response) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _rowIdForMyTasksAhaMoment)[_rowIdForMyTasksAhaMoment]) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks] && babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks].isShown()) {
	      const bindElement = babelHelpers.classPrivateFieldLooseBase(this, _getBindElementForAhaOnCell)[_getBindElementForAhaOnCell](babelHelpers.classPrivateFieldLooseBase(this, _rowIdForMyTasksAhaMoment)[_rowIdForMyTasksAhaMoment], 'MY_TASKS', '.tasks-flow__list-my-tasks span');
	      if (bindElement) {
	        babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks].adjustPosition(bindElement);
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks].close();
	      }
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isAhaShownOnMyTasksColumn === false && babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showAhaOnMyTasksColumn)[_showAhaOnMyTasksColumn](babelHelpers.classPrivateFieldLooseBase(this, _rowIdForMyTasksAhaMoment)[_rowIdForMyTasksAhaMoment]);
	    }
	  }
	}
	function _recognizeFlowId2(pullData) {
	  if ('FLOW_ID' in pullData) {
	    return parseInt(pullData.FLOW_ID, 10);
	  }
	  return 0;
	}
	function _recognizeTaskId2(pullData) {
	  if ('TASK_ID' in pullData) {
	    return parseInt(pullData.TASK_ID, 10);
	  }
	  if ('taskId' in pullData) {
	    return parseInt(pullData.taskId, 10);
	  }
	  if ('entityXmlId' in pullData && pullData.entityXmlId.indexOf('TASK_') === 0) {
	    return parseInt(pullData.entityXmlId.slice(5), 10);
	  }
	  return 0;
	}
	function _getEntityIds2(pullItems) {
	  const entityIds = [];
	  pullItems.forEach(item => {
	    const {
	      data: {
	        id
	      }
	    } = item;
	    entityIds.push(id);
	  });
	  return entityIds;
	}
	function _identifyFlowItems2(pullItems) {
	  return pullItems.filter(item => {
	    const {
	      data: {
	        action
	      }
	    } = item;
	    return Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _delayedPullFlowHandlers)[_delayedPullFlowHandlers]).includes(action);
	  });
	}
	function _identifyTaskItems2(pullItems) {
	  return pullItems.filter(item => {
	    const {
	      data: {
	        action
	      }
	    } = item;
	    return Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _delayedPullTasksHandlers)[_delayedPullTasksHandlers]).includes(action);
	  });
	}
	function _convertTaskItems2(pullItems, mapIds) {
	  const tasksItems = [];

	  // Replace the task id with the flow id.
	  pullItems.forEach(item => {
	    const {
	      data: {
	        id
	      }
	    } = item;
	    if (id in mapIds) {
	      // eslint-disable-next-line no-param-reassign,unicorn/consistent-destructuring
	      item.data.id = mapIds[id];
	      tasksItems.push(item);
	    }
	  });
	  return tasksItems;
	}
	function _uniqueItems2(items) {
	  const uniqueItems = items.reduce((accumulator, currentItem) => {
	    if (!accumulator[currentItem.data.id]) {
	      accumulator[currentItem.data.id] = currentItem;
	    }
	    return accumulator;
	  }, {});
	  return Object.values(uniqueItems);
	}
	function _findTaskAddAction2(pullItems) {
	  return pullItems.find(item => item.data.action === 'task_add');
	}
	function _findTaskRemoveAction2(pullItems) {
	  return pullItems.find(item => item.data.action === 'task_remove');
	}
	function _addTaskRemoveItemToMap2(pullItem, mapIds) {
	  var _pullItem$data$action;
	  // eslint-disable-next-line no-param-reassign
	  mapIds[pullItem.data.id] = (_pullItem$data$action = pullItem.data.actionParams) == null ? void 0 : _pullItem$data$action.FLOW_ID;
	  return mapIds;
	}
	function _isCurrentUserCreatorOfTheTask2(pullItem) {
	  var _pullItem$data$action2, _pullItem$data$action3;
	  const createdBy = (_pullItem$data$action2 = pullItem.data.actionParams) == null ? void 0 : (_pullItem$data$action3 = _pullItem$data$action2.AFTER) == null ? void 0 : _pullItem$data$action3.CREATED_BY;
	  return parseInt(createdBy, 10) === parseInt(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUserId, 10);
	}
	function _showAhaOnMyTasksColumn2(rowId) {
	  const bindElement = babelHelpers.classPrivateFieldLooseBase(this, _getBindElementForAhaOnCell)[_getBindElementForAhaOnCell](rowId, 'MY_TASKS', '.tasks-flow__list-my-tasks span');
	  if (bindElement) {
	    babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks] = new tasks_clue.Clue({
	      id: `my_tasks_${babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUserId}`,
	      autoSave: true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks].show(tasks_clue.Clue.SPOT.MY_TASKS, bindElement);
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isAhaShownOnMyTasksColumn = true;
	  }
	}
	function _showAhaCopilotAdvice2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isAhaShownCopilotAdvice) {
	    return;
	  }
	  const firstRow = babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].getRows().getBodyChild()[0];
	  if (!firstRow) {
	    return;
	  }
	  const bindElement = babelHelpers.classPrivateFieldLooseBase(this, _getBindElementForAhaOnCell)[_getBindElementForAhaOnCell](firstRow.getId(), 'EFFICIENCY', '.tasks-flow__efficiency-copilot-icon-wrapper');
	  if (bindElement) {
	    babelHelpers.classPrivateFieldLooseBase(this, _clueCopilotAdvice)[_clueCopilotAdvice] = new tasks_clue.Clue({
	      id: 'flow_copilot_advice',
	      autoSave: true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _clueCopilotAdvice)[_clueCopilotAdvice].show(tasks_clue.Clue.SPOT.FLOW_COPILOT_ADVICE, bindElement);
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isAhaShownCopilotAdvice = true;
	  }
	}
	function _getBindElementForAhaOnCell2(rowId, columnId, selector) {
	  var _babelHelpers$classPr;
	  return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getCell$1)[_getCell$1](rowId, columnId)) == null ? void 0 : _babelHelpers$classPr.querySelector(selector);
	}
	function _consoleError2(action, error) {
	  // eslint-disable-next-line no-console
	  console.error(`BX.Tasks.Flow.Grid: ${action} error`, error);
	}
	function _clearAnalyticsParams2() {
	  const uri = new main_core.Uri(window.location.href);
	  const section = uri.getQueryParam('ta_sec');
	  if (section) {
	    uri.removeQueryParam('ta_cat', 'ta_sec', 'ta_sub', 'ta_el', 'p1', 'p2', 'p3', 'p4', 'p5');
	    window.history.replaceState(null, null, uri.toString());
	  }
	}
	function _activateHint2() {
	  BX.UI.Hint.init(babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].getContainer());
	}
	function _highlightAddedFlow2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId] !== null && babelHelpers.classPrivateFieldLooseBase(this, _isRowExist)[_isRowExist](babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightRow)[_highlightRow](babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId]);
	    babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId] = null;
	  }
	}
	function _showFlowCreationWizard2() {
	  const uri = new main_core.Uri(window.location.href);
	  const demoFlowId = uri.getQueryParam('demo_flow');
	  if (demoFlowId) {
	    uri.removeQueryParam('demo_flow');
	    window.history.replaceState(null, null, uri.toString());
	    tasks_flow_editForm.EditForm.createInstance({
	      flowId: demoFlowId,
	      demoFlow: 'Y'
	    });
	  }
	  const createFlow = uri.getQueryParam('create_flow');
	  if (createFlow) {
	    uri.removeQueryParam('create_flow');
	    window.history.replaceState(null, null, uri.toString());
	    tasks_flow_editForm.EditForm.createInstance({
	      guideFlow: 'Y'
	    });
	  }
	}
	function _colorPinnedRows2() {
	  const rows = babelHelpers.classPrivateFieldLooseBase(this, _grid$1)[_grid$1].getRows().getRows();
	  rows.forEach(row => {
	    const node = row.getNode();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isPinned)[_isPinned](node)) {
	      main_core.Dom.addClass(node, 'tasks-flow_list_row_pinned');
	    } else {
	      main_core.Dom.removeClass(node, 'tasks-flow_list_row_pinned');
	    }
	  });
	}
	function _isPinned2(node) {
	  return main_core.Type.isDomNode(node.querySelector('.main-grid-cell-content-action-pin.main-grid-cell-content-action-active'));
	}

	var _props = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("props");
	var _filter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filter");
	var _MIN_QUERY_LENGTH = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("MIN_QUERY_LENGTH");
	var _fields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fields");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _updateFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFields");
	var _unSubscribeToEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unSubscribeToEvents");
	var _subscribeToEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToEvents");
	var _inputFilterHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFilterHandler");
	var _applyFilterHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyFilterHandler");
	var _counterClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counterClickHandler");
	var _toggleByField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleByField");
	var _isFilteredByFieldValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFilteredByFieldValue");
	var _isFilteredByField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFilteredByField");
	var _setActive = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setActive");
	class Filter {
	  constructor(props) {
	    Object.defineProperty(this, _setActive, {
	      value: _setActive2
	    });
	    Object.defineProperty(this, _isFilteredByField, {
	      value: _isFilteredByField2
	    });
	    Object.defineProperty(this, _isFilteredByFieldValue, {
	      value: _isFilteredByFieldValue2
	    });
	    Object.defineProperty(this, _toggleByField, {
	      value: _toggleByField2
	    });
	    Object.defineProperty(this, _counterClickHandler, {
	      value: _counterClickHandler2
	    });
	    Object.defineProperty(this, _applyFilterHandler, {
	      value: _applyFilterHandler2
	    });
	    Object.defineProperty(this, _inputFilterHandler, {
	      value: _inputFilterHandler2
	    });
	    Object.defineProperty(this, _subscribeToEvents, {
	      value: _subscribeToEvents2
	    });
	    Object.defineProperty(this, _unSubscribeToEvents, {
	      value: _unSubscribeToEvents2
	    });
	    Object.defineProperty(this, _updateFields, {
	      value: _updateFields2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _props, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _filter, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _MIN_QUERY_LENGTH, {
	      writable: true,
	      value: 3
	    });
	    Object.defineProperty(this, _fields, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _props)[_props] = props;
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]();
	  }
	  hasFilteredFields() {
	    const filteredFields = babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFilterFieldsValues();
	    const fields = Object.values(filteredFields);
	    for (const field of fields) {
	      if (this.isArrayFieldFiller(field) || this.isStringFieldFilled(field)) {
	        return true;
	      }
	    }
	    return false;
	  }
	  isFilterActive() {
	    const isPresetApplied = !['default_filter', 'tmp_filter'].includes(babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getPreset().getCurrentPresetId());
	    const isSearchFilled = !this.isSearchEmpty();
	    const hasFilledFields = this.hasFilteredFields();
	    return isPresetApplied || isSearchFilled || hasFilledFields;
	  }
	  isArrayFieldFiller(field) {
	    return main_core.Type.isArrayFilled(field);
	  }
	  isStringFieldFilled(field) {
	    return field !== 'NONE' && main_core.Type.isStringFilled(field);
	  }
	  isSearchEmpty() {
	    const query = babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getSearch().getSearchString();
	    return !query || query.length < babelHelpers.classPrivateFieldLooseBase(this, _MIN_QUERY_LENGTH)[_MIN_QUERY_LENGTH];
	  }
	}
	function _init2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter] = BX.Main.filterManager.getById(babelHelpers.classPrivateFieldLooseBase(this, _props)[_props].filterId);
	  babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields]();
	  babelHelpers.classPrivateFieldLooseBase(this, _subscribeToEvents)[_subscribeToEvents]();
	}
	function _updateFields2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields] = babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFilterFieldsValues();
	}
	function _unSubscribeToEvents2() {
	  main_core_events.EventEmitter.unsubscribe('BX.Filter.Search:input', babelHelpers.classPrivateFieldLooseBase(this, _inputFilterHandler)[_inputFilterHandler].bind(this));
	  main_core_events.EventEmitter.unsubscribe('BX.Main.Filter:apply', babelHelpers.classPrivateFieldLooseBase(this, _applyFilterHandler)[_applyFilterHandler].bind(this));
	  main_core_events.EventEmitter.unsubscribe('Tasks.Toolbar:onItem', babelHelpers.classPrivateFieldLooseBase(this, _counterClickHandler)[_counterClickHandler].bind(this));
	}
	function _subscribeToEvents2() {
	  main_core_events.EventEmitter.subscribe('BX.Filter.Search:input', babelHelpers.classPrivateFieldLooseBase(this, _inputFilterHandler)[_inputFilterHandler].bind(this));
	  main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', babelHelpers.classPrivateFieldLooseBase(this, _applyFilterHandler)[_applyFilterHandler].bind(this));
	  main_core_events.EventEmitter.subscribe('Tasks.Toolbar:onItem', babelHelpers.classPrivateFieldLooseBase(this, _counterClickHandler)[_counterClickHandler].bind(this));
	}
	function _inputFilterHandler2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _setActive)[_setActive](this.isFilterActive());
	}
	function _applyFilterHandler2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields]();
	}
	function _counterClickHandler2(baseEvent) {
	  const data = baseEvent.getData();
	  if (data.counter && data.counter.filter) {
	    babelHelpers.classPrivateFieldLooseBase(this, _toggleByField)[_toggleByField]({
	      [data.counter.filterField]: data.counter.filterValue
	    });
	  }
	}
	function _toggleByField2(field) {
	  const name = Object.keys(field)[0];
	  const value = field[name];
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isFilteredByFieldValue)[_isFilteredByFieldValue](name, value)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getApi().extendFilter({
	      [name]: value
	    });
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFilterFields().forEach(field => {
	    if (field.getAttribute('data-name') === name) {
	      babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFields().deleteField(field);
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getSearch().apply();
	}
	function _isFilteredByFieldValue2(field, value) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _isFilteredByField)[_isFilteredByField](field) && babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields][field] === value;
	}
	function _isFilteredByField2(field) {
	  if (!Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields]).includes(field)) {
	    return false;
	  }
	  if (main_core.Type.isArray(babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields][field])) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields][field].length > 0;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields][field] !== '';
	}
	function _setActive2(isActive) {
	  const wrap = babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].popupBindElement;
	  if (isActive) {
	    main_core.Dom.removeClass(wrap, 'main-ui-filter-default-applied');
	    main_core.Dom.addClass(wrap, 'main-ui-filter-search--showed');
	  } else {
	    main_core.Dom.addClass(wrap, 'main-ui-filter-default-applied');
	    main_core.Dom.removeClass(wrap, 'main-ui-filter-search--showed');
	  }
	}

	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _flowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flowId");
	var _dashboards = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dashboards");
	var _target = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("target");
	var _getMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItems");
	class BIAnalytics {
	  constructor(data) {
	    Object.defineProperty(this, _getMenuItems, {
	      value: _getMenuItems2
	    });
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _flowId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dashboards, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _target, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _dashboards)[_dashboards] = Object.values(data.dashboards);
	    babelHelpers.classPrivateFieldLooseBase(this, _target)[_target] = data.target;
	    babelHelpers.classPrivateFieldLooseBase(this, _flowId)[_flowId] = Number(data.flowId);
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = `tasks-flow-bi-analytics-menu_${babelHelpers.classPrivateFieldLooseBase(this, _flowId)[_flowId]}`;
	  }
	  static create(data) {
	    return new BIAnalytics(data);
	  }
	  openMenu() {
	    const popupMenu = main_popup.MenuManager.create({
	      id: babelHelpers.classPrivateFieldLooseBase(this, _id)[_id],
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _target)[_target],
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems)[_getMenuItems](),
	      cacheable: false
	    });
	    popupMenu.show();
	  }
	  openFirstDashboard() {
	    const dashboard = babelHelpers.classPrivateFieldLooseBase(this, _dashboards)[_dashboards][0];
	    if (dashboard) {
	      main_sidepanel.SidePanel.Instance.open(dashboard.url);
	    }
	  }
	}
	function _getMenuItems2() {
	  const menuItems = [];
	  babelHelpers.classPrivateFieldLooseBase(this, _dashboards)[_dashboards].forEach(dashboard => {
	    menuItems.push({
	      tabId: dashboard.id,
	      text: dashboard.title,
	      onclick: () => {
	        main_sidepanel.SidePanel.Instance.open(dashboard.url);
	      }
	    });
	  });
	  return menuItems;
	}

	let _$1 = t => t,
	  _t$1;
	class NotEnoughTasksPopup {
	  static show(bindElement) {
	    const {
	      root: popupContent,
	      exampleLink
	    } = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="tasks-flow__not-enough-tasks-popup">
				<div class="tasks-flow__not-enough-tasks-popup-title">
					<span class="tasks-flow__not-enough-tasks-popup-icon ui-icon-set --copilot-ai"/>
					<span class="tasks-flow__not-enough-tasks-popup-title-text">
						${0}
					</span>
				</div>
				<div class="tasks-flow__not-enough-tasks-popup-description">
					${0}
				</div>
				<div class="tasks-flow__not-enough-tasks-popup-example">
					<span class="tasks-flow__not-enough-tasks-popup-example-text" ref="exampleLink">
						${0}
					</span>
				</div>
			</div>
		`), main_core.Loc.getMessage('TASKS_FLOW_LIST_COPILOT_NOT_ENOUGH_TASKS_POPUP_TITLE'), main_core.Loc.getMessage('TASKS_FLOW_LIST_COPILOT_NOT_ENOUGH_TASKS_POPUP_DESCRIPTION'), main_core.Loc.getMessage('TASKS_FLOW_LIST_COPILOT_NOT_ENOUGH_TASKS_POPUP_SHOW_EXAMPLE'));
	    const popup = new main_popup.Popup({
	      bindElement,
	      content: popupContent,
	      cacheable: false,
	      autoHide: true,
	      minWidth: 270,
	      width: 270,
	      padding: 12,
	      angle: {
	        position: 'top',
	        offset: 30
	      }
	    });
	    main_core.Event.bind(exampleLink, 'click', () => {
	      tasks_flow_copilotAdvice.CopilotAdvice.showExample();
	      popup == null ? void 0 : popup.close();
	    });
	    popup.show();
	  }
	}

	exports.Grid = Grid;
	exports.Filter = Filter;
	exports.BIAnalytics = BIAnalytics;
	exports.NotEnoughTasksPopup = NotEnoughTasksPopup;

}((this.BX.Tasks.Flow = this.BX.Tasks.Flow || {}),BX.Tasks.Flow,BX.UI.Dialogs,BX.UI,BX.Pull,BX.Tasks.Flow,BX.Tasks.Flow,BX.Tasks,BX.UI.Manual,BX,BX.Event,BX,BX,BX.Main,BX.Tasks.Flow));
//# sourceMappingURL=script.js.map
