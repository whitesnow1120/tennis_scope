import React, { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import PropTypes from 'prop-types';

import BreakHoldDetail from './BreakHoldDetail';

const MatchDetail = (props) => {
  const { match, player_id } = props;

  const { relationData, setNumber, enableOpponentIds } = useSelector(
    (state) => state.tennis
  );
  const scores = match.scores.split(',');
  let opponentId =
    match.player1_id === player_id ? match.player2_id : match.player1_id;
  const opponentRank =
    match.player1_id === player_id
      ? match.player2_ranking
      : match.player1_ranking;
  const [scoreClassName, setScoreClassName] = useState('match-sets-score');
  const [showEnabled, setShowEnabled] = useState(true);
  const [brw, setBRW] = useState(0);
  const [brl, setBRL] = useState(0);
  const [gah, setGAH] = useState(0);

  useEffect(() => {
    if (
      relationData != undefined &&
      setNumber != undefined &&
      player_id in setNumber
    ) {
      let sets =
        relationData['opponents_breaks'][player_id][opponentId]['sets'];
      let opponentBRW = 0;
      let opponentBRL = 0;
      let opponentGAH = 0;

      if (setNumber[player_id] === 'ALL') {
        opponentBRW += sets['brw'].reduce((a, b) => a + b, 0);
        opponentBRL += sets['brl'].reduce((a, b) => a + b, 0);
        opponentGAH += sets['gah'].reduce((a, b) => a + b, 0);
      } else {
        const index = parseInt(setNumber[player_id]) - 1;
        opponentBRW += sets['brw'][index];
        opponentBRL += sets['brl'][index];
        opponentGAH += sets['gah'][index];
      }
      setBRW(opponentBRW);
      setBRL(opponentBRL);
      setGAH(opponentGAH);
    }

    // count of sets
    switch (scores.length) {
      case 1:
        setScoreClassName(scoreClassName + ' match-sets-score-1');
        break;
      case 2:
        setScoreClassName(scoreClassName + ' match-sets-score-2');
        break;
      case 3:
        setScoreClassName(scoreClassName + ' match-sets-score-3');
        break;
      case 4:
        setScoreClassName(scoreClassName + ' match-sets-score-4');
        break;
      case 5:
        setScoreClassName(scoreClassName + ' match-sets-score-5');
        break;
      default:
        break;
    }

    if (
      enableOpponentIds != undefined &&
      enableOpponentIds[player_id] != undefined &&
      enableOpponentIds[player_id].includes(opponentId.toString())
    ) {
      setShowEnabled(true);
    } else {
      setShowEnabled(false);
    }
  }, [match, setNumber, relationData, enableOpponentIds]);

  /**
   * Set the background color of sets
   * @param { number } index
   * @returns classname
   */
  const getScoreClassName = (index) => {
    const score = scores[index].split('-');
    if (match.player1_id == player_id) {
      if (score[0] >= score[1]) {
        return 'bg-won';
      }
      return 'bg-lose';
    } else {
      if (score[0] >= score[1]) {
        return 'bg-lose';
      }
      return 'bg-won';
    }
  };

  return (
    <>
      {showEnabled && (
        <div className="match-detail">
          <div className="opponent-detail">
            <BreakHoldDetail brw={brw} brl={brl} gah={gah}>
              <div className="opponent-ranking">
                <span>{opponentRank}</span>
              </div>
            </BreakHoldDetail>
          </div>
          <div className="match-sets">
            {match.sets.length > 0 &&
              match.sets.map((set, index) => (
                <div key={index} className={scoreClassName}>
                  <div className={getScoreClassName(index)}>
                    <span>{set.score}</span>
                  </div>
                  <div>
                    <span>{set.depth > 0 ? '+' + set.depth : set.depth}</span>
                  </div>
                </div>
              ))}
          </div>
        </div>
      )}
    </>
  );
};

MatchDetail.propTypes = {
  match: PropTypes.object,
  player_id: PropTypes.number,
  playerBRW: PropTypes.number,
  playerBRL: PropTypes.number,
  playerGAH: PropTypes.number,
  setPlayerBRW: PropTypes.func,
  setPlayerBRL: PropTypes.func,
  setPlayerGAH: PropTypes.func,
};

export default MatchDetail;
