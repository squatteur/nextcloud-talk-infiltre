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

if (($argc < 2)) {
	echo 'Missing search term in call to infiltre.php';
	return 1;
}

$argmode = $argv[1];
$room = $argv[2];
$user = $argv[3];

$mode = explode(' ', $argmode)[0];


if ($mode === '--help' || !in_array($mode, ['start', 'score', 'tour', 'vote', ''], true)) {
	echo '/game - une commande pour jouer à l\'infiltré' . "\n";
	echo "\n";
	echo 'Example: /game start|score|tour|vote <joueur>' . "\n";
	return;
}

function NextcloudTalk_SendMessage($channel_id, $message)
{
	$data = array(
		"token" => $channel_id,
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
	curl_setopt($ch, CURLOPT_USERPWD, BOT_USER . ":" . BOT_PASS);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	// Set HTTP Header 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($payload),
		'Accept: application/json',
		'OCS-APIRequest: true'
	));
	$result = curl_exec($ch);
	curl_close($ch);
}

function NextcloudTalk_Participants($channel_id, $message)
{
	$data = array(
		"token" => $channel_id,
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
	curl_setopt($ch, CURLOPT_USERPWD, BOT_USER . ":" . BOT_PASS);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	// Set HTTP Header 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($payload),
		'Accept: application/json',
		'OCS-APIRequest: true'
	));
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function tour()
{
	global $room;
	$file = ROOT . '/data/' . $room . '.json';
	$filedata = file_get_contents($file);
	$obj = json_decode($filedata);

	$response_bot = "Ordre du tour\n";
	$tabOrdre = array();
	foreach ($obj as $element) {
		if ($element->en_jeu != 'elimine') {
			$tabOrdre[$element->ordre] = $element->name;
		}
	}
	ksort($tabOrdre);

	foreach ($tabOrdre as $element) {
		$response_bot .= "- @" . $element . "\n";
	}

	if (count($tabOrdre) <= 2) {
		$response_bot .= "La partie est terminée";
	}
	NextcloudTalk_SendMessage($room, $response_bot);
	//$response_bot .= "A qui de jouer ?";
}

function score()
{
	global $room;

	// lecture {$room}.json
	$file = ROOT . '/data/' . $room . '_score.json';
	if (!file_exists($file)) {
		echo "Pas de score pour le moment, veuillez lancer une partie";
		return;
	} else {
		$response_bot = "";
		$fileScore = file_get_contents($file);
		$obj = json_decode($fileScore);

		foreach ($obj as $joueur => $score) {
			$response_bot .= "- @" . $joueur . " : " . $score . " points\n";
		}

		if ($response_bot == "") {
			$response_bot = "Erreur : pas de score";
		}
		NextcloudTalk_SendMessage($room, $response_bot);
	}
}

switch ($mode) {
	case 'start':
		$response = '';

		$mode_debug = false;
		$debug = explode(' ', $argmode)[1];
		if ($debug == 'debug') {
			$mode_debug = true;
		}
		$message = "Début de partie lancée par @" . $user . " :";

		//$participants = exec('curl -H "Content-Type: application/json" -H "Accept: application/json" -H "OCS-APIRequest: true" -v -u "bot_infiltre:notify3KO" https://stephane.merle.fr/claude/ocs/v2.php/apps/spreed/api/v1/room/'.$room.'/participants');
		NextcloudTalk_SendMessage($room, $message);

		$objparticipants = NextcloudTalk_Participants($room, $message) . "\n";
		$participants = json_decode($objparticipants);

		// lire le json avec les mots 
		$datamot = file_get_contents(ROOT . '/data/nom.json');
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
		$ind_theme = random_int(1, $sum_key);
		$theme = $tab_theme[$ind_theme];

		echo "Thème : $theme\n";
		$ind_mot_joueur = random_int(0, (count($obj->$theme) - 1));
		$ind_mot_infiltre = random_int(0, (count($obj->$theme) - 1));
		while ($ind_mot_joueur == $ind_mot_infiltre) {
			$ind_mot_infiltre = random_int(0, (count($obj->$theme) - 1));
		}
		$mot_joueur = $obj->$theme[$ind_mot_joueur];
		if ($mode_debug)
			$response .= "ind joueur : " . $mot_joueur . "\n";

		$mot_infiltre = $obj->$theme[$ind_mot_infiltre];
		if ($mode_debug)
			$response .= "ind infiltré : " . $mot_infiltre . "\n";

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

		$nbjoueur = count($participants->ocs->data) - 1;
		$indice_infiltre = random_int(1, ($nbjoueur));
		$data = array();
		$joueur = 1;

		//calcul de l'ordre du tour
		$ordre = range(1, $nbjoueur);
		shuffle($ordre);
		// foreach ($ordre as $ordre2) {
		// 	$response .= $ordre2 ."\n";
		// }
		$tabJoueur = array();
		$tabScore = array();
		foreach ($participants->ocs->data as $v) {

			if ($v->userId != 'bot_infiltre') {
				$tabJoueur["name"] = $v->userId;
				$tabScore[$v->userId] = 0;
				if ($joueur == $indice_infiltre) {
					$tabJoueur["mot"] = $mot_infiltre;
					$tabJoueur["role"] = "infiltré";
					$tabJoueur["ordre"] = $ordre[$joueur - 1];
					$tabJoueur["en_jeu"] = "true";
					if ($mode_debug)
						$response .=  "@" . $tabJoueur["name"] . " est infiltré\n";
				} else {
					$tabJoueur["mot"] = $mot_joueur;
					$tabJoueur["role"] = "joueur";
					$tabJoueur["ordre"] = $ordre[$joueur - 1];
					$tabJoueur["en_jeu"] = "true";
				}
				$data[] = $tabJoueur;
				if (isset($listeparticipants)) {
					$listeparticipants .= ", ";
				}
				$listeparticipants .= $v->userId;
				$joueur++;
			}
		}
		$listeparticipants .= "\n";


		// creation {$room}_score.json
		$fileScore = ROOT . '/data/' . $room . '_score.json';
		if (!file_exists($fileScore)) {

			$dataScore = json_encode($tabScore);
			file_put_contents($fileScore, $dataScore);
		}

		// creation {$room}.json
		$file = ROOT . '/data/' . $room . '.json';
		$datafic = $data;

		$data = json_encode($datafic);
		file_put_contents($file, $data);

		$tabOrdre = array();
		//$response .= "Les participants : " . $listeparticipants;
		foreach ($datafic as $key => $value) {
			//$response .= $value['name']." -> ".$value['ordre'];
			$tabOrdre[$value['ordre']] = $value['name'];
		}
		ksort($tabOrdre);
		foreach ($tabOrdre as $key => $val) {
			$response .= "$key : @$val\n";
		}


		//$response .= $ordre2 ."\n";
		$response_bot = "Tapez /mot pour obtenir votre mot secret";
		NextcloudTalk_SendMessage($room, $response);
		echo ($response_bot);
		break;

	case 'score':
		if (isset(explode(' ', $argmode)[1])) {
			$suppression = explode(' ', $argmode)[1];
			if ($suppression == "supp") {
				if (file_exists(ROOT . '/data/' . $room . '_score.json')) {
					unlink(ROOT . '/data/' . $room . '_score.json');
				} else {
					echo "Il n'y a pas de fichier de score pour ce salon.";
				}
				return;
			} else {
				echo "Utilisez /game score supp pour réinitialiser le score.";
			}
		}
	
		score();

		break;

	case 'vote':
		if (!isset(explode(' ', $argmode)[1])) {
			echo 'Mettez un joueur après la commande /game vote <joueur> pour l\'éliminer';
			return 1;
		} else {
			$elimine = explode(' ', $argmode)[1];
		}

		// lecture {$room}.json
		$file = ROOT . '/data/' . $room . '.json';
		$filedata = file_get_contents($file);
		$obj = json_decode($filedata);
		$response_bot = "";

		foreach ($obj as $element) {
			if (($element->name == $elimine) || ($element->name == substr($elimine, 1))) {
				if ($element->en_jeu == 'elimine') {
					$response_bot .= "- @" . $element->name . " est déja éliminé.\n";
					NextcloudTalk_SendMessage($room, $response_bot);
					//echo $response_bot;
					return;
				}
				$response_bot .= "- @" . $element->name . " est éliminé et était un " . $element->role . "\n";
				$element->en_jeu = 'elimine';
				file_put_contents($file, json_encode($obj));


				if ($element->role == 'infiltré') {
					$response_bot .= "Les joueurs gagnent.\n";

					// creation {$room}_score.json
					$fileScore = ROOT . '/data/' . $room . '_score.json';
					if (file_exists($fileScore)) {
						$fileScore2 = file_get_contents($fileScore);
						$objScore = json_decode($fileScore2);
						foreach ($objScore as $key => $value) {
							if ($key == $element->name) {
								$objScore->$key = $value;
							} else {
								$objScore->$key = $value + 2;
							}
						}
						$dataScore2 = json_encode($objScore);
						file_put_contents($fileScore, $dataScore2);
					} else {
						//recherche joueur et score
						foreach ($obj as $element2) {
							if ($element2->role == 'joueur') {
								$dataScore[$element2->name] = 2;
							}
							if ($element2->role == 'infiltré') {
								$dataScore[$element2->name] = 0;
							}
						}
						$dataScore2 = json_encode($dataScore);
						file_put_contents($fileScore, $dataScore2);
					}
					NextcloudTalk_SendMessage($room, $response_bot);
					score();
					return;
				}
				// si nb de joueur = 1 alors infiltre gagne
				$nbjoueur = 0;
				$nbinfiltre = 0;
				//recherche joueur et score
				foreach ($obj as $element2) {
					if (($element2->role == 'joueur') && ($element2->en_jeu == 'true')) {
						$nbjoueur++;
					}
					if (($element2->role == 'infiltré') && ($element2->en_jeu == 'true')) {
						$nbinfiltre++;
					}
				}

				if (($nbinfiltre == 1) && ($nbjoueur <= 1)) {
					$response_bot .= "L'infiltré gagne.\n";

					// creation {$room}_score.json
					$fileScore = ROOT . '/data/' . $room . '_score.json';
					if (file_exists($fileScore)) {
						$fileScore2 = file_get_contents($fileScore);
						$objScore = json_decode($fileScore2);
						foreach ($objScore as $key => $value) {
							if ($key == $element->name) {
								$objScore->$key = $value + 10;
							} else {
								$objScore->$key = $value;
							}
						}
						$dataScore2 = json_encode($objScore);
						file_put_contents($fileScore, $dataScore2);
					} else {
						//recherche joueur et score
						foreach ($obj as $element2) {
							if ($element2->role == 'joueur') {
								$dataScore[$element2->name] = 0;
							}
							if ($element2->role == 'infiltré') {
								$dataScore[$element2->name] = 10;
							}
						}
						$dataScore2 = json_encode($dataScore);
						file_put_contents($fileScore, $dataScore2);
					}
					NextcloudTalk_SendMessage($room, $response_bot);
					score();
					return;
				}

				NextcloudTalk_SendMessage($room, $response_bot);
				tour();
				// echo "Faites /game tour";
				return;
			}
		}

		$response_bot = "Mauvais nom de joueur";
		echo $response_bot;
		break;

	case 'tour':
		//liste les joueurs actifs

		tour();

		break;

	default:
		echo "/game --help";
}
