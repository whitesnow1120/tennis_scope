import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import PropTypes from 'prop-types';

import { GET_RELATION_ENABLE_OPPONENT_IDS } from '../../store/actions/types';
import BreakHoldDetail from './BreakHoldDetail';
import SetPercent from './SetPercent';
import MatchDetail from './MatchDetail';

const PlayerDetail = (props) => {
  const { player1_id, player2_id } = props;
  const dispatch = useDispatch();
  const { relationData, setNumber, breaks } = useSelector(
    (state) => state.tennis
  );

  const [player1BRW, setPlayer1BRW] = useState(0);
  const [player1BRL, setPlayer1BRL] = useState(0);
  const [player1GAH, setPlayer1GAH] = useState(0);
  const [player2BRW, setPlayer2BRW] = useState(0);
  const [player2BRL, setPlayer2BRL] = useState(0);
  const [player2GAH, setPlayer2GAH] = useState(0);

  useEffect(() => {
    // calculate BRW, BRL, and GAH for the player
    let oIds1 = [];
    let oIds2 = [];

    if (relationData != undefined && player1_id in relationData) {
      let brw = 0;
      let brl = 0;
      let gah = 0;
      relationData[player1_id].map((data) => {
        if (setNumber[player1_id] == 'ALL') {
          data['sets'].map((set) => {
            brw += set[player1_id].brw.reduce((a, b) => a + b, 0);
            brl += set[player1_id].brl.reduce((a, b) => a + b, 0);
            gah += set[player1_id].gah.reduce((a, b) => a + b, 0);
          });
        } else {
          if (data['sets'].length >= parseInt(setNumber[player1_id])) {
            const set = data['sets'][parseInt(setNumber[player1_id]) - 1];
            brw += set[player1_id].brw.reduce((a, b) => a + b, 0);
            brl += set[player1_id].brl.reduce((a, b) => a + b, 0);
            gah += set[player1_id].gah.reduce((a, b) => a + b, 0);
          }
        }
        setPlayer1BRW(brw);
        setPlayer1BRL(brl);
        setPlayer1GAH(gah);
      });

      if (player1_id in breaks) {
        const opponentBreaks = relationData['opponents_breaks'][player1_id];
        const opponentIds = Object.keys(opponentBreaks);
        if (breaks[player1_id] != 'ALL') {
          // Compare LLB, MWB
          let opponentBRW = 0;
          let opponentBRL = 0;

          opponentIds.map((id) => {
            if (setNumber[player1_id] == 'ALL') {
              opponentBRW = opponentBreaks[id]['sets']['brw'].reduce(
                (a, b) => a + b,
                0
              );
              opponentBRL = opponentBreaks[id]['sets']['brl'].reduce(
                (a, b) => a + b,
                0
              );
            } else {
              const index = parseInt(setNumber[player1_id] - 1);
              opponentBRW = opponentBreaks[id]['sets']['brw'][index];
              opponentBRL = opponentBreaks[id]['sets']['brl'][index];
            }
            if (breaks[player1_id] == 'LLB') {
              if (opponentBRL < brl) {
                oIds1.push(id);
              }
            } else if (breaks[player1_id] == 'MWB') {
              if (opponentBRW > brw) {
                oIds1.push(id);
              }
            }
          });
        } else {
          oIds1 = opponentIds;
        }
      }
    }

    if (relationData != undefined && player2_id in relationData) {
      let brw = 0;
      let brl = 0;
      let gah = 0;
      relationData[player2_id].map((data) => {
        if (setNumber[player2_id] == 'ALL') {
          data['sets'].map((set) => {
            brw += set[player2_id].brw.reduce((a, b) => a + b, 0);
            brl += set[player2_id].brl.reduce((a, b) => a + b, 0);
            gah += set[player2_id].gah.reduce((a, b) => a + b, 0);
          });
        } else {
          if (data['sets'].length >= parseInt(setNumber[player2_id])) {
            const set = data['sets'][parseInt(setNumber[player2_id]) - 1];
            brw += set[player2_id].brw.reduce((a, b) => a + b, 0);
            brl += set[player2_id].brl.reduce((a, b) => a + b, 0);
            gah += set[player2_id].gah.reduce((a, b) => a + b, 0);
          }
        }
        setPlayer2BRW(brw);
        setPlayer2BRL(brl);
        setPlayer2GAH(gah);
      });

      if (player2_id in breaks) {
        const opponentBreaks = relationData['opponents_breaks'][player2_id];
        const opponentIds = Object.keys(opponentBreaks);
        if (breaks[player2_id] != 'ALL') {
          // Compare LLB, MWB
          let opponentBRW = 0;
          let opponentBRL = 0;

          opponentIds.map((id) => {
            if (setNumber[player2_id] == 'ALL') {
              opponentBRW = opponentBreaks[id]['sets']['brw'].reduce(
                (a, b) => a + b,
                0
              );
              opponentBRL = opponentBreaks[id]['sets']['brl'].reduce(
                (a, b) => a + b,
                0
              );
            } else {
              const index = parseInt(setNumber[player2_id] - 1);
              opponentBRW = opponentBreaks[id]['sets']['brw'][index];
              opponentBRL = opponentBreaks[id]['sets']['brl'][index];
            }

            if (breaks[player2_id] == 'LLB') {
              if (opponentBRL < brl) {
                oIds2.push(id);
              }
            } else if (breaks[player2_id] == 'MWB') {
              if (opponentBRW > brw) {
                oIds2.push(id);
              }
            }
          });
        } else {
          oIds2 = opponentIds;
        }
      }
    }

    // update enable opponent ids
    let idsPayload = {};
    idsPayload[player1_id] = oIds1;
    idsPayload[player2_id] = oIds2;
    dispatch({
      type: GET_RELATION_ENABLE_OPPONENT_IDS,
      payload: idsPayload,
    });
  }, [relationData, setNumber, breaks]);

  return (
    <>
      <div className="player-detail">
        <div className="player-detail-left">
          <BreakHoldDetail brw={player1BRW} brl={player1BRL} gah={player1GAH} />
        </div>
        <div className="player-detail-right">
          <BreakHoldDetail brw={player2BRW} brl={player2BRL} gah={player2GAH} />
        </div>
      </div>
      <div className="set-percent">
        <div className="set-percent-left">
          <SetPercent relationData={relationData} player_id={player1_id} />
        </div>
        <div className="set-percent-right">
          <SetPercent relationData={relationData} player_id={player2_id} />
        </div>
      </div>
      <div className="match-details">
        <div className="match-details-left">
          {relationData != undefined &&
            player1_id in relationData &&
            relationData[player1_id].length > 0 &&
            relationData[player1_id].map((match, index) => (
              <MatchDetail key={index} match={match} player_id={player1_id} />
            ))}
        </div>
        <div className="match-details-right">
          {relationData != undefined &&
            player2_id in relationData &&
            relationData[player2_id].length > 0 &&
            relationData[player2_id].map((match, index) => (
              <MatchDetail key={index} match={match} player_id={player2_id} />
            ))}
        </div>
      </div>
    </>
  );
};

PlayerDetail.propTypes = {
  relationData: PropTypes.object,
  player1_id: PropTypes.number,
  player2_id: PropTypes.number,
};

export default PlayerDetail;
