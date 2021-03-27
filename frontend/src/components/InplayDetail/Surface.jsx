import React from 'react';
import PropTypes from 'prop-types';

const Surface = (props) => {
  const { selectedSurface, setSelectedSurface } = props;

  const handleClicked = (surfaceType) => {
    setSelectedSurface(surfaceType);
  };

  return (
    <div className="players-detail-surface">
      <div
        className={selectedSurface === 'ALL' ? 'active' : ''}
        onClick={() => handleClicked('ALL')}
      >
        <span>ALL</span>
      </div>
      <div
        className={selectedSurface === 'CLY' ? 'active' : ''}
        onClick={() => handleClicked('CLY')}
      >
        <span>CLY</span>
      </div>
      <div
        className={selectedSurface === 'HRD' ? 'active' : ''}
        onClick={() => handleClicked('HRD')}
      >
        <span>HRD</span>
      </div>
      <div
        className={selectedSurface === 'IND' ? 'active' : ''}
        onClick={() => handleClicked('IND')}
      >
        <span>IND</span>
      </div>
      <div
        className={selectedSurface === 'GRS' ? 'active' : ''}
        onClick={() => handleClicked('GRS')}
      >
        <span>GRS</span>
      </div>
    </div>
  );
};

Surface.propTypes = {
  selectedSurface: PropTypes.string,
  setSelectedSurface: PropTypes.func,
};

export default Surface;
