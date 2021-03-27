import React from 'react';
import PropTypes from 'prop-types';

const FilterOpponent = (props) => {
  const { selectedOpponent, setSelectedOpponent } = props;

  const handleClicked = (opponent) => {
    setSelectedOpponent(opponent);
  };

  return (
    <div className="rank-opponent">
      <div
        className={
          selectedOpponent === 'ALL'
            ? 'active rank-opponent-all'
            : 'rank-opponent-all'
        }
        onClick={() => handleClicked('ALL')}
      >
        <span>ALL</span>
      </div>
      <div
        className={
          selectedOpponent === 'SRO'
            ? 'active rank-opponent-sro'
            : 'rank-opponent-sro'
        }
        onClick={() => handleClicked('SRO')}
      >
        <span>SRO</span>
      </div>
      <div
        className={
          selectedOpponent === 'SO'
            ? 'active rank-opponent-so'
            : 'rank-opponent-so'
        }
        onClick={() => handleClicked('SO')}
      >
        <span>SO</span>
      </div>
    </div>
  );
};

FilterOpponent.propTypes = {
  selectedOpponent: PropTypes.string,
  setSelectedOpponent: PropTypes.func,
};

export default FilterOpponent;
