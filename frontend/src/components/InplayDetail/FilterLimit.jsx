import React from 'react';
import PropTypes from 'prop-types';

const FilterLimit = (props) => {
  const { selectedLimit, setSelectedLimit } = props;

  const handleClicked = (limit) => {
    setSelectedLimit(limit);
  };

  return (
    <div className="limit-count">
      <div
        className={
          selectedLimit === 10
            ? 'active limit-count-l-10'
            : 'limit-count-l-10'
        }
        onClick={() => handleClicked(10)}
      >
        <span>L10</span>
      </div>
      <div
        className={
          selectedLimit === 15
            ? 'active limit-count-l-15'
            : 'limit-count-l-15'
        }
        onClick={() => handleClicked(15)}
      >
        <span>L15</span>
      </div>
      <div
        className={
          selectedLimit === 20
            ? 'active limit-count-l-20'
            : 'limit-count-l-20'
        }
        onClick={() => handleClicked(20)}
      >
        <span>L20</span>
      </div>
    </div>
  );
};

FilterLimit.propTypes = {
  selectedLimit: PropTypes.number,
  setSelectedLimit: PropTypes.func,
};

export default FilterLimit;
