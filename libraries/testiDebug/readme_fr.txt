[PHP5] ODEBUGGER PACKAGE

Ce package est un outil de debugging.
Il surcharge la gestion des erreurs et des exceptions de PHP, pour utiliser une gestion personnalisée.
Les erreurs sont générées sans s'occuper de la constante error_reporting de php.ini.
Les erreurs sont traduites, affichées, avec le code incréminé, la trduction du message d'erreur, une suggestion de correction, la date.
Les exceptions ne sont pas traduites.
Les erreurs peuvent être loggées dans un fichier XML, et/ou afficher en temps réel.
Il est évident que puisque c'est un outil de debugging, il n'est pas destiné à être utilisé
en production, mais uniquement en développement.
Il peut être utilisé autant pour les débutants, qui y trouveront des cuggestions de correction, et des messages intelligibles, que par les "pros", qui pourront le personnaliser
à loisir pour l'adapter à leurs besoins.
Son utilisation est simple. Divers exemples se trouvent dans les différents fichier 'indexN.php'.

A prévoir bientôt : ajout d'une gestion des exceptions. Ainsi que de pas mal de nouvelles options.

Version 20060612

- localisé
- plusieurs erreurs traduites
- types d'erreur traduits
- 2 types d'affichage : un pour le temps réel, un pour le différé, avec statistiques sommaires.
- très facilement personnalisable :
dans le répertoire templates/ se trouvent les fichiers HTML servant de template pour l'affichage.
Ils peuvent être modifiés à loisir, et on peut en créer facilement de nouveau, tant que l'on respecte la règle 
de nommage (voir default.dat et default_log.dat). 
default.dat sert à l'affichage temps réel.
default_log.dat à l'affichage en différé (par exemple, pour afficher un fichier log précédent).
Sont associés dans fichiers template CSS dans le répertoire 'css/'. 

La traduction des types d'erreurs, et des messages d'erreur sont dans le répertoire 'language/xml/'.
'language' faisant référence au langage. Pour l'instant uniquement FR pour Français.
Le fichier types.xml contient les types et leur traduction.
Le fichier errors.xml contient divers messages d'erreurs, avec le message original, sa traduction, et une suggestion de correction.
ON peut très facilement compléter ce fichier avec ses propres messages, traductions et suggestions, en respectant le flux, bien évidemment.

On peut aussi aisément localiser ces fichiers. Par exemple, pour le localiser en anglais, il suffit de créer un 
répertoire 'EN/' (par exemple...), d'y copier ces 2 fichiers, et de les traduire.
On appellera ensuite le debugger ainsi :
$oDebugger = new odebugger ('EN');



HOW TO :
* How to call the debugger : 

$oDebugger = new odebugger ('EN');

Where 'EN' is the chosen language. 
Check the 'xml/' folder to see which languages are currently supported.
English being the default language, you can call the debugger this way, too, for English :
$oDebugger = new odebugger;

* How to add a new language :
In the xml/ folder, you can find surbolders. Each one contains the XML files for a different language.
'EN/' => English
'FR/' => French.
If you want to add, for example, German, just create a 'GE/' subfolder, and copy both the xml files :
errors.xml and types.xml into the new folder.
Then, edit them.
All you have to do is change the <translation> and <suggestion> nodes in the XML files.
And that's it!
You can then call the debugger in German :
$oDebugger = new odebugger ('GE');

How to add a new error :
Well, just edit the chose language errors.xml file (xml/LANGUAGE/errors.xml), and copy/paste
a node within the <errors></errors> root node. It must follow the others:
<error>
	<label>Undefined index:</label>
	<translation>An index has been used without being defined</translation>
	<suggestion>Check the max and min limits of your array</suggestion>
</error>

Just change the values, save the file, and that's it.
You can, of course, also change an existing node, if you do not like my translations/suggestions ;-)

* How to change the display:
Well, there is currently 2 types of display : realtime, and stats.
IN the folder 'templates/', you can find some files. For example :
default.dat
default_log.dat

default.dat is the default realtime display HTML template, while default_log.dat is the default stat HTML template.
You can create your own, of course. The information supplied by odebugger replace the {INFO_NAME} types of string.
Just make sure you put all the information you want in your templates :-)
For the stats template, there is a slight difference : it has 3 parts.
The first one starts from the...start, and ends on : <--! LINES HERE -->.
This part is fixed. These are the headers!

Then, between <--! LINES HERE -->. and <!-- STATS -->, the logs will be displayed. And of course, the lines here
will be repeated as many times as you have lines in your log.
Then, after <!-- STATS -->, you have your stats :-)

You can set new templates with :
odebugger::HTML 
and
odebugger::HTMLLOG
properties. See the part of this documentation about the OPTIONS.

In the 'css/' folder, you can also find files. These are the CSS files used to modify the HTML templates.
default.dat being the CSS for the default.dat HTML template.
default_log.dat being the CSS for the default_log.dat HTML template.
You can also, of course, set them :
odebugger::CSS 
and
odebugger::CSSLOG



OPTIONS que l'on peut modifier :
Syntaxe :
$oDebugger -> {OPTION} = {VALEUR};
Par exemple :
$oDebugger -> LINES = 2;

odebugger::LINES => un entier représentant le nombre de lignes à afficher avant et après la ligne où a été détectée l'erreur.

odebugger::HTML => une chaîne contenant le nom du fichier template HTML présent dans le répertoire 'templates', sans son extension. 
Sert à afficher le log en mode realtime.

odebugger::CSS => une chaîne contenant le nom du fichier template CSS présent dans le répertoire 'css', sans son extension. 
Sert à afficher le log en mode realtime, applique la CSS au template HTML.

odebugger::HTMLLOG => une chaîne contenant le nom du fichier template HTML présent dans le répertoire 'templates', sans son extension. 
Sert à afficher le log en mode statistiques (chargement d'un fichier log, ou affichage global de tout le log de la page courante).

odebugger::CSSLOG => une chaîne contenant le nom du fichier template CSS présent dans le répertoire 'css', sans son extension. 
Sert à afficher le log en mode statistiques (chargement d'un fichier log, ou affichage global de tout le log de la page courante), applique la CSS au template HTML.

odebugger::REALTIME => booléen true ou false. Active ou désactive le mode realtime (interception et affichage des erreurs au fur et à mesure de l'exécution du script).

odebugger::LOGFILE => booléen true ou false. Active ou désactive le mode 'log to file' (sauvegarde ou non du log complet à la fin de l'exécution du script, dans le répertoire 'logs').

odebugger::ERROR => booléen true ou false. Active ou désactive la gestion des erreurs personnalisée.

odebugger::EXCEPTION => booléen true ou false. Active ou désactive la gestion des exceptions personnalisée.



METHODS (the ones you can call only) :

odebugger::checkCode (string sString)
Used to check if there are any errors in a string (usually used by retrieving in a string the content of a file. See index3.php to see that in action).

odebugger::loadXML (string sFile)
Used to get an existing log from a file (logs are stored in the 'logs/' folder).
This method erase any previous log (realtime or not).

odebugger::showAll ()
Used to display the whole current log, just as if it was in realtime mode. You can display a loaded log, or the current realtime log.

odebugger::showLog ()
Used to display the whole current log in stats mode. You can display a loaded log, or the current realtime log.

odebugger::saveToFile (optional string sFile)
Used to save the current log to a file. This methods is used automatically when the script ends, if odebugger::LOGFILE is set to true.
But it can be called manually, with a filename.