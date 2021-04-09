import React from 'react';
import PropTypes from 'prop-types';

const OpponentDetail = (props) => {
  const { playerOdd, oRW, oRL, oGIR, children } = props;

  return (
    <>
      <div className="opponent-sub-detail">
        {children}
        <div className="player-odd">
          <span>{playerOdd.toString()}</span>
        </div>
        <div className="opponent-raw">
          <span>RW:</span>
          <span>{oRW}</span>
        </div>
        <div className="opponent-ral">
          <span>RL:</span>
          <span>{oRL}</span>
        </div>
        <div className="opponent-gir">
          <span>GIR:</span>
          <span>{oGIR}</span>
        </div>
      </div>
    </>
  );
};

OpponentDetail.propTypes = {
  playerOdd: PropTypes.string,
  oRW: PropTypes.number,
  oRL: PropTypes.number,
  oGIR: PropTypes.any,
  children: PropTypes.any,
};

export default OpponentDetail;
