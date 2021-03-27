import React from 'react';
import PropTypes from 'prop-types';

const Set = (props) => {
  const { selectedSet, setSelectedSet } = props;

  const handleClicked = (set) => {
    setSelectedSet(set);
  };

  return (
    <>
      <div className="set-title">
        <span>SET</span>
      </div>
      <div
        className={selectedSet === 'ALL' ? 'active set-all' : 'set-all'}
        onClick={() => handleClicked('ALL')}
      >
        <span>ALL</span>
      </div>
      <div
        className={selectedSet === '1' ? 'active set-1' : 'set-1'}
        onClick={() => handleClicked('1')}
      >
        <span>1</span>
      </div>
      <div
        className={selectedSet === '2' ? 'active set-2' : 'set-2'}
        onClick={() => handleClicked('2')}
      >
        <span>2</span>
      </div>
      <div
        className={selectedSet === '3' ? 'active set-3' : 'set-3'}
        onClick={() => handleClicked('3')}
      >
        <span>3</span>
      </div>
      <div
        className={selectedSet === '4' ? 'active set-4' : 'set-4'}
        onClick={() => handleClicked('4')}
      >
        <span>4</span>
      </div>
      <div
        className={selectedSet === '5' ? 'active set-5' : 'set-5'}
        onClick={() => handleClicked('5')}
      >
        <span>5</span>
      </div>
    </>
  );
};

Set.propTypes = {
  selectedSet: PropTypes.string,
  setSelectedSet: PropTypes.func,
};

export default Set;
