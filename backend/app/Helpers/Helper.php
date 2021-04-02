<?php

namespace App\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use DateTime;
use DatePeriod;
use DateInterval;

class Helper {
	public static function getGamesPoint($game) {
		if ($game == "love" || (int)$game == 10 || (int)$game == 0) {
			return 0;
		} else if ((int)$game == 15) {
			return 1;
		} else if ((int)$game == 30) {
			return 2;
		} else if ((int)$game == 40) {
			return 3;
		}
		return -1;
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
			$empty_array = array_fill(0, 5-count($set_scores), "0-0");
			$set_scores = array_merge($set_scores, $empty_array);

			if ($match["player1_id"] == $player_id) {
				$home = 1;
			} else {
				$home = 2;
			}
			$j = 0;
			$score_count = array();
			$score_count[$j] = 0;

			foreach ($set_scores as $set_score) {
				$ww = 0; // (2:0)  => (6:4)
				$wl = 0; // (2:0)  => (4:6)
				$lw = 0; // (0:2)  => (6:4)
				$ll = 0; // (0:2)  => (4:6)

				$player_scores = array();
				$opponent_scores = array();
				$player_scores[0] = 0;
				$opponent_scores[0] = 0;

				$score_cnt = 0;

				$sets[$i]["sets"][$j]["brw"] = [0,0,0,0]; // 10, 15, 30, 40
				$sets[$i]["sets"][$j]["brl"] = [0,0,0,0];
				$sets[$i]["sets"][$j]["gah"] = [0,0,0,0];
				
				$sets[$i]["sets"][$j]["depth"] = 0;
				$sets[$i]["sets"][$j]["performance"] = [
					"ww" => 0,
					"wl" => 0,
					"lw" => 0,
					"ll" => 0,
				];
				$score_count[$j] = array_key_exists($j, $score_count) ? $score_count[$j] : 0;
				$scores = explode("-", $set_score);
				$score_0 = array_key_exists(0, $scores) ? (int)$scores[0] : 0;
				$score_1 = array_key_exists(1, $scores) ? (int)$scores[1] : 0;
				if (!($score_0 == 0 && $score_1 == 0)) {
					$score_count[$j + 1] = $score_count[$j] + $score_0 + $score_1;

					if ($home == 1) {
						$player_score = $score_0;
						$opponent_score = $score_1;
					} else {
						$player_score = $score_1;
						$opponent_score = $score_0;
					}

					if ($match["detail"] != "") {
						// set BRW, BRL, GAH
						for ($k = $score_count[$j]; $k < $score_count[$j + 1]; $k ++) {
							if ($k < $total_details_count) {
								$set_details = explode(":", $details[$k]);
								if (count($set_details) == 4 && ($set_details[1] == "b" || $set_details[1] == "h")) {
									$game = Helper::getGamesPoint($set_details[3]);
									if ($game != -1) {
										if ((int)$set_details[2] == $home) {
											if ($set_details[1] == "b") {
												$sets[$i]["sets"][$j]["brw"][$game] ++;
											} else {
												$sets[$i]["sets"][$j]["gah"][$game] ++;
											}
											// add score for depth
											$player_scores[$score_cnt + 1] = $player_scores[$score_cnt] + 1;
											$opponent_scores[$score_cnt + 1] = $opponent_scores[$score_cnt];
										} else {
											if ($set_details[1] == "b") {
												$sets[$i]["sets"][$j]["brl"][$game] ++;
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
					}

					// calculate the depth
					$depths = array();
					$depths[0] = 0;
					for ($n = 1; $n < $score_cnt + 1; $n ++) {
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
								$ww = 1;
							} else {
								$wl = 1;
							}
							break;
						} else if ($game_depth == -2) {
							if ($player_score > $opponent_score) {
								$lw = 1;
							} else {
								$ll = 1;
							}
							break;
						}
					}
					if ($ww == 0 && $wl == 0 && $lw == 0 && $ll == 0) {
						if ($player_score > $opponent_score) {
							$ww = 1;
						} else {
							$ll = 1;
						}
					}
					$sets[$i]["sets"][$j]["performance"] = [
						"ww" => $ww,
						"wl" => $wl,
						"lw" => $lw,
						"ll" => $ll,
					];
					$ww = 0;
					$wl = 0;
					$lw = 0;
					$ll = 0;
				}
				$j ++;
			}
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
			if ($match["detail"] != "" && $match["scores"] != "" && $match["scores"] != "0-0" && $match["scores"] != "0-0,") {
				$sets[$i] = $match;
				$details = Helper::getCorrectDetail($match["detail"]);
				$total_details_count = count($details);
	
				$set_scores = explode(",", $match["scores"]);
				$empty_array = array_fill(0, 5-count($set_scores), "0-0");
				$set_scores = array_merge($set_scores, $empty_array);
	
				if ($match["player1_id"] == $player_id) {
					$home = 1;
				} else {
					$home = 2;
				}
				$j = 0;
				$score_count = array();
				$score_count[$j] = 0;
	
				foreach ($set_scores as $set_score) {
					$player_scores = array();
					$opponent_scores = array();
					$player_scores[0] = 0;
					$opponent_scores[0] = 0;
	
					$sets[$i]["brw"][$j] = [0,0,0,0]; // 10, 15, 30, 40
					$sets[$i]["brl"][$j] = [0,0,0,0];
					$sets[$i]["gah"][$j] = [0,0,0,0];
					
					$score_count[$j] = array_key_exists($j, $score_count) ? $score_count[$j] : 0;
					$scores = explode("-", $set_score);
					$score_0 = array_key_exists(0, $scores) ? (int)$scores[0] : 0;
					$score_1 = array_key_exists(1, $scores) ? (int)$scores[1] : 0;
					if (!($score_0 == 0 && $score_1 == 0)) {
						$score_count[$j + 1] = $score_count[$j] + $score_0 + $score_1;
	
						if ($home == 1) {
							$player_score = $score_0;
							$opponent_score = $score_1;
						} else {
							$player_score = $score_1;
							$opponent_score = $score_0;
						}
	
						// set BRW, BRL, GAH
						for ($k = $score_count[$j]; $k < $score_count[$j + 1]; $k ++) {
							if ($k < $total_details_count) {
								$set_details = explode(":", $details[$k]);
								if (count($set_details) == 4 && ($set_details[1] == "b" || $set_details[1] == "h")) {
									$game = Helper::getGamesPoint($set_details[3]);
									if ($game != -1) {
										if ((int)$set_details[2] == $home) {
											if ($set_details[1] == "b") {
												$sets[$i]["brw"][$j][$game] ++;
											} else {
												$sets[$i]["gah"][$j][$game] ++;
											}
										} else {
											if ($set_details[1] == "b") {
												$sets[$i]["brl"][$j][$game] ++;
											}
										}
									}
								}
							}
						}
					}
					$j ++;
				}
				$i ++;
			}
		}
		return $sets;
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
      		$startDate = "20210324";
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

	public static function getMatchesResponse($matches_data_array, $players) {
        $matches = array();
        $i = 0;
        foreach ($matches_data_array as $matches_data) {
            foreach ($matches_data as $data) {
                $matches[$i] = $data;
                switch (trim($data->surface)) {
                    case "Clay":
                        $surface = "CLY";
                        break;
                    case "Hardcourt outdoor":
                        $surface = "HRD";
                        break;
                    case "Hardcourt indoor" || "Carpet indoor":
                        $surface = "IND";
                        break;
                    case "Grass":
                        $surface = "GRS";
                        break;
                    default:
                        $surface = $data->surface;
                        break;
                }
    
                $matches[$i] = [
                    'id'                =>  $data->id,
                    'event_id'          =>  $data->event_id,
                    'player1_id'        =>  $data->player1_id,
                    'player1_name'      =>  trim($data->player1_name),
                    'player1_odd'       =>  $data->player1_odd,
                    'player1_ranking'   =>  "-",
                    'player2_id'        =>  $data->player2_id,
                    'player2_name'      =>  trim($data->player2_name),
                    'player2_odd'       =>  $data->player2_odd,
                    'player2_ranking'   =>  "-",
                    'surface'           =>  $surface,
                    'scores'            =>  trim($data->scores),
                    'time_status'       =>  $data->time_status,
                    'time'              =>  $data->time,
                    'detail'            =>  $data->detail
                ];
    
                foreach ($players as $player) {
                    if ($data->player1_id == $player->api_id) {
                        $matches[$i]["player1_name"] = $player->name;
                        $matches[$i]["player1_ranking"] = (int)$player->ranking;
                    }
    
                    if ($data->player2_id == $player->api_id) {
                        $matches[$i]["player2_name"] = $player->name;
                        $matches[$i]["player2_ranking"] = (int)$player->ranking;
                    }
                }
                $i ++;
            }
        }
        return $matches;
    }

	/**
	 * Pre calculation for Upcoming & Inplay
	 */

	public static function preCalculation() {
		$start_time = microtime(true);
		$log = "Start: " . date("Y-m-d H:i:s");
		$date = date("Ymd");
		$bucket_tables = DB::table("pg_catalog.pg_tables")
					->where("schemaname", "public")
					->where("tablename", "like", "t_bucket_%")
					->get();
		$bucket_table_names = array();
		foreach($bucket_tables as $bucket_table) {
			array_push($bucket_table_names, $bucket_table->tablename);
		}

		$match_table_name = "t_matches_" . substr($date, 0, 4) . "_" . substr($date, 4, 2);
		if (Schema::hasTable($match_table_name)) {
			$upcomingInplayData = DB::table($match_table_name)
									->whereIn("time_status", [0, 1])
									->get();
			$player_ids = array();
			$enable_names = array();
			$i = 0;
			foreach ($upcomingInplayData as $data) {
				$enable_name = "t_bucket_players_" . $data->player1_id . "_" . $data->player2_id;
				if (!(in_array($enable_names, $bucket_table_names))) {
					$player_ids[$i]["player1_id"] = $data->player1_id;
					$player_ids[$i]["player2_id"] = $data->player2_id;
					$i ++;
				}
				array_push($enable_names, $enable_name);
			}
			// drop the old pre-calculated tables
			$old_bucket_tables = array_diff($bucket_table_names, $enable_names);
			foreach ($old_bucket_tables as $bucket_table) {
				Schema::dropIfExists($bucket_table);
			}
	
			$history_tables = DB::table("pg_catalog.pg_tables")
					->where("schemaname", "public")
					->where("tablename", "like", "t_matches_%")
					->get();
			
			$players = DB::table("t_players")->get();
	
			foreach ($player_ids as $ids) {
				// check pre-calculation table is exist or not
				$player1_id = $ids["player1_id"];
				$player2_id = $ids["player2_id"];
				$bucket_players_table = "t_bucket_players_" . $player1_id . "_" . $player2_id;
				if (!Schema::hasTable($bucket_players_table)) {
					// Code to create table
					Schema::create($bucket_players_table, function($table) {
						$table->increments("id");
						$table->integer("event_id");
						$table->integer("p_id");
						$table->string("p_name", 100);
						$table->float("p_odd")->nullable();
						$table->integer("p_ranking")->nullable();
						$table->json("p_brw");
						$table->json("p_brl");
						$table->json("p_gah");
						$table->json("p_depths");
						$table->json("p_ww");
						$table->json("p_wl");
						$table->json("p_lw");
						$table->json("p_ll");
						$table->integer("o_id");
						$table->string("o_name", 100);
						$table->float("o_odd")->nullable();
						$table->integer("o_ranking")->nullable();
						$table->string("scores", 50)->nullable();
						$table->string("surface", 50)->nullable();
						$table->integer("time");
						$table->text("detail")->nullable();
					});
				}

				// $bucket_opponents_table = "t_bucket_opponents_" . $player1_id . "_" . $player2_id;
				// if (!Schema::hasTable($bucket_opponents_table)) {
				// 	// Code to create table
				// 	Schema::create($bucket_opponents_table, function($table) {
				// 		$table->increments("id");
				// 		$table->integer("event_id");
				// 		$table->integer("o_id");
				// 		$table->string("o_name", 100);
				// 		$table->integer("o_ranking")->nullable();
				// 		$table->float("o_odd")->nullable();
				// 		$table->json("o_brw_set");
				// 		$table->json("o_brl_set");
				// 		$table->json("o_gah_set");
				// 		$table->string("surface", 100)->nullable();
				// 		$table->integer("time");
				// 		$table->integer("p_id"); // not oo_id [player1_id or player2_id]
				// 	});
				// }
				Helper::preCalculate($bucket_players_table, $player1_id, $player2_id, $history_tables, $players);
			}
		}

		$execution_time = microtime(true) - $start_time;
		$log .= ("  End: " . date("Y-m-d H:i:s"));
		$log .= "  ExecutionTime: " . $execution_time . "\n";
		echo $log;
	}

	/**
     * Get opponent BRW, BRL, GAH
     */
    public static function setOpponentsDetail($bucket_opponents_table, $opponent_info, $history_tables, $player_id) {
		// check opponent id is exist in t_bucket_opponents table
		$o_id = $opponent_info["o_id"];
		$matches_data_array = array();
		foreach ($history_tables as $table) {
			// filtering by opponent ids
			$matches_data = DB::table($table->tablename)->where('time_status', 3)
										->where(function($query) use ($o_id) {
											$query->where('player1_id', $o_id)
											->orWhere('player2_id', $o_id);
										})->get();

			$match_array = json_decode(json_encode($matches_data), true);
			$matches_data_array = array_merge($matches_data_array, $match_array);
		}
		
		$opponents_details = Helper::getSetsOpponents($matches_data_array, $o_id);
		foreach ($opponents_details as $data) {
			switch (trim($data["surface"])) {
				case "Clay":
					$surface = "CLY";
					break;
				case "Hardcourt outdoor":
					$surface = "HRD";
					break;
				case "Hardcourt indoor" || "Carpet indoor":
					$surface = "IND";
					break;
				case "Grass":
					$surface = "GRS";
					break;
				default:
					$surface = $data["surface"];
					break;
			}
			$db_data = [
				"event_id" 		=> $data["event_id"],
				"o_id" 			=> $o_id,
				"o_name" 		=> $opponent_info["o_name"],
				"o_odd" 		=> $data["player1_id"] == $o_id ? $data["player2_odd"] : $data["player1_odd"],
				"o_ranking" 	=> $opponent_info["o_ranking"] == "-" ? NULL : $opponent_info["o_ranking"],
				"o_brw_set" 	=> json_encode($data["brw"]),
				"o_brl_set" 	=> json_encode($data["brl"]),
				"o_gah_set" 	=> json_encode($data["gah"]),
				"surface" 		=> $surface,
				"time" 			=> $data["time"],
				"p_id" 			=> $player_id,
			];
			DB::table($bucket_opponents_table)
				->updateOrInsert(
					["event_id" => $data["event_id"]],
					$db_data
				);
		}
    }

	/**
	 * Pre calculate matches for player1_id and player2 id
	 */
	public static function preCalculate($bucket_players_table, $player1_id, $player2_id, $history_tables, $players) {
		$matches1_array = array();
        $matches2_array = array();
        foreach ($history_tables as $table) {
            // filtering by player ids (player1)
            $match_table_subquery_1 = DB::table($table->tablename)->where('time_status', 3)
                                        ->where(function($query) use ($player1_id) {
                                            $query->where('player1_id', $player1_id)
                                            ->orWhere('player2_id', $player1_id);
                                        });
            // filtering by player ids (player2)
            $match_table_subquery_2 = DB::table($table->tablename)->where('time_status', 3)
                                        ->where(function($query) use ($player2_id) {
                                            $query->where('player1_id', $player2_id)
                                            ->orWhere('player2_id', $player2_id);
                                        });
            
            array_push($matches1_array, $match_table_subquery_1->get());
            array_push($matches2_array, $match_table_subquery_2->get());
        }

		$matches1 = Helper::getMatchesResponse($matches1_array, $players);
		$matches2 = Helper::getMatchesResponse($matches2_array, $players);

		// add sets
        $matches1_set = Helper::getSetsPerformanceDetail($matches1, $player1_id);
        $matches2_set = Helper::getSetsPerformanceDetail($matches2, $player2_id);

		// add breaks to the players array
        $matches_1 = array();
        $matches_2 = array();

        foreach ($matches1_set as $data) {
			if ($data["scores"] != "" && $data["scores"] != "0-0" && $data["scores"] != "0-0,") {
				$brw = array();
				$brl = array();
				$gah = array();
				$depths = array();
				$ww = array();
				$wl = array();
				$lw = array();
				$ll = array();
				// by sets
				for ($i = 0; $i < 5; $i ++) {
					if (count($data["sets"]) > $i) {
						array_push($brw, $data["sets"][$i]["brw"]);
						array_push($brl, $data["sets"][$i]["brl"]);
						array_push($gah, $data["sets"][$i]["gah"]);
						array_push($depths, $data["sets"][$i]["depth"]);
						array_push($ww, $data["sets"][$i]["performance"]["ww"]);
						array_push($wl, $data["sets"][$i]["performance"]["wl"]);
						array_push($lw, $data["sets"][$i]["performance"]["lw"]);
						array_push($ll, $data["sets"][$i]["performance"]["ll"]);
					}
				}
				// insert $data to t_buckets table
				$p_ranking = $data["player1_id"] == $player1_id ? $data["player1_ranking"] : $data["player2_ranking"];
				$p_ranking = $p_ranking == "-" ? NULL : $p_ranking;
				$player_info = [
					"p_id" =>  $player1_id,
					"p_name" => $data["player1_id"] == $player1_id ? $data["player1_name"] : $data["player2_name"],
					"p_odd" => $data["player1_id"] == $player1_id ? $data["player1_odd"] : $data["player2_odd"],
					"p_ranking" => $p_ranking,
				];
				$opponent_info = [
					"o_id" => $data["player1_id"] == $player1_id ? $data["player2_id"] : $data["player1_id"],
					"o_name" => $data["player1_id"] == $player1_id ? $data["player2_name"] : $data["player1_name"],
					"o_odd" => $data["player1_id"] == $player1_id ? $data["player2_odd"] : $data["player1_odd"],
					"o_ranking" => $data["player1_id"] == $player1_id ? $data["player2_ranking"] : $data["player1_ranking"],
					"surface" => $data["surface"],
					"event_id" => $data["event_id"],
				];
				$db_data = [
					"event_id"  => $data["event_id"],
					"p_id"    	=> $player_info["p_id"],
					"p_name"  	=> $player_info["p_name"],
					"p_odd"   	=> $player_info["p_odd"],
					"p_ranking" => $player_info["p_ranking"],
					"p_brw"		=> json_encode($brw),
					"p_brl"		=> json_encode($brl),
					"p_gah"		=> json_encode($gah),
					"p_depths"	=> json_encode($depths),
					"p_ww"		=> json_encode($ww),
					"p_wl"		=> json_encode($wl),
					"p_lw"		=> json_encode($lw),
					"p_ll"		=> json_encode($ll),
					"scores"    => $data["scores"],
					"surface"   => $data["surface"],
					"time"      => $data["time"],
					"detail"	=> $data["detail"],
					"o_id"		=> $opponent_info["o_id"],
					"o_name"	=> $opponent_info["o_name"],
					"o_odd"		=> $opponent_info["o_odd"],
					"o_ranking"	=> $opponent_info["o_ranking"] == "-" ? NULL : $opponent_info["o_ranking"],
				];
				DB::table($bucket_players_table)
					->updateOrInsert(
						["event_id" => $data["event_id"]],
						$db_data
					);
				// Helper::setOpponentsDetail($bucket_opponents_table, $opponent_info, $history_tables, $player1_id);
			}
		}

        foreach ($matches2_set as $data) {
			if ($data["scores"] != "" && $data["scores"] != "0-0" && $data["scores"] != "0-0,") {
				$brw = array();
				$brl = array();
				$gah = array();
				$depths = array();
				$ww = array();
				$wl = array();
				$lw = array();
				$ll = array();
				// by sets
				for ($i = 0; $i < 5; $i ++) {
					if (count($data["sets"]) > $i) {
						array_push($brw, $data["sets"][$i]["brw"]);
						array_push($brl, $data["sets"][$i]["brl"]);
						array_push($gah, $data["sets"][$i]["gah"]);
						array_push($depths, $data["sets"][$i]["depth"]);
						array_push($ww, $data["sets"][$i]["performance"]["ww"]);
						array_push($wl, $data["sets"][$i]["performance"]["wl"]);
						array_push($lw, $data["sets"][$i]["performance"]["lw"]);
						array_push($ll, $data["sets"][$i]["performance"]["ll"]);
					}
				}
				// insert $data to t_buckets table
				$p_ranking = $data["player1_id"] == $player2_id ? $data["player1_ranking"] : $data["player2_ranking"];
				$p_ranking = $p_ranking == "-" ? NULL : $p_ranking;
				$player_info = [
					"p_id" =>  $player2_id,
					"p_name" => $data["player1_id"] == $player2_id ? $data["player1_name"] : $data["player2_name"],
					"p_odd" => $data["player1_id"] == $player2_id ? $data["player1_odd"] : $data["player2_odd"],
					"p_ranking" => $p_ranking,
				];
				$opponent_info = [
					"o_id" => $data["player1_id"] == $player2_id ? $data["player2_id"] : $data["player1_id"],
					"o_name" => $data["player1_id"] == $player2_id ? $data["player2_name"] : $data["player1_name"],
					"o_odd" => $data["player1_id"] == $player2_id ? $data["player2_odd"] : $data["player1_odd"],
					"o_ranking" => $data["player1_id"] == $player2_id ? $data["player2_ranking"] : $data["player1_ranking"],
					"surface" => $data["surface"],
					"event_id" => $data["event_id"],
				];
				
				$db_data = [
					"event_id"  => $data["event_id"],
					"p_id"    	=> $player_info["p_id"],
					"p_name"  	=> $player_info["p_name"],
					"p_odd"   	=> $player_info["p_odd"],
					"p_ranking" => $player_info["p_ranking"],
					"p_brw"		=> json_encode($brw),
					"p_brl"		=> json_encode($brl),
					"p_gah"		=> json_encode($gah),
					"p_depths"	=> json_encode($depths),
					"p_ww"		=> json_encode($ww),
					"p_wl"		=> json_encode($wl),
					"p_lw"		=> json_encode($lw),
					"p_ll"		=> json_encode($ll),
					"scores"    => $data["scores"],
					"surface"   => $data["surface"],
					"time"      => $data["time"],
					"detail"	=> $data["detail"],
					"o_id"		=> $opponent_info["o_id"],
					"o_name"	=> $opponent_info["o_name"],
					"o_odd"		=> $opponent_info["o_odd"],
					"o_ranking"	=> $opponent_info["o_ranking"] == "-" ? NULL : $opponent_info["o_ranking"],
				];
				DB::table($bucket_players_table)
					->updateOrInsert(
						["event_id" => $data["event_id"]],
						$db_data
					);
				// Helper::setOpponentsDetail($bucket_opponents_table, $opponent_info, $history_tables, $player2_id);
			}
        }
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

		if ($date > date("Ymd")) {
			return;
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
				if ($men_data != null) {
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
				}

				// Get ranking of Women
				$url = "https://api.b365api.com/v1/tennis/ranking?token=$token&type_id=3";
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$women_data = json_decode(curl_exec($curl), true);
				// Store personal information into t_players table
				if ($women_data != null) {
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
