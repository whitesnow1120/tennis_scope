import React from 'react';
import PropTypes from 'prop-types';

const PerformanceStatistics = (props) => {
  const { statistics } = props;

  return (
    <>
      {statistics === undefined || statistics === null ? (
        <div className="loading-performance"></div>
      ) : (
        <div className="performance-statistics">
          <div className="performance-correct">
            <span>{statistics['correct']}</span>
          </div>
          <span>/</span>
          <div className="performance-total">
            <span>{statistics['total']}</span>
          </div>
          <div className="performance-percent">
            <span>{'(' + statistics['percent'] + '%)'}</span>
          </div>
        </div>
      )}
    </>
  );
};

PerformanceStatistics.propTypes = {
  statistics: PropTypes.object,
};

export default PerformanceStatistics;
