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
      		$start_date = "20160901";
    	}
    	$period = new DatePeriod(
			new DateTime($start_date),
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
										->where('time_status', 3)
										->where('scores', '<>', NULL)
										->where('scores', '<>', '')
                                        ->where(function($query) use ($o_id) {
                                            $query->where('player1_id', $o_id)
                                            ->orWhere('player2_id', $o_id);
                                        });
            
            array_push($matches_array, $match_table_subquery->get());
        }
		$matches = Helper::getMatchesResponse($matches_array, $players);
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
	 * Get matches (add some fields)
	 * @param 	array $matches_data_array
	 * @param 	array $players
	 * @return 	array $matches
	 */
	public static function getMatchesResponse($matches_data_array, $players) {
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

		$match_table_name = "t_matches_" . substr($date, 0, 4) . "_" . substr($date, 4, 2);
		if (Schema::hasTable($match_table_name)) {
			$d = substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2);
			$times = Helper::getTimePeriod($d);
			$upcomingInplayData = DB::table($match_table_name)
									->whereIn("time_status", [0, 1])
									->where('time', '>=', $times[0])
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
				$enable_opponent_table_name = "t_bucket_opponents_" . $data->player1_id . "_" . $data->player2_id;
				array_push($enable_names, $enable_opponent_table_name);
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
						// foreach ($players_object as $player_object) {
						// 	DB::table($bucket_players_table)
						// 		->updateOrInsert(
						// 			[
						// 				"event_id" => $player_object['event_id'],
						// 			],
						// 			$player_object
						// 		);
						// }
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
						// foreach ($relation_data[1] as $opponent_object) {
						// 	DB::table($bucket_opponents_table)
						// 		->updateOrInsert(
						// 			[
						// 				"event_id" => $opponent_object['event_id'],
						// 				"o_id" => $opponent_object['o_id'],
						// 				"oo_id" => $opponent_object['oo_id'],
						// 			],
						// 			$opponent_object
						// 		);
						// }
					}
				}
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
											->where('time_status', 3)
											->where('scores', '<>', NULL)
											->where('scores', '<>', '')
											->where(function($query) use ($player_id) {
												$query->where('player1_id', $player_id)
												->orWhere('player2_id', $player_id);
											});
				
				array_push($matches_array, $match_table_subquery->get());
			}
	
			$matches = Helper::getMatchesResponse($matches_array, $players);
	
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

	/**
	 * Import the historical data of a specific day
	 * @param string $date
	 * @param bool $once
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
		$inplay_table = "t_inplay";
		$log = substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2) . ":  Start Time: " . date("Y-m-d H:i:s");
    	// Check Y-m table is exist or not
    	$match_table_name = "t_matches_" . substr($date, 0, 4) . "_" . substr($date, 4, 2);
		$event_ids = array();
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
				$table->index(['player1_id', 'player2_id']);
			});
    	} else {
			// get event ids of current date
			$d = substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2);
			$times = Helper::getTimePeriod($d);
			$db_events = DB::table($match_table_name)
							->select("event_id")
							->where('time', '>=', $times[0])
							->get();
			foreach ($db_events as $db_event) {
				array_push($event_ids, $db_event->event_id);
			}
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
			foreach ($matches as $event) {
				if (!in_array($event["id"], $event_ids)) {
					array_push($event_ids, $event["id"]);
				}
			}
		}

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
							DB::table($match_table_name)
								->updateOrInsert(
									[
										"player1_id" => $player1_id,
										"player2_id" => $player2_id,
									],
									$update_or_insert_array
							);
						} else {
							// Update or Insert into t_matches table
							DB::table($match_table_name)
								->updateOrInsert(
									["event_id" => (int)$event_id],
									$update_or_insert_array
							);
						}
						echo $time_status . "\n";
						if (!$once && $time_status == 1) {
							echo "here\n";
							$inplay_array = [
								"event_id"  => (int)$event_id,
								"ss"   		=> $event["ss"] ? trim($event["ss"]) : "",
								"points"   	=> $event["points"],
							];
							// Update or Insert into t_inplay table
							DB::table($inplay_table)
								->updateOrInsert(
									["event_id" => (int)$event_id],
									$inplay_array
								);
						}
					}
				}
			}
		}
		$execution_time = microtime(true) - $start_time;
		$log .= ("  End Time: " . date("Y-m-d H:i:s"));
		$log .= ("  ===>  Total History Count: " . $history_total_count . ", Execution Time:  " . $execution_time. " secs, Request Count: " . $request_count . "\n");
		echo $log;
		// file_put_contents("log.txt", $log, FILE_APPEND | LOCK_EX);
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
					->where("time_status", 3)
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
		 * Robot 41: BRW + GAH + ODD + L10
		 * Robot 42: BRW + GAH + ODD + L20
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
					return $a['time'] - $b['time'];
				});
				$player1_object = Helper::getUniqueMatchesByEventId($player1_object);
				$player1_objects_l_10 = array_slice($player1_object, 0, 10);
				$player1_object = Helper::getUniqueMatchesByEventId($player1_object);
				$player1_objects_l_20 = array_slice($player1_object, 0, 20);

				$player2_object = $relation_data[0][1];
				usort($player2_object, function($a, $b) {
					return $a['time'] - $b['time'];
				});
				$player2_object = Helper::getUniqueMatchesByEventId($player2_object);
				$player2_objects_l_10 = array_slice($player2_object, 0, 10);
				$player2_object = Helper::getUniqueMatchesByEventId($player2_object);
				$player2_objects_l_20 = array_slice($player2_object, 0, 20);
			} else { // inplay or upcoming so we can use pre-calculation table directly
				$table_name = "t_bucket_players_" . $player1_id . "_" . $player2_id;
				// for robot 41 and 43
				$player1_objects_l_10 = DB::table($table_name)
											->select("event_id", "p_brw", "p_gah")
											->where("p_id", $player1_id)
											->orderByDesc("time")
											->get();
				$player2_objects_l_10 = DB::table($table_name)
											->select("event_id", "p_brw", "p_gah")
											->where("p_id", $player2_id)
											->orderByDesc("time")
											->get();
				// for robot 42 and 44
				$player1_objects_l_20 = DB::table($table_name)
											->select("event_id", "p_brw", "p_gah")
											->where("p_id", $player1_id)
											->orderByDesc("time")
											->get();
				$player2_objects_l_20 = DB::table($table_name)
											->select("event_id", "p_brw", "p_gah")
											->where("p_id", $player2_id)
											->orderByDesc("time")
											->get();
				$player1_objects_l_10 = Helper::getUniqueMatchesByEventId($player1_objects_l_10, 10);
				$player1_objects_l_20 = Helper::getUniqueMatchesByEventId($player1_objects_l_20, 20);
				$player2_objects_l_10 = Helper::getUniqueMatchesByEventId($player2_objects_l_10, 10);
				$player2_objects_l_20 = Helper::getUniqueMatchesByEventId($player2_objects_l_20, 20);
			}

			if ($player_detail["player1_odd"] != NULL && $player_detail["player2_odd"] != NULL && $player_detail["player1_ranking"] != "-" && $player_detail["player2_ranking"] != "-") {
				// for robot 44 (BRW + GAH + RANK + L20)
				$winner_44 = Helper::robot4344($player1_objects_l_20, $player2_objects_l_20, 20, $player_detail);
				if ($winner_44 != 0) {
					$detail = [
						'event_id' => $match['event_id'],
						'winner'=> $winner_44,
						'type' => 44,
					];
					if (!in_array($match['event_id'], $event_ids)) {
						array_push($winners, $detail);
						array_push($event_ids, $match['event_id']);
					}
				}
				// for robot 43 (BRW + GAH + RANK + L10)
				$winner_43 = Helper::robot4344($player1_objects_l_10, $player2_objects_l_10, 10, $player_detail);
				if ($winner_43 != 0) {
					$detail = [
						'event_id' => $match['event_id'],
						'winner'=> $winner_43,
						'type' => 43,
					];
					if (!in_array($match['event_id'], $event_ids)) {
						array_push($winners, $detail);
						array_push($event_ids, $match['event_id']);
					}
				}
	
				// // for robot 42 (BRW + GAH + ODD + L20)
				// $winner_42 = Helper::robot4142($player1_objects_l_20, $player2_objects_l_20, 20, $player_detail);
				// if ($winner_42 != 0) {
				// 	$detail = [
				// 		'event_id' => $match['event_id'],
				// 		'winner'=> $winner_42,
				// 		'type' => 42,
				// 	];
				// 	if (!in_array($match['event_id'], $event_ids)) {
				// 		array_push($winners, $detail);
				// 		array_push($event_ids, $match['event_id']);
				// 	}
				// }
				// // for robot 41 (BRW + GAH + ODD + L10)
				// $winner_41 = Helper::robot4142($player1_objects_l_10, $player2_objects_l_10, 10, $player_detail);
				// if ($winner_41 != 0) {
				// 	$detail = [
				// 		'event_id' => $match['event_id'],
				// 		'winner'=> $winner_41,
				// 		'type' => 41,
				// 	];
				// 	if (!in_array($match['event_id'], $event_ids)) {
				// 		array_push($winners, $detail);
				// 		array_push($event_ids, $match['event_id']);
				// 	}
				// }
			}

			// // for robot 31 (BRW + GAH + unranked + L10)
			// if ($player_detail["player1_ranking"] == "-" && $player_detail["player2_ranking"] == "-") {
			// 	$winner_31 = Helper::robot31($player1_objects_l_10, $player2_objects_l_10, 10);
			// 	$detail = [
			// 		'event_id' => $match['event_id'],
			// 		'winner'=> $winner_31,
			// 		'type' => 31,
			// 	];
			// 	if (!in_array($match['event_id'], $event_ids)) {
			// 		array_push($winners, $detail);
			// 		array_push($event_ids, $match['event_id']);
			// 	}
			// }
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

	public static function robot4142($player1_events, $player2_events, $limit, $player_detail) {
		$player1_odd = $player_detail["player1_odd"];
		$player2_odd = $player_detail["player2_odd"];
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
		if (($expected_winner == 1 && $player1_odd < $player2_odd) || ($expected_winner == 2 && $player2_odd < $player1_odd)) {
			return $expected_winner;
		} else {
			return 0;
		}
	}

	public static function robot31($player1_events, $player2_events, $limit) {
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
		return $expected_winner;
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
			0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
			0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
			0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
			0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
			1, 1, 1, 1,
			0, 0, 0, 0,
			1, 1,
		];
		/* --- Create t_backtest_bots_brw_10 table --- start --- */
		$backtest_bots = [
			"t_backtest_bots_brw_10",
			"t_backtest_bots_brw_15",
			"t_backtest_bots_brw_20",
			"t_backtest_bots_brw_25",
			"t_backtest_bots_brw_30",
			"t_backtest_bots_brw_10_surface",
			"t_backtest_bots_brw_15_surface",
			"t_backtest_bots_brw_20_surface",
			"t_backtest_bots_brw_25_surface",
			"t_backtest_bots_brw_30_surface",

			"t_backtest_bots_brl_10",
			"t_backtest_bots_brl_15",
			"t_backtest_bots_brl_20",
			"t_backtest_bots_brl_25",
			"t_backtest_bots_brl_30",
			"t_backtest_bots_brl_10_surface",
			"t_backtest_bots_brl_15_surface",
			"t_backtest_bots_brl_20_surface",
			"t_backtest_bots_brl_25_surface",
			"t_backtest_bots_brl_30_surface",

			"t_backtest_bots_gah_10",
			"t_backtest_bots_gah_15",
			"t_backtest_bots_gah_20",
			"t_backtest_bots_gah_25",
			"t_backtest_bots_gah_30",
			"t_backtest_bots_gah_10_surface",
			"t_backtest_bots_gah_15_surface",
			"t_backtest_bots_gah_20_surface",
			"t_backtest_bots_gah_25_surface",
			"t_backtest_bots_gah_30_surface",

			"t_backtest_bots_brw_gah_10",
			"t_backtest_bots_brw_gah_15",
			"t_backtest_bots_brw_gah_20",
			"t_backtest_bots_brw_gah_25",
			"t_backtest_bots_brw_gah_30",
			"t_backtest_bots_brw_gah_10_surface",
			"t_backtest_bots_brw_gah_15_surface",
			"t_backtest_bots_brw_gah_20_surface",
			"t_backtest_bots_brw_gah_25_surface",
			"t_backtest_bots_brw_gah_30_surface",

			// Ranked --- start ---
			"t_backtest_bots_brw_gah_odd_10",
			"t_backtest_bots_brw_gah_odd_20",

			"t_backtest_bots_brw_gah_rank_10",
			"t_backtest_bots_brw_gah_rank_20",
			// Ranked --- end ---

			"t_backtest_bots_brw_gah_rank_h_10",
			"t_backtest_bots_brw_gah_rank_h_20",

			"t_backtest_bots_brw_gah_rank_h_mon_tue_10",
			"t_backtest_bots_brw_gah_rank_h_mon_tue_20",

			// Unranked --- start ---
			"t_backtest_bots_brw_gah_odd_unranked_10",
			"t_backtest_bots_brw_gah_odd_unranked_20",
			// Unranked --- end ---
		];

		if ($enable_robots[0]) {
			Schema::dropIfExists($backtest_bots[0]);
			Schema::create($backtest_bots[0], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[1]) {
			Schema::dropIfExists($backtest_bots[1]);
			Schema::create($backtest_bots[1], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[2]) {
			Schema::dropIfExists($backtest_bots[2]);
			Schema::create($backtest_bots[2], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[3]) {
			Schema::dropIfExists($backtest_bots[3]);
			Schema::create($backtest_bots[3], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[4]) {
			Schema::dropIfExists($backtest_bots[4]);
			Schema::create($backtest_bots[4], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[5]) {
			Schema::dropIfExists($backtest_bots[5]);
			Schema::create($backtest_bots[5], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[6]) {
			Schema::dropIfExists($backtest_bots[6]);
			Schema::create($backtest_bots[6], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[7]) {
			Schema::dropIfExists($backtest_bots[7]);
			Schema::create($backtest_bots[7], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[8]) {
			Schema::dropIfExists($backtest_bots[8]);
			Schema::create($backtest_bots[8], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[9]) {
			Schema::dropIfExists($backtest_bots[9]);
			Schema::create($backtest_bots[9], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		/* --- BRL --- */
		if ($enable_robots[10]) {
			Schema::dropIfExists($backtest_bots[10]);
			Schema::create($backtest_bots[10], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[11]) {
			Schema::dropIfExists($backtest_bots[11]);
			Schema::create($backtest_bots[11], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[12]) {
			Schema::dropIfExists($backtest_bots[12]);
			Schema::create($backtest_bots[12], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[13]) {
			Schema::dropIfExists($backtest_bots[13]);
			Schema::create($backtest_bots[13], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[14]) {
			Schema::dropIfExists($backtest_bots[14]);
			Schema::create($backtest_bots[14], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[15]) {
			Schema::dropIfExists($backtest_bots[15]);
			Schema::create($backtest_bots[15], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[16]) {
			Schema::dropIfExists($backtest_bots[16]);
			Schema::create($backtest_bots[16], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[17]) {
			Schema::dropIfExists($backtest_bots[17]);
			Schema::create($backtest_bots[17], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[18]) {
			Schema::dropIfExists($backtest_bots[18]);
			Schema::create($backtest_bots[18], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[19]) {
			Schema::dropIfExists($backtest_bots[19]);
			Schema::create($backtest_bots[19], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brl");
				$table->integer("p2_brl");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		/* --- GAH --- */
		if ($enable_robots[20]) {
			Schema::dropIfExists($backtest_bots[20]);
			Schema::create($backtest_bots[20], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[21]) {
			Schema::dropIfExists($backtest_bots[21]);
			Schema::create($backtest_bots[21], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[22]) {
			Schema::dropIfExists($backtest_bots[22]);
			Schema::create($backtest_bots[22], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[23]) {
			Schema::dropIfExists($backtest_bots[23]);
			Schema::create($backtest_bots[23], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[24]) {
			Schema::dropIfExists($backtest_bots[24]);
			Schema::create($backtest_bots[24], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[25]) {
			Schema::dropIfExists($backtest_bots[25]);
			Schema::create($backtest_bots[25], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[26]) {
			Schema::dropIfExists($backtest_bots[26]);
			Schema::create($backtest_bots[26], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[27]) {
			Schema::dropIfExists($backtest_bots[27]);
			Schema::create($backtest_bots[27], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[28]) {
			Schema::dropIfExists($backtest_bots[28]);
			Schema::create($backtest_bots[28], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[29]) {
			Schema::dropIfExists($backtest_bots[29]);
			Schema::create($backtest_bots[29], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// BRW + GAH
		if ($enable_robots[30]) {
			Schema::dropIfExists($backtest_bots[30]);
			Schema::create($backtest_bots[30], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[31]) {
			Schema::dropIfExists($backtest_bots[31]);
			Schema::create($backtest_bots[31], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[32]) {
			Schema::dropIfExists($backtest_bots[32]);
			Schema::create($backtest_bots[32], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[33]) {
			Schema::dropIfExists($backtest_bots[33]);
			Schema::create($backtest_bots[33], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[34]) {
			Schema::dropIfExists($backtest_bots[34]);
			Schema::create($backtest_bots[34], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[35]) {
			Schema::dropIfExists($backtest_bots[35]);
			Schema::create($backtest_bots[35], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[36]) {
			Schema::dropIfExists($backtest_bots[36]);
			Schema::create($backtest_bots[36], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[37]) {
			Schema::dropIfExists($backtest_bots[37]);
			Schema::create($backtest_bots[37], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[38]) {
			Schema::dropIfExists($backtest_bots[38]);
			Schema::create($backtest_bots[38], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[39]) {
			Schema::dropIfExists($backtest_bots[39]);
			Schema::create($backtest_bots[39], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// BRW + GAH + ODD (Ranked)
		if ($enable_robots[40]) {
			Schema::dropIfExists($backtest_bots[40]);
			Schema::create($backtest_bots[40], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->float("p1_odd");
				$table->float("p2_odd");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[41]) {
			Schema::dropIfExists($backtest_bots[41]);
			Schema::create($backtest_bots[41], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->float("p1_odd");
				$table->float("p2_odd");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// BRW + GAH + RANK (Lower) (Ranked)
		if ($enable_robots[42]) {
			Schema::dropIfExists($backtest_bots[42]);
			Schema::create($backtest_bots[42], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("p1_rank");
				$table->integer("p2_rank");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[43]) {
			Schema::dropIfExists($backtest_bots[43]);
			Schema::create($backtest_bots[43], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("p1_rank");
				$table->integer("p2_rank");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// BRW + GAH + RANK (Higher) with ODD (all range)
		if ($enable_robots[44]) {
			Schema::dropIfExists($backtest_bots[44]);
			Schema::create($backtest_bots[44], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("p1_rank");
				$table->integer("p2_rank");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[45]) {
			Schema::dropIfExists($backtest_bots[45]);
			Schema::create($backtest_bots[45], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("p1_rank");
				$table->integer("p2_rank");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// BRW + GAH + RANK (Higher) with ODD (Monday and Tuesday)
		if ($enable_robots[46]) {
			Schema::dropIfExists($backtest_bots[46]);
			Schema::create($backtest_bots[46], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("p1_rank");
				$table->integer("p2_rank");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[47]) {
			Schema::dropIfExists($backtest_bots[47]);
			Schema::create($backtest_bots[47], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->integer("p1_rank");
				$table->integer("p2_rank");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}

		// BRW + GAH + ODD (Unranked)
		if ($enable_robots[48]) {
			Schema::dropIfExists($backtest_bots[48]);
			Schema::create($backtest_bots[48], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->float("p1_odd");
				$table->float("p2_odd");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		if ($enable_robots[49]) {
			Schema::dropIfExists($backtest_bots[49]);
			Schema::create($backtest_bots[49], function($table) {
				$table->increments("id");
				$table->integer("event_id");
				$table->integer("p1_brw");
				$table->integer("p2_brw");
				$table->integer("p1_gah");
				$table->integer("p2_gah");
				$table->float("p1_odd");
				$table->float("p2_odd");
				$table->integer("expected_winner");
				$table->integer("real_winner");
			});
		}
		
		// get events
		$events = DB::table("t_matches_2021_03")
					->where("time_status", 3)
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

			// if ($event->scores != "" &&
			// 	in_array($player1_id, $player_ids) && in_array($player2_id, $player_ids) &&
			// 	$player1_odd != NULL && $player2_odd != NULL) {
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
											->select("event_id", "p_brw", "p_brl", "p_gah")
											->where("p_id", $player1_id)
											->orderByDesc("time")
											->get();
					$player1_events = Helper::getUniqueMatchesByEventId($player1_events, 30);

					$player1_surface_events = DB::table($backtest_players_table)
													->select("event_id", "p_brw", "p_brl", "p_gah")
													->where("p_id", $player1_id)
													->where("surface", $surface)
													->orderByDesc("time")
													->get();
					$player1_surface_events = Helper::getUniqueMatchesByEventId($player1_surface_events, 30);

					$player2_events = DB::table($backtest_players_table)
											->select("event_id", "p_brw", "p_brl", "p_gah")
											->where("p_id", $player2_id)
											->orderByDesc("time")
											->get();
					$player2_events = Helper::getUniqueMatchesByEventId($player2_events, 30);

					$player2_surface_events = DB::table($backtest_players_table)
													->select("event_id", "p_brw", "p_brl", "p_gah")
													->where("p_id", $player2_id)
													->where("surface", $surface)
													->orderByDesc("time")
													->get();
					$player1_surface_events = Helper::getUniqueMatchesByEventId($player1_surface_events, 30);

					// /* --- strategy 1 (BRW + L10) --- start --- */
					// if ($enable_robots[0]) {
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[0])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 1 (BRW + L10) ---  end  --- */
	
					// /* --- strategy 2 (BRW + L15) --- start --- */
					// if ($enable_robots[1]) {
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[1])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 2 (BRW + L15) ---  end  --- */
	
					// /* --- strategy 3 (BRW + L20) --- start --- */
					// if ($enable_robots[2]) {
					// 	$player1_brw = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[2])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 3 (BRW + L20) ---  end  --- */
	
					// /* --- strategy 4 (BRW + L25) --- start --- */
					// if ($enable_robots[3]) {
					// 	$player1_brw = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[3])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 4 (BRW + L25) ---  end  --- */
	
					// /* --- strategy 5 (BRW + L30) --- start --- */
					// if ($enable_robots[4]) {
					// 	$player1_brw = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[4])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 5 (BRW + L20) ---  end  --- */
	
					// /* --- strategy 6 (BRW + surface + L10) --- start --- */
					// if ($enable_robots[5] && $surface != NULL) {
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[5])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 6 (BRW + surface + L10) ---  end  --- */
	
					// /* --- strategy 7 (BRW + surface + L15) --- start --- */
					// if ($enable_robots[6] && $surface != NULL) {
					// 	$player1_brw = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[6])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 7 (BRW + surface + L15) ---  end  --- */
	
					// /* --- strategy 8 (BRW + surface + L20) --- start --- */
					// if ($enable_robots[7] && $surface != NULL) {
					// 	$player1_brw = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[7])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 8 (BRW + surface + L20) ---  end  --- */
	
					// /* --- strategy 9 (BRW + surface + L25) --- start --- */
					// if ($enable_robots[8] && $surface != NULL) {
					// 	$player1_brw = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[8])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 9 (BRW + surface + L25) ---  end  --- */
	
					// /* --- strategy 10 (BRW + surface + L30) --- start --- */
					// if ($enable_robots[9] && $surface != NULL) {
					// 	$player1_brw = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"expected_winner" 	=> $player1_brw >= $player2_brw ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[9])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 10 (BRW + surface + L30) ---  end  --- */
	
	
					// /* --- strategy 11 (BRL + L10) --- start --- */
					// if ($enable_robots[10]) {
					// 	$player1_brl = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[10])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 11 (BRL + L10) ---  end  --- */
	
					// /* --- strategy 12 (BRL + L15) --- start --- */
					// if ($enable_robots[11]) {
					// 	$player1_brl = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[11])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 12 (BRL + L15) ---  end  --- */
	
					// /* --- strategy 13 (BRL + L20) --- start --- */
					// if ($enable_robots[12]) {
					// 	$player1_brl = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[12])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 13 (BRL + L20) ---  end  --- */
	
					// /* --- strategy 14 (BRL + L25) --- start --- */
					// if ($enable_robots[13]) {
					// 	$player1_brl = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[13])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 14 (BRL + L25) ---  end  --- */
	
					// /* --- strategy 15 (BRL + L30) --- start --- */
					// if ($enable_robots[14]) {
					// 	$player1_brl = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[14])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 15 (BRL + L20) ---  end  --- */
	
					// /* --- strategy 16 (BRL + surface + L10) --- start --- */
					// if ($enable_robots[15] && $surface != NULL) {
					// 	$player1_brl = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[15])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 16 (BRL + surface + L10) ---  end  --- */
	
					// /* --- strategy 17 (BRL + surface + L15) --- start --- */
					// if ($enable_robots[16] && $surface != NULL) {
					// 	$player1_brl = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[16])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 17 (BRL + surface + L15) ---  end  --- */
	
					// /* --- strategy 18 (BRL + surface + L20) --- start --- */
					// if ($enable_robots[17] && $surface != NULL) {
					// 	$player1_brl = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[17])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 18 (BRL + surface + L20) ---  end  --- */
	
					// /* --- strategy 19 (BRL + surface + L25) --- start --- */
					// if ($enable_robots[18] && $surface != NULL) {
					// 	$player1_brl = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[18])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 19 (BRL + surface + L25) ---  end  --- */
	
					// /* --- strategy 20 (BRL + surface + L30) --- start --- */
					// if ($enable_robots[19] && $surface != NULL) {
					// 	$player1_brl = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brls = json_decode($player1_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player1_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_brl = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brls = json_decode($player2_event->p_brl);
					// 		foreach ($p_brls as $p_brl) {
					// 			$player2_brl += array_sum($p_brl);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brl" 			=> $player1_brl,
					// 		"p2_brl" 			=> $player2_brl,
					// 		"expected_winner" 	=> $player1_brl < $player2_brl ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[19])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 20 (BRL + surface + L30) ---  end  --- */
	
	
					// /* --- strategy 21 (GAH + L10) --- start --- */
					// if ($enable_robots[20]) {
					// 	$player1_gah = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[20])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 21 (GAH + L10) ---  end  --- */
	
					// /* --- strategy 22 (GAH + L15) --- start --- */
					// if ($enable_robots[21]) {
					// 	$player1_gah = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[21])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 22 (GAH + L15) ---  end  --- */
	
					// /* --- strategy 23 (GAH + L20) --- start --- */
					// if ($enable_robots[22]) {
					// 	$player1_gah = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[22])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 23 (GAH + L20) ---  end  --- */
	
					// /* --- strategy 24 (GAH + L25) --- start --- */
					// if ($enable_robots[23]) {
					// 	$player1_gah = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[23])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 24 (GAH + L25) ---  end  --- */
	
					// /* --- strategy 25 (GAH + L30) --- start --- */
					// if ($enable_robots[24]) {
					// 	$player1_gah = 0;	
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[24])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 25 (GAH + L20) ---  end  --- */
	
					// /* --- strategy 26 (GAH + surface + L10) --- start --- */
					// if ($enable_robots[25] && $surface != NULL) {
					// 	$player1_gah = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[25])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 26 (GAH + surface + L10) ---  end  --- */
	
					// /* --- strategy 27 (GAH + surface + L15) --- start --- */
					// if ($enable_robots[26] && $surface != NULL) {
					// 	$player1_gah = 0;
					// 	$i = 0;	
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[26])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 27 (GAH + surface + L15) ---  end  --- */
	
					// /* --- strategy 28 (GAH + surface + L20) --- start --- */
					// if ($enable_robots[27] && $surface != NULL) {
					// 	$player1_gah = 0;
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[27])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 28 (GAH + surface + L20) ---  end  --- */
	
					// /* --- strategy 29 (GAH + surface + L25) --- start --- */
					// if ($enable_robots[28] && $surface != NULL) {
					// 	$player1_gah = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[28])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 29 (GAH + surface + L25) ---  end  --- */
	
					// /* --- strategy 30 (GAH + surface + L30) --- start --- */
					// if ($enable_robots[29] && $surface != NULL) {
					// 	$player1_gah = 0;	
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $player1_gah >= $player2_gah ? 1 : 2,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[29])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 30 (GAH + surface + L30) ---  end  --- */
	

					// /* --- strategy 31 (BRW + GAH + L10) --- start --- */
					// if ($enable_robots[30]) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[30])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 31 (BRW + GAH + L10) ---  end  --- */

					// /* --- strategy 32 (BRW + GAH + L15) --- start --- */
					// if ($enable_robots[31]) {
					// 	$player1_gah = 0;	
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[31])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 32 (BRW + GAH + L15) ---  end  --- */

					// /* --- strategy 33 (BRW + GAH + L20) --- start --- */
					// if ($enable_robots[32]) {
					// 	$player1_gah = 0;	
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[32])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 33 (BRW + GAH + L20) ---  end  --- */

					// /* --- strategy 34 (BRW + GAH + L25) --- start --- */
					// if ($enable_robots[33]) {
					// 	$player1_gah = 0;	
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[33])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 34 (BRW + GAH + L25) ---  end  --- */

					// /* --- strategy 35 (BRW + GAH + L30) --- start --- */
					// if ($enable_robots[34]) {
					// 	$player1_gah = 0;	
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[34])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 35 (BRW + GAH + L20) ---  end  --- */

					// /* --- strategy 36 (BRW + GAH + surface + L10) --- start --- */
					// if ($enable_robots[35] && $surface != NULL) {
					// 	$player1_gah = 0;	
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[35])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 36 (BRW + GAH + surface + L10) ---  end  --- */

					// /* --- strategy 37 (BRW + GAH + surface + L15) --- start --- */
					// if ($enable_robots[36] && $surface != NULL) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;	
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 15) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[36])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 37 (BRW + GAH + surface + L15) ---  end  --- */

					// /* --- strategy 38 (BRW + GAH + surface + L20) --- start --- */
					// if ($enable_robots[37] && $surface != NULL) {
					// 	$player1_gah = 0;	
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[37])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 38 (BRW + GAH + surface + L20) ---  end  --- */

					// /* --- strategy 39 (BRW + GAH + surface + L25) --- start --- */
					// if ($enable_robots[38] && $surface != NULL) {
					// 	$player1_gah = 0;	
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 25) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[38])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 39 (BRW + GAH + surface + L25) ---  end  --- */

					// /* --- strategy 40 (BRW + GAH + surface + L30) --- start --- */
					// if ($enable_robots[39] && $surface != NULL) {
					// 	$player1_gah = 0;	
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_surface_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_surface_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 30) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	$insert_data = [
					// 		"event_id" 			=> $event_id,
					// 		"p1_brw" 			=> $player1_brw,
					// 		"p2_brw" 			=> $player2_brw,
					// 		"p1_gah" 			=> $player1_gah,
					// 		"p2_gah" 			=> $player2_gah,
					// 		"expected_winner" 	=> $expected_winner,
					// 		"real_winner"		=> $real_winner,
					// 	];
					// 	DB::table($backtest_bots[39])
					// 		->insert($insert_data);
					// }
					// /* --- strategy 40 (BRW + GAH + surface + L30) ---  end  --- */

					// /* --- strategy 41 (BRW + GAH + ODD + L10) --- start --- */
					// if ($enable_robots[40] &&
					// 	$player1_odd != NULL && $player2_odd != NULL &&
					// 	// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
					// 	$player1_ranking != 501 && $player2_ranking != 501
					// ) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	if (($expected_winner == 1 && $player1_odd < $player2_odd) || ($expected_winner == 2 && $player2_odd < $player1_odd)) {
					// 		$insert_data = [
					// 			"event_id" 			=> $event_id,
					// 			"p1_brw" 			=> $player1_brw,
					// 			"p2_brw" 			=> $player2_brw,
					// 			"p1_gah" 			=> $player1_gah,
					// 			"p2_gah" 			=> $player2_gah,
					// 			"p1_odd" 			=> $player1_odd,
					// 			"p2_odd" 			=> $player2_odd,
					// 			"expected_winner" 	=> $expected_winner,
					// 			"real_winner"		=> $real_winner,
					// 		];
					// 		DB::table($backtest_bots[40])
					// 			->insert($insert_data);
					// 	}
					// }
					// /* --- strategy 41 (BRW + GAH + ODD + L10) ---  end  --- */

					// /* --- strategy 42 (BRW + GAH + ODD + L20) --- start --- */
					// if ($enable_robots[41] &&
					// 	$player1_odd != NULL && $player2_odd != NULL &&
					// 	// (($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
					// 	$player1_ranking != 501 && $player2_ranking != 501
					// ) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	if (($expected_winner == 1 && $player1_odd < $player2_odd) || ($expected_winner == 2 && $player2_odd < $player1_odd)) {
					// 		$insert_data = [
					// 			"event_id" 			=> $event_id,
					// 			"p1_brw" 			=> $player1_brw,
					// 			"p2_brw" 			=> $player2_brw,
					// 			"p1_gah" 			=> $player1_gah,
					// 			"p2_gah" 			=> $player2_gah,
					// 			"p1_odd" 			=> $player1_odd,
					// 			"p2_odd" 			=> $player2_odd,
					// 			"expected_winner" 	=> $expected_winner,
					// 			"real_winner"		=> $real_winner,
					// 		];
					// 		DB::table($backtest_bots[41])
					// 			->insert($insert_data);
					// 	}
					// }
					/* --- strategy 42 (BRW + GAH + ODD + L20) ---  end  --- */

					/* --- strategy 43 (BRW + GAH + RANK + L10) --- start --- */
					if ($enable_robots[42] &&
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
								"p1_brw" 			=> $player1_brw,
								"p2_brw" 			=> $player2_brw,
								"p1_gah" 			=> $player1_gah,
								"p2_gah" 			=> $player2_gah,
								"p1_rank" 			=> $player1_ranking,
								"p2_rank" 			=> $player2_ranking,
								"expected_winner" 	=> $expected_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[42])
								->insert($insert_data);
						}
					}
					/* --- strategy 43 (BRW + GAH + RANK + L10) ---  end  --- */

					/* --- strategy 44 (BRW + GAH + RANK Lower) + L20) --- start --- */
					if ($enable_robots[43] &&
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
								"p1_brw" 			=> $player1_brw,
								"p2_brw" 			=> $player2_brw,
								"p1_gah" 			=> $player1_gah,
								"p2_gah" 			=> $player2_gah,
								"p1_rank" 			=> $player1_ranking,
								"p2_rank" 			=> $player2_ranking,
								"expected_winner" 	=> $expected_winner,
								"real_winner"		=> $real_winner,
							];
							DB::table($backtest_bots[43])
								->insert($insert_data);
						}
					}
					/* --- strategy 44 (BRW + GAH + RANK (Lower) + L20) ---  end  --- */

					// /* --- strategy 45 (BRW + GAH + RANK (Higher) + ODD + L10) --- start --- */
					// if ($enable_robots[44] && $player1_odd != NULL && $player2_odd != NULL) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	if (($expected_winner == 1 && $player1_ranking > $player2_ranking) || ($expected_winner == 2 && $player2_ranking > $player1_ranking)) {
					// 		$insert_data = [
					// 			"event_id" 			=> $event_id,
					// 			"p1_brw" 			=> $player1_brw,
					// 			"p2_brw" 			=> $player2_brw,
					// 			"p1_gah" 			=> $player1_gah,
					// 			"p2_gah" 			=> $player2_gah,
					// 			"p1_rank" 			=> $player1_ranking,
					// 			"p2_rank" 			=> $player2_ranking,
					// 			"expected_winner" 	=> $expected_winner,
					// 			"real_winner"		=> $real_winner,
					// 		];
					// 		DB::table($backtest_bots[44])
					// 			->insert($insert_data);
					// 	}
					// }
					// /* --- strategy 44 (BRW + GAH + RANK (Higher) + ODD + L10) ---  end  --- */

					// /* --- strategy 46 (BRW + GAH + RANK (Higher) + ODD + L20) --- start --- */
					// if ($enable_robots[45] && $player1_odd != NULL && $player2_odd != NULL) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	if (($expected_winner == 1 && $player1_ranking > $player2_ranking) || ($expected_winner == 2 && $player2_ranking > $player1_ranking)) {
					// 		$insert_data = [
					// 			"event_id" 			=> $event_id,
					// 			"p1_brw" 			=> $player1_brw,
					// 			"p2_brw" 			=> $player2_brw,
					// 			"p1_gah" 			=> $player1_gah,
					// 			"p2_gah" 			=> $player2_gah,
					// 			"p1_rank" 			=> $player1_ranking,
					// 			"p2_rank" 			=> $player2_ranking,
					// 			"expected_winner" 	=> $expected_winner,
					// 			"real_winner"		=> $real_winner,
					// 		];
					// 		DB::table($backtest_bots[45])
					// 			->insert($insert_data);
					// 	}
					// }
					// /* --- strategy 46 (BRW + GAH + RANK (Higher) + ODD + L20) ---  end  --- */

					// /* --- strategy 47 (BRW + GAH + RANK (Higher) + ODD + L10 + Monday/Tuesday) --- start --- */
					// if ($enable_robots[46] && $player1_odd != NULL && $player2_odd != NULL && ($weekday == 1 || $weekday == 2)) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	if (($expected_winner == 1 && $player1_ranking > $player2_ranking) || ($expected_winner == 2 && $player2_ranking > $player1_ranking)) {
					// 		$insert_data = [
					// 			"event_id" 			=> $event_id,
					// 			"p1_brw" 			=> $player1_brw,
					// 			"p2_brw" 			=> $player2_brw,
					// 			"p1_gah" 			=> $player1_gah,
					// 			"p2_gah" 			=> $player2_gah,
					// 			"p1_rank" 			=> $player1_ranking,
					// 			"p2_rank" 			=> $player2_ranking,
					// 			"expected_winner" 	=> $expected_winner,
					// 			"real_winner"		=> $real_winner,
					// 		];
					// 		DB::table($backtest_bots[46])
					// 			->insert($insert_data);
					// 	}
					// }
					// /* --- strategy 47 (BRW + GAH + RANK (Higher) + ODD + L10 + Monday/Tuesday) ---  end  --- */

					// /* --- strategy 48 (BRW + GAH + RANK (Higher) + ODD + L20 + Monday/Tuesday) --- start --- */
					// if ($enable_robots[47] && $player1_odd != NULL && $player2_odd != NULL && ($weekday == 1 || $weekday == 2)) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	if (($expected_winner == 1 && $player1_ranking > $player2_ranking) || ($expected_winner == 2 && $player2_ranking > $player1_ranking)) {
					// 		$insert_data = [
					// 			"event_id" 			=> $event_id,
					// 			"p1_brw" 			=> $player1_brw,
					// 			"p2_brw" 			=> $player2_brw,
					// 			"p1_gah" 			=> $player1_gah,
					// 			"p2_gah" 			=> $player2_gah,
					// 			"p1_rank" 			=> $player1_ranking,
					// 			"p2_rank" 			=> $player2_ranking,
					// 			"expected_winner" 	=> $expected_winner,
					// 			"real_winner"		=> $real_winner,
					// 		];
					// 		DB::table($backtest_bots[47])
					// 			->insert($insert_data);
					// 	}
					// }
					// /* --- strategy 48 (BRW + GAH + RANK (Higher) + ODD + L20 + Monday/Tuesday) ---  end  --- */

					// /* --- strategy 49 (BRW + GAH + ODD + L10) unranked --- start --- */
					// if ($enable_robots[48] &&
					// 	$player1_odd != NULL && $player2_odd != NULL &&
					// 	(($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
					// 	$player1_ranking == 501 && $player2_ranking == 501
					// ) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 10) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	if (($expected_winner == 1 && $player1_odd < $player2_odd) || ($expected_winner == 2 && $player2_odd < $player1_odd)) {
					// 		$insert_data = [
					// 			"event_id" 			=> $event_id,
					// 			"p1_brw" 			=> $player1_brw,
					// 			"p2_brw" 			=> $player2_brw,
					// 			"p1_gah" 			=> $player1_gah,
					// 			"p2_gah" 			=> $player2_gah,
					// 			"p1_odd" 			=> $player1_odd,
					// 			"p2_odd" 			=> $player2_odd,
					// 			"expected_winner" 	=> $expected_winner,
					// 			"real_winner"		=> $real_winner,
					// 		];
					// 		DB::table($backtest_bots[48])
					// 			->insert($insert_data);
					// 	}
					// }
					// /* --- strategy 49 (BRW + GAH + ODD + L10) unranked ---  end  --- */

					// /* --- strategy 50 (BRW + GAH + ODD + L20) unranked --- start --- */
					// if ($enable_robots[49] &&
					// 	$player1_odd != NULL && $player2_odd != NULL &&
					// 	(($player1_odd >= 1.7 && $player1_odd <= 2) || ($player2_odd >= 1.7 && $player2_odd <= 2)) &&
					// 	$player1_ranking == 501 && $player2_ranking == 501
					// ) {
					// 	$player1_gah = 0;
					// 	$player1_brw = 0;
					// 	$i = 0;
					// 	foreach ($player1_events as $player1_event) {
					// 		$p_brws = json_decode($player1_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player1_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player1_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player1_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
			
					// 	$player2_gah = 0;
					// 	$player2_brw = 0;
					// 	$i = 0;
					// 	foreach ($player2_events as $player2_event) {
					// 		$p_brws = json_decode($player2_event->p_brw);
					// 		foreach ($p_brws as $p_brw) {
					// 			$player2_brw += array_sum($p_brw);
					// 		}
					// 		$p_gahs = json_decode($player2_event->p_gah);
					// 		foreach ($p_gahs as $p_gah) {
					// 			$player2_gah += array_sum($p_gah);
					// 		}
					// 		$i ++;
					// 		if ($i == 20) {
					// 			break;
					// 		}
					// 	}
					// 	$expected_winner = ($player1_brw + $player1_gah) >= ($player2_brw + $player2_gah) ? 1 : 2;
					// 	if (($expected_winner == 1 && $player1_odd < $player2_odd) || ($expected_winner == 2 && $player2_odd < $player1_odd)) {
					// 		$insert_data = [
					// 			"event_id" 			=> $event_id,
					// 			"p1_brw" 			=> $player1_brw,
					// 			"p2_brw" 			=> $player2_brw,
					// 			"p1_gah" 			=> $player1_gah,
					// 			"p2_gah" 			=> $player2_gah,
					// 			"p1_odd" 			=> $player1_odd,
					// 			"p2_odd" 			=> $player2_odd,
					// 			"expected_winner" 	=> $expected_winner,
					// 			"real_winner"		=> $real_winner,
					// 		];
					// 		DB::table($backtest_bots[49])
					// 			->insert($insert_data);
					// 	}
					// }
					// /* --- strategy 50 (BRW + GAH + ODD + L20) unranked ---  end  --- */

					$event_cnt ++;
					$log = "Ended events: " . $total_event_cnt . " / " . $event_cnt;
					Helper::printLog($log);
				}
			}
		}

	}
}
