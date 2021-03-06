<?php
/** West-Vlams (West-Vlams)
 *
 * See MessagesQqq.php for message documentation incl. usage of parameters
 * To improve a translation please visit http://translatewiki.net
 *
 * @ingroup Language
 * @file
 *
 * @author DasRakel
 * @author Tbc
 * @author לערי ריינהארט
 */

$fallback = 'nl';

$namespaceNames = array(
	NS_MEDIA            => 'Media',
	NS_SPECIAL          => 'Specioal',
	NS_TALK             => 'Discuusje',
	NS_USER             => 'Gebruker',
	NS_USER_TALK        => 'Discuusje_gebruker',
	NS_PROJECT_TALK     => 'Discuusje_$1',
	NS_FILE             => 'Ofbeeldienge',
	NS_FILE_TALK        => 'Discuusje_ofbeeldienge',
	NS_MEDIAWIKI        => 'MediaWiki',
	NS_MEDIAWIKI_TALK   => 'Discuusje_MediaWiki',
	NS_TEMPLATE         => 'Patrôon',
	NS_TEMPLATE_TALK    => 'Discuusje_patrôon',
	NS_HELP             => 'Ulpe',
	NS_HELP_TALK        => 'Discuusje_ulpe',
	NS_CATEGORY         => 'Categorie',
	NS_CATEGORY_TALK    => 'Discuusje_categorie',
);

$messages = array(
# User preference toggles
'tog-underline' => 'Links ounderstreepn:',
'tog-hideminor' => 'Klêne veranderiengn verdukn van juste veranderd',
'tog-enotifrevealaddr' => 'Tôog min e-mailadres in e-mails',
'tog-shownumberswatching' => 'Tôog et aantal gebrukers dan et blad volgn',

'underline-always' => 'Olsan',
'underline-never' => 'Noois',
'underline-default' => 'Browser standoard',

# Dates
'sunday' => 'zundag',
'monday' => 'moandag',
'tuesday' => 'disndag',
'wednesday' => 'woesdag',
'thursday' => 'dunderdag',
'friday' => 'vrydag',
'saturday' => 'zoaterdag',
'sun' => 'zu',
'mon' => 'moa',
'tue' => 'din',
'wed' => 'woe',
'thu' => 'dun',
'fri' => 'vry',
'sat' => 'zat',
'january' => 'januoari',
'february' => 'februoari',
'march' => 'moarte',
'april' => 'april',
'may_long' => 'mei',
'june' => 'juni',
'july' => 'juli',
'august' => 'ogustus',
'september' => 'september',
'october' => 'oktober',
'november' => 'november',
'december' => 'december',
'january-gen' => 'januari',
'february-gen' => 'februoari',
'march-gen' => 'moarte',
'april-gen' => 'april',
'may-gen' => 'mei',
'june-gen' => 'juni',
'july-gen' => 'juli',
'august-gen' => 'ogustus',
'september-gen' => 'september',
'october-gen' => 'oktober',
'november-gen' => 'november',
'december-gen' => 'december',
'jan' => 'jan',
'feb' => 'feb',
'mar' => 'mrt',
'apr' => 'apr',
'may' => 'mei',
'jun' => 'jun',
'jul' => 'jul',
'aug' => 'ogs',
'sep' => 'sep',
'oct' => 'okt',
'nov' => 'nov',
'dec' => 'dec',

# Categories related messages
'listingcontinuesabbrev' => 'vervolg',

'newwindow' => '(opent in e nieuw veister)',
'moredotdotdot' => 'Mêer…',
'mypage' => 'Myn gebrukersblad',
'mytalk' => 'Myn discuusjeblad',
'and' => '&#32;en',

# Cologne Blue skin
'qbedit' => 'Bewerkn',

# Vector skin
'vector-action-delete' => 'Wegdoen',
'vector-action-move' => 'Ernoemn',
'vector-view-create' => 'Anmoakn',
'vector-view-edit' => 'Bewerkn',
'vector-view-history' => 'Geschiedenisse bekykn',
'vector-view-view' => 'Leezn',
'vector-view-viewsource' => 'Brontekst bekykn',

'tagline' => 'Van {{SITENAME}}',
'help' => 'Ulpe',
'search' => 'Zoekn',
'searchbutton' => 'Zoekn',
'history_short' => 'Geschiedenisse',
'updatedmarker' => 'bygewerkt sinds min latste visite',
'printableversion' => 'Drukboare versie',
'permalink' => 'Bluuvende link',
'print' => 'Drukn',
'edit' => "Bewerk'n",
'create' => 'Anmoakn',
'editthispage' => 'Da blad ier bewerkn',
'create-this-page' => 'Da blad ier anmoakn',
'delete' => 'Wegdoen',
'deletethispage' => 'Da blad ier verwydern',
'undelete_short' => '{{PLURAL:$1|êen bewerkinge|$1 bewerkingn}} werekêern',
'protect' => 'Beveilign',
'protectthispage' => 'Da blad ier beveilign',
'unprotect' => 'beveiliginge wegdoen',
'unprotectthispage' => 'De beveiliginge van da blad ier ofleggn',
'newpage' => 'Nieuw blad',
'talkpagelinktext' => 'Discuusje',
'specialpage' => 'Specioal blad',
'talk' => 'Discuusje',
'toolbox' => 'Ulpmiddeln',

# All link text and link target definitions of links into project namespace that get used by other message strings, with the exception of user group pages (see grouppage).
'aboutsite' => 'Over {{SITENAME}}',
'aboutpage' => 'Project:Info',
'disclaimers' => 'Aansprakelekeid',
'mainpage' => 'Voorblad',
'privacy' => 'Privacybeleid',

'viewsourceold' => 'Brontekst bekykn',
'viewsourcelink' => 'Brontekst bekykn',
'site-rss-feed' => '$1 RSS-feed',
'site-atom-feed' => '$1 Atom-feed',
'red-link-title' => '$1 (Blad bestoat nie)',

# Short words for each namespace, by default used in the namespace tab in monobook
'nstab-special' => 'Specioal blad',

# General errors
'viewsource' => 'Brontekst bekykn',

# Login and logout pages
'logout' => 'Ofmeldn',

# Search results
'search-result-size' => '$1 ({{PLURAL:$2|1 woord|$2 woordn}})',

# Special:Log/newusers
'newuserlogpage' => 'Logboek nieuwe gebrukers',

# Recent changes
'recentchanges' => 'Juste veranderd',

# Upload
'upload' => 'Bestand toevoegn',
'uploadbtn' => 'Bestand toevoegn',
'uploadnologin' => 'Ge zyt nie angemeld',
'uploadlog' => 'logboek upgeloade bestandn',
'uploadlogpage' => 'Logboek upgeloade bestandn',
'uploadlogpagetext' => 'Hier stoa e lyste met de mêest recente upgeloade bestandn.',
'uploadedfiles' => 'Upgeloade bestandn',
'uploadedimage' => '"[[$1]]" upgeload',

# Unwatched pages
'unwatchedpages' => "Pagina's die ip niemands volglyste stoan",

# Miscellaneous special pages
'newpages' => 'Nieuwe bloadn',
'newpages-username' => 'Gebrukersnoame:',

# Watchlist
'mywatchlist' => 'Myn volglyste',
'watch' => 'Volgn',
'unwatch' => 'Nie volgn',

# Displayed when you click the "watch" button and it is in the process of watching
'unwatching' => 'Stoppn me volgn...',

# Undelete
'undelete' => 'Weggedoane bloadn bekykn',
'undeletepage' => 'Weggedoane bloadn erstelln of bekykn',
'undeletehistorynoadmin' => "'t Artikel is weggedoan. De reden davôorn ku je zien in de soamnvattienge ieronder, tôpe me uutleg over wie dat 't blad bewerkt èt vôorn dat weggedoan es gewist. Den tekst van die weggedoane versies kan allêene door sysops gelezen wordn.",
'undeletebtn' => 'Erstelln',
'undeletedfiles' => '{{PLURAL:$1|1 bestand|$1 bestandn}} ersteld',

# Contributions
'mycontris' => 'Myn bydroagn',
'uctop' => '(latste veranderienge)',

# Block/unblock
'contribslink' => 'bydroagn',

# Move page
'delete_and_move' => 'Wegdoen en ernoemn',

# Tooltip help for the actions
'tooltip-n-mainpage' => "Noar 't voorblad goane",

# Special:NewFiles
'newimages' => 'Nieuwe ofbeeldiengn',

);
