<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * German locale for local_openeducationbadges.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['active'] = 'aktiv';
$string['activeissuers'] = 'Aktive Institutionen';
$string['activitycompletion'] = 'bei Aktivitätsabschluss:';
$string['addnew'] = 'Neu hinzufügen';
$string['addnewoauth2'] = 'Füge eine neue OAuth2 API Verbindung hinzu';
$string['all'] = 'Alle';
$string['award'] = 'vergeben';
$string['badgecreatefor'] = 'neuen Badge erstellen für \'{$a}\'';
$string['badgeedit'] = 'bearbeiten';
$string['badgelist'] = 'Liste der Badges';
$string['badgelisttitle'] = 'Alle Badges';
$string['badgesplatform'] = 'Erworbene Open Educational Badges';
$string['client'] = 'Verbindung';
$string['clientadd'] = 'Verbindung hinzufügen';
$string['clientdeleted'] = 'Verbindung gelöscht';
$string['clientid'] = 'Client ID';
$string['clientidexists'] = 'Client ID existiert bereits';
$string['clientname'] = 'Name der Verbindung';
$string['clients'] = 'Verbindungen';
$string['clientsaved'] = 'Verbindung gespeichert';
$string['clientsecret'] = 'Client secret';
$string['connectionproblemgeneral'] = 'Es ist ein Problem mit ihrer Verbindungen zu Open Education Badges aufgetreten. Bitte überprüfen Sie ihre Plugin Credentials, die Verfügbarkeit des OEB Servers oder ähnliches.';
$string['connectionsevered'] = 'Diese Verbindung zu Open Education Badges wurde getrennt. Bitte überprüfen Sie ihre Plugin Credentials, die Verfügbarkeit des OEB Servers oder ähnliches und speichern Sie die Verbindung erneut.';
$string['coursecompletion'] = 'bei Kursabschluss';
$string['deleteclientconfirm'] = 'Sind Sie sicher, dass Sie diese Verbindung entfernen wollen?';
$string['editissuers'] = 'Institutionen anpassen';
$string['infoconnectapi'] = 'Füge eine API Authentifizierungsmethode hinzu.';
$string['invalidclientsecret'] = 'Die Client ID oder das Secret sind ungültig';
$string['issuebadgefailed'] = 'Badge Vergabe fehlgeschlagen';
$string['issuers'] = 'Institutionen';
$string['issuerscustomize'] = 'Institutionen anpassen';
$string['issuerssaved'] = 'Institutionen angepasst';
$string['nobadgegiven'] = 'Kein Badge übergeben';
$string['nobadges'] = 'Keine Badges';
$string['nobadgesearned'] = 'Bisher noch keine Badges erworben';
$string['noiframeurl'] = 'Die Iframe URL ist momentan nicht verfügbar.';
$string['noissuergiven'] = 'Kein Issuer übergeben';
$string['oauth2problem'] = 'Es ist ein Problem aufgetreten. Bitte überprüfen Sie ihre Plugin Credentials.';
$string['oeb'] = 'Open Education Badges';
$string['openeducationbadges:configure'] = ' Konfiguriere OEB Settings';
$string['pluginname'] = 'Open Education Badges';
$string['profilebadgelist'] = 'Open Education Badges';
$string['saveawarding'] = 'speichern';
$string['selectaward'] = 'Wähle Vergabemethode:';
$string['selectcompletion'] = 'Wähle Vergabemethode:';
$string['settings'] = 'Settings';
$string['settingssaved'] = 'Settings gespeichert';
$string['status'] = 'Status';

$string['clientid_help'] = 'Client ID ihres openbadges.education Account.';
$string['clientsecret_help'] = 'Melden Sie sich bei Ihrem openbadges.education Account an und navigieren Sie zu "App Integration". Dort können Sie das Client secret finden.';

$string['connectionproblembody'] = 'Es ist ein Problem mit der Verbindung {$a->name} zu Open Education Badges aufgetreten. Bitte überprüfen Sie ihre Plugin Credentials, die Verfügbarkeit des OEB Servers oder ähnliches und speichern Sie die Verbindung erneut.';
$string['connectionproblemsubject'] = 'Es ist ein Problem mit der Verbindung {$a->name} aufgetreten.';

$string['event:apirequest_answered'] = 'OEB API Antwort';
$string['event:apirequest_answered:info'] = 'Antwort: {$a->info}';
$string['event:apirequest_called'] = 'OEB API Anfrage';
$string['event:apirequest_called:info'] = 'Anfrage: {$a->info}';
$string['event:apirequest_failed'] = 'OEB API Fehler';
$string['event:apirequest_failed:info'] = 'API Fehler: {$a->info}';

$string['messageprovider:connectionproblem'] = 'Ein Problem mit der Verbindung ist aufgetreten.';

$string['privacy:metadata:badge_queue'] = 'Informationen zu ausstehenden Badges, die ausgestellt werden sollen.';
$string['privacy:metadata:badge_queue:badgeid'] = 'Die ID des auszustellenden Badges.';
$string['privacy:metadata:badge_queue:id'] = 'Die ID des Datensatzes in der Datenbank.';
$string['privacy:metadata:badge_queue:user_id'] = 'Die ID des Benutzer für den der Badge ausgestellt werden soll.';
$string['privacy:metadata:oeb_client'] = 'Um eine Integration mit dem Remote-Dienst openbadges.education zu ermöglichen, müssen Benutzerdaten mit diesem Dienst ausgetauscht werden.';
$string['privacy:metadata:oeb_client:datarights'] = 'Ihre Datenschutzrechte und wie Sie diese ausüben: <a href="https://openbadges.education/page/privacy-policy" target="_blank">https://openbadges.education/page/privacy-policy</a>';
$string['privacy:metadata:oeb_client:useremail'] = 'Die Benutzer-E-Mail wird von Moodle gesendet, um die Ausstellung von Badges zu ermöglichen und Ihnen den Zugriff auf Ihre Badges auf dem Remote-System zu ermöglichen.';

$string['task:issue_badge'] = 'Versuch Badge in der Warteschlange zu vergeben';
$string['task:issue_badges'] = 'Versuch Badges in der Warteschlange zu vergeben';
