import React from 'react';
import Toggle from 'react-toggle';
import "react-toggle/style.css"
import PropTypes from 'prop-types';

import { useDispatch } from 'react-redux';
import { GET_ACCOUNT_INFO } from '../store/actions/types';

const AccountToggle = (props) => {
  const { title, name, checked, accountInfo } = props;
  const dispatch = useDispatch();

  const handleToggle = () => {
    dispatch({
      type: GET_ACCOUNT_INFO,
      payload: {...accountInfo, [name]: !checked },
    });
  };

  return (
    <div className="account-edit account-toggle">
      <div className="account-values-left">
        <div className="account-edit-title">
          <span>{title}</span>
        </div>
      </div>
      <div className="account-values-right account-action">
        <div className="account-edit-button">
          <Toggle
            defaultChecked={checked}
            onChange={handleToggle}
          />
        </div>
      </div>
    </div>
  );
};

AccountToggle.propTypes = {
  title: PropTypes.string,
  name: PropTypes.string,
  checked: PropTypes.bool,
  accountInfo: PropTypes.object,
  setChecked: PropTypes.func,
};

export default AccountToggle;
