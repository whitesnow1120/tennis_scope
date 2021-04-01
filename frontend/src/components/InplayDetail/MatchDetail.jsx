import React, { useState, useEffect } from 'react';
import { TooltipComponent } from '@syncfusion/ej2-react-popups';
import PropTypes from 'prop-types';

import { formatDateTime } from '../../utils';
import BreakHoldDetail from './BreakHoldDetail';

const MatchDetail = (props) => {
  const { match } = props;
  const [scoreClassName, setScoreClassName] = useState('match-sets-score');
  const [opponentRank, setOpponentRank] = useState('-');
  const [scores, setScores] = useState([]);
  const [depths, setDepths] = useState([]);
  const [home, setHome] = useState(0);
  const [tooltipContent, setToolTipContent] = useState("");

  useEffect(() => {
    if (match != undefined) {
      const matchDate = formatDateTime(match.time);
      let content = `<div class='opponent-tooltip-content'><span>${matchDate[0]}</span>`;
      content += `<span>${match["o_name"]}</span>`;
      let surface = match["surface"] === null ? "-" : match["surface"];
      let odd = match["o_odd"] === null ? "-" : match["o_odd"];
      content += `<span>${surface} ${odd}</span></div>`;

      setToolTipContent(content);
      const scoreClsName = 'match-sets-score';
      const allScores = match.scores.split(',');
      setScores(allScores);
      setOpponentRank(match['o_ranking'] === null ? '-' : match['o_ranking']);
      setDepths(JSON.parse(match['p_depths']));

      const pWW = parseInt(JSON.parse(match['p_ww'])[0]);
      const pWL = parseInt(JSON.parse(match['p_wl'])[0]);
      const pLW = parseInt(JSON.parse(match['p_lw'])[0]);
      const pLL = parseInt(JSON.parse(match['p_ll'])[0]);
      const won = pWW + pLW;
      const lost = pWL + pLL;
      const leftScore = allScores[0].split('-')[0];
      const rightScore = allScores[0].split('-')[1];
      if (leftScore > rightScore) {
        if (won > lost) {
          setHome(1);
        } else {
          setHome(2);
        }
      } else {
        if (won > lost) {
          setHome(2);
        } else {
          setHome(1);
        }
      }
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
    if (home === 1) {
      if (score[0] >= score[1]) {
        return 'bg-won';
      }
      return 'bg-lose';
    } else if (home === 2) {
      if (score[0] >= score[1]) {
        return 'bg-lose';
      }
      return 'bg-won';
    }
  };

  return (
    <>
      <div className="match-detail">
        <div className="opponent-detail">
          <BreakHoldDetail player={false}>
            <TooltipComponent
              className="tooltip-box"
              content={tooltipContent}
              tipPointerPosition="Start"
              target="#opponent_tooltip"
            >
              <div className="opponent-ranking">
                <div id="opponent_tooltip">
                  <span>{opponentRank}</span>
                </div>
              </div>
            </TooltipComponent>
          </BreakHoldDetail>
        </div>
        <div className="match-sets">
          {scores.length > 0 &&
            scores.map((score, index) => (
              <div key={index} className={scoreClassName}>
                <div className={getScoreClassName(index)}>
                  <span>{score}</span>
                </div>
                <div>
                  <span>
                    {depths[index] > 0 ? '+' + depths[index] : depths[index]}
                  </span>
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
