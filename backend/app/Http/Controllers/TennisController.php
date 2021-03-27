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

    public function getMatchesResponse($matches_data, $players) {
        $matches = array();
        $i = 0;
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
                    $matches[$i]["player1_ranking"] = $player->ranking;
                }

                if ($data->player2_id == $player->api_id) {
                    $matches[$i]["player2_name"] = $player->name;
                    $matches[$i]["player2_ranking"] = $player->ranking;
                }
            }
            $i ++;
        }
        return $matches;
    }

    public function getMatches($date, $type) {
        if (!$date) {
            $date = date('Y-m-d', time());
        }
        $match_table_name = "t_matches_" . substr($date, 0, 4) . "_" . substr($date, 5, 2);
        if (!Schema::hasTable($match_table_name)) {
            return array();
        }
        if ($type === 0) { // upcoming
            $matches_data = DB::table($match_table_name)
                            ->where('time_status', 0)
                            ->orderBy('time')
                            ->get();
        } elseif ($type == 3) { // ended
            $times = Helper::getTimePeriod($date);
            $matches_data = DB::table($match_table_name)
                            ->where('time_status', 3)
                            ->whereBetween('time', [$times[0], $times[1]])
                            ->orderBy('time')
                            ->get();
        } elseif ($type == 1) { // in play
            $matches_data = DB::table($match_table_name)
                            ->where('time_status', 1)
                            ->orderBy('time')
                            ->get();
        }
        $players = DB::table("t_players")->get();

        return $this->getMatchesResponse($matches_data, $players);
    }

    public function getRelation(
        $player1_id=NULL,
        $player2_id=NULL,
        $surface="ALL", // ALL, CLY, HRD, IND, GRS
        $rank_diff_1="ALL", // ALL, HIR, LOR
        $rank_diff_2="ALL", // ALL, HIR, LOR
        $opponent="ALL", // ALL, SRO, SO
        $limit=10 // 10, 15, 20
    ) {
        if ($player1_id == NULL || $player2_id == NULL) {
            return [];
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

        $i = 0;
        $match_table_union_1 = NULL;
        $match_table_union_2 = NULL;

        foreach ($tables as $table) {
            // filtering by player ids (player1)
            $match_table_subquery_1 = DB::table($table->tablename)->where('time_status', 3)
                                        ->where(function($query) use ($player1_id) {
                                            $query->where('player1_id', (int)$player1_id)
                                            ->orWhere('player2_id', (int)$player1_id);
                                        });
            // filtering by player ids (player2)
            $match_table_subquery_2 = DB::table($table->tablename)->where('time_status', 3)
                                        ->where(function($query) use ($player2_id) {
                                            $query->where('player1_id', (int)$player2_id)
                                            ->orWhere('player2_id', (int)$player2_id);
                                        });
            // filtering by surface
            if ($condition["surface"] != "ALL") {
                $match_table_subquery_1->whereIn("surface", $condition["surface"]);
                $match_table_subquery_2->whereIn("surface", $condition["surface"]);
            }
            if ($i === 0) {
                $match_table_union_1 = $match_table_subquery_1;
                $match_table_union_2 = $match_table_subquery_2;
            } else {
                $match_table_union_2 = $match_table_union_2->unionAll($match_table_subquery_2);
                $match_table_union_1 = $match_table_union_1->unionAll($match_table_subquery_1);
            }

            $i ++;
        }

        $players = DB::table("t_players")->get();
        $player1_info = DB::table("t_players")
                            ->select("ranking")
                            ->where("id", (int)$player1_id)
                            ->first();
        $player2_info = DB::table("t_players")
                            ->select("ranking")
                            ->where("id", (int)$player2_id)
                            ->first();

        // filtering by HIR, LOR
        $player1_matches_rank = array();
        if ($match_table_union_1) {
            $matches_data_1 = $match_table_union_1->get();
            $player1_total_matches = $this->getMatchesResponse($matches_data_1, $players);
            if ($rank_diff_1 == "HIR") {
                foreach ($player1_total_matches as $data) {
                    if ($data["player1_id"] == $player1_id) { // player : opponent
                        if ($data["player2_ranking"] != "-" && $data["player2_ranking"] < $data["player1_ranking"]) {
                            array_push($player1_matches_rank, $data);
                        }
                    } else if ($data["player2_id"] == $player1_id) { // opponent : player
                        if ($data["player1_ranking"] != "-" && $data["player1_ranking"] < $data["player2_ranking"]) {
                            array_push($player1_matches_rank, $data);
                        }
                    }
                }
            } else if ($rank_diff_1 == "LOR") {
                foreach ($player1_total_matches as $data) {
                    if ($data["player1_id"] == $player1_id) {
                        if ($data["player2_ranking"] == "-" || $data["player1_ranking"] < $data["player2_ranking"]) {
                            array_push($player1_matches_rank, $data);
                        }
                    } else if ($data["player2_id"] == $player1_id) {
                        if ($data["player1_ranking"] == "-" || $data["player2_ranking"] < $data["player1_ranking"]) {
                            array_push($player1_matches_rank, $data);
                        }
                    }
                }
            } else {
                $player1_matches_rank = $player1_total_matches; 
            }
        }

        $player2_matches_rank = array();
        if ($match_table_union_2) {
            $matches_data_2 = $match_table_union_2->get();
            $player2_total_matches = $this->getMatchesResponse($matches_data_2, $players);
            if ($rank_diff_2 == "HIR") {
                foreach ($player2_total_matches as $data) {
                    if ($data["player1_id"] == $player2_id) {
                        if ($data["player2_ranking"] != "-" && $data["player2_ranking"] < $data["player1_ranking"]) {
                            array_push($player2_matches_rank, $data);
                        }
                    } else if ($data["player2_id"] == $player2_id) {
                        if ($data["player1_ranking"] != "-" && $data["player1_ranking"] < $data["player2_ranking"]) {
                            array_push($player2_matches_rank, $data);
                        }
                    }
                }
            } else if ($rank_diff_2 == "LOR") {
                foreach ($player2_total_matches as $data) {
                    if ($data["player1_id"] == $player2_id) {
                        if ($data["player2_ranking"] == "-" || $data["player1_ranking"] < $data["player2_ranking"]) {
                            array_push($player2_matches_rank, $data);
                        }
                    } else if ($data["player2_id"] == $player2_id) {
                        if ($data["player1_ranking"] == "-" || $data["player2_ranking"] < $data["player1_ranking"]) {
                            array_push($player2_matches_rank, $data);
                        }
                    }
                }
            } else {
                $player2_matches_rank = $player2_total_matches; 
            }
        }

        // get rankings of player1 and player2
        $player1_info = DB::table("t_players")
                            ->select("ranking")
                            ->where("id", (int)$player1_id)
                            ->first();
        if ($player1_info) {
            $player1_ranking = $player1_info->ranking;
        } else {
            $player1_ranking = "-";
        }

        $player2_info = DB::table("t_players")
                            ->select("ranking")
                            ->where("id", (int)$player2_id)
                            ->first();
        if ($player2_info) {
            $player2_ranking = $player2_info->ranking;
        } else {
            $player2_ranking = "-";
        }
        // filtering by opponent (SRO, SO)
        $player1_matches_opponent = array();
        $player2_matches_opponent = array();
        if ($opponent == "SRO") {
            if ($player2_ranking != "-") {
                foreach ($player1_matches_rank as $data) {
                    if ($data["player1_id"] == $player1_id) { // player : opponent
                        if ($data["player2_ranking"] >= $player2_ranking - 30 && $data["player2_ranking"] <= $player2_ranking + 30 ) {
                            array_push($player1_matches_opponent, $data);
                        }
                    } else if ($data["player2_id"] == $player1_id) { // opponent : player
                        if ($data["player1_ranking"] >= $player2_ranking - 30 && $data["player1_ranking"] <= $player2_ranking + 30 ) {
                            array_push($player1_matches_opponent, $data);
                        }
                    }
                }
            }

            if ($player1_ranking != "-") {
                foreach ($player2_matches_rank as $data) {
                    if ($data["player1_id"] == $player1_id) { // player : opponent
                        if ($data["player2_ranking"] >= $player1_ranking - 30 && $data["player2_ranking"] <= $player1_ranking + 30 ) {
                            array_push($player2_matches_opponent, $data);
                        }
                    } else if ($data["player2_id"] == $player1_id) { // opponent : player
                        if ($data["player1_ranking"] >= $player1_ranking - 30 && $data["player1_ranking"] <= $player1_ranking + 30 ) {
                            array_push($player2_matches_opponent, $data);
                        }
                    }
                }
            }
        } else if ($opponent == "SO") {
            foreach ($player1_matches_rank as $player1_data) {
                foreach ($player2_matches_rank as $player2_data) {
                    if ($player1_data["player1_id"] == $player1_id && $player2_data["player1_id"] == $player2_id) { // player1 : opponent, player2 : opponent
                        if ($player1_data["player2_id"] == $player2_data["player2_id"]) {
                            array_push($player1_matches_opponent, $player1_data);
                            array_push($player2_matches_opponent, $player2_data);
                        }
                    } else if ($player1_data["player1_id"] == $player1_id && $player2_data["player2_id"] == $player2_id) { // player1 : opponent, opponent : player2
                        if ($player1_data["player2_id"] == $player2_data["player1_id"]) {
                            array_push($player1_matches_opponent, $player1_data);
                            array_push($player2_matches_opponent, $player2_data);
                        }
                    } else if ($player1_data["player2_id"] == $player1_id && $player2_data["player1_id"] == $player2_id) { // opponent : player1, player2 : opponent
                        if ($player1_data["player1_id"] == $player2_data["player2_id"]) {
                            array_push($player1_matches_opponent, $player1_data);
                            array_push($player2_matches_opponent, $player2_data);
                        }
                    } else if ($player1_data["player2_id"] == $player1_id && $player2_data["player2_id"] == $player2_id) { // opponent : player1, opponent : player2
                        if ($player1_data["player1_id"] == $player2_data["player1_id"]) {
                            array_push($player1_matches_opponent, $player1_data);
                            array_push($player2_matches_opponent, $player2_data);
                        }
                    }
                }
            }
        } else {
            $player1_matches_opponent = $player1_matches_rank;
            $player2_matches_opponent = $player2_matches_rank;
        }

        // add sets
        $matches1_set = Helper::getSetsPerformanceDetail($player1_matches_opponent, $player1_id);
        $matches2_set = Helper::getSetsPerformanceDetail($player2_matches_opponent, $player2_id);


        // order by time
        usort($matches1_set, function($a, $b) {
            return $a["time"] < $b["time"] ? 1 : -1;
        });
        usort($matches2_set, function($a, $b) {
            return $a["time"] < $b["time"] ? 1 : -1;
        });

        $player1_limited_array = array_slice($matches1_set, 0, (int)$limit, true);
        $player2_limited_array = array_slice($matches2_set, 0, (int)$limit, true);

        // get BRW, BRL, and GAH for the opponents
        $opponent_details = $this->getOpponentsDetail(
                                                    $player1_id,
                                                    $player1_limited_array,
                                                    $player2_id,
                                                    $player2_limited_array,
                                                    $surface);
        
        // add breaks to the players array
        $matches_1 = array();
        $matches_2 = array();
        $brw = [0,0,0,0,0];
        $brl = [0,0,0,0,0];
        $gah = [0,0,0,0,0];
        foreach ($player1_limited_array as $data) {
            // by sets
            for ($i = 0; $i < 5; $i ++) {
                if (count($data["sets"]) > $i) {
                    $brw[$i] += array_sum($data["sets"][$i][$player1_id]["brw"]);
                    $brl[$i] += array_sum($data["sets"][$i][$player1_id]["brl"]);
                    $gah[$i] += array_sum($data["sets"][$i][$player1_id]["gah"]);
                }
            }
            array_push($matches_1, $data);
        }

        $player1_breaks = [
            "brw" => $brw,
            "brl" => $brl,
            "gah" => $gah,
        ];

        $brw = [0,0,0,0,0];
        $brl = [0,0,0,0,0];
        $gah = [0,0,0,0,0];
        foreach ($player2_limited_array as $data) {
            // by sets
            for ($i = 0; $i < 5; $i ++) {
                if (count($data["sets"]) > $i) {
                    $brw[$i] += array_sum($data["sets"][$i][$player2_id]["brw"]);
                    $brl[$i] += array_sum($data["sets"][$i][$player2_id]["brl"]);
                    $gah[$i] += array_sum($data["sets"][$i][$player2_id]["gah"]);
                }
            }
            array_push($matches_2, $data);
        }
        $player2_breaks = [
            "brw" => $brw,
            "brl" => $brl,
            "gah" => $gah,
        ];

        return [
            $player1_id => $matches_1,
            $player2_id => $matches_2,
            "opponents_breaks" => [
                $player1_id => $opponent_details[$player1_id],
                $player2_id => $opponent_details[$player2_id],
            ],
        ];
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

        $i = 0;
        $match_table_union_1 = NULL;
        $match_table_union_2 = NULL;

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
            if ($i === 0) {
                $match_table_union_1 = $match_table_subquery_1;
                $match_table_union_2 = $match_table_subquery_2;
            } else {
                $match_table_union_1 = $match_table_union_1->unionAll($match_table_subquery_1);
                $match_table_union_2 = $match_table_union_2->unionAll($match_table_subquery_2);
            }

            $i ++;
        }

        $opponents_1_set = array();
        $opponents_2_set = array();

        // add sets
        foreach ($opponent_ids_1 as $id) {
            if ($match_table_union_1) {
                $matches_data_1 = $match_table_union_1
                                    ->where(function($query) use ($id) {
                                        $query->where('player1_id', $id)
                                        ->orWhere('player2_id', $id);
                                    })->orderByDesc("time")
                                    ->limit(count($player1_detail))
                                    ->get();

                $match_array = json_decode(json_encode($matches_data_1), true);
                $opponents_1_set[$id] = Helper::getSetsOpponents($match_array, $id);
            }
        }

        foreach ($opponent_ids_2 as $id) {
            if ($match_table_union_2) {
                $matches_data_2 = $match_table_union_2
                                    ->where(function($query) use ($id) {
                                        $query->where('player1_id', $id)
                                        ->orWhere('player2_id', $id);
                                    })->orderByDesc("time")
                                    ->limit(count($player2_detail))
                                    ->get();

                $match_array = json_decode(json_encode($matches_data_2), true);
                $opponents_2_set[$id] = Helper::getSetsOpponents($match_array, $id);
            }
        }

        return [
            $player1_id => $opponents_1_set,
            $player2_id => $opponents_2_set,
        ];
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

    public function relation(Request $request) {
        try {
            $player1_id = $request->input('player1_id', null);
            $player2_id = $request->input('player2_id', null);
            $surface = $request->input('surface', "ALL"); // ALL, CLY, HRD, IND, GRS
            $rank_diff_1 = $request->input('rank_diff_1', "ALL"); // ALL, HIR, LOR
            $rank_diff_2 = $request->input('rank_diff_2', "ALL"); // ALL, HIR, LOR
            $opponent = $request->input('opponent', "ALL"); // ALL, SRO, SO
            $limit = $request->input('limit', 10); // ALL, SRO, SO

            $relation_data = $this->getRelation($player1_id,
                                                $player2_id,
                                                $surface, // ALL, CLY, HRD, IND, GRS
                                                $rank_diff_1, // ALL, HIR, LOR
                                                $rank_diff_2, // ALL, HIR, LOR
                                                $opponent, // ALL, SRO, SO
                                                $limit, // 10, 15, 20
                                                );
            
            return response()->json($relation_data, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }
}
