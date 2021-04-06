<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Helpers\Helper;

class TennisController extends Controller
{
    public function index() {
        echo "index";
    }

    /**
     * Get matches
     * @param   string  $date
     * @param   int     $type (0: upcoming, 1: inplay, 3: history)
     * @return  array   $matches
     */
    public function getMatches($date, $type) {
        $current_date = date('Y-m-d', time());
        if (!$date) {
            $date = $current_date;
        }
        $players = DB::table("t_players")->get();

        $match_table_name = "t_matches_" . substr($date, 0, 4) . "_" . substr($date, 5, 2);
        if (!Schema::hasTable($match_table_name)) {
            return array();
        }
        $times = Helper::getTimePeriod($date);
        if ($type == 0) { // upcoming
            $matches_data = DB::table($match_table_name)
                            ->where('time_status', 0)
                            ->whereBetween('time', [$times[0], $times[1]])
                            ->orderBy('time')
                            ->get();  
        } elseif ($type == 1) { // in play
            $matches_data = DB::table($match_table_name)
                            ->where('time_status', 1)
                            ->whereBetween('time', [$times[0], $times[1]])
                            ->orderBy('time')
                            ->get();
        } elseif ($type == 3) { // ended
            if ($date == $current_date) {
                $matches_data = DB::table($match_table_name)
                            ->where('time_status', 3)
                            ->whereBetween('time', [$times[0], $times[1]])
                            ->orderBy('time')
                            ->get();
            } else {
                $matches_data = DB::table($match_table_name)
                    ->whereBetween('time', [$times[0], $times[1]])
                    ->orderBy('time')
                    ->get();
            }
            
            $matches_array = array();
            array_push($matches_array, $matches_data);
            return Helper::getMatchesResponse($matches_array, $players);
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
            return Helper::getMatchesResponse($matches_array, $players);
        }
    }

    /**
     * Get relation data (un pre-calcuated)
     * @param   int     $player1_id
     * @param   int     $player2_id
     * @return  array   $matches
     */
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
        $matches1_set = Helper::getPlayersSubDetail($matches1, $player1_id);
        $matches2_set = Helper::getPlayersSubDetail($matches2, $player2_id);

		// add breaks to the players array
        $matches_1 = array();
        $matches_2 = array();

        $player1_objects = array();
        $player2_objects = array();
        $event_ids = array();

        foreach ($matches1_set as $data) {
			if ($data["scores"] != "") {
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
					"o_id"		=> $opponent_info["o_id"],
					"o_name"	=> $opponent_info["o_name"],
					"o_odd"		=> $opponent_info["o_odd"],
					"o_ranking"	=> $opponent_info["o_ranking"] == "-" ? NULL : $opponent_info["o_ranking"],
					"home"	    => $data["home"],
				];
                if (!in_array($data["event_id"], $event_ids)) {
                    array_push($player1_objects, $db_data);
                    array_push($event_ids, $data["event_id"]);
                }
			}
		}

        $event_ids = array();
        foreach ($matches2_set as $data) {
			if ($data["scores"] != "") {
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
					"o_id"		=> $opponent_info["o_id"],
					"o_name"	=> $opponent_info["o_name"],
					"o_odd"		=> $opponent_info["o_odd"],
					"o_ranking"	=> $opponent_info["o_ranking"] == "-" ? NULL : $opponent_info["o_ranking"],
					"home"	    => $data["home"],
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
     * @param  int   $player1_id
     * @param  int   $player2_id
     * @return array $matches
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
                                "time",
                                "home"
                            )
                            ->where("p_id", $player1_id)
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
                                "time",
                                "home"
                            )
                            ->where("p_id", $player2_id)
                            ->get();
        }
        
        return [
            $player1_id => $player1_object,
            $player2_id => $player2_object,
        ];
    }

    /**
     * Get trigger1 data (rule)
     * @param   array $inplay_data
     * @return  array $matches
     */
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

    /**
     * Get player won count
     * @param  array $performance
     * @return int   $sum
     */
    public function getPlayerWonCount($performance) {
        $ww = json_decode($performance[0]);
        $lw = json_decode($performance[1]);
        $sum_ww = array_sum($ww);
        $sum_1w = array_sum($lw);
        return $sum_ww + $sum_1w;
    }

    /**
     * History Request
     */
    public function history(Request $request) {
        try {
            $date = $request->input('date', date('Y-m-d', time()));
            $history_data = $this->getMatches($date, 3); // time_status (3: ended)
            return response()->json($history_data, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }

    /**
     * Upcoming Request
     */
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

    /**
     * Trigger1 Request
     */
    public function trigger1(Request $request) {
        try {
            $trigger1_data = $this->getMatches(null, 1);
            $filteredData = $this->triggerFilter1($trigger1_data);
            return response()->json($filteredData, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }

    /**
     * Relation Request
     */
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
