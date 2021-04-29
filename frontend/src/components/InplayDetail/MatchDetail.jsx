import React, { useState, useEffect } from 'react';
import { TooltipComponent } from '@syncfusion/ej2-react-popups';
import PropTypes from 'prop-types';

import { formatDateTime } from '../../utils';
import OpponentDetail from './OpponentDetail';

const MatchDetail = (props) => {
  const { match } = props;
  const [scoreClassName, setScoreClassName] = useState('match-sets-score');
  const [opponentRank, setOpponentRank] = useState('-');
  const [scores, setScores] = useState([]);
  const [depths, setDepths] = useState([]);
  const [totalDepths, setTotalDepths] = useState(0);
  const [playerOdd, setPlayerOdd] = useState('-');
  const [tooltipContent, setToolTipContent] = useState('');
  const [surface, setSurface] = useState('-');

  useEffect(() => {
    if (match != undefined) {
      setPlayerOdd(
        match['p_odd'] === null ? '-' : parseFloat(match['p_odd']).toFixed(2)
      );
      const matchDate = formatDateTime(match.time);
      let content = `<div class='opponent-tooltip-content'><span>${matchDate[0]}</span>`;
      content += `<span>${match['o_name']}</span>`;
      let matchSurface = match['surface'] === null ? '-' : match['surface'];
      let odd = match['o_odd'] === null ? '-' : match['o_odd'];
      content += `<span>${matchSurface} ${odd}</span></div>`;
      setSurface(matchSurface);

      setToolTipContent(content);
      const scoreClsName = 'match-sets-score';
      const allScores = match.scores.split(',');
      setScores(allScores);
      setOpponentRank(match['o_ranking'] === null ? '-' : match['o_ranking']);
      const depth = JSON.parse(match['p_depths']);
      setDepths(depth);
      setTotalDepths(depth.reduce((a, b) => parseInt(a) + parseInt(b), 0));

      // count of sets
      switch (allScores.length) {
        case 1:
          setScoreClassName(scoreClsName + ' match-sets-score-1');
          break;
        case 2:
          setScoreClassName(scoreClsName + ' match-sets-score-2');
          break;
        case 3:
          setScoreClassName(scoreClsName + ' match-sets-score-3');
          break;
        case 4:
          setScoreClassName(scoreClsName + ' match-sets-score-4');
          break;
        case 5:
          setScoreClassName(scoreClsName + ' match-sets-score-5');
          break;
        default:
          break;
      }
    }
  }, [match]);

  /**
   * Set the background color of sets
   * @param { number } index
   * @returns classname
   */
  const getScoreClassName = (index) => {
    const score = scores[index].split('-');
    if (match['home'] === 'p') {
      if (parseInt(score[0]) >= parseInt(score[1])) {
        return 'bg-won';
      }
      return 'bg-lose';
    } else if (match['home'] === 'o') {
      if (parseInt(score[0]) >= parseInt(score[1])) {
        return 'bg-lose';
      }
      return 'bg-won';
    }
  };

  return (
    <>
      <div className="match-detail">
        <div className="opponent-detail">
          <OpponentDetail
            playerOdd={playerOdd}
            oRW={Math.round(match['oRW'])}
            oRL={Math.round(match['oRL'])}
            oGIR={match['oGIR']}
            surface={surface}
          >
            <TooltipComponent
              className="tooltip-box"
              content={tooltipContent}
              tipPointerPosition="Start"
              target="#opponent_tooltip"
              cssClass="custom-opponent-tooltip"
            >
              <div className="opponent-ranking">
                <div id="opponent_tooltip">
                  <span>{opponentRank}</span>
                </div>
              </div>
            </TooltipComponent>
          </OpponentDetail>
        </div>
        <div className="match-sets">
          {scores.length > 0 &&
            scores.map((score, index) => (
              <div key={index} className={scoreClassName}>
                <div className={getScoreClassName(index)}>
                  <span>{score}</span>
                </div>
                <div>
                  <span>{totalDepths === 0 ? '-' : depths[index]}</span>
                </div>
              </div>
            ))}
        </div>
      </div>
    </>
  );
};

MatchDetail.propTypes = {
  match: PropTypes.object,
};

export default MatchDetail;
