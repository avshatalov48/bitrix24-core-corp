{"version":3,"sources":["logic.js"],"names":["BX","namespace","Tasks","Component","TaskViewSidebar","parameters","this","layout","stagesWrap","stages","taskId","messages","workingTime","start","hours","minutes","end","can","allowTimeTracking","user","isAmAuditor","iAmAuditor","auditorCtrl","pathToTasks","stageId","parseInt","query","Util","Query","taskLimitExceeded","calendarSettings","initDeadline","initReminder","initMark","initTime","initTags","initAuditorThing","initStages","addCustomEvent","window","delegate","onTaskEvent","onChangeProjectLink","prototype","EDIT","Dispatcher","find","then","ctrl","bindControl","onToggleImAuditor","bind","canChange","SORT","stagesShowed","length","cleanNode","i","c","appendChild","TEXT_LAYOUT","create","attrs","data-stageId","ID","title","TITLE","props","className","text","events","click","setStageHadnler","style","cursor","show","setStage","hide","groupId","data","entityId","entityType","run","result","isSuccess","numeric","execute","getStageData","id","proxy_context","saveStage","fireGlobalTaskEvent","STAGE_ID","STAY_AT_PAGE","stage","color","clearAll","calculateTextColor","backgroundColor","borderBottomColor","COLOR","baseColor","defaultColors","r","g","b","util","in_array","toLowerCase","split","join","y","confirm","message","syncAuditor","UI","InfoHelper","deleteItem","addItem","setHeaderButtonLabelText","getData","READ","document","location","reload","type","task","isNotEmptyString","REAL_STATUS","STATUS_CHANGED_DATE","setStatus","status","time","statusName","statusDate","innerHTML","htmlspecialchars","deadline","deadlineClear","proxy","onDeadlineClick","clearDeadline","event","now","Date","today","UTC","getFullYear","getMonth","getDate","calendar","node","field","form","bTime","value","bHideTimebar","bCompatibility","bCategoryTimeVisibilityOption","bTimeVisibility","deadlineTimeVisibility","callback_after","setDeadline","ValueToString","date","format","convertBitrixFormat","replace","display","updateDeadline","emptyDeadline","add","DEADLINE","onCustomEvent","addReminder","reminderAdd","mark","onMarkClick","TaskGradePopup","listValue","onPopupChange","onMarkChange","popup","listItem","name","MARK","bindEvent","onTaskTimerTick","formatTimeAmount","saveTags","tags","tagsString","TAGS","call"],"mappings":"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","file":"logic.map.js"}