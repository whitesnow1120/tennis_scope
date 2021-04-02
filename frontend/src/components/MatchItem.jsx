import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import PropTypes from 'prop-types';

import { getRelationData } from '../apis';
import {
  GET_RELATION_DATA,
  GET_RELATION_FILTERED_DATA,
  GET_OPENED_DETAIL,
} from '../store/actions/types';
import { getWinner, formatDateTime, filterData } from '../utils';
import Surface from './InplayDetail/Surface';
import Set from './InplayDetail/Set';
import FilterRank from './InplayDetail/FilterRank';
import FilterOpponent from './InplayDetail/FilterOpponent';
import FilterLimit from './InplayDetail/FilterLimit';
import PlayerDetail from './InplayDetail/PlayerDetail';

const MatchItem = (props) => {
  const { item, type, loading, setLoading, newIds } = props;
  const dispatch = useDispatch();
  const { relationData, openedDetail } = useSelector((state) => state.tennis);

  const [detailOpened, setDetailOpened] = useState(false);
  const [selectedSurface, setSelectedSurface] = useState('ALL');
  const [selectedRankDiff1, setSelectedRankDiff1] = useState('ALL');
  const [selectedRankDiff2, setSelectedRankDiff2] = useState('ALL');
  const [selectedOpponent, setSelectedOpponent] = useState('ALL');
  const [selectedLimit, setSelectedLimit] = useState(10);
  const [selectedSet1, setSelectedSet1] = useState('ALL');
  const [selectedSet2, setSelectedSet2] = useState('ALL');
  const newBox =
    type === 'trigger1' && newIds.includes(item['event_id'])
      ? 'match-box new-trigger-box'
      : 'match-box';

  const player = getWinner(item.scores);
  const datetime = formatDateTime(item.time);
  const scores = item.scores.split(',');

  useEffect(() => {
    const loadRelationData = async () => {
      setLoading(true);
      let filteredData = {};
      if (
        !(
          relationData != undefined &&
          item.player1_id in relationData &&
          item.player1_id in relationData
        )
      ) {
        const params = {
          player1_id: item.player1_id,
          player2_id: item.player2_id,
        };

        const response = await getRelationData(params);

        if (response.status === 200) {
          filteredData = response.data;
          dispatch({
            type: GET_RELATION_DATA,
            payload: filteredData,
          });
        } else {
          dispatch({ type: GET_RELATION_DATA, payload: {} });
        }
      } else {
        filteredData = relationData;
      }
      // filtering
      const filters = {
        surface: selectedSurface,
        opponent: selectedOpponent,
        rankDiff1: selectedRankDiff1,
        rankDiff2: selectedRankDiff2,
        set1: selectedSet1,
        set2: selectedSet2,
        limit: selectedLimit,
      };
      const data = filterData(
        item.player1_id,
        item.player2_id,
        filteredData,
        filters
      );
      dispatch({
        type: GET_RELATION_FILTERED_DATA,
        payload: data,
      });
      setLoading(false);
    };
    if (
      (openedDetail != undefined &&
        openedDetail['p1_id'] === item.player1_id &&
        openedDetail['p2_id'] === item.player2_id) ||
      (openedDetail['p1_id'] === item.player2_id &&
        openedDetail['p2_id'] === item.player1_id)
    ) {
      loadRelationData();
      setDetailOpened(true);
    } else {
      setDetailOpened(false);
    }
  }, [
    openedDetail,
    selectedSurface,
    selectedOpponent,
    selectedRankDiff1,
    selectedRankDiff2,
    selectedSet1,
    selectedSet2,
    selectedLimit,
  ]);

  const handleMatchClicked = () => {
    let data = {};
    if (
      (openedDetail != undefined &&
        openedDetail['p1_id'] === item.player1_id &&
        openedDetail['p2_id'] === item.player2_id) ||
      (openedDetail['p1_id'] === item.player2_id &&
        openedDetail['p2_id'] === item.player1_id)
    ) {
      data = {
        p1_id: '',
        p2_id: '',
      };
    } else {
      data = {
        p1_id: item.player1_id,
        p2_id: item.player2_id,
      };
    }
    dispatch({
      type: GET_OPENED_DETAIL,
      payload: data,
    });
  };

  return (
    <div className="col-lg-4 col-md-6 col-sm-6 col-xs-12 mb-2 pb-2 pt-2 match-item">
      <div className={newBox}>
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
              {(type === 'inplay' || type === 'trigger1') &&
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
              {(type === 'inplay' || type === 'trigger1') && <span>-</span>}
            </div>
          </div>
        </div>
        {!loading && detailOpened && (
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
  loading: PropTypes.bool,
  setLoading: PropTypes.func,
  newIds: PropTypes.array,
};

export default MatchItem;
