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
                            ->where('time', ">", $times[0])
                            ->orderBy('time')
                            ->get();  
        } elseif ($type == 1) { // in play
            $matches_data = DB::table($match_table_name)
                            ->where('time_status', 1)
                            ->where('time', ">", $times[0])
                            ->orderBy('time')
                            ->get();
        } elseif ($type == 3) { // ended
            if ($date == $current_date) {
                $matches_data = DB::table($match_table_name)
                                ->where('time_status', 3)
                                ->where('scores', '<>', NULL)
                                ->where('scores', '<>', '')
                                ->whereBetween('time', [$times[0], $times[1]])
                                ->orderBy('time')
                                ->get();
            } else {
                $matches_data = DB::table($match_table_name)
                                ->where('time_status', 3)
                                ->where('scores', '<>', NULL)
                                ->where('scores', '<>', '')
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
            $t_inplay = DB::table("t_inplay")->get();
            $t_inplay_event_ids = array();
            foreach ($t_inplay as $data) {
                array_push($t_inplay_event_ids, $data->event_id);
            }
            foreach ($matches_data as $data) {
                // check pre-calculation table is exist or not
                $enable_players_bucket_table = "t_bucket_players_" . $data->player1_id . '_' . $data->player2_id;
                $enable_opponents_bucket_table = "t_bucket_opponents_" . $data->player1_id . '_' . $data->player2_id;
                if (Schema::hasTable($enable_players_bucket_table) && Schema::hasTable($enable_opponents_bucket_table)) {
                    $players_count = DB::table($enable_players_bucket_table)
                                        ->count();
                    $opponents_count = DB::table($enable_opponents_bucket_table)
                                        ->count();
                    if ($players_count > 0 && $opponents_count > 0) {
                        if ($data->time_status == 1) { // for inplay matches
                            // check this event id is in t_inplay table
                            if (in_array($data->event_id, $t_inplay_event_ids)) {
                                array_push($upcoming_inplay_data, $data);
                            }
                        } else {
                            array_push($upcoming_inplay_data, $data);
                        }
                    }
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
            $match_table_subquery_1 = DB::table($table->tablename)
                                        ->where('time_status', 3)
                                        ->where('scores', '<>', NULL)
                                        ->where('scores', '<>', '')
                                        ->where(function($query) use ($player1_id) {
                                            $query->where('player1_id', $player1_id)
                                            ->orWhere('player2_id', $player1_id);
                                        });
            // filtering by player ids (player2)
            $match_table_subquery_2 = DB::table($table->tablename)
                                        ->where('time_status', 3)
                                        ->where('scores', '<>', NULL)
                                        ->where('scores', '<>', '')
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

        $event_ids = array();
        foreach ($matches2_set as $data) {
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
        $bucket_opponents_name = "t_bucket_opponents_" . $player1_id . "_" . $player2_id;
        if (!Schema::hasTable($bucket_players_name)) {
            $history_tables = DB::table("pg_catalog.pg_tables")
					->where("schemaname", "public")
					->where("tablename", "like", "t_matches_%")
					->get();
			
            $players = DB::table("t_players")->get();
            $player_ids = [$player1_id, $player2_id];
            $relation_data = Helper::getRelationMatches($player_ids, $history_tables, $players);
            $player1_object = $relation_data[0][0];
            $player2_object = $relation_data[0][1];
            $opponent_object = $relation_data[1];
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
            
            $opponent_object = DB::table($bucket_opponents_name)
                                ->get();
        }
        
        return [
            $player1_id => $player1_object,
            $player2_id => $player2_object,
            "opponents" => $opponent_object,
        ];
    }

    /**
     * Get trigger1 data (rule)
     * @param   array $inplay_data
     * @return  array $matches
     */
    public function triggerFilter1($inplay_matches) {
        $players_detail = array();
        // get player details
        foreach ($inplay_matches as $match) {
            $surface = $match["surface"];
            $player1_id = $match["player1_id"];
            $player1_ranking = $match["player1_ranking"] === "-" ? 9999 : (int)$match["player1_ranking"];
            $player2_id = $match["player2_id"];
            $player2_ranking = $match["player2_ranking"] === "-" ? 9999 : (int)$match["player2_ranking"];
            $bucket_table = "t_bucket_players_" . $player1_id . "_" . $player2_id;
            $player1_detail = DB::table($bucket_table)
                                ->select(
                                    "p_depths",
                                    "time",
                                )
                                ->where("surface", $surface) // surface filter
                                ->where("p_id", $player1_id)
                                ->where("o_ranking", "<", $player1_ranking) // HIR filter
                                ->orderByDesc("time")
                                ->limit(10)
                                ->get();
            $player2_detail = DB::table($bucket_table)
                                ->select(
                                    "p_depths",
                                    "time",
                                )
                                ->where("surface", $surface) // surface filter
                                ->where("p_id", $player2_id)
                                ->where("o_ranking", "<", $player2_ranking) // HIR filter
                                ->orderByDesc("time")
                                ->limit(10)
                                ->get();
            $players_detail[$player1_id] = $player1_detail;
            $players_detail[$player2_id] = $player2_detail;
        }

        return $players_detail;
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

    public function inplayScoreUpdate(Request $request) {
        try {
            $current_scores = DB::table("t_inplay")
                                ->get();
            return response()->json($current_scores, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }

    public function inplay(Request $request) {
        try {
            $inplay_data = $this->getMatches(null, 1); // time_status (1: inplay)
            $players_detail = $this->triggerFilter1($inplay_data);
            $response = [
                "inplay_detail" => $inplay_data,
                "players_detail" => $players_detail,
            ];
            return response()->json($response, 200);
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

    public function robots(Request $request) {
        try {
            $robot_tables = [
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
            ];

            $robots = array();
            foreach ($robot_tables as $robot_table) {
                $robots[] = DB::table($robot_table)
                            ->select("event_id", "expected_winner", "real_winner")
                            ->get();    
            }
            return response()->json($robots, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }
}
