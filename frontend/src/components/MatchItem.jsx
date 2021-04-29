import React, { useState, useEffect } from 'react';
import { TooltipComponent } from '@syncfusion/ej2-react-popups';
import PropTypes from 'prop-types';

import { getRelationData } from '../apis';
import {
  getWinner,
  formatDateTime,
  filterData,
  sortByTime,
  checkWinner,
} from '../utils';
import LoadingDetail from '../components/LoadingDetail';
import Surface from './InplayDetail/Surface';
import Set from './InplayDetail/Set';
import FilterRank from './InplayDetail/FilterRank';
import FilterOpponent from './InplayDetail/FilterOpponent';
import FilterLimit from './InplayDetail/FilterLimit';
import PlayerDetail from './InplayDetail/PlayerDetail';
import AverageRanks from './InplayDetail/AverageRanks';

const MatchItem = (props) => {
  const {
    item,
    type,
    loading,
    setLoading, // can't click the other matches
    triggerSet,
    openedDetail,
    setOpenedDetail,
    winners,
    roboPicks,
    mobileMatchClicked,
    setMobileMatchClicked,
    matchCnt,
  } = props;

  const [relationData, setRelationData] = useState({});
  const [filteredRelationData, setFilteredRelationData] = useState({});
  const [detailOpened, setDetailOpened] = useState(false);
  const [isClicked, setClicked] = useState();
  const [selectedSurface, setSelectedSurface] = useState('ALL');
  const [selectedRankDiff1, setSelectedRankDiff1] = useState('ALL');
  const [selectedRankDiff2, setSelectedRankDiff2] = useState('ALL');
  const [selectedOpponent, setSelectedOpponent] = useState('ALL');
  const [selectedLimit, setSelectedLimit] = useState(10);
  const [selectedSet1, setSelectedSet1] = useState('ALL');
  const [selectedSet2, setSelectedSet2] = useState('ALL');
  const [matchLoading, setMatchLoading] = useState(false);
  // set initial box style
  const [matchVisible, setMatchVisible] = useState(true);
  const [boxStyle, setBoxStyle] = useState('match-box green-border');
  const [nameColor1, setNameColor1] = useState('');
  const [nameColor2, setNameColor2] = useState('');
  const [botBalance1, setBotBalance1] = useState(false);
  const [botBalance2, setBotBalance2] = useState(false);
  const [matchHide, setMatchHide] = useState(false);
  const [showMoreFilters, setShowMoreFilters] = useState(
    window.innerWidth < 768 ? false : true
  );

  const player = getWinner(item.scores);
  const datetime = formatDateTime(item.time);
  let scores = item.scores.split(',');
  if (type === 'inplay' || type === 'trigger1') {
    scores = item['ss'].split(',');
  }
  let tooltipContent = item['league_name'] === null ? '-' : item['league_name'];
  tooltipContent = `<div class='league-name-tooltip-content'><span>${tooltipContent}</span></div>`;

  useEffect(() => {
    if (!type.includes('trigger')) {
      const winner = checkWinner(item, winners);
      if (winner['type'] == -1 && roboPicks) {
        setMatchVisible(false);
      } else {
        setMatchVisible(true);
      }
    }
  }, [roboPicks]);

  useEffect(() => {
    if (type === 'trigger1' || type === 'trigger2') {
      let clickedEvents = null;
      if (type === 'trigger1') {
        clickedEvents = JSON.parse(
          localStorage.getItem('clickedEventsTrigger1')
        );
      } else {
        clickedEvents = JSON.parse(
          localStorage.getItem('clickedEventsTrigger2')
        );
      }
      if (clickedEvents === null) {
        clickedEvents = {
          set1: [],
          set2: [],
          set3: [],
        };
      }
      if (
        (triggerSet === 1 &&
          !clickedEvents['set1'].includes(item['event_id'])) ||
        (triggerSet === 2 &&
          !clickedEvents['set2'].includes(item['event_id'])) ||
        (triggerSet === 3 && !clickedEvents['set3'].includes(item['event_id']))
      ) {
        setBoxStyle('match-box green-border');
      } else {
        setBoxStyle('match-box');
      }
    } else {
      const winner = checkWinner(item, winners);
      if (
        winner['type'] === 43 ||
        winner['type'] === 44 ||
        winner['type'] === 4
      ) {
        setBoxStyle('match-box orange-border');
        if (winner['winner'] === 1) {
          if (winner['type'] === 4) {
            setBotBalance1(true);
          }
          setNameColor1('orange-color');
        } else {
          if (winner['type'] === 4) {
            setBotBalance2(true);
          }
          setNameColor2('orange-color');
        }
      } else {
        setBoxStyle('match-box');
      }
    }
  }, []);

  useEffect(() => {
    if (
      (type === 'trigger1' && isClicked) ||
      (type === 'trigger2' && isClicked)
    ) {
      setBoxStyle('match-box');
    }
  }, [isClicked]);

  useEffect(() => {
    const loadRelationData = async () => {
      setLoading(true);
      setMatchLoading(true);
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
          sortByTime(filteredData, item.player1_id, item.player2_id);
          setRelationData(filteredData);
        } else {
          setRelationData({});
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

      setFilteredRelationData(data);
      setLoading(false);
      setMatchLoading(false);
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
      if (matchCnt === 1 && window.innerWidth < 768) {
        setMobileMatchClicked(true);
      }
    } else {
      if (window.innerWidth < 768) {
        if (openedDetail['p1_id'] !== '' && openedDetail['p2_id'] !== '') {
          setMobileMatchClicked(true);
          setMatchHide(true);
        } else {
          setMobileMatchClicked(false);
        }
      }
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

  useEffect(() => {
    if (!mobileMatchClicked && window.innerWidth < 768) {
      setMatchHide(false);
      const data = {
        p1_id: '',
        p2_id: '',
      };
      setOpenedDetail(data);
    }
  }, [mobileMatchClicked]);

  const handleMatchClicked = () => {
    setRelationData({});
    let data = {
      p1_id: '',
      p2_id: '',
    };
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
    setOpenedDetail(data);
    // add event_ids to localstorage for trigger1
    if (type === 'trigger1' || type === 'trigger2') {
      let clickedEvents = null;
      if (type === 'trigger1') {
        clickedEvents = JSON.parse(
          localStorage.getItem('clickedEventsTrigger1')
        );
      } else {
        clickedEvents = JSON.parse(
          localStorage.getItem('clickedEventsTrigger2')
        );
      }
      if (clickedEvents === null) {
        clickedEvents = {
          set1: [],
          set2: [],
          set3: [],
        };
      }
      if (
        triggerSet === 1 &&
        !clickedEvents['set1'].includes(item['event_id'])
      ) {
        clickedEvents['set1'].push(item['event_id']);
      } else if (
        triggerSet === 2 &&
        !clickedEvents['set2'].includes(item['event_id'])
      ) {
        clickedEvents['set2'].push(item['event_id']);
      } else if (
        triggerSet === 3 &&
        !clickedEvents['set3'].includes(item['event_id'])
      ) {
        clickedEvents['set3'].push(item['event_id']);
      }
      if (type === 'trigger1') {
        localStorage.setItem(
          'clickedEventsTrigger1',
          JSON.stringify(clickedEvents)
        );
      } else {
        localStorage.setItem(
          'clickedEventsTrigger2',
          JSON.stringify(clickedEvents)
        );
      }
      setClicked(true);
    }
  };

  return (
    <>
      {matchVisible && !matchHide && (
        <div
          className={`col-lg-4 col-md-6 col-sm-6 col-xs-12 mb-2 pb-2 pt-2 match-item ${
            mobileMatchClicked ? 'mobile-match-opened' : ''
          }`}
        >
          <div className={boxStyle}>
            <div className="current-match" onClick={handleMatchClicked}>
              <div className="name-section">
                <div className="name">
                  {botBalance1 && <div className="orange-dot"></div>}
                  <span className={nameColor1}>{item.player1_name}</span>
                </div>
                <div className="name">
                  {botBalance2 && <div className="orange-dot"></div>}
                  <span className={nameColor2}>{item.player2_name}</span>
                </div>
              </div>
              <div className="left">
                <div className="sub-detail pt-2">
                  <div
                    className={
                      player === 1
                        ? 'sub-left winner ranking'
                        : 'sub-left loser ranking'
                    }
                  >
                    <span>{item.player1_ranking}</span>
                  </div>
                  <div className="sub-bottom-group">
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
              </div>
              <div className="right">
                <div className="sub-detail pt-2">
                  <div
                    className={
                      player === 2
                        ? 'sub-left winner ranking'
                        : 'sub-left loser ranking'
                    }
                  >
                    <span>{item.player2_ranking}</span>
                  </div>
                  <div className="sub-bottom-group">
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
              </div>
              {matchLoading ? (
                <LoadingDetail />
              ) : (
                <TooltipComponent
                  className="tooltip-box league-name-tooltip"
                  content={tooltipContent}
                  tipPointerPosition="Top Center"
                  target="#league_name_tooltip"
                  cssClass="custom-league-tooltip"
                >
                  <div className="opponent-ranking">
                    <div id="league_name_tooltip">
                      <div className="center">
                        <div className="scores">
                          {(type === 'inplay' ||
                            type === 'trigger1' ||
                            type === 'trigger2') &&
                            scores.map((score, index) => (
                              <span
                                key={index}
                                className={
                                  index === scores.length - 1
                                    ? 'playing'
                                    : 'played'
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
                          {type === 'history' && (
                            <div className="history-result">
                              <span>-</span>
                            </div>
                          )}
                          {type === 'upcoming' && (
                            <div className="upcoming-time">
                              <span>{datetime[1]}</span>
                            </div>
                          )}
                          {(type === 'inplay' ||
                            type === 'trigger1' ||
                            type === 'trigger2') &&
                            (item['indicator'] === '0,1' ? (
                              <div className="inplay-left">
                                <span>{item['points']}</span>
                                <div className="inplay-green-dot"></div>
                              </div>
                            ) : item['indicator'] === '1,0' ? (
                              <div className="inplay-right">
                                <div className="inplay-green-dot"></div>
                                <span>{item['points']}</span>
                              </div>
                            ) : (
                              <div className="inplay-no-score">
                                <span>0-0</span>
                              </div>
                            ))}
                        </div>
                      </div>
                    </div>
                  </div>
                </TooltipComponent>
              )}
            </div>
            {!loading && detailOpened && (
              <div className="players-detail">
                <Surface
                  setSelectedSurface={setSelectedSurface}
                  selectedSurface={selectedSurface}
                  showMoreFilters={showMoreFilters}
                  setShowMoreFilters={setShowMoreFilters}
                />
                {showMoreFilters && (
                  <>
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
                          <AverageRanks
                            player_id={item.player1_id}
                            filteredRelationData={filteredRelationData}
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
                          <AverageRanks
                            player_id={item.player2_id}
                            filteredRelationData={filteredRelationData}
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
                    <div className="set-container">
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
                    </div>
                  </>
                )}
                <PlayerDetail
                  player1_id={item.player1_id}
                  player2_id={item.player2_id}
                  filteredRelationData={filteredRelationData}
                  showMoreFilters={showMoreFilters}
                />
              </div>
            )}
          </div>
        </div>
      )}
    </>
  );
};

MatchItem.propTypes = {
  matchCnt: PropTypes.number,
  item: PropTypes.object,
  type: PropTypes.string,
  loading: PropTypes.bool,
  setLoading: PropTypes.func,
  triggerSet: PropTypes.number,
  openedDetail: PropTypes.object,
  setOpenedDetail: PropTypes.func,
  winners: PropTypes.array,
  roboPicks: PropTypes.bool,
  mobileMatchClicked: PropTypes.bool,
  setMobileMatchClicked: PropTypes.func,
};

export default MatchItem;
