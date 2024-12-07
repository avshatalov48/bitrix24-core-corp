<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$containerID = 'tasks_group_selector';
if (isset($arResult['GROUPS'][$arParams['GROUP_ID']]))
{
	$currentGroup = $arResult['GROUPS'][$arParams['GROUP_ID']];
	unset($arResult['GROUPS'][$arParams['GROUP_ID']]);
}
else
{
	$currentGroup = array(
		'id' => 'wo',
		'text' => \GetMessage('TASKS_BTN_GROUP_WO')
	);
}

\Bitrix\Main\UI\Extension::load(['ui.entity-selector', 'ui.buttons', 'ui.forms']);
?>

<div class="tasks-project-btn-container" id="<?= htmlspecialcharsbx($containerID) ?>">
	<div class="tasks-project-btn">
		<div class="tasks-project-btn-image">
			<? if (!empty($currentGroup['image'])): ?>
				<img src="<?= $currentGroup['image'] ?>" width="27" height="27"
					 alt="<?= htmlspecialcharsbx($currentGroup['text']); ?>"/>
			<? else: ?>
				<img
					src="data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%2282%22%20height%3D%2282%22%20viewBox%3D%220%200%2082%2082%22%3E%3Cpath%20fill%3D%22%23FFF%22%20fill-rule%3D%22evenodd%22%20d%3D%22M46.03%2033.708s-.475%201.492-.55%201.692c-.172.256-.317.53-.433.816.1%200%201.107.47%201.107.47l3.917%201.227-.056%201.86c-.74.296-1.394.778-1.894%201.4-.19.413-.42.806-.69%201.17%203.568%201.45%205.655%203.573%205.74%205.95.058.422%202.223%208.205%202.347%209.958H72c.014-.072-.5-10.14-.5-10.216%200%200-.946-2.425-2.446-2.672-1.487-.188-2.924-.66-4.233-1.388-.864-.504-1.775-.923-2.72-1.252-.483-.425-.858-.957-1.095-1.555-.5-.623-1.152-1.105-1.894-1.4l-.055-1.86%203.917-1.227s1.01-.47%201.107-.47c-.158-.33-.338-.646-.54-.948-.075-.2-.444-1.554-.444-1.554.572.733%201.242%201.384%201.992%201.933-.667-1.246-1.238-2.542-1.708-3.876-.314-1.233-.532-2.488-.653-3.754-.27-2.353-.69-4.687-1.255-6.987-.406-1.148-1.124-2.16-2.072-2.923-1.403-.974-3.04-1.555-4.742-1.685h-.2c-1.7.13-3.333.712-4.733%201.685-.947.765-1.664%201.777-2.07%202.925-.568%202.3-.987%204.634-1.255%206.987-.103%201.295-.31%202.58-.622%203.84-.47%201.312-1.052%202.58-1.737%203.792.75-.55%201.42-1.202%201.99-1.936zM54.606%2064c0-2.976-3.336-15.56-3.336-15.56%200-1.84-2.4-3.942-7.134-5.166-1.603-.448-3.127-1.142-4.517-2.057-.3-.174-.26-1.78-.26-1.78l-1.524-.237c0-.13-.13-2.057-.13-2.057%201.824-.613%201.636-4.23%201.636-4.23%201.158.645%201.912-2.213%201.912-2.213%201.37-3.976-.682-3.736-.682-3.736.36-2.428.36-4.895%200-7.323-.912-8.053-14.646-5.867-13.018-3.24-4.014-.744-3.1%208.4-3.1%208.4l.87%202.364c-1.71%201.108-.52%202.45-.463%204%20.085%202.28%201.477%201.808%201.477%201.808.086%203.764%201.94%204.26%201.94%204.26.348%202.363.13%201.96.13%201.96l-1.65.2c.022.538-.02%201.075-.13%201.6-1.945.867-2.36%201.375-4.287%202.22-3.726%201.634-7.777%203.76-8.5%206.62C13.117%2052.695%2010.99%2064%2010.99%2064H54.606z%22/%3E%3C/svg%3E"
					width="27" height="27" alt="<?= htmlspecialcharsbx($currentGroup['text']); ?>"/>
			<? endif; ?>
		</div>
		<div class="tasks-project-btn-text"><?= htmlspecialcharsbx($currentGroup['text']); ?></div>
	</div>
</div>

<script>
	(function() {
		const dialog = new BX.UI.EntitySelector.Dialog({
			targetNode: document.getElementById('<?= $containerID ?>'),
			enableSearch: true,
			context: 'TASKS',
			multiple: false,
			footer: [
				BX.Dom.create('span', {
					props: {
						className: 'ui-selector-footer-link ui-selector-footer-link-add'
					},
					text: '<?= \GetMessageJS('TASKS_LINK_CREATE_PROJECT'); ?>',
					events: {
						click: function () {
							BX.SidePanel.Instance.open('/company/personal/user/<?= $arResult['USER_ID'] ?>/groups/create/?firstRow=project')
						}
					}
				})
			],
			entities: [
				{
					id: 'project',
				},
			],
			events: {
				'Item:onSelect': function(event) {
					var item = event.getData().item;
					BX.Tasks.ProjectSelector.reloadProject(item.id);
				},
			}
		});

		document.getElementById('<?= $containerID; ?>').addEventListener('click', function() {
			dialog.show();
		});

	})();
</script>