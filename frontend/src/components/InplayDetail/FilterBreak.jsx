import React from 'react';
import PropTypes from 'prop-types';

const FilterBreak = (props) => {
  const { selectedBreakDiff, setSelectedBreakDiff } = props;

  const handleClicked = (breakDiff) => {
    setSelectedBreakDiff(breakDiff);
  };

  return (
    <div className="break-diff">
      <div
        className={
          selectedBreakDiff === 'ALL'
            ? 'active break-diff-all'
            : 'break-diff-all'
        }
        onClick={() => handleClicked('ALL')}
      >
        <span>ALL</span>
      </div>
      <div
        className={
          selectedBreakDiff === 'LLB'
            ? 'active break-diff-llb'
            : 'break-diff-llb'
        }
        onClick={() => handleClicked('LLB')}
      >
        <span>LLB</span>
      </div>
      <div
        className={
          selectedBreakDiff === 'MWB'
            ? 'active break-diff-mwb'
            : 'break-diff-mwb'
        }
        onClick={() => handleClicked('MWB')}
      >
        <span>MWB</span>
      </div>
    </div>
  );
};

FilterBreak.propTypes = {
  selectedBreakDiff: PropTypes.string,
  setSelectedBreakDiff: PropTypes.func,
};

export default FilterBreak;
