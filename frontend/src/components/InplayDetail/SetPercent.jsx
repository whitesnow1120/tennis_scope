import React, { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import PropTypes from 'prop-types';

const SetPercent = (props) => {
  const { relationData, player_id } = props;
  const { enableOpponentIds } = useSelector((state) => state.tennis);

  const [wwCount, setWWCount] = useState(0);
  const [wlCount, setWLCount] = useState(0);
  const [lwCount, setLWCount] = useState(0);
  const [llCount, setLLCount] = useState(0);

  const [wwPercent, setWWPercent] = useState(0);
  const [wlPercent, setWLPercent] = useState(0);
  const [lwPercent, setLWPercent] = useState(0);
  const [llPercent, setLLPercent] = useState(0);

  useEffect(() => {
    if (relationData != undefined && player_id in relationData) {
      let ww = 0;
      let wl = 0;
      let lw = 0;
      let ll = 0;

      relationData[player_id].map((data) => {
        let opponentId =
          data['player1_id'] === player_id
            ? data['player2_id']
            : data['player1_id'];
        if (
          enableOpponentIds != undefined &&
          enableOpponentIds[player_id] != undefined &&
          enableOpponentIds[player_id].includes(opponentId.toString())
        ) {
          ww += data.performance.ww;
          wl += data.performance.wl;
          lw += data.performance.lw;
          ll += data.performance.ll;
        }
      });

      setWWCount(ww);
      setWLCount(wl);
      setLWCount(lw);
      setLLCount(ll);

      const total = ww + wl + lw + ll;
      if (total == 0) {
        setWWPercent(0);
        setWLPercent(0);
        setLWPercent(0);
        setLLPercent(0);
      } else {
        setWWPercent(Math.round((ww / total) * 100));
        setWLPercent(Math.round((wl / total) * 100));
        setLWPercent(Math.round((lw / total) * 100));
        setLLPercent(Math.round((ll / total) * 100));
      }
    }
  }, [relationData, enableOpponentIds]);

  return (
    <>
      <div className="percent-sub-left">
        <div className="win-win">
          <span>{'W>W'}</span>
          <span>{wwCount}</span>
          <span>[{wwPercent}%]</span>
        </div>
        <div className="lose-win">
          <span>{'L>W'}</span>
          <span>{wlCount}</span>
          <span>[{wlPercent}%]</span>
        </div>
      </div>
      <div className="percent-sub-right">
        <div className="win-lose">
          <span>{'W>L'}</span>
          <span>{lwCount}</span>
          <span>[{lwPercent}%]</span>
        </div>
        <div className="lose-lose">
          <span>{'L>L'}</span>
          <span>{llCount}</span>
          <span>[{llPercent}%]</span>
        </div>
      </div>
    </>
  );
};

SetPercent.propTypes = {
  relationData: PropTypes.any,
  player_id: PropTypes.number,
};

export default SetPercent;
