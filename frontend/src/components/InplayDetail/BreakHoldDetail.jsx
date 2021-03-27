import React from 'react';
import PropTypes from 'prop-types';

const BreakHoldDetail = (props) => {
  const { brw, brl, gah, children } = props;

  return (
    <>
      <div className="break-hold-detail">
        {children}
        <div className="breaks-won">
          <span>BRW</span>
          <span>{brw}</span>
        </div>
        <div className="breaks-lost">
          <span>BRL</span>
          <span>{brl}</span>
        </div>
        <div className="games-hold">
          <span>GAH</span>
          <span>{gah}</span>
        </div>
      </div>
    </>
  );
};

BreakHoldDetail.propTypes = {
  brw: PropTypes.number,
  brl: PropTypes.number,
  gah: PropTypes.number,
  children: PropTypes.any,
};

export default BreakHoldDetail;