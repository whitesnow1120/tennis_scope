import React from 'react';
import PropTypes from 'prop-types';

const CustomCheckbox = (props) => {
  const { label, isChecked, setRoboPicks } = props;

  return (
    <div className="robopicks-checkbox">
      <label>
        <input
          type="checkbox"
          value={label}
          checked={isChecked}
          onChange={() => setRoboPicks(!isChecked)}
        />
        <span>{label}</span>
      </label>
    </div>
  );
};

CustomCheckbox.propTypes = {
  label: PropTypes.string,
  isChecked: PropTypes.bool,
  setRoboPicks: PropTypes.func,
};

export default CustomCheckbox;
