import React, { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import PropTypes from 'prop-types';

const AverageRanks = (props) => {
  const { player_id } = props;
  const { filteredRelationData } = useSelector((state) => state.tennis);
  const [raw, setRAW] = useState(0);
  const [ral, setRAL] = useState(0);

  useEffect(() => {
    if (filteredRelationData != undefined) {
      const totalRAW = filteredRelationData['performance'][player_id]['RAW'];
      const totalRAL = filteredRelationData['performance'][player_id]['RAL'];
      const sumRAW = totalRAW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
      const sumRAL = totalRAL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
      const totalRAWCount = totalRAW.length;
      const totalRALCount = totalRAL.length;
      if (totalRAWCount === 0) {
        setRAW(0);
      } else {
        setRAW(Math.round(sumRAW / totalRAWCount));
      }

      if (totalRALCount === 0) {
        setRAL(0);
      } else {
        setRAL(Math.round(sumRAL / totalRALCount));
      }
    }
  }, [filteredRelationData]);

  return (
    <div className="average-ranks">
      <div className="average-raw">
        <span>RAW:</span>
        <span>{raw}</span>
      </div>
      <div className="average-ral">
        <span>RAL:</span>
        <span>{ral}</span>
      </div>
    </div>
  );
};

AverageRanks.propTypes = {
  player_id: PropTypes.number,
};

export default AverageRanks;
