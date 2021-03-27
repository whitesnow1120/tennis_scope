import React from 'react';
import PropTypes from 'prop-types';

const FilterRank = (props) => {
  const { selectedRankDiff, setSelectedRankDiff } = props;

  const handleClicked = (rankDiff) => {
    setSelectedRankDiff(rankDiff);
  };

  return (
    <div className="rank-diff">
      <div
        className={
          selectedRankDiff === 'ALL' ? 'active rank-diff-all' : 'rank-diff-all'
        }
        onClick={() => handleClicked('ALL')}
      >
        <span>ALL</span>
      </div>
      <div
        className={
          selectedRankDiff === 'HIR' ? 'active rank-diff-hir' : 'rank-diff-hir'
        }
        onClick={() => handleClicked('HIR')}
      >
        <span>HIR</span>
      </div>
      <div
        className={
          selectedRankDiff === 'LOR' ? 'active rank-diff-lor' : 'rank-diff-lor'
        }
        onClick={() => handleClicked('LOR')}
      >
        <span>LOR</span>
      </div>
    </div>
  );
};

FilterRank.propTypes = {
  selectedRankDiff: PropTypes.string,
  setSelectedRankDiff: PropTypes.func,
};

export default FilterRank;
