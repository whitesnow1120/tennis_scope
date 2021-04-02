<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Helpers\Helper;

class TennisController extends Controller
{
    public function index()
    {
        echo "index";
    }

    public function getMatchesResponse($matches_data_array, $players) {
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

    public function getMatches($date, $type) {
        if (!$date) {
            $date = date('Y-m-d', time());
        }
        $players = DB::table("t_players")->get();
        $match_table_name = "t_matches_" . substr($date, 0, 4) . "_" . substr($date, 5, 2);
        if (!Schema::hasTable($match_table_name)) {
            return array();
        }
        if ($type == 0) { // upcoming
            $matches_data = DB::table($match_table_name)
                            ->where('time_status', 0)
                            ->orderBy('time')
                            ->get();        
        } elseif ($type == 1) { // in play
            $matches_data = DB::table($match_table_name)
                            ->where('time_status', 1)
                            ->orderBy('time')
                            ->get();
        } elseif ($type == 3) { // ended
            $times = Helper::getTimePeriod($date);
            $matches_data = DB::table($match_table_name)
                            ->where('time_status', 3)
                            ->whereBetween('time', [$times[0], $times[1]])
                            ->orderBy('time')
                            ->get();
            $matches_array = array();
            array_push($matches_array, $matches_data);
            return $this->getMatchesResponse($matches_array, $players);
        }

        $upcoming_inplay_data = array();
        if ($type == 0 || $type == 1) {
            foreach ($matches_data as $data) {
                $player1_id = $data->player1_id;
                $player2_id = $data->player2_id;
                $enable_bucket_suffix = [$player1_id . '_' . $player2_id, $player2_id . '_' . $player1_id];
                // check pre-calculation table is exist or not
                $enable_bucket_table = array();
                $enable_bucket_table[0] = "t_bucket_players_" . $enable_bucket_suffix[0];
                $enable_bucket_table[1] = "t_bucket_players_" . $enable_bucket_suffix[1];
                if ((Schema::hasTable($enable_bucket_table[0]) || Schema::hasTable($enable_bucket_table[1]))) {
                    array_push($upcoming_inplay_data, $data);
                }
            }
            $matches_array = array();
            array_push($matches_array, $upcoming_inplay_data);
            return $this->getMatchesResponse($matches_array, $players);
        }
    }

    /**
     * Get opponent BRW, BRL, GAH
     */
    public function getOpponentsDetail($player1_id, $player1_detail, $player2_id, $player2_detail, $surface="ALL") {
        $opponent_ids_1 = array();
        $opponent_ids_2 = array();
        foreach ($player1_detail as $data) {
            $opponent_id = $data["player1_id"] == $player1_id ? $data["player2_id"] : $data["player1_id"];
            if (!in_array($opponent_id, $opponent_ids_1)) {
                array_push($opponent_ids_1, $opponent_id);
            }
        }

        foreach ($player2_detail as $data) {
            $opponent_id = $data["player1_id"] == $player2_id ? $data["player2_id"] : $data["player1_id"];
            if (!in_array($opponent_id, $opponent_ids_2)) {
                array_push($opponent_ids_2, $opponent_id);
            }
        }

        $tables = DB::table("pg_catalog.pg_tables")
                    ->where("schemaname", "public")
                    ->where("tablename", "like", "t_matches_%")
                    ->get();

        $condition = array();
        switch($surface) {
            case "CLY":
                $condition["surface"] = ["Clay"];
                break;
            case "HRD":
                $condition["surface"] = ["Hardcourt outdoor"];
                break;
            case "IND":
                $condition["surface"] = ["Hardcourt indoor", "Carpet indoor"];
                break;
            case "GRS":
                $condition["surface"] = ["Grass"];
                break;
            default:
                $condition["surface"] = $surface;
                break;
        }

        $match_table_union_1 = array();
        $match_table_union_2 = array();

        foreach ($tables as $table) {
            // filtering by opponent ids
            $match_table_subquery_1 = DB::table($table->tablename)->where('time_status', 3)
                                        ->where(function($query) use ($opponent_ids_1) {
                                            $query->whereIn('player1_id', $opponent_ids_1)
                                            ->orWhereIn('player2_id', $opponent_ids_1);
                                        });
            $match_table_subquery_2 = DB::table($table->tablename)->where('time_status', 3)
                                        ->where(function($query) use ($opponent_ids_2) {
                                            $query->whereIn('player1_id', $opponent_ids_2)
                                            ->orWhereIn('player2_id', $opponent_ids_2);
                                        });
            // filtering by surface
            if ($condition["surface"] != "ALL") {
                $match_table_subquery1->whereIn("surface", $condition["surface"]);
                $match_table_subquery2->whereIn("surface", $condition["surface"]);
            }

            array_push($match_table_union_1, $match_table_subquery_1);
            array_push($match_table_union_2, $match_table_subquery_2);
        }

        $opponents_1_set = array();
        $opponents_2_set = array();

        // add sets
        foreach ($opponent_ids_1 as $id) {
            if (count($match_table_union_1) > 0) {
                $matches_data_1_array = array();
                foreach ($match_table_union_1 as $data) {
                    $matches_data_1 = $data
                                        ->where(function($query) use ($id) {
                                            $query->where('player1_id', $id)
                                            ->orWhere('player2_id', $id);
                                        })->get();
                    
                    $match_array = json_decode(json_encode($matches_data_1), true);
                    $matches_data_1_array = array_merge($matches_data_1_array, $match_array);
                }
                $opponents_1_set[$id] = Helper::getSetsOpponents($matches_data_1_array, $id);
            }
        }

        foreach ($opponent_ids_2 as $id) {
            if (count($match_table_union_2) > 0) {
                $matches_data_2_array = array();
                foreach ($match_table_union_2 as $data) {
                    $matches_data_2 = $data
                                        ->where(function($query) use ($id) {
                                            $query->where('player1_id', $id)
                                            ->orWhere('player2_id', $id);
                                        })->get();
    
                    $match_array = json_decode(json_encode($matches_data_2), true);
                    $matches_data_2_array = array_merge($matches_data_2_array, $match_array);

                }
                $opponents_2_set[$id] = Helper::getSetsOpponents($matches_data_2_array, $id);
            }
        }

        return [
            $player1_id => $opponents_1_set,
            $player2_id => $opponents_2_set,
        ];
    }

    public function getRelationUnPreCalculation($player1_id, $player2_id) {
        $history_tables = DB::table("pg_catalog.pg_tables")
					->where("schemaname", "public")
					->where("tablename", "like", "t_matches_%")
					->get();
			
        $players = DB::table("t_players")->get();

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

        $player1_objects = array();
        $player2_objects = array();
        $event_ids = array();

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
                if (!in_array($data["event_id"], $event_ids)) {
                    array_push($player1_objects, $db_data);
                    array_push($event_ids, $data["event_id"]);
                }
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
				if (!in_array($data["event_id"], $event_ids)) {
                    array_push($player2_objects, $db_data);
                    array_push($event_ids, $data["event_id"]);
                }
			}
        }
        return [$player1_objects, $player2_objects];
    }

    /**
     * Get pre-calculated data for Upcoming & Inplay
     */
    public function getRelationPreCalculation($player1_id, $player2_id) {
        $bucket_players_name = "t_bucket_players_" . $player1_id . "_" . $player2_id;
        if (!Schema::hasTable($bucket_players_name)) {
            $matches = $this->getRelationUnPreCalculation($player1_id, $player2_id);
            $player1_object = $matches[0];
            $player2_object = $matches[1];
        } else {
            $player1_object = DB::table($bucket_players_name)
                            ->select(
                                "event_id",
                                "p_id",
                                "p_name",
                                "p_odd",
                                "p_ranking",
                                "p_brw",
                                "p_brl",
                                "p_gah",
                                "p_depths",
                                "p_ww",
                                "p_wl",
                                "p_lw",
                                "p_ll",
                                "o_id",
                                "o_name",
                                "o_odd",
                                "o_ranking",
                                "scores",
                                "surface",
                                "time"
                            )
                            ->where("p_id", $player1_id)
                            ->orderByDesc("time")
                            ->get();
            
            $player2_object = DB::table($bucket_players_name)
                            ->select(
                                "event_id",
                                "p_id",
                                "p_name",
                                "p_odd",
                                "p_ranking",
                                "p_brw",
                                "p_brl",
                                "p_gah",
                                "p_depths",
                                "p_ww",
                                "p_wl",
                                "p_lw",
                                "p_ll",
                                "o_id",
                                "o_name",
                                "o_odd",
                                "o_ranking",
                                "scores",
                                "surface",
                                "time"
                            )
                            ->where("p_id", $player2_id)
                            ->orderByDesc("time")
                            ->get();
        }
        
        return [
            $player1_id => $player1_object,
            $player2_id => $player2_object,
        ];
    }

    public function triggerFilter1($inplay_data) {
        $filteredData = array();
        foreach ($inplay_data as $data) {
            // rule (set1 ended)
            $match_scores = explode(",", $data["scores"]);
            if (count($match_scores) >= 2) {
                $player1_id = $data["player1_id"];
                $player2_id = $data["player2_id"];
                $player1_ranking = $data["player1_ranking"];
                $player2_ranking = $data["player2_ranking"];
                // rule 1 (one of players or both have rank)
                if ($player1_ranking != '-' || $player2_ranking != '-') {
                    // (both players have played at least 3 matches with same opponents)
                    $bucket_players_name = "t_bucket_players_" . $player1_id . "_" . $player2_id;
                    $player1_object = DB::table($bucket_players_name)
                                        ->where('p_id', $player1_id)
                                        ->whereNotNull('o_ranking')
                                        ->get();
                    
                    $player1_oids = array();
                    foreach ($player1_object as $player1) {
                        array_push($player1_oids, $player1->o_id);
                    }
                    $player2_object = DB::table($bucket_players_name)
                                        ->where('p_id', $player2_id)
                                        ->whereNotNull('o_ranking')
                                        ->get();
    
                    $same_oids = array();
                    foreach ($player2_object as $player2) {
                        if (in_array($player2->o_id, $player1_oids)) {
                            array_push($same_oids, $player2->o_id);
                        }
                    }
                    if (count($same_oids) >= 3) {
                        // rule 3 (player with worst rank lost set 1)
                        $worst_rank_player = 0;
                        if ($player1_ranking == "-") {
                            $worst_rank_player = 1;
                        } else if ($player2_ranking == "-") {
                            $worst_rank_player = 2;
                        }
                        if ($player1_ranking != "-" && $player2_ranking != "-") {
                            if ($player1_ranking < $player2_ranking) {
                                $worst_rank_player = 2;
                            } else {
                                $worst_rank_player = 1;
                            }
                        }
                        $scores = explode(",", $data["scores"]);
                        if (count($scores) > 0) {
                            $set1 = explode("-", $scores[0]);
                            if (($set1[0] > $set1[1] && $worst_rank_player == 2) || ($set1[0] < $set1[1] && $worst_rank_player == 1)) {
                                // rule 4 (player with worst rank has more sets won with SO)
                                // player 1 sets won count with SO
                                $player1_won_count = 0;
                                foreach ($player1_object as $player1) {
                                    if (in_array($player1->o_id, $same_oids)) {
                                        $performance = [
                                            $player1->p_ww,
                                            $player1->p_lw,
                                        ];
                                        $won_count = $this->getPlayerWonCount($performance);
                                        $player1_won_count += $won_count;
                                    }
                                }
    
                                $player2_won_count = 0;
                                foreach ($player2_object as $player2) {
                                    if (in_array($player2->o_id, $same_oids)) {
                                        $performance = [
                                            $player2->p_ww,
                                            $player2->p_lw,
                                        ];
                                        $won_count = $this->getPlayerWonCount($performance);
                                        $player2_won_count += $won_count;
                                    }
                                }
    
                                if (($worst_rank_player == 1 && $player1_won_count > $player2_won_count) 
                                    || ($worst_rank_player == 2 && $player2_won_count > $player1_won_count)) {
                                    array_push($filteredData, $data);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $filteredData;
    }

    public function getPlayerWonCount($performance) {
        $ww = json_decode($performance[0]);
        $lw = json_decode($performance[1]);
        $sum_ww = array_sum($ww);
        $sum_1w = array_sum($lw);
        return $sum_ww + $sum_1w;
    }

    public function history(Request $request) {
        try {
            $date = $request->input('date', date('Y-m-d', time()));
            $history_data = $this->getMatches($date, 3); // time_status (3: ended)
            return response()->json($history_data, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }

    public function upComing(Request $request) {
        try {
            $upcoming_data = $this->getMatches(null, 0); // time_status (0: upcoming)
            return response()->json($upcoming_data, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }

    public function inplay(Request $request) {
        try {
            $inplay_data = $this->getMatches(null, 1); // time_status (1: inplay)
            return response()->json($inplay_data, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }

    public function trigger1(Request $request) {
        try {
            $trigger1_data = $this->getMatches(null, 1); // trigger
            $filteredData = $this->triggerFilter1($trigger1_data);
            return response()->json($filteredData, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }

    public function relation(Request $request) {
        try {
            $player1_id = $request->input('player1_id', null);
            $player2_id = $request->input('player2_id', null);
            if (!$player1_id || !$player2_id) {
                return response()->json([], 200);
            }
            $relation_data = $this->getRelationPreCalculation($player1_id, $player2_id);
            return response()->json($relation_data, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }
}
