import React from 'react';
import PropTypes from 'prop-types';

const OpponentDetail = (props) => {
  const { playerOdd, children } = props;

  return (
    <>
      <div className="opponent-sub-detail">
        {children}
        <div className="player-odd">
          <span>{playerOdd.toString()}</span>
        </div>
        <div className="opponent-raw">
          <span>RAW:</span>
          <span>{0}</span>
        </div>
        <div className="opponent-ral">
          <span>RAL:</span>
          <span>{0}</span>
        </div>
      </div>
    </>
  );
};

OpponentDetail.propTypes = {
  playerOdd: PropTypes.string,
  children: PropTypes.any,
};

export default OpponentDetail;
