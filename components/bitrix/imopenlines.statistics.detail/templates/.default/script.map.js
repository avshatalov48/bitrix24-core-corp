{"version":3,"sources":["script.js"],"names":["BX","ready","PULL","extendWatch","addCustomEvent","command","params","voteValue","placeholderVote","sessionId","cleanNode","appendChild","MessengerCommon","linesVoteHeadNodes","commentValue","placeholderComment","linesCommentHeadNodes","namespace","OpenLines","ExportManager","this","_id","_settings","_processDialog","_siteId","_sToken","_cToken","_token","prototype","initialize","id","settings","type","isNotEmptyString","util","getRandomString","getSetting","getId","name","defaultval","hasOwnProperty","callAction","action","setAction","start","startExport","exportType","Date","now","SITE_ID","PROCESS_TOKEN","EXPORT_TYPE","COMPONENT_NAME","signedParameters","exportTypeMsgSuffix","charAt","toUpperCase","slice","OpenlinesLongRunningProcessDialog","create","componentName","title","getMessage","summary","isSummaryHtml","requestHandler","result","show","destroy","message","messages","items","self","currentId","delete","currentInstance","Actions","actionController","gridId","transferDialog","transferItems","transferInputId","transferInputName","init","data","transfer","inputId","inputName","initTransferDialogSelector","UI","EntitySelector","Dialog","targetNode","context","getGridInstance","multiple","entities","dynamicLoad","dynamicSearch","options","intranetUsersOnly","inviteEmployeeLink","selectMode","tabs","events","Item:onSelect","proxy","textTitle","item","getSelectedItemTransfer","text","setValueTransferInput","Item:onDeselect","addEventListener","lastChild","search","value","destroyTransferDialogSelector","removeEventListener","input","findChild","tag","getSelectedItems","forEach","entity","gridInfo","Main","gridManager","getById","isPlainObject","refreshGrid","window","requestAnimationFrame","grid","getInstanceById","reload","bind","getPanelControl","controlId","getCheckBoxValue","control","getControl","checked","applyAction","actionName","errors","forAll","selectedIds","getRows","getSelectedIds","length","fields","idsSession","runCloseSpam","runClose","transferId","entityId","runTransfer","confirmGroupAction","runAction","ajax","then","response","runStepProcess","progress","StepProcessing","Process","controller","DialogTitle","DialogSummary","showButtons","stop","close","dialogMaxWidth","addQueueAction","handlers","StepCompleted","state","ProcessResultStatus","completed","getParam","idsChat","sessions","push","chatId","TOTAL_ITEMS","totalItems","parseInt","setParam","setHandler","ProcessCallback","StateChanged","closeDialog","RequestStop","actionData","setTimeout","delegate","PROCESSED_ITEMS","processedItems","showDialog","closeSpam","getChatSessionForAll","GridActions","groupSelector","registeredTimerNodes","defaultPresetId","initPopupBaloon","mode","searchField","groupIdField","bindEvent","htmlspecialcharsback","nameFormatted","open","getGrid","console","log"],"mappings":"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","file":"script.map.js"}