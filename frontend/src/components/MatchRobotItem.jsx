import React from 'react';
import PropTypes from 'prop-types';

const MatchRobotItem = (props) => {
  const { item, robotType = 0 } = props;
  const rightText =
    item['total'] + ' / ' + item['right'] + '   (' + item['percent'] + '%)';
  let boxStyle;
  if (robotType === 41 || robotType === 42) {
    boxStyle = 'match-box green-border';
  } else if (robotType === 43 || robotType === 44) {
    boxStyle = 'match-box orange-border';
  } else {
    boxStyle = 'match-box';
  }

  return (
    <div className="col-lg-4 col-md-6 col-sm-6 col-xs-12 mb-2 pb-2 pt-2 match-item">
      <div className={boxStyle}>
        <div className="current-match">
          <div className="robot-container">
            <div className="robot-header">
              <div className="robot-left">
                <span>{item['name']}</span>
              </div>
              <div className="robot-right">
                <span>{rightText}</span>
              </div>
            </div>
            <div className="robot-footer">
              <span>{item['detail']}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

MatchRobotItem.propTypes = {
  item: PropTypes.object,
  robotType: PropTypes.number,
};

export default MatchRobotItem;
