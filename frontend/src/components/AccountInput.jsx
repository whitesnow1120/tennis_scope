import React, { useState } from 'react';
import PropTypes from 'prop-types';

const AccountInput = (props) => {
  const { title, defaultValue, placeholder, value, setValue } = props;

  const [editEnabled, setEditEnabled] = useState({
    title: '',
    status: false,
  });

  /* Edit button is clicked */
  const handleEditClicked = () => {
    if (!value.opened) {
      setEditEnabled({
        title: title,
        status: true,
      });
      setValue({
        title: value.title,
        val: '',
        changed: false,
        opened: true,
      });
    }
  };

  /* Cancel button is clicked */
  const handleCancelClicked = () => {
    setEditEnabled({
      title: title,
      status: false,
    });
    setValue({
      title: value.title,
      val: '',
      changed: false,
      opened: false,
    });
  };

  /* Save button is clicked */
  const handleSaveClicked = () => {
    setEditEnabled({
      title: title,
      status: false,
    });
    setValue({
      title: value.title,
      val: value.val,
      changed: true,
      opened: false,
    });
  };

  /* Form input value is changed */
  const handleValueChanged = (e) => {
    setValue({
      title: title,
      val: e.target.value,
      changed: false,
      opened: true,
    });
  };

  return (
    <div
      className={
        editEnabled.status === false
          ? 'account-edit'
          : 'account-edit account-enable-edit'
      }
    >
      <div className="account-values-left">
        {editEnabled.status === false ? (
          <>
            <div className="account-edit-title">
              <span>{title}</span>
            </div>
            <div className="account-edit-defaltValue">
              <span>{defaultValue}</span>
            </div>
          </>
        ) : (
          <form>
            <div className="form-group">
              <input
                type="text"
                className="form-control"
                placeholder={placeholder}
                value={value.val}
                onChange={handleValueChanged}
              />
            </div>
          </form>
        )}
      </div>
      <div className="account-values-right account-action">
        {editEnabled.status === false ? (
          <div className="account-edit-button" onClick={handleEditClicked}>
            <span>Edit</span>
          </div>
        ) : (
          <div className="account-button-group">
            <div
              className="account-cancel-button pr-5"
              onClick={handleCancelClicked}
            >
              <span>Cancel</span>
            </div>
            <div className="account-save-button" onClick={handleSaveClicked}>
              <span>Save</span>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

AccountInput.propTypes = {
  title: PropTypes.string,
  defaultValue: PropTypes.string,
  placeholder: PropTypes.string,
  value: PropTypes.object,
  setValue: PropTypes.func,
};

export default AccountInput;
