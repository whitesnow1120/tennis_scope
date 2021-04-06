import React from 'react';
import PropTypes from 'prop-types';

const RankButtonGroup = (props) => {
  const { activeRank, setActiveRank } = props;

  const handleChange = (filter) => {
    localStorage.setItem('rankFilter', filter);
    setActiveRank(filter);
  };

  return (
    <div
      className="btn-group rank-button-group"
      role="group"
      aria-label="Basic example"
    >
      <button
        type="button"
        className={
          activeRank === '1' ? 'btn btn-secondary active' : 'btn btn-secondary'
        }
        onClick={() => handleChange('1')}
      >
        <span>All</span>
      </button>
      <button
        type="button"
        className={
          activeRank === '2' ? 'btn btn-secondary active' : 'btn btn-secondary'
        }
        onClick={() => handleChange('2')}
      >
        Ranked
      </button>
      <button
        type="button"
        className={
          activeRank === '3' ? 'btn btn-secondary active' : 'btn btn-secondary'
        }
        onClick={() => handleChange('3')}
      >
        Unranked
      </button>
    </div>
  );
};

RankButtonGroup.propTypes = {
  activeRank: PropTypes.string,
  setActiveRank: PropTypes.func,
};

export default RankButtonGroup;
