<?php

namespace App\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use DateTime;
use DatePeriod;
use DateInterval;

class Helper {
	/**
	 * Get index of game points
	 * @param 	string 	$game
	 * @return 	int 	$index
	 */
	public static function getGamesPoint($game) {
		if ($game == "love" || (int)$game == 0) {
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

	/**
	 * Import all historical data
	 * @param string $start_date
	 */
	public static function importHistoryData($start_date=NULL) {
    	$start = microtime(true);
    	if (!$start_date) {
      		$start_date = "20161112";
    	}
    	$period = new DatePeriod(
			new DateTime($start_date),
			new DateInterval('P1D'),
			date("Ymd")
    	);
    
    	foreach ($period as $key => $value) {
			Helper::updateDB($value->format("Ymd"), true, 3);
		}
    	$execution_time = (microtime(true) - $start) / 60;
    	echo "Total Execution Time:  " . $execution_time. "  Mins";
  	}

	/**
	 * Make the correct detail including tie-break
	 * @param 	string $detail
	 * @return 	string $new_detail
	 */
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
	 * Get opponent sub detail (oo_ranking for RAW, RAL)
	 * @param int $o_id
	 */
	public static function getOpponentSubDetail($o_id, $history_tables, $players) {
		$opponent_data = [];
		$matches_array = array();
        foreach ($history_tables as $table) {
            // filtering by player ids (player1)
            $match_table_subquery = DB::table($table->tablename)
										->where('scores', '<>', NULL)
										->where('scores', '<>', '')
                                        ->where(function($query) use ($o_id) {
                                            $query->where('player1_id', $o_id)
                                            ->orWhere('player2_id', $o_id);
                                        });
            
            array_push($matches_array, $match_table_subquery->get());
        }
		$matches = Helper::getMatchesResponse($matches_array, $players, 3);
		$event_ids = array();
		foreach ($matches as $match) {
			if (!in_array($match["event_id"], $event_ids)) {
				array_push($event_ids, $match["event_id"]);
				$set_scores = explode(",", $match["scores"]);
				$set_count = count($set_scores);
				$empty_array = array_fill(0, 5-count($set_scores), "0-0");
				$set_scores = array_merge($set_scores, $empty_array);

				if ($set_count > 0) {
					$score = explode("-", $set_scores[0]);
					if (count($score) == 2) {
						$score_1 = (int)$score[0];
						$score_2 = (int)$score[1];
						if ($match["player1_id"] == $o_id) {
							$home = 1;
							$oo_id = $match["player2_id"];
							$o_ranking = $match["player1_ranking"] == "-" ? 501 : $match["player1_ranking"];
							$oo_ranking = $match["player2_ranking"] == "-" ? 501 : $match["player2_ranking"];
							if ($score_1 > $score_2) {
								$won = 1;
							} else {
								$won = 2;
							}
						} else {
							$home = 2;
							$oo_id = $match["player1_id"];
							$o_ranking = $match["player2_ranking"] == "-" ? 501 : $match["player2_ranking"];
							$oo_ranking = $match["player1_ranking"] == "-" ? 501 : $match["player1_ranking"];
							if ($score_1 > $score_2) {
								$won = 2;
							} else {
								$won = 1;
							}
						}

						$depths = [0,0,0,0,0];
						if ($match["detail"] != "") {
							$j = 0;
							$score_count = array();
							$score_count[$j] = 0;
							$details = Helper::getCorrectDetail($match["detail"]);
							$total_details_count = count($details);
							
							foreach ($set_scores as $set_score) {
								$m = 0; // won count index
								$won_counts = array();
								$won_counts[$m] = 0;

								$score_count[$j] = array_key_exists($j, $score_count) ? $score_count[$j] : 0;
								$scores = explode("-", $set_score);
								$score_0 = array_key_exists(0, $scores) ? (int)$scores[0] : 0;
								$score_1 = array_key_exists(1, $scores) ? (int)$scores[1] : 0;

								if (!($score_0 == 0 && $score_1 == 0)) {
									$score_count[$j + 1] = $score_count[$j] + $score_0 + $score_1;
									for ($k = $score_count[$j]; $k < $score_count[$j + 1]; $k ++) {
										if ($k < $total_details_count) {
											$set_details = explode(":", $details[$k]);
											if (count($set_details) == 4 && ($set_details[1] == "b" || $set_details[1] == "h")) {
												$game = Helper::getGamesPoint($set_details[3]);
												if ($game != -1) {
													if ((int)$set_details[2] == $home) {
														// add won counts of the set
														$won_counts[$m] ++;
													} else {
														// add index of won counts array
														$m ++;
														$won_counts[$m] = 0;
													}
												}
											}
										}
									}
									$depths[$j] = max($won_counts);
								}
								$j ++;
							}
						}
			
						$db_data = [
							"event_id"  	=> $match["event_id"],
							"o_id"    		=> $o_id,
							"o_ranking"		=> $o_ranking,
							"oo_id"    		=> $oo_id,
							"oo_ranking"	=> $oo_ranking,
							"surface"		=> $match["surface"],
							"sets"			=> $set_count,
							"time"			=> $match["time"],
							"won"			=> $won,
							"depths"		=> json_encode($depths),
						];
						array_push($opponent_data, $db_data);
					}
				}
			}
		}
		return $opponent_data;
	}

	/**
	 * Get sub details for player
	 * @param array $matches
	 * @param int $player_id
	 */
	public static function getPlayersSubDetail($matches, $player_id) {
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
				$sets[$i]["home"] = "p";
			} else {
				$home = 2;
				$sets[$i]["home"] = "o";
			}
			$j = 0;
			$score_count = array();
			$score_count[$j] = 0;

			foreach ($set_scores as $set_score) {
				$m = 0; // won count index
				$ww = 0; // (2:0)  => (6:4)
				$wl = 0; // (2:0)  => (4:6)
				$lw = 0; // (0:2)  => (6:4)
				$ll = 0; // (0:2)  => (4:6)

				$won_counts = array();
				$won_counts[$m] = 0;
				$player_scores = array();
				$opponent_scores = array();
				$player_scores[0] = 0;
				$opponent_scores[0] = 0;

				$score_cnt = 0;

				$sets[$i]["sets"][$j]["brw"] = [0,0,0,0]; // love, 15, 30, 40
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
											// add won counts of the set
											$won_counts[$m] ++;
											// add score for depth
											$player_scores[$score_cnt + 1] = $player_scores[$score_cnt] + 1;
											$opponent_scores[$score_cnt + 1] = $opponent_scores[$score_cnt];
										} else {
											if ($set_details[1] == "b") {
												$sets[$i]["sets"][$j]["brl"][$game] ++;
											}
											// add index of won counts array
											$m ++;
											$won_counts[$m] = 0;
											// add score for depth
											$opponent_scores[$score_cnt + 1] = $opponent_scores[$score_cnt] + 1;
											$player_scores[$score_cnt + 1] = $player_scores[$score_cnt];
										}
										$score_cnt ++;
									}
								}
							}
						}

						
						$sets[$i]["sets"][$j]["depth"] = max($won_counts);

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
				}
				$j ++;
			}
			$i ++;
		}
		return $sets;
	}

	/**
	 * Get time period (human date -> timestamp) 00:00:00 ~ 23:59:59
	 * @param 	string 	$date
	 * @return 	array 	$times
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
	 * Drop the pre calculation tables 7 days ago
	 */
	public static function dropOldPreCalculationTables() {
		$n7_daysAgo = date('Y-m-d', strtotime('-7 days', time()));
		$times = Helper::getTimePeriod($n7_daysAgo);

        $pre_calculation_table = "t_pre_calculation_history";
		$pre_calculation_history_data = DB::table($pre_calculation_table)
											->where("time", "<", $times[1])
											->get();

		foreach ($pre_calculation_history_data as $data) {
			$bucket_players_table = "t_bucket_players_" . $data->player_ids;
			$bucket_opponents_table = "t_bucket_opponents_" . $data->player_ids;
			Schema::dropIfExists($bucket_players_table);
			Schema::dropIfExists($bucket_opponents_table);
		}
        DB::table($pre_calculation_table)
            ->where("time", "<", $times[1])
            ->delete();
	}
	
	/**
	 * Get matches (add some fields)
	 * @param 	array $matches_data_array
	 * @param 	array $players
	 * @return 	array $matches
	 */
	public static function getMatchesResponse($matches_data_array, $players, $match_type=-1) {
        $matches = array();
        $i = 0;
        foreach ($matches_data_array as $matches_data) {
            foreach ($matches_data as $data) {
                if (gettype($data) == "object") {
                    $data_id = $data->id;
                    $data_surface = $data->surface;
                    $data_event_id = $data->event_id;
                    $data_player1_id = $data->player1_id;
                    $data_player1_name = $data->player1_name;
                    $data_player1_odd = $data->player1_odd;
                    $data_player2_id = $data->player2_id;
                    $data_player2_name = $data->player2_name;
                    $data_player2_odd = $data->player2_odd;
                    $data_scores = $data->scores;
                    $data_time_status = $data->time_status;
                    $data_time = $data->time;
                    $data_detail = $data->detail;
					$data_league_name = $data->league_name;
                } else {
                    $data_id = $data["id"];
                    $data_surface = $data["surface"];
                    $data_event_id = $data["event_id"];
                    $data_player1_id = $data["player1_id"];
                    $data_player1_name = $data["player1_name"];
                    $data_player1_odd = $data["player1_odd"];
                    $data_player2_id = $data["player2_id"];
                    $data_player2_name = $data["player2_name"];
                    $data_player2_odd = $data["player2_odd"];
                    $data_scores = $data["scores"];
                    $data_time_status = $data["time_status"];
                    $data_time = $data["time"];
                    $data_detail = $data["detail"];
					$data_league_name = $data["league_name"];
                }
                switch (trim($data_surface)) {
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
                        $surface = $data_surface;
                        break;
                }
    
                $matches[$i] = [
                    'id'                =>  $data_id,
                    'event_id'          =>  $data_event_id,
                    'league_name'       =>  $data_league_name,
                    'player1_id'        =>  $data_player1_id,
                    'player1_name'      =>  $data_player1_name,
                    'player1_odd'       =>  $data_player1_odd,
                    'player1_ranking'   =>  "-",
                    'player2_id'        =>  $data_player2_id,
                    'player2_name'      =>  $data_player2_name,
                    'player2_odd'       =>  $data_player2_odd,
                    'player2_ranking'   =>  "-",
                    'surface'           =>  $surface,
                    'scores'            =>  $data_scores,
                    'time_status'       =>  $data_time_status,
                    'time'              =>  $data_time,
                    'detail'            =>  $data_detail
                ];
    
                foreach ($players as $player) {
                    if ($data_player1_id == $player->api_id) {
                        $matches[$i]["player1_name"] = $player->name;
                        $matches[$i]["player1_ranking"] = (int)$player->ranking;
                    }
    
                    if ($data_player2_id == $player->api_id) {
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
	 * Pre calculation for Upcoming
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

		// inplay table
		$match_inplay_table_name = "t_inplay_matches";
		$d = substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2);
		$times = Helper::getTimePeriod($d);
		$inplay_data = DB::table($match_inplay_table_name)
								->get();
		$player_ids = array();
		$enable_names = array();
		$i = 0;
		foreach ($inplay_data as $data) {
			$enable_name = "t_bucket_players_" . $data->player1_id . "_" . $data->player2_id;
			if (!(in_array($enable_names, $bucket_table_names))) {
				$player_ids[$i]["player1_id"] = $data->player1_id;
				$player_ids[$i]["player2_id"] = $data->player2_id;
				
				array_push($bucket_table_names, $enable_name);
				array_push($enable_names, $enable_name);

				$enable_opponent_table_name = "t_bucket_opponents_" . $data->player1_id . "_" . $data->player2_id;
				array_push($enable_names, $enable_opponent_table_name);
				array_push($bucket_table_names, $enable_opponent_table_name);

				$i ++;
			}
		}

		// upcoming table
		$match_upcoming_table_name = "t_upcoming_matches";
		$upcoming_data = DB::table($match_upcoming_table_name)
								->get();
		foreach ($upcoming_data as $data) {
			$enable_name = "t_bucket_players_" . $data->player1_id . "_" . $data->player2_id;
			if (!(in_array($enable_names, $bucket_table_names))) {
				$player_ids[$i]["player1_id"] = $data->player1_id;
				$player_ids[$i]["player2_id"] = $data->player2_id;
				
				array_push($bucket_table_names, $enable_name);
				array_push($enable_names, $enable_name);

				$enable_opponent_table_name = "t_bucket_opponents_" . $data->player1_id . "_" . $data->player2_id;
				array_push($enable_names, $enable_opponent_table_name);
				array_push($bucket_table_names, $enable_opponent_table_name);

				$i ++;
			}
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
			$bucket_player_table_exist = true;
			$bucket_opponents_table_exist = true;

			if (!Schema::hasTable($bucket_players_table)) {
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
					$table->string("home", 1);
				});
				$bucket_player_table_exist = false;
			} else {
				$players_table_data_cnt = DB::table($bucket_players_table)
											->count();
				if ($players_table_data_cnt > 0) {
					$bucket_player_table_exist = true;
				} else {
					$bucket_player_table_exist = false;
				}
			}

			$bucket_opponents_table = "t_bucket_opponents_" . $player1_id . "_" . $player2_id;
			if (!Schema::hasTable($bucket_opponents_table)) {
				Schema::create($bucket_opponents_table, function($table) {
					$table->increments("id");
					$table->integer("event_id");
					$table->integer("o_id");
					$table->integer("o_ranking")->nullable();
					$table->integer("oo_id");
					$table->integer("oo_ranking")->nullable();
					$table->integer("won"); // 1: o, 2: oo
					$table->integer("sets")->nullable();
					$table->json("depths");
					$table->string("surface", 50)->nullable();
					$table->integer("time");
				});
				$bucket_opponents_table_exist = false;
			} else {
				$opponents_table_data_cnt = DB::table($bucket_opponents_table)
											->count();
				if ($opponents_table_data_cnt > 0) {
					$bucket_opponents_table_exist = true;
				} else {
					$bucket_opponents_table_exist = false;
				}
			}

			if (!$bucket_player_table_exist || !$bucket_opponents_table_exist) {
				$current_player_ids = [$player1_id, $player2_id];
				$relation_data = Helper::getRelationMatches($current_player_ids, $history_tables, $players);
				if (!$bucket_player_table_exist) {
					// insert t_players_ table
					$players_object = array_merge($relation_data[0][0], $relation_data[0][1]);
					$player_insert_data = collect($players_object);
					$chunks = $player_insert_data->chunk(500);
					foreach ($chunks as $chunk) {
						DB::table($bucket_players_table)
								->insert($chunk->toArray());
					}
				}

				if (!$bucket_opponents_table_exist) {
					//insert t_opponents_ table
					$opponent_insert_data = collect($relation_data[1]);
					$chunks = $opponent_insert_data->chunk(500);
					foreach ($chunks as $chunk) {
						DB::table($bucket_opponents_table)
								->insert($chunk->toArray());
					}
				}
			}
			// Check pre-Calculation history tables
			$pre_calculation_history_table = "t_pre_calculation_history";
			$pre_calculation_exist = DB::table($pre_calculation_history_table)
										->where("player_ids", $player1_id . "_" . $player2_id)
										->count();
			if ($pre_calculation_exist == 0) {
				$pre_calculation_insert_data = [
					"player_ids" => $player1_id . "_" . $player2_id,
					"time"  => $times[0]
				];
				DB::table($pre_calculation_history_table)
					->insert($pre_calculation_insert_data);
			}
		}

		$execution_time = microtime(true) - $start_time;
		$log .= ("  End: " . date("Y-m-d H:i:s"));
		$log .= "  ExecutionTime: " . $execution_time . "\n";
		echo $log;
	}

	/**
     * Get relation data (pre-calcuated)
     * @param   array   $player_ids
     * @return  array   $matches
     */
    public static function getRelationMatches($player_ids, $history_tables, $players, $calculate_type=1) {
		$players_object = array();
		$opponents_objects = array();
		$o_ids = array();
		$player_cnt = 0;
		$total_player_cnt = count($player_ids);

		foreach ($player_ids as $player_id) {
			$matches_array = array();
			$event_ids = array();
			$players_object[$player_cnt] = array();

			foreach ($history_tables as $table) {
				// filtering by player id
				$match_table_subquery = DB::table($table->tablename)
											->where('scores', '<>', NULL)
											->where('scores', '<>', '')
											->where(function($query) use ($player_id) {
												$query->where('player1_id', $player_id)
												->orWhere('player2_id', $player_id);
											});
				
				array_push($matches_array, $match_table_subquery->get());
			}
	
			$matches = Helper::getMatchesResponse($matches_array, $players, 3);
	
			// add sets
			$matches_set = Helper::getPlayersSubDetail($matches, $player_id);
	
			// add breaks to the players array
			$matches = array();
	
			foreach ($matches_set as $data) {
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
				$p_ranking = $data["player1_id"] == $player_id ? $data["player1_ranking"] : $data["player2_ranking"];
				$p_ranking = $p_ranking == "-" ? NULL : $p_ranking;
				$player_info = [
					"p_id" =>  $player_id,
					"p_name" => $data["player1_id"] == $player_id ? $data["player1_name"] : $data["player2_name"],
					"p_odd" => $data["player1_id"] == $player_id ? $data["player1_odd"] : $data["player2_odd"],
					"p_ranking" => $p_ranking,
				];
				$opponent_info = [
					"o_id" => $data["player1_id"] == $player_id ? $data["player2_id"] : $data["player1_id"],
					"o_name" => $data["player1_id"] == $player_id ? $data["player2_name"] : $data["player1_name"],
					"o_odd" => $data["player1_id"] == $player_id ? $data["player2_odd"] : $data["player1_odd"],
					"o_ranking" => $data["player1_id"] == $player_id ? $data["player2_ranking"] : $data["player1_ranking"],
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
					"o_id"		=> $opponent_info["o_id"],
					"o_name"	=> $opponent_info["o_name"],
					"o_odd"		=> $opponent_info["o_odd"],
					"o_ranking"	=> $opponent_info["o_ranking"] == "-" ? NULL : $opponent_info["o_ranking"],
					"home"	    => $data["home"],
				];
				if (!in_array($data["event_id"], $event_ids)) {
					array_push($players_object[$player_cnt], $db_data);
					array_push($event_ids, $data["event_id"]);
					// opponent detail
					if ($calculate_type == 1 && !in_array($opponent_info["o_id"], $o_ids)) {
						$opponent_data = Helper::getOpponentSubDetail($opponent_info["o_id"], $history_tables, $players);
						$opponents_objects = array_merge($opponents_objects, $opponent_data);
						array_push($o_ids, $data["event_id"]);
					}
				}
			}
			$player_cnt ++;
		}
		return [$players_object, $opponents_objects];
    }

	/**
	 * Update Inplay Table every 5 secs
	 */
	public static function updateInplayTable() {
		$curl = curl_init();
		$token = env("API_TOKEN", "");
		$inplay_table = "t_inplay";
		$url = "https://api.b365api.com/v2/events/inplay?sport_id=13&token=$token";
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$inplay_data = json_decode(curl_exec($curl), true);
		
		$previous_events = DB::table($inplay_table)
							->select('event_id')
							->get();
		$old_event_ids = array();
		foreach ($previous_events as $event) {
			array_push($old_event_ids, $event->event_id);
		}
							
		if (array_key_exists("results", $inplay_data)) {
			$events = $inplay_data["results"];
			if (count($events) > 0) {
				foreach ($events as $event) {
					if ($event["points"] != NULL && $event["ss"] != NULL) {
						$inplay_array = [
							"event_id"  => (int)$event["id"],
							"ss"   		=> trim($event["ss"]),
							"points"   	=> $event["points"],
							"indicator"	=> $event["playing_indicator"],
						];
						// Update or Insert into t_inplay table
						DB::table($inplay_table)
							->updateOrInsert(
								["event_id" => (int)$event["id"]],
								$inplay_array
							);
					}
					if (($key = array_search((int)$event["id"], $old_event_ids)) !== false) {
						unset($old_event_ids[$key]);
					}
				}
				DB::table($inplay_table)
					->whereIn('event_id', $old_event_ids)
					->delete();
			}
		}
	}

	public static function updateRankTable() {
		$curl = curl_init();
		$token = env("API_TOKEN", "");
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

	/**
	 * Import the historical data of a specific day
	 * @param string $date
	 * @param bool $once
	 */
  	public static function updateDB($date=false, $once=false, $match_status=3) {
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
		if ($match_status == 0) {
			$match_table_name = "t_upcoming_matches";
		} else if ($match_status == 1) {
			$match_table_name = "t_inplay_matches";
		} else if ($match_status == 3) {
			$match_table_name = "t_matches_" . substr($date, 0, 4) . "_" . substr($date, 4, 2);
		}

		$event_ids = array();
		$origin_event_ids = array();
    	if (!Schema::hasTable($match_table_name)) {
      		// Code to create table
      		Schema::create($match_table_name, function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("league_id")->nullable();
				$table->string("league_name", 255)->nullable();
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
				$table->index(['player1_id', 'player2_id']);
			});
    	} else {
			$origin_events = DB::table($match_table_name)
								->get();
			foreach ($origin_events as $event) {
				array_push($origin_event_ids, $event->event_id);
			}
		}

		$curl = curl_init();
		$token = env("API_TOKEN", "");

		if ($match_status == 3) {
			// Get history data
			$url = "https://api.b365api.com/v2/events/ended?sport_id=13&token=$token&day=$date";
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$history_data = json_decode(curl_exec($curl), true);
			$request_count ++;
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
			$matches = $history;
		} else if ($match_status == 0) {
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
			$matches = $upcoming;
		} else if ($match_status == 1) {
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
			}
			$matches = $inplay;
		}

		$request_event_ids = array();
		if (count($matches) > 0) {
			foreach ($matches as $event) {
				if (!in_array($event["id"], $event_ids)) {
					array_push($request_event_ids, $event["id"]);
					if (!in_array($event["id"], $origin_event_ids)) {
						array_push($event_ids, $event["id"]);
					}
				}
			}
		}

		$inplayUpcomingMatches = array();
		foreach ($event_ids as $event_id) {
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
			if ($view != NULL && array_key_exists("results", $view) && count($view["results"]) > 0 && $view["results"][0]["id"] == $event_id) {
				$event = $view["results"][0];
				if ($event != NULL && array_key_exists("home", $event) && (array_key_exists("away", $event) || array_key_exists("o_away", $event))) {
					$time_status = (int)$event["time_status"];
					if (array_key_exists("league", $event) && array_key_exists("id", $event["league"])) {
						$league_id = $event["league"]["id"];
					} else {
						$league_id = NULL;
					}
					if (array_key_exists("league", $event) && array_key_exists("name", $event["league"])) {
						$league_name = $event["league"]["name"];
					} else {
						$league_name = NULL;
					}
					if ($time_status == 0 || $time_status == 1 || $time_status == 3) {
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
						
						$surface = array_key_exists("extra", $event)
									&& array_key_exists("ground", $event["extra"])
										? $event["extra"]["ground"]
										: NULL;
		
						$detail = "";
						if (array_key_exists("events", $event)) {
							$event_cnt = 0;
							foreach ($event["events"] as $game) {
								if (strpos($game["text"], "Game") !== false) {
									$splitedText = explode(" - ", $game["text"]);
									if (count($splitedText) > 2) {
										$games = explode(" ", trim($splitedText[0]));
		
										$event_cnt = (int)$games[1];
										$detail .= $games[1];
										if (strpos($game["text"], "hold") !== false) {
											$detail .= ":h:";
										} elseif (strpos($game["text"], "breaks") !== false) {
											$detail .= ":b:";
										}
										
										$name = str_replace(" ", "", $splitedText[1]);
										if ($name == str_replace(" ", "", $player1_name)) {
											$detail .= "1:";
										} elseif ($name == str_replace(" ", "", $player2_name)) {
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
							"league_id"     => (int)$league_id,
							"league_name"   => $league_name,
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
						if ($time_status == 0 || $time_status == 1) {
							// Update or Insert into t_matches table
							array_push($inplayUpcomingMatches, $update_or_insert_array);
							if ($time_status == 1) {
								$inplay_array = [
									"event_id"  => (int)$event_id,
									"ss"   		=> $event["ss"] ? trim($event["ss"]) : "",
									"points"   	=> $event["points"],
								];
								// Update or Insert into t_inplay table
								DB::table("t_inplay")
									->updateOrInsert(
										["event_id" => (int)$event_id],
										$inplay_array
									);
							}
						} else {
							// Update or Insert into t_matches table
							DB::table($match_table_name)
								->updateOrInsert(
									["event_id" => (int)$event_id],
									$update_or_insert_array
							);
						}
					}
				}
			}
		}
		if ($match_status == 0 || $match_status == 1) {
			if (count($inplayUpcomingMatches) > 0) {
				// keep these events for inplay/upcoming matches
				$duplicated_event_ids = array_intersect($request_event_ids, $origin_event_ids);
				$old_events = DB::table($match_table_name)
					->select(
						"event_id",
						"league_id",
						"league_name",
						"player1_id",
						"player1_name",
						"player1_odd",
						"player2_id",
						"player2_name",
						"player2_odd",
						"scores",
						"surface",
						"time_status",
						"time",
						"detail"
					)
					->whereIn('event_id', $duplicated_event_ids)
					->get();
				$old_events_array = json_decode(json_encode($old_events), true);
				$inplayUpcomingMatches = array_merge($old_events_array, $inplayUpcomingMatches);

				$insert_data = collect($inplayUpcomingMatches);
				$chunks = $insert_data->chunk(100);
				$chunks_cnt = count($chunks);
				// truncate the inplay and upcoming table
				DB::table($match_table_name)->truncate();
				foreach ($chunks as $chunk) {
					DB::table($match_table_name)
							->insert($chunk->toArray());
				}
			}
		} else if ($match_status == 3) {
			$execution_time = microtime(true) - $start_time;
			$log .= ("  End Time: " . date("Y-m-d H:i:s"));
			$log .= ("  ===>  Total History Count: " . $history_total_count . ", Execution Time:  " . $execution_time. " secs, Request Count: " . $request_count . "\n");
			echo $log;
		}
		curl_close($curl);
	}

	public static function printLog($content) {
		echo $content . "\n";
	}

	/**
	 * Create backtest tables for the historical matches from 2021-04-05 ~ 2021-04-11
	 */
	public static function generateDataForBacktestRobots() {
		$start_time = microtime(true);
		$backtest_players_table = "t_backtest_players";

		/* --- Get player ids between 2021-04-05 and 2021-04-11 --- start --- */
		// get events
		$events = DB::table("t_matches_2021_03")
					->get();

		$player_ids = array();
		foreach ($events as $event) {
			if (!in_array($event->player1_id, $player_ids)) {
				array_push($player_ids, $event->player1_id);
			}
			if (!in_array($event->player2_id, $player_ids)) {
				array_push($player_ids, $event->player2_id);
			}
		}
		/* --- Get player ids between 2021-04-05 and 2021-04-11 ---  end  --- */
		$log = "Count of players: " . count($player_ids);
		Helper::printLog($log);

		$players = DB::table("t_players")->get();
		$history_tables = DB::table("pg_catalog.pg_tables")
								->where("schemaname", "public")
								->where("tablename", "like", "t_matches_%")
								->get();

		/* --- Create backtest players table --- start --- */
		Schema::dropIfExists($backtest_players_table);
		Schema::create($backtest_players_table, function($table) {
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
			$table->string("home", 1);
		});
		/* --- Create backtest players table ---  end  --- */

		$relation_data = Helper::getRelationMatches($player_ids, $history_tables, $players, 0);
		// insert t_backtest_players table
		$relation_players_object = $relation_data[0];
		$players_object = array();
		foreach ($relation_players_object as $player_object) {
			$players_object = array_merge($players_object, $player_object);
		}

		/* --- insert players data --- start --- */
		$total_player_count = count($players_object);
		$log = "players count: " . $total_player_count;
		Helper::printLog($log);
		$player_data = collect($players_object);
		$chunks = $player_data->chunk(500);
		$i = 0;
		$chunks_cnt = count($chunks);
		foreach ($chunks as $chunk) {
			DB::table($backtest_players_table)
					->insert($chunk->toArray());
			$i ++;
			$log = "player chunk ended: " . $chunks_cnt . " / " . $i;
			Helper::printLog($log);
		}
		/* --- insert players data ---  end  --- */

		$execution_time = microtime(true) - $start_time;
		$log .= ("  End: " . date("Y-m-d H:i:s"));
		$log .= "  ExecutionTime: " . $execution_time;
		Helper::printLog($log);
	}

	public static function getWinners($matches, $matchType=0) {
		/**
		 * Robot 43: BRW + GAH + RANK + L10
		 * Robot 44: BRW + GAH + RANK + L20
		 */
		$players = DB::table("t_players")->get();
		$history_tables = DB::table("pg_catalog.pg_tables")
								->where("schemaname", "public")
								->where("tablename", "like", "t_matches_%")
								->get();

		$filteredMatches = Helper::filterMatchesByRankOdd($matches);
		$winners = array();
		$event_ids = array();
        $correct = 0;
		foreach ($filteredMatches as $match) {
			$player1_id = $match["player1_id"];
			$player2_id = $match["player2_id"];
			$player_detail = [
				"player1_odd" => $match["player1_odd"],
				"player2_odd" => $match["player2_odd"],
				"player1_ranking" => $match["player1_ranking"],
				"player2_ranking" => $match["player2_ranking"],
			];
			
			if ($matchType == 0) { // history so we have to pre-calculate
				$player_ids = [$player1_id, $player2_id];
				$relation_data = Helper::getRelationMatches($player_ids, $history_tables, $players, 0);
				
				$player1_object = $relation_data[0][0];
				usort($player1_object, function($a, $b) {
					return $b['time'] - $a['time'];
				});
				$player1_object = Helper::getUniqueMatchesByEventId($player1_object);
				$player1_objects_l_20 = array_slice($player1_object, 0, 20);
				$player1_objects_l_10 = array_slice($player1_object, 0, 10);

				$player2_object = $relation_data[0][1];
				usort($player2_object, function($a, $b) {
					return $b['time'] - $a['time'];
				});
				$player2_object = Helper::getUniqueMatchesByEventId($player2_object);
				$player2_objects_l_20 = array_slice($player2_object, 0, 20);
				$player2_objects_l_10 = array_slice($player2_object, 0, 10);
			} else { // inplay or upcoming so we can use pre-calculation table directly
				$table_name = "t_bucket_players_" . $player1_id . "_" . $player2_id;
				// for robot 41 and 43
				$player1_objects = DB::table($table_name)
											->select("event_id", "p_brw", "p_gah", "p_ww", "p_lw", "p_wl", "p_ll")
											->where("p_id", $player1_id)
											->orderByDesc("time")
											->get();
				// for robot 42 and 44
				$player2_objects = DB::table($table_name)
											->select("event_id", "p_brw", "p_gah", "p_ww", "p_lw", "p_wl", "p_ll")
											->where("p_id", $player2_id)
											->orderByDesc("time")
											->get();
				$player1_objects_l_10 = Helper::getUniqueMatchesByEventId($player1_objects, 10);
				$player1_objects_l_20 = Helper::getUniqueMatchesByEventId($player1_objects, 20);
				$player2_objects_l_10 = Helper::getUniqueMatchesByEventId($player2_objects, 10);
				$player2_objects_l_20 = Helper::getUniqueMatchesByEventId($player2_objects, 20);
			}

			if ($player_detail["player1_odd"] != NULL && $player_detail["player2_odd"] != NULL && $player_detail["player1_ranking"] != "-" && $player_detail["player2_ranking"] != "-") {
				if ($matchType == 0) {
                    $win_1 = 0;
                    $win_2 = 0;
                    $scores = explode(",", $match['scores']);
                    foreach ($scores as $score) {
                        $set_score = explode("-", $score);
                        if (count($set_score) == 2) {
                            $diff = (int)$set_score[0] - (int)$set_score[1];
                            if ($diff >= 0) {
                                $win_1 ++;
                            } else {
                                $win_2 ++;
                            }
                        }
                    }
                }
				// for robot 44 (BRW + GAH + RANK + L20)
				$winner_4 = Helper::robotBalance($player1_objects_l_20, $player2_objects_l_20, 20, $player_detail);
				if ($winner_4 != 0) {
					if ($matchType == 0) {
                        if (($win_1 >= $win_2 && $winner_4 == 1) || ($win_1 <= $win_2 && $winner_4 == 2)) {
                            $correct = 1;
                        } else {
                            $correct = -1;
                        }
                    }
                    $detail = [
						'event_id'  => $match['event_id'],
						'winner'    => $winner_4,
						'type'      => 4,
                        'correct'   => $correct,
					];
					if (!in_array($match['event_id'], $event_ids)) {
						array_push($winners, $detail);
						array_push($event_ids, $match['event_id']);
					}
				}
                // for robot 44 (BRW + GAH + RANK + L20)
				$winner_44 = Helper::robot4344($player1_objects_l_20, $player2_objects_l_20, 20, $player_detail);
				if ($winner_44 != 0) {
					if ($matchType == 0) {
                        if (($win_1 >= $win_2 && $winner_44 == 1) || ($win_1 <= $win_2 && $winner_44 == 2)) {
                            $correct = 1;
                        } else {
                            $correct = -1;
                        }
                    }
                    $detail = [
						'event_id'  => $match['event_id'],
						'winner'    => $winner_44,
						'type'      => 44,
                        'correct'   => $correct,
					];
					if (!in_array($match['event_id'], $event_ids)) {
						array_push($winners, $detail);
						array_push($event_ids, $match['event_id']);
					}
				}
				// for robot 43 (BRW + GAH + RANK + L10)
				$winner_43 = Helper::robot4344($player1_objects_l_10, $player2_objects_l_10, 10, $player_detail);
				if ($winner_43 != 0) {
                    if ($matchType == 0) {
                        if (($win_1 >= $win_2 && $winner_43 == 1) || ($win_1 <= $win_2 && $winner_43 == 2)) {
                            $correct = 1;
                        } else {
                            $correct = -1;
                        }
                    }
					$detail = [
						'event_id'  => $match['event_id'],
						'winner'    => $winner_43,
						'type'      => 43,
                        'correct'   => $correct,
					];
					if (!in_array($match['event_id'], $event_ids)) {
						array_push($winners, $detail);
						array_push($event_ids, $match['event_id']);
					}
				}
			}
		}
		return $winners;
	}

	public static function getUniqueMatchesByEventId($events, $limit=-1) {
		$event_ids = array();
		$newEvents = array();
		$i = 0;
		foreach ($events as $event) {
			if (gettype($event) == "object") {
				$event_id = $event->event_id;
			} else {
				$event_id = $event["event_id"];
			}
			if (!in_array($event_id, $event_ids)) {
				array_push($newEvents, $event);
				array_push($event_ids, $event_id);
				if ($limit != -1) {
					$i ++;
					if ($i == $limit) {
						break;
					}
				}
			}
		}
		return $newEvents;
	}

	public static function getUniqueMatchesByODetail($events, $limit=-1) {
		$event_ids = array();
		$o_ids = array();
		$oo_ids = array();
		$newEvents = array();
		$i = 0;
		foreach ($events as $event) {
			$insert = false;
			$index = array_search($event->event_id, $event_ids);
			if ($index == false) {
				$insert = true;
			} else {
				if ($o_ids[$index] == $event->o_id && $oo_ids[$index] == $event->oo_id) {
					$insert = false;
				} else {
					$insert = true;
				}
			}
			if ($insert) {
				array_push($newEvents, $event);
				array_push($event_ids, $event->event_id);
				array_push($o_ids, $event->o_id);
				array_push($oo_ids, $event->oo_id);
				if ($limit != -1) {
					$i ++;
					if ($i == $limit) {
						break;
					}
				}
			}
		}
		return $newEvents;
	}

	public static function robotBalance($player1_events, $player2_events, $limit, $player_detail) {
		$player1_ranking = $player_detail["player1_ranking"];
		$player2_ranking = $player_detail["player2_ranking"];
		$player1_gah = 0;
		$player1_brw = 0;
		$player1_ww = 0;
		$player1_lw = 0;
		$player1_wl = 0;
		$player1_ll = 0;
		$i = 0;
		foreach ($player1_events as $player1_event) {
			if (gettype($player1_event) == "object") {
				$p_brws = json_decode($player1_event->p_brw);
				$p_gahs = json_decode($player1_event->p_gah);
				$p_ww = json_decode($player1_event->p_ww);
				$p_lw = json_decode($player1_event->p_lw);
				$p_wl = json_decode($player1_event->p_wl);
				$p_ll = json_decode($player1_event->p_ll);
			} else {
				$p_brws = json_decode($player1_event["p_brw"]);
				$p_gahs = json_decode($player1_event["p_gah"]);
				$p_ww = json_decode($player1_event["p_ww"]);
				$p_lw = json_decode($player1_event["p_lw"]);
				$p_wl = json_decode($player1_event["p_wl"]);
				$p_ll = json_decode($player1_event["p_ll"]);
			}

			foreach ($p_brws as $p_brw) {
				$player1_brw += array_sum($p_brw);
			}

			foreach ($p_gahs as $p_gah) {
				$player1_gah += array_sum($p_gah);
			}

			$player1_ww = array_sum($p_ww);
			$player1_lw = array_sum($p_lw);
			$player1_wl = array_sum($p_wl);
			$player1_ll = array_sum($p_ll);

			$i ++;
			if ($i == $limit) {
				break;
			}
		}

		$player2_gah = 0;
		$player2_brw = 0;
		$player2_ww = 0;
		$player2_lw = 0;
		$player2_wl = 0;
		$player2_ll = 0;
		$i = 0;
		foreach ($player2_events as $player2_event) {
			if (gettype($player2_event) == "object") {
				$p_brws = json_decode($player2_event->p_brw);
				$p_gahs = json_decode($player2_event->p_gah);
				$p_ww = json_decode($player2_event->p_ww);
				$p_lw = json_decode($player2_event->p_lw);
				$p_wl = json_decode($player2_event->p_wl);
				$p_ll = json_decode($player2_event->p_ll);
			} else {
				$p_brws = json_decode($player2_event["p_brw"]);
				$p_gahs = json_decode($player2_event["p_gah"]);
				$p_ww = json_decode($player2_event["p_ww"]);
				$p_lw = json_decode($player2_event["p_lw"]);
				$p_wl = json_decode($player2_event["p_wl"]);
				$p_ll = json_decode($player2_event["p_ll"]);
			}

			foreach ($p_brws as $p_brw) {
				$player2_brw += array_sum($p_brw);
			}

			foreach ($p_gahs as $p_gah) {
				$player2_gah += array_sum($p_gah);
			}

			$player2_ww = array_sum($p_ww);
			$player2_lw = array_sum($p_lw);
			$player2_wl = array_sum($p_wl);
			$player2_ll = array_sum($p_ll);
			
			$i ++;
			if ($i == $limit) {
				break;
			}
		}
		$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
		$player1_balance = ($player1_ww + $player1_lw) - ($player1_wl + $player1_ll);
		$player2_balance = ($player2_ww + $player2_lw) - ($player2_wl + $player2_ll);
		$expected_balance_winner = 0;
		if ($player1_balance > $player2_balance) {
			$expected_balance_winner = 1;
		} else if ($player1_balance < $player2_balance) {
			$expected_balance_winner = 2;
		}
		if (($expected_winner == 1 && $expected_balance_winner == 1 && $player1_ranking < $player2_ranking) || ($expected_winner == 2 && $expected_balance_winner == 2 && $player2_ranking < $player1_ranking)) {
			return $expected_winner;
		} else {
			return 0;
		}
	}

	public static function robot4344($player1_events, $player2_events, $limit, $player_detail) {
		$player1_ranking = $player_detail["player1_ranking"];
		$player2_ranking = $player_detail["player2_ranking"];
		$player1_gah = 0;
		$player1_brw = 0;
		$i = 0;
		foreach ($player1_events as $player1_event) {
			if (gettype($player1_event) == "object") {
				$p_brws = json_decode($player1_event->p_brw);
				$p_gahs = json_decode($player1_event->p_gah);
			} else {
				$p_brws = json_decode($player1_event["p_brw"]);
				$p_gahs = json_decode($player1_event["p_gah"]);
			}
			foreach ($p_brws as $p_brw) {
				$player1_brw += array_sum($p_brw);
			}
			foreach ($p_gahs as $p_gah) {
				$player1_gah += array_sum($p_gah);
			}
			$i ++;
			if ($i == $limit) {
				break;
			}
		}

		$player2_gah = 0;
		$player2_brw = 0;
		$i = 0;
		foreach ($player2_events as $player2_event) {
			if (gettype($player2_event) == "object") {
				$p_brws = json_decode($player2_event->p_brw);
				$p_gahs = json_decode($player2_event->p_gah);
			} else {
				$p_brws = json_decode($player2_event["p_brw"]);
				$p_gahs = json_decode($player2_event["p_gah"]);
			}
			foreach ($p_brws as $p_brw) {
				$player2_brw += array_sum($p_brw);
			}
			foreach ($p_gahs as $p_gah) {
				$player2_gah += array_sum($p_gah);
			}
			$i ++;
			if ($i == $limit) {
				break;
			}
		}
		$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
		if (($expected_winner == 1 && $player1_ranking < $player2_ranking) || ($expected_winner == 2 && $player2_ranking < $player1_ranking)) {
			return $expected_winner;
		} else {
			return 0;
		}
	}

	/**
	 * Get the matches that have rank and odd
	 */
	public static function filterMatchesByRankOdd ($matches) {
		$filteredMatches = array();
		foreach ($matches as $match) {
			$player1_ranking = $match["player1_ranking"];
			$player2_ranking = $match["player2_ranking"];
			$player1_odd = $match["player1_odd"];
			$player2_odd = $match["player2_odd"];
			if (($player1_ranking != "-" && $player2_ranking != "-" && $player1_odd != NULL && $player2_odd != NULL) ||
			($player1_ranking == "-" && $player2_ranking == "-")) {
				array_push($filteredMatches, $match);
			}
		}
		return $filteredMatches;
	}

	/**
	 * Create t_backtest_bots_ ... about the strategies
	 */
	public static function robotStrategies() {
		$backtest_players_table = "t_backtest_players";
		$enable_robots = [
			1, 1, // brw + gah (rank)
			1, 1, // brw + gah, ww + lw (rank)
			1, 1, // (ww + lw) - (wl + ll) (rank)
			1, 1, // (ww + lw) - (wl + ll) (unrank)
		];
		/* --- Create t_backtest_bots_brw_10 table --- start --- */
		$backtest_bots = [
			"t_backtest_bots_brw_gah_rank_10",
			"t_backtest_bots_brw_gah_rank_20",
			"t_backtest_bots_brw_gah_balance_rank_10",
			"t_backtest_bots_brw_gah_balance_rank_20",
			"t_backtest_bots_balance_rank_10",
			"t_backtest_bots_balance_rank_20",
			"t_backtest_bots_balance_unrank_10",
			"t_backtest_bots_balance_unrank_20",
		];

		// BRW + GAH + RANK (Lower) (Ranked)
		if ($enable_robots[0]) {
			Schema::dropIfExists($backtest_bots[0]);
			Schema::create($backtest_bots[0], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[1]) {
			Schema::dropIfExists($backtest_bots[1]);
			Schema::create($backtest_bots[1], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// (Ranked) BRW + GAH, (WW + LW) - (WL + LL)
		if ($enable_robots[2]) {
			Schema::dropIfExists($backtest_bots[2]);
			Schema::create($backtest_bots[2], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[3]) {
			Schema::dropIfExists($backtest_bots[3]);
			Schema::create($backtest_bots[3], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// (Ranked) (WW + LW) - (WL + LL)
		if ($enable_robots[4]) {
			Schema::dropIfExists($backtest_bots[4]);
			Schema::create($backtest_bots[4], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[5]) {
			Schema::dropIfExists($backtest_bots[5]);
			Schema::create($backtest_bots[5], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// (Unranked) (WW + LW) - (WL + LL)
		if ($enable_robots[6]) {
			Schema::dropIfExists($backtest_bots[6]);
			Schema::create($backtest_bots[6], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[7]) {
			Schema::dropIfExists($backtest_bots[7]);
			Schema::create($backtest_bots[7], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// get events
		$events = DB::table("t_matches_2021_03")
					->get();
		$total_event_cnt = count($events);
		$log = "Total events: " . $total_event_cnt;
		Helper::printLog($log);

		$players = DB::table("t_players")->get();
		$player_ids = array();
		$player_ranks = array();
		foreach ($players as $player) {
			array_push($player_ids, $player->api_id);
			array_push($player_ranks, $player->ranking);
		}
		$event_cnt = 0;
		foreach ($events as $event) {
			$event_id = $event->event_id; 
			$surface = $event->surface;
			$player1_id = $event->player1_id;
			$player2_id = $event->player2_id;
			$player1_odd = $event->player1_odd;
			$player2_odd = $event->player2_odd;
			if ($player1_odd != NULL) {
				$player1_odd = (float)$player1_odd;
			}
			if ($player2_odd != NULL) {
				$player2_odd = (float)$player2_odd;
			}

			if ($event->scores != "") {
				$scores = explode(",", $event->scores);
				$scores = explode("-", $scores[0]);
				if (count($scores) == 2) {
					if ((int)$scores[0] > (int)$scores[1]) {
						$real_winner = 1;
					} else {
						$real_winner = 2;
					}

					$weekday = date('N', $event->time); 

					if (in_array($player1_id, $player_ids)) {
						$key_1 = array_search($player1_id, $player_ids);
						$player1_ranking = $player_ranks[$key_1];
					} else {
						$player1_ranking = 501;
					}
					if (in_array($player2_id, $player_ids)) {
						$key_2 = array_search($player2_id, $player_ids);
						$player2_ranking = $player_ranks[$key_2];
					} else {
						$player2_ranking = 501;
					}
	
					$player1_events = DB::table($backtest_players_table)
											->select("event_id", "p_brw", "p_brl", "p_gah", "p_ww", "p_lw", "p_wl", "p_ll")
											->where("p_id", $player1_id)
											->orderByDesc("time")
											->get();
					$player1_events = Helper::getUniqueMatchesByEventId($player1_events, 30);

					$player1_surface_events = DB::table($backtest_players_table)
													->select("event_id", "p_brw", "p_brl", "p_gah", "p_ww", "p_lw", "p_wl", "p_ll")
													->where("p_id", $player1_id)
													->where("surface", $surface)
													->orderByDesc("time")
													->get();
					$player1_surface_events = Helper::getUniqueMatchesByEventId($player1_surface_events, 30);

					$player2_events = DB::table($backtest_players_table)
											->select("event_id", "p_brw", "p_brl", "p_gah", "p_ww", "p_lw", "p_wl", "p_ll")
											->where("p_id", $player2_id)
											->orderByDesc("time")
											->get();
					$player2_events = Helper::getUniqueMatchesByEventId($player2_events, 30);

					$player2_surface_events = DB::table($backtest_players_table)
													->select("event_id", "p_brw", "p_brl", "p_gah", "p_ww", "p_lw", "p_wl", "p_ll")
													->where("p_id", $player2_id)
													->where("surface", $surface)
													->orderByDesc("time")
													->get();
					$player1_surface_events = Helper::getUniqueMatchesByEventId($player1_surface_events, 30);

					/* --- strategy 43 (BRW + GAH + RANK + L10) --- start --- */
					if ($enable_robots[0] &&
						$player1_odd != NULL && $player2_odd != NULL &&
						// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
						$player1_ranking != 501 && $player2_ranking != 501
					) {
						$player1_gah = 0;
						$player1_brw = 0;
						$i = 0;
						foreach ($player1_events as $player1_event) {
							$p_brws = json_decode($player1_event->p_brw);
							foreach ($p_brws as $p_brw) {
								$player1_brw += array_sum($p_brw);
							}
							$p_gahs = json_decode($player1_event->p_gah);
							foreach ($p_gahs as $p_gah) {
								$player1_gah += array_sum($p_gah);
							}
							$i ++;
							if ($i == 10) {
								break;
							}
						}
			
						$player2_gah = 0;
						$player2_brw = 0;
						$i = 0;
						foreach ($player2_events as $player2_event) {
							$p_brws = json_decode($player2_event->p_brw);
							foreach ($p_brws as $p_brw) {
								$player2_brw += array_sum($p_brw);
							}
							$p_gahs = json_decode($player2_event->p_gah);
							foreach ($p_gahs as $p_gah) {
								$player2_gah += array_sum($p_gah);
							}
							$i ++;
							if ($i == 10) {
								break;
							}
						}
						$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
						if (($expected_winner == 1 && $player1_ranking < $player2_ranking) || ($expected_winner == 2 && $player2_ranking < $player1_ranking)) {
							$insert_data = [
								"event_id" 			=> $event_id,
								"expected_winner" 	=> $expected_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[0])
								->insert($insert_data);
						}
					}
					/* --- strategy 43 (BRW + GAH + RANK + L10) ---  end  --- */

					/* --- strategy 44 (BRW + GAH + RANK Lower) + L20) --- start --- */
					if ($enable_robots[1] &&
						$player1_odd != NULL && $player2_odd != NULL &&
						// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
						$player1_ranking != 501 && $player2_ranking != 501
					) {
						$player1_gah = 0;
						$player1_brw = 0;
						$i = 0;
						foreach ($player1_events as $player1_event) {
							$p_brws = json_decode($player1_event->p_brw);
							foreach ($p_brws as $p_brw) {
								$player1_brw += array_sum($p_brw);
							}
							$p_gahs = json_decode($player1_event->p_gah);
							foreach ($p_gahs as $p_gah) {
								$player1_gah += array_sum($p_gah);
							}
							$i ++;
							if ($i == 20) {
								break;
							}
						}
			
						$player2_gah = 0;
						$player2_brw = 0;
						$i = 0;
						foreach ($player2_events as $player2_event) {
							$p_brws = json_decode($player2_event->p_brw);
							foreach ($p_brws as $p_brw) {
								$player2_brw += array_sum($p_brw);
							}
							$p_gahs = json_decode($player2_event->p_gah);
							foreach ($p_gahs as $p_gah) {
								$player2_gah += array_sum($p_gah);
							}
							$i ++;
							if ($i == 20) {
								break;
							}
						}
						$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
						if (($expected_winner == 1 && $player1_ranking < $player2_ranking) || ($expected_winner == 2 && $player2_ranking < $player1_ranking)) {
							$insert_data = [
								"event_id" 			=> $event_id,
								"expected_winner" 	=> $expected_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[1])
								->insert($insert_data);
						}
					}
					/* --- strategy 44 (BRW + GAH + RANK (Lower) + L20) ---  end  --- */

					/* --- strategy 3 (BRW + GAH + RANK Lower & Balance) + L10) --- start --- */
					if ($enable_robots[2] &&
						$player1_odd != NULL && $player2_odd != NULL &&
						// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
						$player1_ranking != 501 && $player2_ranking != 501
					) {
						$player1_gah = 0;
						$player1_brw = 0;
						$player1_ww = 0;
						$player1_lw = 0;
						$player1_wl = 0;
						$player1_ll = 0;
						$i = 0;
						foreach ($player1_events as $player1_event) {
							$p_brws = json_decode($player1_event->p_brw);
							foreach ($p_brws as $p_brw) {
								$player1_brw += array_sum($p_brw);
							}

							$p_gahs = json_decode($player1_event->p_gah);
							foreach ($p_gahs as $p_gah) {
								$player1_gah += array_sum($p_gah);
							}

							$p_ww = json_decode($player1_event->p_ww);
							$p_lw = json_decode($player1_event->p_lw);
							$p_wl = json_decode($player1_event->p_wl);
							$p_ll = json_decode($player1_event->p_ll);

							$player1_ww = array_sum($p_ww);
							$player1_lw = array_sum($p_lw);
							$player1_wl = array_sum($p_wl);
							$player1_ll = array_sum($p_ll);

							$i ++;
							if ($i == 10) {
								break;
							}
						}
			
						$player2_gah = 0;
						$player2_brw = 0;
						$player2_ww = 0;
						$player2_lw = 0;
						$player2_wl = 0;
						$player2_ll = 0;
						$i = 0;
						foreach ($player2_events as $player2_event) {
							$p_brws = json_decode($player2_event->p_brw);
							foreach ($p_brws as $p_brw) {
								$player2_brw += array_sum($p_brw);
							}
							
							$p_gahs = json_decode($player2_event->p_gah);
							foreach ($p_gahs as $p_gah) {
								$player2_gah += array_sum($p_gah);
							}

							$p_ww = json_decode($player2_event->p_ww);
							$p_lw = json_decode($player2_event->p_lw);
							$p_wl = json_decode($player2_event->p_wl);
							$p_ll = json_decode($player2_event->p_ll);

							$player2_ww = array_sum($p_ww);
							$player2_lw = array_sum($p_lw);
							$player2_wl = array_sum($p_wl);
							$player2_ll = array_sum($p_ll);
							
							$i ++;
							if ($i == 10) {
								break;
							}
						}
						$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
						$player1_balance = ($player1_ww + $player1_lw) - ($player1_wl + $player1_ll);
						$player2_balance = ($player2_ww + $player2_lw) - ($player2_wl + $player2_ll);
						$expected_balance_winner = 0;
						if ($player1_balance > $player2_balance) {
							$expected_balance_winner = 1;
						} else if ($player1_balance < $player2_balance) {
							$expected_balance_winner = 2;
						}
						if (($expected_winner == 1 && $expected_balance_winner == 1 && $player1_ranking < $player2_ranking) || ($expected_winner == 2 && $expected_balance_winner == 2 && $player2_ranking < $player1_ranking)) {
							$insert_data = [
								"event_id" 			=> $event_id,
								"expected_winner" 	=> $expected_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[2])
								->insert($insert_data);
						}
					}
					/* --- strategy 3 (BRW + GAH + RANK (Lower) & Balance + L10) ---  end  --- */

					/* --- strategy 4 (BRW + GAH + RANK Lower & Balance) + L20) --- start --- */
					if ($enable_robots[3] &&
						$player1_odd != NULL && $player2_odd != NULL &&
						// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
						$player1_ranking != 501 && $player2_ranking != 501
					) {
						$player1_gah = 0;
						$player1_brw = 0;
						$player1_ww = 0;
						$player1_lw = 0;
						$player1_wl = 0;
						$player1_ll = 0;
						$i = 0;
						foreach ($player1_events as $player1_event) {
							$p_brws = json_decode($player1_event->p_brw);
							foreach ($p_brws as $p_brw) {
								$player1_brw += array_sum($p_brw);
							}

							$p_gahs = json_decode($player1_event->p_gah);
							foreach ($p_gahs as $p_gah) {
								$player1_gah += array_sum($p_gah);
							}

							$p_ww = json_decode($player1_event->p_ww);
							$p_lw = json_decode($player1_event->p_lw);
							$p_wl = json_decode($player1_event->p_wl);
							$p_ll = json_decode($player1_event->p_ll);

							$player1_ww = array_sum($p_ww);
							$player1_lw = array_sum($p_lw);
							$player1_wl = array_sum($p_wl);
							$player1_ll = array_sum($p_ll);

							$i ++;
							if ($i == 20) {
								break;
							}
						}
			
						$player2_gah = 0;
						$player2_brw = 0;
						$player2_ww = 0;
						$player2_lw = 0;
						$player2_wl = 0;
						$player2_ll = 0;
						$i = 0;
						foreach ($player2_events as $player2_event) {
							$p_brws = json_decode($player2_event->p_brw);
							foreach ($p_brws as $p_brw) {
								$player2_brw += array_sum($p_brw);
							}
							
							$p_gahs = json_decode($player2_event->p_gah);
							foreach ($p_gahs as $p_gah) {
								$player2_gah += array_sum($p_gah);
							}

							$p_ww = json_decode($player2_event->p_ww);
							$p_lw = json_decode($player2_event->p_lw);
							$p_wl = json_decode($player2_event->p_wl);
							$p_ll = json_decode($player2_event->p_ll);

							$player2_ww = array_sum($p_ww);
							$player2_lw = array_sum($p_lw);
							$player2_wl = array_sum($p_wl);
							$player2_ll = array_sum($p_ll);
							
							$i ++;
							if ($i == 20) {
								break;
							}
						}
						$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
						$player1_balance = ($player1_ww + $player1_lw) - ($player1_wl + $player1_ll);
						$player2_balance = ($player2_ww + $player2_lw) - ($player2_wl + $player2_ll);
						$expected_balance_winner = 0;
						if ($player1_balance > $player2_balance) {
							$expected_balance_winner = 1;
						} else if ($player1_balance < $player2_balance) {
							$expected_balance_winner = 2;
						}
						if (($expected_winner == 1 && $expected_balance_winner == 1 && $player1_ranking < $player2_ranking) || ($expected_winner == 2 && $expected_balance_winner == 2 && $player2_ranking < $player1_ranking)) {
							$insert_data = [
								"event_id" 			=> $event_id,
								"expected_winner" 	=> $expected_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[3])
								->insert($insert_data);
						}
					}
					/* --- strategy 4 (BRW + GAH + RANK (Lower) & Balance + L20) ---  end  --- */

					/* --- strategy 5 (Balance) + L10) --- start --- */
					if ($enable_robots[4] &&
						$player1_odd != NULL && $player2_odd != NULL &&
						// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
						$player1_ranking != 501 && $player2_ranking != 501
					) {
						$player1_ww = 0;
						$player1_lw = 0;
						$player1_wl = 0;
						$player1_ll = 0;
						$i = 0;
						foreach ($player1_events as $player1_event) {
							$p_ww = json_decode($player1_event->p_ww);
							$p_lw = json_decode($player1_event->p_lw);
							$p_wl = json_decode($player1_event->p_wl);
							$p_ll = json_decode($player1_event->p_ll);

							$player1_ww = array_sum($p_ww);
							$player1_lw = array_sum($p_lw);
							$player1_wl = array_sum($p_wl);
							$player1_ll = array_sum($p_ll);

							$i ++;
							if ($i == 10) {
								break;
							}
						}
			
						$player2_ww = 0;
						$player2_lw = 0;
						$player2_wl = 0;
						$player2_ll = 0;
						$i = 0;
						foreach ($player2_events as $player2_event) {
							$p_ww = json_decode($player2_event->p_ww);
							$p_lw = json_decode($player2_event->p_lw);
							$p_wl = json_decode($player2_event->p_wl);
							$p_ll = json_decode($player2_event->p_ll);

							$player2_ww = array_sum($p_ww);
							$player2_lw = array_sum($p_lw);
							$player2_wl = array_sum($p_wl);
							$player2_ll = array_sum($p_ll);
							
							$i ++;
							if ($i == 10) {
								break;
							}
						}
						$player1_balance = ($player1_ww + $player1_lw) - ($player1_wl + $player1_ll);
						$player2_balance = ($player2_ww + $player2_lw) - ($player2_wl + $player2_ll);
						$expected_balance_winner = 0;
						if ($player1_balance > $player2_balance) {
							$expected_balance_winner = 1;
						} else if ($player1_balance < $player2_balance) {
							$expected_balance_winner = 2;
						}

						if (($expected_balance_winner == 1 && $player1_ranking < $player2_ranking) || ($expected_balance_winner == 2 && $player2_ranking < $player1_ranking)) {
							$insert_data = [
								"event_id" 			=> $event_id,
								"expected_winner" 	=> $expected_balance_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[4])
								->insert($insert_data);
						}
					}
					/* --- strategy 5 (Balance + L10) ---  end  --- */

					/* --- strategy 6 (Balance) + L20) --- start --- */
					if ($enable_robots[5] &&
						$player1_odd != NULL && $player2_odd != NULL &&
						// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
						$player1_ranking != 501 && $player2_ranking != 501
					) {
						$player1_ww = 0;
						$player1_lw = 0;
						$player1_wl = 0;
						$player1_ll = 0;
						$i = 0;
						foreach ($player1_events as $player1_event) {
							$p_ww = json_decode($player1_event->p_ww);
							$p_lw = json_decode($player1_event->p_lw);
							$p_wl = json_decode($player1_event->p_wl);
							$p_ll = json_decode($player1_event->p_ll);

							$player1_ww = array_sum($p_ww);
							$player1_lw = array_sum($p_lw);
							$player1_wl = array_sum($p_wl);
							$player1_ll = array_sum($p_ll);

							$i ++;
							if ($i == 20) {
								break;
							}
						}
			
						$player2_ww = 0;
						$player2_lw = 0;
						$player2_wl = 0;
						$player2_ll = 0;
						$i = 0;
						foreach ($player2_events as $player2_event) {
							$p_ww = json_decode($player2_event->p_ww);
							$p_lw = json_decode($player2_event->p_lw);
							$p_wl = json_decode($player2_event->p_wl);
							$p_ll = json_decode($player2_event->p_ll);

							$player2_ww = array_sum($p_ww);
							$player2_lw = array_sum($p_lw);
							$player2_wl = array_sum($p_wl);
							$player2_ll = array_sum($p_ll);
							
							$i ++;
							if ($i == 20) {
								break;
							}
						}
						$player1_balance = ($player1_ww + $player1_lw) - ($player1_wl + $player1_ll);
						$player2_balance = ($player2_ww + $player2_lw) - ($player2_wl + $player2_ll);
						$expected_balance_winner = 0;
						if ($player1_balance > $player2_balance) {
							$expected_balance_winner = 1;
						} else if ($player1_balance < $player2_balance) {
							$expected_balance_winner = 2;
						}

						if (($expected_balance_winner == 1 && $player1_ranking < $player2_ranking) || ($expected_balance_winner == 2 && $player2_ranking < $player1_ranking)) {
							$insert_data = [
								"event_id" 			=> $event_id,
								"expected_winner" 	=> $expected_balance_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[5])
								->insert($insert_data);
						}
					}
					/* --- strategy 6 (Balance + L20) ---  end  --- */

					/* --- strategy 7 Unranked (Balance) + L10) --- start --- */
					if ($enable_robots[6] &&
						$player1_odd != NULL && $player2_odd != NULL &&
						// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
						$player1_ranking == 501 && $player2_ranking == 501
					) {
						$player1_ww = 0;
						$player1_lw = 0;
						$player1_wl = 0;
						$player1_ll = 0;
						$i = 0;
						foreach ($player1_events as $player1_event) {
							$p_ww = json_decode($player1_event->p_ww);
							$p_lw = json_decode($player1_event->p_lw);
							$p_wl = json_decode($player1_event->p_wl);
							$p_ll = json_decode($player1_event->p_ll);

							$player1_ww = array_sum($p_ww);
							$player1_lw = array_sum($p_lw);
							$player1_wl = array_sum($p_wl);
							$player1_ll = array_sum($p_ll);

							$i ++;
							if ($i == 10) {
								break;
							}
						}
			
						$player2_ww = 0;
						$player2_lw = 0;
						$player2_wl = 0;
						$player2_ll = 0;
						$i = 0;
						foreach ($player2_events as $player2_event) {
							$p_ww = json_decode($player2_event->p_ww);
							$p_lw = json_decode($player2_event->p_lw);
							$p_wl = json_decode($player2_event->p_wl);
							$p_ll = json_decode($player2_event->p_ll);

							$player2_ww = array_sum($p_ww);
							$player2_lw = array_sum($p_lw);
							$player2_wl = array_sum($p_wl);
							$player2_ll = array_sum($p_ll);
							
							$i ++;
							if ($i == 10) {
								break;
							}
						}
						$player1_balance = ($player1_ww + $player1_lw) - ($player1_wl + $player1_ll);
						$player2_balance = ($player2_ww + $player2_lw) - ($player2_wl + $player2_ll);
						$expected_balance_winner = 0;
						if ($player1_balance > $player2_balance) {
							$expected_balance_winner = 1;
						} else if ($player1_balance < $player2_balance) {
							$expected_balance_winner = 2;
						}
						if (($expected_balance_winner == 1) || ($expected_balance_winner == 2)) {
							$insert_data = [
								"event_id" 			=> $event_id,
								"expected_winner" 	=> $expected_balance_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[6])
								->insert($insert_data);
						}
					}
					/* --- strategy 7 Unranked (Balance + L10) ---  end  --- */

					/* --- strategy 8 Unranked (Balance) + L20) --- start --- */
					if ($enable_robots[7] &&
						$player1_odd != NULL && $player2_odd != NULL &&
						// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
						$player1_ranking == 501 && $player2_ranking == 501
					) {
						$player1_ww = 0;
						$player1_lw = 0;
						$player1_wl = 0;
						$player1_ll = 0;
						$i = 0;
						foreach ($player1_events as $player1_event) {
							$p_ww = json_decode($player1_event->p_ww);
							$p_lw = json_decode($player1_event->p_lw);
							$p_wl = json_decode($player1_event->p_wl);
							$p_ll = json_decode($player1_event->p_ll);

							$player1_ww = array_sum($p_ww);
							$player1_lw = array_sum($p_lw);
							$player1_wl = array_sum($p_wl);
							$player1_ll = array_sum($p_ll);

							$i ++;
							if ($i == 20) {
								break;
							}
						}
			
						$player2_ww = 0;
						$player2_lw = 0;
						$player2_wl = 0;
						$player2_ll = 0;
						$i = 0;
						foreach ($player2_events as $player2_event) {
							$p_ww = json_decode($player2_event->p_ww);
							$p_lw = json_decode($player2_event->p_lw);
							$p_wl = json_decode($player2_event->p_wl);
							$p_ll = json_decode($player2_event->p_ll);

							$player2_ww = array_sum($p_ww);
							$player2_lw = array_sum($p_lw);
							$player2_wl = array_sum($p_wl);
							$player2_ll = array_sum($p_ll);
							
							$i ++;
							if ($i == 20) {
								break;
							}
						}
						$player1_balance = ($player1_ww + $player1_lw) - ($player1_wl + $player1_ll);
						$player2_balance = ($player2_ww + $player2_lw) - ($player2_wl + $player2_ll);
						$expected_balance_winner = 0;
						if ($player1_balance > $player2_balance) {
							$expected_balance_winner = 1;
						} else if ($player1_balance < $player2_balance) {
							$expected_balance_winner = 2;
						}

						if (($expected_balance_winner == 1) || ($expected_balance_winner == 2)) {
							$insert_data = [
								"event_id" 			=> $event_id,
								"expected_winner" 	=> $expected_balance_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[7])
								->insert($insert_data);
						}
					}
					/* --- strategy 8 Unranked (Balance + L20) ---  end  --- */

					$event_cnt ++;
					$log = "Ended events: " . $total_event_cnt . " / " . $event_cnt;
					Helper::printLog($log);
				}
			}
		}

	}
}
