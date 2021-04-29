import React from 'react';
import PropTypes from 'prop-types';

const Surface = (props) => {
  const {
    selectedSurface,
    setSelectedSurface,
    showMoreFilters,
    setShowMoreFilters,
  } = props;

  const handleClicked = (surfaceType) => {
    setSelectedSurface(surfaceType);
  };

  const handleShowMoreClicked = () => {
    setShowMoreFilters(!showMoreFilters);
  };

  return (
    <div className="players-detail-surface">
      <div className="surface-items">
        <div
          className={selectedSurface === 'ALL' ? 'surface active' : 'surface'}
          onClick={() => handleClicked('ALL')}
        >
          <span>ALL</span>
        </div>
        <div
          className={selectedSurface === 'CLY' ? 'surface active' : 'surface'}
          onClick={() => handleClicked('CLY')}
        >
          <span>CLY</span>
        </div>
        <div
          className={selectedSurface === 'HRD' ? 'surface active' : 'surface'}
          onClick={() => handleClicked('HRD')}
        >
          <span>HRD</span>
        </div>
        <div
          className={selectedSurface === 'IND' ? 'surface active' : 'surface'}
          onClick={() => handleClicked('IND')}
        >
          <span>IND</span>
        </div>
        <div
          className={selectedSurface === 'GRS' ? 'surface active' : 'surface'}
          onClick={() => handleClicked('GRS')}
        >
          <span>GRS</span>
        </div>
      </div>
      <div className="surface-show-more">
        <div className="show-more">
          <span>{showMoreFilters ? 'Show less' : 'Show more'}</span>
        </div>
        <div className="show-more-button" onClick={handleShowMoreClicked}>
          <span>{showMoreFilters ? '-' : '+'}</span>
        </div>
      </div>
    </div>
  );
};

Surface.propTypes = {
  selectedSurface: PropTypes.string,
  setSelectedSurface: PropTypes.func,
  showMoreFilters: PropTypes.bool,
  setShowMoreFilters: PropTypes.func,
};

export default Surface;
