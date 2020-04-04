<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<h3>Einbindung über die Netzwerklaufwerk-Komponente (web-folders)</h3>
<p>Bevor Sie die Dokumentenbibliothek einbinden, vergewissern Sie sich, dass <a href="<?=$arResult["URL"]["HELP"]?>#oswindowsreg">Änderungen im Registrierungs-Editor vorgenommen wurden</a> und <a href="<?=$arResult["URL"]["HELP"]?>#oswindowswebclient">der Service WebClient gestartet</a> ist.</p>
<p>Um die Dokumentenbibliothek auf diese Weise einzubinden, ist die Netzwerklaufwerk-Komponente erforderlich. Wünschenswert ist die Installation der neuesten Software für die Netzwerklaufwerk auf dem Kunden-PC <a href="http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64" target="_blank">auf die Website von Mikrosoft wechseln</a> ). </p>
<ul>
<li>Starten Sie den Datei-Manager (Explorer);</li>
<li>Wählen Sie im Menü den Punkt <b>Service &gt; Netzlaufwerk verbinden </b>aus;</li>
<li>Mit Hilfe des Links <b>Verbindung mit einer Website herstellen, auf der Sie Dokumente und Bilder speichern können</b> starten Sie den Assistenten zum <b>Hinzufügen eines Netzwerkes</b>:</p> 
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/de/network_add_1.png\',630,458,\'Netzlaufwerk verbinden\');'?>">
<img width="250" height="182" border="0" src="<?=$templateFolder.'/images/de/network_add_1_sm.png'?>" style="cursor: pointer;" alt="Bild vergrößern" /></a></b>.</li>
<li>Drücken Sie auf die Schaltfläche <b>Weiter</b>, es öffnet sich das zweite Fenster des <b>Assistenten</b>;</li>
<li>Aktivieren Sie in diesem Fenster die Position <b>Eine benutzerdefinierte Netzwerkadresse auswählen</b>, drücken Sie auf die Schaltfläche <b>Weiter</b>. Es öffnet sich der nächste Schritt des <b>Assistenten</b>:
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/de/network_add_4.png\',612,498,\'Eine Netzwekadresse hinzufügen\');'?>">
<img width="250" height="204" border="0" src="<?=$templateFolder.'/images/de/network_add_4_sm.png'?>" style="cursor: pointer;" alt="Bild vergrößern" /></a></li>
<li>Im Feld <b>Internet- oder Netzwerkadresse</b> geben Sie URL des Ordners, mit dem Verbindung hergestellt werden soll, wie folgt ein: http://&lt;Ihr_Server&gt;/docs/shared/</i>;</li>
<li>Drücken Sie auf die Schaltfläche <b>Next</b>. Wenn sich das Fenster zur Autorisierung öffnet, geben Sie hier die Autorisierungsdaten für den Server ein.</li>
</ul>

<p>Um dann den Ordner öffnen zu können, führen Sie den Befehl aus: <b>Start > Netzwerk > Ordnername</b>.</p>
