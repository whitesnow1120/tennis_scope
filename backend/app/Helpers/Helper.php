<?php

namespace App\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use DateTime;
use DatePeriod;
use DateInterval;

class Helper {
	public static function getGamesPoint($game) {
		if ($game == "love" || (int)$game == 10) {
			return 0;
		} else if ((int)$game == 15) {
			return 1;
		} else if ((int)$game == 30) {
			return 2;
		} else if ((int)$game == 40) {
			return 3;
		}
		return NULL;
	}

	public static function getCorrectDetail($detail) {
		if ($detail == "") {
			return [];
		}
		$details = explode(",", $detail);
		$new_details = array();
		$i = 1;
		foreach ($details as $data) {
			$index = (int)explode(":", $data)[0];
			if ($index == $i) {
				array_push($new_details, $data);
			} else {
				array_push($new_details, $i . "t:0-0");
				array_push($new_details, $data);
				$i ++;
			}
			$i ++;
		}
		return $new_details;
	}

	/**
	 * Get the start and end games for player and opponents
	 */
	public static function getSetsPerformanceDetail($matches, $player_id) {
		$sets = $matches;
		$i = 0;
		foreach ($matches as $match) {
			$details = Helper::getCorrectDetail($match["detail"]);
			$total_details_count = count($details);
			$set_scores = explode(",", $match["scores"]);
			if ($match["player1_id"] == $player_id) {
				$home = 1;
				$opponent_id = $match["player2_id"];
			} else {
				$home = 2;
				$opponent_id = $match["player1_id"];
			}
			$j = 0;
			$score_count = array();
			$score_count[0] = 0;

			$ww = 0; // (2:0)  => (6:4)
			$wl = 0; // (2:0)  => (4:6)
			$lw = 0; // (0:2)  => (6:4)
			$ll = 0; // (0:2)  => (4:6)
			
			foreach ($set_scores as $set_score) {
				$player_scores = array();
				$opponent_scores = array();
				$player_scores[0] = 0;
				$opponent_scores[0] = 0;

				$score_cnt = 0;

				$sets[$i]["sets"][$j][$player_id]["brw"] = [0,0,0,0]; // 10, 15, 30, 40
				$sets[$i]["sets"][$j][$player_id]["brl"] = [0,0,0,0];
				$sets[$i]["sets"][$j][$player_id]["gah"] = [0,0,0,0];
				
				$sets[$i]["sets"][$j][$opponent_id]["brw"] = [0,0,0,0];
				$sets[$i]["sets"][$j][$opponent_id]["brl"] = [0,0,0,0];
				$sets[$i]["sets"][$j][$opponent_id]["gah"] = [0,0,0,0];
				
				$sets[$i]["sets"][$j]["score"] = trim($set_score);
				$sets[$i]["sets"][$j]["depth"] = 0;
				$scores = explode("-", $set_score);
				if (count($scores) == 2) {
					$score_count[$j + 1] = $score_count[$j] + (int)$scores[0] + (int)$scores[1];

					if ($home == 1) {
						$player_score = (int)$scores[0];
						$opponent_score = (int)$scores[1];
					} else {
						$player_score = (int)$scores[1];
						$opponent_score = (int)$scores[0];
					}

					$sets[$i]["sets"][$j][$player_id]["score"] = $player_score;
					$sets[$i]["sets"][$j][$opponent_id]["score"] = $opponent_score;

					if ($match["detail"] != "") {
						// set BRW, BRL, GAH
						for ($k = $score_count[$j]; $k < $score_count[$j + 1]; $k ++) {
							if ($k < $total_details_count) {
								$set_details = explode(":", $details[$k]);
								if (count($set_details) == 4 && ($set_details[1] == "b" || $set_details[1] == "h")) {
									$game = Helper::getGamesPoint($set_details[3]);
										if ((int)$set_details[2] == $home) {
											if ($set_details[1] == "b") {
												$sets[$i]["sets"][$j][$player_id]["brw"][$game] ++;
												$sets[$i]["sets"][$j][$opponent_id]["brl"][$game] ++;
											} else {
												$sets[$i]["sets"][$j][$player_id]["gah"][$game] ++;
											}
											// add score for depth
											$player_scores[$score_cnt + 1] = $player_scores[$score_cnt] + 1;
											$opponent_scores[$score_cnt + 1] = $opponent_scores[$score_cnt];
										} else {
											if ($set_details[1] == "b") {
												$sets[$i]["sets"][$j][$opponent_id]["brw"][$game] ++;
												$sets[$i]["sets"][$j][$player_id]["brl"][$game] ++;
											} else {
												$sets[$i]["sets"][$j][$opponent_id]["gah"][$game] ++;
											}
											// add score for depth
											$opponent_scores[$score_cnt + 1] = $opponent_scores[$score_cnt] + 1;
											$player_scores[$score_cnt + 1] = $player_scores[$score_cnt];
										}
										$score_cnt ++;
								}
							}
						}
					}

					// calculate the depth
					$depths = array();
					$depths[0] = 0;
					for ($n = 0; $n < $score_cnt; $n ++) {
						if ($player_scores[$n] == 6 || $opponent_scores[$n] == 6) {
							break;
						} else {
							$depths[$n] = $player_scores[$n] - $opponent_scores[$n];
						}
					}
					if ($player_score > $opponent_score) {
						$depth = max($depths);
					} else {
						$depth = min($depths);
					}
					$sets[$i]["sets"][$j]["depth"] = $depth;

					// set performance
					for ($n = 0; $n < $score_cnt; $n ++) {
						$game_depth = $player_scores[$n] - $opponent_scores[$n];
						// set depth
						if ($game_depth == 2) {
							if ($player_score > $opponent_score) {
								$ww ++;
							} else {
								$wl ++;
							}
						} else if ($game_depth == -2) {
							if ($player_score > $opponent_score) {
								$lw ++;
							} else {
								$ll ++;
							}
						} else if (($player_scores[$n] == 6 && $opponent_scores[$n] < 6) || ($player_scores[$n] < 6 && $opponent_scores[$n] == 6)) { // tie-break
							if ($player_score > $opponent_score) {
								$ww ++;
							} else {
								$ll ++;
							}
						}
					}
				}
				
				$j ++;
			}
			$sets[$i]["performance"] = [
				"ww" => $ww,
				"wl" => $wl,
				"lw" => $lw,
				"ll" => $ll,
			];
			$i ++;
		}
		return $sets;
	}

	/**
	 * Get the start and end games for only opponents
	 */
	public static function getSetsOpponents($matches, $player_id) {
		$sets = array();
		$i = 0;
		foreach ($matches as $match) {
			$details = Helper::getCorrectDetail($match["detail"]);
			$total_details_count = count($details);
			$set_scores = explode(",", $match["scores"]);
			if ($match["player1_id"] == $player_id) {
				$home = 1;
			} else {
				$home = 2;
			}
			$j = 0;
			$score_count = array();
			$score_count[0] = 0;
			
			foreach ($set_scores as $set_score) {
				$player_scores = array();
				$player_scores[0] = 0;

				$score_cnt = 0;

				$scores = explode("-", $set_score);
				$score_count[$j + 1] = $score_count[$j] + (int)$scores[0] + (int)$scores[1];
				
				// // by games
				$sets[$i][$j]["brw"] = [0,0,0,0]; // 10, 15, 30, 40
				$sets[$i][$j]["brl"] = [0,0,0,0];
				$sets[$i][$j]["gah"] = [0,0,0,0];

				// by sets
				$sets[$i][$j]["sets_brw"] = 0; // 10, 15, 30, 40
				$sets[$i][$j]["sets_brl"] = 0;
				$sets[$i][$j]["sets_gah"] = 0;
	
				if ($match["detail"] != "") {
					// set BRW, BRL, GAH
					for ($k = $score_count[$j]; $k < $score_count[$j + 1]; $k ++) {
						if ($k < $total_details_count) {
							$set_details = explode(":", $details[$k]);
							if (count($set_details) == 4 && ($set_details[1] == "b" || $set_details[1] == "h")) {
								// by games
								$game = Helper::getGamesPoint($set_details[3]);
								if ((int)$set_details[2] == $home) {
									if ($set_details[1] == "b") {
										// by games
										$sets[$i][$j]["brw"][$game] ++;
										// by sets
										$sets[$i][$j]["sets_brw"] ++;
									} else {
										// by games
										$sets[$i][$j]["gah"][$game] ++;
										// by sets
										$sets[$i][$j]["sets_gah"] ++;
									}
								} else {
									if ($set_details[1] == "b") {
										// by games
										$sets[$i][$j]["brl"][$game] ++;
										// by sets
										$sets[$i][$j]["sets_brl"] ++;
									}
								}

								$score_cnt ++;
							}
						}
					}
				}
				$j ++;
			}
			$i ++;
		}

		// get opponents breaks by games
		$game_breaks = array();
		$game_breaks["brw"] = [0,0,0,0];
		$game_breaks["brl"] = [0,0,0,0];
		$game_breaks["gah"] = [0,0,0,0];
		for ($i = 0; $i < count($sets); $i ++) {
			for ($j = 0; $j < count($sets[$i]); $j ++) {
				for ($k = 0; $k < 4; $k ++) {
					$game_breaks["brw"][$k] += $sets[$i][$j]["brw"][$k];
					$game_breaks["brl"][$k] += $sets[$i][$j]["brl"][$k];
					$game_breaks["gah"][$k] += $sets[$i][$j]["gah"][$k];
				}
			}
		}

		// by sets
		$brw = [0,0,0,0,0];
		$brl = [0,0,0,0,0];
		$gah = [0,0,0,0,0];
		
		for ($i = 0; $i < 5; $i ++) { // sets
			foreach($sets as $match) {
				if (count($match) > $i) {
					$brw[$i] += $match[$i]["sets_brw"];			
					$brl[$i] += $match[$i]["sets_brl"];		
					$gah[$i] += $match[$i]["sets_gah"];
				}
			}
		}

		return [
			"sets" => [
				"brw" 	=> $brw,
				"brl" 	=> $brl,
				"gah" 	=> $gah,
			],
			"games" => $game_breaks,
		];
	}

	/**
	 * 00:00:00 ~ 23:59:59
	 */
  	public static function getTimePeriod($date) {
		if (!$date) {
			$date = date('Y-m-d', time());
		}

    	$times = array();
    	$d = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' 00:00:00');
    	if ($d === false) {
      		die("Incorrect date string");
    	} else {
      		array_push($times, $d->getTimestamp());
    	}

    	$d = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' 23:59:59');
    	if ($d === false) {
      		die("Incorrect date string");
		} else {
      		array_push($times, $d->getTimestamp());
    	}

    	return $times;
  	}

	/**
	 * Import all historical data
	 */
  	public static function importHistoryData($startDate=NULL) {
    	$start = microtime(true);
    	if (!$startDate) {
      		$startDate = "20180101";
    	}
    	$period = new DatePeriod(
			new DateTime($startDate),
			new DateInterval('P1D'),
			date("Ymd")
    	);
    
    	foreach ($period as $key => $value) {
			Helper::updateDB($value->format("Ymd"), true);
		}
    	$execution_time = (microtime(true) - $start) / 60;
    	echo "Total Execution Time:  " . $execution_time. "  Mins";
  	}

	/**
	 * Import the historical data of a specific day
	 */
  	public static function updateDB($date=false, $once=false) {
		$start_time = microtime(true);
		$request_count = 0;
		if (!$date) {
			$date = date("Ymd");
		}
		
		$log = substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2) . ":  Start Time: " . date("Y-m-d H:i:s");
    	// Check Y-m table is exist or not
    	$match_table_name = "t_matches_" . substr($date, 0, 4) . "_" . substr($date, 4, 2);
    	if (!Schema::hasTable($match_table_name)) {
      		// Code to create table
      		Schema::create($match_table_name, function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("player1_id");
				$table->string("player1_name", 100);
				$table->float("player1_odd")->nullable();
				$table->integer("player2_id");
				$table->string("player2_name", 100);
				$table->float("player2_odd")->nullable();
				$table->string("scores", 50)->nullable();
				$table->string("surface", 50)->nullable();
				$table->integer("time_status");
				$table->integer("time");
				$table->text("detail")->nullable();
			});
    	}

		$curl = curl_init();
		$token = env("API_TOKEN", "");

		// Get history data
		$url = "https://api.b365api.com/v2/events/ended?sport_id=13&token=$token&day=$date";
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$history_data = json_decode(curl_exec($curl), true);
		$history = array();
    	if (array_key_exists("results", $history_data)) {
      		$history = $history_data["results"];
    	}
    	// Get total history data
    	$history_total_count = 0;
    	if (count($history) > 0) {
			$total_page = intval($history_data["pager"]["total"] / $history_data["pager"]["per_page"]) + 1;
			$history_total_count = $history_data["pager"]["total"];
      		for ($i = 2; $i <= $total_page; $i ++) {
				$url = "https://api.b365api.com/v2/events/ended?sport_id=13&token=$token&day=$date&page=$i";
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$history_data = json_decode(curl_exec($curl), true);
        		if (array_key_exists("results", $history_data)) {
          			$history = array_merge($history, $history_data["results"]);
        		}   
        		$request_count ++;
      		}
    	}
	  
    	if ($once) {
      		$matches = $history;
    	} else {
			// Get upcoming data
			$url = "https://api.b365api.com/v2/events/upcoming?sport_id=13&token=$token";
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$upcoming_data = json_decode(curl_exec($curl), true);
			$upcoming = array();
      		if (array_key_exists("results", $upcoming_data)) {
        		$upcoming = $upcoming_data["results"];
      		}
      		$request_count ++;
      		// Get total upcoming data
      		if (count($upcoming) > 0) {
        		$total_page = intval($upcoming_data["pager"]["total"] / $upcoming_data["pager"]["per_page"]) + 1;
				for ($i = 2; $i <= $total_page; $i ++) {
          			$url = "https://api.b365api.com/v2/events/upcoming?sport_id=13&token=$token&day=$date&page=$i";
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					$upcoming_data = json_decode(curl_exec($curl), true);
          			if (array_key_exists("results", $upcoming_data)) {
            			$upcoming = array_merge($upcoming, $upcoming_data["results"]);
          			}   
          			$request_count ++;
        		}
      		}
      		$matches = array_merge($history, $upcoming);

			// Get inplay data
			$url = "https://api.b365api.com/v2/events/inplay?sport_id=13&token=$token";
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$inplay_data = json_decode(curl_exec($curl), true);
			$inplay = array();
      		if (array_key_exists("results", $inplay_data)) {
				$inplay = $inplay_data["results"];
      		}
      		$request_count ++;
      		// Get total inplay data
      		if (count($inplay) > 0) {
        		$total_page = intval($inplay_data["pager"]["total"] / $inplay_data["pager"]["per_page"]) + 1;
        		for ($i = 2; $i <= $total_page; $i ++) {
					$url = "https://api.b365api.com/v2/events/inplay?sport_id=13&token=$token&day=$date&page=$i";
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					$inplay_data = json_decode(curl_exec($curl), true);
					if (array_key_exists("results", $inplay_data)) {
						$inplay = array_merge($inplay, $inplay_data["results"]);
					}   
					$request_count ++;
				}

				// Get ranking of Men
				$url = "https://api.b365api.com/v1/tennis/ranking?token=$token&type_id=1";
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$men_data = json_decode(curl_exec($curl), true);
				// Store personal information into t_players table
				foreach ($men_data["results"] as $men) {
					DB::table("t_players")
						->updateOrInsert(
							["api_id" => (int)$men["id"]],
							[
								"name"    => $men["name"],
								"gender"  => "m",
								"ranking" => (int)$men["ranking"],
								"api_id"  => (int)$men["id"],
							]
						);
				}

				// Get ranking of Women
				$url = "https://api.b365api.com/v1/tennis/ranking?token=$token&type_id=3";
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$women_data = json_decode(curl_exec($curl), true);
				// Store personal information into t_players table
				foreach ($women_data["results"] as $women) {
					DB::table("t_players")
						->updateOrInsert(
							["api_id" => $women["id"]],
							[
								"name"    => $women["name"],
								"gender"  => "w",
								"ranking" => $women["ranking"],
								"api_id"  => $women["id"],
							]
						);
				}
      		}
      		$matches = array_merge($matches, $inplay);
    	}

		if (count($matches) > 0) {
		// Get Odds
			foreach ($matches as $event) {
				$event_id = $event["id"];
				// Get Odds
				$url = "https://api.b365api.com/v2/event/odds/summary?token=$token&event_id=$event_id";
				$request_count ++;
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$summary = json_decode(curl_exec($curl), true);
				// Get surface
				$url = "https://api.b365api.com/v1/event/view?token=$token&event_id=$event_id";
				$request_count ++;
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$view = json_decode(curl_exec($curl), true);
				
				if (array_key_exists("home", $event) && (array_key_exists("away", $event) || array_key_exists("o_away", $event))) {
					$time_status = (int)$event["time_status"];
					$player1_id = array_key_exists("o_home", $event) ? (int)$event["o_home"]["id"] : (int)$event["home"]["id"];
					$player1_name = array_key_exists("o_home", $event) ? $event["o_home"]["name"] : $event["home"]["name"];
					$player1_odd = NULL;

					$player2_id = array_key_exists("o_away", $event) ? (int)$event["o_away"]["id"] : (int)$event["away"]["id"];
					$player2_name = array_key_exists("o_away", $event) ? $event["o_away"]["name"] : $event["away"]["name"];
					$player2_odd = NULL;

					if ($summary) {
						if ($time_status == 3) {
							if (array_key_exists("results", $summary)
								&& array_key_exists("Bet365", $summary["results"])
								&& array_key_exists("kickoff", $summary["results"]["Bet365"]["odds"])) {
								if (array_key_exists("13_1", $summary["results"]["Bet365"]["odds"]["kickoff"]) && $summary["results"]["Bet365"]["odds"]["kickoff"]["13_1"] !== NULL) {
									$player1_odd = $summary["results"]["Bet365"]["odds"]["kickoff"]["13_1"]["home_od"];
								} elseif (array_key_exists("13_2", $summary["results"]["Bet365"]["odds"]["kickoff"]) && $summary["results"]["Bet365"]["odds"]["kickoff"]["13_2"] !== NULL) {
									$player1_odd = $summary["results"]["Bet365"]["odds"]["kickoff"]["13_2"]["home_od"];
								} elseif (array_key_exists("13_3", $summary["results"]["Bet365"]["odds"]["kickoff"]) && $summary["results"]["Bet365"]["odds"]["kickoff"]["13_3"] !== NULL) {
									$player1_odd = $summary["results"]["Bet365"]["odds"]["kickoff"]["13_3"]["home_od"];
								}
							}

							if (array_key_exists("results", $summary)
								&& array_key_exists("Bet365", $summary["results"])
								&& array_key_exists("kickoff", $summary["results"]["Bet365"]["odds"])) {
								if (array_key_exists("13_1", $summary["results"]["Bet365"]["odds"]["kickoff"]) && $summary["results"]["Bet365"]["odds"]["kickoff"]["13_1"] !== NULL) {
									$player2_odd = $summary["results"]["Bet365"]["odds"]["kickoff"]["13_1"]["away_od"];
								} elseif (array_key_exists("13_2", $summary["results"]["Bet365"]["odds"]["kickoff"]) && $summary["results"]["Bet365"]["odds"]["kickoff"]["13_2"] !== NULL) {
									$player2_odd = $summary["results"]["Bet365"]["odds"]["kickoff"]["13_1"]["away_od"];
								} elseif (array_key_exists("13_3", $summary["results"]["Bet365"]["odds"]["kickoff"]) && $summary["results"]["Bet365"]["odds"]["kickoff"]["13_3"] !== NULL) {
									$player2_odd = $summary["results"]["Bet365"]["odds"]["kickoff"]["13_3"]["away_od"];
								}
							}
						} else {
							if (array_key_exists("results", $summary)
								&& array_key_exists("Bet365", $summary["results"])
								&& array_key_exists("start", $summary["results"]["Bet365"]["odds"])) {
								if (array_key_exists("13_1", $summary["results"]["Bet365"]["odds"]["start"]) && $summary["results"]["Bet365"]["odds"]["start"]["13_1"] !== NULL) {
									$player1_odd = $summary["results"]["Bet365"]["odds"]["start"]["13_1"]["home_od"];
								} elseif (array_key_exists("13_2", $summary["results"]["Bet365"]["odds"]["start"]) && $summary["results"]["Bet365"]["odds"]["start"]["13_2"] !== NULL) {
									$player1_odd = $summary["results"]["Bet365"]["odds"]["start"]["13_2"]["home_od"];
								} elseif (array_key_exists("13_3", $summary["results"]["Bet365"]["odds"]["start"]) && $summary["results"]["Bet365"]["odds"]["start"]["13_3"] !== NULL) {
									$player1_odd = $summary["results"]["Bet365"]["odds"]["start"]["13_3"]["home_od"];
								}
							}

							if (array_key_exists("results", $summary)
								&& array_key_exists("Bet365", $summary["results"])
								&& array_key_exists("start", $summary["results"]["Bet365"]["odds"])) {
								if (array_key_exists("13_1", $summary["results"]["Bet365"]["odds"]["start"]) && $summary["results"]["Bet365"]["odds"]["start"]["13_1"] !== NULL) {
									$player2_odd = $summary["results"]["Bet365"]["odds"]["start"]["13_1"]["away_od"];
								} elseif (array_key_exists("13_2", $summary["results"]["Bet365"]["odds"]["start"]) && $summary["results"]["Bet365"]["odds"]["start"]["13_2"] !== NULL) {
									$player2_odd = $summary["results"]["Bet365"]["odds"]["start"]["13_1"]["away_od"];
								} elseif (array_key_exists("13_3", $summary["results"]["Bet365"]["odds"]["start"]) && $summary["results"]["Bet365"]["odds"]["start"]["13_3"] !== NULL) {
									$player2_odd = $summary["results"]["Bet365"]["odds"]["start"]["13_3"]["away_od"];
								}
							}
						}
					}
					
					$surface = $view && array_key_exists("results", $view)
								&& array_key_exists("extra", $view["results"][0])
								&& array_key_exists("ground", $view["results"][0]["extra"])
									? $view["results"][0]["extra"]["ground"]
									: NULL;

					$detail = "";
					if ($view && array_key_exists("results", $view) && array_key_exists("events", $view["results"][0])) {
						$event_cnt = 0;
						foreach ($view["results"][0]["events"] as $game) {
							if (strpos($game["text"], "Game") !== false) {
								$splitedText = explode(" - ", $game["text"]);
								if (count($splitedText) > 2) {
									$games = explode(" ", $splitedText[0]);

									$event_cnt = (int)$games[1];
									$detail .= $games[1];
									if (strpos($game["text"], "hold") !== false) {
										$detail .= ":h:";
									} elseif (strpos($game["text"], "breaks") !== false) {
										$detail .= ":b:";
									}

									$name = $splitedText[1];
									if ($name == $player1_name) {
										$detail .= "1:";
									} elseif ($name == $player2_name) {
										$detail .= "2:";
									}
									$words = explode(" ", $game["text"]);
									$score = $words[count($words) - 1];
									$detail .= $score;
									$detail .= ",";
								}
							} else if (strpos($game["text"], "tie break") !== false) {
								$event_cnt ++;
								$words = explode(" ", $game["text"]);
								$detail .= $event_cnt;
								$detail .= ":t:";
								$detail .= $words[count($words) - 1];
								$detail .= ",";
							}
						}
					}
					if ($detail != "") {
						$detail = substr($detail, 0, -1);
					}
					$update_or_insert_array = [
						"event_id"      => (int)$event_id,
						"player1_id"    => $player1_id,
						"player1_name"  => $player1_name,
						"player1_odd"   => $player1_odd,
						"player2_id"    => $player2_id,
						"player2_name"  => $player2_name,
						"player2_odd"   => $player2_odd,
						"scores"        => $event["ss"] ? trim($event["ss"]) : "",
						"surface"       => $surface,
						"time_status"   => $time_status,
						"time"          => (int)$event["time"],
						"detail"		=> $detail
					];
					// Update or Insert into t_matches table
					DB::table($match_table_name)
						->updateOrInsert(
							["event_id" => (int)$event_id],
							$update_or_insert_array
						);
				}
			}
		}
		$execution_time = microtime(true) - $start_time;
		$log .= ("  End Time: " . date("Y-m-d H:i:s"));
		$log .= ("  ===>  Total History Count: " . $history_total_count . ", Execution Time:  " . $execution_time. " secs, Request Count: " . $request_count . "\n");
		echo $log;
		file_put_contents("log.txt", $log, FILE_APPEND | LOCK_EX);
		curl_close($curl);
	}
}