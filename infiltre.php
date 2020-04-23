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

if ( ($argc < 2) ){
	echo 'Missing search term in call to infiltre.php';
	return 1;
}

$argmode = $argv[1];
$room = $argv[2];
$user = $argv[3];

$mode = explode(' ', $argmode)[0];

if ($mode === '--help' || !in_array($mode, ['start', 'score', 'tour', 'vote', ''], true)) {
	echo '/game - une commande pour jouer à l\infiltré' . "\n";
	echo "\n";
	echo 'Example: /game start|score|tour|vote <utilisateur>' . "\n";
	return;
}

function NextcloudTalk_SendMessage($channel_id, $message) { 
	$data = array( "token" => $channel_id, 
		"message" => $message, 
		"actorDisplayName" => "PYTHON-NOTIFICATION", 
		"actorType" => "", 
		"actorId" => "", 
		"timestamp" => 0, 
		"messageParameters" => array()
	);
	$payload = json_encode($data);
	$ch = curl_init(SERVER . '/ocs/v2.php/apps/spreed/api/v1/chat/' . $channel_id);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	curl_setopt($ch, CURLOPT_USERPWD, BOT_USER.":".BOT_PASS);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	// Set HTTP Header 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json',
												'Content-Length: ' . strlen($payload),
												'Accept: application/json',
												'OCS-APIRequest: true') );
	$result = curl_exec($ch);
	curl_close($ch);
 }

 function NextcloudTalk_Participants($channel_id, $message) { 
	$data = array( "token" => $channel_id, 
//		"message" => $message, 
		"actorDisplayName" => "PYTHON-NOTIFICATION", 
		"actorType" => "", 
		"actorId" => "", 
		"timestamp" => 0, 
		"messageParameters" => array()
	);
	$payload = json_encode($data);
	$ch = curl_init(SERVER . '/ocs/v2.php/apps/spreed/api/v1/room/' . $channel_id . '/participants');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_USERPWD, BOT_USER.":".BOT_PASS);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	// Set HTTP Header 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json',
												'Content-Length: ' . strlen($payload),
												'Accept: application/json',
												'OCS-APIRequest: true') );
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

switch ($mode) {
	case 'start':
		$message = "Début de partie lancée par @".$user." :";
		 
		//$participants = exec('curl -H "Content-Type: application/json" -H "Accept: application/json" -H "OCS-APIRequest: true" -v -u "bot_infiltre:notify3KO" https://stephane.merle.fr/claude/ocs/v2.php/apps/spreed/api/v1/room/'.$room.'/participants');
		NextcloudTalk_SendMessage($room, $message);
		
		$objparticipants = NextcloudTalk_Participants($room, $message) . "\n";
		$participants = json_decode($objparticipants);
		
		// lire le json avec les mots 
			$datamot = file_get_contents(ROOT.'/data/nom.json'); 
			$obj = json_decode($datamot); 
			// accéder à l'élément approprié
			//echo $obj[0]->nom;
			// choix mot et mot de l'infiltré

			// $jsonIterator = new RecursiveIteratorIterator(
			// 	new RecursiveArrayIterator(json_decode($datamot, TRUE)),
			// 	RecursiveIteratorIterator::SELF_FIRST);
			
			// foreach ($jsonIterator as $key => $val) {
			// 	if(is_array($val)) {
			// 		echo "$key:\n";
			// 	} else {
			// 		echo "$key => $val\n";
			// 	}
			// }
			$sum_key = 0;
			//$tab_theme = array();
			foreach ($obj as $key => $val) {
				$sum_key++;
				$tab_theme[$sum_key] = $key;
			}
			$ind_theme = random_int (1, $sum_key);
			$theme = $tab_theme[$ind_theme];

			echo "Thème : $theme\n";
			$ind_mot_joueur = random_int (0, (count($obj->$theme)-1));
			$ind_mot_infiltre = random_int (0, (count($obj->$theme)-1));
			while ($ind_mot_joueur == $ind_mot_infiltre) {
				$ind_mot_infiltre = random_int (0, (count($obj->$theme)-1));
			}
			$mot_joueur = $obj->$theme[$ind_mot_joueur];
		$response = "ind joueur : ".$mot_joueur."\n";
			$mot_infiltre = $obj->$theme[$ind_mot_infiltre];
		$response .= "ind infiltré : ".$mot_infiltre."\n";
		// if (file_exists(ROOT.'/data/'.$room.'.json')) {
		// 	$dataAnciennePartie = file_get_contents(ROOT.'data/'.$room.'.json');
		// 	$dataAnciennePartieDecode = json_decode($dataAnciennePartie);
		// 	$nb_partie = 0;
		// 	foreach ($dataAnciennePartieDecode as $key => $val) {
		// 		$nb_partie++;
		// 		echo $key;
		// 	}
		// } else {
		// 	$nb_partie = 0;
		// }

		//$partie = $nb_partie + 1;

		$nbjoueur = count($participants->ocs->data)-1;
		$indice_infiltre = random_int (1, ($nbjoueur));
		$data = array();
		$joueur= 1;

		//calcul de l'ordre du tour
		$ordre = range(1, $nbjoueur);
		shuffle($ordre);
		foreach ($ordre as $ordre2) {
			$response .= $ordre2 ."\n";
		}
		
		foreach ($participants->ocs->data as $v) {
		
			if ($v->userId != 'bot_infiltre') {
				$data[$joueur]["name"] = $v->userId;
				
				$data[$joueur]["score"] = 0;
				if ($joueur == $indice_infiltre){
					$data[$joueur]["mot"] = $mot_infiltre;
					$data[$joueur]["role"] = "infiltre";
					$data[$joueur]["ordre"] = $ordre[$joueur-1];
echo "@".$data[$joueur]["name"]." est infiltré\n";
				} else {
					$data[$joueur]["mot"] = $mot_joueur;
					$data[$joueur]["role"] = "joueur";
					$data[$joueur]["ordre"] = $ordre[$joueur-1];
				}
				
				if (isset($listeparticipants)){
					$listeparticipants .= ", ";
				}
				$listeparticipants .= $v->userId;
				$joueur++;
			}
		 }
		 $listeparticipants .= "\n";

		// creation {$room}.json
		$file = ROOT.'/data/'.$room.'.json';
		$datafic = $data;

		$data = json_encode($datafic);
		file_put_contents($file, $data);
		
		$response .= "Les participants : " . $listeparticipants;
		$response_bot = "Tapez /mot pour obtenir votre mot secret";
		NextcloudTalk_SendMessage($room, $response);
		echo ($response_bot);
		break;

	case 'score':
		// lecture {$room}.json
		$file = ROOT.'/data/'.$room.'.json';
		$filedata = file_get_contents($file);
		$obj = json_decode($filedata);

		$response_bot = "";
		foreach ($obj as $element) {
			if ($element->name != 'bot_infiltre') {
				$response_bot .= "- @" . $element->name . " : " . $element->score. " points\n";
			}
		}
		if ($response_bot == ""){
			$response_bot = "Erreur : pas de score";
		}
		NextcloudTalk_SendMessage($room, $response_bot);
		echo "A qui de jouer ?";
		break;

	case 'vote':
		$elimine = explode(' ', $argmode)[1];

		// lecture {$room}.json
		$file = ROOT.'/data/'.$room.'.json';
		$filedata = file_get_contents($file);
		$obj = json_decode($filedata);
		$response_bot = "";
		
		foreach ($obj as $element) {
			if (($element->name == $elimine) || ($element->name == substr($elimine,1))) {
				if ($element->role == 'elimine'){
					$response_bot .= "- @" . $element->name . " est déja éliminé.\n";
					echo $response_bot;
					return;
				}
				$response_bot .= "- @" . $element->name . " était un " . $element->role. "\n";
				$element->role = 'elimine';
				file_put_contents($file, json_encode($obj));
				NextcloudTalk_SendMessage($room, $response_bot);
				echo "Faites /game tour";
				return;
			}
		}

		$response_bot = "Mauvais nom de joueur";
		echo $response_bot;
		break;

	case 'tour':
		//liste les joueurs actifs

		$file = ROOT.'/data/'.$room.'.json';
		$filedata = file_get_contents($file);
		$obj = json_decode($filedata);

		$response_bot = "";
		foreach ($obj as $element) {
			if ($element->name != 'bot_infiltre') {
				if ($element->name != 'elimine') {
					$response_bot .= "- @" . $element->name."\n";
				}
			}
		}
		if ($response_bot == ""){
			$response_bot = "Erreur : pas de score";
		}
		NextcloudTalk_SendMessage($room, $response_bot);
		echo "A qui de jouer ?";


		break;
	default:
		echo "/game --help";
}
