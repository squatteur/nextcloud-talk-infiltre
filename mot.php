<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 bestel squatteur <bestel@squatteur.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Importations config.php
require 'config.php';

if (PHP_SAPI !== 'cli') {
	// Only allow access via the console
	exit;
}

if ($argc < 2) {
	echo 'Missing search term in call to infiltre.php';
	return 1;
}

$mode = $argv[1];
$room = $argv[2];
$user = $argv[3];

if ($mode === '--help') {
	echo '/L\infiltré - récupère votre mot' . "\n";
	echo "\n";
	echo 'Exemple: /mot' . "\n";
	return;
}

// lecture {$room}.json

$file = ROOT."/data/".$room.'.json';
$filedata = file_get_contents($file);
$obj = json_decode($filedata);

// $nb_partie = 0;
// foreach ($obj as $key => $val) {
// 	$nb_partie++;
// }
foreach ($obj as $element) {

	if ($element->name == $user) {
		$response_bot = $element->mot;
		echo ($response_bot);
		return;
	}

}
$response_bot = "Erreur pas de mot";
//NextcloudTalk_SendMessage($room, $response);
echo ($response_bot);