import React, { useEffect, useState } from 'react';
// import { useSelector } from 'react-redux';
import PropTypes from 'prop-types';

import BreakHoldDetail from './BreakHoldDetail';
import SetPercent from './SetPercent';
import MatchDetail from './MatchDetail';

const PlayerDetail = (props) => {
  const { player1_id, player2_id, filteredRelationData } = props;
  // const { filteredRelationData } = useSelector((state) => state.tennis);
  const [player1BRW, setPlayer1BRW] = useState(0);
  const [player1BRL, setPlayer1BRL] = useState(0);
  const [player1GAH, setPlayer1GAH] = useState(0);
  const [player1GRA, setPlayer1GRA] = useState('0');
  const [player2BRW, setPlayer2BRW] = useState(0);
  const [player2BRL, setPlayer2BRL] = useState(0);
  const [player2GAH, setPlayer2GAH] = useState(0);
  const [player2GRA, setPlayer2GRA] = useState('0');

  useEffect(() => {
    if (
      filteredRelationData != undefined &&
      'performance' in filteredRelationData &&
      player1_id in filteredRelationData['performance']
    ) {
      setPlayer1BRW(filteredRelationData['performance'][player1_id]['pBRW']);
      setPlayer1BRL(filteredRelationData['performance'][player1_id]['pBRL']);
      setPlayer1GAH(filteredRelationData['performance'][player1_id]['pGAH']);
      setPlayer1GRA(filteredRelationData['performance'][player1_id]['pGRA']);
    }

    if (
      filteredRelationData != undefined &&
      'performance' in filteredRelationData &&
      player2_id in filteredRelationData['performance']
    ) {
      setPlayer2BRW(filteredRelationData['performance'][player2_id]['pBRW']);
      setPlayer2BRL(filteredRelationData['performance'][player2_id]['pBRL']);
      setPlayer2GAH(filteredRelationData['performance'][player2_id]['pGAH']);
      setPlayer2GRA(filteredRelationData['performance'][player2_id]['pGRA']);
    }
  }, [filteredRelationData]);

  return (
    <>
      <div className="player-detail">
        <div className="player-detail-left">
          <BreakHoldDetail
            brw={player1BRW}
            brl={player1BRL}
            gah={player1GAH}
            gra={player1GRA}
          />
        </div>
        <div className="player-detail-right">
          <BreakHoldDetail
            brw={player2BRW}
            brl={player2BRL}
            gah={player2GAH}
            gra={player2GRA}
          />
        </div>
      </div>
      <div className="set-percent">
        <div className="set-percent-left">
          <SetPercent
            player_id={player1_id}
            filteredRelationData={filteredRelationData}
          />
        </div>
        <div className="set-percent-right">
          <SetPercent
            player_id={player2_id}
            filteredRelationData={filteredRelationData}
          />
        </div>
      </div>
      <div className="match-details">
        <div className="match-details-left">
          {filteredRelationData != undefined &&
            player1_id in filteredRelationData &&
            filteredRelationData[player1_id].length > 0 &&
            filteredRelationData[player1_id].map((match, index) => (
              <MatchDetail key={index} match={match} />
            ))}
        </div>
        <div className="match-details-right">
          {filteredRelationData != undefined &&
            player2_id in filteredRelationData &&
            filteredRelationData[player2_id].length > 0 &&
            filteredRelationData[player2_id].map((match, index) => (
              <MatchDetail key={index} match={match} />
            ))}
        </div>
      </div>
    </>
  );
};

PlayerDetail.propTypes = {
  player1_id: PropTypes.number,
  player2_id: PropTypes.number,
  filteredRelationData: PropTypes.object,
};

export default PlayerDetail;
