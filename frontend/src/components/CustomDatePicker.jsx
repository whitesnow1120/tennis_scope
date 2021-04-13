import React, { useState, forwardRef } from 'react';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import PropTypes from 'prop-types';

const CustomDatePicker = (props) => {
  const { historyDate, setHistoryDate } = props;
  const [isOpened, setIsOpened] = useState(false);

  let selectedDate = historyDate;
  if (typeof historyDate === 'string') {
    selectedDate = new Date(selectedDate);
  }

  const handleDateSelect = (date) => {
    setHistoryDate(date.toString());
  };

  const handleCalendarOpen = () => {
    setIsOpened(true);
  };

  const handleCalendarClose = () => {
    setIsOpened(false);
  };

  const CustomSelectDatepicker = forwardRef(({ value, onClick }, ref) => (
    <div className="d-flex custom-datepicker" onClick={onClick}>
      <div className="custom-datepicker-date">
        <span ref={ref}>{value}</span>
      </div>
      <i
        className={
          isOpened ? 'fas fa-chevron-up fa-xs' : 'fas fa-chevron-down fa-xs'
        }
      ></i>
    </div>
  ));

  CustomSelectDatepicker.displayName = 'CustomSelectDatepicker';

  return (
    <DatePicker
      todayButton="Today"
      showMonthDropdown
      showYearDropdown
      dropdownMode="select"
      selected={selectedDate}
      onSelect={handleDateSelect}
      onCalendarClose={handleCalendarClose}
      onCalendarOpen={handleCalendarOpen}
      dateFormat="dd.MM.yyyy"
      customInput={<CustomSelectDatepicker />}
    />
  );
};

CustomDatePicker.propTypes = {
  value: PropTypes.func,
  onClick: PropTypes.func,
  historyDate: PropTypes.any,
  setHistoryDate: PropTypes.func,
};

export default CustomDatePicker;
