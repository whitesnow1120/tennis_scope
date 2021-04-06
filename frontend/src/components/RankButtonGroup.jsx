import React from 'react';
import PropTypes from 'prop-types';

const RankButtonGroup = (props) => {
  const { activeFilter, setActiveFilter } = props;

  return (
    <div
      className="btn-group rank-button-group"
      role="group"
      aria-label="Basic example"
    >
      <button
        type="button"
        className={
          activeFilter === 1 ? 'btn btn-secondary active' : 'btn btn-secondary'
        }
        onClick={() => setActiveFilter(1)}
      >
        <span>All</span>
      </button>
      <button
        type="button"
        className={
          activeFilter === 2 ? 'btn btn-secondary active' : 'btn btn-secondary'
        }
        onClick={() => setActiveFilter(2)}
      >
        Both Ranked
      </button>
      <button
        type="button"
        className={
          activeFilter === 3 ? 'btn btn-secondary active' : 'btn btn-secondary'
        }
        onClick={() => setActiveFilter(3)}
      >
        One Ranked
      </button>
      <button
        type="button"
        className={
          activeFilter === 4 ? 'btn btn-secondary active' : 'btn btn-secondary'
        }
        onClick={() => setActiveFilter(4)}
      >
        Both Unranked
      </button>
    </div>
  );
};

RankButtonGroup.propTypes = {
  setActiveFilter: PropTypes.func,
  activeFilter: PropTypes.number,
};

export default RankButtonGroup;
