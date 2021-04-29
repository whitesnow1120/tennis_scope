import React from 'react';
import PropTypes from 'prop-types';

const OpponentDetail = (props) => {
  const { playerOdd, oRW, oRL, oGIR, surface, children } = props;

  return (
    <>
      <div className="opponent-sub-detail">
        <div>
          {children}
          <div className="mobile-surface">
            <span>{surface}</span>
          </div>
          <div className="player-odd">
            <span>{playerOdd.toString()}</span>
          </div>
        </div>
        <div>
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
      </div>
    </>
  );
};

OpponentDetail.propTypes = {
  playerOdd: PropTypes.string,
  oRW: PropTypes.number,
  oRL: PropTypes.number,
  oGIR: PropTypes.any,
  surface: PropTypes.string,
  children: PropTypes.any,
};

export default OpponentDetail;
