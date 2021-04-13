import React, { useState, useEffect } from 'react';
// import { useSelector } from 'react-redux';
import PropTypes from 'prop-types';

const SetPercent = (props) => {
  const { player_id, filteredRelationData } = props;
  // const { filteredRelationData } = useSelector((state) => state.tennis);

  const [wwCount, setWWCount] = useState(0);
  const [wlCount, setWLCount] = useState(0);
  const [lwCount, setLWCount] = useState(0);
  const [llCount, setLLCount] = useState(0);

  const [wwPercent, setWWPercent] = useState(0);
  const [wlPercent, setWLPercent] = useState(0);
  const [lwPercent, setLWPercent] = useState(0);
  const [llPercent, setLLPercent] = useState(0);

  useEffect(() => {
    if (
      filteredRelationData != undefined &&
      'performance' in filteredRelationData &&
      player_id in filteredRelationData['performance']
    ) {
      const ww = filteredRelationData['performance'][player_id]['pWW'];
      const wl = filteredRelationData['performance'][player_id]['pWL'];
      const lw = filteredRelationData['performance'][player_id]['pLW'];
      const ll = filteredRelationData['performance'][player_id]['pLL'];
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
  }, [filteredRelationData]);

  return (
    <>
      <div className="percent-sub-left">
        <div className="win-win">
          <div>
            <span>{'W>W'}</span>
          </div>
          <div>
            <span>{wwCount}</span>
          </div>
          <div>
            <span>[{wwPercent}%]</span>
          </div>
        </div>
        <div className="lose-win">
          <div>
            <span>{'L>W'}</span>
          </div>
          <div>
            <span>{wlCount}</span>
          </div>
          <div>
            <span>[{wlPercent}%]</span>
          </div>
        </div>
      </div>
      <div className="percent-sub-right">
        <div className="win-lose">
          <div>
            <span>{'W>L'}</span>
          </div>
          <div>
            <span>{lwCount}</span>
          </div>
          <div>
            <span>[{lwPercent}%]</span>
          </div>
        </div>
        <div className="lose-lose">
          <div>
            <span>{'L>L'}</span>
          </div>
          <div>
            <span>{llCount}</span>
          </div>
          <div>
            <span>[{llPercent}%]</span>
          </div>
        </div>
      </div>
    </>
  );
};

SetPercent.propTypes = {
  player_id: PropTypes.number,
  filteredRelationData: PropTypes.object,
};

export default SetPercent;
