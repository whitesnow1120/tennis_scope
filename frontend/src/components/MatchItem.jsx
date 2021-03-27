import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import PropTypes from 'prop-types';

import { getRelationData } from '../apis';
import {
  GET_RELATION_DATA,
  GET_RELATION_SET_NUMBER,
  GET_RELATION_BREAKS,
} from '../store/actions/types';
import { getWinner, formateDateTime } from '../utils';
import Surface from './InplayDetail/Surface';
import Set from './InplayDetail/Set';
import FilterRank from './InplayDetail/FilterRank';
import FilterBreak from './InplayDetail/FilterBreak';
import FilterOpponent from './InplayDetail/FilterOpponent';
import FilterLimit from './InplayDetail/FilterLimit';
import PlayerDetail from './InplayDetail/PlayerDetail';

const MatchItem = (props) => {
  const { item, type } = props;
  const dispatch = useDispatch();
  const { relationData } = useSelector((state) => state.tennis);

  const [detailOpened, setDetailOpened] = useState(false);
  const [selectedSurface, setSelectedSurface] = useState('ALL');
  const [selectedRankDiff1, setSelectedRankDiff1] = useState('ALL');
  const [selectedRankDiff2, setSelectedRankDiff2] = useState('ALL');
  const [selectedBreakDiff1, setSelectedBreakDiff1] = useState('ALL');
  const [selectedBreakDiff2, setSelectedBreakDiff2] = useState('ALL');
  const [selectedOpponent, setSelectedOpponent] = useState('ALL');
  const [selectedLimit, setSelectedLimit] = useState(10);
  const [selectedSet1, setSelectedSet1] = useState('ALL');
  const [selectedSet2, setSelectedSet2] = useState('ALL');

  const player = getWinner(item.scores);
  const datetime = formateDateTime(item.time);
  const scores = item.scores.split(',');

  useEffect(() => {
    const loadRelationData = async () => {
      const params = {
        player1_id: item.player1_id,
        player2_id: item.player2_id,
        surface: selectedSurface,
        rank_diff_1: selectedRankDiff1,
        rank_diff_2: selectedRankDiff2,
        opponent: selectedOpponent,
        limit: selectedLimit,
      };

      const response = await getRelationData(params);

      if (response.status === 200) {
        dispatch({
          type: GET_RELATION_DATA,
          payload: response.data,
        });
      } else {
        dispatch({ type: GET_RELATION_DATA, payload: [] });
      }
    };

    if (detailOpened) {
      loadRelationData();
    }
  }, [
    detailOpened,
    selectedSurface,
    selectedRankDiff1,
    selectedRankDiff2,
    selectedOpponent,
    selectedLimit,
  ]);

  useEffect(() => {
    if (detailOpened) {
      let setPayload = {};
      setPayload[item.player1_id] = selectedSet1;
      setPayload[item.player2_id] = selectedSet2;
      dispatch({
        type: GET_RELATION_SET_NUMBER,
        payload: setPayload,
      });

      let breakPayload = {};
      breakPayload[item.player1_id] = selectedBreakDiff1;
      breakPayload[item.player2_id] = selectedBreakDiff2;
      dispatch({
        type: GET_RELATION_BREAKS,
        payload: breakPayload,
      });
    }
  }, [
    selectedSet1,
    selectedSet2,
    selectedBreakDiff1,
    selectedBreakDiff2,
    relationData,
  ]);

  const handleMatchClicked = () => {
    const pathName = window.location.pathname;
    if (!(pathName.includes('/upcoming') || pathName.includes('/history') )) {
      setDetailOpened(!detailOpened);
    }
  };

  return (
    <div className="col-lg-4 col-md-6 col-sm-6 col-xs-12 mb-2 pb-2 pt-2 match-item">
      <div className="match-box">
        <div onClick={handleMatchClicked}>
          <div className="left">
            <div className="name">
              <span>{item.player1_name}</span>
            </div>
            <div className="pt-2">
              <div
                className={
                  player === 1
                    ? 'sub-left winner ranking'
                    : 'sub-left loser ranking'
                }
              >
                <span>{item.player1_ranking}</span>
              </div>
              <div className="sub-right">
                <span>
                  {item.player1_odd
                    ? parseFloat(item.player1_odd).toFixed(2)
                    : '-'}
                </span>
              </div>
              <div className="sub-center">
                <span>{item.surface ? item.surface : '-'}</span>
              </div>
            </div>
          </div>
          <div className="right">
            <div className="name">
              <span>{item.player2_name}</span>
            </div>
            <div className="pt-2">
              <div
                className={
                  player === 2
                    ? 'sub-left winner ranking'
                    : 'sub-left loser ranking'
                }
              >
                <span>{item.player2_ranking}</span>
              </div>
              <div className="sub-right">
                <span>
                  {item.player2_odd
                    ? parseFloat(item.player2_odd).toFixed(2)
                    : '-'}
                </span>
              </div>
              <div className="sub-center">
                <span>{item.surface ? item.surface : '-'}</span>
              </div>
            </div>
          </div>
          <div className="center">
            <div className="scores">
              {type === 'inplay' &&
                scores.map((score, index) => (
                  <span
                    key={index}
                    className={
                      index === scores.length - 1 ? 'playing' : 'played'
                    }
                  >
                    {score}
                  </span>
                ))}
              {type === 'upcoming' && <span>{datetime[0]}</span>}
              {type === 'history' && (
                <span>{item.scores.replaceAll(',', ' ')}</span>
              )}
            </div>
            <div className="match-time">
              {type === 'history' && <span>-</span>}
              {type === 'upcoming' && <span>{datetime[1]}</span>}
              {type === 'inplay' && <span>-</span>}
            </div>
          </div>
        </div>
        {detailOpened && (
          <div className="players-detail">
            <Surface
              setSelectedSurface={setSelectedSurface}
              selectedSurface={selectedSurface}
            />
            <div className="compare-filters">
              <div className="left-box">
                <div className="vs">
                  <span>vs</span>
                </div>
                <div>
                  <FilterRank
                    selectedRankDiff={selectedRankDiff1}
                    setSelectedRankDiff={setSelectedRankDiff1}
                  />
                  <FilterBreak
                    selectedBreakDiff={selectedBreakDiff1}
                    setSelectedBreakDiff={setSelectedBreakDiff1}
                  />
                </div>
              </div>
              <div className="right-box">
                <div className="vs">
                  <span>vs</span>
                </div>
                <div>
                  <FilterRank
                    selectedRankDiff={selectedRankDiff2}
                    setSelectedRankDiff={setSelectedRankDiff2}
                  />
                  <FilterBreak
                    selectedBreakDiff={selectedBreakDiff2}
                    setSelectedBreakDiff={setSelectedBreakDiff2}
                  />
                </div>
              </div>
              <div className="center-box">
                <div className="vs">
                  <span>vs</span>
                </div>
                <div>
                  <FilterOpponent
                    selectedOpponent={selectedOpponent}
                    setSelectedOpponent={setSelectedOpponent}
                  />
                  <FilterLimit
                    selectedLimit={selectedLimit}
                    setSelectedLimit={setSelectedLimit}
                  />
                </div>
              </div>
            </div>
            <div className="set">
              <div className="set-left-box">
                <Set
                  selectedSet={selectedSet1}
                  setSelectedSet={setSelectedSet1}
                />
              </div>
              <div className="set-right-box">
                <Set
                  selectedSet={selectedSet2}
                  setSelectedSet={setSelectedSet2}
                />
              </div>
            </div>
            <PlayerDetail
              player1_id={item.player1_id}
              player2_id={item.player2_id}
            />
          </div>
        )}
      </div>
    </div>
  );
};

MatchItem.propTypes = {
  item: PropTypes.object,
  type: PropTypes.string,
};

export default MatchItem;
