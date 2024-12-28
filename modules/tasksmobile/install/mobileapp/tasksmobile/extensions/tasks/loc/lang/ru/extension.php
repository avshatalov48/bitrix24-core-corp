<?php
// region Action denied

$MESS['M_TASKS_DENIED_SELECT_USER_AS_RESPONSIBLE'] = 'Недостаточно прав на постановку задачи этому сотруднику';
$MESS['M_TASKS_DENIED_SELECT_COLLABER_WITHOUT_COLLAB'] = 'Добавьте задачу в коллабу или выберите другого сотрудника';
$MESS['M_TASKS_DENIED_DELEGATE_USER_AS_RESPONSIBLE'] = 'Недостаточно прав на делегирование задачи этому сотруднику';
$MESS['M_TASKS_DENIED_SELECT_PROJECT'] = 'Недостаточно прав на создание задач в этом проекте';
$MESS['M_TASKS_DENIED_SELECT_TASK'] = 'Вы не можете выбрать эту задачу';

// endregion
// region Action denied by access codes

$MESS['M_TASKS_DENIED_UPDATE'] = 'Недостаточно прав на редактирование этой задачи';
$MESS['M_TASKS_DENIED_UPDATECREATOR'] = 'Недостаточно прав на смену постановщика в этой задаче';
$MESS['M_TASKS_DENIED_UPDATERESPONSIBLE'] = 'Недостаточно прав на смену исполнителя в этой задаче';
$MESS['M_TASKS_DENIED_UPDATEACCOMPLICES'] = 'Недостаточно прав на смену соисполниетелей в этой задаче';
$MESS['M_TASKS_DENIED_UPDATEDEADLINE'] = 'Недостаточно прав на изменение крайнего срока в этой задаче';
$MESS['M_TASKS_DENIED_UPDATEFLOW'] = 'Сменить поток нельзя';
$MESS['M_TASKS_DENIED_UPDATEPROJECT'] = 'Сменить проект нельзя';

// endregion

// region flows
$MESS['M_TASKS_FLOW'] = 'Поток';
$MESS['M_TASKS_FLOWS'] = 'Потоки';
$MESS['M_TASKS_DEADLINE_DISABLED_BY_FLOW'] = 'Автоматически';
$MESS['M_TASKS_RESPONSIBLE_DISABLED_BY_FLOW'] = 'Назначается автоматически';
$MESS['M_TASKS_FIELD_DISABLED_BY_FLOW_EXPLANATION'] = 'Нельзя поменять в потоке';
$MESS['M_TASKS_FIELD_DISABLED_BY_FLOW_EXPLANATION_DEADLINE'] = 'Крайний срок настраивается в потоке';
$MESS['M_TASKS_FIELD_DISABLED_BY_FLOW_EXPLANATION_RESPONSIBLE'] = 'Исполнитель назначается в потоке';
$MESS['M_TASKS_FIELD_DISABLED_BY_FLOW_EXPLANATION_PROJECT'] = 'Проект настраивается в потоке';
$MESS['M_TASKS_CHANGE_FLOW_CONFIRM_TITLE'] = 'Выбрать поток?';
$MESS['M_TASKS_CHANGE_FLOW_CONFIRM_BODY'] = 'Введенные данные не сохранятся';
$MESS['M_TASKS_CHANGE_FLOW_CONFIRM_OK'] = 'Выбрать';

// endregion

// region fields
$MESS['M_TASKS_FIELDS_TAGS'] = 'Теги';
$MESS['M_TASKS_FIELDS_TAGS_MULTI'] = 'Теги: #COUNT#';
$MESS['M_TASKS_FIELDS_CRM'] = 'Элементы CRM';
$MESS['M_TASKS_FIELDS_CRM_MULTI'] = 'Элементы CRM: #COUNT#';
$MESS['M_TASKS_FIELDS_FILES'] = 'Файлы';
$MESS['M_TASKS_FIELDS_FILES_MULTI'] = 'Файлы: #COUNT#';
$MESS['M_TASKS_FIELDS_TIME_TRACKING'] = 'Учёт времени';
$MESS['M_TASKS_FIELDS_USER_FIELDS'] = 'Пользовательские поля';
// endregion

//region common
$MESS['M_TASKS_BACK'] = 'Назад';
$MESS['M_TASKS_EXTRA_SETTINGS'] = 'Дополнительные настройки';
$MESS['M_TASKS_SAVE'] = 'Сохранить';
$MESS['M_TASKS_EDIT'] = 'Редактировать';
// endregion

//region task statuses
$MESS['M_TASKS_STATUS_DEFERRED'] = 'Отложена';
$MESS['M_TASKS_STATUS_SUPPOSEDLY_COMPLETED'] = 'Ждёт контроля';
$MESS['M_TASKS_STATUS_COMPLETED'] = 'Завершена';
$MESS['M_TASKS_STATUS_EXPIRED'] = 'Просрочена';
$MESS['M_TASKS_STATUS_NEED_RESULT'] = 'Требуется результат';
// endregion

//region dates conflict
$MESS['M_TASKS_PLANNING_FINISH_DATE_OUT_OF_PROJECT_RANGE'] = 'Дата завершения выходит за сроки проекта';
$MESS['M_TASKS_PLANNING_START_DATE_IS_OUT_OF_PROJECT_RANGE'] = 'Дата начала выходит за сроки проекта';
$MESS['M_TASKS_PLANNING_START_AND_END_DATE_IS_OUT_OF_PROJECT_RANGE'] = 'Даты начала и завершения выходят за сроки проекта';
$MESS['M_TASKS_DEADLINE_IS_OUT_OF_PROJECT_RANGE'] = "Крайний срок задачи выходит за сроки проекта";
// endregion
