<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<h3>Netzlaufwerk-Verbindung</h3>
<ul>
<p>Um die Dokumentenbibliothek als Netzlaufwerk über den Dateimanager (Windows Explorer) einzubinden:</p> 
<li>Starten Sie den Datei-Manager (Explorer);
<br><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/de/network_add_4.png\',612,498,\'Eine Netzweradresse hinzufügen\');'?>">
<img width="250" height="185" border="0" src="<?=$templateFolder.'/images/de/network_add_1_sm.png'?>" style="cursor: pointer;" alt="Bild vergrößern" /></a></li>
<li>Wählen Sie im Menü den Punkt <b>Service >Netzlaufwerk verbinden</b> aus. Es öffnet sich das Dialogfenster zur Verbindung des Netzlaufwerks:</li>
<li>Im Feld <b>Laufwerk</b> geben Sie einen Buchstaben für den  Ordner an, mit dem Verbindung hergestellt werden soll;</li>
<li>Im Feld <b>Ordner</b> geben Sie Pfad zur Bibliothek ein: http://&lt;Ihr_Server&gt;/docs/shared/. Wenn der Ordner bei jedem Systemstart zur Vorschau angeschaltet werden soll, markieren Sie <b>Beim Systemstart wiederherstellen</b>;</li>
<li>Drücken Sie auf Fertigstellen. Wenn sich das Dialogfenster des Operationssystems zur Autorisierung öffnet, geben Sie die Autorisierungsdaten für den Server ein. </li>
</ul>
</p>
<p>Später kann der Ordner entweder mit dem Windows Explorer, wo der Ordner als einzelnes Laufwerk dargestellt wird, oder mit einem beliebigen Dateimanager geöffnet werden.</p>
